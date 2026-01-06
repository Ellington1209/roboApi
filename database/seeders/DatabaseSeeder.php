<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Tom',
            'email' => 'tom@example.com',
            'phone' => '62991720735',
            'password' => Hash::make('password'),
            'is_super_admin' => true,
        ]);

        User::create([
            'name' => 'Gil alves',
            'email' => 'gil.alves@example.com',
            'phone' => '62999035898',
            'password' => Hash::make('password'),
            'is_super_admin' => true,
        ]);

        User::create([
            'name' => 'Abimael pessoa',
            'email' => 'aboimael.pessoa@example.com',
            'phone' => '62984248395',
            'password' => Hash::make('password'),
            'is_super_admin' => true,
        ]);

        User::create([
            'name' => 'Silas',
            'email' => 'silas@example.com',
            'phone' => '62985076727',
            'password' => Hash::make('password'),
            'is_super_admin' => true,
        ]);

        User::create([
            'name' => 'Eliel',
            'email' => 'eliel@example.com',
            'phone' => '62986249350',
            'password' => Hash::make('password'),
            'is_super_admin' => true,
        ]);
    }
}

