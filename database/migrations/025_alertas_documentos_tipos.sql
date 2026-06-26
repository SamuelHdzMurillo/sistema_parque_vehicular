-- Tipos de alerta para documentos adicionales y corrección de registros sin tipo.
INSERT INTO alerta_config (tipo, nombre, umbral_verde, umbral_amarillo, umbral_rojo, unidad)
SELECT 'tarjeta_circulacion', 'Tarjeta de circulación', 60, 30, 0, 'dias'
WHERE NOT EXISTS (SELECT 1 FROM alerta_config WHERE tipo = 'tarjeta_circulacion');

INSERT INTO alerta_config (tipo, nombre, umbral_verde, umbral_amarillo, umbral_rojo, unidad)
SELECT 'factura', 'Factura', 60, 30, 0, 'dias'
WHERE NOT EXISTS (SELECT 1 FROM alerta_config WHERE tipo = 'factura');

INSERT INTO alerta_config (tipo, nombre, umbral_verde, umbral_amarillo, umbral_rojo, unidad)
SELECT 'otro', 'Otro documento', 60, 30, 0, 'dias'
WHERE NOT EXISTS (SELECT 1 FROM alerta_config WHERE tipo = 'otro');

UPDATE documentos
SET tipo = 'poliza'
WHERE activo = 1
  AND (tipo IS NULL OR tipo = '')
  AND (LOWER(titulo) LIKE '%poliza%' OR LOWER(titulo) LIKE '%seguro%');

UPDATE documentos
SET tipo = 'tarjeta_circulacion'
WHERE activo = 1
  AND (tipo IS NULL OR tipo = '')
  AND (LOWER(titulo) LIKE '%tarjeta%' OR LOWER(titulo) LIKE '%circulacion%');

UPDATE documentos
SET tipo = 'verificacion'
WHERE activo = 1
  AND (tipo IS NULL OR tipo = '')
  AND LOWER(titulo) LIKE '%verificacion%';

UPDATE documentos
SET tipo = 'otro'
WHERE activo = 1
  AND (tipo IS NULL OR tipo = '');
