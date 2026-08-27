-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 27, 2026 at 11:06 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hms_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounting_entries`
--

CREATE TABLE `accounting_entries` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `account` varchar(120) NOT NULL,
  `debit` decimal(14,2) DEFAULT 0.00,
  `credit` decimal(14,2) DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `payment_method` enum('Cash','M-Pesa','Bank Transfer','Cheque') DEFAULT 'Cash',
  `reference_id` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accounting_entries`
--

INSERT INTO `accounting_entries` (`id`, `invoice_id`, `account`, `debit`, `credit`, `note`, `payment_method`, `reference_id`, `created_at`) VALUES
(1, 1, 'Accounts Receivable', 1000.00, 0.00, 'Invoice INV-20251126154238-441', 'Cash', NULL, '2025-11-26 14:42:38'),
(2, 1, 'Sales', 0.00, 1000.00, 'Invoice INV-20251126154238-441', 'Cash', NULL, '2025-11-26 14:42:38'),
(3, 2, 'Accounts Receivable', 500.00, 0.00, 'Invoice INV-20251126154258-619', 'Cash', NULL, '2025-11-26 14:42:58'),
(4, 2, 'Pharmacy Sales', 0.00, 500.00, 'Invoice INV-20251126154258-619', 'Cash', NULL, '2025-11-26 14:42:58'),
(5, 6, 'Accounts Receivable', 900.00, 0.00, 'Invoice INV-2025112716165250', 'Cash', NULL, '2025-11-27 15:16:52'),
(6, 6, 'Pharmacy Sales', 0.00, 900.00, 'Invoice INV-2025112716165250', 'Cash', NULL, '2025-11-27 15:16:52'),
(7, 7, 'Accounts Receivable', 1200.00, 0.00, 'Invoice INV-2025112716172156', 'Cash', NULL, '2025-11-27 15:17:21'),
(8, 7, 'Pharmacy Sales', 0.00, 1200.00, 'Invoice INV-2025112716172156', 'Cash', NULL, '2025-11-27 15:17:21'),
(9, 8, 'Accounts Receivable', 1200.00, 0.00, 'Invoice INV-2025112716173459', 'Cash', NULL, '2025-11-27 15:17:34'),
(10, 8, 'Pharmacy Sales', 0.00, 1200.00, 'Invoice INV-2025112716173459', 'Cash', NULL, '2025-11-27 15:17:34'),
(11, 9, 'Accounts Receivable', 4000.00, 0.00, 'Invoice INV-20251201150748-875', 'Cash', NULL, '2025-12-01 14:07:48'),
(12, 9, 'Sales', 0.00, 4000.00, 'Invoice INV-20251201150748-875', 'Cash', NULL, '2025-12-01 14:07:48'),
(13, 10, 'Accounts Receivable', 60.00, 0.00, 'Invoice INV-2025120115081241', 'Cash', NULL, '2025-12-01 14:08:12'),
(14, 10, 'Pharmacy Sales', 0.00, 60.00, 'Invoice INV-2025120115081241', 'Cash', NULL, '2025-12-01 14:08:12'),
(15, 14, 'Accounts Receivable', 1000.00, 0.00, 'Invoice INV-20251222123117-824', 'Cash', NULL, '2025-12-22 11:31:17'),
(16, 14, 'Sales', 0.00, 1000.00, 'Invoice INV-20251222123117-824', 'Cash', NULL, '2025-12-22 11:31:17'),
(17, 15, 'Accounts Receivable', 1000.00, 0.00, 'Invoice INV-20251222125525-800', 'Cash', NULL, '2025-12-22 11:55:25'),
(18, 15, 'Sales', 0.00, 1000.00, 'Invoice INV-20251222125525-800', 'Cash', NULL, '2025-12-22 11:55:25'),
(19, NULL, 'Cash', 1000.00, 0.00, 'Pharmacy sale - Invoice INV-20260207111459-510', 'Cash', NULL, '2026-02-07 10:14:59'),
(20, NULL, 'Cash', 1000.00, 0.00, 'Pharmacy sale - Invoice INV-20260207112050-547', 'Cash', NULL, '2026-02-07 10:20:50'),
(21, NULL, 'Cash', 20.00, 0.00, 'Pharmacy sale - Invoice INV-20260207112759-510', 'Cash', NULL, '2026-02-07 10:27:59'),
(22, NULL, 'Pharmacy Sales', 50.00, 0.00, 'Invoice #25 (cash)', 'Cash', NULL, '2026-02-10 10:05:55'),
(23, NULL, 'Pharmacy Sales', 550.00, 0.00, 'Invoice #26 (cash)', 'Cash', NULL, '2026-02-23 14:15:25'),
(24, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #27 (cash)', 'Cash', NULL, '2026-02-23 14:25:02'),
(25, NULL, 'Pharmacy Sales', 210.00, 0.00, 'Invoice #28 (cash)', 'Cash', NULL, '2026-02-23 14:33:25'),
(26, NULL, 'Pharmacy Sales', 300.00, 0.00, 'Invoice #29 (mpesa)', 'Cash', NULL, '2026-02-24 14:08:49'),
(27, NULL, 'Pharmacy Sales', 1000.00, 0.00, 'Invoice #31 (Mpesa)', 'Cash', NULL, '2026-02-24 14:12:10'),
(28, NULL, 'Pharmacy Sales', 1400.00, 0.00, 'Invoice #33 (Mpesa)', 'Cash', NULL, '2026-02-24 14:24:19'),
(29, NULL, 'Pharmacy Sales', 500.00, 0.00, 'Invoice #36 (Mpesa)', 'Cash', NULL, '2026-02-24 15:08:00'),
(30, NULL, 'Pharmacy Sales', 20.00, 0.00, 'Invoice #38 (Mpesa)', 'Cash', NULL, '2026-02-25 12:55:46'),
(31, NULL, 'Pharmacy Sales', 50.00, 0.00, 'Invoice #40 (Cash)', 'Cash', NULL, '2026-02-25 19:32:11'),
(32, NULL, 'Pharmacy Sales', 10.00, 0.00, 'Invoice #41 (Cash)', 'Cash', NULL, '2026-02-25 19:33:14'),
(33, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #42 (Cash)', 'Cash', NULL, '2026-02-26 05:17:19'),
(34, NULL, 'Pharmacy Sales', 30.00, 0.00, 'Invoice #43 (Cash)', 'Cash', NULL, '2026-02-26 05:17:58'),
(35, NULL, 'Pharmacy Sales', 300.00, 0.00, 'Invoice #50 (Mpesa)', 'Cash', NULL, '2026-02-26 13:53:40'),
(36, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #51 (Mpesa)', 'Cash', NULL, '2026-02-26 13:55:06'),
(37, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #52 (Mpesa)', 'Cash', NULL, '2026-02-26 13:55:42'),
(38, NULL, 'Pharmacy Sales', 50.00, 0.00, 'Invoice #53 (Mpesa)', 'Cash', NULL, '2026-02-26 13:57:37'),
(39, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #54 (Mpesa)', 'Cash', NULL, '2026-02-26 13:58:18'),
(40, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #55 (Mpesa)', 'Cash', NULL, '2026-02-26 13:59:05'),
(41, NULL, 'Pharmacy Sales', 150.00, 0.00, 'Invoice #56 (Mpesa)', 'Cash', NULL, '2026-02-26 14:00:16'),
(42, NULL, 'Pharmacy Sales', 500.00, 0.00, 'Invoice #57 (Mpesa)', 'Cash', NULL, '2026-02-26 14:01:57'),
(43, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #58 (Cash)', 'Cash', NULL, '2026-02-26 14:42:22'),
(44, NULL, 'Pharmacy Sales', 500.00, 0.00, 'Invoice #59 (Cash)', 'Cash', NULL, '2026-02-26 16:58:54'),
(45, NULL, 'Pharmacy Sales', 150.00, 0.00, 'Invoice #60 (Cash)', 'Cash', NULL, '2026-02-26 17:04:52'),
(46, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #62 (Mpesa)', 'Cash', NULL, '2026-02-26 17:27:18'),
(47, NULL, 'Pharmacy Sales', 120.00, 0.00, 'Invoice #63 (Mpesa)', 'Cash', NULL, '2026-02-26 17:28:06'),
(48, NULL, 'Pharmacy Sales', 50.00, 0.00, 'Invoice #64 (Cash)', 'Cash', NULL, '2026-02-26 18:53:45'),
(49, NULL, 'Pharmacy Sales', 1200.00, 0.00, 'Invoice #65 (Cash)', 'Cash', NULL, '2026-02-27 05:26:30'),
(50, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #67 (Mpesa)', 'Cash', NULL, '2026-02-27 14:08:58'),
(51, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #68 (Mpesa)', 'Cash', NULL, '2026-02-27 14:10:49'),
(52, NULL, 'Pharmacy Sales', 20.00, 0.00, 'Invoice #73 (Cash)', 'Cash', NULL, '2026-02-27 19:14:23'),
(53, NULL, 'Pharmacy Sales', 20.00, 0.00, 'Invoice #74 (Cash)', 'Cash', NULL, '2026-02-27 19:14:23'),
(54, NULL, 'Pharmacy Sales', 45.00, 0.00, 'Invoice #75 (Cash)', 'Cash', NULL, '2026-02-27 19:15:08'),
(55, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #81 (Mpesa)', 'Cash', NULL, '2026-02-28 17:58:37'),
(56, NULL, 'Pharmacy Sales', 450.00, 0.00, 'Invoice #82 (Cash)', 'Cash', NULL, '2026-02-28 18:00:10'),
(57, NULL, 'Pharmacy Sales', 150.00, 0.00, 'Invoice #83 (Mpesa)', 'Cash', NULL, '2026-02-28 18:02:33'),
(58, NULL, 'Pharmacy Sales', 40.00, 0.00, 'Invoice #84 (Cash)', 'Cash', NULL, '2026-03-01 08:44:41'),
(59, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #85 (Cash)', 'Cash', NULL, '2026-03-01 08:45:31'),
(60, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #86 (Mpesa)', 'Cash', NULL, '2026-03-01 09:32:01'),
(61, NULL, 'Pharmacy Sales', 10.00, 0.00, 'Invoice #87 (Cash)', 'Cash', NULL, '2026-03-01 10:11:06'),
(62, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #88 (Mpesa)', 'Cash', NULL, '2026-03-01 10:23:31'),
(63, NULL, 'Pharmacy Sales', 110.00, 0.00, 'Invoice #90 (Mpesa)', 'Cash', NULL, '2026-03-02 07:13:27'),
(64, NULL, 'Pharmacy Sales', 20.00, 0.00, 'Invoice #91 (Cash)', 'Cash', NULL, '2026-03-02 07:14:09'),
(65, NULL, 'Pharmacy Sales', 30.00, 0.00, 'Invoice #92 (Cash)', 'Cash', NULL, '2026-03-02 07:45:15'),
(66, NULL, 'Pharmacy Sales', 30.00, 0.00, 'Invoice #93 (Cash)', 'Cash', NULL, '2026-03-02 07:45:26'),
(67, NULL, 'Pharmacy Sales', 30.00, 0.00, 'Invoice #94 (Cash)', 'Cash', NULL, '2026-03-02 08:13:45'),
(68, NULL, 'Pharmacy Sales', 20.00, 0.00, 'Invoice #97 (Mpesa)', 'Cash', NULL, '2026-03-02 11:05:37'),
(69, NULL, 'Pharmacy Sales', 20.00, 0.00, 'Invoice #98 (Mpesa)', 'Cash', NULL, '2026-03-02 11:05:46'),
(70, NULL, 'Pharmacy Sales', 300.00, 0.00, 'Invoice #99 (Mpesa)', 'Cash', NULL, '2026-03-02 11:08:45'),
(71, NULL, 'Pharmacy Sales', 60.00, 0.00, 'Invoice #100 (Cash)', 'Cash', NULL, '2026-03-02 15:01:09'),
(72, NULL, 'Pharmacy Sales', 10.00, 0.00, 'Invoice #101 (Cash)', 'Cash', NULL, '2026-03-02 15:02:25'),
(73, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #102 (Mpesa)', 'Cash', NULL, '2026-03-02 18:03:40'),
(74, NULL, 'Pharmacy Sales', 150.00, 0.00, 'Invoice #104 (Cash)', 'Cash', NULL, '2026-03-03 06:30:02'),
(75, NULL, 'Pharmacy Sales', 50.00, 0.00, 'Invoice #105 (Cash)', 'Cash', NULL, '2026-03-03 06:31:11'),
(76, NULL, 'Pharmacy Sales', 500.00, 0.00, 'Invoice #106 (Mpesa)', 'Cash', NULL, '2026-03-03 06:32:55'),
(77, NULL, 'Pharmacy Sales', 120.00, 0.00, 'Invoice #107 (Mpesa)', 'Cash', NULL, '2026-03-03 06:33:35'),
(78, NULL, 'Pharmacy Sales', 150.00, 0.00, 'Invoice #108 (Cash)', 'Cash', NULL, '2026-03-03 06:34:32'),
(79, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #109 (Mpesa)', 'Cash', NULL, '2026-03-03 06:58:14'),
(80, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #110 (Cash)', 'Cash', NULL, '2026-03-03 06:58:42'),
(81, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #111 (Cash)', 'Cash', NULL, '2026-03-03 09:34:26'),
(82, NULL, 'Pharmacy Sales', 60.00, 0.00, 'Invoice #119 (Mpesa)', 'Cash', NULL, '2026-03-04 17:39:26'),
(83, NULL, 'Pharmacy Sales', 450.00, 0.00, 'Invoice #131 (Mpesa)', 'Cash', NULL, '2026-03-05 13:09:22'),
(84, NULL, 'Pharmacy Sales', 450.00, 0.00, 'Invoice #132 (Mpesa)', 'Cash', NULL, '2026-03-05 13:16:22'),
(85, NULL, 'Pharmacy Sales', 450.00, 0.00, 'Invoice #133 (Mpesa)', 'Cash', NULL, '2026-03-05 13:16:22'),
(86, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #134 (Mpesa)', 'Cash', NULL, '2026-03-05 13:17:08'),
(87, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #135 (Cash)', 'Cash', NULL, '2026-03-05 13:21:33'),
(88, NULL, 'Pharmacy Sales', 20.00, 0.00, 'Invoice #136 (Mpesa)', 'Cash', NULL, '2026-03-05 14:09:35'),
(89, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #137 (Mpesa)', 'Cash', NULL, '2026-03-05 15:28:52'),
(90, NULL, 'Pharmacy Sales', 150.00, 0.00, 'Invoice #138 (Mpesa)', 'Cash', NULL, '2026-03-05 15:39:53'),
(91, NULL, 'Pharmacy Sales', 50.00, 0.00, 'Invoice #139 (Mpesa)', 'Cash', NULL, '2026-03-05 15:42:18'),
(92, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #140 (Mpesa)', 'Cash', NULL, '2026-03-07 07:53:15'),
(93, NULL, 'Pharmacy Sales', 150.00, 0.00, 'Invoice #142 (Mpesa)', 'Cash', NULL, '2026-03-07 07:55:28'),
(94, NULL, 'Pharmacy Sales', 500.00, 0.00, 'Invoice #143 (Mpesa)', 'Cash', NULL, '2026-03-07 08:04:33'),
(95, NULL, 'Pharmacy Sales', 50.00, 0.00, 'Invoice #147 (Mpesa)', 'Cash', NULL, '2026-03-07 13:03:58'),
(96, NULL, 'Pharmacy Sales', 150.00, 0.00, 'Invoice #150 (Cash)', 'Cash', NULL, '2026-03-07 16:53:24'),
(97, NULL, 'Pharmacy Inventory', 0.00, 4000.00, 'Stock Entry: misoprostol[ generic] (Qty: 20)', 'Cash', 'STOCK-ADJ', '2026-03-08 05:55:48'),
(98, NULL, 'Pharmacy Inventory', 0.00, 26000.00, 'Stock Entry: misoprostol[ original] (Qty: 130)', 'Cash', 'STOCK-ADJ', '2026-03-08 06:04:52'),
(99, NULL, 'Pharmacy Inventory', 0.00, 3000.00, 'Stock Entry: misoprostol[ generic] 2 (Qty: 20)', 'Cash', 'STOCK-ADJ', '2026-03-08 06:07:24'),
(100, NULL, 'Pharmacy Inventory', 0.00, 1300.00, 'Stock Entry: Acyclovir (Qty: 13)', 'Cash', 'STOCK-ADJ', '2026-03-08 13:25:24'),
(101, NULL, 'Pharmacy Inventory', 0.00, 20.00, 'Stock Entry: ACYCLOVIR 400MG TABS (Qty: 1)', 'Cash', 'STOCK-ADJ', '2026-03-08 13:27:09'),
(102, NULL, 'Pharmacy Inventory', 0.00, 30.00, 'Stock Entry: Gabapentin (Qty: 1)', 'Cash', 'STOCK-ADJ', '2026-03-08 13:28:16'),
(103, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #156 (Mpesa)', 'Cash', NULL, '2026-03-08 14:45:51'),
(104, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #157 (Mpesa)', 'Cash', NULL, '2026-03-08 14:46:35'),
(105, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #158 (Mpesa)', 'Cash', NULL, '2026-03-08 14:46:43'),
(106, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #161 (Cash)', 'Cash', NULL, '2026-03-08 20:08:53'),
(107, NULL, 'Pharmacy Sales', 50.00, 0.00, 'Invoice #162 (Mpesa)', 'Cash', NULL, '2026-03-08 20:09:17'),
(108, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #163 (Cash)', 'Cash', NULL, '2026-03-08 20:09:39'),
(109, NULL, 'Pharmacy Sales', 8000.00, 0.00, 'Invoice #164 (Mpesa)', 'Cash', NULL, '2026-03-08 20:10:56'),
(110, NULL, 'Pharmacy Sales', 30.00, 0.00, 'Invoice #165 (Cash)', 'Cash', NULL, '2026-03-08 20:11:23'),
(111, NULL, 'Pharmacy Sales', 30.00, 0.00, 'Invoice #166 (Cash)', 'Cash', NULL, '2026-03-08 20:11:34'),
(112, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #167 (Mpesa)', 'Cash', NULL, '2026-03-08 20:12:11'),
(113, NULL, 'Pharmacy Sales', 30.00, 0.00, 'Invoice #168 (Mpesa)', 'Cash', NULL, '2026-03-08 20:12:44'),
(114, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #170 (Cash)', 'Cash', NULL, '2026-03-09 06:50:04'),
(115, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #171 (Cash)', 'Cash', NULL, '2026-03-09 06:50:32'),
(116, NULL, 'Pharmacy Inventory', 0.00, 1000.00, 'Stock Entry: corncaps (Qty: 10)', 'Cash', 'STOCK-ADJ', '2026-03-09 08:03:44'),
(117, NULL, 'Pharmacy Sales', 450.00, 0.00, 'Invoice #173 (Cash)', 'Cash', NULL, '2026-03-10 13:35:52'),
(118, NULL, 'Pharmacy Sales', 30.00, 0.00, 'Invoice #174 (Mpesa)', 'Cash', NULL, '2026-03-10 13:36:26'),
(119, NULL, 'Pharmacy Inventory', 0.00, 500.00, 'Stock Entry: Gentamycin inj (Qty: 5)', 'Cash', 'STOCK-ADJ', '2026-03-10 14:35:48'),
(120, NULL, 'Pharmacy Inventory', 0.00, 240.00, 'Stock Entry: cefbactum (Qty: 2)', 'Cash', 'STOCK-ADJ', '2026-03-10 14:41:12'),
(121, NULL, 'Pharmacy Inventory', 0.00, 1000.00, 'Stock Entry: vit  A 100000mu (Qty: 100)', 'Cash', 'STOCK-ADJ', '2026-03-10 15:09:37'),
(122, NULL, 'Pharmacy Inventory', 0.00, 10000.00, 'Stock Entry: vit A 200000MU (Qty: 1000)', 'Cash', 'STOCK-ADJ', '2026-03-10 15:10:41'),
(123, NULL, 'Pharmacy Inventory', 0.00, 150.00, 'Stock Entry: Cefuroxime (Qty: 1)', 'Cash', 'STOCK-ADJ', '2026-03-10 15:55:19'),
(124, NULL, 'Pharmacy Inventory', 0.00, 50.00, 'Stock Entry: Cotrimoxazole  60mls (Qty: 1)', 'Cash', 'STOCK-ADJ', '2026-03-10 15:57:48'),
(125, NULL, 'Pharmacy Inventory', 0.00, 70.00, 'Stock Entry: Cotrimoxazole (Qty: 1)', 'Cash', 'STOCK-ADJ', '2026-03-10 15:59:33'),
(126, NULL, 'Pharmacy Inventory', 0.00, 50.00, 'Stock Entry: Ampiclox  60 (Qty: 1)', 'Cash', 'STOCK-ADJ', '2026-03-10 16:12:59'),
(127, NULL, 'Pharmacy Inventory', 0.00, 1500.00, 'Stock Entry: Cotrimoxazole 480mg (Qty: 100)', 'Cash', 'STOCK-ADJ', '2026-03-10 16:22:54'),
(128, NULL, 'Pharmacy Inventory', 0.00, 10.00, 'Stock Entry: Artemether tablets (Qty: 2)', 'Cash', 'STOCK-ADJ', '2026-03-10 16:28:56'),
(129, NULL, 'Pharmacy Inventory', 0.00, 200.00, 'Stock Entry: Sildenafil (Qty: 20)', 'Cash', 'STOCK-ADJ', '2026-03-10 16:34:42'),
(130, NULL, 'Pharmacy Inventory', 0.00, 250.00, 'Stock Entry: floxapen 500mg (Qty: 25)', 'Cash', 'STOCK-ADJ', '2026-03-10 16:39:36'),
(131, NULL, 'Pharmacy Inventory', 0.00, 50.00, 'Stock Entry: liquid paraffin 60mls (Qty: 1)', 'Cash', 'STOCK-ADJ', '2026-03-10 17:33:22'),
(132, NULL, 'Pharmacy Inventory', 0.00, 200.00, 'Stock Entry: X traderm (Qty: 1)', 'Cash', 'STOCK-ADJ', '2026-03-10 17:45:53'),
(133, NULL, 'Pharmacy Sales', 50.00, 0.00, 'Invoice #196 (Mpesa)', 'Cash', NULL, '2026-03-10 19:48:27'),
(134, NULL, 'Pharmacy Inventory', 0.00, 2000.00, 'Stock Entry: T.T (Qty: 100)', 'Cash', 'STOCK-ADJ', '2026-03-10 19:49:59'),
(135, NULL, 'Pharmacy Sales', 150.00, 0.00, 'Invoice #197 (Mpesa)', 'Cash', NULL, '2026-03-10 19:52:19'),
(136, NULL, 'Pharmacy Inventory', 0.00, 500.00, 'Stock Entry: Piroxicam 20mg (Qty: 100)', 'Cash', 'STOCK-ADJ', '2026-03-10 20:28:49'),
(137, NULL, 'Pharmacy Inventory', 0.00, 10.00, 'Stock Entry: Ibucap forte (Qty: 2)', 'Cash', 'STOCK-ADJ', '2026-03-10 20:30:38'),
(138, NULL, 'Pharmacy Inventory', 0.00, 100.00, 'Stock Entry: Montene 10mg (Qty: 10)', 'Cash', 'STOCK-ADJ', '2026-03-10 20:32:24'),
(139, NULL, 'Pharmacy Inventory', 0.00, 72.00, 'Stock Entry: Metformin 500mg (Qty: 24)', 'Cash', 'STOCK-ADJ', '2026-03-10 20:33:35'),
(140, NULL, 'Pharmacy Inventory', 0.00, 120.00, 'Stock Entry: Ifas (Qty: 60)', 'Cash', 'STOCK-ADJ', '2026-03-10 20:34:47'),
(141, NULL, 'Pharmacy Inventory', 0.00, 5.00, 'Stock Entry: Flugone (Qty: 1)', 'Cash', 'STOCK-ADJ', '2026-03-10 20:37:27'),
(142, NULL, 'Pharmacy Inventory', 0.00, 75.00, 'Stock Entry: Trust classic (Qty: 3)', 'Cash', 'STOCK-ADJ', '2026-03-11 03:39:39'),
(143, NULL, 'Pharmacy Inventory', 0.00, 250.00, 'Stock Entry: Otorex ear drops (Qty: 1)', 'Cash', 'STOCK-ADJ', '2026-03-11 04:06:27'),
(144, NULL, 'Pharmacy Inventory', 0.00, 100.00, 'Stock Entry: glycerine supp (Qty: 2)', 'Cash', 'STOCK-ADJ', '2026-03-11 04:07:31'),
(145, NULL, 'Pharmacy Inventory', 0.00, 20.00, 'Stock Entry: normil  tabs (Qty: 2)', 'Cash', 'STOCK-ADJ', '2026-03-11 04:08:28'),
(146, NULL, 'Pharmacy Inventory', 0.00, 70.00, 'Stock Entry: Nilworm tabs (Qty: 7)', 'Cash', 'STOCK-ADJ', '2026-03-11 04:09:56'),
(147, NULL, 'Pharmacy Inventory', 0.00, 160.00, 'Stock Entry: allugel (Qty: 2)', 'Cash', 'STOCK-ADJ', '2026-03-11 04:11:07'),
(148, NULL, 'Pharmacy Inventory', 0.00, 300.00, 'Stock Entry: baby weight (Qty: 150)', 'Cash', 'STOCK-ADJ', '2026-03-11 09:19:02'),
(149, NULL, 'Pharmacy Sales', 300.00, 0.00, 'Invoice #199 (Mpesa)', 'Cash', NULL, '2026-03-11 13:32:01'),
(150, NULL, 'Pharmacy Sales', 300.00, 0.00, 'Invoice #200 (Mpesa)', 'Cash', NULL, '2026-03-11 13:32:01'),
(151, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #201 (Mpesa)', 'Cash', NULL, '2026-03-11 13:32:50'),
(152, NULL, 'Pharmacy Sales', 10.00, 0.00, 'Invoice #202 (Mpesa)', 'Cash', NULL, '2026-03-11 15:10:42'),
(153, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #203 (Cash)', 'Cash', NULL, '2026-03-12 06:23:06'),
(154, NULL, 'Pharmacy Sales', 50.00, 0.00, 'Invoice #207 (Mpesa)', 'Cash', NULL, '2026-03-12 15:35:52'),
(155, NULL, 'Pharmacy Sales', 50.00, 0.00, 'Invoice #208 (Mpesa)', 'Cash', NULL, '2026-03-12 15:42:30'),
(156, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #209 (Mpesa)', 'Cash', NULL, '2026-03-13 06:25:47'),
(157, NULL, 'Pharmacy Sales', 20.00, 0.00, 'Invoice #210 (Mpesa)', 'Cash', NULL, '2026-03-13 06:27:13'),
(158, NULL, 'Pharmacy Sales', 20.00, 0.00, 'Invoice #211 (Mpesa)', 'Cash', NULL, '2026-03-13 06:28:05'),
(159, NULL, 'Pharmacy Sales', 20.00, 0.00, 'Invoice #212 (Cash)', 'Cash', NULL, '2026-03-13 08:20:20'),
(160, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #213 (Cash)', 'Cash', NULL, '2026-03-13 08:20:45'),
(161, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #215 (Cash)', 'Cash', NULL, '2026-03-13 11:12:50'),
(162, NULL, 'Pharmacy Sales', 10.00, 0.00, 'Invoice #216 (Cash)', 'Cash', NULL, '2026-03-13 11:13:08'),
(163, NULL, 'Pharmacy Sales', 300.00, 0.00, 'Invoice #218 (Cash)', 'Cash', NULL, '2026-03-13 14:31:43'),
(164, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #219 (Mpesa)', 'Cash', NULL, '2026-03-13 14:32:47'),
(165, NULL, 'Pharmacy Sales', 20.00, 0.00, 'Invoice #220 (Mpesa)', 'Cash', NULL, '2026-03-13 15:37:27'),
(166, NULL, 'Pharmacy Sales', 10.00, 0.00, 'Invoice #221 (Mpesa)', 'Cash', NULL, '2026-03-13 15:37:45'),
(167, NULL, 'Pharmacy Sales', 40.00, 0.00, 'Invoice #224 (Mpesa)', 'Cash', NULL, '2026-03-14 10:39:26'),
(168, NULL, 'Pharmacy Sales', 20.00, 0.00, 'Invoice #225 (Mpesa)', 'Cash', NULL, '2026-03-14 10:40:22'),
(169, NULL, 'Pharmacy Sales', 20.00, 0.00, 'Invoice #226 (Mpesa)', 'Cash', NULL, '2026-03-14 10:40:22'),
(170, NULL, 'Pharmacy Sales', 20.00, 0.00, 'Invoice #227 (Mpesa)', 'Cash', NULL, '2026-03-14 10:40:58'),
(171, NULL, 'Pharmacy Sales', 150.00, 0.00, 'Invoice #228 (Cash)', 'Cash', NULL, '2026-03-14 10:44:07'),
(172, NULL, 'Pharmacy Sales', 1000.00, 0.00, 'Invoice #231 (Cash)', 'Cash', NULL, '2026-03-14 13:38:03'),
(173, NULL, 'Pharmacy Sales', 500.00, 0.00, 'Invoice #232 (Mpesa)', 'Cash', NULL, '2026-03-14 14:46:20'),
(174, NULL, 'Pharmacy Sales', 150.00, 0.00, 'Invoice #233 (Mpesa)', 'Cash', NULL, '2026-03-14 14:46:37'),
(175, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #234 (Mpesa)', 'Cash', NULL, '2026-03-14 14:47:20'),
(176, NULL, 'Pharmacy Inventory', 0.00, 800.00, 'Stock Entry: Bonjela teething gel (Qty: 1)', 'Cash', 'STOCK-ADJ', '2026-03-14 16:38:23'),
(177, NULL, 'Pharmacy Sales', 1500.00, 0.00, 'Invoice #235 (Mpesa)', 'Cash', NULL, '2026-03-14 16:39:13'),
(178, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #236 (Cash)', 'Cash', NULL, '2026-03-14 16:39:46'),
(179, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #237 (Mpesa)', 'Cash', NULL, '2026-03-14 16:40:40'),
(180, NULL, 'Pharmacy Sales', 60.00, 0.00, 'Invoice #238 (Mpesa)', 'Cash', NULL, '2026-03-14 16:41:44'),
(181, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #239 (Mpesa)', 'Cash', NULL, '2026-03-17 03:52:40'),
(182, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #240 (Mpesa)', 'Cash', NULL, '2026-03-17 03:53:21'),
(183, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #241 (Mpesa)', 'Cash', NULL, '2026-03-17 03:54:04'),
(184, NULL, 'Pharmacy Sales', 120.00, 0.00, 'Invoice #242 (Mpesa)', 'Cash', NULL, '2026-03-17 03:54:50'),
(185, NULL, 'Pharmacy Sales', 10.00, 0.00, 'Invoice #243 (Mpesa)', 'Cash', NULL, '2026-03-17 03:56:06'),
(186, NULL, 'Pharmacy Sales', 45.00, 0.00, 'Invoice #244 (Mpesa)', 'Cash', NULL, '2026-03-17 03:57:00'),
(187, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #245 (Mpesa)', 'Cash', NULL, '2026-03-17 03:58:10'),
(188, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #246 (Cash)', 'Cash', NULL, '2026-03-17 03:58:51'),
(189, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #247 (Mpesa)', 'Cash', NULL, '2026-03-17 03:59:53'),
(190, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #248 (Mpesa)', 'Cash', NULL, '2026-03-17 04:00:38'),
(191, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #250 (Mpesa)', 'Cash', NULL, '2026-03-17 09:03:31'),
(192, NULL, 'Pharmacy Sales', 50.00, 0.00, 'Invoice #251 (Credit)', 'Cash', NULL, '2026-03-17 09:04:03'),
(193, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #254 (Mpesa)', 'Cash', NULL, '2026-03-17 18:36:23'),
(194, NULL, 'Procurement Expense', 0.00, 0.00, 'Stock In: 1 x pethidine (PO #1)', 'Cash', 'PO-1', '2026-03-18 14:47:26'),
(195, NULL, 'Procurement Expense', 0.00, 0.00, 'Stock In: 1 x G 23 NEEDLES (PO #2)', 'Cash', 'PO-2', '2026-03-18 14:47:44'),
(196, NULL, 'Pharmacy Sales', 60.00, 0.00, 'Invoice #261 (Mpesa)', 'Cash', NULL, '2026-03-18 20:33:55'),
(197, NULL, 'Pharmacy Inventory', 0.00, 150.00, 'Stock Entry: plasil inj (Qty: 15)', 'Cash', 'STOCK-ADJ', '2026-03-19 07:35:38'),
(198, NULL, 'Pharmacy Sales', 20.00, 0.00, 'Invoice #266 (Mpesa)', 'Cash', NULL, '2026-03-19 16:45:22'),
(199, NULL, 'Pharmacy Sales', 150.00, 0.00, 'Invoice #267 (Mpesa)', 'Cash', NULL, '2026-03-19 16:47:08'),
(200, NULL, 'Pharmacy Inventory', 0.00, 1890.00, 'Stock Entry: Cybro B 200mls (Qty: 7)', 'Cash', 'STOCK-ADJ', '2026-03-19 17:55:14'),
(201, NULL, 'Pharmacy Inventory', 0.00, 420.00, 'Stock Entry: ceftaxidine (Qty: 3)', 'Cash', 'STOCK-ADJ', '2026-03-19 17:59:10'),
(202, NULL, 'Pharmacy Inventory', 0.00, 100.00, 'Stock Entry: Indomethacin (Qty: 100)', 'Cash', 'STOCK-ADJ', '2026-03-19 18:00:38'),
(203, NULL, 'Pharmacy Sales', 20.00, 0.00, 'Invoice #268 (Cash)', 'Cash', NULL, '2026-03-19 18:04:17'),
(204, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #269 (Cash)', 'Cash', NULL, '2026-03-19 18:05:04'),
(205, NULL, 'Pharmacy Inventory', 0.00, 1200.00, 'Stock Entry: Helicos kit (Qty: 1)', 'Cash', 'INV-1', '2026-03-22 18:00:55'),
(206, NULL, 'Pharmacy Inventory', 0.00, 3750.00, 'Stock Entry: nexium (Qty: 150)', 'Cash', 'INV-2', '2026-03-22 18:03:01'),
(207, NULL, 'Pharmacy Inventory', 0.00, 6000.00, 'Stock Entry: Helicos kit (Qty: 5)', 'Cash', 'INV-2', '2026-03-22 18:05:57'),
(208, NULL, 'Procurement Expense', 0.00, 0.00, 'Stock In: 2 x pethidine (PO #3)', 'Cash', 'PO-3', '2026-03-24 09:19:04'),
(209, NULL, 'Procurement Expense', 0.00, 0.00, 'Stock In: 3 x metronidazole 60mls  (PO #3)', 'Cash', 'PO-3', '2026-03-24 09:19:08'),
(210, NULL, 'Procurement Expense', 0.00, 0.00, 'Stock In: 2 x totomol 60mls  (PO #3)', 'Cash', 'PO-3', '2026-03-24 09:19:09'),
(211, NULL, 'Procurement Expense', 0.00, 0.00, 'Stock In: 1 x pethidine (PO #2)', 'Cash', 'PO-2', '2026-03-24 09:19:11'),
(212, NULL, 'Pharmacy Sales', 20.00, 0.00, 'Invoice #276 (Mpesa)', 'Cash', NULL, '2026-03-25 12:02:15'),
(213, NULL, 'Pharmacy Inventory', 0.00, 200.00, 'Stock Entry: omeprazole tabs (Qty: 100)', 'Cash', 'STOCK-ADJ', '2026-03-25 17:13:32'),
(214, NULL, 'Pharmacy Sales', 30.00, 0.00, 'Invoice #278 (Mpesa)', 'Cash', NULL, '2026-03-25 17:14:23'),
(215, NULL, 'Pharmacy Sales', 20.00, 0.00, 'Invoice #280 (Mpesa)', 'Cash', NULL, '2026-03-25 17:40:27'),
(216, NULL, 'Pharmacy Sales', 30.00, 0.00, 'Invoice #281 (Mpesa)', 'Cash', NULL, '2026-03-25 17:53:59'),
(217, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #282 (Mpesa)', 'Cash', NULL, '2026-03-26 08:15:57'),
(218, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #287 (Cash)', 'Cash', NULL, '2026-03-27 14:18:15'),
(219, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #288 (Mpesa)', 'Cash', NULL, '2026-03-27 18:20:35'),
(220, NULL, 'Pharmacy Inventory', 0.00, 1000.00, 'Stock Entry: lasix (Qty: 10)', 'Cash', 'STOCK-ADJ', '2026-03-31 21:46:15'),
(221, NULL, 'Pharmacy Inventory', 0.00, 100.00, 'Stock Entry: lasix tabs (Qty: 10)', 'Cash', 'STOCK-ADJ', '2026-03-31 21:48:54'),
(222, NULL, 'Pharmacy Sales', 150.00, 0.00, 'Invoice #294 (Mpesa)', 'Cash', NULL, '2026-04-01 03:47:32'),
(223, NULL, 'Pharmacy Sales', 20.00, 0.00, 'Invoice #295 (Cash)', 'Cash', NULL, '2026-04-01 03:56:10'),
(224, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #296 (Mpesa)', 'Cash', NULL, '2026-04-01 03:57:54'),
(225, NULL, 'Procurement Expense', 0.00, 350.00, 'Stock In: 10 x ceftriaxone (PO #4)', 'Cash', 'PO-4', '2026-04-01 08:43:54'),
(226, NULL, 'Procurement Expense', 0.00, 35.00, 'Stock In: 1 x cetrizine 60mls (PO #4)', 'Cash', 'PO-4', '2026-04-01 08:43:55'),
(227, NULL, 'Procurement Expense', 0.00, 78.00, 'Stock In: 2 x ibrufen 100mls (PO #4)', 'Cash', 'PO-4', '2026-04-01 08:43:56'),
(228, NULL, 'Procurement Expense', 0.00, 45.00, 'Stock In: 1 x metronidazole 100mls (PO #4)', 'Cash', 'PO-4', '2026-04-01 08:43:57'),
(229, NULL, 'Procurement Expense', 0.00, 70.00, 'Stock In: 2 x metreonidazole 60mls (PO #4)', 'Cash', 'PO-4', '2026-04-01 08:43:58'),
(230, NULL, 'Procurement Expense', 0.00, 102.00, 'Stock In: 3 x amoxyl 60mls (PO #4)', 'Cash', 'PO-4', '2026-04-01 08:43:59'),
(231, NULL, 'Procurement Expense', 0.00, 350.00, 'Stock In: 100 x gloves (PO #4)', 'Cash', 'PO-4', '2026-04-01 08:44:00'),
(232, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #301 (Cash)', 'Cash', NULL, '2026-04-01 16:37:51'),
(233, NULL, 'Pharmacy Sales', 150.00, 0.00, 'Invoice #304 (Cash)', 'Cash', NULL, '2026-04-01 18:17:17'),
(234, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #305 (Mpesa)', 'Cash', NULL, '2026-04-02 04:03:22'),
(235, NULL, 'Procurement Expense', 0.00, 0.00, 'Stock In: 1 x P2 (PO #5)', 'Cash', 'PO-5', '2026-04-02 06:29:24'),
(236, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #306 (Mpesa)', 'Cash', NULL, '2026-04-02 07:31:32'),
(237, NULL, 'Pharmacy Sales', 10.00, 0.00, 'Invoice #310 (Mpesa)', 'Cash', NULL, '2026-04-02 16:09:07'),
(238, NULL, 'Pharmacy Sales', 20.00, 0.00, 'Invoice #311 (Mpesa)', 'Cash', NULL, '2026-04-02 16:10:08'),
(239, NULL, 'Procurement Expense', 0.00, 0.00, 'PO #6 linked expense #1', 'Cash', NULL, '2026-04-03 04:29:22'),
(240, NULL, 'Procurement Expense', 0.00, 0.00, 'PO #7 linked expense #2', 'Cash', NULL, '2026-04-03 04:29:22'),
(241, NULL, 'Procurement Expense', 0.00, 0.00, 'PO #8 linked expense #3', 'Cash', NULL, '2026-04-03 04:29:23'),
(242, NULL, 'Procurement Expense', 0.00, 0.00, 'PO #9 linked expense #4', 'Cash', NULL, '2026-04-03 04:29:52'),
(243, NULL, 'Procurement Expense', 0.00, 0.00, 'PO #10 linked expense #5', 'Cash', NULL, '2026-04-03 04:31:35'),
(244, NULL, 'Pharmacy Sales', 50.00, 0.00, 'Invoice #312 (Cash)', 'Cash', NULL, '2026-04-03 15:09:59'),
(245, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #313 (Cash)', 'Cash', NULL, '2026-04-03 15:12:36'),
(246, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #314 (Mpesa)', 'Cash', NULL, '2026-04-03 15:13:27'),
(247, NULL, 'Pharmacy Sales', 300.00, 0.00, 'Invoice #315 (Mpesa)', 'Cash', NULL, '2026-04-03 15:13:54'),
(248, NULL, 'Pharmacy Sales', 600.00, 0.00, 'Pharmacy sale - patient_id=106, medicine_id=31', 'Cash', NULL, '2026-04-03 17:45:19'),
(249, NULL, 'Pharmacy Sales', 300.00, 0.00, 'Pharmacy sale - patient_id=106, medicine_id=70', 'Cash', NULL, '2026-04-03 17:46:43'),
(250, NULL, 'Pharmacy Sales', 10.00, 0.00, 'Pharmacy sale - patient_id=106, medicine_id=86', 'Cash', NULL, '2026-04-03 17:47:11'),
(251, NULL, 'Pharmacy Sales', 2400.00, 0.00, 'Pharmacy sale - patient_id=107, medicine_id=21', 'Cash', NULL, '2026-04-04 07:31:12'),
(252, NULL, 'Pharmacy Sales', 500.00, 0.00, 'Pharmacy sale - patient_id=107, medicine_id=82', 'Cash', NULL, '2026-04-04 07:31:43'),
(253, NULL, 'Pharmacy Sales', 500.00, 0.00, 'Pharmacy sale - patient_id=107, medicine_id=236', 'Cash', NULL, '2026-04-04 07:32:00'),
(254, NULL, 'Pharmacy Sales', 2400.00, 0.00, 'Pharmacy sale - patient_id=107, medicine_id=238', 'Cash', NULL, '2026-04-04 07:32:53'),
(255, NULL, 'Pharmacy Sales', 500.00, 0.00, 'Pharmacy sale - patient_id=107, medicine_id=286', 'Cash', NULL, '2026-04-04 07:33:38'),
(256, NULL, 'Pharmacy Sales', 500.00, 0.00, 'Pharmacy sale - patient_id=107, medicine_id=286', 'Cash', NULL, '2026-04-04 07:33:54'),
(257, NULL, 'Pharmacy Inventory', 0.00, 330.00, 'Stock Entry: amityphline (Qty: 11)', 'Cash', 'STOCK-ADJ', '2026-04-04 07:38:45'),
(258, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Pharmacy sale - patient_id=107, medicine_id=64', 'Cash', NULL, '2026-04-04 07:39:26'),
(259, NULL, 'Pharmacy Sales', 1600.00, 0.00, 'Pharmacy sale - patient_id=108, medicine_id=6', 'Cash', NULL, '2026-04-04 18:12:23'),
(260, NULL, 'Pharmacy Sales', 800.00, 0.00, 'Pharmacy sale - patient_id=108, medicine_id=93', 'Cash', NULL, '2026-04-04 18:12:39'),
(261, NULL, 'Pharmacy Sales', 150.00, 0.00, 'Pharmacy sale - patient_id=108, medicine_id=207', 'Cash', NULL, '2026-04-04 18:13:02'),
(262, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Pharmacy sale - patient_id=108, medicine_id=60', 'Cash', NULL, '2026-04-04 18:13:52'),
(263, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Pharmacy sale - patient_id=108, medicine_id=44', 'Cash', NULL, '2026-04-04 18:14:07'),
(264, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Pharmacy sale - patient_id=109, medicine_id=19', 'Cash', NULL, '2026-04-04 19:04:40'),
(265, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Pharmacy sale - patient_id=109, medicine_id=207', 'Cash', NULL, '2026-04-04 19:04:55'),
(266, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Pharmacy sale - patient_id=109, medicine_id=269', 'Cash', NULL, '2026-04-04 19:05:21'),
(267, NULL, 'Pharmacy Sales', 1000.00, 0.00, 'Pharmacy sale - patient_id=110, medicine_id=248', 'Cash', NULL, '2026-04-04 20:09:37'),
(268, NULL, 'Pharmacy Sales', 800.00, 0.00, 'Pharmacy sale - patient_id=110, medicine_id=67', 'Cash', NULL, '2026-04-04 20:10:02'),
(269, NULL, 'Pharmacy Sales', 400.00, 0.00, 'Pharmacy sale - patient_id=110, medicine_id=8', 'Cash', NULL, '2026-04-04 20:10:39'),
(270, NULL, 'Pharmacy Sales', 400.00, 0.00, 'Pharmacy sale - patient_id=110, medicine_id=8', 'Cash', NULL, '2026-04-04 20:13:47'),
(271, NULL, 'Pharmacy Sales', 1000.00, 0.00, 'Pharmacy sale - patient_id=110, medicine_id=227', 'Cash', NULL, '2026-04-05 03:42:38'),
(272, NULL, 'Pharmacy Sales', 400.00, 0.00, 'Pharmacy sale - patient_id=107, medicine_id=232', 'Cash', NULL, '2026-04-05 03:50:48'),
(273, NULL, 'Pharmacy Sales', 500.00, 0.00, 'Pharmacy sale - patient_id=107, medicine_id=286', 'Cash', NULL, '2026-04-05 03:51:25'),
(274, NULL, 'Pharmacy Sales', 400.00, 0.00, 'Pharmacy sale - patient_id=102, medicine_id=6', 'Cash', NULL, '2026-04-05 08:08:39'),
(275, NULL, 'Pharmacy Sales', 400.00, 0.00, 'Pharmacy sale - patient_id=111, medicine_id=187', 'Cash', NULL, '2026-04-05 14:44:54'),
(276, NULL, 'Pharmacy Sales', 500.00, 0.00, 'Pharmacy sale - patient_id=111, medicine_id=82', 'Cash', NULL, '2026-04-05 14:45:10'),
(277, NULL, 'Pharmacy Sales', 400.00, 0.00, 'Pharmacy sale - patient_id=111, medicine_id=220', 'Cash', NULL, '2026-04-05 14:45:28'),
(278, NULL, 'Pharmacy Sales', 250.00, 0.00, 'Pharmacy sale - patient_id=110, medicine_id=52', 'Cash', NULL, '2026-04-05 15:26:19'),
(279, NULL, 'Pharmacy Sales', 150.00, 0.00, 'Pharmacy sale - patient_id=110, medicine_id=35', 'Cash', NULL, '2026-04-05 15:26:31'),
(280, NULL, 'Pharmacy Sales', 400.00, 0.00, 'Pharmacy sale - patient_id=111, medicine_id=187', 'Cash', NULL, '2026-04-05 20:08:00'),
(281, NULL, 'Pharmacy Sales', 400.00, 0.00, 'Pharmacy sale - patient_id=111, medicine_id=238', 'Cash', NULL, '2026-04-05 20:09:18'),
(282, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Pharmacy sale - patient_id=111, medicine_id=184', 'Cash', NULL, '2026-04-05 20:09:52'),
(283, NULL, 'Pharmacy Sales', 50.00, 0.00, 'Invoice #323 (Cash)', 'Cash', NULL, '2026-04-05 20:12:01'),
(284, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #324 (Mpesa)', 'Cash', NULL, '2026-04-05 20:12:46'),
(285, NULL, 'Pharmacy Sales', 50.00, 0.00, 'Invoice #325 (Mpesa)', 'Cash', NULL, '2026-04-05 20:14:35'),
(286, NULL, 'Pharmacy Sales', 150.00, 0.00, 'Invoice #326 (Mpesa)', 'Cash', NULL, '2026-04-05 20:15:21'),
(287, NULL, 'Pharmacy Sales', 50.00, 0.00, 'Invoice #327 (Mpesa)', 'Cash', NULL, '2026-04-05 20:15:54'),
(288, NULL, 'Pharmacy Sales', 40.00, 0.00, 'Invoice #328 (Mpesa)', 'Cash', NULL, '2026-04-05 20:17:08'),
(289, NULL, 'Pharmacy Sales', 20.00, 0.00, 'Invoice #329 (Mpesa)', 'Cash', NULL, '2026-04-05 20:17:46'),
(290, NULL, 'Pharmacy Sales', 300.00, 0.00, 'Pharmacy sale - patient_id=112, medicine_id=51', 'Cash', NULL, '2026-04-06 09:14:53'),
(291, NULL, 'Pharmacy Sales', 300.00, 0.00, 'Pharmacy sale - patient_id=112, medicine_id=206', 'Cash', NULL, '2026-04-06 09:15:35'),
(292, NULL, 'Pharmacy Sales', 150.00, 0.00, 'Pharmacy sale - patient_id=112, medicine_id=209', 'Cash', NULL, '2026-04-06 09:16:40'),
(293, NULL, 'Pharmacy Sales', 50.00, 0.00, 'Pharmacy sale - patient_id=112, medicine_id=10', 'Cash', NULL, '2026-04-06 09:17:45'),
(294, NULL, 'Pharmacy Sales', 75.00, 0.00, 'Pharmacy sale - patient_id=112, medicine_id=75', 'Cash', NULL, '2026-04-06 09:18:51'),
(295, NULL, 'Pharmacy Sales', 1200.00, 0.00, 'Pharmacy sale - patient_id=113, medicine_id=6', 'Cash', NULL, '2026-04-06 14:35:28'),
(296, NULL, 'Pharmacy Sales', 600.00, 0.00, 'Pharmacy sale - patient_id=113, medicine_id=232', 'Cash', NULL, '2026-04-06 14:35:37'),
(297, NULL, 'Pharmacy Sales', 300.00, 0.00, 'Pharmacy sale - patient_id=113, medicine_id=70', 'Cash', NULL, '2026-04-06 14:36:04'),
(298, NULL, 'Pharmacy Sales', 150.00, 0.00, 'Pharmacy sale - patient_id=113, medicine_id=19', 'Cash', NULL, '2026-04-06 14:40:05'),
(299, NULL, 'Pharmacy Sales', 1000.00, 0.00, 'Pharmacy sale - patient_id=113, medicine_id=227', 'Cash', NULL, '2026-04-06 14:41:16'),
(300, NULL, 'Pharmacy Sales', 400.00, 0.00, 'Pharmacy sale - patient_id=111, medicine_id=220', 'Cash', NULL, '2026-04-06 16:36:07'),
(301, NULL, 'Pharmacy Sales', 500.00, 0.00, 'Pharmacy sale - patient_id=111, medicine_id=82', 'Cash', NULL, '2026-04-06 16:36:47'),
(302, NULL, 'Pharmacy Sales', 700.00, 0.00, 'Pharmacy sale - patient_id=111, medicine_id=187', 'Cash', NULL, '2026-04-06 16:37:24'),
(303, NULL, 'Pharmacy Sales', 400.00, 0.00, 'Pharmacy sale - patient_id=111, medicine_id=238', 'Cash', NULL, '2026-04-06 16:38:24'),
(304, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Pharmacy sale - patient_id=116, medicine_id=24', 'Cash', NULL, '2026-04-07 11:13:08'),
(305, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #339 (Mpesa)', 'Cash', NULL, '2026-04-07 11:14:05'),
(306, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #340 (Cash)', 'Cash', NULL, '2026-04-07 11:14:38'),
(307, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #341 (Mpesa)', 'Cash', NULL, '2026-04-07 11:15:08'),
(308, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #342 (Mpesa)', 'Cash', NULL, '2026-04-07 11:15:57'),
(309, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #343 (Cash)', 'Cash', NULL, '2026-04-07 11:17:05'),
(310, NULL, 'Pharmacy Sales', 150.00, 0.00, 'Pharmacy sale - patient_id=116, medicine_id=28', 'Cash', NULL, '2026-04-07 11:22:01'),
(311, NULL, 'Pharmacy Sales', 400.00, 0.00, 'Pharmacy sale - patient_id=66, medicine_id=6', 'Cash', NULL, '2026-04-07 13:37:09'),
(312, NULL, 'Pharmacy Sales', 1200.00, 0.00, 'Pharmacy sale - patient_id=66, medicine_id=6', 'Cash', NULL, '2026-04-07 13:37:32'),
(313, NULL, 'Pharmacy Sales', 800.00, 0.00, 'Pharmacy sale - patient_id=66, medicine_id=93', 'Cash', NULL, '2026-04-07 13:37:48'),
(314, NULL, 'Pharmacy Sales', 50.00, 0.00, 'Invoice #348 (Mpesa)', 'Cash', NULL, '2026-04-07 14:04:04'),
(315, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #349 (Mpesa)', 'Cash', NULL, '2026-04-07 14:14:43'),
(316, NULL, 'Pharmacy Sales', 300.00, 0.00, 'Pharmacy sale - patient_id=117, medicine_id=70', 'Cash', NULL, '2026-04-07 17:59:37'),
(317, NULL, 'Pharmacy Sales', 150.00, 0.00, 'Pharmacy sale - patient_id=117, medicine_id=28', 'Cash', NULL, '2026-04-07 17:59:55'),
(318, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Pharmacy sale - patient_id=117, medicine_id=47', 'Cash', NULL, '2026-04-07 18:00:04'),
(319, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Pharmacy sale - patient_id=117, medicine_id=86', 'Cash', NULL, '2026-04-07 18:00:30'),
(320, NULL, 'Procurement Expense', 15211.57, 0.00, 'PO #11 linked expense #6', 'Cash', NULL, '2026-04-07 18:57:46'),
(321, NULL, 'Procurement Expense', 255.00, 0.00, 'Stock In: 1 x Hemoforce prega 200ml (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 18:59:13'),
(322, NULL, 'Procurement Expense', 90.00, 0.00, 'Stock In: 3 x Hydrocortisone 100mg ( inj) (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 18:59:18'),
(323, NULL, 'Procurement Expense', 350.00, 0.00, 'Stock In: 10 x Metronidazole  100ml (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 18:59:21'),
(324, NULL, 'Procurement Expense', 120.00, 0.00, 'Stock In: 100 x Zinc sulphate 20mg (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 18:59:23'),
(325, NULL, 'Procurement Expense', 130.20, 0.00, 'Stock In: 30 x Esomeprazole 40mg (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 18:59:26'),
(326, NULL, 'Procurement Expense', 285.00, 0.00, 'Stock In: 10 x Zulu (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 18:59:29'),
(327, NULL, 'Procurement Expense', 103.00, 0.00, 'Stock In: 1 x Needles ( G21) (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 18:59:32'),
(328, NULL, 'Procurement Expense', 115.00, 0.00, 'Stock In: 5 x Cetrizine 60ml (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 18:59:36'),
(329, NULL, 'Procurement Expense', 360.00, 0.00, 'Stock In: 30 x Cypro B plus (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 18:59:40'),
(330, NULL, 'Procurement Expense', 120.00, 0.00, 'Stock In: 3 x Ibuprofen  100mls (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 18:59:42'),
(331, NULL, 'Procurement Expense', 125.00, 0.00, 'Stock In: 5 x Chlopheniramine 60ml (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 18:59:45'),
(332, NULL, 'Procurement Expense', 700.00, 0.00, 'Stock In: 100 x floxapen 500mg (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 18:59:48'),
(333, NULL, 'Procurement Expense', 180.00, 0.00, 'Stock In: 12 x Secnidazole (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 18:59:50'),
(334, NULL, 'Procurement Expense', 170.00, 0.00, 'Stock In: 2 x Predinisolone 60ml (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 18:59:53'),
(335, NULL, 'Procurement Expense', 300.00, 0.00, 'Stock In: 20 x Dexamethasole 4mg (INJ) (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 18:59:55'),
(336, NULL, 'Procurement Expense', 175.00, 0.00, 'Stock In: 5 x Metronidazole  600ml (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:00:40'),
(337, NULL, 'Procurement Expense', 195.00, 0.00, 'Stock In: 3 x Entamaxin 60mls (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:00:43'),
(338, NULL, 'Procurement Expense', 110.00, 0.00, 'Stock In: 2 x Esomeprazole 40mg (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:00:45'),
(339, NULL, 'Procurement Expense', 130.00, 0.00, 'Stock In: 100 x Ifas (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:00:48'),
(340, NULL, 'Procurement Expense', 39.00, 0.00, 'Stock In: 3 x Fluconazole 150 mg (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:01:17'),
(341, NULL, 'Procurement Expense', 180.00, 0.00, 'Stock In: 2 x Good Morning (60ml) (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:01:25'),
(342, NULL, 'Procurement Expense', 315.00, 0.00, 'Stock In: 3 x Tricohist  60mls (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:02:36'),
(343, NULL, 'Procurement Expense', 400.00, 0.00, 'Stock In: 20 x buscpan inj (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:02:41'),
(344, NULL, 'Procurement Expense', 90.00, 0.00, 'Stock In: 10 x plasil inj (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:02:44'),
(345, NULL, 'Procurement Expense', 90.00, 0.00, 'Stock In: 100 x NIfedipine 20mg (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:02:47'),
(346, NULL, 'Procurement Expense', 360.00, 0.00, 'Stock In: 20 x Neuroforte (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:02:51'),
(347, NULL, 'Procurement Expense', 600.00, 0.00, 'Stock In: 10 x Paracetamol infusion 100mls (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:02:53'),
(348, NULL, 'Procurement Expense', 110.00, 0.00, 'Stock In: 2 x Pharmasal ointment (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:02:59'),
(349, NULL, 'Procurement Expense', 75.00, 0.00, 'Stock In: 100 x Prednisolone 5mg  (cosmos0 (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:03:02'),
(350, NULL, 'Procurement Expense', 50.00, 0.00, 'Stock In: 2 x Promethazine 60ml (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:03:05'),
(351, NULL, 'Procurement Expense', 335.00, 0.00, 'Stock In: 1 x Ranferon 200mls (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:03:08'),
(352, NULL, 'Procurement Expense', 125.00, 0.00, 'Stock In: 5 x P2 ( generic) (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:03:13'),
(353, NULL, 'Procurement Expense', 560.00, 0.00, 'Stock In: 200 x Amoxicilin 500mg (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:03:16'),
(354, NULL, 'Procurement Expense', 78.00, 0.00, 'Stock In: 3 x Surgical spirit 50mls (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:03:19'),
(355, NULL, 'Procurement Expense', 325.00, 0.00, 'Stock In: 5 x Tranexamic acid ( inj) (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:03:21'),
(356, NULL, 'Procurement Expense', 210.00, 0.00, 'Stock In: 10 x Trust classic (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:03:24'),
(357, NULL, 'Procurement Expense', 84.00, 0.00, 'Stock In: 24 x Tinidazole (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:03:27'),
(358, NULL, 'Procurement Expense', 40.00, 0.00, 'Stock In: 4 x VEGA 100MG (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:03:30'),
(359, NULL, 'Procurement Expense', 347.37, 0.00, 'Stock In: 1 x Calpol 100ms (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:03:35'),
(360, NULL, 'Procurement Expense', 510.00, 0.00, 'Stock In: 2 x Brustan 100mls (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:03:37'),
(361, NULL, 'Procurement Expense', 100.00, 0.00, 'Stock In: 2 x Bulkot B cream (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:03:40'),
(362, NULL, 'Procurement Expense', 240.00, 0.00, 'Stock In: 1 x Calpol 60ms (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:03:43'),
(363, NULL, 'Procurement Expense', 675.00, 0.00, 'Stock In: 5 x ceftaxidine (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:04:34'),
(364, NULL, 'Procurement Expense', 990.00, 0.00, 'Stock In: 30 x ceftraxne (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:04:37'),
(365, NULL, 'Procurement Expense', 550.00, 0.00, 'Stock In: 5 x Ceftax (inj) (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:04:42'),
(366, NULL, 'Procurement Expense', 400.00, 0.00, 'Stock In: 2 x zefcolin (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:04:48'),
(367, NULL, 'Procurement Expense', 135.00, 0.00, 'Stock In: 3 x Azithromycin 15mls (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:04:51'),
(368, NULL, 'Procurement Expense', 105.00, 0.00, 'Stock In: 1 x Betafen plus 60ml (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:04:54'),
(369, NULL, 'Procurement Expense', 140.00, 0.00, 'Stock In: 1 x Betafen plus 100ml (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:04:57'),
(370, NULL, 'Procurement Expense', 220.00, 0.00, 'Stock In: 4 x Azithromycin 500mg (PO #11, Supplier Invoice xn115008343)', 'Cash', NULL, '2026-04-07 19:05:03'),
(371, NULL, 'Pharmacy Inventory', 0.00, 235.00, 'Stock Entry: catoxymag 200ml (Qty: 1)', 'Cash', 'INV-1', '2026-04-07 19:07:51'),
(372, NULL, 'Pharmacy Inventory', 0.00, 260.00, 'Stock Entry: Diracip m sus 100ml (Qty: 1)', 'Cash', 'INV-2', '2026-04-07 19:10:13'),
(373, NULL, 'Pharmacy Inventory', 0.00, 8.00, 'Stock Entry: Entamaxin tabs (Qty: 1)', 'Cash', 'INV-1', '2026-04-07 19:12:43'),
(374, NULL, 'Pharmacy Inventory', 0.00, 180.00, 'Stock Entry: Gacet 250mg (Qty: 10)', 'Cash', 'INV-2', '2026-04-07 19:14:51'),
(375, NULL, 'Pharmacy Inventory', 0.00, 75.00, 'Stock Entry: tetrcycline skin oint (Qty: 3)', 'Cash', 'INV-1', '2026-04-07 19:19:28'),
(376, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #351 (Mpesa)', 'Cash', NULL, '2026-04-07 19:35:43'),
(377, NULL, 'Pharmacy Sales', 250.00, 0.00, 'Invoice #352 (Mpesa)', 'Cash', NULL, '2026-04-07 19:36:41'),
(378, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #353 (Mpesa)', 'Cash', NULL, '2026-04-08 08:59:59'),
(379, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #354 (Mpesa)', 'Cash', NULL, '2026-04-08 16:19:15'),
(380, NULL, 'Pharmacy Sales', 20.00, 0.00, 'Invoice #355 (Mpesa)', 'Cash', NULL, '2026-04-08 16:20:08'),
(381, NULL, 'Pharmacy Inventory', 0.00, 400.00, 'Stock Entry: ANUSOL CREAM (Qty: 1)', 'Cash', 'INV-1', '2026-04-09 10:04:48'),
(382, NULL, 'Pharmacy Inventory', 0.00, 1500.00, 'Stock Entry: DAFLON (Qty: 30)', 'Cash', 'INV-1', '2026-04-09 10:06:08'),
(383, 358, 'M-Pesa', 3500.00, 0.00, 'Payment received: Invoice INV-20260409135424-717', 'Cash', NULL, '2026-04-09 11:54:24'),
(384, 358, 'Accounts Receivable', 0.00, 3500.00, 'Payment received: Invoice INV-20260409135424-717', 'Cash', NULL, '2026-04-09 11:54:24'),
(385, 359, 'M-Pesa', 1000.00, 0.00, 'Payment received: Invoice INV-20260409135453-850', 'Cash', NULL, '2026-04-09 11:54:53'),
(386, 359, 'Accounts Receivable', 0.00, 1000.00, 'Payment received: Invoice INV-20260409135453-850', 'Cash', NULL, '2026-04-09 11:54:53'),
(387, 362, 'M-Pesa', 1700.00, 0.00, 'Payment received: Invoice INV-20260409152648-308', 'Cash', NULL, '2026-04-09 13:26:48'),
(388, 362, 'Accounts Receivable', 0.00, 1700.00, 'Payment received: Invoice INV-20260409152648-308', 'Cash', NULL, '2026-04-09 13:26:48'),
(389, 363, 'M-Pesa', 4950.00, 0.00, 'Payment received: Invoice INV-20260409170133-242', 'Cash', NULL, '2026-04-09 15:01:34'),
(390, 363, 'Accounts Receivable', 0.00, 4950.00, 'Payment received: Invoice INV-20260409170133-242', 'Cash', NULL, '2026-04-09 15:01:34'),
(391, 365, 'M-Pesa', 2700.00, 0.00, 'Payment received: Invoice INV-20260409191609-299', 'Cash', NULL, '2026-04-09 17:16:09'),
(392, 365, 'Accounts Receivable', 0.00, 2700.00, 'Payment received: Invoice INV-20260409191609-299', 'Cash', NULL, '2026-04-09 17:16:09'),
(393, 366, 'M-Pesa', 50.00, 0.00, 'Payment received: Invoice INV-20260409193518-821', 'Cash', NULL, '2026-04-09 17:35:18'),
(394, 366, 'Accounts Receivable', 0.00, 50.00, 'Payment received: Invoice INV-20260409193518-821', 'Cash', NULL, '2026-04-09 17:35:19'),
(395, 367, 'Bank', 500.00, 0.00, 'Payment received: Invoice INV-20260409193608-508', 'Cash', NULL, '2026-04-09 17:36:08'),
(396, 367, 'Accounts Receivable', 0.00, 500.00, 'Payment received: Invoice INV-20260409193608-508', 'Cash', NULL, '2026-04-09 17:36:08'),
(397, NULL, 'Pharmacy Sales', 20.00, 0.00, 'Invoice #368 (Mpesa)', 'Cash', NULL, '2026-04-10 08:59:37'),
(398, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #369 (Mpesa)', 'Cash', NULL, '2026-04-10 09:00:00'),
(399, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #370 (Mpesa)', 'Cash', NULL, '2026-04-10 09:00:38'),
(400, NULL, 'Pharmacy Sales', 250.00, 0.00, 'Invoice #371 (Mpesa)', 'Cash', NULL, '2026-04-10 09:01:16'),
(401, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #372 (Mpesa)', 'Cash', NULL, '2026-04-10 09:01:37'),
(402, NULL, 'Pharmacy Sales', 400.00, 0.00, 'Invoice #373 (Mpesa)', 'Cash', NULL, '2026-04-10 09:02:07'),
(403, NULL, 'Pharmacy Sales', 150.00, 0.00, 'Invoice #374 (Mpesa)', 'Cash', NULL, '2026-04-10 09:02:45'),
(404, 375, 'M-Pesa', 1000.00, 0.00, 'Payment received: Invoice INV-20260410114028-188', 'Cash', NULL, '2026-04-10 09:40:28'),
(405, 375, 'Accounts Receivable', 0.00, 1000.00, 'Payment received: Invoice INV-20260410114028-188', 'Cash', NULL, '2026-04-10 09:40:28'),
(406, 376, 'M-Pesa', 500.00, 0.00, 'Payment received: Invoice INV-20260410114401-399', 'Cash', NULL, '2026-04-10 09:44:01'),
(407, 376, 'Accounts Receivable', 0.00, 500.00, 'Payment received: Invoice INV-20260410114401-399', 'Cash', NULL, '2026-04-10 09:44:02'),
(408, 377, 'Cash', 200.00, 0.00, 'Payment received: Invoice INV-20260410120230-131', 'Cash', NULL, '2026-04-10 10:02:30'),
(409, 377, 'Accounts Receivable', 0.00, 200.00, 'Payment received: Invoice INV-20260410120230-131', 'Cash', NULL, '2026-04-10 10:02:30'),
(410, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #378 (Mpesa)', 'Cash', NULL, '2026-04-10 16:56:01'),
(411, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #379 (Mpesa)', 'Cash', NULL, '2026-04-10 17:49:49'),
(412, NULL, 'Pharmacy Sales', 400.00, 0.00, 'Invoice #380 (Mpesa)', 'Cash', NULL, '2026-04-10 17:50:28'),
(413, 381, 'M-Pesa', 800.00, 0.00, 'Payment received: Invoice INV-20260411155331-639', 'Cash', NULL, '2026-04-11 13:53:31'),
(414, 381, 'Accounts Receivable', 0.00, 800.00, 'Payment received: Invoice INV-20260411155331-639', 'Cash', NULL, '2026-04-11 13:53:31'),
(415, 382, 'M-Pesa', 400.00, 0.00, 'Payment received: Invoice INV-20260411155353-114', 'Cash', NULL, '2026-04-11 13:53:53'),
(416, 382, 'Accounts Receivable', 0.00, 400.00, 'Payment received: Invoice INV-20260411155353-114', 'Cash', NULL, '2026-04-11 13:53:53'),
(417, 383, 'M-Pesa', 400.00, 0.00, 'Payment received: Invoice INV-20260411155713-163', 'Cash', NULL, '2026-04-11 13:57:13');
INSERT INTO `accounting_entries` (`id`, `invoice_id`, `account`, `debit`, `credit`, `note`, `payment_method`, `reference_id`, `created_at`) VALUES
(418, 383, 'Accounts Receivable', 0.00, 400.00, 'Payment received: Invoice INV-20260411155713-163', 'Cash', NULL, '2026-04-11 13:57:13'),
(419, NULL, 'Pharmacy Sales', 50.00, 0.00, 'Invoice #384 (Mpesa)', 'Cash', NULL, '2026-04-11 13:58:56'),
(420, 385, 'Cash', 1500.00, 0.00, 'Payment received: Invoice INV-20260411184438-464', 'Cash', NULL, '2026-04-11 16:44:38'),
(421, 385, 'Accounts Receivable', 0.00, 1500.00, 'Payment received: Invoice INV-20260411184438-464', 'Cash', NULL, '2026-04-11 16:44:38'),
(422, NULL, 'Pharmacy Sales', 250.00, 0.00, 'Invoice #386 (Mpesa)', 'Cash', NULL, '2026-04-11 17:47:19'),
(423, 389, 'M-Pesa', 300.00, 0.00, 'Payment received: Invoice INV-20260412065542-260', 'Cash', NULL, '2026-04-12 04:55:42'),
(424, 389, 'Accounts Receivable', 0.00, 300.00, 'Payment received: Invoice INV-20260412065542-260', 'Cash', NULL, '2026-04-12 04:55:42'),
(425, NULL, 'Pharmacy Sales', 50.00, 0.00, 'Invoice #390 (Cash)', 'Cash', NULL, '2026-04-12 04:57:12'),
(426, NULL, 'Pharmacy Sales', 50.00, 0.00, 'Invoice #391 (Mpesa)', 'Cash', NULL, '2026-04-12 09:16:02'),
(427, NULL, 'Pharmacy Sales', 50.00, 0.00, 'Invoice #392 (Mpesa)', 'Cash', NULL, '2026-04-12 10:14:02'),
(428, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #393 (Cash)', 'Cash', NULL, '2026-04-12 14:24:20'),
(429, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #394 (Mpesa)', 'Cash', NULL, '2026-04-12 14:26:17'),
(430, NULL, 'Pharmacy Sales', 150.00, 0.00, 'Invoice #395 (Mpesa)', 'Cash', NULL, '2026-04-12 16:56:43'),
(431, NULL, 'Pharmacy Sales', 50.00, 0.00, 'Invoice #396 (Cash)', 'Cash', NULL, '2026-04-12 19:09:42'),
(432, 398, 'M-Pesa', 300.00, 0.00, 'Payment received: Invoice INV-20260413192344-511', 'Cash', NULL, '2026-04-13 17:23:44'),
(433, 398, 'Accounts Receivable', 0.00, 300.00, 'Payment received: Invoice INV-20260413192344-511', 'Cash', NULL, '2026-04-13 17:23:44'),
(434, 399, 'Cash', 2400.00, 0.00, 'Payment received: Invoice INV-20260413192431-314', 'Cash', NULL, '2026-04-13 17:24:31'),
(435, 399, 'Accounts Receivable', 0.00, 2400.00, 'Payment received: Invoice INV-20260413192431-314', 'Cash', NULL, '2026-04-13 17:24:31'),
(436, 400, 'M-Pesa', 2500.00, 0.00, 'Payment received: Invoice INV-20260413192456-219', 'Cash', NULL, '2026-04-13 17:24:56'),
(437, 400, 'Accounts Receivable', 0.00, 2500.00, 'Payment received: Invoice INV-20260413192456-219', 'Cash', NULL, '2026-04-13 17:24:56'),
(438, NULL, 'Pharmacy Sales', 250.00, 0.00, 'Invoice #401 (Cash)', 'Cash', NULL, '2026-04-13 18:15:13'),
(439, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #402 (Mpesa)', 'Cash', NULL, '2026-04-13 18:16:08'),
(440, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #403 (Cash)', 'Cash', NULL, '2026-04-14 08:56:53'),
(441, 404, 'Cash', 200.00, 0.00, 'Payment received: Invoice INV-20260414125740-623', 'Cash', NULL, '2026-04-14 10:57:40'),
(442, 404, 'Accounts Receivable', 0.00, 200.00, 'Payment received: Invoice INV-20260414125740-623', 'Cash', NULL, '2026-04-14 10:57:40'),
(443, NULL, 'Pharmacy Sales', 500.00, 0.00, 'Invoice #405 (Mpesa)', 'Cash', NULL, '2026-04-14 20:14:34'),
(444, 406, 'M-Pesa', 700.00, 0.00, 'Payment received: Invoice INV-20260415085311-196', 'Cash', NULL, '2026-04-15 06:53:12'),
(445, 406, 'Accounts Receivable', 0.00, 700.00, 'Payment received: Invoice INV-20260415085311-196', 'Cash', NULL, '2026-04-15 06:53:12'),
(446, NULL, 'Pharmacy Sales', 50.00, 0.00, 'Invoice #416 (Cash)', 'Cash', NULL, '2026-04-15 14:11:01'),
(447, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #417 (Mpesa)', 'Cash', NULL, '2026-04-16 14:13:06'),
(448, 421, 'M-Pesa', 800.00, 0.00, 'Payment received: Invoice INV-20260417170940-501', 'Cash', NULL, '2026-04-17 15:09:41'),
(449, 421, 'Accounts Receivable', 0.00, 800.00, 'Payment received: Invoice INV-20260417170940-501', 'Cash', NULL, '2026-04-17 15:09:41'),
(450, 422, 'Cash', 2950.00, 0.00, 'Payment received: Invoice INV-20260417174740-889', 'Cash', NULL, '2026-04-17 15:47:40'),
(451, 422, 'Accounts Receivable', 0.00, 2950.00, 'Payment received: Invoice INV-20260417174740-889', 'Cash', NULL, '2026-04-17 15:47:40'),
(452, NULL, 'Pharmacy Sales', 50.00, 0.00, 'Invoice #424 (Cash)', 'Cash', NULL, '2026-04-17 15:51:14'),
(453, 426, 'M-Pesa', 850.00, 0.00, 'Payment received: Invoice INV-20260418171346-322', 'Cash', NULL, '2026-04-18 15:13:46'),
(454, 426, 'Accounts Receivable', 0.00, 850.00, 'Payment received: Invoice INV-20260418171346-322', 'Cash', NULL, '2026-04-18 15:13:46'),
(455, 427, 'M-Pesa', 850.00, 0.00, 'Payment received: Invoice INV-20260420174223-309', 'Cash', NULL, '2026-04-20 15:42:23'),
(456, 427, 'Accounts Receivable', 0.00, 850.00, 'Payment received: Invoice INV-20260420174223-309', 'Cash', NULL, '2026-04-20 15:42:23'),
(457, 428, 'M-Pesa', 2500.00, 0.00, 'Payment received: Invoice INV-20260421174404-874', 'Cash', NULL, '2026-04-21 15:44:04'),
(458, 428, 'Accounts Receivable', 0.00, 2500.00, 'Payment received: Invoice INV-20260421174404-874', 'Cash', NULL, '2026-04-21 15:44:04'),
(459, 429, 'M-Pesa', 2450.00, 0.00, 'Payment received: Invoice INV-20260421174607-637', 'Cash', NULL, '2026-04-21 15:46:08'),
(460, 429, 'Accounts Receivable', 0.00, 2450.00, 'Payment received: Invoice INV-20260421174607-637', 'Cash', NULL, '2026-04-21 15:46:08'),
(461, 431, 'Cash', 1000.00, 0.00, 'Payment received: Invoice INV-20260423122552-497', 'Cash', NULL, '2026-04-23 10:25:52'),
(462, 431, 'Accounts Receivable', 0.00, 1000.00, 'Payment received: Invoice INV-20260423122552-497', 'Cash', NULL, '2026-04-23 10:25:52'),
(463, 433, 'Cash', 2000.00, 0.00, 'Payment received: Invoice INV-20260425192817-815', 'Cash', NULL, '2026-04-25 17:28:18'),
(464, 433, 'Accounts Receivable', 0.00, 2000.00, 'Payment received: Invoice INV-20260425192817-815', 'Cash', NULL, '2026-04-25 17:28:18'),
(465, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #434 (Cash)', 'Cash', NULL, '2026-04-25 17:29:22'),
(466, NULL, 'Pharmacy Sales', 20.00, 0.00, 'Invoice #435 (Cash)', 'Cash', NULL, '2026-04-25 17:29:44'),
(467, NULL, 'Pharmacy Sales', 500.00, 0.00, 'Invoice #436 (Mpesa)', 'Cash', NULL, '2026-04-25 17:30:13'),
(468, NULL, 'Pharmacy Sales', 500.00, 0.00, 'Invoice #437 (Mpesa)', 'Cash', NULL, '2026-04-25 17:30:43'),
(469, 438, 'M-Pesa', 200.00, 0.00, 'Payment received: Invoice INV-20260425202339-510', 'Cash', NULL, '2026-04-25 18:23:39'),
(470, 438, 'Accounts Receivable', 0.00, 200.00, 'Payment received: Invoice INV-20260425202339-510', 'Cash', NULL, '2026-04-25 18:23:39'),
(471, NULL, 'Pharmacy Inventory', 0.00, 1000.00, 'Stock Entry: norma saline 1 (Qty: 1)', 'Cash', 'STOCK-ADJ', '2026-04-26 13:30:23'),
(472, NULL, 'Pharmacy Inventory', 0.00, 100.00, 'Stock Entry: Ondasentron 1 (Qty: 1)', 'Cash', 'STOCK-ADJ', '2026-04-26 13:32:01'),
(473, NULL, 'Pharmacy Inventory', 0.00, 1400.00, 'Stock Entry: Esomeprazole 40mg ( inj) (Qty: 14)', 'Cash', 'STOCK-ADJ', '2026-04-26 13:34:24'),
(474, 440, 'Cash', 2800.00, 0.00, 'Payment received: Invoice INV-20260426171314-802', 'Cash', NULL, '2026-04-26 15:13:14'),
(475, 440, 'Accounts Receivable', 0.00, 2800.00, 'Payment received: Invoice INV-20260426171314-802', 'Cash', NULL, '2026-04-26 15:13:14'),
(476, 443, 'Cash', 3050.00, 0.00, 'Payment received: Invoice INV-20260428124400-983', 'Cash', NULL, '2026-04-28 10:44:00'),
(477, 443, 'Accounts Receivable', 0.00, 3050.00, 'Payment received: Invoice INV-20260428124400-983', 'Cash', NULL, '2026-04-28 10:44:00'),
(478, 445, 'M-Pesa', 1000.00, 0.00, 'Payment received: Invoice INV-20260429185458-784', 'Cash', NULL, '2026-04-29 16:54:58'),
(479, 445, 'Accounts Receivable', 0.00, 1000.00, 'Payment received: Invoice INV-20260429185458-784', 'Cash', NULL, '2026-04-29 16:54:58'),
(480, 446, 'Cash', 1900.00, 0.00, 'Payment received: Invoice INV-20260430081851-487', 'Cash', NULL, '2026-04-30 06:18:51'),
(481, 446, 'Accounts Receivable', 0.00, 1900.00, 'Payment received: Invoice INV-20260430081851-487', 'Cash', NULL, '2026-04-30 06:18:51'),
(482, NULL, 'Pharmacy Sales', 250.00, 0.00, 'Invoice #448 (Mpesa)', 'Cash', NULL, '2026-05-02 06:58:58'),
(483, 450, 'Cash', 2300.00, 0.00, 'Payment received: Invoice INV-20260502181406-486', 'Cash', NULL, '2026-05-02 16:14:06'),
(484, 450, 'Accounts Receivable', 0.00, 2300.00, 'Payment received: Invoice INV-20260502181406-486', 'Cash', NULL, '2026-05-02 16:14:06'),
(485, 451, 'Cash', 2300.00, 0.00, 'Payment received: Invoice INV-20260502181533-669', 'Cash', NULL, '2026-05-02 16:15:33'),
(486, 451, 'Accounts Receivable', 0.00, 2300.00, 'Payment received: Invoice INV-20260502181533-669', 'Cash', NULL, '2026-05-02 16:15:33'),
(487, 452, 'Cash', 2300.00, 0.00, 'Payment received: Invoice INV-20260502182621-163', 'Cash', NULL, '2026-05-02 16:26:21'),
(488, 452, 'Accounts Receivable', 0.00, 2300.00, 'Payment received: Invoice INV-20260502182621-163', 'Cash', NULL, '2026-05-02 16:26:21'),
(489, 455, 'M-Pesa', 1000.00, 0.00, 'Payment received: Invoice INV-20260503142939-323', 'Cash', NULL, '2026-05-03 12:29:39'),
(490, 455, 'Accounts Receivable', 0.00, 1000.00, 'Payment received: Invoice INV-20260503142939-323', 'Cash', NULL, '2026-05-03 12:29:39'),
(491, 457, 'Cash', 2850.00, 0.00, 'Payment received: Invoice INV-20260503152043-796', 'Cash', NULL, '2026-05-03 13:20:43'),
(492, 457, 'Accounts Receivable', 0.00, 2850.00, 'Payment received: Invoice INV-20260503152043-796', 'Cash', NULL, '2026-05-03 13:20:43'),
(493, 458, 'M-Pesa', 3300.00, 0.00, 'Payment received: Invoice INV-20260503173153-414', 'Cash', NULL, '2026-05-03 15:31:53'),
(494, 458, 'Accounts Receivable', 0.00, 3300.00, 'Payment received: Invoice INV-20260503173153-414', 'Cash', NULL, '2026-05-03 15:31:53'),
(495, 460, 'M-Pesa', 1500.00, 0.00, 'Payment received: Invoice INV-20260503190819-763', 'Cash', NULL, '2026-05-03 17:08:19'),
(496, 460, 'Accounts Receivable', 0.00, 1500.00, 'Payment received: Invoice INV-20260503190819-763', 'Cash', NULL, '2026-05-03 17:08:19'),
(497, 462, 'M-Pesa', 3300.00, 0.00, 'Payment received: Invoice INV-20260504163020-115', 'Cash', NULL, '2026-05-04 14:30:20'),
(498, 462, 'Accounts Receivable', 0.00, 3300.00, 'Payment received: Invoice INV-20260504163020-115', 'Cash', NULL, '2026-05-04 14:30:20'),
(499, 464, 'M-Pesa', 1800.00, 0.00, 'Payment received: Invoice INV-20260504163808-252', 'Cash', NULL, '2026-05-04 14:38:09'),
(500, 464, 'Accounts Receivable', 0.00, 1800.00, 'Payment received: Invoice INV-20260504163808-252', 'Cash', NULL, '2026-05-04 14:38:09'),
(501, 466, 'M-Pesa', 1000.00, 0.00, 'Payment received: Invoice INV-20260507202634-194', 'Cash', NULL, '2026-05-07 18:26:34'),
(502, 466, 'Accounts Receivable', 0.00, 1000.00, 'Payment received: Invoice INV-20260507202634-194', 'Cash', NULL, '2026-05-07 18:26:34'),
(503, 467, 'M-Pesa', 1000.00, 0.00, 'Payment received: Invoice INV-20260507203158-410', 'Cash', NULL, '2026-05-07 18:31:58'),
(504, 467, 'Accounts Receivable', 0.00, 1000.00, 'Payment received: Invoice INV-20260507203158-410', 'Cash', NULL, '2026-05-07 18:31:58'),
(505, 469, 'M-Pesa', 2300.00, 0.00, 'Payment received: Invoice INV-20260508103527-310', 'Cash', NULL, '2026-05-08 08:35:27'),
(506, 469, 'Accounts Receivable', 0.00, 2300.00, 'Payment received: Invoice INV-20260508103527-310', 'Cash', NULL, '2026-05-08 08:35:27'),
(507, 471, 'M-Pesa', 1800.00, 0.00, 'Payment received: Invoice INV-20260508190528-139', 'Cash', NULL, '2026-05-08 17:05:28'),
(508, 471, 'Accounts Receivable', 0.00, 1800.00, 'Payment received: Invoice INV-20260508190528-139', 'Cash', NULL, '2026-05-08 17:05:28'),
(509, 472, 'M-Pesa', 20000.00, 0.00, 'Payment received: Invoice INV-20260509091413-167', 'Cash', NULL, '2026-05-09 07:14:13'),
(510, 472, 'Accounts Receivable', 0.00, 20000.00, 'Payment received: Invoice INV-20260509091413-167', 'Cash', NULL, '2026-05-09 07:14:13'),
(511, 474, 'Cash', 800.00, 0.00, 'Payment received: Invoice INV-20260512165739-161', 'Cash', NULL, '2026-05-12 14:57:39'),
(512, 474, 'Accounts Receivable', 0.00, 800.00, 'Payment received: Invoice INV-20260512165739-161', 'Cash', NULL, '2026-05-12 14:57:39'),
(513, 476, 'Cash', 1300.00, 0.00, 'Payment received: Invoice INV-20260513102216-494', 'Cash', NULL, '2026-05-13 08:22:16'),
(514, 476, 'Accounts Receivable', 0.00, 1300.00, 'Payment received: Invoice INV-20260513102216-494', 'Cash', NULL, '2026-05-13 08:22:16'),
(515, 477, 'Cash', 1300.00, 0.00, 'Payment received: Invoice INV-20260513102217-878', 'Cash', NULL, '2026-05-13 08:22:17'),
(516, 477, 'Accounts Receivable', 0.00, 1300.00, 'Payment received: Invoice INV-20260513102217-878', 'Cash', NULL, '2026-05-13 08:22:18'),
(517, 478, 'Cash', 1300.00, 0.00, 'Payment received: Invoice INV-20260513102218-462', 'Cash', NULL, '2026-05-13 08:22:18'),
(518, 478, 'Accounts Receivable', 0.00, 1300.00, 'Payment received: Invoice INV-20260513102218-462', 'Cash', NULL, '2026-05-13 08:22:18'),
(519, 480, 'M-Pesa', 2000.00, 0.00, 'Payment received: Invoice INV-20260516103735-507', 'Cash', NULL, '2026-05-16 08:37:35'),
(520, 480, 'Accounts Receivable', 0.00, 2000.00, 'Payment received: Invoice INV-20260516103735-507', 'Cash', NULL, '2026-05-16 08:37:35'),
(521, 481, 'Cash', 900.00, 0.00, 'Payment received: Invoice INV-20260516150509-686', 'Cash', NULL, '2026-05-16 13:05:09'),
(522, 481, 'Accounts Receivable', 0.00, 900.00, 'Payment received: Invoice INV-20260516150509-686', 'Cash', NULL, '2026-05-16 13:05:09'),
(523, 482, 'Cash', 6100.00, 0.00, 'Payment received: Invoice INV-20260516150637-534', 'Cash', NULL, '2026-05-16 13:06:38'),
(524, 482, 'Accounts Receivable', 0.00, 6100.00, 'Payment received: Invoice INV-20260516150637-534', 'Cash', NULL, '2026-05-16 13:06:38'),
(525, 483, 'Cash', 6100.00, 0.00, 'Payment received: Invoice INV-20260516150638-867', 'Cash', NULL, '2026-05-16 13:06:38'),
(526, 483, 'Accounts Receivable', 0.00, 6100.00, 'Payment received: Invoice INV-20260516150638-867', 'Cash', NULL, '2026-05-16 13:06:38'),
(527, 486, 'M-Pesa', 1300.00, 0.00, 'Payment received: Invoice INV-20260524204353-318', 'Cash', NULL, '2026-05-24 18:43:53'),
(528, 486, 'Accounts Receivable', 0.00, 1300.00, 'Payment received: Invoice INV-20260524204353-318', 'Cash', NULL, '2026-05-24 18:43:53'),
(529, 488, 'M-Pesa', 1500.00, 0.00, 'Payment received: Invoice INV-20260529150731-692', 'Cash', NULL, '2026-05-29 13:07:31'),
(530, 488, 'Accounts Receivable', 0.00, 1500.00, 'Payment received: Invoice INV-20260529150731-692', 'Cash', NULL, '2026-05-29 13:07:31'),
(531, 490, 'M-Pesa', 4750.00, 0.00, 'Payment received: Invoice INV-20260530204119-831', 'Cash', NULL, '2026-05-30 18:41:19'),
(532, 490, 'Accounts Receivable', 0.00, 4750.00, 'Payment received: Invoice INV-20260530204119-831', 'Cash', NULL, '2026-05-30 18:41:19'),
(533, 492, 'Cash', 2000.00, 0.00, 'Payment received: Invoice INV-20260531120339-251', 'Cash', NULL, '2026-05-31 10:03:39'),
(534, 492, 'Accounts Receivable', 0.00, 2000.00, 'Payment received: Invoice INV-20260531120339-251', 'Cash', NULL, '2026-05-31 10:03:39'),
(535, 494, 'M-Pesa', 1350.00, 0.00, 'Payment received: Invoice INV-20260531205420-443', 'Cash', NULL, '2026-05-31 18:54:20'),
(536, 494, 'Accounts Receivable', 0.00, 1350.00, 'Payment received: Invoice INV-20260531205420-443', 'Cash', NULL, '2026-05-31 18:54:20'),
(537, 497, 'Cash', 1500.00, 0.00, 'Payment received: Invoice INV-20260605121229-909', 'Cash', NULL, '2026-06-05 10:12:29'),
(538, 497, 'Accounts Receivable', 0.00, 1500.00, 'Payment received: Invoice INV-20260605121229-909', 'Cash', NULL, '2026-06-05 10:12:29'),
(539, 500, 'Cash', 1950.00, 0.00, 'Payment received: Invoice INV-20260607160552-120', 'Cash', NULL, '2026-06-07 14:05:52'),
(540, 500, 'Accounts Receivable', 0.00, 1950.00, 'Payment received: Invoice INV-20260607160552-120', 'Cash', NULL, '2026-06-07 14:05:52'),
(541, 502, 'M-Pesa', 2950.00, 0.00, 'Payment received: Invoice INV-20260608173656-760', 'Cash', NULL, '2026-06-08 15:36:56'),
(542, 502, 'Accounts Receivable', 0.00, 2950.00, 'Payment received: Invoice INV-20260608173656-760', 'Cash', NULL, '2026-06-08 15:36:56'),
(543, 504, 'Cash', 8900.00, 0.00, 'Payment received: Invoice INV-20260608180953-267', 'Cash', NULL, '2026-06-08 16:09:53'),
(544, 504, 'Accounts Receivable', 0.00, 8900.00, 'Payment received: Invoice INV-20260608180953-267', 'Cash', NULL, '2026-06-08 16:09:53'),
(545, 506, 'M-Pesa', 3800.00, 0.00, 'Payment received: Invoice INV-20260609085252-894', 'Cash', NULL, '2026-06-09 06:52:53'),
(546, 506, 'Accounts Receivable', 0.00, 3800.00, 'Payment received: Invoice INV-20260609085252-894', 'Cash', NULL, '2026-06-09 06:52:53'),
(547, 508, 'M-Pesa', 5600.00, 0.00, 'Payment received: Invoice INV-20260610111223-513', 'Cash', NULL, '2026-06-10 09:12:24'),
(548, 508, 'Accounts Receivable', 0.00, 5600.00, 'Payment received: Invoice INV-20260610111223-513', 'Cash', NULL, '2026-06-10 09:12:24'),
(549, 511, 'Cash', 2850.00, 0.00, 'Payment received: Invoice INV-20260614073522-154', 'Cash', NULL, '2026-06-14 05:35:23'),
(550, 511, 'Accounts Receivable', 0.00, 2850.00, 'Payment received: Invoice INV-20260614073522-154', 'Cash', NULL, '2026-06-14 05:35:23'),
(551, NULL, 'Pharmacy Sales', 50.00, 0.00, 'Invoice #513 (Cash)', 'Cash', NULL, '2026-06-14 07:25:25'),
(552, 515, 'M-Pesa', 1000.00, 0.00, 'Payment received: Invoice INV-20260617061929-946', 'Cash', NULL, '2026-06-17 04:19:29'),
(553, 515, 'Accounts Receivable', 0.00, 1000.00, 'Payment received: Invoice INV-20260617061929-946', 'Cash', NULL, '2026-06-17 04:19:29'),
(554, 516, 'Cash', 3600.00, 0.00, 'Payment received: Invoice INV-20260618113211-729', 'Cash', NULL, '2026-06-18 09:32:12'),
(555, 516, 'Accounts Receivable', 0.00, 3600.00, 'Payment received: Invoice INV-20260618113211-729', 'Cash', NULL, '2026-06-18 09:32:12'),
(556, 518, 'M-Pesa', 2000.00, 0.00, 'Payment received: Invoice INV-20260618113800-526', 'Cash', NULL, '2026-06-18 09:38:00'),
(557, 518, 'Accounts Receivable', 0.00, 2000.00, 'Payment received: Invoice INV-20260618113800-526', 'Cash', NULL, '2026-06-18 09:38:00'),
(558, 519, 'Cash', 1400.00, 0.00, 'Payment received: Invoice INV-20260618113845-148', 'Cash', NULL, '2026-06-18 09:38:45'),
(559, 519, 'Accounts Receivable', 0.00, 1400.00, 'Payment received: Invoice INV-20260618113845-148', 'Cash', NULL, '2026-06-18 09:38:45'),
(560, NULL, 'Pharmacy Sales', 250.00, 0.00, 'Invoice #520 (Cash)', 'Cash', NULL, '2026-06-18 09:40:06'),
(561, 522, 'Cash', 2800.00, 0.00, 'Payment received: Invoice INV-20260620074815-985', 'Cash', NULL, '2026-06-20 05:48:16'),
(562, 522, 'Accounts Receivable', 0.00, 2800.00, 'Payment received: Invoice INV-20260620074815-985', 'Cash', NULL, '2026-06-20 05:48:16'),
(563, 524, 'M-Pesa', 1000.00, 0.00, 'Payment received: Invoice INV-20260620233547-837', 'Cash', NULL, '2026-06-20 21:35:48'),
(564, 524, 'Accounts Receivable', 0.00, 1000.00, 'Payment received: Invoice INV-20260620233547-837', 'Cash', NULL, '2026-06-20 21:35:48'),
(565, 526, 'Cash', 1500.00, 0.00, 'Payment received: Invoice INV-20260622173457-213', 'Cash', NULL, '2026-06-22 15:34:57'),
(566, 526, 'Accounts Receivable', 0.00, 1500.00, 'Payment received: Invoice INV-20260622173457-213', 'Cash', NULL, '2026-06-22 15:34:57'),
(567, 528, 'M-Pesa', 3000.00, 0.00, 'Payment received: Invoice INV-20260624220738-161', 'Cash', NULL, '2026-06-24 20:07:38'),
(568, 528, 'Accounts Receivable', 0.00, 3000.00, 'Payment received: Invoice INV-20260624220738-161', 'Cash', NULL, '2026-06-24 20:07:38'),
(569, 530, 'Cash', 200.00, 0.00, 'Payment received: Invoice INV-20260626193233-922', 'Cash', NULL, '2026-06-26 17:32:33'),
(570, 530, 'Accounts Receivable', 0.00, 200.00, 'Payment received: Invoice INV-20260626193233-922', 'Cash', NULL, '2026-06-26 17:32:33'),
(571, 531, 'Cash', 1350.00, 0.00, 'Payment received: Invoice INV-20260626193306-578', 'Cash', NULL, '2026-06-26 17:33:06'),
(572, 531, 'Accounts Receivable', 0.00, 1350.00, 'Payment received: Invoice INV-20260626193306-578', 'Cash', NULL, '2026-06-26 17:33:06'),
(573, 533, 'Cash', 4050.00, 0.00, 'Payment received: Invoice INV-20260627075116-723', 'Cash', NULL, '2026-06-27 05:51:16'),
(574, 533, 'Accounts Receivable', 0.00, 4050.00, 'Payment received: Invoice INV-20260627075116-723', 'Cash', NULL, '2026-06-27 05:51:16'),
(575, 535, 'M-Pesa', 500.00, 0.00, 'Payment received: Invoice INV-20260627150040-976', 'Cash', NULL, '2026-06-27 13:00:40'),
(576, 535, 'Accounts Receivable', 0.00, 500.00, 'Payment received: Invoice INV-20260627150040-976', 'Cash', NULL, '2026-06-27 13:00:40'),
(577, 536, 'Cash', 2300.00, 0.00, 'Payment received: Invoice INV-20260627150203-135', 'Cash', NULL, '2026-06-27 13:02:03'),
(578, 536, 'Accounts Receivable', 0.00, 2300.00, 'Payment received: Invoice INV-20260627150203-135', 'Cash', NULL, '2026-06-27 13:02:03'),
(579, NULL, 'Pharmacy Sales', 10.00, 0.00, 'Invoice #537 (Cash)', 'Cash', NULL, '2026-06-27 13:04:42'),
(580, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #538 (Mpesa)', 'Cash', NULL, '2026-06-28 20:12:55'),
(581, 540, 'Cash', 2350.00, 0.00, 'Payment received: Invoice INV-20260701082442-163', 'Cash', NULL, '2026-07-01 06:24:42'),
(582, 540, 'Accounts Receivable', 0.00, 2350.00, 'Payment received: Invoice INV-20260701082442-163', 'Cash', NULL, '2026-07-01 06:24:42'),
(583, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #541 (Cash)', 'Cash', NULL, '2026-07-02 17:48:28'),
(584, NULL, 'Pharmacy Sales', 100.00, 0.00, 'Invoice #542 (Mpesa)', 'Cash', NULL, '2026-07-02 19:09:58'),
(585, 544, 'M-Pesa', 3900.00, 0.00, 'Payment received: Invoice INV-20260703084001-404', 'Cash', NULL, '2026-07-03 06:40:01'),
(586, 544, 'Accounts Receivable', 0.00, 3900.00, 'Payment received: Invoice INV-20260703084001-404', 'Cash', NULL, '2026-07-03 06:40:02'),
(587, 546, 'M-Pesa', 1000.00, 0.00, 'Payment received: Invoice INV-20260704154156-625', 'Cash', NULL, '2026-07-04 13:41:56'),
(588, 546, 'Accounts Receivable', 0.00, 1000.00, 'Payment received: Invoice INV-20260704154156-625', 'Cash', NULL, '2026-07-04 13:41:56'),
(589, 547, 'M-Pesa', 1000.00, 0.00, 'Payment received: Invoice INV-20260704154157-960', 'Cash', NULL, '2026-07-04 13:41:57'),
(590, 547, 'Accounts Receivable', 0.00, 1000.00, 'Payment received: Invoice INV-20260704154157-960', 'Cash', NULL, '2026-07-04 13:41:57'),
(591, 549, 'M-Pesa', 5900.00, 0.00, 'Payment received: Invoice INV-20260705153313-764', 'Cash', NULL, '2026-07-05 13:33:13'),
(592, 549, 'Accounts Receivable', 0.00, 5900.00, 'Payment received: Invoice INV-20260705153313-764', 'Cash', NULL, '2026-07-05 13:33:13'),
(593, 550, 'M-Pesa', 5900.00, 0.00, 'Payment received: Invoice INV-20260705153314-173', 'Cash', NULL, '2026-07-05 13:33:15'),
(594, 550, 'Accounts Receivable', 0.00, 5900.00, 'Payment received: Invoice INV-20260705153314-173', 'Cash', NULL, '2026-07-05 13:33:15'),
(595, 551, 'M-Pesa', 5900.00, 0.00, 'Payment received: Invoice INV-20260705161603-699', 'Cash', NULL, '2026-07-05 14:16:03'),
(596, 551, 'Accounts Receivable', 0.00, 5900.00, 'Payment received: Invoice INV-20260705161603-699', 'Cash', NULL, '2026-07-05 14:16:03'),
(597, 553, 'M-Pesa', 850.00, 0.00, 'Payment received: Invoice INV-20260710091825-209', 'Cash', NULL, '2026-07-10 07:18:25'),
(598, 553, 'Accounts Receivable', 0.00, 850.00, 'Payment received: Invoice INV-20260710091825-209', 'Cash', NULL, '2026-07-10 07:18:26'),
(599, 554, 'M-Pesa', 850.00, 0.00, 'Payment received: Invoice INV-20260710091826-590', 'Cash', NULL, '2026-07-10 07:18:26'),
(600, 554, 'Accounts Receivable', 0.00, 850.00, 'Payment received: Invoice INV-20260710091826-590', 'Cash', NULL, '2026-07-10 07:18:27'),
(601, 555, 'M-Pesa', 850.00, 0.00, 'Payment received: Invoice INV-20260710091828-939', 'Cash', NULL, '2026-07-10 07:18:28'),
(602, 555, 'Accounts Receivable', 0.00, 850.00, 'Payment received: Invoice INV-20260710091828-939', 'Cash', NULL, '2026-07-10 07:18:28'),
(603, 556, 'M-Pesa', 850.00, 0.00, 'Payment received: Invoice INV-20260710091828-608', 'Cash', NULL, '2026-07-10 07:18:29'),
(604, 556, 'Accounts Receivable', 0.00, 850.00, 'Payment received: Invoice INV-20260710091828-608', 'Cash', NULL, '2026-07-10 07:18:29'),
(605, 557, 'M-Pesa', 850.00, 0.00, 'Payment received: Invoice INV-20260710091829-821', 'Cash', NULL, '2026-07-10 07:18:29'),
(606, 557, 'Accounts Receivable', 0.00, 850.00, 'Payment received: Invoice INV-20260710091829-821', 'Cash', NULL, '2026-07-10 07:18:29'),
(607, 560, 'M-Pesa', 4200.00, 0.00, 'Payment received: Invoice INV-20260712133850-974', 'Cash', NULL, '2026-07-12 11:38:51'),
(608, 560, 'Accounts Receivable', 0.00, 4200.00, 'Payment received: Invoice INV-20260712133850-974', 'Cash', NULL, '2026-07-12 11:38:51'),
(609, 561, 'Bank', 2000.00, 0.00, 'Payment received: Invoice INV-20260712134720-664', 'Cash', NULL, '2026-07-12 11:47:20'),
(610, 561, 'Accounts Receivable', 0.00, 2000.00, 'Payment received: Invoice INV-20260712134720-664', 'Cash', NULL, '2026-07-12 11:47:20'),
(611, 565, 'M-Pesa', 550.00, 0.00, 'Payment received: Invoice INV-20260718181746-925', 'Cash', NULL, '2026-07-18 16:17:47'),
(612, 565, 'Accounts Receivable', 0.00, 550.00, 'Payment received: Invoice INV-20260718181746-925', 'Cash', NULL, '2026-07-18 16:17:47'),
(613, NULL, 'Pharmacy Sales', 300.00, 0.00, 'Invoice #566 (Mpesa)', 'Cash', NULL, '2026-07-18 16:22:34'),
(614, 568, 'Cash', 800.00, 0.00, 'Payment received: Invoice INV-20260725211052-494', 'Cash', NULL, '2026-07-25 19:10:52'),
(615, 568, 'Accounts Receivable', 0.00, 800.00, 'Payment received: Invoice INV-20260725211052-494', 'Cash', NULL, '2026-07-25 19:10:52'),
(616, 569, 'Cash', 800.00, 0.00, 'Payment received: Invoice INV-20260725211053-749', 'Cash', NULL, '2026-07-25 19:10:53'),
(617, 569, 'Accounts Receivable', 0.00, 800.00, 'Payment received: Invoice INV-20260725211053-749', 'Cash', NULL, '2026-07-25 19:10:53'),
(618, 571, 'Cash', 200.00, 0.00, 'Payment received: Invoice INV-20260728193453-431', 'Cash', NULL, '2026-07-28 17:34:53'),
(619, 571, 'Accounts Receivable', 0.00, 200.00, 'Payment received: Invoice INV-20260728193453-431', 'Cash', NULL, '2026-07-28 17:34:53'),
(620, 573, 'M-Pesa', 1500.00, 0.00, 'Payment received: Invoice INV-20260730193856-182', 'Cash', NULL, '2026-07-30 17:38:57'),
(621, 573, 'Accounts Receivable', 0.00, 1500.00, 'Payment received: Invoice INV-20260730193856-182', 'Cash', NULL, '2026-07-30 17:38:57'),
(622, 576, 'M-Pesa', 900.00, 0.00, 'Payment received: Invoice INV-20260804113935-886', 'Cash', NULL, '2026-08-04 09:39:35'),
(623, 576, 'Accounts Receivable', 0.00, 900.00, 'Payment received: Invoice INV-20260804113935-886', 'Cash', NULL, '2026-08-04 09:39:35'),
(624, 578, 'M-Pesa', 3150.00, 0.00, 'Payment received: Invoice INV-20260805205504-442', 'Cash', NULL, '2026-08-05 18:55:05'),
(625, 578, 'Accounts Receivable', 0.00, 3150.00, 'Payment received: Invoice INV-20260805205504-442', 'Cash', NULL, '2026-08-05 18:55:05'),
(626, 580, 'Cash', 3500.00, 0.00, 'Payment received: Invoice INV-20260805214132-396', 'Cash', NULL, '2026-08-05 19:41:32'),
(627, 580, 'Accounts Receivable', 0.00, 3500.00, 'Payment received: Invoice INV-20260805214132-396', 'Cash', NULL, '2026-08-05 19:41:32'),
(628, NULL, 'Pharmacy Inventory', 0.00, 2500.00, 'Stock Entry: canula (Qty: 100)', 'Cash', 'STOCK-ADJ', '2026-08-06 07:49:06'),
(629, NULL, 'Pharmacy Inventory', 0.00, 25.00, 'Stock Entry: giving set (Qty: 1)', 'Cash', 'STOCK-ADJ', '2026-08-06 07:50:01'),
(630, 584, 'Cash', 1000.00, 0.00, 'Payment received: Invoice INV-20260807063305-380', 'Cash', NULL, '2026-08-07 04:33:05'),
(631, 584, 'Accounts Receivable', 0.00, 1000.00, 'Payment received: Invoice INV-20260807063305-380', 'Cash', NULL, '2026-08-07 04:33:05'),
(632, 585, 'Cash', 2000.00, 0.00, 'Payment received: Invoice INV-20260807063549-163', 'Cash', NULL, '2026-08-07 04:35:49'),
(633, 585, 'Accounts Receivable', 0.00, 2000.00, 'Payment received: Invoice INV-20260807063549-163', 'Cash', NULL, '2026-08-07 04:35:49'),
(634, 587, 'M-Pesa', 800.00, 0.00, 'Payment received: Invoice INV-20260808222250-904', 'Cash', NULL, '2026-08-08 20:22:50'),
(635, 587, 'Accounts Receivable', 0.00, 800.00, 'Payment received: Invoice INV-20260808222250-904', 'Cash', NULL, '2026-08-08 20:22:50'),
(636, 591, 'M-Pesa', 3100.00, 0.00, 'Payment received: Invoice INV-20260814075206-871', 'Cash', NULL, '2026-08-14 05:52:06'),
(637, 591, 'Accounts Receivable', 0.00, 3100.00, 'Payment received: Invoice INV-20260814075206-871', 'Cash', NULL, '2026-08-14 05:52:06'),
(638, 592, 'M-Pesa', 3100.00, 0.00, 'Payment received: Invoice INV-20260814075206-243', 'Cash', NULL, '2026-08-14 05:52:06'),
(639, 592, 'Accounts Receivable', 0.00, 3100.00, 'Payment received: Invoice INV-20260814075206-243', 'Cash', NULL, '2026-08-14 05:52:06'),
(640, 594, 'Bank', 850.00, 0.00, 'Payment received: Invoice INV-20260814120741-988', 'Cash', NULL, '2026-08-14 10:07:41'),
(641, 594, 'Accounts Receivable', 0.00, 850.00, 'Payment received: Invoice INV-20260814120741-988', 'Cash', NULL, '2026-08-14 10:07:42'),
(642, 596, 'M-Pesa', 400.00, 0.00, 'Payment received: Invoice INV-20260814145406-649', 'Cash', NULL, '2026-08-14 12:54:06'),
(643, 596, 'Accounts Receivable', 0.00, 400.00, 'Payment received: Invoice INV-20260814145406-649', 'Cash', NULL, '2026-08-14 12:54:06'),
(644, 597, 'Cash', 1950.00, 0.00, 'Payment received: Invoice INV-20260814175456-193', 'Cash', NULL, '2026-08-14 15:54:56'),
(645, 597, 'Accounts Receivable', 0.00, 1950.00, 'Payment received: Invoice INV-20260814175456-193', 'Cash', NULL, '2026-08-14 15:54:56'),
(646, 599, 'M-Pesa', 2500.00, 0.00, 'Payment received: Invoice INV-20260819093017-345', 'Cash', NULL, '2026-08-19 07:30:17'),
(647, 599, 'Accounts Receivable', 0.00, 2500.00, 'Payment received: Invoice INV-20260819093017-345', 'Cash', NULL, '2026-08-19 07:30:17'),
(648, 601, 'M-Pesa', 800.00, 0.00, 'Payment received: Invoice INV-20260820162517-856', 'Cash', NULL, '2026-08-20 14:25:17'),
(649, 601, 'Accounts Receivable', 0.00, 800.00, 'Payment received: Invoice INV-20260820162517-856', 'Cash', NULL, '2026-08-20 14:25:17'),
(650, 603, 'M-Pesa', 1000.00, 0.00, 'Payment received: Invoice INV-20260821181732-956', 'Cash', NULL, '2026-08-21 16:17:32'),
(651, 603, 'Accounts Receivable', 0.00, 1000.00, 'Payment received: Invoice INV-20260821181732-956', 'Cash', NULL, '2026-08-21 16:17:32'),
(652, NULL, 'Pharmacy Sales', 20.00, 0.00, 'Invoice #604 (Cash)', 'Cash', NULL, '2026-08-21 16:22:37'),
(653, 606, 'Cash', 600.00, 0.00, 'Payment received: Invoice INV-20260821220222-139', 'Cash', NULL, '2026-08-21 20:02:22'),
(654, 606, 'Accounts Receivable', 0.00, 600.00, 'Payment received: Invoice INV-20260821220222-139', 'Cash', NULL, '2026-08-21 20:02:22'),
(655, 608, 'M-Pesa', 4000.00, 0.00, 'Payment received: Invoice INV-20260822165631-383', 'Cash', NULL, '2026-08-22 14:56:32'),
(656, 608, 'Accounts Receivable', 0.00, 4000.00, 'Payment received: Invoice INV-20260822165631-383', 'Cash', NULL, '2026-08-22 14:56:32'),
(657, 609, 'M-Pesa', 4000.00, 0.00, 'Payment received: Invoice INV-20260822165633-994', 'Cash', NULL, '2026-08-22 14:56:33'),
(658, 609, 'Accounts Receivable', 0.00, 4000.00, 'Payment received: Invoice INV-20260822165633-994', 'Cash', NULL, '2026-08-22 14:56:33'),
(659, 612, 'Cash', 500.00, 0.00, 'Payment received: Invoice INV-20260825074254-968', 'Cash', NULL, '2026-08-25 05:42:54'),
(660, 612, 'Accounts Receivable', 0.00, 500.00, 'Payment received: Invoice INV-20260825074254-968', 'Cash', NULL, '2026-08-25 05:42:54'),
(661, 614, 'M-Pesa', 9500.00, 0.00, 'Payment received: Invoice INV-20260826075549-605', 'Cash', NULL, '2026-08-26 05:55:49'),
(662, 614, 'Accounts Receivable', 0.00, 9500.00, 'Payment received: Invoice INV-20260826075549-605', 'Cash', NULL, '2026-08-26 05:55:49'),
(663, 617, 'M-Pesa', 4000.00, 0.00, 'Payment received: Invoice INV-20260826160735-995', 'Cash', NULL, '2026-08-26 14:07:36'),
(664, 617, 'Accounts Receivable', 0.00, 4000.00, 'Payment received: Invoice INV-20260826160735-995', 'Cash', NULL, '2026-08-26 14:07:36'),
(665, 619, 'M-Pesa', 1200.00, 0.00, 'Payment received: Invoice INV-20260826162244-521', 'Cash', NULL, '2026-08-26 14:22:45'),
(666, 619, 'Accounts Receivable', 0.00, 1200.00, 'Payment received: Invoice INV-20260826162244-521', 'Cash', NULL, '2026-08-26 14:22:45'),
(667, 621, 'M-Pesa', 700.00, 0.00, 'Payment received: Invoice INV-20260826185238-187', 'Cash', NULL, '2026-08-26 16:52:39'),
(668, 621, 'Accounts Receivable', 0.00, 700.00, 'Payment received: Invoice INV-20260826185238-187', 'Cash', NULL, '2026-08-26 16:52:39'),
(669, 622, 'M-Pesa', 1500.00, 0.00, 'Payment received: Invoice INV-20260827070806-774', 'Cash', NULL, '2026-08-27 05:08:07'),
(670, 622, 'Accounts Receivable', 0.00, 1500.00, 'Payment received: Invoice INV-20260827070806-774', 'Cash', NULL, '2026-08-27 05:08:07'),
(671, 624, 'M-Pesa', 400.00, 0.00, 'Payment received: Invoice INV-20260827093258-240', 'Cash', NULL, '2026-08-27 07:32:58'),
(672, 624, 'Accounts Receivable', 0.00, 400.00, 'Payment received: Invoice INV-20260827093258-240', 'Cash', NULL, '2026-08-27 07:32:58'),
(673, NULL, 'Pharmacy Inventory', 0.00, 1000.00, 'Stock Entry: depo,vit B (Qty: 10)', 'Cash', 'STOCK-ADJ', '2026-08-27 07:36:15'),
(674, NULL, 'Procurement Expense', 1000000.00, 0.00, 'PO #12 linked expense #7', 'Cash', NULL, '2026-08-27 08:07:49'),
(675, NULL, 'Procurement Expense', 1000000.00, 0.00, 'Stock In: 10000 x immunizatinon (PO #12, Supplier Invoice inv203)', 'Cash', NULL, '2026-08-27 08:21:04'),
(676, NULL, 'Pharmacy Sales', 200.00, 0.00, 'Invoice #627 (Mpesa)', 'Cash', NULL, '2026-08-27 08:21:39');

-- --------------------------------------------------------

--
-- Table structure for table `admissions`
--

CREATE TABLE `admissions` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `ward_name` varchar(100) NOT NULL,
  `bed_number` int(11) NOT NULL,
  `admitted_by` int(11) DEFAULT NULL,
  `admit_date` timestamp NULL DEFAULT current_timestamp(),
  `ward` varchar(100) DEFAULT NULL,
  `bed` varchar(50) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `discharged_at` timestamp NULL DEFAULT NULL,
  `discharge_summary` text DEFAULT NULL,
  `status` enum('Admitted','Discharged','Transferred') DEFAULT 'Admitted'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `anc_visits`
--

CREATE TABLE `anc_visits` (
  `id` int(11) NOT NULL,
  `encounter_id` int(11) DEFAULT NULL,
  `gestation_weeks` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `appointment_date` date DEFAULT NULL,
  `appointment_time` time DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('waiting','seen','cancelled') DEFAULT 'waiting',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `reason`, `status`, `created_at`) VALUES
(1, 1, 2, '2025-12-23', '13:29:00', 'Sick', 'waiting', '2025-12-23 10:29:21'),
(2, 1, 3, '2025-12-23', '13:29:00', 'sick', 'waiting', '2025-12-23 10:29:53'),
(3, 1, 5, '2025-12-23', '13:32:00', 'sick', 'waiting', '2025-12-23 10:32:53'),
(4, 2, 2, '2026-01-15', '17:52:00', '', 'waiting', '2026-01-15 14:52:32'),
(5, 22, 0, '2026-02-26', '17:15:38', 'Clinical Service: General', '', '2026-02-26 16:15:38'),
(6, 23, 6, '2026-02-26', '17:45:44', 'Clinical Service: ANC', '', '2026-02-26 16:45:44'),
(7, 24, 5, '2026-02-26', '18:21:03', 'Clinical Service: Immunization', '', '2026-02-26 17:21:03'),
(8, 25, 2, '2026-02-26', '18:29:49', 'Clinical Service: OPD', '', '2026-02-26 17:29:49'),
(9, 26, 0, '2026-02-26', '18:36:23', 'Clinical Service: Maternity', '', '2026-02-26 17:36:23'),
(10, 27, 5, '2026-02-26', '18:37:42', 'Clinical Service: ANC', '', '2026-02-26 17:37:42'),
(11, 28, 6, '2026-02-27', '06:57:39', 'Clinical Service: General', '', '2026-02-27 05:57:39'),
(12, 29, 6, '2026-02-27', '16:51:16', 'Clinical Service: General', '', '2026-02-27 15:51:16'),
(13, 30, 6, '2026-02-27', '20:04:45', 'Clinical Service: General', '', '2026-02-27 19:04:45'),
(14, 31, 6, '2026-02-28', '16:58:09', 'Clinical Service: Maternity', '', '2026-02-28 15:58:09'),
(15, 32, 6, '2026-02-28', '17:14:43', 'Clinical Service: Immunization', '', '2026-02-28 16:14:43'),
(16, 33, 0, '2026-02-28', '17:48:24', 'Clinical Service: General', '', '2026-02-28 16:48:24'),
(17, 34, 6, '2026-02-28', '18:14:29', 'Clinical Service: General', '', '2026-02-28 17:14:29'),
(18, 35, 6, '2026-02-28', '18:22:09', 'Clinical Service: General', '', '2026-02-28 17:22:09'),
(19, 36, 6, '2026-03-01', '16:52:10', 'Clinical Service: General', '', '2026-03-01 15:52:10'),
(20, 37, 6, '2026-03-02', '09:31:50', 'Clinical Service: General', '', '2026-03-02 08:31:50'),
(21, 38, 0, '2026-03-02', '12:06:54', 'Clinical Service: General', '', '2026-03-02 11:06:54'),
(22, 39, 6, '2026-03-02', '19:13:49', 'Clinical Service: General', '', '2026-03-02 18:13:49'),
(23, 40, 0, '2026-03-03', '07:35:05', 'Clinical Service: General', '', '2026-03-03 06:35:05'),
(24, 41, 0, '2026-03-03', '07:35:39', 'Clinical Service: General', '', '2026-03-03 06:35:39'),
(25, 42, 6, '2026-03-03', '11:54:33', 'Clinical Service: General', '', '2026-03-03 10:54:33'),
(26, 43, 6, '2026-03-03', '11:54:34', 'Clinical Service: General', '', '2026-03-03 10:54:34'),
(27, 44, 6, '2026-03-03', '18:38:08', 'Clinical Service: General', '', '2026-03-03 17:38:08'),
(28, 45, 6, '2026-03-04', '11:17:07', 'Clinical Service: General', '', '2026-03-04 10:17:07'),
(29, 46, 6, '2026-03-04', '14:36:14', 'Clinical Service: General', '', '2026-03-04 13:36:14'),
(30, 47, 6, '2026-03-05', '06:33:32', 'Clinical Service: General', '', '2026-03-05 05:33:32'),
(31, 48, 6, '2026-03-05', '12:20:33', 'Clinical Service: General', '', '2026-03-05 11:20:33'),
(32, 49, 6, '2026-03-05', '12:46:49', 'Clinical Service: General', '', '2026-03-05 11:46:49'),
(33, 50, 6, '2026-03-05', '13:56:09', 'Clinical Service: General', '', '2026-03-05 12:56:09'),
(34, 51, 6, '2026-03-07', '11:03:49', 'Clinical Service: General', '', '2026-03-07 10:03:49'),
(35, 52, 6, '2026-03-07', '16:54:12', 'Clinical Service: General', '', '2026-03-07 15:54:12'),
(36, 53, 6, '2026-03-08', '06:54:10', 'Clinical Service: General', '', '2026-03-08 05:54:10'),
(37, 54, 6, '2026-03-08', '12:12:03', 'Clinical Service: General', '', '2026-03-08 11:12:03'),
(38, 55, 6, '2026-03-08', '14:30:20', 'Clinical Service: General', '', '2026-03-08 13:30:20'),
(39, 56, 6, '2026-03-08', '19:11:54', 'Clinical Service: General', '', '2026-03-08 18:11:55'),
(40, 57, 6, '2026-03-08', '21:06:35', 'Clinical Service: General', '', '2026-03-08 20:06:35'),
(41, 58, 6, '2026-03-09', '02:13:39', 'Clinical Service: General', '', '2026-03-09 01:13:39'),
(42, 59, 6, '2026-03-09', '09:02:28', 'Clinical Service: General', '', '2026-03-09 08:02:28'),
(43, 60, 6, '2026-03-10', '19:16:27', 'Clinical Service: General', '', '2026-03-10 18:16:27'),
(44, 61, 6, '2026-03-11', '08:39:10', 'Clinical Service: General', '', '2026-03-11 07:39:10'),
(45, 62, 6, '2026-03-12', '07:24:45', 'Clinical Service: General', '', '2026-03-12 06:24:45'),
(46, 63, 6, '2026-03-12', '09:45:05', 'Clinical Service: General', '', '2026-03-12 08:45:05'),
(47, 64, 6, '2026-03-12', '13:38:04', 'Clinical Service: General', '', '2026-03-12 12:38:04'),
(48, 65, 6, '2026-03-12', '13:56:24', 'Clinical Service: General', '', '2026-03-12 12:56:24'),
(49, 66, 0, '2026-03-12', '16:31:15', 'Clinical Service: General', '', '2026-03-12 15:31:15'),
(50, 67, 6, '2026-03-13', '09:22:12', 'Clinical Service: General', '', '2026-03-13 08:22:12'),
(51, 68, 0, '2026-03-13', '14:53:10', 'Clinical Service: General', '', '2026-03-13 13:53:10'),
(52, 69, 0, '2026-03-13', '15:30:05', 'Clinical Service: General', '', '2026-03-13 14:30:05'),
(53, 70, 0, '2026-03-13', '16:39:27', 'Clinical Service: General', '', '2026-03-13 15:39:27'),
(54, 71, 6, '2026-03-13', '18:31:32', 'Clinical Service: General', '', '2026-03-13 17:31:32'),
(55, 72, 6, '2026-03-13', '20:18:50', 'Clinical Service: General', '', '2026-03-13 19:18:50'),
(56, 73, 0, '2026-03-14', '12:26:30', 'Clinical Service: General', '', '2026-03-14 11:26:30'),
(57, 74, 6, '2026-03-18', '11:07:53', 'Clinical Service: General', '', '2026-03-18 10:07:53'),
(58, 75, 6, '2026-03-18', '14:56:19', 'Clinical Service: General', '', '2026-03-18 13:56:19'),
(59, 76, 6, '2026-03-18', '16:31:00', 'Clinical Service: General', '', '2026-03-18 15:31:00'),
(60, 77, 6, '2026-03-18', '17:04:56', 'Clinical Service: General', '', '2026-03-18 16:04:56'),
(61, 78, 6, '2026-03-18', '18:49:32', 'Clinical Service: General', '', '2026-03-18 17:49:32'),
(62, 79, 6, '2026-03-19', '02:00:13', 'Clinical Service: General', '', '2026-03-19 01:00:13'),
(63, 80, 6, '2026-03-19', '07:08:39', 'Clinical Service: General', '', '2026-03-19 06:08:39'),
(64, 81, 7, '2026-03-22', '06:58:13', 'Clinical Service: General', '', '2026-03-22 05:58:13'),
(65, 82, 7, '2026-03-22', '18:32:13', 'Clinical Service: General', '', '2026-03-22 17:32:13'),
(66, 83, 7, '2026-03-23', '13:36:46', 'Clinical Service: OPD', '', '2026-03-23 12:36:46'),
(67, 84, 6, '2026-03-24', '06:48:45', 'Clinical Service: General', '', '2026-03-24 05:48:45'),
(68, 85, 6, '2026-03-24', '07:33:00', 'Clinical Service: General', '', '2026-03-24 06:33:00'),
(69, 86, 6, '2026-03-24', '09:24:54', 'Clinical Service: General', '', '2026-03-24 08:24:54'),
(70, 87, 7, '2026-03-24', '17:17:33', 'Clinical Service: General', '', '2026-03-24 16:17:33'),
(71, 88, 6, '2026-03-25', '18:18:43', 'Clinical Service: General', '', '2026-03-25 17:18:43'),
(72, 89, 6, '2026-03-26', '11:37:46', 'Clinical Service: General', '', '2026-03-26 10:37:46'),
(73, 90, 6, '2026-03-26', '12:20:37', 'Clinical Service: General', '', '2026-03-26 11:20:37'),
(74, 91, 6, '2026-03-27', '02:56:39', 'Clinical Service: General', '', '2026-03-27 01:56:39'),
(75, 92, 6, '2026-03-27', '02:56:39', 'Clinical Service: General', '', '2026-03-27 01:56:39'),
(76, 93, 6, '2026-03-27', '02:56:39', 'Clinical Service: General', '', '2026-03-27 01:56:39'),
(77, 94, 6, '2026-03-27', '02:56:40', 'Clinical Service: General', '', '2026-03-27 01:56:40'),
(78, 95, 6, '2026-03-27', '02:56:40', 'Clinical Service: General', '', '2026-03-27 01:56:40'),
(79, 96, 6, '2026-03-27', '02:56:40', 'Clinical Service: General', '', '2026-03-27 01:56:40'),
(80, 97, 6, '2026-03-27', '02:56:45', 'Clinical Service: General', '', '2026-03-27 01:56:45'),
(81, 98, 7, '2026-03-28', '16:42:32', 'Clinical Service: General', '', '2026-03-28 15:42:32'),
(82, 99, 6, '2026-03-29', '00:34:24', 'Clinical Service: General', '', '2026-03-28 23:34:24'),
(83, 100, 6, '2026-03-29', '17:40:22', 'Clinical Service: General', '', '2026-03-29 15:40:22'),
(84, 101, 6, '2026-03-31', '23:44:03', 'Clinical Service: General', '', '2026-03-31 21:44:03'),
(85, 102, 6, '2026-04-01', '06:50:14', 'Clinical Service: General', '', '2026-04-01 04:50:14'),
(86, 103, 6, '2026-04-01', '07:01:45', 'Clinical Service: General', '', '2026-04-01 05:01:45'),
(87, 104, 6, '2026-04-01', '18:10:19', 'Clinical Service: General', '', '2026-04-01 16:10:19'),
(88, 105, 6, '2026-04-01', '20:05:05', 'Clinical Service: General', '', '2026-04-01 18:05:05'),
(89, 106, 6, '2026-04-03', '19:43:30', 'Clinical Service: General', '', '2026-04-03 17:43:30'),
(90, 107, 6, '2026-04-04', '09:30:08', 'Clinical Service: General', '', '2026-04-04 07:30:08'),
(91, 108, 6, '2026-04-04', '19:38:18', 'Clinical Service: General', '', '2026-04-04 17:38:18'),
(92, 109, 6, '2026-04-04', '21:03:55', 'Clinical Service: General', '', '2026-04-04 19:03:55'),
(93, 110, 6, '2026-04-04', '22:08:11', 'Clinical Service: General', '', '2026-04-04 20:08:11'),
(94, 111, 6, '2026-04-05', '16:44:15', 'Clinical Service: General', '', '2026-04-05 14:44:15'),
(95, 112, 6, '2026-04-06', '11:14:21', 'Clinical Service: General', '', '2026-04-06 09:14:21'),
(96, 113, 6, '2026-04-06', '15:55:51', 'Clinical Service: General', '', '2026-04-06 13:55:51'),
(97, 114, 6, '2026-04-06', '17:12:59', 'Clinical Service: Maternity', '', '2026-04-06 15:12:59'),
(98, 115, 6, '2026-04-06', '17:15:02', 'Clinical Service: Maternity', '', '2026-04-06 15:15:02'),
(99, 116, 0, '2026-04-07', '13:11:53', 'Clinical Service: General', '', '2026-04-07 11:11:53'),
(100, 117, 0, '2026-04-07', '19:59:04', 'Clinical Service: General', '', '2026-04-07 17:59:04'),
(107, 124, 6, '2026-04-08', '18:20:46', 'Clinical Service: General', '', '2026-04-08 16:20:46'),
(108, 125, 7, '2026-04-09', '11:52:12', 'Clinical Service: OPD', '', '2026-04-09 09:52:12'),
(109, 126, 6, '2026-04-09', '13:09:50', 'Clinical Service: General', '', '2026-04-09 11:09:50'),
(110, 127, 6, '2026-04-09', '14:10:26', 'Clinical Service: General', '', '2026-04-09 12:10:26'),
(111, 128, 6, '2026-04-09', '14:22:41', 'Clinical Service: General', '', '2026-04-09 12:22:41'),
(112, 129, 6, '2026-04-10', '15:45:03', 'Clinical Service: General', '', '2026-04-10 13:45:03'),
(120, 137, 7, '2026-04-12', '18:51:17', 'Clinical Service: ANC', '', '2026-04-12 16:51:17'),
(121, 138, 6, '2026-04-13', '09:41:46', 'Clinical Service: General', '', '2026-04-13 07:41:46'),
(122, 139, 6, '2026-04-13', '16:11:56', 'Clinical Service: General', '', '2026-04-13 14:11:56'),
(123, 140, 6, '2026-04-16', '18:20:56', 'Clinical Service: General', '', '2026-04-16 16:20:56'),
(124, 141, 6, '2026-04-17', '15:23:42', 'Clinical Service: General', '', '2026-04-17 13:23:42'),
(125, 142, 6, '2026-04-17', '17:00:52', 'Clinical Service: General', '', '2026-04-17 15:00:52'),
(126, 143, 0, '2026-04-18', '14:52:22', 'Clinical Service: General', '', '2026-04-18 12:52:22'),
(127, 144, 7, '2026-04-20', '14:55:12', 'Clinical Service: ANC', '', '2026-04-20 12:55:12'),
(128, 145, 6, '2026-04-23', '12:20:11', 'Clinical Service: General', '', '2026-04-23 10:20:11'),
(129, 146, 6, '2026-04-24', '10:58:05', 'Clinical Service: General', '', '2026-04-24 08:58:05'),
(130, 147, 6, '2026-04-25', '20:16:22', 'Clinical Service: General', '', '2026-04-25 18:16:22'),
(131, 148, 6, '2026-04-26', '14:52:33', 'Clinical Service: General', '', '2026-04-26 12:52:33'),
(132, 149, 6, '2026-04-26', '14:52:33', 'Clinical Service: General', '', '2026-04-26 12:52:33'),
(133, 150, 6, '2026-04-28', '12:26:10', 'Clinical Service: General', '', '2026-04-28 10:26:10'),
(134, 151, 6, '2026-04-28', '12:41:14', 'Clinical Service: General', '', '2026-04-28 10:41:14'),
(135, 152, 6, '2026-04-29', '18:51:05', 'Clinical Service: General', '', '2026-04-29 16:51:05'),
(136, 153, 7, '2026-04-30', '09:10:38', 'Clinical Service: General', '', '2026-04-30 07:10:38'),
(137, 154, 6, '2026-05-02', '17:57:20', 'Clinical Service: General', '', '2026-05-02 15:57:20'),
(138, 155, 6, '2026-05-03', '13:37:24', 'Clinical Service: General', '', '2026-05-03 11:37:24'),
(139, 156, 6, '2026-05-03', '14:06:36', 'Clinical Service: General', '', '2026-05-03 12:06:36'),
(140, 157, 6, '2026-05-03', '14:32:14', 'Clinical Service: General', '', '2026-05-03 12:32:14'),
(141, 158, 6, '2026-05-03', '17:34:19', 'Clinical Service: General', '', '2026-05-03 15:34:19'),
(142, 159, 6, '2026-05-04', '16:25:28', 'Clinical Service: General', '', '2026-05-04 14:25:28'),
(143, 160, 6, '2026-05-04', '16:36:00', 'Clinical Service: General', '', '2026-05-04 14:36:00'),
(144, 161, 6, '2026-05-07', '19:52:51', 'Clinical Service: General', '', '2026-05-07 17:52:51'),
(145, 162, 7, '2026-05-08', '09:19:11', 'Clinical Service: General', '', '2026-05-08 07:19:11'),
(146, 163, 6, '2026-05-08', '18:12:17', 'Clinical Service: General', '', '2026-05-08 16:12:17'),
(147, 164, 6, '2026-05-12', '16:41:07', 'Clinical Service: General', '', '2026-05-12 14:41:07'),
(148, 165, 6, '2026-05-12', '16:41:08', 'Clinical Service: General', '', '2026-05-12 14:41:08'),
(149, 166, 6, '2026-05-12', '16:41:09', 'Clinical Service: General', '', '2026-05-12 14:41:09'),
(150, 167, 6, '2026-05-13', '08:30:46', 'Clinical Service: General', '', '2026-05-13 06:30:46'),
(151, 168, 6, '2026-05-16', '10:23:23', 'Clinical Service: General', '', '2026-05-16 08:23:23'),
(152, 169, 6, '2026-05-16', '10:25:41', 'Clinical Service: Maternity', '', '2026-05-16 08:25:41'),
(153, 170, 0, '2026-05-21', '21:44:39', 'Clinical Service: General', '', '2026-05-21 19:44:39'),
(154, 171, 7, '2026-05-24', '20:06:02', 'Clinical Service: General', '', '2026-05-24 18:06:02'),
(155, 172, 6, '2026-05-29', '15:03:05', 'Clinical Service: General', '', '2026-05-29 13:03:05'),
(156, 173, 6, '2026-05-30', '17:12:45', 'Clinical Service: General', '', '2026-05-30 15:12:45'),
(157, 174, 6, '2026-05-30', '17:12:45', 'Clinical Service: General', '', '2026-05-30 15:12:45'),
(158, 175, 6, '2026-05-30', '17:12:45', 'Clinical Service: General', '', '2026-05-30 15:12:45'),
(159, 176, 6, '2026-05-30', '17:12:45', 'Clinical Service: General', '', '2026-05-30 15:12:45'),
(160, 177, 6, '2026-05-30', '17:12:45', 'Clinical Service: General', '', '2026-05-30 15:12:45'),
(161, 178, 6, '2026-05-30', '17:12:45', 'Clinical Service: General', '', '2026-05-30 15:12:45'),
(162, 179, 5, '2026-05-31', '11:08:35', 'Clinical Service: General', '', '2026-05-31 09:08:35'),
(163, 180, 7, '2026-05-31', '20:34:49', 'Clinical Service: General', '', '2026-05-31 18:34:49'),
(164, 181, 6, '2026-06-01', '11:14:11', 'Clinical Service: General', '', '2026-06-01 09:14:11'),
(165, 182, 6, '2026-06-05', '12:09:16', 'Clinical Service: General', '', '2026-06-05 10:09:16'),
(166, 183, 6, '2026-06-07', '11:59:18', 'Clinical Service: General', '', '2026-06-07 09:59:18'),
(167, 184, 6, '2026-06-07', '14:02:17', 'Clinical Service: General', '', '2026-06-07 12:02:17'),
(168, 185, 6, '2026-06-08', '16:23:48', 'Clinical Service: Maternity', '', '2026-06-08 14:23:48'),
(169, 186, 7, '2026-06-08', '16:50:46', 'Clinical Service: General', '', '2026-06-08 14:50:46'),
(170, 187, 0, '2026-06-09', '08:20:49', 'Clinical Service: General', '', '2026-06-09 06:20:49'),
(171, 188, 6, '2026-06-09', '08:22:00', 'Clinical Service: General', '', '2026-06-09 06:22:00'),
(172, 189, 6, '2026-06-09', '19:14:36', 'Clinical Service: General', '', '2026-06-09 17:14:36'),
(173, 190, 6, '2026-06-10', '09:34:18', 'Clinical Service: General', '', '2026-06-10 07:34:18'),
(174, 191, 6, '2026-06-12', '17:07:06', 'Clinical Service: General', '', '2026-06-12 15:07:06'),
(175, 192, 6, '2026-06-12', '19:23:51', 'Clinical Service: General', '', '2026-06-12 17:23:51'),
(176, 193, 7, '2026-06-14', '07:29:10', 'Clinical Service: General', '', '2026-06-14 05:29:10'),
(177, 194, 7, '2026-06-14', '08:13:24', 'Clinical Service: Maternity', '', '2026-06-14 06:13:24'),
(178, 195, 7, '2026-06-16', '22:14:41', 'Clinical Service: General', '', '2026-06-16 20:14:41'),
(179, 196, 7, '2026-06-18', '11:34:13', 'Clinical Service: General', '', '2026-06-18 09:34:13'),
(180, 197, 6, '2026-06-20', '07:13:27', 'Clinical Service: General', '', '2026-06-20 05:13:27'),
(181, 198, 6, '2026-06-20', '22:29:35', 'Clinical Service: General', '', '2026-06-20 20:29:35'),
(182, 199, 6, '2026-06-22', '17:25:22', 'Clinical Service: General', '', '2026-06-22 15:25:22'),
(183, 200, 6, '2026-06-24', '20:15:26', 'Clinical Service: General', '', '2026-06-24 18:15:26'),
(184, 201, 6, '2026-06-26', '19:04:55', 'Clinical Service: General', '', '2026-06-26 17:04:55'),
(185, 202, 6, '2026-06-26', '19:04:56', 'Clinical Service: General', '', '2026-06-26 17:04:56'),
(186, 203, 6, '2026-06-27', '07:43:44', 'Clinical Service: General', '', '2026-06-27 05:43:44'),
(187, 204, 0, '2026-06-27', '14:04:03', 'Clinical Service: General', '', '2026-06-27 12:04:03'),
(188, 205, 6, '2026-07-01', '08:22:32', 'Clinical Service: General', '', '2026-07-01 06:22:32'),
(189, 206, 6, '2026-07-03', '08:20:02', 'Clinical Service: General', '', '2026-07-03 06:20:02'),
(190, 207, 6, '2026-07-04', '15:07:07', 'Clinical Service: General', '', '2026-07-04 13:07:07'),
(191, 208, 6, '2026-07-05', '11:20:44', 'Clinical Service: General', '', '2026-07-05 09:20:44'),
(192, 209, 6, '2026-07-10', '09:11:59', 'Clinical Service: General', '', '2026-07-10 07:11:59'),
(193, 210, 6, '2026-07-10', '09:12:02', 'Clinical Service: General', '', '2026-07-10 07:12:02'),
(194, 211, 6, '2026-07-10', '09:12:02', 'Clinical Service: General', '', '2026-07-10 07:12:02'),
(195, 212, 6, '2026-07-10', '09:12:03', 'Clinical Service: General', '', '2026-07-10 07:12:03'),
(196, 213, 6, '2026-07-10', '09:12:03', 'Clinical Service: General', '', '2026-07-10 07:12:03'),
(197, 214, 6, '2026-07-10', '09:12:03', 'Clinical Service: General', '', '2026-07-10 07:12:03'),
(198, 215, 6, '2026-07-12', '12:12:05', 'Clinical Service: General', '', '2026-07-12 10:12:05'),
(199, 216, 6, '2026-07-12', '12:45:10', 'Clinical Service: General', '', '2026-07-12 10:45:10'),
(200, 217, 6, '2026-07-12', '14:04:39', 'Clinical Service: General', '', '2026-07-12 12:04:39'),
(201, 218, 6, '2026-07-18', '10:05:41', 'Clinical Service: General', '', '2026-07-18 08:05:41'),
(202, 219, 6, '2026-07-18', '17:44:03', 'Clinical Service: General', '', '2026-07-18 15:44:03'),
(203, 220, 6, '2026-07-25', '20:18:22', 'Clinical Service: General', '', '2026-07-25 18:18:22'),
(204, 221, 0, '2026-07-27', '18:05:16', 'Clinical Service: General', '', '2026-07-27 16:05:16'),
(205, 222, 6, '2026-07-28', '19:34:14', 'Clinical Service: General', '', '2026-07-28 17:34:14'),
(206, 79, 6, '2026-03-07', '18:12:00', '', '', '2026-07-30 15:13:13'),
(207, 223, 6, '2026-07-30', '17:14:26', 'Clinical Service: General', '', '2026-07-30 15:14:26'),
(208, 224, 6, '2026-08-01', '14:16:18', 'Clinical Service: General', '', '2026-08-01 12:16:18'),
(209, 225, 6, '2026-08-04', '06:55:10', 'Clinical Service: General', '', '2026-08-04 04:55:11'),
(210, 226, 6, '2026-08-05', '20:24:55', 'Clinical Service: General', '', '2026-08-05 18:24:56'),
(211, 227, 6, '2026-08-05', '21:22:09', 'Clinical Service: General', '', '2026-08-05 19:22:09'),
(212, 228, 6, '2026-08-06', '10:55:14', 'Clinical Service: General', '', '2026-08-06 08:55:14'),
(213, 229, 7, '2026-08-06', '18:35:33', 'Clinical Service: Emergency', '', '2026-08-06 16:35:33'),
(214, 230, 0, '2026-08-07', '05:23:53', 'Clinical Service: General', '', '2026-08-07 03:23:53'),
(215, 231, 6, '2026-08-08', '21:24:45', 'Clinical Service: General', '', '2026-08-08 19:24:45'),
(216, 232, 6, '2026-08-11', '19:33:54', 'Clinical Service: General', '', '2026-08-11 17:33:54'),
(217, 233, 6, '2026-08-11', '19:36:25', 'Clinical Service: General', '', '2026-08-11 17:36:25'),
(218, 234, 6, '2026-08-12', '15:12:01', 'Clinical Service: General', '', '2026-08-12 13:12:01'),
(219, 235, 6, '2026-08-13', '15:32:41', 'Clinical Service: General', '', '2026-08-13 13:32:41'),
(220, 236, 6, '2026-08-14', '07:26:22', 'Clinical Service: General', '', '2026-08-14 05:26:22'),
(221, 237, 6, '2026-08-14', '11:47:58', 'Clinical Service: General', '', '2026-08-14 09:47:58'),
(222, 238, 6, '2026-08-14', '14:44:11', 'Clinical Service: General', '', '2026-08-14 12:44:11'),
(223, 239, 6, '2026-08-15', '11:58:21', 'Clinical Service: General', '', '2026-08-15 09:58:21'),
(224, 240, 0, '2026-08-16', '19:03:28', 'Clinical Service: General', '', '2026-08-16 17:03:28'),
(225, 241, 6, '2026-08-19', '08:45:23', 'Clinical Service: General', '', '2026-08-19 06:45:23'),
(226, 242, 6, '2026-08-19', '16:20:33', 'Clinical Service: General', '', '2026-08-19 14:20:33'),
(227, 243, 6, '2026-08-21', '12:35:34', 'Clinical Service: General', '', '2026-08-21 10:35:34'),
(228, 244, 6, '2026-08-21', '20:13:06', 'Clinical Service: General', '', '2026-08-21 18:13:06'),
(229, 245, 6, '2026-08-22', '11:59:55', 'Clinical Service: General', '', '2026-08-22 09:59:55'),
(230, 246, 6, '2026-08-23', '09:23:21', 'Clinical Service: General', '', '2026-08-23 07:23:21'),
(231, 247, 6, '2026-08-25', '06:47:43', 'Clinical Service: General', '', '2026-08-25 04:47:43'),
(232, 248, 6, '2026-08-25', '06:49:38', 'Clinical Service: Maternity', '', '2026-08-25 04:49:38'),
(233, 249, 6, '2026-08-25', '07:19:36', 'Clinical Service: General', '', '2026-08-25 05:19:36'),
(234, 250, 6, '2026-08-26', '13:05:34', 'Clinical Service: General', '', '2026-08-26 11:05:34'),
(235, 251, 6, '2026-08-26', '13:32:12', 'Clinical Service: General', '', '2026-08-26 11:32:12'),
(236, 252, 6, '2026-08-26', '16:19:31', 'Clinical Service: General', '', '2026-08-26 14:19:31'),
(237, 253, 6, '2026-08-26', '18:43:14', 'Clinical Service: General', '', '2026-08-26 16:43:14'),
(238, 254, 6, '2026-08-27', '09:32:37', 'Clinical Service: General', '', '2026-08-27 07:32:38'),
(239, 255, 6, '2026-08-27', '10:39:57', 'Clinical Service: General', '', '2026-08-27 08:39:57');

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `user_id`, `action`, `details`, `created_at`) VALUES
(1, 1, 'maternity_create', 'maternity_id=1,patient_id=3', '2025-12-09 11:02:50'),
(2, 1, 'maternity_baby', 'maternity_id=1,baby=1', '2025-12-09 11:19:01'),
(3, 1, 'maternity_delivery', 'maternity_id=1,mode=Normal', '2025-12-09 11:19:38'),
(4, 1, 'patient_register', 'patient_id=7', '2025-12-10 11:13:24'),
(5, 1, 'patient_register', 'patient_id=8', '2025-12-17 09:24:17'),
(6, 1, 'patient_register', 'patient_id=9', '2025-12-19 10:10:49'),
(7, 1, 'maternity_create', 'maternity_id=2,patient_id=9', '2025-12-19 10:12:05'),
(8, 1, 'maternity_visit', 'maternity_id=2,type=ANC', '2025-12-19 10:16:07'),
(9, 1, 'maternity_visit', 'maternity_id=2,type=ANC', '2025-12-19 11:14:05'),
(10, 1, 'maternity_baby', 'maternity_id=2,baby=1', '2025-12-19 11:14:21'),
(11, 1, 'maternity_delivery', 'maternity_id=2,mode=Normal', '2025-12-19 11:14:33'),
(12, 1, 'patient_register', 'patient_id=1', '2025-12-23 09:27:43'),
(13, 1, 'patient_register', 'patient_id=2', '2026-01-11 10:23:13'),
(14, 1, 'patient_register', 'patient_id=10', '2026-02-24 14:30:53'),
(15, 1, 'patient_register', 'patient_id=15', '2026-02-25 12:17:51'),
(16, 1, 'patient_register', 'patient_id=19', '2026-02-25 12:48:43'),
(17, 6, 'patient_register', 'patient_id=20', '2026-02-26 06:38:43'),
(18, 6, 'patient_register', 'patient_id=21', '2026-02-26 08:33:16'),
(19, 6, 'patient_register', 'patient_id=22', '2026-02-26 16:15:38'),
(20, 6, 'patient_register', 'patient_id=23', '2026-02-26 16:45:45'),
(21, 6, 'patient_register', 'patient_id=24', '2026-02-26 17:21:03'),
(22, 6, 'patient_register', 'patient_id=25', '2026-02-26 17:29:50'),
(23, 6, 'patient_register', 'patient_id=26', '2026-02-26 17:36:23'),
(24, 6, 'patient_register', 'patient_id=27', '2026-02-26 17:37:43'),
(25, 6, 'patient_register', 'patient_id=28', '2026-02-27 05:57:39'),
(26, 6, 'patient_register', 'patient_id=29', '2026-02-27 15:51:16'),
(27, 6, 'patient_register', 'patient_id=30', '2026-02-27 19:04:45'),
(28, 6, 'patient_register', 'patient_id=31', '2026-02-28 15:58:09'),
(29, 6, 'patient_register', 'patient_id=32', '2026-02-28 16:14:44'),
(30, 6, 'patient_register', 'patient_id=33', '2026-02-28 16:48:24'),
(31, 6, 'patient_register', 'patient_id=34', '2026-02-28 17:14:29'),
(32, 6, 'patient_register', 'patient_id=35', '2026-02-28 17:22:09'),
(33, 6, 'patient_register', 'patient_id=36', '2026-03-01 15:52:10'),
(34, 6, 'patient_register', 'patient_id=37', '2026-03-02 08:31:50'),
(35, 6, 'patient_register', 'patient_id=38', '2026-03-02 11:06:54'),
(36, 8, 'patient_register', 'patient_id=39', '2026-03-02 18:13:49'),
(37, 8, 'patient_register', 'patient_id=40', '2026-03-03 06:35:05'),
(38, 8, 'patient_register', 'patient_id=41', '2026-03-03 06:35:39'),
(39, 8, 'patient_register', 'patient_id=42', '2026-03-03 10:54:33'),
(40, 8, 'patient_register', 'patient_id=43', '2026-03-03 10:54:34'),
(41, 6, 'patient_register', 'patient_id=44', '2026-03-03 17:38:08'),
(42, 6, 'patient_register', 'patient_id=45', '2026-03-04 10:17:07'),
(43, 6, 'patient_register', 'patient_id=46', '2026-03-04 13:36:14'),
(44, 6, 'patient_register', 'patient_id=47', '2026-03-05 05:33:32'),
(45, 6, 'patient_register', 'patient_id=48', '2026-03-05 11:20:33'),
(46, 6, 'patient_register', 'patient_id=49', '2026-03-05 11:46:49'),
(47, 6, 'patient_register', 'patient_id=50', '2026-03-05 12:56:09');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `module` varchar(100) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `baby_details`
--

CREATE TABLE `baby_details` (
  `id` int(11) NOT NULL,
  `delivery_id` int(11) NOT NULL,
  `baby_gender` varchar(10) DEFAULT NULL,
  `baby_weight` varchar(20) DEFAULT NULL,
  `condition_at_birth` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `billing`
--

CREATE TABLE `billing` (
  `id` int(11) NOT NULL,
  `patient_number` varchar(20) DEFAULT NULL,
  `service` varchar(150) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'UNPAID',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `patient_id` int(11) NOT NULL,
  `walkin_id` int(11) DEFAULT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `method` varchar(50) DEFAULT NULL,
  `paid` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `billing`
--

INSERT INTO `billing` (`id`, `patient_number`, `service`, `amount`, `status`, `created_at`, `patient_id`, `walkin_id`, `invoice_id`, `paid_amount`, `method`, `paid`) VALUES
(1, NULL, NULL, 6000.00, 'UNPAID', '2026-02-24 13:05:41', 8, NULL, NULL, 0.00, 'Mpesa', 1),
(2, NULL, NULL, 6000.00, 'UNPAID', '2026-02-24 13:23:34', 8, NULL, NULL, 0.00, 'Mpesa', 1),
(3, NULL, NULL, 1000.00, 'UNPAID', '2026-02-24 14:10:52', 9, NULL, NULL, 0.00, 'Mpesa', 1),
(4, NULL, NULL, 1000.00, 'UNPAID', '2026-02-24 14:15:29', 9, NULL, NULL, 0.00, 'Mpesa', 1),
(5, NULL, NULL, 100.00, 'UNPAID', '2026-02-24 14:25:04', 9, NULL, NULL, 0.00, 'Mpesa', 1),
(6, NULL, NULL, 300.00, 'UNPAID', '2026-02-24 14:36:40', 10, NULL, NULL, 0.00, 'Cash', 1),
(7, NULL, NULL, 200.00, 'UNPAID', '2026-02-25 12:52:39', 17, NULL, NULL, 0.00, 'Cash', 1),
(8, NULL, NULL, 200.00, 'UNPAID', '2026-02-25 13:04:58', 16, NULL, NULL, 0.00, 'Mpesa', 1),
(9, NULL, NULL, 6725.00, 'UNPAID', '2026-02-26 07:03:34', 20, NULL, NULL, 0.00, 'Cash', 1),
(10, NULL, NULL, 6725.00, 'UNPAID', '2026-02-26 08:00:17', 20, NULL, NULL, 0.00, 'Cash', 1),
(11, NULL, NULL, 6725.00, 'UNPAID', '2026-02-26 08:02:44', 20, NULL, NULL, 0.00, 'Cash', 1),
(12, NULL, NULL, 6725.00, 'UNPAID', '2026-02-26 08:16:10', 20, NULL, NULL, 0.00, 'Cash', 1),
(13, NULL, NULL, 1000.00, 'UNPAID', '2026-02-26 08:30:35', 19, NULL, NULL, 0.00, 'Mpesa', 1),
(14, NULL, NULL, 6725.00, 'UNPAID', '2026-02-26 08:36:32', 21, NULL, NULL, 0.00, 'Cash', 1),
(15, NULL, NULL, 1000.00, 'UNPAID', '2026-02-26 17:25:28', 24, NULL, NULL, 0.00, 'Mpesa', 1),
(16, NULL, NULL, 1500.00, 'UNPAID', '2026-02-27 07:19:22', 28, NULL, NULL, 0.00, 'Mpesa', 1),
(17, NULL, NULL, 100.00, 'UNPAID', '2026-02-27 15:54:55', 29, NULL, NULL, 0.00, 'Cash', 1),
(18, NULL, NULL, 2200.00, 'UNPAID', '2026-02-27 17:20:41', 28, NULL, NULL, 0.00, 'Mpesa', 1),
(19, NULL, NULL, 500.00, 'UNPAID', '2026-02-27 19:08:50', 30, NULL, NULL, 0.00, 'Mpesa', 1),
(20, NULL, NULL, 150.00, 'UNPAID', '2026-02-27 19:13:39', 30, NULL, NULL, 0.00, 'Mpesa', 1),
(21, NULL, NULL, 3000.00, 'UNPAID', '2026-02-28 16:23:26', 31, NULL, NULL, 0.00, 'Cash', 1),
(22, NULL, NULL, 7000.00, 'UNPAID', '2026-02-28 16:52:26', 33, NULL, NULL, 0.00, 'Cash', 1),
(23, NULL, NULL, 4700.00, 'UNPAID', '2026-02-28 16:53:12', 33, NULL, NULL, 0.00, 'Mpesa', 1),
(24, NULL, NULL, 359.00, 'UNPAID', '2026-02-28 17:23:08', 32, NULL, NULL, 0.00, 'Mpesa', 1),
(25, NULL, NULL, 5200.00, 'UNPAID', '2026-02-28 17:24:15', 35, NULL, NULL, 0.00, 'Mpesa', 1),
(26, NULL, NULL, 1500.00, 'UNPAID', '2026-03-01 16:18:34', 36, NULL, NULL, 0.00, 'Mpesa', 1),
(27, NULL, NULL, 5000.00, 'UNPAID', '2026-03-02 08:14:28', 31, NULL, NULL, 0.00, 'Mpesa', 1),
(28, NULL, NULL, 200.00, 'UNPAID', '2026-03-02 09:27:42', 36, NULL, NULL, 0.00, 'Mpesa', 1),
(29, NULL, NULL, 2000.00, 'UNPAID', '2026-03-02 18:48:18', 39, NULL, NULL, 0.00, 'Mpesa', 1),
(30, NULL, NULL, 3000.00, 'UNPAID', '2026-03-03 12:30:49', 42, NULL, NULL, 0.00, 'Mpesa', 1),
(31, NULL, NULL, 300.00, 'UNPAID', '2026-03-03 14:16:11', 36, NULL, NULL, 0.00, 'Mpesa', 1),
(32, NULL, NULL, 300.00, 'UNPAID', '2026-03-03 18:04:44', 44, NULL, NULL, 0.00, 'Mpesa', 1),
(33, NULL, NULL, 1900.00, 'UNPAID', '2026-03-04 08:21:38', 42, NULL, NULL, 0.00, 'Mpesa', 1),
(34, NULL, NULL, 11100.00, 'UNPAID', '2026-03-04 08:57:08', 37, NULL, NULL, 0.00, 'Mpesa', 1),
(35, NULL, NULL, 1700.00, 'UNPAID', '2026-03-04 10:42:08', 45, NULL, NULL, 0.00, 'Mpesa', 1),
(36, NULL, NULL, 850.00, 'UNPAID', '2026-03-04 13:51:36', 46, NULL, NULL, 0.00, 'Mpesa', 1),
(37, NULL, NULL, 3500.00, 'UNPAID', '2026-03-05 07:27:24', 47, NULL, NULL, 0.00, 'Mpesa', 1),
(38, NULL, NULL, 3150.00, 'UNPAID', '2026-03-05 08:39:55', 34, NULL, NULL, 0.00, 'Mpesa', 1),
(39, NULL, NULL, 250.00, 'UNPAID', '2026-03-05 08:41:00', 28, NULL, NULL, 0.00, 'Cash', 1),
(40, NULL, NULL, 250.00, 'UNPAID', '2026-03-05 08:41:06', 28, NULL, NULL, 0.00, 'Cash', 1),
(41, NULL, NULL, 20.00, 'UNPAID', '2026-03-05 08:43:04', 27, NULL, NULL, 0.00, 'Mpesa', 1),
(42, NULL, NULL, 20.00, 'UNPAID', '2026-03-05 08:43:11', 27, NULL, NULL, 0.00, 'Mpesa', 1),
(43, NULL, NULL, 2250.00, 'UNPAID', '2026-03-05 12:50:32', 49, NULL, NULL, 0.00, 'Mpesa', 1),
(44, NULL, NULL, 2300.00, 'UNPAID', '2026-03-05 12:52:16', 48, NULL, NULL, 0.00, 'Mpesa', 1),
(45, NULL, NULL, 1000.00, 'UNPAID', '2026-03-05 13:07:17', 50, NULL, NULL, 0.00, 'Mpesa', 1),
(46, NULL, NULL, 2200.00, 'UNPAID', '2026-03-05 13:07:36', 50, NULL, NULL, 0.00, 'Cash', 1),
(50, NULL, NULL, 3000.00, 'UNPAID', '2026-03-07 11:39:06', 51, NULL, NULL, 0.00, 'Cash', 1),
(51, NULL, NULL, 1000.00, 'UNPAID', '2026-03-07 16:16:09', 52, NULL, NULL, 0.00, 'Cash', 1),
(53, NULL, NULL, 16700.00, 'UNPAID', '2026-03-08 07:35:38', 53, NULL, NULL, 0.00, 'Mpesa', 1),
(54, NULL, NULL, 2300.00, 'UNPAID', '2026-03-08 13:07:22', 54, NULL, NULL, 0.00, 'Cash', 1),
(55, NULL, NULL, 960.00, 'UNPAID', '2026-03-08 13:28:35', 51, NULL, NULL, 0.00, 'Cash', 1),
(58, NULL, NULL, 750.00, 'UNPAID', '2026-03-08 20:05:28', 56, NULL, NULL, 0.00, 'Cash', 1),
(59, NULL, NULL, 550.00, 'UNPAID', '2026-03-08 20:08:21', 57, NULL, NULL, 0.00, 'Cash', 1),
(60, NULL, NULL, 1100.00, 'UNPAID', '2026-03-09 01:54:16', 58, NULL, NULL, 0.00, 'Mpesa', 1),
(61, NULL, NULL, 2500.00, 'UNPAID', '2026-03-10 08:48:59', 55, NULL, NULL, 0.00, 'Cash', 1),
(62, NULL, NULL, 4800.00, 'UNPAID', '2026-03-10 13:53:02', 55, NULL, NULL, 0.00, 'Cash', 1),
(63, NULL, NULL, 900.00, 'UNPAID', '2026-03-10 14:13:36', 58, NULL, NULL, 0.00, 'Mpesa', 1),
(64, NULL, NULL, 200.00, 'UNPAID', '2026-03-10 14:21:50', 24, NULL, NULL, 0.00, 'Cash', 1),
(65, NULL, NULL, 700.00, 'UNPAID', '2026-03-10 14:22:52', 25, NULL, NULL, 0.00, 'Cash', 1),
(66, NULL, NULL, 200.00, 'UNPAID', '2026-03-10 14:23:28', 26, NULL, NULL, 0.00, 'Cash', 1),
(67, NULL, NULL, 160.00, 'UNPAID', '2026-03-10 14:24:31', 27, NULL, NULL, 0.00, 'Mpesa', 1),
(68, NULL, NULL, 200.00, 'UNPAID', '2026-03-10 14:25:20', 29, NULL, NULL, 0.00, 'Cash', 1),
(69, NULL, NULL, 200.00, 'UNPAID', '2026-03-10 14:25:55', 31, NULL, NULL, 0.00, 'Mpesa', 1),
(70, NULL, NULL, 200.00, 'UNPAID', '2026-03-10 14:26:07', 31, NULL, NULL, 0.00, 'Cash', 1),
(71, NULL, NULL, 800.00, 'UNPAID', '2026-03-10 14:26:23', 31, NULL, NULL, 0.00, 'Cash', 1),
(72, NULL, NULL, 200.00, 'UNPAID', '2026-03-10 14:27:39', 38, NULL, NULL, 0.00, 'Cash', 1),
(73, NULL, NULL, 200.00, 'UNPAID', '2026-03-10 14:28:35', 43, NULL, NULL, 0.00, 'Cash', 1),
(74, NULL, NULL, 200.00, 'UNPAID', '2026-03-10 14:29:05', 44, NULL, NULL, 0.00, 'Cash', 1),
(75, NULL, NULL, 200.00, 'UNPAID', '2026-03-10 14:29:37', 45, NULL, NULL, 0.00, 'Cash', 1),
(76, NULL, NULL, 2100.00, 'UNPAID', '2026-03-10 14:30:49', 52, NULL, NULL, 0.00, 'Cash', 1),
(77, NULL, NULL, 2100.00, 'UNPAID', '2026-03-10 14:31:23', 52, NULL, NULL, 0.00, 'Cash', 1),
(78, NULL, NULL, 200.00, 'UNPAID', '2026-03-10 14:31:52', 56, NULL, NULL, 0.00, 'Cash', 1),
(79, NULL, NULL, 200.00, 'UNPAID', '2026-03-10 14:32:04', 57, NULL, NULL, 0.00, 'Cash', 1),
(80, NULL, NULL, 200.00, 'UNPAID', '2026-03-10 14:32:16', 57, NULL, NULL, 0.00, 'Cash', 1),
(81, NULL, NULL, 200.00, 'UNPAID', '2026-03-10 17:51:59', 54, NULL, NULL, 0.00, 'Cash', 1),
(82, NULL, NULL, 1000.00, 'UNPAID', '2026-03-10 18:43:54', 60, NULL, NULL, 0.00, 'Cash', 1),
(83, NULL, NULL, 3950.00, 'UNPAID', '2026-03-11 09:30:42', 61, NULL, NULL, 0.00, 'Mpesa', 1),
(84, NULL, NULL, 5000.00, 'UNPAID', '2026-03-12 10:24:48', 63, NULL, NULL, 0.00, 'Cash', 1),
(85, NULL, NULL, 625.00, 'UNPAID', '2026-03-12 12:50:52', 64, NULL, NULL, 0.00, 'Cash', 1),
(86, NULL, NULL, 1500.00, 'UNPAID', '2026-03-12 13:00:25', 65, NULL, NULL, 0.00, 'Mpesa', 1),
(87, NULL, NULL, 700.00, 'UNPAID', '2026-03-13 08:22:35', 67, NULL, NULL, 0.00, 'Cash', 1),
(88, NULL, NULL, 200.00, 'UNPAID', '2026-03-13 14:30:33', 67, NULL, NULL, 0.00, 'Cash', 1),
(89, NULL, NULL, 1850.00, 'UNPAID', '2026-03-13 17:00:49', 70, NULL, NULL, 0.00, 'Mpesa', 1),
(90, NULL, NULL, 2300.00, 'UNPAID', '2026-03-13 20:10:53', 72, NULL, NULL, 0.00, 'Mpesa', 1),
(91, NULL, NULL, 300.00, 'UNPAID', '2026-03-14 12:58:56', 72, NULL, NULL, 0.00, 'Mpesa', 1),
(92, NULL, NULL, 1950.00, 'UNPAID', '2026-03-14 13:16:00', 73, NULL, NULL, 0.00, 'Mpesa', 1),
(93, NULL, NULL, 400.00, 'UNPAID', '2026-03-17 05:20:16', 72, NULL, NULL, 0.00, 'Mpesa', 1),
(94, NULL, NULL, 400.00, 'UNPAID', '2026-03-17 15:29:29', 72, NULL, NULL, 0.00, 'Mpesa', 1),
(95, NULL, NULL, 3500.00, 'UNPAID', '2026-03-17 15:37:54', 63, NULL, NULL, 0.00, 'Mpesa', 1),
(96, NULL, NULL, 500.00, 'UNPAID', '2026-03-18 10:43:29', 74, NULL, NULL, 0.00, 'Cash', 1),
(97, NULL, NULL, 400.00, 'UNPAID', '2026-03-18 13:58:10', 75, NULL, NULL, 0.00, 'Cash', 1),
(98, NULL, NULL, 2100.00, 'UNPAID', '2026-03-18 17:25:11', 77, NULL, NULL, 0.00, 'Cash', 1),
(99, NULL, NULL, 2100.00, 'UNPAID', '2026-03-18 17:25:21', 77, NULL, NULL, 0.00, 'Mpesa', 1),
(100, NULL, NULL, 530.00, 'UNPAID', '2026-03-18 18:01:24', 78, NULL, NULL, 0.00, 'Mpesa', 1),
(101, NULL, NULL, 1000.00, 'UNPAID', '2026-03-18 20:15:53', 60, NULL, NULL, 0.00, 'Mpesa', 1),
(102, NULL, NULL, 3650.00, 'UNPAID', '2026-03-19 03:02:51', 79, NULL, NULL, 0.00, 'Mpesa', 1),
(103, NULL, NULL, 1000.00, 'UNPAID', '2026-03-19 10:15:43', 74, NULL, NULL, 0.00, 'Cash', 1),
(104, NULL, NULL, 1000.00, 'UNPAID', '2026-03-19 10:15:44', 74, NULL, NULL, 0.00, 'Cash', 1),
(105, NULL, NULL, 1500.00, 'UNPAID', '2026-03-19 16:22:24', 76, NULL, NULL, 0.00, 'Mpesa', 1),
(106, NULL, NULL, 3000.00, 'UNPAID', '2026-03-21 09:35:34', 80, NULL, NULL, 0.00, 'Cash', 1),
(107, NULL, NULL, 200.00, 'UNPAID', '2026-03-22 06:01:20', 81, NULL, NULL, 0.00, 'Cash', 1),
(108, NULL, NULL, 3000.00, 'UNPAID', '2026-03-23 13:08:34', 83, NULL, NULL, 0.00, 'Cash', 1),
(109, NULL, NULL, 2000.00, 'UNPAID', '2026-03-24 07:27:46', 84, NULL, NULL, 0.00, 'Mpesa', 1),
(110, NULL, NULL, 700.00, 'UNPAID', '2026-03-24 07:33:04', 85, NULL, NULL, 0.00, 'Cash', 1),
(111, NULL, NULL, 200.00, 'UNPAID', '2026-03-24 11:05:12', 86, NULL, NULL, 0.00, 'Wire Transfer', 1),
(112, NULL, NULL, 825.00, 'UNPAID', '2026-03-25 14:56:25', 84, NULL, NULL, 0.00, 'Mpesa', 1),
(113, NULL, NULL, 200.00, 'UNPAID', '2026-03-25 17:19:29', 88, NULL, NULL, 0.00, 'Cash', 1),
(114, NULL, NULL, 2100.00, 'UNPAID', '2026-03-26 10:40:37', 89, NULL, NULL, 0.00, 'Mpesa', 1),
(115, NULL, NULL, 1500.00, 'UNPAID', '2026-03-26 16:32:31', 83, NULL, NULL, 0.00, 'Mpesa', 1),
(116, NULL, NULL, 700.00, 'UNPAID', '2026-03-26 19:17:13', 80, NULL, NULL, 0.00, 'Mpesa', 1),
(117, NULL, NULL, 2350.00, 'UNPAID', '2026-03-27 03:12:19', 91, NULL, NULL, 0.00, 'Mpesa', 1),
(118, NULL, NULL, 4300.00, 'UNPAID', '2026-03-28 16:33:31', 98, NULL, NULL, 0.00, 'Mpesa', 1),
(119, NULL, NULL, 400.00, 'UNPAID', '2026-03-29 00:16:52', 99, NULL, NULL, 0.00, 'Cash', 1),
(120, NULL, NULL, 2200.00, 'UNPAID', '2026-03-29 16:15:08', 100, NULL, NULL, 0.00, 'Mpesa', 1),
(121, NULL, NULL, 2200.00, 'UNPAID', '2026-03-29 16:15:08', 100, NULL, NULL, 0.00, 'Mpesa', 1),
(122, NULL, NULL, 1000.00, 'UNPAID', '2026-03-29 16:27:40', 82, NULL, NULL, 0.00, 'Cash', 1),
(123, NULL, NULL, 600.00, 'UNPAID', '2026-04-01 05:00:05', 102, NULL, NULL, 0.00, 'Mpesa', 1),
(124, NULL, NULL, 600.00, 'UNPAID', '2026-04-01 05:42:47', 102, NULL, NULL, 0.00, 'Cash', 1),
(125, NULL, NULL, 300.00, 'UNPAID', '2026-04-01 16:11:14', 104, NULL, NULL, 0.00, 'Mpesa', 1),
(126, NULL, NULL, 3800.00, 'UNPAID', '2026-04-01 16:36:54', 90, NULL, NULL, 0.00, 'Mpesa', 1),
(127, NULL, NULL, 300.00, 'UNPAID', '2026-04-01 17:46:39', 102, NULL, NULL, 0.00, 'Mpesa', 1),
(128, NULL, NULL, 200.00, 'UNPAID', '2026-04-01 18:05:54', 105, NULL, NULL, 0.00, 'Mpesa', 1),
(129, NULL, NULL, 1800.00, 'UNPAID', '2026-04-02 08:36:52', 101, NULL, NULL, 0.00, 'Cash', 1),
(130, NULL, NULL, 1800.00, 'UNPAID', '2026-04-02 08:37:09', 101, NULL, NULL, 0.00, 'Cash', 1),
(131, NULL, NULL, 2000.00, 'UNPAID', '2026-04-02 15:13:03', 103, NULL, NULL, 0.00, 'Cash', 1),
(132, NULL, NULL, 3000.00, 'UNPAID', '2026-04-04 18:17:08', 108, NULL, NULL, 0.00, 'Mpesa', 1),
(133, NULL, NULL, 1000.00, 'UNPAID', '2026-04-04 19:06:40', 109, NULL, NULL, 0.00, 'Mpesa', 1),
(134, NULL, NULL, 400.00, 'UNPAID', '2026-04-05 08:09:36', 102, NULL, NULL, 0.00, 'Mpesa', 1),
(135, NULL, NULL, 6100.00, 'UNPAID', '2026-04-05 14:43:12', 107, NULL, NULL, 0.00, 'Mpesa', 1),
(136, NULL, NULL, 10000.00, 'UNPAID', '2026-04-05 15:27:34', 110, NULL, NULL, 0.00, 'Mpesa', 1),
(137, NULL, NULL, 2000.00, 'UNPAID', '2026-04-05 16:04:36', 82, NULL, NULL, 0.00, 'Mpesa', 1),
(138, NULL, NULL, 400.00, 'UNPAID', '2026-04-05 17:44:28', 102, NULL, NULL, 0.00, 'Mpesa', 1),
(139, NULL, NULL, 775.00, 'UNPAID', '2026-04-06 09:57:11', 112, NULL, NULL, 0.00, 'Mpesa', 1),
(140, NULL, NULL, 1500.00, 'UNPAID', '2026-04-06 14:45:35', 113, NULL, NULL, 0.00, 'Mpesa', 1),
(141, NULL, NULL, 200.00, 'UNPAID', '2026-04-06 15:40:33', 114, NULL, NULL, 0.00, 'Cash', 1),
(142, NULL, NULL, 400.00, 'UNPAID', '2026-04-06 15:59:23', 115, NULL, NULL, 0.00, 'Mpesa', 1),
(143, NULL, NULL, 800.00, 'UNPAID', '2026-04-06 16:00:51', 75, NULL, NULL, 0.00, 'Cash', 1),
(144, NULL, NULL, 2450.00, 'UNPAID', '2026-04-06 16:01:39', 113, NULL, NULL, 0.00, 'Mpesa', 1),
(145, NULL, NULL, 3500.00, 'UNPAID', '2026-04-06 16:02:46', 76, NULL, NULL, 0.00, 'Mpesa', 1),
(146, NULL, NULL, 1200.00, 'UNPAID', '2026-04-07 11:12:46', 116, NULL, NULL, 0.00, 'Mpesa', 1),
(147, NULL, NULL, 100.00, 'UNPAID', '2026-04-07 11:13:17', 116, NULL, NULL, 0.00, 'Cash', 1),
(148, NULL, NULL, 950.00, 'UNPAID', '2026-04-07 11:22:13', 116, NULL, NULL, 0.00, 'Cash', 1),
(149, NULL, NULL, 8500.00, 'UNPAID', '2026-04-07 13:15:54', 115, NULL, NULL, 0.00, 'Cash', 1),
(150, NULL, NULL, 1900.00, 'UNPAID', '2026-04-07 13:38:45', 66, NULL, NULL, 0.00, 'Mpesa', 1),
(151, NULL, NULL, 8500.00, 'UNPAID', '2026-04-07 13:41:25', 114, NULL, NULL, 0.00, 'Cash', 1),
(152, NULL, NULL, 950.00, 'UNPAID', '2026-04-07 18:00:42', 117, NULL, NULL, 0.00, 'Mpesa', 1),
(153, NULL, NULL, 3500.00, 'UNPAID', '2026-04-09 11:54:24', 125, NULL, NULL, 0.00, 'Mpesa', 1),
(154, NULL, NULL, 1000.00, 'UNPAID', '2026-04-09 11:54:53', 126, NULL, NULL, 0.00, 'Mpesa', 1),
(155, NULL, NULL, 1700.00, 'UNPAID', '2026-04-09 13:26:48', 127, NULL, NULL, 0.00, 'Mpesa', 1),
(156, NULL, NULL, 4950.00, 'UNPAID', '2026-04-09 15:01:33', 128, NULL, NULL, 0.00, 'Mpesa', 1),
(157, NULL, NULL, 2700.00, 'UNPAID', '2026-04-09 17:16:09', 110, NULL, NULL, 0.00, 'Mpesa', 1),
(158, NULL, NULL, 50.00, 'UNPAID', '2026-04-09 17:35:18', 113, NULL, NULL, 0.00, 'Mpesa', 1),
(159, NULL, NULL, 500.00, 'UNPAID', '2026-04-09 17:36:08', 103, NULL, NULL, 0.00, 'Bank', 1),
(160, NULL, NULL, 1000.00, 'UNPAID', '2026-04-10 09:40:28', 127, NULL, NULL, 0.00, 'Mpesa', 1),
(161, NULL, NULL, 500.00, 'UNPAID', '2026-04-10 09:44:01', 126, NULL, NULL, 0.00, 'Mpesa', 1),
(162, NULL, NULL, 200.00, 'UNPAID', '2026-04-10 10:02:30', 124, NULL, NULL, 0.00, 'Cash', 1),
(163, NULL, NULL, 800.00, 'UNPAID', '2026-04-11 13:53:31', 126, NULL, NULL, 0.00, 'Mpesa', 1),
(164, NULL, NULL, 400.00, 'UNPAID', '2026-04-11 13:53:53', 127, NULL, NULL, 0.00, 'Mpesa', 1),
(165, NULL, NULL, 400.00, 'UNPAID', '2026-04-11 13:57:13', 127, NULL, NULL, 0.00, 'Mpesa', 1),
(166, NULL, NULL, 1500.00, 'UNPAID', '2026-04-11 16:44:38', 113, NULL, NULL, 0.00, 'Cash', 1),
(167, NULL, NULL, 300.00, 'UNPAID', '2026-04-12 04:55:41', 129, NULL, NULL, 0.00, 'Mpesa', 1),
(168, NULL, NULL, 300.00, 'UNPAID', '2026-04-13 17:23:43', 139, NULL, NULL, 0.00, 'Mpesa', 1),
(169, NULL, NULL, 2400.00, 'UNPAID', '2026-04-13 17:24:30', 73, NULL, NULL, 0.00, 'Cash', 1),
(170, NULL, NULL, 2500.00, 'UNPAID', '2026-04-13 17:24:56', 66, NULL, NULL, 0.00, 'Mpesa', 1),
(171, NULL, NULL, 200.00, 'UNPAID', '2026-04-14 10:57:40', 107, NULL, NULL, 0.00, 'Cash', 1),
(172, NULL, NULL, 700.00, 'UNPAID', '2026-04-15 06:53:11', 138, NULL, NULL, 0.00, 'Mpesa', 1),
(173, NULL, NULL, 800.00, 'UNPAID', '2026-04-17 15:09:40', 142, NULL, NULL, 0.00, 'Mpesa', 1),
(174, NULL, NULL, 2950.00, 'UNPAID', '2026-04-17 15:47:40', 141, NULL, NULL, 0.00, 'Cash', 1),
(175, NULL, NULL, 850.00, 'UNPAID', '2026-04-18 15:13:46', 142, NULL, NULL, 0.00, 'Mpesa', 1),
(176, NULL, NULL, 850.00, 'UNPAID', '2026-04-20 15:42:23', 142, NULL, NULL, 0.00, 'Mpesa', 1),
(177, NULL, NULL, 2500.00, 'UNPAID', '2026-04-21 15:44:03', 143, NULL, NULL, 0.00, 'Mpesa', 1),
(178, NULL, NULL, 2450.00, 'UNPAID', '2026-04-21 15:46:07', 142, NULL, NULL, 0.00, 'Mpesa', 1),
(179, NULL, NULL, 1000.00, 'UNPAID', '2026-04-23 10:25:52', 145, NULL, NULL, 0.00, 'Cash', 1),
(180, NULL, NULL, 2000.00, 'UNPAID', '2026-04-25 17:28:17', 146, NULL, NULL, 0.00, 'Cash', 1),
(181, NULL, NULL, 200.00, 'UNPAID', '2026-04-25 18:23:38', 147, NULL, NULL, 0.00, 'Mpesa', 1),
(182, NULL, NULL, 2800.00, 'UNPAID', '2026-04-26 15:13:14', 148, NULL, NULL, 0.00, 'Cash', 1),
(183, NULL, NULL, 3050.00, 'UNPAID', '2026-04-28 10:44:00', 151, NULL, NULL, 0.00, 'Cash', 1),
(184, NULL, NULL, 1000.00, 'UNPAID', '2026-04-29 16:54:58', 152, NULL, NULL, 0.00, 'Mpesa', 1),
(185, NULL, NULL, 1900.00, 'UNPAID', '2026-04-30 06:18:51', 145, NULL, NULL, 0.00, 'Cash', 1),
(186, NULL, NULL, 2300.00, 'UNPAID', '2026-05-02 16:14:06', 154, NULL, NULL, 0.00, 'Cash', 1),
(187, NULL, NULL, 2300.00, 'UNPAID', '2026-05-02 16:15:33', 154, NULL, NULL, 0.00, 'Cash', 1),
(188, NULL, NULL, 2300.00, 'UNPAID', '2026-05-02 16:26:21', 154, NULL, NULL, 0.00, 'Cash', 1),
(189, NULL, NULL, 1000.00, 'UNPAID', '2026-05-03 12:29:38', 156, NULL, NULL, 0.00, 'Mpesa', 1),
(190, NULL, NULL, 2850.00, 'UNPAID', '2026-05-03 13:20:43', 157, NULL, NULL, 0.00, 'Cash', 1),
(191, NULL, NULL, 3300.00, 'UNPAID', '2026-05-03 15:31:53', 155, NULL, NULL, 0.00, 'Mpesa', 1),
(192, NULL, NULL, 1500.00, 'UNPAID', '2026-05-03 17:08:19', 158, NULL, NULL, 0.00, 'Mpesa', 1),
(193, NULL, NULL, 3300.00, 'UNPAID', '2026-05-04 14:30:19', 159, NULL, NULL, 0.00, 'Mpesa', 1),
(194, NULL, NULL, 1800.00, 'UNPAID', '2026-05-04 14:38:08', 160, NULL, NULL, 0.00, 'Mpesa', 1),
(195, NULL, NULL, 1000.00, 'UNPAID', '2026-05-07 18:26:34', 161, NULL, NULL, 0.00, 'Mpesa', 1),
(196, NULL, NULL, 1000.00, 'UNPAID', '2026-05-07 18:31:58', 161, NULL, NULL, 0.00, 'Mpesa', 1),
(197, NULL, NULL, 2300.00, 'UNPAID', '2026-05-08 08:35:27', 162, NULL, NULL, 0.00, 'Mpesa', 1),
(198, NULL, NULL, 1800.00, 'UNPAID', '2026-05-08 17:05:28', 163, NULL, NULL, 0.00, 'Mpesa', 1),
(199, NULL, NULL, 20000.00, 'UNPAID', '2026-05-09 07:14:13', 111, NULL, NULL, 0.00, 'Mpesa', 1),
(200, NULL, NULL, 800.00, 'UNPAID', '2026-05-12 14:57:39', 164, NULL, NULL, 0.00, 'Cash', 1),
(201, NULL, NULL, 1300.00, 'UNPAID', '2026-05-13 08:22:16', 167, NULL, NULL, 0.00, 'Cash', 1),
(202, NULL, NULL, 1300.00, 'UNPAID', '2026-05-13 08:22:17', 167, NULL, NULL, 0.00, 'Cash', 1),
(203, NULL, NULL, 1300.00, 'UNPAID', '2026-05-13 08:22:18', 167, NULL, NULL, 0.00, 'Cash', 1),
(204, NULL, NULL, 2000.00, 'UNPAID', '2026-05-16 08:37:35', 169, NULL, NULL, 0.00, 'Mpesa', 1),
(205, NULL, NULL, 900.00, 'UNPAID', '2026-05-16 13:05:09', 169, NULL, NULL, 0.00, 'Cash', 1),
(206, NULL, NULL, 6100.00, 'UNPAID', '2026-05-16 13:06:37', 169, NULL, NULL, 0.00, 'Cash', 1),
(207, NULL, NULL, 6100.00, 'UNPAID', '2026-05-16 13:06:38', 169, NULL, NULL, 0.00, 'Cash', 1),
(208, NULL, NULL, 1300.00, 'UNPAID', '2026-05-24 18:43:53', 171, NULL, NULL, 0.00, 'Mpesa', 1),
(209, NULL, NULL, 1500.00, 'UNPAID', '2026-05-29 13:07:31', 172, NULL, NULL, 0.00, 'Mpesa', 1),
(210, NULL, NULL, 4750.00, 'UNPAID', '2026-05-30 18:41:19', 173, NULL, NULL, 0.00, 'Mpesa', 1),
(211, NULL, NULL, 2000.00, 'UNPAID', '2026-05-31 10:03:38', 179, NULL, NULL, 0.00, 'Cash', 1),
(212, NULL, NULL, 1350.00, 'UNPAID', '2026-05-31 18:54:20', 180, NULL, NULL, 0.00, 'Mpesa', 1),
(213, NULL, NULL, 1500.00, 'UNPAID', '2026-06-05 10:12:29', 182, NULL, NULL, 0.00, 'Cash', 1),
(214, NULL, NULL, 1950.00, 'UNPAID', '2026-06-07 14:05:52', 184, NULL, NULL, 0.00, 'Cash', 1),
(215, NULL, NULL, 2950.00, 'UNPAID', '2026-06-08 15:36:56', 186, NULL, NULL, 0.00, 'Mpesa', 1),
(216, NULL, NULL, 8900.00, 'UNPAID', '2026-06-08 16:09:53', 185, NULL, NULL, 0.00, 'Cash', 1),
(217, NULL, NULL, 3800.00, 'UNPAID', '2026-06-09 06:52:52', 188, NULL, NULL, 0.00, 'Mpesa', 1),
(218, NULL, NULL, 5600.00, 'UNPAID', '2026-06-10 09:12:23', 190, NULL, NULL, 0.00, 'Mpesa', 1),
(219, NULL, NULL, 2850.00, 'UNPAID', '2026-06-14 05:35:22', 193, NULL, NULL, 0.00, 'Cash', 1),
(220, NULL, NULL, 1000.00, 'UNPAID', '2026-06-17 04:19:29', 195, NULL, NULL, 0.00, 'Mpesa', 1),
(221, NULL, NULL, 3600.00, 'UNPAID', '2026-06-18 09:32:11', 191, NULL, NULL, 0.00, 'Cash', 1),
(222, NULL, NULL, 2000.00, 'UNPAID', '2026-06-18 09:38:00', 196, NULL, NULL, 0.00, 'Mpesa', 1),
(223, NULL, NULL, 1400.00, 'UNPAID', '2026-06-18 09:38:45', 196, NULL, NULL, 0.00, 'Cash', 1),
(224, NULL, NULL, 2800.00, 'UNPAID', '2026-06-20 05:48:15', 197, NULL, NULL, 0.00, 'Cash', 1),
(225, NULL, NULL, 1000.00, 'UNPAID', '2026-06-20 21:35:47', 198, NULL, NULL, 0.00, 'Mpesa', 1),
(226, NULL, NULL, 1500.00, 'UNPAID', '2026-06-22 15:34:57', 199, NULL, NULL, 0.00, 'Cash', 1),
(227, NULL, NULL, 3000.00, 'UNPAID', '2026-06-24 20:07:38', 200, NULL, NULL, 0.00, 'Mpesa', 1),
(228, NULL, NULL, 200.00, 'UNPAID', '2026-06-26 17:32:33', 202, NULL, NULL, 0.00, 'Cash', 1),
(229, NULL, NULL, 1350.00, 'UNPAID', '2026-06-26 17:33:06', 201, NULL, NULL, 0.00, 'Cash', 1),
(230, NULL, NULL, 4050.00, 'UNPAID', '2026-06-27 05:51:16', 203, NULL, NULL, 0.00, 'Cash', 1),
(231, NULL, NULL, 500.00, 'UNPAID', '2026-06-27 13:00:39', 204, NULL, NULL, 0.00, 'Mpesa', 1),
(232, NULL, NULL, 2300.00, 'UNPAID', '2026-06-27 13:02:03', 204, NULL, NULL, 0.00, 'Cash', 1),
(233, NULL, NULL, 2350.00, 'UNPAID', '2026-07-01 06:24:42', 205, NULL, NULL, 0.00, 'Cash', 1),
(234, NULL, NULL, 3900.00, 'UNPAID', '2026-07-03 06:40:01', 206, NULL, NULL, 0.00, 'Mpesa', 1),
(235, NULL, NULL, 1000.00, 'UNPAID', '2026-07-04 13:41:56', 207, NULL, NULL, 0.00, 'Mpesa', 1),
(236, NULL, NULL, 1000.00, 'UNPAID', '2026-07-04 13:41:57', 207, NULL, NULL, 0.00, 'Mpesa', 1),
(237, NULL, NULL, 5900.00, 'UNPAID', '2026-07-05 13:33:13', 208, NULL, NULL, 0.00, 'Mpesa', 1),
(238, NULL, NULL, 5900.00, 'UNPAID', '2026-07-05 13:33:14', 208, NULL, NULL, 0.00, 'Mpesa', 1),
(239, NULL, NULL, 5900.00, 'UNPAID', '2026-07-05 14:16:03', 208, NULL, NULL, 0.00, 'Mpesa', 1),
(240, NULL, NULL, 850.00, 'UNPAID', '2026-07-10 07:18:25', 209, NULL, NULL, 0.00, 'Mpesa', 1),
(241, NULL, NULL, 850.00, 'UNPAID', '2026-07-10 07:18:26', 209, NULL, NULL, 0.00, 'Mpesa', 1),
(242, NULL, NULL, 850.00, 'UNPAID', '2026-07-10 07:18:28', 209, NULL, NULL, 0.00, 'Mpesa', 1),
(243, NULL, NULL, 850.00, 'UNPAID', '2026-07-10 07:18:28', 209, NULL, NULL, 0.00, 'Mpesa', 1),
(244, NULL, NULL, 850.00, 'UNPAID', '2026-07-10 07:18:29', 209, NULL, NULL, 0.00, 'Mpesa', 1),
(245, NULL, NULL, 4200.00, 'UNPAID', '2026-07-12 11:38:50', 215, NULL, NULL, 0.00, 'Mpesa', 1),
(246, NULL, NULL, 2000.00, 'UNPAID', '2026-07-12 11:47:20', 216, NULL, NULL, 0.00, 'Bank', 1),
(247, NULL, NULL, 550.00, 'UNPAID', '2026-07-18 16:17:46', 219, NULL, NULL, 0.00, 'Mpesa', 1),
(248, NULL, NULL, 800.00, 'UNPAID', '2026-07-25 19:10:52', 220, NULL, NULL, 0.00, 'Cash', 1),
(249, NULL, NULL, 800.00, 'UNPAID', '2026-07-25 19:10:53', 220, NULL, NULL, 0.00, 'Cash', 1),
(250, NULL, NULL, 200.00, 'UNPAID', '2026-07-28 17:34:53', 222, NULL, NULL, 0.00, 'Cash', 1),
(251, NULL, NULL, 1500.00, 'UNPAID', '2026-07-30 17:38:56', 223, NULL, NULL, 0.00, 'Mpesa', 1),
(252, NULL, NULL, 900.00, 'UNPAID', '2026-08-04 09:39:35', 225, NULL, NULL, 0.00, 'Mpesa', 1),
(253, NULL, NULL, 3150.00, 'UNPAID', '2026-08-05 18:55:04', 226, NULL, NULL, 0.00, 'Mpesa', 1),
(254, NULL, NULL, 3500.00, 'UNPAID', '2026-08-05 19:41:32', 227, NULL, NULL, 0.00, 'Cash', 1),
(255, NULL, NULL, 1000.00, 'UNPAID', '2026-08-07 04:33:05', 230, NULL, NULL, 0.00, 'Cash', 1),
(256, NULL, NULL, 2000.00, 'UNPAID', '2026-08-07 04:35:49', 229, NULL, NULL, 0.00, 'Cash', 1),
(257, NULL, NULL, 800.00, 'UNPAID', '2026-08-08 20:22:50', 231, NULL, NULL, 0.00, 'Mpesa', 1),
(258, NULL, NULL, 3100.00, 'UNPAID', '2026-08-14 05:52:06', 236, NULL, NULL, 0.00, 'Mpesa', 1),
(259, NULL, NULL, 3100.00, 'UNPAID', '2026-08-14 05:52:06', 236, NULL, NULL, 0.00, 'Mpesa', 1),
(260, NULL, NULL, 850.00, 'UNPAID', '2026-08-14 10:07:41', 237, NULL, NULL, 0.00, 'Bank', 1),
(261, NULL, NULL, 400.00, 'UNPAID', '2026-08-14 12:54:06', 238, NULL, NULL, 0.00, 'Mpesa', 1),
(262, NULL, NULL, 1950.00, 'UNPAID', '2026-08-14 15:54:56', 143, NULL, NULL, 0.00, 'Cash', 1),
(263, NULL, NULL, 2500.00, 'UNPAID', '2026-08-19 07:30:17', 241, NULL, NULL, 0.00, 'Mpesa', 1),
(264, NULL, NULL, 800.00, 'UNPAID', '2026-08-20 14:25:17', 241, NULL, NULL, 0.00, 'Mpesa', 1),
(265, NULL, NULL, 1000.00, 'UNPAID', '2026-08-21 16:17:32', 243, NULL, NULL, 0.00, 'Mpesa', 1),
(266, NULL, NULL, 600.00, 'UNPAID', '2026-08-21 20:02:22', 244, NULL, NULL, 0.00, 'Cash', 1),
(267, NULL, NULL, 4000.00, 'UNPAID', '2026-08-22 14:56:31', 245, NULL, NULL, 0.00, 'Mpesa', 1),
(268, NULL, NULL, 4000.00, 'UNPAID', '2026-08-22 14:56:33', 245, NULL, NULL, 0.00, 'Mpesa', 1),
(269, NULL, NULL, 500.00, 'UNPAID', '2026-08-25 05:42:54', 249, NULL, NULL, 0.00, 'Cash', 1),
(270, NULL, NULL, 9500.00, 'UNPAID', '2026-08-26 05:55:48', 248, NULL, NULL, 0.00, 'Mpesa', 1),
(271, NULL, NULL, 4000.00, 'UNPAID', '2026-08-26 14:07:35', 250, NULL, NULL, 0.00, 'Mpesa', 1),
(272, NULL, NULL, 1200.00, 'UNPAID', '2026-08-26 14:22:44', 252, NULL, NULL, 0.00, 'Mpesa', 1),
(273, NULL, NULL, 700.00, 'UNPAID', '2026-08-26 16:52:38', 253, NULL, NULL, 0.00, 'Mpesa', 1),
(274, NULL, NULL, 1500.00, 'UNPAID', '2026-08-27 05:08:06', 249, NULL, NULL, 0.00, 'Mpesa', 1),
(275, NULL, NULL, 400.00, 'UNPAID', '2026-08-27 07:32:57', 254, NULL, NULL, 0.00, 'Mpesa', 1);

-- --------------------------------------------------------

--
-- Table structure for table `billing_items`
--

CREATE TABLE `billing_items` (
  `id` int(11) NOT NULL,
  `encounter_id` int(11) DEFAULT NULL,
  `source` enum('consultation','lab','pharmacy') DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

CREATE TABLE `bills` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `description` varchar(250) DEFAULT NULL,
  `amount` double DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `claim_denials`
--

CREATE TABLE `claim_denials` (
  `id` int(11) NOT NULL,
  `claim_id` int(11) NOT NULL,
  `claim_item_id` int(11) DEFAULT NULL,
  `denial_code` varchar(50) DEFAULT NULL,
  `denial_reason` varchar(255) NOT NULL,
  `denial_category` enum('Eligibility','Authorization','Benefit Limit','Documentation','Coding','Duplicate','Pricing','Other') NOT NULL DEFAULT 'Other',
  `denied_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `appeal_status` enum('Not Appealed','Appealed','Resolved','Written Off') NOT NULL DEFAULT 'Not Appealed',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `claim_headers`
--

CREATE TABLE `claim_headers` (
  `id` int(11) NOT NULL,
  `claim_number` varchar(100) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `patient_coverage_id` int(11) NOT NULL,
  `payer_id` int(11) NOT NULL,
  `plan_id` int(11) DEFAULT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `preauthorization_id` int(11) DEFAULT NULL,
  `encounter_date` date NOT NULL,
  `submission_date` date DEFAULT NULL,
  `claim_status` enum('Draft','Submitted','Under Review','Approved','Partially Approved','Rejected','Paid','Closed') NOT NULL DEFAULT 'Draft',
  `total_claim_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_approved_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_patient_responsibility` decimal(12,2) NOT NULL DEFAULT 0.00,
  `diagnosis_summary` text DEFAULT NULL,
  `submission_notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `claim_items`
--

CREATE TABLE `claim_items` (
  `id` int(11) NOT NULL,
  `claim_id` int(11) NOT NULL,
  `item_type` enum('Consultation','Service','Lab','Radiology','Procedure','Pharmacy','Admission','Other') NOT NULL,
  `source_table` varchar(100) DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `item_code` varchar(100) DEFAULT NULL,
  `item_name` varchar(200) NOT NULL,
  `quantity` decimal(12,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `gross_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `covered_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `copay_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `patient_responsibility` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('Draft','Submitted','Approved','Partially Approved','Rejected','Paid') NOT NULL DEFAULT 'Draft',
  `rejection_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clinic_categories`
--

CREATE TABLE `clinic_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clinic_categories`
--

INSERT INTO `clinic_categories` (`id`, `name`) VALUES
(1, 'General Medicine'),
(2, 'Pediatrics'),
(3, 'Surgery'),
(4, 'Gynecology'),
(5, 'ENT'),
(6, 'Dental');

-- --------------------------------------------------------

--
-- Table structure for table `consultations`
--

CREATE TABLE `consultations` (
  `id` int(11) NOT NULL,
  `encounter_id` int(11) DEFAULT NULL,
  `complaints` text DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deliveries`
--

CREATE TABLE `deliveries` (
  `id` int(11) NOT NULL,
  `mother_name` varchar(100) NOT NULL,
  `delivery_date` date NOT NULL,
  `delivery_type` varchar(50) NOT NULL,
  `baby_weight` varchar(20) NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `diagnosis`
--

CREATE TABLE `diagnosis` (
  `diagnosis_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `icd_10_code` varchar(10) DEFAULT NULL,
  `diagnosis_description` text NOT NULL,
  `diagnosis_type` varchar(20) DEFAULT 'primary',
  `date_diagnosed` date NOT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `diagnosis_master`
--

CREATE TABLE `diagnosis_master` (
  `id` int(11) NOT NULL,
  `diagnosis_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `diagnostics`
--

CREATE TABLE `diagnostics` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `test_name` varchar(200) NOT NULL,
  `results` text DEFAULT NULL,
  `requested_by` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `discharges`
--

CREATE TABLE `discharges` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `discharged_by` int(11) DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dispensations`
--

CREATE TABLE `dispensations` (
  `id` int(11) NOT NULL,
  `encounter_id` int(11) DEFAULT NULL,
  `drug_id` int(11) DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `dispensed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `drugs`
--

CREATE TABLE `drugs` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `encounters`
--

CREATE TABLE `encounters` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `procedures` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `clinic_category` varchar(100) NOT NULL,
  `complaint` text DEFAULT NULL,
  `type` enum('outpatient','inpatient','walkin','emergency','maternity') DEFAULT 'outpatient',
  `doctor_id` int(11) DEFAULT NULL,
  `status` enum('open','closed') DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `closed_at` datetime DEFAULT NULL,
  `presenting_complaint` text DEFAULT NULL,
  `hpc` text DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  `surgical_history` text DEFAULT NULL,
  `family_history` text DEFAULT NULL,
  `drug_history` text DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `social_history` text DEFAULT NULL,
  `review_systems` text DEFAULT NULL,
  `physical_exam` text DEFAULT NULL,
  `differential_diagnosis` text DEFAULT NULL,
  `investigations` text DEFAULT NULL,
  `management_plan` text DEFAULT NULL,
  `prescription_instructions` longtext DEFAULT NULL,
  `doctor_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `encounters`
--

INSERT INTO `encounters` (`id`, `appointment_id`, `patient_id`, `diagnosis`, `procedures`, `notes`, `clinic_category`, `complaint`, `type`, `doctor_id`, `status`, `created_at`, `closed_at`, `presenting_complaint`, `hpc`, `medical_history`, `surgical_history`, `family_history`, `drug_history`, `allergies`, `social_history`, `review_systems`, `physical_exam`, `differential_diagnosis`, `investigations`, `management_plan`, `prescription_instructions`, `doctor_notes`) VALUES
(1, 1, 1, NULL, NULL, NULL, '', NULL, 'outpatient', 2, 'open', '2025-12-23 10:29:21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 2, 1, NULL, NULL, NULL, '', NULL, 'outpatient', 3, 'open', '2025-12-23 10:29:53', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 3, 1, NULL, NULL, NULL, '', NULL, 'outpatient', 5, 'open', '2025-12-23 10:32:53', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, NULL, 1, NULL, NULL, NULL, '', NULL, 'walkin', NULL, 'open', '2026-01-11 08:52:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 4, 2, NULL, NULL, NULL, '', NULL, 'outpatient', 2, 'open', '2026-01-15 14:52:32', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, NULL, 1, NULL, NULL, NULL, '', NULL, 'walkin', NULL, 'open', '2026-02-04 11:47:41', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, NULL, 1, NULL, NULL, NULL, '', NULL, 'walkin', NULL, 'open', '2026-02-04 11:57:48', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, NULL, 1, NULL, NULL, NULL, '', NULL, 'walkin', NULL, 'open', '2026-02-04 11:57:55', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, NULL, 1, NULL, NULL, NULL, '', NULL, 'walkin', NULL, 'open', '2026-02-04 12:05:37', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, NULL, 1, NULL, NULL, NULL, '', NULL, 'walkin', NULL, 'open', '2026-02-04 12:05:40', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(11, NULL, 1, NULL, NULL, NULL, '', NULL, 'walkin', NULL, 'open', '2026-02-04 12:08:44', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(12, NULL, 1, NULL, NULL, NULL, '', NULL, 'walkin', NULL, 'open', '2026-02-04 12:08:49', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(13, NULL, 1, NULL, NULL, NULL, '', NULL, 'walkin', NULL, 'open', '2026-02-04 12:11:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(14, NULL, 1, NULL, NULL, NULL, '', NULL, 'walkin', NULL, 'open', '2026-02-04 12:14:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(15, NULL, 1, NULL, NULL, NULL, '', NULL, 'walkin', NULL, 'open', '2026-02-04 12:14:10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(16, NULL, 1, NULL, NULL, NULL, '', NULL, 'walkin', NULL, 'open', '2026-02-04 12:20:39', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(17, NULL, 1, NULL, NULL, NULL, '', NULL, 'walkin', NULL, 'open', '2026-02-04 12:47:50', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(18, NULL, 1, NULL, NULL, NULL, '', NULL, 'walkin', NULL, 'open', '2026-02-04 12:48:07', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(19, NULL, 1, NULL, NULL, NULL, '', NULL, 'walkin', NULL, 'open', '2026-02-04 13:07:35', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(22, NULL, 8, 'e', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-02-24 12:55:18', NULL, '', '', '', '', '', '', '', 'e', 'e', 'e', '', '', '', NULL, ''),
(23, NULL, 8, 'e', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-02-24 13:23:48', NULL, '', 'd', 'd', '', '', '', '', 'e', 'e', 'e', '', '', '', NULL, ''),
(24, NULL, 8, 'e', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-02-24 13:23:58', NULL, '', 'd', 'd', '', '', '', '', 'e', 'e', 'e', '', '', '', NULL, ''),
(25, NULL, 8, 'e', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-02-24 13:24:04', NULL, '', 'd', 'd', '', '', '', '', 'e', 'e', 'e', '', '', '', NULL, ''),
(26, NULL, 8, 'e', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-02-24 13:32:52', NULL, '', 'd', 'd', '', '', '', '', 'e', 'e', 'e', '', '', '', NULL, ''),
(27, NULL, 9, NULL, NULL, NULL, 'General Outpatient', 'dfswerewewq', 'outpatient', 5, 'open', '2026-02-24 13:44:43', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(28, NULL, 9, 'f', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-02-24 13:45:22', NULL, 'f', 'f', 'f', 'f', 'f', 'f', 'f', 'f', 'f', 'f', 'f', 'f', 'f', 'f', 'f'),
(29, NULL, 28, 'lrti', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-02-27 14:24:37', NULL, 'cough \r\nhob\r\n', '', 'no pre meds', 'no', 'na', 'na', 'na', 'na', 'unremarkable', 'normal', 'bacterimia', 'pbf', '', '', ''),
(30, NULL, 30, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-02-27 19:10:27', NULL, 'pv bleeding for 1 month ', 'post use of 1 mnth fp [ sophia]', 'premeds sterone', '', '', '', '', '', '', '', '', '', '', '', ''),
(31, NULL, 31, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-02-28 16:09:36', NULL, 'laps radiating to the back', 'onset at 27/02/2028', 'known kp', 'no hx of surgery', '', '', 'no known food or drug allergy', '', '', '', '', '', '', '', ''),
(32, NULL, 36, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-01 15:53:17', NULL, 'difficult in swallowing', 'onset  3 days', 'oremeds azithromycin', 'nil', '', '', '', '', '', '', '', '', '', '', ''),
(33, NULL, 37, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-02 10:52:07', NULL, 'knwn steosarcoma', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(34, NULL, 39, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-02 18:14:58', NULL, 'urethral discharge', 'onset 2 weeks ago', 'no premeds', '', '', '', '', '', '', '', '', '', '', '', ''),
(35, NULL, 46, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-04 13:41:14', NULL, 'septic rashes', 'onset 3 days ago', 'premeds saline drps', '', '', '', '', '', '', '', '', '', '', '', ''),
(36, NULL, 46, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-04 13:41:47', NULL, 'septic rashes', 'onset 3 days ago', 'premeds saline drps', '', '', '', '', '', '', '', '', '', '', '', 'diagnosis septic rash'),
(37, NULL, 47, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-05 05:34:46', NULL, 'difficulty in swallowing', 'onset 1 day ago', 'remeds paracetaml', 'nil', 'nil', '', 'no known food or drug allergy', '', '', '', '', '', '', '', ''),
(38, NULL, 34, 'urti', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-05 08:38:23', NULL, 'cough', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(39, NULL, 47, 'tonsilitis', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-05 08:43:43', NULL, 'difficulty in swallowing', 'onset 1 day ago', 'remeds paracetaml', 'nil', 'nil', '', 'no known food or drug allergy', '', '', '', '', '', '', '', ''),
(40, NULL, 48, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-05 11:22:02', NULL, 'chest pain radiating to the back', 'onset 4 days ', 'premeds accinet \r\n        pdl\r\n        myospaz', '', '', '', '', '', '', '', '', '', '', '', ''),
(41, NULL, 49, 'upper respiratory tract infections', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-05 11:55:39', NULL, 'chest pains\r\ncough', '0nset a few months ago', 'no premeds', '', '', '', '', '', '', '', '', '', '', '', ''),
(42, NULL, 51, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-07 11:42:14', NULL, 'chest pains', '', 'on amoiclav ,pdl and myospaz', '', '', '', '', '', '', '', '', '', '', '', ''),
(43, NULL, 52, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-07 16:23:50', NULL, 'dx beast ectasia[ size [0.7]', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(44, NULL, 59, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-09 08:06:13', NULL, 'corn on the right leg', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(45, NULL, 55, 'herpes ', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-10 08:50:54', NULL, 'tingling ,itching and burning blisters', '', 'onset 3 days ago', '', '', '', '', '', '', '', '', '', '', '', ''),
(46, NULL, 60, 'uppwerrespiratory tract nfection', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-10 18:19:32', NULL, 'chest pains\r\nmild headache\r\ncough\r\nmild congestion\r\n', '', 'onset 3 days ag0\r\nprwemeds . diclofenac / cetrizine', '', '', '', 'no food/ drug allergy', '', '', '', '', '', '', '', ''),
(47, NULL, 61, 'upper respiratory tract infection', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-12 06:22:41', NULL, 'cough\r\nrunny nose\r\nchest  congestion\r\n high temps', '', 'premeds iv cef/multivit', '', '', '', 'nkfda', '', '', '', '', 'cbc', '', '', ''),
(48, NULL, 61, 'upper respiratory tract infection', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-12 06:22:41', NULL, 'cough\r\nrunny nose\r\nchest  congestion\r\n high temps', '', 'premeds iv cef/multivit', '', '', '', 'nkfda', '', '', '', '', 'cbc', '', '', ''),
(49, NULL, 70, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-13 16:26:13', NULL, 'high temps', '', 'premeds cetrizine', '', '', '', '', '', '', '', '', '', '', '', ''),
(50, NULL, 70, 'septicaemia', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-13 17:02:46', NULL, 'high temps', '', 'premeds cetrizine', '', '', '', '', '', '', '', '', '', '', 'accinet bd 5/7\r\nmetronidazole tds 5/7\r\nibugesic tds 5/7\r\ngacet stt', ''),
(51, NULL, 71, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-13 17:32:52', NULL, 'high temps', '', 'no premed', '', '', '', '', '', '', '', '', '', '', '', ''),
(52, NULL, 72, 'septicaemia /URTI', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-13 20:15:26', NULL, 'high temp\r\nvmiting\r\ncough', '', 'premeds promethazine\r\nibrufen\r\namoxl', '', '', '', '', '', '', '', '', '', '', '', ''),
(53, NULL, 73, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-14 14:52:45', NULL, 'COUGH \r\nCHEST PAINS', '', 'REMEDS CEFUROXIMWE', '', '', '', '', '', '', '', '', '', '', '', ''),
(54, NULL, 73, 'urti', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-14 14:53:02', NULL, 'COUGH \r\nCHEST PAINS', '', 'REMEDS CEFUROXIMWE', '', '', '', '', '', '', '', '', '', '', '', ''),
(55, NULL, 75, 'UTI IN PG', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-18 13:56:51', NULL, 'GRAVID AND TERM', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(56, NULL, 76, 'tonsiitis', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-18 15:31:46', NULL, 'difficulty in swallowing', '', 'onset 1 day ago', '', '', '', '', '', '', '', '', '', '', '', ''),
(57, NULL, 60, 'low rbs', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-18 19:47:58', NULL, 'body malaise', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(58, NULL, 79, 'septiceamin /urti', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-19 02:33:15', NULL, 'High tem\r\nnasal congestion\r\n1 episde of convulsion', '', 'no premeds', '', '', '', '', '', '', '', '', '', '', '', ''),
(59, NULL, 80, 'urti', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-19 06:09:32', NULL, 'cough \r\nvomiting', '', 'premeds [ orals]', '', '', '', '', '', '', '', '', '', '', '', ''),
(60, NULL, 80, 'urti/septicaemia', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-19 07:36:40', NULL, 'cough \r\nvomiting', '', 'premeds [ orals]', '', '', '', '', '', '', '', '', '', '', '', ''),
(61, NULL, 84, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-24 05:52:49', NULL, 'abdominal pains\r\nvomiting\r\ndiarrhoe \r\n', '', 'premed\r\nmetronidazole\r\n', '', '', '', '', '', '', '', '', '', '', '', ''),
(62, NULL, 87, 'afi', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-24 16:19:41', NULL, 'gbm\r\nhadache\r\ndizziness', '', 'na', 'na', 'na', 'na', 'na', 'na', 'nad', 'normal', 'bactiremia', '', '', '', ''),
(63, NULL, 88, 'urti', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-25 17:19:18', NULL, 'cough', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(64, NULL, 89, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-26 10:38:38', NULL, 'left leg burns\r\nleft thumb burns', '', 'no premeds', '', '', '', '', '', '', '', '', '', '', '', ''),
(65, NULL, 91, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-27 02:53:36', NULL, 'diarrhoea\r\nvomiting \r\nhigh temp', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(66, NULL, 91, 'GE with Some Dehydration', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-27 03:15:27', NULL, 'diarrhoea\r\nvomiting \r\nhigh temp', 'onset 1 day ago', 'premeds cefalexime and metronidazole', 'na', 'na', 'na', 'na', 'na', 'na', 'na', 'na', 'na', 'na', 'na', 'na'),
(67, NULL, 91, 'GE with Some Dehydration', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-27 03:15:47', NULL, 'diarrhoea\r\nvomiting \r\nhigh temp', 'onset 1 day ago', 'premeds cefalexime and metronidazole', 'na', 'na', 'na', 'na', 'na', 'na', 'na', 'na', 'na', 'na', 'na', 'na'),
(68, NULL, 100, 'septicaemia', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-29 15:41:18', NULL, 'headache\r\nhigh temp', '', 'onset 1 day ago', '', '', '', '', '', '', '', '', '', '', '', ''),
(69, NULL, 101, 'htn', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-03-31 21:44:58', NULL, 'high bps 178/118', '', 'nne', '', '', '', '', '', '', '', '', '', '', '', ''),
(70, NULL, 102, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-04-01 04:51:53', NULL, 'joint pains \r\nnausea and vomiting\r\nbody malaise', '', 'onset 1 day ago\r\nno premeds c\\', '', '', '', '', '', '', '', '', '', '', '', ''),
(71, NULL, 103, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-04-01 05:04:37', NULL, 'vomiting\r\n runny nose', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(72, NULL, 106, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-04-03 18:10:04', NULL, 'pain on abdomen', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(73, NULL, 115, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-04-06 15:55:26', NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(74, NULL, 66, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-04-07 13:36:44', NULL, 'cough', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(75, NULL, 124, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-04-08 16:20:46', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(76, NULL, 125, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-04-09 09:52:12', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(77, NULL, 126, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-04-09 11:09:50', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(78, NULL, 127, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-04-09 12:10:26', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(79, NULL, 127, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-04-09 12:20:56', NULL, 'cough', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(80, NULL, 128, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-04-09 12:22:41', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(81, NULL, 129, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-04-10 13:45:03', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(82, NULL, 144, NULL, NULL, NULL, '', NULL, 'maternity', NULL, 'open', '2026-04-20 12:55:12', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(83, NULL, 145, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-04-23 10:20:11', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(84, NULL, 145, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-04-23 10:20:38', NULL, 'chemical  burns', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(85, NULL, 146, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-04-24 08:58:05', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(86, NULL, 147, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-04-25 18:16:22', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(87, NULL, 147, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-04-25 18:18:25', NULL, 'heaache\r\nabdominal pains\r\nvomiting', '', 'remeds promethazine', '', '', '', '', '', '', '', '', '', '', '', ''),
(88, NULL, 147, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-04-25 18:18:32', NULL, 'heaache\r\nabdominal pains\r\nvomiting', '', 'remeds promethazine', '', '', '', '', '', '', '', '', '', '', '', ''),
(89, NULL, 148, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-04-26 12:52:33', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(90, NULL, 149, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-04-26 12:52:34', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(91, NULL, 148, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-04-26 12:56:01', NULL, 'vomiting 2 times ', '', 'magnesium\r\nnormal saline \r\n\r\n\r\n ', '', 'onset yesterday', '', '', '', '', '', '', '', '', '', ''),
(92, NULL, 150, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-04-28 10:26:10', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(93, NULL, 151, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-04-28 10:41:14', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(94, NULL, 152, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-04-29 16:51:05', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(95, NULL, 153, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-04-30 07:10:38', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(96, NULL, 153, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-04-30 07:11:46', NULL, 'joint pains\r\nheadache\r\nchills', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(97, NULL, 154, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-05-02 15:57:20', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(98, NULL, 155, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-05-03 11:37:24', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(99, NULL, 156, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-05-03 12:06:36', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(100, NULL, 156, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-05-03 12:08:23', NULL, 'painful micturation\r\nwhitish discharge\r\nfrequent micturation', '', 'no premeds', '', '', '', '', '', '', '', '', '', '', '', ''),
(101, NULL, 157, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-05-03 12:32:14', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(102, NULL, 158, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-05-03 15:34:19', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(103, NULL, 159, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-05-04 14:25:28', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(104, NULL, 160, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-05-04 14:36:00', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(105, NULL, 161, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-05-07 17:52:51', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(106, NULL, 162, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-05-08 07:19:11', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(107, NULL, 163, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-05-08 16:12:17', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(108, NULL, 163, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-05-08 16:13:33', NULL, 'pv whitish discharge\r\nrashes', '', 'no pemeds\r\nonset 3 days ado', '', '', '', '', '', '', '', '', '', '', '', ''),
(109, NULL, 164, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-05-12 14:41:07', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(110, NULL, 165, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-05-12 14:41:08', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(111, NULL, 166, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-05-12 14:41:09', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(112, NULL, 167, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-05-13 06:30:46', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(113, NULL, 167, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-05-13 06:33:05', NULL, 'diarrhoea\r\nonset 1 week\r\ncough \r\nrunning nose', '', 'no premeds', '', '', '', '', '', '', '', '', '', '', '', ''),
(114, NULL, 167, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-05-13 06:33:33', NULL, 'diarrhoea\r\nonset 1 week\r\ncough \r\nrunning nose', '', 'no premeds', '', '', '', '', '', '', '', '', '', '', '', ''),
(115, NULL, 168, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-05-16 08:23:23', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(116, NULL, 169, NULL, NULL, NULL, '', NULL, 'maternity', NULL, 'open', '2026-05-16 08:25:42', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(117, NULL, 170, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-05-21 19:44:39', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(118, NULL, 171, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-05-24 18:06:02', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(119, NULL, 172, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-05-29 13:03:05', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(120, NULL, 173, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-05-30 15:12:45', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(121, NULL, 174, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-05-30 15:12:45', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(122, NULL, 175, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-05-30 15:12:45', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(123, NULL, 176, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-05-30 15:12:45', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(124, NULL, 177, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-05-30 15:12:45', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(125, NULL, 178, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-05-30 15:12:45', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(126, NULL, 173, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-05-30 15:14:01', NULL, 'headache\r\nleft leg pains sec to rta\r\n \r\n\r\n ', '', '\r\n\r\n', '', '', '', '', '', '', '', '', '', '', '', ''),
(127, NULL, 179, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-05-31 09:08:35', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(128, NULL, 179, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-05-31 09:09:51', NULL, 'high temp\r\n\r\nm', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(129, NULL, 180, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-05-31 18:34:49', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(130, NULL, 181, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-06-01 09:14:11', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(131, NULL, 182, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-06-05 10:09:16', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(132, NULL, 182, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-06-05 10:10:06', NULL, 'high temp\r\nconvulsions', '', 'onset 1 day ago', '', '', '', '', '', '', '', '', '', '', '', ''),
(133, NULL, 183, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-06-07 09:59:18', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(134, NULL, 184, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-06-07 12:02:17', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(135, NULL, 184, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-06-07 12:04:59', NULL, 'headache\r\nrunny \r\ndizziness', '', '0nset today \r\npremeds msaramoja', '', '', '', '', '', '', '', '', '', '', '', ''),
(136, NULL, 184, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-06-07 12:18:59', NULL, 'headache\r\nrunny \r\ndizziness', '', '0nset today \r\npremeds msaramoja', '', '', '', '', '', '', '', '', '', '', '', ''),
(137, NULL, 185, NULL, NULL, NULL, '', NULL, 'maternity', NULL, 'open', '2026-06-08 14:23:48', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(138, NULL, 186, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-06-08 14:50:46', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(139, NULL, 187, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-06-09 06:20:49', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(140, NULL, 188, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-06-09 06:22:00', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(141, NULL, 189, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-06-09 17:14:36', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(142, NULL, 190, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-06-10 07:34:18', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(143, NULL, 190, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-06-10 07:36:15', NULL, 'frequent micturation\r\nnight sweats\r\n\r\n', '', 'onset 1 month ago\r\n\r\n', '', '', '', 'nil', '', '', '', '', '', '', '', ''),
(144, NULL, 190, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-06-10 07:36:15', NULL, 'frequent micturation\r\nnight sweats\r\n\r\n', '', 'onset 1 month ago\r\n\r\n', '', '', '', 'nil', '', '', '', '', '', '', '', ''),
(145, NULL, 191, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-06-12 15:07:06', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(146, NULL, 191, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-06-12 15:08:08', NULL, 'painful swallowing\r\n', '', 'onset today \r\nno premeds', '', '', '', '', '', '', '', '', '', '', '', ''),
(147, NULL, 192, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-06-12 17:23:51', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(148, NULL, 192, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-06-12 17:40:51', NULL, 'severe abdominal pain\r\n no cysts \r\n', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(149, NULL, 192, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-06-12 17:50:30', NULL, 'severe abdominal pain\r\n no cysts \r\ngynaecological  \r\n', '', 'hx of miscarriage \r\novarian cyst by scan', '', '', '', '', '', '', '', '', '', '', '', ''),
(150, NULL, 193, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-06-14 05:29:10', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(151, NULL, 193, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-06-14 05:30:05', NULL, 'vomiting', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(152, NULL, 194, NULL, NULL, NULL, '', NULL, 'maternity', NULL, 'open', '2026-06-14 06:13:24', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(153, NULL, 195, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-06-16 20:14:41', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(154, NULL, 196, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-06-18 09:34:13', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(155, NULL, 196, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-06-18 09:35:21', NULL, '\r\n\r\ncough', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(156, NULL, 197, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-06-20 05:13:28', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(157, NULL, 197, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-06-20 05:16:00', NULL, 'severe abdominal pains\r\ndiarrhoea\r\npainful micturation\r\npainful periods\r\n\r\n', '', 'no premeds\r\n', '', '', '', '', '', '', '', '', '', '', '', ''),
(158, NULL, 198, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-06-20 20:29:35', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(159, NULL, 199, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-06-22 15:25:23', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(160, NULL, 200, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-06-24 18:15:26', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(161, NULL, 200, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-06-24 18:17:57', NULL, '\r\nvomiting \r\nchills\r\n', '', 'hx of asthnatic attack\r\nhas used salbutaml\r\npainkillers used\' dicloenac ', '', '', '', '', '', '', '', '', '', '', '', ''),
(162, NULL, 201, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-06-26 17:04:55', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(163, NULL, 202, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-06-26 17:04:56', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(164, NULL, 201, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-06-26 17:06:19', NULL, 'runny nose', '', 'onset 1 monthy\r\npiriton', '', '', '', '', '', '', '', '', '', '', '', ''),
(165, NULL, 203, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-06-27 05:43:44', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(166, NULL, 204, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-06-27 12:04:03', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(167, NULL, 204, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-06-27 12:44:32', NULL, 'chest pains aggravated by cough\r\nintermittent headache\r\ndry cough\r\n', 'pt presented with complains of headache and chest pain for2/52,has prev been on treatment for URTI showing no signs of improvement', 'n hx of chronic illnesses\r\nn prev hx of similar complains', 'nil', '', 'flugone', '', '', '', '', '', '', '', '', ''),
(168, NULL, 204, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-06-27 12:46:03', NULL, 'chest pains aggravated by cough\r\nintermittent headache\r\ndry cough\r\n', 'pt presented with complains of headache and chest pain for2/52,has prev been on treatment for URTI showing no signs of improvement', 'n hx of chronic illnesses\r\nn prev hx of similar complains', 'nil', '', 'flugone', '', '', '', '', '', '', '', '', ''),
(169, NULL, 205, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-07-01 06:22:32', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(170, NULL, 206, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-07-03 06:20:02', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(171, NULL, 206, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-07-03 06:21:14', NULL, 'ainfull swallowing\r\nbody malaise \r\nhotness of the body', '', 'onset 3 days ago\r\nprwemeds cetrizine\r\n', '', '', '', '', '', '', '', '', '', '', '', ''),
(172, NULL, 207, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-07-04 13:07:07', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(173, NULL, 208, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-07-05 09:20:44', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(174, NULL, 208, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-07-05 09:21:50', NULL, 'headache\r\ndifficulty in sw\\allowing \r\nhigh temp', '', 'onset 1 day  ago \r\nprwemeds diclofrnac', '', '', '', '', '', '', '', '', '', '', '', ''),
(175, NULL, 208, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-07-05 09:27:11', NULL, 'headache\r\ndifficulty in sw\\allowing \r\nhigh temp', '', 'onset 1 day  ago \r\nprwemeds diclofrnac', '', '', '', '', '', '', '', '', '', '', '', ''),
(176, NULL, 209, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-07-10 07:11:59', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(177, NULL, 210, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-07-10 07:12:02', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(178, NULL, 211, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-07-10 07:12:02', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(179, NULL, 212, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-07-10 07:12:03', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(180, NULL, 213, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-07-10 07:12:03', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(181, NULL, 214, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-07-10 07:12:03', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(182, NULL, 215, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-07-12 10:12:05', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(183, NULL, 215, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-07-12 10:25:13', NULL, 'septic wounds\r\npainful sex\r\n', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(184, NULL, 216, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-07-12 10:45:11', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(185, NULL, 216, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-07-12 10:46:18', NULL, 'rashes\r\n itchness \r\n', '', 'no premeds', '', '', '', '', '', '', '', '', '', '', '', ''),
(186, NULL, 217, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-07-12 12:04:39', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(187, NULL, 218, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-07-18 08:05:41', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(188, NULL, 218, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-07-18 08:08:07', NULL, 'dizziness \r\nbody malaise\r\nheavy menses', '', 'piroxacam \r\non impanon family planning', '', '', '', '', '', '', '', '', '', '', '', ''),
(189, NULL, 219, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-07-18 15:44:03', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(190, NULL, 220, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-07-25 18:18:22', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(191, NULL, 221, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-07-27 16:05:16', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(192, NULL, 222, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-07-28 17:34:14', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(193, NULL, 223, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-07-30 15:14:26', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(194, NULL, 223, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-07-30 15:31:07', NULL, 'Registered via reception', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(195, NULL, 224, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-08-01 12:16:18', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(196, NULL, 225, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-08-04 04:55:11', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(197, NULL, 225, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-08-04 04:57:11', NULL, 'general body malaise\r\ndifficulty in swallowing\r\nchills\r\nno hx of travel \r\n\r\n\r\n\r\n\r\n\r\n\r\n', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(198, NULL, 225, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-08-04 05:04:45', NULL, 'general body malaise\r\ndifficulty in swallowing\r\nchills\r\nno hx of travel \r\n\r\n\r\n\r\n\r\n\r\n\r\n', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(199, NULL, 225, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-08-04 06:07:40', NULL, 'general body malaise\r\ndifficulty in swallowing\r\nchills\r\nno hx of travel \r\n\r\n\r\n\r\n\r\n\r\n\r\n', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(200, NULL, 225, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-08-04 06:08:39', NULL, 'general body malaise\r\ndifficulty in swallowing\r\nchills\r\nno hx of travel \r\n\r\n\r\n\r\n\r\n\r\n\r\n', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(201, NULL, 226, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-08-05 18:24:56', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(202, NULL, 226, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-08-05 19:03:35', NULL, 'itcness of the labias\r\nfrequent micturation\r\ncreamy white disacharge', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(203, NULL, 227, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-08-05 19:22:09', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(204, NULL, 227, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-08-05 19:24:04', NULL, 'high temp\r\nbody malaise \r\nrunny nose\r\n', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(205, NULL, 228, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-08-06 08:55:14', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(206, NULL, 228, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-08-06 08:56:30', NULL, 'labia itchness\r\npainfull micturation', '', 'onset 1 week', '', '', '', '', '', '', '', '', '', '', '', ''),
(207, NULL, 229, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-08-06 16:35:33', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(208, NULL, 229, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-08-06 17:48:17', NULL, 'HOB \r\nRUNNING NOSE \r\nVOMITING ', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(209, NULL, 230, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-08-07 03:23:53', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(210, NULL, 230, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-08-07 03:26:03', NULL, 'HOB\r\nCOUGH \r\nRUNNING NOSE ', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(211, NULL, 231, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-08-08 19:24:45', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(212, NULL, 232, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-08-11 17:33:54', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(213, NULL, 232, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-08-11 17:34:53', NULL, 'frequent micturation\r\nwhitish  discharge\r\n', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(214, NULL, 233, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-08-11 17:36:25', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(215, NULL, 233, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-08-11 17:37:51', NULL, 'whitish vaginal discharge\r\nitchness\r\n\r\n\r\n\r\n', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(216, NULL, 233, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-08-11 17:37:51', NULL, 'whitish vaginal discharge\r\nitchness\r\n\r\n\r\n\r\n', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(217, NULL, 234, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-08-12 13:12:01', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(218, NULL, 235, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-08-13 13:32:42', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(219, NULL, 235, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-08-13 13:33:27', NULL, 'vomiting\r\ndarrhoea  \r\nabdominal painsa', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(220, NULL, 236, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-08-14 05:26:22', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(221, NULL, 237, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-08-14 09:47:58', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(222, NULL, 238, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-08-14 12:44:11', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(223, NULL, 239, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-08-15 09:58:21', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(224, NULL, 240, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-08-16 17:03:29', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(225, NULL, 240, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-08-16 17:05:21', NULL, 'HBD\r\nBACK PAIN \r\nCHILLS', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(226, NULL, 241, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-08-19 06:45:23', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(227, NULL, 241, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-08-19 06:47:00', NULL, 'high temp\r\ncough\r\npainful cough ', '', 'onset 1 day ag0', '', '', '', '', '', '', '', '', '', '', '', ''),
(228, NULL, 242, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-08-19 14:20:33', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(229, NULL, 242, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-08-19 14:22:21', NULL, 'vomiting\r\nhigh temp', '', 'no premeds', '', '', '', '', '', '', '', '', '', '', '', ''),
(230, NULL, 243, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-08-21 10:35:34', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(231, NULL, 243, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-08-21 10:38:57', NULL, 'high temp \r\nheadache pain\r\npainful swallowing  \r\n\r\n\r\n', '', 'no premeds\r\n', '', '', '', '', '', '', '', '', '', '', '', ''),
(232, NULL, 243, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-08-21 16:13:30', NULL, 'high temp \r\nheadache pain\r\npainful swallowing  \r\n\r\n\r\n', '', 'no premeds\r\n', '', '', '', '', '', '', '', '', '', '', '', ''),
(233, NULL, 244, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-08-21 18:13:06', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(234, NULL, 245, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-08-22 09:59:55', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(235, NULL, 246, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-08-23 07:23:21', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(236, NULL, 247, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-08-25 04:47:43', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(237, NULL, 248, NULL, NULL, NULL, '', NULL, 'maternity', NULL, 'open', '2026-08-25 04:49:38', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(238, NULL, 247, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-08-25 04:50:21', NULL, 'lower backains radiating to the back', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(239, NULL, 249, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-08-25 05:19:36', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(240, NULL, 249, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-08-25 05:21:28', NULL, 'chest pains radiating to the back', '', 'onset 2 days ago\r\npremeds .. azithromycin', '', '', '', '', '', '', '', '', '', '', '', '');
INSERT INTO `encounters` (`id`, `appointment_id`, `patient_id`, `diagnosis`, `procedures`, `notes`, `clinic_category`, `complaint`, `type`, `doctor_id`, `status`, `created_at`, `closed_at`, `presenting_complaint`, `hpc`, `medical_history`, `surgical_history`, `family_history`, `drug_history`, `allergies`, `social_history`, `review_systems`, `physical_exam`, `differential_diagnosis`, `investigations`, `management_plan`, `prescription_instructions`, `doctor_notes`) VALUES
(241, NULL, 250, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-08-26 11:05:34', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(242, NULL, 250, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-08-26 11:06:15', NULL, 'headache\r\n', '', 'premeds ; painkiller\r\n', '', '', '', '', '', '', '', '', '', '', '', ''),
(243, NULL, 251, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-08-26 11:32:12', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(244, NULL, 251, '', NULL, NULL, '', NULL, 'outpatient', NULL, 'open', '2026-08-26 11:33:03', NULL, 'severe headache\r\nrunny nose\r\n', '', 'premeds ;cetrizine', '', '', '', '', '', '', '', '', '', '', '', ''),
(245, NULL, 252, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-08-26 14:19:31', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(246, NULL, 253, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-08-26 16:43:15', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(247, NULL, 254, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-08-27 07:32:38', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(248, NULL, 255, NULL, NULL, NULL, '', NULL, '', NULL, 'open', '2026-08-27 08:39:57', NULL, 'Registered via reception', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `source_type` varchar(80) DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Pending',
  `created_by` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `expense_date` date NOT NULL,
  `payment_method` enum('Cash','Bank Transfer','M-Pesa','Cheque') DEFAULT 'Cash',
  `reference_no` varchar(50) DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `po_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `category_id`, `amount`, `source_type`, `source_id`, `status`, `created_by`, `description`, `expense_date`, `payment_method`, `reference_no`, `recorded_by`, `po_id`, `created_at`) VALUES
(1, 1, 0.00, 'purchase_order', 6, 'Pending', 6, 'PO #6 procurement expense', '2026-04-03', 'Cash', NULL, 0, NULL, '2026-04-03 04:29:22'),
(2, 1, 0.00, 'purchase_order', 7, 'Pending', 6, 'PO #7 procurement expense', '2026-04-03', 'Cash', NULL, 0, NULL, '2026-04-03 04:29:22'),
(3, 1, 0.00, 'purchase_order', 8, 'Pending', 6, 'PO #8 procurement expense', '2026-04-03', 'Cash', NULL, 0, NULL, '2026-04-03 04:29:23'),
(4, 1, 0.00, 'purchase_order', 9, 'Pending', 6, 'PO #9 procurement expense', '2026-04-03', 'Cash', NULL, 0, NULL, '2026-04-03 04:29:52'),
(5, 1, 0.00, 'purchase_order', 10, 'Pending', 6, 'PO #10 procurement expense', '2026-04-03', 'Cash', NULL, 0, NULL, '2026-04-03 04:31:35'),
(6, 1, 15211.57, 'purchase_order', 11, 'Pending', 6, 'PO #11 procurement expense', '2026-04-07', 'Cash', NULL, 0, NULL, '2026-04-07 18:57:46'),
(7, 1, 1000000.00, 'purchase_order', 12, 'Paid', 6, 'PO #12 procurement expense', '2026-08-27', 'Cash', NULL, 0, NULL, '2026-08-27 08:07:49');

-- --------------------------------------------------------

--
-- Table structure for table `expense_categories`
--

CREATE TABLE `expense_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expense_categories`
--

INSERT INTO `expense_categories` (`id`, `category_name`, `description`) VALUES
(1, 'Medical Stock Purchase', NULL),
(2, 'Staff Salaries', NULL),
(3, 'Utilities (Power/Water)', NULL),
(4, 'Rent', NULL),
(5, 'Equipment Maintenance', NULL),
(6, 'Marketing', NULL),
(7, 'Stock Purchase', NULL),
(8, 'Salaries', NULL),
(9, 'Rent', NULL),
(10, 'Utilities', NULL),
(11, 'Medical Stock Purchase', NULL),
(12, 'Staff Salaries', NULL),
(13, 'Utilities (Power/Water)', NULL),
(14, 'Rent', NULL),
(15, 'Equipment Maintenance', NULL),
(16, 'Marketing', NULL),
(17, 'Stock Purchase', NULL),
(18, 'Salaries', NULL),
(19, 'Rent', NULL),
(20, 'Utilities', NULL),
(21, 'Medical Stock Purchase', NULL),
(22, 'Staff Salaries', NULL),
(23, 'Utilities (Power/Water)', NULL),
(24, 'Rent', NULL),
(25, 'Equipment Maintenance', NULL),
(26, 'Marketing', NULL),
(27, 'Stock Purchase', NULL),
(28, 'Salaries', NULL),
(29, 'Rent', NULL),
(30, 'Utilities', NULL),
(31, 'Medical Stock Purchase', NULL),
(32, 'Staff Salaries', NULL),
(33, 'Utilities (Power/Water)', NULL),
(34, 'Rent', NULL),
(35, 'Equipment Maintenance', NULL),
(36, 'Marketing', NULL),
(37, 'Stock Purchase', NULL),
(38, 'Salaries', NULL),
(39, 'Rent', NULL),
(40, 'Utilities', NULL),
(41, 'Medical Stock Purchase', NULL),
(42, 'Staff Salaries', NULL),
(43, 'Utilities (Power/Water)', NULL),
(44, 'Rent', NULL),
(45, 'Equipment Maintenance', NULL),
(46, 'Marketing', NULL),
(47, 'Stock Purchase', NULL),
(48, 'Salaries', NULL),
(49, 'Rent', NULL),
(50, 'Utilities', NULL),
(51, 'Medical Stock Purchase', NULL),
(52, 'Staff Salaries', NULL),
(53, 'Utilities (Power/Water)', NULL),
(54, 'Rent', NULL),
(55, 'Equipment Maintenance', NULL),
(56, 'Marketing', NULL),
(57, 'Stock Purchase', NULL),
(58, 'Salaries', NULL),
(59, 'Rent', NULL),
(60, 'Utilities', NULL),
(61, 'Medical Stock Purchase', NULL),
(62, 'Staff Salaries', NULL),
(63, 'Utilities (Power/Water)', NULL),
(64, 'Rent', NULL),
(65, 'Equipment Maintenance', NULL),
(66, 'Marketing', NULL),
(67, 'Stock Purchase', NULL),
(68, 'Salaries', NULL),
(69, 'Rent', NULL),
(70, 'Utilities', NULL),
(71, 'Medical Stock Purchase', NULL),
(72, 'Staff Salaries', NULL),
(73, 'Utilities (Power/Water)', NULL),
(74, 'Rent', NULL),
(75, 'Equipment Maintenance', NULL),
(76, 'Marketing', NULL),
(77, 'Stock Purchase', NULL),
(78, 'Salaries', NULL),
(79, 'Rent', NULL),
(80, 'Utilities', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `external_referrals`
--

CREATE TABLE `external_referrals` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `referred_facility` varchar(200) NOT NULL,
  `referred_doctor` varchar(150) DEFAULT NULL,
  `specialty` varchar(150) DEFAULT NULL,
  `reason` varchar(255) NOT NULL,
  `urgency` enum('Routine','Urgent','Emergency') NOT NULL DEFAULT 'Routine',
  `notes` text DEFAULT NULL,
  `status` enum('Pending','Accepted','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_receipts`
--

CREATE TABLE `inventory_receipts` (
  `id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `po_item_id` int(11) NOT NULL,
  `supplier_invoice_no` varchar(120) NOT NULL,
  `qty_received` int(11) NOT NULL,
  `unit_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(50) DEFAULT NULL,
  `received_by` int(11) DEFAULT NULL,
  `received_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_receipts`
--

INSERT INTO `inventory_receipts` (`id`, `po_id`, `po_item_id`, `supplier_invoice_no`, `qty_received`, `unit_cost`, `total_cost`, `payment_method`, `received_by`, `received_at`) VALUES
(1, 11, 40, 'xn115008343', 1, 255.00, 255.00, 'Cash', 6, '2026-04-07 21:59:13'),
(2, 11, 41, 'xn115008343', 3, 30.00, 90.00, 'Cash', 6, '2026-04-07 21:59:18'),
(3, 11, 44, 'xn115008343', 10, 35.00, 350.00, 'Cash', 6, '2026-04-07 21:59:21'),
(4, 11, 61, 'xn115008343', 100, 1.20, 120.00, 'Cash', 6, '2026-04-07 21:59:23'),
(5, 11, 62, 'xn115008343', 30, 4.34, 130.20, 'Cash', 6, '2026-04-07 21:59:26'),
(6, 11, 63, 'xn115008343', 10, 28.50, 285.00, 'Cash', 6, '2026-04-07 21:59:29'),
(7, 11, 64, 'xn115008343', 1, 103.00, 103.00, 'Cash', 6, '2026-04-07 21:59:32'),
(8, 11, 26, 'xn115008343', 5, 23.00, 115.00, 'Cash', 6, '2026-04-07 21:59:36'),
(9, 11, 27, 'xn115008343', 30, 12.00, 360.00, 'Cash', 6, '2026-04-07 21:59:40'),
(10, 11, 28, 'xn115008343', 3, 40.00, 120.00, 'Cash', 6, '2026-04-07 21:59:42'),
(11, 11, 29, 'xn115008343', 5, 25.00, 125.00, 'Cash', 6, '2026-04-07 21:59:45'),
(12, 11, 30, 'xn115008343', 100, 7.00, 700.00, 'Cash', 6, '2026-04-07 21:59:48'),
(13, 11, 31, 'xn115008343', 12, 15.00, 180.00, 'Cash', 6, '2026-04-07 21:59:50'),
(14, 11, 32, 'xn115008343', 2, 85.00, 170.00, 'Cash', 6, '2026-04-07 21:59:53'),
(15, 11, 33, 'xn115008343', 20, 15.00, 300.00, 'Cash', 6, '2026-04-07 21:59:55'),
(16, 11, 34, 'xn115008343', 5, 35.00, 175.00, 'Cash', 6, '2026-04-07 22:00:40'),
(17, 11, 35, 'xn115008343', 3, 65.00, 195.00, 'Cash', 6, '2026-04-07 22:00:43'),
(18, 11, 36, 'xn115008343', 2, 55.00, 110.00, 'Cash', 6, '2026-04-07 22:00:45'),
(19, 11, 37, 'xn115008343', 100, 1.30, 130.00, 'Cash', 6, '2026-04-07 22:00:48'),
(20, 11, 38, 'xn115008343', 3, 13.00, 39.00, 'Cash', 6, '2026-04-07 22:01:17'),
(21, 11, 39, 'xn115008343', 2, 90.00, 180.00, 'Cash', 6, '2026-04-07 22:01:25'),
(22, 11, 56, 'xn115008343', 3, 105.00, 315.00, 'Cash', 6, '2026-04-07 22:02:36'),
(23, 11, 42, 'xn115008343', 20, 20.00, 400.00, 'Cash', 6, '2026-04-07 22:02:41'),
(24, 11, 43, 'xn115008343', 10, 9.00, 90.00, 'Cash', 6, '2026-04-07 22:02:44'),
(25, 11, 45, 'xn115008343', 100, 0.90, 90.00, 'Cash', 6, '2026-04-07 22:02:47'),
(26, 11, 46, 'xn115008343', 20, 18.00, 360.00, 'Cash', 6, '2026-04-07 22:02:51'),
(27, 11, 47, 'xn115008343', 10, 60.00, 600.00, 'Cash', 6, '2026-04-07 22:02:53'),
(28, 11, 48, 'xn115008343', 2, 55.00, 110.00, 'Cash', 6, '2026-04-07 22:02:59'),
(29, 11, 49, 'xn115008343', 100, 0.75, 75.00, 'Cash', 6, '2026-04-07 22:03:02'),
(30, 11, 50, 'xn115008343', 2, 25.00, 50.00, 'Cash', 6, '2026-04-07 22:03:05'),
(31, 11, 51, 'xn115008343', 1, 335.00, 335.00, 'Cash', 6, '2026-04-07 22:03:08'),
(32, 11, 52, 'xn115008343', 5, 25.00, 125.00, 'Cash', 6, '2026-04-07 22:03:13'),
(33, 11, 53, 'xn115008343', 200, 2.80, 560.00, 'Cash', 6, '2026-04-07 22:03:16'),
(34, 11, 54, 'xn115008343', 3, 26.00, 78.00, 'Cash', 6, '2026-04-07 22:03:19'),
(35, 11, 55, 'xn115008343', 5, 65.00, 325.00, 'Cash', 6, '2026-04-07 22:03:21'),
(36, 11, 57, 'xn115008343', 10, 21.00, 210.00, 'Cash', 6, '2026-04-07 22:03:24'),
(37, 11, 58, 'xn115008343', 24, 3.50, 84.00, 'Cash', 6, '2026-04-07 22:03:27'),
(38, 11, 59, 'xn115008343', 4, 10.00, 40.00, 'Cash', 6, '2026-04-07 22:03:30'),
(39, 11, 21, 'xn115008343', 1, 347.37, 347.37, 'Cash', 6, '2026-04-07 22:03:35'),
(40, 11, 19, 'xn115008343', 2, 255.00, 510.00, 'Cash', 6, '2026-04-07 22:03:37'),
(41, 11, 20, 'xn115008343', 2, 50.00, 100.00, 'Cash', 6, '2026-04-07 22:03:40'),
(42, 11, 22, 'xn115008343', 1, 240.00, 240.00, 'Cash', 6, '2026-04-07 22:03:43'),
(43, 11, 23, 'xn115008343', 5, 135.00, 675.00, 'Cash', 6, '2026-04-07 22:04:34'),
(44, 11, 24, 'xn115008343', 30, 33.00, 990.00, 'Cash', 6, '2026-04-07 22:04:37'),
(45, 11, 25, 'xn115008343', 5, 110.00, 550.00, 'Cash', 6, '2026-04-07 22:04:42'),
(46, 11, 60, 'xn115008343', 2, 200.00, 400.00, 'Cash', 6, '2026-04-07 22:04:48'),
(47, 11, 16, 'xn115008343', 3, 45.00, 135.00, 'Cash', 6, '2026-04-07 22:04:51'),
(48, 11, 17, 'xn115008343', 1, 105.00, 105.00, 'Cash', 6, '2026-04-07 22:04:54'),
(49, 11, 18, 'xn115008343', 1, 140.00, 140.00, 'Cash', 6, '2026-04-07 22:04:57'),
(50, 11, 15, 'xn115008343', 4, 55.00, 220.00, 'Cash', 6, '2026-04-07 22:05:03'),
(51, 12, 65, 'inv203', 10000, 100.00, 1000000.00, 'Cash', 6, '2026-08-27 11:21:04');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `walkin_name` varchar(150) DEFAULT NULL,
  `walkin_id` varchar(150) DEFAULT NULL,
  `encounter_id` int(11) DEFAULT NULL,
  `invoice_no` varchar(50) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `status` enum('unpaid','paid') DEFAULT 'unpaid',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `paid_at` datetime DEFAULT NULL,
  `payment_mode` varchar(20) DEFAULT NULL,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `cashier_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `unit_price` decimal(10,2) DEFAULT 0.00,
  `discount` decimal(10,2) DEFAULT 0.00,
  `amount_paid` decimal(10,2) DEFAULT 0.00,
  `balance` decimal(10,2) DEFAULT 0.00,
  `payment_status` varchar(20) DEFAULT 'Unpaid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_number`, `patient_id`, `walkin_name`, `walkin_id`, `encounter_id`, `invoice_no`, `total`, `status`, `created_at`, `paid_at`, `payment_mode`, `paid_amount`, `cashier_id`, `quantity`, `unit_price`, `discount`, `amount_paid`, `balance`, `payment_status`) VALUES
(30, NULL, 9, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-02-24 14:10:52', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(31, NULL, NULL, NULL, '12', NULL, NULL, 1000.00, 'paid', '2026-02-24 14:12:10', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(32, NULL, 9, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-02-24 14:15:29', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(33, NULL, 1, NULL, NULL, NULL, NULL, 1400.00, 'paid', '2026-02-24 14:24:19', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(34, NULL, 9, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-02-24 14:25:05', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(35, NULL, 10, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-02-24 14:36:40', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(36, NULL, NULL, NULL, '13', NULL, NULL, 500.00, 'paid', '2026-02-24 15:08:00', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(37, NULL, 17, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-02-25 12:52:39', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(38, NULL, NULL, NULL, '14', NULL, NULL, 20.00, 'paid', '2026-02-25 12:55:46', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(39, NULL, 16, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-02-25 13:04:58', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(40, NULL, NULL, NULL, '15', NULL, NULL, 50.00, 'paid', '2026-02-25 19:32:11', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(41, NULL, NULL, NULL, '16', NULL, NULL, 10.00, 'paid', '2026-02-25 19:33:14', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(42, NULL, NULL, NULL, '17', NULL, NULL, 100.00, 'paid', '2026-02-26 05:17:19', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(43, NULL, NULL, NULL, '18', NULL, NULL, 30.00, 'paid', '2026-02-26 05:17:58', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(44, NULL, 20, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-02-26 07:03:34', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(45, NULL, 20, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-02-26 08:00:17', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(46, NULL, 20, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-02-26 08:02:45', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(47, NULL, 20, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-02-26 08:16:10', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(48, NULL, 19, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-02-26 08:30:35', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(49, NULL, 21, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-02-26 08:36:32', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(50, NULL, NULL, NULL, '19', NULL, NULL, 300.00, 'paid', '2026-02-26 13:53:40', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(51, NULL, NULL, NULL, '20', NULL, NULL, 100.00, 'paid', '2026-02-26 13:55:06', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(52, NULL, NULL, NULL, '21', NULL, NULL, 100.00, 'paid', '2026-02-26 13:55:42', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(53, NULL, NULL, NULL, '22', NULL, NULL, 50.00, 'paid', '2026-02-26 13:57:37', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(54, NULL, NULL, NULL, '23', NULL, NULL, 100.00, 'paid', '2026-02-26 13:58:18', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(55, NULL, NULL, NULL, '24', NULL, NULL, 100.00, 'paid', '2026-02-26 13:59:05', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(56, NULL, NULL, NULL, '25', NULL, NULL, 150.00, 'paid', '2026-02-26 14:00:16', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(57, NULL, NULL, NULL, '26', NULL, NULL, 500.00, 'paid', '2026-02-26 14:01:57', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(58, NULL, NULL, NULL, '27', NULL, NULL, 100.00, 'paid', '2026-02-26 14:42:22', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(59, NULL, NULL, NULL, '28', NULL, NULL, 500.00, 'paid', '2026-02-26 16:58:54', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(60, NULL, NULL, NULL, '29', NULL, NULL, 150.00, 'paid', '2026-02-26 17:04:52', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(61, NULL, 24, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-02-26 17:25:28', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(62, NULL, NULL, NULL, '30', NULL, NULL, 100.00, 'paid', '2026-02-26 17:27:18', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(63, NULL, NULL, NULL, '31', NULL, NULL, 120.00, 'paid', '2026-02-26 17:28:06', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(64, NULL, NULL, NULL, '32', NULL, NULL, 50.00, 'paid', '2026-02-26 18:53:45', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(65, NULL, NULL, NULL, '33', NULL, NULL, 1200.00, 'paid', '2026-02-27 05:26:30', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(66, NULL, 28, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-02-27 07:19:22', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(67, NULL, NULL, NULL, '34', NULL, NULL, 100.00, 'paid', '2026-02-27 14:08:58', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(68, NULL, NULL, NULL, '35', NULL, NULL, 200.00, 'paid', '2026-02-27 14:10:49', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(69, NULL, 29, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-02-27 15:54:55', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(70, NULL, 28, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-02-27 17:20:41', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(71, NULL, 30, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-02-27 19:08:50', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(72, NULL, 30, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-02-27 19:13:39', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(73, NULL, NULL, NULL, '36', NULL, NULL, 20.00, 'paid', '2026-02-27 19:14:23', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(74, NULL, NULL, NULL, '37', NULL, NULL, 20.00, 'paid', '2026-02-27 19:14:23', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(75, NULL, NULL, NULL, '38', NULL, NULL, 45.00, 'paid', '2026-02-27 19:15:08', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(76, NULL, 31, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-02-28 16:23:26', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(77, NULL, 33, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-02-28 16:52:26', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(78, NULL, 33, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-02-28 16:53:12', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(79, NULL, 32, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-02-28 17:23:08', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(80, NULL, 35, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-02-28 17:24:15', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(81, NULL, NULL, NULL, '39', NULL, NULL, 200.00, 'paid', '2026-02-28 17:58:37', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(82, NULL, NULL, NULL, '40', NULL, NULL, 450.00, 'paid', '2026-02-28 18:00:10', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(83, NULL, NULL, NULL, '41', NULL, NULL, 150.00, 'paid', '2026-02-28 18:02:33', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(84, NULL, NULL, NULL, '42', NULL, NULL, 40.00, 'paid', '2026-03-01 08:44:41', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(85, NULL, NULL, NULL, '43', NULL, NULL, 100.00, 'paid', '2026-03-01 08:45:31', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(86, NULL, NULL, NULL, '44', NULL, NULL, 100.00, 'paid', '2026-03-01 09:32:01', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(87, NULL, NULL, NULL, '45', NULL, NULL, 10.00, 'paid', '2026-03-01 10:11:06', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(88, NULL, NULL, NULL, '46', NULL, NULL, 200.00, 'paid', '2026-03-01 10:23:31', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(89, NULL, 36, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-01 16:18:34', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(90, NULL, NULL, NULL, '47', NULL, NULL, 110.00, 'paid', '2026-03-02 07:13:27', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(91, NULL, NULL, NULL, '48', NULL, NULL, 20.00, 'paid', '2026-03-02 07:14:09', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(92, NULL, NULL, NULL, '49', NULL, NULL, 30.00, 'paid', '2026-03-02 07:45:15', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(93, NULL, NULL, NULL, '50', NULL, NULL, 30.00, 'paid', '2026-03-02 07:45:26', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(94, NULL, NULL, NULL, '51', NULL, NULL, 30.00, 'paid', '2026-03-02 08:13:45', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(95, NULL, 31, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-02 08:14:28', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(96, NULL, 36, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-02 09:27:42', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(97, NULL, NULL, NULL, '52', NULL, NULL, 20.00, 'paid', '2026-03-02 11:05:37', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(98, NULL, NULL, NULL, '53', NULL, NULL, 20.00, 'paid', '2026-03-02 11:05:46', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(99, NULL, NULL, NULL, '54', NULL, NULL, 300.00, 'paid', '2026-03-02 11:08:45', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(100, NULL, NULL, NULL, '55', NULL, NULL, 60.00, 'paid', '2026-03-02 15:01:09', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(101, NULL, NULL, NULL, '56', NULL, NULL, 10.00, 'paid', '2026-03-02 15:02:25', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(102, NULL, NULL, NULL, '57', NULL, NULL, 200.00, 'paid', '2026-03-02 18:03:40', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(103, NULL, 39, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-02 18:48:18', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(104, NULL, NULL, NULL, '58', NULL, NULL, 150.00, 'paid', '2026-03-03 06:30:02', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(105, NULL, NULL, NULL, '59', NULL, NULL, 50.00, 'paid', '2026-03-03 06:31:11', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(106, NULL, NULL, NULL, '60', NULL, NULL, 500.00, 'paid', '2026-03-03 06:32:54', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(107, NULL, NULL, NULL, '61', NULL, NULL, 120.00, 'paid', '2026-03-03 06:33:35', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(108, NULL, NULL, NULL, '62', NULL, NULL, 150.00, 'paid', '2026-03-03 06:34:32', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(109, NULL, NULL, NULL, '63', NULL, NULL, 200.00, 'paid', '2026-03-03 06:58:14', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(110, NULL, NULL, NULL, '64', NULL, NULL, 200.00, 'paid', '2026-03-03 06:58:42', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(111, NULL, NULL, NULL, '65', NULL, NULL, 100.00, 'paid', '2026-03-03 09:34:26', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(112, NULL, 42, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-03 12:30:49', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(113, NULL, 36, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-03 14:16:12', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(114, NULL, 44, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-03 18:04:44', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(115, NULL, 42, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-04 08:21:38', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(116, NULL, 37, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-04 08:57:09', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(117, NULL, 45, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-04 10:42:08', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(118, NULL, 46, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-04 13:51:37', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(119, NULL, NULL, NULL, '66', NULL, NULL, 60.00, 'paid', '2026-03-04 17:39:26', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(120, NULL, 47, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-05 07:27:24', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(121, NULL, 34, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-05 08:39:55', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(122, NULL, 28, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-05 08:41:00', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(123, NULL, 28, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-05 08:41:06', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(124, NULL, 27, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-05 08:43:04', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(125, NULL, 27, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-05 08:43:11', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(126, NULL, 49, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-05 12:50:32', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(127, NULL, 48, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-05 12:52:16', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(128, NULL, 50, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-05 13:07:17', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(129, NULL, 50, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-05 13:07:37', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(130, NULL, 50, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-05 13:07:43', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(131, NULL, NULL, NULL, '67', NULL, NULL, 450.00, 'paid', '2026-03-05 13:09:22', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(132, NULL, NULL, NULL, '68', NULL, NULL, 450.00, 'paid', '2026-03-05 13:16:22', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(133, NULL, NULL, NULL, '69', NULL, NULL, 450.00, 'paid', '2026-03-05 13:16:22', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(134, NULL, NULL, NULL, '70', NULL, NULL, 200.00, 'paid', '2026-03-05 13:17:08', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(135, NULL, NULL, NULL, '71', NULL, NULL, 200.00, 'paid', '2026-03-05 13:21:33', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(136, NULL, NULL, NULL, '72', NULL, NULL, 20.00, 'paid', '2026-03-05 14:09:35', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(137, NULL, NULL, NULL, '73', NULL, NULL, 100.00, 'paid', '2026-03-05 15:28:52', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(138, NULL, NULL, NULL, '74', NULL, NULL, 150.00, 'paid', '2026-03-05 15:39:53', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(139, NULL, NULL, NULL, '75', NULL, NULL, 50.00, 'paid', '2026-03-05 15:42:18', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(140, NULL, NULL, NULL, '76', NULL, NULL, 200.00, 'paid', '2026-03-07 07:53:15', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(142, NULL, NULL, NULL, '78', NULL, NULL, 150.00, 'paid', '2026-03-07 07:55:28', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(143, NULL, 47, NULL, NULL, NULL, NULL, 500.00, 'paid', '2026-03-07 08:04:33', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(144, NULL, 51, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-07 11:22:16', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(145, NULL, 51, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-07 11:38:05', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(146, NULL, 51, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-07 11:39:06', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(147, NULL, NULL, NULL, '79', NULL, NULL, 50.00, 'paid', '2026-03-07 13:03:58', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(148, NULL, 52, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-07 16:16:09', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(150, NULL, NULL, NULL, '80', NULL, NULL, 150.00, 'paid', '2026-03-07 16:53:24', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(151, NULL, 53, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-08 07:35:38', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(152, NULL, 54, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-08 13:07:22', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(153, NULL, 51, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-08 13:28:35', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(154, NULL, 55, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-08 13:43:59', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(155, NULL, 55, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-08 13:45:37', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(156, NULL, NULL, NULL, '81', NULL, NULL, 100.00, 'paid', '2026-03-08 14:45:51', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(157, NULL, NULL, NULL, '82', NULL, NULL, 100.00, 'paid', '2026-03-08 14:46:35', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(158, NULL, NULL, NULL, '83', NULL, NULL, 100.00, 'paid', '2026-03-08 14:46:43', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(159, NULL, 56, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-08 20:05:28', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(160, NULL, 57, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-08 20:08:21', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(161, NULL, NULL, NULL, '84', NULL, NULL, 200.00, 'paid', '2026-03-08 20:08:53', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(162, NULL, NULL, NULL, '85', NULL, NULL, 50.00, 'paid', '2026-03-08 20:09:17', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(163, NULL, NULL, NULL, '86', NULL, NULL, 200.00, 'paid', '2026-03-08 20:09:39', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(164, NULL, NULL, NULL, '87', NULL, NULL, 8000.00, 'paid', '2026-03-08 20:10:56', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(165, NULL, NULL, NULL, '88', NULL, NULL, 30.00, 'paid', '2026-03-08 20:11:23', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(166, NULL, NULL, NULL, '89', NULL, NULL, 30.00, 'paid', '2026-03-08 20:11:34', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(167, NULL, NULL, NULL, '90', NULL, NULL, 100.00, 'paid', '2026-03-08 20:12:11', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(168, NULL, NULL, NULL, '91', NULL, NULL, 30.00, 'paid', '2026-03-08 20:12:44', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(169, NULL, 58, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-09 01:54:16', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(170, NULL, NULL, NULL, '92', NULL, NULL, 200.00, 'paid', '2026-03-09 06:50:04', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(171, NULL, NULL, NULL, '93', NULL, NULL, 100.00, 'paid', '2026-03-09 06:50:32', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(172, NULL, 55, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-10 08:49:00', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(173, NULL, NULL, NULL, '94', NULL, NULL, 450.00, 'paid', '2026-03-10 13:35:52', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(174, NULL, NULL, NULL, '95', NULL, NULL, 30.00, 'paid', '2026-03-10 13:36:26', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(175, NULL, 55, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-10 13:53:02', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(176, NULL, 58, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-10 14:13:36', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(177, NULL, 24, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-10 14:21:50', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(178, NULL, 25, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-10 14:22:52', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(179, NULL, 26, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-10 14:23:29', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(180, NULL, 27, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-10 14:24:31', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(181, NULL, 29, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-10 14:25:20', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(182, NULL, 31, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-10 14:25:55', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(183, NULL, 31, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-10 14:26:07', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(184, NULL, 31, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-10 14:26:23', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(185, NULL, 38, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-10 14:27:39', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(186, NULL, 43, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-10 14:28:35', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(187, NULL, 44, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-10 14:29:05', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(188, NULL, 45, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-10 14:29:37', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(189, NULL, 52, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-10 14:30:49', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(190, NULL, 52, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-10 14:31:23', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(191, NULL, 56, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-10 14:31:52', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(192, NULL, 57, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-10 14:32:04', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(193, NULL, 57, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-10 14:32:16', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(194, NULL, 54, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-10 17:51:59', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(195, NULL, 60, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-10 18:43:54', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(196, NULL, NULL, NULL, '96', NULL, NULL, 50.00, 'paid', '2026-03-10 19:48:27', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(197, NULL, NULL, NULL, '97', NULL, NULL, 150.00, 'paid', '2026-03-10 19:52:19', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(198, NULL, 61, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-11 09:30:42', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(199, NULL, NULL, NULL, '98', NULL, NULL, 300.00, 'paid', '2026-03-11 13:32:01', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(200, NULL, NULL, NULL, '99', NULL, NULL, 300.00, 'paid', '2026-03-11 13:32:01', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(201, NULL, NULL, NULL, '100', NULL, NULL, 100.00, 'paid', '2026-03-11 13:32:50', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(202, NULL, NULL, NULL, '101', NULL, NULL, 10.00, 'paid', '2026-03-11 15:10:42', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(203, NULL, NULL, NULL, '102', NULL, NULL, 200.00, 'paid', '2026-03-12 06:23:06', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(204, NULL, 63, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-12 10:24:49', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(205, NULL, 64, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-12 12:50:52', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(206, NULL, 65, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-12 13:00:25', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(207, NULL, NULL, NULL, '103', NULL, NULL, 50.00, 'paid', '2026-03-12 15:35:52', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(208, NULL, NULL, NULL, '104', NULL, NULL, 50.00, 'paid', '2026-03-12 15:42:30', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(209, NULL, NULL, NULL, '105', NULL, NULL, 200.00, 'paid', '2026-03-13 06:25:47', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(210, NULL, NULL, NULL, '106', NULL, NULL, 20.00, 'paid', '2026-03-13 06:27:13', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(211, NULL, NULL, NULL, '107', NULL, NULL, 20.00, 'paid', '2026-03-13 06:28:05', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(212, NULL, NULL, NULL, '108', NULL, NULL, 20.00, 'paid', '2026-03-13 08:20:20', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(213, NULL, NULL, NULL, '109', NULL, NULL, 200.00, 'paid', '2026-03-13 08:20:45', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(214, NULL, 67, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-13 08:22:36', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(215, NULL, NULL, NULL, '110', NULL, NULL, 200.00, 'paid', '2026-03-13 11:12:50', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(216, NULL, NULL, NULL, '111', NULL, NULL, 10.00, 'paid', '2026-03-13 11:13:08', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(217, NULL, 67, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-13 14:30:33', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(218, NULL, NULL, NULL, '112', NULL, NULL, 300.00, 'paid', '2026-03-13 14:31:43', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(219, NULL, NULL, NULL, '113', NULL, NULL, 100.00, 'paid', '2026-03-13 14:32:47', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(220, NULL, NULL, NULL, '114', NULL, NULL, 20.00, 'paid', '2026-03-13 15:37:27', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(221, NULL, NULL, NULL, '115', NULL, NULL, 10.00, 'paid', '2026-03-13 15:37:45', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(222, NULL, 70, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-13 17:00:49', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(223, NULL, 72, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-13 20:10:54', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(224, NULL, NULL, NULL, '116', NULL, NULL, 40.00, 'paid', '2026-03-14 10:39:26', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(225, NULL, NULL, NULL, '117', NULL, NULL, 20.00, 'paid', '2026-03-14 10:40:22', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(226, NULL, NULL, NULL, '118', NULL, NULL, 20.00, 'paid', '2026-03-14 10:40:22', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(227, NULL, NULL, NULL, '119', NULL, NULL, 20.00, 'paid', '2026-03-14 10:40:58', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(228, NULL, NULL, NULL, '120', NULL, NULL, 150.00, 'paid', '2026-03-14 10:44:07', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(229, NULL, 72, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-14 12:58:56', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(230, NULL, 73, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-14 13:16:00', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(231, NULL, NULL, NULL, '121', NULL, NULL, 1000.00, 'paid', '2026-03-14 13:38:03', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(232, NULL, NULL, NULL, '122', NULL, NULL, 500.00, 'paid', '2026-03-14 14:46:20', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(233, NULL, NULL, NULL, '123', NULL, NULL, 150.00, 'paid', '2026-03-14 14:46:37', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(234, NULL, NULL, NULL, '124', NULL, NULL, 100.00, 'paid', '2026-03-14 14:47:20', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(235, NULL, NULL, NULL, '125', NULL, NULL, 1500.00, 'paid', '2026-03-14 16:39:13', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(236, NULL, NULL, NULL, '126', NULL, NULL, 100.00, 'paid', '2026-03-14 16:39:46', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(237, NULL, NULL, NULL, '127', NULL, NULL, 200.00, 'paid', '2026-03-14 16:40:40', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(238, NULL, NULL, NULL, '128', NULL, NULL, 60.00, 'paid', '2026-03-14 16:41:44', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(239, NULL, NULL, NULL, '129', NULL, NULL, 200.00, 'paid', '2026-03-17 03:52:40', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(240, NULL, NULL, NULL, '130', NULL, NULL, 200.00, 'paid', '2026-03-17 03:53:21', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(241, NULL, NULL, NULL, '131', NULL, NULL, 100.00, 'paid', '2026-03-17 03:54:04', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(242, NULL, NULL, NULL, '132', NULL, NULL, 120.00, 'paid', '2026-03-17 03:54:50', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(243, NULL, NULL, NULL, '133', NULL, NULL, 10.00, 'paid', '2026-03-17 03:56:06', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(244, NULL, NULL, NULL, '134', NULL, NULL, 45.00, 'paid', '2026-03-17 03:57:00', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(245, NULL, NULL, NULL, '135', NULL, NULL, 200.00, 'paid', '2026-03-17 03:58:10', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(246, NULL, NULL, NULL, '136', NULL, NULL, 100.00, 'paid', '2026-03-17 03:58:51', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(247, NULL, NULL, NULL, '137', NULL, NULL, 100.00, 'paid', '2026-03-17 03:59:53', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(248, NULL, NULL, NULL, '138', NULL, NULL, 100.00, 'paid', '2026-03-17 04:00:38', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(249, NULL, 72, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-17 05:20:16', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(250, NULL, NULL, NULL, '139', NULL, NULL, 200.00, 'paid', '2026-03-17 09:03:31', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(251, NULL, NULL, NULL, '140', NULL, NULL, 50.00, 'paid', '2026-03-17 09:04:03', NULL, 'Credit', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(252, NULL, 72, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-17 15:29:29', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(253, NULL, 63, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-17 15:37:54', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(254, NULL, NULL, NULL, '141', NULL, NULL, 100.00, 'paid', '2026-03-17 18:36:23', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(255, NULL, 74, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-18 10:43:29', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(256, NULL, 75, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-18 13:58:10', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(257, NULL, 77, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-18 17:25:11', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(258, NULL, 77, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-18 17:25:21', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(259, NULL, 78, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-18 18:01:25', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(260, NULL, 60, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-18 20:15:53', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(261, NULL, NULL, NULL, '142', NULL, NULL, 60.00, 'paid', '2026-03-18 20:33:55', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(262, NULL, 79, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-19 03:02:51', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(263, NULL, 74, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-19 10:15:43', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(265, NULL, 76, NULL, NULL, NULL, NULL, NULL, '', '2026-03-19 16:22:24', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(266, NULL, NULL, NULL, '143', NULL, NULL, 20.00, 'paid', '2026-03-19 16:45:22', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(267, NULL, NULL, NULL, '144', NULL, NULL, 150.00, 'paid', '2026-03-19 16:47:08', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(268, NULL, NULL, NULL, '145', NULL, NULL, 20.00, 'paid', '2026-03-19 18:04:17', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(269, NULL, NULL, NULL, '146', NULL, NULL, 100.00, 'paid', '2026-03-19 18:05:04', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(270, NULL, 80, NULL, NULL, NULL, NULL, NULL, '', '2026-03-21 09:35:34', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(271, NULL, 81, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-22 06:01:20', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(272, NULL, 83, NULL, NULL, NULL, NULL, NULL, '', '2026-03-23 13:08:34', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(273, NULL, 84, NULL, NULL, NULL, NULL, NULL, '', '2026-03-24 07:27:46', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(274, NULL, 85, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-24 07:33:04', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(275, NULL, 86, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-24 11:05:12', NULL, 'Wire Transfer', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(276, NULL, NULL, NULL, '147', NULL, NULL, 20.00, 'paid', '2026-03-25 12:02:15', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(277, NULL, 84, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-25 14:56:25', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(278, NULL, NULL, NULL, '148', NULL, NULL, 30.00, 'paid', '2026-03-25 17:14:23', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(279, NULL, 88, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-25 17:19:29', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(280, NULL, NULL, NULL, '149', NULL, NULL, 20.00, 'paid', '2026-03-25 17:40:27', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(281, NULL, NULL, NULL, '150', NULL, NULL, 30.00, 'paid', '2026-03-25 17:53:59', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(282, NULL, NULL, NULL, '151', NULL, NULL, 200.00, 'paid', '2026-03-26 08:15:57', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(283, NULL, 89, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-26 10:40:37', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(284, NULL, 83, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-26 16:32:31', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(285, NULL, 80, NULL, NULL, NULL, NULL, NULL, '', '2026-03-26 19:17:13', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(286, NULL, 91, NULL, NULL, NULL, NULL, NULL, '', '2026-03-27 03:12:20', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(287, NULL, NULL, NULL, '152', NULL, NULL, 200.00, 'paid', '2026-03-27 14:18:15', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(288, NULL, NULL, NULL, '153', NULL, NULL, 200.00, 'paid', '2026-03-27 18:20:35', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(289, NULL, 98, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-28 16:33:31', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(290, NULL, 99, NULL, NULL, NULL, NULL, NULL, '', '2026-03-29 00:16:52', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(291, NULL, 100, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-29 16:15:08', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(292, NULL, 100, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-03-29 16:15:08', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(293, NULL, 82, NULL, NULL, NULL, NULL, NULL, '', '2026-03-29 16:27:40', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(294, NULL, NULL, NULL, '154', NULL, NULL, 150.00, 'paid', '2026-04-01 03:47:32', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(295, NULL, NULL, NULL, '155', NULL, NULL, 20.00, 'paid', '2026-04-01 03:56:10', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(296, NULL, NULL, NULL, '156', NULL, NULL, 100.00, 'paid', '2026-04-01 03:57:54', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(297, NULL, 102, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-04-01 05:00:05', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(299, NULL, 104, NULL, NULL, NULL, NULL, NULL, '', '2026-04-01 16:11:14', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(300, NULL, 90, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-04-01 16:36:54', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(301, NULL, NULL, NULL, '157', NULL, NULL, 100.00, 'paid', '2026-04-01 16:37:51', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(302, NULL, 102, NULL, NULL, NULL, NULL, NULL, '', '2026-04-01 17:46:39', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(303, NULL, 105, NULL, NULL, NULL, NULL, NULL, '', '2026-04-01 18:05:54', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(304, NULL, NULL, NULL, '158', NULL, NULL, 150.00, 'paid', '2026-04-01 18:17:17', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(305, NULL, NULL, NULL, '159', NULL, NULL, 100.00, 'paid', '2026-04-02 04:03:22', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(306, NULL, NULL, NULL, '160', NULL, NULL, 200.00, 'paid', '2026-04-02 07:31:32', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(307, NULL, 101, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-04-02 08:36:52', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(309, NULL, 103, NULL, NULL, NULL, NULL, NULL, '', '2026-04-02 15:13:03', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(310, NULL, NULL, NULL, '161', NULL, NULL, 10.00, 'paid', '2026-04-02 16:09:07', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(311, NULL, NULL, NULL, '162', NULL, NULL, 20.00, 'paid', '2026-04-02 16:10:08', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(312, NULL, NULL, NULL, '163', NULL, NULL, 50.00, 'paid', '2026-04-03 15:09:59', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(313, NULL, NULL, NULL, '164', NULL, NULL, 200.00, 'paid', '2026-04-03 15:12:36', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(314, NULL, NULL, NULL, '165', NULL, NULL, 100.00, 'paid', '2026-04-03 15:13:27', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(315, NULL, NULL, NULL, '166', NULL, NULL, 300.00, 'paid', '2026-04-03 15:13:54', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(316, NULL, 108, NULL, NULL, NULL, NULL, NULL, '', '2026-04-04 18:17:08', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(317, NULL, 109, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-04-04 19:06:40', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(318, NULL, 102, NULL, NULL, NULL, NULL, NULL, '', '2026-04-05 08:09:36', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(319, NULL, 107, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-04-05 14:43:12', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(320, NULL, 110, NULL, NULL, NULL, NULL, NULL, '', '2026-04-05 15:27:34', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(321, NULL, 82, NULL, NULL, NULL, NULL, NULL, '', '2026-04-05 16:04:36', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(322, NULL, 102, NULL, NULL, NULL, NULL, NULL, '', '2026-04-05 17:44:29', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(323, NULL, NULL, NULL, '167', NULL, NULL, 50.00, 'paid', '2026-04-05 20:12:01', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(324, NULL, NULL, NULL, '168', NULL, NULL, 100.00, 'paid', '2026-04-05 20:12:46', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(325, NULL, NULL, NULL, '169', NULL, NULL, 50.00, 'paid', '2026-04-05 20:14:35', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(326, NULL, NULL, NULL, '170', NULL, NULL, 150.00, 'paid', '2026-04-05 20:15:21', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(327, NULL, NULL, NULL, '171', NULL, NULL, 50.00, 'paid', '2026-04-05 20:15:54', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(328, NULL, NULL, NULL, '172', NULL, NULL, 40.00, 'paid', '2026-04-05 20:17:08', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(329, NULL, NULL, NULL, '173', NULL, NULL, 20.00, 'paid', '2026-04-05 20:17:46', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(330, NULL, 112, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-04-06 09:57:11', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(331, NULL, 113, NULL, NULL, NULL, NULL, NULL, '', '2026-04-06 14:45:35', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(332, NULL, 114, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-04-06 15:40:33', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(333, NULL, 115, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-04-06 15:59:23', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(334, NULL, 75, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-04-06 16:00:51', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(335, NULL, 113, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-04-06 16:01:39', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(336, NULL, 76, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-04-06 16:02:46', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(337, NULL, 116, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-04-07 11:12:46', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(338, NULL, 116, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-04-07 11:13:17', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(339, NULL, NULL, NULL, '174', NULL, NULL, 100.00, 'paid', '2026-04-07 11:14:05', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(340, NULL, NULL, NULL, '175', NULL, NULL, 200.00, 'paid', '2026-04-07 11:14:38', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(341, NULL, NULL, NULL, '176', NULL, NULL, 200.00, 'paid', '2026-04-07 11:15:08', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(342, NULL, NULL, NULL, '177', NULL, NULL, 200.00, 'paid', '2026-04-07 11:15:57', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(343, NULL, NULL, NULL, '178', NULL, NULL, 100.00, 'paid', '2026-04-07 11:17:05', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(344, NULL, 116, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-04-07 11:22:13', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(345, NULL, 115, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-04-07 13:15:54', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(346, NULL, 66, NULL, NULL, NULL, NULL, NULL, '', '2026-04-07 13:38:45', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(347, NULL, 114, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-04-07 13:41:25', NULL, 'Cash', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(348, NULL, NULL, NULL, '179', NULL, NULL, 50.00, 'paid', '2026-04-07 14:04:04', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(349, NULL, NULL, NULL, '180', NULL, NULL, 200.00, 'paid', '2026-04-07 14:14:43', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(350, NULL, 117, NULL, NULL, NULL, NULL, NULL, 'paid', '2026-04-07 18:00:42', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(351, NULL, NULL, NULL, '181', NULL, NULL, 100.00, 'paid', '2026-04-07 19:35:43', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(352, NULL, NULL, NULL, '182', NULL, NULL, 250.00, 'paid', '2026-04-07 19:36:41', NULL, 'Mpesa', 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(353, 'INV-20260408105958-420', NULL, NULL, '183', NULL, NULL, 200.00, 'paid', '2026-04-08 08:59:58', NULL, 'Mpesa', 200.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(354, 'INV-20260408181914-392', NULL, NULL, '184', NULL, NULL, 200.00, 'paid', '2026-04-08 16:19:15', NULL, 'Mpesa', 200.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(355, 'INV-20260408182008-536', NULL, NULL, '185', NULL, NULL, 20.00, 'paid', '2026-04-08 16:20:08', NULL, 'Mpesa', 20.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(356, 'INV-20260409120159-908', 125, NULL, NULL, NULL, NULL, 7850.00, 'unpaid', '2026-04-09 10:01:59', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(357, 'INV-20260409131010-328', 126, NULL, NULL, NULL, NULL, 1800.00, 'unpaid', '2026-04-09 11:10:10', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(358, 'INV-20260409135424-717', 125, NULL, NULL, NULL, NULL, 0.00, '', '2026-04-09 11:54:24', NULL, 'Mpesa', 3500.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(359, 'INV-20260409135453-850', 126, NULL, NULL, NULL, NULL, 0.00, '', '2026-04-09 11:54:53', NULL, 'Mpesa', 1000.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(360, 'INV-20260409142641-928', 128, NULL, NULL, NULL, NULL, 5430.00, 'unpaid', '2026-04-09 12:26:41', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(361, 'INV-20260409144401-692', 127, NULL, NULL, NULL, NULL, 2400.00, 'unpaid', '2026-04-09 12:44:02', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(362, 'INV-20260409152648-308', 127, NULL, NULL, NULL, NULL, 0.00, '', '2026-04-09 13:26:48', NULL, 'Mpesa', 1700.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(363, 'INV-20260409170133-242', 128, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-04-09 15:01:33', NULL, 'Mpesa', 4950.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(364, 'INV-20260409190137-144', 113, NULL, NULL, NULL, NULL, 1400.00, 'unpaid', '2026-04-09 17:01:37', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(365, 'INV-20260409191609-299', 110, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-04-09 17:16:09', NULL, 'Mpesa', 2700.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(366, 'INV-20260409193518-821', 113, NULL, NULL, NULL, NULL, 0.00, '', '2026-04-09 17:35:18', NULL, 'Mpesa', 50.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(367, 'INV-20260409193608-508', 103, NULL, NULL, NULL, NULL, 0.00, '', '2026-04-09 17:36:08', NULL, 'Bank', 500.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(368, 'INV-20260410105937-876', NULL, NULL, '186', NULL, NULL, 20.00, 'paid', '2026-04-10 08:59:37', NULL, 'Mpesa', 20.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(369, 'INV-20260410110000-767', NULL, NULL, '187', NULL, NULL, 100.00, 'paid', '2026-04-10 09:00:00', NULL, 'Mpesa', 100.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(370, 'INV-20260410110038-979', NULL, NULL, '188', NULL, NULL, 100.00, 'paid', '2026-04-10 09:00:38', NULL, 'Mpesa', 100.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(371, 'INV-20260410110116-927', NULL, NULL, '189', NULL, NULL, 250.00, 'paid', '2026-04-10 09:01:16', NULL, 'Mpesa', 250.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(372, 'INV-20260410110137-145', NULL, NULL, '190', NULL, NULL, 200.00, 'paid', '2026-04-10 09:01:37', NULL, 'Mpesa', 200.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(373, 'INV-20260410110207-200', NULL, NULL, '191', NULL, NULL, 400.00, 'paid', '2026-04-10 09:02:07', NULL, 'Mpesa', 400.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(374, 'INV-20260410110245-573', NULL, NULL, '192', NULL, NULL, 150.00, 'paid', '2026-04-10 09:02:45', NULL, 'Mpesa', 150.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(375, 'INV-20260410114028-188', 127, NULL, NULL, NULL, NULL, 0.00, '', '2026-04-10 09:40:28', NULL, 'Mpesa', 1000.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(376, 'INV-20260410114401-399', 126, NULL, NULL, NULL, NULL, 0.00, '', '2026-04-10 09:44:01', NULL, 'Mpesa', 500.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(377, 'INV-20260410120230-131', 124, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-04-10 10:02:30', NULL, 'Cash', 200.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(378, 'INV-20260410185601-802', NULL, NULL, '193', NULL, NULL, 200.00, 'paid', '2026-04-10 16:56:01', NULL, 'Mpesa', 200.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(379, 'INV-20260410194949-933', NULL, NULL, '194', NULL, NULL, 200.00, 'paid', '2026-04-10 17:49:49', NULL, 'Mpesa', 200.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(380, 'INV-20260410195028-427', NULL, NULL, '195', NULL, NULL, 400.00, 'paid', '2026-04-10 17:50:28', NULL, 'Mpesa', 400.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(381, 'INV-20260411155331-639', 126, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-04-11 13:53:31', NULL, 'Mpesa', 800.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(382, 'INV-20260411155353-114', 127, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-04-11 13:53:53', NULL, 'Mpesa', 400.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid');
INSERT INTO `invoices` (`id`, `invoice_number`, `patient_id`, `walkin_name`, `walkin_id`, `encounter_id`, `invoice_no`, `total`, `status`, `created_at`, `paid_at`, `payment_mode`, `paid_amount`, `cashier_id`, `quantity`, `unit_price`, `discount`, `amount_paid`, `balance`, `payment_status`) VALUES
(383, 'INV-20260411155713-163', 127, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-04-11 13:57:13', NULL, 'Mpesa', 400.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(384, 'INV-20260411155855-661', NULL, NULL, '196', NULL, NULL, 50.00, 'paid', '2026-04-11 13:58:56', NULL, 'Mpesa', 50.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(385, 'INV-20260411184438-464', 113, NULL, NULL, NULL, NULL, 0.00, '', '2026-04-11 16:44:38', NULL, 'Cash', 1500.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(386, 'INV-20260411194719-407', NULL, NULL, '197', NULL, NULL, 250.00, 'paid', '2026-04-11 17:47:19', NULL, 'Mpesa', 250.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(387, 'INV-20260412064636-152', 111, NULL, NULL, NULL, NULL, 38915.00, 'unpaid', '2026-04-12 04:46:36', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(388, 'INV-20260412065341-595', 129, NULL, NULL, NULL, NULL, 1125.00, 'unpaid', '2026-04-12 04:53:41', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(389, 'INV-20260412065542-260', 129, NULL, NULL, NULL, NULL, 0.00, '', '2026-04-12 04:55:42', NULL, 'Mpesa', 300.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(390, 'INV-20260412065712-644', NULL, NULL, '198', NULL, NULL, 50.00, 'paid', '2026-04-12 04:57:12', NULL, 'Cash', 50.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(391, 'INV-20260412111602-103', NULL, NULL, '199', NULL, NULL, 50.00, 'paid', '2026-04-12 09:16:02', NULL, 'Mpesa', 50.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(392, 'INV-20260412121402-244', NULL, NULL, '200', NULL, NULL, 50.00, 'paid', '2026-04-12 10:14:02', NULL, 'Mpesa', 50.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(393, 'INV-20260412162420-726', NULL, NULL, '201', NULL, NULL, 200.00, 'paid', '2026-04-12 14:24:20', NULL, 'Cash', 200.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(394, 'INV-20260412162617-597', NULL, NULL, '202', NULL, NULL, 100.00, 'paid', '2026-04-12 14:26:17', NULL, 'Mpesa', 100.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(395, 'INV-20260412185643-822', NULL, NULL, '203', NULL, NULL, 150.00, 'paid', '2026-04-12 16:56:43', NULL, 'Mpesa', 150.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(396, 'INV-20260412210942-699', NULL, NULL, '204', NULL, NULL, 50.00, 'paid', '2026-04-12 19:09:42', NULL, 'Cash', 50.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(397, 'INV-20260413094516-777', 138, NULL, NULL, NULL, NULL, 2000.00, 'unpaid', '2026-04-13 07:45:16', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(398, 'INV-20260413192344-511', 139, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-04-13 17:23:44', NULL, 'Mpesa', 300.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(399, 'INV-20260413192431-314', 73, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-04-13 17:24:31', NULL, 'Cash', 2400.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(400, 'INV-20260413192456-219', 66, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-04-13 17:24:56', NULL, 'Mpesa', 2500.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(401, 'INV-20260413201513-815', NULL, NULL, '205', NULL, NULL, 250.00, 'paid', '2026-04-13 18:15:13', NULL, 'Cash', 250.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(402, 'INV-20260413201608-731', NULL, NULL, '206', NULL, NULL, 200.00, 'paid', '2026-04-13 18:16:08', NULL, 'Mpesa', 200.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(403, 'INV-20260414105653-318', NULL, NULL, '207', NULL, NULL, 100.00, 'paid', '2026-04-14 08:56:53', NULL, 'Cash', 100.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(404, 'INV-20260414125740-623', 107, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-04-14 10:57:40', NULL, 'Cash', 200.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(405, 'INV-20260414221434-565', NULL, NULL, '208', NULL, NULL, 500.00, 'paid', '2026-04-14 20:14:34', NULL, 'Mpesa', 500.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(406, 'INV-20260415085311-196', 138, NULL, NULL, NULL, NULL, 0.00, '', '2026-04-15 06:53:11', NULL, 'Mpesa', 700.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(416, 'INV-20260415161101-813', NULL, NULL, '218', NULL, NULL, 50.00, 'paid', '2026-04-15 14:11:01', NULL, 'Cash', 50.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(417, 'INV-20260416161306-227', NULL, NULL, '219', NULL, NULL, 200.00, 'paid', '2026-04-16 14:13:06', NULL, 'Mpesa', 200.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(418, 'INV-20260416182114-552', 140, NULL, NULL, NULL, NULL, 3850.00, 'unpaid', '2026-04-16 16:21:14', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(419, 'INV-20260417152519-747', 141, NULL, NULL, NULL, NULL, 1670.00, 'unpaid', '2026-04-17 13:25:19', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(420, 'INV-20260417170130-559', 142, NULL, NULL, NULL, NULL, 4450.00, 'unpaid', '2026-04-17 15:01:30', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(421, 'INV-20260417170940-501', 142, NULL, NULL, NULL, NULL, 0.00, '', '2026-04-17 15:09:40', NULL, 'Mpesa', 800.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(422, 'INV-20260417174740-889', 141, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-04-17 15:47:40', NULL, 'Cash', 2950.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(424, 'INV-20260417175114-642', NULL, NULL, '221', NULL, NULL, 50.00, 'paid', '2026-04-17 15:51:14', NULL, 'Cash', 50.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(425, 'INV-20260418145610-955', 143, NULL, NULL, NULL, NULL, 6450.00, 'unpaid', '2026-04-18 12:56:10', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(426, 'INV-20260418171346-322', 142, NULL, NULL, NULL, NULL, 0.00, '', '2026-04-18 15:13:46', NULL, 'Mpesa', 850.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(427, 'INV-20260420174223-309', 142, NULL, NULL, NULL, NULL, 0.00, '', '2026-04-20 15:42:23', NULL, 'Mpesa', 850.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(428, 'INV-20260421174404-874', 143, NULL, NULL, NULL, NULL, 0.00, '', '2026-04-21 15:44:04', NULL, 'Mpesa', 2500.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(429, 'INV-20260421174607-637', 142, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-04-21 15:46:07', NULL, 'Mpesa', 2450.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(430, 'INV-20260423122123-676', 145, NULL, NULL, NULL, NULL, 2700.00, 'unpaid', '2026-04-23 10:21:23', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(431, 'INV-20260423122552-497', 145, NULL, NULL, NULL, NULL, 0.00, '', '2026-04-23 10:25:52', NULL, 'Cash', 1000.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(432, 'INV-20260424105915-786', 146, NULL, NULL, NULL, NULL, 6300.00, 'unpaid', '2026-04-24 08:59:15', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(433, 'INV-20260425192817-815', 146, NULL, NULL, NULL, NULL, 0.00, '', '2026-04-25 17:28:18', NULL, 'Cash', 2000.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(434, 'INV-20260425192922-315', NULL, NULL, '222', NULL, NULL, 100.00, 'paid', '2026-04-25 17:29:22', NULL, 'Cash', 100.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(435, 'INV-20260425192944-199', NULL, NULL, '223', NULL, NULL, 20.00, 'paid', '2026-04-25 17:29:44', NULL, 'Cash', 20.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(436, 'INV-20260425193013-431', NULL, NULL, '224', NULL, NULL, 500.00, 'paid', '2026-04-25 17:30:13', NULL, 'Mpesa', 500.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(437, 'INV-20260425193043-920', NULL, NULL, '225', NULL, NULL, 500.00, 'paid', '2026-04-25 17:30:43', NULL, 'Mpesa', 500.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(438, 'INV-20260425202339-510', 147, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-04-25 18:23:39', NULL, 'Mpesa', 200.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(439, 'INV-20260426145720-895', 148, NULL, NULL, NULL, NULL, 6100.00, 'unpaid', '2026-04-26 12:57:20', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(440, 'INV-20260426171314-802', 148, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-04-26 15:13:14', NULL, 'Cash', 2800.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(441, 'INV-20260428122654-887', 150, NULL, NULL, NULL, NULL, 2850.00, 'unpaid', '2026-04-28 10:26:54', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(442, 'INV-20260428124144-844', 151, NULL, NULL, NULL, NULL, 2850.00, 'unpaid', '2026-04-28 10:41:44', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(443, 'INV-20260428124400-983', 151, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-04-28 10:44:00', NULL, 'Cash', 3050.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(444, 'INV-20260429185127-290', 152, NULL, NULL, NULL, NULL, 2150.00, 'unpaid', '2026-04-29 16:51:27', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(445, 'INV-20260429185458-784', 152, NULL, NULL, NULL, NULL, 0.00, '', '2026-04-29 16:54:58', NULL, 'Mpesa', 1000.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(446, 'INV-20260430081851-487', 145, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-04-30 06:18:51', NULL, 'Cash', 1900.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(447, 'INV-20260430094159-612', 153, NULL, NULL, NULL, NULL, 11600.00, 'unpaid', '2026-04-30 07:41:59', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(448, 'INV-20260502085858-992', NULL, NULL, '226', NULL, NULL, 250.00, 'paid', '2026-05-02 06:58:58', NULL, 'Mpesa', 250.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(449, 'INV-20260502175813-441', 154, NULL, NULL, NULL, NULL, 5600.00, 'unpaid', '2026-05-02 15:58:13', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(450, 'INV-20260502181406-486', 154, NULL, NULL, NULL, NULL, 0.00, '', '2026-05-02 16:14:06', NULL, 'Cash', 2300.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(451, 'INV-20260502181533-669', 154, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-05-02 16:15:33', NULL, 'Cash', 2300.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(452, 'INV-20260502182621-163', 154, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-05-02 16:26:21', NULL, 'Cash', 2300.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(453, 'INV-20260503133826-576', 155, NULL, NULL, NULL, NULL, 3900.00, 'unpaid', '2026-05-03 11:38:26', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(454, 'INV-20260503141058-480', 156, NULL, NULL, NULL, NULL, 2350.00, 'unpaid', '2026-05-03 12:10:58', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(455, 'INV-20260503142939-323', 156, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-05-03 12:29:39', NULL, 'Mpesa', 1000.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(456, 'INV-20260503143549-798', 157, NULL, NULL, NULL, NULL, 4150.00, 'unpaid', '2026-05-03 12:35:49', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(457, 'INV-20260503152043-796', 157, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-05-03 13:20:43', NULL, 'Cash', 2850.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(458, 'INV-20260503173153-414', 155, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-05-03 15:31:53', NULL, 'Mpesa', 3300.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(459, 'INV-20260503173633-498', 158, NULL, NULL, NULL, NULL, 3500.00, 'unpaid', '2026-05-03 15:36:33', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(460, 'INV-20260503190819-763', 158, NULL, NULL, NULL, NULL, 0.00, '', '2026-05-03 17:08:19', NULL, 'Mpesa', 1500.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(461, 'INV-20260504162659-709', 159, NULL, NULL, NULL, NULL, 3100.00, 'unpaid', '2026-05-04 14:26:59', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(462, 'INV-20260504163020-115', 159, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-05-04 14:30:20', NULL, 'Mpesa', 3300.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(463, 'INV-20260504163612-327', 160, NULL, NULL, NULL, NULL, 1600.00, 'unpaid', '2026-05-04 14:36:12', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(464, 'INV-20260504163808-252', 160, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-05-04 14:38:09', NULL, 'Mpesa', 1800.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(465, 'INV-20260507195643-129', 161, NULL, NULL, NULL, NULL, 2750.00, 'unpaid', '2026-05-07 17:56:43', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(466, 'INV-20260507202634-194', 161, NULL, NULL, NULL, NULL, 0.00, '', '2026-05-07 18:26:34', NULL, 'Mpesa', 1000.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(467, 'INV-20260507203158-410', 161, NULL, NULL, NULL, NULL, 0.00, '', '2026-05-07 18:31:58', NULL, 'Mpesa', 1000.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(468, 'INV-20260508094925-607', 162, NULL, NULL, NULL, NULL, 2850.00, 'unpaid', '2026-05-08 07:49:25', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(469, 'INV-20260508103527-310', 162, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-05-08 08:35:27', NULL, 'Mpesa', 2300.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(470, 'INV-20260508182804-104', 163, NULL, NULL, NULL, NULL, 2700.00, 'unpaid', '2026-05-08 16:28:04', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(471, 'INV-20260508190528-139', 163, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-05-08 17:05:28', NULL, 'Mpesa', 1800.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(472, 'INV-20260509091413-167', 111, NULL, NULL, NULL, NULL, 0.00, '', '2026-05-09 07:14:13', NULL, 'Mpesa', 20000.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(473, 'INV-20260512165701-948', 164, NULL, NULL, NULL, NULL, 300.00, 'unpaid', '2026-05-12 14:57:01', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(474, 'INV-20260512165739-161', 164, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-05-12 14:57:39', NULL, 'Cash', 800.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(475, 'INV-20260513084855-166', 167, NULL, NULL, NULL, NULL, 6950.00, 'unpaid', '2026-05-13 06:48:55', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(476, 'INV-20260513102216-494', 167, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-05-13 08:22:16', NULL, 'Cash', 1300.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(477, 'INV-20260513102217-878', 167, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-05-13 08:22:17', NULL, 'Cash', 1300.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(478, 'INV-20260513102218-462', 167, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-05-13 08:22:18', NULL, 'Cash', 1300.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(479, 'INV-20260516103630-930', 169, NULL, NULL, NULL, NULL, 9700.00, 'unpaid', '2026-05-16 08:36:30', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(480, 'INV-20260516103735-507', 169, NULL, NULL, NULL, NULL, 0.00, '', '2026-05-16 08:37:35', NULL, 'Mpesa', 2000.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(481, 'INV-20260516150509-686', 169, NULL, NULL, NULL, NULL, 0.00, '', '2026-05-16 13:05:09', NULL, 'Cash', 900.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(482, 'INV-20260516150637-534', 169, NULL, NULL, NULL, NULL, 0.00, '', '2026-05-16 13:06:37', NULL, 'Cash', 6100.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(483, 'INV-20260516150638-867', 169, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-05-16 13:06:38', NULL, 'Cash', 6100.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(484, 'INV-20260521214535-709', 170, NULL, NULL, NULL, NULL, 3100.00, 'unpaid', '2026-05-21 19:45:35', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(485, 'INV-20260524201638-928', 171, NULL, NULL, NULL, NULL, 2500.00, 'unpaid', '2026-05-24 18:16:38', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(486, 'INV-20260524204353-318', 171, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-05-24 18:43:53', NULL, 'Mpesa', 1300.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(487, 'INV-20260529150335-636', 172, NULL, NULL, NULL, NULL, 3500.00, 'unpaid', '2026-05-29 13:03:35', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(488, 'INV-20260529150731-692', 172, NULL, NULL, NULL, NULL, 0.00, '', '2026-05-29 13:07:31', NULL, 'Mpesa', 1500.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(489, 'INV-20260530173723-810', 173, NULL, NULL, NULL, NULL, 4400.00, 'unpaid', '2026-05-30 15:37:23', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(490, 'INV-20260530204119-831', 173, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-05-30 18:41:19', NULL, 'Mpesa', 4750.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(491, 'INV-20260531112128-766', 179, NULL, NULL, NULL, NULL, 3750.00, 'unpaid', '2026-05-31 09:21:28', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(492, 'INV-20260531120339-251', 179, NULL, NULL, NULL, NULL, 0.00, '', '2026-05-31 10:03:39', NULL, 'Cash', 2000.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(493, 'INV-20260531203600-221', 180, NULL, NULL, NULL, NULL, 3075.00, 'unpaid', '2026-05-31 18:36:00', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(494, 'INV-20260531205420-443', 180, NULL, NULL, NULL, NULL, 0.00, '', '2026-05-31 18:54:20', NULL, 'Mpesa', 1350.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(495, 'INV-20260601111418-378', 181, NULL, NULL, NULL, NULL, 1100.00, 'unpaid', '2026-06-01 09:14:18', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(496, 'INV-20260605121022-563', 182, NULL, NULL, NULL, NULL, 2400.00, 'unpaid', '2026-06-05 10:10:22', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(497, 'INV-20260605121229-909', 182, NULL, NULL, NULL, NULL, 0.00, '', '2026-06-05 10:12:29', NULL, 'Cash', 1500.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(498, 'INV-20260607115934-915', 183, NULL, NULL, NULL, NULL, 2800.00, 'unpaid', '2026-06-07 09:59:34', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(499, 'INV-20260607141947-418', 184, NULL, NULL, NULL, NULL, 3650.00, 'unpaid', '2026-06-07 12:19:47', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(500, 'INV-20260607160552-120', 184, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-06-07 14:05:52', NULL, 'Cash', 1950.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(501, 'INV-20260608165109-163', 186, NULL, NULL, NULL, NULL, 2750.00, 'unpaid', '2026-06-08 14:51:09', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(502, 'INV-20260608173656-760', 186, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-06-08 15:36:56', NULL, 'Mpesa', 2950.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(503, 'INV-20260608180922-900', 185, NULL, NULL, NULL, NULL, 16700.00, 'unpaid', '2026-06-08 16:09:22', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(504, 'INV-20260608180953-267', 185, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-06-08 16:09:53', NULL, 'Cash', 8900.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(505, 'INV-20260609082246-913', 188, NULL, NULL, NULL, NULL, 3600.00, 'unpaid', '2026-06-09 06:22:46', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(506, 'INV-20260609085252-894', 188, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-06-09 06:52:52', NULL, 'Mpesa', 3800.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(507, 'INV-20260610093713-118', 190, NULL, NULL, NULL, NULL, 5300.00, 'unpaid', '2026-06-10 07:37:13', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(508, 'INV-20260610111223-513', 190, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-06-10 09:12:23', NULL, 'Mpesa', 5600.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(509, 'INV-20260612170909-697', 191, NULL, NULL, NULL, NULL, 3800.00, 'unpaid', '2026-06-12 15:09:09', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(510, 'INV-20260614073147-133', 193, NULL, NULL, NULL, NULL, 2350.00, 'unpaid', '2026-06-14 05:31:47', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(511, 'INV-20260614073522-154', 193, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-06-14 05:35:22', NULL, 'Cash', 2850.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(512, 'INV-20260614081424-557', 194, NULL, NULL, NULL, NULL, 8500.00, 'unpaid', '2026-06-14 06:14:24', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(513, 'INV-20260614092524-306', NULL, NULL, '227', NULL, NULL, 50.00, 'paid', '2026-06-14 07:25:24', NULL, 'Cash', 50.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(514, 'INV-20260616221557-100', 195, NULL, NULL, NULL, NULL, 1825.00, 'unpaid', '2026-06-16 20:15:57', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(515, 'INV-20260617061929-946', 195, NULL, NULL, NULL, NULL, 0.00, '', '2026-06-17 04:19:29', NULL, 'Mpesa', 1000.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(516, 'INV-20260618113211-729', 191, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-06-18 09:32:12', NULL, 'Cash', 3600.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(517, 'INV-20260618113608-434', 196, NULL, NULL, NULL, NULL, 3200.00, 'unpaid', '2026-06-18 09:36:08', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(518, 'INV-20260618113800-526', 196, NULL, NULL, NULL, NULL, 0.00, '', '2026-06-18 09:38:00', NULL, 'Mpesa', 2000.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(519, 'INV-20260618113845-148', 196, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-06-18 09:38:45', NULL, 'Cash', 1400.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(520, 'INV-20260618114006-713', NULL, NULL, '228', NULL, NULL, 250.00, 'paid', '2026-06-18 09:40:06', NULL, 'Cash', 250.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(521, 'INV-20260620071614-419', 197, NULL, NULL, NULL, NULL, 3000.00, 'unpaid', '2026-06-20 05:16:14', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(522, 'INV-20260620074815-985', 197, NULL, NULL, NULL, NULL, 0.00, '', '2026-06-20 05:48:15', NULL, 'Cash', 2800.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(523, 'INV-20260620223040-422', 198, NULL, NULL, NULL, NULL, 3700.00, 'unpaid', '2026-06-20 20:30:40', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(524, 'INV-20260620233547-837', 198, NULL, NULL, NULL, NULL, 0.00, '', '2026-06-20 21:35:47', NULL, 'Mpesa', 1000.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(525, 'INV-20260622172608-503', 199, NULL, NULL, NULL, NULL, 2850.00, 'unpaid', '2026-06-22 15:26:08', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(526, 'INV-20260622173457-213', 199, NULL, NULL, NULL, NULL, 0.00, '', '2026-06-22 15:34:57', NULL, 'Cash', 1500.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(527, 'INV-20260624203919-751', 200, NULL, NULL, NULL, NULL, 5800.00, 'unpaid', '2026-06-24 18:39:19', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(528, 'INV-20260624220738-161', 200, NULL, NULL, NULL, NULL, 0.00, '', '2026-06-24 20:07:38', NULL, 'Mpesa', 3000.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(529, 'INV-20260626190926-860', 201, NULL, NULL, NULL, NULL, 2950.00, 'unpaid', '2026-06-26 17:09:26', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(530, 'INV-20260626193233-922', 202, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-06-26 17:32:33', NULL, 'Cash', 200.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(531, 'INV-20260626193306-578', 201, NULL, NULL, NULL, NULL, 0.00, '', '2026-06-26 17:33:06', NULL, 'Cash', 1350.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(532, 'INV-20260627074419-482', 203, NULL, NULL, NULL, NULL, 4650.00, 'unpaid', '2026-06-27 05:44:19', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(533, 'INV-20260627075116-723', 203, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-06-27 05:51:16', NULL, 'Cash', 4050.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(534, 'INV-20260627144751-675', 204, NULL, NULL, NULL, NULL, 2900.00, 'unpaid', '2026-06-27 12:47:51', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(535, 'INV-20260627150040-976', 204, NULL, NULL, NULL, NULL, 0.00, '', '2026-06-27 13:00:40', NULL, 'Mpesa', 500.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(536, 'INV-20260627150203-135', 204, NULL, NULL, NULL, NULL, 0.00, '', '2026-06-27 13:02:03', NULL, 'Cash', 2300.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(537, 'INV-20260627150442-160', NULL, NULL, '229', NULL, NULL, 10.00, 'paid', '2026-06-27 13:04:42', NULL, 'Cash', 10.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(538, 'INV-20260628221255-897', NULL, NULL, '230', NULL, NULL, 200.00, 'paid', '2026-06-28 20:12:55', NULL, 'Mpesa', 200.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(539, 'INV-20260701082341-734', 205, NULL, NULL, NULL, NULL, 2150.00, 'unpaid', '2026-07-01 06:23:41', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(540, 'INV-20260701082442-163', 205, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-07-01 06:24:42', NULL, 'Cash', 2350.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(541, 'INV-20260702194827-284', NULL, NULL, '231', NULL, NULL, 100.00, 'paid', '2026-07-02 17:48:27', NULL, 'Cash', 100.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(542, 'INV-20260702210958-420', NULL, NULL, '232', NULL, NULL, 100.00, 'paid', '2026-07-02 19:09:58', NULL, 'Mpesa', 100.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(543, 'INV-20260703082427-440', 206, NULL, NULL, NULL, NULL, 4000.00, 'unpaid', '2026-07-03 06:24:27', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(544, 'INV-20260703084001-404', 206, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-07-03 06:40:01', NULL, 'Mpesa', 3900.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(545, 'INV-20260704151233-785', 207, NULL, NULL, NULL, NULL, 2000.00, 'unpaid', '2026-07-04 13:12:33', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(546, 'INV-20260704154156-625', 207, NULL, NULL, NULL, NULL, 0.00, '', '2026-07-04 13:41:56', NULL, 'Mpesa', 1000.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(547, 'INV-20260704154157-960', 207, NULL, NULL, NULL, NULL, 0.00, '', '2026-07-04 13:41:57', NULL, 'Mpesa', 1000.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(548, 'INV-20260705112543-828', 208, NULL, NULL, NULL, NULL, 4900.00, 'unpaid', '2026-07-05 09:25:43', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(549, 'INV-20260705153313-764', 208, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-07-05 13:33:13', NULL, 'Mpesa', 5900.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(550, 'INV-20260705153314-173', 208, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-07-05 13:33:14', NULL, 'Mpesa', 5900.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(551, 'INV-20260705161603-699', 208, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-07-05 14:16:03', NULL, 'Mpesa', 5900.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(552, 'INV-20260710091324-907', 209, NULL, NULL, NULL, NULL, 650.00, 'unpaid', '2026-07-10 07:13:24', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(553, 'INV-20260710091825-209', 209, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-07-10 07:18:25', NULL, 'Mpesa', 850.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(554, 'INV-20260710091826-590', 209, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-07-10 07:18:26', NULL, 'Mpesa', 850.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(555, 'INV-20260710091828-939', 209, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-07-10 07:18:28', NULL, 'Mpesa', 850.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(556, 'INV-20260710091828-608', 209, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-07-10 07:18:28', NULL, 'Mpesa', 850.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(557, 'INV-20260710091829-821', 209, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-07-10 07:18:29', NULL, 'Mpesa', 850.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(558, 'INV-20260712122546-511', 215, NULL, NULL, NULL, NULL, 10500.00, 'unpaid', '2026-07-12 10:25:46', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(559, 'INV-20260712125459-578', 216, NULL, NULL, NULL, NULL, 4150.00, 'unpaid', '2026-07-12 10:54:59', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(560, 'INV-20260712133850-974', 215, NULL, NULL, NULL, NULL, 0.00, '', '2026-07-12 11:38:50', NULL, 'Mpesa', 4200.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(561, 'INV-20260712134720-664', 216, NULL, NULL, NULL, NULL, 0.00, '', '2026-07-12 11:47:20', NULL, 'Bank', 2000.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(562, 'INV-20260712152803-243', 217, NULL, NULL, NULL, NULL, 4200.00, 'unpaid', '2026-07-12 13:28:03', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(563, 'INV-20260718101434-208', 218, NULL, NULL, NULL, NULL, 3900.00, 'unpaid', '2026-07-18 08:14:34', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(564, 'INV-20260718174648-246', 219, NULL, NULL, NULL, NULL, 350.00, 'unpaid', '2026-07-18 15:46:48', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(565, 'INV-20260718181746-925', 219, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-07-18 16:17:46', NULL, 'Mpesa', 550.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(566, 'INV-20260718182234-983', NULL, NULL, '233', NULL, NULL, 300.00, 'paid', '2026-07-18 16:22:34', NULL, 'Mpesa', 300.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(567, 'INV-20260725201948-223', 220, NULL, NULL, NULL, NULL, 600.00, 'unpaid', '2026-07-25 18:19:48', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(568, 'INV-20260725211052-494', 220, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-07-25 19:10:52', NULL, 'Cash', 800.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(569, 'INV-20260725211053-749', 220, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-07-25 19:10:53', NULL, 'Cash', 800.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(570, 'INV-20260727180617-655', 221, NULL, NULL, NULL, NULL, 420.00, 'unpaid', '2026-07-27 16:06:17', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(571, 'INV-20260728193453-431', 222, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-07-28 17:34:53', NULL, 'Cash', 200.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(572, 'INV-20260730173131-381', 223, NULL, NULL, NULL, NULL, 7470.00, 'unpaid', '2026-07-30 15:31:31', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(573, 'INV-20260730193856-182', 223, NULL, NULL, NULL, NULL, 0.00, '', '2026-07-30 17:38:56', NULL, 'Mpesa', 1500.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(574, 'INV-20260801141751-707', 224, NULL, NULL, NULL, NULL, 2650.00, 'unpaid', '2026-08-01 12:17:51', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(575, 'INV-20260804070042-868', 225, NULL, NULL, NULL, NULL, 6500.00, 'unpaid', '2026-08-04 05:00:42', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(576, 'INV-20260804113935-886', 225, NULL, NULL, NULL, NULL, 0.00, '', '2026-08-04 09:39:35', NULL, 'Mpesa', 900.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(577, 'INV-20260805202507-115', 226, NULL, NULL, NULL, NULL, 2950.00, 'unpaid', '2026-08-05 18:25:07', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(578, 'INV-20260805205504-442', 226, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-08-05 18:55:04', NULL, 'Mpesa', 3150.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(579, 'INV-20260805213821-890', 227, NULL, NULL, NULL, NULL, 3300.00, 'unpaid', '2026-08-05 19:38:21', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(580, 'INV-20260805214132-396', 227, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-08-05 19:41:32', NULL, 'Cash', 3500.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(581, 'INV-20260806105659-378', 228, NULL, NULL, NULL, NULL, 2250.00, 'unpaid', '2026-08-06 08:56:59', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(582, 'INV-20260806183719-307', 229, NULL, NULL, NULL, NULL, 3710.01, 'unpaid', '2026-08-06 16:37:19', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(583, 'INV-20260807052819-176', 230, NULL, NULL, NULL, NULL, 1600.00, 'unpaid', '2026-08-07 03:28:19', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(584, 'INV-20260807063305-380', 230, NULL, NULL, NULL, NULL, 0.00, '', '2026-08-07 04:33:05', NULL, 'Cash', 1000.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(585, 'INV-20260807063549-163', 229, NULL, NULL, NULL, NULL, 0.00, '', '2026-08-07 04:35:49', NULL, 'Cash', 2000.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(586, 'INV-20260808212639-229', 231, NULL, NULL, NULL, NULL, 2400.00, 'unpaid', '2026-08-08 19:26:39', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(587, 'INV-20260808222250-904', 231, NULL, NULL, NULL, NULL, 0.00, '', '2026-08-08 20:22:50', NULL, 'Mpesa', 800.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(588, 'INV-20260812151258-157', 234, NULL, NULL, NULL, NULL, 3200.00, 'unpaid', '2026-08-12 13:12:58', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(589, 'INV-20260813153356-794', 235, NULL, NULL, NULL, NULL, 3850.00, 'unpaid', '2026-08-13 13:33:56', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(590, 'INV-20260814072728-398', 236, NULL, NULL, NULL, NULL, 2900.00, 'unpaid', '2026-08-14 05:27:28', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(591, 'INV-20260814075206-871', 236, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-08-14 05:52:06', NULL, 'Mpesa', 3100.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(592, 'INV-20260814075206-243', 236, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-08-14 05:52:06', NULL, 'Mpesa', 3100.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(593, 'INV-20260814114854-431', 237, NULL, NULL, NULL, NULL, 1800.00, 'unpaid', '2026-08-14 09:48:54', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(594, 'INV-20260814120741-988', 237, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-08-14 10:07:41', NULL, 'Bank', 850.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(595, 'INV-20260814144450-215', 238, NULL, NULL, NULL, NULL, 1450.00, 'unpaid', '2026-08-14 12:44:50', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(596, 'INV-20260814145406-649', 238, NULL, NULL, NULL, NULL, 0.00, '', '2026-08-14 12:54:06', NULL, 'Mpesa', 400.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(597, 'INV-20260814175456-193', 143, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-08-14 15:54:56', NULL, 'Cash', 1950.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(598, 'INV-20260819084603-530', 241, NULL, NULL, NULL, NULL, 3100.00, 'unpaid', '2026-08-19 06:46:03', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(599, 'INV-20260819093017-345', 241, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-08-19 07:30:17', NULL, 'Mpesa', 2500.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(600, 'INV-20260819164403-477', 242, NULL, NULL, NULL, NULL, 2600.00, 'unpaid', '2026-08-19 14:44:03', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(601, 'INV-20260820162517-856', 241, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-08-20 14:25:17', NULL, 'Mpesa', 800.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(602, 'INV-20260821181344-272', 243, NULL, NULL, NULL, NULL, 2300.00, 'unpaid', '2026-08-21 16:13:44', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(603, 'INV-20260821181732-956', 243, NULL, NULL, NULL, NULL, 0.00, '', '2026-08-21 16:17:32', NULL, 'Mpesa', 1000.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(604, 'INV-20260821182237-592', NULL, NULL, '234', NULL, NULL, 20.00, 'paid', '2026-08-21 16:22:37', NULL, 'Cash', 20.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(605, 'INV-20260821201349-862', 244, NULL, NULL, NULL, NULL, 4150.00, 'unpaid', '2026-08-21 18:13:49', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(606, 'INV-20260821220222-139', 244, NULL, NULL, NULL, NULL, 0.00, '', '2026-08-21 20:02:22', NULL, 'Cash', 600.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(607, 'INV-20260822120024-456', 245, NULL, NULL, NULL, NULL, 9920.00, 'unpaid', '2026-08-22 10:00:24', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(608, 'INV-20260822165631-383', 245, NULL, NULL, NULL, NULL, 0.00, '', '2026-08-22 14:56:31', NULL, 'Mpesa', 4000.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(609, 'INV-20260822165633-994', 245, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-08-22 14:56:33', NULL, 'Mpesa', 4000.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(610, 'INV-20260823092332-169', 246, NULL, NULL, NULL, NULL, 2950.00, 'unpaid', '2026-08-23 07:23:32', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(611, 'INV-20260825072142-838', 249, NULL, NULL, NULL, NULL, 2790.00, 'unpaid', '2026-08-25 05:21:42', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(612, 'INV-20260825074254-968', 249, NULL, NULL, NULL, NULL, 0.00, '', '2026-08-25 05:42:54', NULL, 'Cash', 500.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(613, 'INV-20260825182236-769', 248, NULL, NULL, NULL, NULL, 9300.00, 'unpaid', '2026-08-25 16:22:36', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(614, 'INV-20260826075549-605', 248, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-08-26 05:55:49', NULL, 'Mpesa', 9500.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(615, 'INV-20260826131745-759', 250, NULL, NULL, NULL, NULL, 4850.00, 'unpaid', '2026-08-26 11:17:45', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(616, 'INV-20260826133349-899', 251, NULL, NULL, NULL, NULL, 2200.00, 'unpaid', '2026-08-26 11:33:49', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(617, 'INV-20260826160735-995', 250, NULL, NULL, NULL, NULL, 0.00, '', '2026-08-26 14:07:35', NULL, 'Mpesa', 4000.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(618, 'INV-20260826162012-253', 252, NULL, NULL, NULL, NULL, 3850.00, 'unpaid', '2026-08-26 14:20:12', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(619, 'INV-20260826162244-521', 252, NULL, NULL, NULL, NULL, 0.00, '', '2026-08-26 14:22:44', NULL, 'Mpesa', 1200.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(620, 'INV-20260826185038-445', 253, NULL, NULL, NULL, NULL, 1000.00, 'unpaid', '2026-08-26 16:50:38', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(621, 'INV-20260826185238-187', 253, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-08-26 16:52:38', NULL, 'Mpesa', 700.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(622, 'INV-20260827070806-774', 249, NULL, NULL, NULL, NULL, 0.00, '', '2026-08-27 05:08:06', NULL, 'Mpesa', 1500.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(623, 'INV-20260827093249-500', 254, NULL, NULL, NULL, NULL, 200.00, 'unpaid', '2026-08-27 07:32:49', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(624, 'INV-20260827093258-240', 254, NULL, NULL, NULL, NULL, 0.00, 'paid', '2026-08-27 07:32:58', NULL, 'Mpesa', 400.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(627, 'INV-20260827102139-317', 32, NULL, NULL, NULL, NULL, 200.00, 'paid', '2026-08-27 08:21:39', NULL, 'Mpesa', 200.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid'),
(628, 'INV-20260827104018-983', 255, NULL, NULL, NULL, NULL, 2000.00, 'unpaid', '2026-08-27 08:40:18', NULL, NULL, 0.00, NULL, 1, 0.00, 0.00, 0.00, 0.00, 'Unpaid');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_financial_allocations`
--

CREATE TABLE `invoice_financial_allocations` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `allocation_type` enum('Patient','SHA','Insurance','Corporate','Writeoff') NOT NULL,
  `payer_id` int(11) DEFAULT NULL,
  `patient_coverage_id` int(11) DEFAULT NULL,
  `claim_id` int(11) DEFAULT NULL,
  `expected_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `approved_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `settled_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `balance_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `allocation_status` enum('Open','Partially Settled','Settled','Denied','Written Off') NOT NULL DEFAULT 'Open',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `med_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `qty` int(11) DEFAULT 1,
  `unit_price` decimal(12,2) DEFAULT 0.00,
  `total` decimal(14,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `item_type` varchar(50) NOT NULL DEFAULT 'pharmacy',
  `source` enum('pharmacy','lab','radiology','manual') DEFAULT 'manual',
  `quantity` int(11) DEFAULT 1,
  `price` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `med_id`, `description`, `qty`, `unit_price`, `total`, `created_at`, `item_type`, `source`, `quantity`, `price`) VALUES
(34, 31, NULL, 'Amoxiclav 625mg', 1, 1000.00, 1000.00, '2026-02-24 14:12:10', 'pharmacy', 'pharmacy', 1, 0.00),
(35, 33, NULL, 'Amoxiclav 625mg', 1, 1000.00, 1000.00, '2026-02-24 14:24:19', 'pharmacy', 'pharmacy', 1, 0.00),
(36, 33, NULL, 'Benzylpwenicillin (inj)', 1, 400.00, 400.00, '2026-02-24 14:24:19', 'pharmacy', 'pharmacy', 1, 0.00),
(37, 34, NULL, 'Patient Payment - Mpesa', 1, 0.00, 100.00, '2026-02-24 14:25:05', 'pharmacy', 'manual', 1, 100.00),
(38, 35, NULL, 'Patient Payment - Cash', 1, 0.00, 300.00, '2026-02-24 14:36:40', 'pharmacy', 'manual', 1, 300.00),
(39, 36, NULL, 'Needles ( G23)', 1, 500.00, 500.00, '2026-02-24 15:08:00', 'pharmacy', 'pharmacy', 1, 0.00),
(40, 37, NULL, 'Patient Payment - Cash', 1, 0.00, 200.00, '2026-02-25 12:52:39', 'pharmacy', 'manual', 1, 200.00),
(41, 38, NULL, 'Amoxicilin 250mg', 1, 20.00, 20.00, '2026-02-25 12:55:46', 'pharmacy', 'pharmacy', 1, 0.00),
(42, 39, NULL, 'Patient Payment - Mpesa', 1, 0.00, 200.00, '2026-02-25 13:04:59', 'pharmacy', 'manual', 1, 200.00),
(43, 40, NULL, 'Cetizine', 5, 10.00, 50.00, '2026-02-25 19:32:11', 'pharmacy', 'pharmacy', 1, 0.00),
(44, 41, NULL, 'Prednisolone 5mg  (cosmos0', 1, 10.00, 10.00, '2026-02-25 19:33:14', 'pharmacy', 'pharmacy', 1, 0.00),
(45, 42, NULL, 'Metronidazole  600ml', 1, 100.00, 100.00, '2026-02-26 05:17:19', 'pharmacy', 'pharmacy', 1, 0.00),
(46, 43, NULL, 'Metronidazole 400mg', 2, 15.00, 30.00, '2026-02-26 05:17:58', 'pharmacy', 'pharmacy', 1, 0.00),
(47, 44, NULL, 'Patient Payment - Cash', 1, 0.00, 6725.00, '2026-02-26 07:03:34', 'pharmacy', 'manual', 1, 6725.00),
(48, 45, NULL, 'Patient Payment - Cash', 1, 0.00, 6725.00, '2026-02-26 08:00:17', 'pharmacy', 'manual', 1, 6725.00),
(49, 46, NULL, 'Patient Payment - Cash', 1, 0.00, 6725.00, '2026-02-26 08:02:45', 'pharmacy', 'manual', 1, 6725.00),
(50, 47, NULL, 'Patient Payment - Cash', 1, 0.00, 6725.00, '2026-02-26 08:16:10', 'pharmacy', 'manual', 1, 6725.00),
(51, 48, NULL, 'Patient Payment - Mpesa', 1, 0.00, 1000.00, '2026-02-26 08:30:35', 'pharmacy', 'manual', 1, 1000.00),
(52, 49, NULL, 'Patient Payment - Cash', 1, 0.00, 6725.00, '2026-02-26 08:36:32', 'pharmacy', 'manual', 1, 6725.00),
(53, 50, NULL, 'Azithromycin 500mg', 1, 300.00, 300.00, '2026-02-26 13:53:40', 'pharmacy', 'pharmacy', 1, 0.00),
(54, 51, NULL, 'Chlopheniramine 60ml', 1, 100.00, 100.00, '2026-02-26 13:55:06', 'pharmacy', 'pharmacy', 1, 0.00),
(55, 52, NULL, 'Chlopheniramine 60ml', 1, 100.00, 100.00, '2026-02-26 13:55:42', 'pharmacy', 'pharmacy', 1, 0.00),
(56, 53, NULL, 'PIRITON', 10, 5.00, 50.00, '2026-02-26 13:57:37', 'pharmacy', 'pharmacy', 1, 0.00),
(57, 54, NULL, 'Ibuprofen 400mg', 10, 10.00, 100.00, '2026-02-26 13:58:18', 'pharmacy', 'pharmacy', 1, 0.00),
(58, 55, NULL, 'Prednisolone 5mg  (cosmos0', 10, 10.00, 100.00, '2026-02-26 13:59:05', 'pharmacy', 'pharmacy', 1, 0.00),
(59, 56, NULL, 'Amoxicilin 60mls', 1, 150.00, 150.00, '2026-02-26 14:00:16', 'pharmacy', 'pharmacy', 1, 0.00),
(60, 57, NULL, 'Shaltoux 100mls', 1, 500.00, 500.00, '2026-02-26 14:01:57', 'pharmacy', 'pharmacy', 1, 0.00),
(61, 58, NULL, 'ABZ  400mg', 1, 100.00, 100.00, '2026-02-26 14:42:22', 'pharmacy', 'pharmacy', 1, 0.00),
(62, 59, NULL, 'Cypon 100mls', 1, 500.00, 500.00, '2026-02-26 16:58:54', 'pharmacy', 'pharmacy', 1, 0.00),
(63, 60, NULL, 'Amoxicilin 60mls', 1, 150.00, 150.00, '2026-02-26 17:04:52', 'pharmacy', 'pharmacy', 1, 0.00),
(64, 61, NULL, 'Patient Payment - Mpesa', 1, 0.00, 1000.00, '2026-02-26 17:25:28', 'pharmacy', 'manual', 1, 1000.00),
(65, 62, NULL, 'Gacet 125mg', 1, 100.00, 100.00, '2026-02-26 17:27:18', 'pharmacy', 'pharmacy', 1, 0.00),
(66, 63, NULL, 'Femiplan pills', 1, 120.00, 120.00, '2026-02-26 17:28:06', 'pharmacy', 'pharmacy', 1, 0.00),
(67, 64, NULL, 'Cetizine', 5, 10.00, 50.00, '2026-02-26 18:53:45', 'pharmacy', 'pharmacy', 1, 0.00),
(68, 65, NULL, 'diprofos', 1, 1200.00, 1200.00, '2026-02-27 05:26:30', 'pharmacy', 'pharmacy', 1, 0.00),
(69, 66, NULL, 'Patient Payment - Mpesa', 1, 0.00, 1500.00, '2026-02-27 07:19:22', 'pharmacy', 'manual', 1, 1500.00),
(70, 67, NULL, 'Amoxicilin 500mg', 5, 20.00, 100.00, '2026-02-27 14:08:58', 'pharmacy', 'pharmacy', 1, 0.00),
(71, 68, NULL, 'Acetal mr', 4, 50.00, 200.00, '2026-02-27 14:10:49', 'pharmacy', 'pharmacy', 1, 0.00),
(72, 69, NULL, 'Patient Payment - Cash', 1, 0.00, 100.00, '2026-02-27 15:54:55', 'pharmacy', 'manual', 1, 100.00),
(73, 70, NULL, 'Patient Payment - Mpesa', 1, 0.00, 2200.00, '2026-02-27 17:20:41', 'pharmacy', 'manual', 1, 2200.00),
(74, 71, NULL, 'Patient Payment - Mpesa', 1, 0.00, 500.00, '2026-02-27 19:08:50', 'pharmacy', 'manual', 1, 500.00),
(75, 72, NULL, 'Patient Payment - Mpesa', 1, 0.00, 150.00, '2026-02-27 19:13:39', 'pharmacy', 'manual', 1, 150.00),
(76, 73, NULL, 'Cetizine', 2, 10.00, 20.00, '2026-02-27 19:14:23', 'pharmacy', 'pharmacy', 1, 0.00),
(77, 74, NULL, 'Cetizine', 2, 10.00, 20.00, '2026-02-27 19:14:23', 'pharmacy', 'pharmacy', 1, 0.00),
(78, 75, NULL, 'Metronidazole 400mg', 3, 15.00, 45.00, '2026-02-27 19:15:08', 'pharmacy', 'pharmacy', 1, 0.00),
(79, 76, NULL, 'Patient Payment - Cash', 1, 0.00, 3000.00, '2026-02-28 16:23:26', 'pharmacy', 'manual', 1, 3000.00),
(80, 77, NULL, 'Patient Payment - Cash', 1, 0.00, 7000.00, '2026-02-28 16:52:27', 'pharmacy', 'manual', 1, 7000.00),
(81, 78, NULL, 'Patient Payment - Mpesa', 1, 0.00, 4700.00, '2026-02-28 16:53:12', 'pharmacy', 'manual', 1, 4700.00),
(82, 79, NULL, 'Patient Payment - Mpesa', 1, 0.00, 359.00, '2026-02-28 17:23:08', 'pharmacy', 'manual', 1, 359.00),
(83, 80, NULL, 'Patient Payment - Mpesa', 1, 0.00, 5200.00, '2026-02-28 17:24:15', 'pharmacy', 'manual', 1, 5200.00),
(84, 81, NULL, 'TEO', 1, 200.00, 200.00, '2026-02-28 17:58:37', 'pharmacy', 'pharmacy', 1, 0.00),
(85, 82, NULL, 'Ibugesic 100mls', 1, 450.00, 450.00, '2026-02-28 18:00:10', 'pharmacy', 'pharmacy', 1, 0.00),
(86, 83, NULL, 'cofzit', 1, 150.00, 150.00, '2026-02-28 18:02:33', 'pharmacy', 'pharmacy', 1, 0.00),
(87, 84, NULL, 'Ashton powder', 4, 10.00, 40.00, '2026-03-01 08:44:41', 'pharmacy', 'pharmacy', 1, 0.00),
(88, 85, NULL, 'Paracetamol syrup 60mls', 1, 100.00, 100.00, '2026-03-01 08:45:31', 'pharmacy', 'pharmacy', 1, 0.00),
(89, 86, NULL, 'P2 ( generic)', 1, 100.00, 100.00, '2026-03-01 09:32:01', 'pharmacy', 'pharmacy', 1, 0.00),
(90, 87, NULL, 'Maramoja', 1, 10.00, 10.00, '2026-03-01 10:11:06', 'pharmacy', 'pharmacy', 1, 0.00),
(91, 88, NULL, 'Good Morning (60ml)', 1, 200.00, 200.00, '2026-03-01 10:23:31', 'pharmacy', 'pharmacy', 1, 0.00),
(92, 89, NULL, 'Patient Payment - Mpesa', 1, 0.00, 1500.00, '2026-03-01 16:18:34', 'pharmacy', 'manual', 1, 1500.00),
(93, 90, NULL, 'Cetizine', 11, 10.00, 110.00, '2026-03-02 07:13:27', 'pharmacy', 'pharmacy', 1, 0.00),
(94, 91, NULL, 'Prednisolone 5mg  (cosmos0', 2, 10.00, 20.00, '2026-03-02 07:14:09', 'pharmacy', 'pharmacy', 1, 0.00),
(95, 92, NULL, 'Metronidazole 400mg', 2, 15.00, 30.00, '2026-03-02 07:45:15', 'pharmacy', 'pharmacy', 1, 0.00),
(96, 93, NULL, 'Metronidazole 400mg', 2, 15.00, 30.00, '2026-03-02 07:45:26', 'pharmacy', 'pharmacy', 1, 0.00),
(97, 94, NULL, 'Metronidazole 400mg', 2, 15.00, 30.00, '2026-03-02 08:13:45', 'pharmacy', 'pharmacy', 1, 0.00),
(98, 95, NULL, 'Patient Payment - Mpesa', 1, 0.00, 5000.00, '2026-03-02 08:14:28', 'pharmacy', 'manual', 1, 5000.00),
(99, 96, NULL, 'Patient Payment - Mpesa', 1, 0.00, 200.00, '2026-03-02 09:27:42', 'pharmacy', 'manual', 1, 200.00),
(100, 97, NULL, 'Ibuprofen 400mg', 2, 10.00, 20.00, '2026-03-02 11:05:37', 'pharmacy', 'pharmacy', 1, 0.00),
(101, 98, NULL, 'Ibuprofen 400mg', 2, 10.00, 20.00, '2026-03-02 11:05:46', 'pharmacy', 'pharmacy', 1, 0.00),
(102, 99, NULL, 'sickoff', 1, 300.00, 300.00, '2026-03-02 11:08:45', 'pharmacy', 'pharmacy', 1, 0.00),
(103, 100, NULL, 'Esomeprazole 40mg', 2, 30.00, 60.00, '2026-03-02 15:01:09', 'pharmacy', 'pharmacy', 1, 0.00),
(104, 101, NULL, 'Paracetamol 500mg', 2, 5.00, 10.00, '2026-03-02 15:02:25', 'pharmacy', 'pharmacy', 1, 0.00),
(105, 102, NULL, 'depo( inj)', 1, 200.00, 200.00, '2026-03-02 18:03:40', 'pharmacy', 'pharmacy', 1, 0.00),
(106, 103, NULL, 'Patient Payment - Mpesa', 1, 0.00, 2000.00, '2026-03-02 18:48:19', 'pharmacy', 'manual', 1, 2000.00),
(107, 104, NULL, 'Cotrimoxazole 960mg', 5, 30.00, 150.00, '2026-03-03 06:30:02', 'pharmacy', 'pharmacy', 1, 0.00),
(108, 105, NULL, 'Ibuprofen 400mg', 5, 10.00, 50.00, '2026-03-03 06:31:11', 'pharmacy', 'pharmacy', 1, 0.00),
(109, 106, NULL, 'nebulization', 1, 500.00, 500.00, '2026-03-03 06:32:55', 'pharmacy', 'pharmacy', 1, 0.00),
(110, 107, NULL, 'Femiplan pills', 1, 120.00, 120.00, '2026-03-03 06:33:35', 'pharmacy', 'pharmacy', 1, 0.00),
(111, 108, NULL, 'Fluconazole 150 mg', 1, 150.00, 150.00, '2026-03-03 06:34:32', 'pharmacy', 'pharmacy', 1, 0.00),
(112, 109, NULL, 'liquid paraffin 100mls', 1, 200.00, 200.00, '2026-03-03 06:58:14', 'pharmacy', 'pharmacy', 1, 0.00),
(113, 110, NULL, 'immunizatinon', 1, 200.00, 200.00, '2026-03-03 06:58:42', 'pharmacy', 'pharmacy', 1, 0.00),
(114, 111, NULL, 'C.D (KISS)', 1, 100.00, 100.00, '2026-03-03 09:34:26', 'pharmacy', 'pharmacy', 1, 0.00),
(115, 112, NULL, 'Patient Payment - Mpesa', 1, 0.00, 3000.00, '2026-03-03 12:30:49', 'pharmacy', 'manual', 1, 3000.00),
(116, 113, NULL, 'Patient Payment - Mpesa', 1, 0.00, 300.00, '2026-03-03 14:16:12', 'pharmacy', 'manual', 1, 300.00),
(117, 114, NULL, 'Patient Payment - Mpesa', 1, 0.00, 300.00, '2026-03-03 18:04:44', 'pharmacy', 'manual', 1, 300.00),
(118, 115, NULL, 'Patient Payment - Mpesa', 1, 0.00, 1900.00, '2026-03-04 08:21:38', 'pharmacy', 'manual', 1, 1900.00),
(119, 116, NULL, 'Patient Payment - Mpesa', 1, 0.00, 11100.00, '2026-03-04 08:57:09', 'pharmacy', 'manual', 1, 11100.00),
(120, 117, NULL, 'Patient Payment - Mpesa', 1, 0.00, 1700.00, '2026-03-04 10:42:08', 'pharmacy', 'manual', 1, 1700.00),
(121, 118, NULL, 'Patient Payment - Mpesa', 1, 0.00, 850.00, '2026-03-04 13:51:37', 'pharmacy', 'manual', 1, 850.00),
(122, 119, NULL, 'Celestamine', 3, 20.00, 60.00, '2026-03-04 17:39:26', 'pharmacy', 'pharmacy', 1, 0.00),
(123, 120, NULL, 'Patient Payment - Mpesa', 1, 0.00, 3500.00, '2026-03-05 07:27:24', 'pharmacy', 'manual', 1, 3500.00),
(124, 121, NULL, 'Patient Payment - Mpesa', 1, 0.00, 3150.00, '2026-03-05 08:39:55', 'pharmacy', 'manual', 1, 3150.00),
(125, 122, NULL, 'Patient Payment - Cash', 1, 0.00, 250.00, '2026-03-05 08:41:00', 'pharmacy', 'manual', 1, 250.00),
(126, 123, NULL, 'Patient Payment - Cash', 1, 0.00, 250.00, '2026-03-05 08:41:06', 'pharmacy', 'manual', 1, 250.00),
(127, 124, NULL, 'Patient Payment - Mpesa', 1, 0.00, 20.00, '2026-03-05 08:43:04', 'pharmacy', 'manual', 1, 20.00),
(128, 125, NULL, 'Patient Payment - Mpesa', 1, 0.00, 20.00, '2026-03-05 08:43:11', 'pharmacy', 'manual', 1, 20.00),
(129, 126, NULL, 'Patient Payment - Mpesa', 1, 0.00, 2250.00, '2026-03-05 12:50:32', 'pharmacy', 'manual', 1, 2250.00),
(130, 127, NULL, 'Patient Payment - Mpesa', 1, 0.00, 2300.00, '2026-03-05 12:52:16', 'pharmacy', 'manual', 1, 2300.00),
(131, 128, NULL, 'Patient Payment - Mpesa', 1, 0.00, 1000.00, '2026-03-05 13:07:17', 'pharmacy', 'manual', 1, 1000.00),
(132, 129, NULL, 'Patient Payment - Cash', 1, 0.00, 2200.00, '2026-03-05 13:07:37', 'pharmacy', 'manual', 1, 2200.00),
(133, 130, NULL, 'Patient Payment - Cash', 1, 0.00, 2200.00, '2026-03-05 13:07:43', 'pharmacy', 'manual', 1, 2200.00),
(134, 131, NULL, 'Calpol 60ms', 1, 450.00, 450.00, '2026-03-05 13:09:22', 'pharmacy', 'pharmacy', 1, 0.00),
(135, 132, NULL, 'Calpol 60ms', 1, 450.00, 450.00, '2026-03-05 13:16:22', 'pharmacy', 'pharmacy', 1, 0.00),
(136, 133, NULL, 'Calpol 60ms', 1, 450.00, 450.00, '2026-03-05 13:16:22', 'pharmacy', 'pharmacy', 1, 0.00),
(137, 134, NULL, 'immunizatinon', 1, 200.00, 200.00, '2026-03-05 13:17:08', 'pharmacy', 'pharmacy', 1, 0.00),
(138, 135, NULL, 'pitc', 1, 200.00, 200.00, '2026-03-05 13:21:33', 'pharmacy', 'pharmacy', 1, 0.00),
(139, 136, NULL, 'Cetizine', 2, 10.00, 20.00, '2026-03-05 14:09:35', 'pharmacy', 'pharmacy', 1, 0.00),
(140, 137, NULL, 'piriton', 1, 100.00, 100.00, '2026-03-05 15:28:52', 'pharmacy', 'pharmacy', 1, 0.00),
(141, 138, NULL, 'Amoxicilin 60mls', 1, 150.00, 150.00, '2026-03-05 15:39:53', 'pharmacy', 'pharmacy', 1, 0.00),
(142, 139, NULL, 'ht/wt', 1, 50.00, 50.00, '2026-03-05 15:42:18', 'pharmacy', 'pharmacy', 1, 0.00),
(143, 140, NULL, 'immunizatinon', 1, 200.00, 200.00, '2026-03-07 07:53:15', 'pharmacy', 'pharmacy', 1, 0.00),
(144, 142, NULL, 'Amoxicilin 60mls', 1, 150.00, 150.00, '2026-03-07 07:55:28', 'pharmacy', 'pharmacy', 1, 0.00),
(145, 143, NULL, 'Ceftax (inj)', 1, 500.00, 500.00, '2026-03-07 08:04:33', 'pharmacy', 'pharmacy', 1, 0.00),
(146, 147, NULL, 'Zulu', 1, 50.00, 50.00, '2026-03-07 13:03:58', 'pharmacy', 'pharmacy', 1, 0.00),
(147, 150, NULL, 'Zulu', 2, 50.00, 100.00, '2026-03-07 16:53:24', 'pharmacy', 'pharmacy', 1, 0.00),
(148, 150, NULL, 'Amoxicilin 500mg', 5, 10.00, 50.00, '2026-03-07 16:53:24', 'pharmacy', 'pharmacy', 1, 0.00),
(149, 156, NULL, 'Ibuprofen 400mg', 10, 10.00, 100.00, '2026-03-08 14:45:51', 'pharmacy', 'pharmacy', 1, 0.00),
(150, 157, NULL, 'Zinc sulphate 20mg', 5, 20.00, 100.00, '2026-03-08 14:46:35', 'pharmacy', 'pharmacy', 1, 0.00),
(151, 158, NULL, 'Zinc sulphate 20mg', 5, 20.00, 100.00, '2026-03-08 14:46:43', 'pharmacy', 'pharmacy', 1, 0.00),
(152, 161, NULL, 'Femiplan pills', 1, 200.00, 200.00, '2026-03-08 20:08:53', 'pharmacy', 'pharmacy', 1, 0.00),
(153, 162, NULL, 'Zulu', 1, 50.00, 50.00, '2026-03-08 20:09:17', 'pharmacy', 'pharmacy', 1, 0.00),
(154, 163, NULL, 'Good Morning (60ml)', 1, 200.00, 200.00, '2026-03-08 20:09:39', 'pharmacy', 'pharmacy', 1, 0.00),
(155, 164, NULL, 'Anti D', 1, 8000.00, 8000.00, '2026-03-08 20:10:56', 'pharmacy', 'pharmacy', 1, 0.00),
(156, 165, NULL, 'Ashton powder', 3, 10.00, 30.00, '2026-03-08 20:11:23', 'pharmacy', 'pharmacy', 1, 0.00),
(157, 166, NULL, 'Ashton powder', 3, 10.00, 30.00, '2026-03-08 20:11:34', 'pharmacy', 'pharmacy', 1, 0.00),
(158, 167, NULL, 'Paracetamol syrup 60mls', 1, 100.00, 100.00, '2026-03-08 20:12:11', 'pharmacy', 'pharmacy', 1, 0.00),
(159, 168, NULL, 'Dexamethasole 4mg', 3, 10.00, 30.00, '2026-03-08 20:12:44', 'pharmacy', 'pharmacy', 1, 0.00),
(160, 170, NULL, 'depo( inj)', 1, 200.00, 200.00, '2026-03-09 06:50:04', 'pharmacy', 'pharmacy', 1, 0.00),
(161, 171, NULL, 'P2 ( generic)', 1, 100.00, 100.00, '2026-03-09 06:50:32', 'pharmacy', 'pharmacy', 1, 0.00),
(162, 173, NULL, 'Betafen plus 100ml', 1, 450.00, 450.00, '2026-03-10 13:35:52', 'pharmacy', 'pharmacy', 1, 0.00),
(163, 174, NULL, 'Cetizine', 3, 10.00, 30.00, '2026-03-10 13:36:26', 'pharmacy', 'pharmacy', 1, 0.00),
(164, 196, NULL, 'Acetal mr', 1, 50.00, 50.00, '2026-03-10 19:48:27', 'pharmacy', 'pharmacy', 1, 0.00),
(165, 197, NULL, 'T.T', 1, 150.00, 150.00, '2026-03-10 19:52:19', 'pharmacy', 'pharmacy', 1, 0.00),
(166, 199, NULL, 'Azithromycin 500mg', 1, 300.00, 300.00, '2026-03-11 13:32:01', 'pharmacy', 'pharmacy', 1, 0.00),
(167, 200, NULL, 'Azithromycin 500mg', 1, 300.00, 300.00, '2026-03-11 13:32:01', 'pharmacy', 'pharmacy', 1, 0.00),
(168, 201, NULL, 'Ibuprofen 400mg', 10, 10.00, 100.00, '2026-03-11 13:32:50', 'pharmacy', 'pharmacy', 1, 0.00),
(169, 202, NULL, 'Paracetamol 500mg', 2, 5.00, 10.00, '2026-03-11 15:10:42', 'pharmacy', 'pharmacy', 1, 0.00),
(170, 203, NULL, 'immunizatinon', 1, 200.00, 200.00, '2026-03-12 06:23:06', 'pharmacy', 'pharmacy', 1, 0.00),
(171, 207, NULL, 'baby weight', 1, 50.00, 50.00, '2026-03-12 15:35:52', 'pharmacy', 'pharmacy', 1, 0.00),
(172, 208, NULL, 'strepsils', 1, 50.00, 50.00, '2026-03-12 15:42:30', 'pharmacy', 'pharmacy', 1, 0.00),
(173, 209, NULL, 'Entamaxin 60mls', 1, 200.00, 200.00, '2026-03-13 06:25:47', 'pharmacy', 'pharmacy', 1, 0.00),
(174, 210, NULL, 'Cetizine', 2, 10.00, 20.00, '2026-03-13 06:27:13', 'pharmacy', 'pharmacy', 1, 0.00),
(175, 211, NULL, 'Celestamine', 1, 20.00, 20.00, '2026-03-13 06:28:05', 'pharmacy', 'pharmacy', 1, 0.00),
(176, 212, NULL, 'Celestamine', 1, 20.00, 20.00, '2026-03-13 08:20:20', 'pharmacy', 'pharmacy', 1, 0.00),
(177, 213, NULL, 'T.T', 1, 200.00, 200.00, '2026-03-13 08:20:45', 'pharmacy', 'pharmacy', 1, 0.00),
(178, 215, NULL, 'depo( inj)', 1, 200.00, 200.00, '2026-03-13 11:12:50', 'pharmacy', 'pharmacy', 1, 0.00),
(179, 216, NULL, 'Cetizine', 1, 10.00, 10.00, '2026-03-13 11:13:08', 'pharmacy', 'pharmacy', 1, 0.00),
(180, 218, NULL, 'dressing', 1, 300.00, 300.00, '2026-03-13 14:31:43', 'pharmacy', 'pharmacy', 1, 0.00),
(181, 219, NULL, 'Amoxicilin 500mg', 10, 10.00, 100.00, '2026-03-13 14:32:47', 'pharmacy', 'pharmacy', 1, 0.00),
(182, 220, NULL, 'Prednisolone 5mg  (cosmos0', 2, 10.00, 20.00, '2026-03-13 15:37:27', 'pharmacy', 'pharmacy', 1, 0.00),
(183, 221, NULL, 'Cetizine', 1, 10.00, 10.00, '2026-03-13 15:37:45', 'pharmacy', 'pharmacy', 1, 0.00),
(184, 224, NULL, 'Flugone', 2, 20.00, 40.00, '2026-03-14 10:39:26', 'pharmacy', 'pharmacy', 1, 0.00),
(185, 225, NULL, 'Celestamine', 1, 20.00, 20.00, '2026-03-14 10:40:22', 'pharmacy', 'pharmacy', 1, 0.00),
(186, 226, NULL, 'Celestamine', 1, 20.00, 20.00, '2026-03-14 10:40:22', 'pharmacy', 'pharmacy', 1, 0.00),
(187, 227, NULL, 'Celestamine', 1, 20.00, 20.00, '2026-03-14 10:40:58', 'pharmacy', 'pharmacy', 1, 0.00),
(188, 228, NULL, 'Amoxicilin 60mls', 1, 150.00, 150.00, '2026-03-14 10:44:07', 'pharmacy', 'pharmacy', 1, 0.00),
(189, 231, NULL, 'tothema', 5, 200.00, 1000.00, '2026-03-14 13:38:03', 'pharmacy', 'pharmacy', 1, 0.00),
(190, 232, NULL, 'zefcolin', 1, 500.00, 500.00, '2026-03-14 14:46:20', 'pharmacy', 'pharmacy', 1, 0.00),
(191, 233, NULL, 'Amoxicilin 60mls', 1, 150.00, 150.00, '2026-03-14 14:46:37', 'pharmacy', 'pharmacy', 1, 0.00),
(192, 234, NULL, 'piriton', 1, 100.00, 100.00, '2026-03-14 14:47:20', 'pharmacy', 'pharmacy', 1, 0.00),
(193, 235, NULL, 'Bonjela teething gel', 1, 1500.00, 1500.00, '2026-03-14 16:39:13', 'pharmacy', 'pharmacy', 1, 0.00),
(194, 236, NULL, 'NIfedipine 20mg', 10, 10.00, 100.00, '2026-03-14 16:39:46', 'pharmacy', 'pharmacy', 1, 0.00),
(195, 237, NULL, 'TEO', 1, 200.00, 200.00, '2026-03-14 16:40:40', 'pharmacy', 'pharmacy', 1, 0.00),
(196, 238, NULL, 'Neuroforte', 2, 30.00, 60.00, '2026-03-14 16:41:44', 'pharmacy', 'pharmacy', 1, 0.00),
(197, 239, NULL, 'Femiplan pills', 1, 200.00, 200.00, '2026-03-17 03:52:40', 'pharmacy', 'pharmacy', 1, 0.00),
(198, 240, NULL, 'TEO', 1, 200.00, 200.00, '2026-03-17 03:53:21', 'pharmacy', 'pharmacy', 1, 0.00),
(199, 241, NULL, 'Paracetamol syrup 60mls', 1, 100.00, 100.00, '2026-03-17 03:54:04', 'pharmacy', 'pharmacy', 1, 0.00),
(200, 242, NULL, 'Neuroforte', 4, 30.00, 120.00, '2026-03-17 03:54:50', 'pharmacy', 'pharmacy', 1, 0.00),
(201, 243, NULL, 'Cetizine', 1, 10.00, 10.00, '2026-03-17 03:56:06', 'pharmacy', 'pharmacy', 1, 0.00),
(202, 244, NULL, 'Metronidazole 400mg', 3, 15.00, 45.00, '2026-03-17 03:57:00', 'pharmacy', 'pharmacy', 1, 0.00),
(203, 245, NULL, 'depo( inj)', 1, 200.00, 200.00, '2026-03-17 03:58:10', 'pharmacy', 'pharmacy', 1, 0.00),
(204, 246, NULL, 'omeprazole', 1, 100.00, 100.00, '2026-03-17 03:58:51', 'pharmacy', 'pharmacy', 1, 0.00),
(205, 247, NULL, 'Cetrizine 60ml', 1, 100.00, 100.00, '2026-03-17 03:59:53', 'pharmacy', 'pharmacy', 1, 0.00),
(206, 248, NULL, 'Paracetamol syrup 60mls', 1, 100.00, 100.00, '2026-03-17 04:00:38', 'pharmacy', 'pharmacy', 1, 0.00),
(207, 250, NULL, 'immunizatinon', 1, 200.00, 200.00, '2026-03-17 09:03:31', 'pharmacy', 'pharmacy', 1, 0.00),
(208, 251, NULL, 'ht/wt', 1, 50.00, 50.00, '2026-03-17 09:04:03', 'pharmacy', 'pharmacy', 1, 0.00),
(209, 254, NULL, 'Tranexamic acid  tabs', 2, 50.00, 100.00, '2026-03-17 18:36:23', 'pharmacy', 'pharmacy', 1, 0.00),
(210, 261, NULL, 'Celestamine', 3, 20.00, 60.00, '2026-03-18 20:33:55', 'pharmacy', 'pharmacy', 1, 0.00),
(211, 266, NULL, 'Cetizine', 2, 10.00, 20.00, '2026-03-19 16:45:22', 'pharmacy', 'pharmacy', 1, 0.00),
(212, 267, NULL, 'Normal saline drops 15mls', 1, 150.00, 150.00, '2026-03-19 16:47:08', 'pharmacy', 'pharmacy', 1, 0.00),
(213, 268, NULL, 'Cetizine', 2, 10.00, 20.00, '2026-03-19 18:04:17', 'pharmacy', 'pharmacy', 1, 0.00),
(214, 269, NULL, 'P2 ( generic)', 1, 100.00, 100.00, '2026-03-19 18:05:04', 'pharmacy', 'pharmacy', 1, 0.00),
(215, 276, NULL, 'Cetizine', 2, 10.00, 20.00, '2026-03-25 12:02:15', 'pharmacy', 'pharmacy', 1, 0.00),
(216, 278, NULL, 'omeprazole tabs', 3, 10.00, 30.00, '2026-03-25 17:14:23', 'pharmacy', 'pharmacy', 1, 0.00),
(217, 280, NULL, 'Celestamine', 1, 20.00, 20.00, '2026-03-25 17:40:27', 'pharmacy', 'pharmacy', 1, 0.00),
(218, 281, NULL, 'Cetizine', 3, 10.00, 30.00, '2026-03-25 17:53:59', 'pharmacy', 'pharmacy', 1, 0.00),
(219, 282, NULL, 'immunizatinon', 1, 200.00, 200.00, '2026-03-26 08:15:57', 'pharmacy', 'pharmacy', 1, 0.00),
(220, 287, NULL, 'Femiplan pills', 1, 200.00, 200.00, '2026-03-27 14:18:15', 'pharmacy', 'pharmacy', 1, 0.00),
(221, 288, NULL, 'Ibuprofen  100mls', 1, 200.00, 200.00, '2026-03-27 18:20:35', 'pharmacy', 'pharmacy', 1, 0.00),
(222, 294, NULL, 'Amoxicilin 60mls', 1, 150.00, 150.00, '2026-04-01 03:47:32', 'pharmacy', 'pharmacy', 1, 0.00),
(223, 295, NULL, 'Paracetamol 500mg', 4, 5.00, 20.00, '2026-04-01 03:56:10', 'pharmacy', 'pharmacy', 1, 0.00),
(224, 296, NULL, 'Paracetamol syrup 60mls', 1, 100.00, 100.00, '2026-04-01 03:57:54', 'pharmacy', 'pharmacy', 1, 0.00),
(225, 301, NULL, 'P2 ( generic)', 1, 100.00, 100.00, '2026-04-01 16:37:51', 'pharmacy', 'pharmacy', 1, 0.00),
(226, 304, NULL, 'Femiplan pills', 1, 150.00, 150.00, '2026-04-01 18:17:17', 'pharmacy', 'pharmacy', 1, 0.00),
(227, 305, NULL, 'C.D (KISS)', 1, 100.00, 100.00, '2026-04-02 04:03:22', 'pharmacy', 'pharmacy', 1, 0.00),
(228, 306, NULL, 'immunizatinon', 1, 200.00, 200.00, '2026-04-02 07:31:32', 'pharmacy', 'pharmacy', 1, 0.00),
(229, 310, NULL, 'Sodamint', 2, 5.00, 10.00, '2026-04-02 16:09:07', 'pharmacy', 'pharmacy', 1, 0.00),
(230, 311, NULL, 'Ashton powder', 2, 10.00, 20.00, '2026-04-02 16:10:08', 'pharmacy', 'pharmacy', 1, 0.00),
(231, 312, NULL, 'Cetizine', 5, 10.00, 50.00, '2026-04-03 15:09:59', 'pharmacy', 'pharmacy', 1, 0.00),
(232, 313, NULL, 'depo( inj)', 1, 200.00, 200.00, '2026-04-03 15:12:36', 'pharmacy', 'pharmacy', 1, 0.00),
(233, 314, NULL, 'Ibuprofen 200mg', 10, 10.00, 100.00, '2026-04-03 15:13:27', 'pharmacy', 'pharmacy', 1, 0.00),
(234, 315, NULL, 'Azithromycin 500mg', 1, 300.00, 300.00, '2026-04-03 15:13:54', 'pharmacy', 'pharmacy', 1, 0.00),
(235, 323, NULL, 'Cetizine', 5, 10.00, 50.00, '2026-04-05 20:12:01', 'pharmacy', 'pharmacy', 1, 0.00),
(236, 324, NULL, 'P2 ( generic)', 1, 100.00, 100.00, '2026-04-05 20:12:46', 'pharmacy', 'pharmacy', 1, 0.00),
(237, 325, NULL, 'Sildenafil', 1, 50.00, 50.00, '2026-04-05 20:14:35', 'pharmacy', 'pharmacy', 1, 0.00),
(238, 326, NULL, 'Femiplan pills', 1, 150.00, 150.00, '2026-04-05 20:15:21', 'pharmacy', 'pharmacy', 1, 0.00),
(239, 327, NULL, 'Amoxicilin 500mg', 5, 10.00, 50.00, '2026-04-05 20:15:54', 'pharmacy', 'pharmacy', 1, 0.00),
(240, 328, NULL, 'Ashton powder', 4, 10.00, 40.00, '2026-04-05 20:17:08', 'pharmacy', 'pharmacy', 1, 0.00),
(241, 329, NULL, 'Paracetamol 500mg', 4, 5.00, 20.00, '2026-04-05 20:17:46', 'pharmacy', 'pharmacy', 1, 0.00),
(242, 339, NULL, 'Cetizine', 10, 10.00, 100.00, '2026-04-07 11:14:05', 'pharmacy', 'pharmacy', 1, 0.00),
(243, 340, NULL, 'Entamaxin 60mls', 1, 200.00, 200.00, '2026-04-07 11:14:38', 'pharmacy', 'pharmacy', 1, 0.00),
(244, 341, NULL, 'Femiplan pills', 1, 200.00, 200.00, '2026-04-07 11:15:08', 'pharmacy', 'pharmacy', 1, 0.00),
(245, 342, NULL, 'Ibuprofen  100mls', 1, 200.00, 200.00, '2026-04-07 11:15:57', 'pharmacy', 'pharmacy', 1, 0.00),
(246, 343, NULL, 'Ibuprofen 400mg', 10, 10.00, 100.00, '2026-04-07 11:17:05', 'pharmacy', 'pharmacy', 1, 0.00),
(247, 348, NULL, 'baby weight', 1, 50.00, 50.00, '2026-04-07 14:04:04', 'pharmacy', 'pharmacy', 1, 0.00),
(248, 349, NULL, 'depo( inj)', 1, 200.00, 200.00, '2026-04-07 14:14:43', 'pharmacy', 'pharmacy', 1, 0.00),
(249, 351, NULL, 'VEGA 100MG', 2, 50.00, 100.00, '2026-04-07 19:35:43', 'pharmacy', 'pharmacy', 1, 0.00),
(250, 352, NULL, 'Allucid 100mls', 1, 250.00, 250.00, '2026-04-07 19:36:41', 'pharmacy', 'pharmacy', 1, 0.00),
(251, 353, 11, 'Good Morning (60ml)', 1, 200.00, 200.00, '2026-04-08 08:59:59', 'pharmacy', 'manual', 1, 0.00),
(252, 354, 111, 'Multivitamin 100mls', 1, 200.00, 200.00, '2026-04-08 16:19:15', 'pharmacy', 'manual', 1, 0.00),
(253, 355, 173, 'Coldcap', 1, 20.00, 20.00, '2026-04-08 16:20:08', 'pharmacy', 'manual', 1, 0.00),
(254, 356, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 3, 500.00, 1500.00, '2026-04-09 10:01:59', 'pharmacy', 'manual', 1, 0.00),
(255, 356, 6, 'Medication: ceftraxne', 4, 400.00, 1600.00, '2026-04-09 10:02:19', 'pharmacy', 'manual', 1, 0.00),
(256, 356, 154, 'Medication: Lactulose 100mls', 1, 400.00, 400.00, '2026-04-09 10:02:38', 'pharmacy', 'manual', 1, 0.00),
(257, 356, 100, 'Medication: Acetal mr', 10, 50.00, 500.00, '2026-04-09 10:02:57', 'pharmacy', 'manual', 1, 0.00),
(258, 356, 302, 'Medication: DAFLON', 30, 100.00, 3000.00, '2026-04-09 10:16:28', 'pharmacy', 'manual', 1, 0.00),
(259, 356, 301, 'Medication: ANUSOL CREAM', 1, 850.00, 850.00, '2026-04-09 10:16:46', 'pharmacy', 'manual', 1, 0.00),
(260, 357, 49, 'Service: pdt', 1, 100.00, 100.00, '2026-04-09 11:10:11', 'service', 'manual', 1, 0.00),
(261, 357, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-04-09 11:30:13', 'pharmacy', 'manual', 1, 0.00),
(262, 357, 70, 'Medication: Azithromycin 500mg', 1, 300.00, 300.00, '2026-04-09 11:30:23', 'pharmacy', 'manual', 1, 0.00),
(263, 357, 86, 'Medication: Paracetamol 500mg', 20, 5.00, 100.00, '2026-04-09 11:31:10', 'pharmacy', 'manual', 1, 0.00),
(264, 357, 23, 'Medication: Clotrimazole pessaries 100mg', 1, 100.00, 100.00, '2026-04-09 11:31:31', 'pharmacy', 'manual', 1, 0.00),
(265, 360, 24, 'Service: CBC', 1, 500.00, 500.00, '2026-04-09 12:26:41', 'service', 'manual', 1, 0.00),
(266, 360, 31, 'Medication: Paracetamol infusion 100mls', 1, 600.00, 600.00, '2026-04-09 12:42:52', 'pharmacy', 'manual', 1, 0.00),
(267, 361, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-04-09 12:44:02', 'pharmacy', 'manual', 1, 0.00),
(268, 361, 93, 'Medication: Dexamethasole 4mg (INJ)', 2, 400.00, 800.00, '2026-04-09 12:44:18', 'pharmacy', 'manual', 1, 0.00),
(269, 361, 99, 'Medication: Amoxicilin 100mls', 1, 200.00, 200.00, '2026-04-09 12:44:34', 'pharmacy', 'manual', 1, 0.00),
(270, 361, 184, 'Medication: Chlopheniramine 60ml', 1, 100.00, 100.00, '2026-04-09 12:44:48', 'pharmacy', 'manual', 1, 0.00),
(271, 361, 33, 'Medication: Paracetamol syrup 60mls', 1, 100.00, 100.00, '2026-04-09 12:45:21', 'pharmacy', 'manual', 1, 0.00),
(272, 360, 96, 'Medication: Dexamethasole 4mg', 3, 10.00, 30.00, '2026-04-09 14:03:37', 'pharmacy', 'manual', 1, 0.00),
(273, 360, 93, 'Medication: Dexamethasole 4mg (INJ)', 2, 400.00, 800.00, '2026-04-09 14:04:01', 'pharmacy', 'manual', 1, 0.00),
(274, 360, 6, 'Medication: ceftraxne', 4, 400.00, 1600.00, '2026-04-09 14:04:18', 'pharmacy', 'manual', 1, 0.00),
(275, 360, 135, 'Medication: Dextrose 5% 500mls', 1, 800.00, 800.00, '2026-04-09 14:04:41', 'pharmacy', 'manual', 1, 0.00),
(276, 360, 245, 'Medication: piriton', 1, 100.00, 100.00, '2026-04-09 14:05:26', 'pharmacy', 'manual', 1, 0.00),
(277, 360, 99, 'Medication: Amoxicilin 100mls', 1, 200.00, 200.00, '2026-04-09 14:23:26', 'pharmacy', 'manual', 1, 0.00),
(278, 360, 114, 'Medication: Normal saline drops 15mls', 1, 150.00, 150.00, '2026-04-09 14:44:56', 'pharmacy', 'manual', 1, 0.00),
(279, 360, 198, 'Medication: Ibuprofen  100mls', 1, 200.00, 200.00, '2026-04-09 14:45:35', 'pharmacy', 'manual', 1, 0.00),
(280, 360, 198, 'Medication: Ibuprofen  100mls', 1, 200.00, 200.00, '2026-04-09 14:47:30', 'pharmacy', 'manual', 1, 0.00),
(281, 360, 35, 'Medication: Cetamol 60mls', 1, 150.00, 150.00, '2026-04-09 14:48:00', 'pharmacy', 'manual', 1, 0.00),
(282, 360, 33, 'Medication: Paracetamol syrup 60mls', 1, 100.00, 100.00, '2026-04-09 14:48:31', 'pharmacy', 'manual', 1, 0.00),
(283, 364, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 2, 500.00, 1000.00, '2026-04-09 17:01:38', 'pharmacy', 'manual', 1, 0.00),
(284, 364, 8, 'Medication: buscpan inj', 1, 400.00, 400.00, '2026-04-09 17:02:16', 'pharmacy', 'manual', 1, 0.00),
(285, 368, 298, 'Entamaxin tabs', 1, 20.00, 20.00, '2026-04-10 08:59:37', 'pharmacy', 'manual', 1, 0.00),
(286, 369, 61, 'ABZ  400mg', 1, 100.00, 100.00, '2026-04-10 09:00:00', 'pharmacy', 'manual', 1, 0.00),
(287, 370, 221, 'PIRITON', 20, 5.00, 100.00, '2026-04-10 09:00:38', 'pharmacy', 'manual', 1, 0.00),
(288, 371, 214, 'Tricohist  60mls', 1, 250.00, 250.00, '2026-04-10 09:01:16', 'pharmacy', 'manual', 1, 0.00),
(289, 372, 74, 'Femiplan pills', 1, 200.00, 200.00, '2026-04-10 09:01:37', 'pharmacy', 'manual', 1, 0.00),
(290, 373, 240, 'immunizatinon', 2, 200.00, 400.00, '2026-04-10 09:02:07', 'pharmacy', 'manual', 1, 0.00),
(291, 374, 35, 'Cetamol 60mls', 1, 150.00, 150.00, '2026-04-10 09:02:45', 'pharmacy', 'manual', 1, 0.00),
(292, 378, 262, 'Ampiclox  60', 1, 200.00, 200.00, '2026-04-10 16:56:01', 'pharmacy', 'manual', 1, 0.00),
(293, 379, 198, 'Ibuprofen  100mls', 1, 200.00, 200.00, '2026-04-10 17:49:49', 'pharmacy', 'manual', 1, 0.00),
(294, 380, 6, 'ceftraxne', 1, 400.00, 400.00, '2026-04-10 17:50:28', 'pharmacy', 'manual', 1, 0.00),
(295, 384, 282, 'baby weight', 1, 50.00, 50.00, '2026-04-11 13:58:56', 'pharmacy', 'manual', 1, 0.00),
(296, 386, 168, 'Allucid 100mls', 1, 250.00, 250.00, '2026-04-11 17:47:19', 'pharmacy', 'manual', 1, 0.00),
(297, 387, 232, 'Medication: iv paracetamol 2', 1, 600.00, 600.00, '2026-04-12 04:46:36', 'pharmacy', 'manual', 1, 0.00),
(298, 388, 81, 'Medication: Metronidazole 400mg', 15, 15.00, 225.00, '2026-04-12 04:53:41', 'pharmacy', 'manual', 1, 0.00),
(299, 388, 256, 'Medication: cefbactum', 1, 500.00, 500.00, '2026-04-12 04:53:59', 'pharmacy', 'manual', 1, 0.00),
(300, 388, 93, 'Medication: Dexamethasole 4mg (INJ)', 1, 400.00, 400.00, '2026-04-12 04:54:21', 'pharmacy', 'manual', 1, 0.00),
(301, 390, 86, 'Paracetamol 500mg', 10, 5.00, 50.00, '2026-04-12 04:57:12', 'pharmacy', 'manual', 1, 0.00),
(302, 391, 166, 'Pregnancy kit', 1, 50.00, 50.00, '2026-04-12 09:16:02', 'pharmacy', 'manual', 1, 0.00),
(303, 392, 166, 'Pregnancy kit', 1, 50.00, 50.00, '2026-04-12 10:14:02', 'pharmacy', 'manual', 1, 0.00),
(304, 393, 198, 'Ibuprofen  100mls', 1, 200.00, 200.00, '2026-04-12 14:24:20', 'pharmacy', 'manual', 1, 0.00),
(305, 394, 61, 'ABZ  400mg', 1, 100.00, 100.00, '2026-04-12 14:26:17', 'pharmacy', 'manual', 1, 0.00),
(306, 395, 35, 'Cetamol 60mls', 1, 150.00, 150.00, '2026-04-12 16:56:43', 'pharmacy', 'manual', 1, 0.00),
(307, 396, 276, 'Trust classic', 1, 50.00, 50.00, '2026-04-12 19:09:42', 'pharmacy', 'manual', 1, 0.00),
(308, 397, 6, 'Medication: ceftraxne', 5, 400.00, 2000.00, '2026-04-13 07:45:16', 'pharmacy', 'manual', 1, 0.00),
(309, 401, 214, 'Tricohist  60mls', 1, 250.00, 250.00, '2026-04-13 18:15:13', 'pharmacy', 'manual', 1, 0.00),
(310, 402, 198, 'Ibuprofen  100mls', 1, 200.00, 200.00, '2026-04-13 18:16:08', 'pharmacy', 'manual', 1, 0.00),
(311, 403, 24, 'Cetrizine 60ml', 1, 100.00, 100.00, '2026-04-14 08:56:53', 'pharmacy', 'manual', 1, 0.00),
(312, 387, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 3, 500.00, 1500.00, '2026-04-14 10:56:50', 'pharmacy', 'manual', 1, 0.00),
(313, 387, 187, 'Medication: Flucloxacilin  100mls', 3, 350.00, 1050.00, '2026-04-14 10:57:17', 'pharmacy', 'manual', 1, 0.00),
(314, 405, 201, 'Tramadol 50mg ( INJ)', 1, 500.00, 500.00, '2026-04-14 20:14:34', 'pharmacy', 'manual', 1, 0.00),
(315, 416, 276, 'Trust classic', 1, 50.00, 50.00, '2026-04-15 14:11:01', 'pharmacy', 'manual', 1, 0.00),
(316, 417, 240, 'immunizatinon', 1, 200.00, 200.00, '2026-04-16 14:13:06', 'pharmacy', 'manual', 1, 0.00),
(317, 418, 24, 'Service: CBC', 1, 500.00, 500.00, '2026-04-16 16:21:15', 'service', 'manual', 1, 0.00),
(318, 418, 238, 'Medication: Paracetamol infusion 100mls', 1, 400.00, 400.00, '2026-04-16 16:22:18', 'pharmacy', 'manual', 1, 0.00),
(319, 418, 83, 'Medication: Dextrose 10% 500mls', 1, 800.00, 800.00, '2026-04-16 16:22:35', 'pharmacy', 'manual', 1, 0.00),
(320, 418, 256, 'Medication: cefbactum', 2, 500.00, 1000.00, '2026-04-16 16:22:48', 'pharmacy', 'manual', 1, 0.00),
(321, 418, 38, 'Medication: Amoxiclav 228mg', 1, 1000.00, 1000.00, '2026-04-16 16:23:09', 'pharmacy', 'manual', 1, 0.00),
(322, 418, 75, 'Medication: Diclofenac 100mg', 10, 15.00, 150.00, '2026-04-16 16:23:22', 'pharmacy', 'manual', 1, 0.00),
(323, 419, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 1, 500.00, 500.00, '2026-04-17 13:25:19', 'pharmacy', 'manual', 1, 0.00),
(324, 419, 46, 'Medication: Ceftax (inj)', 1, 500.00, 500.00, '2026-04-17 13:25:27', 'pharmacy', 'manual', 1, 0.00),
(325, 419, 63, 'Medication: Entamaxin 60mls', 1, 200.00, 200.00, '2026-04-17 13:25:53', 'pharmacy', 'manual', 1, 0.00),
(326, 419, 102, 'Medication: Ampiclox  500mg', 1, 20.00, 20.00, '2026-04-17 13:26:14', 'pharmacy', 'manual', 1, 0.00),
(327, 419, 35, 'Medication: Cetamol 60mls', 1, 150.00, 150.00, '2026-04-17 13:27:14', 'pharmacy', 'manual', 1, 0.00),
(328, 419, 51, 'Medication: Ampiclox  100mls', 1, 300.00, 300.00, '2026-04-17 13:29:14', 'pharmacy', 'manual', 1, 0.00),
(329, 420, 6, 'Medication: ceftraxne', 5, 400.00, 2000.00, '2026-04-17 15:01:30', 'pharmacy', 'manual', 1, 0.00),
(330, 420, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 3, 500.00, 1500.00, '2026-04-17 15:02:04', 'pharmacy', 'manual', 1, 0.00),
(331, 420, 255, 'Medication: Gentamycin inj', 1, 350.00, 350.00, '2026-04-17 15:02:26', 'pharmacy', 'manual', 1, 0.00),
(332, 420, 70, 'Medication: Azithromycin 500mg', 1, 300.00, 300.00, '2026-04-17 15:02:56', 'pharmacy', 'manual', 1, 0.00),
(333, 420, 28, 'Medication: Fluconazole 150 mg', 1, 150.00, 150.00, '2026-04-17 15:03:54', 'pharmacy', 'manual', 1, 0.00),
(334, 420, 75, 'Medication: Diclofenac 100mg', 10, 15.00, 150.00, '2026-04-17 15:04:09', 'pharmacy', 'manual', 1, 0.00),
(335, 424, 282, 'baby weight', 1, 50.00, 50.00, '2026-04-17 15:51:14', 'pharmacy', 'manual', 1, 0.00),
(336, 425, 238, 'Medication: Paracetamol infusion 100mls', 1, 400.00, 400.00, '2026-04-18 12:56:10', 'pharmacy', 'manual', 1, 0.00),
(337, 425, 67, 'Medication: Normal saline 500mls', 1, 800.00, 800.00, '2026-04-18 12:57:10', 'pharmacy', 'manual', 1, 0.00),
(338, 425, 94, 'Medication: Metronidazole  100ml', 1, 200.00, 200.00, '2026-04-18 13:03:28', 'pharmacy', 'manual', 1, 0.00),
(339, 425, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 2, 500.00, 1000.00, '2026-04-18 13:04:00', 'pharmacy', 'manual', 1, 0.00),
(340, 425, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-04-18 13:04:13', 'pharmacy', 'manual', 1, 0.00),
(341, 425, 14, 'Medication: Betafen plus 100ml', 1, 450.00, 450.00, '2026-04-18 13:04:22', 'pharmacy', 'manual', 1, 0.00),
(342, 425, 6, 'Medication: ceftraxne', 4, 400.00, 1600.00, '2026-04-18 13:31:53', 'pharmacy', 'manual', 1, 0.00),
(343, 425, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 1, 500.00, 500.00, '2026-04-18 13:32:26', 'pharmacy', 'manual', 1, 0.00),
(344, 425, 51, 'Medication: Ampiclox  100mls', 1, 300.00, 300.00, '2026-04-18 13:32:45', 'pharmacy', 'manual', 1, 0.00),
(345, 387, 232, 'Medication: iv paracetamol 2', 7, 600.00, 4200.00, '2026-04-18 16:07:46', 'pharmacy', 'manual', 1, 0.00),
(346, 387, 187, 'Medication: Flucloxacilin  100mls', 4, 350.00, 1400.00, '2026-04-18 16:08:19', 'pharmacy', 'manual', 1, 0.00),
(347, 387, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 3, 500.00, 1500.00, '2026-04-18 16:08:59', 'pharmacy', 'manual', 1, 0.00),
(348, 387, 135, 'Medication: Dextrose 5% 500mls', 1, 800.00, 800.00, '2026-04-20 19:48:22', 'pharmacy', 'manual', 1, 0.00),
(349, 387, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 9, 500.00, 4500.00, '2026-04-21 07:47:54', 'pharmacy', 'manual', 1, 0.00),
(350, 387, 238, 'Medication: Paracetamol infusion 100mls', 9, 400.00, 3600.00, '2026-04-21 07:48:19', 'pharmacy', 'manual', 1, 0.00),
(351, 387, 187, 'Medication: Flucloxacilin  100mls', 4, 350.00, 1400.00, '2026-04-21 07:49:11', 'pharmacy', 'manual', 1, 0.00),
(352, 387, 187, 'Medication: Flucloxacilin  100mls', 1, 350.00, 350.00, '2026-04-22 10:15:32', 'pharmacy', 'manual', 1, 0.00),
(353, 387, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 1, 500.00, 500.00, '2026-04-22 10:15:56', 'pharmacy', 'manual', 1, 0.00),
(354, 387, 238, 'Medication: Paracetamol infusion 100mls', 1, 400.00, 400.00, '2026-04-22 10:16:09', 'pharmacy', 'manual', 1, 0.00),
(355, 387, 75, 'Medication: Diclofenac 100mg', 1, 15.00, 15.00, '2026-04-22 10:16:27', 'pharmacy', 'manual', 1, 0.00),
(356, 387, 220, 'Medication: diclofenac inj', 1, 400.00, 400.00, '2026-04-22 10:17:01', 'pharmacy', 'manual', 1, 0.00),
(357, 430, 53, 'Service: minor dressing', 1, 300.00, 300.00, '2026-04-23 10:21:23', 'service', 'manual', 1, 0.00),
(358, 430, 191, 'Medication: Dermazine cream', 1, 150.00, 150.00, '2026-04-23 10:21:39', 'pharmacy', 'manual', 1, 0.00),
(359, 430, 102, 'Medication: Ampiclox  500mg', 20, 20.00, 400.00, '2026-04-23 10:21:53', 'pharmacy', 'manual', 1, 0.00),
(360, 430, 291, 'Medication: Acetal mr', 1, 500.00, 500.00, '2026-04-23 10:22:12', 'pharmacy', 'manual', 1, 0.00),
(361, 430, 201, 'Medication: Tramadol 50mg ( INJ)', 1, 500.00, 500.00, '2026-04-23 10:23:26', 'pharmacy', 'manual', 1, 0.00),
(362, 430, 269, 'Medication: T.T', 1, 200.00, 200.00, '2026-04-23 10:23:37', 'pharmacy', 'manual', 1, 0.00),
(363, 430, 53, 'Service: minor dressing', 1, 300.00, 300.00, '2026-04-23 10:24:04', 'service', 'manual', 1, 0.00),
(364, 430, 91, 'Medication: Hydrocortisone 100mg ( inj)', 1, 350.00, 350.00, '2026-04-23 10:25:27', 'pharmacy', 'manual', 1, 0.00),
(365, 432, 232, 'Medication: iv paracetamol 2', 1, 600.00, 600.00, '2026-04-24 08:59:15', 'pharmacy', 'manual', 1, 0.00),
(366, 432, 79, 'Medication: Ringers lactate 500mls', 1, 800.00, 800.00, '2026-04-24 08:59:28', 'pharmacy', 'manual', 1, 0.00),
(367, 432, 284, 'Medication: plasil inj', 1, 400.00, 400.00, '2026-04-24 09:18:18', 'pharmacy', 'manual', 1, 0.00),
(368, 432, 284, 'Medication: plasil inj', 1, 400.00, 400.00, '2026-04-24 09:18:19', 'pharmacy', 'manual', 1, 0.00),
(369, 432, 284, 'Medication: plasil inj', 1, 400.00, 400.00, '2026-04-24 09:18:19', 'pharmacy', 'manual', 1, 0.00),
(370, 432, 284, 'Medication: plasil inj', 1, 400.00, 400.00, '2026-04-24 09:18:20', 'pharmacy', 'manual', 1, 0.00),
(371, 432, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 1, 500.00, 500.00, '2026-04-24 09:41:20', 'pharmacy', 'manual', 1, 0.00),
(372, 432, 6, 'Medication: ceftraxne', 2, 400.00, 800.00, '2026-04-24 10:06:19', 'pharmacy', 'manual', 1, 0.00),
(373, 432, 108, 'Medication: Ibugesic  60mls', 1, 400.00, 400.00, '2026-04-24 10:06:33', 'pharmacy', 'manual', 1, 0.00),
(374, 432, 182, 'Medication: Betafen plus 60ml', 1, 400.00, 400.00, '2026-04-24 10:08:01', 'pharmacy', 'manual', 1, 0.00),
(375, 432, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-04-24 10:14:45', 'pharmacy', 'manual', 1, 0.00),
(376, 387, 255, 'Medication: Gentamycin inj', 2, 350.00, 700.00, '2026-04-24 16:54:57', 'pharmacy', 'manual', 1, 0.00),
(377, 387, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 2, 500.00, 1000.00, '2026-04-24 17:04:02', 'pharmacy', 'manual', 1, 0.00),
(378, 387, 54, 'Service: major dressing', 1, 500.00, 500.00, '2026-04-25 09:05:34', 'service', 'manual', 1, 0.00),
(379, 387, 54, 'Service: major dressing', 1, 500.00, 500.00, '2026-04-25 09:05:45', 'service', 'manual', 1, 0.00),
(380, 434, 24, 'Cetrizine 60ml', 1, 100.00, 100.00, '2026-04-25 17:29:22', 'pharmacy', 'manual', 1, 0.00),
(381, 435, 173, 'Coldcap', 1, 20.00, 20.00, '2026-04-25 17:29:44', 'pharmacy', 'manual', 1, 0.00),
(382, 436, 82, 'Metronidazole 500mg /100mls ( infusion)', 1, 500.00, 500.00, '2026-04-25 17:30:13', 'pharmacy', 'manual', 1, 0.00),
(383, 437, 256, 'cefbactum', 1, 500.00, 500.00, '2026-04-25 17:30:43', 'pharmacy', 'manual', 1, 0.00),
(384, 439, 67, 'Medication: Normal saline 500mls', 1, 1000.00, 1000.00, '2026-04-26 12:57:20', 'pharmacy', 'manual', 1, 0.00),
(385, 439, 195, 'Medication: Ondasentron 4ml', 1, 1500.00, 1500.00, '2026-04-26 12:59:48', 'pharmacy', 'manual', 1, 0.00),
(386, 439, 67, 'Medication: Normal saline 500mls', 1, 1000.00, 1000.00, '2026-04-26 13:28:11', 'pharmacy', 'manual', 1, 0.00),
(387, 439, 304, 'Medication: Ondasentron 1', 1, 800.00, 800.00, '2026-04-26 13:32:25', 'pharmacy', 'manual', 1, 0.00),
(388, 439, 303, 'Medication: norma saline 1', 1, 1000.00, 1000.00, '2026-04-26 13:32:54', 'pharmacy', 'manual', 1, 0.00),
(389, 439, 305, 'Medication: Esomeprazole 40mg ( inj)', 1, 800.00, 800.00, '2026-04-26 13:35:09', 'pharmacy', 'manual', 1, 0.00),
(390, 441, 220, 'Medication: diclofenac inj', 1, 400.00, 400.00, '2026-04-28 10:26:54', 'pharmacy', 'manual', 1, 0.00),
(391, 441, 191, 'Medication: Dermazine cream', 1, 150.00, 150.00, '2026-04-28 10:27:02', 'pharmacy', 'manual', 1, 0.00),
(392, 441, 241, 'Medication: dressing', 1, 300.00, 300.00, '2026-04-28 10:27:15', 'pharmacy', 'manual', 1, 0.00),
(393, 441, 91, 'Medication: Hydrocortisone 100mg ( inj)', 2, 350.00, 700.00, '2026-04-28 10:27:29', 'pharmacy', 'manual', 1, 0.00),
(394, 441, 266, 'Medication: floxapen 500mg', 20, 30.00, 600.00, '2026-04-28 10:28:24', 'pharmacy', 'manual', 1, 0.00),
(395, 441, 100, 'Medication: Acetal mr', 10, 50.00, 500.00, '2026-04-28 10:28:57', 'pharmacy', 'manual', 1, 0.00),
(396, 441, 269, 'Medication: T.T', 1, 200.00, 200.00, '2026-04-28 10:29:25', 'pharmacy', 'manual', 1, 0.00),
(397, 442, 220, 'Medication: diclofenac inj', 1, 400.00, 400.00, '2026-04-28 10:41:44', 'pharmacy', 'manual', 1, 0.00),
(398, 442, 269, 'Medication: T.T', 1, 200.00, 200.00, '2026-04-28 10:41:51', 'pharmacy', 'manual', 1, 0.00),
(399, 442, 191, 'Medication: Dermazine cream', 1, 150.00, 150.00, '2026-04-28 10:41:59', 'pharmacy', 'manual', 1, 0.00),
(400, 442, 91, 'Medication: Hydrocortisone 100mg ( inj)', 2, 350.00, 700.00, '2026-04-28 10:42:10', 'pharmacy', 'manual', 1, 0.00),
(401, 442, 266, 'Medication: floxapen 500mg', 20, 30.00, 600.00, '2026-04-28 10:42:51', 'pharmacy', 'manual', 1, 0.00),
(402, 442, 100, 'Medication: Acetal mr', 10, 50.00, 500.00, '2026-04-28 10:43:09', 'pharmacy', 'manual', 1, 0.00),
(403, 442, 241, 'Medication: dressing', 1, 300.00, 300.00, '2026-04-28 10:43:16', 'pharmacy', 'manual', 1, 0.00),
(404, 387, 220, 'Medication: diclofenac inj', 1, 400.00, 400.00, '2026-04-28 15:32:00', 'pharmacy', 'manual', 1, 0.00),
(405, 387, 201, 'Medication: Tramadol 50mg ( INJ)', 2, 500.00, 1000.00, '2026-04-28 15:32:11', 'pharmacy', 'manual', 1, 0.00),
(406, 387, 89, 'Medication: Cypro B plus', 10, 30.00, 300.00, '2026-04-28 15:32:38', 'pharmacy', 'manual', 1, 0.00),
(407, 387, 199, 'Medication: Promethazine  25 mg tabs', 10, 5.00, 50.00, '2026-04-28 15:32:50', 'pharmacy', 'manual', 1, 0.00),
(408, 444, 93, 'Medication: Dexamethasole 4mg (INJ)', 2, 400.00, 800.00, '2026-04-29 16:51:27', 'pharmacy', 'manual', 1, 0.00),
(409, 444, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-04-29 16:51:43', 'pharmacy', 'manual', 1, 0.00),
(410, 444, 75, 'Medication: Diclofenac 100mg', 10, 15.00, 150.00, '2026-04-29 16:51:59', 'pharmacy', 'manual', 1, 0.00),
(411, 387, 256, 'Medication: cefbactum', 1, 500.00, 500.00, '2026-04-29 17:27:17', 'pharmacy', 'manual', 1, 0.00),
(412, 387, 232, 'Medication: iv paracetamol 2', 1, 600.00, 600.00, '2026-04-29 17:27:33', 'pharmacy', 'manual', 1, 0.00),
(413, 387, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 1, 500.00, 500.00, '2026-04-29 17:28:07', 'pharmacy', 'manual', 1, 0.00),
(414, 387, 201, 'Medication: Tramadol 50mg ( INJ)', 1, 500.00, 500.00, '2026-04-29 17:28:17', 'pharmacy', 'manual', 1, 0.00),
(415, 447, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-04-30 07:41:59', 'pharmacy', 'manual', 1, 0.00),
(416, 447, 37, 'Medication: Amoxiclav 625mg', 1, 9000.00, 9000.00, '2026-04-30 07:46:55', 'pharmacy', 'manual', 1, 0.00),
(417, 447, 291, 'Medication: Acetal mr', 1, 500.00, 500.00, '2026-04-30 07:47:11', 'pharmacy', 'manual', 1, 0.00),
(418, 447, 60, 'Medication: Prednisolone 5mg  (cosmos0', 10, 10.00, 100.00, '2026-04-30 07:47:29', 'pharmacy', 'manual', 1, 0.00),
(419, 447, 220, 'Medication: diclofenac inj', 1, 400.00, 400.00, '2026-04-30 07:48:13', 'pharmacy', 'manual', 1, 0.00),
(420, 447, 220, 'Medication: diclofenac inj', 1, 400.00, 400.00, '2026-04-30 07:48:51', 'pharmacy', 'manual', 1, 0.00),
(421, 387, 233, 'Medication: Tramadol 50mg ( INJ)', 1, 500.00, 500.00, '2026-05-01 07:05:24', 'pharmacy', 'manual', 1, 0.00),
(422, 387, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 1, 500.00, 500.00, '2026-05-01 07:06:00', 'pharmacy', 'manual', 1, 0.00),
(423, 448, 197, 'Pharmasal ointment', 1, 250.00, 250.00, '2026-05-02 06:58:58', 'pharmacy', 'manual', 1, 0.00),
(424, 449, 57, 'Medication: Hyoscine  (inj)', 1, 400.00, 400.00, '2026-05-02 15:58:14', 'pharmacy', 'manual', 1, 0.00),
(425, 449, 109, 'Medication: Metoclopromide 2mls (inj)', 1, 400.00, 400.00, '2026-05-02 15:58:52', 'pharmacy', 'manual', 1, 0.00),
(426, 449, 6, 'Medication: ceftraxne', 1, 400.00, 400.00, '2026-05-02 15:59:14', 'pharmacy', 'manual', 1, 0.00),
(427, 449, 135, 'Medication: Dextrose 5% 500mls', 1, 800.00, 800.00, '2026-05-02 16:01:30', 'pharmacy', 'manual', 1, 0.00),
(428, 449, 6, 'Medication: ceftraxne', 1, 400.00, 400.00, '2026-05-02 16:04:08', 'pharmacy', 'manual', 1, 0.00),
(429, 449, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-05-02 16:07:48', 'pharmacy', 'manual', 1, 0.00),
(430, 449, 66, 'Service: admission fee', 1, 500.00, 500.00, '2026-05-02 16:13:37', 'service', 'manual', 1, 0.00),
(431, 387, 93, 'Medication: Dexamethasole 4mg (INJ)', 2, 400.00, 800.00, '2026-05-02 16:59:26', 'pharmacy', 'manual', 1, 0.00),
(432, 387, 256, 'Medication: cefbactum', 5, 500.00, 2500.00, '2026-05-02 16:59:42', 'pharmacy', 'manual', 1, 0.00),
(433, 387, 91, 'Medication: Hydrocortisone 100mg ( inj)', 1, 350.00, 350.00, '2026-05-02 16:59:51', 'pharmacy', 'manual', 1, 0.00),
(434, 387, 291, 'Medication: Acetal mr', 1, 500.00, 500.00, '2026-05-02 17:00:33', 'pharmacy', 'manual', 1, 0.00),
(435, 387, 128, 'Medication: Zulu', 1, 50.00, 50.00, '2026-05-02 17:00:42', 'pharmacy', 'manual', 1, 0.00),
(436, 387, 199, 'Medication: Promethazine  25 mg tabs', 10, 5.00, 50.00, '2026-05-02 17:01:07', 'pharmacy', 'manual', 1, 0.00),
(437, 449, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 3, 500.00, 1500.00, '2026-05-03 05:12:24', 'pharmacy', 'manual', 1, 0.00),
(438, 453, 8, 'Medication: buscpan inj', 1, 400.00, 400.00, '2026-05-03 11:38:27', 'pharmacy', 'manual', 1, 0.00),
(439, 453, 31, 'Medication: Paracetamol infusion 100mls', 1, 600.00, 600.00, '2026-05-03 11:42:54', 'pharmacy', 'manual', 1, 0.00),
(440, 454, 6, 'Medication: ceftraxne', 2, 400.00, 800.00, '2026-05-03 12:10:58', 'pharmacy', 'manual', 1, 0.00),
(441, 454, 37, 'Medication: Amoxiclav 625mg', 1, 1000.00, 1000.00, '2026-05-03 12:11:33', 'pharmacy', 'manual', 1, 0.00),
(442, 454, 19, 'Medication: Ibuprofen 400mg', 10, 10.00, 100.00, '2026-05-03 12:12:14', 'pharmacy', 'manual', 1, 0.00),
(443, 454, 28, 'Medication: Fluconazole 150 mg', 1, 150.00, 150.00, '2026-05-03 12:12:24', 'pharmacy', 'manual', 1, 0.00),
(444, 454, 70, 'Medication: Azithromycin 500mg', 1, 300.00, 300.00, '2026-05-03 12:15:05', 'pharmacy', 'manual', 1, 0.00),
(445, 456, 93, 'Medication: Dexamethasole 4mg (INJ)', 1, 400.00, 400.00, '2026-05-03 12:35:50', 'pharmacy', 'manual', 1, 0.00),
(446, 456, 6, 'Medication: ceftraxne', 1, 400.00, 400.00, '2026-05-03 12:36:04', 'pharmacy', 'manual', 1, 0.00),
(447, 456, 38, 'Medication: Amoxiclav 228mg', 1, 1000.00, 1000.00, '2026-05-03 12:36:30', 'pharmacy', 'manual', 1, 0.00),
(448, 456, 246, 'Medication: piriton', 1, 200.00, 200.00, '2026-05-03 12:37:35', 'pharmacy', 'manual', 1, 0.00),
(449, 456, 14, 'Medication: Betafen plus 100ml', 1, 450.00, 450.00, '2026-05-03 12:37:59', 'pharmacy', 'manual', 1, 0.00),
(450, 456, 21, 'Medication: Predinisolone 60ml', 1, 400.00, 400.00, '2026-05-03 13:06:13', 'pharmacy', 'manual', 1, 0.00),
(451, 456, 259, 'Medication: Cefuroxime', 1, 500.00, 500.00, '2026-05-03 13:07:59', 'pharmacy', 'manual', 1, 0.00),
(452, 456, 39, 'Medication: Amoxiclav 156mg', 1, 800.00, 800.00, '2026-05-03 13:10:56', 'pharmacy', 'manual', 1, 0.00),
(453, 453, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 1, 500.00, 500.00, '2026-05-03 15:09:59', 'pharmacy', 'manual', 1, 0.00),
(454, 453, 107, 'Medication: Esomeprazole 40mg ( inj)', 3, 500.00, 1500.00, '2026-05-03 15:10:25', 'pharmacy', 'manual', 1, 0.00),
(455, 453, 6, 'Medication: ceftraxne', 1, 400.00, 400.00, '2026-05-03 15:10:47', 'pharmacy', 'manual', 1, 0.00),
(456, 453, 57, 'Service: sick off', 1, 500.00, 500.00, '2026-05-03 15:34:46', 'service', 'manual', 1, 0.00),
(457, 459, 107, 'Medication: Esomeprazole 40mg ( inj)', 1, 500.00, 500.00, '2026-05-03 15:36:33', 'pharmacy', 'manual', 1, 0.00),
(458, 459, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 1, 500.00, 500.00, '2026-05-03 15:36:54', 'pharmacy', 'manual', 1, 0.00),
(459, 459, 6, 'Medication: ceftraxne', 2, 400.00, 800.00, '2026-05-03 15:37:11', 'pharmacy', 'manual', 1, 0.00),
(460, 459, 8, 'Medication: buscpan inj', 1, 400.00, 400.00, '2026-05-03 15:38:01', 'pharmacy', 'manual', 1, 0.00),
(461, 459, 67, 'Medication: Normal saline 500mls', 1, 800.00, 800.00, '2026-05-03 15:38:38', 'pharmacy', 'manual', 1, 0.00),
(462, 459, 3, 'Medication: Omeprazole 20mg', 1, 100.00, 100.00, '2026-05-03 15:39:34', 'pharmacy', 'manual', 1, 0.00),
(463, 459, 80, 'Medication: buscpan 10mg', 10, 10.00, 100.00, '2026-05-03 15:40:02', 'pharmacy', 'manual', 1, 0.00),
(464, 459, 138, 'Medication: Loperamide', 5, 20.00, 100.00, '2026-05-03 15:40:36', 'pharmacy', 'manual', 1, 0.00),
(465, 459, 138, 'Medication: Loperamide', 5, 20.00, 100.00, '2026-05-03 15:40:36', 'pharmacy', 'manual', 1, 0.00),
(466, 459, 61, 'Medication: ABZ  400mg', 1, 100.00, 100.00, '2026-05-03 16:57:54', 'pharmacy', 'manual', 1, 0.00),
(467, 461, 53, 'Service: minor dressing', 1, 300.00, 300.00, '2026-05-04 14:26:59', 'service', 'manual', 1, 0.00),
(468, 461, 201, 'Medication: Tramadol 50mg ( INJ)', 1, 500.00, 500.00, '2026-05-04 14:27:15', 'pharmacy', 'manual', 1, 0.00),
(469, 461, 269, 'Medication: T.T', 1, 200.00, 200.00, '2026-05-04 14:27:24', 'pharmacy', 'manual', 1, 0.00),
(470, 461, 91, 'Medication: Hydrocortisone 100mg ( inj)', 1, 350.00, 350.00, '2026-05-04 14:27:30', 'pharmacy', 'manual', 1, 0.00),
(471, 461, 266, 'Medication: floxapen 500mg', 20, 30.00, 600.00, '2026-05-04 14:27:42', 'pharmacy', 'manual', 1, 0.00),
(472, 461, 291, 'Medication: Acetal mr', 1, 500.00, 500.00, '2026-05-04 14:27:51', 'pharmacy', 'manual', 1, 0.00);
INSERT INTO `invoice_items` (`id`, `invoice_id`, `med_id`, `description`, `qty`, `unit_price`, `total`, `created_at`, `item_type`, `source`, `quantity`, `price`) VALUES
(473, 461, 241, 'Medication: dressing', 1, 300.00, 300.00, '2026-05-04 14:28:05', 'pharmacy', 'manual', 1, 0.00),
(474, 461, 241, 'Medication: dressing', 1, 300.00, 300.00, '2026-05-04 14:28:48', 'pharmacy', 'manual', 1, 0.00),
(475, 461, 10, 'Medication: Cetizine', 5, 10.00, 50.00, '2026-05-04 14:30:05', 'pharmacy', 'manual', 1, 0.00),
(476, 463, 241, 'Medication: dressing', 1, 300.00, 300.00, '2026-05-04 14:36:12', 'pharmacy', 'manual', 1, 0.00),
(477, 463, 91, 'Medication: Hydrocortisone 100mg ( inj)', 1, 350.00, 350.00, '2026-05-04 14:36:18', 'pharmacy', 'manual', 1, 0.00),
(478, 463, 220, 'Medication: diclofenac inj', 1, 400.00, 400.00, '2026-05-04 14:36:34', 'pharmacy', 'manual', 1, 0.00),
(479, 463, 102, 'Medication: Ampiclox  500mg', 20, 20.00, 400.00, '2026-05-04 14:37:08', 'pharmacy', 'manual', 1, 0.00),
(480, 463, 19, 'Medication: Ibuprofen 400mg', 10, 10.00, 100.00, '2026-05-04 14:37:24', 'pharmacy', 'manual', 1, 0.00),
(481, 463, 10, 'Medication: Cetizine', 5, 10.00, 50.00, '2026-05-04 14:37:39', 'pharmacy', 'manual', 1, 0.00),
(482, 465, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-05-07 17:56:43', 'pharmacy', 'manual', 1, 0.00),
(483, 465, 93, 'Medication: Dexamethasole 4mg (INJ)', 2, 400.00, 800.00, '2026-05-07 17:57:04', 'pharmacy', 'manual', 1, 0.00),
(484, 465, 57, 'Medication: Hyoscine  (inj)', 1, 400.00, 400.00, '2026-05-07 17:58:01', 'pharmacy', 'manual', 1, 0.00),
(485, 465, 91, 'Medication: Hydrocortisone 100mg ( inj)', 1, 350.00, 350.00, '2026-05-07 17:59:11', 'pharmacy', 'manual', 1, 0.00),
(486, 468, 39, 'Medication: Amoxiclav 156mg', 1, 800.00, 800.00, '2026-05-08 07:49:25', 'pharmacy', 'manual', 1, 0.00),
(487, 468, 182, 'Medication: Betafen plus 60ml', 1, 400.00, 400.00, '2026-05-08 07:50:15', 'pharmacy', 'manual', 1, 0.00),
(488, 468, 123, 'Medication: Gacet 125mg', 1, 100.00, 100.00, '2026-05-08 07:51:02', 'pharmacy', 'manual', 1, 0.00),
(489, 468, 246, 'Medication: piriton', 1, 200.00, 200.00, '2026-05-08 07:52:14', 'pharmacy', 'manual', 1, 0.00),
(490, 468, 98, 'Medication: Amoxicilin 60mls', 1, 150.00, 150.00, '2026-05-08 08:08:17', 'pharmacy', 'manual', 1, 0.00),
(491, 468, 182, 'Medication: Betafen plus 60ml', 1, 400.00, 400.00, '2026-05-08 08:09:54', 'pharmacy', 'manual', 1, 0.00),
(492, 468, 39, 'Medication: Amoxiclav 156mg', 1, 800.00, 800.00, '2026-05-08 08:35:18', 'pharmacy', 'manual', 1, 0.00),
(493, 470, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-05-08 16:28:05', 'pharmacy', 'manual', 1, 0.00),
(494, 470, 70, 'Medication: Azithromycin 500mg', 1, 300.00, 300.00, '2026-05-08 16:28:48', 'pharmacy', 'manual', 1, 0.00),
(495, 470, 23, 'Medication: Clotrimazole pessaries 100mg', 1, 100.00, 100.00, '2026-05-08 16:29:18', 'pharmacy', 'manual', 1, 0.00),
(496, 470, 47, 'Medication: Clotrimazole cream', 1, 200.00, 200.00, '2026-05-08 16:29:28', 'pharmacy', 'manual', 1, 0.00),
(497, 470, 188, 'Medication: Doxycycline 100mg', 10, 30.00, 300.00, '2026-05-08 16:33:04', 'pharmacy', 'manual', 1, 0.00),
(498, 470, 47, 'Medication: Clotrimazole cream', 1, 200.00, 200.00, '2026-05-08 16:33:23', 'pharmacy', 'manual', 1, 0.00),
(499, 470, 6, 'Medication: ceftraxne', 1, 400.00, 400.00, '2026-05-08 16:36:42', 'pharmacy', 'manual', 1, 0.00),
(500, 387, 232, 'Medication: iv paracetamol 2', 1, 600.00, 600.00, '2026-05-09 07:14:45', 'pharmacy', 'manual', 1, 0.00),
(501, 387, 6, 'Medication: ceftraxne', 2, 400.00, 800.00, '2026-05-09 07:15:02', 'pharmacy', 'manual', 1, 0.00),
(502, 387, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 1, 500.00, 500.00, '2026-05-09 07:15:28', 'pharmacy', 'manual', 1, 0.00),
(503, 387, 195, 'Medication: Ondasentron 4ml', 1, 500.00, 500.00, '2026-05-09 07:15:43', 'pharmacy', 'manual', 1, 0.00),
(504, 387, 135, 'Medication: Dextrose 5% 500mls', 1, 800.00, 800.00, '2026-05-09 07:16:00', 'pharmacy', 'manual', 1, 0.00),
(505, 473, 28, 'Medication: Fluconazole 150 mg', 1, 150.00, 150.00, '2026-05-12 14:57:02', 'pharmacy', 'manual', 1, 0.00),
(506, 473, 19, 'Medication: Ibuprofen 400mg', 15, 10.00, 150.00, '2026-05-12 14:57:32', 'pharmacy', 'manual', 1, 0.00),
(507, 475, 232, 'Medication: iv paracetamol 2', 1, 600.00, 600.00, '2026-05-13 06:48:55', 'pharmacy', 'manual', 1, 0.00),
(508, 475, 232, 'Medication: iv paracetamol 2', 1, 600.00, 600.00, '2026-05-13 06:48:56', 'pharmacy', 'manual', 1, 0.00),
(509, 475, 79, 'Medication: Ringers lactate 500mls', 1, 800.00, 800.00, '2026-05-13 06:49:17', 'pharmacy', 'manual', 1, 0.00),
(510, 475, 256, 'Medication: cefbactum', 4, 500.00, 2000.00, '2026-05-13 07:06:16', 'pharmacy', 'manual', 1, 0.00),
(511, 475, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 3, 500.00, 1500.00, '2026-05-13 07:07:06', 'pharmacy', 'manual', 1, 0.00),
(512, 475, 58, 'Medication: Ibugesic 100mls', 1, 450.00, 450.00, '2026-05-13 07:07:53', 'pharmacy', 'manual', 1, 0.00),
(513, 475, 61, 'Medication: ABZ  400mg', 1, 100.00, 100.00, '2026-05-13 07:08:03', 'pharmacy', 'manual', 1, 0.00),
(514, 475, 285, 'Medication: Cybro B 200mls', 1, 700.00, 700.00, '2026-05-13 07:09:19', 'pharmacy', 'manual', 1, 0.00),
(515, 475, 94, 'Medication: Metronidazole  100ml', 1, 200.00, 200.00, '2026-05-13 07:24:53', 'pharmacy', 'manual', 1, 0.00),
(516, 387, 83, 'Medication: Dextrose 10% 500mls', 1, 800.00, 800.00, '2026-05-15 11:32:15', 'pharmacy', 'manual', 1, 0.00),
(517, 387, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 1, 500.00, 500.00, '2026-05-15 11:32:33', 'pharmacy', 'manual', 1, 0.00),
(518, 387, 238, 'Medication: Paracetamol infusion 100mls', 1, 400.00, 400.00, '2026-05-15 11:32:48', 'pharmacy', 'manual', 1, 0.00),
(519, 479, 67, 'Service: normal delivery', 1, 8500.00, 8500.00, '2026-05-16 08:36:31', 'service', 'manual', 1, 0.00),
(520, 479, 8, 'Medication: buscpan inj', 1, 400.00, 400.00, '2026-05-16 08:36:56', 'pharmacy', 'manual', 1, 0.00),
(521, 479, 67, 'Medication: Normal saline 500mls', 1, 800.00, 800.00, '2026-05-16 08:37:07', 'pharmacy', 'manual', 1, 0.00),
(522, 484, 8, 'Medication: buscpan inj', 1, 400.00, 400.00, '2026-05-21 19:45:35', 'pharmacy', 'manual', 1, 0.00),
(523, 484, 305, 'Medication: Esomeprazole 40mg ( inj)', 1, 800.00, 800.00, '2026-05-21 19:46:13', 'pharmacy', 'manual', 1, 0.00),
(524, 484, 79, 'Medication: Ringers lactate 500mls', 1, 800.00, 800.00, '2026-05-21 19:46:50', 'pharmacy', 'manual', 1, 0.00),
(525, 484, 195, 'Medication: Ondasentron 4ml', 1, 500.00, 500.00, '2026-05-21 19:47:56', 'pharmacy', 'manual', 1, 0.00),
(526, 484, 31, 'Medication: Paracetamol infusion 100mls', 1, 600.00, 600.00, '2026-05-21 20:30:14', 'pharmacy', 'manual', 1, 0.00),
(527, 485, 24, 'Service: CBC', 1, 300.00, 300.00, '2026-05-24 18:16:39', 'service', 'manual', 1, 0.00),
(528, 485, 237, 'Medication: Ringers lactate 500mls', 1, 500.00, 500.00, '2026-05-24 18:22:43', 'pharmacy', 'manual', 1, 0.00),
(529, 485, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 1, 500.00, 500.00, '2026-05-24 18:23:18', 'pharmacy', 'manual', 1, 0.00),
(530, 485, 63, 'Medication: Entamaxin 60mls', 1, 200.00, 200.00, '2026-05-24 18:23:52', 'pharmacy', 'manual', 1, 0.00),
(531, 485, 213, 'Medication: Zinc sulphate 20mg', 10, 20.00, 200.00, '2026-05-24 18:24:22', 'pharmacy', 'manual', 1, 0.00),
(532, 485, 51, 'Medication: Ampiclox  100mls', 1, 300.00, 300.00, '2026-05-24 18:25:36', 'pharmacy', 'manual', 1, 0.00),
(533, 485, 32, 'Medication: Paracetamol syrup 100mls', 1, 150.00, 150.00, '2026-05-24 18:26:53', 'pharmacy', 'manual', 1, 0.00),
(534, 485, 94, 'Medication: Metronidazole  100ml', 1, 200.00, 200.00, '2026-05-24 18:35:42', 'pharmacy', 'manual', 1, 0.00),
(535, 485, 20, 'Medication: O.R.S sachets', 3, 50.00, 150.00, '2026-05-24 18:37:13', 'pharmacy', 'manual', 1, 0.00),
(536, 487, 232, 'Medication: iv paracetamol 2', 1, 600.00, 600.00, '2026-05-29 13:03:35', 'pharmacy', 'manual', 1, 0.00),
(537, 487, 94, 'Medication: Metronidazole  100ml', 1, 200.00, 200.00, '2026-05-29 13:03:47', 'pharmacy', 'manual', 1, 0.00),
(538, 487, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 1, 500.00, 500.00, '2026-05-29 13:04:08', 'pharmacy', 'manual', 1, 0.00),
(539, 487, 79, 'Medication: Ringers lactate 500mls', 1, 800.00, 800.00, '2026-05-29 13:04:31', 'pharmacy', 'manual', 1, 0.00),
(540, 487, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-05-29 13:04:59', 'pharmacy', 'manual', 1, 0.00),
(541, 487, 198, 'Medication: Ibuprofen  100mls', 1, 200.00, 200.00, '2026-05-29 13:06:36', 'pharmacy', 'manual', 1, 0.00),
(542, 489, 63, 'Service: Hb', 1, 300.00, 300.00, '2026-05-30 15:37:24', 'service', 'manual', 1, 0.00),
(543, 489, 201, 'Medication: Tramadol 50mg ( INJ)', 1, 500.00, 500.00, '2026-05-30 15:37:36', 'pharmacy', 'manual', 1, 0.00),
(544, 489, 79, 'Medication: Ringers lactate 500mls', 1, 800.00, 800.00, '2026-05-30 15:38:03', 'pharmacy', 'manual', 1, 0.00),
(545, 489, 197, 'Medication: Pharmasal ointment', 1, 250.00, 250.00, '2026-05-30 15:45:23', 'pharmacy', 'manual', 1, 0.00),
(546, 489, 291, 'Medication: Acetal mr', 1, 500.00, 500.00, '2026-05-30 15:45:41', 'pharmacy', 'manual', 1, 0.00),
(547, 489, 135, 'Medication: Dextrose 5% 500mls', 1, 800.00, 800.00, '2026-05-30 16:52:25', 'pharmacy', 'manual', 1, 0.00),
(548, 489, 102, 'Medication: Ampiclox  500mg', 20, 20.00, 400.00, '2026-05-30 17:46:49', 'pharmacy', 'manual', 1, 0.00),
(549, 489, 10, 'Medication: Cetizine', 5, 10.00, 50.00, '2026-05-30 18:01:02', 'pharmacy', 'manual', 1, 0.00),
(550, 489, 6, 'Medication: ceftraxne', 1, 400.00, 400.00, '2026-05-30 18:01:17', 'pharmacy', 'manual', 1, 0.00),
(551, 489, 93, 'Medication: Dexamethasole 4mg (INJ)', 1, 400.00, 400.00, '2026-05-30 18:01:30', 'pharmacy', 'manual', 1, 0.00),
(552, 491, 232, 'Medication: iv paracetamol 2', 1, 600.00, 600.00, '2026-05-31 09:21:28', 'pharmacy', 'manual', 1, 0.00),
(553, 491, 94, 'Medication: Metronidazole  100ml', 1, 200.00, 200.00, '2026-05-31 09:21:42', 'pharmacy', 'manual', 1, 0.00),
(554, 491, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 1, 500.00, 500.00, '2026-05-31 09:21:59', 'pharmacy', 'manual', 1, 0.00),
(555, 491, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-05-31 09:22:24', 'pharmacy', 'manual', 1, 0.00),
(556, 491, 59, 'Medication: Azithromycin 15mls', 1, 150.00, 150.00, '2026-05-31 09:23:01', 'pharmacy', 'manual', 1, 0.00),
(557, 491, 93, 'Medication: Dexamethasole 4mg (INJ)', 1, 400.00, 400.00, '2026-05-31 09:24:55', 'pharmacy', 'manual', 1, 0.00),
(558, 491, 54, 'Medication: Brustan 100mls', 1, 700.00, 700.00, '2026-05-31 09:25:26', 'pharmacy', 'manual', 1, 0.00),
(559, 493, 81, 'Medication: Metronidazole 400mg', 15, 15.00, 225.00, '2026-05-31 18:36:00', 'pharmacy', 'manual', 1, 0.00),
(560, 493, 37, 'Medication: Amoxiclav 625mg', 1, 1000.00, 1000.00, '2026-05-31 18:36:40', 'pharmacy', 'manual', 1, 0.00),
(561, 493, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-05-31 18:37:07', 'pharmacy', 'manual', 1, 0.00),
(562, 493, 220, 'Medication: diclofenac inj', 1, 400.00, 400.00, '2026-05-31 18:37:29', 'pharmacy', 'manual', 1, 0.00),
(563, 493, 19, 'Medication: Ibuprofen 400mg', 10, 10.00, 100.00, '2026-05-31 18:39:20', 'pharmacy', 'manual', 1, 0.00),
(564, 493, 207, 'Medication: Amoxicilin 500mg', 15, 10.00, 150.00, '2026-05-31 18:39:48', 'pharmacy', 'manual', 1, 0.00),
(565, 495, 37, 'Medication: Amoxiclav 625mg', 1, 1000.00, 1000.00, '2026-06-01 09:14:18', 'pharmacy', 'manual', 1, 0.00),
(566, 495, 96, 'Medication: Dexamethasole 4mg', 10, 10.00, 100.00, '2026-06-01 09:14:37', 'pharmacy', 'manual', 1, 0.00),
(567, 496, 232, 'Medication: iv paracetamol 2', 1, 600.00, 600.00, '2026-06-05 10:10:23', 'pharmacy', 'manual', 1, 0.00),
(568, 496, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-06-05 10:10:41', 'pharmacy', 'manual', 1, 0.00),
(569, 496, 99, 'Medication: Amoxicilin 100mls', 1, 200.00, 200.00, '2026-06-05 10:10:57', 'pharmacy', 'manual', 1, 0.00),
(570, 496, 32, 'Medication: Paracetamol syrup 100mls', 1, 150.00, 150.00, '2026-06-05 10:11:17', 'pharmacy', 'manual', 1, 0.00),
(571, 496, 24, 'Service: CBC', 1, 250.00, 250.00, '2026-06-05 10:12:14', 'service', 'manual', 1, 0.00),
(572, 498, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 1, 500.00, 500.00, '2026-06-07 09:59:34', 'pharmacy', 'manual', 1, 0.00),
(573, 498, 79, 'Medication: Ringers lactate 500mls', 1, 800.00, 800.00, '2026-06-07 09:59:49', 'pharmacy', 'manual', 1, 0.00),
(574, 498, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-06-07 10:00:04', 'pharmacy', 'manual', 1, 0.00),
(575, 498, 110, 'Medication: Metronidazole 200mg', 10, 15.00, 150.00, '2026-06-07 10:00:26', 'pharmacy', 'manual', 1, 0.00),
(576, 498, 209, 'Medication: Ibuprofen 200mg', 15, 10.00, 150.00, '2026-06-07 10:02:15', 'pharmacy', 'manual', 1, 0.00),
(577, 499, 79, 'Medication: Ringers lactate 500mls', 1, 800.00, 800.00, '2026-06-07 12:19:47', 'pharmacy', 'manual', 1, 0.00),
(578, 499, 24, 'Service: CBC', 1, 500.00, 500.00, '2026-06-07 12:20:10', 'service', 'manual', 1, 0.00),
(579, 499, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-06-07 13:28:12', 'pharmacy', 'manual', 1, 0.00),
(580, 499, 10, 'Medication: Cetizine', 5, 10.00, 50.00, '2026-06-07 13:28:31', 'pharmacy', 'manual', 1, 0.00),
(581, 499, 93, 'Medication: Dexamethasole 4mg (INJ)', 1, 400.00, 400.00, '2026-06-07 13:28:46', 'pharmacy', 'manual', 1, 0.00),
(582, 499, 207, 'Medication: Amoxicilin 500mg', 15, 10.00, 150.00, '2026-06-07 13:30:51', 'pharmacy', 'manual', 1, 0.00),
(583, 499, 19, 'Medication: Ibuprofen 400mg', 15, 10.00, 150.00, '2026-06-07 13:31:10', 'pharmacy', 'manual', 1, 0.00),
(584, 499, 6, 'Medication: ceftraxne', 1, 400.00, 400.00, '2026-06-07 13:42:37', 'pharmacy', 'manual', 1, 0.00),
(585, 501, 24, 'Service: CBC', 1, 500.00, 500.00, '2026-06-08 14:51:10', 'service', 'manual', 1, 0.00),
(586, 501, 32, 'Service: Urinalysis', 1, 300.00, 300.00, '2026-06-08 14:51:19', 'service', 'manual', 1, 0.00),
(587, 501, 6, 'Medication: ceftraxne', 1, 400.00, 400.00, '2026-06-08 15:22:43', 'pharmacy', 'manual', 1, 0.00),
(588, 501, 37, 'Medication: Amoxiclav 625mg', 1, 1000.00, 1000.00, '2026-06-08 15:22:54', 'pharmacy', 'manual', 1, 0.00),
(589, 501, 44, 'Medication: Meloxicam 15 mg', 10, 20.00, 200.00, '2026-06-08 15:23:15', 'pharmacy', 'manual', 1, 0.00),
(590, 501, 28, 'Medication: Fluconazole 150 mg', 1, 150.00, 150.00, '2026-06-08 15:23:32', 'pharmacy', 'manual', 1, 0.00),
(591, 501, 47, 'Medication: Clotrimazole cream', 1, 200.00, 200.00, '2026-06-08 15:23:52', 'pharmacy', 'manual', 1, 0.00),
(592, 503, 62, 'Service: ANC', 1, 200.00, 200.00, '2026-06-08 16:09:22', 'service', 'manual', 1, 0.00),
(593, 503, 50, 'Service: maternity', 1, 8000.00, 8000.00, '2026-06-08 16:09:30', 'service', 'manual', 1, 0.00),
(594, 503, 67, 'Service: normal delivery', 1, 8500.00, 8500.00, '2026-06-08 16:09:40', 'service', 'manual', 1, 0.00),
(595, 505, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-06-09 06:22:46', 'pharmacy', 'manual', 1, 0.00),
(596, 505, 91, 'Medication: Hydrocortisone 100mg ( inj)', 2, 350.00, 700.00, '2026-06-09 06:22:58', 'pharmacy', 'manual', 1, 0.00),
(597, 505, 91, 'Medication: Hydrocortisone 100mg ( inj)', 2, 350.00, 700.00, '2026-06-09 06:22:58', 'pharmacy', 'manual', 1, 0.00),
(598, 505, 39, 'Medication: Amoxiclav 156mg', 1, 800.00, 800.00, '2026-06-09 06:23:26', 'pharmacy', 'manual', 1, 0.00),
(599, 505, 24, 'Medication: Cetrizine 60ml', 1, 100.00, 100.00, '2026-06-09 06:23:36', 'pharmacy', 'manual', 1, 0.00),
(600, 505, 158, 'Medication: Hydrocortisone cream', 1, 100.00, 100.00, '2026-06-09 06:24:32', 'pharmacy', 'manual', 1, 0.00),
(601, 507, 24, 'Service: CBC', 1, 500.00, 500.00, '2026-06-10 07:37:13', 'service', 'manual', 1, 0.00),
(602, 507, 32, 'Service: Urinalysis', 1, 300.00, 300.00, '2026-06-10 07:37:45', 'service', 'manual', 1, 0.00),
(603, 507, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-06-10 09:09:55', 'pharmacy', 'manual', 1, 0.00),
(604, 507, 93, 'Medication: Dexamethasole 4mg (INJ)', 2, 400.00, 800.00, '2026-06-10 09:10:17', 'pharmacy', 'manual', 1, 0.00),
(605, 507, 305, 'Medication: Esomeprazole 40mg ( inj)', 1, 800.00, 800.00, '2026-06-10 09:10:43', 'pharmacy', 'manual', 1, 0.00),
(606, 507, 255, 'Medication: Gentamycin inj', 2, 350.00, 700.00, '2026-06-10 09:11:38', 'pharmacy', 'manual', 1, 0.00),
(607, 507, 5, 'Medication: omeprazole', 10, 100.00, 1000.00, '2026-06-10 09:11:49', 'pharmacy', 'manual', 1, 0.00),
(608, 509, 232, 'Medication: iv paracetamol 2', 1, 600.00, 600.00, '2026-06-12 15:09:09', 'pharmacy', 'manual', 1, 0.00),
(609, 509, 94, 'Medication: Metronidazole  100ml', 2, 200.00, 400.00, '2026-06-12 15:09:36', 'pharmacy', 'manual', 1, 0.00),
(610, 509, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 1, 500.00, 500.00, '2026-06-12 15:46:43', 'pharmacy', 'manual', 1, 0.00),
(611, 509, 79, 'Medication: Ringers lactate 500mls', 1, 800.00, 800.00, '2026-06-12 15:47:04', 'pharmacy', 'manual', 1, 0.00),
(612, 509, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-06-12 15:47:44', 'pharmacy', 'manual', 1, 0.00),
(613, 509, 209, 'Medication: Ibuprofen 200mg', 15, 10.00, 150.00, '2026-06-12 15:48:42', 'pharmacy', 'manual', 1, 0.00),
(614, 509, 110, 'Medication: Metronidazole 200mg', 10, 15.00, 150.00, '2026-06-12 15:49:02', 'pharmacy', 'manual', 1, 0.00),
(615, 510, 54, 'Service: major dressing', 1, 500.00, 500.00, '2026-06-14 05:31:47', 'service', 'manual', 1, 0.00),
(616, 510, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-06-14 05:32:45', 'pharmacy', 'manual', 1, 0.00),
(617, 510, 207, 'Medication: Amoxicilin 500mg', 15, 10.00, 150.00, '2026-06-14 05:33:35', 'pharmacy', 'manual', 1, 0.00),
(618, 510, 291, 'Medication: Acetal mr', 1, 500.00, 500.00, '2026-06-14 05:33:51', 'pharmacy', 'manual', 1, 0.00),
(619, 512, 67, 'Service: normal delivery', 1, 8500.00, 8500.00, '2026-06-14 06:14:24', 'service', 'manual', 1, 0.00),
(620, 513, 170, 'Sodamint', 4, 5.00, 20.00, '2026-06-14 07:25:24', 'pharmacy', 'manual', 1, 0.00),
(621, 513, 103, 'Ashton powder', 3, 10.00, 30.00, '2026-06-14 07:25:24', 'pharmacy', 'manual', 1, 0.00),
(622, 514, 206, 'Medication: Amoxicilin 250mg', 15, 20.00, 300.00, '2026-06-16 20:15:57', 'pharmacy', 'manual', 1, 0.00),
(623, 514, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-06-16 20:16:34', 'pharmacy', 'manual', 1, 0.00),
(624, 514, 209, 'Medication: Ibuprofen 200mg', 10, 10.00, 100.00, '2026-06-16 20:17:29', 'pharmacy', 'manual', 1, 0.00),
(625, 514, 110, 'Medication: Metronidazole 200mg', 15, 15.00, 225.00, '2026-06-16 20:17:48', 'pharmacy', 'manual', 1, 0.00),
(626, 517, 53, 'Service: minor dressing', 1, 300.00, 300.00, '2026-06-18 09:36:08', 'service', 'manual', 1, 0.00),
(627, 517, 102, 'Medication: Ampiclox  500mg', 20, 20.00, 400.00, '2026-06-18 09:36:57', 'pharmacy', 'manual', 1, 0.00),
(628, 517, 256, 'Medication: cefbactum', 5, 500.00, 2500.00, '2026-06-18 09:37:12', 'pharmacy', 'manual', 1, 0.00),
(629, 520, 168, 'Allucid 100mls', 1, 250.00, 250.00, '2026-06-18 09:40:06', 'pharmacy', 'manual', 1, 0.00),
(630, 521, 32, 'Service: Urinalysis', 1, 300.00, 300.00, '2026-06-20 05:16:15', 'service', 'manual', 1, 0.00),
(631, 521, 6, 'Medication: ceftraxne', 4, 400.00, 1600.00, '2026-06-20 05:27:21', 'pharmacy', 'manual', 1, 0.00),
(632, 521, 70, 'Medication: Azithromycin 500mg', 1, 300.00, 300.00, '2026-06-20 05:27:48', 'pharmacy', 'manual', 1, 0.00),
(633, 521, 8, 'Medication: buscpan inj', 1, 400.00, 400.00, '2026-06-20 05:28:15', 'pharmacy', 'manual', 1, 0.00),
(634, 521, 80, 'Medication: buscpan 10mg', 10, 10.00, 100.00, '2026-06-20 05:28:53', 'pharmacy', 'manual', 1, 0.00),
(635, 521, 70, 'Medication: Azithromycin 500mg', 1, 300.00, 300.00, '2026-06-20 05:53:45', 'pharmacy', 'manual', 1, 0.00),
(636, 523, 232, 'Medication: iv paracetamol 2', 1, 600.00, 600.00, '2026-06-20 20:30:40', 'pharmacy', 'manual', 1, 0.00),
(637, 523, 93, 'Medication: Dexamethasole 4mg (INJ)', 1, 400.00, 400.00, '2026-06-20 20:31:09', 'pharmacy', 'manual', 1, 0.00),
(638, 523, 93, 'Medication: Dexamethasole 4mg (INJ)', 1, 400.00, 400.00, '2026-06-20 21:10:12', 'pharmacy', 'manual', 1, 0.00),
(639, 523, 299, 'Medication: Gacet 250mg', 1, 200.00, 200.00, '2026-06-20 21:10:44', 'pharmacy', 'manual', 1, 0.00),
(640, 523, 54, 'Medication: Brustan 100mls', 1, 700.00, 700.00, '2026-06-20 21:11:03', 'pharmacy', 'manual', 1, 0.00),
(641, 523, 38, 'Medication: Amoxiclav 228mg', 1, 1000.00, 1000.00, '2026-06-20 21:11:51', 'pharmacy', 'manual', 1, 0.00),
(642, 523, 21, 'Medication: Predinisolone 60ml', 1, 400.00, 400.00, '2026-06-20 21:12:06', 'pharmacy', 'manual', 1, 0.00),
(643, 525, 79, 'Medication: Ringers lactate 500mls', 1, 800.00, 800.00, '2026-06-22 15:26:08', 'pharmacy', 'manual', 1, 0.00),
(644, 525, 6, 'Medication: ceftraxne', 4, 400.00, 1600.00, '2026-06-22 15:28:49', 'pharmacy', 'manual', 1, 0.00),
(645, 525, 28, 'Medication: Fluconazole 150 mg', 1, 150.00, 150.00, '2026-06-22 15:29:01', 'pharmacy', 'manual', 1, 0.00),
(646, 525, 80, 'Medication: buscpan 10mg', 10, 10.00, 100.00, '2026-06-22 15:29:43', 'pharmacy', 'manual', 1, 0.00),
(647, 525, 186, 'Medication: Ciprofloxacin 500mg', 10, 20.00, 200.00, '2026-06-22 15:30:16', 'pharmacy', 'manual', 1, 0.00),
(648, 527, 305, 'Medication: Esomeprazole 40mg ( inj)', 1, 800.00, 800.00, '2026-06-24 18:39:19', 'pharmacy', 'manual', 1, 0.00),
(649, 527, 31, 'Medication: Paracetamol infusion 100mls', 1, 600.00, 600.00, '2026-06-24 18:40:01', 'pharmacy', 'manual', 1, 0.00),
(650, 527, 284, 'Medication: plasil inj', 1, 400.00, 400.00, '2026-06-24 18:40:16', 'pharmacy', 'manual', 1, 0.00),
(651, 527, 42, 'Medication: Artemether inj 80mg/ml', 3, 350.00, 1050.00, '2026-06-24 18:50:03', 'pharmacy', 'manual', 1, 0.00),
(652, 527, 264, 'Medication: Artemether tablets', 1, 200.00, 200.00, '2026-06-24 18:51:08', 'pharmacy', 'manual', 1, 0.00),
(653, 527, 86, 'Medication: Paracetamol 500mg', 20, 5.00, 100.00, '2026-06-24 18:51:48', 'pharmacy', 'manual', 1, 0.00),
(654, 527, 42, 'Medication: Artemether inj 80mg/ml', 2, 350.00, 700.00, '2026-06-24 18:52:37', 'pharmacy', 'manual', 1, 0.00),
(655, 527, 6, 'Medication: ceftraxne', 1, 400.00, 400.00, '2026-06-24 18:53:10', 'pharmacy', 'manual', 1, 0.00),
(656, 527, 42, 'Medication: Artemether inj 80mg/ml', 1, 350.00, 350.00, '2026-06-24 19:02:58', 'pharmacy', 'manual', 1, 0.00),
(657, 527, 6, 'Medication: ceftraxne', 2, 400.00, 800.00, '2026-06-24 19:03:20', 'pharmacy', 'manual', 1, 0.00),
(658, 527, 238, 'Medication: Paracetamol infusion 100mls', 1, 400.00, 400.00, '2026-06-24 19:03:48', 'pharmacy', 'manual', 1, 0.00),
(659, 529, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-06-26 17:09:26', 'pharmacy', 'manual', 1, 0.00),
(660, 529, 93, 'Medication: Dexamethasole 4mg (INJ)', 1, 400.00, 400.00, '2026-06-26 17:10:04', 'pharmacy', 'manual', 1, 0.00),
(661, 529, 98, 'Medication: Amoxicilin 60mls', 1, 150.00, 150.00, '2026-06-26 17:10:53', 'pharmacy', 'manual', 1, 0.00),
(662, 529, 6, 'Medication: ceftraxne', 2, 400.00, 800.00, '2026-06-26 17:13:23', 'pharmacy', 'manual', 1, 0.00),
(663, 529, 93, 'Medication: Dexamethasole 4mg (INJ)', 1, 400.00, 400.00, '2026-06-26 17:13:53', 'pharmacy', 'manual', 1, 0.00),
(664, 532, 67, 'Medication: Normal saline 500mls', 1, 800.00, 800.00, '2026-06-27 05:44:19', 'pharmacy', 'manual', 1, 0.00),
(665, 532, 284, 'Medication: plasil inj', 1, 400.00, 400.00, '2026-06-27 05:44:40', 'pharmacy', 'manual', 1, 0.00),
(666, 532, 135, 'Medication: Dextrose 5% 500mls', 1, 800.00, 800.00, '2026-06-27 05:45:16', 'pharmacy', 'manual', 1, 0.00),
(667, 532, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-06-27 05:45:36', 'pharmacy', 'manual', 1, 0.00),
(668, 532, 25, 'Service: Blood Sugar Random', 1, 300.00, 300.00, '2026-06-27 05:46:14', 'service', 'manual', 1, 0.00),
(669, 532, 199, 'Medication: Promethazine  25 mg tabs', 10, 5.00, 50.00, '2026-06-27 05:46:31', 'pharmacy', 'manual', 1, 0.00),
(670, 532, 291, 'Medication: Acetal mr', 1, 500.00, 500.00, '2026-06-27 05:46:40', 'pharmacy', 'manual', 1, 0.00),
(671, 532, 31, 'Medication: Paracetamol infusion 100mls', 1, 600.00, 600.00, '2026-06-27 05:48:55', 'pharmacy', 'manual', 1, 0.00),
(672, 534, 6, 'Medication: ceftraxne', 4, 400.00, 1600.00, '2026-06-27 12:47:51', 'pharmacy', 'manual', 1, 0.00),
(673, 534, 93, 'Medication: Dexamethasole 4mg (INJ)', 2, 400.00, 800.00, '2026-06-27 12:49:10', 'pharmacy', 'manual', 1, 0.00),
(674, 534, 70, 'Medication: Azithromycin 500mg', 1, 300.00, 300.00, '2026-06-27 12:50:50', 'pharmacy', 'manual', 1, 0.00),
(675, 534, 10, 'Medication: Cetizine', 10, 10.00, 100.00, '2026-06-27 12:53:08', 'pharmacy', 'manual', 1, 0.00),
(676, 534, 86, 'Medication: Paracetamol 500mg', 20, 5.00, 100.00, '2026-06-27 12:54:31', 'pharmacy', 'manual', 1, 0.00),
(677, 537, 287, 'Indomethacin', 1, 10.00, 10.00, '2026-06-27 13:04:42', 'pharmacy', 'manual', 1, 0.00),
(678, 538, 99, 'Amoxicilin 100mls', 1, 200.00, 200.00, '2026-06-28 20:12:55', 'pharmacy', 'manual', 1, 0.00),
(679, 539, 46, 'Medication: Ceftax (inj)', 2, 500.00, 1000.00, '2026-07-01 06:23:41', 'pharmacy', 'manual', 1, 0.00),
(680, 539, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 1, 500.00, 500.00, '2026-07-01 06:23:56', 'pharmacy', 'manual', 1, 0.00),
(681, 539, 81, 'Medication: Metronidazole 400mg', 10, 15.00, 150.00, '2026-07-01 06:24:14', 'pharmacy', 'manual', 1, 0.00),
(682, 539, 291, 'Medication: Acetal mr', 1, 500.00, 500.00, '2026-07-01 06:24:28', 'pharmacy', 'manual', 1, 0.00),
(683, 541, 61, 'ABZ  400mg', 1, 100.00, 100.00, '2026-07-02 17:48:27', 'pharmacy', 'manual', 1, 0.00),
(684, 542, 33, 'Paracetamol syrup 60mls', 1, 100.00, 100.00, '2026-07-02 19:09:58', 'pharmacy', 'manual', 1, 0.00),
(685, 543, 6, 'Medication: ceftraxne', 4, 400.00, 1600.00, '2026-07-03 06:24:27', 'pharmacy', 'manual', 1, 0.00),
(686, 543, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 2, 500.00, 1000.00, '2026-07-03 06:24:48', 'pharmacy', 'manual', 1, 0.00),
(687, 543, 70, 'Medication: Azithromycin 500mg', 1, 300.00, 300.00, '2026-07-03 06:25:13', 'pharmacy', 'manual', 1, 0.00),
(688, 543, 31, 'Medication: Paracetamol infusion 100mls', 1, 600.00, 600.00, '2026-07-03 06:26:11', 'pharmacy', 'manual', 1, 0.00),
(689, 543, 291, 'Medication: Acetal mr', 1, 500.00, 500.00, '2026-07-03 06:26:37', 'pharmacy', 'manual', 1, 0.00),
(690, 545, 93, 'Medication: Dexamethasole 4mg (INJ)', 1, 400.00, 400.00, '2026-07-04 13:12:33', 'pharmacy', 'manual', 1, 0.00),
(691, 545, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-07-04 13:13:09', 'pharmacy', 'manual', 1, 0.00),
(692, 545, 99, 'Medication: Amoxicilin 100mls', 1, 200.00, 200.00, '2026-07-04 13:13:33', 'pharmacy', 'manual', 1, 0.00),
(693, 545, 198, 'Medication: Ibuprofen  100mls', 1, 200.00, 200.00, '2026-07-04 13:14:00', 'pharmacy', 'manual', 1, 0.00),
(694, 548, 232, 'Medication: iv paracetamol 2', 1, 600.00, 600.00, '2026-07-05 09:25:43', 'pharmacy', 'manual', 1, 0.00),
(695, 548, 303, 'Medication: norma saline 1', 1, 1000.00, 1000.00, '2026-07-05 09:26:29', 'pharmacy', 'manual', 1, 0.00),
(696, 548, 201, 'Medication: Tramadol 50mg ( INJ)', 1, 500.00, 500.00, '2026-07-05 10:00:22', 'pharmacy', 'manual', 1, 0.00),
(697, 548, 67, 'Medication: Normal saline 500mls', 1, 800.00, 800.00, '2026-07-05 10:24:02', 'pharmacy', 'manual', 1, 0.00),
(698, 548, 6, 'Medication: ceftraxne', 5, 400.00, 2000.00, '2026-07-05 11:37:27', 'pharmacy', 'manual', 1, 0.00),
(699, 552, 21, 'Medication: Predinisolone 60ml', 1, 400.00, 400.00, '2026-07-10 07:13:25', 'pharmacy', 'manual', 1, 0.00),
(700, 552, 245, 'Medication: piriton', 1, 100.00, 100.00, '2026-07-10 07:14:21', 'pharmacy', 'manual', 1, 0.00),
(701, 552, 98, 'Medication: Amoxicilin 60mls', 1, 150.00, 150.00, '2026-07-10 07:14:35', 'pharmacy', 'manual', 1, 0.00),
(702, 558, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 3, 500.00, 1500.00, '2026-07-12 10:25:46', 'pharmacy', 'manual', 1, 0.00),
(703, 558, 6, 'Medication: ceftraxne', 4, 400.00, 1600.00, '2026-07-12 10:26:01', 'pharmacy', 'manual', 1, 0.00),
(704, 558, 47, 'Medication: Clotrimazole cream', 1, 200.00, 200.00, '2026-07-12 10:26:10', 'pharmacy', 'manual', 1, 0.00),
(705, 558, 23, 'Medication: Clotrimazole pessaries 100mg', 1, 100.00, 100.00, '2026-07-12 10:26:29', 'pharmacy', 'manual', 1, 0.00),
(706, 558, 28, 'Medication: Fluconazole 150 mg', 1, 150.00, 150.00, '2026-07-12 10:27:37', 'pharmacy', 'manual', 1, 0.00),
(707, 558, 291, 'Medication: Acetal mr', 1, 500.00, 500.00, '2026-07-12 10:30:09', 'pharmacy', 'manual', 1, 0.00),
(708, 558, 100, 'Medication: Acetal mr', 10, 50.00, 500.00, '2026-07-12 10:33:55', 'pharmacy', 'manual', 1, 0.00),
(709, 558, 291, 'Medication: Acetal mr', 10, 500.00, 5000.00, '2026-07-12 10:41:54', 'pharmacy', 'manual', 1, 0.00),
(710, 558, 100, 'Medication: Acetal mr', 10, 50.00, 500.00, '2026-07-12 10:42:21', 'pharmacy', 'manual', 1, 0.00),
(711, 558, 100, 'Medication: Acetal mr', 9, 50.00, 450.00, '2026-07-12 10:43:10', 'pharmacy', 'manual', 1, 0.00),
(712, 559, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 2, 500.00, 1000.00, '2026-07-12 10:54:59', 'pharmacy', 'manual', 1, 0.00),
(713, 559, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-07-12 10:55:08', 'pharmacy', 'manual', 1, 0.00),
(714, 559, 291, 'Medication: Acetal mr', 1, 500.00, 500.00, '2026-07-12 10:55:19', 'pharmacy', 'manual', 1, 0.00),
(715, 559, 10, 'Medication: Cetizine', 5, 10.00, 50.00, '2026-07-12 10:55:40', 'pharmacy', 'manual', 1, 0.00),
(716, 559, 91, 'Medication: Hydrocortisone 100mg ( inj)', 2, 350.00, 700.00, '2026-07-12 10:56:04', 'pharmacy', 'manual', 1, 0.00),
(717, 559, 47, 'Medication: Clotrimazole cream', 1, 200.00, 200.00, '2026-07-12 10:56:43', 'pharmacy', 'manual', 1, 0.00),
(718, 559, 291, 'Medication: Acetal mr', 1, 500.00, 500.00, '2026-07-12 10:58:41', 'pharmacy', 'manual', 1, 0.00),
(719, 562, 201, 'Medication: Tramadol 50mg ( INJ)', 1, 500.00, 500.00, '2026-07-12 13:28:03', 'pharmacy', 'manual', 1, 0.00),
(720, 562, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 3, 500.00, 1500.00, '2026-07-12 13:29:37', 'pharmacy', 'manual', 1, 0.00),
(721, 562, 6, 'Medication: ceftraxne', 4, 400.00, 1600.00, '2026-07-12 13:29:52', 'pharmacy', 'manual', 1, 0.00),
(722, 562, 102, 'Medication: Ampiclox  500mg', 20, 20.00, 400.00, '2026-07-12 13:31:23', 'pharmacy', 'manual', 1, 0.00),
(723, 562, 19, 'Medication: Ibuprofen 400mg', 10, 10.00, 100.00, '2026-07-12 13:32:19', 'pharmacy', 'manual', 1, 0.00),
(724, 562, 5, 'Medication: omeprazole', 1, 100.00, 100.00, '2026-07-12 13:32:37', 'pharmacy', 'manual', 1, 0.00),
(725, 563, 25, 'Service: Blood Sugar Random', 1, 100.00, 100.00, '2026-07-18 08:14:35', 'service', 'manual', 1, 0.00),
(726, 563, 63, 'Service: Hb', 1, 300.00, 300.00, '2026-07-18 08:14:45', 'service', 'manual', 1, 0.00),
(727, 563, 67, 'Medication: Normal saline 500mls', 1, 800.00, 800.00, '2026-07-18 08:15:00', 'pharmacy', 'manual', 1, 0.00),
(728, 563, 225, 'Medication: Tranexamic acid ( inj)', 1, 500.00, 500.00, '2026-07-18 08:15:22', 'pharmacy', 'manual', 1, 0.00),
(729, 563, 17, 'Medication: Hemoforce family 200ml', 1, 800.00, 800.00, '2026-07-18 08:15:38', 'pharmacy', 'manual', 1, 0.00),
(730, 563, 74, 'Medication: Femiplan pills', 1, 200.00, 200.00, '2026-07-18 08:16:00', 'pharmacy', 'manual', 1, 0.00),
(731, 563, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-07-18 08:41:24', 'pharmacy', 'manual', 1, 0.00),
(732, 564, 52, 'Medication: Neonatal ampiclox', 1, 250.00, 250.00, '2026-07-18 15:46:49', 'pharmacy', 'manual', 1, 0.00),
(733, 564, 33, 'Medication: Paracetamol syrup 60mls', 1, 100.00, 100.00, '2026-07-18 15:50:15', 'pharmacy', 'manual', 1, 0.00),
(734, 566, 88, 'Nystatin 30mls', 1, 300.00, 300.00, '2026-07-18 16:22:34', 'pharmacy', 'manual', 1, 0.00),
(735, 567, 52, 'Medication: Neonatal ampiclox', 1, 250.00, 250.00, '2026-07-25 18:19:49', 'pharmacy', 'manual', 1, 0.00),
(736, 567, 13, 'Medication: Promethazine 60ml', 1, 150.00, 150.00, '2026-07-25 18:20:08', 'pharmacy', 'manual', 1, 0.00),
(737, 567, 153, 'Medication: ORS sachets', 2, 50.00, 100.00, '2026-07-25 18:20:48', 'pharmacy', 'manual', 1, 0.00),
(738, 567, 213, 'Medication: Zinc sulphate 20mg', 5, 20.00, 100.00, '2026-07-25 18:21:04', 'pharmacy', 'manual', 1, 0.00),
(739, 570, 206, 'Medication: Amoxicilin 250mg', 1, 20.00, 20.00, '2026-07-27 16:06:17', 'pharmacy', 'manual', 1, 0.00),
(740, 570, 65, 'Service: minor stitching 2', 1, 400.00, 400.00, '2026-07-27 16:06:24', 'service', 'manual', 1, 0.00),
(741, 572, 299, 'Medication: Gacet 250mg', 1, 200.00, 200.00, '2026-07-30 15:31:32', 'pharmacy', 'manual', 1, 0.00),
(742, 572, 232, 'Medication: iv paracetamol 2', 1, 600.00, 600.00, '2026-07-30 15:31:46', 'pharmacy', 'manual', 1, 0.00),
(743, 572, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-07-30 16:10:46', 'pharmacy', 'manual', 1, 0.00),
(744, 572, 24, 'Service: CBC', 1, 500.00, 500.00, '2026-07-30 16:11:28', 'service', 'manual', 1, 0.00),
(745, 572, 94, 'Medication: Metronidazole  100ml', 1, 200.00, 200.00, '2026-07-30 16:11:50', 'pharmacy', 'manual', 1, 0.00),
(746, 572, 14, 'Medication: Betafen plus 100ml', 1, 450.00, 450.00, '2026-07-30 16:12:29', 'pharmacy', 'manual', 1, 0.00),
(747, 572, 51, 'Medication: Ampiclox  100mls', 1, 300.00, 300.00, '2026-07-30 16:17:39', 'pharmacy', 'manual', 1, 0.00),
(748, 572, 96, 'Medication: Dexamethasole 4mg', 2, 10.00, 20.00, '2026-07-30 16:20:49', 'pharmacy', 'manual', 1, 0.00),
(749, 572, 93, 'Medication: Dexamethasole 4mg (INJ)', 1, 400.00, 400.00, '2026-07-30 16:21:47', 'pharmacy', 'manual', 1, 0.00),
(750, 572, 6, 'Medication: ceftraxne', 5, 400.00, 2000.00, '2026-07-30 16:44:52', 'pharmacy', 'manual', 1, 0.00),
(751, 572, 6, 'Medication: ceftraxne', 4, 400.00, 1600.00, '2026-07-30 16:50:05', 'pharmacy', 'manual', 1, 0.00),
(752, 574, 70, 'Medication: Azithromycin 500mg', 1, 300.00, 300.00, '2026-08-01 12:17:51', 'pharmacy', 'manual', 1, 0.00),
(753, 574, 10, 'Medication: Cetizine', 5, 10.00, 50.00, '2026-08-01 12:18:44', 'pharmacy', 'manual', 1, 0.00),
(754, 574, 19, 'Medication: Ibuprofen 400mg', 10, 10.00, 100.00, '2026-08-01 12:19:17', 'pharmacy', 'manual', 1, 0.00),
(755, 574, 30, 'Service: Malaria Test', 1, 200.00, 200.00, '2026-08-01 12:23:22', 'service', 'manual', 1, 0.00),
(756, 574, 24, 'Service: CBC', 1, 500.00, 500.00, '2026-08-01 12:23:56', 'service', 'manual', 1, 0.00),
(757, 574, 37, 'Medication: Amoxiclav 625mg', 1, 1000.00, 1000.00, '2026-08-01 12:24:22', 'pharmacy', 'manual', 1, 0.00),
(758, 574, 100, 'Medication: Acetal mr', 10, 50.00, 500.00, '2026-08-01 12:24:39', 'pharmacy', 'manual', 1, 0.00),
(759, 575, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 3, 500.00, 1500.00, '2026-08-04 05:00:42', 'pharmacy', 'manual', 1, 0.00),
(760, 575, 46, 'Medication: Ceftax (inj)', 2, 500.00, 1000.00, '2026-08-04 05:01:43', 'pharmacy', 'manual', 1, 0.00),
(761, 575, 220, 'Medication: diclofenac inj', 1, 400.00, 400.00, '2026-08-04 05:02:15', 'pharmacy', 'manual', 1, 0.00),
(762, 575, 291, 'Medication: Acetal mr', 1, 500.00, 500.00, '2026-08-04 05:02:31', 'pharmacy', 'manual', 1, 0.00),
(763, 575, 93, 'Medication: Dexamethasole 4mg (INJ)', 1, 400.00, 400.00, '2026-08-04 05:04:00', 'pharmacy', 'manual', 1, 0.00),
(764, 575, 236, 'Medication: normalsaline', 1, 500.00, 500.00, '2026-08-04 05:05:22', 'pharmacy', 'manual', 1, 0.00),
(765, 575, 256, 'Medication: cefbactum', 4, 500.00, 2000.00, '2026-08-04 06:12:54', 'pharmacy', 'manual', 1, 0.00),
(766, 575, 271, 'Medication: Ibucap forte', 10, 20.00, 200.00, '2026-08-04 06:13:38', 'pharmacy', 'manual', 1, 0.00),
(767, 577, 32, 'Service: Urinalysis', 1, 300.00, 300.00, '2026-08-05 18:25:07', 'service', 'manual', 1, 0.00),
(768, 577, 6, 'Medication: ceftraxne', 4, 400.00, 1600.00, '2026-08-05 18:38:35', 'pharmacy', 'manual', 1, 0.00),
(769, 577, 47, 'Medication: Clotrimazole cream', 1, 200.00, 200.00, '2026-08-05 18:38:49', 'pharmacy', 'manual', 1, 0.00),
(770, 577, 95, 'Medication: Clotrimazole pessaries 200mg', 1, 200.00, 200.00, '2026-08-05 18:39:14', 'pharmacy', 'manual', 1, 0.00),
(771, 577, 28, 'Medication: Fluconazole 150 mg', 1, 150.00, 150.00, '2026-08-05 18:39:33', 'pharmacy', 'manual', 1, 0.00),
(772, 577, 70, 'Medication: Azithromycin 500mg', 1, 300.00, 300.00, '2026-08-05 18:39:54', 'pharmacy', 'manual', 1, 0.00),
(773, 577, 19, 'Medication: Ibuprofen 400mg', 20, 10.00, 200.00, '2026-08-05 18:40:38', 'pharmacy', 'manual', 1, 0.00),
(774, 579, 93, 'Medication: Dexamethasole 4mg (INJ)', 2, 400.00, 800.00, '2026-08-05 19:38:21', 'pharmacy', 'manual', 1, 0.00),
(775, 579, 6, 'Medication: ceftraxne', 4, 400.00, 1600.00, '2026-08-05 19:38:36', 'pharmacy', 'manual', 1, 0.00),
(776, 579, 232, 'Medication: iv paracetamol 2', 1, 600.00, 600.00, '2026-08-05 19:39:06', 'pharmacy', 'manual', 1, 0.00),
(777, 579, 70, 'Medication: Azithromycin 500mg', 1, 300.00, 300.00, '2026-08-05 19:39:33', 'pharmacy', 'manual', 1, 0.00),
(778, 581, 32, 'Service: Urinalysis', 1, 300.00, 300.00, '2026-08-06 08:56:59', 'service', 'manual', 1, 0.00),
(779, 581, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-08-06 09:07:16', 'pharmacy', 'manual', 1, 0.00),
(780, 581, 70, 'Medication: Azithromycin 500mg', 1, 300.00, 300.00, '2026-08-06 09:07:48', 'pharmacy', 'manual', 1, 0.00),
(781, 581, 28, 'Medication: Fluconazole 150 mg', 1, 150.00, 150.00, '2026-08-06 09:08:00', 'pharmacy', 'manual', 1, 0.00),
(782, 581, 47, 'Medication: Clotrimazole cream', 1, 200.00, 200.00, '2026-08-06 09:08:12', 'pharmacy', 'manual', 1, 0.00),
(783, 581, 19, 'Medication: Ibuprofen 400mg', 10, 10.00, 100.00, '2026-08-06 09:09:06', 'pharmacy', 'manual', 1, 0.00),
(784, 582, 306, 'Medication: canula', 1, 100.00, 100.00, '2026-08-06 16:37:19', 'pharmacy', 'manual', 1, 0.00),
(785, 582, 96, 'Medication: Dexamethasole 4mg', 1, 10.01, 10.01, '2026-08-06 16:38:55', 'pharmacy', 'manual', 1, 0.00),
(786, 582, 6, 'Medication: ceftraxne', 1, 400.00, 400.00, '2026-08-06 16:39:38', 'pharmacy', 'manual', 1, 0.00),
(787, 582, 232, 'Medication: iv paracetamol 2', 1, 600.00, 600.00, '2026-08-06 16:40:22', 'pharmacy', 'manual', 1, 0.00),
(788, 582, 38, 'Medication: Amoxiclav 228mg', 1, 1000.00, 1000.00, '2026-08-06 16:42:00', 'pharmacy', 'manual', 1, 0.00),
(789, 582, 21, 'Medication: Predinisolone 60ml', 1, 400.00, 400.00, '2026-08-06 16:42:37', 'pharmacy', 'manual', 1, 0.00),
(790, 582, 93, 'Medication: Dexamethasole 4mg (INJ)', 1, 400.00, 400.00, '2026-08-06 16:43:26', 'pharmacy', 'manual', 1, 0.00),
(791, 582, 108, 'Medication: Ibugesic  60mls', 1, 400.00, 400.00, '2026-08-06 16:44:08', 'pharmacy', 'manual', 1, 0.00),
(792, 582, 21, 'Medication: Predinisolone 60ml', 1, 400.00, 400.00, '2026-08-06 17:15:38', 'pharmacy', 'manual', 1, 0.00),
(793, 583, 21, 'Medication: Predinisolone 60ml', 1, 400.00, 400.00, '2026-08-07 03:28:19', 'pharmacy', 'manual', 1, 0.00),
(794, 583, 215, 'Medication: Metronidazole  600ml', 1, 100.00, 100.00, '2026-08-07 03:28:53', 'pharmacy', 'manual', 1, 0.00),
(795, 583, 4, 'Medication: tridex', 1, 100.00, 100.00, '2026-08-07 03:30:56', 'pharmacy', 'manual', 1, 0.00),
(796, 583, 38, 'Medication: Amoxiclav 228mg', 1, 1000.00, 1000.00, '2026-08-07 03:39:30', 'pharmacy', 'manual', 1, 0.00),
(797, 586, 232, 'Medication: iv paracetamol 2', 1, 600.00, 600.00, '2026-08-08 19:26:39', 'pharmacy', 'manual', 1, 0.00),
(798, 586, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 1, 500.00, 500.00, '2026-08-08 19:27:14', 'pharmacy', 'manual', 1, 0.00),
(799, 586, 284, 'Medication: plasil inj', 1, 400.00, 400.00, '2026-08-08 19:27:28', 'pharmacy', 'manual', 1, 0.00),
(800, 586, 6, 'Medication: ceftraxne', 1, 400.00, 400.00, '2026-08-08 19:48:29', 'pharmacy', 'manual', 1, 0.00),
(801, 586, 94, 'Medication: Metronidazole  100ml', 1, 200.00, 200.00, '2026-08-08 19:48:39', 'pharmacy', 'manual', 1, 0.00),
(802, 586, 59, 'Medication: Azithromycin 15mls', 1, 150.00, 150.00, '2026-08-08 19:49:11', 'pharmacy', 'manual', 1, 0.00),
(803, 586, 35, 'Medication: Cetamol 60mls', 1, 150.00, 150.00, '2026-08-08 19:49:38', 'pharmacy', 'manual', 1, 0.00),
(804, 588, 67, 'Medication: Normal saline 500mls', 1, 800.00, 800.00, '2026-08-12 13:12:59', 'pharmacy', 'manual', 1, 0.00),
(805, 588, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 1, 500.00, 500.00, '2026-08-12 13:21:44', 'pharmacy', 'manual', 1, 0.00),
(806, 588, 6, 'Medication: ceftraxne', 1, 400.00, 400.00, '2026-08-12 13:22:06', 'pharmacy', 'manual', 1, 0.00),
(807, 588, 153, 'Medication: ORS sachets', 3, 50.00, 150.00, '2026-08-12 13:22:26', 'pharmacy', 'manual', 1, 0.00),
(808, 588, 213, 'Medication: Zinc sulphate 20mg', 5, 20.00, 100.00, '2026-08-12 13:22:39', 'pharmacy', 'manual', 1, 0.00),
(809, 588, 35, 'Medication: Cetamol 60mls', 1, 150.00, 150.00, '2026-08-12 13:26:56', 'pharmacy', 'manual', 1, 0.00),
(810, 588, 6, 'Medication: ceftraxne', 2, 400.00, 800.00, '2026-08-12 13:41:04', 'pharmacy', 'manual', 1, 0.00),
(811, 588, 58, 'Service: stool  for o/c', 1, 300.00, 300.00, '2026-08-12 15:34:28', 'service', 'manual', 1, 0.00),
(812, 589, 58, 'Service: stool  for o/c', 1, 300.00, 300.00, '2026-08-13 13:33:56', 'service', 'manual', 1, 0.00),
(813, 589, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 1, 500.00, 500.00, '2026-08-13 13:35:28', 'pharmacy', 'manual', 1, 0.00),
(814, 589, 6, 'Medication: ceftraxne', 4, 400.00, 1600.00, '2026-08-13 14:00:44', 'pharmacy', 'manual', 1, 0.00),
(815, 589, 94, 'Medication: Metronidazole  100ml', 1, 200.00, 200.00, '2026-08-13 14:01:34', 'pharmacy', 'manual', 1, 0.00),
(816, 589, 56, 'Medication: Plasil  syrup', 1, 250.00, 250.00, '2026-08-13 14:02:08', 'pharmacy', 'manual', 1, 0.00),
(817, 589, 61, 'Medication: ABZ  400mg', 1, 100.00, 100.00, '2026-08-13 14:02:35', 'pharmacy', 'manual', 1, 0.00),
(818, 589, 67, 'Medication: Normal saline 500mls', 1, 800.00, 800.00, '2026-08-13 14:44:00', 'pharmacy', 'manual', 1, 0.00),
(819, 589, 123, 'Medication: Gacet 125mg', 1, 100.00, 100.00, '2026-08-13 15:49:04', 'pharmacy', 'manual', 1, 0.00),
(820, 590, 6, 'Medication: ceftraxne', 4, 400.00, 1600.00, '2026-08-14 05:27:28', 'pharmacy', 'manual', 1, 0.00),
(821, 590, 28, 'Medication: Fluconazole 150 mg', 1, 150.00, 150.00, '2026-08-14 05:27:49', 'pharmacy', 'manual', 1, 0.00),
(822, 590, 95, 'Medication: Clotrimazole pessaries 200mg', 1, 200.00, 200.00, '2026-08-14 05:28:34', 'pharmacy', 'manual', 1, 0.00),
(823, 590, 255, 'Medication: Gentamycin inj', 1, 350.00, 350.00, '2026-08-14 05:29:20', 'pharmacy', 'manual', 1, 0.00),
(824, 590, 70, 'Medication: Azithromycin 500mg', 1, 300.00, 300.00, '2026-08-14 05:29:41', 'pharmacy', 'manual', 1, 0.00),
(825, 590, 32, 'Service: Urinalysis', 1, 300.00, 300.00, '2026-08-14 05:30:32', 'service', 'manual', 1, 0.00),
(826, 593, 232, 'Medication: iv paracetamol 2', 1, 600.00, 600.00, '2026-08-14 09:48:54', 'pharmacy', 'manual', 1, 0.00),
(827, 593, 59, 'Medication: Azithromycin 15mls', 1, 150.00, 150.00, '2026-08-14 09:49:04', 'pharmacy', 'manual', 1, 0.00),
(828, 593, 94, 'Medication: Metronidazole  100ml', 1, 200.00, 200.00, '2026-08-14 09:49:24', 'pharmacy', 'manual', 1, 0.00),
(829, 593, 32, 'Medication: Paracetamol syrup 100mls', 1, 150.00, 150.00, '2026-08-14 09:50:55', 'pharmacy', 'manual', 1, 0.00),
(830, 593, 6, 'Medication: ceftraxne', 1, 400.00, 400.00, '2026-08-14 09:51:03', 'pharmacy', 'manual', 1, 0.00),
(831, 593, 70, 'Medication: Azithromycin 500mg', 1, 300.00, 300.00, '2026-08-14 09:57:45', 'pharmacy', 'manual', 1, 0.00),
(832, 595, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-08-14 12:44:50', 'pharmacy', 'manual', 1, 0.00),
(833, 595, 207, 'Medication: Amoxicilin 500mg', 15, 10.00, 150.00, '2026-08-14 12:45:11', 'pharmacy', 'manual', 1, 0.00),
(834, 595, 86, 'Medication: Paracetamol 500mg', 20, 5.00, 100.00, '2026-08-14 12:45:35', 'pharmacy', 'manual', 1, 0.00),
(835, 598, 24, 'Service: CBC', 1, 500.00, 500.00, '2026-08-19 06:46:03', 'service', 'manual', 1, 0.00),
(836, 598, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-08-19 07:17:07', 'pharmacy', 'manual', 1, 0.00),
(837, 598, 60, 'Medication: Prednisolone 5mg  (cosmos0', 10, 10.00, 100.00, '2026-08-19 07:17:24', 'pharmacy', 'manual', 1, 0.00),
(838, 598, 291, 'Medication: Acetal mr', 1, 500.00, 500.00, '2026-08-19 07:17:44', 'pharmacy', 'manual', 1, 0.00),
(839, 600, 232, 'Medication: iv paracetamol 2', 1, 600.00, 600.00, '2026-08-19 14:44:03', 'pharmacy', 'manual', 1, 0.00),
(840, 600, 284, 'Medication: plasil inj', 1, 400.00, 400.00, '2026-08-19 14:44:51', 'pharmacy', 'manual', 1, 0.00),
(841, 600, 46, 'Medication: Ceftax (inj)', 1, 500.00, 500.00, '2026-08-19 14:45:18', 'pharmacy', 'manual', 1, 0.00),
(842, 600, 51, 'Medication: Ampiclox  100mls', 1, 300.00, 300.00, '2026-08-19 14:45:57', 'pharmacy', 'manual', 1, 0.00),
(843, 600, 108, 'Medication: Ibugesic  60mls', 1, 400.00, 400.00, '2026-08-19 14:46:24', 'pharmacy', 'manual', 1, 0.00),
(844, 600, 6, 'Medication: ceftraxne', 1, 400.00, 400.00, '2026-08-19 15:18:03', 'pharmacy', 'manual', 1, 0.00),
(845, 598, 67, 'Medication: Normal saline 500mls', 1, 800.00, 800.00, '2026-08-20 13:32:40', 'pharmacy', 'manual', 1, 0.00),
(846, 602, 51, 'Medication: Ampiclox  100mls', 1, 300.00, 300.00, '2026-08-21 16:13:45', 'pharmacy', 'manual', 1, 0.00),
(847, 602, 46, 'Medication: Ceftax (inj)', 4, 500.00, 2000.00, '2026-08-21 16:15:19', 'pharmacy', 'manual', 1, 0.00),
(848, 604, 206, 'Amoxicilin 250mg', 1, 20.00, 20.00, '2026-08-21 16:22:37', 'pharmacy', 'manual', 1, 0.00),
(849, 605, 286, 'Medication: ceftaxidine', 3, 500.00, 1500.00, '2026-08-21 18:13:49', 'pharmacy', 'manual', 1, 0.00),
(850, 605, 123, 'Medication: Gacet 125mg', 1, 100.00, 100.00, '2026-08-21 18:14:04', 'pharmacy', 'manual', 1, 0.00),
(851, 605, 58, 'Medication: Ibugesic 100mls', 1, 450.00, 450.00, '2026-08-21 18:14:32', 'pharmacy', 'manual', 1, 0.00),
(852, 605, 51, 'Medication: Ampiclox  100mls', 1, 300.00, 300.00, '2026-08-21 18:14:54', 'pharmacy', 'manual', 1, 0.00),
(853, 605, 93, 'Medication: Dexamethasole 4mg (INJ)', 1, 400.00, 400.00, '2026-08-21 18:15:12', 'pharmacy', 'manual', 1, 0.00),
(854, 605, 232, 'Medication: iv paracetamol 2', 1, 600.00, 600.00, '2026-08-21 18:18:29', 'pharmacy', 'manual', 1, 0.00),
(855, 605, 67, 'Medication: Normal saline 500mls', 1, 800.00, 800.00, '2026-08-21 18:54:43', 'pharmacy', 'manual', 1, 0.00),
(856, 607, 232, 'Medication: iv paracetamol 2', 1, 600.00, 600.00, '2026-08-22 10:00:25', 'pharmacy', 'manual', 1, 0.00),
(857, 607, 232, 'Medication: iv paracetamol 2', 1, 600.00, 600.00, '2026-08-22 10:00:25', 'pharmacy', 'manual', 1, 0.00),
(858, 607, 93, 'Medication: Dexamethasole 4mg (INJ)', 1, 400.00, 400.00, '2026-08-22 10:10:57', 'pharmacy', 'manual', 1, 0.00),
(859, 607, 91, 'Medication: Hydrocortisone 100mg ( inj)', 1, 350.00, 350.00, '2026-08-22 10:11:06', 'pharmacy', 'manual', 1, 0.00),
(860, 607, 236, 'Medication: normalsaline', 1, 500.00, 500.00, '2026-08-22 10:17:28', 'pharmacy', 'manual', 1, 0.00),
(861, 607, 236, 'Medication: normalsaline', 1, 500.00, 500.00, '2026-08-22 10:17:29', 'pharmacy', 'manual', 1, 0.00),
(862, 607, 79, 'Medication: Ringers lactate 500mls', 1, 800.00, 800.00, '2026-08-22 10:18:41', 'pharmacy', 'manual', 1, 0.00),
(863, 607, 64, 'Medication: Aminophylline (inj) 10mls', 1, 800.00, 800.00, '2026-08-22 11:15:04', 'pharmacy', 'manual', 1, 0.00),
(864, 607, 82, 'Medication: Metronidazole 500mg /100mls ( infusion)', 1, 500.00, 500.00, '2026-08-22 11:15:53', 'pharmacy', 'manual', 1, 0.00),
(865, 607, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-08-22 11:18:18', 'pharmacy', 'manual', 1, 0.00),
(866, 607, 153, 'Medication: ORS sachets', 3, 50.00, 150.00, '2026-08-22 11:21:25', 'pharmacy', 'manual', 1, 0.00),
(867, 607, 213, 'Medication: Zinc sulphate 20mg', 6, 20.00, 120.00, '2026-08-22 11:22:01', 'pharmacy', 'manual', 1, 0.00),
(868, 607, 94, 'Medication: Metronidazole  100ml', 1, 200.00, 200.00, '2026-08-22 12:34:24', 'pharmacy', 'manual', 1, 0.00),
(869, 607, 182, 'Medication: Betafen plus 60ml', 1, 400.00, 400.00, '2026-08-22 12:37:18', 'pharmacy', 'manual', 1, 0.00),
(870, 607, 21, 'Medication: Predinisolone 60ml', 6, 400.00, 2400.00, '2026-08-22 13:26:42', 'pharmacy', 'manual', 1, 0.00),
(871, 607, 21, 'Medication: Predinisolone 60ml', 1, 400.00, 400.00, '2026-08-22 13:27:03', 'pharmacy', 'manual', 1, 0.00),
(872, 610, 232, 'Medication: iv paracetamol 2', 1, 600.00, 600.00, '2026-08-23 07:23:32', 'pharmacy', 'manual', 1, 0.00),
(873, 610, 232, 'Medication: iv paracetamol 2', 1, 600.00, 600.00, '2026-08-23 07:23:32', 'pharmacy', 'manual', 1, 0.00),
(874, 610, 93, 'Medication: Dexamethasole 4mg (INJ)', 1, 400.00, 400.00, '2026-08-23 07:23:51', 'pharmacy', 'manual', 1, 0.00),
(875, 610, 256, 'Medication: cefbactum', 1, 500.00, 500.00, '2026-08-23 07:24:07', 'pharmacy', 'manual', 1, 0.00),
(876, 610, 123, 'Medication: Gacet 125mg', 1, 100.00, 100.00, '2026-08-23 07:24:21', 'pharmacy', 'manual', 1, 0.00),
(877, 610, 58, 'Medication: Ibugesic 100mls', 1, 450.00, 450.00, '2026-08-23 07:24:58', 'pharmacy', 'manual', 1, 0.00),
(878, 610, 51, 'Medication: Ampiclox  100mls', 1, 300.00, 300.00, '2026-08-23 07:26:04', 'pharmacy', 'manual', 1, 0.00),
(879, 611, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-08-25 05:21:42', 'pharmacy', 'manual', 1, 0.00),
(880, 611, 93, 'Medication: Dexamethasole 4mg (INJ)', 3, 400.00, 1200.00, '2026-08-25 05:22:10', 'pharmacy', 'manual', 1, 0.00),
(881, 611, 60, 'Medication: Prednisolone 5mg  (cosmos0', 10, 10.00, 100.00, '2026-08-25 05:22:26', 'pharmacy', 'manual', 1, 0.00),
(882, 611, 44, 'Medication: Meloxicam 15 mg', 10, 20.00, 200.00, '2026-08-25 05:22:45', 'pharmacy', 'manual', 1, 0.00),
(883, 611, 113, 'Medication: Neuroforte', 3, 30.00, 90.00, '2026-08-25 05:23:03', 'pharmacy', 'manual', 1, 0.00),
(884, 613, 67, 'Service: normal delivery', 1, 8500.00, 8500.00, '2026-08-25 16:22:36', 'service', 'manual', 1, 0.00),
(885, 613, 8, 'Medication: buscpan inj', 1, 400.00, 400.00, '2026-08-25 16:23:48', 'pharmacy', 'manual', 1, 0.00),
(886, 613, 8, 'Medication: buscpan inj', 1, 400.00, 400.00, '2026-08-25 16:24:18', 'pharmacy', 'manual', 1, 0.00),
(887, 615, 232, 'Medication: iv paracetamol 2', 1, 600.00, 600.00, '2026-08-26 11:17:46', 'pharmacy', 'manual', 1, 0.00),
(888, 615, 67, 'Medication: Normal saline 500mls', 1, 800.00, 800.00, '2026-08-26 11:18:11', 'pharmacy', 'manual', 1, 0.00),
(889, 615, 201, 'Medication: Tramadol 50mg ( INJ)', 1, 500.00, 500.00, '2026-08-26 11:18:37', 'pharmacy', 'manual', 1, 0.00),
(890, 616, 24, 'Service: CBC', 1, 500.00, 500.00, '2026-08-26 11:33:49', 'service', 'manual', 1, 0.00),
(891, 616, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-08-26 13:02:39', 'pharmacy', 'manual', 1, 0.00);
INSERT INTO `invoice_items` (`id`, `invoice_id`, `med_id`, `description`, `qty`, `unit_price`, `total`, `created_at`, `item_type`, `source`, `quantity`, `price`) VALUES
(892, 616, 291, 'Medication: Acetal mr', 1, 500.00, 500.00, '2026-08-26 13:02:57', 'pharmacy', 'manual', 1, 0.00),
(893, 615, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-08-26 13:43:43', 'pharmacy', 'manual', 1, 0.00),
(894, 615, 75, 'Medication: Diclofenac 100mg', 10, 15.00, 150.00, '2026-08-26 13:44:33', 'pharmacy', 'manual', 1, 0.00),
(895, 615, 70, 'Medication: Azithromycin 500mg', 1, 300.00, 300.00, '2026-08-26 13:44:48', 'pharmacy', 'manual', 1, 0.00),
(896, 615, 39, 'Medication: Amoxiclav 156mg', 1, 800.00, 800.00, '2026-08-26 14:06:47', 'pharmacy', 'manual', 1, 0.00),
(897, 615, 291, 'Medication: Acetal mr', 1, 500.00, 500.00, '2026-08-26 14:07:12', 'pharmacy', 'manual', 1, 0.00),
(898, 618, 256, 'Medication: cefbactum', 4, 500.00, 2000.00, '2026-08-26 14:20:12', 'pharmacy', 'manual', 1, 0.00),
(899, 618, 232, 'Medication: iv paracetamol 2', 1, 600.00, 600.00, '2026-08-26 14:20:19', 'pharmacy', 'manual', 1, 0.00),
(900, 618, 123, 'Medication: Gacet 125mg', 1, 100.00, 100.00, '2026-08-26 14:20:31', 'pharmacy', 'manual', 1, 0.00),
(901, 618, 58, 'Medication: Ibugesic 100mls', 1, 450.00, 450.00, '2026-08-26 14:20:54', 'pharmacy', 'manual', 1, 0.00),
(902, 618, 51, 'Medication: Ampiclox  100mls', 1, 300.00, 300.00, '2026-08-26 14:21:10', 'pharmacy', 'manual', 1, 0.00),
(903, 618, 93, 'Medication: Dexamethasole 4mg (INJ)', 1, 400.00, 400.00, '2026-08-26 14:21:52', 'pharmacy', 'manual', 1, 0.00),
(904, 620, 201, 'Medication: Tramadol 50mg ( INJ)', 1, 500.00, 500.00, '2026-08-26 16:50:38', 'pharmacy', 'manual', 1, 0.00),
(905, 620, 201, 'Medication: Tramadol 50mg ( INJ)', 1, 500.00, 500.00, '2026-08-26 16:51:20', 'pharmacy', 'manual', 1, 0.00),
(906, 623, 142, 'Medication: depo( inj)', 1, 200.00, 200.00, '2026-08-27 07:32:49', 'pharmacy', 'manual', 1, 0.00),
(907, 627, 240, 'immunizatinon', 1, 200.00, 200.00, '2026-08-27 08:21:39', 'pharmacy', 'manual', 1, 0.00),
(908, 628, 6, 'Medication: ceftraxne', 3, 400.00, 1200.00, '2026-08-27 08:40:18', 'pharmacy', 'manual', 1, 0.00),
(909, 628, 93, 'Medication: Dexamethasole 4mg (INJ)', 1, 400.00, 400.00, '2026-08-27 08:40:29', 'pharmacy', 'manual', 1, 0.00),
(910, 628, 21, 'Medication: Predinisolone 60ml', 1, 400.00, 400.00, '2026-08-27 08:40:46', 'pharmacy', 'manual', 1, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `invoice_payments`
--

CREATE TABLE `invoice_payments` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `payer_source` enum('Patient','SHA','Insurance','Corporate','Other') NOT NULL DEFAULT 'Patient',
  `payer_id` int(11) DEFAULT NULL,
  `patient_coverage_id` int(11) DEFAULT NULL,
  `payment_method` enum('Cash','Mpesa','Card','Bank','Cheque','Transfer','Credit','Other') NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reference_number` varchar(100) DEFAULT NULL,
  `external_reference` varchar(100) DEFAULT NULL,
  `payment_status` enum('Pending','Completed','Reversed','Failed') NOT NULL DEFAULT 'Completed',
  `received_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_payment_allocations`
--

CREATE TABLE `invoice_payment_allocations` (
  `id` int(11) NOT NULL,
  `invoice_payment_id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `invoice_financial_allocation_id` int(11) DEFAULT NULL,
  `claim_id` int(11) DEFAULT NULL,
  `remittance_item_id` int(11) DEFAULT NULL,
  `applied_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `allocation_notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_procedures`
--

CREATE TABLE `lab_procedures` (
  `id` int(11) NOT NULL,
  `procedure_name` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_requests`
--

CREATE TABLE `lab_requests` (
  `id` int(11) NOT NULL,
  `encounter_id` int(11) DEFAULT NULL,
  `procedure_id` int(11) DEFAULT NULL,
  `status` enum('pending','done') DEFAULT 'pending',
  `requested_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `patient_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lab_requests`
--

INSERT INTO `lab_requests` (`id`, `encounter_id`, `procedure_id`, `status`, `requested_by`, `created_at`, `patient_id`) VALUES
(1, 0, 34, 'pending', 1, '2026-02-25 14:43:37', 19);

-- --------------------------------------------------------

--
-- Table structure for table `lab_results`
--

CREATE TABLE `lab_results` (
  `id` int(11) NOT NULL,
  `lab_test_id` int(11) DEFAULT NULL,
  `result_text` text DEFAULT NULL,
  `reported_by` int(11) DEFAULT NULL,
  `reported_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_tests`
--

CREATE TABLE `lab_tests` (
  `id` int(11) NOT NULL,
  `encounter_id` int(11) DEFAULT NULL,
  `patient_id` int(11) NOT NULL,
  `requested_by` int(11) DEFAULT NULL,
  `test_name` varchar(255) NOT NULL,
  `status` enum('requested','processing','completed') DEFAULT 'requested',
  `requested_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_tests_master`
--

CREATE TABLE `lab_tests_master` (
  `id` int(11) NOT NULL,
  `test_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_tests_master`
--

INSERT INTO `lab_tests_master` (`id`, `test_name`, `price`) VALUES
(1, 'CBC', 800.00),
(2, 'Blood Sugar', 300.00),
(3, 'HbA1c', 1500.00),
(4, 'Liver Function Test', 1800.00),
(5, 'Kidney Function Test', 1800.00),
(6, 'Lipid Profile', 2000.00),
(7, 'Urinalysis', 500.00),
(8, 'Malaria Test', 400.00),
(9, 'Typhoid Test', 600.00),
(10, 'HIV Test', 500.00),
(11, 'Pregnancy Test', 300.00),
(12, 'Blood Grouping', 400.00),
(13, 'ESR', 500.00),
(14, 'CRP', 900.00),
(15, 'PSA', 2500.00);

-- --------------------------------------------------------

--
-- Table structure for table `maternity`
--

CREATE TABLE `maternity` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `anc_number` varchar(60) DEFAULT NULL,
  `gravida` int(11) DEFAULT 0,
  `parity` int(11) DEFAULT 0,
  `last_menstrual_period` date DEFAULT NULL,
  `expected_delivery` date DEFAULT NULL,
  `antenatal_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `maternity`
--

INSERT INTO `maternity` (`id`, `patient_id`, `anc_number`, `gravida`, `parity`, `last_menstrual_period`, `expected_delivery`, `antenatal_notes`, `created_at`) VALUES
(3, 28, 'TEMP-1772369892', 0, 0, NULL, '2026-12-01', NULL, '2026-03-01 12:58:12'),
(4, 33, 'TEMP-1772371101', 0, 0, NULL, '2026-12-01', NULL, '2026-03-01 13:18:21'),
(5, 31, 'TEMP-1772371352', 0, 0, NULL, '2026-12-01', NULL, '2026-03-01 13:22:32'),
(6, 144, 'ANC-2026-0144', 0, 0, NULL, NULL, NULL, '2026-04-20 12:55:12'),
(7, 169, 'ANC-2026-0169', 0, 0, NULL, NULL, NULL, '2026-05-16 08:25:42'),
(8, 185, 'ANC-2026-0185', 0, 0, NULL, NULL, NULL, '2026-06-08 14:23:48'),
(9, 194, 'ANC-2026-0194', 0, 0, NULL, NULL, NULL, '2026-06-14 06:13:24'),
(10, 248, 'ANC-2026-0248', 0, 0, NULL, NULL, NULL, '2026-08-25 04:49:38');

-- --------------------------------------------------------

--
-- Table structure for table `maternity_admissions`
--

CREATE TABLE `maternity_admissions` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `admission_date` datetime NOT NULL,
  `ward` varchar(100) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Admitted'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maternity_baby`
--

CREATE TABLE `maternity_baby` (
  `id` int(11) NOT NULL,
  `maternity_id` int(11) NOT NULL,
  `baby_number` int(11) DEFAULT 1,
  `gender` varchar(10) DEFAULT NULL,
  `weight` decimal(6,2) DEFAULT NULL,
  `apgar` varchar(20) DEFAULT NULL,
  `resuscitation` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `alive` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `maternity_baby`
--

INSERT INTO `maternity_baby` (`id`, `maternity_id`, `baby_number`, `gender`, `weight`, `apgar`, `resuscitation`, `notes`, `created_at`, `alive`) VALUES
(3, 5, 1, 'Female', 3.00, '10/10', 'None', 'Mother delivered a live female infant wih an apgar score 0f 7 in 1 min ,8 in 5 min and 10 in 10 min\\\'both 10 iu oxytocin administered , placenta expelled,. both mother and baby stable', '2026-03-13 14:04:29', 1),
(4, 5, 0, 'Male', 0.00, '', 'None', '', '2026-04-06 16:05:50', 1),
(5, 8, 0, 'Male', 2.70, '10/10', 'None', 'mother rogressed well in labour to secnd stage at 1;20, at1;25pm mther delivwered a live male infant with an apgar score of at 1 9,at 5min 10 and at10 mins 10.weighing 2.7kgs.placenta expelled, oxytocin 10iuadministered ,bloodloss 10mls .baby well breastfeeding. both mother and baby are stable', '2026-06-08 16:16:57', 1),
(6, 9, 0, 'Male', 2.50, '8/10', 'None', 'mother progressed well to 2nd stage of labor and delivered a LMI of APGAR score of 8^1,9^5,10^10 weighing 2.5kg,10IU Oxytocin administered IM, pacenta,membranes and clots completely expelled episiotomy done, well sutured uterus well contracted  AML 200 mls ,both mother and baby stable,mother advised to keep the baby warm and initiate B/F.', '2026-06-14 15:29:07', 1);

-- --------------------------------------------------------

--
-- Table structure for table `maternity_billing`
--

CREATE TABLE `maternity_billing` (
  `id` int(11) NOT NULL,
  `maternity_id` int(11) NOT NULL,
  `item` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maternity_deliveries`
--

CREATE TABLE `maternity_deliveries` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `delivery_date` datetime NOT NULL,
  `type` varchar(100) DEFAULT NULL,
  `complications` text DEFAULT NULL,
  `baby_weight` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maternity_delivery`
--

CREATE TABLE `maternity_delivery` (
  `id` int(11) NOT NULL,
  `maternity_id` int(11) NOT NULL,
  `delivery_time` datetime DEFAULT NULL,
  `delivery_mode` varchar(50) DEFAULT NULL,
  `primary_doctor` int(11) DEFAULT NULL,
  `mother_condition` varchar(255) DEFAULT NULL,
  `complications` text DEFAULT NULL,
  `blood_loss` decimal(8,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `maternity_delivery`
--

INSERT INTO `maternity_delivery` (`id`, `maternity_id`, `delivery_time`, `delivery_mode`, `primary_doctor`, `mother_condition`, `complications`, `blood_loss`, `notes`, `created_at`) VALUES
(3, 5, '2026-02-28 18:00:00', 'SVD', 0, 'STABLE', 'NONE', 150.00, 'Mother delivered a live female infant wih an apgar score 0f 7 in 1 min ,8 in 5 min and 10 in 10 min\\\'both 10 iu oxytocin administered , placenta expelled,. both mother and baby stable', '2026-03-13 14:04:29'),
(4, 5, '2026-04-06 18:04:00', 'SVD', 0, '', '', 0.00, '', '2026-04-06 16:05:50'),
(5, 8, '2026-06-08 13:25:00', 'SVD', 0, 'STABLE', 'NONE', 150.00, 'mother rogressed well in labour to secnd stage at 1;20, at1;25pm mther delivwered a live male infant with an apgar score of at 1 9,at 5min 10 and at10 mins 10.weighing 2.7kgs.placenta expelled, oxytocin 10iuadministered ,bloodloss 10mls .baby well breastfeeding. both mother and baby are stable', '2026-06-08 16:16:56'),
(6, 9, '2026-06-14 15:10:00', 'SVD', 0, 'STABLE', 'NONE', 200.00, 'mother progressed well to 2nd stage of labor and delivered a LMI of APGAR score of 8^1,9^5,10^10 weighing 2.5kg,10IU Oxytocin administered IM, pacenta,membranes and clots completely expelled episiotomy done, well sutured uterus well contracted  AML 200 mls ,both mother and baby stable,mother advised to keep the baby warm and initiate B/F.', '2026-06-14 15:29:07');

-- --------------------------------------------------------

--
-- Table structure for table `maternity_profiles`
--

CREATE TABLE `maternity_profiles` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `gravida` int(11) DEFAULT NULL,
  `para` int(11) DEFAULT NULL,
  `lmp` date DEFAULT NULL,
  `edd` date DEFAULT NULL,
  `risk_level` enum('low','medium','high') DEFAULT 'low',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maternity_records`
--

CREATE TABLE `maternity_records` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `gravida` int(11) DEFAULT NULL,
  `para` int(11) DEFAULT NULL,
  `last_menstrual_period` date DEFAULT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maternity_visits`
--

CREATE TABLE `maternity_visits` (
  `id` int(11) NOT NULL,
  `maternity_id` int(11) NOT NULL,
  `visit_type` enum('ANC','PNC','Labour') NOT NULL,
  `bp` varchar(50) DEFAULT NULL,
  `temp` varchar(20) DEFAULT NULL,
  `pulse` varchar(20) DEFAULT NULL,
  `weight` varchar(20) DEFAULT NULL,
  `fetal_heart_rate` varchar(20) DEFAULT NULL,
  `cervix` varchar(50) DEFAULT NULL,
  `membrane_status` varchar(50) DEFAULT NULL,
  `drugs_given` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `maternity_visits`
--

INSERT INTO `maternity_visits` (`id`, `maternity_id`, `visit_type`, `bp`, `temp`, `pulse`, `weight`, `fetal_heart_rate`, `cervix`, `membrane_status`, `drugs_given`, `notes`, `created_at`) VALUES
(3, 5, 'Labour', '105/70', '37.6', '72b/m', '', '138b/min', '6cm', 'ruptured', 'Drugs: Oxytocin | Srvs: maternity', 'mother was fully dilated at 6am,progressed to 2nd stage of labour and delivered a live female neonate AT 6.20AM with a apgar score of 7 IN 1 MIN ,10 IN 5 MIN AND 10 IN 10 MIN ,WEIGHING 3KG.PLACENTA EXPELLED. blood loss 100mls .mother and baby both stable and in good condition\'.\n[DELIVERY RECORD: Outcome: Live Birth | Mode: SVD | Baby: Female, 3kg]', '2026-03-01 15:15:19'),
(4, 7, 'Labour', '1210/80', '36', '72b/m', '67', '138b/min', '2cm', 'intact', 'Drugs: None | Procedures: None', 'mothe rogresses well and at 10 she delivered a live female infant weighing 2.9kgs with an apar score of 1[6] 5[8] 10[10].placenta expelled oxytocin 10iu administered .boh mother and baby are stable and in good condition\n[DELIVERY RECORD: Outcome: Live Birth | Mode: SVD | Baby: Female, 2.9kg]', '2026-05-16 08:32:00'),
(5, 10, 'ANC', '120/80', '', '', '67', '138b/min', '2cm', 'intact', 'Drugs: None | Procedures: None', 'mother came thro opd comlaining of laps radiating to the back\r\n.on ve she was 2cm dilated with fhr of 138b/m;', '2026-08-25 04:55:06');

-- --------------------------------------------------------

--
-- Table structure for table `medications`
--

CREATE TABLE `medications` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `unit` varchar(50) NOT NULL,
  `buying_price` decimal(10,2) NOT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `newborns`
--

CREATE TABLE `newborns` (
  `id` int(11) NOT NULL,
  `delivery_id` int(11) DEFAULT NULL,
  `baby_name` varchar(100) DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `birth_weight` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `patient_number` varchar(20) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `findings` text DEFAULT NULL,
  `clinical_history` text DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `age` int(11) DEFAULT NULL,
  `clinic_category` varchar(50) DEFAULT NULL,
  `presenting_complaint` text DEFAULT NULL,
  `hpc` text DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `pmh` text DEFAULT NULL,
  `psh` text DEFAULT NULL,
  `drug_history` text DEFAULT NULL,
  `family_history` text DEFAULT NULL,
  `social_history` text DEFAULT NULL,
  `ros` text DEFAULT NULL,
  `examination` text DEFAULT NULL,
  `appointment_date` datetime DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `next_of_kin_name` varchar(255) DEFAULT NULL,
  `next_of_kin_phone` varchar(20) DEFAULT NULL,
  `diagnosis` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `patient_number`, `full_name`, `gender`, `date_of_birth`, `phone`, `findings`, `clinical_history`, `doctor_id`, `email`, `address`, `created_at`, `age`, `clinic_category`, `presenting_complaint`, `hpc`, `allergies`, `pmh`, `psh`, `drug_history`, `family_history`, `social_history`, `ros`, `examination`, `appointment_date`, `id_number`, `next_of_kin_name`, `next_of_kin_phone`, `diagnosis`) VALUES
(20, 'EMC-20260226-792341', 'Redempta     NDANU', 'Female', '1994-12-25', '', NULL, NULL, 2, NULL, '', '2026-02-26 06:38:43', 31, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL, '0', '0703940666', NULL),
(21, 'EMC-20260226-479638', 'Redempta  Kithiaka', 'Female', NULL, '', NULL, NULL, 2, NULL, '', '2026-02-26 08:33:16', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL, '0', '0703940666', NULL),
(24, 'EMC-20260226-646330', 'Immaculate Kawira', 'Female', '2005-03-03', '07107346400', NULL, NULL, 5, NULL, 'Mlolongo', '2026-02-26 17:21:03', 20, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '0713792727', NULL),
(27, 'EMC-20260226-746282', 'JANE', '', '2026-02-12', '', NULL, NULL, 5, NULL, '', '2026-02-26 17:37:42', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(28, 'EMC-20260227-185969', 'Elsie      Deborah', 'Female', '2024-02-05', '0793905535', NULL, NULL, 6, NULL, 'mlolongo', '2026-02-27 05:57:39', 2, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '0721912256', NULL),
(30, 'EMC-20260227-908468', 'lilian ndunge', 'Female', NULL, '0705383560', NULL, NULL, 6, NULL, '', '2026-02-27 19:04:44', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(31, 'EMC-20260228-428943', 'stacey nkatha', 'Female', NULL, '0769522481', NULL, NULL, 6, NULL, 'mlolongo', '2026-02-28 15:58:09', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '0740122481', NULL),
(32, 'WLK-20260228-528327', 'm', 'Female', NULL, '', NULL, NULL, 6, NULL, '', '2026-02-28 16:14:43', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(33, 'EMC-20260228-730483', 'Liz  Marita', 'Female', NULL, '0702375616', NULL, NULL, 0, NULL, '', '2026-02-28 16:48:24', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(34, 'EMC-20260228-886978', 'kyle muthomi', 'Male', NULL, '0110535349', NULL, NULL, 6, NULL, 'mlolongo', '2026-02-28 17:14:29', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '0796284499', NULL),
(35, 'WLK-20260228-932998', 'm', 'Female', NULL, '', NULL, NULL, 6, NULL, '', '2026-02-28 17:22:09', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(36, 'EMC-20260301-033037', 'Redempta  NDANU', 'Female', NULL, '0708448136', NULL, NULL, 6, NULL, 'MLOLONGO', '2026-03-01 15:52:10', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '0703940666', NULL),
(37, 'EMC-20260302-031073', 'Melody  Nkatha', 'Female', NULL, '0110535349', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-02 08:31:50', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '0796284499', NULL),
(38, 'WLK-20260302-961480', 'm', 'Female', NULL, '', NULL, NULL, 0, NULL, '', '2026-03-02 11:06:54', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(39, 'EMC-20260302-522962', 'mwanzia ndaguta', 'Male', '1995-11-23', '0723386925', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-02 18:13:49', 30, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '0723386925', NULL),
(40, 'WLK-20260303-970510', 'l', '', NULL, '', NULL, NULL, 0, NULL, '', '2026-03-03 06:35:05', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(41, 'EMC-20260303-973983', 'm', 'Female', NULL, '', NULL, NULL, 0, NULL, '', '2026-03-03 06:35:39', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(42, 'EMC-20260303-527365', 'peter muriuki', 'Male', '1998-07-14', '0719770963', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-03 10:54:33', 27, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '0719770963', NULL),
(43, 'EMC-20260303-527464', 'peter muriuki', 'Male', '1998-07-14', '0719770963', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-03 10:54:34', 27, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '0719770963', NULL),
(44, 'EMC-20260303-948825', 'david majengo', 'Male', '2001-11-20', '0758483016', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-03 17:38:08', 24, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '0758483016', NULL),
(45, 'EMC-20260304-942739', 'david ndungu', 'Male', '2000-10-27', '0704275856', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-04 10:17:07', 25, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(46, 'EMC-20260304-137420', 'savion wamburu', 'Male', '2026-02-16', '0719177618', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-04 13:36:14', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(47, 'EMC-20260305-881290', 'hinarry mutua', 'Female', '2008-04-11', '0797371109', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-05 05:33:32', 17, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '0724534298', NULL),
(48, 'EMC-20260305-963341', 'Ronald      Ngala', 'Male', NULL, '0724321460', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-05 11:20:33', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(49, 'EMC-20260305-120932', 'moses odhiambo', 'Male', NULL, '0714837561', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-05 11:46:49', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(50, 'EMC-20260305-536933', 'sylvia kathambi', 'Female', NULL, '0115500204', NULL, NULL, 6, NULL, '011550204', '2026-03-05 12:56:09', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(51, 'EMC-20260307-782992', 'Ronald      Ngala', '', NULL, '0724321460', NULL, NULL, 6, NULL, '', '2026-03-07 10:03:49', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(52, 'EMC-20260307-885285', 'winnie karuki', 'Female', NULL, '0723238869', NULL, NULL, 6, NULL, 'mllongo', '2026-03-07 15:54:12', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '0723', NULL),
(53, 'EMC-20260308-925065', 'marwa', 'Female', NULL, '0720728131', NULL, NULL, 6, NULL, 'eastleigh', '2026-03-08 05:54:10', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '0720728131', NULL),
(54, 'EMC-20260308-832299', 'ann muthoni  weru', 'Female', NULL, '0794470120', NULL, NULL, 6, NULL, 'mllongo', '2026-03-08 11:12:02', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(55, 'EMC-20260308-662023', 'ronald lukeman', 'Male', NULL, '0724321460', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-08 13:30:20', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(56, 'EMC-20260308-351445', 'Augustus  musembi', 'Female', NULL, '', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-08 18:11:54', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(57, 'EMC-20260308-039592', 'fellisters musyoka', 'Female', NULL, '', NULL, NULL, 6, NULL, '', '2026-03-08 20:06:35', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(58, 'EMC-20260309-881911', 'winnie ivayo', 'Female', '0199-09-04', '0794419401', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-09 01:13:39', 1826, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(59, 'EMC-20260309-334854', 'eunice sibiya', 'Female', NULL, '0714301076', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-09 08:02:28', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(60, 'EMC-20260310-658774', 'Daniel  juma', 'Male', NULL, '0723273998', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-10 18:16:27', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(61, 'EMC-20260311-474999', 'Delan mumo', 'Male', '2025-06-18', '0115752133', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-11 07:39:10', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(62, 'EMC-20260312-668554', 'melody nkatha', 'Female', NULL, '0110535349', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-12 06:24:45', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(63, 'EMC-20260312-510565', 'edna moraa', 'Male', NULL, '0794602927', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-12 08:45:05', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1', '', NULL),
(64, 'EMC-20260312-908442', 'urbanus kimeu', 'Male', NULL, '0794602927', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-12 12:38:04', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(65, 'EMC-20260312-018472', 'ronald lukeman', 'Female', NULL, '0724321460', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-12 12:56:24', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(66, 'EMC-20260312-947584', 'David  esther', '', NULL, '0713792727', NULL, NULL, 0, NULL, 'mlolongo', '2026-03-12 15:31:15', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(67, 'WLK-20260313-013231', 'm', 'Female', NULL, '', NULL, NULL, 6, NULL, '', '2026-03-13 08:22:12', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(68, 'WLK-20260313-999042', 'm', '', NULL, '', NULL, NULL, 0, NULL, '', '2026-03-13 13:53:10', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(69, 'WLK-20260313-220523', 'm', '', NULL, '', NULL, NULL, 0, NULL, '', '2026-03-13 14:30:05', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(70, 'EMC-20260313-636798', 'liam mwendwa', '', NULL, '0797712136', NULL, NULL, 0, NULL, 'mllongo', '2026-03-13 15:39:27', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(71, 'EMC-20260313-309275', 'esther moraa', '', '2023-01-11', '0716960154', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-13 17:31:32', 3, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(72, 'EMC-20260313-953057', 'drew mclntyre', 'Male', '2023-04-23', '0729398044', NULL, NULL, 6, NULL, 'katani', '2026-03-13 19:18:50', 2, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '0715657715', NULL),
(73, 'EMC-20260314-759046', 'Esther mbogo', 'Female', NULL, '0781700512', NULL, NULL, 0, NULL, 'mlolongo', '2026-03-14 11:26:30', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(74, 'EMC-20260318-847382', 'fellisters musyoka', 'Female', NULL, '072753734', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-18 10:07:53', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(75, 'EMC-20260318-217967', 'AISHA MUSTAPHA', 'Female', NULL, '075970709', NULL, NULL, 6, NULL, 'MLOLONGO', '2026-03-18 13:56:19', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(76, 'EMC-20260318-786069', 'Immaculate muthama', '', NULL, '0769471588', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-18 15:31:00', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(77, 'EMC-20260318-989662', 'Ethan musyoka', 'Male', '2026-02-08', '0794583563', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-18 16:04:56', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(78, 'EMC-20260318-617291', 'dominic            odhiambo', 'Male', NULL, '0701396776', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-18 17:49:32', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(79, 'EMC-20260319-201370', 'ILIKHAMU RAMADHAN', 'Female', '2023-11-15', '011578419', NULL, NULL, 6, NULL, 'MLOLONGO', '2026-03-19 01:00:13', 2, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(80, 'EMC-20260319-051969', 'tianah nyakio', '', NULL, '0768435083', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-19 06:08:39', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(81, 'WLK-20260322-909360', 'walk', '', NULL, '', NULL, NULL, 7, NULL, '', '2026-03-22 05:58:13', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(82, 'EMC-20260322-073341', 'lilian akinyi', 'Female', '1985-02-23', '0790503248', NULL, NULL, 7, NULL, 'mlolongo', '2026-03-22 17:32:13', 41, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '0000', NULL),
(83, 'EMC-20260323-940598', 'yvonne  kimatu', 'Female', '1998-09-22', '0745864341', NULL, NULL, 7, NULL, 'mlolongo', '2026-03-23 12:36:46', 27, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '0000', NULL),
(84, 'EMC-20260324-132564', 'stacy kelvin', 'Female', NULL, '0707903253', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-24 05:48:45', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(85, 'EMC-20260324-398082', 'naomi majuma', 'Female', NULL, '0757838049', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-24 06:33:00', 0, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '', NULL),
(86, 'EMC-20260324-4312', 'Benjamin Wambua', 'Male', '1993-10-13', '0705259931', NULL, NULL, 6, NULL, 'Mlolongo', '2026-03-24 08:24:54', 32, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Moses Wambua', '0792719718', NULL),
(87, 'EMC-20260324-2507', 'cliff  gashoki', 'Male', '2000-04-20', '0743151258', NULL, NULL, 7, NULL, 'mlolongo', '2026-03-24 16:17:33', 25, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '0743151258', NULL),
(88, 'EMC-20260325-1854', 'Immaculate Kawira', 'Female', NULL, '07107346400', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-25 17:18:43', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'immacuate', '', NULL),
(89, 'EMC-20260326-2397', 'Kelvin onyango', 'Male', NULL, '0758195448', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-26 10:37:46', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'kelvin', '', NULL),
(90, 'EMC-20260326-6847', 'kyle muthomi', 'Male', NULL, '079628449', NULL, NULL, 6, NULL, '', '2026-03-26 11:20:37', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'kyle melody', '', NULL),
(91, 'EMC-20260327-5603', 'felicia daniels', 'Female', '2025-05-23', '0700468717', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-27 01:56:39', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'janet akinyi', '', NULL),
(98, 'EMC-20260328-9786', 'tesla  ann', 'Female', '2022-01-01', '', NULL, NULL, 7, NULL, 'mlolongo', '2026-03-28 15:42:32', 4, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'yvone', '0745864341', NULL),
(99, 'EMC-20260329-4966', 'emmanuel         mumo', 'Male', NULL, '0723838753', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-28 23:34:24', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'emmanuel', '', NULL),
(100, 'EMC-20260329-8960', 'Gabriel muthoi kamau', 'Male', NULL, '0726050001', NULL, NULL, 6, NULL, 'MLOLONGO', '2026-03-29 15:40:22', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'LINET', '', NULL),
(101, 'EMC-20260331-9718', 'catherine  kimathi', 'Female', NULL, '', NULL, NULL, 6, NULL, 'mlolongo', '2026-03-31 21:44:03', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'kimathi', '', NULL),
(102, 'EMC-20260401-7487', 'kez odhiambo', 'Male', '2005-08-06', '0791603106', NULL, NULL, 6, NULL, 'mlolongo', '2026-04-01 04:50:14', 20, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'odhiambo', '', NULL),
(103, 'EMC-20260401-2069', 'raymond harman', 'Male', '2025-06-08', '0748138883', NULL, NULL, 6, NULL, 'mlolongo', '2026-04-01 05:01:45', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'sarah', '', NULL),
(104, 'WLK-20260401-7350', 'everlyne', 'Female', NULL, '', NULL, NULL, 6, NULL, '', '2026-04-01 16:10:19', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', NULL),
(105, 'WLK-20260401-8697', 'm', '', NULL, '', NULL, NULL, 6, NULL, '', '2026-04-01 18:05:05', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', NULL),
(106, 'EMC-20260403-6098', 'brenda kibet', 'Female', NULL, '0727540513', NULL, NULL, 6, NULL, 'mlolongo', '2026-04-03 17:43:30', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brenda', '', NULL),
(107, 'EMC-20260404-8405', 'melody nkatha', 'Female', NULL, '0110535349', NULL, NULL, 6, NULL, 'mlolongo', '2026-04-04 07:30:08', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'immacuate', '', NULL),
(108, 'EMC-20260404-2260', 'sebastian kimatu', 'Male', NULL, '0712367918', NULL, NULL, 6, NULL, 'mlolongo', '2026-04-04 17:38:18', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'sebastian', '', NULL),
(109, 'EMC-20260404-9016', 'titus mureithi wangari', '', NULL, '0704476694', NULL, NULL, 6, NULL, 'mlolongo', '2026-04-04 19:03:55', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'titus', '', NULL),
(110, 'EMC-20260404-9240', 'AISHA MUSTAPHA', '', NULL, '0711719117', NULL, NULL, 6, NULL, 'MLOLONGO', '2026-04-04 20:08:11', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(111, 'EMC-20260405-3802', 'melody nkatha', 'Female', NULL, '0110535349', NULL, NULL, 6, NULL, 'mlolongo', '2026-04-05 14:44:15', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'melody', '', NULL),
(112, 'EMC-20260406-5750', 'nancy meshack', 'Female', NULL, '0729573594', NULL, NULL, 6, NULL, 'mlolongo', '2026-04-06 09:14:21', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'mbuvi', '', NULL),
(113, 'EMC-20260406-6698', 'immaculate wambua', 'Female', NULL, '0745452763', NULL, NULL, 6, NULL, 'mlolongo', '2026-04-06 13:55:51', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'immaculate', '', NULL),
(114, 'EMC-20260406-3838', 'Everlyne   ndanu', 'Female', NULL, '0745840753', NULL, NULL, 6, NULL, 'mlolongo', '2026-04-06 15:12:59', 0, 'Maternity', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'everlyne', '', NULL),
(115, 'EMC-20260406-8526', 'Everlyne   ndanu', 'Female', NULL, '0745840753', NULL, NULL, 6, NULL, 'mlolongo', '2026-04-06 15:15:02', 0, 'Maternity', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'everlyne', '', NULL),
(116, 'WLK-20260407-8295', 'k', '', NULL, '', NULL, NULL, 0, NULL, '', '2026-04-07 11:11:53', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', NULL),
(117, 'WLK-20260407-9903', 'k', '', NULL, '', NULL, NULL, 0, NULL, '', '2026-04-07 17:59:04', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', NULL),
(124, 'WLK-20260408-4577', 'm', '', NULL, '', NULL, NULL, 6, NULL, '', '2026-04-08 16:20:46', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', NULL),
(125, 'EMC-20260409-2599', 'ABDI GODANA', 'Male', NULL, '0796605659', NULL, NULL, 7, NULL, 'MLOLONGO', '2026-04-09 09:52:12', 0, 'OPD', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ABDI', '', NULL),
(126, 'EMC-20260409-4051', 'CATHERINE MUOKI', 'Female', NULL, '0791832623', NULL, NULL, 6, NULL, 'MLOLONGO', '2026-04-09 11:09:50', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(127, 'EMC-20260409-9890', 'brighton mumo', 'Male', NULL, '0759556002', NULL, NULL, 6, NULL, 'mllongo', '2026-04-09 12:10:26', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(128, 'EMC-20260409-5436', 'naira muthoni', '', '2024-12-04', '0725365586', NULL, NULL, 6, NULL, 'mlolongo', '2026-04-09 12:22:41', 1, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'naira', '', NULL),
(129, 'WLK-20260410-8572', 'm', '', NULL, '', NULL, NULL, 6, NULL, '', '2026-04-10 13:45:03', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', NULL),
(137, 'EMC-20260412-2327', 'Dorcas Mwende', 'Female', '2004-07-12', '0705545433', NULL, NULL, 7, NULL, '', '2026-04-12 16:51:17', 21, 'ANC', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Geoffrey', '', NULL),
(138, 'EMC-20260413-2792', 'leonard ngata', 'Male', NULL, '0758160061', NULL, NULL, 6, NULL, 'mlolongo', '2026-04-13 07:41:46', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(139, 'EMC-20260413-6767', 'jacinta ndunge', 'Female', NULL, '0703168557', NULL, NULL, 6, NULL, 'mlolongo', '2026-04-13 14:11:56', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(140, 'EMC-20260416-1146', 'Eric', 'Male', NULL, '0711892123', NULL, NULL, 6, NULL, 'mlolongo', '2026-04-16 16:20:56', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '0799613100', NULL),
(141, 'EMC-20260417-9450', 'JASLYNE    NYAWIRA', '', NULL, '0710734640', NULL, NULL, 6, NULL, 'MLOONGO', '2026-04-17 13:23:41', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'kinya', '', NULL),
(142, 'EMC-20260417-7515', 'JASPHINE MUTHOKA', 'Female', NULL, '0791819481', NULL, NULL, 6, NULL, 'MLOLONGO', '2026-04-17 15:00:52', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(143, 'EMC-20260418-5459', 'nancy mbuvi', '', NULL, '', NULL, NULL, 0, NULL, 'mlolongo', '2026-04-18 12:52:22', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'mbuvi', '', NULL),
(144, 'EMC-20260420-7669', 'bree', 'Female', '1996-06-20', '0110535349', NULL, NULL, 7, NULL, '', '2026-04-20 12:55:12', 29, 'ANC', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '0796284499', NULL),
(145, 'EMC-20260423-3839', 'kelvin onyango', '', NULL, '', NULL, NULL, 6, NULL, 'mlolongo', '2026-04-23 10:20:10', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '0758195448', NULL),
(146, 'EMC-20260424-7292', 'joy karimi', 'Female', '2026-02-09', '0768203443', NULL, NULL, 6, NULL, 'mlolongo', '2026-04-24 08:58:05', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'caroline kanana', '', NULL),
(147, 'EMC-20260425-7368', 'rebecca nyabati', 'Female', '2013-12-07', '0716960154', NULL, NULL, 6, NULL, 'mlolongo', '2026-04-25 18:16:22', 12, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(148, 'EMC-20260426-6599', 'mirrian mwende', 'Female', NULL, '0705625659', NULL, NULL, 6, NULL, 'mlolongo', '2026-04-26 12:52:33', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(149, 'EMC-20260426-1661', 'mirrian mwende', 'Female', NULL, '0705625659', NULL, NULL, 6, NULL, 'mlolongo', '2026-04-26 12:52:33', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(150, 'EMC-20260428-3602', 'Robert wafula', 'Male', NULL, '0795758895', NULL, NULL, 6, NULL, 'mlolongo', '2026-04-28 10:26:09', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(151, 'EMC-20260428-8615', 'robert', '', NULL, '0795758895', NULL, NULL, 6, NULL, 'kayole', '2026-04-28 10:41:14', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(152, 'EMC-20260429-2029', 'mirrian mwikali', 'Female', NULL, '0704899393', NULL, NULL, 6, NULL, 'mlolongo', '2026-04-29 16:51:05', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(153, 'EMC-20260430-8764', 'jacob  mwendwa', 'Male', '2005-11-03', '0704655619', NULL, NULL, 7, NULL, 'mlolongo', '2026-04-30 07:10:38', 20, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '0704655619', NULL),
(154, 'EMC-20260502-9111', 'Leonard Aringo', 'Male', '1999-12-19', '0757588341', NULL, NULL, 6, NULL, 'Mlolongo', '2026-05-02 15:57:20', 26, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Brenda Aringo', '0119395967', NULL),
(155, 'EMC-20260503-7662', 'mercy silvester', '', NULL, '0705969627', NULL, NULL, 6, NULL, 'mlolongo', '2026-05-03 11:37:24', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(156, 'EMC-20260503-8957', 'nelson ka buru', '', NULL, '0788299153', NULL, NULL, 6, NULL, 'mlolongo', '2026-05-03 12:06:36', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(157, 'EMC-20260503-8985', 'prince', '', NULL, '', NULL, NULL, 6, NULL, 'mllongo', '2026-05-03 12:32:14', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(158, 'EMC-20260503-8247', 'mercy silvester', 'Female', NULL, '0745864341', NULL, NULL, 6, NULL, 'mlolongo', '2026-05-03 15:34:19', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(159, 'EMC-20260504-1177', 'Joshua mwanzia', 'Male', NULL, '0110444719', NULL, NULL, 6, NULL, 'mlolongo', '2026-05-04 14:25:28', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(160, 'EMC-20260504-8060', 'kelvin onyango', 'Male', NULL, '0758195448', NULL, NULL, 6, NULL, '', '2026-05-04 14:36:00', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(161, 'EMC-20260507-9166', 'kelvin  korir', 'Male', NULL, '', NULL, NULL, 6, NULL, 'mlolongo', '2026-05-07 17:52:51', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(162, 'EMC-20260508-7255', 'davinna angel', 'Female', '2025-11-25', '0703227360', NULL, NULL, 7, NULL, 'mlolongo', '2026-05-08 07:19:10', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'mother', '0703227360', NULL),
(163, 'EMC-20260508-4341', 'audrey mukanda', 'Female', NULL, '0757483739', NULL, NULL, 6, NULL, 'mlolongo', '2026-05-08 16:12:17', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(164, 'EMC-20260512-8496', 'reagan', '', NULL, '', NULL, NULL, 6, NULL, 'mllng', '2026-05-12 14:41:07', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(165, 'EMC-20260512-5954', 'reagan', '', NULL, '', NULL, NULL, 6, NULL, 'mllng', '2026-05-12 14:41:08', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(166, 'EMC-20260512-6183', 'reagan', '', NULL, '', NULL, NULL, 6, NULL, 'mllng', '2026-05-12 14:41:09', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(167, 'EMC-20260513-6408', 'decline muuo', 'Male', '2022-03-13', '0706238991', NULL, NULL, 6, NULL, 'mlolongo', '2026-05-13 06:30:46', 4, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(168, 'EMC-20260516-9549', 'lilian ombago', 'Female', NULL, '0768268902', NULL, NULL, 6, NULL, 'mlolongo', '2026-05-16 08:23:23', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(169, 'EMC-20260516-5422', 'lilian ombogo', 'Female', NULL, '0768268902', NULL, NULL, 6, NULL, 'mlolongo', '2026-05-16 08:25:41', 0, 'Maternity', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(170, 'EMC-20260521-1450', 'valentine Wanyonyi', 'Female', NULL, '0790684174', NULL, NULL, 0, NULL, '', '2026-05-21 19:44:39', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Timothy Onyonyi', '', NULL),
(171, 'EMC-20260524-2326', 'olivia njeri', '', '2023-05-12', '0728767870', NULL, NULL, 7, NULL, 'mlolongo', '2026-05-24 18:06:02', 3, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'mother', '0728769570', NULL),
(172, 'EMC-20260529-9934', 'destiny syombua', 'Female', '2023-03-16', '0115752133', NULL, NULL, 6, NULL, 'mlolongo', '2026-05-29 13:03:05', 3, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(173, 'EMC-20260530-7872', 'john mulei', 'Male', NULL, '0720621239', NULL, NULL, 6, NULL, 'mlolongo', '2026-05-30 15:12:45', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(174, 'EMC-20260530-9405', 'john mulei', 'Male', NULL, '0720621239', NULL, NULL, 6, NULL, 'mlolongo', '2026-05-30 15:12:45', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(175, 'EMC-20260530-2739', 'john mulei', 'Male', NULL, '0720621239', NULL, NULL, 6, NULL, 'mlolongo', '2026-05-30 15:12:45', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(176, 'EMC-20260530-4334', 'john mulei', 'Male', NULL, '0720621239', NULL, NULL, 6, NULL, 'mlolongo', '2026-05-30 15:12:45', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(177, 'EMC-20260530-4450', 'john mulei', 'Male', NULL, '0720621239', NULL, NULL, 6, NULL, 'mlolongo', '2026-05-30 15:12:45', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(178, 'EMC-20260530-2112', 'john mulei', 'Male', NULL, '0720621239', NULL, NULL, 6, NULL, 'mlolongo', '2026-05-30 15:12:45', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(179, 'EMC-20260531-6900', 'melody wambui', 'Male', '2021-10-20', '072546537', NULL, NULL, 5, NULL, 'mlolongo', '2026-05-31 09:08:35', 4, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(180, 'EMC-20260531-3240', 'GIDYS NGUGI', 'Female', '2004-05-06', '0769759861', NULL, NULL, 7, NULL, 'MLOLONGO', '2026-05-31 18:34:49', 22, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '0769759861', NULL),
(181, 'EMC-20260601-5765', 'Immaculate Kawira', 'Male', NULL, '', NULL, NULL, 6, NULL, 'mlolongo', '2026-06-01 09:14:10', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(182, 'EMC-20260605-8503', 'lucky  wambua nicholas', 'Male', NULL, '0711719117', NULL, NULL, 6, NULL, 'mlolongo', '2026-06-05 10:09:16', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(183, 'EMC-20260607-9539', 'faith', 'Female', NULL, '', NULL, NULL, 6, NULL, 'mlolongo', '2026-06-07 09:59:18', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(184, 'EMC-20260607-7580', 'emma mwangi', 'Female', '2007-02-07', '0799900124', NULL, NULL, 6, NULL, 'self', '2026-06-07 12:02:17', 19, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(185, 'EMC-20260608-3109', 'mirriam mbihe', 'Female', NULL, '0797576025', NULL, NULL, 6, NULL, 'mlolongo', '2026-06-08 14:23:48', 0, 'Maternity', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(186, 'EMC-20260608-2698', 'amos  kimanzi', '', NULL, '0757177724', NULL, NULL, 7, NULL, 'mlolongo', '2026-06-08 14:50:46', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(187, 'EMC-20260609-7309', 'elian gisohi', 'Male', NULL, '0790984141', NULL, NULL, 0, NULL, 'mlolongo', '2026-06-09 06:20:49', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(188, 'EMC-20260609-3249', 'elian  gisohi', 'Male', NULL, '0790984141', NULL, NULL, 6, NULL, 'mlolongo', '2026-06-09 06:22:00', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(189, 'EMC-20260609-4689', 'justus okana', 'Male', NULL, '0710903215', NULL, NULL, 6, NULL, 'mlolongo', '2026-06-09 17:14:35', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(190, 'EMC-20260610-4741', 'elphas odero', 'Male', NULL, '0706629877', NULL, NULL, 6, NULL, 'mlolongo', '2026-06-10 07:34:18', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(191, 'EMC-20260612-7801', 'emmanuel muleli', 'Male', NULL, '0714626263', NULL, NULL, 6, NULL, 'mlolongo', '2026-06-12 15:07:06', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(192, 'EMC-20260612-9900', 'christine magangi', 'Female', NULL, '0717789455', NULL, NULL, 6, NULL, 'mlolongo', '2026-06-12 17:23:51', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(193, 'EMC-20260614-4608', 'Immaculate Kawira', 'Male', NULL, '07107346400', NULL, NULL, 7, NULL, 'mlolongo', '2026-06-14 05:29:10', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(194, 'EMC-20260614-8775', 'mercy stephen', 'Female', NULL, '0710734640', NULL, NULL, 7, NULL, 'mlolongo', '2026-06-14 06:13:24', 0, 'Maternity', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(195, 'EMC-20260616-2044', 'vincent  onyango', 'Male', '2017-11-25', '0115784119', NULL, NULL, 7, NULL, 'mlolongo', '2026-06-16 20:14:41', 8, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '011578119', NULL),
(196, 'EMC-20260618-6527', 'Immaculate Kawira', 'Male', NULL, '07107346400', NULL, NULL, 7, NULL, 'mllngo', '2026-06-18 09:34:13', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '0703940666', NULL),
(197, 'EMC-20260620-7183', 'patience     ken', 'Female', '2002-01-14', '0794725210', NULL, NULL, 6, NULL, 'mlolongo', '2026-06-20 05:13:27', 24, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(198, 'EMC-20260620-3986', 'alvarez patrick', 'Male', '2024-03-01', '0707424073', NULL, NULL, 6, NULL, 'mlolongo', '2026-06-20 20:29:35', 2, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(199, 'EMC-20260622-5295', 'brenda  Njiru', 'Female', NULL, '0705564785', NULL, NULL, 6, NULL, 'mlolongo', '2026-06-22 15:25:22', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(200, 'EMC-20260624-3426', 'daniel kivuya', 'Male', NULL, '0758652139', NULL, NULL, 6, NULL, 'mlolngo', '2026-06-24 18:15:26', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(201, 'EMC-20260626-6668', 'ashlyn talia', 'Female', NULL, '079947131', NULL, NULL, 6, NULL, 'mllong', '2026-06-26 17:04:55', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(202, 'EMC-20260626-5136', 'ashlyn talia', 'Female', NULL, '079947131', NULL, NULL, 6, NULL, 'mllong', '2026-06-26 17:04:56', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(203, 'EMC-20260627-3717', 'Benjamin Wambua', 'Male', NULL, '0705259931', NULL, NULL, 6, NULL, 'mlolongo', '2026-06-27 05:43:44', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(204, 'EMC-20260627-7336', 'charity wandera', 'Female', '2026-06-27', '0714301076', NULL, NULL, 0, NULL, '', '2026-06-27 12:04:03', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'eunice', '0714301076', NULL),
(205, 'EMC-20260701-7899', 'mercy kanja', 'Female', NULL, '0743845532', NULL, NULL, 6, NULL, 'mlolongo', '2026-07-01 06:22:32', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(206, 'EMC-20260703-6881', 'roney ochieng', 'Male', NULL, '0743850972', NULL, NULL, 6, NULL, 'katani', '2026-07-03 06:20:02', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(207, 'EMC-20260704-4736', 'blessing mwende', 'Female', NULL, '0769326495', NULL, NULL, 6, NULL, 'mlolongo', '2026-07-04 13:07:07', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(208, 'EMC-20260705-3470', 'shon   nzau', 'Female', '2007-08-06', '0720621239', NULL, NULL, 6, NULL, 'syokimau', '2026-07-05 09:20:44', 18, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(209, 'EMC-20260710-3580', 'gibson ali', 'Male', NULL, '0758036298', NULL, NULL, 6, NULL, 'mlolongo', '2026-07-10 07:11:58', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(210, 'EMC-20260710-9009', 'gibson ali', 'Male', NULL, '0758036298', NULL, NULL, 6, NULL, 'mlolongo', '2026-07-10 07:12:02', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(211, 'EMC-20260710-5797', 'gibson ali', 'Male', NULL, '0758036298', NULL, NULL, 6, NULL, 'mlolongo', '2026-07-10 07:12:02', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(212, 'EMC-20260710-2508', 'gibson ali', 'Male', NULL, '0758036298', NULL, NULL, 6, NULL, 'mlolongo', '2026-07-10 07:12:03', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(213, 'EMC-20260710-6174', 'gibson ali', 'Male', NULL, '0758036298', NULL, NULL, 6, NULL, 'mlolongo', '2026-07-10 07:12:03', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(214, 'EMC-20260710-4840', 'gibson ali', 'Male', NULL, '0758036298', NULL, NULL, 6, NULL, 'mlolongo', '2026-07-10 07:12:03', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(215, 'EMC-20260712-7070', 'Diana Arama', 'Female', NULL, '', NULL, NULL, 6, NULL, 'mlolongo', '2026-07-12 10:12:05', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(216, 'EMC-20260712-3403', 'eunice mwamumba', 'Female', NULL, '0759497764', NULL, NULL, 6, NULL, 'mllongo', '2026-07-12 10:45:10', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(217, 'EMC-20260712-8369', 'immaculate  syombua', 'Female', NULL, '0769471588', NULL, NULL, 6, NULL, 'mlolongo', '2026-07-12 12:04:39', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(218, 'EMC-20260718-4650', 'sheila mutai', 'Female', NULL, '0714459277', NULL, NULL, 6, NULL, 'mlolongo', '2026-07-18 08:05:41', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(219, 'EMC-20260718-2471', 'stephen wonder', 'Male', NULL, '702754780', NULL, NULL, 6, NULL, 'mlolongo', '2026-07-18 15:44:03', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(220, 'EMC-20260725-3474', 'ashlyn talia', 'Female', NULL, '0799471313', NULL, NULL, 6, NULL, 'mlolongo', '2026-07-25 18:18:22', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(221, 'EMC-20260727-8042', 'm', '', NULL, '', NULL, NULL, 0, NULL, '', '2026-07-27 16:05:16', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(222, 'EMC-20260728-3713', 'abigael wambua', 'Female', NULL, '07107346400', NULL, NULL, 6, NULL, 'mllongo', '2026-07-28 17:34:13', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(223, 'EMC-20260730-4726', 'ilikhamu ramadhan', 'Female', NULL, '', NULL, NULL, 6, NULL, 'mlolongo', '2026-07-30 15:14:26', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(224, 'EMC-20260801-9530', 'blessing mutheu', 'Female', NULL, '0794755289', NULL, NULL, 6, NULL, 'mlolongo', '2026-08-01 12:16:18', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(225, 'EMC-20260804-2789', 'gennifer makala', 'Female', NULL, '0713233761', NULL, NULL, 6, NULL, 'mlolongo', '2026-08-04 04:55:10', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(226, 'EMC-20260805-5070', 'ruth nkirote', 'Female', NULL, '0718977350', NULL, NULL, 6, NULL, 'mlolongo', '2026-08-05 18:24:55', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(227, 'EMC-20260805-7239', 'ashley', '', NULL, '', NULL, NULL, 6, NULL, 'mlolongo', '2026-08-05 19:22:09', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(228, 'EMC-20260806-9492', 'judith mumo', 'Female', NULL, '0787131701', NULL, NULL, 6, NULL, 'mlolongo', '2026-08-06 08:55:14', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(229, 'EMC-20260806-1330', 'MILLAN NDAMBUKI', 'Female', '2025-12-01', '0115818049', NULL, NULL, 7, NULL, '', '2026-08-06 16:35:33', 0, 'Emergency', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'CHRISTINE NZENGWELA', '0758068621', NULL),
(230, 'EMC-20260807-6473', 'AMELIA KANINI', 'Female', '2024-10-11', '0748016858', NULL, NULL, 0, NULL, '', '2026-08-07 03:23:53', 1, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'OLIVE MTINDI', '0748016858', NULL),
(231, 'EMC-20260808-4081', 'brendah kawira', 'Female', NULL, '0742977055', NULL, NULL, 6, NULL, 'mlolongo', '2026-08-08 19:24:45', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(232, 'EMC-20260811-6949', 'patric mutua', 'Male', NULL, '0707424073', NULL, NULL, 6, NULL, 'mlolongo', '2026-08-11 17:33:53', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(233, 'EMC-20260811-1012', 'felistwers patricah', 'Female', NULL, '0757147486', NULL, NULL, 6, NULL, 'mlolongo', '2026-08-11 17:36:25', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(234, 'EMC-20260812-7919', 'gibson ali', 'Male', NULL, '0758036298', NULL, NULL, 6, NULL, 'mlolongo', '2026-08-12 13:12:01', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(235, 'EMC-20260813-9700', 'sarah james', '', NULL, '07107346400', NULL, NULL, 6, NULL, 'mlolongo', '2026-08-13 13:32:41', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(236, 'EMC-20260814-6030', 'jane', 'Female', NULL, '0710734640', NULL, NULL, 6, NULL, 'mlolongo', '2026-08-14 05:26:22', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(237, 'EMC-20260814-3032', 'esther benard   ', 'Female', NULL, '0742891432', NULL, NULL, 6, NULL, 'mlolongo', '2026-08-14 09:47:58', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(238, 'EMC-20260814-5103', 'iriene mwende', '', NULL, '0743967969', NULL, NULL, 6, NULL, 'mlolongo', '2026-08-14 12:44:11', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(239, 'EMC-20260815-9361', 'felinard muthui', '', NULL, '0712113071', NULL, NULL, 6, NULL, 'mlolongo', '2026-08-15 09:58:21', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'felinard muthui', '', NULL),
(240, 'WLK-20260816-3888', 'ESTHER KARIUKI', 'Female', '2005-02-10', '0798223574', NULL, NULL, 0, NULL, '', '2026-08-16 17:03:28', 21, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', NULL),
(241, 'EMC-20260819-1555', 'peter kyule', 'Male', NULL, '0721905307', NULL, NULL, 6, NULL, 'mlolongo', '2026-08-19 06:45:23', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(242, 'EMC-20260819-2030', 'harald  car mutinda', 'Male', NULL, '0768906745', NULL, NULL, 6, NULL, 'mllongo', '2026-08-19 14:20:33', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(243, 'EMC-20260821-7055', 'nehema precious', 'Female', NULL, '0715296773', NULL, NULL, 6, NULL, 'mlolongo', '2026-08-21 10:35:34', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(244, 'EMC-20260821-4896', 'samuel  wambua', '', NULL, '0716592105', NULL, NULL, 6, NULL, 'mlolongo', '2026-08-21 18:13:06', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(245, 'EMC-20260822-2061', 'emmanuel niklan', 'Male', NULL, '0794111599', NULL, NULL, 6, NULL, 'mlolongo', '2026-08-22 09:59:54', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(246, 'EMC-20260823-7328', 'chinedu obina', 'Male', NULL, '011377281', NULL, NULL, 6, NULL, 'mlolongo', '2026-08-23 07:23:21', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(247, 'EMC-20260825-5104', 'faith mbatha', 'Female', NULL, '0794282240', NULL, NULL, 6, NULL, 'mlolongo', '2026-08-25 04:47:43', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(248, 'EMC-20260825-9659', 'faith mbatha', 'Female', NULL, '0794282240', NULL, NULL, 6, NULL, 'mlolongo', '2026-08-25 04:49:38', 0, 'Maternity', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(249, 'EMC-20260825-6589', 'philemon ogot', 'Male', NULL, '', NULL, NULL, 6, NULL, 'mlolongo', '2026-08-25 05:19:36', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(250, 'EMC-20260826-6011', 'evans mwenda', 'Male', NULL, '0791204726', NULL, NULL, 6, NULL, 'mlolongo', '2026-08-26 11:05:34', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(251, 'EMC-20260826-4568', 'beatrice josiah', 'Female', NULL, '0790625992', NULL, NULL, 6, NULL, 'mlolongo', '2026-08-26 11:32:12', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(252, 'EMC-20260826-2533', 'obinna junior', '', NULL, '07107346400', NULL, NULL, 6, NULL, 'mlolongo', '2026-08-26 14:19:31', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'mother', '', NULL),
(253, 'EMC-20260826-4131', 'audrey mukanda', 'Female', NULL, '0794208344', NULL, NULL, 6, NULL, 'mlolongo', '2026-08-26 16:43:14', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL),
(254, 'WLK-20260827-1737', 'm', '', NULL, '', NULL, NULL, 6, NULL, '', '2026-08-27 07:32:37', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', NULL),
(255, 'EMC-20260827-5208', 'Deborah   netpat', 'Female', NULL, '0702754780', NULL, NULL, 6, NULL, 'mlolongo', '2026-08-27 08:39:57', 0, 'General', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'self', '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `patient_allergies`
--

CREATE TABLE `patient_allergies` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `allergy` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_bills`
--

CREATE TABLE `patient_bills` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('pending','paid') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_clinical_notes`
--

CREATE TABLE `patient_clinical_notes` (
  `id` int(11) NOT NULL,
  `patient_number` varchar(20) NOT NULL,
  `presenting_complaint` text DEFAULT NULL,
  `history_presenting_complaint` text DEFAULT NULL,
  `past_medical_history` text DEFAULT NULL,
  `drug_history` text DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `family_history` text DEFAULT NULL,
  `social_history` text DEFAULT NULL,
  `review_of_systems` text DEFAULT NULL,
  `physical_exam` text DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `investigation` text DEFAULT NULL,
  `treatment_plan` text DEFAULT NULL,
  `clinic_category` varchar(100) DEFAULT NULL,
  `doctor_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_coverages`
--

CREATE TABLE `patient_coverages` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `payer_id` int(11) NOT NULL,
  `plan_id` int(11) DEFAULT NULL,
  `member_number` varchar(100) NOT NULL,
  `card_number` varchar(100) DEFAULT NULL,
  `principal_member_name` varchar(150) DEFAULT NULL,
  `employer_name` varchar(150) DEFAULT NULL,
  `relationship_to_principal` varchar(50) DEFAULT NULL,
  `eligibility_status` enum('Pending','Verified','Inactive','Expired','Rejected') NOT NULL DEFAULT 'Pending',
  `verification_reference` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_diagnosis`
--

CREATE TABLE `patient_diagnosis` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `diagnosis` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_financial_accounts`
--

CREATE TABLE `patient_financial_accounts` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `account_class` enum('Cash','SHA','Insurance','Corporate','Mixed') NOT NULL DEFAULT 'Cash',
  `current_coverage_id` int(11) DEFAULT NULL,
  `current_payer_id` int(11) DEFAULT NULL,
  `current_plan_id` int(11) DEFAULT NULL,
  `running_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_copay_due` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_claims_outstanding` decimal(12,2) NOT NULL DEFAULT 0.00,
  `last_verified_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_history`
--

CREATE TABLE `patient_history` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `id_number` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_notes`
--

CREATE TABLE `patient_notes` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_orders`
--

CREATE TABLE `patient_orders` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `order_type` enum('procedure','drug','lab') NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `unit_price` decimal(10,2) DEFAULT 0.00,
  `status` enum('pending','billed','dispensed','completed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_procedures`
--

CREATE TABLE `patient_procedures` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `procedure_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_queue`
--

CREATE TABLE `patient_queue` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `status` enum('waiting','called','done','skipped') DEFAULT 'waiting',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_services`
--

CREATE TABLE `patient_services` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `category` enum('procedure','lab','radiology','treatment') NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `doctor_notes` text DEFAULT NULL,
  `results` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `lab_technician_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_services`
--

INSERT INTO `patient_services` (`id`, `patient_id`, `service_id`, `category`, `price`, `doctor_notes`, `results`, `status`, `lab_technician_id`, `created_at`) VALUES
(27, 20, 24, '', 500.00, NULL, NULL, 'Pending', NULL, '2026-02-26 10:59:23'),
(28, 20, 25, '', 300.00, NULL, NULL, 'Pending', NULL, '2026-02-26 10:59:47'),
(29, 21, 24, '', 500.00, NULL, NULL, 'Pending', NULL, '2026-02-26 11:33:32'),
(30, 21, 25, '', 300.00, NULL, NULL, 'Pending', NULL, '2026-02-26 11:33:41'),
(32, 24, 19, '', 1000.00, NULL, NULL, 'Pending', NULL, '2026-02-26 20:22:19'),
(34, 28, 24, 'lab', 500.00, '', 'leukocytosis with marked neutropenia and thrombocytopenia', 'Completed', NULL, '2026-02-27 08:59:24'),
(37, 31, 50, '', 8000.00, NULL, NULL, 'Pending', NULL, '2026-02-28 19:12:38'),
(38, 33, 51, '', 7000.00, NULL, NULL, 'Pending', NULL, '2026-02-28 19:50:26'),
(40, 35, 52, '', 5000.00, NULL, NULL, 'Pending', NULL, '2026-02-28 20:23:56'),
(41, 40, 32, '', 300.00, NULL, NULL, 'Pending', NULL, '2026-03-03 09:36:16'),
(42, 40, 32, 'lab', 300.00, '', 'leu==', 'Completed', NULL, '2026-03-03 09:55:15'),
(43, 42, 58, '', 300.00, NULL, NULL, 'Pending', NULL, '2026-03-03 13:57:54'),
(44, 42, 59, '', 800.00, NULL, NULL, 'Pending', NULL, '2026-03-03 13:58:16'),
(47, 44, 32, 'lab', 300.00, '', 'leu', 'Completed', NULL, '2026-03-03 20:38:58'),
(48, 47, 24, 'lab', 500.00, '', '', 'Completed', NULL, '2026-03-05 08:35:09'),
(49, 48, 25, 'lab', 300.00, '', '5.6mm/l', 'Completed', NULL, '2026-03-05 14:43:22'),
(50, 49, 60, 'lab', 200.00, '', 'n', 'Completed', NULL, '2026-03-05 14:52:38'),
(51, 50, 21, '', 2500.00, NULL, NULL, 'Pending', NULL, '2026-03-05 16:06:19'),
(53, 58, 32, 'lab', 300.00, '', 'leu++', 'Completed', NULL, '2026-03-09 04:14:16'),
(54, 63, 40, 'procedure', 1500.00, NULL, NULL, 'Completed', NULL, '2026-03-12 13:26:30'),
(55, 66, 24, 'lab', 500.00, '', 'elevated  wbc ', 'Completed', NULL, '2026-03-12 18:32:22'),
(56, 67, 61, 'procedure', 500.00, NULL, NULL, 'Completed', NULL, '2026-03-13 11:22:27'),
(57, 67, 62, 'procedure', 200.00, NULL, NULL, 'Completed', NULL, '2026-03-13 17:30:19'),
(58, 70, 24, 'procedure', 500.00, NULL, NULL, 'Completed', NULL, '2026-03-13 18:53:43'),
(59, 72, 24, 'procedure', 500.00, NULL, NULL, 'Completed', NULL, '2026-03-13 22:22:40'),
(60, 72, 58, 'procedure', 300.00, NULL, NULL, 'Completed', NULL, '2026-03-13 22:22:51'),
(61, 74, 59, 'lab', 500.00, '', 'negative', 'Completed', NULL, '2026-03-18 13:11:44'),
(62, 74, 49, 'lab', 100.00, '', 'positive', 'Completed', NULL, '2026-03-18 13:12:43'),
(63, 75, 32, 'lab', 300.00, '', 'leu elevated', 'Completed', NULL, '2026-03-18 16:57:53'),
(65, 77, 24, 'lab', 500.00, '', 'elevated wbcs', 'Completed', NULL, '2026-03-18 19:06:01'),
(66, 77, 61, 'procedure', 500.00, NULL, NULL, 'Completed', NULL, '2026-03-18 19:42:16'),
(67, 76, 25, 'lab', 100.00, '', '5.4mmol', 'Completed', NULL, '2026-03-18 21:10:29'),
(68, 60, 25, 'lab', 100.00, '', '4.4mmol', 'Completed', NULL, '2026-03-18 22:48:25'),
(69, 60, 63, 'lab', 300.00, '', '14.4', 'Completed', NULL, '2026-03-18 22:50:55'),
(70, 79, 24, 'lab', 500.00, '', 'elevated wbc', 'Completed', NULL, '2026-03-19 05:28:36'),
(71, 80, 24, 'lab', 0.00, '', 'elevated wbc', 'Completed', NULL, '2026-03-19 09:10:02'),
(72, 82, 24, 'lab', 500.00, '', 'elevated wbc', 'Completed', NULL, '2026-03-22 20:48:00'),
(73, 82, 30, 'lab', 200.00, '', 'negative', 'Completed', NULL, '2026-03-22 20:48:13'),
(74, 82, 59, 'lab', 500.00, '', 'negative', 'Completed', NULL, '2026-03-22 20:48:30'),
(75, 83, 24, 'lab', 500.00, '', 'elwevated wbc', 'Completed', NULL, '2026-03-23 15:37:15'),
(76, 83, 30, 'lab', 200.00, '', 'negative', 'Completed', NULL, '2026-03-23 15:37:28'),
(77, 84, 58, 'lab', 300.00, '', 'E.histolytica seen\r\nAscaris Lubricoides', 'Completed', NULL, '2026-03-24 08:53:08'),
(78, 85, 64, 'procedure', 500.00, NULL, NULL, 'Completed', NULL, '2026-03-24 10:32:53'),
(82, 87, 30, 'lab', 200.00, '', 'negative', 'Completed', NULL, '2026-03-24 19:26:41'),
(83, 87, 24, 'lab', 400.00, '', 'neutrophils    Elevated [HIGH]\r\nlymhocytes      Normal\r\nEosinophils      Normal\r\nMonocytes      Normal\r\nBasophils        Normal\r\n    HB  14.5 g/dl', 'Completed', NULL, '2026-03-24 19:44:19'),
(84, 60, 25, 'lab', 600.00, '', '4.2mm/l', 'Completed', NULL, '2026-03-25 07:04:38'),
(85, 60, 25, 'lab', 100.00, '', '5.7mm/l', 'Completed', NULL, '2026-03-25 07:13:50'),
(86, 90, 24, 'lab', 500.00, '', 'elwevated wbc', 'Completed', NULL, '2026-03-26 15:01:34'),
(87, 98, 24, 'lab', 500.00, '', 'elevatedwbc', 'Completed', NULL, '2026-03-28 18:43:28'),
(88, 100, 24, 'lab', 500.00, '', 'high wbc', 'Completed', NULL, '2026-03-29 18:42:03'),
(89, 103, 24, 'lab', 500.00, '', 'high wbc', 'Completed', NULL, '2026-04-01 08:05:04'),
(90, 103, 58, 'lab', 300.00, '', 'e.h seen', 'Completed', NULL, '2026-04-01 08:05:24'),
(91, 102, 24, 'lab', 500.00, '', 'elev wbc', 'Completed', NULL, '2026-04-01 08:21:28'),
(92, 104, 62, 'procedure', 200.00, NULL, NULL, 'Completed', NULL, '2026-04-01 19:10:39'),
(93, 105, 34, 'lab', 200.00, '', 'neg', 'Completed', NULL, '2026-04-01 21:05:36'),
(94, 107, 1, 'procedure', 300.00, NULL, NULL, 'Completed', NULL, '2026-04-04 10:35:51'),
(95, 107, 1, 'procedure', 300.00, NULL, NULL, 'Completed', NULL, '2026-04-04 10:36:06'),
(96, 108, 24, 'lab', 500.00, '', 'wbc high', 'Completed', NULL, '2026-04-04 20:38:44'),
(97, 109, 65, 'procedure', 400.00, NULL, NULL, 'Completed', NULL, '2026-04-04 22:06:26'),
(98, 110, 50, 'procedure', 8500.00, NULL, NULL, 'Completed', NULL, '2026-04-04 23:09:09'),
(99, 113, 24, 'procedure', 500.00, NULL, NULL, 'Completed', NULL, '2026-04-06 17:00:33'),
(101, 114, 50, 'procedure', 8500.00, NULL, NULL, 'Completed', NULL, '2026-04-06 18:41:48'),
(102, 115, 62, 'procedure', 200.00, NULL, NULL, 'Completed', NULL, '2026-04-06 19:08:23'),
(103, 115, 50, 'procedure', 8500.00, NULL, NULL, 'Completed', NULL, '2026-04-06 19:09:02'),
(104, 116, 23, 'procedure', 1000.00, NULL, NULL, 'Completed', NULL, '2026-04-07 14:12:33'),
(105, 116, 32, 'lab', 300.00, '', 'nad', 'Completed', NULL, '2026-04-07 14:17:30'),
(106, 116, 40, 'procedure', 500.00, NULL, NULL, 'Completed', NULL, '2026-04-07 14:17:59'),
(107, 126, 32, 'lab', 300.00, '', 'leu high', 'Completed', NULL, '2026-04-09 14:10:03'),
(108, 126, 49, 'procedure', 100.00, NULL, NULL, 'Completed', NULL, '2026-04-09 14:10:10'),
(109, 127, 24, 'lab', 500.00, '', 'high wbc', 'Completed', NULL, '2026-04-09 15:10:44'),
(110, 128, 24, 'procedure', 500.00, NULL, NULL, 'Completed', NULL, '2026-04-09 15:26:41'),
(111, 128, 25, 'lab', 100.00, '', '4.5mm/l', 'Completed', NULL, '2026-04-09 15:50:37'),
(112, 113, 32, 'lab', 300.00, '', 'nad', 'Completed', NULL, '2026-04-09 19:05:21'),
(113, 129, 32, 'lab', 300.00, '', 'leu elevated', 'Completed', NULL, '2026-04-12 07:54:54'),
(114, 138, 32, 'lab', 300.00, '', 'nad', 'Completed', NULL, '2026-04-13 10:56:51'),
(115, 139, 49, 'lab', 100.00, '', 'neg', 'Completed', NULL, '2026-04-13 17:13:18'),
(117, 140, 24, 'procedure', 500.00, NULL, NULL, 'Completed', NULL, '2026-04-16 19:21:14'),
(118, 140, 30, 'lab', 300.00, '', 'neg', 'Completed', NULL, '2026-04-16 19:21:43'),
(119, 140, 63, 'lab', 300.00, '', '11,8', 'Completed', NULL, '2026-04-16 19:21:56'),
(120, 141, 59, 'lab', 800.00, '', 'neg', 'Completed', NULL, '2026-04-17 16:24:41'),
(121, 141, 58, 'lab', 300.00, '', 'eh seen ', 'Completed', NULL, '2026-04-17 16:25:01'),
(122, 142, 32, 'lab', 300.00, '', 'elev wbc', 'Completed', NULL, '2026-04-17 18:01:15'),
(123, 111, 63, 'lab', 300.00, '', '7.8g/dl', 'Completed', NULL, '2026-04-20 22:46:40'),
(124, 111, 25, 'lab', 100.00, '', '4.2mm/l', 'Completed', NULL, '2026-04-20 22:47:01'),
(125, 145, 53, 'procedure', 300.00, NULL, NULL, 'Completed', NULL, '2026-04-23 13:21:23'),
(126, 145, 53, 'procedure', 300.00, NULL, NULL, 'Completed', NULL, '2026-04-23 13:24:04'),
(127, 146, 58, 'lab', 300.00, '', 'eh seen\r\nascaris seen', 'Completed', NULL, '2026-04-24 11:58:59'),
(128, 111, 54, 'procedure', 500.00, NULL, NULL, 'Completed', NULL, '2026-04-25 12:05:34'),
(129, 111, 54, 'procedure', 500.00, NULL, NULL, 'Completed', NULL, '2026-04-25 12:05:45'),
(130, 153, 24, 'lab', 500.00, '', 'high wbc', 'Completed', NULL, '2026-04-30 10:12:11'),
(131, 154, 24, 'lab', 300.00, '', 'wbc high', 'Completed', NULL, '2026-05-02 19:00:22'),
(132, 154, 25, 'lab', 100.00, '', '4.5', 'Completed', NULL, '2026-05-02 19:00:51'),
(133, 154, 66, 'procedure', 500.00, NULL, NULL, 'Completed', NULL, '2026-05-02 19:13:37'),
(134, 155, 58, 'lab', 300.00, '', 'nad', 'Completed', NULL, '2026-05-03 14:37:52'),
(135, 155, 59, 'lab', 500.00, '', 'pos', 'Completed', NULL, '2026-05-03 14:38:09'),
(137, 155, 58, 'lab', 300.00, '', 'nad', 'Completed', NULL, '2026-05-03 18:34:38'),
(139, 158, 58, 'lab', 300.00, '', 'e.hv\r\n seen', 'Completed', NULL, '2026-05-03 18:35:43'),
(140, 158, 59, 'lab', 500.00, '', 'positivwe', 'Completed', NULL, '2026-05-03 18:35:55'),
(141, 159, 53, 'procedure', 300.00, NULL, NULL, 'Completed', NULL, '2026-05-04 17:26:59'),
(142, 162, 24, 'lab', 400.00, '', 'high wbc', 'Completed', NULL, '2026-05-08 10:19:28'),
(143, 162, 30, 'lab', 200.00, '', 'neg', 'Completed', NULL, '2026-05-08 10:19:47'),
(144, 163, 32, 'lab', 300.00, '', 'leu high', 'Completed', NULL, '2026-05-08 19:13:45'),
(145, 164, 32, 'lab', 300.00, '', 'leu high', 'Completed', NULL, '2026-05-12 17:41:40'),
(146, 167, 58, 'lab', 300.00, '', 'e.h seen', 'Completed', NULL, '2026-05-13 09:33:48'),
(154, 167, 24, 'lab', 500.00, '', 'elevated wbc', 'Completed', NULL, '2026-05-13 09:35:31'),
(155, 167, 63, 'lab', 300.00, '', '`10.1g/dl', 'Completed', NULL, '2026-05-13 10:05:48'),
(156, 169, 67, 'procedure', 8500.00, NULL, NULL, 'Completed', NULL, '2026-05-16 11:36:30'),
(157, 171, 24, 'procedure', 300.00, NULL, NULL, 'Completed', NULL, '2026-05-24 21:16:38'),
(160, 173, 25, 'lab', 150.00, '', '5.5 mm/l', 'Completed', NULL, '2026-05-30 18:36:25'),
(164, 173, 63, 'procedure', 300.00, NULL, NULL, 'Completed', NULL, '2026-05-30 18:37:23'),
(165, 180, 24, 'lab', 400.00, '', 'elevated wbcs', 'Completed', NULL, '2026-05-31 21:35:10'),
(166, 180, 30, 'lab', 200.00, '', 'neg', 'Completed', NULL, '2026-05-31 21:35:23'),
(167, 182, 24, 'procedure', 250.00, NULL, NULL, 'Completed', NULL, '2026-06-05 13:12:14'),
(168, 184, 24, 'procedure', 500.00, NULL, NULL, 'Completed', NULL, '2026-06-07 15:20:10'),
(169, 186, 24, 'procedure', 500.00, NULL, NULL, 'Completed', NULL, '2026-06-08 17:51:09'),
(170, 186, 32, 'procedure', 300.00, NULL, NULL, 'Completed', NULL, '2026-06-08 17:51:19'),
(171, 185, 62, 'procedure', 200.00, NULL, NULL, 'Completed', NULL, '2026-06-08 19:09:22'),
(173, 185, 67, 'procedure', 8500.00, NULL, NULL, 'Completed', NULL, '2026-06-08 19:09:40'),
(174, 189, 32, 'lab', 300.00, '', 'leu ++', 'Completed', NULL, '2026-06-09 20:15:04'),
(175, 190, 24, 'procedure', 500.00, NULL, NULL, 'Completed', NULL, '2026-06-10 10:37:13'),
(176, 190, 32, 'procedure', 300.00, NULL, NULL, 'Completed', NULL, '2026-06-10 10:37:45'),
(177, 190, 25, 'lab', 100.00, '', '4.8mm/l', 'Completed', NULL, '2026-06-10 10:38:46'),
(178, 193, 54, 'procedure', 500.00, NULL, NULL, 'Completed', NULL, '2026-06-14 08:31:47'),
(179, 193, 32, 'lab', 300.00, '', 'nad', 'Completed', NULL, '2026-06-14 08:32:16'),
(180, 194, 63, 'lab', 300.00, '', '11.2g/dl', 'Completed', NULL, '2026-06-14 09:13:45'),
(181, 194, 60, 'lab', 200.00, '', 'neg', 'Completed', NULL, '2026-06-14 09:13:56'),
(182, 194, 67, 'procedure', 8500.00, NULL, NULL, 'Completed', NULL, '2026-06-14 09:14:24'),
(183, 196, 53, 'procedure', 300.00, NULL, NULL, 'Completed', NULL, '2026-06-18 12:36:07'),
(184, 197, 32, 'procedure', 300.00, NULL, NULL, 'Completed', NULL, '2026-06-20 08:16:14'),
(185, 199, 32, 'lab', 300.00, '', 'leu++', 'Completed', NULL, '2026-06-22 18:25:55'),
(186, 200, 30, 'lab', 250.00, '', 'neg', 'Completed', NULL, '2026-06-24 21:18:47'),
(187, 200, 59, 'lab', 800.00, '', 'pos', 'Completed', NULL, '2026-06-24 21:19:44'),
(188, 200, 24, 'lab', 500.00, '', 'wbc high', 'Completed', NULL, '2026-06-24 21:40:33'),
(189, 203, 25, 'procedure', 300.00, NULL, NULL, 'Completed', NULL, '2026-06-27 08:46:14'),
(190, 208, 24, 'lab', 800.00, '', 'wbc high', 'Completed', NULL, '2026-07-05 12:23:44'),
(191, 217, 30, 'lab', 250.00, '', 'neg', 'Completed', NULL, '2026-07-12 15:08:54'),
(192, 217, 24, 'lab', 500.00, '', 'high wbc', 'Completed', NULL, '2026-07-12 15:09:13'),
(193, 217, 63, 'lab', 300.00, '', '12.6', 'Completed', NULL, '2026-07-12 16:25:21'),
(194, 217, 25, 'lab', 100.00, '', '`12.6', 'Completed', NULL, '2026-07-12 16:25:45'),
(195, 218, 25, 'procedure', 100.00, NULL, NULL, 'Completed', NULL, '2026-07-18 11:14:34'),
(196, 218, 63, 'procedure', 300.00, NULL, NULL, 'Completed', NULL, '2026-07-18 11:14:45'),
(197, 221, 65, 'procedure', 400.00, NULL, NULL, 'Completed', NULL, '2026-07-27 19:06:24'),
(198, 223, 24, 'procedure', 500.00, NULL, NULL, 'Completed', NULL, '2026-07-30 19:11:28'),
(199, 224, 30, 'procedure', 200.00, NULL, NULL, 'Completed', NULL, '2026-08-01 15:23:22'),
(200, 224, 24, 'procedure', 500.00, NULL, NULL, 'Completed', NULL, '2026-08-01 15:23:56'),
(201, 225, 25, 'lab', 100.00, '', '5.6mm/l', 'Completed', NULL, '2026-08-04 08:17:15'),
(203, 226, 32, 'procedure', 300.00, NULL, NULL, 'Completed', NULL, '2026-08-05 21:25:07'),
(204, 228, 32, 'procedure', 300.00, NULL, NULL, 'Completed', NULL, '2026-08-06 11:56:59'),
(205, 228, 49, 'lab', 100.00, '', 'pos', 'Completed', NULL, '2026-08-06 11:57:13'),
(206, 232, 32, 'lab', 300.00, '', 'leu++', 'Completed', NULL, '2026-08-11 20:35:08'),
(207, 233, 32, 'lab', 300.00, '', 'leu++', 'Completed', NULL, '2026-08-11 20:38:03'),
(208, 234, 25, 'lab', 100.00, '', '5;6mm/l', 'Completed', NULL, '2026-08-12 16:12:40'),
(209, 234, 58, 'procedure', 300.00, NULL, NULL, 'Completed', NULL, '2026-08-12 18:34:28'),
(210, 235, 58, 'procedure', 300.00, NULL, NULL, 'Completed', NULL, '2026-08-13 16:33:55'),
(211, 236, 32, 'procedure', 300.00, NULL, NULL, 'Completed', NULL, '2026-08-14 08:30:32'),
(213, 241, 24, 'lab', 500.00, '', 'Hb ..13.5\r\nWbc . HIGH\r\nPlatelets .. normal\r\nRbcs- Normal \r\nneutrophils- high\r\nlymhocytes-normal\r\neosinophils -normal\r\n\r\n\r\nbasophils\r\n\r\n\r\n', 'Completed', NULL, '2026-08-19 09:59:17'),
(214, 242, 24, 'lab', 500.00, '', 'Hb ,11.9\r\nWBC .high\r\n\r\n', 'Completed', NULL, '2026-08-19 17:22:54'),
(215, 248, 67, 'procedure', 8500.00, NULL, NULL, 'Completed', NULL, '2026-08-25 19:22:35'),
(216, 251, 24, 'procedure', 500.00, NULL, NULL, 'Completed', NULL, '2026-08-26 14:33:49');

-- --------------------------------------------------------

--
-- Table structure for table `payers`
--

CREATE TABLE `payers` (
  `id` int(11) NOT NULL,
  `payer_code` varchar(50) NOT NULL,
  `payer_name` varchar(150) NOT NULL,
  `payer_type` enum('SHA','Insurance','Corporate','Cash','Other') NOT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `contact_email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `claims_email` varchar(150) DEFAULT NULL,
  `requires_preauth` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payer_plans`
--

CREATE TABLE `payer_plans` (
  `id` int(11) NOT NULL,
  `payer_id` int(11) NOT NULL,
  `plan_code` varchar(50) NOT NULL,
  `plan_name` varchar(150) NOT NULL,
  `coverage_scope` enum('Outpatient','Inpatient','Both') NOT NULL DEFAULT 'Outpatient',
  `consultation_cover_percent` decimal(5,2) NOT NULL DEFAULT 100.00,
  `pharmacy_cover_percent` decimal(5,2) NOT NULL DEFAULT 100.00,
  `lab_cover_percent` decimal(5,2) NOT NULL DEFAULT 100.00,
  `radiology_cover_percent` decimal(5,2) NOT NULL DEFAULT 100.00,
  `procedure_cover_percent` decimal(5,2) NOT NULL DEFAULT 100.00,
  `default_copay_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `annual_limit` decimal(12,2) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payer_tariffs`
--

CREATE TABLE `payer_tariffs` (
  `id` int(11) NOT NULL,
  `payer_id` int(11) NOT NULL,
  `plan_id` int(11) DEFAULT NULL,
  `item_type` enum('Consultation','Service','Lab','Radiology','Procedure','Pharmacy','Admission','Other') NOT NULL,
  `item_code` varchar(100) NOT NULL,
  `item_name` varchar(200) NOT NULL,
  `base_price` decimal(12,2) NOT NULL,
  `approved_price` decimal(12,2) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` varchar(50) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `patient_id`, `amount`, `method`, `reference`, `created_at`) VALUES
(1, 8, 10.00, 'M-Pesa', NULL, '2026-02-23 18:32:11'),
(2, 8, 10.00, 'Cash', NULL, '2026-02-23 18:32:24'),
(3, 8, 50.00, 'Cash', NULL, '2026-02-24 07:18:08'),
(4, 8, 50.00, 'MPESA', NULL, '2026-02-24 07:52:59'),
(5, 8, 50.00, 'MPESA', NULL, '2026-02-24 07:57:01'),
(6, 8, 50.00, 'MPESA', NULL, '2026-02-24 07:59:10');

-- --------------------------------------------------------

--
-- Table structure for table `pharmacy_counters`
--

CREATE TABLE `pharmacy_counters` (
  `id` int(11) NOT NULL,
  `counter_name` varchar(50) NOT NULL,
  `is_active` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pharmacy_counters`
--

INSERT INTO `pharmacy_counters` (`id`, `counter_name`, `is_active`) VALUES
(1, 'Counter 1', 1),
(2, 'Counter 2', 1);

-- --------------------------------------------------------

--
-- Table structure for table `pharmacy_dispense`
--

CREATE TABLE `pharmacy_dispense` (
  `id` int(11) NOT NULL,
  `prescription_id` int(11) DEFAULT NULL,
  `med_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `dispensed_by` int(11) DEFAULT NULL,
  `dispensed_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pharmacy_queue`
--

CREATE TABLE `pharmacy_queue` (
  `id` int(11) NOT NULL,
  `prescription_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `status` enum('pending','completed') DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pharmacy_sales`
--

CREATE TABLE `pharmacy_sales` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `encounter_id` int(11) DEFAULT NULL,
  `walkin_id` int(11) DEFAULT NULL,
  `med_id` int(11) DEFAULT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `dispensed_by` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `total` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pharmacy_sales`
--

INSERT INTO `pharmacy_sales` (`id`, `patient_id`, `encounter_id`, `walkin_id`, `med_id`, `invoice_id`, `dispensed_by`, `quantity`, `total`, `created_at`) VALUES
(1, NULL, 15, NULL, 62, NULL, NULL, 1, 100.00, '2026-02-04 12:14:10'),
(2, NULL, 16, NULL, 62, NULL, NULL, 1, 100.00, '2026-02-04 12:20:40'),
(3, NULL, 18, NULL, 100, NULL, NULL, 1, 50.00, '2026-02-04 12:48:07'),
(4, NULL, 19, NULL, 62, NULL, NULL, 1, 100.00, '2026-02-04 13:07:36'),
(5, 3, NULL, NULL, 37, 17, 1, 1, 1000.00, '2026-02-07 10:14:59'),
(6, 5, NULL, NULL, 38, 18, 1, 1, 1000.00, '2026-02-07 10:20:50'),
(7, NULL, NULL, NULL, 9, 20, 1, 1, 20.00, '2026-02-07 10:27:59');

-- --------------------------------------------------------

--
-- Table structure for table `pharmacy_sale_items`
--

CREATE TABLE `pharmacy_sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `medication_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pharmacy_stock`
--

CREATE TABLE `pharmacy_stock` (
  `id` int(11) NOT NULL,
  `drug_name` varchar(200) NOT NULL,
  `unit` varchar(30) DEFAULT NULL,
  `batch_no` varchar(50) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `invoice_no` varchar(50) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `status` enum('active','expired','out_of_stock') NOT NULL DEFAULT 'active',
  `buying_price` decimal(10,2) DEFAULT NULL,
  `selling_price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp(),
  `supplier` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pharmacy_stock`
--

INSERT INTO `pharmacy_stock` (`id`, `drug_name`, `unit`, `batch_no`, `expiry_date`, `supplier_id`, `invoice_no`, `quantity`, `status`, `buying_price`, `selling_price`, `created_at`, `updated_at`, `supplier`) VALUES
(1, 'erreer', 'tablets', '', '2028-01-11', NULL, '0', 452, 'active', 10.00, 15.00, '2026-01-11 07:42:27', '2026-01-11 13:20:29', ''),
(2, 'gdfdf', 'ml', '12', '2028-02-11', NULL, '1', 2, 'active', 10.00, 15.00, '2026-01-11 07:48:06', '2026-01-11 07:48:06', 'rt'),
(3, 'Omeprazole 20mg', 'capsules', '005', '2028-02-11', NULL, '1', 119, 'active', 50.00, 100.00, '2026-01-11 09:55:53', '2026-01-11 09:55:53', 'flased'),
(4, 'tridex', 'bottle', '', NULL, NULL, '0', 100, 'active', 50.00, 100.00, '2026-01-11 09:58:00', '2026-01-11 09:58:00', ''),
(5, 'omeprazole', 'capsules', '', NULL, NULL, '0', 9, 'active', 50.00, 100.00, '2026-01-11 11:06:36', '2026-01-11 11:06:36', ''),
(6, 'ceftraxne', 'vials', '', NULL, NULL, '0', 69, 'active', 35.00, 400.00, '2026-01-11 18:51:19', '2026-01-11 18:51:19', ''),
(7, 'Benacoff', 'mls', '', NULL, NULL, '0', 1, 'active', 100.00, 250.00, '2026-01-11 19:03:04', '2026-01-11 19:03:04', ''),
(8, 'buscpan inj', 'ampoules', '', NULL, NULL, '0', 31, 'active', 80.00, 400.00, '2026-01-11 19:04:40', '2026-01-11 19:04:40', ''),
(9, 'Celestamine', 'tablets', '', NULL, NULL, '0', 19, 'active', 6.00, 20.00, '2026-01-11 19:15:25', '2026-01-11 19:15:25', ''),
(10, 'Cetizine', 'tablets', '', NULL, NULL, '0', 106, 'active', 1.00, 10.00, '2026-01-11 19:16:21', '2026-01-11 19:16:21', ''),
(11, 'Good Morning (60ml)', 'syrup', '', NULL, NULL, '0', 3, 'active', 85.00, 200.00, '2026-01-11 19:19:46', '2026-01-11 19:19:46', ''),
(12, 'Procaine penicillin fortified (PPF) INJ', 'vials', '', NULL, NULL, '0', 1, 'active', 40.00, 500.00, '2026-01-11 19:22:37', '2026-01-11 19:22:37', ''),
(13, 'Promethazine 60ml', 'syrup', '', NULL, NULL, '0', 3, 'active', 25.00, 150.00, '2026-01-11 19:24:13', '2026-01-11 19:24:13', ''),
(14, 'Betafen plus 100ml', 'syrup', '', NULL, NULL, '0', 3, 'active', 75.00, 450.00, '2026-01-11 19:26:35', '2026-01-11 19:26:35', ''),
(15, 'Branulla  G24 (yellow)', 'pcs', '', NULL, NULL, '0', 10, 'active', 15.00, 50.00, '2026-01-11 19:32:12', '2026-01-11 19:32:12', ''),
(16, 'Syringes 10ml', 'pcs', '', NULL, NULL, '0', 100, 'active', 5.00, 10.00, '2026-01-11 19:37:25', '2026-01-11 19:37:25', ''),
(17, 'Hemoforce family 200ml', 'syrup', '', NULL, NULL, '0', 1, 'active', 215.00, 800.00, '2026-01-11 19:38:58', '2026-01-11 19:38:58', ''),
(18, 'Hemoforce prega 200ml', 'syrup', '', NULL, NULL, '0', 2, 'active', 300.00, 1000.00, '2026-01-11 19:40:13', '2026-01-11 19:40:13', ''),
(19, 'Ibuprofen 400mg', 'tablets', '', NULL, NULL, '0', 108, 'active', 1.25, 10.00, '2026-01-11 19:42:59', '2026-01-11 19:42:59', ''),
(20, 'O.R.S sachets', 'sachets', '', NULL, NULL, '0', 10, 'active', 9.00, 50.00, '2026-01-11 19:44:51', '2026-01-11 19:44:51', ''),
(21, 'Predinisolone 60ml', 'syrup', '', NULL, NULL, '0', 6, 'active', 80.00, 400.00, '2026-01-11 19:47:29', '2026-01-11 19:47:29', ''),
(22, 'Betamol 60ml', 'syrup', '', NULL, NULL, '0', 1, 'active', 23.00, 100.00, '2026-01-11 19:48:48', '2026-01-11 19:48:48', ''),
(23, 'Clotrimazole pessaries 100mg', 'pessaries', '', NULL, NULL, '0', 1, 'active', 35.00, 100.00, '2026-01-12 07:52:05', '2026-01-12 07:52:05', ''),
(24, 'Cetrizine 60ml', 'syrup', '', NULL, NULL, '0', 6, 'active', 26.00, 100.00, '2026-01-12 18:45:09', '2026-01-12 18:45:09', ''),
(25, 'Chloheniramine 100ml', 'syrup', '', NULL, NULL, '0', 0, 'active', 29.00, 200.00, '2026-01-12 18:48:19', '2026-01-12 18:48:19', ''),
(26, 'Cotton wool 50g', 'rolls', '', NULL, NULL, '0', 4, 'active', 50.00, 100.00, '2026-01-12 18:51:25', '2026-01-12 18:51:25', ''),
(27, 'Crepe bandage 3\"', 'rolls', '', NULL, NULL, '0', 5, 'active', 22.00, 100.00, '2026-01-12 18:57:15', '2026-01-12 18:57:15', ''),
(28, 'Fluconazole 150 mg', 'tabets', '', NULL, NULL, '0', 5, 'active', 13.00, 150.00, '2026-01-12 18:58:43', '2026-01-12 18:58:43', ''),
(29, 'Giving sets', 'pieces', '', NULL, NULL, '0', 10, 'active', 13.00, 50.00, '2026-01-12 19:00:08', '2026-01-12 19:00:08', ''),
(30, 'Lonart tabs', 'tablets', '', NULL, NULL, '0', 3, 'active', 52.00, 150.00, '2026-01-12 19:00:45', '2026-01-12 19:00:45', ''),
(31, 'Paracetamol infusion 100mls', 'Drip', '', NULL, NULL, '0', 14, 'active', 60.00, 600.00, '2026-01-12 19:01:45', '2026-01-12 19:01:45', ''),
(32, 'Paracetamol syrup 100mls', 'mls', '', NULL, NULL, '0', 5, 'active', 35.00, 150.00, '2026-01-12 19:02:53', '2026-01-12 19:02:53', ''),
(33, 'Paracetamol syrup 60mls', 'ml', '', NULL, NULL, '0', 3, 'active', 25.00, 100.00, '2026-01-12 19:03:32', '2026-01-12 19:03:32', ''),
(34, 'Cetamol 100mls', 'mls', '', NULL, NULL, '0', 0, 'active', 50.00, 200.00, '2026-01-12 19:04:17', '2026-01-12 19:04:17', ''),
(35, 'Cetamol 60mls', 'mls', '', NULL, NULL, '0', 3, 'active', 40.00, 150.00, '2026-01-12 19:05:14', '2026-01-12 19:05:14', ''),
(36, 'Helicos kit', 'kit', '', NULL, NULL, '0', 1, 'active', 1250.00, 2500.00, '2026-01-12 19:06:14', '2026-01-12 19:06:14', ''),
(37, 'Amoxiclav 625mg', 'tablets', '', NULL, NULL, '0', 2, 'active', 15.00, 1000.00, '2026-01-12 19:37:37', '2026-01-12 19:37:37', ''),
(38, 'Amoxiclav 228mg', 'syrup', '', NULL, NULL, '0', 2, 'active', 75.00, 1000.00, '2026-01-12 19:38:39', '2026-01-12 19:38:39', ''),
(39, 'Amoxiclav 156mg', 'syrup', '', NULL, NULL, '0', 2, 'active', 100.00, 800.00, '2026-01-12 19:40:03', '2026-01-12 19:40:03', ''),
(40, 'Gloves', 'boxes', '', NULL, NULL, '0', 105, 'active', 350.00, 1000.00, '2026-01-12 19:40:53', '2026-01-12 19:40:53', ''),
(41, 'Osteocare suplements', 'box', '', NULL, NULL, '0', 1, 'active', 675.00, 1800.00, '2026-01-12 19:42:33', '2026-01-12 19:42:33', ''),
(42, 'Artemether inj 80mg/ml', 'ampoules', '', NULL, NULL, '0', 5, 'active', 65.00, 350.00, '2026-01-12 19:44:18', '2026-01-12 19:44:18', ''),
(43, 'Meloxicam 7.5 mg', 'tablets', '', NULL, NULL, '0', 125, 'active', 1.40, 15.00, '2026-01-12 19:46:06', '2026-01-12 19:46:06', ''),
(44, 'Meloxicam 15 mg', 'tablets', '', NULL, NULL, '0', 58, 'active', 2.00, 20.00, '2026-01-12 19:46:48', '2026-01-12 19:46:48', ''),
(45, 'Probeta eye/ear /nose drops', 'bottle', '', NULL, NULL, '0', 2, 'active', 115.00, 200.00, '2026-01-12 19:48:17', '2026-01-12 19:48:17', ''),
(46, 'Ceftax (inj)', 'vial', '', NULL, NULL, '0', 8, 'active', 98.00, 500.00, '2026-01-12 19:50:23', '2026-01-12 19:50:23', ''),
(47, 'Clotrimazole cream', 'tube', '', NULL, NULL, '0', 2, 'active', 25.00, 200.00, '2026-01-12 19:51:38', '2026-01-12 19:51:38', ''),
(48, 'Diclofenac gel', 'tube', '', NULL, NULL, '0', 2, 'active', 20.00, 150.00, '2026-01-12 19:52:33', '2026-01-12 19:52:33', ''),
(49, 'P2 ( generic)', 'tablets', '', NULL, NULL, '0', 7, 'active', 35.00, 100.00, '2026-01-12 19:53:41', '2026-01-12 19:53:41', ''),
(50, 'P2 (original)', 'tablets', '', NULL, NULL, '0', 3, 'active', 100.00, 200.00, '2026-01-12 19:54:44', '2026-01-12 19:54:44', ''),
(51, 'Ampiclox  100mls', 'mls', '', NULL, NULL, '0', 3, 'active', 70.00, 300.00, '2026-01-12 19:56:10', '2026-01-12 19:56:10', ''),
(52, 'Neonatal ampiclox', 'syrup', '', NULL, NULL, '0', 3, 'active', 50.00, 250.00, '2026-01-12 19:57:44', '2026-01-12 19:57:44', ''),
(53, 'Branulla  G26 (purple)', 'pcs', '', NULL, NULL, '0', 16, 'active', 16.00, 50.00, '2026-01-12 19:58:35', '2026-01-12 19:58:35', ''),
(54, 'Brustan 100mls', 'syrup', '', NULL, NULL, '0', 7, 'active', 260.00, 700.00, '2026-01-12 19:59:14', '2026-01-12 19:59:14', ''),
(55, 'Syringes 5ml', 'box', '', NULL, NULL, '0', 1, 'active', 270.00, 1000.00, '2026-01-12 20:00:56', '2026-01-12 20:00:56', ''),
(56, 'Plasil  syrup', 'syrup', '', NULL, NULL, '0', 2, 'active', 65.00, 250.00, '2026-01-12 20:02:11', '2026-01-12 20:02:11', ''),
(57, 'Hyoscine  (inj)', 'ampoules', '', NULL, NULL, '0', 10, 'active', 18.00, 400.00, '2026-01-12 20:02:52', '2026-01-12 20:02:52', ''),
(58, 'Ibugesic 100mls', 'syrup', '', NULL, NULL, '0', 4, 'active', 105.00, 450.00, '2026-01-12 20:03:24', '2026-01-12 20:03:24', ''),
(59, 'Azithromycin 15mls', 'syrup', '', NULL, NULL, '0', 5, 'active', 45.00, 150.00, '2026-01-12 20:04:34', '2026-01-12 20:04:34', ''),
(60, 'Prednisolone 5mg  (cosmos0', 'tablets', '', NULL, NULL, '0', 318, 'active', 2.30, 10.00, '2026-01-12 20:05:35', '2026-01-12 20:05:35', ''),
(61, 'ABZ  400mg', 'tablets', '', NULL, NULL, '0', 3, 'active', 10.00, 100.00, '2026-01-12 20:06:40', '2026-01-12 20:06:40', ''),
(62, 'ABZ 10mls', 'syrup', '', NULL, NULL, '0', 5, 'active', 18.00, 100.00, '2026-01-12 20:07:15', '2026-01-12 20:07:15', ''),
(63, 'Entamaxin 60mls', 'syrup', '', NULL, NULL, '0', 3, 'active', 70.00, 200.00, '2026-01-12 20:07:58', '2026-01-12 20:07:58', ''),
(64, 'Aminophylline (inj) 10mls', 'ampoules', '', NULL, NULL, '0', 3, 'active', 22.00, 800.00, '2026-01-12 20:09:14', '2026-01-12 20:09:14', ''),
(65, 'Lignocaine 30mls', 'vials', '', NULL, NULL, '0', 3, 'active', 36.00, 300.00, '2026-01-12 20:10:51', '2026-01-12 20:10:51', ''),
(66, 'Norash cream', 'tube', '', NULL, NULL, '0', 1, 'active', 310.00, 700.00, '2026-01-12 20:11:51', '2026-01-12 20:11:51', ''),
(67, 'Normal saline 500mls', 'drip', '', NULL, NULL, '0', 8, 'active', 75.00, 800.00, '2026-01-12 20:13:03', '2026-01-12 20:13:03', ''),
(68, 'TEO', 'tube', '', NULL, NULL, '0', 3, 'active', 35.00, 200.00, '2026-01-12 20:14:20', '2026-01-12 20:14:20', ''),
(69, 'Hctz 25mg', 'tablets', '', NULL, NULL, '0', 90, 'active', 1.97, 5.00, '2026-01-12 20:16:32', '2026-01-12 20:16:32', ''),
(70, 'Azithromycin 500mg', 'tablets', '', NULL, NULL, '0', 5, 'active', 55.00, 300.00, '2026-01-12 20:18:00', '2026-01-12 20:18:00', ''),
(71, 'Needles ( G21)', 'box', '', NULL, NULL, '0', 2, 'active', 103.00, 500.00, '2026-01-12 20:19:58', '2026-01-12 20:19:58', ''),
(72, 'Needles ( G23)', 'box', '', NULL, NULL, '0', 1, 'active', 108.00, 500.00, '2026-01-12 20:20:36', '2026-01-12 20:20:36', ''),
(73, 'Safety box', 'box', '', NULL, NULL, '0', 1, 'active', 130.00, 150.00, '2026-01-12 20:21:27', '2026-01-12 20:21:27', ''),
(74, 'Femiplan pills', 'strips', '', NULL, NULL, '0', 4, 'active', 85.00, 200.00, '2026-01-12 20:22:26', '2026-01-12 20:22:26', ''),
(75, 'Diclofenac 100mg', 'tablets', '', NULL, NULL, '0', 136, 'active', 0.65, 15.00, '2026-01-12 20:23:43', '2026-01-12 20:23:43', ''),
(76, 'Glucomet 500mg', 'tablets', '', NULL, NULL, '0', 28, 'active', 4.46, 10.00, '2026-01-12 20:25:19', '2026-01-12 20:25:19', ''),
(77, 'Ferroussulphate', 'tablets', '', NULL, NULL, '0', 60, 'active', 1.00, 10.00, '2026-01-12 20:27:25', '2026-01-12 20:27:25', ''),
(78, 'Alugel 100mls', 'syrup', '', NULL, NULL, '0', 2, 'active', 86.00, 200.00, '2026-01-12 20:28:04', '2026-01-12 20:28:04', ''),
(79, 'Ringers lactate 500mls', 'drip', '', NULL, NULL, '0', 4, 'active', 90.00, 800.00, '2026-01-12 20:28:39', '2026-01-12 20:28:39', ''),
(80, 'buscpan 10mg', 'tablets', '', NULL, NULL, '0', 119, 'active', 2.40, 10.00, '2026-01-12 20:29:43', '2026-01-12 20:29:43', ''),
(81, 'Metronidazole 400mg', 'tablets', '', NULL, NULL, '0', 41, 'active', 1.35, 15.00, '2026-01-12 20:30:46', '2026-01-12 20:30:46', ''),
(82, 'Metronidazole 500mg /100mls ( infusion)', 'drip', '', NULL, NULL, '0', 9, 'active', 35.00, 500.00, '2026-01-12 20:32:05', '2026-01-12 20:32:05', ''),
(83, 'Dextrose 10% 500mls', 'drip', '', NULL, NULL, '0', 4, 'active', 50.00, 800.00, '2026-01-12 20:33:00', '2026-01-12 20:33:00', ''),
(84, 'Branulla  G20 (pink)', 'pcs', '', NULL, NULL, '0', 5, 'active', 12.00, 50.00, '2026-01-12 20:34:12', '2026-01-12 20:34:12', ''),
(85, 'Cord clamps', 'pcs', '', NULL, NULL, '0', 4, 'active', 7.60, 50.00, '2026-01-12 20:35:40', '2026-01-12 20:35:40', ''),
(86, 'Paracetamol 500mg', 'tablets', '', NULL, NULL, '0', 210, 'active', 0.65, 5.00, '2026-01-12 20:36:55', '2026-01-12 20:36:55', ''),
(87, 'Surgical spirit 50mls', 'mls', '', NULL, NULL, '0', 4, 'active', 28.00, 50.00, '2026-01-12 20:37:37', '2026-01-12 20:37:37', ''),
(88, 'Nystatin 30mls', 'syrup', '', NULL, NULL, '0', 0, 'active', 60.00, 300.00, '2026-01-12 20:38:10', '2026-01-12 20:38:10', ''),
(89, 'Cypro B plus', 'tablets', '', NULL, NULL, '0', 48, 'active', 12.00, 30.00, '2026-01-12 20:39:57', '2026-01-12 20:39:57', ''),
(90, 'Xtraderm cream', 'tube', '', NULL, NULL, '0', 1, 'active', 125.00, 250.00, '2026-01-12 20:40:47', '2026-01-12 20:40:47', ''),
(91, 'Hydrocortisone 100mg ( inj)', 'vials', '', NULL, NULL, '0', 11, 'active', 30.00, 350.00, '2026-01-12 20:42:11', '2026-01-12 20:42:11', ''),
(92, 'Avamys nasal spray', 'spray', '', NULL, NULL, '0', 0, 'active', 1050.00, 2000.00, '2026-01-12 20:43:22', '2026-01-12 20:43:22', ''),
(93, 'Dexamethasole 4mg (INJ)', 'ampoules', '', NULL, NULL, '0', 46, 'active', 8.00, 400.00, '2026-01-12 20:44:29', '2026-01-12 20:44:29', ''),
(94, 'Metronidazole  100ml', 'syrup', '', NULL, NULL, '0', 13, 'active', 43.00, 200.00, '2026-01-12 20:45:17', '2026-01-12 20:45:17', ''),
(95, 'Clotrimazole pessaries 200mg', 'pessaries', '', NULL, NULL, '0', 1, 'active', 55.00, 200.00, '2026-01-12 20:46:19', '2026-01-12 20:46:19', ''),
(96, 'Dexamethasole 4mg', 'tablets', '', NULL, NULL, '0', 93, 'active', 0.04, 10.00, '2026-01-12 20:47:12', '2026-01-12 20:47:12', ''),
(97, 'Gentamycin ear /eye drops', 'bottle', '', NULL, NULL, '0', 2, 'active', 20.00, 100.00, '2026-01-12 20:48:03', '2026-01-12 20:48:03', ''),
(98, 'Amoxicilin 60mls', 'syrup', '', NULL, NULL, '0', 4, 'active', 31.00, 150.00, '2026-01-12 20:49:14', '2026-01-12 20:49:14', ''),
(99, 'Amoxicilin 100mls', 'syrup', '', NULL, NULL, '0', 3, 'active', 49.00, 200.00, '2026-01-12 20:49:43', '2026-01-12 20:49:43', ''),
(100, 'Acetal mr', 'tablets', '', NULL, NULL, '0', 120, 'active', 6.50, 50.00, '2026-01-12 20:51:20', '2026-01-12 20:51:20', ''),
(101, 'Nilworm syrup', 'syrup', '', NULL, NULL, '0', 5, 'active', 20.00, 50.00, '2026-01-12 20:52:41', '2026-01-12 20:52:41', ''),
(102, 'Ampiclox  500mg', 'tablets', '', NULL, NULL, '0', 120, 'active', 4.25, 20.00, '2026-01-12 20:54:44', '2026-01-12 20:54:44', ''),
(103, 'Ashton powder', 'powder', '', NULL, NULL, '0', 14, 'active', 3.50, 10.00, '2026-01-12 20:56:07', '2026-01-12 20:56:07', ''),
(104, 'Calpol 60ms', 'syrup', '', NULL, NULL, '0', 1, 'active', 230.00, 450.00, '2026-01-12 20:56:45', '2026-01-12 20:56:45', ''),
(105, 'Clozole B cream', 'tube', '', NULL, NULL, '0', 1, 'active', 75.00, 150.00, '2026-01-12 20:58:17', '2026-01-12 20:58:17', ''),
(106, 'Cotrimoxazole 960mg', 'tablets', '', NULL, NULL, '0', 70, 'active', 3.10, 30.00, '2026-01-12 21:00:23', '2026-01-12 21:00:23', ''),
(107, 'Esomeprazole 40mg ( inj)', 'vial', '', NULL, NULL, '0', 4, 'active', 60.00, 500.00, '2026-01-12 21:01:40', '2026-01-12 21:01:40', ''),
(108, 'Ibugesic  60mls', 'syrup', '', NULL, NULL, '0', 3, 'active', 80.00, 400.00, '2026-01-12 21:02:20', '2026-01-12 21:02:20', ''),
(109, 'Metoclopromide 2mls (inj)', 'ampoules', '', NULL, NULL, '0', 7, 'active', 12.00, 400.00, '2026-01-12 21:04:27', '2026-01-12 21:04:27', ''),
(110, 'Metronidazole 200mg', 'tablets', '', NULL, NULL, '0', 70, 'active', 0.90, 15.00, '2026-01-12 21:05:08', '2026-01-12 21:05:08', ''),
(111, 'Multivitamin 100mls', 'syrup', '', NULL, NULL, '0', 2, 'active', 50.00, 200.00, '2026-01-12 21:05:59', '2026-01-12 21:05:59', ''),
(112, 'Multivitamin 60mls', 'syrup', '', NULL, NULL, '0', 2, 'active', 25.00, 150.00, '2026-01-12 21:06:40', '2026-01-12 21:06:40', ''),
(113, 'Neuroforte', 'tablets', '', NULL, NULL, '0', 54, 'active', 3.00, 30.00, '2026-01-12 21:09:58', '2026-01-12 21:09:58', ''),
(114, 'Normal saline drops 15mls', 'mls', '', NULL, NULL, '0', 5, 'active', 25.00, 150.00, '2026-01-12 21:11:15', '2026-01-12 21:11:15', ''),
(115, 'Oxytocin', 'vias', '', NULL, NULL, '0', 5, 'active', 30.00, 300.00, '2026-01-12 21:11:52', '2026-01-12 21:11:52', ''),
(116, 'Ranferon 200mls', 'syrup', '', NULL, NULL, '0', 4, 'active', 330.00, 1000.00, '2026-01-12 21:12:42', '2026-01-12 21:12:42', ''),
(117, 'Tanzol  10mls', 'syrup', '', NULL, NULL, '0', 0, 'active', 20.00, 50.00, '2026-01-12 21:13:26', '2026-01-12 21:13:26', ''),
(118, 'Tanzol  400mg', 'tablets', '', NULL, NULL, '0', 6, 'active', 13.00, 50.00, '2026-01-12 21:14:12', '2026-01-12 21:14:12', ''),
(119, 'Tridex 60mls', 'syrup', '', NULL, NULL, '0', 2, 'active', 45.00, 150.00, '2026-01-12 21:14:57', '2026-01-12 21:14:57', ''),
(120, 'C.D (classic)', 'pkt', '', NULL, NULL, '0', 3, 'active', 21.00, 50.00, '2026-01-12 21:16:10', '2026-01-12 21:16:10', ''),
(121, 'Esomeprazole 40mg', 'tablets', '', NULL, NULL, '0', 56, 'active', 4.30, 30.00, '2026-01-12 21:17:30', '2026-01-12 21:17:30', ''),
(122, 'DNS 500mls', 'drip', '', NULL, NULL, '0', 2, 'active', 75.00, 800.00, '2026-01-12 21:19:12', '2026-01-12 21:19:12', ''),
(123, 'Gacet 125mg', 'supps', '', NULL, NULL, '0', 4, 'active', 18.00, 100.00, '2026-01-12 21:20:13', '2026-01-12 21:20:13', ''),
(124, 'Benzylpwenicillin (inj)', 'vials', '', NULL, NULL, '0', 4, 'active', 18.00, 400.00, '2026-01-12 21:22:06', '2026-01-12 21:22:06', ''),
(125, 'Crepe bandage 2\"', 'rolls', '', NULL, NULL, '0', 50, 'active', 18.00, 50.00, '2026-01-12 21:24:26', '2026-01-12 21:24:26', ''),
(126, 'Fevapyn 60mls', 'syrup', '', NULL, NULL, '0', 5, 'active', 23.00, 200.00, '2026-01-12 21:25:07', '2026-01-12 21:25:07', ''),
(127, 'Fevapyn 100mls', 'syrup', '', NULL, NULL, '0', 5, 'active', 30.00, 300.00, '2026-01-12 21:25:34', '2026-01-12 21:25:34', ''),
(128, 'Zulu', 'tablets', '', NULL, NULL, '0', 11, 'active', 29.00, 50.00, '2026-01-12 21:26:31', '2026-01-12 21:26:31', ''),
(129, 'Acnestar cream', 'tube', '', NULL, NULL, '0', 0, 'active', 75.00, 250.00, '2026-01-12 21:27:46', '2026-01-12 21:27:46', ''),
(130, 'Amlodipine', 'tablets', '', NULL, NULL, '0', 0, 'active', 4.00, 10.00, '2026-01-12 21:28:57', '2026-01-12 21:28:57', ''),
(131, 'Presartan H', 'tablets', '', NULL, NULL, '0', 5, 'active', 5.00, 10.00, '2026-01-12 21:29:41', '2026-01-12 21:29:41', ''),
(132, 'Medical envelope', 'pcs', '', NULL, NULL, '0', 100, 'active', 0.20, 10.00, '2026-01-12 21:31:33', '2026-01-12 21:31:33', ''),
(133, 'Cotton wool 100g', 'roll', '', NULL, NULL, '0', 4, 'active', 88.00, 200.00, '2026-01-12 21:32:16', '2026-01-12 21:32:16', ''),
(134, 'Cotton wool 400g', 'roll', '', NULL, NULL, '0', 5, 'active', 276.00, 500.00, '2026-01-12 21:33:04', '2026-01-12 21:33:04', ''),
(135, 'Dextrose 5% 500mls', 'drip', '', NULL, NULL, '0', 6, 'active', 50.00, 800.00, '2026-01-12 21:33:49', '2026-01-12 21:33:49', ''),
(136, 'Bulkot B cream', 'tube', '', NULL, NULL, '0', 4, 'active', 50.00, 200.00, '2026-01-12 21:35:25', '2026-01-12 21:35:25', ''),
(137, 'Griseofuvin 50mg', 'tablets', '', NULL, NULL, '0', 80, 'active', 6.80, 27.00, '2026-01-12 21:37:43', '2026-01-12 21:37:43', ''),
(138, 'Loperamide', 'tablets', '', NULL, NULL, '0', 22, 'active', 2.00, 20.00, '2026-01-12 21:38:39', '2026-01-12 21:38:39', ''),
(139, 'Tranexamic acid ( inj)', 'vials', '', NULL, NULL, '0', 8, 'active', 84.00, 800.00, '2026-01-12 21:39:34', '2026-01-12 21:39:34', ''),
(140, 'Natoa 30mls', 'syrup', '', NULL, NULL, '0', 1, 'active', 50.00, 100.00, '2026-01-12 21:40:12', '2026-01-12 21:40:12', ''),
(141, 'Natoa tabs', 'pkt', '', NULL, NULL, '0', 0, 'active', 40.00, 100.00, '2026-01-12 21:41:22', '2026-01-12 21:41:22', ''),
(142, 'depo( inj)', 'vials', '', NULL, NULL, '0', 4, 'active', 80.00, 200.00, '2026-01-12 21:42:37', '2026-01-12 21:42:37', ''),
(143, 'Bonnissan 120mls', 'syrup', '', NULL, NULL, '0', 1, 'active', 390.00, 800.00, '2026-01-12 21:43:40', '2026-01-12 21:43:40', ''),
(144, 'Gripe water 60mls', 'syrup', '', NULL, NULL, '0', 2, 'active', 30.00, 100.00, '2026-01-12 21:44:47', '2026-01-12 21:44:47', ''),
(145, 'VEGA 100MG', 'tablets', '', NULL, NULL, '0', 5, 'active', 10.00, 50.00, '2026-01-12 21:45:41', '2026-01-12 21:45:41', ''),
(146, 'Anti D', 'vials', '', NULL, NULL, '0', 2, 'active', 6900.00, 8000.00, '2026-01-12 21:47:01', '2026-01-12 21:47:01', ''),
(147, 'Acyclovir cream', 'tube', '', NULL, NULL, '0', 0, 'active', 50.00, 250.00, '2026-01-12 21:48:20', '2026-01-12 21:48:20', ''),
(148, 'ENO', 'pair', '', NULL, NULL, '0', 9, 'active', 15.00, 30.00, '2026-01-12 21:50:45', '2026-01-12 21:50:45', ''),
(149, 'ENO  sachet', 'sachets', '', NULL, NULL, '0', 7, 'active', 6.00, 30.00, '2026-01-12 21:51:14', '2026-01-12 21:51:14', ''),
(150, 'Bromocreptine 2.5 mg', 'tablets', '', NULL, NULL, '0', 10, 'active', 33.70, 60.00, '2026-01-12 21:53:47', '2026-01-12 21:53:47', ''),
(151, 'PRESARTAN H', 'tablets', '', NULL, NULL, '0', 20, 'active', 5.00, 10.00, '2026-01-12 21:55:47', '2026-01-12 21:55:47', ''),
(152, 'Branulla  G22 (blue)', 'pcs', '', NULL, NULL, '0', 2, 'active', 15.00, 50.00, '2026-01-12 21:57:55', '2026-01-12 21:57:55', ''),
(153, 'ORS sachets', 'sachets', '', NULL, NULL, '0', 5, 'active', 9.00, 50.00, '2026-01-12 21:59:42', '2026-01-12 21:59:42', ''),
(154, 'Lactulose 100mls', 'syrup', '', NULL, NULL, '0', 2, 'active', 215.00, 400.00, '2026-01-12 22:01:19', '2026-01-12 22:01:19', ''),
(155, 'Pure glycerine', 'syrup', '', NULL, NULL, '0', 1, 'active', 50.00, 100.00, '2026-01-12 22:02:22', '2026-01-12 22:02:22', ''),
(156, 'Calamine lotion 100mls', 'bottle', '', NULL, NULL, '0', 1, 'active', 30.00, 200.00, '2026-01-12 22:04:15', '2026-01-12 22:04:15', ''),
(157, 'Cypon 100mls', 'syrup', '', NULL, NULL, '0', 1, 'active', 235.00, 500.00, '2026-01-12 22:04:49', '2026-01-12 22:04:49', ''),
(158, 'Hydrocortisone cream', 'tube', '', NULL, NULL, '0', 2, 'active', 35.00, 100.00, '2026-01-12 22:05:32', '2026-01-12 22:05:32', ''),
(159, 'Flugone 120mls', 'syrup', '', NULL, NULL, '0', 1, 'active', 275.00, 500.00, '2026-01-12 22:06:03', '2026-01-12 22:06:03', ''),
(160, 'Flugone 60 mls', 'syrup', '', NULL, NULL, '0', 1, 'active', 174.00, 400.00, '2026-01-12 22:06:33', '2026-01-12 22:06:33', ''),
(161, 'Gastrogel 100mls', 'syrup', '', NULL, NULL, '0', 2, 'active', 65.00, 250.00, '2026-01-12 22:07:13', '2026-01-12 22:07:13', ''),
(162, 'kaluma balm', 'pcs', '', NULL, NULL, '0', 0, 'active', 35.00, 50.00, '2026-01-12 22:08:02', '2026-01-12 22:08:02', ''),
(163, 'liquid paraffin 100mls', 'syrup', '', NULL, NULL, '0', 1, 'active', 52.00, 200.00, '2026-01-12 22:08:34', '2026-01-12 22:08:34', ''),
(164, 'Povidone 100mls', 'mls', '', NULL, NULL, '0', 1, 'active', 75.00, 300.00, '2026-01-12 22:09:40', '2026-01-12 22:09:40', ''),
(165, 'Relcer gel 100mls', 'syrup', '', NULL, NULL, '0', 1, 'active', 265.00, 500.00, '2026-01-12 22:10:23', '2026-01-12 22:10:23', ''),
(166, 'Pregnancy kit', 'kit', '', NULL, NULL, '0', 3, 'active', 7.00, 50.00, '2026-01-12 22:12:02', '2026-01-12 22:12:02', ''),
(167, 'Surgical spirit 100mls', 'mls', '', NULL, NULL, '0', 1, 'active', 35.00, 100.00, '2026-01-12 22:13:10', '2026-01-12 22:13:10', ''),
(168, 'Allucid 100mls', 'syrup', '', NULL, NULL, '0', 1, 'active', 100.00, 250.00, '2026-01-12 22:13:41', '2026-01-12 22:13:41', ''),
(169, 'C.D (KISS)', 'PC', '', NULL, NULL, '0', 3, 'active', 25.00, 100.00, '2026-01-12 22:15:31', '2026-01-12 22:15:31', ''),
(170, 'Sodamint', 'tablets', '', NULL, NULL, '0', 98, 'active', 0.90, 5.00, '2026-01-12 22:16:08', '2026-01-12 22:16:08', ''),
(171, 'Tranexamic acid  tabs', 'tablets', '', NULL, NULL, '0', 27, 'active', 19.50, 50.00, '2026-01-12 22:16:52', '2026-01-12 22:16:52', ''),
(172, 'Benzathine penicillin 2.4mu', 'vials', '', NULL, NULL, '0', 5, 'active', 40.00, 500.00, '2026-01-12 22:17:43', '2026-01-12 22:17:43', ''),
(173, 'Coldcap', 'tablets', '', NULL, NULL, '0', 6, 'active', 10.40, 20.00, '2026-01-12 22:20:00', '2026-01-12 22:20:00', ''),
(174, 'Diazepam 5mg', 'tablets', '', NULL, NULL, '0', 93, 'active', 2.90, 10.00, '2026-01-12 22:20:28', '2026-01-12 22:20:28', ''),
(175, 'Secnidazole', 'pair', '', NULL, NULL, '0', 22, 'active', 30.00, 100.00, '2026-01-12 22:22:12', '2026-01-12 22:22:12', ''),
(176, 'Diracip m', 'tablets', '', NULL, NULL, '0', 30, 'active', 260.00, 700.00, '2026-01-12 22:22:55', '2026-01-12 22:22:55', ''),
(177, 'Elastoplast', 'strips', '', NULL, NULL, '0', 30, 'active', 1.20, 10.00, '2026-01-12 22:23:28', '2026-01-12 22:23:28', ''),
(178, 'Nosic tablets', 'tablets', '', NULL, NULL, '0', 12, 'active', 21.70, 30.00, '2026-01-12 22:24:57', '2026-01-12 22:24:57', ''),
(179, 'Nylon sutures', 'sutures', '', NULL, NULL, '0', 5, 'active', 23.00, 300.00, '2026-01-12 22:26:57', '2026-01-12 22:26:57', ''),
(180, 'Pregnacare', 'kit', '', NULL, NULL, '0', 1, 'active', 790.00, 2500.00, '2026-01-12 22:27:49', '2026-01-12 22:27:49', ''),
(181, 'Tinidazole', 'tabl', '', NULL, NULL, '0', 32, 'active', 3.50, 20.00, '2026-01-12 22:29:08', '2026-01-12 22:29:08', ''),
(182, 'Betafen plus 60ml', 'syrup', '', NULL, NULL, '0', 3, 'active', 105.00, 400.00, '2026-01-12 22:29:57', '2026-01-12 22:29:57', ''),
(183, 'Cotton wool 200g', 'roll', '', NULL, NULL, '0', 3, 'active', 155.00, 300.00, '2026-01-12 22:30:54', '2026-01-12 22:30:54', ''),
(184, 'Chlopheniramine 60ml', 'syrup', '', NULL, NULL, '0', 9, 'active', 25.00, 100.00, '2026-01-12 22:31:53', '2026-01-12 22:31:53', ''),
(185, 'strepsils', 'pairs', '', NULL, NULL, '0', 12, 'active', 30.00, 50.00, '2026-01-12 22:33:58', '2026-01-12 22:33:58', ''),
(186, 'Ciprofloxacin 500mg', 'tabs', '', NULL, NULL, '0', 10, 'active', 3.50, 20.00, '2026-01-12 22:35:58', '2026-01-12 22:35:58', ''),
(187, 'Flucloxacilin  100mls', 'syrup', '', NULL, NULL, '0', 2, 'active', 75.00, 350.00, '2026-01-12 22:37:01', '2026-01-12 22:37:01', ''),
(188, 'Doxycycline 100mg', 'tabs', '', NULL, NULL, '0', 39, 'active', 2.30, 30.00, '2026-01-12 22:38:29', '2026-01-12 22:38:29', ''),
(189, 'Shaltoux 100mls', 'syrup', '', NULL, NULL, '0', 1, 'active', 180.00, 500.00, '2026-01-12 22:39:24', '2026-01-12 22:39:24', ''),
(190, 'Calpol 100ms', 'syrup', '', NULL, NULL, '0', 2, 'active', 325.00, 550.00, '2026-01-12 22:40:28', '2026-01-12 22:40:28', ''),
(191, 'Dermazine cream', 'tube', '', NULL, NULL, '0', 38, 'active', 38.00, 150.00, '2026-01-12 22:41:10', '2026-01-12 22:41:10', ''),
(192, 'Hartmans sol 500mls', 'drip', '', NULL, NULL, '0', 2, 'active', 65.00, 800.00, '2026-01-12 22:42:16', '2026-01-12 22:42:16', ''),
(193, 'kaluma tabs', 'tabs', '', NULL, NULL, '0', 43, 'active', 6.20, 10.00, '2026-01-12 22:43:24', '2026-01-12 22:43:24', ''),
(194, 'Maramoja', 'tablets', '', NULL, NULL, '0', 10, 'active', 7.60, 10.00, '2026-01-12 22:44:23', '2026-01-12 22:44:23', ''),
(195, 'Ondasentron 4ml', 'vials', '', NULL, NULL, '0', 10, 'active', 80.00, 500.00, '2026-01-12 22:45:33', '2026-01-12 22:45:33', ''),
(196, 'Otobiotic ear drop', 'mls', '', NULL, NULL, '0', 1, 'active', 240.00, 500.00, '2026-01-12 22:46:11', '2026-01-12 22:46:11', ''),
(197, 'Pharmasal ointment', 'tube', '', NULL, NULL, '0', 4, 'active', 55.00, 250.00, '2026-01-12 22:46:49', '2026-01-12 22:46:49', ''),
(198, 'Ibuprofen  100mls', 'syrup', '', NULL, NULL, '0', 1, 'active', 31.00, 200.00, '2026-01-12 22:47:27', '2026-01-12 22:47:27', ''),
(199, 'Promethazine  25 mg tabs', 'tabs', '', NULL, NULL, '0', 52, 'active', 1.70, 5.00, '2026-01-12 22:48:11', '2026-01-12 22:48:11', ''),
(200, 'Enema 120mls', 'mls', '', NULL, NULL, '0', 1, 'active', 185.00, 450.00, '2026-01-12 22:49:07', '2026-01-12 22:49:07', ''),
(201, 'Tramadol 50mg ( INJ)', 'ampoules', '', NULL, NULL, '0', 2, 'active', 25.00, 500.00, '2026-01-12 22:50:09', '2026-01-12 22:50:09', ''),
(202, 'Tridex 100mls', 'syrup', '', NULL, NULL, '0', 3, 'active', 50.00, 200.00, '2026-01-12 22:50:37', '2026-01-12 22:50:37', ''),
(203, 'zedcal tabs', 'kit', '', NULL, NULL, '0', 1, 'active', 515.00, 1500.00, '2026-01-12 22:51:37', '2026-01-12 22:51:37', ''),
(204, 'Alcof  100mls', 'syrup', '', NULL, NULL, '0', 0, 'active', 40.00, 200.00, '2026-01-12 22:52:33', '2026-01-12 22:52:33', ''),
(205, 'Alcof  60mls', 'syrup', '', NULL, NULL, '0', 2, 'active', 35.00, 150.00, '2026-01-12 22:53:07', '2026-01-12 22:53:07', ''),
(206, 'Amoxicilin 250mg', 'tabs', '', NULL, NULL, '0', 129, 'active', 2.40, 20.00, '2026-01-12 22:53:55', '2026-01-12 22:53:55', ''),
(207, 'Amoxicilin 500mg', 'tabs', '', NULL, NULL, '0', 305, 'active', 3.80, 10.00, '2026-01-12 22:54:42', '2026-01-12 22:54:42', ''),
(208, 'Cipladon 1000mg', 'tabs', '', NULL, NULL, '0', 5, 'active', 45.00, 100.00, '2026-01-12 22:55:57', '2026-01-12 22:55:57', ''),
(209, 'Ibuprofen 200mg', 'tabs', '', NULL, NULL, '0', 93, 'active', 0.90, 10.00, '2026-01-12 22:57:24', '2026-01-12 22:57:24', ''),
(210, 'Nitrofurantoin 100mg', 't\\ab', '', NULL, NULL, '0', 49, 'active', 2.10, 10.00, '2026-01-12 22:58:16', '2026-01-12 22:58:16', ''),
(211, 'Powergesic gel', 'tube', '', NULL, NULL, '0', 2, 'active', 240.00, 450.00, '2026-01-12 22:59:00', '2026-01-12 22:59:00', ''),
(212, 'Salbutamol 60mls', 'syrup', '', NULL, NULL, '0', 1, 'active', 30.00, 100.00, '2026-01-12 22:59:50', '2026-01-12 22:59:50', ''),
(213, 'Zinc sulphate 20mg', 'tabs', '', NULL, NULL, '0', 134, 'active', 1.50, 20.00, '2026-01-12 23:00:29', '2026-01-12 23:00:29', ''),
(214, 'Tricohist  60mls', 'syrup', '', NULL, NULL, '0', 3, 'active', 105.00, 250.00, '2026-01-12 23:01:29', '2026-01-12 23:01:29', ''),
(215, 'Metronidazole  600ml', 'syrup', '', NULL, NULL, '0', 7, 'active', 30.00, 100.00, '2026-01-12 23:02:26', '2026-01-12 23:02:26', ''),
(216, 'NIfedipine 20mg', 'tabs', '', NULL, NULL, '0', 144, 'active', 0.90, 10.00, '2026-01-12 23:03:33', '2026-01-12 23:03:33', ''),
(217, 'Pylotrip kit', 'kit', '', NULL, NULL, '0', 1, 'active', 1400.00, 3000.00, '2026-01-12 23:04:21', '2026-01-12 23:04:21', ''),
(218, 'Salbutamol 100mls', 'syrup', '', NULL, NULL, '0', 1, 'active', 27.00, 200.00, '2026-01-12 23:05:28', '2026-01-12 23:05:28', ''),
(219, 'dds', '1', '', '2026-02-25', NULL, '0', 2, 'active', 12.00, 15.00, '2026-02-23 18:02:24', '2026-02-23 18:02:24', ''),
(220, 'diclofenac inj', 'vials', '', NULL, NULL, '0', 17, 'active', 350.00, 400.00, '2026-02-26 07:36:39', '2026-02-26 07:36:39', ''),
(221, 'PIRITON', 'tabs', '', NULL, NULL, '0', 76, 'active', 0.25, 5.00, '2026-02-26 13:57:08', '2026-02-26 13:57:08', ''),
(222, 'Shaultox syrup', 'syrup', '', NULL, NULL, '0', 1, 'active', 230.00, 500.00, '2026-02-26 14:01:27', '2026-02-26 14:01:27', ''),
(223, 'diprofos', 'vials', '', NULL, NULL, '0', 3, 'active', 400.00, 1200.00, '2026-02-27 05:24:32', '2026-02-27 05:24:32', ''),
(224, 'Tranexamic acid ( inj)', 'vials', '', NULL, NULL, '0', 10, 'active', 100.00, 800.00, '2026-02-27 19:06:06', '2026-02-27 19:06:06', ''),
(225, 'Tranexamic acid ( inj)', 'vials', '', NULL, NULL, '0', 8, 'active', 100.00, 500.00, '2026-02-27 19:08:00', '2026-02-27 19:08:00', ''),
(226, 'Femiplan pills', 'tabs', '', NULL, NULL, '0', 8, 'active', 105.00, 150.00, '2026-02-27 19:11:22', '2026-02-27 19:11:22', ''),
(227, 'tothema', 'vials', '', NULL, NULL, '0', 15, 'active', 900.00, 200.00, '2026-02-28 16:11:17', '2026-02-28 16:11:17', ''),
(228, 'vit A', 'mls', '', NULL, NULL, '0', 500, 'active', 10.00, 50.00, '2026-02-28 16:15:39', '2026-02-28 16:15:39', ''),
(229, 'cofzit', 'syrup', '', NULL, NULL, '0', 3, 'active', 50.00, 150.00, '2026-02-28 18:02:05', '2026-02-28 18:02:05', ''),
(230, 'pethidine', 'vials', '', NULL, NULL, '0', 14, 'active', 800.00, 800.00, '2026-03-02 08:32:48', '2026-03-02 08:32:48', ''),
(231, 'pethidine', '10', '', NULL, NULL, '0', 9, 'active', 800.00, 1500.00, '2026-03-02 08:33:41', '2026-03-02 08:33:41', ''),
(232, 'iv paracetamol 2', 'pcs', '', NULL, NULL, '0', 5, 'active', 100.00, 600.00, '2026-03-02 08:34:56', '2026-03-02 08:34:56', ''),
(233, 'Tramadol 50mg ( INJ)', 'vials', '', NULL, NULL, '0', 5, 'active', 400.00, 500.00, '2026-03-02 08:36:06', '2026-03-02 08:36:06', ''),
(234, 'pethidine', '10', '', NULL, NULL, '0', 9, 'active', 800.00, 1500.00, '2026-03-02 08:54:36', '2026-03-02 08:54:36', ''),
(235, 'sickoff', 'pcs', '', NULL, NULL, '0', 4, 'active', 10.00, 300.00, '2026-03-02 11:08:13', '2026-03-02 11:08:13', ''),
(236, 'normalsaline', 'mls', '', NULL, NULL, '0', 20, 'active', 200.00, 500.00, '2026-03-03 06:03:30', '2026-03-03 06:03:30', ''),
(237, 'Ringers lactate 500mls', 'mls', '', NULL, NULL, '0', 5, 'active', 200.00, 500.00, '2026-03-03 06:03:59', '2026-03-03 06:03:59', ''),
(238, 'Paracetamol infusion 100mls', 'mls', '', NULL, NULL, '0', 15, 'active', 300.00, 400.00, '2026-03-03 06:05:54', '2026-03-03 06:05:54', ''),
(239, 'nebulization', 'pcs', '', NULL, NULL, '0', 6, 'active', 100.00, 500.00, '2026-03-03 06:32:19', '2026-03-03 06:32:19', ''),
(240, 'immunizatinon', 'no', '', NULL, NULL, '0', 9999, 'active', 100.00, 200.00, '2026-03-03 06:57:38', '2026-03-03 06:57:38', ''),
(241, 'dressing', 'pcs', '', NULL, NULL, '0', 4, 'active', 200.00, 300.00, '2026-03-03 17:59:06', '2026-03-03 17:59:06', ''),
(243, 'zefcolin', 'vials', '', NULL, NULL, '0', 2, 'active', 200.00, 500.00, '2026-03-05 11:57:49', '2026-03-05 11:57:49', ''),
(244, 'pitc', 'pcs', '', NULL, NULL, '0', 9, 'active', 50.00, 200.00, '2026-03-05 13:18:53', '2026-03-05 13:18:53', ''),
(245, 'piriton', 'syrup', '', NULL, NULL, '0', 3, 'active', 30.00, 100.00, '2026-03-05 15:26:37', '2026-03-05 15:26:37', ''),
(246, 'piriton', 'syrup', '', NULL, NULL, '0', 5, 'active', 2.00, 200.00, '2026-03-05 15:27:31', '2026-03-05 15:27:31', ''),
(247, 'ht/wt', 'pcs', '', NULL, NULL, '0', 8, 'active', 0.00, 50.00, '2026-03-05 15:41:47', '2026-03-05 15:41:47', ''),
(248, 'misoprostol[ generic]', '20', '', '2028-01-02', NULL, '0', 20, 'active', 200.00, 1000.00, '2026-03-08 05:55:47', '2026-03-08 05:55:47', 'Generic Supplier'),
(249, 'misoprostol[ original]', '30', '', '2030-02-05', NULL, '0', 130, 'active', 200.00, 1000.00, '2026-03-08 06:04:52', '2026-03-08 06:04:52', 'Generic Supplier'),
(250, 'misoprostol[ generic] 2', 'vials', '', '2030-01-02', NULL, '0', 20, 'active', 150.00, 500.00, '2026-03-08 06:07:24', '2026-03-08 06:07:24', 'Generic Supplier'),
(251, 'Acyclovir', '3', '', '2028-02-05', NULL, '0', 0, 'active', 100.00, 300.00, '2026-03-08 13:25:24', '2026-03-08 13:25:24', 'Generic Supplier'),
(252, 'ACYCLOVIR 400MG TABS', 'tabs', '', '2028-02-03', NULL, '0', 0, 'active', 20.00, 50.00, '2026-03-08 13:27:09', '2026-03-08 13:27:09', 'Generic Supplier'),
(253, 'Gabapentin', 'tabs', '', '2028-01-02', NULL, '0', 1, 'active', 30.00, 50.00, '2026-03-08 13:28:16', '2026-03-08 13:28:16', 'Generic Supplier'),
(254, 'corncaps', 'pcs', '', '2030-12-31', NULL, '0', 10, 'active', 100.00, 200.00, '2026-03-09 08:03:44', '2026-03-09 08:03:44', 'Generic Supplier'),
(255, 'Gentamycin inj', 'vials', '', '2028-01-02', NULL, '0', 5, 'active', 100.00, 350.00, '2026-03-10 14:35:48', '2026-03-10 14:35:48', 'Generic Supplier'),
(256, 'cefbactum', 'vials', '', '2028-02-04', NULL, '0', 1, 'active', 120.00, 500.00, '2026-03-10 14:41:12', '2026-03-10 14:41:12', 'Generic Supplier'),
(257, 'vit  A 100000mu', 'tabs', '', '2027-01-02', NULL, '0', 100, 'active', 10.00, 50.00, '2026-03-10 15:09:37', '2026-03-10 15:09:37', 'Generic Supplier'),
(258, 'vit A 200000MU', 'tabs', '', '2027-12-03', NULL, '0', 1000, 'active', 10.00, 50.00, '2026-03-10 15:10:40', '2026-03-10 15:10:40', 'Generic Supplier'),
(259, 'Cefuroxime', 'mls', '', '2018-12-22', NULL, '0', 1, 'active', 150.00, 500.00, '2026-03-10 15:55:19', '2026-03-10 15:55:19', 'Generic Supplier'),
(260, 'Cotrimoxazole  60mls', 'syrup', '', '2028-12-31', NULL, '0', 1, 'active', 50.00, 150.00, '2026-03-10 15:57:48', '2026-03-10 15:57:48', 'Generic Supplier'),
(261, 'Cotrimoxazole', 'vials', '', '2028-01-01', NULL, '0', 0, 'active', 70.00, 200.00, '2026-03-10 15:59:33', '2026-03-10 15:59:33', 'Generic Supplier'),
(262, 'Ampiclox  60', 'mls', '', '2027-01-02', NULL, '0', 0, 'active', 50.00, 200.00, '2026-03-10 16:12:59', '2026-03-10 16:12:59', 'Generic Supplier'),
(263, 'Cotrimoxazole 480mg', 'tabs', '', '0027-12-22', NULL, '0', 100, 'active', 15.00, 20.00, '2026-03-10 16:22:54', '2026-03-10 16:22:54', 'Generic Supplier'),
(264, 'Artemether tablets', 'tabs', '', '0028-02-22', NULL, '0', 2, 'active', 5.00, 200.00, '2026-03-10 16:28:56', '2026-03-10 16:28:56', 'Generic Supplier'),
(265, 'Sildenafil', 'tabs', '', '2027-01-02', NULL, '0', 19, 'active', 10.00, 50.00, '2026-03-10 16:34:42', '2026-03-10 16:34:42', 'Generic Supplier'),
(266, 'floxapen 500mg', 'tabs', '', '2028-01-03', NULL, '0', 125, 'active', 10.00, 30.00, '2026-03-10 16:39:36', '2026-03-10 16:39:36', 'Generic Supplier'),
(267, 'liquid paraffin 60mls', 'mls', '', '2028-01-02', NULL, '0', 2, 'active', 50.00, 150.00, '2026-03-10 17:33:22', '2026-03-10 17:33:22', 'Generic Supplier'),
(268, 'X traderm', 'tube', '', '2028-01-02', NULL, '0', 1, 'active', 200.00, 400.00, '2026-03-10 17:45:53', '2026-03-10 17:45:53', 'Generic Supplier'),
(269, 'T.T', 'vials', '', '2028-01-02', NULL, '0', 98, 'active', 20.00, 200.00, '2026-03-10 19:49:59', '2026-03-10 19:49:59', 'Generic Supplier'),
(270, 'Piroxicam 20mg', 'tabs', '', '2030-04-04', NULL, '0', 100, 'active', 5.00, 20.00, '2026-03-10 20:28:49', '2026-03-10 20:28:49', 'Generic Supplier'),
(271, 'Ibucap forte', 'tabs', '', '3029-07-04', NULL, '0', 2, 'active', 5.00, 20.00, '2026-03-10 20:30:38', '2026-03-10 20:30:38', 'Generic Supplier'),
(272, 'Montene 10mg', 'tabs', '', '2031-07-04', NULL, '0', 10, 'active', 10.00, 30.00, '2026-03-10 20:32:24', '2026-03-10 20:32:24', 'Generic Supplier'),
(273, 'Metformin 500mg', 'tabs', '', '2032-05-10', NULL, '0', 24, 'active', 3.00, 10.00, '2026-03-10 20:33:35', '2026-03-10 20:33:35', 'Generic Supplier'),
(274, 'Ifas', 'tabs', '', '2032-01-05', NULL, '0', 160, 'active', 2.00, 10.00, '2026-03-10 20:34:47', '2026-03-10 20:34:47', 'Generic Supplier'),
(275, 'Flugone', 'tabs', '', '2032-05-04', NULL, '0', 4, 'active', 5.00, 20.00, '2026-03-10 20:37:27', '2026-03-10 20:37:27', 'Generic Supplier'),
(276, 'Trust classic', 'pcs', '', '0028-12-20', NULL, '0', 11, 'active', 25.00, 50.00, '2026-03-11 03:39:39', '2026-03-11 03:39:39', 'Generic Supplier'),
(277, 'Otorex ear drops', 'pcs', '', '2028-01-02', NULL, '0', 1, 'active', 250.00, 500.00, '2026-03-11 04:06:27', '2026-03-11 04:06:27', 'Generic Supplier'),
(278, 'glycerine supp', 'pcs', '', '2028-01-01', NULL, '0', 2, 'active', 50.00, 100.00, '2026-03-11 04:07:31', '2026-03-11 04:07:31', 'Generic Supplier'),
(279, 'normil  tabs', 'tabs', '', '2028-01-11', NULL, '0', 2, 'active', 10.00, 50.00, '2026-03-11 04:08:28', '2026-03-11 04:08:28', 'Generic Supplier'),
(280, 'Nilworm tabs', '1', '', '2028-01-01', NULL, '0', 7, 'active', 10.00, 50.00, '2026-03-11 04:09:56', '2026-03-11 04:09:56', 'Generic Supplier'),
(281, 'allugel', 'syrup', '', '2028-01-02', NULL, '0', 2, 'active', 80.00, 200.00, '2026-03-11 04:11:07', '2026-03-11 04:11:07', 'Generic Supplier'),
(282, 'baby weight', 'pcs', '', '2028-01-03', NULL, '0', 146, 'active', 2.00, 50.00, '2026-03-11 09:19:02', '2026-03-11 09:19:02', 'Generic Supplier'),
(283, 'Bonjela teething gel', '1', '', '2028-12-31', NULL, '0', 0, 'active', 800.00, 1500.00, '2026-03-14 16:38:23', '2026-03-14 16:38:23', 'BIbo pharmaceuticals'),
(284, 'plasil inj', 'vials', '', '2027-01-02', NULL, '0', 25, 'active', 10.00, 400.00, '2026-03-19 07:35:38', '2026-03-19 07:35:38', 'Generic Supplier'),
(285, 'Cybro B 200mls', 'syrup', '', '2027-01-31', NULL, '0', 7, 'active', 270.00, 700.00, '2026-03-19 17:55:14', '2026-03-19 17:55:14', 'Generic Supplier'),
(286, 'ceftaxidine', 'vials', '', '2028-03-18', NULL, '0', 8, 'active', 140.00, 500.00, '2026-03-19 17:59:10', '2026-03-19 17:59:10', 'Generic Supplier'),
(287, 'Indomethacin', 'tablets', '', '2028-03-18', NULL, '0', 99, 'active', 1.00, 10.00, '2026-03-19 18:00:38', '2026-03-19 18:00:38', 'Generic Supplier'),
(289, 'nexium', 'tabs', '', '2027-02-22', NULL, '2', 150, 'active', 25.00, 40.00, '2026-03-22 18:03:01', '2026-03-22 18:03:01', 'Pefric'),
(290, 'Helicos kit', 'tabs', '', '2027-11-11', NULL, '2', 5, 'active', 1200.00, 2500.00, '2026-03-22 18:05:57', '2026-03-22 18:05:57', 'Pefric'),
(291, 'Acetal mr', 'tabs', '', '2028-01-01', NULL, '2', 100, 'active', 0.00, 500.00, '2026-03-23 12:44:53', '2026-03-23 12:44:53', 'Pefric'),
(292, 'omeprazole tabs', 'tabs', '', '2028-01-02', NULL, '0', 97, 'active', 2.00, 10.00, '2026-03-25 17:13:31', '2026-03-25 17:13:31', 'Generic Supplier'),
(293, 'lasix', 'vials', '', '2028-01-05', NULL, '0', 10, 'active', 100.00, 800.00, '2026-03-31 21:46:15', '2026-03-31 21:46:15', 'Ludende pharmaceuticals'),
(294, 'lasix tabs', 'tabs', '', '2028-01-05', NULL, '0', 10, 'active', 10.00, 10.00, '2026-03-31 21:48:54', '2026-03-31 21:48:54', 'Generic Supplier'),
(295, 'amityphline', '20', '1', '2028-01-03', NULL, '0', 11, 'active', 30.00, 50.00, '2026-04-04 07:38:45', '2026-04-04 07:38:45', 'Generic Supplier'),
(296, 'catoxymag 200ml', 'syrup', '', '2028-01-05', NULL, '1', 1, 'active', 235.00, 700.00, '2026-04-07 19:07:51', '2026-04-07 19:07:51', 'BIbo pharmaceuticals'),
(297, 'Diracip m sus 100ml', 'syrup', '', '2029-01-02', NULL, '2', 1, 'active', 260.00, 550.00, '2026-04-07 19:10:13', '2026-04-07 19:10:13', 'BIbo pharmaceuticals'),
(298, 'Entamaxin tabs', 'tabs', '', '2028-01-04', NULL, '1', 0, 'active', 8.00, 20.00, '2026-04-07 19:12:43', '2026-04-07 19:12:43', 'BIbo pharmaceuticals'),
(299, 'Gacet 250mg', 'pcs', '', '2029-02-05', NULL, '2', 10, 'active', 18.00, 200.00, '2026-04-07 19:14:51', '2026-04-07 19:14:51', 'BIbo pharmaceuticals'),
(300, 'tetrcycline skin oint', 'pcs', '', '2029-02-05', NULL, '1', 3, 'active', 25.00, 3.00, '2026-04-07 19:19:28', '2026-04-07 19:19:28', 'BIbo pharmaceuticals'),
(301, 'ANUSOL CREAM', 'vials', '', '2028-01-03', NULL, '1', 1, 'active', 400.00, 850.00, '2026-04-09 10:04:47', '2026-04-09 10:04:47', 'Pefric (E.A) Ltd'),
(302, 'DAFLON', 'tabs', '', '2028-03-03', NULL, '1', 30, 'active', 50.00, 100.00, '2026-04-09 10:06:08', '2026-04-09 10:06:08', 'Pefric (E.A) Ltd'),
(303, 'norma saline 1', 'mls', '', '2028-12-03', NULL, '0', 1, 'active', 1000.00, 1000.00, '2026-04-26 13:30:23', '2026-04-26 13:30:23', 'BIbo pharmaceuticals'),
(304, 'Ondasentron 1', 'vials', '', '2028-12-02', NULL, '0', 1, 'active', 100.00, 800.00, '2026-04-26 13:32:01', '2026-04-26 13:32:01', 'BIbo pharmaceuticals'),
(305, 'Esomeprazole 40mg ( inj)', 'vials', '', '2028-12-02', NULL, '0', 14, 'active', 100.00, 800.00, '2026-04-26 13:34:24', '2026-04-26 13:34:24', 'Generic Supplier'),
(306, 'canula', 'pc', '', '2030-01-02', NULL, '0', 100, 'active', 25.00, 100.00, '2026-08-06 07:49:05', '2026-08-06 07:49:05', 'Generic Supplier'),
(307, 'giving set', 'pcs', '', '2039-12-31', NULL, '0', 1, 'active', 25.00, 100.00, '2026-08-06 07:50:00', '2026-08-06 07:50:00', 'BIbo pharmaceuticals'),
(308, 'depo,vit B', 'vials', '', '2030-01-03', NULL, '0', 10, 'active', 100.00, 300.00, '2026-08-27 07:36:15', '2026-08-27 07:36:15', 'BIbo pharmaceuticals');

-- --------------------------------------------------------

--
-- Table structure for table `pma__bookmark`
--

CREATE TABLE `pma__bookmark` (
  `id` int(10) UNSIGNED NOT NULL,
  `dbase` varchar(255) NOT NULL DEFAULT '',
  `user` varchar(255) NOT NULL DEFAULT '',
  `label` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `query` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Bookmarks';

-- --------------------------------------------------------

--
-- Table structure for table `pma__central_columns`
--

CREATE TABLE `pma__central_columns` (
  `db_name` varchar(64) NOT NULL,
  `col_name` varchar(64) NOT NULL,
  `col_type` varchar(64) NOT NULL,
  `col_length` text DEFAULT NULL,
  `col_collation` varchar(64) NOT NULL,
  `col_isNull` tinyint(1) NOT NULL,
  `col_extra` varchar(255) DEFAULT '',
  `col_default` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Central list of columns';

-- --------------------------------------------------------

--
-- Table structure for table `pma__column_info`
--

CREATE TABLE `pma__column_info` (
  `id` int(5) UNSIGNED NOT NULL,
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `column_name` varchar(64) NOT NULL DEFAULT '',
  `comment` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `mimetype` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `transformation` varchar(255) NOT NULL DEFAULT '',
  `transformation_options` varchar(255) NOT NULL DEFAULT '',
  `input_transformation` varchar(255) NOT NULL DEFAULT '',
  `input_transformation_options` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Column information for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__designer_settings`
--

CREATE TABLE `pma__designer_settings` (
  `username` varchar(64) NOT NULL,
  `settings_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Settings related to Designer';

-- --------------------------------------------------------

--
-- Table structure for table `pma__export_templates`
--

CREATE TABLE `pma__export_templates` (
  `id` int(5) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL,
  `export_type` varchar(10) NOT NULL,
  `template_name` varchar(64) NOT NULL,
  `template_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Saved export templates';

-- --------------------------------------------------------

--
-- Table structure for table `pma__favorite`
--

CREATE TABLE `pma__favorite` (
  `username` varchar(64) NOT NULL,
  `tables` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Favorite tables';

-- --------------------------------------------------------

--
-- Table structure for table `pma__history`
--

CREATE TABLE `pma__history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `db` varchar(64) NOT NULL DEFAULT '',
  `table` varchar(64) NOT NULL DEFAULT '',
  `timevalue` timestamp NOT NULL DEFAULT current_timestamp(),
  `sqlquery` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='SQL history for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__navigationhiding`
--

CREATE TABLE `pma__navigationhiding` (
  `username` varchar(64) NOT NULL,
  `item_name` varchar(64) NOT NULL,
  `item_type` varchar(64) NOT NULL,
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Hidden items of navigation tree';

-- --------------------------------------------------------

--
-- Table structure for table `pma__pdf_pages`
--

CREATE TABLE `pma__pdf_pages` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `page_nr` int(10) UNSIGNED NOT NULL,
  `page_descr` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='PDF relation pages for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__recent`
--

CREATE TABLE `pma__recent` (
  `username` varchar(64) NOT NULL,
  `tables` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Recently accessed tables';

--
-- Dumping data for table `pma__recent`
--

INSERT INTO `pma__recent` (`username`, `tables`) VALUES
('root', '[{\"db\":\"hms_db\",\"table\":\"stock_movements\"},{\"db\":\"hms_db\",\"table\":\"pharmacy_sales\"},{\"db\":\"hms_db\",\"table\":\"lab_tests\"},{\"db\":\"hms_db\",\"table\":\"lab_results\"},{\"db\":\"hms_db\",\"table\":\"vitals\"},{\"db\":\"hms_db\",\"table\":\"patients\"},{\"db\":\"hms_db\",\"table\":\"patient_history\"},{\"db\":\"hms_db\",\"table\":\"invoices\"},{\"db\":\"hms_db\",\"table\":\"pharmacy_stock\"},{\"db\":\"hms_db\",\"table\":\"encounters\"}]');

-- --------------------------------------------------------

--
-- Table structure for table `pma__relation`
--

CREATE TABLE `pma__relation` (
  `master_db` varchar(64) NOT NULL DEFAULT '',
  `master_table` varchar(64) NOT NULL DEFAULT '',
  `master_field` varchar(64) NOT NULL DEFAULT '',
  `foreign_db` varchar(64) NOT NULL DEFAULT '',
  `foreign_table` varchar(64) NOT NULL DEFAULT '',
  `foreign_field` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Relation table';

-- --------------------------------------------------------

--
-- Table structure for table `pma__savedsearches`
--

CREATE TABLE `pma__savedsearches` (
  `id` int(5) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `search_name` varchar(64) NOT NULL DEFAULT '',
  `search_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Saved searches';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_coords`
--

CREATE TABLE `pma__table_coords` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `pdf_page_number` int(11) NOT NULL DEFAULT 0,
  `x` float UNSIGNED NOT NULL DEFAULT 0,
  `y` float UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Table coordinates for phpMyAdmin PDF output';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_info`
--

CREATE TABLE `pma__table_info` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `display_field` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Table information for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_uiprefs`
--

CREATE TABLE `pma__table_uiprefs` (
  `username` varchar(64) NOT NULL,
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `prefs` text NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Tables'' UI preferences';

-- --------------------------------------------------------

--
-- Table structure for table `pma__tracking`
--

CREATE TABLE `pma__tracking` (
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `version` int(10) UNSIGNED NOT NULL,
  `date_created` datetime NOT NULL,
  `date_updated` datetime NOT NULL,
  `schema_snapshot` text NOT NULL,
  `schema_sql` text DEFAULT NULL,
  `data_sql` longtext DEFAULT NULL,
  `tracking` set('UPDATE','REPLACE','INSERT','DELETE','TRUNCATE','CREATE DATABASE','ALTER DATABASE','DROP DATABASE','CREATE TABLE','ALTER TABLE','RENAME TABLE','DROP TABLE','CREATE INDEX','DROP INDEX','CREATE VIEW','ALTER VIEW','DROP VIEW') DEFAULT NULL,
  `tracking_active` int(1) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Database changes tracking for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__userconfig`
--

CREATE TABLE `pma__userconfig` (
  `username` varchar(64) NOT NULL,
  `timevalue` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `config_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='User preferences storage for phpMyAdmin';

--
-- Dumping data for table `pma__userconfig`
--

INSERT INTO `pma__userconfig` (`username`, `timevalue`, `config_data`) VALUES
('root', '2026-01-11 07:17:23', '{\"Console\\/Mode\":\"collapse\"}');

-- --------------------------------------------------------

--
-- Table structure for table `pma__usergroups`
--

CREATE TABLE `pma__usergroups` (
  `usergroup` varchar(64) NOT NULL,
  `tab` varchar(64) NOT NULL,
  `allowed` enum('Y','N') NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='User groups with configured menu items';

-- --------------------------------------------------------

--
-- Table structure for table `pma__users`
--

CREATE TABLE `pma__users` (
  `username` varchar(64) NOT NULL,
  `usergroup` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Users and their assignments to user groups';

-- --------------------------------------------------------

--
-- Table structure for table `preauthorizations`
--

CREATE TABLE `preauthorizations` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `patient_coverage_id` int(11) NOT NULL,
  `payer_id` int(11) NOT NULL,
  `plan_id` int(11) DEFAULT NULL,
  `request_number` varchar(100) NOT NULL,
  `authorization_number` varchar(100) DEFAULT NULL,
  `requested_service_type` enum('Consultation','Service','Lab','Radiology','Procedure','Pharmacy','Admission','Other') NOT NULL,
  `requested_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `approved_amount` decimal(12,2) DEFAULT NULL,
  `status` enum('Draft','Submitted','Approved','Partially Approved','Rejected','Expired') NOT NULL DEFAULT 'Draft',
  `valid_from` datetime DEFAULT NULL,
  `valid_to` datetime DEFAULT NULL,
  `clinical_notes` text DEFAULT NULL,
  `payer_notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `id` int(11) NOT NULL,
  `encounter_id` int(11) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `drug_name` varchar(255) DEFAULT NULL,
  `dosage` varchar(100) DEFAULT NULL,
  `frequency` varchar(100) DEFAULT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `prescribed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `medicine_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `prescriptions`
--

INSERT INTO `prescriptions` (`id`, `encounter_id`, `patient_id`, `drug_name`, `dosage`, `frequency`, `duration`, `quantity`, `invoice_id`, `prescribed_by`, `created_at`, `medicine_id`) VALUES
(1, 0, 13, 'Allucid 100mls', NULL, NULL, NULL, 1, 0, 0, '2026-02-05 12:39:53', NULL),
(2, 0, 15, 'Amoxicilin 250mg', NULL, NULL, NULL, 1, 0, 0, '2026-02-05 12:40:04', NULL),
(3, 0, 16, 'Amoxicilin 250mg', NULL, NULL, NULL, 1, 0, 0, '2026-02-05 12:40:08', NULL),
(4, 0, 17, 'Amoxicilin 250mg', NULL, NULL, NULL, 1, 0, 0, '2026-02-05 12:40:33', NULL),
(5, 0, 18, 'Amoxiclav 228mg', NULL, NULL, NULL, 1, 0, 0, '2026-02-05 12:53:59', NULL),
(6, 0, 1, 'Anti D', NULL, NULL, NULL, 1, 0, 0, '2026-02-06 09:29:57', NULL),
(7, 0, 1, 'Amoxiclav 156mg', NULL, NULL, NULL, 1, 0, 0, '2026-02-06 09:40:47', NULL),
(8, 0, 1, 'Acetal mr', NULL, NULL, NULL, 1, 0, 0, '2026-02-06 09:40:47', NULL),
(9, 0, 1, 'Amoxiclav 156mg', NULL, NULL, NULL, 1, 0, 0, '2026-02-06 09:44:16', NULL),
(10, 0, 1, 'Acetal mr', NULL, NULL, NULL, 1, 0, 0, '2026-02-06 09:44:16', NULL),
(11, 0, 1, 'Amoxiclav 156mg', NULL, NULL, NULL, 1, 0, 0, '2026-02-06 09:51:27', NULL),
(12, 0, 1, 'Acetal mr', NULL, NULL, NULL, 1, 0, 0, '2026-02-06 09:51:27', NULL),
(13, 0, 1, 'Amoxiclav 156mg', NULL, NULL, NULL, 1, 0, 0, '2026-02-06 09:51:30', NULL),
(14, 0, 1, 'Acetal mr', NULL, NULL, NULL, 1, 0, 0, '2026-02-06 09:51:30', NULL),
(15, 0, 1, 'Amoxiclav 156mg', NULL, NULL, NULL, 1, 0, 0, '2026-02-06 09:51:32', NULL),
(16, 0, 1, 'Acetal mr', NULL, NULL, NULL, 1, 0, 0, '2026-02-06 09:51:32', NULL),
(17, NULL, 8, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-02-23 16:39:08', 102),
(18, NULL, 8, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-02-24 13:02:37', 98),
(19, NULL, 9, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-02-24 13:53:18', 39),
(20, NULL, 19, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-02-25 15:33:37', 51),
(28, NULL, 20, NULL, NULL, NULL, NULL, 15, NULL, NULL, '2026-02-26 07:56:34', 81),
(29, NULL, 20, NULL, NULL, NULL, NULL, 5, NULL, NULL, '2026-02-26 07:57:18', 6),
(30, NULL, 20, NULL, NULL, NULL, NULL, 4, NULL, NULL, '2026-02-26 07:58:15', 82),
(31, NULL, 20, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-02-26 07:58:43', 37),
(32, NULL, 20, NULL, NULL, NULL, NULL, 10, NULL, NULL, '2026-02-26 07:59:08', 100),
(33, NULL, 21, NULL, NULL, NULL, NULL, 5, NULL, NULL, '2026-02-26 08:34:05', 6),
(34, NULL, 21, NULL, NULL, NULL, NULL, 4, NULL, NULL, '2026-02-26 08:35:05', 82),
(35, NULL, 21, NULL, NULL, NULL, NULL, 15, NULL, NULL, '2026-02-26 08:35:22', 81),
(36, NULL, 21, NULL, NULL, NULL, NULL, 10, NULL, NULL, '2026-02-26 08:35:38', 100),
(37, NULL, 21, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-02-26 08:36:05', 37),
(38, NULL, 28, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-02-27 05:59:55', 31),
(39, NULL, 28, NULL, NULL, NULL, NULL, 2, NULL, NULL, '2026-02-27 06:43:45', 93),
(40, NULL, 28, NULL, NULL, NULL, NULL, 3, NULL, NULL, '2026-02-27 06:44:14', 6),
(41, NULL, 28, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-02-27 06:45:06', 98),
(43, NULL, 28, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-02-27 06:47:03', 7),
(44, NULL, 28, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-02-27 06:47:31', 14),
(45, NULL, 30, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-02-27 19:08:29', 225),
(46, NULL, 30, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-02-27 19:13:20', 226),
(47, NULL, 31, NULL, NULL, NULL, NULL, 5, NULL, NULL, '2026-02-28 16:11:39', 227),
(48, NULL, 32, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-02-28 16:16:00', 228),
(49, NULL, 32, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-02-28 16:16:20', 61),
(50, NULL, 33, NULL, NULL, NULL, NULL, 5, NULL, NULL, '2026-02-28 16:51:06', 6),
(51, NULL, 33, NULL, NULL, NULL, NULL, 5, NULL, NULL, '2026-02-28 16:51:41', 82),
(52, NULL, 34, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-02-28 17:20:33', 39),
(53, NULL, 34, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-02-28 17:21:04', 14),
(54, NULL, 34, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-02-28 17:21:18', 189),
(56, NULL, 36, NULL, NULL, NULL, NULL, 3, NULL, NULL, '2026-03-01 15:54:34', 6),
(57, NULL, 36, NULL, NULL, NULL, NULL, 15, NULL, NULL, '2026-03-01 15:55:24', 19),
(59, NULL, 36, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-03-01 15:57:55', 82),
(60, NULL, 36, NULL, NULL, NULL, NULL, 15, NULL, NULL, '2026-03-01 16:12:39', 81),
(61, NULL, 37, NULL, NULL, NULL, NULL, 3, NULL, NULL, '2026-03-02 08:37:06', 230),
(62, NULL, 37, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-03-02 08:37:23', 201),
(63, NULL, 37, NULL, NULL, NULL, NULL, 2, NULL, NULL, '2026-03-02 10:52:37', 230),
(64, NULL, 37, NULL, NULL, NULL, NULL, 2, NULL, NULL, '2026-03-02 10:58:23', 230),
(65, NULL, 39, NULL, NULL, NULL, NULL, 4, NULL, NULL, '2026-03-02 18:15:17', 6),
(72, NULL, 39, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-03-02 18:49:13', 172),
(73, NULL, 39, NULL, NULL, NULL, NULL, 10, NULL, NULL, '2026-03-02 18:50:02', 19),
(74, NULL, 39, NULL, NULL, NULL, NULL, 5, NULL, NULL, '2026-03-02 18:51:29', 188),
(75, NULL, 37, NULL, NULL, NULL, NULL, 6, NULL, NULL, '2026-03-03 06:02:10', 230),
(76, NULL, 42, NULL, NULL, NULL, NULL, 5, NULL, NULL, '2026-03-03 11:39:44', 6),
(77, NULL, 42, NULL, NULL, NULL, NULL, 2, NULL, NULL, '2026-03-03 11:40:08', 82),
(78, NULL, 42, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-03-03 11:40:33', 78),
(80, NULL, 42, NULL, NULL, NULL, NULL, 20, NULL, NULL, '2026-03-03 11:41:27', 86),
(81, NULL, 42, NULL, NULL, NULL, NULL, 10, NULL, NULL, '2026-03-03 11:42:15', 186),
(82, NULL, 42, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-03-03 11:42:58', 140),
(83, NULL, 44, NULL, NULL, NULL, NULL, 4, NULL, NULL, '2026-03-03 17:56:00', 6),
(84, NULL, 44, NULL, NULL, NULL, NULL, 3, NULL, NULL, '2026-03-03 17:56:20', 82),
(85, NULL, 44, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-03-03 17:57:18', 147),
(86, NULL, 44, NULL, NULL, NULL, NULL, 10, NULL, NULL, '2026-03-03 17:57:38', 100),
(87, NULL, 44, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-03-03 17:59:23', 241),
(88, NULL, 45, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-03-04 10:21:25', 93),
(89, NULL, 45, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-03-04 10:21:59', 6),
(90, NULL, 45, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-03-04 10:22:40', 70),
(92, NULL, 45, NULL, NULL, NULL, NULL, 10, NULL, NULL, '2026-03-04 10:40:41', 60),
(93, NULL, 45, NULL, NULL, NULL, NULL, 10, NULL, NULL, '2026-03-04 10:41:12', 44),
(94, NULL, 45, NULL, NULL, NULL, NULL, 10, NULL, NULL, '2026-03-04 10:41:48', 10),
(95, NULL, 46, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-03-04 13:38:13', 52),
(96, NULL, 46, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-03-04 13:38:51', 163),
(97, NULL, 46, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-03-04 13:39:14', 24),
(98, NULL, 46, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-03-04 13:40:23', 33),
(99, NULL, 47, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-03-05 06:09:34', 31),
(104, NULL, 47, NULL, NULL, NULL, NULL, 3, NULL, NULL, '2026-03-05 06:28:05', 6),
(105, NULL, 47, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-03-05 06:28:54', 70),
(106, NULL, 47, NULL, NULL, NULL, NULL, 15, NULL, NULL, '2026-03-05 06:29:31', 19),
(107, NULL, 47, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-03-05 07:26:20', 82),
(108, NULL, 47, NULL, NULL, NULL, NULL, 5, NULL, NULL, '2026-03-05 07:27:00', 10),
(109, NULL, 48, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-03-05 11:23:41', 79),
(111, NULL, 48, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-03-05 11:25:03', 93),
(112, NULL, 48, NULL, NULL, NULL, NULL, 10, NULL, NULL, '2026-03-05 11:42:48', 242),
(113, NULL, 49, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-03-05 11:56:01', 46),
(114, NULL, 49, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-03-05 11:56:22', 93),
(115, NULL, 49, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-03-05 11:58:37', 243),
(116, NULL, 49, NULL, NULL, NULL, NULL, 15, NULL, NULL, '2026-03-05 11:59:19', 207),
(117, NULL, 49, NULL, NULL, NULL, NULL, 5, NULL, NULL, '2026-03-05 12:00:01', 10),
(118, NULL, 49, NULL, NULL, NULL, NULL, 10, NULL, NULL, '2026-03-05 12:01:18', 19),
(119, NULL, 50, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-03-05 12:57:05', 70),
(120, NULL, 50, NULL, NULL, NULL, NULL, 1, NULL, NULL, '2026-03-05 13:00:27', 23),
(121, NULL, 50, NULL, NULL, NULL, NULL, 20, NULL, NULL, '2026-03-05 13:05:20', 86),
(122, NULL, 48, NULL, NULL, '', NULL, 2, 500, NULL, '2026-03-07 09:56:57', 46),
(123, NULL, 48, NULL, NULL, '', NULL, 1, 500, NULL, '2026-03-07 09:57:50', 201),
(127, NULL, 51, NULL, NULL, '', NULL, 2, 500, NULL, '2026-03-07 10:04:07', 46),
(128, NULL, 51, NULL, NULL, '', NULL, 1, 400, NULL, '2026-03-07 10:04:30', 93),
(129, NULL, 51, NULL, NULL, '', NULL, 1, 500, NULL, '2026-03-07 10:04:48', 233),
(130, NULL, 51, NULL, NULL, '', NULL, 30, 27, NULL, '2026-03-07 10:05:22', 137),
(131, NULL, 51, NULL, NULL, '', NULL, 1, 150, NULL, '2026-03-07 10:05:49', 28),
(132, NULL, 51, NULL, NULL, '', NULL, 1, 800, NULL, '2026-03-07 10:20:05', 135),
(133, NULL, 51, NULL, NULL, '', NULL, 1, 300, NULL, '2026-03-07 11:18:14', 70),
(134, NULL, 52, NULL, NULL, '', NULL, 4, 400, NULL, '2026-03-07 15:54:42', 6),
(135, NULL, 52, NULL, NULL, '', NULL, 2, 500, NULL, '2026-03-07 15:55:41', 82),
(136, NULL, 52, NULL, NULL, '', NULL, 1, 300, NULL, '2026-03-07 15:56:05', 70),
(138, NULL, 53, NULL, NULL, '', NULL, 8, 1000, NULL, '2026-03-08 06:05:37', 249),
(139, NULL, 53, NULL, NULL, '', NULL, 5, 500, NULL, '2026-03-08 06:08:20', 250),
(140, NULL, 53, NULL, NULL, '', NULL, 2, 600, NULL, '2026-03-08 06:10:59', 232),
(141, NULL, 53, NULL, NULL, '', NULL, 1, 500, NULL, '2026-03-08 06:11:47', 82),
(142, NULL, 53, NULL, NULL, '', NULL, 2, 800, NULL, '2026-03-08 06:12:11', 135),
(143, NULL, 53, NULL, NULL, '', NULL, 2, 800, NULL, '2026-03-08 06:12:56', 236),
(144, NULL, 53, NULL, NULL, '', NULL, 1, 500, NULL, '2026-03-08 06:13:39', 46),
(145, NULL, 53, NULL, NULL, '', NULL, 1, 400, NULL, '2026-03-08 06:14:36', 220),
(146, NULL, 53, NULL, NULL, '', NULL, 1, 400, NULL, '2026-03-08 07:35:24', 220),
(147, NULL, 54, NULL, NULL, '', NULL, 1, 500, NULL, '2026-03-08 11:12:21', 46),
(148, NULL, 54, NULL, NULL, '', NULL, 1, 400, NULL, '2026-03-08 11:12:42', 93),
(149, NULL, 54, NULL, NULL, '', NULL, 10, 10, NULL, '2026-03-08 11:13:16', 60),
(150, NULL, 54, NULL, NULL, '', NULL, 20, 5, NULL, '2026-03-08 11:15:18', 86),
(151, NULL, 54, NULL, NULL, '', NULL, 1, 800, NULL, '2026-03-08 11:16:15', 37),
(152, NULL, 54, NULL, NULL, '', NULL, 1, 400, NULL, '2026-03-08 11:18:26', 220),
(153, NULL, 55, NULL, NULL, '', NULL, 30, 50, NULL, '2026-03-08 13:30:53', 252),
(155, NULL, 55, NULL, NULL, '', NULL, 30, 50, NULL, '2026-03-08 13:32:18', 253),
(156, NULL, 55, NULL, NULL, '', NULL, 1, 1500, NULL, '2026-03-08 13:32:45', 147),
(157, NULL, 56, NULL, NULL, '', NULL, 10, 30, NULL, '2026-03-08 18:12:41', 106),
(159, NULL, 56, NULL, NULL, '', NULL, 1, 100, NULL, '2026-03-08 18:12:54', 61),
(160, NULL, 56, NULL, NULL, '', NULL, 10, 5, NULL, '2026-03-08 18:13:42', 86),
(161, NULL, 56, NULL, NULL, '', NULL, 20, 15, NULL, '2026-03-08 18:14:51', 81),
(162, NULL, 57, NULL, NULL, '', NULL, 1, 300, NULL, '2026-03-08 20:06:54', 70),
(163, NULL, 57, NULL, NULL, '', NULL, 20, 5, NULL, '2026-03-08 20:07:33', 86),
(164, NULL, 57, NULL, NULL, '', NULL, 1, 150, NULL, '2026-03-08 20:08:06', 28),
(165, NULL, 58, NULL, NULL, '', NULL, 5, 400, NULL, '2026-03-09 01:14:53', 6),
(166, NULL, 58, NULL, NULL, '', NULL, 2, 500, NULL, '2026-03-09 01:15:29', 82),
(167, NULL, 58, NULL, NULL, '', NULL, 1, 300, NULL, '2026-03-09 01:15:44', 70),
(168, NULL, 58, NULL, NULL, '', NULL, 20, 5, NULL, '2026-03-09 01:16:04', 86),
(169, NULL, 59, NULL, NULL, '', NULL, 5, 200, NULL, '2026-03-09 08:04:51', 254),
(170, NULL, 59, NULL, NULL, '', NULL, 3, 400, NULL, '2026-03-09 08:05:14', 6),
(171, NULL, 55, NULL, NULL, '', NULL, 1, 500, NULL, '2026-03-10 08:51:20', 201),
(172, NULL, 55, NULL, NULL, '', NULL, 1, 600, NULL, '2026-03-10 08:51:34', 232),
(173, NULL, 55, NULL, NULL, '', NULL, 2, 350, NULL, '2026-03-10 08:51:49', 91),
(174, NULL, 55, NULL, NULL, '', NULL, 1, 100, NULL, '2026-03-10 08:52:06', 245),
(175, NULL, 55, NULL, NULL, '', NULL, 10, 50, NULL, '2026-03-10 12:47:46', 128),
(176, NULL, 55, NULL, NULL, '', NULL, 1, 200, NULL, '2026-03-10 13:44:35', 78),
(178, NULL, 60, NULL, NULL, '', NULL, 1, 350, NULL, '2026-03-10 18:20:07', 91),
(184, NULL, 60, NULL, NULL, '', NULL, 3, 400, NULL, '2026-03-10 18:24:44', 6),
(185, NULL, 60, NULL, NULL, '', NULL, 6, 10, NULL, '2026-03-10 18:26:07', 60),
(188, NULL, 61, NULL, NULL, '', NULL, 1, 400, NULL, '2026-03-11 07:40:03', 93),
(189, NULL, 61, NULL, NULL, '', NULL, 1, 400, NULL, '2026-03-11 07:40:43', 238),
(190, NULL, 61, NULL, NULL, '', NULL, 1, 800, NULL, '2026-03-11 07:41:13', 67),
(191, NULL, 61, NULL, NULL, '', NULL, 3, 400, NULL, '2026-03-11 09:12:23', 6),
(192, NULL, 61, NULL, NULL, '', NULL, 1, 50, NULL, '2026-03-11 09:20:52', 282),
(193, NULL, 61, NULL, NULL, '', NULL, 1, 450, NULL, '2026-03-11 09:25:35', 14),
(194, NULL, 61, NULL, NULL, '', NULL, 1, 450, NULL, '2026-03-11 09:25:35', 14),
(195, NULL, 62, NULL, NULL, '', NULL, 3, 500, NULL, '2026-03-12 06:25:03', 201),
(196, NULL, 62, NULL, NULL, '', NULL, 5, 400, NULL, '2026-03-12 06:25:53', 238),
(197, NULL, 62, NULL, NULL, '', NULL, 10, 400, NULL, '2026-03-12 06:26:29', 220),
(198, NULL, 63, NULL, NULL, '', NULL, 10, 500, NULL, '2026-03-12 08:45:31', 256),
(199, NULL, 63, NULL, NULL, '', NULL, 4, 600, NULL, '2026-03-12 08:46:12', 31),
(200, NULL, 63, NULL, NULL, '', NULL, 5, 500, NULL, '2026-03-12 08:47:19', 82),
(201, NULL, 63, NULL, NULL, '', NULL, 1, 1000, NULL, '2026-03-12 08:47:33', 37),
(203, NULL, 63, NULL, NULL, '', NULL, 4, 500, NULL, '2026-03-12 08:48:47', 172),
(204, NULL, 64, NULL, NULL, '', NULL, 10, 20, NULL, '2026-03-12 12:38:42', 186),
(205, NULL, 64, NULL, NULL, '', NULL, 15, 15, NULL, '2026-03-12 12:39:22', 81),
(206, NULL, 65, NULL, NULL, '', NULL, 2, 350, NULL, '2026-03-12 12:56:44', 42),
(208, NULL, 65, NULL, NULL, '', NULL, 1, 600, NULL, '2026-03-12 12:57:44', 31),
(209, NULL, 66, NULL, NULL, '', NULL, 1, 400, NULL, '2026-03-12 15:32:46', 93),
(210, NULL, 66, NULL, NULL, '', NULL, 1, 300, NULL, '2026-03-12 15:33:16', 21),
(211, NULL, 66, NULL, NULL, '', NULL, 1, 500, NULL, '2026-03-12 15:33:32', 259),
(212, NULL, 62, NULL, NULL, '', NULL, 1, 400, NULL, '2026-03-12 15:37:33', 238),
(213, NULL, 70, NULL, NULL, '', NULL, 1, 100, NULL, '2026-03-13 15:48:58', 123),
(214, NULL, 70, NULL, NULL, '', NULL, 1, 800, NULL, '2026-03-13 16:21:49', 39),
(218, NULL, 70, NULL, NULL, '', NULL, 1, 250, NULL, '2026-03-13 16:59:06', 108),
(219, NULL, 71, NULL, NULL, '', NULL, 1, 200, NULL, '2026-03-13 17:51:49', 99),
(221, NULL, 71, NULL, NULL, '', NULL, 1, 100, NULL, '2026-03-13 17:52:25', 123),
(222, NULL, 71, NULL, NULL, '', NULL, 1, 200, NULL, '2026-03-13 17:52:56', 198),
(223, NULL, 71, NULL, NULL, '', NULL, 1, 100, NULL, '2026-03-13 17:53:43', 215),
(224, NULL, 71, NULL, NULL, '', NULL, 1, 200, NULL, '2026-03-13 17:54:35', 45),
(225, NULL, 72, NULL, NULL, '', NULL, 1, 600, NULL, '2026-03-13 19:21:27', 31),
(227, NULL, 72, NULL, NULL, '', NULL, 1, 400, NULL, '2026-03-13 19:22:19', 57),
(228, NULL, 72, NULL, NULL, '', NULL, 4, 400, NULL, '2026-03-13 19:23:36', 6),
(229, NULL, 73, NULL, NULL, '', NULL, 1, 500, NULL, '2026-03-14 13:11:13', 256),
(230, NULL, 73, NULL, NULL, '', NULL, 1, 500, NULL, '2026-03-14 13:11:31', 233),
(231, NULL, 73, NULL, NULL, '', NULL, 1, 400, NULL, '2026-03-14 13:11:46', 93),
(233, NULL, 73, NULL, NULL, '', NULL, 15, 10, NULL, '2026-03-14 13:13:30', 207),
(234, NULL, 73, NULL, NULL, '', NULL, 20, 5, NULL, '2026-03-14 13:14:11', 86),
(235, NULL, 73, NULL, NULL, '', NULL, 10, 10, NULL, '2026-03-14 13:14:58', 60),
(236, NULL, 62, NULL, NULL, '', NULL, 6, 400, NULL, '2026-03-14 16:42:40', 220),
(237, NULL, 62, NULL, NULL, '', NULL, 3, 800, NULL, '2026-03-14 16:43:04', 67),
(238, NULL, 62, NULL, NULL, '', NULL, 5, 400, NULL, '2026-03-14 16:43:36', 238),
(239, NULL, 62, NULL, NULL, '', NULL, 10, 400, NULL, '2026-03-16 14:31:23', 238),
(240, NULL, 62, NULL, NULL, '', NULL, 5, 400, NULL, '2026-03-16 14:31:53', 220),
(241, NULL, 62, NULL, NULL, '', NULL, 5, 500, NULL, '2026-03-16 14:33:24', 236),
(242, NULL, 62, NULL, NULL, '', NULL, 1, 800, NULL, '2026-03-16 14:33:37', 83),
(243, NULL, 72, NULL, NULL, '', NULL, 1, 100, NULL, '2026-03-17 05:19:25', 215),
(244, NULL, 72, NULL, NULL, '', NULL, 1, 50, NULL, '2026-03-17 05:20:01', 118),
(245, NULL, 74, NULL, NULL, '', NULL, 4, 400, NULL, '2026-03-18 10:08:31', 6),
(246, NULL, 74, NULL, NULL, '', NULL, 3, 500, NULL, '2026-03-18 10:09:18', 82),
(247, NULL, 74, NULL, NULL, '', NULL, 1, 150, NULL, '2026-03-18 10:09:37', 28),
(248, NULL, 74, NULL, NULL, '', NULL, 20, 5, NULL, '2026-03-18 10:10:26', 86),
(249, NULL, 74, NULL, NULL, '', NULL, 1, 200, NULL, '2026-03-18 10:11:01', 95),
(250, NULL, 75, NULL, NULL, '', NULL, 1, 400, NULL, '2026-03-18 13:57:11', 6),
(251, NULL, 75, NULL, NULL, '', NULL, 1, 300, NULL, '2026-03-18 13:57:31', 70),
(252, NULL, 76, NULL, NULL, '', NULL, 1, 600, NULL, '2026-03-18 15:33:27', 232),
(253, NULL, 77, NULL, NULL, '', NULL, 1, 100, NULL, '2026-03-18 16:05:32', 123),
(254, NULL, 77, NULL, NULL, '', NULL, 1, 250, NULL, '2026-03-18 16:41:07', 52),
(255, NULL, 77, NULL, NULL, '', NULL, 1, 450, NULL, '2026-03-18 16:41:53', 58),
(256, NULL, 77, NULL, NULL, '', NULL, 1, 100, NULL, '2026-03-18 16:42:03', 123),
(257, NULL, 78, NULL, NULL, '', NULL, 1, 20, NULL, '2026-03-18 17:50:39', 44),
(258, NULL, 78, NULL, NULL, '', NULL, 1, 300, NULL, '2026-03-18 17:51:03', 70),
(259, NULL, 78, NULL, NULL, '', NULL, 1, 10, NULL, '2026-03-18 17:51:40', 10),
(260, NULL, 76, NULL, NULL, '', NULL, 1, 800, NULL, '2026-03-18 18:07:45', 135),
(261, NULL, 76, NULL, NULL, '', NULL, 4, 400, NULL, '2026-03-18 18:07:56', 6),
(263, NULL, 76, NULL, NULL, '', NULL, 1, 300, NULL, '2026-03-18 18:20:05', 70),
(267, NULL, 76, NULL, NULL, '', NULL, 1, 500, NULL, '2026-03-18 19:22:50', 82),
(268, NULL, 76, NULL, NULL, '', NULL, 1, 500, NULL, '2026-03-18 19:23:17', 107),
(269, NULL, 76, NULL, NULL, '', NULL, 1, 250, NULL, '2026-03-18 19:23:43', 168),
(270, NULL, 76, NULL, NULL, '', NULL, 20, 5, NULL, '2026-03-18 19:25:40', 86),
(271, NULL, 76, NULL, NULL, '', NULL, 5, 10, NULL, '2026-03-18 19:34:35', 3),
(272, NULL, 60, NULL, NULL, '', NULL, 1, 800, NULL, '2026-03-18 19:51:14', 135),
(273, NULL, 60, NULL, NULL, '', NULL, 9, 10, NULL, '2026-03-18 19:57:53', 19),
(274, NULL, 79, NULL, NULL, '', NULL, 1, 600, NULL, '2026-03-19 01:00:57', 31),
(275, NULL, 79, NULL, NULL, '', NULL, 3, 400, NULL, '2026-03-19 02:29:02', 6),
(276, NULL, 79, NULL, NULL, '', NULL, 1, 400, NULL, '2026-03-19 02:29:20', 93),
(277, NULL, 79, NULL, NULL, '', NULL, 1, 450, NULL, '2026-03-19 02:29:36', 14),
(278, NULL, 79, NULL, NULL, '', NULL, 1, 150, NULL, '2026-03-19 02:30:13', 229),
(279, NULL, 79, NULL, NULL, '', NULL, 1, 150, NULL, '2026-03-19 02:31:00', 114),
(280, NULL, 80, NULL, NULL, '', NULL, 1, 600, NULL, '2026-03-19 06:23:36', 232),
(281, NULL, 80, NULL, NULL, '', NULL, 4, 400, NULL, '2026-03-19 06:40:55', 6),
(282, NULL, 80, NULL, NULL, '', NULL, 2, 400, NULL, '2026-03-19 06:41:35', 93),
(283, NULL, 80, NULL, NULL, '', NULL, 1, 400, NULL, '2026-03-19 06:42:10', 21),
(284, NULL, 80, NULL, NULL, '', NULL, 1, 700, NULL, '2026-03-19 06:42:43', 54),
(286, NULL, 80, NULL, NULL, '', NULL, 10, 20, NULL, '2026-03-19 06:55:43', 9),
(288, NULL, 80, NULL, NULL, '', NULL, 1, 400, NULL, '2026-03-19 07:36:15', 284),
(289, NULL, 73, NULL, NULL, '', NULL, 3, 500, NULL, '2026-03-19 07:39:44', 46),
(290, NULL, 73, NULL, NULL, '', NULL, 1, 400, NULL, '2026-03-19 07:40:47', 93),
(291, NULL, 73, NULL, NULL, '', NULL, 1, 500, NULL, '2026-03-19 07:41:33', 201),
(292, NULL, 60, NULL, NULL, '', NULL, 1, 800, NULL, '2026-03-19 18:56:56', 135),
(294, NULL, 82, NULL, NULL, '4 days', NULL, 4, 1200, NULL, '2026-03-22 17:42:57', 223),
(295, NULL, 82, NULL, NULL, '1', NULL, 1, 400, NULL, '2026-03-22 17:45:19', 220),
(297, NULL, 82, NULL, NULL, '3', NULL, 3, 500, NULL, '2026-03-22 17:50:52', 82),
(298, NULL, 82, NULL, NULL, '3 days', NULL, 3, 400, NULL, '2026-03-22 17:51:35', 6),
(299, NULL, 82, NULL, NULL, '3 days', NULL, 3, 500, NULL, '2026-03-22 17:53:02', 107),
(300, NULL, 82, NULL, NULL, '3 days', NULL, 20, 5, NULL, '2026-03-22 17:53:37', 86),
(301, NULL, 82, NULL, NULL, '1 month', NULL, 30, 40, NULL, '2026-03-22 18:03:51', 289),
(302, NULL, 82, NULL, NULL, '14 days', NULL, 2, 2500, NULL, '2026-03-22 18:06:47', 290),
(303, NULL, 83, NULL, NULL, '4 days', NULL, 4, 400, NULL, '2026-03-23 12:41:07', 6),
(304, NULL, 83, NULL, NULL, '', NULL, 1, 400, NULL, '2026-03-23 12:42:45', 220),
(305, NULL, 83, NULL, NULL, '', NULL, 1, 1000, NULL, '2026-03-23 12:43:23', 37),
(306, NULL, 83, NULL, NULL, '', NULL, 10, 10, NULL, '2026-03-23 12:43:47', 60),
(307, NULL, 83, NULL, NULL, '', NULL, 10, 50, NULL, '2026-03-23 12:45:20', 100),
(308, NULL, 84, NULL, NULL, '', NULL, 1, 100, NULL, '2026-03-24 06:12:49', 61),
(309, NULL, 84, NULL, NULL, '', NULL, 1, 500, NULL, '2026-03-24 06:13:07', 82),
(310, NULL, 84, NULL, NULL, '', NULL, 2, 400, NULL, '2026-03-24 06:13:27', 6),
(311, NULL, 84, NULL, NULL, '', NULL, 1, 400, NULL, '2026-03-24 06:14:10', 8),
(312, NULL, 84, NULL, NULL, '', NULL, 10, 10, NULL, '2026-03-24 06:14:40', 80),
(313, NULL, 84, NULL, NULL, '', NULL, 15, 15, NULL, '2026-03-24 06:15:19', 110),
(314, NULL, 84, NULL, NULL, '', NULL, 1, 200, NULL, '2026-03-24 06:15:39', 63),
(316, NULL, 87, NULL, NULL, 'stat', NULL, 1, 400, NULL, '2026-03-24 17:19:10', 220),
(318, NULL, 87, NULL, NULL, '4 days', NULL, 20, 5, NULL, '2026-03-24 17:19:56', 86),
(319, NULL, 87, NULL, NULL, '4 days', NULL, 10, 5, NULL, '2026-03-24 17:20:44', 221),
(320, NULL, 87, NULL, NULL, '', NULL, 1, 300, NULL, '2026-03-24 17:26:17', 70),
(321, NULL, 34, NULL, NULL, '', NULL, 1, 100, NULL, '2026-03-26 09:18:22', 123),
(322, NULL, 89, NULL, NULL, '', NULL, 1, 300, NULL, '2026-03-26 10:39:01', 241),
(323, NULL, 89, NULL, NULL, '', NULL, 1, 350, NULL, '2026-03-26 10:39:11', 91),
(324, NULL, 89, NULL, NULL, '', NULL, 20, 30, NULL, '2026-03-26 10:39:36', 266),
(325, NULL, 89, NULL, NULL, '', NULL, 1, 500, NULL, '2026-03-26 10:39:54', 291),
(326, NULL, 89, NULL, NULL, '', NULL, 1, 150, NULL, '2026-03-26 10:40:21', 191),
(327, NULL, 90, NULL, NULL, '', NULL, 1, 100, NULL, '2026-03-26 11:45:23', 123),
(328, NULL, 90, NULL, NULL, '', NULL, 1, 600, NULL, '2026-03-26 11:45:30', 232),
(329, NULL, 90, NULL, NULL, '', NULL, 3, 400, NULL, '2026-03-26 11:45:47', 6),
(330, NULL, 91, NULL, NULL, '', NULL, 1, 600, NULL, '2026-03-27 02:09:38', 232),
(331, NULL, 91, NULL, NULL, '', NULL, 1, 800, NULL, '2026-03-27 02:09:59', 79),
(332, NULL, 91, NULL, NULL, '', NULL, 1, 400, NULL, '2026-03-27 02:24:11', 284),
(334, NULL, 91, NULL, NULL, '', NULL, 10, 20, NULL, '2026-03-27 02:37:36', 213),
(335, NULL, 91, NULL, NULL, '', NULL, 1, 150, NULL, '2026-03-27 02:39:05', 13),
(336, NULL, 91, NULL, NULL, '', NULL, 3, 400, NULL, '2026-03-27 02:39:34', 6),
(338, NULL, 90, NULL, NULL, '', NULL, 1, 400, NULL, '2026-03-27 02:40:36', 238),
(340, NULL, 90, NULL, NULL, '', NULL, 1, 800, NULL, '2026-03-27 02:50:01', 79),
(341, NULL, 91, NULL, NULL, '', NULL, 1, 500, NULL, '2026-03-27 02:54:03', 82),
(342, NULL, 91, NULL, NULL, '', NULL, 2, 50, NULL, '2026-03-27 02:56:04', 153),
(343, NULL, 91, NULL, NULL, '', NULL, 1, 200, NULL, '2026-03-27 03:04:17', 163),
(345, NULL, 98, NULL, NULL, '', NULL, 3, 400, NULL, '2026-03-28 15:55:28', 6),
(346, NULL, 98, NULL, NULL, '', NULL, 3, 500, NULL, '2026-03-28 15:56:19', 82),
(347, NULL, 98, NULL, NULL, '', NULL, 1, 400, NULL, '2026-03-28 15:56:34', 238),
(348, NULL, 98, NULL, NULL, '', NULL, 1, 200, NULL, '2026-03-28 15:56:55', 198),
(349, NULL, 98, NULL, NULL, '5', NULL, 1, 300, NULL, '2026-03-28 15:59:52', 51),
(350, NULL, 99, NULL, NULL, '', NULL, 1, 400, NULL, '2026-03-28 23:34:56', 238),
(351, NULL, 99, NULL, NULL, '', NULL, 3, 400, NULL, '2026-03-29 00:11:48', 6),
(352, NULL, 99, NULL, NULL, '', NULL, 15, 10, NULL, '2026-03-29 00:12:48', 209),
(355, NULL, 99, NULL, NULL, '', NULL, 1, 300, NULL, '2026-03-29 00:14:17', 70),
(356, NULL, 99, NULL, NULL, '', NULL, 6, 10, NULL, '2026-03-29 00:15:45', 10),
(357, NULL, 99, NULL, NULL, '', NULL, 16, 15, NULL, '2026-03-29 00:16:38', 110),
(358, NULL, 100, NULL, NULL, '', NULL, 1, 600, NULL, '2026-03-29 15:41:42', 31),
(359, NULL, 100, NULL, NULL, '', NULL, 1, 400, NULL, '2026-03-29 15:42:13', 6),
(361, NULL, 100, NULL, NULL, '', NULL, 1, 200, NULL, '2026-03-29 15:42:53', 198),
(363, NULL, 100, NULL, NULL, '', NULL, 1, 300, NULL, '2026-03-29 15:44:08', 51),
(364, NULL, 101, NULL, NULL, '', NULL, 1, 800, NULL, '2026-03-31 21:46:47', 293),
(365, NULL, 101, NULL, NULL, '', NULL, 2, 100, NULL, '2026-03-31 21:47:19', 216),
(366, NULL, 101, NULL, NULL, '', NULL, 2, 100, NULL, '2026-03-31 21:47:47', 69),
(368, NULL, 101, NULL, NULL, '', NULL, 1, 400, NULL, '2026-03-31 21:51:12', 6),
(369, NULL, 102, NULL, NULL, '', NULL, 1, 400, NULL, '2026-04-01 04:56:40', 220),
(371, NULL, 102, NULL, NULL, '', NULL, 10, 20, NULL, '2026-04-01 05:36:23', 102),
(372, NULL, 102, NULL, NULL, '', NULL, 10, 15, NULL, '2026-04-01 05:36:50', 75),
(373, NULL, 102, NULL, NULL, '', NULL, 3, 400, NULL, '2026-04-01 05:37:27', 6),
(374, NULL, 103, NULL, NULL, '', NULL, 1, 400, NULL, '2026-04-01 05:49:40', 93),
(375, NULL, 103, NULL, NULL, '', NULL, 3, 400, NULL, '2026-04-01 05:49:57', 6),
(376, NULL, 103, NULL, NULL, '', NULL, 1, 150, NULL, '2026-04-01 05:50:22', 13),
(377, NULL, 103, NULL, NULL, '', NULL, 1, 150, NULL, '2026-04-01 05:50:36', 35),
(378, NULL, 103, NULL, NULL, '', NULL, 1, 150, NULL, '2026-04-01 05:51:06', 114),
(379, NULL, 103, NULL, NULL, '', NULL, 1, 100, NULL, '2026-04-01 06:03:43', 215),
(380, NULL, 106, NULL, NULL, '', NULL, 1, 600, NULL, '2026-04-03 17:45:19', 31),
(381, NULL, 106, NULL, NULL, '', NULL, 1, 300, NULL, '2026-04-03 17:46:43', 70),
(382, NULL, 106, NULL, NULL, '', NULL, 1, 10, NULL, '2026-04-03 17:47:11', 86),
(384, NULL, 107, NULL, NULL, '', NULL, 1, 500, NULL, '2026-04-04 07:31:43', 82),
(385, NULL, 107, NULL, NULL, '', NULL, 1, 500, NULL, '2026-04-04 07:32:00', 236),
(386, NULL, 107, NULL, NULL, '', NULL, 6, 400, NULL, '2026-04-04 07:32:53', 238),
(387, NULL, 107, NULL, NULL, '', NULL, 1, 500, NULL, '2026-04-04 07:33:38', 286),
(388, NULL, 107, NULL, NULL, '', NULL, 1, 500, NULL, '2026-04-04 07:33:54', 286),
(390, NULL, 108, NULL, NULL, '', NULL, 4, 400, NULL, '2026-04-04 18:12:23', 6),
(391, NULL, 108, NULL, NULL, '', NULL, 2, 400, NULL, '2026-04-04 18:12:39', 93),
(392, NULL, 108, NULL, NULL, '', NULL, 15, 10, NULL, '2026-04-04 18:13:02', 207),
(393, NULL, 108, NULL, NULL, '', NULL, 10, 10, NULL, '2026-04-04 18:13:52', 60),
(394, NULL, 108, NULL, NULL, '', NULL, 10, 20, NULL, '2026-04-04 18:14:07', 44),
(395, NULL, 109, NULL, NULL, '', NULL, 10, 10, NULL, '2026-04-04 19:04:40', 19),
(396, NULL, 109, NULL, NULL, '', NULL, 10, 10, NULL, '2026-04-04 19:04:55', 207),
(397, NULL, 109, NULL, NULL, '', NULL, 1, 200, NULL, '2026-04-04 19:05:20', 269),
(398, NULL, 110, NULL, NULL, '', NULL, 1, 1000, NULL, '2026-04-04 20:09:37', 248),
(399, NULL, 110, NULL, NULL, '', NULL, 1, 800, NULL, '2026-04-04 20:10:02', 67),
(400, NULL, 110, NULL, NULL, '', NULL, 1, 400, NULL, '2026-04-04 20:10:39', 8),
(401, NULL, 110, NULL, NULL, '', NULL, 1, 400, NULL, '2026-04-04 20:13:47', 8),
(402, NULL, 110, NULL, NULL, '', NULL, 5, 200, NULL, '2026-04-05 03:42:38', 227),
(403, NULL, 107, NULL, NULL, '', NULL, 1, 400, NULL, '2026-04-05 03:50:48', 232),
(404, NULL, 107, NULL, NULL, '', NULL, 1, 500, NULL, '2026-04-05 03:51:25', 286),
(406, NULL, 111, NULL, NULL, '', NULL, 1, 400, NULL, '2026-04-05 14:44:54', 187),
(407, NULL, 111, NULL, NULL, '', NULL, 1, 500, NULL, '2026-04-05 14:45:10', 82),
(409, NULL, 110, NULL, NULL, '', NULL, 1, 250, NULL, '2026-04-05 15:26:19', 52),
(410, NULL, 110, NULL, NULL, '', NULL, 1, 150, NULL, '2026-04-05 15:26:31', 35),
(411, NULL, 111, NULL, NULL, '', NULL, 1, 400, NULL, '2026-04-05 20:07:59', 187),
(412, NULL, 111, NULL, NULL, '', NULL, 1, 400, NULL, '2026-04-05 20:09:18', 238),
(413, NULL, 111, NULL, NULL, '', NULL, 1, 100, NULL, '2026-04-05 20:09:52', 184),
(415, NULL, 112, NULL, NULL, '', NULL, 15, 20, NULL, '2026-04-06 09:15:35', 206),
(416, NULL, 112, NULL, NULL, '', NULL, 15, 10, NULL, '2026-04-06 09:16:39', 209),
(417, NULL, 112, NULL, NULL, '', NULL, 5, 10, NULL, '2026-04-06 09:17:45', 10),
(418, NULL, 112, NULL, NULL, '', NULL, 5, 15, NULL, '2026-04-06 09:18:51', 75),
(419, NULL, 113, NULL, NULL, '', NULL, 3, 400, NULL, '2026-04-06 14:35:28', 6),
(420, NULL, 113, NULL, NULL, '', NULL, 1, 600, NULL, '2026-04-06 14:35:37', 232),
(421, NULL, 113, NULL, NULL, '', NULL, 1, 300, NULL, '2026-04-06 14:36:04', 70),
(422, NULL, 113, NULL, NULL, '', NULL, 15, 10, NULL, '2026-04-06 14:40:05', 19),
(423, NULL, 113, NULL, NULL, '', NULL, 5, 200, NULL, '2026-04-06 14:41:16', 227),
(424, NULL, 111, NULL, NULL, '', NULL, 1, 400, NULL, '2026-04-06 16:36:07', 220),
(425, NULL, 111, NULL, NULL, '', NULL, 1, 500, NULL, '2026-04-06 16:36:47', 82),
(426, NULL, 111, NULL, NULL, '', NULL, 2, 350, NULL, '2026-04-06 16:37:24', 187),
(427, NULL, 111, NULL, NULL, '', NULL, 1, 400, NULL, '2026-04-06 16:38:23', 238),
(428, NULL, 116, NULL, NULL, '', NULL, 1, 100, NULL, '2026-04-07 11:13:08', 24),
(429, NULL, 116, NULL, NULL, '', NULL, 1, 150, NULL, '2026-04-07 11:22:01', 28),
(430, NULL, 66, NULL, NULL, '', NULL, 1, 400, NULL, '2026-04-07 13:37:09', 6),
(431, NULL, 66, NULL, NULL, '', NULL, 3, 400, NULL, '2026-04-07 13:37:32', 6),
(432, NULL, 66, NULL, NULL, '', NULL, 2, 400, NULL, '2026-04-07 13:37:48', 93),
(433, NULL, 117, NULL, NULL, '', NULL, 1, 300, NULL, '2026-04-07 17:59:37', 70),
(434, NULL, 117, NULL, NULL, '', NULL, 1, 150, NULL, '2026-04-07 17:59:55', 28),
(435, NULL, 117, NULL, NULL, '', NULL, 1, 200, NULL, '2026-04-07 18:00:04', 47),
(436, NULL, 117, NULL, NULL, '', NULL, 20, 5, NULL, '2026-04-07 18:00:30', 86),
(437, NULL, 125, NULL, NULL, '', NULL, 3, 356, NULL, '2026-04-09 10:01:59', 82),
(438, NULL, 125, NULL, NULL, '', NULL, 4, 356, NULL, '2026-04-09 10:02:19', 6),
(439, NULL, 125, NULL, NULL, '', NULL, 1, 356, NULL, '2026-04-09 10:02:38', 154),
(440, NULL, 125, NULL, NULL, '', NULL, 10, 356, NULL, '2026-04-09 10:02:57', 100),
(441, NULL, 125, NULL, NULL, '', NULL, 30, 356, NULL, '2026-04-09 10:16:28', 302),
(442, NULL, 125, NULL, NULL, '', NULL, 1, 356, NULL, '2026-04-09 10:16:46', 301),
(443, NULL, 126, NULL, NULL, '', NULL, 3, 357, NULL, '2026-04-09 11:30:12', 6),
(444, NULL, 126, NULL, NULL, '', NULL, 1, 357, NULL, '2026-04-09 11:30:23', 70),
(445, NULL, 126, NULL, NULL, '', NULL, 20, 357, NULL, '2026-04-09 11:31:10', 86),
(446, NULL, 126, NULL, NULL, '', NULL, 1, 357, NULL, '2026-04-09 11:31:31', 23),
(447, NULL, 128, NULL, NULL, '', NULL, 1, 360, NULL, '2026-04-09 12:42:52', 31),
(448, NULL, 127, NULL, NULL, '', NULL, 3, 361, NULL, '2026-04-09 12:44:01', 6),
(449, NULL, 127, NULL, NULL, '', NULL, 2, 361, NULL, '2026-04-09 12:44:18', 93),
(450, NULL, 127, NULL, NULL, '', NULL, 1, 361, NULL, '2026-04-09 12:44:34', 99),
(451, NULL, 127, NULL, NULL, '', NULL, 1, 361, NULL, '2026-04-09 12:44:48', 184),
(452, NULL, 127, NULL, NULL, '', NULL, 1, 361, NULL, '2026-04-09 12:45:21', 33),
(454, NULL, 128, NULL, NULL, '', NULL, 2, 360, NULL, '2026-04-09 14:04:01', 93),
(455, NULL, 128, NULL, NULL, '', NULL, 4, 360, NULL, '2026-04-09 14:04:17', 6),
(456, NULL, 128, NULL, NULL, '', NULL, 1, 360, NULL, '2026-04-09 14:04:41', 135),
(457, NULL, 128, NULL, NULL, '', NULL, 1, 360, NULL, '2026-04-09 14:05:26', 245),
(459, NULL, 128, NULL, NULL, '', NULL, 1, 360, NULL, '2026-04-09 14:44:56', 114),
(464, NULL, 128, NULL, NULL, '', NULL, 1, 360, NULL, '2026-04-09 14:48:31', 33),
(465, NULL, 113, NULL, NULL, '', NULL, 2, 364, NULL, '2026-04-09 17:01:37', 82),
(466, NULL, 113, NULL, NULL, '', NULL, 1, 364, NULL, '2026-04-09 17:02:16', 8),
(467, NULL, 111, NULL, NULL, '', NULL, 1, 387, NULL, '2026-04-12 04:46:36', 232),
(468, NULL, 129, NULL, NULL, '', NULL, 15, 388, NULL, '2026-04-12 04:53:41', 81),
(469, NULL, 129, NULL, NULL, '', NULL, 1, 388, NULL, '2026-04-12 04:53:59', 256),
(470, NULL, 129, NULL, NULL, '', NULL, 1, 388, NULL, '2026-04-12 04:54:21', 93),
(471, NULL, 138, NULL, NULL, '', NULL, 5, 397, NULL, '2026-04-13 07:45:16', 6),
(472, NULL, 111, NULL, NULL, '', NULL, 3, 387, NULL, '2026-04-14 10:56:50', 82),
(473, NULL, 111, NULL, NULL, '', NULL, 3, 387, NULL, '2026-04-14 10:57:17', 187),
(474, NULL, 140, NULL, NULL, '', NULL, 1, 418, NULL, '2026-04-16 16:22:18', 238),
(475, NULL, 140, NULL, NULL, '', NULL, 1, 418, NULL, '2026-04-16 16:22:35', 83),
(476, NULL, 140, NULL, NULL, '', NULL, 2, 418, NULL, '2026-04-16 16:22:48', 256),
(477, NULL, 140, NULL, NULL, '', NULL, 1, 418, NULL, '2026-04-16 16:23:09', 38),
(478, NULL, 140, NULL, NULL, '', NULL, 10, 418, NULL, '2026-04-16 16:23:21', 75),
(479, NULL, 141, NULL, NULL, '', NULL, 1, 419, NULL, '2026-04-17 13:25:19', 82),
(480, NULL, 141, NULL, NULL, '', NULL, 1, 419, NULL, '2026-04-17 13:25:27', 46),
(481, NULL, 141, NULL, NULL, '', NULL, 1, 419, NULL, '2026-04-17 13:25:53', 63),
(483, NULL, 141, NULL, NULL, '', NULL, 1, 419, NULL, '2026-04-17 13:27:14', 35),
(484, NULL, 141, NULL, NULL, '', NULL, 1, 419, NULL, '2026-04-17 13:29:14', 51),
(485, NULL, 142, NULL, NULL, '', NULL, 5, 420, NULL, '2026-04-17 15:01:30', 6),
(486, NULL, 142, NULL, NULL, '', NULL, 3, 420, NULL, '2026-04-17 15:02:04', 82),
(487, NULL, 142, NULL, NULL, '', NULL, 1, 420, NULL, '2026-04-17 15:02:26', 255),
(488, NULL, 142, NULL, NULL, '', NULL, 1, 420, NULL, '2026-04-17 15:02:56', 70),
(489, NULL, 142, NULL, NULL, '', NULL, 1, 420, NULL, '2026-04-17 15:03:54', 28),
(490, NULL, 142, NULL, NULL, '', NULL, 10, 420, NULL, '2026-04-17 15:04:09', 75),
(491, NULL, 143, NULL, NULL, '', NULL, 1, 425, NULL, '2026-04-18 12:56:10', 238),
(492, NULL, 143, NULL, NULL, '', NULL, 1, 425, NULL, '2026-04-18 12:57:10', 67),
(493, NULL, 143, NULL, NULL, '', NULL, 1, 425, NULL, '2026-04-18 13:03:28', 94),
(496, NULL, 143, NULL, NULL, '', NULL, 1, 425, NULL, '2026-04-18 13:04:22', 14),
(497, NULL, 143, NULL, NULL, '', NULL, 4, 425, NULL, '2026-04-18 13:31:53', 6),
(498, NULL, 143, NULL, NULL, '', NULL, 1, 425, NULL, '2026-04-18 13:32:26', 82),
(499, NULL, 143, NULL, NULL, '', NULL, 1, 425, NULL, '2026-04-18 13:32:45', 51),
(500, NULL, 111, NULL, NULL, '', NULL, 7, 387, NULL, '2026-04-18 16:07:46', 232),
(501, NULL, 111, NULL, NULL, '', NULL, 4, 387, NULL, '2026-04-18 16:08:19', 187),
(502, NULL, 111, NULL, NULL, '', NULL, 3, 387, NULL, '2026-04-18 16:08:59', 82),
(503, NULL, 111, NULL, NULL, '', NULL, 1, 387, NULL, '2026-04-20 19:48:22', 135),
(504, NULL, 111, NULL, NULL, '', NULL, 9, 387, NULL, '2026-04-21 07:47:54', 82),
(505, NULL, 111, NULL, NULL, '', NULL, 9, 387, NULL, '2026-04-21 07:48:19', 238),
(506, NULL, 111, NULL, NULL, '', NULL, 4, 387, NULL, '2026-04-21 07:49:10', 187),
(507, NULL, 111, NULL, NULL, '', NULL, 1, 387, NULL, '2026-04-22 10:15:32', 187),
(508, NULL, 111, NULL, NULL, '', NULL, 1, 387, NULL, '2026-04-22 10:15:56', 82),
(509, NULL, 111, NULL, NULL, '', NULL, 1, 387, NULL, '2026-04-22 10:16:09', 238),
(510, NULL, 111, NULL, NULL, '', NULL, 1, 387, NULL, '2026-04-22 10:16:27', 75),
(511, NULL, 111, NULL, NULL, '', NULL, 1, 387, NULL, '2026-04-22 10:17:01', 220),
(512, NULL, 145, NULL, NULL, '', NULL, 1, 430, NULL, '2026-04-23 10:21:39', 191),
(513, NULL, 145, NULL, NULL, '', NULL, 20, 430, NULL, '2026-04-23 10:21:53', 102),
(514, NULL, 145, NULL, NULL, '', NULL, 1, 430, NULL, '2026-04-23 10:22:12', 291),
(515, NULL, 145, NULL, NULL, '', NULL, 1, 430, NULL, '2026-04-23 10:23:26', 201),
(516, NULL, 145, NULL, NULL, '', NULL, 1, 430, NULL, '2026-04-23 10:23:37', 269),
(517, NULL, 145, NULL, NULL, '', NULL, 1, 430, NULL, '2026-04-23 10:25:27', 91),
(518, NULL, 146, NULL, NULL, '', NULL, 1, 432, NULL, '2026-04-24 08:59:15', 232),
(519, NULL, 146, NULL, NULL, '', NULL, 1, 432, NULL, '2026-04-24 08:59:24', 79),
(520, NULL, 146, NULL, NULL, '', NULL, 1, 432, NULL, '2026-04-24 09:18:18', 284),
(524, NULL, 146, NULL, NULL, '', NULL, 1, 432, NULL, '2026-04-24 09:41:20', 82),
(527, NULL, 146, NULL, NULL, '', NULL, 1, 432, NULL, '2026-04-24 10:08:01', 182),
(528, NULL, 146, NULL, NULL, '', NULL, 3, 432, NULL, '2026-04-24 10:14:45', 6),
(529, NULL, 111, NULL, NULL, '', NULL, 2, 387, NULL, '2026-04-24 16:54:57', 255),
(530, NULL, 111, NULL, NULL, '', NULL, 2, 387, NULL, '2026-04-24 17:04:02', 82),
(534, NULL, 148, NULL, NULL, '', NULL, 1, 439, NULL, '2026-04-26 13:32:25', 304),
(535, NULL, 148, NULL, NULL, '', NULL, 1, 439, NULL, '2026-04-26 13:32:53', 303),
(536, NULL, 148, NULL, NULL, '', NULL, 1, 439, NULL, '2026-04-26 13:35:09', 305),
(537, NULL, 150, NULL, NULL, '', NULL, 1, 441, NULL, '2026-04-28 10:26:53', 220),
(538, NULL, 150, NULL, NULL, '', NULL, 1, 441, NULL, '2026-04-28 10:27:02', 191),
(539, NULL, 150, NULL, NULL, '', NULL, 1, 441, NULL, '2026-04-28 10:27:15', 241),
(540, NULL, 150, NULL, NULL, '', NULL, 2, 441, NULL, '2026-04-28 10:27:29', 91),
(541, NULL, 150, NULL, NULL, '', NULL, 20, 441, NULL, '2026-04-28 10:28:24', 266),
(542, NULL, 150, NULL, NULL, '', NULL, 10, 441, NULL, '2026-04-28 10:28:57', 100),
(543, NULL, 150, NULL, NULL, '', NULL, 1, 441, NULL, '2026-04-28 10:29:25', 269),
(544, NULL, 151, NULL, NULL, '', NULL, 1, 442, NULL, '2026-04-28 10:41:44', 220),
(545, NULL, 151, NULL, NULL, '', NULL, 1, 442, NULL, '2026-04-28 10:41:51', 269),
(546, NULL, 151, NULL, NULL, '', NULL, 1, 442, NULL, '2026-04-28 10:41:59', 191),
(547, NULL, 151, NULL, NULL, '', NULL, 2, 442, NULL, '2026-04-28 10:42:10', 91),
(548, NULL, 151, NULL, NULL, '', NULL, 20, 442, NULL, '2026-04-28 10:42:51', 266),
(549, NULL, 151, NULL, NULL, '', NULL, 10, 442, NULL, '2026-04-28 10:43:09', 100),
(550, NULL, 151, NULL, NULL, '', NULL, 1, 442, NULL, '2026-04-28 10:43:16', 241),
(551, NULL, 111, NULL, NULL, '', NULL, 1, 387, NULL, '2026-04-28 15:32:00', 220),
(552, NULL, 111, NULL, NULL, '', NULL, 2, 387, NULL, '2026-04-28 15:32:10', 201),
(553, NULL, 111, NULL, NULL, '', NULL, 10, 387, NULL, '2026-04-28 15:32:38', 89),
(554, NULL, 111, NULL, NULL, '', NULL, 10, 387, NULL, '2026-04-28 15:32:50', 199),
(555, NULL, 152, NULL, NULL, '', NULL, 2, 444, NULL, '2026-04-29 16:51:27', 93),
(556, NULL, 152, NULL, NULL, '', NULL, 3, 444, NULL, '2026-04-29 16:51:43', 6),
(557, NULL, 152, NULL, NULL, '', NULL, 10, 444, NULL, '2026-04-29 16:51:59', 75),
(558, NULL, 111, NULL, NULL, '', NULL, 1, 387, NULL, '2026-04-29 17:27:17', 256),
(559, NULL, 111, NULL, NULL, '', NULL, 1, 387, NULL, '2026-04-29 17:27:33', 232),
(560, NULL, 111, NULL, NULL, '', NULL, 1, 387, NULL, '2026-04-29 17:28:07', 82),
(561, NULL, 111, NULL, NULL, '', NULL, 1, 387, NULL, '2026-04-29 17:28:17', 201),
(562, NULL, 153, NULL, NULL, '3 days', NULL, 3, 447, NULL, '2026-04-30 07:41:59', 6),
(563, NULL, 153, NULL, NULL, '', NULL, 1, 447, NULL, '2026-04-30 07:46:55', 37),
(564, NULL, 153, NULL, NULL, '', NULL, 1, 447, NULL, '2026-04-30 07:47:11', 291),
(565, NULL, 153, NULL, NULL, '', NULL, 10, 447, NULL, '2026-04-30 07:47:29', 60),
(567, NULL, 153, NULL, NULL, '', NULL, 1, 447, NULL, '2026-04-30 07:48:51', 220),
(568, NULL, 111, NULL, NULL, '', NULL, 1, 387, NULL, '2026-05-01 07:05:24', 233),
(569, NULL, 111, NULL, NULL, '', NULL, 1, 387, NULL, '2026-05-01 07:05:59', 82),
(570, NULL, 154, NULL, NULL, '1', NULL, 1, 449, NULL, '2026-05-02 15:58:13', 57),
(571, NULL, 154, NULL, NULL, '1', NULL, 1, 449, NULL, '2026-05-02 15:58:52', 109),
(573, NULL, 154, NULL, NULL, '', NULL, 1, 449, NULL, '2026-05-02 16:01:30', 135),
(575, NULL, 154, NULL, NULL, '3 days', NULL, 3, 449, NULL, '2026-05-02 16:07:48', 6),
(576, NULL, 111, NULL, NULL, '', NULL, 2, 387, NULL, '2026-05-02 16:59:26', 93),
(577, NULL, 111, NULL, NULL, '', NULL, 5, 387, NULL, '2026-05-02 16:59:42', 256),
(578, NULL, 111, NULL, NULL, '', NULL, 1, 387, NULL, '2026-05-02 16:59:51', 91),
(579, NULL, 111, NULL, NULL, '', NULL, 1, 387, NULL, '2026-05-02 17:00:33', 291),
(580, NULL, 111, NULL, NULL, '', NULL, 1, 387, NULL, '2026-05-02 17:00:42', 128),
(581, NULL, 111, NULL, NULL, '', NULL, 10, 387, NULL, '2026-05-02 17:01:06', 199),
(582, NULL, 154, NULL, NULL, '3', NULL, 3, 449, NULL, '2026-05-03 05:12:24', 82),
(583, NULL, 155, NULL, NULL, '', NULL, 1, 453, NULL, '2026-05-03 11:38:26', 8),
(585, NULL, 156, NULL, NULL, '', NULL, 2, 454, NULL, '2026-05-03 12:10:58', 6),
(590, NULL, 157, NULL, NULL, '', NULL, 1, 456, NULL, '2026-05-03 12:35:49', 93),
(591, NULL, 157, NULL, NULL, '', NULL, 1, 456, NULL, '2026-05-03 12:36:04', 6),
(593, NULL, 157, NULL, NULL, '', NULL, 1, 456, NULL, '2026-05-03 12:37:35', 246),
(594, NULL, 157, NULL, NULL, '', NULL, 1, 456, NULL, '2026-05-03 12:37:59', 14),
(595, NULL, 157, NULL, NULL, '', NULL, 1, 456, NULL, '2026-05-03 13:06:13', 21),
(597, NULL, 157, NULL, NULL, '', NULL, 1, 456, NULL, '2026-05-03 13:10:55', 39),
(599, NULL, 155, NULL, NULL, '', NULL, 3, 453, NULL, '2026-05-03 15:10:25', 107),
(600, NULL, 155, NULL, NULL, '', NULL, 1, 453, NULL, '2026-05-03 15:10:47', 6),
(601, NULL, 158, NULL, NULL, '', NULL, 1, 459, NULL, '2026-05-03 15:36:33', 107),
(602, NULL, 158, NULL, NULL, '', NULL, 1, 459, NULL, '2026-05-03 15:36:54', 82),
(603, NULL, 158, NULL, NULL, '', NULL, 2, 459, NULL, '2026-05-03 15:37:11', 6),
(604, NULL, 158, NULL, NULL, '', NULL, 1, 459, NULL, '2026-05-03 15:38:01', 8),
(605, NULL, 158, NULL, NULL, '', NULL, 1, 459, NULL, '2026-05-03 15:38:37', 67),
(606, NULL, 158, NULL, NULL, '', NULL, 1, 459, NULL, '2026-05-03 15:39:34', 3),
(607, NULL, 158, NULL, NULL, '', NULL, 10, 459, NULL, '2026-05-03 15:40:02', 80),
(609, NULL, 158, NULL, NULL, '', NULL, 5, 459, NULL, '2026-05-03 15:40:36', 138),
(610, NULL, 158, NULL, NULL, '', NULL, 1, 459, NULL, '2026-05-03 16:57:54', 61),
(611, NULL, 159, NULL, NULL, '', NULL, 1, 461, NULL, '2026-05-04 14:27:15', 201),
(612, NULL, 159, NULL, NULL, '', NULL, 1, 461, NULL, '2026-05-04 14:27:24', 269),
(613, NULL, 159, NULL, NULL, '', NULL, 1, 461, NULL, '2026-05-04 14:27:30', 91),
(614, NULL, 159, NULL, NULL, '', NULL, 20, 461, NULL, '2026-05-04 14:27:42', 266),
(615, NULL, 159, NULL, NULL, '', NULL, 1, 461, NULL, '2026-05-04 14:27:51', 291),
(616, NULL, 159, NULL, NULL, '', NULL, 1, 461, NULL, '2026-05-04 14:28:05', 241),
(617, NULL, 159, NULL, NULL, '', NULL, 1, 461, NULL, '2026-05-04 14:28:48', 241),
(618, NULL, 159, NULL, NULL, '', NULL, 5, 461, NULL, '2026-05-04 14:30:05', 10),
(619, NULL, 160, NULL, NULL, '', NULL, 1, 463, NULL, '2026-05-04 14:36:12', 241),
(620, NULL, 160, NULL, NULL, '', NULL, 1, 463, NULL, '2026-05-04 14:36:18', 91),
(621, NULL, 160, NULL, NULL, '', NULL, 1, 463, NULL, '2026-05-04 14:36:34', 220),
(622, NULL, 160, NULL, NULL, '', NULL, 20, 463, NULL, '2026-05-04 14:37:08', 102),
(623, NULL, 160, NULL, NULL, '', NULL, 10, 463, NULL, '2026-05-04 14:37:24', 19),
(624, NULL, 160, NULL, NULL, '', NULL, 5, 463, NULL, '2026-05-04 14:37:38', 10),
(625, NULL, 161, NULL, NULL, '', NULL, 3, 465, NULL, '2026-05-07 17:56:43', 6),
(626, NULL, 161, NULL, NULL, '', NULL, 2, 465, NULL, '2026-05-07 17:57:04', 93),
(628, NULL, 161, NULL, NULL, '', NULL, 1, 465, NULL, '2026-05-07 17:59:11', 91),
(631, NULL, 162, NULL, NULL, '', NULL, 1, 468, NULL, '2026-05-08 07:51:02', 123),
(632, NULL, 162, NULL, NULL, 'bd 5', NULL, 1, 468, NULL, '2026-05-08 07:52:14', 246),
(634, NULL, 162, NULL, NULL, '', NULL, 1, 468, NULL, '2026-05-08 08:09:54', 182),
(635, NULL, 162, NULL, NULL, '', NULL, 1, 468, NULL, '2026-05-08 08:35:18', 39),
(637, NULL, 163, NULL, NULL, '', NULL, 1, 470, NULL, '2026-05-08 16:28:48', 70),
(638, NULL, 163, NULL, NULL, '', NULL, 1, 470, NULL, '2026-05-08 16:29:18', 23),
(639, NULL, 163, NULL, NULL, '', NULL, 1, 470, NULL, '2026-05-08 16:29:28', 47),
(640, NULL, 163, NULL, NULL, '', NULL, 10, 470, NULL, '2026-05-08 16:33:04', 188),
(642, NULL, 163, NULL, NULL, '', NULL, 1, 470, NULL, '2026-05-08 16:36:42', 6),
(643, NULL, 111, NULL, NULL, '', NULL, 1, 387, NULL, '2026-05-09 07:14:45', 232),
(644, NULL, 111, NULL, NULL, '', NULL, 2, 387, NULL, '2026-05-09 07:15:02', 6),
(645, NULL, 111, NULL, NULL, '', NULL, 1, 387, NULL, '2026-05-09 07:15:28', 82),
(646, NULL, 111, NULL, NULL, '', NULL, 1, 387, NULL, '2026-05-09 07:15:43', 195),
(647, NULL, 111, NULL, NULL, '', NULL, 1, 387, NULL, '2026-05-09 07:16:00', 135),
(648, NULL, 164, NULL, NULL, '', NULL, 1, 473, NULL, '2026-05-12 14:57:01', 28),
(649, NULL, 164, NULL, NULL, '', NULL, 15, 473, NULL, '2026-05-12 14:57:32', 19),
(659, NULL, 111, NULL, NULL, '', NULL, 1, 387, NULL, '2026-05-15 11:32:14', 83),
(660, NULL, 111, NULL, NULL, '', NULL, 1, 387, NULL, '2026-05-15 11:32:33', 82),
(661, NULL, 111, NULL, NULL, '', NULL, 1, 387, NULL, '2026-05-15 11:32:48', 238),
(662, NULL, 169, NULL, NULL, '', NULL, 1, 479, NULL, '2026-05-16 08:36:56', 8),
(663, NULL, 169, NULL, NULL, '', NULL, 1, 479, NULL, '2026-05-16 08:37:07', 67),
(664, NULL, 170, NULL, NULL, '1', NULL, 1, 484, NULL, '2026-05-21 19:45:35', 8),
(665, NULL, 170, NULL, NULL, '', NULL, 1, 484, NULL, '2026-05-21 19:46:13', 305),
(666, NULL, 170, NULL, NULL, '', NULL, 1, 484, NULL, '2026-05-21 19:46:50', 79),
(667, NULL, 170, NULL, NULL, '', NULL, 1, 484, NULL, '2026-05-21 19:47:55', 195),
(668, NULL, 170, NULL, NULL, '', NULL, 1, 484, NULL, '2026-05-21 20:30:14', 31),
(673, NULL, 171, NULL, NULL, '', NULL, 1, 485, NULL, '2026-05-24 18:25:36', 51),
(674, NULL, 171, NULL, NULL, '', NULL, 1, 485, NULL, '2026-05-24 18:26:53', 32),
(675, NULL, 171, NULL, NULL, '', NULL, 1, 485, NULL, '2026-05-24 18:35:42', 94),
(676, NULL, 171, NULL, NULL, '', NULL, 3, 485, NULL, '2026-05-24 18:37:13', 20),
(677, NULL, 172, NULL, NULL, '', NULL, 1, 487, NULL, '2026-05-29 13:03:35', 232),
(678, NULL, 172, NULL, NULL, '', NULL, 1, 487, NULL, '2026-05-29 13:03:47', 94),
(679, NULL, 172, NULL, NULL, '', NULL, 1, 487, NULL, '2026-05-29 13:04:07', 82),
(680, NULL, 172, NULL, NULL, '', NULL, 1, 487, NULL, '2026-05-29 13:04:31', 79),
(681, NULL, 172, NULL, NULL, '', NULL, 3, 487, NULL, '2026-05-29 13:04:59', 6),
(682, NULL, 172, NULL, NULL, '', NULL, 1, 487, NULL, '2026-05-29 13:06:36', 198),
(683, NULL, 173, NULL, NULL, '', NULL, 1, 489, NULL, '2026-05-30 15:37:35', 201),
(684, NULL, 173, NULL, NULL, '', NULL, 1, 489, NULL, '2026-05-30 15:38:03', 79),
(685, NULL, 173, NULL, NULL, '', NULL, 1, 489, NULL, '2026-05-30 15:45:22', 197),
(686, NULL, 173, NULL, NULL, '', NULL, 1, 489, NULL, '2026-05-30 15:45:41', 291),
(687, NULL, 173, NULL, NULL, '', NULL, 1, 489, NULL, '2026-05-30 16:52:25', 135),
(688, NULL, 173, NULL, NULL, '', NULL, 20, 489, NULL, '2026-05-30 17:46:49', 102),
(689, NULL, 173, NULL, NULL, '', NULL, 5, 489, NULL, '2026-05-30 18:01:02', 10),
(690, NULL, 173, NULL, NULL, '', NULL, 1, 489, NULL, '2026-05-30 18:01:17', 6),
(691, NULL, 173, NULL, NULL, '', NULL, 1, 489, NULL, '2026-05-30 18:01:30', 93),
(692, NULL, 179, NULL, NULL, '', NULL, 1, 491, NULL, '2026-05-31 09:21:28', 232),
(693, NULL, 179, NULL, NULL, '', NULL, 1, 491, NULL, '2026-05-31 09:21:42', 94),
(694, NULL, 179, NULL, NULL, '', NULL, 1, 491, NULL, '2026-05-31 09:21:59', 82),
(695, NULL, 179, NULL, NULL, '', NULL, 3, 491, NULL, '2026-05-31 09:22:24', 6),
(696, NULL, 179, NULL, NULL, '', NULL, 1, 491, NULL, '2026-05-31 09:23:01', 59),
(697, NULL, 179, NULL, NULL, '', NULL, 1, 491, NULL, '2026-05-31 09:24:55', 93),
(698, NULL, 179, NULL, NULL, '', NULL, 1, 491, NULL, '2026-05-31 09:25:26', 54),
(699, NULL, 180, NULL, NULL, '', NULL, 15, 493, NULL, '2026-05-31 18:36:00', 81),
(701, NULL, 180, NULL, NULL, '', NULL, 3, 493, NULL, '2026-05-31 18:37:07', 6),
(702, NULL, 180, NULL, NULL, '', NULL, 1, 493, NULL, '2026-05-31 18:37:29', 220),
(703, NULL, 180, NULL, NULL, '', NULL, 10, 493, NULL, '2026-05-31 18:39:20', 19),
(704, NULL, 180, NULL, NULL, '', NULL, 15, 493, NULL, '2026-05-31 18:39:48', 207),
(705, NULL, 181, NULL, NULL, '', NULL, 1, 495, NULL, '2026-06-01 09:14:18', 37),
(706, NULL, 181, NULL, NULL, '', NULL, 10, 495, NULL, '2026-06-01 09:14:37', 96),
(707, NULL, 182, NULL, NULL, '', NULL, 1, 496, NULL, '2026-06-05 10:10:22', 232),
(708, NULL, 182, NULL, NULL, '', NULL, 3, 496, NULL, '2026-06-05 10:10:41', 6),
(709, NULL, 182, NULL, NULL, '', NULL, 1, 496, NULL, '2026-06-05 10:10:57', 99),
(710, NULL, 182, NULL, NULL, '', NULL, 1, 496, NULL, '2026-06-05 10:11:17', 32),
(711, NULL, 183, NULL, NULL, '', NULL, 1, 498, NULL, '2026-06-07 09:59:34', 82),
(712, NULL, 183, NULL, NULL, '', NULL, 1, 498, NULL, '2026-06-07 09:59:49', 79),
(713, NULL, 183, NULL, NULL, '', NULL, 3, 498, NULL, '2026-06-07 10:00:04', 6),
(714, NULL, 183, NULL, NULL, '', NULL, 10, 498, NULL, '2026-06-07 10:00:26', 110),
(715, NULL, 183, NULL, NULL, '', NULL, 15, 498, NULL, '2026-06-07 10:02:15', 209),
(716, NULL, 184, NULL, NULL, '', NULL, 1, 499, NULL, '2026-06-07 12:19:46', 79),
(718, NULL, 184, NULL, NULL, '', NULL, 5, 499, NULL, '2026-06-07 13:28:31', 10),
(722, NULL, 184, NULL, NULL, '', NULL, 1, 499, NULL, '2026-06-07 13:42:37', 6),
(723, NULL, 186, NULL, NULL, '', NULL, 1, 501, NULL, '2026-06-08 15:22:43', 6),
(724, NULL, 186, NULL, NULL, '', NULL, 1, 501, NULL, '2026-06-08 15:22:54', 37),
(725, NULL, 186, NULL, NULL, '', NULL, 10, 501, NULL, '2026-06-08 15:23:15', 44),
(726, NULL, 186, NULL, NULL, '', NULL, 1, 501, NULL, '2026-06-08 15:23:32', 28),
(727, NULL, 186, NULL, NULL, '', NULL, 1, 501, NULL, '2026-06-08 15:23:51', 47),
(728, NULL, 188, NULL, NULL, '', NULL, 3, 505, NULL, '2026-06-09 06:22:46', 6),
(729, NULL, 188, NULL, NULL, '', NULL, 2, 505, NULL, '2026-06-09 06:22:58', 91),
(730, NULL, 188, NULL, NULL, '', NULL, 2, 505, NULL, '2026-06-09 06:22:58', 91),
(731, NULL, 188, NULL, NULL, '', NULL, 1, 505, NULL, '2026-06-09 06:23:26', 39),
(732, NULL, 188, NULL, NULL, '', NULL, 1, 505, NULL, '2026-06-09 06:23:35', 24),
(733, NULL, 188, NULL, NULL, '', NULL, 1, 505, NULL, '2026-06-09 06:24:31', 158),
(734, NULL, 190, NULL, NULL, '', NULL, 3, 507, NULL, '2026-06-10 09:09:55', 6),
(735, NULL, 190, NULL, NULL, '', NULL, 2, 507, NULL, '2026-06-10 09:10:17', 93),
(736, NULL, 190, NULL, NULL, '', NULL, 1, 507, NULL, '2026-06-10 09:10:43', 305),
(737, NULL, 190, NULL, NULL, '', NULL, 2, 507, NULL, '2026-06-10 09:11:38', 255),
(738, NULL, 190, NULL, NULL, '', NULL, 10, 507, NULL, '2026-06-10 09:11:49', 5),
(739, NULL, 191, NULL, NULL, '', NULL, 1, 509, NULL, '2026-06-12 15:09:09', 232),
(741, NULL, 191, NULL, NULL, '', NULL, 1, 509, NULL, '2026-06-12 15:46:43', 82),
(742, NULL, 191, NULL, NULL, '', NULL, 1, 509, NULL, '2026-06-12 15:47:04', 79),
(743, NULL, 191, NULL, NULL, '', NULL, 3, 509, NULL, '2026-06-12 15:47:44', 6),
(744, NULL, 191, NULL, NULL, '', NULL, 15, 509, NULL, '2026-06-12 15:48:42', 209),
(745, NULL, 191, NULL, NULL, '', NULL, 10, 509, NULL, '2026-06-12 15:49:02', 110),
(746, NULL, 193, NULL, NULL, '', NULL, 3, 510, NULL, '2026-06-14 05:32:45', 6),
(747, NULL, 193, NULL, NULL, '', NULL, 15, 510, NULL, '2026-06-14 05:33:35', 207),
(748, NULL, 193, NULL, NULL, '', NULL, 1, 510, NULL, '2026-06-14 05:33:51', 291),
(749, NULL, 195, NULL, NULL, '', NULL, 15, 514, NULL, '2026-06-16 20:15:57', 206),
(750, NULL, 195, NULL, NULL, '', NULL, 3, 514, NULL, '2026-06-16 20:16:34', 6),
(751, NULL, 195, NULL, NULL, '', NULL, 10, 514, NULL, '2026-06-16 20:17:29', 209);
INSERT INTO `prescriptions` (`id`, `encounter_id`, `patient_id`, `drug_name`, `dosage`, `frequency`, `duration`, `quantity`, `invoice_id`, `prescribed_by`, `created_at`, `medicine_id`) VALUES
(752, NULL, 195, NULL, NULL, '', NULL, 15, 514, NULL, '2026-06-16 20:17:48', 110),
(753, NULL, 196, NULL, NULL, '', NULL, 20, 517, NULL, '2026-06-18 09:36:57', 102),
(754, NULL, 196, NULL, NULL, '', NULL, 5, 517, NULL, '2026-06-18 09:37:12', 256),
(755, NULL, 197, NULL, NULL, '', NULL, 4, 521, NULL, '2026-06-20 05:27:21', 6),
(756, NULL, 197, NULL, NULL, '', NULL, 1, 521, NULL, '2026-06-20 05:27:48', 70),
(757, NULL, 197, NULL, NULL, '', NULL, 1, 521, NULL, '2026-06-20 05:28:15', 8),
(758, NULL, 197, NULL, NULL, '', NULL, 10, 521, NULL, '2026-06-20 05:28:53', 80),
(759, NULL, 197, NULL, NULL, '', NULL, 1, 521, NULL, '2026-06-20 05:53:45', 70),
(762, NULL, 198, NULL, NULL, '', NULL, 1, 523, NULL, '2026-06-20 21:10:12', 93),
(763, NULL, 198, NULL, NULL, '', NULL, 1, 523, NULL, '2026-06-20 21:10:44', 299),
(764, NULL, 198, NULL, NULL, '', NULL, 1, 523, NULL, '2026-06-20 21:11:03', 54),
(765, NULL, 198, NULL, NULL, '', NULL, 1, 523, NULL, '2026-06-20 21:11:51', 38),
(766, NULL, 198, NULL, NULL, '', NULL, 1, 523, NULL, '2026-06-20 21:12:06', 21),
(767, NULL, 199, NULL, NULL, '', NULL, 1, 525, NULL, '2026-06-22 15:26:08', 79),
(768, NULL, 199, NULL, NULL, '', NULL, 4, 525, NULL, '2026-06-22 15:28:49', 6),
(769, NULL, 199, NULL, NULL, '', NULL, 1, 525, NULL, '2026-06-22 15:29:00', 28),
(770, NULL, 199, NULL, NULL, '', NULL, 10, 525, NULL, '2026-06-22 15:29:43', 80),
(771, NULL, 199, NULL, NULL, '', NULL, 10, 525, NULL, '2026-06-22 15:30:16', 186),
(772, NULL, 200, NULL, NULL, '', NULL, 1, 527, NULL, '2026-06-24 18:39:19', 305),
(774, NULL, 200, NULL, NULL, '', NULL, 1, 527, NULL, '2026-06-24 18:40:16', 284),
(776, NULL, 200, NULL, NULL, '', NULL, 1, 527, NULL, '2026-06-24 18:51:08', 264),
(777, NULL, 200, NULL, NULL, '', NULL, 20, 527, NULL, '2026-06-24 18:51:47', 86),
(780, NULL, 200, NULL, NULL, '', NULL, 1, 527, NULL, '2026-06-24 19:02:57', 42),
(781, NULL, 200, NULL, NULL, '', NULL, 2, 527, NULL, '2026-06-24 19:03:19', 6),
(782, NULL, 200, NULL, NULL, '', NULL, 1, 527, NULL, '2026-06-24 19:03:48', 238),
(785, NULL, 201, NULL, NULL, '', NULL, 1, 529, NULL, '2026-06-26 17:10:53', 98),
(786, NULL, 201, NULL, NULL, '', NULL, 2, 529, NULL, '2026-06-26 17:13:23', 6),
(787, NULL, 201, NULL, NULL, '', NULL, 1, 529, NULL, '2026-06-26 17:13:53', 93),
(789, NULL, 203, NULL, NULL, '', NULL, 1, 532, NULL, '2026-06-27 05:44:40', 284),
(790, NULL, 203, NULL, NULL, '', NULL, 1, 532, NULL, '2026-06-27 05:45:16', 135),
(791, NULL, 203, NULL, NULL, '', NULL, 3, 532, NULL, '2026-06-27 05:45:36', 6),
(792, NULL, 203, NULL, NULL, '', NULL, 10, 532, NULL, '2026-06-27 05:46:31', 199),
(793, NULL, 203, NULL, NULL, '', NULL, 1, 532, NULL, '2026-06-27 05:46:40', 291),
(794, NULL, 203, NULL, NULL, '', NULL, 1, 532, NULL, '2026-06-27 05:48:55', 31),
(795, NULL, 204, NULL, NULL, '1gm BD 2/7', NULL, 4, 534, NULL, '2026-06-27 12:47:51', 6),
(796, NULL, 204, NULL, NULL, '4MG/1ML  OD 2/7 DAYS', NULL, 2, 534, NULL, '2026-06-27 12:49:10', 93),
(797, NULL, 204, NULL, NULL, '500 MG OD 3/7 DAYS', NULL, 1, 534, NULL, '2026-06-27 12:50:50', 70),
(798, NULL, 204, NULL, NULL, '10 MG OD 10/7 DAYS', NULL, 10, 534, NULL, '2026-06-27 12:53:08', 10),
(799, NULL, 204, NULL, NULL, ' TDS 3/7 DAYS THEN PRN', NULL, 20, 534, NULL, '2026-06-27 12:54:31', 86),
(800, NULL, 205, NULL, NULL, '', NULL, 2, 539, NULL, '2026-07-01 06:23:41', 46),
(801, NULL, 205, NULL, NULL, '', NULL, 1, 539, NULL, '2026-07-01 06:23:56', 82),
(802, NULL, 205, NULL, NULL, '', NULL, 10, 539, NULL, '2026-07-01 06:24:14', 81),
(803, NULL, 205, NULL, NULL, '', NULL, 1, 539, NULL, '2026-07-01 06:24:28', 291),
(804, NULL, 206, NULL, NULL, '', NULL, 4, 543, NULL, '2026-07-03 06:24:27', 6),
(805, NULL, 206, NULL, NULL, '', NULL, 2, 543, NULL, '2026-07-03 06:24:48', 82),
(807, NULL, 206, NULL, NULL, '', NULL, 1, 543, NULL, '2026-07-03 06:26:11', 31),
(808, NULL, 206, NULL, NULL, '', NULL, 1, 543, NULL, '2026-07-03 06:26:37', 291),
(809, NULL, 207, NULL, NULL, '', NULL, 1, 545, NULL, '2026-07-04 13:12:33', 93),
(810, NULL, 207, NULL, NULL, '', NULL, 3, 545, NULL, '2026-07-04 13:13:09', 6),
(811, NULL, 207, NULL, NULL, '', NULL, 1, 545, NULL, '2026-07-04 13:13:32', 99),
(812, NULL, 207, NULL, NULL, '', NULL, 1, 545, NULL, '2026-07-04 13:14:00', 198),
(813, NULL, 208, NULL, NULL, '', NULL, 1, 548, NULL, '2026-07-05 09:25:43', 232),
(814, NULL, 208, NULL, NULL, '', NULL, 1, 548, NULL, '2026-07-05 09:26:29', 303),
(815, NULL, 208, NULL, NULL, '', NULL, 1, 548, NULL, '2026-07-05 10:00:22', 201),
(816, NULL, 208, NULL, NULL, '', NULL, 1, 548, NULL, '2026-07-05 10:24:02', 67),
(817, NULL, 208, NULL, NULL, '', NULL, 5, 548, NULL, '2026-07-05 11:37:27', 6),
(818, NULL, 209, NULL, NULL, '', NULL, 1, 552, NULL, '2026-07-10 07:13:24', 21),
(819, NULL, 209, NULL, NULL, '', NULL, 1, 552, NULL, '2026-07-10 07:14:20', 245),
(820, NULL, 209, NULL, NULL, '', NULL, 1, 552, NULL, '2026-07-10 07:14:35', 98),
(821, NULL, 215, NULL, NULL, '', NULL, 3, 558, NULL, '2026-07-12 10:25:46', 82),
(822, NULL, 215, NULL, NULL, '', NULL, 4, 558, NULL, '2026-07-12 10:26:01', 6),
(823, NULL, 215, NULL, NULL, '', NULL, 1, 558, NULL, '2026-07-12 10:26:10', 47),
(824, NULL, 215, NULL, NULL, '', NULL, 1, 558, NULL, '2026-07-12 10:26:29', 23),
(825, NULL, 215, NULL, NULL, '', NULL, 1, 558, NULL, '2026-07-12 10:27:37', 28),
(827, NULL, 215, NULL, NULL, '', NULL, 10, 558, NULL, '2026-07-12 10:33:55', 100),
(829, NULL, 215, NULL, NULL, '', NULL, 10, 558, NULL, '2026-07-12 10:42:21', 100),
(830, NULL, 215, NULL, NULL, '', NULL, 9, 558, NULL, '2026-07-12 10:43:10', 100),
(831, NULL, 216, NULL, NULL, '', NULL, 2, 559, NULL, '2026-07-12 10:54:59', 82),
(832, NULL, 216, NULL, NULL, '', NULL, 3, 559, NULL, '2026-07-12 10:55:08', 6),
(833, NULL, 216, NULL, NULL, '', NULL, 1, 559, NULL, '2026-07-12 10:55:19', 291),
(834, NULL, 216, NULL, NULL, '', NULL, 5, 559, NULL, '2026-07-12 10:55:40', 10),
(835, NULL, 216, NULL, NULL, '', NULL, 2, 559, NULL, '2026-07-12 10:56:04', 91),
(836, NULL, 216, NULL, NULL, '', NULL, 1, 559, NULL, '2026-07-12 10:56:43', 47),
(837, NULL, 216, NULL, NULL, '', NULL, 1, 559, NULL, '2026-07-12 10:58:41', 291),
(838, NULL, 217, NULL, NULL, '', NULL, 1, 562, NULL, '2026-07-12 13:28:03', 201),
(839, NULL, 217, NULL, NULL, '', NULL, 3, 562, NULL, '2026-07-12 13:29:37', 82),
(840, NULL, 217, NULL, NULL, '', NULL, 4, 562, NULL, '2026-07-12 13:29:52', 6),
(841, NULL, 217, NULL, NULL, '', NULL, 20, 562, NULL, '2026-07-12 13:31:23', 102),
(842, NULL, 217, NULL, NULL, '', NULL, 10, 562, NULL, '2026-07-12 13:32:19', 19),
(843, NULL, 217, NULL, NULL, '', NULL, 1, 562, NULL, '2026-07-12 13:32:37', 5),
(844, NULL, 218, NULL, NULL, '', NULL, 1, 563, NULL, '2026-07-18 08:15:00', 67),
(845, NULL, 218, NULL, NULL, '', NULL, 1, 563, NULL, '2026-07-18 08:15:22', 225),
(846, NULL, 218, NULL, NULL, '', NULL, 1, 563, NULL, '2026-07-18 08:15:38', 17),
(847, NULL, 218, NULL, NULL, '', NULL, 1, 563, NULL, '2026-07-18 08:16:00', 74),
(848, NULL, 218, NULL, NULL, '', NULL, 3, 563, NULL, '2026-07-18 08:41:24', 6),
(849, NULL, 219, NULL, NULL, '', NULL, 1, 564, NULL, '2026-07-18 15:46:48', 52),
(850, NULL, 219, NULL, NULL, '', NULL, 1, 564, NULL, '2026-07-18 15:50:15', 33),
(851, NULL, 220, NULL, NULL, '', NULL, 1, 567, NULL, '2026-07-25 18:19:48', 52),
(852, NULL, 220, NULL, NULL, '', NULL, 1, 567, NULL, '2026-07-25 18:20:08', 13),
(853, NULL, 220, NULL, NULL, '', NULL, 2, 567, NULL, '2026-07-25 18:20:48', 153),
(854, NULL, 220, NULL, NULL, '', NULL, 5, 567, NULL, '2026-07-25 18:21:04', 213),
(855, NULL, 221, NULL, NULL, '', NULL, 1, 570, NULL, '2026-07-27 16:06:17', 206),
(856, NULL, 223, NULL, NULL, '', NULL, 1, 572, NULL, '2026-07-30 15:31:31', 299),
(857, NULL, 223, NULL, NULL, '', NULL, 1, 572, NULL, '2026-07-30 15:31:46', 232),
(860, NULL, 223, NULL, NULL, '', NULL, 1, 572, NULL, '2026-07-30 16:12:29', 14),
(863, NULL, 223, NULL, NULL, '', NULL, 1, 572, NULL, '2026-07-30 16:21:47', 93),
(865, NULL, 223, NULL, NULL, '', NULL, 4, 572, NULL, '2026-07-30 16:50:05', 6),
(869, NULL, 224, NULL, NULL, '', NULL, 1, 574, NULL, '2026-08-01 12:24:22', 37),
(870, NULL, 224, NULL, NULL, '', NULL, 10, 574, NULL, '2026-08-01 12:24:39', 100),
(871, NULL, 225, NULL, NULL, '', NULL, 3, 575, NULL, '2026-08-04 05:00:42', 82),
(873, NULL, 225, NULL, NULL, '', NULL, 1, 575, NULL, '2026-08-04 05:02:15', 220),
(876, NULL, 225, NULL, NULL, '', NULL, 1, 575, NULL, '2026-08-04 05:05:22', 236),
(877, NULL, 225, NULL, NULL, '', NULL, 4, 575, NULL, '2026-08-04 06:12:54', 256),
(878, NULL, 225, NULL, NULL, '', NULL, 10, 575, NULL, '2026-08-04 06:13:38', 271),
(879, NULL, 226, NULL, NULL, '', NULL, 4, 577, NULL, '2026-08-05 18:38:35', 6),
(880, NULL, 226, NULL, NULL, '', NULL, 1, 577, NULL, '2026-08-05 18:38:49', 47),
(881, NULL, 226, NULL, NULL, '', NULL, 1, 577, NULL, '2026-08-05 18:39:14', 95),
(882, NULL, 226, NULL, NULL, '', NULL, 1, 577, NULL, '2026-08-05 18:39:33', 28),
(883, NULL, 226, NULL, NULL, '', NULL, 1, 577, NULL, '2026-08-05 18:39:54', 70),
(884, NULL, 226, NULL, NULL, '', NULL, 0, 0, NULL, '2026-08-05 18:40:21', 19),
(885, NULL, 226, NULL, NULL, '', NULL, 20, 577, NULL, '2026-08-05 18:40:38', 19),
(886, NULL, 227, NULL, NULL, '', NULL, 2, 579, NULL, '2026-08-05 19:38:21', 93),
(887, NULL, 227, NULL, NULL, '', NULL, 4, 579, NULL, '2026-08-05 19:38:36', 6),
(888, NULL, 227, NULL, NULL, '', NULL, 1, 579, NULL, '2026-08-05 19:39:06', 232),
(889, NULL, 227, NULL, NULL, '', NULL, 1, 579, NULL, '2026-08-05 19:39:33', 70),
(890, NULL, 228, NULL, NULL, '', NULL, 3, 581, NULL, '2026-08-06 09:07:16', 6),
(891, NULL, 228, NULL, NULL, '', NULL, 1, 581, NULL, '2026-08-06 09:07:48', 70),
(892, NULL, 228, NULL, NULL, '', NULL, 1, 581, NULL, '2026-08-06 09:08:00', 28),
(893, NULL, 228, NULL, NULL, '', NULL, 1, 581, NULL, '2026-08-06 09:08:12', 47),
(894, NULL, 228, NULL, NULL, '', NULL, 10, 581, NULL, '2026-08-06 09:09:06', 19),
(897, NULL, 229, NULL, NULL, '3', NULL, 1, 582, NULL, '2026-08-06 16:39:38', 6),
(898, NULL, 229, NULL, NULL, '', NULL, 1, 582, NULL, '2026-08-06 16:40:22', 232),
(899, NULL, 229, NULL, NULL, '', NULL, 1, 582, NULL, '2026-08-06 16:42:00', 38),
(901, NULL, 229, NULL, NULL, '', NULL, 1, 582, NULL, '2026-08-06 16:43:25', 93),
(902, NULL, 229, NULL, NULL, '', NULL, 1, 582, NULL, '2026-08-06 16:44:08', 108),
(903, NULL, 229, NULL, NULL, '', NULL, 1, 582, NULL, '2026-08-06 17:15:38', 21),
(904, NULL, 230, NULL, NULL, '', NULL, 1, 583, NULL, '2026-08-07 03:28:19', 21),
(905, NULL, 230, NULL, NULL, '', NULL, 1, 583, NULL, '2026-08-07 03:28:53', 215),
(906, NULL, 230, NULL, NULL, '', NULL, 1, 583, NULL, '2026-08-07 03:30:56', 4),
(907, NULL, 230, NULL, NULL, '', NULL, 1, 583, NULL, '2026-08-07 03:39:30', 38),
(910, NULL, 231, NULL, NULL, '', NULL, 1, 586, NULL, '2026-08-08 19:27:28', 284),
(911, NULL, 231, NULL, NULL, '', NULL, 1, 586, NULL, '2026-08-08 19:48:29', 6),
(912, NULL, 231, NULL, NULL, '', NULL, 1, 586, NULL, '2026-08-08 19:48:39', 94),
(913, NULL, 231, NULL, NULL, '', NULL, 1, 586, NULL, '2026-08-08 19:49:11', 59),
(914, NULL, 231, NULL, NULL, '', NULL, 1, 586, NULL, '2026-08-08 19:49:38', 35),
(915, NULL, 234, NULL, NULL, '', NULL, 1, 588, NULL, '2026-08-12 13:12:58', 67),
(916, NULL, 234, NULL, NULL, '', NULL, 1, 588, NULL, '2026-08-12 13:21:43', 82),
(918, NULL, 234, NULL, NULL, '', NULL, 3, 588, NULL, '2026-08-12 13:22:26', 153),
(919, NULL, 234, NULL, NULL, '', NULL, 5, 588, NULL, '2026-08-12 13:22:38', 213),
(920, NULL, 234, NULL, NULL, '', NULL, 1, 588, NULL, '2026-08-12 13:26:56', 35),
(921, NULL, 234, NULL, NULL, '', NULL, 2, 588, NULL, '2026-08-12 13:41:04', 6),
(922, NULL, 235, NULL, NULL, '', NULL, 1, 589, NULL, '2026-08-13 13:35:28', 82),
(923, NULL, 235, NULL, NULL, '', NULL, 4, 589, NULL, '2026-08-13 14:00:44', 6),
(926, NULL, 235, NULL, NULL, '', NULL, 1, 589, NULL, '2026-08-13 14:02:35', 61),
(927, NULL, 235, NULL, NULL, '', NULL, 1, 589, NULL, '2026-08-13 14:44:00', 67),
(928, NULL, 235, NULL, NULL, '', NULL, 1, 589, NULL, '2026-08-13 15:49:03', 123),
(930, NULL, 236, NULL, NULL, '', NULL, 4, 590, NULL, '2026-08-14 05:27:28', 6),
(931, NULL, 236, NULL, NULL, '', NULL, 1, 590, NULL, '2026-08-14 05:27:49', 28),
(932, NULL, 236, NULL, NULL, '', NULL, 1, 590, NULL, '2026-08-14 05:28:34', 95),
(933, NULL, 236, NULL, NULL, '', NULL, 1, 590, NULL, '2026-08-14 05:29:20', 255),
(934, NULL, 236, NULL, NULL, '', NULL, 1, 590, NULL, '2026-08-14 05:29:41', 70),
(937, NULL, 237, NULL, NULL, '', NULL, 1, 593, NULL, '2026-08-14 09:49:24', 94),
(938, NULL, 237, NULL, NULL, '', NULL, 1, 593, NULL, '2026-08-14 09:50:55', 32),
(940, NULL, 237, NULL, NULL, '', NULL, 1, 593, NULL, '2026-08-14 09:57:45', 70),
(941, NULL, 238, NULL, NULL, '', NULL, 3, 595, NULL, '2026-08-14 12:44:50', 6),
(942, NULL, 238, NULL, NULL, '', NULL, 15, 595, NULL, '2026-08-14 12:45:11', 207),
(943, NULL, 238, NULL, NULL, '', NULL, 20, 595, NULL, '2026-08-14 12:45:35', 86),
(944, NULL, 241, NULL, NULL, '', NULL, 3, 598, NULL, '2026-08-19 07:17:07', 6),
(945, NULL, 241, NULL, NULL, '', NULL, 10, 598, NULL, '2026-08-19 07:17:24', 60),
(946, NULL, 241, NULL, NULL, '', NULL, 1, 598, NULL, '2026-08-19 07:17:44', 291),
(947, NULL, 242, NULL, NULL, '', NULL, 1, 600, NULL, '2026-08-19 14:44:03', 232),
(948, NULL, 242, NULL, NULL, '', NULL, 1, 600, NULL, '2026-08-19 14:44:51', 284),
(949, NULL, 242, NULL, NULL, '', NULL, 1, 600, NULL, '2026-08-19 14:45:18', 46),
(950, NULL, 242, NULL, NULL, '', NULL, 1, 600, NULL, '2026-08-19 14:45:57', 51),
(951, NULL, 242, NULL, NULL, '', NULL, 1, 600, NULL, '2026-08-19 14:46:24', 108),
(952, NULL, 242, NULL, NULL, '', NULL, 1, 600, NULL, '2026-08-19 15:18:03', 6),
(953, NULL, 241, NULL, NULL, '', NULL, 1, 598, NULL, '2026-08-20 13:32:40', 67),
(954, NULL, 243, NULL, NULL, '', NULL, 1, 602, NULL, '2026-08-21 16:13:44', 51),
(955, NULL, 243, NULL, NULL, '', NULL, 4, 602, NULL, '2026-08-21 16:15:19', 46),
(956, NULL, 244, NULL, NULL, '', NULL, 3, 605, NULL, '2026-08-21 18:13:49', 286),
(957, NULL, 244, NULL, NULL, '', NULL, 1, 605, NULL, '2026-08-21 18:14:03', 123),
(958, NULL, 244, NULL, NULL, '', NULL, 1, 605, NULL, '2026-08-21 18:14:32', 58),
(959, NULL, 244, NULL, NULL, '', NULL, 1, 605, NULL, '2026-08-21 18:14:54', 51),
(960, NULL, 244, NULL, NULL, '', NULL, 1, 605, NULL, '2026-08-21 18:15:12', 93),
(961, NULL, 244, NULL, NULL, '', NULL, 1, 605, NULL, '2026-08-21 18:18:28', 232),
(962, NULL, 244, NULL, NULL, '', NULL, 1, 605, NULL, '2026-08-21 18:54:43', 67),
(964, NULL, 245, NULL, NULL, '', NULL, 1, 607, NULL, '2026-08-22 10:00:25', 232),
(965, NULL, 245, NULL, NULL, '', NULL, 1, 607, NULL, '2026-08-22 10:10:57', 93),
(966, NULL, 245, NULL, NULL, '', NULL, 1, 607, NULL, '2026-08-22 10:11:06', 91),
(969, NULL, 245, NULL, NULL, '', NULL, 1, 607, NULL, '2026-08-22 10:18:41', 79),
(970, NULL, 245, NULL, NULL, '', NULL, 1, 607, NULL, '2026-08-22 11:15:04', 64),
(971, NULL, 245, NULL, NULL, '', NULL, 1, 607, NULL, '2026-08-22 11:15:53', 82),
(972, NULL, 245, NULL, NULL, '', NULL, 3, 607, NULL, '2026-08-22 11:18:18', 6),
(973, NULL, 245, NULL, NULL, '', NULL, 3, 607, NULL, '2026-08-22 11:21:25', 153),
(974, NULL, 245, NULL, NULL, '', NULL, 6, 607, NULL, '2026-08-22 11:22:01', 213),
(975, NULL, 245, NULL, NULL, '', NULL, 1, 607, NULL, '2026-08-22 12:34:23', 94),
(976, NULL, 245, NULL, NULL, '', NULL, 1, 607, NULL, '2026-08-22 12:37:18', 182),
(978, NULL, 245, NULL, NULL, '', NULL, 1, 607, NULL, '2026-08-22 13:27:03', 21),
(980, NULL, 246, NULL, NULL, '', NULL, 1, 610, NULL, '2026-08-23 07:23:32', 232),
(981, NULL, 246, NULL, NULL, '', NULL, 1, 610, NULL, '2026-08-23 07:23:51', 93),
(982, NULL, 246, NULL, NULL, '', NULL, 1, 610, NULL, '2026-08-23 07:24:07', 256),
(983, NULL, 246, NULL, NULL, '', NULL, 1, 610, NULL, '2026-08-23 07:24:21', 123),
(984, NULL, 246, NULL, NULL, '', NULL, 1, 610, NULL, '2026-08-23 07:24:58', 58),
(985, NULL, 246, NULL, NULL, '', NULL, 1, 610, NULL, '2026-08-23 07:26:04', 51),
(986, NULL, 249, NULL, NULL, '', NULL, 3, 611, NULL, '2026-08-25 05:21:42', 6),
(987, NULL, 249, NULL, NULL, '', NULL, 3, 611, NULL, '2026-08-25 05:22:10', 93),
(988, NULL, 249, NULL, NULL, '', NULL, 10, 611, NULL, '2026-08-25 05:22:25', 60),
(989, NULL, 249, NULL, NULL, '', NULL, 10, 611, NULL, '2026-08-25 05:22:45', 44),
(990, NULL, 249, NULL, NULL, '', NULL, 3, 611, NULL, '2026-08-25 05:23:03', 113),
(991, NULL, 248, NULL, NULL, '', NULL, 1, 613, NULL, '2026-08-25 16:23:48', 8),
(992, NULL, 248, NULL, NULL, '', NULL, 1, 613, NULL, '2026-08-25 16:24:18', 8),
(993, NULL, 250, NULL, NULL, '', NULL, 1, 615, NULL, '2026-08-26 11:17:45', 232),
(994, NULL, 250, NULL, NULL, '', NULL, 1, 615, NULL, '2026-08-26 11:18:11', 67),
(995, NULL, 250, NULL, NULL, '', NULL, 1, 615, NULL, '2026-08-26 11:18:37', 201),
(996, NULL, 251, NULL, NULL, '', NULL, 3, 616, NULL, '2026-08-26 13:02:39', 6),
(997, NULL, 251, NULL, NULL, '', NULL, 1, 616, NULL, '2026-08-26 13:02:57', 291),
(998, NULL, 250, NULL, NULL, '', NULL, 3, 615, NULL, '2026-08-26 13:43:43', 6),
(999, NULL, 250, NULL, NULL, '', NULL, 10, 615, NULL, '2026-08-26 13:44:33', 75),
(1000, NULL, 250, NULL, NULL, '', NULL, 1, 615, NULL, '2026-08-26 13:44:48', 70),
(1001, NULL, 250, NULL, NULL, '', NULL, 1, 615, NULL, '2026-08-26 14:06:47', 39),
(1002, NULL, 250, NULL, NULL, '', NULL, 1, 615, NULL, '2026-08-26 14:07:12', 291),
(1003, NULL, 252, NULL, NULL, '', NULL, 4, 618, NULL, '2026-08-26 14:20:12', 256),
(1004, NULL, 252, NULL, NULL, '', NULL, 1, 618, NULL, '2026-08-26 14:20:19', 232),
(1006, NULL, 252, NULL, NULL, '', NULL, 1, 618, NULL, '2026-08-26 14:20:54', 58),
(1007, NULL, 252, NULL, NULL, '', NULL, 1, 618, NULL, '2026-08-26 14:21:10', 51),
(1008, NULL, 252, NULL, NULL, '', NULL, 1, 618, NULL, '2026-08-26 14:21:52', 93),
(1009, NULL, 253, NULL, NULL, '', NULL, 1, 620, NULL, '2026-08-26 16:50:38', 201),
(1011, NULL, 254, NULL, NULL, '', NULL, 1, 623, NULL, '2026-08-27 07:32:48', 142),
(1012, NULL, 255, NULL, NULL, '', NULL, 3, 628, NULL, '2026-08-27 08:40:18', 6),
(1013, NULL, 255, NULL, NULL, '', NULL, 1, 628, NULL, '2026-08-27 08:40:29', 93),
(1014, NULL, 255, NULL, NULL, '', NULL, 1, 628, NULL, '2026-08-27 08:40:46', 21);

-- --------------------------------------------------------

--
-- Table structure for table `procedures`
--

CREATE TABLE `procedures` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `procedures_master`
--

CREATE TABLE `procedures_master` (
  `id` int(11) NOT NULL,
  `procedure_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `procedures_master`
--

INSERT INTO `procedures_master` (`id`, `procedure_name`, `price`) VALUES
(1, 'Wound Dressing', 1500.00),
(2, 'Minor Surgery', 5000.00),
(3, 'Major Surgery', 25000.00),
(4, 'Stitching', 2500.00),
(5, 'Catheterization', 2000.00),
(6, 'Nebulization', 1200.00),
(7, 'IV Cannulation', 800.00),
(8, 'Blood Transfusion', 4500.00),
(9, 'Delivery - Normal', 15000.00),
(10, 'Delivery - CS', 45000.00),
(11, 'ECG Procedure', 1000.00),
(12, 'Endoscopy', 12000.00),
(13, 'Biopsy', 7000.00),
(14, 'Suturing', 2500.00),
(15, 'Abscess Drainage', 3500.00);

-- --------------------------------------------------------

--
-- Table structure for table `procedures_performed`
--

CREATE TABLE `procedures_performed` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `procedure_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `order_date` date DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','invoiced') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`id`, `supplier_id`, `user_id`, `vendor_id`, `created_by`, `order_date`, `total_amount`, `status`, `created_at`) VALUES
(1, 3, 6, 0, 0, '2026-03-14', 0.00, '', '2026-03-14 10:52:58'),
(2, 4, 6, 0, 0, '2026-03-14', 0.00, '', '2026-03-14 13:18:15'),
(3, 3, 6, 0, 0, '2026-03-18', 0.00, '', '2026-03-18 05:19:42'),
(4, 1, 6, 0, 0, '2026-04-01', 1030.00, '', '2026-04-01 08:42:15'),
(5, 2, 6, 0, 0, '2026-04-02', 0.00, '', '2026-04-02 06:29:09'),
(6, 3, 6, 0, 0, '2026-04-03', 0.00, 'pending', '2026-04-03 04:29:18'),
(7, 3, 6, 0, 0, '2026-04-03', 0.00, 'pending', '2026-04-03 04:29:22'),
(8, 3, 6, 0, 0, '2026-04-03', 0.00, 'pending', '2026-04-03 04:29:23'),
(9, 3, 6, 0, 0, '2026-04-03', 0.00, 'pending', '2026-04-03 04:29:51'),
(10, 3, 6, 0, 0, '2026-04-03', 0.00, 'pending', '2026-04-03 04:31:35'),
(11, 2, 6, 0, 0, '2026-04-07', 15211.57, '', '2026-04-07 18:57:44'),
(12, 2, 6, 0, 0, '2026-08-27', 1000000.00, '', '2026-08-27 08:07:49');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `id` int(11) NOT NULL,
  `purchase_order_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `medication_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT NULL,
  `received_qty` int(11) DEFAULT 0,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_order_items`
--

INSERT INTO `purchase_order_items` (`id`, `purchase_order_id`, `item_name`, `medication_id`, `quantity`, `received_qty`, `unit_price`, `line_total`, `unit_cost`, `total`) VALUES
(1, 1, 'pethidine', 0, 1, 1, 0.00, 0.00, NULL, NULL),
(2, 2, 'pethidine', 0, 1, 1, 0.00, 0.00, NULL, NULL),
(3, 2, 'G 23 NEEDLES', 0, 1, 1, 0.00, 0.00, NULL, NULL),
(4, 3, 'pethidine', 0, 2, 2, 0.00, 0.00, NULL, NULL),
(5, 3, 'metronidazole 60mls ', 0, 3, 3, 0.00, 0.00, NULL, NULL),
(6, 3, 'totomol 60mls ', 0, 2, 2, 0.00, 0.00, NULL, NULL),
(7, 4, 'ceftriaxone', 0, 10, 10, 35.00, 350.00, NULL, NULL),
(8, 4, 'cetrizine 60mls', 0, 1, 1, 35.00, 35.00, NULL, NULL),
(9, 4, 'ibrufen 100mls', 0, 2, 2, 39.00, 78.00, NULL, NULL),
(10, 4, 'metronidazole 100mls', 0, 1, 1, 45.00, 45.00, NULL, NULL),
(11, 4, 'metreonidazole 60mls', 0, 2, 2, 35.00, 70.00, NULL, NULL),
(12, 4, 'amoxyl 60mls', 0, 3, 3, 34.00, 102.00, NULL, NULL),
(13, 4, 'gloves', 0, 100, 100, 3.50, 350.00, NULL, NULL),
(14, 5, 'P2', 0, 1, 1, 0.00, 0.00, NULL, NULL),
(15, 11, 'Azithromycin 500mg', 0, 4, 4, 55.00, 220.00, NULL, NULL),
(16, 11, 'Azithromycin 15mls', 0, 3, 3, 45.00, 135.00, NULL, NULL),
(17, 11, 'Betafen plus 60ml', 0, 1, 1, 105.00, 105.00, NULL, NULL),
(18, 11, 'Betafen plus 100ml', 0, 1, 1, 140.00, 140.00, NULL, NULL),
(19, 11, 'Brustan 100mls', 0, 2, 2, 255.00, 510.00, NULL, NULL),
(20, 11, 'Bulkot B cream', 0, 2, 2, 50.00, 100.00, NULL, NULL),
(21, 11, 'Calpol 100ms', 0, 1, 1, 347.37, 347.37, NULL, NULL),
(22, 11, 'Calpol 60ms', 0, 1, 1, 240.00, 240.00, NULL, NULL),
(23, 11, 'ceftaxidine', 0, 5, 5, 135.00, 675.00, NULL, NULL),
(24, 11, 'ceftraxne', 0, 30, 30, 33.00, 990.00, NULL, NULL),
(25, 11, 'Ceftax (inj)', 0, 5, 5, 110.00, 550.00, NULL, NULL),
(26, 11, 'Cetrizine 60ml', 0, 5, 5, 23.00, 115.00, NULL, NULL),
(27, 11, 'Cypro B plus', 0, 30, 30, 12.00, 360.00, NULL, NULL),
(28, 11, 'Ibuprofen  100mls', 0, 3, 3, 40.00, 120.00, NULL, NULL),
(29, 11, 'Chlopheniramine 60ml', 0, 5, 5, 25.00, 125.00, NULL, NULL),
(30, 11, 'floxapen 500mg', 0, 100, 100, 7.00, 700.00, NULL, NULL),
(31, 11, 'Secnidazole', 0, 12, 12, 15.00, 180.00, NULL, NULL),
(32, 11, 'Predinisolone 60ml', 0, 2, 2, 85.00, 170.00, NULL, NULL),
(33, 11, 'Dexamethasole 4mg (INJ)', 0, 20, 20, 15.00, 300.00, NULL, NULL),
(34, 11, 'Metronidazole  600ml', 0, 5, 5, 35.00, 175.00, NULL, NULL),
(35, 11, 'Entamaxin 60mls', 0, 3, 3, 65.00, 195.00, NULL, NULL),
(36, 11, 'Esomeprazole 40mg', 0, 2, 2, 55.00, 110.00, NULL, NULL),
(37, 11, 'Ifas', 0, 100, 100, 1.30, 130.00, NULL, NULL),
(38, 11, 'Fluconazole 150 mg', 0, 3, 3, 13.00, 39.00, NULL, NULL),
(39, 11, 'Good Morning (60ml)', 0, 2, 2, 90.00, 180.00, NULL, NULL),
(40, 11, 'Hemoforce prega 200ml', 0, 1, 1, 255.00, 255.00, NULL, NULL),
(41, 11, 'Hydrocortisone 100mg ( inj)', 0, 3, 3, 30.00, 90.00, NULL, NULL),
(42, 11, 'buscpan inj', 0, 20, 20, 20.00, 400.00, NULL, NULL),
(43, 11, 'plasil inj', 0, 10, 10, 9.00, 90.00, NULL, NULL),
(44, 11, 'Metronidazole  100ml', 0, 10, 10, 35.00, 350.00, NULL, NULL),
(45, 11, 'NIfedipine 20mg', 0, 100, 100, 0.90, 90.00, NULL, NULL),
(46, 11, 'Neuroforte', 0, 20, 20, 18.00, 360.00, NULL, NULL),
(47, 11, 'Paracetamol infusion 100mls', 0, 10, 10, 60.00, 600.00, NULL, NULL),
(48, 11, 'Pharmasal ointment', 0, 2, 2, 55.00, 110.00, NULL, NULL),
(49, 11, 'Prednisolone 5mg  (cosmos0', 0, 100, 100, 0.75, 75.00, NULL, NULL),
(50, 11, 'Promethazine 60ml', 0, 2, 2, 25.00, 50.00, NULL, NULL),
(51, 11, 'Ranferon 200mls', 0, 1, 1, 335.00, 335.00, NULL, NULL),
(52, 11, 'P2 ( generic)', 0, 5, 5, 25.00, 125.00, NULL, NULL),
(53, 11, 'Amoxicilin 500mg', 0, 200, 200, 2.80, 560.00, NULL, NULL),
(54, 11, 'Surgical spirit 50mls', 0, 3, 3, 26.00, 78.00, NULL, NULL),
(55, 11, 'Tranexamic acid ( inj)', 0, 5, 5, 65.00, 325.00, NULL, NULL),
(56, 11, 'Tricohist  60mls', 0, 3, 3, 105.00, 315.00, NULL, NULL),
(57, 11, 'Trust classic', 0, 10, 10, 21.00, 210.00, NULL, NULL),
(58, 11, 'Tinidazole', 0, 24, 24, 3.50, 84.00, NULL, NULL),
(59, 11, 'VEGA 100MG', 0, 4, 4, 10.00, 40.00, NULL, NULL),
(60, 11, 'zefcolin', 0, 2, 2, 200.00, 400.00, NULL, NULL),
(61, 11, 'Zinc sulphate 20mg', 0, 100, 100, 1.20, 120.00, NULL, NULL),
(62, 11, 'Esomeprazole 40mg', 0, 30, 30, 4.34, 130.20, NULL, NULL),
(63, 11, 'Zulu', 0, 10, 10, 28.50, 285.00, NULL, NULL),
(64, 11, 'Needles ( G21)', 0, 1, 1, 103.00, 103.00, NULL, NULL),
(65, 12, 'immunizatinon', 0, 10000, 10000, 100.00, 1000000.00, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `radiology_master`
--

CREATE TABLE `radiology_master` (
  `id` int(11) NOT NULL,
  `scan_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `radiology_master`
--

INSERT INTO `radiology_master` (`id`, `scan_name`, `price`) VALUES
(1, 'X-Ray Chest', 1500.00),
(2, 'X-Ray Limb', 1200.00),
(3, 'Ultrasound Abdomen', 3500.00),
(4, 'Ultrasound Pelvis', 3000.00),
(5, 'CT Scan Brain', 12000.00),
(6, 'CT Scan Abdomen', 15000.00),
(7, 'MRI Brain', 25000.00),
(8, 'MRI Spine', 28000.00),
(9, 'Mammogram', 5000.00),
(10, 'Echo', 6000.00);

-- --------------------------------------------------------

--
-- Table structure for table `radiology_requests`
--

CREATE TABLE `radiology_requests` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `procedure_name` varchar(255) DEFAULT NULL,
  `requested_by` int(11) DEFAULT NULL,
  `status` enum('Pending','Completed') DEFAULT 'Pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `radiology_tests`
--

CREATE TABLE `radiology_tests` (
  `id` int(11) NOT NULL,
  `scan_name` varchar(255) NOT NULL,
  `test_description` text DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `remittances`
--

CREATE TABLE `remittances` (
  `id` int(11) NOT NULL,
  `payer_id` int(11) NOT NULL,
  `reference_number` varchar(100) NOT NULL,
  `payment_date` date NOT NULL,
  `amount_received` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('Bank','Cheque','Transfer','Cash','Other') NOT NULL DEFAULT 'Bank',
  `bank_reference` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `remittance_items`
--

CREATE TABLE `remittance_items` (
  `id` int(11) NOT NULL,
  `remittance_id` int(11) NOT NULL,
  `claim_id` int(11) NOT NULL,
  `claim_item_id` int(11) DEFAULT NULL,
  `approved_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `adjustment_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `patient_responsibility_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `remittance_status` enum('Matched','Partial','Denied','Unmatched') NOT NULL DEFAULT 'Matched',
  `remarks` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `report_type` varchar(100) DEFAULT NULL,
  `generated_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `category` enum('procedure','lab','radiology','treatment') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `price`, `category`) VALUES
(1, 'See Doctor', 1000.00, ''),
(2, 'Malaria', 2000.00, ''),
(3, 'Urinalysis', 1000.00, 'lab');

-- --------------------------------------------------------

--
-- Table structure for table `services_master`
--

CREATE TABLE `services_master` (
  `id` int(11) NOT NULL,
  `category` enum('procedure','treatment','lab','radiology') NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services_master`
--

INSERT INTO `services_master` (`id`, `category`, `service_name`, `price`, `active`, `created_at`) VALUES
(1, 'procedure', 'Wound Dressing', 800.00, 1, '2026-02-16 11:58:04'),
(2, 'procedure', 'Minor Surgery', 5000.00, 1, '2026-02-16 11:58:04'),
(3, 'procedure', 'Major Surgery', 25000.00, 1, '2026-02-16 11:58:04'),
(4, 'procedure', 'Suturing', 1500.00, 1, '2026-02-16 11:58:04'),
(5, 'procedure', 'Incision & Drainage', 3000.00, 1, '2026-02-16 11:58:04'),
(6, 'procedure', 'Catheterization', 2000.00, 1, '2026-02-16 11:58:04'),
(7, 'procedure', 'Nebulization', 1000.00, 1, '2026-02-16 11:58:04'),
(8, 'procedure', 'IV Cannulation', 800.00, 1, '2026-02-16 11:58:04'),
(9, 'procedure', 'Blood Transfusion', 3500.00, 1, '2026-02-16 11:58:04'),
(10, 'procedure', 'Pap Smear', 2500.00, 1, '2026-02-16 11:58:04'),
(11, 'procedure', 'ECG', 1500.00, 1, '2026-02-16 11:58:04'),
(12, 'procedure', 'Endoscopy', 12000.00, 1, '2026-02-16 11:58:04'),
(13, 'procedure', 'Colonoscopy', 18000.00, 1, '2026-02-16 11:58:04'),
(14, 'procedure', 'Biopsy', 5000.00, 1, '2026-02-16 11:58:04'),
(15, 'treatment', 'Physiotherapy Session', 2000.00, 1, '2026-02-16 11:58:04'),
(16, 'treatment', 'Chemotherapy Session', 15000.00, 1, '2026-02-16 11:58:04'),
(17, 'treatment', 'Dialysis', 7000.00, 1, '2026-02-16 11:58:04'),
(18, 'treatment', 'Counselling Session', 3000.00, 1, '2026-02-16 11:58:04'),
(19, 'treatment', 'Vaccination - Child', 1000.00, 1, '2026-02-16 11:58:04'),
(20, 'treatment', 'Vaccination - Adult', 1500.00, 1, '2026-02-16 11:58:04'),
(21, 'treatment', 'Antenatal Clinic Visit', 2500.00, 1, '2026-02-16 11:58:04'),
(22, 'treatment', 'Postnatal Visit', 2000.00, 1, '2026-02-16 11:58:04'),
(23, 'treatment', 'Family Planning Service', 1800.00, 1, '2026-02-16 11:58:04'),
(24, 'lab', 'CBC', 500.00, 1, '2026-02-16 11:58:05'),
(25, 'lab', 'Blood Sugar Random', 300.00, 1, '2026-02-16 11:58:05'),
(26, 'lab', 'HbA1c', 1200.00, 1, '2026-02-16 11:58:05'),
(27, 'lab', 'Liver Function Test', 700.00, 1, '2026-02-16 11:58:05'),
(28, 'lab', 'Kidney Function Test', 700.00, 1, '2026-02-16 11:58:05'),
(29, 'lab', 'Lipid Profile', 1500.00, 1, '2026-02-16 11:58:05'),
(30, 'lab', 'Malaria Test', 400.00, 1, '2026-02-16 11:58:05'),
(31, 'lab', 'Typhoid Test', 600.00, 1, '2026-02-16 11:58:05'),
(32, 'lab', 'Urinalysis', 300.00, 1, '2026-02-16 11:58:05'),
(33, 'lab', 'Stool Analysis', 400.00, 1, '2026-02-16 11:58:05'),
(34, 'lab', 'HIV Test', 500.00, 1, '2026-02-16 11:58:05'),
(35, 'lab', 'Pregnancy Test', 300.00, 1, '2026-02-16 11:58:05'),
(36, 'lab', 'PSA', 2000.00, 1, '2026-02-16 11:58:05'),
(37, 'lab', 'Thyroid Profile', 2500.00, 1, '2026-02-16 11:58:05'),
(38, 'radiology', 'X-Ray Chest', 1000.00, 1, '2026-02-16 11:58:05'),
(39, 'radiology', 'X-Ray Limb', 1000.00, 1, '2026-02-16 11:58:05'),
(40, 'radiology', 'Ultrasound Abdomen', 2000.00, 1, '2026-02-16 11:58:05'),
(41, 'radiology', 'Pelvic Ultrasound', 2200.00, 1, '2026-02-16 11:58:05'),
(42, 'radiology', 'CT Scan Brain', 5000.00, 1, '2026-02-16 11:58:05'),
(43, 'radiology', 'CT Scan Abdomen', 5500.00, 1, '2026-02-16 11:58:05'),
(44, 'radiology', 'MRI Brain', 15000.00, 1, '2026-02-16 11:58:05'),
(45, 'radiology', 'MRI Spine', 16000.00, 1, '2026-02-16 11:58:05'),
(46, 'radiology', 'Mammography', 4000.00, 1, '2026-02-16 11:58:05'),
(47, '', 'Consultation Fee', 200.00, 1, '2026-02-25 12:44:25'),
(48, '', 'BABY WT', 50.00, 1, '2026-02-26 08:07:47'),
(49, 'lab', 'pdt', 100.00, 1, '2026-02-27 15:52:38'),
(50, '', 'maternity', 8000.00, 1, '2026-02-28 16:12:08'),
(51, '', 'MVA', 7000.00, 1, '2026-02-28 16:49:38'),
(52, '', 'MVa', 5000.00, 1, '2026-02-28 16:49:59'),
(53, '', 'minor dressing', 300.00, 1, '2026-03-01 10:31:24'),
(54, '', 'major dressing', 500.00, 1, '2026-03-01 10:31:49'),
(55, '', 'minor stitching', 1500.00, 1, '2026-03-01 10:32:32'),
(56, '', 'major stitching', 2000.00, 1, '2026-03-01 10:33:01'),
(57, 'treatment', 'sick off', 500.00, 1, '2026-03-02 11:04:21'),
(58, 'lab', 'stool  for o/c', 300.00, 1, '2026-03-03 10:56:56'),
(59, 'lab', 'H.pylori', 800.00, 1, '2026-03-03 10:57:33'),
(60, 'lab', 'pitc', 200.00, 1, '2026-03-05 11:52:17'),
(61, '', 'enema with sap', 500.00, 1, '2026-03-13 08:21:36'),
(62, '', 'ANC', 200.00, 1, '2026-03-13 13:52:57'),
(63, 'lab', 'Hb', 300.00, 1, '2026-03-18 19:50:08'),
(64, '', 'imlanr removal', 500.00, 1, '2026-03-24 07:32:12'),
(65, '', 'minor stitching 2', 400.00, 1, '2026-04-04 19:06:06'),
(66, 'treatment', 'admission fee', 500.00, 1, '2026-05-02 16:12:58'),
(67, '', 'normal delivery', 8500.00, 1, '2026-05-16 08:34:46');

-- --------------------------------------------------------

--
-- Table structure for table `service_master`
--

CREATE TABLE `service_master` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` enum('procedure','lab','radiology','treatment') NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_packages`
--

CREATE TABLE `service_packages` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `package_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` int(11) NOT NULL,
  `stock_id` int(11) NOT NULL,
  `movement_type` enum('in','out') NOT NULL,
  `quantity_change` int(11) NOT NULL,
  `balance_after` int(11) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `stock_id`, `movement_type`, `quantity_change`, `balance_after`, `note`, `user_id`, `created_at`) VALUES
(1, 1, 'in', 100, 102, '', 1, '2026-01-11 13:05:25'),
(2, 1, 'in', 100, 202, '', 1, '2026-01-11 13:05:40'),
(3, 1, 'in', 100, 302, '', 1, '2026-01-11 13:05:46'),
(4, 1, 'in', 100, 402, '', 1, '2026-01-11 13:09:09'),
(5, 1, 'in', 50, 452, '', 1, '2026-01-11 13:20:29'),
(6, 62, 'out', -1, 4, 'Sale', 1, '2026-02-04 12:14:10'),
(7, 62, 'out', -1, 3, 'Sale', 1, '2026-02-04 12:20:40'),
(8, 100, 'out', 1, 19, 'Sale', 1, '2026-02-04 12:47:51'),
(9, 100, 'out', -1, 18, 'Sale', 1, '2026-02-04 12:48:07'),
(10, 62, 'out', -1, 2, 'Sale', 1, '2026-02-04 13:07:36'),
(11, 204, 'out', 1, 2, 'Sale', 1, '2026-02-04 13:38:31'),
(12, 98, 'in', 10, 10, '', 6, '2026-03-07 07:55:02'),
(13, 146, 'in', 3, 3, '', 6, '2026-03-08 20:10:33'),
(14, 6, 'in', 15, 20, '', 6, '2026-03-10 12:07:23'),
(15, 195, 'in', -14, 10, '', 6, '2026-03-10 12:38:42'),
(16, 171, 'in', -27, 3, '', 6, '2026-03-10 12:40:20'),
(17, 220, 'in', 5, 17, '', 6, '2026-03-10 12:51:21'),
(18, 8, 'in', 1, 11, '', 6, '2026-03-10 12:51:52'),
(19, 109, 'in', 1, 7, '', 6, '2026-03-10 12:52:38'),
(20, 96, 'in', 0, 97, '', 6, '2026-03-10 14:33:18'),
(21, 93, 'in', -4, 6, '', 6, '2026-03-10 14:33:32'),
(22, 172, 'in', -1, 5, '', 6, '2026-03-10 14:37:42'),
(23, 124, 'in', 2, 4, '', 6, '2026-03-10 14:37:58'),
(24, 12, 'in', -1, 1, '', 6, '2026-03-10 14:38:30'),
(25, 107, 'in', -1, 4, '', 6, '2026-03-10 14:38:56'),
(26, 82, 'in', 5, 10, '', 6, '2026-03-10 14:39:33'),
(27, 31, 'in', -6, 4, '', 6, '2026-03-10 14:39:48'),
(28, 88, 'in', -2, 1, '', 6, '2026-03-10 15:53:38'),
(29, 59, 'in', 1, 2, '', 6, '2026-03-10 15:54:04'),
(30, 261, 'in', -1, 0, '', 6, '2026-03-10 16:02:15'),
(31, 164, 'in', -6, 1, '', 6, '2026-03-10 16:08:46'),
(32, 39, 'in', 2, 2, '', 6, '2026-03-10 16:09:38'),
(33, 38, 'in', 2, 2, '', 6, '2026-03-10 16:09:49'),
(34, 37, 'in', 1, 2, '', 6, '2026-03-10 16:10:09'),
(35, 98, 'in', -7, 2, '', 6, '2026-03-10 16:10:40'),
(36, 99, 'in', -1, 4, '', 6, '2026-03-10 16:11:32'),
(37, 51, 'in', -1, 3, '', 6, '2026-03-10 16:11:48'),
(38, 102, 'in', 60, 120, '', 6, '2026-03-10 16:14:19'),
(39, 63, 'in', -1, 2, '', 6, '2026-03-10 16:15:09'),
(40, 94, 'in', 0, 3, '', 6, '2026-03-10 16:15:31'),
(41, 81, 'in', 15, 44, '', 6, '2026-03-10 16:16:17'),
(42, 110, 'in', 30, 70, '', 6, '2026-03-10 16:17:05'),
(43, 259, 'in', 0, 1, '', 6, '2026-03-10 16:17:24'),
(44, 137, 'in', 70, 80, '', 6, '2026-03-10 16:17:44'),
(45, 188, 'in', 9, 39, '', 6, '2026-03-10 16:18:17'),
(46, 106, 'in', 35, 70, '', 6, '2026-03-10 16:18:53'),
(47, 186, 'in', -10, 10, '', 6, '2026-03-10 16:20:15'),
(48, 207, 'in', 100, 120, '', 6, '2026-03-10 16:20:47'),
(49, 210, 'in', 39, 49, '', 6, '2026-03-10 16:21:21'),
(50, 174, 'in', 83, 93, '', 6, '2026-03-10 16:21:38'),
(51, 28, 'in', -2, 2, '', 6, '2026-03-10 16:24:11'),
(52, 42, 'in', 0, 5, '', 6, '2026-03-10 16:24:45'),
(53, 23, 'in', -2, 1, '', 6, '2026-03-10 16:35:16'),
(54, 206, 'in', 104, 130, '', 6, '2026-03-10 16:40:28'),
(55, 163, 'in', 1, 1, '', 6, '2026-03-10 17:32:10'),
(56, 167, 'in', -2, 1, '', 6, '2026-03-10 17:34:36'),
(57, 87, 'in', -2, 1, '', 6, '2026-03-10 17:34:50'),
(58, 156, 'in', 1, 1, '', 6, '2026-03-10 17:35:07'),
(59, 48, 'in', -2, 2, '', 6, '2026-03-10 17:35:29'),
(60, 105, 'in', -4, 1, '', 6, '2026-03-10 17:35:58'),
(61, 158, 'in', 0, 2, '', 6, '2026-03-10 17:36:31'),
(62, 91, 'in', 3, 8, '', 6, '2026-03-10 17:36:41'),
(63, 47, 'in', 0, 2, '', 6, '2026-03-10 17:37:12'),
(64, 136, 'in', -3, 2, '', 6, '2026-03-10 17:37:25'),
(65, 111, 'in', -2, 3, '', 6, '2026-03-10 17:38:23'),
(66, 112, 'in', -2, 2, '', 6, '2026-03-10 17:38:36'),
(67, 56, 'in', -3, 2, '', 6, '2026-03-10 17:39:10'),
(68, 162, 'in', -1, 0, '', 6, '2026-03-10 17:40:27'),
(69, 193, 'in', 0, 20, '', 6, '2026-03-10 17:41:36'),
(70, 193, 'in', 23, 43, '', 6, '2026-03-10 17:41:51'),
(71, 194, 'in', -59, 10, '', 6, '2026-03-10 17:42:29'),
(72, 185, 'in', -7, 13, '', 6, '2026-03-10 17:42:48'),
(73, 153, 'in', -5, 5, '', 6, '2026-03-10 19:46:30'),
(74, 148, 'in', 6, 9, '', 6, '2026-03-10 19:46:56'),
(75, 149, 'in', 2, 7, '', 6, '2026-03-10 19:47:09'),
(76, 269, 'in', 0, 100, '', 6, '2026-03-10 19:51:54'),
(77, 269, 'in', 0, 99, '', 6, '2026-03-10 19:52:44'),
(78, 100, 'in', 0, 8, '', 6, '2026-03-10 20:10:15'),
(79, 61, 'in', 112, 120, '', 6, '2026-03-10 20:10:35'),
(80, 19, 'in', 0, 71, '', 6, '2026-03-10 20:10:51'),
(81, 19, 'in', 0, 71, '', 6, '2026-03-10 20:11:10'),
(82, 19, 'in', -62, 9, '', 6, '2026-03-10 20:11:29'),
(83, 75, 'in', 35, 36, '', 6, '2026-03-10 20:12:22'),
(84, 43, 'in', 75, 125, '', 6, '2026-03-10 20:13:14'),
(85, 44, 'in', -42, 58, '', 6, '2026-03-10 20:13:44'),
(86, 19, 'in', 19, 28, '', 6, '2026-03-10 20:14:15'),
(87, 209, 'in', 73, 103, '', 6, '2026-03-10 20:14:36'),
(88, 86, 'in', -16, 32, '', 6, '2026-03-10 20:15:19'),
(89, 199, 'in', 42, 52, '', 6, '2026-03-10 20:16:04'),
(90, 80, 'in', 79, 119, '', 6, '2026-03-10 20:16:43'),
(91, 171, 'in', 26, 29, '', 6, '2026-03-10 20:17:18'),
(92, 178, 'in', 2, 12, '', 6, '2026-03-10 20:18:18'),
(93, 216, 'in', 44, 54, '', 6, '2026-03-10 20:19:02'),
(94, 221, 'in', 96, 96, '', 6, '2026-03-10 20:20:29'),
(95, 69, 'in', -10, 90, '', 6, '2026-03-10 20:20:46'),
(96, 25, 'in', -2, 0, '', 6, '2026-03-10 20:21:34'),
(97, 24, 'in', -3, 2, '', 6, '2026-03-10 20:22:28'),
(98, 173, 'in', -2, 8, '', 6, '2026-03-10 20:23:03'),
(99, 96, 'in', -4, 93, '', 6, '2026-03-10 20:23:25'),
(100, 60, 'in', 133, 220, '', 6, '2026-03-10 20:23:45'),
(101, 9, 'in', 2, 28, '', 6, '2026-03-10 20:24:02'),
(102, 10, 'in', -30, 40, '', 6, '2026-03-10 20:24:43'),
(103, 74, 'in', 4, 8, '', 6, '2026-03-11 03:40:39'),
(104, 49, 'in', 2, 5, '', 6, '2026-03-11 03:41:01'),
(105, 17, 'in', -4, 1, '', 6, '2026-03-11 03:41:25'),
(106, 143, 'in', -1, 1, '', 6, '2026-03-11 03:41:45'),
(107, 161, 'in', 1, 2, '', 6, '2026-03-11 03:42:00'),
(108, 7, 'in', -2, 1, '', 6, '2026-03-11 03:42:27'),
(109, 275, 'in', 6, 7, '', 6, '2026-03-11 03:43:28'),
(110, 119, 'in', -1, 2, '', 6, '2026-03-11 03:45:41'),
(111, 214, 'in', -2, 1, '', 6, '2026-03-11 03:45:55'),
(112, 229, 'in', 1, 3, '', 6, '2026-03-11 03:46:22'),
(113, 157, 'in', 1, 1, '', 6, '2026-03-11 03:47:10'),
(114, 22, 'in', -1, 1, '', 6, '2026-03-11 03:47:40'),
(115, 45, 'in', -3, 2, '', 6, '2026-03-11 03:47:55'),
(116, 61, 'in', -114, 6, '', 6, '2026-03-11 03:48:24'),
(117, 62, 'in', 3, 5, '', 6, '2026-03-11 03:48:36'),
(118, 100, 'in', 112, 120, '', 6, '2026-03-11 03:49:15'),
(119, 129, 'in', -1, 0, '', 6, '2026-03-11 03:49:32'),
(120, 251, 'in', -13, 0, '', 6, '2026-03-11 03:49:50'),
(121, 147, 'in', -1, 0, '', 6, '2026-03-11 03:50:06'),
(122, 252, 'in', -1, 0, '', 6, '2026-03-11 03:50:16'),
(123, 204, 'in', -2, 0, '', 6, '2026-03-11 03:50:31'),
(124, 205, 'in', -2, 0, '', 6, '2026-03-11 03:50:44'),
(125, 168, 'in', 1, 1, '', 6, '2026-03-11 03:50:56'),
(126, 64, 'in', -1, 3, '', 6, '2026-03-11 03:51:25'),
(127, 130, 'in', -4, 0, '', 6, '2026-03-11 03:51:46'),
(128, 103, 'in', 14, 23, '', 6, '2026-03-11 03:52:14'),
(129, 103, 'in', 0, 23, '', 6, '2026-03-11 03:52:14'),
(130, 213, 'in', 24, 34, '', 6, '2026-03-11 03:52:49'),
(131, 89, 'in', -32, 18, '', 6, '2026-03-11 03:53:06'),
(132, 3, 'in', 19, 119, '', 6, '2026-03-11 03:54:54'),
(133, 121, 'in', -4, 24, '', 6, '2026-03-11 03:55:11'),
(134, 170, 'in', -6, 4, '', 6, '2026-03-11 03:55:24'),
(135, 153, 'in', 0, 5, '', 6, '2026-03-11 03:56:11'),
(136, 118, 'in', 1, 6, '', 6, '2026-03-11 03:57:02'),
(137, 117, 'in', -3, 0, '', 6, '2026-03-11 03:59:54'),
(138, 181, 'in', -2, 8, '', 6, '2026-03-11 04:00:18'),
(139, 138, 'in', 16, 22, '', 6, '2026-03-11 04:00:36'),
(140, 97, 'in', -1, 2, '', 6, '2026-03-11 04:03:13'),
(141, 141, 'in', -2, 1, '', 6, '2026-03-11 04:03:28'),
(142, 140, 'in', -2, 1, '', 6, '2026-03-11 04:03:40'),
(143, 141, 'in', -1, 0, '', 6, '2026-03-11 04:03:48'),
(144, 114, 'in', -3, 1, '', 6, '2026-03-11 04:04:08'),
(145, 101, 'in', 1, 5, '', 6, '2026-03-11 04:05:20'),
(146, 279, 'in', 0, 2, '', 6, '2026-03-11 04:09:04'),
(147, 169, 'in', -4, 4, '', 6, '2026-03-11 04:11:32'),
(148, 282, 'in', 0, 150, '', 6, '2026-03-11 09:20:04'),
(149, 282, 'in', 0, 150, '', 6, '2026-03-11 09:20:04'),
(150, 14, 'in', 0, 1, '', 6, '2026-03-11 10:11:47'),
(151, 168, 'in', 1, 2, '', 6, '2026-03-11 10:12:08'),
(152, 204, 'in', 2, 2, '', 6, '2026-03-11 10:12:24'),
(153, 204, 'in', -2, 0, '', 6, '2026-03-11 10:12:39'),
(154, 204, 'in', 0, 0, '', 6, '2026-03-11 10:12:39'),
(155, 205, 'in', 2, 2, '', 6, '2026-03-11 10:12:48'),
(156, 34, 'in', -5, 0, '', 6, '2026-03-11 10:13:30'),
(157, 24, 'in', 2, 4, '', 6, '2026-03-11 10:13:42'),
(158, 10, 'in', 100, 140, '', 6, '2026-03-11 10:14:09'),
(159, 93, 'in', 10, 16, '', 6, '2026-03-11 10:14:38'),
(160, 114, 'in', 2, 3, '', 6, '2026-03-11 10:14:56'),
(161, 86, 'in', 100, 132, '', 6, '2026-03-11 10:15:22'),
(162, 170, 'in', 100, 104, '', 6, '2026-03-11 10:15:45'),
(163, 214, 'in', 1, 2, '', 6, '2026-03-11 10:16:00'),
(164, 86, 'in', -2, 128, '', 6, '2026-03-11 15:25:02'),
(165, 275, 'in', -1, 6, '', 6, '2026-03-11 15:25:16');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `contact_name` varchar(100) DEFAULT NULL,
  `category` varchar(50) DEFAULT 'General',
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `contact_person` varchar(150) DEFAULT NULL,
  `mobile` varchar(60) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `tax_pin` varchar(80) DEFAULT NULL,
  `vat_number` varchar(80) DEFAULT NULL,
  `payment_terms` varchar(120) DEFAULT NULL,
  `bank_name` varchar(120) DEFAULT NULL,
  `bank_account` varchar(120) DEFAULT NULL,
  `credit_limit` decimal(12,2) NOT NULL DEFAULT 0.00,
  `address_line` varchar(255) DEFAULT NULL,
  `city` varchar(120) DEFAULT NULL,
  `country` varchar(120) DEFAULT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'Active',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact_name`, `category`, `phone`, `email`, `address`, `created_at`, `contact_person`, `mobile`, `website`, `tax_pin`, `vat_number`, `payment_terms`, `bank_name`, `bank_account`, `credit_limit`, `address_line`, `city`, `country`, `status`, `notes`) VALUES
(1, 'Pefric (E.A) Ltd', NULL, 'Medicines', '0110535349', 'pefricpharm@gmail.com', NULL, '2026-03-14 10:28:20', '', '0720560031', '', 'P051239643k', '', 'Immediate', '', '', 0.00, 'P.O Box 2424-00100', 'Nairobi', 'Kenya', 'Active', ''),
(2, 'BIbo pharmaceuticals', NULL, 'Medicines', '798335740', NULL, NULL, '2026-03-14 10:46:18', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, 'Active', NULL),
(3, 'Ludende pharmaceuticals', NULL, 'Medicines', '076090870', NULL, NULL, '2026-03-14 10:48:57', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, 'Active', NULL),
(4, 'Ludende pharmaceuticals', NULL, 'Medicines', '076090870', NULL, NULL, '2026-03-14 10:56:58', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, 'Active', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `supplier_invoices`
--

CREATE TABLE `supplier_invoices` (
  `id` int(11) NOT NULL,
  `purchase_order_id` int(11) NOT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `invoice_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `treatments`
--

CREATE TABLE `treatments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `treatment_type` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `administered_by` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'reception',
  `is_super` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `department` varchar(100) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`, `is_super`, `created_at`, `department`, `specialization`) VALUES
(1, 'admin', '0192023a7bbd73250516f069df18b500', 'System Administrator', 'admin', 1, '2025-11-26 12:50:46', NULL, NULL),
(5, 'benjamin', '81dc9bdb52d04dc20036dbd8313ed055', 'Benjamin Wambua', 'doctor', 1, '2025-12-23 10:31:43', NULL, NULL),
(6, 'kawira', '$2y$10$WgdaSvUHAK5TzrYwYCchI.mUHh1WtQYvqUOx3P062Mz.MZ1C49vWu', 'Immaculate Kawira', 'doctor', 1, '2026-02-25 13:43:54', NULL, NULL),
(7, 'teddy', '$2y$10$78nHyIyZSV.h9o1LWRSnmevjRa4Ke.y4IlP.RW7mXM9e2PnNELBxS', 'Teddy mutiga', 'doctor', 0, '2026-03-02 08:18:41', NULL, NULL),
(8, 'sysadmin', '$2y$10$zwUSGQV0nBDX77CkWAPBC.3/3hZRQapNu9MkGojoM26PN3fOg209q', 'Admin', 'admin', 0, '2026-03-02 15:50:42', NULL, NULL),
(9, 'sarah', '$2y$10$ZIc1sbJ431K./c.RcuuiyeS6UgfbTwdY4doX8XZSP5lzNs/BVvchS', 'sarah', 'doctor', 0, '2026-08-27 08:55:40', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vendors`
--

CREATE TABLE `vendors` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vendors`
--

INSERT INTO `vendors` (`id`, `name`, `phone`, `email`, `address`, `created_at`) VALUES
(1, 'New', '07888888888', 'new@gmail.com', NULL, '2025-12-21 10:59:35'),
(2, 'ge', '6554', '', '', '2025-12-22 10:54:46');

-- --------------------------------------------------------

--
-- Table structure for table `vitals`
--

CREATE TABLE `vitals` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `temperature` varchar(20) DEFAULT NULL,
  `bp` varchar(20) DEFAULT NULL,
  `weight` varchar(20) DEFAULT NULL,
  `pulse` varchar(20) DEFAULT NULL,
  `spo2` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `encounter_id` int(11) DEFAULT NULL,
  `respiration` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vitals`
--

INSERT INTO `vitals` (`id`, `patient_id`, `temperature`, `bp`, `weight`, `pulse`, `spo2`, `created_at`, `encounter_id`, `respiration`) VALUES
(1, 12, '49.9', '120/80', '75', '56', NULL, '2026-02-05 08:39:27', NULL, 80),
(2, 12, '40', '30', '50', '180', NULL, '2026-02-05 09:54:18', 1, 50),
(3, 8, '23', '45', '60', '45', NULL, '2026-02-10 13:51:54', 20, 32),
(4, 9, '50', '118/17', '45', '56', NULL, '2026-02-24 13:44:43', 27, 45),
(5, 15, '3', '', '3', '', NULL, '2026-02-25 12:17:51', NULL, 0),
(6, 20, '38', '125/36', '75', '76', NULL, '2026-02-26 06:38:43', NULL, 18),
(7, 21, '38', '128/72', '', '', NULL, '2026-02-26 08:33:16', NULL, 0),
(8, 23, '23', '125/36', '45', '70', NULL, '2026-02-26 16:45:44', NULL, 16),
(9, 24, '35', '128/72', '102', '70', NULL, '2026-02-26 17:21:03', NULL, 14),
(10, 28, '37.7', '', '', '', NULL, '2026-02-27 05:57:39', NULL, 16),
(11, 31, '36.6', '105/65', '', '78', NULL, '2026-02-28 15:58:09', NULL, 16),
(12, 33, '37', '120/80', '', '72', NULL, '2026-02-28 16:48:24', NULL, 18),
(13, 34, '372', '', '12', '', NULL, '2026-02-28 17:14:29', NULL, 0),
(14, 35, '37', '', '', '', NULL, '2026-02-28 17:22:09', NULL, 0),
(15, 36, '35.5', '111/77', '', '72', NULL, '2026-03-01 15:52:10', NULL, 16),
(16, 37, '37', '', '54', '', NULL, '2026-03-02 08:31:50', NULL, 16),
(17, 39, '38', '', '', '72', NULL, '2026-03-02 18:13:49', NULL, 18),
(18, 42, '38', '', '', '', NULL, '2026-03-03 10:54:33', NULL, 18),
(19, 43, '38', '', '', '', NULL, '2026-03-03 10:54:34', NULL, 18),
(20, 44, '38', '120/70', '', '', NULL, '2026-03-03 17:38:08', NULL, 0),
(21, 45, '', '', '', '72', NULL, '2026-03-04 10:17:07', NULL, 18),
(22, 46, '', '', '2.9', '', NULL, '2026-03-04 13:36:14', NULL, 0),
(23, 47, '38', '123/77', '', '72', NULL, '2026-03-05 05:33:32', NULL, 16),
(24, 48, '38', '104/66', '', '79', NULL, '2026-03-05 11:20:33', NULL, 18),
(25, 49, '38', '', '', '95', NULL, '2026-03-05 11:46:49', NULL, 18),
(26, 50, '36.5', '120/80', '70', '72', NULL, '2026-03-05 12:56:09', NULL, 18),
(27, 51, '37', '120/70', '', '', NULL, '2026-03-07 10:03:49', NULL, 0),
(28, 52, '37', '123/74', '', '70', NULL, '2026-03-07 15:54:12', NULL, 16),
(29, 53, '38', '120/80', '', '72', NULL, '2026-03-08 05:54:10', NULL, 16),
(30, 54, '38', '120/80', '', '72', NULL, '2026-03-08 11:12:03', NULL, 16),
(31, 55, '38', '120/70', '62', '72', NULL, '2026-03-08 13:30:20', NULL, 16),
(32, 56, '38', '120/80', '', '', NULL, '2026-03-08 18:11:55', NULL, 16),
(33, 57, '37', '122/80', '', '', NULL, '2026-03-08 20:06:35', NULL, 16),
(34, 58, '38', '', '', '72', NULL, '2026-03-09 01:13:39', NULL, 16),
(35, 59, '38', '120/70', '70', '72', NULL, '2026-03-09 08:02:28', NULL, 16),
(36, 60, '36', '102/78', '', '72', NULL, '2026-03-10 18:16:27', NULL, 16),
(37, 61, '38', '1', '', '', NULL, '2026-03-11 07:39:10', NULL, 0),
(38, 62, '37', '120/78', '54', '72', NULL, '2026-03-12 06:24:45', NULL, 16),
(39, 63, '38', '120/72', '70', '72', NULL, '2026-03-12 08:45:05', NULL, 16),
(40, 64, '38', '120/80', '', '72', NULL, '2026-03-12 12:38:04', NULL, 16),
(41, 65, '36', '', '', '72', NULL, '2026-03-12 12:56:24', NULL, 16),
(42, 66, '36', '', '', '', NULL, '2026-03-12 15:31:15', NULL, 16),
(43, 70, '38', '', '', '', NULL, '2026-03-13 15:39:27', NULL, 16),
(44, 72, '37.9', '', '', '', NULL, '2026-03-13 19:18:50', NULL, 16),
(45, 73, '38', '', '', '', NULL, '2026-03-14 11:26:30', NULL, 16),
(46, 74, '38', '120/80', '70', '', NULL, '2026-03-18 10:07:53', NULL, 16),
(47, 75, '38', '', '', '', NULL, '2026-03-18 13:56:19', NULL, 0),
(48, 76, '38', '', '', '', NULL, '2026-03-18 15:31:01', NULL, 16),
(49, 77, '39', '', '', '', NULL, '2026-03-18 16:04:56', NULL, 0),
(50, 78, '38', '', '', '', NULL, '2026-03-18 17:49:32', NULL, 16),
(51, 79, '38.5', '', '', '', NULL, '2026-03-19 01:00:13', NULL, 16),
(52, 80, '38', '', '', '', NULL, '2026-03-19 06:08:39', NULL, 0),
(53, 82, '', '125/68', '', '', NULL, '2026-03-22 17:32:13', NULL, 0),
(54, 83, '', '121/72', '', '90', NULL, '2026-03-23 12:36:46', NULL, 0),
(55, 85, '365', '120/69', '', '', NULL, '2026-03-24 06:33:00', NULL, 0),
(56, 86, '36', '120/80', '75', '72', '14', '2026-03-24 11:04:43', NULL, 30),
(57, 87, '', '148/95', '', '88', NULL, '2026-03-24 16:17:33', NULL, 13),
(58, 88, '38', '', '', '', NULL, '2026-03-25 17:18:43', NULL, 16),
(59, 88, '38.1', '', '', '', '', '2026-03-25 17:27:17', NULL, 16),
(60, 34, '38', '', '12', '', '', '2026-03-26 09:17:07', NULL, 0),
(61, 34, '38', '', '', '', '', '2026-03-26 09:17:30', NULL, 0),
(62, 89, '38', '120/75', '70', '72', NULL, '2026-03-26 10:37:46', NULL, 16),
(63, 90, '37.9', '', '', '', NULL, '2026-03-26 11:20:37', NULL, 0),
(64, 91, '37.9', '', '', '', '', '2026-03-27 02:00:08', NULL, 0),
(65, 98, '38.4', '', '', '', NULL, '2026-03-28 15:42:32', NULL, 19),
(66, 99, '38', '', '', '72', NULL, '2026-03-28 23:34:24', NULL, 14),
(67, 100, '38.7', '', '', '', NULL, '2026-03-29 15:40:22', NULL, 16),
(68, 101, '36', '178/118', '', '', NULL, '2026-03-31 21:44:03', NULL, 16),
(69, 102, '38.2', '119/78', '', '100', '90', '2026-04-01 04:54:50', NULL, 16),
(70, 106, '38', '120/80', '', '', NULL, '2026-04-03 17:43:30', NULL, 0),
(71, 107, '38', '120/70', '', '', NULL, '2026-04-04 07:30:08', NULL, 0),
(72, 108, '', '114/73', '', '92', NULL, '2026-04-04 17:38:18', NULL, 16),
(73, 108, '36.8', '114/73', '', '92', '', '2026-04-04 17:40:10', NULL, 16),
(74, 111, '38', '120/80', '', '', NULL, '2026-04-05 14:44:15', NULL, 0),
(75, 112, '36.5', '120/80', '', '', NULL, '2026-04-06 09:14:21', NULL, 16),
(76, 113, '', '139/91', '', '', '', '2026-04-06 14:44:00', NULL, 0),
(77, 113, '38', '139/91', '', '', '', '2026-04-06 14:44:06', NULL, 0),
(78, 113, '38', '139/91', '', '', '', '2026-04-06 14:44:08', NULL, 0),
(79, 114, '37', '120/80', '', '', NULL, '2026-04-06 15:12:59', NULL, 16),
(80, 115, '37', '120/80', '', '', NULL, '2026-04-06 15:15:02', NULL, 16),
(85, 125, '36.6', '133/86', '', '85', NULL, '2026-04-09 09:52:12', NULL, 18),
(86, 126, '38', '', '', '', NULL, '2026-04-09 11:09:50', NULL, 16),
(87, 126, '38', '123/78', '', '', '', '2026-04-09 11:11:41', NULL, 16),
(88, 127, '38', '', '70', '', NULL, '2026-04-09 12:10:26', NULL, 16),
(89, 129, '37', '', '', '', NULL, '2026-04-10 13:45:03', NULL, 0),
(93, 138, '36', '120/70', '75', '72', NULL, '2026-04-13 07:41:46', NULL, 18),
(94, 139, '37', '', '', '', NULL, '2026-04-13 14:11:56', NULL, 0),
(95, 140, '38', '', '', '', NULL, '2026-04-16 16:20:56', NULL, 0),
(96, 141, '38', '', '70', '', NULL, '2026-04-17 13:23:42', NULL, 0),
(97, 142, '38', '120/ 80', '70', '', NULL, '2026-04-17 15:00:52', NULL, 0),
(98, 143, '38.8', '', '70', '', NULL, '2026-04-18 12:52:22', NULL, 16),
(99, 145, '38', '', '', '', NULL, '2026-04-23 10:20:11', NULL, 0),
(100, 146, '38', '', '', '', NULL, '2026-04-24 08:58:05', NULL, 0),
(101, 147, '38', '', '', '', NULL, '2026-04-25 18:16:22', NULL, 16),
(102, 148, '38', '', '', '', NULL, '2026-04-26 12:52:33', NULL, 0),
(103, 149, '38', '', '', '', NULL, '2026-04-26 12:52:34', NULL, 0),
(104, 150, '36', '120/80', '70', '', NULL, '2026-04-28 10:26:10', NULL, 16),
(105, 151, '36', '120/70', '', '', NULL, '2026-04-28 10:41:14', NULL, 0),
(106, 152, '36', '125/76', '70', '', NULL, '2026-04-29 16:51:05', NULL, 16),
(107, 154, '36.5', '127/76', '', '72', NULL, '2026-05-02 15:57:20', NULL, 17),
(108, 155, '38', '', '', '', NULL, '2026-05-03 11:37:24', NULL, 0),
(109, 157, '38', '', '', '', NULL, '2026-05-03 12:32:14', NULL, 0),
(110, 158, '36', '12/80', '', '', NULL, '2026-05-03 15:34:19', NULL, 16),
(111, 159, '38', '', '', '', NULL, '2026-05-04 14:25:28', NULL, 0),
(112, 160, '37', '', '', '', NULL, '2026-05-04 14:36:00', NULL, 0),
(113, 161, '38', '115/57', '', '', NULL, '2026-05-07 17:52:51', NULL, 0),
(114, 162, '37.4', '', '6.4', '', NULL, '2026-05-08 07:19:11', NULL, 0),
(115, 164, '38', '', '', '', NULL, '2026-05-12 14:41:07', NULL, 0),
(116, 165, '38', '', '', '', NULL, '2026-05-12 14:41:08', NULL, 0),
(117, 166, '38', '', '', '', NULL, '2026-05-12 14:41:09', NULL, 0),
(118, 167, '38', '', '13', '', NULL, '2026-05-13 06:30:46', NULL, 16),
(119, 167, '387.8', '', '13', '', '', '2026-05-13 06:33:26', NULL, 16),
(120, 168, '37', '', '70', '72', NULL, '2026-05-16 08:23:23', NULL, 16),
(121, 169, '37', '120/80', '70', '', NULL, '2026-05-16 08:25:41', NULL, 16),
(122, 170, '36.5', '122 55', '', '', NULL, '2026-05-21 19:44:39', NULL, 16),
(123, 170, '36.5', '122/52', '', '', '', '2026-05-21 20:09:31', NULL, 16),
(124, 171, '', '', '', '', NULL, '2026-05-24 18:06:02', NULL, 17),
(125, 172, '238', '', '17', '', NULL, '2026-05-29 13:03:05', NULL, 16),
(126, 173, '', '112/78', '', '', NULL, '2026-05-30 15:12:45', NULL, 0),
(127, 174, '', '112/78', '', '', NULL, '2026-05-30 15:12:45', NULL, 0),
(128, 175, '', '112/78', '', '', NULL, '2026-05-30 15:12:45', NULL, 0),
(129, 176, '', '112/78', '', '', NULL, '2026-05-30 15:12:45', NULL, 0),
(130, 177, '', '112/78', '', '', NULL, '2026-05-30 15:12:45', NULL, 0),
(131, 178, '', '112/78', '', '', NULL, '2026-05-30 15:12:45', NULL, 0),
(132, 179, '', '', '13', '', NULL, '2026-05-31 09:08:35', NULL, 16),
(133, 179, '37.9', '', '13', '', '', '2026-05-31 09:13:04', NULL, 16),
(134, 179, '38.5', '', '13', '', '', '2026-05-31 09:13:19', NULL, 16),
(135, 179, '38.5', '', '13', '', '', '2026-05-31 09:13:21', NULL, 16),
(136, 180, '35.2', '', '', '', NULL, '2026-05-31 18:34:49', NULL, 16),
(137, 181, '', '', '', '', NULL, '2026-06-01 09:14:11', NULL, 16),
(138, 182, '38', '', '70', '', NULL, '2026-06-05 10:09:16', NULL, 16),
(139, 187, '38', '', '', '', NULL, '2026-06-09 06:20:49', NULL, 0),
(140, 188, '36', '', '', '', NULL, '2026-06-09 06:22:00', NULL, 16),
(141, 189, '38', '', '67', '', NULL, '2026-06-09 17:14:36', NULL, 0),
(142, 190, '38', '120/80', '70', '72', NULL, '2026-06-10 07:34:18', NULL, 16),
(143, 196, '', '', '', '', '', '2026-06-18 09:34:45', NULL, 0),
(144, 197, '36', '120/80', '70', '', NULL, '2026-06-20 05:13:27', NULL, 16),
(145, 198, '38.2', '', '', '', NULL, '2026-06-20 20:29:35', NULL, 0),
(146, 199, '37', '90/56', '', '', NULL, '2026-06-22 15:25:23', NULL, 0),
(147, 200, '38', '110/66', '', '', '', '2026-06-24 18:21:13', NULL, 0),
(148, 200, '38', '110/66', '', '114', '', '2026-06-24 18:21:40', NULL, 0),
(149, 200, '38', '110/66', '', '114', '', '2026-06-24 18:21:43', NULL, 0),
(150, 200, '38', '110/66', '', '114', '', '2026-06-24 18:21:44', NULL, 0),
(151, 203, '38', '', '', '', NULL, '2026-06-27 05:43:44', NULL, 18),
(152, 203, '38', '110/66', '', '78', '94', '2026-06-27 12:05:23', NULL, 18),
(153, 203, '38', '110/66', '', '78', '94', '2026-06-27 12:05:52', NULL, 18),
(154, 204, '36.7', '122/77', '', '76', '94', '2026-06-27 12:45:50', NULL, 0),
(155, 208, '', '97/56', '', '104', '', '2026-07-05 09:24:39', NULL, 0),
(156, 209, '36.2', '', '', '', NULL, '2026-07-10 07:11:59', NULL, 0),
(157, 210, '36.2', '', '', '', NULL, '2026-07-10 07:12:02', NULL, 0),
(158, 211, '36.2', '', '', '', NULL, '2026-07-10 07:12:02', NULL, 0),
(159, 212, '36.2', '', '', '', NULL, '2026-07-10 07:12:03', NULL, 0),
(160, 213, '36.2', '', '', '', NULL, '2026-07-10 07:12:03', NULL, 0),
(161, 214, '36.2', '', '', '', NULL, '2026-07-10 07:12:03', NULL, 0),
(162, 215, '38', '120/80', '', '', NULL, '2026-07-12 10:12:05', NULL, 16),
(163, 216, '37', '120/70', '70', '', NULL, '2026-07-12 10:45:10', NULL, 16),
(164, 217, '38', '', '', '', NULL, '2026-07-12 12:04:39', NULL, 0),
(165, 220, '38', '', '70', '', NULL, '2026-07-25 18:18:22', NULL, 16),
(166, 222, '38', '', '70', '', NULL, '2026-07-28 17:34:14', NULL, 0),
(167, 223, '38.5', '', '', '', NULL, '2026-07-30 15:14:26', NULL, 0),
(168, 223, '38.5', '', '', '', '', '2026-07-30 15:30:49', NULL, 0),
(169, 224, '38', '', '', '', NULL, '2026-08-01 12:16:18', NULL, 16),
(170, 226, '38', '', '70', '', NULL, '2026-08-05 18:24:56', NULL, 0),
(171, 227, '38', '', '7', '', NULL, '2026-08-05 19:22:09', NULL, 16),
(172, 228, '37', '', '49', '', NULL, '2026-08-06 08:55:14', NULL, 16),
(173, 229, '38.1', '', '', '', NULL, '2026-08-06 16:35:33', NULL, 0),
(174, 229, '38.1', '', '', '', '', '2026-08-06 16:35:46', NULL, 0),
(175, 230, '38.3', '', '', '', NULL, '2026-08-07 03:23:53', NULL, 0),
(176, 230, '38.3', '', '', '', '', '2026-08-07 03:26:10', NULL, 0),
(177, 231, '38', '', '70', '', NULL, '2026-08-08 19:24:45', NULL, 0),
(178, 231, '38.6', '', '70', '', '', '2026-08-08 19:26:19', NULL, 0),
(179, 231, '38.6', '', '70', '', '', '2026-08-08 19:26:25', NULL, 0),
(180, 232, '38', '', '70', '', NULL, '2026-08-11 17:33:54', NULL, 0),
(181, 233, '38', '', '70', '', NULL, '2026-08-11 17:36:25', NULL, 16),
(182, 234, '36.6', '', '7', '', NULL, '2026-08-12 13:12:01', NULL, 0),
(183, 235, '37', '', '', '', NULL, '2026-08-13 13:32:41', NULL, 0),
(184, 236, '37', '', '', '', NULL, '2026-08-14 05:26:22', NULL, 0),
(185, 237, '38.2', '', '', '', NULL, '2026-08-14 09:47:58', NULL, 0),
(186, 238, '38', '', '', '', NULL, '2026-08-14 12:44:11', NULL, 0),
(187, 240, '38.5', '', '', '', NULL, '2026-08-16 17:03:29', NULL, 0),
(188, 241, '37', '137/72', '', '98', NULL, '2026-08-19 06:45:23', NULL, 0),
(189, 242, '38', '', '', '', NULL, '2026-08-19 14:20:33', NULL, 0),
(190, 244, '38', '', '', '', NULL, '2026-08-21 18:13:06', NULL, 0),
(191, 245, '39.6', '', '', '', NULL, '2026-08-22 09:59:55', NULL, 0),
(192, 246, '38', '', '', '', NULL, '2026-08-23 07:23:21', NULL, 0),
(193, 247, '36.5', '120/76', '', '', NULL, '2026-08-25 04:47:43', NULL, 16),
(194, 248, '36', '120/80', '', '72', NULL, '2026-08-25 04:49:38', NULL, 16),
(195, 249, '38', '', '', '', NULL, '2026-08-25 05:19:36', NULL, 16),
(196, 250, '38', '', '', '', NULL, '2026-08-26 11:05:34', NULL, 0),
(197, 251, '37', '130/70', '', '', NULL, '2026-08-26 11:32:12', NULL, 16),
(198, 252, '38', '', '', '', NULL, '2026-08-26 14:19:31', NULL, 0),
(199, 253, '38', '120', '', '', NULL, '2026-08-26 16:43:15', NULL, 0),
(200, 253, '38', '122/78', '', '', '', '2026-08-26 16:45:04', NULL, 0),
(201, 254, '38', '', '', '', NULL, '2026-08-27 07:32:38', NULL, 0),
(202, 255, '38', '', '', '', NULL, '2026-08-27 08:39:57', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `walkin_customers`
--

CREATE TABLE `walkin_customers` (
  `id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `walkin_customers`
--

INSERT INTO `walkin_customers` (`id`, `full_name`, `phone`, `created_at`) VALUES
(3, 'v', NULL, '2026-02-10 09:55:14'),
(4, 'vb', NULL, '2026-02-10 09:55:28'),
(5, 'vb', NULL, '2026-02-10 09:57:36'),
(6, 'b', NULL, '2026-02-10 09:57:46'),
(7, 'walk', NULL, '2026-02-10 10:05:55'),
(8, 'c', NULL, '2026-02-23 14:15:25'),
(9, 'c', NULL, '2026-02-23 14:25:02'),
(10, 'v', NULL, '2026-02-23 14:33:25'),
(11, 'f', NULL, '2026-02-24 14:08:48'),
(12, 'f', NULL, '2026-02-24 14:12:10'),
(13, 'Walk-in', NULL, '2026-02-24 15:08:00'),
(14, 'Walk-in', NULL, '2026-02-25 12:55:46'),
(15, 'Walk-in', NULL, '2026-02-25 19:32:11'),
(16, 'Walk-in', NULL, '2026-02-25 19:33:14'),
(17, 'Walk-in', NULL, '2026-02-26 05:17:19'),
(18, 'Walk-in', NULL, '2026-02-26 05:17:58'),
(19, 'Walk-in', NULL, '2026-02-26 13:53:40'),
(20, 'Walk-in', NULL, '2026-02-26 13:55:06'),
(21, 'Walk-in', NULL, '2026-02-26 13:55:42'),
(22, 'Walk-in', NULL, '2026-02-26 13:57:37'),
(23, 'Walk-in', NULL, '2026-02-26 13:58:18'),
(24, 'Walk-in', NULL, '2026-02-26 13:59:05'),
(25, 'Walk-in', NULL, '2026-02-26 14:00:16'),
(26, 'Walk-in', NULL, '2026-02-26 14:01:57'),
(27, 'Walk-in', NULL, '2026-02-26 14:42:22'),
(28, 'Walk-in', NULL, '2026-02-26 16:58:54'),
(29, 'Walk-in', NULL, '2026-02-26 17:04:52'),
(30, 'Walk-in', NULL, '2026-02-26 17:27:18'),
(31, 'Walk-in', NULL, '2026-02-26 17:28:06'),
(32, 'Walk-in', NULL, '2026-02-26 18:53:45'),
(33, 'Walk-in', NULL, '2026-02-27 05:26:30'),
(34, 'Walk-in', NULL, '2026-02-27 14:08:58'),
(35, 'Walk-in', NULL, '2026-02-27 14:10:49'),
(36, 'Walk-in', NULL, '2026-02-27 19:14:23'),
(37, 'Walk-in', NULL, '2026-02-27 19:14:23'),
(38, 'Walk-in', NULL, '2026-02-27 19:15:08'),
(39, 'Walk-in', NULL, '2026-02-28 17:58:37'),
(40, 'Walk-in', NULL, '2026-02-28 18:00:10'),
(41, 'Walk-in', NULL, '2026-02-28 18:02:33'),
(42, 'Walk-in', NULL, '2026-03-01 08:44:41'),
(43, 'Walk-in', NULL, '2026-03-01 08:45:31'),
(44, 'Walk-in', NULL, '2026-03-01 09:32:01'),
(45, 'Walk-in', NULL, '2026-03-01 10:11:06'),
(46, 'Walk-in', NULL, '2026-03-01 10:23:31'),
(47, 'Walk-in', NULL, '2026-03-02 07:13:27'),
(48, 'Walk-in', NULL, '2026-03-02 07:14:09'),
(49, 'Walk-in', NULL, '2026-03-02 07:45:15'),
(50, 'Walk-in', NULL, '2026-03-02 07:45:26'),
(51, 'Walk-in', NULL, '2026-03-02 08:13:45'),
(52, 'Walk-in', NULL, '2026-03-02 11:05:37'),
(53, 'Walk-in', NULL, '2026-03-02 11:05:46'),
(54, 'Walk-in', NULL, '2026-03-02 11:08:45'),
(55, 'Walk-in', NULL, '2026-03-02 15:01:09'),
(56, 'Walk-in', NULL, '2026-03-02 15:02:25'),
(57, 'Walk-in', NULL, '2026-03-02 18:03:40'),
(58, 'Walk-in', NULL, '2026-03-03 06:30:02'),
(59, 'Walk-in', NULL, '2026-03-03 06:31:11'),
(60, 'Walk-in', NULL, '2026-03-03 06:32:54'),
(61, 'Walk-in', NULL, '2026-03-03 06:33:35'),
(62, 'Walk-in', NULL, '2026-03-03 06:34:32'),
(63, 'Walk-in', NULL, '2026-03-03 06:58:14'),
(64, 'Walk-in', NULL, '2026-03-03 06:58:42'),
(65, 'Walk-in', NULL, '2026-03-03 09:34:26'),
(66, 'Walk-in', NULL, '2026-03-04 17:39:26'),
(67, 'Walk-in', NULL, '2026-03-05 13:09:22'),
(68, 'Walk-in', NULL, '2026-03-05 13:16:22'),
(69, 'Walk-in', NULL, '2026-03-05 13:16:22'),
(70, 'Walk-in', NULL, '2026-03-05 13:17:08'),
(71, 'Walk-in', NULL, '2026-03-05 13:21:33'),
(72, 'Walk-in', NULL, '2026-03-05 14:09:35'),
(73, 'Walk-in', NULL, '2026-03-05 15:28:52'),
(74, 'Walk-in', NULL, '2026-03-05 15:39:53'),
(75, 'Walk-in', NULL, '2026-03-05 15:42:18'),
(76, 'Walk-in', NULL, '2026-03-07 07:53:15'),
(78, 'Walk-in', NULL, '2026-03-07 07:55:28'),
(79, 'Walk-in', NULL, '2026-03-07 13:03:58'),
(80, 'Walk-in', NULL, '2026-03-07 16:53:24'),
(81, 'Walk-in', NULL, '2026-03-08 14:45:51'),
(82, 'Walk-in', NULL, '2026-03-08 14:46:35'),
(83, 'Walk-in', NULL, '2026-03-08 14:46:43'),
(84, 'Walk-in', NULL, '2026-03-08 20:08:53'),
(85, 'Walk-in', NULL, '2026-03-08 20:09:17'),
(86, 'Walk-in', NULL, '2026-03-08 20:09:39'),
(87, 'Walk-in', NULL, '2026-03-08 20:10:56'),
(88, 'Walk-in', NULL, '2026-03-08 20:11:23'),
(89, 'Walk-in', NULL, '2026-03-08 20:11:34'),
(90, 'Walk-in', NULL, '2026-03-08 20:12:11'),
(91, 'Walk-in', NULL, '2026-03-08 20:12:44'),
(92, 'Walk-in', NULL, '2026-03-09 06:50:04'),
(93, 'Walk-in', NULL, '2026-03-09 06:50:32'),
(94, 'Walk-in', NULL, '2026-03-10 13:35:52'),
(95, 'Walk-in', NULL, '2026-03-10 13:36:26'),
(96, 'Walk-in', NULL, '2026-03-10 19:48:27'),
(97, 'Walk-in', NULL, '2026-03-10 19:52:19'),
(98, 'Walk-in', NULL, '2026-03-11 13:32:01'),
(99, 'Walk-in', NULL, '2026-03-11 13:32:01'),
(100, 'Walk-in', NULL, '2026-03-11 13:32:50'),
(101, 'Walk-in', NULL, '2026-03-11 15:10:42'),
(102, 'Walk-in', NULL, '2026-03-12 06:23:06'),
(103, 'Walk-in', NULL, '2026-03-12 15:35:52'),
(104, 'Walk-in', NULL, '2026-03-12 15:42:30'),
(105, 'Walk-in', NULL, '2026-03-13 06:25:47'),
(106, 'Walk-in', NULL, '2026-03-13 06:27:13'),
(107, 'Walk-in', NULL, '2026-03-13 06:28:05'),
(108, 'Walk-in', NULL, '2026-03-13 08:20:20'),
(109, 'Walk-in', NULL, '2026-03-13 08:20:45'),
(110, 'Walk-in', NULL, '2026-03-13 11:12:50'),
(111, 'Walk-in', NULL, '2026-03-13 11:13:08'),
(112, 'Walk-in', NULL, '2026-03-13 14:31:43'),
(113, 'Walk-in', NULL, '2026-03-13 14:32:47'),
(114, 'Walk-in', NULL, '2026-03-13 15:37:27'),
(115, 'Walk-in', NULL, '2026-03-13 15:37:45'),
(116, 'Walk-in', NULL, '2026-03-14 10:39:26'),
(117, 'Walk-in', NULL, '2026-03-14 10:40:22'),
(118, 'Walk-in', NULL, '2026-03-14 10:40:22'),
(119, 'Walk-in', NULL, '2026-03-14 10:40:58'),
(120, 'Walk-in', NULL, '2026-03-14 10:44:07'),
(121, 'Walk-in', NULL, '2026-03-14 13:38:03'),
(122, 'Walk-in', NULL, '2026-03-14 14:46:20'),
(123, 'Walk-in', NULL, '2026-03-14 14:46:37'),
(124, 'Walk-in', NULL, '2026-03-14 14:47:20'),
(125, 'Walk-in', NULL, '2026-03-14 16:39:13'),
(126, 'Walk-in', NULL, '2026-03-14 16:39:46'),
(127, 'Walk-in', NULL, '2026-03-14 16:40:40'),
(128, 'Walk-in', NULL, '2026-03-14 16:41:43'),
(129, 'Walk-in', NULL, '2026-03-17 03:52:40'),
(130, 'Walk-in', NULL, '2026-03-17 03:53:21'),
(131, 'Walk-in', NULL, '2026-03-17 03:54:04'),
(132, 'Walk-in', NULL, '2026-03-17 03:54:50'),
(133, 'Walk-in', NULL, '2026-03-17 03:56:06'),
(134, 'Walk-in', NULL, '2026-03-17 03:57:00'),
(135, 'Walk-in', NULL, '2026-03-17 03:58:10'),
(136, 'Walk-in', NULL, '2026-03-17 03:58:51'),
(137, 'Walk-in', NULL, '2026-03-17 03:59:53'),
(138, 'Walk-in', NULL, '2026-03-17 04:00:38'),
(139, 'Walk-in', NULL, '2026-03-17 09:03:31'),
(140, 'Walk-in', NULL, '2026-03-17 09:04:03'),
(141, 'Walk-in', NULL, '2026-03-17 18:36:23'),
(142, 'Walk-in', NULL, '2026-03-18 20:33:55'),
(143, 'Walk-in', NULL, '2026-03-19 16:45:22'),
(144, 'Walk-in', NULL, '2026-03-19 16:47:08'),
(145, 'Walk-in', NULL, '2026-03-19 18:04:17'),
(146, 'Walk-in', NULL, '2026-03-19 18:05:04'),
(147, 'Walk-in', NULL, '2026-03-25 12:02:15'),
(148, 'Walk-in', NULL, '2026-03-25 17:14:23'),
(149, 'Walk-in', NULL, '2026-03-25 17:40:27'),
(150, 'Walk-in', NULL, '2026-03-25 17:53:59'),
(151, 'Walk-in', NULL, '2026-03-26 08:15:57'),
(152, 'Walk-in', NULL, '2026-03-27 14:18:15'),
(153, 'Walk-in', NULL, '2026-03-27 18:20:35'),
(154, 'Walk-in', NULL, '2026-04-01 03:47:32'),
(155, 'Walk-in', NULL, '2026-04-01 03:56:10'),
(156, 'Walk-in', NULL, '2026-04-01 03:57:54'),
(157, 'Walk-in', NULL, '2026-04-01 16:37:51'),
(158, 'Walk-in', NULL, '2026-04-01 18:17:17'),
(159, 'Walk-in', NULL, '2026-04-02 04:03:22'),
(160, 'Walk-in', NULL, '2026-04-02 07:31:32'),
(161, 'Walk-in', NULL, '2026-04-02 16:09:07'),
(162, 'Walk-in', NULL, '2026-04-02 16:10:08'),
(163, 'Walk-in', NULL, '2026-04-03 15:09:59'),
(164, 'Walk-in', NULL, '2026-04-03 15:12:36'),
(165, 'Walk-in', NULL, '2026-04-03 15:13:27'),
(166, 'Walk-in', NULL, '2026-04-03 15:13:54'),
(167, 'Walk-in', NULL, '2026-04-05 20:12:01'),
(168, 'Walk-in', NULL, '2026-04-05 20:12:46'),
(169, 'Walk-in', NULL, '2026-04-05 20:14:35'),
(170, 'Walk-in', NULL, '2026-04-05 20:15:21'),
(171, 'Walk-in', NULL, '2026-04-05 20:15:54'),
(172, 'Walk-in', NULL, '2026-04-05 20:17:08'),
(173, 'Walk-in', NULL, '2026-04-05 20:17:46'),
(174, 'Walk-in', NULL, '2026-04-07 11:14:05'),
(175, 'Walk-in', NULL, '2026-04-07 11:14:38'),
(176, 'Walk-in', NULL, '2026-04-07 11:15:08'),
(177, 'Walk-in', NULL, '2026-04-07 11:15:57'),
(178, 'Walk-in', NULL, '2026-04-07 11:17:05'),
(179, 'Walk-in', NULL, '2026-04-07 14:04:04'),
(180, 'Walk-in', NULL, '2026-04-07 14:14:43'),
(181, 'Walk-in', NULL, '2026-04-07 19:35:43'),
(182, 'Walk-in', NULL, '2026-04-07 19:36:41'),
(183, 'Walk-in', NULL, '2026-04-08 08:59:58'),
(184, 'Walk-in', NULL, '2026-04-08 16:19:14'),
(185, 'Walk-in', NULL, '2026-04-08 16:20:08'),
(186, 'Walk-in', NULL, '2026-04-10 08:59:37'),
(187, 'Walk-in', NULL, '2026-04-10 09:00:00'),
(188, 'Walk-in', NULL, '2026-04-10 09:00:37'),
(189, 'Walk-in', NULL, '2026-04-10 09:01:16'),
(190, 'Walk-in', NULL, '2026-04-10 09:01:37'),
(191, 'Walk-in', NULL, '2026-04-10 09:02:07'),
(192, 'Walk-in', NULL, '2026-04-10 09:02:45'),
(193, 'Walk-in', NULL, '2026-04-10 16:56:01'),
(194, 'Walk-in', NULL, '2026-04-10 17:49:49'),
(195, 'Walk-in', NULL, '2026-04-10 17:50:28'),
(196, 'Walk-in', NULL, '2026-04-11 13:58:55'),
(197, 'Walk-in', NULL, '2026-04-11 17:47:19'),
(198, 'Walk-in', NULL, '2026-04-12 04:57:12'),
(199, 'Walk-in', NULL, '2026-04-12 09:16:02'),
(200, 'Walk-in', NULL, '2026-04-12 10:14:02'),
(201, 'Walk-in', NULL, '2026-04-12 14:24:20'),
(202, 'Walk-in', NULL, '2026-04-12 14:26:17'),
(203, 'Walk-in', NULL, '2026-04-12 16:56:43'),
(204, 'Walk-in', NULL, '2026-04-12 19:09:42'),
(205, 'Walk-in', NULL, '2026-04-13 18:15:13'),
(206, 'Walk-in', NULL, '2026-04-13 18:16:08'),
(207, 'Walk-in', NULL, '2026-04-14 08:56:52'),
(208, 'Walk-in', NULL, '2026-04-14 20:14:34'),
(218, 'Walk-in', NULL, '2026-04-15 14:11:01'),
(219, 'Walk-in', NULL, '2026-04-16 14:13:06'),
(221, 'Walk-in', NULL, '2026-04-17 15:51:14'),
(222, 'Walk-in', NULL, '2026-04-25 17:29:22'),
(223, 'Walk-in', NULL, '2026-04-25 17:29:44'),
(224, 'Walk-in', NULL, '2026-04-25 17:30:13'),
(225, 'Walk-in', NULL, '2026-04-25 17:30:43'),
(226, 'Walk-in', NULL, '2026-05-02 06:58:58'),
(227, 'Walk-in', NULL, '2026-06-14 07:25:24'),
(228, 'Walk-in', NULL, '2026-06-18 09:40:06'),
(229, 'Walk-in', NULL, '2026-06-27 13:04:42'),
(230, 'Walk-in', NULL, '2026-06-28 20:12:55'),
(231, 'Walk-in', NULL, '2026-07-02 17:48:27'),
(232, 'Walk-in', NULL, '2026-07-02 19:09:58'),
(233, 'Walk-in', NULL, '2026-07-18 16:22:34'),
(234, 'Walk-in', NULL, '2026-08-21 16:22:37');

-- --------------------------------------------------------

--
-- Table structure for table `walkin_sales`
--

CREATE TABLE `walkin_sales` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_phone` varchar(50) DEFAULT NULL,
  `total_paid` decimal(10,2) DEFAULT NULL,
  `payment_mode` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `walkin_sale_items`
--

CREATE TABLE `walkin_sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) DEFAULT NULL,
  `med_id` int(11) DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `walk_in_customers`
--

CREATE TABLE `walk_in_customers` (
  `id` int(11) NOT NULL,
  `name` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounting_entries`
--
ALTER TABLE `accounting_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `account` (`account`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `account_2` (`account`),
  ADD KEY `created_at_2` (`created_at`);

--
-- Indexes for table `admissions`
--
ALTER TABLE `admissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `admitted_by` (`admitted_by`);

--
-- Indexes for table `anc_visits`
--
ALTER TABLE `anc_visits`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `baby_details`
--
ALTER TABLE `baby_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_baby_delivery` (`delivery_id`);

--
-- Indexes for table `billing`
--
ALTER TABLE `billing`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `billing_items`
--
ALTER TABLE `billing_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `encounter_id` (`encounter_id`);

--
-- Indexes for table `bills`
--
ALTER TABLE `bills`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `claim_denials`
--
ALTER TABLE `claim_denials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_claim_denials_header` (`claim_id`),
  ADD KEY `fk_claim_denials_item` (`claim_item_id`);

--
-- Indexes for table `claim_headers`
--
ALTER TABLE `claim_headers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `claim_number` (`claim_number`),
  ADD KEY `fk_claim_header_coverage` (`patient_coverage_id`),
  ADD KEY `fk_claim_header_payer` (`payer_id`),
  ADD KEY `fk_claim_header_plan` (`plan_id`),
  ADD KEY `fk_claim_header_preauth` (`preauthorization_id`);

--
-- Indexes for table `claim_items`
--
ALTER TABLE `claim_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_claim_items_header` (`claim_id`);

--
-- Indexes for table `clinic_categories`
--
ALTER TABLE `clinic_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `consultations`
--
ALTER TABLE `consultations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `encounter_id` (`encounter_id`);

--
-- Indexes for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `diagnosis`
--
ALTER TABLE `diagnosis`
  ADD PRIMARY KEY (`diagnosis_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `diagnosis_master`
--
ALTER TABLE `diagnosis_master`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `diagnostics`
--
ALTER TABLE `diagnostics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `discharges`
--
ALTER TABLE `discharges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `discharged_by` (`discharged_by`);

--
-- Indexes for table `dispensations`
--
ALTER TABLE `dispensations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `drugs`
--
ALTER TABLE `drugs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `encounters`
--
ALTER TABLE `encounters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_category` (`category_id`);

--
-- Indexes for table `expense_categories`
--
ALTER TABLE `expense_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `external_referrals`
--
ALTER TABLE `external_referrals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_receipts`
--
ALTER TABLE `inventory_receipts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_no` (`invoice_no`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `encounter_id` (`encounter_id`);

--
-- Indexes for table `invoice_financial_allocations`
--
ALTER TABLE `invoice_financial_allocations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_invoice_allocations_payer` (`payer_id`),
  ADD KEY `fk_invoice_allocations_coverage` (`patient_coverage_id`),
  ADD KEY `fk_invoice_allocations_claim` (`claim_id`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `med_id` (`med_id`);

--
-- Indexes for table `invoice_payments`
--
ALTER TABLE `invoice_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_invoice_payments_payer` (`payer_id`),
  ADD KEY `fk_invoice_payments_coverage` (`patient_coverage_id`);

--
-- Indexes for table `invoice_payment_allocations`
--
ALTER TABLE `invoice_payment_allocations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_payment_allocations_payment` (`invoice_payment_id`),
  ADD KEY `fk_payment_allocations_invoice_split` (`invoice_financial_allocation_id`),
  ADD KEY `fk_payment_allocations_claim` (`claim_id`);

--
-- Indexes for table `lab_procedures`
--
ALTER TABLE `lab_procedures`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lab_requests`
--
ALTER TABLE `lab_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `encounter_id` (`encounter_id`),
  ADD KEY `procedure_id` (`procedure_id`);

--
-- Indexes for table `lab_results`
--
ALTER TABLE `lab_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lab_request_id` (`lab_test_id`);

--
-- Indexes for table `lab_tests`
--
ALTER TABLE `lab_tests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `requested_by` (`requested_by`),
  ADD KEY `fk_encounter` (`encounter_id`);

--
-- Indexes for table `lab_tests_master`
--
ALTER TABLE `lab_tests_master`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `maternity`
--
ALTER TABLE `maternity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `maternity_admissions`
--
ALTER TABLE `maternity_admissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `maternity_baby`
--
ALTER TABLE `maternity_baby`
  ADD PRIMARY KEY (`id`),
  ADD KEY `maternity_id` (`maternity_id`);

--
-- Indexes for table `maternity_billing`
--
ALTER TABLE `maternity_billing`
  ADD PRIMARY KEY (`id`),
  ADD KEY `maternity_id` (`maternity_id`);

--
-- Indexes for table `maternity_deliveries`
--
ALTER TABLE `maternity_deliveries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `maternity_delivery`
--
ALTER TABLE `maternity_delivery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `maternity_id` (`maternity_id`);

--
-- Indexes for table `maternity_profiles`
--
ALTER TABLE `maternity_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `maternity_records`
--
ALTER TABLE `maternity_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `maternity_visits`
--
ALTER TABLE `maternity_visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `maternity_id` (`maternity_id`),
  ADD KEY `idx_visit_type` (`visit_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `medications`
--
ALTER TABLE `medications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `newborns`
--
ALTER TABLE `newborns`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `patient_number` (`patient_number`);

--
-- Indexes for table `patient_allergies`
--
ALTER TABLE `patient_allergies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `patient_bills`
--
ALTER TABLE `patient_bills`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `patient_clinical_notes`
--
ALTER TABLE `patient_clinical_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_number` (`patient_number`);

--
-- Indexes for table `patient_coverages`
--
ALTER TABLE `patient_coverages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_patient_member` (`patient_id`,`payer_id`,`member_number`),
  ADD KEY `fk_patient_coverages_payer` (`payer_id`),
  ADD KEY `fk_patient_coverages_plan` (`plan_id`);

--
-- Indexes for table `patient_diagnosis`
--
ALTER TABLE `patient_diagnosis`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `patient_financial_accounts`
--
ALTER TABLE `patient_financial_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `patient_id` (`patient_id`),
  ADD KEY `fk_financial_account_coverage` (`current_coverage_id`),
  ADD KEY `fk_financial_account_payer` (`current_payer_id`),
  ADD KEY `fk_financial_account_plan` (`current_plan_id`);

--
-- Indexes for table `patient_history`
--
ALTER TABLE `patient_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `patient_notes`
--
ALTER TABLE `patient_notes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `patient_orders`
--
ALTER TABLE `patient_orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `patient_procedures`
--
ALTER TABLE `patient_procedures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `procedure_id` (`procedure_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `patient_queue`
--
ALTER TABLE `patient_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `patient_services`
--
ALTER TABLE `patient_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `patient_services_ibfk_2` (`service_id`);

--
-- Indexes for table `payers`
--
ALTER TABLE `payers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payer_code` (`payer_code`);

--
-- Indexes for table `payer_plans`
--
ALTER TABLE `payer_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_payer_plan_code` (`payer_id`,`plan_code`);

--
-- Indexes for table `payer_tariffs`
--
ALTER TABLE `payer_tariffs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_tariff_item` (`payer_id`,`plan_id`,`item_type`,`item_code`),
  ADD KEY `fk_payer_tariffs_plan` (`plan_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `pharmacy_counters`
--
ALTER TABLE `pharmacy_counters`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pharmacy_dispense`
--
ALTER TABLE `pharmacy_dispense`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prescription_id` (`prescription_id`),
  ADD KEY `med_id` (`med_id`);

--
-- Indexes for table `pharmacy_queue`
--
ALTER TABLE `pharmacy_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prescription_id` (`prescription_id`);

--
-- Indexes for table `pharmacy_sales`
--
ALTER TABLE `pharmacy_sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `idx_encounter_id` (`encounter_id`),
  ADD KEY `idx_invoice_id` (`invoice_id`),
  ADD KEY `idx_med_id` (`med_id`);

--
-- Indexes for table `pharmacy_sale_items`
--
ALTER TABLE `pharmacy_sale_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pharmacy_stock`
--
ALTER TABLE `pharmacy_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pharmacy_supplier` (`supplier_id`);

--
-- Indexes for table `pma__bookmark`
--
ALTER TABLE `pma__bookmark`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pma__central_columns`
--
ALTER TABLE `pma__central_columns`
  ADD PRIMARY KEY (`db_name`,`col_name`);

--
-- Indexes for table `pma__column_info`
--
ALTER TABLE `pma__column_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `db_name` (`db_name`,`table_name`,`column_name`);

--
-- Indexes for table `pma__designer_settings`
--
ALTER TABLE `pma__designer_settings`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__export_templates`
--
ALTER TABLE `pma__export_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `u_user_type_template` (`username`,`export_type`,`template_name`);

--
-- Indexes for table `pma__favorite`
--
ALTER TABLE `pma__favorite`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__history`
--
ALTER TABLE `pma__history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`,`db`,`table`,`timevalue`);

--
-- Indexes for table `pma__navigationhiding`
--
ALTER TABLE `pma__navigationhiding`
  ADD PRIMARY KEY (`username`,`item_name`,`item_type`,`db_name`,`table_name`);

--
-- Indexes for table `pma__pdf_pages`
--
ALTER TABLE `pma__pdf_pages`
  ADD PRIMARY KEY (`page_nr`),
  ADD KEY `db_name` (`db_name`);

--
-- Indexes for table `pma__recent`
--
ALTER TABLE `pma__recent`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__relation`
--
ALTER TABLE `pma__relation`
  ADD PRIMARY KEY (`master_db`,`master_table`,`master_field`),
  ADD KEY `foreign_field` (`foreign_db`,`foreign_table`);

--
-- Indexes for table `pma__savedsearches`
--
ALTER TABLE `pma__savedsearches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `u_savedsearches_username_dbname` (`username`,`db_name`,`search_name`);

--
-- Indexes for table `pma__table_coords`
--
ALTER TABLE `pma__table_coords`
  ADD PRIMARY KEY (`db_name`,`table_name`,`pdf_page_number`);

--
-- Indexes for table `pma__table_info`
--
ALTER TABLE `pma__table_info`
  ADD PRIMARY KEY (`db_name`,`table_name`);

--
-- Indexes for table `pma__table_uiprefs`
--
ALTER TABLE `pma__table_uiprefs`
  ADD PRIMARY KEY (`username`,`db_name`,`table_name`);

--
-- Indexes for table `pma__tracking`
--
ALTER TABLE `pma__tracking`
  ADD PRIMARY KEY (`db_name`,`table_name`,`version`);

--
-- Indexes for table `pma__userconfig`
--
ALTER TABLE `pma__userconfig`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__usergroups`
--
ALTER TABLE `pma__usergroups`
  ADD PRIMARY KEY (`usergroup`,`tab`,`allowed`);

--
-- Indexes for table `pma__users`
--
ALTER TABLE `pma__users`
  ADD PRIMARY KEY (`username`,`usergroup`);

--
-- Indexes for table `preauthorizations`
--
ALTER TABLE `preauthorizations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_number` (`request_number`),
  ADD KEY `fk_preauth_coverage` (`patient_coverage_id`),
  ADD KEY `fk_preauth_payer` (`payer_id`),
  ADD KEY `fk_preauth_plan` (`plan_id`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `encounter_id` (`encounter_id`);

--
-- Indexes for table `procedures`
--
ALTER TABLE `procedures`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `procedures_master`
--
ALTER TABLE `procedures_master`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `procedures_performed`
--
ALTER TABLE `procedures_performed`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `radiology_master`
--
ALTER TABLE `radiology_master`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `radiology_requests`
--
ALTER TABLE `radiology_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `radiology_tests`
--
ALTER TABLE `radiology_tests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `remittances`
--
ALTER TABLE `remittances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference_number` (`reference_number`),
  ADD KEY `fk_remittances_payer` (`payer_id`);

--
-- Indexes for table `remittance_items`
--
ALTER TABLE `remittance_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_remittance_items_header` (`remittance_id`),
  ADD KEY `fk_remittance_items_claim` (`claim_id`),
  ADD KEY `fk_remittance_items_claim_item` (`claim_item_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `services_master`
--
ALTER TABLE `services_master`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `service_master`
--
ALTER TABLE `service_master`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `service_packages`
--
ALTER TABLE `service_packages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `supplier_invoices`
--
ALTER TABLE `supplier_invoices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `treatments`
--
ALTER TABLE `treatments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_idx` (`patient_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `vendors`
--
ALTER TABLE `vendors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vitals`
--
ALTER TABLE `vitals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `encounter_id` (`encounter_id`);

--
-- Indexes for table `walkin_customers`
--
ALTER TABLE `walkin_customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `walkin_sales`
--
ALTER TABLE `walkin_sales`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `walkin_sale_items`
--
ALTER TABLE `walkin_sale_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `walk_in_customers`
--
ALTER TABLE `walk_in_customers`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounting_entries`
--
ALTER TABLE `accounting_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=677;

--
-- AUTO_INCREMENT for table `admissions`
--
ALTER TABLE `admissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `anc_visits`
--
ALTER TABLE `anc_visits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=240;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `baby_details`
--
ALTER TABLE `baby_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `billing`
--
ALTER TABLE `billing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=276;

--
-- AUTO_INCREMENT for table `billing_items`
--
ALTER TABLE `billing_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bills`
--
ALTER TABLE `bills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `claim_denials`
--
ALTER TABLE `claim_denials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `claim_headers`
--
ALTER TABLE `claim_headers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `claim_items`
--
ALTER TABLE `claim_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clinic_categories`
--
ALTER TABLE `clinic_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `consultations`
--
ALTER TABLE `consultations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deliveries`
--
ALTER TABLE `deliveries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `diagnosis`
--
ALTER TABLE `diagnosis`
  MODIFY `diagnosis_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `diagnosis_master`
--
ALTER TABLE `diagnosis_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `diagnostics`
--
ALTER TABLE `diagnostics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `discharges`
--
ALTER TABLE `discharges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dispensations`
--
ALTER TABLE `dispensations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `drugs`
--
ALTER TABLE `drugs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `encounters`
--
ALTER TABLE `encounters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=249;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `expense_categories`
--
ALTER TABLE `expense_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `external_referrals`
--
ALTER TABLE `external_referrals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_receipts`
--
ALTER TABLE `inventory_receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=629;

--
-- AUTO_INCREMENT for table `invoice_financial_allocations`
--
ALTER TABLE `invoice_financial_allocations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=911;

--
-- AUTO_INCREMENT for table `invoice_payments`
--
ALTER TABLE `invoice_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoice_payment_allocations`
--
ALTER TABLE `invoice_payment_allocations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_procedures`
--
ALTER TABLE `lab_procedures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_requests`
--
ALTER TABLE `lab_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `lab_results`
--
ALTER TABLE `lab_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_tests`
--
ALTER TABLE `lab_tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `lab_tests_master`
--
ALTER TABLE `lab_tests_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `maternity`
--
ALTER TABLE `maternity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `maternity_admissions`
--
ALTER TABLE `maternity_admissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maternity_baby`
--
ALTER TABLE `maternity_baby`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `maternity_billing`
--
ALTER TABLE `maternity_billing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maternity_deliveries`
--
ALTER TABLE `maternity_deliveries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maternity_delivery`
--
ALTER TABLE `maternity_delivery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `maternity_profiles`
--
ALTER TABLE `maternity_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maternity_records`
--
ALTER TABLE `maternity_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maternity_visits`
--
ALTER TABLE `maternity_visits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `medications`
--
ALTER TABLE `medications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `newborns`
--
ALTER TABLE `newborns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=256;

--
-- AUTO_INCREMENT for table `patient_allergies`
--
ALTER TABLE `patient_allergies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_bills`
--
ALTER TABLE `patient_bills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_clinical_notes`
--
ALTER TABLE `patient_clinical_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_coverages`
--
ALTER TABLE `patient_coverages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_diagnosis`
--
ALTER TABLE `patient_diagnosis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_financial_accounts`
--
ALTER TABLE `patient_financial_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_history`
--
ALTER TABLE `patient_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_notes`
--
ALTER TABLE `patient_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_orders`
--
ALTER TABLE `patient_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_procedures`
--
ALTER TABLE `patient_procedures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_queue`
--
ALTER TABLE `patient_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_services`
--
ALTER TABLE `patient_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=217;

--
-- AUTO_INCREMENT for table `payers`
--
ALTER TABLE `payers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payer_plans`
--
ALTER TABLE `payer_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payer_tariffs`
--
ALTER TABLE `payer_tariffs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `pharmacy_counters`
--
ALTER TABLE `pharmacy_counters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pharmacy_dispense`
--
ALTER TABLE `pharmacy_dispense`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pharmacy_queue`
--
ALTER TABLE `pharmacy_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pharmacy_sales`
--
ALTER TABLE `pharmacy_sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `pharmacy_sale_items`
--
ALTER TABLE `pharmacy_sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pharmacy_stock`
--
ALTER TABLE `pharmacy_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=309;

--
-- AUTO_INCREMENT for table `pma__bookmark`
--
ALTER TABLE `pma__bookmark`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__column_info`
--
ALTER TABLE `pma__column_info`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__export_templates`
--
ALTER TABLE `pma__export_templates`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__history`
--
ALTER TABLE `pma__history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__pdf_pages`
--
ALTER TABLE `pma__pdf_pages`
  MODIFY `page_nr` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__savedsearches`
--
ALTER TABLE `pma__savedsearches`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `preauthorizations`
--
ALTER TABLE `preauthorizations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1015;

--
-- AUTO_INCREMENT for table `procedures`
--
ALTER TABLE `procedures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `procedures_master`
--
ALTER TABLE `procedures_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `procedures_performed`
--
ALTER TABLE `procedures_performed`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `radiology_master`
--
ALTER TABLE `radiology_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `radiology_requests`
--
ALTER TABLE `radiology_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `radiology_tests`
--
ALTER TABLE `radiology_tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `remittances`
--
ALTER TABLE `remittances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `remittance_items`
--
ALTER TABLE `remittance_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `services_master`
--
ALTER TABLE `services_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `service_master`
--
ALTER TABLE `service_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_packages`
--
ALTER TABLE `service_packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=166;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `supplier_invoices`
--
ALTER TABLE `supplier_invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `treatments`
--
ALTER TABLE `treatments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `vendors`
--
ALTER TABLE `vendors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `vitals`
--
ALTER TABLE `vitals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=203;

--
-- AUTO_INCREMENT for table `walkin_customers`
--
ALTER TABLE `walkin_customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=237;

--
-- AUTO_INCREMENT for table `walkin_sales`
--
ALTER TABLE `walkin_sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `walkin_sale_items`
--
ALTER TABLE `walkin_sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `walk_in_customers`
--
ALTER TABLE `walk_in_customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admissions`
--
ALTER TABLE `admissions`
  ADD CONSTRAINT `admissions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `admissions_ibfk_2` FOREIGN KEY (`admitted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `claim_denials`
--
ALTER TABLE `claim_denials`
  ADD CONSTRAINT `fk_claim_denials_header` FOREIGN KEY (`claim_id`) REFERENCES `claim_headers` (`id`),
  ADD CONSTRAINT `fk_claim_denials_item` FOREIGN KEY (`claim_item_id`) REFERENCES `claim_items` (`id`);

--
-- Constraints for table `claim_headers`
--
ALTER TABLE `claim_headers`
  ADD CONSTRAINT `fk_claim_header_coverage` FOREIGN KEY (`patient_coverage_id`) REFERENCES `patient_coverages` (`id`),
  ADD CONSTRAINT `fk_claim_header_payer` FOREIGN KEY (`payer_id`) REFERENCES `payers` (`id`),
  ADD CONSTRAINT `fk_claim_header_plan` FOREIGN KEY (`plan_id`) REFERENCES `payer_plans` (`id`),
  ADD CONSTRAINT `fk_claim_header_preauth` FOREIGN KEY (`preauthorization_id`) REFERENCES `preauthorizations` (`id`);

--
-- Constraints for table `claim_items`
--
ALTER TABLE `claim_items`
  ADD CONSTRAINT `fk_claim_items_header` FOREIGN KEY (`claim_id`) REFERENCES `claim_headers` (`id`);

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `fk_category` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoice_financial_allocations`
--
ALTER TABLE `invoice_financial_allocations`
  ADD CONSTRAINT `fk_invoice_allocations_claim` FOREIGN KEY (`claim_id`) REFERENCES `claim_headers` (`id`),
  ADD CONSTRAINT `fk_invoice_allocations_coverage` FOREIGN KEY (`patient_coverage_id`) REFERENCES `patient_coverages` (`id`),
  ADD CONSTRAINT `fk_invoice_allocations_payer` FOREIGN KEY (`payer_id`) REFERENCES `payers` (`id`);

--
-- Constraints for table `invoice_payments`
--
ALTER TABLE `invoice_payments`
  ADD CONSTRAINT `fk_invoice_payments_coverage` FOREIGN KEY (`patient_coverage_id`) REFERENCES `patient_coverages` (`id`),
  ADD CONSTRAINT `fk_invoice_payments_payer` FOREIGN KEY (`payer_id`) REFERENCES `payers` (`id`);

--
-- Constraints for table `invoice_payment_allocations`
--
ALTER TABLE `invoice_payment_allocations`
  ADD CONSTRAINT `fk_payment_allocations_claim` FOREIGN KEY (`claim_id`) REFERENCES `claim_headers` (`id`),
  ADD CONSTRAINT `fk_payment_allocations_invoice_split` FOREIGN KEY (`invoice_financial_allocation_id`) REFERENCES `invoice_financial_allocations` (`id`),
  ADD CONSTRAINT `fk_payment_allocations_payment` FOREIGN KEY (`invoice_payment_id`) REFERENCES `invoice_payments` (`id`);

--
-- Constraints for table `patient_clinical_notes`
--
ALTER TABLE `patient_clinical_notes`
  ADD CONSTRAINT `patient_clinical_notes_ibfk_1` FOREIGN KEY (`patient_number`) REFERENCES `patients` (`patient_number`);

--
-- Constraints for table `patient_coverages`
--
ALTER TABLE `patient_coverages`
  ADD CONSTRAINT `fk_patient_coverages_payer` FOREIGN KEY (`payer_id`) REFERENCES `payers` (`id`),
  ADD CONSTRAINT `fk_patient_coverages_plan` FOREIGN KEY (`plan_id`) REFERENCES `payer_plans` (`id`);

--
-- Constraints for table `patient_financial_accounts`
--
ALTER TABLE `patient_financial_accounts`
  ADD CONSTRAINT `fk_financial_account_coverage` FOREIGN KEY (`current_coverage_id`) REFERENCES `patient_coverages` (`id`),
  ADD CONSTRAINT `fk_financial_account_payer` FOREIGN KEY (`current_payer_id`) REFERENCES `payers` (`id`),
  ADD CONSTRAINT `fk_financial_account_plan` FOREIGN KEY (`current_plan_id`) REFERENCES `payer_plans` (`id`);

--
-- Constraints for table `patient_services`
--
ALTER TABLE `patient_services`
  ADD CONSTRAINT `patient_services_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `patient_services_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services_master` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payer_plans`
--
ALTER TABLE `payer_plans`
  ADD CONSTRAINT `fk_payer_plans_payer` FOREIGN KEY (`payer_id`) REFERENCES `payers` (`id`);

--
-- Constraints for table `payer_tariffs`
--
ALTER TABLE `payer_tariffs`
  ADD CONSTRAINT `fk_payer_tariffs_payer` FOREIGN KEY (`payer_id`) REFERENCES `payers` (`id`),
  ADD CONSTRAINT `fk_payer_tariffs_plan` FOREIGN KEY (`plan_id`) REFERENCES `payer_plans` (`id`);

--
-- Constraints for table `pharmacy_queue`
--
ALTER TABLE `pharmacy_queue`
  ADD CONSTRAINT `pharmacy_queue_ibfk_1` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`);

--
-- Constraints for table `preauthorizations`
--
ALTER TABLE `preauthorizations`
  ADD CONSTRAINT `fk_preauth_coverage` FOREIGN KEY (`patient_coverage_id`) REFERENCES `patient_coverages` (`id`),
  ADD CONSTRAINT `fk_preauth_payer` FOREIGN KEY (`payer_id`) REFERENCES `payers` (`id`),
  ADD CONSTRAINT `fk_preauth_plan` FOREIGN KEY (`plan_id`) REFERENCES `payer_plans` (`id`);

--
-- Constraints for table `remittances`
--
ALTER TABLE `remittances`
  ADD CONSTRAINT `fk_remittances_payer` FOREIGN KEY (`payer_id`) REFERENCES `payers` (`id`);

--
-- Constraints for table `remittance_items`
--
ALTER TABLE `remittance_items`
  ADD CONSTRAINT `fk_remittance_items_claim` FOREIGN KEY (`claim_id`) REFERENCES `claim_headers` (`id`),
  ADD CONSTRAINT `fk_remittance_items_claim_item` FOREIGN KEY (`claim_item_id`) REFERENCES `claim_items` (`id`),
  ADD CONSTRAINT `fk_remittance_items_header` FOREIGN KEY (`remittance_id`) REFERENCES `remittances` (`id`);

--
-- Constraints for table `treatments`
--
ALTER TABLE `treatments`
  ADD CONSTRAINT `fk_treatment_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
