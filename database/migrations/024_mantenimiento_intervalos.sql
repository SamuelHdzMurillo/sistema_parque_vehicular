-- Intervalos de próximo servicio por registro de mantenimiento (fuente para alertas)
ALTER TABLE mantenimiento_servicios
    ADD COLUMN intervalo_km INT UNSIGNED NULL AFTER servicio,
    ADD COLUMN intervalo_dias INT UNSIGNED NULL AFTER intervalo_km;

-- Backfill desde configuración legacy de alerta_config
UPDATE mantenimiento_servicios ms
INNER JOIN alerta_config ac ON ac.tipo = ms.servicio AND ac.unidad = 'km'
SET
    ms.intervalo_km = COALESCE(ms.intervalo_km, NULLIF(ac.umbral_verde, 0)),
    ms.intervalo_dias = COALESCE(ms.intervalo_dias, ac.umbral_verde_dias)
WHERE ms.intervalo_km IS NULL OR ms.intervalo_dias IS NULL;
