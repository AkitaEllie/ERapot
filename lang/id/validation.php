<?php

/*
 | The default messages for the rules this application uses. The framework ships
 | English only, so without these any rule lacking a custom message on its screen
 | surfaces in English under the "id" locale.
 */
return [
    'after' => ':attribute harus setelah :date.',
    'array' => ':attribute harus berupa daftar.',
    'between' => [
        'numeric' => ':attribute harus antara :min dan :max.',
        'string' => ':attribute harus antara :min dan :max karakter.',
    ],
    'boolean' => ':attribute harus bernilai benar atau salah.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'date' => ':attribute harus berupa tanggal yang valid.',
    'digits' => ':attribute harus terdiri dari :digits digit angka.',
    'email' => ':attribute harus berupa alamat email yang valid.',
    'enum' => ':attribute yang dipilih tidak valid.',
    'exists' => ':attribute yang dipilih tidak ditemukan.',
    'file' => ':attribute harus berupa berkas.',
    'image' => ':attribute harus berupa gambar.',
    'in' => ':attribute yang dipilih tidak valid.',
    'integer' => ':attribute harus berupa bilangan bulat.',
    'max' => [
        'array' => ':attribute tidak boleh lebih dari :max item.',
        'file' => ':attribute tidak boleh lebih dari :max KB.',
        'numeric' => ':attribute tidak boleh lebih dari :max.',
        'string' => ':attribute tidak boleh lebih dari :max karakter.',
    ],
    'mimes' => ':attribute harus berupa berkas: :values.',
    'min' => [
        'array' => ':attribute minimal berisi :min item.',
        'file' => ':attribute minimal :min KB.',
        'numeric' => ':attribute minimal :min.',
        'string' => ':attribute minimal :min karakter.',
    ],
    'numeric' => ':attribute harus berupa angka.',
    'required' => ':attribute wajib diisi.',
    'required_if' => ':attribute wajib diisi.',
    'required_without' => ':attribute wajib diisi.',
    'string' => ':attribute harus berupa teks.',
    'unique' => ':attribute sudah digunakan.',
    'uploaded' => ':attribute gagal diunggah.',
];
