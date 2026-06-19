-- =====================================================================
-- Migración 014: Estado actual de luces del tablero por vehículo
-- =====================================================================
-- Guarda qué luces de advertencia están encendidas en cada unidad.
-- Se actualiza al registrar comisiones (salida/regreso) o inspecciones.
-- =====================================================================

CREATE TABLE IF NOT EXISTS vehiculo_luces_tablero (
    vehiculo_id INT UNSIGNED NOT NULL,
    luz_codigo VARCHAR(40) NOT NULL,
    PRIMARY KEY (vehiculo_id, luz_codigo),
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE,
    INDEX idx_vehiculo_luces_codigo (luz_codigo)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS vehiculo_luces_meta (
    vehiculo_id INT UNSIGNED NOT NULL PRIMARY KEY,
    origen_tipo ENUM('comision','inspeccion') NOT NULL,
    origen_id INT UNSIGNED NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE
) ENGINE=InnoDB;
