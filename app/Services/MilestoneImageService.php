<?php

namespace App\Services;

use App\Models\Event;
use App\Models\FitLifeActivity;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MilestoneImageService
{
    private const BASE_IMAGE_PATH = 'images';

    public function getMilestoneImage(int $eventId, float $distance, ?int $activityId = null): array
    {
        $event = Event::find($eventId);
        $imagePath = [];


        if (! $event) {
            return [
                'error' => 'Event not found',
                'status' => false,
            ];
        }

        if ($activityId) {
            $activity = FitLifeActivity::where('id', $activityId)
                ->where('event_id', $eventId)
                ->first();

            $distance = (float) $activity->total_points;

            if (! $activity) {
                return [
                    'error' => 'Activity not found or does not belong to this event',
                    'status' => false,
                ];
            }

            $imagePath = $this->buildImagePathFromActivity($event, $activity, $distance);
        }

        return $imagePath;
    }

    public function getBibImage($eventId, $activityId, $isCompleted = false)
    {
        $bibImages = $this->getMilestoneImage($eventId, 0, $activityId);
        $images = '';

        if (! empty($bibImages['bib'])) {
            foreach ($bibImages['bib'] as $image) {
                if ($isCompleted) {
                    if (!str_contains($image['filename'], '-bw')) {
                        $images = $image['url'];
                    }
                } else {
                    if (str_contains($image['filename'], '-bw')) {
                        $images = $image['url'];
                    }
                }
            }
        }
        return $images;
    }

    private function buildImagePathFromActivity(Event $event, FitLifeActivity $activity, float $distance): array
    {
        // Convert activity attributes to slugs
        $sponsorSlug = Str::slug($activity->sponsor);
        $categorySlug = Str::slug($activity->category);
        $groupSlug = Str::slug($activity->group);

        // Build the path structure: images/{event_id}/{sponsor-slug}/{category-slug}/{group-slug}/{event_id}_{distance}_{activity_id}.jpg
        $directoryPath = '';

        //if ($event->id === 77) {
        $directoryPath = sprintf(
            '%s/%s/%s/%s/%s%s',
            self::BASE_IMAGE_PATH,
            'the-heros-journey',
            $sponsorSlug,
            $categorySlug,
            $groupSlug,
            '/the-heros-journey'
        );
        //}

        $fullPath = public_path($directoryPath);

        if (! File::exists($fullPath)) {
            return [];
        }

        // Get all files in the directory
        $files = File::files($fullPath);
        $matchingImages = [];

        foreach ($files as $file) {
            $filename = $file->getFilename();

            // Match pattern: event_id_distance_activity_id.jpg
            $pattern = sprintf('/-%d.*\.(jpg|jpeg|png)$/i',
                (int) $distance
            );

            if (preg_match($pattern, $filename)) {
                $imageData = [
                    'url' => asset($directoryPath.'/'.$filename),
                    'path' => $directoryPath.'/'.$filename,
                    'filename' => $filename,
                ];

                if (stripos($filename, 'calendar') !== false) {
                    $matchingImages['calendar'][] = $imageData;
                } else {
                    $matchingImages['bib'][] = $imageData;
                }
            }
        }

        return $matchingImages;
    }
}
