-- Permite registrar mantenimientos realizados antes del sistema (kilometraje menor al actual).
ALTER TABLE mantenimientos
    ADD COLUMN es_historico TINYINT(1) NOT NULL DEFAULT 0 AFTER kilometraje;
