-- Database backup for fina_isp
-- Generated at 2026-05-17 23:40:07

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `bkash_sms_payments`;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (1, 'Anike Redmi', 'sms_device', NULL, 'SIM1_\nSubId：0\n2026-05-17 15:09:39\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-17 15:09:39', '2026-05-17 15:09:39');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (2, '19999999999', 'sms_device', '19999999999', '19999999999\n[ISP_SMS] Congratulations, the sender test is successful, please continue to add forwarding rules!\nSIM1_TestOperator_18888888888\nSubId：0\n2026-05-17 15:09:55\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-17 15:09:55', '2026-05-17 15:09:55');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (3, 'Extra Offer', 'sms_device', 'Extra Offer', 'Extra Offer\n  আজ রবিতে  নগদ রিচার্জে ২০GB +৩০০মিনিট@৫১৯টাকা, 30 GB+ ৬০০মিনিট@৬৯৯টাকা, ৩০ দিন!\nSIM2_\nSubId：2\n2026-05-15 09:39:43\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-17 15:10:24', '2026-05-17 15:10:24');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (4, 'BDTICKETS', 'sms_device', 'BDTICKETS', 'BDTICKETS\nএই ঈদে দেশব্যাপী সব রুটের বাস টিকেট পেতে ক্লিক cutt.ly/KtBykiUn\nSIM2_\nSubId：2\n2026-05-17 19:31:51\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-17 19:31:51', '2026-05-17 19:31:51');

DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `customer_balance_transactions`;
CREATE TABLE `customer_balance_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `payment_id` bigint(20) unsigned DEFAULT NULL,
  `payment_account_id` bigint(20) unsigned DEFAULT NULL,
  `payment_method` varchar(255) DEFAULT NULL,
  `direction` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) NOT NULL,
  `transaction_date` date NOT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_balance_transactions_customer_id_foreign` (`customer_id`),
  KEY `customer_balance_transactions_payment_id_foreign` (`payment_id`),
  KEY `customer_balance_transactions_payment_account_id_foreign` (`payment_account_id`),
  KEY `customer_balance_transactions_entry_by_index` (`entry_by`),
  KEY `customer_balance_transactions_entry_by_type_index` (`entry_by_type`),
  CONSTRAINT `customer_balance_transactions_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_balance_transactions_payment_account_id_foreign` FOREIGN KEY (`payment_account_id`) REFERENCES `payment_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customer_balance_transactions_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `connection_id` varchar(255) NOT NULL,
  `mikrotik_username` varchar(255) DEFAULT NULL,
  `mikrotik_password` text DEFAULT NULL,
  `mikrotik_router_id` bigint(20) unsigned DEFAULT NULL,
  `address` text NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `never_suspend` tinyint(1) NOT NULL DEFAULT 0,
  `grace_until` date DEFAULT NULL,
  `grace_days` int(10) unsigned DEFAULT NULL,
  `grace_used_at` timestamp NULL DEFAULT NULL,
  `account_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customers_connection_id_unique` (`connection_id`),
  UNIQUE KEY `customers_mikrotik_username_unique` (`mikrotik_username`),
  KEY `customers_phone_index` (`phone`),
  KEY `customers_mikrotik_router_id_foreign` (`mikrotik_router_id`),
  KEY `customers_entry_by_index` (`entry_by`),
  KEY `customers_entry_by_type_index` (`entry_by_type`),
  CONSTRAINT `customers_mikrotik_router_id_foreign` FOREIGN KEY (`mikrotik_router_id`) REFERENCES `mikrotik_routers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `customers` (`id`, `entry_by`, `entry_by_type`, `name`, `phone`, `email`, `connection_id`, `mikrotik_username`, `mikrotik_password`, `mikrotik_router_id`, `address`, `status`, `never_suspend`, `grace_until`, `grace_days`, `grace_used_at`, `account_balance`, `created_at`, `updated_at`) VALUES (1, '2', 'user', 'Fine Fit', '01980078076', NULL, 'AUTO-20260517173213-133', NULL, NULL, NULL, 'Not provided', 'active', 0, NULL, NULL, NULL, '0.00', '2026-05-17 17:32:13', '2026-05-17 17:32:13');
