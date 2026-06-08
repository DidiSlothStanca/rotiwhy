<?php
// Konfigurasi session agar hanya bertahan selama browser terbuka (session cookie)
// Tidak menyimpan ke disk/persistent storage
if (session_status() === PHP_SESSION_NONE) {
    // Set session cookie lifetime 0 = hanya bertahan saat browser aktif
    // Saat browser ditutup, session akan hilang
    session_set_cookie_params([
        'lifetime' => 0,           // 0 = session cookie (tidak persistent)
        'path' => '/',
        'domain' => '',
        'secure' => false,         // Set true jika menggunakan HTTPS
        'httponly' => true,        // Proteksi dari JavaScript
        'samesite' => 'Lax'        // Proteksi CSRF
    ]);
    session_start();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$host = "localhost";
$user = "root"; 
$pass = "";     
$db   = "toko_roti";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}
?>
