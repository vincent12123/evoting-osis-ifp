<?php

// Pesan validasi Laravel dalam Bahasa Indonesia (NFR-03, NF-05).
// Pesan khusus per field (mis. nomor urut, foto) didefinisikan langsung di form.
return [
    'accepted' => ':Attribute harus disetujui.',
    'array' => ':Attribute harus berupa daftar.',
    'boolean' => ':Attribute harus bernilai ya atau tidak.',
    'between' => [
        'numeric' => ':Attribute harus antara :min dan :max.',
        'file' => ':Attribute harus antara :min dan :max kilobita.',
        'string' => ':Attribute harus antara :min dan :max karakter.',
        'array' => ':Attribute harus berisi antara :min dan :max butir.',
    ],
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'date' => ':Attribute bukan tanggal yang valid.',
    'distinct' => ':Attribute memiliki nilai ganda.',
    'email' => ':Attribute harus berupa alamat email yang valid.',
    'exists' => ':Attribute yang dipilih tidak ditemukan.',
    'file' => ':Attribute harus berupa berkas.',
    'filled' => ':Attribute wajib diisi.',
    'image' => ':Attribute harus berupa gambar.',
    'in' => ':Attribute yang dipilih tidak valid.',
    'integer' => ':Attribute harus berupa bilangan bulat.',
    'max' => [
        'numeric' => ':Attribute maksimal :max.',
        'file' => ':Attribute maksimal :max kilobita.',
        'string' => ':Attribute maksimal :max karakter.',
        'array' => ':Attribute maksimal berisi :max butir.',
    ],
    'mimes' => ':Attribute harus berupa berkas berjenis: :values.',
    'mimetypes' => ':Attribute harus berupa berkas berjenis: :values.',
    'min' => [
        'numeric' => ':Attribute minimal :min.',
        'file' => ':Attribute minimal :min kilobita.',
        'string' => ':Attribute minimal :min karakter.',
        'array' => ':Attribute minimal berisi :min butir.',
    ],
    'numeric' => ':Attribute harus berupa angka.',
    'regex' => 'Format :attribute tidak valid.',
    'required' => ':Attribute wajib diisi.',
    'required_if' => ':Attribute wajib diisi bila :other bernilai :value.',
    'same' => ':Attribute dan :other harus sama.',
    'size' => [
        'numeric' => ':Attribute harus :size.',
        'file' => ':Attribute harus berukuran :size kilobita.',
        'string' => ':Attribute harus :size karakter.',
        'array' => ':Attribute harus berisi :size butir.',
    ],
    'string' => ':Attribute harus berupa teks.',
    'unique' => ':Attribute sudah dipakai.',
    'uploaded' => ':Attribute gagal diunggah.',
    'uuid' => ':Attribute harus berupa UUID yang valid.',

    'custom' => [],
    'attributes' => [],
];
