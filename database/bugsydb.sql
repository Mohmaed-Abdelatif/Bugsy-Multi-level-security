-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 07, 2026 at 08:22 AM
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
-- Database: `bugsydb`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `resource_type` varchar(50) DEFAULT NULL,
  `resource_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `request_method` varchar(10) DEFAULT NULL,
  `request_url` text DEFAULT NULL,
  `status_code` int(11) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `resource_type`, `resource_id`, `ip_address`, `user_agent`, `request_method`, `request_url`, `status_code`, `details`, `created_at`) VALUES
(1, NULL, 'login_success_v2', 'login', 11, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":11}', '2026-06-22 12:20:53'),
(2, NULL, 'login_success_v2', 'login', 3, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":3}', '2026-06-22 12:23:38'),
(3, 3, 'product_created_v2', 'product', 9, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/products', 200, '{\"product_id\":\"9\",\"by\":3}', '2026-06-22 12:24:37'),
(4, 3, 'product_created_v2', 'product', 12, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/products', 200, '{\"product_id\":\"12\",\"by\":3}', '2026-06-22 12:28:54'),
(5, 3, 'product_created_v2', 'product', 14, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/products', 200, '{\"product_id\":\"14\",\"by\":3}', '2026-06-22 12:29:28'),
(6, 3, 'product_created_v2', 'product', 15, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/products', 200, '{\"product_id\":\"15\",\"by\":3}', '2026-06-22 12:33:30'),
(7, 11, 'review_created_v2', 'review', 1, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/products/1/reviews', 200, '{\"review_id\":4,\"product_id\":1,\"user_id\":11,\"rating\":5}', '2026-06-22 12:35:34'),
(8, 11, 'review_created_v2', 'review', 2, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/products/2/reviews', 200, '{\"review_id\":5,\"product_id\":2,\"user_id\":11,\"rating\":5}', '2026-06-22 12:37:04'),
(9, NULL, 'login_success_v2', 'login', 12, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":12}', '2026-06-22 12:39:10'),
(10, 11, 'review_updated_v2', 'review', 5, '::1', 'PostmanRuntime/7.54.0', 'PUT', '/Bugsy/api/v2/reviews/5', 200, '{\"review_id\":5,\"user_id\":11,\"changes\":[\"rating\"]}', '2026-06-22 12:40:00'),
(11, NULL, 'login_success_v2', 'login', 13, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":13}', '2026-06-22 12:40:40'),
(12, NULL, 'login_success_v2', 'login', 3, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":3}', '2026-06-22 12:46:09'),
(13, 3, 'admin_added_v2', 'admin', NULL, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/admin/add', 200, '{\"new_admin_id\":14,\"by\":3}', '2026-06-22 12:48:51'),
(14, NULL, 'login_success_v2', 'login', 14, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":14}', '2026-06-22 12:49:38'),
(15, 14, 'product_created_v2', 'product', 16, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/products', 200, '{\"product_id\":\"16\",\"by\":14}', '2026-06-22 12:50:35'),
(16, 14, 'product_created_v2', 'product', 17, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/products', 200, '{\"product_id\":\"17\",\"by\":14}', '2026-06-22 12:51:27'),
(17, NULL, 'login_success_v2', 'login', 1, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":1}', '2026-06-22 13:13:14'),
(18, NULL, 'login_success_v2', 'login', 1, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":1}', '2026-06-23 09:33:18'),
(19, NULL, 'login_success_v2', 'login', 14, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":14}', '2026-06-23 09:33:48'),
(20, 14, 'brand_created_v2', 'brand', 6, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/brands', 200, '{\"brand_id\":\"6\",\"by\":14}', '2026-06-23 09:34:59'),
(21, 14, 'brand_created_v2', 'brand', 7, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/brands', 200, '{\"brand_id\":\"7\",\"by\":14}', '2026-06-23 09:36:42'),
(22, NULL, 'login_success_v2', 'login', 14, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":14}', '2026-06-23 09:41:12'),
(23, NULL, 'login_success_v2', 'login', 11, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":11}', '2026-06-23 09:41:45'),
(24, NULL, 'login_success_v2', 'login', 12, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":12}', '2026-06-23 09:42:05'),
(25, NULL, 'logout_v2', 'logout', NULL, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/logout', 200, '{\"user_id\":null}', '2026-06-23 09:43:34'),
(26, NULL, 'login_success_v2', 'login', 12, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":12}', '2026-06-23 09:43:53'),
(27, NULL, 'login_success_v2', 'login', 12, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":12}', '2026-06-23 11:46:22'),
(28, NULL, 'login_success_v2', 'login', 12, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":12}', '2026-06-23 11:46:27'),
(29, NULL, 'login_success_v2', 'login', 12, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":12}', '2026-06-23 11:46:38'),
(30, NULL, 'login_success_v2', 'login', 12, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":12}', '2026-06-23 11:46:44'),
(31, NULL, 'login_success_v2', 'login', 12, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":12}', '2026-06-23 11:46:46'),
(32, NULL, 'login_success_v2', 'login', 11, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":11}', '2026-06-23 11:48:29'),
(33, NULL, 'login_success_v2', 'login', 14, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":14}', '2026-06-23 11:49:45'),
(34, 11, 'cart_item_added_v2', 'cart', 4, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/add', 200, '{\"user_id\":11,\"product_id\":4,\"quantity\":2}', '2026-06-23 11:52:36'),
(35, 11, 'cart_item_added_v2', 'cart', 4, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/add', 200, '{\"user_id\":11,\"product_id\":4,\"quantity\":1}', '2026-06-23 11:53:14'),
(36, 11, 'cart_item_added_v2', 'cart', 4, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/add', 200, '{\"user_id\":11,\"product_id\":4,\"quantity\":1}', '2026-06-23 11:53:25'),
(37, 11, 'cart_item_added_v2', 'cart', 4, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/add', 200, '{\"user_id\":11,\"product_id\":4,\"quantity\":3}', '2026-06-23 11:53:38'),
(38, 11, 'cart_item_updated_v2', 'cart', 11, '::1', 'PostmanRuntime/7.54.0', 'PUT', '/Bugsy/api/v2/cart/items/11', 200, '{\"user_id\":11,\"item_id\":11}', '2026-06-23 11:56:20'),
(39, 11, 'cart_item_updated_v2', 'cart', 11, '::1', 'PostmanRuntime/7.54.0', 'PUT', '/Bugsy/api/v2/cart/items/12', 200, '{\"user_id\":11,\"item_id\":12}', '2026-06-23 11:56:42'),
(40, 11, 'cart_item_updated_v2', 'cart', 11, '::1', 'PostmanRuntime/7.54.0', 'PUT', '/Bugsy/api/v2/cart/items/12', 200, '{\"user_id\":11,\"item_id\":12}', '2026-06-23 11:57:09'),
(41, 11, 'cart_item_updated_v2', 'cart', 11, '::1', 'PostmanRuntime/7.54.0', 'PUT', '/Bugsy/api/v2/cart/items/11', 200, '{\"user_id\":11,\"item_id\":11}', '2026-06-23 11:58:18'),
(42, 11, 'cart_item_updated_v2', 'cart', 11, '::1', 'PostmanRuntime/7.54.0', 'PUT', '/Bugsy/api/v2/cart/items/12', 200, '{\"user_id\":11,\"item_id\":12}', '2026-06-23 11:58:38'),
(43, 11, 'cart_item_updated_v2', 'cart', 11, '::1', 'PostmanRuntime/7.54.0', 'PUT', '/Bugsy/api/v2/cart/items/11', 200, '{\"user_id\":11,\"item_id\":11}', '2026-06-23 11:58:42'),
(44, 11, 'cart_cleared_v2', 'cart', 11, '::1', 'PostmanRuntime/7.54.0', 'DELETE', '/Bugsy/api/v2/cart/clear', 200, '{\"user_id\":11}', '2026-06-23 11:59:54'),
(45, 11, 'cart_item_added_v2', 'cart', 5, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/add', 200, '{\"user_id\":11,\"product_id\":5,\"quantity\":1}', '2026-06-23 12:02:17'),
(46, 11, 'order_created_v2', 'order', 9, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/checkout', 200, '{\"user_id\":11,\"order_id\":9,\"total\":\"60000.00\"}', '2026-06-23 12:02:58'),
(47, 11, 'order_cancelled_v2', 'order', 9, '::1', 'PostmanRuntime/7.54.0', 'PUT', '/Bugsy/api/v2/orders/9/cancel', 200, '{\"user_id\":11,\"order_id\":9}', '2026-06-23 12:06:59'),
(48, 14, 'order_status_updated_v2', 'order', 1, '::1', 'PostmanRuntime/7.54.0', 'PUT', '/Bugsy/api/v2/orders/1/status', 200, '{\"admin_id\":14,\"order_id\":1,\"status\":\"shipped\"}', '2026-06-23 12:09:48'),
(49, 14, 'product_created_v2', 'product', 18, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/products', 200, '{\"product_id\":\"18\",\"by\":14}', '2026-06-23 12:14:37'),
(50, 14, 'product_deleted_v2', 'product', 18, '::1', 'PostmanRuntime/7.54.0', 'DELETE', '/Bugsy/api/v2/products/18', 200, '{\"product_id\":18,\"product_name\":\"Honor\",\"images_deleted\":0,\"images_failed\":0,\"by\":14}', '2026-06-23 12:16:17'),
(51, 14, 'product_created_v2', 'product', 19, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/products', 200, '{\"product_id\":\"19\",\"by\":14}', '2026-06-23 12:18:03'),
(52, 14, 'product_created_v2', 'product', 20, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/products', 200, '{\"product_id\":\"20\",\"by\":14}', '2026-06-23 12:18:41'),
(53, 14, 'product_updated_v2', 'product', 20, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/products/20', 200, '{\"product_id\":20,\"by\":14,\"fields\":[\"name\",\"price\",\"brand_id\",\"category_id\",\"stock\"]}', '2026-06-23 12:21:22'),
(54, 14, 'product_updated_v2', 'product', 20, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/products/20', 200, '{\"product_id\":20,\"by\":14,\"fields\":[\"name\",\"price\",\"brand_id\",\"category_id\",\"stock\",\"main_image\"]}', '2026-06-23 12:22:15'),
(55, NULL, 'login_success_v2', 'login', 14, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":14}', '2026-06-23 12:50:02'),
(56, NULL, 'login_success_v2', 'login', 14, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":14}', '2026-06-23 13:06:34'),
(57, 14, 'product_deleted_v2', 'product', 20, '::1', 'PostmanRuntime/7.54.0', 'DELETE', '/Bugsy/api/v2/products/20', 200, '{\"product_id\":20,\"product_name\":\"x\",\"images_deleted\":6,\"images_failed\":0,\"by\":14}', '2026-06-23 13:14:54'),
(58, 14, 'product_deleted_v2', 'product', 2, '::1', 'PostmanRuntime/7.54.0', 'DELETE', '/Bugsy/api/v2/products/2', 200, '{\"product_id\":2,\"product_name\":\"iPhone 15 Pro Max\",\"images_deleted\":6,\"images_failed\":0,\"by\":14}', '2026-06-23 13:15:20'),
(59, 14, 'product_deleted_v2', 'product', 4, '::1', 'PostmanRuntime/7.54.0', 'DELETE', '/Bugsy/api/v2/products/4', 200, '{\"product_id\":4,\"product_name\":\"Samsung Galaxy S24 Ultra\",\"images_deleted\":7,\"images_failed\":0,\"by\":14}', '2026-06-23 13:15:23'),
(60, 14, 'product_deleted_v2', 'product', 5, '::1', 'PostmanRuntime/7.54.0', 'DELETE', '/Bugsy/api/v2/products/5', 200, '{\"product_id\":5,\"product_name\":\"Xiaomi 17 Pro Max \",\"images_deleted\":7,\"images_failed\":0,\"by\":14}', '2026-06-23 13:15:25'),
(61, 14, 'product_image_deleted_v2', 'product', 1, '::1', 'PostmanRuntime/7.54.0', 'DELETE', '/Bugsy/api/v2/products/images/12', 200, '{\"image_id\":12,\"product_id\":1,\"filename\":\"product_6a3a845d971121.01054543.png\"}', '2026-06-23 13:16:37'),
(62, 14, 'product_image_deleted_v2', 'product', 1, '::1', 'PostmanRuntime/7.54.0', 'DELETE', '/Bugsy/api/v2/products/images/13', 200, '{\"image_id\":13,\"product_id\":1,\"filename\":\"product_6a3a845d974d18.18272524.png\"}', '2026-06-23 13:16:42'),
(63, 14, 'product_images_replaced_v2', 'product', 6, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/products/6/images/replace', 200, '{\"product_id\":6,\"old_count\":1,\"new_count\":1,\"by\":14}', '2026-06-23 13:22:27'),
(64, 14, 'product_image_deleted_v2', 'product', 6, '::1', 'PostmanRuntime/7.54.0', 'DELETE', '/Bugsy/api/v2/products/images/34', 200, '{\"image_id\":34,\"product_id\":6,\"filename\":\"product_39a81ef8425e53db.png\"}', '2026-06-23 13:23:11'),
(65, 14, 'product_deleted_v2', 'product', 19, '::1', 'PostmanRuntime/7.54.0', 'DELETE', '/Bugsy/api/v2/products/19', 200, '{\"product_id\":19,\"product_name\":\"x\",\"images_deleted\":1,\"images_failed\":0,\"by\":14}', '2026-06-23 13:24:01'),
(66, 14, 'product_deleted_v2', 'product', 16, '::1', 'PostmanRuntime/7.54.0', 'DELETE', '/Bugsy/api/v2/products/16', 200, '{\"product_id\":16,\"product_name\":\"x\",\"images_deleted\":0,\"images_failed\":0,\"by\":14}', '2026-06-23 13:24:07'),
(67, NULL, 'login_success_v2', 'login', 11, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":11}', '2026-06-23 13:25:31'),
(68, 14, 'category_created_v2', 'category', 6, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/categories', 200, '{\"category_id\":\"6\",\"by\":14}', '2026-06-23 13:26:50'),
(69, 14, 'category_updated_v2', 'category', 6, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/categories/6', 200, '{\"category_id\":6,\"by\":14}', '2026-06-23 13:27:59'),
(70, 14, 'category_updated_v2', 'category', 6, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/categories/6', 200, '{\"category_id\":6,\"by\":14}', '2026-06-23 13:29:19'),
(71, 14, 'category_deleted_v2', 'category', 6, '::1', 'PostmanRuntime/7.54.0', 'DELETE', '/Bugsy/api/v2/categories/6', 200, '{\"category_id\":6,\"name\":\"xx\",\"by\":14}', '2026-06-23 13:29:50'),
(72, 14, 'brand_created_v2', 'brand', 8, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/brands', 200, '{\"brand_id\":\"8\",\"by\":14}', '2026-06-23 13:31:26'),
(73, 14, 'brand_deleted_v2', 'brand', 8, '::1', 'PostmanRuntime/7.54.0', 'DELETE', '/Bugsy/api/v2/brands/8', 200, '{\"brand_id\":8,\"name\":\"sony\",\"by\":14}', '2026-06-23 13:32:21'),
(74, NULL, 'login_success_v2', 'login', 11, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":11}', '2026-06-23 13:35:10'),
(75, NULL, 'login_success_v2', 'login', 12, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":12}', '2026-06-23 13:38:12'),
(76, 12, 'review_created_v2', 'review', 1, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/products/1/reviews', 200, '{\"review_id\":6,\"product_id\":1,\"user_id\":12,\"rating\":1}', '2026-06-23 13:38:35'),
(77, 12, 'review_updated_v2', 'review', 6, '::1', 'PostmanRuntime/7.54.0', 'PUT', '/Bugsy/api/v2/reviews/6', 200, '{\"review_id\":6,\"user_id\":12,\"changes\":[\"rating\",\"comment\"]}', '2026-06-23 13:43:32'),
(78, NULL, 'login_success_v2', 'login', 11, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":11}', '2026-06-23 13:45:32'),
(79, 12, 'review_deleted_v2', 'review', 1, '::1', 'PostmanRuntime/7.54.0', 'DELETE', '/Bugsy/api/v2/reviews/6', 200, '{\"review_id\":6,\"product_id\":1,\"user_id\":12}', '2026-06-23 13:46:24'),
(80, NULL, 'login_success_v2', 'login', 11, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":11}', '2026-06-23 13:47:30'),
(81, NULL, 'login_success_v2', 'login', 11, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":11}', '2026-06-23 13:47:31'),
(82, NULL, 'login_success_v2', 'login', 11, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":11}', '2026-06-23 13:47:33'),
(83, NULL, 'login_success_v2', 'login', 11, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":11}', '2026-06-23 13:47:34'),
(84, NULL, 'login_success_v2', 'login', 11, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":11}', '2026-06-23 13:47:35'),
(85, NULL, 'login_success_v2', 'login', 11, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":11}', '2026-06-23 13:47:36'),
(86, NULL, 'login_success_v2', 'login', 11, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":11}', '2026-06-23 13:47:38'),
(87, NULL, 'login_success_v2', 'login', 11, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":11}', '2026-06-23 13:47:39'),
(88, NULL, 'login_success_v2', 'login', 11, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":11}', '2026-06-23 13:47:40'),
(89, NULL, 'login_success_v2', 'login', 11, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":11}', '2026-06-23 13:49:33'),
(90, NULL, 'login_success_v2', 'login', 11, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":11}', '2026-06-23 13:49:35'),
(91, NULL, 'login_success_v2', 'login', 11, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":11}', '2026-06-23 13:49:36'),
(92, NULL, 'login_success_v2', 'login', 11, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":11}', '2026-06-23 13:49:37'),
(93, NULL, 'login_success_v2', 'login', 11, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":11}', '2026-06-23 13:49:38'),
(94, NULL, 'login_success_v2', 'login', 11, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":11}', '2026-06-23 13:49:39'),
(95, NULL, 'login_success_v2', 'login', 11, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":11}', '2026-06-23 13:49:40'),
(96, NULL, 'login_success_v2', 'login', 2, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":2}', '2026-06-25 08:40:51'),
(97, NULL, 'login_success_v2', 'login', 9, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":9}', '2026-06-25 08:41:51'),
(98, NULL, 'login_success_v2', 'login', 9, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":9}', '2026-06-25 08:42:29'),
(99, NULL, 'logout_v2', 'logout', NULL, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/logout', 200, '{\"user_id\":null}', '2026-06-25 08:42:56'),
(100, NULL, 'login_success_v2', 'login', 14, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":14}', '2026-06-25 08:43:41'),
(101, 14, 'review_updated_v2', 'review', 3, '::1', 'PostmanRuntime/7.54.0', 'PUT', '/Bugsy/api/v2/reviews/3', 200, '{\"review_id\":3,\"user_id\":14,\"changes\":[\"rating\",\"comment\"]}', '2026-06-25 08:45:36'),
(102, 14, 'product_created_v2', 'product', 21, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/products', 200, '{\"product_id\":\"21\",\"by\":14}', '2026-06-25 08:47:53'),
(103, NULL, 'logout_v2', 'logout', NULL, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/logout', 200, '{\"user_id\":null}', '2026-06-25 08:50:06'),
(104, NULL, 'login_success_v2', 'login', 9, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":9}', '2026-06-27 13:54:51'),
(105, 9, 'password_changed_v2', 'unknown', 9, '::1', 'PostmanRuntime/7.54.0', 'PUT', '/Bugsy/api/v2/users/9/password', 200, '{\"user_id\":9}', '2026-06-27 13:57:58'),
(106, NULL, 'login_success_v2', 'login', 2, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":2}', '2026-06-27 18:30:51'),
(107, NULL, 'login_success_v2', 'login', 2, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":2}', '2026-06-28 12:46:06'),
(108, 2, 'cart_item_added_v2', 'cart', 9, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/add', 200, '{\"user_id\":2,\"product_id\":9,\"quantity\":1}', '2026-06-28 12:47:35'),
(109, 2, 'cart_item_added_v2', 'cart', 1, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/add', 200, '{\"user_id\":2,\"product_id\":1,\"quantity\":1}', '2026-06-28 12:49:19'),
(110, NULL, 'login_success_v2', 'login', 2, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":2}', '2026-06-28 13:52:33'),
(111, NULL, 'login_success_v2', 'login', 2, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":2}', '2026-06-28 15:08:25'),
(112, NULL, 'login_success_v2', 'login', 2, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":2}', '2026-06-28 15:08:45'),
(113, 2, 'order_created_v2', 'order', 17, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/checkout', 200, '{\"user_id\":2,\"order_id\":17,\"total\":\"100200.00\",\"promo_code\":null,\"discount_amount\":\"0.00\"}', '2026-06-28 15:11:52'),
(114, 2, 'cart_item_added_v2', 'cart', 15, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/add', 200, '{\"user_id\":2,\"product_id\":15,\"quantity\":1}', '2026-06-28 15:47:37'),
(115, 2, 'cart_item_added_v2', 'cart', 15, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/add', 200, '{\"user_id\":2,\"product_id\":15,\"quantity\":1}', '2026-06-28 15:47:51'),
(116, 2, 'order_created_v2', 'order', 18, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/checkout', 200, '{\"user_id\":2,\"order_id\":18,\"total\":\"200.00\",\"promo_code\":null,\"discount_amount\":\"0.00\"}', '2026-06-28 15:48:17'),
(117, NULL, 'login_success_v2', 'login', 14, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":14}', '2026-06-28 15:50:40'),
(118, 14, 'promo_code_created_v2', 'unknown', NULL, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/promo-codes', 200, '{\"promo_id\":\"2\",\"code\":\"WELCOME50\",\"by\":14}', '2026-06-28 15:51:46'),
(119, NULL, 'login_success_v2', 'login', 2, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":2}', '2026-06-28 15:52:46'),
(120, 2, 'cart_item_added_v2', 'cart', 3, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/add', 200, '{\"user_id\":2,\"product_id\":3,\"quantity\":1}', '2026-06-28 15:54:56'),
(121, 2, 'order_created_v2', 'order', 19, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/checkout', 200, '{\"user_id\":2,\"order_id\":19,\"total\":\"10000.00\",\"promo_code\":null,\"discount_amount\":\"0.00\"}', '2026-06-28 15:56:00'),
(122, 2, 'cart_item_added_v2', 'cart', 3, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/add', 200, '{\"user_id\":2,\"product_id\":3,\"quantity\":1}', '2026-06-28 15:56:33'),
(123, 2, 'promo_applied_v2', 'unknown', 2, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/promo', 200, '{\"user_id\":2,\"code\":\"WELCOME50\"}', '2026-06-28 15:57:36'),
(124, 2, 'order_created_v2', 'order', 20, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/checkout', 200, '{\"user_id\":2,\"order_id\":20,\"total\":\"5000.00\",\"promo_code\":\"WELCOME50\",\"discount_amount\":\"5000.00\"}', '2026-06-28 15:59:35'),
(125, 2, 'cart_item_added_v2', 'cart', 3, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/add', 200, '{\"user_id\":2,\"product_id\":3,\"quantity\":1}', '2026-06-28 16:00:20'),
(126, 2, 'cart_item_added_v2', 'cart', 3, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/add', 200, '{\"user_id\":2,\"product_id\":3,\"quantity\":1}', '2026-06-28 16:00:27'),
(127, 2, 'cart_item_added_v2', 'cart', 21, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/add', 200, '{\"user_id\":2,\"product_id\":21,\"quantity\":1}', '2026-06-28 16:00:42'),
(128, 2, 'promo_applied_v2', 'unknown', 2, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/promo', 200, '{\"user_id\":2,\"code\":\"WELCOME50\"}', '2026-06-28 16:01:50'),
(129, 2, 'order_created_v2', 'order', 21, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/checkout', 200, '{\"user_id\":2,\"order_id\":21,\"total\":\"10025.00\",\"promo_code\":\"WELCOME50\",\"discount_amount\":\"10025.00\"}', '2026-06-28 16:02:26'),
(130, 2, 'cart_item_added_v2', 'cart', 1, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/add', 200, '{\"user_id\":2,\"product_id\":1,\"quantity\":1}', '2026-06-28 16:03:05'),
(131, NULL, 'login_success_v2', 'login', 11, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":11}', '2026-06-28 16:04:09'),
(132, 11, 'cart_item_added_v2', 'cart', 1, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/add', 200, '{\"user_id\":11,\"product_id\":1,\"quantity\":1}', '2026-06-28 16:04:53'),
(133, 11, 'order_created_v2', 'order', 22, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/checkout', 200, '{\"user_id\":11,\"order_id\":22,\"total\":\"100000.00\",\"promo_code\":null,\"discount_amount\":\"0.00\"}', '2026-06-28 16:05:14'),
(134, 11, 'cart_item_added_v2', 'cart', 1, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/add', 200, '{\"user_id\":11,\"product_id\":1,\"quantity\":1}', '2026-06-28 16:05:41'),
(135, 11, 'promo_applied_v2', 'unknown', 11, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/promo', 200, '{\"user_id\":11,\"code\":\"WELCOME50\"}', '2026-06-28 16:06:16'),
(136, 11, 'order_created_v2', 'order', 23, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/checkout', 200, '{\"user_id\":11,\"order_id\":23,\"total\":\"50000.00\",\"promo_code\":\"WELCOME50\",\"discount_amount\":\"50000.00\"}', '2026-06-28 16:07:02'),
(137, 11, 'cart_item_added_v2', 'cart', 1, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/add', 200, '{\"user_id\":11,\"product_id\":1,\"quantity\":1}', '2026-06-28 16:08:01'),
(138, 11, 'promo_applied_v2', 'unknown', 11, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/promo', 200, '{\"user_id\":11,\"code\":\"WELCOME20\"}', '2026-06-28 16:08:30'),
(139, 11, 'order_created_v2', 'order', 24, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/checkout', 200, '{\"user_id\":11,\"order_id\":24,\"total\":\"80000.00\",\"promo_code\":\"WELCOME20\",\"discount_amount\":\"20000.00\"}', '2026-06-28 16:10:08'),
(140, NULL, 'login_success_v2', 'login', 14, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":14}', '2026-06-28 16:13:38'),
(141, NULL, 'login_success_v2', 'login', 3, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":3}', '2026-06-28 16:15:06'),
(142, 3, 'promo_code_updated_v2', 'unknown', NULL, '::1', 'PostmanRuntime/7.54.0', 'PUT', '/Bugsy/api/v2/promo-codes/2', 200, '{\"promo_id\":2,\"fields\":[\"is_active\"],\"by\":3}', '2026-06-28 16:16:20'),
(143, NULL, 'login_success_v2', 'login', 2, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":2}', '2026-06-28 16:17:28'),
(144, 2, 'cart_item_added_v2', 'cart', 21, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/add', 200, '{\"user_id\":2,\"product_id\":21,\"quantity\":1}', '2026-06-28 16:18:36'),
(145, 2, 'cart_item_removed_v2', 'cart', 2, '::1', 'PostmanRuntime/7.54.0', 'DELETE', '/Bugsy/api/v2/cart/items/30', 200, '{\"user_id\":2,\"item_id\":30}', '2026-06-28 16:19:09'),
(146, 2, 'cart_item_added_v2', 'cart', 21, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/add', 200, '{\"user_id\":2,\"product_id\":21,\"quantity\":1}', '2026-06-28 16:20:05'),
(147, 2, 'cart_item_added_v2', 'cart', 21, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/add', 200, '{\"user_id\":2,\"product_id\":21,\"quantity\":1}', '2026-06-28 16:20:08'),
(148, 2, 'promo_applied_v2', 'unknown', 2, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/promo', 200, '{\"user_id\":2,\"code\":\"WELCOME20\"}', '2026-06-28 16:21:09'),
(149, NULL, 'login_success_v2', 'login', 12, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":12}', '2026-07-01 14:04:25'),
(150, 12, 'cart_item_added_v2', 'cart', 1, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/add', 200, '{\"user_id\":12,\"product_id\":1,\"quantity\":1}', '2026-07-01 14:05:14'),
(151, 12, 'promo_applied_v2', 'unknown', 12, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/promo', 200, '{\"user_id\":12,\"code\":\"WELCOME20\"}', '2026-07-01 14:05:38'),
(152, 12, 'order_created_v2', 'order', 25, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/checkout', 200, '{\"user_id\":12,\"order_id\":25,\"total\":\"80000.00\",\"promo_code\":\"WELCOME20\",\"discount_amount\":\"20000.00\"}', '2026-07-01 14:05:59'),
(153, NULL, 'login_success_v2', 'login', 3, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":3}', '2026-07-01 16:32:57'),
(154, NULL, 'login_success_v2', 'login', 3, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":3}', '2026-07-01 16:33:12'),
(155, 3, 'product_created_v2', 'product', 22, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/products', 200, '{\"product_id\":\"22\",\"by\":3}', '2026-07-01 16:37:52'),
(156, NULL, 'login_success_v2', 'login', 2, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":2}', '2026-07-02 20:20:58'),
(157, 2, 'cart_item_added_v2', 'cart', 1, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/add', 200, '{\"user_id\":2,\"product_id\":1,\"quantity\":1}', '2026-07-02 21:04:22'),
(158, 2, 'order_created_v2', 'order', 27, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/checkout', 200, '{\"user_id\":2,\"order_id\":27,\"total\":\"100000.00\",\"promo_code\":null,\"discount_amount\":\"0.00\"}', '2026-07-02 21:04:50'),
(159, 2, 'cart_item_added_v2', 'cart', 21, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/add', 200, '{\"user_id\":2,\"product_id\":21,\"quantity\":1}', '2026-07-02 21:15:14'),
(160, NULL, 'login_success_v2', 'login', 2, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":2}', '2026-07-02 21:24:38'),
(161, 2, 'cart_item_added_v2', 'cart', 23, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/add', 200, '{\"user_id\":2,\"product_id\":23,\"quantity\":1}', '2026-07-02 21:25:54'),
(162, 2, 'promo_applied_v2', 'unknown', 2, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/promo', 200, '{\"user_id\":2,\"code\":\"WELCOME20\"}', '2026-07-02 21:26:21'),
(163, 2, 'cart_cleared_v2', 'cart', 2, '::1', 'PostmanRuntime/7.54.0', 'DELETE', '/Bugsy/api/v2/cart/clear', 200, '{\"user_id\":2}', '2026-07-02 21:26:32'),
(164, 2, 'cart_item_added_v2', 'cart', 23, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/add', 200, '{\"user_id\":2,\"product_id\":23,\"quantity\":1}', '2026-07-02 21:30:09'),
(165, 2, 'order_created_v2', 'order', 28, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/checkout', 200, '{\"user_id\":2,\"order_id\":28,\"total\":\"100.00\",\"promo_code\":null,\"discount_amount\":\"0.00\"}', '2026-07-02 21:30:52'),
(166, 2, 'cart_item_added_v2', 'cart', 23, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/add', 200, '{\"user_id\":2,\"product_id\":23,\"quantity\":1}', '2026-07-02 21:53:26'),
(167, 2, 'order_created_v2', 'order', 30, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/checkout', 200, '{\"user_id\":2,\"order_id\":30,\"total\":\"100.00\",\"promo_code\":null,\"discount_amount\":\"0.00\"}', '2026-07-02 21:53:44'),
(168, NULL, 'login_success_v2', 'login', 2, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/login', 200, '{\"user_id\":2}', '2026-07-04 17:37:09'),
(169, 2, 'cart_item_added_v2', 'cart', 3, '::1', 'PostmanRuntime/7.54.0', 'POST', '/Bugsy/api/v2/cart/add', 200, '{\"user_id\":2,\"product_id\":3,\"quantity\":1}', '2026-07-04 18:08:07');

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `name`, `logo`, `created_at`, `updated_at`) VALUES
(1, 'Apple', 'product_6908c8f0e400f4.50078718.png', '2025-11-03 13:23:28', '2025-11-03 13:23:28'),
(2, 'Samsung', 'product_6908c91d6a6ee3.36329258.png', '2025-11-03 13:24:13', '2025-11-03 13:24:13'),
(3, 'Huawei', 'product_6908c94863f3d8.52257896.png', '2025-11-03 13:24:56', '2025-11-03 13:24:56'),
(4, 'Xiaomi', 'product_6908c9633e4af7.80363757.png', '2025-11-03 13:25:23', '2025-11-03 13:25:23'),
(5, 'Vivo', 'product_6908c9df4cf1e8.89284801.png', '2025-11-03 13:25:57', '2025-11-03 13:27:27'),
(6, 'OPPO', 'product_8415b4bb8138b504.png', '2026-06-23 06:34:59', '2026-06-23 06:34:59'),
(7, 'Honor', 'product_fa0f349db6a80573.png', '2026-06-23 06:36:42', '2026-06-23 06:36:42');

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `promo_code` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `carts`
--

INSERT INTO `carts` (`id`, `user_id`, `promo_code`, `created_at`, `updated_at`) VALUES
(1, 2, NULL, '2025-11-03 14:28:30', '2026-07-02 21:26:32'),
(2, 5, NULL, '2025-11-03 14:36:56', '2026-06-28 09:39:43'),
(3, 7, NULL, '2025-12-08 14:29:20', '2025-12-08 14:29:20'),
(4, 3, NULL, '2025-12-08 14:49:24', '2025-12-08 14:49:24'),
(5, 11, NULL, '2026-06-18 09:30:45', '2026-06-28 16:10:08'),
(6, 12, NULL, '2026-06-18 09:33:24', '2026-07-01 14:05:59'),
(7, 1, NULL, '2026-06-28 06:42:46', '2026-06-28 09:44:49'),
(8, 6, NULL, '2026-06-28 06:46:02', '2026-06-28 09:48:01'),
(9, 15, NULL, '2026-07-02 11:52:23', '2026-07-02 14:59:43');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`id`, `cart_id`, `product_id`, `quantity`, `price`, `created_at`, `updated_at`) VALUES
(36, 9, 1, 1, 100000.00, '2026-07-02 11:52:23', '2026-07-02 11:52:23'),
(45, 1, 3, 1, 10000.00, '2026-07-04 15:08:07', '2026-07-04 15:08:07');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `cat_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `cat_image`, `created_at`, `updated_at`) VALUES
(1, 'Phones', 'Smartphones and mobile phones', 'product_6908cb52d79551.44240455.png', '2025-11-03 13:33:38', '2025-11-03 13:33:38'),
(2, 'Tablets', 'Tablets and iPads', 'product_6908cb7b1b0098.27812214.png', '2025-11-03 13:34:19', '2025-11-03 13:34:19'),
(3, 'Laptops', 'Laptops and notebooks', 'product_6908cba5963bd3.12274598.png', '2025-11-03 13:35:01', '2025-11-03 13:35:01'),
(4, 'Smartwatches', 'Smart watches and fitness trackers', 'product_6908cbca3261e7.68249439.png', '2025-11-03 13:35:38', '2025-11-03 13:35:38'),
(5, 'Headphones', 'Headphones, earbuds, and audio devices', 'product_6908cbf3691f89.39831193.png', '2025-11-03 13:36:19', '2025-11-03 13:36:19');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `payment_method` varchar(50) NOT NULL,
  `promo_code` varchar(50) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `shipping_address` text NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `user_id`, `total`, `status`, `payment_method`, `promo_code`, `discount_amount`, `payment_status`, `shipping_address`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'ORD-20251103-00001', 2, 200000.00, 'shipped', 'cash', NULL, 0.00, 'pending', 'xaddress', 'Please call before delivery', '2025-11-03 14:32:21', '2026-06-23 12:09:48'),
