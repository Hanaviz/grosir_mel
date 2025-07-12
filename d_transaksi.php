<?php
include 'koneksi.php';

// Fungsi untuk membuat ID unik dengan prefiks
function generate_unique_id($koneksi, $prefix, $table, $column) {
    $prefix_len = strlen($prefix) + 1;
    // KODE YANG BENAR
$query = "SELECT $column FROM $table WHERE $column LIKE '$prefix\_%' ORDER BY CAST(SUBSTRING($column, $prefix_len + 1) AS UNSIGNED) DESC LIMIT 1";
    $result = $koneksi->query($query);
    if ($result->num_rows > 0) {
        $last_id = $result->fetch_assoc()[$column];
        $last_num = (int)substr($last_id, $prefix_len);
        $new_num = $last_num + 1;
    } else {
        $new_num = 1;
    }
    return $prefix . '_' . str_pad($new_num, 3, '0', STR_PAD_LEFT);
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: home.php");
    exit();
}
$id_transaksi = $koneksi->real_escape_string($_GET['id']);

// --- LOGIKA PROSES TAMBAH ITEM BARANG ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_barang'])) {
    $id_barang = $koneksi->real_escape_string($_POST['id_barang']);
    $jumlah = (int)$_POST['jumlah'];

    $tipe_res = $koneksi->query("SELECT tipe_transaksi FROM Transaction_Header WHERE id_transaksi='$id_transaksi'");
    $tipe_transaksi = $tipe_res->fetch_assoc()['tipe_transaksi'];

    $barang_res = $koneksi->query("SELECT harga_satuan, stok FROM Barang WHERE id_barang='$id_barang'");
    $barang_data = $barang_res->fetch_assoc();
    $harga_satuan = $barang_data['harga_satuan'];
    $stok_saat_ini = $barang_data['stok'];

    if ($tipe_transaksi == 'Keluar' && $jumlah > $stok_saat_ini) {
        header("Location: d_transaksi.php?id=" . $id_transaksi . "&error=stok_kurang");
        exit();
    }
    
    // **PERBAIKAN: Buat ID Detail baru**
    $id_detail_baru = generate_unique_id($koneksi, 'DET', 'Transaction_Detail', 'id_detail');
    $sql = "INSERT INTO Transaction_Detail (id_detail, id_transaksi, id_barang, jumlah, harga_saat_transaksi) VALUES ('$id_detail_baru', '$id_transaksi', '$id_barang', '$jumlah', '$harga_satuan')";
    
    if ($koneksi->query($sql)) {
        if ($tipe_transaksi == 'Masuk') {
            $koneksi->query("UPDATE Barang SET stok = stok + $jumlah WHERE id_barang = '$id_barang'");
        } else {
            $koneksi->query("UPDATE Barang SET stok = stok - $jumlah WHERE id_barang = '$id_barang'");
        }
    }
    
    header("Location: d_transaksi.php?id=" . $id_transaksi);
    exit();
}

// Proses Hapus Item dari Transaksi (tidak berubah)
if (isset($_GET['hapus_detail'])) {
    $id_detail_hapus = $koneksi->real_escape_string($_GET['hapus_detail']);
    
    $detail_res = $koneksi->query("SELECT id_barang, jumlah, (SELECT tipe_transaksi FROM Transaction_Header th WHERE th.id_transaksi=td.id_transaksi) as tipe FROM Transaction_Detail td WHERE id_detail='$id_detail_hapus'");
    if ($detail_res->num_rows > 0) {
        $detail_data = $detail_res->fetch_assoc();
        $id_barang_stok = $detail_data['id_barang'];
        $jumlah_stok = $detail_data['jumlah'];
        if ($detail_data['tipe'] == 'Masuk') {
            $koneksi->query("UPDATE Barang SET stok = stok - $jumlah_stok WHERE id_barang = '$id_barang_stok'");
        } else {
            $koneksi->query("UPDATE Barang SET stok = stok + $jumlah_stok WHERE id_barang = '$id_barang_stok'");
        }
    }

    $koneksi->query("DELETE FROM Transaction_Detail WHERE id_detail='$id_detail_hapus'");
    header("Location: d_transaksi.php?id=" . $id_transaksi);
    exit();
}


// --- Ambil Data untuk Tampilan ---
$sql_header = "SELECT th.id_transaksi, th.tanggal, th.tipe_transaksi, p.nama_pelanggan, d.nama_distributor
               FROM Transaction_Header th
               LEFT JOIN Pelanggan p ON th.id_pelanggan = p.id_pelanggan
               LEFT JOIN Distributor d ON th.id_distributor = d.id_distributor
               WHERE th.id_transaksi = '$id_transaksi'";
$header_result = $koneksi->query($sql_header);
if($header_result->num_rows == 0) {
    header("Location: home.php");
    exit();
}
$header_data = $header_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Detail Transaksi - Grosir Mel</title>
    <link rel="stylesheet" href="stylebar.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .alert-box { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: var(--border-radius); font-weight: 500; }
        .alert-box.danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-header"><h1>Grosir Mel</h1></div>
    <ul class="sidebar-menu">
        <li><a href="home.php"><i class="fas fa-house"></i> <span>Home</span></a></li>
        <li><a href="barang.php"><i class="fas fa-boxes-stacked"></i> <span>Barang</span></a></li>
        <li><a href="pelanggan.php"><i class="fas fa-user-friends"></i> <span>Pelanggan</span></a></li>
        <li class="active"><a href="transaksi.php"><i class="fas fa-file-invoice-dollar"></i> <span>Transaksi</span></a></li>
        <li><a href="distributor.php"><i class="fas fa-truck"></i> <span>Distributor</span></a></li>
    </ul>
