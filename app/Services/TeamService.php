<?php

namespace App\Services;

use App\Repositories\TeamRepository;

class TeamService
{
    public function __construct(
        protected TeamRepository $teamRepository
    ) {
    }
    
    public function formatTeam($team, $user){
        
        if(is_null($team)) return null;
        
        $team->is_team_owner = $team->owner_id == $user->id;
        
        $eventId = $team->event_id;
        
        $membershipStatus = null;
        if($team->requests()->where(["prospective_member_id" => $user->id,"event_id" => $eventId])->count()){
            $membershipStatus = "RequestedJoin";
        } else if($team->memberships()->where(["user_id" => $user->id,"event_id" => $eventId])->count()){
            $membershipStatus = "Joined";
        } else  if($team->invites()->where(["prospective_member_id" => $user->id,"event_id" => $eventId])->count()){
            $membershipStatus = "JoinInProcess";
        }
        $team->membership_status = $membershipStatus;
        
        unset($team->requests);
        unset($team->memberships);
        unset($team->invites);
        unset($team->owner_id);
        
        return $team;
    }
    
    public function find($id)
    {
        return $this->teamRepository->find($id);
    }
    
    public function achievements($event, $dateRange, $team){
        return $this->teamRepository->achievements($event, $dateRange, $team);
    }
}