(2, 'ORD-20251103-00002', 5, 10000.00, 'processing', 'credit_card', NULL, 0.00, 'paid', '...', 'Please call before delivery', '2025-11-03 14:39:41', '2025-11-03 14:39:42'),
(3, 'ORD-20251208-00001', 7, 300000.00, 'processing', 'cash', NULL, 0.00, 'pending', 'x', NULL, '2025-12-08 14:30:43', '2025-12-08 14:30:43'),
(4, 'ORD-20251208-00002', 7, 180000.00, 'shipped', 'cash', NULL, 0.00, 'pending', 'x', NULL, '2025-12-08 14:32:04', '2025-12-08 15:05:09'),
(6, 'ORD-6D379B3565', 11, 200000.00, 'shipped', 'cash_on_delivery', NULL, 0.00, 'pending', '123 Cairo Street', '', '2026-06-18 12:37:31', '2026-06-18 13:05:06'),
(7, 'ORD-5CB7F59CC0', 12, 1060000.00, 'cancelled', 'cash_on_delivery', NULL, 0.00, 'pending', '123 Cairo Street', '', '2026-06-18 12:39:20', '2026-06-18 12:41:16'),
(8, 'ORD-2850C8EDAD', 12, 200000.00, 'cancelled', 'cash_on_delivery', NULL, 0.00, 'pending', 'x', '', '2026-06-18 12:58:05', '2026-06-18 12:58:44'),
(9, 'ORD-08CF88FF86', 11, 60000.00, 'cancelled', 'cash_on_delivery', NULL, 0.00, 'pending', 'x', '', '2026-06-23 12:02:58', '2026-06-23 12:06:59'),
(10, 'ORD-20260628-00001', 2, 535000.00, 'processing', 'cash', NULL, 0.00, 'pending', 'x', NULL, '2026-06-28 05:24:33', '2026-06-28 05:24:33'),
(11, 'ORD-20260628-00002', 2, 35000.00, 'processing', 'cash', NULL, 0.00, 'pending', 'x', NULL, '2026-06-28 05:58:24', '2026-06-28 05:58:24'),
(12, 'ORD-20260628-00003', 2, 56080.00, 'processing', 'cash', 'WELCOME20', 14020.00, 'pending', 'x', NULL, '2026-06-28 06:28:19', '2026-06-28 06:28:19'),
(13, 'ORD-20260628-00004', 2, 35000.00, 'processing', 'cash', NULL, 0.00, 'pending', 'x', NULL, '2026-06-28 06:34:15', '2026-06-28 06:34:15'),
(14, 'ORD-20260628-00005', 5, 80.00, 'processing', 'cash', 'WELCOME20', 20.00, 'pending', 'x', NULL, '2026-06-28 06:39:43', '2026-06-28 06:39:43'),
(15, 'ORD-20260628-00006', 1, 120.00, 'processing', 'cash', 'WELCOME20', 30.00, 'pending', 'x', NULL, '2026-06-28 06:44:49', '2026-06-28 06:44:49'),
(16, 'ORD-20260628-00007', 6, 28000.00, 'processing', 'cash', 'WELCOME20', 7000.00, 'pending', 'x', NULL, '2026-06-28 06:48:01', '2026-06-28 06:48:01'),
(17, 'ORD-83F998E970', 2, 100200.00, 'pending', 'cash_on_delivery', NULL, 0.00, 'pending', 'x', '', '2026-06-28 15:11:52', '2026-06-28 15:11:52'),
(18, 'ORD-1380FD09FA', 2, 200.00, 'pending', 'cash_on_delivery', NULL, 0.00, 'pending', 'x', '', '2026-06-28 15:48:17', '2026-06-28 15:48:17'),
(19, 'ORD-76CA8664AF', 2, 10000.00, 'pending', 'cash_on_delivery', NULL, 0.00, 'pending', 'd', '', '2026-06-28 15:56:00', '2026-06-28 15:56:00'),
(20, 'ORD-CC5C11F66B', 2, 5000.00, 'pending', 'cash_on_delivery', 'WELCOME50', 5000.00, 'pending', 'x', '', '2026-06-28 15:59:35', '2026-06-28 15:59:35'),
(21, 'ORD-4225ABCD92', 2, 10025.00, 'pending', 'cash_on_delivery', 'WELCOME50', 10025.00, 'pending', 'x', '', '2026-06-28 16:02:26', '2026-06-28 16:02:26'),
(22, 'ORD-2D9676FF98', 11, 100000.00, 'pending', 'cash_on_delivery', NULL, 0.00, 'pending', 'x', '', '2026-06-28 16:05:14', '2026-06-28 16:05:14'),
(23, 'ORD-FEB0C1E87D', 11, 50000.00, 'pending', 'cash_on_delivery', 'WELCOME50', 50000.00, 'pending', 'x', '', '2026-06-28 16:07:02', '2026-06-28 16:07:02'),
(24, 'ORD-FE5CF5C291', 11, 80000.00, 'pending', 'cash_on_delivery', 'WELCOME20', 20000.00, 'pending', 'x', '', '2026-06-28 16:10:08', '2026-06-28 16:10:08'),
(25, 'ORD-AC1F3610A3', 12, 80000.00, 'pending', 'cash_on_delivery', 'WELCOME20', 20000.00, 'pending', 'x', '', '2026-07-01 14:05:59', '2026-07-01 14:05:59'),
(26, 'ORD-20260702-00001', 2, 100150.00, 'processing', 'cash', NULL, 0.00, 'pending', 'x', NULL, '2026-07-02 18:03:08', '2026-07-02 18:03:08'),
(27, 'ORD-6C0DD03705', 2, 100000.00, 'pending', 'cash_on_delivery', NULL, 0.00, 'pending', 'x', '', '2026-07-02 21:04:50', '2026-07-02 21:04:50'),
(28, 'ORD-6894671C0A', 2, 100.00, 'pending', 'cash_on_delivery', NULL, 0.00, 'pending', 'd', '', '2026-07-02 21:30:52', '2026-07-02 21:30:52'),
(29, 'ORD-20260702-00002', 2, 100.00, 'processing', 'cash', NULL, 0.00, 'pending', 'x', NULL, '2026-07-02 18:50:59', '2026-07-02 18:50:59'),
(30, 'ORD-804E5CBCB3', 2, 100.00, 'pending', 'cash_on_delivery', NULL, 0.00, 'pending', 'd', '', '2026-07-02 21:53:44', '2026-07-02 21:53:44'),
(31, 'ORD-20260702-00003', 2, 100.00, 'processing', 'cash', NULL, 0.00, 'pending', 'x', NULL, '2026-07-02 18:54:32', '2026-07-02 18:54:32');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `price`, `subtotal`, `created_at`, `updated_at`) VALUES
(2, 1, 1, 'iPhone 17 Pro Max', 1, 100000.00, 100000.00, '2025-11-03 14:32:21', '2025-11-03 14:32:21'),
(3, 2, 3, 'myPhone redmi not 8', 1, 10000.00, 10000.00, '2025-11-03 14:39:41', '2025-11-03 14:39:41'),
(4, 3, 1, 'iPhone 17 Pro Max', 3, 100000.00, 300000.00, '2025-12-08 14:30:43', '2025-12-08 14:30:43'),
(7, 6, 1, 'iPhone 17 Pro Max', 2, 100000.00, 200000.00, '2026-06-18 12:37:31', '2026-06-18 12:37:31'),
(9, 8, 1, 'iPhone 17 Pro Max', 2, 100000.00, 200000.00, '2026-06-18 12:58:05', '2026-06-18 12:58:05'),
(11, 10, 6, 'Huawei Nova 13 Pro', 1, 35000.00, 35000.00, '2026-06-28 05:24:33', '2026-06-28 05:24:33'),
(12, 10, 1, 'iPhone 17 Pro Max', 5, 100000.00, 500000.00, '2026-06-28 05:24:33', '2026-06-28 05:24:33'),
(13, 11, 6, 'Huawei Nova 13 Pro', 1, 35000.00, 35000.00, '2026-06-28 05:58:24', '2026-06-28 05:58:24'),
(14, 12, 9, 'Test', 1, 100.00, 100.00, '2026-06-28 06:28:19', '2026-06-28 06:28:19'),
(15, 12, 6, 'Huawei Nova 13 Pro', 2, 35000.00, 70000.00, '2026-06-28 06:28:19', '2026-06-28 06:28:19'),
(16, 13, 6, 'Huawei Nova 13 Pro', 1, 35000.00, 35000.00, '2026-06-28 06:34:15', '2026-06-28 06:34:15'),
(17, 14, 9, 'Test', 1, 100.00, 100.00, '2026-06-28 06:39:43', '2026-06-28 06:39:43'),
(18, 15, 21, 'xx', 3, 50.00, 150.00, '2026-06-28 06:44:49', '2026-06-28 06:44:49'),
(19, 16, 6, 'Huawei Nova 13 Pro', 1, 35000.00, 35000.00, '2026-06-28 06:48:01', '2026-06-28 06:48:01'),
(20, 17, 9, 'Test', 2, 100.00, 200.00, '2026-06-28 15:11:52', '2026-06-28 15:11:52'),
(21, 17, 1, 'iPhone 17 Pro Max', 1, 100000.00, 100000.00, '2026-06-28 15:11:52', '2026-06-28 15:11:52'),
(22, 18, 15, 'Test2', 2, 100.00, 200.00, '2026-06-28 15:48:17', '2026-06-28 15:48:17'),
(23, 19, 3, 'myPhone redmi not 8', 1, 10000.00, 10000.00, '2026-06-28 15:56:00', '2026-06-28 15:56:00'),
(24, 20, 3, 'myPhone redmi not 8', 1, 10000.00, 10000.00, '2026-06-28 15:59:35', '2026-06-28 15:59:35'),
(25, 21, 3, 'myPhone redmi not 8', 2, 10000.00, 20000.00, '2026-06-28 16:02:26', '2026-06-28 16:02:26'),
(26, 21, 21, 'xx', 1, 50.00, 50.00, '2026-06-28 16:02:26', '2026-06-28 16:02:26'),
(27, 22, 1, 'iPhone 17 Pro Max', 1, 100000.00, 100000.00, '2026-06-28 16:05:14', '2026-06-28 16:05:14'),
(28, 23, 1, 'iPhone 17 Pro Max', 1, 100000.00, 100000.00, '2026-06-28 16:07:02', '2026-06-28 16:07:02'),
(29, 24, 1, 'iPhone 17 Pro Max', 1, 100000.00, 100000.00, '2026-06-28 16:10:08', '2026-06-28 16:10:08'),
(30, 25, 1, 'iPhone 17 Pro Max', 1, 100000.00, 100000.00, '2026-07-01 14:05:59', '2026-07-01 14:05:59'),
(31, 26, 1, 'iPhone 17 Pro Max', 1, 100000.00, 100000.00, '2026-07-02 18:03:08', '2026-07-02 18:03:08'),
(32, 26, 21, 'xx', 3, 50.00, 150.00, '2026-07-02 18:03:08', '2026-07-02 18:03:08'),
(33, 27, 1, 'iPhone 17 Pro Max', 1, 100000.00, 100000.00, '2026-07-02 21:04:50', '2026-07-02 21:04:50'),
(34, 28, 23, 'test2', 1, 100.00, 100.00, '2026-07-02 21:30:52', '2026-07-02 21:30:52'),
(35, 29, 22, 'test', 1, 100.00, 100.00, '2026-07-02 18:50:59', '2026-07-02 18:50:59'),
(36, 30, 23, 'test2', 1, 100.00, 100.00, '2026-07-02 21:53:44', '2026-07-02 21:53:44'),
(37, 31, 22, 'test', 1, 100.00, 100.00, '2026-07-02 18:54:32', '2026-07-02 18:54:32');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `brand_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `specifications` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specifications`)),
  `main_image` varchar(255) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `stock`, `brand_id`, `category_id`, `specifications`, `main_image`, `rating`, `is_available`, `created_at`, `updated_at`) VALUES
