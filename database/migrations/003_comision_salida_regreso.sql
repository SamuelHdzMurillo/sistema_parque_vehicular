-- =====================================================================
-- Migración 003: Comisión — responsable de regreso y documentos firmados
-- =====================================================================
-- Agrega a la tabla `comisiones`:
--   * responsable_regreso_nombre / responsable_regreso_id:
--     persona que se compromete a traer (regresar) el vehículo. Puede
--     seleccionarse de la lista de usuarios o escribirse a mano.
--   * doc_salida_ruta / doc_regreso_ruta:
--     rutas de los PDF firmados (salida y regreso) cargados al sistema.
--
-- Aplicar manualmente (phpMyAdmin / consola MySQL) sobre la base de datos
-- del sistema, una sola vez.
-- =====================================================================

ALTER TABLE comisiones
    ADD COLUMN responsable_regreso_nombre VARCHAR(200) NULL AFTER conductor_id,
    ADD COLUMN responsable_regreso_id INT UNSIGNED NULL AFTER responsable_regreso_nombre,
    ADD COLUMN doc_salida_ruta VARCHAR(255) NULL AFTER firma_digital,
    ADD COLUMN doc_regreso_ruta VARCHAR(255) NULL AFTER doc_salida_ruta,
    ADD CONSTRAINT fk_comisiones_resp_regreso
        FOREIGN KEY (responsable_regreso_id) REFERENCES users(id) ON DELETE SET NULL;
