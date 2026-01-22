<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class DataSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'id' => 1,
                'name' => 'Manual',
                'short_name' => 'manual',
                'description' => 'Manual entry via the RTE website',
                'resynchronizable' => false,
                'profile' => '{}',
            ],
            [
                'id' => 2,
                'name' => 'Fitbit',
                'short_name' => 'fitbit',
                'description' => 'Fitbit activity trackers',
                'resynchronizable' => false,
                'profile' => '{}',
            ],
            [
                'id' => 3,
                'name' => 'Garmin',
                'short_name' => 'garmin',
                'description' => 'Garmin activity trackers',
                'resynchronizable' => false,
                'profile' => '{}',
            ],
            [
                'id' => 4,
                'name' => 'Strava',
                'short_name' => 'strava',
                'description' => 'Strava activity trackers',
                'resynchronizable' => false,
                'profile' => '{}',
            ],
            [
                'id' => 5,
                'name' => 'Apple',
                'short_name' => 'apple',
                'description' => 'Apple native activity app',
                'resynchronizable' => false,
                'profile' => '{}',
            ],
            [
                'id' => 6,
                'name' => 'Oura Ring',
                'short_name' => 'ouraring',
                'description' => 'Oura Ring activity trackers',
                'resynchronizable' => false,
                'profile' => '{}',
            ],
            [
                'id' => 7,
                'name' => 'Samsung Health',
                'short_name' => 'samsung',
                'description' => 'Samsung Health activity tracker',
                'resynchronizable' => false,
                'profile' => '{}',
            ],
        ];

        $table = DB::table('data_sources');

        $table->truncate();

        $table->insert($data);
    }
}
