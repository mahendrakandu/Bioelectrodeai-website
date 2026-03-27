-- BioElectrode AI - Full Database Setup Script
-- Run this in phpMyAdmin or MySQL CLI: source db_setup.sql

CREATE DATABASE IF NOT EXISTS `bioelectrodeai` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `bioelectrodeai`;

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `name`          VARCHAR(100) NOT NULL,
    `email`         VARCHAR(150) NOT NULL UNIQUE,
    `password`      VARCHAR(255) NOT NULL,
    `role`          ENUM('Student','Researcher','Educator','Admin','User') DEFAULT 'Student',
    `status`        ENUM('Pending','Active','Blocked') DEFAULT 'Active',
    `bio`           TEXT DEFAULT NULL,
    `profile_image` VARCHAR(255) DEFAULT NULL,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_login`    TIMESTAMP NULL DEFAULT NULL
);

-- Datasets table
CREATE TABLE IF NOT EXISTS `datasets` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(255) NOT NULL,
    `signal_type` ENUM('ECG','EEG','EMG') NOT NULL,
    `technique`   ENUM('Bipolar','Monopolar') NOT NULL,
    `file_size`   VARCHAR(50) NOT NULL,
    `file_path`   VARCHAR(255) NOT NULL,
    `upload_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status`      ENUM('Raw','Processed','Training') DEFAULT 'Raw',
    `uploaded_by` INT DEFAULT NULL,
    FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- AI Models table
CREATE TABLE IF NOT EXISTS `ai_models` (
    `id`                INT AUTO_INCREMENT PRIMARY KEY,
    `version`           VARCHAR(50) NOT NULL,
    `training_accuracy` DECIMAL(5,2) NOT NULL,
    `validation_score`  DECIMAL(5,2) NOT NULL,
    `last_trained`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status`            ENUM('Development','Deployed','Archived') DEFAULT 'Development'
);

-- System logs table
CREATE TABLE IF NOT EXISTS `system_logs` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT DEFAULT NULL,
    `action`     VARCHAR(255) NOT NULL,
    `details`    TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- App items table
CREATE TABLE IF NOT EXISTS `app_items` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `title`       VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `type`        ENUM('Dataset','Model','Announcement','Feature') DEFAULT 'Announcement',
    `added_date`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Learning content table
CREATE TABLE IF NOT EXISTS `learning_content` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `page_slug`   VARCHAR(50) NOT NULL,
    `section_id`  VARCHAR(50) NOT NULL,
    `title`       VARCHAR(255),
    `content`     TEXT,
    `modified_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (`page_slug`, `section_id`)
);

-- User progress table
CREATE TABLE IF NOT EXISTS `user_progress` (
    `id`                    INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`               INT NOT NULL,
    `module_name`           VARCHAR(100) NOT NULL,
    `completion_percentage` INT DEFAULT 0,
    `last_updated`          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY (`user_id`, `module_name`)
);

-- AI Analysis History table
CREATE TABLE IF NOT EXISTS `analysis_history` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`     INT NOT NULL,
    `signal_type` VARCHAR(50) NOT NULL,
    `technique`   VARCHAR(50) NOT NULL,
    `results_json` JSON NOT NULL,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Quiz Results table
CREATE TABLE IF NOT EXISTS `quiz_results` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`         INT NOT NULL,
    `quiz_type`       VARCHAR(100) NOT NULL,
    `score`           INT NOT NULL,
    `total_questions` INT NOT NULL,
    `completed_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Default Admin user (password: 'password')
INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`)
VALUES ('Super Admin', 'admin@bioelectrode.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'Admin', 'Active')
ON DUPLICATE KEY UPDATE `role` = 'Admin', `status` = 'Active';

-- Sample users
INSERT IGNORE INTO `users` (`name`, `email`, `password`, `role`, `status`) VALUES
('John Doe',       'john@example.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Student',    'Active'),
('Dr. Smith',      'smith@example.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Researcher', 'Active'),
('Prof. Miller',   'miller@example.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Educator',   'Active');

-- Sample datasets
INSERT IGNORE INTO `datasets` (`name`, `signal_type`, `technique`, `file_size`, `file_path`, `status`) VALUES
('Atrial Fibrillation DB', 'ECG', 'Bipolar',   '124 MB', '/uploads/datasets/afib_db.csv',    'Processed'),
('Motor Imagery Data',     'EEG', 'Monopolar', '2.1 GB', '/uploads/datasets/motor_eeg.edf',  'Training'),
('Muscle Fatigue DB',      'EMG', 'Bipolar',   '56 MB',  '/uploads/datasets/fatigue_emg.csv','Raw');

-- Sample AI models
INSERT IGNORE INTO `ai_models` (`version`, `training_accuracy`, `validation_score`, `status`) VALUES
('v2.4.1',    94.80, 92.30, 'Deployed'),
('v2.5.0-beta',96.10, 93.50, 'Development');

-- Sample logs
INSERT IGNORE INTO `system_logs` (`action`, `details`) VALUES
('System Boot',    'Server started successfully'),
('Dataset Upload', 'Atrial Fibrillation DB uploaded by system'),
('Model Training', 'v2.4.1 finished training with 94.8% accuracy'),
('User Login',     'Admin logged into dashboard');

-- All sample user passwords are: password
