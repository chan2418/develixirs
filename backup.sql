-- MySQL dump 10.13  Distrib 9.5.0, for macos15.7 (arm64)
--
-- Host: 127.0.0.1    Database: develixirs_db
-- ------------------------------------------------------
-- Server version	8.0.44

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `banners`
--

DROP TABLE IF EXISTS `banners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `banners` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT '',
  `link` text,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `page_slot` varchar(32) NOT NULL DEFAULT 'home',
  `category_id` int DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `banners`
--

LOCK TABLES `banners` WRITE;
/*!40000 ALTER TABLE `banners` DISABLE KEYS */;
INSERT INTO `banners` VALUES (3,'Homepage banner','1763884825-1043deea86ec.jpeg','Welcome banner','http://127.0.0.1:8003/admin/banner.php',1,'2025-11-23 08:00:25','2025-11-23 08:00:25','home',NULL,NULL),(4,'omkarslider-m1-1-1250x500','1763899414-4b34b58dbd5b.jpg','Welcome banner','http://127.0.0.1:8003/admin/banner.php',1,'2025-11-23 12:03:34','2025-11-23 12:03:34','home',NULL,NULL),(18,'Banner 2025-11-24 04:26:10','1763958370-45607e9389.webp','','',1,'2025-11-24 04:26:10','2025-11-24 04:26:10','home_sidebar',NULL,NULL),(19,'Banner 2025-11-24 04:30:39','1763958639-f7e505c329.webp','','http://127.0.0.1:8003/admin/banner.php',1,'2025-11-24 04:30:39','2025-11-24 04:30:39','home_sidebar',NULL,NULL),(23,'Banner 2025-11-24 09:44:42','1763977482-ae024bd1ab.jpg','','https://www.google.com/search?num=10&newwindow=1&sca_esv=2d910e10f9e5456a&sxsrf=AE3TifO9tBra2CrsPjXqQh99Gjv1cyEOqg:1763898693488&udm=2&fbs=AIIjpHxU7SXXniUZfeShr2fp4giZ1Y6MJ25_tmWITc7uy4KIeioyp3OhN11EY0n5qfq-zENwnGygERInUV_0g0XKeHGJRAdFPaX_SSIJt7xYUfpm-_vQHq3hzHA2DKyn4_QBnQmzJzRbbjOPw1BvHSgNexNCWYY3W1po7cj-turW4QANz87mzlbnYh0yzluxEnlslmki5VQphgTLwzF5rw5S350lSej9Rw&q=banner+for+herbal+products&sa=X&ved=2ahUKEwi7963YmoiRAxXETGwGHVKXIbMQtKgLegQIEBAB&biw=1440&bih=715&dpr=2#vhid=7n5oaEnzHJDSdM&vssid=mosaic',1,'2025-11-24 09:44:42','2025-11-24 09:44:42','home_center',NULL,NULL),(24,'Banner 2025-11-24 12:11:13','1763986273-36082bee0c.jpg','','',1,'2025-11-24 12:11:13','2025-11-24 12:11:13','home_offer',NULL,NULL),(25,'Banner 2025-11-25 01:06:17','1764032777-07bb693f30.jpeg','','',1,'2025-11-25 01:06:17','2025-11-25 01:06:17','product',NULL,NULL),(26,'Banner 2025-11-25 01:35:49','1764034549-de7b387273.jpg','','',1,'2025-11-25 01:35:49','2025-11-25 01:35:49','product',NULL,NULL),(28,'Banner 2025-11-25 10:56:37','1764068197-531e3766c1.png','','',1,'2025-11-25 10:03:11','2025-11-25 10:03:11','home_offer',NULL,NULL),(31,'Banner 2025-11-26 04:03:49','1764129829-0f1741f6d7.jpg','','http://127.0.0.1:8003/admin/banner.php',1,'2025-11-26 04:03:49','2025-11-26 04:03:49','top_category',4,NULL),(32,'Banner 2025-11-26 04:04:35','1764129875-4e6451713e.webp','','http://127.0.0.1:8003/admin/banner.php',1,'2025-11-26 04:04:35','2025-11-26 04:04:35','top_category',10,NULL),(34,'Banner 2025-11-26 11:05:33','1764155133-0bf8635920.png','','http://127.0.0.1:8003/admin/banner.php',1,'2025-11-26 11:05:33','2025-11-26 11:05:33','category',8,NULL),(35,'Banner 2025-11-26 11:06:27','1764155187-1d999450aa.webp','','http://127.0.0.1:8003/admin/banner.php',1,'2025-11-26 11:06:27','2025-11-26 11:06:27','category',7,NULL),(37,'Banner 2025-11-26 11:09:29','1764155369-91bdf11bf7.jpg','','http://127.0.0.1:8003/admin/banner.php',1,'2025-11-26 11:09:29','2025-11-26 11:09:29','category',9,NULL),(39,'Banner 2025-11-26 11:53:22','1764158002-597297e414.webp','','http://127.0.0.1:8003/admin/banner.php',1,'2025-11-26 11:53:22','2025-11-26 11:53:22','product_sidebar',NULL,NULL),(45,'Banner 2025-11-28 01:48:44','1764294524-92be6d27bd.jpg','','http://127.0.0.1:8003/admin/banner.php',1,'2025-11-28 01:48:44','2025-11-28 01:48:44','product_detail_sidebar',NULL,NULL),(48,'Banner 2025-11-29 17:02:57','1764435777-d0e8c6cb13.jpg','','http://127.0.0.1:8003/admin/banner.php',1,'2025-11-29 17:02:57','2025-11-29 17:02:57','blog',NULL,NULL);
/*!40000 ALTER TABLE `banners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blogs`
--

DROP TABLE IF EXISTS `blogs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blogs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` mediumtext COLLATE utf8mb4_unicode_ci,
  `author` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category_id` int unsigned DEFAULT NULL,
  `featured_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `slug` (`slug`),
  KEY `is_published` (`is_published`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blogs`
--

