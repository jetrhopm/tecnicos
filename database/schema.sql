CREATE DATABASE IF NOT EXISTS servicio_tecnico_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE servicio_tecnico_db;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS auditoria;
DROP TABLE IF EXISTS mensajes;
DROP TABLE IF EXISTS archivos;
DROP TABLE IF EXISTS agenda_eventos;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS sesiones;
DROP TABLE IF EXISTS garantias;
DROP TABLE IF EXISTS entregas;
DROP TABLE IF EXISTS pagos;
DROP TABLE IF EXISTS inventario_movimientos;
DROP TABLE IF EXISTS refacciones_ordenes;
DROP TABLE IF EXISTS refacciones;
DROP TABLE IF EXISTS proveedores;
DROP TABLE IF EXISTS reparacion_avances;
DROP TABLE IF EXISTS reparaciones;
DROP TABLE IF EXISTS cotizacion_items;
DROP TABLE IF EXISTS cotizaciones;
DROP TABLE IF EXISTS diagnosticos;
DROP TABLE IF EXISTS ordenes_servicio;
DROP TABLE IF EXISTS equipos;
DROP TABLE IF EXISTS clientes;
DROP TABLE IF EXISTS configuraciones;
DROP TABLE IF EXISTS user_roles;
DROP TABLE IF EXISTS role_permissions;
DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(40) NULL,
    status ENUM('activo','inactivo','bloqueado') NOT NULL DEFAULT 'activo',
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    label VARCHAR(120) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module VARCHAR(80) NOT NULL,
    action VARCHAR(80) NOT NULL,
    label VARCHAR(160) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_permission (module, action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE role_permissions (
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_rp_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_roles (
    user_id INT UNSIGNED NOT NULL,
    role_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id, role_id),
    CONSTRAINT fk_ur_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ur_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE configuraciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(120) NOT NULL UNIQUE,
    valor TEXT NULL,
    tipo VARCHAR(40) NOT NULL DEFAULT 'string',
    grupo VARCHAR(80) NOT NULL DEFAULT 'general',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE clientes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(190) NOT NULL,
    telefono VARCHAR(40) NOT NULL,
    whatsapp VARCHAR(40) NULL,
    email VARCHAR(190) NULL,
    domicilio VARCHAR(255) NULL,
    ciudad VARCHAR(120) NULL,
    estado VARCHAR(120) NULL,
    codigo_postal VARCHAR(20) NULL,
    rfc VARCHAR(20) NULL,
    notas_internas TEXT NULL,
    estatus ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    INDEX idx_clientes_nombre (nombre_completo),
    INDEX idx_clientes_telefono (telefono),
    INDEX idx_clientes_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE equipos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT UNSIGNED NOT NULL,
    tipo ENUM('celular','laptop','pc','consola','impresora','electrodomestico','herramienta','moto','otro') NOT NULL DEFAULT 'otro',
    marca VARCHAR(120) NULL,
    modelo VARCHAR(120) NULL,
    numero_serie VARCHAR(120) NULL,
    imei VARCHAR(80) NULL,
    color VARCHAR(80) NULL,
    password_equipo VARCHAR(120) NULL,
    accesorios_recibidos TEXT NULL,
    estado_fisico TEXT NULL,
    observaciones TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_equipos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    INDEX idx_equipos_cliente (cliente_id),
    INDEX idx_equipos_serie (numero_serie),
    INDEX idx_equipos_imei (imei)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ordenes_servicio (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(60) NOT NULL UNIQUE,
    cliente_id INT UNSIGNED NOT NULL,
    equipo_id INT UNSIGNED NOT NULL,
    tecnico_id INT UNSIGNED NULL,
    recibido_por INT UNSIGNED NOT NULL,
    tipo_servicio VARCHAR(120) NOT NULL,
    falla_reportada TEXT NOT NULL,
    diagnostico_inicial TEXT NULL,
    prioridad ENUM('baja','normal','alta','urgente') NOT NULL DEFAULT 'normal',
    estado ENUM('recibida','en_revision','diagnosticada','esperando_autorizacion','autorizada','rechazada','en_reparacion','esperando_refaccion','reparada','no_reparable','lista_para_entrega','entregada','cancelada','garantia') NOT NULL DEFAULT 'recibida',
    fecha_recepcion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_estimada_entrega DATETIME NULL,
    fecha_real_entrega DATETIME NULL,
    costo_estimado DECIMAL(12,2) NOT NULL DEFAULT 0,
    costo_final DECIMAL(12,2) NOT NULL DEFAULT 0,
    anticipo DECIMAL(12,2) NOT NULL DEFAULT 0,
    saldo_pendiente DECIMAL(12,2) NOT NULL DEFAULT 0,
    garantia_ofrecida VARCHAR(160) NULL,
    observaciones_internas TEXT NULL,
    observaciones_cliente TEXT NULL,
    codigo_entrega VARCHAR(80) NULL UNIQUE,
    ubicacion_actual VARCHAR(120) NOT NULL DEFAULT 'Recepcion',
    token_publico VARCHAR(120) NOT NULL,
    firma_recepcion TEXT NULL,
    firma_entrega TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_orden_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    CONSTRAINT fk_orden_equipo FOREIGN KEY (equipo_id) REFERENCES equipos(id),
    CONSTRAINT fk_orden_tecnico FOREIGN KEY (tecnico_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_orden_recibido FOREIGN KEY (recibido_por) REFERENCES users(id),
    INDEX idx_orden_folio (folio),
    INDEX idx_orden_cliente (cliente_id),
    INDEX idx_orden_equipo (equipo_id),
    INDEX idx_orden_estado (estado),
    INDEX idx_orden_codigo_entrega (codigo_entrega),
    INDEX idx_orden_tecnico (tecnico_id),
    INDEX idx_orden_fecha (fecha_recepcion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE entregas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    orden_id INT UNSIGNED NOT NULL,
    codigo_entrega VARCHAR(80) NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    recibido_por_nombre VARCHAR(190) NOT NULL,
    recibido_por_identificacion VARCHAR(120) NULL,
    saldo_antes DECIMAL(12,2) NOT NULL DEFAULT 0,
    pago_final DECIMAL(12,2) NOT NULL DEFAULT 0,
    metodo_pago ENUM('efectivo','transferencia','tarjeta','otro') NULL,
    referencia_pago VARCHAR(160) NULL,
    saldo_despues DECIMAL(12,2) NOT NULL DEFAULT 0,
    garantia_id INT UNSIGNED NULL,
    observaciones TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_entrega_orden FOREIGN KEY (orden_id) REFERENCES ordenes_servicio(id) ON DELETE CASCADE,
    CONSTRAINT fk_entrega_user FOREIGN KEY (usuario_id) REFERENCES users(id),
    INDEX idx_entrega_codigo (codigo_entrega),
    INDEX idx_entrega_fecha (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE diagnosticos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    orden_id INT UNSIGNED NOT NULL,
    tecnico_id INT UNSIGNED NOT NULL,
    diagnostico_tecnico TEXT NOT NULL,
    diagnostico_cliente TEXT NULL,
    causa_probable TEXT NULL,
    pruebas_realizadas TEXT NULL,
    piezas_necesarias TEXT NULL,
    tiempo_estimado VARCHAR(120) NULL,
    costo_mano_obra DECIMAL(12,2) NOT NULL DEFAULT 0,
    costo_refacciones DECIMAL(12,2) NOT NULL DEFAULT 0,
    costo_total_sugerido DECIMAL(12,2) NOT NULL DEFAULT 0,
    bloqueado TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_diag_orden FOREIGN KEY (orden_id) REFERENCES ordenes_servicio(id) ON DELETE CASCADE,
    CONSTRAINT fk_diag_tecnico FOREIGN KEY (tecnico_id) REFERENCES users(id),
    INDEX idx_diag_orden (orden_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cotizaciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    orden_id INT UNSIGNED NOT NULL,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    descuento DECIMAL(12,2) NOT NULL DEFAULT 0,
    iva DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    vigencia DATE NULL,
    terminos TEXT NULL,
    estado ENUM('pendiente','aceptada','rechazada','vencida') NOT NULL DEFAULT 'pendiente',
    motivo_rechazo TEXT NULL,
    aceptada_at DATETIME NULL,
    rechazada_at DATETIME NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cot_orden FOREIGN KEY (orden_id) REFERENCES ordenes_servicio(id) ON DELETE CASCADE,
    CONSTRAINT fk_cot_user FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_cot_orden (orden_id),
    INDEX idx_cot_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cotizacion_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cotizacion_id INT UNSIGNED NOT NULL,
    tipo ENUM('mano_obra','refaccion','servicio','otro') NOT NULL DEFAULT 'servicio',
    descripcion VARCHAR(255) NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(12,2) NOT NULL DEFAULT 0,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cot_item FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reparaciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    orden_id INT UNSIGNED NOT NULL,
    tecnico_id INT UNSIGNED NOT NULL,
    trabajo_realizado TEXT NOT NULL,
    piezas_instaladas TEXT NULL,
    pruebas_finales TEXT NULL,
    observaciones_internas TEXT NULL,
    observaciones_cliente TEXT NULL,
    resultado ENUM('reparado','no_reparable','requiere_mas_revision') NOT NULL DEFAULT 'reparado',
    fecha_inicio DATETIME NULL,
    fecha_fin DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_rep_orden FOREIGN KEY (orden_id) REFERENCES ordenes_servicio(id) ON DELETE CASCADE,
    CONSTRAINT fk_rep_tecnico FOREIGN KEY (tecnico_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reparacion_avances (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reparacion_id INT UNSIGNED NULL,
    orden_id INT UNSIGNED NOT NULL,
    tecnico_id INT UNSIGNED NOT NULL,
    descripcion TEXT NOT NULL,
    checklist JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_avance_rep FOREIGN KEY (reparacion_id) REFERENCES reparaciones(id) ON DELETE SET NULL,
    CONSTRAINT fk_avance_orden FOREIGN KEY (orden_id) REFERENCES ordenes_servicio(id) ON DELETE CASCADE,
    CONSTRAINT fk_avance_tecnico FOREIGN KEY (tecnico_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE proveedores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(190) NOT NULL,
    contacto VARCHAR(160) NULL,
    telefono VARCHAR(40) NULL,
    email VARCHAR(190) NULL,
    domicilio VARCHAR(255) NULL,
    sitio_web VARCHAR(190) NULL,
    notas TEXT NULL,
    estatus ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE refacciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    proveedor_id INT UNSIGNED NULL,
    nombre VARCHAR(190) NOT NULL,
    sku VARCHAR(100) NOT NULL UNIQUE,
    categoria VARCHAR(120) NULL,
    marca VARCHAR(120) NULL,
    modelo_compatible VARCHAR(160) NULL,
    costo DECIMAL(12,2) NOT NULL DEFAULT 0,
    precio_venta DECIMAL(12,2) NOT NULL DEFAULT 0,
    stock_actual INT NOT NULL DEFAULT 0,
    stock_minimo INT NOT NULL DEFAULT 0,
    ubicacion VARCHAR(120) NULL,
    estatus ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_ref_proveedor FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE SET NULL,
    INDEX idx_ref_stock (stock_actual, stock_minimo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE refacciones_ordenes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    orden_id INT UNSIGNED NOT NULL,
    refaccion_id INT UNSIGNED NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ro_orden FOREIGN KEY (orden_id) REFERENCES ordenes_servicio(id) ON DELETE CASCADE,
    CONSTRAINT fk_ro_ref FOREIGN KEY (refaccion_id) REFERENCES refacciones(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE inventario_movimientos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    refaccion_id INT UNSIGNED NOT NULL,
    orden_id INT UNSIGNED NULL,
    usuario_id INT UNSIGNED NOT NULL,
    tipo ENUM('entrada','salida','ajuste','cancelacion') NOT NULL,
    cantidad INT NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    costo_unitario DECIMAL(12,2) NULL,
    stock_anterior INT NOT NULL,
    stock_nuevo INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mov_ref FOREIGN KEY (refaccion_id) REFERENCES refacciones(id),
    CONSTRAINT fk_mov_orden FOREIGN KEY (orden_id) REFERENCES ordenes_servicio(id) ON DELETE SET NULL,
    CONSTRAINT fk_mov_user FOREIGN KEY (usuario_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pagos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    orden_id INT UNSIGNED NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    metodo ENUM('efectivo','transferencia','tarjeta','otro') NOT NULL DEFAULT 'efectivo',
    referencia VARCHAR(160) NULL,
    usuario_id INT UNSIGNED NOT NULL,
    notas TEXT NULL,
    comprobante_archivo_id INT UNSIGNED NULL,
    estado ENUM('activo','cancelado') NOT NULL DEFAULT 'activo',
    motivo_cancelacion TEXT NULL,
    cancelado_por INT UNSIGNED NULL,
    cancelado_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pago_orden FOREIGN KEY (orden_id) REFERENCES ordenes_servicio(id) ON DELETE CASCADE,
    CONSTRAINT fk_pago_user FOREIGN KEY (usuario_id) REFERENCES users(id),
    INDEX idx_pago_fecha (created_at),
    INDEX idx_pago_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE garantias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    orden_id INT UNSIGNED NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    condiciones TEXT NULL,
    estado ENUM('activa','vencida','aplicada','cancelada') NOT NULL DEFAULT 'activa',
    motivo TEXT NULL,
    resolucion TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_gar_orden FOREIGN KEY (orden_id) REFERENCES ordenes_servicio(id) ON DELETE CASCADE,
    INDEX idx_gar_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE archivos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entidad_tipo VARCHAR(80) NOT NULL,
    entidad_id INT UNSIGNED NOT NULL,
    categoria ENUM('recepcion','diagnostico','reparacion','entrega','garantia','pago','otro') NOT NULL DEFAULT 'otro',
    nombre_original VARCHAR(255) NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta VARCHAR(255) NOT NULL,
    mime VARCHAR(120) NOT NULL,
    tamano INT UNSIGNED NOT NULL,
    visible_cliente TINYINT(1) NOT NULL DEFAULT 0,
    uploaded_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_arch_entidad (entidad_tipo, entidad_id),
    CONSTRAINT fk_arch_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE pagos
    ADD CONSTRAINT fk_pago_comprobante FOREIGN KEY (comprobante_archivo_id) REFERENCES archivos(id) ON DELETE SET NULL;

CREATE TABLE mensajes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT UNSIGNED NULL,
    orden_id INT UNSIGNED NULL,
    canal ENUM('whatsapp','email','sms','llamada','manual') NOT NULL DEFAULT 'manual',
    plantilla VARCHAR(120) NULL,
    destinatario VARCHAR(190) NULL,
    mensaje TEXT NOT NULL,
    estado ENUM('preparado','enviado','fallido','registrado') NOT NULL DEFAULT 'registrado',
    usuario_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_msg_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
    CONSTRAINT fk_msg_orden FOREIGN KEY (orden_id) REFERENCES ordenes_servicio(id) ON DELETE SET NULL,
    CONSTRAINT fk_msg_user FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE auditoria (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NULL,
    accion VARCHAR(120) NOT NULL,
    modulo VARCHAR(120) NOT NULL,
    registro_id VARCHAR(80) NULL,
    datos_anteriores JSON NULL,
    datos_nuevos JSON NULL,
    ip VARCHAR(60) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_audit_modulo (modulo),
    INDEX idx_audit_fecha (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notificaciones (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    tipo VARCHAR(60) NOT NULL,
    titulo VARCHAR(160) NOT NULL,
    mensaje VARCHAR(255) NULL,
    url VARCHAR(255) NULL,
    leida TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notif_user (user_id, leida),
    INDEX idx_notif_fecha (created_at),
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE agenda_eventos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    orden_id INT UNSIGNED NULL,
    tecnico_id INT UNSIGNED NULL,
    titulo VARCHAR(190) NOT NULL,
    descripcion TEXT NULL,
    inicio DATETIME NOT NULL,
    fin DATETIME NULL,
    tipo ENUM('visita','entrega','recordatorio','trabajo','otro') NOT NULL DEFAULT 'otro',
    estado ENUM('programado','realizado','cancelado') NOT NULL DEFAULT 'programado',
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ag_orden FOREIGN KEY (orden_id) REFERENCES ordenes_servicio(id) ON DELETE SET NULL,
    CONSTRAINT fk_ag_tecnico FOREIGN KEY (tecnico_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_ag_user FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sesiones (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    ip VARCHAR(60) NULL,
    user_agent VARCHAR(255) NULL,
    payload LONGTEXT NULL,
    last_activity INT UNSIGNED NOT NULL,
    INDEX idx_ses_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    token VARCHAR(120) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pr_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
