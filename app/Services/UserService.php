<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use App\Repositories\UserRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
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

    public function currentAchievements($event, $dateRange, $user)
    {
        return $this->userRepository->currentAchievements($event, $dateRange, $user);
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
                    $user->participations()->create([
                        'event_id' => $event->id,
                        'subscription_start_date' => $event->start_date,
                        'subscription_end_date' => $event->end_date,
                    ]);

                    if ($event->id === 2) {
                        $user->participations()->where(['event_id' => $event->id])->update(['subscription_start_date' => Carbon::now()->format('Y-m-d')]);
                    }
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

    public function followings($user, $event_id, $perPage = 100, string $source = 'api')
    {
        $paginationArgs = $source === 'web'
            ? [$perPage, ['*'], 'userFollowingPage']
            : [$perPage];

        return $user->following()
            ->where('event_id', $event_id)
            ->simplePaginate(...$paginationArgs)
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

    public function monthlies(int $eventId, User $user): Collection
    {
        return $this->userRepository->getMonthlyPoints($eventId, $user);
    }

    public function total(int $eventId, User $user): float
    {
        return (float) $user->totalPoints()->where('event_id', $eventId)->sum('amount');
    }

    public function yearlyTotal(int $eventId, User $user)
    {
        return $this->userRepository->yearlyTotal($eventId, $user);
    }
    public function yearlyMonthTotal(int $eventId, User $user)
    {
        return $this->userRepository->yearlyMonthTotal($eventId, $user);
    }
}
