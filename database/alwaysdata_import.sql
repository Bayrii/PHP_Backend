-- ============================================
-- Supervised Driving Experience Database Schema
-- FOR ALWAYSDATA HOSTING - Tables Only
-- ============================================
-- 
-- This file contains ONLY table creation and data
-- (No database creation - use your existing AlwaysData database)
--
-- IMPORTANT: Before importing, select your database in phpMyAdmin:
-- Database name: bayram-aliyev_driving_experience
-- User: 443284
-- 
-- Usage in AlwaysData phpMyAdmin:
-- 1. Log in to AlwaysData phpMyAdmin
-- 2. Click on your database: "bayram-aliyev_driving_experience"
-- 3. Click on "Import" tab
-- 4. Choose this file
-- 5. Click "Go"
-- 
-- After Import:
-- Register your first account at: https://yoursite.alwaysdata.net/register.php
-- 
-- ============================================

-- Set SQL mode and character set
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ============================================
-- 1. USER AUTHENTICATION TABLE
-- ============================================

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. LOOKUP TABLES
-- ============================================

-- Vehicle Types
DROP TABLE IF EXISTS `vehicle_types`;
CREATE TABLE IF NOT EXISTS `vehicle_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `vehicle_types` (`id`, `name`) VALUES
(1, 'Sedan'),
(2, 'SUV'),
(3, 'Truck'),
(4, 'Van'),
(5, 'Compact Car'),
(6, 'Motorcycle');

-- Time of Day
DROP TABLE IF EXISTS `time_of_day`;
CREATE TABLE IF NOT EXISTS `time_of_day` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `time_of_day` (`id`, `name`) VALUES
(1, 'Morning (6AM-12PM)'),
(2, 'Afternoon (12PM-6PM)'),
(3, 'Evening (6PM-10PM)'),
(4, 'Night (10PM-6AM)');

-- Surfaces
DROP TABLE IF EXISTS `surfaces`;
CREATE TABLE IF NOT EXISTS `surfaces` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `surfaces` (`id`, `name`) VALUES
(1, 'Dry Asphalt'),
(2, 'Wet Asphalt'),
(3, 'Snow'),
(4, 'Ice'),
(5, 'Gravel'),
(6, 'Dirt Road');

-- Road Densities
DROP TABLE IF EXISTS `road_densities`;
CREATE TABLE IF NOT EXISTS `road_densities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `road_densities` (`id`, `name`) VALUES
(1, 'Light Traffic'),
(2, 'Moderate Traffic'),
(3, 'Heavy Traffic'),
(4, 'No Traffic');

-- Road Types
DROP TABLE IF EXISTS `road_types`;
CREATE TABLE IF NOT EXISTS `road_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `road_types` (`id`, `name`) VALUES
(1, 'City Street'),
(2, 'Highway'),
(3, 'Rural Road'),
(4, 'Residential Area'),
(5, 'Parking Lot'),
(6, 'Mountain Road');

-- Weather Conditions
DROP TABLE IF EXISTS `weather_conditions`;
CREATE TABLE IF NOT EXISTS `weather_conditions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `weather_conditions` (`id`, `name`) VALUES
(1, 'Clear/Sunny'),
(2, 'Cloudy'),
(3, 'Rainy'),
(4, 'Snowy'),
(5, 'Foggy'),
(6, 'Windy');

-- ============================================
-- 3. MAIN TABLE - DRIVING EXPERIENCES
-- ============================================

DROP TABLE IF EXISTS `driving_experiences`;
CREATE TABLE IF NOT EXISTS `driving_experiences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `duration_minutes` int(11) NOT NULL,
  `distance_km` decimal(6,2) NOT NULL,
  `start_location` varchar(255) NOT NULL,
  `end_location` varchar(255) NOT NULL,
  `vehicle_type_id` int(11) NOT NULL,
  `time_of_day_id` int(11) NOT NULL,
  `surface_id` int(11) NOT NULL,
  `road_density_id` int(11) NOT NULL,
  `road_type_id` int(11) NOT NULL,
  `weather_id` int(11) NOT NULL,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_date` (`date`),
  KEY `vehicle_type_id` (`vehicle_type_id`),
  KEY `time_of_day_id` (`time_of_day_id`),
  KEY `surface_id` (`surface_id`),
  KEY `road_density_id` (`road_density_id`),
  KEY `road_type_id` (`road_type_id`),
  KEY `weather_id` (`weather_id`),
  CONSTRAINT `driving_experiences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `driving_experiences_ibfk_2` FOREIGN KEY (`vehicle_type_id`) REFERENCES `vehicle_types` (`id`),
  CONSTRAINT `driving_experiences_ibfk_3` FOREIGN KEY (`time_of_day_id`) REFERENCES `time_of_day` (`id`),
  CONSTRAINT `driving_experiences_ibfk_4` FOREIGN KEY (`surface_id`) REFERENCES `surfaces` (`id`),
  CONSTRAINT `driving_experiences_ibfk_5` FOREIGN KEY (`road_density_id`) REFERENCES `road_densities` (`id`),
  CONSTRAINT `driving_experiences_ibfk_6` FOREIGN KEY (`road_type_id`) REFERENCES `road_types` (`id`),
  CONSTRAINT `driving_experiences_ibfk_7` FOREIGN KEY (`weather_id`) REFERENCES `weather_conditions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SETUP COMPLETE
-- ============================================
-- 
-- Next Steps:
-- 1. Go to your website
-- 2. Navigate to /register.php
-- 3. Create your first account
-- 4. Start logging driving experiences!
-- 
-- ============================================
