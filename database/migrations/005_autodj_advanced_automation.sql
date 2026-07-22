-- Migration 005: Automatizacion Avanzada de AutoDJ (Vinetas, Publicidad y Hora Exacta)
ALTER TABLE playlists 
  MODIFY COLUMN type ENUM('general', 'scheduled', 'jingle', 'commercial', 'top_of_hour') NOT NULL DEFAULT 'general',
  ADD COLUMN IF NOT EXISTS play_every_x INT UNSIGNED NOT NULL DEFAULT 0 AFTER shuffle,
  ADD COLUMN IF NOT EXISTS interrupt_immediately TINYINT(1) NOT NULL DEFAULT 0 AFTER play_every_x,
  ADD COLUMN IF NOT EXISTS cron_minute INT NOT NULL DEFAULT 0 AFTER interrupt_immediately;
