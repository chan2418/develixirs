-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 07, 2026 at 06:20 AM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u295126515_develixirs`
--

-- --------------------------------------------------------

--
-- Table structure for table `authors`
--

CREATE TABLE `authors` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `authors`
--

INSERT INTO `authors` (`id`, `name`, `profile_pic`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 'chandru', '/assets/uploads/authors/author_693858d59d84d.png', 2, '2025-12-09 17:13:57', '2025-12-09 17:13:57'),
(2, 'DevElixir', '/assets/uploads/authors/author_693d291b5b601.jpg', 4, '2025-12-11 05:05:32', '2025-12-13 08:57:35');

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT '',
  `link` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `page_slot` varchar(32) NOT NULL DEFAULT 'home',
  `category_id` int(11) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `banners`
--

INSERT INTO `banners` (`id`, `name`, `filename`, `alt_text`, `link`, `is_active`, `created_at`, `updated_at`, `page_slot`, `category_id`, `category`) VALUES
(3, 'Homepage banner', '1763884825-1043deea86ec.jpeg', 'Welcome banner', 'http://127.0.0.1:8003/admin/banner.php', 1, '2025-11-23 08:00:25', '2025-11-23 08:00:25', 'home', NULL, NULL),
(4, 'omkarslider-m1-1-1250x500', '1763899414-4b34b58dbd5b.jpg', 'Welcome banner', 'http://127.0.0.1:8003/admin/banner.php', 1, '2025-11-23 12:03:34', '2025-11-23 12:03:34', 'home', NULL, NULL),
(18, 'Banner 2025-11-24 04:26:10', '1763958370-45607e9389.webp', '', '', 1, '2025-11-24 04:26:10', '2025-11-24 04:26:10', 'home_sidebar', NULL, NULL),
(19, 'Banner 2025-11-24 04:30:39', '1763958639-f7e505c329.webp', '', 'http://127.0.0.1:8003/admin/banner.php', 1, '2025-11-24 04:30:39', '2025-11-24 04:30:39', 'home_sidebar', NULL, NULL),
(23, 'Banner 2025-11-24 09:44:42', '1763977482-ae024bd1ab.jpg', '', 'https://www.google.com/search?num=10&newwindow=1&sca_esv=2d910e10f9e5456a&sxsrf=AE3TifO9tBra2CrsPjXqQh99Gjv1cyEOqg:1763898693488&udm=2&fbs=AIIjpHxU7SXXniUZfeShr2fp4giZ1Y6MJ25_tmWITc7uy4KIeioyp3OhN11EY0n5qfq-zENwnGygERInUV_0g0XKeHGJRAdFPaX_SSIJt7xYUfpm-_vQHq3hzHA2DKyn4_QBnQmzJzRbbjOPw1BvHSgNexNCWYY3W1po7cj-turW4QANz87mzlbnYh0yzluxEnlslmki5VQphgTLwzF5rw5S350lSej9Rw&q=banner+for+herbal+products&sa=X&ved=2ahUKEwi7963YmoiRAxXETGwGHVKXIbMQtKgLegQIEBAB&biw=1440&bih=715&dpr=2#vhid=7n5oaEnzHJDSdM&vssid=mosaic', 1, '2025-11-24 09:44:42', '2025-11-24 09:44:42', 'home_center', NULL, NULL),
(25, 'Banner 2025-11-25 01:06:17', '1764032777-07bb693f30.jpeg', '', '', 1, '2025-11-25 01:06:17', '2025-11-25 01:06:17', 'product', NULL, NULL),
(26, 'Banner 2025-11-25 01:35:49', '1764034549-de7b387273.jpg', '', '', 1, '2025-11-25 01:35:49', '2025-11-25 01:35:49', 'product', NULL, NULL),
(31, 'Banner 2025-11-26 04:03:49', '1764129829-0f1741f6d7.jpg', '', 'http://127.0.0.1:8003/admin/banner.php', 1, '2025-11-26 04:03:49', '2025-11-26 04:03:49', 'top_category', 4, NULL),
(32, 'Banner 2025-11-26 04:04:35', '1764129875-4e6451713e.webp', '', 'http://127.0.0.1:8003/admin/banner.php', 1, '2025-11-26 04:04:35', '2025-11-26 04:04:35', 'top_category', 10, NULL),
(34, 'Banner 2025-11-26 11:05:33', '1764155133-0bf8635920.png', '', 'http://127.0.0.1:8003/admin/banner.php', 1, '2025-11-26 11:05:33', '2025-11-26 11:05:33', 'category', 8, NULL),
(35, 'Banner 2025-11-26 11:06:27', '1764155187-1d999450aa.webp', '', 'http://127.0.0.1:8003/admin/banner.php', 1, '2025-11-26 11:06:27', '2025-11-26 11:06:27', 'category', 7, NULL),
(37, 'Banner 2025-11-26 11:09:29', '1764155369-91bdf11bf7.jpg', '', 'http://127.0.0.1:8003/admin/banner.php', 1, '2025-11-26 11:09:29', '2025-11-26 11:09:29', 'category', 9, NULL),
(39, 'Banner 2025-11-26 11:53:22', '1764158002-597297e414.webp', '', 'http://127.0.0.1:8003/admin/banner.php', 1, '2025-11-26 11:53:22', '2025-11-26 11:53:22', 'product_sidebar', NULL, NULL),
(45, 'Banner 2025-11-28 01:48:44', '1764294524-92be6d27bd.jpg', '', 'http://127.0.0.1:8003/admin/banner.php', 1, '2025-11-28 01:48:44', '2025-11-28 01:48:44', 'product_detail_sidebar', NULL, NULL),
(48, 'Banner 2025-11-29 17:02:57', '1764435777-d0e8c6cb13.jpg', '', 'http://127.0.0.1:8003/admin/banner.php', 1, '2025-11-29 17:02:57', '2025-11-29 17:02:57', 'blog', NULL, NULL),
(49, 'Welcome banner', '1764970834-13255aa056.jpg', 'Welcome banner', 'http://127.0.0.1:8003/admin/banner.php', 1, '2025-12-05 21:40:34', '2025-12-05 21:40:34', 'home', NULL, NULL),
(50, 'baby skin care', '1764970971-f077f169f0.jpg', 'baby skin care', 'http://127.0.0.1:8003/admin/banner.php', 1, '2025-12-05 21:42:51', '2025-12-05 21:42:51', 'home_sidebar', NULL, NULL),
(51, 'baby skin care', '1764971054-93b2d26881.jpg', 'baby skin care', 'http://127.0.0.1:8003/admin/banner.php', 1, '2025-12-05 21:44:14', '2025-12-05 21:44:14', 'home_sidebar', NULL, NULL),
(55, 'Banner 2025-12-05 21:56:34', '1764971794-a1c6dc7606.jpg', '', 'https://www.google.com/search?num=10&newwindow=1&sca_esv=2d910e10f9e5456a&sxsrf=AE3TifO9tBra2CrsPjXqQh99Gjv1cyEOqg:1763898693488&udm=2&fbs=AIIjpHxU7SXXniUZfeShr2fp4giZ1Y6MJ25_tmWITc7uy4KIeioyp3OhN11EY0n5qfq-zENwnGygERInUV_0g0XKeHGJRAdFPaX_SSIJt7xYUfpm-_vQHq3hzHA2DKyn4_QBnQmzJzRbbjOPw1BvHSgNexNCWYY3W1po7cj-turW4QANz87mzlbnYh0yzluxEnlslmki5VQphgTLwzF5rw5S350lSej9Rw&q=banner+for+herbal+products&sa=X&ved=2ahUKEwi7963YmoiRAxXETGwGHVKXIbMQtKgLegQIEBAB&biw=1440&bih=715&dpr=2#vhid=7n5oaEnzHJDSdM&vssid=mosaic', 1, '2025-12-05 21:56:34', '2025-12-05 21:56:34', 'home_center', NULL, NULL),
(56, 'ayurvedic skincare products', '1764972034-e710c14c90.jpg', 'ayurvedic skincare products', '', 1, '2025-12-05 22:00:34', '2025-12-05 22:00:34', 'product', NULL, NULL),
(57, 'Banner 2025-12-05 22:01:55', '1764972114-81afaebee5.png', '', 'http://127.0.0.1:8003/admin/banner.php', 1, '2025-12-05 22:01:55', '2025-12-05 22:01:55', 'product_sidebar', NULL, NULL),
(58, 'buy one get one', '1764972257-4070050f1c.jpg', 'buy one get one', '', 1, '2025-12-05 22:04:17', '2025-12-05 22:04:17', 'product_detail', NULL, NULL),
(59, 'Banner 2025-12-05 22:05:05', '1764972304-7d65edfe45.png', '', 'http://127.0.0.1:8003/admin/banner.php', 1, '2025-12-05 22:05:05', '2025-12-05 22:05:05', 'product_detail_sidebar', NULL, NULL),
(60, 'Banner 2025-12-05 22:07:23', '1764972443-dec645963e.jpg', '', 'http://127.0.0.1:8003/admin/banner.php', 1, '2025-12-05 22:07:23', '2025-12-05 22:07:23', 'blog', NULL, NULL),
(61, 'Holistic Ayurveda beauty blog for skin, hair, and baby care care', '1765594523-6746b8b501.jpg', 'Holistic Ayurveda beauty blog for skin, hair, and baby care care', 'http://127.0.0.1:8003/admin/banner.php', 1, '2025-12-13 02:55:23', '2025-12-13 02:55:23', 'blog', NULL, NULL),
(62, 'baby hair and body wash herbal mild cleansers with baby', '1766388075-084559bb04.jpg', 'baby hair and body wash herbal mild cleansers with baby', 'http://127.0.0.1:8003/admin/banner.php', 1, '2025-12-22 07:21:15', '2025-12-22 07:21:15', 'top_category', 34, NULL),
(67, 'skin care oils for beauty skin', '1767341303-f6d323cbe3.jpg', 'skin care oils for beauty skin', 'https://www.google.com/search?num=10&newwindow=1&sca_esv=2d910e10f9e5456a&sxsrf=AE3TifO9tBra2CrsPjXqQh99Gjv1cyEOqg:1763898693488&udm=2&fbs=AIIjpHxU7SXXniUZfeShr2fp4giZ1Y6MJ25_tmWITc7uy4KIeioyp3OhN11EY0n5qfq-zENwnGygERInUV_0g0XKeHGJRAdFPaX_SSIJt7xYUfpm-_vQHq3hzHA2DKyn4_QBnQmzJzRbbjOPw1BvHSgNexNCWYY3W1po7cj-turW4QANz87mzlbnYh0yzluxEnlslmki5VQphgTLwzF5rw5S350lSej9Rw&q=banner+for+herbal+products&sa=X&ved=2ahUKEwi7963YmoiRAxXETGwGHVKXIbMQtKgLegQIEBAB&biw=1440&bih=715&dpr=2#vhid=7n5oaEnzHJDSdM&vssid=mosaic', 1, '2026-01-02 08:08:23', '2026-01-02 08:08:23', 'home_center', NULL, NULL),
(76, 'skin care oils for beauty skin', '1767345409-799eca7844.jpg', 'skin care oils for beauty skin', 'https://www.google.com/search?num=10&newwindow=1&sca_esv=2d910e10f9e5456a&sxsrf=AE3TifO9tBra2CrsPjXqQh99Gjv1cyEOqg:1763898693488&udm=2&fbs=AIIjpHxU7SXXniUZfeShr2fp4giZ1Y6MJ25_tmWITc7uy4KIeioyp3OhN11EY0n5qfq-zENwnGygERInUV_0g0XKeHGJRAdFPaX_SSIJt7xYUfpm-_vQHq3hzHA2DKyn4_QBnQmzJzRbbjOPw1BvHSgNexNCWYY3W1po7cj-turW4QANz87mzlbnYh0yzluxEnlslmki5VQphgTLwzF5rw5S350lSej9Rw&q=banner+for+herbal+products&sa=X&ved=2ahUKEwi7963YmoiRAxXETGwGHVKXIbMQtKgLegQIEBAB&biw=1440&bih=715&dpr=2#vhid=7n5oaEnzHJDSdM&vssid=mosaic', 1, '2026-01-02 09:16:49', '2026-01-02 09:16:49', 'home_center', NULL, NULL),
(79, 'Banner 2026-01-02 14:51:29', '1767345688-5ceeb6c682.png', '', '', 1, '2026-01-02 09:21:29', '2026-01-02 09:21:29', 'home_before_blogs', NULL, NULL),
(80, 'Banner 2026-01-02 14:51:29', '1767345689-3b9e4feca0.jpg', '', '', 1, '2026-01-02 09:21:29', '2026-01-02 09:21:29', 'home_before_blogs', NULL, NULL),
(81, 'baby hair and body wash herbal mild cleansers with baby', '1767431669-e79e37bf99.jpg', 'baby hair and body wash herbal mild cleansers with baby', 'http://127.0.0.1:8003/admin/banner.php', 1, '2026-01-03 09:14:29', '2026-01-03 09:14:29', 'top_category', 31, NULL),
(82, 'baby hair and body wash herbal mild cleansers with baby', '1767432194-5add0c9ed0.jpg', 'baby hair and body wash herbal mild cleansers with baby', 'http://127.0.0.1:8003/admin/banner.php', 1, '2026-01-03 09:23:14', '2026-01-03 09:23:14', 'top_category', 31, NULL),
(83, 'skin care oils for beauty skin', '1767434334-c145390c93.jpg', 'skin care oils for beauty skin', 'https://www.google.com/search?num=10&newwindow=1&sca_esv=2d910e10f9e5456a&sxsrf=AE3TifO9tBra2CrsPjXqQh99Gjv1cyEOqg:1763898693488&udm=2&fbs=AIIjpHxU7SXXniUZfeShr2fp4giZ1Y6MJ25_tmWITc7uy4KIeioyp3OhN11EY0n5qfq-zENwnGygERInUV_0g0XKeHGJRAdFPaX_SSIJt7xYUfpm-_vQHq3hzHA2DKyn4_QBnQmzJzRbbjOPw1BvHSgNexNCWYY3W1po7cj-turW4QANz87mzlbnYh0yzluxEnlslmki5VQphgTLwzF5rw5S350lSej9Rw&q=banner+for+herbal+products&sa=X&ved=2ahUKEwi7963YmoiRAxXETGwGHVKXIbMQtKgLegQIEBAB&biw=1440&bih=715&dpr=2#vhid=7n5oaEnzHJDSdM&vssid=mosaic', 1, '2026-01-03 09:58:54', '2026-01-03 09:58:54', 'home_center', NULL, NULL),
(84, 'Welcome banner', '1767434349-b55e648bbb.jpg', 'Welcome banner', 'http://127.0.0.1:8003/admin/banner.php', 1, '2026-01-03 09:59:09', '2026-01-03 09:59:09', 'home', NULL, NULL),
(85, 'healthy skincare for beauty', '1767502907-89c1a3c89e.jpg', 'healthy skincare for beauty', 'http://127.0.0.1:8003/admin/banner.php', 1, '2026-01-04 05:01:47', '2026-01-04 05:01:47', 'home', NULL, NULL),
(86, 'ayurvedic for baby skin', '1767502964-3a0c800555.jpg', 'ayurvedic for baby skin', '', 1, '2026-01-04 05:02:44', '2026-01-04 05:02:44', 'product', NULL, NULL),
(87, 'join with us', '1767503450-f49035276d.jpg', 'join with us', 'http://127.0.0.1:8003/admin/banner.php', 1, '2026-01-04 05:10:50', '2026-01-04 05:10:50', 'blog', NULL, NULL),
(88, 'baby hair and body wash herbal mild cleansers with baby', '1767503764-aeb7e60461.jpg', 'baby hair and body wash herbal mild cleansers with baby', 'http://127.0.0.1:8003/admin/banner.php', 1, '2026-01-04 05:16:04', '2026-01-04 05:16:04', 'top_category', 31, NULL),
(89, 'baby hair and body wash herbal mild cleansers with baby', '1767503764-2fbf71e101.jpg', 'baby hair and body wash herbal mild cleansers with baby', 'http://127.0.0.1:8003/admin/banner.php', 1, '2026-01-04 05:16:04', '2026-01-04 05:16:04', 'top_category', 31, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `blogs`
--

CREATE TABLE `blogs` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(500) DEFAULT NULL,
  `content` mediumtext DEFAULT NULL,
  `author` varchar(150) DEFAULT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `blog_category_id` int(11) DEFAULT NULL,
  `published_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `blogs`
--

INSERT INTO `blogs` (`id`, `title`, `slug`, `meta_title`, `meta_description`, `content`, `author`, `category_id`, `author_id`, `featured_image`, `is_published`, `created_at`, `updated_at`, `blog_category_id`, `published_at`) VALUES
(27, 'Homemade ubtan remedies for dark spots', 'homemade-ubtan-remedies-for-dark-spots', 'Homemade Ubtan Remedies for Dark Spots | Ayurvedic Skin Care', 'Discover effective homemade ubtan remedies for dark spots using Ayurvedic ingredients. Natural, safe solutions for pigmentation & glowing skin.', '<p>Ubtan, Even if you have never had the opportunity to use it, you must have heard of ubtan. This is a wonderful skincare ingredient that is very commonly used in Indian households in their skincare regimen.</p>\r\n\r\n<p>From time immemorial, ubtan has been used as a cleanser. When there were no soaps to wash away dirt, people would use ubtan.</p>\r\n\r\n<p>It is a natural combination prepared with an array of herbs, spices, and other natural ingredients. The herb has been part of Ayurveda systems for its many therapeutic effects, including skin rejuvenation. Ubtan is a safe option so it is free of all harmful chemicals and retained for all skin types.</p>\r\n\r\n<h2>What is ubtan ?</h2>\r\n\r\n<p><br />\r\nUbtan constitutes a south Asian traditional skincare approach concocted from natural ingredients such as turmeric, chickpea flour, and herbs. It is used for cleaning, exfoliation, and skin brightening. It finds usage largely in India as part of the classical pre-wedding ceremonies.</p>\r\n\r\n<p>Apart from ceremonies, the benefits work wonders for many effective remedy for common skin issues like acne, pigmentation, and tanning. These properties targeted against ubiquity-B Gautam make the powder antibacterial, anti-inflammatory, and skin brightening and thus remain quite popular in India even today.</p>\r\n\r\n<h2>Turmeric and gram flour ubtan</h2>\r\n\r\n<p><br />\r\nAn everlasting ubtan recipe with turmeric and gram flour. Because of its deep-seated connection with traditional cosmetic procedures, this has been touted by modern-day aesthetics as the best ubtan for the face across the globe. Turmeric, being anti-inflammatory and brightening, and the chickpea flour, with its mild exfoliation, assist in balancing pigmentation and help the skin regain its glow.</p>\r\n\r\n<p>Ingredients:</p>\r\n\r\n<p>2 tbsp turmeric powder</p>\r\n\r\n<p>2 tbsp chickpea flour (besan)</p>\r\n\r\n<p>1 tbsp raw milk</p>\r\n\r\n<p>A few drops of rosewater</p>\r\n\r\n<p>How to use?<br />\r\nCombine the turmeric powder and chickpea flour in a bowl.<br />\r\nAdd raw milk and stir until a paste is formed.<br />\r\nAdd a few drops of rosewater for a refreshing scent.<br />\r\nApply this ubtan to your face and body, allowing it to dry for 15 to 20 minutes.<br />\r\nGently scrub it off while still waiting a bit; this serves as an exfoliation.<br />\r\nWash then with lukewarm water.</p>\r\n\r\n<h2><br />\r\nSandalwood and milk ubtan</h2>\r\n\r\n<p><br />\r\nSandalwood is famous for its cooling and calming effects. It lightens discolorations and redness. In contrast, milk nourishes and softens the skin.</p>\r\n\r\n<p>Ingredients:<br />\r\n1 tablespoon sandalwood powder<br />\r\nMilk</p>\r\n\r\n<p>How to use:<br />\r\nTake 1 tablespoon sandalwood powder.<br />\r\nAdd sufficient milk so as to make a creamy paste.<br />\r\nApply on the face and neck. Allow to dry on its own.<br />\r\nRinse with lukewarm water for skin that is clear, refreshed, and healthy looking.</p>\r\n\r\n<h2><br />\r\nTurmeric and yogurt ubtan</h2>\r\n\r\n<p><br />\r\nAn ubtan made with turmeric and yogurt is capable of healing and brightening the skin. Turmeric lightens and heals through its antimicrobial and anti-inflammatory properties, whereas yogurt&#39;s lactic acid gently exfoliates by removing dead skin and impurities.</p>\r\n\r\n<p>Ingredients:<br />\r\n1 tablespoon turmeric powder<br />\r\n2 tablespoon Plain yogurt</p>\r\n\r\n<p>How to use:<br />\r\nMix 1 tbsp of turmeric powder with 2 tbsp of plain yogurt to form a thick paste.<br />\r\nApply the mixture to your face and leave it on for at least 20 minutes.<br />\r\nRinse off</p>\r\n\r\n<h2><br />\r\nNeem and Tulsi ubtan</h2>\r\n\r\n<p><br />\r\nUbtan ingredients are always carefully chosen to make the skin glowing and healthy; however, Neem and Tulsi Ubtan is famous for maximizing benefits.</p>\r\n\r\n<p>Ingredients:<br />\r\nA handful of neem leaves<br />\r\nA handful of fresh tulsi (basil) leaves<br />\r\n1 tablespoon of multani mitti (fuller&#39;s earth)</p>\r\n\r\n<p>How to use:<br />\r\nCrush the neem and tulsi leaves into a paste.<br />\r\nAdd multani mitti to this paste.<br />\r\nApply this ubtan on your skin for about 15-20 minutes until it dries out.<br />\r\nWash off with lukewarm water.</p>\r\n\r\n<h2><br />\r\nNeem and Multani Mitti ubtan</h2>\r\n\r\n<p><br />\r\nThe traditional remedy of Neem and Multani Mitti Ubtan has been famous since time immemorial. It uses the powerful action of neem and Multani mitti&nbsp;&nbsp;to keep the skin healthy and glowing.</p>\r\n\r\n<p>Neem acts as an antibacterial and anti-inflammatory agent, thereby preventing acne and a host of other skin problems. Multani mitti acts as a wonderful exfoliant, taking away the dirt and excess oil from the skin.</p>\r\n\r\n<p>Ingredients:<br />\r\n1 tablespoon of neem powder<br />\r\n1 tablespoon of multani mitti (Fuller&#39;s Earth)<br />\r\nA teaspoon of lemon juice<br />\r\nWater</p>\r\n\r\n<p>How to use:<br />\r\nCombine neem powder and multani mitti.<br />\r\nAdd lemon juice and enough water to form a paste.<br />\r\nApply, keep dry for 15 minutes, then rinse.</p>\r\n\r\n<h2><br />\r\nSaffron and Almond Ubtan</h2>\r\n\r\n<p><br />\r\nSaffron and Almond Ubtan is an age-old beauty ritual, which has been valued generation after generation. This majestic mixture brings together the luxury of saffron and the moisturizing abilities of almonds to form a powerful mixture that goes beyond skin care; it is a ritual of self-love and renewal.</p>\r\n\r\n<p>Embracing the ancient secrets of beauty rituals, this fair skin ubtan becomes a sign of ageless beauty, which honors the beauty that does not obey the rules of generations.</p>\r\n\r\n<p>Ingredients:<br />\r\n5-6 soaked almonds overnight<br />\r\nA few strands of saffron<br />\r\n1 tablespoon honey</p>\r\n\r\n<p>How to use:<br />\r\nPuree soaked almonds into a smooth paste.<br />\r\nAdd honey and saffron strands to the paste.<br />\r\nApply this ubtan and massage it on your skin gently.<br />\r\nLeave it for 15-20 minutes and wash with lukewarm water.</p>\r\n\r\n<p>Disclaimer:<br />\r\nAyurvedic principles shared in this blog are based on traditional knowledge and general practices. They are not a substitute for professional medical advice. Individual skin and hair types may respond differently. Always consult a qualified practitioner for personalized guidance.</p>\r\n', NULL, NULL, 2, '/assets/uploads/blogs/blog_693d1cb489123.jpg', 1, '2025-12-13 07:58:44', '2025-12-13 08:55:19', 8, '2025-12-13 13:28:00'),
(28, 'Vatyalaka ( Sida cordifolia)', 'vatyalaka-sida-cordifolia-', 'Sida Cordifolia: Why Ayurveda Recommends Bala for Radiant Sk', 'bala ayurvedic herbs potent Rasayana is essential for soothing inflammation, balancing Vata and Pitta, and achieving naturally glowing, resilient skin.', '<p>Here are some other names of Vatyalaka (Bala) &ndash; Sida cordifolia,<br />\r\n1. Sanskrit &ndash; Bala, Vatyalaka<br />\r\n2. Hindi &ndash; Bariyar, Khareti<br />\r\n3. Tamil &ndash; Kurunthotti<br />\r\n4. Telugu &ndash; Chittamuttivittulu<br />\r\n5. Kannada &ndash; Hettale<br />\r\n6. Malayalam &ndash; Kurunthotti<br />\r\n7. Marathi &ndash; Kharenti<br />\r\n8. Gujarati &ndash; Khapeti<br />\r\n9. Bengali &ndash; Berela<br />\r\n10. Odia &ndash; Balaa</p>\r\n\r\n<h2><br />\r\nWhat is &nbsp;Vatyalaka(Bala)?</h2>\r\n\r\n<p><img alt=\"Image\" src=\"/assets/uploads/media/fbbafab2549643cbac3385d9f9e93531_Furniture_Sale_Instagram_stories_template.png\" style=\"height:auto; margin:10px 0; max-width:100%\" /></p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>Bala is indeed a valuable plant as far as the shukrala qualities are concerned, as they are said to improve both the quality and quantity of sperm count. This classical herbal medication mainly acts as Central Nervous system depressant, analgesic, anti-inflammatory, hypotensive and hypoglycaemic agent. The best nerve tonic for numerous vata disorders, blood purification, prompt healing from piles, and fetal growth and development advantages are said to have been bestowed by this herb. Other common names include country mallow, and hearty -leaf sida.</p>\r\n\r\n<h2>For urinary problems:</h2>\r\n\r\n<p><br />\r\nAs one of the chief natural diuretics, Bala works directly to increase urine output and even acts to cure a number of kidney disorders. Mainly, it is applied with the action of other natural herbs for a cure against urinary tract infections as well as an enhancer to the functions of the kidneys. The amazing herbal weed also substantially helps strengthen the system and cure about urinary incontinence.</p>\r\n\r\n<h2><br />\r\nRespiratory Disorders:</h2>\r\n\r\n<p><br />\r\nIts role has been recognized in treating asthma, bronchitis, common cold, nasal obstruction, sinus, cough, and wheezing. It also helps with the relief of chest congestion.</p>\r\n\r\n<h2><br />\r\nDigestive Problems:</h2>\r\n\r\n<p><br />\r\nBala helps in the absorption of water and nutrients from the intestines and controls motility of the large intestine. The herb thus finds its place in the treatment of Irritable Bowel Syndrome (IBS).</p>\r\n\r\n<h2><br />\r\nWeight loss:</h2>\r\n\r\n<p><br />\r\nAs per some health practitioners, this herb can help awaken the central nervous system. This in turn will stimulate the pulse and raise blood pressure. Gradually the metabolic rate of the body will increase which will help in shedding off those extra kilos. But no scientific evidence validates it yet.</p>\r\n\r\n<h2><br />\r\nAnti-Inflammatory Effects:&nbsp;</h2>\r\n\r\n<p><br />\r\nThe herbal medicine is said to possess very strong anti inflammatory and analgesic traits, which are of tremendous importance in easing the symptoms for arthritis like joint and muscle pain, overcoming stiffens and improved mobility. Application of a paste made of powder of Bala directly on the affected area is relieving the associated inflammation and thus provides relief from arthritis and gout.<br />\r\nSupport Immune Function:&nbsp;<br />\r\nGreat Immune boosting properties of Bala make it one of the most coveted herbs for health and illness prevention. The natural mechanisms function better as this herb promotes the wellness of one&#39;s immune system and increases the immunise ability of the body to penetrate germs and diseases. Immunomodulatory effects of Bala lead to the regulation and strengthening of the immune system to optimally function. This would prove especially beneficial in periods of enhanced stress or recovery following an illness.</p>\r\n\r\n<h2>Skincare Benefits&nbsp;</h2>\r\n\r\n<p><br />\r\nBala is good for the skin. Strongly active antiinflammatory and antioxidant properties can heal skin disorders of eczema and psoriasis. Further, regenerates skin and also diminishes scar and wound marks.<br />\r\n&bull;&nbsp;&nbsp; &nbsp;Bala can be incorporated into skin care formulations to soothe away irritations while improving the overall skin texture.&nbsp;<br />\r\n&bull;&nbsp;&nbsp; &nbsp;In the olden days, bali was used for beauty enhancement among kings and queens. It is a very effective agent making them beautiful&nbsp;<br />\r\n&bull;&nbsp;&nbsp; &nbsp;It is generally given with Ashoka and Shatavari for good results.&nbsp;<br />\r\n&bull;&nbsp;&nbsp; &nbsp;It is used in Urticaria, Abscesses, boil, cysts, buboes, and ulcers.&nbsp;</p>\r\n\r\n<p>Healthy Skin:<br />\r\nBala herb is packed with antioxidants and other important nutrients which restores, revives, and repairs skin and hair health. It has historically been known as beautifying herb. This traditional herbal formulation can be taken as internal and bent on external application to richer nourishment of the skin and inward healing. Regular intake of Bala enhances hair growth and strengthens the scalp while oil can also serve as a natural hair conditioner.</p>\r\n\r\n<h2>Haircare Benefits&nbsp;</h2>\r\n\r\n<p>Rich in antioxidants and other nutrients that bring luster to hair and glow to skin, Bala finds mention as an important beautifying herb in ancient texts. Whether applied directly on the skin or ingested, Bala acts as an essential nutrient for an even-toned complexion.<br />\r\nRegular usage of Bala can promote hair growth and strength, while Bala oil can be directly applied to hair for deep conditioning. It is also an excellent appetizer.<br />\r\nStrengthens Hair Roots :<br />\r\nBala is known traditionally to nourish and strengthen hair follicles, thus decreasing hair loss.<br />\r\nPrevents Dryness:<br />\r\nBala is believed to have hydrating properties, which may help retain moisture in the scalp and hair.<br />\r\nReduces Scalp Inflammation:<br />\r\nThe anti-inflammatory properties of Bala can help soothe irritated and inflamed scalp.</p>\r\n\r\n<p><strong>Disclaimer:</strong><br />\r\nAll content on this website is for informational purposes only and does not constitute medical advice.</p>\r\n\r\n<div style=\"display: inline-block; width: 80%; max-width: 100%; resize: both; overflow: hidden; border: 1px dashed #ccc; vertical-align: top; margin: 10px 0;\">\r\n<video controls=\"\" src=\"/assets/uploads/media/6d8b5136630444888c993cbe87fafc7b_men_useing_eyebrow_growth_oil.mp4\" style=\"width: 100%; height: auto; display: block;\">&nbsp;</video>\r\n</div>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p><br />\r\n<br />\r\n&nbsp;</p>\r\n', NULL, NULL, 2, '/assets/uploads/media/f1af4b6cb83947a9aa76ff41d6b82c8d_Vatyalaka_Sida_cordifolia_The_Ayurvedic_Herb_of_Strength_and_Skin_Vitality_copy.jpg', 1, '2025-12-13 11:21:15', '2025-12-28 12:46:06', 7, '2025-12-13 16:51:00');

