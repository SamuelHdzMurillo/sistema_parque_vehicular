-- Seeds iniciales SICV
USE sicv_cecyte_bcs;

INSERT INTO roles (slug, nombre, descripcion) VALUES
('admin_general', 'Administrador General', 'Acceso completo al sistema. Puede crear, editar y eliminar usuarios; consultar la auditoría de cambios; y gestionar todos los módulos: vehículos, comisiones, inspecciones, daños, mantenimiento, combustible, proveedores, herramientas, documentos, alertas y reportes. Rol de más alto nivel, destinado a dirección general o responsable de TI.'),
('admin_transporte', 'Administrador de Transporte', 'Gestión operativa integral del parque vehicular. Puede administrar vehículos, comisiones, inspecciones, daños, mantenimiento, combustible, proveedores, herramientas y documentación; configurar alertas y exportar reportes. No puede crear, modificar ni eliminar usuarios del sistema ni consultar la auditoría de cuentas.'),
('supervisor', 'Supervisor', 'Supervisa y autoriza la operación del parque. Puede consultar y actualizar información en la mayoría de módulos, autorizar comisiones y mantenimientos, y exportar reportes. No crea ni elimina registros operativos ni gestiona usuarios. Ideal para jefes de área o coordinadores de transporte.'),
('responsable_vehiculo', 'Responsable de Vehículo', 'Operación diaria de las unidades asignadas. Puede registrar comisiones, inspecciones, reportes de daños y cargas de combustible; actualizar el estado de sus vehículos y herramientas; y consultar expedientes, alertas y reportes de sus unidades. No autoriza comisiones ni mantenimientos ni accede a la configuración del sistema.'),
('consulta', 'Consulta', 'Acceso de solo lectura. Puede consultar vehículos, expedientes, comisiones, inspecciones, mantenimiento, combustible y alertas; ver el panel principal y exportar reportes informativos. No puede crear, editar ni eliminar ningún registro. Pensado para personal administrativo o consulta externa.');

INSERT INTO permissions (slug, modulo, accion, descripcion) VALUES
('usuarios.read', 'usuarios', 'read', 'Ver usuarios'),
('usuarios.create', 'usuarios', 'create', 'Crear usuarios'),
('usuarios.update', 'usuarios', 'update', 'Editar usuarios'),
('usuarios.delete', 'usuarios', 'delete', 'Eliminar usuarios'),
('vehiculos.read', 'vehiculos', 'read', 'Ver vehículos'),
('vehiculos.create', 'vehiculos', 'create', 'Crear vehículos'),
('vehiculos.update', 'vehiculos', 'update', 'Editar vehículos'),
('vehiculos.delete', 'vehiculos', 'delete', 'Dar de baja vehículos'),
('expediente.read', 'expediente', 'read', 'Ver expediente digital'),
('comisiones.read', 'comisiones', 'read', 'Ver comisiones'),
('comisiones.create', 'comisiones', 'create', 'Crear comisiones'),
('comisiones.update', 'comisiones', 'update', 'Editar comisiones'),
('comisiones.delete', 'comisiones', 'delete', 'Cancelar comisiones'),
('comisiones.authorize', 'comisiones', 'authorize', 'Autorizar comisiones'),
('inspecciones.read', 'inspecciones', 'read', 'Ver inspecciones'),
('inspecciones.create', 'inspecciones', 'create', 'Crear inspecciones'),
('inspecciones.update', 'inspecciones', 'update', 'Editar inspecciones'),
('danios.read', 'danios', 'read', 'Ver daños'),
('danios.create', 'danios', 'create', 'Reportar daños'),
('danios.update', 'danios', 'update', 'Actualizar daños'),
('mantenimiento.read', 'mantenimiento', 'read', 'Ver mantenimiento'),
('mantenimiento.create', 'mantenimiento', 'create', 'Crear mantenimiento'),
('mantenimiento.update', 'mantenimiento', 'update', 'Editar mantenimiento'),
('mantenimiento.authorize', 'mantenimiento', 'authorize', 'Autorizar mantenimiento'),
('proveedores.read', 'proveedores', 'read', 'Ver proveedores'),
('proveedores.create', 'proveedores', 'create', 'Crear proveedores'),
('proveedores.update', 'proveedores', 'update', 'Editar proveedores'),
('combustible.read', 'combustible', 'read', 'Ver combustible'),
('combustible.create', 'combustible', 'create', 'Registrar cargas'),
('combustible.update', 'combustible', 'update', 'Editar cargas'),
('herramientas.read', 'herramientas', 'read', 'Ver herramientas'),
('herramientas.update', 'herramientas', 'update', 'Actualizar herramientas'),
('documentos.read', 'documentos', 'read', 'Ver documentos'),
('documentos.create', 'documentos', 'create', 'Subir documentos'),
('documentos.update', 'documentos', 'update', 'Actualizar documentos'),
('alertas.read', 'alertas', 'read', 'Ver alertas'),
('alertas.config', 'alertas', 'config', 'Configurar alertas'),
('dashboard.read', 'dashboard', 'read', 'Ver dashboard'),
('reportes.export', 'reportes', 'export', 'Exportar reportes'),
('auditoria.read', 'auditoria', 'read', 'Ver auditoría');

