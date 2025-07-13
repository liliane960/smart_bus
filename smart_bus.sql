-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 13, 2025 at 04:47 AM
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
-- Database: `smart_bus`
--

-- --------------------------------------------------------

--
-- Table structure for table `buses`
--

CREATE TABLE `buses` (
  `bus_id` int(11) NOT NULL,
  `plate_number` varchar(20) NOT NULL,
  `capacity` int(11) DEFAULT 18,
  `status` varchar(20) DEFAULT 'active',
  `driver_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buses`
--

INSERT INTO `buses` (`bus_id`, `plate_number`, `capacity`, `status`, `driver_id`) VALUES
(1, 'RAC123B', 18, 'active', 1),
(2, 'RAC122B', 12, 'active', 2),
(3, 'RAC122B', 12, 'active', 1);

-- --------------------------------------------------------

--
-- Table structure for table `bus_logs`
--

CREATE TABLE `bus_logs` (
  `id` int(11) NOT NULL,
  `bus_id` int(11) DEFAULT NULL,
  `event` varchar(50) DEFAULT NULL,
  `passenger_count` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bus_logs`
--

INSERT INTO `bus_logs` (`id`, `bus_id`, `event`, `passenger_count`, `status`, `created_at`) VALUES
(1, 1, 'entry', 5, 'normal', '2025-07-13 02:54:21'),
(2, 1, 'entry', 1, 'normal', '2025-07-13 02:57:43'),
(3, 1, 'entry', 2, 'normal', '2025-07-13 02:57:43'),
(4, 1, 'entry', 3, 'normal', '2025-07-13 02:57:44'),
(5, 1, 'entry', 4, 'normal', '2025-07-13 02:57:45'),
(6, 1, 'entry', 5, 'normal', '2025-07-13 02:57:45'),
(7, 1, 'entry', 6, 'normal', '2025-07-13 02:57:46'),
(8, 1, 'entry', 7, 'normal', '2025-07-13 02:57:46'),
(9, 1, 'entry', 8, 'normal', '2025-07-13 02:57:47'),
(10, 1, 'entry', 9, 'normal', '2025-07-13 02:57:48'),
(11, 1, 'entry', 10, 'normal', '2025-07-13 02:57:48'),
(12, 1, 'entry', 11, 'normal', '2025-07-13 02:57:49'),
(13, 1, 'entry', 12, 'normal', '2025-07-13 02:57:50'),
(14, 1, 'entry', 13, 'normal', '2025-07-13 02:57:50'),
(15, 1, 'entry', 14, 'normal', '2025-07-13 02:57:52'),
(16, 1, 'entry', 15, 'normal', '2025-07-13 02:57:53'),
(17, 1, 'entry', 16, 'normal', '2025-07-13 02:57:54'),
(18, 1, 'entry', 17, 'normal', '2025-07-13 02:57:55'),
(19, 1, 'entry', 18, 'full', '2025-07-13 02:57:56'),
(20, 1, 'entry', 19, 'full', '2025-07-13 02:58:02'),
(21, 1, 'entry', 20, 'full', '2025-07-13 02:58:03'),
(22, 1, 'exit', 19, 'normal', '2025-07-13 02:58:08'),
(23, 1, 'exit', 18, 'normal', '2025-07-13 02:58:09'),
(24, 1, 'exit', 17, 'normal', '2025-07-13 02:58:22'),
(25, 1, 'entry', 1, 'normal', '2025-07-13 02:58:59'),
(26, 1, 'entry', 2, 'normal', '2025-07-13 02:58:59'),
(27, 1, 'entry', 3, 'normal', '2025-07-13 02:59:00'),
(28, 1, 'entry', 4, 'normal', '2025-07-13 02:59:01'),
(29, 1, 'entry', 5, 'normal', '2025-07-13 02:59:01'),
(30, 1, 'entry', 6, 'normal', '2025-07-13 02:59:02'),
(31, 1, 'entry', 7, 'normal', '2025-07-13 02:59:02'),
(32, 1, 'entry', 8, 'normal', '2025-07-13 02:59:03'),
(33, 1, 'entry', 9, 'normal', '2025-07-13 02:59:04'),
(34, 1, 'entry', 10, 'normal', '2025-07-13 02:59:04'),
(35, 1, 'entry', 11, 'normal', '2025-07-13 02:59:05'),
(36, 1, 'entry', 12, 'normal', '2025-07-13 02:59:05'),
(37, 1, 'entry', 13, 'normal', '2025-07-13 02:59:06'),
(38, 1, 'entry', 14, 'normal', '2025-07-13 02:59:07'),
(39, 1, 'entry', 15, 'normal', '2025-07-13 02:59:07'),
(40, 1, 'entry', 16, 'normal', '2025-07-13 02:59:08'),
(41, 1, 'entry', 17, 'normal', '2025-07-13 02:59:09'),
(42, 1, 'entry', 18, 'full', '2025-07-13 02:59:09'),
(43, 1, 'exit', 17, 'normal', '2025-07-13 03:03:29'),
(44, 1, 'exit', 16, 'normal', '2025-07-13 03:06:33'),
(45, 1, 'entry', 17, 'normal', '2025-07-13 03:10:50'),
(46, 1, 'entry', 18, 'full', '2025-07-13 03:11:01'),
(47, 1, 'entry', 19, 'full', '2025-07-13 03:11:05'),
(48, 1, 'entry', 5, 'normal', '2025-07-13 04:29:29');

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `driver_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`driver_id`, `name`, `phone`) VALUES
(1, 'John Doe', '+250780000065'),
(2, 'claude', '+250783456845');

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `log_id` int(11) NOT NULL,
  `bus_id` int(11) DEFAULT NULL,
  `event` enum('entry','exit') DEFAULT NULL,
  `passenger_count` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `bus_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT NULL,
  `comment` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `passenger_logs`
--

CREATE TABLE `passenger_logs` (
  `log_id` int(11) NOT NULL,
  `bus_id` int(11) DEFAULT NULL,
  `event` enum('entry','exit') DEFAULT NULL,
  `count` int(11) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','police','driver') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `role`) VALUES
(1, 'admin', '$2y$10$6SS8vR9K0opXDCb6vfsqJeEN6mCGIBmrqC0tCKawC26fttXPoz5jC', 'admin'),
(2, 'liliane', '$2y$10$qv5BQkU1hzK6rCkT34K1VOE.M1pLrziuh/.N28pB6EBK33FfZzC6G', 'police'),
(3, 'jesus_driver', '$2y$10$qv5BQkU1hzK6rCkT34K1VOE.M1pLrziuh/.N28pB6EBK33FfZzC6G', 'driver'),
(4, 'admin1', '$2y$10$6SS8vR9K0opXDCb6vfsqJeEN6mCGIBmrqC0tCKawC26fttXPoz5jC', 'admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `buses`
--
ALTER TABLE `buses`
  ADD PRIMARY KEY (`bus_id`);

--
-- Indexes for table `bus_logs`
--
ALTER TABLE `bus_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`driver_id`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `bus_id` (`bus_id`);

--
-- Indexes for table `passenger_logs`
--
ALTER TABLE `passenger_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `bus_id` (`bus_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `buses`
--
ALTER TABLE `buses`
  MODIFY `bus_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `bus_logs`
--
ALTER TABLE `bus_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `driver_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `passenger_logs`
--
ALTER TABLE `passenger_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`bus_id`) REFERENCES `buses` (`bus_id`);

--
-- Constraints for table `passenger_logs`
--
ALTER TABLE `passenger_logs`
  ADD CONSTRAINT `passenger_logs_ibfk_1` FOREIGN KEY (`bus_id`) REFERENCES `buses` (`bus_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
