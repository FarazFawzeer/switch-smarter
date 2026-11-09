-- =====================================================================
-- Switch Smarter — Revision 8 (live database)
-- Same as migration 2026_11_09_000001_revision_8_changes. Run once.
-- Take a backup first.
-- =====================================================================

SET NAMES utf8mb4;

-- 1) Team: work category (PPM / Breakdown & Repair / Both)
ALTER TABLE `users`
    ADD COLUMN `work_category` varchar(20) NOT NULL DEFAULT 'both' AFTER `type`;

-- 2) Stores: item master
CREATE TABLE `inventory_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `item_code` varchar(50) NOT NULL,
  `part_name` varchar(191) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `part_number` varchar(100) DEFAULT NULL,
  `unit` varchar(30) NOT NULL DEFAULT 'Nos',
  `unit_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `stock_qty` decimal(12,2) NOT NULL DEFAULT 0.00,
  `min_stock` decimal(12,2) NOT NULL DEFAULT 0.00,
  `store_location` varchar(150) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inventory_items_item_code_unique` (`item_code`),
  KEY `inventory_items_created_by_foreign` (`created_by`),
  KEY `inventory_items_part_number_index` (`part_number`),
  CONSTRAINT `inventory_items_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) Stores: material requests (MRN)
CREATE TABLE `material_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `mrn_no` varchar(30) DEFAULT NULL,
  `inventory_item_id` bigint(20) unsigned NOT NULL,
  `contract_id` bigint(20) unsigned DEFAULT NULL,
  `elevator_unit_id` bigint(20) unsigned DEFAULT NULL,
  `quantity_requested` decimal(12,2) NOT NULL,
  `quantity_issued` decimal(12,2) DEFAULT NULL,
  `unit_cost` decimal(12,2) DEFAULT NULL,
  `total_cost` decimal(14,2) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `requested_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approval_remarks` varchar(500) DEFAULT NULL,
  `issued_by` bigint(20) unsigned DEFAULT NULL,
  `issued_at` timestamp NULL DEFAULT NULL,
  `issue_remarks` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `material_requests_mrn_no_unique` (`mrn_no`),
  KEY `material_requests_inventory_item_id_foreign` (`inventory_item_id`),
  KEY `material_requests_contract_id_foreign` (`contract_id`),
  KEY `material_requests_elevator_unit_id_foreign` (`elevator_unit_id`),
  KEY `material_requests_requested_by_foreign` (`requested_by`),
  KEY `material_requests_approved_by_foreign` (`approved_by`),
  KEY `material_requests_issued_by_foreign` (`issued_by`),
  KEY `material_requests_status_created_at_index` (`status`,`created_at`),
  CONSTRAINT `material_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `material_requests_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `material_requests_elevator_unit_id_foreign` FOREIGN KEY (`elevator_unit_id`) REFERENCES `elevator_units` (`id`) ON DELETE SET NULL,
  CONSTRAINT `material_requests_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`),
  CONSTRAINT `material_requests_issued_by_foreign` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `material_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) Stores: every stock movement (receive + / issue − / adjust ±)
CREATE TABLE `stock_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inventory_item_id` bigint(20) unsigned NOT NULL,
  `type` varchar(20) NOT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `unit_cost` decimal(12,2) DEFAULT NULL,
  `balance_after` decimal(12,2) NOT NULL,
  `material_request_id` bigint(20) unsigned DEFAULT NULL,
  `contract_id` bigint(20) unsigned DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `note` varchar(500) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_transactions_material_request_id_foreign` (`material_request_id`),
  KEY `stock_transactions_contract_id_foreign` (`contract_id`),
  KEY `stock_transactions_user_id_foreign` (`user_id`),
  KEY `stock_transactions_inventory_item_id_created_at_index` (`inventory_item_id`,`created_at`),
  CONSTRAINT `stock_transactions_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_transactions_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_transactions_material_request_id_foreign` FOREIGN KEY (`material_request_id`) REFERENCES `material_requests` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5) New shift timings: Morning 8am–5pm, Evening 4pm–1am, Night 11pm–8am
UPDATE `shifts` SET `start_time` = '08:00:00', `end_time` = '17:00:00', `updated_at` = NOW() WHERE `name` = 'Morning';
UPDATE `shifts` SET `start_time` = '16:00:00', `end_time` = '01:00:00', `updated_at` = NOW() WHERE `name` = 'Evening';
UPDATE `shifts` SET `start_time` = '23:00:00', `end_time` = '08:00:00', `updated_at` = NOW() WHERE `name` = 'Night';
-- add any of the three that does not exist yet
INSERT INTO `shifts` (`name`, `start_time`, `end_time`, `created_at`, `updated_at`)
SELECT s.name, s.start_time, s.end_time, NOW(), NOW() FROM (
    SELECT 'Morning' AS name, '08:00:00' AS start_time, '17:00:00' AS end_time
    UNION ALL SELECT 'Evening', '16:00:00', '01:00:00'
    UNION ALL SELECT 'Night', '23:00:00', '08:00:00'
) s WHERE NOT EXISTS (SELECT 1 FROM `shifts` x WHERE x.name = s.name);

-- 6) Mark the migration as run (so `php artisan migrate` will not repeat it)
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_09_000001_revision_8_changes', COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`;

-- ---------------------------------------------------------------------
-- Optional: create the Storekeeper login from Admin → Add (type "Storekeeper"),
-- or change an existing user:
--   UPDATE `users` SET `type` = 'Storekeeper' WHERE `email` = 'store@yourcompany.com';
-- Optional: set the Breakdown & Repair team:
--   UPDATE `users` SET `work_category` = 'breakdown' WHERE `employee_id` IN ('EMP-…', 'EMP-…');
-- ---------------------------------------------------------------------
