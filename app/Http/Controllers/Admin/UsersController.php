<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use App\Utilities\DataTable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class UsersController extends Controller
{
    public function index(Request $request, DataTable $dataTable)
    {
        if ($request->ajax()) {

            $query = User::select(['first_name', 'last_name', 'email', 'display_name', 'id'])
                ->where('super_admin', false);

            [$userCount, $users] = $dataTable->setSearchableColumns(['first_name', 'last_name', 'email', 'display_name'])
                ->query($request, $query)->response();

            $users = $users->map(function ($user) {
                $user->action = [
                    view('admin.users.actions.user', compact('user'))->render(),
                ];

                return $user;
            });

            return response()->json([
                'draw' => $request->get('draw'),
                'recordsTotal' => $userCount,
                'recordsFiltered' => $userCount,
                'data' => $users,
            ]);

        }

        return view('admin.users.list');
    }

    public function create(Request $request)
    {

        $user = User::find($request->route()->parameter('id'));

        $events = Event::active()->orderBy('end_date', 'DESC');

        if ($user) {
            $events->orWhereHas('participations', function ($query) use ($user) {
                return $query->where('user_id', $user->id);
            });
        }
        $events = $events->get();

        // return $events;

        return view('admin.users.create', compact('user', 'events'));
    }

    public function store(Request $request)
    {
        $user = User::find($request->route()->parameter('id'));

        $request->validate([
            'first_name' => 'required|alpha|max:255',
            'last_name' => 'required|alpha|max:255',
            'display_name' => 'required|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user),
            ],
            'password' => [
                'required_if_accepted:enabled_password',
                Rule::excludeIf(!$request->get('enabled_password', false)),
                Password::min(6),
            ],
            'confirm_password' => [
                'required_if_accepted:enabled_password',
                'same:password',
            ],
            'event' => 'required|array|min:1',
        ]);

        $data = $request->only(['first_name', 'last_name', 'email', 'display_name']);

        if ($request->get('enabled_password')) {
            $data['encrypted_password'] = $request->get('password');
        }

        if ($user) {
            $user->fill($data)->save();
            $flashMessage = 'User details updated successfully.!';
        } else {
            $user = User::create($data);
            $flashMessage = 'User details created successfully.!';
        }

        $events = Event::whereIn('id', $request->get('event'))->get();

        foreach ($events as $event) {
            $user->participations()
                ->updateOrCreate(
                    ['event_id' => $event->id],
                    [
                        'event_id' => $event->id,
                        'subscribed' => true,
                        'subscription_start_date' => Carbon::parse($request->input('start_date.' . $event->id))->format('Y-m-d'),
                        'subscription_end_date' => Carbon::parse($request->input('end_date.' . $event->id))->format('Y-m-d'),
                    ]
                );
        }

        $userParticipations = $user->participations()->whereNotIn('event_id', $request->get('event'))->with('event')->get();

        if ($userParticipations->count()) {
            foreach ($userParticipations as $userParticipation) {
                $event = $userParticipation->event;

                switch ($event->event_type) {
                    case 'fit_life':
                        $hasRegistration = $event->fitLifeRegistrations()->where('user_id', $user->id)->count();

                        if (!$hasRegistration) {
                            $userParticipation->delete();
                        }
                        break;
                    case 'regular':
                    case 'promotional':
                        $hasPoint = $user->points()->where('event_id', $event->id)->count();

                        if (!$hasPoint) {
                            $userParticipation->delete();
                        }
                        break;
                }
            }
        }

        return redirect()->route('admin.users')->with('alert', ['type' => 'success', 'message' => $flashMessage]);
    }
}
