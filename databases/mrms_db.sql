-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 30, 2026 at 05:11 PM
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
(1, 'Grand Opening — Meghdoot Resort', 'We are thrilled to announce the official launch of Meghdoot Resort Management System. Book your room online today!', 'uploads/announcements/grand_opening.pdf', 'general', 'booking.php', '2026-01-01 00:00:00', '2026-01-31 23:59:59'),
(2, 'Eid Special Offer — 20% Discount', 'Enjoy 20% off on all Deluxe and Suite rooms during Eid holidays. Limited seats available!', 'uploads/announcements/eid_offer.pdf', 'offer', 'rooms.php', '2026-03-25 00:00:00', '2026-04-05 23:59:59'),
(3, 'Suite 302 Maintenance Notice', 'Suite 302 will be under maintenance from March 5 to March 8. We apologize for the inconvenience.', 'uploads/announcements/maintenance_notice.pdf', 'alert', NULL, '2026-03-04 00:00:00', '2026-03-08 23:59:59'),
(4, 'New Food Menu Added', 'We have added 3 new items to our in-room dining menu. Check them out and place your order!', 'uploads/announcements/new_menu.pdf', 'general', 'food_menu.php', '2026-02-15 00:00:00', '2026-02-28 23:59:59');

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
  `booked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`booking_id`, `guest_id`, `room_id`, `check_in`, `check_out`, `status`, `transaction_id`, `pay_amount`, `due_amount`, `payment_status`, `booked_at`, `updated_at`) VALUES
(1, 4, 1, '2026-02-10', '2026-02-13', 'Checked-Out', 'SSLCZ_TEST_001', 7500.00, 0.00, 'paid', '2026-02-08 04:00:00', '2026-02-13 06:00:00'),
(2, 5, 3, '2026-02-15', '2026-02-18', 'Checked-Out', 'SSLCZ_TEST_002', 6000.00, 6000.00, 'partial', '2026-02-12 05:00:00', '2026-02-18 05:00:00'),
(3, 6, 5, '2026-03-01', '2026-03-04', 'Confirmed', 'SSLCZ_TEST_003', 6500.00, 13000.00, 'partial', '2026-02-25 03:00:00', '2026-02-25 03:00:00'),
(4, 4, 7, '2026-03-10', '2026-03-12', 'Pending', NULL, 0.00, 20000.00, 'unpaid', '2026-03-08 08:00:00', '2026-03-08 08:00:00'),
(5, 5, 2, '2026-03-20', '2026-03-22', 'Confirmed', 'SSLCZ_TEST_004', 2500.00, 2500.00, 'partial', '2026-03-18 04:00:00', '2026-03-18 04:00:00'),
(6, 15, 1, '2026-05-31', '2026-06-04', 'Pending', NULL, 10000.00, 10000.00, 'unpaid', '2026-05-30 09:10:26', '2026-05-30 09:10:26'),
(7, 15, 1, '2026-06-02', '2026-06-03', 'Pending', NULL, 2500.00, 2500.00, 'unpaid', '2026-05-30 09:10:44', '2026-05-30 09:10:44'),
(8, 15, 1, '2026-06-02', '2026-06-03', 'Pending', NULL, 2500.00, 2500.00, 'unpaid', '2026-05-30 09:10:55', '2026-05-30 09:10:55'),
(9, 15, 1, '2026-06-02', '2026-06-03', 'Pending', NULL, 2500.00, 2500.00, 'unpaid', '2026-05-30 09:12:36', '2026-05-30 09:12:36'),
(10, 15, 1, '2026-06-02', '2026-06-05', 'Pending', NULL, 7500.00, 7500.00, 'unpaid', '2026-05-30 09:40:48', '2026-05-30 09:40:48'),
(11, 15, 1, '2026-06-01', '2026-06-04', 'Pending', NULL, 7500.00, 7500.00, 'unpaid', '2026-05-30 15:09:43', '2026-05-30 15:09:43');

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
(1, 'Hilsa Fish Curry', 'Traditional Bengali hilsa with mustard sauce.', 350.00, 'Lunch', 'images/food/hilsa.jpg', 1),
(2, 'Resort Breakfast Set', 'Bread, egg, juice, and tea.', 250.00, 'Breakfast', 'images/food/breakfast.jpg', 1),
(3, 'Chicken Biryani', 'Aromatic rice with tender chicken pieces.', 320.00, 'Lunch', 'images/food/biryani.jpg', 1),
(4, 'Vegetable Khichuri', 'Classic comfort food with mixed vegetables.', 180.00, 'Dinner', 'images/food/khichuri.jpg', 1),
(5, 'Fresh Fruit Platter', 'Seasonal fruits from local farms.', 200.00, 'Snacks', 'images/food/fruits.jpg', 1),
(6, 'Beef Rezala', 'Slow-cooked beef in white gravy.', 400.00, 'Dinner', 'images/food/rezala.jpg', 1),
(7, 'Masala Tea', 'Spiced tea with milk.', 50.00, 'Beverages', 'images/food/tea.jpg', 1),
(8, 'Fresh Coconut Water', 'Chilled coconut water served fresh.', 80.00, 'Beverages', 'images/food/coconut.jpg', 0);

-- --------------------------------------------------------

--
-- Table structure for table `invoice`
--

CREATE TABLE `invoice` (
  `invoice_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `room_charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `service_charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `issued_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice`
