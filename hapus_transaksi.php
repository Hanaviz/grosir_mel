<?php
// hapus_transaksi.php

include 'koneksi.php';

// 1. Validasi apakah ID transaksi ada
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: transaksi.php?status=gagal_hapus");
    exit();
}

$id_transaksi = $_GET['id'];

// Mulai transaksi database untuk memastikan semua proses berhasil atau tidak sama sekali
$koneksi->begin_transaction();

try {
    // 2. Ambil semua item detail dan tipe transaksi sebelum dihapus
    $stmt_get_details = $koneksi->prepare(
        "SELECT td.id_barang, td.jumlah, th.tipe_transaksi
         FROM Transaction_Detail td
         JOIN Transaction_Header th ON td.id_transaksi = th.id_transaksi
         WHERE td.id_transaksi = ?"
    );
    $stmt_get_details->bind_param("s", $id_transaksi);
    $stmt_get_details->execute();
    $result_details = $stmt_get_details->get_result();

    // 3. Kembalikan stok untuk setiap barang
    while ($detail = $result_details->fetch_assoc()) {
        $id_barang = $detail['id_barang'];
        $jumlah = $detail['jumlah'];
        $tipe_transaksi = $detail['tipe_transaksi'];

        if ($tipe_transaksi == 'Masuk') {
            // Jika transaksi PEMASUKAN dibatalkan, STOK DIKURANGI
            $stmt_update_stok = $koneksi->prepare("UPDATE Barang SET stok = stok - ? WHERE id_barang = ?");
        } else { // 'Keluar'
            // Jika transaksi PENJUALAN dibatalkan, STOK DIKEMBALIKAN
            $stmt_update_stok = $koneksi->prepare("UPDATE Barang SET stok = stok + ? WHERE id_barang = ?");
        }

        if ($stmt_update_stok) {
            $stmt_update_stok->bind_param("is", $jumlah, $id_barang);
            $stmt_update_stok->execute();
            $stmt_update_stok->close();
        } else {
            throw new Exception("Gagal mempersiapkan statement update stok.");
        }
    }
    $stmt_get_details->close();

    // 4. Hapus data dari Transaction_Detail
    $stmt_delete_detail = $koneksi->prepare("DELETE FROM Transaction_Detail WHERE id_transaksi = ?");
    $stmt_delete_detail->bind_param("s", $id_transaksi);
    $stmt_delete_detail->execute();
    $stmt_delete_detail->close();

    // 5. Hapus data dari Transaction_Header
    $stmt_delete_header = $koneksi->prepare("DELETE FROM Transaction_Header WHERE id_transaksi = ?");
    $stmt_delete_header->bind_param("s", $id_transaksi);
    $stmt_delete_header->execute();
    $stmt_delete_header->close();

    // Jika semua query berhasil, commit transaksi
    $koneksi->commit();
    header("Location: transaksi.php?status=hapus_sukses");

} catch (Exception $e) {
    // Jika ada satu saja query yang gagal, batalkan semua perubahan
    $koneksi->rollback();
    // Tampilkan pesan error atau redirect ke halaman error
    // die("Error: Gagal menghapus transaksi. " . $e->getMessage());
    header("Location: transaksi.php?status=gagal_hapus");
}

$koneksi->close();
exit();
?>