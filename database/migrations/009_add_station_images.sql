-- Migration 009: Add logo_url and background_url to stations table
ALTER TABLE `stations`
  ADD COLUMN `logo_url` VARCHAR(255) NULL AFTER `genre`,
  ADD COLUMN `background_url` VARCHAR(255) NULL AFTER `logo_url`;
