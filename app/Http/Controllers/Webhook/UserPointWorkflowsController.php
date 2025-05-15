<?php
 
namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\{
    EventService
};

use App\Models\{
    EventParticipation
};
 
class UserPointWorkflowsController extends Controller
{
    public function triggerWorkFlow(Request $request, EventService $eventService){
        $participations = EventParticipation::where('event_id',64)->get();
        
        foreach($participations as $participation){
        
            $eventService->userPointWorkflow($participation->user_id, $participation->event_id);
        }
        
    }
    
}
