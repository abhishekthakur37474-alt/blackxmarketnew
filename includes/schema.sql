-- BLACK X MARKET - MySQL schema
-- Import this file into the database configured in includes/config.php
-- (e.g. u758638448_blackxmarket)
--
-- Usage (command line):
--   mysql -u <user> -p <database> < includes/schema.sql
--
-- Timestamps are stored as epoch milliseconds (BIGINT) to match the
-- front-end JavaScript (Date.now()).

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `users` (
  `uid`           VARCHAR(40)  NOT NULL,
  `name`          VARCHAR(120) NOT NULL DEFAULT '',
  `email`         VARCHAR(190) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL DEFAULT '',
  `phone`         VARCHAR(40)  NOT NULL DEFAULT '',
  `photo_url`     VARCHAR(500) NOT NULL DEFAULT '',
  `role`          VARCHAR(20)  NOT NULL DEFAULT 'user',
  `status`        VARCHAR(20)  NOT NULL DEFAULT 'active',
  `created_at`    BIGINT       NOT NULL DEFAULT 0,
  `updated_at`    BIGINT       NOT NULL DEFAULT 0,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `uniq_users_email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products` (
  `id`                 VARCHAR(40)  NOT NULL,
  `title`              VARCHAR(255) NOT NULL DEFAULT '',
  `category`           VARCHAR(120) NOT NULL DEFAULT '',
  `description`        TEXT         NULL,
  `original_price`     DECIMAL(12,2) NOT NULL DEFAULT 0,
  `discounted_price`   DECIMAL(12,2) NOT NULL DEFAULT 0,
  `discount_percent`   DECIMAL(6,2)  NOT NULL DEFAULT 0,
  `status`             VARCHAR(20)  NOT NULL DEFAULT 'active',
  `thumbnail_url`      VARCHAR(500) NOT NULL DEFAULT '',
  `additional_details` LONGTEXT     NULL,
  `created_at`         BIGINT       NOT NULL DEFAULT 0,
  `updated_at`         BIGINT       NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_products_status` (`status`),
  KEY `idx_products_category` (`category`),
  KEY `idx_products_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `carousels` (
  `id`          VARCHAR(40)  NOT NULL,
  `title`       VARCHAR(255) NOT NULL DEFAULT '',
  `description` VARCHAR(500) NOT NULL DEFAULT '',
  `button_text` VARCHAR(120) NOT NULL DEFAULT '',
  `link`        VARCHAR(500) NOT NULL DEFAULT '',
  `image_url`   VARCHAR(500) NOT NULL DEFAULT '',
  `sort_order`  INT          NOT NULL DEFAULT 0,
  `status`      VARCHAR(20)  NOT NULL DEFAULT 'active',
  `created_at`  BIGINT       NOT NULL DEFAULT 0,
  `updated_at`  BIGINT       NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_carousels_order` (`sort_order`),
  KEY `idx_carousels_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `coupons` (
  `id`           VARCHAR(40)   NOT NULL,
  `code`         VARCHAR(80)   NOT NULL,
  `type`         VARCHAR(20)   NOT NULL DEFAULT 'percentage',
  `value`        DECIMAL(12,2) NOT NULL DEFAULT 0,
  `min_amount`   DECIMAL(12,2) NOT NULL DEFAULT 0,
  `max_discount` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `expiry_date`  VARCHAR(20)   NOT NULL DEFAULT '',
  `usage_limit`  INT           NOT NULL DEFAULT 0,
  `used_count`   INT           NOT NULL DEFAULT 0,
  `status`       VARCHAR(20)   NOT NULL DEFAULT 'active',
  `created_at`   BIGINT        NOT NULL DEFAULT 0,
  `updated_at`   BIGINT        NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_coupons_code` (`code`),
  KEY `idx_coupons_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `orders` (
  `order_id`               VARCHAR(40)   NOT NULL,
  `uid`                    VARCHAR(40)   NOT NULL,
  `user_email`             VARCHAR(190)  NOT NULL DEFAULT '',
  `items`                  LONGTEXT      NULL,
  `product_id`             VARCHAR(40)   NOT NULL DEFAULT '',
  `product_title`          VARCHAR(255)  NOT NULL DEFAULT '',
  `product_thumbnail`      VARCHAR(500)  NOT NULL DEFAULT '',
  `subtotal`               DECIMAL(12,2) NOT NULL DEFAULT 0,
  `coupon_code`            VARCHAR(80)   NOT NULL DEFAULT '',
  `coupon_id`              VARCHAR(40)   NOT NULL DEFAULT '',
  `coupon_discount`        DECIMAL(12,2) NOT NULL DEFAULT 0,
  `amount`                 DECIMAL(12,2) NOT NULL DEFAULT 0,
  `transaction_id`         VARCHAR(190)  NOT NULL DEFAULT '',
  `payment_screenshot_url` VARCHAR(500)  NOT NULL DEFAULT '',
  `payment_snapshot`       LONGTEXT      NULL,
  `delivery_details`       LONGTEXT      NULL,
  `status`                 VARCHAR(30)   NOT NULL DEFAULT 'pending',
  `rejection_reason`       TEXT          NULL,
  `created_at`             BIGINT        NOT NULL DEFAULT 0,
  `updated_at`             BIGINT        NOT NULL DEFAULT 0,
  PRIMARY KEY (`order_id`),
  KEY `idx_orders_uid` (`uid`),
  KEY `idx_orders_status` (`status`),
  KEY `idx_orders_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `carts` (
  `uid`        VARCHAR(40) NOT NULL,
  `product_id` VARCHAR(40) NOT NULL,
  `quantity`   INT         NOT NULL DEFAULT 1,
  `added_at`   BIGINT      NOT NULL DEFAULT 0,
  PRIMARY KEY (`uid`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wishlists` (
  `uid`        VARCHAR(40) NOT NULL,
  `product_id` VARCHAR(40) NOT NULL,
  `added_at`   BIGINT      NOT NULL DEFAULT 0,
  PRIMARY KEY (`uid`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reviews` (
  `id`            VARCHAR(40)  NOT NULL,
  `uid`           VARCHAR(40)  NOT NULL,
  `user_name`     VARCHAR(120) NOT NULL DEFAULT '',
  `product_id`    VARCHAR(40)  NOT NULL DEFAULT '',
  `product_title` VARCHAR(255) NOT NULL DEFAULT '',
  `rating`        TINYINT      NOT NULL DEFAULT 0,
  `comment`       TEXT         NULL,
  `status`        VARCHAR(20)  NOT NULL DEFAULT 'pending',
  `created_at`    BIGINT       NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_reviews_status` (`status`),
  KEY `idx_reviews_product` (`product_id`),
  KEY `idx_reviews_uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `support_tickets` (
  `id`         VARCHAR(40)  NOT NULL,
  `uid`        VARCHAR(40)  NOT NULL,
  `user_name`  VARCHAR(120) NOT NULL DEFAULT '',
  `email`      VARCHAR(190) NOT NULL DEFAULT '',
  `order_id`   VARCHAR(40)  NOT NULL DEFAULT '',
  `subject`    VARCHAR(255) NOT NULL DEFAULT '',
  `message`    TEXT         NULL,
  `reply`      TEXT         NULL,
  `status`     VARCHAR(20)  NOT NULL DEFAULT 'open',
  `created_at` BIGINT       NOT NULL DEFAULT 0,
  `updated_at` BIGINT       NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_support_uid` (`uid`),
  KEY `idx_support_status` (`status`),
  KEY `idx_support_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id`         VARCHAR(40)  NOT NULL,
  `uid`        VARCHAR(40)  NOT NULL,
  `title`      VARCHAR(255) NOT NULL DEFAULT '',
  `message`    TEXT         NULL,
  `type`       VARCHAR(20)  NOT NULL DEFAULT 'info',
  `link`       VARCHAR(500) NOT NULL DEFAULT '',
  `is_read`    TINYINT      NOT NULL DEFAULT 0,
  `created_at` BIGINT       NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_notif_uid` (`uid`),
  KEY `idx_notif_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `settings` (
  `name`  VARCHAR(60) NOT NULL,
  `value` LONGTEXT    NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id`         VARCHAR(40)  NOT NULL,
  `email`      VARCHAR(190) NOT NULL,
  `token`      VARCHAR(190) NOT NULL,
  `expires_at` BIGINT       NOT NULL DEFAULT 0,
  `created_at` BIGINT       NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_reset_token` (`token`),
  KEY `idx_reset_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default settings (safe to re-run)
INSERT INTO `settings` (`name`, `value`) VALUES
  ('payment', '{"upiId":"","qrCodeUrl":"","paymentDisplayName":"BLACK X MARKET","instructions":"Scan the QR code or use the UPI ID, then submit the screenshot and transaction ID.","active":true}'),
  ('siteInfo', '{"siteName":"BLACK X MARKET","tagline":"DIGITAL GAMING STUFF, DELIVERED.","supportEmail":"","currency":"\u20b9"}')
ON DUPLICATE KEY UPDATE `name` = `name`;
