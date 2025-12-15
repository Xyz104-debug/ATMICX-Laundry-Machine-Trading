-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 14, 2025 at 02:35 PM
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

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `Payment_ID` int(11) NOT NULL,
  `Client_ID` int(11) DEFAULT NULL,
  `Service_ID` int(11) DEFAULT NULL,
  `User_ID` int(11) DEFAULT NULL,
  `Amount_Paid` decimal(10,2) DEFAULT NULL,
  `Payment_Method` varchar(50) DEFAULT NULL,
  `Date_Paid` datetime DEFAULT NULL,
  `Status` varchar(50) DEFAULT NULL,
  `Proof_Image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `Handling_Fee` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service`
--

CREATE TABLE `service` (
  `Service_ID` int(11) NOT NULL,
  `Client_ID` int(11) DEFAULT NULL,
  `User_ID` int(11) DEFAULT NULL,
  `Quotation_ID` int(11) DEFAULT NULL,
  `Service_Date` datetime DEFAULT NULL,
  `Status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 'blazeking58', '$argon2id$v=19$m=65536,t=2,p=1$ZC4xN0taeU5SemxvTVRKcA$i6NIdAbfdVngKTcojEYBPQSfTYRvPWDa3xxk3RpHawA', 'secretary', NULL, NULL, NULL, 'barbasgaming1@gmail.com', '01a3a4f0103fe13b62e69f7288e6183cbd68f0d8f12ded2395a548da2001df37', '2025-12-13 04:42:24'),
(3, 'blazeking5', '$argon2id$v=19$m=65536,t=2,p=1$cWo2aGg5SW1URGIxOXBzLw$V+s/FnnDkCmhVOS6obGACMj2ztsWVNgav6CwHqQTMZY', 'manager', NULL, NULL, NULL, 'hughz2004@gmail.com', NULL, NULL);

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
  ADD KEY `User_ID` (`User_ID`);

--
-- Indexes for table `quotation`
--
ALTER TABLE `quotation`
  ADD PRIMARY KEY (`Quotation_ID`),
  ADD KEY `Client_ID` (`Client_ID`),
  ADD KEY `User_ID` (`User_ID`);

--
-- Indexes for table `service`
--
ALTER TABLE `service`
  ADD PRIMARY KEY (`Service_ID`),
  ADD KEY `Client_ID` (`Client_ID`),
  ADD KEY `User_ID` (`User_ID`),
  ADD KEY `Quotation_ID` (`Quotation_ID`);

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
  MODIFY `Client_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_record`
--
ALTER TABLE `customer_record`
  MODIFY `Record_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `Item_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `Payment_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quotation`
--
ALTER TABLE `quotation`
  MODIFY `Quotation_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service`
--
ALTER TABLE `service`
  MODIFY `Service_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_item`
--
ALTER TABLE `service_item`
  MODIFY `ServiceItem_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `User_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `asset`
--
ALTER TABLE `asset`
  ADD CONSTRAINT `asset_ibfk_1` FOREIGN KEY (`Client_ID`) REFERENCES `client` (`Client_ID`);

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
