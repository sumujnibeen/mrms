-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 24, 2026 at 07:17 PM
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
-- Database: `mrms_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `department` varchar(50) NOT NULL,
  `role` varchar(80) NOT NULL,
  `salary` decimal(10,2) DEFAULT 0.00,
  `shift` enum('Morning','Evening','Night','Rotating') DEFAULT 'Morning',
  `status` enum('Active','On Leave','Resigned') DEFAULT 'Active',
  `joined_at` date NOT NULL DEFAULT curdate(),
  `photo` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `name`, `email`, `phone`, `department`, `role`, `salary`, `shift`, `status`, `joined_at`, `photo`, `address`, `created_at`) VALUES
(1, 'Rahim Uddin', 'rahim@mrms.com', '01711000001', 'Housekeeping', 'Head Housekeeper', 22000.00, 'Morning', 'Active', '2023-01-15', NULL, NULL, '2026-06-04 13:00:11'),
(2, 'Sumaiya Akter', 'sumaiya@mrms.com', '01812000002', 'Housekeeping', 'Room Cleaner', 14000.00, 'Morning', 'Active', '2023-03-10', NULL, NULL, '2026-06-04 13:00:11'),
(3, 'Karim Miah', 'karim@mrms.com', '01911000003', 'Kitchen', 'Head Chef', 35000.00, 'Morning', 'Active', '2022-08-20', NULL, NULL, '2026-06-04 13:00:11'),
(4, 'Nasrin Begum', 'nasrin@mrms.com', '01612000004', 'Kitchen', 'Cook', 18000.00, 'Evening', 'Active', '2023-06-01', NULL, NULL, '2026-06-04 13:00:11'),
(5, 'Jamal Hossain', 'jamal@mrms.com', '01711000005', 'Security', 'Security Guard', 16000.00, 'Night', 'Active', '2023-02-14', NULL, NULL, '2026-06-04 13:00:11'),
(6, 'Rony Islam', 'rony@mrms.com', '01812000006', 'Maintenance', 'Electrician', 20000.00, 'Morning', 'Active', '2023-04-05', NULL, NULL, '2026-06-04 13:00:11'),
(7, 'Fatema Khanam', 'fatema@mrms.com', '01911000007', 'Front Desk', 'Receptionist', 24000.00, 'Rotating', 'Active', '2022-11-01', NULL, NULL, '2026-06-04 13:00:11'),
(8, 'Arif Billah', 'arif@mrms.com', '01612000008', 'Restaurant', 'Waiter', 13000.00, 'Evening', 'On Leave', '2023-07-20', NULL, NULL, '2026-06-04 13:00:11'),
(9, 'Mitu Rani', 'mitu@mrms.com', '01711000009', 'Laundry', 'Laundry Staff', 12000.00, 'Morning', 'Active', '2024-01-10', NULL, NULL, '2026-06-04 13:00:11'),
(10, 'Sabbir Ahmed', 'sabbir@mrms.com', '01812000010', 'Management', 'Duty Manager', 40000.00, 'Rotating', 'Active', '2022-05-15', NULL, NULL, '2026-06-04 13:00:11'),
(11, 'krishna roy', 'kroy@gmail.com', '01378798139', 'Housekeeping', 'Room Cleaner', 15000.00, 'Morning', 'Active', '2026-06-04', NULL, 'srimongol,Mowlovibazar,sylhet', '2026-06-04 13:03:28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