-- --------------------------------------------------------

--
-- Table structure for table `blog_categories`
--

CREATE TABLE `blog_categories` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `blog_categories`
--

INSERT INTO `blog_categories` (`id`, `title`, `slug`, `description`, `created_at`, `updated_at`) VALUES
(5, 'Ayurvedic Beauty Wisdom', 'ayurvedic-beauty-wisdom', 'Discover the timeless secrets of Ayurveda for radiant skin, healthy hair, and overall well-being. This category explores the core principles of Ayurvedic beauty, including Dosha-based routines, traditional rituals, and herbal remedies. Learn how to integrate natural ingredients, holistic self-care practices, and age-old wellness techniques into your modern beauty routine. Perfect for those seeking authentic, natural, and sustainable approaches to enhance their beauty from within.', '2025-12-13 03:36:23', '2025-12-13 03:36:23'),
(6, 'Skincare. Hair Care  & Product Education', 'skincare-hair-care', 'Take care of your skin, hair, and eyebrows naturally with Ayurvedic wisdom. This category focuses on practical routines, seasonal care, and remedies for common concerns like hair fall, slow growth, acne, dry or oily skin, and anti-aging. Explore tips, treatments, and rituals that combine traditional Ayurveda with modern beauty solutions to achieve healthy, glowing skin and strong, lustrous hair.', '2025-12-13 03:41:32', '2025-12-13 04:16:20'),
(7, 'Ingredient Spotlight', 'ingredient-spotlight-product-education', 'Unlock the power of nature with herbs and Ayurvedic formulations. This category highlights the benefits, uses, and safety of natural ingredients like Yastimadhu, Amla, Brahmi, and more. Learn how to use DevElixir products effectively, discover their unique Ayurvedic formulations, and see real results. Ideal for readers who want to understand what goes into their beauty products and how to maximize their benefits.in Ayurvedic tradition.', '2025-12-13 03:42:22', '2025-12-13 04:16:35'),
(8, 'DIY, Home Remedies & Lifestyle', 'diy-home-remedies-lifestyle', 'Bring Ayurveda into your daily life with easy, natural remedies and wellness tips. This category offers DIY beauty recipes, home remedies, and lifestyle advice to support skin, hair, and overall well-being. Learn how diet, sleep, stress management, and seasonal routines can enhance your natural beauty while embracing holistic self-care practices rooted in Ayurvedic tradition.', '2025-12-13 03:43:17', '2025-12-13 03:43:17'),
(9, 'Video', 'video', 'Experience Ayurveda in motion with our curated video content. From expert-guided skincare and hair care tutorials to DIY remedies, ingredient spotlights, and product demonstrations, our video blogs bring natural beauty to life. Learn step-by-step Ayurvedic routines, discover the benefits of herbal ingredients, and watch timeless beauty rituals being practiced, all designed to help you achieve radiant skin, healthy hair, and holistic self-care.', '2025-12-13 04:01:19', '2025-12-13 04:01:19'),
(10, 'Audio', 'audio', 'Immerse yourself in the world of Ayurvedic beauty and wellness through our audio content. From expert discussions on skincare, hair, and eyebrow care to DIY remedies, ingredient insights, and product education, our audio blogs guide you step-by-step to naturally radiant skin and healthy hair. Perfect for learning on-the-go, these podcasts and audio guides help you integrate Ayurvedic practices, holistic self-care routines, and natural beauty rituals into your daily life.', '2025-12-13 04:02:30', '2025-12-13 04:02:30');

-- --------------------------------------------------------

--
-- Table structure for table `blog_post_tags`
--

CREATE TABLE `blog_post_tags` (
  `id` int(10) UNSIGNED NOT NULL,
  `blog_id` int(10) UNSIGNED NOT NULL,
  `tag_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci COMMENT='Many-to-many relationship between blogs and tags';

--
-- Dumping data for table `blog_post_tags`
--

INSERT INTO `blog_post_tags` (`id`, `blog_id`, `tag_id`, `created_at`) VALUES
(86, 27, 12, '2025-12-13 08:55:19'),
(87, 27, 10, '2025-12-13 08:55:19'),
(88, 27, 9, '2025-12-13 08:55:19'),
(89, 27, 13, '2025-12-13 08:55:19'),
(90, 27, 22, '2025-12-13 08:55:19'),
(91, 27, 11, '2025-12-13 08:55:19'),
(92, 27, 16, '2025-12-13 08:55:19');

-- --------------------------------------------------------

--
-- Table structure for table `blog_related`
--

CREATE TABLE `blog_related` (
  `id` int(10) UNSIGNED NOT NULL,
  `blog_id` int(10) UNSIGNED NOT NULL COMMENT 'The main blog post ID',
  `related_blog_id` int(10) UNSIGNED NOT NULL COMMENT 'The related blog post ID',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci COMMENT='Stores manually selected related articles for blog posts';

-- --------------------------------------------------------

--
-- Table structure for table `blog_tags`
--

CREATE TABLE `blog_tags` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL COMMENT 'Full description displayed on tag archive page',
  `seo_title` varchar(60) DEFAULT NULL COMMENT 'SEO optimized title tag (max 60 chars)',
  `seo_description` varchar(160) DEFAULT NULL COMMENT 'Meta description for search engines (max 160 chars)',
  `seo_image` varchar(255) DEFAULT NULL COMMENT 'Open Graph image for social sharing',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci COMMENT='Blog tags with SEO metadata for tag archive pages';

--
-- Dumping data for table `blog_tags`
--

INSERT INTO `blog_tags` (`id`, `name`, `slug`, `description`, `seo_title`, `seo_description`, `seo_image`, `created_at`, `updated_at`) VALUES
(9, 'homemade ubtan', 'homemade-ubtan', '', '', '', '', '2025-12-13 06:29:33', '2025-12-13 06:29:33'),
(10, 'dark spots', 'dark-spots', '', '', '', '', '2025-12-13 06:29:53', '2025-12-13 06:29:53'),
(11, 'pigmentation', 'pigmentation', '', '', '', '', '2025-12-13 06:30:12', '2025-12-13 06:30:12'),
(12, 'ayurvedic skincare', 'ayurvedic-skincare', '', '', '', '', '2025-12-13 06:30:26', '2025-12-13 06:30:26'),
(13, 'natural remedies', 'natural-remedies', '', '', '', '', '2025-12-13 06:30:45', '2025-12-13 06:30:45'),
(15, 'herbal ubtan', 'herbal-ubtan', '', '', '', '', '2025-12-13 06:31:37', '2025-12-13 06:31:37'),
(16, 'skin brightening', 'skin-brightening', '', '', '', '', '2025-12-13 06:32:09', '2025-12-13 06:32:09'),
(17, 'Ayurveda', 'ayurveda', '', '', '', '', '2025-12-13 08:15:52', '2025-12-13 08:15:52'),
(18, 'Dosha (Vata, Pitta, Kapha)', 'dosha-vata-pitta-kapha-', '', '', '', '', '2025-12-13 08:17:04', '2025-12-13 08:17:04'),
(19, 'Traditional Beauty Rituals', 'traditional-beauty-rituals', '', '', '', '', '2025-12-13 08:17:22', '2025-12-13 08:17:22'),
(20, 'Holistic Skincare', 'holistic-skincare', '', '', '', '', '2025-12-13 08:17:39', '2025-12-13 08:17:39'),
(21, 'Herbal Wisdom', 'herbal-wisdom', '', '', '', '', '2025-12-13 08:17:52', '2025-12-13 08:17:52'),
(22, 'Natural Self-Care', 'natural-self-care', '', '', '', '', '2025-12-13 08:18:03', '2025-12-13 08:18:03'),
(23, 'Timeless Beauty Practices', 'timeless-beauty-practices', '', '', '', '', '2025-12-13 08:18:11', '2025-12-13 08:18:11');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`, `created_at`, `updated_at`) VALUES
(14, 2, 16, 1, '2025-12-04 07:37:48', '2025-12-04 07:37:48'),
(19, 4, 33, 1, '2025-12-14 07:53:01', '2025-12-14 07:53:01'),
(20, 4, 44, 4, '2025-12-31 06:02:28', '2026-01-06 15:59:32'),
(21, 4, 43, 2, '2025-12-31 06:02:30', '2026-01-06 15:55:57'),
(22, 4, 42, 1, '2025-12-31 06:02:31', '2025-12-31 06:02:31'),
(23, 4, 31, 1, '2025-12-31 06:02:33', '2025-12-31 06:02:33');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `title` varchar(255) DEFAULT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `faqs` text DEFAULT NULL,
  `media_gallery` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `created_at`, `title`, `parent_id`, `description`, `image`, `meta_title`, `meta_description`, `faqs`, `media_gallery`) VALUES
