<?php

return [

    /*
    | Akun admin pertama, dibuat oleh DatabaseSeeder (SPEC §8A.1).
    | Nilai hanya dari .env — jangan pernah menulis kredensial di kode.
    */
    'admin' => [
        'name' => env('ADMIN_NAME', 'Admin Panitia'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

];
