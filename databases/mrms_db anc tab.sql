-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 02, 2026 at 09:42 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mrms_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcement`
--

CREATE TABLE `announcement` (
  `Announcement_id` int(11) NOT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `Message` text DEFAULT NULL,
  `PDF_File` varchar(255) DEFAULT NULL,
  `Type` varchar(50) DEFAULT NULL,
  `Link` varchar(255) DEFAULT NULL,
  `Start_date` datetime DEFAULT NULL,
  `End_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcement`
--

INSERT INTO `announcement` (`Announcement_id`, `Title`, `Message`, `PDF_File`, `Type`, `Link`, `Start_date`, `End_date`) VALUES
(1, 'Grand Opening — Meghdoot Resort', 'We are thrilled to announce the official launch of Meghdoot Resort Management System. Book your room online today!', 'uploads/announcements/grand_opening.pdf', 'general', 'booking.php', '2026-05-01 00:00:00', '2026-07-31 23:59:59'),
(2, 'Summer Special — 20% Off Deluxe & Suite Rooms', 'Beat the heat with our Summer Special! Enjoy 20% off on all Deluxe and Suite bookings made before June 30. Use code SUMMER26 at checkout. Limited rooms available — book early!', 'uploads/announcements/eid_offer.pdf', 'offer', 'rooms.php', '2026-05-20 00:00:00', '2026-06-30 23:59:59'),
(3, 'Scheduled Maintenance — Pool Area', 'The resort swimming pool will be temporarily closed from June 3 to June 5, 2026 for routine maintenance and cleaning. All other amenities remain fully operational. We apologize for any inconvenience.', 'uploads/announcements/maintenance_notice.pdf', 'alert', NULL, '2026-05-28 00:00:00', '2026-06-06 23:59:59'),
(4, 'New Items Added to Our Food Menu', 'We have added 5 exciting new dishes to our in-room dining menu including Hilsa Fish Curry, Prawn Masala, and fresh seasonal beverages. Order directly from your room via the My Stay portal.', 'uploads/announcements/new_menu.pdf', 'general', 'food_menu.php', '2026-05-15 00:00:00', '2026-07-15 23:59:59'),
(5, 'Weekend Getaway Package — Book 2 Nights, Get 1 Free', 'Planning a weekend escape? Book any 2 consecutive nights from Friday to Sunday and get the third night completely free! Valid for Single and Double rooms. Offer valid throughout June 2026. Advance booking required.', NULL, 'offer', 'rooms.php', '2026-06-01 00:00:00', '2026-06-30 23:59:59'),
(6, 'Eid-ul-Adha Special Arrangements 2026', 'Meghdoot Resort will be hosting a special Eid-ul-Adha celebration this year with traditional decorations, special festive meals, and cultural programs for our guests. Rooms are filling up fast — book your stay now to avoid disappointment!', NULL, 'offer', 'rooms.php', '2026-05-25 00:00:00', '2026-06-20 23:59:59'),
(7, 'Reminder: Check-In & Check-Out Timings', 'Standard check-in time at Meghdoot Resort is 2:00 PM and check-out is 11:00 AM. Early check-in and late check-out may be arranged subject to availability — please contact the front desk. Online booking confirmation must be shown at reception.', NULL, 'general', NULL, '2026-05-01 00:00:00', '2026-12-31 23:59:59'),
(8, 'Complimentary Breakfast for Suite Bookings', 'All guests booked in our Suite rooms now receive a complimentary breakfast for two every morning of their stay. Breakfast is served from 7:30 AM to 10:30 AM in the dining area. Enjoy our freshly prepared traditional and continental options.', NULL, 'offer', 'room_details.php?id=1', '2026-05-20 00:00:00', '2026-08-31 23:59:59'),
(9, 'High-Speed Wi-Fi Now Available Resort-Wide', 'We are pleased to announce that high-speed fibre internet is now available across all rooms and common areas of Meghdoot Resort. Connection is free for all guests — login details will be provided at check-in. Enjoy seamless streaming and browsing during your stay.', NULL, 'general', NULL, '2026-05-10 00:00:00', '2026-12-31 23:59:59'),
(10, 'Pay Your Advance Online — Skip the Queue', 'Did you know you can pay your booking advance directly from our website via SSLCommerz? Complete your booking online and pay 30% advance securely — no need to visit the resort in advance. Log in to your account and go to My Bookings.', NULL, 'general', 'my_stay.php', '2026-05-01 00:00:00', '2026-12-31 23:59:59'),
(11, 'Daily Housekeeping Schedule Update', 'Housekeeping services are provided once daily between 10:00 AM and 1:00 PM. Guests who wish to skip housekeeping on a particular day may place a \"Do Not Disturb\" service request through the My Stay portal or inform the front desk before 9:30 AM.', NULL, 'general', NULL, '2026-05-15 00:00:00', '2026-12-31 23:59:59'),
(12, 'Group Booking Discount — 15% Off for 3+ Rooms', 'Planning a group trip, family reunion, or corporate retreat? Book 3 or more rooms at once and receive a flat 15% discount on your total bill. Contact our front desk directly at +880 1700-000000 or email info@meghdootresort.com to arrange your group booking.', NULL, 'offer', NULL, '2026-05-01 00:00:00', '2026-09-30 23:59:59');

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `booking_id` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `status` enum('Pending','Confirmed','Checked-In','Checked-Out','Cancelled') NOT NULL DEFAULT 'Pending',
  `transaction_id` varchar(100) DEFAULT NULL,
  `pay_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `due_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('paid','unpaid','partial') NOT NULL DEFAULT 'unpaid',
  `refund_status` enum('none','requested','approved','rejected') NOT NULL DEFAULT 'none',
  `refund_percent` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `booked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`booking_id`, `guest_id`, `room_id`, `check_in`, `check_out`, `status`, `transaction_id`, `pay_amount`, `due_amount`, `payment_status`, `refund_status`, `refund_percent`, `booked_at`, `updated_at`) VALUES
