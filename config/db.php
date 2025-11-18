<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\FirebaseException;

$serviceAccountPath = __DIR__ . '/firebase_credentials.json';

// 🔹 Cek apakah file kredensial tersedia
if (!file_exists($serviceAccountPath)) {
    http_response_code(500);
    die("Error: File kredensial Firebase tidak ditemukan di " . $serviceAccountPath);
}

try {
    // Buat instance utama Firebase (Factory)
    $firebase = (new Factory)
        ->withServiceAccount($serviceAccountPath)
        ->withDatabaseUri('https://dlhk-arsip-default-rtdb.asia-southeast1.firebasedatabase.app');

    //  Buat instance Database (Realtime Database)
    $database = $firebase->createDatabase();

    // Tidak pakai Storage/Bucket
    $storage = null;
    $bucket  = null;

} catch (\Throwable $e) {
    http_response_code(500);
    error_log('Inisialisasi Firebase Gagal: ' . $e->getMessage());
    die('Sistem gagal memuat konfigurasi Firebase.');
}

// 🔹 Inisialisasi Auth (opsional)
$auth = null;
try {
    $auth = $firebase->createAuth();
} catch (\Throwable $e) {
    error_log('Auth Gagal: ' . $e->getMessage());
}