INSERT INTO `customers` (`id`, `entry_by`, `entry_by_type`, `name`, `phone`, `email`, `connection_id`, `mikrotik_username`, `mikrotik_password`, `mikrotik_router_id`, `address`, `status`, `never_suspend`, `grace_until`, `grace_days`, `grace_used_at`, `account_balance`, `created_at`, `updated_at`) VALUES (2, '2', 'user', 'Police Super, Kushtia.', '01713374214', NULL, 'AUTO-20260517224449-104', 'AUTO-20260517224449-104', 'eyJpdiI6ImZXaURVOG9ZOGdVUUY3bzFFWFB1YXc9PSIsInZhbHVlIjoiVFlURURaYnVDcnVIUWRySHo4RHJxQT09IiwibWFjIjoiMjNkMTUzN2Q2MzE4NjNiNDExNjQ3ODYzYjcwYTYwYzI1MzU5OTA0ZjdjMmRiY2I1NDFmZTRlZWNmNzY5NDUxOSIsInRhZyI6IiJ9', NULL, 'Kushtia.', 'active', 0, NULL, NULL, NULL, '0.00', '2026-05-17 22:44:49', '2026-05-17 23:27:36');
INSERT INTO `customers` (`id`, `entry_by`, `entry_by_type`, `name`, `phone`, `email`, `connection_id`, `mikrotik_username`, `mikrotik_password`, `mikrotik_router_id`, `address`, `status`, `never_suspend`, `grace_until`, `grace_days`, `grace_used_at`, `account_balance`, `created_at`, `updated_at`) VALUES (3, '2', 'user', 'Mr Rabby', '01722770880', NULL, 'AUTO-20260517233858-952', NULL, NULL, NULL, '', 'active', 0, NULL, NULL, NULL, '0.00', '2026-05-17 23:38:58', '2026-05-17 23:38:58');

DROP TABLE IF EXISTS `employee_salary_revisions`;
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


DROP TABLE IF EXISTS `employees`;
CREATE TABLE `employees` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `designation` varchar(255) DEFAULT NULL,
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `expenses`;
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
  KEY `expenses_payment_account_id_foreign` (`payment_account_id`),
  KEY `expenses_expense_type_expense_date_index` (`expense_type`,`expense_date`),
  KEY `expenses_category_expense_date_index` (`category`,`expense_date`),
  KEY `expenses_employee_id_foreign` (`employee_id`),
  CONSTRAINT `expenses_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_payment_account_id_foreign` FOREIGN KEY (`payment_account_id`) REFERENCES `payment_accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `failed_jobs`;
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


DROP TABLE IF EXISTS `internet_packages`;
CREATE TABLE `internet_packages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `speed` varchar(255) NOT NULL,
  `mikrotik_profile` varchar(255) DEFAULT NULL,
  `monthly_price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `internet_packages_entry_by_index` (`entry_by`),
  KEY `internet_packages_entry_by_type_index` (`entry_by_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `invoice_items`;
