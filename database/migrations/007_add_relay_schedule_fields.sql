-- Migration 007: Add Relay Modes and Scheduling Attributes to Stations
ALTER TABLE `stations`
  ADD COLUMN `relay_mode` ENUM('fulltime', 'scheduled', 'exclusive', 'disabled') NOT NULL DEFAULT 'fulltime' AFTER `relay_url`,
  ADD COLUMN `relay_start_hour` TIME NULL AFTER `relay_mode`,
  ADD COLUMN `relay_end_hour` TIME NULL AFTER `relay_start_hour`;