(1, 4, 1, '2026-02-10', '2026-02-13', 'Checked-Out', 'SSLCZ_TEST_001', 7500.00, 0.00, 'paid', 'none', 0, '2026-02-08 04:00:00', '2026-02-13 06:00:00'),
(2, 5, 3, '2026-02-15', '2026-02-18', 'Checked-Out', 'SSLCZ_TEST_002', 6000.00, 6000.00, 'partial', 'none', 0, '2026-02-12 05:00:00', '2026-02-18 05:00:00'),
(3, 6, 5, '2026-03-01', '2026-03-04', 'Checked-In', 'SSLCZ_TEST_003', 19500.00, 13000.00, 'partial', 'none', 0, '2026-02-25 03:00:00', '2026-06-02 01:15:02'),
(4, 4, 7, '2026-03-10', '2026-03-12', 'Pending', NULL, 20000.00, 20000.00, 'unpaid', 'none', 0, '2026-03-08 08:00:00', '2026-06-02 01:15:02'),
(5, 5, 2, '2026-03-20', '2026-03-22', 'Checked-Out', 'SSLCZ_TEST_004', 2500.00, 0.00, 'paid', 'none', 0, '2026-03-18 04:00:00', '2026-06-01 01:56:45'),
(6, 15, 1, '2026-05-31', '2026-06-04', 'Cancelled', NULL, 10000.00, 10000.00, 'unpaid', 'none', 0, '2026-05-30 09:10:26', '2026-05-30 17:12:55'),
(7, 15, 1, '2026-06-02', '2026-06-03', 'Cancelled', NULL, 2500.00, 2500.00, 'unpaid', 'none', 0, '2026-05-30 09:10:44', '2026-05-30 17:13:00'),
(8, 15, 1, '2026-06-02', '2026-06-03', 'Cancelled', NULL, 2500.00, 2500.00, 'unpaid', 'none', 0, '2026-05-30 09:10:55', '2026-05-30 17:12:50'),
(9, 15, 1, '2026-06-02', '2026-06-03', 'Cancelled', NULL, 2500.00, 2500.00, 'unpaid', 'none', 0, '2026-05-30 09:12:36', '2026-05-30 17:12:45'),
(10, 15, 1, '2026-06-02', '2026-06-05', 'Cancelled', NULL, 7500.00, 7500.00, 'unpaid', 'none', 0, '2026-05-30 09:40:48', '2026-05-30 17:11:39'),
(11, 15, 1, '2026-06-01', '2026-06-04', 'Cancelled', NULL, 7500.00, 7500.00, 'unpaid', 'none', 0, '2026-05-30 15:09:43', '2026-05-30 17:11:31'),
(12, 15, 1, '2026-06-06', '2026-06-12', 'Cancelled', 'MRMS_12_1780154743', 15000.00, 10500.00, 'partial', 'none', 0, '2026-05-30 15:25:34', '2026-06-02 01:15:02'),
(13, 15, 1, '2026-06-05', '2026-06-06', 'Cancelled', 'MRMS_13_1780154794', 2500.00, 1750.00, 'partial', 'none', 0, '2026-05-30 15:26:26', '2026-06-02 01:15:02'),
(14, 15, 1, '2026-06-01', '2026-06-02', 'Cancelled', 'MRMS_14_1780155165', 2500.00, 1750.00, 'partial', 'none', 0, '2026-05-30 15:32:38', '2026-06-02 01:15:02'),
(15, 15, 4, '2026-06-02', '2026-06-05', 'Cancelled', 'MRMS_15_1780155498', 12000.00, 8400.00, 'partial', 'none', 0, '2026-05-30 15:38:10', '2026-06-02 01:15:02'),
(16, 15, 3, '2026-05-31', '2026-06-03', 'Cancelled', 'MRMS_16_1780155698', 12000.00, 8400.00, 'partial', 'none', 0, '2026-05-30 15:41:30', '2026-06-02 01:15:02'),
(17, 15, 2, '2026-06-01', '2026-06-02', 'Cancelled', 'MRMS_17_1780160705', 2500.00, 1750.00, 'partial', 'none', 0, '2026-05-30 17:04:59', '2026-06-02 01:15:02'),
(18, 15, 1, '2026-06-01', '2026-06-02', 'Cancelled', 'MRMS_18_1780161208', 2500.00, 1750.00, 'partial', 'none', 0, '2026-05-30 17:13:22', '2026-06-02 01:15:02'),
(19, 15, 1, '2026-06-02', '2026-06-04', 'Cancelled', 'MRMS_19_1780211172', 5000.00, 3500.00, 'partial', 'none', 0, '2026-05-31 07:06:02', '2026-06-02 01:15:02'),
(20, 15, 1, '2026-06-02', '2026-06-03', 'Checked-Out', 'MRMS_20_1780213017', 1750.00, 0.00, 'paid', 'requested', 25, '2026-05-31 07:23:32', '2026-06-01 01:56:33'),
(21, 15, 3, '2026-06-02', '2026-06-03', 'Cancelled', 'MRMS_21_1780212983', 2400.00, 1600.00, 'partial', 'requested', 20, '2026-05-31 07:35:10', '2026-05-31 10:32:42'),
(22, 15, 2, '2026-06-02', '2026-06-20', 'Cancelled', 'MRMS_22_1780223493', 18000.00, 0.00, 'partial', 'none', 0, '2026-05-31 10:30:25', '2026-05-31 10:33:00'),
(23, 15, 5, '2026-06-26', '2026-07-03', 'Cancelled', 'MRMS_23_1780223708', 27300.00, 18200.00, 'partial', 'none', 0, '2026-05-31 10:34:48', '2026-05-31 10:38:27'),
(24, 15, 1, '2026-06-03', '2026-06-27', 'Checked-In', 'MRMS_24_1780279290', 36000.00, 0.00, 'paid', 'none', 0, '2026-06-01 02:01:13', '2026-06-02 01:15:02'),
(25, 15, 2, '2026-06-05', '2026-06-20', 'Cancelled', 'MRMS_25_1780279346', 22500.00, 15000.00, 'partial', 'none', 0, '2026-06-01 02:02:14', '2026-06-01 02:29:53'),
(26, 15, 3, '2026-06-02', '2026-06-06', 'Cancelled', 'MRMS_26_1780280685', 9600.00, 6400.00, 'partial', 'none', 0, '2026-06-01 02:23:36', '2026-06-01 02:29:33'),
(27, 15, 3, '2026-06-03', '2026-06-18', 'Pending', NULL, 60000.00, 60000.00, 'unpaid', 'none', 0, '2026-06-01 02:30:11', '2026-06-01 02:30:11'),
(28, 15, 3, '2026-06-03', '2026-06-06', 'Cancelled', NULL, 12000.00, 12000.00, 'unpaid', 'none', 0, '2026-06-01 02:30:26', '2026-06-01 11:31:07'),
(29, 15, 3, '2026-06-03', '2026-06-07', 'Cancelled', 'MRMS_29_1780313411', 16000.00, 11200.00, 'partial', 'none', 0, '2026-06-01 02:30:34', '2026-06-02 01:15:02'),
(30, 15, 2, '2026-06-02', '2026-06-05', 'Checked-In', 'MRMS_30_1780314151', 7500.00, 5250.00, 'partial', 'none', 0, '2026-06-01 11:42:20', '2026-06-02 01:15:02'),
(31, 15, 3, '2026-06-02', '2026-06-05', 'Cancelled', 'MRMS_31_1780329413', 12000.00, 8400.00, 'partial', 'none', 0, '2026-06-01 15:52:43', '2026-06-02 01:15:02'),
(32, 15, 7, '2026-06-02', '2026-06-03', 'Cancelled', 'MRMS_32_1780329528', 10000.00, 7000.00, 'partial', 'none', 0, '2026-06-01 15:57:57', '2026-06-02 01:15:02'),
(33, 15, 8, '2026-06-13', '2026-06-27', 'Cancelled', 'MRMS_33_1780362937', 42000.00, 98000.00, 'partial', 'none', 0, '2026-06-02 01:15:31', '2026-06-02 01:16:53'),
(34, 15, 8, '2026-06-20', '2026-06-27', 'Cancelled', 'MRMS_34_1780363039', 70000.00, 49000.00, 'partial', 'requested', 80, '2026-06-02 01:17:09', '2026-06-02 04:52:38');

