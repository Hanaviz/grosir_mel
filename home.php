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

// --- LOGIKA UNTUK HISTORY PENJUALAN ---
$report_type = $_GET['report_type'] ?? 'daily'; // Default ke harian
$selected_date = $_GET['selected_date'] ?? date('Y-m-d');
$selected_month = $_GET['selected_month'] ?? date('Y-m');
$selected_year = $_GET['selected_year'] ?? date('Y');

$history_data = [];
$total_sales_period = 0;
$report_title = "Laporan Penjualan Harian";

$sql_history_base = "
    SELECT
";

// Adjust SELECT and GROUP BY based on report type for sales
if ($report_type == 'monthly_summary') {
    $sql_history_base .= "DATE_FORMAT(th.tanggal, '%Y-%m') as sales_period,
                          SUM(td.jumlah * td.harga_saat_transaksi) as total_sales";
    $group_by_clause = " GROUP BY sales_period";
} elseif ($report_type == 'yearly_summary') {
    $sql_history_base .= "YEAR(th.tanggal) as sales_period,
                          SUM(td.jumlah * td.harga_saat_transaksi) as total_sales";
    $group_by_clause = " GROUP BY sales_period";
} else {
    $sql_history_base .= "DATE_FORMAT(th.tanggal, '%Y-%m-%d') as sales_date,
                          SUM(td.jumlah * td.harga_saat_transaksi) as total_sales";
    $group_by_clause = " GROUP BY sales_date";
}


$sql_history_base .= "
    FROM
        Transaction_Header th
    JOIN
        Transaction_Detail td ON th.id_transaksi = td.id_transaksi
    WHERE
        th.tipe_transaksi = 'Keluar'
";

$where_clause = "";


switch ($report_type) {
    case 'daily':
        $where_clause = " AND th.tanggal = '$selected_date'";
        $report_title = "Laporan Penjualan Harian Tanggal: " . date('d F Y', strtotime($selected_date));
        break;
    case 'monthly':
        $where_clause = " AND DATE_FORMAT(th.tanggal, '%Y-%m') = '$selected_month'";
        $report_title = "Laporan Penjualan Harian Untuk Bulan: " . date('F Y', strtotime($selected_month . '-01'));
        break;
    case 'yearly':
        $where_clause = " AND YEAR(th.tanggal) = '$selected_year'";
        $report_title = "Laporan Penjualan Harian Untuk Tahun: " . $selected_year;
        break;
    case 'monthly_summary':
        $report_title = "Laporan Penjualan Bulanan (Ringkasan)";
        break;
    case 'yearly_summary':
        $report_title = "Laporan Penjualan Tahunan (Ringkasan)";
        break;
}

$sql_history = $sql_history_base . $where_clause . $group_by_clause;

// Add ORDER BY clause for the chart data
if ($report_type == 'monthly_summary' || $report_type == 'yearly_summary') {
    $sql_history .= " ORDER BY sales_period ASC";
} else {
    $sql_history .= " ORDER BY sales_date ASC"; // Order by date ascending for line chart
}

$result_history = $koneksi->query($sql_history);

if ($result_history && $result_history->num_rows > 0) {
    while ($row_history = $result_history->fetch_assoc()) {
        $history_data[] = $row_history;
        $total_sales_period += $row_history['total_sales'];
    }
}

// Prepare data for Chart.js (Sales)
$chartLabels = [];
$chartData = [];

foreach ($history_data as $data) {
    if ($report_type == 'monthly_summary') {
        $chartLabels[] = date('M Y', strtotime($data['sales_period'] . '-01'));
    } elseif ($report_type == 'yearly_summary') {
        $chartLabels[] = $data['sales_period'];
    } else {
        $chartLabels[] = date('d M Y', strtotime($data['sales_date']));
    }
    $chartData[] = $data['total_sales'];
}

$chartLabelsJson = json_encode($chartLabels);
$chartDataJson = json_encode($chartData);


// --- LOGIKA BARU UNTUK HISTORY PEMASUKAN ---
$inbound_report_type = $_GET['inbound_report_type'] ?? 'daily'; // Default ke harian
$inbound_selected_date = $_GET['inbound_selected_date'] ?? date('Y-m-d');
$inbound_selected_month = $_GET['inbound_selected_month'] ?? date('Y-m');
$inbound_selected_year = $_GET['inbound_selected_year'] ?? date('Y');

