<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Http\Controllers\API\BaseController;

use App\Models\{
    Event,
    FitLifeActivity,
    FitLifeActivityRegistration,
    User
};

class QuestsController extends BaseController
{
    public function all(Request $request, $type = 'quest'): JsonResponse
    {
        $request->validate([
            "event_id" => [
                'required',
                Rule::exists((new Event)->getTable(),'id'),
            ]
        ]);

        $user = $request->user();

        $participation = $user->participations()->where(['event_id' => $request->event_id])->whereHas('event')->first();

        if(is_null($participation)) {
            return $this->sendError('ERROR', ['error'=>'User is not participating in this event']);
        }

        $event = $participation->event;

        $pageLimit = $request->page_limit??100;

        $activeTillDate = Carbon::now()->subDays(14)->format('Y-m-d');
        $currentDate = Carbon::now()->format('Y-m-d');

        $isJournal = ($type == 'journal');

        $isUpcoming = $request->list_type == 'upcoming';

        $cacheName = "quest_{$user->id}_{$isUpcoming}_{$request->event_id}_{$pageLimit}_{$request->is_archived}_{$currentDate}_{$activeTillDate}";

       if(Cache::has($cacheName)){
           $item = Cache::get($cacheName);
          // return $this->sendResponse($item, 'Response');
       }

        $questRegistrations = $user->questRegistrations()
        ->where(function($query) use($request, $activeTillDate,$currentDate, $isJournal, $isUpcoming){

            if($isJournal) {
                return $query;
            }

            if($isUpcoming) {
                return $query->where('archived', false)->where('date','>=',$currentDate);
            }

            if($request->is_archived) {
                return $query->where('archived', true)->orWhere('date','<',$activeTillDate);
            }

            return $query->where('archived', false)->where('date','>=',$activeTillDate);
        })
        ->select(['id','date','notes','data','archived','shared','activity_id','created_at','updated_at','image'])
        ->with('activity', function($query){
            return $query->select(['id','sponsor','category','group','name','description','tags','total_points','social_hashtags','sports','available_from','available_until','data']);
        })
        ->whereHas('activity', function($query) use($event){
            return $query->where('event_id', $event->id);
        })
        ->orderBy('date',$isJournal?'DESC':'ASC')
        ->simplePaginate($pageLimit)
        ->through(function ($item){
            if($item->image){
                $item->image = url("uploads/quests/".$item->image);
            }

            $activity = $item->activity;

            //$item->is_completed = $activity->milestones()->count() == $item->milestoneStatuses()->count();

             $milestone = $activity->milestones()->select(['id'])->where('total_points','<=',1000)->latest('total_points')->first();

             $hasCount = $item->milestoneStatuses()->where(['milestone_id' => $milestone?$milestone->id:0])->count();

             $item->is_completed = !!$hasCount;

            $activity->description = $this->htmlToPlainText($activity->description);

            $item->activity =  $activity;
            return $item;
        });
                Cache::put($cacheName, $questRegistrations, now()->addHours(2));
        return $this->sendResponse($questRegistrations, 'Response');
    }

     public function findOne(Request $request, $id): JsonResponse
     {
         $user = $request->user();

         $cacheName = "quest_{$user->id}_{$id}";

       if(Cache::has($cacheName)){
           $item = Cache::get($cacheName);
           //return $this->sendResponse($item, 'Response');
       }

         $item = $user->questRegistrations()->where('id',$id)
        ->select(['id','date','notes','data','archived','shared','activity_id','created_at','updated_at','image'])
        ->with('activity', function($query){
            return $query->select(['id','sponsor','category','group','name','description','tags','total_points','social_hashtags','sports','available_from','available_until','data']);
        })
        ->first();

        if(is_null($item)) {
            return $this->sendError('ERROR', ['error'=>'Quest not found']);
        }

        if($item->image){
            $item->image = url("uploads/quests/".$item->image);
        }

        $activity = $item->activity;

        $activity->description = $this->htmlToPlainText($activity->description);

        $item->activity =  $activity;

       // $item->is_completed = $activity->milestones()->count() == $item->milestoneStatuses()->count();

         $milestone = $activity->milestones()->select(['id'])->where('total_points','<=',1000)->latest('total_points')->first();

         $hasCount = $item->milestoneStatuses()->where(['milestone_id' => $milestone?$milestone->id:0])->count();

         $item->is_completed = !!$hasCount;

         Cache::put($cacheName, $item, now()->addHours(2));

        return $this->sendResponse($item, 'Response');
     }

