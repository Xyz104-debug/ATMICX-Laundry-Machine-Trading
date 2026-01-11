-- =============================================
-- FEEDBACK SYSTEM TABLE CREATION
-- Execute this script to add the feedback system to your existing database
-- =============================================

USE atmicxdb;

-- Create client_feedback table
CREATE TABLE IF NOT EXISTS `client_feedback` (
  `Feedback_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Client_ID` int(11) NOT NULL,
  `Rating` int(1) NOT NULL CHECK (`Rating` >= 1 AND `Rating` <= 5),
  `Category` varchar(50) NOT NULL,
  `Message` text NOT NULL,
  `Status` enum('new','reviewed','responded','resolved') DEFAULT 'new',
  `Manager_Response` text DEFAULT NULL,
  `Responded_At` datetime DEFAULT NULL,
  `Created_At` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Feedback_ID`),
  KEY `Client_ID` (`Client_ID`),
  KEY `Status` (`Status`),
  KEY `Created_At` (`Created_At`),
  CONSTRAINT `client_feedback_ibfk_1` FOREIGN KEY (`Client_ID`) REFERENCES `client` (`Client_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Verify table creation
SELECT 'Feedback table created successfully!' as Status;

-- Show table structure
DESCRIBE client_feedback;
