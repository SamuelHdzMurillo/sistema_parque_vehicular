-- =====================================================================
-- SICV — Reinicio de datos operativos (conserva accesos)
-- =====================================================================
-- Base de datos: sicv_cecyte_bcs  (ver .env → DB_DATABASE)
--
-- CONSERVA (accesos y estructura de cuentas):
--   roles, permissions, role_permissions
--   users  (emails, contraseñas, roles asignados)
--   planteles, areas  (los usuarios referencian area_id)
--
-- ELIMINA (operación / transaccional):
--   vehículos, comisiones, inspecciones, daños, mantenimiento,
--   combustible, documentos, alertas generadas, auditoría, etc.
--
-- También cierra sesiones activas (habrá que volver a iniciar sesión).
--
-- ⚠️  NO ejecutes 000_reset_database.sql: ese script borra TODO.
-- =====================================================================

USE sicv_cecyte_bcs;

SET FOREIGN_KEY_CHECKS = 0;

-- --- Hijos primero (orden seguro aunque FK estén desactivadas) ---
TRUNCATE TABLE comision_luces_tablero;
TRUNCATE TABLE comision_niveles_liquidos;
TRUNCATE TABLE comision_fotos;
TRUNCATE TABLE comisiones;

TRUNCATE TABLE inspeccion_luces_tablero;
TRUNCATE TABLE inspeccion_fotos;
TRUNCATE TABLE inspeccion_items;
TRUNCATE TABLE inspecciones;

TRUNCATE TABLE danio_fotos;
TRUNCATE TABLE danio_seguimiento;
TRUNCATE TABLE danios;

TRUNCATE TABLE mantenimiento_fotos;
TRUNCATE TABLE mantenimientos;

TRUNCATE TABLE combustible_cargas;

TRUNCATE TABLE herramienta_reposiciones;
TRUNCATE TABLE vehiculo_fotos;
TRUNCATE TABLE vehiculo_estado_historial;
TRUNCATE TABLE herramientas_vehiculo;
TRUNCATE TABLE vehiculos;

TRUNCATE TABLE documentos;
TRUNCATE TABLE alertas;
TRUNCATE TABLE auditoria;

-- Catálogos operativos (no son credenciales)
TRUNCATE TABLE conductores;
TRUNCATE TABLE proveedores;

-- Sesiones y rastros de login (credenciales intactas)
TRUNCATE TABLE user_sessions;
TRUNCATE TABLE access_logs;
TRUNCATE TABLE password_resets;

-- --- Desbloqueo de cuentas por intentos fallidos ---
UPDATE users
SET intentos_fallidos = 0,
    bloqueado_hasta = NULL;

SET FOREIGN_KEY_CHECKS = 1;

-- Verificación rápida (opcional):
-- SELECT COUNT(*) AS usuarios FROM users;
-- SELECT COUNT(*) AS vehiculos FROM vehiculos;
