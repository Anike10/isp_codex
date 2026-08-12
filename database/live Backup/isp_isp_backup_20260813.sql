/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.18-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: 127.0.0.1    Database: isp_isp
-- ------------------------------------------------------
-- Server version	10.11.18-MariaDB-ubu2404

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `app_ip_pools`
--

DROP TABLE IF EXISTS `app_ip_pools`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_ip_pools` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `mikrotik_router_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `ranges` text NOT NULL,
  `next_pool` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `app_ip_pools_mikrotik_router_id_name_unique` (`mikrotik_router_id`,`name`),
  CONSTRAINT `app_ip_pools_mikrotik_router_id_foreign` FOREIGN KEY (`mikrotik_router_id`) REFERENCES `mikrotik_routers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_ip_pools`
--

LOCK TABLES `app_ip_pools` WRITE;
/*!40000 ALTER TABLE `app_ip_pools` DISABLE KEYS */;
INSERT INTO `app_ip_pools` VALUES
(1,1,'a141ranvid','10.99.1.0/24',NULL,NULL,'active','2026-08-11 17:54:15','2026-08-11 17:54:15'),
(2,1,'govt_college','10.99.5.0/24',NULL,NULL,'active','2026-08-11 17:54:18','2026-08-11 17:54:18'),
(3,1,'kpi_all','10.99.8.0/24',NULL,NULL,'active','2026-08-11 17:54:31','2026-08-11 17:54:31'),
(4,1,'kpi_comdpt','10.99.9.0/24',NULL,NULL,'active','2026-08-11 17:54:35','2026-08-11 17:54:35'),
(5,1,'Zillas','10.99.4.0/24',NULL,NULL,'active','2026-08-11 17:54:47','2026-08-11 17:54:47'),
(6,1,'Travelshouse','10.99.3.0/24',NULL,NULL,'active','2026-08-11 17:54:50','2026-08-11 17:54:50'),
(7,1,'StarLink','10.99.22.0/24',NULL,NULL,'active','2026-08-11 17:54:52','2026-08-11 17:54:52'),
(8,1,'shena_nir','10.99.7.0/24',NULL,NULL,'active','2026-08-11 17:54:56','2026-08-11 17:54:56'),
(9,1,'Saifulkst','10.99.2.0/24',NULL,NULL,'active','2026-08-11 17:55:00','2026-08-11 17:55:00'),
(10,1,'pool_180','10.99.180.0/24',NULL,NULL,'active','2026-08-11 17:55:04','2026-08-11 17:55:04'),
(11,1,'mosharof_bgoly','10.99.10.0/24',NULL,NULL,'active','2026-08-11 17:55:08','2026-08-11 17:55:08'),
(12,1,'lgedks','10.99.6.0/24',NULL,NULL,'active','2026-08-11 17:55:12','2026-08-11 17:55:12'),
(13,1,'inactive','10.99.99.0/24',NULL,NULL,'active','2026-08-12 19:33:49','2026-08-12 19:33:49');
/*!40000 ALTER TABLE `app_ip_pools` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_settings`
--

DROP TABLE IF EXISTS `app_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `app_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_settings`
--

LOCK TABLES `app_settings` WRITE;
/*!40000 ALTER TABLE `app_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bkash_sms_payments`
--

DROP TABLE IF EXISTS `bkash_sms_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bkash_sms_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `sms_sender` varchar(255) DEFAULT NULL,
  `raw_sms` text NOT NULL,
  `customer_number` varchar(255) DEFAULT NULL,
  `trx_id` varchar(255) DEFAULT NULL,
  `ledger_trx_id` varchar(255) DEFAULT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `payment_id` bigint(20) unsigned DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bkash_sms_payments_ledger_trx_id_unique` (`ledger_trx_id`),
  KEY `bkash_sms_payments_customer_id_foreign` (`customer_id`),
  KEY `bkash_sms_payments_invoice_id_foreign` (`invoice_id`),
  KEY `bkash_sms_payments_payment_id_foreign` (`payment_id`),
  KEY `bkash_sms_payments_customer_number_index` (`customer_number`),
  KEY `bkash_sms_payments_status_index` (`status`),
  KEY `bkash_sms_payments_trx_id_index` (`trx_id`),
  KEY `bkash_sms_payments_entry_by_index` (`entry_by`),
  KEY `bkash_sms_payments_entry_by_type_index` (`entry_by_type`),
  CONSTRAINT `bkash_sms_payments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bkash_sms_payments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bkash_sms_payments_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bkash_sms_payments`
--

LOCK TABLES `bkash_sms_payments` WRITE;
/*!40000 ALTER TABLE `bkash_sms_payments` DISABLE KEYS */;
INSERT INTO `bkash_sms_payments` VALUES
(1,'19999999999','sms_device','19999999999','19999999999\n[ISP_SMS] Congratulations, the sender test is successful, please continue to add forwarding rules!\nSIM1_TestOperator_18888888888\nSubId：0\n2026-08-11 20:40:55\nAnike Redmi',NULL,NULL,NULL,NULL,NULL,NULL,'failed',NULL,NULL,NULL,'Could not parse bKash amount or TrxID from SMS.','2026-08-11 20:40:56','2026-08-11 20:40:56'),
(2,'19999999999','sms_device','19999999999','19999999999\n[ISP_SMS] Congratulations, the sender test is successful, please continue to add forwarding rules!\nSIM1_TestOperator_18888888888\nSubId：0\n2026-08-11 20:41:14\nAnike Redmi',NULL,NULL,NULL,NULL,NULL,NULL,'failed',NULL,NULL,NULL,'Could not parse bKash amount or TrxID from SMS.','2026-08-11 20:41:14','2026-08-11 20:41:14'),
(3,'Anike Redmi','sms_device',NULL,'SIM2_\nSubId：0\n2026-08-11 21:03:44\nAnike Redmi',NULL,NULL,NULL,NULL,NULL,NULL,'failed',NULL,NULL,NULL,'Could not parse bKash amount or TrxID from SMS.','2026-08-11 21:03:46','2026-08-11 21:03:46'),
(4,'Anike Redmi','sms_device',NULL,'SIM2_\nSubId：0\n2026-08-11 21:03:46\nAnike Redmi',NULL,NULL,NULL,NULL,NULL,NULL,'failed',NULL,NULL,NULL,'Could not parse bKash amount or TrxID from SMS.','2026-08-11 21:03:46','2026-08-11 21:03:46'),
(5,'Anike Redmi','sms_device','bKash','bKash\nYou have received Tk 12.00 from 01972777070. Fee Tk 0.00. Balance Tk 47,954.09. TrxID DHB1CZ173X at 11/08/2026 21:06\nSIM2_\nSubId：2\n2026-08-11 21:06:08\nAnike Redmi','01972777070','DHB1CZ173X','DHB1CZ173X',NULL,12.00,'2026-08-11','pending',NULL,NULL,NULL,'No customer matched this bKash sender number.','2026-08-11 21:06:09','2026-08-11 21:06:09'),
(6,'My Usage','sms_device','My Usage','My Usage\nজুলাই২৬\nরিচার্জঃ 1004.00৳\nখরচঃ\nভয়েসঃ 109.27৳\nডাটাঃ 0.00৳\nঅন্যান্যঃ 25.03৳\nSIM2_\nSubId：2\n2026-08-12 11:14:45\nAnike Redmi',NULL,NULL,NULL,NULL,NULL,NULL,'failed',NULL,NULL,NULL,'Could not parse bKash amount or TrxID from SMS.','2026-08-12 11:14:46','2026-08-12 11:14:46'),
(7,'19999999999','sms_device','19999999999','19999999999\n[ISP_SMS] Congratulations, the sender test is successful, please continue to add forwarding rules!\nSIM1_TestOperator_18888888888\nSubId：0\n2026-08-12 22:48:42\nShofiq M',NULL,NULL,NULL,NULL,NULL,NULL,'failed',NULL,NULL,NULL,'Could not parse bKash amount or TrxID from SMS.','2026-08-12 22:48:44','2026-08-12 22:48:44'),
(8,'Shofiq M','sms_device',NULL,'SIM1_\nSubId：0\n2026-08-12 22:50:56\nShofiq M',NULL,NULL,NULL,NULL,NULL,NULL,'failed',NULL,NULL,NULL,'Could not parse bKash amount or TrxID from SMS.','2026-08-12 22:50:58','2026-08-12 22:50:58'),
(9,'Shofiq M','sms_device',NULL,'SIM1_\nSubId：0\n2026-08-12 22:51:14\nShofiq M',NULL,NULL,NULL,NULL,NULL,NULL,'failed',NULL,NULL,NULL,'Could not parse bKash amount or TrxID from SMS.','2026-08-12 22:51:16','2026-08-12 22:51:16'),
(10,'Shofiq M','sms_device','bKash','bKash\nYou have received Tk 11.00 from 01972777070. Fee Tk 0.00. Balance Tk 255.63. TrxID DHC0EBDYUS at 12/08/2026 22:59\n\nSubId：1\n2026-08-12 22:59:36\nShofiq M','01972777070','DHC0EBDYUS','DHC0EBDYUS',NULL,11.00,'2026-08-12','pending',NULL,NULL,NULL,'No customer matched this bKash sender number.','2026-08-12 22:59:38','2026-08-12 22:59:38'),
(11,'Shofiq M','sms_device','bKash','bKash\nYou have received Tk 12.00 from 01972777070. Fee Tk 0.00. Balance Tk 267.63. TrxID DHC6EBL5IW at 12/08/2026 23:05\n\nSubId：1\n2026-08-12 23:05:46\nShofiq M','01972777070','DHC6EBL5IW','DHC6EBL5IW',NULL,12.00,'2026-08-12','processed',305,12,4,'Customer matched by sender number. Payment recorded successfully.','2026-08-12 23:05:48','2026-08-12 23:05:49');
/*!40000 ALTER TABLE `bkash_sms_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_balance_transactions`
--

DROP TABLE IF EXISTS `customer_balance_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_balance_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `payment_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `payment_account_id` bigint(20) unsigned DEFAULT NULL,
  `payment_method` varchar(255) DEFAULT NULL,
  `direction` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) NOT NULL,
  `transaction_date` date NOT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `operation_key` char(36) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_balance_transactions_operation_key_unique` (`operation_key`),
  KEY `customer_balance_transactions_customer_id_foreign` (`customer_id`),
  KEY `customer_balance_transactions_payment_id_foreign` (`payment_id`),
  KEY `customer_balance_transactions_entry_by_index` (`entry_by`),
  KEY `customer_balance_transactions_entry_by_type_index` (`entry_by_type`),
  KEY `balance_transactions_account_ledger_index` (`payment_account_id`,`direction`,`payment_id`,`transaction_date`,`id`),
  KEY `balance_transactions_method_ledger_index` (`payment_method`,`direction`,`payment_id`,`transaction_date`,`id`),
  KEY `customer_balance_transactions_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `customer_balance_transactions_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_balance_transactions_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customer_balance_transactions_payment_account_id_foreign` FOREIGN KEY (`payment_account_id`) REFERENCES `payment_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customer_balance_transactions_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_balance_transactions`
--

LOCK TABLES `customer_balance_transactions` WRITE;
/*!40000 ALTER TABLE `customer_balance_transactions` DISABLE KEYS */;
INSERT INTO `customer_balance_transactions` VALUES
(1,'2','user',348,NULL,NULL,NULL,'cash','credit',500.00,500.00,'2026-08-11',NULL,NULL,NULL,'2026-08-11 14:43:30','2026-08-11 14:43:30'),
(2,'2','user',348,NULL,NULL,NULL,'cash','credit',500.00,1000.00,'2026-08-11',NULL,NULL,NULL,'2026-08-11 15:02:56','2026-08-11 15:02:56'),
(3,'2','user',348,NULL,NULL,NULL,'cash','credit',500.00,1500.00,'2026-08-11',NULL,NULL,NULL,'2026-08-11 15:17:12','2026-08-11 15:17:12'),
(4,'2','user',348,NULL,NULL,NULL,'cash','credit',500.00,2000.00,'2026-08-11',NULL,NULL,NULL,'2026-08-11 15:25:51','2026-08-11 15:25:51'),
(5,'2','user',348,NULL,NULL,NULL,'cash','credit',500.00,2500.00,'2026-08-11',NULL,NULL,NULL,'2026-08-11 20:18:28','2026-08-11 20:18:28'),
(6,'2','user',348,NULL,NULL,NULL,'advance','debit',500.00,2000.00,'2026-08-11','INV-2',NULL,'Automatic renewal from advance balance for remembered package.','2026-08-11 20:18:28','2026-08-11 20:18:28'),
(7,'2','user',348,NULL,NULL,NULL,'advance','debit',500.00,1500.00,'2026-08-11','INV-3',NULL,'Automatic renewal from advance balance for remembered package.','2026-08-11 20:18:28','2026-08-11 20:18:28'),
(8,'2','user',348,NULL,NULL,NULL,'advance','debit',500.00,1000.00,'2026-08-11','INV-4',NULL,'Automatic renewal from advance balance for remembered package.','2026-08-11 20:18:28','2026-08-11 20:18:28'),
(9,'2','user',348,NULL,NULL,NULL,'advance','debit',500.00,500.00,'2026-08-11','INV-5',NULL,'Automatic renewal from advance balance for remembered package.','2026-08-11 20:18:28','2026-08-11 20:18:28'),
(10,'2','user',348,NULL,NULL,NULL,'advance','debit',500.00,0.00,'2026-08-11','INV-6',NULL,'Automatic renewal from advance balance for remembered package.','2026-08-11 20:18:28','2026-08-11 20:18:28'),
(11,'5','user',321,NULL,NULL,1,'bkash','credit',1000.00,1000.00,'2026-08-12',NULL,NULL,NULL,'2026-08-12 21:48:10','2026-08-12 21:48:10'),
(12,'5','user',321,NULL,NULL,NULL,'advance','debit',500.00,500.00,'2026-08-12','INV-10',NULL,'Automatic renewal from advance balance for remembered package.','2026-08-12 21:48:10','2026-08-12 21:48:10'),
(13,'5','user',321,NULL,NULL,NULL,'advance','debit',500.00,0.00,'2026-08-12','INV-11',NULL,'Automatic renewal from advance balance for remembered package.','2026-08-12 21:48:10','2026-08-12 21:48:10'),
(14,'Shofiq M','sms_device',305,4,NULL,2,'bkash','credit',2.00,2.00,'2026-08-12','DHC6EBL5IW',NULL,'Unallocated payment amount added to customer advance balance.','2026-08-12 23:05:48','2026-08-12 23:05:48');
/*!40000 ALTER TABLE `customer_balance_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_mikrotik_router`
--

DROP TABLE IF EXISTS `customer_mikrotik_router`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_mikrotik_router` (
  `customer_id` bigint(20) unsigned NOT NULL,
  `mikrotik_router_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  UNIQUE KEY `customer_mikrotik_router_customer_id_mikrotik_router_id_unique` (`customer_id`,`mikrotik_router_id`),
  KEY `customer_mikrotik_router_mikrotik_router_id_foreign` (`mikrotik_router_id`),
  CONSTRAINT `customer_mikrotik_router_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_mikrotik_router_mikrotik_router_id_foreign` FOREIGN KEY (`mikrotik_router_id`) REFERENCES `mikrotik_routers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_mikrotik_router`
--

LOCK TABLES `customer_mikrotik_router` WRITE;
/*!40000 ALTER TABLE `customer_mikrotik_router` DISABLE KEYS */;
INSERT INTO `customer_mikrotik_router` VALUES
(1,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(2,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(3,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(4,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(5,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(6,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(7,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(8,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(9,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(10,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(11,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(12,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(13,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(14,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(15,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(16,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(17,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(18,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(19,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(20,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(21,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(22,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(23,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(24,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(25,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(26,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(27,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(28,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(29,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(30,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(31,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(32,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(33,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(34,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(35,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(36,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(37,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(38,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(39,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(40,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(41,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(42,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(43,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(44,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(45,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(46,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(47,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(48,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(49,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(50,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(51,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(52,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(53,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(54,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(55,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(56,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(57,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(58,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(59,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(60,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(61,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(62,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(63,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(64,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(65,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(66,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(67,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(68,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(69,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(70,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(71,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(72,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(73,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(74,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(75,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(76,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(77,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(78,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(79,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(80,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(81,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(82,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(83,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(84,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(85,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(86,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(87,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(88,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(89,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(90,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(91,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(92,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(93,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(94,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(95,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(96,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(97,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(98,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(99,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(100,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(101,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(102,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(103,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(104,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(105,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(106,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(107,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(108,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(109,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(110,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(111,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(112,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(113,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(114,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(115,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(116,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(117,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(118,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(119,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(120,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(121,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(122,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(123,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(124,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(125,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(126,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(127,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(128,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(129,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(130,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(131,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(132,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(133,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(134,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(135,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(136,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(137,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(138,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(139,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(140,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(141,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(142,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(143,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(144,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(145,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(146,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(147,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(148,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(149,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(150,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(151,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(152,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(153,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(154,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(155,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(156,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(157,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(158,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(159,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(160,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(161,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(162,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(163,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(164,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(165,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(166,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(167,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(168,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(169,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(170,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(171,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(172,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(173,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(174,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(175,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(176,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(177,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(178,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(179,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(180,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(181,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(182,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(183,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(184,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(185,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(186,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(187,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(188,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(189,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(190,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(191,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(192,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(193,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(194,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(195,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(196,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(197,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(198,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(199,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(200,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(201,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(202,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(203,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(204,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(205,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(206,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(207,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(208,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(209,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(210,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(211,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(212,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(213,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(214,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(215,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(216,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(217,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(218,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(219,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(220,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(221,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(222,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(223,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(224,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(225,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(226,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(227,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(228,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(229,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(230,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(231,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(232,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(233,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(234,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(235,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(236,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(237,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(238,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(239,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(240,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(241,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(242,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(243,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(244,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(245,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(246,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(247,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(248,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(249,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(250,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(251,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(252,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(253,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(254,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(255,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(256,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(257,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(258,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(259,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(260,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(261,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(262,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(263,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(264,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(265,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(266,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(267,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(268,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(269,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(270,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(271,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(272,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(273,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(274,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(275,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(276,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(277,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(278,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(279,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(280,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(281,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(282,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(283,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(284,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(285,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(286,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(287,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(288,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(289,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(290,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(291,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(292,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(293,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(294,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(295,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(296,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(297,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(298,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(299,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(300,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(301,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(302,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(303,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(304,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(305,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(306,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(307,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(308,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(309,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(310,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(311,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(312,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(313,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(314,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(315,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(316,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(317,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(318,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(319,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(320,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(321,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(322,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(323,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(324,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(325,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(326,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(327,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(328,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(329,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(330,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(331,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(332,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(333,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(334,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(335,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(336,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(337,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(338,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(339,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(340,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(341,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(342,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(343,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(344,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(345,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(346,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(347,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(348,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(349,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(350,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(351,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(352,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(353,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(354,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(355,1,'2026-08-12 19:02:36','2026-08-12 19:02:36'),
(356,1,'2026-08-12 19:02:36','2026-08-12 19:02:36');
/*!40000 ALTER TABLE `customer_mikrotik_router` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `connection_id` varchar(255) DEFAULT NULL,
  `mikrotik_username` varchar(255) DEFAULT NULL,
  `mikrotik_password` text DEFAULT NULL,
  `mikrotik_router_id` bigint(20) unsigned DEFAULT NULL,
  `use_fixed_ip` tinyint(1) NOT NULL DEFAULT 0,
  `fixed_ip_address` varchar(45) DEFAULT NULL,
  `learned_ip_address` varchar(45) DEFAULT NULL,
  `learned_ip_package_id` bigint(20) unsigned DEFAULT NULL,
  `last_connected_ip` varchar(45) DEFAULT NULL,
  `last_connected_mac` varchar(64) DEFAULT NULL,
  `last_connected_at` timestamp NULL DEFAULT NULL,
  `address` text NOT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `never_suspend` tinyint(1) NOT NULL DEFAULT 0,
  `grace_until` date DEFAULT NULL,
  `grace_days` int(10) unsigned DEFAULT NULL,
  `grace_used_at` timestamp NULL DEFAULT NULL,
  `service_valid_from` date DEFAULT NULL,
  `service_valid_until` date DEFAULT NULL,
  `service_validity_note` text DEFAULT NULL,
  `account_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_customer` tinyint(1) NOT NULL DEFAULT 1,
  `is_vendor` tinyint(1) NOT NULL DEFAULT 0,
  `is_reseller` tinyint(1) NOT NULL DEFAULT 0,
  `reseller_id` bigint(20) unsigned DEFAULT NULL,
  `reseller_daily_payment_limit` decimal(12,2) DEFAULT NULL,
  `reseller_commission_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customers_connection_id_unique` (`connection_id`),
  UNIQUE KEY `customers_mikrotik_username_unique` (`mikrotik_username`),
  KEY `customers_phone_index` (`phone`),
  KEY `customers_mikrotik_router_id_foreign` (`mikrotik_router_id`),
  KEY `customers_entry_by_index` (`entry_by`),
  KEY `customers_entry_by_type_index` (`entry_by_type`),
  KEY `customers_learned_ip_package_id_foreign` (`learned_ip_package_id`),
  KEY `customers_reseller_id_foreign` (`reseller_id`),
  KEY `customers_is_reseller_status_index` (`is_reseller`,`status`),
  CONSTRAINT `customers_learned_ip_package_id_foreign` FOREIGN KEY (`learned_ip_package_id`) REFERENCES `internet_packages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customers_mikrotik_router_id_foreign` FOREIGN KEY (`mikrotik_router_id`) REFERENCES `mikrotik_routers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customers_reseller_id_foreign` FOREIGN KEY (`reseller_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=357 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES
(1,'2','user','customs','Not provided',NULL,'customs','customs','eyJpdiI6ImVlSlhmRWZoNlFsVWFMczhrZEMzc2c9PSIsInZhbHVlIjoiVjJqZ1FqT2lSN1BBSit3Sm1OWW9xdz09IiwibWFjIjoiMjNjM2Y4ODU5YWU4ODE1YWFmMzFiMWE0ZDk0MmU5N2YyNWFkNjg5MjUwNDE5OTZjZTk5ODhjZWYxNjk5Y2I0YiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: customs\nProfile: 40 MB 180 IP\nService: any\nRouter comment: none','inactive',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(2,'2','user','6road_azizul','Not provided',NULL,'6road_azizul','6road_azizul','eyJpdiI6IjgzQTU0YkQxQ1RZUE1QK1NHOVpjUXc9PSIsInZhbHVlIjoiRlErRi9pbVgrQlJLUWZLV21LMDFtdz09IiwibWFjIjoiZjkyMzBkNWNlMjA5YzJhMjM3ZmE0ODRmYjJlYWIxMWNiMjExMDdlZTgyOWFjMGQ2MDYyNzI5ZTc0ZjU0NDI4MyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: 6road_azizul\nProfile: 30 MB govt_college\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(3,'2','user','Anike','Not provided',NULL,'customsb','customsb','eyJpdiI6IjlZamtXRjRRU1h3TDVXQzVueURhUkE9PSIsInZhbHVlIjoiOFFNOFE3aFBPc2Ftd0xhTXhxKzhWZz09IiwibWFjIjoiMzRmYzE2YWNkZTczMzg5MmVkMWFiNWY4NzYzYzg4MDc2NDYzZDMxNjFhMjU2ZTFjODg5MjUyYjM0MTliOGEyMyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: customsb\nProfile: 30 MB Saifulkst\nService: any\nRouter comment: Anike','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(4,'2','user','Anike BNP Office','Not provided',NULL,'bnp','bnp','eyJpdiI6Ik52SkEwVXg5cFF3V3VBencyaEZkVlE9PSIsInZhbHVlIjoiRWo5Q3JjVjJqWGhFTUp1MHJEQlNtQT09IiwibWFjIjoiYTI4MzFmYzY0OTNlZmJlOGY2MzQ2OTU4Y2JhODFhY2IyYzZjMDc4OGQwOGY0MzM1YjQ5OTBkYmJmZTIxZjZmNiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: bnp\nProfile: 50 MB KPI\nService: any\nRouter comment: Anike BNP Office','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(5,'2','user','Anike','Not provided',NULL,'bazar_biplob_vi','bazar_biplob_vi','eyJpdiI6IkJIb2o0QkF1VmdrTWVFa3lvaDR2b3c9PSIsInZhbHVlIjoiT0pVT0Y4ODltcjB2THhDZll1cTBMUT09IiwibWFjIjoiNjc5ZGNhNGRkN2JjMzFiZjVjNzM4MDMzOTk1MDlmY2FjZTU4YzQ0YTkwYWVlZDhmODgyZGRiNjFkYTliYTRhMSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: bazar_biplob_vi\nProfile: 30 MB govt_college\nService: any\nRouter comment: Anike','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(6,'2','user','Anike','Not provided',NULL,'agrani','agrani','eyJpdiI6Im9WUFllSGJMMGtIb3AvYVZaUmlOR0E9PSIsInZhbHVlIjoiSlk2MUQ0OHNPMy9ZenM2cXN3ZFBYZz09IiwibWFjIjoiYmM2NWNlNTI5NTNmZDIwMmFjNjA4YWEzODZjYTg4M2MwNGVmMjJhOGY4NmQ1ODRhNGFhY2M2NjU3OTAzZDk4NSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: agrani\nProfile: 110 MB 141ranvid\nService: any\nRouter comment: Anike','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(7,'2','user','Anike','Not provided',NULL,'firoz_vi','firoz_vi','eyJpdiI6InQwcjlGUjhIZ0pRN0F1NDgrLzhmVWc9PSIsInZhbHVlIjoiVGoyai9wbFNxYTFFa3Y2YXNCcGNzdz09IiwibWFjIjoiYzM3NmE0NjI5MTdlMTBiY2M4MzA2MDQ3YzA5YTAyNGJmNmMzOTE5YjQ5ZTUzOWMyOGYzMzY2ZTlkNzFhZmFkZCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: firoz_vi\nProfile: 30 MB govt_college\nService: any\nRouter comment: Anike\n[2026-08-10 23:14] Manual validity override: not set → 2026-08-31. Reason: end','active',0,NULL,NULL,NULL,NULL,'2026-08-31','[2026-08-10 23:14] Manual validity override: not set → 2026-08-31. Reason: end',0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:14:24'),
(8,'2','user','Anike','Not provided',NULL,'dolon_mama','dolon_mama','eyJpdiI6IndYd0JXbFR1QzNhTTVqSUJvdSt5YWc9PSIsInZhbHVlIjoiZHdEdWtRbFlZa21IL3IrUWY0dE9nZz09IiwibWFjIjoiN2FkMWI4MGVjZTQzMjY2MGNlYzFmOTAxMzkzOTFlNmFjODdiYTQ2MGNlNzZhNGMyZWUwOWE1Yjg2YjVlMjllMCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: dolon_mama\nProfile: 50 Mb_Travelshouse\nService: any\nRouter comment: Anike','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(9,'2','user','Anike','Not provided',NULL,'dr_shahariar_rajon','dr_shahariar_rajon','eyJpdiI6IlZ6MU51dTVkd2MyZjN6cngwWjNwdkE9PSIsInZhbHVlIjoiNkZHeHdOVmZJT2tqWFpLNE90bCtlQT09IiwibWFjIjoiNGUyNmYyNTUzNDFiY2EzYzdhZDJhOGRjZDJiNzUxYmM3NDQ2NDI1YmI0NTYxMGJjNWY1MDIyYTFlYjc4NTllNCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: dr_shahariar_rajon\nProfile: 30 MB Lgedks\nService: any\nRouter comment: Anike','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(10,'2','user','access','Not provided',NULL,'access','access','eyJpdiI6ImIydC9EbkVpSC82QzREbkRsOUdRbWc9PSIsInZhbHVlIjoiZU4vWm9ZZ1I1ZzByWFBlZ2ErMEpHdz09IiwibWFjIjoiYTc4MjIwNWI4NDJlOTEwODVjNTEyYjA3OTU5OGQwZTM1ZmFiZDZjMDc3NDFmNDMxZDNmMzFiYTMzZjcwNTA5NCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: access\nProfile: 30 MB Saifulkst\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(11,'2','user','jamai_star','Not provided',NULL,'jamai_star','jamai_star','eyJpdiI6InNGZyt3MFNlS2ltUVpHMkFYWTZaSWc9PSIsInZhbHVlIjoiZlF2T2JXNFVkWEpEYVhXd3FsVzNKQT09IiwibWFjIjoiNTMzYTYyNDQ5N2M5NjcyNDUzNmM1Zjc3ZWQwNzcyZDkzYzNkMGZiZGU5YTBlMTI0ODY4ZDM2YTQ0ZDQxNjNmMyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: jamai_star\nProfile: 200 Mb Star\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(12,'2','user','Shofiq','Not provided',NULL,'farukvai','farukvai','eyJpdiI6InpSb1k5eFhhMEpGUFNlOEltaytZTFE9PSIsInZhbHVlIjoiVXpCajdWK0dXWm02TVR2MWdmYnZwQT09IiwibWFjIjoiM2FhODAzZTlhZTkxNjVkYWNjZjYyOTNlNzdkMDhkZjQ3MmU2OTU5YjFmZThhY2NkMGQzOGJmM2YwYzk4N2E0OCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: farukvai\nProfile: 30 Mb Star\nService: any\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(13,'2','user','abonicom','Not provided',NULL,'abonicom','abonicom','eyJpdiI6Im9Cb0psUkVXWXROdTRxRG5qR29DUmc9PSIsInZhbHVlIjoicEdWSTVOK1F4Z2FXQTlCQnZLRTkwZz09IiwibWFjIjoiYTViMDE2MTE2NjlkOTE3MGYzMjAzZThkZDE2MWI1NDYzYTM1YjkxYjc2Njk5MGI1Mzg0ZGRmN2Q1YzA4YTg0YyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: abonicom\nProfile: 30 MB shena_nir\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(14,'2','user','shofiq','Not provided',NULL,'babuvai6','babuvai6','eyJpdiI6Ikh3WUhucVlhVEsrdFNURUJqcm1FOEE9PSIsInZhbHVlIjoia0paVk0xWkxCMGRieVVMdzRqK215Zz09IiwibWFjIjoiNDkyY2NlMzVkYzg5YTFjZTU3ZjRkNmNiNTU3MjhmMTA2NjNmMTM0MjJhOTcwYmZiNjE2MDFiNGUxMmE4N2VlYSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: babuvai6\nProfile: 30 MB Lgedks\nService: any\nRouter comment: shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(15,'2','user','shofiq','Not provided',NULL,'disha1','disha1','eyJpdiI6IkZXblZOZ0MwN2ZBMlVieXg2bU5jRFE9PSIsInZhbHVlIjoiL2E3RmU3M1lNVU5MRlZHNmEyMmp1UT09IiwibWFjIjoiMDgxMjczYzU2NzA2MjAzYmRlNDVkNzc0Y2I4NWJlYWRjMDQ0ODY5YjQyZTg5YjFkYzYwOWJlNWY0ZGJiYWIzMSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: disha1\nProfile: 30 MB shena_nir\nService: any\nRouter comment: shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(16,'2','user','shofiq','Not provided',NULL,'icollegebm','icollegebm','eyJpdiI6ImM5Z3g0ay9pTEU1ZEtxcmxUQW9hQ1E9PSIsInZhbHVlIjoibFZZU2QyZVkzdklxS1N4QmZyK2MvZz09IiwibWFjIjoiNjg1NjVlOTZiYTE3MDdhY2IwODE1OWZmZmY0YzY5MGMzYzNkYTY1NDFhMTQwZjkzOTgxYzZlMThiNzEwMzZkOCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: icollegebm\nProfile: 30 Mb Star\nService: any\nRouter comment: shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(17,'2','user','shofiq','Not provided',NULL,'alomkhan','alomkhan','eyJpdiI6IjNRbFpKWFZvS1VUTk1JcEFYWTVlekE9PSIsInZhbHVlIjoiR0JRSkVRdTFqZjBjcmt3YTJZU25jdz09IiwibWFjIjoiOWYwNmYwMjRkMGJkNjFmYjA5NWZjYThiNjU1ZWE3MWQwZDY2NzRiN2ExYmViZmZmMmVjNTE3ZTRhY2YzOTViMSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: alomkhan\nProfile: 40 Mb Star\nService: any\nRouter comment: shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(18,'2','user','Shofiq','Not provided',NULL,'itech','itech','eyJpdiI6IkY2ZlRBV0cwYkloeGxiL2YyNUl3UGc9PSIsInZhbHVlIjoibEpvNFVyV1ptT282MllSMzhHRE5Cdz09IiwibWFjIjoiOTNmZGEwNTI5MTc4NzhjN2VhZGNiYWE3M2QwNDRmMGVkMDk0MGY1NTZkYzk5ZjM5M2VhNzYwODFiZTFhZWM0OSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: itech\nProfile: 30 MB shena_nir\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(19,'2','user','Shofiq','Not provided',NULL,'btclhome','btclhome','eyJpdiI6IldnR28wRWhCNE5IS3ZvdHFUSEJWaEE9PSIsInZhbHVlIjoiVEhueU9sQThsWlNRalFyZkQxK0VWZz09IiwibWFjIjoiMmE3YjhmMTEyZGRjZjI3ZGVlZGIwOTgxNDJjNzlhZjlhMmM5MWRiN2FjODVlZjNiMTcxOWIwZDRlZTYwM2VjZCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: btclhome\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(20,'2','user','Shofiq','Not provided',NULL,'070dailykus','070dailykus','eyJpdiI6IjVlK1FKZG1jbExOcHdISW5OZ3BHdHc9PSIsInZhbHVlIjoiZ21LVkhhQ1B6bmcyc3Q0WU1MT0dYZz09IiwibWFjIjoiMDBhM2MzNTRmZDk4YTdhOWFkMDU3MDY0Mzc0MDRhYzgxODhmYzRhYmRhYThiNGIxOWNlMjAxN2U2NWY2NzFmNiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: 070dailykus\nProfile: 30 MB shena_nir\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(21,'2','user','Shofiq','Not provided',NULL,'familyware','familyware','eyJpdiI6IkUwYkMySXhaM2hWS0hkTjFpU2ZYV1E9PSIsInZhbHVlIjoibnlsZG9NQ0FXN1JZQVlQdndTU2tCUT09IiwibWFjIjoiNzNmYzVlZjJjNTczZGNhMzkxMGVjZDhiYmM2NWNjMjY2ZDA0NGVhYWQ5YjI3OTU2MmFhMGE0NzYzYmI5MzhmZiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: familyware\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(22,'2','user','Shofiq','Not provided',NULL,'azizulvai','azizulvai','eyJpdiI6IjcyQWtDYjhDU3d5ZjVqWmZPa2F4T0E9PSIsInZhbHVlIjoiVW1wVjNlczNCOVNBV1ZSekVvU2Zidz09IiwibWFjIjoiMGEyOGFkMzJlODljNzZiOTFlMTcyOWRmODA2YTNmNmNkMmI0OTdjYTBhN2NhNjFkNWQ4MjVhOWEyM2FmZjE1MCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: azizulvai\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(23,'2','user','Shofiq','Not provided',NULL,'azomuncle','azomuncle','eyJpdiI6IkxqYjIzQWhzVm9HaWUyZ3VVWkowZmc9PSIsInZhbHVlIjoiMWJOWGJiUjBoNTNwbHpEWTN5bkJGZz09IiwibWFjIjoiYjllNWViYWNhMzJkMTRiM2VmNGEzNmJiZDQyYjdmMjhjNzgzMzkyMWE0YmFmNGIxYjZhMDMyNzc3MTM3NGEwNCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: azomuncle\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(24,'2','user','Shofiq','Not provided',NULL,'anupomkst','anupomkst','eyJpdiI6InJlQkg4NDFObjh2WE83aW5JRmxIeEE9PSIsInZhbHVlIjoiUXMvUU5iRGcrSWsrUk1kZTQ4akMyQT09IiwibWFjIjoiZWJlNDIxOWIxMTA2MTg1ODI3OTgwYTMzMjY3ZDg5MTAwZWQ3ODYwNDU1OTJlNjc3YzAyMTMzMjMwNzAyNGM4ZCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: anupomkst\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(25,'2','user','Shofiq','Not provided',NULL,'driqbalsir','driqbalsir','eyJpdiI6IjVNeVFwWjNTVkF3NDRSOFA4SDBINFE9PSIsInZhbHVlIjoiTkRPaEdvMXV6RHlxQW40bUg3dWswQT09IiwibWFjIjoiOWY4NTZiYjg4ZTExOGNlMmE4YWQyMjEwOGQ2NmVmYTY4NTAzNmE2ZjIyMjlmYWM2ZjZmZWRkZWY5MjBmZGJhYyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: driqbalsir\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(26,'2','user','Shofiq','Not provided',NULL,'dramansir','dramansir','eyJpdiI6Ik1yVG5oMDVKMmt2TFZIVzVaNFROZXc9PSIsInZhbHVlIjoieUtpZzFiZ2psUlVPQnpQWTNRU3orZz09IiwibWFjIjoiMDlmMWI3YjExOTdjZjBiODc4YTAyOTRmYWJiNmE3MmUwMTE5NzNjMDE2YTYzOGYwNjExMjhhM2U3OGNjNmY1MCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: dramansir\nProfile: 30 Mb_Travelshouse\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(27,'2','user','Shofiq','Not provided',NULL,'101wdbrokonsir','101wdbrokonsir','eyJpdiI6ImdQMEIwQmg1bldoVEc5YUN5QkppK2c9PSIsInZhbHVlIjoiSXQ3eCtPQ0lhbkJNMDNSRG9VVitjZz09IiwibWFjIjoiMzc3MzQ2MjE2ZGJlOWE2YzU2ZTc1NGE5NmQxNGM2MDMyYTA5M2RjOWJhYmVhMjZiYTI5MDU2MmU2YTg0NjM2NSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: 101wdbrokonsir\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(28,'2','user','Shofiq','Not provided',NULL,'julia','julia','eyJpdiI6IkR1QVM3QXlNays3SGZ6RElXZWtXZ2c9PSIsInZhbHVlIjoiRXo0U3luOTNDU3RTYU5Mc29pc1JGUT09IiwibWFjIjoiZjU1Mzk1NjllZTdkMDFhYmQ5YmE5Yzk3NzZjMDljN2QyZjA3YTBkOWE1ZGI2MzUzYmQ0MTk3YjE1ZTQ4NDg1NyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: julia\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(29,'2','user','Shofiq','Not provided',NULL,'ikramvai','ikramvai','eyJpdiI6InM3Q0YwUXZaY2d3OXZ6VHN3NmFiWGc9PSIsInZhbHVlIjoiTHRzRkZoMHp0WWp1bE5HYUtONmR2Zz09IiwibWFjIjoiZjZmNjJlYWU4ZDVlZmM0MzY5ZWJlYzMxMzJhNGYzOGE4NjA3ODAyNDc0YmI5NmZhNTFkYzA5YjEyNGIyYTQ0YyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: ikramvai\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(30,'2','user','Shofiq','Not provided',NULL,'imrankst','imrankst','eyJpdiI6ImpxQmxLSWhvWmRqSEdHemRlWW15MWc9PSIsInZhbHVlIjoiZ1dmZGRxbk9QY2w3MVFGMGNrSVhndz09IiwibWFjIjoiYjQ1MWQyYzA3MWVkZTA3NzhhZjViNWRmM2FhMjQ4YjYzNmNiOGE4Mjg5YWQxNGIyZjBhNjEzZWZlYzQ2MTViMCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: imrankst\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(31,'2','user','babu_potua','Not provided',NULL,'babu_potua','babu_potua','eyJpdiI6InliOHpMVFEyQmk0OXV6elh1emg5UFE9PSIsInZhbHVlIjoiRkwyTGd3eXI5M1RuOW5OUWlqR0RDZz09IiwibWFjIjoiNDg4NDg5NTBhOWUzZDdlMGYyZjAyZTRmNWM4NjI0ZDU2MDcxNGIwZTg3NDYxODRlZGE2ZDY0Njc0N2Q4NzkyYyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: babu_potua\nProfile: 30 Mb_Travelshouse\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(32,'2','user','akash_kkhan','Not provided',NULL,'akash_kkhan','akash_kkhan','eyJpdiI6IitTV1o3dXJLSTZQT3NpTGFEY3FiWWc9PSIsInZhbHVlIjoic3N0U0FnYWNQUnppT1ZmcjN5UHBqQT09IiwibWFjIjoiNmVjODc3OGJjODY3YTA2ZGUwN2U3ODZkYzc5MTRjYmUxOGNkODMwMWYxOTk5ZWJmNzk0ZGY5MGZiYzk3MWYwOCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: akash_kkhan\nProfile: 30 Mb_Travelshouse\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(33,'2','user','shofiq','Not provided',NULL,'ashikvai','ashikvai','eyJpdiI6Im5SdmtnbDRZWmRzUy9pK1UxMitEK1E9PSIsInZhbHVlIjoiekg2emxzSVgxckd6TFRTRGxxMGV2dz09IiwibWFjIjoiYjJiYzRkNWU2YWZlNmIwOTBlMzRhYmM1ODdhNWUyMWQ0MGU4ZTEwYTYwMDBlZTNlMTE3NDJjNWZiNzA0ZDE5MyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: ashikvai\nProfile: 30 Mb Star\nService: any\nRouter comment: shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(34,'2','user','itech_sohel_home','Not provided',NULL,'itech_sohel_home','itech_sohel_home','eyJpdiI6IkxCQkMzTmdtMHIwY3JIVStDeVBGVnc9PSIsInZhbHVlIjoiWG4yQ1ZKck9MQlo0K1hLWEFUOUxtdz09IiwibWFjIjoiN2U2ZWIzYjUwYTBlZTRiYTEyMDBlOWRiN2FjMTRjNzU1YWQ3OWE0MDQ0ZDMxNTM0ZTRiYjg4NDUyMTVlNWIxZSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: itech_sohel_home\nProfile: 50 Mb_Travelshouse\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(35,'2','user','Shofiq','Not provided',NULL,'dristry_custom','dristry_custom','eyJpdiI6IjZKZXBYRXFDRXhtc1o4WkdYRTlZalE9PSIsInZhbHVlIjoiSE9BSDRRQXlEU0FDb041aTN5SHNUdz09IiwibWFjIjoiMGZhNjU3MTg2ZjY2Yjk1MDZmMzdlZGUzYzJhN2YyMjcyYzdlZWE1MWIwZmZiYTgwYzQzMTEyYmZhMzc5ZjA5OCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: dristry_custom\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(36,'2','user','Shofiq','Not provided',NULL,'cityfurniture','cityfurniture','eyJpdiI6IkMvSlhzdDZNeVJZdzRISitoN2xsYXc9PSIsInZhbHVlIjoiOWc2Q3lsa05MMUM5N1crVU84MVFwdz09IiwibWFjIjoiNThjYzI5OGRlNjEwNWIzMzY0ZTU3NWViNGY1NmQwM2QxZmM2MDJkNjdhZThhNmUwMzczMzA0OTZlOTFkM2YyZSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: cityfurniture\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(37,'2','user','Shofiq','Not provided',NULL,'familyjewel','familyjewel','eyJpdiI6InBzMEp0bGJ5VDI1YnFpc3FnUTZxL2c9PSIsInZhbHVlIjoicE1DQWpRQjVQSlhIQ1Vvcyt2VkZ4Zz09IiwibWFjIjoiZjU3NjgyYTc2NzM3NDI1N2ZlMTNiZWRjNGJlMmM5MjQ2MGRlYjFlNzM4NmNiNTZlMTk1MTM3YmZhNGM0NjBhMCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: familyjewel\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(38,'2','user','Shofiq','Not provided',NULL,'apexm','apexm','eyJpdiI6Inh0dzNxclJEK3ZidHFzWFVvb2VpM0E9PSIsInZhbHVlIjoiRk9TdEpwb0hyZzhrT2k3NmFyTU1DZz09IiwibWFjIjoiNmZhNjEzNDAyMWVlOTM4YzU1MzgwNDliYmJjYTQ4ZjM3ODk4ZjJkZGI2YmQ4ZDg1NjJlMjcxNWFiMDkxMzQyOCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: apexm\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: Shofiq','inactive',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(39,'2','user','helelvai','Not provided',NULL,'helelvai','helelvai','eyJpdiI6IjRTbEFpYkN1V3hOaEV2Z2ZyOU5mZkE9PSIsInZhbHVlIjoidlZrdTVITVloYnJMV2FmOE5DcUhKUT09IiwibWFjIjoiMDk3OGFkNzg4MjljNDQ1MDhkOTI4NzBiM2M0ZmY0MWM0Y2UzMmRlMDc5NDM4OWViMWZhZmRkZWUzMTlmYTIxZSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: helelvai\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(40,'2','user','Shofiq','Not provided',NULL,'examcare','examcare','eyJpdiI6IlhtTVRiTzEvSU96TUZFU1FKeDd4VXc9PSIsInZhbHVlIjoiYUp4c3lOQ2NPWjlyZU9FR0x4YjBEZz09IiwibWFjIjoiZDBmMzJiNjQ0Zjk2YzYwOWZjZmI3YmY4YWEzYmFiNDIzN2M2MDEwNjJhYWU5NzdjM2MyYjFjOWQzYWVmMTE5OCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: examcare\nProfile: 30 Mb_Travelshouse\nService: any\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(41,'2','user','Anike','Not provided',NULL,'Dr_Samrat_Chember','Dr_Samrat_Chember','eyJpdiI6ImZKeHdvZDZGY0M1Mi9nTXFPUFhQTmc9PSIsInZhbHVlIjoiQmhldGFxbzc1M21XYW9NM3ZuTTd1QT09IiwibWFjIjoiZjkzYmVkMjU3MTNjYzY4M2I2NDQyZGI3ZmQxMjc2ZDdkZGY1NTc0ZDhlMDBhNmMxOGIyZjNkMjRkZjcwMDg0ZCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\nConnection ID: Dr_Samrat_Chember\nProfile: 50 Mb_Travelshouse\nService: pppoe\nRouter comment: Anike','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:17','2026-08-10 23:08:17'),
(42,'2','user','Anike','Not provided',NULL,'ava_monowar','ava_monowar','eyJpdiI6IlBQZlFjVW9Jc0NLM0VuMk5GQlB5WHc9PSIsInZhbHVlIjoiR21GVTJ6bklnaThnUjV4UnI1NEZPUT09IiwibWFjIjoiOTM0OWQyMDZjMmQwZGE5ZDVkYzU5MDg1MzBmYjNmNGI2OWMzOGU5MDQ1ZTBhYjk1NmEwMmYzZGExNzU2MGYwYSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: ava_monowar\nProfile: 30 MB govt_college\nService: any\nRouter comment: Anike','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(43,'2','user','Shofiq','Not provided',NULL,'banker','banker','eyJpdiI6IjBOdEI5VE02R013dmk4ZTNsNklvaGc9PSIsInZhbHVlIjoiWkFLMFF3SzRhRHhiSVczVG9lWkRFQT09IiwibWFjIjoiMDEwNTc2YTk3NDZjMTQ4ZjZlMmJkNTk2ZmRmZjk2ODkwOWVkMjAzNTcyMjVjNzI0ZTU3YWZkOWY4ODE1NDY4ZCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: banker\nProfile: 30 MB shena_nir\nService: any\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(44,'2','user','shofiq','Not provided',NULL,'hannanvai','hannanvai','eyJpdiI6IlQxakZnaXdHNURPSFJudDlhUDJlaXc9PSIsInZhbHVlIjoiMlU0bmRMMktDdFNlZWxTd2VuTlJ2UT09IiwibWFjIjoiZmMyNjQ5N2YzN2U5NTI1ZWFhMzI2ZDNiZTUzZGJiOGI4OGVkN2E2MTZhOGRlNjI1YWEwZTg0M2FmZTY2NTRkNyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: hannanvai\nProfile: 30 Mb_Travelshouse\nService: any\nRouter comment: shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(45,'2','user','Shofiq','Not provided',NULL,'apuvai','apuvai','eyJpdiI6IkJDcUF3eU9GRW9nODJhVXNKSEoyd1E9PSIsInZhbHVlIjoiYkpxL3M5bDFOcXJaUG9UdkI5SFZKdz09IiwibWFjIjoiZWYzNmRhMGM5ZjU0M2VlMmE1ZDRmMDEwNDQ4YTk0MDJmNTZjYTBjNmEzYTcwNmIyODIxNjM2ZTVkN2M4N2YyMCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: apuvai\nProfile: 30 Mb_Travelshouse\nService: any\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(46,'2','user','shofiq','Not provided',NULL,'ic_arif','ic_arif','eyJpdiI6IklNV2hXTDl5eTNtMlVJV3JOS296S1E9PSIsInZhbHVlIjoiRStNSENKS0FSUkdKME5JanBWL0VNZz09IiwibWFjIjoiYjRkNWQzMzU0MDYwNzk3MGUxNTNmMTlmM2Y5YTk4YzkxNWQ2NTIzMmFmNTQyMGE1NTgyNmJkZTdlZjE5ODRjYyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: ic_arif\nProfile: 30 MB shena_nir\nService: any\nRouter comment: shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(47,'2','user','Shofiq','Not provided',NULL,'asad','asad','eyJpdiI6InFjeFpLblE2dkp6ZGtXNXNWK0llQ0E9PSIsInZhbHVlIjoiTk8zOUlXR3lja0RkNy8vcS9KQjczZz09IiwibWFjIjoiYTAzOTlhMTY0YzYzZGZjMDgzOTNlYTVjMTM5NjI3ODRiYjQwYzRlM2E4MDc0NDU3OTFhMzkyYjc1M2Y2ZTgzZSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: asad\nProfile: 30 Mb_Travelshouse\nService: any\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(48,'2','user','ali_vai','Not provided',NULL,'ali_vai','ali_vai','eyJpdiI6IlkzOXVFQnpadkQ4K09naE5aelBJb3c9PSIsInZhbHVlIjoiQmV2cTRuZE5vQjRxUG9BdWJxN1pHQT09IiwibWFjIjoiZWRkN2I5ZmRjZTAzYTQ0YWRjZWFkMGViYjljYjM3NTlkMWE1MDExZjE4M2YxM2RlZGMzMDRhMDJhNzA2NTI5NiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: ali_vai\nProfile: 30 Mb_Travelshouse\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(49,'2','user','Shofiq','Not provided',NULL,'islamia_ict','islamia_ict','eyJpdiI6ImFtcDZERDR0VHlteUtOWTZTSnl3VVE9PSIsInZhbHVlIjoiV2wwZHhQcFBKRnJ2ZmRNZGliLzZQUT09IiwibWFjIjoiMzllYmJiNzVhMGQyMjZmMzAxYjliMjYzMmUwMmI2YmViNGMwMGQ5YmIzMzA5MmRiOTRhY2I1MTdlNzU2ZGIzOCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: islamia_ict\nProfile: 30 Mb Star\nService: any\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(50,'2','user','Shofiq','Not provided',NULL,'buroacc','buroacc','eyJpdiI6Ilpxd0R5V3NlalNHYUIvRWR0TjlzSWc9PSIsInZhbHVlIjoiL3NCbHd3MHJPVTBzSG5uVXZEbFZWUT09IiwibWFjIjoiODc1YTI3ZmYyNDhjMjQyNDcyOTNlNTdjZTNmZjlhNTRmMzFlNTliMmM3ODM2NTA3ZTFmOTkwNDdjYWM5N2VhMSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: buroacc\nProfile: 30 MB Lgedks\nService: any\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(51,'2','user','educationxen','Not provided',NULL,'educationxen','educationxen','eyJpdiI6ImdSeTFmTzNZcTliSFowK1lpbnZ4d3c9PSIsInZhbHVlIjoiMDd6d05PMXBPWTJsLzhtenErUlMrQT09IiwibWFjIjoiNjFjZmY0Njg4ZjBmMjRkMTczY2IxN2Q5ZjAxODAzNzVmYjUwNTEzNmRiMjFiOWY5NDIyZTEwZGFkZDdiNGY1OSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: educationxen\nProfile: 30 MB govt_college\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(52,'2','user','073waltonp','Not provided',NULL,'073waltonp','073waltonp','eyJpdiI6ImF6Ymd2U1J3TGZVQ3ViMFRZZTN6Smc9PSIsInZhbHVlIjoiY0xYWTNQRWtnVGxQYWVlb1FJd2NNdz09IiwibWFjIjoiNTc5N2U5ZmM4Mjg3NTljZDVlZTdhYzQ0OWEyZDFhZjBjNTNmYjI0NmRmOWNiNWEyOTc1ZDEzZjc2OTUzODcyNCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: 073waltonp\nProfile: 30 MB govt_college\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(53,'2','user','dristy','Not provided',NULL,'dristy','dristy','eyJpdiI6IjlPQklxdkNaWUlpYlJFUTRIZXNEWmc9PSIsInZhbHVlIjoiN1FhcUthaVVSL0grWS9lOTRIZWd5dz09IiwibWFjIjoiNGJiNmRhMTVlMTMxZGUxN2ZhNTI4YzhmNmU0YzA4MjE1NWViOGZkMDEzOTA3ODhlZTRiZmZmNGE1NDFhMTllNiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: dristy\nProfile: 30 Mb Star\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(54,'2','user','Islamia_ITC_LAB','Not provided',NULL,'Islamia_ITC_LAB','Islamia_ITC_LAB','eyJpdiI6IncvSDV2YWR3SWFFK29sbGVDeFM2ZEE9PSIsInZhbHVlIjoibWNvUmlQTms0dkNTL2Q3Z2NqRWRJUT09IiwibWFjIjoiMTdhYmEwY2I4NzliOTBmZjY4NWU5YzRhNDIxNGZmZTkwYThkMjBlMTJmNGJiZmQ1NTUyZjhmMzc2OGFkNmQ1YSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: Islamia_ITC_LAB\nProfile: 30 MB Saifulkst\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(55,'2','user','ics_history','Not provided',NULL,'ics_history','ics_history','eyJpdiI6ImxYMFJlZFFET0I1Z0t2ZWVFbXJsa1E9PSIsInZhbHVlIjoiQTRuYnNwZi9qYm5wRVJObFZtTWNHQT09IiwibWFjIjoiYzU0Y2MxMWExNjc4NGI5YjViZmNlMGNlNjk5NWYxNWRkNTVkY2JkN2Q2YjFhZTM2MzgzZmM5MjgwMDUzODEzOSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: ics_history\nProfile: 30 MB Saifulkst\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(56,'2','user','kawserprint','Not provided',NULL,'kawserprint','kawserprint','eyJpdiI6Inp6ZWViaHdnOHFUcktFUm5yUjFzNHc9PSIsInZhbHVlIjoiUWI1Yml3cHUwYmliMnIyTzFqekRyUT09IiwibWFjIjoiMWIwMDRlMDVjZjM3ZjYyMWQzYjQ4YWU1OTEwNDlmNzU1NGEyOTc2MmZjMDRmNjE0ZTRkNDE1NzkxMDc4NWMwMSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: kawserprint\nProfile: 30 MB shena_nir\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(57,'2','user','babul_fyt','Not provided',NULL,'babul_fyt','babul_fyt','eyJpdiI6ImgxMDBOMnAzSHdJeW9XVE52QXFpc3c9PSIsInZhbHVlIjoiWFpzUlAyTGZqQTBvTytRbzc0TUNFdz09IiwibWFjIjoiYjI5YWMyZDk4MmVhMjdiNTBlYWJhNGM5M2VlYzhjYzJmZGMwY2EzY2ExZWViYmVlMDgzMmU0ZDhjMWQyMzE2MiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: babul_fyt\nProfile: 30 Mb Star\nService: any\nRouter comment: none\n\nImported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:19\nConnection ID: babul_fyt\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:19'),
(58,'2','user','fulbabu','Not provided',NULL,'fulbabu','fulbabu','eyJpdiI6IkFLUGgrOG11NXFYUXNNZVMyMHduakE9PSIsInZhbHVlIjoiVGZtc08xbTZRUjRBRko3OG1xMDl0Zz09IiwibWFjIjoiMzE5ZTRhNmY4MDk4NDFiOWU0MDY0ZTcwY2QyMzIwMTE0ZjIzYzRlYjE5N2UzNWJkMjEwOWI4ZDA4ZTlhZGUyZiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: fulbabu\nProfile: 30 Mb_Travelshouse\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(59,'2','user','Shofiq','Not provided',NULL,'joardderbtc','joardderbtc','eyJpdiI6ImJSOEUyd2JBdlVGVUFJZ1VaeFF3dVE9PSIsInZhbHVlIjoiVlF4dkRWMHF6NElvbTIwL3Y0NlhGZz09IiwibWFjIjoiNThkYzgzNWJlY2FiZmRkYWQzZjc5MjE4ZWE3MjNiMGUxNTMyNTU4OTcwYjc1YTk4Y2JkNWEyMjllZjkxOWNkNyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: joardderbtc\nProfile: 30 Mb Star\nService: any\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(60,'2','user','fishoff','Not provided',NULL,'fishoff','fishoff','eyJpdiI6ImRMU3VpbkIyeTRtaytmaTE5RlI2dFE9PSIsInZhbHVlIjoibTFST1YzUzMrMHNHcldjdFFZWjMyZz09IiwibWFjIjoiOGJmOTIyNTZjMGMwMzNiMmI5NjgzMzI5MzkxY2NlMmUwZTk0OTk0YzkxNTdlN2I2YjljOGM3ODlhYTVmMDdjZiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: fishoff\nProfile: 30 Mb_Travelshouse\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(61,'2','user','kbmahidi','Not provided',NULL,'kbmahidi','kbmahidi','eyJpdiI6IlRMWkM4UWYzaUV3cWs3NUpnaTYyU0E9PSIsInZhbHVlIjoiak5rcTd2VGVGQ041OHlQWWlDNjhYdz09IiwibWFjIjoiODM4MjI3YTUzMDIxOWYwM2FlMTA0Yjc0MDAxM2M5MDA0ZjkyODAzMTE0NDY4ODUwZGIzNGVmMmYwM2EzNDgwNSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: kbmahidi\nProfile: 30 Mb_Travelshouse\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(62,'2','user','ful_jaman','Not provided',NULL,'ful_jaman','ful_jaman','eyJpdiI6IkpVL2laVFg5S044UTM3OEgzTVpFdkE9PSIsInZhbHVlIjoiVWYxRXF2T2dzQUo1U1RObEJqdG9QUT09IiwibWFjIjoiYTA5NTU2YTc1OTgyMGUxNzMyOTM2YTE5OWI3MGFhZmIzZDU4ZDUxOWU3ZDc2YjE2N2NhYjhjNGMzYzdjZWExMSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: ful_jaman\nProfile: 30 MB Saifulkst\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(63,'2','user','buro1','Not provided',NULL,'buro1','buro1','eyJpdiI6IkFBQUJzcisrSXhIbEoyTEtHQTFuU3c9PSIsInZhbHVlIjoiTFVHMHRibXJjWkdtZmpCcFpaaUxjdz09IiwibWFjIjoiMmMzNTNmNWY0MDYwZTY2ZGU2NTBhMjQ5OWViMTY4OTQ0NmRlNDJhZGQzOGJhMGJiOWEyMzI1MGQyYTYzNjI4ZiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: buro1\nProfile: 30 MB govt_college\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(64,'2','user','hobyvai','Not provided',NULL,'hobyvai','hobyvai','eyJpdiI6Ik5QMWxXaUxEbEtYWUMxQU5NNW1xb0E9PSIsInZhbHVlIjoiSmJZSDREYURzdEEzdjMrd0VMNmc5QT09IiwibWFjIjoiYjRmMWRlZDkyNjM5NTczMTk2MDlkYjk1NmJjZDI5MjQ2YmUxMmEwYjUyOTcwYzQ4ZmY2ZDZhZmYzNWU5NTBiNCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: hobyvai\nProfile: 30 MB govt_college\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(65,'2','user','Anike','Not provided',NULL,'amin_mobile_salim','amin_mobile_salim','eyJpdiI6InBlcEl2UUVJdVlQQmxxTVN3a05nSHc9PSIsInZhbHVlIjoiYlpkcS83N2dhODN1eHBwVVNraFhVZz09IiwibWFjIjoiZWNhNTQzNmNiZGU2YzNmMGQzZTVhNDg3NjU0OTQ1MWUzZTMyYTZjMzE3OWE4NTkwMTZlNjFhNDY3MGNkOWJlNSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: amin_mobile_salim\nProfile: 50 Mb_Travelshouse\nService: pppoe\nRouter comment: Anike','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(66,'2','user','ful_sona_salim','Not provided',NULL,'ful_sona_salim','ful_sona_salim','eyJpdiI6Illob2hnZUZtL2o1REdCVlcwTFFrYWc9PSIsInZhbHVlIjoiZGNCeFpRR1lsaWJ1b0RKK0NVc2d5Zz09IiwibWFjIjoiMzdmNWMzNTNhNjk4OTNlNWRmMWE2M2RhYjFlODk0Mzc0ZGNhMTMwMWRkMjdiN2NlYWJkNTQ4NzA1NWNlNmM4NSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: ful_sona_salim\nProfile: 30 MB govt_college\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(67,'2','user','Anike','Not provided',NULL,'ab_rokibul','ab_rokibul','eyJpdiI6Ii9hTHJYL0xIdk14UTkwbGdCT2E1cVE9PSIsInZhbHVlIjoiZFlBOUJQUTF1QURybjErZjZPYU1rUT09IiwibWFjIjoiMGMwYWM2ODQwY2ZjYzZlNDc2Nzk4NTZmNTcxNzg3ZDhhYzk5NGNmNmQyYjA2YzBiMDQ3YTBhYTk3YjlkZjdkZSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: ab_rokibul\nProfile: 30 MB govt_college\nService: any\nRouter comment: Anike','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(68,'2','user','dokan_jaman','Not provided',NULL,'dokan_jaman','dokan_jaman','eyJpdiI6IlVSRmFhcHJ4bUljc3NRandTQW91bHc9PSIsInZhbHVlIjoiQlVTaC9VTFlkNEVyS0w4VjBIRE9pUT09IiwibWFjIjoiNGI2MGE2NzM4OGU1OTUyODRkMzVjMTM3YmQyZTI2NDBlOWYxZmE2MDVlN2UxODkyYjBjOTA0ZjNlOGQ0OTgxNyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: dokan_jaman\nProfile: 30 MB govt_college\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(69,'2','user','buro_home','Not provided',NULL,'buro_home','buro_home','eyJpdiI6IlhINk1sQS9XM0hrdUpkdHpJRW5pWmc9PSIsInZhbHVlIjoidUtJNFRYOGY3QXVEY25SWHNORUJsUT09IiwibWFjIjoiZTJkY2YyMDRiYWJjZTAwM2E5ZGVkNTYwYzYxOGE1Y2NjODc0YWM5OGRmNTE3M2I5ZjRkYzBjNDNlMTdmNGFhMCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: buro_home\nProfile: 30 MB govt_college\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(70,'2','user','alomgirkst','Not provided',NULL,'alomgirkst','alomgirkst','eyJpdiI6ImRPU05WUXVXazF4dE9MQmZXS1pva1E9PSIsInZhbHVlIjoiaE9WakZHSlVEMWF1QVR5TGZLUWxWdz09IiwibWFjIjoiODY4NDM0YzRmNDI4YzlkNGM2ZGY1ZTFiNmUxMzhjNTNhM2Y4M2RhNjk3MTU2N2IwZWI1OGM5OWY4Y2VlZTZmZCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: alomgirkst\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(71,'2','user','111','Not provided',NULL,'111','111','eyJpdiI6IkJpbENOa3paNGlBR1BNYTRITSs4bmc9PSIsInZhbHVlIjoiLzBrTk5pblJGYjMrUnFTWUdTSmJIUT09IiwibWFjIjoiYjliYTZlMzBmMzg5NTZlNDljZDEyN2QxNGI5YjUwZTUzNjg5YTcxYWQzZjJmZjBjOWQyMDBiYjNjOWZlMjRiNiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: 111\nProfile: 110 MB 141ranvid\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(72,'2','user','customs_rokon','Not provided',NULL,'customs_rokon','customs_rokon','eyJpdiI6IkdBQUkwYWxzc3p5TWZ4Q1BrY3l2UUE9PSIsInZhbHVlIjoiMVFZbGJnNWJNazFqQXJkU1FWN0hXQT09IiwibWFjIjoiODljMzJkYTM5YzFlNGNiZjE5MTA4YzA3YjA4MjhhMmU2ZDNmZDE4MGMyNzNmZjk2ZWVmODgxNzhjNTA5MTRhOCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: customs_rokon\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(73,'2','user','hajotkhana','Not provided',NULL,'hajotkhana','hajotkhana','eyJpdiI6IkwyOEpvR3VtMDBCeFRMcjhyQllXcmc9PSIsInZhbHVlIjoib0JwdmVQRzdHZjNyMjVQUmp2Z20vUT09IiwibWFjIjoiOTIxYjFmMTlkOTRlYzE2NTkxYTM5MmJiMjUxOGE3NjAyZDZkZGEyZTM3Mzk3ZmE1YWE2ZjgxZjBmNTc3ZmI2OSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: hajotkhana\nProfile: 50 MB shena_nir\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(74,'2','user','junnun_shena','Not provided',NULL,'junnun_shena','junnun_shena','eyJpdiI6ImhUcmI5dHU2SEJMeTl6MXBYcWZaa1E9PSIsInZhbHVlIjoiaXByRjJJbHRsVU5vZU9KVmxyOFVWZz09IiwibWFjIjoiYzA3NGUyZGY2NDYyNmRkOTI2ZjAwMTMwYTgyOGE0MTFiMjM0MmY3NzI5ZDc0MzNjZmFlYWE2YzExZmU3ZjkyMiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: junnun_shena\nProfile: 30 MB shena_nir\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(75,'2','user','habibur_shena','Not provided',NULL,'habibur_shena','habibur_shena','eyJpdiI6IkpoYnpCdTZJblZNWmNEaTBLTVovK2c9PSIsInZhbHVlIjoibnhZd2RSdzB3RFRJbUxmZlNnN2xTZz09IiwibWFjIjoiNGQ1OWE0ZWRhNTYxNTk2ODE1NTBlNjI0NGMxZGRlY2NjY2RiNTUzMDk1MzQ5MmQ2MGE0ZjhkZTlmZGFhMGM4NCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: habibur_shena\nProfile: 30 MB shena_nir\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(76,'2','user','hamim_shena','Not provided',NULL,'hamim_shena','hamim_shena','eyJpdiI6IlplWWhrcFNBNkxsYnRBTHNzbWdlRlE9PSIsInZhbHVlIjoiazZSRzZPd1FUWVNsT3k0K21rdVNzZz09IiwibWFjIjoiY2JjNzllMDVkMmFhYzI5NmY3ODcwYzEyODBmMTcxNmFlYTcxOTAxYTNiNTZmMTExNWYyZmY4YzZhNDdiZDkxOCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: hamim_shena\nProfile: 30 MB shena_nir\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(77,'2','user','joshimvai','Not provided',NULL,'joshimvai','joshimvai','eyJpdiI6IkVLMDRla3Z6VWNIN2d6UmlJSTNtRXc9PSIsInZhbHVlIjoiK3ZvNERxa3dvbFpOWWpNOXBPd3FOUT09IiwibWFjIjoiZmMxYjI3NzcxZTdmNzE0NGMyZmZjZmExYmQ1MTk1NjM5M2E0MmZkM2U2NWY1YjQ5Y2MyZDM4NmFmNzY0NzQ0YyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: joshimvai\nProfile: 30 MB shena_nir\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(78,'2','user','alomgirwdb','Not provided',NULL,'alomgirwdb','alomgirwdb','eyJpdiI6InRJa1dRdVZPNElMcVYvSWlPMEg3L1E9PSIsInZhbHVlIjoidHZSYmFlU21PQVhpOVBWZE1OK3ZVdz09IiwibWFjIjoiMDViMmQ1MzIwYjRmZThlNGI2Y2Y0Y2JkZDI1ZTJlZTBlM2JkZDRiODY2MDE3MmU3ODI4YTM2YWNhOWIzN2FiMyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: alomgirwdb\nProfile: 30 MB shena_nir\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(79,'2','user','Shofiq','Not provided',NULL,'CID25058','CID25058','eyJpdiI6ImFFaCtIZzB2RVMreHROb1Y0QmJ3Z2c9PSIsInZhbHVlIjoiUUVYZThkZ2lXSUxQK0FKWENFQlRydz09IiwibWFjIjoiM2U4ZWUxZDJiMDIxMzU0ODg0NDNhYTVhNDVhOWE4MGI0OGE3M2M3ODg0MzQ0ZDBmNWRmOTdiZWM3NjJmYzYzMiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: CID25058\nProfile: 30 Mb_Travelshouse\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(80,'2','user','Shofiq','Not provided',NULL,'fine_fit','fine_fit','eyJpdiI6IlBENzlBRWE1YWNURGtzeDZKS1lWc1E9PSIsInZhbHVlIjoiUXZpVHoxU2F2YmxpbG1uOEN1NTB3Zz09IiwibWFjIjoiN2UyMzUyOTA4NWEzNDg4YTdhZDE0NWEyODk3NzBhNTU4MTViZTJmOTkwM2ViYTdjMDIzOTM0MGMyYWNjZDkyMCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: fine_fit\nProfile: 110 MB 141ranvid\nService: any\nRouter comment: Shofiq','inactive',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(81,'2','user','boro_amirul','Not provided',NULL,'boro_amirul','boro_amirul','eyJpdiI6InVlNWQ3K0F1UmZhbFAvSkFZcWhCQ2c9PSIsInZhbHVlIjoiQ3B4YnVFbmRlTW4zeXZ5MHFTTmxidz09IiwibWFjIjoiMzMxZWZmZmY0MzE1NTMzZjI3NTE2MjE3ODczMzc4YjBiZGMxYTMzNGNlNDZkYjgxMmM4ODI1YWEzYjU0Mjk4OSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: boro_amirul\nProfile: 110 MB 141ranvid\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(82,'2','user','almahdi_trd','Not provided',NULL,'almahdi_trd','almahdi_trd','eyJpdiI6InJGSUZsNTF1UnV5dVNHNSsyRHVFL2c9PSIsInZhbHVlIjoiMG84aTAvcU5VUk5BN3RZNTJ6R0lNQT09IiwibWFjIjoiNTIyYzVmZmExZDg4ZTljZDI1ZDYxZThhODlkMjNiNTc0YjEwMGIxY2JjZjg5ODQ5MDgxZjFlNDliNjQxNTZmYSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: almahdi_trd\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(83,'2','user','jony_oly_vi','Not provided',NULL,'jony_oly_vi','jony_oly_vi','eyJpdiI6IlNXU1ZHY0hNbnEvUHBjalZiSmFJYXc9PSIsInZhbHVlIjoiSllCc1VMTU9kNWtTa1Y0c0FlQ0F6Zz09IiwibWFjIjoiYzE5MzFlMTlkYjI5NmMyYzA3YjUyMDEwY2FiYTY1OThkZjU2NzQ5YjQ1OWJkMGNmYmU0NWE4OTk0NjMyMGY5ZCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: jony_oly_vi\nProfile: 50 Mb_Travelshouse\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(84,'2','user','jim_amirul_vi','Not provided',NULL,'jim_amirul_vi','jim_amirul_vi','eyJpdiI6IjU0M3EwZGJ4dUhjK0FkaXRSZklaQnc9PSIsInZhbHVlIjoid2JIdmJ5bjlqM1BiTG9pL1RHL3d3Zz09IiwibWFjIjoiNTBiNzFjNGI5ZmEzNjIxYjI0YmMxMmI2MjUyYzFmNGE1NDNkOWE4MGJkYTJlNGRjYjNiMzM2MjQ4NzY3MTU0MyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: jim_amirul_vi\nProfile: 50 MB KPI\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(85,'2','user','anupomcomputer','Not provided',NULL,'anupomcomputer','anupomcomputer','eyJpdiI6IlFORlQvdmxwN3Z5YzJPRklRdWMyUEE9PSIsInZhbHVlIjoiOHJIMTN3OXA3MWV5amFpRUxPWlBCdz09IiwibWFjIjoiN2VjZDljYTY5ZmU0NGQ3ZmQ3ZGI1MDk4YzZjZjcwZjNkZDI2MTE5MjIyNDUyN2RjZWUyNDZiYzQ3NzAxOTg5OSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: anupomcomputer\nProfile: 50 MB shena_nir\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(86,'2','user','dhrubotara','Not provided',NULL,'dhrubotara','dhrubotara','eyJpdiI6Ik0xVU9PY29vWlRpVTRUWmxpeHJENnc9PSIsInZhbHVlIjoia3FUMmNpQzJuU1ZYbmFRbHdCRDM1Zz09IiwibWFjIjoiM2QzYjUwNTdlYWJiNzliZTNjY2QxOWIzYmQxY2QwMjllN2UwMDFlN2U2NTFhMTU2MmU4NDQyOTFmODY5OTJlZCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: dhrubotara\nProfile: 30 MB shena_nir\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(87,'2','user','bortoman_bari','Not provided',NULL,'bortoman_bari','bortoman_bari','eyJpdiI6IkJON0xjdjlseUZGaVkvOW5OYjBRd3c9PSIsInZhbHVlIjoiVThTeFdzYXpKY3NGVlpWUjJhM1lkdz09IiwibWFjIjoiZWY2OTI3YzJjM2YxN2I2ZDI5ZTc0MTU3ZmE5ZjVhOGE5MTc5NDcwZDY0YTQ2OTAwYjVhODFiZTgyZWQ2NTFiNSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: bortoman_bari\nProfile: 30 MB govt_college\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(88,'2','user','getcopop','Not provided',NULL,'getcopop','getcopop','eyJpdiI6IlJSa0krQnJBcTJPNHhqK3RDZUhPdEE9PSIsInZhbHVlIjoiUFhiL2xMVktPcUlVaWg0WktseXlLUT09IiwibWFjIjoiMzU3YmNjZmU3YzZiN2Y4NjgxMDk0ZjdhZGE4ZmJhNTg3MGJlNmRhNjgyZGY0Mzg3NDc4MGU4NWFiOTA3MDU0NSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: getcopop\nProfile: 50 MB shena_nir\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(89,'2','user','bisuvai','Not provided',NULL,'bisuvai','bisuvai','eyJpdiI6IkNkeTFvaURobDBVdHc4bXJWSmgwRmc9PSIsInZhbHVlIjoiK2xRVklFSk9yK1orT2p4cG9PMlBrdz09IiwibWFjIjoiMzBiNWJmMDEyY2VlMTY4M2VlZGQ5MmRhYTkwOGQ1ODA5NmIyNWYzZmI3MjZhNmM0YTg4ZjU2NDY0NGIwMTg3MyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\nConnection ID: bisuvai\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:18','2026-08-10 23:08:18'),
(90,'2','user','imranber','Not provided',NULL,'imranber','imranber','eyJpdiI6Im1aY1o3VHJ2VFptWjdaZWFLUERpV3c9PSIsInZhbHVlIjoicXdpV1VlUDJINFNvMXNuenNWMlVlZz09IiwibWFjIjoiMWMxOTQ1ZDEwYTNlNjU3ODg3MmRmZWI1M2IzMDk1YTk1YjQ2ZWM3NmFmMTdkN2ZlZjc4ZDZkNWY3ZjkyZGQ5YyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:19\nConnection ID: imranber\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:19','2026-08-10 23:08:19'),
(91,'2','user','borhan_bermij','Not provided',NULL,'borhan_bermij','borhan_bermij','eyJpdiI6InZsMmxXZjZFVWw3cTViK2d1VTMwVXc9PSIsInZhbHVlIjoiM0JRYjYxUms1Um5CN2VsK2hlaWVCQT09IiwibWFjIjoiY2I1MDhmN2NjMmU4NjJiZWM0YWIwMTJhMDU2MmE0NDIzMDJmNTc2M2JjZTU3MTdmNDZhY2MxZTRmOTEwMGU5NiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:19\nConnection ID: borhan_bermij\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:19','2026-08-10 23:08:19'),
(92,'2','user','jayanta_berj','Not provided',NULL,'jayanta_berj','jayanta_berj','eyJpdiI6Imp3bUk5bXRuZnI4aXFvSEc4QlVGWFE9PSIsInZhbHVlIjoia2tWZERUbThvMFV1UUpZMzMyclJsZz09IiwibWFjIjoiNmVmOWQ5NzIwYzI1OTFjZDYzZTEyMGIwZDZjNmU0YTVkNDgwOWFmNjc3ODE5ODRhOTE2ZDNiZTk4ODVlYzA3NiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:19\nConnection ID: jayanta_berj\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:19','2026-08-10 23:08:19'),
(93,'2','user','bobita_basha','Not provided',NULL,'bobita_basha','bobita_basha','eyJpdiI6Im0xNWJCdUpTSHZLbFZsM1JlVmVTQlE9PSIsInZhbHVlIjoiMEtjQ0liYWlrQkJhQ1p2WDdBNGo4UT09IiwibWFjIjoiMTZhNmI0NjM4Zjg1YWY3ZDVkYzJmOTcyYTFkYzY4NjM0NTY4OTg0NWYzZDRmNWFjYzBkODcxMTRkN2QxYjE4MSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:19\nConnection ID: bobita_basha\nProfile: 30 MB Lgedks\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:19','2026-08-10 23:08:19'),
(94,'2','user','aktervai_kb','Not provided',NULL,'aktervai_kb','aktervai_kb','eyJpdiI6IkxwUEhQRlZmd3Nwb0U2NmpJbEtTK2c9PSIsInZhbHVlIjoiT3ZNYWE1TC96Z0dxYW9SdzIweGpYZz09IiwibWFjIjoiOWZlZWQ1OTIxZmVkNzUxZTlhMTExYzE0YTRlYjA1OGJlNmYxNWFhZmNjMDE4ZWQ3MTkyYzcxNjBmZWM3Njc3MiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:19\nConnection ID: aktervai_kb\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:19','2026-08-10 23:08:19'),
(95,'2','user','joy_bridge','Not provided',NULL,'joy_bridge','joy_bridge','eyJpdiI6ImdKQXJEK0xFVC9Oclo0Znp1bXVqN3c9PSIsInZhbHVlIjoiK2pyVjNpTERudG1NSGNoS0doN0lkQT09IiwibWFjIjoiNmUzNWI4OThiM2JlOTE3MWEzODEwMDQ5YTVhMGZiOTA5Mzc1YTFjZDg3MDhkNTE5MzJkYWU2ZjkxYmI2N2IzNCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:19\nConnection ID: joy_bridge\nProfile: 50 MB mosharof_bgoly\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:19','2026-08-10 23:08:19'),
(96,'2','user','jony_bridge','Not provided',NULL,'jony_bridge','jony_bridge','eyJpdiI6Ik9tSkxSelE0c0dVN2dna1ZsMFVxQUE9PSIsInZhbHVlIjoiU002K2w0NkxBRlRqeXBVams0OWRGdz09IiwibWFjIjoiZTQ3NmQyOTU5ZjNmMzZiMTZkMDcwZWNmYTFmYzU5YTU0Zjg4NTk4ZWMwNThhNzU4OTJiNzU0OWJmY2VlNmU2YSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:19\nConnection ID: jony_bridge\nProfile: 50 MB mosharof_bgoly\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:19','2026-08-10 23:08:19'),
(97,'2','user','abhamid','Not provided',NULL,'abhamid','abhamid','eyJpdiI6Ikw2TGlBcmxkVlRPcXJmZ09oZXhydlE9PSIsInZhbHVlIjoic2NHNTNFRjM1UXQ5NHlEa1NZMXNaQT09IiwibWFjIjoiYzU1MDhlMmU3OThhNjAxMjdhYWFmM2RmY2YzMzJiNzEwOTAxMThiNDVkNTRkNzFiMzQ3NWRhM2RlMzUxYzBiMCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:19\nConnection ID: abhamid\nProfile: 50 MB mosharof_bgoly\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:19','2026-08-10 23:08:19'),
(98,'2','user','jahidvai_basha','Not provided',NULL,'jahidvai_basha','jahidvai_basha','eyJpdiI6IjVYbkM2YlltTVU1cUFrVDM0TGNNdHc9PSIsInZhbHVlIjoiclJ0M1lHSUJZQ2gzeHZHNUJ3eTNnUT09IiwibWFjIjoiNWY2MmQ5MThhZDYwOWM4ZjAzNTg0ODNiODU2M2QwYjdmMWViODYzOGNmNDJhMDAxOWZiOGUxNGRiNGM1MWY3NCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:19\nConnection ID: jahidvai_basha\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:19','2026-08-10 23:08:19'),
(99,'2','user','alamin_basha','Not provided',NULL,'alamin_basha','alamin_basha','eyJpdiI6IjNxR3NLVVBLVDFhNTQzTndGdzZVOEE9PSIsInZhbHVlIjoid3VBVUEyYVJkOTV6RVF6YjdZUzNSUT09IiwibWFjIjoiYzIwNDUxNzA1OGU4Njk0N2RmNzMzMDlhNzY5ODI0ZWUzYTg3NWIxMmQ0NjU1ODA2ZDkxMjAzZDE0ZDVmOTQ1ZiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:19\nConnection ID: alamin_basha\nProfile: 50 MB mosharof_bgoly\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:19','2026-08-10 23:08:19'),
(100,'2','user','Anike','Not provided',NULL,'lipu','lipu','eyJpdiI6IlA4VGRod0xTZ1RFYlpMQjlXNktqb1E9PSIsInZhbHVlIjoiNlVlMjNvaXYzMEluV212RW13bUx3QT09IiwibWFjIjoiNzBjYWE3YTcyODAzMjEyNjE5MWU0YzllNzU4NTk5ZTg0NWI4MmMzMjNlOTM1MzJjZWEwYjQzZDM1NzYzZDczNCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:54\nConnection ID: lipu\nProfile: 30 MB govt_college\nService: any\nRouter comment: Anike','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:54','2026-08-10 23:08:54'),
(101,'2','user','Anike','Not provided',NULL,'nabil','nabil','eyJpdiI6IlFKc0dvQjFZb1Y5Smg5c0hvMFVaMXc9PSIsInZhbHVlIjoiNmpRWjFNQ0JPMkRyL3c2aUtmWEdhdz09IiwibWFjIjoiMjI5NDA5MjI0MjEyYzZlN2MwN2VmZWEyNDczMDExOGY1MDRjNjcwN2I5NDJjZWQ2Y2M3ZWZkY2FlZjkyNzFjMCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:54\nConnection ID: nabil\nProfile: 30 MB govt_college\nService: any\nRouter comment: Anike','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:54','2026-08-10 23:08:54'),
(102,'2','user','Anike','Not provided',NULL,'koyel_vi','koyel_vi','eyJpdiI6ImtTWStFQnFXcUJNMGJ5M1BoMVk1SFE9PSIsInZhbHVlIjoiZllBbVJiYlhJZnBRRE52dXlRSGNHQT09IiwibWFjIjoiZTgxMGMyNDM0MjkwOWZhZDg3ODEwYzlkOTM5M2UyMWRmZDMxNDMwOTU1N2Y5Mzc3MTg0NzlkMmU0ODZiNWZmYiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:54\nConnection ID: koyel_vi\nProfile: 30 MB 141ranvid\nService: any\nRouter comment: Anike','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:54','2026-08-10 23:08:54'),
(103,'2','user','Anike','Not provided',NULL,'kollol_home','kollol_home','eyJpdiI6ImpwV0lyTmJsb2VRMnNWdTAybWxVb2c9PSIsInZhbHVlIjoiM1FtRHE1REN0V1kyQzJhZEJzbi9vdz09IiwibWFjIjoiYmM0NTRlNjcyZWYzZjBkMTc5MTYxNzcyOThmYjIzY2I3NWI2MzczODU3Y2M2NGNkOWNhMzdhOTI0ZTI2MGRiOSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:54\nConnection ID: kollol_home\nProfile: 100Mb_kpi_comdpt\nService: any\nRouter comment: Anike','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:54','2026-08-10 23:08:54'),
(104,'2','user','Anike','Not provided',NULL,'knb_gm','knb_gm','eyJpdiI6IlhYRHBzY05WeGFla2QwWUl1N3FaL0E9PSIsInZhbHVlIjoiSDFFamNEM1hGQUtZNkplMnhBYmNEdz09IiwibWFjIjoiM2NjOGMzYWJhZGMyNWU2YzgwZmU2N2ExMjM3YjgyMmQxZjc4MWFkNmI2YjRiY2U5ZTRkYmZmOWQxN2Y0NDJlYSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:54\nConnection ID: knb_gm\nProfile: 30 MB 180 IP\nService: any\nRouter comment: Anike','inactive',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:54','2026-08-10 23:08:54'),
(105,'2','user','Police','Not provided',NULL,'police_asp_admin','police_asp_admin','eyJpdiI6IjZLc2RlVGlPbExGRXBLWlZEL2I5U0E9PSIsInZhbHVlIjoiYldVSzBYVzk4cThuY0IrN0FLUDV3dz09IiwibWFjIjoiYWExZTMxY2EwNGU5MDM1YmMyMDk1YzUzZjJmNzRiMjBiNmRkNDdhMzJhMzdmNDc4NTYxNDI4Zjk3YjkxM2NiNSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:54\nConnection ID: police_asp_admin\nProfile: 30 MB 141ranvid\nService: any\nRouter comment: Police','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:54','2026-08-10 23:08:54'),
(106,'2','user','Police','Not provided',NULL,'police_asp_crime','police_asp_crime','eyJpdiI6InB6cytXUW5remxZdnV6SllCTWxWVFE9PSIsInZhbHVlIjoicVRqVWlyb2FqTzl4Qkw5YzJ2ZmQzQT09IiwibWFjIjoiNWEwZjc4NzgyZjRmNDExZGEzNjFmOTRlMzFjODkzZmUwMzZhODI1MTFlODMzNTcyZDYxNDY1NDk1ZmUwZGQxOSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:54\nConnection ID: police_asp_crime\nProfile: 30 MB 141ranvid\nService: any\nRouter comment: Police','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:54','2026-08-10 23:08:54'),
(107,'2','user','Police','Not provided',NULL,'police_asp_dsb','police_asp_dsb','eyJpdiI6IkF6OUdBeW9iN0tGc3pocDFaTlBiOXc9PSIsInZhbHVlIjoiai9Rd2t3blp1NDFOa3pLV1o2ZzdMZz09IiwibWFjIjoiYjM3NWQ2N2JkOGUzMmRmMzE1MWRlNjRkZTUxMzFhMWU2YzU4MDMwYjY0MTUwOWIzMmM4NDY2OTViMDUxNzkwNSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:54\nConnection ID: police_asp_dsb\nProfile: 30 MB 141ranvid\nService: any\nRouter comment: Police','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:54','2026-08-10 23:08:54'),
(108,'2','user','police_ciber_crime','Not provided',NULL,'police_ciber_crime','police_ciber_crime','eyJpdiI6ImZKb2pId0xFWkMzRHRtNmd4aSs4M2c9PSIsInZhbHVlIjoiWGhmYk8wbVh3ZFEySWJESWMwMzRHdz09IiwibWFjIjoiZjc4NGZiYjY0YTBmNWFiZTc0NDhjYjg5NGQ2M2NkMTFiYWQzMTE5MWM3ZGQwYzBlMWNmZTkxMjAxZDY4ZWRmZSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:54\nConnection ID: police_ciber_crime\nProfile: 40 MB 180 IP\nService: any\nRouter comment: none','inactive',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:54','2026-08-10 23:08:54'),
(109,'2','user','police','Not provided',NULL,'police_bit','police_bit','eyJpdiI6InJsMFBOZnpXSXhFdWdENEdBR2VwT3c9PSIsInZhbHVlIjoiMUhzN1RkemxXdmdSaHhsZFZraUU1Zz09IiwibWFjIjoiNzVkNDVlOGRlYzVkOWMwMzNhOWFjNzdlNDY2YzU0YWQ3ZDhmOTQyMWFhMjQ4M2I0NGEwMzliOWI2NWI4NmZmOCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:54\nConnection ID: police_bit\nProfile: 30 MB 141ranvid\nService: any\nRouter comment: police','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:54','2026-08-10 23:08:54'),
(110,'2','user','Police','Not provided',NULL,'police_db','police_db','eyJpdiI6IlRMU3ozSzhyeGRjeDd1OTV5dThnTXc9PSIsInZhbHVlIjoiUjhtS0x0V3lPRVNKYm95S2VmdlVwUT09IiwibWFjIjoiZGQwMDg0Y2EzMDZmNjNkZThlODFiNTczM2NkNmFkMzhkNGMzODlmNTg5ZTdjOTZhMjFkMTE4MmU4NzY4Njg1YiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:54\nConnection ID: police_db\nProfile: 30 MB 141ranvid\nService: any\nRouter comment: Police','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:54','2026-08-10 23:08:54'),
(111,'2','user','Police','Not provided',NULL,'police_acc','police_acc','eyJpdiI6ImVpYitrblpadFV4VjlncUlDcVg0ZGc9PSIsInZhbHVlIjoicXN1OXBJV3Y2NjdXcmQ1d3NYekt3Zz09IiwibWFjIjoiN2EyODM3Yzk5ZmFjMzMyOGU0MzFjOWQ2MzFmYzAyYzFlYTRkN2VhMWI3NzZlOGQxZTQ3NDc4NzFiYTI0OGQyNiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:54\nConnection ID: police_acc\nProfile: 30 MB 180 IP\nService: any\nRouter comment: Police','inactive',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:54','2026-08-10 23:08:54'),
(112,'2','user','Police','Not provided',NULL,'police_armory','police_armory','eyJpdiI6IklMZnlHTTQ3QXlpdUtMYXAwdktyanc9PSIsInZhbHVlIjoiN0JDTzhCUVo2aVVHcFE4L1JQeTRPdz09IiwibWFjIjoiZjQ3Y2VlMjMzNDE2M2I4OWY3OGQ4YmI3N2Y0OTNhNzQ2OWM0NTk1MmJmMGNmYTZkMTY1YjQ3ZDQxNzY5ODdjNCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:54\nConnection ID: police_armory\nProfile: 30 MB 141ranvid\nService: any\nRouter comment: Police','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:54','2026-08-10 23:08:54'),
(113,'2','user','Police','Not provided',NULL,'police_asp_sodor','police_asp_sodor','eyJpdiI6Ik4xL3h6WW9pTjNQcElzUlZHWXAxM2c9PSIsInZhbHVlIjoiaS8xVENOVzYxb0tpNHd3eVpxaVBMQT09IiwibWFjIjoiMDU4NTlmNjI4M2RhNzNkYzAwNjU1Y2JjMzZhMzdhZDJkNzliMWQ5NmQyNWM2NTdjYTNjYzU0OGM5NTA1YWJmNyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: police_asp_sodor\nProfile: 40 MB 180 IP\nService: any\nRouter comment: Police','inactive',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(114,'2','user','police','Not provided',NULL,'police_crime','police_crime','eyJpdiI6IkhPWWw4MnRSbWFjUTBhRkN4Q2RYWGc9PSIsInZhbHVlIjoicWM0Q3RGbVEvWlo1OFBmQUt4L0ZOdz09IiwibWFjIjoiMjRkZTI0ZDJkOTUwNTA5NDk1ZTUyOGYyNDgzNDhhYTBlNWNmMGZmZGZhMzBkOGQzYTQ0NWYzNGU4OGRjMDhlNSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: police_crime\nProfile: 30 MB 141ranvid\nService: any\nRouter comment: police','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(115,'2','user','Police','Not provided',NULL,'police_cyber_crime','police_cyber_crime','eyJpdiI6IlFUK0UrZTI5TitWcDI5ZDhCSEdWd0E9PSIsInZhbHVlIjoiK1NVbWlkTmlpNVhnQU1jT1gzcDVwQT09IiwibWFjIjoiNTMzMmMyYzBiZTdkNjdlY2EwMTE0MzExMmI3ODE2ZDAwNjZmNjg3ZDkzNmI0NjkwOTIwYTI0Y2QxMDBiMzI2MyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: police_cyber_crime\nProfile: 30 MB 141ranvid\nService: any\nRouter comment: Police','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(116,'2','user','Police','Not provided',NULL,'police_asp_crime_1','police_asp_crime_1','eyJpdiI6Ilk1OGVKcFpjWVp5VDlYd1EvSmV0QVE9PSIsInZhbHVlIjoiSjBSTHpnakdjMjdETGlDZTkrS1EyUT09IiwibWFjIjoiOGQ0NjQxYjg2YjNlYTZmMWY1MGZlODk4OTljNzg5NzJmOTBkYjFkNThiNDU4YTJkOWMzOTBiNDA4Njc3MGZkNSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: police_asp_crime_1\nProfile: 30 MB 141ranvid\nService: any\nRouter comment: Police','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(117,'2','user','Police','Not provided',NULL,'police_d_store','police_d_store','eyJpdiI6ImFmdnVORjJuS3dvb1pQU2NVbkhuWEE9PSIsInZhbHVlIjoiTDUvOVRZbU9oblBDaDltdGsrZ3g0Zz09IiwibWFjIjoiMzUwNGYzZjg0MDEwODQ5OTI3MjkyYThjMmY1NjUzZmM5YmE4NmE5NzVjZDNlYTdjMGJlYTIwMjRjZTE5MzczYyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: police_d_store\nProfile: 30 MB 141ranvid\nService: any\nRouter comment: Police','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(118,'2','user','Police','Not provided',NULL,'police_asp_home','police_asp_home','eyJpdiI6InNIRkR6THhRbzM1Wm5wN2VqSno5MEE9PSIsInZhbHVlIjoiT1lhY25TVnZMV0xvQ01YcG9vakY1Zz09IiwibWFjIjoiNGVjNmVlYjA5ZmRkZWJmYjczNTI5MGQ4Y2VlN2Q3MTFlYzJiMmUwMGIxZWNiMjFjNTlhNjMxOGI2NGYxYWI2OSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: police_asp_home\nProfile: 40 MB 180 IP\nService: any\nRouter comment: Police','inactive',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(119,'2','user','Police','Not provided',NULL,'police_cloth','police_cloth','eyJpdiI6ImRQOE1KMWsyWFlsYUJ6V2xLeS9KeUE9PSIsInZhbHVlIjoiNkJwQzZuOG9MMUxoVnlpMlovUTZadz09IiwibWFjIjoiZjEwMWY2NDJiMmY2N2IwYzYzMWQ1ZWZhOTU3YTk0MTAxYzQ3YWU0NzU1YjFiZDFiMzVjNDg2NjU0YjA5Y2YxNSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: police_cloth\nProfile: 30 MB 141ranvid\nService: any\nRouter comment: Police','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(120,'2','user','Shofiq','Not provided',NULL,'kpiprincipal','kpiprincipal','eyJpdiI6ImphNnplRnhRa1Nrd1pUMnhERlB4bWc9PSIsInZhbHVlIjoiZjJwbW1rZkNPNW1LM0dWOExMUXRLQT09IiwibWFjIjoiNDc3NTNjMDVhZDcxZjc1NmIzNGZkOGJlYTcxYjMyNmIzYjIwNTA2NzNjMjlhMGFmZmU1NWJiZTU1Mzk0ZWIzMyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: kpiprincipal\nProfile: 200 Mb Star\nService: any\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(121,'2','user','shofiq','Not provided',NULL,'maju','maju','eyJpdiI6IjE3OVNLUk9lcDhtSEM2bjVFcGhaL3c9PSIsInZhbHVlIjoibnlPeW5RWnlYNzFOQWhaMUhzK0Zjdz09IiwibWFjIjoiOWY4YzlhMGZiZDNjNGUyYjJlNjk3ZDQzNWM0ZmZhMDY4NzlkZTBlMTU2YTE0MDhhYTQwYmYwNzM1NjZkYTlmYyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: maju\nProfile: 30 Mb Star\nService: any\nRouter comment: shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(122,'2','user','shofiq','Not provided',NULL,'kpihelel','kpihelel','eyJpdiI6IkRzeTVTNlA3eXgvRVErc0krbm1ieWc9PSIsInZhbHVlIjoielJlM1VlNVRDQlI3VTdFZHNzR2RXQT09IiwibWFjIjoiOTlhZDQyYThlNzQ4Y2QzODc0MjFjYmE1NTk4ZDFkODk3ZTc0NWFkYzYzOGE2MjBiZTI5MDc0NzgwYzYzZjZlOCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: kpihelel\nProfile: 30 Mb Star\nService: any\nRouter comment: shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(123,'2','user','Shofiq','Not provided',NULL,'mirschool','mirschool','eyJpdiI6Ilp1UURSbFNscytxd3hGSGl1dGN6N3c9PSIsInZhbHVlIjoiQ3VtV25CVnR6QzR6UXRFb25zTmN2QT09IiwibWFjIjoiMzk1MmE2OGNhOWE3MWZhMTJjYjM2ODNhNzc0YzRmNjdmNmRiOGQwY2M4MDM5YmExNDgzY2EzOTNkYjE5M2VjOCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: mirschool\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(124,'2','user','Shofiq','Not provided',NULL,'murad_vi','murad_vi','eyJpdiI6ImdCREN2TjN3ZE1nZFNqeGUzT3hxNkE9PSIsInZhbHVlIjoic0U1YUdpaUNkS2Q2WHozYlBjOHdZZz09IiwibWFjIjoiMTQwZmY3YjcwMDkyNGM5ZTEyM2UyZDg4MDI2NmM2M2Q2YTgwMzdlZWNlOGNiNzg0NTcyMDI3OGNjZWQzMzUxOSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: murad_vi\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(125,'2','user','Shofiq','Not provided',NULL,'nafizkst','nafizkst','eyJpdiI6Ink0MTRDa0FKdjVxcXh1bWw2SUZpV0E9PSIsInZhbHVlIjoiS04xYzJxZlhkZDNkQU95Q1MwY2R4QT09IiwibWFjIjoiYzg5YzA0M2Q2ZTU2N2ExZGQ1MzM0YThlYTJkM2JjZThkODkwMWI0NGE1MGVmOTBjZWRmMDJjOGRkYWNkODNhMSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: nafizkst\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(126,'2','user','Shofiq','Not provided',NULL,'munnabasha','munnabasha','eyJpdiI6IlVwWmFiRElyZTRhSXJUZVh0eU80Z3c9PSIsInZhbHVlIjoiSkxxSGdicWVac25ZbHNwWEdzTWh0Zz09IiwibWFjIjoiYjkzODJkMzg0ZDdmMjc4MzVmNzQxMTZmNmVmOGU1NzUxNTlmZmYwNDkyODkzNTExZTY2Njg1ZGQzY2NmMDVhNiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: munnabasha\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(127,'2','user','Shofiq','Not provided',NULL,'onkurdec','onkurdec','eyJpdiI6IjZobFdnbVVOZE1OUm1tem44emFaZkE9PSIsInZhbHVlIjoiUzc1QjRCbXdxVlpiS3RHQ0FjUXpFZz09IiwibWFjIjoiMzk4MWE1MjMwMjYxZDYzOTY1NGZmNTc0YWFmMmUzN2E5NjIxZmY1MTMzNjY3MjQxYzlkNDQwZDAzYmQ1ZTg2MCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: onkurdec\nProfile: 30 MB 141ranvid\nService: pppoe\nRouter comment: Shofiq','inactive',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(128,'2','user','Shofiq','Not provided',NULL,'kpishamim','kpishamim','eyJpdiI6Ikt2MzZNYUwvSVVJbTIzemJxbktYb3c9PSIsInZhbHVlIjoibDZ3amlUMjhKNDhtU0h5aUFOUnVPUT09IiwibWFjIjoiNjBiZjgxNTBjZTc3ZjAzYTk1ZjUwMWQxMzUyZmQwMzI4ZTFmOTM3YzU3OWZiYzViNzFjMWRlODIzYzQ0MjlkZCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: kpishamim\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(129,'2','user','Shofiq','Not provided',NULL,'kpiamol','kpiamol','eyJpdiI6ImtJaVVXeU9DQWx0UWFyS0tObURkaGc9PSIsInZhbHVlIjoiVXUzNDFRb0VmcjlONER1ZCs5U2NHZz09IiwibWFjIjoiYzc4OWI1MjMyZjM2NGNlODI0ZTJkOTg5ZWUxN2FiMGJkNWFmYzFhMzBjNGYzOGYyOTU0YmNmZTcxNjAxMTFiZCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: kpiamol\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(130,'2','user','Shofiq','Not provided',NULL,'kpimasud','kpimasud','eyJpdiI6InU1SVAyS1JPOHVuNytTTUUwM2dwM2c9PSIsInZhbHVlIjoiOE1zK0ZHMXRRYW1zemtnc2NBeU9NUT09IiwibWFjIjoiZTY5YTU3OTE2ZDdiZjVjMjI0MzMyZTQ2OTA0YzNiMTcwMWZiOWJiOThkY2M2YWVmMTU3Y2Y3ODliM2Q1MGIwYiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: kpimasud\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(131,'2','user','olongker','Not provided',NULL,'olongker','olongker','eyJpdiI6InRscjNyY3loYUJNZmYrR2FyT25pTnc9PSIsInZhbHVlIjoic0VIT1B2YXNtRmR5ZHdCWmMyTnYzUT09IiwibWFjIjoiODExOGFiMWRmNTU0NDRiNDE0M2Q0N2ZkMjYzZTM5NjkwMWJmYjFjYjAyNmVmYmIxYWUxYmQwMjE2OTA3Mzk1MiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: olongker\nProfile: 30 Mb_Travelshouse\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(132,'2','user','Shofiq','Not provided',NULL,'kpikonam','kpikonam','eyJpdiI6IjgxbUNIb1ZSdW0zNVM4NTlibVRKYXc9PSIsInZhbHVlIjoiL00rT3FrZ2xPOUNJdmxURkdsMnJYQT09IiwibWFjIjoiNTVhYWVjOGRlMTRiNDhiOGY1ZTNhMjU2NTdlMmI0Yjk3NjNmNTM0ZjdkZDRhN2I1Nzk2NjQwNTMwNWVkYmUyZiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: kpikonam\nProfile: 30 MB KPI\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(133,'2','user','Shofiq','Not provided',NULL,'kpizisansir','kpizisansir','eyJpdiI6IjhEaVFiMmFWODFIa2ozTGhRSEdVeWc9PSIsInZhbHVlIjoiaFBEQUNPZzJ3QjR3TTRpVFI1RDU5dz09IiwibWFjIjoiNGY0NTE2MzNlMjRmYmZhM2JkNmVkOWIxMTI2OGQ5NjkxNjAyNTljNDAxMDZmYWU4YzQ5ODk0YzgyNDJkNTA0OCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: kpizisansir\nProfile: 30 MB shena_nir\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(134,'2','user','Anike','Not provided',NULL,'kps_prijom','kps_prijom','eyJpdiI6IlN2RVRjTEh3TTFoWS9rN2gySXI2NGc9PSIsInZhbHVlIjoiNmdCbkphTk1rVnNDNlRLczczMzBpZz09IiwibWFjIjoiZGY0ZmUzNjZhNzBkMDI5ODAxMDdmNDFmNTljZDRlNTBmMjhjNGExZDY2YzZkYThlY2FmZjliNWY3OWFjM2Q3MCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: kps_prijom\nProfile: 30 MB govt_college\nService: any\nRouter comment: Anike','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(135,'2','user','Shofiq','Not provided',NULL,'lged4th','lged4th','eyJpdiI6Ik03RytzTUZFNDhDOXhXTXcyVEpldUE9PSIsInZhbHVlIjoiSmZUWDVRczA3bEovcmgyMG9nRlZHQT09IiwibWFjIjoiZjY3YjA1NTY1ODQwMzYyNDc1ZmMxNzMzYzU1MTk4MzUxMjExMDAxMTQ3NjY4YTdhNTVhNjlmNjIxNzU4NjA0MiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: lged4th\nProfile: 30 Mb Star\nService: any\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(136,'2','user','Shofiq','Not provided',NULL,'kpilibery','kpilibery','eyJpdiI6ImtYSlRkYjAzTG5Qc01TQmpaOFRIN3c9PSIsInZhbHVlIjoid3pSVjdsbThpTmhXZ25pQVFPa1Z4QT09IiwibWFjIjoiZjg0ZjM1OTAwMjNiMjRkYTMxOTk0NTNjNjQyZDM4ODAxNmJmNWViMDUyZGNkNThhMGQyYWY0N2U1Y2Q1NzFmNyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: kpilibery\nProfile: 30 Mb Star\nService: any\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(137,'2','user','Shofiq','Not provided',NULL,'niribilit','niribilit','eyJpdiI6IjFCKzBCa1FoY1dhcnBiWVloTzY3QXc9PSIsInZhbHVlIjoiRHE3d0I2TldTLzF1NzVSVVg2VTJOdz09IiwibWFjIjoiZTc0MTRkODg5YWY4MzlkZThkZTIxM2M1MjFkMmZiNDY4ZTRmNDZkNTRhZWJkM2I5NzMxYjU2YWY0NThlNWFkNSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: niribilit\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(138,'2','user','Munna Barir pase','Not provided',NULL,'munna','munna','eyJpdiI6IjBaL3JvY29OMGdiK2pkWCtVUHJndUE9PSIsInZhbHVlIjoiNDFhczJ2REtWR0I0U2RIQzA4Y05RQT09IiwibWFjIjoiODBjYTgwOGRkYWQ2ZWQxMWNlMDg4ODZkNDllZjE5ZWUxMjhhMjNkODFhMDYwOGNkM2VmNTk2NDI1OGExYTcxMCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: munna\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: Munna Barir pase','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(139,'2','user','Anike','Not provided',NULL,'kps_snv','kps_snv','eyJpdiI6InpIcUNoMGp6RWpZMlRQcFZvUXpMR2c9PSIsInZhbHVlIjoiZW9RMjhyL3U4OGJSK0Q2SDdpVmlVUT09IiwibWFjIjoiNGUyOTkwMjVkNjU5YzgzYWFlNjVjZTE5MTI0Mzc3ZGU5OWM4MmZhZDhhYzBiNGVjNTI1ZjY0ZTI5YmU1OTJkYyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: kps_snv\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: Anike','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(140,'2','user','Shofiq','Not provided',NULL,'manik_fashion','manik_fashion','eyJpdiI6Im5RWEtMQVFobEl6bjNUU2EvTWFCQ3c9PSIsInZhbHVlIjoiWkV4ZEhKdjJWYWcxUlN6S1ZWYmFkZz09IiwibWFjIjoiNmI5NjFkNTQzZjA2MDhkMjlmZWE3OWNhZDIwYmUxNWU3NmZiMjlmYzk0YjQ1YmVlNDRiZWI1ZjYzZDYyOTVmZiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: manik_fashion\nProfile: 30 Mb Star\nService: any\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(141,'2','user','Shofiq','Not provided',NULL,'liton','liton','eyJpdiI6InI0MXBzYjc4eC8ycEMxUUJCMlJNVkE9PSIsInZhbHVlIjoiZXJxNzF0aXNqZXV5MFE1TXl2ZWh4dz09IiwibWFjIjoiODJjZGMyOTJmYzJhYWQ2YmZmZjljMDA0NjBjMzA0ZWFjMmI5MzVmZTg4NTA4MjhhNDg2ZjBhODBhOGUzYTY3NyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: liton\nProfile: 30 Mb Star\nService: any\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(142,'2','user','Shofiq','Not provided',NULL,'mukul','mukul','eyJpdiI6IkYwVXNkbnJjVHExZ3NRaFRvMlJDbEE9PSIsInZhbHVlIjoiR1FTRUUrcjBMWmo1dW51NnBnb3F5Zz09IiwibWFjIjoiYmY2M2JlMDNiZjFkODhmMzk0NmZiNTZiNzQ4NDk2MDdmNzgyNTliMmFjM2M3NjkxNTdiZTA3M2RjNWEyMWY2NiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: mukul\nProfile: 30 Mb Star\nService: any\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(143,'2','user','Shofiq','Not provided',NULL,'ornob_mukul','ornob_mukul','eyJpdiI6IktvcjVtbWEwV2xwZEpjUHF5MmNIVUE9PSIsInZhbHVlIjoia3R2TFI1S2V1SEJ5aUtvaWFCWGo5QT09IiwibWFjIjoiOTU2MWUwOWFmYjI4ZjE4ZTg1Y2Q1MzY4NjEzOTM2ZWI3NmNhYjg4ZjAxNmQxZTkzN2I2OGNjZTRhYjYxMjUzMyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: ornob_mukul\nProfile: 30 MB govt_college\nService: any\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(144,'2','user','Anike 600tk /10/25','Not provided',NULL,'kps_arif','kps_arif','eyJpdiI6IlkwUDFZZklxZjg0SWhicVBEMDRhQ1E9PSIsInZhbHVlIjoiTGk1NytCb1ArK3RmbHprZ3JoVmozdz09IiwibWFjIjoiMmU0Yjk3OTVkZTg1OTJhZTgxZTkyYmE3ZTliMGU3MDY4NzgxNmJmZWE1MDUzZThlYzU0ZTQ3NjBmYmNlM2QxZSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: kps_arif\nProfile: 50 Mb_Travelshouse\nService: any\nRouter comment: Anike 600tk /10/25','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(145,'2','user','mizanvai_btcl','Not provided',NULL,'mizanvai_btcl','mizanvai_btcl','eyJpdiI6Ik9uZEJiSnpERUpGaGpheXVZdWc0RFE9PSIsInZhbHVlIjoiQ2VJdjIzaDNNMENBN3lJSTNIVGVxdz09IiwibWFjIjoiYWM1ZmFhYzBhN2RkMmI0ZDFhMDU1YWRlYmVjYWE0NjY2MGNhY2Q4NTM5ZDlmOTRjOGM0ZTAzMjVkMDI4NTg3NSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: mizanvai_btcl\nProfile: 30 MB Lgedks\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(146,'2','user','kps','Not provided',NULL,'kps','kps','eyJpdiI6InplWjZPNE9NVEpXUFRKYVF5bU1zZ2c9PSIsInZhbHVlIjoieU9CMktPZ0M1R1hrSEw1NEkwWk9wUT09IiwibWFjIjoiZWUxYzdhZTQ2YjY2ZWY4NWVhOTc0NmIxMzQyOGIxNDNlZDhkYjRmNDJlNTA2ZWM4NWJhNTgzY2E0ZWZlNmM2NSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: kps\nProfile: 30 MB 141ranvid\nService: any\nRouter comment: none','inactive',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(147,'2','user','kist','Not provided',NULL,'kist','kist','eyJpdiI6IktadThJdHNTNTdWbnZsWDBLL09DZ1E9PSIsInZhbHVlIjoieWZrTGh0QVRlR0tFUGg0L2QwRGtwQT09IiwibWFjIjoiZmI3MmNiMzUyYjA4ZTdmMWQ3YTgyYzZiODE1ZTUzNzhlZWZiOGEyNzc3ZjcyZGRmMWEwNDYzMGY4ZjM4ZThiMSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: kist\nProfile: 30 MB govt_college\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(148,'2','user','Anike','Not provided',NULL,'mamun_telicom_salim','mamun_telicom_salim','eyJpdiI6Ik1aUE13QnlHUGk2ekNKeThweU5aeEE9PSIsInZhbHVlIjoibTFROFN3V2RMU2o1NENONmUxeGllZz09IiwibWFjIjoiMWVmZGY2OTVkNTkwMTc0N2U0NzQzYTkzODMyMDYzNjE1M2E5OWNiNDliNjY2YThhYzg3N2JiMmIyOGNmMzE2MyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: mamun_telicom_salim\nProfile: 30 MB Lgedks\nService: any\nRouter comment: Anike','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(149,'2','user','kibriavai','Not provided',NULL,'kibriavai','kibriavai','eyJpdiI6IjFjQ1hjbmU5T2dyMkxjQmZabFNQM0E9PSIsInZhbHVlIjoibFJxUkpjVTYwcXh2WHo3aGd2TWhzdz09IiwibWFjIjoiZTU1N2U3OTY3ZjJmZWI3ODBmMjc5YjdiYzU5MGQ5NWYzM2IxMTYyM2Y2NmUxZDAzY2NjOTY0YzRlYjBjMGJmZiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: kibriavai\nProfile: 30 Mb Star\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(150,'2','user','kushtiat','Not provided',NULL,'kushtiat','kushtiat','eyJpdiI6InZNSGI2QVdxRFJXdC9RT1UxcFdUTlE9PSIsInZhbHVlIjoiamZiWWt6bC9kM2xvUWl2NWUrc1lXQT09IiwibWFjIjoiMmQxZDA2Y2ZmOTE0MGNiOGI4NDBmZDE0MjlmMmEzYWY4OWJiZjM4YjUxYzRmN2QxOTRmN2M2ZmE1ZTAwY2JhMiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: kushtiat\nProfile: 30 Mb_Travelshouse\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(151,'2','user','mir_teacher','Not provided',NULL,'mir_teacher','mir_teacher','eyJpdiI6ImNLZjhmVEFPRjFLbnZFdDNEOTFYemc9PSIsInZhbHVlIjoieHdIeUxTaHJuMFE4VktQbzVROUpOZz09IiwibWFjIjoiMzYyZjM1N2I5NjQ4YThlMTc3ZDhhMDg2ZjFkMDE5YzA0NDJhNGY3OTNkYjNjNDFkNjRlYjU1MDlmYjI0N2NhMCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: mir_teacher\nProfile: 30 Mb_Travelshouse\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(152,'2','user','marufkst','Not provided',NULL,'marufkst','marufkst','eyJpdiI6InRkZTRmSDlmY3B1ZC9pV3FxUVAzSWc9PSIsInZhbHVlIjoicUxrL1dXNXg3ekhqSHA1OU9jQnAxdz09IiwibWFjIjoiNzlmYzI4M2UyZTgwMzQ4MDkyOGIxMTY3YzQyZTc1ZjM1NDMzYzAxNDJhODViYzI5NWE2ZWVhNTY5ZDM2NTVlNSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: marufkst\nProfile: 30 Mb_Travelshouse\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(153,'2','user','majo_nana','Not provided',NULL,'majo_nana','majo_nana','eyJpdiI6IjViM096ZkNSU3o3OVNGS1NDUnJuYlE9PSIsInZhbHVlIjoiQzBmb1NLT1FweUlWZUFyNzc3ZVl1dz09IiwibWFjIjoiNWJjYmQyODE1YTc3ZDNlNjg5ZTFjZWM4YTEzMjZjZjJkNzkwYzY3OWJhOGI0OTJiZTJiNzUwOWJmNTVlZjgwNyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: majo_nana\nProfile: 30 MB Lgedks\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(154,'2','user','khairulkst','Not provided',NULL,'khairulkst','khairulkst','eyJpdiI6IlBUbGVzYmk0c2tmUjdROGU2UnAwb2c9PSIsInZhbHVlIjoibW1pZ0dxNExqbitUSy8wdTZPRHBxZz09IiwibWFjIjoiMzY2Y2Y1OWFiOGZmMzU5MDZlYmUyZmNjNWU1NDZiMGFlYjVmNWYyNGJmOWY3ODNjNDkwMWEzZTMyNDBmZmFjOCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: khairulkst\nProfile: 30 Mb_Travelshouse\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(155,'2','user','mmclass','Not provided',NULL,'mmclass','mmclass','eyJpdiI6InYyQWR5SjhGRmhzM0UwZEdEd1hZSUE9PSIsInZhbHVlIjoiSytYMzBuS2hFcFduTENqN2xZRUhpQT09IiwibWFjIjoiMzBlMjQ3MjE4OGI0NjUxMmU0NTIwOTI2NWFhMGVkY2ZiMzlkZTA4MjkyYTJlM2NhMGY1YzQ4ZjhiMDgxOGEwNCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: mmclass\nProfile: 30 MB ZIlas\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(156,'2','user','nahid','Not provided',NULL,'nahid','nahid','eyJpdiI6IlNxTGg3RlpWQy9sZGg4a2RUZjhtZEE9PSIsInZhbHVlIjoidkdXYk1tbWZTa1FvWjdEaTM4RlhlZz09IiwibWFjIjoiOTFkZThkNWMzNTIwMjY5NGJjZGYzZDIyNzJjMDI5ODNlZThlNTBhZjE4MGVhNTQ3ZTJmMjk2MjkxOWI3NWYzNyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: nahid\nProfile: 30 Mb_Travelshouse\nService: any\nRouter comment: none','inactive',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(157,'2','user','kistprincipal','Not provided',NULL,'kistprincipal','kistprincipal','eyJpdiI6IitiTWxKUHFvRVY3dW9BZitFV1F4UUE9PSIsInZhbHVlIjoidXl0S2xCTXM1M3c2R0g2M2hwa29hUT09IiwibWFjIjoiNjYwYzkwNmE1MjVjNTAyMDcwZjE3NTVmMDE1YTRmZTQ3ODRjYjFhNWU1MzNhMGIwYmRmZjZjNTE4YTBlMDQ2OSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: kistprincipal\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(158,'2','user','kobirtea','Not provided',NULL,'kobirtea','kobirtea','eyJpdiI6IkRuWTA0dkNyVk4vNy8zM2JYaUZyM0E9PSIsInZhbHVlIjoiNUNqUDl6NElQVEY3TVBjUzl0YnVYdz09IiwibWFjIjoiMmRlZTAyNTMyYThjOTM0ZDk2NzI3MjQ3NGRkYmU2OTcyOTcyNWJkNTJmMjdiMjlmOTNlN2M4MThhM2RmNDdmMCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: kobirtea\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(159,'2','user','nayeemkst','Not provided',NULL,'nayeemkst','nayeemkst','eyJpdiI6IkMxdEhCM24vYStJQk5WU2Vna1lFTmc9PSIsInZhbHVlIjoiaHhoNVlCaWh4WXFlOTdnNFl4b08wZz09IiwibWFjIjoiZGQ5ZjFlOTE1NGZmOTFmYjE1ZTBiYzdjMDhjMzNhYjBlNDkwYzUzNDhlMGExNWZlOGVlMTU5MGFlYjY1MzYyZiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: nayeemkst\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(160,'2','user','madani','Not provided',NULL,'madani','madani','eyJpdiI6ImI3SWxBQm9oNDlHNEEyL0hvQUh6WkE9PSIsInZhbHVlIjoicWlGR0I4a2ErakZPRlpjQXhzQk1Pdz09IiwibWFjIjoiYzMwYTllYjg1OWJiOWE3Yzk5ZjE2NzZmMDRkOGQwY2YzNmM3OTUyZWNjMDU5OTMzODM1ODBiMjM0MjAyYzg3NyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: madani\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(161,'2','user','mohibul_shena','Not provided',NULL,'mohibul_shena','mohibul_shena','eyJpdiI6IlRPNzdhaXVHNFVjU2FMTkZJMU9nNWc9PSIsInZhbHVlIjoiTEorTXZrK0ljbDdkSmhoVkl6VUlWQT09IiwibWFjIjoiYTI2ZTE2YWY4YmQ3ZDc0M2FiMTQwZjBjMTc1ZTI4MDNhZjlmODdjOGQ3ZjQ5NGVmNjIxMjQzNDk5NzRkY2I5ZSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:55\nConnection ID: mohibul_shena\nProfile: 30 MB shena_nir\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:55','2026-08-10 23:08:55'),
(162,'2','user','major_shena','Not provided',NULL,'major_shena','major_shena','eyJpdiI6ImJMVHJlQ29YSWNPMUE0bUFtK0N6aXc9PSIsInZhbHVlIjoieWNWVjNOWWkwRGdWbTFsYXYxRjVNQT09IiwibWFjIjoiMmVjZTVmMjEyZGY5MTk0MjEyYWNjYjM1ODJkOGYwZjk2ZmE4ZmQ2NzRhZDM2YTJiNDVkMGNmNWU3MjA3MWM5MiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: major_shena\nProfile: 30 MB shena_nir\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(163,'2','user','mukto_anam','Not provided',NULL,'mukto_anam','mukto_anam','eyJpdiI6Ik0yckY1dnEzVW4yUVFIRkVnYk9ZTXc9PSIsInZhbHVlIjoiL1J1M3VrbitTeXRCdFJvSGRVa0N3QT09IiwibWFjIjoiYjhkZGY5ZjBlODQ1Yzc4OGI3N2I3M2Y5YzQ1YWNiYjJmOWNjY2YyNWNkZTgxMWMxZWYyNmQwOTQ4MzJjZjBjNSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: mukto_anam\nProfile: 30 MB shena_nir\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(164,'2','user','parvezvai','Not provided',NULL,'parvezvai','parvezvai','eyJpdiI6InkycDJncFJCaG0vMDdMSkR6YTFROGc9PSIsInZhbHVlIjoiY0tUc09OUWpOZG16RHhDa0doQy9YZz09IiwibWFjIjoiNjZhMmYzMTFmOTAxMWNhMjFiMGE1NTI0OWJlMTQyN2NiMWNkNTM2OWI1YTU2Y2JkZDE4Zjk5YTY2MThlZmU5NiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: parvezvai\nProfile: 30 MB shena_nir\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(165,'2','user','medul_shena','Not provided',NULL,'medul_shena','medul_shena','eyJpdiI6IksxYUcxVGo1cGZWZXR4dE1vRlExb2c9PSIsInZhbHVlIjoieFQ1bGJ6bTZXdS9mb0xkRlVEUkxBdz09IiwibWFjIjoiNDRmZTc5ZDYxMmExNmI0YzQ5OWM1ZDFiOGQ1ZTk5MTNjM2M5OTg1NmNlYWYwODFkZTk0YjI0ZmYzZTkxMWY1YiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: medul_shena\nProfile: 30 MB shena_nir\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(166,'2','user','lged_rezwan','Not provided',NULL,'lged_rezwan','lged_rezwan','eyJpdiI6InJtaVVGOE9nYWt2QllDZ2VNWWpTaXc9PSIsInZhbHVlIjoid1FnUHBzRHB2SzBNK1AySFBBZll2Zz09IiwibWFjIjoiZWRjNjk5ZjM3ZTNmYWJjZDZjZjgwOWJmN2UxYmQwM2U3Yjc3NDA4MzM3YTkxMzE1NzkxNmU0ODc1ODU5ZTE2YSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: lged_rezwan\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(167,'2','user','kpi_ct','Not provided',NULL,'kpi_ct','kpi_ct','eyJpdiI6Ik5SUmZaQ3NpU1hIYUJ5YW1FQmdpUHc9PSIsInZhbHVlIjoiWFZiU1hiZmtRUmtFTGQvTFdCMGlJQT09IiwibWFjIjoiM2VjZGE3Yzk4ODhhOTc5YjMxOWI3ZmQ0ODFiODJkMTMwNTc4OTE3ZWVkMjc0M2I3NDhmYzk3ZjU2OGRmZTFiNiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: kpi_ct\nProfile: 50 MB KPI\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(168,'2','user','kpi_etlab','Not provided',NULL,'kpi_etlab','kpi_etlab','eyJpdiI6Imd4SDRHdWI2dDRwL3BWVnNMaitxM3c9PSIsInZhbHVlIjoiRW0yVTJrY29JOG9rM1E1RkpoWkk2UT09IiwibWFjIjoiODYyYzNmOTJmZjljM2FjOTE4YzlkOTE4NzAxMzNhZjRiODJjMTc5MTAyY2ZjNjkxN2FkYTRmOWU5YTA0MWNjZiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: kpi_etlab\nProfile: 50 MB KPI\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(169,'2','user','kpi_hvl','Not provided',NULL,'kpi_hvl','kpi_hvl','eyJpdiI6IlJiVmJQNHBDYXVBZU16b1Z5V2ZOYkE9PSIsInZhbHVlIjoiZFdHREVUK29mWHBTOUFOS1B1N1dsQT09IiwibWFjIjoiNTQzMmViMGVkYjU2NGE2YTY3ZDA5ZDk0MWU3NmY2OGM0ZWNjZTc1ZGQ4ZmZlMTdhYmYwYmM1ZjBjYTE5Y2Y4MCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: kpi_hvl\nProfile: 50 MB KPI\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(170,'2','user','kpi_fuellab','Not provided',NULL,'kpi_fuellab','kpi_fuellab','eyJpdiI6IlhMalM1Y01OaTZqbHZCa1FKNEpBa1E9PSIsInZhbHVlIjoid1djTWdpdlVKT01pUm5iRUEzMUxCUT09IiwibWFjIjoiM2NkZTQ0MjQ5ODc5NmM1ZDkxMzYwMzI1MzA2ZTQ4NDM4ZjZlMWJjMDc2MDdlMzI1OWY3N2FhNzEyNWNkYmVlMyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: kpi_fuellab\nProfile: 50 MB KPI\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(171,'2','user','kpi_asset','Not provided',NULL,'kpi_asset','kpi_asset','eyJpdiI6InRaK3NYN0RQd0J3Y1pSNUNIY2xka1E9PSIsInZhbHVlIjoicmFFM1RCUk0zM2luWHhOS0xLL2dDQT09IiwibWFjIjoiODZjMDQ5Njg3NDczOTQ5YjhkZGNiMTczY2JmNWIxMTkwM2FjZGFhZDk1OTk2MjhiZGJmOTE5YWY1YWEwZGNhZCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: kpi_asset\nProfile: 50 MB KPI\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(172,'2','user','kpi_2nd','Not provided',NULL,'kpi_2nd','kpi_2nd','eyJpdiI6InNPMWI1amFyNWp0UHpJM3JrNCs2bnc9PSIsInZhbHVlIjoicVBHWFd4WkI3VFY0YTJWUUs5dnhTdz09IiwibWFjIjoiYTAyYzBmZWI1NWIxYTQ3YjJiY2Y0OGQ3ZTAxNTQ3MjA2NjRjNzk1M2ZhMWU0MzEyMTljNjI1YWI2MmY0NmEwNyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: kpi_2nd\nProfile: 50 MB KPI\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(173,'2','user','kpi_confa','Not provided',NULL,'kpi_confa','kpi_confa','eyJpdiI6IkVvZkVKaDR0aDVzVnVJNE5BN3J6aFE9PSIsInZhbHVlIjoiUUlQNmkzYWlPa1hGN3p1M2ZDWjJFQT09IiwibWFjIjoiODUzY2ExNWEwOTQzNmMzNDI0ZTFlZGQ2OTBhMzFiNDMyOTgyOTU2YjViYjQ0Y2U4ZDgxNTExNWJkZjc3NGVlYSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: kpi_confa\nProfile: 50 MB KPI\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(174,'2','user','kpi_testing_lab','Not provided',NULL,'kpi_testing_lab','kpi_testing_lab','eyJpdiI6Ink1RVkvRE1wMVFxVFBUa2RLS0gwb1E9PSIsInZhbHVlIjoiVnJUU2owYTk5ZkJ2RnFyVzhmaDZvdz09IiwibWFjIjoiNmM4Y2YyZDY2NDY4YTQ0NTA3YzljODM0NTY2YTkyNmFhNWVlYmRiY2M1ZWQ5ZWEyNjMyNzM4NGQzYzcyNDJlMSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: kpi_testing_lab\nProfile: 50 MB KPI\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(175,'2','user','kpi_ent','Not provided',NULL,'kpi_ent','kpi_ent','eyJpdiI6IlRzRDNpMXJpNXI5VzYrTUNGL2tXMWc9PSIsInZhbHVlIjoiZzBOUXNTanhCUzBLM0gxSkZGdXk5QT09IiwibWFjIjoiZjVjNjA2OTE2NDYyZDJlNzY1MTRhNTg2ZmRkNDlmYTAzMTNmMzljZWQyMDIzYjUzOGY0ZWRiMjU1NTYwNTAzYSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: kpi_ent\nProfile: 50 MB KPI\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(176,'2','user','kpi_acc','Not provided',NULL,'kpi_acc','kpi_acc','eyJpdiI6Ik40LzAxUEdHWGJXM2FxamcwL0xGZ2c9PSIsInZhbHVlIjoibHNnMG9GdGlKNisvakY0SUZUMmgwZz09IiwibWFjIjoiZWNjMWEwZmJiNjg0OTJhZjAyYzRiZWYxYzg4ZTljMWFmZDk4OTVmNmNhYTNmZjQ5MjA3MjJjNTY3ZDFjZjA0NCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: kpi_acc\nProfile: 50 MB KPI\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(177,'2','user','kpi_control','Not provided',NULL,'kpi_control','kpi_control','eyJpdiI6InBGbERLc2NIaDNBTXlxNGo0Uk9JMVE9PSIsInZhbHVlIjoiaDhzaGZOUFQ5ckFxQ2xVTWMyTXlEZz09IiwibWFjIjoiNGE0NGE1MzM4MDZkMzlkOGM3Y2Q4MTJhMDc1MGM3NmIzNWM5NGRkN2QyNTZhZDczNTc1MmQ3MDU2MWQwZWYwYyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: kpi_control\nProfile: 50 MB KPI\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(178,'2','user','kpi_elcdept','Not provided',NULL,'kpi_elcdept','kpi_elcdept','eyJpdiI6Ijd4ZWNWdW9TVXgvTGY4SHdkN1hiWnc9PSIsInZhbHVlIjoiaGxKL01ZbXhjSWlnU0RGTFN6cjgzdz09IiwibWFjIjoiMzg5YTYxMTNlNzRiZGYzN2U0MWQyMTY5OWYzMDJiODczNjFhZDViNThjZTk2YjYwZmNlYmE3ZmMwYzYxODA0NSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: kpi_elcdept\nProfile: 50 MB KPI\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(179,'2','user','mamunvai','Not provided',NULL,'mamunvai','mamunvai','eyJpdiI6Ik1NMC9sNmpTQ2ZkZGlrT0JkK1BYcWc9PSIsInZhbHVlIjoiRnQrUjM2SnBkWEhoVFhEOGJwd01Kdz09IiwibWFjIjoiYjFlYzEyMzdhMzRiMmI2ZTYyNTlhZTQwYjVjMGFmYzFkNzc3MTljNmM1ZWZlYzU5YmY3NTZjMDhjYTIzMzM1YyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: mamunvai\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(180,'2','user','kpi_zisansir','Not provided',NULL,'kpi_zisansir','kpi_zisansir','eyJpdiI6ImhlTnI3VXRScG1iU1VrY3U3S0NoYWc9PSIsInZhbHVlIjoiVGNYdktRM1pDSnVLYVcySWlCZ1ZOdz09IiwibWFjIjoiOGM5NDJmZmY2MzE3NDZmZDQxODVmNDVjOGJlYjQ0ZDgyNzhjZWY2ZjgyMzEwYzZjZWFlMzVhZTNiYzhiYTM3YSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: kpi_zisansir\nProfile: 50 MB mosharof_bgoly\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(181,'2','user','Munna Barir pase','Not provided',NULL,'mamun','mamun','eyJpdiI6IlM5eXd0eHpPN3d4cU9zV0VrUnFoMWc9PSIsInZhbHVlIjoib0htL3RMVGlpTU1FWC8ybEwyTG9Bdz09IiwibWFjIjoiNWJmMjhkNmNlOTJlN2JmN2M4MTgxOTlkYzI1N2I2MzEyMDUxODZmNTZhNzMzZTM1OWQ1YjIyNjg2ODk3NWY0OCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: mamun\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: Munna Barir pase','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(182,'2','user','mintu_sir','Not provided',NULL,'mintu_sir','mintu_sir','eyJpdiI6IlhRRHdZOXN4NzNYQ1NOblovb3lNY2c9PSIsInZhbHVlIjoiZTYwNGViOTJhTXR2WnppRGc3OEZHdz09IiwibWFjIjoiZDMwYjE1ZTlkZTc4ZGY4NThkZWE2MWUzZjMxZjI2YjZiZTgxNDMzNzdkYWFlZGI0YTUwMjkxZjlkNWNjMzdkZiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: mintu_sir\nProfile: 30 MB shena_nir\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(183,'2','user','moriom_anam','Not provided',NULL,'moriom_anam','moriom_anam','eyJpdiI6IkptSGd0bjEvNFI4Q0dxWWpvR01oaVE9PSIsInZhbHVlIjoiZmpNK0ZCSzdhRXBCSjNtZnA5ajNKQT09IiwibWFjIjoiMzUxMTJlOThmNDg1ZWQxNDQ4ZGU2MTM2ZTNkYjY4ZmQwNTI4NWEyMmM1YTU3YTgzOWE5OTNlNWM3MTMwYjEyNiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: moriom_anam\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(184,'2','user','lged_guest','Not provided',NULL,'lged_guest','lged_guest','eyJpdiI6IjZiWlZPM0FEUDlJcTdIU3dLMEhlb3c9PSIsInZhbHVlIjoiMElNUmI1ZmRLb0oxU0RUQmdVTEMwZz09IiwibWFjIjoiNDg0Y2NlYTVkOGNiMjBiM2RlY2U4ZTFiYTY3ZjdhMDYwNDNhMDJmYzI4NmVlMDIxODU2NTNiNDk5MTNhZDE1NyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: lged_guest\nProfile: 50 MB shena_nir\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(185,'2','user','nondita_alomgir','Not provided',NULL,'nondita_alomgir','nondita_alomgir','eyJpdiI6IkhyNDZQQTJrNW94ekF6ZmQzS0FLYmc9PSIsInZhbHVlIjoiZ1htQ0JTejVROFhORlF0WXBQY2NvZz09IiwibWFjIjoiY2RmZTE0MTRmZTcwMmFiYzgyZTc5ZTQ4MGJlZTllMjZmYmU2ZjViNjRiZTMyNTNmMmNjM2Y1NTZiZWMzY2M0YSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: nondita_alomgir\nProfile: 100 ZillaS\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(186,'2','user','kpi_physicslab','Not provided',NULL,'kpi_physicslab','kpi_physicslab','eyJpdiI6IjJPdDRvVlVnclovMUhPczE5dU1DNFE9PSIsInZhbHVlIjoicTZ1cFpHdGd2cXdEMUR0UzhvUkpIUT09IiwibWFjIjoiY2Q2OTBiNTY5YzBlYjE3ZTZhZGE0MjRiZTRmMTg2MmUwZTcxMzBjZDhhZGI2MTJkZjNhMzQ0ZjE3ZTliM2IzNyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: kpi_physicslab\nProfile: 100Mb_kpi_comdpt\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(187,'2','user','monirul_goshala','Not provided',NULL,'monirul_goshala','monirul_goshala','eyJpdiI6InlOdnJiTEhmU0xKdXpJZEtyM0RjaUE9PSIsInZhbHVlIjoib2U0WTNHeXQwak9HaFgzZUdXU2M1UT09IiwibWFjIjoiZDI5OTgzNjVlNjA5M2FlNmI1ZWY2ODQxNDExMDZkYjI3NzhkM2NiY2VjMDI3MGVmYjQ0ZTU4NzQ4OGFhNTdjYyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: monirul_goshala\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(188,'2','user','moinul_goshala','Not provided',NULL,'moinul_goshala','moinul_goshala','eyJpdiI6InlCRUtkRGRmUy8rcjNPdnl5TFBuZlE9PSIsInZhbHVlIjoiM1MveXVRTjVLSG1lQ0prcEI0emlXQT09IiwibWFjIjoiNjU2NDM5Yjc2ZGMwMmVjZjk2NzU5ZDgwZDFiMjUzOGYyOGViYmVlODA1ODlhOTIzMmY1NjNhZTk5ZDkxM2RhNCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: moinul_goshala\nProfile: 30 MB Saifulkst\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(189,'2','user','kgc_saifulsir','Not provided',NULL,'kgc_saifulsir','kgc_saifulsir','eyJpdiI6IjYyN3Q0a3dZa01Ba0ZNNXdKZnMvd2c9PSIsInZhbHVlIjoianBtY0lFbGF4YldYWEZZMitGNS8xUT09IiwibWFjIjoiNWJkMTM0MTU5NzEyNTY4MTdhMjI2ZDg2N2RhNWRkNWRjNjFlZmViNDgxZDc0ZDg0YTMyZjNjYTAzMmJiZjNhYiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: kgc_saifulsir\nProfile: 50 MB mosharof_bgoly\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(190,'2','user','musharof.bgoly','Not provided',NULL,'musharof.bgoly','musharof.bgoly','eyJpdiI6IlFMT1pkSVF6aWVoTENMOWlHaW0xeGc9PSIsInZhbHVlIjoiMGhpSWVDS2VtQng1WVkrRlF2bWljZz09IiwibWFjIjoiZjEwYzg2ZDc4ODkxZjY1YTZjOWFhYWM0NTA5Mzk2MGFmNzY2MzczZjJhYzZlMDQ5YjRlYWYwYjUxODQwZDhjMCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: musharof.bgoly\nProfile: 50 MB mosharof_bgoly\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(191,'2','user','kochi_bridge','Not provided',NULL,'kochi_bridge','kochi_bridge','eyJpdiI6Imt6emtqVGxNVk5iMU5BWWM3NW5QN3c9PSIsInZhbHVlIjoiUXpYZEoybEN0YkNMaytqQ21KZ3gwQT09IiwibWFjIjoiN2U0OTVlMDI3ZGU1ZjAwZDI1YTgxMGIzYmYwNWE0ZGExN2VkZmI3ZmY2ZjIzNThjYTVhMDU2ZDU4NzIyMDgwOCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: kochi_bridge\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(192,'2','user','kibria_bridge','Not provided',NULL,'kibria_bridge','kibria_bridge','eyJpdiI6IjRsME80UjY0V1Z6eWlIMERZUjJ3M0E9PSIsInZhbHVlIjoiRzNqeTM5d1Bqa3o1eG1pTUp3Yi9vUT09IiwibWFjIjoiMTZmMDQzMDdjMzcyY2EwYjMzZThkNzRmNjEzYTljNjY0ZDM1ZmYwNTY0NWFjMmE1OGY2MjE3OTQxNDRhYmFhOCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: kibria_bridge\nProfile: 50 MB mosharof_bgoly\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(193,'2','user','pappu_bridge','Not provided',NULL,'pappu_bridge','pappu_bridge','eyJpdiI6IjJsQ2lSa3FLNmRVUXA4aHFxdjErUlE9PSIsInZhbHVlIjoiTUNNNWc0SzRWUlNQbFAxOXpJVWJMZz09IiwibWFjIjoiMGRlMjkzN2FiZDAyOWM3MjViYzg4MzE1MmE2YWRlZjA1Y2FjMmRlYmI2ZDhiOWY0ODAyYmY1ZTE3NTc1MmEwMiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: pappu_bridge\nProfile: 50 MB mosharof_bgoly\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(194,'2','user','muid_addin','Not provided',NULL,'muid_addin','muid_addin','eyJpdiI6ImJqMUJIVGwva096UWFpMTZVUWZHQVE9PSIsInZhbHVlIjoiUnYrMGExa2lCcFNPeXU2OTRVcUdTdz09IiwibWFjIjoiNjMwODdkYzliZjcyNGVkNTk5ZWFmNDI1ZjQxOWQxZmU3ZmY1ZDE4ZWY2MWU4MDAzNzQ5N2RjMTRiMTM0ZWE5ZiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: muid_addin\nProfile: 50 MB mosharof_bgoly\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(195,'2','user','mustak_shop','Not provided',NULL,'mustak_shop','mustak_shop','eyJpdiI6Im9oMmdSZFZQVGtuL2NzTXE1Sy9ISkE9PSIsInZhbHVlIjoiOVgyR0tpbjlTWHZHdEpIMjhMZ3F1dz09IiwibWFjIjoiODRlNmVjZTE2OTVmNjI4OTRiNzcwYzI0MDBiM2U1ZTczZTFhNTIxMGFiMjkxM2YyZTFjYzE2MDQwZDJhZmUwYyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: mustak_shop\nProfile: 50 MB mosharof_bgoly\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(196,'2','user','mow_fa','Not provided',NULL,'mow_fa','mow_fa','eyJpdiI6Inhrdjc0aUQzZGlNRitJQ3pjLzB4VHc9PSIsInZhbHVlIjoieUdaWFJqVm1hTnlaZmFOM0krVk9xQT09IiwibWFjIjoiMWQyZTAwZDJhYzRkNjdjMjgxYzQyYWQ2YzYzNGU0ZmNmMzQxMmY3MWY1N2MzYjljZDQzN2M3YWJiYjdmNzUwNyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: mow_fa\nProfile: 50 MB mosharof_bgoly\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(197,'2','user','mazu_moszid','Not provided',NULL,'mazu_moszid','mazu_moszid','eyJpdiI6Ik1yZmpuclNuNFpjSnhOY010QzFlSHc9PSIsInZhbHVlIjoiWXJDNnFWV0lBa3FGZktmdVAwTktXQT09IiwibWFjIjoiNjU3NGU0MzNiZGU1NTEyMWJkMjVjMjdiNmE3MmRhYjZiZTM4YTU1ZGYzM2I1M2MyZjcwMGE1YzMwYzE4NmQxYSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: mazu_moszid\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(198,'2','user','01720604218','Not provided',NULL,'nickber_rail','nickber_rail','eyJpdiI6InB5eU1LL0VTZ2pLQ0tpQ0lUSWpkQUE9PSIsInZhbHVlIjoiYzA5S3hVUVplY09ieHZ4SGZMUDdSQT09IiwibWFjIjoiOGNjYmFmODhjZjE5NDcyZDRkNTQ3NjIwY2E0ZDk1MDAwNzZjMDJhZGUzMzA4OTc5NTRmMzQ5ODc1ZjdmZjBmZCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: nickber_rail\nProfile: 50 MB mosharof_bgoly\nService: pppoe\nRouter comment: 01720604218','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(199,'2','user','masum_advocate','Not provided',NULL,'masum_advocate','masum_advocate','eyJpdiI6IjVXTFZPRTErRTFiczVmTVl6aXVBYkE9PSIsInZhbHVlIjoiM1pQZGVON0RvUnppeVRvWkZZZXJKUT09IiwibWFjIjoiOGU5NjBiMmFkYjBhZDBlYzc4ZWU5OWZhYTU1YjdlNmEwMWFjZjU0MDIzMjlkZTJlZWZkN2Q5M2JjNjY0ZWUzNiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:56\nConnection ID: masum_advocate\nProfile: 50 MB mosharof_bgoly\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:08:56','2026-08-10 23:08:56'),
(200,'2','user','Anike','Not provided',NULL,'shuchona_israfil','shuchona_israfil','eyJpdiI6IlAyamxmeExkNTRuVlMvczNRaE41emc9PSIsInZhbHVlIjoiUWdpZVVycGc4ZnVMVlcvWVJtVVZSUT09IiwibWFjIjoiY2U4OTM0M2I5MzNiZDRiZDQ2MzgyMGViNzIwZmEwYTkzN2Q1NDZkOTJiN2RhY2M4NTliZjQ4N2ZhZTc0NDNlZSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:26\nConnection ID: shuchona_israfil\nProfile: 50 Mb_Travelshouse\nService: any\nRouter comment: Anike','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:26','2026-08-10 23:09:26'),
(201,'2','user','Anike','Not provided',NULL,'sagor_mosso','sagor_mosso','eyJpdiI6Im5ONzRYTUNDSC8wd0NEV0ZYUnVVUGc9PSIsInZhbHVlIjoiMWdDbnphWWVoRDdGRnA0OHFwRzlsQT09IiwibWFjIjoiNDQxYzJlOGRhOTUzOTFhYzQyYWMyOGFlNWE1ZDZlNGI5MzdmYjYzNmE0YzZlZTA2NzgzMWVkNGZmOTI0ODJmOSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:26\nConnection ID: sagor_mosso\nProfile: 30 MB govt_college\nService: any\nRouter comment: Anike','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:26','2026-08-10 23:09:26'),
(202,'2','user','Anike','Not provided',NULL,'Rashid_Rony','Rashid_Rony','eyJpdiI6Im1RcmJFMk1yZFFQQnByeU9rTlR2UXc9PSIsInZhbHVlIjoiU1hyMGFvWEtFd3VoRThKeTk5Vk43UT09IiwibWFjIjoiNDdkMzcwYjY3NzQwZmJmZDIyOWZmMTNkYTA3NWFiNzJkYWRjMThmNTRkODIwMDNlNGRiMDg5ZTNjZGMxNzA1MiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:26\nConnection ID: Rashid_Rony\nProfile: 30 MB Saifulkst\nService: any\nRouter comment: Anike','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:26','2026-08-10 23:09:26'),
(203,'2','user','Anike','Not provided',NULL,'romel_vi_ety','romel_vi_ety','eyJpdiI6IkI4Z0Q3ZkYxSVFNeUF2THpGU2ZvU1E9PSIsInZhbHVlIjoibTJxL3A1ZTMvL2x0UHBLcWwxVmVqQT09IiwibWFjIjoiMDg1MDAyNGUyMDQyM2FiYmQxYTZmZmI1NzMyNzRiNjQ1NzBkM2ZmY2MzZDgzNjM0ZDlhNWJhODcwMTNmMjdhNiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:26\nConnection ID: romel_vi_ety\nProfile: 30 MB Saifulkst\nService: any\nRouter comment: Anike','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:26','2026-08-10 23:09:26'),
(204,'2','user','Shofiq','Not provided',NULL,'prottoy_ripon _dada','prottoy_ripon _dada','eyJpdiI6ImgyWFhTS1FSTzJpT3FmeWR6djR5S1E9PSIsInZhbHVlIjoiZjJ6MXB6ZVVuZUhPK3lRMVZmTE9HQT09IiwibWFjIjoiODExNmU0MjVkNDBkOWI1MjcyYzkyZTBmOGJmMWRlNjA1NmM3NTU2YjEzNGRhY2JhZDkxZjNlYTc3YzA5NWEwNCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:26\nConnection ID: prottoy_ripon _dada\nProfile: 110 MB 141ranvid\nService: any\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:26','2026-08-10 23:09:26'),
(205,'2','user','Police','Not provided',NULL,'police_hospital','police_hospital','eyJpdiI6InJXNW1tL092THN4Vm5uMGJWdDRQT1E9PSIsInZhbHVlIjoiUzl5d1dMNmI0cGViSUZnL0cwQ05Gdz09IiwibWFjIjoiZDk4ZTYwMWNlYmEyMTc5MTNiMWU5YTJhNDFkMDA2YmFkNmQ3MmE5ZGEzNDA1OWViZThjMjZmZTM3NjAxMzU4NSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:26\nConnection ID: police_hospital\nProfile: 30 MB 141ranvid\nService: any\nRouter comment: Police','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:26','2026-08-10 23:09:26'),
(206,'2','user','Police','Not provided',NULL,'police_sp_home','police_sp_home','eyJpdiI6IjNidWEzQmlNV3lSOXlaclJMaUlZRnc9PSIsInZhbHVlIjoib0kvRlExbDY2Y0VKM1gyYU93ZUxXdz09IiwibWFjIjoiZWExZmIwODIwMjIwNjRjNWZmOTcyNmExNjVkM2FiNTllOTYyOTU1NDZlOWEwZjllNzRhNjcwZGZlZjMwN2JiMCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: police_sp_home\nProfile: 40 MB 180 IP\nService: any\nRouter comment: Police','inactive',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(207,'2','user','Police','Not provided',NULL,'police_sp_office','police_sp_office','eyJpdiI6Ik80YlFpNms5MmsxYjgvMlE5Q3lmL2c9PSIsInZhbHVlIjoiVGE3K3hjMDluaUFjUGVjTk5WT25kdz09IiwibWFjIjoiNGIxMWE4MzFkMzczYWZhNmRlNTAwY2Q2OGJiMTEyMmY0ZTQ4ZWNiYjA4Yzc3OWNiNjljYjgzZDg1NjZiMTYwMyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: police_sp_office\nProfile: 40 MB 180 IP\nService: any\nRouter comment: Police','inactive',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(208,'2','user','Police','Not provided',NULL,'police_dsb_office','police_dsb_office','eyJpdiI6InNoVm5BNzZnSnB2cktFS0tlQmkvS0E9PSIsInZhbHVlIjoiMldRbXJzaWNuLzJ4NWI0b3N2akd5Zz09IiwibWFjIjoiYTJjYTk3MWEyMDE5YTZkOGMyYTlhOGYzYjQ2NjI5MTZjNzU5NjE4NzE3M2IxZjU0NTVmYTlkYjdmZTM0Mjk2ZiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: police_dsb_office\nProfile: 30 MB 141ranvid\nService: any\nRouter comment: Police','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(209,'2','user','Police','Not provided',NULL,'police_dio','police_dio','eyJpdiI6IjVKbWpMQnJiNWxWY1h0K254NjhTUXc9PSIsInZhbHVlIjoiSFJwNFFzTmtDVmVKQmUxODFNazRwQT09IiwibWFjIjoiMmUxZDcxNjcxYTc2YmEzOTI1ODhhOTFhZWI2Mjg1M2ZhYjNmNDFlYTU3NjMzYjM1MjE0ODQxOTRiNjk1YjJkNCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: police_dio\nProfile: 30 MB 141ranvid\nService: any\nRouter comment: Police','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(210,'2','user','Police','Not provided',NULL,'police_ict','police_ict','eyJpdiI6ImI4YVpEaVljaUxxbHpaVmNaMlZIdUE9PSIsInZhbHVlIjoiV21kY1B1Zzc5SUljVkg2UVltaXROQT09IiwibWFjIjoiYWQ1ZWNjMDE1YjYzNTU1NzQ3Njk2NGVmZTU3ZGYxYjY0YTQ0OGE5NzgxYThiYjM5NzYyZjMwMWJlOGNkMThlOCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: police_ict\nProfile: 30 MB 141ranvid\nService: any\nRouter comment: Police','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(211,'2','user','Police','Not provided',NULL,'police_db_si','police_db_si','eyJpdiI6InFIYXFGU3FKOXN3cDFHdGJlbU5lWUE9PSIsInZhbHVlIjoiZUN0blU4MCtBelpPcFNHQTBOOHFIUT09IiwibWFjIjoiMjJiNjhhMjViNDBiMDI2YWY1MjU2MmFkYWFlMzZiODBkODI1M2MzMTY0MTFlZWM2Y2VkZDVkYTUwMDY0MDgwNSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: police_db_si\nProfile: 30 MB 141ranvid\nService: any\nRouter comment: Police','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(212,'2','user','Police','Not provided',NULL,'police_mt_office','police_mt_office','eyJpdiI6ImpBendNMzE3K0pNbmErb1NJRTVpVGc9PSIsInZhbHVlIjoialVvblk1NXgxUFBISmVSdzd6YjFZUT09IiwibWFjIjoiN2MyZWE5MzRkZmNiMGRjOGY3MzBmOTkwMzg3Mjc3NDAxMTU3ZmE1YmMyMTRhYTMzM2Q2ODkwNWQzYjZhZWYzZiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: police_mt_office\nProfile: 30 MB 141ranvid\nService: any\nRouter comment: Police','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(213,'2','user','Police','Not provided',NULL,'police_ration','police_ration','eyJpdiI6InZHdWtXUDRVQzUzSjlEYVBhd3RzVEE9PSIsInZhbHVlIjoiaEV2Zy93TzJlVFVXcXJxbjhkZ0p6Zz09IiwibWFjIjoiYjliNDc0OTk4OTRlMzNlOTQ3ZTgzOWMxNjUxYzg0OTc4ZmI4MGMzNjc5MDRkZGFjMTMwNGNjN2RkZjNmMTdmMSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: police_ration\nProfile: 30 MB 141ranvid\nService: any\nRouter comment: Police','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(214,'2','user','Police','Not provided',NULL,'police_ri','police_ri','eyJpdiI6IjU0NjdlbGNDZTQ5Qy9xTzdDTXZVRXc9PSIsInZhbHVlIjoiTmVodm9QQklld2RHNVduaVREcTRNZz09IiwibWFjIjoiNTczMzcyYTFmYmRkOWZmN2JiNzdlNTRlYWNkYzhmNDMzMWQ3ODBiMzkzZDNkYzBiZDViYTIyZDIyY2NkZDg0NSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: police_ri\nProfile: 30 MB 141ranvid\nService: any\nRouter comment: Police','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(215,'2','user','Police','Not provided',NULL,'police_punac','police_punac','eyJpdiI6ImEzdG50RVoxQjdFNnBqTGRTYmU1Z2c9PSIsInZhbHVlIjoia3NueFl4ODNEQ3FlMmdUUldzWW5VQT09IiwibWFjIjoiOGVmOWUzY2NiMWIxMWZiMGE4YjU1OTIyNmI4YzllMDM3YzYxY2EyYzUzMWQ5NTBjZDE4N2U0OGFmYTBiZDIzZCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: police_punac\nProfile: 30 MB 141ranvid\nService: any\nRouter comment: Police','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(216,'2','user','Police','Not provided',NULL,'police_reserve','police_reserve','eyJpdiI6IlNmbktYK0hFcmlMZzF0bWZ6RU1aUHc9PSIsInZhbHVlIjoiNDN4dGpJWExNeVZnYk0xREhOQXNHZz09IiwibWFjIjoiZWNjNjdhZTk3MjE2MDk3OTliYTY4OTM4YTVlYzAxMDkwNTdkNzgwYTA4ZDM0NTgyOWQzODRiN2YzZTc5MGUyZCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: police_reserve\nProfile: 30 MB 141ranvid\nService: any\nRouter comment: Police','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(217,'2','user','Police','Not provided',NULL,'police_stano_1','police_stano_1','eyJpdiI6IlhsZlB3VmRqY3ltMkhPS3JBWVFybGc9PSIsInZhbHVlIjoiSnNORUtpenhka2RRUHVXSjdQenMyUT09IiwibWFjIjoiZWRjMTdkYTczODFjMWU0NzY3NjNiNjE2YTU1YzczZmQ2ZGUyOGVlNThmMTY4ZjZhZDE2NzM3NDIxMTFjYzVlNSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: police_stano_1\nProfile: 30 MB 141ranvid\nService: any\nRouter comment: Police','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(218,'2','user','Police','Not provided',NULL,'police_reserve_ro1','police_reserve_ro1','eyJpdiI6ImQxa01DaHNGeUN3UmJLV1lta2VOYlE9PSIsInZhbHVlIjoiTFg4MU03Q01hZjJqT085WHZVOUZPZz09IiwibWFjIjoiYmI5ZWFkY2QwNmUxNGQ3YjE3YzY5M2Q3ODZmMjQ0MTRjMWIxZjQ4ZGQ2MmQ3Y2M2NzhkYTlhYzQzMzUwMTEzYiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: police_reserve_ro1\nProfile: 30 MB 141ranvid\nService: any\nRouter comment: Police','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(219,'2','user','Police','Not provided',NULL,'police_head_ass','police_head_ass','eyJpdiI6IndtczA1Tmlra3dSa1ZlOVROQTd5ZUE9PSIsInZhbHVlIjoid1JzdmVLRUI2VnpZaTVibktPVnI2UT09IiwibWFjIjoiYTNmZjZhOGYyZDQ2Y2IwZGI0ZTkxZWEzZTY0OWViOGFhYjJjYjQzZjk2MDgxZjc4N2IzYzE3ZjYwNzYwODg5OCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: police_head_ass\nProfile: 30 MB 141ranvid\nService: any\nRouter comment: Police','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(220,'2','user','Police','Not provided',NULL,'police_hospital_doctor','police_hospital_doctor','eyJpdiI6Im5QeXV5aFZsMTgwZ1JEZGFWZVU1NUE9PSIsInZhbHVlIjoiQWJ0a1JiZVNqM0VhdHB2Y3NvVHd5dz09IiwibWFjIjoiOWU2MGZkZGU3Y2UyNzZhNjQ0MTI4MzdlZjJkYjI2MGJiNTgyYzZiYjM2ZmFkMjdkMjliY2U4Y2ZlZDMzMDViOCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: police_hospital_doctor\nProfile: 30 MB 141ranvid\nService: any\nRouter comment: Police','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(221,'2','user','Anike Jony vi Thanar mor','Not provided',NULL,'shohel_add','shohel_add','eyJpdiI6IlhQU0ZnWndFVVUwcm5ObjY2K3kra1E9PSIsInZhbHVlIjoia29iR3J2a3g5Y1BoaGlCQ0lrWllidz09IiwibWFjIjoiZTA2NzM5NzNlYWFhYzI5MGVjMWY0M2NhNGZlZTc3YmE5MzgyOWNiOTZlOWI2YWQ2Y2FhZWI4NTY3OGY0NGJiMCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: shohel_add\nProfile: 30 MB Saifulkst\nService: any\nRouter comment: Anike Jony vi Thanar mor','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(222,'2','user','shofiqstar','Not provided',NULL,'shofiqstar','shofiqstar','eyJpdiI6Ik9ZUEZnNlZ2QTR3YThud0R3UUZlZ2c9PSIsInZhbHVlIjoiamNZZ3U1QUlxdWptZDVLcFRoV2tFZz09IiwibWFjIjoiNDI1N2MyZjY3OGFmZTU1MjYzZjcwNTNjMzdhY2M1NzViMjQxMTJmYWIzNTEwM2I3OWJlOWNmODIxZWIwN2YyYSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: shofiqstar\nProfile: 200 Mb Star\nService: any\nRouter comment: none','inactive',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(223,'2','user','Mosan','Not provided',NULL,'rabby','rabby','eyJpdiI6ImtEOFRXZjZHdjNLMFpjbEZCYlhmWnc9PSIsInZhbHVlIjoiWDgzV2VFcWQwTWV6SUZQVVZjWkgwQT09IiwibWFjIjoiZGY4MzAyNGM0OGYzNDZkYTEyMTQ2NGIzYWNhNjY0MDliMDhhNWJiYTQzOGU2NTRiNjAxMjE1MDI0NDNjYjc0ZCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: rabby\nProfile: 200 Mb Star\nService: any\nRouter comment: Mosan','inactive',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(224,'2','user','shofiq','Not provided',NULL,'rashidago','rashidago','eyJpdiI6IlNVc1BnaWdDMXl2SHlWdzZRQmNSN3c9PSIsInZhbHVlIjoiVG9tN2FWZHB6TGFNcTFnVlJYYVIrdz09IiwibWFjIjoiMTNjZjMwOGJjYzMyNjA3MzZmM2NlYzg3NWMyMDk1NzlmMjIzNzZlMDg3M2YxNDQ1ODU0ZDBjNzE3YjcwOWJlNyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: rashidago\nProfile: 30 Mb Star\nService: any\nRouter comment: shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(225,'2','user','Shofiq','Not provided',NULL,'shahinmp','shahinmp','eyJpdiI6IjZSYit0SmdJancwajB1S0NjL2hDY1E9PSIsInZhbHVlIjoiVXBZQnRUWnBWc1RRSk1CT2QveUFCQT09IiwibWFjIjoiYmU1YWM4MTM4MzAxNGI5MTUxNjBlNzFmYmIyNDliNDdiYjgzOTcxY2Y5OTE4YjY5MWViNmZkNTExZmI5Y2ZmNiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: shahinmp\nProfile: 30 MB Saifulkst\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(226,'2','user','Shofiq','Not provided',NULL,'shamim6r','shamim6r','eyJpdiI6Ill2YmhMTnNoTEp4Y0xESmpiZEQvZXc9PSIsInZhbHVlIjoieFErRmswMTh4SjU2YVE1ZDN5elBHdz09IiwibWFjIjoiZThmOGZlNTRiMjhlMzgxZmQwMmM3ZDNkMDI3ZmI3MzIyNDRkNjQ0NjRmMTM1MGQ5MzFkM2ZjNmFkYTA4NDJkOSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: shamim6r\nProfile: 30 Mb_Travelshouse\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(227,'2','user','Shofiq','Not provided',NULL,'ptijui','ptijui','eyJpdiI6Im01aG9rU3o1RzdSN0ZrN05CMUw1SlE9PSIsInZhbHVlIjoiSFNVSmppYkhCZlRVb2cva01vWEgyQT09IiwibWFjIjoiN2ZlYmY1ZTdjYzk2NDE4OGYwOWExOTZlMThkOGFiZGUyNzE0MDc3ZDM5MGZjYWZlMzI1YTM4NDI0MzEwNjRkYyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: ptijui\nProfile: 30 MB Saifulkst\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(228,'2','user','Anike','Not provided',NULL,'premkst','premkst','eyJpdiI6IlpLTFRGWWRoQjd3Z0ZJb3JxOW8zTXc9PSIsInZhbHVlIjoiODBMVmk4RWllRW5yQ2h2bUlCRjFtZz09IiwibWFjIjoiYzMyMTIyZmNmNjdjNTM5OGM4MjZkMDFiNzkxYjM1MGNlZmNmOWQ1Zjc3NzRmZTIyMjUwNDQ3MjYxYTYwNmJiZCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: premkst\nProfile: 50 Mb_Travelshouse\nService: pppoe\nRouter comment: Anike','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(229,'2','user','Shofiq','Not provided',NULL,'samiulkst','samiulkst','eyJpdiI6IkhpSm1POVdnMFppN1h5MEdrMmQxbXc9PSIsInZhbHVlIjoiQVdNKzRDZTBzbklNN3kwazRRVHhjUT09IiwibWFjIjoiMmE1N2EzNDQzODY3MmI4YWJhMWZhNzcyZWIyZGEyMGZkMmZlMjA1MGRlNGMxNDFkYjRhMWYyMzlkZmQ1MzgwNCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: samiulkst\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(230,'2','user','Shofiq','Not provided',NULL,'shamimakst','shamimakst','eyJpdiI6IkJFN1YvTkZ6Y0ZIb1c4OE1OaEpNYWc9PSIsInZhbHVlIjoicDNZOEp2YTVrUkpSNTZ3anVxM1FLUT09IiwibWFjIjoiYTRhYzVlMzJkNzMzYjc5NmYwOGU3NWY3ZDIyNzg5M2RjMWQ5ZDIxMGJjYjQzY2VlNzI1NjM0ODU3ZGRkODhmNSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: shamimakst\nProfile: 30 MB Saifulkst\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(231,'2','user','Shofiq','Not provided',NULL,'rabtasir','rabtasir','eyJpdiI6Ikt2OEFpSXBWUldydWNQSjArdzRxcXc9PSIsInZhbHVlIjoiSk81MkZjZ1ZGWWx2cjliV3lKdVhlQT09IiwibWFjIjoiMTlkYTU0Y2NjMTAzNmFlZmYxMTdlOWJiOGQ4YmYzMmEwOGJiYmZjNmIwZDhiNzUyYjI2MWY1YzE1NTQwZjhmNiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: rabtasir\nProfile: 30 MB Saifulkst\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(232,'2','user','Shofiq','Not provided',NULL,'roshidago','roshidago','eyJpdiI6ImdkalRoM2R2bGxsSFZjamFvWkVHT0E9PSIsInZhbHVlIjoic1d1SGliVGU2bmhJemlIRGEwVjdvZz09IiwibWFjIjoiOGFhNWQ2MWIwYWQ4ZTc3MmU4NDA5NjE1OGQ4OTViNTY0MmRjOTUxZjZmMmE2NzUwNTQ4ODhhYjNiYTI5ODFhNiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: roshidago\nProfile: 30 MB Saifulkst\nService: pppoe\nRouter comment: Shofiq','inactive',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(233,'2','user','Shofiq','Not provided',NULL,'probash','probash','eyJpdiI6IjI2VDFUVnJtVjg3K0l3MzdIOFVMb0E9PSIsInZhbHVlIjoid3FncFFDekRwYzdBRlFiSmxGZjRoUT09IiwibWFjIjoiNWMxZWY0OGE4NWU2NmNhODhlODZiYjQ4ZTA3NDhhNDQ2ZWY5ZTY4N2JlMWZiODZhODFiOWJjZGRiZjU1ZWIwYyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: probash\nProfile: 30 MB Saifulkst\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(234,'2','user','Shofiq','Not provided',NULL,'ronjitvai','ronjitvai','eyJpdiI6IkYvb3l0TjZqZTE0aE1yazdaY0ZjTlE9PSIsInZhbHVlIjoiYXdqVFBkNStEdVFPL0dsbVk2VWgzdz09IiwibWFjIjoiYzU1NjE4ZDdiMjc5MmFmMDEyYzAxMzYzODE4MDNiMTJjZDYwMmM2ODE1YjRlZjdjZTkzZDhhMWRhMTg0MmRiNyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: ronjitvai\nProfile: 30 MB Saifulkst\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(235,'2','user','Anike','Not provided',NULL,'shopna_surovi_aunt','shopna_surovi_aunt','eyJpdiI6IlVicmZ4N3JOUC84MytRRHVpekVVcEE9PSIsInZhbHVlIjoiQ1BoblNRdlRaS3RHREVlMlhxWFBnUT09IiwibWFjIjoiYzEwZGExN2E0YzIwNDgyMTkzZjEwNWY0NGRhZmQ0MjA1NGYwNmQzMmM5ZGJkYTFmNGY5ZjM0MDZmYTcxNWExOCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: shopna_surovi_aunt\nProfile: 30 Mb_Travelshouse\nService: pppoe\nRouter comment: Anike','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(236,'2','user','police_field','Not provided',NULL,'police_field','police_field','eyJpdiI6Ilp0aWRMampPdFQ3YnR0Y2pnODhHSXc9PSIsInZhbHVlIjoiQTJ1bWlGNEo3ZC9BTEtCVzUrZTRMdz09IiwibWFjIjoiNDAwNTBiM2JhODQ0MDgyN2Q2MDM2NDc0MGJjMjhjMTNjNjg4MjdlY2FlZTNlMzliYzljYzRkNDdhMGRjMjE2YSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: police_field\nProfile: 50 MB shena_nir\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(237,'2','user','Shofiq','Not provided',NULL,'shabag','shabag','eyJpdiI6Ik9uUEh2NnpyMEpZQVdUcTk0Z1IxWVE9PSIsInZhbHVlIjoialRsbTJZWWFCVW9FQlhBcGQwdDVFdz09IiwibWFjIjoiZDkxNzU4OTNhMjgyM2UyMGY5YzdmOWJhNDIxYmJjYmI1ODliNjIwNjRhNzEwYTBkNzBhZjI5ZGQ2OTM4NjVmMiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: shabag\nProfile: 30 MB Saifulkst\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(238,'2','user','Anike','Not provided',NULL,'rafi12','rafi12','eyJpdiI6IjJnZ0NpazVQSmsrQ1Y5TCtRRm5Ha0E9PSIsInZhbHVlIjoiaGZ3Q0V2d2RsZDRLMVdoZ2VpeHBHUT09IiwibWFjIjoiYzY3MDMzMWFkZjdkNTI0ZmFiYzdmNDMwZGQ4OWI1OGI0NzI3NDY5MGE0NzA0ZTMwMjVkMjEzYjBkNzAxNDU5OCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: rafi12\nProfile: 30 MB Saifulkst\nService: pppoe\nRouter comment: Anike','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(239,'2','user','Anike','Not provided',NULL,'rajon_atiq','rajon_atiq','eyJpdiI6IlRGWjlNc0J4VUdqNkk4MTBjSGR5R2c9PSIsInZhbHVlIjoiSEtMQkhXaGUxWUk2L0lZOTQ0ZzV0QT09IiwibWFjIjoiMDdmMjRmMjQzN2NlZDg2MTJmYmNmOWFjZjY5NDViMjc2ZTcyYzU4MDVmYzAwOWNjOWViOGU0ZWFmZDllOTRjMCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: rajon_atiq\nProfile: 30 MB govt_college\nService: pppoe\nRouter comment: Anike','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(240,'2','user','Shofiq','Not provided',NULL,'shomobyb','shomobyb','eyJpdiI6IlB0ZG9mUHh1WlhyWklpUm94eXRGWFE9PSIsInZhbHVlIjoiSTdBRmZQbkhaQ09nMGNwbnBJbzZTUT09IiwibWFjIjoiMmZiMDQ5Mzc3YWM0NjUwNWFkNTc3N2MzYmJjMzMzYWQxMjJiYTVkNGYzOWNlMDNmZjZkOTRlOTExODU2YzZlNSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: shomobyb\nProfile: 50 MB mosharof_bgoly\nService: any\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(241,'2','user','shofiq','Not provided',NULL,'shohan_saddam','shohan_saddam','eyJpdiI6IlZOaTZHcDZUZ3phMGNlYlRYdkQzdHc9PSIsInZhbHVlIjoiaWVjWWx4cVhHcUw0eFB3SThMc0orQT09IiwibWFjIjoiNmNiMjMxN2U3NzFhNTM1ODJhMjczMDc3ZTVhYmNmYmU0NjMxYTRkMGI1YjY1NDA2YzBjMzg0ZTFkYWFiOWE0NiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: shohan_saddam\nProfile: 30 Mb_Travelshouse\nService: any\nRouter comment: shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(242,'2','user','Anike 24/9/25 - 800tk','Not provided',NULL,'police_imran','police_imran','eyJpdiI6IlhlblhUVXdnR2FnWUV5TFg4NVdKa3c9PSIsInZhbHVlIjoiblRXa1M4eGI5MWdhTFJkczRjd2QyZz09IiwibWFjIjoiNWNmN2QyZmRkNzAzNDc2N2MyMzI3NDEzODU1YzQ4MzNmOWMzNTkyMzkzOGRmMjg3NDU3OWVlNWUwZWM3MGUwMiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: police_imran\nProfile: 50 Mb_Travelshouse\nService: pppoe\nRouter comment: Anike 24/9/25 - 800tk','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(243,'2','user','Anike','Not provided',NULL,'raja_uncle','raja_uncle','eyJpdiI6InpyZ3FtUkRhcURzMjZnVW5MS3NxUnc9PSIsInZhbHVlIjoiV1NDZ0RBZ1BVZWUweC8rM3pDTUZ6dz09IiwibWFjIjoiOGYxZTgyZjMxM2Y2NmExNGFiYWE4ZGQ5ZDY0ODI3MTU1MGNhNDE3MDNjOTUxZWI4Y2EwMTMyYzg5ZWNiMjJhNiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: raja_uncle\nProfile: 30 MB 141ranvid\nService: pppoe\nRouter comment: Anike','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(244,'2','user','Anike','Not provided',NULL,'rudro_dip','rudro_dip','eyJpdiI6InlvSUZmRHNCOGhhSjN3WWRhT1M1NlE9PSIsInZhbHVlIjoiOVBvSmhhV2NuL3VsOVRXalpoQkIxUT09IiwibWFjIjoiMmYxMzAyNTY4YzUxZDI0ZTk5NGUxMTZhY2M1ZDFjNDVhMTJkYWE2YzlhM2NlMjNkY2U3NzZkYjEyMjQwOGY4ZCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: rudro_dip\nProfile: 30 MB Saifulkst\nService: pppoe\nRouter comment: Anike','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(245,'2','user','Anike','Not provided',NULL,'Rakibhujur','Rakibhujur','eyJpdiI6Ii9vcW9zd2MzNU9RdHVaYVJ0U1F2WGc9PSIsInZhbHVlIjoibjlwZnJaQVcvL25hcW9lNUY0Q05DQT09IiwibWFjIjoiMjdmMzc0YjQ0YzMxMDIyYTNmODQ3YTYwMGYzNzEwMWRjMjljZTBkOGIzODc2YTU0MzY5M2M3NmZiNjNmMDFjZCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: Rakibhujur\nProfile: 30 MB govt_college\nService: pppoe\nRouter comment: Anike','inactive',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(246,'2','user','shofiq','Not provided',NULL,'rafitkst','rafitkst','eyJpdiI6IkxudjdZRmVvY28xS0JPYkFHR0YyZkE9PSIsInZhbHVlIjoiaVJrWFp0czg1eGpnYnNyMmQ2V0FWdz09IiwibWFjIjoiMTJhNTFlZTY0ZWY3OWNlZmRlODVkNzVmZmE3ZWY3M2E2NmM4Y2NmMjRiYzFjOWMyMzk0YWM0N2M1MTBhYjg0OSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: rafitkst\nProfile: 30 MB shena_nir\nService: any\nRouter comment: shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(247,'2','user','Shofiq','Not provided',NULL,'shikha_mukul','shikha_mukul','eyJpdiI6InUyZGRVZ0tCVWIrbDRWN0s1NEdmSVE9PSIsInZhbHVlIjoiaUI0NTFtQVZ0TzUyTGhXQmhuc1lVdz09IiwibWFjIjoiMjAxMTBkZGFkYWEzNzA2MDg4ZjExMGU0NmIyZGRhMGM1MmVmOTBkZjI2Y2U2YTBhYTIzMzg1ODk1ZGM0YWJkNSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: shikha_mukul\nProfile: 30 MB Lgedks\nService: any\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(248,'2','user','Shofiq','Not provided',NULL,'rohan_mukul','rohan_mukul','eyJpdiI6IkpOZ2x1TExSc3YzaU1DWXJSaWNLNGc9PSIsInZhbHVlIjoiY2h0dmRMS1Y1TGZjNlFSTHU5S2pDZz09IiwibWFjIjoiZGRlMGEwYTUzZWQwYjdjOGI0MDIxMTk5ZGU3M2ZkOTNkOTg3NTIzMzk2NGQxYWYxZThkZDBhOWNkMjYxMDcyYSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: rohan_mukul\nProfile: 30 Mb_Travelshouse\nService: any\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(249,'2','user','Shofiq','Not provided',NULL,'raisulsir','raisulsir','eyJpdiI6IlphRDFQa1VyV2RySWxMRXVOOEFYdUE9PSIsInZhbHVlIjoiTGFFNXRXNXZOemphVG9HWmUzVFRMdz09IiwibWFjIjoiMzJiMzFlYTRhN2ZlZmNlYmM1NjEyNjg3Y2EwYTAzM2Y1MDJmOTAyODYxNjVlYTMwMTg1ZjFmMjVhOWIyMWI5MyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: raisulsir\nProfile: 30 Mb_Travelshouse\nService: any\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(250,'2','user','rifab','Not provided',NULL,'rifab','rifab','eyJpdiI6Ill4S01YN2NPYjlObjB3NmhDZG9rWVE9PSIsInZhbHVlIjoiYUUxNVVNbDdHZDZmaVlMU2tJcEkyUT09IiwibWFjIjoiY2Y5Zjk3MDE3ODM1NTdlYTE3YjJkMTdmYWY5M2E5NjFlNDQ4NWRhODc1ZWIyNTNmOWFhNDI5OTVjNzA0YjZlMSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: rifab\nProfile: 30 Mb Star\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(251,'2','user','shahed_basha','Not provided',NULL,'shahed_basha','shahed_basha','eyJpdiI6Im5kSHB6N3Z5aWc5M1kxeS9KK3NDckE9PSIsInZhbHVlIjoiOExwcXRZSWtTSjFMZGZNUUlMRlpHUT09IiwibWFjIjoiYWQyOWQ4ZWRkMDg4MDA3NDVkZDRiMzBlZGRhMWJiMzY2MjY5NDM2MzJkZDRhOTc3ZTg1OTgxMjA1MjU2OGQ2YSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: shahed_basha\nProfile: 30 MB Saifulkst\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(252,'2','user','Shofiq','Not provided',NULL,'shadid_elc','shadid_elc','eyJpdiI6Ik50eDVsbDR0TUdlRFpHMFNlVUpKOEE9PSIsInZhbHVlIjoiRHlka1djMTZxbjNwKzVyNER1dy9EUT09IiwibWFjIjoiNmNlMTQ3OTgyYWFlYTZiZTg2MmFhMjRlN2Q5YzBlYzQ4YTE4NDkwN2UzZjA3NDc3MmEwODc0NzZiYWJhNTI4OSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: shadid_elc\nProfile: 30 MB Saifulkst\nService: any\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(253,'2','user','Anike','Not provided',NULL,'police_imran_home','police_imran_home','eyJpdiI6Ilp0NXdHczJxYUNRSklKUVRlMzRaanc9PSIsInZhbHVlIjoiNGx3UUkxNHk0c21Vc0Z5c3BsanRXZz09IiwibWFjIjoiYmExOWVkYWUwMzQ3ZjQwZDMzYjM0ZmEwODk1ZjFmMjkyMWI0ZWYyM2FkZjRiYmEzMzcyNTE2OTQzYjcxYWQyOCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:27\nConnection ID: police_imran_home\nProfile: 50 Mb_Travelshouse\nService: any\nRouter comment: Anike','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:27','2026-08-10 23:09:27'),
(254,'2','user','sabatr','Not provided',NULL,'sabatr','sabatr','eyJpdiI6ImFIZkJnU3htbnpkRnR2anl3UXltcHc9PSIsInZhbHVlIjoicm1HM0ZSdHhqbFgxelBFZ0FpaklKZz09IiwibWFjIjoiZTQyZDE1NzgzNDBmYjQxMGRiZjVhZTI2ZGExNmVjZGNkYzYzMjFhYmE2N2UxNjI4ZmI5Yzk5MjcyMTNkNzFjMiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: sabatr\nProfile: 30 Mb_Travelshouse\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(255,'2','user','setu6','Not provided',NULL,'setu6','setu6','eyJpdiI6ImF6MFh5anZrMDh4TERHQmpiZ0tHc2c9PSIsInZhbHVlIjoiQWp3MlFvaDFGMEN5R3ZhRDhxdENUUT09IiwibWFjIjoiMDA3NmM2NTU3N2QyOGUyM2JlNTNlYzBmNjBiYjY3OWYzMTIxOWVmZDQ4NTkxZDNhOTM2MzRhOWQwOGU5N2FkMSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: setu6\nProfile: 50 MB shena_nir\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(256,'2','user','pwdasst','Not provided',NULL,'pwdasst','pwdasst','eyJpdiI6IkZZSDJhMjNqQ2dPVnJvSUd1a0xpaHc9PSIsInZhbHVlIjoidWJKa2kxV1VSM2E3U0ovMFpYa0hrdz09IiwibWFjIjoiMjFhOGNjNDRjYTkzNTM2OThhOWIwNTIxMzVkZDNmOWNmYTRiZDRmZmRmNzFiYzRiYjgxZDgyNTA4YzU4YzQ4NCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: pwdasst\nProfile: 30 Mb Star\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(257,'2','user','rublevai','Not provided',NULL,'rublevai','rublevai','eyJpdiI6ImQvdmpsZmE2Rkp6M0dKd1VkczJwU3c9PSIsInZhbHVlIjoibE1XWTMrV2F6TE1jenpRdkRXeEhNdz09IiwibWFjIjoiNTZmMDIzMTg4ZTQ3ZmM5YzQ3NTUxMzVhNWZiMDBhYTAyMjQ1YTRlYWU1ZDBjNmYwZjhiODQ2ZmJmMTZmZTBlYiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: rublevai\nProfile: 30 Mb_Travelshouse\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(258,'2','user','ruble_basha','Not provided',NULL,'ruble_basha','ruble_basha','eyJpdiI6IjdmSzg5SnBRclc3MWxjalJHZXRpcmc9PSIsInZhbHVlIjoiTGdZaUcwWUZsSER6YlQvQ3VqTFg4Zz09IiwibWFjIjoiZjJkY2RhNjRiYTVkOWEwYjBiN2Y5ZGJmYTkwZjdlNTdjYzM1N2JlMTIwMTlhNGUzMmU0MDE3Mjc3NDRiNjdkMCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: ruble_basha\nProfile: 30 MB govt_college\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(259,'2','user','rina_court','Not provided',NULL,'rina_court','rina_court','eyJpdiI6InJtTEVDeGlQK2h3VnhzUG9pK0Q5SGc9PSIsInZhbHVlIjoiMWI5eGx6SE4xSjg3ZElxRlg5bWlNdz09IiwibWFjIjoiODUwNmIwYTY1NWQ2NDlhOTZlNGQ0YjM3MWQzNGQwYjkzZmRlOGEzMTE5M2FkNGJiNThkYzBlODFhYzY0MjcyNSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: rina_court\nProfile: 30 MB govt_college\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(260,'2','user','rumman_ basha','Not provided',NULL,'rumman_ basha','rumman_ basha','eyJpdiI6IndBelNPNCs4cWVlTTNCa2x5eE5BL1E9PSIsInZhbHVlIjoiS2NLS0pyVU43a1IrQ1JVUFhobFNSUT09IiwibWFjIjoiNTcyODkxMzA5NzRhZDZmYjI3ZDU5YWM4NGIwNzdiYWY2NTVlMWVjNWYxYjIyM2ZhODE5NGE3Njk0M2Q0NzY5NyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: rumman_ basha\nProfile: 30 MB govt_college\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(261,'2','user','shahin_shop','Not provided',NULL,'shahin_shop','shahin_shop','eyJpdiI6ImJFYVlZQ0tjQ1ZDM09NK0Q0OXNqSHc9PSIsInZhbHVlIjoiNTdwVXd2MVI0dll4T3ZoQnpLZjFtdz09IiwibWFjIjoiYWYxZTMzYzA5YzMwYjJlNmQ1YzBlNWEzNjM3MmIwZDI4MjhiODc0ZDU0YTY0MDc4MWQ3YzExMTQ0OWFhMzg4NiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: shahin_shop\nProfile: 50 MB mosharof_bgoly\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(262,'2','user','salea_ator','Not provided',NULL,'salea_ator','salea_ator','eyJpdiI6Ik1wTm40ZXZyQ05kK1k5NlNERk9vc1E9PSIsInZhbHVlIjoidENTT0p1NWpZaDJVQ1pKQlhTZFk5QT09IiwibWFjIjoiYTAzMGQ1NGM4NTYzYjZmZjI2MTEwNmRmNGY1OWEyZmQ3MmUxOTA1NjI1OWVmYzU5NjJlOWM5ODE1NmEwMjQxMyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: salea_ator\nProfile: 30 MB ZIlas\nService: any\nRouter comment: none','inactive',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(263,'2','user','shofiqks','Not provided',NULL,'shofiqks','shofiqks','eyJpdiI6Ikd1WE92SkIrbTdZRzJibGhIM0RzeWc9PSIsInZhbHVlIjoiRGIrRkRIR0VhVnIzcG1LT3AyL1E2UT09IiwibWFjIjoiNmY4ZTQ4OTdiNDZlOTEyMzNjZjY2YjM2MTk3NGEyZjJjOTI2ZTA1NGE2ODg4ZmQ1MWI0OTYwOGE3MTI1MzcwOSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: shofiqks\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: none','inactive',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(264,'2','user','salim_samiul_vi','Not provided',NULL,'salim_samiul_vi','salim_samiul_vi','eyJpdiI6ImQvZ1RWS1c1L0p1WjZUK2djU3JvUmc9PSIsInZhbHVlIjoibXdGbGl4YVMvOFc2bkFFcnE2MEJaQT09IiwibWFjIjoiNmQxOGEyY2M3YzUxZTBlOWU4NmVlODk5Y2E0YWQxMmIyYzZmMDkzZDUyOTY5ZDIyYWI4NDdkYjMzOTJjY2E0YSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: salim_samiul_vi\nProfile: 30 MB govt_college\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(265,'2','user','sajibkst','Not provided',NULL,'sajibkst','sajibkst','eyJpdiI6ImpHQ2VEd3FPQTlEMWJJNnRrZER5VlE9PSIsInZhbHVlIjoiOG1TUUFDWnc1TEdCNkJlUlYxMVhLQT09IiwibWFjIjoiYTZjYWUxMGM1NThlZjI0ZDgyMjQ1MGNiMzc0YTNkODNiYWFkMWU2MmFlYzFiNzQ2MzNlMzQyYjg4ZjQxNTBmOSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: sajibkst\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(266,'2','user','shamimpq','Not provided',NULL,'shamimpq','shamimpq','eyJpdiI6Imk4SnJreDh6YlBhTXI4bmVWYmJheEE9PSIsInZhbHVlIjoiNHo5WDlyR01keURvQ08wMDNBdTExdz09IiwibWFjIjoiZjFmYTQyY2YxYWMzODI4MGNlOGJkMjI5YWFiYThiYzYyMzZiODgyMDgwODNhYmQ2MGE2MTgwYmVmZmI4YzRjNCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: shamimpq\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(267,'2','user','pwdacc','Not provided',NULL,'pwdacc','pwdacc','eyJpdiI6IituOWpqZHNYRWllRXpIbk50MUorZkE9PSIsInZhbHVlIjoiWW4veE51S284TXVjUXhVVlRoVyt6Zz09IiwibWFjIjoiYjhmYTRkNWQ3ZjEwNDdmYjQ2NTY5YjExNmRiNTA2OTRiYWY3ZjhmM2IxZTEzMTRhNGNlZWFiNzg0NTlhMWYzZiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: pwdacc\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(268,'2','user','rantukb','Not provided',NULL,'rantukb','rantukb','eyJpdiI6ImJ6bFI0SzlSUG9IOHZwV0NENXJab1E9PSIsInZhbHVlIjoiKzZ1aDBYLzBmSHF5R2V4MXZTVUVjZz09IiwibWFjIjoiNzZiNWJhNWEyMWE4NzhlNWQ0NDVkNWQxYmUzMDdlYjE1OTJlN2YwNmEzYTZlZGY5YWIwNzBjZGY2YTIzMGM4ZiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: rantukb\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(269,'2','user','saifulstation','Not provided',NULL,'saifulstation','saifulstation','eyJpdiI6IlQxMGdHKzA0UXRSQWpmV1NkdE1jZ3c9PSIsInZhbHVlIjoieWxNREdXOTYxY2c4RHR2SVZSazRFUT09IiwibWFjIjoiNDdkZGQ2ZTBhMjA1YmJjODg0M2ViNzkzNDliZjc5ZDJkOTg2YmQyOTRhNzY0YjI5YzdhY2MyN2VlNjc2NjAzMiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: saifulstation\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(270,'2','user','shahin_natural','Not provided',NULL,'shahin_natural','shahin_natural','eyJpdiI6IncvUytlZDJUUHpJVHRsTWRISXpEVXc9PSIsInZhbHVlIjoiSGNRUE90OVg3RjBkRUovWUhVNXZBdz09IiwibWFjIjoiZDUzMGViNDdiZmRhNDIwMTkxYzRmNDg4NGIxNWIyMTk0NWJlMmYyOWJjZWZkZGJlZTUzMzNmMmY1ZGQwZDUyYyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: shahin_natural\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(271,'2','user','Anike KPS Firoj','Not provided',NULL,'rakibvai','rakibvai','eyJpdiI6IklVTnMzZXJLa2M5V1BZZHRRcG9yZkE9PSIsInZhbHVlIjoidFl2eXErdzZoU3NNcEZrNy9BSVVBdz09IiwibWFjIjoiOGRhYTIyNGRjNDYyZmMzNjY1ODE5MTYwZjQ2YjJjMmQxMTA4NDdlNmY4MTJmMmM3OTc0YmI4NDVjZTExZmExMyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: rakibvai\nProfile: 50 MB shena_nir\nService: pppoe\nRouter comment: Anike KPS Firoj','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(272,'2','user','refat_shena','Not provided',NULL,'refat_shena','refat_shena','eyJpdiI6Im40WjR3Zkk0T0dNL3A0eDQ0NnZyZ1E9PSIsInZhbHVlIjoiMUlCYVpwZmw0VXJwNXpFRE8zWFBqdz09IiwibWFjIjoiZmU4MWYwOWJhMDQ2MGNkNTVhYWVhMDg2NWNiYjliOWJmNjM4NWI0MTNiY2FlYjBkZjlhNmZhZDI4NThiMDc1OSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: refat_shena\nProfile: 30 MB shena_nir\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(273,'2','user','senanir','Not provided',NULL,'senanir','senanir','eyJpdiI6ImlvUlp2R2xWRm1oS1dSSjhaOU8zVXc9PSIsInZhbHVlIjoicWlxK2kwbTFTVE00bDZsTFdoamh0dz09IiwibWFjIjoiZWJlMzI4Y2VjMTdjZDg2Mjg3MzRiNGIzZGU1ZDllYjAwMTkxNWM0NWQzZWI0MDYwNDM0MWFkMzNjM2I4ZTQ1YiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: senanir\nProfile: 30 MB shena_nir\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(274,'2','user','rima_seba','Not provided',NULL,'rima_seba','rima_seba','eyJpdiI6InFVT2F2eE92RnNHV2t4N2lDSEt3cFE9PSIsInZhbHVlIjoiUUxrNTRIQUYzcUI4d25FVWFFSmE3UT09IiwibWFjIjoiMDMzNzM4MzNkZTg1ZTRjMTE1ZGYyZWE3NTY0MWI2ZmQ2MDM5MTA5NzYyOTVkMGFmNzMzN2FiZTJjOWQ2YzFlNyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: rima_seba\nProfile: 50 MB shena_nir\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(275,'2','user','roich_mama','Not provided',NULL,'roich_mama','roich_mama','eyJpdiI6Im1HZFQ5RDRoaFAxRGRoc3FPVTFIWHc9PSIsInZhbHVlIjoiSlFlZzNSN3BMVkg1bmx6bVZGd1E2Zz09IiwibWFjIjoiMTc0Y2ViZGJkNjA5NmNjMDBiY2JiYTFmOGU3NmQwYzA4OGY5YzQ2NmEyOTNiYTdmM2ZiM2RmMzQ3NTU4YjZhYyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: roich_mama\nProfile: 30 MB shena_nir\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(276,'2','user','shanto_saddam','Not provided',NULL,'shanto_saddam','shanto_saddam','eyJpdiI6ImhRQVQ0Q2YvVEtucGIvUTdhNlRNakE9PSIsInZhbHVlIjoiUm0zSDJsRjZIN29sd29VbU5FNjNHUT09IiwibWFjIjoiYTA0Mjg3NTEwNmQ2NDEyNzY2YmQ0OGY2NmJmYjNmNmI2OTQ5NmE2OWFkOGVkN2E4MmM3MmRjMTMwMzcyMDA3NSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: shanto_saddam\nProfile: 30 MB shena_nir\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(277,'2','user','ruhan_anam','Not provided',NULL,'ruhan_anam','ruhan_anam','eyJpdiI6IjUxTS9jb1Bsb3E1STdrQ1lzYXZidmc9PSIsInZhbHVlIjoiaW9qNGk2ekh6cTRkV002ZU1jZ29Udz09IiwibWFjIjoiMzA5ZTU3NDllODk4NTI5OWI4M2NjMmU4ZWU4MmZlNmYwNGI1ZDYwNzk5ZjFiY2QyODczMDE0YTY5MTVhZmIzYyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: ruhan_anam\nProfile: 30 MB shena_nir\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(278,'2','user','shopon_goshala','Not provided',NULL,'shopon_goshala','shopon_goshala','eyJpdiI6IlRaWVBUdDhmQXRXeVZpU05RZ3JyOWc9PSIsInZhbHVlIjoiSEkvcDhIWkxLUENLQXV4U0NkRWZMdz09IiwibWFjIjoiMDM3MGQzNDI2MDA3ZDFmN2UzMGJhOTRkYjIyODhlNWZiYjllYTIxNGNhZTVkMDMyYjUxMTRlMmQwNWM4YmIwOCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: shopon_goshala\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(279,'2','user','Police 21_4_26 @ 600 2 month advance from arshed','Not provided',NULL,'police_hospital_lab','police_hospital_lab','eyJpdiI6Im9TMVhCajJLWFhoMHAxOWxzdldld3c9PSIsInZhbHVlIjoiN1MycS8wc3VKVjFON2RkNTBOZ3lYZz09IiwibWFjIjoiMTNmMzhkNzU5ZDhiZjcxNmYwMWU2MmM1NzEyYjZjOTQzZTg4ZGI5MTgxZDc2NTk1MTcwZjJkMTBiOGY3ZmM0YSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: police_hospital_lab\nProfile: 50 Mb_Travelshouse\nService: any\nRouter comment: Police 21_4_26 @ 600 2 month advance from arshed','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(280,'2','user','shamol_dada','Not provided',NULL,'shamol_dada','shamol_dada','eyJpdiI6ImcvOFh2TWs1c2FZbldkL1ZUUFBTOHc9PSIsInZhbHVlIjoibFU2VnNGNlllWE01N2R5WGFuWTBaZz09IiwibWFjIjoiOTAwNjY5OWJlMmYyYzMzOWU5Y2VhMGFjNDVlZmU1ZWZhNDg5ZGJhMWI5MGQxNjU5MGQ5MGZhZjkzMGViMjM2YiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: shamol_dada\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(281,'2','user','rana_ns','Not provided',NULL,'rana_ns','rana_ns','eyJpdiI6ImdJNWZ6UVFRdzhndEdNb3J1SisxTlE9PSIsInZhbHVlIjoiSE9kbmdrTjFiWmJTbEQzNUprV0dEdz09IiwibWFjIjoiZjBkZmM3NzljMjg0NjlmZTZiMjA4M2JjNDA1ZTIwOTQ3NDU4NTczMDgyYTlhMGRiYjFjZjNlOGQwYjcwYjFmZCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: rana_ns\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(282,'2','user','rabbi_coffe','Not provided',NULL,'rabbi_coffe','rabbi_coffe','eyJpdiI6IkZDbUx3bk9DOHRJb1RNLy9lRXhxeFE9PSIsInZhbHVlIjoiSTJxTmYwMFl4ZVE4TmRSTTF6TEhvdz09IiwibWFjIjoiYjk2YThkYTc5YzE5OTEwZmNiMGFhNDMwNTJkNDIwZTZmNDUzMWQ3NzdmNTUwYmM3NjQ4NTQ5ZmEzZGMzMjcyZSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: rabbi_coffe\nProfile: 30 MB KPI\nService: pppoe\nRouter comment: none','inactive',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(283,'2','user','shovon_vai','Not provided',NULL,'shovon_vai','shovon_vai','eyJpdiI6IjVueTg0MkQ1QVVuQWlNYWxNcllXdnc9PSIsInZhbHVlIjoiaGg0aUdWNDI3OWFPTG04cERaQ0FoQT09IiwibWFjIjoiNGRhYWE5NmVmZjBhNjYwODRhMDBhYzJlOGVmZTBjZWJjMjdjNDY5NjBlY2ZjYmVhOWI0YzBhZjdmYzhmOGVmOCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: shovon_vai\nProfile: 30 MB shena_nir\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(284,'2','user','riaz_shop','Not provided',NULL,'riaz_shop','riaz_shop','eyJpdiI6ImNIR1JJT1hyQlJVeVcrLyszN2VlMnc9PSIsInZhbHVlIjoiakIxblBYakc1cU8xaEg3NXFCMUVldz09IiwibWFjIjoiMDNkZjMwYTk1YjhjNzAyN2UyNDZmYjQ3YWMwMGI2NWI2NDM0MTJmMWZmOGU4YmQ3MmYwMDlmMjQ0OTZmYWU4ZiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: riaz_shop\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(285,'2','user','shomobay_bank','Not provided',NULL,'shomobay_bank','shomobay_bank','eyJpdiI6InFwK1FBRXdsWE5SWGhYb3lxanAydXc9PSIsInZhbHVlIjoib2YrZVVWWC92aVNFS1FRa004YmJXdz09IiwibWFjIjoiZWUwZjVlYjQzNWEyNzY0YjAyYjY0YjIzZmZlODM3OWNmZmU3ODQ4YTU1NDI4NDAzMjViMDRhZDk3NTAwNmNhYiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: shomobay_bank\nProfile: 50 MB shena_nir\nService: pppoe\nRouter comment: shomobay_bank','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(286,'2','user','ridoybg','Not provided',NULL,'ridoybg','ridoybg','eyJpdiI6IndWWmpxTE9jRGtXT0ZtbE1GSTdjQXc9PSIsInZhbHVlIjoiUndXVHdneGFLdGdjb3JoWmNNSTFjZz09IiwibWFjIjoiM2Q3OWFjZDY3YTkxNjVmYjEyNzBmZWRhMDRlMTNlMjQ4MzRiODVhMWQ1YTk5YTdhYTE3OWE2Y2Q1MDc0MjZlNSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: ridoybg\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(287,'2','user','ripa_bermij','Not provided',NULL,'ripa_bermij','ripa_bermij','eyJpdiI6IjI5dTVqVEppTmoxd2lsUFNyVmhQZkE9PSIsInZhbHVlIjoiTjhMdGZMQldKOVpvYkd6NFUxbGNnQT09IiwibWFjIjoiYjQ2Njk5OWYzMDc2YTljY2RlMzc0NTRmZWI1MTMyYTY3MTlmNTZmNTdhY2JlYzg1NmIzODg4MDUwY2E0ZmU2MiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: ripa_bermij\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(288,'2','user','rakibst','Not provided',NULL,'rakibst','rakibst','eyJpdiI6IlhxVTVMU09IZzltTjdCWnR6RDNuQ2c9PSIsInZhbHVlIjoid0dYODR0TWdMaTl5NTVkOUZOUzZhZz09IiwibWFjIjoiMzE5OGMwZDg1OTNkMjgwMzM5YmUwNjk5MDJiOGQwNDhmMWZlOGMyYjBmNmU4YmE2ZGE2ZjNjYzEwYmYxYzcyYSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: rakibst\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(289,'2','user','Anike','Not provided',NULL,'police_saheb_vi','police_saheb_vi','eyJpdiI6Ikl4RVgwMmxScnFETW5UcTY4b2tqQlE9PSIsInZhbHVlIjoib0NpMEpGVFhmOVVXYkRQK0l3am5rdz09IiwibWFjIjoiZDcyODkwOThjMDRkNWMyODBhOGU1YjU1Mjc5NjIwMWFhYTEwNmEzY2FmYjhiYTQ2ZDg0M2JhNGI1MzVlOTM0NyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: police_saheb_vi\nProfile: 50 MB mosharof_bgoly\nService: pppoe\nRouter comment: Anike','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(290,'2','user','raselvai_kushtiab','Not provided',NULL,'raselvai_kushtiab','raselvai_kushtiab','eyJpdiI6IklJQU5HY29QeFRLc00vWEFsYlNuNlE9PSIsInZhbHVlIjoiN3NQbGh4M3B3NXNic0FDd0E3MytyZz09IiwibWFjIjoiNGQxYzJlMTIzODg0ZGY2NDE1OGU2MDcxMmU0YzFlZTdiZGFjZmM0ZTY5ZjVhNmQ0YjM5Y2FjNmJmNGY0ZDA0ZCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: raselvai_kushtiab\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(291,'2','user','raka_farmacy','Not provided',NULL,'raka_farmacy','raka_farmacy','eyJpdiI6Ik54cnFyYmNYeksrODg5d3lHamhYb2c9PSIsInZhbHVlIjoiNFlTWE5tWjFGWnNuWW9IR1pJS25Mdz09IiwibWFjIjoiYjVlZTkxNzVmMzNlMjlmM2EyNTNjMWYzY2IzMzJhZGI5MTZlMTI0MDJiNzEyZDFiYzZkZmY1MzIxYzEwOTUzNCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: raka_farmacy\nProfile: 30 MB Saifulkst\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(292,'2','user','01859304704','Not provided',NULL,'shomik_datta_ns','shomik_datta_ns','eyJpdiI6IlI1UGNyRUhLTjRlR29XazhGVGNPV1E9PSIsInZhbHVlIjoic1FIOSt2VHFObVdXZTc0ZmNEQ1FtZz09IiwibWFjIjoiOTAwMDM5MDY5N2NkZDJlYjFlOWM2ZjkxYzIxNGFiNzYyZTVmNWJmM2U5MjU2NGE2NzY0Y2E2ODM0NmFhZmVkYSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: shomik_datta_ns\nProfile: 50 MB mosharof_bgoly\nService: pppoe\nRouter comment: 01859304704','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(293,'2','user','shorf_optic','Not provided',NULL,'shorf_optic','shorf_optic','eyJpdiI6IlZFanN0S1dzSDRMVjd3d3cySlptbkE9PSIsInZhbHVlIjoiaWRVTURLdVRoODV5Qmp6czR2Nmo2UT09IiwibWFjIjoiOWI3MGIwMDRiYzllNjhhYjU5NTFiNjk5ZDk5NTFmMTg2NWI2Zjk2MjdkOWZmZWM3NTJiNzVmYTgyZjIxNWQ5NyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: shorf_optic\nProfile: 50 MB mosharof_bgoly\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(294,'2','user','salauddin_itech','Not provided',NULL,'salauddin_itech','salauddin_itech','eyJpdiI6IjcvRjlwMnJhbzZsN2hpSEpuMERXcWc9PSIsInZhbHVlIjoiNVBTVmNxL1NHTFhSbXhOS1JEaDZxQT09IiwibWFjIjoiZjVmMjFmOGMzMDU3ZGEyM2E4ZDVlZWExZTYzZTI3N2I2YzY2YWVjMTBiNDMwZDMyNDNiZGNiNGVhYWVhMDNmNSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: salauddin_itech\nProfile: 50 MB mosharof_bgoly\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(295,'2','user','rana_basha','Not provided',NULL,'rana_basha','rana_basha','eyJpdiI6Ik1CVmRsOVoyMzVraVY4QkorNFNDeEE9PSIsInZhbHVlIjoiaGVTYXJ5MHNmOTBPOCt4MWlKVTR6Zz09IiwibWFjIjoiNDdjNmMxYzU0ZmQzYmY2YTZkZWYxNDhlMDQ2ZDM3MzI1NjIwYjBiZjE0ZjIxOGM5OWYyZTM5NWE0OWI0ZWEzMyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: rana_basha\nProfile: 50 MB mosharof_bgoly\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(296,'2','user','rodna_onkur','Not provided',NULL,'rodna_onkur','rodna_onkur','eyJpdiI6IlpNNFhrd0lrcXgxZHVzTzR2RVhZOEE9PSIsInZhbHVlIjoiZFgzUDBQZHJxNVYzTzNSd1c0R0J3Zz09IiwibWFjIjoiOWNmZGRkY2RmZjA5NjI5ZGNhMzM1MjFkN2MzNzgzNzgxYTY4NGE2ZDRiMjBiNzdiNjJlYmQ5ZWJjYzAzYTg2MSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: rodna_onkur\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(297,'2','user','public_imran','Not provided',NULL,'public_imran','public_imran','eyJpdiI6Ilg3dWswS2RNRm5ZeTRmUjZRZFB4dUE9PSIsInZhbHVlIjoiM2I0OWxqUTRsN29PZ0F4QW51ZTAzQT09IiwibWFjIjoiNWYyNDI1NTIxZWZkYWFhMTVhYmUxYzA3N2UyZTJhNTQ0ZmM1NjA5NjU4NDg3MzQ0NTNiODM1ZDEzZGE5OGE2ZSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: public_imran\nProfile: 30 MB shena_nir\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(298,'2','user','salea_ator_home','Not provided',NULL,'salea_ator_home','salea_ator_home','eyJpdiI6Ikt5K21teEdjdm9xcStaNG04aUtiRnc9PSIsInZhbHVlIjoiMmNudElqUmFmTXhybmxiTTRpSGl6QT09IiwibWFjIjoiNjQ1YTc5NDE3NGZmZjEzODQ3NzhiYWVhNjJlNzQwYzVhZTkzY2Y1YzA4ZmY3OTgwZDUyOWZlMWY0MWI2ZjI0NyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: salea_ator_home\nProfile: 110 MB 141ranvid\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:28','2026-08-10 23:09:28'),
(299,'2','user','shakil_custom','Not provided',NULL,'shakil_custom','shakil_custom','eyJpdiI6InRsNDEzUHRaL2Y3VGxHbHZUcWVhZnc9PSIsInZhbHVlIjoiRGl5YTVEakpuMEpnK0lyOHV2NFUrUT09IiwibWFjIjoiNTgzNDllMmMwMGRkMGM1MjQ1ZjQ4OGNmMDJjZjVhYTExNDAxNjA5ZmNkMzFjMzhkNzZmZDVhZDUwOGMxMDE4ZCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:28\nConnection ID: shakil_custom\nProfile: 100Mb_kpi_comdpt\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:29','2026-08-10 23:09:29'),
(300,'2','user','ttcsirhome','Not provided',NULL,'ttcsirhome','ttcsirhome','eyJpdiI6IkExdmdtWjBsSEJUN1ltQXFvdTVaREE9PSIsInZhbHVlIjoiTThVQU1VMnJ1aWlZZlZlUG1WRnlPZz09IiwibWFjIjoiM2UwOTI2NWVkYjE0MTg5Yjc4ZTM2MGZjMWM1ODcxMDYwY2ExZmIwMDhjYjc0OTQwNTE1ODVlYjNlNGFkNGNhZSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: ttcsirhome\nProfile: 30 MB govt_college\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(301,'2','user','star_test','Not provided',NULL,'star_test','star_test','eyJpdiI6Ii92UHRLUHBldzg3dGtrbDBaQ3hwMEE9PSIsInZhbHVlIjoiT3RmYXdLRzlOTlJrd05aR0U2L2lrZz09IiwibWFjIjoiMWI5MzhjZTAyYjk0NDZhMmI3ZDIwZGNjZDE3ZGJmN2M2OWE5N2JiODU0YTVlMjcwYjRhZDkyMzFlMmU4ODg3MSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: star_test\nProfile: 30 Mb Star\nService: any\nRouter comment: none','inactive',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(302,'2','user','star_hap_3','Not provided',NULL,'star_hap_3','star_hap_3','eyJpdiI6IlI2M0J0Ym5BNVdRQkNudTVNT2x6emc9PSIsInZhbHVlIjoiY2kwVU5nZ0NxTitRalRtSmRuR3dlUT09IiwibWFjIjoiN2ZlNGMzMzE5MTcwOGI2ZGRkY2IxMzM3OTRiMDY2Njc0YzQ4ZDM5NzQ5Nzc0NGU1N2UwMmQ5ZTQ0MGM2NTY0NiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: star_hap_3\nProfile: 200 Mb Star\nService: any\nRouter comment: none','inactive',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(303,'2','user','usstar','Not provided',NULL,'usstar','usstar','eyJpdiI6IlhxQlZscEUzRWF0S08ydFBCK0xYbEE9PSIsInZhbHVlIjoidFdnSi94eGUyOUlxdkx3Z0Y0N2VKQT09IiwibWFjIjoiNDFiYzA5MjI2Mzk1N2I5YjdmYTcwNGI1YTlkNDUxODA5ZGVlMzY0ODQzZmRlNDc2MTI3N2VhYjJhOTY2Y2Q1OSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: usstar\nProfile: 200 Mb Star\nService: any\nRouter comment: none','inactive',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(304,'2','user','shofiq','Not provided',NULL,'upzsir','upzsir','eyJpdiI6IlpZK3lDM3F2MnN2WWd1VnVJekZGZ3c9PSIsInZhbHVlIjoiZ2JhbWNJWWJIS2VtV2YyVHlLL3lEUT09IiwibWFjIjoiNzFkM2QxYmU4ZDZmOGZjMGVmOTE2Nzc1ZmRkZWRkOGQ3YWNhZGJhNzcxZjhjMDk1OTBmNTQ1MTc0MzdmMmNkMSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: upzsir\nProfile: 50 MB shena_nir\nService: any\nRouter comment: shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(305,'2','user','Tannisha','01972777070',NULL,'tafsircom','tafsircom','eyJpdiI6InZzZlRqRGhTalcveVpuYmtkOXpDbkE9PSIsInZhbHVlIjoib3JDNjJKK3luM3g2alQvR0Q5RnpGdz09IiwibWFjIjoiZjFlNzQ2NWYxZmM2MzgwMjcyZTQwZjBlMzFlOWI5ZTVjYjFhMGFlZWU0OTNkMDdiODZmNmY4YTZlNjlhM2U2ZCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\r\nConnection ID: tafsircom\r\nProfile: 200 Mb Star\r\nService: any\r\nRouter comment: Anike\r\n[2026-08-12 21:42] Activated package to 2026-09-12 via quick-activate action.\n[2026-08-12 23:04] Manual validity override: 2026-09-12 → 2026-08-11. Reason: test\n[12/08/2026 23:05] Paid validity: payment date 12/08/2026; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 12/08/2026 to 11/09/2026. Payment note: Auto bKash SMS TrxID: DHC6EBL5IW','active',0,NULL,NULL,NULL,'2026-08-12','2026-09-11','[12/08/2026 23:05] Paid validity: payment date 12/08/2026; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 12/08/2026 to 11/09/2026. Payment note: Auto bKash SMS TrxID: DHC6EBL5IW',2.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-12 23:05:48'),
(306,'2','user','Shofiq','Not provided',NULL,'tuku_vi','tuku_vi','eyJpdiI6ImpBMTdNUlk1bXEycGt1S3p6NW51bFE9PSIsInZhbHVlIjoiWmtubEgwQVRmQzJGT1JzZ1oxUHNhUT09IiwibWFjIjoiMGU5N2E2YjNkODdhNmQwN2IyMThmYmI1ZGNjN2I1YjdiNmZlNmE0ZGI5YmEyNTljNzhjNTg0ZDFkMGMxYTcwYiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: tuku_vi\nProfile: 30 Mb_Travelshouse\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(307,'2','user','Shofiq','Not provided',NULL,'wadud_driver','wadud_driver','eyJpdiI6IjB0QWppeXhVbml5N2J0Njl4bDNzNlE9PSIsInZhbHVlIjoiT3d2ejRPUHppcjdoNzFKRzcyaHpBQT09IiwibWFjIjoiNTU5MTBiNDI2MGM1OWRkM2NhMDJkN2MwYTE1ZDA1NjU2N2QzZDJhNGNkZjYwYTNjNjg1ZTRlZTk1ZTE1NDVjMSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: wadud_driver\nProfile: 30 MB Saifulkst\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(308,'2','user','Shofiq','Not provided',NULL,'wdbmamun','wdbmamun','eyJpdiI6IldnanVwMm9hWk5tVEEwR3BZazN4YXc9PSIsInZhbHVlIjoiR2ZXeWlPcDV6d2thZi9JNzhoTTl1Zz09IiwibWFjIjoiZWFkOWU4NzZhNDc4ODVjYWQzNjM0ZmQ0Y2Q4YzFiYTAwZWQ3ODg0MTBkZWY0NzkyNmVhNjMzZGM3ZmE0MmNjZiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: wdbmamun\nProfile: 30 MB Saifulkst\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(309,'2','user','Shofiq','Not provided',NULL,'sumonkst','sumonkst','eyJpdiI6IjFEMkt2L2lvYjl0VWxIam5DVEUvVmc9PSIsInZhbHVlIjoiWVBSUjFtc0JkU1RhYXFTNXRxNm80Zz09IiwibWFjIjoiNjNjZGZkMDk4NzRiOTM2Y2E5YWY4ZTM2ZjVmZGQyNmI1NzIwNmNjMTQ1NWU0YTUzYjBmYzExZTIzNTU5YWI2MyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: sumonkst\nProfile: 30 MB Saifulkst\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(310,'2','user','Shofiq','Not provided',NULL,'sujonpol','sujonpol','eyJpdiI6Img3NUNZbTY5eGpVem5ZMEg4Q0xOQXc9PSIsInZhbHVlIjoibnNReFU2T3c0Qk9SZ2dUTThUWS9QZz09IiwibWFjIjoiODc5NjM3NWM4MmU2ZGM5ZGNlYjAwYjZlNWJmNjgxNjA3MGI0NTU0YmM0NjZhMjY1ODE2MmYzYjg0M2U3YjU0MCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: sujonpol\nProfile: 30 MB Saifulkst\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(311,'2','user','Shofiq','Not provided',NULL,'wdbalamin','wdbalamin','eyJpdiI6IisyTlFtOW53NVdHUmxaYVBEN095Qnc9PSIsInZhbHVlIjoiV3BSd2ZQRE10cE50TkZudXVUQ3YzUT09IiwibWFjIjoiZjcyMjQ2YjZhYTk2YTQzMmYwOTMyYTdhNjYxNjliMzhjMGE2YTk2ZDg4OTQ5NGNlNmQ1ZTVmMzhmMWE4NmU1OSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: wdbalamin\nProfile: 30 MB Saifulkst\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(312,'2','user','Shofiq','Not provided',NULL,'somendada','somendada','eyJpdiI6ImN3Y0tWN0JZbjlTYXJ1WWdNdVRKVlE9PSIsInZhbHVlIjoiT1Vhbk91ajhrK09pUzdOcWxZdGI2QT09IiwibWFjIjoiNTVkOTk3ODNlOTVmMDI2YjdlZjBkNmE3NGI4ZTc1NDczNGUyMzE0NjhmYWFjOTBkMmY5NDNmZmY5MDIwN2E0MCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: somendada\nProfile: 30 MB Saifulkst\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(313,'2','user','Shofiq','Not provided',NULL,'suzonkst','suzonkst','eyJpdiI6IklSQjBqNmtELzJxbFZkR0g2bDVNQ1E9PSIsInZhbHVlIjoiTS9obk9hK3FZWUZyaGNjb1ZxbDJYQT09IiwibWFjIjoiYWUyOTc2NTAwZmIwYzdlZTUwYzg2NDY1ZmViNDAyMmM0ZjU4ZTIxODRmNzE2YTk3MzdlZWZhODZhOTE5NWZkNiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: suzonkst\nProfile: 30 MB Saifulkst\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(314,'2','user','Shofiq','Not provided',NULL,'untisixr','untisixr','eyJpdiI6ImQwYURtNUN2OHVOSjkzVXNNQk1aK1E9PSIsInZhbHVlIjoiQTZXTXVtbDBJdFN4L1RMWXo4ZDVWUT09IiwibWFjIjoiNzMwNDJmMGJlNTE4YzkxMzAyMWUyYjdlMTRkYzA0ZWIxMjA0ZjA4MGZmNzIxYjhjOGY0NzU5M2M4YjU3YmJlMiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: untisixr\nProfile: 30 MB Saifulkst\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(315,'2','user','Shofiq','Not provided',NULL,'tamannakst','tamannakst','eyJpdiI6IkE0Ulc5NFAveitqRnZHYkQzNWNOSmc9PSIsInZhbHVlIjoiODFBSnJNRVhRaXRuWDkzZGIrWUVaQT09IiwibWFjIjoiOWZjMjU2NjEwZTA3ZDU3ZWQ4MDA5MDQ2NDU1YTc2MTEzMmY1YzVlNDlhNmU4NWJiMDhhMzNmMDgxMzliOTgyZCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: tamannakst\nProfile: 30 MB Saifulkst\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(316,'2','user','Shofiq','Not provided',NULL,'tasinkst','tasinkst','eyJpdiI6IlBYcEFUa2FNZlJORTBzZ2lpRXlkeWc9PSIsInZhbHVlIjoiVlFycEQyR0d5eVhzOVlGc0N1RVZOUT09IiwibWFjIjoiYjEyZjAzOTYyY2ViODUyODE3NmI2ZTZkOWUxOGUzNzk3YTliNDQ0NGU1YTc1NzU3MTZmN2U4YmJlYWE5OTljZiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: tasinkst\nProfile: 30 MB shena_nir\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(317,'2','user','shofiq','Not provided',NULL,'wdbsaiful','wdbsaiful','eyJpdiI6IlFYQ3BPN29PeDUzMjgvT2J3alRXU3c9PSIsInZhbHVlIjoiQWdsWGRKdHdEeDBpL0hyWU13YUdKUT09IiwibWFjIjoiNjU5M2I2MDFmNzc3YjIzZWI5OWM1NjczOGJkMjkwMGU2NWRkMzdmZmUxZDQ3MTMyNDYyMDZjYWU5MjEyYzM5MCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: wdbsaiful\nProfile: 30 MB shena_nir\nService: any\nRouter comment: shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(318,'2','user','Shofiq','Not provided',NULL,'wdboishe','wdboishe','eyJpdiI6InhDREJVQ3gwZjJHYWlsVWIvY3NQS2c9PSIsInZhbHVlIjoiWlJLbzE2aVBnVEsxcm9rNWdGOE5mQT09IiwibWFjIjoiMzliZWQ4MDA2N2JhMDg4NWUyZDlmODkxN2JhZmIwOTU1NzkzNThjM2FhNDU1MmEwMjQyY2RmNWRlNWY0Mzc1YSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: wdboishe\nProfile: 30 MB Saifulkst\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(319,'2','user','Shofiq','Not provided',NULL,'wdbruble','wdbruble','eyJpdiI6InFMeWNUZXlna3N1YjBycFRVZ2NWK1E9PSIsInZhbHVlIjoiUUc1d2lqdkM3eS9weitjOVFlak82dz09IiwibWFjIjoiMTFhMGFkYzEzZjI4YTdhZTk3ZmRhZjE4ZTMyMGI1ZTljYTIzYzkyMzE0MWRjZmIyMmE2MTI0NDk2MTdmOTNiNSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: wdbruble\nProfile: 30 MB Saifulkst\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(320,'2','user','Shofiq','Not provided',NULL,'tahamid','tahamid','eyJpdiI6IkpraG1iajkvSXllRWRVUWtZNmtjbEE9PSIsInZhbHVlIjoiL0lHQVZpaTJhWDhrNklxTFpGdWpBdz09IiwibWFjIjoiMTU5N2I4YzkyNTM3ODc4OTAwYTQ0YzY5Zjg5OTJmMmVkYjA5OWZlYzZkNGJkYjMzMTZhMTlkMjQwMGI0MjExMSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: tahamid\nProfile: 30 MB KPI\nService: pppoe\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(321,'2','user','Anike','Not provided',NULL,'tisha','tisha','eyJpdiI6IkdqN0E0bVNNazQ3a1ZwSFA4aE9vaWc9PSIsInZhbHVlIjoiNW5jQUpIQWhtbFRqUndzMHdVSHlUUT09IiwibWFjIjoiZjQ5YzllMTFlMjM0ZGQ5MmUwZTFhMzdjNmY5NDk2NWQwOTBhMTEwNWUwNGQxYjBiNzgxNWE2YzZhYTY3YjM1MSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: tisha\nProfile: 30 MB Saifulkst\nService: pppoe\nRouter comment: Anike\n[2026-08-12 21:42] Activated package to 2026-08-13 via quick-activate action.\n[2026-08-12 21:47] Paid validity: payment date 2026-08-12; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-12 to 2026-09-11.\n[2026-08-12 21:48] Paid validity extended for 2 month(s): 2026-09-12 to 2026-11-11. Note: Automatic renewal from advance balance for remembered package.','active',0,NULL,NULL,NULL,'2026-09-12','2026-11-11','[2026-08-12 21:48] Paid validity extended for 2 month(s): 2026-09-12 to 2026-11-11. Note: Automatic renewal from advance balance for remembered package.',0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-12 21:48:10'),
(322,'2','user','Anike','Not provided',NULL,'uttom_kaka_chuchona','uttom_kaka_chuchona','eyJpdiI6IlIzSjRCSXZBZDl1emRiTzFLZm9tV3c9PSIsInZhbHVlIjoiOWRsMDMxUGJhekp1WTZNWlhRT2t4Zz09IiwibWFjIjoiOGU5NjQ4ZDMyMTc0ZTBlN2Q3ZTVlMzg5YjMzODdiMGY0YzI4ZmFhOTk1NzBiYzNkZWRkMDEwYmI4NGIxMTA0MyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: uttom_kaka_chuchona\nProfile: 30 Mb_Travelshouse\nService: any\nRouter comment: Anike','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(323,'2','user','shofiq','Not provided',NULL,'sohelvai','sohelvai','eyJpdiI6Ijg4U3dCd2FOVGZraVZJejYvRTJVR2c9PSIsInZhbHVlIjoiaFpVWFI1d2M2eDg1TVRqaFBTSS9UQT09IiwibWFjIjoiNWVlNWU3NWMzMTFmMDliMTUxOWVmZTA1MmRkZWJkODk0NDAxMWMwMDY1N2Q2NTAyZTY0MDFjOTc0YTVlYmU1NSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: sohelvai\nProfile: 30 Mb Star\nService: any\nRouter comment: shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(324,'2','user','Shofiq','Not provided',NULL,'upzfamily','upzfamily','eyJpdiI6Ik42Yzh4TEdneTFTZjJkVUxEVDRkRmc9PSIsInZhbHVlIjoiVit6Y0gvaVh3OHFHclVCTTFISG9MUT09IiwibWFjIjoiYmE5N2JiMDhhMDcwMTRkMzNkMWI1ZTc3OGQ3OGE4M2UwNWMwZjM0OTE3NGU4N2I3NmM0ZWE2NzUzOGIzYWQwZCIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: upzfamily\nProfile: 30 Mb_Travelshouse\nService: any\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(325,'2','user','sohel_travels','Not provided',NULL,'sohel_travels','sohel_travels','eyJpdiI6Ikk0TDhOa1A0Wkx0SUZFQVg1VENKdEE9PSIsInZhbHVlIjoiUER4Y3N6bUhHYWhOS1A4emQxOWptQT09IiwibWFjIjoiMWM2MjZkNzI1Y2MxYzc4MDMyZGRmZjE1ZTk5ZDkzMzQ4OTQ0NDE5MDQ5NWFmZDYyMmZiMzc4ZjcwMDRkY2VlZiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: sohel_travels\nProfile: 30 Mb_Travelshouse\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(326,'2','user','Shofiq','Not provided',NULL,'wdbjoy','wdbjoy','eyJpdiI6Im9oYi94aHZJRml0TVp1L3pTZUxCWVE9PSIsInZhbHVlIjoiVG9IcUQ1bE9Eenpnd3M1dFdpUHRFQT09IiwibWFjIjoiYWRjZGM3OGM4ZThmNTI2OTFiN2NjZGFhMDNmOTdjZTlhZjFiZTkwMGUwZDZjZmJlMzEzNGY3OTU0NTBjODE4ZiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: wdbjoy\nProfile: 30 MB Lgedks\nService: any\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(327,'2','user','Shofiq','Not provided',NULL,'sunjid','sunjid','eyJpdiI6IlQrZHVMZktQVFFEa211RkwxZE8vYUE9PSIsInZhbHVlIjoiVklUR1ZFd1ZySEZCSUprZ3NvWFduZz09IiwibWFjIjoiMjkwNDU1NDdlZDQ5ZTJhZWE4MDY1MzcyNmNjNDQ0YzA4NmUxNGM2ZGI4YjM2YWFmNjQ5NTA5YjU2N2IyYjkyNiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: sunjid\nProfile: 30 Mb_Travelshouse\nService: any\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(328,'2','user','wdbpd','Not provided',NULL,'wdbpd','wdbpd','eyJpdiI6ImM2aE1IWSthOU1oM2Q1YzlrZ1lnY3c9PSIsInZhbHVlIjoiT25ZdmxiN1BaclR5Q2UxQTJ2Z0dvdz09IiwibWFjIjoiMGY0NDFhNjhhMTdhNWFkYjY5YTk1YWIyZjE4MTFhYTEzYTEwNmE5ZWJmODQyYTFhY2MzYmEwZTU3NmVkYjM1MiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: wdbpd\nProfile: 30 Mb_Travelshouse\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(329,'2','user','Shofiq','Not provided',NULL,'wdbhujur','wdbhujur','eyJpdiI6ImFFR01lVS9SejNMTkp0bUhCb0NzY0E9PSIsInZhbHVlIjoic2dSc3lvRk10WDFWUW0xNWI5c2tRQT09IiwibWFjIjoiNDVlNGM4ZTBiY2QxZDM4NmVjMTVhOTlkM2QzYzA4NDU4YWI1YWIxMDBiMWU2YTFjZWQ3YjQxNTUxYmM5NTFiNSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: wdbhujur\nProfile: 30 Mb Star\nService: any\nRouter comment: Shofiq','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(330,'2','user','turjo_saddam','Not provided',NULL,'turjo_saddam','turjo_saddam','eyJpdiI6ImdzT2lkVk8zaXRra25ZNG50M3VoY2c9PSIsInZhbHVlIjoiOUFBd1hKTU1WLzFGM2xHL2hXZFNqdz09IiwibWFjIjoiMTExNmNlMmYxMTI1Yjk0N2E3YzAwNjgxM2ZkM2E0YWJlMzhjNzU3MTA5MzNjZDI4MzQ2YzJlMjM1ZmM4ZThiZSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: turjo_saddam\nProfile: 30 Mb_Travelshouse\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(331,'2','user','test321','Not provided',NULL,'test321','test321','eyJpdiI6IlR4ZVdOWjJqMVNuUzNPSTRrZGFSTFE9PSIsInZhbHVlIjoid2M1VFNWUDJzc2k2a3hqbUl1WHNXQT09IiwibWFjIjoiOTAxNDlmMjk1OTRiNDMzYTU4NDRlMWUwOWM3ZTRmNWQzZDdiM2FjNmUwNDJhZjAzZGVjMmE2ZmM4MWZiNTI4YyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: test321\nProfile: 30 MB ZIlas\nService: any\nRouter comment: none','inactive',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(332,'2','user','sijanglass','Not provided',NULL,'sijanglass','sijanglass','eyJpdiI6IjZqdTJuZWczZFNmWTZGbkJvZHpub2c9PSIsInZhbHVlIjoiNXpXbDhaVHNKaGovY1pNeTM5T0s4Zz09IiwibWFjIjoiOTdjMDBhZTY4YmY2Yzg1NzMwODI5NDZlYTRlODZkODgxMGUwZDdhNjUwMzlkMDZlMzI3N2I4NjFhYjVkMThkYyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: sijanglass\nProfile: 50 MB shena_nir\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(333,'2','user','toyebmob','Not provided',NULL,'toyebmob','toyebmob','eyJpdiI6IkpDTHVweFlpYk1lTTgzNWxhL2pRaFE9PSIsInZhbHVlIjoiV2FVMmhBbERwd2Z6SzVVZk9JcUNBUT09IiwibWFjIjoiMTA1NjBlYzJiMjk3ZjM3ZjQ0MDlkOGIxODJkMjM4NGQ4NTU4YmRhZjUyOWZiOTE2YjBiZmY1ODQxNDE5ODM0OSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: toyebmob\nProfile: 30 Mb_Travelshouse\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(334,'2','user','wdbrest','Not provided',NULL,'wdbrest','wdbrest','eyJpdiI6IllwdkhVWmJyZUxCeThsbWE2dXBISnc9PSIsInZhbHVlIjoibXR5VmZnR3A0cEJRbVQ5OE9lYnFwZz09IiwibWFjIjoiODhiOGJlNGFiN2E4ZjA1YmI3ZjM4NmFhYzdkYTFiNTQ4NDA4YTUxYjI3NGMxYThjMDhiZjlmMTFlOGQ0YjljMiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: wdbrest\nProfile: 30 Mb Star\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(335,'2','user','zillaschools','Not provided',NULL,'zillaschools','zillaschools','eyJpdiI6Imk0ZHFXd1dZQVN5YnduU24wbGhzRHc9PSIsInZhbHVlIjoiRWlOSVRwaDJZRFdJSVRLem1Lemd2UT09IiwibWFjIjoiZTM0OGU5OWE2MWY3NjU3NTU3MzA2ZDFlNGNjYThkNDgzMTZiYTBkMDUyODEzZGRmMzJhMzNjNmQyNDFkNTViMiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: zillaschools\nProfile: 50 MB mosharof_bgoly\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(336,'2','user','ttcpkb','Not provided',NULL,'ttcpkb','ttcpkb','eyJpdiI6InVyWTZPK1B1cVJObkplOGovVGU3OHc9PSIsInZhbHVlIjoiMWJydDNxNTJQNzF6VkZIYjhDdm9wQT09IiwibWFjIjoiNjlkZjFkYjlhMzk1MjUxMjBlZmQzNDA2MzU1NmIwZWYxOTliNjE0NmQ1MDMxZGUyNTZhMGJlMjNmZGU3YjQwNyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: ttcpkb\nProfile: 30 MB Saifulkst\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(337,'2','user','sksahib','Not provided',NULL,'sksahib','sksahib','eyJpdiI6ImFQU3RmME9uSzBOV0dmOEJtaTZCaHc9PSIsInZhbHVlIjoiZ0VNZloxUzlrUERBSlpyTWpRRjF3Zz09IiwibWFjIjoiZDg1OTk4NDE4ZTgzM2Q3NGM3ZjRkOWNmNmEyYTliN2RkN2ZhYTdkZmZjNjdlODQyOWQzNWZkZmUyMjAzMDgxZSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: sksahib\nProfile: 30 Mb_Travelshouse\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(338,'2','user','test_hap3','Not provided',NULL,'test_hap3','test_hap3','eyJpdiI6Im1sMkljSDdmT004RjllUE4xTHJiMlE9PSIsInZhbHVlIjoibWRLdlNqL0lFcmRVNjVWd2ZsYmd3dz09IiwibWFjIjoiNGRiMWI1Y2I0NWNmMWU4MjM5MWFlY2Q5Y2ExYTNhYTNjZDFlMjFhNmVkM2MxYjZkMzNiMDJmMjg2NWI2MDllYiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: test_hap3\nProfile: 30 MB Lgedks\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(339,'2','user','Wahiduzzama','Not provided',NULL,'Wahiduzzama','Wahiduzzama','eyJpdiI6Ikl1Sk9pL0RyRU1wU0djbk1STmtFemc9PSIsInZhbHVlIjoiT1Raa1l3dVkzUk41OWRDM2Z3NVpCQT09IiwibWFjIjoiNDliNzk3OWY2ZmY5YjZhMjU0MjJjYzZlMGVmMmZhZjQ1MjIzMGMwZTVhZDdmMzE5YzdkM2EwNGJlN2FhNTg3NSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: Wahiduzzama\nProfile: 30 MB govt_college\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(340,'2','user','twoha','Not provided',NULL,'twoha','twoha','eyJpdiI6Im1DbXErdEFCRXh6d2E0R0NQaGxJOVE9PSIsInZhbHVlIjoiNDBpQkNLUFA2N3dLa2ZZcGk5UG9Udz09IiwibWFjIjoiMTVkZDNmZGY2YmU4OWM0MGNlMDA2ODlkYTE0ZmIwMzNjYTA1MDdiM2Y0YWQ2NmVkMTBhMzAwMjYyOTk5ZTExZSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: twoha\nProfile: 30 MB govt_college\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(341,'2','user','sohailvai','Not provided',NULL,'sohailvai','sohailvai','eyJpdiI6Ik9QSFNUVlBaQW03eHhzQ0tQWUI5b2c9PSIsInZhbHVlIjoiZktzclBuMjFYekNrVks2ZENjbmsyUT09IiwibWFjIjoiZmNmOGNhMDQzYWY2M2Y0NDM3OTA0NjRhOTIyNGUyMzQzYmMwZGViMTNmNGEwOGM0NzAxOTllOTk4MDkyZDM1MSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: sohailvai\nProfile: 30 MB govt_college\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(342,'2','user','wdbaminul','Not provided',NULL,'wdbaminul','wdbaminul','eyJpdiI6InRsaFJMQWFRZXpXTkJNc3JwUy93MEE9PSIsInZhbHVlIjoiT0xaNHloZ3BRWEhQcElyWEErNnQ3UT09IiwibWFjIjoiNWM0N2JmN2VkMzU1OGNiOWQxMzBmZDQ3OTc3ZjAzZjU4MTg0YTQ1ZTZiMDkzZDcyYzllMDRjNTM2ZDU2YjMxMiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: wdbaminul\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(343,'2','user','sparkit','Not provided',NULL,'sparkit','sparkit','eyJpdiI6ImN2Sy8yaEFXdzBpcmVvc25vNmprL2c9PSIsInZhbHVlIjoiT3ZERndwOGV2NklGcXFiL3RZVkNiZz09IiwibWFjIjoiZTMwMTcxNWI4ZTU3YzcyMWEwY2NjYmZmMzZhMWY3MjJjMjFhMWY4ZjgwMzg5Zjg2ODY1Y2NjMzIwMzZjZjAyNSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: sparkit\nProfile: 50 MB shena_nir\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(344,'2','user','wdbxen','Not provided',NULL,'wdbxen','wdbxen','eyJpdiI6InI2anJEd0RMc3UxdHZDVWc1MmMvUUE9PSIsInZhbHVlIjoiSm85MFZHWlpsNWs2MFV2R1dvTXFMQT09IiwibWFjIjoiMWQzMWU2OTZmYmRlMzc0ZDY3NTE3NDY5ODYxNTIxN2Q1ZTFiYzAxMmI5YjJlMTZlY2Q0NjQyYWI0ZDdhNzM5MiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: wdbxen\nProfile: 30 MB shena_nir\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(345,'2','user','subirdada','Not provided',NULL,'subirdada','subirdada','eyJpdiI6ImtlWEgzY2JPeGUyRVhLN2E2dGtsQkE9PSIsInZhbHVlIjoiRHIrZFVMMnpqMmcydHFGWDh0eS9wZz09IiwibWFjIjoiMDcyN2E0MjYzY2ZmNmM4MTEyZTlhYTdmYjgxMDhjY2I1OTdjZWJkNmQwNmM3ZWFjODhkNzkwZjUzOWRiMWRiMyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\nConnection ID: subirdada\nProfile: 30 MB shena_nir\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:44','2026-08-10 23:09:44'),
(346,'2','user','tipu_vai','Not provided',NULL,'tipu_vai','tipu_vai','eyJpdiI6IjlqZGs1dC9ZWDlubzVrRlBXWE9Oc2c9PSIsInZhbHVlIjoieXhWbGQ1b3ozK1BFcHB1dEpaaHBqQT09IiwibWFjIjoiN2M4NDgzNDNjNjk0ZTBkYjk4YmUxYjdmZTBmZmZhZTgzN2QwYjJlN2Y4MDZlMGJiZTE4OTc3MWYzOWQzNjQ3NyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\r\nConnection ID: tipu_vai\r\nProfile: 30 MB Lgedks\r\nService: pppoe\r\nRouter comment: none\n[2026-08-11 18:27] Activated package to 2026-08-31 via quick-activate action.','active',1,NULL,NULL,NULL,'2026-08-11','2026-08-31','[2026-08-11 18:27] Activated package to 2026-08-31 via quick-activate action.',0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:45','2026-08-11 18:27:24'),
(347,'2','user','ss_brrirs171','Not provided',NULL,'ss_brrirs171','ss_brrirs171','eyJpdiI6Ii9UbmlvSUF5SFJyM0hjcWNibkxMWFE9PSIsInZhbHVlIjoiSXpwMmhqcExsam1HUWNVMFRlWDJHQT09IiwibWFjIjoiNTUzZWJkOGNhNzU3ZjdlY2UxZGMwMWY1OWI0ZTVlNGJkZjhhNWEwYzYwY2RhMTAyNjFiYTQ0NGRhNWFlNjBhNiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\nConnection ID: ss_brrirs171\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: none\n[2026-08-12 21:53] Service temporarily force-inactivated while validity remained not set. Reason: test\n[2026-08-12 21:54] Activated package to 2026-09-12 via quick-activate action.','active',0,NULL,NULL,NULL,'2026-08-12','2026-09-12','[2026-08-12 21:54] Activated package to 2026-09-12 via quick-activate action.',0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:45','2026-08-12 21:54:54'),
(348,'2','user','tanisha_home','Not provided',NULL,'tanisha_home','tanisha_home','eyJpdiI6ImRwTlRROEZnYVVXUVd0Qi9KOFhNU2c9PSIsInZhbHVlIjoiUnRuL3hNWVBDUjJOaGkwd0JjdEhBdz09IiwibWFjIjoiZWYxOGQxMWIwNjc5MGMzMWZlZjFhMDc2NTA3MDViNWY4YzVhYzhjMzM5ZTAxZWYzNzRkMWYyOGFhMjUyYjM3ZiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\nConnection ID: tanisha_home\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: none\n[2026-08-11 20:18] Paid validity extended for 5 month(s): 2026-08-11 to 2027-01-10. Note: Automatic renewal from advance balance for remembered package.\n[2026-08-11 20:21] Paid validity: payment date 2026-08-11; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-11 to 2026-09-10.\n[2026-08-12 19:35] Service force-inactivated while validity remained 2026-09-10. Reason: test\n[2026-08-12 21:15] Paid validity: payment date 2026-08-12; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-12 to 2026-09-11.\n[2026-08-12 21:16] Service temporarily force-inactivated while validity remained 2026-09-11. Reason: test\n[2026-08-12 21:17] Service temporarily force-activated while validity remained 2026-09-11. Reason: test\n[2026-08-12 21:27] Service temporarily force-inactivated while validity remained 2026-09-11. Reason: test\n[2026-08-12 21:30] Service temporarily force-activated while validity remained 2026-09-11. Reason: test','active',0,NULL,NULL,NULL,'2026-08-12','2026-09-11','[2026-08-12 21:15] Paid validity: payment date 2026-08-12; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-12 to 2026-09-11.\n[2026-08-12 21:16] Service temporarily force-inactivated while validity remained 2026-09-11. Reason: test\n[2026-08-12 21:17] Service temporarily force-activated while validity remained 2026-09-11. Reason: test\n[2026-08-12 21:27] Service temporarily force-inactivated while validity remained 2026-09-11. Reason: test\n[2026-08-12 21:30] Service temporarily force-activated while validity remained 2026-09-11. Reason: test',0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:45','2026-08-12 21:30:51'),
(349,'2','user','tuku_kutu_vi','Not provided',NULL,'tuku_kutu_vi','tuku_kutu_vi','eyJpdiI6IldUR3JEemQzQkcvL3czOUpZYW5FMGc9PSIsInZhbHVlIjoiZ1VhM2RDdWpaMy8wZkRDS2htY3FHdz09IiwibWFjIjoiZGUwYmMxMzY5ZTQzZjc2MzY0NWFhYzdkNmMzZmU0NGMwNDliMTE0MDk0NmU1M2Q4MGI5Y2Q3ZTE5NTkwYWM4YyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\nConnection ID: tuku_kutu_vi\nProfile: 50 Mb_Travelshouse\nService: any\nRouter comment: none\n[2026-08-12 21:56] Activated package to 2026-09-12 via quick-activate action.\n[2026-08-12 21:58] Manual validity override: 2026-09-12 → 2026-08-29. Reason: Paid tk 1000 29/6/26 for 2 month','active',0,NULL,NULL,NULL,'2026-08-12','2026-08-29','[2026-08-12 21:58] Manual validity override: 2026-09-12 → 2026-08-29. Reason: Paid tk 1000 29/6/26 for 2 month',0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:45','2026-08-12 21:58:23'),
(350,'2','user','wdb','Not provided',NULL,'wdb','wdb','eyJpdiI6IlhhaFNzQVlWUk9mK0U5clRMMUEzQ1E9PSIsInZhbHVlIjoib2NLaEthMnZwMWZYNnc3dmRSUGI5QT09IiwibWFjIjoiMmE0ODFjZmFmODA0NGUwYTRkMDk4NWJjZmZiZTU5NTg1NmJlOTVlZDUwOTViNjVmNzRmN2NjNGMyNzlmYzVlYiIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\nConnection ID: wdb\nProfile: 100Mb_kpi_comdpt\nService: pppoe\nRouter comment: none\n[2026-08-12 22:00] Activated package to 2026-09-12 via quick-activate action.\n[2026-08-12 22:01] Manual validity override: 2026-09-12 → 2026-08-11. Reason: jklk\n[2026-08-12 22:03] Service temporarily force-inactivated while validity remained 2026-08-11. Reason: test','inactive',0,'2026-08-14',2,'2026-08-12 22:01:59','2026-08-12','2026-08-11','[2026-08-12 22:01] Manual validity override: 2026-09-12 → 2026-08-11. Reason: jklk\n[2026-08-12 22:03] Service temporarily force-inactivated while validity remained 2026-08-11. Reason: test',0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:45','2026-08-12 22:03:42'),
(351,'2','user','sohel_vai','Not provided',NULL,'sohel_vai','sohel_vai','eyJpdiI6IlQ0RDk2OW83R2NPWEx0emRZaW9NTmc9PSIsInZhbHVlIjoiRkR4cldEODdmbUpBYklnZ0ZsOEdHQT09IiwibWFjIjoiZjgxMDNlMTNjMjdlYTQ3N2UxNzI0MTNmM2ViNTE1MGNlNGRlM2ZhNjhlYWM3MjA0YjViMzk4MzM2YmY3NzY1YSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\nConnection ID: sohel_vai\nProfile: 50 MB shena_nir\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:45','2026-08-10 23:09:45'),
(352,'2','user','taslima','Not provided',NULL,'taslima','taslima','eyJpdiI6IlZxakMvV0pIN2k1V29SdFBTRXEwREE9PSIsInZhbHVlIjoiTWY1TkhLOUFzZlU2ektLWlRTU0FwUT09IiwibWFjIjoiYjkxYWY1NDA3OGY1MGYzMDlkYTM5ZjBmMmI5MjU5OGY3MzNkMGYwZmRhZjNhZjgyNWJjN2UxYjRkY2JiNGMxMSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\r\nConnection ID: taslima\r\nProfile: 30 MB shena_nir\r\nService: pppoe\r\nRouter comment: none\n[2026-08-12 21:39] Activated package to 2026-08-12 via quick-activate action.','active',1,NULL,NULL,NULL,'2026-08-12','2026-08-12','[2026-08-12 21:39] Activated package to 2026-08-12 via quick-activate action.',0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:45','2026-08-12 21:39:53'),
(353,'2','user','wdb_khalid','Not provided',NULL,'wdb_khalid','wdb_khalid','eyJpdiI6IjlZMGg0UFl5TitMQ0kzeTROYjhmRFE9PSIsInZhbHVlIjoiczlJRktFVXBnOTQzNGZlWm40V1BoZz09IiwibWFjIjoiMjY2NzFiNjAzYzk4NzE3OWUzMDFkMTQ4NWFmYmE1ZmQ0OWZkNTY4ZGI2MDU4YmExNTVlMTVhZjNhYmQyMjAwMSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\nConnection ID: wdb_khalid\nProfile: 30 MB Lgedks\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:45','2026-08-10 23:09:45'),
(354,'2','user','tofazzol_ripon_dada','Not provided',NULL,'tofazzol_ripon_dada','tofazzol_ripon_dada','eyJpdiI6IlBIWUZrZlRCMkVod050b0NJYlNRR0E9PSIsInZhbHVlIjoiMHBrSG4xNzJBRk12VXoxY2RNVkVhQT09IiwibWFjIjoiNGJhOTY1NzlmMGYzOWM3NDEwMDZhMjJkZDM5MzM2ZTY2MGM3Nzg4NWEwOGY1MzAxODM2ZTk0NzQ1NGU2M2JjNyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\nConnection ID: tofazzol_ripon_dada\nProfile: 100 ZillaS\nService: any\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:45','2026-08-10 23:09:45'),
(355,'2','user','sumanbg','Not provided',NULL,'sumanbg','sumanbg','eyJpdiI6IlduVC8zeHNxYTdLaDVjS0hLL0UyU2c9PSIsInZhbHVlIjoiT2pOU2x6SVA3NzZ4VzdzeGhReDQzQT09IiwibWFjIjoiNTJjZTIwODhhNTY3NDNlZGYyNmQ0YmJjYTI4NDNhY2I5ZTRlNWNjYzgwZTBiNDI0MWRkYzE4NTc2ZmU4Y2I5MyIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\nConnection ID: sumanbg\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:45','2026-08-10 23:09:45'),
(356,'2','user','toyemob','Not provided',NULL,'toyemob','toyemob','eyJpdiI6IkZNdlZwbFJMcEhMeWgrY1F6SGovaHc9PSIsInZhbHVlIjoiVDZ6ZlFST1o5NWJqckxaM3U5ejgyQT09IiwibWFjIjoiMmUyNDI3MGM0MmIxODhiOGEyOWQ5MDEyYzVjNTAyMDU3ZjU1YmJjY2ViMzcxM2MwYzg0MDQzMjAxZGUyODZiMSIsInRhZyI6IiJ9',1,0,NULL,NULL,NULL,NULL,NULL,NULL,'Imported from MikroTik 1036 MikroTik','Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\nConnection ID: toyemob\nProfile: 30 MB ZIlas\nService: pppoe\nRouter comment: none','active',0,NULL,NULL,NULL,NULL,NULL,NULL,0.00,1,0,0,NULL,NULL,0.00,'2026-08-10 23:09:45','2026-08-10 23:09:45');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_asset_assignments`
--

DROP TABLE IF EXISTS `employee_asset_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_asset_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `issued_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `source_condition` varchar(20) NOT NULL DEFAULT 'new',
  `quantity` int(10) unsigned NOT NULL,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `serialless_quantity` int(10) unsigned NOT NULL DEFAULT 0,
  `serial_numbers` text DEFAULT NULL,
  `assigned_at` date NOT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `approval_document_path` varchar(255) DEFAULT NULL,
  `approval_document_name` varchar(255) DEFAULT NULL,
  `approval_document_mime` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_asset_assignments_warehouse_id_foreign` (`warehouse_id`),
  KEY `employee_asset_assignments_issued_by_user_id_foreign` (`issued_by_user_id`),
  KEY `employee_asset_assignments_employee_id_assigned_at_index` (`employee_id`,`assigned_at`),
  KEY `employee_asset_assignments_product_id_source_condition_index` (`product_id`,`source_condition`),
  CONSTRAINT `employee_asset_assignments_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  CONSTRAINT `employee_asset_assignments_issued_by_user_id_foreign` FOREIGN KEY (`issued_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employee_asset_assignments_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `employee_asset_assignments_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_asset_assignments`
--

LOCK TABLES `employee_asset_assignments` WRITE;
/*!40000 ALTER TABLE `employee_asset_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_asset_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_asset_returns`
--

DROP TABLE IF EXISTS `employee_asset_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_asset_returns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_asset_assignment_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `received_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `serialless_quantity` int(10) unsigned NOT NULL DEFAULT 0,
  `serial_numbers` text DEFAULT NULL,
  `returned_at` date NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_asset_returns_warehouse_id_foreign` (`warehouse_id`),
  KEY `employee_asset_returns_received_by_user_id_foreign` (`received_by_user_id`),
  KEY `asset_returns_assignment_date_idx` (`employee_asset_assignment_id`,`returned_at`),
  CONSTRAINT `employee_asset_returns_employee_asset_assignment_id_foreign` FOREIGN KEY (`employee_asset_assignment_id`) REFERENCES `employee_asset_assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_asset_returns_received_by_user_id_foreign` FOREIGN KEY (`received_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employee_asset_returns_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_asset_returns`
--

LOCK TABLES `employee_asset_returns` WRITE;
/*!40000 ALTER TABLE `employee_asset_returns` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_asset_returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_salary_revisions`
--

DROP TABLE IF EXISTS `employee_salary_revisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_salary_revisions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `old_salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `new_salary` decimal(12,2) NOT NULL,
  `effective_from` date NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_salary_revisions_employee_id_effective_from_index` (`employee_id`,`effective_from`),
  CONSTRAINT `employee_salary_revisions_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_salary_revisions`
--

LOCK TABLES `employee_salary_revisions` WRITE;
/*!40000 ALTER TABLE `employee_salary_revisions` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_salary_revisions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `employees` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `designation` varchar(255) DEFAULT NULL,
  `fleet_role` varchar(30) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `join_date` date DEFAULT NULL,
  `current_salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `salary_effective_from` date DEFAULT NULL,
  `yearly_bonus_count` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `bonus_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employees_fleet_role_index` (`fleet_role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) NOT NULL DEFAULT 'user',
  `expense_type` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `employee_id` bigint(20) unsigned DEFAULT NULL,
  `employee_name` varchar(255) DEFAULT NULL,
  `employee_designation` varchar(255) DEFAULT NULL,
  `salary_month` varchar(7) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(255) NOT NULL,
  `payment_account_id` bigint(20) unsigned DEFAULT NULL,
  `expense_date` date NOT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expenses_expense_type_expense_date_index` (`expense_type`,`expense_date`),
  KEY `expenses_category_expense_date_index` (`category`,`expense_date`),
  KEY `expenses_employee_id_foreign` (`employee_id`),
  KEY `expenses_account_ledger_index` (`payment_account_id`,`expense_date`,`id`),
  KEY `expenses_method_ledger_index` (`payment_method`,`expense_date`,`id`),
  CONSTRAINT `expenses_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_payment_account_id_foreign` FOREIGN KEY (`payment_account_id`) REFERENCES `payment_accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenses`
--

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `internet_packages`
--

DROP TABLE IF EXISTS `internet_packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `internet_packages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `speed` varchar(255) NOT NULL,
  `mikrotik_profile` varchar(255) DEFAULT NULL,
  `default_ip_pool` varchar(255) DEFAULT NULL,
  `monthly_price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `internet_packages_entry_by_index` (`entry_by`),
  KEY `internet_packages_entry_by_type_index` (`entry_by_type`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `internet_packages`
--

LOCK TABLES `internet_packages` WRITE;
/*!40000 ALTER TABLE `internet_packages` DISABLE KEYS */;
INSERT INTO `internet_packages` VALUES
(1,'2','user','default','Imported profile','default',NULL,0.00,'Automatically imported from MikroTik 1036 MikroTik (103.133.200.180). Set the package price before billing.','active','2026-08-10 23:06:56','2026-08-10 23:06:56'),
(2,'2','user','40 MB 180 IP','40m/40m','40 MB 180 IP','pool_180',500.00,'Automatically imported from MikroTik 1036 MikroTik (103.133.200.180). Set the package price before billing.','active','2026-08-10 23:06:56','2026-08-12 19:15:34'),
(3,'2','user','30 MB 180 IP','30m/30m','30 MB 180 IP','pool_180',500.00,'Automatically imported from MikroTik 1036 MikroTik (103.133.200.180). Set the package price before billing.','active','2026-08-10 23:06:56','2026-08-12 19:19:50'),
(4,'2','user','30 Mb Star','10m/30m','30 Mb Star','StarLink',10.00,'Automatically imported from MikroTik 1036 MikroTik (103.133.200.180). Set the package price before billing.','active','2026-08-10 23:06:56','2026-08-12 22:13:39'),
(5,'2','user','200 Mb Star','10m/200m','200 Mb Star','StarLink',500.00,'Automatically imported from MikroTik 1036 MikroTik (103.133.200.180). Set the package price before billing.','active','2026-08-10 23:06:56','2026-08-12 19:16:05'),
(6,'2','user','30 MB 141ranvid','30m/30m','30 MB 141ranvid','a141ranvid',500.00,'Automatically imported from MikroTik 1036 MikroTik (103.133.200.180). Set the package price before billing.','active','2026-08-10 23:06:56','2026-08-12 19:16:10'),
(7,'2','user','50 Mb_Travelshouse','50m/50m','50 Mb_Travelshouse','Travelshouse',750.00,'Automatically imported from MikroTik 1036 MikroTik (103.133.200.180). Set the package price before billing.','active','2026-08-10 23:06:56','2026-08-12 19:16:46'),
(8,'2','user','30 Mb_Travelshouse','30m/30m','30 Mb_Travelshouse','Travelshouse',500.00,'Automatically imported from MikroTik 1036 MikroTik (103.133.200.180). Set the package price before billing.','active','2026-08-10 23:06:56','2026-08-12 19:16:51'),
(9,'2','user','40 Mb Star','40m/40m','40 Mb Star','StarLink',500.00,'Automatically imported from MikroTik 1036 MikroTik (103.133.200.180). Set the package price before billing.','active','2026-08-10 23:06:56','2026-08-12 19:16:56'),
(10,'2','user','30 MB Saifulkst','30m/30m','30 MB Saifulkst','Saifulkst',500.00,'Automatically imported from MikroTik 1036 MikroTik (103.133.200.180). Set the package price before billing.','active','2026-08-10 23:06:56','2026-08-12 19:17:01'),
(11,'2','user','30 MB govt_college','30m/30m','30 MB govt_college','govt_college',500.00,'Automatically imported from MikroTik 1036 MikroTik (103.133.200.180). Set the package price before billing.','active','2026-08-10 23:06:56','2026-08-12 19:17:09'),
(12,'2','user','30 MB ZIlas','30m/30m','30 MB ZIlas','Zillas',500.00,'Automatically imported from MikroTik 1036 MikroTik (103.133.200.180). Set the package price before billing.','active','2026-08-10 23:06:56','2026-08-12 19:18:06'),
(13,'2','user','30 MB Lgedks','30m/30m','30 MB Lgedks','lgedks',500.00,'Automatically imported from MikroTik 1036 MikroTik (103.133.200.180). Set the package price before billing.','active','2026-08-10 23:06:56','2026-08-12 19:06:39'),
(14,'2','user','30 MB shena_nir','30m/30m','30 MB shena_nir','shena_nir',500.00,'Automatically imported from MikroTik 1036 MikroTik (103.133.200.180). Set the package price before billing.','active','2026-08-10 23:06:56','2026-08-12 19:18:12'),
(15,'2','user','50 MB shena_nir','50m/50m','50 MB shena_nir','shena_nir',500.00,'Automatically imported from MikroTik 1036 MikroTik (103.133.200.180). Set the package price before billing.','active','2026-08-10 23:06:56','2026-08-12 19:18:15'),
(16,'2','user','30 MB KPI','30m/30m','30 MB KPI','kpi_all',500.00,'Automatically imported from MikroTik 1036 MikroTik (103.133.200.180). Set the package price before billing.','active','2026-08-10 23:06:56','2026-08-12 19:18:22'),
(17,'2','user','50 MB KPI','60m/60m','50 MB KPI','kpi_all',750.00,'Automatically imported from MikroTik 1036 MikroTik (103.133.200.180). Set the package price before billing.','active','2026-08-10 23:06:56','2026-08-12 19:19:30'),
(18,'2','user','110 MB 141ranvid','110m/110m','110 MB 141ranvid','a141ranvid',1000.00,'Automatically imported from MikroTik 1036 MikroTik (103.133.200.180). Set the package price before billing.','active','2026-08-10 23:06:56','2026-08-12 19:18:40'),
(19,'2','user','100Mb_kpi_comdpt','100m/100m','100Mb_kpi_comdpt','kpi_comdpt',1000.00,'Automatically imported from MikroTik 1036 MikroTik (103.133.200.180). Set the package price before billing.','active','2026-08-10 23:06:56','2026-08-12 19:18:45'),
(20,'2','user','100 ZillaS','100m/100m','100 ZillaS','Zillas',1000.00,'Automatically imported from MikroTik 1036 MikroTik (103.133.200.180). Set the package price before billing.','active','2026-08-10 23:06:56','2026-08-12 19:18:50'),
(21,'2','user','50 MB mosharof_bgoly','50m/50m','50 MB mosharof_bgoly','mosharof_bgoly',750.00,'Automatically imported from MikroTik 1036 MikroTik (103.133.200.180). Set the package price before billing.','active','2026-08-10 23:06:56','2026-08-12 19:18:54'),
(22,'2','user','30 MB','30m/30m','30 MB','pool_180',500.00,'Automatically imported from MikroTik 1036 MikroTik (103.133.200.180). Set the package price before billing.','active','2026-08-10 23:06:56','2026-08-12 19:18:58'),
(23,'2','user','default-encryption','Imported profile','default-encryption',NULL,0.00,'Automatically imported from MikroTik 1036 MikroTik (103.133.200.180). Set the package price before billing.','active','2026-08-10 23:06:56','2026-08-10 23:06:56'),
(24,'2','user','inactive','10k/10k','inactive','inactive',0.00,'Automatically imported from MikroTik 1036 MikroTik (103.133.200.180). Set the package price before billing.','active','2026-08-11 20:18:01','2026-08-12 19:34:11');
/*!40000 ALTER TABLE `internet_packages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoice_items`
--

DROP TABLE IF EXISTS `invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_type` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `serial_numbers` text DEFAULT NULL,
  `serialless_quantity` int(10) unsigned NOT NULL DEFAULT 0,
  `warranty_days` int(10) unsigned DEFAULT NULL,
  `service_guarantee_days` int(10) unsigned DEFAULT NULL,
  `service_guarantee_until` date DEFAULT NULL,
  `service_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_items_invoice_id_foreign` (`invoice_id`),
  KEY `invoice_items_entry_by_index` (`entry_by`),
  KEY `invoice_items_entry_by_type_index` (`entry_by_type`),
  KEY `invoice_items_product_id_foreign` (`product_id`),
  CONSTRAINT `invoice_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoice_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoice_items`
--

LOCK TABLES `invoice_items` WRITE;
/*!40000 ALTER TABLE `invoice_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoice_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `reseller_id` bigint(20) unsigned DEFAULT NULL,
  `reseller_commission_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `reseller_commission_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `gross_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `invoice_no` varchar(255) NOT NULL,
  `billing_month` varchar(255) NOT NULL,
  `invoice_type` varchar(255) NOT NULL DEFAULT 'service',
  `subtotal` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_type` varchar(20) NOT NULL DEFAULT 'amount',
  `discount_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `vat` decimal(10,2) NOT NULL DEFAULT 0.00,
  `vat_type` varchar(20) NOT NULL DEFAULT 'amount',
  `vat_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `due_amount` decimal(10,2) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'unpaid',
  `finalized_at` timestamp NULL DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `payment_note` text DEFAULT NULL,
  `public_note` text DEFAULT NULL,
  `show_public_note` tinyint(1) NOT NULL DEFAULT 0,
  `private_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_invoice_no_unique` (`invoice_no`),
  KEY `invoices_billing_month_index` (`billing_month`),
  KEY `invoices_customer_id_index` (`customer_id`),
  KEY `invoices_entry_by_index` (`entry_by`),
  KEY `invoices_entry_by_type_index` (`entry_by_type`),
  KEY `invoices_reseller_id_foreign` (`reseller_id`),
  CONSTRAINT `invoices_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_reseller_id_foreign` FOREIGN KEY (`reseller_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` VALUES
(1,'2','user',348,NULL,0.00,0.00,0.00,'INV-2026-08-00348','2026-08','service',0.00,0.00,'amount',0.00,0.00,'amount',0.00,0.00,0.00,0.00,'unpaid',NULL,'2026-08-10',NULL,NULL,0,NULL,'2026-08-11 14:43:30','2026-08-11 14:43:30'),
(2,'2','user',348,NULL,0.00,0.00,500.00,'INV-2026-09-00348','2026-09','service',500.00,0.00,'amount',0.00,0.00,'amount',0.00,500.00,500.00,0.00,'paid',NULL,'2026-09-10',NULL,NULL,0,NULL,'2026-08-11 20:18:28','2026-08-11 20:18:28'),
(3,'2','user',348,NULL,0.00,0.00,500.00,'INV-2026-10-00348','2026-10','service',500.00,0.00,'amount',0.00,0.00,'amount',0.00,500.00,500.00,0.00,'paid',NULL,'2026-10-10',NULL,NULL,0,NULL,'2026-08-11 20:18:28','2026-08-11 20:18:28'),
(4,'2','user',348,NULL,0.00,0.00,500.00,'INV-2026-11-00348','2026-11','service',500.00,0.00,'amount',0.00,0.00,'amount',0.00,500.00,500.00,0.00,'paid',NULL,'2026-11-10',NULL,NULL,0,NULL,'2026-08-11 20:18:28','2026-08-11 20:18:28'),
(5,'2','user',348,NULL,0.00,0.00,500.00,'INV-2026-12-00348','2026-12','service',500.00,0.00,'amount',0.00,0.00,'amount',0.00,500.00,500.00,0.00,'paid',NULL,'2026-12-10',NULL,NULL,0,NULL,'2026-08-11 20:18:28','2026-08-11 20:18:28'),
(6,'2','user',348,NULL,0.00,0.00,500.00,'INV-2027-01-00348','2027-01','service',500.00,0.00,'amount',0.00,0.00,'amount',0.00,500.00,500.00,0.00,'paid',NULL,'2027-01-10',NULL,NULL,0,NULL,'2026-08-11 20:18:28','2026-08-11 20:18:28'),
(7,'2','user',348,NULL,0.00,0.00,500.00,'INV-2027-02-00348','2027-02','service',500.00,0.00,'amount',0.00,0.00,'amount',0.00,500.00,500.00,0.00,'paid',NULL,'2027-02-10',NULL,NULL,0,NULL,'2026-08-11 20:18:28','2026-08-11 20:21:36'),
(8,'2','user',348,NULL,0.00,0.00,500.00,'INV-2027-03-00348','2027-03','service',500.00,0.00,'amount',0.00,0.00,'amount',0.00,500.00,500.00,0.00,'paid',NULL,'2027-03-10',NULL,NULL,0,NULL,'2026-08-11 20:21:37','2026-08-12 21:15:58'),
(9,'5','user',321,NULL,0.00,0.00,500.00,'INV-2026-08-00321','2026-08','service',500.00,0.00,'amount',0.00,0.00,'amount',0.00,500.00,500.00,0.00,'paid',NULL,'2026-08-10',NULL,NULL,0,NULL,'2026-08-12 21:47:09','2026-08-12 21:47:09'),
(10,'5','user',321,NULL,0.00,0.00,500.00,'INV-2026-09-00321','2026-09','service',500.00,0.00,'amount',0.00,0.00,'amount',0.00,500.00,500.00,0.00,'paid',NULL,'2026-09-10',NULL,NULL,0,NULL,'2026-08-12 21:48:10','2026-08-12 21:48:10'),
(11,'5','user',321,NULL,0.00,0.00,500.00,'INV-2026-10-00321','2026-10','service',500.00,0.00,'amount',0.00,0.00,'amount',0.00,500.00,500.00,0.00,'paid',NULL,'2026-10-10',NULL,NULL,0,NULL,'2026-08-12 21:48:10','2026-08-12 21:48:10'),
(12,'system','system',305,NULL,0.00,0.00,10.00,'INV-2026-08-00305','2026-08','service',10.00,0.00,'amount',0.00,0.00,'amount',0.00,10.00,10.00,0.00,'paid',NULL,'2026-08-10',NULL,NULL,0,NULL,'2026-08-12 23:05:48','2026-08-12 23:05:48');
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
(1,'0001_01_01_000000_create_users_table',1),
(2,'0001_01_01_000001_create_cache_table',1),
(3,'0001_01_01_000002_create_jobs_table',1),
(4,'2026_04_26_000000_create_isp_management_tables',1),
(5,'2026_05_04_000001_update_invoices_allow_multiple_per_month',1),
(6,'2026_05_04_000002_create_invoice_items_table',1),
(7,'2026_05_06_000001_remove_invoice_type_unique_constraint',1),
(8,'2026_05_06_000002_add_vat_to_invoices_table',1),
(9,'2026_05_06_000003_create_payment_accounts_table',1),
(10,'2026_05_06_000004_add_opening_balance_to_payment_accounts_table',1),
(11,'2026_05_06_000005_add_finalized_at_to_invoices_table',1),
(12,'2026_05_06_000006_create_roles_and_permissions_tables',1),
(13,'2026_05_06_000007_create_default_admin_user',1),
(14,'2026_05_06_000008_create_mikrotik_routers_table',1),
(15,'2026_05_06_000009_add_mikrotik_login_fields',1),
(16,'2026_05_06_000010_add_mikrotik_router_target_to_customers_table',1),
(17,'2026_05_06_000011_add_account_balance_to_customers_table',1),
(18,'2026_05_06_000012_create_bkash_sms_payments_table',1),
(19,'2026_05_06_000013_add_ref_and_allow_duplicate_bkash_sms_trx_ids',1),
(20,'2026_05_06_000014_add_unique_ledger_trx_id_to_bkash_sms_payments',1),
(21,'2026_05_06_000015_add_connection_status_to_mikrotik_routers_table',1),
(22,'2026_05_06_000016_add_status_since_to_mikrotik_routers_table',1),
(23,'2026_05_06_000017_add_never_suspend_to_customers_table',1),
(24,'2026_05_06_000018_add_pppoe_sync_settings_to_mikrotik_routers_table',1),
(25,'2026_05_06_000019_add_grace_period_to_customers_table',1),
(26,'2026_05_06_000020_create_payment_allocations_and_customer_balance_transactions',1),
(27,'2026_05_06_000021_remap_bkash_sms_payments_to_sms_device_accounts',1),
(28,'2026_05_06_000022_add_entry_by_to_application_tables',1),
(29,'2026_05_06_000023_add_entry_by_type_and_backfill_entry_by',1),
(30,'2026_05_12_000001_create_expenses_table',1),
(31,'2026_05_12_000002_add_manage_expenses_permission',1),
(32,'2026_05_12_000003_create_employees_and_salary_revisions',1),
(33,'2026_05_18_000001_create_olt_onus_table',1),
(34,'2026_05_18_000002_create_olt_devices_and_live_fields',1),
(35,'2026_05_18_000003_add_access_method_to_olt_devices',1),
(36,'2026_05_18_000004_add_read_context_commands_to_olt_devices',1),
(37,'2026_05_18_000005_add_pon_ports_to_olt_devices',1),
(38,'2026_05_18_000006_convert_olt_tables_to_utf8mb4',1),
(39,'2026_05_18_000007_merge_duplicate_olt_onu_live_rows',1),
(40,'2026_05_18_000008_clear_stale_olt_parser_errors',1),
(41,'2026_05_18_000009_add_olt_onu_register_history_fields',1),
(42,'2026_05_18_000010_add_onu_alarm_command_to_olt_devices',1),
(43,'2026_05_18_000011_add_brand_profile_to_olt_devices',1),
(44,'2026_05_18_000012_add_onu_vlan_command_to_olt_devices',1),
(45,'2026_05_18_000013_add_onu_learned_macs',1),
(46,'2026_05_18_000014_create_olt_protocol_profiles_table',1),
(47,'2026_05_18_000015_update_hsgq_gpon_profile_polling_defaults',1),
(48,'2026_05_18_000016_fix_hsgq_gpon_vlan_mac_commands',1),
(49,'2026_05_18_000017_add_olt_write_commands_to_protocol_profiles',1),
(50,'2026_05_18_000018_set_hsgq_gpon_vlan_write_command',1),
(51,'2026_05_18_000019_fix_hsgq_gpon_native_vlan_write_command',1),
(52,'2026_05_19_000001_fix_hsgq_gpon_native_vlan_port_id',1),
(53,'2026_05_19_000002_restore_hsgq_gpon_context_native_vlan',1),
(54,'2026_05_22_000001_add_note_to_olt_onus_table',1),
(55,'2026_06_02_000001_add_snmp_polling_to_olt_devices',1),
(56,'2026_06_02_000002_make_customer_connection_id_nullable',1),
(57,'2026_06_02_000003_add_party_roles_to_customers',1),
(58,'2026_06_02_000004_create_purchase_bills_and_product_serials',1),
(59,'2026_06_02_000005_add_brand_and_subcategory_to_products',1),
(60,'2026_06_02_000006_create_product_categories_table',1),
(61,'2026_06_02_000007_add_track_inventory_to_products',1),
(62,'2026_06_02_000008_add_barcode_serial_and_warranty_defaults_to_products',1),
(63,'2026_06_04_000001_add_product_and_serials_to_invoice_items',1),
(64,'2026_06_04_000002_add_service_and_warranty_claims',1),
(65,'2026_06_05_000001_create_network_map_features_table',1),
(66,'2026_06_15_000001_add_adjustment_inputs_to_invoices_table',1),
(67,'2026_06_15_000002_add_notes_to_invoices_table',1),
(68,'2026_06_15_000003_create_app_settings_and_add_payment_note_to_invoices',1),
(69,'2026_06_19_000001_add_serialless_quantity_to_stock_lines',1),
(70,'2026_06_19_000002_add_warehouse_inventory',1),
(71,'2026_06_19_000003_add_serial_numbers_to_stock_movements',1),
(72,'2026_06_19_000005_create_quotations',1),
(73,'2026_07_12_000001_create_record_versions_table',1),
(74,'2026_07_12_000002_add_payment_ledger_indexes',1),
(75,'2026_07_14_000001_create_sale_returns_table',1),
(76,'2026_07_14_000002_add_finalized_at_to_purchase_bills',1),
(77,'2026_07_15_000001_create_employee_asset_assignments',1),
(78,'2026_07_15_000002_add_value_to_employee_asset_assignments',1),
(79,'2026_07_15_000003_add_documents_to_in_house_and_purchase_bills',1),
(80,'2026_07_15_000004_create_fleet_management_tables',1),
(81,'2026_07_15_000006_add_credit_application_to_sale_returns',1),
(82,'2026_07_15_000007_add_credit_total_to_sale_returns',1),
(83,'2026_07_15_000008_add_work_name_to_vehicle_maintenance_logs',1),
(84,'2026_07_16_000001_add_media_to_vehicle_maintenance_logs',1),
(85,'2026_07_16_000001_create_organizations_and_print_logs',1),
(86,'2026_07_16_000002_add_print_preferences_and_bank_info_to_organizations',1),
(87,'2026_07_16_000003_add_finalization_to_fleet_records',1),
(88,'2026_07_18_000001_add_olt_background_refresh_and_port_controls',1),
(89,'2026_07_18_000002_fix_hsgq_gpon_hgu_vlan_port_type',1),
(90,'2026_07_18_000003_add_ethernet_port_count_to_olt_onus',1),
(91,'2026_07_18_000004_add_mikrotik_import_workflow',2),
(92,'2026_07_18_000005_create_app_ip_pools',3),
(93,'2026_07_18_000006_add_service_validity_to_customers_table',4),
(94,'2026_07_18_000007_add_default_ip_pool_to_internet_packages',5),
(95,'2026_07_19_000001_add_pppoe_address_tracking_to_customers',6),
(96,'2026_07_19_000002_set_default_router_sync_interval_to_sixty_minutes',6),
(97,'2026_07_19_000003_add_reseller_wallet_features',6),
(98,'2026_07_19_000004_add_reseller_commission_features',6),
(99,'2026_08_11_000001_create_permission_user_denials_table',7),
(100,'2026_08_12_000001_create_customer_mikrotik_router_table',8),
(101,'2026_08_11_000002_add_payment_defaults_to_users_table',9),
(102,'2026_08_12_000002_repair_bkash_day_first_payment_dates',9);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mikrotik_imported_ip_pools`
--

DROP TABLE IF EXISTS `mikrotik_imported_ip_pools`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mikrotik_imported_ip_pools` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `mikrotik_router_id` bigint(20) unsigned NOT NULL,
  `routeros_id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `ranges` text DEFAULT NULL,
  `next_pool` varchar(255) DEFAULT NULL,
  `source_note` text DEFAULT NULL,
  `imported_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mikrotik_imported_ip_pools_mikrotik_router_id_routeros_id_unique` (`mikrotik_router_id`,`routeros_id`),
  CONSTRAINT `mikrotik_imported_ip_pools_mikrotik_router_id_foreign` FOREIGN KEY (`mikrotik_router_id`) REFERENCES `mikrotik_routers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mikrotik_imported_ip_pools`
--

LOCK TABLES `mikrotik_imported_ip_pools` WRITE;
/*!40000 ALTER TABLE `mikrotik_imported_ip_pools` DISABLE KEYS */;
INSERT INTO `mikrotik_imported_ip_pools` VALUES
(1,1,'*6','StarLink','10.99.22.0/24',NULL,NULL,'2026-08-12 19:32:54','2026-08-10 23:07:10','2026-08-12 19:32:54'),
(2,1,'*9','Zillas','10.99.4.0/24',NULL,NULL,'2026-08-12 19:32:54','2026-08-10 23:07:10','2026-08-12 19:32:54'),
(3,1,'*A','Travelshouse','10.99.3.0/24',NULL,NULL,'2026-08-12 19:32:54','2026-08-10 23:07:10','2026-08-12 19:32:54'),
(4,1,'*B','Saifulkst','10.99.2.0/24',NULL,NULL,'2026-08-12 19:32:54','2026-08-10 23:07:10','2026-08-12 19:32:54'),
(5,1,'*C','govt_college','10.99.5.0/24',NULL,NULL,'2026-08-12 19:32:54','2026-08-10 23:07:10','2026-08-12 19:32:54'),
(6,1,'*D','a141ranvid','10.99.1.0/24',NULL,NULL,'2026-08-12 19:32:54','2026-08-10 23:07:10','2026-08-12 19:32:54'),
(7,1,'*E','lgedks','10.99.6.0/24',NULL,NULL,'2026-08-12 19:32:54','2026-08-10 23:07:10','2026-08-12 19:32:54'),
(8,1,'*F','pool_180','10.99.180.0/24',NULL,NULL,'2026-08-12 19:32:54','2026-08-10 23:07:10','2026-08-12 19:32:54'),
(9,1,'*10','shena_nir','10.99.7.0/24',NULL,NULL,'2026-08-12 19:32:54','2026-08-10 23:07:10','2026-08-12 19:32:54'),
(10,1,'*11','kpi_all','10.99.8.0/24',NULL,NULL,'2026-08-12 19:32:54','2026-08-10 23:07:10','2026-08-12 19:32:54'),
(11,1,'*12','kpi_comdpt','10.99.9.0/24',NULL,NULL,'2026-08-12 19:32:54','2026-08-10 23:07:10','2026-08-12 19:32:54'),
(12,1,'*13','mosharof_bgoly','10.99.10.0/24',NULL,NULL,'2026-08-12 19:32:54','2026-08-10 23:07:10','2026-08-12 19:32:54'),
(13,1,'*14','inactive','10.99.99.0/24',NULL,NULL,'2026-08-12 19:32:54','2026-08-12 19:31:55','2026-08-12 19:32:54');
/*!40000 ALTER TABLE `mikrotik_imported_ip_pools` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mikrotik_imported_profiles`
--

DROP TABLE IF EXISTS `mikrotik_imported_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mikrotik_imported_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `mikrotik_router_id` bigint(20) unsigned NOT NULL,
  `routeros_id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `local_address` varchar(255) DEFAULT NULL,
  `remote_address` varchar(255) DEFAULT NULL,
  `rate_limit` varchar(255) DEFAULT NULL,
  `disabled` tinyint(1) NOT NULL DEFAULT 0,
  `source_note` text DEFAULT NULL,
  `imported_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mikrotik_imported_profiles_mikrotik_router_id_routeros_id_unique` (`mikrotik_router_id`,`routeros_id`),
  CONSTRAINT `mikrotik_imported_profiles_mikrotik_router_id_foreign` FOREIGN KEY (`mikrotik_router_id`) REFERENCES `mikrotik_routers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mikrotik_imported_profiles`
--

LOCK TABLES `mikrotik_imported_profiles` WRITE;
/*!40000 ALTER TABLE `mikrotik_imported_profiles` DISABLE KEYS */;
INSERT INTO `mikrotik_imported_profiles` VALUES
(1,1,'*0','default',NULL,NULL,NULL,0,NULL,'2026-08-11 20:18:01','2026-08-10 23:06:56','2026-08-11 20:18:01'),
(2,1,'*1','40 MB 180 IP','10.1.1.1','pool_180','40m/40m',0,NULL,'2026-08-11 20:18:01','2026-08-10 23:06:56','2026-08-11 20:18:01'),
(3,1,'*2','30 MB 180 IP','10.1.1.1','pool_180','30m/30m',0,NULL,'2026-08-11 20:18:01','2026-08-10 23:06:56','2026-08-11 20:18:01'),
(4,1,'*4','30 Mb Star','10.1.1.1','StarLink','10m/30m',0,NULL,'2026-08-11 20:18:01','2026-08-10 23:06:56','2026-08-11 20:18:01'),
(5,1,'*5','200 Mb Star','10.1.1.1','StarLink','10m/200m',0,NULL,'2026-08-11 20:18:01','2026-08-10 23:06:56','2026-08-11 20:18:01'),
(6,1,'*6','30 MB 141ranvid','10.1.1.1','a141ranvid','30m/30m',0,NULL,'2026-08-11 20:18:01','2026-08-10 23:06:56','2026-08-11 20:18:01'),
(7,1,'*7','50 Mb_Travelshouse','10.1.1.1','Travelshouse','50m/50m',0,NULL,'2026-08-11 20:18:01','2026-08-10 23:06:56','2026-08-11 20:18:01'),
(8,1,'*8','30 Mb_Travelshouse','10.1.1.1','Travelshouse','30m/30m',0,NULL,'2026-08-11 20:18:01','2026-08-10 23:06:56','2026-08-11 20:18:01'),
(9,1,'*9','40 Mb Star','10.1.1.1','StarLink','40m/40m',0,NULL,'2026-08-11 20:18:01','2026-08-10 23:06:56','2026-08-11 20:18:01'),
(10,1,'*A','30 MB Saifulkst','10.1.1.1','Saifulkst','30m/30m',0,NULL,'2026-08-11 20:18:01','2026-08-10 23:06:56','2026-08-11 20:18:01'),
(11,1,'*B','30 MB govt_college','10.1.1.1','govt_college','30m/30m',0,NULL,'2026-08-11 20:18:01','2026-08-10 23:06:56','2026-08-11 20:18:01'),
(12,1,'*C','30 MB ZIlas','10.1.1.1','Zillas','30m/30m',0,NULL,'2026-08-11 20:18:01','2026-08-10 23:06:56','2026-08-11 20:18:01'),
(13,1,'*D','30 MB Lgedks','10.1.1.1','lgedks','30m/30m',0,NULL,'2026-08-11 20:18:01','2026-08-10 23:06:56','2026-08-11 20:18:01'),
(14,1,'*E','30 MB shena_nir','10.1.1.1','shena_nir','30m/30m',0,NULL,'2026-08-11 20:18:01','2026-08-10 23:06:56','2026-08-11 20:18:01'),
(15,1,'*10','50 MB shena_nir','10.1.1.1','shena_nir','50m/50m',0,NULL,'2026-08-11 20:18:01','2026-08-10 23:06:56','2026-08-11 20:18:01'),
(16,1,'*11','30 MB KPI','10.1.1.1','kpi_all','30m/30m',0,NULL,'2026-08-11 20:18:01','2026-08-10 23:06:56','2026-08-11 20:18:01'),
(17,1,'*12','50 MB KPI','10.1.1.1','kpi_all','60m/60m',0,NULL,'2026-08-11 20:18:01','2026-08-10 23:06:56','2026-08-11 20:18:01'),
(18,1,'*13','110 MB 141ranvid','10.1.1.1','a141ranvid','110m/110m',0,NULL,'2026-08-11 20:18:01','2026-08-10 23:06:56','2026-08-11 20:18:01'),
(19,1,'*14','100Mb_kpi_comdpt','10.1.1.1','kpi_comdpt','100m/100m',0,NULL,'2026-08-11 20:18:01','2026-08-10 23:06:56','2026-08-11 20:18:01'),
(20,1,'*15','100 ZillaS','10.1.1.1','Zillas','100m/100m',0,NULL,'2026-08-11 20:18:01','2026-08-10 23:06:56','2026-08-11 20:18:01'),
(21,1,'*16','50 MB mosharof_bgoly','10.1.1.1','mosharof_bgoly','50m/50m',0,NULL,'2026-08-11 20:18:01','2026-08-10 23:06:56','2026-08-11 20:18:01'),
(22,1,'*17','30 MB','10.1.1.1','mosharof_bgoly','30m/30m',0,NULL,'2026-08-11 20:18:01','2026-08-10 23:06:56','2026-08-11 20:18:01'),
(23,1,'*FFFFFFFE','default-encryption',NULL,NULL,NULL,0,NULL,'2026-08-11 20:18:01','2026-08-10 23:06:56','2026-08-11 20:18:01'),
(24,1,'*18','inactive',NULL,NULL,NULL,0,NULL,'2026-08-11 20:18:01','2026-08-11 20:18:01','2026-08-11 20:18:01');
/*!40000 ALTER TABLE `mikrotik_imported_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mikrotik_imported_secrets`
--

DROP TABLE IF EXISTS `mikrotik_imported_secrets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mikrotik_imported_secrets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `mikrotik_router_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `routeros_id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `password` text DEFAULT NULL,
  `service` varchar(255) DEFAULT NULL,
  `profile` varchar(255) DEFAULT NULL,
  `local_address` varchar(255) DEFAULT NULL,
  `remote_address` varchar(255) DEFAULT NULL,
  `disabled` tinyint(1) NOT NULL DEFAULT 0,
  `router_comment` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `imported_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mikrotik_imported_secrets_mikrotik_router_id_routeros_id_unique` (`mikrotik_router_id`,`routeros_id`),
  KEY `mikrotik_imported_secrets_customer_id_foreign` (`customer_id`),
  CONSTRAINT `mikrotik_imported_secrets_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `mikrotik_imported_secrets_mikrotik_router_id_foreign` FOREIGN KEY (`mikrotik_router_id`) REFERENCES `mikrotik_routers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=359 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mikrotik_imported_secrets`
--

LOCK TABLES `mikrotik_imported_secrets` WRITE;
/*!40000 ALTER TABLE `mikrotik_imported_secrets` DISABLE KEYS */;
INSERT INTO `mikrotik_imported_secrets` VALUES
(1,1,1,'*2','customs','eyJpdiI6IjFqWXZEN2FVakkyMGpUSndkM3hsL3c9PSIsInZhbHVlIjoiTFZ5RWRQVDd4UmdPS1Rack1kb2x5dz09IiwibWFjIjoiYzFhNzQ2NjYyZWNkOTRhOGQ4YjM1OGFmN2Q4ZDdjNDlkNGFjN2M1Yjc3MjgxMzA0NGRiNzYwMDEyMWIzYmYyZiIsInRhZyI6IiJ9','any','40 MB 180 IP',NULL,NULL,1,NULL,NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:17'),
(2,1,100,'*3','lipu','eyJpdiI6IlJLYTQyZnJqQmxqNEtMQkc0bDhHN0E9PSIsInZhbHVlIjoiRE1ocmZaU05vSUdvS1ZFalBkLzY4Zz09IiwibWFjIjoiMzViMzgxMTIzM2U3MTNiMmI4ZGMzMDRkNzljYmJiZDA2ODU0MzkwN2RmYjBkYzA2ZDdjZDZkNGMwYTkyOWQ3NCIsInRhZyI6IiJ9','any','30 MB govt_college',NULL,NULL,0,'Anike',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:54'),
(3,1,2,'*4','6road_azizul','eyJpdiI6InBtTUtkOGJYMHRKTW9tNmEvVHREaVE9PSIsInZhbHVlIjoiSlErYldBYVBxci96QityN1pwTmVOUT09IiwibWFjIjoiMTc4Nzc5MWU2OTJjYjJkZDBiZDY4NzI1YjdmMTFlNzhlMWQwMGQyYjU0MGJlZjdjZWM0OWIxZDNiMGJlNjJhYiIsInRhZyI6IiJ9','any','30 MB govt_college',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:17'),
(4,1,200,'*5','shuchona_israfil','eyJpdiI6InVyL2cwSVRVc01MVWNtc25sWmF3Q0E9PSIsInZhbHVlIjoiRlpoYnRlTXBZZlhuU1BoSW5uYi9tZz09IiwibWFjIjoiYWJiNmJlMDVmNGViYWZhOWRmNDllNTkyNzEyYjI0NWY1YTYxNTMyMmMwNGY0ZDg0Yjg4N2U4ODIxNjBlNmY2MyIsInRhZyI6IiJ9','any','50 Mb_Travelshouse',NULL,NULL,0,'Anike',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:26'),
(5,1,3,'*6','customsb','eyJpdiI6IkpJUC9IMTg0TmNlQVpkWDBreFhWanc9PSIsInZhbHVlIjoiNVM0Z2pQckwreGdFOWY3S1RMbjBxQT09IiwibWFjIjoiN2IwZGRlNGRjMTRjMTA2Yjk2NDJkOTdlZmI3NmY3ODE0NWY5M2M3ODBhZjQ1Mjg4Mzc5ZjVmZTcwNDRlODVjMiIsInRhZyI6IiJ9','any','30 MB Saifulkst',NULL,NULL,0,'Anike',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:17'),
(6,1,4,'*7','bnp','eyJpdiI6ImlCMUlDNjlwL3ZWblFhZWJvbU5XVVE9PSIsInZhbHVlIjoiUDJ5VVBXemVveWh3RXNRSkFYMy9Vdz09IiwibWFjIjoiZTE5ZTM5NGRmYTFlODJlNTllYjNjNDhhNmY5MGJiYjlhZTdiOTA3MjdiMGY1MTAwOGI5NDRmODZkYjY2NmFiNSIsInRhZyI6IiJ9','any','50 MB KPI',NULL,NULL,0,'Anike BNP Office',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:17'),
(7,1,300,'*8','ttcsirhome','eyJpdiI6Ilp3anhkYnZnUmF2THlaTGlyM3A5OUE9PSIsInZhbHVlIjoiazdxK01ZSkRpbHhZYVhMTFdwR0t6Zz09IiwibWFjIjoiNTgzZTFlNWFiN2RmN2I1ZGUzMGY2ZGRmOTA0YjU5OWI5N2FmNjA3MzhhMTlkYzAxZTg3ZmRmYmM5NWRlZWQwNyIsInRhZyI6IiJ9','any','30 MB govt_college',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:44'),
(8,1,101,'*B','nabil','eyJpdiI6ImF0Zk1DQjlqNkp0Wnhtd1Iyb25vdGc9PSIsInZhbHVlIjoiKyswS1F2MTJKeDZuNndONUh5TUFKUT09IiwibWFjIjoiZDI5ZTdmNmQzYjNjNGEzZjg5Y2VhMDYzZjFmNmFlNTNlMmU2YjZjNDZmMzY4YmE0Mjg2ZDljYmYwMTc3NTBmYiIsInRhZyI6IiJ9','any','30 MB govt_college',NULL,NULL,0,'Anike',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:54'),
(9,1,5,'*C','bazar_biplob_vi','eyJpdiI6InJQcUo4dHhMMm5pS014elYrZWtEdGc9PSIsInZhbHVlIjoiYis4aXE0RzFmVjJNcVR1OHBoVlFmQT09IiwibWFjIjoiNGI1MDYwZGNmZjEwMzAxYTBjMWFiNTJhODhkNTQ0NWQ0ZGRkNzFlOWQ3YTgzMzBjYTdkOTI4ZDAwZjhlMDZiYSIsInRhZyI6IiJ9','any','30 MB govt_college',NULL,NULL,0,'Anike',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:17'),
(10,1,102,'*D','koyel_vi','eyJpdiI6IjU1TDdubWJzMVA4Uk1FMzJmZngydkE9PSIsInZhbHVlIjoiUVRPaXFOelF1bWlyUnZxSWlXeGVrUT09IiwibWFjIjoiZDY0YjEwMDJjYjRjZGIwNTFmZTU0NWZhNjZkYjZjMDZmZjA1ODIzZjJhMWFmZmE2MzExZjM4OTljODg5ZTdjMiIsInRhZyI6IiJ9','any','30 MB 141ranvid',NULL,NULL,0,'Anike',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:54'),
(11,1,103,'*E','kollol_home','eyJpdiI6IjFTUDR2Q2hOMGd4MHJPam5WS0NrR3c9PSIsInZhbHVlIjoicDkxWmpvZkp0VkZWVzhRNkppbG1NQT09IiwibWFjIjoiYmIyNjg0OTRiNDAyMzY2M2RlZjM3YTcxNjYwZjVjYTE2MmFkMmRjYjg1ZDgzOTQ3YzFlYTk5ZjdkODgzNWI0NSIsInRhZyI6IiJ9','any','100Mb_kpi_comdpt',NULL,NULL,0,'Anike',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:54'),
(12,1,6,'*F','agrani','eyJpdiI6ImRScnRmZmV4SHNXbW5mYzkrNEtyTVE9PSIsInZhbHVlIjoiRk81aVNhcitFREJRaFRyeE1EOXVPdz09IiwibWFjIjoiNDdlOTA2MjVkMTcwMzgzYzRjNTY3MWNjZmNlNzAzYzI3ZmNkYzNjNjBkMWI0Y2FiOWViNWNjMTA4NjI3Y2I5MSIsInRhZyI6IiJ9','any','110 MB 141ranvid',NULL,NULL,0,'Anike',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:17'),
(13,1,7,'*10','firoz_vi','eyJpdiI6IndXT1ZKKzFiQUhWUGR6aUpIeGRyU3c9PSIsInZhbHVlIjoiNVFzTkZmR3AveWpBcG1KOXMwWDF3QT09IiwibWFjIjoiOTAxNzRiMTU2NzkyMWVhZWRlY2M1MTcyMThkYWNhOTdhNTRhYzBiNDIxOWY3NzE5OGVmZDVhMWQ1MmU4YzQxYSIsInRhZyI6IiJ9','any','30 MB govt_college',NULL,NULL,0,'Anike',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:17'),
(14,1,201,'*11','sagor_mosso','eyJpdiI6IjBCcENpdlpLSjEwYllpcDJPLytNYnc9PSIsInZhbHVlIjoiWXE2YlBDQmEyK1YxTWtaSVZ2WFc0QT09IiwibWFjIjoiZTkxOGU1N2I4ZDZhNzRmNWI0MGQ4YWNhMzE0ZGNkYzVmMTNiNGEzZDg5YTdjMGQ1Y2VjMWU5YzhhYTk4OWUxYyIsInRhZyI6IiJ9','any','30 MB govt_college',NULL,NULL,0,'Anike',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:26'),
(15,1,8,'*13','dolon_mama','eyJpdiI6Ik9taG5BRk5aSFJSZDlwdVN4clhkY2c9PSIsInZhbHVlIjoiNHp1RGpwYkkxSkhRS0puV2tYOXI5dz09IiwibWFjIjoiYTg5YTQ1OTFjMWRlNzY4MjcyZjk4ZjM0ZmQ5NzI3MTI5NDg3MjZiNDc1NDE1N2M2NDlmMjZkNjIzMjAwYzA5MSIsInRhZyI6IiJ9','any','50 Mb_Travelshouse',NULL,NULL,0,'Anike',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:17'),
(16,1,202,'*15','Rashid_Rony','eyJpdiI6InQ5dkFRcGNpSFhka2lkRkUrT1FmdXc9PSIsInZhbHVlIjoidHYrZXJSTDAzbVliNURYMk1jbVNDUT09IiwibWFjIjoiZmI1MzhjNDI5NTBjY2E3NTFlNmRlNDc2ZDEyYWI5ZTUwZTM4OTJjYTJjMmVlNWMyOGQyNDk1ZDY2NzllYWIzYiIsInRhZyI6IiJ9','any','30 MB Saifulkst',NULL,NULL,0,'Anike',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:26'),
(17,1,104,'*16','knb_gm','eyJpdiI6Imd1dExMZEJZK1h0MHJ6M1JDbGNkUnc9PSIsInZhbHVlIjoiNWpDelQ1dTVGeTZHZ3RBQ2xCdVRNQT09IiwibWFjIjoiODMwZTFkNmVhZWMyYzQyYjQyNGMxOTdmYzEwYjdhN2VkMzU5MWM2NDFkYjYwNDI3MzEyZjdmOWJkMGFiYWE1MyIsInRhZyI6IiJ9','any','30 MB 180 IP',NULL,NULL,1,'Anike',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:54'),
(18,1,203,'*17','romel_vi_ety','eyJpdiI6IlkzMy84NXhkV3lIUHZ4Q1VzSGRUZXc9PSIsInZhbHVlIjoiN1VZaTAvNUpWUzY1ZktNbWNuZ2MvQT09IiwibWFjIjoiYjMzOTM3MDU3YzdmMThjYWNkMWJkYzUwNzAwMDhkMjFkOGM4YmQ2M2Y5NjlmZDc2OTdkMTM2NjBmNTUwNzJmNSIsInRhZyI6IiJ9','any','30 MB Saifulkst',NULL,NULL,0,'Anike',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:26'),
(19,1,9,'*18','dr_shahariar_rajon','eyJpdiI6ImkvdnRZVmxLVS9saEF6K1oyZ0xUaVE9PSIsInZhbHVlIjoiNEVBN01JYjZIemhhNUV5c1dWZjg3QT09IiwibWFjIjoiMDk0YjU1NjliODhiZDU1NTdiNjBhYWNkMjIzZjg3MGE4ZjNmMmViMGIxMWM4MmUyM2Y1YzZmMDgxZWQ2M2YyYiIsInRhZyI6IiJ9','any','30 MB Lgedks',NULL,NULL,0,'Anike',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:17'),
(20,1,204,'*1B','prottoy_ripon _dada','eyJpdiI6IlVYS1hBejIyVnlpaW44dEEyS3RUSVE9PSIsInZhbHVlIjoiVS81TzQyRGpRT1FTMFpKeWZacXNJdz09IiwibWFjIjoiZTI3MGZlMGI5MDMxY2JiYWU4MDViNzM4NzE1YTdiNzA1OGM5NTExZWNiNzQ3ZWZjZWMyMmI1YjNlYmNmMTljMyIsInRhZyI6IiJ9','any','110 MB 141ranvid',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:26'),
(21,1,10,'*1D','access','eyJpdiI6IlZlQ1NzT1F3Y3BHVnRlNmNoSDRPZ2c9PSIsInZhbHVlIjoid1BuMml2RjZOeEV3NnBNNlFVb3VSQT09IiwibWFjIjoiZDlhNGJhNjFjZGM5OTQ5OWYzMTU2MDFjYWIxNDYzMmVhYjFiNWE4OTAxNDhlYzIyMGE4MTM0ZGEyMGIwYjk5NSIsInRhZyI6IiJ9','any','30 MB Saifulkst',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:17'),
(22,1,205,'*1E','police_hospital','eyJpdiI6ImFHOWQwNFVPNG03dmRoRXpyR0hsdVE9PSIsInZhbHVlIjoiMDZaYjlhWk8xK0VNUDlTNmlpWFFidz09IiwibWFjIjoiODAwNzBhMDI4NDU3MDM5ZWZhYjk1NGJhYTNiNmQ5YjU2ZTAxYjUxOTc4NDYxMGFjMTYxNjcwNDg5MGUzZjBjZSIsInRhZyI6IiJ9','any','30 MB 141ranvid',NULL,NULL,0,'Police',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:27'),
(23,1,206,'*1F','police_sp_home','eyJpdiI6IkU0bWZ5WnY5MHNwYU9BcmtwMkp4dVE9PSIsInZhbHVlIjoiY0t5K3I2S1I2cE1lSjZNODNoemJQdz09IiwibWFjIjoiM2Y1ZDVhYmE0MGVmOTliNWRlYTUyYjEyNWQxYzQ0ZjA3YzdlM2RjYTllOGJiY2M1ZDk3M2I4NmZmZDY3ZWJjOSIsInRhZyI6IiJ9','any','40 MB 180 IP',NULL,NULL,1,'Police',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:27'),
(24,1,207,'*20','police_sp_office','eyJpdiI6IkpSaTBOTXpQalZiQVlOeFdrOXJoS3c9PSIsInZhbHVlIjoiblhUQmpkZzlXQjgrL3NraDRqdXhKUT09IiwibWFjIjoiYjkwNGMwOGE1MWUxOGRjOTFmZWEyMGNjZjc0MGMzNzZmOTA1OWYyYmEzMTVjZWQxOGViYzRkMWMxMjI1ODI5ZiIsInRhZyI6IiJ9','any','40 MB 180 IP',NULL,NULL,1,'Police',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:27'),
(25,1,105,'*21','police_asp_admin','eyJpdiI6IllGWXNSZ04xN2NESDU5ejdoY0dtZXc9PSIsInZhbHVlIjoidDVnS1pkV1ljTGVaNnZ6Yi9CZXo4dz09IiwibWFjIjoiNDFjMDdmZmI0OTI1YzcxMTgwNjY3NGNjOTRhMDU1YzIyM2Y4NjBmMGNjZTc1ZjI1NGRhZTJjOWY5NmViYmVjYyIsInRhZyI6IiJ9','any','30 MB 141ranvid',NULL,NULL,0,'Police',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:54'),
(26,1,106,'*22','police_asp_crime','eyJpdiI6IjNyVjBKcEhWbldOc2VlT2xwYlZCR0E9PSIsInZhbHVlIjoidy84QUYzVXFYanNtdXBCY1o2R3pGdz09IiwibWFjIjoiMjExYzU0MTkyMjIxYTc5MjU4NzM5NzYwMzg5MGYwNDk1MGRiNWNkZjFmNjEwYmE3YTkyMjk1NWRjMjg4ZTQ1MyIsInRhZyI6IiJ9','any','30 MB 141ranvid',NULL,NULL,0,'Police',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:54'),
(27,1,107,'*23','police_asp_dsb','eyJpdiI6ImhMQXBheEhWRTFPN2tOSjVnUmJneUE9PSIsInZhbHVlIjoiNnd1eTRtaW10NzlaNkdQM1c3bFI1UT09IiwibWFjIjoiMzhlZGNkNzg3NzVmOTMzNzkzNjM0NjE2MjcyNmFlMjcxZDRiNTFiYTllYzZmZDY4ZDI4ZWM3ZDgzMDQ1YmJmMyIsInRhZyI6IiJ9','any','30 MB 141ranvid',NULL,NULL,0,'Police',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:54'),
(28,1,208,'*24','police_dsb_office','eyJpdiI6IjhJYU5UUXl1bnF1aC80aVpPTzI3OUE9PSIsInZhbHVlIjoiekJqZjJWU2YzbXhiUDl6QTZ4czkxQT09IiwibWFjIjoiNjk5MmU2NjNkZmY2YTRjZTM0MDU1ODk5MjhiZGNlMGM4Y2Y5ODdlNGM0YjU2ZTAxYTY2OWE3YjlmYzNiYjRiYiIsInRhZyI6IiJ9','any','30 MB 141ranvid',NULL,NULL,0,'Police',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:27'),
(29,1,209,'*25','police_dio','eyJpdiI6Im5yUGNaRkNzcFFnUGZ5cGZBZ24wbmc9PSIsInZhbHVlIjoiSFNjenp3WHpMVE9NUmpycEY2ZFpDQT09IiwibWFjIjoiYTFiNTZhMDI5ZGZhNGY2NTMwZWMyYTI0MjE0NjllOGZiMGU5YjlkMDdkYTBjODZlMWM1N2RkYzRiNjZhZWNhMSIsInRhZyI6IiJ9','any','30 MB 141ranvid',NULL,NULL,0,'Police',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:27'),
(30,1,108,'*26','police_ciber_crime','eyJpdiI6IktpMkpUMVBucDJwYTJPSzcyVU9od1E9PSIsInZhbHVlIjoiRlhsamRRSjlTQkJ1RWppWWQzSW1Vdz09IiwibWFjIjoiNGI0YzU3Yzg3YzYyNzJkY2FhZWM4YjU5MTMyMmE0NzhhNWFmYTA4ZTk1ZTNmZjRkZDRmZjgzMTM1OTc0NWQyOCIsInRhZyI6IiJ9','any','40 MB 180 IP',NULL,NULL,1,NULL,NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:54'),
(31,1,109,'*27','police_bit','eyJpdiI6InRRUGNYZXFQbitvUnJNWW9LOU8zRnc9PSIsInZhbHVlIjoiZ0prbFBDMXA5MWV6amM4Smkxc3Yvdz09IiwibWFjIjoiOGY5MDRlOWJjZDZmYTEzOTBiY2Q5OGFlZTRkNzhkMGI4MjE5MDJmZmM4YzYwYjhmMzU5Yjc0NWQ0NmQ0N2M3YiIsInRhZyI6IiJ9','any','30 MB 141ranvid',NULL,NULL,0,'police',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:54'),
(32,1,210,'*28','police_ict','eyJpdiI6IjVIbWFyZzNLaEJhWHRHQzQ0MkloaWc9PSIsInZhbHVlIjoiRUJZVGc4cm9IcmFtTjROdzhBQzRSZz09IiwibWFjIjoiMzYzMWRiZjA2Zjg5YzU5YWM4ZjUwYTVmZDg4ZTQ2YmIwNzMxZDFjZDI4MTNlNzA1ZTA4YjJmNjc2MWJlMjlkNiIsInRhZyI6IiJ9','any','30 MB 141ranvid',NULL,NULL,0,'Police',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:27'),
(33,1,110,'*29','police_db','eyJpdiI6Im9Ld0IzaGUzN2huaURjRXpVbzFVM2c9PSIsInZhbHVlIjoieWMrQW05Y0YyYjVna1FJZ0xMeUxLdz09IiwibWFjIjoiNGJhMjZkM2FmZTgxMWE5Y2ZjZjFkMGU4MjI1YTQ3MzRiNzYzMDFmZjcwZDk1MjdmY2I0NGQ1MTdhZTBlMTA3NiIsInRhZyI6IiJ9','any','30 MB 141ranvid',NULL,NULL,0,'Police',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:54'),
(34,1,111,'*2A','police_acc','eyJpdiI6IjIxQnZ4bzY2aGQ1MzVUc0xXVDZhcnc9PSIsInZhbHVlIjoib2tFdlordDVLbnorQXN5b2NFM2NYUT09IiwibWFjIjoiMmNmY2U0NDM0OTVlOGM5OTA5NmY5ODE0ZjFlMjI3MDg4ZTNmN2QxYTZhYjY2MjUyN2M5ZGVkNDUyN2QyYjQ2OCIsInRhZyI6IiJ9','any','30 MB 180 IP',NULL,NULL,1,'Police',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:54'),
(35,1,211,'*2B','police_db_si','eyJpdiI6InVZWjZTZUpHdUF0S0lrZVU1OGt4QXc9PSIsInZhbHVlIjoiYzVxQnN6OG9MV0VtL2U2OThxMEt4UT09IiwibWFjIjoiMzNhYjc1MDk5NDhmZjlkMmY3NmExZDY0Y2NlYjEzYWY0NjI3NjcwYjA0ZTczMjVjZjQ4MTAxMzk0ZGMyNmMwZCIsInRhZyI6IiJ9','any','30 MB 141ranvid',NULL,NULL,0,'Police',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:27'),
(36,1,112,'*2C','police_armory','eyJpdiI6IkZDdGNRT1c5OGY5S2REMnVva3N4VkE9PSIsInZhbHVlIjoiR25PajZDQ2g0SkVhU2Y2Um9tVGcyQT09IiwibWFjIjoiZmJmMGIxMWZiNDRkYWI1NjM4NDMwNzgyMjFhMTY4NWQ1YmJmYjM0NmMyOGJlMThmZGE3Y2E3MjE4M2RkMTUxOCIsInRhZyI6IiJ9','any','30 MB 141ranvid',NULL,NULL,0,'Police',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:55'),
(37,1,212,'*2D','police_mt_office','eyJpdiI6InBpa0NUTjZpbmRyb1lDNTg0WGZxTUE9PSIsInZhbHVlIjoiYXZ2MWwxczRFRm9HNWpBb1pSTXNhZz09IiwibWFjIjoiODU1ZDRjZWRhZWQ0YjQzY2VjYzMxYmI5MmE1YzM1MmE3NGNiODI5MmI5YjUzZjBkMzNlYzg4YjRiY2NhZGNjMiIsInRhZyI6IiJ9','any','30 MB 141ranvid',NULL,NULL,0,'Police',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:27'),
(38,1,213,'*2E','police_ration','eyJpdiI6IktVZ2tXaFB2akRiMmlIc0xrY1NrcUE9PSIsInZhbHVlIjoid0tYSzM5YVhpdGV1RzAxSHVGMEdKdz09IiwibWFjIjoiM2MzM2JlNWNiYjk1ZDNlYjJlMjE1MjVkNzI2YzhmZjcxZGI3YTU4ZDU2NTkxNTgwOWU4N2Y5NWI1MDkyNmZmNyIsInRhZyI6IiJ9','any','30 MB 141ranvid',NULL,NULL,0,'Police',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:27'),
(39,1,214,'*2F','police_ri','eyJpdiI6InVncWFMRlNrdDVEY0VCUHdyMGMwOGc9PSIsInZhbHVlIjoiNG5RazYwZjF3UTk4Y05hVFFUQW1uUT09IiwibWFjIjoiNTBiZjZmNDlmM2UzNjhkY2U3YTg1OTU5ZDljZGU2NzZlNzlmMzM1ODJkMGNkMzY4NWJiNTE1ZTM0MGZkNzExYiIsInRhZyI6IiJ9','any','30 MB 141ranvid',NULL,NULL,0,'Police',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:27'),
(40,1,113,'*30','police_asp_sodor','eyJpdiI6Ii91bnNSRE5FM2U2U2w2Qk90YzBjRWc9PSIsInZhbHVlIjoiZFd4K1hjSDlxNHFsbXcxaEVZQW05QT09IiwibWFjIjoiMDYwZWJhMTQzNTFmNzY4MGM3ZjIwMmUzY2VlY2NhNGE4OGM4YmQzYTM3ZTQ3ZjZiMzRmYjI3NmU3MTcyMWQ4NyIsInRhZyI6IiJ9','any','40 MB 180 IP',NULL,NULL,1,'Police',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:55'),
(41,1,215,'*31','police_punac','eyJpdiI6Im5qRTF5MlIwUk81VjZHMGtqa0Z1cmc9PSIsInZhbHVlIjoiQktsZFFJUUthelh6M3JRT1N2aFVCQT09IiwibWFjIjoiOTY3ZmQzOTkwZWJkNTkwNTk2Y2I4ZDRlNjNiNGM3YWQ2YzZhMzBhYmE5NDI5ZTI4OWM2ZmFkMDU5MGM2OWEwYyIsInRhZyI6IiJ9','any','30 MB 141ranvid',NULL,NULL,0,'Police',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:27'),
(42,1,216,'*32','police_reserve','eyJpdiI6IlgrcURPL3pLcUxCTTFyZGliQ01FNVE9PSIsInZhbHVlIjoiQmJ1QnlGMFVPcHU3azNVZlhKOGNidz09IiwibWFjIjoiNzk3NmJhYTgyMzFmY2JlZmMzOTZiNTBkMzA3YjUxYWM1ODIxY2E0NWVmNWUzZWI2OGQ4ZGI1ZTIxNjYzOTg4YSIsInRhZyI6IiJ9','any','30 MB 141ranvid',NULL,NULL,0,'Police',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:27'),
(43,1,217,'*33','police_stano_1','eyJpdiI6ImJDNU5Gb1RHVGVsRkZ3aWFtUjhDN3c9PSIsInZhbHVlIjoic0R2alNJZXJCdFhsN2pRRkxMaC8yUT09IiwibWFjIjoiZDU5YjIzYTFiMGQwMmUzZTI0Y2JiMmQwOTMyZGYwZDE1NGNkOTAzNjlkMGE0NTIzNzJkOWE5NTFjMzNlZTljYiIsInRhZyI6IiJ9','any','30 MB 141ranvid',NULL,NULL,0,'Police',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:27'),
(44,1,114,'*34','police_crime','eyJpdiI6IjVhZnRnSmxNREE0QlloSU1vM1dEUEE9PSIsInZhbHVlIjoiWGlyeXg2TW5MUlBuaDNjajR3Z2pCUT09IiwibWFjIjoiZTJmOWUwNDRiYzg2NGNmMTYwYjc4YzYzM2NjNzgyNmQ5MDI4YjQ3NDllNmM2OTJlNzgwNDE4MDQxZTJlY2MwNyIsInRhZyI6IiJ9','any','30 MB 141ranvid',NULL,NULL,0,'police',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:55'),
(45,1,115,'*35','police_cyber_crime','eyJpdiI6Ikt2UXlsbWhPK0c4MitLS0JuQStYQ3c9PSIsInZhbHVlIjoiQWRWUmE0c3RzUEI0TWJQWCtEcjMrdz09IiwibWFjIjoiYjkzMTBhYWJmMzUzMjJmZGIwMGVkOTQyNjg5ZmI3ZDc5YjhlOWE5OTA3OWI3YjM2OWU5Y2ZkNGFlNjU1MDQ1NyIsInRhZyI6IiJ9','any','30 MB 141ranvid',NULL,NULL,0,'Police',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:55'),
(46,1,116,'*36','police_asp_crime_1','eyJpdiI6InZ2ZU94R3dpbVFuUUdRa0JZRWV1WUE9PSIsInZhbHVlIjoiMDIvMERtUlBwVnVtTklEaWE3SDZFUT09IiwibWFjIjoiOTYwNzQ0OTc1NWMwZDQwM2U2NmE5YTU5MGE2YmJiZGE2NGNhM2E4N2E3MTQxMGZkNDMwMzU4M2QwZDIyNDBlOSIsInRhZyI6IiJ9','any','30 MB 141ranvid',NULL,NULL,0,'Police',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:55'),
(47,1,218,'*37','police_reserve_ro1','eyJpdiI6Ilp0Y0ppZXRvWWhvZC9ZRUtzL2QvL1E9PSIsInZhbHVlIjoiQzFIQ01aVjJiRlkwY1Y0SFQ5Y3B4dz09IiwibWFjIjoiODA4N2U1MTNmM2FiMjBlM2I3YzE0ZDYxZjQwMTFjMGJjNDczOGY5YWRkN2UyNjk3MGU0NGI2YjJhNDViOWFhZiIsInRhZyI6IiJ9','any','30 MB 141ranvid',NULL,NULL,0,'Police',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:27'),
(48,1,219,'*38','police_head_ass','eyJpdiI6InMxNGdSWHRqRDg4ejJLVjk2NXh4blE9PSIsInZhbHVlIjoiNnNMSWVId0VFaUhPTEVKNC81K0xMZz09IiwibWFjIjoiNWVmZDEzNmRhYzAxODA1MGYyYWQzMjI0MTgyZTdiY2I1OGNiNDBjOWU2NjI3NmQ1OWUxMDliMmRmYTUyYmNiNiIsInRhZyI6IiJ9','any','30 MB 141ranvid',NULL,NULL,0,'Police',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:27'),
(49,1,117,'*39','police_d_store','eyJpdiI6IkxlWnFpZ0NVMFZEOEN4c3lHUWxHWEE9PSIsInZhbHVlIjoiRnZRZ2JHazRGUWtTampHTnJqeUFHQT09IiwibWFjIjoiZGViMzI3N2IwZmYzNjU1ZWVhMTIyM2E2OGVjZjllMmY2NjdmNTc1NGYzYzI0OWEyMDQyNzA0Y2Q4NjI5YWE1MyIsInRhZyI6IiJ9','any','30 MB 141ranvid',NULL,NULL,0,'Police',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:55'),
(50,1,220,'*3A','police_hospital_doctor','eyJpdiI6IlpRS1huaHA3amVGaS9ETUI1MWxFb1E9PSIsInZhbHVlIjoiS3JwOHlybERpVTc2TFB2V05Sd3Fndz09IiwibWFjIjoiZWNlMjdhOGJhYWJhOTA4ZDAzYzAyOGJlZDE5M2YwNGZlYmIwM2M5MmMyNGZjYjhlYmU2MjJmYzAwODFmYWViOCIsInRhZyI6IiJ9','any','30 MB 141ranvid',NULL,NULL,0,'Police',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:27'),
(51,1,118,'*3B','police_asp_home','eyJpdiI6ImxKZXZMWWN5NXpIWEZJK3RLTXpXYlE9PSIsInZhbHVlIjoiNVZKb3VJalNib29iREUveVRQNXFHQT09IiwibWFjIjoiYjQxNTlhOWZjZjc2NWJjMDg2MDRhM2Q5ZmIyOTQ4ODY5MmE0NTU2ZWI1ZGJkZjEzYjUxMGRmMDMzOTQ3ZTY1MiIsInRhZyI6IiJ9','any','40 MB 180 IP',NULL,NULL,1,'Police',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:55'),
(52,1,119,'*3C','police_cloth','eyJpdiI6IjVqV0E2c1Y1RVNhQmk2L0k1TGFqM3c9PSIsInZhbHVlIjoiOU9ZTnZlT2N4U0ptSDBEeTdranlBdz09IiwibWFjIjoiZmMzNWFiOGQzOTYxN2NmYzNiNDljZGEzMTY3YmFmYmEyYzI2ZDIxNWJmN2JjYjdmODc2ZjZmMDQwNGNkZTgxZiIsInRhZyI6IiJ9','any','30 MB 141ranvid',NULL,NULL,0,'Police',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:55'),
(53,1,221,'*3F','shohel_add','eyJpdiI6InRsdmlHdGNwcnBDaGlOeU9XQ09LYkE9PSIsInZhbHVlIjoiTUE2WDV4K0ZvVzhyK3RBbnJMb2grUT09IiwibWFjIjoiNDI2NGU1MzM5MDU3ZTQ1MmMwYmViYTZjYzlmOWRhZTI4NTAyNGJlNTE2YTUxOGYxNDRkOWY5NzM4NjQxZTMzZiIsInRhZyI6IiJ9','any','30 MB Saifulkst',NULL,NULL,0,'Anike Jony vi Thanar mor',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:27'),
(54,1,11,'*40','jamai_star','eyJpdiI6IjU1bzZCdGY4Rld1cVVJNFdQWkErdXc9PSIsInZhbHVlIjoiZUtqQlNsczRBYjFuZVE2ODFpUGszQT09IiwibWFjIjoiMGE2ZDUwMGVmY2Y0YjU5ODEwYjVmMTZiZjRjNzNhMWNiZWFhZGI5OGEwMjFkN2FkNzg0ZDkxZjYzOWQyZDdlNCIsInRhZyI6IiJ9','any','200 Mb Star',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:17'),
(55,1,301,'*41','star_test','eyJpdiI6IkRPQk1HK1k3WTRzK2RXTDZhMGpMb0E9PSIsInZhbHVlIjoiN0NWdE5qNlhIM3M1MzhaeklyTWJCUT09IiwibWFjIjoiYmFjNzE4ZjY3MDIyMjhmY2E5Nzc0NzVkM2M1ZjNkZTY1NjhjMGQ5YjZiOWQxZWMwYTgzODdlN2NjZjQ4NjUxMSIsInRhZyI6IiJ9','any','30 Mb Star',NULL,NULL,1,NULL,NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:44'),
(56,1,222,'*42','shofiqstar','eyJpdiI6ImZlSzMrMGl1OE9jVmRyNmlzL01ya3c9PSIsInZhbHVlIjoiVmVZUXQvV0w3RzRGR2NOVDRwdE11Zz09IiwibWFjIjoiMzI1ODZlZTRmMDZjNmFmODQ1MjI3YWQwYjk5ZDdmYjhiNGUwOTlkNmI5NDlkYWNiZWJlNjNjMWJmOTJmMDdkNiIsInRhZyI6IiJ9','any','200 Mb Star',NULL,NULL,1,NULL,NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:27'),
(57,1,302,'*43','star_hap_3','eyJpdiI6Im5pSFh0S0NxaHJST2k4NFVwOXFEK0E9PSIsInZhbHVlIjoibnF3NmtlMlFEdEhCSUdMZmd1eWNodz09IiwibWFjIjoiM2RkMmUzY2NjMjllNmU1ODYzMTRmNTMzZmRmY2U3MjExYWZiMjk1OTRjYzk4MDJjZDgyYTRjNWNhMTNjZDAyNyIsInRhZyI6IiJ9','any','200 Mb Star',NULL,NULL,1,NULL,NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:44'),
(58,1,303,'*44','usstar','eyJpdiI6IlVnMWM0bmJDcTF2ZFVIVk9jSGEwMHc9PSIsInZhbHVlIjoiUW1TWkEwRkhTK1FhbGdMWElxNlNrUT09IiwibWFjIjoiYTQzNmM1ZmFlNWQyMDkxZWQzOTYwNGY5NWIxN2MxYzA3ZGI0NzRhNDMxMWVkZmU3Mjc1OGIwOWY0YWQ3NzMwNCIsInRhZyI6IiJ9','any','200 Mb Star',NULL,NULL,1,NULL,NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:44'),
(59,1,12,'*45','farukvai','eyJpdiI6ImRPcGQ5K3VKWUFrWll5bEJ2bzkrdWc9PSIsInZhbHVlIjoiakxiS2pLVVZwR0JDcGhzQzhMVGZkQT09IiwibWFjIjoiMWVjMzMxOTRmZTdhZTdlMjViZjEwN2I5OGRlNjVhODlhOTY3OTNiMDM0YjQ3OTIyNjM5NjcxNGZhMGYyN2QxMyIsInRhZyI6IiJ9','any','30 Mb Star',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:17'),
(60,1,304,'*46','upzsir','eyJpdiI6IjBuaVJUQURwWEVEY1Z0TGEvUC9iYlE9PSIsInZhbHVlIjoid3VHYmNqN1lyZk9SRS82UWUwQk5iUT09IiwibWFjIjoiMWNlMzkzYWIyMmEyNTliN2Q5NWU3NjRlNDgyZTIxNWU3OGEzZDgxZmI1MTRlODIxZDFjZTg2ODg3YzQzN2E5MiIsInRhZyI6IiJ9','any','50 MB shena_nir',NULL,NULL,0,'shofiq',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:44'),
(61,1,223,'*48','rabby','eyJpdiI6IkJyM2hXb0dNV1hkcWpONUViT3krNWc9PSIsInZhbHVlIjoiT1JRRTZNNXdna1NhQlJUeDNPbEt4Zz09IiwibWFjIjoiOTdjZTg5ZTNlNDcyYmI0NjJmYjgyYjI0N2Q4YjE3ZjYxY2UyOWI5YTRhNWZlYThmZjI3YTVmMDY0Y2NmYmNhNyIsInRhZyI6IiJ9','any','200 Mb Star',NULL,NULL,1,'Mosan',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:27'),
(62,1,120,'*49','kpiprincipal','eyJpdiI6ImVIMndveG1UcGhUZmZOSzVIWjBacmc9PSIsInZhbHVlIjoiTFdEdEUzcCtHemVHQjJDbFQ1akhvUT09IiwibWFjIjoiMmZkNjFkOTgwODYxZjBjMjMwYzQ1MDk0MzBlYWU2NTE4YzE1ZTdjNjdjYTE0MDI5OTBkNzQ0ZWY1NGE3Njc1NiIsInRhZyI6IiJ9','any','200 Mb Star',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:55'),
(63,1,305,'*4A','tafsircom','eyJpdiI6ImNZcjNMQlNQQzdnc0Nhb0hvdjhUTUE9PSIsInZhbHVlIjoiTXZlN0JBWUR0MkptWmRWb0NsYklmdz09IiwibWFjIjoiZjA4MDAxNjY2NjBiZTU3YWU1ODVjZjBiNjRmYjFhNDQ4NjY3OTY5YjU3NmY2YmUwOGQ2ZjUxOGU2N2YxYjlkMyIsInRhZyI6IiJ9','any','200 Mb Star',NULL,NULL,1,'Anike',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:44'),
(64,1,13,'*66','abonicom','eyJpdiI6ImppUVdYMTZwelRnOXA0WktKQWgySEE9PSIsInZhbHVlIjoiZnA5RzdCUlBsWlZJTFcwU1crRVlmUT09IiwibWFjIjoiNGM1ZjM3NGE3MDZkYzAzMmYxZDIzMjZlNTkwYzc3OGQwNzRjNTVlMGNlYzllNWFmOGNjMDVhMDRiMzA2MjdkZiIsInRhZyI6IiJ9','any','30 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:17'),
(65,1,14,'*67','babuvai6','eyJpdiI6InIwRTN2aXZHNXIxakcyVENoODhMTEE9PSIsInZhbHVlIjoiVFRXSnBnSTAxbzR3akdsQXZoUC9ndz09IiwibWFjIjoiYmU2MjJiZjZkM2NjZDkyOTdlNWNmNDgzZWI4NGZjODUwYjEyZDUzMmQ1YmMwOGQwM2I1MDc3MjAxOWYwMTBhNSIsInRhZyI6IiJ9','any','30 MB Lgedks',NULL,NULL,0,'shofiq',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:17'),
(66,1,15,'*68','disha1','eyJpdiI6IjlVTjd1SDhZeDltaGtDOHFhK3gyUHc9PSIsInZhbHVlIjoibVR0aFhiY2Z3MGZ3VFBTSXNyc1M3QT09IiwibWFjIjoiN2ZlOTExZmQ4M2RhMGI1Nzk5MDY5MDliNGY1MDE1MTBlNTNkMzk1NTEwY2E3ZTQ2Nzg1YWMwMzkwNmFmMzZkYiIsInRhZyI6IiJ9','any','30 MB shena_nir',NULL,NULL,0,'shofiq',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:17'),
(67,1,121,'*69','maju','eyJpdiI6Ik5SWWRuUFFpdWVraTduckVPTFFQL3c9PSIsInZhbHVlIjoiU1VTWTJuUXNMNjRVNituL0tidFJCUT09IiwibWFjIjoiNmViNDVkZTNhM2VmYTYwNGMyZWNiMzhjOTE4MmZiMDc4ZmI1NDMzMmNmZmE3NjdjMmIyODY2NjU1ZDUwMGZkZiIsInRhZyI6IiJ9','any','30 Mb Star',NULL,NULL,0,'shofiq',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:55'),
(68,1,122,'*6A','kpihelel','eyJpdiI6IjhLbjFZVFlXU3ppUnRSekU5b0ZQVVE9PSIsInZhbHVlIjoidkhzdERtMmg4UjFiR0Z5ZDNVNndkUT09IiwibWFjIjoiOGY3NTQzZDgxZWVhNjE2MDBiZmViMjgzODVjZjVmYjMxZmYxNWI3YzAxYTczNTY1YTdkODA2NzNhZWQ2NGRlMiIsInRhZyI6IiJ9','any','30 Mb Star',NULL,NULL,0,'shofiq',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:55'),
(69,1,16,'*6B','icollegebm','eyJpdiI6InUzUm9wQi9sV09aQUlTYnBESkxvT0E9PSIsInZhbHVlIjoiY3lXb1ZDZDl4RWlxTVk5YlVYNU5hUT09IiwibWFjIjoiY2FmYTNjODNmNDQ5ZmRjNDkxZDE0YWU1NmI3YmYyODQ2MTk1NGQyNmZkNDRhMTc1MjE3YzhiZWE2OTFiNTBkMyIsInRhZyI6IiJ9','any','30 Mb Star',NULL,NULL,0,'shofiq',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:17'),
(70,1,17,'*6C','alomkhan','eyJpdiI6IkhrOXVjVHJFRDVhaWJRMHlGQWNaalE9PSIsInZhbHVlIjoiQ3RRNStKR1ZlWUErS0VJMk1oQXdrdz09IiwibWFjIjoiN2UxN2FmYWRmYzc2OGMzMmI5MjQyNzZlYzcyNmI1MjM1MzUyMTA4NzlkOTY5YzFhZDcxZTk2MzA1NGEyMzEzNiIsInRhZyI6IiJ9','any','40 Mb Star',NULL,NULL,0,'shofiq',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:17'),
(71,1,224,'*6D','rashidago','eyJpdiI6IlJLSDFvKzdIWVd5MHJTWS9MSGM4SkE9PSIsInZhbHVlIjoiOUNEamxIaE1WV3pzWkNTWHNQY1Y2Zz09IiwibWFjIjoiN2Y3ZWIxYmY4YTIzMzg5YzFjNjkzZjJhMDljY2RmOTM3NDAxMWY2MTlmZGYzOTdlMmY4OGVmNjIzNjVmMzkxZSIsInRhZyI6IiJ9','any','30 Mb Star',NULL,NULL,0,'shofiq',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:27'),
(72,1,123,'*6E','mirschool','eyJpdiI6ImE5dVZYZ3ByQ3huY2RERnNtQzlkeHc9PSIsInZhbHVlIjoiQ1p5ME9KRFIyVVo4NlhWdTNmamRkZz09IiwibWFjIjoiMmQ1MmU2ZDA5ZmEzN2JhN2ZiZDAzMzg0OTM2NGJkMzc0ZDE0ODMzN2Q0MDU0ZWJhNGVjMDFhMjk1YmNlZDljZCIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:55'),
(73,1,306,'*6F','tuku_vi','eyJpdiI6IlQ2R2d3SFFlTk14WGVzNEpYaUxFdlE9PSIsInZhbHVlIjoieU0xZGJJQktDK2k4Wlc1WEtaMTR1QT09IiwibWFjIjoiYWU5NWRiOTFlODNmODE1MDVjMjc2ODUxMGZlNmIzMTA3NWEwMDAzMmZiZWU5N2EzNmM5M2M5MTMxYWE5Zjg0YSIsInRhZyI6IiJ9','pppoe','30 Mb_Travelshouse',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:44'),
(74,1,18,'*77','itech','eyJpdiI6ImVIaDFiS0ZCdGpCNmlKblhqaWYvMlE9PSIsInZhbHVlIjoiYTJkT3IvajEvb2tYMHltREl1T1pWZz09IiwibWFjIjoiZjNiNGY2NDJjNGQyZWUyMDgxNTg0YWZlZWY0YTdmZDU1OTIxYzliMzhmZjBkNTUzYWE4OTNhMTMxYjA1NzQxOSIsInRhZyI6IiJ9','pppoe','30 MB shena_nir',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:17'),
(75,1,307,'*7B','wadud_driver','eyJpdiI6ImszRjVuVFovMm9iYVhMVm16cnNFcHc9PSIsInZhbHVlIjoiS2diY293KzFzeGoxckZjVTJGUnRJZz09IiwibWFjIjoiYTViZjVkMjg3NGRiNTNjMGI0YWZkOWQ3NjViOTYwMjNjZWRhODhkMzE1MTg1YTZhNjFjYjFiZGEyOWJlNjA1ZCIsInRhZyI6IiJ9','pppoe','30 MB Saifulkst',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:44'),
(76,1,124,'*7C','murad_vi','eyJpdiI6IjQ3NlFGdGNpWFlzZmRYMHhBUVA4aVE9PSIsInZhbHVlIjoib0FlOTA2eGJaeU5wVUtWdktOeDBiUT09IiwibWFjIjoiOGI3NGI5Njk3NTBkMDY0NzAzNWY2MDc4NzhmNGJlYzUxNmY0YmIxNTk5MDQ5MmI2NGNmMTJhYjNlMDY3OGYxOSIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:55'),
(77,1,225,'*7D','shahinmp','eyJpdiI6IjRuK2pwUFhwTlRBdWRDMTUzMjhkL0E9PSIsInZhbHVlIjoicUV6THZzZ1JWOUIydDEwbDZrSHJsZz09IiwibWFjIjoiOTU0NTZkNzY1YmQ4OTc4Y2QyYmM1N2I5MDc5MzgxMjM4MDYzMDhiNTFkMjFkNmVlYTMyOWM0YzdjZWYxNzQwOCIsInRhZyI6IiJ9','pppoe','30 MB Saifulkst',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:27'),
(78,1,308,'*7E','wdbmamun','eyJpdiI6Imk5RHpMZHQxK1NjUmxKTTN4cUlld1E9PSIsInZhbHVlIjoiMUtXNUhMQ1lPVmNkVWJ1ZFA2ODZTZz09IiwibWFjIjoiMGUxZjVkNDQ5MDdiMmY3Njg1Y2YzNzlhMTUxYzEwYTgxNDdiN2Y1NjAxYTY2ZjQ5MzgwOTQwMDM0NmU5MThhYyIsInRhZyI6IiJ9','pppoe','30 MB Saifulkst',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:44'),
(79,1,19,'*7F','btclhome','eyJpdiI6IllQZm9XSjZXWWpVWlkrM2Q1S3N5RkE9PSIsInZhbHVlIjoiRzBrY3N2TC9ackkzS3lQY0ZYbUJUQT09IiwibWFjIjoiN2Y5YWE2Y2M3NmMzYTUzNWMxMzU3YTFmZjAzOGI3ZDBiYTE2ZmEwNWZmOTViNWFiNGY0MDk2MzA1YjNmOGJiNiIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:17'),
(80,1,20,'*80','070dailykus','eyJpdiI6Inp2OHZtZFI3c01oS1loYjQ5bkhuWUE9PSIsInZhbHVlIjoidHNoOXArWUtYRkN3cjdreVVPTWt5dz09IiwibWFjIjoiNTlmZmVlMDM0NWZkNjM2YWQ3YmRlM2E3MDI2MGI5OGQwNmU4NTA2MGI4YzZjMjI3YTViMDhmMjI3ZjdlYzdhZSIsInRhZyI6IiJ9','pppoe','30 MB shena_nir',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:17'),
(81,1,309,'*81','sumonkst','eyJpdiI6Im1lVjM4Z1lNczVUdzJIdU45MERFR2c9PSIsInZhbHVlIjoiTCtNdTJEUW1KUHR6Zk9RRUZ1SVdtQT09IiwibWFjIjoiNGU2Y2MwNWE4NTY4MGQ2NTA1Mzk2YTQxYzcyYmJiNGM2MjIyODY4YTBiMTBlN2RkZTU5NzQ1MWVjNDdkMGQ4ZSIsInRhZyI6IiJ9','pppoe','30 MB Saifulkst',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:44'),
(82,1,21,'*83','familyware','eyJpdiI6InR3TnE0VE1kckNPcW5sSWllZ0NQN0E9PSIsInZhbHVlIjoiVktac0xjNXh3ZkI0dkdRbkphc1ZnZz09IiwibWFjIjoiN2ViODU2YjRmZGI3ZjMxYzMyN2IwYzA4YTBkYTAyZDNlZjlkZjg4NmNiM2E2ZTFjYTZmMGFiMWZhM2U5MzZlOSIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:17'),
(83,1,125,'*84','nafizkst','eyJpdiI6IlBFb1Zzd3NjL004ZDlsSnRPdTBmbUE9PSIsInZhbHVlIjoiejVNVFlyTmxRd00rTlBibGEvWVBjQT09IiwibWFjIjoiY2M3Mjc2YzQwYjM3NjhiNDRmYmJmOGM1MDcxZTI4MzM5YmM3ZDc5YzE3ZjJhYjA0NmVkOWMzM2ExZjM0OTM3OCIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:55'),
(84,1,22,'*85','azizulvai','eyJpdiI6IkxsWXpGV3F5RlZpankvV3VjZHh4SWc9PSIsInZhbHVlIjoiZ2JyWXJSb01HdXJQKzkzZmtmK3hxZz09IiwibWFjIjoiNDliNGFhODA4YWFmM2Y1NTQ3YmVmNmViNWEyMGU5MWQ5NGVlZjlmNDhiMzBiZmM0YjE2MzRmNjVjYTUxYTc1OCIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:17'),
(85,1,126,'*86','munnabasha','eyJpdiI6IkpWa0dnK1RBVzB1Kzg0MGdGR1ZQMFE9PSIsInZhbHVlIjoiQlE2QUF6ZFpGV0ptL1lHMFBHNENHdz09IiwibWFjIjoiODIwNTcwMjhiYzc5NGQ4NjdmNjIxZmU2NjUxYzcyY2ZkYzFiZTBkZTRjMzU4NmE4YThjNmJmNDEwOThkYWExOSIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:08:55'),
(86,1,310,'*87','sujonpol','eyJpdiI6IkR0Wm1tV2wxRWpmSTBBNlZEbjgxM0E9PSIsInZhbHVlIjoiZStXelZEMFNlbUJ3ZHVtaU9PT1RXZz09IiwibWFjIjoiNzVkMDE1YjdmNWIxMzJkZWU5MmVhZGI1OWE2NDIxODc3ZWNhYWY2NWIwYWNiODA3ZDNjZTM4ZGQ4MDI1MjVhNyIsInRhZyI6IiJ9','pppoe','30 MB Saifulkst',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:14','2026-08-10 23:07:14','2026-08-10 23:09:44'),
(87,1,127,'*88','onkurdec','eyJpdiI6InE3cWJHSERRaS9pOHZZVHV5WWMrdUE9PSIsInZhbHVlIjoiMHMvNm1CUUh4d2svdWNQSGdqT0g2QT09IiwibWFjIjoiYmU0ODg0MThiMDIxZmFlZTk0ZGQwMmYxMzU1NzEyY2VkNDFiYTQxMjZjMTRlMjU2ZmIyZTRiNWJkNmE5YzYxZCIsInRhZyI6IiJ9','pppoe','30 MB 141ranvid',NULL,NULL,1,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(88,1,311,'*89','wdbalamin','eyJpdiI6IlIvQldnYm1GY3lKSTgvWEZuaVZtVEE9PSIsInZhbHVlIjoiVUVOeG1GektCViswYW9mTmFtc0J6Zz09IiwibWFjIjoiZmRiY2Q4ZjczM2YyYjYwZmNkNzgyYzcyYmZjOWZiMjBhOGYwZTE2MGQ4YTM3MDIzMWM1Nzg1NGE3ODUxZGM0YyIsInRhZyI6IiJ9','pppoe','30 MB Saifulkst',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:44'),
(89,1,226,'*8A','shamim6r','eyJpdiI6IjVjNGdJdFhoQVRRSVRxbDB4bE9SUUE9PSIsInZhbHVlIjoiSWtiZE1CdEVuQ1I1TUFueGJmL2x1dz09IiwibWFjIjoiYzVjNDI3ODdmZmMzYjQzYjk4MTMzYTY0NjY4ODM5NmE0MzU1MWRiNTM0YmI0MzQ4OTFkNjdlODg5MDY0ZTFlNSIsInRhZyI6IiJ9','pppoe','30 Mb_Travelshouse',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:27'),
(90,1,23,'*8B','azomuncle','eyJpdiI6IkJIRTBTRnVsYW5EemkwOENoTDlJQ3c9PSIsInZhbHVlIjoiUG82S3NtNzBKMWlzRVVJTE9wTmxkZz09IiwibWFjIjoiMWI2MTQ0Njc5NzczMWJmYWZjMmNmZjM2YzkyNDhmN2ZjN2U3MWMxMDk5ZmYzYjg5NjY0ZTI3MjMwMWYwODgxMCIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:17'),
(91,1,227,'*8C','ptijui','eyJpdiI6IkNuSGhuSlJXTWtRRUo4R2lqbjNKK0E9PSIsInZhbHVlIjoicTZ3eFRaeGIzNXJ4K2FRU29SdERHZz09IiwibWFjIjoiZDlhZWY5NzI4YzMwMjUxYTliYTJiODRiYzdkZDk5ZjI0ZTVjZWYwMzhjODhkM2NkNDE2NTFkY2M2NDQxZGY1YSIsInRhZyI6IiJ9','pppoe','30 MB Saifulkst',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:27'),
(92,1,24,'*8D','anupomkst','eyJpdiI6IlA2aFB6V3ZxQ09oWTdiV3d2NStNbmc9PSIsInZhbHVlIjoiaU5nQksybXc0L1ZZZWRWUGpRMkFDQT09IiwibWFjIjoiMjI2YmE3ZTNkNjI3M2IwYjZhODExNTc1ZTJjYzQxYzYwY2U0ZWM2ZGQ0OTE1ZmExMmI5NTY5ZTAyMWIyNWZkOCIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:17'),
(93,1,228,'*8E','premkst','eyJpdiI6ImVrVk90ajM1em9KTWNQZlAzV3RuV2c9PSIsInZhbHVlIjoiYXBjT05VNk1sMFBsOEpqQ1hIeUk2UT09IiwibWFjIjoiYjcxYzBiNWQxNDVkOTk2ZWE0NGNiZmEwZTg3Yzg5N2E5YzY1OTMzOTUxOWZmMWJlMjJjN2JhZjEzOTEyMGY0NyIsInRhZyI6IiJ9','pppoe','50 Mb_Travelshouse',NULL,NULL,0,'Anike',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:27'),
(94,1,25,'*8F','driqbalsir','eyJpdiI6IjdrWmJGN3JoZUNVczQ1Z0VUekhQUnc9PSIsInZhbHVlIjoiYnczQ0tpYzM5cjNESmtlaDh1cE1QZz09IiwibWFjIjoiYmZkOTlkNzY3YzZkYWM1NDcxYTJhYzExN2I3ZDNlZWEyZGQyMjJhODg2YmYyOGM3NGExOTI5Njg1MmU5OTAxMCIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:17'),
(95,1,26,'*90','dramansir','eyJpdiI6IklyQndHQ1BFUHhjL3JRNU15bUhEYmc9PSIsInZhbHVlIjoiQzFHZUt3UUxCU2kzWUxGbXlER1Vqdz09IiwibWFjIjoiMTIyMzE3ZjA4MmJiOTRlNzU2OTQ0NzAzZjNiYmJkYTU3ZDA5ODAzMDYwOWI5NDE5ZjIzNGYzMDVkMzc5MGFiMCIsInRhZyI6IiJ9','pppoe','30 Mb_Travelshouse',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:17'),
(96,1,27,'*91','101wdbrokonsir','eyJpdiI6Im9tR3RXT3haMitzc1ZwZWFjdGxVQVE9PSIsInZhbHVlIjoiUjIyaUZadER1ZXBZblVzV0N4cU9iQT09IiwibWFjIjoiMjg3YTQyMzA2NGIyNDg1ZTQxY2E2ZDQxZDhkYmVlNzk1NzJmMzA2MGMyMDcyNWIxYWZkYWJkNjMwODU1MmM4NiIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:17'),
(97,1,312,'*92','somendada','eyJpdiI6IlU0RFpRWWlNYS9kSzA5Q09kQzJuWHc9PSIsInZhbHVlIjoiblRCa3QxSUxIcGZRTWdRR3lJUVI2QT09IiwibWFjIjoiN2VmNTI4YzE5YTY4ZWY5NTg5NWNlYWE3NzI5Y2Q2MDUwMDkzMDllOWRmOWY3MjAyZGI0ZGVkMGRhN2E3NDcyZSIsInRhZyI6IiJ9','pppoe','30 MB Saifulkst',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:44'),
(98,1,229,'*93','samiulkst','eyJpdiI6InFJb0tCcGRjam96ZHRMNnB5NktUU3c9PSIsInZhbHVlIjoiZ2Z3ZGwzQVR6emNuT2tPbEs5dkFqQT09IiwibWFjIjoiYjgxNjI2MGY2NzhjYTNlMjVjYzFjNjQ1NWU3ZDM2MzVmOGQzZDhhN2Q4NzhkNTgzNjU3OWNmZjczZjE4ZDRkNCIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:27'),
(99,1,128,'*94','kpishamim','eyJpdiI6ImRGTjRFR2tDQndLV3dtc0luV1UyQVE9PSIsInZhbHVlIjoiNXRCQkZ1NHM4SjdOMkcwOUlIZnVsQT09IiwibWFjIjoiYzAzY2MxOTgzZjJjMWE2OTI5MDdmYzJmMzEzYjU0MDA1YzE5NDFjODlmNTJkMTdmNGQ3ODU4YjUzZmYzYmM0OSIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(100,1,313,'*95','suzonkst','eyJpdiI6InJHenhITFlzbWQ5SUpOblY1UmptU3c9PSIsInZhbHVlIjoiQkR5N2JqUVA1RThBTnF4VUFZWnlWUT09IiwibWFjIjoiODdlZjMxYjE2NjlmYjVlYzQ5ZmQzNThiNjdkMzM2NjRiZDhjNDYzMTE4NjhmYmY4YmRiZmVkZTI1Y2VjYWRmNSIsInRhZyI6IiJ9','pppoe','30 MB Saifulkst',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:44'),
(101,1,314,'*96','untisixr','eyJpdiI6Ii85NWNjdzF4Sy8xN21PWTZqenphaWc9PSIsInZhbHVlIjoicWNqWlpCUE53QUJyUUhiWFNkQnZOQT09IiwibWFjIjoiZDY0OGU5ZDRlZWQxYjI5NDdhZmY3MDFmYWQxYWY2ZWIxYTliYmEwOGQ3ZjY1OGMyZWYzZjc2MTkwMjI0MGQzOSIsInRhZyI6IiJ9','pppoe','30 MB Saifulkst',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:44'),
(102,1,230,'*97','shamimakst','eyJpdiI6Ind6MTFVUDRRTHM2TEhpd0J1RXJOU1E9PSIsInZhbHVlIjoiYTR5clZlRi9OSzJrczRKS3R3QnpHZz09IiwibWFjIjoiM2NhYmE1Yzk2Mzg5MzNhNDk1MjFlYzdmMzBjZDFiODhjNWNlYzE2MDBkMDhiY2VlZjRlMzYwZmE4MTJlN2RkNSIsInRhZyI6IiJ9','pppoe','30 MB Saifulkst',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:27'),
(103,1,231,'*98','rabtasir','eyJpdiI6Ikg2cFIxOUZ3WGE0VElvaUNDbFF0dUE9PSIsInZhbHVlIjoiMlZUNlQwMzR3U3k3UjEwTi81bzZOZz09IiwibWFjIjoiZWYwZGFhOWM5YjhjOTE5YjZjNjU1NWRkMzJlYmRkNjJjMzM0ZWQ3NjYzOWFmMGQxMDJiMGJiN2JlZWVhOTQ4NyIsInRhZyI6IiJ9','pppoe','30 MB Saifulkst',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:27'),
(104,1,129,'*99','kpiamol','eyJpdiI6IjlHVEozUzRjL1B4VlhPVDdNTTl6WkE9PSIsInZhbHVlIjoiRzhsMWVEZGQ0OWZhNE52QU5PT281dz09IiwibWFjIjoiYTA2ODZhNTUwMjg0Y2YxZjg3YzQ1N2I5N2E4MDkyMTI2NzNhYTUxZGY3MTJhZGQ4MWIxNWZkODYwNGJjMGQ4ZSIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(105,1,130,'*9A','kpimasud','eyJpdiI6Iko1MTlwWmdHTCtPV1ZlUzRVZ0sxMFE9PSIsInZhbHVlIjoiQWJDRHc0cndCejZnelpSL2VQejg2UT09IiwibWFjIjoiNjliODQ5ZmFjNDJkZjVjMTIyNjc0Nzc2NDIwMWFkN2VhMWQzOGZkMmEzMzVkOThiZjI3NDUyNTczNTg4ZmIzMyIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(106,1,315,'*9B','tamannakst','eyJpdiI6ImYwK3RHd0IyUHpEM3FVaHptWkVhblE9PSIsInZhbHVlIjoiMWd2Rmc4TmFwSnd5MDMwZjBGai9wUT09IiwibWFjIjoiZjEwNjdiYzM0NGJiZTcwM2ExNjZlMmJlOWUzYTYwMWZlZjAxOTNkZWU5MjY3Mzc1MzliZjFjZGMzZjc3ZDg1NSIsInRhZyI6IiJ9','pppoe','30 MB Saifulkst',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:44'),
(107,1,28,'*9C','julia','eyJpdiI6Ik5ZQlhHTjA5MXVpbS9FS0NxOFpRa2c9PSIsInZhbHVlIjoiMGJwVnJaWTNXaU1OY1FoQjhPeS96UT09IiwibWFjIjoiZThhZjRiNTI5MDcxNGJiZWI3MTBhZTBlODIyMjNkOGViMmZkNjg0NjA2NDkzNTM2MzZjYjViZTU3Y2IyNWY5YiIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:17'),
(108,1,29,'*9D','ikramvai','eyJpdiI6IkN0eHFHN1NnUlFYaDdoOXJsOVlyM0E9PSIsInZhbHVlIjoielU3bWxLdm16bWRFdkZrMXplY05YUT09IiwibWFjIjoiNGU2YTMwODkzZWE0MDgzNzU3MzU1Mzk2OGE2ZWQ5YWNlN2E0ZmRjODBjYWE5ZDQ5M2Q5NzJmMjU4ZmE1ZWIyNyIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:17'),
(109,1,232,'*9E','roshidago','eyJpdiI6Ink4bkpTOTB3UkZMdFN0dlhEMjdjSGc9PSIsInZhbHVlIjoieG5ISHdoWEhvcG11cDlwSDZieFlwZz09IiwibWFjIjoiMjJiZGQwOGU3ZTQwNWZiYzE3NDAxZjI3M2I2Nzc2Nzc1YzA5NTBlN2U0YmUyODlhOTYyMDc3ZmM0YmE0YjZmZiIsInRhZyI6IiJ9','pppoe','30 MB Saifulkst',NULL,NULL,1,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:27'),
(110,1,233,'*9F','probash','eyJpdiI6ImVwVkczTXh3cSsrb3IyWjlRWHp2ZHc9PSIsInZhbHVlIjoiZmFWcExicHVLUWc5UFkyZnRpRjZxQT09IiwibWFjIjoiYzJjMDc1ZGI2ODcxZGU2ZmUxYTFkYzNhMTA4NDZkYTEyYTljNTc0NGEzYWE1ZmFjYjU1ZjgzZDkzNDAyYTdlYiIsInRhZyI6IiJ9','pppoe','30 MB Saifulkst',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:27'),
(111,1,234,'*A0','ronjitvai','eyJpdiI6ImRlbXFMM0gveHJtVHN5L2xjTmd0bmc9PSIsInZhbHVlIjoiRFU5OUw3RWRGeWV1cGwzWnBrRm1jQT09IiwibWFjIjoiNjY5MDhiZTc4ZmRlNWM5MGZkMmM5OTExNTkzMTk2MzVkMzA5ZTFiZDk5MzdiMGI0Y2UyZjVjM2FkOGJlMTA2ZSIsInRhZyI6IiJ9','pppoe','30 MB Saifulkst',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:27'),
(112,1,316,'*A1','tasinkst','eyJpdiI6ImFjQ1B6QlArM2xFdjRKVE02M1NUeHc9PSIsInZhbHVlIjoiYnJwMU5lWjRhYVNQTC8reUJDM2tnUT09IiwibWFjIjoiMWU4MDI2OWU0NTk1ODk2ZGIwOGNlOTE5MWIyYTU2ZDE4MjQ5YzkzYzU3OGVjODBlNGYxNGRiNzExZjdmY2JmYyIsInRhZyI6IiJ9','pppoe','30 MB shena_nir',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:44'),
(113,1,30,'*A2','imrankst','eyJpdiI6IkFLbFNqTVhNTFIvZVpmYlJ3U0xDdHc9PSIsInZhbHVlIjoiR0Jhd0ZSZlN3VzkzNG1xNHZEcnZydz09IiwibWFjIjoiY2E4ZDE4N2JlODk2MTI4ZDcxMmJmM2U2MjBjMmQ0ZGIzODJlYWJjNWQ5M2FlYjBmMGZjODUwNWY2MjhmYTlmMSIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:17'),
(114,1,31,'*A4','babu_potua','eyJpdiI6ImN3MDlDVUdZNklPWjNCTGp5SktZU0E9PSIsInZhbHVlIjoiZElTYlhTb3BEdGtuYTB3YnhBSWQ5dz09IiwibWFjIjoiZDYzNDhiNDAxZmMyZGM2Mzc0NDk2MWE3ZjU5MmY2YmYwNTNhNzYzMTRkNGEwMjkyN2Q3ZjZkZjYxNTRjOGQ1OSIsInRhZyI6IiJ9','pppoe','30 Mb_Travelshouse',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:17'),
(115,1,131,'*A5','olongker','eyJpdiI6ImlLdVRzRjVKaG4zMXpVRFF5ZTlwa1E9PSIsInZhbHVlIjoiL3F5V3FnTWV0Mld6ckZQNXZ0TldDdz09IiwibWFjIjoiYTQ3NTRkODQ2YTk2YWRhZDkwNWQwNTJhNmNjMzIzOTEwYzQ1NDI3NjY1NzgzYWFhMjgwMmMyODFlZGVmNDRkYiIsInRhZyI6IiJ9','pppoe','30 Mb_Travelshouse',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(116,1,32,'*A7','akash_kkhan','eyJpdiI6ImlMUjZlcXlXSkNIdFZud1RRUGszREE9PSIsInZhbHVlIjoiR3d6MjhlTTN6elJEZTR1NlRLYytkQT09IiwibWFjIjoiNWJhYmUyM2M0M2U0MmYxMjVlMjQ1Y2E0MTQ2ZGZjMDEwYWNkOTU0ZDg0Njg2YTQ0MDhhZTk0N2Q5YjM0Nzg4ZSIsInRhZyI6IiJ9','pppoe','30 Mb_Travelshouse',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:17'),
(117,1,235,'*A8','shopna_surovi_aunt','eyJpdiI6ImNMdDE5TlZFSUV0Zi9SOThjL2xQSnc9PSIsInZhbHVlIjoiT1d3enlPZWtFdW1SV1lKekZJOFNBZz09IiwibWFjIjoiOWJhOGRlZjhjNWUwNzFmYTkxNGRlNDhmNTljYTMxMTBkZTU2NGZmYzA0MDIyMmZhYzZkZGEwNDQ3MDFlNWZmOSIsInRhZyI6IiJ9','pppoe','30 Mb_Travelshouse',NULL,NULL,0,'Anike',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:27'),
(118,1,236,'*A9','police_field','eyJpdiI6Im52VE5OWlpnSHhaLzdDUzBSU2lub3c9PSIsInZhbHVlIjoiaDNtZVU4NnZRUlBIbTByWHNZK3E1UT09IiwibWFjIjoiYmJjYmRhMDIzY2RjZDY5MmE2MmIyMDJmMDhhYmM1ZjI3YTFjNzE4N2VhY2U2ZmIxOWJhMjY5ZjEwZTllYWVlMyIsInRhZyI6IiJ9','any','50 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:27'),
(119,1,317,'*AA','wdbsaiful','eyJpdiI6Imd6N2w5amppcmpxdlRGT0x2RGlFMEE9PSIsInZhbHVlIjoiL0syYnpUdFI2RmtRV1UyUkJBcFFQQT09IiwibWFjIjoiMDVhOTU4MGYyNTI5Njc3MmE5ZDgzMTBhMDgxYjRkM2YwZWI5Mzc4ZjVjMzUyMDI3NGUxZDFhOGIzYjhlYzMxNyIsInRhZyI6IiJ9','any','30 MB shena_nir',NULL,NULL,0,'shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:44'),
(120,1,33,'*AB','ashikvai','eyJpdiI6IkNPbWhYbDlhSnR5V0k2c3BLN3FLNFE9PSIsInZhbHVlIjoiRUtZUExBNi9DL1VrSEJNT2NEc3E3UT09IiwibWFjIjoiZmIxZTBmZDg0OTUyZjM5OTkxMGZkNDZhZTA3OTAwZWYxNGIxOGZkMDA4NjEzZDQxY2UxYTg5NTVhOGQwMDBlYiIsInRhZyI6IiJ9','any','30 Mb Star',NULL,NULL,0,'shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:17'),
(121,1,34,'*AC','itech_sohel_home','eyJpdiI6ImZrenJja0E1Sk1RN0UvNDV4Sjg2U0E9PSIsInZhbHVlIjoiUFZTa1hXaUZDSEdVUzNNOU5CTytjQT09IiwibWFjIjoiYTQ5ZWZlZjhiMjEzMmM1ZGE4ZGIxNWU5OTdmZWNiMmNhZjhiMGFjMGExN2U0NzgwNzEwMjkwYWNkOTZkMmI2ZiIsInRhZyI6IiJ9','any','50 Mb_Travelshouse',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:17'),
(122,1,237,'*AD','shabag','eyJpdiI6ImtmQnA5VjVNeHVuVkc4WHRYclQ5MXc9PSIsInZhbHVlIjoibXF1cEJSS2F2dExxYk9sWDFXelZXdz09IiwibWFjIjoiZThhNjY2OWYxMDlmZWE1NWFiYjM3YjMxODc0MDhmNzk4NjVlYjk0MjI1YzQ2ODUyMjdmMDAxOTBhYjg1MmQwNiIsInRhZyI6IiJ9','pppoe','30 MB Saifulkst',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:27'),
(123,1,318,'*AE','wdboishe','eyJpdiI6IlRXRTVUY0RSajdaeE0wZU1aazIrK3c9PSIsInZhbHVlIjoiK2xjSnU1RkUrK1RVaHRhUGNPNW0wdz09IiwibWFjIjoiYmMyZDU0ZjY1ZTFlZjJjMTQ3ODBhOTEwNTkxMjM1MTIxNTUxZDQxNDJmMGYxNTlmZDJkNmI2OWQ5OTk3ODY5OCIsInRhZyI6IiJ9','pppoe','30 MB Saifulkst',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:44'),
(124,1,238,'*AF','rafi12','eyJpdiI6IkpUYmNhRDhySDArbElTRkdudnZsNGc9PSIsInZhbHVlIjoiREhhMDJJbTgxTUZYdmtMci80OHYwQT09IiwibWFjIjoiZTdkMDJkMWU4ZTBjMmFjODdlMWIyNzYwZDdmMGVlYWFhMGMxN2NlNDUzM2I3ZjE0MjA1N2JjZjBjZGQzZTc5NCIsInRhZyI6IiJ9','pppoe','30 MB Saifulkst',NULL,NULL,0,'Anike',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:27'),
(125,1,132,'*B0','kpikonam','eyJpdiI6IjZTT0R3aEpYSVN1TnAvdzM5RlpDcFE9PSIsInZhbHVlIjoiMlZyNmR1Z1hhMXQvWDZmbWQwNVo2UT09IiwibWFjIjoiODdmOGY0MDBhZmNlYmY0OTIxZDQ0NDc4MGJlOTkyYTI2MmU1Yjk0OTg1MWE1MDExMjg2MDI2NGE0MTA1ZjA5MSIsInRhZyI6IiJ9','pppoe','30 MB KPI',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(126,1,319,'*B1','wdbruble','eyJpdiI6ImdZN0pjczVydW16Z3pEbHVHVWpmelE9PSIsInZhbHVlIjoiSEdFRWpWRWQ0cGp1bk5NZHlzV3ZSUT09IiwibWFjIjoiYzhiMzkzMDAyOTAwZmZhMzQwMTkyZTQyYjMxYWIwMDZlZjE2M2I0MjY4YmNiOWUxY2Y1Mzg0MmYzOTJiZTMzZiIsInRhZyI6IiJ9','pppoe','30 MB Saifulkst',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:44'),
(127,1,35,'*B2','dristry_custom','eyJpdiI6IitMM0MzZm1IdU5yeUJIamNlV2l2ZWc9PSIsInZhbHVlIjoieURqNHhQOUVMWFV4OXlHb3FKQlk5QT09IiwibWFjIjoiNjZiYzJmMTM0MjViMDg1ZWE0YjI5NTI2ZDNjODE0YTUxMDIzYWYyMDI2ZmJkYzExNjA0NDgzNzhkNmQ5NTUzZiIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:17'),
(128,1,36,'*B3','cityfurniture','eyJpdiI6IldnN3hEcHRZTWRSZVNabHZHblJIM0E9PSIsInZhbHVlIjoiSDZ3YW1odXVvUXpvbmZndEhpN0dCZz09IiwibWFjIjoiY2M1ZDkyNjZjZWQ1MjhmZTllZmE2NTc5MjZiOWVmMTI1ZWI4MmYyNGIwNGU3ZDU2ZDZmNWRjYTQyMDFjYTNkOSIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:17'),
(129,1,133,'*B4','kpizisansir','eyJpdiI6InRqMTY0NFNaRmxGdGRTTVJtUW9zUXc9PSIsInZhbHVlIjoiTkhFN2dLeUhmSUxhSWxxa2ZuV2RpQT09IiwibWFjIjoiY2M5NTI2MTZiYzE4ZDE0OTI1NWQzMTRhOGJhNjZlN2VmZTljOTc5ZDk0MGMxYjJlOGU0MGI3YzUzNGM5MGU4MyIsInRhZyI6IiJ9','pppoe','30 MB shena_nir',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(130,1,239,'*B7','rajon_atiq','eyJpdiI6IklIOGFXRUdlNTErVSsyMndBYVpuWHc9PSIsInZhbHVlIjoibTZiNHVzTHpDeVZhUHd4VzRNK1JaUT09IiwibWFjIjoiMWZmYzgwMTQ4ZDg5MDU4MDhkYzEwMzZkNzMyNGQwNThlNWQxNDViM2QxZTM1ZGQ1MDA2OWYzMGI0NTIzYjVhYSIsInRhZyI6IiJ9','pppoe','30 MB govt_college',NULL,NULL,0,'Anike',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:27'),
(131,1,37,'*B8','familyjewel','eyJpdiI6InRsV25nS3BFZExzeHJhbE9LT0JRVXc9PSIsInZhbHVlIjoibU12b1J2QXdkN21JYVFscVZhcXRHdz09IiwibWFjIjoiYTUxNWMwNmE5YTAxNGU2NDU3MjkxYmE1NzM4YzAyYTM1NTc2MDkzYWExOTcyNzBjOWJmZGIwNmRkMmQ3Y2Y2NCIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:17'),
(132,1,38,'*B9','apexm','eyJpdiI6IjA3UkMvQjd3ZU9VQzRvZGxjaHNZcFE9PSIsInZhbHVlIjoiSEdUUHJ5amUzWEZsNTcvcXVHWEFKZz09IiwibWFjIjoiNzkwMjNlZDUxZTM3OTAzOTRhZTRjMmNiYTBlMmM5ZTI0NzhmODFiY2VhNjkxZWE4ZDMyYWM5OWRlNTExMDNiMCIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,1,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:17'),
(133,1,320,'*BA','tahamid','eyJpdiI6IjZ6RnJXY2E5UHRZMXdJd25kMzF1d1E9PSIsInZhbHVlIjoiRktCQ0lya2FGZmhKODZGNmVTRm5vQT09IiwibWFjIjoiMTYwZDE1MGQzMDcwZmNhY2ZkYzVjODc2NWNiM2YzYjVhMzEwYmQ4ZTY5NTA1MDc5ZjA3NDBiYTdkODEwNTQwMSIsInRhZyI6IiJ9','pppoe','30 MB KPI',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:44'),
(134,1,134,'*BB','kps_prijom','eyJpdiI6ImdJdWV0R05pNDlFcm1yc055NGJWZEE9PSIsInZhbHVlIjoiRmhBR3Y3Wm5RSWFSc2ptenlYcENqQT09IiwibWFjIjoiNzc0Y2ViNmQwOGY4NDFiODc2ODE0NWVmMDEwY2U3YmMxMGZhOTliZjkxNjQwNWQxMGQxNDJmMDc4ZGYwMmY4MCIsInRhZyI6IiJ9','any','30 MB govt_college',NULL,NULL,0,'Anike',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(135,1,135,'*BC','lged4th','eyJpdiI6InlWaVVSVFRYbXVVSHlBUlpkbjREU3c9PSIsInZhbHVlIjoicmtTQzVrY1l2ajZvVEwwY0l2T0NJQT09IiwibWFjIjoiN2IyOGRmMDFmNzQ0MDg3Mjc4MjEzMjk0ZWViYWNkZGM4MjY1YWRmNDliZWI0ZTMyMjBiYjUwODc3MzY5MTZlMyIsInRhZyI6IiJ9','any','30 Mb Star',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(136,1,39,'*BD','helelvai','eyJpdiI6InNvdVRCTHlmdlZ2OEQrSTNmOUxzNWc9PSIsInZhbHVlIjoiVy9OQzBYeWNDWlVWcy9MbHRxTWVRZz09IiwibWFjIjoiNWRlODgyOTc3MmIwYjA1NzNhZDRjYzMxYTI1YjMxY2NlNTdkNjI5YTJiY2Q5MGExMjQyNGZkNzUyN2I1NmRjYSIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:17'),
(137,1,40,'*BE','examcare','eyJpdiI6InNzaktaMjVQUUdNb1V3c3BBUDhITUE9PSIsInZhbHVlIjoiMlZOQUxDVmcxanQ4NWxWRXU3MkRsQT09IiwibWFjIjoiMmUzYjc0ZGUxMTQxYmRmOWRkNWQwNjY1MTU2MmM2YTY4YjEzNWM3YjMyYjNiOTYzYzc1ZWFkZTQ5N2JjZDY5MSIsInRhZyI6IiJ9','any','30 Mb_Travelshouse',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:17'),
(138,1,136,'*BF','kpilibery','eyJpdiI6IkZYdmlKRDJnMXk5UkVrZUxaZHdWMEE9PSIsInZhbHVlIjoiRmh3T01yZHowaW5BZkhtWnBQVnJWdz09IiwibWFjIjoiYmZiYTcyNjc5NjRjZmZhM2JlZDQ2OTFlYjQ2MzcwZmU1ZTMxYTRlMjhlZmM1MWIwMGRmOWVkZDZmMzdhYzIzMSIsInRhZyI6IiJ9','any','30 Mb Star',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(139,1,240,'*C0','shomobyb','eyJpdiI6ImxQRWptTStRakRBNEQxYVlxdTAyenc9PSIsInZhbHVlIjoiQjZjMGJ4ZXM1WUlwbHVWRHNraDZFQT09IiwibWFjIjoiMDBmYThmNjA4OGU2MmQ5NDYwM2NjNDJkZmVhZTE5YjU0YWRhOTNjMjlhMWQ4YjI3ZjYxODI4Yjc4YmFkMjUwYiIsInRhZyI6IiJ9','any','50 MB mosharof_bgoly',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:27'),
(140,1,241,'*C1','shohan_saddam','eyJpdiI6IjVSdXV6eFZiNkpkU1p0aE0xVXVBTkE9PSIsInZhbHVlIjoiT3FwSy9HSnYydXVxUWJEY25TcUdmQT09IiwibWFjIjoiNDUwMTMwZTQ3NGVlNTA5NTUyYzM0Y2JjYWZkMjY2ZGIzNzQ5MGNkMjcxZThlZGEwOGI2ZGU4M2M4OTY4NGFlNCIsInRhZyI6IiJ9','any','30 Mb_Travelshouse',NULL,NULL,0,'shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:27'),
(141,1,242,'*C2','police_imran','eyJpdiI6ImZsUGsyaFlIVkhJU2FVUVRhT1R3TkE9PSIsInZhbHVlIjoiZ09nL1NRTmpyT0xobkd5cnM1dCtNZz09IiwibWFjIjoiYjdmMTY5MTk1OWFiODZiNWJjMjlhM2M0ZGI0ZmYwYzllYmZlYWMzZjg1OTIzMWQ1NWU2NGY0MTk0NmExYjk4NyIsInRhZyI6IiJ9','pppoe','50 Mb_Travelshouse',NULL,NULL,0,'Anike 24/9/25 - 800tk',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:27'),
(142,1,243,'*C8','raja_uncle','eyJpdiI6ImFVN29kUmRMODgrdGhpSEpjNG1jc0E9PSIsInZhbHVlIjoiSERxOFhSZ3piU3B0emJFM2hkS3FFZz09IiwibWFjIjoiOTM5YmFhMTM4NmI4MDNmNGEyMmY1NjNiY2YxMWRiZTFjNmIxNDdmMGY5MDAzZWUyYzcxOTc4MDhiYzNkZGE0MiIsInRhZyI6IiJ9','pppoe','30 MB 141ranvid',NULL,NULL,0,'Anike',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:27'),
(143,1,244,'*C9','rudro_dip','eyJpdiI6IkY0VlFhSXdsMnRyUnJtcUtTV1BvTlE9PSIsInZhbHVlIjoiWUxOZFJkOHpVUU5mTE9udnRuekpVQT09IiwibWFjIjoiNTBhZDMwYzY4YTMzNGY4OWFjYmYwMGM2NDE2YzcwMzIxMGExNjI4NWE4MjQ2MTJmZDFkODJlZWY0Yjk0ZmU4NCIsInRhZyI6IiJ9','pppoe','30 MB Saifulkst',NULL,NULL,0,'Anike',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:27'),
(144,1,245,'*CA','Rakibhujur','eyJpdiI6ImZKTm9wY2c3bjVPaXhGSERhYnBta2c9PSIsInZhbHVlIjoiQ1JvNUNNWjdHSjEyN1NpTzlFNjhMUT09IiwibWFjIjoiNjJiMzQ3MzY5YWZjNDJjZTRiOTRjNjRhNTIxYThlNzM4NTc0NTVlMWExYWNmNTlmZmFlZWM1MTE5NjkzMTdlOSIsInRhZyI6IiJ9','pppoe','30 MB govt_college',NULL,NULL,1,'Anike',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:27'),
(145,1,137,'*CC','niribilit','eyJpdiI6IklnUk1jQVRNQVhzWVpYUlJZd2FFYnc9PSIsInZhbHVlIjoidE11NEE3Sngyc01OVGxyKzBmTlFmZz09IiwibWFjIjoiYWU1NTFlZjY5YTI2NjIyNjhmMzBjNTZkMDU1Mjg4MTNlYzBjNTAzNzNkNDlkNmI1YzVlMzliMjY1MjE1NTRjZiIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(146,1,321,'*CD','tisha','eyJpdiI6Iit0MUoxY1FKS0dzNWp3YytiQXpreGc9PSIsInZhbHVlIjoiK2xlaFltNTgwb0dnaVcySFZ6a0tzdz09IiwibWFjIjoiMmFkOTQ0YTcyZTlhYTE5MjRjOGFmZjk0N2I5NWE4MWVhZjVlYjk0MWE2Njc5OTM3OTA1MTZmYzA5NzYwMDA0MCIsInRhZyI6IiJ9','pppoe','30 MB Saifulkst',NULL,NULL,0,'Anike',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:44'),
(147,1,138,'*CE','munna','eyJpdiI6ImlpaDFQL0IvTzF2czg4cnlUemdicGc9PSIsInZhbHVlIjoib28waDhHamp1ZnFhN28wTDN5Wm4rdz09IiwibWFjIjoiM2NiNWNlMjIyY2UxYmEwNGZhNGRhMTFmZjA1NTVjMTQ5ZTUyZGFlZjI1NjQyMzNlMGI2YzhmYWM4NzU0YjlhOCIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,'Munna Barir pase',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(148,1,139,'*CF','kps_snv','eyJpdiI6IlF4R1krVU5pN3FTbzJnR2NEa29sZHc9PSIsInZhbHVlIjoiUkg2SE1HT0ZlQ1QveHNWbVEzYTdiZz09IiwibWFjIjoiMzcxZTE4NDNmZmQwY2I3ZjZkZGQ2NWRiMzU4OGU1NDcxOTM1OWM1Y2M5ODAzNTcyZTE1MzViZmFhMjIzMDAxZSIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,'Anike',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(149,1,41,'*D0','Dr_Samrat_Chember','eyJpdiI6Ii83aGhCaGtwbmdheENWRmZ5dmU5aFE9PSIsInZhbHVlIjoicjY1ZGxVUWRWQU9VdFMrN0oyMGdUdz09IiwibWFjIjoiZDg4MDU3ZmZhMDA5YzNkYTZiYmY3NDhhOTljODdhMjNmNmFjNjIzYjk2OTAzMmY4MmViYzQ3ZTA5YzgyZTI1ZCIsInRhZyI6IiJ9','pppoe','50 Mb_Travelshouse',NULL,NULL,0,'Anike',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(150,1,42,'*D1','ava_monowar','eyJpdiI6ImlDTDB1QThtVVI4YkpCcThhL0REQkE9PSIsInZhbHVlIjoidlVvaWNoaEVBNzVHaWFVRjlTY2pLQT09IiwibWFjIjoiNzYxZjAxMWIyZDNlMmMzM2Q1YjBkOTE2OWUwMjcwZWEzYzJkMzEwZjQ2OTRkYjVhMWE1MjI4ZGM3ZDQ5NGU5MyIsInRhZyI6IiJ9','any','30 MB govt_college',NULL,NULL,0,'Anike',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(151,1,140,'*D2','manik_fashion','eyJpdiI6InVsOVdJeFB0aFhOMTdRYnVxcGtKbFE9PSIsInZhbHVlIjoieFhtdFgxK0Jpd2Rtc1lQMVl5cVEwZz09IiwibWFjIjoiM2Y2ODY3YmQ0Mjc0NWZjOGNhZjQ1YjBjOWE5OWZhNWJlZDY5NmExNGJlMThhYzlkYTFjOTdlY2NjMzIyMjZkMSIsInRhZyI6IiJ9','any','30 Mb Star',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(152,1,43,'*D3','banker','eyJpdiI6InRRTE9TbDZabjZYYXZLOTEzNFlvMXc9PSIsInZhbHVlIjoiRHZlK3E1WDdBU2dUZTZQOTF1aElqQT09IiwibWFjIjoiYzkwYWEzNWRlNGVjNjhlN2Q0Mzk3OTlhNjU5NTA2ZTU0ZWY4MmMxZjU2Yjg5YjZlNmNlOWU2Y2MwZTkxZmY4ZCIsInRhZyI6IiJ9','any','30 MB shena_nir',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(153,1,322,'*D5','uttom_kaka_chuchona','eyJpdiI6IkNpSGd5dUx2Q3ZQYkYrR2JvOFZNQnc9PSIsInZhbHVlIjoiYStoZVV4Ym53aC9LbC9NdnZMUlJVUT09IiwibWFjIjoiMGIyMTYxMzJiOTA5N2IwN2YxZDU0OWY3ZDI3ZDdkMDNlM2JiZWE3NmUyNTRhOGE4Njc2MTE1YzllZjg5NTc5ZCIsInRhZyI6IiJ9','any','30 Mb_Travelshouse',NULL,NULL,0,'Anike',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:44'),
(154,1,246,'*D6','rafitkst','eyJpdiI6InJ1Sk9PSE9mSGdOUHB2K2pVcXlRaUE9PSIsInZhbHVlIjoiWVh2UlcrRTlvUnhBdThQTDZLSmdzZz09IiwibWFjIjoiYmE0OTc2OTFlNjU0ODgzMzdkNmE0ZjI4NzYxYWRkYTdmNjE5ZmY2NThkZmRlYzI4YTc0ZGNkN2ZiYWYzODMxZSIsInRhZyI6IiJ9','any','30 MB shena_nir',NULL,NULL,0,'shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:27'),
(155,1,323,'*D7','sohelvai','eyJpdiI6ImZpMGYrTEh2TEs5V2htZlZLZXRndnc9PSIsInZhbHVlIjoiTmE4YVNIcWRzbVo4REMzb29VTDdHUT09IiwibWFjIjoiOTE3NDRiZDE3ZWI5NjAzMDJkMGIwYTk4MDRiYTczZDA4Yjk5NjE2OGI2NzY3MzA4YTE2ZDU1N2I3OTA1ODY4MSIsInRhZyI6IiJ9','any','30 Mb Star',NULL,NULL,0,'shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:44'),
(156,1,141,'*D8','liton','eyJpdiI6ImJuRzl6dXpnYlhXQk5CNVF1NGFRL1E9PSIsInZhbHVlIjoiLy8yVmczRU1WTG9KbkptaDc0Nzdmdz09IiwibWFjIjoiYWU0MGM3MWVmMDAyN2E3ZjRiMmVmNDViODhhMzkwNjI4MGQ4MGI1YWYyZDMyZDBkZjJjNjJhNWE0YzhjYjUwZCIsInRhZyI6IiJ9','any','30 Mb Star',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(157,1,142,'*D9','mukul','eyJpdiI6IlNpN2V2cDBURFNUU0FqakVsazI1emc9PSIsInZhbHVlIjoiVTlvTTVuazlpcEpzemZ1L2JpM1J5Zz09IiwibWFjIjoiOTYzOGQzNjI2NzY0MDM3OGQ5MDQxODUyYTY2Y2UwNmY2YjRiNjQxNzI0NmJkYTQxZmY0MTE0ZjNkNDdlMzRjOCIsInRhZyI6IiJ9','any','30 Mb Star',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(158,1,247,'*DB','shikha_mukul','eyJpdiI6IjdUMVJ0a3hFRWZlWkFBTU5NQnNabHc9PSIsInZhbHVlIjoienhtWTkwV0lUYVc2b2ZpT3pWbHo2QT09IiwibWFjIjoiZmE2MmU1YzY2YjhkYmRkMjI4ZGI5YTRhMTgxNmUyZjFmNDc0Y2FiYjI4ZWRiYzRmMjU4MzhlZGE2NzM2NzY1NyIsInRhZyI6IiJ9','any','30 MB Lgedks',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:27'),
(159,1,143,'*DC','ornob_mukul','eyJpdiI6IjFXYndlR2o3RHl4enNUYzQ3aFA3eXc9PSIsInZhbHVlIjoiZHJVQTBITnRFdGd5MXR5b3pEZ2FVQT09IiwibWFjIjoiY2IxZTkwNzMwYjNmOTg2NzUxODk5NzFjN2Y3NGJmZDY1YTI2ZGQ4ZjVmMTVkM2JkMGVjMTQxYzY4NzQ3OTQ2NSIsInRhZyI6IiJ9','any','30 MB govt_college',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(160,1,44,'*DD','hannanvai','eyJpdiI6IlM5OE9icHB0bFgrSnhJRDdrTDRpYkE9PSIsInZhbHVlIjoia3gvTVhRVVVMcTlFZkM2K3RLZjdxZz09IiwibWFjIjoiNTQ3YjQ0YTBkY2RiZjExZjNmMDZkZGQyNGMxNzNhNDUwZTBmYjJmOGI1MzE0ZDk2MmMzZWM5NTZjZGM1YmViZCIsInRhZyI6IiJ9','any','30 Mb_Travelshouse',NULL,NULL,0,'shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(161,1,248,'*DF','rohan_mukul','eyJpdiI6IklDa1gzNjFMYkdiY0ZoNE9BUXJBWGc9PSIsInZhbHVlIjoiaVMvYmF2Y2ZlZytqN0I1Ym16UzhOUT09IiwibWFjIjoiMGEzMWZhNzkxZmI4ODg3ZDA2MTFiMmU2OGMwNDllOTc2YjA0M2FjZDJlODQzMDIzYmMwZWYwNWI5ZjBhYTQyNCIsInRhZyI6IiJ9','any','30 Mb_Travelshouse',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:27'),
(162,1,45,'*E1','apuvai','eyJpdiI6IkJSOVBKY2RSam9rcktvdDkxLzR3eEE9PSIsInZhbHVlIjoiUXJqV0NFeUJCWlRrTXBhd01pUllCZz09IiwibWFjIjoiNWEyNmIwYjE5MjBjMzlhMmI4MzUzMjU0YjAyYzg4ZjNmZjZkZGQ0MmEyZWVhYmIzMjU2YWZjZDI5MDdlZDZmYiIsInRhZyI6IiJ9','any','30 Mb_Travelshouse',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(163,1,324,'*E2','upzfamily','eyJpdiI6IkVLMXVXVW5jMFRDaE51NVYrcTlLTlE9PSIsInZhbHVlIjoiRU41czNFMWNzZGd2Ti9RS1djdjNHQT09IiwibWFjIjoiODUzMWNiOTdiNTBmOTVmZjI3ODMwNzljM2M5ZTE1MTE4YmYwNDVjMzljZDVlYjJjMjAzNThmM2I5MWQ2MjJkOCIsInRhZyI6IiJ9','any','30 Mb_Travelshouse',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:44'),
(164,1,144,'*E4','kps_arif','eyJpdiI6IkZTM2paS2xBMXo1Z1VQbDZMeXg1VHc9PSIsInZhbHVlIjoiUzJwaGRXMEVIRWdjclRiVFBIbjV3Zz09IiwibWFjIjoiYmJlMTBmOGRkM2JlMDg5ODllODU4NTJkZTcwNzRjNTk2YjQ5MDc1ZjY5YTNjOWNjYjYwNjE4NTE2ODYzMTAxNCIsInRhZyI6IiJ9','any','50 Mb_Travelshouse',NULL,NULL,0,'Anike 600tk /10/25',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(165,1,46,'*E7','ic_arif','eyJpdiI6ImE1RFpNb2Z6Q2NsaS90NDBHeGdLT2c9PSIsInZhbHVlIjoiV2VwU3dpWGRHRzYxOVpLT29NcG9VQT09IiwibWFjIjoiMDhiNjY0ZWEyYjYzNGY1ODA2MWNkZjU2MWZlOTBmNWJlYjk0NGM0MzhhMmQ4NmFjYTRlN2I0NjFkZWE0MmUwMyIsInRhZyI6IiJ9','any','30 MB shena_nir',NULL,NULL,0,'shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(166,1,249,'*EA','raisulsir','eyJpdiI6IjI1ZWUrejNPdkIvRWdVMFFYQW0vTFE9PSIsInZhbHVlIjoieGs5c2hVUWZxK3FFdXJXMDluZDNqZz09IiwibWFjIjoiMzA0MjI2MTFjNTUwNzNjMzI2MzMyOTJmMjg2NjY0NzQxODFhMjMwYzM2NGNhNDMzYWRjNTRmZTMwZThmYzBjYiIsInRhZyI6IiJ9','any','30 Mb_Travelshouse',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:27'),
(167,1,47,'*EB','asad','eyJpdiI6Imp3ak9idUtDWkYrNlNUSVNnKzRaL2c9PSIsInZhbHVlIjoiaHdoNEJ3VjVOSVhDbEMvTzJkSXJJZz09IiwibWFjIjoiY2Q5ZmY4MTZlY2Q2ZDZiYTkzNTQzOGIxMjYyMjIyZjM4ZWU5YzE5OTc2YjczYWIzN2IzNGUxYjA3MWE4NzZlNSIsInRhZyI6IiJ9','any','30 Mb_Travelshouse',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(168,1,48,'*EC','ali_vai','eyJpdiI6ImgwOEdEOVcyMXRadENXQjNiQTRSdFE9PSIsInZhbHVlIjoiZmtobU5adVlMR3o2RXRTY0YzYW01UT09IiwibWFjIjoiMzYyMzdhNDRkM2E4N2EzNmZkNDQ4ZGNmZDY1NWRlMDEyOWRiZjFmYjA3MDA3OWMwOTI5NWRjNDQ2ZmUyZDcyMCIsInRhZyI6IiJ9','any','30 Mb_Travelshouse',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(169,1,325,'*ED','sohel_travels','eyJpdiI6IlJDcjlnN0E4K3drRzhXcVZMTFZiQlE9PSIsInZhbHVlIjoiM1Zta3RBejJXcVU2WHJFajVJWHdFQT09IiwibWFjIjoiYTczYzI1NDg1YTU1N2NmYmU2ZDg3OGIxNzU1NGZkNGM3ZjA4OGFlMzI0YWVlNmNiZDcxOWExMWMyNzExYmE0NyIsInRhZyI6IiJ9','any','30 Mb_Travelshouse',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:44'),
(170,1,49,'*EE','islamia_ict','eyJpdiI6InVtTHA4em8xMWk4N0NaT3dUUHZWL2c9PSIsInZhbHVlIjoieXBPTnNBN09PY3kxQWJJNUNsclVpZz09IiwibWFjIjoiZWY5YTJmNmU5ZGQwZGQ4NzVkY2I1OTMyYTI1YTRiNjI0NGI0NTM3MzQxNzI3MTVhYTI0ODJiNjg4MWZiZmVkMCIsInRhZyI6IiJ9','any','30 Mb Star',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(171,1,50,'*EF','buroacc','eyJpdiI6ImhrRlBicFFYSmtzanlLZ21hVDBidHc9PSIsInZhbHVlIjoiSE9CL1BVMU5KRzFGVTB6dHZsYmtpdz09IiwibWFjIjoiOTNjZTg4YTRmZTUxMjBlNTZlYjYyMWY2YTY4Y2U5Yzc0ZWVmOTAxNzRkN2NkYTM1MjYwZjA5YzBhYzE2M2VmOSIsInRhZyI6IiJ9','any','30 MB Lgedks',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(172,1,145,'*F0','mizanvai_btcl','eyJpdiI6ImpLL1VPVms4K1cxSml1b25JSVZic1E9PSIsInZhbHVlIjoic05mSnV6MWJiTHJwVDlvd1NHeGNuQT09IiwibWFjIjoiMjQ2OTg3OTA3ZmQ4ZTM2NDM3M2JlZjc5NDlhODMyZGUzYjBmOWIyMTBkNmM3NmZkNzNjNTAzNDA2Y2UzOTM1YSIsInRhZyI6IiJ9','any','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(173,1,326,'*F1','wdbjoy','eyJpdiI6IlRQREx3T2lkRXhKWXVQM2FTR0xiY1E9PSIsInZhbHVlIjoiWGNqc0ZkdFZnVWIxVHQyMUsvbGdidz09IiwibWFjIjoiMWU2Y2VkODE5MGJhYzIyMzViMDJkZmJkMTc4MDBmZTA3Mzk3NThmOTIyMmEwMzg2MmRmZTFiZTViYzdjZGIzZCIsInRhZyI6IiJ9','any','30 MB Lgedks',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:44'),
(174,1,250,'*F3','rifab','eyJpdiI6Ik94OEI4aldIRUY5OE9jWlJndmtLekE9PSIsInZhbHVlIjoiZmpzSlVBNytiWXJMTmswSmF6eWdhQT09IiwibWFjIjoiNDRiMjYwMDY2ZGM3YTE3ZDgyZGJhNDM5Yzc5M2NlNTU0OWVkNWJmMzdjZTFjYmQ0NDNkYzRiYWM0ZjkzNGFjNSIsInRhZyI6IiJ9','any','30 Mb Star',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:27'),
(175,1,327,'*F4','sunjid','eyJpdiI6IjJZMzRSbmkyL0gvbFU2Z1pCUVNMTkE9PSIsInZhbHVlIjoidmh0NXRCQk5mbXpIbnVlRjFxR0Vndz09IiwibWFjIjoiOTg2NWJkMzBiYTRjZTFhZjYyZTMyNzI0MGUxZjNhNTFjMzMxZGQ2NWE0NGJjNjMzYzgzYzc2OWZiYTExODQ0YyIsInRhZyI6IiJ9','any','30 Mb_Travelshouse',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:44'),
(176,1,146,'*FD','kps','eyJpdiI6ImpXMm83eU1WWEUrTTFRa1lDaHBKWVE9PSIsInZhbHVlIjoiRTNsZ2gzVEJiaGN2Q1lBaWsyWXRKZz09IiwibWFjIjoiMGE4ZmZmOGE1NDM1MGM0MTRmODA0OGVjOTU4M2I0ZjkwYTg3OGMxMDQzODJiYWY5MTAxNzViYzlkMjM4MDNjMSIsInRhZyI6IiJ9','any','30 MB 141ranvid',NULL,NULL,1,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(177,1,328,'*FE','wdbpd','eyJpdiI6InFyNXRDQ3lUZE5hQmV4NEFnOEJYZmc9PSIsInZhbHVlIjoiYzBRM1B5N0hGYzBDdjJnK2YwZU51UT09IiwibWFjIjoiZmE5ZjdiYzI5NDJkZjdhYjIyOTNiMjhlNmY4ZGFiNjc5ZGQ4ODEwMDUwYTY5YmU0ZTU1ZGI2ZWIwZjM5OGVjNiIsInRhZyI6IiJ9','any','30 Mb_Travelshouse',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:44'),
(178,1,51,'*FF','educationxen','eyJpdiI6ImtnWTA2eXBxRWZnR3hrZ08vem9HSkE9PSIsInZhbHVlIjoiNitTK0NPVE5nemMwcU81M1B6c25Cdz09IiwibWFjIjoiYWJkZWFiYzdmMDkyNDk0MDI3NzQ2Y2YwYTg0NjBhZDI5ZWZkYzE1YTBkMzJkNzJjMDFiYWFjYTA5OTZlYmQ5ZiIsInRhZyI6IiJ9','any','30 MB govt_college',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(179,1,52,'*100','073waltonp','eyJpdiI6IndSUTJ1ZlNzVDVzQ1dvVEtDd0Rzamc9PSIsInZhbHVlIjoiUjMvNXVBcjFNODlKNHdWdHZId0VHZz09IiwibWFjIjoiNWFlNmJjNzE3MjNjNDIxMmI4MzU0MGNjNTkzYzcwNDM0YTAxZDlmMTBkMzk3MWIxODg4Yzk3NmE4OTM2ZjE2NSIsInRhZyI6IiJ9','any','30 MB govt_college',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(180,1,53,'*101','dristy','eyJpdiI6IkV3ODB0SFZQZ2IvS2UzRVFMRGQ5ZFE9PSIsInZhbHVlIjoiQ0xxMk5ROXFxSi9xRUpYemJoY1VHZz09IiwibWFjIjoiZjI1MWVhZjI3MmZhMGQwNzAxZjNlZDk5Y2NjMzhjZWJkYzdmYjJlNGY0OTZlYmYwYjA2OGEwY2U1MGE5ODg0MSIsInRhZyI6IiJ9','any','30 Mb Star',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(181,1,147,'*102','kist','eyJpdiI6IjlYcGM5endYSFpVVTVKejBxQVV3dmc9PSIsInZhbHVlIjoiWXJkMjNtUHl0KzRTenNqenduU3dvdz09IiwibWFjIjoiY2IwYjc3Nzk2ZTM2N2IxOGRjMDA4ODVhYWNjYWRkYWQ0OTU3OTVhYTE4ZTA0MWNiYmVhMzkzOGVhMTEyODkxMyIsInRhZyI6IiJ9','any','30 MB govt_college',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(182,1,251,'*103','shahed_basha','eyJpdiI6Incwcm9iRkZtZHEwZ1VsRzRiRjZyT0E9PSIsInZhbHVlIjoiakE4aWFYTzRrY2U3dVlXcndEUkU5Zz09IiwibWFjIjoiZTExNGYxNjg0ZTMzN2VmOWE0YmEwMmU2ODNhMzEyZDc1YmY3N2Y1YTRkMTVlZTdkY2E4NmU4ZWQwYzI3Zjk4MCIsInRhZyI6IiJ9','any','30 MB Saifulkst',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:27'),
(183,1,54,'*104','Islamia_ITC_LAB','eyJpdiI6IlY0S1lCdllEQ3BqcVlVQnMvYitrK1E9PSIsInZhbHVlIjoiMUtVb2lUbjc3SVZLc1BNclNNL3JsUT09IiwibWFjIjoiODFmOTUxOGVlYTk2Mzc3ZDgxNzE2ZDUzMzhjODJlMGIxNWUwN2Y5YzRjYzg0MGRkYzAwNWIxYTVmMzFiNDkzNCIsInRhZyI6IiJ9','any','30 MB Saifulkst',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(184,1,55,'*105','ics_history','eyJpdiI6IlRrODAvUE04M2lZQi85d0hLS2ZNRnc9PSIsInZhbHVlIjoiL054U1FaeWRoRzB5N1YxYldSaGs0UT09IiwibWFjIjoiNjkxMzViYTgxOTliZGU5MTA2ZGM0NjBjNTcwYmM5NzA0MTQ3NjQ4MGVhN2Y5YTc3YzVlZjcyZGQ1ODY5NzFjNiIsInRhZyI6IiJ9','any','30 MB Saifulkst',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(185,1,329,'*106','wdbhujur','eyJpdiI6IjBSSVVpRmlJZ2lnOW1oUDFnK3FEenc9PSIsInZhbHVlIjoiSlZJZGxIRFROcmpiUWNtRGFvSHh6dz09IiwibWFjIjoiMjEyYTRlOTFlZGM2ZGQyOTMxNjg2ZjM5Yjg5OTI2NjgzZTBjNGNjZGJlYzMwNmNiZGY4MTA1MDQyYjBkNmQxYiIsInRhZyI6IiJ9','any','30 Mb Star',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:44'),
(186,1,252,'*107','shadid_elc','eyJpdiI6IlBPL3ExK1RqbGN1cStWWVNzMGcrWVE9PSIsInZhbHVlIjoiTXV0S1BJLzIyei9sZGZNNEtzS2hYZz09IiwibWFjIjoiMTFmYjYxZmQ5OWQ2MzlmM2RhYzg0ODdhMjQxNDhhMGIyODBkNjBlMWZiZWViZjEyMjcxYWM0ODAyZmQyZDA1YSIsInRhZyI6IiJ9','any','30 MB Saifulkst',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:27'),
(187,1,253,'*108','police_imran_home','eyJpdiI6ImNIWndlS0xEZ0I4QnZFVEg5NS9CWUE9PSIsInZhbHVlIjoiRys5OFhLSkpGbFNNWEpYWlhjZlV6UT09IiwibWFjIjoiOWM1MjM2ODEzZDkyMGYzODU2ZjU5OGVjNTJkMWFlYjg2YmNkOWU5NTBkY2FkYzNiNmZhNzA5MDc5OThiN2QxNCIsInRhZyI6IiJ9','any','50 Mb_Travelshouse',NULL,NULL,0,'Anike',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:28'),
(188,1,330,'*109','turjo_saddam','eyJpdiI6InFScHpneUxNeS9XL1draXdGR05sSEE9PSIsInZhbHVlIjoiU29uYXdDTWE0UFZrak1NbElrZEcrQT09IiwibWFjIjoiZDRkN2Y5ODc1MzE3MjcxMWQ3Y2U1YzczYzcwMTE0NTIxMTZiYjA3MzI3NTczY2QwZGIwNWQwNjAwMWM3YjJmYiIsInRhZyI6IiJ9','any','30 Mb_Travelshouse',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:44'),
(189,1,148,'*10A','mamun_telicom_salim','eyJpdiI6IktFZG5veVRKMEMvbnpGQ1NaRDkvTVE9PSIsInZhbHVlIjoiZHgwbEVTSGt4UGhUQkZ3NG5VZURjdz09IiwibWFjIjoiNmY1OGVlMmRjYTM4NmIyNzIxMTQ4MmFlYmZmYjNiNDY0ODQzYTQyN2M1NmYxMGMwMGM4NWZjYTBhMDE1NzlhNSIsInRhZyI6IiJ9','any','30 MB Lgedks',NULL,NULL,0,'Anike',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(190,1,331,'*10C','test321','eyJpdiI6Im9uNld3enFvNmdhVTN5V09mNStPV2c9PSIsInZhbHVlIjoiNzkwS0pTcnprTFRRa1VXTEF0WFo5UT09IiwibWFjIjoiYjc5YWU1YzdkY2UxYzBjZmM5YTRmZmNkYjlhZTNmZjVhZjhkMzg3NTkzMTk1NmQ3MGQzNDc4ODBkM2E4NWZlMyIsInRhZyI6IiJ9','any','30 MB ZIlas',NULL,NULL,1,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:44'),
(191,1,149,'*10D','kibriavai','eyJpdiI6IkNzK2xrakdtWllGV05Ma2lDRndpYkE9PSIsInZhbHVlIjoiSVc5eFJsc0JXMm04TExRYURFZ1pjZz09IiwibWFjIjoiZWVjNjcwYjk4OGE3MzQ3MzcyNjc3ZDVlZjY1MGRjM2IyNTFjMjMyZDIxMTZkYjI3M2M0MWQzZTMzZDAxNDlkYiIsInRhZyI6IiJ9','any','30 Mb Star',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(192,1,332,'*10E','sijanglass','eyJpdiI6Imp2L3ZWNW5DOXluK0JTUlZzN1lUYmc9PSIsInZhbHVlIjoiemdZRGtxdXRIYzlGaTY3YTRGUUd3dz09IiwibWFjIjoiYjRkZDMwYTBkMTQzNTIyMTBhMmM3ZmJkNzMwMDNlZmRmZDAxZTFiOWU5NzM0OWU3NWE3MWMwN2RkNjk0MDAyZiIsInRhZyI6IiJ9','any','50 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:44'),
(193,1,56,'*10F','kawserprint','eyJpdiI6ImgxekExNThNYXQrb2RWZk5TOGxGUXc9PSIsInZhbHVlIjoiSHFhWjNRZk5OVzNDMTRQTmovMUVvZz09IiwibWFjIjoiNTNlMTdiZDU1ZjAxZTE2NjU1MDhhNTQwMDEzNmMxZDYxNDUxZWEwODhlZjBjMGIyOTMwOWM1ZTQ4N2NlNzhkMSIsInRhZyI6IiJ9','any','30 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(194,1,57,'*110','babul_fyt','eyJpdiI6Ik8zaTBpTzZKUUl6aEhvZEJseERBTEE9PSIsInZhbHVlIjoibm5CTWVJemhtTnU1RVhuSjJvaFh2QT09IiwibWFjIjoiNjllYTE3ZmE2MGExM2E1NGM1YWU4ZGQ2M2Q5MmYwMDk0NjQzN2I2ZWIxZjUwZjc5YTdmOWI1NWFiNDZmNWJlZiIsInRhZyI6IiJ9','any','30 Mb Star',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(195,1,254,'*111','sabatr','eyJpdiI6IlZUUWovY3RBMmhkeFMwRDBicU56UUE9PSIsInZhbHVlIjoiMDVrYUlRcDNLRDVYL3FSYW1URHlPdz09IiwibWFjIjoiNmNmNTg4MzI0YTM1OTA1ODIyMjE1NjA5YWJlNzE2Y2Q3M2U1OWRiMWIxNTYzOWVlZjJmMjRjYzY4MzM5MTUwMSIsInRhZyI6IiJ9','any','30 Mb_Travelshouse',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:28'),
(196,1,58,'*115','fulbabu','eyJpdiI6IkRUOXVRcHdONlpSelUwVWsxYS80dVE9PSIsInZhbHVlIjoic2t3TkxQbWt6TEhNcmQ0VzFiaUhrdz09IiwibWFjIjoiMmFmNzBiOTA1N2RkMzg3MzM1MDA3YzAyNTU3MDFjZTA5MmU5N2Y0NjlkNGI5MmQ2YzFkOTljOGZhNTJlOWE3ZiIsInRhZyI6IiJ9','any','30 Mb_Travelshouse',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(197,1,150,'*116','kushtiat','eyJpdiI6Ink2OXRWWTJZWFV3eFdvL2lJQ2JGOGc9PSIsInZhbHVlIjoiVUllNXU1YkNiZkF0bWFVR29RTVpHQT09IiwibWFjIjoiMjk0MzExZTIxYmI2NWRkYTAxYmRkODBhMWY0NTRkMjkxODBhYzM1MmE0ZmE5OTUxNjA3NDFjNmRiOGRlMzE1MCIsInRhZyI6IiJ9','any','30 Mb_Travelshouse',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(198,1,151,'*117','mir_teacher','eyJpdiI6ImhJTnpjSXlNYjBDMGU2Unk3Uk1wNlE9PSIsInZhbHVlIjoidmFveHozZnlJQlR2cWtxdDdIaXJiQT09IiwibWFjIjoiN2JkNzgwZjg2Y2U3ZmE1MjNlNGY0YjBkYTY5MmI5MjVhMWIxNzZhNWNiYzg1MDc4NGM1NTgxNDdiNWY1NTMwYSIsInRhZyI6IiJ9','any','30 Mb_Travelshouse',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(199,1,59,'*118','joardderbtc','eyJpdiI6ImkwREFNQWdtcWlwUzlvb1JYNzlGQXc9PSIsInZhbHVlIjoiWHd0UTFmR2VvODU0QVJXTHJYMkxDQT09IiwibWFjIjoiODEzNjcxMTcyNmVlNDU2NWNmZmZkMzA3ZDRhNmMyMTNkNmQ5OWY3MDI4NzJiMDkyYTdkYmI2ZGVmYzRkNjgwZSIsInRhZyI6IiJ9','any','30 Mb Star',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(200,1,60,'*119','fishoff','eyJpdiI6IlNSSDZUNHlEdCtseVlPMmh1WUNiU1E9PSIsInZhbHVlIjoidEMvL3FSanBlZW9jZjUwSm14N0VBUT09IiwibWFjIjoiMzE5NDc1NTQ1MWYxZThjNWUzMTllY2IxMzNhZDFmY2JhNzdjZDYyYjVlYjZiMWQ0MjE2ZjYwYzY5ZWYzMzQ5NyIsInRhZyI6IiJ9','any','30 Mb_Travelshouse',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(201,1,333,'*11A','toyebmob','eyJpdiI6Img0SU90UU5iRzJSNmJ6dlc4N2M4L2c9PSIsInZhbHVlIjoidlNKNUlDUHA1UmZQWVg2emxvZ3ZZUT09IiwibWFjIjoiMGJhNmZlNjg4Y2RmNWY0NzQ3ZmE0OGY3M2NmMDFmOGNkYzVmNmNiNjMwM2I3ZmE2Mjc3MWE0Y2UwN2VkNzA4MCIsInRhZyI6IiJ9','any','30 Mb_Travelshouse',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:44'),
(202,1,152,'*11B','marufkst','eyJpdiI6InhQWUswS0E5NjFvWDVsYUVJRnlBbkE9PSIsInZhbHVlIjoiSVEyYjhvTDVDU1FGZXJRaGtkeXV0dz09IiwibWFjIjoiYzlkZmU5ZjkyZTQ1ZDQyMjVmMGVjYzNlNDg1ZjZhYTI1YzcwYjJhMmEwMjNmZWIzODQ0MDUwMWFhMzdjNDM5YyIsInRhZyI6IiJ9','any','30 Mb_Travelshouse',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(203,1,61,'*11D','kbmahidi','eyJpdiI6ImdYbyt3eHFEWGdMbkRtMnZHSFVLWGc9PSIsInZhbHVlIjoiRHZ6N2pDWnRyN3lCTUE4b2lOQmNoUT09IiwibWFjIjoiNzQ1ZGZhMjQ3ZTIzNDJhYzA4Y2MyMDFjYTBmMDM4ODUxZWJiNDI3ZGM3ZjU0ZjY2YzkwMjQ5YTRjOTZkYzZmMiIsInRhZyI6IiJ9','any','30 Mb_Travelshouse',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(204,1,334,'*11E','wdbrest','eyJpdiI6IkFIQkpLeHZRa0UwR3NETWpyWnlzeGc9PSIsInZhbHVlIjoid3ZyME5nTVRidWM3d0ZYam5ySHVoQT09IiwibWFjIjoiMGRlOTZlZGEyMzE4NTI4ZjVmNDY2NjUzYTZkMjY2MGFjM2U4MWE3OTdlMTBjZGEyY2NmMWFhZTBmYzBlYzUyZCIsInRhZyI6IiJ9','any','30 Mb Star',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:44'),
(205,1,153,'*11F','majo_nana','eyJpdiI6IlQ3WFRWbERTSVE0VFcwY2xZUGMwRGc9PSIsInZhbHVlIjoiTkpSOWNpWWcvczFGZnpBTGJvTkNlQT09IiwibWFjIjoiYTg3Yjg0YzY3MzMwZTc5NmFkMzVhYTQyYTE4ZGQyNTMxYmZkMjZlYmE2ZDE0YjM4ZGJkMmU2ZjEwMDM4ZWNjZSIsInRhZyI6IiJ9','any','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(206,1,154,'*120','khairulkst','eyJpdiI6ImpRZG1sZm1ZdWwyVzRDZWQ0aThZcUE9PSIsInZhbHVlIjoiTlZiL2VrMXhPcTRCM0VuaW41UmZEZz09IiwibWFjIjoiNjNjMzIwZWY1YzA4MTVmYTUxOGIzZTMyMjViOWI3ODQ2ZWNiZGU3MGI2YzYwZmRmY2M0NTQ4MTIzYzRjYTUwMCIsInRhZyI6IiJ9','any','30 Mb_Travelshouse',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(207,1,255,'*121','setu6','eyJpdiI6InhyT0plRjNGak1xRVBtQzhiNmtNMkE9PSIsInZhbHVlIjoiVVhXdGZ5bWJsd3RlWWFqdk5sNlFPZz09IiwibWFjIjoiMGY4OGZkMzg2MjY0MTIxMjAxMjQyMjMxNzhhOTJmMWZhNWMxYWM5NTAyMTU4MTFlMGJjNTkzOWFkZmVkYzc1MyIsInRhZyI6IiJ9','any','50 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:28'),
(208,1,335,'*123','zillaschools','eyJpdiI6IkVWRUpDSkNIcHBNUjlrOEtFWlZhdUE9PSIsInZhbHVlIjoiNStxVkx2YTdrR3Y1RG1HdXEvcDdTdz09IiwibWFjIjoiZWZhNTdiNzA1OGQ4NmM0YjBjZmJlYTc4MjZkNjZmMDE0ZTA5MDhmNGE2ZjA2ZWFhYzJlYjcwNzI2MWM4NmI1ZSIsInRhZyI6IiJ9','any','50 MB mosharof_bgoly',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:44'),
(209,1,336,'*124','ttcpkb','eyJpdiI6ImUxYS9XV3JHUE8vYnZYRWVwUk1pSXc9PSIsInZhbHVlIjoiQXdhZkptTzNvKzczMlFZU3Y5ZnE0UT09IiwibWFjIjoiOTc1YTg1YzkxNGUzYTE2MGQwOTE5NmI3NTk0MTQ2Y2YyNTQxZjUwZGFmYmIyMjZjMThhNzYzMzNhNThlMDY2ZiIsInRhZyI6IiJ9','any','30 MB Saifulkst',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:44'),
(210,1,256,'*125','pwdasst','eyJpdiI6Inkwc09nNURRYVl4ZWY4Q1loVjhTc1E9PSIsInZhbHVlIjoiRzVkTlducmRwU2VDMWdldzdpeHJGZz09IiwibWFjIjoiZmEyNmU2M2RkOTBlYjIzNWI5MTQ3Nzc4MWNkMzMwYTM4MTk1MDcwOWQ4NTNmY2M3NjQxZTk5YzI5ZjdlOTY3MCIsInRhZyI6IiJ9','any','30 Mb Star',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:28'),
(211,1,257,'*129','rublevai','eyJpdiI6IklWd25YN2ZnU2JKZXNyU1RlK09ablE9PSIsInZhbHVlIjoiUDJtdUdEQlA5cU9wazFzeTBSM0hUQT09IiwibWFjIjoiNzA2MTI1MDFkYjkyZWIyZGFmNjQxNjQxZTVlMWZhZTFiY2MyMDIwZmRhMTgyNzhlN2IzYjk2ZWVjYjMyY2YzZCIsInRhZyI6IiJ9','any','30 Mb_Travelshouse',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:28'),
(212,1,337,'*12A','sksahib','eyJpdiI6IkJoazhIN0xDNGNQRzBJeldpZm0ycXc9PSIsInZhbHVlIjoibG1vZDhBMHExQ04weVZsRWhSRzRaUT09IiwibWFjIjoiMWQ5YmE3MmU4YjU5YjFkZmMzMTJjY2U3MzcxM2Y5MGQ0NWJkMjQyMTI3NWU2M2FhMDM1NzZlNzJhNmJhYTIwMyIsInRhZyI6IiJ9','any','30 Mb_Travelshouse',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:44'),
(213,1,155,'*12B','mmclass','eyJpdiI6IkF0L0NXSEdudnp6aDRBdzdhc01MclE9PSIsInZhbHVlIjoiazFHZFJXZms0WnJvRUZOeDFmR0tGUT09IiwibWFjIjoiODFlYTI2Yzc0NDIyZDU1YTY4OTU4NjQ2ZmIyODExYmNiYTBiNWIxZDE5ZWIzYTAxOWJlMDE2ZDQ2NDE1NjRlYiIsInRhZyI6IiJ9','any','30 MB ZIlas',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(214,1,62,'*12C','ful_jaman','eyJpdiI6InZGcDV5REFheVpCZmpiWk1JbDdObHc9PSIsInZhbHVlIjoiZVpMQmdVWE1WOWg0dkJHVC9vYlFlUT09IiwibWFjIjoiMjVlMTJmZTk3YmE0ZGM1ZTc3M2ZlZDJiYWQyNmQyMmI0MzczNDM5NGJkZDFjMGFhY2Y0Y2JiYmUwNjAxZDA2NyIsInRhZyI6IiJ9','any','30 MB Saifulkst',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(215,1,156,'*12D','nahid','eyJpdiI6IjAwWHYrTnlyRlVwUFJmcFBpUC93UGc9PSIsInZhbHVlIjoiZ2NESjNtcGVGWHQ3QjJkRXNQZ1RXUT09IiwibWFjIjoiZGFlOWE1MjU5YzgyZWJhM2UwYWUwNjJmZDQ3MTVlNTZiY2VhMmQ2NmQ3ODQxOTg2MGEzNGMyMjg3OWY5ODBhZSIsInRhZyI6IiJ9','any','30 Mb_Travelshouse',NULL,NULL,1,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(216,1,63,'*12E','buro1','eyJpdiI6IjBkTTdYL3NFUStrcm1FUkFxR3V5NHc9PSIsInZhbHVlIjoiNm9DY0NBelZoWjJlZE9VaTk4WGpjdz09IiwibWFjIjoiN2JmMTU2ZTM0ZmYyYjJiMjZjMzZlNDcyMDQ2Y2Y3NDBmMTE4M2YyNTk0ZjA5NDI4NzFlYjA0NmJkMDEzOTUzMCIsInRhZyI6IiJ9','pppoe','30 MB govt_college',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(217,1,338,'*12F','test_hap3','eyJpdiI6InRxU25rN052OXloWDUzcEpqWk1hWXc9PSIsInZhbHVlIjoiZ0tXZlZjc3A1MmVUMk1TWUJrSHhxUT09IiwibWFjIjoiOWI4YjIzNjAzMGJjYmFhY2M2MmY0OTIyYTBmMzU3M2M3MjBkMzg5ZGE4MTk1ODc0MmJiMjVmMDEyZjgzZmE0ZSIsInRhZyI6IiJ9','any','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:44'),
(218,1,258,'*130','ruble_basha','eyJpdiI6Ikc1THhHQVlTdHN5ckFodFFHKzE0WWc9PSIsInZhbHVlIjoiWU8wSUpqK3JXRjVVYXExUHZIVU1KQT09IiwibWFjIjoiNTg5YzY5MjI5YmQ1ZDE4YTUwYjIzNzY3ODZlOGMxNzBjMjc2MGFkZGJjMmRlZDNkNGU2NTIzODgxNzkwZmRiZSIsInRhZyI6IiJ9','any','30 MB govt_college',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:28'),
(219,1,339,'*131','Wahiduzzama','eyJpdiI6IndCU1F6MmlIRFdxTE9Xdng3M2V4L3c9PSIsInZhbHVlIjoiWUVOR2dTVG4wTmszdmRqdC96d1FqUT09IiwibWFjIjoiZDQ2ZjllNGVhZTZiNmM5ODBlYmRlZGQ0NDkwZjg5NzEyMDc5YmQzMzc3YzI1YmUyYmQxOTQ1MWFiZjllZjkzNyIsInRhZyI6IiJ9','any','30 MB govt_college',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:44'),
(220,1,259,'*132','rina_court','eyJpdiI6InFyNXJ1ZkRRcERsM1pNUXg1UTkyQ0E9PSIsInZhbHVlIjoiZHA0cExhcm9uOUlsRjYrUTJMR2Q5QT09IiwibWFjIjoiZmUyOTcyMjkwMjY1MzAyMWVjOGZlNDE2MjkxMjYwMDFkM2RhOTY1NDMzOWMxMDEyMmFjOTE2YWFlMDhhNjA1YyIsInRhZyI6IiJ9','any','30 MB govt_college',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:28'),
(221,1,260,'*133','rumman_ basha','eyJpdiI6ImJxcmxYQ0IwSzB0UExlZHg0RmsxRkE9PSIsInZhbHVlIjoiQkR5eVU5dHd4bVJNYitWa1NYbmgvUT09IiwibWFjIjoiMjIyZTJlZmU0NTAwYzI0MmU3MjNmM2Y5NWIzMzVlOGFhMWU4ZGZhZDg1MmJhNGZiMTJmMzExZmU3ZjI1NzZmMSIsInRhZyI6IiJ9','any','30 MB govt_college',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:28'),
(222,1,64,'*134','hobyvai','eyJpdiI6ImpJa1Q2a1FFTU1xK000TjBFWW5PZnc9PSIsInZhbHVlIjoiMnhrdjkzbVBTam0ya2N2MFBBWlArUT09IiwibWFjIjoiMWU5MWI0Y2JiZmRiNzYwNjRiNmE0NTIxYjA4NGI2MjZlZDM2ZjFhMTJjM2FlODAwYTU4YjlhNjI5NzZlNzQ5YyIsInRhZyI6IiJ9','any','30 MB govt_college',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(223,1,340,'*135','twoha','eyJpdiI6IjVUbWRsUXVzRmZXYUNIMjhmWThUTWc9PSIsInZhbHVlIjoidlorYitKRUlvUUFGQXZRc3BoWXFhdz09IiwibWFjIjoiNmMzZDFkMDNmMjJkMzNkYTE3ODkwMjhjNGVlY2UyZWFkNjUwMzc3ZWQ0M2M4YmVkMmEyYWMwNzFhMTkwNGNjMyIsInRhZyI6IiJ9','pppoe','30 MB govt_college',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:44'),
(224,1,65,'*136','amin_mobile_salim','eyJpdiI6Inl3a1BKOHNXeDRFa0hhZVMyZUNjNGc9PSIsInZhbHVlIjoid25oSkJwaXMzUllJVGhOV0NoRlo2Zz09IiwibWFjIjoiOGFhNDJmYzZlYzJiZTUxYWRhYWY5YjMyMGZiYTkyZTY4NDM0N2U1MmFjMjgzZDFhMmFlYTZkZDU1Y2M2YTEwYyIsInRhZyI6IiJ9','pppoe','50 Mb_Travelshouse',NULL,NULL,0,'Anike',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(225,1,66,'*137','ful_sona_salim','eyJpdiI6IjJHWGZRTHBpWTVlcVBQdFBLWEVYdnc9PSIsInZhbHVlIjoiOU00dlN0eHR2akN6dEZQQXV3R3hPQT09IiwibWFjIjoiMGQzNjA4MDA3ZDNhYjk4YmIwYmE3NzMwMTA2YjE3YmZlYjZiMWIyYmRlODI0YTE2NzAyODRkMmRjMWExZTJiZiIsInRhZyI6IiJ9','pppoe','30 MB govt_college',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(226,1,261,'*138','shahin_shop','eyJpdiI6IjQrRXdrU1hkQmcveXRtYVlJYnRleVE9PSIsInZhbHVlIjoiMkUvZkRPZGtNZUR6VU1JdCtYU2NrUT09IiwibWFjIjoiMTBmZDZjNTkzZmFkYzgzZjU5ZmQzMzU1M2RjYjNjOWY2NGY4ODU3NGQwNTI3NWIxZmFlODUzZWMxODhkNjgzYSIsInRhZyI6IiJ9','pppoe','50 MB mosharof_bgoly',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:28'),
(227,1,67,'*139','ab_rokibul','eyJpdiI6IllSc1Y1dmF0TWEycTJGaThpUkh6UkE9PSIsInZhbHVlIjoiM2VSbGloNGdpV25Qa3V6a0V1MlRnUT09IiwibWFjIjoiNGQxZTc1MmU5N2VmZjU5MzQ5ZWRmMTkyZDBiMWM0NmJhZGNiYWFhOGVhZmVkODUyMTk0ZTA5Nzg3YTg1NjU0YSIsInRhZyI6IiJ9','any','30 MB govt_college',NULL,NULL,0,'Anike',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(228,1,68,'*13A','dokan_jaman','eyJpdiI6InFicWJHYVZjVjRyNmVsb0R1UXpyYlE9PSIsInZhbHVlIjoiMURJMUlIa05xQXNHeHN6cS9vQkNNUT09IiwibWFjIjoiYTM1NzVjMjBiYmFkMTJlYzFmMWViMTUyZGExNjUwNGI1Y2Q0YmVhZjg0YzU3YzVlZWU4M2YzYjViNWJjMDI5NSIsInRhZyI6IiJ9','any','30 MB govt_college',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(229,1,262,'*13B','salea_ator','eyJpdiI6IkE0d0luQ2NQaWVzTlBZelpVQlg4WEE9PSIsInZhbHVlIjoiUmRjUS8xZ051Q1YwTzYrbWpNS0ZSZz09IiwibWFjIjoiMzM0ZTBkNDc5MTI4ZjQ4ODhiNjEzYmExNDczZWE3NmVjZjNlNTFlMDI5Zjk3ZTc4Nzc2ODkxZjFmODc5MTlhNiIsInRhZyI6IiJ9','any','30 MB ZIlas',NULL,NULL,1,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:28'),
(230,1,69,'*13C','buro_home','eyJpdiI6IlRwZ0dBZlRBTUpEaUZOMWw2R0lCbGc9PSIsInZhbHVlIjoiaG8rVnBiL2orS09ycE1rb0JvUnZpUT09IiwibWFjIjoiYTU2MTIyOWYxMGE2MGYyZWEwMzI0OTAwNzNjZGVlNDQyOTkyZmZmY2U3Y2NkZWJkMTQ4MTgyMGY2YzQ5Y2VhMyIsInRhZyI6IiJ9','pppoe','30 MB govt_college',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(231,1,263,'*13D','shofiqks','eyJpdiI6IjQ2alBEcFljT3VYdVdNSHJlSE1Mb0E9PSIsInZhbHVlIjoiYmFIYU1ld2Q2SW5mODgvRHJFbERjdz09IiwibWFjIjoiNmQ4MTNlMWEzZjEyMzVkYzdmMmM2M2M3ZmI2NzY1MjliZjA5YTNjM2RkYTRlMzUxODM4ZDU1MjBjZDgzMjEyZSIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,1,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:28'),
(232,1,341,'*13E','sohailvai','eyJpdiI6ImdpcUxQeE9SV1h3aDN5MkxmVlhFMHc9PSIsInZhbHVlIjoiUHNLdldSeGptejg2MlJGV0xvcXhUdz09IiwibWFjIjoiYTE1M2E3OWRiYmRhYjE2MjNkNzRmOGZmYzJhYzczYjA2NTMwNmQwYzQ0OGFmN2EwNTY1MjkzN2ZlMmIxOWUyOSIsInRhZyI6IiJ9','pppoe','30 MB govt_college',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:44'),
(233,1,264,'*13F','salim_samiul_vi','eyJpdiI6IlYxb3FaV3grVWJDL05ucGVSdXh1L2c9PSIsInZhbHVlIjoiZnVmUHFjV1lEaXUvdnZIOXhhSjl5QT09IiwibWFjIjoiZTE0MDc2NjRlMjFmYzNmMTViZWJlNDkxZDk2MGYyOWJjNjcxNWVkYzc4Mzk4YWJlMWYyMGRjZTQ0ZmQ5MjY2ZSIsInRhZyI6IiJ9','pppoe','30 MB govt_college',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:28'),
(234,1,70,'*140','alomgirkst','eyJpdiI6InBod3hNTlUwQ1dML2ZYVm4xWUo1Ymc9PSIsInZhbHVlIjoiZ1JLRlJrQ20vbWN5Mkx0TUVkUFBPZz09IiwibWFjIjoiNTg2M2RmMGNlZTY2NzAzMThlZjU1OWNjMzU2ZTVjYTM4ZTI2N2Q3NGYyM2QwZWFlNjhlZTg5MGMxMTU3ZjMwNCIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(235,1,157,'*141','kistprincipal','eyJpdiI6IkxGQm9sL1E5ZHZsR1J4UlMxUXk4SUE9PSIsInZhbHVlIjoiRjVyM2dTRTF2QlB0czhZRDJERTFLdz09IiwibWFjIjoiZjExYWI0MjcwOWIwNTUzMTBmNzdjMmUwYzE5ZjQ1N2RkZWQyZGE5NGU4NGMyYzMyNzQzMzQ2YWIwNDdlNGNhNSIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(236,1,158,'*142','kobirtea','eyJpdiI6Ik54cVJURzg0MnhwSjJkYTdURUVpSHc9PSIsInZhbHVlIjoidjRFTmdFeERGUkJqSjlIcktaZTdJdz09IiwibWFjIjoiNTNlNTg1MWMzZjhhMzA4MmE1ZWM4MTIzYzJlNzM0YzMxNzIxN2M4MzRkMmQ5MjllODlhYTQxNmMxYzI3MDliYyIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(237,1,159,'*144','nayeemkst','eyJpdiI6IlJneCs2bk9KdGw1WUhmK2NoQWdGckE9PSIsInZhbHVlIjoiam0zc1lsVkxvUE5jaURSZjRxY2E5QT09IiwibWFjIjoiM2Q5MzQ0ODdiMmM4ZjMyMzlhYWY0NzEyYWIwMTY0NDJiMmNiMzhmOTljMWIwZTY2MzVkNTk4ZDliNTcwOGM0MyIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(238,1,265,'*145','sajibkst','eyJpdiI6IlBXRzhWMVQrZ3RERit5aWowOUFFWXc9PSIsInZhbHVlIjoidDg2czh1MUZNUWRVVWVFYkorNzFxQT09IiwibWFjIjoiNmVmZGVlZjdjYTkwY2M1YWM4ZjNiMmY0YzI1Y2VlMDk3ZjA0YTZkZGRjYzY1NWMxNzRlOTNkY2VjOTY5NzgyYiIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:28'),
(239,1,266,'*146','shamimpq','eyJpdiI6InI2Vm5OV3hkZE9NZHBidWtRU1hvdVE9PSIsInZhbHVlIjoiS0hrZkN0SzdpMjFiVDNYdnBhd291QT09IiwibWFjIjoiNzFkMGY0MjI2MjZmNDU0NDI2MjMxOTk4ZjFiNDMxNjJjOWJkODIxMTZiYjRkZGFhZTc5MTk2MGE5YzZhNWQ3OSIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:28'),
(240,1,342,'*147','wdbaminul','eyJpdiI6IkRVOGFjMlJuQ2NzTUxMMWJkV2JHYVE9PSIsInZhbHVlIjoiRE1WUUkzMlF3TkN1VmticE8wMi80QT09IiwibWFjIjoiOWJkOGE3N2FjZTcyNDU4YTEzODc4OTFmYzY1Yjk3MTE4ZDgxYjQ5MDMxMWMxYTYyZTAyMzU0MTkwNDljZjkwNiIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:44'),
(241,1,267,'*148','pwdacc','eyJpdiI6IkRCMmlkVlY2anJxbTN4d0FTRDFFQnc9PSIsInZhbHVlIjoiQXY3b09zVjAySFlKZ3VhTW0wM0hjQT09IiwibWFjIjoiYTI3ZjUxMjNlOTU5NzlkN2IxMmMzYzVhOTYzZTI4YzcwNmNmNTBiZjA3YjRhYTQ5NDA3NmFjOTQ4ZjdjMjI2ZCIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:28'),
(242,1,71,'*149','111','eyJpdiI6IkNvMldYRU9maTNMNkczcFRXbWx3VVE9PSIsInZhbHVlIjoicnZqQ0VWNnBJSjZIYWhxTW5HK3FoUT09IiwibWFjIjoiMjM3NWFmZjQ3NDA2OWRlNmViNDIzNWNmOThlMzIzZGFhZmFmNDIzYWI4Yjk0NGFhODMyOTFiZWRmOGUxOTcyYSIsInRhZyI6IiJ9','any','110 MB 141ranvid',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(243,1,72,'*14A','customs_rokon','eyJpdiI6InBReExWN2VpdEFEN3d6YzNyQlJkYUE9PSIsInZhbHVlIjoidlhUVEQ4NEtpaXV4aFY0Ryt2MzZOQT09IiwibWFjIjoiNTJiMTdmOTg1NjgwZjkzNDRmNTU1ZjA5M2Q3M2Q4YWVkYTJhNGU2Yzc0YzVjMTI1M2Q3M2Y5N2MxZDZiNzU5YSIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(244,1,268,'*14B','rantukb','eyJpdiI6IjhUdkRyYVZWU1VybkVmQjVsK1NKRHc9PSIsInZhbHVlIjoiUWhoU0J2S1Q1L0oyQkozelJxR2tGdz09IiwibWFjIjoiYzM0OTYxN2UwN2FiNWQ2MGM4MThkNTdiOTZiYzgzYTE4ZWNmYmMzMzUyODA3ZGQwN2M4YzQ5NjBiZmI5ZGY4NiIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:28'),
(245,1,269,'*14C','saifulstation','eyJpdiI6Ikh1eU9KRFpkWXZlRGU3RmFwdEN3V1E9PSIsInZhbHVlIjoicGYwbW95RklTelh5ZTFLU2lwUVNDdz09IiwibWFjIjoiYjgzMGU1YTVkMzYwODI3M2U2MmNhMjAzYTJjMWNhYWQwZTY0YjU5NWY0OGZmOWFlNzE1NTA3YjhmMzU1MjViMiIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:28'),
(246,1,270,'*14D','shahin_natural','eyJpdiI6InJSWGJUNTRoNmFia0lKQ3NOcUkwOFE9PSIsInZhbHVlIjoiQUVra1FZcGNVT1JHSTNlRTZkbTdQQT09IiwibWFjIjoiMTkyZjJjYmI1MDVmMGE0MTgwNzlmZTdjY2VhZjVkNzI5ZDE4MDBiNTJmNGRiZWUyOTcwZDNjZDI0NWNiY2E5OCIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:28'),
(247,1,271,'*151','rakibvai','eyJpdiI6IldlVGY2OVFJaFNNYjQ5SWtWc3Uzcmc9PSIsInZhbHVlIjoibGdHTlp6bXVZeVEvT1FpMlBoT1RlQT09IiwibWFjIjoiYTM1MzgyM2QxZDVjNmI1ZTM3ZjM3ODJkNzM0ZGU1ZDk4NGMwZGMzYmM2YWU1YjMzM2QwZWNjNjIyNjVlZjA0MiIsInRhZyI6IiJ9','pppoe','50 MB shena_nir',NULL,NULL,0,'Anike KPS Firoj',NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:28'),
(248,1,160,'*152','madani','eyJpdiI6IktGOVNwaXdNVmhLdlVEWWNxNDJFS0E9PSIsInZhbHVlIjoidjljZkJmZUh6bGM0Umd1cWl1VlIvUT09IiwibWFjIjoiNzFhNzFhNGUzOTU1YTQ4ZTBlYjA3MTI1NzcwYjRkZDc0NjkxZWQ4MGU4MTE5YjUwZmMzOTFjNmMzNTBkMmM2YSIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:55'),
(249,1,73,'*153','hajotkhana','eyJpdiI6IitSUGk5U1JGdCt6dXQ4TVJyM2ZZRmc9PSIsInZhbHVlIjoiWVpZNW92TDlQNWZ6TmtkMWdqbVM0Zz09IiwibWFjIjoiYzg3NGM0YzdjNTg5M2Y5ZjE3MGQ2NmUyOWE0YzY2MGQ1MGViMDNmOTI3ODVlYTgwOWE5YzBhZTBlNGFlZGVhNCIsInRhZyI6IiJ9','pppoe','50 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(250,1,272,'*154','refat_shena','eyJpdiI6IkR1dVE0cjJ2ekVrNVdSM29VZERzVHc9PSIsInZhbHVlIjoibkVlK3E3NWt6Zk02VjlSd2hnK28xQT09IiwibWFjIjoiMjBkNDE5NTdkMGNjMWJmNjUyMWRiZDdhZTZjNWQ1MGNlYTM2ZDg5N2IzYzllNjZkNWNmODBjNjc2YjE2MDc5YyIsInRhZyI6IiJ9','pppoe','30 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:28'),
(251,1,74,'*155','junnun_shena','eyJpdiI6IlZkOFl0cG1jaHdmVFg5RzZmOHFmNkE9PSIsInZhbHVlIjoiWjFITjVBd1hBUVQxSU42SFJNbWhXUT09IiwibWFjIjoiNzE2N2MyNGUzNmQ5MTU3MzVkZjEwOGQyNTMzMzg2YzM5YWMyMTI0MWUwMDBlZGEwNWRjZTA0ZjFlNzhhYzM4MCIsInRhZyI6IiJ9','pppoe','30 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(252,1,75,'*156','habibur_shena','eyJpdiI6IkRvMmRXdE84NjFiN25Oc1VROVc5Rnc9PSIsInZhbHVlIjoiTzlQNkNoVXE5bytmSS91VDVFNm01dz09IiwibWFjIjoiNDI3MzhhYmJjMmIwMDEyMDRkOTczZTlhMWZlZGIyNDdjM2ViMmNiNjY2MTAwNDdmMzY0MDJiODI5Mzc0NDhkOSIsInRhZyI6IiJ9','pppoe','30 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(253,1,76,'*157','hamim_shena','eyJpdiI6Inh2bjNUTXlqS0x2RFA4T3RvY2l4eGc9PSIsInZhbHVlIjoia2MzcjFPY0NsV2JhckZVV0xTRUd1Zz09IiwibWFjIjoiZjUzNTY1MzdiOTUwMmFhODMyNTI2OGVmMjkwYTI4ZThiNDVjNTc3NDNkMzAzMTI4OTA4NmRkMDZjMGI4MzY4MCIsInRhZyI6IiJ9','pppoe','30 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:18'),
(254,1,161,'*158','mohibul_shena','eyJpdiI6IlhmVkhEaVRzSERhZ1p3WlJ0ei82NGc9PSIsInZhbHVlIjoiZU8ydHd3bXVjVXl1MWdqTjNrK2lQdz09IiwibWFjIjoiNzgxMmJjNzVlYTY3OTllMWEzNmJlNjY4ZmQ4NmE0MTA3MDYwOTNjMTgzYjE5Mzc4NWQ1NzBiYzkxOGM3MTMzYSIsInRhZyI6IiJ9','pppoe','30 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:56'),
(255,1,162,'*159','major_shena','eyJpdiI6Inl0Q2NQcDBhVG0wRjlESmFkTGlKTWc9PSIsInZhbHVlIjoiLzM2SDV4RTRnY0dFM3U5Y3k3SlZNZz09IiwibWFjIjoiMzI5YmJkYTlhYzk1MWU3ZDA1MTY0ODY5NGJhNzQ2ZDI5MzM5NGM0Mjk3MDYwMjRiZjAwMjAzYmFmMmFiNzdhNyIsInRhZyI6IiJ9','pppoe','30 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:56'),
(256,1,273,'*15A','senanir','eyJpdiI6IlQ2aHByc2lKQ2lPVm5GWEVpMEc2SWc9PSIsInZhbHVlIjoic1QxRFRJM2RkdzFsYXRvWjNXbkxydz09IiwibWFjIjoiNTMyMGE4MzY5ZDZjZjIwZGUzZDQ4OTkxNzdiNDRkN2Q2YjA4M2NjNzk3NDA1NGU3ODczY2E5YTFlNzE5NTI2ZiIsInRhZyI6IiJ9','pppoe','30 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:09:28'),
(257,1,163,'*15B','mukto_anam','eyJpdiI6IlV5YWdVdUdOUzRpMDVXZVY1aEJ5UHc9PSIsInZhbHVlIjoiZGV4UGVyWE9vcnQ0YTNlcFVxNzl4QT09IiwibWFjIjoiZjI5NTMzNTQ4NTlmZDY3YjQ4NTFmMDc3NWU4ZGEzMWY5YjI3NTU3N2QxY2QxMzFiNzJiNWMzZGRjYWIwZmQ2OCIsInRhZyI6IiJ9','pppoe','30 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:15','2026-08-10 23:07:15','2026-08-10 23:08:56'),
(258,1,77,'*15C','joshimvai','eyJpdiI6InRCeStwRzRET1JHc2dIamlpN0dqWFE9PSIsInZhbHVlIjoiT3NiRnROZ0RSSWZDYXhYTktnYUJmQT09IiwibWFjIjoiYzJlMGZjZDg4NWQzM2MwNzA2MDQ0NDUzMDUwNDlhMzI1MThkODA4MmVmNjczMGQ3NTAwZDZjMDI5NTBmNzA4MiIsInRhZyI6IiJ9','pppoe','30 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:18'),
(259,1,274,'*15D','rima_seba','eyJpdiI6InF5SXA1enI1Y1FsOHYyMWVnYUZGUHc9PSIsInZhbHVlIjoiMkZEdDhWOVFJM20vbTI3UDJnNytzZz09IiwibWFjIjoiZDAwM2RkNTk1ZThlMDZhNzc5MzJkNGRjYTE3YmU0MDkwNDI0YjVmYjJlYjcwMDFjZmZiN2YxYzRmNzNjNDg2YiIsInRhZyI6IiJ9','pppoe','50 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:28'),
(260,1,275,'*15E','roich_mama','eyJpdiI6Im5QcVc3YTc2Y3VpY2MzR291NmI3ZVE9PSIsInZhbHVlIjoiT28zRVRjNFRYSFFSTW1zWmRGS2RZZz09IiwibWFjIjoiY2EyZTdiYjJlYmJlNDg2YTU3MDczNTIyNzcwZGUyZjcyNWU0MWFlY2FhMzliZTllNDM1ZGU1ZDQwMTJiNjA1YyIsInRhZyI6IiJ9','pppoe','30 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:28'),
(261,1,164,'*15F','parvezvai','eyJpdiI6IkxTeUFkVEQ2Y1BPVmptNkRKWDIzVFE9PSIsInZhbHVlIjoiNWVIaXloM0wvb1RsK1h2SHhEYStFUT09IiwibWFjIjoiZTI3NTdkNGJmZWIzZjg4ZmE4NzYzYTJhMDc5Y2U3ZDczY2I3M2NkMjljNzUxZDk5ZGVlNDFkYzk4NTQ3NTcxNSIsInRhZyI6IiJ9','pppoe','30 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(262,1,343,'*161','sparkit','eyJpdiI6Im45S0xZWjM4dm1jc3pXQWpDT1ZUT3c9PSIsInZhbHVlIjoiQTErb3I5a05nbzRUMndxK2w1cXFOQT09IiwibWFjIjoiZTcyNjlmOWUwMTFmNWY0MzhlOTY5MjUzODEwMGVlZmMyNmI5NzQ5OWJlZTJiYjY5Zjg0MjAzNjUyNmY0NWVlZCIsInRhZyI6IiJ9','pppoe','50 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:44'),
(263,1,165,'*162','medul_shena','eyJpdiI6IjFKRjBQWktaM2hqNEJWQ1ZvMmFKR0E9PSIsInZhbHVlIjoiNkZLTjdBMlZBVUxJUnVXcUJFYlVodz09IiwibWFjIjoiZjYzNDQ1NjRlMGZjODU4ZGRlN2Y5NGU0OTQwYTUzN2EyZjk5NzRiYTc3YWY2Y2M0MTUyYjM4YTUwYTIzZDMyOCIsInRhZyI6IiJ9','pppoe','30 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(264,1,78,'*163','alomgirwdb','eyJpdiI6IjQvbVZWKzFBS3JVOGJIQUQwdDcyMHc9PSIsInZhbHVlIjoiUVFKVDlUenBHU3Y5Slo5Q0VnbUZ4QT09IiwibWFjIjoiNWQ2YzFmMjIwYTQwMWViOWQ0OTkwYzcyNGZkNTEwMjY3ZGU2NTdiNmExODg5M2EzYjZhYjgyNzUwMzgxZDc2NSIsInRhZyI6IiJ9','pppoe','30 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:18'),
(265,1,166,'*164','lged_rezwan','eyJpdiI6Ik1vSHdLOXh0TnNTMU9hUjVPRHd6NHc9PSIsInZhbHVlIjoiS1d3THBnb2lnNnlOK2lCOFJLc2YvUT09IiwibWFjIjoiNTBlYzc2OGFkNWY2Y2QzODA0MDAzYTIyYzI4M2Q5ODdkNTEwZTJkYmRkMmRiZjVkMjEyMmE0NGJjZGMxNmI4NiIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(266,1,167,'*165','kpi_ct','eyJpdiI6IlozN2JLejhjWXpUT3EzOGozTlc1cWc9PSIsInZhbHVlIjoicHdBMUdLMlBpY2tSelZ4VEhLcDQwdz09IiwibWFjIjoiZDMzMzVlNTY0MjIzZjU2NzA1OTZlYzIxNzNhNTcxZGE4ODA2ZWQ2ZmY2NGFmMDc3NmE0MmZhZmI4MmY0NDlhMSIsInRhZyI6IiJ9','pppoe','50 MB KPI',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(267,1,168,'*166','kpi_etlab','eyJpdiI6IlVZNEJuSGV4S3dLbWVnVVdNMkxodFE9PSIsInZhbHVlIjoib3lDbXp1OXVsZWR6YUNMWkFneitpdz09IiwibWFjIjoiZjQyMjM3ZTk4NmViNDNjNDQ1OTlkYzM3NzM3NjU3MDNkOWIzYmQxMDVkZTJiYmVhOTgzYTVkMjEwZGE3NTQ1NyIsInRhZyI6IiJ9','pppoe','50 MB KPI',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(268,1,169,'*167','kpi_hvl','eyJpdiI6IjdrdDU3M29Gb0I3Z0I0L1lCUktjN3c9PSIsInZhbHVlIjoiVnNwKzR0Tmlzc1YvbmFSNk5RNW1iZz09IiwibWFjIjoiNDZjMDgyMzdlMmNlMTNjNTk2Mjk3ZDljN2I2MWQ0ZmQ1N2IyODRjZjNmMzJkYWQyODY2ZDgzMTdjMTdiOGZmMiIsInRhZyI6IiJ9','pppoe','50 MB KPI',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(269,1,170,'*168','kpi_fuellab','eyJpdiI6IjVUOFNzVmw2bHZHV0VLbW1waXczdFE9PSIsInZhbHVlIjoiN00xZ3ZlUVlCWXVpYXN6ZE91UTBnUT09IiwibWFjIjoiOGFhMmYxZDNjNTZhMmVlZGY2NmY1MDllMjcwN2MyZDMzNjUwZjhlMGZlYzRjMGU0NjgzMTAyZTdjYmNmZTU0OCIsInRhZyI6IiJ9','pppoe','50 MB KPI',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(270,1,171,'*169','kpi_asset','eyJpdiI6IlgrS094T05jS0FLVWR6OU5VWnorZkE9PSIsInZhbHVlIjoiTHZMQ1VKOXJXL0wvbUhkcDhXd2JYdz09IiwibWFjIjoiMjQ2YzI1ZjFiODBhOGI1YTc3Y2FmOWVhOTk5YWQ0ZGFlYWI3YTdlM2Q5MjFhMjE2MTc0ZjY0NGVlNWZkNDQxZCIsInRhZyI6IiJ9','pppoe','50 MB KPI',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(271,1,172,'*16A','kpi_2nd','eyJpdiI6InNnOHRJT1NFdnVUSUJXc0d6YjBpdnc9PSIsInZhbHVlIjoiZDAzWWc3am1ZaWhidnJZUVpCeFY5QT09IiwibWFjIjoiNmVmNTQ3YjgzNmNhY2Q1MTlkMzk2NjcwNTk3ZjhhODVmM2ExYTMwZWRiMWNhYTY0Y2Y2ZGFmMTY3MDI0YWE4ZiIsInRhZyI6IiJ9','pppoe','50 MB KPI',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(272,1,173,'*16B','kpi_confa','eyJpdiI6Im12ZXpQQU9kczNPU0dTeit0UVZJb3c9PSIsInZhbHVlIjoiaGMwOWtqM0x4TTJBZ2dHZ1kzVWw5QT09IiwibWFjIjoiNmIxNzU4ZGMyMDQxZGViMmZlYTA4MWZhOTE4Y2E3OTU3ZTRhYjdlZTc3MWI1YjcyMTUzMmJkYmNiN2RhMDA2MSIsInRhZyI6IiJ9','pppoe','50 MB KPI',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(273,1,174,'*16C','kpi_testing_lab','eyJpdiI6InZaUnpsWFRzbGxJc2JZbzRkVGY1dlE9PSIsInZhbHVlIjoicC9mSG5tOVFLMmFVYTlxWmtHTWRWQT09IiwibWFjIjoiOTJkOTBhYTRiNDllYmJjNjQ2ZWM5N2I2MWNjZTk1YTAyZmUzNzcxMGUwMjJkZmVkMjFlYmE2NDJmZjA2MDdiOSIsInRhZyI6IiJ9','pppoe','50 MB KPI',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(274,1,175,'*16D','kpi_ent','eyJpdiI6IkovREg3UHJXa2RDRkhNTXgwR1dDbWc9PSIsInZhbHVlIjoiaXlWbGFvd2FqQ3VUR1NTTTE1cEVXUT09IiwibWFjIjoiZDY5ZjdjMGI2OTE3OGU1ZDZkNmRjNmQ4NDcxZTJiNjg1MzI3YjAzMTNjYWJiNGNjN2U3NjliNWRlNGI0MjE3OCIsInRhZyI6IiJ9','pppoe','50 MB KPI',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(275,1,176,'*16F','kpi_acc','eyJpdiI6IndHOFZqbjNkSHd0bVJNYWwwbmpYelE9PSIsInZhbHVlIjoiRWtJMzZZLzVEWlc0TGFHUzJNNU43QT09IiwibWFjIjoiNTRlYmE5MjQ2N2E3MjZiZGRmY2JmY2JjMmJiMTJkOTcyOTdhNzkwOTViNTRjMjMwM2YyYjQ4MmVjNDA5NDY3ZCIsInRhZyI6IiJ9','pppoe','50 MB KPI',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(276,1,177,'*170','kpi_control','eyJpdiI6Imc3aDRVU1VyUlErc3ZpNW1LWm00K0E9PSIsInZhbHVlIjoiVmlRVTVVaG1PbU1Jb3RZTVBVV3dMZz09IiwibWFjIjoiNTdiODIzMTQzN2Q3ZWQ5MjY1Zjg4MmYwYTk1ZGM2MGQ4ZWI3ZjkxN2RkY2IwODZlMGNjN2EwOWVmNzIzNTcyOSIsInRhZyI6IiJ9','pppoe','50 MB KPI',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(277,1,178,'*171','kpi_elcdept','eyJpdiI6IjE2V0JDVWZldWZkZi9OK3djQmozQnc9PSIsInZhbHVlIjoiMVZLR2c0RXhiWHdmYTV5ak5WUm5zQT09IiwibWFjIjoiYmQ3MzAzM2IzOWMxN2Q2NmI0NDIzZDVlNzc3N2JiZWY3NTZiNTQzYjc3ZGMzYmIxZThiYWFkNzQyYmMyNmYzMCIsInRhZyI6IiJ9','pppoe','50 MB KPI',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(278,1,344,'*172','wdbxen','eyJpdiI6Ik4vNEcvMzBVanpEclZXc2NYRkJEWXc9PSIsInZhbHVlIjoiM3pxekp0cmI1cEdwVTU0aVhBdWJJUT09IiwibWFjIjoiMzRjNDgzN2ExN2QwYmI5ZGY3N2EyZTg5Zjg3MmIwYTcxYjJlZmFmYmI0YTc0MGYwNTQ4NjRhM2RkNWE3OGZlMyIsInRhZyI6IiJ9','pppoe','30 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:44'),
(279,1,276,'*173','shanto_saddam','eyJpdiI6IkpEeWc3eUFsam5NczdnNlZmNHh3MFE9PSIsInZhbHVlIjoicG83bjBNcEdSNlBXdlRvc1ZHQ3g5dz09IiwibWFjIjoiMWNlMjExOWEwMWViNDZiNjgwNGU5ZWVmYjNlOGFlYTI2ZDk3MzZkZjdhZGFlNzNlNDY1YmJmMWU2YWRjZGE0OCIsInRhZyI6IiJ9','pppoe','30 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:28'),
(280,1,277,'*174','ruhan_anam','eyJpdiI6IndJbTJTVit4S2hPQXcxVkorUWpkVWc9PSIsInZhbHVlIjoidTRPVW1uaFJBNVJFSStRNXhzT2hoQT09IiwibWFjIjoiZTM0MDAxZjVmNDEzNWQyZGUwNmFhMjYyNTk5OWJiMjI1ZjIxYmQyOGMyZWRmM2I4YjZlYzkzYWViNGMxMGIzOCIsInRhZyI6IiJ9','pppoe','30 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:28'),
(281,1,278,'*176','shopon_goshala','eyJpdiI6IlhQeHFCNU02WnBnWm5WQ1F0L2lhVXc9PSIsInZhbHVlIjoiYUdzTDNFbjlBQ254Ny8rQk90VG8xQT09IiwibWFjIjoiNTMyOGJmMTk2ZTJkMWFhMjMyNWQxY2QwNDRiZThlZDJjNTkyNTVhODA1ZjEzNWZmMGUzMTgxZjcyYTM1ZTRjZCIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:28'),
(282,1,279,'*178','police_hospital_lab','eyJpdiI6Imd2RE96THlNUUtSdFdvZGw5TjB0c2c9PSIsInZhbHVlIjoibDlDQ3Q3TkgwS1ZWR2ZSNFR1M1BwUT09IiwibWFjIjoiMjdlNzAxMTU0NzZkYjBiMmQ1MjhkNjdhYjYwZGFmMTA4YjRkMjM0Y2M5YzMxMzNjOWQzYmNlMGQwNmVmMGI1NCIsInRhZyI6IiJ9','any','50 Mb_Travelshouse',NULL,NULL,0,'Police 21_4_26 @ 600 2 month advance from arshed',NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:28'),
(283,1,345,'*179','subirdada','eyJpdiI6IjZWd3Nxd29yRHhENUYvTmJhSjE1c2c9PSIsInZhbHVlIjoiVjlLaWk0R3g5ZS8xcHQyYmZkaTI5Zz09IiwibWFjIjoiZTgxODA1YTk5NzdlOWZmNzY2OTQwZGY2MjUyYjhiMTJmNmNiOTljODRjMGMxZDAwMDgyNTlmMjI0MDA4NzRhZSIsInRhZyI6IiJ9','pppoe','30 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:44'),
(284,1,280,'*17A','shamol_dada','eyJpdiI6Im9Ec1A1Q1NicUVZaUtEWS8xN1BOM0E9PSIsInZhbHVlIjoiRFYrRjZTemxYWDg1WnFvZkJXbmdHZz09IiwibWFjIjoiNmEyOGEyMDJjNmEzNzgwZjBkZWY5NzgzYThhNjcyNWM1NDcyYTA2MTIxODc3NWEyY2VmMGJhYmVkM2U0ODQ0NyIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:28'),
(285,1,179,'*17B','mamunvai','eyJpdiI6ImhJSWhtODdtTzg4YmhiMEFPUEVXS3c9PSIsInZhbHVlIjoiZVFsRGZMaFdlUW51V3N5cEFJRVZaUT09IiwibWFjIjoiODllNTMxYTZmNTNjMmZhZmNiNTQ1N2NkMDUyNzBlZTRjMGI2ZGMwZjc3ZjViNTQyN2E1YTZlZDVlZDVlY2MxMyIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(286,1,180,'*17C','kpi_zisansir','eyJpdiI6IlRqSzVMRTRTMlM5TG95QTJPeENqYWc9PSIsInZhbHVlIjoiQU9hUE5IK3EwQnU4dVBaZTFnc1d1UT09IiwibWFjIjoiNzMyMzQxNWM1NDM5NzViZTJjN2MyMzJiYzViNWI5ZjZlYTVhYzdmYTM5ZDcyNGM0ZDI4ZGUwOGU2YmUyOGU3YiIsInRhZyI6IiJ9','pppoe','50 MB mosharof_bgoly',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(287,1,181,'*17F','mamun','eyJpdiI6IlJrRVRRVXlMVzkzcDNyM1UyMzg5ckE9PSIsInZhbHVlIjoiRC9DbStpKytDcWVSN1hxQzBwMG5uUT09IiwibWFjIjoiYzA3MTJkOTY2Y2ZkMzExYTg2MjdmYjI5MWQ5NGZhM2E4Mjc1ZWE4MDljNzBlOWU1ZjlkZmFiNjMxMWJhYzdkNCIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,'Munna Barir pase',NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(288,1,79,'*180','CID25058','eyJpdiI6InFId2l4Q0w3UVBKaEZuUDNJUGdFMEE9PSIsInZhbHVlIjoiK21lNlJhaUllRlFnVjFxTWtWUG1qUT09IiwibWFjIjoiNmIwODQ5NzU2NjYwMjkyYTNkZjkyNTRkZTBjNTVhYmFlZTFmNmY1ZTJiOWU1YTAwMDNjZjBiMDRkMmEyY2EzOCIsInRhZyI6IiJ9','pppoe','30 Mb_Travelshouse',NULL,NULL,0,'Shofiq',NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:18'),
(289,1,80,'*181','fine_fit','eyJpdiI6ImtyUU1JdlBlbHZ6NkptZkEwcFl0ZGc9PSIsInZhbHVlIjoieEc1TXNUcWpKNHp0VkpERk12SWo3dz09IiwibWFjIjoiOWE1OWVhN2M4NDkzYjMxY2FhNjkzMzA0NTVkOTA0ZDBhZTM0YmI0ODhmNDY2Zjc4NDkyNDQzODgzNWFiODBlMSIsInRhZyI6IiJ9','any','110 MB 141ranvid',NULL,NULL,1,'Shofiq',NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:18'),
(290,1,281,'*182','rana_ns','eyJpdiI6ImIvRjFFakxTNC9XOVhzQXhNVXB1QlE9PSIsInZhbHVlIjoic1dHVkRtaDJ5VFZwelNEcHljWEwrUT09IiwibWFjIjoiMmU2YzZiMzAzN2NlMDBhZjNjMWJhNDJjMDNmMzkzOTBlYzFiYWVlNGQ0NmRhZmJiNmE4NTQxMGIxYTJiOGEzNiIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:28'),
(291,1,182,'*183','mintu_sir','eyJpdiI6ImxqSEZTR1YyeS9WSTEzTHVUZ0Y4NGc9PSIsInZhbHVlIjoiSVVGZ1M0d2I3bDRVSDU4M3pRcmFRQT09IiwibWFjIjoiMWZlYzBjODc1Y2Y2NDkxYzFhOTkyNGNlNGIyNGJmYzI0MWEzYzgyNWYxYmFiMjllMTg3MjRkYjY3NTk1ZjNlOCIsInRhZyI6IiJ9','pppoe','30 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(292,1,183,'*184','moriom_anam','eyJpdiI6Inh3MkhpVG1GZ042NjRsRzl3MkRkbFE9PSIsInZhbHVlIjoiMFdpSEJwRWw4NFBzZnlpVTA2VUU2dz09IiwibWFjIjoiNWJlZWZmMmUwMDM4ZGFmNzJlMTRiNGE3N2Q3ODdhZDg0M2YzMGExOWZmOGQzMmNkNTk0M2YyNmVkMDhhNTE1MyIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(293,1,346,'*185','tipu_vai','eyJpdiI6IlpOeXdRZlQ0Vy9iYzZ2OWcxdXpCNHc9PSIsInZhbHVlIjoid01WWUw5RVFmakN0T3hpYXNxK1o4Zz09IiwibWFjIjoiZDk3NjBkODhiZmQ5NTYzMWM0OGYxY2I2NjIwYTFlOTc1ZGJhNWYzN2MyZmNiZjY0ZDY0MDMyNWQ0MDhiNDJhYyIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:45'),
(294,1,282,'*186','rabbi_coffe','eyJpdiI6Ik94UlBJR0RaeHpwZ3BCQlBmOUhMVmc9PSIsInZhbHVlIjoiNDdIRVlCM0tVYUdQRzRPd1BCWVVmQT09IiwibWFjIjoiOTc5MmZkMjNlZjNhNTI4ZjIyOTkwNDRhZmI5YWVkMWQyZWM5NGNmNTRjNGVhYWI3MjZlMTFjNzBiYTg3NWNkMSIsInRhZyI6IiJ9','pppoe','30 MB KPI',NULL,NULL,1,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:28'),
(295,1,81,'*187','boro_amirul','eyJpdiI6IlRNTnRrdHJCZ1BsQjNaOUFDakRUeXc9PSIsInZhbHVlIjoiMktZSE10aWhTcUNzZ2JJMHZ0T2V3Zz09IiwibWFjIjoiNzg0NGZjZWQ3MGMxZDkyOGRmM2JiMTg4OWUwNWVhNmFjNmE2NTI1YzkyM2Y1OWJjZjFkNjQ4ZGRkNzk4NGEzZSIsInRhZyI6IiJ9','any','110 MB 141ranvid',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:18'),
(296,1,82,'*188','almahdi_trd','eyJpdiI6Ikk4NHMxVTF6czN5MkR2Zlp2WUs0OUE9PSIsInZhbHVlIjoicjdLMU5kM05LcGNtYnJUa3NqYUdiQT09IiwibWFjIjoiZmVhMGQ3YWZlOThiYjc3NjM5ZDMyYWEwM2VhZTk4MzJiYzlkMTQ0ZTY2YTRhNjExNzAwNmMwYmE0YjQ1ZjMxMyIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:18'),
(297,1,283,'*189','shovon_vai','eyJpdiI6ImZDTmgybExqREFFN3h2cWZpc3Nod2c9PSIsInZhbHVlIjoiSHI5Q2FNZVdSZUt1WmtqSnlWa2RuUT09IiwibWFjIjoiNjUwM2I5MjI1M2UzM2M4NThhMGFhOTQwMDg5NGFjNjE1NDYzYzIwMGZkZjA1NDQ4MjQxYTM0YmNkMmJhZTE0NSIsInRhZyI6IiJ9','pppoe','30 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:28'),
(298,1,184,'*18A','lged_guest','eyJpdiI6IjUzeGtTQzNwZGlEWWI1dm85cjhzUVE9PSIsInZhbHVlIjoieWt1NGZEVDRWcVIydFdsNGdkb2ZJdz09IiwibWFjIjoiNDM1MGQ4NzFmMGMxMDM1MTFiZmE1M2FkZTQ4NmJiYWI5MmE5M2IzYTQzZjZlN2MwNzUzZTBjYjhiMzJiNmZmMiIsInRhZyI6IiJ9','pppoe','50 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(299,1,83,'*18B','jony_oly_vi','eyJpdiI6IjN0NS84bEFVQS9WM0dkUmsvNTEyQVE9PSIsInZhbHVlIjoib1ZPT09ZSW9XVWc2OXgyV3dyTjJlQT09IiwibWFjIjoiMmYwNjI5ZTAxYzMxNjIyMjk1MDUyNDA4MTk2Yzk4NWM4MzkyOGUzYjU1Mjc5ZWI0NTQyY2YyNTg1MWNkODg3ZiIsInRhZyI6IiJ9','any','50 Mb_Travelshouse',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:18'),
(300,1,284,'*18C','riaz_shop','eyJpdiI6IkU4QkZaSk04d29VMDlxUWdaSkE5Q1E9PSIsInZhbHVlIjoiTXJid2VRTDZqMnJGdjJqbUZWVnYzdz09IiwibWFjIjoiM2FhMDI2MjIxMTQyYzJiN2NkMTM0MjIyMWU3ZGFhYTllZmY4MDc4MTYyMmMzMWEzOTQyNzhiOWRhMTgxN2M4NSIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:28'),
(301,1,347,'*18D','ss_brrirs171','eyJpdiI6ImhtcEVxWkNCSDZQVUlhU1QweUxlMHc9PSIsInZhbHVlIjoiUkJMUFFTZVVCVEs2ZVAxdXBlUmQzUT09IiwibWFjIjoiMGQ3OWUzYTQwYWZiNzM1Njk4YjFjYzg0MTVkMTQ3MTk3YmIxNTBmMGRlZDgyYzM0NjVmYTZmOWEyZjJmZjM2NCIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:45'),
(302,1,348,'*18E','tanisha_home','eyJpdiI6ImszZzBiQzR1bXFubkU0Ui9FY2FLNGc9PSIsInZhbHVlIjoicThpL3JBcGJUNWJRNEswT2dsUmxtdz09IiwibWFjIjoiYjI4ZTBkZmNlNjdhNTI2NDliNWY0ODg5ZTY2YTA5YTY0ZmUwNGM3MzdmZjBmY2FmM2VkY2IzYjkxYjhjNDQ0MSIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:45'),
(303,1,349,'*18F','tuku_kutu_vi','eyJpdiI6IkhQbWpBaWZBYTlvSG91by9pUEo3aVE9PSIsInZhbHVlIjoiTy8yMVVoOGZVcUpkTUkzYTluN3owQT09IiwibWFjIjoiMzNjYWVhMWU4NTQxMDMzYmNmZTFhODllNmM0OTJlYjY2YTFiNTc1OGQxNjM0NmQ4OTZhM2M0NWVmMmEzYzUxZCIsInRhZyI6IiJ9','any','50 Mb_Travelshouse',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:45'),
(304,1,84,'*192','jim_amirul_vi','eyJpdiI6IkRKRGgvZzBIbHMweHBzbSswQzBYNEE9PSIsInZhbHVlIjoiZUtiUmIzcVJTZHo2LytxU0JkbXR0Zz09IiwibWFjIjoiZDExZmFlOTM5OTYyZmRkZTA0Zjc5NWU2NGNiZjI0NTQ3OTkyNmY4OGNhMTNjN2RkZDhiNGU2YTQzZDBlMGYyOCIsInRhZyI6IiJ9','any','50 MB KPI',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:18'),
(305,1,285,'*193','shomobay_bank','eyJpdiI6ImtVNFd4UXNBd09ZSi9QSE5zQ043b2c9PSIsInZhbHVlIjoiMFFMUGpFTllPUFJXUnJxSUhxTWJOZz09IiwibWFjIjoiOWMyZjZmNWJmODY3NWU0NDVlOGQ1ZTYwNzZjOTVhMGFkN2JkNzc4NDE1YWVhODlmMjI4OTE0Yjc3M2VkMDNiNCIsInRhZyI6IiJ9','pppoe','50 MB shena_nir',NULL,NULL,0,'shomobay_bank',NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:28'),
(306,1,85,'*194','anupomcomputer','eyJpdiI6IlRWa3FNd2ZGcGlmT2Z6T1pZMW0wckE9PSIsInZhbHVlIjoicUVpUW8vSVhSa2RTUUtqRVBUQXlLdz09IiwibWFjIjoiMDIzZjg5OGVlNzM4NTZkYzBmZjI3MGVmM2NiYjg1YTU3MWEzYzlkZTgwYmVjMmY1MjlmZjZkN2Q2ZjFlYmFiMCIsInRhZyI6IiJ9','pppoe','50 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:18'),
(307,1,350,'*195','wdb','eyJpdiI6Ii8rektyVG03eThLMFVYV2J1TmlEVUE9PSIsInZhbHVlIjoiakxwQ3lMbEhwWjNFaytCQjVOQ1loQT09IiwibWFjIjoiZmFiMWZhOTRiOGY3Yzg3NzE4ZjllYWUzZTY0NGIzNzVjNjJmNjU3NzJmNjY1ZDlmMzg4NTNiYzY3NzQ2ZDg1MyIsInRhZyI6IiJ9','pppoe','100Mb_kpi_comdpt',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:45'),
(308,1,86,'*196','dhrubotara','eyJpdiI6IjFJWWNvd0tNdTV4NXN0bnFINlA2c1E9PSIsInZhbHVlIjoiU1RpRk5OMDBGNll5V3A5UGFieXZZQT09IiwibWFjIjoiNTVjN2NhZmY2YmViMGEzZTJlN2VjNTU0Nzc5OGMyYTVlM2U2ZmZkOTFkMmEzMGQzNzRiZDBmN2Y0ZWU1MmYxMyIsInRhZyI6IiJ9','pppoe','30 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:18'),
(309,1,185,'*197','nondita_alomgir','eyJpdiI6IlJCMU5CVHh4OUtOQmoyaDQ4aCtPK3c9PSIsInZhbHVlIjoidk1IaFNwMjNtdzVSS0Z1R0lSbDdmUT09IiwibWFjIjoiM2ZjN2VkNjVlYzBjNjJjNmZiMzgzNzJiZDkyOTJlZWU4MDgzMmE5YjEzNzFlZTk1MzY4MGY5ZjViOTNjZDFiYSIsInRhZyI6IiJ9','any','100 ZillaS',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(310,1,186,'*198','kpi_physicslab','eyJpdiI6ImU2cFJyQ3l0NkZ1QUVnZy9VdXRFa1E9PSIsInZhbHVlIjoiTmdLYXBOVjRVS3pXRHV4dFZsOTV3Zz09IiwibWFjIjoiZDg1NzkzZDYwOTFiZWVjMjM4OTBhNDM1YjNiNzdmZmIyMmJlZWY0NTM0MGEzODhiNDM0MjUwOTk5NzVjN2JiNCIsInRhZyI6IiJ9','pppoe','100Mb_kpi_comdpt',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(311,1,351,'*199','sohel_vai','eyJpdiI6IjZzWUVjSTQwUStxa2dmM2JITkhhWGc9PSIsInZhbHVlIjoiczcxdVBBL3hNWTF6bzk3bVBWR3VZUT09IiwibWFjIjoiOGY3MjJkYjdlMTMwN2YzYWY0OTUxNWJlMDhhYWNjMDU2ZjJiYzFkNTBjNTY1ODZmYjMwMmFhYTM1YWQ4YjUyYSIsInRhZyI6IiJ9','pppoe','50 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:45'),
(312,1,87,'*19A','bortoman_bari','eyJpdiI6Im04V3RFeVltYUNGQjVKSlV2NENBRmc9PSIsInZhbHVlIjoicWMyNFRBVGVFY0o1TUVGUCsxek9yZz09IiwibWFjIjoiZWMyM2NiZWU5MWJiYzE4ZTcwNDc3ZDlmOWEwZTEyZTkwMjM1MTU3MjJjZmY3M2MxYmE2YjVlYWU2Y2UxMjIyMyIsInRhZyI6IiJ9','any','30 MB govt_college',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:18'),
(313,1,352,'*19B','taslima','eyJpdiI6ImJ3NDVCYVd3bjNQSnhwcWRDbG9uUGc9PSIsInZhbHVlIjoiVVhFUkg2ek1sSHpBUDZ1bHRPRWlsUT09IiwibWFjIjoiMmZlNTQ0NWNjYjVlMjE1MTg4ZTk0NGE4Njc1NWNkNTQxYzJjYTVhMmY3MTgzMTE5OTMxOWVlODU3MWQyYWYyYyIsInRhZyI6IiJ9','pppoe','30 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:45'),
(314,1,187,'*19C','monirul_goshala','eyJpdiI6Ii9oWVYxT3ZuREk3Zi9ncmdQMUVKaGc9PSIsInZhbHVlIjoibCthR0dnNEIzY3Budm4yK25STnJGUT09IiwibWFjIjoiYTk3ODI3NWI2ODQ1MjA5YzBhNTQ4YWMxN2YwYTQ0N2Q0ZGI0NDgyNTJlZTg3ZmU4NWIzZWJjNTEyMGY1OWNkYyIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(315,1,188,'*19D','moinul_goshala','eyJpdiI6Ik5TWmZINkpkUGJIcEUwQ25GY3Jia3c9PSIsInZhbHVlIjoibFFTcDBiS0w0QXRpTHg3bEhHYUFMdz09IiwibWFjIjoiMjg5MThkYzNlNjFlMTA1YTNhOTYwMDE1YTRkZjQ4M2M2ZDU0MDJhMjZhMmZjODM4Y2VmZTQ5MjJlYTA0OTU2MyIsInRhZyI6IiJ9','pppoe','30 MB Saifulkst',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(316,1,353,'*19E','wdb_khalid','eyJpdiI6IlAvbmJSdmd1NmJNSmt0YkZRVkcyVmc9PSIsInZhbHVlIjoiVzRFNkNYMzRtN3FkNHBlZ1hWcjM3Zz09IiwibWFjIjoiMzFiZDA1NDM5ZGUwZTBiYjkwMDdkZDViNzI1MzhjZTc4ZTdlNWM2YmVmZGEzOTc5ZjgyNzhkOGJlZGYyYjBhZCIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:45'),
(317,1,354,'*19F','tofazzol_ripon_dada','eyJpdiI6InM2b0xJU2NlWE1CQTdOSFRaOGhYaUE9PSIsInZhbHVlIjoiZ3FJRnhDb1FXS1FXU3VyOW1LQ0h1Zz09IiwibWFjIjoiNWFiMWQ2MTQxM2IwNDA3ZTllNTE5MTg2YTJlZWNjNWMwOGVjMzFkNTk1NWY0YjM0MWJmMjczNmFkZGU1NWY4MSIsInRhZyI6IiJ9','any','100 ZillaS',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:45'),
(318,1,88,'*1A0','getcopop','eyJpdiI6ImhBRjQ3VXl1VFZ5VG5lREVyRENOVkE9PSIsInZhbHVlIjoibzNCQTZTTG5uMi9qUElqb1JYMlN1dz09IiwibWFjIjoiMWRiYzgyODFjZjNiNzE1NWUxNDZmNGMyYjBmYTM1OGM0ZTNlYzllZTQ0YzI5OGE5YzY4N2I2M2Y4YTk5ZWQ4MSIsInRhZyI6IiJ9','pppoe','50 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:18'),
(319,1,189,'*1A1','kgc_saifulsir','eyJpdiI6InRjZ0JBNTAwSkRFN3BRVUR2ck55cFE9PSIsInZhbHVlIjoiVHRKTXNxYjZ5aFJUclJ1c21jTTRQUT09IiwibWFjIjoiOWFlOTFkZjYyMmZiZjYzNWI4YWEyYmIzMjI4YzU0ZjJhOTRiZTQxOTlhNTg3ZTEzNTA1NTVhMGZhYzRkYmJkMCIsInRhZyI6IiJ9','pppoe','50 MB mosharof_bgoly',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(320,1,89,'*1A2','bisuvai','eyJpdiI6Iml0bWx1SDlRcmtUSk5VTldneTZ3eWc9PSIsInZhbHVlIjoiRDVQQllQNENDd2JkaEF1S3g1cU05Zz09IiwibWFjIjoiNTlmMjEzZTk5NTVlY2UxYTFjYzg1NTVlZWMzYTkzODJkNTgyZjcwZWM4MjVjZTdkNGZmZDA5NWRhYjgzYzE3YSIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:19'),
(321,1,355,'*1A3','sumanbg','eyJpdiI6InhDK2lmQThiYlBoVVBTNUtVYlNGV3c9PSIsInZhbHVlIjoiSmpVaTB3d1Y0TVBOR2VGREJDbDZhZz09IiwibWFjIjoiM2UyNzNiZWIxZjMwYmEwOGZkZWFkNjZlY2U4NzQ1NGYxYjJmYjRmZjFiNGZlMjUwNTBkNTRkMGY4MTgwODZjZSIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:45'),
(322,1,286,'*1A4','ridoybg','eyJpdiI6InRmSXBuNm5WNDNtcXF0c0hhSXpXUWc9PSIsInZhbHVlIjoiSzEwK3o2UCtKbjg3RWhPdEwrZTVnZz09IiwibWFjIjoiN2YwOGI1NjhmYjQzZjQwYjE3MThjYjQwNmYzMzFmN2M0ZjRjNmFmODFmMWY2ZTNiNjk2MmIzNmY5MDE5N2Q3OCIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:28'),
(323,1,90,'*1A5','imranber','eyJpdiI6InJrR2xtdy9kekNkWHFEOU1WQ2d0SkE9PSIsInZhbHVlIjoiNEoxQ2hJZml6eW5XU0I3Yk1pUWcwdz09IiwibWFjIjoiMTJmZWQ3ZTQwOTY0YmEzOWY1OTQ0YzYzZmI4NjYxZTU4OWIxYTJiNzM4ZDAxYTIwZGFlNTUwYWNjNTMyMWJjOCIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:19'),
(324,1,190,'*1A6','musharof.bgoly','eyJpdiI6ImhLMVFJUzM0M2k5YUwrTHdWVG5aRXc9PSIsInZhbHVlIjoiSGEya1FudTlBSUFNYWpOenhiM1BrQT09IiwibWFjIjoiNzIxMjgxOThkNGJkMThjZWMzZTJmZmNiZjFmZWNhNjk2ODk4ODhiYjQxZWRiNjlkMzM2Njg2ODllOTNmNTcyNyIsInRhZyI6IiJ9','pppoe','50 MB mosharof_bgoly',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(325,1,91,'*1AA','borhan_bermij','eyJpdiI6Im14WFA4cTBuOGI4c2VGR2RrRjhPTUE9PSIsInZhbHVlIjoiWlBoNGN6WUp4SDNuenMrK2hmNXlxQT09IiwibWFjIjoiNDhkOGM0MGIzNzk4Nzk2OTQ5NmQ2ZTQyZTE5YzU0ZWVmMTM5ZDVlMmNkYWMyMzJjOGFiMTg5OGZhYWE1ZjllYiIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:19'),
(326,1,92,'*1AB','jayanta_berj','eyJpdiI6IkRDbERKUUd3SjJsS2cvMFozeUtZRmc9PSIsInZhbHVlIjoib2haR1BJeHpkVlNONm10QzBKRHRvZz09IiwibWFjIjoiMWU2YTQ3ZmU4ZDBlOWFiNjlhNDMxZjU5NTk2MjkwMGM5YmViZTBjZTczYjc4YzQ3ODk5MDI0ZTE5OTJiNjNkMCIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:19'),
(327,1,93,'*1AC','bobita_basha','eyJpdiI6IkhXZzdqczFpL05FNUtZenROYzFrYnc9PSIsInZhbHVlIjoiVDBEQlcrQ0ZHN2RCeWNmay9FZ2k3QT09IiwibWFjIjoiYjNmOTYyNThlYzFlZTI1OGYzZDRjODNhNTE5NGZlZjNlNmQ4ZGY0N2E3MzFlZDI2ODFkODgxYzhkMTRkZjFkNCIsInRhZyI6IiJ9','any','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:19'),
(328,1,287,'*1AD','ripa_bermij','eyJpdiI6InJqY0tzZEkyc0haU0Q5cnVZd2tkSmc9PSIsInZhbHVlIjoiOHZIVFMvWXNuRHI2N1pqY1VBQ29RQT09IiwibWFjIjoiOGVkY2FiMzllODg3MDZmNjJiNTU0Njk1ZTE0M2Y4MTQ4Yzc3YTVjMGZiZGVkNTk0NGZmMGMyNGRlZWRkOTNmOCIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:28'),
(329,1,191,'*1AE','kochi_bridge','eyJpdiI6IjkrMnNLSEVTOEZpK1ZHZThpRzhsSVE9PSIsInZhbHVlIjoiZzRPcFNiaS9xSWltSkRiTWczbng2UT09IiwibWFjIjoiYjkzMjE2ZjY5NTU4NDAxOTBhOTBjNDVhM2Y2MTU4NzZiYjkxNGIwZmE3ZjE4OWQ5NzBmYmM2Mjk3ZDY3ZGQ5MyIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(330,1,94,'*1AF','aktervai_kb','eyJpdiI6ImFTbzhBREJ6RDVLaGpwQjdYQ2FLNVE9PSIsInZhbHVlIjoiWnFFdjkvQXQwVFdFNU5GcU1RZTRvZz09IiwibWFjIjoiZWU5ZThkMGFhN2YxNDdiZWNhY2Y3NWY5N2JjMGY0YTU5YTUxMjVlYWNjMmIwZTEwNTQ1MzFiMTgwMDI3NWUyMiIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:19'),
(331,1,288,'*1B0','rakibst','eyJpdiI6IlprUWx6eGszQkxRTkExcXRnU1FrNnc9PSIsInZhbHVlIjoiUE9nOWg0UDZUQ1c3MUVibVB5VFJ1dz09IiwibWFjIjoiNWMyYTZkZWE5ZmE0MzdhOGE4YmYzNWE5MjNhYWU2ZjkyY2VlMTdjZDIwZTJjOTBmNDdlMWY2MjEwMDY0ZDNiOSIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:28'),
(332,1,356,'*1B1','toyemob','eyJpdiI6IlFDWVJDbWlXRVdMU2lVOUcvSUxDdXc9PSIsInZhbHVlIjoiNE45MTJVeVFZUDZpdHlwNzF6N0o1QT09IiwibWFjIjoiNTdiNzJmOTk0MzEyM2YyMmYzY2RmYWJmYjc1MmRmZTk1OWQyMjUwYmQ2OGMwYjJhZjRkY2Y3ZTc1Nzg3Y2Y1OCIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:45'),
(333,1,57,'*1B2','babul_fyt','eyJpdiI6Ikp5RG5EUVFXeWd3eTNtY2pUaEdvZXc9PSIsInZhbHVlIjoiWVJ6QnlTblIzSTJobzVHbUFjZ05iUT09IiwibWFjIjoiYTYwZTQxNmMwODk3YWZkZGVhYWNiZGI1MGU3OWQ4N2EyOWU2YjZlNmIxOGEwMWJjNzcyYmFlMmY5NWU0ZDUxZSIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:19'),
(334,1,289,'*1B3','police_saheb_vi','eyJpdiI6IjVqd3RYUUdxVDBUN1VacFA5TUVLWEE9PSIsInZhbHVlIjoiOXE3ckw1elNSNkZ0UmlxN0hndU9Vdz09IiwibWFjIjoiMDBkMTEwM2Q1YWJkYjU3M2ZjMWYxMTAwYjU3NGY0Y2I0M2RkMzM1YTZlMGFiM2FkMGMyOGYxNThjNDgxMGVkYyIsInRhZyI6IiJ9','pppoe','50 MB mosharof_bgoly',NULL,NULL,0,'Anike',NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:28'),
(335,1,290,'*1B4','raselvai_kushtiab','eyJpdiI6Ik4wZTZzMi80cE9ZTkZPUGdaMm1XZ1E9PSIsInZhbHVlIjoiSStROHhTQkUrWFNYVW4zekYvVzhTUT09IiwibWFjIjoiYmQ3Y2RmOGMxYjUyNTg0MjE4M2M5YTY3MzFhNGI0OTUwN2Y5NTg5Mjg4MGU0N2MxMzEzOWJlMDA5NDQ0NjY2YiIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:28'),
(336,1,291,'*1B5','raka_farmacy','eyJpdiI6ImJnckJNTFBiRkszclJFMDBNQnZ4T1E9PSIsInZhbHVlIjoiN3BlT1cySGFWanBrYnJWdWJlblQwdz09IiwibWFjIjoiNjU3MmJjOGU0ZjBkNDQwMTMwZmEyNDU3YzE3ZjZjOTkzMjc5OWQxYTIwYTVkNDYxMDYzN2RhYjdmYjUwNDFkYSIsInRhZyI6IiJ9','pppoe','30 MB Saifulkst',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:28'),
(337,1,95,'*1B6','joy_bridge','eyJpdiI6IjlhZndWMTlwMnRRVlBheXBpeExiOXc9PSIsInZhbHVlIjoiaWRRM1ZOb3J4UTJ6d25VTlRlUmViQT09IiwibWFjIjoiZjNlZGNmMGJmYzE3ODBkZTZlOTY1NzQyM2MyNTNkZGMwZWQ2M2I2NDFlZDQzY2Q5YTczYzE5MzU3Zjc3NWQ1MiIsInRhZyI6IiJ9','pppoe','50 MB mosharof_bgoly',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:19'),
(338,1,96,'*1B7','jony_bridge','eyJpdiI6IkVYQmpNaUtwZmhkajczTEp3enB4S0E9PSIsInZhbHVlIjoibmp4UDlqRU8zRU9JVWEzY2ZEaVk4dz09IiwibWFjIjoiNmM1YWI1MzRhNzgwNDExZGMzOWUwNWQ3MDFkNzM1N2ZhNzk5NmFiNTBiMzRlNDg0NTlhM2ZkOGNmODM0OGVmNyIsInRhZyI6IiJ9','pppoe','50 MB mosharof_bgoly',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:19'),
(339,1,192,'*1B8','kibria_bridge','eyJpdiI6ImFkTHQyKzMycEhiU0xLcWlwUiswNWc9PSIsInZhbHVlIjoiNmQ4TzVCRnQ0c3NFNE5DYXVFMmNrdz09IiwibWFjIjoiN2I1NDFjNDRiZDNkODYzMDUyZWIzODM2MzE5ZTA5M2ZiMGIzYjU0NjkwYWZjZDQxY2RhNjMxODg1M2Q1MWU5YiIsInRhZyI6IiJ9','pppoe','50 MB mosharof_bgoly',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(340,1,193,'*1B9','pappu_bridge','eyJpdiI6InJvOTluSDEyNlZ6ekFwVStORGNKUVE9PSIsInZhbHVlIjoiWGZNZit3UmNGbzNkb1hsWVJQbXFoZz09IiwibWFjIjoiZWIyMzZjNTA5ODBkMTdkM2EwMGQyMmM1MDQ1ZWU1Y2M2Y2Y3MmEzMDAxZDBjMjk4ZTViNmNhNmRkNjIxZTg3OCIsInRhZyI6IiJ9','pppoe','50 MB mosharof_bgoly',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(341,1,194,'*1BA','muid_addin','eyJpdiI6IjYxcllpN3oyWFUxUFdMcm84NEdYbmc9PSIsInZhbHVlIjoiMm1CMW0rRVdqTkRIY1o2ZUw2TDBlQT09IiwibWFjIjoiNzU4OGRiMmNmNmExMGYzZTk1YWVmMmVkOWU1ZmRlZGFmYmYxMzYzNGM4OTlhN2VkZDllNjBmOGVjOWVlOGMzNSIsInRhZyI6IiJ9','pppoe','50 MB mosharof_bgoly',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(342,1,195,'*1BB','mustak_shop','eyJpdiI6IkhNMjVaNzRSRDJMSXdxSmRyMGFiVWc9PSIsInZhbHVlIjoiUk1keDFiZEYydlVvMENZQzlwRkloUT09IiwibWFjIjoiNjYyYzdlMDdiYzJkNjM3YTExZjQ1M2MwYjE5NTBlODA3NGEwODQyYWMyYTQzODdjYjJmYjIzNDUyNzM0NDQ0MiIsInRhZyI6IiJ9','pppoe','50 MB mosharof_bgoly',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(343,1,292,'*1BC','shomik_datta_ns','eyJpdiI6Ik5yZy9aWktnRnBBN3JxSGVzTC9YYUE9PSIsInZhbHVlIjoiK0FnUDFzd2pSYXIxVlpIQUY4NmR1Zz09IiwibWFjIjoiMTljOGJjM2Y5MGE1ZGVlZTQxZTQxYWEwY2FmMmNmZjJmYjRmYjQyZTA0OGU1MTM4MzAzYWQ2NjEwMzhmNTA5YSIsInRhZyI6IiJ9','pppoe','50 MB mosharof_bgoly',NULL,NULL,0,'01859304704',NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:28'),
(344,1,97,'*1BD','abhamid','eyJpdiI6InJRa2d0QVgwb3NTS1JmYjM5S1dJK2c9PSIsInZhbHVlIjoiSXptdTdRcUFOYXFBcGxHQ2RFZTlHZz09IiwibWFjIjoiNWJkMTg4Njk5MmI2ZGQwMmZlYjQ4MTExYWYwZjMyZTE3ODdkZWRiNWVhNTI2MmY1NzBmMzExOGZjNmQ2ZjBlZiIsInRhZyI6IiJ9','pppoe','50 MB mosharof_bgoly',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:19'),
(345,1,293,'*1BE','shorf_optic','eyJpdiI6Ild5ZjVrWjJFY1ZIQW1TTGRhOWMrckE9PSIsInZhbHVlIjoiaW9pUU5FUDRQSVM5WDBEQmttQWZQZz09IiwibWFjIjoiN2Q0MTRkOTdkOWVkZGFiNWI5ZTlhZjQ1YTM2NDExNmVhNGNmYzVlZmU2MjI5MTgwNmNjMjk2ZTIyNmNkMGI1OCIsInRhZyI6IiJ9','pppoe','50 MB mosharof_bgoly',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:28'),
(346,1,294,'*1BF','salauddin_itech','eyJpdiI6Imx5R3B2aTZvTzkzQkRER2ZrTDNZVXc9PSIsInZhbHVlIjoiTndUR3JMUG10U3lGemNaODJoMEtyZz09IiwibWFjIjoiNjJiOTZhODI2NzhlZDgzZTk5M2NmZTA5ZTJlZGJiZmE0ZTkyNDE1NzdiZTZiODlmNzJjNjQ4OGE4ZWQ4ZGI2YiIsInRhZyI6IiJ9','pppoe','50 MB mosharof_bgoly',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:28'),
(347,1,295,'*1C0','rana_basha','eyJpdiI6InJmczMyazgvUWZYT0tNTjRtczdxVkE9PSIsInZhbHVlIjoiS0M4Yi83aGRrbXNUUC9CVTJaNjU0UT09IiwibWFjIjoiYzkyNTdhOTgxNGY1YWY5MWU1M2FkOTczNjIzYmE4NzFmMjZlM2Q5MGVjYTM3YzlkZTVmZjVkOTZjMjVjODgxYiIsInRhZyI6IiJ9','pppoe','50 MB mosharof_bgoly',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:28'),
(348,1,296,'*1C1','rodna_onkur','eyJpdiI6ImpDNkhUQkhWbUxoZUV3dThGOEF6NUE9PSIsInZhbHVlIjoiaENDeWk0dDI4bXBhdFhQMHhJcURRZz09IiwibWFjIjoiMmFlNTkwOTUxYTIxZGRjMzY5OWQ5YjMyMmViNDIzZTEwZTJkYTRkNDgzYjc1NTBhMjQ1MmJhYjJjN2NmYmI0MiIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:28'),
(349,1,98,'*1C2','jahidvai_basha','eyJpdiI6IlNoUnRmZ0J6SmtENTQrbHdobnNxY0E9PSIsInZhbHVlIjoiL1dOU3Y0UEVQaXZhb2NwSk9obEJaZz09IiwibWFjIjoiMTdhZDRmNGU1Mjk5NmVmOTNmNDNmNmRjNjc0YWQ1NDhhMTk5NGY1YjcyNGIxZGZjN2FlZWFiMTk5NjZjMjFjMSIsInRhZyI6IiJ9','pppoe','30 MB ZIlas',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:19'),
(350,1,NULL,'*1C3','ttcpkb','eyJpdiI6Inc1S1o4bW0rbU9QRW1UdDVHQi8vMVE9PSIsInZhbHVlIjoiLzdpTFVCd2dBTHpGUEtsS1lPbEJpUT09IiwibWFjIjoiZGYyYTkwYThiM2UwNDgwMDlhMDFkMTQ1MzQwZjZkZjBlMzA3MmRkYjc3YmIyOTQwM2RlMGEzMjc0NjQwZTA2NyIsInRhZyI6IiJ9','pppoe','50 MB mosharof_bgoly',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:07:16'),
(351,1,196,'*1C4','mow_fa','eyJpdiI6IlBWT2JUL1hIeUVWQWd0NlBZVml2RUE9PSIsInZhbHVlIjoiOWZlVnRQc29XdEk2akQzcUo3YkhUdz09IiwibWFjIjoiNDllY2Q5NDMwMTgyYWM3OWUxMmY5NjJhOWQ4ZTdiMzVkZDRiMTUwMzUyODg3MGMxMWE4YzcyNmQ0YWEwNjJlNSIsInRhZyI6IiJ9','any','50 MB mosharof_bgoly',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(352,1,297,'*1C5','public_imran','eyJpdiI6InU3RFpCaVlsb1RUbUR6Uy9NeHlzeWc9PSIsInZhbHVlIjoiRnI1UE16VVdTR1FpK0djakRXUnBwZz09IiwibWFjIjoiNjU3YmVmZDFkYTMwOGZkMmVkY2Y0MmNjNjQyOTk0Njc3ZjkwYWEyMzVmZWEzZmE5ZTlmYWFiNWM5Y2M0NDQ4NiIsInRhZyI6IiJ9','any','30 MB shena_nir',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:28'),
(353,1,197,'*1C6','mazu_moszid','eyJpdiI6IkpoNmk5b21qUHVYdWhmNFI5SjlDbEE9PSIsInZhbHVlIjoib0xmcm45amFucUdTTUhCc01TZFRXZz09IiwibWFjIjoiNjBkMjBjYjQyZjJjMDI1OTBkYjQzNjc0ZTk1ZmRhMzgzYWJiZDdiMDFmY2JmY2ZhM2IzN2FiYzA2MTMxYjUzNyIsInRhZyI6IiJ9','pppoe','30 MB Lgedks',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(354,1,198,'*1C7','nickber_rail','eyJpdiI6InUxLzEzWnlmSklaTk0zQ2RKTGtCOVE9PSIsInZhbHVlIjoiN2x4YzM0R292V2daTGRrc3lzekxGdz09IiwibWFjIjoiN2I1M2YwM2QyYTQ2OGRhOTRkYzNkODhhOWE2YWUwNDA3NzgzMmI1ZWZmNWVhNDFlZTUwYWU2YWY0ZWUyZDVjYyIsInRhZyI6IiJ9','pppoe','50 MB mosharof_bgoly',NULL,NULL,0,'01720604218',NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(355,1,298,'*1C8','salea_ator_home','eyJpdiI6ImZlbWZaWFVLWkVZT0VUMFFZN1c2a3c9PSIsInZhbHVlIjoiWUdTem4zaDlZK3VScW5oMmxpaXVlQT09IiwibWFjIjoiMzFmNDI0MGUxODcxMDUzNzJkNDU5ZjNhY2Q0YjlmZmE0ZTJiYWRhZGY3YWE5Mzg0NmU5YzIyYjE4YjZjMzZkNiIsInRhZyI6IiJ9','any','110 MB 141ranvid',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:28'),
(356,1,199,'*1C9','masum_advocate','eyJpdiI6IkRrdzNQMXFCYzRpdVJrenAzRTN0MEE9PSIsInZhbHVlIjoiVGp1N2NSa0JnTUF3OVVSWDQwaXBzUT09IiwibWFjIjoiNzI0NmFlY2M2M2QwZDUzM2JkMWY3ZjVkOTllYzIwMTA0OGMzYTY0NjQ4YTAyOWMwMjAzYmZkY2E3Zjg0MDZiMCIsInRhZyI6IiJ9','pppoe','50 MB mosharof_bgoly',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:56'),
(357,1,299,'*1CA','shakil_custom','eyJpdiI6InloYmVqWjNEUjhEK240RU5oMjZFenc9PSIsInZhbHVlIjoiSERSWTYrRVo2U1FOWU9tamNVOVRPdz09IiwibWFjIjoiMDQ0YmUwZTM0YzAzYmU3Yjc4OWQ2Yjg2Yjg2MWRlMzM0N2QwOTU4YWNmZjQ1OGI0NjhiMjFkOWFlZGEyYmIxMCIsInRhZyI6IiJ9','any','100Mb_kpi_comdpt',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:09:29'),
(358,1,99,'*1CB','alamin_basha','eyJpdiI6IlRjYmxPQ0hLRC94eDlBV0VVYVJIemc9PSIsInZhbHVlIjoiSis4SU1tK3JVN1diYVlwU2ZXVnZQQT09IiwibWFjIjoiYmNkYTFmYTRmNTNmZDdjZDJiNTI0MGIwNDZhMDQ1ZWY2ZWI1YjQxZjE4NjRhM2NlNDFmNGFhMDllZjRiZDdkNCIsInRhZyI6IiJ9','pppoe','50 MB mosharof_bgoly',NULL,NULL,0,NULL,NULL,'2026-08-10 23:07:16','2026-08-10 23:07:16','2026-08-10 23:08:19');
/*!40000 ALTER TABLE `mikrotik_imported_secrets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mikrotik_routers`
--

DROP TABLE IF EXISTS `mikrotik_routers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mikrotik_routers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `ip_address` varchar(255) NOT NULL,
  `api_port` smallint(5) unsigned NOT NULL DEFAULT 8728,
  `pppoe_sync_interval_minutes` int(10) unsigned NOT NULL DEFAULT 60,
  `username` varchar(255) NOT NULL,
  `password` text NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `last_api_status` varchar(255) DEFAULT NULL,
  `api_status_since` timestamp NULL DEFAULT NULL,
  `last_ping_status` varchar(255) DEFAULT NULL,
  `ping_status_since` timestamp NULL DEFAULT NULL,
  `last_api_latency_ms` int(10) unsigned DEFAULT NULL,
  `last_ping_latency_ms` int(10) unsigned DEFAULT NULL,
  `last_checked_at` timestamp NULL DEFAULT NULL,
  `last_online_at` timestamp NULL DEFAULT NULL,
  `last_offline_at` timestamp NULL DEFAULT NULL,
  `last_ping_at` timestamp NULL DEFAULT NULL,
  `last_connection_message` text DEFAULT NULL,
  `last_pppoe_sync_at` timestamp NULL DEFAULT NULL,
  `inactive_pppoe_profile` varchar(255) NOT NULL DEFAULT 'inactive',
  `last_pppoe_sync_summary` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mikrotik_routers_ip_address_unique` (`ip_address`),
  KEY `mikrotik_routers_entry_by_index` (`entry_by`),
  KEY `mikrotik_routers_entry_by_type_index` (`entry_by_type`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mikrotik_routers`
--

LOCK TABLES `mikrotik_routers` WRITE;
/*!40000 ALTER TABLE `mikrotik_routers` DISABLE KEYS */;
INSERT INTO `mikrotik_routers` VALUES
(1,'2','user','1036 MikroTik','103.133.200.180',8787,60,'admin','eyJpdiI6IkdXNkNqSVJNUTN6SG5xWmxBTURnTHc9PSIsInZhbHVlIjoibC95Q3BidTBOYXZ1RFdEcmkza0lTUT09IiwibWFjIjoiNzkwMzBhM2E5ZDgwNGEyZGNlNzE2ZWI1N2JjNjVlNGQzZWU3NzUwNjkzMTk2ZDcxNTNjNmYxYmJlNDNjN2VkMiIsInRhZyI6IiJ9','active','online','2026-08-10 23:05:51','online','2026-08-10 23:05:51',306,59,'2026-08-12 19:32:49','2026-08-10 23:05:51',NULL,'2026-08-10 23:05:51','Login successful: RouterOS accepted the saved username \'admin\'.',NULL,'inactive',NULL,NULL,'2026-08-10 23:05:51','2026-08-12 19:32:49');
/*!40000 ALTER TABLE `mikrotik_routers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `network_map_features`
--

DROP TABLE IF EXISTS `network_map_features`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `network_map_features` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` bigint(20) unsigned DEFAULT NULL,
  `feature_uuid` char(36) NOT NULL,
  `feature_type` varchar(20) NOT NULL,
  `component_type` varchar(30) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`properties`)),
  `geometry` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`geometry`)),
  `latitude` decimal(11,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `length_meters` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `network_map_features_feature_uuid_unique` (`feature_uuid`),
  KEY `network_map_features_entry_by_foreign` (`entry_by`),
  KEY `network_map_features_feature_type_component_type_index` (`feature_type`,`component_type`),
  KEY `network_map_features_feature_type_index` (`feature_type`),
  KEY `network_map_features_component_type_index` (`component_type`),
  KEY `network_map_features_name_index` (`name`),
  KEY `network_map_features_latitude_index` (`latitude`),
  KEY `network_map_features_longitude_index` (`longitude`),
  KEY `network_map_features_length_meters_index` (`length_meters`),
  CONSTRAINT `network_map_features_entry_by_foreign` FOREIGN KEY (`entry_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `network_map_features`
--

LOCK TABLES `network_map_features` WRITE;
/*!40000 ALTER TABLE `network_map_features` DISABLE KEYS */;
/*!40000 ALTER TABLE `network_map_features` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `olt_devices`
--

DROP TABLE IF EXISTS `olt_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `olt_devices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `brand` varchar(255) NOT NULL DEFAULT 'HSGQ',
  `protocol_profile` varchar(255) NOT NULL DEFAULT 'hsgq_epon',
  `host` varchar(255) NOT NULL,
  `access_method` varchar(255) NOT NULL DEFAULT 'ssh',
  `port` smallint(5) unsigned NOT NULL DEFAULT 22,
  `username` varchar(255) NOT NULL,
  `password` text NOT NULL,
  `enable_password` text DEFAULT NULL,
  `snmp_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `snmp_version` varchar(8) NOT NULL DEFAULT '2c',
  `snmp_port` smallint(5) unsigned NOT NULL DEFAULT 161,
  `snmp_community` text DEFAULT NULL,
  `snmp_timeout_ms` int(10) unsigned NOT NULL DEFAULT 800,
  `snmp_retries` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `snmp_status_oid_template` varchar(255) DEFAULT NULL,
  `snmp_power_oid_template` varchar(255) DEFAULT NULL,
  `snmp_power_divisor` decimal(8,2) NOT NULL DEFAULT 1.00,
  `read_context_commands` text DEFAULT NULL,
  `pon_ports` varchar(255) NOT NULL DEFAULT '1,2,3,4,5,6,7,8',
  `onu_status_command` varchar(255) NOT NULL DEFAULT 'show onu-info all',
  `onu_power_command` varchar(255) NOT NULL DEFAULT 'show optical-info',
  `onu_alarm_command` varchar(255) DEFAULT NULL,
  `onu_vlan_command` varchar(255) DEFAULT NULL,
  `onu_mac_command` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'unknown',
  `last_polled_at` timestamp NULL DEFAULT NULL,
  `last_error` text DEFAULT NULL,
  `last_raw_output` longtext DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `olt_devices_entry_by_index` (`entry_by`),
  KEY `olt_devices_status_index` (`status`),
  KEY `olt_devices_access_method_index` (`access_method`),
  KEY `olt_devices_protocol_profile_index` (`protocol_profile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `olt_devices`
--

LOCK TABLES `olt_devices` WRITE;
/*!40000 ALTER TABLE `olt_devices` DISABLE KEYS */;
/*!40000 ALTER TABLE `olt_devices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `olt_onus`
--

DROP TABLE IF EXISTS `olt_onus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `olt_onus` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `olt_device_id` bigint(20) unsigned DEFAULT NULL,
  `olt_name` varchar(255) DEFAULT NULL,
  `pon_port` tinyint(3) unsigned NOT NULL,
  `onu_id` smallint(5) unsigned NOT NULL,
  `mac_address` varchar(32) DEFAULT NULL,
  `onu_type` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `note` text DEFAULT NULL,
  `parent_splitter` smallint(5) unsigned DEFAULT NULL,
  `port_vlans` longtext DEFAULT NULL CHECK (json_valid(`port_vlans`)),
  `port_admin_states` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`port_admin_states`)),
  `ethernet_port_count` tinyint(3) unsigned DEFAULT NULL,
  `learned_macs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`learned_macs`)),
  `rx_power_dbm` decimal(6,2) DEFAULT NULL,
  `power_note` varchar(255) DEFAULT NULL,
  `distance_m` int(10) unsigned DEFAULT NULL,
  `raw_bind_config` text DEFAULT NULL,
  `raw_interface_config` text DEFAULT NULL,
  `raw_live_output` longtext DEFAULT NULL,
  `last_backup_at` timestamp NULL DEFAULT NULL,
  `last_live_polled_at` timestamp NULL DEFAULT NULL,
  `last_registered_at` timestamp NULL DEFAULT NULL,
  `last_deregistered_at` timestamp NULL DEFAULT NULL,
  `last_deregister_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `olt_onus_olt_device_id_pon_port_onu_id_unique` (`olt_device_id`,`pon_port`,`onu_id`),
  KEY `olt_onus_entry_by_index` (`entry_by`),
  KEY `olt_onus_olt_name_index` (`olt_name`),
  KEY `olt_onus_mac_address_index` (`mac_address`),
  KEY `olt_onus_name_index` (`name`),
  KEY `olt_onus_rx_power_dbm_index` (`rx_power_dbm`),
  KEY `olt_onus_last_backup_at_index` (`last_backup_at`),
  KEY `olt_onus_status_index` (`status`),
  KEY `olt_onus_last_live_polled_at_index` (`last_live_polled_at`),
  KEY `olt_onus_last_registered_at_index` (`last_registered_at`),
  KEY `olt_onus_last_deregistered_at_index` (`last_deregistered_at`),
  CONSTRAINT `olt_onus_olt_device_id_foreign` FOREIGN KEY (`olt_device_id`) REFERENCES `olt_devices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `olt_onus`
--

LOCK TABLES `olt_onus` WRITE;
/*!40000 ALTER TABLE `olt_onus` DISABLE KEYS */;
/*!40000 ALTER TABLE `olt_onus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `olt_protocol_profiles`
--

DROP TABLE IF EXISTS `olt_protocol_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `olt_protocol_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `brand` varchar(255) DEFAULT NULL,
  `pon_interface_command` varchar(255) NOT NULL DEFAULT 'interface epon {pon_port}',
  `onu_context_command` varchar(255) DEFAULT NULL,
  `supports_vlan_polling` tinyint(1) NOT NULL DEFAULT 0,
  `supports_mac_polling` tinyint(1) NOT NULL DEFAULT 0,
  `default_read_context_commands` text DEFAULT NULL,
  `default_onu_status_command` varchar(255) DEFAULT NULL,
  `default_onu_power_command` varchar(255) DEFAULT NULL,
  `default_onu_alarm_command` varchar(255) DEFAULT NULL,
  `default_onu_vlan_command` varchar(255) DEFAULT NULL,
  `default_onu_mac_command` varchar(255) DEFAULT NULL,
  `vlan_write_context_command` varchar(255) DEFAULT NULL,
  `vlan_write_command` varchar(255) DEFAULT NULL,
  `port_admin_context_command` varchar(255) DEFAULT NULL,
  `port_admin_command` varchar(255) DEFAULT NULL,
  `save_config_command` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `olt_protocol_profiles_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `olt_protocol_profiles`
--

LOCK TABLES `olt_protocol_profiles` WRITE;
/*!40000 ALTER TABLE `olt_protocol_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `olt_protocol_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `olt_refresh_runs`
--

DROP TABLE IF EXISTS `olt_refresh_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `olt_refresh_runs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `olt_device_id` bigint(20) unsigned DEFAULT NULL,
  `olt_name` varchar(255) NOT NULL,
  `refresh_mode` varchar(255) NOT NULL DEFAULT 'full_mac',
  `pon_port` tinyint(3) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'queued',
  `progress` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `message` varchar(255) DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `olt_refresh_runs_olt_device_id_foreign` (`olt_device_id`),
  KEY `olt_refresh_runs_status_index` (`status`),
  CONSTRAINT `olt_refresh_runs_olt_device_id_foreign` FOREIGN KEY (`olt_device_id`) REFERENCES `olt_devices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `olt_refresh_runs`
--

LOCK TABLES `olt_refresh_runs` WRITE;
/*!40000 ALTER TABLE `olt_refresh_runs` DISABLE KEYS */;
/*!40000 ALTER TABLE `olt_refresh_runs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `organizations`
--

DROP TABLE IF EXISTS `organizations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `organizations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `mobile` varchar(100) DEFAULT NULL,
  `phone` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `tax_id` varchar(100) DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `footer_note` text DEFAULT NULL,
  `default_without_signature` tinyint(1) NOT NULL DEFAULT 0,
  `show_organization_selector` tinyint(1) NOT NULL DEFAULT 1,
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_account_name` varchar(255) DEFAULT NULL,
  `bank_account_number` varchar(100) DEFAULT NULL,
  `bank_branch` varchar(255) DEFAULT NULL,
  `bank_routing_number` varchar(100) DEFAULT NULL,
  `show_bank_info_on_invoice` tinyint(1) NOT NULL DEFAULT 0,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `organizations_is_default_index` (`is_default`),
  KEY `organizations_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `organizations`
--

LOCK TABLES `organizations` WRITE;
/*!40000 ALTER TABLE `organizations` DISABLE KEYS */;
/*!40000 ALTER TABLE `organizations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_accounts`
--

DROP TABLE IF EXISTS `payment_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `payment_method` varchar(255) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `account_number` varchar(255) NOT NULL,
  `opening_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_accounts_payment_method_account_number_unique` (`payment_method`,`account_number`),
  KEY `payment_accounts_entry_by_index` (`entry_by`),
  KEY `payment_accounts_entry_by_type_index` (`entry_by_type`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_accounts`
--

LOCK TABLES `payment_accounts` WRITE;
/*!40000 ALTER TABLE `payment_accounts` DISABLE KEYS */;
INSERT INTO `payment_accounts` VALUES
(1,'5','user','bkash','Shofiqul Bkash','01798987928',0.00,'active','2026-08-12 21:45:40','2026-08-12 21:46:10'),
(2,'Shofiq M','sms_device','bkash','Shofiq M','sms-device:shofiq-m',0.00,'active','2026-08-12 23:05:48','2026-08-12 23:05:48');
/*!40000 ALTER TABLE `payment_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_allocations`
--

DROP TABLE IF EXISTS `payment_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_allocations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `funded_by_customer_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `payment_id` bigint(20) unsigned DEFAULT NULL,
  `source_type` varchar(255) NOT NULL DEFAULT 'payment',
  `operation_key` char(36) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `allocated_at` date NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_allocations_operation_key_unique` (`operation_key`),
  KEY `payment_allocations_customer_id_foreign` (`customer_id`),
  KEY `payment_allocations_invoice_id_foreign` (`invoice_id`),
  KEY `payment_allocations_payment_id_foreign` (`payment_id`),
  KEY `payment_allocations_entry_by_index` (`entry_by`),
  KEY `payment_allocations_entry_by_type_index` (`entry_by_type`),
  KEY `payment_allocations_funded_by_customer_id_foreign` (`funded_by_customer_id`),
  CONSTRAINT `payment_allocations_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payment_allocations_funded_by_customer_id_foreign` FOREIGN KEY (`funded_by_customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payment_allocations_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payment_allocations_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_allocations`
--

LOCK TABLES `payment_allocations` WRITE;
/*!40000 ALTER TABLE `payment_allocations` DISABLE KEYS */;
INSERT INTO `payment_allocations` VALUES
(1,'2','user',348,NULL,2,NULL,'advance',NULL,500.00,'2026-08-11','Automatic renewal from advance balance for remembered package.','2026-08-11 20:18:28','2026-08-11 20:18:28'),
(2,'2','user',348,NULL,3,NULL,'advance',NULL,500.00,'2026-08-11','Automatic renewal from advance balance for remembered package.','2026-08-11 20:18:28','2026-08-11 20:18:28'),
(3,'2','user',348,NULL,4,NULL,'advance',NULL,500.00,'2026-08-11','Automatic renewal from advance balance for remembered package.','2026-08-11 20:18:28','2026-08-11 20:18:28'),
(4,'2','user',348,NULL,5,NULL,'advance',NULL,500.00,'2026-08-11','Automatic renewal from advance balance for remembered package.','2026-08-11 20:18:28','2026-08-11 20:18:28'),
(5,'2','user',348,NULL,6,NULL,'advance',NULL,500.00,'2026-08-11','Automatic renewal from advance balance for remembered package.','2026-08-11 20:18:28','2026-08-11 20:18:28'),
(6,'2','user',348,NULL,7,1,'payment',NULL,500.00,'2026-08-11',NULL,'2026-08-11 20:21:36','2026-08-11 20:21:36'),
(7,'2','user',348,NULL,8,2,'payment',NULL,500.00,'2026-08-12',NULL,'2026-08-12 21:15:58','2026-08-12 21:15:58'),
(8,'5','user',321,NULL,9,3,'payment',NULL,500.00,'2026-08-12',NULL,'2026-08-12 21:47:09','2026-08-12 21:47:09'),
(9,'5','user',321,NULL,10,NULL,'advance',NULL,500.00,'2026-08-12','Automatic renewal from advance balance for remembered package.','2026-08-12 21:48:10','2026-08-12 21:48:10'),
(10,'5','user',321,NULL,11,NULL,'advance',NULL,500.00,'2026-08-12','Automatic renewal from advance balance for remembered package.','2026-08-12 21:48:10','2026-08-12 21:48:10'),
(11,'Shofiq M','sms_device',305,NULL,12,4,'payment',NULL,10.00,'2026-08-12','Auto bKash SMS TrxID: DHC6EBL5IW','2026-08-12 23:05:48','2026-08-12 23:05:48');
/*!40000 ALTER TABLE `payment_allocations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(255) NOT NULL DEFAULT 'cash',
  `payment_account_id` bigint(20) unsigned DEFAULT NULL,
  `payment_date` date NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_customer_id_foreign` (`customer_id`),
  KEY `payments_invoice_id_foreign` (`invoice_id`),
  KEY `payments_entry_by_index` (`entry_by`),
  KEY `payments_entry_by_type_index` (`entry_by_type`),
  KEY `payments_account_ledger_index` (`payment_account_id`,`payment_date`,`id`),
  KEY `payments_method_ledger_index` (`payment_method`,`payment_date`,`id`),
  CONSTRAINT `payments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_payment_account_id_foreign` FOREIGN KEY (`payment_account_id`) REFERENCES `payment_accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES
(1,'2','user',348,7,500.00,'cash',NULL,'2026-08-11',NULL,'2026-08-11 20:21:36','2026-08-11 20:21:36'),
(2,'2','user',348,8,500.00,'cash',NULL,'2026-08-12',NULL,'2026-08-12 21:15:58','2026-08-12 21:15:58'),
(3,'5','user',321,9,500.00,'bkash',1,'2026-08-12',NULL,'2026-08-12 21:47:09','2026-08-12 21:47:09'),
(4,'Shofiq M','sms_device',305,12,12.00,'bkash',2,'2026-08-12','Auto bKash SMS TrxID: DHC6EBL5IW','2026-08-12 23:05:48','2026-08-12 23:05:48');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permission_role`
--

DROP TABLE IF EXISTS `permission_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permission_role` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `permission_role_role_id_foreign` (`role_id`),
  CONSTRAINT `permission_role_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `permission_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permission_role`
--

LOCK TABLES `permission_role` WRITE;
/*!40000 ALTER TABLE `permission_role` DISABLE KEYS */;
/*!40000 ALTER TABLE `permission_role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permission_user`
--

DROP TABLE IF EXISTS `permission_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permission_user` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`user_id`),
  KEY `permission_user_user_id_foreign` (`user_id`),
  CONSTRAINT `permission_user_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `permission_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permission_user`
--

LOCK TABLES `permission_user` WRITE;
/*!40000 ALTER TABLE `permission_user` DISABLE KEYS */;
INSERT INTO `permission_user` VALUES
(1,2),
(1,4),
(1,5),
(1,6),
(2,2),
(2,4),
(2,5),
(2,6),
(3,2),
(3,4),
(3,5),
(3,6),
(4,2),
(4,4),
(4,5),
(4,6),
(5,2),
(5,4),
(5,5),
(6,2),
(6,4),
(6,5),
(6,6),
(7,2),
(7,4),
(7,5),
(7,6),
(8,2),
(8,4),
(8,5),
(8,6),
(9,2),
(9,4),
(9,5),
(9,6),
(10,2),
(10,4),
(10,5),
(10,6),
(11,2),
(11,4),
(11,5),
(11,6),
(12,2),
(12,4),
(12,5),
(12,6),
(13,2),
(13,4),
(13,5),
(13,6),
(14,2),
(14,4),
(14,5),
(14,6),
(15,2),
(15,4),
(15,5),
(15,6),
(16,2),
(16,4),
(16,5),
(17,2),
(17,4),
(17,5),
(18,2),
(18,4),
(18,5),
(18,6),
(19,2),
(19,4),
(19,5),
(19,6),
(20,2),
(20,4),
(20,5),
(20,6);
/*!40000 ALTER TABLE `permission_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permission_user_denials`
--

DROP TABLE IF EXISTS `permission_user_denials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permission_user_denials` (
  `user_id` bigint(20) unsigned NOT NULL,
  `permission_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`permission_id`),
  KEY `permission_user_denials_permission_id_foreign` (`permission_id`),
  CONSTRAINT `permission_user_denials_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `permission_user_denials_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permission_user_denials`
--

LOCK TABLES `permission_user_denials` WRITE;
/*!40000 ALTER TABLE `permission_user_denials` DISABLE KEYS */;
INSERT INTO `permission_user_denials` VALUES
(6,5),
(6,16),
(6,17);
/*!40000 ALTER TABLE `permission_user_denials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_unique` (`name`),
  KEY `permissions_entry_by_index` (`entry_by`),
  KEY `permissions_entry_by_type_index` (`entry_by_type`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES
(1,'system','system','view_dashboard','View dashboard','2026-07-18 05:55:22','2026-07-18 05:55:22'),
(2,'system','system','manage_customers','Manage customers','2026-07-18 05:55:22','2026-07-18 05:55:22'),
(3,'system','system','manage_packages','Manage packages','2026-07-18 05:55:22','2026-07-18 05:55:22'),
(4,'system','system','manage_invoices','Manage invoices','2026-07-18 05:55:22','2026-07-18 05:55:22'),
(5,'system','system','finalize_invoices','Finalize invoices','2026-07-18 05:55:22','2026-07-18 05:55:22'),
(6,'system','system','manage_payments','Manage payments','2026-07-18 05:55:22','2026-07-18 05:55:22'),
(7,'system','system','manage_payment_accounts','Manage payment accounts','2026-07-18 05:55:22','2026-07-18 05:55:22'),
(8,'system','system','manage_tickets','Manage tickets','2026-07-18 05:55:22','2026-07-18 05:55:22'),
(9,'system','system','manage_products','Manage inventory','2026-07-18 05:55:22','2026-07-18 05:55:22'),
(10,'system','system','manage_users','Manage users and permissions','2026-07-18 05:55:22','2026-07-18 05:55:22'),
(11,'system','system','download_backup','Download database backup','2026-07-18 05:55:22','2026-07-18 05:55:22'),
(12,'system','system','manage_mikrotik_routers','Manage MikroTik routers','2026-07-18 05:55:22','2026-07-18 05:55:22'),
(13,NULL,NULL,'manage_expenses','Manage salaries and expenses','2026-07-18 05:55:27','2026-07-18 05:55:27'),
(14,NULL,NULL,'view_warranty_claims','View warranty claims','2026-07-18 05:55:31','2026-07-18 05:55:31'),
(15,NULL,NULL,'manage_warranty_claims','Manage warranty claims','2026-07-18 05:55:31','2026-07-18 05:55:31'),
(16,NULL,NULL,'close_warranty_claims','Close warranty claims','2026-07-18 05:55:32','2026-07-18 05:55:32'),
(17,NULL,NULL,'manage_service_products','Manage service products','2026-07-18 05:55:32','2026-07-18 05:55:32'),
(18,NULL,NULL,'manage_fleet','Manage vehicles and fleet','2026-07-18 05:55:36','2026-07-18 05:55:36'),
(19,NULL,NULL,'manage_resellers','Manage resellers and wallets','2026-07-21 23:59:13','2026-07-21 23:59:13'),
(20,NULL,NULL,'use_reseller_portal','Use reseller portal','2026-07-21 23:59:13','2026-07-21 23:59:13');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `print_logs`
--

DROP TABLE IF EXISTS `print_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `print_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `printable_type` varchar(255) DEFAULT NULL,
  `printable_id` bigint(20) unsigned DEFAULT NULL,
  `document_type` varchar(50) NOT NULL,
  `document_no` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `printed_at` timestamp NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `print_logs_organization_id_foreign` (`organization_id`),
  KEY `print_logs_printable_type_printable_id_index` (`printable_type`,`printable_id`),
  KEY `print_logs_user_id_foreign` (`user_id`),
  KEY `print_logs_document_type_index` (`document_type`),
  KEY `print_logs_document_no_index` (`document_no`),
  KEY `print_logs_printed_at_index` (`printed_at`),
  CONSTRAINT `print_logs_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `print_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `print_logs`
--

LOCK TABLES `print_logs` WRITE;
/*!40000 ALTER TABLE `print_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `print_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_categories`
--

DROP TABLE IF EXISTS `product_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_categories_parent_id_name_unique` (`parent_id`,`name`),
  CONSTRAINT `product_categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `product_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_categories`
--

LOCK TABLES `product_categories` WRITE;
/*!40000 ALTER TABLE `product_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_serials`
--

DROP TABLE IF EXISTS `product_serials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_serials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `purchase_bill_id` bigint(20) unsigned DEFAULT NULL,
  `purchase_bill_item_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_item_id` bigint(20) unsigned DEFAULT NULL,
  `serial_number` varchar(255) NOT NULL,
  `warranty_until` date DEFAULT NULL,
  `sold_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'in_stock',
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_serials_product_id_serial_number_unique` (`product_id`,`serial_number`),
  KEY `product_serials_purchase_bill_id_foreign` (`purchase_bill_id`),
  KEY `product_serials_purchase_bill_item_id_foreign` (`purchase_bill_item_id`),
  KEY `product_serials_status_index` (`status`),
  KEY `product_serials_customer_id_foreign` (`customer_id`),
  KEY `product_serials_invoice_id_foreign` (`invoice_id`),
  KEY `product_serials_invoice_item_id_foreign` (`invoice_item_id`),
  KEY `product_serials_warehouse_id_foreign` (`warehouse_id`),
  CONSTRAINT `product_serials_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_serials_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_serials_invoice_item_id_foreign` FOREIGN KEY (`invoice_item_id`) REFERENCES `invoice_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_serials_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_serials_purchase_bill_id_foreign` FOREIGN KEY (`purchase_bill_id`) REFERENCES `purchase_bills` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_serials_purchase_bill_item_id_foreign` FOREIGN KEY (`purchase_bill_item_id`) REFERENCES `purchase_bill_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_serials_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_serials`
--

LOCK TABLES `product_serials` WRITE;
/*!40000 ALTER TABLE `product_serials` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_serials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_warehouse_stocks`
--

DROP TABLE IF EXISTS `product_warehouse_stocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_warehouse_stocks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `quantity` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_warehouse_stocks_product_id_warehouse_id_unique` (`product_id`,`warehouse_id`),
  KEY `product_warehouse_stocks_warehouse_id_foreign` (`warehouse_id`),
  CONSTRAINT `product_warehouse_stocks_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_warehouse_stocks_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_warehouse_stocks`
--

LOCK TABLES `product_warehouse_stocks` WRITE;
/*!40000 ALTER TABLE `product_warehouse_stocks` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_warehouse_stocks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `sku` varchar(255) NOT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `brand` varchar(255) DEFAULT NULL,
  `product_category_id` bigint(20) unsigned DEFAULT NULL,
  `product_type` varchar(255) NOT NULL DEFAULT 'stock',
  `track_inventory` tinyint(1) NOT NULL DEFAULT 1,
  `track_serial_numbers` tinyint(1) NOT NULL DEFAULT 0,
  `warranty_days` int(10) unsigned DEFAULT NULL,
  `service_guarantee_days` int(10) unsigned DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `subcategory` varchar(255) DEFAULT NULL,
  `purchase_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sale_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `low_stock_alert` int(11) NOT NULL DEFAULT 5,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_sku_unique` (`sku`),
  UNIQUE KEY `products_barcode_unique` (`barcode`),
  KEY `products_entry_by_index` (`entry_by`),
  KEY `products_entry_by_type_index` (`entry_by_type`),
  KEY `products_brand_category_subcategory_index` (`brand`,`category`,`subcategory`),
  KEY `products_product_category_id_foreign` (`product_category_id`),
  KEY `products_product_type_index` (`product_type`),
  CONSTRAINT `products_product_category_id_foreign` FOREIGN KEY (`product_category_id`) REFERENCES `product_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_bill_items`
--

DROP TABLE IF EXISTS `purchase_bill_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_bill_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_bill_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `serialless_quantity` int(10) unsigned NOT NULL DEFAULT 0,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `warranty_months` smallint(5) unsigned DEFAULT NULL,
  `warranty_days` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_bill_items_purchase_bill_id_foreign` (`purchase_bill_id`),
  KEY `purchase_bill_items_product_id_foreign` (`product_id`),
  CONSTRAINT `purchase_bill_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `purchase_bill_items_purchase_bill_id_foreign` FOREIGN KEY (`purchase_bill_id`) REFERENCES `purchase_bills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_bill_items`
--

LOCK TABLES `purchase_bill_items` WRITE;
/*!40000 ALTER TABLE `purchase_bill_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_bill_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_bills`
--

DROP TABLE IF EXISTS `purchase_bills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_bills` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `party_id` bigint(20) unsigned DEFAULT NULL,
  `bill_no` varchar(255) NOT NULL,
  `purchase_date` date NOT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `document_name` varchar(255) DEFAULT NULL,
  `document_mime` varchar(100) DEFAULT NULL,
  `finalized_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_bills_bill_no_unique` (`bill_no`),
  KEY `purchase_bills_party_id_foreign` (`party_id`),
  CONSTRAINT `purchase_bills_party_id_foreign` FOREIGN KEY (`party_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_bills`
--

LOCK TABLES `purchase_bills` WRITE;
/*!40000 ALTER TABLE `purchase_bills` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_bills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quotation_items`
--

DROP TABLE IF EXISTS `quotation_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `quotation_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `quotation_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_type` varchar(255) DEFAULT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `serial_numbers` text DEFAULT NULL,
  `serialless_quantity` int(10) unsigned NOT NULL DEFAULT 0,
  `warranty_days` int(10) unsigned DEFAULT NULL,
  `service_guarantee_days` int(10) unsigned DEFAULT NULL,
  `service_guarantee_until` date DEFAULT NULL,
  `service_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quotation_items_quotation_id_foreign` (`quotation_id`),
  KEY `quotation_items_product_id_foreign` (`product_id`),
  KEY `quotation_items_entry_by_index` (`entry_by`),
  KEY `quotation_items_entry_by_type_index` (`entry_by_type`),
  CONSTRAINT `quotation_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quotation_items_quotation_id_foreign` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quotation_items`
--

LOCK TABLES `quotation_items` WRITE;
/*!40000 ALTER TABLE `quotation_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `quotation_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quotations`
--

DROP TABLE IF EXISTS `quotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `quotations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `converted_invoice_id` bigint(20) unsigned DEFAULT NULL,
  `quotation_no` varchar(255) NOT NULL,
  `quotation_date` date NOT NULL,
  `valid_until` date DEFAULT NULL,
  `billing_month` varchar(7) NOT NULL,
  `invoice_type` varchar(255) NOT NULL DEFAULT 'product',
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_type` varchar(255) NOT NULL DEFAULT 'amount',
  `discount_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `vat` decimal(12,2) NOT NULL DEFAULT 0.00,
  `vat_type` varchar(255) NOT NULL DEFAULT 'amount',
  `vat_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `payment_note` text DEFAULT NULL,
  `public_note` text DEFAULT NULL,
  `show_public_note` tinyint(1) NOT NULL DEFAULT 0,
  `private_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quotations_quotation_no_unique` (`quotation_no`),
  KEY `quotations_customer_id_foreign` (`customer_id`),
  KEY `quotations_converted_invoice_id_foreign` (`converted_invoice_id`),
  KEY `quotations_entry_by_index` (`entry_by`),
  KEY `quotations_entry_by_type_index` (`entry_by_type`),
  KEY `quotations_status_index` (`status`),
  CONSTRAINT `quotations_converted_invoice_id_foreign` FOREIGN KEY (`converted_invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quotations_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quotations`
--

LOCK TABLES `quotations` WRITE;
/*!40000 ALTER TABLE `quotations` DISABLE KEYS */;
/*!40000 ALTER TABLE `quotations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `record_versions`
--

DROP TABLE IF EXISTS `record_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `record_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `versionable_type` varchar(255) NOT NULL,
  `versionable_id` bigint(20) unsigned NOT NULL,
  `table_name` varchar(255) NOT NULL,
  `action` varchar(255) NOT NULL DEFAULT 'updated',
  `edited_by` varchar(255) DEFAULT NULL,
  `edited_by_type` varchar(255) DEFAULT NULL,
  `edited_by_name` varchar(255) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `changed_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`changed_fields`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `record_versions_versionable_type_versionable_id_created_at_index` (`versionable_type`,`versionable_id`,`created_at`),
  KEY `record_versions_versionable_type_index` (`versionable_type`),
  KEY `record_versions_versionable_id_index` (`versionable_id`),
  KEY `record_versions_table_name_index` (`table_name`),
  KEY `record_versions_action_index` (`action`),
  KEY `record_versions_edited_by_index` (`edited_by`),
  KEY `record_versions_edited_by_type_index` (`edited_by_type`)
) ENGINE=InnoDB AUTO_INCREMENT=125 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `record_versions`
--

LOCK TABLES `record_versions` WRITE;
/*!40000 ALTER TABLE `record_versions` DISABLE KEYS */;
INSERT INTO `record_versions` VALUES
(1,'App\\Models\\User',4,'users','updated','4','user','Arik','{\"remember_token\":\"[hidden]\"}','{\"remember_token\":\"[hidden]\"}','[\"remember_token\"]','{\"source\":\"model_update\"}','2026-07-22 04:03:17','2026-07-22 04:03:17'),
(2,'App\\Models\\User',5,'users','updated','5','user','Shofiq','{\"remember_token\":\"[hidden]\"}','{\"remember_token\":\"[hidden]\"}','[\"remember_token\"]','{\"source\":\"model_update\"}','2026-08-10 21:27:22','2026-08-10 21:27:22'),
(3,'App\\Models\\Customer',57,'customers','updated','2','user','Anike10','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\\nConnection ID: babul_fyt\\nProfile: 30 Mb Star\\nService: any\\nRouter comment: none\"}','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:18\\nConnection ID: babul_fyt\\nProfile: 30 Mb Star\\nService: any\\nRouter comment: none\\n\\nImported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:19\\nConnection ID: babul_fyt\\nProfile: 30 MB Lgedks\\nService: pppoe\\nRouter comment: none\"}','[\"notes\"]','{\"source\":\"model_update\"}','2026-08-10 23:08:19','2026-08-10 23:08:19'),
(4,'App\\Models\\Subscription',57,'subscriptions','updated','2','user','Anike10','{\"internet_package_id\":4}','{\"internet_package_id\":13}','[\"internet_package_id\"]','{\"source\":\"model_update\"}','2026-08-10 23:08:19','2026-08-10 23:08:19'),
(5,'App\\Models\\Customer',7,'customers','updated','2','user','Anike10','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\\nConnection ID: firoz_vi\\nProfile: 30 MB govt_college\\nService: any\\nRouter comment: Anike\",\"service_valid_until\":null,\"service_validity_note\":null}','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:08:17\\nConnection ID: firoz_vi\\nProfile: 30 MB govt_college\\nService: any\\nRouter comment: Anike\\n[2026-08-10 23:14] Manual validity override: not set \\u2192 2026-08-31. Reason: end\",\"service_valid_until\":\"2026-08-31 00:00:00\",\"service_validity_note\":\"[2026-08-10 23:14] Manual validity override: not set \\u2192 2026-08-31. Reason: end\"}','[\"notes\",\"service_valid_until\",\"service_validity_note\"]','{\"source\":\"model_update\"}','2026-08-10 23:14:24','2026-08-10 23:14:24'),
(6,'App\\Models\\Customer',346,'customers','updated','5','user','Shofiq','{\"account_balance\":\"0.00\",\"active_subscription\":{\"customer_id\":346,\"end_date\":null,\"internet_package_id\":13,\"package\":{\"default_ip_pool\":null,\"description\":\"Automatically imported from MikroTik 1036 MikroTik (103.133.200.180). Set the package price before billing.\",\"mikrotik_profile\":\"30 MB Lgedks\",\"monthly_price\":\"0.00\",\"name\":\"30 MB Lgedks\",\"speed\":\"30m\\/30m\",\"status\":\"active\"},\"start_date\":\"2026-08-09T18:00:00.000000Z\",\"status\":\"active\"},\"address\":\"Imported from MikroTik 1036 MikroTik\",\"connection_id\":\"tipu_vai\",\"email\":null,\"fixed_ip_address\":null,\"grace_days\":null,\"grace_until\":null,\"grace_used_at\":null,\"is_customer\":true,\"is_reseller\":false,\"is_vendor\":false,\"last_connected_at\":null,\"last_connected_ip\":null,\"last_connected_mac\":null,\"learned_ip_address\":null,\"learned_ip_package_id\":null,\"mikrotik_router_id\":1,\"mikrotik_username\":\"tipu_vai\",\"name\":\"tipu_vai\",\"never_suspend\":false,\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\nConnection ID: tipu_vai\\nProfile: 30 MB Lgedks\\nService: pppoe\\nRouter comment: none\",\"phone\":\"Not provided\",\"reseller_commission_percent\":\"0.00\",\"reseller_daily_payment_limit\":null,\"reseller_id\":null,\"service_valid_from\":null,\"service_valid_until\":null,\"service_validity_note\":null,\"status\":\"active\",\"use_fixed_ip\":false}','{\"account_balance\":\"0.00\",\"active_subscription\":{\"customer_id\":346,\"end_date\":null,\"internet_package_id\":13,\"package\":{\"default_ip_pool\":null,\"description\":\"Automatically imported from MikroTik 1036 MikroTik (103.133.200.180). Set the package price before billing.\",\"mikrotik_profile\":\"30 MB Lgedks\",\"monthly_price\":\"0.00\",\"name\":\"30 MB Lgedks\",\"speed\":\"30m\\/30m\",\"status\":\"active\"},\"start_date\":\"2026-08-09T18:00:00.000000Z\",\"status\":\"active\"},\"address\":\"Imported from MikroTik 1036 MikroTik\",\"connection_id\":\"tipu_vai\",\"email\":null,\"fixed_ip_address\":null,\"grace_days\":null,\"grace_until\":null,\"grace_used_at\":null,\"is_customer\":true,\"is_reseller\":false,\"is_vendor\":false,\"last_connected_at\":null,\"last_connected_ip\":null,\"last_connected_mac\":null,\"learned_ip_address\":null,\"learned_ip_package_id\":null,\"mikrotik_router_id\":1,\"mikrotik_username\":\"tipu_vai\",\"name\":\"tipu_vai\",\"never_suspend\":true,\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\r\\nConnection ID: tipu_vai\\r\\nProfile: 30 MB Lgedks\\r\\nService: pppoe\\r\\nRouter comment: none\",\"phone\":\"Not provided\",\"reseller_commission_percent\":\"0.00\",\"reseller_daily_payment_limit\":null,\"reseller_id\":null,\"service_valid_from\":null,\"service_valid_until\":null,\"service_validity_note\":null,\"status\":\"active\",\"use_fixed_ip\":false}','[\"never_suspend\",\"notes\"]','{\"source\":\"party_edit\",\"party_name\":\"tipu_vai\"}','2026-08-11 11:45:23','2026-08-11 11:45:23'),
(7,'App\\Models\\Customer',352,'customers','updated','5','user','Shofiq','{\"account_balance\":\"0.00\",\"active_subscription\":{\"customer_id\":352,\"end_date\":null,\"internet_package_id\":14,\"package\":{\"default_ip_pool\":null,\"description\":\"Automatically imported from MikroTik 1036 MikroTik (103.133.200.180). Set the package price before billing.\",\"mikrotik_profile\":\"30 MB shena_nir\",\"monthly_price\":\"0.00\",\"name\":\"30 MB shena_nir\",\"speed\":\"30m\\/30m\",\"status\":\"active\"},\"start_date\":\"2026-08-09T18:00:00.000000Z\",\"status\":\"active\"},\"address\":\"Imported from MikroTik 1036 MikroTik\",\"connection_id\":\"taslima\",\"email\":null,\"fixed_ip_address\":null,\"grace_days\":null,\"grace_until\":null,\"grace_used_at\":null,\"is_customer\":true,\"is_reseller\":false,\"is_vendor\":false,\"last_connected_at\":null,\"last_connected_ip\":null,\"last_connected_mac\":null,\"learned_ip_address\":null,\"learned_ip_package_id\":null,\"mikrotik_router_id\":1,\"mikrotik_username\":\"taslima\",\"name\":\"taslima\",\"never_suspend\":false,\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\nConnection ID: taslima\\nProfile: 30 MB shena_nir\\nService: pppoe\\nRouter comment: none\",\"phone\":\"Not provided\",\"reseller_commission_percent\":\"0.00\",\"reseller_daily_payment_limit\":null,\"reseller_id\":null,\"service_valid_from\":null,\"service_valid_until\":null,\"service_validity_note\":null,\"status\":\"active\",\"use_fixed_ip\":false}','{\"account_balance\":\"0.00\",\"active_subscription\":{\"customer_id\":352,\"end_date\":null,\"internet_package_id\":14,\"package\":{\"default_ip_pool\":null,\"description\":\"Automatically imported from MikroTik 1036 MikroTik (103.133.200.180). Set the package price before billing.\",\"mikrotik_profile\":\"30 MB shena_nir\",\"monthly_price\":\"0.00\",\"name\":\"30 MB shena_nir\",\"speed\":\"30m\\/30m\",\"status\":\"active\"},\"start_date\":\"2026-08-09T18:00:00.000000Z\",\"status\":\"active\"},\"address\":\"Imported from MikroTik 1036 MikroTik\",\"connection_id\":\"taslima\",\"email\":null,\"fixed_ip_address\":null,\"grace_days\":null,\"grace_until\":null,\"grace_used_at\":null,\"is_customer\":true,\"is_reseller\":false,\"is_vendor\":false,\"last_connected_at\":null,\"last_connected_ip\":null,\"last_connected_mac\":null,\"learned_ip_address\":null,\"learned_ip_package_id\":null,\"mikrotik_router_id\":1,\"mikrotik_username\":\"taslima\",\"name\":\"taslima\",\"never_suspend\":true,\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\r\\nConnection ID: taslima\\r\\nProfile: 30 MB shena_nir\\r\\nService: pppoe\\r\\nRouter comment: none\",\"phone\":\"Not provided\",\"reseller_commission_percent\":\"0.00\",\"reseller_daily_payment_limit\":null,\"reseller_id\":null,\"service_valid_from\":null,\"service_valid_until\":null,\"service_validity_note\":null,\"status\":\"active\",\"use_fixed_ip\":false}','[\"never_suspend\",\"notes\"]','{\"source\":\"party_edit\",\"party_name\":\"taslima\"}','2026-08-11 11:46:11','2026-08-11 11:46:11'),
(8,'App\\Models\\Customer',348,'customers','updated','2','user','Anike10','{\"account_balance\":\"0.00\"}','{\"account_balance\":\"500.00\"}','[\"account_balance\"]','{\"source\":\"model_update\"}','2026-08-11 14:43:30','2026-08-11 14:43:30'),
(9,'App\\Models\\InternetPackage',2,'internet_packages','updated','2','user','Anike10','{\"monthly_price\":\"0.00\"}','{\"monthly_price\":\"500.00\"}','[\"monthly_price\"]','{\"source\":\"model_update\"}','2026-08-11 14:44:45','2026-08-11 14:44:45'),
(10,'App\\Models\\InternetPackage',3,'internet_packages','updated','2','user','Anike10','{\"monthly_price\":\"0.00\"}','{\"monthly_price\":\"500.00\"}','[\"monthly_price\"]','{\"source\":\"model_update\"}','2026-08-11 14:44:50','2026-08-11 14:44:50'),
(11,'App\\Models\\InternetPackage',4,'internet_packages','updated','2','user','Anike10','{\"monthly_price\":\"0.00\"}','{\"monthly_price\":\"500.00\"}','[\"monthly_price\"]','{\"source\":\"model_update\"}','2026-08-11 14:44:53','2026-08-11 14:44:53'),
(12,'App\\Models\\InternetPackage',5,'internet_packages','updated','2','user','Anike10','{\"monthly_price\":\"0.00\"}','{\"monthly_price\":\"500.00\"}','[\"monthly_price\"]','{\"source\":\"model_update\"}','2026-08-11 14:44:56','2026-08-11 14:44:56'),
(13,'App\\Models\\InternetPackage',6,'internet_packages','updated','2','user','Anike10','{\"monthly_price\":\"0.00\"}','{\"monthly_price\":\"500.00\"}','[\"monthly_price\"]','{\"source\":\"model_update\"}','2026-08-11 14:45:00','2026-08-11 14:45:00'),
(14,'App\\Models\\InternetPackage',7,'internet_packages','updated','2','user','Anike10','{\"monthly_price\":\"0.00\"}','{\"monthly_price\":\"750.00\"}','[\"monthly_price\"]','{\"source\":\"model_update\"}','2026-08-11 14:45:09','2026-08-11 14:45:09'),
(15,'App\\Models\\InternetPackage',8,'internet_packages','updated','2','user','Anike10','{\"monthly_price\":\"0.00\"}','{\"monthly_price\":\"500.00\"}','[\"monthly_price\"]','{\"source\":\"model_update\"}','2026-08-11 14:45:14','2026-08-11 14:45:14'),
(16,'App\\Models\\InternetPackage',9,'internet_packages','updated','2','user','Anike10','{\"monthly_price\":\"0.00\"}','{\"monthly_price\":\"500.00\"}','[\"monthly_price\"]','{\"source\":\"model_update\"}','2026-08-11 14:45:18','2026-08-11 14:45:18'),
(17,'App\\Models\\InternetPackage',10,'internet_packages','updated','2','user','Anike10','{\"monthly_price\":\"0.00\"}','{\"monthly_price\":\"500.00\"}','[\"monthly_price\"]','{\"source\":\"model_update\"}','2026-08-11 14:45:28','2026-08-11 14:45:28'),
(18,'App\\Models\\InternetPackage',11,'internet_packages','updated','2','user','Anike10','{\"monthly_price\":\"0.00\"}','{\"monthly_price\":\"500.00\"}','[\"monthly_price\"]','{\"source\":\"model_update\"}','2026-08-11 14:45:33','2026-08-11 14:45:33'),
(19,'App\\Models\\InternetPackage',12,'internet_packages','updated','2','user','Anike10','{\"monthly_price\":\"0.00\"}','{\"monthly_price\":\"500.00\"}','[\"monthly_price\"]','{\"source\":\"model_update\"}','2026-08-11 14:45:38','2026-08-11 14:45:38'),
(20,'App\\Models\\InternetPackage',13,'internet_packages','updated','2','user','Anike10','{\"monthly_price\":\"0.00\"}','{\"monthly_price\":\"500.00\"}','[\"monthly_price\"]','{\"source\":\"model_update\"}','2026-08-11 14:45:41','2026-08-11 14:45:41'),
(21,'App\\Models\\InternetPackage',14,'internet_packages','updated','2','user','Anike10','{\"monthly_price\":\"0.00\"}','{\"monthly_price\":\"500.00\"}','[\"monthly_price\"]','{\"source\":\"model_update\"}','2026-08-11 14:45:44','2026-08-11 14:45:44'),
(22,'App\\Models\\InternetPackage',15,'internet_packages','updated','2','user','Anike10','{\"monthly_price\":\"0.00\"}','{\"monthly_price\":\"500.00\"}','[\"monthly_price\"]','{\"source\":\"model_update\"}','2026-08-11 14:45:48','2026-08-11 14:45:48'),
(23,'App\\Models\\InternetPackage',16,'internet_packages','updated','2','user','Anike10','{\"monthly_price\":\"0.00\"}','{\"monthly_price\":\"500.00\"}','[\"monthly_price\"]','{\"source\":\"model_update\"}','2026-08-11 14:45:57','2026-08-11 14:45:57'),
(24,'App\\Models\\InternetPackage',17,'internet_packages','updated','2','user','Anike10','{\"monthly_price\":\"0.00\"}','{\"monthly_price\":\"750.00\"}','[\"monthly_price\"]','{\"source\":\"model_update\"}','2026-08-11 14:46:04','2026-08-11 14:46:04'),
(25,'App\\Models\\InternetPackage',18,'internet_packages','updated','2','user','Anike10','{\"monthly_price\":\"0.00\"}','{\"monthly_price\":\"1000.00\"}','[\"monthly_price\"]','{\"source\":\"model_update\"}','2026-08-11 14:46:09','2026-08-11 14:46:09'),
(26,'App\\Models\\InternetPackage',19,'internet_packages','updated','2','user','Anike10','{\"monthly_price\":\"0.00\"}','{\"monthly_price\":\"1000.00\"}','[\"monthly_price\"]','{\"source\":\"model_update\"}','2026-08-11 14:46:12','2026-08-11 14:46:12'),
(27,'App\\Models\\InternetPackage',20,'internet_packages','updated','2','user','Anike10','{\"monthly_price\":\"0.00\"}','{\"monthly_price\":\"1000.00\"}','[\"monthly_price\"]','{\"source\":\"model_update\"}','2026-08-11 14:46:15','2026-08-11 14:46:15'),
(28,'App\\Models\\InternetPackage',21,'internet_packages','updated','2','user','Anike10','{\"monthly_price\":\"0.00\"}','{\"monthly_price\":\"750.00\"}','[\"monthly_price\"]','{\"source\":\"model_update\"}','2026-08-11 14:46:21','2026-08-11 14:46:21'),
(29,'App\\Models\\InternetPackage',22,'internet_packages','updated','2','user','Anike10','{\"monthly_price\":\"0.00\"}','{\"monthly_price\":\"500.00\"}','[\"monthly_price\"]','{\"source\":\"model_update\"}','2026-08-11 14:46:24','2026-08-11 14:46:24'),
(30,'App\\Models\\Customer',348,'customers','updated','2','user','Anike10','{\"account_balance\":\"500.00\"}','{\"account_balance\":\"1000.00\"}','[\"account_balance\"]','{\"source\":\"model_update\"}','2026-08-11 15:02:56','2026-08-11 15:02:56'),
(31,'App\\Models\\Customer',348,'customers','updated','2','user','Anike10','{\"account_balance\":\"1000.00\"}','{\"account_balance\":\"1500.00\"}','[\"account_balance\"]','{\"source\":\"model_update\"}','2026-08-11 15:17:12','2026-08-11 15:17:12'),
(32,'App\\Models\\User',2,'users','updated','2','user','Anike10','{\"remember_token\":\"[hidden]\"}','{\"remember_token\":\"[hidden]\"}','[\"remember_token\"]','{\"source\":\"model_update\"}','2026-08-11 15:19:41','2026-08-11 15:19:41'),
(33,'App\\Models\\Customer',348,'customers','updated','2','user','Anike10','{\"account_balance\":\"1500.00\"}','{\"account_balance\":\"2000.00\"}','[\"account_balance\"]','{\"source\":\"model_update\"}','2026-08-11 15:25:51','2026-08-11 15:25:51'),
(34,'App\\Models\\Customer',346,'customers','updated','2','user','Anike10','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\r\\nConnection ID: tipu_vai\\r\\nProfile: 30 MB Lgedks\\r\\nService: pppoe\\r\\nRouter comment: none\",\"service_valid_from\":null,\"service_valid_until\":null,\"service_validity_note\":null}','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\r\\nConnection ID: tipu_vai\\r\\nProfile: 30 MB Lgedks\\r\\nService: pppoe\\r\\nRouter comment: none\\n[2026-08-11 18:27] Activated package to 2026-08-31 via quick-activate action.\",\"service_valid_from\":\"2026-08-11 00:00:00\",\"service_valid_until\":\"2026-08-31 00:00:00\",\"service_validity_note\":\"[2026-08-11 18:27] Activated package to 2026-08-31 via quick-activate action.\"}','[\"notes\",\"service_valid_from\",\"service_valid_until\",\"service_validity_note\"]','{\"source\":\"model_update\"}','2026-08-11 18:27:24','2026-08-11 18:27:24'),
(35,'App\\Models\\Customer',348,'customers','updated','2','user','Anike10','{\"account_balance\":\"2000.00\"}','{\"account_balance\":\"2500.00\"}','[\"account_balance\"]','{\"source\":\"model_update\"}','2026-08-11 20:18:28','2026-08-11 20:18:28'),
(36,'App\\Models\\Invoice',2,'invoices','updated','2','user','Anike10','{\"paid_amount\":\"0.00\",\"due_amount\":\"500.00\",\"status\":\"unpaid\"}','{\"paid_amount\":500,\"due_amount\":0,\"status\":\"paid\"}','[\"paid_amount\",\"due_amount\",\"status\"]','{\"source\":\"model_update\"}','2026-08-11 20:18:28','2026-08-11 20:18:28'),
(37,'App\\Models\\Customer',348,'customers','updated','2','user','Anike10','{\"account_balance\":\"2500.00\"}','{\"account_balance\":\"2000.00\"}','[\"account_balance\"]','{\"source\":\"model_update\"}','2026-08-11 20:18:28','2026-08-11 20:18:28'),
(38,'App\\Models\\Invoice',3,'invoices','updated','2','user','Anike10','{\"paid_amount\":\"0.00\",\"due_amount\":\"500.00\",\"status\":\"unpaid\"}','{\"paid_amount\":500,\"due_amount\":0,\"status\":\"paid\"}','[\"paid_amount\",\"due_amount\",\"status\"]','{\"source\":\"model_update\"}','2026-08-11 20:18:28','2026-08-11 20:18:28'),
(39,'App\\Models\\Customer',348,'customers','updated','2','user','Anike10','{\"account_balance\":\"2000.00\"}','{\"account_balance\":\"1500.00\"}','[\"account_balance\"]','{\"source\":\"model_update\"}','2026-08-11 20:18:28','2026-08-11 20:18:28'),
(40,'App\\Models\\Invoice',4,'invoices','updated','2','user','Anike10','{\"paid_amount\":\"0.00\",\"due_amount\":\"500.00\",\"status\":\"unpaid\"}','{\"paid_amount\":500,\"due_amount\":0,\"status\":\"paid\"}','[\"paid_amount\",\"due_amount\",\"status\"]','{\"source\":\"model_update\"}','2026-08-11 20:18:28','2026-08-11 20:18:28'),
(41,'App\\Models\\Customer',348,'customers','updated','2','user','Anike10','{\"account_balance\":\"1500.00\"}','{\"account_balance\":\"1000.00\"}','[\"account_balance\"]','{\"source\":\"model_update\"}','2026-08-11 20:18:28','2026-08-11 20:18:28'),
(42,'App\\Models\\Invoice',5,'invoices','updated','2','user','Anike10','{\"paid_amount\":\"0.00\",\"due_amount\":\"500.00\",\"status\":\"unpaid\"}','{\"paid_amount\":500,\"due_amount\":0,\"status\":\"paid\"}','[\"paid_amount\",\"due_amount\",\"status\"]','{\"source\":\"model_update\"}','2026-08-11 20:18:28','2026-08-11 20:18:28'),
(43,'App\\Models\\Customer',348,'customers','updated','2','user','Anike10','{\"account_balance\":\"1000.00\"}','{\"account_balance\":\"500.00\"}','[\"account_balance\"]','{\"source\":\"model_update\"}','2026-08-11 20:18:28','2026-08-11 20:18:28'),
(44,'App\\Models\\Invoice',6,'invoices','updated','2','user','Anike10','{\"paid_amount\":\"0.00\",\"due_amount\":\"500.00\",\"status\":\"unpaid\"}','{\"paid_amount\":500,\"due_amount\":0,\"status\":\"paid\"}','[\"paid_amount\",\"due_amount\",\"status\"]','{\"source\":\"model_update\"}','2026-08-11 20:18:28','2026-08-11 20:18:28'),
(45,'App\\Models\\Customer',348,'customers','updated','2','user','Anike10','{\"account_balance\":\"500.00\"}','{\"account_balance\":\"0.00\"}','[\"account_balance\"]','{\"source\":\"model_update\"}','2026-08-11 20:18:28','2026-08-11 20:18:28'),
(46,'App\\Models\\Customer',348,'customers','updated','2','user','Anike10','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\nConnection ID: tanisha_home\\nProfile: 30 MB Lgedks\\nService: pppoe\\nRouter comment: none\",\"service_valid_from\":null,\"service_valid_until\":null,\"service_validity_note\":null}','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\nConnection ID: tanisha_home\\nProfile: 30 MB Lgedks\\nService: pppoe\\nRouter comment: none\\n[2026-08-11 20:18] Paid validity extended for 5 month(s): 2026-08-11 to 2027-01-10. Note: Automatic renewal from advance balance for remembered package.\",\"service_valid_from\":\"2026-08-11 00:00:00\",\"service_valid_until\":\"2027-01-10 00:00:00\",\"service_validity_note\":\"[2026-08-11 20:18] Paid validity extended for 5 month(s): 2026-08-11 to 2027-01-10. Note: Automatic renewal from advance balance for remembered package.\"}','[\"notes\",\"service_valid_from\",\"service_valid_until\",\"service_validity_note\"]','{\"source\":\"model_update\"}','2026-08-11 20:18:29','2026-08-11 20:18:29'),
(47,'App\\Models\\Invoice',7,'invoices','updated','2','user','Anike10','{\"paid_amount\":\"0.00\",\"due_amount\":\"500.00\",\"status\":\"unpaid\"}','{\"paid_amount\":500,\"due_amount\":0,\"status\":\"paid\"}','[\"paid_amount\",\"due_amount\",\"status\"]','{\"source\":\"model_update\"}','2026-08-11 20:21:36','2026-08-11 20:21:36'),
(48,'App\\Models\\Customer',348,'customers','updated','2','user','Anike10','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\nConnection ID: tanisha_home\\nProfile: 30 MB Lgedks\\nService: pppoe\\nRouter comment: none\\n[2026-08-11 20:18] Paid validity extended for 5 month(s): 2026-08-11 to 2027-01-10. Note: Automatic renewal from advance balance for remembered package.\",\"service_valid_until\":\"2027-01-10 00:00:00\",\"service_validity_note\":\"[2026-08-11 20:18] Paid validity extended for 5 month(s): 2026-08-11 to 2027-01-10. Note: Automatic renewal from advance balance for remembered package.\"}','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\nConnection ID: tanisha_home\\nProfile: 30 MB Lgedks\\nService: pppoe\\nRouter comment: none\\n[2026-08-11 20:18] Paid validity extended for 5 month(s): 2026-08-11 to 2027-01-10. Note: Automatic renewal from advance balance for remembered package.\\n[2026-08-11 20:21] Paid validity: payment date 2026-08-11; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-11 to 2026-09-10.\",\"service_valid_until\":\"2026-09-10 00:00:00\",\"service_validity_note\":\"[2026-08-11 20:21] Paid validity: payment date 2026-08-11; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-11 to 2026-09-10.\"}','[\"notes\",\"service_valid_until\",\"service_validity_note\"]','{\"source\":\"model_update\"}','2026-08-11 20:21:36','2026-08-11 20:21:36'),
(49,'App\\Models\\User',4,'users','updated','2','user','Anike10','{\"denied_permissions\":[],\"email\":\"arik@arik.com\",\"email_verified_at\":null,\"login_credential_changed\":false,\"menu_accesses\":[],\"name\":\"Arik\",\"permissions\":[{\"label\":\"View dashboard\",\"name\":\"view_dashboard\"},{\"label\":\"Manage customers\",\"name\":\"manage_customers\"},{\"label\":\"Manage packages\",\"name\":\"manage_packages\"},{\"label\":\"Manage invoices\",\"name\":\"manage_invoices\"},{\"label\":\"Finalize invoices\",\"name\":\"finalize_invoices\"},{\"label\":\"Manage payments\",\"name\":\"manage_payments\"},{\"label\":\"Manage payment accounts\",\"name\":\"manage_payment_accounts\"},{\"label\":\"Manage tickets\",\"name\":\"manage_tickets\"},{\"label\":\"Manage inventory\",\"name\":\"manage_products\"},{\"label\":\"Manage users and permissions\",\"name\":\"manage_users\"},{\"label\":\"Download database backup\",\"name\":\"download_backup\"},{\"label\":\"Manage MikroTik routers\",\"name\":\"manage_mikrotik_routers\"},{\"label\":\"Manage salaries and expenses\",\"name\":\"manage_expenses\"},{\"label\":\"View warranty claims\",\"name\":\"view_warranty_claims\"},{\"label\":\"Manage warranty claims\",\"name\":\"manage_warranty_claims\"},{\"label\":\"Close warranty claims\",\"name\":\"close_warranty_claims\"},{\"label\":\"Manage service products\",\"name\":\"manage_service_products\"},{\"label\":\"Manage vehicles and fleet\",\"name\":\"manage_fleet\"},{\"label\":\"Manage resellers and wallets\",\"name\":\"manage_resellers\"},{\"label\":\"Use reseller portal\",\"name\":\"use_reseller_portal\"}],\"reseller_id\":null,\"roles\":[]}','{\"denied_permissions\":[],\"email\":\"arik@arik.com\",\"email_verified_at\":null,\"login_credential_changed\":true,\"menu_accesses\":[{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":4}],\"name\":\"Arik\",\"permissions\":[{\"label\":\"View dashboard\",\"name\":\"view_dashboard\"},{\"label\":\"Manage customers\",\"name\":\"manage_customers\"},{\"label\":\"Manage packages\",\"name\":\"manage_packages\"},{\"label\":\"Manage invoices\",\"name\":\"manage_invoices\"},{\"label\":\"Finalize invoices\",\"name\":\"finalize_invoices\"},{\"label\":\"Manage payments\",\"name\":\"manage_payments\"},{\"label\":\"Manage payment accounts\",\"name\":\"manage_payment_accounts\"},{\"label\":\"Manage tickets\",\"name\":\"manage_tickets\"},{\"label\":\"Manage inventory\",\"name\":\"manage_products\"},{\"label\":\"Manage users and permissions\",\"name\":\"manage_users\"},{\"label\":\"Download database backup\",\"name\":\"download_backup\"},{\"label\":\"Manage MikroTik routers\",\"name\":\"manage_mikrotik_routers\"},{\"label\":\"Manage salaries and expenses\",\"name\":\"manage_expenses\"},{\"label\":\"View warranty claims\",\"name\":\"view_warranty_claims\"},{\"label\":\"Manage warranty claims\",\"name\":\"manage_warranty_claims\"},{\"label\":\"Close warranty claims\",\"name\":\"close_warranty_claims\"},{\"label\":\"Manage service products\",\"name\":\"manage_service_products\"},{\"label\":\"Manage vehicles and fleet\",\"name\":\"manage_fleet\"},{\"label\":\"Manage resellers and wallets\",\"name\":\"manage_resellers\"},{\"label\":\"Use reseller portal\",\"name\":\"use_reseller_portal\"}],\"reseller_id\":null,\"roles\":[]}','[\"login_credential_changed\",\"menu_accesses\"]','{\"source\":\"user_edit\",\"user_email\":\"arik@arik.com\"}','2026-08-11 21:52:48','2026-08-11 21:52:48'),
(50,'App\\Models\\User',5,'users','updated','2','user','Anike10','{\"denied_permissions\":[],\"email\":\"shofiqulkst@gmail.com\",\"email_verified_at\":null,\"login_credential_changed\":false,\"menu_accesses\":[],\"name\":\"Shofiq\",\"permissions\":[{\"label\":\"View dashboard\",\"name\":\"view_dashboard\"},{\"label\":\"Manage customers\",\"name\":\"manage_customers\"},{\"label\":\"Manage packages\",\"name\":\"manage_packages\"},{\"label\":\"Manage invoices\",\"name\":\"manage_invoices\"},{\"label\":\"Finalize invoices\",\"name\":\"finalize_invoices\"},{\"label\":\"Manage payments\",\"name\":\"manage_payments\"},{\"label\":\"Manage payment accounts\",\"name\":\"manage_payment_accounts\"},{\"label\":\"Manage tickets\",\"name\":\"manage_tickets\"},{\"label\":\"Manage inventory\",\"name\":\"manage_products\"},{\"label\":\"Manage users and permissions\",\"name\":\"manage_users\"},{\"label\":\"Download database backup\",\"name\":\"download_backup\"},{\"label\":\"Manage MikroTik routers\",\"name\":\"manage_mikrotik_routers\"},{\"label\":\"Manage salaries and expenses\",\"name\":\"manage_expenses\"},{\"label\":\"View warranty claims\",\"name\":\"view_warranty_claims\"},{\"label\":\"Manage warranty claims\",\"name\":\"manage_warranty_claims\"},{\"label\":\"Close warranty claims\",\"name\":\"close_warranty_claims\"},{\"label\":\"Manage service products\",\"name\":\"manage_service_products\"},{\"label\":\"Manage vehicles and fleet\",\"name\":\"manage_fleet\"},{\"label\":\"Manage resellers and wallets\",\"name\":\"manage_resellers\"},{\"label\":\"Use reseller portal\",\"name\":\"use_reseller_portal\"}],\"reseller_id\":null,\"roles\":[{\"label\":\"Administrator\",\"name\":\"admin\"},{\"label\":\"Reseller\",\"name\":\"reseller\"}]}','{\"denied_permissions\":[],\"email\":\"shofiqulkst@gmail.com\",\"email_verified_at\":null,\"login_credential_changed\":true,\"menu_accesses\":[{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":5}],\"name\":\"Shofiq\",\"permissions\":[{\"label\":\"View dashboard\",\"name\":\"view_dashboard\"},{\"label\":\"Manage customers\",\"name\":\"manage_customers\"},{\"label\":\"Manage packages\",\"name\":\"manage_packages\"},{\"label\":\"Manage invoices\",\"name\":\"manage_invoices\"},{\"label\":\"Finalize invoices\",\"name\":\"finalize_invoices\"},{\"label\":\"Manage payments\",\"name\":\"manage_payments\"},{\"label\":\"Manage payment accounts\",\"name\":\"manage_payment_accounts\"},{\"label\":\"Manage tickets\",\"name\":\"manage_tickets\"},{\"label\":\"Manage inventory\",\"name\":\"manage_products\"},{\"label\":\"Manage users and permissions\",\"name\":\"manage_users\"},{\"label\":\"Download database backup\",\"name\":\"download_backup\"},{\"label\":\"Manage MikroTik routers\",\"name\":\"manage_mikrotik_routers\"},{\"label\":\"Manage salaries and expenses\",\"name\":\"manage_expenses\"},{\"label\":\"View warranty claims\",\"name\":\"view_warranty_claims\"},{\"label\":\"Manage warranty claims\",\"name\":\"manage_warranty_claims\"},{\"label\":\"Close warranty claims\",\"name\":\"close_warranty_claims\"},{\"label\":\"Manage service products\",\"name\":\"manage_service_products\"},{\"label\":\"Manage vehicles and fleet\",\"name\":\"manage_fleet\"},{\"label\":\"Manage resellers and wallets\",\"name\":\"manage_resellers\"},{\"label\":\"Use reseller portal\",\"name\":\"use_reseller_portal\"}],\"reseller_id\":null,\"roles\":[{\"label\":\"Administrator\",\"name\":\"admin\"},{\"label\":\"Reseller\",\"name\":\"reseller\"}]}','[\"login_credential_changed\",\"menu_accesses\"]','{\"source\":\"user_edit\",\"user_email\":\"shofiqulkst@gmail.com\"}','2026-08-11 21:53:24','2026-08-11 21:53:24'),
(51,'App\\Models\\User',6,'users','updated','6','user','test','{\"remember_token\":\"[hidden]\"}','{\"remember_token\":\"[hidden]\"}','[\"remember_token\"]','{\"source\":\"model_update\"}','2026-08-11 22:13:30','2026-08-11 22:13:30'),
(52,'App\\Models\\User',6,'users','updated','2','user','Anike10','{\"denied_permissions\":[{\"label\":\"View dashboard\",\"name\":\"view_dashboard\"},{\"label\":\"Manage customers\",\"name\":\"manage_customers\"},{\"label\":\"Manage packages\",\"name\":\"manage_packages\"},{\"label\":\"Manage invoices\",\"name\":\"manage_invoices\"},{\"label\":\"Finalize invoices\",\"name\":\"finalize_invoices\"},{\"label\":\"Manage payments\",\"name\":\"manage_payments\"},{\"label\":\"Manage payment accounts\",\"name\":\"manage_payment_accounts\"},{\"label\":\"Manage tickets\",\"name\":\"manage_tickets\"},{\"label\":\"Manage inventory\",\"name\":\"manage_products\"},{\"label\":\"Manage users and permissions\",\"name\":\"manage_users\"},{\"label\":\"Download database backup\",\"name\":\"download_backup\"},{\"label\":\"Manage MikroTik routers\",\"name\":\"manage_mikrotik_routers\"},{\"label\":\"Manage salaries and expenses\",\"name\":\"manage_expenses\"},{\"label\":\"View warranty claims\",\"name\":\"view_warranty_claims\"},{\"label\":\"Manage warranty claims\",\"name\":\"manage_warranty_claims\"},{\"label\":\"Close warranty claims\",\"name\":\"close_warranty_claims\"},{\"label\":\"Manage service products\",\"name\":\"manage_service_products\"},{\"label\":\"Manage vehicles and fleet\",\"name\":\"manage_fleet\"},{\"label\":\"Manage resellers and wallets\",\"name\":\"manage_resellers\"},{\"label\":\"Use reseller portal\",\"name\":\"use_reseller_portal\"}],\"email\":\"t@t.com\",\"email_verified_at\":null,\"login_credential_changed\":false,\"menu_accesses\":[{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6}],\"name\":\"test\",\"permissions\":[],\"reseller_id\":null,\"roles\":[]}','{\"denied_permissions\":[{\"label\":\"Finalize invoices\",\"name\":\"finalize_invoices\"},{\"label\":\"Close warranty claims\",\"name\":\"close_warranty_claims\"},{\"label\":\"Manage service products\",\"name\":\"manage_service_products\"}],\"email\":\"t@t.com\",\"email_verified_at\":null,\"login_credential_changed\":true,\"menu_accesses\":[{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":false,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6},{\"allowed\":true,\"menu_key\":\"[hidden]\",\"user_id\":6}],\"name\":\"test\",\"permissions\":[{\"label\":\"View dashboard\",\"name\":\"view_dashboard\"},{\"label\":\"Manage customers\",\"name\":\"manage_customers\"},{\"label\":\"Manage packages\",\"name\":\"manage_packages\"},{\"label\":\"Manage invoices\",\"name\":\"manage_invoices\"},{\"label\":\"Manage payments\",\"name\":\"manage_payments\"},{\"label\":\"Manage payment accounts\",\"name\":\"manage_payment_accounts\"},{\"label\":\"Manage tickets\",\"name\":\"manage_tickets\"},{\"label\":\"Manage inventory\",\"name\":\"manage_products\"},{\"label\":\"Manage users and permissions\",\"name\":\"manage_users\"},{\"label\":\"Download database backup\",\"name\":\"download_backup\"},{\"label\":\"Manage MikroTik routers\",\"name\":\"manage_mikrotik_routers\"},{\"label\":\"Manage salaries and expenses\",\"name\":\"manage_expenses\"},{\"label\":\"View warranty claims\",\"name\":\"view_warranty_claims\"},{\"label\":\"Manage warranty claims\",\"name\":\"manage_warranty_claims\"},{\"label\":\"Manage vehicles and fleet\",\"name\":\"manage_fleet\"},{\"label\":\"Manage resellers and wallets\",\"name\":\"manage_resellers\"},{\"label\":\"Use reseller portal\",\"name\":\"use_reseller_portal\"}],\"reseller_id\":null,\"roles\":[]}','[\"denied_permissions\",\"login_credential_changed\",\"menu_accesses\",\"permissions\"]','{\"source\":\"user_edit\",\"user_email\":\"t@t.com\"}','2026-08-11 22:14:05','2026-08-11 22:14:05'),
(53,'App\\Models\\InternetPackage',13,'internet_packages','updated','2','user','Anike10','{\"default_ip_pool\":null}','{\"default_ip_pool\":\"lgedks\"}','[\"default_ip_pool\"]','{\"source\":\"model_update\"}','2026-08-12 19:06:39','2026-08-12 19:06:39'),
(54,'App\\Models\\InternetPackage',2,'internet_packages','updated','2','user','Anike10','{\"default_ip_pool\":null}','{\"default_ip_pool\":\"pool_180\"}','[\"default_ip_pool\"]','{\"source\":\"model_update\"}','2026-08-12 19:15:34','2026-08-12 19:15:34'),
(55,'App\\Models\\InternetPackage',4,'internet_packages','updated','2','user','Anike10','{\"default_ip_pool\":null}','{\"default_ip_pool\":\"StarLink\"}','[\"default_ip_pool\"]','{\"source\":\"model_update\"}','2026-08-12 19:15:59','2026-08-12 19:15:59'),
(56,'App\\Models\\InternetPackage',5,'internet_packages','updated','2','user','Anike10','{\"default_ip_pool\":null}','{\"default_ip_pool\":\"StarLink\"}','[\"default_ip_pool\"]','{\"source\":\"model_update\"}','2026-08-12 19:16:05','2026-08-12 19:16:05'),
(57,'App\\Models\\InternetPackage',6,'internet_packages','updated','2','user','Anike10','{\"default_ip_pool\":null}','{\"default_ip_pool\":\"a141ranvid\"}','[\"default_ip_pool\"]','{\"source\":\"model_update\"}','2026-08-12 19:16:10','2026-08-12 19:16:10'),
(58,'App\\Models\\InternetPackage',7,'internet_packages','updated','2','user','Anike10','{\"default_ip_pool\":null}','{\"default_ip_pool\":\"Travelshouse\"}','[\"default_ip_pool\"]','{\"source\":\"model_update\"}','2026-08-12 19:16:46','2026-08-12 19:16:46'),
(59,'App\\Models\\InternetPackage',8,'internet_packages','updated','2','user','Anike10','{\"default_ip_pool\":null}','{\"default_ip_pool\":\"Travelshouse\"}','[\"default_ip_pool\"]','{\"source\":\"model_update\"}','2026-08-12 19:16:51','2026-08-12 19:16:51'),
(60,'App\\Models\\InternetPackage',9,'internet_packages','updated','2','user','Anike10','{\"default_ip_pool\":null}','{\"default_ip_pool\":\"StarLink\"}','[\"default_ip_pool\"]','{\"source\":\"model_update\"}','2026-08-12 19:16:56','2026-08-12 19:16:56'),
(61,'App\\Models\\InternetPackage',10,'internet_packages','updated','2','user','Anike10','{\"default_ip_pool\":null}','{\"default_ip_pool\":\"Saifulkst\"}','[\"default_ip_pool\"]','{\"source\":\"model_update\"}','2026-08-12 19:17:01','2026-08-12 19:17:01'),
(62,'App\\Models\\InternetPackage',11,'internet_packages','updated','2','user','Anike10','{\"default_ip_pool\":null}','{\"default_ip_pool\":\"govt_college\"}','[\"default_ip_pool\"]','{\"source\":\"model_update\"}','2026-08-12 19:17:09','2026-08-12 19:17:09'),
(63,'App\\Models\\InternetPackage',12,'internet_packages','updated','2','user','Anike10','{\"default_ip_pool\":null}','{\"default_ip_pool\":\"Zillas\"}','[\"default_ip_pool\"]','{\"source\":\"model_update\"}','2026-08-12 19:18:06','2026-08-12 19:18:06'),
(64,'App\\Models\\InternetPackage',14,'internet_packages','updated','2','user','Anike10','{\"default_ip_pool\":null}','{\"default_ip_pool\":\"shena_nir\"}','[\"default_ip_pool\"]','{\"source\":\"model_update\"}','2026-08-12 19:18:12','2026-08-12 19:18:12'),
(65,'App\\Models\\InternetPackage',15,'internet_packages','updated','2','user','Anike10','{\"default_ip_pool\":null}','{\"default_ip_pool\":\"shena_nir\"}','[\"default_ip_pool\"]','{\"source\":\"model_update\"}','2026-08-12 19:18:15','2026-08-12 19:18:15'),
(66,'App\\Models\\InternetPackage',16,'internet_packages','updated','2','user','Anike10','{\"default_ip_pool\":null}','{\"default_ip_pool\":\"kpi_all\"}','[\"default_ip_pool\"]','{\"source\":\"model_update\"}','2026-08-12 19:18:22','2026-08-12 19:18:22'),
(67,'App\\Models\\InternetPackage',17,'internet_packages','updated','2','user','Anike10','{\"default_ip_pool\":null}','{\"default_ip_pool\":\"a141ranvid\"}','[\"default_ip_pool\"]','{\"source\":\"model_update\"}','2026-08-12 19:18:32','2026-08-12 19:18:32'),
(68,'App\\Models\\InternetPackage',18,'internet_packages','updated','2','user','Anike10','{\"default_ip_pool\":null}','{\"default_ip_pool\":\"a141ranvid\"}','[\"default_ip_pool\"]','{\"source\":\"model_update\"}','2026-08-12 19:18:40','2026-08-12 19:18:40'),
(69,'App\\Models\\InternetPackage',19,'internet_packages','updated','2','user','Anike10','{\"default_ip_pool\":null}','{\"default_ip_pool\":\"kpi_comdpt\"}','[\"default_ip_pool\"]','{\"source\":\"model_update\"}','2026-08-12 19:18:45','2026-08-12 19:18:45'),
(70,'App\\Models\\InternetPackage',20,'internet_packages','updated','2','user','Anike10','{\"default_ip_pool\":null}','{\"default_ip_pool\":\"Zillas\"}','[\"default_ip_pool\"]','{\"source\":\"model_update\"}','2026-08-12 19:18:50','2026-08-12 19:18:50'),
(71,'App\\Models\\InternetPackage',21,'internet_packages','updated','2','user','Anike10','{\"default_ip_pool\":null}','{\"default_ip_pool\":\"mosharof_bgoly\"}','[\"default_ip_pool\"]','{\"source\":\"model_update\"}','2026-08-12 19:18:54','2026-08-12 19:18:54'),
(72,'App\\Models\\InternetPackage',22,'internet_packages','updated','2','user','Anike10','{\"default_ip_pool\":null}','{\"default_ip_pool\":\"pool_180\"}','[\"default_ip_pool\"]','{\"source\":\"model_update\"}','2026-08-12 19:18:58','2026-08-12 19:18:58'),
(73,'App\\Models\\InternetPackage',17,'internet_packages','updated','2','user','Anike10','{\"default_ip_pool\":\"a141ranvid\"}','{\"default_ip_pool\":\"kpi_all\"}','[\"default_ip_pool\"]','{\"source\":\"model_update\"}','2026-08-12 19:19:30','2026-08-12 19:19:30'),
(74,'App\\Models\\InternetPackage',3,'internet_packages','updated','2','user','Anike10','{\"default_ip_pool\":null}','{\"default_ip_pool\":\"pool_180\"}','[\"default_ip_pool\"]','{\"source\":\"model_update\"}','2026-08-12 19:19:50','2026-08-12 19:19:50'),
(75,'App\\Models\\InternetPackage',24,'internet_packages','updated','2','user','Anike10','{\"speed\":\"Imported profile\"}','{\"speed\":\"10k\\/10k\"}','[\"speed\"]','{\"source\":\"model_update\"}','2026-08-12 19:31:42','2026-08-12 19:31:42'),
(76,'App\\Models\\InternetPackage',24,'internet_packages','updated','2','user','Anike10','{\"default_ip_pool\":null}','{\"default_ip_pool\":\"inactive\"}','[\"default_ip_pool\"]','{\"source\":\"model_update\"}','2026-08-12 19:34:11','2026-08-12 19:34:11'),
(77,'App\\Models\\Customer',348,'customers','updated','2','user','Anike10','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\nConnection ID: tanisha_home\\nProfile: 30 MB Lgedks\\nService: pppoe\\nRouter comment: none\\n[2026-08-11 20:18] Paid validity extended for 5 month(s): 2026-08-11 to 2027-01-10. Note: Automatic renewal from advance balance for remembered package.\\n[2026-08-11 20:21] Paid validity: payment date 2026-08-11; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-11 to 2026-09-10.\",\"status\":\"active\",\"service_validity_note\":\"[2026-08-11 20:21] Paid validity: payment date 2026-08-11; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-11 to 2026-09-10.\"}','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\nConnection ID: tanisha_home\\nProfile: 30 MB Lgedks\\nService: pppoe\\nRouter comment: none\\n[2026-08-11 20:18] Paid validity extended for 5 month(s): 2026-08-11 to 2027-01-10. Note: Automatic renewal from advance balance for remembered package.\\n[2026-08-11 20:21] Paid validity: payment date 2026-08-11; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-11 to 2026-09-10.\\n[2026-08-12 19:35] Service force-inactivated while validity remained 2026-09-10. Reason: test\",\"status\":\"inactive\",\"service_validity_note\":\"[2026-08-11 20:21] Paid validity: payment date 2026-08-11; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-11 to 2026-09-10.\\n[2026-08-12 19:35] Service force-inactivated while validity remained 2026-09-10. Reason: test\"}','[\"notes\",\"status\",\"service_validity_note\"]','{\"source\":\"model_update\"}','2026-08-12 19:35:25','2026-08-12 19:35:25'),
(78,'App\\Models\\Subscription',348,'subscriptions','updated','2','user','Anike10','{\"end_date\":null,\"status\":\"active\"}','{\"end_date\":\"2026-08-12 00:00:00\",\"status\":\"inactive\"}','[\"end_date\",\"status\"]','{\"source\":\"model_update\"}','2026-08-12 19:35:25','2026-08-12 19:35:25'),
(79,'App\\Models\\Invoice',8,'invoices','updated','2','user','Anike10','{\"paid_amount\":\"0.00\",\"due_amount\":\"500.00\",\"status\":\"unpaid\"}','{\"paid_amount\":500,\"due_amount\":0,\"status\":\"paid\"}','[\"paid_amount\",\"due_amount\",\"status\"]','{\"source\":\"model_update\"}','2026-08-12 21:15:58','2026-08-12 21:15:58'),
(80,'App\\Models\\Customer',348,'customers','updated','2','user','Anike10','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\nConnection ID: tanisha_home\\nProfile: 30 MB Lgedks\\nService: pppoe\\nRouter comment: none\\n[2026-08-11 20:18] Paid validity extended for 5 month(s): 2026-08-11 to 2027-01-10. Note: Automatic renewal from advance balance for remembered package.\\n[2026-08-11 20:21] Paid validity: payment date 2026-08-11; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-11 to 2026-09-10.\\n[2026-08-12 19:35] Service force-inactivated while validity remained 2026-09-10. Reason: test\",\"status\":\"inactive\",\"service_valid_from\":\"2026-08-11 00:00:00\",\"service_valid_until\":\"2026-09-10 00:00:00\",\"service_validity_note\":\"[2026-08-11 20:21] Paid validity: payment date 2026-08-11; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-11 to 2026-09-10.\\n[2026-08-12 19:35] Service force-inactivated while validity remained 2026-09-10. Reason: test\"}','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\nConnection ID: tanisha_home\\nProfile: 30 MB Lgedks\\nService: pppoe\\nRouter comment: none\\n[2026-08-11 20:18] Paid validity extended for 5 month(s): 2026-08-11 to 2027-01-10. Note: Automatic renewal from advance balance for remembered package.\\n[2026-08-11 20:21] Paid validity: payment date 2026-08-11; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-11 to 2026-09-10.\\n[2026-08-12 19:35] Service force-inactivated while validity remained 2026-09-10. Reason: test\\n[2026-08-12 21:15] Paid validity: payment date 2026-08-12; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-12 to 2026-09-11.\",\"status\":\"active\",\"service_valid_from\":\"2026-08-12 00:00:00\",\"service_valid_until\":\"2026-09-11 00:00:00\",\"service_validity_note\":\"[2026-08-12 21:15] Paid validity: payment date 2026-08-12; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-12 to 2026-09-11.\"}','[\"notes\",\"status\",\"service_valid_from\",\"service_valid_until\",\"service_validity_note\"]','{\"source\":\"model_update\"}','2026-08-12 21:15:58','2026-08-12 21:15:58'),
(81,'App\\Models\\Subscription',348,'subscriptions','updated','2','user','Anike10','{\"end_date\":\"2026-08-12 00:00:00\",\"status\":\"inactive\"}','{\"end_date\":null,\"status\":\"active\"}','[\"end_date\",\"status\"]','{\"source\":\"model_update\"}','2026-08-12 21:15:58','2026-08-12 21:15:58'),
(82,'App\\Models\\Customer',348,'customers','updated','2','user','Anike10','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\nConnection ID: tanisha_home\\nProfile: 30 MB Lgedks\\nService: pppoe\\nRouter comment: none\\n[2026-08-11 20:18] Paid validity extended for 5 month(s): 2026-08-11 to 2027-01-10. Note: Automatic renewal from advance balance for remembered package.\\n[2026-08-11 20:21] Paid validity: payment date 2026-08-11; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-11 to 2026-09-10.\\n[2026-08-12 19:35] Service force-inactivated while validity remained 2026-09-10. Reason: test\\n[2026-08-12 21:15] Paid validity: payment date 2026-08-12; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-12 to 2026-09-11.\",\"status\":\"active\",\"service_validity_note\":\"[2026-08-12 21:15] Paid validity: payment date 2026-08-12; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-12 to 2026-09-11.\"}','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\nConnection ID: tanisha_home\\nProfile: 30 MB Lgedks\\nService: pppoe\\nRouter comment: none\\n[2026-08-11 20:18] Paid validity extended for 5 month(s): 2026-08-11 to 2027-01-10. Note: Automatic renewal from advance balance for remembered package.\\n[2026-08-11 20:21] Paid validity: payment date 2026-08-11; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-11 to 2026-09-10.\\n[2026-08-12 19:35] Service force-inactivated while validity remained 2026-09-10. Reason: test\\n[2026-08-12 21:15] Paid validity: payment date 2026-08-12; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-12 to 2026-09-11.\\n[2026-08-12 21:16] Service temporarily force-inactivated while validity remained 2026-09-11. Reason: test\",\"status\":\"inactive\",\"service_validity_note\":\"[2026-08-12 21:15] Paid validity: payment date 2026-08-12; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-12 to 2026-09-11.\\n[2026-08-12 21:16] Service temporarily force-inactivated while validity remained 2026-09-11. Reason: test\"}','[\"notes\",\"status\",\"service_validity_note\"]','{\"source\":\"model_update\"}','2026-08-12 21:16:38','2026-08-12 21:16:38'),
(83,'App\\Models\\Subscription',348,'subscriptions','updated','2','user','Anike10','{\"end_date\":null,\"status\":\"active\"}','{\"end_date\":\"2026-08-12 00:00:00\",\"status\":\"inactive\"}','[\"end_date\",\"status\"]','{\"source\":\"model_update\"}','2026-08-12 21:16:38','2026-08-12 21:16:38'),
(84,'App\\Models\\Customer',348,'customers','updated','2','user','Anike10','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\nConnection ID: tanisha_home\\nProfile: 30 MB Lgedks\\nService: pppoe\\nRouter comment: none\\n[2026-08-11 20:18] Paid validity extended for 5 month(s): 2026-08-11 to 2027-01-10. Note: Automatic renewal from advance balance for remembered package.\\n[2026-08-11 20:21] Paid validity: payment date 2026-08-11; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-11 to 2026-09-10.\\n[2026-08-12 19:35] Service force-inactivated while validity remained 2026-09-10. Reason: test\\n[2026-08-12 21:15] Paid validity: payment date 2026-08-12; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-12 to 2026-09-11.\\n[2026-08-12 21:16] Service temporarily force-inactivated while validity remained 2026-09-11. Reason: test\",\"status\":\"inactive\",\"service_validity_note\":\"[2026-08-12 21:15] Paid validity: payment date 2026-08-12; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-12 to 2026-09-11.\\n[2026-08-12 21:16] Service temporarily force-inactivated while validity remained 2026-09-11. Reason: test\"}','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\nConnection ID: tanisha_home\\nProfile: 30 MB Lgedks\\nService: pppoe\\nRouter comment: none\\n[2026-08-11 20:18] Paid validity extended for 5 month(s): 2026-08-11 to 2027-01-10. Note: Automatic renewal from advance balance for remembered package.\\n[2026-08-11 20:21] Paid validity: payment date 2026-08-11; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-11 to 2026-09-10.\\n[2026-08-12 19:35] Service force-inactivated while validity remained 2026-09-10. Reason: test\\n[2026-08-12 21:15] Paid validity: payment date 2026-08-12; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-12 to 2026-09-11.\\n[2026-08-12 21:16] Service temporarily force-inactivated while validity remained 2026-09-11. Reason: test\\n[2026-08-12 21:17] Service temporarily force-activated while validity remained 2026-09-11. Reason: test\",\"status\":\"active\",\"service_validity_note\":\"[2026-08-12 21:15] Paid validity: payment date 2026-08-12; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-12 to 2026-09-11.\\n[2026-08-12 21:16] Service temporarily force-inactivated while validity remained 2026-09-11. Reason: test\\n[2026-08-12 21:17] Service temporarily force-activated while validity remained 2026-09-11. Reason: test\"}','[\"notes\",\"status\",\"service_validity_note\"]','{\"source\":\"model_update\"}','2026-08-12 21:17:04','2026-08-12 21:17:04'),
(85,'App\\Models\\Subscription',348,'subscriptions','updated','2','user','Anike10','{\"end_date\":\"2026-08-12 00:00:00\",\"status\":\"inactive\"}','{\"end_date\":null,\"status\":\"active\"}','[\"end_date\",\"status\"]','{\"source\":\"model_update\"}','2026-08-12 21:17:04','2026-08-12 21:17:04'),
(86,'App\\Models\\Customer',348,'customers','updated','2','user','Anike10','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\nConnection ID: tanisha_home\\nProfile: 30 MB Lgedks\\nService: pppoe\\nRouter comment: none\\n[2026-08-11 20:18] Paid validity extended for 5 month(s): 2026-08-11 to 2027-01-10. Note: Automatic renewal from advance balance for remembered package.\\n[2026-08-11 20:21] Paid validity: payment date 2026-08-11; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-11 to 2026-09-10.\\n[2026-08-12 19:35] Service force-inactivated while validity remained 2026-09-10. Reason: test\\n[2026-08-12 21:15] Paid validity: payment date 2026-08-12; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-12 to 2026-09-11.\\n[2026-08-12 21:16] Service temporarily force-inactivated while validity remained 2026-09-11. Reason: test\\n[2026-08-12 21:17] Service temporarily force-activated while validity remained 2026-09-11. Reason: test\",\"status\":\"active\",\"service_validity_note\":\"[2026-08-12 21:15] Paid validity: payment date 2026-08-12; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-12 to 2026-09-11.\\n[2026-08-12 21:16] Service temporarily force-inactivated while validity remained 2026-09-11. Reason: test\\n[2026-08-12 21:17] Service temporarily force-activated while validity remained 2026-09-11. Reason: test\"}','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\nConnection ID: tanisha_home\\nProfile: 30 MB Lgedks\\nService: pppoe\\nRouter comment: none\\n[2026-08-11 20:18] Paid validity extended for 5 month(s): 2026-08-11 to 2027-01-10. Note: Automatic renewal from advance balance for remembered package.\\n[2026-08-11 20:21] Paid validity: payment date 2026-08-11; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-11 to 2026-09-10.\\n[2026-08-12 19:35] Service force-inactivated while validity remained 2026-09-10. Reason: test\\n[2026-08-12 21:15] Paid validity: payment date 2026-08-12; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-12 to 2026-09-11.\\n[2026-08-12 21:16] Service temporarily force-inactivated while validity remained 2026-09-11. Reason: test\\n[2026-08-12 21:17] Service temporarily force-activated while validity remained 2026-09-11. Reason: test\\n[2026-08-12 21:27] Service temporarily force-inactivated while validity remained 2026-09-11. Reason: test\",\"status\":\"inactive\",\"service_validity_note\":\"[2026-08-12 21:15] Paid validity: payment date 2026-08-12; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-12 to 2026-09-11.\\n[2026-08-12 21:16] Service temporarily force-inactivated while validity remained 2026-09-11. Reason: test\\n[2026-08-12 21:17] Service temporarily force-activated while validity remained 2026-09-11. Reason: test\\n[2026-08-12 21:27] Service temporarily force-inactivated while validity remained 2026-09-11. Reason: test\"}','[\"notes\",\"status\",\"service_validity_note\"]','{\"source\":\"model_update\"}','2026-08-12 21:27:56','2026-08-12 21:27:56'),
(87,'App\\Models\\Subscription',348,'subscriptions','updated','2','user','Anike10','{\"end_date\":null,\"status\":\"active\"}','{\"end_date\":\"2026-08-12 00:00:00\",\"status\":\"inactive\"}','[\"end_date\",\"status\"]','{\"source\":\"model_update\"}','2026-08-12 21:27:56','2026-08-12 21:27:56'),
(88,'App\\Models\\Customer',348,'customers','updated','2','user','Anike10','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\nConnection ID: tanisha_home\\nProfile: 30 MB Lgedks\\nService: pppoe\\nRouter comment: none\\n[2026-08-11 20:18] Paid validity extended for 5 month(s): 2026-08-11 to 2027-01-10. Note: Automatic renewal from advance balance for remembered package.\\n[2026-08-11 20:21] Paid validity: payment date 2026-08-11; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-11 to 2026-09-10.\\n[2026-08-12 19:35] Service force-inactivated while validity remained 2026-09-10. Reason: test\\n[2026-08-12 21:15] Paid validity: payment date 2026-08-12; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-12 to 2026-09-11.\\n[2026-08-12 21:16] Service temporarily force-inactivated while validity remained 2026-09-11. Reason: test\\n[2026-08-12 21:17] Service temporarily force-activated while validity remained 2026-09-11. Reason: test\\n[2026-08-12 21:27] Service temporarily force-inactivated while validity remained 2026-09-11. Reason: test\",\"status\":\"inactive\",\"service_validity_note\":\"[2026-08-12 21:15] Paid validity: payment date 2026-08-12; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-12 to 2026-09-11.\\n[2026-08-12 21:16] Service temporarily force-inactivated while validity remained 2026-09-11. Reason: test\\n[2026-08-12 21:17] Service temporarily force-activated while validity remained 2026-09-11. Reason: test\\n[2026-08-12 21:27] Service temporarily force-inactivated while validity remained 2026-09-11. Reason: test\"}','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\nConnection ID: tanisha_home\\nProfile: 30 MB Lgedks\\nService: pppoe\\nRouter comment: none\\n[2026-08-11 20:18] Paid validity extended for 5 month(s): 2026-08-11 to 2027-01-10. Note: Automatic renewal from advance balance for remembered package.\\n[2026-08-11 20:21] Paid validity: payment date 2026-08-11; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-11 to 2026-09-10.\\n[2026-08-12 19:35] Service force-inactivated while validity remained 2026-09-10. Reason: test\\n[2026-08-12 21:15] Paid validity: payment date 2026-08-12; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-12 to 2026-09-11.\\n[2026-08-12 21:16] Service temporarily force-inactivated while validity remained 2026-09-11. Reason: test\\n[2026-08-12 21:17] Service temporarily force-activated while validity remained 2026-09-11. Reason: test\\n[2026-08-12 21:27] Service temporarily force-inactivated while validity remained 2026-09-11. Reason: test\\n[2026-08-12 21:30] Service temporarily force-activated while validity remained 2026-09-11. Reason: test\",\"status\":\"active\",\"service_validity_note\":\"[2026-08-12 21:15] Paid validity: payment date 2026-08-12; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-12 to 2026-09-11.\\n[2026-08-12 21:16] Service temporarily force-inactivated while validity remained 2026-09-11. Reason: test\\n[2026-08-12 21:17] Service temporarily force-activated while validity remained 2026-09-11. Reason: test\\n[2026-08-12 21:27] Service temporarily force-inactivated while validity remained 2026-09-11. Reason: test\\n[2026-08-12 21:30] Service temporarily force-activated while validity remained 2026-09-11. Reason: test\"}','[\"notes\",\"status\",\"service_validity_note\"]','{\"source\":\"model_update\"}','2026-08-12 21:30:51','2026-08-12 21:30:51'),
(89,'App\\Models\\Subscription',348,'subscriptions','updated','2','user','Anike10','{\"end_date\":\"2026-08-12 00:00:00\",\"status\":\"inactive\"}','{\"end_date\":null,\"status\":\"active\"}','[\"end_date\",\"status\"]','{\"source\":\"model_update\"}','2026-08-12 21:30:51','2026-08-12 21:30:51'),
(90,'App\\Models\\Customer',352,'customers','updated','5','user','Shofiq','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\r\\nConnection ID: taslima\\r\\nProfile: 30 MB shena_nir\\r\\nService: pppoe\\r\\nRouter comment: none\",\"service_valid_from\":null,\"service_valid_until\":null,\"service_validity_note\":null}','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\r\\nConnection ID: taslima\\r\\nProfile: 30 MB shena_nir\\r\\nService: pppoe\\r\\nRouter comment: none\\n[2026-08-12 21:39] Activated package to 2026-08-12 via quick-activate action.\",\"service_valid_from\":\"2026-08-12 00:00:00\",\"service_valid_until\":\"2026-08-12 00:00:00\",\"service_validity_note\":\"[2026-08-12 21:39] Activated package to 2026-08-12 via quick-activate action.\"}','[\"notes\",\"service_valid_from\",\"service_valid_until\",\"service_validity_note\"]','{\"source\":\"model_update\"}','2026-08-12 21:39:53','2026-08-12 21:39:53'),
(91,'App\\Models\\Customer',321,'customers','updated','5','user','Shofiq','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\\nConnection ID: tisha\\nProfile: 30 MB Saifulkst\\nService: pppoe\\nRouter comment: Anike\",\"service_valid_from\":null,\"service_valid_until\":null,\"service_validity_note\":null}','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\\nConnection ID: tisha\\nProfile: 30 MB Saifulkst\\nService: pppoe\\nRouter comment: Anike\\n[2026-08-12 21:42] Activated package to 2026-08-13 via quick-activate action.\",\"service_valid_from\":\"2026-08-12 00:00:00\",\"service_valid_until\":\"2026-08-13 00:00:00\",\"service_validity_note\":\"[2026-08-12 21:42] Activated package to 2026-08-13 via quick-activate action.\"}','[\"notes\",\"service_valid_from\",\"service_valid_until\",\"service_validity_note\"]','{\"source\":\"model_update\"}','2026-08-12 21:42:15','2026-08-12 21:42:15'),
(92,'App\\Models\\Customer',305,'customers','updated','5','user','Shofiq','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\\nConnection ID: tafsircom\\nProfile: 200 Mb Star\\nService: any\\nRouter comment: Anike\",\"status\":\"inactive\",\"service_valid_from\":null,\"service_valid_until\":null,\"service_validity_note\":null}','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\\nConnection ID: tafsircom\\nProfile: 200 Mb Star\\nService: any\\nRouter comment: Anike\\n[2026-08-12 21:42] Activated package to 2026-09-12 via quick-activate action.\",\"status\":\"active\",\"service_valid_from\":\"2026-08-12 00:00:00\",\"service_valid_until\":\"2026-09-12 00:00:00\",\"service_validity_note\":\"[2026-08-12 21:42] Activated package to 2026-09-12 via quick-activate action.\"}','[\"notes\",\"status\",\"service_valid_from\",\"service_valid_until\",\"service_validity_note\"]','{\"source\":\"model_update\"}','2026-08-12 21:42:56','2026-08-12 21:42:56'),
(93,'App\\Models\\Subscription',305,'subscriptions','updated','5','user','Shofiq','{\"status\":\"inactive\"}','{\"status\":\"active\"}','[\"status\"]','{\"source\":\"model_update\"}','2026-08-12 21:42:56','2026-08-12 21:42:56'),
(94,'App\\Models\\PaymentAccount',1,'payment_accounts','updated','5','user','Shofiq','{\"account_name\":\"Shofiqul\"}','{\"account_name\":\"Shofiqul Bkash\"}','[\"account_name\"]','{\"source\":\"model_update\"}','2026-08-12 21:46:10','2026-08-12 21:46:10'),
(95,'App\\Models\\Invoice',9,'invoices','updated','5','user','Shofiq','{\"paid_amount\":\"0.00\",\"due_amount\":\"500.00\",\"status\":\"unpaid\"}','{\"paid_amount\":500,\"due_amount\":0,\"status\":\"paid\"}','[\"paid_amount\",\"due_amount\",\"status\"]','{\"source\":\"model_update\"}','2026-08-12 21:47:09','2026-08-12 21:47:09'),
(96,'App\\Models\\Customer',321,'customers','updated','5','user','Shofiq','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\\nConnection ID: tisha\\nProfile: 30 MB Saifulkst\\nService: pppoe\\nRouter comment: Anike\\n[2026-08-12 21:42] Activated package to 2026-08-13 via quick-activate action.\",\"service_valid_until\":\"2026-08-13 00:00:00\",\"service_validity_note\":\"[2026-08-12 21:42] Activated package to 2026-08-13 via quick-activate action.\"}','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\\nConnection ID: tisha\\nProfile: 30 MB Saifulkst\\nService: pppoe\\nRouter comment: Anike\\n[2026-08-12 21:42] Activated package to 2026-08-13 via quick-activate action.\\n[2026-08-12 21:47] Paid validity: payment date 2026-08-12; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-12 to 2026-09-11.\",\"service_valid_until\":\"2026-09-11 00:00:00\",\"service_validity_note\":\"[2026-08-12 21:47] Paid validity: payment date 2026-08-12; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-12 to 2026-09-11.\"}','[\"notes\",\"service_valid_until\",\"service_validity_note\"]','{\"source\":\"model_update\"}','2026-08-12 21:47:09','2026-08-12 21:47:09'),
(97,'App\\Models\\Customer',321,'customers','updated','5','user','Shofiq','{\"account_balance\":\"0.00\"}','{\"account_balance\":\"1000.00\"}','[\"account_balance\"]','{\"source\":\"model_update\"}','2026-08-12 21:48:10','2026-08-12 21:48:10'),
(98,'App\\Models\\Invoice',10,'invoices','updated','5','user','Shofiq','{\"paid_amount\":\"0.00\",\"due_amount\":\"500.00\",\"status\":\"unpaid\"}','{\"paid_amount\":500,\"due_amount\":0,\"status\":\"paid\"}','[\"paid_amount\",\"due_amount\",\"status\"]','{\"source\":\"model_update\"}','2026-08-12 21:48:10','2026-08-12 21:48:10'),
(99,'App\\Models\\Customer',321,'customers','updated','5','user','Shofiq','{\"account_balance\":\"1000.00\"}','{\"account_balance\":\"500.00\"}','[\"account_balance\"]','{\"source\":\"model_update\"}','2026-08-12 21:48:10','2026-08-12 21:48:10'),
(100,'App\\Models\\Invoice',11,'invoices','updated','5','user','Shofiq','{\"paid_amount\":\"0.00\",\"due_amount\":\"500.00\",\"status\":\"unpaid\"}','{\"paid_amount\":500,\"due_amount\":0,\"status\":\"paid\"}','[\"paid_amount\",\"due_amount\",\"status\"]','{\"source\":\"model_update\"}','2026-08-12 21:48:10','2026-08-12 21:48:10'),
(101,'App\\Models\\Customer',321,'customers','updated','5','user','Shofiq','{\"account_balance\":\"500.00\"}','{\"account_balance\":\"0.00\"}','[\"account_balance\"]','{\"source\":\"model_update\"}','2026-08-12 21:48:10','2026-08-12 21:48:10'),
(102,'App\\Models\\Customer',321,'customers','updated','5','user','Shofiq','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\\nConnection ID: tisha\\nProfile: 30 MB Saifulkst\\nService: pppoe\\nRouter comment: Anike\\n[2026-08-12 21:42] Activated package to 2026-08-13 via quick-activate action.\\n[2026-08-12 21:47] Paid validity: payment date 2026-08-12; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-12 to 2026-09-11.\",\"service_valid_from\":\"2026-08-12 00:00:00\",\"service_valid_until\":\"2026-09-11 00:00:00\",\"service_validity_note\":\"[2026-08-12 21:47] Paid validity: payment date 2026-08-12; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-12 to 2026-09-11.\"}','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\\nConnection ID: tisha\\nProfile: 30 MB Saifulkst\\nService: pppoe\\nRouter comment: Anike\\n[2026-08-12 21:42] Activated package to 2026-08-13 via quick-activate action.\\n[2026-08-12 21:47] Paid validity: payment date 2026-08-12; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-08-12 to 2026-09-11.\\n[2026-08-12 21:48] Paid validity extended for 2 month(s): 2026-09-12 to 2026-11-11. Note: Automatic renewal from advance balance for remembered package.\",\"service_valid_from\":\"2026-09-12 00:00:00\",\"service_valid_until\":\"2026-11-11 00:00:00\",\"service_validity_note\":\"[2026-08-12 21:48] Paid validity extended for 2 month(s): 2026-09-12 to 2026-11-11. Note: Automatic renewal from advance balance for remembered package.\"}','[\"notes\",\"service_valid_from\",\"service_valid_until\",\"service_validity_note\"]','{\"source\":\"model_update\"}','2026-08-12 21:48:10','2026-08-12 21:48:10'),
(103,'App\\Models\\Customer',347,'customers','updated','5','user','Shofiq','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\nConnection ID: ss_brrirs171\\nProfile: 30 MB Lgedks\\nService: pppoe\\nRouter comment: none\",\"status\":\"active\",\"service_validity_note\":null}','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\nConnection ID: ss_brrirs171\\nProfile: 30 MB Lgedks\\nService: pppoe\\nRouter comment: none\\n[2026-08-12 21:53] Service temporarily force-inactivated while validity remained not set. Reason: test\",\"status\":\"inactive\",\"service_validity_note\":\"[2026-08-12 21:53] Service temporarily force-inactivated while validity remained not set. Reason: test\"}','[\"notes\",\"status\",\"service_validity_note\"]','{\"source\":\"model_update\"}','2026-08-12 21:53:39','2026-08-12 21:53:39'),
(104,'App\\Models\\Subscription',347,'subscriptions','updated','5','user','Shofiq','{\"end_date\":null,\"status\":\"active\"}','{\"end_date\":\"2026-08-12 00:00:00\",\"status\":\"inactive\"}','[\"end_date\",\"status\"]','{\"source\":\"model_update\"}','2026-08-12 21:53:39','2026-08-12 21:53:39'),
(105,'App\\Models\\Customer',347,'customers','updated','5','user','Shofiq','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\nConnection ID: ss_brrirs171\\nProfile: 30 MB Lgedks\\nService: pppoe\\nRouter comment: none\\n[2026-08-12 21:53] Service temporarily force-inactivated while validity remained not set. Reason: test\",\"status\":\"inactive\",\"service_valid_from\":null,\"service_valid_until\":null,\"service_validity_note\":\"[2026-08-12 21:53] Service temporarily force-inactivated while validity remained not set. Reason: test\"}','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\nConnection ID: ss_brrirs171\\nProfile: 30 MB Lgedks\\nService: pppoe\\nRouter comment: none\\n[2026-08-12 21:53] Service temporarily force-inactivated while validity remained not set. Reason: test\\n[2026-08-12 21:54] Activated package to 2026-09-12 via quick-activate action.\",\"status\":\"active\",\"service_valid_from\":\"2026-08-12 00:00:00\",\"service_valid_until\":\"2026-09-12 00:00:00\",\"service_validity_note\":\"[2026-08-12 21:54] Activated package to 2026-09-12 via quick-activate action.\"}','[\"notes\",\"status\",\"service_valid_from\",\"service_valid_until\",\"service_validity_note\"]','{\"source\":\"model_update\"}','2026-08-12 21:54:54','2026-08-12 21:54:54'),
(106,'App\\Models\\Subscription',347,'subscriptions','updated','5','user','Shofiq','{\"end_date\":\"2026-08-12 00:00:00\",\"status\":\"inactive\"}','{\"end_date\":null,\"status\":\"active\"}','[\"end_date\",\"status\"]','{\"source\":\"model_update\"}','2026-08-12 21:54:54','2026-08-12 21:54:54'),
(107,'App\\Models\\Customer',349,'customers','updated','5','user','Shofiq','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\nConnection ID: tuku_kutu_vi\\nProfile: 50 Mb_Travelshouse\\nService: any\\nRouter comment: none\",\"service_valid_from\":null,\"service_valid_until\":null,\"service_validity_note\":null}','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\nConnection ID: tuku_kutu_vi\\nProfile: 50 Mb_Travelshouse\\nService: any\\nRouter comment: none\\n[2026-08-12 21:56] Activated package to 2026-09-12 via quick-activate action.\",\"service_valid_from\":\"2026-08-12 00:00:00\",\"service_valid_until\":\"2026-09-12 00:00:00\",\"service_validity_note\":\"[2026-08-12 21:56] Activated package to 2026-09-12 via quick-activate action.\"}','[\"notes\",\"service_valid_from\",\"service_valid_until\",\"service_validity_note\"]','{\"source\":\"model_update\"}','2026-08-12 21:56:24','2026-08-12 21:56:24'),
(108,'App\\Models\\Customer',349,'customers','updated','2','user','Anike10','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\nConnection ID: tuku_kutu_vi\\nProfile: 50 Mb_Travelshouse\\nService: any\\nRouter comment: none\\n[2026-08-12 21:56] Activated package to 2026-09-12 via quick-activate action.\",\"service_valid_until\":\"2026-09-12 00:00:00\",\"service_validity_note\":\"[2026-08-12 21:56] Activated package to 2026-09-12 via quick-activate action.\"}','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\nConnection ID: tuku_kutu_vi\\nProfile: 50 Mb_Travelshouse\\nService: any\\nRouter comment: none\\n[2026-08-12 21:56] Activated package to 2026-09-12 via quick-activate action.\\n[2026-08-12 21:58] Manual validity override: 2026-09-12 \\u2192 2026-08-29. Reason: Paid tk 1000 29\\/6\\/26 for 2 month\",\"service_valid_until\":\"2026-08-29 00:00:00\",\"service_validity_note\":\"[2026-08-12 21:58] Manual validity override: 2026-09-12 \\u2192 2026-08-29. Reason: Paid tk 1000 29\\/6\\/26 for 2 month\"}','[\"notes\",\"service_valid_until\",\"service_validity_note\"]','{\"source\":\"model_update\"}','2026-08-12 21:58:23','2026-08-12 21:58:23'),
(109,'App\\Models\\Customer',350,'customers','updated','5','user','Shofiq','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\nConnection ID: wdb\\nProfile: 100Mb_kpi_comdpt\\nService: pppoe\\nRouter comment: none\",\"service_valid_from\":null,\"service_valid_until\":null,\"service_validity_note\":null}','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\nConnection ID: wdb\\nProfile: 100Mb_kpi_comdpt\\nService: pppoe\\nRouter comment: none\\n[2026-08-12 22:00] Activated package to 2026-09-12 via quick-activate action.\",\"service_valid_from\":\"2026-08-12 00:00:00\",\"service_valid_until\":\"2026-09-12 00:00:00\",\"service_validity_note\":\"[2026-08-12 22:00] Activated package to 2026-09-12 via quick-activate action.\"}','[\"notes\",\"service_valid_from\",\"service_valid_until\",\"service_validity_note\"]','{\"source\":\"model_update\"}','2026-08-12 22:00:34','2026-08-12 22:00:34'),
(110,'App\\Models\\Customer',350,'customers','updated','5','user','Shofiq','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\nConnection ID: wdb\\nProfile: 100Mb_kpi_comdpt\\nService: pppoe\\nRouter comment: none\\n[2026-08-12 22:00] Activated package to 2026-09-12 via quick-activate action.\",\"status\":\"active\",\"service_valid_until\":\"2026-09-12 00:00:00\",\"service_validity_note\":\"[2026-08-12 22:00] Activated package to 2026-09-12 via quick-activate action.\"}','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\nConnection ID: wdb\\nProfile: 100Mb_kpi_comdpt\\nService: pppoe\\nRouter comment: none\\n[2026-08-12 22:00] Activated package to 2026-09-12 via quick-activate action.\\n[2026-08-12 22:01] Manual validity override: 2026-09-12 \\u2192 2026-08-11. Reason: jklk\",\"status\":\"inactive\",\"service_valid_until\":\"2026-08-11 00:00:00\",\"service_validity_note\":\"[2026-08-12 22:01] Manual validity override: 2026-09-12 \\u2192 2026-08-11. Reason: jklk\"}','[\"notes\",\"status\",\"service_valid_until\",\"service_validity_note\"]','{\"source\":\"model_update\"}','2026-08-12 22:01:31','2026-08-12 22:01:31'),
(111,'App\\Models\\Subscription',350,'subscriptions','updated','5','user','Shofiq','{\"end_date\":null,\"status\":\"active\"}','{\"end_date\":\"2026-08-11 00:00:00\",\"status\":\"inactive\"}','[\"end_date\",\"status\"]','{\"source\":\"model_update\"}','2026-08-12 22:01:31','2026-08-12 22:01:31'),
(112,'App\\Models\\Customer',350,'customers','updated','5','user','Shofiq','{\"status\":\"inactive\",\"grace_until\":null,\"grace_days\":null,\"grace_used_at\":null}','{\"status\":\"active\",\"grace_until\":\"2026-08-14 00:00:00\",\"grace_days\":\"2\",\"grace_used_at\":\"2026-08-12 22:01:59\"}','[\"status\",\"grace_until\",\"grace_days\",\"grace_used_at\"]','{\"source\":\"model_update\"}','2026-08-12 22:01:59','2026-08-12 22:01:59'),
(113,'App\\Models\\Subscription',350,'subscriptions','updated','5','user','Shofiq','{\"end_date\":\"2026-08-11 00:00:00\",\"status\":\"inactive\"}','{\"end_date\":null,\"status\":\"active\"}','[\"end_date\",\"status\"]','{\"source\":\"model_update\"}','2026-08-12 22:01:59','2026-08-12 22:01:59'),
(114,'App\\Models\\Customer',350,'customers','updated','5','user','Shofiq','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\nConnection ID: wdb\\nProfile: 100Mb_kpi_comdpt\\nService: pppoe\\nRouter comment: none\\n[2026-08-12 22:00] Activated package to 2026-09-12 via quick-activate action.\\n[2026-08-12 22:01] Manual validity override: 2026-09-12 \\u2192 2026-08-11. Reason: jklk\",\"status\":\"active\",\"service_validity_note\":\"[2026-08-12 22:01] Manual validity override: 2026-09-12 \\u2192 2026-08-11. Reason: jklk\"}','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:45\\nConnection ID: wdb\\nProfile: 100Mb_kpi_comdpt\\nService: pppoe\\nRouter comment: none\\n[2026-08-12 22:00] Activated package to 2026-09-12 via quick-activate action.\\n[2026-08-12 22:01] Manual validity override: 2026-09-12 \\u2192 2026-08-11. Reason: jklk\\n[2026-08-12 22:03] Service temporarily force-inactivated while validity remained 2026-08-11. Reason: test\",\"status\":\"inactive\",\"service_validity_note\":\"[2026-08-12 22:01] Manual validity override: 2026-09-12 \\u2192 2026-08-11. Reason: jklk\\n[2026-08-12 22:03] Service temporarily force-inactivated while validity remained 2026-08-11. Reason: test\"}','[\"notes\",\"status\",\"service_validity_note\"]','{\"source\":\"model_update\"}','2026-08-12 22:03:42','2026-08-12 22:03:42'),
(115,'App\\Models\\Subscription',350,'subscriptions','updated','5','user','Shofiq','{\"end_date\":null,\"status\":\"active\"}','{\"end_date\":\"2026-08-12 00:00:00\",\"status\":\"inactive\"}','[\"end_date\",\"status\"]','{\"source\":\"model_update\"}','2026-08-12 22:03:42','2026-08-12 22:03:42'),
(116,'App\\Models\\InternetPackage',4,'internet_packages','updated','2','user','Anike10','{\"monthly_price\":\"500.00\"}','{\"monthly_price\":\"10.00\"}','[\"monthly_price\"]','{\"source\":\"model_update\"}','2026-08-12 22:13:39','2026-08-12 22:13:39'),
(117,'App\\Models\\Subscription',305,'subscriptions','updated','5','user','Shofiq','{\"internet_package_id\":5}','{\"internet_package_id\":\"4\"}','[\"internet_package_id\"]','{\"source\":\"model_update\"}','2026-08-12 23:03:36','2026-08-12 23:03:36'),
(118,'App\\Models\\Customer',305,'customers','updated','5','user','Shofiq','{\"account_balance\":\"0.00\",\"active_subscription\":{\"customer_id\":305,\"end_date\":null,\"internet_package_id\":5,\"package\":{\"default_ip_pool\":\"StarLink\",\"description\":\"Automatically imported from MikroTik 1036 MikroTik (103.133.200.180). Set the package price before billing.\",\"mikrotik_profile\":\"200 Mb Star\",\"monthly_price\":\"500.00\",\"name\":\"200 Mb Star\",\"speed\":\"10m\\/200m\",\"status\":\"active\"},\"start_date\":\"2026-08-09T18:00:00.000000Z\",\"status\":\"active\"},\"address\":\"Imported from MikroTik 1036 MikroTik\",\"connection_id\":\"tafsircom\",\"email\":null,\"fixed_ip_address\":null,\"grace_days\":null,\"grace_until\":null,\"grace_used_at\":null,\"is_customer\":true,\"is_reseller\":false,\"is_vendor\":false,\"last_connected_at\":null,\"last_connected_ip\":null,\"last_connected_mac\":null,\"learned_ip_address\":null,\"learned_ip_package_id\":null,\"mikrotik_router_id\":1,\"mikrotik_routers\":[{\"api_port\":8787,\"api_status_since\":\"2026-08-10T17:05:51.000000Z\",\"inactive_pppoe_profile\":\"inactive\",\"ip_address\":\"103.133.200.180\",\"last_api_latency_ms\":306,\"last_api_status\":\"online\",\"last_checked_at\":\"2026-08-12T13:32:49.000000Z\",\"last_connection_message\":\"Login successful: RouterOS accepted the saved username \'admin\'.\",\"last_offline_at\":null,\"last_online_at\":\"2026-08-10T17:05:51.000000Z\",\"last_ping_at\":\"2026-08-10T17:05:51.000000Z\",\"last_ping_latency_ms\":59,\"last_ping_status\":\"online\",\"last_pppoe_sync_at\":null,\"last_pppoe_sync_summary\":null,\"name\":\"1036 MikroTik\",\"notes\":null,\"ping_status_since\":\"2026-08-10T17:05:51.000000Z\",\"pppoe_sync_interval_minutes\":60,\"status\":\"active\",\"username\":\"admin\"}],\"mikrotik_username\":\"tafsircom\",\"name\":\"Anike\",\"never_suspend\":false,\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\\nConnection ID: tafsircom\\nProfile: 200 Mb Star\\nService: any\\nRouter comment: Anike\\n[2026-08-12 21:42] Activated package to 2026-09-12 via quick-activate action.\",\"phone\":\"Not provided\",\"reseller_commission_percent\":\"0.00\",\"reseller_daily_payment_limit\":null,\"reseller_id\":null,\"service_valid_from\":\"2026-08-11T18:00:00.000000Z\",\"service_valid_until\":\"2026-09-11T18:00:00.000000Z\",\"service_validity_note\":\"[2026-08-12 21:42] Activated package to 2026-09-12 via quick-activate action.\",\"status\":\"active\",\"use_fixed_ip\":false}','{\"account_balance\":\"0.00\",\"active_subscription\":{\"customer_id\":305,\"end_date\":null,\"internet_package_id\":4,\"package\":{\"default_ip_pool\":\"StarLink\",\"description\":\"Automatically imported from MikroTik 1036 MikroTik (103.133.200.180). Set the package price before billing.\",\"mikrotik_profile\":\"30 Mb Star\",\"monthly_price\":\"10.00\",\"name\":\"30 Mb Star\",\"speed\":\"10m\\/30m\",\"status\":\"active\"},\"start_date\":\"2026-08-09T18:00:00.000000Z\",\"status\":\"active\"},\"address\":\"Imported from MikroTik 1036 MikroTik\",\"connection_id\":\"tafsircom\",\"email\":null,\"fixed_ip_address\":null,\"grace_days\":null,\"grace_until\":null,\"grace_used_at\":null,\"is_customer\":true,\"is_reseller\":false,\"is_vendor\":false,\"last_connected_at\":null,\"last_connected_ip\":null,\"last_connected_mac\":null,\"learned_ip_address\":null,\"learned_ip_package_id\":null,\"mikrotik_router_id\":1,\"mikrotik_routers\":[{\"api_port\":8787,\"api_status_since\":\"2026-08-10T17:05:51.000000Z\",\"inactive_pppoe_profile\":\"inactive\",\"ip_address\":\"103.133.200.180\",\"last_api_latency_ms\":306,\"last_api_status\":\"online\",\"last_checked_at\":\"2026-08-12T13:32:49.000000Z\",\"last_connection_message\":\"Login successful: RouterOS accepted the saved username \'admin\'.\",\"last_offline_at\":null,\"last_online_at\":\"2026-08-10T17:05:51.000000Z\",\"last_ping_at\":\"2026-08-10T17:05:51.000000Z\",\"last_ping_latency_ms\":59,\"last_ping_status\":\"online\",\"last_pppoe_sync_at\":null,\"last_pppoe_sync_summary\":null,\"name\":\"1036 MikroTik\",\"notes\":null,\"ping_status_since\":\"2026-08-10T17:05:51.000000Z\",\"pppoe_sync_interval_minutes\":60,\"status\":\"active\",\"username\":\"admin\"}],\"mikrotik_username\":\"tafsircom\",\"name\":\"Tannisha\",\"never_suspend\":false,\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\\r\\nConnection ID: tafsircom\\r\\nProfile: 200 Mb Star\\r\\nService: any\\r\\nRouter comment: Anike\\r\\n[2026-08-12 21:42] Activated package to 2026-09-12 via quick-activate action.\",\"phone\":\"01972777070\",\"reseller_commission_percent\":\"0.00\",\"reseller_daily_payment_limit\":null,\"reseller_id\":null,\"service_valid_from\":\"2026-08-11T18:00:00.000000Z\",\"service_valid_until\":\"2026-09-11T18:00:00.000000Z\",\"service_validity_note\":\"[2026-08-12 21:42] Activated package to 2026-09-12 via quick-activate action.\",\"status\":\"active\",\"use_fixed_ip\":false}','[\"active_subscription.internet_package_id\",\"active_subscription.package.mikrotik_profile\",\"active_subscription.package.monthly_price\",\"active_subscription.package.name\",\"active_subscription.package.speed\",\"name\",\"notes\",\"phone\"]','{\"source\":\"party_edit\",\"party_name\":\"Tannisha\"}','2026-08-12 23:03:36','2026-08-12 23:03:36'),
(119,'App\\Models\\Customer',305,'customers','updated','5','user','Shofiq','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\\r\\nConnection ID: tafsircom\\r\\nProfile: 200 Mb Star\\r\\nService: any\\r\\nRouter comment: Anike\\r\\n[2026-08-12 21:42] Activated package to 2026-09-12 via quick-activate action.\",\"status\":\"active\",\"service_valid_until\":\"2026-09-12 00:00:00\",\"service_validity_note\":\"[2026-08-12 21:42] Activated package to 2026-09-12 via quick-activate action.\"}','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\\r\\nConnection ID: tafsircom\\r\\nProfile: 200 Mb Star\\r\\nService: any\\r\\nRouter comment: Anike\\r\\n[2026-08-12 21:42] Activated package to 2026-09-12 via quick-activate action.\\n[2026-08-12 23:04] Manual validity override: 2026-09-12 \\u2192 2026-08-11. Reason: test\",\"status\":\"inactive\",\"service_valid_until\":\"2026-08-11 00:00:00\",\"service_validity_note\":\"[2026-08-12 23:04] Manual validity override: 2026-09-12 \\u2192 2026-08-11. Reason: test\"}','[\"notes\",\"status\",\"service_valid_until\",\"service_validity_note\"]','{\"source\":\"model_update\"}','2026-08-12 23:04:59','2026-08-12 23:04:59'),
(120,'App\\Models\\Subscription',305,'subscriptions','updated','5','user','Shofiq','{\"end_date\":null,\"status\":\"active\"}','{\"end_date\":\"2026-08-11 00:00:00\",\"status\":\"inactive\"}','[\"end_date\",\"status\"]','{\"source\":\"model_update\"}','2026-08-12 23:04:59','2026-08-12 23:04:59'),
(121,'App\\Models\\Invoice',12,'invoices','updated','system','system','System','{\"paid_amount\":\"0.00\",\"due_amount\":\"10.00\",\"status\":\"unpaid\"}','{\"paid_amount\":10,\"due_amount\":0,\"status\":\"paid\"}','[\"paid_amount\",\"due_amount\",\"status\"]','{\"source\":\"model_update\"}','2026-08-12 23:05:48','2026-08-12 23:05:48'),
(122,'App\\Models\\Customer',305,'customers','updated','system','system','System','{\"account_balance\":\"0.00\"}','{\"account_balance\":\"2.00\"}','[\"account_balance\"]','{\"source\":\"model_update\"}','2026-08-12 23:05:48','2026-08-12 23:05:48'),
(123,'App\\Models\\Customer',305,'customers','updated','system','system','System','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\\r\\nConnection ID: tafsircom\\r\\nProfile: 200 Mb Star\\r\\nService: any\\r\\nRouter comment: Anike\\r\\n[2026-08-12 21:42] Activated package to 2026-09-12 via quick-activate action.\\n[2026-08-12 23:04] Manual validity override: 2026-09-12 \\u2192 2026-08-11. Reason: test\",\"status\":\"inactive\",\"service_valid_from\":\"2026-08-12 00:00:00\",\"service_valid_until\":\"2026-08-11 00:00:00\",\"service_validity_note\":\"[2026-08-12 23:04] Manual validity override: 2026-09-12 \\u2192 2026-08-11. Reason: test\"}','{\"notes\":\"Imported from MikroTik: 1036 MikroTik (103.133.200.180:8787) at 2026-08-10 23:09:44\\r\\nConnection ID: tafsircom\\r\\nProfile: 200 Mb Star\\r\\nService: any\\r\\nRouter comment: Anike\\r\\n[2026-08-12 21:42] Activated package to 2026-09-12 via quick-activate action.\\n[2026-08-12 23:04] Manual validity override: 2026-09-12 \\u2192 2026-08-11. Reason: test\\n[2026-08-12 23:05] Paid validity: payment date 2026-12-08; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-12-08 to 2027-01-07. Payment note: Auto bKash SMS TrxID: DHC6EBL5IW\",\"status\":\"active\",\"service_valid_from\":\"2026-12-08 00:00:00\",\"service_valid_until\":\"2027-01-07 00:00:00\",\"service_validity_note\":\"[2026-08-12 23:05] Paid validity: payment date 2026-12-08; one-month period 31 day(s); grace deducted 0 day(s); validity 31 day(s), 2026-12-08 to 2027-01-07. Payment note: Auto bKash SMS TrxID: DHC6EBL5IW\"}','[\"notes\",\"status\",\"service_valid_from\",\"service_valid_until\",\"service_validity_note\"]','{\"source\":\"model_update\"}','2026-08-12 23:05:48','2026-08-12 23:05:48'),
(124,'App\\Models\\Subscription',305,'subscriptions','updated','system','system','System','{\"end_date\":\"2026-08-11 00:00:00\",\"status\":\"inactive\"}','{\"end_date\":null,\"status\":\"active\"}','[\"end_date\",\"status\"]','{\"source\":\"model_update\"}','2026-08-12 23:05:48','2026-08-12 23:05:48');
/*!40000 ALTER TABLE `record_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reseller_commission_histories`
--

DROP TABLE IF EXISTS `reseller_commission_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reseller_commission_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reseller_id` bigint(20) unsigned NOT NULL,
  `old_percent` decimal(5,2) DEFAULT NULL,
  `new_percent` decimal(5,2) NOT NULL,
  `changed_by` bigint(20) unsigned DEFAULT NULL,
  `changed_at` timestamp NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reseller_commission_histories_changed_by_foreign` (`changed_by`),
  KEY `reseller_commission_histories_reseller_id_changed_at_index` (`reseller_id`,`changed_at`),
  CONSTRAINT `reseller_commission_histories_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reseller_commission_histories_reseller_id_foreign` FOREIGN KEY (`reseller_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reseller_commission_histories`
--

LOCK TABLES `reseller_commission_histories` WRITE;
/*!40000 ALTER TABLE `reseller_commission_histories` DISABLE KEYS */;
/*!40000 ALTER TABLE `reseller_commission_histories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_user`
--

DROP TABLE IF EXISTS `role_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_user` (
  `role_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`user_id`),
  KEY `role_user_user_id_foreign` (`user_id`),
  CONSTRAINT `role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_user`
--

LOCK TABLES `role_user` WRITE;
/*!40000 ALTER TABLE `role_user` DISABLE KEYS */;
INSERT INTO `role_user` VALUES
(1,2),
(1,5),
(2,5);
/*!40000 ALTER TABLE `role_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`),
  KEY `roles_entry_by_index` (`entry_by`),
  KEY `roles_entry_by_type_index` (`entry_by_type`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES
(1,'system','system','admin','Administrator','2026-07-18 05:55:22','2026-07-18 05:55:22'),
(2,NULL,NULL,'reseller','Reseller','2026-07-21 23:59:13','2026-07-21 23:59:13');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_return_items`
--

DROP TABLE IF EXISTS `sale_return_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_return_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sale_return_id` bigint(20) unsigned NOT NULL,
  `invoice_item_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `serialless_quantity` int(10) unsigned NOT NULL DEFAULT 0,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `serial_numbers` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_return_items_sale_return_id_foreign` (`sale_return_id`),
  KEY `sale_return_items_invoice_item_id_foreign` (`invoice_item_id`),
  KEY `sale_return_items_product_id_foreign` (`product_id`),
  CONSTRAINT `sale_return_items_invoice_item_id_foreign` FOREIGN KEY (`invoice_item_id`) REFERENCES `invoice_items` (`id`),
  CONSTRAINT `sale_return_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sale_return_items_sale_return_id_foreign` FOREIGN KEY (`sale_return_id`) REFERENCES `sale_returns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_return_items`
--

LOCK TABLES `sale_return_items` WRITE;
/*!40000 ALTER TABLE `sale_return_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `sale_return_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_returns`
--

DROP TABLE IF EXISTS `sale_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_returns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `return_no` varchar(255) NOT NULL,
  `return_date` date NOT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `credit_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `invoice_credit_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `advance_credit_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sale_returns_return_no_unique` (`return_no`),
  KEY `sale_returns_invoice_id_foreign` (`invoice_id`),
  KEY `sale_returns_customer_id_foreign` (`customer_id`),
  KEY `sale_returns_entry_by_index` (`entry_by`),
  KEY `sale_returns_entry_by_type_index` (`entry_by_type`),
  CONSTRAINT `sale_returns_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sale_returns_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_returns`
--

LOCK TABLES `sale_returns` WRITE;
/*!40000 ALTER TABLE `sale_returns` DISABLE KEYS */;
/*!40000 ALTER TABLE `sale_returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES
('3YGHevQcXsOSuqwCbmsKsgVUaHiSREUpiMahByWK',NULL,'103.93.34.204','curl/8.14.1','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiUlBXS2o2RG56R3l3Znd0Z2tSa1d6elBHSHF5c3VoQ1pLTkp1U1NUWSI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czoyMToiaHR0cHM6Ly9pc3AudXMuY29tLmJkIjt9czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHBzOi8vaXNwLnVzLmNvbS5iZCI7czo1OiJyb3V0ZSI7czo5OiJkYXNoYm9hcmQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1786558181),
('eBCeyb2UwoBuayO37yUcMHUrajfux0tZF3AAcaLC',NULL,'103.93.34.204','curl/8.14.1','YTozOntzOjY6Il90b2tlbiI7czo0MDoicTkyUk5xR21NdUdjNmtLQVdidE1EYVNaeWhBOWZNdDhmYUV0UzEwQSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHBzOi8vaXNwLnVzLmNvbS5iZC9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1786558183),
('TFwHKl8adePp7IbqFPO06e5k1yeFnE868wJDaQ5r',NULL,'103.93.34.204','curl/8.14.1','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiU1d0VGJTY1BBUjdqMmM0cU1CV1FCaFB4b2dnTFBjM0x1U1c1UUlIYyI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czoyMToiaHR0cHM6Ly9pc3AudXMuY29tLmJkIjt9czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHBzOi8vaXNwLnVzLmNvbS5iZCI7czo1OiJyb3V0ZSI7czo5OiJkYXNoYm9hcmQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1786558181),
('UhbqBgQA0l4ALlaqYL03yiQKiFExmgHpnVV8NGX1',5,'103.237.37.219','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiYkZ3ZERuTWczQXJLM0VOZ1pNaWE5YWd6NFRwVDBrQnBBN0JVZ3o4YiI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NTtzOjk6Il9wcmV2aW91cyI7YToyOntzOjM6InVybCI7czozNToiaHR0cHM6Ly9pc3AudXMuY29tLmJkL2N1c3RvbWVycy8zMDUiO3M6NToicm91dGUiO3M6MTQ6ImN1c3RvbWVycy5zaG93Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1786554370),
('vQ4NLOfjEnA2WrQbkPL41x7BbnPqtBLEapO8QbXz',2,'162.4.7.22','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','YTo1OntzOjY6Il90b2tlbiI7czo0MDoiS1pNbWlQMTBySHlBQUNnOUhseUNxSG9vUDA5TFI1SGpjYW51RnVJdCI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjMxOiJodHRwczovL2lzcC51cy5jb20uYmQvY3VzdG9tZXJzIjtzOjU6InJvdXRlIjtzOjE1OiJjdXN0b21lcnMuaW5kZXgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToyO30=',1786559147);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_movements`
--

DROP TABLE IF EXISTS `stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `related_warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `serialless_quantity` int(10) unsigned NOT NULL DEFAULT 0,
  `serial_numbers` text DEFAULT NULL,
  `balance_before` int(10) unsigned DEFAULT NULL,
  `balance_after` int(10) unsigned DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_movements_product_id_foreign` (`product_id`),
  KEY `stock_movements_entry_by_index` (`entry_by`),
  KEY `stock_movements_entry_by_type_index` (`entry_by_type`),
  KEY `stock_movements_warehouse_id_foreign` (`warehouse_id`),
  KEY `stock_movements_related_warehouse_id_foreign` (`related_warehouse_id`),
  CONSTRAINT `stock_movements_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_movements_related_warehouse_id_foreign` FOREIGN KEY (`related_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_movements_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_movements`
--

LOCK TABLES `stock_movements` WRITE;
/*!40000 ALTER TABLE `stock_movements` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `internet_package_id` bigint(20) unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscriptions_customer_id_foreign` (`customer_id`),
  KEY `subscriptions_internet_package_id_foreign` (`internet_package_id`),
  KEY `subscriptions_entry_by_index` (`entry_by`),
  KEY `subscriptions_entry_by_type_index` (`entry_by_type`),
  CONSTRAINT `subscriptions_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subscriptions_internet_package_id_foreign` FOREIGN KEY (`internet_package_id`) REFERENCES `internet_packages` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=357 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscriptions`
--

LOCK TABLES `subscriptions` WRITE;
/*!40000 ALTER TABLE `subscriptions` DISABLE KEYS */;
INSERT INTO `subscriptions` VALUES
(1,'2','user',1,2,'2026-08-10',NULL,'inactive','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(2,'2','user',2,11,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(3,'2','user',3,10,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(4,'2','user',4,17,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(5,'2','user',5,11,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(6,'2','user',6,18,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(7,'2','user',7,11,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(8,'2','user',8,7,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(9,'2','user',9,13,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(10,'2','user',10,10,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(11,'2','user',11,5,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(12,'2','user',12,4,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(13,'2','user',13,14,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(14,'2','user',14,13,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(15,'2','user',15,14,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(16,'2','user',16,4,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(17,'2','user',17,9,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(18,'2','user',18,14,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(19,'2','user',19,12,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(20,'2','user',20,14,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(21,'2','user',21,12,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(22,'2','user',22,12,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(23,'2','user',23,12,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(24,'2','user',24,12,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(25,'2','user',25,12,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(26,'2','user',26,8,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(27,'2','user',27,12,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(28,'2','user',28,12,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(29,'2','user',29,12,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(30,'2','user',30,12,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(31,'2','user',31,8,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(32,'2','user',32,8,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(33,'2','user',33,4,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(34,'2','user',34,7,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(35,'2','user',35,12,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(36,'2','user',36,12,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(37,'2','user',37,13,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(38,'2','user',38,12,'2026-08-10',NULL,'inactive','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(39,'2','user',39,12,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(40,'2','user',40,8,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(41,'2','user',41,7,'2026-08-10',NULL,'active','2026-08-10 23:08:17','2026-08-10 23:08:17'),
(42,'2','user',42,11,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(43,'2','user',43,14,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(44,'2','user',44,8,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(45,'2','user',45,8,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(46,'2','user',46,14,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(47,'2','user',47,8,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(48,'2','user',48,8,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(49,'2','user',49,4,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(50,'2','user',50,13,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(51,'2','user',51,11,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(52,'2','user',52,11,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(53,'2','user',53,4,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(54,'2','user',54,10,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(55,'2','user',55,10,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(56,'2','user',56,14,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(57,'2','user',57,13,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:19'),
(58,'2','user',58,8,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(59,'2','user',59,4,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(60,'2','user',60,8,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(61,'2','user',61,8,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(62,'2','user',62,10,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(63,'2','user',63,11,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(64,'2','user',64,11,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(65,'2','user',65,7,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(66,'2','user',66,11,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(67,'2','user',67,11,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(68,'2','user',68,11,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(69,'2','user',69,11,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(70,'2','user',70,13,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(71,'2','user',71,18,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(72,'2','user',72,13,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(73,'2','user',73,15,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(74,'2','user',74,14,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(75,'2','user',75,14,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(76,'2','user',76,14,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(77,'2','user',77,14,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(78,'2','user',78,14,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(79,'2','user',79,8,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(80,'2','user',80,18,'2026-08-10',NULL,'inactive','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(81,'2','user',81,18,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(82,'2','user',82,13,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(83,'2','user',83,7,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(84,'2','user',84,17,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(85,'2','user',85,15,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(86,'2','user',86,14,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(87,'2','user',87,11,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(88,'2','user',88,15,'2026-08-10',NULL,'active','2026-08-10 23:08:18','2026-08-10 23:08:18'),
(89,'2','user',89,12,'2026-08-10',NULL,'active','2026-08-10 23:08:19','2026-08-10 23:08:19'),
(90,'2','user',90,12,'2026-08-10',NULL,'active','2026-08-10 23:08:19','2026-08-10 23:08:19'),
(91,'2','user',91,12,'2026-08-10',NULL,'active','2026-08-10 23:08:19','2026-08-10 23:08:19'),
(92,'2','user',92,12,'2026-08-10',NULL,'active','2026-08-10 23:08:19','2026-08-10 23:08:19'),
(93,'2','user',93,13,'2026-08-10',NULL,'active','2026-08-10 23:08:19','2026-08-10 23:08:19'),
(94,'2','user',94,12,'2026-08-10',NULL,'active','2026-08-10 23:08:19','2026-08-10 23:08:19'),
(95,'2','user',95,21,'2026-08-10',NULL,'active','2026-08-10 23:08:19','2026-08-10 23:08:19'),
(96,'2','user',96,21,'2026-08-10',NULL,'active','2026-08-10 23:08:19','2026-08-10 23:08:19'),
(97,'2','user',97,21,'2026-08-10',NULL,'active','2026-08-10 23:08:19','2026-08-10 23:08:19'),
(98,'2','user',98,12,'2026-08-10',NULL,'active','2026-08-10 23:08:19','2026-08-10 23:08:19'),
(99,'2','user',99,21,'2026-08-10',NULL,'active','2026-08-10 23:08:19','2026-08-10 23:08:19'),
(100,'2','user',100,11,'2026-08-10',NULL,'active','2026-08-10 23:08:54','2026-08-10 23:08:54'),
(101,'2','user',101,11,'2026-08-10',NULL,'active','2026-08-10 23:08:54','2026-08-10 23:08:54'),
(102,'2','user',102,6,'2026-08-10',NULL,'active','2026-08-10 23:08:54','2026-08-10 23:08:54'),
(103,'2','user',103,19,'2026-08-10',NULL,'active','2026-08-10 23:08:54','2026-08-10 23:08:54'),
(104,'2','user',104,3,'2026-08-10',NULL,'inactive','2026-08-10 23:08:54','2026-08-10 23:08:54'),
(105,'2','user',105,6,'2026-08-10',NULL,'active','2026-08-10 23:08:54','2026-08-10 23:08:54'),
(106,'2','user',106,6,'2026-08-10',NULL,'active','2026-08-10 23:08:54','2026-08-10 23:08:54'),
(107,'2','user',107,6,'2026-08-10',NULL,'active','2026-08-10 23:08:54','2026-08-10 23:08:54'),
(108,'2','user',108,2,'2026-08-10',NULL,'inactive','2026-08-10 23:08:54','2026-08-10 23:08:54'),
(109,'2','user',109,6,'2026-08-10',NULL,'active','2026-08-10 23:08:54','2026-08-10 23:08:54'),
(110,'2','user',110,6,'2026-08-10',NULL,'active','2026-08-10 23:08:54','2026-08-10 23:08:54'),
(111,'2','user',111,3,'2026-08-10',NULL,'inactive','2026-08-10 23:08:54','2026-08-10 23:08:54'),
(112,'2','user',112,6,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(113,'2','user',113,2,'2026-08-10',NULL,'inactive','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(114,'2','user',114,6,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(115,'2','user',115,6,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(116,'2','user',116,6,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(117,'2','user',117,6,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(118,'2','user',118,2,'2026-08-10',NULL,'inactive','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(119,'2','user',119,6,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(120,'2','user',120,5,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(121,'2','user',121,4,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(122,'2','user',122,4,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(123,'2','user',123,12,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(124,'2','user',124,12,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(125,'2','user',125,12,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(126,'2','user',126,12,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(127,'2','user',127,6,'2026-08-10',NULL,'inactive','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(128,'2','user',128,12,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(129,'2','user',129,12,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(130,'2','user',130,12,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(131,'2','user',131,8,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(132,'2','user',132,16,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(133,'2','user',133,14,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(134,'2','user',134,11,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(135,'2','user',135,4,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(136,'2','user',136,4,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(137,'2','user',137,12,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(138,'2','user',138,12,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(139,'2','user',139,12,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(140,'2','user',140,4,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(141,'2','user',141,4,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(142,'2','user',142,4,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(143,'2','user',143,11,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(144,'2','user',144,7,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(145,'2','user',145,13,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(146,'2','user',146,6,'2026-08-10',NULL,'inactive','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(147,'2','user',147,11,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(148,'2','user',148,13,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(149,'2','user',149,4,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(150,'2','user',150,8,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(151,'2','user',151,8,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(152,'2','user',152,8,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(153,'2','user',153,13,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(154,'2','user',154,8,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(155,'2','user',155,12,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(156,'2','user',156,8,'2026-08-10',NULL,'inactive','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(157,'2','user',157,13,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(158,'2','user',158,13,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(159,'2','user',159,13,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(160,'2','user',160,13,'2026-08-10',NULL,'active','2026-08-10 23:08:55','2026-08-10 23:08:55'),
(161,'2','user',161,14,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(162,'2','user',162,14,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(163,'2','user',163,14,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(164,'2','user',164,14,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(165,'2','user',165,14,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(166,'2','user',166,13,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(167,'2','user',167,17,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(168,'2','user',168,17,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(169,'2','user',169,17,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(170,'2','user',170,17,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(171,'2','user',171,17,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(172,'2','user',172,17,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(173,'2','user',173,17,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(174,'2','user',174,17,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(175,'2','user',175,17,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(176,'2','user',176,17,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(177,'2','user',177,17,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(178,'2','user',178,17,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(179,'2','user',179,13,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(180,'2','user',180,21,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(181,'2','user',181,12,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(182,'2','user',182,14,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(183,'2','user',183,13,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(184,'2','user',184,15,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(185,'2','user',185,20,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(186,'2','user',186,19,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(187,'2','user',187,13,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(188,'2','user',188,10,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(189,'2','user',189,21,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(190,'2','user',190,21,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(191,'2','user',191,12,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(192,'2','user',192,21,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(193,'2','user',193,21,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(194,'2','user',194,21,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(195,'2','user',195,21,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(196,'2','user',196,21,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(197,'2','user',197,13,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(198,'2','user',198,21,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(199,'2','user',199,21,'2026-08-10',NULL,'active','2026-08-10 23:08:56','2026-08-10 23:08:56'),
(200,'2','user',200,7,'2026-08-10',NULL,'active','2026-08-10 23:09:26','2026-08-10 23:09:26'),
(201,'2','user',201,11,'2026-08-10',NULL,'active','2026-08-10 23:09:26','2026-08-10 23:09:26'),
(202,'2','user',202,10,'2026-08-10',NULL,'active','2026-08-10 23:09:26','2026-08-10 23:09:26'),
(203,'2','user',203,10,'2026-08-10',NULL,'active','2026-08-10 23:09:26','2026-08-10 23:09:26'),
(204,'2','user',204,18,'2026-08-10',NULL,'active','2026-08-10 23:09:26','2026-08-10 23:09:26'),
(205,'2','user',205,6,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(206,'2','user',206,2,'2026-08-10',NULL,'inactive','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(207,'2','user',207,2,'2026-08-10',NULL,'inactive','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(208,'2','user',208,6,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(209,'2','user',209,6,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(210,'2','user',210,6,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(211,'2','user',211,6,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(212,'2','user',212,6,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(213,'2','user',213,6,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(214,'2','user',214,6,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(215,'2','user',215,6,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(216,'2','user',216,6,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(217,'2','user',217,6,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(218,'2','user',218,6,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(219,'2','user',219,6,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(220,'2','user',220,6,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(221,'2','user',221,10,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(222,'2','user',222,5,'2026-08-10',NULL,'inactive','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(223,'2','user',223,5,'2026-08-10',NULL,'inactive','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(224,'2','user',224,4,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(225,'2','user',225,10,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(226,'2','user',226,8,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(227,'2','user',227,10,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(228,'2','user',228,7,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(229,'2','user',229,13,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(230,'2','user',230,10,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(231,'2','user',231,10,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(232,'2','user',232,10,'2026-08-10',NULL,'inactive','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(233,'2','user',233,10,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(234,'2','user',234,10,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(235,'2','user',235,8,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(236,'2','user',236,15,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(237,'2','user',237,10,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(238,'2','user',238,10,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(239,'2','user',239,11,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(240,'2','user',240,21,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(241,'2','user',241,8,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(242,'2','user',242,7,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(243,'2','user',243,6,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(244,'2','user',244,10,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(245,'2','user',245,11,'2026-08-10',NULL,'inactive','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(246,'2','user',246,14,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(247,'2','user',247,13,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(248,'2','user',248,8,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(249,'2','user',249,8,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(250,'2','user',250,4,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(251,'2','user',251,10,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(252,'2','user',252,10,'2026-08-10',NULL,'active','2026-08-10 23:09:27','2026-08-10 23:09:27'),
(253,'2','user',253,7,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(254,'2','user',254,8,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(255,'2','user',255,15,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(256,'2','user',256,4,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(257,'2','user',257,8,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(258,'2','user',258,11,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(259,'2','user',259,11,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(260,'2','user',260,11,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(261,'2','user',261,21,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(262,'2','user',262,12,'2026-08-10',NULL,'inactive','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(263,'2','user',263,13,'2026-08-10',NULL,'inactive','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(264,'2','user',264,11,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(265,'2','user',265,13,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(266,'2','user',266,13,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(267,'2','user',267,13,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(268,'2','user',268,13,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(269,'2','user',269,13,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(270,'2','user',270,13,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(271,'2','user',271,15,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(272,'2','user',272,14,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(273,'2','user',273,14,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(274,'2','user',274,15,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(275,'2','user',275,14,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(276,'2','user',276,14,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(277,'2','user',277,14,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(278,'2','user',278,13,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(279,'2','user',279,7,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(280,'2','user',280,13,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(281,'2','user',281,13,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(282,'2','user',282,16,'2026-08-10',NULL,'inactive','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(283,'2','user',283,14,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(284,'2','user',284,13,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(285,'2','user',285,15,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(286,'2','user',286,13,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(287,'2','user',287,13,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(288,'2','user',288,13,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(289,'2','user',289,21,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(290,'2','user',290,12,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(291,'2','user',291,10,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(292,'2','user',292,21,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(293,'2','user',293,21,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(294,'2','user',294,21,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(295,'2','user',295,21,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(296,'2','user',296,12,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(297,'2','user',297,14,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(298,'2','user',298,18,'2026-08-10',NULL,'active','2026-08-10 23:09:28','2026-08-10 23:09:28'),
(299,'2','user',299,19,'2026-08-10',NULL,'active','2026-08-10 23:09:29','2026-08-10 23:09:29'),
(300,'2','user',300,11,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(301,'2','user',301,4,'2026-08-10',NULL,'inactive','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(302,'2','user',302,5,'2026-08-10',NULL,'inactive','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(303,'2','user',303,5,'2026-08-10',NULL,'inactive','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(304,'2','user',304,15,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(305,'2','user',305,4,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-12 23:05:48'),
(306,'2','user',306,8,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(307,'2','user',307,10,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(308,'2','user',308,10,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(309,'2','user',309,10,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(310,'2','user',310,10,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(311,'2','user',311,10,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(312,'2','user',312,10,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(313,'2','user',313,10,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(314,'2','user',314,10,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(315,'2','user',315,10,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(316,'2','user',316,14,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(317,'2','user',317,14,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(318,'2','user',318,10,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(319,'2','user',319,10,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(320,'2','user',320,16,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(321,'2','user',321,10,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(322,'2','user',322,8,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(323,'2','user',323,4,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(324,'2','user',324,8,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(325,'2','user',325,8,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(326,'2','user',326,13,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(327,'2','user',327,8,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(328,'2','user',328,8,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(329,'2','user',329,4,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(330,'2','user',330,8,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(331,'2','user',331,12,'2026-08-10',NULL,'inactive','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(332,'2','user',332,15,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(333,'2','user',333,8,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(334,'2','user',334,4,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(335,'2','user',335,21,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(336,'2','user',336,10,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(337,'2','user',337,8,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(338,'2','user',338,13,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(339,'2','user',339,11,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(340,'2','user',340,11,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(341,'2','user',341,11,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(342,'2','user',342,13,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(343,'2','user',343,15,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(344,'2','user',344,14,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(345,'2','user',345,14,'2026-08-10',NULL,'active','2026-08-10 23:09:44','2026-08-10 23:09:44'),
(346,'2','user',346,13,'2026-08-10',NULL,'active','2026-08-10 23:09:45','2026-08-10 23:09:45'),
(347,'2','user',347,13,'2026-08-10',NULL,'active','2026-08-10 23:09:45','2026-08-12 21:54:54'),
(348,'2','user',348,13,'2026-08-10',NULL,'active','2026-08-10 23:09:45','2026-08-12 21:30:51'),
(349,'2','user',349,7,'2026-08-10',NULL,'active','2026-08-10 23:09:45','2026-08-10 23:09:45'),
(350,'2','user',350,19,'2026-08-10','2026-08-12','inactive','2026-08-10 23:09:45','2026-08-12 22:03:42'),
(351,'2','user',351,15,'2026-08-10',NULL,'active','2026-08-10 23:09:45','2026-08-10 23:09:45'),
(352,'2','user',352,14,'2026-08-10',NULL,'active','2026-08-10 23:09:45','2026-08-10 23:09:45'),
(353,'2','user',353,13,'2026-08-10',NULL,'active','2026-08-10 23:09:45','2026-08-10 23:09:45'),
(354,'2','user',354,20,'2026-08-10',NULL,'active','2026-08-10 23:09:45','2026-08-10 23:09:45'),
(355,'2','user',355,12,'2026-08-10',NULL,'active','2026-08-10 23:09:45','2026-08-10 23:09:45'),
(356,'2','user',356,12,'2026-08-10',NULL,'active','2026-08-10 23:09:45','2026-08-10 23:09:45');
/*!40000 ALTER TABLE `subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `support_tickets`
--

DROP TABLE IF EXISTS `support_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_tickets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `priority` varchar(255) NOT NULL DEFAULT 'normal',
  `status` varchar(255) NOT NULL DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `support_tickets_customer_id_foreign` (`customer_id`),
  KEY `support_tickets_assigned_to_foreign` (`assigned_to`),
  KEY `support_tickets_entry_by_index` (`entry_by`),
  KEY `support_tickets_entry_by_type_index` (`entry_by_type`),
  CONSTRAINT `support_tickets_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `support_tickets_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_tickets`
--

LOCK TABLES `support_tickets` WRITE;
/*!40000 ALTER TABLE `support_tickets` DISABLE KEYS */;
/*!40000 ALTER TABLE `support_tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `used_product_warehouse_stocks`
--

DROP TABLE IF EXISTS `used_product_warehouse_stocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `used_product_warehouse_stocks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `quantity` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `used_product_warehouse_stocks_product_id_warehouse_id_unique` (`product_id`,`warehouse_id`),
  KEY `used_product_warehouse_stocks_warehouse_id_foreign` (`warehouse_id`),
  CONSTRAINT `used_product_warehouse_stocks_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `used_product_warehouse_stocks_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `used_product_warehouse_stocks`
--

LOCK TABLES `used_product_warehouse_stocks` WRITE;
/*!40000 ALTER TABLE `used_product_warehouse_stocks` DISABLE KEYS */;
/*!40000 ALTER TABLE `used_product_warehouse_stocks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_menu_accesses`
--

DROP TABLE IF EXISTS `user_menu_accesses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_menu_accesses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `menu_key` varchar(100) NOT NULL,
  `allowed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_menu_accesses_user_id_menu_key_unique` (`user_id`,`menu_key`),
  CONSTRAINT `user_menu_accesses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=209 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_menu_accesses`
--

LOCK TABLES `user_menu_accesses` WRITE;
/*!40000 ALTER TABLE `user_menu_accesses` DISABLE KEYS */;
INSERT INTO `user_menu_accesses` VALUES
(1,4,'dashboard',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(2,4,'reseller_portal',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(3,4,'packages',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(4,4,'mikrotik_routers',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(5,4,'ip_pools',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(6,4,'network_map',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(7,4,'olt_onus',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(8,4,'onu_deny_list',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(9,4,'onu_auto_discovery',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(10,4,'olt_protocol_profiles',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(11,4,'parties',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(12,4,'resellers',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(13,4,'invoices',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(14,4,'create_invoice',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(15,4,'sale_returns',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(16,4,'quotations',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(17,4,'create_quotation',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(18,4,'payment_note_default',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(19,4,'organizations',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(20,4,'print_history',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(21,4,'payments',0,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(22,4,'bkash_sms',0,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(23,4,'payment_accounts',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(24,4,'accounting_ledger',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(25,4,'employees',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(26,4,'expenses',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(27,4,'tickets',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(28,4,'products',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(29,4,'in_house_use',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(30,4,'employee_asset_report',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(31,4,'returned_used_stock',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(32,4,'in_house_history',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(33,4,'warehouses',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(34,4,'stock_transfer',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(35,4,'stock_history',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(36,4,'product_categories',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(37,4,'purchase_bills',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(38,4,'warranty_claims',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(39,4,'new_warranty_claim',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(40,4,'fleet_vehicles',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(41,4,'fleet_add_vehicle',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(42,4,'fleet_maintenance_schedules',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(43,4,'fleet_log_maintenance',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(44,4,'fleet_settings',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(45,4,'fleet_reports',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(46,4,'fleet_expense_report',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(47,4,'fleet_maintenance_report',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(48,4,'fleet_due_report',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(49,4,'fleet_duty_history',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(50,4,'users',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(51,4,'roles',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(52,4,'database_backup',1,'2026-08-11 21:52:48','2026-08-11 21:52:48'),
(53,5,'dashboard',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(54,5,'reseller_portal',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(55,5,'packages',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(56,5,'mikrotik_routers',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(57,5,'ip_pools',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(58,5,'network_map',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(59,5,'olt_onus',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(60,5,'onu_deny_list',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(61,5,'onu_auto_discovery',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(62,5,'olt_protocol_profiles',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(63,5,'parties',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(64,5,'resellers',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(65,5,'invoices',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(66,5,'create_invoice',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(67,5,'sale_returns',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(68,5,'quotations',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(69,5,'create_quotation',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(70,5,'payment_note_default',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(71,5,'organizations',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(72,5,'print_history',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(73,5,'payments',0,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(74,5,'bkash_sms',0,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(75,5,'payment_accounts',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(76,5,'accounting_ledger',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(77,5,'employees',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(78,5,'expenses',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(79,5,'tickets',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(80,5,'products',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(81,5,'in_house_use',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(82,5,'employee_asset_report',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(83,5,'returned_used_stock',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(84,5,'in_house_history',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(85,5,'warehouses',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(86,5,'stock_transfer',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(87,5,'stock_history',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(88,5,'product_categories',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(89,5,'purchase_bills',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(90,5,'warranty_claims',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(91,5,'new_warranty_claim',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(92,5,'fleet_vehicles',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(93,5,'fleet_add_vehicle',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(94,5,'fleet_maintenance_schedules',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(95,5,'fleet_log_maintenance',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(96,5,'fleet_settings',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(97,5,'fleet_reports',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(98,5,'fleet_expense_report',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(99,5,'fleet_maintenance_report',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(100,5,'fleet_due_report',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(101,5,'fleet_duty_history',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(102,5,'users',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(103,5,'roles',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(104,5,'database_backup',1,'2026-08-11 21:53:24','2026-08-11 21:53:24'),
(105,6,'dashboard',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(106,6,'reseller_portal',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(107,6,'packages',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(108,6,'mikrotik_routers',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(109,6,'ip_pools',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(110,6,'network_map',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(111,6,'olt_onus',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(112,6,'onu_deny_list',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(113,6,'onu_auto_discovery',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(114,6,'olt_protocol_profiles',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(115,6,'parties',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(116,6,'resellers',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(117,6,'invoices',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(118,6,'create_invoice',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(119,6,'sale_returns',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(120,6,'quotations',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(121,6,'create_quotation',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(122,6,'payment_note_default',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(123,6,'organizations',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(124,6,'print_history',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(125,6,'payments',0,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(126,6,'bkash_sms',0,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(127,6,'payment_accounts',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(128,6,'accounting_ledger',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(129,6,'employees',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(130,6,'expenses',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(131,6,'tickets',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(132,6,'products',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(133,6,'in_house_use',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(134,6,'employee_asset_report',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(135,6,'returned_used_stock',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(136,6,'in_house_history',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(137,6,'warehouses',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(138,6,'stock_transfer',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(139,6,'stock_history',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(140,6,'product_categories',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(141,6,'purchase_bills',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(142,6,'warranty_claims',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(143,6,'new_warranty_claim',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(144,6,'fleet_vehicles',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(145,6,'fleet_add_vehicle',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(146,6,'fleet_maintenance_schedules',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(147,6,'fleet_log_maintenance',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(148,6,'fleet_settings',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(149,6,'fleet_reports',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(150,6,'fleet_expense_report',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(151,6,'fleet_maintenance_report',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(152,6,'fleet_due_report',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(153,6,'fleet_duty_history',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(154,6,'users',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(155,6,'roles',1,'2026-08-11 22:12:53','2026-08-11 22:14:05'),
(156,6,'database_backup',1,'2026-08-11 22:12:53','2026-08-11 22:14:05');
/*!40000 ALTER TABLE `user_menu_accesses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `reseller_id` bigint(20) unsigned DEFAULT NULL,
  `default_payment_method` varchar(255) DEFAULT NULL,
  `default_payment_account_id` bigint(20) unsigned DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_entry_by_index` (`entry_by`),
  KEY `users_entry_by_type_index` (`entry_by_type`),
  KEY `users_reseller_id_foreign` (`reseller_id`),
  KEY `users_default_payment_account_id_foreign` (`default_payment_account_id`),
  CONSTRAINT `users_default_payment_account_id_foreign` FOREIGN KEY (`default_payment_account_id`) REFERENCES `payment_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_reseller_id_foreign` FOREIGN KEY (`reseller_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(2,'system','system','Anike10','anike10@gmail.com',NULL,'$2y$12$C7ubsTzm0lO4E.c8V0yih.XRgnAhHq5.j2Czq0V1NdkVw9QUgB1Q2',NULL,NULL,NULL,'qutZEbHyxsx8M0rlfG1hLYJP03BHPiw1M8YRq2r69McVLFFCsflwegKlKdH9','2026-07-18 05:55:38','2026-07-22 01:23:35'),
(4,'system','system','Arik','arik@arik.com',NULL,'$2y$12$mU2nWThcufxINqdb/GweB.HsA5SctnUfjt9iRNFZMyGVsY/pzHqBO',NULL,NULL,NULL,'WVGEI5w9EdwD6CSi8tahCHzofju7jY1cAzUVRFJw9uI6MPJkP4Pjp4EKGGsg','2026-07-22 01:23:35','2026-08-11 21:52:48'),
(5,'2','user','Shofiq','shofiqulkst@gmail.com',NULL,'$2y$12$SsfYtAMmjAS2ff1emBwcyOd0/lSA3lXBoxGWiOVuOR1yhCEP07Tym',NULL,NULL,NULL,'RJcgHiLYQIrlSUK34r6I0laLdIDgTgSI85HhSjaHy43pdmDxBjHyDmScoTtI','2026-08-10 21:26:24','2026-08-11 21:53:24'),
(6,'2','user','test','t@t.com',NULL,'$2y$12$E0TQ7GSWWUga04UYMacoYuX9xY9QEJgoq50uw2XsgrUdv/tf3K5cq',NULL,NULL,NULL,'dAAEc50pdJAeFaowg8uQuBSnnWuVuuWoIBAhTMW1UOVBEtPufBdqIuupkxix','2026-08-11 22:12:53','2026-08-11 22:14:05');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicle_assignments_history`
--

DROP TABLE IF EXISTS `vehicle_assignments_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicle_assignments_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `duty_role` varchar(30) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `note` text DEFAULT NULL,
  `assigned_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vehicle_assignments_history_assigned_by_foreign` (`assigned_by`),
  KEY `fleet_assignment_vehicle_role_idx` (`vehicle_id`,`duty_role`,`end_date`),
  KEY `fleet_assignment_employee_dates_idx` (`employee_id`,`start_date`,`end_date`),
  CONSTRAINT `vehicle_assignments_history_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vehicle_assignments_history_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  CONSTRAINT `vehicle_assignments_history_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicle_assignments_history`
--

LOCK TABLES `vehicle_assignments_history` WRITE;
/*!40000 ALTER TABLE `vehicle_assignments_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `vehicle_assignments_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicle_expenses`
--

DROP TABLE IF EXISTS `vehicle_expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicle_expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `category` varchar(40) NOT NULL,
  `expense_date` date NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `quantity` decimal(12,3) DEFAULT NULL,
  `unit` varchar(30) DEFAULT NULL,
  `mileage` bigint(20) unsigned DEFAULT NULL,
  `trip_reference` varchar(255) DEFAULT NULL,
  `vendor` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `finalized_at` timestamp NULL DEFAULT NULL,
  `finalized_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vehicle_expenses_employee_id_foreign` (`employee_id`),
  KEY `vehicle_expenses_created_by_foreign` (`created_by`),
  KEY `vehicle_expenses_vehicle_id_expense_date_index` (`vehicle_id`,`expense_date`),
  KEY `vehicle_expenses_category_index` (`category`),
  KEY `vehicle_expenses_expense_date_index` (`expense_date`),
  KEY `vehicle_expenses_finalized_by_foreign` (`finalized_by`),
  CONSTRAINT `vehicle_expenses_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vehicle_expenses_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vehicle_expenses_finalized_by_foreign` FOREIGN KEY (`finalized_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vehicle_expenses_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicle_expenses`
--

LOCK TABLES `vehicle_expenses` WRITE;
/*!40000 ALTER TABLE `vehicle_expenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `vehicle_expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicle_maintenance_items`
--

DROP TABLE IF EXISTS `vehicle_maintenance_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicle_maintenance_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `maintenance_type` varchar(30) NOT NULL,
  `interval_days` int(10) unsigned DEFAULT NULL,
  `interval_mileage` bigint(20) unsigned DEFAULT NULL,
  `last_checked_at` date DEFAULT NULL,
  `last_changed_at` date DEFAULT NULL,
  `last_service_mileage` bigint(20) unsigned DEFAULT NULL,
  `next_due_date` date DEFAULT NULL,
  `next_due_mileage` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicle_maintenance_items_vehicle_id_name_unique` (`vehicle_id`,`name`),
  KEY `fleet_maintenance_due_idx` (`vehicle_id`,`is_active`,`next_due_date`),
  CONSTRAINT `vehicle_maintenance_items_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicle_maintenance_items`
--

LOCK TABLES `vehicle_maintenance_items` WRITE;
/*!40000 ALTER TABLE `vehicle_maintenance_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `vehicle_maintenance_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicle_maintenance_logs`
--

DROP TABLE IF EXISTS `vehicle_maintenance_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicle_maintenance_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_id` bigint(20) unsigned NOT NULL,
  `maintenance_item_id` bigint(20) unsigned DEFAULT NULL,
  `work_name` varchar(255) DEFAULT NULL,
  `action` varchar(30) NOT NULL,
  `service_date` date NOT NULL,
  `mileage` bigint(20) unsigned DEFAULT NULL,
  `cost` decimal(14,2) NOT NULL DEFAULT 0.00,
  `vendor` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `youtube_url` varchar(2048) DEFAULT NULL,
  `finalized_at` timestamp NULL DEFAULT NULL,
  `finalized_by` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vehicle_maintenance_logs_maintenance_item_id_foreign` (`maintenance_item_id`),
  KEY `vehicle_maintenance_logs_created_by_foreign` (`created_by`),
  KEY `vehicle_maintenance_logs_vehicle_id_service_date_index` (`vehicle_id`,`service_date`),
  KEY `vehicle_maintenance_logs_finalized_by_foreign` (`finalized_by`),
  CONSTRAINT `vehicle_maintenance_logs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vehicle_maintenance_logs_finalized_by_foreign` FOREIGN KEY (`finalized_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vehicle_maintenance_logs_maintenance_item_id_foreign` FOREIGN KEY (`maintenance_item_id`) REFERENCES `vehicle_maintenance_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vehicle_maintenance_logs_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicle_maintenance_logs`
--

LOCK TABLES `vehicle_maintenance_logs` WRITE;
/*!40000 ALTER TABLE `vehicle_maintenance_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `vehicle_maintenance_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicle_maintenance_photos`
--

DROP TABLE IF EXISTS `vehicle_maintenance_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicle_maintenance_photos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_maintenance_log_id` bigint(20) unsigned NOT NULL,
  `path` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `size` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vehicle_maintenance_photos_vehicle_maintenance_log_id_foreign` (`vehicle_maintenance_log_id`),
  CONSTRAINT `vehicle_maintenance_photos_vehicle_maintenance_log_id_foreign` FOREIGN KEY (`vehicle_maintenance_log_id`) REFERENCES `vehicle_maintenance_logs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicle_maintenance_photos`
--

LOCK TABLES `vehicle_maintenance_photos` WRITE;
/*!40000 ALTER TABLE `vehicle_maintenance_photos` DISABLE KEYS */;
/*!40000 ALTER TABLE `vehicle_maintenance_photos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicles`
--

DROP TABLE IF EXISTS `vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `registration_no` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `vehicle_type` varchar(255) DEFAULT NULL,
  `make` varchar(255) DEFAULT NULL,
  `model` varchar(255) DEFAULT NULL,
  `year` smallint(5) unsigned DEFAULT NULL,
  `chassis_no` varchar(255) DEFAULT NULL,
  `engine_no` varchar(255) DEFAULT NULL,
  `fuel_type` varchar(30) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `current_mileage` bigint(20) unsigned NOT NULL DEFAULT 0,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicles_registration_no_unique` (`registration_no`),
  UNIQUE KEY `vehicles_chassis_no_unique` (`chassis_no`),
  UNIQUE KEY `vehicles_engine_no_unique` (`engine_no`),
  KEY `vehicles_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicles`
--

LOCK TABLES `vehicles` WRITE;
/*!40000 ALTER TABLE `vehicles` DISABLE KEYS */;
/*!40000 ALTER TABLE `vehicles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouses`
--

DROP TABLE IF EXISTS `warehouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `warehouses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warehouses_code_unique` (`code`),
  KEY `warehouses_entry_by_index` (`entry_by`),
  KEY `warehouses_entry_by_type_index` (`entry_by_type`),
  KEY `warehouses_is_default_index` (`is_default`),
  KEY `warehouses_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouses`
--

LOCK TABLES `warehouses` WRITE;
/*!40000 ALTER TABLE `warehouses` DISABLE KEYS */;
/*!40000 ALTER TABLE `warehouses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warranty_claim_logs`
--

DROP TABLE IF EXISTS `warranty_claim_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `warranty_claim_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `warranty_claim_id` bigint(20) unsigned NOT NULL,
  `old_status` varchar(255) DEFAULT NULL,
  `new_status` varchar(255) NOT NULL,
  `note` text DEFAULT NULL,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `warranty_claim_logs_warranty_claim_id_foreign` (`warranty_claim_id`),
  CONSTRAINT `warranty_claim_logs_warranty_claim_id_foreign` FOREIGN KEY (`warranty_claim_id`) REFERENCES `warranty_claims` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warranty_claim_logs`
--

LOCK TABLES `warranty_claim_logs` WRITE;
/*!40000 ALTER TABLE `warranty_claim_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `warranty_claim_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warranty_claims`
--

DROP TABLE IF EXISTS `warranty_claims`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `warranty_claims` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `claim_no` varchar(255) NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_item_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `product_serial_id` bigint(20) unsigned DEFAULT NULL,
  `claim_date` date NOT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `warranty_status` varchar(255) NOT NULL DEFAULT 'unknown',
  `problem_description` text NOT NULL,
  `diagnosis_note` text DEFAULT NULL,
  `action_type` varchar(255) NOT NULL DEFAULT 'repair',
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `vendor_id` bigint(20) unsigned DEFAULT NULL,
  `replacement_product_id` bigint(20) unsigned DEFAULT NULL,
  `replacement_product_serial_id` bigint(20) unsigned DEFAULT NULL,
  `service_invoice_id` bigint(20) unsigned DEFAULT NULL,
  `service_charge` decimal(12,2) NOT NULL DEFAULT 0.00,
  `resolution_note` text DEFAULT NULL,
  `delivery_note` text DEFAULT NULL,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warranty_claims_claim_no_unique` (`claim_no`),
  KEY `warranty_claims_customer_id_foreign` (`customer_id`),
  KEY `warranty_claims_invoice_id_foreign` (`invoice_id`),
  KEY `warranty_claims_invoice_item_id_foreign` (`invoice_item_id`),
  KEY `warranty_claims_product_id_foreign` (`product_id`),
  KEY `warranty_claims_product_serial_id_foreign` (`product_serial_id`),
  KEY `warranty_claims_assigned_to_foreign` (`assigned_to`),
  KEY `warranty_claims_vendor_id_foreign` (`vendor_id`),
  KEY `warranty_claims_replacement_product_id_foreign` (`replacement_product_id`),
  KEY `warranty_claims_replacement_product_serial_id_foreign` (`replacement_product_serial_id`),
  KEY `warranty_claims_service_invoice_id_foreign` (`service_invoice_id`),
  KEY `warranty_claims_warranty_status_index` (`warranty_status`),
  KEY `warranty_claims_action_type_index` (`action_type`),
  KEY `warranty_claims_status_index` (`status`),
  CONSTRAINT `warranty_claims_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `warranty_claims_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `warranty_claims_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `warranty_claims_invoice_item_id_foreign` FOREIGN KEY (`invoice_item_id`) REFERENCES `invoice_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `warranty_claims_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `warranty_claims_product_serial_id_foreign` FOREIGN KEY (`product_serial_id`) REFERENCES `product_serials` (`id`) ON DELETE SET NULL,
  CONSTRAINT `warranty_claims_replacement_product_id_foreign` FOREIGN KEY (`replacement_product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `warranty_claims_replacement_product_serial_id_foreign` FOREIGN KEY (`replacement_product_serial_id`) REFERENCES `product_serials` (`id`) ON DELETE SET NULL,
  CONSTRAINT `warranty_claims_service_invoice_id_foreign` FOREIGN KEY (`service_invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `warranty_claims_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warranty_claims`
--

LOCK TABLES `warranty_claims` WRITE;
/*!40000 ALTER TABLE `warranty_claims` DISABLE KEYS */;
/*!40000 ALTER TABLE `warranty_claims` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'isp_isp'
--

--
-- Dumping routines for database 'isp_isp'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-12 18:28:22
