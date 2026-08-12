-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: isp_codex
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_ip_pools`
--

LOCK TABLES `app_ip_pools` WRITE;
/*!40000 ALTER TABLE `app_ip_pools` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_ip_pools` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_settings`
--

DROP TABLE IF EXISTS `app_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `app_settings_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_settings`
--

LOCK TABLES `app_settings` WRITE;
/*!40000 ALTER TABLE `app_settings` DISABLE KEYS */;
INSERT INTO `app_settings` VALUES (1,'invoice_payment_note','Please pay the due amount by the due date. Keep this bill for your records.','2026-08-11 15:44:02','2026-08-11 15:44:02');
/*!40000 ALTER TABLE `app_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bkash_sms_payments`
--

DROP TABLE IF EXISTS `bkash_sms_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bkash_sms_payments`
--

LOCK TABLES `bkash_sms_payments` WRITE;
/*!40000 ALTER TABLE `bkash_sms_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `bkash_sms_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_balance_transactions`
--

LOCK TABLES `customer_balance_transactions` WRITE;
/*!40000 ALTER TABLE `customer_balance_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_balance_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_asset_assignments`
--

DROP TABLE IF EXISTS `employee_asset_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `internet_packages`
--

LOCK TABLES `internet_packages` WRITE;
/*!40000 ALTER TABLE `internet_packages` DISABLE KEYS */;
/*!40000 ALTER TABLE `internet_packages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoice_items`
--

DROP TABLE IF EXISTS `invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_04_26_000000_create_isp_management_tables',1),(5,'2026_05_04_000001_update_invoices_allow_multiple_per_month',1),(6,'2026_05_04_000002_create_invoice_items_table',1),(7,'2026_05_06_000001_remove_invoice_type_unique_constraint',1),(8,'2026_05_06_000002_add_vat_to_invoices_table',1),(9,'2026_05_06_000003_create_payment_accounts_table',1),(10,'2026_05_06_000004_add_opening_balance_to_payment_accounts_table',1),(11,'2026_05_06_000005_add_finalized_at_to_invoices_table',1),(12,'2026_05_06_000006_create_roles_and_permissions_tables',1),(13,'2026_05_06_000007_create_default_admin_user',1),(14,'2026_05_06_000008_create_mikrotik_routers_table',1),(15,'2026_05_06_000009_add_mikrotik_login_fields',1),(16,'2026_05_06_000010_add_mikrotik_router_target_to_customers_table',1),(17,'2026_05_06_000011_add_account_balance_to_customers_table',1),(18,'2026_05_06_000012_create_bkash_sms_payments_table',1),(19,'2026_05_06_000013_add_ref_and_allow_duplicate_bkash_sms_trx_ids',1),(20,'2026_05_06_000014_add_unique_ledger_trx_id_to_bkash_sms_payments',1),(21,'2026_05_06_000015_add_connection_status_to_mikrotik_routers_table',1),(22,'2026_05_06_000016_add_status_since_to_mikrotik_routers_table',1),(23,'2026_05_06_000017_add_never_suspend_to_customers_table',1),(24,'2026_05_06_000018_add_pppoe_sync_settings_to_mikrotik_routers_table',1),(25,'2026_05_06_000019_add_grace_period_to_customers_table',1),(26,'2026_05_06_000020_create_payment_allocations_and_customer_balance_transactions',1),(27,'2026_05_06_000021_remap_bkash_sms_payments_to_sms_device_accounts',1),(28,'2026_05_06_000022_add_entry_by_to_application_tables',1),(29,'2026_05_06_000023_add_entry_by_type_and_backfill_entry_by',1),(30,'2026_05_12_000001_create_expenses_table',1),(31,'2026_05_12_000002_add_manage_expenses_permission',1),(32,'2026_05_12_000003_create_employees_and_salary_revisions',1),(33,'2026_05_18_000001_create_olt_onus_table',1),(34,'2026_05_18_000002_create_olt_devices_and_live_fields',1),(35,'2026_05_18_000003_add_access_method_to_olt_devices',1),(36,'2026_05_18_000004_add_read_context_commands_to_olt_devices',1),(37,'2026_05_18_000005_add_pon_ports_to_olt_devices',1),(38,'2026_05_18_000006_convert_olt_tables_to_utf8mb4',1),(39,'2026_05_18_000007_merge_duplicate_olt_onu_live_rows',1),(40,'2026_05_18_000008_clear_stale_olt_parser_errors',1),(41,'2026_05_18_000009_add_olt_onu_register_history_fields',1),(42,'2026_05_18_000010_add_onu_alarm_command_to_olt_devices',1),(43,'2026_05_18_000011_add_brand_profile_to_olt_devices',1),(44,'2026_05_18_000012_add_onu_vlan_command_to_olt_devices',1),(45,'2026_05_18_000013_add_onu_learned_macs',1),(46,'2026_05_18_000014_create_olt_protocol_profiles_table',1),(47,'2026_05_18_000015_update_hsgq_gpon_profile_polling_defaults',1),(48,'2026_05_18_000016_fix_hsgq_gpon_vlan_mac_commands',1),(49,'2026_05_18_000017_add_olt_write_commands_to_protocol_profiles',1),(50,'2026_05_18_000018_set_hsgq_gpon_vlan_write_command',1),(51,'2026_05_18_000019_fix_hsgq_gpon_native_vlan_write_command',1),(52,'2026_05_19_000001_fix_hsgq_gpon_native_vlan_port_id',1),(53,'2026_05_19_000002_restore_hsgq_gpon_context_native_vlan',1),(54,'2026_05_22_000001_add_note_to_olt_onus_table',1),(55,'2026_06_02_000001_add_snmp_polling_to_olt_devices',1),(56,'2026_06_02_000002_make_customer_connection_id_nullable',1),(57,'2026_06_02_000003_add_party_roles_to_customers',1),(58,'2026_06_02_000004_create_purchase_bills_and_product_serials',1),(59,'2026_06_02_000005_add_brand_and_subcategory_to_products',1),(60,'2026_06_02_000006_create_product_categories_table',1),(61,'2026_06_02_000007_add_track_inventory_to_products',1),(62,'2026_06_02_000008_add_barcode_serial_and_warranty_defaults_to_products',1),(63,'2026_06_04_000001_add_product_and_serials_to_invoice_items',1),(64,'2026_06_04_000002_add_service_and_warranty_claims',1),(65,'2026_06_05_000001_create_network_map_features_table',1),(66,'2026_06_15_000001_add_adjustment_inputs_to_invoices_table',1),(67,'2026_06_15_000002_add_notes_to_invoices_table',1),(68,'2026_06_15_000003_create_app_settings_and_add_payment_note_to_invoices',1),(69,'2026_06_19_000001_add_serialless_quantity_to_stock_lines',1),(70,'2026_06_19_000002_add_warehouse_inventory',1),(71,'2026_06_19_000003_add_serial_numbers_to_stock_movements',1),(72,'2026_06_19_000005_create_quotations',1),(73,'2026_07_12_000001_create_record_versions_table',1),(74,'2026_07_12_000002_add_payment_ledger_indexes',1),(75,'2026_07_14_000001_create_sale_returns_table',1),(76,'2026_07_14_000002_add_finalized_at_to_purchase_bills',1),(77,'2026_07_15_000001_create_employee_asset_assignments',1),(78,'2026_07_15_000002_add_value_to_employee_asset_assignments',1),(79,'2026_07_15_000003_add_documents_to_in_house_and_purchase_bills',1),(80,'2026_07_15_000004_create_fleet_management_tables',1),(81,'2026_07_15_000006_add_credit_application_to_sale_returns',1),(82,'2026_07_15_000007_add_credit_total_to_sale_returns',1),(83,'2026_07_15_000008_add_work_name_to_vehicle_maintenance_logs',1),(84,'2026_07_16_000001_add_media_to_vehicle_maintenance_logs',1),(85,'2026_07_16_000001_create_organizations_and_print_logs',1),(86,'2026_07_16_000002_add_print_preferences_and_bank_info_to_organizations',1),(87,'2026_07_16_000003_add_finalization_to_fleet_records',1),(88,'2026_07_18_000001_add_olt_background_refresh_and_port_controls',1),(89,'2026_07_18_000002_fix_hsgq_gpon_hgu_vlan_port_type',1),(90,'2026_07_18_000003_add_ethernet_port_count_to_olt_onus',1),(91,'2026_07_18_000004_add_mikrotik_import_workflow',1),(92,'2026_07_18_000005_create_app_ip_pools',1),(93,'2026_07_18_000006_add_service_validity_to_customers_table',1),(94,'2026_07_18_000007_add_default_ip_pool_to_internet_packages',1),(95,'2026_07_19_000001_add_pppoe_address_tracking_to_customers',1),(96,'2026_07_19_000002_set_default_router_sync_interval_to_sixty_minutes',1),(97,'2026_07_19_000003_add_reseller_wallet_features',1),(98,'2026_07_19_000004_add_reseller_commission_features',1),(99,'2026_08_11_000001_create_permission_user_denials_table',1),(100,'2026_08_11_000002_add_payment_defaults_to_users_table',2);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mikrotik_imported_ip_pools`
--

DROP TABLE IF EXISTS `mikrotik_imported_ip_pools`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mikrotik_imported_ip_pools` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `mikrotik_router_id` bigint(20) unsigned NOT NULL,
  `routeros_id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `ranges` text DEFAULT NULL,
  `next_pool` varchar(255) DEFAULT NULL,
  `source_note` text DEFAULT NULL,
  `imported_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mikrotik_imported_ip_pools_mikrotik_router_id_routeros_id_unique` (`mikrotik_router_id`,`routeros_id`),
  CONSTRAINT `mikrotik_imported_ip_pools_mikrotik_router_id_foreign` FOREIGN KEY (`mikrotik_router_id`) REFERENCES `mikrotik_routers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mikrotik_imported_ip_pools`
--

LOCK TABLES `mikrotik_imported_ip_pools` WRITE;
/*!40000 ALTER TABLE `mikrotik_imported_ip_pools` DISABLE KEYS */;
/*!40000 ALTER TABLE `mikrotik_imported_ip_pools` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mikrotik_imported_profiles`
--

DROP TABLE IF EXISTS `mikrotik_imported_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `imported_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mikrotik_imported_profiles_mikrotik_router_id_routeros_id_unique` (`mikrotik_router_id`,`routeros_id`),
  CONSTRAINT `mikrotik_imported_profiles_mikrotik_router_id_foreign` FOREIGN KEY (`mikrotik_router_id`) REFERENCES `mikrotik_routers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mikrotik_imported_profiles`
--

LOCK TABLES `mikrotik_imported_profiles` WRITE;
/*!40000 ALTER TABLE `mikrotik_imported_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `mikrotik_imported_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mikrotik_imported_secrets`
--

DROP TABLE IF EXISTS `mikrotik_imported_secrets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `imported_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mikrotik_imported_secrets_mikrotik_router_id_routeros_id_unique` (`mikrotik_router_id`,`routeros_id`),
  KEY `mikrotik_imported_secrets_customer_id_foreign` (`customer_id`),
  CONSTRAINT `mikrotik_imported_secrets_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `mikrotik_imported_secrets_mikrotik_router_id_foreign` FOREIGN KEY (`mikrotik_router_id`) REFERENCES `mikrotik_routers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mikrotik_imported_secrets`
--

LOCK TABLES `mikrotik_imported_secrets` WRITE;
/*!40000 ALTER TABLE `mikrotik_imported_secrets` DISABLE KEYS */;
/*!40000 ALTER TABLE `mikrotik_imported_secrets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mikrotik_routers`
--

DROP TABLE IF EXISTS `mikrotik_routers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
INSERT INTO `mikrotik_routers` VALUES (1,'system','system','Main MikroTik','192.168.6.1',8728,60,'admin','eyJpdiI6IlU1dEFHTUdnTXY0VXA0WWh3TkhvVHc9PSIsInZhbHVlIjoibkt2b0M1WitPN0FKYXVuZ0gxbW8zZz09IiwibWFjIjoiNjZlNzAyYWZiNTRiYmU2ODg0Mjc1NmUzNzAxMTcwNDUzYTU2NWVhNWRlZGRjMzgwYmIzYTA2YmEyMWE2OTM5ZiIsInRhZyI6IiJ9','active',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'inactive',NULL,'Default router added from local setup.','2026-08-11 15:43:56','2026-08-11 15:43:56');
/*!40000 ALTER TABLE `mikrotik_routers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `network_map_features`
--

DROP TABLE IF EXISTS `network_map_features`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `olt_protocol_profiles`
--

LOCK TABLES `olt_protocol_profiles` WRITE;
/*!40000 ALTER TABLE `olt_protocol_profiles` DISABLE KEYS */;
INSERT INTO `olt_protocol_profiles` VALUES (1,'hsgq_epon','HSGQ EPON OLT','HSGQ','interface epon {pon_port}','interface onu {pon_port}/{onu_id}',1,1,'enable\nconfig','show onu-info all','show optical-info','show onu-info-alarm {onu_id}','show port-vlan','show mac-address epon all','interface onu {pon_port}/{onu_id}','port-vlan {port} mode tag {vlan} pri {priority}',NULL,NULL,'save','2026-08-11 15:43:59','2026-08-11 15:43:59'),(2,'hsgq_gpon','HSGQ GPON OLT','HSGQ','interface gpon {pon_port}','interface ont {pon_port}/{onu_id}',1,1,'enable\nconfig','show ont-info all','show ont-optical all','show ont-info {onu_id}','show service-port all','show mac-address all','interface gpon {pon_port}','ont port native-vlan {onu_id} {port_path} vlan {vlan} {priority}','interface gpon {pon_port}','ont port attribute {onu_id} eth {port} admin-status {state}','save','2026-08-11 15:43:59','2026-08-11 15:44:07'),(3,'generic_epon','Generic EPON OLT',NULL,'interface epon {pon_port}',NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-08-11 15:43:59','2026-08-11 15:43:59');
/*!40000 ALTER TABLE `olt_protocol_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `olt_refresh_runs`
--

DROP TABLE IF EXISTS `olt_refresh_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `organizations`
--

LOCK TABLES `organizations` WRITE;
/*!40000 ALTER TABLE `organizations` DISABLE KEYS */;
INSERT INTO `organizations` VALUES (1,'Ultimate Solution ISP Manager',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,0,1,1,'2026-08-11 15:44:06','2026-08-11 15:44:06');
/*!40000 ALTER TABLE `organizations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_accounts`
--

LOCK TABLES `payment_accounts` WRITE;
/*!40000 ALTER TABLE `payment_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_allocations`
--

DROP TABLE IF EXISTS `payment_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_allocations`
--

LOCK TABLES `payment_allocations` WRITE;
/*!40000 ALTER TABLE `payment_allocations` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_allocations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permission_role`
--

DROP TABLE IF EXISTS `permission_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
INSERT INTO `permission_role` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(6,1),(7,1),(8,1),(9,1),(10,1),(11,1),(12,1),(13,1),(14,1),(15,1),(16,1),(17,1),(18,1),(19,1),(20,1),(20,2);
/*!40000 ALTER TABLE `permission_role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permission_user`
--

DROP TABLE IF EXISTS `permission_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40000 ALTER TABLE `permission_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permission_user_denials`
--

DROP TABLE IF EXISTS `permission_user_denials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40000 ALTER TABLE `permission_user_denials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
INSERT INTO `permissions` VALUES (1,'system','system','view_dashboard','View dashboard','2026-08-11 15:43:55','2026-08-11 15:43:55'),(2,'system','system','manage_customers','Manage customers','2026-08-11 15:43:55','2026-08-11 15:43:55'),(3,'system','system','manage_packages','Manage packages','2026-08-11 15:43:55','2026-08-11 15:43:55'),(4,'system','system','manage_invoices','Manage invoices','2026-08-11 15:43:55','2026-08-11 15:43:55'),(5,'system','system','finalize_invoices','Finalize invoices','2026-08-11 15:43:55','2026-08-11 15:43:55'),(6,'system','system','manage_payments','Manage payments','2026-08-11 15:43:55','2026-08-11 15:43:55'),(7,'system','system','manage_payment_accounts','Manage payment accounts','2026-08-11 15:43:55','2026-08-11 15:43:55'),(8,'system','system','manage_tickets','Manage tickets','2026-08-11 15:43:55','2026-08-11 15:43:55'),(9,'system','system','manage_products','Manage inventory','2026-08-11 15:43:55','2026-08-11 15:43:55'),(10,'system','system','manage_users','Manage users and permissions','2026-08-11 15:43:55','2026-08-11 15:43:55'),(11,'system','system','download_backup','Download database backup','2026-08-11 15:43:55','2026-08-11 15:43:55'),(12,'system','system','manage_mikrotik_routers','Manage MikroTik routers','2026-08-11 15:43:56','2026-08-11 15:43:56'),(13,NULL,NULL,'manage_expenses','Manage salaries and expenses','2026-08-11 15:43:58','2026-08-11 15:43:58'),(14,NULL,NULL,'view_warranty_claims','View warranty claims','2026-08-11 15:44:02','2026-08-11 15:44:02'),(15,NULL,NULL,'manage_warranty_claims','Manage warranty claims','2026-08-11 15:44:02','2026-08-11 15:44:02'),(16,NULL,NULL,'close_warranty_claims','Close warranty claims','2026-08-11 15:44:02','2026-08-11 15:44:02'),(17,NULL,NULL,'manage_service_products','Manage service products','2026-08-11 15:44:02','2026-08-11 15:44:02'),(18,NULL,NULL,'manage_fleet','Manage vehicles and fleet','2026-08-11 15:44:06','2026-08-11 15:44:06'),(19,NULL,NULL,'manage_resellers','Manage resellers and wallets','2026-08-11 15:44:08','2026-08-11 15:44:08'),(20,NULL,NULL,'use_reseller_portal','Use reseller portal','2026-08-11 15:44:08','2026-08-11 15:44:08');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `print_logs`
--

DROP TABLE IF EXISTS `print_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `print_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `printable_type` varchar(255) DEFAULT NULL,
  `printable_id` bigint(20) unsigned DEFAULT NULL,
  `document_type` varchar(50) NOT NULL,
  `document_no` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `printed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
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
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `record_versions`
--

LOCK TABLES `record_versions` WRITE;
/*!40000 ALTER TABLE `record_versions` DISABLE KEYS */;
INSERT INTO `record_versions` VALUES (1,'App\\Models\\User',7,'users','updated','7','user','Anike Admin','{\"remember_token\":\"[hidden]\"}','{\"remember_token\":\"[hidden]\"}','[\"remember_token\"]','{\"source\":\"model_update\"}','2026-08-12 17:53:46','2026-08-12 17:53:46');
/*!40000 ALTER TABLE `record_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reseller_commission_histories`
--

DROP TABLE IF EXISTS `reseller_commission_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reseller_commission_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reseller_id` bigint(20) unsigned NOT NULL,
  `old_percent` decimal(5,2) DEFAULT NULL,
  `new_percent` decimal(5,2) NOT NULL,
  `changed_by` bigint(20) unsigned DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
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
/*!40101 SET character_set_client = utf8 */;
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
INSERT INTO `role_user` VALUES (1,1),(1,7);
/*!40000 ALTER TABLE `role_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'system','system','admin','Administrator','2026-08-11 15:43:55','2026-08-11 15:43:55'),(2,NULL,NULL,'reseller','Reseller','2026-08-11 15:44:08','2026-08-11 15:44:08');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_return_items`
--

DROP TABLE IF EXISTS `sale_return_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
INSERT INTO `sessions` VALUES ('aAt2gefdD0d33Soo4fiCG95uSB1oy1M3FhxTwgv4',7,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','YTo1OntzOjY6Il90b2tlbiI7czo0MDoidEV1Ym5IbHMwTUg5cHZRcFQ3UHRlTjlOYkRHZ29WZmpBbk9xSXhvNyI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjQ2OiJodHRwOi8vbG9jYWxob3N0L2lzcF9jb2RleC9wdWJsaWMvdXNlcnMvMS9lZGl0IjtzOjU6InJvdXRlIjtzOjEwOiJ1c2Vycy5lZGl0Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6Nzt9',1786463271),('x0qwnIftpSriMEinpSKhDamZfmaDqwlCIJyKvmJU',7,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','YTo1OntzOjY6Il90b2tlbiI7czo0MDoiNG9QSHZqTGVjTndCaEg5NDd1ZTRMa2djeXp3ajYwdlJjWmc3QWlvUSI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjI2OiJodHRwOi8vbG9jYWxob3N0L2lzcF9jb2RleCI7czo1OiJyb3V0ZSI7czo5OiJkYXNoYm9hcmQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTo3O30=',1786558154);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_movements`
--

DROP TABLE IF EXISTS `stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscriptions`
--

LOCK TABLES `subscriptions` WRITE;
/*!40000 ALTER TABLE `subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `support_tickets`
--

DROP TABLE IF EXISTS `support_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_menu_accesses`
--

LOCK TABLES `user_menu_accesses` WRITE;
/*!40000 ALTER TABLE `user_menu_accesses` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_menu_accesses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'system','system','Admin User','admin@example.com',NULL,'$2y$12$o42GzGuoGfHHrh/PFzXbmOEPPzpk4RudNloEy/6VmRoFW5bDYrdpy',NULL,NULL,NULL,NULL,'2026-08-11 15:43:56','2026-08-11 15:43:56'),(7,'system','system','Anike Admin','anike10@gmail.com',NULL,'$2y$12$hqnuOYERsCzd88SDqocyteWnp2peszhiLQXw/QVrKNu9FdI/cAemG',NULL,NULL,NULL,'yADtRw8HHyq4fhmnO8V1z19p1xH0SMH4OaV5uWUNgFScmGYupv23QW297OA2','2026-08-11 15:47:08','2026-08-11 15:47:08');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicle_assignments_history`
--

DROP TABLE IF EXISTS `vehicle_assignments_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouses`
--

LOCK TABLES `warehouses` WRITE;
/*!40000 ALTER TABLE `warehouses` DISABLE KEYS */;
INSERT INTO `warehouses` VALUES (1,'system','system','Main Warehouse','MAIN',NULL,1,1,'2026-08-11 15:44:02','2026-08-11 15:44:02');
/*!40000 ALTER TABLE `warehouses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warranty_claim_logs`
--

DROP TABLE IF EXISTS `warranty_claim_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = utf8 */;
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
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-13  0:29:21
