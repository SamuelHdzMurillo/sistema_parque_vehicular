-- =====================================================================
-- Migración 005: Niveles de líquidos por comisión (salida y regreso)
-- =====================================================================
-- Registra el nivel (lleno / medio / bajo) de los líquidos del vehículo
-- al momento de la salida y del regreso de una comisión.
--
-- Aplicar manualmente (phpMyAdmin / consola MySQL) una sola vez.
-- =====================================================================

CREATE TABLE IF NOT EXISTS comision_niveles_liquidos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    comision_id INT UNSIGNED NOT NULL,
    momento ENUM('salida','regreso') NOT NULL,
    liquido_codigo VARCHAR(40) NOT NULL,
    nivel ENUM('lleno','medio','bajo') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (comision_id) REFERENCES comisiones(id) ON DELETE CASCADE,
    INDEX idx_comision_niveles_comision (comision_id),
    UNIQUE KEY uq_comision_liquido (comision_id, momento, liquido_codigo)
) ENGINE=InnoDB;
