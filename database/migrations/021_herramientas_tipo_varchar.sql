-- =====================================================================
-- Migración 021: Permitir tipos de herramienta personalizados
-- =====================================================================
-- Cambia el ENUM fijo por VARCHAR para registrar herramientas adicionales
-- que no están en el catálogo estándar.
--
-- Aplicar manualmente (phpMyAdmin / consola MySQL) una sola vez.
-- =====================================================================

ALTER TABLE herramientas_vehiculo
    MODIFY tipo VARCHAR(40) NOT NULL;
