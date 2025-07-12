<?php
include 'koneksi.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Daftar Transaksi - Grosir Mel</title>
    <link rel="stylesheet" href="stylebar.css" /> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* CSS untuk Kontrol Tabel (Pencarian & Pagination) */
        .table-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        #searchInput { width: 300px; padding: 10px 15px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .pagination-container { display: flex; gap: 5px; }
        .page-btn { padding: 8px 12px; border: 1px solid #ddd; background-color: #fff; color: var(--primary-color); cursor: pointer; border-radius: 5px; }
        .page-btn.active { background-color: var(--primary-color); color: #fff; }
        .page-btn:disabled { cursor: not-allowed; opacity: 0.6; }
        .no-data-row { text-align: center; font-style: italic; }
        .tipe-masuk { color: var(--success-color); font-weight: bold; }
        .tipe-keluar { color: var(--danger-color); font-weight: bold; }
    </style>
</head>
<body>
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
        <li class="active">
            <a href="transaksi.php"><i class="fas fa-file-invoice-dollar"></i> <span>Transaksi</span></a>
        </li>
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'distributor.php') ? 'active' : ''; ?>">
            <a href="distributor.php"><i class="fas fa-truck"></i> <span>Distributor</span></a>
        </li>
    </ul>
</div>

<div class="main-content">
    <div class="header">
        <div class="header-title">Riwayat Transaksi</div>
    </div>

    <h2>DAFTAR SEMUA TRANSAKSI</h2>
    <div class="header-actions" style="margin-bottom: 20px;">
    </div>
    <div class="table-controls">
        <div id="pagination-container" class="pagination-container"></div>
        <input type="text" id="searchInput" placeholder="Cari transaksi...">
    </div>

    <table>
        <thead>
            <tr>
                <th>ID Transaksi</th>
                <th>Tanggal</th>
                <th>Tipe</th>
                <th>Pihak Terkait</th>
                <th>Total Nilai</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody id="transaksiTableBody">
        <?php
        // Query kompleks untuk mengambil semua data yang diperlukan
        $sql = "SELECT
                    th.id_transaksi,
                    th.tanggal,
                    th.tipe_transaksi,
                    COALESCE(p.nama_pelanggan, d.nama_distributor) AS pihak_terkait,
                    (SELECT SUM(td.jumlah * td.harga_saat_transaksi)
                     FROM Transaction_Detail td
                     WHERE td.id_transaksi = th.id_transaksi) AS total_nilai
                FROM
                    Transaction_Header th
                LEFT JOIN Pelanggan p ON th.id_pelanggan = p.id_pelanggan
                LEFT JOIN Distributor d ON th.id_distributor = d.id_distributor
                ORDER BY th.id_transaksi DESC";
        
        $result = $koneksi->query($sql);
        if ($result && $result->num_rows > 0):
            while ($row = $result->fetch_assoc()):
        ?>
        <tr>
            <td>#<?= htmlspecialchars($row['id_transaksi']); ?></td>
            <td><?= htmlspecialchars(date('d F Y', strtotime($row['tanggal']))); ?></td>
            <td>
                <span class="<?= $row['tipe_transaksi'] == 'Masuk' ? 'tipe-masuk' : 'tipe-keluar'; ?>">
                    <?= htmlspecialchars($row['tipe_transaksi']); ?>
                </span>
            </td>
            <td><?= htmlspecialchars($row['pihak_terkait'] ?? 'N/A'); ?></td>
            <td>Rp <?= number_format($row['total_nilai'] ?? 0, 0, ',', '.'); ?></td>
            <td style="display: flex; gap: 5px;">
                <a href="d_transaksi.php?id=<?= $row['id_transaksi']; ?>">
                <button class="edit" title="Lihat Detail"><i class="fas fa-eye"></i> Detail</button>
                </a>
                 <a href="hapus_transaksi.php?id=<?= $row['id_transaksi']; ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus transaksi ini? Stok barang akan dikembalikan seperti semula.');">
                 <button class="hapus" title="Hapus Transaksi"><i class="fas fa-trash-alt"></i> Hapus</button>
    </a>
</td>
        </tr>
        <?php
            endwhile;
        else:
        ?>
        <tr class="no-data-row"><td colspan="6">Belum ada data transaksi.</td></tr>
        <?php
        endif;
        $koneksi->close();
        ?>
        </tbody>
    </table>
</div>
    
<script>
// Script ini berfungsi untuk filter pencarian dan pagination
document.addEventListener("DOMContentLoaded", function() {
    const tableBody = document.getElementById("transaksiTableBody");
    const searchInput = document.getElementById("searchInput");
    const paginationContainer = document.getElementById("pagination-container");
    // Ambil baris yang memiliki data saja (abaikan baris 'no-data-row')
    const originalRows = Array.from(tableBody.querySelectorAll("tr:not(.no-data-row)"));
    let filteredRows = [...originalRows];
    const rowsPerPage = 10; // Tampilkan 10 data per halaman
    let currentPage = 1;

    function displayPage(page) {
        currentPage = page;
        tableBody.innerHTML = ""; // Kosongkan tabel
        
        const start = (page - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        const paginatedRows = filteredRows.slice(start, end);

        if (paginatedRows.length > 0) {
            paginatedRows.forEach(row => tableBody.appendChild(row));
        } else {
            const noDataMessage = "Data transaksi tidak ditemukan.";
            tableBody.innerHTML = `<tr class="no-data-row"><td colspan="6">${noDataMessage}</td></tr>`;
        }
        setupPagination();
    }

    function setupPagination() {
        paginationContainer.innerHTML = "";
        const pageCount = Math.ceil(filteredRows.length / rowsPerPage);

        const prevButton = document.createElement("button");
        prevButton.innerText = "‹";
        prevButton.classList.add("page-btn");
        prevButton.disabled = currentPage === 1;
        prevButton.addEventListener("click", () => displayPage(currentPage - 1));
        paginationContainer.appendChild(prevButton);

        for (let i = 1; i <= pageCount; i++) {
            const btn = document.createElement("button");
            btn.innerText = i;
            btn.classList.add("page-btn");
            if (i === currentPage) {
                btn.classList.add("active");
            }
            btn.addEventListener("click", () => displayPage(i));
            paginationContainer.appendChild(btn);
        }

        const nextButton = document.createElement("button");
        nextButton.innerText = "›";
        nextButton.classList.add("page-btn");
        nextButton.disabled = currentPage === pageCount || pageCount === 0;
        nextButton.addEventListener("click", () => displayPage(currentPage + 1));
        paginationContainer.appendChild(nextButton);
    }
    
    searchInput.addEventListener("keyup", function() {
        const searchTerm = this.value.toLowerCase();
        filteredRows = originalRows.filter(row => {
            return row.textContent.toLowerCase().includes(searchTerm);
        });
        displayPage(1); // Kembali ke halaman pertama setelah pencarian
    });
    
    // Tampilan awal
    if (originalRows.length > 0) {
      displayPage(1);
    }
});

;
</script>
<div id="pemasukanModal" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h2>Buat Transaksi Pemasukan</h2>
        <form id="pemasukanForm" action="buat_transaksi.php" method="GET">
            <label for="distributor_id">Pilih Distributor:</label>
            <select name="id_distributor" id="distributor_id" required>
    <option value="">-- Pilih Distributor --</option>
    <?php
    // Buka koneksi lagi jika sudah ditutup
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

        if (button) {
            button.addEventListener("click", () => {
                modal.style.display = "flex";
            });
        }

        if (closeBtn) {
            closeBtn.addEventListener("click", () => {
                modal.style.display = "none";
            });
        }

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