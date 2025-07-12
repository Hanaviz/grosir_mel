<?php
include 'koneksi.php';

// Fungsi untuk membuat ID unik dengan prefiks
function generate_unique_id($koneksi, $prefix, $table, $column) {
    $prefix_len = strlen($prefix) + 1; // Panjang prefiks + underscore
    // KODE YANG BENAR
$query = "SELECT $column FROM $table WHERE $column LIKE '$prefix\_%' ORDER BY CAST(SUBSTRING($column, $prefix_len + 1) AS UNSIGNED) DESC LIMIT 1";
    $result = $koneksi->query($query);
    if ($result->num_rows > 0) {
        $last_id = $result->fetch_assoc()[$column];
        // --- PERBAIKAN ADA DI BARIS INI ---
        $last_num = (int)substr($last_id, $prefix_len);
        $new_num = $last_num + 1;
    } else {
        $new_num = 1;
    }
    return $prefix . '_' . str_pad($new_num, 3, '0', STR_PAD_LEFT);
}

 
$tanggal = date('Y-m-d');
// Buat ID Transaksi baru terlebih dahulu
$id_transaksi_baru = generate_unique_id($koneksi, 'TRX', 'Transaction_Header', 'id_transaksi');

// Cek apakah ini transaksi dari DISTRIBUTOR (Masuk)
if (isset($_GET['id_distributor']) && !empty($_GET['id_distributor'])) {
    $id_distributor = $koneksi->real_escape_string($_GET['id_distributor']);
    $tipe = 'Masuk';
    $keterangan = 'Pemasukan barang dari distributor';
    
    $stmt = $koneksi->prepare(
        "INSERT INTO Transaction_Header (id_transaksi, tanggal, id_distributor, tipe_transaksi, keterangan) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("sssss", $id_transaksi_baru, $tanggal, $id_distributor, $tipe, $keterangan);

// Cek apakah ini transaksi dari PELANGGAN (Keluar)
} elseif (isset($_GET['id_pelanggan']) && !empty($_GET['id_pelanggan'])) {
    $id_pelanggan = $koneksi->real_escape_string($_GET['id_pelanggan']);
    $tipe = 'Keluar';
    $keterangan = 'Penjualan barang ke pelanggan';

    $stmt = $koneksi->prepare(
        "INSERT INTO Transaction_Header (id_transaksi, tanggal, id_pelanggan, tipe_transaksi, keterangan) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("sssss", $id_transaksi_baru, $tanggal, $id_pelanggan, $tipe, $keterangan);

} else {
    header("Location: home.php");
    exit();
}

if ($stmt === false) {
    die('Gagal mempersiapkan statement: ' . htmlspecialchars($koneksi->error));
}

// Baris 59 tempat error terjadi
if ($stmt->execute()) {
    header("Location: d_transaksi.php?id=" . $id_transaksi_baru);
    exit();
} else {
    echo "Error: Gagal membuat transaksi baru. " . htmlspecialchars($stmt->error);
    
}

$stmt->close();
$koneksi->close();
?>