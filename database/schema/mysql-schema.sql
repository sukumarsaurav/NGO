-- =====================================================================================
--  Vision Good Work Global Foundation — NGO Management Platform
--  MySQL 8.0 schema
--
--  Source of truth: docs/02-DATABASE-SCHEMA.md (+ M01–M12 module specs)
--  Target:          MySQL 8.0.16 or later (CHECK constraints are enforced from 8.0.16)
--  Engine:          InnoDB, utf8mb4 / utf8mb4_unicode_ci throughout
--
--  CONVENTIONS
--    * Money is BIGINT UNSIGNED in PAISE. Never DECIMAL, never FLOAT.
--      150050 = ₹1,500.50.
--    * Primary keys are BIGINT UNSIGNED AUTO_INCREMENT (Laravel convention).
--    * Public-facing identifiers are `uuid` CHAR(36). Never expose `id` in a URL.
--    * Financial rows use ON DELETE RESTRICT. Nothing in the money chain cascades.
--    * Soft deletes (`deleted_at`) only where the module spec calls for them.
--    * All timestamps are stored UTC; the application renders Asia/Kolkata.
--
--  CREATION ORDER IS SIGNIFICANT — foreign keys dictate it. Run top to bottom.
--
--  Six notes where this file deliberately differs from, or hardens, the prose spec
--  are collected at the bottom under "IMPLEMENTATION NOTES". Read them.
-- =====================================================================================

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET FOREIGN_KEY_CHECKS = 0;
SET SESSION sql_require_primary_key = 1;

CREATE DATABASE IF NOT EXISTS `vgwgf`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;
USE `vgwgf`;


-- =====================================================================================
-- 1. FOUNDATION & AUTH  (M01, M02)
-- =====================================================================================