LOCK TABLES `blogs` WRITE;
/*!40000 ALTER TABLE `blogs` DISABLE KEYS */;
INSERT INTO `blogs` VALUES (2,'black panther','panther harish','one panther','act dumb play smart','The sun rose over Blackfang Island, casting long shadows across the jungle.\r\n\r\nDeep within the emerald canopy, a young warrior named Panther Harish stretched, claws glinting.\r\n\r\nHarish wasn’t a normal panther—he walked upright, talked, and dreamed bigger than any beast alive.\r\n\r\nHis dream was simple: to become the King of All Wilds.\r\n\r\nBut to claim that title, he needed the legendary artifact known as the Midnight Crown.\r\n\r\nRumors said it was hidden somewhere beyond the Great Tempest Seas.\r\n\r\nHarish grinned, slung his leaf-woven satchel on his back, and dashed through the forest.\r\n\r\n“Adventure starts today!” he roared.\r\n\r\nVillagers stared at him with a mix of fear and awe.\r\n\r\n“Panther Harish, you’re crazy,” one elder muttered.\r\n\r\nBut like Luffy, Harish simply laughed at danger.\r\n\r\nAt the edge of the coast, he found his makeshift boat—barely floating.\r\n\r\n“Perfect!” he said confidently.\r\n\r\nHe pushed off the shore and set sail toward destiny.\r\n\r\nDays passed with storms tossing the tiny vessel.\r\n\r\nHarish held on with claws dug into the rope.\r\n\r\n“Nothing can stop a future king!” he yelled at the sky.\r\n\r\nHis boat eventually crashed onto Skull Reef Island.\r\n\r\nThere, he met Tiko the Spear-Monkey, a quick fighter with a sharp mind.\r\n\r\nTiko attacked him at first, thinking Harish was a threat.\r\n\r\nAfter a fierce scuffle, Harish offered him food instead of revenge.\r\n\r\n“Join my crew,” Harish said.\r\n\r\nTiko blinked, then smiled. “Only if I get to be vice-captain.”\r\n\r\n“Deal!” Harish said instantly.\r\n\r\nTogether, they explored the reef caves searching for supplies.\r\n\r\nThey found glowing stones and ancient carvings','Administrator',NULL,'/assets/uploads/blogs/blog_692aaedde6901.PNG',1,'2025-11-29 06:10:12','2025-11-29 08:29:18'),(3,'Men care','mens-care','','','Men’s Care products are designed to meet the daily grooming and skincare needs of modern men. From cleansing and moisturizing to shaving and styling, these products help maintain healthy skin, hair, and overall appearance. Easy to use and suitable for all skin types.\r\n\r\n2. Professional Product Description\r\n\r\nOur Men’s Care range is specially formulated to address the unique skin and grooming concerns of men. Each product delivers effective cleansing, hydration, and protection, helping you maintain a fresh, confident look throughout the day. Perfect for everyday use and crafted with high-quality ingredients for long-lasting results.','Administrator',4,'/assets/uploads/blogs/blog_692ae6cd72292.PNG',1,'2025-11-29 09:03:39','2025-11-29 12:27:58'),(4,'baby care','baby-care','','','Men’s Care products are designed to meet the daily grooming and skincare needs of modern men. From cleansing and moisturizing to shaving and styling, these products help maintain healthy skin, hair, and overall appearance. Easy to use and suitable for all skin types.\r\n\r\n2. Professional Product Description\r\n\r\nOur Men’s Care range is specially formulated to address the unique skin and grooming concerns of men. Each product delivers effective cleansing, hydration, and protection, helping you maintain a fresh, confident look throughout the day. Perfect for everyday use and crafted with high-quality ingredients for long-lasting results.','Administrator',10,'/assets/uploads/blogs/blog_692ae6bfb8270.PNG',1,'2025-11-29 09:05:45','2025-11-29 12:27:45'),(5,'luffy','whole cake isaland','luffy','luffy is a kindness and storng , he is best maon character for anime world','Luffy and the Straw Hats sail into Big Mom’s territory, the dangerous and sweet-smelling Totto Land, after Sanji suddenly disappears. Why?\r\nBecause the dude got forced into a political marriage with Big Mom’s daughter, Pudding, thanks to his messed–up Vinsmoke family.\r\n\r\nWhen the crew reaches Whole Cake Island, they split up — some go to rescue Sanji, others to find the mysterious Road Poneglyph that Big Mom has. The island looks cute, but every candy house and smiling tree hides Big Mom’s crazy rage and hunger fits.\r\n\r\nSanji, meanwhile, is trapped. He wants to come back to his crew, but Zeff’s life is threatened, so he suffers quietly and agrees to the wedding. Luffy doesn’t accept this. He gets beaten, bruised, starved, but literally waits in the rain at the spot Sanji kicked him… because he believes Sanji will return.\r\n\r\nThe wedding begins, Pudding reveals her evil plan to shoot Sanji, but she breaks down when Sanji compliments her “ugly eye,” and she cries. Chaos erupts as Luffy and friends crash the wedding with hundreds of Luffy clones popping out of the cake.\r\n\r\nInside the chaos, Brook steals the Poneglyph rubbings, becoming MVP. Big Mom, enraged over her destroyed “wedding cake” and Brook breaking the portrait of Mother Carmel, screams so loudly that people faint.\r\n\r\nThe Straw Hats escape through the mirror world with the help of Jinbe, Pedro, and the Fire Tank Pirates. Catekurī chases Luffy into an intense one–on–one fight where Luffy unlocks Gear Fourth: Snakeman, beating Katakuri in epic style.\r\n\r\nThe crew finally flees to the Sunny, chased by Big Mom herself who is literally eating everything in her path during a hunger rampage.\r\n\r\nDespite losing Pedro and nearly losing the Sunny, the Straw Hat crew barely escapes Big Mom’s territory alive — and Sanji officially returns “home.”','Administrator',4,'/assets/uploads/blogs/blog_692d328898d5b.jpg',1,'2025-12-01 06:15:36',NULL);
/*!40000 ALTER TABLE `blogs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cart` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `quantity` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart`
--

LOCK TABLES `cart` WRITE;
/*!40000 ALTER TABLE `cart` DISABLE KEYS */;
INSERT INTO `cart` VALUES (14,2,16,1,'2025-12-04 07:37:48','2025-12-04 07:37:48');
/*!40000 ALTER TABLE `cart` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `title` varchar(255) DEFAULT NULL,
  `parent_id` int unsigned DEFAULT NULL,
  `description` text,
  `image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (4,'Men Care','men-care','2025-11-24 01:46:55','Men Care',NULL,'test',NULL),(7,'Hair oil','hair-oil','2025-11-24 04:47:36','Hair oil',4,'Hair oil for men','1763959656-b75cdf673b.webp'),(8,'Face wash','face-wash','2025-11-24 04:48:53','Face wash',4,'face wash','1763959733-e9987172b3.jpg'),(9,'Hair Wash','hair-wash','2025-11-24 04:50:38','Hair Wash',4,'hair wash','1763959838-c8778da95d.jpg'),(10,'Baby care','baby-care','2025-11-25 11:59:03','Baby care',NULL,'','1764076707-93201c232b.jpg'),(11,'women care','women-care','2025-12-02 14:01:04','women care',NULL,'',NULL),(13,'Hair Care','hair-care','2025-12-02 14:01:42','Hair Care',NULL,'',NULL),(16,'Hair wash','women-care-hair-wash','2025-12-02 17:39:42','Hair wash',11,'',NULL);
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupons`
--

DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupons` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `discount_type` enum('percentage','flat') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL,
  `max_discount_limit` decimal(10,2) DEFAULT NULL,
  `min_purchase` decimal(10,2) DEFAULT NULL,
  `offer_type` enum('first_user','cart_value','festival','product_specific','category_specific','universal') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'universal',
  `usage_limit_per_user` enum('once','unlimited') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'once',
  `can_be_clubbed` tinyint(1) NOT NULL DEFAULT '0',
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_code` (`code`),
  KEY `idx_status` (`status`),
  KEY `idx_offer_type` (`offer_type`),
  KEY `idx_dates` (`start_date`,`end_date`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupons`
--

LOCK TABLES `coupons` WRITE;
/*!40000 ALTER TABLE `coupons` DISABLE KEYS */;
INSERT INTO `coupons` VALUES (1,'First order bonus','DEVOKK9J79HQG','','percentage',10.00,NULL,NULL,'first_user','once',0,'2025-12-01 09:43:00','2026-02-01 09:43:00','active','2025-12-01 04:13:39','2025-12-01 04:13:39'),(2,'Dewali offer','DEVEPQBMKDOL04','','percentage',20.00,NULL,5000.00,'universal','unlimited',1,'2025-12-01 13:41:00','2026-11-20 13:41:00','active','2025-12-01 08:11:41','2025-12-01 08:11:41');
/*!40000 ALTER TABLE `coupons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `filter_groups`
--

DROP TABLE IF EXISTS `filter_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `filter_groups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `param_key` varchar(50) NOT NULL,
  `column_name` varchar(50) NOT NULL,
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `filter_groups`
--

LOCK TABLES `filter_groups` WRITE;
/*!40000 ALTER TABLE `filter_groups` DISABLE KEYS */;
INSERT INTO `filter_groups` VALUES (3,'color','color','color',1,1),(6,'category','category','category_name',1,1);
/*!40000 ALTER TABLE `filter_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `filter_options`
--

DROP TABLE IF EXISTS `filter_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `filter_options` (
  `id` int NOT NULL AUTO_INCREMENT,
  `group_id` int NOT NULL,
  `value` varchar(100) NOT NULL,
  `label` varchar(100) NOT NULL,
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `fk_group` (`group_id`),
  CONSTRAINT `fk_group` FOREIGN KEY (`group_id`) REFERENCES `filter_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `filter_options`
--

LOCK TABLES `filter_options` WRITE;
/*!40000 ALTER TABLE `filter_options` DISABLE KEYS */;
INSERT INTO `filter_options` VALUES (5,3,'Black','Black',1,1),(8,6,'Men Care','men care',1,1),(9,6,'Baby care','Baby care',2,1),(10,6,'women care','women care',3,1);
/*!40000 ALTER TABLE `filter_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `homepage_products`
--

DROP TABLE IF EXISTS `homepage_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `homepage_products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `section` enum('best_seller','sale','top_rated','trendy') NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `homepage_products`
--

LOCK TABLES `homepage_products` WRITE;
/*!40000 ALTER TABLE `homepage_products` DISABLE KEYS */;
INSERT INTO `homepage_products` VALUES (12,21,'trendy',1),(13,18,'trendy',2),(14,17,'trendy',3),(15,16,'trendy',4),(19,21,'best_seller',1),(20,19,'best_seller',2),(21,18,'best_seller',3),(22,17,'best_seller',4),(23,19,'sale',1),(24,17,'sale',2),(25,16,'sale',3),(26,19,'top_rated',1),(27,18,'top_rated',2),(28,17,'top_rated',3);
/*!40000 ALTER TABLE `homepage_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `message` text,
  `url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,'Welcome','Your admin panel is ready','/admin/dashboard.php',1,'2025-11-18 16:53:23'),(2,'New Order','Order #102 received','/admin/orders.php?id=102',1,'2025-11-18 16:53:23'),(3,'New Order #order_Rn2WRuatlT4a46','Customer Chandru Prasath placed a new order of ₹479.00','order_view.php?id=13',1,'2025-12-03 06:28:30'),(4,'New Order #order_Rn2oHAyy1DObwY','Customer Chandru Prasath placed a new order of ₹3,600.00','order_view.php?id=14',1,'2025-12-03 06:45:25'),(5,'New Order #order_Rn2zHG4bRxQQDd','Customer Chandru Prasath placed a new order of ₹5,760.00','order_view.php?id=15',1,'2025-12-03 06:55:49');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `sku` varchar(100) DEFAULT NULL,
  `hsn` varchar(20) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `short_description` text,
  `description` text,
  `ingredients` text,
  `how_to_use` text,
  `faqs` text,
  `variant_label` varchar(100) DEFAULT 'Size',
  `main_variant_name` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `compare_price` decimal(10,2) DEFAULT NULL,
  `discount_percent` decimal(5,2) DEFAULT NULL,
  `currency` varchar(10) DEFAULT 'INR',
  `category_id` int unsigned DEFAULT NULL,
  `parent_category_id` int DEFAULT NULL,
  `category_name` varchar(255) DEFAULT NULL,
  `images` text,
  `variants` text,
  `related_products` text,
  `stock` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `is_featured` tinyint(1) DEFAULT '0',
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `product_media` json DEFAULT NULL COMMENT 'Stores array of media files (images/videos) with type and path',
  `gst_rate` decimal(5,2) DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (16,'',NULL,'Ayurvedic Eye kajal Aloe Vera Bhringraj Charming Anjana 2gm','ayurvedic-eye-kajal-aloe-vera-bhringraj-charming-anjana-2gm','Ayurvedic Eye kajal Aloe Vera Bhringraj Charming Anjana 2gm','Say goodbye to the discomfort of dry eyes and give your eyes the care they deserve with our Ayurvedic Eye Kajal enriched with Desi Cow Ghee. Whether it’s due to extended screen time or exposure to environmental pollutants, our kajal helps you maintain comfortable and well-hydrated eyes.',NULL,NULL,NULL,'Size',NULL,399.00,NULL,NULL,'INR',4,4,'Men Care','[\"8e2202201f76.jpg\"]',NULL,NULL,10,1,0,'Ayurvedic Eye kajal Aloe Vera Bhringraj Charming Anjana 2gm','Ayurvedic Eye kajal Aloe Vera Bhringraj Charming Anjana 2gm','2025-11-24 06:50:13','2025-11-26 07:07:53',NULL,0.00),(17,'',NULL,'Babies Ayurvedic Eye Kajal Pure and Natural Anjana','babies-ayurvedic-eye-kajal-pure-and-natural-anjana','','<p>Our Baby Eye Kajal is an Ayurvedic wonder enriched with the goodness of nature. Crafted with care, it contains only pure and natural ingredients.</p>',NULL,NULL,NULL,'Size',NULL,399.00,NULL,NULL,'INR',16,11,'women care','[\"fb15b8e998ea.jpg\"]',NULL,NULL,10,1,0,'Our Baby Eye Kajal is an Ayurvedic wonder enriched with the goodness of nature. Crafted with care, it contains only pure and natural ingredients.','Our Baby Eye Kajal is an Ayurvedic wonder enriched with the goodness of nature. Crafted with care, it contains only pure and natural ingredients.','2025-11-24 06:52:57','2025-12-02 17:40:36','[]',0.00),(18,'',NULL,'Eye Kajal Alovera Bringraj Prevent dry eyes & protect from Pollution & infections','eye-kajal-alovera-bringraj-prevent-dry-eyes-protect-from-pollution-infections','Prevent dry eyes','Say goodbye to the discomfort of dry eyes and give your eyes the care they deserve with our Ayurvedic Eye Kajal enriched with Desi Cow Ghee. Whether it’s due to extended screen time or exposure to environmental pollutants, our kajal helps you maintain comfortable and well-hydrated eyes',NULL,NULL,NULL,'Size',NULL,399.00,NULL,NULL,'INR',8,4,'Men Care','[\"1763967298_042b9bdfba78.jpg\"]',NULL,NULL,10,1,0,'Eye Kajal Alovera Bringraj Prevent dry eyes & protect from Pollution & infections','Eye Kajal Alovera Bringraj Prevent dry eyes & protect from Pollution & infections','2025-11-24 06:54:41','2025-11-26 07:07:53',NULL,0.00),(19,'',NULL,'Face cleanser Honey Vetiver Anantmool Luster Even Tone Non-Forming','face-cleanser-honey-vetiver-anantmool-luster-even-tone-non-forming','','The facial cleanser is made with distilled water of vetiver , ananthamul ,Ayurvedic herbs, pure unrefined mountain honey.\r\nIt’s a traditional Ayurvedic process and a time-tested formula that leaves your face soft, hydrated and suitable for all skin type.',NULL,NULL,NULL,'Size',NULL,495.00,NULL,NULL,'INR',9,4,'Men Care','[\"69904cd66623.png\"]',NULL,NULL,10,1,0,'Face cleanser Honey Vetiver Anantmool Luster Even Tone Non-Forming','Face cleanser Honey Vetiver Anantmool Luster Even Tone Non-Forming','2025-11-24 06:57:18','2025-11-26 07:07:53',NULL,0.00),(21,'',NULL,'test','facial-toner-distilled-sandalwood-rose-water-skin-food-preservative-alcohol-free','<p>fyjklioefghjkvbnm,rrrrrrrr</p>','<p>Facial Toner Distilled Sandalwood Rose Water Skin Food is absolutely Preservative and  Alcohol-free 100% stem distilled toner, Refreshing, Hydrating, and Oil controls. Deep Cleanse.  Act as skin/body Mist, or Face Tonic, an astringent causes the contraction, and firmness of skin cells and the skin pores</p>',NULL,NULL,NULL,'ml',NULL,1299.00,NULL,NULL,'INR',10,10,'Baby care','[\"6755ca5b18fc.jpg\",\"1764295013_79464f9aa5a3.jpg\",\"1764296169_cb923bbec6b6.jpg\",\"1764296169_8fbf2804a36a.jpg\",\"1764296169_86f355124d03.jpg\",\"1764296169_3e88898f4d1d.jpg\",\"1764296169_c693703551f6.png\"]',NULL,NULL,10,1,0,'Facial Toner Distilled Sandalwood Rose Water Skin Food Preservative Alcohol Free','Facial Toner Distilled Sandalwood Rose Water Skin Food Preservative Alcohol Free','2025-11-24 07:01:26','2025-12-01 17:02:41','[]',0.00),(27,'',NULL,'test','test','test','test','test','test',NULL,'ml',NULL,1200.00,NULL,NULL,'INR',7,4,'Men Care','[\"1764311884_c5e9e42f0a1c.jpg\",\"1764311884_f2b7a9083d82.png\"]',NULL,NULL,10,1,0,'','','2025-11-28 06:36:52','2025-11-28 06:38:04',NULL,0.00),(29,'','3200','Nalpamaradi Kera Thailam Bridal Mom Baby Skin Beauty','nalpamaradi-kera-thailam-bridal-mom-baby-skin-beauty','<p><strong class=\"ql-font-ibm-plex-mono\">Nalpamaradi Kera Thailam is opting for Skin brightening treatment, especially for Bridal and babies. This oil helps lighten the appearance of dark patches, cure uneven skin color, and Reduce Stretch marks.</strong></p>','<p><strong class=\"ql-font-open-sans\"><em>Nalpamaradi Kera Thailam is opting for Skin brightening treatment, especially for Bridal and babies. This oil helps lighten the appearance of dark patches, cure uneven skin color, and Reduce Stretch marks.</em></strong></p>','<p>Nalpamaram\r\nTurmeric\r\nTriphala\r\nSandalwood</p>','<p>Known to reduce excess heat in the body (Pitta in Ayurveda) and treat various skin issues, including acne, dry skin, and skin irritation.</p>',NULL,'Product','Nalpamaradi',3600.00,4000.00,10.00,'INR',7,4,'Men Care','[\"c28997524100.jpg\",\"459a32ced2ab.jpg\",\"77285b548731.jpg\"]',NULL,NULL,10,1,0,'Known to reduce excess heat in the body (Pitta in Ayurveda) and treat various skin issues, including acne, dry skin, and skin irritation.','Known to reduce excess heat in the body (Pitta in Ayurveda) and treat various skin issues, including acne, dry skin, and skin irritation.','2025-11-28 09:56:57','2025-12-03 07:22:41','[{\"path\": \"1764354601_6929ea2922709.mp4\", \"type\": \"video\"}, {\"path\": \"1764355085_6929ec0d103fd.mp4\", \"type\": \"video\"}]',5.00);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;
--
-- Table structure for table `product_faqs`
--

DROP TABLE IF EXISTS `product_faqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_faqs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int unsigned NOT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_faqs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_faqs`
--

LOCK TABLES `product_faqs` WRITE;
/*!40000 ALTER TABLE `product_faqs` DISABLE KEYS */;
INSERT INTO `product_faqs` VALUES (1,21,'What is the ingrediant use in this product','Almond Shell Powder: A natural skin exfoliant, it stimulates blood circulation and removes dead skin cells.','2025-11-28 01:21:22'),(2,29,'Why Choose DevElixir','100% Natural','2025-11-28 09:56:57');
/*!40000 ALTER TABLE `product_faqs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_relations`
--

DROP TABLE IF EXISTS `product_relations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_relations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int unsigned NOT NULL,
  `related_product_id` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_relation` (`product_id`,`related_product_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_related_product_id` (`related_product_id`),
  CONSTRAINT `product_relations_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_relations_ibfk_2` FOREIGN KEY (`related_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=146 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_relations`
--

LOCK TABLES `product_relations` WRITE;
/*!40000 ALTER TABLE `product_relations` DISABLE KEYS */;
INSERT INTO `product_relations` VALUES (141,29,16,'2025-12-03 07:22:41'),(142,29,17,'2025-12-03 07:22:41'),(143,29,19,'2025-12-03 07:22:41'),(144,29,21,'2025-12-03 07:22:41'),(145,29,27,'2025-12-03 07:22:41');
/*!40000 ALTER TABLE `product_relations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_reviews`
--

DROP TABLE IF EXISTS `product_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_reviews` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int unsigned NOT NULL,
  `reviewer_name` varchar(120) DEFAULT NULL,
  `reviewer_email` varchar(180) DEFAULT NULL,
  `rating` decimal(3,2) NOT NULL DEFAULT '0.00',
  `comment` text,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `moderated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_reviews`
--

LOCK TABLES `product_reviews` WRITE;
/*!40000 ALTER TABLE `product_reviews` DISABLE KEYS */;
INSERT INTO `product_reviews` VALUES (17,16,'Chan Tester','customer@example.com',4.50,'Very good product, helped a lot. Will buy again.','approved','2025-01-20 10:00:00','2025-01-20 11:00:00'),(18,17,'Chan Tester','customer@example.com',3.80,'Nice, but packaging could be better.','approved','2025-01-22 14:30:00','2025-01-22 15:00:00'),(19,18,'Chan Tester','customer@example.com',5.00,'Excellent quality. Totally worth the price.','approved','2025-01-25 09:15:00','2025-01-25 09:45:00'),(23,29,'Test User','test@example.com',5.00,'This is a test review.','approved','2025-11-29 17:42:39',NULL),(27,29,'Chandru Prasath','chandrusri247@gmail.com',5.00,'Nalpamaradi Kera Thailam is often described as a \\"miracle oil\\" and a \\"game-changer\\" for the skin, with many users reporting remarkable results and considering it a \\"must-buy\\" for achieving a natural, radiant glow.','approved','2025-11-29 18:02:05',NULL),(28,18,'Chandru Prasath','chandrusri247@gmail.com',5.00,'Users who prefer natural, chemical-free products often highly rate these kajals for their soothing properties and for being safe for sensitive eyes. However, some reviewers note that, because they lack synthetic chemicals, they may not be as long-lasting or smudge-proof as conventional brands','approved','2025-11-29 18:03:30',NULL),(29,16,'Chandru Prasath','chandrusri247@gmail.com',5.00,'Soothing and Cooling Effect: Many users highlight the immediate cooling and soothing sensation upon application, which helps relieve eye strain and fatigue, particularly for those working on computers or reading for long hours.','approved','2025-11-29 18:04:19',NULL);
/*!40000 ALTER TABLE `product_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_variants`
--

DROP TABLE IF EXISTS `product_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_variants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int unsigned NOT NULL,
  `variant_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `compare_price` decimal(10,2) DEFAULT NULL,
  `discount_percent` decimal(5,2) DEFAULT NULL,
  `stock` int DEFAULT '0',
  `sku` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `custom_title` varchar(255) DEFAULT NULL,
  `custom_description` text,
  `short_description` text,
  `ingredients` text,
  `how_to_use` text,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text,
  `custom_faqs` json DEFAULT NULL,
  `images` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET @saved_cs_client     = @@character_set_client */;

