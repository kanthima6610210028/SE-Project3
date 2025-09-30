-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 28, 2025 at 02:10 PM
-- Server version: 5.7.24
-- PHP Version: 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `publication_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `approval`
--

CREATE TABLE `approval` (
  `App_id` int(11) NOT NULL,
  `Publication_id` int(11) NOT NULL,
  `Approved_by` int(11) NOT NULL,
  `Status` enum('Pending','Approved','Rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `Approved_at` timestamp NULL DEFAULT NULL,
  `Comment` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `approval`
--

INSERT INTO `approval` (`App_id`, `Publication_id`, `Approved_by`, `Status`, `Approved_at`, `Comment`) VALUES
(1, 1, 2, 'Approved', '2025-09-27 08:45:00', 'Well-written paper. Approved for publication.'),
(2, 2, 2, 'Approved', '2025-09-28 07:48:36', ''),
(3, 7, 2, 'Approved', '2025-09-28 10:39:04', ''),
(5, 4, 2, 'Rejected', '2025-09-28 13:44:24', ''),
(6, 5, 2, 'Rejected', '2025-09-28 13:44:26', ''),
(7, 6, 2, 'Approved', '2025-09-28 13:44:28', '');

-- --------------------------------------------------------

--
-- Table structure for table `auditlog`
--

CREATE TABLE `auditlog` (
  `Log_id` int(11) NOT NULL,
  `User_id` int(11) NOT NULL,
  `Publication_id` int(11) DEFAULT NULL,
  `Action_type` enum('add','edit','delete','approve','reject','rollback','login','logout') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Log_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Record_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `auditlog`
--

INSERT INTO `auditlog` (`Log_id`, `User_id`, `Publication_id`, `Action_type`, `Log_date`, `Record_id`) VALUES
(1, 1, NULL, 'login', '2025-09-28 02:55:00', 1),
(2, 4, NULL, 'login', '2025-09-28 03:15:00', 4),
(3, 1, 2, 'add', '2025-09-28 04:30:00', 2),
(4, 2, 1, 'approve', '2025-09-27 08:45:00', 1),
(5, 2, 2, 'approve', '2025-09-28 07:48:36', NULL),
(6, 2, 7, 'approve', '2025-09-28 10:39:04', NULL),
(7, 2, 4, 'rollback', '2025-09-28 13:44:24', NULL),
(8, 2, 5, 'reject', '2025-09-28 13:44:26', NULL),
(9, 2, 6, 'approve', '2025-09-28 13:44:28', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `authors`
--

CREATE TABLE `authors` (
  `Author_id` int(11) NOT NULL,
  `Name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Affiliation` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `authors`
--

INSERT INTO `authors` (`Author_id`, `Name`, `Affiliation`, `Email`) VALUES
(1, 'Assoc. Prof. Dr. Wanida Srichan', 'Faculty of Science, PSU', 'wanida.s@psu.ac.th'),
(2, 'Dr. Chatchai Wongsasri', 'Faculty of Engineering, CMU', 'chatchai.w@cmu.ac.th'),
(3, 'Prof. Dr. Siriporn Srisuwan', 'Faculty of Medicine, CU', 'siriporn.s@chula.ac.th');

-- --------------------------------------------------------

--
-- Table structure for table `author_publication`
--

CREATE TABLE `author_publication` (
  `Author_id` int(11) NOT NULL,
  `Publication_id` int(11) NOT NULL,
  `Role_in_pub` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `author_publication`
--

INSERT INTO `author_publication` (`Author_id`, `Publication_id`, `Role_in_pub`) VALUES
(1, 1, 'First Author'),
(2, 1, 'Co-author'),
(2, 2, 'First Author');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `Notification_id` int(11) NOT NULL,
  `User_id` int(11) NOT NULL,
  `Message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `Status` enum('Unread','Read') COLLATE utf8mb4_unicode_ci DEFAULT 'Unread',
  `Sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `publicationfile`
--

CREATE TABLE `publicationfile` (
  `File_id` int(11) NOT NULL,
  `File_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `File_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Publication_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `publicationfile`
--

INSERT INTO `publicationfile` (`File_id`, `File_path`, `File_type`, `Uploaded_at`, `Publication_id`) VALUES
(1, 'uploads/publication_2/pubfile_1759043564.docx', 'docx', '2025-09-28 07:12:44', 2),
(2, 'uploads/publication_1/paper_1_final.pdf', 'pdf', '2025-09-27 03:00:00', 1),
(3, 'uploads/publication_2/conference_submission.docx', 'docx', '2025-09-28 04:30:00', 2);

-- --------------------------------------------------------

--
-- Table structure for table `publications`
--

CREATE TABLE `publications` (
  `PubID` int(11) NOT NULL,
  `PubName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `PubDetail` text COLLATE utf8mb4_unicode_ci,
  `PubType` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `PubStatus` enum('Draft','Pending','Published','Rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'Draft',
  `PubDate` date DEFAULT NULL,
  `User_id` int(11) NOT NULL,
  `Created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `publications`
--

INSERT INTO `publications` (`PubID`, `PubName`, `PubDetail`, `PubType`, `PubStatus`, `PubDate`, `User_id`, `Created_at`) VALUES
(1, 'ระบบบริหารจัดการตีพิมพ์', 'วิชาSE', 'วารสาร', 'Draft', '2025-09-28', 1, '2025-09-27 17:09:27'),
(2, 'Seearr', 'asdafawa', 'Journal', 'Published', '2025-01-01', 1, '2025-09-28 07:12:44'),
(4, 'โนอา', 'กาก กดกดกด', 'Journal', 'Draft', '2025-01-01', 1, '2025-09-28 08:27:36'),
(5, 'โนอา', 'ไก่', 'Journal', 'Rejected', '2025-01-01', 1, '2025-09-28 08:28:10'),
(6, 'โนอา', 'ไก่', 'Journal', 'Published', '2025-01-01', 1, '2025-09-28 08:28:55'),
(7, 'Seearr', 'asdafawa', 'Journal', 'Published', '2025-01-01', 1, '2025-09-28 08:29:16');

--
-- Triggers `publications`
--
DELIMITER $$
CREATE TRIGGER `trg_pubdate_before_insert` BEFORE INSERT ON `publications` FOR EACH ROW BEGIN
  IF NEW.PubDate IS NULL THEN
    SET NEW.PubDate = CURDATE();
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `report`
--

CREATE TABLE `report` (
  `Report_id` int(11) NOT NULL,
  `Report_type_id` int(11) NOT NULL,
  `Created_by` int(11) NOT NULL,
  `Created_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Content` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reporttype`
--

CREATE TABLE `reporttype` (
  `Report_type_id` int(11) NOT NULL,
  `Report_type_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `User_Id` int(11) NOT NULL,
  `UserName` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `UserEmail` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `UserPass` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `UserRole` enum('Admin','Officer','Teacher') COLLATE utf8mb4_unicode_ci NOT NULL,
  `Created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`User_Id`, `UserName`, `UserEmail`, `UserPass`, `UserRole`, `Created_at`) VALUES
(1, 'Dr. Somsak', 'teacher@uni.ac.th', '123456', 'Teacher', '2025-09-27 08:34:37'),
(2, 'Officer Jane', 'officer@uni.ac.th', '123456', 'Officer', '2025-09-27 08:34:37'),
(4, 'Admin', 'admin@uni.ac.th', '123456', 'Admin', '2025-09-27 11:01:45');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `approval`
--
ALTER TABLE `approval`
  ADD PRIMARY KEY (`App_id`),
  ADD KEY `Publication_id` (`Publication_id`),
  ADD KEY `Approved_by` (`Approved_by`);

--
-- Indexes for table `auditlog`
--
ALTER TABLE `auditlog`
  ADD PRIMARY KEY (`Log_id`),
  ADD KEY `User_id` (`User_id`),
  ADD KEY `Publication_id` (`Publication_id`);

--
-- Indexes for table `authors`
--
ALTER TABLE `authors`
  ADD PRIMARY KEY (`Author_id`);

--
-- Indexes for table `author_publication`
--
ALTER TABLE `author_publication`
  ADD PRIMARY KEY (`Author_id`,`Publication_id`),
  ADD KEY `Publication_id` (`Publication_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`Notification_id`),
  ADD KEY `User_id` (`User_id`);

--
-- Indexes for table `publicationfile`
--
ALTER TABLE `publicationfile`
  ADD PRIMARY KEY (`File_id`),
  ADD KEY `Publication_id` (`Publication_id`);

--
-- Indexes for table `publications`
--
ALTER TABLE `publications`
  ADD PRIMARY KEY (`PubID`),
  ADD KEY `User_id` (`User_id`);

--
-- Indexes for table `report`
--
ALTER TABLE `report`
  ADD PRIMARY KEY (`Report_id`),
  ADD KEY `Report_type_id` (`Report_type_id`),
  ADD KEY `Created_by` (`Created_by`);

--
-- Indexes for table `reporttype`
--
ALTER TABLE `reporttype`
  ADD PRIMARY KEY (`Report_type_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`User_Id`),
  ADD UNIQUE KEY `UserEmail` (`UserEmail`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `approval`
--
ALTER TABLE `approval`
  MODIFY `App_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `auditlog`
--
ALTER TABLE `auditlog`
  MODIFY `Log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `authors`
--
ALTER TABLE `authors`
  MODIFY `Author_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `Notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `publicationfile`
--
ALTER TABLE `publicationfile`
  MODIFY `File_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `publications`
--
ALTER TABLE `publications`
  MODIFY `PubID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `report`
--
ALTER TABLE `report`
  MODIFY `Report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reporttype`
--
ALTER TABLE `reporttype`
  MODIFY `Report_type_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `User_Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `approval`
--
ALTER TABLE `approval`
  ADD CONSTRAINT `approval_ibfk_1` FOREIGN KEY (`Publication_id`) REFERENCES `publications` (`PubID`) ON DELETE CASCADE,
  ADD CONSTRAINT `approval_ibfk_2` FOREIGN KEY (`Approved_by`) REFERENCES `users` (`User_Id`);

--
-- Constraints for table `auditlog`
--
ALTER TABLE `auditlog`
  ADD CONSTRAINT `auditlog_ibfk_1` FOREIGN KEY (`User_id`) REFERENCES `users` (`User_Id`),
  ADD CONSTRAINT `auditlog_ibfk_2` FOREIGN KEY (`Publication_id`) REFERENCES `publications` (`PubID`);

--
-- Constraints for table `author_publication`
--
ALTER TABLE `author_publication`
  ADD CONSTRAINT `author_publication_ibfk_1` FOREIGN KEY (`Author_id`) REFERENCES `authors` (`Author_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `author_publication_ibfk_2` FOREIGN KEY (`Publication_id`) REFERENCES `publications` (`PubID`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`User_id`) REFERENCES `users` (`User_Id`) ON DELETE CASCADE;

--
-- Constraints for table `publicationfile`
--
ALTER TABLE `publicationfile`
  ADD CONSTRAINT `publicationfile_ibfk_1` FOREIGN KEY (`Publication_id`) REFERENCES `publications` (`PubID`) ON DELETE CASCADE;

--
-- Constraints for table `publications`
--
ALTER TABLE `publications`
  ADD CONSTRAINT `publications_ibfk_1` FOREIGN KEY (`User_id`) REFERENCES `users` (`User_Id`) ON DELETE CASCADE;

--
-- Constraints for table `report`
--
ALTER TABLE `report`
  ADD CONSTRAINT `report_ibfk_1` FOREIGN KEY (`Report_type_id`) REFERENCES `reporttype` (`Report_type_id`),
  ADD CONSTRAINT `report_ibfk_2` FOREIGN KEY (`Created_by`) REFERENCES `users` (`User_Id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
