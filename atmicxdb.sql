-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 15, 2026 at 12:36 AM
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
-- Database: `atmicxdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `asset`
--

CREATE TABLE `asset` (
  `Asset_ID` int(11) NOT NULL,
  `Client_ID` int(11) DEFAULT NULL,
  `Item_Name` varchar(255) DEFAULT NULL,
  `Type` varchar(100) DEFAULT NULL,
  `Serial_Number` varchar(100) DEFAULT NULL,
  `Date_Installed` date DEFAULT NULL,
  `Warranty_End` date DEFAULT NULL,
  `Status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client`
--

CREATE TABLE `client` (
  `Client_ID` int(11) NOT NULL,
  `Name` varchar(255) NOT NULL,
  `Contact_Num` varchar(50) DEFAULT NULL,
  `Email` varchar(255) DEFAULT NULL,
  `Address` text DEFAULT NULL,
  `Password_Hash` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `client`
--

INSERT INTO `client` (`Client_ID`, `Name`, `Contact_Num`, `Email`, `Address`, `Password_Hash`) VALUES
(1, 'Christian J. Barbas Jr.', '09604215897', 'testemail@gmail.com', 'Zone 2, Brgy. Handumnan, Bacolod City, Negros Occidental', '$argon2id$v=19$m=65536,t=4,p=1$QXJBczVDTW5LMzN2ODZ2Ug$1jP5Kndg+ShZbRLIwrVaya5gqo2PNuMrnSMypend0bw');

-- --------------------------------------------------------

--
-- Table structure for table `client_feedback`
--

CREATE TABLE `client_feedback` (
  `Feedback_ID` int(11) NOT NULL,
  `Client_ID` int(11) NOT NULL,
  `Rating` int(1) NOT NULL CHECK (`Rating` >= 1 and `Rating` <= 5),
  `Category` varchar(50) NOT NULL,
  `Message` text NOT NULL,
  `Status` enum('new','reviewed','responded','resolved') DEFAULT 'new',
  `Manager_Response` text DEFAULT NULL,
  `Responded_At` datetime DEFAULT NULL,
  `Created_At` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `client_feedback`
--

INSERT INTO `client_feedback` (`Feedback_ID`, `Client_ID`, `Rating`, `Category`, `Message`, `Status`, `Manager_Response`, `Responded_At`, `Created_At`) VALUES
(1, 1, 5, 'Product Quality', 'dgdsfhdsffgsdf', 'responded', 'wretwqerqwef', '2026-01-15 06:53:15', '2026-01-15 06:53:07');

-- --------------------------------------------------------

--
-- Table structure for table `customer_record`
--

CREATE TABLE `customer_record` (
  `Record_ID` int(11) NOT NULL,
  `Client_ID` int(11) DEFAULT NULL,
  `User_ID` int(11) DEFAULT NULL,
  `Service_Req` text DEFAULT NULL,
  `Service_Date` datetime DEFAULT NULL,
  `Status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `Item_ID` int(11) NOT NULL,
  `User_ID` int(11) DEFAULT NULL,
  `Item_Name` varchar(255) DEFAULT NULL,
  `Quantity` int(11) DEFAULT 0,
  `Branch` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`Item_ID`, `User_ID`, `Item_Name`, `Quantity`, `Branch`) VALUES
(1, 4, 'Haier Pro XL', 10, 'Manila HQ'),
(2, NULL, 'Haier Pro XL', 30, 'Bacolod Branch'),
(3, NULL, 'Haier Pro XL', 10, 'Cebu Branch');

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `Payment_ID` int(11) NOT NULL,
  `Client_ID` int(11) DEFAULT NULL,
  `Service_ID` int(11) DEFAULT NULL,
  `quotation_id` int(11) DEFAULT NULL,
  `service_request_id` int(11) DEFAULT NULL,
  `User_ID` int(11) DEFAULT NULL,
  `Amount_Paid` decimal(10,2) DEFAULT NULL,
  `Payment_Method` varchar(50) DEFAULT NULL,
  `Date_Paid` datetime DEFAULT NULL,
  `payment_date` datetime DEFAULT current_timestamp(),
  `verification_date` datetime DEFAULT NULL,
  `Status` enum('pending','approved','rejected','verified') DEFAULT 'pending',
  `Proof_Image` varchar(255) DEFAULT NULL,
  `proof_file_path` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`Payment_ID`, `Client_ID`, `Service_ID`, `quotation_id`, `service_request_id`, `User_ID`, `Amount_Paid`, `Payment_Method`, `Date_Paid`, `payment_date`, `verification_date`, `Status`, `Proof_Image`, `proof_file_path`) VALUES