--
-- Dumping data for table `product_variants`
--

LOCK TABLES `product_variants` WRITE;
/*!40000 ALTER TABLE `product_variants` DISABLE KEYS */;
INSERT INTO `product_variants` VALUES (1,21,'100ml',500.00,NULL,NULL,10,'',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]'),(2,21,'200ml',1000.00,NULL,NULL,10,'',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]'),(3,21,'300ml',1400.00,NULL,NULL,10,'',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]'),(4,27,'100ml',1200.00,NULL,NULL,10,'',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(5,29,'KUNKUMADI',900.00,NULL,NULL,10,'','var_1764326760_d6a56278.png',1,'KUNKUMADI TAILA To Improve Your Complexion And Get A Rejuvenated Glow','KUNKUMADI TAILA Improves complexion And Get A Rejuvenated Glow, This oil moisturizes your skin and makes your skin spotless and luster naturally. This is 100% natural with an Ayurvedic formulation.','KUNKUMADI TAILA Improves complexion And Get A Rejuvenated Glow, This oil moisturizes your skin and makes your skin spotless and luster naturally. This is 100% natural with an Ayurvedic formulation.',NULL,NULL,NULL,NULL,NULL,'["var_1764326760_d6a56278.png"]'),(6,29,'Anti-Acne',630.00,700.00,10.00,10,'','var_1764328562_7b1a60b4.png',1,'Anti-Acne Face Cleanser Neem Sandalwood 100 ml','Anti-Acne  Facial Cleanser Enriched with Neem Sandalwood, Aloe vera, and Ayurvedic herbs. which helps to reduce and soothe acne, hydrate, and reduce open pores.','Anti-Acne  Facial Cleanser Enriched with Neem Sandalwood, Aloe vera, and Ayurvedic herbs. which helps to reduce and soothe acne, hydrate, and reduce open pores.',NULL,NULL,NULL,NULL,NULL,'["var_1764328562_7b1a60b4.png", "var_1764329027_9bf72894.png", "var_1764329027_492c1a23.jpg"]');
/*!40000 ALTER TABLE `product_variants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned NOT NULL,
  `product_id` int unsigned DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `variant` varchar(255) DEFAULT NULL,
  `qty` int DEFAULT '1',
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,6,17,'Babies Ayurvedic Eye Kajal Pure and Natural Anjana',NULL,5,399.00,'2025-12-02 18:12:20'),(2,7,29,'Nalpamaradi Kera Thailam Bridal Mom Baby Skin Beauty',NULL,1,3600.00,'2025-12-03 01:43:58'),(3,8,29,'Nalpamaradi Kera Thailam Bridal Mom Baby Skin Beauty',NULL,1,3600.00,'2025-12-03 01:48:23'),(4,9,17,'Babies Ayurvedic Eye Kajal Pure and Natural Anjana',NULL,1,399.00,'2025-12-03 01:53:13'),(5,12,17,'Babies Ayurvedic Eye Kajal Pure and Natural Anjana',NULL,1,399.00,'2025-12-03 04:04:48'),(6,13,17,'Babies Ayurvedic Eye Kajal Pure and Natural Anjana',NULL,1,399.00,'2025-12-03 06:28:30'),(7,14,29,'Nalpamaradi Kera Thailam Bridal Mom Baby Skin Beauty',NULL,1,3600.00,'2025-12-03 06:45:25'),(8,15,29,'Nalpamaradi Kera Thailam Bridal Mom Baby Skin Beauty',NULL,2,3600.00,'2025-12-03 06:55:49');
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;



DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int unsigned NOT NULL,
  `user_id` int unsigned DEFAULT NULL,
  `rating` int NOT NULL,
  `review` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipments`
--

DROP TABLE IF EXISTS `shipments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shipments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shipment_number` varchar(64) NOT NULL,
  `order_id` int unsigned NOT NULL,
  `carrier` varchar(100) DEFAULT NULL,
  `tracking_number` varchar(128) DEFAULT NULL,
  `shipping_method` varchar(64) DEFAULT NULL,
  `shipping_cost` decimal(10,2) DEFAULT '0.00',
  `weight` decimal(10,2) DEFAULT '0.00',
  `status` enum('pending','label_created','in_transit','delivered','returned','cancelled') NOT NULL DEFAULT 'pending',
  `label_file` varchar(255) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `shipped_at` datetime DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shipment_number` (`shipment_number`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `shipments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipments`
--

LOCK TABLES `shipments` WRITE;
/*!40000 ALTER TABLE `shipments` DISABLE KEYS */;
INSERT INTO `shipments` VALUES (1,'SHIP-TEST-001',1,'BlueDart',NULL,'Air Express',120.00,0.00,'pending',NULL,NULL,'2025-11-19 10:02:26',NULL,NULL);
/*!40000 ALTER TABLE `shipments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipment_items`
--

DROP TABLE IF EXISTS `shipment_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shipment_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shipment_id` bigint unsigned NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `sku` varchar(128) DEFAULT NULL,
  `qty` int NOT NULL DEFAULT '1',
  `weight` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `shipment_id` (`shipment_id`),
  CONSTRAINT `shipment_items_ibfk_1` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipment_items`
--

LOCK TABLES `shipment_items` WRITE;
/*!40000 ALTER TABLE `shipment_items` DISABLE KEYS */;
INSERT INTO `shipment_items` VALUES (1,1,'Sample Product A','SKU-001',2,0.50),(2,1,'Sample Product B','SKU-002',1,1.20);
/*!40000 ALTER TABLE `shipment_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_settings`
--

DROP TABLE IF EXISTS `site_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `site_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_settings`
--

LOCK TABLES `site_settings` WRITE;
/*!40000 ALTER TABLE `site_settings` DISABLE KEYS */;
INSERT INTO `site_settings` VALUES (1,'why_devilixirs','✔ 100% Herbal formula\n✔ No chemicals or parabens\n✔ Crafted in small batches\n✔ Made in Chennai with care','2025-11-27 15:25:00'),(2,'company_name','DEVELIXIR','2025-12-03 07:22:21'),(3,'company_address','DevElixir Natural Cosmetics ™\r\nNo:6, 3rd Cross Street,\r\nKamatchiamman Garden, Sethukkarai,\r\nGudiyatham-632602, Vellore, Tamilnadu\r\nINDIA','2025-12-03 07:22:21'),(4,'company_email','support@develixir.com','2025-12-03 07:22:21'),(5,'company_phone','','2025-12-03 07:22:21'),(6,'tax_rate','18','2025-12-03 07:22:21');
/*!40000 ALTER TABLE `site_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tags` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tags`
--

LOCK TABLES `tags` WRITE;
/*!40000 ALTER TABLE `tags` DISABLE KEYS */;
INSERT INTO `tags` VALUES (1,'Hair Fall','hair-fall',1,'2025-11-24 10:40:05'),(2,'Anti Dandruf','anti-dandruf',1,'2025-11-24 10:40:35');
/*!40000 ALTER TABLE `tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_tags`
--

DROP TABLE IF EXISTS `product_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_tags` (
  `product_id` int unsigned NOT NULL,
  `tag_id` int unsigned NOT NULL,
  PRIMARY KEY (`product_id`,`tag_id`),
  KEY `fk_product_tags_tag` (`tag_id`),
  CONSTRAINT `fk_product_tags_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_product_tags_tag` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_tags`
--

LOCK TABLES `product_tags` WRITE;
/*!40000 ALTER TABLE `product_tags` DISABLE KEYS */;
INSERT INTO `product_tags` VALUES (27,1),(29,1),(27,2),(29,2);
/*!40000 ALTER TABLE `product_tags` ENABLE KEYS */;
UNLOCK TABLES;



