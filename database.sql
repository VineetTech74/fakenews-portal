-- Fake News Portal Database Schema
-- Database: fakenews_db

CREATE DATABASE IF NOT EXISTS `fakenews_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `fakenews_db`;

-- --------------------------------------------------------
-- Table structure for `users`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `profile_pic` varchar(255) DEFAULT 'default-avatar.png',
  `age` int(11) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` varchar(50) DEFAULT 'Standard User',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for `reported_news`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reported_news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `headline` varchar(255) NOT NULL,
  `platform` varchar(100) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `date_reported` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_reported_user` (`user_id`),
  CONSTRAINT `fk_reported_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for `search_history`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `search_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `headline` text NOT NULL,
  `trust_score` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `checked_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_history_user` (`user_id`),
  CONSTRAINT `fk_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
