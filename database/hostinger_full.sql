-- Sistema Web de Gestion de Servicios Tecnicos y Reparaciones
-- SQL completo para Hostinger / hosting compartido
-- Importar dentro de la base de datos creada desde el panel de Hostinger.
-- No crea ni selecciona base de datos.
-- Charset recomendado: utf8mb4 (UTF-8 completo para MySQL).

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET collation_connection = 'utf8mb4_unicode_ci';
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
DROP TABLE IF EXISTS caja_movimientos;
DROP TABLE IF EXISTS caja_turnos;
DROP TABLE IF EXISTS inventario_movimientos;
DROP TABLE IF EXISTS venta_refaccion_items;
DROP TABLE IF EXISTS ventas_refacciones;
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
    refaccion_id INT UNSIGNED NULL,
    descripcion VARCHAR(255) NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL DEFAULT 1,
    costo_unitario DECIMAL(12,2) NOT NULL DEFAULT 0,
    precio_unitario DECIMAL(12,2) NOT NULL DEFAULT 0,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cot_item FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id) ON DELETE CASCADE,
    INDEX idx_cot_item_refaccion (refaccion_id)
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

ALTER TABLE cotizacion_items
    ADD CONSTRAINT fk_cot_item_refaccion FOREIGN KEY (refaccion_id) REFERENCES refacciones(id) ON DELETE SET NULL;