-- --------------------------------------------------------

--
-- Table structure for table `food_menu`
--

CREATE TABLE `food_menu` (
  `menu_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `category` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `available` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `food_menu`
--

INSERT INTO `food_menu` (`menu_id`, `name`, `description`, `price`, `category`, `image`, `available`) VALUES
(1, 'Hilsa Fish Curry', 'Traditional Bengali hilsa with mustard sauce.', 350.00, 'Lunch', 'assets/images/food/hilsa.jpg', 1),
(2, 'Resort Breakfast Set', 'Bread, egg, juice, and tea.', 250.00, 'Breakfast', 'assets/images/food/breakfast.jpg', 1),
(3, 'Chicken Biryani', 'Aromatic rice with tender chicken pieces.', 320.00, 'Lunch', 'assets/images/food/biryani.jpg', 1),
(4, 'Vegetable Khichuri', 'Classic comfort food with mixed vegetables.', 180.00, 'Dinner', 'assets/images/food/khichuri.jpg', 1),
(5, 'Fresh Fruit Platter', 'Seasonal fruits from local farms.', 200.00, 'Snacks', 'assets/images/food/fruits.jpg', 1),
(6, 'Beef Rezala', 'Slow-cooked beef in white gravy.', 400.00, 'Dinner', 'assets/images/food/rezala.jpg', 1),
(7, 'Masala Tea', 'Spiced tea with milk.', 50.00, 'Beverages', 'assets/images/food/tea.jpg', 1),
(8, 'Fresh Coconut Water', 'Chilled coconut water served fresh.', 80.00, 'Beverages', 'assets/images/food/coconut.jpg', 0);

-- --------------------------------------------------------

--
-- Table structure for table `invoice`
--

CREATE TABLE `invoice` (
  `invoice_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `room_charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `service_charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `issued_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice`
--

INSERT INTO `invoice` (`invoice_id`, `booking_id`, `room_charge`, `service_charge`, `discount`, `tax`, `total`, `issued_at`) VALUES
(1, 1, 7500.00, 750.00, 0.00, 412.50, 8662.50, '2026-02-13 12:30:00'),
(2, 2, 12000.00, 520.00, 0.00, 625.20, 13145.20, '2026-02-18 11:30:00'),
(3, 20, 1750.00, 0.00, 0.00, 87.50, 1837.50, '2026-06-01 07:56:33'),
(4, 5, 5000.00, 0.00, 0.00, 250.00, 5250.00, '2026-06-01 07:56:45');

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `method` varchar(50) DEFAULT 'SSLCommerz',
  `status` enum('paid','unpaid','partial') NOT NULL DEFAULT 'unpaid',
  `transaction_id` varchar(100) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`payment_id`, `booking_id`, `amount`, `method`, `status`, `transaction_id`, `paid_at`) VALUES
