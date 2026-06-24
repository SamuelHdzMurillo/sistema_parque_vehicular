-- Permiso para eliminar mantenimientos
USE sicv_cecyte_bcs;

INSERT IGNORE INTO permissions (slug, modulo, accion, descripcion) VALUES
('mantenimiento.delete', 'mantenimiento', 'delete', 'Eliminar mantenimientos');

-- Admin general (1) y Admin transporte (2)
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions WHERE slug = 'mantenimiento.delete';
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE slug = 'mantenimiento.delete';
