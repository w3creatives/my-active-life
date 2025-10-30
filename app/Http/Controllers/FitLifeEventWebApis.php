<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use App\Models\Event;
use App\Services\MilestoneImageService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class FitLifeEventWebApis extends BaseController
{
    public function getQuests(Request $request, $type = 'quest'): JsonResponse
    {
        $user = Auth::user();

        $participation = $user->participations()->where(['event_id' => $user->preferred_event_id])->whereHas('event')->first();

        if (is_null($participation)) {
            return $this->sendError('ERROR', ['error' => 'User is not participating in this event']);
        }

        $event = $participation->event;

        $pageLimit = $request->page_limit ?? 100;

        $activeTillDate = Carbon::now()->subDays(14)->format('Y-m-d');
        $currentDate = Carbon::now()->format('Y-m-d');

        $isJournal = ($type === 'journal');

        $isUpcoming = $request->list_type === 'upcoming';

        $cacheName = "quest_{$user->id}_{$isUpcoming}_{$request->event_id}_{$pageLimit}_{$request->is_archived}_{$currentDate}_{$activeTillDate}";

        // if(Cache::has($cacheName)){
        // $item = Cache::get($cacheName);
        // return $this->sendResponse($item, 'Response');
        // }

        $questRegistrations = $user->questRegistrations()
            ->where(function ($query) use ($request, $activeTillDate, $currentDate, $isJournal, $isUpcoming) {

                if ($isJournal) {
                    return $query;
                }

                if ($isUpcoming) {
                    return $query->where('archived', false)->where('date', '>=', $currentDate);
                }

                if ($request->is_archived) {
                    return $query->where('archived', true)->orWhere('date', '<', $activeTillDate);
                }

                return $query->where('archived', false)->where('date', '>=', $activeTillDate);
            })
            ->select(['id', 'date', 'notes', 'data', 'archived', 'shared', 'activity_id', 'created_at', 'updated_at', 'image'])
            ->with('activity', function ($query) {
                return $query->select(['id', 'sponsor', 'category', 'group', 'name', 'description', 'tags', 'total_points', 'social_hashtags', 'sports', 'available_from', 'available_until', 'data']);
            })
            ->whereHas('activity', function ($query) use ($event) {
                return $query->where('event_id', $event->id);
            })
            ->orderBy('date', $isJournal ? 'DESC' : 'ASC')
            ->simplePaginate($pageLimit)
            ->through(function ($item) use ($event) {
                if ($item->image) {
                    $item->image = url('uploads/quests/'.$item->image);
                }

                $activity = $item->activity;

                // $item->is_completed = $activity->milestones()->count() == $item->milestoneStatuses()->count();

                $milestone = $activity->milestones()->select(['id'])->where('total_points', '<=', 1000)->latest('total_points')->first();

                $hasCount = $item->milestoneStatuses()->where(['milestone_id' => $milestone ? $milestone->id : 0])->count();

                $item->is_completed = (bool) $hasCount;

                $activity->description = $this->htmlToPlainText($activity->description);

                $activity->bib_image = (new MilestoneImageService)->getBibImage($event->id, $activity->id, $item->is_completed);

                $item->activity = $activity;

                return $item;
            });

        // Cache::put($cacheName, $questRegistrations, now()->addHours(2));
        return $this->sendResponse($questRegistrations, 'Response');
    }

    private function htmlToPlainText($str): string
    {
        $str = str_replace('&nbsp;', ' ', $str);
        $str = html_entity_decode($str, ENT_QUOTES | ENT_COMPAT, 'UTF-8');
        $str = html_entity_decode($str, ENT_HTML5, 'UTF-8');
        $str = html_entity_decode($str);
        $str = htmlspecialchars_decode($str);
        $str = strip_tags($str);

        return preg_replace('~\h*(\R)\s*~', '$1', $str);
    }
}