    private function htmlToPlainText($str){
        $str = str_replace('&nbsp;', ' ', $str);
        $str = html_entity_decode($str, ENT_QUOTES | ENT_COMPAT , 'UTF-8');
        $str = html_entity_decode($str, ENT_HTML5, 'UTF-8');
        $str = html_entity_decode($str);
        $str = htmlspecialchars_decode($str);
        $str = strip_tags($str);
        return preg_replace('~\h*(\R)\s*~', '$1', $str);
        return trim(preg_replace('/\s+/', ' ', $str));
    }

    public function questActivities(Request $request): JsonResponse
    {

        $request->validate([
            "event_id" => [
                'required',
                Rule::exists((new Event)->getTable(),'id'),
            ]
        ]);

        $user = $request->user();

          $cacheName = "quest_activity_{$user->id}_{$request->event_id}";

       if(Cache::has($cacheName)){
           $item = Cache::get($cacheName);
          // return $this->sendResponse($item, 'Response');
       }

        $participation = $user->participations()->where(['event_id' => $request->event_id])->whereHas('event')->first();

        if(is_null($participation)) {
            return $this->sendError('ERROR', ['error'=>'User is not participating in this event']);
        }

        $event = $participation->event;

        $activities = $event->fitActivities()
        ->select(['id','sponsor','category','group','name','description','tags','total_points','social_hashtags','sports','available_from','available_until','data'])
        //->available()
        ->get();

        $activities = $activities->map(function($activity) use($user){
            $activity->description = $this->htmlToPlainText($activity->description);

            $items = $user->questRegistrations()->where('activity_id', $activity->id)->get();

            $registrationCount = $items->count();

            $milestone = $activity->milestones()->select(['id'])->where('total_points','<=',1000)->latest('total_points')->first();

           $completedCount = 0;

           if($registrationCount)  {
            foreach($items as $item){
                $hasCount = $item->milestoneStatuses()->where(['milestone_id' => $milestone?$milestone->id:0])->count();

                if($completedCount) {
                    $completedCount += 1;
                }
            }

            $activity->is_completed = ($completedCount == $items->count());
           } else {
               $activity->is_completed = false;
           }

           $activity->quest_count = $registrationCount;

            return $activity;
        });
        Cache::put($cacheName, $activities, now()->addHours(2));
             //$activity->description = $this->htmlToPlainText($activity->description);
               /*$milestone = $activity->milestones()->select(['id'])->where('total_points','<=',1000)->latest('total_points')->first();

             $hasCount = $item->milestoneStatuses()->where(['milestone_id' => $milestone?$milestone->id:0])->count();

             $item->is_completed = !!$hasCount;*/
        return $this->sendResponse($activities, 'Response');
    }