(1, 1, 7500.00, 'SSLCommerz', 'paid', 'SSLCZ_TEST_001', '2026-02-08 10:30:00'),
(2, 2, 6000.00, 'SSLCommerz', 'partial', 'SSLCZ_TEST_002', '2026-02-12 11:30:00'),
(3, 3, 6500.00, 'SSLCommerz', 'partial', 'SSLCZ_TEST_003', '2026-02-25 09:30:00'),
(4, 5, 2500.00, 'SSLCommerz', 'partial', 'SSLCZ_TEST_004', '2026-03-18 10:30:00'),
(5, 12, 4500.00, 'SSLCommerz', 'partial', 'MRMS_12_1780154743', '2026-05-30 17:25:58'),
(6, 13, 750.00, 'SSLCommerz', 'partial', 'MRMS_13_1780154794', '2026-05-30 17:26:48'),
(7, 14, 750.00, 'SSLCommerz', 'partial', 'MRMS_14_1780155165', '2026-05-30 17:32:58'),
(8, 15, 3600.00, 'SSLCommerz', 'partial', 'MRMS_15_1780155498', '2026-05-30 17:38:32'),
(9, 16, 3600.00, 'SSLCommerz', 'partial', 'MRMS_16_1780155698', '2026-05-30 17:41:51'),
(10, 17, 750.00, 'SSLCommerz', 'partial', 'MRMS_17_1780160705', '2026-05-30 19:05:18'),
(11, 18, 750.00, 'SSLCommerz', 'partial', 'MRMS_18_1780161208', '2026-05-30 19:13:41'),
(12, 19, 1500.00, 'SSLCommerz', 'partial', 'MRMS_19_1780211172', '2026-05-31 09:06:26'),
(13, 20, 750.00, 'SSLCommerz', 'partial', 'MRMS_20_1780212219', '2026-05-31 09:23:52'),
(14, 21, 2400.00, 'SSLCommerz', 'partial', 'MRMS_21_1780212983', '2026-05-31 09:36:37'),
(15, 20, 1750.00, 'SSLCommerz', 'partial', 'MRMS_20_1780213017', '2026-05-31 09:37:09'),
(16, 22, 27000.00, 'SSLCommerz', 'partial', 'MRMS_22_1780223443', '2026-05-31 12:30:57'),
(17, 22, 18000.00, 'SSLCommerz', 'partial', 'MRMS_22_1780223493', '2026-05-31 12:31:46'),
(18, 23, 27300.00, 'SSLCommerz', 'partial', 'MRMS_23_1780223708', '2026-05-31 12:35:24'),
(19, 20, 87.50, 'Cash', 'paid', 'CASH_20_1780278993', '2026-06-01 07:56:33'),
(20, 5, 2750.00, 'Cash', 'paid', 'CASH_5_1780279005', '2026-06-01 07:56:45'),
(21, 24, 36000.00, 'SSLCommerz', 'partial', 'MRMS_24_1780279290', '2026-06-01 04:01:48'),
(22, 25, 22500.00, 'SSLCommerz', 'partial', 'MRMS_25_1780279346', '2026-06-01 04:02:52'),
(23, 26, 9600.00, 'SSLCommerz', 'partial', 'MRMS_26_1780280685', '2026-06-01 04:25:00'),
(24, 29, 4800.00, 'SSLCommerz', 'partial', 'MRMS_29_1780313411', '2026-06-01 13:30:32'),
(25, 30, 2250.00, 'SSLCommerz', 'partial', 'MRMS_30_1780314151', '2026-06-01 13:42:46'),
(26, 31, 3600.00, 'SSLCommerz', 'partial', 'MRMS_31_1780329413', '2026-06-01 17:57:07'),
(27, 32, 3000.00, 'SSLCommerz', 'partial', 'MRMS_32_1780329528', '2026-06-01 17:59:07'),
(28, 33, 42000.00, 'SSLCommerz', 'partial', 'MRMS_33_1780362937', '2026-06-02 03:15:52'),
(29, 34, 21000.00, 'SSLCommerz', 'paid', 'MRMS_34_1780363039', '2026-06-02 03:17:34');

