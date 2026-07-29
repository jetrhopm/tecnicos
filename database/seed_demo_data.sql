-- Datos demo operativos: clientes, equipos, inventario, ordenes, pagos y agenda.
-- Importar despues de database/seed_catalogos_base.sql.
-- No crea roles, permisos ni usuarios.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 1;

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

UPDATE equipos e
JOIN equipo_marcas m ON m.slug = LOWER(REPLACE(REPLACE(e.marca, '+', ''), ' ', '-'))
SET e.marca_id = m.id
WHERE e.marca_id IS NULL AND e.marca IS NOT NULL;

UPDATE equipos e
JOIN equipo_modelos mo ON mo.marca_id = e.marca_id AND mo.slug = LOWER(REPLACE(e.modelo, ' ', '-'))
SET e.modelo_id = mo.id
WHERE e.modelo_id IS NULL AND e.marca_id IS NOT NULL AND e.modelo IS NOT NULL;

INSERT INTO proveedores (nombre, contacto, telefono, email, domicilio, sitio_web, notas, estatus)
SELECT 'Proveedor Demo', 'Contacto Demo', '5552003000', 'proveedor@local.test', 'Calle Refacciones 456', 'https://example.test', 'Proveedor de ejemplo.', 'activo'
WHERE NOT EXISTS (SELECT 1 FROM proveedores WHERE email = 'proveedor@local.test' AND deleted_at IS NULL);

INSERT INTO refacciones (proveedor_id, nombre, sku, categoria, marca, modelo_compatible, costo, precio_venta, stock_actual, stock_minimo, ubicacion, estatus)
SELECT p.id, x.nombre, x.sku, x.categoria, x.marca, x.modelo_compatible, x.costo, x.precio_venta, x.stock_actual, x.stock_minimo, x.ubicacion, 'activo'
FROM proveedores p
JOIN (
    SELECT 'Pantalla OLED compatible Samsung A15' nombre, 'POS-SAM-A15-OLED' sku, 'Pantallas' categoria, 'Samsung' marca, 'Galaxy A15' modelo_compatible, 620.00 costo, 1150.00 precio_venta, 8 stock_actual, 2 stock_minimo, 'A1' ubicacion
    UNION ALL SELECT 'Pantalla incell iPhone 11', 'POS-IPH11-INCELL', 'Pantallas', 'Apple', 'iPhone 11', 480.00, 950.00, 6, 2, 'A2'
    UNION ALL SELECT 'Pantalla OLED iPhone 13', 'POS-IPH13-OLED', 'Pantallas', 'Apple', 'iPhone 13', 1450.00, 2350.00, 4, 1, 'A3'
    UNION ALL SELECT 'Bateria iPhone 11 alta capacidad', 'POS-IPH11-BAT', 'Baterias', 'Apple', 'iPhone 11', 210.00, 480.00, 10, 3, 'B1'
    UNION ALL SELECT 'Bateria Samsung A14/A15', 'POS-SAM-A14-BAT', 'Baterias', 'Samsung', 'Galaxy A14 / A15', 180.00, 420.00, 12, 3, 'B2'
    UNION ALL SELECT 'Centro de carga Redmi Note 12', 'POS-REDMI12-CG', 'Centros de carga', 'Xiaomi', 'Redmi Note 12', 95.00, 280.00, 15, 4, 'C1'
    UNION ALL SELECT 'Centro de carga Motorola G Power', 'POS-MOTOGP-CG', 'Centros de carga', 'Motorola', 'Moto G Power', 110.00, 300.00, 9, 3, 'C2'
    UNION ALL SELECT 'Flex encendido Samsung A15', 'POS-SAM-A15-FLEX', 'Flex y botones', 'Samsung', 'Galaxy A15', 65.00, 180.00, 18, 5, 'C3'
    UNION ALL SELECT 'Camara trasera iPhone 12', 'POS-IPH12-CAMT', 'Camaras', 'Apple', 'iPhone 12', 520.00, 980.00, 3, 1, 'D1'
    UNION ALL SELECT 'Bocina auricular universal celular', 'POS-UNI-AURI', 'Audio', 'Generica', 'Celular universal', 35.00, 120.00, 25, 6, 'D2'
    UNION ALL SELECT 'Microfono universal soldable', 'POS-UNI-MIC', 'Audio', 'Generica', 'Celular universal', 18.00, 80.00, 30, 8, 'D3'
    UNION ALL SELECT 'Vidrio templado 6.5 universal', 'POS-MICA-65', 'Accesorios', 'Generica', 'Universal 6.5 pulgadas', 18.00, 80.00, 50, 10, 'E1'
    UNION ALL SELECT 'Mica ceramica iPhone 11/XR', 'POS-MICA-IPH11', 'Accesorios', 'Apple', 'iPhone 11 / XR', 28.00, 120.00, 35, 8, 'E2'
    UNION ALL SELECT 'Cable USB-C carga rapida 1m', 'POS-CABLE-USBC', 'Accesorios', 'Generica', 'USB-C', 38.00, 130.00, 40, 10, 'F1'
    UNION ALL SELECT 'Cable Lightning 1m', 'POS-CABLE-LIG', 'Accesorios', 'Apple', 'Lightning', 42.00, 150.00, 30, 8, 'F2'
    UNION ALL SELECT 'Cargador tipo C 25W', 'POS-CARG-TC25', 'Accesorios', 'Generica', 'USB-C 25W', 95.00, 250.00, 20, 5, 'F3'
    UNION ALL SELECT 'Memoria RAM DDR4 8GB laptop', 'POS-RAM-DDR4-8', 'Laptop', 'Generica', 'DDR4 SO-DIMM', 360.00, 650.00, 7, 2, 'L1'
    UNION ALL SELECT 'SSD 240GB SATA', 'POS-SSD-240', 'Laptop', 'Generica', 'SATA 2.5', 310.00, 590.00, 8, 2, 'L2'
    UNION ALL SELECT 'Pasta termica alto rendimiento', 'POS-PASTA-TERM', 'Servicio', 'Generica', 'CPU/GPU', 55.00, 160.00, 16, 4, 'L3'
    UNION ALL SELECT 'Joystick analogo control PS4', 'POS-PS4-JOY', 'Consolas', 'Sony', 'DualShock 4', 55.00, 180.00, 12, 3, 'G1'
    UNION ALL SELECT 'Puerto HDMI PlayStation 4', 'POS-PS4-HDMI', 'Consolas', 'Sony', 'PlayStation 4', 85.00, 260.00, 6, 2, 'G2'
    UNION ALL SELECT 'Fusible microondas 20A', 'POS-MICRO-FUS20', 'Electrodomesticos', 'Generica', 'Microondas', 20.00, 90.00, 20, 5, 'H1'
    UNION ALL SELECT 'Carbon para taladro universal', 'POS-CARBON-TAL', 'Herramientas', 'Generica', 'Taladro universal', 25.00, 110.00, 22, 6, 'H2'
    UNION ALL SELECT 'Foco LED moto H4', 'POS-MOTO-H4LED', 'Motos', 'Generica', 'H4', 75.00, 190.00, 10, 3, 'M1'
) x
WHERE p.email = 'proveedor@local.test'
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    categoria = VALUES(categoria),
    marca = VALUES(marca),
    modelo_compatible = VALUES(modelo_compatible),
    costo = VALUES(costo),
    precio_venta = VALUES(precio_venta),
    stock_actual = VALUES(stock_actual),
    stock_minimo = VALUES(stock_minimo),
    ubicacion = VALUES(ubicacion),
    estatus = VALUES(estatus);

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