(31, 'Skin Care', 'skin-care', '2025-12-21 20:38:42', 'Skin Care', NULL, '<h1>Skincare Mistakes To Avoid&nbsp;</h1>\r\n\r\n<p><br />\r\nSkincare at times could be an exhilarating journey---especially so during your 20s, when you are attempting to set up a life. I am not always a dermatologist-Gosh, I&#39;ve made all the classic skincare mistakes. So, for those in their 20s who want to produce a stronger skincare regimen, take note of the things I forbade myself from doing.<br />\r\nThe five biggest incompatibilities I&#39;d had with skincare, plus a huge myth once followed:</p>\r\n\r\n<h2><br />\r\n1. Not removing Kajal before bed</h2>\r\n\r\n<p><br />\r\nI mostly used to skip removing makeup-well, kajal systems are different. I went for a wash-basically assuming that was okay. Kajal, being long-lasting in nature, usually has oil-base components that don&#39;t wash off easily with water, leaving behind residues.<br />\r\nThe residues cause irritation, redness, and styes-to name a few.<br />\r\nInflammation also leads to premature aging.<br />\r\nUse cold-pressed coconut oil or sweet almond oil on a cotton swab to gently wipe off all eye makeup since these are both natural, gentle, and moisturizing.<br />\r\n2. Using foaming face wash with dry skin<br />\r\nI used to think that the more foam it had, the cleaner my skin. That was a big mistake. Foaming cleansers usually contain sulfates that strip away the natural oils. This aggravates dryness.<br />\r\nUse a homemade cleanser of raw honey with a few drops of milk or rose water. It offers cleansing without drying moisture and suits dry skin.<br />\r\n3. Not Moisturizing right after Washing My Face<br />\r\nI once used to feel moisturizing at night once was enough. But applying moisturizer over damp skin keeps hydration trapped better.<br />\r\nSkin loses water fast once washed.<br />\r\nDelaying moisturizer means losing a bit of its power.<br />\r\nApply some aloe vera gel or shea butter right after washing your face while it is still slightly damp. They help seal moisture and soothe dryness naturally.<br />\r\n4. Using Expensive Creams by the Pinch<br />\r\nBack in the days, I used to buy expensive creams and use them very sparingly, with mere dots of creams spread once a week. I thought it would make them last, with little use, yet still be effective. They were not.<br />\r\n&bull;&nbsp;&nbsp; &nbsp;If applied from time to time, results will not show.<br />\r\n&bull;&nbsp;&nbsp; &nbsp;Inconsistent use makes no sense.<br />\r\n&bull;&nbsp;&nbsp; &nbsp;Aloe vera gel and a few drops of jojoba or argan oil.<br />\r\n&bull;&nbsp;&nbsp; &nbsp;Whipped shea butter infused with lavender oil.<br />\r\nUsing natural well-priced products on a regular basis is much better than infrequent usage of expensive ones.<br />\r\n5. Not Reapplying Inner Block Hours<br />\r\nIn my pre-dermatology days, I would have thought a single application of sunscreen in the morning was enough. No it&#39;s not&nbsp;<br />\r\nUV protection fades away after 2&ndash;3 hours.&nbsp;<br />\r\nNo reapplications mean no real protection from the sun.&nbsp;<br />\r\nUse a natural mineral sunscreen made of zinc oxide and non-nano titanium dioxide. Reapply every 2&ndash;3 hours. Otherwise, if there is a window at your workplace, you are going to get sun exposure there!<br />\r\nThe fairness creams<br />\r\nYes-I really thought those things could lighten my skin permanently. &nbsp;According to what I now know, no cream can do that safely and permanently. Most fairness products will dry your skin out. They offer a very narrow beauty stereotype. You need healthy, glowing skin-not lighter skin.<br />\r\nWhat actually helps:<br />\r\nHydration And Sun protection<br />\r\nA wholesome skin routine with natural, beneficial materials<br />\r\nYour twenties may be wild learning years, but your skin need not suffer any damage.&nbsp;<br />\r\nAvoid these silly things I did:<br />\r\n&bull;&nbsp;&nbsp; &nbsp;Always remove makeup.<br />\r\n&bull;&nbsp;&nbsp; &nbsp;Use the right products for your skin.<br />\r\n&bull;&nbsp;&nbsp; &nbsp;Moisturize right after cleansing.<br />\r\n&bull;&nbsp;&nbsp; &nbsp;Use plenty of skincare products.<br />\r\nForget fairness creams!</p>', 'assets/uploads/media/ebbe7188bc5143bfbd04889b6c4a5422_Rooted_in_Ayurveda__Crafted_with_Integrity.jpg', 'natural skincare is essential for beauty skin', '', '[{\"q\":\"How can normal skin be maintained?\",\"a\":\"\\u2022\\tDaily cleansing: Cleanse gently twice a day to remove dirt and impurities.\\r\\n\\u2022\\tMoisturization: Use antioxidant-rich moisturizers during the day and hydrating creams at night.\\r\\n\\u2022\\tSun protection: Apply sunscreen regularly to prevent UV damage.\\r\\n\\u2022\\tOccasional exfoliation: Remove dead skin cells to maintain a healthy glow.\"}]', '[\"\\/assets\\/uploads\\/media\\/d00b7c7921904f6f957bf92ffd604169_skin_care__develixir_png.png\",\"gallery-1767337200-89dd4bc2-0.jpg\",\"gallery-1767337200-3c9d3354-1.jpg\",\"gallery-1767337200-9c727940-2.jpg\",\"gallery-1767337200-19461ff0-3.jpg\",\"gallery-1767337200-9c40d8a4-4.jpg\"]'),
(32, 'Hair Care', 'hair-care', '2025-12-21 20:38:54', 'Hair Care', NULL, '', 'assets/uploads/media/018e078387184fcb8ced317609c2d05c_hair_care__develixir_png.png', '', '', NULL, NULL),
(33, 'Personal Care', 'personal-care', '2025-12-21 20:39:12', 'Personal Care', NULL, '', 'assets/uploads/media/5f8a473c73db4da189cd4ce4117302fc_Untitled-1_model_banner.jpg', '', '', NULL, NULL),
(34, 'Baby Care', 'baby-care', '2025-12-21 20:39:26', 'Baby Care', NULL, '', 'assets/uploads/media/f271efed2a6f4a988743310b0f02187a_triphala_fruits_image.jfif', '', '', NULL, NULL),
(35, 'Wellness', 'wellness', '2025-12-21 20:39:38', 'Wellness', NULL, '', 'assets/uploads/media/0aa1dc83553f4652ad58a5608dd3218b_anitdandruff_oil_ingredients_2.jpg', '', '', NULL, NULL),
(36, 'Hair Wash Powders / Shampoo', 'hair-care-hair-wash-powders-shampoo', '2025-12-21 20:46:15', 'Hair Wash Powders / Shampoo', 32, '', NULL, '', '', NULL, NULL),
(38, 'Hair Oils', 'hair-care-hair-oils', '2025-12-21 20:46:53', 'Hair Oils', 32, '', NULL, '', '', NULL, NULL),
(39, 'Hair Masks / Packs', 'hair-care-hair-masks-packs', '2025-12-21 20:47:20', 'Hair Masks / Packs', 32, '', NULL, '', '', NULL, NULL),
(40, 'Scalp Care', 'hair-care-scalp-care', '2025-12-21 20:47:58', 'Scalp Care', 32, '', NULL, '', '', NULL, NULL),
(41, 'Eye Care', 'eye-care', '2025-12-21 20:57:03', 'Eye Care', NULL, '', 'assets/uploads/categories/1763959838-c8778da95d.jpg', '', '', NULL, NULL),
(42, 'Eye Kajal', 'eye-care-eye-kajal', '2025-12-21 20:57:57', 'Eye Kajal', 41, '', NULL, '', '', NULL, NULL),
(43, 'Eyebrow Oil', 'eye-care-eyebrow-oil', '2025-12-21 20:58:25', 'Eyebrow Oil', 41, '', NULL, '', '', NULL, NULL),
(44, 'Under Eye Care', 'eye-care-under-eye-care', '2025-12-21 20:58:41', 'Under Eye Care', 41, '', NULL, '', '', NULL, NULL),
(45, 'Face Powders / Ubtan', 'skin-care-face-powders-ubtan', '2025-12-21 20:59:39', 'Face Powders / Ubtan', 31, '', NULL, '', '', NULL, NULL),
(46, 'Face Oils & Creams', 'skin-care-face-oils-creams', '2025-12-21 21:00:03', 'Face Oils & Creams', 31, '', '1766500543-e42f44ba11.png', '', '', NULL, NULL),
(47, 'Face Gels & Face Masks / Packs', 'skin-care-face-gels-face-masks-packs', '2025-12-21 21:00:24', 'Face Gels & Face Masks / Packs', 31, '', 'assets/uploads/media/0248ecf2a651479a8269fdbbaa3dc26c_dev-elixir-kadhira-tooth-powder-ingredients-1.jpg', '', '', NULL, NULL),
(48, 'Herbal Cleansers', 'skin-care-herbal-cleansers', '2025-12-21 21:00:47', 'Herbal Cleansers', 31, '', NULL, '', '', NULL, NULL),
(49, 'Face Toner & Mist', 'skin-care-face-toner-mist', '2025-12-21 21:01:09', 'Face Toner & Mist', 31, '', NULL, '', '', NULL, NULL),
(50, 'Lip Care', 'skin-care-lip-care', '2025-12-21 21:01:43', 'Lip Care', 31, '', NULL, '', '', NULL, NULL),
(51, 'Herbal Bath Powder', 'bath-body-herbal-bath-powder', '2025-12-21 21:10:59', 'Herbal Bath Powder', 33, '', NULL, '', '', NULL, NULL),
(52, 'Body Cleanser', 'bath-body-body-cleanser', '2025-12-21 21:11:23', 'Body Cleanser', 33, '', NULL, '', '', NULL, NULL),
(53, 'Body Scrub', 'bath-body-body-scrub', '2025-12-21 21:11:44', 'Body Scrub', 33, '', NULL, '', '', NULL, NULL),
(54, 'Body Mask', 'bath-body-body-mask', '2025-12-21 21:12:15', 'Body Mask', 33, '', NULL, '', '', NULL, NULL),
(55, 'Body Oil', 'bath-body-body-oil', '2025-12-21 21:12:37', 'Body Oil', 33, '', NULL, '', '', NULL, NULL),
(56, 'Body Butter / Cream', 'bath-body-body-butter-cream', '2025-12-21 21:13:01', 'Body Butter / Cream', 33, '', NULL, '', '', NULL, NULL),
(57, 'Foot Care', 'bath-body-foot-care', '2025-12-21 21:13:20', 'Foot Care', 33, '', NULL, '', '', NULL, NULL),
(58, 'Hair Serum / Hair Tonic', 'hair-care-hair-serum-hair-tonic', '2025-12-21 21:15:39', 'Hair Serum / Hair Tonic', 32, '', NULL, '', '', NULL, NULL),
(59, 'Oral Care', 'personal-care-oral-care', '2025-12-21 21:22:08', 'Oral Care', 33, '', NULL, '', '', NULL, NULL),
(60, 'Baby Oral Care', 'baby-care-baby-oral-care', '2025-12-21 21:24:59', 'Baby Oral Care', 34, '', NULL, '', '', NULL, NULL),
(61, 'Baby Bath Powder/Ubtan', 'baby-care-baby-bath-powder-ubtan', '2025-12-21 21:27:11', 'Baby Bath Powder/Ubtan', 34, '', NULL, '', '', NULL, NULL),
(62, 'Baby Massage / Body Oil', 'baby-care-baby-massage-body-oil', '2025-12-21 21:27:40', 'Baby Massage / Body Oil', 34, '', NULL, '', '', NULL, NULL),
(63, 'Baby Lotion / Cream', 'baby-care-baby-lotion-cream', '2025-12-21 21:28:16', 'Baby Lotion / Cream', 34, '', NULL, '', '', NULL, NULL),
(64, 'Baby Hair Wash / Cleanser', 'baby-care-baby-hair-wash-cleanser', '2025-12-21 21:29:33', 'Baby Hair Wash / Cleanser', 34, '', NULL, '', '', NULL, NULL),
(65, 'Baby Body Washes', 'baby-care-baby-body-washes', '2025-12-21 21:30:30', 'Baby Body Washes', 34, '', NULL, '', '', NULL, NULL),
(66, 'Herbal Oils (Therapeutic)', 'wellness-herbal-oils-therapeutic', '2025-12-21 21:33:14', 'Herbal Oils (Therapeutic)', 35, '', NULL, '', '', NULL, NULL),
(67, 'Herbal Powders (Wellness)', 'wellness-herbal-powders-wellness', '2025-12-21 21:33:34', 'Herbal Powders (Wellness)', 35, '', NULL, '', '', NULL, NULL),
(68, 'Pain Relief Oils', 'wellness-pain-relief-oils', '2025-12-21 21:33:54', 'Pain Relief Oils', 35, '', NULL, '', '', NULL, NULL),
(69, 'Massage Oils', 'wellness-massage-oils', '2025-12-21 21:34:23', 'Massage Oils', 35, '', NULL, '', '', NULL, NULL),
(70, 'Traditional Remedies', 'wellness-traditional-remedies', '2025-12-21 21:35:05', 'Traditional Remedies', 35, '', NULL, '', '', NULL, NULL),
(71, 'testing', 'personal-care-testing', '2025-12-23 04:33:30', 'testing', 33, '', NULL, '', '', NULL, NULL),
(72, 'testing2', 'personal-care-testing2', '2025-12-23 04:33:46', 'testing2', 33, '', NULL, '', '', NULL, NULL),
(73, 'testing3', 'personal-care-testing3', '2025-12-23 04:34:03', 'testing3', 33, '', NULL, '', '', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_type` enum('percentage','flat') NOT NULL DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL,
  `max_discount_limit` decimal(10,2) DEFAULT NULL,
  `min_purchase` decimal(10,2) DEFAULT NULL,
  `offer_type` enum('first_user','cart_value','festival','product_specific','category_specific','universal') NOT NULL DEFAULT 'universal',
  `usage_limit_per_user` enum('once','unlimited') NOT NULL DEFAULT 'once',
  `can_be_clubbed` tinyint(1) NOT NULL DEFAULT 0,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `coupons`
--

INSERT INTO `coupons` (`id`, `title`, `code`, `description`, `discount_type`, `discount_value`, `max_discount_limit`, `min_purchase`, `offer_type`, `usage_limit_per_user`, `can_be_clubbed`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 'First order bonus', 'DEVOKK9J79HQG', '', 'percentage', 10.00, NULL, NULL, 'first_user', 'once', 0, '2025-12-01 09:43:00', '2026-02-01 09:43:00', 'active', '2025-12-01 04:13:39', '2025-12-01 04:13:39'),
(2, 'Dewali offer', 'DEVEPQBMKDOL04', '', 'percentage', 20.00, NULL, 5000.00, 'universal', 'unlimited', 1, '2025-12-01 13:41:00', '2026-11-20 13:41:00', 'active', '2025-12-01 08:11:41', '2025-12-01 08:11:41');

-- --------------------------------------------------------

--
-- Table structure for table `coupon_categories`
--

CREATE TABLE `coupon_categories` (
  `coupon_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coupon_products`
--

CREATE TABLE `coupon_products` (
  `coupon_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coupon_usage`
--

CREATE TABLE `coupon_usage` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `coupon_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED DEFAULT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `used_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `coupon_usage`
--

INSERT INTO `coupon_usage` (`id`, `coupon_id`, `user_id`, `order_id`, `discount_amount`, `used_at`) VALUES
(1, 1, 2, 9, 39.90, '2025-12-03 01:53:13'),
(2, 2, 2, 15, 1440.00, '2025-12-03 06:55:49'),
(3, 1, 4, 16, 360.00, '2025-12-05 02:36:51');

-- --------------------------------------------------------

--
-- Table structure for table `filter_groups`
--

CREATE TABLE `filter_groups` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `param_key` varchar(50) NOT NULL,
  `column_name` varchar(50) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `filter_groups`
--

INSERT INTO `filter_groups` (`id`, `category_id`, `name`, `param_key`, `column_name`, `sort_order`, `is_active`) VALUES
(3, 34, 'PRODUCT TYPE', 'PRODUCT TYPE', 'category_name', 1, 1),
(6, 32, 'INGREDIENTS', 'Ingredients', 'ingredients', 1, 1),
(8, 41, 'CONCERN / BENEFIT', 'CONCERN / BENEFIT', 'category_name', 1, 1),
(9, 34, 'Baby Care', 'variant', 'variants', 1, 1),
(10, 41, 'Eye Care', 'Ingredients', 'sku', 3, 1);

-- --------------------------------------------------------

--
-- Table structure for table `filter_group_values`
--

CREATE TABLE `filter_group_values` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `value` varchar(100) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `filter_options`
--

CREATE TABLE `filter_options` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `value` varchar(100) NOT NULL,
  `label` varchar(100) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `filter_options`
--

INSERT INTO `filter_options` (`id`, `group_id`, `value`, `label`, `sort_order`, `is_active`) VALUES
(11, 3, 'Hair Care', 'Hair Wash Powder', 1, 1),
(12, 3, 'Hair Care', 'Hair Oil', 0, 1),
(13, 8, 'Hair Care', 'Hair Fall Control', 1, 1),
(15, 8, 'Hair Care', 'Hair Growth', 0, 1),
(16, 8, 'Hair Care', 'Dandruff Care', 1, 1),
(17, 6, 'Hair Care', 'Bhringraj', 0, 1),
(18, 6, 'Hair Care', 'Neeli', 1, 1),
(19, 6, 'Hair Care', 'Ghee', 1, 1),
(20, 10, 'KDTP-01', 'concern', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `homepage_products`
--

CREATE TABLE `homepage_products` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `section` enum('best_seller','sale','top_rated','trendy') NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `homepage_products`
--

INSERT INTO `homepage_products` (`id`, `product_id`, `section`, `sort_order`) VALUES
(45, 36, 'sale', 1),
(46, 35, 'sale', 2),
(47, 33, 'sale', 3),
(48, 31, 'sale', 4),
(49, 29, 'sale', 5),
(50, 44, 'top_rated', 1),
(51, 43, 'top_rated', 2),
(52, 42, 'top_rated', 3),
(53, 31, 'top_rated', 4),
(54, 29, 'top_rated', 5),
(55, 44, 'best_seller', 1),
(56, 43, 'best_seller', 2),
(57, 31, 'best_seller', 3),
(58, 42, 'trendy', 1),
(59, 31, 'trendy', 2),
(60, 29, 'trendy', 3);

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_number` varchar(64) NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('issued','cleared') NOT NULL DEFAULT 'issued',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `cleared_at` datetime DEFAULT NULL,
  `discount` decimal(12,2) DEFAULT 0.00,
  `other_discount` decimal(12,2) DEFAULT 0.00,
  `shipping_charge` decimal(12,2) DEFAULT 0.00,
  `tax_rate` decimal(6,2) DEFAULT 0.00,
  `tax_amount` decimal(12,2) DEFAULT NULL,
  `other_fees` decimal(12,2) DEFAULT 0.00,
  `pdf_file` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_number`, `order_id`, `amount`, `status`, `created_by`, `created_at`, `cleared_at`, `discount`, `other_discount`, `shipping_charge`, `tax_rate`, `tax_amount`, `other_fees`, `pdf_file`) VALUES
(13, 'INV-2025-001', 1, 1499.00, 'cleared', 1, '2025-11-19 03:46:49', '2025-11-19 05:37:24', 0.00, 0.00, 0.00, 0.00, NULL, 0.00, NULL),
(14, 'INV-2025-002', 2, 2499.50, 'cleared', 1, '2025-11-19 03:46:49', NULL, 0.00, 0.00, 0.00, 0.00, NULL, 0.00, NULL),
(15, 'INV-2025-003', 3, 999.00, 'cleared', 1, '2025-11-19 03:46:49', '2025-11-19 07:13:48', 0.00, 0.00, 0.00, 0.00, NULL, 0.00, NULL),
(16, 'INV-TEST-ALL-01', 1, 2000.00, 'issued', 1, '2025-11-19 07:08:57', NULL, 100.00, 50.00, 49.00, 18.00, 333.00, 25.00, 'OD335927864916938100.pdf'),
(17, 'INV-order_Rn2WRuatlT4a46-251203', 13, 479.00, 'issued', NULL, '2025-12-03 06:43:03', NULL, 0.00, 0.00, 0.00, 0.00, NULL, 0.00, NULL),
(18, 'INV-order_Rn04h9uQbsumj7-251203', 12, 479.00, 'issued', NULL, '2025-12-03 06:43:36', NULL, 0.00, 0.00, 0.00, 0.00, NULL, 0.00, NULL),
(19, 'INV-order_Rn2oHAyy1DObwY-251203', 14, 3600.00, 'issued', NULL, '2025-12-03 06:45:45', NULL, 0.00, 0.00, 0.00, 0.00, NULL, 0.00, NULL),
(20, 'INV-order_Rn2zHG4bRxQQDd-251203', 15, 5760.00, 'issued', NULL, '2025-12-03 06:57:42', NULL, 0.00, 0.00, 0.00, 0.00, 1098.31, 0.00, NULL),
(21, 'INV-order_RnldtWoaTvOfPb-251205', 16, 3240.00, 'issued', NULL, '2025-12-05 04:08:29', NULL, 0.00, 0.00, 0.00, 0.00, 549.15, 0.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `description` text NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `description`, `qty`, `unit_price`, `amount`, `created_at`) VALUES
(1, 16, 'Product A – Qty 2', 2, 500.00, 1000.00, '2025-11-19 07:08:57'),
(2, 16, 'Product B – Qty 1', 1, 1000.00, 1000.00, '2025-11-19 07:08:57'),
(3, 17, 'Babies Ayurvedic Eye Kajal Pure and Natural Anjana', 1, 399.00, 399.00, '2025-12-03 06:43:03'),
(4, 18, 'Babies Ayurvedic Eye Kajal Pure and Natural Anjana', 1, 399.00, 399.00, '2025-12-03 06:43:36'),
(5, 19, 'Nalpamaradi Kera Thailam Bridal Mom Baby Skin Beauty', 1, 3600.00, 3600.00, '2025-12-03 06:45:45'),
(6, 20, 'Nalpamaradi Kera Thailam Bridal Mom Baby Skin Beauty', 2, 3600.00, 7200.00, '2025-12-03 06:57:42'),
(7, 21, 'Nalpamaradi Kera Thailam Bridal Mom Baby Skin Beauty', 1, 3600.00, 3600.00, '2025-12-05 04:08:29');

-- --------------------------------------------------------

--
-- Table structure for table `media_files`
--

CREATE TABLE `media_files` (
  `id` char(36) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_filename` varchar(255) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `size` bigint(20) DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `storage_path` text DEFAULT NULL,
  `cdn_url` text DEFAULT NULL,
  `thumb_url` text DEFAULT NULL,
  `alt_text` text DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_favorite` tinyint(1) DEFAULT 0,
  `colors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`colors`)),
  `exif` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`exif`)),
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
  `last_used_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `folder_id` char(36) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `media_files`
--

INSERT INTO `media_files` (`id`, `filename`, `original_filename`, `mime_type`, `size`, `width`, `height`, `storage_path`, `cdn_url`, `thumb_url`, `alt_text`, `title`, `description`, `is_favorite`, `colors`, `exif`, `uploaded_by`, `uploaded_at`, `last_used_at`, `deleted_at`, `folder_id`) VALUES
('01191cd7-b734-4ee3-af0a-4d3c63affac5', '1763977482-ae024bd1ab.jpg', '1763977482-ae024bd1ab.jpg', 'image/jpeg', 95863, 1250, 500, 'assets/uploads/banners/1763977482-ae024bd1ab.jpg', '/assets/uploads/banners/1763977482-ae024bd1ab.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:10:57', NULL, NULL, 'bc2a1d6c-7d89-49af-8404-9416491db457'),
('018e078387184fcb8ced317609c2d05c', '018e078387184fcb8ced317609c2d05c_hair_care__develixir_png.png', 'hair care  develixir.png.png', 'image/png', 148159, 1080, 1080, 'assets/uploads/media/018e078387184fcb8ced317609c2d05c_hair_care__develixir_png.png', '/assets/uploads/media/018e078387184fcb8ced317609c2d05c_hair_care__develixir_png.png', '/assets/uploads/media/thumbs/018e078387184fcb8ced317609c2d05c.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-24 20:02:25', NULL, NULL, '06986aa8-ab0a-442b-a77b-51900ca11400'),
('0248ecf2a651479a8269fdbbaa3dc26c', '0248ecf2a651479a8269fdbbaa3dc26c_dev-elixir-kadhira-tooth-powder-ingredients-1.jpg', 'dev-elixir-kadhira-tooth-powder-ingredients-1.jpg', 'image/jpeg', 449636, 970, 600, 'assets/uploads/media/0248ecf2a651479a8269fdbbaa3dc26c_dev-elixir-kadhira-tooth-powder-ingredients-1.jpg', '/assets/uploads/media/0248ecf2a651479a8269fdbbaa3dc26c_dev-elixir-kadhira-tooth-powder-ingredients-1.jpg', '/assets/uploads/media/thumbs/0248ecf2a651479a8269fdbbaa3dc26c.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-21 22:27:15', NULL, NULL, NULL),
('02c42b06-4850-4ee8-b276-64b6bd0791a3', '1766076083_69442eb32de98_lib.mp4', '1766076083_69442eb32de98_lib.mp4', 'video/mp4', 17248711, NULL, NULL, 'assets/uploads/product_media/1766076083_69442eb32de98_lib.mp4', '/assets/uploads/product_media/1766076083_69442eb32de98_lib.mp4', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-22 08:01:19', NULL, NULL, 'c8346eaf-d4d0-43b4-898e-6128305f952b'),
('056484cf-37a7-4503-94d9-91956e0f3926', '1763986273-36082bee0c.jpg', '1763986273-36082bee0c.jpg', 'image/jpeg', 60389, 270, 350, 'assets/uploads/banners/1763986273-36082bee0c.jpg', '/assets/uploads/banners/1763986273-36082bee0c.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:10:57', NULL, NULL, 'bc2a1d6c-7d89-49af-8404-9416491db457'),
('0575e5fb-07ab-4722-be13-9d508f22b237', '1764972114-81afaebee5.png', '1764972114-81afaebee5.png', 'image/png', 1299080, 1024, 1024, 'assets/uploads/banners/1764972114-81afaebee5.png', '/assets/uploads/banners/1764972114-81afaebee5.png', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:10:57', NULL, NULL, '50feb35c-434d-4b61-8683-6088a4235dfe'),
('0669e6a1c22e46379489a32a366ab3ee', '0669e6a1c22e46379489a32a366ab3ee_Screenshot_2025-12-08_at_11_54_14___AM.png', 'Screenshot 2025-12-08 at 11.54.14 AM.png', 'image/png', 1308620, 2880, 1800, 'assets/uploads/media/0669e6a1c22e46379489a32a366ab3ee_Screenshot_2025-12-08_at_11_54_14___AM.png', '/assets/uploads/media/0669e6a1c22e46379489a32a366ab3ee_Screenshot_2025-12-08_at_11_54_14___AM.png', '/assets/uploads/media/thumbs/0669e6a1c22e46379489a32a366ab3ee.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-08 09:08:35', NULL, '2025-12-09 06:09:50', NULL),
('07365d22-7dfa-4a9c-b2a4-aeca876b3642', '1764355085_6929ec0d103fd.mp4', '1764355085_6929ec0d103fd.mp4', 'video/mp4', 1666477, NULL, NULL, 'assets/uploads/product_media/1764355085_6929ec0d103fd.mp4', '/assets/uploads/product_media/1764355085_6929ec0d103fd.mp4', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:28:28', NULL, NULL, '49f6f691-d59b-4cdd-88b1-5b54437bc04e'),
('0857f572-6772-4a01-a6e1-ef22b47d0a43', '1766387544_6948ef5897057_lib.mp4', '1766387544_6948ef5897057_lib.mp4', 'video/mp4', 17248711, NULL, NULL, 'assets/uploads/product_media/1766387544_6948ef5897057_lib.mp4', '/assets/uploads/product_media/1766387544_6948ef5897057_lib.mp4', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-22 08:01:19', NULL, NULL, '8582551c-70b9-439c-b3b1-ce571aa26f35'),
('0aa1dc83553f4652ad58a5608dd3218b', '0aa1dc83553f4652ad58a5608dd3218b_anitdandruff_oil_ingredients_2.jpg', 'anitdandruff oil ingredients 2.jpg', 'image/jpeg', 959549, 1080, 1080, 'assets/uploads/media/0aa1dc83553f4652ad58a5608dd3218b_anitdandruff_oil_ingredients_2.jpg', '/assets/uploads/media/0aa1dc83553f4652ad58a5608dd3218b_anitdandruff_oil_ingredients_2.jpg', '/assets/uploads/media/thumbs/0aa1dc83553f4652ad58a5608dd3218b.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2026-01-02 07:26:00', NULL, NULL, NULL),
('0b0f4573-7b25-4a72-bd7f-e256dd77505e', '1766356905_694877a95cb36_lib.jpg', '1766356905_694877a95cb36_lib.jpg', 'image/jpeg', 449636, 970, 600, 'assets/uploads/product_media/1766356905_694877a95cb36_lib.jpg', '/assets/uploads/product_media/1766356905_694877a95cb36_lib.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-22 08:01:19', NULL, NULL, '4cfd30c1-f938-4f9b-92f7-3a9638e048bc'),
('0b17daae56cb421eb08c6ef6a611b1f9', '0b17daae56cb421eb08c6ef6a611b1f9_how-to-use-shikakai-hair-wash.jpg', 'how-to-use-shikakai-hair-wash.jpg', 'image/jpeg', 739185, 1000, 1000, 'assets/uploads/media/0b17daae56cb421eb08c6ef6a611b1f9_how-to-use-shikakai-hair-wash.jpg', '/assets/uploads/media/0b17daae56cb421eb08c6ef6a611b1f9_how-to-use-shikakai-hair-wash.jpg', '/assets/uploads/media/thumbs/0b17daae56cb421eb08c6ef6a611b1f9.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-18 12:02:33', NULL, NULL, NULL),
('0b97187b-711e-4452-9421-6a463d9efb30', '1764155187-1d999450aa.webp', '1764155187-1d999450aa.webp', 'image/webp', 46836, 1000, 300, 'assets/uploads/banners/1764155187-1d999450aa.webp', '/assets/uploads/banners/1764155187-1d999450aa.webp', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:10:57', NULL, NULL, '4ba01a5b-7754-4e1e-8c45-b4315d588d95'),
('0fbabdfd-d9cb-4da2-ac00-17d8af301fd7', '1765594523-6746b8b501.jpg', '1765594523-6746b8b501.jpg', 'image/jpeg', 129893, 1920, 652, 'assets/uploads/banners/1765594523-6746b8b501.jpg', '/assets/uploads/banners/1765594523-6746b8b501.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-14 06:59:10', NULL, NULL, '4b935e8c-762a-41d5-8518-c46a9d9c1f63'),
('10d09f64-9683-45c7-85d2-3b4084d38444', '10d09f64-9683-45c7-85d2-3b4084d38444.png', 'Copy of Furniture Sale Instagram stories template.png', 'image/png', 3110164, 1080, 1920, 'assets/uploads/media/10d09f64-9683-45c7-85d2-3b4084d38444.png', '/assets/uploads/media/10d09f64-9683-45c7-85d2-3b4084d38444.png', '/assets/uploads/media/thumbs/fbbafab2549643cbac3385d9f9e93531.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-14 07:01:03', NULL, NULL, NULL),
('11288fdc-75d6-4390-93ea-9bce566d1e46', '1764972443-dec645963e.jpg', '1764972443-dec645963e.jpg', 'image/jpeg', 142885, 1600, 600, 'assets/uploads/banners/1764972443-dec645963e.jpg', '/assets/uploads/banners/1764972443-dec645963e.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:10:57', NULL, NULL, '4b935e8c-762a-41d5-8518-c46a9d9c1f63'),
('118f59d7-f75b-4b09-b4d8-a92703bc9300', '1764034549-de7b387273.jpg', '1764034549-de7b387273.jpg', 'image/jpeg', 95863, 1250, 500, 'assets/uploads/banners/1764034549-de7b387273.jpg', '/assets/uploads/banners/1764034549-de7b387273.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:10:57', NULL, NULL, '50feb35c-434d-4b61-8683-6088a4235dfe'),
('11ee488c-a2e9-4300-83de-c02769c13bd0', '1764971345-d46e3458b7.png', '1764971345-d46e3458b7.png', 'image/png', 438992, 1920, 1714, 'assets/uploads/banners/1764971345-d46e3458b7.png', '/assets/uploads/banners/1764971345-d46e3458b7.png', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:10:57', NULL, NULL, 'bc2a1d6c-7d89-49af-8404-9416491db457'),
('134632cd-81a1-4166-9bab-0b1717a285a1', 'dc4fba4c5fc9.png', 'dc4fba4c5fc9.png', 'image/png', 869039, 1200, 1200, 'assets/uploads/products/dc4fba4c5fc9.png', '/assets/uploads/products/dc4fba4c5fc9.png', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-22 08:01:19', NULL, NULL, '8582551c-70b9-439c-b3b1-ce571aa26f35'),
('17fc866f-f9b8-4256-a642-bc7c829c73db', '1765047368_0730c563f5b5.mp4', '1765047368_0730c563f5b5.mp4', 'video/mp4', 1746419, NULL, NULL, 'assets/uploads/products/1765047368_0730c563f5b5.mp4', '/assets/uploads/products/1765047368_0730c563f5b5.mp4', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:28:28', NULL, NULL, '1d293e96-95e9-49d1-87ab-664459220334'),
('18a7a6c51e2e46978d52849f94e68609', '18a7a6c51e2e46978d52849f94e68609_Screenshot_2025-12-07_at_8_35_21___AM.png', 'Screenshot 2025-12-07 at 8.35.21 AM.png', 'image/png', 42953, 1708, 258, 'assets/uploads/media/18a7a6c51e2e46978d52849f94e68609_Screenshot_2025-12-07_at_8_35_21___AM.png', '/assets/uploads/media/18a7a6c51e2e46978d52849f94e68609_Screenshot_2025-12-07_at_8_35_21___AM.png', '/assets/uploads/media/thumbs/18a7a6c51e2e46978d52849f94e68609.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-08 09:10:25', NULL, '2025-12-09 06:09:50', NULL),
('1984d5132e4d4b74a082acb8b8c386d3', '1984d5132e4d4b74a082acb8b8c386d3_Screenshot_2025-11-30_at_9_27_00___AM.png', 'Screenshot 2025-11-30 at 9.27.00 AM.png', 'image/png', 407254, 468, 1120, 'assets/uploads/media/1984d5132e4d4b74a082acb8b8c386d3_Screenshot_2025-11-30_at_9_27_00___AM.png', '/assets/uploads/media/1984d5132e4d4b74a082acb8b8c386d3_Screenshot_2025-11-30_at_9_27_00___AM.png', '/assets/uploads/media/thumbs/1984d5132e4d4b74a082acb8b8c386d3.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-08 09:10:25', NULL, '2025-12-09 06:09:50', NULL),
('19daf512fe5b461d8a322a5de5b192eb', '19daf512fe5b461d8a322a5de5b192eb_Screenshot_2025-12-08_at_11_54_14___AM.png', 'Screenshot 2025-12-08 at 11.54.14 AM.png', 'image/png', 1308620, 2880, 1800, 'assets/uploads/media/19daf512fe5b461d8a322a5de5b192eb_Screenshot_2025-12-08_at_11_54_14___AM.png', '/assets/uploads/media/19daf512fe5b461d8a322a5de5b192eb_Screenshot_2025-12-08_at_11_54_14___AM.png', '/assets/uploads/media/thumbs/19daf512fe5b461d8a322a5de5b192eb.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-08 09:10:25', NULL, '2025-12-09 06:09:50', NULL),
('19f8295f-47c1-47fb-84b5-0bf91ec5e6b3', '77285b548731.jpg', '77285b548731.jpg', 'image/jpeg', 265046, 1000, 1000, 'assets/uploads/products/77285b548731.jpg', '/assets/uploads/products/77285b548731.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:28:28', NULL, NULL, '49f6f691-d59b-4cdd-88b1-5b54437bc04e'),
('1cf75e01-363e-497f-8bf2-3ad4c5525fcb', '1764971054-93b2d26881.jpg', '1764971054-93b2d26881.jpg', 'image/jpeg', 172984, 1200, 1200, 'assets/uploads/banners/1764971054-93b2d26881.jpg', '/assets/uploads/banners/1764971054-93b2d26881.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:10:57', NULL, NULL, 'bc2a1d6c-7d89-49af-8404-9416491db457'),
('1e89acf893ee4e02b9a521b2be9c52f5', '1e89acf893ee4e02b9a521b2be9c52f5_wellness__develixir_png.png', 'wellness  develixir.png.png', 'image/png', 291157, 1080, 1080, 'assets/uploads/media/1e89acf893ee4e02b9a521b2be9c52f5_wellness__develixir_png.png', '/assets/uploads/media/1e89acf893ee4e02b9a521b2be9c52f5_wellness__develixir_png.png', '/assets/uploads/media/thumbs/1e89acf893ee4e02b9a521b2be9c52f5.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-24 20:02:25', NULL, NULL, '06986aa8-ab0a-442b-a77b-51900ca11400'),
('1e8d2d9f76b94d7db9d82f5d45011266', '1e8d2d9f76b94d7db9d82f5d45011266_bala_herb.mp4', 'bala herb.mp4', 'video/mp4', 801446, NULL, NULL, 'assets/uploads/media/1e8d2d9f76b94d7db9d82f5d45011266_bala_herb.mp4', '/assets/uploads/media/1e8d2d9f76b94d7db9d82f5d45011266_bala_herb.mp4', NULL, '', '', '', 0, NULL, NULL, 6, '2025-12-14 09:00:30', NULL, NULL, NULL),
('208dea66c7bf46398bfd26274198ba2c', '208dea66c7bf46398bfd26274198ba2c_Screenshot_2025-12-09_at_11_14_10___AM.png', 'Screenshot 2025-12-09 at 11.14.10 AM.png', 'image/png', 1091522, 2384, 1290, 'assets/uploads/media/208dea66c7bf46398bfd26274198ba2c_Screenshot_2025-12-09_at_11_14_10___AM.png', '/assets/uploads/media/208dea66c7bf46398bfd26274198ba2c_Screenshot_2025-12-09_at_11_14_10___AM.png', '/assets/uploads/media/thumbs/208dea66c7bf46398bfd26274198ba2c.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-09 06:32:42', NULL, '2025-12-09 06:33:12', 'f32511c0-44a5-4567-ac65-c2d9a38bc23b'),
('212c3d4b-e1a5-447c-847b-e6037c70efaf', 'b83f6e04372a.jpg', 'b83f6e04372a.jpg', 'image/jpeg', 212965, 1100, 1100, 'assets/uploads/products/b83f6e04372a.jpg', '/assets/uploads/products/b83f6e04372a.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:28:28', NULL, NULL, '1d293e96-95e9-49d1-87ab-664459220334'),
('21ad9aa68b054ed896fda8d31c059c2a', '21ad9aa68b054ed896fda8d31c059c2a_bath_and_body_care_develixir_png.png', 'bath and body care develixir.png.png', 'image/png', 120345, 1080, 1080, 'assets/uploads/media/21ad9aa68b054ed896fda8d31c059c2a_bath_and_body_care_develixir_png.png', '/assets/uploads/media/21ad9aa68b054ed896fda8d31c059c2a_bath_and_body_care_develixir_png.png', '/assets/uploads/media/thumbs/21ad9aa68b054ed896fda8d31c059c2a.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-24 20:02:25', NULL, NULL, '06986aa8-ab0a-442b-a77b-51900ca11400'),
('252c457bce664e08ad84749e470e8fc5', '252c457bce664e08ad84749e470e8fc5_Natural_face_care_with_Ayurveda_for_healthy_glowing_skin_copy.jpg', 'Natural face care with Ayurveda for healthy glowing skin copy.jpg', 'image/jpeg', 455360, 1200, 630, 'assets/uploads/media/252c457bce664e08ad84749e470e8fc5_Natural_face_care_with_Ayurveda_for_healthy_glowing_skin_copy.jpg', '/assets/uploads/media/252c457bce664e08ad84749e470e8fc5_Natural_face_care_with_Ayurveda_for_healthy_glowing_skin_copy.jpg', '/assets/uploads/media/thumbs/252c457bce664e08ad84749e470e8fc5.jpg', 'Woman applying homemade ubtan face pack for dark spot care at home', 'Homemade Ubtan Remedies for Dark Spots', '', 0, NULL, NULL, 6, '2025-12-13 07:38:56', NULL, NULL, NULL),
('25486f8d37ad4105bd15d2b15f03acc2', '25486f8d37ad4105bd15d2b15f03acc2_community_and_etics_copy.jpg', 'community and etics copy.jpg', 'image/jpeg', 255993, 970, 600, 'assets/uploads/media/25486f8d37ad4105bd15d2b15f03acc2_community_and_etics_copy.jpg', '/assets/uploads/media/25486f8d37ad4105bd15d2b15f03acc2_community_and_etics_copy.jpg', '/assets/uploads/media/thumbs/25486f8d37ad4105bd15d2b15f03acc2.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2026-01-04 04:54:19', NULL, NULL, NULL),
('27602d2f-4cd9-4b62-a42e-f1946e728472', 'a8764ece5875.jpg', 'a8764ece5875.jpg', 'image/jpeg', 264361, 1000, 1000, 'assets/uploads/products/a8764ece5875.jpg', '/assets/uploads/products/a8764ece5875.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-22 08:01:19', NULL, NULL, 'c8346eaf-d4d0-43b4-898e-6128305f952b'),
('28840b8d067b45d593c4a8c1d22fae62', '28840b8d067b45d593c4a8c1d22fae62_Screenshot_2025-11-30_at_9_26_21___AM.png', 'Screenshot 2025-11-30 at 9.26.21 AM.png', 'image/png', 265250, 468, 1120, 'assets/uploads/media/28840b8d067b45d593c4a8c1d22fae62_Screenshot_2025-11-30_at_9_26_21___AM.png', '/assets/uploads/media/28840b8d067b45d593c4a8c1d22fae62_Screenshot_2025-11-30_at_9_26_21___AM.png', '/assets/uploads/media/thumbs/28840b8d067b45d593c4a8c1d22fae62.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-08 09:10:25', NULL, '2025-12-09 06:09:50', NULL),
('2ed8a2d6-11e1-4406-baa3-25d20455af68', 'e2f678e99040.jpg', 'e2f678e99040.jpg', 'image/jpeg', 53179, 600, 600, 'assets/uploads/products/e2f678e99040.jpg', '/assets/uploads/products/e2f678e99040.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:28:28', NULL, NULL, '1d293e96-95e9-49d1-87ab-664459220334'),
('30c2f19a-c79f-4a99-80e3-3c11e86a7f99', '06c7168b9872.jpg', '06c7168b9872.jpg', 'image/jpeg', 119166, 1000, 1000, 'assets/uploads/products/06c7168b9872.jpg', '/assets/uploads/products/06c7168b9872.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-22 08:01:19', NULL, NULL, 'c8346eaf-d4d0-43b4-898e-6128305f952b'),
('39b276f9-547a-42e5-923c-67c8eb6904a4', '1765175277-189717e481.jpg', '1765175277-189717e481.jpg', 'image/jpeg', 108234, 1080, 1080, 'assets/uploads/categories/1765175277-189717e481.jpg', '/assets/uploads/categories/1765175277-189717e481.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:28:28', NULL, NULL, 'f8077188-87dc-4fe7-ac76-a1cadc94b224'),
('3ea8026be3884240914543d8af9b485a', '3ea8026be3884240914543d8af9b485a_Screenshot_2025-12-08_at_2_30_31___PM.png', 'Screenshot 2025-12-08 at 2.30.31 PM.png', 'image/png', 1076194, 2384, 1290, 'assets/uploads/media/3ea8026be3884240914543d8af9b485a_Screenshot_2025-12-08_at_2_30_31___PM.png', '/assets/uploads/media/3ea8026be3884240914543d8af9b485a_Screenshot_2025-12-08_at_2_30_31___PM.png', '/assets/uploads/media/thumbs/3ea8026be3884240914543d8af9b485a.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-08 09:10:24', NULL, '2025-12-09 06:09:50', NULL),
('41540ce410fc45f9973ac28c04b44fae', '41540ce410fc45f9973ac28c04b44fae_Untitled-1_model_banner.jpg', 'Untitled-1 model banner.jpg', 'image/jpeg', 929888, 1200, 630, 'assets/uploads/media/41540ce410fc45f9973ac28c04b44fae_Untitled-1_model_banner.jpg', '/assets/uploads/media/41540ce410fc45f9973ac28c04b44fae_Untitled-1_model_banner.jpg', '/assets/uploads/media/thumbs/41540ce410fc45f9973ac28c04b44fae.jpg', '', '', '', 0, NULL, NULL, 6, '2025-12-11 05:13:55', NULL, NULL, NULL),
('41569bfa-836c-440f-9622-d5a32495a74e', 'af56a7f7297e.jpg', 'af56a7f7297e.jpg', 'image/jpeg', 203451, 1100, 1100, 'assets/uploads/products/af56a7f7297e.jpg', '/assets/uploads/products/af56a7f7297e.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:28:28', NULL, NULL, '1d293e96-95e9-49d1-87ab-664459220334'),
('41a94317bce64554ab5421b369a9597f', '41a94317bce64554ab5421b369a9597f_Screenshot_2025-12-07_at_7_45_37___AM.png', 'Screenshot 2025-12-07 at 7.45.37 AM.png', 'image/png', 166377, 2848, 1184, 'assets/uploads/media/41a94317bce64554ab5421b369a9597f_Screenshot_2025-12-07_at_7_45_37___AM.png', '/assets/uploads/media/41a94317bce64554ab5421b369a9597f_Screenshot_2025-12-07_at_7_45_37___AM.png', '/assets/uploads/media/thumbs/41a94317bce64554ab5421b369a9597f.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-08 09:10:25', NULL, '2025-12-09 06:09:50', NULL),
('428d92975e53427dbf498d506edf7e48', '428d92975e53427dbf498d506edf7e48_benefits_foot_creams_copy.jpg', 'benefits foot creams copy.jpg', 'image/jpeg', 171790, 600, 600, 'assets/uploads/media/428d92975e53427dbf498d506edf7e48_benefits_foot_creams_copy.jpg', '/assets/uploads/media/428d92975e53427dbf498d506edf7e48_benefits_foot_creams_copy.jpg', '/assets/uploads/media/thumbs/428d92975e53427dbf498d506edf7e48.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-18 05:01:33', NULL, NULL, NULL),
('44a55d75-9b14-474f-bc31-f6880ffdd9a7', '95c51d08edfc.jpg', '95c51d08edfc.jpg', 'image/jpeg', 180253, 1100, 1100, 'assets/uploads/products/95c51d08edfc.jpg', '/assets/uploads/products/95c51d08edfc.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:28:28', NULL, NULL, '1d293e96-95e9-49d1-87ab-664459220334'),
('4539796b2d45484da75021b366f5fb05', '4539796b2d45484da75021b366f5fb05_Natural_glow_baby_bath_powder_copy.jpg', 'Natural glow baby bath powder copy.jpg', 'image/jpeg', 800087, 1100, 1100, 'assets/uploads/media/4539796b2d45484da75021b366f5fb05_Natural_glow_baby_bath_powder_copy.jpg', '/assets/uploads/media/4539796b2d45484da75021b366f5fb05_Natural_glow_baby_bath_powder_copy.jpg', '/assets/uploads/media/thumbs/4539796b2d45484da75021b366f5fb05.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2026-01-02 06:49:41', NULL, NULL, 'd0185db3-4942-4e71-a5a6-0be1af14e207'),
('46f72a615b2e475fbb450048632efcc0', '46f72a615b2e475fbb450048632efcc0_how_to_use_baby_bath_powder_almond_rose-1_copy.jpg', 'how to use baby bath powder almond rose-1 copy.jpg', 'image/jpeg', 768268, 1100, 1100, 'assets/uploads/media/46f72a615b2e475fbb450048632efcc0_how_to_use_baby_bath_powder_almond_rose-1_copy.jpg', '/assets/uploads/media/46f72a615b2e475fbb450048632efcc0_how_to_use_baby_bath_powder_almond_rose-1_copy.jpg', '/assets/uploads/media/thumbs/46f72a615b2e475fbb450048632efcc0.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2026-01-02 06:49:41', NULL, NULL, 'd0185db3-4942-4e71-a5a6-0be1af14e207'),
('4b67d913-65b2-4d41-9164-7d67380de418', '1764155369-91bdf11bf7.jpg', '1764155369-91bdf11bf7.jpg', 'image/jpeg', 114442, 1600, 422, 'assets/uploads/banners/1764155369-91bdf11bf7.jpg', '/assets/uploads/banners/1764155369-91bdf11bf7.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:10:57', NULL, NULL, '4ba01a5b-7754-4e1e-8c45-b4315d588d95'),
('4bbffc33-c16e-44bd-a41c-a3143e080d4f', '1764294524-92be6d27bd.jpg', '1764294524-92be6d27bd.jpg', 'image/jpeg', 30069, 626, 331, 'assets/uploads/banners/1764294524-92be6d27bd.jpg', '/assets/uploads/banners/1764294524-92be6d27bd.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:10:57', NULL, NULL, '50feb35c-434d-4b61-8683-6088a4235dfe'),
('4d714192853d4c56b5ad137dc65a451c', '4d714192853d4c56b5ad137dc65a451c_Screenshot_2025-12-05_at_1_35_27___PM.png', 'Screenshot 2025-12-05 at 1.35.27 PM.png', 'image/png', 3107877, 2776, 1468, 'assets/uploads/media/4d714192853d4c56b5ad137dc65a451c_Screenshot_2025-12-05_at_1_35_27___PM.png', '/assets/uploads/media/4d714192853d4c56b5ad137dc65a451c_Screenshot_2025-12-05_at_1_35_27___PM.png', '/assets/uploads/media/thumbs/4d714192853d4c56b5ad137dc65a451c.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-12 02:11:58', NULL, NULL, NULL),
('4e9de01b2e3f492d8d3094a4c451986a', '4e9de01b2e3f492d8d3094a4c451986a_dev-elixir-tooth-powder-kadhira.jpg', 'dev-elixir-tooth-powder-kadhira.jpg', 'image/jpeg', 373150, 1000, 1000, 'assets/uploads/media/4e9de01b2e3f492d8d3094a4c451986a_dev-elixir-tooth-powder-kadhira.jpg', '/assets/uploads/media/4e9de01b2e3f492d8d3094a4c451986a_dev-elixir-tooth-powder-kadhira.jpg', '/assets/uploads/media/thumbs/4e9de01b2e3f492d8d3094a4c451986a.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-21 22:30:27', NULL, NULL, NULL),
('518ccca4-1a82-438f-ad4f-cddde95caf34', '1763958639-f7e505c329.webp', '1763958639-f7e505c329.webp', 'image/webp', 37358, 480, 480, 'assets/uploads/banners/1763958639-f7e505c329.webp', '/assets/uploads/banners/1763958639-f7e505c329.webp', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:10:57', NULL, NULL, 'bc2a1d6c-7d89-49af-8404-9416491db457'),
('557dbb42da0b4d91a106fcbed6d48d5e', '557dbb42da0b4d91a106fcbed6d48d5e_why_develixir_choose_rose_copy2_1.mp4', 'why develixir choose rose copy2_1.mp4', 'video/mp4', 17248711, NULL, NULL, 'assets/uploads/media/557dbb42da0b4d91a106fcbed6d48d5e_why_develixir_choose_rose_copy2_1.mp4', '/assets/uploads/media/557dbb42da0b4d91a106fcbed6d48d5e_why_develixir_choose_rose_copy2_1.mp4', NULL, NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-18 05:22:01', NULL, NULL, NULL),
('5711575d58294b288c58e94c5518de53', '5711575d58294b288c58e94c5518de53_Screenshot_2025-12-09_at_11_14_10___AM.png', 'Screenshot 2025-12-09 at 11.14.10 AM.png', 'image/png', 1091522, 2384, 1290, 'assets/uploads/media/5711575d58294b288c58e94c5518de53_Screenshot_2025-12-09_at_11_14_10___AM.png', '/assets/uploads/media/5711575d58294b288c58e94c5518de53_Screenshot_2025-12-09_at_11_14_10___AM.png', '/assets/uploads/media/thumbs/5711575d58294b288c58e94c5518de53.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-09 08:50:57', NULL, NULL, '30078477-6870-4ad0-a440-df9bcbf4a053'),
('574ca32d-11b6-43b8-a382-9988914175bc', 'b84ee50d030f.JPG', 'b84ee50d030f.JPG', 'image/jpeg', 639414, 1920, 1440, 'assets/uploads/products/b84ee50d030f.JPG', '/assets/uploads/products/b84ee50d030f.JPG', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:28:28', NULL, NULL, '8cc3bf31-1932-41f2-9030-b1f72fbfa667'),
('577ff97b368a44eca5f0107d217c7a74', '577ff97b368a44eca5f0107d217c7a74_Screenshot_2025-12-09_at_11_14_10___AM.png', 'Screenshot 2025-12-09 at 11.14.10 AM.png', 'image/png', 1091522, 2384, 1290, 'assets/uploads/media/577ff97b368a44eca5f0107d217c7a74_Screenshot_2025-12-09_at_11_14_10___AM.png', '/assets/uploads/media/577ff97b368a44eca5f0107d217c7a74_Screenshot_2025-12-09_at_11_14_10___AM.png', '/assets/uploads/media/thumbs/577ff97b368a44eca5f0107d217c7a74.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-09 06:09:59', NULL, '2025-12-09 06:12:53', NULL),
('5843b369-053f-44fb-b332-095b8fa41a9d', '69723da38cb8.jpg', '69723da38cb8.jpg', 'image/jpeg', 177464, 1100, 1100, 'assets/uploads/products/69723da38cb8.jpg', '/assets/uploads/products/69723da38cb8.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:28:28', NULL, NULL, '1d293e96-95e9-49d1-87ab-664459220334'),
('5a60d886-4de5-4172-976d-3bf6191002df', '1764155133-0bf8635920.png', '1764155133-0bf8635920.png', 'image/png', 583926, 1600, 500, 'assets/uploads/banners/1764155133-0bf8635920.png', '/assets/uploads/banners/1764155133-0bf8635920.png', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:10:57', NULL, NULL, '4ba01a5b-7754-4e1e-8c45-b4315d588d95'),
('5c839475-dd66-48d6-b713-cb4c4a25e2f4', 'b58368c3ee6e.jpg', 'b58368c3ee6e.jpg', 'image/jpeg', 155584, 1000, 1000, 'assets/uploads/products/b58368c3ee6e.jpg', '/assets/uploads/products/b58368c3ee6e.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-22 08:01:19', NULL, NULL, 'c8346eaf-d4d0-43b4-898e-6128305f952b'),
('5f8a473c73db4da189cd4ce4117302fc', '5f8a473c73db4da189cd4ce4117302fc_Untitled-1_model_banner.jpg', 'Untitled-1 model banner.jpg', 'image/jpeg', 929888, 1200, 630, 'assets/uploads/media/5f8a473c73db4da189cd4ce4117302fc_Untitled-1_model_banner.jpg', '/assets/uploads/media/5f8a473c73db4da189cd4ce4117302fc_Untitled-1_model_banner.jpg', '/assets/uploads/media/thumbs/5f8a473c73db4da189cd4ce4117302fc.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2026-01-02 07:37:10', NULL, NULL, NULL),
('61279e4f-9ad5-458f-9b0b-fc38a3acf2fc', '1764076707-93201c232b.jpg', '1764076707-93201c232b.jpg', 'image/jpeg', 167398, 1600, 1000, 'assets/uploads/categories/1764076707-93201c232b.jpg', '/assets/uploads/categories/1764076707-93201c232b.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:28:28', NULL, '2025-12-09 08:43:56', '646482e4-41f3-4d10-b453-b64c859bbe49'),
('627bde68e4374b07aeb4db2b0fcac0bf', '627bde68e4374b07aeb4db2b0fcac0bf_Screenshot_2025-12-08_at_8_55_47___AM.png', 'Screenshot 2025-12-08 at 8.55.47 AM.png', 'image/png', 1481675, 970, 1372, 'assets/uploads/media/627bde68e4374b07aeb4db2b0fcac0bf_Screenshot_2025-12-08_at_8_55_47___AM.png', '/assets/uploads/media/627bde68e4374b07aeb4db2b0fcac0bf_Screenshot_2025-12-08_at_8_55_47___AM.png', '/assets/uploads/media/thumbs/627bde68e4374b07aeb4db2b0fcac0bf.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-08 09:10:25', NULL, '2025-12-09 06:09:50', NULL),
('635633c0dd3a49d78a1b123b865acb37', '635633c0dd3a49d78a1b123b865acb37_eyecare_develixir.png', 'eyecare develixir.png', 'image/png', 145814, 1080, 1080, 'assets/uploads/media/635633c0dd3a49d78a1b123b865acb37_eyecare_develixir.png', '/assets/uploads/media/635633c0dd3a49d78a1b123b865acb37_eyecare_develixir.png', '/assets/uploads/media/thumbs/635633c0dd3a49d78a1b123b865acb37.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-24 20:02:25', NULL, NULL, '06986aa8-ab0a-442b-a77b-51900ca11400'),
('648895eb-9b7e-436e-b6b7-9066eea6f19b', '1764129829-0f1741f6d7.jpg', '1764129829-0f1741f6d7.jpg', 'image/jpeg', 32603, 1024, 266, 'assets/uploads/banners/1764129829-0f1741f6d7.jpg', '/assets/uploads/banners/1764129829-0f1741f6d7.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:10:57', NULL, NULL, '4ba01a5b-7754-4e1e-8c45-b4315d588d95'),
('66eca920-a2ef-4812-b35d-587f83d842c6', '1764129875-4e6451713e.webp', '1764129875-4e6451713e.webp', 'image/webp', 390260, 3033, 1250, 'assets/uploads/banners/1764129875-4e6451713e.webp', '/assets/uploads/banners/1764129875-4e6451713e.webp', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:10:57', NULL, NULL, '4ba01a5b-7754-4e1e-8c45-b4315d588d95'),
('67898f1c-0ffb-4ed1-8933-a1546055a842', '1763959733-e9987172b3.jpg', '1763959733-e9987172b3.jpg', 'image/jpeg', 19595, 612, 408, 'assets/uploads/categories/1763959733-e9987172b3.jpg', '/assets/uploads/categories/1763959733-e9987172b3.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:28:28', NULL, NULL, '22112f89-984e-4e68-98af-9cb469eb07de'),
('6a7d97f92e9d42dfbf4652e7bc0a6d2b', '6a7d97f92e9d42dfbf4652e7bc0a6d2b_Screenshot_2025-12-07_at_3_08_00___PM.png', 'Screenshot 2025-12-07 at 3.08.00 PM.png', 'image/png', 193349, 2876, 1372, 'assets/uploads/media/6a7d97f92e9d42dfbf4652e7bc0a6d2b_Screenshot_2025-12-07_at_3_08_00___PM.png', '/assets/uploads/media/6a7d97f92e9d42dfbf4652e7bc0a6d2b_Screenshot_2025-12-07_at_3_08_00___PM.png', '/assets/uploads/media/thumbs/6a7d97f92e9d42dfbf4652e7bc0a6d2b.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-08 09:10:25', NULL, '2025-12-09 06:09:50', NULL),
('6c52c291-f81a-4c0b-bebe-c76879dff9e8', 'c28997524100.jpg', 'c28997524100.jpg', 'image/jpeg', 464270, 1000, 1000, 'assets/uploads/products/c28997524100.jpg', '/assets/uploads/products/c28997524100.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:28:28', NULL, NULL, '49f6f691-d59b-4cdd-88b1-5b54437bc04e'),
('6d8b5136630444888c993cbe87fafc7b', '6d8b5136630444888c993cbe87fafc7b_men_useing_eyebrow_growth_oil.mp4', 'men useing eyebrow growth oil.mp4', 'video/mp4', 28019429, NULL, NULL, 'assets/uploads/media/6d8b5136630444888c993cbe87fafc7b_men_useing_eyebrow_growth_oil.mp4', '/assets/uploads/media/6d8b5136630444888c993cbe87fafc7b_men_useing_eyebrow_growth_oil.mp4', NULL, NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-15 05:47:01', NULL, NULL, NULL),
('6ffa9f86-8c95-4a96-bbdd-6b60e5200757', 'ee36d8f868ad.jpg', 'ee36d8f868ad.jpg', 'image/jpeg', 49898, 600, 600, 'assets/uploads/products/ee36d8f868ad.jpg', '/assets/uploads/products/ee36d8f868ad.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:28:28', NULL, NULL, 'b9a65b37-974b-4ef8-abe9-e114c897447f'),
('7002f2a3-376e-4528-a5ff-0c1df204bd6f', '7002f2a3-376e-4528-a5ff-0c1df204bd6f.jpg', 'test.jpg', 'image/jpeg', 82942, 612, 408, 'assets/uploads/media/7002f2a3-376e-4528-a5ff-0c1df204bd6f.jpg', '/assets/uploads/media/7002f2a3-376e-4528-a5ff-0c1df204bd6f.jpg', NULL, NULL, NULL, NULL, 1, NULL, NULL, 6, '2025-12-09 06:41:04', NULL, NULL, 'f32511c0-44a5-4567-ac65-c2d9a38bc23b'),
('70e882e7-4394-4a7e-874d-52b2656b4e5f', '1763884825-1043deea86ec.jpeg', '1763884825-1043deea86ec.jpeg', 'image/jpeg', 153380, 1600, 583, 'assets/uploads/banners/1763884825-1043deea86ec.jpeg', '/assets/uploads/banners/1763884825-1043deea86ec.jpeg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:10:57', NULL, NULL, 'bc2a1d6c-7d89-49af-8404-9416491db457'),
('732848c1-7423-4c16-844a-6e5cdc62bb1f', '1764972304-7d65edfe45.png', '1764972304-7d65edfe45.png', 'image/png', 2381849, 1536, 1024, 'assets/uploads/banners/1764972304-7d65edfe45.png', '/assets/uploads/banners/1764972304-7d65edfe45.png', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:10:57', NULL, NULL, '50feb35c-434d-4b61-8683-6088a4235dfe'),
('74699f72-a559-49ec-91a0-cd1e85c431d2', '1764970971-f077f169f0.jpg', '1764970971-f077f169f0.jpg', 'image/jpeg', 178704, 1920, 652, 'assets/uploads/banners/1764970971-f077f169f0.jpg', '/assets/uploads/banners/1764970971-f077f169f0.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:10:57', NULL, NULL, 'bc2a1d6c-7d89-49af-8404-9416491db457'),
('754f95294dc64d7c840ced08e1e9c87a', '754f95294dc64d7c840ced08e1e9c87a_about_our_journey.jpg', 'about our journey.jpg', 'image/jpeg', 199304, 900, 400, 'assets/uploads/media/754f95294dc64d7c840ced08e1e9c87a_about_our_journey.jpg', '/assets/uploads/media/754f95294dc64d7c840ced08e1e9c87a_about_our_journey.jpg', '/assets/uploads/media/thumbs/754f95294dc64d7c840ced08e1e9c87a.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-29 12:57:39', NULL, NULL, NULL),
('780ee9489eb24ed0b53319d047d59f00', '780ee9489eb24ed0b53319d047d59f00_Screenshot_2025-12-03_at_9_41_42___AM.png', 'Screenshot 2025-12-03 at 9.41.42 AM.png', 'image/png', 196444, 2158, 1368, 'assets/uploads/media/780ee9489eb24ed0b53319d047d59f00_Screenshot_2025-12-03_at_9_41_42___AM.png', '/assets/uploads/media/780ee9489eb24ed0b53319d047d59f00_Screenshot_2025-12-03_at_9_41_42___AM.png', '/assets/uploads/media/thumbs/780ee9489eb24ed0b53319d047d59f00.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-08 09:10:25', NULL, '2025-12-09 06:09:50', NULL),
('7e6d969df3b14d118c9889cdaf2c0fee', '7e6d969df3b14d118c9889cdaf2c0fee_Give_your_feet_the_feather-touch_care_copy.jpg', 'Give your feet the feather-touch care copy.jpg', 'image/jpeg', 140751, 600, 600, 'assets/uploads/media/7e6d969df3b14d118c9889cdaf2c0fee_Give_your_feet_the_feather-touch_care_copy.jpg', '/assets/uploads/media/7e6d969df3b14d118c9889cdaf2c0fee_Give_your_feet_the_feather-touch_care_copy.jpg', '/assets/uploads/media/thumbs/7e6d969df3b14d118c9889cdaf2c0fee.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-18 04:58:50', NULL, NULL, NULL),
('81350a11-bcc1-4b13-9762-389751b22852', '1764354601_6929ea2922709.mp4', '1764354601_6929ea2922709.mp4', 'video/mp4', 1666477, NULL, NULL, 'assets/uploads/product_media/1764354601_6929ea2922709.mp4', '/assets/uploads/product_media/1764354601_6929ea2922709.mp4', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:28:28', NULL, NULL, '49f6f691-d59b-4cdd-88b1-5b54437bc04e'),
('81a99cd3e9a44659bc089d7f365388d4', '81a99cd3e9a44659bc089d7f365388d4_Screenshot_2025-12-04_at_1_19_04___PM.png', 'Screenshot 2025-12-04 at 1.19.04 PM.png', 'image/png', 398842, 596, 1280, 'assets/uploads/media/81a99cd3e9a44659bc089d7f365388d4_Screenshot_2025-12-04_at_1_19_04___PM.png', '/assets/uploads/media/81a99cd3e9a44659bc089d7f365388d4_Screenshot_2025-12-04_at_1_19_04___PM.png', '/assets/uploads/media/thumbs/81a99cd3e9a44659bc089d7f365388d4.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-09 06:03:55', NULL, '2025-12-09 06:04:26', NULL),
('82b8c257-1339-49b4-b820-890e6ad05c35', '82b8c257-1339-49b4-b820-890e6ad05c35.jpg', 'Copy of eye-of-model-with-colorful-art-make-up-close-up.jpg', 'image/jpeg', 82942, 612, 408, 'assets/uploads/media/82b8c257-1339-49b4-b820-890e6ad05c35.jpg', '/assets/uploads/media/82b8c257-1339-49b4-b820-890e6ad05c35.jpg', NULL, NULL, NULL, NULL, 1, NULL, NULL, 6, '2025-12-09 06:41:42', NULL, NULL, 'f32511c0-44a5-4567-ac65-c2d9a38bc23b'),
('86bcab9242cc463ab7dd86c6bc364e37', '86bcab9242cc463ab7dd86c6bc364e37_why_we_use_rose_for_baby_bath_powder_copy.jpg', 'why we use rose for baby bath powder copy.jpg', 'image/jpeg', 833600, 1850, 1080, 'assets/uploads/media/86bcab9242cc463ab7dd86c6bc364e37_why_we_use_rose_for_baby_bath_powder_copy.jpg', '/assets/uploads/media/86bcab9242cc463ab7dd86c6bc364e37_why_we_use_rose_for_baby_bath_powder_copy.jpg', '/assets/uploads/media/thumbs/86bcab9242cc463ab7dd86c6bc364e37.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-15 05:57:39', NULL, NULL, NULL),
('8ddc8b76-ff64-4ce2-9776-82cb71047e6f', 'd41a2287ac4f.jpeg', 'd41a2287ac4f.jpeg', 'image/jpeg', 13500, 237, 213, 'assets/uploads/products/d41a2287ac4f.jpeg', '/assets/uploads/products/d41a2287ac4f.jpeg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:28:28', NULL, NULL, '0e2fd299-9ec9-43be-bb7f-d0a30a0bee01'),
('8ecb0693-1ecc-4c3e-af24-a6ba7bc46fad', '1764970834-13255aa056.jpg', '1764970834-13255aa056.jpg', 'image/jpeg', 134927, 1920, 652, 'assets/uploads/banners/1764970834-13255aa056.jpg', '/assets/uploads/banners/1764970834-13255aa056.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:10:57', NULL, NULL, 'bc2a1d6c-7d89-49af-8404-9416491db457'),
('8ecb6bc1feb041c7be6944b94c23cd85', '8ecb6bc1feb041c7be6944b94c23cd85_our_services.jpg', 'our services.jpg', 'image/jpeg', 475376, 970, 600, 'assets/uploads/media/8ecb6bc1feb041c7be6944b94c23cd85_our_services.jpg', '/assets/uploads/media/8ecb6bc1feb041c7be6944b94c23cd85_our_services.jpg', '/assets/uploads/media/thumbs/8ecb6bc1feb041c7be6944b94c23cd85.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-29 10:47:07', NULL, NULL, NULL),
('8fe92d18e60846fa8ef1367fa46895d1', '8fe92d18e60846fa8ef1367fa46895d1_ALMOND_ROSE_INGREDIENTS_BENEFITS_copy.jpg', 'ALMOND ROSE INGREDIENTS BENEFITS copy.jpg', 'image/jpeg', 655242, 1100, 1100, 'assets/uploads/media/8fe92d18e60846fa8ef1367fa46895d1_ALMOND_ROSE_INGREDIENTS_BENEFITS_copy.jpg', '/assets/uploads/media/8fe92d18e60846fa8ef1367fa46895d1_ALMOND_ROSE_INGREDIENTS_BENEFITS_copy.jpg', '/assets/uploads/media/thumbs/8fe92d18e60846fa8ef1367fa46895d1.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2026-01-02 06:49:41', NULL, NULL, 'd0185db3-4942-4e71-a5a6-0be1af14e207'),
('9045ca3d839341e8a22c0bc82c75b79b', '9045ca3d839341e8a22c0bc82c75b79b_Screenshot_2025-12-09_at_11_14_29___AM.png', 'Screenshot 2025-12-09 at 11.14.29 AM.png', 'image/png', 1041879, 2384, 1290, 'assets/uploads/media/9045ca3d839341e8a22c0bc82c75b79b_Screenshot_2025-12-09_at_11_14_29___AM.png', '/assets/uploads/media/9045ca3d839341e8a22c0bc82c75b79b_Screenshot_2025-12-09_at_11_14_29___AM.png', '/assets/uploads/media/thumbs/9045ca3d839341e8a22c0bc82c75b79b.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-09 06:13:13', NULL, '2025-12-09 06:28:14', 'f32511c0-44a5-4567-ac65-c2d9a38bc23b'),
('961f7b6f-a2ff-4781-bec1-776ae5fdb835', '4e9800de60b9.jpg', '4e9800de60b9.jpg', 'image/jpeg', 59555, 600, 600, 'assets/uploads/products/4e9800de60b9.jpg', '/assets/uploads/products/4e9800de60b9.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-22 08:01:19', NULL, NULL, 'c8346eaf-d4d0-43b4-898e-6128305f952b'),
('97349f83605344fd85f94757bcfcd43d', '97349f83605344fd85f94757bcfcd43d_Screenshot_2025-12-07_at_7_47_46___AM.png', 'Screenshot 2025-12-07 at 7.47.46 AM.png', 'image/png', 90156, 2354, 1184, 'assets/uploads/media/97349f83605344fd85f94757bcfcd43d_Screenshot_2025-12-07_at_7_47_46___AM.png', '/assets/uploads/media/97349f83605344fd85f94757bcfcd43d_Screenshot_2025-12-07_at_7_47_46___AM.png', '/assets/uploads/media/thumbs/97349f83605344fd85f94757bcfcd43d.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-08 09:10:25', NULL, '2025-12-09 06:09:50', NULL),
('98c8d329-d46a-4304-bd26-ce1bd8ad33cb', '1441ad58f2a5.jpg', '1441ad58f2a5.jpg', 'image/jpeg', 172604, 1000, 1000, 'assets/uploads/products/1441ad58f2a5.jpg', '/assets/uploads/products/1441ad58f2a5.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-22 08:01:19', NULL, NULL, 'c8346eaf-d4d0-43b4-898e-6128305f952b'),
('9fca8228-1cdd-4ce8-bb53-627627654b6d', '1764972034-e710c14c90.jpg', '1764972034-e710c14c90.jpg', 'image/jpeg', 142885, 1600, 600, 'assets/uploads/banners/1764972034-e710c14c90.jpg', '/assets/uploads/banners/1764972034-e710c14c90.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:10:57', NULL, NULL, '50feb35c-434d-4b61-8683-6088a4235dfe'),
('a403826356174c769ad054d238d7a8b4', 'a403826356174c769ad054d238d7a8b4_HAPPY_NEW_YEAR.png', 'HAPPY NEW YEAR.png', 'image/png', 1698302, 1024, 1024, 'assets/uploads/media/a403826356174c769ad054d238d7a8b4_HAPPY_NEW_YEAR.png', '/assets/uploads/media/a403826356174c769ad054d238d7a8b4_HAPPY_NEW_YEAR.png', '/assets/uploads/media/thumbs/a403826356174c769ad054d238d7a8b4.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2026-01-02 07:24:37', NULL, NULL, NULL),
('a5633d20-1349-410e-b0cf-05426c6989db', '1763958370-45607e9389.webp', '1763958370-45607e9389.webp', 'image/webp', 94658, 1200, 1200, 'assets/uploads/banners/1763958370-45607e9389.webp', '/assets/uploads/banners/1763958370-45607e9389.webp', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:10:57', NULL, NULL, 'bc2a1d6c-7d89-49af-8404-9416491db457'),
('a6177d8096244da8aee7103c4fc8e525', 'a6177d8096244da8aee7103c4fc8e525_USP_Develixxirs.png', 'USP Develixxirs.png', 'image/png', 187741, 816, 306, 'assets/uploads/media/a6177d8096244da8aee7103c4fc8e525_USP_Develixxirs.png', '/assets/uploads/media/a6177d8096244da8aee7103c4fc8e525_USP_Develixxirs.png', '/assets/uploads/media/thumbs/a6177d8096244da8aee7103c4fc8e525.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-14 09:29:07', NULL, NULL, '06986aa8-ab0a-442b-a77b-51900ca11400'),
('aa3e7ec570ad462da74b7ed935e326c2', 'aa3e7ec570ad462da74b7ed935e326c2_growth_wit_us_sucessufully.jpg', 'growth wit us sucessufully.jpg', 'image/jpeg', 483826, 970, 600, 'assets/uploads/media/aa3e7ec570ad462da74b7ed935e326c2_growth_wit_us_sucessufully.jpg', '/assets/uploads/media/aa3e7ec570ad462da74b7ed935e326c2_growth_wit_us_sucessufully.jpg', '/assets/uploads/media/thumbs/aa3e7ec570ad462da74b7ed935e326c2.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-29 10:59:21', NULL, NULL, NULL),
('ab42e16e21fc4cd09d42bee857fa6118', 'ab42e16e21fc4cd09d42bee857fa6118_Screenshot_2025-12-08_at_12_59_58___PM.png', 'Screenshot 2025-12-08 at 12.59.58 PM.png', 'image/png', 552794, 2156, 776, 'assets/uploads/media/ab42e16e21fc4cd09d42bee857fa6118_Screenshot_2025-12-08_at_12_59_58___PM.png', '/assets/uploads/media/ab42e16e21fc4cd09d42bee857fa6118_Screenshot_2025-12-08_at_12_59_58___PM.png', '/assets/uploads/media/thumbs/ab42e16e21fc4cd09d42bee857fa6118.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-08 09:10:25', NULL, '2025-12-09 06:09:50', NULL),
('ac63a2ad-3725-4312-9494-5fb29577b44c', '1764158002-597297e414.webp', '1764158002-597297e414.webp', 'image/webp', 157982, 1080, 1080, 'assets/uploads/banners/1764158002-597297e414.webp', '/assets/uploads/banners/1764158002-597297e414.webp', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:10:57', NULL, NULL, '50feb35c-434d-4b61-8683-6088a4235dfe'),
('ac68be58-be95-4f34-b062-053d881ef1b7', '1763959838-c8778da95d.jpg', '1763959838-c8778da95d.jpg', 'image/jpeg', 433300, 2560, 1707, 'assets/uploads/categories/1763959838-c8778da95d.jpg', '/assets/uploads/categories/1763959838-c8778da95d.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:28:28', NULL, NULL, '8de1e065-5a5b-45ac-9f53-fc0ca7688681'),
('aecdfbe5eec143e78e9a6267ce95af77', 'aecdfbe5eec143e78e9a6267ce95af77_Screenshot_2025-12-08_at_10_52_48___AM.png', 'Screenshot 2025-12-08 at 10.52.48 AM.png', 'image/png', 44537, 970, 586, 'assets/uploads/media/aecdfbe5eec143e78e9a6267ce95af77_Screenshot_2025-12-08_at_10_52_48___AM.png', '/assets/uploads/media/aecdfbe5eec143e78e9a6267ce95af77_Screenshot_2025-12-08_at_10_52_48___AM.png', '/assets/uploads/media/thumbs/aecdfbe5eec143e78e9a6267ce95af77.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-08 09:10:25', NULL, '2025-12-09 06:09:50', NULL),
('b1d5795c0f81492abb436300e45f5aaf', 'b1d5795c0f81492abb436300e45f5aaf_pngwing_com-9.jpg', 'pngwing.com-9.jpg', 'image/jpeg', 17034, 616, 580, 'assets/uploads/media/b1d5795c0f81492abb436300e45f5aaf_pngwing_com-9.jpg', '/assets/uploads/media/b1d5795c0f81492abb436300e45f5aaf_pngwing_com-9.jpg', '/assets/uploads/media/thumbs/b1d5795c0f81492abb436300e45f5aaf.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2026-01-04 05:13:50', NULL, NULL, NULL),
('b3159e6b-58a4-46a8-b6e0-2058e5124d2f', '1764972257-4070050f1c.jpg', '1764972257-4070050f1c.jpg', 'image/jpeg', 171149, 1080, 1080, 'assets/uploads/banners/1764972257-4070050f1c.jpg', '/assets/uploads/banners/1764972257-4070050f1c.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:10:57', NULL, NULL, '50feb35c-434d-4b61-8683-6088a4235dfe'),
('b49723cb-145a-43fc-b2ca-130ede448656', '459a32ced2ab.jpg', '459a32ced2ab.jpg', 'image/jpeg', 281339, 1000, 1000, 'assets/uploads/products/459a32ced2ab.jpg', '/assets/uploads/products/459a32ced2ab.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:28:28', NULL, NULL, '49f6f691-d59b-4cdd-88b1-5b54437bc04e'),
('b4fc6cea6190448eaef9a4d230c0bc25', 'b4fc6cea6190448eaef9a4d230c0bc25_Screenshot_2025-12-07_at_8_34_22___AM.png', 'Screenshot 2025-12-07 at 8.34.22 AM.png', 'image/png', 28463, 1708, 156, 'assets/uploads/media/b4fc6cea6190448eaef9a4d230c0bc25_Screenshot_2025-12-07_at_8_34_22___AM.png', '/assets/uploads/media/b4fc6cea6190448eaef9a4d230c0bc25_Screenshot_2025-12-07_at_8_34_22___AM.png', '/assets/uploads/media/thumbs/b4fc6cea6190448eaef9a4d230c0bc25.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-08 09:10:25', NULL, '2025-12-09 06:09:50', NULL),
('b636e74bc8114711b181fa0824fd9e75', 'b636e74bc8114711b181fa0824fd9e75_fott_cream_soft_smooth_copy.jpg', 'fott cream soft smooth copy.jpg', 'image/jpeg', 140768, 600, 600, 'assets/uploads/media/b636e74bc8114711b181fa0824fd9e75_fott_cream_soft_smooth_copy.jpg', '/assets/uploads/media/b636e74bc8114711b181fa0824fd9e75_fott_cream_soft_smooth_copy.jpg', '/assets/uploads/media/thumbs/b636e74bc8114711b181fa0824fd9e75.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-18 05:02:12', NULL, NULL, NULL),
('b91be6c475de4697bc62b94560e4a455', 'b91be6c475de4697bc62b94560e4a455_Screenshot_2025-12-07_at_9_44_26___AM.png', 'Screenshot 2025-12-07 at 9.44.26 AM.png', 'image/png', 152236, 1708, 764, 'assets/uploads/media/b91be6c475de4697bc62b94560e4a455_Screenshot_2025-12-07_at_9_44_26___AM.png', '/assets/uploads/media/b91be6c475de4697bc62b94560e4a455_Screenshot_2025-12-07_at_9_44_26___AM.png', '/assets/uploads/media/thumbs/b91be6c475de4697bc62b94560e4a455.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-08 09:10:25', NULL, '2025-12-09 06:09:50', NULL),
('bb902cfd00d44fa9864a4d62434aa7cd', 'bb902cfd00d44fa9864a4d62434aa7cd_FOOT_CREAM_BEFORE_AFER_copy.jpg', 'FOOT CREAM BEFORE AFER copy.jpg', 'image/jpeg', 129038, 600, 600, 'assets/uploads/media/bb902cfd00d44fa9864a4d62434aa7cd_FOOT_CREAM_BEFORE_AFER_copy.jpg', '/assets/uploads/media/bb902cfd00d44fa9864a4d62434aa7cd_FOOT_CREAM_BEFORE_AFER_copy.jpg', '/assets/uploads/media/thumbs/bb902cfd00d44fa9864a4d62434aa7cd.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-18 05:00:15', NULL, NULL, NULL),
('bc6375d5-f601-4a89-b611-009de8a4c59a', '1764435777-d0e8c6cb13.jpg', '1764435777-d0e8c6cb13.jpg', 'image/jpeg', 224714, 1366, 500, 'assets/uploads/banners/1764435777-d0e8c6cb13.jpg', '/assets/uploads/banners/1764435777-d0e8c6cb13.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:10:57', NULL, NULL, '4b935e8c-762a-41d5-8518-c46a9d9c1f63'),
('bdda9906-a534-437f-9e5d-7c7afc8fdac9', '8466a309669c.jpg', '8466a309669c.jpg', 'image/jpeg', 177309, 1000, 1000, 'assets/uploads/products/8466a309669c.jpg', '/assets/uploads/products/8466a309669c.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-22 08:01:19', NULL, NULL, 'c8346eaf-d4d0-43b4-898e-6128305f952b'),
('c7d6cd1c1bda499c91a9cc3ed4143ba0', 'c7d6cd1c1bda499c91a9cc3ed4143ba0_Screenshot_2025-12-08_at_7_04_27___AM.png', 'Screenshot 2025-12-08 at 7.04.27 AM.png', 'image/png', 1940941, 2876, 1372, 'assets/uploads/media/c7d6cd1c1bda499c91a9cc3ed4143ba0_Screenshot_2025-12-08_at_7_04_27___AM.png', '/assets/uploads/media/c7d6cd1c1bda499c91a9cc3ed4143ba0_Screenshot_2025-12-08_at_7_04_27___AM.png', '/assets/uploads/media/thumbs/c7d6cd1c1bda499c91a9cc3ed4143ba0.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-08 09:10:24', NULL, '2025-12-09 06:09:50', NULL),
('c90042939ae447049374c51e784d51fa', 'c90042939ae447049374c51e784d51fa_Screenshot_2025-12-07_at_1_20_00___PM.png', 'Screenshot 2025-12-07 at 1.20.00 PM.png', 'image/png', 54558, 1708, 370, 'assets/uploads/media/c90042939ae447049374c51e784d51fa_Screenshot_2025-12-07_at_1_20_00___PM.png', '/assets/uploads/media/c90042939ae447049374c51e784d51fa_Screenshot_2025-12-07_at_1_20_00___PM.png', '/assets/uploads/media/thumbs/c90042939ae447049374c51e784d51fa.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-08 09:10:25', NULL, '2025-12-09 06:09:50', NULL),
('c9874c81-68af-41bd-9609-5bb463751008', '46118cba7a37.JPG', '46118cba7a37.JPG', 'image/jpeg', 638011, 1920, 1440, 'assets/uploads/products/46118cba7a37.JPG', '/assets/uploads/products/46118cba7a37.JPG', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:28:28', NULL, NULL, '8cc3bf31-1932-41f2-9030-b1f72fbfa667'),
('cd51ac0a6b3c4451afd1246de2f38c21', 'cd51ac0a6b3c4451afd1246de2f38c21_baby_care_develixir_png.png', 'baby care develixir.png.png', 'image/png', 499378, 1080, 1080, 'assets/uploads/media/cd51ac0a6b3c4451afd1246de2f38c21_baby_care_develixir_png.png', '/assets/uploads/media/cd51ac0a6b3c4451afd1246de2f38c21_baby_care_develixir_png.png', '/assets/uploads/media/thumbs/cd51ac0a6b3c4451afd1246de2f38c21.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-24 20:02:25', NULL, NULL, '06986aa8-ab0a-442b-a77b-51900ca11400'),
('cfcbb89224cc44e7a1d19477ad7f4298', 'cfcbb89224cc44e7a1d19477ad7f4298_Almond_Rose_Baby_Bath_Powder_Gentle_Mother_Trusted_copy.jpg', 'Almond Rose Baby Bath Powder Gentle Mother Trusted copy.jpg', 'image/jpeg', 618065, 1100, 1100, 'assets/uploads/media/cfcbb89224cc44e7a1d19477ad7f4298_Almond_Rose_Baby_Bath_Powder_Gentle_Mother_Trusted_copy.jpg', '/assets/uploads/media/cfcbb89224cc44e7a1d19477ad7f4298_Almond_Rose_Baby_Bath_Powder_Gentle_Mother_Trusted_copy.jpg', '/assets/uploads/media/thumbs/cfcbb89224cc44e7a1d19477ad7f4298.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2026-01-02 06:49:41', NULL, NULL, 'd0185db3-4942-4e71-a5a6-0be1af14e207'),
('d00b7c7921904f6f957bf92ffd604169', 'd00b7c7921904f6f957bf92ffd604169_skin_care__develixir_png.png', 'skin care  develixir.png.png', 'image/png', 142555, 1080, 1080, 'assets/uploads/media/d00b7c7921904f6f957bf92ffd604169_skin_care__develixir_png.png', '/assets/uploads/media/d00b7c7921904f6f957bf92ffd604169_skin_care__develixir_png.png', '/assets/uploads/media/thumbs/d00b7c7921904f6f957bf92ffd604169.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-24 20:02:25', NULL, NULL, '06986aa8-ab0a-442b-a77b-51900ca11400'),
('d20faac1e6e24269adeb9a10abe761f2', 'd20faac1e6e24269adeb9a10abe761f2_download_-_2025-10-17T151914_343.jpeg', 'download - 2025-10-17T151914.343.jpeg', 'image/jpeg', 6190, 291, 173, 'assets/uploads/media/d20faac1e6e24269adeb9a10abe761f2_download_-_2025-10-17T151914_343.jpeg', '/assets/uploads/media/d20faac1e6e24269adeb9a10abe761f2_download_-_2025-10-17T151914_343.jpeg', '/assets/uploads/media/thumbs/d20faac1e6e24269adeb9a10abe761f2.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2026-01-02 06:48:59', NULL, '2026-01-02 06:49:09', '646482e4-41f3-4d10-b453-b64c859bbe49');
INSERT INTO `media_files` (`id`, `filename`, `original_filename`, `mime_type`, `size`, `width`, `height`, `storage_path`, `cdn_url`, `thumb_url`, `alt_text`, `title`, `description`, `is_favorite`, `colors`, `exif`, `uploaded_by`, `uploaded_at`, `last_used_at`, `deleted_at`, `folder_id`) VALUES
('d3ac73574ca74f98b54d500287a91f40', 'd3ac73574ca74f98b54d500287a91f40_Screenshot_2025-12-08_at_7_10_56___AM.png', 'Screenshot 2025-12-08 at 7.10.56 AM.png', 'image/png', 1998972, 2876, 1372, 'assets/uploads/media/d3ac73574ca74f98b54d500287a91f40_Screenshot_2025-12-08_at_7_10_56___AM.png', '/assets/uploads/media/d3ac73574ca74f98b54d500287a91f40_Screenshot_2025-12-08_at_7_10_56___AM.png', '/assets/uploads/media/thumbs/d3ac73574ca74f98b54d500287a91f40.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-08 09:10:25', NULL, '2025-12-09 06:09:50', NULL),
('d9951cf4-23f9-49f9-8b27-0bc8c2f5d009', 'd4d74218deb0.jpg', 'd4d74218deb0.jpg', 'image/jpeg', 201869, 1100, 1100, 'assets/uploads/products/d4d74218deb0.jpg', '/assets/uploads/products/d4d74218deb0.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:28:28', NULL, NULL, '1d293e96-95e9-49d1-87ab-664459220334'),
('dbaf085c-a4c3-496c-bb4a-bbe80614252e', '1763959656-b75cdf673b.webp', '1763959656-b75cdf673b.webp', 'image/webp', 586512, 5744, 3840, 'assets/uploads/categories/1763959656-b75cdf673b.webp', '/assets/uploads/categories/1763959656-b75cdf673b.webp', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:28:28', NULL, NULL, '894083d9-3a0a-4499-b58c-57de8d88ec20'),
('e0147d738e9b45feac333570e7cafbc0', 'e0147d738e9b45feac333570e7cafbc0_almond_rose_baby_bath_powder_for_skin.jpg', 'almond rose baby bath powder for skin.jpg', 'image/jpeg', 422690, 1100, 1100, 'assets/uploads/media/e0147d738e9b45feac333570e7cafbc0_almond_rose_baby_bath_powder_for_skin.jpg', '/assets/uploads/media/e0147d738e9b45feac333570e7cafbc0_almond_rose_baby_bath_powder_for_skin.jpg', '/assets/uploads/media/thumbs/e0147d738e9b45feac333570e7cafbc0.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2026-01-02 06:49:41', NULL, NULL, 'd0185db3-4942-4e71-a5a6-0be1af14e207'),
('e194fca0-8302-460d-a92a-93571f2200e5', '1764976238-8b40744d3e.png', '1764976238-8b40744d3e.png', 'image/png', 1297370, 1024, 1024, 'assets/uploads/categories/1764976238-8b40744d3e.png', '/assets/uploads/categories/1764976238-8b40744d3e.png', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:28:28', NULL, NULL, '58390f7f-437d-42ea-a373-66932e4aebbf'),
('e3d07ed8-2d4a-45ea-8916-57a738d51c0f', '1763899414-4b34b58dbd5b.jpg', '1763899414-4b34b58dbd5b.jpg', 'image/jpeg', 95863, 1250, 500, 'assets/uploads/banners/1763899414-4b34b58dbd5b.jpg', '/assets/uploads/banners/1763899414-4b34b58dbd5b.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:10:57', NULL, NULL, 'bc2a1d6c-7d89-49af-8404-9416491db457'),
('ebbe7188bc5143bfbd04889b6c4a5422', 'ebbe7188bc5143bfbd04889b6c4a5422_Rooted_in_Ayurveda__Crafted_with_Integrity.jpg', 'Rooted in Ayurveda. Crafted with Integrity.jpg', 'image/jpeg', 1035285, 1024, 1024, 'assets/uploads/media/ebbe7188bc5143bfbd04889b6c4a5422_Rooted_in_Ayurveda__Crafted_with_Integrity.jpg', '/assets/uploads/media/ebbe7188bc5143bfbd04889b6c4a5422_Rooted_in_Ayurveda__Crafted_with_Integrity.jpg', '/assets/uploads/media/thumbs/ebbe7188bc5143bfbd04889b6c4a5422.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-29 13:02:20', NULL, NULL, NULL),
('ef957689-0881-4411-ab77-9edc0659e233', '1766388075-084559bb04.jpg', '1766388075-084559bb04.jpg', 'image/jpeg', 152062, 1600, 600, 'assets/uploads/banners/1766388075-084559bb04.jpg', '/assets/uploads/banners/1766388075-084559bb04.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-22 08:01:19', NULL, NULL, '4ba01a5b-7754-4e1e-8c45-b4315d588d95'),
('f1af4b6cb83947a9aa76ff41d6b82c8d', 'f1af4b6cb83947a9aa76ff41d6b82c8d_Vatyalaka_Sida_cordifolia_The_Ayurvedic_Herb_of_Strength_and_Skin_Vitality_copy.jpg', 'Vatyalaka Sida cordifolia The Ayurvedic Herb of Strength and Skin Vitality copy.jpg', 'image/jpeg', 853697, 1200, 630, 'assets/uploads/media/f1af4b6cb83947a9aa76ff41d6b82c8d_Vatyalaka_Sida_cordifolia_The_Ayurvedic_Herb_of_Strength_and_Skin_Vitality_copy.jpg', '/assets/uploads/media/f1af4b6cb83947a9aa76ff41d6b82c8d_Vatyalaka_Sida_cordifolia_The_Ayurvedic_Herb_of_Strength_and_Skin_Vitality_copy.jpg', '/assets/uploads/media/thumbs/f1af4b6cb83947a9aa76ff41d6b82c8d.jpg', 'Vatyalaka, or Sida cordifolia (Bala), a low-growing Ayurvedic medicinal plant with heart-shaped leaves, light yellow/cream flowers, and distinctive segmented, dry seed capsules, photographed in a dry, sunny landscape.', 'The Ayurvedic herb Vatyalaka (Sida cordifolia)', '', 0, NULL, NULL, 6, '2025-12-13 11:12:07', NULL, NULL, '4b935e8c-762a-41d5-8518-c46a9d9c1f63'),
('f271efed2a6f4a988743310b0f02187a', 'f271efed2a6f4a988743310b0f02187a_triphala_fruits_image.jfif', 'triphala fruits image.jfif', 'image/jpeg', 4541, 246, 148, 'assets/uploads/media/f271efed2a6f4a988743310b0f02187a_triphala_fruits_image.jfif', '/assets/uploads/media/f271efed2a6f4a988743310b0f02187a_triphala_fruits_image.jfif', '/assets/uploads/media/thumbs/f271efed2a6f4a988743310b0f02187a.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2026-01-02 07:36:01', NULL, NULL, NULL),
('f52a8724fcd941e6a8d1431a4c14b6ca', 'f52a8724fcd941e6a8d1431a4c14b6ca_Screenshot_2025-12-04_at_1_19_04___PM.png', 'Screenshot 2025-12-04 at 1.19.04 PM.png', 'image/png', 398842, 596, 1280, 'assets/uploads/media/f52a8724fcd941e6a8d1431a4c14b6ca_Screenshot_2025-12-04_at_1_19_04___PM.png', '/assets/uploads/media/f52a8724fcd941e6a8d1431a4c14b6ca_Screenshot_2025-12-04_at_1_19_04___PM.png', '/assets/uploads/media/thumbs/f52a8724fcd941e6a8d1431a4c14b6ca.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-08 09:10:25', NULL, '2025-12-09 06:09:50', NULL),
('f6792d5e-43b2-4f41-a78f-8afef0c9a8bc', '1764971794-a1c6dc7606.jpg', '1764971794-a1c6dc7606.jpg', 'image/jpeg', 100239, 1000, 500, 'assets/uploads/banners/1764971794-a1c6dc7606.jpg', '/assets/uploads/banners/1764971794-a1c6dc7606.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:10:57', NULL, NULL, 'bc2a1d6c-7d89-49af-8404-9416491db457'),
('f8b1f7c2-071f-49f4-9019-709744a7bf09', '1764032777-07bb693f30.jpeg', '1764032777-07bb693f30.jpeg', 'image/jpeg', 153380, 1600, 583, 'assets/uploads/banners/1764032777-07bb693f30.jpeg', '/assets/uploads/banners/1764032777-07bb693f30.jpeg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-12-09 08:10:57', NULL, NULL, '50feb35c-434d-4b61-8683-6088a4235dfe'),
('fa82a5c834814ff98d2ffc718c218df5', 'fa82a5c834814ff98d2ffc718c218df5_Screenshot_2025-12-07_at_8_49_35___AM.png', 'Screenshot 2025-12-07 at 8.49.35 AM.png', 'image/png', 38999, 1708, 258, 'assets/uploads/media/fa82a5c834814ff98d2ffc718c218df5_Screenshot_2025-12-07_at_8_49_35___AM.png', '/assets/uploads/media/fa82a5c834814ff98d2ffc718c218df5_Screenshot_2025-12-07_at_8_49_35___AM.png', '/assets/uploads/media/thumbs/fa82a5c834814ff98d2ffc718c218df5.jpg', NULL, NULL, NULL, 0, NULL, NULL, 6, '2025-12-08 09:10:25', NULL, '2025-12-09 06:04:39', NULL),
('fbbafab2549643cbac3385d9f9e93531', 'fbbafab2549643cbac3385d9f9e93531_Furniture_Sale_Instagram_stories_template.png', 'Furniture Sale Instagram stories template.png', 'image/png', 3110164, 1080, 1920, 'assets/uploads/media/fbbafab2549643cbac3385d9f9e93531_Furniture_Sale_Instagram_stories_template.png', '/assets/uploads/media/fbbafab2549643cbac3385d9f9e93531_Furniture_Sale_Instagram_stories_template.png', '/assets/uploads/media/thumbs/fbbafab2549643cbac3385d9f9e93531.jpg', NULL, NULL, NULL, 1, NULL, NULL, 6, '2025-12-12 07:33:05', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `media_file_tags`
--

CREATE TABLE `media_file_tags` (
  `media_id` char(36) NOT NULL,
  `tag_id` char(36) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `media_file_tags`
--

INSERT INTO `media_file_tags` (`media_id`, `tag_id`) VALUES
('252c457bce664e08ad84749e470e8fc5', '21232960c4eb4996987f843d64a73ba8'),
('252c457bce664e08ad84749e470e8fc5', '8cd87a160e1e441185dcfb2aab93403b'),
('252c457bce664e08ad84749e470e8fc5', '932f4f2a472b4cbbb583922b989726a1'),
('252c457bce664e08ad84749e470e8fc5', 'd7abd633dd6f423d9e588fb6c4e58965'),
('252c457bce664e08ad84749e470e8fc5', 'e7fedf940482486eb2b6e61dabce4940');

-- --------------------------------------------------------

--
-- Table structure for table `media_folders`
--

CREATE TABLE `media_folders` (
  `id` char(36) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `parent_id` char(36) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `media_folders`
--

INSERT INTO `media_folders` (`id`, `name`, `parent_id`, `created_at`) VALUES
('06986aa8-ab0a-442b-a77b-51900ca11400', 'Dev Elixir Icons', NULL, '2025-12-14 09:28:38'),
('0e2fd299-9ec9-43be-bb7f-d0a30a0bee01', 'Manjishta – Skin Care Benefits', '31bc83af-2bec-4111-b605-aa79d67bdf02', '2025-12-09 07:33:57'),
('1464a7bf-26e2-4f4a-a0d1-34f071cb74b2', 'women care', '30085790-d8ca-41b5-8282-17992cd37b4f', '2025-12-09 07:45:36'),
('1d293e96-95e9-49d1-87ab-664459220334', 'Baby Girl Bath Powder 21 Ayurvedic Herbs Nalangu Ma Even Skin tone Ubtan 100 gm', '31bc83af-2bec-4111-b605-aa79d67bdf02', '2025-12-09 07:35:22'),
('22112f89-984e-4e68-98af-9cb469eb07de', 'Face wash', '30085790-d8ca-41b5-8282-17992cd37b4f', '2025-12-09 07:44:10'),
('2c319bbf-6452-4380-a588-a20b43c52fa1', 'Blog', '30085790-d8ca-41b5-8282-17992cd37b4f', '2025-12-09 08:07:12'),
('30078477-6870-4ad0-a440-df9bcbf4a053', 'beauty tips', '30085790-d8ca-41b5-8282-17992cd37b4f', '2025-12-09 08:07:12'),
('30085790-d8ca-41b5-8282-17992cd37b4f', 'Categories', NULL, '2025-12-09 07:40:57'),
('31bc83af-2bec-4111-b605-aa79d67bdf02', 'Products', NULL, '2025-12-09 07:21:36'),
('33fa097e-6d39-410c-b323-08ff9af68f77', 'oralcare', '30085790-d8ca-41b5-8282-17992cd37b4f', '2025-12-09 08:07:12'),
('49f6f691-d59b-4cdd-88b1-5b54437bc04e', 'Nalpamaradi Kera Thailam Bridal Mom Baby Skin Beauty', '31bc83af-2bec-4111-b605-aa79d67bdf02', '2025-12-09 07:31:35'),
('4b935e8c-762a-41d5-8518-c46a9d9c1f63', 'Banners', '968a704b-8e1b-4bc4-9c79-2f3b50b11bef', '2025-12-09 08:20:43'),
('4ba01a5b-7754-4e1e-8c45-b4315d588d95', 'Banners', '92f71e3c-db87-483e-8ed5-2d56eebc9309', '2025-12-09 08:20:43'),
('4cfd30c1-f938-4f9b-92f7-3a9638e048bc', 'Tooth Powder Strengthen Gums Whiten Teeth Ayurvedic Kadhira', '31bc83af-2bec-4111-b605-aa79d67bdf02', '2025-12-22 08:01:19'),
('50feb35c-434d-4b61-8683-6088a4235dfe', 'Banners', 'e7df378b-ca07-477b-99bd-fb6db4bd6523', '2025-12-09 08:20:43'),
('58390f7f-437d-42ea-a373-66932e4aebbf', 'skincare', '30085790-d8ca-41b5-8282-17992cd37b4f', '2025-12-09 08:06:35'),
('61188bfc-ae9e-4456-81a9-7e51712843ed', 'Podcast', '30085790-d8ca-41b5-8282-17992cd37b4f', '2025-12-09 08:07:12'),
('646482e4-41f3-4d10-b453-b64c859bbe49', 'Baby care', '30085790-d8ca-41b5-8282-17992cd37b4f', '2025-12-09 07:44:31'),
('66eca1b6-e854-49f3-a0bc-02725e2b45e6', 'test', 'bc2a1d6c-7d89-49af-8404-9416491db457', '2025-12-09 08:55:27'),
('76c70325-9f30-4ce2-8d4f-2061aeebeaac', 'Men Care', '30085790-d8ca-41b5-8282-17992cd37b4f', '2025-12-09 07:41:53'),
('7cbbd308-e0a7-47ca-b08e-67f9f799dd7f', 'Folder', 'f32511c0-44a5-4567-ac65-c2d9a38bc23b', '2025-12-09 07:36:46'),
('7eeb2b6b-170d-46a5-942a-27c7ccc0f6c4', 'testing', NULL, '2025-12-09 08:46:11'),
('7f0e34f2-9e51-4419-8ad4-6bba0a12b2c2', 'Homepage', NULL, '2025-12-09 08:07:12'),
('8582551c-70b9-439c-b3b1-ce571aa26f35', 'Baby girl Bath powder | Natural skin glowing Nalanguma | Wild Turmeric Rose Green grams Sandalwood Ubtan', '31bc83af-2bec-4111-b605-aa79d67bdf02', '2025-12-22 08:01:19'),
('894083d9-3a0a-4499-b58c-57de8d88ec20', 'Hair oil', '30085790-d8ca-41b5-8282-17992cd37b4f', '2025-12-09 07:43:49'),
('8cc3bf31-1932-41f2-9030-b1f72fbfa667', 'test', '31bc83af-2bec-4111-b605-aa79d67bdf02', '2025-12-09 07:39:41'),
('8de1e065-5a5b-45ac-9f53-fc0ca7688681', 'Hair Wash', '30085790-d8ca-41b5-8282-17992cd37b4f', '2025-12-09 07:44:27'),
('92f71e3c-db87-483e-8ed5-2d56eebc9309', 'Categories Page', NULL, '2025-12-09 08:20:43'),
('968a704b-8e1b-4bc4-9c79-2f3b50b11bef', 'Blog Page', NULL, '2025-12-09 08:20:43'),
('9aeba734-b6af-4b96-98ba-7917f4dd33c9', 'Hair Care', '30085790-d8ca-41b5-8282-17992cd37b4f', '2025-12-09 07:45:41'),
('a3ad7d4a-bdd2-461e-8433-02139a056552', 'videos', '30085790-d8ca-41b5-8282-17992cd37b4f', '2025-12-09 08:07:12'),
('b9a65b37-974b-4ef8-abe9-e114c897447f', 'Rose Gel | Radiant Essence Skin Gel | Face & Body glow', '31bc83af-2bec-4111-b605-aa79d67bdf02', '2025-12-09 07:33:24'),
('bc2a1d6c-7d89-49af-8404-9416491db457', 'Banners', '7f0e34f2-9e51-4419-8ad4-6bba0a12b2c2', '2025-12-09 08:07:12'),
('c8346eaf-d4d0-43b4-898e-6128305f952b', 'Shikakai Hair Washes Powder | Deep Cleanses Strengthens & Stimulates Hair Growth |27 Ayuvedic Herbs Non-Foaming', '31bc83af-2bec-4111-b605-aa79d67bdf02', '2025-12-22 08:01:19'),
('d0185db3-4942-4e71-a5a6-0be1af14e207', 'Almond Rose Baby Bath Powder', '646482e4-41f3-4d10-b453-b64c859bbe49', '2026-01-02 06:47:49'),
('e7df378b-ca07-477b-99bd-fb6db4bd6523', 'Products Page', NULL, '2025-12-09 08:20:43'),
('eeb9429d-030c-41b9-a451-22bea09a8421', 'Lip care', '30085790-d8ca-41b5-8282-17992cd37b4f', '2025-12-09 08:07:12'),
('f32511c0-44a5-4567-ac65-c2d9a38bc23b', 'test', NULL, '2025-12-09 05:58:05'),
('f8077188-87dc-4fe7-ac76-a1cadc94b224', 'Ingredients', '30085790-d8ca-41b5-8282-17992cd37b4f', '2025-12-09 08:07:12');

-- --------------------------------------------------------

--
-- Table structure for table `media_tags`
--

CREATE TABLE `media_tags` (
  `id` char(36) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `slug` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `media_tags`
--

INSERT INTO `media_tags` (`id`, `name`, `slug`, `created_at`) VALUES
('21232960c4eb4996987f843d64a73ba8', 'facemask', 'facemask', '2025-12-14 06:37:53'),
('8cd87a160e1e441185dcfb2aab93403b', 'pigmentation', 'pigmentation', '2025-12-14 06:37:53'),
('932f4f2a472b4cbbb583922b989726a1', 'ubtan', 'ubtan', '2025-12-14 06:37:53'),
('d7abd633dd6f423d9e588fb6c4e58965', 'detan', 'detan', '2025-12-14 06:37:53'),
('e7fedf940482486eb2b6e61dabce4940', 'facepack', 'facepack', '2025-12-14 06:37:53');

-- --------------------------------------------------------

--
-- Table structure for table `media_usage`
--

CREATE TABLE `media_usage` (
  `id` char(36) NOT NULL,
  `media_id` char(36) DEFAULT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `field_name` varchar(100) DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `media_variants`
--

CREATE TABLE `media_variants` (
  `id` char(36) NOT NULL,
  `media_id` char(36) DEFAULT NULL,
  `variant_name` varchar(50) DEFAULT NULL,
  `format` varchar(10) DEFAULT NULL,
  `url` text DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `size` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `media_versions`
--

CREATE TABLE `media_versions` (
  `id` char(36) NOT NULL,
  `media_id` char(36) DEFAULT NULL,
  `version_number` int(11) DEFAULT NULL,
  `storage_path` text DEFAULT NULL,
  `url` text DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `title`, `message`, `url`, `is_read`, `created_at`) VALUES
(1, 'Welcome', 'Your admin panel is ready', '/admin/dashboard.php', 1, '2025-11-18 16:53:23'),
(2, 'New Order', 'Order #102 received', '/admin/orders.php?id=102', 1, '2025-11-18 16:53:23'),
(3, 'New Order #order_Rn2WRuatlT4a46', 'Customer Chandru Prasath placed a new order of ₹479.00', 'order_view.php?id=13', 1, '2025-12-03 06:28:30'),
(4, 'New Order #order_Rn2oHAyy1DObwY', 'Customer Chandru Prasath placed a new order of ₹3,600.00', 'order_view.php?id=14', 1, '2025-12-03 06:45:25'),
(5, 'New Order #order_Rn2zHG4bRxQQDd', 'Customer Chandru Prasath placed a new order of ₹5,760.00', 'order_view.php?id=15', 1, '2025-12-03 06:55:49'),
(6, 'New Order #order_RnldtWoaTvOfPb', 'Customer Dev Elixir Natural Cosmetics placed a new order of ₹3,240.00', 'order_view.php?id=16', 1, '2025-12-05 02:36:51'),
(7, 'New Order #order_RqgTzNGDZeK1Uz', 'Customer Dev Elixir Natural Cosmetics placed a new order of ₹3,600.00', 'order_view.php?id=17', 0, '2025-12-12 11:31:08'),
(8, 'New Order #order_RqvQYGJrjKgUuT', 'Customer Dev Elixir Natural Cosmetics placed a new order of ₹479.00', 'order_view.php?id=18', 0, '2025-12-13 02:08:07');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `coupon_code` varchar(50) DEFAULT NULL,
  `coupon_discount` decimal(10,2) DEFAULT 0.00,
  `status` varchar(50) DEFAULT 'processing',
  `payment_status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shipping_charge` decimal(10,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `order_number` varchar(100) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_address` text DEFAULT NULL,
  `order_status` varchar(50) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total`, `coupon_code`, `coupon_discount`, `status`, `payment_status`, `created_at`, `total_amount`, `shipping_charge`, `tax_amount`, `order_number`, `customer_name`, `customer_address`, `order_status`) VALUES
(1, NULL, 0.00, NULL, 0.00, 'processing', 'paid', '2025-11-19 03:46:37', 1499.00, 0.00, 228.66, 'ORD-2025-001', 'Dummy Customer 1', NULL, 'delivered'),
(2, NULL, 0.00, NULL, 0.00, 'processing', 'paid', '2025-11-19 03:46:37', 2499.50, 0.00, 381.28, 'ORD-2025-002', 'Dummy Customer 2', NULL, 'delivered'),
(3, NULL, 0.00, NULL, 0.00, 'processing', 'paid', '2025-11-19 03:46:37', 999.00, 0.00, 152.39, 'ORD-2025-003', 'Dummy Customer 3', NULL, 'processing'),
(4, NULL, 0.00, NULL, 0.00, 'processing', 'paid', '2025-11-19 03:46:37', 3299.00, 0.00, 503.24, 'ORD-2025-004', 'Dummy Customer 4', NULL, 'delivered'),
(5, NULL, 0.00, NULL, 0.00, 'processing', 'paid', '2025-11-19 03:46:37', 499.00, 0.00, 76.12, 'ORD-2025-005', 'Dummy Customer 5', NULL, 'processing'),
(6, 2, 0.00, NULL, 0.00, 'processing', 'pending', '2025-12-02 18:12:20', 1995.00, 0.00, 304.32, 'ORD-20251202-4265C5', 'Chandru Prasath', 'Chandru Sri\\n18/4balgangathara Thilagar St\\n18\\nVellore, Tamil Nadu - 632602\\nPhone: 08946071785', 'packed'),
(7, 2, 3240.00, 'DEVOKK9J79HQG', 360.00, 'processing', 'paid', '2025-12-03 01:43:58', 3240.00, 0.00, 494.24, 'order_Rmxfo5eBy77NKK', 'Chandru Prasath', 'Chandru Sri\\n18/4balgangathara Thilagar St\\n18\\nVellore, Tamil Nadu - 632602\\nPhone: 08946071785', 'processing'),
(8, 2, 3240.00, 'DEVOKK9J79HQG', 360.00, 'processing', 'paid', '2025-12-03 01:48:23', 3240.00, 0.00, 494.24, 'order_RmxkdpAGDD2zpa', 'Chandru Prasath', 'Chandru Sri\\n18/4balgangathara Thilagar St\\n18\\nVellore, Tamil Nadu - 632602\\nPhone: 08946071785', 'processing'),
(9, 2, 439.10, 'DEVOKK9J79HQG', 39.90, 'processing', 'paid', '2025-12-03 01:53:13', 439.10, 0.00, 66.98, 'order_RmxpjZ5uJ3hhfx', 'Chandru Prasath', 'Chandru Sri\\n18/4balgangathara Thilagar St\\n18\\nVellore, Tamil Nadu - 632602\\nPhone: 08946071785', 'processing'),
(10, 1, 0.00, NULL, 0.00, 'completed', 'pending', '2025-12-03 03:48:06', 500.00, 0.00, 76.27, NULL, NULL, NULL, 'pending'),
(11, 1, 0.00, NULL, 0.00, 'completed', 'pending', '2025-12-03 03:48:24', 500.00, 0.00, 76.27, NULL, NULL, NULL, 'pending'),
(12, 2, 479.00, '', 0.00, 'processing', 'paid', '2025-12-03 04:04:48', 479.00, 0.00, 73.07, 'order_Rn04h9uQbsumj7', 'Chandru Prasath', 'Chandru Sri\\n18/4balgangathara Thilagar St\\n18\\nVellore, Tamil Nadu - 632602\\nPhone: 08946071785', 'processing'),
(13, 2, 479.00, '', 0.00, 'processing', 'paid', '2025-12-03 06:28:30', 479.00, 80.00, 73.07, 'order_Rn2WRuatlT4a46', 'Chandru Prasath', 'Chandru Sri\\n18/4balgangathara Thilagar St\\n18\\nVellore, Tamil Nadu - 632602\\nPhone: 08946071785', 'cancelled'),
(14, 2, 3600.00, '', 0.00, 'processing', 'paid', '2025-12-03 06:45:25', 3600.00, 0.00, 549.15, 'order_Rn2oHAyy1DObwY', 'Chandru Prasath', 'Chandru Sri\\n18/4balgangathara Thilagar St\\n18\\nVellore, Tamil Nadu - 632602\\nPhone: 08946071785', 'processing'),
(15, 2, 5760.00, 'DEVEPQBMKDOL04', 1440.00, 'processing', 'paid', '2025-12-03 06:55:49', 5760.00, 0.00, 1098.31, 'order_Rn2zHG4bRxQQDd', 'Chandru Prasath', 'Chandru Sri\\n18/4balgangathara Thilagar St\\n18\\nVellore, Tamil Nadu - 632602\\nPhone: 08946071785', 'shipped'),
(16, 4, 3240.00, 'DEVOKK9J79HQG', 360.00, 'processing', 'paid', '2025-12-05 02:36:51', 3240.00, 0.00, 549.15, 'order_RnldtWoaTvOfPb', 'Dev Elixir Natural Cosmetics', 'Chandru Prasath\n18/4 balagangathara thilagar st pichanoor pet gudiyattam\n18\nVellore, Tamil Nadu - 632602\nPhone: 08946071785', 'packed'),
(17, 4, 3600.00, '', 0.00, 'processing', 'paid', '2025-12-12 11:31:08', 3600.00, 0.00, 549.15, 'order_RqgTzNGDZeK1Uz', 'Dev Elixir Natural Cosmetics', 'Chandru Prasath\n18/4 balagangathara thilagar st pichanoor pet gudiyattam\n18\nVellore, Tamil Nadu - 632602\nPhone: 08946071785', 'processing'),
(18, 4, 479.00, '', 0.00, 'processing', 'paid', '2025-12-13 02:08:07', 479.00, 80.00, 60.86, 'order_RqvQYGJrjKgUuT', 'Dev Elixir Natural Cosmetics', 'Chandru Prasath\n18/4 balagangathara thilagar st pichanoor pet gudiyattam\n18\nVellore, Tamil Nadu - 632602\nPhone: 08946071785', 'processing');

-- --------------------------------------------------------

--
-- Table structure for table `order_addresses`
--

CREATE TABLE `order_addresses` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `address_type` enum('shipping','billing') NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `pincode` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `variant` varchar(255) DEFAULT NULL,
  `qty` int(11) DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `variant`, `qty`, `price`, `created_at`) VALUES
(1, 6, NULL, 'Babies Ayurvedic Eye Kajal Pure and Natural Anjana', NULL, 5, 399.00, '2025-12-02 18:12:20'),
(2, 7, 29, 'Nalpamaradi Kera Thailam Bridal Mom Baby Skin Beauty', NULL, 1, 3600.00, '2025-12-03 01:43:58'),
(3, 8, 29, 'Nalpamaradi Kera Thailam Bridal Mom Baby Skin Beauty', NULL, 1, 3600.00, '2025-12-03 01:48:23'),
(4, 9, NULL, 'Babies Ayurvedic Eye Kajal Pure and Natural Anjana', NULL, 1, 399.00, '2025-12-03 01:53:13'),
(5, 12, NULL, 'Babies Ayurvedic Eye Kajal Pure and Natural Anjana', NULL, 1, 399.00, '2025-12-03 04:04:48'),
(6, 13, NULL, 'Babies Ayurvedic Eye Kajal Pure and Natural Anjana', NULL, 1, 399.00, '2025-12-03 06:28:30'),
(7, 14, 29, 'Nalpamaradi Kera Thailam Bridal Mom Baby Skin Beauty', NULL, 1, 3600.00, '2025-12-03 06:45:25'),
(8, 15, 29, 'Nalpamaradi Kera Thailam Bridal Mom Baby Skin Beauty', NULL, 2, 3600.00, '2025-12-03 06:55:49'),
(9, 16, 29, 'Nalpamaradi Kera Thailam Bridal Mom Baby Skin Beauty', NULL, 1, 3600.00, '2025-12-05 02:36:51'),
(10, 17, 29, 'Nalpamaradi Kera Thailam Bridal Mom Baby Skin Beauty', NULL, 1, 3600.00, '2025-12-12 11:31:08'),
(11, 18, NULL, 'Baby Girl Bath Powder 21 Ayurvedic Herbs Nalangu Ma Even Skin tone Ubtan 100 gm', NULL, 1, 399.00, '2025-12-13 02:08:07');

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `type` varchar(50) DEFAULT 'custom',
  `status` varchar(20) DEFAULT 'draft',
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`content`)),
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 1,
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pages`
--