CREATE TABLE `users` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`              CHAR(36)        NOT NULL,
  `name`              VARCHAR(150)    NOT NULL,
  `email`             VARCHAR(190)    NOT NULL,
  `phone`             VARCHAR(20)         NULL,
  `email_verified_at` TIMESTAMP           NULL,
  `phone_verified_at` TIMESTAMP           NULL,
  -- NULL for guest-created donor records; they claim the account via password reset.
  `password`          VARCHAR(255)        NULL,
  `avatar_path`       VARCHAR(255)        NULL,
  `bio`               TEXT                NULL COMMENT 'Blog byline; feeds Article.author JSON-LD',
  `is_active`         TINYINT(1)      NOT NULL DEFAULT 1,
  `last_login_at`     TIMESTAMP           NULL,
  `remember_token`    VARCHAR(100)        NULL,
  `created_at`        TIMESTAMP           NULL,
  `updated_at`        TIMESTAMP           NULL,
  `deleted_at`        TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_uuid`  (`uuid`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `ix_users_phone`        (`phone`),
  KEY `ix_users_active`       (`is_active`, `deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
  `email`      VARCHAR(190) NOT NULL,
  `token`      VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP        NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- DB-backed singleton config. Read through SettingsRepository, cached forever,
-- cache busted on write. See M02.
CREATE TABLE `settings` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key`          VARCHAR(100)    NOT NULL,
  `value`        TEXT                NULL,
  `type`         ENUM('string','int','bool','json','file') NOT NULL DEFAULT 'string',
  `group`        VARCHAR(50)     NOT NULL,
  `is_encrypted` TINYINT(1)      NOT NULL DEFAULT 0,
  `created_at`   TIMESTAMP           NULL,
  `updated_at`   TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_settings_key` (`key`),
  KEY `ix_settings_group`      (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Groups: organisation, donation, receipt, social, homepage, seo';

-- Idempotency guard. EVERY gateway webhook writes here first.
-- The unique key below is what makes webhook replays safe. See M05.
CREATE TABLE `webhook_events` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider`     VARCHAR(30)     NOT NULL DEFAULT 'razorpay',
  `event_id`     VARCHAR(190)    NOT NULL COMMENT 'The gateway''s own event id',
  `event_type`   VARCHAR(80)     NOT NULL,
  `payload`      JSON            NOT NULL,
  `status`       ENUM('received','processed','failed','ignored') NOT NULL DEFAULT 'received',
  `error`        TEXT                NULL,
  `processed_at` TIMESTAMP           NULL,
  `created_at`   TIMESTAMP           NULL,
  `updated_at`   TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_webhook_provider_event` (`provider`, `event_id`),
  KEY `ix_webhook_status`                (`status`, `created_at`),
  KEY `ix_webhook_type`                  (`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================================
-- 2. MEMBERS  (M03)
-- =====================================================================================

CREATE TABLE `departments` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`            VARCHAR(120)    NOT NULL,
  `slug`            VARCHAR(120)    NOT NULL,
  `parent_id`       BIGINT UNSIGNED     NULL COMMENT 'Nested departments; validate against cycles on save',
  `manager_user_id` BIGINT UNSIGNED     NULL COMMENT 'Drives /manager panel scoping',
  `is_active`       TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`      TIMESTAMP           NULL,
  `updated_at`      TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_departments_slug` (`slug`),
  KEY `ix_departments_parent`      (`parent_id`),
  KEY `ix_departments_manager`     (`manager_user_id`),
  CONSTRAINT `fk_departments_parent`  FOREIGN KEY (`parent_id`)
    REFERENCES `departments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_departments_manager` FOREIGN KEY (`manager_user_id`)
    REFERENCES `users` (`id`)        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `designations` (
  `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`              VARCHAR(120)    NOT NULL,
  `slug`               VARCHAR(120)    NOT NULL,
  `rank`               SMALLINT        NOT NULL DEFAULT 0,
  -- FK added after document_templates exists — see section 3.
  `letter_template_id` BIGINT UNSIGNED     NULL,
  `is_active`          TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`         TIMESTAMP           NULL,
  `updated_at`         TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_designations_slug` (`slug`),
  KEY `ix_designations_rank`        (`rank`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `members` (
  `id`                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`                    CHAR(36)        NOT NULL,
  `user_id`                 BIGINT UNSIGNED NOT NULL,
  `member_code`             VARCHAR(30)     NOT NULL COMMENT 'VGWGF-2026-00123, row-locked generator',
  `department_id`           BIGINT UNSIGNED     NULL,
  `designation_id`          BIGINT UNSIGNED     NULL,
  `photo_path`              VARCHAR(255)        NULL,
  `date_of_birth`           DATE                NULL,
  `gender`                  ENUM('male','female','other') NULL,
  `blood_group`             VARCHAR(5)          NULL,
  `address_line1`           VARCHAR(190)        NULL,
  `address_line2`           VARCHAR(190)        NULL,
  `city`                    VARCHAR(80)         NULL,
  `state`                   VARCHAR(80)         NULL,
  `pincode`                 VARCHAR(10)         NULL,
  `emergency_contact_name`  VARCHAR(120)        NULL,
  `emergency_contact_phone` VARCHAR(20)         NULL,
  `id_proof_type`           VARCHAR(40)         NULL,
  -- ENCRYPTED AT REST (Laravel `encrypted` cast). See IMPLEMENTATION NOTE 1 for the width.
  `id_proof_number`         VARCHAR(512)        NULL,
  `joined_on`               DATE            NOT NULL,
  `valid_until`             DATE                NULL COMMENT 'ID card expiry',
  `status`                  ENUM('pending','active','suspended','resigned','expired')
                              NOT NULL DEFAULT 'pending',
  `notes`                   TEXT                NULL,
  `created_at`              TIMESTAMP           NULL,
  `updated_at`              TIMESTAMP           NULL,
  `deleted_at`              TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_members_uuid`    (`uuid`),
  UNIQUE KEY `uq_members_user`    (`user_id`),
  UNIQUE KEY `uq_members_code`    (`member_code`),
  KEY `ix_members_status_dept`    (`status`, `department_id`),
  KEY `ix_members_department`     (`department_id`),
  KEY `ix_members_designation`    (`designation_id`),
  KEY `ix_members_valid_until`    (`valid_until`) COMMENT 'Expiring-ID-card dashboard widget',
  CONSTRAINT `fk_members_user`        FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)         ON DELETE RESTRICT,
  CONSTRAINT `fk_members_department`  FOREIGN KEY (`department_id`)
    REFERENCES `departments` (`id`)   ON DELETE RESTRICT,
  CONSTRAINT `fk_members_designation` FOREIGN KEY (`designation_id`)
    REFERENCES `designations` (`id`)  ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================================
-- 3. DOCUMENT ENGINE  (M04)
-- =====================================================================================

CREATE TABLE `document_templates` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`            VARCHAR(120)    NOT NULL,
  `type`            ENUM('id_card','appointment_letter','certificate') NOT NULL,
  `body_html`       LONGTEXT        NOT NULL COMMENT 'Blade-compatible; rendered with a RESTRICTED variable set',
  `css`             LONGTEXT            NULL,
  `page_size`       VARCHAR(20)     NOT NULL DEFAULT 'A4' COMMENT 'CR80 = 85.6x54mm for ID cards',
  `orientation`     ENUM('portrait','landscape') NOT NULL DEFAULT 'portrait',
  `background_path` VARCHAR(255)        NULL,
  `qr_enabled`      TINYINT(1)      NOT NULL DEFAULT 1,
  `qr_position`     JSON                NULL COMMENT '{x, y, size} in mm',
  `is_default`      TINYINT(1)      NOT NULL DEFAULT 0,
  `is_active`       TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`      TIMESTAMP           NULL,
  `updated_at`      TIMESTAMP           NULL,
  -- Enforces "exactly one default template per type" in the schema rather than in a
  -- callback. NULLs repeat freely in a MySQL unique index; non-defaults are all NULL.
  -- See IMPLEMENTATION NOTE 2.
  `default_key`     VARCHAR(40) GENERATED ALWAYS AS
                      (IF(`is_default` = 1, CAST(`type` AS CHAR), NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_document_templates_default` (`default_key`),
  KEY `ix_document_templates_type`           (`type`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `designations`
  ADD CONSTRAINT `fk_designations_letter_template` FOREIGN KEY (`letter_template_id`)
    REFERENCES `document_templates` (`id`) ON DELETE SET NULL;

-- Immutable once issued. Reissuing creates a NEW row and supersedes the old one.
CREATE TABLE `issued_documents` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`              CHAR(36)        NOT NULL COMMENT 'THIS is what the QR code encodes',
  `document_number`   VARCHAR(40)     NOT NULL COMMENT 'IDC-2026-00042 / APL- / CRT-',
  `type`              ENUM('id_card','appointment_letter','certificate') NOT NULL,
  `member_id`         BIGINT UNSIGNED NOT NULL,
  `template_id`       BIGINT UNSIGNED NOT NULL,
  `title`             VARCHAR(190)    NOT NULL,
  -- Member data FROZEN at issue time. The PDF renders only from this, never from
  -- live relations, or a 2026 letter would silently rewrite itself in 2027.
  `snapshot_data`     JSON            NOT NULL,
  `file_path`         VARCHAR(255)        NULL COMMENT 'NULL until the queued job completes',
  `qr_payload`        VARCHAR(255)    NOT NULL,
  `issued_by_user_id` BIGINT UNSIGNED NOT NULL,
  `issued_on`         DATE            NOT NULL,
  `valid_until`       DATE                NULL,
  `status`            ENUM('queued','issued','revoked','superseded') NOT NULL DEFAULT 'queued',
  `revoked_at`        TIMESTAMP           NULL,
  `revoked_reason`    VARCHAR(255)        NULL,
  `superseded_by_id`  BIGINT UNSIGNED     NULL,
  `download_count`    INT UNSIGNED    NOT NULL DEFAULT 0,
  `verified_count`    INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT 'QR scans',
  `created_at`        TIMESTAMP           NULL,
  `updated_at`        TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_issued_documents_uuid`   (`uuid`),
  UNIQUE KEY `uq_issued_documents_number` (`document_number`),
  KEY `ix_issued_documents_member`        (`member_id`, `type`, `status`),
  KEY `ix_issued_documents_type_status`   (`type`, `status`),
  KEY `ix_issued_documents_template`      (`template_id`),
  KEY `ix_issued_documents_issuer`        (`issued_by_user_id`),
  KEY `ix_issued_documents_superseded`    (`superseded_by_id`),
  CONSTRAINT `fk_issued_documents_member`     FOREIGN KEY (`member_id`)
    REFERENCES `members` (`id`)              ON DELETE RESTRICT,
  CONSTRAINT `fk_issued_documents_template`   FOREIGN KEY (`template_id`)
    REFERENCES `document_templates` (`id`)   ON DELETE RESTRICT,
  CONSTRAINT `fk_issued_documents_issuer`     FOREIGN KEY (`issued_by_user_id`)
    REFERENCES `users` (`id`)                ON DELETE RESTRICT,
  CONSTRAINT `fk_issued_documents_superseded` FOREIGN KEY (`superseded_by_id`)
    REFERENCES `issued_documents` (`id`)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================================
-- 4. CAMPAIGNS & CROWDFUNDING  (M08)
-- =====================================================================================

CREATE TABLE `campaign_categories` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`             VARCHAR(100)    NOT NULL,
  `slug`             VARCHAR(100)    NOT NULL,
  `icon_path`        VARCHAR(255)        NULL,
  `description`      TEXT                NULL COMMENT 'Short blurb for the homepage tile',
  `intro_body`       TEXT                NULL COMMENT '150+ words of unique copy on /causes/{slug} — what makes the page rank',
  `meta_title`       VARCHAR(190)        NULL,
  `meta_description` VARCHAR(255)        NULL,
  `sort_order`       SMALLINT        NOT NULL DEFAULT 0,
  `is_active`        TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`       TIMESTAMP           NULL,
  `updated_at`       TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_campaign_categories_slug` (`slug`),
  KEY `ix_campaign_categories_active`      (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `campaigns` (
  `id`                     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`                   CHAR(36)        NOT NULL,
  `slug`                   VARCHAR(190)    NOT NULL COMMENT 'FROZEN once published; changes go through `redirects`',
  `category_id`            BIGINT UNSIGNED NOT NULL,
  `title`                  VARCHAR(190)    NOT NULL,
  `subtitle`               VARCHAR(255)        NULL,
  `beneficiary_name`       VARCHAR(150)        NULL COMMENT 'The "by ..." line on cards',
  `story`                  LONGTEXT        NOT NULL,
  `cover_image_path`       VARCHAR(255)        NULL,
  `video_url`              VARCHAR(255)        NULL,
  `goal_amount`            BIGINT UNSIGNED NOT NULL,
  `raised_amount`          BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'DENORMALISED — reconciled nightly',
  `donor_count`            INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT 'DENORMALISED',
  `offline_raised_amount`  BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Cheques etc.; added to display total',
  `allows_recurring`       TINYINT(1)      NOT NULL DEFAULT 1,
  `is_tax_benefit`         TINYINT(1)      NOT NULL DEFAULT 1,
  `is_featured`            TINYINT(1)      NOT NULL DEFAULT 0,
  `is_urgent`              TINYINT(1)      NOT NULL DEFAULT 0,
  `status`                 ENUM('draft','pending_review','active','paused','completed','closed')
                             NOT NULL DEFAULT 'draft',
  `starts_at`              TIMESTAMP           NULL,
  `ends_at`                TIMESTAMP           NULL,
  `sort_order`             SMALLINT        NOT NULL DEFAULT 0,
  `meta_title`             VARCHAR(190)        NULL,
  `meta_description`       VARCHAR(255)        NULL,
  `created_by_user_id`     BIGINT UNSIGNED NOT NULL,
  `created_at`             TIMESTAMP           NULL,
  `updated_at`             TIMESTAMP           NULL,
  `deleted_at`             TIMESTAMP           NULL COMMENT 'Soft delete only — donations must stay traceable',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_campaigns_uuid` (`uuid`),
  UNIQUE KEY `uq_campaigns_slug` (`slug`),
  KEY `ix_campaigns_listing`     (`status`, `is_featured`, `sort_order`),
  KEY `ix_campaigns_category`    (`category_id`, `status`),
  KEY `ix_campaigns_ends_at`     (`status`, `ends_at`) COMMENT 'Ending-soon sort + auto-complete job',
  KEY `ix_campaigns_creator`     (`created_by_user_id`),
  CONSTRAINT `fk_campaigns_category` FOREIGN KEY (`category_id`)
    REFERENCES `campaign_categories` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_campaigns_creator`  FOREIGN KEY (`created_by_user_id`)
    REFERENCES `users` (`id`)              ON DELETE RESTRICT,
  CONSTRAINT `ck_campaigns_goal_positive` CHECK (`goal_amount` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The needs catalogue — the "Products" tab. RESTRICT, not CASCADE: a cascade from
-- campaigns would try to delete rows that donation_items restricts, and the whole
-- delete would fail with a confusing error instead of a clear one.
CREATE TABLE `campaign_products` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `campaign_id`  BIGINT UNSIGNED NOT NULL,
  `name`         VARCHAR(120)    NOT NULL COMMENT 'Medicine kit, Rice 10KG',
  `description`  VARCHAR(255)        NULL,
  `image_path`   VARCHAR(255)        NULL,
  `unit_price`   BIGINT UNSIGNED NOT NULL COMMENT 'Paise. NEVER read from the client.',
  `units_needed` INT UNSIGNED    NOT NULL,
  `units_funded` INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT 'DENORMALISED — reconciled nightly',
  `sort_order`   SMALLINT        NOT NULL DEFAULT 0,
  `is_active`    TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`   TIMESTAMP           NULL,
  `updated_at`   TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  KEY `ix_campaign_products_listing` (`campaign_id`, `is_active`, `sort_order`),
  CONSTRAINT `fk_campaign_products_campaign` FOREIGN KEY (`campaign_id`)
    REFERENCES `campaigns` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `ck_campaign_products_price`  CHECK (`unit_price` > 0),
  CONSTRAINT `ck_campaign_products_needed` CHECK (`units_needed` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Per-campaign impact counters ("5,000+ Dogs Rescued"). Distinct from the site-wide
-- `impact_stats` in section 9 — these belong to one campaign and one beneficiary.
CREATE TABLE `campaign_stats` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `campaign_id` BIGINT UNSIGNED NOT NULL,
  `label`       VARCHAR(120)    NOT NULL,
  `value`       VARCHAR(20)     NOT NULL,
  `suffix`      VARCHAR(5)          NULL COMMENT '+ , K+',
  `sort_order`  SMALLINT        NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP           NULL,
  `updated_at`  TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  KEY `ix_campaign_stats_campaign` (`campaign_id`, `sort_order`),
  CONSTRAINT `fk_campaign_stats_campaign` FOREIGN KEY (`campaign_id`)
    REFERENCES `campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- NULL campaign_id = a global FAQ shown on every campaign page.
CREATE TABLE `campaign_faqs` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `campaign_id`  BIGINT UNSIGNED     NULL COMMENT 'NULL = global, shown on all campaigns',
  `question`     VARCHAR(255)    NOT NULL,
  `answer`       TEXT            NOT NULL,
  `sort_order`   SMALLINT        NOT NULL DEFAULT 0,
  `is_published` TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`   TIMESTAMP           NULL,
  `updated_at`   TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  KEY `ix_campaign_faqs_campaign` (`campaign_id`, `is_published`, `sort_order`),
  CONSTRAINT `fk_campaign_faqs_campaign` FOREIGN KEY (`campaign_id`)
    REFERENCES `campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `campaign_updates` (
  `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`               CHAR(36)        NOT NULL COMMENT 'Notify-donor emails link to a specific update',
  `campaign_id`        BIGINT UNSIGNED NOT NULL,
  `title`              VARCHAR(190)    NOT NULL,
  `body`               TEXT            NOT NULL,
  `image_path`         VARCHAR(255)        NULL,
  `published_at`       TIMESTAMP           NULL,
  `notify_donors`      TINYINT(1)      NOT NULL DEFAULT 0,
  `created_by_user_id` BIGINT UNSIGNED NOT NULL,
  `created_at`         TIMESTAMP           NULL,
  `updated_at`         TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_campaign_updates_uuid` (`uuid`),
  KEY `ix_campaign_updates_campaign`    (`campaign_id`, `published_at`),
  KEY `ix_campaign_updates_creator`     (`created_by_user_id`),
  CONSTRAINT `fk_campaign_updates_campaign` FOREIGN KEY (`campaign_id`)
    REFERENCES `campaigns` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_campaign_updates_creator`  FOREIGN KEY (`created_by_user_id`)
    REFERENCES `users` (`id`)     ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fundraiser_requests` (
  `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`                CHAR(36)        NOT NULL,
  `name`                VARCHAR(150)    NOT NULL,
  `email`               VARCHAR(190)    NOT NULL,
  `phone`               VARCHAR(20)     NOT NULL,
  `organisation_name`   VARCHAR(190)        NULL,
  `cause_category_id`   BIGINT UNSIGNED     NULL,
  `title`               VARCHAR(190)    NOT NULL,
  `description`         TEXT            NOT NULL,
  `goal_amount`         BIGINT UNSIGNED NOT NULL,
  `documents`           JSON                NULL COMMENT 'Uploaded proof paths',
  `status`              ENUM('new','under_review','approved','rejected') NOT NULL DEFAULT 'new',
  `reviewed_by_user_id` BIGINT UNSIGNED     NULL,
  `reviewed_at`         TIMESTAMP           NULL,
  `review_notes`        TEXT                NULL,
  `campaign_id`         BIGINT UNSIGNED     NULL COMMENT 'Set on approval',
  `ip_address`          VARCHAR(45)         NULL COMMENT 'Spam triage',
  `created_at`          TIMESTAMP           NULL,
  `updated_at`          TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fundraiser_requests_uuid` (`uuid`),
  KEY `ix_fundraiser_requests_status`      (`status`, `created_at`),
  KEY `ix_fundraiser_requests_category`    (`cause_category_id`),
  KEY `ix_fundraiser_requests_reviewer`    (`reviewed_by_user_id`),
  KEY `ix_fundraiser_requests_campaign`    (`campaign_id`),
  CONSTRAINT `fk_fundraiser_requests_category` FOREIGN KEY (`cause_category_id`)
    REFERENCES `campaign_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_fundraiser_requests_reviewer` FOREIGN KEY (`reviewed_by_user_id`)
    REFERENCES `users` (`id`)               ON DELETE SET NULL,
  CONSTRAINT `fk_fundraiser_requests_campaign` FOREIGN KEY (`campaign_id`)
    REFERENCES `campaigns` (`id`)           ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================================
-- 5. DONORS, SUBSCRIPTIONS, DONATIONS  (M05, M06)
--    Order: donors -> subscriptions -> donations -> donation_items
--                                                -> subscription_charges
--                                                -> payment_transactions
-- =====================================================================================

CREATE TABLE `donors` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`              CHAR(36)        NOT NULL,
  `user_id`           BIGINT UNSIGNED     NULL COMMENT 'NULL for guest donors',
  `name`              VARCHAR(150)    NOT NULL,
  `email`             VARCHAR(190)    NOT NULL COMMENT 'Dedupe key — case-insensitive, trimmed',
  `phone`             VARCHAR(20)         NULL COMMENT 'NEVER dedupe on this alone; shared family numbers',
  -- ENCRYPTED AT REST. Never logged, masked in the UI. See IMPLEMENTATION NOTE 1.
  `pan`               VARCHAR(512)        NULL,
  `address_line1`     VARCHAR(190)        NULL COMMENT 'Required on an 80G receipt',
  `address_line2`     VARCHAR(190)        NULL,
  `city`              VARCHAR(80)         NULL,
  `state`             VARCHAR(80)         NULL,
  `pincode`           VARCHAR(10)         NULL,
  `country`           VARCHAR(60)     NOT NULL DEFAULT 'India',
  `donor_type`        ENUM('individual','company','trust','huf','foreign')
                        NOT NULL DEFAULT 'individual' COMMENT 'Form 10BD needs this',
  `total_donated`     BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'DENORMALISED',
  `donation_count`    INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT 'DENORMALISED',
  `first_donated_at`  TIMESTAMP           NULL,
  `last_donated_at`   TIMESTAMP           NULL,
  `is_anonymous`      TINYINT(1)      NOT NULL DEFAULT 0,
  `marketing_opt_in`  TINYINT(1)      NOT NULL DEFAULT 0,
  `created_at`        TIMESTAMP           NULL,
  `updated_at`        TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_donors_uuid`  (`uuid`),
  UNIQUE KEY `uq_donors_user`  (`user_id`),
  KEY `ix_donors_email`        (`email`),
  KEY `ix_donors_phone`        (`phone`),
  KEY `ix_donors_last_donated` (`last_donated_at`),
  CONSTRAINT `fk_donors_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mirrors Razorpay's own state machine EXACTLY. Do not invent a simplified status set.
CREATE TABLE `subscriptions` (
  `id`                       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`                     CHAR(36)        NOT NULL,
  `donor_id`                 BIGINT UNSIGNED NOT NULL,
  `campaign_id`              BIGINT UNSIGNED     NULL,
  `provider`                 VARCHAR(30)     NOT NULL DEFAULT 'razorpay',
  `provider_plan_id`         VARCHAR(120)        NULL,
  `provider_subscription_id` VARCHAR(120)        NULL,
  `provider_token_id`        VARCHAR(120)        NULL COMMENT 'Mandate token',
  `amount`                   BIGINT UNSIGNED NOT NULL COMMENT 'Per cycle, paise',
  `interval`                 ENUM('monthly','quarterly','yearly') NOT NULL DEFAULT 'monthly',
  `total_cycles`             INT                 NULL COMMENT 'NULL = until cancelled',
  `completed_cycles`         INT             NOT NULL DEFAULT 0,
  `status`                   ENUM('created','pending_authentication','active','paused',
                                  'halted','completed','cancelled','expired')
                               NOT NULL DEFAULT 'created',
  `mandate_type`             ENUM('upi_autopay','emandate','card') NULL,
  `started_at`               TIMESTAMP           NULL,
  `next_charge_at`           TIMESTAMP           NULL,
  `last_charged_at`          TIMESTAMP           NULL,
  `ended_at`                 TIMESTAMP           NULL,
  `cancelled_at`             TIMESTAMP           NULL,
  `cancelled_by`             ENUM('donor','admin','gateway','bank') NULL,
  `cancellation_reason`      VARCHAR(255)        NULL,
  `failed_charge_count`      SMALLINT        NOT NULL DEFAULT 0 COMMENT '3 consecutive -> halt',
  `total_collected`          BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `raw_response`             JSON                NULL,
  `created_at`               TIMESTAMP           NULL,
  `updated_at`               TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_subscriptions_uuid`     (`uuid`),
  UNIQUE KEY `uq_subscriptions_provider` (`provider_subscription_id`),
  KEY `ix_subscriptions_donor`           (`donor_id`, `status`),
  KEY `ix_subscriptions_campaign`        (`campaign_id`),
  KEY `ix_subscriptions_status`          (`status`),
  KEY `ix_subscriptions_next_charge`     (`status`, `next_charge_at`) COMMENT 'Daily reconciliation job',
  CONSTRAINT `fk_subscriptions_donor`    FOREIGN KEY (`donor_id`)
    REFERENCES `donors` (`id`)           ON DELETE RESTRICT,
  CONSTRAINT `fk_subscriptions_campaign` FOREIGN KEY (`campaign_id`)
    REFERENCES `campaigns` (`id`)        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The business record of a gift. NEVER deleted — corrections are status changes.
CREATE TABLE `donations` (
  `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`                CHAR(36)        NOT NULL,
  `donation_number`     VARCHAR(40)         NULL COMMENT 'Assigned on success',
  `donor_id`            BIGINT UNSIGNED NOT NULL,
  `campaign_id`         BIGINT UNSIGNED     NULL COMMENT 'NULL = general fund',
  `subscription_id`     BIGINT UNSIGNED     NULL,
  `amount`              BIGINT UNSIGNED NOT NULL COMMENT 'AUTHORITATIVE total = items_amount + free_amount',
  `items_amount`        BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Sum of donation_items.line_total',
  `free_amount`         BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'The open "any amount" portion',
  `currency`            CHAR(3)         NOT NULL DEFAULT 'INR',
  `type`                ENUM('one_time','recurring') NOT NULL DEFAULT 'one_time',
  `payment_mode`        ENUM('upi','card','netbanking','wallet','cash','cheque',
                             'bank_transfer','other') NOT NULL DEFAULT 'other',
  `status`              ENUM('pending','processing','succeeded','failed','refunded',
                             'cancelled','abandoned') NOT NULL DEFAULT 'pending',
  `is_offline`          TINYINT(1)      NOT NULL DEFAULT 0,
  `donated_at`          TIMESTAMP           NULL COMMENT 'When money actually moved',
  `financial_year`      CHAR(7)         NOT NULL COMMENT '2026-27 — denormalised; Apr 1 - Mar 31',
  `eligible_for_80g`    TINYINT(1)      NOT NULL DEFAULT 1 COMMENT 'FALSE for cash > Rs 2,000',
  `message`             TEXT                NULL,
  `dedicated_to`        VARCHAR(150)        NULL,
  `source`              VARCHAR(50)         NULL,
  `utm_data`            JSON                NULL COMMENT 'Captured at checkout; cannot be reconstructed later',
  `ip_address`          VARCHAR(45)         NULL,
  `recorded_by_user_id` BIGINT UNSIGNED     NULL COMMENT 'Offline entries',
  `notes`               TEXT                NULL,
  `created_at`          TIMESTAMP           NULL,
  `updated_at`          TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_donations_uuid`   (`uuid`),
  UNIQUE KEY `uq_donations_number` (`donation_number`),
  KEY `ix_donations_status_date`   (`status`, `donated_at`),
  KEY `ix_donations_campaign`      (`campaign_id`, `status`),
  KEY `ix_donations_fy`            (`financial_year`, `status`),
  KEY `ix_donations_donor`         (`donor_id`, `donated_at`),
  KEY `ix_donations_subscription`  (`subscription_id`),
  KEY `ix_donations_recorder`      (`recorded_by_user_id`),
  KEY `ix_donations_80g`           (`financial_year`, `eligible_for_80g`, `status`)
    COMMENT 'Form 10BD export + missing-PAN report',
  KEY `ix_donations_pending_sweep` (`status`, `created_at`)
    COMMENT 'AbandonStalePendingDonations job',
  CONSTRAINT `fk_donations_donor`        FOREIGN KEY (`donor_id`)
    REFERENCES `donors` (`id`)           ON DELETE RESTRICT,
  CONSTRAINT `fk_donations_campaign`     FOREIGN KEY (`campaign_id`)
    REFERENCES `campaigns` (`id`)        ON DELETE RESTRICT,
  CONSTRAINT `fk_donations_subscription` FOREIGN KEY (`subscription_id`)
    REFERENCES `subscriptions` (`id`)    ON DELETE RESTRICT,
  CONSTRAINT `fk_donations_recorder`     FOREIGN KEY (`recorded_by_user_id`)
    REFERENCES `users` (`id`)            ON DELETE RESTRICT,
  -- The totals must agree. See IMPLEMENTATION NOTE 3 before enabling in production.
  CONSTRAINT `ck_donations_amount_split` CHECK (`amount` = `items_amount` + `free_amount`),
  CONSTRAINT `ck_donations_amount_pos`   CHECK (`amount` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Catalogue line items. `unit_price` is SNAPSHOTTED, not joined: prices change, and a
-- receipt reprinted next year must show what the donor actually paid.
CREATE TABLE `donation_items` (
  `id`                  BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `donation_id`         BIGINT UNSIGNED  NOT NULL,
  `campaign_product_id` BIGINT UNSIGNED  NOT NULL,
  `quantity`            SMALLINT UNSIGNED NOT NULL,
  `unit_price`          BIGINT UNSIGNED  NOT NULL COMMENT 'Price AT TIME OF DONATION',
  -- Generated, so it can never disagree with its own operands.
  `line_total`          BIGINT UNSIGNED  GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,
  `created_at`          TIMESTAMP            NULL,
  `updated_at`          TIMESTAMP            NULL,
  PRIMARY KEY (`id`),
  KEY `ix_donation_items_donation` (`donation_id`),
  KEY `ix_donation_items_product`  (`campaign_product_id`) COMMENT 'units_funded reconciler',
  CONSTRAINT `fk_donation_items_donation` FOREIGN KEY (`donation_id`)
    REFERENCES `donations` (`id`)         ON DELETE RESTRICT,
  CONSTRAINT `fk_donation_items_product`  FOREIGN KEY (`campaign_product_id`)
    REFERENCES `campaign_products` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `ck_donation_items_qty` CHECK (`quantity` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `subscription_charges` (
  `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `subscription_id`     BIGINT UNSIGNED NOT NULL,
  `donation_id`         BIGINT UNSIGNED     NULL COMMENT 'Created on success',
  `cycle_number`        INT             NOT NULL,
  `amount`              BIGINT UNSIGNED NOT NULL,
  `status`              ENUM('scheduled','processing','succeeded','failed','skipped')
                          NOT NULL DEFAULT 'scheduled',
  `provider_payment_id` VARCHAR(120)        NULL,
  `scheduled_for`       TIMESTAMP       NOT NULL,
  `charged_at`          TIMESTAMP           NULL,
  `failure_reason`      VARCHAR(255)        NULL,
  `retry_count`         SMALLINT        NOT NULL DEFAULT 0,
  `created_at`          TIMESTAMP           NULL,
  `updated_at`          TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  -- This is what stops a duplicate subscription.charged webhook double-recording.
  UNIQUE KEY `uq_subscription_charges_cycle` (`subscription_id`, `cycle_number`),
  KEY `ix_subscription_charges_status`       (`status`, `scheduled_for`),
  KEY `ix_subscription_charges_donation`     (`donation_id`),
  CONSTRAINT `fk_subscription_charges_subscription` FOREIGN KEY (`subscription_id`)
    REFERENCES `subscriptions` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_subscription_charges_donation`     FOREIGN KEY (`donation_id`)
    REFERENCES `donations` (`id`)     ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The gateway record. A donation can have several attempts; only one succeeds.
CREATE TABLE `payment_transactions` (
  `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `donation_id`         BIGINT UNSIGNED NOT NULL,
  `provider`            VARCHAR(30)     NOT NULL DEFAULT 'razorpay',
  `provider_order_id`   VARCHAR(120)        NULL,
  `provider_payment_id` VARCHAR(120)        NULL,
  `provider_signature`  VARCHAR(255)        NULL,
  `amount`              BIGINT UNSIGNED NOT NULL,
  `fee`                 BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Gateway fee, paise',
  `tax`                 BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'GST on the fee',
  `net_amount`          BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'What actually lands in the bank',
  `status`              ENUM('created','authorized','captured','failed','refunded')
                          NOT NULL DEFAULT 'created',
  `method`              VARCHAR(30)         NULL,
  `bank`                VARCHAR(60)         NULL,
  `vpa`                 VARCHAR(120)        NULL COMMENT 'UPI id',
  `card_last4`          CHAR(4)             NULL COMMENT 'NEVER store full card data',
  `error_code`          VARCHAR(60)         NULL,
  `error_description`   TEXT                NULL,
  `raw_response`        JSON                NULL,
  `captured_at`         TIMESTAMP           NULL,
  `refunded_at`         TIMESTAMP           NULL,
  `refund_amount`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`          TIMESTAMP           NULL,
  `updated_at`          TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payment_transactions_payment` (`provider_payment_id`),
  KEY `ix_payment_transactions_donation`       (`donation_id`),
  KEY `ix_payment_transactions_order`          (`provider_order_id`),
  KEY `ix_payment_transactions_status`         (`status`, `captured_at`)
    COMMENT 'Payment reconciliation report',
  CONSTRAINT `fk_payment_transactions_donation` FOREIGN KEY (`donation_id`)
    REFERENCES `donations` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `ck_payment_transactions_refund` CHECK (`refund_amount` <= `amount`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================================
-- 6. RECEIPTS & 80G COMPLIANCE  (M07)
--    The most compliance-sensitive tables in the system.
-- =====================================================================================

-- THE CONCURRENCY-CRITICAL TABLE. One row per (series, financial year).
-- Allocation: ensure the row exists OUTSIDE the transaction, then
-- SELECT ... FOR UPDATE inside it. A lock on a row that does not exist locks nothing,
-- which is how the 1 April race gets in.
CREATE TABLE `receipt_sequences` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `series`         ENUM('donation','80g') NOT NULL,
  `financial_year` CHAR(7)         NOT NULL COMMENT '2026-27',
  `prefix`         VARCHAR(20)     NOT NULL COMMENT 'VGWGF/80G/2026-27/',
  `last_number`    INT UNSIGNED    NOT NULL DEFAULT 0,
  `locked_at`      TIMESTAMP           NULL,
  `created_at`     TIMESTAMP           NULL,
  `updated_at`     TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_receipt_sequences_series_fy` (`series`, `financial_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `receipts` (
  `id`               BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `uuid`             CHAR(36)         NOT NULL COMMENT 'QR verification target',
  `receipt_number`   VARCHAR(50)      NOT NULL COMMENT 'VGWGF/80G/2026-27/00042',
  `sequence_number`  INT UNSIGNED     NOT NULL COMMENT 'The raw integer, 42 — what gap detection queries',
  `series`           ENUM('donation','80g') NOT NULL,
  `revision`         SMALLINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Incremented on reissue after cancellation',
  `donation_id`      BIGINT UNSIGNED  NOT NULL,
  `donor_id`         BIGINT UNSIGNED  NOT NULL,
  `financial_year`   CHAR(7)          NOT NULL,
  `amount`           BIGINT UNSIGNED  NOT NULL,
  `amount_in_words`  VARCHAR(255)     NOT NULL COMMENT 'Frozen at generation; Indian lakh/crore format',
  `snapshot_data`    JSON             NOT NULL COMMENT 'Donor name/address/PAN + org 80G number, all frozen',
  `file_path`        VARCHAR(255)         NULL,
  `issued_on`        DATE             NOT NULL,
  `emailed_at`       TIMESTAMP            NULL,
  `email_status`     ENUM('pending','sent','failed','bounced') NOT NULL DEFAULT 'pending',
  `download_count`   INT UNSIGNED     NOT NULL DEFAULT 0,
  `is_cancelled`     TINYINT(1)       NOT NULL DEFAULT 0 COMMENT 'Cancelled receipts KEEP their number',
  `cancelled_reason` VARCHAR(255)         NULL,
  `created_at`       TIMESTAMP            NULL,
  `updated_at`       TIMESTAMP            NULL,
  -- Emulates a partial unique index: enforces "at most ONE non-cancelled receipt per
  -- (donation, series)" while still allowing any number of cancelled ones, because
  -- MySQL permits repeated NULLs in a unique index. See IMPLEMENTATION NOTE 4 —
  -- this is stronger than what the prose spec assumed was possible.
  `live_receipt_key` VARCHAR(64) GENERATED ALWAYS AS
                       (IF(`is_cancelled` = 0,
                           CONCAT(`donation_id`, ':', CAST(`series` AS CHAR)),
                           NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_receipts_uuid`     (`uuid`),
  UNIQUE KEY `uq_receipts_number`   (`receipt_number`),
  UNIQUE KEY `uq_receipts_revision` (`donation_id`, `series`, `revision`),
  UNIQUE KEY `uq_receipts_live`     (`live_receipt_key`),
  KEY `ix_receipts_gap_detection`   (`series`, `financial_year`, `sequence_number`),
  KEY `ix_receipts_donor`           (`donor_id`, `financial_year`),
  KEY `ix_receipts_email_status`    (`email_status`) COMMENT '"Attention needed" widget',
  CONSTRAINT `fk_receipts_donation` FOREIGN KEY (`donation_id`)
    REFERENCES `donations` (`id`)   ON DELETE RESTRICT,
  CONSTRAINT `fk_receipts_donor`    FOREIGN KEY (`donor_id`)
    REFERENCES `donors` (`id`)      ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================================
-- 7. NOTICES & COMMUNICATION  (M09)
-- =====================================================================================

CREATE TABLE `notices` (
  `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`               CHAR(36)        NOT NULL,
  `title`              VARCHAR(190)    NOT NULL,
  `body`               LONGTEXT        NOT NULL,
  `attachment_path`    VARCHAR(255)        NULL,
  `audience`           ENUM('all_members','department','designation','specific','all_donors') NOT NULL,
  `audience_filter`    JSON                NULL COMMENT '{department_ids:[...]} / {user_ids:[...]}',
  `priority`           ENUM('normal','important','urgent') NOT NULL DEFAULT 'normal',
  `send_email`         TINYINT(1)      NOT NULL DEFAULT 1,
  `published_at`       TIMESTAMP           NULL,
  `expires_at`         TIMESTAMP           NULL,
  `status`             ENUM('draft','scheduled','sending','sent','failed') NOT NULL DEFAULT 'draft',
  `recipient_count`    INT UNSIGNED    NOT NULL DEFAULT 0,
  `read_count`         INT UNSIGNED    NOT NULL DEFAULT 0,
  `created_by_user_id` BIGINT UNSIGNED NOT NULL,
  `created_at`         TIMESTAMP           NULL,
  `updated_at`         TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_notices_uuid`  (`uuid`),
  KEY `ix_notices_published`    (`status`, `published_at`),
  KEY `ix_notices_audience`     (`audience`),
  KEY `ix_notices_creator`      (`created_by_user_id`)
    COMMENT 'Manager panel scopes notices by creator — see M11',
  CONSTRAINT `fk_notices_creator` FOREIGN KEY (`created_by_user_id`)
    REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Recipients are MATERIALISED at publish time, not resolved dynamically, so a member
-- who joins next week does not retroactively receive last week's notice.
CREATE TABLE `notice_recipients` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `notice_id`    BIGINT UNSIGNED NOT NULL,
  `user_id`      BIGINT UNSIGNED NOT NULL,
  `read_at`      TIMESTAMP           NULL,
  `email_status` ENUM('pending','sent','failed','bounced') NOT NULL DEFAULT 'pending',
  `emailed_at`   TIMESTAMP           NULL,
  `created_at`   TIMESTAMP           NULL,
  `updated_at`   TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_notice_recipients` (`notice_id`, `user_id`),
  KEY `ix_notice_recipients_inbox`  (`user_id`, `read_at`) COMMENT 'Portal notice inbox',
  CONSTRAINT `fk_notice_recipients_notice` FOREIGN KEY (`notice_id`)
    REFERENCES `notices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notice_recipients_user`   FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `email_templates` (
  `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key`                 VARCHAR(80)     NOT NULL COMMENT 'donation.thank_you, receipt.80g, ...',
  `name`                VARCHAR(120)    NOT NULL,
  `subject`             VARCHAR(190)    NOT NULL COMMENT 'Supports {{ variables }} — WHITELIST substitution, never Blade compilation of DB content',
  `body_html`           LONGTEXT        NOT NULL,
  `available_variables` JSON            NOT NULL,
  `is_active`           TINYINT(1)      NOT NULL DEFAULT 1,
  `send_copy_to_admin`  TINYINT(1)      NOT NULL DEFAULT 0,
  `created_at`          TIMESTAMP           NULL,
  `updated_at`          TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email_templates_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `subscribers` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`           VARCHAR(190)    NOT NULL,
  `name`            VARCHAR(150)        NULL,
  `token`           CHAR(40)        NOT NULL COMMENT 'One-click unsubscribe',
  `confirmed_at`    TIMESTAMP           NULL COMMENT 'Double opt-in',
  `unsubscribed_at` TIMESTAMP           NULL,
  `source`          VARCHAR(50)         NULL,
  `created_at`      TIMESTAMP           NULL,
  `updated_at`      TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_subscribers_email` (`email`),
  UNIQUE KEY `uq_subscribers_token` (`token`),
  KEY `ix_subscribers_active`       (`confirmed_at`, `unsubscribed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================================
-- 8. PUBLIC SITE, CMS & SEO  (M10, 07-SEO)
-- =====================================================================================

CREATE TABLE `pages` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug`             VARCHAR(190)    NOT NULL COMMENT 'Reserved slugs matching real routes are blocked at validation',
  `title`            VARCHAR(190)    NOT NULL,
  `body`             LONGTEXT        NOT NULL,
  `template`         VARCHAR(50)     NOT NULL DEFAULT 'default',
  `meta_title`       VARCHAR(190)        NULL,
  `meta_description` VARCHAR(255)        NULL,
  `is_published`     TINYINT(1)      NOT NULL DEFAULT 1,
  `sort_order`       SMALLINT        NOT NULL DEFAULT 0,
  `created_at`       TIMESTAMP           NULL,
  `updated_at`       TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pages_slug` (`slug`),
  KEY `ix_pages_published`   (`is_published`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `posts` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug`             VARCHAR(190)    NOT NULL,
  `title`            VARCHAR(190)    NOT NULL,
  `excerpt`          VARCHAR(500)        NULL,
  `body`             LONGTEXT        NOT NULL,
  `cover_image_path` VARCHAR(255)        NULL,
  -- Cast to the PostCategory enum in the app. Free text produces
  -- "Education"/"education"/"Educaton" and a filter that silently drops posts.
  `category`         VARCHAR(80)         NULL,
  `tags`             JSON                NULL,
  `author_user_id`   BIGINT UNSIGNED NOT NULL,
  `published_at`     TIMESTAMP           NULL,
  `view_count`       INT UNSIGNED    NOT NULL DEFAULT 0,
  `meta_title`       VARCHAR(190)        NULL,
  `meta_description` VARCHAR(255)        NULL,
  `is_published`     TINYINT(1)      NOT NULL DEFAULT 0,
  `created_at`       TIMESTAMP           NULL,
  `updated_at`       TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_posts_slug`  (`slug`),
  KEY `ix_posts_published`    (`is_published`, `published_at`),
  KEY `ix_posts_category`     (`category`, `is_published`),
  KEY `ix_posts_author`       (`author_user_id`),
  CONSTRAINT `fk_posts_author` FOREIGN KEY (`author_user_id`)
    REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `testimonials` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`         VARCHAR(150)    NOT NULL,
  `location`     VARCHAR(120)        NULL,
  `avatar_path`  VARCHAR(255)        NULL,
  `quote`        TEXT            NOT NULL,
  `rating`       TINYINT UNSIGNED    NULL,
  `is_published` TINYINT(1)      NOT NULL DEFAULT 1,
  `sort_order`   SMALLINT        NOT NULL DEFAULT 0,
  `created_at`   TIMESTAMP           NULL,
  `updated_at`   TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  KEY `ix_testimonials_published` (`is_published`, `sort_order`),
  CONSTRAINT `ck_testimonials_rating` CHECK (`rating` IS NULL OR `rating` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `press_mentions` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `outlet_name`  VARCHAR(150)    NOT NULL,
  `logo_path`    VARCHAR(255)        NULL,
  `url`          VARCHAR(255)        NULL,
  `published_on` DATE                NULL,
  `is_published` TINYINT(1)      NOT NULL DEFAULT 1,
  `sort_order`   SMALLINT        NOT NULL DEFAULT 0,
  `created_at`   TIMESTAMP           NULL,
  `updated_at`   TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  KEY `ix_press_mentions_published` (`is_published`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Site-wide homepage counters. Manually maintained — auto-computing "lives impacted"
-- from the database would be dishonest.
CREATE TABLE `impact_stats` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `label`      VARCHAR(120)    NOT NULL,
  `value`      VARCHAR(20)     NOT NULL,
  `suffix`     VARCHAR(5)          NULL COMMENT '+ , K+',
  `icon`       VARCHAR(80)         NULL,
  `sort_order` SMALLINT        NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP           NULL,
  `updated_at` TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  KEY `ix_impact_stats_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The homepage hero. `mobile_image_path` is not a nicety: the hero is the page's LCP
-- element and a 1920x720 desktop image letterboxes to an unreadable strip at 360px.
CREATE TABLE `banners` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`             VARCHAR(190)    NOT NULL,
  `subtitle`          VARCHAR(255)        NULL,
  `image_path`        VARCHAR(255)    NOT NULL COMMENT 'Desktop, ~1920x720',
  `mobile_image_path` VARCHAR(255)        NULL COMMENT '~750x900; falls back to image_path',
  `cta_label`         VARCHAR(60)         NULL,
  `cta_url`           VARCHAR(255)        NULL,
  `campaign_id`       BIGINT UNSIGNED     NULL COMMENT 'Shortcut instead of cta_url',
  `sort_order`        SMALLINT        NOT NULL DEFAULT 0,
  `is_published`      TINYINT(1)      NOT NULL DEFAULT 1,
  `starts_at`         TIMESTAMP           NULL,
  `ends_at`           TIMESTAMP           NULL,
  `created_at`        TIMESTAMP           NULL,
  `updated_at`        TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  KEY `ix_banners_active`   (`is_published`, `sort_order`),
  KEY `ix_banners_schedule` (`is_published`, `starts_at`, `ends_at`),
  KEY `ix_banners_campaign` (`campaign_id`),
  CONSTRAINT `fk_banners_campaign` FOREIGN KEY (`campaign_id`)
    REFERENCES `campaigns` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Makes the immutable-slug rule survivable. Resolved in middleware AFTER the router
-- fails and before the 404 renders, so it costs one query only on genuine misses.
CREATE TABLE `redirects` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `from_path`   VARCHAR(255)    NOT NULL,
  `to_path`     VARCHAR(255)    NOT NULL,
  `status_code` SMALLINT        NOT NULL DEFAULT 301,
  `hits`        INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT 'Which old URLs still get traffic',
  `last_hit_at` TIMESTAMP           NULL,
  `created_at`  TIMESTAMP           NULL,
  `updated_at`  TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_redirects_from` (`from_path`),
  CONSTRAINT `ck_redirects_no_self_loop` CHECK (`from_path` <> `to_path`),
  CONSTRAINT `ck_redirects_status`       CHECK (`status_code` IN (301, 302, 308))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `contact_messages` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(150)    NOT NULL,
  `email`      VARCHAR(190)    NOT NULL,
  `phone`      VARCHAR(20)         NULL,
  `subject`    VARCHAR(190)        NULL,
  `message`    TEXT            NOT NULL,
  `ip_address` VARCHAR(45)         NULL COMMENT 'Spam triage',
  `user_agent` VARCHAR(255)        NULL,
  `is_read`    TINYINT(1)      NOT NULL DEFAULT 0,
  `read_at`    TIMESTAMP           NULL,
  `created_at` TIMESTAMP           NULL,
  `updated_at` TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  KEY `ix_contact_messages_unread` (`is_read`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================================
-- 9. CROSS-CUTTING — LARAVEL & SPATIE
--    These are normally published by the framework and package migrations. They are
--    reproduced here so this file is a complete picture of the database. If you run
--    package migrations, do NOT also run this section.
-- =====================================================================================

CREATE TABLE `sessions` (
  `id`            VARCHAR(255)    NOT NULL,
  `user_id`       BIGINT UNSIGNED     NULL,
  `ip_address`    VARCHAR(45)         NULL,
  `user_agent`    TEXT                NULL,
  `payload`       LONGTEXT        NOT NULL,
  `last_activity` INT             NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_sessions_user`          (`user_id`),
  KEY `ix_sessions_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache` (
  `key`        VARCHAR(255) NOT NULL,
  `value`      MEDIUMTEXT   NOT NULL,
  `expiration` INT          NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
  `key`        VARCHAR(255) NOT NULL,
  `owner`      VARCHAR(255) NOT NULL,
  `expiration` INT          NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jobs` (
  `id`           BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `queue`        VARCHAR(255)     NOT NULL,
  `payload`      LONGTEXT         NOT NULL,
  `attempts`     TINYINT UNSIGNED NOT NULL,
  `reserved_at`  INT UNSIGNED         NULL,
  `available_at` INT UNSIGNED     NOT NULL,
  `created_at`   INT UNSIGNED     NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_jobs_queue` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_batches` (
  `id`             VARCHAR(255) NOT NULL,
  `name`           VARCHAR(255) NOT NULL,
  `total_jobs`     INT          NOT NULL,
  `pending_jobs`   INT          NOT NULL,
  `failed_jobs`    INT          NOT NULL,
  `failed_job_ids` LONGTEXT     NOT NULL,
  `options`        MEDIUMTEXT       NULL,
  `cancelled_at`   INT              NULL,
  `created_at`     INT          NOT NULL,
  `finished_at`    INT              NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- MONITOR THIS TABLE. A silently failing PDF job means a donor never gets their
-- 80G receipt. It feeds the "attention needed" dashboard widget.
CREATE TABLE `failed_jobs` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`       VARCHAR(255)    NOT NULL,
  `connection` TEXT            NOT NULL,
  `queue`      TEXT            NOT NULL,
  `payload`    LONGTEXT        NOT NULL,
  `exception`  LONGTEXT        NOT NULL,
  `failed_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_failed_jobs_uuid` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- spatie/laravel-permission
CREATE TABLE `permissions` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(125)    NOT NULL,
  `guard_name` VARCHAR(125)    NOT NULL,
  `created_at` TIMESTAMP           NULL,
  `updated_at` TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_permissions_name_guard` (`name`, `guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `roles` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(125)    NOT NULL,
  `guard_name` VARCHAR(125)    NOT NULL,
  `created_at` TIMESTAMP           NULL,
  `updated_at` TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_name_guard` (`name`, `guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='super-admin, admin, manager, member, donor';

CREATE TABLE `model_has_permissions` (
  `permission_id` BIGINT UNSIGNED NOT NULL,
  `model_type`    VARCHAR(190)    NOT NULL,
  `model_id`      BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`permission_id`, `model_id`, `model_type`),
  KEY `ix_mhp_model` (`model_id`, `model_type`),
  CONSTRAINT `fk_mhp_permission` FOREIGN KEY (`permission_id`)
    REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `model_has_roles` (
  `role_id`    BIGINT UNSIGNED NOT NULL,
  `model_type` VARCHAR(190)    NOT NULL,
  `model_id`   BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`, `model_id`, `model_type`),
  KEY `ix_mhr_model` (`model_id`, `model_type`),
  CONSTRAINT `fk_mhr_role` FOREIGN KEY (`role_id`)
    REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `role_has_permissions` (
  `permission_id` BIGINT UNSIGNED NOT NULL,
  `role_id`       BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`permission_id`, `role_id`),
  CONSTRAINT `fk_rhp_permission` FOREIGN KEY (`permission_id`)
    REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rhp_role`       FOREIGN KEY (`role_id`)
    REFERENCES `roles` (`id`)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- spatie/laravel-activitylog — audits donation status changes, receipt generation,
-- document issue/revoke, member status changes, settings edits, role changes.
CREATE TABLE `activity_log` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `log_name`     VARCHAR(255)        NULL,
  `description`  TEXT            NOT NULL,
  `subject_type` VARCHAR(255)        NULL,
  `subject_id`   BIGINT UNSIGNED     NULL,
  `event`        VARCHAR(255)        NULL,
  `causer_type`  VARCHAR(255)        NULL,
  `causer_id`    BIGINT UNSIGNED     NULL,
  `properties`   JSON                NULL,
  `batch_uuid`   CHAR(36)            NULL,
  `created_at`   TIMESTAMP           NULL,
  `updated_at`   TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  KEY `ix_activity_log_subject` (`subject_type`, `subject_id`),
  KEY `ix_activity_log_causer`  (`causer_type`, `causer_id`),
  KEY `ix_activity_log_name`    (`log_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- spatie/laravel-medialibrary
CREATE TABLE `media` (
  `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `model_type`           VARCHAR(255)    NOT NULL,
  `model_id`             BIGINT UNSIGNED NOT NULL,
  `uuid`                 CHAR(36)            NULL,
  `collection_name`      VARCHAR(255)    NOT NULL,
  `name`                 VARCHAR(255)    NOT NULL,
  `file_name`            VARCHAR(255)    NOT NULL,
  `mime_type`            VARCHAR(255)        NULL,
  `disk`                 VARCHAR(255)    NOT NULL,
  `conversions_disk`     VARCHAR(255)        NULL,
  `size`                 BIGINT UNSIGNED NOT NULL,
  `manipulations`        JSON            NOT NULL,
  `custom_properties`    JSON            NOT NULL,
  `generated_conversions` JSON           NOT NULL,
  `responsive_images`    JSON            NOT NULL,
  `order_column`         INT UNSIGNED        NULL,
  `created_at`           TIMESTAMP           NULL,
  `updated_at`           TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_media_uuid` (`uuid`),
  KEY `ix_media_model`       (`model_type`, `model_id`),
  KEY `ix_media_order`       (`order_column`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================================
-- 10. REPORTING VIEWS
--     Read-only helpers so the reporting layer cannot express these two queries
--     differently in two places (M12: "never write the aggregation logic twice").
-- =====================================================================================

-- Receipt gap detection. Should ALWAYS return zero rows. This is an alarm, not an
-- analysis. Uses sequence_number, never a parsed receipt_number string.
CREATE OR REPLACE VIEW `v_receipt_gaps` AS
SELECT
    `series`,
    `financial_year`,
    `prev_number` + 1 AS `gap_starts_at`,
    `sequence_number` - 1 AS `gap_ends_at`,
    `sequence_number` - `prev_number` - 1 AS `missing_count`
FROM (
    SELECT
        `series`,
        `financial_year`,
        `sequence_number`,
        LAG(`sequence_number`) OVER (
            PARTITION BY `series`, `financial_year`
            ORDER BY `sequence_number`
        ) AS `prev_number`
    FROM `receipts`
) AS `ordered`
WHERE `prev_number` IS NOT NULL
  AND `sequence_number` <> `prev_number` + 1;

-- Campaign totals recomputed from source. The nightly reconciler compares this
-- against the denormalised columns; the admin drift report reads the difference.
CREATE OR REPLACE VIEW `v_campaign_totals_actual` AS
SELECT
    c.`id` AS `campaign_id`,
    COALESCE(SUM(d.`amount`), 0)          AS `computed_raised_amount`,
    COUNT(DISTINCT d.`donor_id`)          AS `computed_donor_count`,
    c.`raised_amount`                     AS `stored_raised_amount`,
    c.`donor_count`                       AS `stored_donor_count`,
    COALESCE(SUM(d.`amount`), 0) - c.`raised_amount` AS `amount_drift`
FROM `campaigns` c
LEFT JOIN `donations` d
       ON d.`campaign_id` = c.`id`
      AND d.`status` = 'succeeded'
GROUP BY c.`id`, c.`raised_amount`, c.`donor_count`;


SET FOREIGN_KEY_CHECKS = 1;


-- =====================================================================================
--  IMPLEMENTATION NOTES
--  Six places where this file hardens or corrects the prose spec. Read before migrating.
-- =====================================================================================
--
--  1. ENCRYPTED COLUMN WIDTHS — corrects the spec.
--     docs/02-DATABASE-SCHEMA.md gives `donors.pan` as VARCHAR(20) and
--     `members.id_proof_number` as VARCHAR(60), which are the widths of the PLAINTEXT
--     values. Laravel's `encrypted` cast emits base64 of a JSON envelope
--     ({iv, value, mac, tag}); a 10-character PAN comes out around 250 characters and
--     will be SILENTLY TRUNCATED into an undecryptable value at VARCHAR(20).
--     Both are VARCHAR(512) here. Truncated ciphertext is unrecoverable — there is no
--     migration back from it, which is why this cannot wait for a later fix.
--     Neither column is indexed: encrypted values are not searchable by equality, and
--     an index on them would only be misleading.
--
--  2. ONE DEFAULT TEMPLATE PER TYPE — `document_templates.default_key`.
--     A stored generated column holding the type when `is_default = 1` and NULL
--     otherwise, with a UNIQUE index. MySQL allows repeated NULLs in a unique index, so
--     non-default rows never collide while a second default of the same type is
--     rejected by the database rather than by a model callback that a bulk import or a
--     tinker session can bypass.
--
--  3. `ck_donations_amount_split` — a deliberate constraint, worth a decision.
--     It asserts amount = items_amount + free_amount. Any code path that writes a
--     donation without setting the split (recurring charges, offline entries, seeders)
--     will FAIL LOUDLY rather than record a donation whose components disagree with its
--     total. That is the intent. If it blocks early development, drop the constraint
--     rather than weakening the columns, and reinstate it before launch.
--
--  4. `receipts.live_receipt_key` — enforces in the schema what the spec assumed could
--     only be enforced in application code.
--     M07 states the "at most one non-cancelled receipt per (donation, series)" rule
--     has to be a guarded write because MySQL 8 has no partial indexes. It can be
--     expressed: a generated column that is CONCAT(donation_id, ':', series) while the
--     receipt is live and NULL once cancelled, under a UNIQUE index. Cancelled receipts
--     all become NULL and stop colliding; two live receipts for the same donation are
--     impossible.
--     KEEP the application-level guarded write as well. The constraint prevents the bad
--     row; the guarded write produces a clear error instead of a raw SQLSTATE 23000 in
--     front of a donor mid-payment.
--
--  5. `donation_items.line_total` is a STORED GENERATED column.
--     quantity * unit_price cannot drift from its own operands. The application still
--     recomputes items_amount server-side from campaign_products.unit_price — the
--     generated column protects the row, not the pricing decision.
--
--  6. `campaigns.slug` is UNIQUE and campaigns are SOFT-deleted, so a deleted campaign
--     keeps its slug reserved. That is intentional: slugs are frozen after publish and
--     old links must keep resolving through `redirects`. It does mean a slug can never
--     be reused after deletion, which is the correct trade for an SEO-bearing URL.
--
-- =====================================================================================
--  END OF SCHEMA
--  49 tables (35 application + 14 framework/package), 2 views.
--
--  NOT YET EXECUTED. There is no MySQL server or Docker daemon on this machine, so
--  this file has been checked statically only: every foreign key resolves to a table
--  and column that exists, every referenced table is created before the table that
--  references it, every index names a defined column, and parentheses and identifier
--  quoting balance. That is not the same as having run it. Before trusting it:
--
--      mysql -u root -p < database/schema/mysql-schema.sql
--
--  Expect to shake out at most a few width or default-value details on first run.
-- =====================================================================================
