-- ============================================================
--  Online Restaurant / Cafe Menu System  -  Database Schema
--  Engine: MySQL / MariaDB (shared hosting friendly)
--  Charset: utf8mb4 (full Persian + emoji support)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------
--  Tenants  (each restaurant / cafe = one tenant)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS tenants (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(150) NOT NULL,
  slug          VARCHAR(80)  NOT NULL,
  phone         VARCHAR(30)  DEFAULT NULL,
  address       VARCHAR(255) DEFAULT NULL,
  currency      VARCHAR(10)  NOT NULL DEFAULT 'تومان',
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_tenant_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
--  Theme / UI settings per tenant
--  template: classic | modern | elegant | dark  (different layouts
--  so two tenants never look identical)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS themes (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id       INT UNSIGNED NOT NULL,
  template        VARCHAR(30)  NOT NULL DEFAULT 'classic',
  primary_color   VARCHAR(20)  NOT NULL DEFAULT '#c0392b',
  secondary_color VARCHAR(20)  NOT NULL DEFAULT '#2c3e50',
  bg_color        VARCHAR(20)  NOT NULL DEFAULT '#faf7f2',
  text_color      VARCHAR(20)  NOT NULL DEFAULT '#2c3e50',
  font_family     VARCHAR(60)  NOT NULL DEFAULT 'Vazirmatn',
  logo_path       VARCHAR(255) DEFAULT NULL,
  hero_image      VARCHAR(255) DEFAULT NULL,
  hero_title      VARCHAR(150) DEFAULT NULL,
  hero_subtitle   VARCHAR(255) DEFAULT NULL,
  custom_css      TEXT         DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_theme_tenant (tenant_id),
  CONSTRAINT fk_theme_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
--  Users  (super admin + per-tenant admins/staff)
--  role: superadmin | admin | staff
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id     INT UNSIGNED DEFAULT NULL,
  name          VARCHAR(120) NOT NULL,
  email         VARCHAR(150) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          VARCHAR(20)  NOT NULL DEFAULT 'admin',
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_user_email (email),
  KEY idx_user_tenant (tenant_id),
  CONSTRAINT fk_user_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
--  Categories
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id   INT UNSIGNED NOT NULL,
  name        VARCHAR(120) NOT NULL,
  sort_order  INT          NOT NULL DEFAULT 0,
  is_active   TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  KEY idx_cat_tenant (tenant_id),
  CONSTRAINT fk_cat_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
--  Menu items
--  price stored in the tenant currency's smallest practical unit
--  (here: plain integer amount, e.g. Toman). rating cached for speed.
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS items (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id     INT UNSIGNED NOT NULL,
  category_id   INT UNSIGNED DEFAULT NULL,
  name          VARCHAR(150) NOT NULL,
  description   VARCHAR(500) DEFAULT NULL,
  price         BIGINT       NOT NULL DEFAULT 0,
  image_path    VARCHAR(255) DEFAULT NULL,
  is_available  TINYINT(1)   NOT NULL DEFAULT 1,
  is_featured   TINYINT(1)   NOT NULL DEFAULT 0,
  sort_order    INT          NOT NULL DEFAULT 0,
  rating_sum    INT UNSIGNED NOT NULL DEFAULT 0,
  rating_count  INT UNSIGNED NOT NULL DEFAULT 0,
  order_count   INT UNSIGNED NOT NULL DEFAULT 0,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_item_tenant (tenant_id),
  KEY idx_item_category (category_id),
  KEY idx_item_available (tenant_id, is_available),
  CONSTRAINT fk_item_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_item_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
--  Customers (end diners) per tenant
--  rank_score is recomputed by RankingService from accounting data
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS customers (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id     INT UNSIGNED NOT NULL,
  first_name    VARCHAR(80)  NOT NULL,
  last_name     VARCHAR(80)  DEFAULT NULL,
  phone         VARCHAR(30)  NOT NULL,
  birthdate     DATE         DEFAULT NULL,
  total_spent   BIGINT       NOT NULL DEFAULT 0,
  orders_count  INT UNSIGNED NOT NULL DEFAULT 0,
  rank_score    INT          NOT NULL DEFAULT 0,
  tier          VARCHAR(20)  NOT NULL DEFAULT 'bronze',
  consent_sms   TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_customer_phone (tenant_id, phone),
  KEY idx_customer_tenant (tenant_id),
  KEY idx_customer_birth (tenant_id, birthdate),
  CONSTRAINT fk_customer_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
--  Orders + order items (also feeds ranking / popularity)
--  status: pending | paid | preparing | done | canceled
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id      INT UNSIGNED NOT NULL,
  customer_id    INT UNSIGNED DEFAULT NULL,
  total          BIGINT       NOT NULL DEFAULT 0,
  discount_total BIGINT       NOT NULL DEFAULT 0,
  status         VARCHAR(20)  NOT NULL DEFAULT 'pending',
  payment_status VARCHAR(20)  NOT NULL DEFAULT 'unpaid',
  payment_ref    VARCHAR(120) DEFAULT NULL,
  note           VARCHAR(500) DEFAULT NULL,
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_order_tenant (tenant_id),
  KEY idx_order_customer (customer_id),
  KEY idx_order_created (tenant_id, created_at),
  CONSTRAINT fk_order_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_order_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id       INT UNSIGNED NOT NULL,
  item_id        INT UNSIGNED DEFAULT NULL,
  name_snapshot  VARCHAR(150) NOT NULL,
  price_snapshot BIGINT       NOT NULL DEFAULT 0,
  qty            INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  KEY idx_oi_order (order_id),
  CONSTRAINT fk_oi_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_oi_item FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
--  Discounts / promotions
--  type: percent | fixed
--  scope: all | category | item | tier
--  days_of_week: comma list 0-6 (Sat..Fri) for day-based promos, NULL = always
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS discounts (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id    INT UNSIGNED NOT NULL,
  name         VARCHAR(120) NOT NULL,
  type         VARCHAR(10)  NOT NULL DEFAULT 'percent',
  value        INT          NOT NULL DEFAULT 0,
  scope        VARCHAR(15)  NOT NULL DEFAULT 'all',
  target_id    INT UNSIGNED DEFAULT NULL,
  days_of_week VARCHAR(20)  DEFAULT NULL,
  starts_at    DATE         DEFAULT NULL,
  ends_at      DATE         DEFAULT NULL,
  is_active    TINYINT(1)   NOT NULL DEFAULT 1,
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_disc_tenant (tenant_id, is_active),
  CONSTRAINT fk_disc_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
--  Audit log for bulk price shifts (undo / accountability)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS price_change_log (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id   INT UNSIGNED NOT NULL,
  user_id     INT UNSIGNED DEFAULT NULL,
  scope       VARCHAR(15)  NOT NULL,
  target_id   INT UNSIGNED DEFAULT NULL,
  mode        VARCHAR(10)  NOT NULL,            -- percent | fixed
  direction   VARCHAR(4)   NOT NULL,            -- up | down
  amount      INT          NOT NULL,
  affected    INT          NOT NULL DEFAULT 0,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_pcl_tenant (tenant_id),
  CONSTRAINT fk_pcl_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
--  Channel integrations (telegram / bale / sms / payment / accounting)
--  config_json holds tokens & endpoints; never exposed to public.
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS integrations (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id   INT UNSIGNED NOT NULL,
  channel     VARCHAR(20)  NOT NULL,            -- telegram | bale | sms | payment | accounting
  provider    VARCHAR(40)  DEFAULT NULL,        -- e.g. zarinpal | kavenegar | hesabfa
  config_json TEXT         DEFAULT NULL,
  is_active   TINYINT(1)   NOT NULL DEFAULT 0,
  updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_integration (tenant_id, channel),
  CONSTRAINT fk_integ_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
--  Campaigns (broadcast PM / SMS) with audience targeting
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS campaigns (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id    INT UNSIGNED NOT NULL,
  title        VARCHAR(150) NOT NULL,
  body         TEXT         NOT NULL,
  channel      VARCHAR(20)  NOT NULL DEFAULT 'sms',  -- sms | telegram | bale
  min_age      INT          DEFAULT NULL,
  max_age      INT          DEFAULT NULL,
  tier         VARCHAR(20)  DEFAULT NULL,
  status       VARCHAR(15)  NOT NULL DEFAULT 'draft', -- draft | sending | sent
  sent_count   INT          NOT NULL DEFAULT 0,
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_camp_tenant (tenant_id),
  CONSTRAINT fk_camp_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS message_log (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id    INT UNSIGNED NOT NULL,
  customer_id  INT UNSIGNED DEFAULT NULL,
  campaign_id  INT UNSIGNED DEFAULT NULL,
  channel      VARCHAR(20)  NOT NULL,
  kind         VARCHAR(20)  NOT NULL DEFAULT 'campaign', -- campaign | birthday | system
  recipient    VARCHAR(60)  NOT NULL,
  body         TEXT         NOT NULL,
  status       VARCHAR(15)  NOT NULL DEFAULT 'queued',  -- queued | sent | failed
  error        VARCHAR(255) DEFAULT NULL,
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_msg_tenant (tenant_id),
  KEY idx_msg_customer (customer_id),
  CONSTRAINT fk_msg_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
--  Surveys (20 multiple-choice questions per tenant)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS survey_questions (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id   INT UNSIGNED NOT NULL,
  question    VARCHAR(255) NOT NULL,
  sort_order  INT          NOT NULL DEFAULT 0,
  is_active   TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  KEY idx_sq_tenant (tenant_id),
  CONSTRAINT fk_sq_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS survey_options (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  question_id INT UNSIGNED NOT NULL,
  label       VARCHAR(150) NOT NULL,
  sort_order  INT          NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_so_question (question_id),
  CONSTRAINT fk_so_question FOREIGN KEY (question_id) REFERENCES survey_questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS survey_responses (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id   INT UNSIGNED NOT NULL,
  customer_id INT UNSIGNED DEFAULT NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_sr_tenant (tenant_id),
  CONSTRAINT fk_sr_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS survey_answers (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  response_id INT UNSIGNED NOT NULL,
  question_id INT UNSIGNED NOT NULL,
  option_id   INT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_sa_response (response_id),
  KEY idx_sa_question (question_id),
  CONSTRAINT fk_sa_response FOREIGN KEY (response_id) REFERENCES survey_responses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
--  Item ratings (stars) -> feeds rating cache + popular list
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS ratings (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id   INT UNSIGNED NOT NULL,
  item_id     INT UNSIGNED NOT NULL,
  customer_id INT UNSIGNED DEFAULT NULL,
  stars       TINYINT      NOT NULL DEFAULT 5,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_rating_item (item_id),
  CONSTRAINT fk_rating_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_rating_item FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
--  Birthday greeting log (prevents duplicate greetings per year)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS birthday_log (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id   INT UNSIGNED NOT NULL,
  customer_id INT UNSIGNED NOT NULL,
  year_sent   SMALLINT     NOT NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_birthday (customer_id, year_sent),
  CONSTRAINT fk_bday_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