--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `google_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` enum('male','female') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `otp` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT '0',
  `role` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `google_id` (`google_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Administrator','admin@admin.com',NULL,NULL,NULL,'$2y$10$oFAJprvwTTu.nqCb0OjvweqcSTTJmfAl.0TrdCb69zh3tsPIkNT4q',NULL,1,'admin',1,'2025-11-18 02:18:22'),(2,'Chandru Prasath','chandrusri247@gmail.com','113656964454919534640','+918946071785',NULL,'$2y$12$1g.Rf956kFI/hfHeLW2bD.w/tnv9WKAmL6hfnnL2aDBbESLHzEoxm',NULL,1,'admin',1,'2025-11-25 11:26:26'),(3,'harish panther k','pantherharish28@gmail.com','111712213758281880057','+91 8946071785','male','$2y$12$c/7uK.uAaMnoMgCUIH7ZL.3lW119TkZcLZsxcxIu4G9zAwDj/AarS',NULL,1,'admin',1,'2025-11-27 07:59:26');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_addresses`
--

DROP TABLE IF EXISTS `user_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_addresses` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address_line1` text NOT NULL,
  `address_line2` text,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `pincode` varchar(10) NOT NULL,
  `is_default` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_addresses`
--

LOCK TABLES `user_addresses` WRITE;
/*!40000 ALTER TABLE `user_addresses` DISABLE KEYS */;
INSERT INTO `user_addresses` VALUES (3,2,'Chandru Sri','08946071785','18/4balgangathara Thilagar St','18','Vellore','Tamil Nadu','632602',1,'2025-11-27 05:23:51'),(4,3,'Chandru Prasath','08946071785','East Wing, Arcot Road, Shyamala Towers, Saligramam, Chennai, Tamil Nadu 600093','','Chennai','Tamil Nadu','600093',0,'2025-11-27 13:35:10'),(5,3,'harish','9363076177','18/4 balagangathara thilagar st pichanoor pet gudiyattam','','Vellore','Tamil Nadu','632602',0,'2025-11-27 13:36:43'),(6,3,'harish','09363076177','18/4 balagangathara thilagar st pichanoor pet gudiyattam','','Vellore','Tamil Nadu','632602',0,'2025-11-27 13:55:52'),(7,3,'Chandru Prasath','08946071785','East Wing, Arcot Road, Shyamala Towers, Saligramam, Chennai, Tamil Nadu 600093','18','Chennai','Tamil Nadu','600093',1,'2025-11-27 16:20:20'),(8,2,'panther harish','9363076177','18/4 venakata chalapathi nagar','18','Chennai','Tamil Nadu','632607',0,'2025-11-29 10:31:08'),(9,2,'Chandru Prasath','08946071785','18/4 balagangathara thilagar st pichanoor pet gudiyattam','18','Chennai','Tamil Nadu','600093',0,'2025-12-01 06:07:12');
/*!40000 ALTER TABLE `user_addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_notifications`
--

DROP TABLE IF EXISTS `user_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_notifications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_notifications`
--

LOCK TABLES `user_notifications` WRITE;
/*!40000 ALTER TABLE `user_notifications` DISABLE KEYS */;
INSERT INTO `user_notifications` VALUES (1,1,'Welcome!','Welcome to Develixirs. Happy shopping!',0,'2025-11-27 16:44:46');
/*!40000 ALTER TABLE `user_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_upi`
--

DROP TABLE IF EXISTS `user_upi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_upi` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `upi_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'UPI',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_upi_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_upi`
--

LOCK TABLES `user_upi` WRITE;
/*!40000 ALTER TABLE `user_upi` DISABLE KEYS */;
INSERT INTO `user_upi` VALUES (1,1,'testuser@okhdfcbank','Google Pay','2025-11-27 16:24:10'),(2,1,'testuser@oksbi','PhonePe','2025-11-27 16:24:10');
/*!40000 ALTER TABLE `user_upi` ENABLE KEYS */;
UNLOCK TABLES;



DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `coupon_code` varchar(50) DEFAULT NULL,
  `coupon_discount` decimal(10,2) DEFAULT '0.00',
  `status` varchar(50) DEFAULT 'processing',
  `payment_status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `shipping_charge` decimal(10,2) DEFAULT '0.00',
  `tax_amount` decimal(10,2) DEFAULT '0.00',
  `order_number` varchar(100) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_address` text,
  `order_status` varchar(50) DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET @saved_cs_client     = @@character_set_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,NULL,0.00,NULL,0.00,'processing','paid','2025-11-19 03:46:37',1499.00,0.00,228.66,'ORD-2025-001','Dummy Customer 1',NULL,'delivered'),(2,NULL,0.00,NULL,0.00,'processing','paid','2025-11-19 03:46:37',2499.50,0.00,381.28,'ORD-2025-002','Dummy Customer 2',NULL,'delivered'),(3,NULL,0.00,NULL,0.00,'processing','paid','2025-11-19 03:46:37',999.00,0.00,152.39,'ORD-2025-003','Dummy Customer 3',NULL,'processing'),(4,NULL,0.00,NULL,0.00,'processing','paid','2025-11-19 03:46:37',3299.00,0.00,503.24,'ORD-2025-004','Dummy Customer 4',NULL,'delivered'),(5,NULL,0.00,NULL,0.00,'processing','paid','2025-11-19 03:46:37',499.00,0.00,76.12,'ORD-2025-005','Dummy Customer 5',NULL,'processing'),(6,2,0.00,NULL,0.00,'processing','pending','2025-12-02 18:12:20',1995.00,0.00,304.32,'ORD-20251202-4265C5','Chandru Prasath','Chandru Sri\\n18/4balgangathara Thilagar St\\n18\\nVellore, Tamil Nadu - 632602\\nPhone: 08946071785','packed'),(7,2,3240.00,'DEVOKK9J79HQG',360.00,'processing','paid','2025-12-03 01:43:58',3240.00,0.00,494.24,'order_Rmxfo5eBy77NKK','Chandru Prasath','Chandru Sri\\n18/4balgangathara Thilagar St\\n18\\nVellore, Tamil Nadu - 632602\\nPhone: 08946071785','processing'),(8,2,3240.00,'DEVOKK9J79HQG',360.00,'processing','paid','2025-12-03 01:48:23',3240.00,0.00,494.24,'order_RmxkdpAGDD2zpa','Chandru Prasath','Chandru Sri\\n18/4balgangathara Thilagar St\\n18\\nVellore, Tamil Nadu - 632602\\nPhone: 08946071785','processing'),(9,2,439.10,'DEVOKK9J79HQG',39.90,'processing','paid','2025-12-03 01:53:13',439.10,0.00,66.98,'order_RmxpjZ5uJ3hhfx','Chandru Prasath','Chandru Sri\\n18/4balgangathara Thilagar St\\n18\\nVellore, Tamil Nadu - 632602\\nPhone: 08946071785','processing'),(10,1,0.00,NULL,0.00,'completed','pending','2025-12-03 03:48:06',500.00,0.00,76.27,NULL,NULL,NULL,'pending'),(11,1,0.00,NULL,0.00,'completed','pending','2025-12-03 03:48:24',500.00,0.00,76.27,NULL,NULL,NULL,'pending'),(12,2,479.00,'',0.00,'processing','paid','2025-12-03 04:04:48',479.00,0.00,73.07,'order_Rn04h9uQbsumj7','Chandru Prasath','Chandru Sri\\n18/4balgangathara Thilagar St\\n18\\nVellore, Tamil Nadu - 632602\\nPhone: 08946071785','processing'),(13,2,479.00,'',0.00,'processing','paid','2025-12-03 06:28:30',479.00,80.00,73.07,'order_Rn2WRuatlT4a46','Chandru Prasath','Chandru Sri\\n18/4balgangathara Thilagar St\\n18\\nVellore, Tamil Nadu - 632602\\nPhone: 08946071785','cancelled'),(14,2,3600.00,'',0.00,'processing','paid','2025-12-03 06:45:25',3600.00,0.00,549.15,'order_Rn2oHAyy1DObwY','Chandru Prasath','Chandru Sri\\n18/4balgangathara Thilagar St\\n18\\nVellore, Tamil Nadu - 632602\\nPhone: 08946071785','processing'),(15,2,5760.00,'DEVEPQBMKDOL04',1440.00,'processing','paid','2025-12-03 06:55:49',5760.00,0.00,1098.31,'order_Rn2zHG4bRxQQDd','Chandru Prasath','Chandru Sri\\n18/4balgangathara Thilagar St\\n18\\nVellore, Tamil Nadu - 632602\\nPhone: 08946071785','shipped');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_id` int unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` enum('issued','cleared') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'issued',
  `created_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cleared_at` datetime DEFAULT NULL,
  `discount` decimal(12,2) DEFAULT '0.00',
  `other_discount` decimal(12,2) DEFAULT '0.00',
  `shipping_charge` decimal(12,2) DEFAULT '0.00',
  `tax_rate` decimal(6,2) DEFAULT '0.00',
  `tax_amount` decimal(12,2) DEFAULT NULL,
  `other_fees` decimal(12,2) DEFAULT '0.00',
  `pdf_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `fk_reviews_order` (`order_id`),
  CONSTRAINT `fk_reviews_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET @saved_cs_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` VALUES (13,'INV-2025-001',1,1499.00,'cleared',1,'2025-11-19 03:46:49','2025-11-19 05:37:24',0.00,0.00,0.00,0.00,NULL,0.00,NULL),(14,'INV-2025-002',2,2499.50,'cleared',1,'2025-11-19 03:46:49',NULL,0.00,0.00,0.00,0.00,NULL,0.00,NULL),(15,'INV-2025-003',3,999.00,'cleared',1,'2025-11-19 03:46:49','2025-11-19 07:13:48',0.00,0.00,0.00,0.00,NULL,0.00,NULL),(16,'INV-TEST-ALL-01',1,2000.00,'issued',1,'2025-11-19 07:08:57',NULL,100.00,50.00,49.00,18.00,333.00,25.00,'OD335927864916938100.pdf'),(17,'INV-order_Rn2WRuatlT4a46-251203',13,479.00,'issued',NULL,'2025-12-03 06:43:03',NULL,0.00,0.00,0.00,0.00,NULL,0.00,NULL),(18,'INV-order_Rn04h9uQbsumj7-251203',12,479.00,'issued',NULL,'2025-12-03 06:43:36',NULL,0.00,0.00,0.00,0.00,NULL,0.00,NULL),(19,'INV-order_Rn2oHAyy1DObwY-251203',14,3600.00,'issued',NULL,'2025-12-03 06:45:45',NULL,0.00,0.00,0.00,0.00,NULL,0.00,NULL),(20,'INV-order_Rn2zHG4bRxQQDd-251203',15,5760.00,'issued',NULL,'2025-12-03 06:57:42',NULL,0.00,0.00,0.00,0.00,1098.31,0.00,NULL);
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoice_items`
--

