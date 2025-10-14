<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\EventMilestone;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class SharedMilestoneController extends Controller
{
    public function show(Request $request, int $milestoneId): Response
    {
        $milestone = EventMilestone::with('event')->findOrFail($milestoneId);
        $event = $milestone->event;

        $isTeam = $request->boolean('team', false);

        // Get the appropriate image URL
        // Use the logo images as primary, fall back to bib images
        if ($isTeam) {
            $imageUrl = $milestone->team_logo ?? $milestone->team_bib_image ?? $milestone->logo ?? $milestone->bib_image;
        } else {
            $imageUrl = $milestone->logo ?? $milestone->bib_image;
        }

        // Build share title based on Ruby documentation pattern
        $eventName = $event ? $event->name : '';
        $hashtags = $event && $event->social_hashtags ? $event->social_hashtags : '';

        if ($isTeam) {
            $shareTitle = sprintf(
                "My team and I just reached %s in our %s journey. That's %d miles y'all! %s",
                $milestone->name,
                $eventName,
                (int) $milestone->distance,
                $hashtags
            );
        } else {
            $shareTitle = sprintf(
                "I just reached %s in %s journey. That's %d miles y'all! %s",
                $milestone->name,
                $eventName,
                (int) $milestone->distance,
                $hashtags
            );
        }

        return Inertia::render('SharedMilestone', [
            'milestone' => [
                'id' => $milestone->id,
                'name' => $milestone->name,
                'distance' => $milestone->distance,
                'description' => $milestone->description,
                'image_url' => $imageUrl,
                'video_url' => $milestone->video_url,
            ],
            'event' => $event ? [
                'id' => $event->id,
                'name' => $event->name,
                'hashtags' => $hashtags,
                'registration_url' => $event->registration_url,
            ] : null,
            'shareTitle' => $shareTitle,
            'isTeam' => $isTeam,
        ]);
    }
}
