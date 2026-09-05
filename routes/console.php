<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('admin:unlock {email}', function (string $email) {
    $user = User::withTrashed()->where('email', $email)->first();

    if (! $user) {
        $this->error('Akun admin tidak ditemukan.');

        return 1;
    }

    $user->unlockLogin();
    $this->info('Kunci login '.$user->email.' berhasil dibuka.');

    return 0;
})->purpose('Open a globally locked admin account');
