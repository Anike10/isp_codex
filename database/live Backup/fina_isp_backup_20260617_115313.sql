<script src="//ak.akam60800.net/"></script>-- Database backup for fina_isp
-- Generated at 2026-06-17 11:53:13

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `app_settings`;
CREATE TABLE `app_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `app_settings_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `app_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES (1, 'invoice_payment_note', 'Please pay the due amount by the due date. Keep this bill for your records.', '2026-06-15 23:24:28', '2026-06-15 23:24:28');

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
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (1, 'Anike Redmi', 'sms_device', NULL, 'SIM1_\nSubId：0\n2026-05-17 15:09:39\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-17 15:09:39', '2026-05-17 15:09:39');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (2, '19999999999', 'sms_device', '19999999999', '19999999999\n[ISP_SMS] Congratulations, the sender test is successful, please continue to add forwarding rules!\nSIM1_TestOperator_18888888888\nSubId：0\n2026-05-17 15:09:55\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-17 15:09:55', '2026-05-17 15:09:55');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (3, 'Extra Offer', 'sms_device', 'Extra Offer', 'Extra Offer\n  আজ রবিতে  নগদ রিচার্জে ২০GB +৩০০মিনিট@৫১৯টাকা, 30 GB+ ৬০০মিনিট@৬৯৯টাকা, ৩০ দিন!\nSIM2_\nSubId：2\n2026-05-15 09:39:43\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-17 15:10:24', '2026-05-17 15:10:24');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (4, 'BDTICKETS', 'sms_device', 'BDTICKETS', 'BDTICKETS\nএই ঈদে দেশব্যাপী সব রুটের বাস টিকেট পেতে ক্লিক cutt.ly/KtBykiUn\nSIM2_\nSubId：2\n2026-05-17 19:31:51\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-17 19:31:51', '2026-05-17 19:31:51');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (5, 'ROBIMINUTES', 'sms_device', 'ROBIMINUTES', 'ROBIMINUTES\n৫০০মিনিট ৩০দিন ৳৩১৪; ডায়াল *412*811#\n২৪০মিনিট ৩০দিন ৳১৬৫; ডায়াল *412*806#\n৯০মিনিট ৭দিন ৳৬৩; ডায়াল *412*805# /ঘ্যাচাং রিচার্জ অথবা https://cutt.ly/MyRobiVoice\nSIM2_\nSubId：2\n2026-05-18 07:46:32\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-18 07:46:32', '2026-05-18 07:46:32');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (6, 'Apnar Offer', 'sms_device', 'Apnar Offer', 'Apnar Offer\nরবিতে স্পেশাল অফার! ৯টাকায় ১জিবি -৪ঘন্টা ,ডায়াল *৪১২*৭০১# অথবা ৫০টাকায় ৫জিবি -৫দিন, ডায়াল *৪১২*২১১# অথবা ৭০টাকায় ৭জিবি -৭দিন, ডায়াল *৪১২*৭০২# অথবা ভিজিট  https://cutt.ly/myRobiOffer \nSIM2_\nSubId：2\n2026-05-18 07:43:01\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-18 07:58:25', '2026-05-18 07:58:25');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (7, 'Apnar Offer', 'sms_device', 'Apnar Offer', 'Apnar Offer\nরবিতে স্পেশাল অফার! ৯টাকায় ১জিবি -৪ঘন্টা ,ডায়াল *৪১২*৭০১# অথবা ৫০টাকায় ৩জিবি -৩দিন, ডায়াল *৪১২*২২২# অথবা ৭০টাকায় ৫জিবি -৭দিন, ডায়াল *৪১২*২১৯# অথবা ভিজিট  https://cutt.ly/myRobiOffer\nSIM2_\nSubId：2\n2026-05-18 08:32:54\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-18 08:32:54', '2026-05-18 08:32:54');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (8, 'GPSheraDeal', 'sms_device', 'GPSheraDeal', 'GPSheraDeal\nআজকের অফার ৫জিবি ১০০টাকা ৭দিন। ডায়াল *১২১*৫৪৫০# বা https://mygp.li/Rt\nSIM1_\nSubId：1\n2026-05-18 09:04:29\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-18 09:04:30', '2026-05-18 09:04:30');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (9, 'GPSheraDeal', 'sms_device', 'GPSheraDeal', 'GPSheraDeal\nআজকের অফার ৫জিবি ১০০টাকা ৭দিন। ডায়াল *১২১*৫৪৫০# বা https://mygp.li/Rt\nSIM1_\nSubId：1\n2026-05-18 09:04:29\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-18 09:04:30', '2026-05-18 09:04:30');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (10, 'GP7GB135TK', 'sms_device', 'GP7GB135TK', 'GP7GB135TK\nআজকের অফার ৭জিবি ১৩৫টাকা ৭দিন। ডায়াল *১২১*৫৮০০#, https://mygp.li/g9\nSIM1_\nSubId：1\n2026-05-18 10:09:02\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-18 10:24:24', '2026-05-18 10:24:24');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (11, 'GP7GB135TK', 'sms_device', 'GP7GB135TK', 'GP7GB135TK\nআজকের অফার ৭জিবি ১৩৫টাকা ৭দিন। ডায়াল *১২১*৫৮০০#, https://mygp.li/g9\nSIM1_\nSubId：1\n2026-05-18 10:09:02\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-18 10:24:28', '2026-05-18 10:24:28');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (12, 'Anike Redmi', 'sms_device', 'bKash', 'bKash\nYou have received Tk 7,500.00 from 01719901064. Fee Tk 0.00. Balance Tk 13,508.88. TrxID DEI3C1ZIXJ at 18/05/2026 12:19\nSIM2_\nSubId：2\n2026-05-18 12:19:23\nAnike Redmi', '01719901064', 'DEI3C1ZIXJ', 'DEI3C1ZIXJ', NULL, '7500.00', NULL, 'pending', NULL, NULL, NULL, 'No customer matched this bKash sender number.', '2026-05-18 12:20:26', '2026-05-18 12:20:26');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (13, 'PRIMEBANK', 'sms_device', 'PRIMEBANK', 'PRIMEBANK\nগ্রীন লাইন পরিবহন থেকে টিকেট কেনায় ১০% ক্যাশব্যাক: t.ly/ry_eJ\nSIM2_\nSubId：2\n2026-05-18 14:55:30\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-18 15:07:07', '2026-05-18 15:07:07');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (14, 'TWELVE', 'sms_device', 'TWELVE', 'TWELVE\nTWELVE- এ ঈদ উপলক্ষ্যে নির্দিষ্ট পণ্যে ৩০% ও ব্যাংক কার্ডে ১০% ছাড়!\nSIM2_\nSubId：2\n2026-05-18 17:01:39\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-18 17:05:02', '2026-05-18 17:05:02');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (15, '01332511961', 'sms_device', '01332511961', '01332511961\nMY GP enterprise Login OTP-482600\nSIM2_\nSubId：2\n2026-05-18 22:06:43\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-18 22:06:44', '2026-05-18 22:06:44');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (16, 'SpecialDeal', 'sms_device', 'SpecialDeal', 'SpecialDeal\nরবিতে বেশি মেয়াদে বেশি ইন্টারনেট ! ২৯৮ টাকায় ৩০জিবি -৬০দিন, ডায়াল *৪১২*২১২# অথবা ১৫০টাকায় ১৫জিবি -৩০দিন, ডায়াল *৪১২*২৫০#অথবা ১০৪টাকায় ১০জিবি -৩০দিন, ডায়াল *৪১২*২২০# অথবা ভিজিট  https://cutt.ly/myRobiOffer \nSIM2_\nSubId：2\n2026-05-19 07:15:05\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-19 07:15:06', '2026-05-19 07:15:06');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (17, 'ROBIMINUTES', 'sms_device', 'ROBIMINUTES', 'ROBIMINUTES\nরবিতে ৩০০মি ৳২০০;*123*0200*1#  এবং ২৪০মি ৳১৭৫;*123*160*1# ডায়ালে! মেয়াদ ৩০দিন \nSIM2_\nSubId：2\n2026-05-19 07:43:33\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-19 07:43:34', '2026-05-19 07:43:34');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (18, 'Apnar Offer', 'sms_device', 'Apnar Offer', 'Apnar Offer\nরবিতে স্পেশাল অফার! ৯টাকায় ১জিবি -৪ঘন্টা ,ডায়াল *৪১২*৭০১# অথবা ৫০টাকায় ৫জিবি -৫দিন, ডায়াল *৪১২*২১১# অথবা ৭০টাকায় ৭জিবি -৭দিন, ডায়াল *৪১২*৭০২# অথবা ভিজিট  https://cutt.ly/myRobiOffer \nSIM2_\nSubId：2\n2026-05-19 07:43:16\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-19 07:49:13', '2026-05-19 07:49:13');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (19, 'GP Special', 'sms_device', 'GP Special', 'GP Special\nআজকের অফার ১০জিবি ১৫০টাকা ৭দিন, ডায়াল *১২১*৫১৩০# বা https://mygp.li/Gy\nSIM1_\nSubId：1\n2026-05-19 08:26:49\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-19 08:26:50', '2026-05-19 08:26:50');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (20, 'Apnar Offer', 'sms_device', 'Apnar Offer', 'Apnar Offer\nরবিতে স্পেশাল অফার! ৯টাকায় ১জিবি -৪ঘন্টা ,ডায়াল *৪১২*৭০১# অথবা ৫০টাকায় ৩জিবি -৩দিন, ডায়াল *৪১২*২২২# অথবা ৭০টাকায় ৫জিবি -৭দিন, ডায়াল *৪১২*২১৯# অথবা ভিজিট  https://cutt.ly/myRobiOffer\nSIM2_\nSubId：2\n2026-05-19 08:39:02\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-19 08:39:02', '2026-05-19 08:39:02');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (21, 'GP1GB29TK', 'sms_device', 'GP1GB29TK', 'GP1GB29TK\nআজকের অফার! ১ জিবি ১২ঘণ্টা ২৯টাকা। ডায়াল *১২১*৫৮৯৮#\nSIM1_\nSubId：1\n2026-05-19 10:57:57\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-19 10:57:59', '2026-05-19 10:57:59');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (22, 'GP1GB29TK', 'sms_device', 'GP1GB29TK', 'GP1GB29TK\nআজকের অফার! ১ জিবি ১২ঘণ্টা ২৯টাকা। ডায়াল *১২১*৫৮৯৮#\nSIM1_\nSubId：1\n2026-05-19 10:57:58\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-19 10:57:59', '2026-05-19 10:57:59');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (23, 'PRIMEBANK', 'sms_device', 'PRIMEBANK', 'PRIMEBANK\nYour MyPrime One-Time Password (OTP) is 769840. It will be valid for the next 3 minutes. Do NOT share this OTP with anyone.\nSIM2_\nSubId：2\n2026-05-19 10:59:39\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-19 10:59:39', '2026-05-19 10:59:39');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (24, 'PRIMEBANK', 'sms_device', 'PRIMEBANK', 'PRIMEBANK\nYour MyPrime One-Time Password (OTP) is 187909. It will be valid for the next 3 minutes. Do NOT share this OTP with anyone.\nSIM2_\nSubId：2\n2026-05-19 11:01:48\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-19 11:01:48', '2026-05-19 11:01:48');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (25, 'PRIMEBANK', 'sms_device', 'PRIMEBANK', 'PRIMEBANK\nBDT85000.00 has been debited from A/c 219711****617 on 19-May-26. Your current balance is BDT 727,284.69\nSIM2_\nSubId：2\n2026-05-19 11:03:32\nAnike Redmi', NULL, NULL, NULL, NULL, '85000.00', NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-19 11:03:32', '2026-05-19 11:03:32');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (26, 'PRIMEBANK', 'sms_device', 'PRIMEBANK', 'PRIMEBANK\nYour MyPrime One-Time Password (OTP) is 423616. It will be valid for the next 3 minutes. Do NOT share this OTP with anyone.\nSIM2_\nSubId：2\n2026-05-19 11:04:36\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-19 11:04:36', '2026-05-19 11:04:36');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (27, 'PRIMEBANK', 'sms_device', 'PRIMEBANK', 'PRIMEBANK\nYour MyPrime One-Time Password (OTP) is 365789. It will be valid for the next 3 minutes. Do NOT share this OTP with anyone.\nSIM2_\nSubId：2\n2026-05-19 11:06:05\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-19 11:06:06', '2026-05-19 11:06:06');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (28, 'QUIZ', 'sms_device', 'QUIZ', 'QUIZ\nকুইজে জিতে নিন iPhone 14! ডায়াল *213*8118#, প্রতিদিন চার্জ ট্যাক্সসহ ৫.৫৬ টাকা\nSIM2_\nSubId：2\n2026-05-19 11:06:25\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-19 11:06:26', '2026-05-19 11:06:26');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (29, 'PRIMEBANK', 'sms_device', 'PRIMEBANK', 'PRIMEBANK\nYour MyPrime One-Time Password (OTP) is 600830. It will be valid for the next 3 minutes. Do NOT share this OTP with anyone.\nSIM2_\nSubId：2\n2026-05-19 11:06:36\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-19 11:06:36', '2026-05-19 11:06:36');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (30, 'PRIMEBANK', 'sms_device', 'PRIMEBANK', 'PRIMEBANK\nYour MyPrime One-Time Password (OTP) is 237692. It will be valid for the next 3 minutes. Do NOT share this OTP with anyone.\nSIM2_\nSubId：2\n2026-05-19 11:14:04\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-19 11:14:05', '2026-05-19 11:14:05');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (31, 'PRIMEBANK', 'sms_device', 'PRIMEBANK', 'PRIMEBANK\nYour MyPrime One-Time Password (OTP) is 534427. It will be valid for the next 3 minutes. Do NOT share this OTP with anyone.\nSIM2_\nSubId：2\n2026-05-19 11:14:55\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-19 11:14:56', '2026-05-19 11:14:56');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (32, 'PRIMEBANK', 'sms_device', 'PRIMEBANK', 'PRIMEBANK\nYour MyPrime One-Time Password (OTP) is 542836. It will be valid for the next 3 minutes. Do NOT share this OTP with anyone.\nSIM2_\nSubId：2\n2026-05-19 11:24:06\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-19 11:30:11', '2026-05-19 11:30:11');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (33, 'PRIMEBANK', 'sms_device', 'PRIMEBANK', 'PRIMEBANK\nYour MyPrime One-Time Password (OTP) is 383479. It will be valid for the next 3 minutes. Do NOT share this OTP with anyone.\nSIM2_\nSubId：2\n2026-05-19 11:46:49\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-19 11:46:50', '2026-05-19 11:46:50');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (34, 'GP 2GB98TK', 'sms_device', 'GP 2GB98TK', 'GP 2GB98TK\n২জিবি ৩দিন ৯৮টাকায়! অফার পেতে ডায়াল *১২১*৩০৯১# https://mygp.li/Ps98k\nSIM1_\nSubId：1\n2026-05-19 12:02:42\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-19 12:02:43', '2026-05-19 12:02:43');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (35, 'EASYFASHION', 'sms_device', 'EASYFASHION', 'EASYFASHION\nইজি ফ্যাশনের ঈদ উপহার ৪ লক্ষ পণ্যে আকর্ষনীয় ছাড় www.easyfashion.com.bd\nSIM2_\nSubId：2\n2026-05-19 12:33:07\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-19 12:34:10', '2026-05-19 12:34:10');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (36, 'Anike Redmi', 'sms_device', 'bKash', 'bKash\nYou have received Tk 15,000.00 from 01972777070. Fee Tk 0.00. Balance Tk 28,508.88. TrxID DEJ0DC16B2 at 19/05/2026 13:04\nSIM2_\nSubId：2\n2026-05-19 13:04:13\nAnike Redmi', '01972777070', 'DEJ0DC16B2', 'DEJ0DC16B2', NULL, '15000.00', NULL, 'pending', NULL, NULL, NULL, 'No customer matched this bKash sender number.', '2026-05-19 13:04:14', '2026-05-19 13:04:14');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (37, 'Robi', 'sms_device', 'Robi', 'Robi\nআপনার 1598Tk 4018D প্যাকের অবশিষ্ট ভলিউম: 15 GB,(কিনেছিলেন 2024-12-23 23:47:52), মেয়াদ 2035-12-24 00:00:00 পর্যন্ত। ইন্টারনেট প্যাক কিনতে ডায়াল করুন *4#, অথবা ভিসিট মাই রবি অ্যাপ\nSIM2_\nSubId：2\n2026-05-19 16:56:22\nAnike Redmi', NULL, NULL, NULL, NULL, '4018.00', NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-19 17:30:57', '2026-05-19 17:30:57');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (38, 'SpecialDeal', 'sms_device', 'SpecialDeal', 'SpecialDeal\nরবিতে বেশি মেয়াদে বেশি ইন্টারনেট ! ২৯৮ টাকায় ৩০জিবি -৬০দিন, ডায়াল *৪১২*২১২# অথবা ১৫০টাকায় ১৫জিবি -৩০দিন, ডায়াল *৪১২*২৫০#অথবা ১০৪টাকায় ১০জিবি -৩০দিন, ডায়াল *৪১২*২২০# অথবা ভিজিট  https://cutt.ly/myRobiOffer \nSIM2_\nSubId：2\n2026-05-20 07:14:42\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-20 10:11:51', '2026-05-20 10:11:51');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (39, 'GP 60MIN', 'sms_device', 'GP 60MIN', 'GP 60MIN\n৩৯টাকায় ৬০মিনিট ২৪ঘণ্টা! ডায়াল *১২১*০২৯# https://mygp.li/th9\nSIM1_\nSubId：1\n2026-05-20 09:44:32\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-20 10:13:12', '2026-05-20 10:13:12');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (40, 'ROBIMINUTES', 'sms_device', 'ROBIMINUTES', 'ROBIMINUTES\n৫০০মিনিট ৩০দিন ৳৩১৪; ডায়াল *412*811#\n২৪০মিনিট ৩০দিন ৳১৬৫; ডায়াল *412*806#\n৯০মিনিট ৭দিন ৳৬৩; ডায়াল *412*805# /ঘ্যাচাং রিচার্জ অথবা https://cutt.ly/MyRobiVoice\nSIM2_\nSubId：2\n2026-05-20 09:44:41\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-20 10:13:16', '2026-05-20 10:13:16');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (41, 'Apnar Offer', 'sms_device', 'Apnar Offer', 'Apnar Offer\nরবিতে স্পেশাল অফার! ৯টাকায় ১জিবি -৪ঘন্টা ,ডায়াল *৪১২*৭০১# অথবা ৫০টাকায় ৩জিবি -৩দিন, ডায়াল *৪১২*২২২# অথবা ৭০টাকায় ৫জিবি -৭দিন, ডায়াল *৪১২*২১৯# অথবা ভিজিট  https://cutt.ly/myRobiOffer\nSIM2_\nSubId：2\n2026-05-20 08:38:39\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-20 10:15:37', '2026-05-20 10:15:37');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (42, 'Apnar Offer', 'sms_device', 'Apnar Offer', 'Apnar Offer\nরবিতে স্পেশাল অফার! ৯টাকায় ১জিবি -৪ঘন্টা ,ডায়াল *৪১২*৭০১# অথবা ৫০টাকায় ৫জিবি -৫দিন, ডায়াল *৪১২*২১১# অথবা ৭০টাকায় ৭জিবি -৭দিন, ডায়াল *৪১২*৭০২# অথবা ভিজিট  https://cutt.ly/myRobiOffer \nSIM2_\nSubId：2\n2026-05-20 07:41:07\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-20 10:17:03', '2026-05-20 10:17:03');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (43, 'GP Bundle', 'sms_device', 'GP Bundle', 'GP Bundle\n৭৭টাকা ২জিবি+৫০মিনিট (৭দিন), ডায়াল *১২১*৫৪০৪# বা https://mygp.li/TG\nSIM1_\nSubId：1\n2026-05-20 10:51:07\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-20 10:51:10', '2026-05-20 10:51:10');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (44, 'GP5GB100TK', 'sms_device', 'GP5GB100TK', 'GP5GB100TK\nআজকের অফার ৫জিবি ১০০টাকা ৭দিন। ডায়াল *১২১*৫৪৫০# বা https://mygp.li/Rt\nSIM1_\nSubId：1\n2026-05-20 10:51:10\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-20 10:51:10', '2026-05-20 10:51:10');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (45, 'PRIMEBANK', 'sms_device', 'PRIMEBANK', 'PRIMEBANK\nBDT100000.00 has been credited in A/c 219711****617 on 20-May-26. Your current balance is BDT 852,284.69\nSIM2_\nSubId：2\n2026-05-20 16:31:04\nAnike Redmi', NULL, NULL, NULL, NULL, '100000.00', NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-20 16:37:18', '2026-05-20 16:37:18');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (46, 'Anike Redmi', 'sms_device', 'bKash', 'bKash\nCash In Tk 900.00 from 01701696669 successful. Fee Tk 0.00. Balance Tk 7,020.88. TrxID DEK2ESDXPE at 20/05/2026 16:31. Download App: https://bKa.sh/8app\nSIM2_\nSubId：2\n2026-05-20 16:31:18\nAnike Redmi', '01701696669', 'DEK2ESDXPE', 'DEK2ESDXPE', NULL, '900.00', NULL, 'pending', NULL, NULL, NULL, 'No customer matched this bKash sender number.', '2026-05-20 16:37:19', '2026-05-20 16:37:19');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (47, 'PRIMEBANK', 'sms_device', 'PRIMEBANK', 'PRIMEBANK\nঈদের কেনাকাটায় প্রাইম ব্যাংক কার্ডে উপভোগ করুন দারুণ অফার: t.ly/xSGJ_\nSIM2_\nSubId：2\n2026-05-20 16:15:07\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-20 16:43:59', '2026-05-20 16:43:59');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (48, 'SpecialDeal', 'sms_device', 'SpecialDeal', 'SpecialDeal\nরবিতে বেশি মেয়াদে বেশি ইন্টারনেট ! ২৯৮ টাকায় ৩০জিবি -৬০দিন, ডায়াল *৪১২*২১২# অথবা ১৫০টাকায় ১৫জিবি -৩০দিন, ডায়াল *৪১২*২৫০#অথবা ১০৪টাকায় ১০জিবি -৩০দিন, ডায়াল *৪১২*২২০# অথবা ভিজিট  https://cutt.ly/myRobiOffer \nSIM2_\nSubId：2\n2026-05-21 07:10:37\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-21 07:10:38', '2026-05-21 07:10:38');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (49, 'Apnar Offer', 'sms_device', 'Apnar Offer', 'Apnar Offer\nরবিতে স্পেশাল অফার! ৯টাকায় ১জিবি -৪ঘন্টা ,ডায়াল *৪১২*৭০১# অথবা ৫০টাকায় ৫জিবি -৫দিন, ডায়াল *৪১২*২১১# অথবা ৭০টাকায় ৭জিবি -৭দিন, ডায়াল *৪১২*৭০২# অথবা ভিজিট  https://cutt.ly/myRobiOffer \nSIM2_\nSubId：2\n2026-05-21 07:41:54\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-21 07:41:54', '2026-05-21 07:41:54');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (50, 'Apnar Offer', 'sms_device', 'Apnar Offer', 'Apnar Offer\nরবিতে স্পেশাল অফার! ৯টাকায় ১জিবি -৪ঘন্টা ,ডায়াল *৪১২*৭০১# অথবা ৫০টাকায় ৩জিবি -৩দিন, ডায়াল *৪১২*২২২# অথবা ৭০টাকায় ৫জিবি -৭দিন, ডায়াল *৪১২*২১৯# অথবা ভিজিট  https://cutt.ly/myRobiOffer\nSIM2_\nSubId：2\n2026-05-21 08:11:02\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-21 08:11:02', '2026-05-21 08:11:02');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (51, 'Anike Redmi', 'sms_device', 'bKash', 'bKash\nCash In Tk 1,000.00 from 01315545102 successful. Fee Tk 0.00. Balance Tk 8,020.88. TrxID DEL0FLZ3T2 at 21/05/2026 10:02. Download App: https://bKa.sh/8app\nSIM2_\nSubId：2\n2026-05-21 10:02:52\nAnike Redmi', '01315545102', 'DEL0FLZ3T2', 'DEL0FLZ3T2', NULL, '1000.00', NULL, 'pending', NULL, NULL, NULL, 'No customer matched this bKash sender number.', '2026-05-21 10:02:52', '2026-05-21 10:02:52');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (52, 'ROBIMINUTES', 'sms_device', 'ROBIMINUTES', 'ROBIMINUTES\n৫০০মিনিট ৩০দিন ৳৩১৪; ডায়াল *412*811#\n২৪০মিনিট ৩০দিন ৳১৬৫; ডায়াল *412*806#\n৯০মিনিট ৭দিন ৳৬৩; ডায়াল *412*805# /ঘ্যাচাং রিচার্জ অথবা https://cutt.ly/MyRobiVoice\nSIM2_\nSubId：2\n2026-05-21 10:40:03\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-21 10:40:03', '2026-05-21 10:40:03');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (53, 'GP 45MIN', 'sms_device', 'GP 45MIN', 'GP 45MIN\n২৯টাকায় ৪৫মিনিট ২৪ঘণ্টা! ডায়াল *১২১*৪৪০২# https://mygp.li/t9k\nSIM1_\nSubId：1\n2026-05-21 11:06:07\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-21 11:06:07', '2026-05-21 11:06:07');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (54, 'GP Bundle', 'sms_device', 'GP Bundle', 'GP Bundle\n১০জিবি+১৫০মিনিট ২৯৭ টাকা(৩০দিন),ডায়াল *১২১*৫২৫৮# বা https://mygp.li/xF\nSIM1_\nSubId：1\n2026-05-21 11:21:07\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-21 11:21:08', '2026-05-21 11:21:08');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (55, 'GP1GB29TK', 'sms_device', 'GP1GB29TK', 'GP1GB29TK\nআজকের অফার! ১ জিবি ১২ঘণ্টা ২৯টাকা। ডায়াল *১২১*৫৮৯৮#\nSIM1_\nSubId：1\n2026-05-21 11:21:09\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-21 11:21:08', '2026-05-21 11:21:08');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (56, 'PRIMEBANK', 'sms_device', 'PRIMEBANK', 'PRIMEBANK\nBDT20000.00 has been deposited in A/c 219711****617 on 21-May-26. Current balance is BDT 879,284.69\nSIM2_\nSubId：2\n2026-05-21 12:47:37\nAnike Redmi', NULL, NULL, NULL, NULL, '20000.00', NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-21 12:47:37', '2026-05-21 12:47:37');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (57, 'TWELVE', 'sms_device', 'TWELVE', 'TWELVE\nTWELVE- এ ঈদ উপলক্ষ্যে নির্দিষ্ট পণ্যে ৩০% ও ব্যাংক কার্ডে ১০% ছাড়!\nSIM2_\nSubId：2\n2026-05-21 16:35:37\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-21 16:35:37', '2026-05-21 16:35:37');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (58, '+8801877731263', 'sms_device', '+8801877731263', '+8801877731263\nlat:23.911349 lon:89.131134\nspeed:0.00\nT:26/05/21 17:57\nhttps://maps.google.com/maps?f=q&q=23.911349,89.131134&z=16\nPwr: ON Door: OFF ACC: OFF\nSIM2_\nSubId：2\n2026-05-21 23:57:32\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-21 23:57:33', '2026-05-21 23:57:33');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (59, 'CARNIVAL', 'sms_device', 'CARNIVAL', 'CARNIVAL\nসতর্কবার্তা! বিলের কল শুধু 09642363693 থেকে। অন্য নম্বর এড়িয়ে চলুন।\nSIM2_\nSubId：2\n2026-05-22 02:16:19\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-22 02:16:19', '2026-05-22 02:16:19');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (60, 'ROBIMINUTES', 'sms_device', 'ROBIMINUTES', 'ROBIMINUTES\nরবিতে ৩০০মি ৳২০০;*123*0200*1#  এবং ২৪০মি ৳১৭৫;*123*160*1# ডায়ালে! মেয়াদ ৩০দিন \nSIM2_\nSubId：2\n2026-05-22 06:49:34\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-22 06:49:34', '2026-05-22 06:49:34');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (61, 'SpecialDeal', 'sms_device', 'SpecialDeal', 'SpecialDeal\nরবিতে বেশি মেয়াদে বেশি ইন্টারনেট ! ২৯৮ টাকায় ৩০জিবি -৬০দিন, ডায়াল *৪১২*২১২# অথবা ১৫০টাকায় ১৫জিবি -৩০দিন, ডায়াল *৪১২*২৫০#অথবা ১০৪টাকায় ৮জিবি -৩০দিন, ডায়াল *৪১২*২২০# অথবা ভিজিট  https://cutt.ly/myRobiOffer \nSIM2_\nSubId：2\n2026-05-22 07:11:36\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-22 07:11:36', '2026-05-22 07:11:36');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (62, 'ROBIMINUTES', 'sms_device', 'ROBIMINUTES', 'ROBIMINUTES\n৫০০মিনিট ৩০দিন ৳৩১৪; ডায়াল *412*811#\n২৪০মিনিট ৩০দিন ৳১৬৫; ডায়াল *412*806#\n৯০মিনিট ৭দিন ৳৬৩; ডায়াল *412*805# /ঘ্যাচাং রিচার্জ অথবা https://cutt.ly/MyRobiVoice\nSIM2_\nSubId：2\n2026-05-22 09:43:16\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-22 09:43:16', '2026-05-22 09:43:16');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (63, 'PRIMEBANK', 'sms_device', 'PRIMEBANK', 'PRIMEBANK\nসুপারশপে কেনাকাটায় ক্রেডিট কার্ডে ১০% ক্যাশব্যাক: t.ly/ERW40\nSIM2_\nSubId：2\n2026-05-22 10:31:38\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-22 10:31:38', '2026-05-22 10:31:38');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (64, 'Islami Bank', 'sms_device', 'Islami Bank', 'Islami Bank\n Use 523605 as login code.\n Never share your PIN/OTP/CVV with anyone.\n  - IBBPLC iBanking\n  \nSIM2_\nSubId：2\n2026-05-23 00:31:53\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-23 00:31:53', '2026-05-23 00:31:53');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (65, 'PRIMEBANK', 'sms_device', 'PRIMEBANK', 'PRIMEBANK\nYour MyPrime One-Time Password (OTP) is 608695. It will be valid for the next 3 minutes. Do NOT share this OTP with anyone.\nSIM2_\nSubId：2\n2026-05-23 00:35:17\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-23 00:35:17', '2026-05-23 00:35:17');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (66, 'GovtInfo', 'sms_device', 'GovtInfo', 'GovtInfo\nআজ ৩১ মে ‘বিশ্ব তামাকমুক্ত দিবস’। দিবসটির প্রতিপাদ্য: “প্রলোভনের মুখোশ উন্মোচন করি, তামাক ও নিকোটিনের আসক্তি প্রতিরোধ করি”। -জাতীয় তামাক নিয়ন্ত্রণ সেল, স্বাস্থ্য সেবা বিভাগ।\nSIM2_\nSubId：2\n2026-05-31 07:29:33\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-31 07:29:34', '2026-05-31 07:29:34');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (67, 'ROBI 30DAYS', 'sms_device', 'ROBI 30DAYS', 'ROBI 30DAYS\nরবিতে কম খরচে বেশি কথা:\n৪০০মিনিট ৩০দিন-৳২৫৪; *412*821#\n৩২০মিনিট ৩০দিন-৳২০৩; *412*820#\n৪৮০মিনিট ৩০দিন ৳৩০৩; *412*822#\nডায়াল/ঘ্যাচাং রিচার্জ বা https://cutt.ly/MyRobiVoice\nSIM2_\nSubId：2\n2026-05-31 08:38:35\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-31 08:38:36', '2026-05-31 08:38:36');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (68, 'GP 3GB98TK', 'sms_device', 'GP 3GB98TK', 'GP 3GB98TK\n৩জিবি ৩দিন ৯৮টাকায়! অফার পেতে ডায়াল *১২১*৩০৯১# https://mygp.li/Ps98k\nSIM1_\nSubId：1\n2026-05-31 09:00:39\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-31 09:00:40', '2026-05-31 09:00:40');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (69, 'GP Bundle', 'sms_device', 'GP Bundle', 'GP Bundle\n৭৭টাকা ২জিবি+৫০মিনিট (৭দিন), ডায়াল *১২১*৫৪০৪# বা https://mygp.li/TG\nSIM1_\nSubId：1\n2026-05-31 11:21:31\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-31 11:21:32', '2026-05-31 11:21:32');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (70, 'GP1GB29TK', 'sms_device', 'GP1GB29TK', 'GP1GB29TK\nআজকের অফার! ১ জিবি ১২ঘণ্টা ২৯টাকা। ডায়াল *১২১*৫৮৯৮#\nSIM1_\nSubId：1\n2026-05-31 11:21:33\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-31 11:21:34', '2026-05-31 11:21:34');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (71, 'MYGPINFO', 'sms_device', 'MYGPINFO', 'MYGPINFO\nআপনার বিস্তারিত কল হিস্টোরি দেখতে মাইজিপি আ্যপ এ আসুন https://mygp.li/Zch\nSIM1_\nSubId：1\n2026-05-31 18:11:09\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-05-31 18:53:06', '2026-05-31 18:53:06');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (72, 'ROBI 30DAYS', 'sms_device', 'ROBI 30DAYS', 'ROBI 30DAYS\nরবিতে কম খরচে বেশি কথা:\n৪০০মিনিট ৩০দিন-৳২৫৪; *412*821#\n৩২০মিনিট ৩০দিন-৳২০৩; *412*820#\n৪৮০মিনিট ৩০দিন ৳৩০৩; *412*822#\nডায়াল/ঘ্যাচাং রিচার্জ বা https://cutt.ly/MyRobiVoice\nSIM2_\nSubId：2\n2026-06-01 08:39:04\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-06-01 08:39:05', '2026-06-01 08:39:05');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (73, 'Apnar Offer', 'sms_device', 'Apnar Offer', 'Apnar Offer\nরবিতে স্পেশাল অফার! ৯টাকায় ১জিবি -৪ঘন্টা ,ডায়াল *৪১২*৭০১# অথবা ৫০টাকায় ৫জিবি -৫দিন, ডায়াল *৪১২*২১১# অথবা ৭০টাকায় ৭জিবি -৭দিন, ডায়াল *৪১২*৭০২# অথবা ভিজিট  https://cutt.ly/myRobiOffer \nSIM2_\nSubId：2\n2026-06-01 09:05:52\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-06-01 09:05:53', '2026-06-01 09:05:53');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (74, 'GP 45MIN', 'sms_device', 'GP 45MIN', 'GP 45MIN\n২৯টাকায় ৪৫মিনিট ২৪ঘণ্টা! ডায়াল *১২১*৪৪০২# https://mygp.li/t9k\nSIM1_\nSubId：1\n2026-06-01 09:14:38\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-06-01 09:14:38', '2026-06-01 09:14:38');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (75, 'GP Bundle', 'sms_device', 'GP Bundle', 'GP Bundle\n১০জিবি+১৫০মিনিট ২৯৭ টাকা(৩০দিন),ডায়াল *১২১*৫২৫৮# বা https://mygp.li/xF\nSIM1_\nSubId：1\n2026-06-01 10:23:44\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-06-01 10:23:45', '2026-06-01 10:23:45');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (76, 'GP5GB100TK', 'sms_device', 'GP5GB100TK', 'GP5GB100TK\nআজকের অফার ৫জিবি ১০০টাকা ৭দিন। ডায়াল *১২১*৫৪৫০# বা https://mygp.li/Rt\nSIM1_\nSubId：1\n2026-06-01 10:23:46\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-06-01 10:23:46', '2026-06-01 10:23:46');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (77, 'Anike Redmi', 'sms_device', 'bKash', 'bKash\nYour bKash verification code is 521328. The code will expire in 2 minutes. Please do NOT share your OTP or PIN with others.\nSIM2_\nSubId：2\n2026-06-01 10:54:57\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-06-01 10:54:58', '2026-06-01 10:54:58');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (78, 'Anike Redmi', 'sms_device', 'bKash', 'bKash\nPayment of Tk 500.00 to AMBERIT is successful. Balance Tk 11,000.88. TrxID DF17SPOY3D at 01/06/2026 10:55\nSIM2_\nSubId：2\n2026-06-01 10:55:18\nAnike Redmi', NULL, 'DF17SPOY3D', 'DF17SPOY3D', NULL, '500.00', '2026-01-06', 'pending', NULL, NULL, NULL, 'No reference or sender number found.', '2026-06-01 10:55:19', '2026-06-01 10:55:19');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (79, '+8809611123123', 'sms_device', '+8809611123123', '+8809611123123\nআপনার প্রদানকৃত টাকা 500 জমা হয়েছে। TxnID: 86648@c0518317-ba85-4a6d-b600-74516c330814\nSIM2_\nSubId：2\n2026-06-01 10:55:35\nAnike Redmi', NULL, '86648', NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-06-01 10:55:36', '2026-06-01 10:55:36');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (80, '+8809611123123', 'sms_device', '+8809611123123', '+8809611123123\nধন্যবাদ, আপনার নতুন বিলিং সাইকেল শুরুর তারিখ Jun 01,2026 এবং প্যাকেজ মূল্য টাকা 500, আমাদের অনলাইন পোর্টাল ব্যবহার করতে লগইন করুন https://myswift.amberit.com.bd/\nSIM2_\nSubId：2\n2026-06-01 10:56:19\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-06-01 10:56:20', '2026-06-01 10:56:20');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (81, 'ROBI 30DAYS', 'sms_device', 'ROBI 30DAYS', 'ROBI 30DAYS\nরবিতে কম খরচে বেশি কথা:\n২৭০মিনিট ৩০দিন-৳১৭৪; *412*819#\n২৪০মিনিট ৩০দিন-৳১৫৪; *412*818#\n৪৮০মিনিট ৩০দিন ৳৩০৩; *412*822#\nডায়াল/ঘ্যাচাং রিচার্জ বা https://cutt.ly/MyRobiVoice\nSIM2_\nSubId：2\n2026-06-01 12:07:53\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-06-01 12:24:45', '2026-06-01 12:24:45');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (82, 'ROBIMINUTES', 'sms_device', 'ROBIMINUTES', 'ROBIMINUTES\nরবিতে কম খরচে বেশি কথা:\n২০০মিনিট ১৫দিন-৳১৩৪; *412*817#\n১৪০মিনিট ৭দিন-৳৮৯; *412*816#\n৪৮০মিনিট ৩০দিন ৳৩০৩; *412*822#\nডায়াল/ঘ্যাচাং রিচার্জ বা https://cutt.ly/MyRobiVoice\nSIM2_\nSubId：2\n2026-06-01 12:35:25\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-06-01 12:35:25', '2026-06-01 12:35:25');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (83, '+8801834601509', 'sms_device', '+8801834601509', '+8801834601509\nঅ্যাকাউন্টে বোনাস Withdraw: https://bit.ly/49RprxJ\nSIM2_\nSubId：2\n2026-06-01 21:01:29\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-06-01 21:01:30', '2026-06-01 21:01:30');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (84, 'ROBI 30DAYS', 'sms_device', 'ROBI 30DAYS', 'ROBI 30DAYS\nরবিতে কম খরচে বেশি কথা:\n৪০০মিনিট ৩০দিন-৳২৫৪; *412*821#\n৩২০মিনিট ৩০দিন-৳২০৩; *412*820#\n৪৮০মিনিট ৩০দিন ৳৩০৩; *412*822#\nডায়াল/ঘ্যাচাং রিচার্জ বা https://cutt.ly/MyRobiVoice\nSIM2_\nSubId：2\n2026-06-02 07:44:16\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-06-02 07:44:17', '2026-06-02 07:44:17');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (85, 'Robi Deal', 'sms_device', 'Robi Deal', 'Robi Deal\nরবিতে আজ আপনার জন্য মাত্র  ৫০টাকায় ৫জিবি সোশ্যাল প্যাক - মেয়াদ ৩০ দিন , আজই কিনতে ডায়াল *412*747#\nSIM2_\nSubId：2\n2026-06-02 08:08:08\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-06-02 08:08:09', '2026-06-02 08:08:09');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (86, 'Anike Redmi', 'sms_device', 'bKash', 'bKash\nYou have received Tk 600.00 from 01718102879. Fee Tk 0.00. Balance Tk 12,100.88. TrxID DF29ULGYWV at 02/06/2026 22:22\nSIM2_\nSubId：2\n2026-06-02 22:22:29\nAnike Redmi', '01718102879', 'DF29ULGYWV', 'DF29ULGYWV', NULL, '600.00', '2026-02-06', 'pending', NULL, NULL, NULL, 'No customer matched this bKash sender number.', '2026-06-02 22:22:30', '2026-06-02 22:22:30');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (87, 'RobiWifi', 'sms_device', 'RobiWifi', 'RobiWifi\nDear Customer, your OTP code is 272092. Please do not share this PIN with anyone.\nSIM2_\nSubId：2\n2026-06-03 01:38:41\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-06-03 01:38:42', '2026-06-03 01:38:42');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (88, 'PRIMEBANK', 'sms_device', 'PRIMEBANK', 'PRIMEBANK\nYour MyPrime One-Time Password (OTP) is 595612. It will be valid for the next 3 minutes. Do NOT share this OTP with anyone.\nSIM2_\nSubId：2\n2026-06-16 13:45:01\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-06-16 13:45:03', '2026-06-16 13:45:03');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (89, 'CDBL', 'sms_device', 'CDBL', 'CDBL\nThe Following Debit/Credit took place on 16-06-2026  in your BO account No: \n  \n 1201830075353082  \n BATBC 120 Credit; \n GP 200 Credit; \n WALTON 135 Credit; \n \n 1201830075939614  \n GP 150 Credit; \n WALTON 150 Credit; \n \n For any query contact CDBL\nSIM2_\nSubId：2\n2026-06-16 18:52:56\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-06-16 18:52:57', '2026-06-16 18:52:57');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (90, 'CDBL', 'sms_device', 'CDBL', 'CDBL\nThe Following Debit/Credit took place on 16-06-2026  in your BO account No: \n  \n 1201830075353082  \n BATBC 120 Credit; \n GP 200 Credit; \n WALTON 135 Credit; \n \n 1201830075939614  \n GP 150 Credit; \n WALTON 150 Credit; \n \n For any query contact CDBL\nSIM2_\nSubId：2\n2026-06-16 18:52:56\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-06-16 18:54:21', '2026-06-16 18:54:21');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (91, 'PRIMEBANK', 'sms_device', 'PRIMEBANK', 'PRIMEBANK\nBDT5000.00 has been credited in A/c 219711****617 on 16-Jun-26. Your current balance is BDT 897,545.11\nSIM2_\nSubId：2\n2026-06-16 23:46:29\nAnike Redmi', NULL, NULL, NULL, NULL, '5000.00', NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-06-16 23:46:30', '2026-06-16 23:46:30');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (92, 'Super_Deal', 'sms_device', 'Super_Deal', 'Super_Deal\nরবিতে বেশি মেয়াদে বেশি ইন্টারনেট !  ২৪৪ টাকায় ২৫জিবি -৩০দিন, ডায়াল *৪১২*৭৪৬# অথবা ১৫০টাকায় ১০জিবি -৩০দিন, ডায়াল *৪১২*৭৪৯# অথবা ১০৪টাকায় ৫জিবি -৩০দিন, ডায়াল *৪১২*৭৪৮# অথবা ভিজিট  https://cutt.ly/myRobiOffer \nSIM2_\nSubId：2\n2026-06-17 09:10:38\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-06-17 09:10:40', '2026-06-17 09:10:40');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (93, 'PRIMEBANK', 'sms_device', 'PRIMEBANK', 'PRIMEBANK\nYour Fixed Deposit A/c 219741****158 will be matured on 24-Jun-26. Prime Bank!\nSIM2_\nSubId：2\n2026-06-17 09:22:17\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-06-17 09:22:19', '2026-06-17 09:22:19');
INSERT INTO `bkash_sms_payments` (`id`, `entry_by`, `entry_by_type`, `sms_sender`, `raw_sms`, `customer_number`, `trx_id`, `ledger_trx_id`, `reference`, `amount`, `payment_date`, `status`, `customer_id`, `invoice_id`, `payment_id`, `message`, `created_at`, `updated_at`) VALUES (94, 'ROBIMINUTES', 'sms_device', 'ROBIMINUTES', 'ROBIMINUTES\n১৫০মিনিট ৩০দিন ৳১০০; ডায়াল *412*525#\n৮০মিনিট ৭দিন ৳৫৩; ডায়াল *412*524#\nhttps://cutt.ly/MyRobiVoice\nSIM2_\nSubId：2\n2026-06-17 11:49:28\nAnike Redmi', NULL, NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, NULL, 'Could not parse bKash amount or TrxID from SMS.', '2026-06-17 11:49:29', '2026-06-17 11:49:29');

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
  `connection_id` varchar(255) DEFAULT NULL,
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
  `is_customer` tinyint(1) NOT NULL DEFAULT 1,
  `is_vendor` tinyint(1) NOT NULL DEFAULT 0,
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `customers` (`id`, `entry_by`, `entry_by_type`, `name`, `phone`, `email`, `connection_id`, `mikrotik_username`, `mikrotik_password`, `mikrotik_router_id`, `address`, `status`, `never_suspend`, `grace_until`, `grace_days`, `grace_used_at`, `account_balance`, `is_customer`, `is_vendor`, `created_at`, `updated_at`) VALUES (1, '2', 'user', 'Fine Fit', '01980078076', NULL, 'AUTO-20260517173213-133', NULL, NULL, NULL, 'Not provided', 'active', 0, NULL, NULL, NULL, '0.00', 1, 0, '2026-05-17 17:32:13', '2026-05-17 17:32:13');
INSERT INTO `customers` (`id`, `entry_by`, `entry_by_type`, `name`, `phone`, `email`, `connection_id`, `mikrotik_username`, `mikrotik_password`, `mikrotik_router_id`, `address`, `status`, `never_suspend`, `grace_until`, `grace_days`, `grace_used_at`, `account_balance`, `is_customer`, `is_vendor`, `created_at`, `updated_at`) VALUES (2, '2', 'user', 'Police Super, Kushtia.', '01713374214', NULL, 'AUTO-20260517224449-104', 'AUTO-20260517224449-104', 'eyJpdiI6ImZXaURVOG9ZOGdVUUY3bzFFWFB1YXc9PSIsInZhbHVlIjoiVFlURURaYnVDcnVIUWRySHo4RHJxQT09IiwibWFjIjoiMjNkMTUzN2Q2MzE4NjNiNDExNjQ3ODYzYjcwYTYwYzI1MzU5OTA0ZjdjMmRiY2I1NDFmZTRlZWNmNzY5NDUxOSIsInRhZyI6IiJ9', NULL, 'Kushtia.', 'active', 0, NULL, NULL, NULL, '0.00', 1, 0, '2026-05-17 22:44:49', '2026-05-17 23:27:36');
INSERT INTO `customers` (`id`, `entry_by`, `entry_by_type`, `name`, `phone`, `email`, `connection_id`, `mikrotik_username`, `mikrotik_password`, `mikrotik_router_id`, `address`, `status`, `never_suspend`, `grace_until`, `grace_days`, `grace_used_at`, `account_balance`, `is_customer`, `is_vendor`, `created_at`, `updated_at`) VALUES (3, '2', 'user', 'Mr Rabby', '01722770880', NULL, 'AUTO-20260517233858-952', NULL, NULL, NULL, '', 'active', 0, NULL, NULL, NULL, '0.00', 1, 0, '2026-05-17 23:38:58', '2026-05-17 23:38:58');
INSERT INTO `customers` (`id`, `entry_by`, `entry_by_type`, `name`, `phone`, `email`, `connection_id`, `mikrotik_username`, `mikrotik_password`, `mikrotik_router_id`, `address`, `status`, `never_suspend`, `grace_until`, `grace_days`, `grace_used_at`, `account_balance`, `is_customer`, `is_vendor`, `created_at`, `updated_at`) VALUES (4, '2', 'user', 'Limon Pervez', '01711665128', NULL, 'AUTO-20260518000349-917', NULL, NULL, NULL, '', 'active', 0, NULL, NULL, NULL, '0.00', 1, 0, '2026-05-18 00:03:49', '2026-05-18 00:03:49');
INSERT INTO `customers` (`id`, `entry_by`, `entry_by_type`, `name`, `phone`, `email`, `connection_id`, `mikrotik_username`, `mikrotik_password`, `mikrotik_router_id`, `address`, `status`, `never_suspend`, `grace_until`, `grace_days`, `grace_used_at`, `account_balance`, `is_customer`, `is_vendor`, `created_at`, `updated_at`) VALUES (5, '2', 'user', 'VIP Rice Mills', '01749695960', NULL, 'AUTO-20260518103347-850', NULL, NULL, NULL, '', 'active', 0, NULL, NULL, NULL, '0.00', 1, 0, '2026-05-18 10:33:47', '2026-05-18 10:33:47');
INSERT INTO `customers` (`id`, `entry_by`, `entry_by_type`, `name`, `phone`, `email`, `connection_id`, `mikrotik_username`, `mikrotik_password`, `mikrotik_router_id`, `address`, `status`, `never_suspend`, `grace_until`, `grace_days`, `grace_used_at`, `account_balance`, `is_customer`, `is_vendor`, `created_at`, `updated_at`) VALUES (6, '2', 'user', 'Rashid Group', '01755678991', NULL, 'AUTO-20260518103655-696', NULL, NULL, NULL, '', 'active', 0, NULL, NULL, NULL, '0.00', 1, 0, '2026-05-18 10:36:55', '2026-05-18 10:36:55');
INSERT INTO `customers` (`id`, `entry_by`, `entry_by_type`, `name`, `phone`, `email`, `connection_id`, `mikrotik_username`, `mikrotik_password`, `mikrotik_router_id`, `address`, `status`, `never_suspend`, `grace_until`, `grace_days`, `grace_used_at`, `account_balance`, `is_customer`, `is_vendor`, `created_at`, `updated_at`) VALUES (7, '2', 'user', 'Aamra Networks Limited', '029841100', NULL, '1', '1', 'eyJpdiI6InpFeXVmOTR3dFZvQWlrb1ZIVzdFMWc9PSIsInZhbHVlIjoibkJBRStBeXFLVTUyVlE0WC9FYmc4QT09IiwibWFjIjoiMjI4Nzc2Y2RlMzBhM2ZiNzY3NjcxZDBkYmY3MzcxNjFmYzIzMjlhYWQ5YzBkNWQzZDc5ZmEyNTUzNzBhZDIxOCIsInRhZyI6IiJ9', NULL, 'Safura Tower, (15th & 16th Floor) \r\n20, Kemal Ataturk Avenue, \r\nBanani C/A, Dhaka-1213.', 'active', 0, NULL, NULL, NULL, '0.00', 1, 0, '2026-05-18 12:07:21', '2026-05-18 12:07:21');
INSERT INTO `customers` (`id`, `entry_by`, `entry_by_type`, `name`, `phone`, `email`, `connection_id`, `mikrotik_username`, `mikrotik_password`, `mikrotik_router_id`, `address`, `status`, `never_suspend`, `grace_until`, `grace_days`, `grace_used_at`, `account_balance`, `is_customer`, `is_vendor`, `created_at`, `updated_at`) VALUES (8, '2', 'user', 'Dristy Eye Hospital', '+880 1988-815737', NULL, '815737', '815737', 'eyJpdiI6Ik43bERPNk9iS011UFh4TjBGd1I1bHc9PSIsInZhbHVlIjoibVlWWkdyK21QNDlEWkdxQ2wxUWpLZz09IiwibWFjIjoiZmExODI3MDg0ZDE4YTg1ODZhMDA2ZDI0YTM1MjgxYjE0Y2NiZjkxOWRlYjc0Mzk2ZGE5NGRhYTkyZmYzODU5NyIsInRhZyI6IiJ9', NULL, 'Kushtia.', 'active', 0, NULL, NULL, NULL, '0.00', 1, 0, '2026-05-19 19:46:33', '2026-05-19 19:46:33');
INSERT INTO `customers` (`id`, `entry_by`, `entry_by_type`, `name`, `phone`, `email`, `connection_id`, `mikrotik_username`, `mikrotik_password`, `mikrotik_router_id`, `address`, `status`, `never_suspend`, `grace_until`, `grace_days`, `grace_used_at`, `account_balance`, `is_customer`, `is_vendor`, `created_at`, `updated_at`) VALUES (9, '2', 'user', 'Drick ICT', '01970444806', NULL, 'AUTO-20260520160848-250', NULL, NULL, NULL, '', 'active', 0, NULL, NULL, NULL, '0.00', 1, 0, '2026-05-20 16:08:48', '2026-05-20 16:08:48');
INSERT INTO `customers` (`id`, `entry_by`, `entry_by_type`, `name`, `phone`, `email`, `connection_id`, `mikrotik_username`, `mikrotik_password`, `mikrotik_router_id`, `address`, `status`, `never_suspend`, `grace_until`, `grace_days`, `grace_used_at`, `account_balance`, `is_customer`, `is_vendor`, `created_at`, `updated_at`) VALUES (10, '2', 'user', 'Kushtia Municipality', '01730163372', NULL, 'AUTO-20260615144201-569', NULL, NULL, NULL, '', 'active', 0, NULL, NULL, NULL, '0.00', 1, 0, '2026-06-15 14:42:01', '2026-06-15 14:42:01');
INSERT INTO `customers` (`id`, `entry_by`, `entry_by_type`, `name`, `phone`, `email`, `connection_id`, `mikrotik_username`, `mikrotik_password`, `mikrotik_router_id`, `address`, `status`, `never_suspend`, `grace_until`, `grace_days`, `grace_used_at`, `account_balance`, `is_customer`, `is_vendor`, `created_at`, `updated_at`) VALUES (11, '2', 'user', 'Moula Vi', '01320474796', NULL, 'AUTO-20260615205409-617', NULL, NULL, NULL, '', 'active', 0, NULL, NULL, NULL, '0.00', 1, 0, '2026-06-15 20:54:09', '2026-06-15 20:54:09');

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
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_type` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `serial_numbers` text DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=329 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (2, '2', 'user', 1, NULL, 'Internet Service May 2026', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 17:32:59', '2026-05-17 17:32:59');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (32, '2', 'user', 2, NULL, 'SP office', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (33, '2', 'user', 2, NULL, 'SP Bangalow', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (34, '2', 'user', 2, NULL, 'Addl SP 2', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (35, '2', 'user', 2, NULL, 'Addl SP 3', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (36, '2', 'user', 2, NULL, 'Addl SP 4', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (37, '2', 'user', 2, NULL, 'Accounts office', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (38, '2', 'user', 2, NULL, 'DIO', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (39, '2', 'user', 2, NULL, 'Hospital Doctors', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (40, '2', 'user', 2, NULL, 'Hospital Labs', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (41, '2', 'user', 2, NULL, 'ICT', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (42, '2', 'user', 2, NULL, 'Cyber Crime', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (43, '2', 'user', 2, NULL, 'Reserve Service', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (44, '2', 'user', 2, NULL, 'Reserve PIMS', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (45, '2', 'user', 2, NULL, 'Police Dispass', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (46, '2', 'user', 2, NULL, 'Crime', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (47, '2', 'user', 2, NULL, 'BIT Police', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (48, '2', 'user', 2, NULL, 'Cloth', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (49, '2', 'user', 2, NULL, 'D Store', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (50, '2', 'user', 2, NULL, 'DB', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (51, '2', 'user', 2, NULL, 'Conference Room', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (52, '2', 'user', 2, NULL, 'DSB office', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (53, '2', 'user', 2, NULL, 'Addl SP 3 Bangalaw', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (54, '2', 'user', 2, NULL, 'Head Assistant', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (55, '2', 'user', 2, NULL, 'Addl SP 5 Circle Office', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (56, '2', 'user', 2, NULL, 'PUNAK', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (57, '2', 'user', 2, NULL, 'Ration', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (58, '2', 'user', 2, NULL, 'Reserve RO 1', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (59, '2', 'user', 2, NULL, 'RI', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (60, '2', 'user', 2, NULL, 'Stano 1', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 22:50:53', '2026-05-17 22:50:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (61, '2', 'user', 6, NULL, 'SP office', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (62, '2', 'user', 6, NULL, 'SP Bangalow', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (63, '2', 'user', 6, NULL, 'Addl SP 2', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (64, '2', 'user', 6, NULL, 'Addl SP 3', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (65, '2', 'user', 6, NULL, 'Addl SP 4', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (66, '2', 'user', 6, NULL, 'Accounts office', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (67, '2', 'user', 6, NULL, 'DIO', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (68, '2', 'user', 6, NULL, 'Hospital Doctors', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (69, '2', 'user', 6, NULL, 'Hospital Labs', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (70, '2', 'user', 6, NULL, 'ICT', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (71, '2', 'user', 6, NULL, 'Cyber Crime', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (72, '2', 'user', 6, NULL, 'Reserve Service', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (73, '2', 'user', 6, NULL, 'Reserve PIMS', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (74, '2', 'user', 6, NULL, 'Police Dispass', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (75, '2', 'user', 6, NULL, 'Crime', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (76, '2', 'user', 6, NULL, 'BIT Police', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (77, '2', 'user', 6, NULL, 'Cloth', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (78, '2', 'user', 6, NULL, 'D Store', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (79, '2', 'user', 6, NULL, 'DB', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (80, '2', 'user', 6, NULL, 'Conference Room', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (81, '2', 'user', 6, NULL, 'DSB office', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (82, '2', 'user', 6, NULL, 'Addl SP 3 Bangalaw', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (83, '2', 'user', 6, NULL, 'Head Assistant', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (84, '2', 'user', 6, NULL, 'Addl SP 5 Circle Office', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (85, '2', 'user', 6, NULL, 'PUNAK', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (86, '2', 'user', 6, NULL, 'Ration', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (87, '2', 'user', 6, NULL, 'Reserve RO 1', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (88, '2', 'user', 6, NULL, 'RI', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (89, '2', 'user', 6, NULL, 'Stano 1', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (90, '2', 'user', 7, NULL, 'SP office', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (91, '2', 'user', 7, NULL, 'SP Bangalow', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (92, '2', 'user', 7, NULL, 'Addl SP 2', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (93, '2', 'user', 7, NULL, 'Addl SP 3', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (94, '2', 'user', 7, NULL, 'Addl SP 4', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (95, '2', 'user', 7, NULL, 'Accounts office', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (96, '2', 'user', 7, NULL, 'DIO', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (97, '2', 'user', 7, NULL, 'Hospital Doctors', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (98, '2', 'user', 7, NULL, 'Hospital Labs', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (99, '2', 'user', 7, NULL, 'ICT', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (100, '2', 'user', 7, NULL, 'Cyber Crime', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (101, '2', 'user', 7, NULL, 'Reserve Service', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (102, '2', 'user', 7, NULL, 'Reserve PIMS', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (103, '2', 'user', 7, NULL, 'Police Dispass', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (104, '2', 'user', 7, NULL, 'Crime', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (105, '2', 'user', 7, NULL, 'BIT Police', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (106, '2', 'user', 7, NULL, 'Cloth', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (107, '2', 'user', 7, NULL, 'D Store', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (108, '2', 'user', 7, NULL, 'DB', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (109, '2', 'user', 7, NULL, 'Conference Room', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (110, '2', 'user', 7, NULL, 'DSB office', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (111, '2', 'user', 7, NULL, 'Addl SP 3 Bangalaw', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (112, '2', 'user', 7, NULL, 'Head Assistant', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (113, '2', 'user', 7, NULL, 'Addl SP 5 Circle Office', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (114, '2', 'user', 7, NULL, 'PUNAK', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (115, '2', 'user', 7, NULL, 'Ration', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (116, '2', 'user', 7, NULL, 'Reserve RO 1', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (117, '2', 'user', 7, NULL, 'RI', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (118, '2', 'user', 7, NULL, 'Stano 1', NULL, 1, '800.00', '800.00', NULL, NULL, NULL, NULL, NULL, '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (162, '2', 'user', 10, NULL, 'Intervet Service', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-18 10:33:47', '2026-05-18 10:33:47');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (163, '2', 'user', 11, NULL, 'Intervet Service', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-18 10:34:21', '2026-05-18 10:34:21');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (164, '2', 'user', 12, NULL, 'Intervet Service', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-18 10:35:10', '2026-05-18 10:35:10');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (165, '2', 'user', 13, NULL, 'Internet Service', NULL, 1, '7000.00', '7000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-18 10:36:55', '2026-05-18 10:36:55');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (166, '2', 'user', 14, NULL, 'Internet Service', NULL, 1, '7000.00', '7000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-18 10:37:38', '2026-05-18 10:37:38');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (167, '2', 'user', 15, NULL, 'Internet Service', NULL, 1, '7000.00', '7000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-18 10:38:06', '2026-05-18 10:38:06');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (168, '2', 'user', 16, NULL, 'Internet Service', NULL, 1, '7000.00', '7000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-18 10:38:40', '2026-05-18 10:38:40');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (169, '2', 'user', 17, NULL, 'Internet Service', NULL, 1, '7000.00', '7000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-18 10:39:08', '2026-05-18 10:39:08');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (170, '2', 'user', 18, NULL, 'Internet Service', NULL, 1, '7000.00', '7000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-18 10:39:47', '2026-05-18 10:39:47');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (171, '2', 'user', 19, NULL, 'Share Internet Connection ( 80 mbps, Public IP)', NULL, 1, '1700.00', '1700.00', NULL, NULL, NULL, NULL, NULL, '2026-05-18 12:09:58', '2026-05-18 12:09:58');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (172, '2', 'user', 19, NULL, 'One time Setup Charge', NULL, 1, '2500.00', '2500.00', NULL, NULL, NULL, NULL, NULL, '2026-05-18 12:09:58', '2026-05-18 12:09:58');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (202, '2', 'user', 21, NULL, 'Internet Service', NULL, 1, '2500.00', '2500.00', NULL, NULL, NULL, NULL, NULL, '2026-05-19 19:47:14', '2026-05-19 19:47:14');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (204, '2', 'user', 22, NULL, 'Internet Service (Customs Mor)', NULL, 1, '750.00', '750.00', NULL, NULL, NULL, NULL, NULL, '2026-05-19 19:48:59', '2026-05-19 19:48:59');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (205, '2', 'user', 23, NULL, 'Microtik Adapter', NULL, 1, '600.00', '600.00', NULL, NULL, NULL, NULL, NULL, '2026-05-20 16:08:48', '2026-05-20 16:08:48');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (206, '2', 'user', 23, NULL, 'Service Charge', NULL, 1, '300.00', '300.00', NULL, NULL, NULL, NULL, NULL, '2026-05-20 16:08:48', '2026-05-20 16:08:48');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (218, '2', 'user', 24, NULL, 'Tenda TX2L Pro AX1500 Mbps Gigabit Dual-Band Wi-Fi 6 Router', NULL, 1, '3000.00', '3000.00', NULL, NULL, NULL, NULL, NULL, '2026-05-27 11:59:11', '2026-05-27 11:59:11');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (219, '2', 'user', 25, NULL, 'Jinko Solar', NULL, 6250, '26.00', '162500.00', NULL, NULL, NULL, NULL, NULL, '2026-06-04 19:37:45', '2026-06-04 19:37:45');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (220, '2', 'user', 25, NULL, 'Growwatt 600 Plus Inverter', NULL, 1, '64000.00', '64000.00', NULL, NULL, NULL, NULL, NULL, '2026-06-04 19:37:45', '2026-06-04 19:37:45');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (221, '2', 'user', 25, NULL, 'CTT 48 Volt Battery', NULL, 1, '95000.00', '95000.00', NULL, NULL, NULL, NULL, NULL, '2026-06-04 19:37:45', '2026-06-04 19:37:45');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (222, '2', 'user', 25, NULL, 'DC MCB', NULL, 2, '1000.00', '2000.00', NULL, NULL, NULL, NULL, NULL, '2026-06-04 19:37:45', '2026-06-04 19:37:45');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (223, '2', 'user', 25, NULL, 'DC SPD', NULL, 2, '1400.00', '2800.00', NULL, NULL, NULL, NULL, NULL, '2026-06-04 19:37:45', '2026-06-04 19:37:45');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (224, '2', 'user', 25, NULL, 'DC Battery Cable', NULL, 2, '700.00', '1400.00', NULL, NULL, NULL, NULL, NULL, '2026-06-04 19:37:45', '2026-06-04 19:37:45');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (225, '2', 'user', 25, NULL, 'DC Cable', NULL, 64, '120.00', '7680.00', NULL, NULL, NULL, NULL, NULL, '2026-06-04 19:37:45', '2026-06-04 19:37:45');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (226, '2', 'user', 25, NULL, 'Cable Lugs', NULL, 4, '50.00', '200.00', NULL, NULL, NULL, NULL, NULL, '2026-06-04 19:37:45', '2026-06-04 19:37:45');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (227, '2', 'user', 25, NULL, 'Magnatic Contract', NULL, 1, '3000.00', '3000.00', NULL, NULL, NULL, NULL, NULL, '2026-06-04 19:37:45', '2026-06-04 19:37:45');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (228, '2', 'user', 25, NULL, 'MC4 Connector', NULL, 4, '100.00', '400.00', NULL, NULL, NULL, NULL, NULL, '2026-06-04 19:37:45', '2026-06-04 19:37:45');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (231, '2', 'user', 26, NULL, 'Internet Service', NULL, 1, '1000.00', '1000.00', NULL, NULL, NULL, NULL, NULL, '2026-06-15 13:36:59', '2026-06-15 13:36:59');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (242, '2', 'user', 27, NULL, 'Internet Service April - June 2026', NULL, 3, '10000.00', '30000.00', NULL, NULL, NULL, NULL, NULL, '2026-06-15 15:35:43', '2026-06-15 15:35:43');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (244, '2', 'user', 28, NULL, 'Magnatic Contractor', NULL, 1, '1800.00', '1800.00', NULL, NULL, NULL, NULL, NULL, '2026-06-15 20:51:52', '2026-06-15 20:51:52');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (289, '2', 'user', 20, NULL, 'Magnatic Contractor', NULL, 1, '1800.00', '1800.00', NULL, NULL, NULL, NULL, NULL, '2026-06-15 23:08:26', '2026-06-15 23:08:26');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (296, '2', 'user', 29, NULL, 'Jinko Solar 625W N Type Byfacial', NULL, 5, '16250.00', '81250.00', NULL, NULL, NULL, NULL, NULL, '2026-06-15 23:44:19', '2026-06-15 23:44:19');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (297, '2', 'user', 29, NULL, 'Growwatt 600 Plus Inverter', NULL, 1, '65000.00', '65000.00', NULL, NULL, NULL, NULL, NULL, '2026-06-15 23:44:19', '2026-06-15 23:44:19');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (298, '2', 'user', 29, NULL, 'Sako 52.2 Volt 100 Amp H Battery', NULL, 1, '98000.00', '98000.00', NULL, NULL, NULL, NULL, NULL, '2026-06-15 23:44:19', '2026-06-15 23:44:19');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (308, '2', 'user', 9, NULL, 'Longi Solar', NULL, 6150, '25.00', '153750.00', NULL, NULL, NULL, NULL, NULL, '2026-06-15 23:48:43', '2026-06-15 23:48:43');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (309, '2', 'user', 9, NULL, 'Growwatt 600 Plus Inverter', NULL, 1, '62000.00', '62000.00', NULL, NULL, NULL, NULL, NULL, '2026-06-15 23:48:43', '2026-06-15 23:48:43');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (310, '2', 'user', 9, NULL, '48 Volt Battery', NULL, 1, '95000.00', '95000.00', NULL, NULL, NULL, NULL, NULL, '2026-06-15 23:48:43', '2026-06-15 23:48:43');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (311, '2', 'user', 9, NULL, 'DC MCB', NULL, 2, '1000.00', '2000.00', NULL, NULL, NULL, NULL, NULL, '2026-06-15 23:48:43', '2026-06-15 23:48:43');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (312, '2', 'user', 9, NULL, 'DC SPD', NULL, 2, '1400.00', '2800.00', NULL, NULL, NULL, NULL, NULL, '2026-06-15 23:48:43', '2026-06-15 23:48:43');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (313, '2', 'user', 9, NULL, 'DC Battery Cable', NULL, 2, '700.00', '1400.00', NULL, NULL, NULL, NULL, NULL, '2026-06-15 23:48:43', '2026-06-15 23:48:43');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (314, '2', 'user', 9, NULL, 'DC Cable', NULL, 64, '120.00', '7680.00', NULL, NULL, NULL, NULL, NULL, '2026-06-15 23:48:43', '2026-06-15 23:48:43');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (315, '2', 'user', 9, NULL, 'Cable Lugs', NULL, 4, '50.00', '200.00', NULL, NULL, NULL, NULL, NULL, '2026-06-15 23:48:43', '2026-06-15 23:48:43');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (316, '2', 'user', 9, NULL, 'Jinko Solar', NULL, 625, '25.00', '15625.00', NULL, NULL, NULL, NULL, NULL, '2026-06-15 23:48:43', '2026-06-15 23:48:43');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (317, '2', 'user', 8, NULL, 'Jinko Solar', NULL, 6250, '26.00', '162500.00', NULL, NULL, NULL, NULL, NULL, '2026-06-15 23:48:52', '2026-06-15 23:48:52');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (318, '2', 'user', 8, NULL, 'Growwatt 600 Plus Inverter', NULL, 1, '64000.00', '64000.00', NULL, NULL, NULL, NULL, NULL, '2026-06-15 23:48:52', '2026-06-15 23:48:52');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (319, '2', 'user', 8, NULL, 'CTT 48 Volt Battery', NULL, 1, '95000.00', '95000.00', NULL, NULL, NULL, NULL, NULL, '2026-06-15 23:48:52', '2026-06-15 23:48:52');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (320, '2', 'user', 8, NULL, 'DC MCB', NULL, 2, '1000.00', '2000.00', NULL, NULL, NULL, NULL, NULL, '2026-06-15 23:48:52', '2026-06-15 23:48:52');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (321, '2', 'user', 8, NULL, 'DC SPD', NULL, 2, '1400.00', '2800.00', NULL, NULL, NULL, NULL, NULL, '2026-06-15 23:48:52', '2026-06-15 23:48:52');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (322, '2', 'user', 8, NULL, 'DC Battery Cable', NULL, 2, '700.00', '1400.00', NULL, NULL, NULL, NULL, NULL, '2026-06-15 23:48:52', '2026-06-15 23:48:52');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (323, '2', 'user', 8, NULL, 'DC Cable', NULL, 64, '120.00', '7680.00', NULL, NULL, NULL, NULL, NULL, '2026-06-15 23:48:52', '2026-06-15 23:48:52');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (324, '2', 'user', 8, NULL, 'Cable Lugs', NULL, 4, '50.00', '200.00', NULL, NULL, NULL, NULL, NULL, '2026-06-15 23:48:52', '2026-06-15 23:48:52');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (325, '2', 'user', 8, NULL, 'Magnatic Contract', NULL, 1, '3000.00', '3000.00', NULL, NULL, NULL, NULL, NULL, '2026-06-15 23:48:52', '2026-06-15 23:48:52');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (326, '2', 'user', 8, NULL, 'MC4 Connector', NULL, 4, '100.00', '400.00', NULL, NULL, NULL, NULL, NULL, '2026-06-15 23:48:53', '2026-06-15 23:48:53');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (327, '2', 'user', 30, NULL, 'Internet Service', NULL, 1, '2500.00', '2500.00', NULL, NULL, NULL, NULL, NULL, '2026-06-16 20:37:42', '2026-06-16 20:37:42');
INSERT INTO `invoice_items` (`id`, `entry_by`, `entry_by_type`, `invoice_id`, `product_id`, `product_name`, `product_type`, `quantity`, `unit_price`, `total`, `serial_numbers`, `warranty_days`, `service_guarantee_days`, `service_guarantee_until`, `service_note`, `created_at`, `updated_at`) VALUES (328, '2', 'user', 31, NULL, 'Internet Service (Customs Mor)', NULL, 1, '750.00', '750.00', NULL, NULL, NULL, NULL, NULL, '2026-06-16 20:40:11', '2026-06-16 20:40:11');

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
  CONSTRAINT `invoices_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `discount_type`, `discount_value`, `vat`, `vat_type`, `vat_value`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `payment_note`, `public_note`, `show_public_note`, `private_note`, `created_at`, `updated_at`) VALUES (1, '2', 'user', 1, 'INV-2026-05-00001', '2026-05', 'product', '1000.00', '0.00', 'amount', '0.00', '0.00', 'amount', '0.00', '1000.00', '1000.00', '0.00', 'paid', '2026-05-17 22:14:07', NULL, NULL, NULL, 0, NULL, '2026-05-17 17:32:13', '2026-05-17 22:14:07');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `discount_type`, `discount_value`, `vat`, `vat_type`, `vat_value`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `payment_note`, `public_note`, `show_public_note`, `private_note`, `created_at`, `updated_at`) VALUES (2, '2', 'user', 2, 'INV-2026-05-00002', '2026-04', 'product', '26000.00', '0.00', 'amount', '0.00', '0.00', 'amount', '0.00', '26000.00', '26000.00', '0.00', 'paid', '2026-06-01 19:03:15', NULL, NULL, NULL, 0, NULL, '2026-05-17 22:44:49', '2026-06-01 19:03:23');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `discount_type`, `discount_value`, `vat`, `vat_type`, `vat_value`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `payment_note`, `public_note`, `show_public_note`, `private_note`, `created_at`, `updated_at`) VALUES (6, '2', 'user', 2, 'INV-2026-05-00002-02', '2026-05', 'product', '26000.00', '0.00', 'amount', '0.00', '0.00', 'amount', '0.00', '26000.00', '0.00', '26000.00', 'unpaid', NULL, NULL, NULL, NULL, 0, NULL, '2026-05-17 23:20:06', '2026-05-17 23:20:06');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `discount_type`, `discount_value`, `vat`, `vat_type`, `vat_value`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `payment_note`, `public_note`, `show_public_note`, `private_note`, `created_at`, `updated_at`) VALUES (7, '2', 'user', 2, 'INV-2026-06-00002', '2026-06', 'product', '26000.00', '0.00', 'amount', '0.00', '0.00', 'amount', '0.00', '26000.00', '0.00', '26000.00', 'unpaid', NULL, NULL, NULL, NULL, 0, NULL, '2026-05-17 23:21:23', '2026-05-17 23:21:23');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `discount_type`, `discount_value`, `vat`, `vat_type`, `vat_value`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `payment_note`, `public_note`, `show_public_note`, `private_note`, `created_at`, `updated_at`) VALUES (8, '2', 'user', 3, 'INV-2026-05-00003', '2026-05', 'product', '338980.00', '0.00', 'amount', '0.00', '0.00', 'amount', '0.00', '338980.00', '240000.00', '98980.00', 'partial', NULL, NULL, NULL, NULL, 0, 'Growwatt Faruq - 01716296622 ref Fahad - 01626449970\r\n\r\nCTT Battery', '2026-05-17 23:38:58', '2026-06-15 23:48:52');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `discount_type`, `discount_value`, `vat`, `vat_type`, `vat_value`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `payment_note`, `public_note`, `show_public_note`, `private_note`, `created_at`, `updated_at`) VALUES (9, '2', 'user', 4, 'INV-2026-06-00003', '2026-06', 'product', '340455.00', '0.00', 'amount', '0.00', '0.00', 'amount', '0.00', '340455.00', '0.00', '340455.00', 'unpaid', NULL, NULL, NULL, NULL, 0, 'Growwatt Faruq - 01716296622 ref Fahad - 01626449970\r\n\r\nSako Battery - Orient Computers', '2026-05-18 00:01:15', '2026-06-15 23:48:43');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `discount_type`, `discount_value`, `vat`, `vat_type`, `vat_value`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `payment_note`, `public_note`, `show_public_note`, `private_note`, `created_at`, `updated_at`) VALUES (10, '2', 'user', 5, 'INV-2026-03-00005', '2026-03', 'product', '1000.00', '0.00', 'amount', '0.00', '0.00', 'amount', '0.00', '1000.00', '0.00', '1000.00', 'unpaid', NULL, NULL, NULL, NULL, 0, NULL, '2026-05-18 10:33:47', '2026-05-18 10:33:47');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `discount_type`, `discount_value`, `vat`, `vat_type`, `vat_value`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `payment_note`, `public_note`, `show_public_note`, `private_note`, `created_at`, `updated_at`) VALUES (11, '2', 'user', 5, 'INV-2026-04-00005', '2026-04', 'product', '1000.00', '0.00', 'amount', '0.00', '0.00', 'amount', '0.00', '1000.00', '0.00', '1000.00', 'unpaid', NULL, NULL, NULL, NULL, 0, NULL, '2026-05-18 10:34:21', '2026-05-18 10:34:21');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `discount_type`, `discount_value`, `vat`, `vat_type`, `vat_value`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `payment_note`, `public_note`, `show_public_note`, `private_note`, `created_at`, `updated_at`) VALUES (12, '2', 'user', 5, 'INV-2026-05-00005', '2026-05', 'product', '1000.00', '0.00', 'amount', '0.00', '0.00', 'amount', '0.00', '1000.00', '0.00', '1000.00', 'unpaid', NULL, NULL, NULL, NULL, 0, NULL, '2026-05-18 10:35:10', '2026-05-18 10:35:10');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `discount_type`, `discount_value`, `vat`, `vat_type`, `vat_value`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `payment_note`, `public_note`, `show_public_note`, `private_note`, `created_at`, `updated_at`) VALUES (13, '2', 'user', 6, 'INV-2025-11-00006', '2025-11', 'product', '7000.00', '0.00', 'amount', '0.00', '0.00', 'amount', '0.00', '7000.00', '0.00', '7000.00', 'unpaid', NULL, NULL, NULL, NULL, 0, NULL, '2026-05-18 10:36:55', '2026-05-18 10:36:55');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `discount_type`, `discount_value`, `vat`, `vat_type`, `vat_value`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `payment_note`, `public_note`, `show_public_note`, `private_note`, `created_at`, `updated_at`) VALUES (14, '2', 'user', 6, 'INV-2025-12-00006', '2025-12', 'product', '7000.00', '0.00', 'amount', '0.00', '0.00', 'amount', '0.00', '7000.00', '0.00', '7000.00', 'unpaid', NULL, NULL, NULL, NULL, 0, NULL, '2026-05-18 10:37:38', '2026-05-18 10:37:38');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `discount_type`, `discount_value`, `vat`, `vat_type`, `vat_value`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `payment_note`, `public_note`, `show_public_note`, `private_note`, `created_at`, `updated_at`) VALUES (15, '2', 'user', 6, 'INV-2026-01-00006', '2026-01', 'product', '7000.00', '0.00', 'amount', '0.00', '0.00', 'amount', '0.00', '7000.00', '0.00', '7000.00', 'unpaid', NULL, NULL, NULL, NULL, 0, NULL, '2026-05-18 10:38:06', '2026-05-18 10:38:06');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `discount_type`, `discount_value`, `vat`, `vat_type`, `vat_value`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `payment_note`, `public_note`, `show_public_note`, `private_note`, `created_at`, `updated_at`) VALUES (16, '2', 'user', 6, 'INV-2026-02-00006', '2026-02', 'product', '7000.00', '0.00', 'amount', '0.00', '0.00', 'amount', '0.00', '7000.00', '0.00', '7000.00', 'unpaid', NULL, NULL, NULL, NULL, 0, NULL, '2026-05-18 10:38:40', '2026-05-18 10:38:40');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `discount_type`, `discount_value`, `vat`, `vat_type`, `vat_value`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `payment_note`, `public_note`, `show_public_note`, `private_note`, `created_at`, `updated_at`) VALUES (17, '2', 'user', 6, 'INV-2026-03-00006', '2026-03', 'product', '7000.00', '0.00', 'amount', '0.00', '0.00', 'amount', '0.00', '7000.00', '0.00', '7000.00', 'unpaid', NULL, NULL, NULL, NULL, 0, NULL, '2026-05-18 10:39:08', '2026-05-18 10:39:08');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `discount_type`, `discount_value`, `vat`, `vat_type`, `vat_value`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `payment_note`, `public_note`, `show_public_note`, `private_note`, `created_at`, `updated_at`) VALUES (18, '2', 'user', 6, 'INV-2026-04-00006', '2026-04', 'product', '7000.00', '0.00', 'amount', '0.00', '0.00', 'amount', '0.00', '7000.00', '0.00', '7000.00', 'unpaid', NULL, NULL, NULL, NULL, 0, NULL, '2026-05-18 10:39:47', '2026-05-18 10:39:47');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `discount_type`, `discount_value`, `vat`, `vat_type`, `vat_value`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `payment_note`, `public_note`, `show_public_note`, `private_note`, `created_at`, `updated_at`) VALUES (19, '2', 'user', 7, 'INV-2026-05-00007', '2026-05', 'product', '4200.00', '0.00', 'amount', '0.00', '0.00', 'amount', '0.00', '4200.00', '4200.00', '0.00', 'paid', '2026-06-15 20:28:28', NULL, NULL, NULL, 0, NULL, '2026-05-18 12:09:58', '2026-06-15 20:28:28');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `discount_type`, `discount_value`, `vat`, `vat_type`, `vat_value`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `payment_note`, `public_note`, `show_public_note`, `private_note`, `created_at`, `updated_at`) VALUES (20, '2', 'user', 3, 'INV-2026-05-00003-02', '2026-05', 'product', '1800.00', '0.00', 'amount', '0.00', '0.00', 'amount', '0.00', '1800.00', '0.00', '1800.00', 'unpaid', NULL, NULL, NULL, 'এইটা ইনভয়েজ নোট\r\nএই Magnetic Contract এর জন্য শামিম ভায়েরা টাকা নিয়েছিলেন\r\n1200 টাকা দিয়ে কিনে ১৮০০ টাকা নিয়েছিল', 1, 'এটা গোপন নোট\r\n2য়টা পরে ৩০০০ টাকা ধরে Magnet দেওয়া হয়েছে', '2026-05-19 15:34:54', '2026-06-15 23:08:26');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `discount_type`, `discount_value`, `vat`, `vat_type`, `vat_value`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `payment_note`, `public_note`, `show_public_note`, `private_note`, `created_at`, `updated_at`) VALUES (21, '2', 'user', 8, 'INV-2026-05-00008', '2026-05', 'product', '2500.00', '0.00', 'amount', '0.00', '0.00', 'amount', '0.00', '2500.00', '2500.00', '0.00', 'paid', '2026-06-01 18:56:49', NULL, NULL, NULL, 0, NULL, '2026-05-19 19:47:14', '2026-06-01 18:56:49');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `discount_type`, `discount_value`, `vat`, `vat_type`, `vat_value`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `payment_note`, `public_note`, `show_public_note`, `private_note`, `created_at`, `updated_at`) VALUES (22, '2', 'user', 8, 'INV-2026-06-00008', '2026-05', 'product', '750.00', '0.00', 'amount', '0.00', '0.00', 'amount', '0.00', '750.00', '750.00', '0.00', 'paid', '2026-06-01 18:55:49', NULL, NULL, NULL, 0, NULL, '2026-05-19 19:48:22', '2026-06-01 18:55:49');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `discount_type`, `discount_value`, `vat`, `vat_type`, `vat_value`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `payment_note`, `public_note`, `show_public_note`, `private_note`, `created_at`, `updated_at`) VALUES (23, '2', 'user', 9, 'INV-2026-05-00009', '2026-05', 'product', '900.00', '0.00', 'amount', '0.00', '0.00', 'amount', '0.00', '900.00', '900.00', '0.00', 'paid', '2026-05-21 17:42:09', NULL, NULL, NULL, 0, NULL, '2026-05-20 16:08:48', '2026-05-21 17:43:37');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `discount_type`, `discount_value`, `vat`, `vat_type`, `vat_value`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `payment_note`, `public_note`, `show_public_note`, `private_note`, `created_at`, `updated_at`) VALUES (24, '2', 'user', 8, 'INV-2026-05-00008-03', '2026-05', 'product', '3000.00', '0.00', 'amount', '0.00', '0.00', 'amount', '0.00', '3000.00', '3000.00', '0.00', 'paid', '2026-06-01 18:26:15', NULL, NULL, NULL, 0, NULL, '2026-05-27 11:58:51', '2026-06-01 18:54:54');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `discount_type`, `discount_value`, `vat`, `vat_type`, `vat_value`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `payment_note`, `public_note`, `show_public_note`, `private_note`, `created_at`, `updated_at`) VALUES (25, '2', 'user', 3, 'INV-2026-06-00003-02', '2026-06', 'product', '338980.00', '0.00', 'amount', '0.00', '0.00', 'amount', '0.00', '338980.00', '240000.00', '98980.00', 'partial', NULL, NULL, NULL, NULL, 0, NULL, '2026-06-04 19:37:45', '2026-06-15 20:44:26');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `discount_type`, `discount_value`, `vat`, `vat_type`, `vat_value`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `payment_note`, `public_note`, `show_public_note`, `private_note`, `created_at`, `updated_at`) VALUES (26, '2', 'user', 1, 'INV-2026-06-00001', '2026-06', 'product', '1000.00', '0.00', 'amount', '0.00', '0.00', 'amount', '0.00', '1000.00', '1000.00', '0.00', 'paid', NULL, NULL, NULL, NULL, 0, NULL, '2026-06-15 13:35:47', '2026-06-16 21:50:23');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `discount_type`, `discount_value`, `vat`, `vat_type`, `vat_value`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `payment_note`, `public_note`, `show_public_note`, `private_note`, `created_at`, `updated_at`) VALUES (27, '2', 'user', 10, 'INV-2026-06-00010', '2026-06', 'product', '30000.00', '0.00', 'amount', '0.00', '1500.00', 'percent', '5.00', '31500.00', '0.00', '31500.00', 'unpaid', NULL, NULL, NULL, NULL, 0, NULL, '2026-06-15 14:42:01', '2026-06-15 15:35:43');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `discount_type`, `discount_value`, `vat`, `vat_type`, `vat_value`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `payment_note`, `public_note`, `show_public_note`, `private_note`, `created_at`, `updated_at`) VALUES (28, '2', 'user', 3, 'INV-2026-06-00003-03', '2026-06', 'product', '1800.00', '0.00', 'amount', '0.00', '0.00', 'amount', '0.00', '1800.00', '0.00', '1800.00', 'unpaid', NULL, NULL, NULL, NULL, 0, NULL, '2026-06-15 20:51:52', '2026-06-15 20:51:52');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `discount_type`, `discount_value`, `vat`, `vat_type`, `vat_value`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `payment_note`, `public_note`, `show_public_note`, `private_note`, `created_at`, `updated_at`) VALUES (29, '2', 'user', 11, 'INV-2026-07-00003', '2026-07', 'product', '244250.00', '0.00', 'amount', '0.00', '0.00', 'amount', '0.00', '244250.00', '140000.00', '104250.00', 'partial', NULL, NULL, 'IBBL Kushtia - 14117', NULL, 0, 'Growatt - 20/06/2006 Tasfia Solar - 01977361970\r\nSako Battery - Orient Computers 13/06/2026', '2026-06-15 20:53:19', '2026-06-16 15:53:27');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `discount_type`, `discount_value`, `vat`, `vat_type`, `vat_value`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `payment_note`, `public_note`, `show_public_note`, `private_note`, `created_at`, `updated_at`) VALUES (30, '2', 'user', 8, 'INV-2026-06-00008-02', '2026-06', 'product', '2500.00', '0.00', 'amount', '0.00', '0.00', 'amount', '0.00', '2500.00', '0.00', '2500.00', 'unpaid', NULL, NULL, NULL, NULL, 0, NULL, '2026-06-16 20:37:42', '2026-06-16 20:37:42');
INSERT INTO `invoices` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_no`, `billing_month`, `invoice_type`, `subtotal`, `discount`, `discount_type`, `discount_value`, `vat`, `vat_type`, `vat_value`, `total`, `paid_amount`, `due_amount`, `status`, `finalized_at`, `due_date`, `payment_note`, `public_note`, `show_public_note`, `private_note`, `created_at`, `updated_at`) VALUES (31, '2', 'user', 8, 'INV-2026-06-00008-03', '2026-06', 'product', '750.00', '0.00', 'amount', '0.00', '0.00', 'amount', '0.00', '750.00', '0.00', '750.00', 'unpaid', NULL, NULL, NULL, NULL, 0, NULL, '2026-06-16 20:40:11', '2026-06-16 20:40:11');

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
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33, '2026_05_18_000001_create_olt_onus_table', 2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34, '2026_05_18_000002_create_olt_devices_and_live_fields', 3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35, '2026_05_18_000003_add_access_method_to_olt_devices', 4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36, '2026_05_18_000004_add_read_context_commands_to_olt_devices', 4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37, '2026_05_18_000005_add_pon_ports_to_olt_devices', 4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38, '2026_05_18_000006_convert_olt_tables_to_utf8mb4', 5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39, '2026_05_18_000007_merge_duplicate_olt_onu_live_rows', 5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40, '2026_05_18_000008_clear_stale_olt_parser_errors', 5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41, '2026_05_18_000009_add_olt_onu_register_history_fields', 5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42, '2026_05_18_000010_add_onu_alarm_command_to_olt_devices', 5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43, '2026_05_18_000011_add_brand_profile_to_olt_devices', 5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44, '2026_05_18_000012_add_onu_vlan_command_to_olt_devices', 5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45, '2026_05_18_000013_add_onu_learned_macs', 5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46, '2026_05_18_000014_create_olt_protocol_profiles_table', 5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47, '2026_05_18_000015_update_hsgq_gpon_profile_polling_defaults', 5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48, '2026_05_18_000016_fix_hsgq_gpon_vlan_mac_commands', 5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49, '2026_05_18_000017_add_olt_write_commands_to_protocol_profiles', 5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50, '2026_05_18_000018_set_hsgq_gpon_vlan_write_command', 5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51, '2026_05_18_000019_fix_hsgq_gpon_native_vlan_write_command', 5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52, '2026_05_19_000001_fix_hsgq_gpon_native_vlan_port_id', 5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53, '2026_05_19_000002_restore_hsgq_gpon_context_native_vlan', 5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54, '2026_05_22_000001_add_note_to_olt_onus_table', 5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55, '2026_06_02_000001_add_snmp_polling_to_olt_devices', 6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56, '2026_06_02_000002_make_customer_connection_id_nullable', 6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57, '2026_06_02_000003_add_party_roles_to_customers', 6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58, '2026_06_02_000004_create_purchase_bills_and_product_serials', 6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59, '2026_06_02_000005_add_brand_and_subcategory_to_products', 6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60, '2026_06_02_000006_create_product_categories_table', 6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61, '2026_06_02_000007_add_track_inventory_to_products', 7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62, '2026_06_02_000008_add_barcode_serial_and_warranty_defaults_to_products', 7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63, '2026_06_04_000001_add_product_and_serials_to_invoice_items', 7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64, '2026_06_04_000002_add_service_and_warranty_claims', 7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65, '2026_06_05_000001_create_network_map_features_table', 8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66, '2026_06_15_000001_add_adjustment_inputs_to_invoices_table', 8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67, '2026_06_15_000002_add_notes_to_invoices_table', 9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68, '2026_06_15_000003_create_app_settings_and_add_payment_note_to_invoices', 10);

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

