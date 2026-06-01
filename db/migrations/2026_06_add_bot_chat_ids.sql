-- Migration: store messaging chat ids on customers so the Telegram / Bale
-- bots can deliver PMs to the right person. Run once on existing installs.

ALTER TABLE customers
  ADD COLUMN telegram_chat_id VARCHAR(40) DEFAULT NULL AFTER consent_sms,
  ADD COLUMN bale_chat_id     VARCHAR(40) DEFAULT NULL AFTER telegram_chat_id;

-- speed up "find customer by chat id" lookups in the webhook
CREATE INDEX idx_customer_tg ON customers (tenant_id, telegram_chat_id);
CREATE INDEX idx_customer_bale ON customers (tenant_id, bale_chat_id);
