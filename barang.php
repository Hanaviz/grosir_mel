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
        $last_num = (int)substr($last_id, $prefix_len );
        $new_num = $last_num + 1;
    } else {
        $new_num = 1;
    }
    return $prefix . '_' . str_pad($new_num, 3, '0', STR_PAD_LEFT);
}


// --- LOGIKA PROSES TAMBAH & EDIT (DIPERBARUI) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_barang = $koneksi->real_escape_string($_POST['nama_barang']);
    $kategori_barang = $koneksi->real_escape_string($_POST['kategori_barang']);
    $harga_satuan = $koneksi->real_escape_string($_POST['harga_satuan']);
    $satuan = $koneksi->real_escape_string($_POST['satuan']);

    if (isset($_POST['id_barang']) && !empty($_POST['id_barang'])) {
        // Proses UPDATE (tidak berubah)
        $id_barang = $koneksi->real_escape_string($_POST['id_barang']);
        $sql = "UPDATE Barang SET nama_barang='$nama_barang', kategori_barang='$kategori_barang', harga_satuan='$harga_satuan', satuan='$satuan' WHERE id_barang='$id_barang'";
    } else {
        // **PERBAIKAN: Proses INSERT dengan ID baru**
        $id_barang_baru = generate_unique_id($koneksi, 'BAR', 'Barang', 'id_barang');
        // Stok awal diatur menjadi 0
        $sql = "INSERT INTO Barang (id_barang, nama_barang, kategori_barang, harga_satuan, stok, satuan) VALUES ('$id_barang_baru', '$nama_barang', '$kategori_barang', '$harga_satuan', 0, '$satuan')";
    }
    $koneksi->query($sql);
    header("Location: barang.php");
    exit();
}