DROP TABLE IF EXISTS `invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned NOT NULL,
  `description` text NOT NULL,
  `qty` int NOT NULL DEFAULT '1',
  `unit_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `invoice_id` (`invoice_id`),
  CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoice_items`
--

LOCK TABLES `invoice_items` WRITE;
/*!40000 ALTER TABLE `invoice_items` DISABLE KEYS */;
INSERT INTO `invoice_items` VALUES (1,16,'Product A – Qty 2',2,500.00,1000.00,'2025-11-19 07:08:57'),(2,16,'Product B – Qty 1',1,1000.00,1000.00,'2025-11-19 07:08:57'),(3,17,'Babies Ayurvedic Eye Kajal Pure and Natural Anjana',1,399.00,399.00,'2025-12-03 06:43:03'),(4,18,'Babies Ayurvedic Eye Kajal Pure and Natural Anjana',1,399.00,399.00,'2025-12-03 06:43:36'),(5,19,'Nalpamaradi Kera Thailam Bridal Mom Baby Skin Beauty',1,3600.00,3600.00,'2025-12-03 06:45:45'),(6,20,'Nalpamaradi Kera Thailam Bridal Mom Baby Skin Beauty',2,3600.00,7200.00,'2025-12-03 06:57:42');
/*!40000 ALTER TABLE `invoice_items` ENABLE KEYS */;
UNLOCK TABLES;



DROP TABLE IF EXISTS `variant_faqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `variant_faqs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `variant_id` int NOT NULL,
  `question` varchar(500) NOT NULL,
  `answer` text NOT NULL,
  `display_order` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_variant` (`variant_id`),
  CONSTRAINT `variant_faqs_ibfk_1` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `variant_faqs`
