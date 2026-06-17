-- SICV CECYTE BCS - Esquema completo v1.0
-- MySQL 8.0+ | UTF8MB4 | 3FN
--
-- IMPORTANTE: Si ya importaste este script y ves error #1050 "tabla ya existe",
-- NO vuelvas a ejecutarlo. La BD ya está lista.
-- Para reinstalar desde cero, ejecuta primero: 000_reset_database.sql
--
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS sicv_cecyte_bcs
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sicv_cecyte_bcs;

-- ===================== ROLES Y PERMISOS =====================
CREATE TABLE roles (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE permissions (
    id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(80) NOT NULL UNIQUE,
    modulo VARCHAR(50) NOT NULL,
    accion VARCHAR(20) NOT NULL,
    descripcion VARCHAR(200) NULL,
    INDEX idx_permissions_modulo (modulo)
) ENGINE=InnoDB;

CREATE TABLE role_permissions (
    role_id TINYINT UNSIGNED NOT NULL,
    permission_id SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===================== USUARIOS =====================
CREATE TABLE areas (
    id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id TINYINT UNSIGNED NOT NULL,
    area_id SMALLINT UNSIGNED NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido_paterno VARCHAR(100) NOT NULL,
    apellido_materno VARCHAR(100) NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    telefono VARCHAR(20) NULL,
    password_hash VARCHAR(255) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    bloqueado_hasta DATETIME NULL,
    intentos_fallidos TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ultimo_acceso DATETIME NULL,
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE SET NULL,
    INDEX idx_users_role (role_id),
    INDEX idx_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE user_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    session_token VARCHAR(128) NOT NULL UNIQUE,
    remember_token_hash VARCHAR(255) NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(500) NULL,
    ultimo_uso DATETIME NOT NULL,
    expira_en DATETIME NOT NULL,
    activa TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_sessions_user (user_id),
    INDEX idx_sessions_token (session_token)
) ENGINE=InnoDB;

CREATE TABLE access_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    email_intentado VARCHAR(150) NULL,
    exito TINYINT(1) NOT NULL,
    motivo VARCHAR(100) NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_access_logs_user (user_id),
    INDEX idx_access_logs_fecha (created_at)
) ENGINE=InnoDB;

CREATE TABLE password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expira_en DATETIME NOT NULL,
    usado TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_password_resets_token (token_hash)
) ENGINE=InnoDB;

-- ===================== PROVEEDORES =====================
CREATE TABLE proveedores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    razon_social VARCHAR(200) NOT NULL,
    rfc VARCHAR(13) NULL,
    telefono VARCHAR(20) NULL,
    email VARCHAR(150) NULL,
    direccion TEXT NULL,
    tipo ENUM('mantenimiento','combustible','ambos','otro') NOT NULL DEFAULT 'ambos',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ===================== VEHÍCULOS =====================
CREATE TABLE vehiculos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    numero_economico VARCHAR(30) NOT NULL UNIQUE,
    marca VARCHAR(80) NOT NULL,
    modelo VARCHAR(80) NOT NULL,
    version VARCHAR(80) NULL,
    anio SMALLINT UNSIGNED NOT NULL,
    color VARCHAR(50) NOT NULL,
    placas VARCHAR(20) NOT NULL UNIQUE,
    serie_vin CHAR(17) NOT NULL UNIQUE,
    motor VARCHAR(80) NULL,
    tipo_combustible ENUM('gasolina','diesel','hibrido','electrico','gnc') NOT NULL,
    capacidad_tanque DECIMAL(8,2) NOT NULL,
    kilometraje_actual INT UNSIGNED NOT NULL DEFAULT 0,
    area_id SMALLINT UNSIGNED NOT NULL,
    responsable_id INT UNSIGNED NOT NULL,
    fecha_adquisicion DATE NOT NULL,
    estado ENUM('activo','disponible','en_comision','en_mantenimiento','en_taller','fuera_servicio','baja') NOT NULL DEFAULT 'disponible',
    foto_principal VARCHAR(255) NULL,
    observaciones TEXT NULL,
    version_lock INT UNSIGNED NOT NULL DEFAULT 0,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    FOREIGN KEY (area_id) REFERENCES areas(id),
    FOREIGN KEY (responsable_id) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_vehiculos_estado (estado),
    INDEX idx_vehiculos_area (area_id),
    INDEX idx_vehiculos_responsable (responsable_id),
    INDEX idx_vehiculos_placas (placas)
) ENGINE=InnoDB;