// Logika Hapus (tidak berubah)
// Proses Hapus diubah menjadi Proses Menonaktifkan Barang
// Logika Hapus dengan Pengecekan
if (isset($_GET['hapus'])) {
    $id_hapus = $koneksi->real_escape_string($_GET['hapus']);

    // 1. Cek apakah barang pernah digunakan di tabel detail transaksi
    $check_sql = "SELECT COUNT(*) as total FROM Transaction_Detail WHERE id_barang = '$id_hapus'";
    $check_result = $koneksi->query($check_sql);
    $total_transaksi = $check_result->fetch_assoc()['total'];

    if ($total_transaksi > 0) {
        // 2. Jika sudah ada di transaksi, kirim pesan error dan JANGAN HAPUS
        header("Location: barang.php?error=in_use");
        exit();
    } else {
        // 3. Jika belum pernah dipakai, hapus permanen dari database
        $sql = "DELETE FROM Barang WHERE id_barang='$id_hapus'";
        $koneksi->query($sql);
        header("Location: barang.php?success=deleted");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Grosir Mel - Barang</title>
    <link rel="stylesheet" href="stylebar.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        .table-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        #searchInput { width: 300px; padding: 10px 15px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .pagination-container { display: flex; gap: 5px; }
        .page-btn { padding: 8px 12px; border: 1px solid #ddd; background-color: #fff; color: var(--primary-color); cursor: pointer; border-radius: 5px; }
        .page-btn:hover { background-color: #f0f0f0; }
        .page-btn.active { background-color: var(--primary-color); color: #fff; border-color: var(--primary-color); }
        .page-btn:disabled { cursor: not-allowed; opacity: 0.6; }
        .no-data-row { text-align: center; font-style: italic; color: var(--text-color); }
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
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'transaksi.php') ? 'active' : ''; ?>">
            <a href="transaksi.php"><i class="fas fa-file-invoice-dollar"></i> <span>Transaksi</span></a>
        </li>
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'distributor.php') ? 'active' : ''; ?>">
            <a href="distributor.php"><i class="fas fa-truck"></i> <span>Distributor</span></a>
        </li>
    </ul>
</div>

<div class="main-content">
    <div class="header">
        <div class="header-title">Barang</div>
        
    </div>

    <h2>DAFTAR BARANG</h2>
    
    <div class="table-controls">
        <div id="pagination-container" class="pagination-container"></div>
        <input type="text" id="searchInput" placeholder="Cari barang...">
    </div>

    <table>
        <thead>
            <tr>
                <th>ID Barang</th>
                <th>Nama Barang</th>
                <th>Kategori</th>
                <th>Harga Satuan</th>
                <th>Jenis Satuan</th>
                <th>Stok</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody id="barangTableBody">
        <?php
 $sql = "SELECT * FROM Barang WHERE status = 'Aktif' ORDER BY CAST(SUBSTRING(id_barang, 5) AS UNSIGNED) DESC";
        $result = $koneksi->query($sql);
        if ($result->num_rows > 0):
            while ($row = $result->fetch_assoc()):
        ?>
        <tr>
            <td><?= htmlspecialchars($row['id_barang']); ?></td>
            <td><?= htmlspecialchars($row['nama_barang']); ?></td>
            <td><?= htmlspecialchars($row['kategori_barang']); ?></td>
            <td>Rp <?= number_format($row['harga_satuan']); ?></td>
            <td><?= htmlspecialchars($row['satuan']); ?></td>
            <td><?= htmlspecialchars($row['stok'] ?? 0); ?></td>
            <td>
                <button class="edit" 
                    data-id="<?= htmlspecialchars($row['id_barang']); ?>"
                    data-nama="<?= htmlspecialchars($row['nama_barang']); ?>"
                    data-kategori="<?= htmlspecialchars($row['kategori_barang']); ?>"
                    data-harga="<?= htmlspecialchars($row['harga_satuan']); ?>"
                    data-satuan="<?= htmlspecialchars($row['satuan']); ?>">
                    Edit
                </button>
                <a href="barang.php?hapus=<?= htmlspecialchars($row['id_barang']); ?>" onclick="return confirm('Yakin ingin menghapus data ini?')">
                    <button class="hapus">Hapus</button>
                </a>
            </td>
        </tr>
        <?php
            endwhile;
        else:
        ?>
        <tr class="no-data-row"><td colspan="7">Belum ada data barang.</td></tr>
        <?php
        endif;
        ?>
        </tbody>
    </table>
    <div class="action-buttons" style="text-align: right; margin-top: 10px;">
        <button class="tambah" id="tambahBtn">Tambah</button>
    </div>
</div>

<div id="barangModal" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h2 id="modalTitle">Tambah Barang Baru</h2>
        <form id="barangForm" method="POST" action="barang.php">
            <input type="hidden" id="id_barang" name="id_barang">
            <label for="nama_barang">Nama Barang:</label>
            <input type="text" id="nama_barang" name="nama_barang" required>
            <label for="kategori_barang">Kategori Barang:</label>
            <input type="text" id="kategori_barang" name="kategori_barang" required>
            <label for="harga_satuan">Harga Satuan:</label>
            <input type="number" id="harga_satuan" name="harga_satuan" min="0" required>
            <label for="satuan">Jenis Satuan:</label>
            <input type="text" id="satuan" name="satuan" placeholder="Contoh: Pcs, Lusin, Karton" required>
            <div class="modal-actions">
                <button type="submit" class="simpan">Simpan</button>
            </div>
        </form>
    </div>
</div>
    
<script>
document.addEventListener("DOMContentLoaded", function() {
    const modal = document.getElementById("barangModal");
    const tambahBtn = document.getElementById("tambahBtn");
    const closeBtn = document.querySelector(".close-button");
    const barangForm = document.getElementById("barangForm");
    const modalTitle = document.getElementById("modalTitle");

    const openModal = (mode, data = null) => {
        barangForm.reset();
        modalTitle.innerText = mode === "edit" ? "Edit Barang" : "Tambah Barang Baru";
        if (mode === 'edit') {
            document.getElementById("id_barang").value = data.id;
            document.getElementById("nama_barang").value = data.nama;
            document.getElementById("kategori_barang").value = data.kategori;
            document.getElementById("harga_satuan").value = data.harga;
            document.getElementById("satuan").value = data.satuan; // Tambahkan ini
        } else {
            document.getElementById("id_barang").value = "";
        }
        modal.style.display = "flex";
    };
    
    tambahBtn.addEventListener("click", () => openModal("tambah"));
    closeBtn.addEventListener("click", () => modal.style.display = "none");
    window.addEventListener("click", (e) => { if (e.target === modal) modal.style.display = "none"; });

    document.getElementById("barangTableBody").addEventListener("click", (event) => {
        if (event.target.classList.contains("edit")) {
            const button = event.target;
            openModal("edit", {
                id: button.dataset.id,
                nama: button.dataset.nama,
                kategori: button.dataset.kategori,
                harga: button.dataset.harga,
                satuan: button.dataset.satuan // Tambahkan ini
            });
        }
    });

    const tableBody = document.getElementById("barangTableBody");
    const searchInput = document.getElementById("searchInput");
    const paginationContainer = document.getElementById("pagination-container");
    const originalRows = Array.from(tableBody.querySelectorAll("tr"));
    let filteredRows = [...originalRows];
    const rowsPerPage = 5; 
    let currentPage = 1;

    function displayPage(page) {
        currentPage = page;
        tableBody.innerHTML = "";
        
        const start = (page - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        const paginatedRows = filteredRows.slice(start, end);

        if (paginatedRows.length > 0) {
            paginatedRows.forEach(row => tableBody.appendChild(row));
        } else {
            const noDataMessage = filteredRows.length === 0 && originalRows.length > 0 ? "Data tidak ditemukan." : "Belum ada data barang.";
            // Perbarui colspan menjadi 7
            tableBody.innerHTML = `<tr class="no-data-row"><td colspan="7">${noDataMessage}</td></tr>`;
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
            if (i === currentPage) { btn.classList.add("active"); }
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
        displayPage(1);
    });
    
    if (originalRows.length > 0) {
      displayPage(1);
    }
});
</script>
</body>
</html>