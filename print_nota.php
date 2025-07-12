<?php
include 'koneksi.php';

// Pastikan ID transaksi disediakan
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID Transaksi tidak ditemukan.");
}

$id_transaksi = $koneksi->real_escape_string($_GET['id']);

// Ambil detail header transaksi
$sql_header = "SELECT th.id_transaksi, th.tanggal, th.tipe_transaksi, 
                      p.nama_pelanggan, p.alamat_pelanggan, p.nohp_pelanggan,
                      d.nama_distributor, d.alamat_distributor, d.telepon_distributor
               FROM Transaction_Header th
               LEFT JOIN Pelanggan p ON th.id_pelanggan = p.id_pelanggan
               LEFT JOIN Distributor d ON th.id_distributor = d.id_distributor
               WHERE th.id_transaksi = '$id_transaksi'";
$header_result = $koneksi->query($sql_header);
if($header_result->num_rows == 0) {
    die("Transaksi tidak ditemukan.");
}
$header_data = $header_result->fetch_assoc();

// Ambil item detail transaksi
$sql_detail = "SELECT td.id_detail, b.nama_barang, b.satuan, td.jumlah, td.harga_saat_transaksi
               FROM Transaction_Detail td
               JOIN Barang b ON td.id_barang = b.id_barang
               WHERE td.id_transaksi = '$id_transaksi'";
$result_detail = $koneksi->query($sql_detail);

$total_keseluruhan = 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Transaksi #<?= htmlspecialchars($header_data['id_transaksi']); ?></title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            color: #333;
        }
        .nota-container {
            width: 80mm; /* Lebar nota umum */
            margin: 0 auto;
            /* Border dan shadow dihilangkan untuk cetak */
            /* border: 1px solid #ccc; */
            padding: 15px;
            /* box-shadow: 0 0 5px rgba(0,0,0,0.1); */
        }
        .header-nota {
            text-align: center;
            margin-bottom: 15px;
        }
        .header-nota h2 {
            margin: 0;
            font-size: 18px;
            color: #222;
        }
        .header-nota p {
            margin: 2px 0;
            font-size: 10px;
        }
        .info-transaksi, .detail-barang {
            margin-bottom: 15px;
        }
        .info-transaksi p {
            margin: 2px 0;
        }
        .detail-barang table {
            width: 100%;
            border-collapse: collapse;
        }
        .detail-barang th, .detail-barang td {
            border-bottom: 1px dashed #eee; /* Garis putus-putus untuk pemisah item */
            padding: 5px 0;
            text-align: left;
        }
        .detail-barang th {
            font-weight: bold;
            font-size: 10px;
        }
        .detail-barang td {
            font-size: 10px;
        }
        .detail-barang .text-right {
            text-align: right;
        }
        .total-section {
            border-top: 1px dashed #ccc;
            padding-top: 10px;
            text-align: right;
        }
        .total-section p {
            margin: 2px 0;
            font-size: 12px;
            font-weight: bold;
        }
        .footer-nota {
            text-align: center;
            margin-top: 20px;
            font-size: 10px;
        }
        /* Media query untuk cetak */
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .nota-container {
                width: 80mm; /* Pastikan lebar tetap untuk cetak */
                border: none;
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="nota-container">
        <div class="header-nota">
            <h2>Grosir Mel</h2>
            <p>Jl. Contoh No. 123, Kota Padang</p>
            <p>Telp: 0812-3456-7890</p>
            <p>Email: info@grosirmel.com</p>
            <hr style="border: 1px solid #ddd; margin: 10px 0;">
        </div>

        <div class="info-transaksi">
            <p>Nota Transaksi: #<?= htmlspecialchars($header_data['id_transaksi']); ?></p>
            <p>Tanggal: <?= htmlspecialchars(date('d F Y', strtotime($header_data['tanggal']))); ?></p>
            <?php if ($header_data['tipe_transaksi'] == 'Keluar' && $header_data['nama_pelanggan']): ?>
                <p>Pelanggan: <?= htmlspecialchars($header_data['nama_pelanggan']); ?></p>
                <p>Alamat: <?= htmlspecialchars($header_data['alamat_pelanggan'] ?? '-'); ?></p>
                <p>No. HP: <?= htmlspecialchars($header_data['nohp_pelanggan'] ?? '-'); ?></p>
            <?php elseif ($header_data['tipe_transaksi'] == 'Masuk' && $header_data['nama_distributor']): ?>
                <!-- Jika ini nota untuk pemasukan, tampilkan info distributor -->
                <p>Distributor: <?= htmlspecialchars($header_data['nama_distributor']); ?></p>
                <p>Alamat: <?= htmlspecialchars($header_data['alamat_distributor'] ?? '-'); ?></p>
                <p>Telp: <?= htmlspecialchars($header_data['telepon_distributor'] ?? '-'); ?></p>
            <?php else: ?>
                <p>Pihak Terkait: N/A</p>
            <?php endif; ?>
            <hr style="border: 1px dashed #eee; margin: 10px 0;">
        </div>

        <div class="detail-barang">
            <table>
                <thead>
                    <tr>
                        <th>Barang</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Harga</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_detail && $result_detail->num_rows > 0): ?>
                        <?php while ($row = $result_detail->fetch_assoc()):
                            $subtotal = $row['jumlah'] * $row['harga_saat_transaksi'];
                            $total_keseluruhan += $subtotal;
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nama_barang']); ?></td>
                            <td class="text-right"><?= htmlspecialchars($row['jumlah']) . ' ' . htmlspecialchars($row['satuan']); ?></td>
                            <td class="text-right">Rp <?= number_format($row['harga_saat_transaksi'], 0, ',', '.'); ?></td>
                            <td class="text-right">Rp <?= number_format($subtotal, 0, ',', '.'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center;">Tidak ada item dalam transaksi ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="total-section">
            <p>Total: Rp <?= number_format($total_keseluruhan, 0, ',', '.'); ?></p>
        </div>

        <div class="footer-nota">
            <p>Terima kasih atas transaksi Anda!</p>
            <p>--- Layanan Pelanggan ---</p>
            <p>Senin-Jumat, 08:00-17:00</p>
        </div>
    </div>
</body>
</html>