CREATE TABLE `invoice_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_items_invoice_id_foreign` (`invoice_id`),
  KEY `invoice_items_entry_by_index` (`entry_by`),
  KEY `invoice_items_entry_by_type_index` (`entry_by_type`),
  CONSTRAINT `invoice_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=127 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (2, '2', 'user', 1, 'Internet Service May 2026', 1, '1000.00', '1000.00', '2026-05-17 17:32:59', '2026-05-17 17:32:59');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (32, '2', 'user', 2, 'SP office', 1, '1000.00', '1000.00', '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (33, '2', 'user', 2, 'SP Bangalow', 1, '1000.00', '1000.00', '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (34, '2', 'user', 2, 'Addl SP 2', 1, '1000.00', '1000.00', '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (35, '2', 'user', 2, 'Addl SP 3', 1, '1000.00', '1000.00', '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (36, '2', 'user', 2, 'Addl SP 4', 1, '1000.00', '1000.00', '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (37, '2', 'user', 2, 'Accounts office', 1, '1000.00', '1000.00', '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (38, '2', 'user', 2, 'DIO', 1, '1000.00', '1000.00', '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (39, '2', 'user', 2, 'Hospital Doctors', 1, '1000.00', '1000.00', '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (40, '2', 'user', 2, 'Hospital Labs', 1, '1000.00', '1000.00', '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (41, '2', 'user', 2, 'ICT', 1, '1000.00', '1000.00', '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (42, '2', 'user', 2, 'Cyber Crime', 1, '1000.00', '1000.00', '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (43, '2', 'user', 2, 'Reserve Service', 1, '1000.00', '1000.00', '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (44, '2', 'user', 2, 'Reserve PIMS', 1, '1000.00', '1000.00', '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (45, '2', 'user', 2, 'Police Dispass', 1, '1000.00', '1000.00', '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (46, '2', 'user', 2, 'Crime', 1, '800.00', '800.00', '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (47, '2', 'user', 2, 'BIT Police', 1, '800.00', '800.00', '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (48, '2', 'user', 2, 'Cloth', 1, '800.00', '800.00', '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (49, '2', 'user', 2, 'D Store', 1, '800.00', '800.00', '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (50, '2', 'user', 2, 'DB', 1, '800.00', '800.00', '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (51, '2', 'user', 2, 'Conference Room', 1, '800.00', '800.00', '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (52, '2', 'user', 2, 'DSB office', 1, '800.00', '800.00', '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (53, '2', 'user', 2, 'Addl SP 3 Bangalaw', 1, '800.00', '800.00', '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (54, '2', 'user', 2, 'Head Assistant', 1, '800.00', '800.00', '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (55, '2', 'user', 2, 'Addl SP 5 Circle Office', 1, '800.00', '800.00', '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (56, '2', 'user', 2, 'PUNAK', 1, '800.00', '800.00', '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (57, '2', 'user', 2, 'Ration', 1, '800.00', '800.00', '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (58, '2', 'user', 2, 'Reserve RO 1', 1, '800.00', '800.00', '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (59, '2', 'user', 2, 'RI', 1, '800.00', '800.00', '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (60, '2', 'user', 2, 'Stano 1', 1, '800.00', '800.00', '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (61, '2', 'user', 6, 'SP office', 1, '1000.00', '1000.00', '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (62, '2', 'user', 6, 'SP Bangalow', 1, '1000.00', '1000.00', '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (63, '2', 'user', 6, 'Addl SP 2', 1, '1000.00', '1000.00', '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (64, '2', 'user', 6, 'Addl SP 3', 1, '1000.00', '1000.00', '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (65, '2', 'user', 6, 'Addl SP 4', 1, '1000.00', '1000.00', '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (66, '2', 'user', 6, 'Accounts office', 1, '1000.00', '1000.00', '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (67, '2', 'user', 6, 'DIO', 1, '1000.00', '1000.00', '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (68, '2', 'user', 6, 'Hospital Doctors', 1, '1000.00', '1000.00', '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (69, '2', 'user', 6, 'Hospital Labs', 1, '1000.00', '1000.00', '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (70, '2', 'user', 6, 'ICT', 1, '1000.00', '1000.00', '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (71, '2', 'user', 6, 'Cyber Crime', 1, '1000.00', '1000.00', '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (72, '2', 'user', 6, 'Reserve Service', 1, '1000.00', '1000.00', '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (73, '2', 'user', 6, 'Reserve PIMS', 1, '1000.00', '1000.00', '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (74, '2', 'user', 6, 'Police Dispass', 1, '1000.00', '1000.00', '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (75, '2', 'user', 6, 'Crime', 1, '800.00', '800.00', '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (76, '2', 'user', 6, 'BIT Police', 1, '800.00', '800.00', '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (77, '2', 'user', 6, 'Cloth', 1, '800.00', '800.00', '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (78, '2', 'user', 6, 'D Store', 1, '800.00', '800.00', '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (79, '2', 'user', 6, 'DB', 1, '800.00', '800.00', '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (80, '2', 'user', 6, 'Conference Room', 1, '800.00', '800.00', '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (81, '2', 'user', 6, 'DSB office', 1, '800.00', '800.00', '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (82, '2', 'user', 6, 'Addl SP 3 Bangalaw', 1, '800.00', '800.00', '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (83, '2', 'user', 6, 'Head Assistant', 1, '800.00', '800.00', '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (84, '2', 'user', 6, 'Addl SP 5 Circle Office', 1, '800.00', '800.00', '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (85, '2', 'user', 6, 'PUNAK', 1, '800.00', '800.00', '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (86, '2', 'user', 6, 'Ration', 1, '800.00', '800.00', '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (87, '2', 'user', 6, 'Reserve RO 1', 1, '800.00', '800.00', '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (88, '2', 'user', 6, 'RI', 1, '800.00', '800.00', '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (89, '2', 'user', 6, 'Stano 1', 1, '800.00', '800.00', '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (90, '2', 'user', 7, 'SP office', 1, '1000.00', '1000.00', '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (91, '2', 'user', 7, 'SP Bangalow', 1, '1000.00', '1000.00', '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (92, '2', 'user', 7, 'Addl SP 2', 1, '1000.00', '1000.00', '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (93, '2', 'user', 7, 'Addl SP 3', 1, '1000.00', '1000.00', '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (94, '2', 'user', 7, 'Addl SP 4', 1, '1000.00', '1000.00', '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (95, '2', 'user', 7, 'Accounts office', 1, '1000.00', '1000.00', '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (96, '2', 'user', 7, 'DIO', 1, '1000.00', '1000.00', '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (97, '2', 'user', 7, 'Hospital Doctors', 1, '1000.00', '1000.00', '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (98, '2', 'user', 7, 'Hospital Labs', 1, '1000.00', '1000.00', '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (99, '2', 'user', 7, 'ICT', 1, '1000.00', '1000.00', '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (100, '2', 'user', 7, 'Cyber Crime', 1, '1000.00', '1000.00', '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (101, '2', 'user', 7, 'Reserve Service', 1, '1000.00', '1000.00', '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (102, '2', 'user', 7, 'Reserve PIMS', 1, '1000.00', '1000.00', '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (103, '2', 'user', 7, 'Police Dispass', 1, '1000.00', '1000.00', '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (104, '2', 'user', 7, 'Crime', 1, '800.00', '800.00', '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (105, '2', 'user', 7, 'BIT Police', 1, '800.00', '800.00', '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (106, '2', 'user', 7, 'Cloth', 1, '800.00', '800.00', '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (107, '2', 'user', 7, 'D Store', 1, '800.00', '800.00', '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (108, '2', 'user', 7, 'DB', 1, '800.00', '800.00', '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (109, '2', 'user', 7, 'Conference Room', 1, '800.00', '800.00', '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (110, '2', 'user', 7, 'DSB office', 1, '800.00', '800.00', '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (111, '2', 'user', 7, 'Addl SP 3 Bangalaw', 1, '800.00', '800.00', '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (112, '2', 'user', 7, 'Head Assistant', 1, '800.00', '800.00', '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (113, '2', 'user', 7, 'Addl SP 5 Circle Office', 1, '800.00', '800.00', '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (114, '2', 'user', 7, 'PUNAK', 1, '800.00', '800.00', '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (115, '2', 'user', 7, 'Ration', 1, '800.00', '800.00', '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (116, '2', 'user', 7, 'Reserve RO 1', 1, '800.00', '800.00', '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (117, '2', 'user', 7, 'RI', 1, '800.00', '800.00', '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (118, '2', 'user', 7, 'Stano 1', 1, '800.00', '800.00', '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (119, '2', 'user', 8, 'Jinko Solar', 6250, '25.50', '159375.00', '2026-05-17 23:38:58', '2026-05-17 23:38:58');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (120, '2', 'user', 8, 'Growwatt 600 Inverter', 1, '64000.00', '64000.00', '2026-05-17 23:38:58', '2026-05-17 23:38:58');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (121, '2', 'user', 8, 'CTT 48 Volt Battery', 1, '95000.00', '95000.00', '2026-05-17 23:38:58', '2026-05-17 23:38:58');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (122, '2', 'user', 8, 'DC MCB', 2, '1000.00', '2000.00', '2026-05-17 23:38:58', '2026-05-17 23:38:58');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (123, '2', 'user', 8, 'DC SPD', 2, '1400.00', '2800.00', '2026-05-17 23:38:58', '2026-05-17 23:38:58');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (124, '2', 'user', 8, 'DC Battery Cable', 2, '700.00', '1400.00', '2026-05-17 23:38:58', '2026-05-17 23:38:58');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (125, '2', 'user', 8, 'DC Cable', 64, '120.00', '7680.00', '2026-05-17 23:38:58', '2026-05-17 23:38:58');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (126, '2', 'user', 8, 'Cable Lugs', 4, '50.00', '200.00', '2026-05-17 23:38:58', '2026-05-17 23:38:58');

DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `invoice_no` varchar(255) NOT NULL,
  `billing_month` varchar(255) NOT NULL,
  `invoice_type` varchar(255) NOT NULL DEFAULT 'service',
  `subtotal` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `vat` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `due_amount` decimal(10,2) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'unpaid',
  `finalized_at` timestamp NULL DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_invoice_no_unique` (`invoice_no`),
  KEY `invoices_billing_month_index` (`billing_month`),
  KEY `invoices_customer_id_index` (`customer_id`),
  KEY `invoices_entry_by_index` (`entry_by`),
  KEY `invoices_entry_by_type_index` (`entry_by_type`),
  CONSTRAINT `invoices_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `vat`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `created_at`, `updated_at`) VALUES (1, '2', 'user', 1, 'INV-2026-05-00001', '2026-05', 'product', '1000.00', '0.00', '0.00', '1000.00', '1000.00', '0.00', 'paid', '2026-05-17 22:14:07', NULL, '2026-05-17 17:32:13', '2026-05-17 22:14:07');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `vat`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `created_at`, `updated_at`) VALUES (2, '2', 'user', 2, 'INV-2026-05-00002', '2026-04', 'product', '26000.00', '0.00', '0.00', '26000.00', '0.00', '26000.00', 'unpaid', NULL, NULL, '2026-05-17 22:44:49', '2026-05-17 22:50:53');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `vat`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `created_at`, `updated_at`) VALUES (6, '2', 'user', 2, 'INV-2026-05-00002-02', '2026-05', 'product', '26000.00', '0.00', '0.00', '26000.00', '0.00', '26000.00', 'unpaid', NULL, NULL, '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `vat`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `created_at`, `updated_at`) VALUES (7, '2', 'user', 2, 'INV-2026-06-00002', '2026-06', 'product', '26000.00', '0.00', '0.00', '26000.00', '0.00', '26000.00', 'unpaid', NULL, NULL, '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `vat`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `created_at`, `updated_at`) VALUES (8, '2', 'user', 3, 'INV-2026-05-00003', '2026-05', 'product', '332455.00', '0.00', '0.00', '332455.00', '0.00', '332455.00', 'unpaid', NULL, NULL, '2026-05-17 23:38:58', '2026-05-17 23:38:58');

DROP TABLE IF EXISTS `job_batches`;
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


DROP TABLE IF EXISTS `jobs`;
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


DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1, '0001_01_01_000000_create_users_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2, '0001_01_01_000001_create_cache_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3, '0001_01_01_000002_create_jobs_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4, '2026_04_26_000000_create_isp_management_tables', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5, '2026_05_04_000001_update_invoices_allow_multiple_per_month', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6, '2026_05_04_000002_create_invoice_items_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7, '2026_05_06_000001_remove_invoice_type_unique_constraint', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8, '2026_05_06_000002_add_vat_to_invoices_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9, '2026_05_06_000003_create_payment_accounts_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10, '2026_05_06_000004_add_opening_balance_to_payment_accounts_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11, '2026_05_06_000005_add_finalized_at_to_invoices_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12, '2026_05_06_000006_create_roles_and_permissions_tables', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13, '2026_05_06_000007_create_default_admin_user', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14, '2026_05_06_000008_create_mikrotik_routers_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15, '2026_05_06_000009_add_mikrotik_login_fields', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16, '2026_05_06_000010_add_mikrotik_router_target_to_customers_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17, '2026_05_06_000011_add_account_balance_to_customers_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18, '2026_05_06_000012_create_bkash_sms_payments_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19, '2026_05_06_000013_add_ref_and_allow_duplicate_bkash_sms_trx_ids', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20, '2026_05_06_000014_add_unique_ledger_trx_id_to_bkash_sms_payments', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21, '2026_05_06_000015_add_connection_status_to_mikrotik_routers_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22, '2026_05_06_000016_add_status_since_to_mikrotik_routers_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23, '2026_05_06_000017_add_never_suspend_to_customers_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24, '2026_05_06_000018_add_pppoe_sync_settings_to_mikrotik_routers_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25, '2026_05_06_000019_add_grace_period_to_customers_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26, '2026_05_06_000020_create_payment_allocations_and_customer_balance_transactions', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27, '2026_05_06_000021_remap_bkash_sms_payments_to_sms_device_accounts', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28, '2026_05_06_000022_add_entry_by_to_application_tables', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29, '2026_05_06_000023_add_entry_by_type_and_backfill_entry_by', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30, '2026_05_12_000001_create_expenses_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31, '2026_05_12_000002_add_manage_expenses_permission', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32, '2026_05_12_000003_create_employees_and_salary_revisions', 1);

DROP TABLE IF EXISTS `mikrotik_routers`;
CREATE TABLE `mikrotik_routers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `ip_address` varchar(255) NOT NULL,
  `api_port` smallint(5) unsigned NOT NULL DEFAULT 8728,
  `pppoe_sync_interval_minutes` int(10) unsigned NOT NULL DEFAULT 10,
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