CREATE TABLE vehiculo_fotos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id INT UNSIGNED NOT NULL,
    ruta VARCHAR(255) NOT NULL,
    descripcion VARCHAR(200) NULL,
    es_principal TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE,
    INDEX idx_vehiculo_fotos_vehiculo (vehiculo_id)
) ENGINE=InnoDB;

CREATE TABLE vehiculo_estado_historial (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id INT UNSIGNED NOT NULL,
    estado_anterior VARCHAR(30) NOT NULL,
    estado_nuevo VARCHAR(30) NOT NULL,
    motivo TEXT NULL,
    user_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===================== COMISIONES =====================
CREATE TABLE comisiones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(30) NOT NULL UNIQUE,
    vehiculo_id INT UNSIGNED NOT NULL,
    area_solicitante_id SMALLINT UNSIGNED NOT NULL,
    responsable_id INT UNSIGNED NOT NULL,
    conductor_nombre VARCHAR(200) NOT NULL,
    conductor_id INT UNSIGNED NULL,
    destino VARCHAR(300) NOT NULL,
    motivo TEXT NOT NULL,
    fecha DATE NOT NULL,
    hora_salida TIME NOT NULL,
    hora_regreso TIME NULL,
    km_salida INT UNSIGNED NOT NULL,
    km_regreso INT UNSIGNED NULL,
    combustible_salida DECIMAL(5,2) NOT NULL,
    combustible_regreso DECIMAL(5,2) NULL,
    km_recorridos INT UNSIGNED NULL,
    litros_consumidos DECIMAL(8,2) NULL,
    rendimiento DECIMAL(8,2) NULL,
    observaciones TEXT NULL,
    firma_digital VARCHAR(255) NULL,
    estado ENUM('borrador','en_curso','finalizada','cancelada') NOT NULL DEFAULT 'borrador',
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id),
    FOREIGN KEY (area_solicitante_id) REFERENCES areas(id),
    FOREIGN KEY (responsable_id) REFERENCES users(id),
    FOREIGN KEY (conductor_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_comisiones_vehiculo (vehiculo_id),
    INDEX idx_comisiones_fecha (fecha),
    INDEX idx_comisiones_estado (estado)
) ENGINE=InnoDB;

CREATE TABLE comision_fotos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    comision_id INT UNSIGNED NOT NULL,
    ruta VARCHAR(255) NOT NULL,
    tipo ENUM('salida','regreso','otro') NOT NULL DEFAULT 'otro',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (comision_id) REFERENCES comisiones(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===================== INSPECCIONES =====================
CREATE TABLE inspecciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id INT UNSIGNED NOT NULL,
    responsable_id INT UNSIGNED NOT NULL,
    kilometraje INT UNSIGNED NOT NULL,
    fecha DATE NOT NULL,
    observaciones_generales TEXT NULL,
    firma_digital VARCHAR(255) NULL,
    resultado_general ENUM('aprobada','condicionada','rechazada') NOT NULL DEFAULT 'aprobada',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id),
    FOREIGN KEY (responsable_id) REFERENCES users(id),
    INDEX idx_inspecciones_vehiculo (vehiculo_id),
    INDEX idx_inspecciones_fecha (fecha)
) ENGINE=InnoDB;

