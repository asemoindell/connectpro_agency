-- phpMyAdmin SQL Dump
-- version 5.0.4
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 04, 2025 at 07:42 PM
-- Server version: 10.4.16-MariaDB
-- PHP Version: 7.4.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `connectpro_agency`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super-admin','content-admin','service-admin') DEFAULT 'content-admin',
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `first_name`, `last_name`, `email`, `password`, `role`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'Super', 'Admin', 'admin@connectpro.com', '$2y$10$UiJvfA7tQoLSbeZIrQQ0LuSlYOmpRRSoTB7n6qj8ZI2yffAc96eHq', 'super-admin', 'active', '2025-07-04 16:29:42', '2025-07-03 07:37:56', '2025-07-04 16:29:42'),
(2, 'Content', 'Manager', 'content@connectpro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'content-admin', 'active', NULL, '2025-07-03 07:37:56', '2025-07-03 07:37:56');

-- --------------------------------------------------------

--
-- Table structure for table `admin_activity_log`
--

CREATE TABLE `admin_activity_log` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `affected_user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `admin_activity_log`
--

INSERT INTO `admin_activity_log` (`id`, `admin_id`, `action`, `description`, `affected_user_id`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'logout', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-07-03 14:44:16'),
(2, 1, 'logout', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-07-03 21:34:55'),
(3, 1, 'logout', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-07-04 07:30:12'),
(4, 1, 'logout', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-07-04 09:49:19'),
(5, 1, 'logout', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-07-04 10:52:09'),
(6, 1, 'logout', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-07-04 10:55:32'),
(7, 1, 'logout', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-07-04 10:56:48'),
(8, 1, 'logout', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-07-04 11:50:03'),
(9, 1, 'logout', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-07-04 11:52:02');

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `specialization` varchar(200) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `profile_image` varchar(500) DEFAULT NULL,
  `hourly_rate` decimal(10,2) DEFAULT 0.00,
  `commission_rate` decimal(5,2) DEFAULT 0.00,
  `is_available` tinyint(1) DEFAULT 1,
  `password` varchar(255) NOT NULL,
  `role` enum('super-admin','content-admin','service-admin') DEFAULT 'content-admin',
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `first_name`, `last_name`, `email`, `username`, `phone`, `specialization`, `bio`, `profile_image`, `hourly_rate`, `commission_rate`, `is_available`, `password`, `role`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'John', 'Admin', 'admin@connectpro.com', 'admin@connectpro.com', '', '', '', NULL, '0.00', '0.00', 1, '$2y$10$wY8BF0beJSMCx0NKwn.6EekE8HUDy5DmedvrEFBGkfsRiiMJVnr6a', 'service-admin', 'active', '2025-07-04 09:49:29', '2025-07-02 23:49:05', '2025-07-04 09:52:03'),
(2, 'Sarah', 'Content', 'content@connectpro.com', 'content@connectpro.com', '', '', '', NULL, '12.00', '100.00', 1, '$2y$10$S6hYODeG77RFF5bUUzT8SuTe075eRNQzXt6Qiuz8O9C8VDlEDAmua', 'service-admin', 'active', NULL, '2025-07-02 23:49:05', '2025-07-04 08:19:53'),
(7, 'Test', 'User', 'test@example.com', 'test@example.com', '', '', '', NULL, '0.00', '0.00', 1, '$2y$10$mV/W3iP0ut2B2bAMKUnKk.NeZPP6YLPDrp/vnXu88bJIphRSHw6Su', 'service-admin', 'active', '2025-07-04 07:30:19', '2025-07-03 01:07:56', '2025-07-04 17:17:28');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `booking_reference` varchar(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `service_name` varchar(200) DEFAULT NULL,
  `client_name` varchar(200) NOT NULL,
  `client_email` varchar(200) NOT NULL,
  `client_phone` varchar(20) DEFAULT NULL,
  `service_details` text DEFAULT NULL,
  `urgency_level` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('pending','confirmed','waiting_approval','approved','payment_pending','paid','in_progress','completed','cancelled') DEFAULT 'pending',
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `quoted_price` decimal(10,2) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `assigned_admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `message_id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `sender_type` enum('user','admin') NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `message_type` enum('text','file','image') DEFAULT 'text',
  `file_url` varchar(500) DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`message_id`, `room_id`, `sender_type`, `sender_id`, `message`, `message_type`, `file_url`, `sent_at`, `read_at`) VALUES
(5, 1, 'user', 1, 'Hello! I have a question about my booking.', 'text', NULL, '2025-07-03 20:29:31', NULL),
(6, 1, 'admin', 1, 'Hi! I would be happy to help you. What can I assist you with today?', 'text', NULL, '2025-07-03 20:31:31', NULL),
(7, 1, 'user', 1, 'I would like to know the timeline for my service request.', 'text', NULL, '2025-07-03 20:34:31', NULL),
(8, 2, 'user', 2, 'Hi there! When can we schedule the consultation?', 'text', NULL, '2025-07-03 20:24:31', NULL),
(9, 2, 'admin', 2, 'Hello! I can schedule your consultation for this week. What days work best for you?', 'text', NULL, '2025-07-03 20:27:31', NULL),
(10, 5, 'user', 1, 'hello', 'text', NULL, '2025-07-04 09:46:44', NULL),
(11, 7, 'user', 1, 'hello', 'text', NULL, '2025-07-04 09:47:04', NULL),
(12, 8, 'user', 1, 'hello', 'text', NULL, '2025-07-04 09:49:41', '2025-07-04 09:49:50'),
(13, 8, 'user', 1, 'how are u', 'text', NULL, '2025-07-04 09:49:46', '2025-07-04 09:49:50');

-- --------------------------------------------------------

--
-- Table structure for table `chat_permissions`
--

CREATE TABLE `chat_permissions` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `can_chat` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `chat_rooms`
--

CREATE TABLE `chat_rooms` (
  `room_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `status` enum('active','closed','archived') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_message_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `chat_rooms`
--

INSERT INTO `chat_rooms` (`room_id`, `booking_id`, `user_id`, `admin_id`, `status`, `created_at`, `last_message_at`) VALUES
(5, 1, 1, 1, 'active', '2025-07-03 20:39:31', '2025-07-04 09:46:44'),
(6, 2, 2, 2, 'active', '2025-07-03 20:39:31', '2025-07-03 20:39:31'),
(7, 3, 1, 1, 'active', '2025-07-04 09:46:57', '2025-07-04 09:47:04'),
(8, 4, 1, 1, 'active', '2025-07-04 09:47:13', '2025-07-04 09:49:46');

-- --------------------------------------------------------

--
-- Table structure for table `contact_inquiries`
--

CREATE TABLE `contact_inquiries` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `service` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('new','in-progress','resolved','closed') DEFAULT 'new',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `content_pages`
--

CREATE TABLE `content_pages` (
  `id` int(11) NOT NULL,
  `page_name` varchar(50) NOT NULL,
  `section_name` varchar(100) NOT NULL,
  `content_key` varchar(100) NOT NULL,
  `content_value` text DEFAULT NULL,
  `content_type` enum('text','html','json','image') DEFAULT 'text',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `content_pages`
--

INSERT INTO `content_pages` (`id`, `page_name`, `section_name`, `content_key`, `content_value`, `content_type`, `created_at`, `updated_at`) VALUES
(8, 'home', 'hero', 'title', 'Your Trusted Service Connection Hub', 'text', '2025-07-03 07:37:56', '2025-07-03 07:37:56'),
(9, 'home', 'hero', 'subtitle', 'Connect with professional agents, book flights, make reservations, find lawyers, get tax assistance, hire engineers, and access all professional services in one place.', 'text', '2025-07-03 07:37:56', '2025-07-03 07:37:56'),
(10, 'home', 'stats', 'clients', '10000', 'text', '2025-07-03 07:37:56', '2025-07-03 07:37:56'),
(11, 'home', 'stats', 'services', '500', 'text', '2025-07-03 07:37:56', '2025-07-03 07:37:56'),
(12, 'home', 'stats', 'satisfaction', '99', 'text', '2025-07-03 07:37:56', '2025-07-03 07:37:56'),
(13, 'home', 'stats', 'support', '24/7', 'text', '2025-07-03 07:37:56', '2025-07-03 07:37:56');

-- --------------------------------------------------------

--
-- Table structure for table `crypto_payments`
--

CREATE TABLE `crypto_payments` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `payment_method` enum('btc','usdt') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_address` varchar(100) NOT NULL,
  `transaction_hash` varchar(100) DEFAULT NULL,
  `status` enum('pending','verifying','confirmed','failed','cancelled') NOT NULL DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `crypto_payments`
--

INSERT INTO `crypto_payments` (`id`, `booking_id`, `payment_method`, `amount`, `payment_address`, `transaction_hash`, `status`, `admin_notes`, `verified_by`, `verified_at`, `created_at`, `updated_at`) VALUES
(1, 25, 'btc', '185.00', '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa', NULL, 'cancelled', NULL, NULL, NULL, '2025-07-04 12:13:02', '2025-07-04 16:32:47'),
(3, 27, 'btc', '63.00', '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa', NULL, 'pending', NULL, NULL, NULL, '2025-07-04 12:58:27', '2025-07-04 12:58:27'),
(4, 28, 'btc', '248.00', '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa', NULL, 'verifying', NULL, NULL, NULL, '2025-07-04 13:23:41', '2025-07-04 16:22:54'),
(5, 29, 'btc', '14490.00', '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa', NULL, 'verifying', NULL, NULL, NULL, '2025-07-04 16:23:49', '2025-07-04 16:23:55'),
(6, 30, 'btc', '248.00', '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa', NULL, 'verifying', NULL, NULL, NULL, '2025-07-04 16:37:30', '2025-07-04 16:37:41'),
(7, 31, 'btc', '14490.00', '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa', NULL, 'cancelled', NULL, NULL, NULL, '2025-07-04 16:38:09', '2025-07-04 16:38:12');

-- --------------------------------------------------------

--
-- Table structure for table `crypto_wallets`
--

CREATE TABLE `crypto_wallets` (
  `id` int(11) NOT NULL,
  `currency` enum('usdt','bitcoin') NOT NULL,
  `network` varchar(50) DEFAULT 'mainnet',
  `address` varchar(200) NOT NULL,
  `label` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `crypto_wallets`
--

INSERT INTO `crypto_wallets` (`id`, `currency`, `network`, `address`, `label`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'bitcoin', 'mainnet', '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa', 'Primary Bitcoin Wallet (Demo)', 1, '2025-07-03 13:13:56', '2025-07-03 13:13:56'),
(2, 'usdt', 'mainnet', 'TQNDzxPm9qNcfEuaGc7w2YW8nA5K8v5Z2m', 'Primary USDT Wallet TRC20 (Demo)', 1, '2025-07-03 13:13:56', '2025-07-03 13:13:56');

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL,
  `template_type` varchar(50) DEFAULT NULL,
  `recipient_email` varchar(255) NOT NULL,
  `recipient_name` varchar(255) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `status` enum('sent','failed','pending') DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `email_notifications`
--

CREATE TABLE `email_notifications` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `recipient_email` varchar(200) NOT NULL,
  `email_type` enum('booking_confirmation','approval_notice','payment_reminder','chat_invitation','completion_notice') NOT NULL,
  `subject` varchar(300) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('sent','failed','pending') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `email_notifications`
--

INSERT INTO `email_notifications` (`id`, `booking_id`, `recipient_email`, `email_type`, `subject`, `content`, `sent_at`, `status`) VALUES
(5, 9, 'test@example.com', 'booking_confirmation', 'Booking Confirmation - Reference: CP2025392B22', '\n        <html>\n        <head>\n            <style>\n                .email-container { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; }\n                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }\n                .content { padding: 20px; background: #f9f9f9; }\n                .booking-details { background: white; padding: 15px; border-radius: 8px; margin: 15px 0; }\n                .footer { background: #333; color: white; padding: 15px; text-align: center; font-size: 12px; }\n                .highlight { color: #667eea; font-weight: bold; }\n            </style>\n        </head>\n        <body>\n            <div class=\'email-container\'>\n                <div class=\'header\'>\n                    <h1>🎉 Booking Confirmed!</h1>\n                    <p>Thank you for choosing ConnectPro Agency</p>\n                </div>\n                \n                <div class=\'content\'>\n                    <p>Dear Test Client,</p>\n                    \n                    <p>We have successfully received your service booking request. Here are the details:</p>\n                    \n                    <div class=\'booking-details\'>\n                        <h3>Booking Information</h3>\n                        <p><strong>Reference Number:</strong> <span class=\'highlight\'>CP2025392B22</span></p>\n                        <p><strong>Service:</strong> Legal Document Review</p>\n                        <p><strong>Booking Date:</strong> July 3, 2025 10:04 AM</p>\n                        <p><strong>Status:</strong> Confirmed - Pending Review</p>\n                    </div>\n                    \n                    <div class=\'booking-details\'>\n                        <h3>⏰ What Happens Next?</h3>\n                        <ol>\n                            <li><strong>Review Period:</strong> Our team will review your request within 3-4 business days</li>\n                            <li><strong>Approval:</strong> You\'ll receive approval notification by <strong>2025-07-07</strong></li>\n                            <li><strong>Payment:</strong> After approval, you\'ll receive payment details</li>\n                            <li><strong>Service Delivery:</strong> Once paid, we\'ll connect you with your dedicated agent</li>\n                        </ol>\n                    </div>\n                    \n                    <div class=\'booking-details\'>\n                        <h3>📞 Need Help?</h3>\n                        <p>If you have any questions, please don\'t hesitate to contact us:</p>\n                        <p>📧 Email: support@connectpro.com</p>\n                        <p>📱 Phone: +1 (555) 123-4567</p>\n                    </div>\n                </div>\n                \n                <div class=\'footer\'>\n                    <p>&copy; 2025 ConnectPro Agency. All rights reserved.</p>\n                    <p>You\'re receiving this email because you made a booking with us.</p>\n                </div>\n            </div>\n        </body>\n        </html>', '2025-07-03 09:04:19', 'sent'),
(6, 10, 'asemoindell@gmail.com', 'booking_confirmation', 'Booking Confirmation - Reference: CP202538B289', '\n        <html>\n        <head>\n            <style>\n                .email-container { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; }\n                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }\n                .content { padding: 20px; background: #f9f9f9; }\n                .booking-details { background: white; padding: 15px; border-radius: 8px; margin: 15px 0; }\n                .footer { background: #333; color: white; padding: 15px; text-align: center; font-size: 12px; }\n                .highlight { color: #667eea; font-weight: bold; }\n            </style>\n        </head>\n        <body>\n            <div class=\'email-container\'>\n                <div class=\'header\'>\n                    <h1>🎉 Booking Confirmed!</h1>\n                    <p>Thank you for choosing ConnectPro Agency</p>\n                </div>\n                \n                <div class=\'content\'>\n                    <p>Dear Asemota Osasumwen,</p>\n                    \n                    <p>We have successfully received your service booking request. Here are the details:</p>\n                    \n                    <div class=\'booking-details\'>\n                        <h3>Booking Information</h3>\n                        <p><strong>Reference Number:</strong> <span class=\'highlight\'>CP202538B289</span></p>\n                        <p><strong>Service:</strong> Tax Consultation</p>\n                        <p><strong>Booking Date:</strong> July 3, 2025 10:07 AM</p>\n                        <p><strong>Status:</strong> Confirmed - Pending Review</p>\n                    </div>\n                    \n                    <div class=\'booking-details\'>\n                        <h3>⏰ What Happens Next?</h3>\n                        <ol>\n                            <li><strong>Review Period:</strong> Our team will review your request within 3-4 business days</li>\n                            <li><strong>Approval:</strong> You\'ll receive approval notification by <strong>2025-07-07</strong></li>\n                            <li><strong>Payment:</strong> After approval, you\'ll receive payment details</li>\n                            <li><strong>Service Delivery:</strong> Once paid, we\'ll connect you with your dedicated agent</li>\n                        </ol>\n                    </div>\n                    \n                    <div class=\'booking-details\'>\n                        <h3>📞 Need Help?</h3>\n                        <p>If you have any questions, please don\'t hesitate to contact us:</p>\n                        <p>📧 Email: support@connectpro.com</p>\n                        <p>📱 Phone: +1 (555) 123-4567</p>\n                    </div>\n                </div>\n                \n                <div class=\'footer\'>\n                    <p>&copy; 2025 ConnectPro Agency. All rights reserved.</p>\n                    <p>You\'re receiving this email because you made a booking with us.</p>\n                </div>\n            </div>\n        </body>\n        </html>', '2025-07-03 09:07:31', 'sent'),
(7, 11, 'asemoindell@gmail.com', 'booking_confirmation', 'Booking Confirmation - Reference: CP20250223B6', '\n        <html>\n        <head>\n            <style>\n                .email-container { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; }\n                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }\n                .content { padding: 20px; background: #f9f9f9; }\n                .booking-details { background: white; padding: 15px; border-radius: 8px; margin: 15px 0; }\n                .footer { background: #333; color: white; padding: 15px; text-align: center; font-size: 12px; }\n                .highlight { color: #667eea; font-weight: bold; }\n            </style>\n        </head>\n        <body>\n            <div class=\'email-container\'>\n                <div class=\'header\'>\n                    <h1>🎉 Booking Confirmed!</h1>\n                    <p>Thank you for choosing ConnectPro Agency</p>\n                </div>\n                \n                <div class=\'content\'>\n                    <p>Dear Asemota Osasumwen,</p>\n                    \n                    <p>We have successfully received your service booking request. Here are the details:</p>\n                    \n                    <div class=\'booking-details\'>\n                        <h3>Booking Information</h3>\n                        <p><strong>Reference Number:</strong> <span class=\'highlight\'>CP20250223B6</span></p>\n                        <p><strong>Service:</strong> Tax Consultation</p>\n                        <p><strong>Booking Date:</strong> July 3, 2025 10:07 AM</p>\n                        <p><strong>Status:</strong> Confirmed - Pending Review</p>\n                    </div>\n                    \n                    <div class=\'booking-details\'>\n                        <h3>⏰ What Happens Next?</h3>\n                        <ol>\n                            <li><strong>Review Period:</strong> Our team will review your request within 3-4 business days</li>\n                            <li><strong>Approval:</strong> You\'ll receive approval notification by <strong>2025-07-07</strong></li>\n                            <li><strong>Payment:</strong> After approval, you\'ll receive payment details</li>\n                            <li><strong>Service Delivery:</strong> Once paid, we\'ll connect you with your dedicated agent</li>\n                        </ol>\n                    </div>\n                    \n                    <div class=\'booking-details\'>\n                        <h3>📞 Need Help?</h3>\n                        <p>If you have any questions, please don\'t hesitate to contact us:</p>\n                        <p>📧 Email: support@connectpro.com</p>\n                        <p>📱 Phone: +1 (555) 123-4567</p>\n                    </div>\n                </div>\n                \n                <div class=\'footer\'>\n                    <p>&copy; 2025 ConnectPro Agency. All rights reserved.</p>\n                    <p>You\'re receiving this email because you made a booking with us.</p>\n                </div>\n            </div>\n        </body>\n        </html>', '2025-07-03 09:07:44', 'sent');

-- --------------------------------------------------------

--
-- Table structure for table `email_templates`
--

CREATE TABLE `email_templates` (
  `id` int(11) NOT NULL,
  `template_type` varchar(50) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) NOT NULL DEFAULT 1,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `email_templates`
--

INSERT INTO `email_templates` (`id`, `template_type`, `subject`, `content`, `is_active`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'user_registration', 'Welcome to ConnectPro Agency!', 'Hello {{user_name}},\\n\\nThank you for registering with ConnectPro Agency! Your account has been created successfully.\\n\\nYour registration details:\\n- Name: {{user_name}}\\n- Email: {{user_email}}\\n- Registration Date: {{registration_date}}\\n\\nYour account is currently pending approval. Once approved, you will receive an email confirmation and can start booking our services.\\n\\nIf you have any questions, please contact our support team.\\n\\nBest regards,\\nConnectPro Agency Team', 1, 1, NULL, '2025-07-03 09:32:55', '2025-07-03 09:32:55'),
(2, 'user_login', 'Login Notification - ConnectPro Agency', 'Hello {{user_name}},\\n\\nWe noticed a login to your ConnectPro Agency account.\\n\\nLogin Details:\\n- Time: {{login_time}}\\n- IP Address: {{ip_address}}\\n- Location: {{location}}\\n- Device: {{user_agent}}\\n\\nIf this wasn\'t you, please contact our support team immediately.\\n\\nBest regards,\\nConnectPro Agency Team', 1, 1, NULL, '2025-07-03 09:32:55', '2025-07-03 09:32:55'),
(3, 'user_approval', 'Account Approved - Welcome to ConnectPro Agency!', 'Hello {{user_name}},\\n\\nGreat news! Your ConnectPro Agency account has been approved!\\n\\nYou can now:\\n- Book services from our catalog\\n- Track your booking status\\n- Communicate with our agents\\n- Access your dashboard\\n\\nLogin to your account: {{login_url}}\\n\\nAdmin Notes: {{admin_notes}}\\n\\nWelcome aboard!\\n\\nBest regards,\\nConnectPro Agency Team', 1, 1, NULL, '2025-07-03 09:32:55', '2025-07-03 09:32:55'),
(4, 'user_rejection', 'Account Registration - ConnectPro Agency', 'Hello {{user_name}},\\n\\nThank you for your interest in ConnectPro Agency. After reviewing your registration, we regret to inform you that we cannot approve your account at this time.\\n\\nReason: {{rejection_reason}}\\n\\nIf you have any questions or would like to reapply, please contact our support team.\\n\\nBest regards,\\nConnectPro Agency Team', 1, 1, NULL, '2025-07-03 09:32:55', '2025-07-03 09:32:55'),
(5, 'booking_approval', 'Booking Approved - {{booking_reference}}', 'Hello {{client_name}},\\n\\nExcellent news! Your booking has been approved.\\n\\nBooking Details:\\n- Reference: {{booking_reference}}\\n- Service: {{service_name}}\\n- Agent: {{agent_name}}\\n- Final Price: ${{final_price}}\\n\\nNext Steps:\\n{{next_steps}}\\n\\nPayment Link: {{payment_url}}\\n\\nBest regards,\\nConnectPro Agency Team', 1, 1, NULL, '2025-07-03 09:32:55', '2025-07-03 09:32:55');

-- --------------------------------------------------------

--
-- Table structure for table `inquiries`
--

CREATE TABLE `inquiries` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `service_category` varchar(50) DEFAULT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `status` enum('new','contacted','in-progress','resolved','closed') DEFAULT 'new',
  `assigned_admin_id` int(11) DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_type` enum('user','admin') NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT 'general',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `payment_reference` varchar(50) NOT NULL,
  `payment_method` enum('stripe','paypal','usdt','bitcoin','bank_transfer') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'USD',
  `payment_status` enum('pending','processing','completed','failed','refunded') DEFAULT 'pending',
  `gateway_transaction_id` varchar(200) DEFAULT NULL,
  `gateway_response` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `booking_id`, `payment_reference`, `payment_method`, `amount`, `currency`, `payment_status`, `gateway_transaction_id`, `gateway_response`, `admin_notes`, `paid_at`, `created_at`) VALUES
(1, 11, 'TEST_USDT_20250703_3840', 'usdt', '100.00', 'USD', 'pending', NULL, NULL, NULL, NULL, '2025-07-03 13:18:27'),
(2, 11, 'TEST_BTC_20250703_2173', 'bitcoin', '250.00', 'USD', 'pending', NULL, NULL, NULL, NULL, '2025-07-03 13:18:27'),
(3, 5, 'PAY2025001', 'stripe', '248.00', 'USD', 'completed', NULL, NULL, NULL, '2025-07-03 21:13:24', '2025-07-03 21:13:24'),
(4, 8, 'PAY2025002', 'paypal', '248.00', 'USD', 'processing', NULL, NULL, '', NULL, '2025-07-03 21:13:24');

-- --------------------------------------------------------

--
-- Table structure for table `payment_fees`
--

CREATE TABLE `payment_fees` (
  `id` int(11) NOT NULL,
  `payment_method` enum('stripe','paypal','usdt','bitcoin','bank_transfer') NOT NULL,
  `fee_type` enum('percentage','fixed','combined') DEFAULT 'percentage',
  `percentage_fee` decimal(5,4) DEFAULT 0.0000,
  `fixed_fee` decimal(10,2) DEFAULT 0.00,
  `minimum_fee` decimal(10,2) DEFAULT 0.00,
  `maximum_fee` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `payment_fees`
--

INSERT INTO `payment_fees` (`id`, `payment_method`, `fee_type`, `percentage_fee`, `fixed_fee`, `minimum_fee`, `maximum_fee`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'stripe', 'combined', '0.0290', '0.30', '0.30', NULL, 1, '2025-07-03 13:13:56', '2025-07-03 13:13:56'),
(2, 'paypal', 'combined', '0.0349', '0.49', '0.49', NULL, 1, '2025-07-03 13:13:56', '2025-07-03 13:13:56'),
(3, 'usdt', 'fixed', '0.0000', '2.00', '2.00', NULL, 1, '2025-07-03 13:13:56', '2025-07-03 13:13:56'),
(4, 'bitcoin', 'percentage', '0.0050', '0.00', '0.00', NULL, 1, '2025-07-03 13:13:56', '2025-07-03 13:13:56'),
(5, 'bank_transfer', 'fixed', '0.0000', '5.00', '5.00', NULL, 1, '2025-07-03 13:13:56', '2025-07-03 13:13:56');

-- --------------------------------------------------------

--
-- Stand-in structure for view `payment_method_stats`
-- (See below for the actual view)
--
CREATE TABLE `payment_method_stats` (
`payment_method` enum('stripe','paypal','usdt','bitcoin','bank_transfer')
,`transaction_count` bigint(21)
,`total_amount` decimal(32,2)
,`average_amount` decimal(14,6)
,`completed_amount` decimal(32,2)
,`completed_count` bigint(21)
,`pending_count` bigint(21)
,`failed_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `short_description` varchar(255) DEFAULT NULL,
  `price_range` varchar(50) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `image_url` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `title`, `slug`, `description`, `short_description`, `price_range`, `category`, `features`, `image_url`, `is_featured`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Travel Agent Services', 'travel-agent', 'Professional travel planning and booking services for business and leisure trips.', 'Expert travel planning and booking assistance', '$50 - $200', 'Travel', '[\"Flight booking\", \"Hotel reservations\", \"Travel insurance\", \"Itinerary planning\"]', '', 1, 'active', '2025-07-02 23:49:05', '2025-07-03 20:16:58'),
(2, 'Legal Consultation', 'legal-consultation', 'Professional legal advice and consultation services from experienced attorneys.', 'Expert legal advice and consultation', '$100 - $500', 'Legal', '[\"Contract review\", \"Legal advice\", \"Document preparation\", \"Court representation\"]', NULL, 1, 'active', '2025-07-02 23:49:05', '2025-07-02 23:49:05'),
(3, 'Tax Preparation', 'tax-preparation', 'Professional tax preparation and filing services for individuals and businesses.', 'Professional tax preparation and filing', '$75 - $300', 'Finance', '[\"Tax filing\", \"Tax planning\", \"Audit support\", \"Financial consultation\"]', NULL, 1, 'active', '2025-07-02 23:49:05', '2025-07-02 23:49:05'),
(4, 'Engineering Services', 'engineering', 'Professional engineering consultation and project management services.', 'Expert engineering consultation', '$100 - $400', 'Technical', '[\"Project consultation\", \"Technical analysis\", \"Design review\", \"Implementation support\"]', NULL, 0, 'active', '2025-07-02 23:49:05', '2025-07-02 23:49:05'),
(5, 'Event Planning', 'event-planning', 'Complete event planning and management services for corporate and personal events.', 'Professional event planning services', '$200 - $1000', 'Events', '[\"Venue booking\", \"Catering coordination\", \"Entertainment booking\", \"Event management\"]', NULL, 0, 'active', '2025-07-02 23:49:05', '2025-07-02 23:49:05'),
(6, 'Financial Advisory', 'financial-advisory', 'Professional financial planning and investment advisory services.', 'Expert financial planning and advice', '$150 - $600', 'Finance', '[\"Investment planning\", \"Retirement planning\", \"Insurance advice\", \"Wealth management\"]', NULL, 1, 'active', '2025-07-02 23:49:05', '2025-07-02 23:49:05');

-- --------------------------------------------------------

--
-- Table structure for table `services_enhanced`
--

CREATE TABLE `services_enhanced` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `base_price` decimal(10,2) DEFAULT 0.00,
  `agent_fee` decimal(10,2) DEFAULT 0.00,
  `agent_fee_percentage` decimal(5,2) DEFAULT 0.00,
  `processing_fee` decimal(10,2) DEFAULT 0.00,
  `vat_rate` decimal(5,2) DEFAULT 0.00,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `status` enum('active','inactive','draft') DEFAULT 'draft',
  `is_featured` tinyint(1) DEFAULT 0,
  `requires_approval` tinyint(1) DEFAULT 1,
  `enable_agent_fee` tinyint(1) DEFAULT 1,
  `enable_processing_fee` tinyint(1) DEFAULT 0,
  `enable_vat` tinyint(1) DEFAULT 0,
  `enable_tax` tinyint(1) DEFAULT 0,
  `assigned_agent_id` int(11) DEFAULT NULL,
  `agent_name` varchar(200) DEFAULT NULL,
  `agent_email` varchar(200) DEFAULT NULL,
  `agent_phone` varchar(50) DEFAULT NULL,
  `estimated_duration` varchar(100) DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `image_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `services_enhanced`
--

INSERT INTO `services_enhanced` (`id`, `title`, `slug`, `description`, `short_description`, `category`, `base_price`, `agent_fee`, `agent_fee_percentage`, `processing_fee`, `vat_rate`, `tax_rate`, `status`, `is_featured`, `requires_approval`, `enable_agent_fee`, `enable_processing_fee`, `enable_vat`, `enable_tax`, `assigned_agent_id`, `agent_name`, `agent_email`, `agent_phone`, `estimated_duration`, `requirements`, `features`, `image_url`, `created_at`, `updated_at`) VALUES
(1, 'Legal Document Review', 'legal-document-review', 'Professional legal document review and consultation', 'Get your legal documents reviewed by certified attorneys', 'Legal Services', '150.00', '30.00', '0.00', '5.00', '0.00', '0.00', 'active', 0, 1, 1, 1, 0, 0, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-03 08:51:03', '2025-07-03 08:51:03'),
(2, 'Flight Booking Service', 'flight-booking-service', 'Complete flight booking and travel arrangement service', 'Book flights worldwide with our expert travel agents', 'Travel & Tourism', '50.00', '10.00', '0.00', '3.00', '0.00', '0.00', 'active', 0, 1, 1, 1, 0, 0, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-03 08:51:03', '2025-07-03 08:51:03'),
(3, 'Tax Consultation', 'tax-consultation', 'Professional tax consultation and filing assistance', 'Expert tax advice and filing support', 'Financial Services', '200.00', '40.00', '0.00', '8.00', '0.00', '0.00', 'active', 0, 1, 1, 1, 1, 1, 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-03 08:51:03', '2025-07-03 08:51:03'),
(4, 'dfdsfsdf', 'dfdsfsdf', 'sdfsdfsd', 'sdfsdfsd', 'Legal Services', '300.00', '3000.00', '0.00', '3000.00', '30.00', '100.00', 'active', 1, 1, 1, 1, 1, 1, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-03 09:19:31', '2025-07-03 09:19:31');

-- --------------------------------------------------------

--
-- Table structure for table `service_agents`
--

CREATE TABLE `service_agents` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `specialization` varchar(200) DEFAULT NULL,
  `commission_rate` decimal(5,2) DEFAULT 10.00,
  `status` enum('active','inactive') DEFAULT 'active',
  `avatar_url` varchar(500) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `service_agents`
--

INSERT INTO `service_agents` (`id`, `name`, `email`, `phone`, `specialization`, `commission_rate`, `status`, `avatar_url`, `bio`, `created_at`, `updated_at`) VALUES
(1, 'John Smith', 'john.smith@connectpro.com', '+1-555-0101', 'Legal Services', '15.00', 'active', NULL, NULL, '2025-07-03 08:50:39', '2025-07-03 08:50:39'),
(2, 'Sarah Johnson', 'sarah.johnson@connectpro.com', '+1-555-0102', 'Travel & Tourism', '12.00', 'active', NULL, NULL, '2025-07-03 08:50:39', '2025-07-03 08:50:39'),
(3, 'Michael Brown', 'michael.brown@connectpro.com', '+1-555-0103', 'Financial Services', '18.00', 'active', NULL, NULL, '2025-07-03 08:50:39', '2025-07-03 08:50:39'),
(4, 'Emily Davis', 'emily.davis@connectpro.com', '+1-555-0104', 'Technology', '20.00', 'active', NULL, NULL, '2025-07-03 08:50:39', '2025-07-03 08:50:39'),
(5, 'David Wilson', 'david.wilson@connectpro.com', '+1-555-0105', 'Business Services', '16.00', 'active', NULL, NULL, '2025-07-03 08:50:39', '2025-07-03 08:50:39'),
(6, 'Lisa Garcia', 'lisa.garcia@connectpro.com', '+1-555-0106', 'Real Estate', '14.00', 'active', NULL, NULL, '2025-07-03 08:50:39', '2025-07-03 08:50:39'),
(7, 'aadad', 'asadasd@gmail.com', '242342342343', 'Legal Services', '10.00', 'active', NULL, NULL, '2025-07-03 09:18:39', '2025-07-03 09:18:39');

-- --------------------------------------------------------

--
-- Table structure for table `service_bookings`
--

CREATE TABLE `service_bookings` (
  `id` int(11) NOT NULL,
  `booking_reference` varchar(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `client_name` varchar(200) NOT NULL,
  `client_email` varchar(200) NOT NULL,
  `client_phone` varchar(20) DEFAULT NULL,
  `service_details` text DEFAULT NULL,
  `urgency_level` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('pending','confirmed','waiting_approval','approved','payment_pending','paid','in_progress','completed','cancelled') DEFAULT 'pending',
  `payment_status` enum('pending','processing','confirmed','failed','cancelled') DEFAULT 'pending',
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `confirmation_sent_at` timestamp NULL DEFAULT NULL,
  `approval_deadline` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `payment_completed_at` timestamp NULL DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `quoted_price` decimal(10,2) DEFAULT NULL,
  `agent_fee` decimal(10,2) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `assigned_admin_id` int(11) DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `service_bookings`
--

INSERT INTO `service_bookings` (`id`, `booking_reference`, `user_id`, `service_id`, `client_name`, `client_email`, `client_phone`, `service_details`, `urgency_level`, `status`, `payment_status`, `booking_date`, `confirmation_sent_at`, `approval_deadline`, `approved_at`, `payment_completed_at`, `started_at`, `completed_at`, `quoted_price`, `agent_fee`, `total_amount`, `assigned_admin_id`, `admin_notes`, `created_at`, `updated_at`) VALUES
(1, 'BK-000001', 1, 1, 'Client Name 1', 'client1@example.com', '123-456-7801', 'Test booking details for booking 1', 'low', 'pending', 'pending', '2025-07-04 09:02:11', NULL, NULL, NULL, NULL, NULL, NULL, '600.00', '60.00', '660.00', 1, 'Test admin notes for booking 1', '2025-07-04 09:02:11', '2025-07-04 09:02:11'),
(2, 'BK-000002', 1, 1, 'Client Name 2', 'client2@example.com', '123-456-7802', 'Test booking details for booking 2', 'medium', 'confirmed', 'pending', '2025-07-04 09:02:11', NULL, NULL, NULL, NULL, NULL, NULL, '700.00', '70.00', '770.00', 1, 'Test admin notes for booking 2', '2025-07-04 09:02:11', '2025-07-04 09:02:11'),
(3, 'BK-000003', 1, 1, 'Client Name 3', 'client3@example.com', '123-456-7803', 'Test booking details for booking 3', 'high', 'approved', 'pending', '2025-07-04 09:02:11', NULL, NULL, NULL, NULL, NULL, NULL, '800.00', '80.00', '880.00', 1, 'Test admin notes for booking 3', '2025-07-04 09:02:11', '2025-07-04 17:16:33'),
(4, 'BK-000004', 1, 1, 'Client Name 4', 'client4@example.com', '123-456-7804', 'Test booking details for booking 4', 'low', 'in_progress', 'pending', '2025-07-04 09:02:11', NULL, NULL, NULL, NULL, NULL, NULL, '900.00', '90.00', '990.00', 1, 'Test admin notes for booking 4', '2025-07-04 09:02:11', '2025-07-04 09:02:11'),
(5, 'CP2025F8C006', 1, 3, 'Asemota Osasumwen', 'asemoindell@gmail.com', '07066558981', 'jogug', 'medium', 'completed', 'pending', '2025-07-03 09:03:11', '2025-07-03 09:03:11', '2025-07-07 10:03:11', NULL, NULL, NULL, NULL, '200.00', '40.00', '248.00', NULL, NULL, '2025-07-03 09:03:11', '2025-07-03 21:08:14'),
(6, 'CP2025B84A6D', 1, 3, 'Asemota Osasumwen', 'asemoindell@gmail.com', '07066558981', 'jogug', 'medium', 'in_progress', 'pending', '2025-07-03 09:03:23', '2025-07-03 09:03:23', '2025-07-07 10:03:23', NULL, NULL, NULL, NULL, '200.00', '40.00', '248.00', NULL, NULL, '2025-07-03 09:03:23', '2025-07-03 21:08:14'),
(7, 'CP20254BFB8F', NULL, 1, 'Test Client', 'test@example.com', '+1-555-0123', 'This is a test booking submission to verify the system works correctly.', 'medium', 'waiting_approval', 'pending', '2025-07-03 09:03:32', '2025-07-03 09:03:32', '2025-07-07 10:03:32', NULL, NULL, NULL, NULL, '150.00', '30.00', '185.00', NULL, NULL, '2025-07-03 09:03:32', '2025-07-03 09:03:32'),
(8, 'CP2025C8B4B4', 1, 3, 'Asemota Osasumwen', 'asemoindell@gmail.com', '07066558981', 'jogug', 'medium', 'approved', 'pending', '2025-07-03 09:03:40', '2025-07-03 09:03:40', '2025-07-07 10:03:40', NULL, NULL, NULL, NULL, '200.00', '40.00', '248.00', NULL, NULL, '2025-07-03 09:03:40', '2025-07-03 21:08:14'),
(9, 'CP2025392B22', NULL, 1, 'Test Client', 'test@example.com', '+1-555-0123', 'This is a test booking submission to verify the system works correctly.', 'medium', 'waiting_approval', 'pending', '2025-07-03 09:04:19', '2025-07-03 09:04:19', '2025-07-07 10:04:19', NULL, NULL, NULL, NULL, '150.00', '30.00', '185.00', NULL, NULL, '2025-07-03 09:04:19', '2025-07-03 09:04:19'),
(10, 'CP202538B289', 1, 3, 'Asemota Osasumwen', 'asemoindell@gmail.com', '07066558981', 'jogug', 'medium', 'waiting_approval', 'pending', '2025-07-03 09:07:31', '2025-07-03 09:07:31', '2025-07-07 10:07:31', NULL, NULL, NULL, NULL, '200.00', '40.00', '248.00', NULL, NULL, '2025-07-03 09:07:31', '2025-07-03 09:07:31'),
(11, 'CP20250223B6', 1, 3, 'Asemota Osasumwen', 'asemoindell@gmail.com', '07066558981', 'jogug', 'medium', 'approved', 'pending', '2025-07-03 09:07:44', '2025-07-03 09:07:44', '2025-07-07 10:07:44', NULL, NULL, NULL, NULL, '200.00', '40.00', '248.00', NULL, '', '2025-07-03 09:07:44', '2025-07-03 14:40:07'),
(12, 'CP202565EF1E', 1, 3, 'BLUERAYHOSTLTD BRH', 'bluerayhosts@gmail.com', '3025562188', 'bjbojbkbk;bnk;bn;lbn', 'medium', 'approved', 'pending', '2025-07-03 14:53:42', '2025-07-03 14:53:42', '2025-07-07 15:53:42', NULL, NULL, NULL, NULL, '200.00', '40.00', '248.00', NULL, '', '2025-07-03 14:53:42', '2025-07-03 14:57:51'),
(13, 'CP202537CBEB', 1, 2, 'Blue RayHost', 'bluerayhosts@gmail.com', '3025562188', 'ljbojhjihgo', 'medium', 'waiting_approval', 'pending', '2025-07-03 14:59:15', '2025-07-03 14:59:15', '2025-07-07 15:59:15', NULL, NULL, NULL, NULL, '50.00', '10.00', '63.00', NULL, NULL, '2025-07-03 14:59:15', '2025-07-03 14:59:15'),
(14, 'CP20258CB2C8', 1, 3, 'Asemota Osasumwen', 'asemoindell@gmail.com', '+2347066558981', 'bjbjbpknpn;k', 'medium', 'waiting_approval', 'pending', '2025-07-03 15:23:20', '2025-07-03 15:23:20', '2025-07-07 16:23:20', NULL, NULL, NULL, NULL, '200.00', '40.00', '248.00', NULL, NULL, '2025-07-03 15:23:20', '2025-07-03 15:23:20'),
(15, 'CP2025474BF5', 1, 3, 'Asemota Osasumwen', 'asemoindell@gmail.com', '+2347066558981', 'bjbjbpknpn;k', 'medium', 'waiting_approval', 'pending', '2025-07-03 15:47:32', '2025-07-03 15:47:32', '2025-07-07 16:47:32', NULL, NULL, NULL, NULL, '200.00', '40.00', '248.00', NULL, NULL, '2025-07-03 15:47:32', '2025-07-03 15:47:32'),
(16, 'FINAL20250703184709', 1, 1, 'Test User', 'test@example.com', NULL, 'Test booking with agent selection', 'medium', 'waiting_approval', 'pending', '2025-07-03 16:47:09', NULL, '2025-07-07 17:47:09', NULL, NULL, NULL, NULL, '150.00', NULL, '185.00', 2, NULL, '2025-07-03 16:47:09', '2025-07-03 16:47:09'),
(17, 'CP2025836774', 1, 3, 'John User', 'user@connectpro.com', NULL, 'sngzdfngsdgdf', 'medium', 'waiting_approval', 'pending', '2025-07-03 19:38:16', NULL, '2025-07-07 20:38:16', NULL, NULL, NULL, NULL, '200.00', NULL, '248.00', 2, NULL, '2025-07-03 19:38:16', '2025-07-03 19:38:16'),
(18, 'CP2025739D26', 1, 2, 'John User', 'user@connectpro.com', NULL, 'sgasasdgas', 'medium', 'waiting_approval', 'pending', '2025-07-03 19:38:47', NULL, '2025-07-07 20:38:47', NULL, NULL, NULL, NULL, '50.00', NULL, '63.00', 2, NULL, '2025-07-03 19:38:47', '2025-07-03 19:38:47'),
(19, 'CP2025C16E6A', 1, 4, 'John User', 'user@connectpro.com', NULL, 'sgsgsfdgdf', 'medium', 'waiting_approval', 'pending', '2025-07-04 10:58:04', NULL, '2025-07-08 11:58:04', NULL, NULL, NULL, NULL, '300.00', NULL, '14490.00', 1, NULL, '2025-07-04 10:58:04', '2025-07-04 10:58:04'),
(20, 'CP2025856960', 1, 6, 'Asemota Osasumwen', 'asemoindell@gmail.com', '2347066558981', 'sgasgsfgczccz', 'medium', 'waiting_approval', 'pending', '2025-07-04 11:07:52', '2025-07-04 11:07:52', '2025-07-08 12:07:52', NULL, NULL, NULL, NULL, NULL, '0.00', '0.00', NULL, NULL, '2025-07-04 11:07:52', '2025-07-04 11:07:52'),
(21, 'CP202561D04C', 1, 4, 'John User', 'user@connectpro.com', NULL, 'sfasdfsfdsdsdf', 'medium', 'waiting_approval', 'pending', '2025-07-04 11:09:58', NULL, '2025-07-08 12:09:58', NULL, NULL, NULL, NULL, '300.00', NULL, '14490.00', 2, NULL, '2025-07-04 11:09:58', '2025-07-04 11:09:58'),
(22, 'CP20253F14D8', 1, 1, 'John User', 'user@connectpro.com', NULL, 'sdadffsdfdffdsfdasfdsfsda', 'medium', 'waiting_approval', 'pending', '2025-07-04 11:10:27', NULL, '2025-07-08 12:10:27', NULL, NULL, NULL, NULL, '150.00', NULL, '185.00', 1, NULL, '2025-07-04 11:10:27', '2025-07-04 11:10:27'),
(23, 'CP2025EEBE73', 1, 2, 'John User', 'user@connectpro.com', NULL, 'sadfsdfsadfasdfsadfsaafsadfsad', 'urgent', 'waiting_approval', 'pending', '2025-07-04 11:10:54', NULL, '2025-07-08 12:10:54', NULL, NULL, NULL, NULL, '50.00', NULL, '63.00', 2, NULL, '2025-07-04 11:10:54', '2025-07-04 11:10:54'),
(24, 'CP2025974650', 1, 1, 'John User', 'user@connectpro.com', NULL, 'Service booked via agent selection', 'medium', 'pending', 'pending', '2025-07-04 11:52:25', NULL, '2025-07-08 12:52:25', NULL, NULL, NULL, NULL, '100.00', NULL, '100.00', 2, NULL, '2025-07-04 11:52:25', '2025-07-04 11:52:25'),
(25, 'CP202502047F', 1, 1, 'John User', 'user@connectpro.com', NULL, 'Service booked via agent selection', 'medium', 'cancelled', 'cancelled', '2025-07-04 12:11:28', NULL, '2025-07-08 13:11:28', NULL, NULL, NULL, NULL, '150.00', NULL, '185.00', 1, NULL, '2025-07-04 12:11:28', '2025-07-04 16:32:47'),
(27, 'CP2025397C89', 1, 2, 'John User', 'user@connectpro.com', NULL, 'Service booked via agent selection', 'medium', 'pending', 'pending', '2025-07-04 12:58:27', NULL, '2025-07-08 13:58:27', NULL, NULL, NULL, NULL, '50.00', NULL, '63.00', 2, NULL, '2025-07-04 12:58:27', '2025-07-04 12:58:27'),
(28, 'CP2025DCAA02', 1, 3, 'John User', 'user@connectpro.com', NULL, 'Service booked via agent selection', 'medium', 'pending', 'pending', '2025-07-04 13:23:41', NULL, '2025-07-08 14:23:41', NULL, NULL, NULL, NULL, '200.00', NULL, '248.00', 2, NULL, '2025-07-04 13:23:41', '2025-07-04 13:23:41'),
(29, 'CP202553A092', 1, 4, 'John User', 'user@connectpro.com', NULL, 'Service booked via agent selection', 'medium', 'pending', 'pending', '2025-07-04 16:23:49', NULL, '2025-07-08 17:23:49', NULL, NULL, NULL, NULL, '300.00', NULL, '14490.00', 2, NULL, '2025-07-04 16:23:49', '2025-07-04 16:23:49'),
(30, 'CP2025AD2C90', 1, 3, 'John User', 'user@connectpro.com', NULL, 'Service booked via agent selection', 'medium', 'payment_pending', 'processing', '2025-07-04 16:37:30', NULL, '2025-07-08 17:37:30', NULL, NULL, NULL, NULL, '200.00', NULL, '248.00', 7, NULL, '2025-07-04 16:37:30', '2025-07-04 16:37:41'),
(31, 'CP20251044D6', 1, 4, 'John User', 'user@connectpro.com', NULL, 'Service booked via agent selection', 'medium', 'cancelled', 'cancelled', '2025-07-04 16:38:09', NULL, '2025-07-08 17:38:09', NULL, NULL, NULL, NULL, '300.00', NULL, '14490.00', 2, NULL, '2025-07-04 16:38:09', '2025-07-04 16:38:12');

-- --------------------------------------------------------

--
-- Table structure for table `service_categories`
--

CREATE TABLE `service_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `service_categories`
--

INSERT INTO `service_categories` (`id`, `name`, `description`, `icon`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Travel', 'Flight bookings, hotel reservations, travel planning', 'plane', 'active', '2025-07-03 07:37:56', '2025-07-03 07:37:56'),
(2, 'Legal', 'Legal consultation, document preparation, court representation', 'balance-scale', 'active', '2025-07-03 07:37:56', '2025-07-03 07:37:56'),
(3, 'Finance', 'Tax preparation, financial planning, accounting services', 'calculator', 'active', '2025-07-03 07:37:56', '2025-07-03 07:37:56'),
(4, 'Technical', 'Software development, IT consulting, technical support', 'cogs', 'active', '2025-07-03 07:37:56', '2025-07-03 07:37:56'),
(5, 'Business', 'Business consulting, marketing, administrative services', 'briefcase', 'active', '2025-07-03 07:37:56', '2025-07-03 07:37:56'),
(6, 'Travel', 'Flight bookings, hotel reservations, travel planning', 'plane', 'active', '2025-07-03 07:40:07', '2025-07-03 07:40:07'),
(7, 'Legal', 'Legal consultation, document preparation, court representation', 'balance-scale', 'active', '2025-07-03 07:40:07', '2025-07-03 07:40:07'),
(8, 'Finance', 'Tax preparation, financial planning, accounting services', 'calculator', 'active', '2025-07-03 07:40:07', '2025-07-03 07:40:07'),
(9, 'Technical', 'Software development, IT consulting, technical support', 'cogs', 'active', '2025-07-03 07:40:07', '2025-07-03 07:40:07'),
(10, 'Business', 'Business consulting, marketing, administrative services', 'briefcase', 'active', '2025-07-03 07:40:07', '2025-07-03 07:40:07');

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('text','number','boolean','json') DEFAULT 'text',
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','inactive','pending') DEFAULT 'active',
  `newsletter_subscribed` tinyint(1) DEFAULT 0,
  `email_verified` tinyint(1) DEFAULT 0,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `last_activity` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `phone`, `password`, `status`, `newsletter_subscribed`, `email_verified`, `last_login`, `created_at`, `updated_at`, `approved_by`, `approved_at`, `admin_notes`, `last_activity`) VALUES
(1, 'John', 'User', 'user@connectpro.com', NULL, '$2y$10$DVkaBaa7y/Qd7bqluAGnX.eDg9wS0e9SalGQf4L73wnzztXEaqy1K', 'active', 0, 0, '2025-07-04 11:52:06', '2025-07-03 01:19:34', '2025-07-04 16:39:34', NULL, NULL, NULL, '2025-07-04 16:39:34'),
(2, 'Jane', 'Smith', 'jane@example.com', '555-1234', '$2y$10$4Ys3W.UnTd2g/yKYiGhP0.0TfaT/bwS9M2eNu4IVennqpsrXED4f6', 'active', 1, 0, NULL, '2025-07-03 01:20:46', '2025-07-03 01:20:46', NULL, NULL, NULL, NULL),
(3, 'Test', 'User', 'test1751527653@example.com', '1234567890', '$2y$10$RJ9rLCK8cJsZT4mtOpdR6eDT8qFkz3z8kReW7mskCvlkjI0yQQgWO', 'active', 0, 0, NULL, '2025-07-03 07:27:33', '2025-07-03 07:27:33', NULL, NULL, NULL, NULL),
(4, 'Test', 'User', 'test1751528094@example.com', '1234567890', '$2y$10$eC/ifWY0ZhFxIeFnUsMRVuxpumSsEkZLTISfglqQMhlaT5qyck2YG', 'active', 0, 0, NULL, '2025-07-03 07:34:54', '2025-07-03 07:34:54', NULL, NULL, NULL, NULL),
(5, 'Test', 'User', 'test1751528288@example.com', '1234567890', '$2y$10$EhjnID7SErchNAnAr1CzJubP0EbBEVq7oEUWK3J3HrYomNWIftPjK', 'active', 0, 0, NULL, '2025-07-03 07:38:08', '2025-07-03 07:38:08', NULL, NULL, NULL, NULL),
(6, 'Test', 'User', 'test1751528364@example.com', '1234567890', '$2y$10$8caHyxo3aDFB6t1vnNLOguJNZiqeA8GeXaYfBU91ElNJWdZYzVn1a', 'active', 0, 0, NULL, '2025-07-03 07:39:24', '2025-07-03 07:39:24', NULL, NULL, NULL, NULL),
(7, 'Test', 'User', 'test1751528450@example.com', '1234567890', '$2y$10$DJfEyf7Bzc9/nbq4PqpJZOafOeskIRWDCMJEP4iAZMaMGfeOwMo8m', 'active', 0, 0, NULL, '2025-07-03 07:40:50', '2025-07-03 07:40:50', NULL, NULL, NULL, NULL),
(8, 'Test', 'User', 'test1751528587@example.com', '1234567890', '$2y$10$ZvkY3fmBOozo7jz81Hym0eqCw8pnx32JvJhAOezFGLWqp5SfJMTPe', 'active', 0, 0, NULL, '2025-07-03 07:43:07', '2025-07-03 07:43:07', NULL, NULL, NULL, NULL),
(9, 'Test', 'User', 'test1751529069@example.com', '1234567890', '$2y$10$ARmTjiDtHOcNWz8/VK0viOB2mvgS0HCE.1Ik8dCTZ5A7ypgFh7KJG', 'active', 0, 0, NULL, '2025-07-03 07:51:09', '2025-07-03 07:51:09', NULL, NULL, NULL, NULL),
(10, 'Test', 'User', 'test1751529204@example.com', '1234567890', '$2y$10$TyGyP1m05.cPVMwJYF7z7eaK/p1LXDDNpZnOSh0wJdKSVD5Hbebqe', 'active', 0, 0, NULL, '2025-07-03 07:53:24', '2025-07-03 07:53:24', NULL, NULL, NULL, NULL),
(11, 'Test', 'User', 'test@example.com', NULL, '$2y$10$itFC0uci1WJOl/2Evi/bHeHf44pUjHcDegM8k3tki5/df6Za/qn.6', 'active', 0, 1, NULL, '2025-07-03 08:16:50', '2025-07-03 08:16:50', NULL, NULL, NULL, NULL),
(12, 'John', 'Doe', 'john.doe@example.com', '+1-555-0101', '$2y$10$ekUcoMmhSpWVr8pZ4yExkuEAmFpD.XUK4JuRXPAsuw24x.7GBOUAq', 'active', 0, 1, NULL, '2025-07-03 11:21:29', '2025-07-03 11:21:29', NULL, NULL, NULL, NULL),
(13, 'Jane', 'Smith', 'jane.smith@example.com', '+1-555-0102', '$2y$10$5nerMqAeF3q3M028SeUYqOFHd7FzX7qd1M7edqpYyhP2jSEuVsUHK', 'pending', 0, 1, NULL, '2025-07-03 11:21:29', '2025-07-03 11:21:29', NULL, NULL, NULL, NULL),
(14, 'Mike', 'Johnson', 'mike.johnson@example.com', '+1-555-0103', '$2y$10$ZAtYBnNOR52ArLx1jDKEZeQ7.rs7PCP4DTMNwStCxuoIQS.mW.P4G', 'active', 0, 1, NULL, '2025-07-03 11:21:29', '2025-07-03 11:21:29', NULL, NULL, NULL, NULL),
(15, 'Sarah', 'Williams', 'sarah.williams@example.com', '+1-555-0104', '$2y$10$Em5qV6eMjBW9Dwvs2jUuPeJaRaziWgrPHIs6Mkjwe2bGfKupG6dz.', 'pending', 0, 1, NULL, '2025-07-03 11:21:29', '2025-07-03 11:21:29', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_activities`
--

CREATE TABLE `user_activities` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_activities`
--

INSERT INTO `user_activities` (`id`, `user_id`, `activity_type`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'registration', 'User registered an account', '127.0.0.1', NULL, '2025-06-25 11:29:37'),
(2, 2, 'login', 'User logged into the system', '127.0.0.1', NULL, '2025-06-08 11:29:37'),
(3, 1, 'profile_update', 'User updated their profile information', '127.0.0.1', NULL, '2025-06-22 11:29:37'),
(4, 3, 'registration', 'User registered an account', '192.168.1.100', NULL, '2025-06-22 11:29:37'),
(5, 2, 'booking', 'User made a service booking', '127.0.0.1', NULL, '2025-06-10 11:29:37'),
(6, 4, 'registration', 'User registered an account', '10.0.0.1', NULL, '2025-06-10 11:29:37'),
(7, 1, 'login', 'User logged into the system', '127.0.0.1', NULL, '2025-06-17 11:29:37'),
(8, 3, 'login', 'User logged into the system', '192.168.1.100', NULL, '2025-06-23 11:29:37');

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_log`
--

CREATE TABLE `user_activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `user_activity_log`
--

INSERT INTO `user_activity_log` (`id`, `user_id`, `action`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'logout', NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-07-04 08:00:06'),
(2, 1, 'logout', NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-07-04 10:59:56'),
(3, 1, 'logout', NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-07-04 16:39:34');

-- --------------------------------------------------------

--
-- Table structure for table `user_locations`
--

CREATE TABLE `user_locations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `country` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `timezone` varchar(50) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `user_login_logs`
--

CREATE TABLE `user_login_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `logout_time` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `is_successful` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `user_service_preferences`
--

CREATE TABLE `user_service_preferences` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `interest_level` enum('low','medium','high') DEFAULT 'medium',
  `preferred_agent_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure for view `payment_method_stats`
--
DROP TABLE IF EXISTS `payment_method_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `payment_method_stats`  AS SELECT `payments`.`payment_method` AS `payment_method`, count(0) AS `transaction_count`, sum(`payments`.`amount`) AS `total_amount`, avg(`payments`.`amount`) AS `average_amount`, sum(case when `payments`.`payment_status` = 'completed' then `payments`.`amount` else 0 end) AS `completed_amount`, count(case when `payments`.`payment_status` = 'completed' then 1 end) AS `completed_count`, count(case when `payments`.`payment_status` = 'pending' then 1 end) AS `pending_count`, count(case when `payments`.`payment_status` = 'failed' then 1 end) AS `failed_count` FROM `payments` GROUP BY `payments`.`payment_method` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_activity` (`admin_id`,`created_at`),
  ADD KEY `idx_action` (`action`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD UNIQUE KEY `booking_reference` (`booking_reference`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`message_id`);

--
-- Indexes for table `chat_permissions`
--
ALTER TABLE `chat_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_booking_chat` (`booking_id`),
  ADD KEY `idx_chat_permissions_booking` (`booking_id`),
  ADD KEY `idx_chat_permissions_user` (`user_id`),
  ADD KEY `idx_chat_permissions_agent` (`agent_id`);

--
-- Indexes for table `chat_rooms`
--
ALTER TABLE `chat_rooms`
  ADD PRIMARY KEY (`room_id`);

--
-- Indexes for table `contact_inquiries`
--
ALTER TABLE `contact_inquiries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `content_pages`
--
ALTER TABLE `content_pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_content` (`page_name`,`section_name`,`content_key`);

--
-- Indexes for table `crypto_payments`
--
ALTER TABLE `crypto_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `verified_by` (`verified_by`),
  ADD KEY `idx_crypto_payments_booking` (`booking_id`),
  ADD KEY `idx_crypto_payments_status` (`status`);

--
-- Indexes for table `crypto_wallets`
--
ALTER TABLE `crypto_wallets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `address` (`address`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_recipient` (`recipient_email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_template` (`template_type`),
  ADD KEY `idx_sent_date` (`sent_at`);

--
-- Indexes for table `email_notifications`
--
ALTER TABLE `email_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `email_templates`
--
ALTER TABLE `email_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `template_type` (`template_type`),
  ADD KEY `idx_template_type` (`template_type`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_admin_id` (`assigned_admin_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user` (`user_type`,`user_id`),
  ADD KEY `idx_notifications_read` (`is_read`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD UNIQUE KEY `payment_reference` (`payment_reference`),
  ADD KEY `idx_payments_status` (`payment_status`),
  ADD KEY `idx_payments_method` (`payment_method`),
  ADD KEY `idx_payments_created` (`created_at`),
  ADD KEY `idx_payments_booking` (`booking_id`);

--
-- Indexes for table `payment_fees`
--
ALTER TABLE `payment_fees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `services_enhanced`
--
ALTER TABLE `services_enhanced`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `service_agents`
--
ALTER TABLE `service_agents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `service_bookings`
--
ALTER TABLE `service_bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_reference` (`booking_reference`),
  ADD KEY `idx_service_bookings_payment_status` (`payment_status`);

--
-- Indexes for table `service_categories`
--
ALTER TABLE `service_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_activities`
--
ALTER TABLE `user_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `activity_type` (`activity_type`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_activity` (`user_id`,`created_at`),
  ADD KEY `idx_action` (`action`);

--
-- Indexes for table `user_locations`
--
ALTER TABLE `user_locations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_location` (`user_id`),
  ADD KEY `idx_country` (`country`),
  ADD KEY `idx_ip` (`ip_address`);

--
-- Indexes for table `user_login_logs`
--
ALTER TABLE `user_login_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_login` (`user_id`,`login_time`),
  ADD KEY `idx_ip_address` (`ip_address`);

--
-- Indexes for table `user_service_preferences`
--
ALTER TABLE `user_service_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_service` (`user_id`,`service_id`),
  ADD KEY `idx_user_preferences` (`user_id`),
  ADD KEY `idx_service_preferences` (`service_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `chat_permissions`
--
ALTER TABLE `chat_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_rooms`
--
ALTER TABLE `chat_rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `contact_inquiries`
--
ALTER TABLE `contact_inquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content_pages`
--
ALTER TABLE `content_pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `crypto_payments`
--
ALTER TABLE `crypto_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `crypto_wallets`
--
ALTER TABLE `crypto_wallets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_notifications`
--
ALTER TABLE `email_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `email_templates`
--
ALTER TABLE `email_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payment_fees`
--
ALTER TABLE `payment_fees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `services_enhanced`
--
ALTER TABLE `services_enhanced`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `service_agents`
--
ALTER TABLE `service_agents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `service_bookings`
--
ALTER TABLE `service_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `service_categories`
--
ALTER TABLE `service_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `user_activities`
--
ALTER TABLE `user_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_locations`
--
ALTER TABLE `user_locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_login_logs`
--
ALTER TABLE `user_login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_service_preferences`
--
ALTER TABLE `user_service_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chat_permissions`
--
ALTER TABLE `chat_permissions`
  ADD CONSTRAINT `chat_permissions_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `service_bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_permissions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_permissions_ibfk_3` FOREIGN KEY (`agent_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `crypto_payments`
--
ALTER TABLE `crypto_payments`
  ADD CONSTRAINT `crypto_payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `service_bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `crypto_payments_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD CONSTRAINT `inquiries_ibfk_1` FOREIGN KEY (`assigned_admin_id`) REFERENCES `admins` (`admin_id`);

--
-- Constraints for table `user_activities`
--
ALTER TABLE `user_activities`
  ADD CONSTRAINT `user_activities_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