-- --------------------------------------------------------

--
-- Table structure for table `review`
--

CREATE TABLE `review` (
  `review_id` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `target_type` enum('room','food') NOT NULL,
  `target_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `room`
--

CREATE TABLE `room` (
  `room_id` int(11) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `type` enum('Single','Double','Deluxe','Suite') NOT NULL,
  `price_per_night` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Available','Booked','Under Maintenance') NOT NULL DEFAULT 'Available',
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `floor` int(11) DEFAULT 1,
  `capacity` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room`
--

INSERT INTO `room` (`room_id`, `room_number`, `type`, `price_per_night`, `status`, `description`, `image`, `floor`, `capacity`, `created_at`) VALUES
(1, '101', 'Single', 2500.00, 'Booked', 'Cozy single room with garden view.', 'assets/images/rooms/single_101.jpg', 1, 1, '2026-01-01 02:00:00'),
(2, '102', 'Single', 2500.00, 'Booked', 'Single room with attached bathroom.', 'assets/images/rooms/single_102.jpg', 1, 1, '2026-01-01 02:00:00'),
(3, '103', 'Double', 4000.00, 'Available', 'Spacious double room with lake view.', 'assets/images/rooms/double_103.jpg', 1, 2, '2026-01-01 02:00:00'),
(4, '201', 'Double', 4000.00, 'Available', 'Double room with balcony.', 'assets/images/rooms/double_201.jpg', 2, 2, '2026-01-01 02:00:00'),
(5, '202', 'Deluxe', 6500.00, 'Booked', 'Deluxe room with premium amenities.', 'assets/images/rooms/deluxe_202.jpg', 2, 2, '2026-01-01 02:00:00'),
(6, '203', 'Deluxe', 6500.00, 'Under Maintenance', 'Deluxe room — currently under maintenance.', 'assets/images/rooms/deluxe_203.jpg', 2, 2, '2026-01-01 02:00:00'),
(7, '301', 'Suite', 10000.00, 'Available', 'Luxury suite with private jacuzzi.', 'assets/images/rooms/suite_301.jpg', 3, 4, '2026-01-01 02:00:00'),
(8, '302', 'Suite', 10000.00, 'Available', 'Suite with panoramic resort view.', 'assets/images/rooms/suite_302.jpg', 3, 4, '2026-01-01 02:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `service_request`
--

CREATE TABLE `service_request` (
  `service_id` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `type` enum('food','housekeeping','extra_towel','laundry','other') NOT NULL DEFAULT 'other',
  `description` text DEFAULT NULL,
  `status` enum('Pending','Processing','Done') NOT NULL DEFAULT 'Pending',
  `charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `added_by` enum('guest','receptionist') NOT NULL DEFAULT 'guest'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_request`
--

INSERT INTO `service_request` (`service_id`, `guest_id`, `booking_id`, `type`, `description`, `status`, `charge`, `requested_at`, `added_by`) VALUES
(1, 4, 1, 'food', 'Hilsa Fish Curry x2 and 2 Masala Tea.', 'Done', 750.00, '2026-02-10 07:00:00', 'guest'),
(2, 4, 1, 'extra_towel', '2 extra towels needed.', 'Done', 0.00, '2026-02-11 03:00:00', 'guest'),
(3, 5, 2, 'housekeeping', 'Room cleaning requested.', 'Done', 0.00, '2026-02-16 04:00:00', 'guest'),
(4, 5, 2, 'food', 'Chicken Biryani x1, Fresh Fruit Platter x1.', 'Done', 520.00, '2026-02-17 06:30:00', 'guest'),
(5, 6, 3, 'laundry', '3 shirts and 2 pants for laundry.', 'Processing', 200.00, '2026-03-02 02:00:00', 'guest'),
(6, 6, 3, 'food', 'Resort Breakfast Set x2.', 'Pending', 500.00, '2026-03-03 01:30:00', 'guest'),
(7, 15, 24, 'food', 'Masala Tea x3', 'Pending', 150.00, '2026-06-01 02:03:53', 'guest'),
(8, 15, 24, 'food', 'Fresh Fruit Platter x10', 'Pending', 2000.00, '2026-06-01 02:05:30', 'guest');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `User_id` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Role` enum('admin','receptionist','guest') NOT NULL DEFAULT 'guest',
  `Phone` varchar(20) DEFAULT NULL,
  `Photo` varchar(255) DEFAULT NULL,
  `Created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`User_id`, `Name`, `Email`, `Password`, `Role`, `Phone`, `Photo`, `Created_at`) VALUES
(1, 'Shafi', 'shafi@mrms.com', '$2y$10$G7Bym8WGbgwl2st/dLTOtOMcacD189ibcjlRa4/64nkSfghAedqbO', 'admin', '01700000001', 'images/user/shafi.jpg', '2026-01-01 02:00:00'),
(2, 'Prothoma Akter', 'prothoma@mrms.com', '$2y$10$YFqSh4HfR3RDa0ZLQmW8OulD/ujp7XkzChMQLq9y97gYZ3mWhYc5W', 'receptionist', '01700000002', 'images/user/prothoma.jpg', '2026-01-01 02:05:00'),
(3, 'Nadira Khanom', 'nadira@mrms.com', '$2y$10$YFqSh4HfR3RDa0ZLQmW8OulD/ujp7XkzChMQLq9y97gYZ3mWhYc5W', 'receptionist', '01700000003', 'images/user/nadira.jpg', '2026-01-01 02:10:00'),
(4, 'Rahim Uddin', 'rahim@gmail.com', '$2y$10$JfeqdhxcIP181gbqYyszaeTuwTBtt5jNvi9EJg80rOsTcBkZvBKlO', 'guest', '01811111111', 'images/user/rahim.jpg', '2026-01-05 03:00:00'),
(5, 'Tahmina Begum', 'tahmina@gmail.com', '$2y$10$JfeqdhxcIP181gbqYyszaeTuwTBtt5jNvi9EJg80rOsTcBkZvBKlO', 'guest', '01922222222', 'images/user/tahmina.jpg', '2026-01-06 04:00:00'),
(6, 'Karim Hossain', 'karim@gmail.com', '$2y$10$JfeqdhxcIP181gbqYyszaeTuwTBtt5jNvi9EJg80rOsTcBkZvBKlO', 'guest', '01633333333', 'images/user/karim.jpg', '2026-01-07 05:00:00'),
(15, 'kobir', 'kobir@gmail.com', '$2y$10$3MHGrQrluV.94kAB/vr2aOmW18M1b/isIaug7yx3aI1nwKQnTrIN.', 'guest', NULL, NULL, '2026-05-30 08:42:53'),
(16, 'Shafi', 'admin@gmail.com', '$2y$10$t/MrCh8km8t82yss6a7ePOTEkQL/hJuOY10Nbh3lSl5TBBis8/Z0O', 'admin', NULL, NULL, '2026-06-01 01:25:47'),
(17, 'Reception man', 'reception@gamil.com', '$2y$10$c63tubmarWJpSwCQLsop2uEJcjq9ydWcArzda0g6XIHI5VTCosmvu', 'receptionist', NULL, NULL, '2026-06-01 01:27:01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcement`
--
ALTER TABLE `announcement`
  ADD PRIMARY KEY (`Announcement_id`);

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `guest_id` (`guest_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `food_menu`
--
ALTER TABLE `food_menu`
  ADD PRIMARY KEY (`menu_id`);

--
-- Indexes for table `invoice`
--
ALTER TABLE `invoice`
  ADD PRIMARY KEY (`invoice_id`),
  ADD UNIQUE KEY `booking_id` (`booking_id`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `review`
--
ALTER TABLE `review`
  ADD PRIMARY KEY (`review_id`),
  ADD UNIQUE KEY `uq_guest_target` (`guest_id`,`target_type`,`target_id`),
  ADD KEY `idx_target` (`target_type`,`target_id`);

--
-- Indexes for table `room`
--
ALTER TABLE `room`
  ADD PRIMARY KEY (`room_id`),
  ADD UNIQUE KEY `room_number` (`room_number`);

--
-- Indexes for table `service_request`
--
ALTER TABLE `service_request`
  ADD PRIMARY KEY (`service_id`),
  ADD KEY `guest_id` (`guest_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`User_id`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcement`
--
ALTER TABLE `announcement`
  MODIFY `Announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `food_menu`
--
ALTER TABLE `food_menu`
  MODIFY `menu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `invoice`
--
ALTER TABLE `invoice`
  MODIFY `invoice_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `review`
--
ALTER TABLE `review`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `room`
--
ALTER TABLE `room`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `service_request`
--
ALTER TABLE `service_request`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `User_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `booking_ibfk_1` FOREIGN KEY (`guest_id`) REFERENCES `user` (`User_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `room` (`room_id`) ON DELETE CASCADE;

--
-- Constraints for table `invoice`
--
ALTER TABLE `invoice`
  ADD CONSTRAINT `invoice_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`) ON DELETE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`) ON DELETE CASCADE;

--
-- Constraints for table `review`
--
ALTER TABLE `review`
  ADD CONSTRAINT `fk_review_guest` FOREIGN KEY (`guest_id`) REFERENCES `user` (`User_id`) ON DELETE CASCADE;

--
-- Constraints for table `service_request`
--
ALTER TABLE `service_request`
  ADD CONSTRAINT `service_ibfk_1` FOREIGN KEY (`guest_id`) REFERENCES `user` (`User_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_ibfk_2` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
