-- =====================================================================
-- Migración 004: Luces del tablero por comisión (salida y regreso)
-- =====================================================================
-- Permite registrar qué luces de advertencia del tablero están
-- encendidas al momento de la salida y del regreso de una comisión,
-- reutilizando el mismo catálogo de luces que usa el módulo de
-- inspecciones.
--
-- Aplicar manualmente (phpMyAdmin / consola MySQL) una sola vez.
-- =====================================================================

CREATE TABLE IF NOT EXISTS comision_luces_tablero (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    comision_id INT UNSIGNED NOT NULL,
    momento ENUM('salida','regreso') NOT NULL,
    luz_codigo VARCHAR(40) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (comision_id) REFERENCES comisiones(id) ON DELETE CASCADE,
    INDEX idx_comision_luces_comision (comision_id),
    UNIQUE KEY uq_comision_luz (comision_id, momento, luz_codigo)
) ENGINE=InnoDB;
