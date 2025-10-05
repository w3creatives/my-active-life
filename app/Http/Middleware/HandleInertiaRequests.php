<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Event;
use App\Traits\UserEventParticipationTrait;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

final class HandleInertiaRequests extends Middleware
{
    use UserEventParticipationTrait;

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $manager = app('impersonate');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'alert' => $request->session()->get('alert'),
            'auth' => [
                'user' => $request->user(),
                'total_points' => function () use ($request) {
                    if (! $request->user()) {
                        return 0;
                    }

                    $totalPoints = (float) $request->user()->totalPoints()->where('event_id', $request->user()->preferred_event_id)->first()->amount ?? 0.0;

                    return round($totalPoints, 2);
                },
                'preferred_event' => function () use ($request) {
                    if (! $request->user()) {
                        return null;
                    }

                    return $request->user()->preferred_event_id ?
                        Event::find($request->user()->preferred_event_id) : null;
                },
                'participations' => function () use ($request) {
                    if (! $request->user()) {
                        return [];
                    }

                    return $this->userParticipations($request->user());
                },
                'is_admin' => $request->user() && (bool) $request->user()->super_admin,
                'is_impersonating' => $manager->isImpersonating(),
            ],
            'ziggy' => fn (): array => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }
}
