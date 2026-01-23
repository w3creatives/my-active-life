<?php

namespace Database\Seeders;

use App\Models\Modality;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ModalitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Modality::truncate();

        Modality::insert([
            ['id' => 1, 'name' => 'daily_steps', 'value' =>  1],
            ['id' => 2, 'name' => 'run', 'value' =>  2],
            ['id' => 3, 'name' => 'walk', 'value' =>  4],
            ['id' => 4, 'name' => 'bike', 'value' =>  8],
            ['id' => 5, 'name' => 'swim','value' => 16],
            ['id' => 6, 'name' => 'other', 'value' => 32],
        ]);
    }
}