$inbound_history_data = [];
$total_inbound_period = 0;
$inbound_report_title = "Laporan Pemasukan Harian";

$sql_inbound_history_base = "
    SELECT
";

// Adjust SELECT and GROUP BY based on report type for inbound
if ($inbound_report_type == 'monthly_summary') {
    $sql_inbound_history_base .= "DATE_FORMAT(th.tanggal, '%Y-%m') as inbound_period,
                                  SUM(td.jumlah * td.harga_saat_transaksi) as total_inbound";
    $inbound_group_by_clause = " GROUP BY inbound_period";
} elseif ($inbound_report_type == 'yearly_summary') {
    $sql_inbound_history_base .= "YEAR(th.tanggal) as inbound_period,
                                  SUM(td.jumlah * td.harga_saat_transaksi) as total_inbound";
    $inbound_group_by_clause = " GROUP BY inbound_period";
} else {
    $sql_inbound_history_base .= "DATE_FORMAT(th.tanggal, '%Y-%m-%d') as inbound_date,
                                  SUM(td.jumlah * td.harga_saat_transaksi) as total_inbound";
    $inbound_group_by_clause = " GROUP BY inbound_date";
}

$sql_inbound_history_base .= "
    FROM
        Transaction_Header th
    JOIN
        Transaction_Detail td ON th.id_transaksi = td.id_transaksi
    WHERE
        th.tipe_transaksi = 'Masuk'
";

$inbound_where_clause = "";

switch ($inbound_report_type) {
    case 'daily':
        $inbound_where_clause = " AND th.tanggal = '$inbound_selected_date'";
        $inbound_report_title = "Laporan Pemasukan Harian Tanggal: " . date('d F Y', strtotime($inbound_selected_date));
        break;
    case 'monthly':
        $inbound_where_clause = " AND DATE_FORMAT(th.tanggal, '%Y-%m') = '$inbound_selected_month'";
        $inbound_report_title = "Laporan Pemasukan Harian Untuk Bulan: " . date('F Y', strtotime($inbound_selected_month . '-01'));
        break;
    case 'yearly':
        $inbound_where_clause = " AND YEAR(th.tanggal) = '$inbound_selected_year'";
        $inbound_report_title = "Laporan Pemasukan Harian Untuk Tahun: " . $inbound_selected_year;
        break;
    case 'monthly_summary':
        $inbound_report_title = "Laporan Pemasukan Bulanan (Ringkasan)";
        break;
    case 'yearly_summary':
        $inbound_report_title = "Laporan Pemasukan Tahunan (Ringkasan)";
        break;
}

$sql_inbound_history = $sql_inbound_history_base . $inbound_where_clause . $inbound_group_by_clause;

// Add ORDER BY clause for the chart data
if ($inbound_report_type == 'monthly_summary' || $inbound_report_type == 'yearly_summary') {
    $sql_inbound_history .= " ORDER BY inbound_period ASC";
} else {
    $sql_inbound_history .= " ORDER BY inbound_date ASC"; // Order by date ascending for line chart
}

$result_inbound_history = $koneksi->query($sql_inbound_history);

if ($result_inbound_history && $result_inbound_history->num_rows > 0) {
    while ($row_inbound_history = $result_inbound_history->fetch_assoc()) {
        $inbound_history_data[] = $row_inbound_history;
        $total_inbound_period += $row_inbound_history['total_inbound'];
    }
}

// Prepare data for Chart.js (Inbound)
$inboundChartLabels = [];
$inboundChartData = [];

foreach ($inbound_history_data as $data) {
    if ($inbound_report_type == 'monthly_summary') {
        $inboundChartLabels[] = date('M Y', strtotime($data['inbound_period'] . '-01'));
    } elseif ($inbound_report_type == 'yearly_summary') {
        $inboundChartLabels[] = $data['inbound_period'];
    } else {
        $inboundChartLabels[] = date('d M Y', strtotime($data['inbound_date']));
    }
    $inboundChartData[] = $data['total_inbound'];
}

