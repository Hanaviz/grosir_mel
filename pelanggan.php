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

// --- LOGIKA PHP DIPERBARUI ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_pelanggan = $koneksi->real_escape_string($_POST['nama_pelanggan']);
    $alamat_pelanggan = $koneksi->real_escape_string($_POST['alamat_pelanggan']);
    $nohp_pelanggan = $koneksi->real_escape_string($_POST['nohp_pelanggan']);
    
    if (isset($_POST['id_pelanggan']) && !empty($_POST['id_pelanggan'])) {
        $id_pelanggan = $koneksi->real_escape_string($_POST['id_pelanggan']);
        $sql = "UPDATE Pelanggan SET nama_pelanggan='$nama_pelanggan', alamat_pelanggan='$alamat_pelanggan', nohp_pelanggan='$nohp_pelanggan' WHERE id_pelanggan='$id_pelanggan'";
    } else {
        // **PERBAIKAN: Proses INSERT dengan ID baru**
        $id_pelanggan_baru = generate_unique_id($koneksi, 'PEL', 'Pelanggan', 'id_pelanggan');
        $sql = "INSERT INTO Pelanggan (id_pelanggan, nama_pelanggan, alamat_pelanggan, nohp_pelanggan) VALUES ('$id_pelanggan_baru', '$nama_pelanggan', '$alamat_pelanggan', '$nohp_pelanggan')";
    }
    $koneksi->query($sql);
    header("Location: pelanggan.php");
    exit();
}

// Logika Hapus (tidak berubah)
if (isset($_GET['hapus'])) {
    $id_hapus = $koneksi->real_escape_string($_GET['hapus']);
    $sql = "DELETE FROM Pelanggan WHERE id_pelanggan='$id_hapus'";
    $koneksi->query($sql);
    header("Location: pelanggan.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Grosir Mel - Pelanggan</title>
    <link rel="stylesheet" href="stylepel.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
    <h2>DAFTAR PELANGGAN</h2>
    <div class="table-controls">
        <div id="pagination-container" class="pagination-container"></div>
        <input type="text" id="searchInput" placeholder="Cari pelanggan...">
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama Pelanggan</th>
                <th>Alamat</th>
                <th>No. Hp</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody id="pelangganTableBody">
        <?php
        // Query SELECT tidak perlu diubah, karena tidak masalah jika data 'jenis_pelanggan' ikut terbaca
        $sql = "SELECT * FROM Pelanggan ORDER BY id_pelanggan DESC";
        $result = $koneksi->query($sql);
        if ($result && $result->num_rows > 0):
            while ($row = $result->fetch_assoc()):
        ?>
        <tr>
            <td><?= htmlspecialchars($row['id_pelanggan']); ?></td>
            <td><?= htmlspecialchars($row['nama_pelanggan']); ?></td>
            <td><?= htmlspecialchars($row['alamat_pelanggan']); ?></td>
            <td><?= htmlspecialchars($row['nohp_pelanggan']); ?></td>
            <td class="action-cell">
                <button class="edit"
                    data-id="<?= htmlspecialchars($row['id_pelanggan']); ?>"
                    data-nama="<?= htmlspecialchars($row['nama_pelanggan']); ?>"
                    data-alamat="<?= htmlspecialchars($row['alamat_pelanggan']); ?>"
                    data-nohp="<?= htmlspecialchars($row['nohp_pelanggan']); ?>">
                    Edit
                </button>
                <a href="pelanggan.php?hapus=<?= htmlspecialchars($row['id_pelanggan']); ?>" onclick="return confirm('Yakin hapus pelanggan ini?')">
                    <button class="hapus">Hapus</button>
                </a>
            </td>
        </tr>
        <?php
            endwhile;
        else:
        ?>
            <tr class="no-data-row"><td colspan="5">Belum ada data pelanggan.</td></tr>
        <?php
        endif;
        ?>
        </tbody>
    </table>
    <div class="action-buttons" style="text-align: right; margin-top: 10px;">
        <button class="tambah" id="tambahBtn">Tambah</button>
    </div>
</div>

<div id="dataModal" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h2 id="modalTitle">Tambah Pelanggan Baru</h2>
        <form id="dataForm" method="POST" action="pelanggan.php">
            <input type="hidden" id="id_pelanggan" name="id_pelanggan">
            
            <label for="nama_pelanggan">Nama Pelanggan:</label>
            <input type="text" id="nama_pelanggan" name="nama_pelanggan" required>
            
            <label for="alamat_pelanggan">Alamat:</label>
            <input type="text" id="alamat_pelanggan" name="alamat_pelanggan" required>
            
            <label for="nohp_pelanggan">No. HP:</label>
            <input type="text" id="nohp_pelanggan" name="nohp_pelanggan" required>

            <div class="modal-actions">
                <button type="submit" class="simpan">Simpan</button>
            </div>
        </form>
    </div>
</div>
    
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Pengaturan Modal
    const modal = document.getElementById("dataModal");
    const tambahBtn = document.getElementById("tambahBtn");
    const closeBtn = document.querySelector("#dataModal .close-button");
    const dataForm = document.getElementById("dataForm");
    const modalTitle = document.getElementById("modalTitle");

    // --- JAVASCRIPT DIPERBARUI (Logika untuk 'jenis' dihapus) ---
    const openModal = (mode, data = null) => {
        dataForm.reset();
        modalTitle.innerText = mode === "edit" ? "Edit Data Pelanggan" : "Tambah Pelanggan Baru";
        if (mode === 'edit') {
            document.getElementById("id_pelanggan").value = data.id;
            document.getElementById("nama_pelanggan").value = data.nama;
            document.getElementById("alamat_pelanggan").value = data.alamat;
            document.getElementById("nohp_pelanggan").value = data.nohp;
            // Baris untuk 'jenis_pelanggan' dihapus
        } else {
            document.getElementById("id_pelanggan").value = "";
        }
        modal.style.display = "flex";
    };
    
    tambahBtn.addEventListener("click", () => openModal("tambah"));
    closeBtn.addEventListener("click", () => modal.style.display = "none");
    window.addEventListener("click", (e) => { if(e.target == modal) modal.style.display = "none"; });

    document.getElementById("pelangganTableBody").addEventListener("click", function (event) {
        if (event.target.classList.contains("edit")) {
            const button = event.target;
            // Objek data diperbarui tanpa 'jenis'
            openModal("edit", {
                id: button.dataset.id,
                nama: button.dataset.nama,
                alamat: button.dataset.alamat,
                nohp: button.dataset.nohp
            });
        }
    });

    // --- LOGIKA PENCARIAN DAN PAGINATION DIPERBARUI ---
    const tableBody = document.getElementById("pelangganTableBody");
    const searchInput = document.getElementById("searchInput");
    const paginationContainer = document.getElementById("pagination-container");
    const originalRows = Array.from(tableBody.querySelectorAll("tr.no-data-row")).length > 0 ? [] : Array.from(tableBody.querySelectorAll("tr"));
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
            const noDataMessage = filteredRows.length === 0 && originalRows.length > 0 ? "Data tidak ditemukan." : "Belum ada data pelanggan.";
            // Colspan diubah dari 6 menjadi 5
            tableBody.innerHTML = `<tr class="no-data-row"><td colspan="5">${noDataMessage}</td></tr>`;
        }
        setupPagination();
    }

    function setupPagination() {
        paginationContainer.innerHTML = "";
        const pageCount = Math.ceil(filteredRows.length / rowsPerPage);

        if (pageCount > 0) {
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
                if (i === currentPage) btn.classList.add("active");
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