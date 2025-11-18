-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 18, 2025 at 06:01 PM
-- Server version: 10.11.6-MariaDB-cll-lve
-- PHP Version: 8.4.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rotz3716_isolascreen`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `email`, `created_at`) VALUES
(1, 'admin', '$2y$10$d82x09.FwY0Vw5YcFoxOXeOkwL6wSGYegNalF1dAZg8inF9Zhsze6', 'admin@isolascreen.com', '2025-11-13 03:24:54');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `booking_code` varchar(20) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `seats` text NOT NULL COMMENT 'JSON array seat labels',
  `total_seats` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `booking_status` enum('pending','confirmed','cancelled','expired','used') DEFAULT 'pending',
  `payment_status` enum('unpaid','paid','failed','refunded') DEFAULT 'unpaid',
  `booking_date` timestamp NULL DEFAULT current_timestamp(),
  `expired_at` timestamp NULL DEFAULT NULL COMMENT 'Waktu booking akan expired jika belum bayar',
  `email_sent` tinyint(1) DEFAULT 0,
  `email_sent_at` timestamp NULL DEFAULT NULL,
  `used_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `booking_code`, `schedule_id`, `customer_name`, `customer_email`, `customer_phone`, `seats`, `total_seats`, `total_price`, `booking_status`, `payment_status`, `booking_date`, `expired_at`, `email_sent`, `email_sent_at`, `used_at`) VALUES
