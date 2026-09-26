-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 26, 2026 at 06:06 PM
-- Server version: 5.7.44-cll-lve
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `heriansyah_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `jual_barang`
--

CREATE TABLE `jual_barang` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Yang mengajukan jual barang',
  `catatan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('menunggu','diacc','ditolak','dibatalkan') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'menunggu',
  `acc_by` int(11) DEFAULT NULL,
  `acc_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jual_barang`
--

INSERT INTO `jual_barang` (`id`, `user_id`, `catatan`, `status`, `acc_by`, `acc_at`, `created_at`, `updated_at`) VALUES
(1, 6, '', 'diacc', 6, '2026-09-26 11:06:05', '2026-09-26 11:05:55', '2026-09-26 11:06:05');

-- --------------------------------------------------------

--
-- Table structure for table `jual_barang_item`
--

CREATE TABLE `jual_barang_item` (
  `id` int(11) NOT NULL,
  `jual_barang_id` int(11) NOT NULL,
  `produk_id` int(11) DEFAULT NULL,
  `nama_produk` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qty` int(11) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jual_barang_item`
--

INSERT INTO `jual_barang_item` (`id`, `jual_barang_id`, `produk_id`, `nama_produk`, `qty`) VALUES
(1, 1, 36, 'Metal Scrap', 100),
(2, 1, 37, 'Spesial Metal', 100);

-- --------------------------------------------------------

--
-- Table structure for table `jual_barang_whitelist`
--

CREATE TABLE `jual_barang_whitelist` (
  `produk_id` int(11) NOT NULL COMMENT 'Produk yang boleh dijual homies lewat Jual Barang',
  `added_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jual_barang_whitelist`
--

INSERT INTO `jual_barang_whitelist` (`produk_id`, `added_by`, `created_at`) VALUES
(36, 6, '2026-09-26 11:05:10'),
(37, 6, '2026-09-26 11:05:10');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_request`
--

CREATE TABLE `password_reset_request` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `discord_id_input` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `catatan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('menunggu','disetujui','ditolak') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'menunggu',
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pemrosesan`
--

CREATE TABLE `pemrosesan` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Yang mengajukan pemrosesan',
  `catatan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('menunggu','diacc','dilaporkan','selesai','ditolak','dibatalkan') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'menunggu',
  `acc_by` int(11) DEFAULT NULL,
  `acc_at` timestamp NULL DEFAULT NULL,
  `hasil_uang` decimal(14,2) DEFAULT NULL COMMENT 'Uang hasil pemrosesan, diisi pengaju',
  `bukti_link` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Link bukti (Discord/apapun yang online), diisi pengaju',
  `lapor_at` timestamp NULL DEFAULT NULL,
  `selesai_by` int(11) DEFAULT NULL,
  `selesai_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pemrosesan`
--

INSERT INTO `pemrosesan` (`id`, `user_id`, `catatan`, `status`, `acc_by`, `acc_at`, `hasil_uang`, `bukti_link`, `lapor_at`, `selesai_by`, `selesai_at`, `created_at`, `updated_at`) VALUES
(1, 6, '', 'selesai', 6, '2026-09-25 19:16:15', NULL, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTpONBqO6Ahifzr1L5wnN0YFBNPST46HBwoVuzEitbMiuo5b7Lt632ih7c&s=10', '2026-09-25 19:16:57', 6, '2026-09-25 19:17:29', '2026-09-25 19:16:06', '2026-09-25 19:17:29'),
(2, 6, '', 'selesai', 6, '2026-09-25 19:23:24', NULL, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTpONBqO6Ahifzr1L5wnN0YFBNPST46HBwoVuzEitbMiuo5b7Lt632ih7c&s=10', '2026-09-25 19:23:53', 6, '2026-09-25 19:24:08', '2026-09-25 19:23:17', '2026-09-25 19:24:08'),
(3, 5, '', 'selesai', 8, '2026-09-25 19:28:22', NULL, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTpONBqO6Ahifzr1L5wnN0YFBNPST46HBwoVuzEitbMiuo5b7Lt632ih7c&s=10', '2026-09-25 19:30:58', 8, '2026-09-25 19:31:42', '2026-09-25 19:28:11', '2026-09-25 19:31:42');

-- --------------------------------------------------------

--
-- Table structure for table `pemrosesan_hasil_item`
--

CREATE TABLE `pemrosesan_hasil_item` (
  `id` int(11) NOT NULL,
  `pemrosesan_id` int(11) NOT NULL,
  `produk_id` int(11) DEFAULT NULL,
  `nama_produk` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qty` int(11) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pemrosesan_hasil_item`
--

INSERT INTO `pemrosesan_hasil_item` (`id`, `pemrosesan_id`, `produk_id`, `nama_produk`, `qty`, `created_at`) VALUES
(1, 1, 32, 'Bagging Table', 1, '2026-09-25 19:16:57'),
(2, 1, 31, 'Meth Oven', 1, '2026-09-25 19:16:57'),
(3, 1, 24, 'Meth Bag', 1000, '2026-09-25 19:16:57'),
(4, 2, 32, 'Bagging Table', 1, '2026-09-25 19:23:53'),
(5, 2, 30, 'Meth Cooking Table', 1, '2026-09-25 19:23:53'),
(6, 2, 31, 'Meth Oven', 1, '2026-09-25 19:23:53'),
(7, 3, 32, 'Bagging Table', 1, '2026-09-25 19:30:58'),
(8, 3, 30, 'Meth Cooking Table', 1, '2026-09-25 19:30:58'),
(9, 3, 31, 'Meth Oven', 1, '2026-09-25 19:30:58'),
(10, 3, 24, 'Meth Bag', 1000, '2026-09-25 19:30:58');

-- --------------------------------------------------------

--
-- Table structure for table `pemrosesan_item`
--

CREATE TABLE `pemrosesan_item` (
  `id` int(11) NOT NULL,
  `pemrosesan_id` int(11) NOT NULL,
  `produk_id` int(11) DEFAULT NULL,
  `nama_produk` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qty` int(11) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pemrosesan_item`
--

INSERT INTO `pemrosesan_item` (`id`, `pemrosesan_id`, `produk_id`, `nama_produk`, `qty`) VALUES
(1, 1, 29, 'Baggy', 1000),
(2, 1, 32, 'Bagging Table', 1),
(3, 1, 31, 'Meth Oven', 1),
(4, 1, 34, 'Meth Pax', 100),
(5, 2, 32, 'Bagging Table', 1),
(6, 2, 30, 'Meth Cooking Table', 1),
(7, 2, 31, 'Meth Oven', 1),
(8, 3, 32, 'Bagging Table', 1),
(9, 3, 30, 'Meth Cooking Table', 1),
(10, 3, 31, 'Meth Oven', 1),
(11, 3, 34, 'Meth Pax', 100),
(12, 3, 29, 'Baggy', 1000);

-- --------------------------------------------------------

--
-- Table structure for table `penjualan`
--

CREATE TABLE `penjualan` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Yang mengajukan penjualan',
  `catatan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('menunggu','diacc','dilaporkan','selesai','ditolak','dibatalkan') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'menunggu',
  `acc_by` int(11) DEFAULT NULL,
  `acc_at` timestamp NULL DEFAULT NULL,
  `hasil_uang` decimal(14,2) DEFAULT NULL COMMENT 'Uang hasil penjualan, diisi homies',
  `bukti_link` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Link bukti (Discord/apapun yang online), diisi homies',
  `lapor_at` timestamp NULL DEFAULT NULL,
  `selesai_by` int(11) DEFAULT NULL,
  `selesai_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `penjualan_item`
--

CREATE TABLE `penjualan_item` (
  `id` int(11) NOT NULL,
  `penjualan_id` int(11) NOT NULL,
  `produk_id` int(11) DEFAULT NULL,
  `nama_produk` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qty` int(11) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pesanan`
--

CREATE TABLE `pesanan` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total` decimal(14,2) NOT NULL DEFAULT '0.00',
  `catatan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('menunggu','selesai','dibatalkan','ditolak') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'menunggu',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pesanan`
--

INSERT INTO `pesanan` (`id`, `user_id`, `total`, `catatan`, `status`, `created_at`, `updated_at`) VALUES
(5, 6, 242500.00, 'Abis badai ya bang', 'ditolak', '2026-09-25 11:39:48', '2026-09-25 17:10:08');

-- --------------------------------------------------------

--
-- Table structure for table `pesanan_item`
--

CREATE TABLE `pesanan_item` (
  `id` int(11) NOT NULL,
  `pesanan_id` int(11) NOT NULL,
  `produk_id` int(11) DEFAULT NULL,
  `nama_produk` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `harga` decimal(14,2) NOT NULL DEFAULT '0.00',
  `qty` int(11) NOT NULL DEFAULT '1',
  `subtotal` decimal(14,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pesanan_item`
--

INSERT INTO `pesanan_item` (`id`, `pesanan_id`, `produk_id`, `nama_produk`, `harga`, `qty`, `subtotal`) VALUES
(6, 5, 1, 'Virtus', 242500.00, 1, 242500.00);

-- --------------------------------------------------------

--
-- Table structure for table `produk`
--

CREATE TABLE `produk` (
  `id` int(11) NOT NULL,
  `nama_produk` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kategori` enum('Senjata','Ammo','Attachment','Narko','Lainnya','Spesial') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Lainnya',
  `foto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stok` int(11) NOT NULL DEFAULT '0',
  `harga_beli` decimal(14,2) NOT NULL DEFAULT '0.00',
  `harga_jual` decimal(14,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `produk`
--

INSERT INTO `produk` (`id`, `nama_produk`, `kategori`, `foto`, `stok`, `harga_beli`, `harga_jual`, `created_at`) VALUES
(1, 'Virtus', 'Senjata', 'produk_1790275932_5619.png', 10, 227500.00, 242500.00, '2026-09-24 12:32:17'),
(2, 'Assault Rifle', 'Senjata', 'produk_1790256086_7073.png', 10, 195000.00, 210000.00, '2026-09-24 13:05:40'),
(3, 'Black Revolver', 'Senjata', 'produk_1790275923_5544.png', 10, 91000.00, 96000.00, '2026-09-24 13:05:53'),
(4, 'Carbine Rifle', 'Senjata', 'produk_1790256043_4979.png', 99999, 260000.00, 999999999.00, '2026-09-24 13:06:06'),
(5, 'Ceramic Pistol', 'Senjata', 'produk_1790256038_7922.png', 10, 26000.00, 31000.00, '2026-09-24 13:06:15'),
(6, 'KVR', 'Senjata', 'produk_1790275916_4582.png', 10, 78000.00, 83000.00, '2026-09-24 13:06:29'),
(7, 'Machine pistol', 'Senjata', 'produk_1790256031_3831.png', 10, 26000.00, 31000.00, '2026-09-24 13:06:35'),
(8, 'Micro SMG', 'Senjata', 'produk_1790255983_7408.png', 10, 29900.00, 34900.00, '2026-09-24 13:06:42'),
(9, 'Mini SMG', 'Senjata', 'produk_1790255977_9277.png', 10, 29900.00, 34900.00, '2026-09-24 13:06:49'),
(10, 'Navy revolver', 'Senjata', 'produk_1790255971_1089.png', 10, 71500.00, 76500.00, '2026-09-24 13:06:56'),
(11, 'Pistol.50', 'Senjata', 'produk_1790255965_8876.png', 10, 9100.00, 14100.00, '2026-09-24 13:07:05'),
(12, 'Pump Shotgun', 'Senjata', 'produk_1790255959_6045.png', 10, 65000.00, 70000.00, '2026-09-24 13:07:15'),
(13, 'SMG', 'Senjata', 'produk_1790255954_6173.png', 10, 39000.00, 44000.00, '2026-09-24 13:07:23'),
(14, 'X17 Modular', 'Senjata', 'produk_1790275883_6697.png', 10, 32500.00, 37500.00, '2026-09-24 13:07:34'),
(15, 'Box Ammo .50', 'Ammo', 'produk_1790255947_4719.png', 500, 1300.00, 2500.00, '2026-09-24 13:10:22'),
(16, 'Box Ammo .44 Magnum', 'Ammo', 'produk_1790255940_8756.png', 300, 5200.00, 6000.00, '2026-09-24 13:10:46'),
(17, 'Box Ammo .45 ACP', 'Ammo', 'produk_1790255934_5353.png', 300, 5200.00, 6000.00, '2026-09-24 13:11:07'),
(18, 'Box Ammo 9MM', 'Ammo', 'produk_1790255928_9689.png', 500, 3900.00, 5000.00, '2026-09-24 13:11:15'),
(19, 'Box Ammo Rifle 762', 'Ammo', 'produk_1790255922_8138.png', 500, 6500.00, 7500.00, '2026-09-24 13:11:36'),
(20, 'Box Ammo Rifle 556', 'Ammo', 'produk_1790255916_2556.png', 500, 6500.00, 7500.00, '2026-09-24 13:12:37'),
(21, 'Box Ammo Shotgun', 'Ammo', 'produk_1790255912_4649.png', 170, 6500.00, 7500.00, '2026-09-24 13:16:38'),
(22, 'Weed Bag', 'Narko', 'produk_1790256144_8656.png', 880, 0.00, 500.00, '2026-09-24 13:22:24'),
(23, 'Cocaine Bag', 'Narko', 'produk_1790256153_9975.png', 150, 700.00, 800.00, '2026-09-24 13:22:33'),
(24, 'Meth Bag', 'Narko', 'produk_1790256161_1695.png', 4400, 0.00, 600.00, '2026-09-24 13:22:41'),
(25, 'Opium Bag', 'Narko', 'produk_1790256247_2072.png', 6000, 0.00, 600.00, '2026-09-24 13:24:07'),
(26, 'Lockpick', 'Lainnya', 'produk_1790256273_3470.png', 100, 3000.00, 4000.00, '2026-09-24 13:24:33'),
(27, 'Vest Merah', 'Lainnya', 'produk_1790258478_7138.png', 75, 1300.00, 1800.00, '2026-09-24 14:01:18'),
(28, 'Vest Biru', 'Lainnya', 'produk_1790258489_7182.png', 250, 2600.00, 4000.00, '2026-09-24 14:01:29'),
(29, 'Baggy', 'Spesial', 'produk_1790420553_1031.png', 8000, 0.00, 0.00, '2026-09-25 19:05:20'),
(30, 'Meth Cooking Table', 'Spesial', 'produk_1790420548_8666.png', 10, 0.00, 0.00, '2026-09-25 19:10:06'),
(31, 'Meth Oven', 'Spesial', 'produk_1790420539_8275.png', 10, 0.00, 0.00, '2026-09-25 19:11:44'),
(32, 'Bagging Table', 'Spesial', 'produk_1790420534_2156.png', 10, 0.00, 0.00, '2026-09-25 19:12:22'),
(33, 'Weed Seed', 'Spesial', 'produk_1790420530_1593.png', 1000, 0.00, 0.00, '2026-09-25 19:13:34'),
(34, 'Meth Pax', 'Spesial', 'produk_1790420524_4509.png', 100, 0.00, 0.00, '2026-09-25 19:15:03'),
(35, 'Weed Daun', 'Spesial', 'produk_1790420519_6136.png', 1000, 0.00, 0.00, '2026-09-25 19:22:41'),
(36, 'Metal Scrap', 'Spesial', 'produk_1790420656_3518.png', 100, 700.00, 0.00, '2026-09-26 11:04:16'),
(37, 'Spesial Metal', 'Spesial', NULL, 100, 15000.00, 0.00, '2026-09-26 11:04:59');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `discord_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('admin','staff','homies') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'homies',
  `status` enum('aktif','nonaktif') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktif',
  `must_change_password` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `phone`, `discord_id`, `role`, `status`, `must_change_password`, `created_at`) VALUES
(5, 'ilham', '$2y$10$r1kACAgaEPukEptlfehW3evcmC09n4Azq161DB.KCW5ZErScPtg/6', 'ilham', '', '539845830568443904', 'homies', 'aktif', 0, '2026-09-24 13:35:26'),
(6, 'baba', '$2y$10$e9waGH/Mv7YHsqF.YwQ8C..XFKkt8XOQVVUUm5HhJCbQCEc3/BzDu', 'Baba C', NULL, NULL, 'admin', 'aktif', 0, '2026-09-25 11:11:20'),
(7, 'dupan', '$2y$10$X4dfnaEdH2kc2XAi64Z2nuLJYKplcRCs6scbY8kMbAknJhtRB6C4q', 'Dupan T', NULL, NULL, 'admin', 'aktif', 0, '2026-09-25 11:11:29'),
(8, 'admin', '$2y$10$zHZC2CtLd1jvl/FfRsgNo.CuSnMIFT3ta67cKQL8c3SK6unKiQnFi', 'Administrator', NULL, NULL, 'admin', 'aktif', 0, '2026-09-25 11:29:02'),
(9, 'staff', '$2y$10$DoGrtc3mT/HMbMruSgCVteJKmU4ciJrL38UI3iD7.ybfAtkLABtY2', 'Staff Gudang', NULL, NULL, 'staff', 'aktif', 0, '2026-09-25 11:29:09'),
(10, 'igor', '$2y$10$z7RnOKL2/tZwfXZVaTQONe4wElJUEsqcZ04oZUwhN.rh4sQJlODJe', 'Brookz Butcher Carwyn', '89136338', '397827492960010244', 'homies', 'aktif', 0, '2026-09-26 07:28:09'),
(11, 'jacky', '$2y$10$QwMcv.iuFM9SQR5sRRxjre2LdmKXt3yHoKnJp5AGbSTJlj.RCcjXa', 'Koo Wong', '88163647', '940445026805252096', 'homies', 'aktif', 0, '2026-09-26 07:30:37'),
(12, 'alan', '$2y$10$d112OmeWS5LtLjb/zMoVAOIl1KoGbpRp4nE848w5V8/Uygev/0V1S', 'Alan Addison', '11423940', '727453186213806140', 'homies', 'aktif', 0, '2026-09-26 07:32:54'),
(13, 'bob', '$2y$10$YFkTAGoTVEUqBtKaJmxfveI9Qs7eDDRVI/lU7GqIxPQFzx.PStay.', 'BoB Finn', '77362128', '1426641038402916412', 'homies', 'aktif', 0, '2026-09-26 07:34:01'),
(14, 'celz', '$2y$10$tIpTpal4TEE.GnkvKtI6/uWv.ZRzwjFBIthrG7VVx6ATd7WSNCN66', 'Celz Phil', '44998268', '559650720463454236', 'homies', 'aktif', 0, '2026-09-26 07:35:18'),
(15, 'sella', '$2y$10$n20MR589bqgb9FH2Vkefku7KZKyWf/eW1sK9F9xshKwq79L6/KbXK', 'Sella Quinn', '21008215', '1218586984537260113', 'homies', 'aktif', 0, '2026-09-26 07:36:18'),
(16, 'abi', '$2y$10$AvpHQhEuxnMjYoXaXNFAb.hpqS8wC1iSB0Uog.1Xj66bsaxfGM.ae', 'Abigail Florencia Carwyn', '33862212', '1060919733832134687', 'homies', 'aktif', 0, '2026-09-26 07:37:19'),
(17, 'toprak', '$2y$10$pnoiTv79JymtCOrqFxc5FOYSQdd7uW2VsLVqPbqwH3.0Vp/5..pQ.', 'Glow Parto', '33470848', '1441964225298825262', 'homies', 'aktif', 0, '2026-09-26 07:38:42'),
(18, 'julian', '$2y$10$iPdnIJdl2XjrD9HRwBqgDONjHHPCPJY3rQtSvO8XeI3DomfozRLfe', 'Enzo Julian', '57446886', '998206413211963512', 'homies', 'aktif', 0, '2026-09-26 07:40:41'),
(19, 'asep', '$2y$10$4LGbKUcS6YRC7IQ751vgteZzkVBLXUC9NrGAHsCMVo.kGQ/J.87k6', 'asep air', '88869729', '1452969435911557263', 'homies', 'aktif', 0, '2026-09-26 07:41:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `jual_barang`
--
ALTER TABLE `jual_barang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_jb_user` (`user_id`),
  ADD KEY `idx_jb_status` (`status`),
  ADD KEY `fk_jb_acc` (`acc_by`);

--
-- Indexes for table `jual_barang_item`
--
ALTER TABLE `jual_barang_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_jbi` (`jual_barang_id`),
  ADD KEY `fk_jbi_produk` (`produk_id`);

--
-- Indexes for table `jual_barang_whitelist`
--
ALTER TABLE `jual_barang_whitelist`
  ADD PRIMARY KEY (`produk_id`),
  ADD KEY `fk_jbw_added_by` (`added_by`);

--
-- Indexes for table `password_reset_request`
--
ALTER TABLE `password_reset_request`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `fk_reset_admin` (`processed_by`);

--
-- Indexes for table `pemrosesan`
--
ALTER TABLE `pemrosesan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `fk_pemrosesan_acc` (`acc_by`),
  ADD KEY `fk_pemrosesan_selesai` (`selesai_by`);

--
-- Indexes for table `pemrosesan_hasil_item`
--
ALTER TABLE `pemrosesan_hasil_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_phi_pemrosesan` (`pemrosesan_id`),
  ADD KEY `fk_phi_produk` (`produk_id`);

--
-- Indexes for table `pemrosesan_item`
--
ALTER TABLE `pemrosesan_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pr` (`pemrosesan_id`),
  ADD KEY `fk_pri_produk` (`produk_id`);

--
-- Indexes for table `penjualan`
--
ALTER TABLE `penjualan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `fk_penjualan_acc` (`acc_by`),
  ADD KEY `fk_penjualan_selesai` (`selesai_by`);

--
-- Indexes for table `penjualan_item`
--
ALTER TABLE `penjualan_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pj` (`penjualan_id`),
  ADD KEY `fk_pji_produk` (`produk_id`);

--
-- Indexes for table `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `pesanan_item`
--
ALTER TABLE `pesanan_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pesanan` (`pesanan_id`),
  ADD KEY `fk_item_produk` (`produk_id`);

--
-- Indexes for table `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `jual_barang`
--
ALTER TABLE `jual_barang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `jual_barang_item`
--
ALTER TABLE `jual_barang_item`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `password_reset_request`
--
ALTER TABLE `password_reset_request`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pemrosesan`
--
ALTER TABLE `pemrosesan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pemrosesan_hasil_item`
--
ALTER TABLE `pemrosesan_hasil_item`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `pemrosesan_item`
--
ALTER TABLE `pemrosesan_item`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `penjualan`
--
ALTER TABLE `penjualan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `penjualan_item`
--
ALTER TABLE `penjualan_item`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `pesanan_item`
--
ALTER TABLE `pesanan_item`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `produk`
--
ALTER TABLE `produk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `jual_barang`
--
ALTER TABLE `jual_barang`
  ADD CONSTRAINT `fk_jb_acc` FOREIGN KEY (`acc_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_jb_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `jual_barang_item`
--
ALTER TABLE `jual_barang_item`
  ADD CONSTRAINT `fk_jbi_jual_barang` FOREIGN KEY (`jual_barang_id`) REFERENCES `jual_barang` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_jbi_produk` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `jual_barang_whitelist`
--
ALTER TABLE `jual_barang_whitelist`
  ADD CONSTRAINT `fk_jbw_added_by` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_jbw_produk` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_reset_request`
--
ALTER TABLE `password_reset_request`
  ADD CONSTRAINT `fk_reset_admin` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pemrosesan`
--
ALTER TABLE `pemrosesan`
  ADD CONSTRAINT `fk_pemrosesan_acc` FOREIGN KEY (`acc_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_pemrosesan_selesai` FOREIGN KEY (`selesai_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_pemrosesan_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pemrosesan_hasil_item`
--
ALTER TABLE `pemrosesan_hasil_item`
  ADD CONSTRAINT `fk_phi_pemrosesan` FOREIGN KEY (`pemrosesan_id`) REFERENCES `pemrosesan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_phi_produk` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `pemrosesan_item`
--
ALTER TABLE `pemrosesan_item`
  ADD CONSTRAINT `fk_pri_pemrosesan` FOREIGN KEY (`pemrosesan_id`) REFERENCES `pemrosesan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pri_produk` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `penjualan`
--
ALTER TABLE `penjualan`
  ADD CONSTRAINT `fk_penjualan_acc` FOREIGN KEY (`acc_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_penjualan_selesai` FOREIGN KEY (`selesai_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_penjualan_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `penjualan_item`
--
ALTER TABLE `penjualan_item`
  ADD CONSTRAINT `fk_pji_penjualan` FOREIGN KEY (`penjualan_id`) REFERENCES `penjualan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pji_produk` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `pesanan`
--
ALTER TABLE `pesanan`
  ADD CONSTRAINT `fk_pesanan_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pesanan_item`
--
ALTER TABLE `pesanan_item`
  ADD CONSTRAINT `fk_item_pesanan` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_item_produk` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