--

INSERT INTO `invoice` (`invoice_id`, `booking_id`, `room_charge`, `service_charge`, `tax`, `total`, `issued_at`) VALUES
(1, 1, 7500.00, 750.00, 412.50, 8662.50, '2026-02-13 12:30:00'),
(2, 2, 12000.00, 520.00, 625.20, 13145.20, '2026-02-18 11:30:00');

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
(4, 5, 2500.00, 'SSLCommerz', 'partial', 'SSLCZ_TEST_004', '2026-03-18 10:30:00');

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
(1, '101', 'Single', 2500.00, 'Available', 'Cozy single room with garden view.', 'assets/images/rooms/single_101.jpg', 1, 1, '2026-01-01 02:00:00'),
(2, '102', 'Single', 2500.00, 'Booked', 'Single room with attached bathroom.', 'assets/images/rooms/single_102.jpg', 1, 1, '2026-01-01 02:00:00'),
(3, '103', 'Double', 4000.00, 'Available', 'Spacious double room with lake view.', 'assets/images/rooms/double_103.jpg', 1, 2, '2026-01-01 02:00:00'),
(4, '201', 'Double', 4000.00, 'Available', 'Double room with balcony.', 'assets/images/rooms/double_201.jpg', 2, 2, '2026-01-01 02:00:00'),
(5, '202', 'Deluxe', 6500.00, 'Available', 'Deluxe room with premium amenities.', 'assets/images/rooms/deluxe_202.jpg', 2, 2, '2026-01-01 02:00:00'),
(6, '203', 'Deluxe', 6500.00, 'Under Maintenance', 'Deluxe room — currently under maintenance.', 'assets/images/rooms/deluxe_203.jpg', 2, 2, '2026-01-01 02:00:00'),
(7, '301', 'Suite', 10000.00, 'Available', 'Luxury suite with private jacuzzi.', 'assets/images/rooms/suite_301.jpg', 3, 4, '2026-01-01 02:00:00'),
(8, '302', 'Suite', 10000.00, 'Booked', 'Suite with panoramic resort view.', 'assets/images/rooms/suite_302.jpg', 3, 4, '2026-01-01 02:00:00');

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
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_request`
--

INSERT INTO `service_request` (`service_id`, `guest_id`, `booking_id`, `type`, `description`, `status`, `charge`, `requested_at`) VALUES
(1, 4, 1, 'food', 'Hilsa Fish Curry x2 and 2 Masala Tea.', 'Done', 750.00, '2026-02-10 07:00:00'),
(2, 4, 1, 'extra_towel', '2 extra towels needed.', 'Done', 0.00, '2026-02-11 03:00:00'),
(3, 5, 2, 'housekeeping', 'Room cleaning requested.', 'Done', 0.00, '2026-02-16 04:00:00'),
(4, 5, 2, 'food', 'Chicken Biryani x1, Fresh Fruit Platter x1.', 'Done', 520.00, '2026-02-17 06:30:00'),
(5, 6, 3, 'laundry', '3 shirts and 2 pants for laundry.', 'Processing', 200.00, '2026-03-02 02:00:00'),
(6, 6, 3, 'food', 'Resort Breakfast Set x2.', 'Pending', 500.00, '2026-03-03 01:30:00');

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
(15, 'kobir', 'kobir@gmail.com', '$2y$10$3MHGrQrluV.94kAB/vr2aOmW18M1b/isIaug7yx3aI1nwKQnTrIN.', 'guest', NULL, NULL, '2026-05-30 08:42:53');

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
  MODIFY `Announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `food_menu`
--
ALTER TABLE `food_menu`
  MODIFY `menu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `invoice`
--
ALTER TABLE `invoice`
  MODIFY `invoice_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `room`
--
ALTER TABLE `room`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `service_request`
--
ALTER TABLE `service_request`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `User_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

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
-- Constraints for table `service_request`
--
ALTER TABLE `service_request`
  ADD CONSTRAINT `service_ibfk_1` FOREIGN KEY (`guest_id`) REFERENCES `user` (`User_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_ibfk_2` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
