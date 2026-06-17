-- =====================================================================
-- Migración 010: Planteles, áreas por plantel y catálogo de conductores
-- =====================================================================
-- * planteles: sedes / campus (DG, LP, CAB, etc.)
-- * areas.plantel_id: vincula cada área a su plantel (ej. Jurídico — DG)
-- * conductores: nombre, área y teléfono para comisiones
-- * comisiones.conductor_id pasa a referenciar conductores (antes users)
-- =====================================================================

USE sicv_cecyte_bcs;

CREATE TABLE IF NOT EXISTS planteles (
    id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO planteles (clave, nombre) VALUES
('DG', 'Dirección General'),
('LP', 'Plantel La Paz'),
('CAB', 'Plantel Cabo San Lucas');

SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'areas' AND COLUMN_NAME = 'plantel_id'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE areas ADD COLUMN plantel_id SMALLINT UNSIGNED NULL AFTER nombre',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE areas a
JOIN planteles p ON p.clave = 'DG'
SET a.plantel_id = p.id
WHERE a.clave IN ('DG', 'TRANS', 'SERV-GEN', 'JUR') AND a.plantel_id IS NULL;

UPDATE areas a
JOIN planteles p ON p.clave = 'LP'
SET a.plantel_id = p.id
WHERE a.clave IN ('PLANTEL-LP') AND a.plantel_id IS NULL;

UPDATE areas a
JOIN planteles p ON p.clave = 'CAB'
SET a.plantel_id = p.id
WHERE a.clave IN ('PLANTEL-CAB') AND a.plantel_id IS NULL;

UPDATE areas a
JOIN planteles p ON p.clave = 'DG'
SET a.plantel_id = p.id
WHERE a.plantel_id IS NULL;

SET @fk_exists = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'areas' AND CONSTRAINT_NAME = 'fk_areas_plantel'
);
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE areas ADD CONSTRAINT fk_areas_plantel FOREIGN KEY (plantel_id) REFERENCES planteles(id) ON DELETE SET NULL',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT IGNORE INTO areas (clave, nombre, plantel_id)
SELECT 'JUR', 'Jurídico', p.id FROM planteles p WHERE p.clave = 'DG' LIMIT 1;

INSERT IGNORE INTO areas (clave, nombre, plantel_id)
SELECT 'ADM', 'Administración', p.id FROM planteles p WHERE p.clave = 'LP' LIMIT 1;

INSERT IGNORE INTO areas (clave, nombre, plantel_id)
SELECT 'ADM-CAB', 'Administración', p.id FROM planteles p WHERE p.clave = 'CAB' LIMIT 1;

CREATE TABLE IF NOT EXISTS conductores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    area_id SMALLINT UNSIGNED NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (area_id) REFERENCES areas(id),
    INDEX idx_conductores_area (area_id),
    INDEX idx_conductores_activo (activo)
) ENGINE=InnoDB;

SET @constraint_name = (
    SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'comisiones'
      AND COLUMN_NAME = 'conductor_id' AND REFERENCED_TABLE_NAME = 'users'
    LIMIT 1
);
SET @sql = IF(@constraint_name IS NOT NULL,
    CONCAT('ALTER TABLE comisiones DROP FOREIGN KEY ', @constraint_name),
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE comisiones SET conductor_id = NULL WHERE conductor_id IS NOT NULL;

SET @fk_conductor_exists = (
    SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'comisiones'
      AND COLUMN_NAME = 'conductor_id' AND REFERENCED_TABLE_NAME = 'conductores'
);
SET @sql = IF(@fk_conductor_exists = 0,
    'ALTER TABLE comisiones ADD CONSTRAINT fk_comisiones_conductor FOREIGN KEY (conductor_id) REFERENCES conductores(id) ON DELETE SET NULL',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT IGNORE INTO conductores (nombre, area_id, telefono)
SELECT 'Lic. María González', a.id, '6121001101'
FROM areas a WHERE a.clave = 'JUR' LIMIT 1;

INSERT IGNORE INTO conductores (nombre, area_id, telefono)
SELECT 'Ing. Roberto Sánchez', a.id, '6121001102'
FROM areas a WHERE a.clave = 'TRANS' LIMIT 1;

INSERT IGNORE INTO conductores (nombre, area_id, telefono)
SELECT 'Lic. Patricia Morales', a.id, '6121001103'
FROM areas a WHERE a.clave = 'DG' LIMIT 1;

INSERT IGNORE INTO conductores (nombre, area_id, telefono)
SELECT 'Juan Pérez López', a.id, '6124445566'
FROM areas a WHERE a.clave = 'PLANTEL-LP' LIMIT 1;

INSERT IGNORE INTO conductores (nombre, area_id, telefono)
SELECT 'Ana Torres Vega', a.id, '6121112233'
FROM areas a WHERE a.clave = 'PLANTEL-CAB' LIMIT 1;

INSERT IGNORE INTO conductores (nombre, area_id, telefono)
SELECT 'Carlos Mendoza Ruiz', a.id, '6127654321'
FROM areas a WHERE a.clave = 'TRANS' LIMIT 1;
