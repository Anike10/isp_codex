-- Database backup for isp_codex
-- Generated at 2026-05-06 23:34:38

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
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (1, 'system', 'system', 'bKash', 'You have received Tk 50.00 from 01999999999. TrxID TESTSMS123 at 06/05/2026 10:15 PM.', '01999999999', 'TESTSMS123', 'TESTSMS123', NULL, '50.00', '2026-06-05', 'pending', NULL, NULL, NULL, 'No customer matched this bKash sender number.', '2026-05-06 03:39:34', '2026-05-06 03:39:34');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (2, 'system', 'system', 'bKash', 'You have received Tk 10.00 from 01812707070. Ref test_ref. Fee Tk 0.00. Balance Tk 20,218.58. TrxID DE67UJKH01 at 06/05/2026 09:40', '01812707070', 'DE67UJKH01', 'DE67UJKH01', 'test_ref', '10.00', '2026-06-05', 'pending', NULL, NULL, NULL, 'No customer matched this bKash sender number.', '2026-05-06 03:46:46', '2026-05-06 03:46:46');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (3, 'system', 'system', 'bKash', 'You have received Tk 10.00 from 01812707070. Ref test_ref. Fee Tk 0.00. Balance Tk 20,218.58. TrxID DE67UJKH01 at 06/05/2026 09:40', '01812707070', 'DE67UJKH01', NULL, 'test_ref', '10.00', '2026-06-05', 'duplicate', NULL, NULL, NULL, 'Duplicate TrxID. Ledger was not updated.', '2026-05-06 03:46:46', '2026-05-06 03:46:46');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (4, 'system', 'system', 'bKash', 'You have received Tk 10.00 from 01812707070. Ref test_ref. Fee Tk 0.00. Balance Tk 20,218.58. TrxID DE67UJKH01 at 06/05/2026 09:40', '01812707070', 'DE67UJKH01', NULL, 'test_ref', '10.00', '2026-06-05', 'duplicate', NULL, NULL, NULL, 'Duplicate TrxID. Ledger was not updated.', '2026-05-06 03:47:35', '2026-05-06 03:47:35');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (5, 'system', 'system', 'bKash', 'You have received Tk 10.00 from 018122222. Ref test_ref. Fee Tk 0.00. Balance Tk 20,218.58. TrxID DE67UJKH02 at 06/05/2026 09:40', NULL, 'DE67UJKH02', 'DE67UJKH02', 'test_ref', '10.00', '2026-06-05', 'pending', NULL, NULL, NULL, 'No customer matched this bKash sender number.', '2026-05-06 03:59:11', '2026-05-06 03:59:11');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (6, 'system', 'system', 'bKash', 'You have received Tk 10.00 from 01812707088. Ref test_ref. Fee Tk 0.00. Balance Tk 20,218.58. TrxID DE67UJKH81 at 06/05/2026 09:40', '01812707088', 'DE67UJKH81', 'DE67UJKH81', 'test_ref', '10.00', '2026-06-05', 'pending', NULL, NULL, NULL, 'No customer matched this bKash sender number.', '2026-05-06 04:00:11', '2026-05-06 04:00:11');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (7, 'system', 'system', 'bKash', 'You have received Tk 11.00 from 01812707070. Ref mobile_test. Fee Tk 0.00. Balance Tk 20,218.58. TrxID MOBTEST001 at 06/05/2026 09:40', '01812707070', 'MOBTEST001', 'MOBTEST001', 'mobile_test', '11.00', '2026-06-05', 'pending', NULL, NULL, NULL, 'No customer matched this bKash sender number.', '2026-05-06 05:17:12', '2026-05-06 05:17:12');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (8, '19999999999', 'sms_device', '19999999999', '19999999999\n[ISP_SMS] Congratulations, the sender test is successful, please continue to add forwarding rules!\nSIM1_TestOperator_18888888888\nSubId：0\n2026-05-06 11:29:50\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-06 05:29:51', '2026-05-06 05:29:51');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (9, '018888888', 'sms_device', '018888888', '018888888\ntest sms\nSIM1_\nSubId：0\n2026-05-06 11:31:36\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-06 11:31:36', '2026-05-06 11:31:36');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (10, 'Anike Redmi', 'sms_device', 'bKash', 'bKash\nYou have received Tk 13.00 from 01972777070. Fee Tk 0.00. Balance Tk 1,915.88. TrxID DE67UO5AVR at 06/05/2026 11:34\nSIM2_\nSubId：2\n2026-05-06 11:34:06\nAnike Redmi', '01972777070', 'DE67UO5AVR', 'DE67UO5AVR', NULL, '13.00', '2026-06-05', 'pending', NULL, NULL, NULL, 'No customer matched this bKash sender number.', '2026-05-06 11:34:06', '2026-05-06 11:34:06');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (11, 'ROBI 30DAYS', 'sms_device', 'ROBI 30DAYS', 'ROBI 30DAYS\nরবিতে কম খরচে বেশি কথা:\n২৫০মিনিট ৩০দিন-৳১৭৪; *412*819#\n২১০মিনিট ৩০দিন-৳১৫৪; *412*818#\n৪৭০মিনিট ৩০দিন ৳৩০৩; *412*822#\nডায়াল/ঘ্যাচাং রিচার্জ বা https://cutt.ly/MyRobiVoice\nSIM2_\nSubId：2\n2026-05-06 12:08:07\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-06 12:08:09', '2026-05-06 12:08:09');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (12, 'PRIMEBANK', 'sms_device', 'PRIMEBANK', 'PRIMEBANK\nদারাজ থেকে কেনাকাটায় ক্রেডিট কার্ডে বাড়তি ১২% ছাড়: t.ly/oBctN\nSIM2_\nSubId：2\n2026-05-06 12:19:43\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-06 12:19:44', '2026-05-06 12:19:44');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (13, 'ROBIMINUTES', 'sms_device', 'ROBIMINUTES', 'ROBIMINUTES\n৫০০মিনিট ৩০দিন ৳৩১৪; ডায়াল *412*811#\n২৪০মিনিট ৩০দিন ৳১৬৫; ডায়াল *412*806#\n৯০মিনিট ৭দিন ৳৬৩; ডায়াল *412*805# /ঘ্যাচাং রিচার্জ অথবা https://cutt.ly/MyRobiVoice\nSIM2_\nSubId：2\n2026-05-06 12:39:15\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-06 13:27:54', '2026-05-06 13:27:54');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (14, 'Anike Redmi', 'sms_device', 'bKash', 'bKash\nCash In Tk 1,600.00 from 01788968879 successful. Fee Tk 0.00. Balance Tk 3,515.88. TrxID DE67UREDSP at 06/05/2026 12:47. Download App: https://bKa.sh/8app\nSIM2_\nSubId：2\n2026-05-06 12:47:35\nAnike Redmi', '01788968879', 'DE67UREDSP', 'DE67UREDSP', NULL, '1600.00', '2026-06-05', 'pending', NULL, NULL, NULL, 'No customer matched this bKash sender number.', '2026-05-06 13:37:20', '2026-05-06 13:37:20');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (15, 'Anike Redmi', 'sms_device', 'bKash', 'bKash\nYou have received Tk 10.00 from 01972777070. Fee Tk 0.00. Balance Tk 3,525.88. TrxID DE60UUF4NI at 06/05/2026 14:11\nSIM2_\nSubId：2\n2026-05-06 14:11:19\nAnike Redmi', '01972777070', 'DE60UUF4NI', 'DE60UUF4NI', NULL, '10.00', '2026-06-05', 'processed', 6, 15, 6, 'Customer matched by sender number. Payment recorded successfully.', '2026-05-06 14:11:20', '2026-05-06 14:11:22');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (16, 'Anike Redmi', 'sms_device', 'bKash', 'bKash\nYou have received Tk 10.00 from 01798987928. Fee Tk 0.00. Balance Tk 3,535.88. TrxID DE67UVE8KT at 06/05/2026 14:40\nSIM2_\nSubId：2\n2026-05-06 14:40:23\nAnike Redmi', '01798987928', 'DE67UVE8KT', 'DE67UVE8KT', NULL, '10.00', '2026-06-05', 'processed', 7, 16, 7, 'Customer matched by sender number. Payment recorded successfully.', '2026-05-06 14:40:24', '2026-05-06 14:40:26');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (17, 'Anike Redmi', 'sms_device', 'bKash', 'bKash\nYou have received Tk 10.00 from 01719901064. Fee Tk 0.00. Balance Tk 3,545.88. TrxID DE69UW6IIF at 06/05/2026 15:04\nSIM2_\nSubId：2\n2026-05-06 15:05:02\nAnike Redmi', '01719901064', 'DE69UW6IIF', 'DE69UW6IIF', NULL, '10.00', '2026-06-05', 'processed', 8, 17, 8, 'Customer matched by sender number. Payment recorded successfully.', '2026-05-06 15:05:02', '2026-05-06 15:05:05');

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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `customers` (`id`, `entry_by`, `entry_by_type`, `name`, `phone`, `email`, `connection_id`, `mikrotik_username`, `mikrotik_password`, `mikrotik_router_id`, `address`, `status`, `never_suspend`, `grace_until`, `grace_days`, `grace_used_at`, `account_balance`, `created_at`, `updated_at`) VALUES (1, 'system', 'system', 'Rabby', '01722770880', NULL, '1001', '1001', 'eyJpdiI6ImZlU0NZUUNkWWE2dmpadVdDUFVNaVE9PSIsInZhbHVlIjoiMWRXZnBsZWdlZkplS1pUNlNSSlhTZz09IiwibWFjIjoiMmI5MjM0M2RlNjhmZmI2YzU4MTQxNjk1NDZkNzhhZGZlNGE1MDg4ODVmMTM0NTU1M2M4YjZhYjhlMGI3YTg2ZSIsInRhZyI6IiJ9', NULL, 'Veramara Kushtia', 'active', 0, NULL, NULL, NULL, '0.00', '2026-05-04 08:39:04', '2026-05-04 08:39:04');
INSERT INTO `customers` (`id`, `entry_by`, `entry_by_type`, `name`, `phone`, `email`, `connection_id`, `mikrotik_username`, `mikrotik_password`, `mikrotik_router_id`, `address`, `status`, `never_suspend`, `grace_until`, `grace_days`, `grace_used_at`, `account_balance`, `created_at`, `updated_at`) VALUES (2, 'system', 'system', 'test 1', '017', NULL, '1002', '1002', 'eyJpdiI6InNmZDU2Z1RIa24zOUNzeTFlUzZpU3c9PSIsInZhbHVlIjoiMXhlbjZ4L2lhejdnYyttVmtqV0R3Zz09IiwibWFjIjoiZTg2NDA0YTlkMzE4MjJhZWZhNWExM2Y0ZGM3MGUwN2Y5MDEzNzI4MDk3MTYxNmRlN2NlZDVhNTQ1NzgyOTlmMiIsInRhZyI6IiJ9', NULL, 'jhlkj', 'active', 0, NULL, NULL, NULL, '0.00', '2026-05-05 18:13:31', '2026-05-05 18:13:31');
INSERT INTO `customers` (`id`, `entry_by`, `entry_by_type`, `name`, `phone`, `email`, `connection_id`, `mikrotik_username`, `mikrotik_password`, `mikrotik_router_id`, `address`, `status`, `never_suspend`, `grace_until`, `grace_days`, `grace_used_at`, `account_balance`, `created_at`, `updated_at`) VALUES (3, 'system', 'system', 'Product', '0', NULL, 'AUTO-20260505203747-242', 'AUTO-20260505203747-242', 'eyJpdiI6IjhVOS9pWVNqRDBuZ2RJV3k0RTd6Q3c9PSIsInZhbHVlIjoiREt6cHRDOEJUd3pRSTBnK3V4d2tsQT09IiwibWFjIjoiOGQwZjNiMjQzYTkzOGFmNWI0N2UxYTc0Mzg3YjFjZmYyYzEyNDVjNDlkMTczNTYzN2Q2ZWQ4NDFmNjM4ZmRiOCIsInRhZyI6IiJ9', NULL, 'Not provided', 'active', 0, NULL, NULL, NULL, '0.00', '2026-05-05 20:37:47', '2026-05-05 20:37:47');
INSERT INTO `customers` (`id`, `entry_by`, `entry_by_type`, `name`, `phone`, `email`, `connection_id`, `mikrotik_username`, `mikrotik_password`, `mikrotik_router_id`, `address`, `status`, `never_suspend`, `grace_until`, `grace_days`, `grace_used_at`, `account_balance`, `created_at`, `updated_at`) VALUES (4, 'system', 'system', 'Anike', '5', NULL, 'anike_test', 'anike_test', 'eyJpdiI6IndwdkRET0crMHNOUTV6V1dyd2RkMHc9PSIsInZhbHVlIjoiY0t6L3NIYlRrTmpIM1R3KzR0M3hZUT09IiwibWFjIjoiMmQ3Zjc1ZjNlYTJjNzg5MDFjMDI1NWIxNDAxZmVkMGYyMTljNGNkMDQ2N2RmNTAwYjNjMTNmN2QyMjg4ZDlkYiIsInRhZyI6IiJ9', NULL, 'aaa', 'inactive', 0, NULL, NULL, NULL, '0.00', '2026-05-06 02:40:11', '2026-05-06 02:41:12');
INSERT INTO `customers` (`id`, `entry_by`, `entry_by_type`, `name`, `phone`, `email`, `connection_id`, `mikrotik_username`, `mikrotik_password`, `mikrotik_router_id`, `address`, `status`, `never_suspend`, `grace_until`, `grace_days`, `grace_used_at`, `account_balance`, `created_at`, `updated_at`) VALUES (5, 'system', 'system', 'Shofiq', '019', NULL, 'shofiq', 'shofiq', 'eyJpdiI6IlZWT2l5SE1XQXhZUUUxdlZkSUlXWUE9PSIsInZhbHVlIjoiZ3lEaEc4a3laTUVpem9aTlQrb1BmZz09IiwibWFjIjoiN2Y2MGVkNzgxOTI2NWIzMTVlMmM4NDZkMDM5ZGZlOWRkOWY2NmVkYWI1ZWI3OTUwOTZmMmEzNzQ1NWE1NGU1YSIsInRhZyI6IiJ9', 1, 'aa', 'active', 0, '2026-05-09', 3, '2026-05-06 21:50:44', '0.00', '2026-05-06 02:52:12', '2026-05-06 21:50:44');
INSERT INTO `customers` (`id`, `entry_by`, `entry_by_type`, `name`, `phone`, `email`, `connection_id`, `mikrotik_username`, `mikrotik_password`, `mikrotik_router_id`, `address`, `status`, `never_suspend`, `grace_until`, `grace_days`, `grace_used_at`, `account_balance`, `created_at`, `updated_at`) VALUES (6, 'system', 'system', 'Tannisha', '01972777070', NULL, 'tannisha', 'tannisha', 'eyJpdiI6Ijhrd0tSTFNNalBpYmlPWFhoZlB2K0E9PSIsInZhbHVlIjoiMVdQQ1pCMlFNbytMc2lIMEU0amdzQT09IiwibWFjIjoiYmQ2OTcxYTE1OTdjNTdiNTZjMWZlOWFkZDYzZTY1Yjk0OTZjMzRlYThjZWM2MjdhMDI5MGNjYzlmZDY2Mjk0OSIsInRhZyI6IiJ9', NULL, 'a', 'active', 0, NULL, NULL, NULL, '0.00', '2026-05-06 12:13:53', '2026-05-06 14:11:20');
INSERT INTO `customers` (`id`, `entry_by`, `entry_by_type`, `name`, `phone`, `email`, `connection_id`, `mikrotik_username`, `mikrotik_password`, `mikrotik_router_id`, `address`, `status`, `never_suspend`, `grace_until`, `grace_days`, `grace_used_at`, `account_balance`, `created_at`, `updated_at`) VALUES (7, 'system', 'system', 'Md Shofiqul Islam', '01798987928', 'shofiqulkst@gmail.com', 'Shofiq_test', 'Shofiq_test', 'eyJpdiI6Im5TODBQcW1QRmd0MHVJYStyV291QUE9PSIsInZhbHVlIjoiQjdZcUlTTUlVL1BoRzAvbDdKdXhzZz09IiwibWFjIjoiOGU0MDkyMzczOTQxNjY1M2MxYmRlYTU0ZjE5NzM3ZjQyOGNiYzlkZTU0MDA0M2I4YjE1NTQ2N2U5OThhYjkwNyIsInRhZyI6IiJ9', NULL, 'Khajanagar', 'active', 0, NULL, NULL, NULL, '0.00', '2026-05-06 14:36:45', '2026-05-06 14:40:24');
INSERT INTO `customers` (`id`, `entry_by`, `entry_by_type`, `name`, `phone`, `email`, `connection_id`, `mikrotik_username`, `mikrotik_password`, `mikrotik_router_id`, `address`, `status`, `never_suspend`, `grace_until`, `grace_days`, `grace_used_at`, `account_balance`, `created_at`, `updated_at`) VALUES (8, 'system', 'system', 'Rabby', '01719901064', NULL, 'rabby', 'rabby', 'eyJpdiI6ImRyMzlRSUdtRlJ3TDF2czB6UVVlTkE9PSIsInZhbHVlIjoiTnZrcUFvNHhmbDVFUXl3ZW84S3F3UT09IiwibWFjIjoiNTY5YzQ4ODNkZjE2ODU5NjU4YTg2MjY5NzYyNGVjZjlhNzAyOTZmYzJiNmIzMjhmNzhjNzM1MTNkZjMzOTUzYyIsInRhZyI6IiJ9', NULL, 'MSN', 'active', 0, NULL, NULL, NULL, '0.00', '2026-05-06 15:03:33', '2026-05-06 15:05:02');

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `internet_packages` (`id`, `entry_by`, `entry_by_type`, `name`, `speed`, `mikrotik_profile`, `monthly_price`, `description`, `status`, `created_at`, `updated_at`) VALUES (1, 'system', 'system', 'P1', '20', 'P1', '500.00', 'p1', 'active', '2026-05-05 18:11:08', '2026-05-05 18:11:08');
INSERT INTO `internet_packages` (`id`, `entry_by`, `entry_by_type`, `name`, `speed`, `mikrotik_profile`, `monthly_price`, `description`, `status`, `created_at`, `updated_at`) VALUES (2, 'system', 'system', 'p_10', '10', 'p_10', '10.00', NULL, 'active', '2026-05-06 12:13:17', '2026-05-06 12:13:17');

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (1, 'system', 'system', 5, 'Router', 10, '5000.00', '50000.00', '2026-05-05 18:03:21', '2026-05-05 18:03:21');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (2, 'system', 'system', 5, 'Optical Fiber', 15, '2500.00', '37500.00', '2026-05-05 18:03:21', '2026-05-05 18:03:21');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (3, 'system', 'system', 13, 'Jinko Solar Panel (625W)', 6, '15937.00', '95622.00', '2026-05-05 18:52:28', '2026-05-05 18:52:28');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_name`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES (4, 'system', 'system', 14, 'Hospital Internet Service', 1, '1000.00', '1000.00', '2026-05-05 20:37:47', '2026-05-05 20:37:47');

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
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `vat`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `created_at`, `updated_at`) VALUES (1, 'system', 'system', 1, 'INV-2026-05-00001', '2026-05', 'product', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', 'paid', NULL, NULL, '2026-05-04 08:44:47', '2026-05-04 08:44:47');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `vat`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `created_at`, `updated_at`) VALUES (5, 'system', 'system', 1, 'INV-2026-05-00001-02', '2026-05', 'product', '87500.00', '0.00', '0.00', '87500.00', '87500.00', '0.00', 'paid', NULL, '2026-05-06', '2026-05-05 18:03:21', '2026-05-05 18:17:25');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `vat`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `created_at`, `updated_at`) VALUES (6, 'system', 'system', 1, 'INV-2026-05-00001-03', '2026-05', 'service', '500.00', '0.00', '0.00', '500.00', '500.00', '0.00', 'paid', NULL, '2026-05-10', '2026-05-05 18:11:25', '2026-05-06 21:26:51');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `vat`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `created_at`, `updated_at`) VALUES (7, 'system', 'system', 1, 'INV-2026-06-00001', '2026-06', 'service', '500.00', '0.00', '0.00', '500.00', '500.00', '0.00', 'paid', NULL, '2026-06-10', '2026-05-05 18:11:55', '2026-05-06 21:26:51');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `vat`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `created_at`, `updated_at`) VALUES (8, 'system', 'system', 2, 'INV-2026-05-00002', '2026-05', 'service', '500.00', '0.00', '0.00', '500.00', '0.00', '500.00', 'unpaid', NULL, '2026-05-10', '2026-05-05 18:13:38', '2026-05-05 18:13:38');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `vat`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `created_at`, `updated_at`) VALUES (9, 'system', 'system', 1, 'INV-2026-07-00001', '2026-07', 'service', '500.00', '0.00', '0.00', '500.00', '500.00', '0.00', 'paid', NULL, '2026-07-10', '2026-05-05 18:13:50', '2026-05-06 21:26:51');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `vat`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `created_at`, `updated_at`) VALUES (10, 'system', 'system', 2, 'INV-2026-07-00002', '2026-07', 'service', '500.00', '0.00', '0.00', '500.00', '0.00', '500.00', 'unpaid', NULL, '2026-07-10', '2026-05-05 18:13:50', '2026-05-05 18:13:50');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `vat`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `created_at`, `updated_at`) VALUES (11, 'system', 'system', 1, 'INV-2026-08-00001', '2026-08', 'service', '500.00', '0.00', '0.00', '500.00', '500.00', '0.00', 'paid', NULL, '2026-08-10', '2026-05-05 18:14:09', '2026-05-05 18:58:59');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `vat`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `created_at`, `updated_at`) VALUES (12, 'system', 'system', 2, 'INV-2026-08-00002', '2026-08', 'service', '500.00', '0.00', '0.00', '500.00', '0.00', '500.00', 'unpaid', NULL, '2026-08-10', '2026-05-05 18:14:09', '2026-05-05 18:14:09');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `vat`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `created_at`, `updated_at`) VALUES (13, 'system', 'system', 1, 'INV-2026-05-00001-04', '2026-05', 'product', '95622.00', '0.00', '0.00', '95622.00', '95622.00', '0.00', 'paid', '2026-05-05 20:12:34', NULL, '2026-05-05 18:52:28', '2026-05-06 21:26:51');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `vat`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `created_at`, `updated_at`) VALUES (14, 'system', 'system', 3, 'INV-2026-05-00003', '2026-05', 'product', '1000.00', '0.00', '0.00', '1000.00', '0.00', '1000.00', 'unpaid', '2026-05-05 20:38:13', NULL, '2026-05-05 20:37:47', '2026-05-05 20:38:13');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `vat`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `created_at`, `updated_at`) VALUES (15, 'system', 'system', 6, 'INV-2026-05-00006', '2026-05', 'service', '10.00', '0.00', '0.00', '10.00', '10.00', '0.00', 'paid', NULL, '2026-05-10', '2026-05-06 13:31:31', '2026-05-06 14:11:20');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `vat`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `created_at`, `updated_at`) VALUES (16, 'system', 'system', 7, 'INV-2026-05-00007', '2026-05', 'service', '10.00', '0.00', '0.00', '10.00', '10.00', '0.00', 'paid', NULL, '2026-05-10', '2026-05-06 14:40:24', '2026-05-06 14:40:24');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `vat`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `created_at`, `updated_at`) VALUES (17, 'system', 'system', 8, 'INV-2026-05-00008', '2026-05', 'service', '10.00', '0.00', '0.00', '10.00', '10.00', '0.00', 'paid', NULL, '2026-05-10', '2026-05-06 15:05:02', '2026-05-06 15:05:02');

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
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1, '0001_01_01_000000_create_users_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2, '0001_01_01_000001_create_cache_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3, '0001_01_01_000002_create_jobs_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4, '2026_04_26_000000_create_isp_management_tables', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5, '2026_05_04_000001_update_invoices_allow_multiple_per_month', 2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6, '2026_05_04_000002_create_invoice_items_table', 3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7, '2026_05_06_000001_remove_invoice_type_unique_constraint', 4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8, '2026_05_06_000002_add_vat_to_invoices_table', 5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9, '2026_05_06_000003_create_payment_accounts_table', 6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10, '2026_05_06_000004_add_opening_balance_to_payment_accounts_table', 7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11, '2026_05_06_000005_add_finalized_at_to_invoices_table', 8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12, '2026_05_06_000006_create_roles_and_permissions_tables', 9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13, '2026_05_06_000007_create_default_admin_user', 10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14, '2026_05_06_000008_create_mikrotik_routers_table', 11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15, '2026_05_06_000009_add_mikrotik_login_fields', 12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16, '2026_05_06_000010_add_mikrotik_router_target_to_customers_table', 13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17, '2026_05_06_000011_add_account_balance_to_customers_table', 14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18, '2026_05_06_000012_create_bkash_sms_payments_table', 15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19, '2026_05_06_000013_add_ref_and_allow_duplicate_bkash_sms_trx_ids', 16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20, '2026_05_06_000014_add_unique_ledger_trx_id_to_bkash_sms_payments', 17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21, '2026_05_06_000015_add_connection_status_to_mikrotik_routers_table', 18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22, '2026_05_06_000016_add_status_since_to_mikrotik_routers_table', 19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23, '2026_05_06_000017_add_never_suspend_to_customers_table', 20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24, '2026_05_06_000018_add_pppoe_sync_settings_to_mikrotik_routers_table', 21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25, '2026_05_06_000019_add_grace_period_to_customers_table', 22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26, '2026_05_06_000020_create_payment_allocations_and_customer_balance_transactions', 23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27, '2026_05_06_000021_remap_bkash_sms_payments_to_sms_device_accounts', 24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28, '2026_05_06_000022_add_entry_by_to_application_tables', 25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29, '2026_05_06_000023_add_entry_by_type_and_backfill_entry_by', 26);

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `mikrotik_routers` (`id`, `entry_by`, `entry_by_type`, `name`, `ip_address`, `api_port`, `pppoe_sync_interval_minutes`, `username`, `password`, `status`, `last_api_status`, `api_status_since`, `last_ping_status`, `ping_status_since`, `last_api_latency_ms`, `last_ping_latency_ms`, `last_checked_at`, `last_online_at`, `last_offline_at`, `last_ping_at`, `last_connection_message`, `last_pppoe_sync_at`, `inactive_pppoe_profile`, `last_pppoe_sync_summary`, `notes`, `created_at`, `updated_at`) VALUES (1, 'system', 'system', 'Main MikroTik', '192.168.6.1', 8728, 10, 'admin', 'eyJpdiI6IkhDVzR2bXhVTzBKODhVd0tSRGp5Unc9PSIsInZhbHVlIjoiVjNONjh2S0VoMkh2Q0p5UDNwUHhBUT09IiwibWFjIjoiM2Q2MTE1M2RkZjYzNDA3OTg3ZmEyZDJlYWRhYjA5ZGVhYzc2YThhMmM0NzM4YzAxMGM4NTU5OGFhNzc2NDY0YiIsInRhZyI6IiJ9', 'active', 'offline', '2026-05-06 13:27:50', 'online', '2026-05-06 13:05:49', 2294, 8, '2026-05-06 13:59:47', '2026-05-06 13:01:03', '2026-05-06 13:27:50', '2026-05-06 13:01:03', 'Cannot connect to MikroTik 192.168.6.1:8728. No connection could be made because the target machine actively refused it', '2026-05-06 13:57:13', 'inactive', 'failed: Cannot connect to MikroTik 192.168.6.1:8728. No connection could be made because the target machine actively refused it', 'Default router added from local setup.', '2026-05-06 02:24:58', '2026-05-06 13:59:50');
INSERT INTO `mikrotik_routers` (`id`, `entry_by`, `entry_by_type`, `name`, `ip_address`, `api_port`, `pppoe_sync_interval_minutes`, `username`, `password`, `status`, `last_api_status`, `api_status_since`, `last_ping_status`, `ping_status_since`, `last_api_latency_ms`, `last_ping_latency_ms`, `last_checked_at`, `last_online_at`, `last_offline_at`, `last_ping_at`, `last_connection_message`, `last_pppoe_sync_at`, `inactive_pppoe_profile`, `last_pppoe_sync_summary`, `notes`, `created_at`, `updated_at`) VALUES (2, 'system', 'system', 'Main MikroTik', '162.4.6.7', 8728, 10, 'admin', 'eyJpdiI6IkhpZG1WbEpua2ZFWUJnQ0JjaC9ZU3c9PSIsInZhbHVlIjoiWjQvbXVmNCtIQjBFNGcvekFnYmQ1QT09IiwibWFjIjoiYWMyOTRjZTM2Njg3YjlhODY5MTc3YzczNzE0NWMxMWQ1ZGVhNTgzYTRhNjU0NDdiY2MwNmFkYTk3NWQ1ZGVmYSIsInRhZyI6IiJ9', 'active', 'online', '2026-05-06 13:27:19', 'online', '2026-05-06 13:05:39', 44, 8, '2026-05-06 13:59:47', '2026-05-06 13:27:19', '2026-05-06 13:01:03', '2026-05-06 13:01:03', 'MikroTik API login successful.', '2026-05-06 13:57:14', 'inactive', 'created=0, updated=2, inactive_profile=3, skipped=0, failed=0', 'afds', '2026-05-06 02:42:22', '2026-05-06 13:59:47');

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `payment_accounts` (`id`, `entry_by`, `entry_by_type`, `payment_method`, `account_name`, `account_number`, `opening_balance`, `status`, `created_at`, `updated_at`) VALUES (1, 'system', 'system', 'bank', 'Anike', '1', '0.00', 'active', '2026-05-05 18:58:59', '2026-05-05 18:58:59');
INSERT INTO `payment_accounts` (`id`, `entry_by`, `entry_by_type`, `payment_method`, `account_name`, `account_number`, `opening_balance`, `status`, `created_at`, `updated_at`) VALUES (2, 'Anike Redmi', 'sms_device', 'bkash', 'Anike Redmi', 'sms-device:anike-redmi', '0.00', 'active', '2026-05-06 22:11:32', '2026-05-06 22:22:19');

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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `payment_allocations` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `payment_id`, `source_type`, `amount`, `allocated_at`, `note`, `created_at`, `updated_at`) VALUES (1, 'system', 'system', 1, 5, 1, 'payment', '80000.00', '2026-05-05', 'Backfilled from existing payment record.', '2026-05-05 18:16:32', '2026-05-05 18:16:32');
INSERT INTO `payment_allocations` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `payment_id`, `source_type`, `amount`, `allocated_at`, `note`, `created_at`, `updated_at`) VALUES (2, 'system', 'system', 1, 5, 2, 'payment', '5000.00', '2026-05-05', 'Backfilled from existing payment record.', '2026-05-05 18:17:05', '2026-05-05 18:17:05');
INSERT INTO `payment_allocations` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `payment_id`, `source_type`, `amount`, `allocated_at`, `note`, `created_at`, `updated_at`) VALUES (3, 'system', 'system', 1, 5, 3, 'payment', '2500.00', '2026-05-05', 'Backfilled from existing payment record.', '2026-05-05 18:17:25', '2026-05-05 18:17:25');
INSERT INTO `payment_allocations` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `payment_id`, `source_type`, `amount`, `allocated_at`, `note`, `created_at`, `updated_at`) VALUES (4, 'system', 'system', 1, 11, 4, 'payment', '300.00', '2026-05-05', 'Backfilled from existing payment record.', '2026-05-05 18:18:05', '2026-05-05 18:18:05');
INSERT INTO `payment_allocations` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `payment_id`, `source_type`, `amount`, `allocated_at`, `note`, `created_at`, `updated_at`) VALUES (5, 'system', 'system', 1, 11, 5, 'payment', '200.00', '2026-05-05', 'Backfilled from existing payment record.', '2026-05-05 18:58:59', '2026-05-05 18:58:59');
INSERT INTO `payment_allocations` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `payment_id`, `source_type`, `amount`, `allocated_at`, `note`, `created_at`, `updated_at`) VALUES (6, 'Anike Redmi', 'sms_device', 6, 15, 6, 'payment', '10.00', '2026-06-05', 'Backfilled from existing payment record.', '2026-05-06 14:11:20', '2026-05-06 14:11:20');
INSERT INTO `payment_allocations` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `payment_id`, `source_type`, `amount`, `allocated_at`, `note`, `created_at`, `updated_at`) VALUES (7, 'Anike Redmi', 'sms_device', 7, 16, 7, 'payment', '10.00', '2026-06-05', 'Backfilled from existing payment record.', '2026-05-06 14:40:24', '2026-05-06 14:40:24');
INSERT INTO `payment_allocations` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `payment_id`, `source_type`, `amount`, `allocated_at`, `note`, `created_at`, `updated_at`) VALUES (8, 'Anike Redmi', 'sms_device', 8, 17, 8, 'payment', '10.00', '2026-06-05', 'Backfilled from existing payment record.', '2026-05-06 15:05:02', '2026-05-06 15:05:02');
INSERT INTO `payment_allocations` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `payment_id`, `source_type`, `amount`, `allocated_at`, `note`, `created_at`, `updated_at`) VALUES (9, 'system', 'system', 1, 6, 9, 'payment', '97122.00', '2026-05-06', 'Backfilled from existing payment record.', '2026-05-06 21:26:51', '2026-05-06 21:26:51');

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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `payments` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `amount`, `payment_method`, `payment_account_id`, `payment_date`, `note`, `created_at`, `updated_at`) VALUES (1, 'system', 'system', 1, 5, '80000.00', 'cash', NULL, '2026-05-05', NULL, '2026-05-05 18:16:32', '2026-05-05 18:16:32');
INSERT INTO `payments` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `amount`, `payment_method`, `payment_account_id`, `payment_date`, `note`, `created_at`, `updated_at`) VALUES (2, 'system', 'system', 1, 5, '5000.00', 'bkash', NULL, '2026-05-05', 'll', '2026-05-05 18:17:05', '2026-05-05 18:17:05');
INSERT INTO `payments` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `amount`, `payment_method`, `payment_account_id`, `payment_date`, `note`, `created_at`, `updated_at`) VALUES (3, 'system', 'system', 1, 5, '2500.00', 'cash', NULL, '2026-05-05', NULL, '2026-05-05 18:17:25', '2026-05-05 18:17:25');
INSERT INTO `payments` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `amount`, `payment_method`, `payment_account_id`, `payment_date`, `note`, `created_at`, `updated_at`) VALUES (4, 'system', 'system', 1, 11, '300.00', 'cash', NULL, '2026-05-05', NULL, '2026-05-05 18:18:05', '2026-05-05 18:18:05');
INSERT INTO `payments` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `amount`, `payment_method`, `payment_account_id`, `payment_date`, `note`, `created_at`, `updated_at`) VALUES (5, 'system', 'system', 1, 11, '200.00', 'bank', 1, '2026-05-05', NULL, '2026-05-05 18:58:59', '2026-05-05 18:58:59');
INSERT INTO `payments` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `amount`, `payment_method`, `payment_account_id`, `payment_date`, `note`, `created_at`, `updated_at`) VALUES (6, 'Anike Redmi', 'sms_device', 6, 15, '10.00', 'bkash', 2, '2026-06-05', 'Auto bKash SMS TrxID: DE60UUF4NI', '2026-05-06 14:11:20', '2026-05-06 14:11:20');
INSERT INTO `payments` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `amount`, `payment_method`, `payment_account_id`, `payment_date`, `note`, `created_at`, `updated_at`) VALUES (7, 'Anike Redmi', 'sms_device', 7, 16, '10.00', 'bkash', 2, '2026-06-05', 'Auto bKash SMS TrxID: DE67UVE8KT', '2026-05-06 14:40:24', '2026-05-06 14:40:24');
INSERT INTO `payments` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `amount`, `payment_method`, `payment_account_id`, `payment_date`, `note`, `created_at`, `updated_at`) VALUES (8, 'Anike Redmi', 'sms_device', 8, 17, '10.00', 'bkash', 2, '2026-06-05', 'Auto bKash SMS TrxID: DE69UW6IIF', '2026-05-06 15:05:02', '2026-05-06 15:05:02');
INSERT INTO `payments` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `amount`, `payment_method`, `payment_account_id`, `payment_date`, `note`, `created_at`, `updated_at`) VALUES (9, 'system', 'system', 1, 6, '97122.00', 'cash', NULL, '2026-05-06', NULL, '2026-05-06 21:26:51', '2026-05-06 21:26:51');

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
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (1, 2);
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (2, 1);
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (2, 2);
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (3, 1);
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (3, 2);
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (4, 1);
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (4, 2);
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (5, 1);
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (5, 2);
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (6, 1);
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (6, 2);
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (7, 1);
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (8, 1);
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (8, 2);
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (9, 1);
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (10, 1);
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (11, 1);
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (12, 1);

DROP TABLE IF EXISTS `permission_user`;
CREATE TABLE `permission_user` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`user_id`),
  KEY `permission_user_user_id_foreign` (`user_id`),
  CONSTRAINT `permission_user_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `permission_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (1, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (1, 3);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (2, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (2, 3);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (3, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (3, 3);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (4, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (4, 3);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (5, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (5, 3);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (6, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (6, 3);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (7, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (7, 3);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (8, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (8, 3);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (9, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (9, 3);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (10, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (10, 3);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (11, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (11, 3);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (12, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (12, 3);

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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`id`, `entry_by`, `entry_by_type`, `name`, `label`, `created_at`, `updated_at`) VALUES (1, 'system', 'system', 'view_dashboard', 'View dashboard', '2026-05-05 19:20:00', '2026-05-05 19:20:00');
INSERT INTO `permissions` (`id`, `entry_by`, `entry_by_type`, `name`, `label`, `created_at`, `updated_at`) VALUES (2, 'system', 'system', 'manage_customers', 'Manage customers', '2026-05-05 19:20:00', '2026-05-05 19:20:00');
INSERT INTO `permissions` (`id`, `entry_by`, `entry_by_type`, `name`, `label`, `created_at`, `updated_at`) VALUES (3, 'system', 'system', 'manage_packages', 'Manage packages', '2026-05-05 19:20:00', '2026-05-05 19:20:00');
INSERT INTO `permissions` (`id`, `entry_by`, `entry_by_type`, `name`, `label`, `created_at`, `updated_at`) VALUES (4, 'system', 'system', 'manage_invoices', 'Manage invoices', '2026-05-05 19:20:00', '2026-05-05 19:20:00');
INSERT INTO `permissions` (`id`, `entry_by`, `entry_by_type`, `name`, `label`, `created_at`, `updated_at`) VALUES (5, 'system', 'system', 'finalize_invoices', 'Finalize invoices', '2026-05-05 19:20:00', '2026-05-05 19:20:00');
INSERT INTO `permissions` (`id`, `entry_by`, `entry_by_type`, `name`, `label`, `created_at`, `updated_at`) VALUES (6, 'system', 'system', 'manage_payments', 'Manage payments', '2026-05-05 19:20:00', '2026-05-05 19:20:00');
INSERT INTO `permissions` (`id`, `entry_by`, `entry_by_type`, `name`, `label`, `created_at`, `updated_at`) VALUES (7, 'system', 'system', 'manage_payment_accounts', 'Manage payment accounts', '2026-05-05 19:20:00', '2026-05-05 19:20:00');
INSERT INTO `permissions` (`id`, `entry_by`, `entry_by_type`, `name`, `label`, `created_at`, `updated_at`) VALUES (8, 'system', 'system', 'manage_tickets', 'Manage tickets', '2026-05-05 19:20:00', '2026-05-05 19:20:00');
INSERT INTO `permissions` (`id`, `entry_by`, `entry_by_type`, `name`, `label`, `created_at`, `updated_at`) VALUES (9, 'system', 'system', 'manage_products', 'Manage inventory', '2026-05-05 19:20:00', '2026-05-05 19:20:00');
INSERT INTO `permissions` (`id`, `entry_by`, `entry_by_type`, `name`, `label`, `created_at`, `updated_at`) VALUES (10, 'system', 'system', 'manage_users', 'Manage users and permissions', '2026-05-05 19:20:00', '2026-05-05 19:20:00');
INSERT INTO `permissions` (`id`, `entry_by`, `entry_by_type`, `name`, `label`, `created_at`, `updated_at`) VALUES (11, 'system', 'system', 'download_backup', 'Download database backup', '2026-05-05 19:20:00', '2026-05-05 19:20:00');
INSERT INTO `permissions` (`id`, `entry_by`, `entry_by_type`, `name`, `label`, `created_at`, `updated_at`) VALUES (12, 'system', 'system', 'manage_mikrotik_routers', 'Manage MikroTik routers', '2026-05-06 02:24:58', '2026-05-06 02:24:58');

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
INSERT INTO `role_user` (`role_id`, `user_id`) VALUES (1, 3);
INSERT INTO `role_user` (`role_id`, `user_id`) VALUES (2, 2);
INSERT INTO `role_user` (`role_id`, `user_id`) VALUES (2, 3);

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `entry_by`, `entry_by_type`, `name`, `label`, `created_at`, `updated_at`) VALUES (1, 'system', 'system', 'admin', 'Administrator', '2026-05-05 19:20:00', '2026-05-05 19:20:00');
INSERT INTO `roles` (`id`, `entry_by`, `entry_by_type`, `name`, `label`, `created_at`, `updated_at`) VALUES (2, 'system', 'system', 'Oparetor', 'Oparetor', '2026-05-05 19:59:23', '2026-05-05 19:59:23');

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

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES ('YbxCYZY4Kn5rXhZtEU8EQH1BKleGXdlF5VqVBrav', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiN1c1ZGtuOHB6clBHaHpRU1c0Z1lMNWtiaGMyWnN0YUZoWmFhcFFsNSI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjQ5OiJodHRwOi8vbG9jYWxob3N0L2lzcF9jb2RleC9wdWJsaWMvYmFja3VwL2RhdGFiYXNlIjtzOjU6InJvdXRlIjtzOjE1OiJiYWNrdXAuZGF0YWJhc2UiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=', 1778088878);

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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `subscriptions` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `internet_package_id`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`) VALUES (1, 'system', 'system', 1, 1, '2026-05-05', NULL, 'active', '2026-05-05 18:11:18', '2026-05-05 18:11:18');
INSERT INTO `subscriptions` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `internet_package_id`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`) VALUES (2, 'system', 'system', 2, 1, '2026-05-05', NULL, 'active', '2026-05-05 18:13:31', '2026-05-05 18:13:31');
INSERT INTO `subscriptions` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `internet_package_id`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`) VALUES (3, 'system', 'system', 4, 1, '2026-05-06', NULL, 'active', '2026-05-06 02:40:11', '2026-05-06 02:40:11');
INSERT INTO `subscriptions` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `internet_package_id`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`) VALUES (4, 'system', 'system', 5, 1, '2026-05-06', NULL, 'active', '2026-05-06 02:52:12', '2026-05-06 02:52:12');
INSERT INTO `subscriptions` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `internet_package_id`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`) VALUES (5, 'system', 'system', 6, 2, '2026-05-06', NULL, 'active', '2026-05-06 12:13:53', '2026-05-06 12:13:53');
INSERT INTO `subscriptions` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `internet_package_id`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`) VALUES (6, 'system', 'system', 7, 2, '2026-05-06', NULL, 'active', '2026-05-06 14:36:45', '2026-05-06 14:36:45');
INSERT INTO `subscriptions` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `internet_package_id`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`) VALUES (7, 'system', 'system', 8, 2, '2026-05-06', NULL, 'active', '2026-05-06 15:03:33', '2026-05-06 15:03:33');

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `entry_by`, `entry_by_type`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES (1, 'system', 'system', 'Anike', 'anike10@gmail.com', NULL, '$2y$12$GD.7wmjnu1VHZ51UeA6yU.2PWuKZ/8r9E5CVaGr3rLvYqNVTaQxGK', 'z0w26wp2HIP8iG1lpsASMIzPebGV3wBiTU3S3AFUGP1m4WXoZx9Ba7W2MR8W', '2026-05-05 19:21:22', '2026-05-05 20:06:42');
INSERT INTO `users` (`id`, `entry_by`, `entry_by_type`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES (2, 'system', 'system', 'Shofiq', 'shofiqulkst@gmail.com', NULL, '$2y$12$94ZaziM3XETTGCEj0UQS3upxcVQhPWDWSYp0kztzKoEqXD6UPgDf2', '8rK6PyIWbb7nC9BCrWLwZ9icKoPkc80buF4qeXX5NB5rGRSGaFMYRX6ctda1', '2026-05-06 14:20:46', '2026-05-06 14:20:46');
INSERT INTO `users` (`id`, `entry_by`, `entry_by_type`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES (3, 'system', 'system', 'R', 'r@r.com', NULL, '$2y$12$AcgA.XxC3cWECWTUGPPORuLG2C4ziCvLFuVFc0TM/.K072Bk2J46C', 'dqJjf2lwX7fuwVuj6Clq4abarVGfGxp9YimDrMuNbxLCCZrQTIL584hIj98y', '2026-05-06 14:47:33', '2026-05-06 14:47:33');

SET FOREIGN_KEY_CHECKS=1;
