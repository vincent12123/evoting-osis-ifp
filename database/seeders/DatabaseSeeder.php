<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Membuat akun admin pertama dari .env (SPEC §8A.1).
     * Aman dijalankan berulang: akun yang sudah ada tidak diubah.
     */
    public function run(): void
    {
        $admin = config('evoting.admin');

        if (blank($admin['email']) || blank($admin['password'])) {
            throw new RuntimeException('ADMIN_EMAIL dan ADMIN_PASSWORD wajib diisi di .env sebelum menjalankan seeder.');
        }

        if (mb_strlen($admin['password']) < 10) {
            throw new RuntimeException('ADMIN_PASSWORD minimal 10 karakter (SPEC §4.1).');
        }

        User::firstOrCreate(
            ['email' => $admin['email']],
            ['name' => $admin['name'], 'password' => $admin['password']],
        );
    }
}
