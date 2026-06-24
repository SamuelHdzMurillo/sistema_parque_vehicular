-- Varios servicios preventivos por mantenimiento (tabla puente)
CREATE TABLE IF NOT EXISTS mantenimiento_servicios (
    mantenimiento_id INT UNSIGNED NOT NULL,
    servicio VARCHAR(50) NOT NULL,
    PRIMARY KEY (mantenimiento_id, servicio),
    CONSTRAINT fk_mantenimiento_servicios_mantenimiento
        FOREIGN KEY (mantenimiento_id) REFERENCES mantenimientos(id) ON DELETE CASCADE
);

INSERT IGNORE INTO mantenimiento_servicios (mantenimiento_id, servicio)
SELECT id, servicio FROM mantenimientos WHERE servicio IS NOT NULL AND servicio != '';