(38, 'ISOLA-5A42BE81', 8, 'yuda', 'myudar2301@gmail.com', '081233009283', '[\"J7\"]', 1, 8000.00, 'used', 'paid', '2025-11-18 07:52:04', '2025-11-18 08:07:04', 1, '2025-11-18 07:53:34', NULL),
(39, 'ISOLA-B2B88201', 8, 'saf', '', '', '[\"B8\"]', 1, 8000.00, 'used', 'paid', '2025-11-18 08:15:39', NULL, 0, NULL, '2025-11-18 09:35:44'),
(40, 'ISOLA-F24BED6C', 8, 'JOAN', 'myudar2301@gmail.com', '081233009283', '[\"M4\"]', 1, 8000.00, 'confirmed', 'paid', '2025-11-18 09:40:52', '2025-11-18 09:55:52', 1, '2025-11-18 09:41:23', NULL),
(41, 'ISOLA-FEB818A4', 8, 'JOAN', 'myudar2301@gmail.com', '081233009283', '[\"P3\"]', 1, 8000.00, 'confirmed', 'paid', '2025-11-18 09:44:11', '2025-11-18 09:59:11', 1, '2025-11-18 09:44:28', NULL),
(42, 'ISOLA-0CF7DAB2', 8, 'saf', 'myudar2301@gmail.com', '081233009283', '[\"I4\"]', 1, 8000.00, 'confirmed', 'paid', '2025-11-18 09:47:59', '2025-11-18 10:02:59', 1, '2025-11-18 09:48:19', NULL),
(43, 'ISOLA-30B544B1', 8, 'yuda', 'myudar2301@gmail.com', '081233009283', '[\"D3\"]', 1, 8000.00, 'confirmed', 'paid', '2025-11-18 09:57:31', '2025-11-18 10:12:31', 1, '2025-11-18 09:57:57', NULL),
(44, 'ISOLA-3F16145D', 9, 'JOAN', 'myudar2301@gmail.com', '081233009283', '[\"J6\"]', 1, 20000.00, 'confirmed', 'paid', '2025-11-18 10:01:21', '2025-11-18 10:16:21', 1, '2025-11-18 10:01:47', NULL),
(45, 'ISOLA-E6C91B8D', 8, 'SUMAIYAH', 'myudar2301@gmail.com', '081233009283', '[\"P13\"]', 1, 8000.00, 'confirmed', 'paid', '2025-11-18 10:46:04', '2025-11-18 11:01:04', 1, '2025-11-18 10:46:34', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `films`
--

CREATE TABLE `films` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `duration` int(11) NOT NULL COMMENT 'Durasi dalam menit',
  `genre` varchar(100) DEFAULT NULL,
  `rating` varchar(10) DEFAULT NULL COMMENT 'G, PG, PG-13, R, dll',
  `release_date` date DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL COMMENT 'Path ke gambar cover',
  `trailer_url` varchar(255) DEFAULT NULL,
  `trailer_file` varchar(255) DEFAULT NULL,
  `second_film_id` int(11) DEFAULT NULL,
  `is_double_feature` tinyint(1) DEFAULT 0,
  `second_cover_image` varchar(255) DEFAULT NULL,
  `director` varchar(100) DEFAULT NULL,
  `cast` text DEFAULT NULL COMMENT 'Pemain utama',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `films`
--

INSERT INTO `films` (`id`, `title`, `description`, `duration`, `genre`, `rating`, `release_date`, `cover_image`, `trailer_url`, `trailer_file`, `second_film_id`, `is_double_feature`, `second_cover_image`, `director`, `cast`, `status`, `created_at`, `updated_at`) VALUES
(9, 'Negana', 'Setahun pascabencana gempa bumi, di tengah hiruk-pikuk revitalisasi Kota Palu, Gana (14) seorang anak perempuan yang, berusaha kabur dari tempat tinggalnya Hunian Sementara (Huntara), karena akan dinikahkan dengan seorang duda (40), namun gempa-gempa susulan selalu menggagalkan rencananya.', 24, 'Drama', 'R', '2025-04-30', '691ab5e70ed86_1763358183.webp', '', '', 10, 1, '691ab7e35e7ae_1763358691.webp', 'Vania Qanita Damayanti', 'Nurul Maisarah, Annisa Sazkia', 'active', '2025-11-17 05:43:03', '2025-11-17 05:51:31'),
(10, 'Sintas Berlayar', 'Film Dokumenter', 25, 'Dokumenter', 'SU', '2022-01-01', '691ab71ed7eb3_1763358494.webp', '', '', NULL, 0, '', 'Firgiawan', '', 'active', '2025-11-17 05:48:14', '2025-11-18 09:46:15');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` int(11) NOT NULL,
  `film_id` int(11) NOT NULL,
  `show_date` date NOT NULL,
  `show_time` time NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `promo_price` decimal(10,2) DEFAULT NULL,
  `is_promo` tinyint(1) DEFAULT 0,
  `promo_start_date` date DEFAULT NULL,
  `promo_end_date` date DEFAULT NULL,
  `available_seats` int(11) DEFAULT 50 COMMENT 'Jumlah kursi tersedia',
  `total_seats` int(11) DEFAULT 50 COMMENT 'Total kursi di studio',
  `status` enum('active','inactive','full') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`id`, `film_id`, `show_date`, `show_time`, `price`, `promo_price`, `is_promo`, `promo_start_date`, `promo_end_date`, `available_seats`, `total_seats`, `status`, `created_at`) VALUES
(8, 9, '2025-11-21', '14:54:00', 8000.00, NULL, 0, NULL, NULL, 169, 176, 'active', '2025-11-18 07:51:41'),
(9, 10, '2025-11-29', '21:00:00', 50000.00, 20000.00, 1, '2025-11-18', '2025-11-28', 175, 176, 'active', '2025-11-18 10:01:07');

--
-- Triggers `schedules`
--
DELIMITER $$
CREATE TRIGGER `after_schedule_insert` AFTER INSERT ON `schedules` FOR EACH ROW BEGIN
    -- Konfigurasi jumlah kursi per baris
    -- Format: row_char, jumlah_kursi
    DECLARE seat_config TEXT DEFAULT 'A:9,B:11,C:11,D:11,E:11,F:9,G:9,H:13,I:13,J:13,K:12,L:12,M:11,N:9,O:9,P:13';
    
    DECLARE i INT DEFAULT 1;
    DECLARE j INT;
    DECLARE row_char CHAR(1);
    DECLARE seats_in_row INT;
    DECLARE row_config VARCHAR(10);
    DECLARE total_rows INT DEFAULT 16; -- A sampai P = 16 baris
    
    -- Loop untuk setiap baris
    WHILE i <= total_rows DO
        SET row_char = CHAR(64 + i); -- A=65, B=66, dst
        
        -- Tentukan jumlah kursi untuk baris ini
        CASE row_char
            WHEN 'A' THEN SET seats_in_row = 9;
            WHEN 'B' THEN SET seats_in_row = 11;
            WHEN 'C' THEN SET seats_in_row = 11;
            WHEN 'D' THEN SET seats_in_row = 11;
            WHEN 'E' THEN SET seats_in_row = 11;
            WHEN 'F' THEN SET seats_in_row = 9;
            WHEN 'G' THEN SET seats_in_row = 9;
            WHEN 'H' THEN SET seats_in_row = 13;
            WHEN 'I' THEN SET seats_in_row = 13;
            WHEN 'J' THEN SET seats_in_row = 13;
            WHEN 'K' THEN SET seats_in_row = 12;
            WHEN 'L' THEN SET seats_in_row = 12;
            WHEN 'M' THEN SET seats_in_row = 11;
            WHEN 'N' THEN SET seats_in_row = 9;
            WHEN 'O' THEN SET seats_in_row = 9;
            WHEN 'P' THEN SET seats_in_row = 13;
            ELSE SET seats_in_row = 0;
        END CASE;
        
        -- Insert kursi untuk baris ini
        SET j = 1;
        WHILE j <= seats_in_row DO
            INSERT INTO seats (schedule_id, seat_row, seat_number, seat_label, status)
            VALUES (NEW.id, row_char, j, CONCAT(row_char, j), 'available');
            SET j = j + 1;
        END WHILE;
        
        SET i = i + 1;
    END WHILE;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `seats`
--

CREATE TABLE `seats` (
  `id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `seat_row` varchar(2) NOT NULL COMMENT 'A, B, C, dll',
  `seat_number` int(11) NOT NULL,
  `seat_label` varchar(5) NOT NULL COMMENT 'A1, A2, B1, dll',
  `status` enum('available','reserved','booked') DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `seats`
--

INSERT INTO `seats` (`id`, `schedule_id`, `seat_row`, `seat_number`, `seat_label`, `status`, `created_at`) VALUES
(617, 8, 'A', 1, 'A1', 'available', '2025-11-18 07:51:41'),
(618, 8, 'A', 2, 'A2', 'available', '2025-11-18 07:51:41'),
(619, 8, 'A', 3, 'A3', 'available', '2025-11-18 07:51:41'),
(620, 8, 'A', 4, 'A4', 'available', '2025-11-18 07:51:41'),
(621, 8, 'A', 5, 'A5', 'available', '2025-11-18 07:51:41'),
(622, 8, 'A', 6, 'A6', 'available', '2025-11-18 07:51:41'),
(623, 8, 'A', 7, 'A7', 'available', '2025-11-18 07:51:41'),
(624, 8, 'A', 8, 'A8', 'available', '2025-11-18 07:51:41'),
(625, 8, 'A', 9, 'A9', 'available', '2025-11-18 07:51:41'),
(626, 8, 'B', 1, 'B1', 'available', '2025-11-18 07:51:41'),
(627, 8, 'B', 2, 'B2', 'available', '2025-11-18 07:51:41'),
(628, 8, 'B', 3, 'B3', 'available', '2025-11-18 07:51:41'),
(629, 8, 'B', 4, 'B4', 'available', '2025-11-18 07:51:41'),
(630, 8, 'B', 5, 'B5', 'available', '2025-11-18 07:51:41'),
(631, 8, 'B', 6, 'B6', 'available', '2025-11-18 07:51:41'),
(632, 8, 'B', 7, 'B7', 'available', '2025-11-18 07:51:41'),
(633, 8, 'B', 8, 'B8', 'booked', '2025-11-18 07:51:41'),
(634, 8, 'B', 9, 'B9', 'available', '2025-11-18 07:51:41'),
(635, 8, 'B', 10, 'B10', 'available', '2025-11-18 07:51:41'),
(636, 8, 'B', 11, 'B11', 'available', '2025-11-18 07:51:41'),
(637, 8, 'C', 1, 'C1', 'available', '2025-11-18 07:51:41'),
(638, 8, 'C', 2, 'C2', 'available', '2025-11-18 07:51:41'),
(639, 8, 'C', 3, 'C3', 'available', '2025-11-18 07:51:41'),
(640, 8, 'C', 4, 'C4', 'available', '2025-11-18 07:51:41'),
(641, 8, 'C', 5, 'C5', 'available', '2025-11-18 07:51:41'),
(642, 8, 'C', 6, 'C6', 'available', '2025-11-18 07:51:41'),
(643, 8, 'C', 7, 'C7', 'available', '2025-11-18 07:51:41'),
(644, 8, 'C', 8, 'C8', 'available', '2025-11-18 07:51:41'),
(645, 8, 'C', 9, 'C9', 'available', '2025-11-18 07:51:41'),
(646, 8, 'C', 10, 'C10', 'available', '2025-11-18 07:51:41'),
(647, 8, 'C', 11, 'C11', 'available', '2025-11-18 07:51:41'),
(648, 8, 'D', 1, 'D1', 'available', '2025-11-18 07:51:41'),
(649, 8, 'D', 2, 'D2', 'available', '2025-11-18 07:51:41'),
(650, 8, 'D', 3, 'D3', 'booked', '2025-11-18 07:51:41'),
(651, 8, 'D', 4, 'D4', 'available', '2025-11-18 07:51:41'),
(652, 8, 'D', 5, 'D5', 'available', '2025-11-18 07:51:41'),
(653, 8, 'D', 6, 'D6', 'available', '2025-11-18 07:51:41'),
(654, 8, 'D', 7, 'D7', 'available', '2025-11-18 07:51:41'),
(655, 8, 'D', 8, 'D8', 'available', '2025-11-18 07:51:41'),
(656, 8, 'D', 9, 'D9', 'available', '2025-11-18 07:51:41'),
(657, 8, 'D', 10, 'D10', 'available', '2025-11-18 07:51:41'),
(658, 8, 'D', 11, 'D11', 'available', '2025-11-18 07:51:41'),
(659, 8, 'E', 1, 'E1', 'available', '2025-11-18 07:51:41'),
(660, 8, 'E', 2, 'E2', 'available', '2025-11-18 07:51:41'),
(661, 8, 'E', 3, 'E3', 'available', '2025-11-18 07:51:41'),
(662, 8, 'E', 4, 'E4', 'available', '2025-11-18 07:51:41'),
(663, 8, 'E', 5, 'E5', 'available', '2025-11-18 07:51:41'),
(664, 8, 'E', 6, 'E6', 'available', '2025-11-18 07:51:41'),
(665, 8, 'E', 7, 'E7', 'available', '2025-11-18 07:51:41'),
(666, 8, 'E', 8, 'E8', 'available', '2025-11-18 07:51:41'),
(667, 8, 'E', 9, 'E9', 'available', '2025-11-18 07:51:41'),
(668, 8, 'E', 10, 'E10', 'available', '2025-11-18 07:51:41'),
(669, 8, 'E', 11, 'E11', 'available', '2025-11-18 07:51:41'),
(670, 8, 'F', 1, 'F1', 'available', '2025-11-18 07:51:41'),
(671, 8, 'F', 2, 'F2', 'available', '2025-11-18 07:51:41'),
(672, 8, 'F', 3, 'F3', 'available', '2025-11-18 07:51:41'),
(673, 8, 'F', 4, 'F4', 'available', '2025-11-18 07:51:41'),
(674, 8, 'F', 5, 'F5', 'available', '2025-11-18 07:51:41'),
(675, 8, 'F', 6, 'F6', 'available', '2025-11-18 07:51:41'),
(676, 8, 'F', 7, 'F7', 'available', '2025-11-18 07:51:41'),
(677, 8, 'F', 8, 'F8', 'available', '2025-11-18 07:51:41'),
(678, 8, 'F', 9, 'F9', 'available', '2025-11-18 07:51:41'),
(679, 8, 'G', 1, 'G1', 'available', '2025-11-18 07:51:41'),
(680, 8, 'G', 2, 'G2', 'available', '2025-11-18 07:51:41'),
(681, 8, 'G', 3, 'G3', 'available', '2025-11-18 07:51:41'),
(682, 8, 'G', 4, 'G4', 'available', '2025-11-18 07:51:41'),
(683, 8, 'G', 5, 'G5', 'available', '2025-11-18 07:51:41'),
(684, 8, 'G', 6, 'G6', 'available', '2025-11-18 07:51:41'),
(685, 8, 'G', 7, 'G7', 'available', '2025-11-18 07:51:41'),
(686, 8, 'G', 8, 'G8', 'available', '2025-11-18 07:51:41'),
(687, 8, 'G', 9, 'G9', 'available', '2025-11-18 07:51:41'),
(688, 8, 'H', 1, 'H1', 'available', '2025-11-18 07:51:41'),
(689, 8, 'H', 2, 'H2', 'available', '2025-11-18 07:51:41'),
(690, 8, 'H', 3, 'H3', 'available', '2025-11-18 07:51:41'),
(691, 8, 'H', 4, 'H4', 'available', '2025-11-18 07:51:41'),
(692, 8, 'H', 5, 'H5', 'available', '2025-11-18 07:51:41'),
(693, 8, 'H', 6, 'H6', 'available', '2025-11-18 07:51:41'),
(694, 8, 'H', 7, 'H7', 'available', '2025-11-18 07:51:41'),
(695, 8, 'H', 8, 'H8', 'available', '2025-11-18 07:51:41'),
(696, 8, 'H', 9, 'H9', 'available', '2025-11-18 07:51:41'),
(697, 8, 'H', 10, 'H10', 'available', '2025-11-18 07:51:41'),
(698, 8, 'H', 11, 'H11', 'available', '2025-11-18 07:51:41'),
(699, 8, 'H', 12, 'H12', 'available', '2025-11-18 07:51:41'),
(700, 8, 'H', 13, 'H13', 'available', '2025-11-18 07:51:41'),
(701, 8, 'I', 1, 'I1', 'available', '2025-11-18 07:51:41'),
(702, 8, 'I', 2, 'I2', 'available', '2025-11-18 07:51:41'),
(703, 8, 'I', 3, 'I3', 'available', '2025-11-18 07:51:41'),
(704, 8, 'I', 4, 'I4', 'booked', '2025-11-18 07:51:41'),
(705, 8, 'I', 5, 'I5', 'available', '2025-11-18 07:51:41'),
(706, 8, 'I', 6, 'I6', 'available', '2025-11-18 07:51:41'),
(707, 8, 'I', 7, 'I7', 'available', '2025-11-18 07:51:41'),
(708, 8, 'I', 8, 'I8', 'available', '2025-11-18 07:51:41'),
(709, 8, 'I', 9, 'I9', 'available', '2025-11-18 07:51:41'),
(710, 8, 'I', 10, 'I10', 'available', '2025-11-18 07:51:41'),
(711, 8, 'I', 11, 'I11', 'available', '2025-11-18 07:51:41'),
(712, 8, 'I', 12, 'I12', 'available', '2025-11-18 07:51:41'),
(713, 8, 'I', 13, 'I13', 'available', '2025-11-18 07:51:41'),
(714, 8, 'J', 1, 'J1', 'available', '2025-11-18 07:51:41'),
(715, 8, 'J', 2, 'J2', 'available', '2025-11-18 07:51:41'),
(716, 8, 'J', 3, 'J3', 'available', '2025-11-18 07:51:41'),
(717, 8, 'J', 4, 'J4', 'available', '2025-11-18 07:51:41'),
(718, 8, 'J', 5, 'J5', 'available', '2025-11-18 07:51:41'),
(719, 8, 'J', 6, 'J6', 'available', '2025-11-18 07:51:41'),
(720, 8, 'J', 7, 'J7', 'booked', '2025-11-18 07:51:41'),
(721, 8, 'J', 8, 'J8', 'available', '2025-11-18 07:51:41'),
(722, 8, 'J', 9, 'J9', 'available', '2025-11-18 07:51:41'),
(723, 8, 'J', 10, 'J10', 'available', '2025-11-18 07:51:41'),
(724, 8, 'J', 11, 'J11', 'available', '2025-11-18 07:51:41'),
(725, 8, 'J', 12, 'J12', 'available', '2025-11-18 07:51:41'),
(726, 8, 'J', 13, 'J13', 'available', '2025-11-18 07:51:41'),
(727, 8, 'K', 1, 'K1', 'available', '2025-11-18 07:51:41'),
(728, 8, 'K', 2, 'K2', 'available', '2025-11-18 07:51:41'),
(729, 8, 'K', 3, 'K3', 'available', '2025-11-18 07:51:41'),
(730, 8, 'K', 4, 'K4', 'available', '2025-11-18 07:51:41'),
(731, 8, 'K', 5, 'K5', 'available', '2025-11-18 07:51:41'),
(732, 8, 'K', 6, 'K6', 'available', '2025-11-18 07:51:41'),
(733, 8, 'K', 7, 'K7', 'available', '2025-11-18 07:51:41'),
(734, 8, 'K', 8, 'K8', 'available', '2025-11-18 07:51:41'),
(735, 8, 'K', 9, 'K9', 'available', '2025-11-18 07:51:41'),
(736, 8, 'K', 10, 'K10', 'available', '2025-11-18 07:51:41'),
(737, 8, 'K', 11, 'K11', 'available', '2025-11-18 07:51:41'),
(738, 8, 'K', 12, 'K12', 'available', '2025-11-18 07:51:41'),
(739, 8, 'L', 1, 'L1', 'available', '2025-11-18 07:51:41'),
(740, 8, 'L', 2, 'L2', 'available', '2025-11-18 07:51:41'),
(741, 8, 'L', 3, 'L3', 'available', '2025-11-18 07:51:41'),
(742, 8, 'L', 4, 'L4', 'available', '2025-11-18 07:51:41'),
(743, 8, 'L', 5, 'L5', 'available', '2025-11-18 07:51:41'),
(744, 8, 'L', 6, 'L6', 'available', '2025-11-18 07:51:41'),
(745, 8, 'L', 7, 'L7', 'available', '2025-11-18 07:51:41'),
(746, 8, 'L', 8, 'L8', 'available', '2025-11-18 07:51:41'),
(747, 8, 'L', 9, 'L9', 'available', '2025-11-18 07:51:41'),
(748, 8, 'L', 10, 'L10', 'available', '2025-11-18 07:51:41'),
(749, 8, 'L', 11, 'L11', 'available', '2025-11-18 07:51:41'),
(750, 8, 'L', 12, 'L12', 'available', '2025-11-18 07:51:41'),
(751, 8, 'M', 1, 'M1', 'available', '2025-11-18 07:51:41'),
(752, 8, 'M', 2, 'M2', 'available', '2025-11-18 07:51:41'),
(753, 8, 'M', 3, 'M3', 'available', '2025-11-18 07:51:41'),
(754, 8, 'M', 4, 'M4', 'reserved', '2025-11-18 07:51:41'),
(755, 8, 'M', 5, 'M5', 'available', '2025-11-18 07:51:41'),
(756, 8, 'M', 6, 'M6', 'available', '2025-11-18 07:51:41'),
(757, 8, 'M', 7, 'M7', 'available', '2025-11-18 07:51:41'),
(758, 8, 'M', 8, 'M8', 'available', '2025-11-18 07:51:41'),
(759, 8, 'M', 9, 'M9', 'available', '2025-11-18 07:51:41'),
(760, 8, 'M', 10, 'M10', 'available', '2025-11-18 07:51:41'),
(761, 8, 'M', 11, 'M11', 'available', '2025-11-18 07:51:41'),
(762, 8, 'N', 1, 'N1', 'available', '2025-11-18 07:51:41'),
(763, 8, 'N', 2, 'N2', 'available', '2025-11-18 07:51:41'),
(764, 8, 'N', 3, 'N3', 'available', '2025-11-18 07:51:41'),
(765, 8, 'N', 4, 'N4', 'available', '2025-11-18 07:51:41'),
(766, 8, 'N', 5, 'N5', 'available', '2025-11-18 07:51:41'),
(767, 8, 'N', 6, 'N6', 'available', '2025-11-18 07:51:41'),
(768, 8, 'N', 7, 'N7', 'available', '2025-11-18 07:51:41'),
(769, 8, 'N', 8, 'N8', 'available', '2025-11-18 07:51:41'),
(770, 8, 'N', 9, 'N9', 'available', '2025-11-18 07:51:41'),
(771, 8, 'O', 1, 'O1', 'available', '2025-11-18 07:51:41'),
(772, 8, 'O', 2, 'O2', 'available', '2025-11-18 07:51:41'),
(773, 8, 'O', 3, 'O3', 'available', '2025-11-18 07:51:41'),
(774, 8, 'O', 4, 'O4', 'available', '2025-11-18 07:51:41'),
(775, 8, 'O', 5, 'O5', 'available', '2025-11-18 07:51:41'),
(776, 8, 'O', 6, 'O6', 'available', '2025-11-18 07:51:41'),
(777, 8, 'O', 7, 'O7', 'available', '2025-11-18 07:51:41'),
(778, 8, 'O', 8, 'O8', 'available', '2025-11-18 07:51:41'),
(779, 8, 'O', 9, 'O9', 'available', '2025-11-18 07:51:41'),
(780, 8, 'P', 1, 'P1', 'available', '2025-11-18 07:51:41'),
(781, 8, 'P', 2, 'P2', 'available', '2025-11-18 07:51:41'),
(782, 8, 'P', 3, 'P3', 'booked', '2025-11-18 07:51:41'),
(783, 8, 'P', 4, 'P4', 'available', '2025-11-18 07:51:41'),
(784, 8, 'P', 5, 'P5', 'available', '2025-11-18 07:51:41'),
(785, 8, 'P', 6, 'P6', 'available', '2025-11-18 07:51:41'),
(786, 8, 'P', 7, 'P7', 'available', '2025-11-18 07:51:41'),
(787, 8, 'P', 8, 'P8', 'available', '2025-11-18 07:51:41'),
(788, 8, 'P', 9, 'P9', 'available', '2025-11-18 07:51:41'),
(789, 8, 'P', 10, 'P10', 'available', '2025-11-18 07:51:41'),
(790, 8, 'P', 11, 'P11', 'available', '2025-11-18 07:51:41'),
(791, 8, 'P', 12, 'P12', 'available', '2025-11-18 07:51:41'),
(792, 8, 'P', 13, 'P13', 'booked', '2025-11-18 07:51:41'),
(793, 9, 'A', 1, 'A1', 'available', '2025-11-18 10:01:07'),
(794, 9, 'A', 2, 'A2', 'available', '2025-11-18 10:01:07'),
(795, 9, 'A', 3, 'A3', 'available', '2025-11-18 10:01:07'),
(796, 9, 'A', 4, 'A4', 'available', '2025-11-18 10:01:07'),
(797, 9, 'A', 5, 'A5', 'available', '2025-11-18 10:01:07'),
(798, 9, 'A', 6, 'A6', 'available', '2025-11-18 10:01:07'),
(799, 9, 'A', 7, 'A7', 'available', '2025-11-18 10:01:07'),
(800, 9, 'A', 8, 'A8', 'available', '2025-11-18 10:01:07'),
(801, 9, 'A', 9, 'A9', 'available', '2025-11-18 10:01:07'),
(802, 9, 'B', 1, 'B1', 'available', '2025-11-18 10:01:07'),
(803, 9, 'B', 2, 'B2', 'available', '2025-11-18 10:01:07'),
(804, 9, 'B', 3, 'B3', 'available', '2025-11-18 10:01:07'),
(805, 9, 'B', 4, 'B4', 'available', '2025-11-18 10:01:07'),
(806, 9, 'B', 5, 'B5', 'available', '2025-11-18 10:01:07'),
(807, 9, 'B', 6, 'B6', 'available', '2025-11-18 10:01:07'),
(808, 9, 'B', 7, 'B7', 'available', '2025-11-18 10:01:07'),
(809, 9, 'B', 8, 'B8', 'available', '2025-11-18 10:01:07'),
(810, 9, 'B', 9, 'B9', 'available', '2025-11-18 10:01:07'),
(811, 9, 'B', 10, 'B10', 'available', '2025-11-18 10:01:07'),
(812, 9, 'B', 11, 'B11', 'available', '2025-11-18 10:01:07'),
(813, 9, 'C', 1, 'C1', 'available', '2025-11-18 10:01:07'),
(814, 9, 'C', 2, 'C2', 'available', '2025-11-18 10:01:07'),
(815, 9, 'C', 3, 'C3', 'available', '2025-11-18 10:01:07'),
(816, 9, 'C', 4, 'C4', 'available', '2025-11-18 10:01:07'),
(817, 9, 'C', 5, 'C5', 'available', '2025-11-18 10:01:07'),
(818, 9, 'C', 6, 'C6', 'available', '2025-11-18 10:01:07'),
(819, 9, 'C', 7, 'C7', 'available', '2025-11-18 10:01:07'),
(820, 9, 'C', 8, 'C8', 'available', '2025-11-18 10:01:07'),
(821, 9, 'C', 9, 'C9', 'available', '2025-11-18 10:01:07'),
(822, 9, 'C', 10, 'C10', 'available', '2025-11-18 10:01:07'),
(823, 9, 'C', 11, 'C11', 'available', '2025-11-18 10:01:07'),
(824, 9, 'D', 1, 'D1', 'available', '2025-11-18 10:01:07'),
(825, 9, 'D', 2, 'D2', 'available', '2025-11-18 10:01:07'),
(826, 9, 'D', 3, 'D3', 'available', '2025-11-18 10:01:07'),
(827, 9, 'D', 4, 'D4', 'available', '2025-11-18 10:01:07'),
(828, 9, 'D', 5, 'D5', 'available', '2025-11-18 10:01:07'),
(829, 9, 'D', 6, 'D6', 'available', '2025-11-18 10:01:07'),
(830, 9, 'D', 7, 'D7', 'available', '2025-11-18 10:01:07'),
(831, 9, 'D', 8, 'D8', 'available', '2025-11-18 10:01:07'),
(832, 9, 'D', 9, 'D9', 'available', '2025-11-18 10:01:07'),
(833, 9, 'D', 10, 'D10', 'available', '2025-11-18 10:01:07'),
(834, 9, 'D', 11, 'D11', 'available', '2025-11-18 10:01:07'),
(835, 9, 'E', 1, 'E1', 'available', '2025-11-18 10:01:07'),
(836, 9, 'E', 2, 'E2', 'available', '2025-11-18 10:01:07'),
(837, 9, 'E', 3, 'E3', 'available', '2025-11-18 10:01:07'),
(838, 9, 'E', 4, 'E4', 'available', '2025-11-18 10:01:07'),
(839, 9, 'E', 5, 'E5', 'available', '2025-11-18 10:01:07'),
(840, 9, 'E', 6, 'E6', 'available', '2025-11-18 10:01:07'),
(841, 9, 'E', 7, 'E7', 'available', '2025-11-18 10:01:07'),
(842, 9, 'E', 8, 'E8', 'available', '2025-11-18 10:01:07'),
(843, 9, 'E', 9, 'E9', 'available', '2025-11-18 10:01:07'),
(844, 9, 'E', 10, 'E10', 'available', '2025-11-18 10:01:07'),
(845, 9, 'E', 11, 'E11', 'available', '2025-11-18 10:01:07'),
(846, 9, 'F', 1, 'F1', 'available', '2025-11-18 10:01:07'),
(847, 9, 'F', 2, 'F2', 'available', '2025-11-18 10:01:07'),
(848, 9, 'F', 3, 'F3', 'available', '2025-11-18 10:01:07'),
(849, 9, 'F', 4, 'F4', 'available', '2025-11-18 10:01:07'),
(850, 9, 'F', 5, 'F5', 'available', '2025-11-18 10:01:07'),
(851, 9, 'F', 6, 'F6', 'available', '2025-11-18 10:01:07'),
(852, 9, 'F', 7, 'F7', 'available', '2025-11-18 10:01:07'),
(853, 9, 'F', 8, 'F8', 'available', '2025-11-18 10:01:07'),
(854, 9, 'F', 9, 'F9', 'available', '2025-11-18 10:01:07'),
(855, 9, 'G', 1, 'G1', 'available', '2025-11-18 10:01:07'),
(856, 9, 'G', 2, 'G2', 'available', '2025-11-18 10:01:07'),
(857, 9, 'G', 3, 'G3', 'available', '2025-11-18 10:01:07'),
(858, 9, 'G', 4, 'G4', 'available', '2025-11-18 10:01:07'),
(859, 9, 'G', 5, 'G5', 'available', '2025-11-18 10:01:07'),
(860, 9, 'G', 6, 'G6', 'available', '2025-11-18 10:01:07'),
(861, 9, 'G', 7, 'G7', 'available', '2025-11-18 10:01:07'),
(862, 9, 'G', 8, 'G8', 'available', '2025-11-18 10:01:07'),
(863, 9, 'G', 9, 'G9', 'available', '2025-11-18 10:01:07'),
(864, 9, 'H', 1, 'H1', 'available', '2025-11-18 10:01:07'),
(865, 9, 'H', 2, 'H2', 'available', '2025-11-18 10:01:07'),
(866, 9, 'H', 3, 'H3', 'available', '2025-11-18 10:01:07'),
(867, 9, 'H', 4, 'H4', 'available', '2025-11-18 10:01:07'),
(868, 9, 'H', 5, 'H5', 'available', '2025-11-18 10:01:07'),
(869, 9, 'H', 6, 'H6', 'available', '2025-11-18 10:01:07'),
(870, 9, 'H', 7, 'H7', 'available', '2025-11-18 10:01:07'),
(871, 9, 'H', 8, 'H8', 'available', '2025-11-18 10:01:07'),
(872, 9, 'H', 9, 'H9', 'available', '2025-11-18 10:01:07'),
(873, 9, 'H', 10, 'H10', 'available', '2025-11-18 10:01:07'),
(874, 9, 'H', 11, 'H11', 'available', '2025-11-18 10:01:07'),
(875, 9, 'H', 12, 'H12', 'available', '2025-11-18 10:01:07'),
(876, 9, 'H', 13, 'H13', 'available', '2025-11-18 10:01:07'),
(877, 9, 'I', 1, 'I1', 'available', '2025-11-18 10:01:07'),
(878, 9, 'I', 2, 'I2', 'available', '2025-11-18 10:01:07'),
(879, 9, 'I', 3, 'I3', 'available', '2025-11-18 10:01:07'),
(880, 9, 'I', 4, 'I4', 'available', '2025-11-18 10:01:07'),
(881, 9, 'I', 5, 'I5', 'available', '2025-11-18 10:01:07'),
(882, 9, 'I', 6, 'I6', 'available', '2025-11-18 10:01:07'),
(883, 9, 'I', 7, 'I7', 'available', '2025-11-18 10:01:07'),
(884, 9, 'I', 8, 'I8', 'available', '2025-11-18 10:01:07'),
(885, 9, 'I', 9, 'I9', 'available', '2025-11-18 10:01:07'),
(886, 9, 'I', 10, 'I10', 'available', '2025-11-18 10:01:07'),
(887, 9, 'I', 11, 'I11', 'available', '2025-11-18 10:01:07'),
(888, 9, 'I', 12, 'I12', 'available', '2025-11-18 10:01:07'),
(889, 9, 'I', 13, 'I13', 'available', '2025-11-18 10:01:07'),
(890, 9, 'J', 1, 'J1', 'available', '2025-11-18 10:01:07'),
(891, 9, 'J', 2, 'J2', 'available', '2025-11-18 10:01:07'),
(892, 9, 'J', 3, 'J3', 'available', '2025-11-18 10:01:07'),
(893, 9, 'J', 4, 'J4', 'available', '2025-11-18 10:01:07'),
(894, 9, 'J', 5, 'J5', 'available', '2025-11-18 10:01:07'),
(895, 9, 'J', 6, 'J6', 'booked', '2025-11-18 10:01:07'),
(896, 9, 'J', 7, 'J7', 'available', '2025-11-18 10:01:07'),
(897, 9, 'J', 8, 'J8', 'available', '2025-11-18 10:01:07'),
(898, 9, 'J', 9, 'J9', 'available', '2025-11-18 10:01:07'),
(899, 9, 'J', 10, 'J10', 'available', '2025-11-18 10:01:07'),
(900, 9, 'J', 11, 'J11', 'available', '2025-11-18 10:01:07'),
(901, 9, 'J', 12, 'J12', 'available', '2025-11-18 10:01:07'),
(902, 9, 'J', 13, 'J13', 'available', '2025-11-18 10:01:07'),
(903, 9, 'K', 1, 'K1', 'available', '2025-11-18 10:01:07'),
(904, 9, 'K', 2, 'K2', 'available', '2025-11-18 10:01:07'),
(905, 9, 'K', 3, 'K3', 'available', '2025-11-18 10:01:07'),
(906, 9, 'K', 4, 'K4', 'available', '2025-11-18 10:01:07'),
(907, 9, 'K', 5, 'K5', 'available', '2025-11-18 10:01:07'),
(908, 9, 'K', 6, 'K6', 'available', '2025-11-18 10:01:07'),
(909, 9, 'K', 7, 'K7', 'available', '2025-11-18 10:01:07'),
(910, 9, 'K', 8, 'K8', 'available', '2025-11-18 10:01:07'),
(911, 9, 'K', 9, 'K9', 'available', '2025-11-18 10:01:07'),
(912, 9, 'K', 10, 'K10', 'available', '2025-11-18 10:01:07'),
(913, 9, 'K', 11, 'K11', 'available', '2025-11-18 10:01:07'),
(914, 9, 'K', 12, 'K12', 'available', '2025-11-18 10:01:07'),
(915, 9, 'L', 1, 'L1', 'available', '2025-11-18 10:01:07'),
(916, 9, 'L', 2, 'L2', 'available', '2025-11-18 10:01:07'),
(917, 9, 'L', 3, 'L3', 'available', '2025-11-18 10:01:07'),
(918, 9, 'L', 4, 'L4', 'available', '2025-11-18 10:01:07'),
(919, 9, 'L', 5, 'L5', 'available', '2025-11-18 10:01:07'),
(920, 9, 'L', 6, 'L6', 'available', '2025-11-18 10:01:07'),
(921, 9, 'L', 7, 'L7', 'available', '2025-11-18 10:01:07'),
(922, 9, 'L', 8, 'L8', 'available', '2025-11-18 10:01:07'),
(923, 9, 'L', 9, 'L9', 'available', '2025-11-18 10:01:07'),
(924, 9, 'L', 10, 'L10', 'available', '2025-11-18 10:01:07'),
(925, 9, 'L', 11, 'L11', 'available', '2025-11-18 10:01:07'),
(926, 9, 'L', 12, 'L12', 'available', '2025-11-18 10:01:07'),
(927, 9, 'M', 1, 'M1', 'available', '2025-11-18 10:01:07'),
(928, 9, 'M', 2, 'M2', 'available', '2025-11-18 10:01:07'),
(929, 9, 'M', 3, 'M3', 'available', '2025-11-18 10:01:07'),
(930, 9, 'M', 4, 'M4', 'available', '2025-11-18 10:01:07'),
(931, 9, 'M', 5, 'M5', 'available', '2025-11-18 10:01:07'),
(932, 9, 'M', 6, 'M6', 'available', '2025-11-18 10:01:07'),
(933, 9, 'M', 7, 'M7', 'available', '2025-11-18 10:01:07'),
(934, 9, 'M', 8, 'M8', 'available', '2025-11-18 10:01:07'),
(935, 9, 'M', 9, 'M9', 'available', '2025-11-18 10:01:07'),
(936, 9, 'M', 10, 'M10', 'available', '2025-11-18 10:01:07'),
(937, 9, 'M', 11, 'M11', 'available', '2025-11-18 10:01:07'),
(938, 9, 'N', 1, 'N1', 'available', '2025-11-18 10:01:07'),
(939, 9, 'N', 2, 'N2', 'available', '2025-11-18 10:01:07'),
(940, 9, 'N', 3, 'N3', 'available', '2025-11-18 10:01:07'),
(941, 9, 'N', 4, 'N4', 'available', '2025-11-18 10:01:07'),
(942, 9, 'N', 5, 'N5', 'available', '2025-11-18 10:01:07'),
(943, 9, 'N', 6, 'N6', 'available', '2025-11-18 10:01:07'),
(944, 9, 'N', 7, 'N7', 'available', '2025-11-18 10:01:07'),
(945, 9, 'N', 8, 'N8', 'available', '2025-11-18 10:01:07'),
(946, 9, 'N', 9, 'N9', 'available', '2025-11-18 10:01:07'),
(947, 9, 'O', 1, 'O1', 'available', '2025-11-18 10:01:07'),
(948, 9, 'O', 2, 'O2', 'available', '2025-11-18 10:01:07'),
(949, 9, 'O', 3, 'O3', 'available', '2025-11-18 10:01:07'),
(950, 9, 'O', 4, 'O4', 'available', '2025-11-18 10:01:07'),
(951, 9, 'O', 5, 'O5', 'available', '2025-11-18 10:01:07'),
(952, 9, 'O', 6, 'O6', 'available', '2025-11-18 10:01:07'),
(953, 9, 'O', 7, 'O7', 'available', '2025-11-18 10:01:07'),
(954, 9, 'O', 8, 'O8', 'available', '2025-11-18 10:01:07'),
(955, 9, 'O', 9, 'O9', 'available', '2025-11-18 10:01:07'),
(956, 9, 'P', 1, 'P1', 'available', '2025-11-18 10:01:07'),
(957, 9, 'P', 2, 'P2', 'available', '2025-11-18 10:01:07'),
(958, 9, 'P', 3, 'P3', 'available', '2025-11-18 10:01:07'),
(959, 9, 'P', 4, 'P4', 'available', '2025-11-18 10:01:07'),
(960, 9, 'P', 5, 'P5', 'available', '2025-11-18 10:01:07'),
(961, 9, 'P', 6, 'P6', 'available', '2025-11-18 10:01:07'),
(962, 9, 'P', 7, 'P7', 'available', '2025-11-18 10:01:07'),
(963, 9, 'P', 8, 'P8', 'available', '2025-11-18 10:01:07'),
(964, 9, 'P', 9, 'P9', 'available', '2025-11-18 10:01:07'),
(965, 9, 'P', 10, 'P10', 'available', '2025-11-18 10:01:07'),
(966, 9, 'P', 11, 'P11', 'available', '2025-11-18 10:01:07'),
(967, 9, 'P', 12, 'P12', 'available', '2025-11-18 10:01:07'),
(968, 9, 'P', 13, 'P13', 'available', '2025-11-18 10:01:07');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` varchar(50) DEFAULT 'text' COMMENT 'text, number, boolean, json',
  `description` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_at`) VALUES