-- Admin general: todos los permisos
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions;

-- Admin transporte
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE slug NOT IN ('usuarios.create','usuarios.update','usuarios.delete','auditoria.read');

-- Supervisor
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE accion IN ('read','update','authorize') OR slug = 'reportes.export';

-- Responsable vehículo
INSERT INTO role_permissions (role_id, permission_id)
SELECT 4, id FROM permissions WHERE slug IN (
    'vehiculos.read','vehiculos.update','expediente.read',
    'comisiones.read','comisiones.create','comisiones.update',
    'inspecciones.read','inspecciones.create','inspecciones.update',
    'danios.read','danios.create','danios.update',
    'mantenimiento.read','combustible.read','combustible.create','combustible.update',
    'proveedores.read',
    'herramientas.read','herramientas.update','documentos.read',
    'alertas.read','dashboard.read','reportes.export'
);

-- Consulta
INSERT INTO role_permissions (role_id, permission_id)
SELECT 5, id FROM permissions WHERE accion = 'read' OR slug IN ('expediente.read','dashboard.read','reportes.export');

INSERT INTO areas (clave, nombre) VALUES
('DG', 'Dirección General'),
('TRANS', 'Departamento de Transporte'),
('PLANTEL-LP', 'Plantel La Paz'),
('PLANTEL-CAB', 'Plantel Cabo San Lucas'),
('SERV-GEN', 'Servicios Generales');

INSERT INTO alerta_config (tipo, nombre, umbral_verde, umbral_amarillo, umbral_rojo, unidad) VALUES
('cambio_aceite', 'Cambio de aceite', 5000, 2000, 500, 'km'),
('afinacion', 'Afinación', 20000, 5000, 1000, 'km'),
('llantas', 'Revisión de llantas', 40000, 10000, 2000, 'km'),
('bateria', 'Batería', 730, 365, 90, 'dias'),
('seguro', 'Póliza de seguro', 60, 30, 0, 'dias'),
('tenencia', 'Tenencia', 60, 30, 0, 'dias'),
('verificacion', 'Verificación vehicular', 60, 30, 0, 'dias'),
('licencia', 'Licencia de conductor', 60, 30, 0, 'dias');

-- Password: Admin123!
INSERT INTO users (role_id, area_id, nombre, apellido_paterno, apellido_materno, email, telefono, password_hash, activo) VALUES
(1, 1, 'Administrador', 'Sistema', 'SICV', 'admin@cecytebcs.edu.mx', '6121234567', '$2y$10$CAMUM/AOqSlJTZwOY3glH.siSsRFemZUAHGgVl.47/.f7SSk9NkQ6', 1),
(2, 2, 'Carlos', 'Mendoza', 'Ruiz', 'transporte@cecytebcs.edu.mx', '6127654321', '$2y$10$CAMUM/AOqSlJTZwOY3glH.siSsRFemZUAHGgVl.47/.f7SSk9NkQ6', 1),
(3, 2, 'Ana', 'Torres', 'Vega', 'supervisor@cecytebcs.edu.mx', '6121112233', '$2y$10$CAMUM/AOqSlJTZwOY3glH.siSsRFemZUAHGgVl.47/.f7SSk9NkQ6', 1),
(4, 3, 'Juan', 'Pérez', 'López', 'responsable@cecytebcs.edu.mx', '6124445566', '$2y$10$CAMUM/AOqSlJTZwOY3glH.siSsRFemZUAHGgVl.47/.f7SSk9NkQ6', 1);

