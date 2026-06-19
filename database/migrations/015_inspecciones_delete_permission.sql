-- Permiso para eliminar inspecciones
USE sicv_cecyte_bcs;

INSERT IGNORE INTO permissions (slug, modulo, accion, descripcion) VALUES
('inspecciones.delete', 'inspecciones', 'delete', 'Eliminar inspecciones');

-- Admin general (1) y Admin transporte (2)
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions WHERE slug = 'inspecciones.delete';
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE slug = 'inspecciones.delete';