INSERT INTO `mikrotik_routers` (`id`, `entry_by`, `entry_by_type`, `name`, `ip_address`, `api_port`, `pppoe_sync_interval_minutes`, `username`, `password`, `status`, `last_api_status`, `api_status_since`, `last_ping_status`, `ping_status_since`, `last_api_latency_ms`, `last_ping_latency_ms`, `last_checked_at`, `last_online_at`, `last_offline_at`, `last_ping_at`, `last_connection_message`, `last_pppoe_sync_at`, `inactive_pppoe_profile`, `last_pppoe_sync_summary`, `notes`, `created_at`, `updated_at`) VALUES (1, 'system', 'system', 'Main MikroTik', '192.168.6.1', 8728, 10, 'admin', 'eyJpdiI6InA2V0VleXBFYVd2MHVhLzE3cXZUOXc9PSIsInZhbHVlIjoiSjBsM3A1K3l3OVBpQnVIYWtOR0h3dz09IiwibWFjIjoiYjQyNzAzOTQ3MTEwNTcyODEzYmRiZjBkNTMzZTAzNTg1OTEyNTA2MTk1YjYxMWIwNTYyOWE4YTk3ZDE5OWExYyIsInRhZyI6IiJ9', 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'inactive', NULL, 'Default router added from local setup.', '2026-05-17 14:55:12', '2026-05-17 14:55:12');

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `payment_accounts`;
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


