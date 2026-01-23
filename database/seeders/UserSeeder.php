<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::insert([
            'first_name' => 'Puneet',
            'last_name' => 'Gupta',
            'display_name' => 'Puneet Gupta',
            'email' => 'admin@shareacopy.com',
            'encrypted_password' => bcrypt('password'),
            'super_admin' => true,
        ]);
    }
}