$inboundChartLabelsJson = json_encode($inboundChartLabels);
$inboundChartDataJson = json_encode($inboundChartData);
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
        /* Style for History section */
        .history-section {
            background-color: var(--light-color);
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-top: 30px;
        }
        .history-section h3 {
            margin-bottom: 20px;
            color: var(--dark-color);
            font-weight: 600;
        }
        .filter-form {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filter-form label {
            font-weight: 500;
            color: var(--dark-color);
        }
        .filter-form input[type="radio"] {
            margin-right: 5px;
        }
        .filter-form input[type="date"],
        .filter-form input[type="month"],
        .filter-form input[type="number"],
        .filter-form button {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .filter-form button {
            background-color: var(--primary-color);
            color: var(--light-color);
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .filter-form button:hover {
            background-color: #3a80d2;
        }
        .history-summary {
            font-size: 18px;
            font-weight: 600;
            text-align: right;
            margin-top: 20px;
            color: var(--dark-color);
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

            <div class="history-section">
                <h3>Histori Penjualan</h3>
                <form class="filter-form" method="GET" action="home.php">
                    <label>
                        <input type="radio" name="report_type" value="daily" <?= ($report_type == 'daily') ? 'checked' : ''; ?> onchange="this.form.submit()"> Harian
                    </label>
                    <input type="date" name="selected_date" value="<?= htmlspecialchars($selected_date); ?>" onchange="this.form.submit()" <?= ($report_type == 'daily') ? '' : 'style="display:none;"'; ?>>

                    <label>
                        <input type="radio" name="report_type" value="monthly" <?= ($report_type == 'monthly') ? 'checked' : ''; ?> onchange="this.form.submit()"> Bulanan
                    </label>
                    <input type="month" name="selected_month" value="<?= htmlspecialchars($selected_month); ?>" onchange="this.form.submit()" <?= ($report_type == 'monthly') ? '' : 'style="display:none;"'; ?>>

                    <label>
                        <input type="radio" name="report_type" value="yearly" <?= ($report_type == 'yearly') ? 'checked' : ''; ?> onchange="this.form.submit()"> Tahunan
                    </label>
                    <input type="number" name="selected_year" value="<?= htmlspecialchars($selected_year); ?>" min="2000" max="<?= date('Y'); ?>" onchange="this.form.submit()" <?= ($report_type == 'yearly') ? '' : 'style="display:none;"'; ?>>

                    <label>
                        <input type="radio" name="report_type" value="monthly_summary" <?= ($report_type == 'monthly_summary') ? 'checked' : ''; ?> onchange="this.form.submit()"> Ringkasan Bulanan
                    </label>
                    <label>
                        <input type="radio" name="report_type" value="yearly_summary" <?= ($report_type == 'yearly_summary') ? 'checked' : ''; ?> onchange="this.form.submit()"> Ringkasan Tahunan
                    </label>
                </form>

                <div style="width: 80%; margin: 20px auto;">
                    <canvas id="salesHistoryChart"></canvas>
                </div>

                <h4 style="margin-top: 20px; margin-bottom: 15px; color: var(--dark-color);"><?= $report_title; ?></h4>
                <div class="content-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Periode</th>
                                <th>Total Penjualan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($history_data)): ?>
                                <?php foreach ($history_data as $data): ?>
                                    <tr>
                                        <td>
                                            <?php
                                                if ($report_type == 'monthly_summary') {
                                                    echo htmlspecialchars(date('F Y', strtotime($data['sales_period'] . '-01')));
                                                } elseif ($report_type == 'yearly_summary') {
                                                    echo htmlspecialchars($data['sales_period']);
                                                } else {
                                                    echo htmlspecialchars(date('d F Y', strtotime($data['sales_date'])));
                                                }
                                            ?>
                                        </td>
                                        <td>Rp <?= number_format($data['total_sales'], 0, ',', '.'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" style="text-align: center; font-style: italic;">Tidak ada data penjualan untuk periode ini.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th style="text-align: right;">Total Keseluruhan Periode:</th>
                                <th>Rp <?= number_format($total_sales_period, 0, ',', '.'); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="history-section" style="margin-top: 30px;">
                <h3>Histori Pemasukan</h3>
                <form class="filter-form" method="GET" action="home.php">
                    <label>
                        <input type="radio" name="inbound_report_type" value="daily" <?= ($inbound_report_type == 'daily') ? 'checked' : ''; ?> onchange="this.form.submit()"> Harian
                    </label>
                    <input type="date" name="inbound_selected_date" value="<?= htmlspecialchars($inbound_selected_date); ?>" onchange="this.form.submit()" <?= ($inbound_report_type == 'daily') ? '' : 'style="display:none;"'; ?>>

                    <label>
                        <input type="radio" name="inbound_report_type" value="monthly" <?= ($inbound_report_type == 'monthly') ? 'checked' : ''; ?> onchange="this.form.submit()"> Bulanan
                    </label>
                    <input type="month" name="inbound_selected_month" value="<?= htmlspecialchars($inbound_selected_month); ?>" onchange="this.form.submit()" <?= ($inbound_report_type == 'monthly') ? '' : 'style="display:none;"'; ?>>

                    <label>
                        <input type="radio" name="inbound_report_type" value="yearly" <?= ($inbound_report_type == 'yearly') ? 'checked' : ''; ?> onchange="this.form.submit()"> Tahunan
                    </label>
                    <input type="number" name="inbound_selected_year" value="<?= htmlspecialchars($inbound_selected_year); ?>" min="2000" max="<?= date('Y'); ?>" onchange="this.form.submit()" <?= ($inbound_report_type == 'yearly') ? '' : 'style="display:none;"'; ?>>

                    <label>
                        <input type="radio" name="inbound_report_type" value="monthly_summary" <?= ($inbound_report_type == 'monthly_summary') ? 'checked' : ''; ?> onchange="this.form.submit()"> Ringkasan Bulanan
                    </label>
                    <label>
                        <input type="radio" name="inbound_report_type" value="yearly_summary" <?= ($inbound_report_type == 'yearly_summary') ? 'checked' : ''; ?> onchange="this.form.submit()"> Ringkasan Tahunan
                    </label>
                </form>

                <div style="width: 80%; margin: 20px auto;">
                    <canvas id="inboundHistoryChart"></canvas>
                </div>

                <h4 style="margin-top: 20px; margin-bottom: 15px; color: var(--dark-color);"><?= $inbound_report_title; ?></h4>
                <div class="content-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Periode</th>
                                <th>Total Pemasukan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($inbound_history_data)): ?>
                                <?php foreach ($inbound_history_data as $data): ?>
                                    <tr>
                                        <td>
                                            <?php
                                                if ($inbound_report_type == 'monthly_summary') {
                                                    echo htmlspecialchars(date('F Y', strtotime($data['inbound_period'] . '-01')));
                                                } elseif ($inbound_report_type == 'yearly_summary') {
                                                    echo htmlspecialchars($data['inbound_period']);
                                                } else {
                                                    echo htmlspecialchars(date('d F Y', strtotime($data['inbound_date'])));
                                                }
                                            ?>
                                        </td>
                                        <td>Rp <?= number_format($data['total_inbound'], 0, ',', '.'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" style="text-align: center; font-style: italic;">Tidak ada data pemasukan untuk periode ini.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th style="text-align: right;">Total Keseluruhan Periode:</th>
                                <th>Rp <?= number_format($total_inbound_period, 0, ',', '.'); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
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
                    // Re-open connection if closed, although it should be open from the main script
                    if (!$koneksi || $koneksi->connect_error) { include 'koneksi.php'; }
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
                    // Re-open connection if closed, although it should be open from the main script
                    if (!$koneksi || $koneksi->connect_error) { include 'koneksi.php'; }
                    $pelanggan_res = $koneksi->query("SELECT id_pelanggan, nama_pelanggan FROM Pelanggan ORDER BY nama_pelanggan");
                    while($pel = $pelanggan_res->fetch_assoc()) {
                        echo "<option value='".htmlspecialchars($pel['id_pelanggan'])."'>".htmlspecialchars($pel['nama_pelanggan'])."</option>";
                    }
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

    // Logic to show/hide date/month/year inputs for sales chart based on radio button selection
    const reportTypeRadios = document.querySelectorAll('input[name="report_type"]');
    const selectedDateInput = document.querySelector('input[name="selected_date"]');
    const selectedMonthInput = document.querySelector('input[name="selected_month"]');
    const selectedYearInput = document.querySelector('input[name="selected_year"]');

    function toggleDateInputs() {
        const currentReportType = document.querySelector('input[name="report_type"]:checked').value;

        selectedDateInput.style.display = 'none';
        selectedMonthInput.style.display = 'none';
        selectedYearInput.style.display = 'none';

        if (currentReportType === 'daily') {
            selectedDateInput.style.display = 'inline-block';
        } else if (currentReportType === 'monthly') {
            selectedMonthInput.style.display = 'inline-block';
        } else if (currentReportType === 'yearly') {
            selectedYearInput.style.display = 'inline-block';
        }
    }

    reportTypeRadios.forEach(radio => {
        radio.addEventListener('change', toggleDateInputs);
    });

    // Initial call to set correct visibility on page load for sales
    toggleDateInputs();

    // Sales Chart.js implementation
    const ctx = document.getElementById('salesHistoryChart');
    const chartLabels = <?= $chartLabelsJson; ?>;
    const chartData = <?= $chartDataJson; ?>;
    const reportTitle = "<?= $report_title; ?>";
    const reportType = "<?= $report_type; ?>";

    let chartType = 'line'; // Default to line chart
    if (reportType === 'monthly_summary' || reportType === 'yearly_summary') {
        chartType = 'bar'; // Use bar chart for summaries
    }

    new Chart(ctx, {
        type: chartType,
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Total Penjualan (Rp)',
                data: chartData,
                backgroundColor: 'rgba(74, 144, 226, 0.6)', // Corresponds to var(--primary-color)
                borderColor: 'rgba(74, 144, 226, 1)',
                borderWidth: 1,
                fill: false, // For line chart, don't fill area below line
                tension: 0.4 // Smoothness of the line
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: reportTitle,
                    font: {
                        size: 18
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Total Penjualan (Rp)'
                    },
                    ticks: {
                        callback: function(value, index, values) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: (reportType === 'monthly_summary' ? 'Bulan' : (reportType === 'yearly_summary' ? 'Tahun' : 'Tanggal'))
                    }
                }
            }
        }
    });

    // Logic to show/hide date/month/year inputs for inbound chart based on radio button selection
    const inboundReportTypeRadios = document.querySelectorAll('input[name="inbound_report_type"]');
    const inboundSelectedDateInput = document.querySelector('input[name="inbound_selected_date"]');
    const inboundSelectedMonthInput = document.querySelector('input[name="inbound_selected_month"]');
    const inboundSelectedYearInput = document.querySelector('input[name="inbound_selected_year"]');

    function toggleInboundDateInputs() {
        const currentInboundReportType = document.querySelector('input[name="inbound_report_type"]:checked').value;

        inboundSelectedDateInput.style.display = 'none';
        inboundSelectedMonthInput.style.display = 'none';
        inboundSelectedYearInput.style.display = 'none';

        if (currentInboundReportType === 'daily') {
            inboundSelectedDateInput.style.display = 'inline-block';
        } else if (currentInboundReportType === 'monthly') {
            inboundSelectedMonthInput.style.display = 'inline-block';
        } else if (currentInboundReportType === 'yearly') {
            inboundSelectedYearInput.style.display = 'inline-block';
        }
    }

    inboundReportTypeRadios.forEach(radio => {
        radio.addEventListener('change', toggleInboundDateInputs);
    });

    // Initial call to set correct visibility on page load for inbound
    toggleInboundDateInputs();

    // Inbound Chart.js implementation
    const inboundCtx = document.getElementById('inboundHistoryChart');
    const inboundChartLabels = <?= $inboundChartLabelsJson; ?>;
    const inboundChartData = <?= $inboundChartDataJson; ?>;
    const inboundReportTitle = "<?= $inbound_report_title; ?>";
    const inboundReportType = "<?= $inbound_report_type; ?>";

    let inboundChartType = 'line';
    if (inboundReportType === 'monthly_summary' || inboundReportType === 'yearly_summary') {
        inboundChartType = 'bar';
    }

    new Chart(inboundCtx, {
        type: inboundChartType,
        data: {
            labels: inboundChartLabels,
            datasets: [{
                label: 'Total Pemasukan (Rp)',
                data: inboundChartData,
                backgroundColor: 'rgba(80, 227, 194, 0.6)', // Corresponds to var(--success-color)
                borderColor: 'rgba(80, 227, 194, 1)',
                borderWidth: 1,
                fill: false,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: inboundReportTitle,
                    font: {
                        size: 18
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Total Pemasukan (Rp)'
                    },
                    ticks: {
                        callback: function(value, index, values) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: (inboundReportType === 'monthly_summary' ? 'Bulan' : (inboundReportType === 'yearly_summary' ? 'Tahun' : 'Tanggal'))
                    }
                }
            }
        }
    });
});
</script>
</body>
</html>