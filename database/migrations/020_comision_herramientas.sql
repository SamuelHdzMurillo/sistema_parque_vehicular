-- =====================================================================
-- Migración 020: Herramientas entregadas por comisión (salida y regreso)
-- =====================================================================
-- Registra qué herramientas del vehículo se entregan al salir y cuáles
-- regresan al finalizar la comisión.
--
-- Aplicar manualmente (phpMyAdmin / consola MySQL) una sola vez.
-- =====================================================================

CREATE TABLE IF NOT EXISTS comision_herramientas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    comision_id INT UNSIGNED NOT NULL,
    momento ENUM('salida','regreso') NOT NULL,
    tipo VARCHAR(40) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (comision_id) REFERENCES comisiones(id) ON DELETE CASCADE,
    INDEX idx_comision_herramientas_comision (comision_id),
    UNIQUE KEY uq_comision_herramienta (comision_id, momento, tipo)
) ENGINE=InnoDB;
