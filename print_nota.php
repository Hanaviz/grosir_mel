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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(to right, #38496E, #4A608F);
            --primary-gradient-hover: linear-gradient(to right, #2D3A55, #38496E);
            --primary-color: #38496E;
            --primary-color-light: #4A608F;
            --primary-color-dark: #2D3A55;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', 'Poppins', sans-serif;
            background: var(--primary-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-size: 12px;
            color: var(--primary-color);
        }
        
        .nota-wrapper {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            overflow: hidden;
            position: relative;
        }
        
        .nota-wrapper::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }
        
        .nota-container {
            width: 85mm;
            margin: 0 auto;
            padding: 25px;
            background: white;
            position: relative;
        }
        
        .header-nota {
            text-align: center;
            margin-bottom: 25px;
            position: relative;
        }
        
        .logo-circle {
            width: 60px;
            height: 60px;
            background: var(--primary-gradient);
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: 700;
            box-shadow: 0 8px 16px rgba(56, 73, 110, 0.3);
        }
        
        .header-nota h2 {
            margin: 0 0 8px 0;
            font-size: 24px;
            font-weight: 700;
            color: #2d3748;
            letter-spacing: -0.5px;
        }
        
        .header-nota .subtitle {
            font-size: 11px;
            color: #718096;
            margin-bottom: 12px;
            font-weight: 500;
        }
        
        .contact-info {
            background: #f8fafc;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
        }
        
        .contact-info p {
            margin: 3px 0;
            font-size: 10px;
            color: #4a5568;
        }
        
        .divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
            margin: 20px 0;
            border-radius: 1px;
        }
        
        .info-transaksi {
            background: #f7fafc;
            padding: 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            position: relative;
        }
        
        .info-transaksi::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-gradient);
            border-radius: 0 2px 2px 0;
        }
        
        .transaction-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .badge-masuk {
            background: #c6f6d5;
            color: #22543d;
        }
        
        .badge-keluar {
            background: #fed7e2;
            color: #702459;
        }
        
        .info-transaksi p {
            margin: 6px 0;
            font-size: 11px;
            color: #4a5568;
            line-height: 1.5;
        }
        
        .info-transaksi strong {
            color: #2d3748;
            font-weight: 600;
        }
        
        .detail-barang {
            margin-bottom: 20px;
        }
        
        .table-header {
            background: var(--primary-gradient);
            color: white;
            padding: 12px 0;
            border-radius: 8px 8px 0 0;
            font-weight: 600;
        }
        
        .detail-barang table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .detail-barang th {
            background: var(--primary-gradient);
            color: white;
            padding: 12px 8px;
            font-weight: 600;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .detail-barang td {
            padding: 12px 8px;
            font-size: 10px;
            border-bottom: 1px solid #e2e8f0;
            background: white;
            transition: background-color 0.2s ease;
        }
        
        .detail-barang tr:nth-child(even) td {
            background: #f8fafc;
        }
        
        .detail-barang tr:hover td {
            background: #edf2f7;
        }
        
        .detail-barang .text-right {
            text-align: right;
            font-weight: 500;
        }
        
        .item-name {
            font-weight: 600;
            color: #2d3748;
        }
        
        .price-text {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .total-section {
            background: var(--primary-gradient);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            position: relative;
            margin-bottom: 20px;
            box-shadow: 0 8px 16px rgba(56, 73, 110, 0.3);
        }
        
        .total-section::before {
            content: '';
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 20px;
            height: 20px;
            background: var(--primary-gradient);
            border-radius: 50%;
        }
        
        .total-label {
            font-size: 12px;
            opacity: 0.9;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .total-amount {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        
        .footer-nota {
            text-align: center;
            padding: 20px;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        
        .thank-you {
            font-size: 14px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 12px;
        }
        
        .service-info {
            font-size: 10px;
            color: #718096;
            line-height: 1.6;
        }
        
        .service-hours {
            background: white;
            padding: 8px 12px;
            border-radius: 8px;
            margin-top: 8px;
            border: 1px solid #e2e8f0;
            font-weight: 500;
        }
        
        .decorative-dots {
            text-align: center;
            margin: 15px 0;
            color: #cbd5e0;
            font-size: 16px;
            letter-spacing: 8px;
        }
        
        /* Print styles */
        @media print {
            body {
                background: white;
                margin: 0;
                padding: 0;
                display: block;
            }
            
            .nota-wrapper {
                box-shadow: none;
                border-radius: 0;
                background: white;
            }
            
            .nota-wrapper::before {
                display: none;
            }
            
            .nota-container {
                width: 80mm;
                padding: 15px;
                margin: 0;
            }
            
            .logo-circle {
                box-shadow: none;
            }
            
            .total-section {
                box-shadow: none;
            }
            
            .detail-barang table {
                box-shadow: none;
            }
            
            .detail-barang tr:hover td {
                background: inherit;
            }
        }
        
        /* Animations for screen view */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .nota-container > * {
            animation: fadeInUp 0.6s ease forwards;
        }
        
        .nota-container > *:nth-child(2) { animation-delay: 0.1s; }
        .nota-container > *:nth-child(3) { animation-delay: 0.2s; }
        .nota-container > *:nth-child(4) { animation-delay: 0.3s; }
        .nota-container > *:nth-child(5) { animation-delay: 0.4s; }
        .nota-container > *:nth-child(6) { animation-delay: 0.5s; }
        
        @media print {
            .nota-container > * {
                animation: none;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="nota-wrapper">
        <div class="nota-container">
            <div class="header-nota">
                <div class="logo-circle">GM</div>
                <h2>Grosir Mel</h2>
                <p class="subtitle">Solusi Lengkap Kebutuhan Grosir Anda</p>
                
                <div class="contact-info">
                    <p><strong>📍</strong> Jl. Contoh No. 123, Kota Padang</p>
                    <p><strong>📞</strong> 0812-3456-7890</p>
                    <p><strong>✉️</strong> info@grosirmel.com</p>
                </div>
            </div>

            <div class="info-transaksi">
                <div class="transaction-badge <?= $header_data['tipe_transaksi'] == 'Masuk' ? 'badge-masuk' : 'badge-keluar'; ?>">
                    <?= $header_data['tipe_transaksi'] == 'Masuk' ? '📦 Barang Masuk' : '🛍️ Penjualan'; ?>
                </div>
                
                <p><strong>Nota Transaksi:</strong> #<?= htmlspecialchars($header_data['id_transaksi']); ?></p>
                <p><strong>Tanggal:</strong> <?= htmlspecialchars(date('d F Y - H:i', strtotime($header_data['tanggal']))); ?> WIB</p>
                
                <?php if ($header_data['tipe_transaksi'] == 'Keluar' && $header_data['nama_pelanggan']): ?>
                    <div class="divider"></div>
                    <p><strong>👤 Pelanggan:</strong> <?= htmlspecialchars($header_data['nama_pelanggan']); ?></p>
                    <p><strong>📍 Alamat:</strong> <?= htmlspecialchars($header_data['alamat_pelanggan'] ?? 'Tidak tercatat'); ?></p>
                    <p><strong>📱 No. HP:</strong> <?= htmlspecialchars($header_data['nohp_pelanggan'] ?? 'Tidak tercatat'); ?></p>
                <?php elseif ($header_data['tipe_transaksi'] == 'Masuk' && $header_data['nama_distributor']): ?>
                    <div class="divider"></div>
                    <p><strong>🏢 Distributor:</strong> <?= htmlspecialchars($header_data['nama_distributor']); ?></p>
                    <p><strong>📍 Alamat:</strong> <?= htmlspecialchars($header_data['alamat_distributor'] ?? 'Tidak tercatat'); ?></p>
                    <p><strong>☎️ Telp:</strong> <?= htmlspecialchars($header_data['telepon_distributor'] ?? 'Tidak tercatat'); ?></p>
                <?php endif; ?>
            </div>

            <div class="detail-barang">
                <table>
                    <thead>
                        <tr>
                            <th>Nama Barang</th>
                            <th style="text-align: right;">Qty</th>
                            <th style="text-align: right;">Harga</th>
                            <th style="text-align: right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_detail && $result_detail->num_rows > 0): ?>
                            <?php while ($row = $result_detail->fetch_assoc()):
                                $subtotal = $row['jumlah'] * $row['harga_saat_transaksi'];
                                $total_keseluruhan += $subtotal;
                            ?>
                            <tr>
                                <td class="item-name"><?= htmlspecialchars($row['nama_barang']); ?></td>
                                <td class="text-right"><?= htmlspecialchars($row['jumlah']) . ' ' . htmlspecialchars($row['satuan']); ?></td>
                                <td class="text-right price-text">Rp <?= number_format($row['harga_saat_transaksi'], 0, ',', '.'); ?></td>
                                <td class="text-right price-text">Rp <?= number_format($subtotal, 0, ',', '.'); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center; color: #718096; font-style: italic;">Tidak ada item dalam transaksi ini</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="total-section">
                <div class="total-label">Total Keseluruhan</div>
                <div class="total-amount">Rp <?= number_format($total_keseluruhan, 0, ',', '.'); ?></div>
            </div>

            <div class="decorative-dots">• • • • •</div>

            <div class="footer-nota">
                <div class="thank-you">🙏 Terima kasih atas kepercayaan Anda!</div>
                <div class="service-info">
                    <p>Untuk pertanyaan dan keluhan, hubungi:</p>
                    <div class="service-hours">
                        <strong>📞 Customer Service</strong><br>
                        Senin - Jumat: 08:00 - 17:00 WIB<br>
                        Sabtu: 08:00 - 12:00 WIB
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>