(1, 'site_name', 'LAYAR ISOLA', 'text', 'Nama website', '2025-11-18 07:51:22'),
(2, 'studio_name', 'FPSD Auditorium', 'text', 'Nama studio bioskop', '2025-11-18 07:51:22'),
(3, 'total_seats', '176', 'number', 'Total kursi di studio', '2025-11-18 07:51:22'),
(4, 'seat_rows', '16', 'number', 'Jumlah baris kursi (A, B, C, D, E)', '2025-11-18 07:51:22'),
(5, 'seat_per_row', '10', 'number', 'Jumlah kursi per baris', '2025-11-18 07:51:22'),
(6, 'booking_timeout', '15', 'number', 'Waktu timeout booking dalam menit', '2025-11-18 07:51:22'),
(11, 'currency', 'IDR', 'text', 'Mata uang', '2025-11-13 03:24:54'),
(12, 'contact_email', 'layarisola@ftvupi.id', 'text', 'Email kontak', '2025-11-18 07:51:22'),
(13, 'contact_phone', '+62 851-1773-9866', 'text', 'Telepon kontak', '2025-11-18 07:51:22'),
(14, 'tripay_api_key', 'DEV-8BKDl6NCmDIAkqqNNM22KCejh60gDwA0GXq581Le', 'text', 'API Key dari Tripay untuk integrasi pembayaran QRIS', '2025-11-18 07:51:22'),
(15, 'tripay_private_key', 'BeiqB-96Uvk-rkUJo-H6cYj-yopRI', 'text', 'Private Key dari Tripay untuk signature', '2025-11-18 07:51:22'),
(16, 'tripay_merchant_code', 'T46915', 'text', 'Kode merchant Tripay', '2025-11-18 07:51:22'),
(17, 'tripay_environment', 'sandbox', 'text', 'Environment Tripay: sandbox/production', '2025-11-18 07:51:22'),
(18, 'smtp_host', 'mail.sleepwellindonesia.com', 'text', 'Host server SMTP untuk pengiriman email', '2025-11-18 07:51:22'),
(19, 'smtp_port', '587', 'number', 'Port SMTP (587 untuk TLS, 465 untuk SSL)', '2025-11-18 07:51:22'),
(20, 'smtp_username', 'no-reply@sleepwellindonesia.com', 'text', 'Username/email SMTP', '2025-11-18 07:51:22'),
(21, 'smtp_password', 'Otongkecil', 'text', 'Password SMTP/App Password', '2025-11-18 07:51:22'),
(22, 'site_email', 'no-reply@sleepwellindonesia.com', 'text', 'Email pengirim tiket', '2025-11-18 07:51:22');

