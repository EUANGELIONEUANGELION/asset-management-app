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
        // Membuat akun Admin / Supervisor untuk uji coba login
        User::create([
            'nama' => 'User Supervisor',
            'email' => 'supervisor@test.com',
            'password' => Hash::make('password123'), // Password otomatis di-enkripsi aman
            'no_telepon' => '081234567890',
            'role' => 'supervisor', // Sesuaikan dengan enum pilihan Anda ('officer'/'tim'/'supervisor')
        ]);

        // Membuat akun Officer tambahan jika diperlukan
        User::create([
            'nama' => 'User Officer',
            'email' => 'officer@test.com',
            'password' => Hash::make('password123'),
            'no_telepon' => '089876543210',
            'role' => 'officer',
        ]);

          User::create([
            'nama' => 'User Tim',
            'email' => 'tim@test.com',
            'password' => Hash::make('password123'),
            'no_telepon' => '089876543210',
            'role' => 'tim',
        ]);
    }
}
