-- Add synced_at columns for local -> live sync tracking
-- Run this in your local MySQL database before using the sync feature.

ALTER TABLE products ADD COLUMN synced_at DATETIME NULL AFTER created_at;
ALTER TABLE inventory ADD COLUMN synced_at DATETIME NULL AFTER updated_at;
