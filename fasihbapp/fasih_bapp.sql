-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 07, 2026 at 06:45 AM
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
-- Database: `fasih_bapp`
--

-- --------------------------------------------------------

--
-- Table structure for table `docx_files`
--

CREATE TABLE `docx_files` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `original_name` varchar(500) NOT NULL,
  `stored_path` text NOT NULL,
  `person_count` int(11) NOT NULL DEFAULT 0,
  `marker_count` int(11) NOT NULL DEFAULT 0,
  `status` varchar(50) NOT NULL DEFAULT 'uploaded',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `wilayah` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `persons`
--

CREATE TABLE `persons` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `docx_file_id` int(11) NOT NULL,
  `order_index` int(11) NOT NULL,
  `name` varchar(500) NOT NULL,
  `screenshot_id` int(11) DEFAULT NULL,
  `match_score` int(11) NOT NULL DEFAULT 0,
  `match_type` varchar(20) NOT NULL DEFAULT 'none'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `process_log`
--

CREATE TABLE `process_log` (
  `id` int(11) NOT NULL,
  `docx_file_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `message` text DEFAULT NULL,
  `output_path` text DEFAULT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `screenshots`
--

CREATE TABLE `screenshots` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `original_name` varchar(500) NOT NULL,
  `stored_path` text NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password_hash`, `created_at`) VALUES
(1, 'Rahmat', 'rmtgt2004@gmail.com', '$2y$10$WEHR5uWbrx0MKfmjOcIzsuexuwavd6xe.RYknuRVv5/kFO7QKMoJe', '2026-09-07 05:22:14');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `docx_files`
--
ALTER TABLE `docx_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_id` (`job_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `persons`
--
ALTER TABLE `persons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `docx_file_id` (`docx_file_id`);

--
-- Indexes for table `process_log`
--
ALTER TABLE `process_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `docx_file_id` (`docx_file_id`);

--
-- Indexes for table `screenshots`
--
ALTER TABLE `screenshots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_id` (`job_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama` (`nama`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `docx_files`
--
ALTER TABLE `docx_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `persons`
--
ALTER TABLE `persons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `process_log`
--
ALTER TABLE `process_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `screenshots`
--
ALTER TABLE `screenshots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `docx_files`
--
ALTER TABLE `docx_files`
  ADD CONSTRAINT `docx_files_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `persons`
--
ALTER TABLE `persons`
  ADD CONSTRAINT `persons_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `persons_ibfk_2` FOREIGN KEY (`docx_file_id`) REFERENCES `docx_files` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `process_log`
--
ALTER TABLE `process_log`
  ADD CONSTRAINT `process_log_ibfk_1` FOREIGN KEY (`docx_file_id`) REFERENCES `docx_files` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `screenshots`
--
ALTER TABLE `screenshots`
  ADD CONSTRAINT `screenshots_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