(1, 'iPhone 17 Pro Max', 'Apple IPhone 17 Pro Max With FaceTime ', 100000.00, 0, 1, 1, '{\"ram\": \"12GB\", \"storage\": \"256GB\",\"color\":\"orange\"}', 'product_6908d18e9e4c05.13656668.png', 5.00, 1, '2025-11-03 14:00:14', '2026-07-02 21:04:50'),
(3, 'myPhone redmi not 8', '', 10000.00, 26, 4, 1, '{\"ram\": \"6GB\", \"storage\": \"64GB\", \"camera\": \"48MP + 12MP + 12MP\", \"color\": \"blue\"}', 'product_6908d365b721c8.84164463.jpg', 5.00, 1, '2025-11-03 14:08:05', '2026-06-28 16:02:26'),
(6, 'Huawei Nova 13 Pro', 'Huawei Nova 13 Pro Smartphone, 6.8\" Display, Advanced Camera System, Long Battery Life, Dual SIM – Black', 35000.00, 11, 3, 1, '{\"ram\": \"12GB\", \"storage\": \"512GB\",\"color\": \"Black\"}', 'product_6908d6f28a8ec1.44157427.jpg', 4.00, 1, '2025-11-03 14:23:14', '2026-06-28 06:48:01'),
(9, 'Test', NULL, 100.00, 1, 1, 1, NULL, NULL, 0.00, 1, '2026-06-22 09:24:37', '2026-06-28 15:11:52'),
(15, 'Test2', NULL, 100.00, 3, 1, 1, NULL, NULL, 0.00, 1, '2026-06-22 09:33:30', '2026-06-28 15:48:17'),
(21, 'xx', NULL, 50.00, 16, 1, 1, NULL, NULL, 0.00, 1, '2026-06-25 05:47:53', '2026-07-02 18:03:08'),
(22, 'test', NULL, 100.00, 3, 1, 1, NULL, 'product_440785df79da151c.png', 0.00, 1, '2026-07-01 13:37:52', '2026-07-02 18:54:32'),
(23, 'test2', NULL, 100.00, 3, 1, 1, NULL, 'product_6a4549e51bced2.42793757.png', 0.00, 1, '2026-07-01 14:09:57', '2026-07-02 21:53:44'),
(24, 'test2', NULL, 100.00, 5, 1, 1, NULL, 'product_6a4549fa69b1f5.14135954.png', 0.00, 1, '2026-07-01 14:10:18', '2026-07-01 14:10:18'),
(25, 'test6', NULL, 100.00, 5, 1, 1, NULL, 'product_6a454a172e2099.85939388.png', 0.00, 1, '2026-07-01 14:10:47', '2026-07-01 14:10:47');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `created_at`) VALUES
(1, 1, 'product_6908d18e9fff84.49180780.jpg', '2025-11-03 16:00:14'),
(2, 1, 'product_6908d18ea02619.87287016.jpg', '2025-11-03 16:00:14'),
(35, 22, 'product_652e11db615f6424.png', '2026-07-01 16:37:52'),
(36, 22, 'product_d1cecef31b1c21cf.png', '2026-07-01 16:37:52'),
(37, 22, 'product_7703b163c50c777e.png', '2026-07-01 16:37:52'),
(38, 22, 'product_fa0fdfa1fda3977d.png', '2026-07-01 16:37:52');