--

LOCK TABLES `variant_faqs` WRITE;
/*!40000 ALTER TABLE `variant_faqs` DISABLE KEYS */;
INSERT INTO `variant_faqs` VALUES (85,6,'Reduces Acne/Pimples','The combination of Neem, Sandalwood, and Ayurvedic herbs likely contributes to the anti-inflammatory and antimicrobial properties, which can help reduce the occurrence of acne and pimples.',0),(86,5,'Lightening Dark Circles','The oil’s herbal ingredients are particularly effective in lightening the skin under the eyes, reducing the appearance of dark circles. This can contribute to a more youthful and refreshed appearance',0);
/*!40000 ALTER TABLE `variant_faqs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wishlist` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_wishlist` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlist`
--

LOCK TABLES `wishlist` WRITE;
/*!40000 ALTER TABLE `wishlist` DISABLE KEYS */;
INSERT INTO `wishlist` VALUES (1,1,16,'2025-11-27 16:44:46'),(3,2,29,'2025-11-29 09:13:37'),(5,2,27,'2025-12-01 06:06:00'),(6,2,21,'2025-12-01 06:06:01'),(7,2,19,'2025-12-01 06:06:04'),(8,2,18,'2025-12-01 06:06:06'),(9,2,17,'2025-12-01 06:06:07');
/*!40000 ALTER TABLE `wishlist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlists`
--

DROP TABLE IF EXISTS `wishlists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wishlists` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_product` (`user_id`,`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlists`
--

LOCK TABLES `wishlists` WRITE;
/*!40000 ALTER TABLE `wishlists` DISABLE KEYS */;
INSERT INTO `wishlists` VALUES (2,2,29,'2025-11-28 12:47:30');
/*!40000 ALTER TABLE `wishlists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupon_categories`
--

DROP TABLE IF EXISTS `coupon_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupon_categories` (
  `coupon_id` int unsigned NOT NULL,
  `category_id` int unsigned NOT NULL,
  PRIMARY KEY (`coupon_id`,`category_id`),
  KEY `fk_category` (`category_id`),
  CONSTRAINT `fk_categories_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_coupon_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupon_categories`
--

LOCK TABLES `coupon_categories` WRITE;
/*!40000 ALTER TABLE `coupon_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `coupon_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupon_products`
--

DROP TABLE IF EXISTS `coupon_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupon_products` (
  `coupon_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  PRIMARY KEY (`coupon_id`,`product_id`),
  KEY `fk_product` (`product_id`),
  CONSTRAINT `fk_products_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_coupon_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupon_products`
--

LOCK TABLES `coupon_products` WRITE;
/*!40000 ALTER TABLE `coupon_products` DISABLE KEYS */;
/*!40000 ALTER TABLE `coupon_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupon_usage`
--

DROP TABLE IF EXISTS `coupon_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupon_usage` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `coupon_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `order_id` int unsigned DEFAULT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `used_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_coupon_user` (`coupon_id`,`user_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_order` (`order_id`),
  CONSTRAINT `fk_usage_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_usage_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_usage_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupon_usage`
--

LOCK TABLES `coupon_usage` WRITE;
/*!40000 ALTER TABLE `coupon_usage` DISABLE KEYS */;
INSERT INTO `coupon_usage` VALUES (1,1,2,9,39.90,'2025-12-03 01:53:13'),(2,2,2,15,1440.00,'2025-12-03 06:55:49');
/*!40000 ALTER TABLE `coupon_usage` ENABLE KEYS */;
UNLOCK TABLES;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-05  6:36:48
