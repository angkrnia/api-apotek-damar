<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // CREATE DEFAULT USER
        \App\Models\User::insert([
            [
                'fullname' => 'dr. Friska Yeni Sinamo',
                'phone' => '6282210767570',
                'password' => bcrypt('123456'),
                'role' => ADMIN,
                'created_at' => now(),
            ],
            [
                'fullname' => 'dr. Friska Yeni Sinamo',
                'phone' => '6282216026507',
                'password' => bcrypt('123456'),
                'role' => ADMIN,
                'created_at' => now(),
            ],
            [
                'fullname' => 'Angga Kurnia',
                'phone' => '6282210157618',
                'password' => bcrypt('123456'),
                'role' => ADMIN,
                'created_at' => now(),
            ],
            [
                'fullname' => 'Apt. Nadya Putri, S.Farm',
                'phone' => '6282210157601',
                'password' => bcrypt('123456'),
                'role' => PHARMACIST,
                'created_at' => now(),
            ],
        ]);
    }
}