INSERT INTO `mikrotik_routers` (`id`, `entry_by`, `entry_by_type`, `name`, `ip_address`, `api_port`, `pppoe_sync_interval_minutes`, `username`, `password`, `status`, `last_api_status`, `api_status_since`, `last_ping_status`, `ping_status_since`, `last_api_latency_ms`, `last_ping_latency_ms`, `last_checked_at`, `last_online_at`, `last_offline_at`, `last_ping_at`, `last_connection_message`, `last_pppoe_sync_at`, `inactive_pppoe_profile`, `last_pppoe_sync_summary`, `notes`, `created_at`, `updated_at`) VALUES (1, 'system', 'system', 'Main MikroTik', '192.168.6.1', 8728, 10, 'admin', 'eyJpdiI6InA2V0VleXBFYVd2MHVhLzE3cXZUOXc9PSIsInZhbHVlIjoiSjBsM3A1K3l3OVBpQnVIYWtOR0h3dz09IiwibWFjIjoiYjQyNzAzOTQ3MTEwNTcyODEzYmRiZjBkNTMzZTAzNTg1OTEyNTA2MTk1YjYxMWIwNTYyOWE4YTk3ZDE5OWExYyIsInRhZyI6IiJ9', 'active', 'online', '2026-06-04 21:24:20', 'online', '2026-05-18 00:50:49', 24, 1, '2026-06-04 21:24:20', '2026-06-04 21:24:20', '2026-05-18 22:23:30', '2026-05-18 00:50:49', 'MikroTik API login successful.', NULL, 'inactive', NULL, 'Default router added from local setup.', '2026-05-17 14:55:12', '2026-06-04 21:24:20');