CREATE TABLE refacciones_ordenes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    orden_id INT UNSIGNED NOT NULL,
    refaccion_id INT UNSIGNED NOT NULL,
    cotizacion_item_id INT UNSIGNED NULL,
    cantidad INT NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(12,2) NOT NULL DEFAULT 0,
    estado ENUM('activa','cancelada') NOT NULL DEFAULT 'activa',
    motivo_cancelacion TEXT NULL,
    cancelado_por INT UNSIGNED NULL,
    cancelado_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ro_orden FOREIGN KEY (orden_id) REFERENCES ordenes_servicio(id) ON DELETE CASCADE,
    CONSTRAINT fk_ro_ref FOREIGN KEY (refaccion_id) REFERENCES refacciones(id),
    CONSTRAINT fk_ro_cot_item FOREIGN KEY (cotizacion_item_id) REFERENCES cotizacion_items(id) ON DELETE SET NULL,
    CONSTRAINT fk_ro_cancel_user FOREIGN KEY (cancelado_por) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_ro_estado (estado),
    UNIQUE KEY uq_ro_cot_item (cotizacion_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ventas_refacciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(60) NOT NULL UNIQUE,
    cliente_nombre VARCHAR(190) NULL,
    cliente_telefono VARCHAR(40) NULL,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    descuento DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    metodo_pago ENUM('efectivo','transferencia','tarjeta','otro') NOT NULL DEFAULT 'efectivo',
    referencia VARCHAR(160) NULL,
    notas TEXT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    estado ENUM('activa','cancelada') NOT NULL DEFAULT 'activa',
    motivo_cancelacion TEXT NULL,
    cancelado_por INT UNSIGNED NULL,
    cancelado_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_vr_user FOREIGN KEY (usuario_id) REFERENCES users(id),
    CONSTRAINT fk_vr_cancel_user FOREIGN KEY (cancelado_por) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_vr_fecha (created_at),
    INDEX idx_vr_usuario (usuario_id),
    INDEX idx_vr_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE venta_refaccion_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    venta_id INT UNSIGNED NOT NULL,
    refaccion_id INT UNSIGNED NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    sku VARCHAR(100) NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    costo_unitario DECIMAL(12,2) NOT NULL DEFAULT 0,
    precio_unitario DECIMAL(12,2) NOT NULL DEFAULT 0,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_vri_venta FOREIGN KEY (venta_id) REFERENCES ventas_refacciones(id) ON DELETE CASCADE,
    CONSTRAINT fk_vri_ref FOREIGN KEY (refaccion_id) REFERENCES refacciones(id),
    INDEX idx_vri_refaccion (refaccion_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE caja_turnos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(60) NOT NULL UNIQUE,
    estado ENUM('abierto','cerrado') NOT NULL DEFAULT 'abierto',
    fondo_inicial DECIMAL(12,2) NOT NULL DEFAULT 0,
    efectivo_contado DECIMAL(12,2) NOT NULL DEFAULT 0,
    transferencia_contado DECIMAL(12,2) NOT NULL DEFAULT 0,
    tarjeta_contado DECIMAL(12,2) NOT NULL DEFAULT 0,
    otro_contado DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_esperado DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_contado DECIMAL(12,2) NOT NULL DEFAULT 0,
    diferencia DECIMAL(12,2) NOT NULL DEFAULT 0,
    abierto_por INT UNSIGNED NOT NULL,
    cerrado_por INT UNSIGNED NULL,
    opened_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at DATETIME NULL,
    observaciones TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_caja_turno_abre FOREIGN KEY (abierto_por) REFERENCES users(id),
    CONSTRAINT fk_caja_turno_cierra FOREIGN KEY (cerrado_por) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_caja_turno_estado (estado),
    INDEX idx_caja_turno_opened (opened_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE caja_movimientos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    turno_id INT UNSIGNED NOT NULL,
    tipo ENUM('retiro') NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    metodo ENUM('efectivo','transferencia','tarjeta','otro') NOT NULL DEFAULT 'efectivo',
    concepto VARCHAR(255) NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_caja_mov_turno FOREIGN KEY (turno_id) REFERENCES caja_turnos(id) ON DELETE CASCADE,
    CONSTRAINT fk_caja_mov_user FOREIGN KEY (usuario_id) REFERENCES users(id),
    INDEX idx_caja_mov_turno (turno_id),
    INDEX idx_caja_mov_fecha (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE inventario_movimientos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    refaccion_id INT UNSIGNED NOT NULL,
    orden_id INT UNSIGNED NULL,
    venta_refaccion_id INT UNSIGNED NULL,
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
    CONSTRAINT fk_mov_venta_ref FOREIGN KEY (venta_refaccion_id) REFERENCES ventas_refacciones(id) ON DELETE SET NULL,
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


-- Datos iniciales, roles, permisos y usuarios demo

INSERT INTO roles (name, label) VALUES
('superadmin', 'Super administrador'),
('admin', 'Administrador'),
('recepcion', 'Recepcion'),
('tecnico', 'Tecnico'),
('tecnico_senior', 'Tecnico senior'),
('almacen', 'Almacen'),
('caja', 'Caja'),
('cliente_consulta', 'Cliente consulta')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO permissions (module, action, label)
SELECT m.module, a.action, CONCAT(a.action, ' ', m.module)
FROM (
    SELECT 'dashboard' module UNION SELECT 'clientes' UNION SELECT 'equipos' UNION SELECT 'ordenes'
    UNION SELECT 'diagnosticos' UNION SELECT 'cotizaciones' UNION SELECT 'reparaciones'
    UNION SELECT 'inventario' UNION SELECT 'proveedores' UNION SELECT 'pagos' UNION SELECT 'punto_venta' UNION SELECT 'caja' UNION SELECT 'garantias'
    UNION SELECT 'agenda' UNION SELECT 'mensajes' UNION SELECT 'reportes' UNION SELECT 'configuracion'
    UNION SELECT 'usuarios' UNION SELECT 'auditoria'
) m
CROSS JOIN (
    SELECT 'ver' action UNION SELECT 'crear' UNION SELECT 'editar' UNION SELECT 'eliminar'
    UNION SELECT 'autorizar' UNION SELECT 'cambiar_estado' UNION SELECT 'exportar'
    UNION SELECT 'imprimir' UNION SELECT 'administrar'
) a
WHERE 1 = 1
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.name = 'superadmin'
ON DUPLICATE KEY UPDATE role_id = role_id;

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name = 'admin' AND p.module NOT IN ('usuarios') AND p.action <> 'eliminar'
ON DUPLICATE KEY UPDATE role_id = role_id;

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name = 'recepcion'
AND ((p.module IN ('dashboard','clientes','equipos','ordenes','agenda','mensajes') AND p.action IN ('ver','crear','editar','imprimir'))
OR (p.module = 'pagos' AND p.action IN ('ver','crear')))
ON DUPLICATE KEY UPDATE role_id = role_id;

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name = 'recepcion' AND p.module = 'punto_venta' AND p.action IN ('ver','crear','imprimir')
ON DUPLICATE KEY UPDATE role_id = role_id;

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name = 'tecnico'
AND p.module IN ('dashboard','ordenes','diagnosticos','reparaciones','agenda','mensajes')
AND p.action IN ('ver','crear','editar','cambiar_estado','imprimir')
ON DUPLICATE KEY UPDATE role_id = role_id;

-- El tecnico normal puede preparar cotizaciones, pero no autorizarlas.
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name = 'tecnico'
AND p.module = 'cotizaciones'
AND p.action IN ('ver','crear')
ON DUPLICATE KEY UPDATE role_id = role_id;

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name = 'tecnico_senior'
AND p.module IN ('dashboard','ordenes','diagnosticos','cotizaciones','reparaciones','inventario','agenda','mensajes')
AND p.action IN ('ver','crear','editar','autorizar','cambiar_estado','imprimir')
ON DUPLICATE KEY UPDATE role_id = role_id;

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name = 'almacen' AND p.module IN ('dashboard','inventario','proveedores','ordenes','agenda') AND p.action IN ('ver','crear','editar','exportar')
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Acceso al modulo de almacen (inventario y proveedores) para tecnicos y recepcion.
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name IN ('tecnico','tecnico_senior','recepcion') AND p.module IN ('inventario','proveedores') AND p.action IN ('ver','crear','editar')
ON DUPLICATE KEY UPDATE role_id = role_id;

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name = 'caja' AND p.module IN ('dashboard','pagos','ordenes','agenda','punto_venta') AND p.action IN ('ver','crear','editar','imprimir','exportar')
ON DUPLICATE KEY UPDATE role_id = role_id;

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name = 'caja' AND p.module = 'caja' AND p.action IN ('ver','editar','imprimir')
ON DUPLICATE KEY UPDATE role_id = role_id;

INSERT INTO users (name, email, password, status)
VALUES ('Administrador Local', 'admin@local.test', '$2y$10$T2vxiuHA0r2c/LZwQmt2JOyPtFDO7X8jZYOuSMUkgF6/T.F28SAHS', 'activo')
ON DUPLICATE KEY UPDATE name = VALUES(name), password = VALUES(password), status = VALUES(status);

INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u JOIN roles r ON r.name = 'superadmin' WHERE u.email = 'admin@local.test'
ON DUPLICATE KEY UPDATE user_id = user_id;

INSERT INTO users (name, email, password, phone, status) VALUES
('Superadmin Demo', 'superadmin@local.test', '$2y$10$T2vxiuHA0r2c/LZwQmt2JOyPtFDO7X8jZYOuSMUkgF6/T.F28SAHS', '5550000001', 'activo'),
('Administrador Demo', 'administrador@local.test', '$2y$10$T2vxiuHA0r2c/LZwQmt2JOyPtFDO7X8jZYOuSMUkgF6/T.F28SAHS', '5550000002', 'activo'),
('Recepcion Demo', 'recepcion@local.test', '$2y$10$T2vxiuHA0r2c/LZwQmt2JOyPtFDO7X8jZYOuSMUkgF6/T.F28SAHS', '5550000003', 'activo'),
('Tecnico Demo', 'tecnico@local.test', '$2y$10$T2vxiuHA0r2c/LZwQmt2JOyPtFDO7X8jZYOuSMUkgF6/T.F28SAHS', '5550000004', 'activo'),
('Tecnico Senior Demo', 'tecnico_senior@local.test', '$2y$10$T2vxiuHA0r2c/LZwQmt2JOyPtFDO7X8jZYOuSMUkgF6/T.F28SAHS', '5550000005', 'activo'),
('Almacen Demo', 'almacen@local.test', '$2y$10$T2vxiuHA0r2c/LZwQmt2JOyPtFDO7X8jZYOuSMUkgF6/T.F28SAHS', '5550000006', 'activo'),
('Caja Demo', 'caja@local.test', '$2y$10$T2vxiuHA0r2c/LZwQmt2JOyPtFDO7X8jZYOuSMUkgF6/T.F28SAHS', '5550000007', 'activo'),
('Cliente Consulta Demo', 'cliente_consulta@local.test', '$2y$10$T2vxiuHA0r2c/LZwQmt2JOyPtFDO7X8jZYOuSMUkgF6/T.F28SAHS', '5550000008', 'activo')
ON DUPLICATE KEY UPDATE name = VALUES(name), password = VALUES(password), phone = VALUES(phone), status = VALUES(status);

INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u
JOIN roles r ON r.name = CASE u.email
    WHEN 'superadmin@local.test' THEN 'superadmin'
    WHEN 'administrador@local.test' THEN 'admin'
    WHEN 'recepcion@local.test' THEN 'recepcion'
    WHEN 'tecnico@local.test' THEN 'tecnico'
    WHEN 'tecnico_senior@local.test' THEN 'tecnico_senior'
    WHEN 'almacen@local.test' THEN 'almacen'
    WHEN 'caja@local.test' THEN 'caja'
    WHEN 'cliente_consulta@local.test' THEN 'cliente_consulta'
END
WHERE u.email IN (
    'superadmin@local.test',
    'administrador@local.test',
    'recepcion@local.test',
    'tecnico@local.test',
    'tecnico_senior@local.test',
    'almacen@local.test',
    'caja@local.test',
    'cliente_consulta@local.test'
)
ON DUPLICATE KEY UPDATE user_id = user_id;

INSERT INTO configuraciones (clave, valor, tipo, grupo) VALUES
('negocio.nombre', 'Servicio Tecnico', 'string', 'negocio'),
('negocio.telefono', '', 'string', 'negocio'),
('negocio.whatsapp', '', 'string', 'negocio'),
('negocio.email', '', 'string', 'negocio'),
('negocio.direccion', '', 'string', 'negocio'),
('negocio.logo_url', '', 'string', 'negocio'),
('sistema.nombre', 'Sistema Web de Gestión de Servicios Técnicos', 'string', 'sistema'),
('sistema.moneda', 'MXN', 'string', 'sistema'),
('sistema.iva_activo', '0', 'bool', 'sistema'),
('sistema.iva_porcentaje', '16', 'number', 'sistema'),
('ordenes.prefijo_folio', 'ST', 'string', 'ordenes'),
('ordenes.garantia_default', '30 días naturales sobre la reparación realizada', 'string', 'ordenes'),
('garantia.dias_default', '30', 'number', 'garantia'),
('ticket.garantia', '1. El taller no se responsabiliza por pérdida o extravío de equipos que no sean retirados dentro de los 90 días naturales posteriores a la fecha de ingreso.\n2. La garantía es de 30 días naturales a partir de la fecha de reparación y aplica únicamente sobre la falla o servicio realizado.\n3. Para retirar el equipo es indispensable presentar la orden de servicio.\n4. Al firmar la orden, el cliente acepta las condiciones físicas y de funcionamiento en las que se recibe el equipo.\n5. La garantía será válida siempre que el sello de garantía permanezca intacto y el equipo no haya sido manipulado o revisado por terceros.\n6. No cuentan con garantía los equipos mojados, golpeados, con pantalla dañada o con falla en flex.\n7. No cuentan con garantía los equipos afectados por variaciones de voltaje.\n8. Las reparaciones o servicios de software no cuentan con garantía.\n9. Todo servicio o actualización de software se realiza bajo autorización y riesgo del cliente.\n10. No se realizan reembolsos bajo ningún concepto.', 'text', 'ticket'),
('archivos.max_mb', '8', 'number', 'archivos'),
('whatsapp.orden_recibida', 'Hola {cliente}, tu orden {folio} para el equipo {equipo} fue recibida correctamente. Puedes consultar el avance aqui: {link}', 'text', 'plantillas'),
('whatsapp.diagnostico_listo', 'Hola {cliente}, tenemos lista la cotizacion de tu orden {folio} ({equipo}). Necesitamos tu validacion para continuar con la reparacion. Revisala y autorizala aqui: {link}', 'text', 'plantillas'),
('whatsapp.equipo_listo', 'Hola {cliente}, tu {equipo} de la orden {folio} ya esta listo para entrega. Saldo por pagar: {saldo}. Puedes pasar a recogerlo. Gracias.', 'text', 'plantillas'),
('whatsapp.entregado', 'Hola {cliente}, gracias por tu preferencia. Tu {equipo} de la orden {folio} fue entregado. Conserva tu comprobante para hacer valida la garantia. Estamos para servirte, que tengas excelente dia.', 'text', 'plantillas'),
('whatsapp.no_reparable', 'Hola {cliente}, lamentablemente tu {equipo} de la orden {folio} no pudo ser reparado. Puedes pasar a recogerlo cuando gustes y con gusto te explicamos el diagnostico. Cualquier duda estamos para ayudarte.', 'text', 'plantillas'),
('whatsapp.demora', 'Hola {cliente}, te avisamos que tu {equipo} de la orden {folio} esta tomando mas tiempo del estimado (por ejemplo, en espera de una refaccion). Te mantendremos al tanto en cuanto tengamos novedades. Gracias por tu paciencia.', 'text', 'plantillas'),
('legal.terminos_servicio', 'El cliente autoriza la revision del equipo y acepta las condiciones del servicio.', 'text', 'legal'),
('legal.politica_garantia', '1. El taller no se responsabiliza por pérdida o extravío de equipos que no sean retirados dentro de los 90 días naturales posteriores a la fecha de ingreso.\n2. La garantía es de 30 días naturales a partir de la fecha de reparación y aplica únicamente sobre la falla o servicio realizado.\n3. Para retirar el equipo es indispensable presentar la orden de servicio.\n4. Al firmar la orden, el cliente acepta las condiciones físicas y de funcionamiento en las que se recibe el equipo.\n5. La garantía será válida siempre que el sello de garantía permanezca intacto y el equipo no haya sido manipulado o revisado por terceros.\n6. No cuentan con garantía los equipos mojados, golpeados, con pantalla dañada o con falla en flex.\n7. No cuentan con garantía los equipos afectados por variaciones de voltaje.\n8. Las reparaciones o servicios de software no cuentan con garantía.\n9. Todo servicio o actualización de software se realiza bajo autorización y riesgo del cliente.\n10. No se realizan reembolsos bajo ningún concepto.', 'text', 'legal')
ON DUPLICATE KEY UPDATE valor = VALUES(valor), tipo = VALUES(tipo), grupo = VALUES(grupo);

UPDATE clientes
SET nombre_completo = 'Cliente Demo Taller',
    telefono = '5551002000',
    whatsapp = '5551002000',
    domicilio = 'Av. Servicio 123',
    ciudad = 'Ciudad Demo',
    estado = 'Estado Demo',
    codigo_postal = '00000',
    rfc = 'XAXX010101000',
    notas_internas = 'Cliente de ejemplo creado por seed.',
    estatus = 'activo'
WHERE email = 'cliente.demo@local.test' AND deleted_at IS NULL;

INSERT INTO clientes (nombre_completo, telefono, whatsapp, email, domicilio, ciudad, estado, codigo_postal, rfc, notas_internas, estatus)
SELECT 'Cliente Demo Taller', '5551002000', '5551002000', 'cliente.demo@local.test', 'Av. Servicio 123', 'Ciudad Demo', 'Estado Demo', '00000', 'XAXX010101000', 'Cliente de ejemplo creado por seed.', 'activo'
WHERE NOT EXISTS (SELECT 1 FROM clientes WHERE email = 'cliente.demo@local.test' AND deleted_at IS NULL);

INSERT INTO equipos (cliente_id, tipo, marca, modelo, numero_serie, imei, color, password_equipo, accesorios_recibidos, estado_fisico, observaciones)
SELECT c.id, x.tipo, x.marca, x.modelo, x.serie, x.imei, x.color, x.pass, x.accesorios, x.estado_fisico, x.observaciones
FROM clientes c
JOIN (
    SELECT 'celular' tipo, 'Samsung' marca, 'Galaxy Demo' modelo, 'DEMO-CELULAR-001' serie, '359000000000001' imei, 'Negro' color, '1234' pass, 'Cargador' accesorios, 'Pantalla rayada' estado_fisico, 'Equipo demo para orden principal' observaciones
    UNION SELECT 'laptop', 'Lenovo', 'ThinkPad Demo', 'DEMO-LAPTOP-001', NULL, 'Gris', '', 'Cargador', 'Bisagra con desgaste', 'Equipo demo'
    UNION SELECT 'pc', 'Ensamblada', 'Ryzen Demo', 'DEMO-PC-001', NULL, 'Negro', '', 'Cable poder', 'Gabinete con polvo', 'Equipo demo'
    UNION SELECT 'consola', 'Sony', 'PlayStation Demo', 'DEMO-CONSOLA-001', NULL, 'Blanco', '', 'Control', 'Buen estado', 'Equipo demo'
    UNION SELECT 'impresora', 'HP', 'LaserJet Demo', 'DEMO-IMPRESORA-001', NULL, 'Blanco', '', 'Cable USB', 'Tapa floja', 'Equipo demo'
    UNION SELECT 'electrodomestico', 'LG', 'Microondas Demo', 'DEMO-ELECTRO-001', NULL, 'Plata', '', 'Plato giratorio', 'Uso normal', 'Equipo demo'
    UNION SELECT 'herramienta', 'Bosch', 'Taladro Demo', 'DEMO-HERRAMIENTA-001', NULL, 'Azul', '', 'Maletin', 'Carcasa marcada', 'Equipo demo'
    UNION SELECT 'moto', 'Italika', 'FT Demo', 'DEMO-MOTO-001', NULL, 'Rojo', '', 'Llave', 'Golpe lateral menor', 'Equipo demo'
    UNION SELECT 'otro', 'Generica', 'Equipo Otro Demo', 'DEMO-OTRO-001', NULL, 'Verde', '', 'Sin accesorios', 'Buen estado', 'Equipo demo'
) x
WHERE c.telefono = '5551002000'
AND NOT EXISTS (SELECT 1 FROM equipos e WHERE e.numero_serie = x.serie);

INSERT INTO proveedores (nombre, contacto, telefono, email, domicilio, sitio_web, notas, estatus)
SELECT 'Proveedor Demo', 'Contacto Demo', '5552003000', 'proveedor@local.test', 'Calle Refacciones 456', 'https://example.test', 'Proveedor de ejemplo.', 'activo'
WHERE NOT EXISTS (SELECT 1 FROM proveedores WHERE email = 'proveedor@local.test' AND deleted_at IS NULL);

INSERT INTO refacciones (proveedor_id, nombre, sku, categoria, marca, modelo_compatible, costo, precio_venta, stock_actual, stock_minimo, ubicacion, estatus)
SELECT p.id, 'Pantalla Demo OLED', 'SKU-DEMO-PANTALLA', 'Pantallas', 'Samsung', 'Galaxy Demo', 850.00, 1450.00, 5, 2, 'A1', 'activo'
FROM proveedores p
WHERE p.email = 'proveedor@local.test'
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), stock_actual = VALUES(stock_actual), stock_minimo = VALUES(stock_minimo);

INSERT INTO ordenes_servicio (
    folio, cliente_id, equipo_id, tecnico_id, recibido_por, tipo_servicio, falla_reportada, diagnostico_inicial,
    prioridad, estado, fecha_estimada_entrega, costo_estimado, costo_final, anticipo, saldo_pendiente,
    garantia_ofrecida, observaciones_internas, observaciones_cliente, codigo_entrega, ubicacion_actual, token_publico
)
SELECT 'ST-DEMO-00001', c.id, e.id, t.id, r.id, 'Reparacion', 'El cliente reporta que la pantalla no enciende despues de una caida.',
       'Se recibe equipo con pantalla rayada y sin evidencia de humedad.', 'alta', 'esperando_autorizacion',
       DATE_ADD(NOW(), INTERVAL 3 DAY), 300.00, 1450.00, 300.00, 1150.00,
       '30 dias sobre pantalla instalada', 'Orden demo con datos visibles para pruebas.', 'Diagnostico en proceso de autorizacion.', 'ENT-DEMO2468', 'Recepcion', 'demo-token-orden-00001'
FROM clientes c
JOIN equipos e ON e.cliente_id = c.id AND e.numero_serie = 'DEMO-CELULAR-001'
JOIN users t ON t.email = 'tecnico@local.test'
JOIN users r ON r.email = 'recepcion@local.test'
WHERE c.telefono = '5551002000'
ON DUPLICATE KEY UPDATE estado = VALUES(estado), costo_final = VALUES(costo_final), anticipo = VALUES(anticipo), saldo_pendiente = VALUES(saldo_pendiente), codigo_entrega = VALUES(codigo_entrega);

INSERT INTO diagnosticos (orden_id, tecnico_id, diagnostico_tecnico, diagnostico_cliente, causa_probable, pruebas_realizadas, piezas_necesarias, tiempo_estimado, costo_mano_obra, costo_refacciones, costo_total_sugerido, bloqueado)
SELECT o.id, u.id, 'Pantalla sin imagen. Flex danado por golpe. Equipo enciende y vibra.', 'La pantalla requiere reemplazo para recuperar imagen y tactil.',
       'Impacto fisico', 'Prueba con pantalla de laboratorio y revision de conectores.', 'Pantalla OLED compatible', '2 horas', 350.00, 1100.00, 1450.00, 0
FROM ordenes_servicio o
JOIN users u ON u.email = 'tecnico@local.test'
WHERE o.folio = 'ST-DEMO-00001'
AND NOT EXISTS (SELECT 1 FROM diagnosticos d WHERE d.orden_id = o.id);

INSERT INTO cotizaciones (orden_id, version, subtotal, descuento, iva, total, vigencia, terminos, estado, created_by)
SELECT o.id, 1, 1450.00, 0.00, 0.00, 1450.00, DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'Cotizacion demo sujeta a disponibilidad de refaccion.', 'pendiente', u.id
FROM ordenes_servicio o
JOIN users u ON u.email = 'tecnico_senior@local.test'
WHERE o.folio = 'ST-DEMO-00001'
AND NOT EXISTS (SELECT 1 FROM cotizaciones q WHERE q.orden_id = o.id AND q.version = 1);

INSERT INTO cotizacion_items (cotizacion_id, tipo, descripcion, cantidad, precio_unitario, subtotal)
SELECT q.id, 'refaccion', 'Pantalla OLED compatible instalada', 1, 1450.00, 1450.00
FROM cotizaciones q
JOIN ordenes_servicio o ON o.id = q.orden_id
WHERE o.folio = 'ST-DEMO-00001'
AND NOT EXISTS (SELECT 1 FROM cotizacion_items qi WHERE qi.cotizacion_id = q.id);

INSERT INTO pagos (orden_id, monto, metodo, referencia, usuario_id, notas, estado)
SELECT o.id, 300.00, 'efectivo', 'ANT-DEMO-001', u.id, 'Anticipo demo.', 'activo'
FROM ordenes_servicio o
JOIN users u ON u.email = 'caja@local.test'
WHERE o.folio = 'ST-DEMO-00001'
AND NOT EXISTS (SELECT 1 FROM pagos p WHERE p.orden_id = o.id AND p.referencia = 'ANT-DEMO-001');

INSERT INTO garantias (orden_id, fecha_inicio, fecha_fin, condiciones, estado, motivo, resolucion)
SELECT o.id, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Garantia demo sobre pantalla instalada.', 'activa', NULL, NULL
FROM ordenes_servicio o
WHERE o.folio = 'ST-DEMO-00001'
AND NOT EXISTS (SELECT 1 FROM garantias g WHERE g.orden_id = o.id);

INSERT INTO mensajes (cliente_id, orden_id, canal, plantilla, destinatario, mensaje, estado, usuario_id)
SELECT c.id, o.id, 'whatsapp', 'orden_recibida', c.whatsapp, 'Hola Cliente Demo Taller, tu orden ST-DEMO-00001 fue recibida correctamente.', 'registrado', u.id
FROM ordenes_servicio o
JOIN clientes c ON c.id = o.cliente_id
JOIN users u ON u.email = 'recepcion@local.test'
WHERE o.folio = 'ST-DEMO-00001'
AND NOT EXISTS (SELECT 1 FROM mensajes m WHERE m.orden_id = o.id AND m.plantilla = 'orden_recibida');

INSERT INTO agenda_eventos (orden_id, tecnico_id, titulo, descripcion, inicio, fin, tipo, estado, created_by)
SELECT o.id, t.id, 'Revision demo', 'Evento demo para revisar carga del tecnico.', DATE_ADD(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 1 DAY) + INTERVAL 1 HOUR, 'trabajo', 'programado', r.id
FROM ordenes_servicio o
JOIN users t ON t.email = 'tecnico@local.test'
JOIN users r ON r.email = 'recepcion@local.test'
WHERE o.folio = 'ST-DEMO-00001'
AND NOT EXISTS (SELECT 1 FROM agenda_eventos a WHERE a.orden_id = o.id AND a.titulo = 'Revision demo');