(1, 1, NULL, 1, NULL, NULL, 630000.00, NULL, NULL, '2026-01-15 06:52:06', NULL, 'pending', NULL, 'uploads/payment_proofs/proof_1__1768431126.jpg'),
(2, 1, NULL, 4, NULL, NULL, 945000.00, NULL, NULL, '2026-01-15 07:00:47', NULL, 'pending', NULL, 'uploads/payment_proofs/proof_1__1768431647.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `quotation`
--

CREATE TABLE `quotation` (
  `Quotation_ID` int(11) NOT NULL,
  `Client_ID` int(11) DEFAULT NULL,
  `User_ID` int(11) DEFAULT NULL,
  `Package` varchar(255) DEFAULT NULL,
  `Amount` decimal(10,2) DEFAULT NULL,
  `Date_Issued` date DEFAULT NULL,
  `Status` varchar(50) DEFAULT NULL,
  `Delivery_Method` varchar(100) DEFAULT NULL,
  `Handling_Fee` decimal(10,2) DEFAULT NULL,
  `Proof_File` varchar(500) DEFAULT NULL,
  `Created_By` varchar(255) DEFAULT NULL,
  `service_request_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quotation`
--

INSERT INTO `quotation` (`Quotation_ID`, `Client_ID`, `User_ID`, `Package`, `Amount`, `Date_Issued`, `Status`, `Delivery_Method`, `Handling_Fee`, `Proof_File`, `Created_By`, `service_request_id`) VALUES
(1, 1, NULL, 'The Micro Start (2 Sets)', 630000.00, '2026-01-15', 'Verified', 'Standard Delivery', 30000.00, NULL, NULL, NULL),
(2, 1, 3, '2-Set Investor Package (₱450,000)', 465000.00, '2026-01-15', 'Approved', 'Standard Delivery', 15000.00, 'quote_proof_1768430507_7662.jpg', NULL, NULL),
(3, 1, NULL, 'The Essential Start (3 Sets)', 945000.00, '2026-01-15', 'Pending', 'Standard Delivery', 45000.00, NULL, NULL, NULL),
(4, 1, NULL, 'The Essential Start (3 Sets)', 945000.00, '2026-01-15', 'Scheduled', 'Standard Delivery', 45000.00, NULL, NULL, NULL),
(5, 1, NULL, 'The Essential Start (3 Sets)', 945000.00, '2026-01-15', 'Pending', 'Standard Delivery', 45000.00, NULL, NULL, NULL),
(6, 1, 3, 'The Essential Start - 3 Sets (₱900,000)', 915000.00, '2026-01-15', 'Approved', 'Standard Delivery', 15000.00, 'quote_proof_1768431598_1433.jpg', NULL, NULL),
(7, 1, NULL, 'Maintenance Service - asdadasdadad', 6000.00, '2026-01-15', 'Approved - Awaiting Payment', 'On-Site Service', NULL, NULL, NULL, 2);

-- --------------------------------------------------------

--
-- Table structure for table `service`
--

CREATE TABLE `service` (
  `Service_ID` int(11) NOT NULL,
  `Client_ID` int(11) DEFAULT NULL,
  `Priority` varchar(50) DEFAULT 'normal',
  `estimated_cost` decimal(10,2) DEFAULT 0.00,
  `scheduled_date` date DEFAULT NULL,
  `scheduled_time` time DEFAULT NULL,
  `assigned_team` varchar(255) DEFAULT NULL,
  `User_ID` int(11) DEFAULT NULL,
  `Quotation_ID` int(11) DEFAULT NULL,
  `Service_Date` datetime DEFAULT NULL,
  `Status` varchar(50) DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `date_requested` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service`
--

INSERT INTO `service` (`Service_ID`, `Client_ID`, `Priority`, `estimated_cost`, `scheduled_date`, `scheduled_time`, `assigned_team`, `User_ID`, `Quotation_ID`, `Service_Date`, `Status`, `type`, `description`, `location`, `date_requested`) VALUES
(1, 1, 'medium', 0.00, NULL, NULL, NULL, NULL, NULL, '2026-01-15 07:09:32', 'rejected_by_manager', 'maintenance', 'asdasadasda', 'Not specified', '2026-01-15 07:07:06'),
(2, 1, 'normal', 6000.00, '2026-01-15', '09:00:00', 'Team Alpha', NULL, NULL, '2026-01-15 07:15:18', 'scheduled', 'maintenance', 'asdadasdadad', 'Not specified', '2026-01-15 07:11:47');

-- --------------------------------------------------------

--
-- Table structure for table `service_item`
--

CREATE TABLE `service_item` (
  `ServiceItem_ID` int(11) NOT NULL,
  `Item_ID` int(11) DEFAULT NULL,
  `Service_ID` int(11) DEFAULT NULL,
  `Quantity_Used` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `User_ID` int(11) NOT NULL,
  `Name` varchar(255) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `Role` varchar(50) DEFAULT NULL,
  `Status` varchar(50) DEFAULT NULL,
  `Branch` varchar(100) DEFAULT NULL,
  `Assigned` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`User_ID`, `Name`, `PasswordHash`, `Role`, `Status`, `Branch`, `Assigned`, `email`, `reset_token`, `reset_expires`) VALUES
(3, 'blazeking5', '$argon2id$v=19$m=65536,t=2,p=1$cWo2aGg5SW1URGIxOXBzLw$V+s/FnnDkCmhVOS6obGACMj2ztsWVNgav6CwHqQTMZY', 'manager', NULL, NULL, NULL, 'hughz2004@gmail.com', NULL, NULL),
(4, 'blazeking123', '$argon2id$v=19$m=65536,t=2,p=1$aUFLb1g5bnNLa0VDODNpNg$4aNYLSgqAjKYrsiwLfBM6+WgKO+hOqig8ogekWFW3DY', 'manager', NULL, NULL, NULL, 'testemail@gmail.com', NULL, NULL),
(5, 'blazeking123', '$argon2id$v=19$m=65536,t=2,p=1$U1JIL3EwM2R5MDVQbndkYQ$YohVzYejH6PglF5Q2lUsqk8ykycvMTYfRuRxHvZD2zU', 'secretary', NULL, NULL, NULL, 'barbasgaming1@gmail.com', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `asset`
--
ALTER TABLE `asset`
  ADD PRIMARY KEY (`Asset_ID`),
  ADD KEY `Client_ID` (`Client_ID`);

--
-- Indexes for table `client`
--
ALTER TABLE `client`
  ADD PRIMARY KEY (`Client_ID`);

--
-- Indexes for table `client_feedback`
--
ALTER TABLE `client_feedback`
  ADD PRIMARY KEY (`Feedback_ID`),
  ADD KEY `Client_ID` (`Client_ID`),
  ADD KEY `Status` (`Status`),
  ADD KEY `Created_At` (`Created_At`);

--
-- Indexes for table `customer_record`
--
ALTER TABLE `customer_record`
  ADD PRIMARY KEY (`Record_ID`),
  ADD KEY `Client_ID` (`Client_ID`),
  ADD KEY `User_ID` (`User_ID`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`Item_ID`),
  ADD KEY `User_ID` (`User_ID`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`Payment_ID`),
  ADD KEY `Client_ID` (`Client_ID`),
  ADD KEY `Service_ID` (`Service_ID`),
  ADD KEY `User_ID` (`User_ID`),
  ADD KEY `fk_payment_quotation` (`quotation_id`),
  ADD KEY `fk_payment_service_request` (`service_request_id`),
  ADD KEY `idx_payment_status` (`Status`),
  ADD KEY `idx_payment_date` (`payment_date`);

--
-- Indexes for table `quotation`
--
ALTER TABLE `quotation`
  ADD PRIMARY KEY (`Quotation_ID`),
  ADD KEY `Client_ID` (`Client_ID`),
  ADD KEY `User_ID` (`User_ID`),
  ADD KEY `idx_quotation_status` (`Status`);

--
-- Indexes for table `service`
--
ALTER TABLE `service`
  ADD PRIMARY KEY (`Service_ID`),
  ADD KEY `Client_ID` (`Client_ID`),
  ADD KEY `User_ID` (`User_ID`),
  ADD KEY `Quotation_ID` (`Quotation_ID`),
  ADD KEY `idx_service_status` (`Status`);

--
-- Indexes for table `service_item`
--
ALTER TABLE `service_item`
  ADD PRIMARY KEY (`ServiceItem_ID`),
  ADD KEY `Item_ID` (`Item_ID`),
  ADD KEY `Service_ID` (`Service_ID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`User_ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `asset`
--
ALTER TABLE `asset`
  MODIFY `Asset_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `client`
--
ALTER TABLE `client`
  MODIFY `Client_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `client_feedback`
--
ALTER TABLE `client_feedback`
  MODIFY `Feedback_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customer_record`
--
ALTER TABLE `customer_record`
  MODIFY `Record_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `Item_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `Payment_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `quotation`
--
ALTER TABLE `quotation`
  MODIFY `Quotation_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `service`
--
ALTER TABLE `service`
  MODIFY `Service_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `service_item`
--
ALTER TABLE `service_item`
  MODIFY `ServiceItem_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `User_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `asset`
--
ALTER TABLE `asset`
  ADD CONSTRAINT `asset_ibfk_1` FOREIGN KEY (`Client_ID`) REFERENCES `client` (`Client_ID`);

--
-- Constraints for table `client_feedback`
--
ALTER TABLE `client_feedback`
  ADD CONSTRAINT `client_feedback_ibfk_1` FOREIGN KEY (`Client_ID`) REFERENCES `client` (`Client_ID`) ON DELETE CASCADE;

--
-- Constraints for table `customer_record`
--
ALTER TABLE `customer_record`
  ADD CONSTRAINT `customer_record_ibfk_1` FOREIGN KEY (`Client_ID`) REFERENCES `client` (`Client_ID`),
  ADD CONSTRAINT `customer_record_ibfk_2` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`);

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`);

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `fk_payment_quotation` FOREIGN KEY (`quotation_id`) REFERENCES `quotation` (`Quotation_ID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_payment_service_request` FOREIGN KEY (`service_request_id`) REFERENCES `service` (`Service_ID`) ON DELETE SET NULL,
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`Client_ID`) REFERENCES `client` (`Client_ID`),
  ADD CONSTRAINT `payment_ibfk_2` FOREIGN KEY (`Service_ID`) REFERENCES `service` (`Service_ID`),
  ADD CONSTRAINT `payment_ibfk_3` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`);

--
-- Constraints for table `quotation`
--
ALTER TABLE `quotation`
  ADD CONSTRAINT `quotation_ibfk_1` FOREIGN KEY (`Client_ID`) REFERENCES `client` (`Client_ID`),
  ADD CONSTRAINT `quotation_ibfk_2` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`);

--
-- Constraints for table `service`
--
ALTER TABLE `service`
  ADD CONSTRAINT `service_ibfk_1` FOREIGN KEY (`Client_ID`) REFERENCES `client` (`Client_ID`),
  ADD CONSTRAINT `service_ibfk_2` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`),
  ADD CONSTRAINT `service_ibfk_3` FOREIGN KEY (`Quotation_ID`) REFERENCES `quotation` (`Quotation_ID`);

--
-- Constraints for table `service_item`
--
ALTER TABLE `service_item`
  ADD CONSTRAINT `service_item_ibfk_1` FOREIGN KEY (`Item_ID`) REFERENCES `inventory` (`Item_ID`),
  ADD CONSTRAINT `service_item_ibfk_2` FOREIGN KEY (`Service_ID`) REFERENCES `service` (`Service_ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
