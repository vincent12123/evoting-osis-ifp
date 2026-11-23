<?php

// Checkpoint 1 — SPEC REQ-01, REQ-23 · acceptance-criteria.md bagian A.

use App\Models\ActivityLog;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Filament\Auth\Pages\Login;
use Livewire\Livewire;

beforeEach(function () {
    config([
        'evoting.admin.name' => 'Admin Panitia',
        'evoting.admin.email' => 'admin@evoting.test',
        'evoting.admin.password' => 'kata-sandi-uji-123',
    ]);
});

function attemptLogin(string $email, string $password)
{
    return Livewire::test(Login::class)
        ->fillForm(['email' => $email, 'password' => $password])
        ->call('authenticate');
}

it('AC-01 seeder membuat akun admin dari .env yang bisa login ke /admin', function () {
    $this->seed(DatabaseSeeder::class);

    $admin = User::where('email', 'admin@evoting.test')->first();
    expect($admin)->not->toBeNull()
        ->and($admin->name)->toBe('Admin Panitia');

    attemptLogin('admin@evoting.test', 'kata-sandi-uji-123')
        ->assertHasNoFormErrors()
        ->assertRedirect('/admin');

    $this->assertAuthenticatedAs($admin);
    $this->get('/admin')->assertOk();
});

it('AC-01 seeder aman dijalankan ulang dan tidak mengubah kata sandi yang sudah ada', function () {
    $this->seed(DatabaseSeeder::class);
    config(['evoting.admin.password' => 'kata-sandi-lain-456']);
    $this->seed(DatabaseSeeder::class);

    expect(User::count())->toBe(1);
    attemptLogin('admin@evoting.test', 'kata-sandi-uji-123')->assertHasNoFormErrors();
});

it('AC-01 seeder menolak jika ADMIN_PASSWORD kosong', function () {
    config(['evoting.admin.password' => null]);
    $this->seed(DatabaseSeeder::class);
})->throws(RuntimeException::class, 'ADMIN_EMAIL dan ADMIN_PASSWORD wajib diisi');

it('AC-01 seeder menolak kata sandi kurang dari 10 karakter', function () {
    config(['evoting.admin.password' => 'pendek']);
    $this->seed(DatabaseSeeder::class);
})->throws(RuntimeException::class, 'minimal 10 karakter');

it('AC-02 pengunjung yang belum login dialihkan ke halaman login', function (string $url) {
    $this->get($url)->assertRedirect('/admin/login');
})->with([
    '/admin',
    '/admin/candidates',
    '/admin/elections',
    '/admin/activity-logs',
    // '/admin/recap' ditambahkan saat halamannya dibuat (Checkpoint 5).
]);

it('AC-02 halaman login tampil dalam Bahasa Indonesia', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('Masuk ke akun Anda')
        ->assertSee('Kata sandi');
});

it('AC-02 kata sandi salah ditolak dengan pesan dari kartu SCR-A1', function () {
    $this->seed(DatabaseSeeder::class);

    attemptLogin('admin@evoting.test', 'salah-sama-sekali')
        ->assertHasFormErrors(['email' => 'Email atau kata sandi salah.']);

    $this->assertGuest();
});

it('AC-03 percobaan ke-6 dalam 1 menit ditolak meski kata sandi benar', function () {
    $this->seed(DatabaseSeeder::class);

    foreach (range(1, 5) as $_) {
        attemptLogin('admin@evoting.test', 'salah-sama-sekali');
    }

    attemptLogin('admin@evoting.test', 'kata-sandi-uji-123')
        ->assertNotified('Terlalu banyak percobaan masuk.');

    $this->assertGuest();
});

it('AC-04 login berhasil tercatat di log aktivitas', function () {
    $this->seed(DatabaseSeeder::class);
    $admin = User::firstOrFail();

    attemptLogin('admin@evoting.test', 'kata-sandi-uji-123');

    $log = ActivityLog::where('action', 'auth.login')->sole();
    expect($log->user_id)->toBe($admin->id)
        ->and($log->created_at)->not->toBeNull();
});

it('AC-04 login gagal tidak tercatat sebagai login', function () {
    $this->seed(DatabaseSeeder::class);

    attemptLogin('admin@evoting.test', 'salah-sama-sekali');

    expect(ActivityLog::where('action', 'auth.login')->count())->toBe(0);
});
