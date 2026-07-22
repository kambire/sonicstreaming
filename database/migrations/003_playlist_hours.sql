-- Migration 003: Horarios de inicio y fin para playlists programadas
ALTER TABLE playlists ADD COLUMN IF NOT EXISTS start_time VARCHAR(8) NULL DEFAULT NULL;
ALTER TABLE playlists ADD COLUMN IF NOT EXISTS end_time VARCHAR(8) NULL DEFAULT NULL;
