-- Migration 008: Add bitrate column to stations for configurable AutoDJ streaming quality
ALTER TABLE `stations`
  ADD COLUMN `bitrate` INT NULL DEFAULT NULL AFTER `max_bitrate`;
