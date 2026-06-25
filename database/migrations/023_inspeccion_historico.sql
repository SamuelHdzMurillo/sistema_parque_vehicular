-- Permite registrar inspecciones olvidadas (fecha o kilometraje anterior al actual).
ALTER TABLE inspecciones
    ADD COLUMN es_historico TINYINT(1) NOT NULL DEFAULT 0 AFTER kilometraje;
