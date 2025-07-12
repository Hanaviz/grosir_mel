<?php
// Pengaturan untuk koneksi database
$db_host = 'localhost';     // Nama host, biasanya 'localhost' untuk XAMPP
$db_user = 'root';          // Nama pengguna database, default 'root' untuk XAMPP
$db_pass = '';              // Password database, default kosong untuk XAMPP
$db_name = 'grosir_mel'; // GANTI DENGAN NAMA DATABASE ANDA

// Membuat koneksi
$koneksi = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Memeriksa koneksi
if (!$koneksi) {
    // Jika koneksi gagal, hentikan skrip dan tampilkan pesan error
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}
// Jika koneksi berhasil, skrip akan lanjut.
// Anda tidak perlu menulis pesan sukses di 
?>

