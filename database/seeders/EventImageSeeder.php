<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Event;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class EventImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run1(): void
    {

        $imageFolders = File::allDirectories(public_path('images'));

        foreach ($imageFolders as $imageFolder) {

        }

        dd($imageFolders);

    }

    public function run(): void
    {

        $events = Event::query()->where('id', 55)->get();

        foreach ($events as $event) {

            $imagePathSlug = Str::slug($event->name);

            $directory = public_path('images/'.$imagePathSlug);

            try {
                $files = File::files($directory);
            } catch (Exception $exception) {
                continue;
            }

            if (empty($files)) {
                continue;
            }

            $eventLogoImageName = null;

            foreach ($files as $file) {
                $isBannerImage = Str::of($file->getFilename())->contains(['Banner', 'header-image', 'header', 'banner']);

                if (! $isBannerImage) {
                    continue;
                }
                $eventLogoImageName = $file->getFilename();
            }

            $eventSubFolders = File::directories($directory);

            foreach ($eventSubFolders as $eventSubFolder) {
                $images = File::files($eventSubFolder);

                dd($images);
            }

            Log::info('Files for '.$imagePathSlug, [$files]);

        }
    }
}