CREATE TABLE inspeccion_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inspeccion_id INT UNSIGNED NOT NULL,
    item_codigo VARCHAR(40) NOT NULL,
    item_nombre VARCHAR(100) NOT NULL,
    calificacion ENUM('bueno','regular','malo') NOT NULL,
    observaciones TEXT NULL,
    FOREIGN KEY (inspeccion_id) REFERENCES inspecciones(id) ON DELETE CASCADE,
    INDEX idx_inspeccion_items_inspeccion (inspeccion_id)
) ENGINE=InnoDB;

CREATE TABLE inspeccion_fotos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inspeccion_id INT UNSIGNED NOT NULL,
    item_codigo VARCHAR(40) NULL,
    ruta VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inspeccion_id) REFERENCES inspecciones(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE inspeccion_luces_tablero (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inspeccion_id INT UNSIGNED NOT NULL,
    luz_codigo VARCHAR(40) NOT NULL,
    FOREIGN KEY (inspeccion_id) REFERENCES inspecciones(id) ON DELETE CASCADE,
    INDEX idx_inspeccion_luces_inspeccion (inspeccion_id),
    UNIQUE KEY uq_inspeccion_luz (inspeccion_id, luz_codigo)
) ENGINE=InnoDB;

-- ===================== DAÑOS =====================
CREATE TABLE danios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id INT UNSIGNED NOT NULL,
    tipo_dano ENUM('golpe','rayon','cristal','defensa','faro','interior','llanta') NOT NULL,
    ubicacion VARCHAR(200) NOT NULL,
    descripcion TEXT NOT NULL,
    estado ENUM('reportado','en_evaluacion','en_reparacion','reparado','cerrado_sin_accion') NOT NULL DEFAULT 'reportado',
    reportado_por INT UNSIGNED NOT NULL,
    mantenimiento_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id),
    FOREIGN KEY (reportado_por) REFERENCES users(id),
    INDEX idx_danios_vehiculo (vehiculo_id),
    INDEX idx_danios_estado (estado)
) ENGINE=InnoDB;

CREATE TABLE danio_fotos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    danio_id INT UNSIGNED NOT NULL,
    ruta VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (danio_id) REFERENCES danios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE danio_seguimiento (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    danio_id INT UNSIGNED NOT NULL,
    estado_anterior VARCHAR(30) NOT NULL,
    estado_nuevo VARCHAR(30) NOT NULL,
    comentario TEXT NULL,
    user_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (danio_id) REFERENCES danios(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ===================== MANTENIMIENTO =====================
CREATE TABLE mantenimientos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(30) NOT NULL UNIQUE,
    vehiculo_id INT UNSIGNED NOT NULL,
    tipo ENUM('preventivo','correctivo','predictivo') NOT NULL,
    fecha DATE NOT NULL,
    kilometraje INT UNSIGNED NOT NULL,
    proveedor_id INT UNSIGNED NULL,
    descripcion TEXT NOT NULL,
    costo DECIMAL(12,2) NOT NULL DEFAULT 0,
    factura_ruta VARCHAR(255) NULL,
    xml_ruta VARCHAR(255) NULL,
    pdf_ruta VARCHAR(255) NULL,
    responsable_id INT UNSIGNED NOT NULL,
    autorizado_por INT UNSIGNED NULL,
    observaciones TEXT NULL,
    estado ENUM('pendiente','programado','autorizado','en_proceso','finalizado','cancelado') NOT NULL DEFAULT 'pendiente',
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id),
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE SET NULL,
    FOREIGN KEY (responsable_id) REFERENCES users(id),
    FOREIGN KEY (autorizado_por) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_mantenimientos_vehiculo (vehiculo_id),
    INDEX idx_mantenimientos_estado (estado)
) ENGINE=InnoDB;

CREATE TABLE mantenimiento_fotos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mantenimiento_id INT UNSIGNED NOT NULL,
    ruta VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mantenimiento_id) REFERENCES mantenimientos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- FK danios -> mantenimientos (deferred)
