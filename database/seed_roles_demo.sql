-- Roles, permisos y usuarios demo.
-- Importar despues de database/schema.sql.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO roles (name, label) VALUES
('licenciante', 'Administrador de licencias'),
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
    UNION SELECT 'usuarios' UNION SELECT 'auditoria' UNION SELECT 'licencias'
) m
CROSS JOIN (
    SELECT 'ver' action UNION SELECT 'crear' UNION SELECT 'editar' UNION SELECT 'eliminar'
    UNION SELECT 'autorizar' UNION SELECT 'cambiar_estado' UNION SELECT 'exportar'
    UNION SELECT 'imprimir' UNION SELECT 'administrar'
) a
WHERE 1 = 1
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.name = 'superadmin' AND p.module <> 'licencias'
ON DUPLICATE KEY UPDATE role_id = role_id;

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name = 'licenciante'
AND (
    (p.module = 'dashboard' AND p.action = 'ver')
    OR (p.module = 'usuarios' AND p.action IN ('ver','crear','editar','eliminar','administrar'))
    OR (p.module = 'licencias' AND p.action IN ('ver','administrar'))
)
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

INSERT INTO users (name, email, password, status)
VALUES ('Administrador de Licencias', 'admin@gocentersuplementos.com.mx', '$2y$10$CNak65snzPhbj0O4AceIBeTwjAetn/jczciOaRWo8DX8EbeLT5GDW', 'activo')
ON DUPLICATE KEY UPDATE name = VALUES(name), password = VALUES(password), status = 'activo', deleted_at = NULL;

INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u JOIN roles r ON r.name = 'licenciante' WHERE u.email = 'admin@gocentersuplementos.com.mx'
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
