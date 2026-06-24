-- Vincula mantenimientos con tipos de alerta (cambio_aceite, afinacion, llantas…)
ALTER TABLE mantenimientos
    ADD COLUMN servicio VARCHAR(50) NULL AFTER tipo,
    ADD INDEX idx_mantenimientos_servicio (servicio);

UPDATE mantenimientos SET servicio = 'cambio_aceite'
WHERE servicio IS NULL AND tipo = 'preventivo' AND estado = 'finalizado'
  AND (descripcion LIKE '%aceite%' OR descripcion LIKE '%Aceite%');

UPDATE mantenimientos SET servicio = 'afinacion'
WHERE servicio IS NULL AND tipo = 'preventivo' AND estado = 'finalizado'
  AND (descripcion LIKE '%afinaci%' OR descripcion LIKE '%Afinaci%');

UPDATE mantenimientos SET servicio = 'llantas'
WHERE servicio IS NULL AND tipo = 'preventivo' AND estado = 'finalizado'
  AND (descripcion LIKE '%llanta%' OR descripcion LIKE '%Llanta%');
