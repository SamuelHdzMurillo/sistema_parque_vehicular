-- Permisos del módulo de proveedores
USE sicv_cecyte_bcs;

INSERT IGNORE INTO permissions (slug, modulo, accion, descripcion) VALUES
('proveedores.read', 'proveedores', 'read', 'Ver proveedores'),
('proveedores.create', 'proveedores', 'create', 'Crear proveedores'),
('proveedores.update', 'proveedores', 'update', 'Editar proveedores');

-- Admin general (1) y Admin transporte (2): acceso completo
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions WHERE modulo = 'proveedores';
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE modulo = 'proveedores';

-- Supervisor (3): ver y editar
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE slug IN ('proveedores.read', 'proveedores.update');

-- Responsable vehículo (4) y Consulta (5): solo lectura
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 4, id FROM permissions WHERE slug = 'proveedores.read';
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 5, id FROM permissions WHERE slug = 'proveedores.read';