INSERT INTO proveedores (razon_social, rfc, telefono, tipo) VALUES
('Taller Automotriz del Pacífico', 'TAP850101ABC', '6123001100', 'mantenimiento'),
('Gasolinera Pemex Centro', 'GPC900202XYZ', '6123002200', 'combustible'),
('Refacciones BCS SA de CV', 'RBS950303DEF', '6123003300', 'ambos');

INSERT INTO vehiculos (numero_economico, marca, modelo, version, anio, color, placas, serie_vin, motor, tipo_combustible, capacidad_tanque, kilometraje_actual, area_id, responsable_id, fecha_adquisicion, estado, created_by) VALUES
('VEH-001', 'Nissan', 'NP300', 'SE', 2022, 'Blanco', 'ABC123A', '1N6AD0EV5NN123456', '2.5L', 'gasolina', 80.00, 45230, 3, 4, '2022-03-15', 'disponible', 1),
('VEH-002', 'Toyota', 'Hilux', 'SR', 2021, 'Gris', 'XYZ789B', '5TFJX4GN2MX654321', '2.7L', 'gasolina', 80.00, 67890, 4, 4, '2021-06-20', 'activo', 1),
('VEH-003', 'Chevrolet', 'Aveo', 'LT', 2020, 'Rojo', 'DEF456C', '3G1TC5CF0LL789012', '1.6L', 'gasolina', 45.00, 89120, 5, 4, '2020-01-10', 'en_mantenimiento', 1);

INSERT INTO herramientas_vehiculo (vehiculo_id, tipo, estado) VALUES
(1, 'gato', 'presente'), (1, 'cruceta', 'presente'), (1, 'extintor', 'presente'),
(1, 'botiquin', 'presente'), (1, 'triangulos', 'presente'), (1, 'linterna', 'presente'), (1, 'llanta_refaccion', 'presente'),
(2, 'gato', 'presente'), (2, 'cruceta', 'presente'), (2, 'extintor', 'vencido'), (2, 'botiquin', 'presente'),
(2, 'triangulos', 'presente'), (2, 'linterna', 'danado'), (2, 'llanta_refaccion', 'presente'),
(3, 'gato', 'presente'), (3, 'cruceta', 'ausente'), (3, 'extintor', 'presente'), (3, 'botiquin', 'presente'),
(3, 'triangulos', 'presente'), (3, 'linterna', 'presente'), (3, 'llanta_refaccion', 'presente');

INSERT INTO documentos (vehiculo_id, tipo, titulo, numero_documento, fecha_emision, fecha_vencimiento, archivo_ruta, archivo_tipo, uploaded_by) VALUES
(1, 'poliza', 'Seguro VEH-001', 'POL-2025-001', '2025-01-01', '2026-12-31', 'docs/demo_poliza.pdf', 'application/pdf', 1),
(1, 'tarjeta_circulacion', 'Tarjeta circulación VEH-001', 'TC-001', '2025-01-01', '2026-06-30', 'docs/demo_tarjeta.pdf', 'application/pdf', 1),
(2, 'verificacion', 'Verificación VEH-002', 'VER-002', '2025-03-01', '2025-08-15', 'docs/demo_verificacion.pdf', 'application/pdf', 1);

INSERT INTO combustible_cargas (vehiculo_id, proveedor_id, fecha, litros, importe, kilometraje, rendimiento, costo_por_km, registrado_por) VALUES
(1, 2, '2025-05-01', 45.00, 1125.00, 44000, 10.50, 2.38, 2),
(1, 2, '2025-05-15', 50.00, 1250.00, 44525, 10.50, 2.38, 2),
(2, 2, '2025-05-10', 60.00, 1500.00, 67000, 9.80, 2.55, 2);

INSERT INTO mantenimientos (folio, vehiculo_id, tipo, fecha, kilometraje, proveedor_id, descripcion, costo, responsable_id, estado, created_by) VALUES
('MNT-2025-001', 3, 'correctivo', '2025-06-01', 89120, 1, 'Reparación de suspensión delantera', 8500.00, 2, 'en_proceso', 2),
('MNT-2025-002', 1, 'preventivo', '2025-04-01', 42000, 1, 'Cambio de aceite y filtros', 1200.00, 2, 'finalizado', 2);

INSERT INTO alertas (vehiculo_id, tipo, titulo, mensaje, nivel) VALUES
(2, 'verificacion', 'Verificación por vencer', 'La verificación del VEH-002 vence en menos de 60 días', 'amarillo'),
(3, 'cambio_aceite', 'Cambio de aceite pendiente', 'El VEH-003 requiere cambio de aceite pronto', 'rojo');