-- --------------------------------------------------------

--
-- Table structure for table `promo_codes`
--

CREATE TABLE `promo_codes` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `min_order_amount` decimal(10,2) DEFAULT 0.00,
  `usage_limit_total` int(11) DEFAULT NULL,
  `usage_limit_per_user` int(11) DEFAULT NULL,
  `times_used` int(11) DEFAULT 0,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `promo_codes`
--

INSERT INTO `promo_codes` (`id`, `code`, `description`, `discount_type`, `discount_value`, `min_order_amount`, `usage_limit_total`, `usage_limit_per_user`, `times_used`, `expires_at`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'WELCOME20', '20% off for new users', 'percentage', 20.00, 100.00, 500, 20, 6, '2026-07-05 20:59:59', 1, '2026-06-28 06:11:56', '2026-07-02 21:16:21'),
(2, 'WELCOME50', '50% off for new users', 'percentage', 50.00, 100.00, 500, 2, 3, '2026-12-31 21:59:59', 0, '2026-06-28 12:51:46', '2026-07-02 21:16:17'),
(3, 'WELCOME30', '30% off for new users', 'percentage', 30.00, 100.00, 500, 1, 0, '2026-12-31 21:59:59', 1, '2026-07-04 15:24:20', '2026-07-04 15:24:20');

