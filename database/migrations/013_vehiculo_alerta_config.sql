-- Umbrales en días para alertas basadas en km (lógica OR: km o días)
ALTER TABLE alerta_config
    ADD COLUMN IF NOT EXISTS umbral_verde_dias INT UNSIGNED NULL DEFAULT NULL AFTER umbral_rojo,
    ADD COLUMN IF NOT EXISTS umbral_amarillo_dias INT UNSIGNED NULL DEFAULT NULL AFTER umbral_verde_dias,
    ADD COLUMN IF NOT EXISTS umbral_rojo_dias INT UNSIGNED NULL DEFAULT NULL AFTER umbral_amarillo_dias;

UPDATE alerta_config SET
    umbral_verde_dias = 365,
    umbral_amarillo_dias = 180,
    umbral_rojo_dias = 90
WHERE unidad = 'km' AND umbral_verde_dias IS NULL;

-- Umbrales en días por vehículo (tabla puede existir con esquema previo)
ALTER TABLE vehiculo_alerta_config
    ADD COLUMN IF NOT EXISTS umbral_verde_dias INT UNSIGNED NULL DEFAULT NULL AFTER umbral_rojo,
    ADD COLUMN IF NOT EXISTS umbral_amarillo_dias INT UNSIGNED NULL DEFAULT NULL AFTER umbral_verde_dias,
    ADD COLUMN IF NOT EXISTS umbral_rojo_dias INT UNSIGNED NULL DEFAULT NULL AFTER umbral_amarillo_dias;