INSERT INTO `pages` (`id`, `title`, `slug`, `type`, `status`, `content`, `meta_title`, `meta_description`, `meta_keywords`, `is_public`, `published_at`, `created_at`, `updated_at`) VALUES
(3, 'Community-Ethics', 'community-ethics', 'custom', 'published', '[{\"type\":\"hero\",\"data\":{\"heading\":\"Community-Ethics\",\"subheading\":\"\",\"bg_image\":\"https://newv2.develixirs.com/assets/uploads/media/25486f8d37ad4105bd15d2b15f03acc2_community_and_etics_copy.jpg\",\"cta_text\":\"\",\"cta_link\":\"\"}},{\"type\":\"text\",\"data\":{\"content\":\"<p><strong>Sustainability &amp; Ethics</strong></p>\\n\\n<p>Ayurvedic formulations, ancient techniques, modern innovation, and hygiene standards.<br />\\nIngredients sourced from local farmers<br />\\nEthically harvested herbs, roots, and flowers<br />\\nWildcrafted ingredients from trusted forest regions<br />\\nBiodegradable and recyclable packaging&mdash;refill and reuse initiative</p>\\n\\n<p><strong>Women Empowerment</strong></p>\\n\\n<p>85% of our workforce are rural women<br />\\nSkilling, training, and employment for local women<br />\\nSupport for self-reliance and community building</p>\\n\\n<p><strong>Clean Beauty Standards</strong></p>\\n\\n<p>100% natural, plant-based, and cruelty-free<br />\\nProducts crafted in small batches.<br />\\nNo parabens, sulfates, silicones, or synthetic fragrances<br />\\nTested for safety, backed by Ayurveda</p>\\n\\n<p><strong>Certifications</strong></p>\\n\\n<p>AYUSH Certified<br />\\nISO &amp; GMP Certified Facility<br />\\nTraditional preparation + modern compliance</p>\\n\"}}]', 'Community & Ethics', '', NULL, 1, NULL, '2025-12-28 18:01:20', '2026-01-04 04:56:25'),
(4, 'About Us', 'about-us', 'custom', 'published', '[{\"type\":\"text\",\"data\":{\"content\":\"This Document Is An Electronic Record In Terms Of Information Technology Act, 2000 And Rules There Under As Applicable And The Amended Provisions Pertaining To Electronic Records In Various Statutes As Amended By The Information Technology Act, 2000. This Electronic Record Is Generated By A Computer System And Does Not Require Any Physical Or Digital Signatures.\\nWelcome to DevElixir\\nDevElixir Natural Cosmetics Online provides you with the content and services available on this website, subject to the following Terms and Conditions, our Privacy Policy, Payment Policy and other conditions and policies which you may find throughout our website, in connection with certain functionality, features or promotions, as well as customer service, all of which are deemed a part of and included within these terms and conditions (collectively, “Terms and Conditions”). By accessing or using this website, you are acknowledging that you have read, understood, and you agree, without limitation or qualification, to be bound by these Terms and Conditions.\\n1. Privacy\\nPlease review our privacy-policy so that you may understand our privacy practices.\\n2. Payment Policy\\nPlease see our Payment Policy to understand the purchase processes of our products.\\n3. Products and Services for Personal Use\\nThe products and services described on this website, and any samples thereof we may provide to you, are for personal use only. You may not sell or resell any of the products or services, or samples thereof, you receive from us. We reserve the right, with or without notice, to cancel or reduce the quantity of any products or services to be provided to you that we believe, in our sole discretion, may result in the violation of our Terms and Conditions.\\n4. Accuracy of Information\\nWe attempt to be as accurate as possible when describing our products on the website. However, except to the extent implied by applicable law, we do not warrant that the product descriptions, colours, information or other content available on the website are accurate, complete, reliable, curren.,\\n5. Manufacturing Information\\nAll our products are manufactured & marketed by\\nDevElixir Natural Cosmetics\\nNo.6 3rd cross,Kamatchiamman Nagar,\\nSedhukkarai\\nGudiyatham-632602\\nTAMIL NADU\\nMade in INDIA.\\n\\n6. Intellectual Property\\nAll information and content available on the website and its “look and feel”, including but not limited to trademarks, logos, service marks, text, graphics, logos, button icons, images, audio clips, data compilations and software, and the compilation and organization thereof (collectively, the “Content”) is the property of DevElixir Online, our Affiliates, partners or licensors, and is protected by laws of India, including laws governing all applicable forms of intellectual property.\\nExcept as set forth in the limited licenses in Section 6, or as required under applicable law, neither the Content nor any portion of this website may be used, reproduced, duplicated, copied, sold, resold, accessed, modified, or otherwise exploited, in whole or in part, for any purpose without our express, prior written consent.\\n7. Limited Licenses\\nWe grant you a limited, revocable, and non-exclusive license to access and make personal use of DevElixir Online website. This limited license does not include the right to: (a) frame or utilize framing techniques to enclose the website or any portion thereof; (b) republish, redistribute, transmit, sell, license or download the website or any and/or all Content (except caching or as necessary to view the website); (c) make any use of the website or any and/or all Content other than personal use; (d) modify, reverse engineer or create any derivative works based upon either the website or any and/or all Content; (e) collect account information for the benefit of yourself or another party; (f) use any meta tags or any other “hidden text” utilizing any and/or all Content; or (g) use software robots, spiders, crawlers, or similar data gathering and extraction tools, or take any other action that may impose an unreasonable burden or load on our infrastructure. You must retain, without modification, all proprietary notices on the website or affixed to or contained in the website.\\nWe also grant you a limited, revocable, and nonexclusive license to create a hyperlink to the home page of the website for personal, non-commercial use only. A website that links to the website (i) may link to, but not replicate, any and/or all of our Content; (ii) may not imply that we are endorsing such website or its services or products; (iii) may not misrepresent its relationship with us; (iv) may not contain content that could be construed as distasteful, obscene, offensive controversial or illegal or inappropriate for any ages; (v) may not portray us or our products or services, in a false, misleading, derogatory, or otherwise offensive or objectionable manner, or associate us with undesirable products, services, or opinions; and (vi) may not link to any page of the website other than the home page. We may, in our sole discretion, request that you remove any link to the website, and upon receipt of such request, you shall immediately remove such link and cease any linking unless separately and expressly authorized in writing by us to resume linking.\\nAny unauthorized use by you of the DevElixir Online website or any and/or all of our Content automatically terminates the limited licenses set forth in this Section without prejudice to any other remedy provided by applicable law or these Terms and Conditions.\\n8. Your Obligations and Responsibilities\\nIn the access or use of the DevElixir Online website, you shall comply with these Terms and Conditions and the special warnings or instructions for access or use posted on the website. You shall act always in accordance with the law, custom and in good faith. You may not make any change or alteration to the website or any Content or services that may appear on this website and may not impair in any way the integrity or operation of the website. Without limiting the generality of any other provision of these Terms and Conditions, if you default negligently or willfully in any of the obligations set forth in these Terms and Conditions, you shall be liable for all the losses and damages that this may cause to DevElixir Online, our Affiliates, partners or licensors.\\n9. Third Party Links\\nWe are not responsible for the content of any off-website pages or any other websites linked to or from the DevElixir Online website. Links appearing on this website are for convenience only and are not an endorsement by us, our affiliates or our partners of the referenced content, product, service, or supplier. Your linking to or from any off-website pages or other websites is at your own risk. We are in no way responsible for examining or evaluating, and we do not warrant the offerings of, off-website pages or any other websites linked to or from the site, nor do we assume any responsibility or liability for the actions, content, products, or services of such pages and websites, including, without limitation, their privacy policies and terms and conditions. You should carefully review the terms and conditions and privacy policies of all off-website pages and other websites that you visit.\\n10. Special Features, Functionality and Events\\nDevElixir Online may offer certain special features and functionality or events (such as contests, promotions or other offerings) which may (a) be subject to terms of use, rules and/or policies in addition to or in lieu of these Terms and Conditions; and (b) be offered by us or by third parties. If so, we will notify you of this and if you choose to take advantage of these offerings, you agree that your use of those offerings will be subject to such additional or separate terms of use, rules and/or policies.\\n11. Submissions\\nIt is our policy to decline unsolicited suggestions and ideas. Notwithstanding our policy with regard to unsolicited suggestions and ideas, any inquiries, feedback, suggestions, ideas or other information you provide us (collectively, “Submissions”) will be treated as non-proprietary and non-confidential. Subject to the terms of our Privacy Policy, by transmitting or posting any Submission, you hereby grant us the right to copy, use, reproduce, modify, adapt, translate, publish, license, distribute, sell or assign the Submission in any way as we see fit, including but not limited to copying in whole or in part, creating derivative works from, distributing and displaying any Submission in any form, media, or technology, whether now known or hereafter developed, alone or as part of other works, or using the Submission within or in connection with our products or services. You also acknowledge that your Submission will not be returned and we may use your Submission, and any ideas, concepts or know how contained therein, without payment of money or any other form of consideration, for any purpose including, without limitation, developing, manufacturing, distributing and marketing products.\\nIf you make a Submission, you represent and warrant that you own or otherwise control the rights to your Submission. You further represent and warrant that such Submission does not constitute or contain software viruses, commercial solicitation, chain letters, mass mailings, or any form of “spam”. You may not use a false email address, impersonate any person or entity, or otherwise mislead us as to the origin of any Submission. You agree to indemnify us for all claims arising from or in connection with any claims to any rights in any Submission or any damages arising from any Submission.\\n12. User Content\\nWhen you transmit, upload, post, e-mail or otherwise make available data, text, software, music, sound, photographs, graphics, images, videos, messages or other materials (“User Content”) on the website, you are entirely responsible for such User Content. Such User Content constitutes a Submission under Section 10 above. This means that all third parties, and not we, are entirely responsible for all User Content that they post to the website. You agree not to engage in or assist or encourage others to engage in transmitting, uploading, posting, e-mailing or otherwise making available on the website User Content that (a) is unlawful, harmful, threatening, abusive, harassing, tortious, defamatory, vulgar, obscene, pornographic, libelous, invasive of another’s privacy, hateful, or racially, ethnically or otherwise objectionable; (b) you do not have a right to make available under any law or under contractual or fiduciary relationships; (c) is known by you to be false, inaccurate or misleading; (d) you were compensated for or granted any consideration by any third party; or (e) infringes any patent, trademark, trade secret, copyright or other proprietary and/or intellectual property rights of any party. In addition, you agree not to transmit, upload, post, e-mail, or otherwise make available any software viruses, unsolicited or unauthorized advertising, solicitation or promotional material, including chain letters, mass mailings, or any form of “spam”. You further agree not to (i) impersonate any person or entity, or falsely state or otherwise misrepresent your affiliation with any person or entity; (ii) “stalk” or otherwise harass including advocating harassment of another, entrap or harm any third party including harming minors in any way; (iii) forge headers or otherwise manipulate identifiers in order to disguise the origin of any User Content; (iv) intentionally or unintentionally violate any applicable local, state, national or international law; or (v) collect or store personally identifiable data about other users.\\nWe do not endorse or control the User Content transmitted or posted on the Develixir Natural Cosmetics Online website and therefore, we do not guarantee the accuracy, integrity or quality of User Content. You understand that by using this website, you may be exposed to User Content that is offensive, indecent or objectionable to you. Under no circumstances will we be liable in any way for any User Content, including, without limitation, for any errors or omissions in any User Content, or for any loss or damage of any kind incurred by you as a result of the use of any User Content transmitted, uploaded, posted, e-mailed or otherwise made available via the website.\\nYou acknowledge that we have the right (but not the obligation) in our sole discretion to refuse to post or remove any User Content and we reserve the right to change, condense, or delete any User Content. Without limiting the generality of the foregoing or any other provision of these Terms and Conditions, we have the right to remove any User Content that violates these Terms and Conditions or is otherwise objectionable and we reserve the right to refuse service without prior notice for any users who violate these Terms and Conditions or infringe the rights of others.\\n13. Copyright Complaints\\nWe respect the intellectual property of others. If you believe that copyrighted materials have been copied in a way that constitutes copyright infringement, please send an email or written notice to us for notices of infringement and provide the following: (i) identification of the copyrighted work(s) that you claim has been infringed and that you are the copyright owner or authorised to act on the copyright owner’s behalf; (ii) a description of the material that you claim is infringing and the location of the material on the website; (iii) your address, telephone number and email address to legal info@develixirnaturalcosmetics.com\\nNote: The above contact information is provided exclusively for notifying DevElixir that copyrighted material may have been infringed. All other inquiries will not receive a response through this process and should be directed to our customer service group by email info@develixirnaturalcosmetics.com\\n14. Representations and Warranties and Limitation of Liability\\nDevElixir Online website is presented “As Is”. We make no representations or warranties of any kind whatsoever, express or implied, in connection with these terms and conditions or the site, including but not limited to warranties of merchantability, non-infringement or fitness for a particular purpose, except to the extent such representations and warranties are not legally excludable.\\nYou agree that, to the fullest extent permitted by applicable law, we will not be responsible or liable (whether in contract, tort (including negligence) or otherwise), under any circumstances, for any (a) interruption of business; (b) access delays or access interruptions to the site; (c) data non-delivery, wrong delivery, corruption, destruction or other modification; (d) loss or damages of any sort incurred as a result of dealings with or the presence of off-website links on the website; (e) computer viruses, system failures or malfunctions which may occur in connection with your use of the site, including during hyperlink to or from third party websites (f) any inaccuracies or omissions in content or (g) events beyond our reasonable control.\\nDevElixir personal care products are based on natural and Ayurvedic & Siddha formulations. Though enormous efforts are made and precautions taken to render the products absolutely safe for human use, it is possible that certain ingredients may cause allergic reactions to certain individuals or adversely affect individuals with pre-existing medical conditions. Please make yourself aware of the ingredients and usage instructions accompanying each of our products to make sure that they are safe for you to use. It will be your sole responsibility to take proper precaution/ professional medical/ dermatological advice before using any of our personal care products that you may be allergic to. You agree that DevElixir Online will not be responsible or liable for any product related issues including without limitation any allergic reactions to you on account of usage of our products. For any purchases made through the DevElixir Online, you will inter alia be governed by the limitation of liability and disclaimer conditions provided in more detail with the product packaging/leaflets.\\nFurther, to the fullest extent permitted by law, we will not be liable for any indirect, special, punitive, incidental, or consequential damages of any kind (including lost profits) related to the website or your use thereof regardless of the form of action whether in contract, tort (including negligence) or otherwise, even if we have been advised of the possibility of such damages and in no event shall our maximum aggregate liability exceed Indian Rupees 2000 only.\\nYou agree that no claims or action arising out of, or related to, the use of the website or these terms and conditions may be brought by you more than one (1) year after the cause of action relating to such claim or action arose.\\n15. Indemnification\\nYou agree to defend, indemnify and hold us harmless for any loss, damages or costs, including reasonable attorneys’ fees, resulting from any third party claim, action, or demand resulting from your use of Develixir Natural Cosmetics Online or breach of these Terms and Conditions. You also agree to indemnify us for any loss, damages, or costs, including reasonable attorneys’ fees, resulting from your use of software robots, spiders, crawlers, or similar data gathering and extraction tools, or any other action you take that imposes an unreasonable burden or load on our infrastructure.\\n16. Disputes\\nWith respect to any dispute regarding the website, all rights and obligations and all actions contemplated by these Terms and Conditions shall be governed by the laws of India and the courts of Gudiyatham, India, as if the Terms and Conditions were a contract wholly entered into and wholly performed within Gudiyatham, India, subject to foreign legal mandatory provisions. To the fullest extent permitted by applicable law, any dispute, differences or claim arising out your visit to the DevElixir Online website shall be referred to the sole Arbitrator appointed by the Chairman and Managing Director of DevEixir in accordance with the law. The venue of such arbitration shall be at Gudiyatham, India and the award of the Arbitrator shall be final and binding on all parties and may be entered as a judgment in any court of competent jurisdiction. To the fullest extent permitted by applicable law, no arbitration under these Terms and Conditions shall be joined to an arbitration involving any other party subject to this Terms and Conditions, whether through class arbitration proceedings or otherwise.\\nTo the extent arbitration is not permitted by applicable law, any dispute relating in any way to your visit to the website shall be submitted to an appropriate court or other judicial body in India, as applicable, without prejudice to the applicable law and all applicable provisions of this Section, and subject to our right to seek injunctive or other appropriate relief in any court, if you have in any manner violated or threatened to violate our intellectual property rights and you consent to exclusive jurisdiction and venue in such courts.\\n17. Consent to Receive Notices Electronically by Posting on the Website and Via Email\\nYou consent to receive any agreements, notices, disclosures and other communications (collectively, “Notices”) to which these Terms and Conditions refer from us electronically including without limitation by email or by posting notices on this website. You agree that all Notices that we provide to you electronically satisfy any legal requirement that such communications be in writing. To withdraw your consent to receive Notices electronically, you must notify us of your withdrawal of such consent by emailing us at info@develixirnaturalcosmetics.com and discontinue your use of this website. In such event, all rights granted to you pursuant to these Terms and Conditions, including but not limited to the limited licenses set forth in Section 6 hereof, shall automatically terminate. Unfortunately, we cannot provide the benefits of this website to any user that cannot consent to receipt of Notices electronically.\\nPlease note that this consent to receive Notices is entirely separate from any election you may make with respect to receipt of marketing communications. Your options with respect to receipt of marketing communications are set forth in our Privacy Policy.\\n18. General\\nYou acknowledge and agree that these Terms and Conditions constitute the complete and exclusive agreement between us concerning your use of the website, and supersede and govern all prior proposals, agreements, or other communications.\\nWe reserve the right, in our sole discretion, to change these Terms and Conditions at any time by posting the changes on the website and providing notice of such change. Any changes are effective immediately upon posting to the Site and release of notice of such change. Your continued use of the website thereafter constitutes your agreement to all such changed Terms and Conditions. We may, with or without prior notice, terminate any of the rights granted by these Terms and Conditions. You shall comply immediately with any termination or other notice, including, as applicable, by ceasing all use of the website.\\nNothing contained in these Terms and Conditions shall be construed as creating any agency, partnership, or other form of joint enterprise between us. Our failure to require your performance of any provision hereof shall not affect our full right to require such performance at any time thereafter, nor shall our waiver of a breach of any provision hereof be taken or held to be a waiver of the provision itself. In the event that any provision of these Terms and Conditions shall be unenforceable or invalid under any applicable law or be so held by any applicable arbitral award or court decision, such unenforceability or invalidity shall not render these Terms and Conditions unenforceable or invalid as a whole but these Terms and Conditions shall be modified, to the extent possible, by the adjudicating entity to most fully reflect the original intent of the parties as reflected in the original provision.\\nIf you have any questions regarding these Terms and Conditions, please email us at info@develixirnaturalcosmetics.com\\nK.RAJINI.B.A.B.L\\nADVOCATE\\nGUDIYATHAM\"}}]', '', '', NULL, 1, NULL, '2025-12-28 18:07:18', '2025-12-29 13:12:18'),
(6, 'Our Services', 'our-services', 'custom', 'published', '[{\"type\":\"hero\",\"data\":{\"heading\":\"Our Services\",\"subheading\":\"Protected Partnership Model\",\"bg_image\":\"https://newv2.develixirs.com/assets/uploads/media/aa3e7ec570ad462da74b7ed935e326c2_growth_wit_us_sucessufully.jpg\",\"cta_text\":\"Whastapp\",\"cta_link\":\"\"}},{\"type\":\"image_text\",\"data\":{\"image\":\"https://newv2.develixirs.com/assets/uploads/media/8ecb6bc1feb041c7be6944b94c23cd85_our_services.jpg\",\"position\":\"left\",\"content\":\"Exclusive District Rights\\n\\nOne Distributor. One Territory. Zero Overlap.\\nEnjoy protected district coverage with price discipline and long-term growth.\"}},{\"type\":\"form\",\"data\":{\"form_type\":\"contact\",\"recipient_email\":\"\",\"btn_label\":\"Send Message\",\"success_msg\":\"Thank you! We received your message.\"}}]', 'Our Services', 'Protected Partnership Model', NULL, 1, NULL, '2025-12-29 10:59:07', '2025-12-29 13:10:00'),
(7, 'DEVELIXIR Admin', 'our-story', 'custom', 'published', '[{\"type\":\"hero\",\"data\":{\"heading\":\"About Us\",\"subheading\":\"Rooted in Ayurveda. Crafted with Integrity.\",\"bg_image\":\"https://newv2.develixirs.com/assets/uploads/media/754f95294dc64d7c840ced08e1e9c87a_about_our_journey.jpg\",\"cta_text\":\"\",\"cta_link\":\"\"}},{\"type\":\"image_text\",\"data\":{\"image\":\"https://newv2.develixirs.com/assets/uploads/media/ebbe7188bc5143bfbd04889b6c4a5422_Rooted_in_Ayurveda__Crafted_with_Integrity.jpg\",\"position\":\"left\",\"content\":\"At DevElixir Natural Cosmetics, we believe true beauty and wellness begin with nature. Guided by the timeless wisdom of Ayurveda, we create pure, natural formulations that nurture the body, respect the earth, and honor tradition.\\n\\nFounded in 2014 by Mrs. Rukmani and Mr. Prakash, DevElixir began as a modest physical store in Gudiyatham, built on trust, authenticity, and a deep understanding of herbal wellness. What started as a local endeavor gradually evolved into a growing community of customers who believed in our approach to clean, honest beauty.\\n\\nIn 2019, we expanded online, allowing our Ayurvedic formulations to reach homes beyond our hometown. While our reach has grown, our philosophy remains unchanged — every product is crafted with the same care, intention, and respect for tradition as it was on day one.\"}},{\"type\":\"image_text\",\"data\":{\"image\":\"https://newv2.develixirs.com/assets/uploads/media/0248ecf2a651479a8269fdbbaa3dc26c_dev-elixir-kadhira-tooth-powder-ingredients-1.jpg\",\"position\":\"left\",\"content\":\"Ayurveda is not just our inspiration — it is our foundation.\\nWe follow time-honoured Ayurvedic principles and traditional preparation methods to create formulations that are gentle, effective, and balanced. Each product is designed to support everyday wellness, becoming a meaningful ritual rather than just a routine.\\n\\nWe believe:\\nAuthentic ingredients, mindful processes, and honest practices create true wellness.\"}}]', '', '', NULL, 1, NULL, '2025-12-29 12:57:24', '2026-01-02 07:37:36'),
(8, 'DEVELIXIR Admin', 'what-is-develixir', 'custom', 'published', '[{\"type\":\"hero\",\"data\":{\"heading\":\"what is Dev Elixir Doing\",\"subheading\":\"Your wellbeing is our prioritize \",\"bg_image\":\"https://newv2.develixirs.com/assets/uploads/media/754f95294dc64d7c840ced08e1e9c87a_about_our_journey.jpg\",\"cta_text\":\"\",\"cta_link\":\"\"}},{\"type\":\"image_text\",\"data\":{\"image\":\"https://newv2.develixirs.com/assets/uploads/media/0aa1dc83553f4652ad58a5608dd3218b_anitdandruff_oil_ingredients_2.jpg\",\"position\":\"right\",\"content\":\"<h3><strong>What is DevElixir doing?</strong></h3>\\n\\n<p>Use of Natural Ingredients: Ayurvedic cosmetics prioritize the use of natural ingredients, such as herbs like neem, tulsi, aloe vera, and various plant extracts. These ingredients are chosen for their potential benefits to the skin, hair, and overall health. Holistic Approach: Ayurveda takes a holistic approach to beauty and wellness, considering the interconnectedness of the mind, body, and spirit. Products from Develixir may aim to address both physical and mental well-being. Customization: Some Ayurvedic cosmetics companies offer personalized products or consultations to tailor skincare and personal care routines to individual doshas or skin types, as per Ayurvedic principles. Avoidance of Harmful Chemicals: Ayurvedic cosmetics often avoid harsh chemicals, synthetic fragrances, and artificial colors that can be irritating or harmful to the skin. Sustainability: Many natural cosmetics companies, including those focusing on Ayurvedic principles, prioritize eco-friendly and sustainable packaging and sourcing practices to minimize their environmental impact. Transparency: Companies like Develixir may emphasize transparency in ingredient sourcing, production processes, and product labeling to build trust with consumers. Product Range: Ayurvedic cosmetics companies typically offer a range of products, including skincare items like creams, lotions, and oils, as well as haircare products and personal care items like soaps and shampoos.</p>\\n\"}}]', '', '', NULL, 1, NULL, '2026-01-02 07:28:09', '2026-01-02 07:49:12');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `hsn` varchar(20) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `short_description` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ingredients` text DEFAULT NULL,
  `how_to_use` text DEFAULT NULL,
  `faqs` text DEFAULT NULL,
  `variant_label` varchar(100) DEFAULT 'Size',
  `main_variant_name` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `compare_price` decimal(10,2) DEFAULT NULL,
  `discount_percent` decimal(5,2) DEFAULT NULL,
  `currency` varchar(10) DEFAULT 'INR',
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `parent_category_id` int(11) DEFAULT NULL,
  `category_name` varchar(255) DEFAULT NULL,
  `images` text DEFAULT NULL,
  `variants` text DEFAULT NULL,
  `related_products` text DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `product_media` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Stores array of media files (images/videos) with type and path' CHECK (json_valid(`product_media`)),
  `gst_rate` decimal(5,2) DEFAULT 0.00,
  `seo_keywords` text DEFAULT NULL,
  `label_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `sku`, `hsn`, `name`, `slug`, `short_description`, `description`, `ingredients`, `how_to_use`, `faqs`, `variant_label`, `main_variant_name`, `price`, `compare_price`, `discount_percent`, `currency`, `category_id`, `parent_category_id`, `category_name`, `images`, `variants`, `related_products`, `stock`, `is_active`, `is_featured`, `meta_title`, `meta_description`, `created_at`, `updated_at`, `product_media`, `gst_rate`, `seo_keywords`, `label_id`) VALUES
