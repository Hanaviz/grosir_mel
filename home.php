<?php
include 'koneksi.php'; // Pastikan path ini benar

// 1. Ambil data untuk kartu statistik
$query_barang = "SELECT COUNT(*) as total FROM Barang WHERE status = 'Aktif'";
$res_barang = $koneksi->query($query_barang);
$total_barang = $res_barang->fetch_assoc()['total'];

$query_pelanggan = "SELECT COUNT(*) as total FROM Pelanggan";
$res_pelanggan = $koneksi->query($query_pelanggan);
$total_pelanggan = $res_pelanggan->fetch_assoc()['total'];

$query_distributor = "SELECT COUNT(*) as total FROM Distributor";
$res_distributor = $koneksi->query($query_distributor);
$total_distributor = $res_distributor->fetch_assoc()['total'];

$query_transaksi = "SELECT COUNT(*) as total FROM Transaction_Header";
$res_transaksi = $koneksi->query($query_transaksi);
$total_transaksi = $res_transaksi->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Grosir Mel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="styledis.css" />

    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        .stat-card {
            background-color: var(--light-color);
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .stat-card .icon {
            font-size: 32px;
            padding: 15px;
            border-radius: 50%;
            color: var(--light-color);
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .stat-card .icon.barang { background-color: #f8b44e; } /* Kuning */
        .stat-card .icon.pelanggan { background-color: #50e3c2; } /* Hijau Toska */
        .stat-card .icon.distributor { background-color: #4a90e2; } /* Biru */
        .stat-card .icon.transaksi { background-color: #e35050; } /* Merah */

        .stat-card-info h3 {
            font-size: 28px;
            font-weight: 600;
            color: var(--dark-color);
            margin: 0;
        }
        .stat-card-info p {
            font-size: 14px;
            color: var(--text-color);
            margin: 0;
        }
        .content-header {
            margin-bottom: 20px;
            color: var(--dark-color);
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-actions button {
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
    <div class="sidebar">
    <div class="sidebar-header">
        <h1>Grosir Mel</h1>
    </div>
    <ul class="sidebar-menu">
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'home.php') ? 'active' : ''; ?>">
            <a href="home.php"><i class="fas fa-house"></i> <span>Home</span></a>
        </li>

        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'barang.php') ? 'active' : ''; ?>">
            <a href="barang.php"><i class="fas fa-boxes-stacked"></i> <span>Barang</span></a>
        </li>

        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'pelanggan.php') ? 'active' : ''; ?>">
            <a href="pelanggan.php"><i class="fas fa-user-friends"></i> <span>Pelanggan</span></a>
        </li>

        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'transaksi.php') ? 'active' : ''; ?>">
            <a href="transaksi.php"><i class="fas fa-file-invoice-dollar"></i> <span>Transaksi</span></a>
        </li>

        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'distributor.php') ? 'active' : ''; ?>">
            <a href="distributor.php"><i class="fas fa-truck"></i> <span>Distributor</span></a>
        </li>
    </ul>
</div>

        <div class="main-content">
            <header>
                <h2>Dashboard</h2>
            </header>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon barang"><i class="fas fa-boxes-stacked"></i></div>
                    <div class="stat-card-info">
                        <h3><?= $total_barang; ?></h3>
                        <p>Total Barang Aktif</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="icon pelanggan"><i class="fas fa-user-friends"></i></div>
                    <div class="stat-card-info">
                        <h3><?= $total_pelanggan; ?></h3>
                        <p>Total Pelanggan</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="icon distributor"><i class="fas fa-truck"></i></div>
                    <div class="stat-card-info">
                        <h3><?= $total_distributor; ?></h3>
                        <p>Total Distributor</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="icon transaksi"><i class="fas fa-file-invoice-dollar"></i></div>
                    <div class="stat-card-info">
                        <h3><?= $total_transaksi; ?></h3>
                        <p>Jumlah Transaksi</p>
                    </div>
                </div>
            </div>

            <div class="content-header">
                <h3>Transaksi Terbaru</h3>
                <div class="header-actions">
                    <button id="btnPemasukan" class="btn btn-success"><i class="fas fa-dolly"></i> Buat Pemasukan</button>
                    <button id="btnPenjualan" class="btn" style="background-color: #9b59b6; color: white;"><i class="fas fa-shopping-cart"></i> Buat Penjualan</button>
                </div>
            </div>
            <div class="content-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID Transaksi</th>
                            <th>Tanggal</th>
                            <th>Pelanggan</th>
                            <th>Distributor</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    // Query untuk mengambil 5 transaksi terakhir
                    $sql_latest = "SELECT th.id_transaksi, th.tanggal, p.nama_pelanggan, d.nama_distributor
                                   FROM Transaction_Header th
                                   LEFT JOIN Pelanggan p ON th.id_pelanggan = p.id_pelanggan
                                   LEFT JOIN Distributor d ON th.id_distributor = d.id_distributor
                                   ORDER BY CAST(SUBSTRING(th.id_transaksi, 5) AS UNSIGNED) DESC, th.tanggal DESC
                                   LIMIT 5";
                    $result_latest = $koneksi->query($sql_latest);
                    if ($result_latest && $result_latest->num_rows > 0):
                        while ($row = $result_latest->fetch_assoc()):
                    ?>
                        <tr>
                            <td>#<?= htmlspecialchars($row['id_transaksi']); ?></td>
                            <td><?= htmlspecialchars(date('d M Y', strtotime($row['tanggal']))); ?></td>
                            <td><?= htmlspecialchars($row['nama_pelanggan'] ?? 'N/A'); ?></td>
                            <td><?= htmlspecialchars($row['nama_distributor'] ?? 'N/A'); ?></td>
                            <td style="display: flex; gap: 5px;">
    <a href="d_transaksi.php?id=<?= $row['id_transaksi']; ?>">
        <button class="btn btn-primary" title="Lihat Detail"><i class="fas fa-eye"></i> Detail</button>
    </a>
    <a href="hapus_transaksi.php?id=<?= $row['id_transaksi']; ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus transaksi ini? Stok barang akan dikembalikan seperti semula.');">
        <button class="btn btn-danger" title="Hapus Transaksi"><i class="fas fa-trash-alt"></i> Hapus</button>
    </a>
</td>
                        </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <tr>
                           <td colspan="5" style="text-align: center; padding: 20px;">Belum ada data transaksi.</td>
                        </tr>
                    <?php
                    endif;
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="pemasukanModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Buat Transaksi Pemasukan</h2>
            <form id="pemasukanForm" action="buat_transaksi.php" method="GET">
                <label for="distributor_id">Pilih Distributor:</label>
                <select name="id_distributor" id="distributor_id" required>
                    <option value="">-- Pilih Distributor --</option>
                    <?php
                    $distributor_res = $koneksi->query("SELECT id_distributor, nama_distributor FROM Distributor ORDER BY nama_distributor");
                    while($dist = $distributor_res->fetch_assoc()) {
                        echo "<option value='".htmlspecialchars($dist['id_distributor'])."'>".htmlspecialchars($dist['nama_distributor'])."</option>";
                    }
                    ?>
                </select>
                <div class="modal-actions">
                    <button type="submit" class="btn btn-success">Lanjutkan</button>
                </div>
            </form>
        </div>
    </div>

    <div id="penjualanModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Buat Transaksi Penjualan</h2>
            <form id="penjualanForm" action="buat_transaksi.php" method="GET">
                <label for="pelanggan_id">Pilih Pelanggan:</label>
                <select name="id_pelanggan" id="pelanggan_id" required>
                    <option value="">-- Pilih Pelanggan --</option>
                    <?php
                    $pelanggan_res = $koneksi->query("SELECT id_pelanggan, nama_pelanggan FROM Pelanggan ORDER BY nama_pelanggan");
                    while($pel = $pelanggan_res->fetch_assoc()) {
                        echo "<option value='".htmlspecialchars($pel['id_pelanggan'])."'>".htmlspecialchars($pel['nama_pelanggan'])."</option>";
                    }
                    $koneksi->close();
                    ?>
                </select>
                <div class="modal-actions">
                    <button type="submit" class="btn" style="background-color: #9b59b6; color: white; width:100%;">Lanjutkan</button>
                </div>
            </form>
        </div>
    </div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Fungsi umum untuk mengelola modal
    function setupModal(buttonId, modalId) {
        const modal = document.getElementById(modalId);
        const button = document.getElementById(buttonId);
        const closeBtn = modal.querySelector(".close-button");

        button.addEventListener("click", () => {
            modal.style.display = "flex";
        });

        closeBtn.addEventListener("click", () => {
            modal.style.display = "none";
        });

        window.addEventListener("click", (event) => {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        });
    }

    // Terapkan fungsi ke setiap pasangan tombol dan modal
    setupModal('btnPemasukan', 'pemasukanModal');
    setupModal('btnPenjualan', 'penjualanModal');
});
</script>
</body>
</html>