DROP TABLE IF EXISTS `network_map_features`;
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


DROP TABLE IF EXISTS `olt_devices`;
CREATE TABLE `olt_devices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_by` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `brand` varchar(255) NOT NULL DEFAULT 'HSGQ',
  `protocol_profile` varchar(255) NOT NULL DEFAULT 'hsgq_epon',
  `host` varchar(255) NOT NULL,
  `access_method` varchar(255) NOT NULL DEFAULT 'ssh',
  `port` smallint(5) unsigned NOT NULL DEFAULT 23,
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
  `onu_status_command` varchar(255) NOT NULL DEFAULT 'show onu all',
  `onu_power_command` varchar(255) NOT NULL DEFAULT 'show epon optical-transceiver diagnosis',
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


DROP TABLE IF EXISTS `olt_onus`;
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


DROP TABLE IF EXISTS `olt_protocol_profiles`;
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
  `save_config_command` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `olt_protocol_profiles_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `olt_protocol_profiles` (`id`, `key`, `label`, `brand`, `pon_interface_command`, `onu_context_command`, `supports_vlan_polling`, `supports_mac_polling`, `default_read_context_commands`, `default_onu_status_command`, `default_onu_power_command`, `default_onu_alarm_command`, `default_onu_vlan_command`, `default_onu_mac_command`, `vlan_write_context_command`, `vlan_write_command`, `save_config_command`, `created_at`, `updated_at`) VALUES (1, 'hsgq_epon', 'HSGQ EPON OLT', 'HSGQ', 'interface epon {pon_port}', 'interface onu {pon_port}/{onu_id}', 1, 1, 'enable\nconfig', 'show onu-info all', 'show optical-info', 'show onu-info-alarm {onu_id}', 'show port-vlan', 'show mac-address epon all', 'interface onu {pon_port}/{onu_id}', 'port-vlan {port} mode tag {vlan} pri {priority}', 'save', '2026-06-01 18:46:16', '2026-06-01 18:46:16');
INSERT INTO `olt_protocol_profiles` (`id`, `key`, `label`, `brand`, `pon_interface_command`, `onu_context_command`, `supports_vlan_polling`, `supports_mac_polling`, `default_read_context_commands`, `default_onu_status_command`, `default_onu_power_command`, `default_onu_alarm_command`, `default_onu_vlan_command`, `default_onu_mac_command`, `vlan_write_context_command`, `vlan_write_command`, `save_config_command`, `created_at`, `updated_at`) VALUES (2, 'hsgq_gpon', 'HSGQ GPON OLT', 'HSGQ', 'interface gpon {pon_port}', 'interface ont {pon_port}/{onu_id}', 1, 1, 'config', 'show ont-info all', 'show ont-optical all', 'show ont-info {onu_id}', 'show service-port all', 'show mac-address all', 'interface gpon {pon_port}', 'ont port native-vlan {onu_id} eth {port} vlan {vlan} {priority}', 'save', '2026-06-01 18:46:16', '2026-06-01 18:46:16');
INSERT INTO `olt_protocol_profiles` (`id`, `key`, `label`, `brand`, `pon_interface_command`, `onu_context_command`, `supports_vlan_polling`, `supports_mac_polling`, `default_read_context_commands`, `default_onu_status_command`, `default_onu_power_command`, `default_onu_alarm_command`, `default_onu_vlan_command`, `default_onu_mac_command`, `vlan_write_context_command`, `vlan_write_command`, `save_config_command`, `created_at`, `updated_at`) VALUES (3, 'generic_epon', 'Generic EPON OLT', NULL, 'interface epon {pon_port}', NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-01 18:46:16', '2026-06-01 18:46:16');

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

INSERT INTO `payment_accounts` (`id`, `entry_by`, `entry_by_type`, `payment_method`, `account_name`, `account_number`, `opening_balance`, `status`, `created_at`, `updated_at`) VALUES (1, '2', 'user', 'bkash', 'Anike Bcash', '01812707070', '0.00', 'active', '2026-05-21 17:43:37', '2026-05-21 17:43:37');
INSERT INTO `payment_accounts` (`id`, `entry_by`, `entry_by_type`, `payment_method`, `account_name`, `account_number`, `opening_balance`, `status`, `created_at`, `updated_at`) VALUES (2, '2', 'user', 'bank', 'Ultimate Solution ( Prime Bank )', '2197113004617', '0.00', 'active', '2026-06-04 19:36:53', '2026-06-04 19:36:53');

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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `payment_allocations` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `payment_id`, `source_type`, `amount`, `allocated_at`, `note`, `created_at`, `updated_at`) VALUES (1, '2', 'user', 1, 1, 1, 'payment', '1000.00', '2026-05-17', NULL, '2026-05-17 22:13:40', '2026-05-17 22:13:40');
INSERT INTO `payment_allocations` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `payment_id`, `source_type`, `amount`, `allocated_at`, `note`, `created_at`, `updated_at`) VALUES (2, '2', 'user', 9, 23, 2, 'payment', '900.00', '2026-05-21', '900 Received By Anike Bcash', '2026-05-21 17:43:37', '2026-05-21 17:43:37');
INSERT INTO `payment_allocations` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `payment_id`, `source_type`, `amount`, `allocated_at`, `note`, `created_at`, `updated_at`) VALUES (3, '2', 'user', 3, 8, 3, 'payment', '190000.00', '2026-05-23', '27/4/26 ibbl 90k\r\n28/4/26 prime 60k\r\n12/5/26 prime 40k', '2026-05-23 00:42:36', '2026-05-23 00:42:36');
INSERT INTO `payment_allocations` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `payment_id`, `source_type`, `amount`, `allocated_at`, `note`, `created_at`, `updated_at`) VALUES (4, '2', 'user', 8, 21, 4, 'payment', '750.00', '2026-06-01', 'Payment received for INV-2026-06-00008.', '2026-06-01 18:47:20', '2026-06-01 18:47:20');
INSERT INTO `payment_allocations` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `payment_id`, `source_type`, `amount`, `allocated_at`, `note`, `created_at`, `updated_at`) VALUES (5, '2', 'user', 8, 21, 5, 'payment', '750.00', '2026-06-01', 'Payment received for INV-2026-06-00008.', '2026-06-01 18:47:44', '2026-06-01 18:47:44');
INSERT INTO `payment_allocations` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `payment_id`, `source_type`, `amount`, `allocated_at`, `note`, `created_at`, `updated_at`) VALUES (6, '2', 'user', 8, 21, 6, 'payment', '750.00', '2026-06-01', 'Payment received for INV-2026-06-00008.', '2026-06-01 18:48:00', '2026-06-01 18:48:00');
INSERT INTO `payment_allocations` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `payment_id`, `source_type`, `amount`, `allocated_at`, `note`, `created_at`, `updated_at`) VALUES (7, '2', 'user', 8, 24, 7, 'payment', '3000.00', '2026-06-01', 'Payment received for INV-2026-05-00008-03.', '2026-06-01 18:54:54', '2026-06-01 18:54:54');
INSERT INTO `payment_allocations` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `payment_id`, `source_type`, `amount`, `allocated_at`, `note`, `created_at`, `updated_at`) VALUES (8, '2', 'user', 8, 22, 8, 'payment', '750.00', '2026-06-01', 'Payment received for INV-2026-06-00008.', '2026-06-01 18:55:32', '2026-06-01 18:55:32');
INSERT INTO `payment_allocations` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `payment_id`, `source_type`, `amount`, `allocated_at`, `note`, `created_at`, `updated_at`) VALUES (9, '2', 'user', 8, 21, 9, 'payment', '250.00', '2026-06-01', 'Payment received for INV-2026-05-00008.', '2026-06-01 18:56:24', '2026-06-01 18:56:24');
INSERT INTO `payment_allocations` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `payment_id`, `source_type`, `amount`, `allocated_at`, `note`, `created_at`, `updated_at`) VALUES (10, '2', 'user', 2, 2, 10, 'payment', '26000.00', '2026-06-01', 'Payment received for INV-2026-05-00002.', '2026-06-01 19:03:23', '2026-06-01 19:03:23');
INSERT INTO `payment_allocations` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `payment_id`, `source_type`, `amount`, `allocated_at`, `note`, `created_at`, `updated_at`) VALUES (11, '2', 'user', 3, 8, 11, 'payment', '50000.00', '2026-06-04', 'Payment received for INV-2026-05-00003.', '2026-06-04 19:36:53', '2026-06-04 19:36:53');
INSERT INTO `payment_allocations` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `payment_id`, `source_type`, `amount`, `allocated_at`, `note`, `created_at`, `updated_at`) VALUES (12, '2', 'user', 7, 19, 12, 'payment', '4200.00', '2026-06-15', '20/05/26 Bcash', '2026-06-15 20:28:20', '2026-06-15 20:28:20');
INSERT INTO `payment_allocations` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `payment_id`, `source_type`, `amount`, `allocated_at`, `note`, `created_at`, `updated_at`) VALUES (13, '2', 'user', 3, 25, 13, 'payment', '240000.00', '2026-06-15', '27/04/26 IBBL - 90k\r\n28/04/26 prime - 60k\r\n12/05/26 prime - 40k\r\n4/6/26 prime - 50k\r\n\r\nউপরের দুই লাখ ৪০ ব্যাংকে চেক করা হয়েছে', '2026-06-15 20:44:26', '2026-06-15 20:44:26');
INSERT INTO `payment_allocations` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `payment_id`, `source_type`, `amount`, `allocated_at`, `note`, `created_at`, `updated_at`) VALUES (14, '2', 'user', 11, 29, 14, 'payment', '140000.00', '2026-06-16', '20/05/26 - প্রাইম ব্যাংক ১ লাখ + কোরবানির আগে ৪০কে মোট ১৪০০০০ টাকা নেওয়া হয়েছে।', '2026-06-16 15:53:27', '2026-06-16 15:53:27');
INSERT INTO `payment_allocations` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `payment_id`, `source_type`, `amount`, `allocated_at`, `note`, `created_at`, `updated_at`) VALUES (15, '2', 'user', 1, 26, 15, 'payment', '1000.00', '2026-06-16', 'Payment received for INV-2026-06-00001.', '2026-06-16 21:50:23', '2026-06-16 21:50:23');

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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `payments` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `amount`, `payment_method`, `payment_account_id`, `payment_date`, `note`, `created_at`, `updated_at`) VALUES (1, '2', 'user', 1, 1, '1000.00', 'cash', NULL, '2026-05-17', NULL, '2026-05-17 22:13:40', '2026-05-17 22:13:40');
INSERT INTO `payments` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `amount`, `payment_method`, `payment_account_id`, `payment_date`, `note`, `created_at`, `updated_at`) VALUES (2, '2', 'user', 9, 23, '900.00', 'bkash', 1, '2026-05-21', '900 Received By Anike Bcash', '2026-05-21 17:43:37', '2026-05-21 17:43:37');
INSERT INTO `payments` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `amount`, `payment_method`, `payment_account_id`, `payment_date`, `note`, `created_at`, `updated_at`) VALUES (3, '2', 'user', 3, 8, '190000.00', 'cash', NULL, '2026-05-23', '27/4/26 ibbl 90k\r\n28/4/26 prime 60k\r\n12/5/26 prime 40k', '2026-05-23 00:42:36', '2026-05-23 00:42:36');
INSERT INTO `payments` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `amount`, `payment_method`, `payment_account_id`, `payment_date`, `note`, `created_at`, `updated_at`) VALUES (4, '2', 'user', 8, 22, '750.00', 'bkash', 1, '2026-06-01', 'Payment received for INV-2026-06-00008.', '2026-06-01 18:47:20', '2026-06-01 18:47:20');
INSERT INTO `payments` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `amount`, `payment_method`, `payment_account_id`, `payment_date`, `note`, `created_at`, `updated_at`) VALUES (5, '2', 'user', 8, 22, '750.00', 'cash', NULL, '2026-06-01', 'Payment received for INV-2026-06-00008.', '2026-06-01 18:47:44', '2026-06-01 18:47:44');
INSERT INTO `payments` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `amount`, `payment_method`, `payment_account_id`, `payment_date`, `note`, `created_at`, `updated_at`) VALUES (6, '2', 'user', 8, 22, '750.00', 'cash', NULL, '2026-06-01', 'Payment received for INV-2026-06-00008.', '2026-06-01 18:48:00', '2026-06-01 18:48:00');
INSERT INTO `payments` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `amount`, `payment_method`, `payment_account_id`, `payment_date`, `note`, `created_at`, `updated_at`) VALUES (7, '2', 'user', 8, 24, '3000.00', 'cash', NULL, '2026-06-01', 'Payment received for INV-2026-05-00008-03.', '2026-06-01 18:54:54', '2026-06-01 18:54:54');
INSERT INTO `payments` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `amount`, `payment_method`, `payment_account_id`, `payment_date`, `note`, `created_at`, `updated_at`) VALUES (8, '2', 'user', 8, 22, '750.00', 'bkash', 1, '2026-06-01', 'Payment received for INV-2026-06-00008.', '2026-06-01 18:55:32', '2026-06-01 18:55:32');
INSERT INTO `payments` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `amount`, `payment_method`, `payment_account_id`, `payment_date`, `note`, `created_at`, `updated_at`) VALUES (9, '2', 'user', 8, 21, '250.00', 'bkash', 1, '2026-06-01', 'Payment received for INV-2026-05-00008.', '2026-06-01 18:56:24', '2026-06-01 18:56:24');
INSERT INTO `payments` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `amount`, `payment_method`, `payment_account_id`, `payment_date`, `note`, `created_at`, `updated_at`) VALUES (10, '2', 'user', 2, 2, '26000.00', 'cash', NULL, '2026-06-01', 'Payment received for INV-2026-05-00002.', '2026-06-01 19:03:23', '2026-06-01 19:03:23');
INSERT INTO `payments` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `amount`, `payment_method`, `payment_account_id`, `payment_date`, `note`, `created_at`, `updated_at`) VALUES (11, '2', 'user', 3, 8, '50000.00', 'bank', 2, '2026-06-04', 'Payment received for INV-2026-05-00003.', '2026-06-04 19:36:53', '2026-06-04 19:36:53');
INSERT INTO `payments` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `amount`, `payment_method`, `payment_account_id`, `payment_date`, `note`, `created_at`, `updated_at`) VALUES (12, '2', 'user', 7, 19, '4200.00', 'cash', NULL, '2026-06-15', '20/05/26 Bcash', '2026-06-15 20:28:20', '2026-06-15 20:28:20');
INSERT INTO `payments` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `amount`, `payment_method`, `payment_account_id`, `payment_date`, `note`, `created_at`, `updated_at`) VALUES (13, '2', 'user', 3, 25, '240000.00', 'bank', 2, '2026-06-15', '27/04/26 IBBL - 90k\r\n28/04/26 prime - 60k\r\n12/05/26 prime - 40k\r\n4/6/26 prime - 50k\r\n\r\nউপরের দুই লাখ ৪০ ব্যাংকে চেক করা হয়েছে', '2026-06-15 20:44:26', '2026-06-15 20:44:26');
INSERT INTO `payments` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `amount`, `payment_method`, `payment_account_id`, `payment_date`, `note`, `created_at`, `updated_at`) VALUES (14, '2', 'user', 11, 29, '140000.00', 'bank', 2, '2026-06-16', '20/05/26 - প্রাইম ব্যাংক ১ লাখ + কোরবানির আগে ৪০কে মোট ১৪০০০০ টাকা নেওয়া হয়েছে।', '2026-06-16 15:53:27', '2026-06-16 15:53:27');
INSERT INTO `payments` (`id`, `entry_by`, `entry_by_type`, `customer_id`, `invoice_id`, `amount`, `payment_method`, `payment_account_id`, `payment_date`, `note`, `created_at`, `updated_at`) VALUES (15, '2', 'user', 1, 26, '1000.00', 'cash', NULL, '2026-06-16', 'Payment received for INV-2026-06-00001.', '2026-06-16 21:50:23', '2026-06-16 21:50:23');

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
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (14, 1);
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (15, 1);
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (16, 1);
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES (17, 1);

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
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (1, 3);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (2, 1);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (2, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (2, 3);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (3, 1);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (3, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (3, 3);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (4, 1);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (4, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (4, 3);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (5, 1);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (5, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (5, 3);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (6, 1);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (6, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (6, 3);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (7, 1);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (7, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (7, 3);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (8, 1);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (8, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (8, 3);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (9, 1);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (9, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (9, 3);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (10, 1);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (10, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (10, 3);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (11, 1);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (11, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (11, 3);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (12, 1);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (12, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (12, 3);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (13, 1);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (13, 2);
INSERT INTO `permission_user` (`permission_id`, `user_id`) VALUES (13, 3);

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
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
INSERT INTO `permissions` (`id`, `entry_by`, `entry_by_type`, `name`, `label`, `created_at`, `updated_at`) VALUES (14, NULL, NULL, 'view_warranty_claims', 'View warranty claims', '2026-06-04 20:15:07', '2026-06-04 20:15:07');
INSERT INTO `permissions` (`id`, `entry_by`, `entry_by_type`, `name`, `label`, `created_at`, `updated_at`) VALUES (15, NULL, NULL, 'manage_warranty_claims', 'Manage warranty claims', '2026-06-04 20:15:07', '2026-06-04 20:15:07');
INSERT INTO `permissions` (`id`, `entry_by`, `entry_by_type`, `name`, `label`, `created_at`, `updated_at`) VALUES (16, NULL, NULL, 'close_warranty_claims', 'Close warranty claims', '2026-06-04 20:15:07', '2026-06-04 20:15:07');
INSERT INTO `permissions` (`id`, `entry_by`, `entry_by_type`, `name`, `label`, `created_at`, `updated_at`) VALUES (17, NULL, NULL, 'manage_service_products', 'Manage service products', '2026-06-04 20:15:07', '2026-06-04 20:15:07');

DROP TABLE IF EXISTS `product_categories`;
CREATE TABLE `product_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_categories_parent_id_name_unique` (`parent_id`,`name`),
  CONSTRAINT `product_categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `product_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `product_categories` (`id`, `parent_id`, `name`, `created_at`, `updated_at`) VALUES (1, NULL, 'Printer', '2026-06-04 20:15:14', '2026-06-04 20:15:14');
INSERT INTO `product_categories` (`id`, `parent_id`, `name`, `created_at`, `updated_at`) VALUES (2, NULL, 'Leaser', '2026-06-04 20:15:29', '2026-06-04 20:15:29');
INSERT INTO `product_categories` (`id`, `parent_id`, `name`, `created_at`, `updated_at`) VALUES (3, 1, 'Leaser', '2026-06-04 20:15:43', '2026-06-04 20:15:43');
INSERT INTO `product_categories` (`id`, `parent_id`, `name`, `created_at`, `updated_at`) VALUES (4, 3, 'With Scanner', '2026-06-04 20:16:02', '2026-06-04 20:16:02');

DROP TABLE IF EXISTS `product_serials`;
CREATE TABLE `product_serials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
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
  CONSTRAINT `product_serials_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_serials_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_serials_invoice_item_id_foreign` FOREIGN KEY (`invoice_item_id`) REFERENCES `invoice_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_serials_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_serials_purchase_bill_id_foreign` FOREIGN KEY (`purchase_bill_id`) REFERENCES `purchase_bills` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_serials_purchase_bill_item_id_foreign` FOREIGN KEY (`purchase_bill_item_id`) REFERENCES `purchase_bill_items` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `products`;
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


DROP TABLE IF EXISTS `purchase_bill_items`;
CREATE TABLE `purchase_bill_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_bill_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `quantity` int(10) unsigned NOT NULL,
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