DROP TABLE IF EXISTS `payment_allocations`;
CREATE TABLE `payment_allocations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `payment_id` bigint(20) unsigned DEFAULT NULL,
  `source_type` varchar(255) NOT NULL DEFAULT 'payment',
  `amount` decimal(10,2) NOT NULL,
  `allocated_at` date NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_allocations_customer_id_foreign` (`customer_id`),
  KEY `payment_allocations_invoice_id_foreign` (`invoice_id`),
  KEY `payment_allocations_payment_id_foreign` (`payment_id`),
  KEY `payment_allocations_entry_by_index` (`entry_by`),
  KEY `payment_allocations_entry_by_type_index` (`entry_by_type`),
  CONSTRAINT `payment_allocations_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payment_allocations_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payment_allocations_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `payment_allocations` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `payment_id`, `source_type`, `amount`, `allocated_at`, `note`, `created_at`, `updated_at`) VALUES (1, '2', 'user', 1, 1, 1, 'payment', '1000.00', '2026-05-17', NULL, '2026-05-17 22:13:40', '2026-05-17 22:13:40');

DROP TABLE IF EXISTS `payments`;
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
  KEY `payments_payment_account_id_foreign` (`payment_account_id`),
  KEY `payments_entry_by_index` (`entry_by`),
  KEY `payments_entry_by_type_index` (`entry_by_type`),
  CONSTRAINT `payments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_payment_account_id_foreign` FOREIGN KEY (`payment_account_id`) REFERENCES `payment_accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `payments` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `amount`, `payment_method`, `payment_account_id`, `payment_date`, `note`, `created_at`, `updated_at`) VALUES (1, '2', 'user', 1, 1, '1000.00', 'cash', NULL, '2026-05-17', NULL, '2026-05-17 22:13:40', '2026-05-17 22:13:40');

