<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RegisterUserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Petugas Pintu Masuk',
            'username' => 'petugas',
            'password' => Hash::make('password'),
            'role' => 'register',
        ]);
    }
}
