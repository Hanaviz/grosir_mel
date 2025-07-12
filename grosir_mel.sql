
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `grosir_mel`
--

-- --------------------------------------------------------

--
-- Table structure for table `barang`
--

CREATE TABLE `barang` (
  `id_barang` varchar(20) NOT NULL,
  `nama_barang` varchar(100) DEFAULT NULL,
  `kategori_barang` varchar(100) DEFAULT NULL,
  `harga_satuan` int(11) DEFAULT NULL,
  `satuan` varchar(50) NOT NULL DEFAULT 'Pcs',
  `stok` int(11) NOT NULL DEFAULT 0,
  `status` enum('Aktif','Tidak Aktif') NOT NULL DEFAULT 'Aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barang`
--

INSERT INTO `barang` (`id_barang`, `nama_barang`, `kategori_barang`, `harga_satuan`, `satuan`, `stok`, `status`) VALUES
('BAR_001', 'Indomie Goreng Rasa Ayam Geprek', 'MAKANAN', 3000, 'Pcs', 40, 'Aktif'),
('BAR_002', 'Golda Coffe', 'MINUMAN', 2500, 'Botol', 80, 'Aktif'),
('BAR_003', 'Pepsodent-Besar', 'Barang Harian', 7000, 'Pcs', 35, 'Aktif');

-- --------------------------------------------------------

--
-- Table structure for table `distributor`
--

CREATE TABLE `distributor` (
  `id_distributor` varchar(20) NOT NULL,
  `nama_distributor` varchar(100) DEFAULT NULL,
  `alamat_distributor` varchar(255) DEFAULT NULL,
  `telepon_distributor` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `distributor`
--

INSERT INTO `distributor` (`id_distributor`, `nama_distributor`, `alamat_distributor`, `telepon_distributor`) VALUES
('DIS_001', 'WINGS', 'Jakarta', '08231648988'),
('DIS_002', 'Unilever', 'jakarta', '0864946149'),
('DIS_003', 'indofood', 'bandung', '083164697924');

-- --------------------------------------------------------

--
-- Table structure for table `pelanggan`
--

CREATE TABLE `pelanggan` (
  `id_pelanggan` varchar(20) NOT NULL,
  `nama_pelanggan` varchar(100) DEFAULT NULL,
  `alamat_pelanggan` varchar(255) DEFAULT NULL,
  `nohp_pelanggan` varchar(20) DEFAULT NULL,
  `jenis_pelanggan` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pelanggan`
--

INSERT INTO `pelanggan` (`id_pelanggan`, `nama_pelanggan`, `alamat_pelanggan`, `nohp_pelanggan`, `jenis_pelanggan`) VALUES
('PEL_001', 'ucup', 'pauh', '0816273448899', NULL),
('PEL_002', 'ahmad', 'belimbing', '0893834382938', NULL),
('PEL_003', 'yanto', 'andalas', '08127635462737', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `transaction_detail`
--

CREATE TABLE `transaction_detail` (
  `id_detail` varchar(20) NOT NULL,
  `id_transaksi` varchar(20) NOT NULL,
  `id_barang` varchar(20) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `harga_saat_transaksi` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction_detail`
--

INSERT INTO `transaction_detail` (`id_detail`, `id_transaksi`, `id_barang`, `jumlah`, `harga_saat_transaksi`) VALUES
('DET_001', 'TRX_001', 'BAR_001', 50, 3000.00),
('DET_002', 'TRX_002', 'BAR_003', 40, 7000.00),
('DET_003', 'TRX_003', 'BAR_002', 100, 2500.00),
('DET_004', 'TRX_004', 'BAR_002', 20, 2500.00),
('DET_005', 'TRX_005', 'BAR_001', 10, 3000.00),
('DET_006', 'TRX_006', 'BAR_003', 5, 7000.00);

-- --------------------------------------------------------

--
-- Table structure for table `transaction_header`
--

CREATE TABLE `transaction_header` (
  `id_transaksi` varchar(20) NOT NULL,
  `tanggal` date DEFAULT NULL,
  `id_distributor` varchar(20) DEFAULT NULL,
  `id_pelanggan` varchar(20) DEFAULT NULL,
  `tipe_transaksi` enum('Masuk','Keluar') NOT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction_header`
--

INSERT INTO `transaction_header` (`id_transaksi`, `tanggal`, `id_distributor`, `id_pelanggan`, `tipe_transaksi`, `keterangan`) VALUES
('TRX_001', '2025-06-17', 'DIS_003', NULL, 'Masuk', 'Pemasukan barang dari distributor'),
('TRX_002', '2025-06-17', 'DIS_002', NULL, 'Masuk', 'Pemasukan barang dari distributor'),
('TRX_003', '2025-06-17', 'DIS_001', NULL, 'Masuk', 'Pemasukan barang dari distributor'),
('TRX_004', '2025-06-17', NULL, 'PEL_002', 'Keluar', 'Penjualan barang ke pelanggan'),
('TRX_005', '2025-06-17', NULL, 'PEL_001', 'Keluar', 'Penjualan barang ke pelanggan'),
('TRX_006', '2025-06-17', NULL, 'PEL_003', 'Keluar', 'Penjualan barang ke pelanggan');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barang`
--
ALTER TABLE `barang`
  ADD PRIMARY KEY (`id_barang`);

--
-- Indexes for table `distributor`
--
ALTER TABLE `distributor`
  ADD PRIMARY KEY (`id_distributor`);

--
-- Indexes for table `pelanggan`
--
ALTER TABLE `pelanggan`
  ADD PRIMARY KEY (`id_pelanggan`);

--
-- Indexes for table `transaction_detail`
--
ALTER TABLE `transaction_detail`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `fk_header_detail` (`id_transaksi`),
  ADD KEY `fk_barang_detail` (`id_barang`);

--
-- Indexes for table `transaction_header`
--
ALTER TABLE `transaction_header`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `fk_pelanggan_header` (`id_pelanggan`),
  ADD KEY `fk_distributor_header` (`id_distributor`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `transaction_detail`
--
ALTER TABLE `transaction_detail`
  ADD CONSTRAINT `fk_barang_detail` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_header_detail` FOREIGN KEY (`id_transaksi`) REFERENCES `transaction_header` (`id_transaksi`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `transaction_header`
--
ALTER TABLE `transaction_header`
  ADD CONSTRAINT `fk_distributor_header` FOREIGN KEY (`id_distributor`) REFERENCES `distributor` (`id_distributor`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pelanggan_header` FOREIGN KEY (`id_pelanggan`) REFERENCES `pelanggan` (`id_pelanggan`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