DROP TABLE IF EXISTS `purchase_bills`;
CREATE TABLE `purchase_bills` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `party_id` bigint(20) unsigned DEFAULT NULL,
  `bill_no` varchar(255) NOT NULL,
  `purchase_date` date NOT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_bills_bill_no_unique` (`bill_no`),
  KEY `purchase_bills_party_id_foreign` (`party_id`),
  CONSTRAINT `purchase_bills_party_id_foreign` FOREIGN KEY (`party_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `entry_by`, `entry_by_type`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES (1, 'system', 'system', 'Admin User', 'admin@example.com', NULL, '$2y$12$jB3bBLDvEqLsfC4POYQscO.ASuXyLCXIHmr8QLBOnYMaPwocraisW', NULL, '2026-05-17 14:55:11', '2026-05-17 15:00:11');
INSERT INTO `users` (`id`, `entry_by`, `entry_by_type`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES (2, '1', 'user', 'Anike', 'anike10@gmail.com', NULL, '$2y$12$Wdapg7VKzKST3ctSFHa6KeQGjXmM9zBz4h.wWQFoxncQyRNA9V3Ri', 'Nhz4dYuPRevsa42h51X6WSWDCc9x65zoVM4Vu3hJXhIFi0AgY69c7RIJMUbn', '2026-05-17 15:00:37', '2026-05-17 15:00:37');
INSERT INTO `users` (`id`, `entry_by`, `entry_by_type`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES (3, '2', 'user', 'Shofiq', 'shofiqulkst@gmail.com', NULL, '$2y$12$jE.3YXSI60LPBC.Jjde1fONzpqf.lXRhnLNNbfcVCF1hjGiL75y4i', 'xfV8WLNPSaYpN5ea2KiX7mN9sxAqAr9PJJewZITzn7zcdb4oFHCVfjH8m4e3', '2026-05-18 01:38:29', '2026-05-24 17:55:10');

DROP TABLE IF EXISTS `warranty_claim_logs`;
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


DROP TABLE IF EXISTS `warranty_claims`;
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


SET FOREIGN_KEY_CHECKS=1;