-- --------------------------------------------------------

--
-- Table structure for table `promo_code_usage`
--

CREATE TABLE `promo_code_usage` (
  `id` int(11) NOT NULL,
  `promo_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `promo_code_usage`
--

INSERT INTO `promo_code_usage` (`id`, `promo_id`, `user_id`, `order_id`, `discount_amount`, `used_at`) VALUES
(1, 1, 2, 12, 14020.00, '2026-06-28 09:28:19'),
(2, 1, 5, 14, 20.00, '2026-06-28 09:39:43'),
(3, 1, 1, 15, 30.00, '2026-06-28 09:44:49'),
(4, 1, 6, 16, 7000.00, '2026-06-28 09:48:01'),
(5, 2, 2, 20, 5000.00, '2026-06-28 15:59:35'),
(6, 2, 2, 21, 10025.00, '2026-06-28 16:02:26'),
(7, 2, 11, 23, 50000.00, '2026-06-28 16:07:02'),
(8, 1, 11, 24, 20000.00, '2026-06-28 16:10:08'),
(9, 1, 12, 25, 20000.00, '2026-07-01 14:05:59');

-- --------------------------------------------------------

--
-- Table structure for table `rate_limits`
--

CREATE TABLE `rate_limits` (
  `id` int(11) NOT NULL,
  `identifier` varchar(255) NOT NULL,
  `attempts` int(11) NOT NULL DEFAULT 1,
  `window_start` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rate_limits`
--

INSERT INTO `rate_limits` (`id`, `identifier`, `attempts`, `window_start`, `created_at`) VALUES
(17, '::1:POST /login', 1, '2026-07-04 17:37:09', '2026-06-19 12:33:19');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` decimal(2,1) NOT NULL CHECK (`rating` >= 1.0 and `rating` <= 5.0),
  `title` varchar(255) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `is_verified_purchase` tinyint(1) DEFAULT 0,
  `helpful_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `title`, `comment`, `is_verified_purchase`, `helpful_count`, `created_at`, `updated_at`) VALUES