(29, '', '3200', 'Nalpamaradi Kera Thailam Bridal Mom Baby Skin Beauty', 'nalpamaradi-kera-thailam-bridal-mom-baby-skin-beauty', '<p><strong class=\"ql-font-ibm-plex-mono\">Nalpamaradi Kera Thailam is opting for Skin brightening treatment, especially for Bridal and babies. This oil helps lighten the appearance of dark patches, cure uneven skin color, and Reduce Stretch marks.</strong></p>', '<p><strong class=\"ql-font-open-sans\"><em>Nalpamaradi Kera Thailam is opting for Skin brightening treatment, especially for Bridal and babies. This oil helps lighten the appearance of dark patches, cure uneven skin color, and Reduce Stretch marks.</em></strong></p>', '<p>Nalpamaram Turmeric Triphala Sandalwood</p>', '<p>Known to reduce excess heat in the body (Pitta in Ayurveda) and treat various skin issues, including acne, dry skin, and skin irritation.</p>', NULL, 'Product', 'Nalpamaradi', 3600.00, 4000.00, 10.00, 'INR', NULL, NULL, NULL, '[\"c28997524100.jpg\",\"459a32ced2ab.jpg\",\"77285b548731.jpg\"]', NULL, NULL, 10, 1, 0, 'Known to reduce excess heat in the body (Pitta in Ayurveda) and treat various skin issues, including acne, dry skin, and skin irritation.', 'Known to reduce excess heat in the body (Pitta in Ayurveda) and treat various skin issues, including acne, dry skin, and skin irritation.', '2025-11-28 09:56:57', '2025-12-28 18:16:07', '[{\"path\":\"1764354601_6929ea2922709.mp4\",\"type\":\"video\"},{\"path\":\"1764355085_6929ec0d103fd.mp4\",\"type\":\"video\"}]', 5.00, '', NULL),
(31, 'fg-044', '3990992', 'Rose Gel | Radiant Essence Skin Gel | Face & Body glow', 'rose-gel-radiant-essence-skin-gel-face-body-glow', '<blockquote>\r\n<p style=\"margin-left:40px\">&nbsp;</p>\r\n</blockquote>', '<p><img alt=\"Image\" src=\"/assets/uploads/media/86bcab9242cc463ab7dd86c6bc364e37_why_we_use_rose_for_baby_bath_powder_copy.jpg\" style=\"height:auto; margin:10px 0; max-width:100%\" /></p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>Benefits:</p>\r\n\r\n<p>Ayurvedic Gel Hydration and skin&rsquo;s moisture balance,</p>\r\n\r\n<p>Revitalization skin.</p>\r\n\r\n<p>Pigmentation Reduction.</p>\r\n\r\n<p>Sunspot Prevention.</p>\r\n\r\n<p>Contract&nbsp;open pores</p>\r\n\r\n<p>Acne-Free &amp; Healthy Skin.</p>\r\n\r\n<p>Delicate Fragrance.</p>\r\n\r\n<p>No harmful chemicals,</p>\r\n\r\n<p>No Artificial fragrances</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>It is an Ayurvedic natural brightening face gel that helps reduce acne, acne scars, and sun damage. It reduces the visibility of fine lines, wrinkles, and age spots. Hydrates the skin tightens oil production pores and rejuvenates dull skin.</p>\r\n\r\n<p>&nbsp;</p>', '<p>INGREDIENTS:</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>Pure distilled Rose Water, Indian White Sandalwood, Nutmeg, and plant preservative.</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>&nbsp;</p>', '<p>Highlights:</p>\r\n\r\n<p>&bull;&nbsp;&nbsp;&nbsp;&nbsp;User: Suitable for teens and is unisex, making it versatile for a broad range of individuals.</p>\r\n\r\n<p>&bull;&nbsp;&nbsp;&nbsp;&nbsp;Use: Intended for daily use, both in the morning and at night, ensuring your skin receives continuous care.</p>\r\n\r\n<p>&bull;&nbsp;&nbsp;&nbsp;&nbsp;Product Shelf Life: The product remains effective for up to 1 year from its manufacturing date</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>How to Use DevElixir Rose Facial Gel:</p>\r\n\r\n<p>1.&nbsp;&nbsp;&nbsp;&nbsp;Start with a freshly cleansed and toned skin.</p>\r\n\r\n<p>2.&nbsp;&nbsp;&nbsp;&nbsp;Apply a small amount of DevElixir Rose Facial Gel evenly to your entire face.</p>\r\n\r\n<p>3.&nbsp;&nbsp;&nbsp;&nbsp;For daytime use, it serves as a protective day moisturizer.</p>\r\n\r\n<p>4.&nbsp;&nbsp;&nbsp;&nbsp;For nighttime application, it acts as a nourishing night mask, providing your skin with the care it needs while you rest.</p>', NULL, 'Size', NULL, 347.00, 688.00, 49.56, 'INR', NULL, 11, 'women care', '[\"ee36d8f868ad.jpg\"]', NULL, NULL, 10, 1, 0, '', '', '2025-12-05 18:43:40', '2025-12-15 05:59:41', '[]', 18.00, '', NULL),
(42, 'SKPO-01', '399090', 'Shikakai Hair Washes Powder | Deep Cleanses Strengthens & Stimulates Hair Growth |27 Ayuvedic Herbs Non-Foaming', 'shikakai-hair-washes-powder-deep-cleanses-strengthens-stimulates-hair-growth-27-ayuvedic-herbs-non-foaming', '<p>&nbsp;</p>\r\n\r\n<p><img alt=\"Image\" src=\"/assets/uploads/media/a6177d8096244da8aee7103c4fc8e525_USP_Develixxirs.png\" style=\"height:auto; margin:10px 0; max-width:100%\" /></p>\r\n\r\n<p><span style=\"font-size:12pt\"><span style=\"font-family:&quot;Times New Roman&quot;,&quot;serif&quot;\"><strong>Reclaim your hair&rsquo;s natural health &mdash; the Ayurvedic way.</strong><br />\r\n<strong>DevElixir Ayurvedic Herbal Shampoo</strong> is more than a cleanser &mdash; it&rsquo;s a commitment to holistic wellness, beauty, and balance.</span></span></p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>&nbsp;</p>', '<h2>What is Shikakai Powder?</h2>\r\n\r\n<p>Shikakai Powder Hair Wash cleanses and removes stubborn dirt, debris, and excess oils, and fights dandruff, and lice. It protects hair&#39;s essential nutrients, promotes hair growth, nourishes the hair roots, revitalizes dry, dull hair, and adds shine and a healthy scalp.</p>\r\n\r\n<p>Our Shikakai Hair Wash Powder is a 100% natural shampoo formulated to cleanse your scalp and hair gently yet effectively. our powdered cleanser is free from all additives, staying true to its organic origins.</p>\r\n\r\n<p>It helps restore natural hair color, leaving hair soft, manageable, and healthy from root to tip. Our Shikakai Hair Wash Powder provides a sustainable, chemical-free solution for healthy, vibrant hair.</p>\r\n\r\n<h2><strong>shikakai Powder Benefits for Hair:</strong></h2>\r\n\r\n<p>&nbsp;&nbsp;Prevent hair fall</p>\r\n\r\n<p>&nbsp;&nbsp;Promotes hair growth</p>\r\n\r\n<p>Treats dandruff and reduces white flakes</p>\r\n\r\n<p>Leaves hair looking smooth, and lustrous after every wash.</p>\r\n\r\n<p>Sufficient natural lather for easy washing</p>\r\n\r\n<p>Strengthens hair from the roots, increasing elasticity</p>\r\n\r\n<p>Helps prevent dreaded split ends</p>\r\n\r\n<p>Moisturized locks improve hair texture and reduce dryness in all seasons</p>', '<p><strong>What are the ingredients in Tejay Shikakai Hair Wash Powder?</strong></p>\r\n\r\n<table>\r\n	<tbody>\r\n		<tr>\r\n			<td>Common Name</td>\r\n			<td>&nbsp;Botanical Name</td>\r\n			<td>Key Benefits</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Shikakai&nbsp;</td>\r\n			<td>Acacia concinna</td>\r\n			<td>Natural cleanser, strengthens hair, promotes hair growth</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Soapnut (Reetha)</td>\r\n			<td>&nbsp;Sapindus mukorossi&nbsp;</td>\r\n			<td>&nbsp;Gentle cleanser, reduces dandruff, adds shine</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Neem&nbsp;</td>\r\n			<td>|Azadirachta indica</td>\r\n			<td>Antibacterial, treats scalp infections, prevents lice</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Fenugreek</td>\r\n			<td>Trigonella foenum-graecum &nbsp;</td>\r\n			<td>Conditions hair, reduces hair fall, treats dandruff</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Amla&nbsp;</td>\r\n			<td>Emblica officinalis</td>\r\n			<td>Rich in Vitamin C, promotes hair growth, prevents premature greying</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Hibiscus Leaves</td>\r\n			<td>&nbsp;Hibiscus rosa-sinensis</td>\r\n			<td>&nbsp;Stimulates hair growth, prevents hair fall, adds luster</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Hibiscus Flower</td>\r\n			<td>&nbsp;Hibiscus rosa-sinensis</td>\r\n			<td>&nbsp;Deep conditioner, nourishes scalp, strengthens hair roots</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Aloe Vera&nbsp;</td>\r\n			<td>Aloe barbadensis miller &nbsp;</td>\r\n			<td>Soothes scalp, conditions hair, promotes growth</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Vetiver Root</td>\r\n			<td>&nbsp;Chrysopogon zizanioides</td>\r\n			<td>&nbsp;Cools the scalp, strengthens hair roots, prevents dandruff</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Bhringraj&nbsp;</td>\r\n			<td>&nbsp;Eclipta alba / Eclipta prostrata&nbsp;</td>\r\n			<td>&nbsp;Promotes hair growth, combats baldness, reduces hair fall</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Neeli (Indigo)&nbsp;</td>\r\n			<td>&nbsp;Indigofera tinctoria&nbsp;</td>\r\n			<td>Natural hair dye, promotes hair health, improves thickness</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Jatamansi</td>\r\n			<td>&nbsp;Nardostachys jatamansi&nbsp;<br />\r\n			&nbsp;</td>\r\n			<td>&nbsp;Strengthens hair, prevents greying, calms the mind and supports hair growth<br />\r\n			&nbsp;</td>\r\n		</tr>\r\n	</tbody>\r\n</table>', '<p>How to use Shikakai Powder for hair?<br />\r\nTake 2-3 tablespoons of our Organic Hair Wash Powder and mix it with water to create a smooth paste.<br />\r\nThoroughly wet your hair with lukewarm water to open up the hair cuticles for better cleansing.<br />\r\nStart applying the paste from the scalp, moving down to the length of your hair in a gentle circular motion.<br />\r\nAdd a little water and gently massage your scalp and hair to create a natural lather.</p>\r\n\r\n<p>Rinse with warm water until all the paste is completely washed off and reapply if necessary, leaving your hair fresh and clean.</p>\r\n\r\n<p><img alt=\"Image\" src=\"/assets/uploads/media/0b17daae56cb421eb08c6ef6a611b1f9_how-to-use-shikakai-hair-wash.jpg\" style=\"height:auto; margin:10px 0; max-width:100%\" /></p>\r\n\r\n<p>&nbsp;</p>', NULL, 'Size', NULL, 398.99, 499.00, 20.04, 'INR', NULL, 13, 'Hair Care', '[\"06c7168b9872.jpg\",\"a8764ece5875.jpg\",\"4e9800de60b9.jpg\",\"8466a309669c.jpg\",\"1441ad58f2a5.jpg\",\"b58368c3ee6e.jpg\",\"\\/assets\\/uploads\\/media\\/6d8b5136630444888c993cbe87fafc7b_men_useing_eyebrow_growth_oil.mp4\"]', NULL, NULL, 10, 1, 0, 'Ayurvedic Herbal Shampoo with Shikakai, Amla & Reetha', 'Cleanse and nourish your hair with our Ayurvedic herbal shampoo made with shikakai, reetha, amla, neem, and bhringraj. Gentle and natural.', '2025-12-18 12:19:20', '2025-12-18 16:41:23', '[{\"type\":\"video\",\"path\":\"1766076083_69442eb32de98_lib.mp4\"}]', 5.00, 'ayurvedic shampoo for hair fall, herbal shampoo for dandruff, natural shampoo for sensitive scalp, ayurvedic shampoo with shikakai reetha, herbal shampoo for healthy scalp, plant based herbal shampoo, daily use ayurvedic shampoo, indian herbal shampoo,', 1),
(43, 'KDTP-01', '3990992', 'Tooth Powder Strengthen Gums Whiten Teeth Ayurvedic Kadhira', 'tooth-powder-strengthen-gums-whiten-teeth-ayurvedic-kadhira', '<p>This Ayurvedic tooth powder strengthens gums and whitens teeth. Khadira is enriched with manjistha, paddy charcoal, neem, &amp; Ayurvedic herbs. This chemical-free tooth powder&#39;s rock salt takes care of your teeth. This is used to engulf the dirt and expel it from the mouth. This Ayurvedic tooth powder is an alternative to toothpastes</p>', '<p><strong>Ayurvedic Herbal Tooth Powder Benefits for Adults</strong><br />\r\nIt may help to control various disorders related to gums and teeth, including oral health, gingivitis, and plaque formation.<br />\r\nPrevent dental caries<br />\r\nFresh breath<br />\r\nMaintain saliva pH<br />\r\nStrengthen oral Flora<br />\r\nHealing gums<br />\r\n&nbsp;</p>\r\n\r\n<p><strong>Kadhira Tooth Powder Elixir&#39;s Essentials;&nbsp;</strong></p>\r\n\r\n<p><strong>Paddy charcoal</strong><br />\r\nPaddy without rice is put on a smoke fire (no real fire), and the resultant charcoal is for tooth cleaning.&nbsp;</p>\r\n\r\n<p>Charcoal helps to absorb discolorants in your teeth&rsquo;s enamel.</p>\r\n\r\n<p>This activated charcoal is made of rice husk, which is soft on the tooth. The toxin-absorbing medicinal and cosmetic uses.</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<h2><strong>Manjistha (Indian Madder)</strong></h2>\r\n\r\n<p><br />\r\nTooth decay and plaque are caused by the buildup of bacteria in the mouth, and if these plaque bacteria accumulate below the gum line, they become culprits behind the development of periodontal issues such as swelling and irritation of the gums (even tooth loss if left untreated). Indian madder&rsquo;s anti-inflammatory properties may help prevent these gum conditions.<br />\r\n&nbsp;</p>\r\n\r\n<h2><strong>Neem</strong></h2>\r\n\r\n<p><br />\r\nNeem may help to prevent and heal gum disease and prevent cavities. Eliminate bacteria that cause cavities and inflammation of the gums, prevent bacteria from adhering to your teeth (reduce plaque),</p>\r\n\r\n<p>Enhance mouth immunity in general, and through all this, freshen the breath.</p>', '<table>\r\n	<tbody>\r\n		<tr>\r\n			<td>Ingredient</td>\r\n			<td>Botanical Name / Description</td>\r\n			<td>Tooth Powder Benefits</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Manjishta&nbsp;&nbsp;&nbsp;</td>\r\n			<td>Rubia cordifolia&nbsp;&nbsp;&nbsp;</td>\r\n			<td>&nbsp;Helps reduce gum inflammation, purifies oral tissues, supports healthy gums</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Paddy Charcoal&nbsp;&nbsp;&nbsp;&nbsp;</td>\r\n			<td>Activated rice husk charcoal&nbsp;&nbsp;&nbsp;&nbsp;</td>\r\n			<td>Gently whitens teeth, removes plaque and stains, detoxifies the mouth</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Neem&nbsp;&nbsp;</td>\r\n			<td>Azadirachta indica&nbsp;</td>\r\n			<td>&nbsp;Antibacterial, fights plaque and bacteria, prevents bad breath</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Khadira&nbsp;</td>\r\n			<td>Acacia catechu&nbsp;&nbsp;</td>\r\n			<td>Astringent, strengthens gums, reduces bleeding gums</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Cloves&nbsp;&nbsp;&nbsp;</td>\r\n			<td>Syzygium aromaticum&nbsp;&nbsp;</td>\r\n			<td>Natural pain reliever, antibacterial, prevents cavities, freshens breath</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Rock Salt&nbsp;&nbsp;</td>\r\n			<td>Mineral-rich natural salt&nbsp;&nbsp;&nbsp;&nbsp;</td>\r\n			<td>Strengthens enamel, balances oral pH, reduces sensitivity</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Various Natural Ingredients&nbsp;&nbsp;&nbsp;</td>\r\n			<td>&nbsp;Herbs, roots, flowers, and earth minerals&nbsp;&nbsp;&nbsp;&nbsp;</td>\r\n			<td>Support overall oral hygiene, enhance taste, nourish oral tissues</td>\r\n		</tr>\r\n	</tbody>\r\n</table>', '<p>User: Adults/Unisex<br />\r\nUse: Daily<br />\r\nProduct shelf life: 2 years from Mfg date.<br />\r\nOur product is made at home with Patience, love, and care.<br />\r\nPURPOSE: CLEANSE, WHITEN, PROTECT, STRENGTHEN</p>\r\n\r\n<p>How to Use<br />\r\nBrush twice daily. Use a clean, wet toothbrush and take the required Kadhira tooth powder. Brush in gentle circles all over the teeth, leave it for 2 to 5 minutes, split, and clean with water.<br />\r\nBrushing the teeth twice a day.<br />\r\nEat a well-balanced diet<br />\r\nKadhira Tooth Powder is an alternative to chemical-based tooth cleansers. If you have any issues with your tooth, consult your Physician</p>', NULL, 'weight', '100gm', 299.00, 347.00, 13.83, 'INR', 59, 33, 'Personal Care', '[\"\\/assets\\/uploads\\/media\\/4e9de01b2e3f492d8d3094a4c451986a_dev-elixir-tooth-powder-kadhira.jpg\"]', NULL, NULL, 10, 1, 0, '', '', '2025-12-21 22:37:25', '2025-12-22 05:36:47', '[{\"type\":\"image\",\"path\":\"1766356905_694877a95cb36_lib.jpg\"}]', 5.00, 'gum healing,', 1),
(44, 'BBPO-11', '3990992', 'Baby girl Bath powder | Natural skin glowing Nalanguma | Wild Turmeric Rose Green grams Sandalwood Ubtan', 'baby-girl-bath-powder-natural-skin-glowing-nalanguma-wild-turmeric-rose-green-grams-sandalwood-ubtan', '<p>Natural Glow | Protect Baby Skin Health | Removes Impurities</p>\r\n\r\n<p>Baby girl bath powder helps to secret proper cleansing to get rid of dead cells and to toxins not thrown off naturally by the skin , remove all dirt, grime and chemical and environmental pollutants, clear out clogged pores, and eliminate infection-causing bacteria without also stripping away the skin&#39;s natural moisturizer and hydration.</p>', '<h2><strong>Benefits:</strong></h2>\r\n\r\n<p><br />\r\nThis Natural cleanser removes skin impurities and dead skin cells to give an even tone without stripping natural oils<br />\r\nNot drying your baby&#39;s skin &amp; promoting soft and smooth<br />\r\nThe baby feels relaxed after the bath.<br />\r\nNatural herbal fragrances reduce body odor<br />\r\nIt may help protect suntan sunburn and de-tan the skin<br />\r\nIt may help to reduce the risk of diaper rashes.</p>\r\n\r\n<p>Baby Girl Bath Powder is specially crafted to give your little one a gentle and nourishing bath time experience. Designed for newborns to children up to 12 years old, this soap-free Ubtan is made with a carefully blended selection of natural Ayurvedic ingredients and certified by ISO and GMP standards. this Nalangu Maa formula not only cleanses but also soothes and protects, promoting healthy skin for your baby<br />\r\n&nbsp;</p>\r\n\r\n<div style=\"display: inline-block; width: 80%; max-width: 100%; resize: both; overflow: hidden; border: 1px dashed #ccc; vertical-align: top; margin: 10px 0;\">\r\n<video controls=\"\" src=\"/assets/uploads/media/6d8b5136630444888c993cbe87fafc7b_men_useing_eyebrow_growth_oil.mp4\" style=\"width: 100%; height: auto; display: block;\">&nbsp;</video>\r\n</div>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>&nbsp;</p>', '<table>\r\n	<tbody>\r\n		<tr>\r\n			<td>\r\n			<p>Ingredient | Botanical Name | Baby-Safe Skin Benefits</p>\r\n			</td>\r\n			<td>\r\n			<p>Botanical Name</p>\r\n			</td>\r\n			<td>\r\n			<p>&nbsp;Baby-Safe Skin Benefits</p>\r\n			</td>\r\n		</tr>\r\n		<tr>\r\n			<td><br />\r\n			Masur Dhal (Red Lentil)</td>\r\n			<td>&nbsp;Lens culinaris&nbsp;</td>\r\n			<td>&nbsp;| Lens culinaris | Gently cleanses, removes dead skin cells, improves skin tone naturally.</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Kasturi Manjal (Wild Turmeric)</td>\r\n			<td>&nbsp;Curcuma aromatica&nbsp;</td>\r\n			<td>Mild antiseptic, prevents infections, reduces diaper rash and skin irritation.</td>\r\n		</tr>\r\n		<tr>\r\n			<td><br />\r\n			White Sandalwood</td>\r\n			<td>&nbsp;Santalum album&nbsp;</td>\r\n			<td>Soothes heat rashes, cools skin, and imparts a mild fragrance.</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Licorice (Yashtimadhu)&nbsp;</td>\r\n			<td>Glycyrrhiza glabra&nbsp;</td>\r\n			<td>Reduces redness, keeps skin smooth, lightens dark patches (if any), and soothes dryness.</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Rose Petals&nbsp;<br />\r\n			&nbsp;</td>\r\n			<td>Rosa centifolia&nbsp;</td>\r\n			<td>Hydrates, softens, and cools skin; perfect for calming baths.</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Anantha Moola</td>\r\n			<td>&nbsp;|Hemidesmus indicus&nbsp;</td>\r\n			<td>Detoxifying and calming, especially helpful for sensitive baby skin prone to rashes.</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Vetiver</td>\r\n			<td>Vetiveria zizanioides&nbsp;</td>\r\n			<td>Calming, reduces itching or rashes, gently tones and refreshes baby skin.<br />\r\n			&nbsp;</td>\r\n		</tr>\r\n	</tbody>\r\n</table>', '<h2>How to Use:</h2>\r\n\r\n<p><br />\r\n&bull;&nbsp;&nbsp;&nbsp;&nbsp;This baby ubtan is ideal for daily use as a cleanser alternative to soap<br />\r\n&bull;&nbsp;&nbsp;&nbsp;&nbsp;For best results, make a paste with water.<br />\r\n&bull;&nbsp;&nbsp;&nbsp;&nbsp;Apply the paste to your baby&rsquo;s moist skin.<br />\r\n&bull;&nbsp;&nbsp;&nbsp;&nbsp;Gently massage it in and wash it off with water.<br />\r\n&bull;&nbsp;&nbsp;&nbsp;&nbsp;You can also add milk or rose water to create the paste for a soothing application.</p>\r\n\r\n<h2><br />\r\nHighlights:</h2>\r\n\r\n<p><br />\r\n&bull;&nbsp;&nbsp;&nbsp;&nbsp;Suitable for newborn, and above<br />\r\n&bull;&nbsp;&nbsp;&nbsp;&nbsp;Recommended for daily use.<br />\r\n&bull;&nbsp;&nbsp;&nbsp;&nbsp;Shelf life: 1 year from the manufacturing date.<br />\r\n&bull;&nbsp;&nbsp;&nbsp;&nbsp;Our product is lovingly made at home with patience, love, and care.</p>\r\n\r\n<p>&nbsp;</p>', NULL, 'weight', '200 gm', 499.00, 799.00, 37.55, 'INR', 61, 34, 'Baby Care', '[\"dc4fba4c5fc9.png\"]', NULL, NULL, 1, 1, 0, '', '', '2025-12-22 06:33:41', '2026-01-02 07:08:35', '[{\"type\":\"video\",\"path\":\"1766387544_6948ef5897057_lib.mp4\"}]', 18.00, 'bay skin whitening, skin tone,', 1);

