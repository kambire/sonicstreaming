-- Migration 006: Expand Server Configuration Attributes
ALTER TABLE `servers`
  ADD COLUMN `public_ip` VARCHAR(45) NULL AFTER `hostname`,
  ADD COLUMN `ssl_port` INT UNSIGNED DEFAULT 7000 AFTER `public_ip`,
  ADD COLUMN `ssh_port` INT UNSIGNED DEFAULT 40002 AFTER `ssl_port`,
  ADD COLUMN `ssh_user` VARCHAR(60) DEFAULT 'user' AFTER `ssh_port`,
  ADD COLUMN `harbor_port_offset` INT UNSIGNED DEFAULT 10000 AFTER `port_range_end`,
  ADD COLUMN `telnet_port_offset` INT UNSIGNED DEFAULT 20000 AFTER `harbor_port_offset`,
  ADD COLUMN `default_max_listeners` INT UNSIGNED DEFAULT 500 AFTER `max_streams`,
  ADD COLUMN `default_max_bitrate` INT UNSIGNED DEFAULT 192 AFTER `default_max_listeners`,
  ADD COLUMN `default_max_tracks` INT UNSIGNED DEFAULT 500 AFTER `default_max_bitrate`,
  ADD COLUMN `datacenter_location` VARCHAR(190) NULL AFTER `status`,
  ADD COLUMN `notes` TEXT NULL AFTER `datacenter_location`;

-- Actualizar servidor inicial #1 con datos reales de produccion
UPDATE `servers` SET
  `hostname` = 'sonic.geeks.com.py',
  `public_ip` = '186.182.28.19',
  `ssl_port` = 7000,
  `ssh_port` = 40002,
  `datacenter_location` = 'Asunción, Paraguay (Geeks DataCenter)'
WHERE `id` = 1;
