<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class SessionsController extends BaseController
{
    public function login(Request $request): JsonResponse
    {

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();

            $hasTeam = Team::where(function ($query) use ($user) {
                return $query->where('owner_id', $user->id)
                    ->orWhereHas('memberships', function ($query) use ($user) {
                        return $query->where('user_id', $user->id);
                    });
            })->first();

            $preferredTeam = Team::where(function ($query) use ($user) {
                return $query->where('owner_id', $user->id)->where('event_id', $user->preferred_event_id)
                    ->orWhereHas('memberships', function ($query) use ($user) {
                        return $query->where('user_id', $user->id)->where('event_id', $user->preferred_event_id);
                    });
            })->first();
            $success['id'] = $user->id;
            $success['token'] = $user->createToken('MyApp')->accessToken;
            $success['name'] = $user->display_name;
            $success['first_name'] = $user->first_name;
            $success['last_name'] = $user->last_name;
            $success['email'] = $user->email;
            $success['time_zone'] = $user->time_zone;
            $success['time_zone_name'] = $user->time_zone_name;
            $success['settings'] = $user->settings;
            $success['preferred_event_id'] = $user->preferred_event_id;

            $success['has_team'] = (bool) $preferredTeam;
            $success['preferred_team_id'] = $preferredTeam ? $preferredTeam->id : null;
            $success['preferred_team'] = $preferredTeam;

            return $this->sendResponse($success, 'User login successfully.');
        }

        return $this->sendError('Invalid email address or password', ['error' => 'Invalid email address or password']);

    }
}
