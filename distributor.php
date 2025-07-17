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
        $last_num = (int)substr($last_id, $prefix_len );
        $new_num = $last_num + 1;
    } else {
        $new_num = 1;
    }
    return $prefix . '_' . str_pad($new_num, 3, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_distributor = strip_tags($_POST['nama_distributor']);
    $alamat_distributor = strip_tags($_POST['alamat_distributor']);
    $telepon_distributor = strip_tags($_POST['telepon_distributor']);

    if (isset($_POST['id_distributor']) && !empty($_POST['id_distributor'])) {
        $id_distributor = $_POST['id_distributor'];
        $stmt = $koneksi->prepare("UPDATE Distributor SET nama_distributor=?, alamat_distributor=?, telepon_distributor=? WHERE id_distributor=?");
        // Tipe data ID sekarang string 's'
        $stmt->bind_param("ssss", $nama_distributor, $alamat_distributor, $telepon_distributor, $id_distributor);
    } else {
        // **PERBAIKAN: Proses INSERT dengan ID baru**
        $id_distributor_baru = generate_unique_id($koneksi, 'DIS', 'Distributor', 'id_distributor');
        $stmt = $koneksi->prepare("INSERT INTO Distributor (id_distributor, nama_distributor, alamat_distributor, telepon_distributor) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $id_distributor_baru, $nama_distributor, $alamat_distributor, $telepon_distributor);
    }

    $stmt->execute();
    $stmt->close();
    header("Location: distributor.php");
    exit();
}

if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    $stmt = $koneksi->prepare("DELETE FROM Distributor WHERE id_distributor = ?");
    // Tipe data ID sekarang string 's'
    $stmt->bind_param("s", $id_hapus);
    $stmt->execute();
    $stmt->close();
    header("Location: distributor.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grosir Mel - Distributor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="style.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
                <h2>DAFTAR DISTRIBUTOR</h2>
            </header>
            
            <div class="table-controls">
                <div id="pagination-container" class="pagination-container"></div>
                <input type="text" id="searchInput" placeholder="Cari distributor...">
            </div>

            <div class="content-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Distributor</th>
                            <th>Alamat</th>
                            <th>No. Hp</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="distributorTableBody">
                    <?php
                    if (!$koneksi || $koneksi->connect_error) {
                        include 'koneksi.php';
                    }
                    $sql = "SELECT id_distributor, nama_distributor, alamat_distributor, telepon_distributor FROM Distributor ORDER BY id_distributor DESC";
                    $result = $koneksi->query($sql);
                    if ($result && $result->num_rows > 0):
                        while ($row = $result->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id_distributor']); ?></td>
                            <td><?= htmlspecialchars($row['nama_distributor']); ?></td>
                            <td><?= htmlspecialchars($row['alamat_distributor']); ?></td>
                            <td><?= htmlspecialchars($row['telepon_distributor']); ?></td>
                            <td class="action-cell">
            
                                <button class="btn btn-edit edit"
                                    data-id="<?= htmlspecialchars($row['id_distributor']); ?>"
                                    data-nama="<?= htmlspecialchars($row['nama_distributor']); ?>"
                                    data-alamat="<?= htmlspecialchars($row['alamat_distributor']); ?>"
                                    data-telepon="<?= htmlspecialchars($row['telepon_distributor']); ?>">
                                    <i class="fas fa-pencil-alt"></i> Edit
                                </button>
                                <a href="distributor.php?hapus=<?= htmlspecialchars($row['id_distributor']); ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus distributor ini?')">
                                    <button class="btn btn-danger"><i class="fas fa-trash-alt"></i> Hapus</button>
                                </a>
                            </td>
                        </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <tr class="no-data-row"><td colspan="5">Belum ada data distributor.</td></tr>
                    <?php
                    endif;
                    // Koneksi ditutup di file lain yang meng-include, jika tidak ada, bisa ditutup di sini
                    if (isset($koneksi) && !$koneksi->connect_error) {
                        $koneksi->close();
                    }
                    ?>
                    </tbody>
                </table>
            </div>
            <div class="action-buttons" style="text-align: right; margin-top: 15px;">
                <button class="btn btn-primary" id="tambahBtn"><i class="fas fa-plus"></i> Tambah Distributor</button>
            </div>
        </div>
    </div>

    <div id="dataModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Tambah Distributor Baru</h2>
                <span class="close-button">&times;</span>
            </div>
            <div class="modal-body">
                <form id="dataForm" method="POST" action="distributor.php">
                    <input type="hidden" id="id_distributor" name="id_distributor">
                    <div class="form-group">
                        <label for="nama_distributor">Nama Distributor:</label>
                        <input type="text" id="nama_distributor" name="nama_distributor" required>
                    </div>
                    <div class="form-group">
                        <label for="alamat_distributor">Alamat:</label>
                        <input type="text" id="alamat_distributor" name="alamat_distributor" required>
                    </div>
                    <div class="form-group">
                        <label for="telepon_distributor">No. HP:</label>
                        <input type="tel" id="telepon_distributor" name="telepon_distributor" required>
                    </div>
                    <div class="modal-actions">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
document.addEventListener("DOMContentLoaded", function() {
    // Pengaturan Modal (Sekarang berfungsi karena HTML-nya sudah ada)
    const modal = document.getElementById("dataModal");
    const modalTitle = document.getElementById("modalTitle");
    const tambahBtn = document.getElementById("tambahBtn");
    const closeBtn = document.querySelector("#dataModal .close-button");
    const dataForm = document.getElementById("dataForm");
    
    const openModal = (mode, data = {}) => {
        dataForm.reset();
        modalTitle.innerText = mode === 'edit' ? "Edit Data Distributor" : "Tambah Distributor Baru";
        if (mode === 'edit') {
            document.getElementById("id_distributor").value = data.id;
            document.getElementById("nama_distributor").value = data.nama;
            document.getElementById("alamat_distributor").value = data.alamat;
            document.getElementById("telepon_distributor").value = data.telepon;
        } else {
            document.getElementById("id_distributor").value = "";
        }
        modal.style.display = "flex";
    };

    tambahBtn.addEventListener("click", () => openModal('tambah'));
    closeBtn.addEventListener("click", () => modal.style.display = "none");
    window.addEventListener("click", (event) => { if (event.target === modal) { modal.style.display = "none"; } });

    document.getElementById("distributorTableBody").addEventListener("click", function (event) {
        const editButton = event.target.closest(".btn-edit");
        if (editButton) {
            openModal('edit', {
                id: editButton.dataset.id,
                nama: editButton.dataset.nama,
                alamat: editButton.dataset.alamat,
                telepon: editButton.dataset.telepon,
            });
        }
    });

    // --- LOGIKA PENCARIAN DAN PAGINATION ---
    const tableBody = document.getElementById("distributorTableBody");
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
            const noDataMessage = filteredRows.length === 0 && originalRows.length > 0 ? "Data tidak ditemukan." : "Belum ada data distributor.";
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
            nextButton.disabled = currentPage === pageCount;
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