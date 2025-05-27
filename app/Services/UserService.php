<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use App\Repositories\UserRepository;
use Illuminate\Support\Str;

final class UserService
{
    public function __construct(
        protected UserRepository $userRepository
    ) {}

    public function find($id)
    {
        return $this->userRepository->find($id);
    }

    public function basic($request)
    {
        return $this->userRepository->basic($request);
    }

    public function profile($request)
    {
        return $this->userRepository->profile($request);
    }

    public function achievements($event, $dateRange, $user)
    {
        return $this->userRepository->achievements($event, $dateRange, $user);
    }

    public function createShopifyUser($sopifyOrder, $data, $metafields)
    {
        if (! $data || ! (isset($metafields['subtitle']))) {
            return false;
        }

        // $event = Event::where('name',$metafields['subtitle'])->latest()->first();

        $subtitles = explode('|', $metafields['subtitle']);

        $userData = [];

        foreach ($data as $item) {
            $key = null;
            if (Str::contains($item['name'], 'mail')) {
                $key = 'email';
            } elseif (Str::contains($item['name'], 'rstName')) {
                $key = 'first_name';
            } elseif (Str::contains($item['name'], 'astName')) {
                $key = 'last_name';
            }

            if ($key === null) {
                continue;
            }

            $userData[$key] = $item['value'];
        }

        if (! $userData || ! isset($userData['email']) || ! $userData['email']) {

            if (! $sopifyOrder) {
                return false;
            }

            $userData = [
                'email' => $sopifyOrder->email,
                'first_name' => $sopifyOrder->first_name,
                'last_name' => $sopifyOrder->last_name,
            ];
        }

        $user = $this->userRepository->findByEmail($userData['email']);

        if (! $user) {
            $userData['display_name'] = implode(' ', [$userData['first_name'], $userData['last_name']]);
            $userData['email'] = mb_strtolower($userData['email']);
            $userData['encrypted_password'] = 'gfh19ab';
            $userData['preferred_event_id'] = 0;

            $user = $this->userRepository->createShopifyUser($userData);
        }

        foreach ($subtitles as $subtitle) {
            $event = Event::where('name', $subtitle)->latest()->first();

            if ($event) {
                if (! $user->preferred_event_id) {
                    $user->fill(['preferred_event_id' => $event->id])->save();
                }
                $hasEvent = $user->participations()->where(['event_id' => $event->id])->count();

                if (! $hasEvent) {
                    $user->participations()->create(['event_id' => $event->id, 'subscription_end_date' => $event->end_date]);
                }
            }
        }

        return $user;

    }

    public function timezones()
    {
        return config('timezones.timezone');
    }

    public function followers($user, $event_id, $pageLimit = 100)
    {
        return $user->followers()->where('event_id', $event_id)->simplePaginate($pageLimit)
            ->through(function ($item) {
                $follower = $item->follower;

                return [
                    'id' => $follower->id,
                    'display_name' => trim($follower->display_name),
                    'first_name' => trim($follower->first_name),
                    'last_name' => trim($follower->last_name),
                    'total_miles' => $follower->totalPoints()->where('event_id', $item->event_id)->sum('amount'),
                ];
            });
    }

    public function followings($user, $event_id, $pageLimit = 100)
    {
        return $user->following()->where('event_id', $event_id)->simplePaginate($pageLimit)
            ->through(function ($item) {
                $follower = $item->following;

                return [
                    'id' => $follower->id,
                    'display_name' => trim($follower->display_name),
                    'first_name' => trim($follower->first_name),
                    'last_name' => trim($follower->last_name),
                    'total_miles' => $follower->totalPoints()->where('event_id', $item->event_id)->sum('amount'),
                ];
            });
    }
}
