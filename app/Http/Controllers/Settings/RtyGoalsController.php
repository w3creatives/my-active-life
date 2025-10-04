<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class RtyGoalsController extends Controller
{
    /**
     * Get RTY mileage goal for the specified event
     */
    public function getGoal(Request $request): JsonResponse
    {
        $request->validate([
            'event_id' => [
                'required',
                Rule::exists((new Event)->getTable(), 'id'),
            ],
        ]);

        $user = $request->user();
        $participation = $user->participations()->where('event_id', $request->event_id)->first();

        if (is_null($participation)) {
            return response()->json([
                'success' => false,
                'message' => 'User is not participating in this event',
            ], 404);
        }

        $event = $participation->event;
        $settings = json_decode($user->settings, true);
        $eventSlug = Str::slug($event->name);
        $rtyGoals = $settings['rty_goals'] ?? [];

        $rtyGoal = collect($rtyGoals)->filter(function ($goal) use ($eventSlug) {
            return in_array($eventSlug, array_keys($goal));
        })->pluck($eventSlug)->first();

        return response()->json([
            'success' => true,
            'data' => [
                'rty_mileage_goal' => $rtyGoal,
            ],
        ]);
    }

    /**
     * Get activity modalities for the specified event
     */
    public function getModalities(Request $request): JsonResponse
    {
        $request->validate([
            'event_id' => [
                'required',
                Rule::exists((new Event)->getTable(), 'id'),
            ],
        ]);

        $user = $request->user();
        $participation = $user->participations()->where('event_id', $request->event_id)->first();

        if (is_null($participation)) {
            return response()->json([
                'success' => false,
                'message' => 'User is not participating in this event',
            ], 404);
        }

        $settings = json_decode($participation->settings, true);
        $modalityOverrides = $settings['modality_overrides'] ?? [];
        $modalities = ['bike', 'swim', 'other'];

        $data = collect($modalities)->map(function ($item) use ($modalityOverrides) {
            return [
                'name' => $item,
                'enabled' => in_array($item, $modalityOverrides),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'modalities' => $data,
            ],
        ]);
    }

    /**
     * Update RTY mileage goal
     */
    public function updateGoal(Request $request): RedirectResponse
    {
        $request->validate([
            'mileage_goal' => 'required',
            'event_id' => [
                'required',
                Rule::exists((new Event)->getTable(), 'id'),
            ],
        ]);

        $user = $request->user();
        $participation = $user->participations()->where('event_id', $request->event_id)->first();

        if (is_null($participation)) {
            return back()->with('error', 'User is not participating in this event');
        }

        $event = $participation->event;
        $settings = json_decode($user->settings, true);
        $rtyGoals = $settings['rty_goals'] ?? [];
        $eventSlug = Str::slug($event->name);
        $mileageGoal = $request->mileage_goal;

        $hasRtyGoal = collect($rtyGoals)->where($eventSlug, '!=', null)->count();

        if ($hasRtyGoal) {
            $rtyGoals = collect($rtyGoals)->map(function ($goal) use ($eventSlug, $mileageGoal) {
                if (in_array($eventSlug, array_keys($goal))) {
                    $goal[$eventSlug] = $mileageGoal;
                }

                return $goal;
            })->toArray();
        } else {
            $rtyGoals[] = [$eventSlug => $mileageGoal];
        }

        $settings['rty_goals'] = $rtyGoals;
        $user->fill(['settings' => json_encode($settings)])->save();

        return back()->with('success', sprintf('Successfully updated your goal for %s to be %s miles.', $event->name, $mileageGoal));
    }

    /**
     * Update event modality setting
     */
    public function updateModality(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|in:bike,swim,other',
            'enabled' => 'required|boolean',
            'event_id' => [
                'required',
                Rule::exists((new Event)->getTable(), 'id'),
            ],
        ]);

        $user = $request->user();
        $participation = $user->participations()->where('event_id', $request->event_id)->first();

        if (is_null($participation)) {
            return back()->with('error', 'User is not participating in this event');
        }

        $settings = json_decode($participation->settings, true);
        $modalityName = $request->name;
        $enabled = $request->enabled;
        $modalityOverrides = $settings['modality_overrides'] ?? [];

        $collection = collect($modalityOverrides);
        $modalityOverrides = $collection->merge($modalityName);

        $modalityOverrides = $modalityOverrides->filter(function ($item) use ($modalityName, $enabled) {
            if (! $enabled) {
                return $modalityName !== $item;
            }

            return true;
        })->unique()->values()->toArray();

        $settings['modality_overrides'] = $modalityOverrides;
        $participation->fill(['settings' => json_encode($settings)])->save();

        return back()->with('success', 'Activity type updated successfully.');
    }
}