-- --------------------------------------------------------

--
-- Table structure for table `sponsors`
--

CREATE TABLE `sponsors` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `image` varchar(255) NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `sponsors`
--

INSERT INTO `sponsors` (`id`, `name`, `image`, `url`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(7, 'Layar Isola', '691b1ad9aaf5d_1763384025.png', '', 1, 0, '2025-11-17 12:53:45', '2025-11-17 12:53:45'),
(8, 'Layar Isola', '691b1c8d4b0f0_1763384461.png', 'https://www.instagram.com/layarisola/?utm_source=ig_web_button_share_sheet', 1, 1, '2025-11-17 13:01:01', '2025-11-17 13:01:01');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `order_id` varchar(50) NOT NULL COMMENT 'Order ID untuk Midtrans',
  `reference` varchar(50) DEFAULT NULL,
  `merchant_ref` varchar(50) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `qr_string` text DEFAULT NULL,
  `qr_url` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'UNPAID',
  `paid_at` timestamp NULL DEFAULT NULL,
  `tripay_response` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `booking_id`, `order_id`, `reference`, `merchant_ref`, `amount`, `qr_string`, `qr_url`, `status`, `paid_at`, `tripay_response`, `created_at`, `updated_at`) VALUES
(27, 38, 'ORDER-ISOLA-5A42BE81-1763452326', 'DEV-T46915309640KML3B', 'BOOK-ISOLA-5A42BE81-1763452326', 8000.00, NULL, 'https://tripay.co.id/qr/DEV-T46915309640KML3B', 'PAID', '2025-11-18 07:53:32', '{\"reference\":\"DEV-T46915309640KML3B\",\"merchant_ref\":\"BOOK-ISOLA-5A42BE81-1763452326\",\"payment_method\":\"QRIS by ShopeePay\",\"payment_method_code\":\"QRIS\",\"total_amount\":8000,\"fee_merchant\":806,\"fee_customer\":0,\"total_fee\":806,\"amount_received\":7194,\"is_closed_payment\":1,\"status\":\"PAID\",\"paid_at\":1763452411,\"note\":null}', '2025-11-18 07:52:07', '2025-11-18 07:53:32'),
(28, 39, 'OFFLINE-ISOLA-B2B88201', 'OFFLINE-1763453739', 'OFFLINE-ISOLA-B2B88201', 8000.00, NULL, NULL, 'PAID', '2025-11-18 08:15:39', NULL, '2025-11-18 08:15:39', '2025-11-18 08:15:39'),
(29, 40, 'ORDER-ISOLA-F24BED6C-1763458862', 'DEV-T46915309674FGFTD', 'BOOK-ISOLA-F24BED6C-1763458862', 8000.00, NULL, 'https://tripay.co.id/qr/DEV-T46915309674FGFTD', 'PAID', '2025-11-18 09:41:21', '{\"success\":true,\"message\":\"\",\"data\":{\"reference\":\"DEV-T46915309674FGFTD\",\"merchant_ref\":\"BOOK-ISOLA-F24BED6C-1763458862\",\"payment_selection_type\":\"static\",\"payment_method\":\"QRIS\",\"payment_name\":\"QRIS by ShopeePay\",\"customer_name\":\"JOAN\",\"customer_email\":\"myudar2301@gmail.com\",\"customer_phone\":\"081233009283\",\"callback_url\":\"https:\\/\\/sleepwellindonesia.com\\/isolascreen\\/payment-callback.php\",\"return_url\":\"https:\\/\\/sleepwellindonesia.com\\/booking-success.php?booking=ISOLA-F24BED6C\",\"amount\":8000,\"fee_merchant\":806,\"fee_customer\":0,\"total_fee\":806,\"amount_received\":7194,\"pay_code\":null,\"pay_url\":null,\"checkout_url\":\"https:\\/\\/tripay.co.id\\/checkout\\/DEV-T46915309674FGFTD\",\"status\":\"UNPAID\",\"expired_time\":1763462404,\"order_items\":[{\"sku\":null,\"name\":\"Tiket Negana\",\"price\":8000,\"quantity\":1,\"subtotal\":8000,\"product_url\":null,\"image_url\":null}],\"instructions\":[{\"title\":\"Pembayaran via QRIS (ShopeePay)\",\"steps\":[\"Masuk ke aplikasi dompet digital Anda yang telah mendukung QRIS\",\"Pindai\\/Scan QR Code yang tersedia\",\"Akan muncul detail transaksi. Pastikan data transaksi sudah sesuai\",\"Selesaikan proses pembayaran Anda\",\"Transaksi selesai. Simpan bukti pembayaran Anda\"]},{\"title\":\"Pembayaran via QRIS (Mobile)\",\"steps\":[\"Download QR Code pada invoice\",\"Masuk ke aplikasi dompet digital Anda yang telah mendukung QRIS\",\"Upload QR Code yang telah di download tadi\",\"Akan muncul detail transaksi. Pastikan data transaksi sudah sesuai\",\"Selesaikan proses pembayaran Anda\",\"Transaksi selesai. Simpan bukti pembayaran Anda\"]}],\"qr_string\":\"SANDBOX MODE\",\"qr_url\":\"https:\\/\\/tripay.co.id\\/qr\\/DEV-T46915309674FGFTD\"}}', '2025-11-18 09:41:02', '2025-11-18 09:41:21'),
(30, 41, 'ORDER-ISOLA-FEB818A4-1763459053', 'DEV-T469153096768J5KZ', 'BOOK-ISOLA-FEB818A4-1763459053', 8000.00, NULL, 'https://tripay.co.id/qr/DEV-T469153096768J5KZ', 'PAID', '2025-11-18 09:44:26', '{\"reference\":\"DEV-T469153096768J5KZ\",\"merchant_ref\":\"BOOK-ISOLA-FEB818A4-1763459053\",\"payment_method\":\"QRIS by ShopeePay\",\"payment_method_code\":\"QRIS\",\"total_amount\":8000,\"fee_merchant\":806,\"fee_customer\":0,\"total_fee\":806,\"amount_received\":7194,\"is_closed_payment\":1,\"status\":\"PAID\",\"paid_at\":1763459068,\"note\":null}', '2025-11-18 09:44:13', '2025-11-18 09:44:26'),
(31, 42, 'ORDER-ISOLA-0CF7DAB2-1763459281', 'DEV-T46915309678REXAL', 'BOOK-ISOLA-0CF7DAB2-1763459281', 8000.00, NULL, 'https://tripay.co.id/qr/DEV-T46915309678REXAL', 'PAID', '2025-11-18 09:48:17', '{\"reference\":\"DEV-T46915309678REXAL\",\"merchant_ref\":\"BOOK-ISOLA-0CF7DAB2-1763459281\",\"payment_method\":\"QRIS by ShopeePay\",\"payment_method_code\":\"QRIS\",\"total_amount\":8000,\"fee_merchant\":806,\"fee_customer\":0,\"total_fee\":806,\"amount_received\":7194,\"is_closed_payment\":1,\"status\":\"PAID\",\"paid_at\":1763459298,\"note\":null}', '2025-11-18 09:48:02', '2025-11-18 09:48:17'),
(32, 43, 'ORDER-ISOLA-30B544B1-1763459853', 'DEV-T469153096823R7DD', 'BOOK-ISOLA-30B544B1-1763459853', 8000.00, NULL, 'https://tripay.co.id/qr/DEV-T469153096823R7DD', 'PAID', '2025-11-18 09:57:55', '{\"reference\":\"DEV-T469153096823R7DD\",\"merchant_ref\":\"BOOK-ISOLA-30B544B1-1763459853\",\"payment_method\":\"QRIS by ShopeePay\",\"payment_method_code\":\"QRIS\",\"total_amount\":8000,\"fee_merchant\":806,\"fee_customer\":0,\"total_fee\":806,\"amount_received\":7194,\"is_closed_payment\":1,\"status\":\"PAID\",\"paid_at\":1763459877,\"note\":null}', '2025-11-18 09:57:33', '2025-11-18 09:57:55'),
(33, 44, 'ORDER-ISOLA-3F16145D-1763460083', 'DEV-T46915309683ACPCP', 'BOOK-ISOLA-3F16145D-1763460083', 20000.00, NULL, 'https://tripay.co.id/qr/DEV-T46915309683ACPCP', 'PAID', '2025-11-18 10:01:45', '{\"reference\":\"DEV-T46915309683ACPCP\",\"merchant_ref\":\"BOOK-ISOLA-3F16145D-1763460083\",\"payment_method\":\"QRIS by ShopeePay\",\"payment_method_code\":\"QRIS\",\"total_amount\":20000,\"fee_merchant\":890,\"fee_customer\":0,\"total_fee\":890,\"amount_received\":19110,\"is_closed_payment\":1,\"status\":\"PAID\",\"paid_at\":1763460106,\"note\":null}', '2025-11-18 10:01:24', '2025-11-18 10:01:45'),
(34, 45, 'ORDER-ISOLA-E6C91B8D-1763462766', 'DEV-T469153096898CMXO', 'BOOK-ISOLA-E6C91B8D-1763462766', 8000.00, NULL, 'https://tripay.co.id/qr/DEV-T469153096898CMXO', 'PAID', '2025-11-18 10:46:32', '{\"reference\":\"DEV-T469153096898CMXO\",\"merchant_ref\":\"BOOK-ISOLA-E6C91B8D-1763462766\",\"payment_method\":\"QRIS by ShopeePay\",\"payment_method_code\":\"QRIS\",\"total_amount\":8000,\"fee_merchant\":806,\"fee_customer\":0,\"total_fee\":806,\"amount_received\":7194,\"is_closed_payment\":1,\"status\":\"PAID\",\"paid_at\":1763462792,\"note\":null}', '2025-11-18 10:46:07', '2025-11-18 10:46:32');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_code` (`booking_code`),
  ADD KEY `schedule_id` (`schedule_id`),
  ADD KEY `idx_booking_code` (`booking_code`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_booking_date` (`booking_date`);

--
-- Indexes for table `films`
--
ALTER TABLE `films`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_show_date` (`show_date`),
  ADD KEY `idx_film_date` (`film_id`,`show_date`);

--
-- Indexes for table `seats`
--
ALTER TABLE `seats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_seat` (`schedule_id`,`seat_label`),
  ADD KEY `idx_schedule_status` (`schedule_id`,`status`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `sponsors`
--
ALTER TABLE `sponsors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `idx_order_id` (`order_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `films`
--
ALTER TABLE `films`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `seats`
--
ALTER TABLE `seats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=969;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `sponsors`
--
ALTER TABLE `sponsors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`film_id`) REFERENCES `films` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seats`
--
ALTER TABLE `seats`
  ADD CONSTRAINT `seats_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
