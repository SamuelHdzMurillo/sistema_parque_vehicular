-- Permisos del módulo de catálogos (áreas, conductores, planteles)
USE sicv_cecyte_bcs;

INSERT IGNORE INTO permissions (slug, modulo, accion, descripcion) VALUES
('catalogos.read', 'catalogos', 'read', 'Ver catálogos'),
('catalogos.create', 'catalogos', 'create', 'Crear registros en catálogos'),
('catalogos.update', 'catalogos', 'update', 'Editar catálogos');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions WHERE modulo = 'catalogos';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE modulo = 'catalogos';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE slug IN ('catalogos.read', 'catalogos.update');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 4, id FROM permissions WHERE slug = 'catalogos.read';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 5, id FROM permissions WHERE slug = 'catalogos.read';