-- --------------------------------------------------------

--
-- Table structure for table `product_faqs`
--

CREATE TABLE `product_faqs` (
  `id` int(11) NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `product_faqs`
--

INSERT INTO `product_faqs` (`id`, `product_id`, `question`, `answer`, `created_at`) VALUES
(2, 29, 'Why Choose DevElixir', '100% Natural', '2025-11-28 09:56:57'),
(3, 33, 'how to use?', 'use daiy', '2025-12-05 22:55:48'),
(4, 37, 'how to use?', 'daiy use', '2025-12-14 10:17:40'),
(5, 37, 'can be use for baby', 'yes', '2025-12-14 10:17:40'),
(6, 37, 'is it stainless', 'yes', '2025-12-14 10:17:40'),
(7, 42, 'What makes this shampoo Ayurvedic?', 'This shampoo is prepared using traditional Ayurvedic herbs like shikakai, reetha, amla, neem, bhringraj, brahmi, hibiscus, licorice, and jatamansi, known for naturally cleansing and nourishing the scalp and hair.', '2025-12-18 12:19:20'),
(8, 42, 'Is this shampoo suitable for daily use?', 'Yes. The gentle herbal formulation cleanses without stripping natural oils, making it suitable for regular or daily use.', '2025-12-18 12:19:20'),
(9, 42, 'Does this shampoo help reduce hair fall?', 'The presence of bhringraj, amla, and brahmi helps strengthen hair roots and support healthy hair growth when used consistently as part of a regular hair care routine.', '2025-12-18 12:19:20'),
(10, 42, 'Is it effective for dandruff and itchy scalp?', 'Herbs like neem and reetha help keep the scalp clean and fresh, supporting dandruff control and reducing scalp discomfort.', '2025-12-18 12:19:20'),
(11, 42, 'Does this shampoo dry the hair?', 'No. Unlike harsh chemical shampoos, shikakai and reetha gently cleanse while helping maintain the hair’s natural moisture balance.', '2025-12-18 12:19:20'),
(12, 43, 'how to use?', 'morning  and before bed', '2025-12-21 22:37:25');

-- --------------------------------------------------------

--
-- Table structure for table `product_filter_values`
--

CREATE TABLE `product_filter_values` (
  `product_id` int(11) NOT NULL,
  `filter_value_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_groups`
--

CREATE TABLE `product_groups` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `product_groups`
--

INSERT INTO `product_groups` (`id`, `name`, `image`, `created_at`) VALUES
(1, 'test', '/assets/uploads/media/4e9de01b2e3f492d8d3094a4c451986a_dev-elixir-tooth-powder-kadhira.jpg', '2025-12-28 11:08:44'),
(2, 'foot care', '/assets/uploads/media/7e6d969df3b14d118c9889cdaf2c0fee_Give_your_feet_the_feather-touch_care_copy.jpg', '2025-12-28 18:14:18'),
(3, 'Baby Bath Powder', 'group_1767337673_0e6f13aa.png', '2026-01-02 07:07:54');

-- --------------------------------------------------------

--
-- Table structure for table `product_group_map`
--

CREATE TABLE `product_group_map` (
  `product_id` int(10) UNSIGNED NOT NULL,
  `group_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `product_group_map`
--

INSERT INTO `product_group_map` (`product_id`, `group_id`) VALUES
(29, 2),
(44, 3);

-- --------------------------------------------------------

--
-- Table structure for table `product_labels`
--

CREATE TABLE `product_labels` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `color` varchar(20) NOT NULL DEFAULT '#000000',
  `bg_color` varchar(20) DEFAULT '#ff0000',
  `text_color` varchar(20) DEFAULT '#ffffff',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_labels`
--

INSERT INTO `product_labels` (`id`, `name`, `color`, `bg_color`, `text_color`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'New', '#f01919', '#ff0000', '#ffffff', 0, 1, '2025-12-07 07:54:58');

-- --------------------------------------------------------

--
-- Table structure for table `product_relations`
--

CREATE TABLE `product_relations` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `related_product_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_relations`
--

INSERT INTO `product_relations` (`id`, `product_id`, `related_product_id`, `created_at`) VALUES
(148, 39, 32, '2025-12-15 03:49:00'),
(153, 31, 29, '2025-12-15 05:59:41'),
(160, 44, 29, '2026-01-02 07:08:35');

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `reviewer_name` varchar(120) DEFAULT NULL,
  `reviewer_email` varchar(180) DEFAULT NULL,
  `rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `title` varchar(255) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `images` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `is_featured` tinyint(1) DEFAULT 0,
  `admin_note` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `moderated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `product_reviews`
--

INSERT INTO `product_reviews` (`id`, `product_id`, `reviewer_name`, `reviewer_email`, `rating`, `title`, `comment`, `images`, `status`, `is_featured`, `admin_note`, `created_at`, `moderated_at`) VALUES
(23, 29, 'Test User', 'test@example.com', 5.00, NULL, 'This is a test review.', NULL, 'approved', 0, NULL, '2025-11-29 17:42:39', NULL),
(27, 29, 'Chandru Prasath', 'chandrusri247@gmail.com', 5.00, NULL, 'Nalpamaradi Kera Thailam is often described as a \\\"miracle oil\\\" and a \\\"game-changer\\\" for the skin, with many users reporting remarkable results and considering it a \\\"must-buy\\\" for achieving a natural, radiant glow.', NULL, 'approved', 0, NULL, '2025-11-29 18:02:05', NULL),
(30, 33, 'harish panther k', 'pantherharish28@gmail.com', 5.00, NULL, 'This product really work good', NULL, 'approved', 0, NULL, '2025-12-14 09:29:28', NULL),
(31, 32, 'harish panther k', 'pantherharish28@gmail.com', 5.00, '', 'product review was changed', NULL, 'pending', 1, '', '2025-12-14 09:31:48', NULL),
(32, 37, 'harish panther k', 'pantherharish28@gmail.com', 5.00, NULL, 'testing', NULL, 'approved', 0, NULL, '2025-12-14 12:26:32', NULL),
(33, 29, 'harish panther k', 'pantherharish28@gmail.com', 5.00, NULL, 'test review', NULL, 'approved', 0, NULL, '2025-12-14 12:36:46', NULL),
(34, 33, 'Latha', NULL, 5.00, 'good', 'thanks', NULL, 'approved', 0, '', '2025-12-15 05:17:09', NULL),
(35, 32, 'Latha.P', NULL, 5.00, 'GREAT PRODUCT', 'SO GOOD THANKS', NULL, 'approved', 1, '', '2025-12-17 08:13:46', NULL),
(36, 37, 'Dev Elixir Natural Cosmetics', 'develixir74@gmail.com', 3.00, NULL, 'test', '[\"review_37_4_1765996446_0.jpeg\"]', 'approved', 0, NULL, '2025-12-17 18:34:06', NULL),
(37, 33, 'Dev Elixir Natural Cosmetics', 'develixir74@gmail.com', 3.00, NULL, 'test', NULL, 'approved', 0, NULL, '2025-12-17 18:38:01', NULL),
(38, 31, 'Dev Elixir Natural Cosmetics', 'develixir74@gmail.com', 1.00, NULL, 'test', NULL, 'approved', 0, NULL, '2025-12-17 18:41:20', NULL),
(39, 29, 'Dev Elixir Natural Cosmetics', 'develixir74@gmail.com', 1.00, NULL, 'yes', '[\"review_29_4_1766033310_0.png\"]', 'approved', 0, NULL, '2025-12-18 04:48:30', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_tags`
--

CREATE TABLE `product_tags` (
  `product_id` int(10) UNSIGNED NOT NULL,
  `tag_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `product_tags`
--

INSERT INTO `product_tags` (`product_id`, `tag_id`) VALUES
(29, 1),
(31, 1),
(32, 1),
(33, 1),
(29, 2),
(33, 2),
(37, 3),
(33, 4),
(35, 4),
(37, 7),
(37, 8),
(42, 9),
(42, 10),
(42, 11),
(42, 12),
(42, 13),
(42, 14),
(42, 15),
(42, 16),
(43, 18),
(44, 19),
(44, 20),
(44, 21),
(44, 22);

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `variant_name` varchar(255) NOT NULL,
  `type` varchar(20) DEFAULT 'custom',
  `linked_product_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `compare_price` decimal(10,2) DEFAULT NULL,
  `discount_percent` decimal(5,2) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `sku` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `custom_title` varchar(255) DEFAULT NULL,
  `custom_description` text DEFAULT NULL,
  `short_description` text DEFAULT NULL,
  `ingredients` text DEFAULT NULL,
  `how_to_use` text DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `custom_faqs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_faqs`)),
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `variant_name`, `type`, `linked_product_id`, `price`, `compare_price`, `discount_percent`, `stock`, `sku`, `image`, `is_active`, `custom_title`, `custom_description`, `short_description`, `ingredients`, `how_to_use`, `meta_title`, `meta_description`, `custom_faqs`, `images`) VALUES
(5, 29, 'KUNKUMADI', 'custom', NULL, 900.00, NULL, NULL, 10, '', 'var_1764326760_d6a56278.png', 1, 'KUNKUMADI TAILA To Improve Your Complexion And Get A Rejuvenated Glow', 'KUNKUMADI TAILA Improves complexion And Get A Rejuvenated Glow, This oil moisturizes your skin and makes your skin spotless and luster naturally. This is 100% natural with an Ayurvedic formulation.', 'KUNKUMADI TAILA Improves complexion And Get A Rejuvenated Glow, This oil moisturizes your skin and makes your skin spotless and luster naturally. This is 100% natural with an Ayurvedic formulation.', NULL, NULL, NULL, NULL, NULL, '[\"var_1764326760_d6a56278.png\"]'),
(6, 29, 'Anti-Acne', 'custom', NULL, 630.00, 700.00, 10.00, 10, '', 'var_1764328562_7b1a60b4.png', 1, 'Anti-Acne Face Cleanser Neem Sandalwood 100 ml', 'Anti-Acne  Facial Cleanser Enriched with Neem Sandalwood, Aloe vera, and Ayurvedic herbs. which helps to reduce and soothe acne, hydrate, and reduce open pores.', 'Anti-Acne  Facial Cleanser Enriched with Neem Sandalwood, Aloe vera, and Ayurvedic herbs. which helps to reduce and soothe acne, hydrate, and reduce open pores.', NULL, NULL, NULL, NULL, NULL, '[\"var_1764328562_7b1a60b4.png\",\"var_1764329027_9bf72894.png\",\"var_1764329027_492c1a23.jpg\"]'),
(15, 43, 'pack of 2', 'custom', NULL, 499.00, 699.00, 28.61, 10, 'KADP-02', 'var_1766381807_79de4026.jpg', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '[\"var_1766381807_79de4026.jpg\"]'),
(16, 44, '300 gm', 'custom', NULL, 699.00, 899.00, 22.25, 10, 'BBPO0-3', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '[]'),
(17, 44, '400', 'custom', NULL, 999.00, 1099.00, 9.10, 10, '', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '[]'),
(18, 44, '100 ml + 100 gm', 'custom', NULL, 689.00, 999.00, 31.03, 10, 'COMB-01', 'var_1766386649_29ec715e.jpg', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '[\"var_1766386649_29ec715e.jpg\"]');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `rating` int(11) NOT NULL,
  `review` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipments`
--

CREATE TABLE `shipments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shipment_number` varchar(64) NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `carrier` varchar(100) DEFAULT NULL,
  `tracking_number` varchar(128) DEFAULT NULL,
  `shipping_method` varchar(64) DEFAULT NULL,
  `shipping_cost` decimal(10,2) DEFAULT 0.00,
  `weight` decimal(10,2) DEFAULT 0.00,
  `status` enum('pending','label_created','in_transit','delivered','returned','cancelled') NOT NULL DEFAULT 'pending',
  `label_file` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `shipped_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `shipments`
--

INSERT INTO `shipments` (`id`, `shipment_number`, `order_id`, `carrier`, `tracking_number`, `shipping_method`, `shipping_cost`, `weight`, `status`, `label_file`, `created_by`, `created_at`, `shipped_at`, `notes`) VALUES
(1, 'SHIP-TEST-001', 1, 'BlueDart', NULL, 'Air Express', 120.00, 0.00, 'in_transit', NULL, NULL, '2025-11-19 10:02:26', '2025-12-13 01:48:38', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `shipment_items`
--

CREATE TABLE `shipment_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shipment_id` bigint(20) UNSIGNED NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `sku` varchar(128) DEFAULT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `weight` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `shipment_items`
--

INSERT INTO `shipment_items` (`id`, `shipment_id`, `product_name`, `sku`, `qty`, `weight`) VALUES
(1, 1, 'Sample Product A', 'SKU-001', 2, 0.50),
(2, 1, 'Sample Product B', 'SKU-002', 1, 1.20);

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'why_devilixirs', '✔ 100% Herbal formula\n✔ No chemicals or parabens\n✔ Crafted in small batches\n✔ Made in Chennai with care', '2025-11-27 15:25:00'),
(2, 'company_name', 'DEVELIXIR', '2025-12-22 10:47:05'),
(3, 'company_address', 'DevElixir Natural Cosmetics ™\r\nNo:6, 3rd Cross Street,\r\nKamatchiamman Garden, Sethukkarai,\r\nGudiyatham-632602, Vellore, Tamilnadu\r\nINDIA', '2025-12-22 10:47:05'),
(4, 'company_email', 'support@develixir.com', '2025-12-22 10:47:05'),
(5, 'company_phone', '+91 95006 50454', '2025-12-22 10:47:05'),
(6, 'tax_rate', '18', '2025-12-22 10:47:05'),
(61, 'footer_settings', '{\"about\":{\"description\":\"testing\",\"social_fb\":\"fffffff\",\"social_tw\":\"\",\"social_insta\":\"\",\"social_pin\":\"\"},\"link_columns\":[{\"title\":\"SHOP\",\"links\":[{\"label\":\"Baby care\",\"url\":\"https:\\/\\/newv2.develixirs.com\\/product.php?category[]=Baby+Care\"},{\"label\":\"Personal Care\",\"url\":\"https:\\/\\/newv2.develixirs.com\\/product.php?cat=33\"}]},{\"title\":\"About\",\"links\":[{\"label\":\"About us\",\"url\":\"https:\\/\\/newv2.develixirs.com\\/page.php?slug=about-us\"},{\"label\":\"Community\",\"url\":\"https:\\/\\/newv2.develixirs.com\\/page.php?slug=community-ethics\"},{\"label\":\"Our Services\",\"url\":\"https:\\/\\/newv2.develixirs.com\\/page.php?slug=our-services\"}]},{\"title\":\"Links\",\"links\":[{\"label\":\"My Account\",\"url\":\"https:\\/\\/newv2.develixirs.com\\/login.php\"},{\"label\":\"My Order\",\"url\":\"https:\\/\\/newv2.develixirs.com\\/my-profile.php\"}]},{\"title\":\"New Column\",\"links\":[{\"label\":\"test\",\"url\":\"\"}]}],\"contact\":{\"address_line1\":\"DevElixir Natural Cosmetics \\u2122\",\"address_line2\":\"No:6, 3rd Cross Street,\",\"address_line3\":\"Kamatchiamman Garden, Sethukkarai,\",\"address_city\":\"Gudiyatham-632602, Vellore, Tamilnadu\",\"address_country\":\"INDIA\",\"email\":\"sales@develixirs.com\",\"phone\":\"+91 95006 50454\"},\"gallery\":{\"title\":\"Gallery\",\"images\":[\"\\/assets\\/uploads\\/media\\/ebbe7188bc5143bfbd04889b6c4a5422_Rooted_in_Ayurveda__Crafted_with_Integrity.jpg\",\"\\/assets\\/uploads\\/media\\/7e6d969df3b14d118c9889cdaf2c0fee_Give_your_feet_the_feather-touch_care_copy.jpg\",\"\",\"\",\"\",\"\"]}}', '2025-12-29 14:13:08'),
(80, 'subscribe_image', '/assets/uploads/media/b1d5795c0f81492abb436300e45f5aaf_pngwing_com-9.jpg', '2026-01-05 02:34:12'),
(127, 'our_story_image', '/assets/uploads/media/b1d5795c0f81492abb436300e45f5aaf_pngwing_com-9.jpg', '2026-01-05 04:05:23'),
(173, 'home_video_url', '/assets/uploads/media/6d8b5136630444888c993cbe87fafc7b_men_useing_eyebrow_growth_oil.mp4', '2026-01-05 04:51:34'),
(174, 'home_video_title', 'test', '2026-01-05 04:51:34'),
(175, 'home_video_desc', '<p><strong>DevElixir provides the best Ayurvedic beauty products for online shopping</strong></p>\r\n\r\n<p>DevElixir&rsquo;s Ayurvedic beauty products perfectly embody natural beauty, blending traditional Indian medicine with modern skincare innovations to ensure pure and authentic results. Each product is carefully crafted to enhance your skin and promote overall well-being.</p>\r\n\r\n<p>With a wide range of offerings, from facial serums to nourishing hair oils that help maintain a youthful appearance, DevElixir caters to all your skincare needs. Our formulas are free from harmful chemicals and synthetic additives, preserving your skin&rsquo;s inherent radiance.</p>\r\n', '2026-01-05 04:51:34'),
(176, 'home_video_btn_text', 'contact', '2026-01-05 04:51:34'),
(177, 'home_video_btn_link', '', '2026-01-05 04:51:34'),
(178, 'home_video_btn_color', '#4f46e5', '2026-01-05 04:51:34'),
(179, 'home_seo_title', 'skin care beauty', '2026-01-05 04:51:34'),
(180, 'home_seo_description', 'best ayurvedic skin care', '2026-01-05 04:51:34'),
(181, 'our_story_title', '', '2026-01-05 04:51:34'),
(182, 'our_story_description', '', '2026-01-05 04:51:34'),
(183, 'cert_section_title', '', '2026-01-05 04:51:34'),
(184, 'cert_section_icon', 'fa-solid fa-award', '2026-01-05 04:51:34'),
(185, 'features_json', '[{\"icon\":\"fa-solid fa-earth-americas\",\"title\":\"Worldwide Shipping\",\"desc\":\"Free worldwide shipping across the globe\"},{\"icon\":\"fa-brands fa-whatsapp\",\"title\":\"Whatsapp Customer\",\"desc\":\"24-day hassle-free return policy\"},{\"icon\":\"fa-regular fa-credit-card\",\"title\":\"Secured Payments\",\"desc\":\"We accept all major credit cards\"},{\"icon\":\"fa-solid fa-truck-fast\",\"title\":\"Quick Delivery\",\"desc\":\"Free shipping across India above \\u20b9499\"},{\"icon\":\"fa-solid fa-leaf\",\"title\":\"Freshly Made\",\"desc\":\"We make your produce fresh batches\"}]', '2026-01-05 04:51:34'),
(186, 'our_stories_json', '[{\"title\":\"A New Era in Natural Beauty\",\"description\":\"Pioneering sustainable practices with traditional Ayurvedic wisdom.\",\"image\":\"\\/assets\\/uploads\\/media\\/25486f8d37ad4105bd15d2b15f03acc2_community_and_etics_copy.jpg\"},{\"title\":\"A Multi-Sensorial Journey\",\"description\":\"Authentic roots, sophisticated presentation, immersive experience.\",\"image\":\"\"},{\"title\":\"Fresh, Pure, Potent\",\"description\":\"Handcrafted using 100% natural ingredients from the Indian landscape.\",\"image\":\"\"}]', '2026-01-05 04:51:34'),
(187, 'quick_links_json', '[\"4\",\"3\",\"7\",\"6\"]', '2026-01-05 04:51:34'),
(188, 'cert_badges_json', '[{\"title\":\"GMP Certified\",\"image\":\"assets\\/uploads\\/badges\\/badge_1767588694_0_GMP CERTIFICATE-DEVELIXIR NATURAL COSMETICS.png\"},{\"title\":\"AYUSH Premium\",\"image\":\"assets\\/uploads\\/badges\\/badge_1767588694_1_AYUSH CERTIFIED DEV ELIXIR NATURAL COSMETICS.png\"},{\"title\":\"ISO 9001:2015\",\"image\":\"assets\\/uploads\\/badges\\/badge_1767588694_2_ISO CERTIFIED DEVELIXIER NATURAL COSMETICS.png\"}]', '2026-01-05 04:51:34');

-- --------------------------------------------------------

--
-- Table structure for table `subscription_plans`
--

CREATE TABLE `subscription_plans` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL DEFAULT 'Monthly Subscription',
  `price` decimal(10,2) NOT NULL,
  `compare_price` decimal(10,2) DEFAULT NULL COMMENT 'Original price before discount',
  `discount_percentage` decimal(5,2) NOT NULL DEFAULT 10.00,
  `billing_cycle` enum('monthly','yearly') NOT NULL DEFAULT 'monthly',
  `benefits` text DEFAULT NULL COMMENT 'JSON array of benefits',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `auto_renew_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `validity_days` int(11) NOT NULL DEFAULT 30,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci COMMENT='Subscription plan configurations';

--
-- Dumping data for table `subscription_plans`
--

INSERT INTO `subscription_plans` (`id`, `name`, `price`, `compare_price`, `discount_percentage`, `billing_cycle`, `benefits`, `is_active`, `auto_renew_enabled`, `validity_days`, `created_at`, `updated_at`) VALUES
(1, 'Premium Membership', 1000.00, 1500.00, 10.00, 'monthly', '[\"Guaranteed 10% discount on every product\",\"Subscriber-only special offers\",\"Priority customer support\",\"Early access to new arrivals\",\"Exclusive festival deals\",\"Rewards points boost\"]', 1, 1, 33, '2025-12-07 08:34:29', '2025-12-07 09:43:50'),
(2, 'Premium Membership', 1000.00, NULL, 10.00, 'monthly', '[\"Guaranteed 10% discount on every product\",\"Subscriber-only special offers\",\"Priority customer support\",\"Early access to new arrivals\",\"Exclusive festival deals\",\"Rewards points boost\"]', 1, 1, 30, '2025-12-07 09:32:07', '2025-12-07 09:32:07');

-- --------------------------------------------------------

--
-- Table structure for table `subscription_transactions`
--

CREATE TABLE `subscription_transactions` (
  `id` int(11) NOT NULL,
  `user_subscription_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_id` varchar(255) DEFAULT NULL COMMENT 'Gateway payment ID',
  `payment_status` enum('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  `payment_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci COMMENT='Subscription payment history';

--
-- Dumping data for table `subscription_transactions`
--

INSERT INTO `subscription_transactions` (`id`, `user_subscription_id`, `user_id`, `plan_id`, `amount`, `payment_method`, `payment_id`, `payment_status`, `payment_date`, `created_at`) VALUES
(1, 1, 4, 1, 1000.00, NULL, 'order_Rof8ugtKZOEkxV', 'pending', NULL, '2025-12-07 08:53:53'),
(2, 2, 4, 1, 1000.00, NULL, 'order_RrPr6gSJK5Osbh', 'pending', NULL, '2025-12-14 07:53:38');

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tags`
--

INSERT INTO `tags` (`id`, `name`, `slug`, `is_active`, `created_at`) VALUES
(1, 'Hair Fall', 'hair-fall', 1, '2025-11-24 10:40:05'),
(2, 'Anti Dandruf', 'anti-dandruf', 1, '2025-11-24 10:40:35'),
(3, 'skin brighten', 'skin-brighten', 1, '2025-12-05 23:05:16'),
(4, 'test', 'test', 0, '2025-12-06 06:23:49'),
(5, 'testing', 'testing', 1, '2025-12-06 19:19:42'),
(6, 'testu', 'testu', 1, '2025-12-07 02:56:18'),
(7, 'bridal skincare', 'bridal-skincare', 1, '2025-12-14 10:08:08'),
(8, 'baby skin lightening', 'baby-skin-lightening', 1, '2025-12-14 10:08:08'),
(9, 'ayurvedic shampoo', 'ayurvedic-shampoo', 1, '2025-12-18 12:19:19'),
(10, 'herbal shampoo', 'herbal-shampoo', 1, '2025-12-18 12:19:19'),
(11, 'shikakai shampoo', 'shikakai-shampoo', 1, '2025-12-18 12:19:19'),
(12, 'natural shampoo', 'natural-shampoo', 1, '2025-12-18 12:19:19'),
(13, 'ayurvedic herbal shampoo', 'ayurvedic-herbal-shampoo', 1, '2025-12-18 12:19:19'),
(14, 'herbal hair cleanser', 'herbal-hair-cleanser', 1, '2025-12-18 12:19:19'),
(15, 'chemical free shampoo', 'chemical-free-shampoo', 1, '2025-12-18 12:19:19'),
(16, 'traditional ayurvedic shampoo', 'traditional-ayurvedic-shampoo', 1, '2025-12-18 12:19:19'),
(17, 'test', 'test-1', 1, '2025-12-18 12:41:54'),
(18, 'teeth whitening', 'teeth-whitening', 1, '2025-12-21 22:37:25'),
(19, 'bath powder', 'bath-powder', 1, '2025-12-22 06:33:41'),
(20, 'ubtan', 'ubtan', 1, '2025-12-22 06:33:41'),
(21, 'skin glow', 'skin-glow', 1, '2025-12-22 06:33:41'),
(22, 'babybodywash', 'babybodywash', 1, '2025-12-22 06:33:41');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `otp` varchar(6) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `role` varchar(50) NOT NULL DEFAULT 'admin',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_subscriber` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Is user currently subscribed',
  `current_subscription_id` int(11) DEFAULT NULL COMMENT 'FK to user_subscriptions',
  `subscription_expires_at` date DEFAULT NULL COMMENT 'Subscription expiry date'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `google_id`, `phone`, `gender`, `password`, `otp`, `is_verified`, `role`, `is_active`, `created_at`, `is_subscriber`, `current_subscription_id`, `subscription_expires_at`) VALUES
(1, 'Administrator', 'admin@admin.com', NULL, NULL, NULL, '$2y$10$oFAJprvwTTu.nqCb0OjvweqcSTTJmfAl.0TrdCb69zh3tsPIkNT4q', NULL, 1, 'admin', 1, '2025-11-18 02:18:22', 0, NULL, NULL),
(2, 'Chandru Prasath', 'chandrusri247@gmail.com', '113656964454919534640', '+918946071785', NULL, '$2y$12$1g.Rf956kFI/hfHeLW2bD.w/tnv9WKAmL6hfnnL2aDBbESLHzEoxm', NULL, 1, 'admin', 1, '2025-11-25 11:26:26', 0, NULL, NULL),
(3, 'harish panther k', 'pantherharish28@gmail.com', '111712213758281880057', '+91 8946071785', 'male', '$2y$12$c/7uK.uAaMnoMgCUIH7ZL.3lW119TkZcLZsxcxIu4G9zAwDj/AarS', NULL, 1, 'admin', 1, '2025-11-27 07:59:26', 0, NULL, NULL),
(4, 'Dev Elixir Natural Cosmetics', 'develixir74@gmail.com', '100580337042844906248', NULL, NULL, '$2y$10$uTtfhd4GnCGmxN1C7MLP0OqxhFuxHYuWanqyD/Gh3ECbntK/vnvvq', NULL, 0, 'customer', 1, '2025-12-05 02:26:13', 0, NULL, NULL),
(6, 'Admin', 'admin@develixirs.com', NULL, NULL, NULL, '$2y$10$UyyYzpDWIKoqtR.gPdGP..3LfyhQ5ej1KrX.9llV2amoCiDY4p.32', NULL, 0, 'admin', 1, '2025-12-05 04:17:33', 0, NULL, NULL),
(9, 'Zup Tech', 'zup.tech.in@gmail.com', '106451843236814754031', NULL, NULL, '$2y$10$oBRRyHpGRPiZr0RNQ/wBAecezRo2YH0WXgi2IisSwEu/Ulnkvx4Hm', NULL, 0, 'customer', 1, '2025-12-18 16:48:18', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address_line1` text NOT NULL,
  `address_line2` text DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `pincode` varchar(10) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_addresses`
--

INSERT INTO `user_addresses` (`id`, `user_id`, `full_name`, `phone`, `address_line1`, `address_line2`, `city`, `state`, `pincode`, `is_default`, `created_at`) VALUES
(3, 2, 'Chandru Sri', '08946071785', '18/4balgangathara Thilagar St', '18', 'Vellore', 'Tamil Nadu', '632602', 1, '2025-11-27 05:23:51'),
(4, 3, 'Chandru Prasath', '08946071785', 'East Wing, Arcot Road, Shyamala Towers, Saligramam, Chennai, Tamil Nadu 600093', '', 'Chennai', 'Tamil Nadu', '600093', 0, '2025-11-27 13:35:10'),
(5, 3, 'harish', '9363076177', '18/4 balagangathara thilagar st pichanoor pet gudiyattam', '', 'Vellore', 'Tamil Nadu', '632602', 0, '2025-11-27 13:36:43'),
(6, 3, 'harish', '09363076177', '18/4 balagangathara thilagar st pichanoor pet gudiyattam', '', 'Vellore', 'Tamil Nadu', '632602', 0, '2025-11-27 13:55:52'),
(7, 3, 'Chandru Prasath', '08946071785', 'East Wing, Arcot Road, Shyamala Towers, Saligramam, Chennai, Tamil Nadu 600093', '18', 'Chennai', 'Tamil Nadu', '600093', 1, '2025-11-27 16:20:20'),
(8, 2, 'panther harish', '9363076177', '18/4 venakata chalapathi nagar', '18', 'Chennai', 'Tamil Nadu', '632607', 0, '2025-11-29 10:31:08'),
(9, 2, 'Chandru Prasath', '08946071785', '18/4 balagangathara thilagar st pichanoor pet gudiyattam', '18', 'Chennai', 'Tamil Nadu', '600093', 0, '2025-12-01 06:07:12'),
(10, 4, 'Chandru Prasath', '08946071785', '18/4 balagangathara thilagar st pichanoor pet gudiyattam', '18', 'Vellore', 'Tamil Nadu', '632602', 1, '2025-12-05 02:27:24');

-- --------------------------------------------------------

--
-- Table structure for table `user_notifications`
--

CREATE TABLE `user_notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_notifications`
--

INSERT INTO `user_notifications` (`id`, `user_id`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 1, 'Welcome!', 'Welcome to Develixirs. Happy shopping!', 0, '2025-11-27 16:44:46');

-- --------------------------------------------------------

--
-- Table structure for table `user_subscriptions`
--

CREATE TABLE `user_subscriptions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `status` enum('active','expired','cancelled','pending') NOT NULL DEFAULT 'pending',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `auto_renew` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci COMMENT='User subscription records';

--
-- Dumping data for table `user_subscriptions`
--

INSERT INTO `user_subscriptions` (`id`, `user_id`, `plan_id`, `status`, `start_date`, `end_date`, `auto_renew`, `created_at`, `updated_at`) VALUES
(1, 4, 1, 'pending', '2025-12-07', '2026-01-06', 1, '2025-12-07 08:53:53', '2025-12-07 08:53:53'),
(2, 4, 1, 'pending', '2025-12-14', '2026-01-16', 1, '2025-12-14 07:53:38', '2025-12-14 07:53:38');

-- --------------------------------------------------------

--
-- Table structure for table `user_upi`
--

CREATE TABLE `user_upi` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `upi_id` varchar(255) NOT NULL,
  `provider` varchar(100) DEFAULT 'UPI',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_upi`
--

INSERT INTO `user_upi` (`id`, `user_id`, `upi_id`, `provider`, `created_at`) VALUES
(1, 1, 'testuser@okhdfcbank', 'Google Pay', '2025-11-27 16:24:10'),
(2, 1, 'testuser@oksbi', 'PhonePe', '2025-11-27 16:24:10');

-- --------------------------------------------------------

--
-- Table structure for table `variant_faqs`
--

CREATE TABLE `variant_faqs` (
  `id` int(11) NOT NULL,
  `variant_id` int(11) NOT NULL,
  `question` varchar(500) NOT NULL,
  `answer` text NOT NULL,
  `display_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `variant_faqs`
--

INSERT INTO `variant_faqs` (`id`, `variant_id`, `question`, `answer`, `display_order`) VALUES
(87, 7, 'Hi men can be use it??', 'yes neem is perfect for all include men also', 0),
(88, 6, 'Reduces Acne/Pimples', 'The combination of Neem, Sandalwood, and Ayurvedic herbs likely contributes to the anti-inflammatory and antimicrobial properties, which can help reduce the occurrence of acne and pimples.', 0),
(89, 5, 'Lightening Dark Circles', 'The oil’s herbal ingredients are particularly effective in lightening the skin under the eyes, reducing the appearance of dark circles. This can contribute to a more youthful and refreshed appearance', 0);

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `product_id`, `created_at`) VALUES
(3, 2, 29, '2025-11-29 09:13:37'),
(12, 4, 33, '2025-12-08 03:14:44');

-- --------------------------------------------------------

--
-- Table structure for table `wishlists`
--

CREATE TABLE `wishlists` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `wishlists`
--

INSERT INTO `wishlists` (`id`, `user_id`, `product_id`, `created_at`) VALUES
(2, 2, 29, '2025-11-28 12:47:30');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `authors`
--
ALTER TABLE `authors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `blogs`
--
ALTER TABLE `blogs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `slug` (`slug`),
  ADD KEY `is_published` (`is_published`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `fk_blogs_author` (`author_id`);

--
-- Indexes for table `blog_categories`
--
ALTER TABLE `blog_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `blog_post_tags`
--
ALTER TABLE `blog_post_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_blog_tag` (`blog_id`,`tag_id`),
  ADD KEY `idx_blog_id` (`blog_id`),
  ADD KEY `idx_tag_id` (`tag_id`);

--
-- Indexes for table `blog_related`
--
ALTER TABLE `blog_related`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_relation` (`blog_id`,`related_blog_id`),
  ADD KEY `related_blog_id` (`related_blog_id`),
  ADD KEY `idx_blog_id` (`blog_id`);

--
-- Indexes for table `blog_tags`
--
ALTER TABLE `blog_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_name` (`name`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_code` (`code`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_offer_type` (`offer_type`),
  ADD KEY `idx_dates` (`start_date`,`end_date`);

--
-- Indexes for table `coupon_categories`
--
ALTER TABLE `coupon_categories`
  ADD PRIMARY KEY (`coupon_id`,`category_id`),
  ADD KEY `fk_category` (`category_id`);

--
-- Indexes for table `coupon_products`
--
ALTER TABLE `coupon_products`
  ADD PRIMARY KEY (`coupon_id`,`product_id`),
  ADD KEY `fk_product` (`product_id`);

--
-- Indexes for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_coupon_user` (`coupon_id`,`user_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_order` (`order_id`);

--
-- Indexes for table `filter_groups`
--
ALTER TABLE `filter_groups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `filter_group_values`
--
ALTER TABLE `filter_group_values`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`);

--
-- Indexes for table `filter_options`
--
ALTER TABLE `filter_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_group` (`group_id`);

--
-- Indexes for table `homepage_products`
--
ALTER TABLE `homepage_products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `fk_reviews_order` (`order_id`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `media_files`
--
ALTER TABLE `media_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_uploaded_at` (`uploaded_at`),
  ADD KEY `idx_deleted_at` (`deleted_at`),
  ADD KEY `idx_mime_type` (`mime_type`),
  ADD KEY `idx_uploaded_by` (`uploaded_by`);

--
-- Indexes for table `media_file_tags`
--
ALTER TABLE `media_file_tags`
  ADD PRIMARY KEY (`media_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indexes for table `media_folders`
--
ALTER TABLE `media_folders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_parent_id` (`parent_id`);

--
-- Indexes for table `media_tags`
--
ALTER TABLE `media_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `media_usage`
--
ALTER TABLE `media_usage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_media_id` (`media_id`),
  ADD KEY `idx_entity` (`entity_type`,`entity_id`);

--
-- Indexes for table `media_variants`
--
ALTER TABLE `media_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_media_id` (`media_id`);

--
-- Indexes for table `media_versions`
--
ALTER TABLE `media_versions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_media_version` (`media_id`,`version_number`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_addresses`
--
ALTER TABLE `order_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_faqs`
--
ALTER TABLE `product_faqs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_filter_values`
--
ALTER TABLE `product_filter_values`
  ADD PRIMARY KEY (`product_id`,`filter_value_id`),
  ADD KEY `filter_value_id` (`filter_value_id`);

--
-- Indexes for table `product_groups`
--
ALTER TABLE `product_groups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_group_map`
--
ALTER TABLE `product_group_map`
  ADD PRIMARY KEY (`product_id`,`group_id`),
  ADD KEY `fk_pg_map_group` (`group_id`);

--
-- Indexes for table `product_labels`
--
ALTER TABLE `product_labels`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_relations`
--
ALTER TABLE `product_relations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_relation` (`product_id`,`related_product_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_related_product_id` (`related_product_id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_tags`
--
ALTER TABLE `product_tags`
  ADD PRIMARY KEY (`product_id`,`tag_id`),
  ADD KEY `fk_product_tags_tag` (`tag_id`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `shipments`
--
ALTER TABLE `shipments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `shipment_number` (`shipment_number`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `shipment_items`
--
ALTER TABLE `shipment_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shipment_id` (`shipment_id`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subscription_transactions`
--
ALTER TABLE `subscription_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_subscription_id` (`user_subscription_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `payment_status` (`payment_status`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `google_id` (`google_id`);

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_subscriptions`
--
ALTER TABLE `user_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `plan_id` (`plan_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `user_upi`
--
ALTER TABLE `user_upi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `variant_faqs`
--
ALTER TABLE `variant_faqs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_variant` (`variant_id`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_wishlist` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_product` (`user_id`,`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `authors`
--
ALTER TABLE `authors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT for table `blogs`
--
ALTER TABLE `blogs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `blog_categories`
--
ALTER TABLE `blog_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `blog_post_tags`
--
ALTER TABLE `blog_post_tags`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT for table `blog_related`
--
ALTER TABLE `blog_related`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `blog_tags`
--
ALTER TABLE `blog_tags`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `filter_groups`
--
ALTER TABLE `filter_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `filter_group_values`
--
ALTER TABLE `filter_group_values`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `filter_options`
--
ALTER TABLE `filter_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `homepage_products`
--
ALTER TABLE `homepage_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `order_addresses`
--
ALTER TABLE `order_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `product_faqs`
--
ALTER TABLE `product_faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `product_groups`
--
ALTER TABLE `product_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `product_labels`
--
ALTER TABLE `product_labels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `product_relations`
--
ALTER TABLE `product_relations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=161;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipments`
--
ALTER TABLE `shipments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `shipment_items`
--
ALTER TABLE `shipment_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=189;

--
-- AUTO_INCREMENT for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `subscription_transactions`
--
ALTER TABLE `subscription_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `user_notifications`
--
ALTER TABLE `user_notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_subscriptions`
--
ALTER TABLE `user_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_upi`
--
ALTER TABLE `user_upi`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `variant_faqs`
--
ALTER TABLE `variant_faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `authors`
--
ALTER TABLE `authors`
  ADD CONSTRAINT `authors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `blogs`
--
ALTER TABLE `blogs`
  ADD CONSTRAINT `fk_blogs_author` FOREIGN KEY (`author_id`) REFERENCES `authors` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `blog_post_tags`
--
ALTER TABLE `blog_post_tags`
  ADD CONSTRAINT `blog_post_tags_ibfk_1` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `blog_post_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `blog_tags` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `blog_related`
--
ALTER TABLE `blog_related`
  ADD CONSTRAINT `blog_related_ibfk_1` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `blog_related_ibfk_2` FOREIGN KEY (`related_blog_id`) REFERENCES `blogs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coupon_categories`
--
ALTER TABLE `coupon_categories`
  ADD CONSTRAINT `fk_categories_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_coupon_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coupon_products`
--
ALTER TABLE `coupon_products`
  ADD CONSTRAINT `fk_coupon_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_products_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  ADD CONSTRAINT `fk_usage_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_usage_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_usage_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `filter_group_values`
--
ALTER TABLE `filter_group_values`
  ADD CONSTRAINT `filter_group_values_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `filter_groups` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `filter_options`
--
ALTER TABLE `filter_options`
  ADD CONSTRAINT `fk_group` FOREIGN KEY (`group_id`) REFERENCES `filter_groups` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `fk_reviews_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `media_file_tags`
--
ALTER TABLE `media_file_tags`
  ADD CONSTRAINT `media_file_tags_ibfk_1` FOREIGN KEY (`media_id`) REFERENCES `media_files` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `media_file_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `media_tags` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `media_usage`
--
ALTER TABLE `media_usage`
  ADD CONSTRAINT `media_usage_ibfk_1` FOREIGN KEY (`media_id`) REFERENCES `media_files` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `media_variants`
--
ALTER TABLE `media_variants`
  ADD CONSTRAINT `media_variants_ibfk_1` FOREIGN KEY (`media_id`) REFERENCES `media_files` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `media_versions`
--
ALTER TABLE `media_versions`
  ADD CONSTRAINT `media_versions_ibfk_1` FOREIGN KEY (`media_id`) REFERENCES `media_files` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_addresses`
--
ALTER TABLE `order_addresses`
  ADD CONSTRAINT `order_addresses_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_faqs`
--
ALTER TABLE `product_faqs`
  ADD CONSTRAINT `product_faqs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_filter_values`
--
ALTER TABLE `product_filter_values`
  ADD CONSTRAINT `product_filter_values_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_filter_values_ibfk_2` FOREIGN KEY (`filter_value_id`) REFERENCES `filter_group_values` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_group_map`
--
ALTER TABLE `product_group_map`
  ADD CONSTRAINT `fk_pg_map_group` FOREIGN KEY (`group_id`) REFERENCES `product_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pg_map_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_relations`
--
ALTER TABLE `product_relations`
  ADD CONSTRAINT `product_relations_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_relations_ibfk_2` FOREIGN KEY (`related_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_tags`
--
ALTER TABLE `product_tags`
  ADD CONSTRAINT `fk_product_tags_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_product_tags_tag` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipments`
--
ALTER TABLE `shipments`
  ADD CONSTRAINT `shipments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipment_items`
--
ALTER TABLE `shipment_items`
  ADD CONSTRAINT `shipment_items_ibfk_1` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD CONSTRAINT `user_notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_upi`
--
ALTER TABLE `user_upi`
  ADD CONSTRAINT `user_upi_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `variant_faqs`
--
ALTER TABLE `variant_faqs`
  ADD CONSTRAINT `variant_faqs_ibfk_1` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
