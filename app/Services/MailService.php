<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;

class MailService
{
    //private $apiUrl = "https://staging-tracker.runtheedge.com/api/v1/";
    private $apiUrl;
    
    public function __construct()
    {
        $this->apiUrl = env('TRACKER_API_URL');
    }
    
    public function sendCelebrationMail($event_id,$user_id){
        
        $queryString = http_build_query(compact('event_id','user_id'));
        
        $this->sendRequest('send_celebration_mail?'.$queryString);
    }
    
    public function sendPasswordResetEmail($email){
        
        $data = ['user' => ['email' => $email]];
        
        return $this->sendRequest('passwords',$data);
    }
    
    public function sendTeamInviteEmail($email,$team_id){
        $data = compact('email', 'team_id');
        
        return $this->sendRequest('team_invite_emails',$data);
    }
    
    private function sendRequest($url, $data=[]){
        return Http::post($this->apiUrl.$url,$data);
    }
}