    public function registration(Request $request): JsonResponse
    {
        $request->validate([
            "event_id" => [
                'required',
                Rule::exists((new Event)->getTable(),'id'),
            ],
             "activity_id" => [
                'required',
                Rule::exists((new FitLifeActivity)->getTable(),'id'),
            ],
            'invitees_email.*' => [
                'email',
                //Rule::exists((new User)->getTable(),'email'),
            ],
            'date' => 'required|date'
        ],
        [
            "invitees_email.*.exists" => "Ah Shucks! This person (:input) is either not yet registered for this challenge or this in the wrong email. Check with them and try again!"
        ]);

        $user = $request->user();

        $participation = $user->participations()->where(['event_id' => $request->event_id])->whereHas('event')->first();

        if(is_null($participation)) {
            return $this->sendError('ERROR', ['error'=>'User is not participating in this event']);
        }

        $event = $participation->event;

        $activity = $event->fitActivities()->find($request->activity_id);


        if(is_null($activity)){
             return $this->sendError('ERROR', ['error'=>'Quest activity not found']);
        }

        $registration = $activity->registrations()->where(['user_id' => $user->id,'date' => $request->date])->first();

        if(!is_null($registration)){
             return $this->sendError('ERROR', ['error'=>'Quest is already Scheduled for given date']);
        }

        $registration = $activity->registrations()->create(['user_id' => $user->id,'date' => $request->date]);

        //$registration->milestoneStatuses()->create(['']);

        if($request->invitees_email) {
            foreach($request->invitees_email as $inviteeEmail){
                $invitee = User::where('email', $inviteeEmail)->first();

                $activity->invitations()->create(['inviter_id' => $user->id, 'invitee_id' => $invitee?$invitee->id:NULL,'invitee_email' =>$inviteeEmail,'accepted' => false,'secret' => strtoupper(str()->random(32))]);
            }
        }

         return $this->sendResponse([], sprintf("Successfully scheduled Quest '%s'", $activity->name));
    }

    public function updateRegistration(Request $request): JsonResponse
    {
        $request->validate([
            'quest_id' => [
                'required',
                Rule::exists((new FitLifeActivityRegistration)->getTable(),'id'),
            ],
            'note' => 'required|max:100',
            'photo' => ['required', 'extensions:jpg,png'],
        ]);

        $user = $request->user();

        $quest = $user->questRegistrations()->find($request->quest_id);

        if(is_null($quest)) {
            return $this->sendError('ERROR', ['error'=>'Quest not found']);
        }

        $file = $request->file('photo');

        $imageName = uniqid(time(), true).'.'.$file->getClientOriginalExtension();
        $file->move(public_path('/uploads/quests'), $imageName);

        $quest->fill(['notes' => $request->note,'image' => $imageName])->save();
        return $this->sendResponse([],"Quest updated");

    }
     private function deleteQuestForeignData($id){
        $tables = DB::select("select table_name from information_schema.columns where column_name = 'registration_id'");

        foreach($tables as $table) {
            DB::table($table->table_name)->where('registration_id', $id)->delete();
        }
    }
     public function deleteRegistration(Request $request): JsonResponse
    {
        $request->validate([
            'quest_id' => [
                'required',
                Rule::exists((new FitLifeActivityRegistration)->getTable(),'id'),
            ]
        ]);

        $user = $request->user();

        $quest = $user->questRegistrations()->find($request->quest_id);

        if(is_null($quest)) {
            return $this->sendError('ERROR', ['error'=>'Quest not found']);
        }

        $file = public_path('/uploads/quests/'.$quest->image);

         try{
            $this->deleteQuestForeignData($quest->id);
            $quest->delete();

            if (File::exists($file)) {
                 File::delete($file);
            }

            return $this->sendResponse([],"Quest Deleted");
         } catch(Exception $e) {
               return $this->sendError('ERROR', ['error'=>'Something went wrong']);
         }
    }

    public function archiveRegistration(Request $request): JsonResponse
    {
        $request->validate([
            'quest_id' => [
                'required',
                Rule::exists((new FitLifeActivityRegistration)->getTable(),'id'),
            ]
        ]);

        $user = $request->user();

        $quest = $user->questRegistrations()->find($request->quest_id);

        if(is_null($quest)) {
            return $this->sendError('ERROR', ['error'=>'Quest not found']);
        }

        $quest->fill(['archived' => true])->save();

        return $this->sendResponse([],"Quest moved to history");
    }
}