DROP TABLE IF EXISTS `permission_role`;
CREATE TABLE `permission_role` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `permission_role_role_id_foreign` (`role_id`),
  CONSTRAINT `permission_role_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `permission_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (1, 1);
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (2, 1);
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (3, 1);
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (4, 1);
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (5, 1);
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (6, 1);
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (7, 1);
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (8, 1);
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (9, 1);
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (10, 1);
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (11, 1);
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (12, 1);
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (13, 1);

DROP TABLE IF EXISTS `permission_user`;
CREATE TABLE `permission_user` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`user_id`),
  KEY `permission_user_user_id_foreign` (`user_id`),
  CONSTRAINT `permission_user_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `permission_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (1, 1);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (1, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (2, 1);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (2, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (3, 1);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (3, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (4, 1);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (4, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (5, 1);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (5, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (6, 1);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (6, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (7, 1);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (7, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (8, 1);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (8, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (9, 1);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (9, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (10, 1);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (10, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (11, 1);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (11, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (12, 1);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (12, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (13, 1);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (13, 2);

DROP TABLE IF EXISTS `permissions`;
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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`id`, `entry_by`, `entry_by_type`, `name`, `label`, `created_at`, `updated_at`) VALUES (1, 'system', 'system', 'view_dashboard', 'View dashboard', '2026-05-17 14:55:11', '2026-05-17 14:55:11');
INSERT INTO `permissions` (`id`, `entry_by`, `entry_by_type`, `name`, `label`, `created_at`, `updated_at`) VALUES (2, 'system', 'system', 'manage_customers', 'Manage customers', '2026-05-17 14:55:11', '2026-05-17 14:55:11');
INSERT INTO `permissions` (`id`, `entry_by`, `entry_by_type`, `name`, `label`, `created_at`, `updated_at`) VALUES (3, 'system', 'system', 'manage_packages', 'Manage packages', '2026-05-17 14:55:11', '2026-05-17 14:55:11');
INSERT INTO `permissions` (`id`, `entry_by`, `entry_by_type`, `name`, `label`, `created_at`, `updated_at`) VALUES (4, 'system', 'system', 'manage_invoices', 'Manage invoices', '2026-05-17 14:55:11', '2026-05-17 14:55:11');
INSERT INTO `permissions` (`id`, `entry_by`, `entry_by_type`, `name`, `label`, `created_at`, `updated_at`) VALUES (5, 'system', 'system', 'finalize_invoices', 'Finalize invoices', '2026-05-17 14:55:11', '2026-05-17 14:55:11');
INSERT INTO `permissions` (`id`, `entry_by`, `entry_by_type`, `name`, `label`, `created_at`, `updated_at`) VALUES (6, 'system', 'system', 'manage_payments', 'Manage payments', '2026-05-17 14:55:11', '2026-05-17 14:55:11');
INSERT INTO `permissions` (`id`, `entry_by`, `entry_by_type`, `name`, `label`, `created_at`, `updated_at`) VALUES (7, 'system', 'system', 'manage_payment_accounts', 'Manage payment accounts', '2026-05-17 14:55:11', '2026-05-17 14:55:11');
INSERT INTO `permissions` (`id`, `entry_by`, `entry_by_type`, `name`, `label`, `created_at`, `updated_at`) VALUES (8, 'system', 'system', 'manage_tickets', 'Manage tickets', '2026-05-17 14:55:11', '2026-05-17 14:55:11');
INSERT INTO `permissions` (`id`, `entry_by`, `entry_by_type`, `name`, `label`, `created_at`, `updated_at`) VALUES (9, 'system', 'system', 'manage_products', 'Manage inventory', '2026-05-17 14:55:11', '2026-05-17 14:55:11');
INSERT INTO `permissions` (`id`, `entry_by`, `entry_by_type`, `name`, `label`, `created_at`, `updated_at`) VALUES (10, 'system', 'system', 'manage_users', 'Manage users and permissions', '2026-05-17 14:55:11', '2026-05-17 14:55:11');
INSERT INTO `permissions` (`id`, `entry_by`, `entry_by_type`, `name`, `label`, `created_at`, `updated_at`) VALUES (11, 'system', 'system', 'download_backup', 'Download database backup', '2026-05-17 14:55:11', '2026-05-17 14:55:11');
INSERT INTO `permissions` (`id`, `entry_by`, `entry_by_type`, `name`, `label`, `created_at`, `updated_at`) VALUES (12, 'system', 'system', 'manage_mikrotik_routers', 'Manage MikroTik routers', '2026-05-17 14:55:12', '2026-05-17 14:55:12');
INSERT INTO `permissions` (`id`, `entry_by`, `entry_by_type`, `name`, `label`, `created_at`, `updated_at`) VALUES (13, NULL, NULL, 'manage_expenses', 'Manage salaries and expenses', '2026-05-17 14:55:14', '2026-05-17 14:55:14');

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `sku` varchar(255) NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `purchase_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sale_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `low_stock_alert` int(11) NOT NULL DEFAULT 5,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_sku_unique` (`sku`),
  KEY `products_entry_by_index` (`entry_by`),
  KEY `products_entry_by_type_index` (`entry_by_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `role_user`;
CREATE TABLE `role_user` (
  `role_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`user_id`),
  KEY `role_user_user_id_foreign` (`user_id`),
  CONSTRAINT `role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `role_user` (`role_id`, `user_id`) VALUES (1, 1);
INSERT INTO `role_user` (`role_id`, `user_id`) VALUES (1, 2);

DROP TABLE IF EXISTS `roles`;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `entry_by`, `entry_by_type`, `name`, `label`, `created_at`, `updated_at`) VALUES (1, 'system', 'system', 'admin', 'Administrator', '2026-05-17 14:55:11', '2026-05-17 14:55:11');

DROP TABLE IF EXISTS `sessions`;
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


DROP TABLE IF EXISTS `stock_movements`;
CREATE TABLE `stock_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_movements_product_id_foreign` (`product_id`),
  KEY `stock_movements_entry_by_index` (`entry_by`),
  KEY `stock_movements_entry_by_type_index` (`entry_by_type`),
  CONSTRAINT `stock_movements_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `subscriptions`;
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


DROP TABLE IF EXISTS `support_tickets`;
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


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `entry_by_type` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_entry_by_index` (`entry_by`),
  KEY `users_entry_by_type_index` (`entry_by_type`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `entry_by`, `entry_by_type`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES (1, 'system', 'system', 'Admin User', 'admin@example.com', NULL, '$2y$12$jB3bBLDvEqLsfC4POYQscO.ASuXyLCXIHmr8QLBOnYMaPwocraisW', NULL, '2026-05-17 14:55:11', '2026-05-17 15:00:11');
INSERT INTO `users` (`id`, `entry_by`, `entry_by_type`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES (2, '1', 'user', 'Anike', 'anike10@gmail.com', NULL, '$2y$12$Wdapg7VKzKST3ctSFHa6KeQGjXmM9zBz4h.wWQFoxncQyRNA9V3Ri', 'Nhz4dYuPRevsa42h51X6WSWDCc9x65zoVM4Vu3hJXhIFi0AgY69c7RIJMUbn', '2026-05-17 15:00:37', '2026-05-17 15:00:37');

SET FOREIGN_KEY_CHECKS=1;
