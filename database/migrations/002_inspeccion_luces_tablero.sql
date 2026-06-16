-- Luces del tablero encendidas por inspección
USE sicv_cecyte_bcs;

CREATE TABLE IF NOT EXISTS inspeccion_luces_tablero (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inspeccion_id INT UNSIGNED NOT NULL,
    luz_codigo VARCHAR(40) NOT NULL,
    FOREIGN KEY (inspeccion_id) REFERENCES inspecciones(id) ON DELETE CASCADE,
    INDEX idx_inspeccion_luces_inspeccion (inspeccion_id),
    UNIQUE KEY uq_inspeccion_luz (inspeccion_id, luz_codigo)
) ENGINE=InnoDB;