(1, 1, 5, 5.0, 'Great phone!', 'not buy yet but is Great...', 0, 0, '2025-11-03 14:42:07', '2026-06-11 09:59:51'),
(2, 3, 5, 5.0, 'Great phone!', 'Very satisfied with this purchase...', 1, 2, '2025-11-03 14:42:35', '2026-06-11 09:58:54'),
(3, 6, 5, 4.0, 'normal!', 'good', 0, 1, '2025-11-03 14:45:30', '2026-06-25 05:45:36'),
(4, 1, 11, 5.0, '', 'Great', 1, 0, '2026-06-22 09:35:34', '2026-06-22 09:35:34');

-- --------------------------------------------------------

--
-- Table structure for table `review_helpfulness`
--

CREATE TABLE `review_helpfulness` (
  `id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_helpful` tinyint(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `review_helpfulness`
--

INSERT INTO `review_helpfulness` (`id`, `review_id`, `user_id`, `is_helpful`, `created_at`) VALUES
(2, 2, 1, 1, '2026-06-11 09:57:33'),
(3, 2, 2, 1, '2026-06-11 09:58:54'),
(5, 3, 2, 1, '2026-06-11 09:59:29');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `token_hash`, `ip_address`, `user_agent`, `expires_at`, `created_at`) VALUES
(4, 12, '7a3ace9a9c69a10109345866aaeb8c44f0e8be38067dae7cd229566724dc5be7', '::1', 'PostmanRuntime/7.54.0', '2026-06-18 09:45:14', '2026-06-18 11:46:06'),
(5, 12, 'd3c7f67ac24e50eda874aee3952475e070e0f3fed575d8301586a321adf9750e', '::1', 'PostmanRuntime/7.54.0', '2026-06-23 07:42:05', '2026-06-23 09:43:34'),
(6, 9, '62f659c812648a3f596f89aa7ff7cc844665a8b7f4f528b02bae92f39830d3ad', '::1', 'PostmanRuntime/7.54.0', '2026-06-25 06:42:29', '2026-06-25 08:42:56'),
(7, 14, 'd08409e9dbb9d8aedc2c2d090c36b78a935398c8211ce95758db8382649ebcd7', '::1', 'PostmanRuntime/7.54.0', '2026-06-25 06:43:41', '2026-06-25 08:50:06');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `role` enum('customer','admin') DEFAULT 'customer',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `address`, `profile_photo`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'mohamed', 'imohamed.abdelatif@gmail.com', '81dc9bdb52d04dc20036dbd8313ed055', '1019902711', 'kafr saqr', 'user_1_1767709650_695d1bd23920b.jpg', 'customer', 1, '2025-11-03 11:23:20', '2026-01-06 12:27:30'),
(2, 'Ahmed Mohamed Updated', 'x@x.com', '81dc9bdb52d04dc20036dbd8313ed055', NULL, NULL, 'user_2_373ccada90e5ce7f.jpg', 'customer', 1, '2025-11-03 11:24:37', '2026-07-04 14:56:54'),
(3, 'xadmin', 'xadmin@x.com', '81dc9bdb52d04dc20036dbd8313ed055', NULL, NULL, NULL, 'admin', 1, '2025-11-03 11:24:48', '2026-01-06 13:06:50'),
(4, 'yadmin', 'yadmin@y.com', '81dc9bdb52d04dc20036dbd8313ed055', NULL, NULL, NULL, 'admin', 1, '2025-11-03 11:41:10', '2025-11-03 13:43:08'),
(5, 'y', 'y@y.com', '81dc9bdb52d04dc20036dbd8313ed055', NULL, NULL, '', 'customer', 1, '2025-11-03 11:41:57', '2026-01-06 14:20:58'),
(6, 'ahmed ali', 'ahmed.ali@gmail.com', '81dc9bdb52d04dc20036dbd8313ed055', NULL, NULL, NULL, 'customer', 1, '2025-11-03 13:40:39', '2025-11-03 13:40:39'),
(7, 'x', 'x@xxxxxxxx.com', '81dc9bdb52d04dc20036dbd8313ed055', NULL, NULL, NULL, 'customer', 1, '2025-11-24 18:30:50', '2025-11-24 18:30:50'),
(8, 'Ahmed Test v2 edited', 'ahmed@test.com', '$2y$12$/9BynBxwDrTv/jjlbpl3weW13jYOL3dnBVHTw44kIWGlx3kiCS8vm', '', 'Cairo', NULL, 'customer', 1, '2026-06-16 10:57:33', '2026-06-16 13:05:29'),
(9, 'xv2', 'xv2@x.com', '$2y$12$UaWxS0Lwi5522stlUMtFbOWm/FyDp9eAcIh83rYa7K9QBqOEaAtMu', '', '', NULL, 'customer', 1, '2026-06-16 12:08:22', '2026-06-27 10:57:58'),
(11, 'user A', 'userA@x.com', '$2y$12$yBpnVc9Y8CbnwapOlniEL.OE4l4f9Dn2F1mMFw4BfvbxfH606t8YS', '', '', NULL, 'customer', 1, '2026-06-18 08:39:32', '2026-06-18 08:39:32'),
(12, 'user B', 'userB@x.com', '$2y$12$Asd9no7z2rKWlu5LCkh8D.pJe6.pzlv.R3gaM7llQVheK8YuEn2hm', '', 'xxxxx', NULL, 'customer', 1, '2026-06-18 08:39:55', '2026-06-23 06:52:17'),
(14, 'xadmin2', 'xadmin2@x.com', '$2y$12$TNEni3clMJH446vv0sm/1.TaF1YcHA1gjYcDSeliLzqLVI1DYvZ1y', '', '', NULL, 'admin', 1, '2026-06-22 09:48:51', '2026-06-22 09:48:51'),
(15, 'me', 'imoalsaeed@gmail.com', '44b112c9b9e6ffea948898a487b00dd3', NULL, NULL, NULL, 'customer', 1, '2026-07-02 11:52:00', '2026-07-02 11:52:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_resource` (`resource_type`,`resource_id`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_ip` (`ip_address`);

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_name` (`name`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cart_product` (`cart_id`,`product_id`),
  ADD KEY `idx_cart` (`cart_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_name` (`name`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_order_number` (`order_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_brand` (`brand_id`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_price` (`price`),
  ADD KEY `idx_available` (`is_available`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `promo_codes`
--
ALTER TABLE `promo_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_code` (`code`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `promo_code_usage`
--
ALTER TABLE `promo_code_usage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_promo_user` (`promo_id`,`user_id`),
  ADD KEY `idx_order` (`order_id`);

--
-- Indexes for table `rate_limits`
--
ALTER TABLE `rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_identifier` (`identifier`),
  ADD KEY `idx_window` (`window_start`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `review_helpfulness`
--
ALTER TABLE `review_helpfulness`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_review` (`user_id`,`review_id`),
  ADD KEY `idx_review` (`review_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token_hash` (`token_hash`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_token` (`token_hash`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=170;

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `promo_codes`
--
ALTER TABLE `promo_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `promo_code_usage`
--
ALTER TABLE `promo_code_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `rate_limits`
--
ALTER TABLE `rate_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `review_helpfulness`
--
ALTER TABLE `review_helpfulness`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `promo_code_usage`
--
ALTER TABLE `promo_code_usage`
  ADD CONSTRAINT `promo_code_usage_ibfk_1` FOREIGN KEY (`promo_id`) REFERENCES `promo_codes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `promo_code_usage_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `promo_code_usage_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `review_helpfulness`
--
ALTER TABLE `review_helpfulness`
  ADD CONSTRAINT `review_helpfulness_ibfk_1` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_helpfulness_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