ALTER TABLE danios ADD FOREIGN KEY (mantenimiento_id) REFERENCES mantenimientos(id) ON DELETE SET NULL;

-- ===================== COMBUSTIBLE =====================
CREATE TABLE combustible_cargas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id INT UNSIGNED NOT NULL,
    proveedor_id INT UNSIGNED NULL,
    fecha DATE NOT NULL,
    litros DECIMAL(10,2) NOT NULL,
    importe DECIMAL(12,2) NOT NULL,
    kilometraje INT UNSIGNED NOT NULL,
    folio_ticket VARCHAR(100) NULL,
    factura_ruta VARCHAR(255) NULL,
    observaciones TEXT NULL,
    rendimiento DECIMAL(8,2) NULL,
    costo_por_km DECIMAL(10,4) NULL,
    registrado_por INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id),
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE SET NULL,
    FOREIGN KEY (registrado_por) REFERENCES users(id),
    INDEX idx_combustible_vehiculo (vehiculo_id),
    INDEX idx_combustible_fecha (fecha)
) ENGINE=InnoDB;

-- ===================== HERRAMIENTAS =====================
CREATE TABLE herramientas_vehiculo (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id INT UNSIGNED NOT NULL,
    tipo ENUM('gato','cruceta','extintor','botiquin','triangulos','linterna','llanta_refaccion') NOT NULL,
    estado ENUM('presente','ausente','danado','vencido') NOT NULL DEFAULT 'presente',
    foto_ruta VARCHAR(255) NULL,
    fecha_vencimiento DATE NULL,
    observaciones TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_herramienta_vehiculo (vehiculo_id, tipo),
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE herramienta_reposiciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    herramienta_id INT UNSIGNED NOT NULL,
    fecha DATE NOT NULL,
    estado_anterior VARCHAR(20) NOT NULL,
    estado_nuevo VARCHAR(20) NOT NULL,
    observaciones TEXT NULL,
    user_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (herramienta_id) REFERENCES herramientas_vehiculo(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ===================== DOCUMENTOS =====================
CREATE TABLE documentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id INT UNSIGNED NULL,
    user_id INT UNSIGNED NULL,
    tipo ENUM('factura','poliza','licencia','tarjeta_circulacion','verificacion','tenencia','otro') NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    numero_documento VARCHAR(100) NULL,
    fecha_emision DATE NULL,
    fecha_vencimiento DATE NULL,
    archivo_ruta VARCHAR(255) NOT NULL,
    archivo_tipo VARCHAR(50) NOT NULL,
    version SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    uploaded_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_documentos_vehiculo (vehiculo_id),
    INDEX idx_documentos_vencimiento (fecha_vencimiento),
    INDEX idx_documentos_tipo (tipo)
) ENGINE=InnoDB;

-- ===================== ALERTAS =====================
CREATE TABLE alerta_config (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    umbral_verde INT NOT NULL DEFAULT 500,
    umbral_amarillo INT NOT NULL DEFAULT 200,
    umbral_rojo INT NOT NULL DEFAULT 0,
    unidad ENUM('km','dias','litros') NOT NULL DEFAULT 'km',
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE alertas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id INT UNSIGNED NULL,
    documento_id INT UNSIGNED NULL,
    tipo VARCHAR(50) NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    mensaje TEXT NOT NULL,
    nivel ENUM('verde','amarillo','rojo') NOT NULL,
    atendida TINYINT(1) NOT NULL DEFAULT 0,
    atendida_por INT UNSIGNED NULL,
    atendida_en DATETIME NULL,
    comentario_atencion TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE,
    FOREIGN KEY (documento_id) REFERENCES documentos(id) ON DELETE SET NULL,
    FOREIGN KEY (atendida_por) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_alertas_nivel (nivel),
    INDEX idx_alertas_atendida (atendida),
    INDEX idx_alertas_vehiculo (vehiculo_id)
) ENGINE=InnoDB;

-- ===================== AUDITORÍA =====================
CREATE TABLE auditoria (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    accion VARCHAR(30) NOT NULL,
    tabla_afectada VARCHAR(80) NOT NULL,
    registro_id INT UNSIGNED NULL,
    valores_anteriores JSON NULL,
    valores_nuevos JSON NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_auditoria_tabla (tabla_afectada),
    INDEX idx_auditoria_user (user_id),
    INDEX idx_auditoria_fecha (created_at)
) ENGINE=InnoDB;

-- ===================== VISTAS REPORTES =====================
CREATE OR REPLACE VIEW v_vehiculos_resumen AS
SELECT
    v.id, v.numero_economico, v.marca, v.modelo, v.placas, v.estado,
    a.nombre AS area_nombre,
    CONCAT(u.nombre, ' ', u.apellido_paterno) AS responsable_nombre,
    v.kilometraje_actual
FROM vehiculos v
JOIN areas a ON a.id = v.area_id
JOIN users u ON u.id = v.responsable_id
WHERE v.deleted_at IS NULL;

CREATE OR REPLACE VIEW v_costos_vehiculo AS
SELECT
    v.id AS vehiculo_id,
    v.numero_economico,
    COALESCE(SUM(m.costo), 0) AS costo_mantenimiento,
    COALESCE((SELECT SUM(c.importe) FROM combustible_cargas c WHERE c.vehiculo_id = v.id), 0) AS costo_combustible,
    COALESCE(SUM(m.costo), 0) + COALESCE((SELECT SUM(c.importe) FROM combustible_cargas c WHERE c.vehiculo_id = v.id), 0) AS costo_total
FROM vehiculos v
LEFT JOIN mantenimientos m ON m.vehiculo_id = v.id AND m.estado = 'finalizado'
WHERE v.deleted_at IS NULL
GROUP BY v.id, v.numero_economico;

CREATE OR REPLACE VIEW v_documentos_por_vencer AS
SELECT d.*, v.numero_economico, v.placas,
    DATEDIFF(d.fecha_vencimiento, CURDATE()) AS dias_restantes
FROM documentos d
LEFT JOIN vehiculos v ON v.id = d.vehiculo_id
WHERE d.activo = 1 AND d.fecha_vencimiento IS NOT NULL
    AND d.fecha_vencimiento >= CURDATE();

-- ===================== TRIGGERS AUDITORÍA =====================
DELIMITER //

CREATE TRIGGER trg_vehiculos_update AFTER UPDATE ON vehiculos
FOR EACH ROW
BEGIN
    INSERT INTO auditoria (user_id, accion, tabla_afectada, registro_id, valores_anteriores, valores_nuevos, ip_address)
    VALUES (
        NEW.updated_by,
        'UPDATE',
        'vehiculos',
        NEW.id,
        JSON_OBJECT('estado', OLD.estado, 'kilometraje', OLD.kilometraje_actual),
        JSON_OBJECT('estado', NEW.estado, 'kilometraje', NEW.kilometraje_actual),
        COALESCE(@audit_ip, '0.0.0.0')
    );
END//

CREATE TRIGGER trg_users_update AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    IF OLD.password_hash != NEW.password_hash OR OLD.activo != NEW.activo THEN
        INSERT INTO auditoria (user_id, accion, tabla_afectada, registro_id, valores_anteriores, valores_nuevos, ip_address)
        VALUES (
            NEW.id,
            'UPDATE',
            'users',
            NEW.id,
            JSON_OBJECT('activo', OLD.activo),
            JSON_OBJECT('activo', NEW.activo),
            COALESCE(@audit_ip, '0.0.0.0')
        );
    END IF;
END//

DELIMITER ;

SET FOREIGN_KEY_CHECKS = 1;