</div>

<div class="main-content">
    <div class="header">
        <div class="header-title">Detail Transaksi #<?= htmlspecialchars($header_data['id_transaksi']); ?></div>
        <a href="transaksi.php"><button class="logout">Kembali</button></a>
    </div>
    
    <?php if(isset($_GET['error']) && $_GET['error'] == 'stok_kurang'): ?>
        <div class="alert-box danger">
            <i class="fas fa-exclamation-triangle"></i> Gagal menambahkan barang: Stok tidak mencukupi!
        </div>
    <?php endif; ?>

    <div class="info-header" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: var(--shadow); margin-bottom: 20px;">
        <p><strong>Tanggal:</strong> <?= htmlspecialchars(date('d F Y', strtotime($header_data['tanggal']))); ?></p>
        <p><strong>Tipe:</strong> <span style="font-weight: bold; color: <?= $header_data['tipe_transaksi'] == 'Masuk' ? 'var(--success-color)' : 'var(--danger-color)'; ?>;"><?= htmlspecialchars($header_data['tipe_transaksi']); ?></span></p>
        <?php if($header_data['nama_pelanggan']): ?><p><strong>Pelanggan:</strong> <?= htmlspecialchars($header_data['nama_pelanggan']); ?></p><?php endif; ?>
        <?php if($header_data['nama_distributor']): ?><p><strong>Distributor:</strong> <?= htmlspecialchars($header_data['nama_distributor']); ?></p><?php endif; ?>
    </div>

    <h2>DAFTAR BARANG TRANSAKSI</h2>
    <table>
        <thead>
            <tr>
                <th>Nama Barang</th>
                <th>Jumlah</th>
                <th>Harga Satuan</th>
                <th>Subtotal</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $total_keseluruhan = 0;
        // Query diperbarui untuk mengambil 'satuan' dari tabel Barang
        $sql_detail = "SELECT td.id_detail, b.nama_barang, b.satuan, td.jumlah, td.harga_saat_transaksi
                       FROM Transaction_Detail td
                       JOIN Barang b ON td.id_barang = b.id_barang
                       WHERE td.id_transaksi = '$id_transaksi'";
        $result_detail = $koneksi->query($sql_detail);
        if ($result_detail->num_rows > 0):
            while ($row = $result_detail->fetch_assoc()):
                $subtotal = $row['jumlah'] * $row['harga_saat_transaksi'];
                $total_keseluruhan += $subtotal;
        ?>
        <tr>
            <td><?= htmlspecialchars($row['nama_barang']); ?></td>
            <td><?= htmlspecialchars($row['jumlah']) . ' ' . htmlspecialchars($row['satuan']); ?></td>
            <td>Rp <?= number_format($row['harga_saat_transaksi'], 0, ',', '.'); ?></td>
            <td>Rp <?= number_format($subtotal, 0, ',', '.'); ?></td>
            <td>
                <a href="d_transaksi.php?id=<?= $id_transaksi; ?>&hapus_detail=<?= $row['id_detail']; ?>" onclick="return confirm('Yakin hapus item ini dari transaksi?')">
                    <button class="hapus">Hapus</button>
                </a>
            </td>
        </tr>
        <?php
            endwhile;
        else:
        ?>
        <tr><td colspan="5" style="text-align:center; font-style:italic;">Belum ada barang dalam transaksi ini.</td></tr>
        <?php
        endif;
        ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" style="text-align:right;">Total Keseluruhan:</th>
                <th colspan="2">Rp <?= number_format($total_keseluruhan, 0, ',', '.'); ?></th>
            </tr>
        </tfoot>
    </table>

    <div class="action-buttons" style="text-align: right; margin-top: 20px;">
        <button class="tambah" id="tambahBtn">Tambah Barang</button>
    </div>
</div>

<div id="dataModal" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h2 id="modalTitle">Tambah Barang ke Transaksi</h2>
        <form id="dataForm" method="POST" action="d_transaksi.php?id=<?= $id_transaksi; ?>">
            <label for="id_barang">Pilih Barang:</label>
            <select id="id_barang" name="id_barang" required>
                <option value="">-- Pilih Barang --</option>
                <?php
                if (!$koneksi || $koneksi->connect_error) { include 'koneksi.php'; }
                // Query diperbarui untuk mengambil satuan dan menampilkannya di pilihan
                $barang_res = $koneksi->query("SELECT id_barang, nama_barang, stok, satuan FROM Barang WHERE status = 'Aktif' ORDER BY nama_barang");                while($brg = $barang_res->fetch_assoc()){
                    echo "<option value='".htmlspecialchars($brg['id_barang'])."'>".htmlspecialchars($brg['nama_barang'])." (Stok: ".htmlspecialchars($brg['stok'])." ".htmlspecialchars($brg['satuan']).")</option>";
                }
                ?>
            </select>

            <label for="jumlah">Jumlah:</label>
            <input type="number" id="jumlah" name="jumlah" min="1" required>

            <div class="modal-actions">
                <button type="submit" class="simpan">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById("dataModal");
    const tambahBtn = document.getElementById("tambahBtn");
    const closeBtn = document.querySelector(".close-button");
    const form = document.getElementById("dataForm");

    const openModal = () => {
        form.reset();
        modal.style.display = "flex";
    }

    tambahBtn.addEventListener("click", openModal);
    closeBtn.addEventListener("click", () => modal.style.display = "none");
    window.addEventListener("click", (e) => { if(e.target == modal) modal.style.display = "none"; });
</script>
</body>
</html>