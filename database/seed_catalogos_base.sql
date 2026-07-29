-- Catalogo base de marcas y modelos de equipos.
-- Importar despues de database/seed_roles_demo.sql.
-- No crea roles, usuarios, clientes, ordenes ni inventario.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO equipo_marcas (nombre, slug, estatus) VALUES
('Acer', 'acer', 'activo'),
('Apple', 'apple', 'activo'),
('Asus', 'asus', 'activo'),
('Black+Decker', 'black-decker', 'activo'),
('Bosch', 'bosch', 'activo'),
('Brother', 'brother', 'activo'),
('Canon', 'canon', 'activo'),
('Dell', 'dell', 'activo'),
('DeWalt', 'dewalt', 'activo'),
('Epson', 'epson', 'activo'),
('Generica', 'generica', 'activo'),
('Hisense', 'hisense', 'activo'),
('Honda', 'honda', 'activo'),
('Honor', 'honor', 'activo'),
('HP', 'hp', 'activo'),
('Huawei', 'huawei', 'activo'),
('Italika', 'italika', 'activo'),
('Lenovo', 'lenovo', 'activo'),
('LG', 'lg', 'activo'),
('Mabe', 'mabe', 'activo'),
('Makita', 'makita', 'activo'),
('Microsoft', 'microsoft', 'activo'),
('Motorola', 'motorola', 'activo'),
('MSI', 'msi', 'activo'),
('Nintendo', 'nintendo', 'activo'),
('Nokia', 'nokia', 'activo'),
('OnePlus', 'oneplus', 'activo'),
('Oppo', 'oppo', 'activo'),
('PlayStation', 'playstation', 'activo'),
('Realme', 'realme', 'activo'),
('Ryobi', 'ryobi', 'activo'),
('Samsung', 'samsung', 'activo'),
('Sanyo', 'sanyo', 'activo'),
('Sony', 'sony', 'activo'),
('TCL', 'tcl', 'activo'),
('Toshiba', 'toshiba', 'activo'),
('Vivo', 'vivo', 'activo'),
('Whirlpool', 'whirlpool', 'activo'),
('Xiaomi', 'xiaomi', 'activo'),
('Yamaha', 'yamaha', 'activo')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), estatus = VALUES(estatus);

INSERT INTO equipo_modelos (marca_id, nombre, slug, tipo_equipo, estatus)
SELECT m.id, x.nombre, x.slug, x.tipo_equipo, 'activo'
FROM equipo_marcas m
JOIN (
    SELECT 'Apple' marca, 'iPhone 11' nombre, 'iphone-11' slug, 'celular' tipo_equipo
    UNION SELECT 'Apple', 'iPhone 12', 'iphone-12', 'celular'
    UNION SELECT 'Apple', 'iPhone 13', 'iphone-13', 'celular'
    UNION SELECT 'Apple', 'iPhone 14', 'iphone-14', 'celular'
    UNION SELECT 'Apple', 'iPhone 15', 'iphone-15', 'celular'
    UNION SELECT 'Apple', 'iPhone 16', 'iphone-16', 'celular'
    UNION SELECT 'Apple', 'iPad', 'ipad', 'otro'
    UNION SELECT 'Apple', 'MacBook Air', 'macbook-air', 'laptop'
    UNION SELECT 'Apple', 'MacBook Pro', 'macbook-pro', 'laptop'
    UNION SELECT 'Samsung', 'Galaxy A03', 'galaxy-a03', 'celular'
    UNION SELECT 'Samsung', 'Galaxy A14', 'galaxy-a14', 'celular'
    UNION SELECT 'Samsung', 'Galaxy A15', 'galaxy-a15', 'celular'
    UNION SELECT 'Samsung', 'Galaxy A24', 'galaxy-a24', 'celular'
    UNION SELECT 'Samsung', 'Galaxy A34', 'galaxy-a34', 'celular'
    UNION SELECT 'Samsung', 'Galaxy A54', 'galaxy-a54', 'celular'
    UNION SELECT 'Samsung', 'Galaxy S21', 'galaxy-s21', 'celular'
    UNION SELECT 'Samsung', 'Galaxy S22', 'galaxy-s22', 'celular'
    UNION SELECT 'Samsung', 'Galaxy S23', 'galaxy-s23', 'celular'
    UNION SELECT 'Samsung', 'Galaxy S24', 'galaxy-s24', 'celular'
    UNION SELECT 'Samsung', 'Galaxy Tab', 'galaxy-tab', 'otro'
    UNION SELECT 'Samsung', 'Samsung TV', 'samsung-tv', 'electrodomestico'
    UNION SELECT 'Motorola', 'Moto G Power', 'moto-g-power', 'celular'
    UNION SELECT 'Motorola', 'Moto G Play', 'moto-g-play', 'celular'
    UNION SELECT 'Motorola', 'Moto G Stylus', 'moto-g-stylus', 'celular'
    UNION SELECT 'Motorola', 'Moto Edge', 'moto-edge', 'celular'
    UNION SELECT 'Xiaomi', 'Redmi Note 10', 'redmi-note-10', 'celular'
    UNION SELECT 'Xiaomi', 'Redmi Note 11', 'redmi-note-11', 'celular'
    UNION SELECT 'Xiaomi', 'Redmi Note 12', 'redmi-note-12', 'celular'
    UNION SELECT 'Xiaomi', 'Redmi Note 13', 'redmi-note-13', 'celular'
    UNION SELECT 'Xiaomi', 'Poco X3', 'poco-x3', 'celular'
    UNION SELECT 'Xiaomi', 'Poco X5', 'poco-x5', 'celular'
    UNION SELECT 'Huawei', 'P30 Lite', 'p30-lite', 'celular'
    UNION SELECT 'Huawei', 'P40 Lite', 'p40-lite', 'celular'
    UNION SELECT 'Huawei', 'Y9', 'y9', 'celular'
    UNION SELECT 'Honor', 'Honor X8', 'honor-x8', 'celular'
    UNION SELECT 'Oppo', 'Reno 7', 'reno-7', 'celular'
    UNION SELECT 'Oppo', 'A57', 'a57', 'celular'
    UNION SELECT 'Vivo', 'Y20', 'y20', 'celular'
    UNION SELECT 'Realme', 'C55', 'c55', 'celular'
    UNION SELECT 'OnePlus', 'Nord', 'nord', 'celular'
    UNION SELECT 'Lenovo', 'ThinkPad', 'thinkpad', 'laptop'
    UNION SELECT 'Lenovo', 'IdeaPad', 'ideapad', 'laptop'
    UNION SELECT 'HP', 'Pavilion', 'pavilion', 'laptop'
    UNION SELECT 'HP', 'LaserJet', 'laserjet', 'impresora'
    UNION SELECT 'HP', 'DeskJet', 'deskjet', 'impresora'
    UNION SELECT 'Dell', 'Inspiron', 'inspiron', 'laptop'
    UNION SELECT 'Dell', 'Latitude', 'latitude', 'laptop'
    UNION SELECT 'Asus', 'VivoBook', 'vivobook', 'laptop'
    UNION SELECT 'Asus', 'ROG', 'rog', 'laptop'
    UNION SELECT 'Acer', 'Aspire', 'aspire', 'laptop'
    UNION SELECT 'MSI', 'Modern', 'modern', 'laptop'
    UNION SELECT 'Brother', 'DCP', 'dcp', 'impresora'
    UNION SELECT 'Epson', 'EcoTank', 'ecotank', 'impresora'
    UNION SELECT 'Canon', 'PIXMA', 'pixma', 'impresora'
    UNION SELECT 'Sony', 'PlayStation 4', 'playstation-4', 'consola'
    UNION SELECT 'Sony', 'PlayStation 5', 'playstation-5', 'consola'
    UNION SELECT 'Nintendo', 'Switch', 'switch', 'consola'
    UNION SELECT 'Microsoft', 'Xbox One', 'xbox-one', 'consola'
    UNION SELECT 'Microsoft', 'Xbox Series S', 'xbox-series-s', 'consola'
    UNION SELECT 'Microsoft', 'Xbox Series X', 'xbox-series-x', 'consola'
    UNION SELECT 'LG', 'Microondas', 'microondas', 'electrodomestico'
    UNION SELECT 'LG', 'Lavadora', 'lavadora', 'electrodomestico'
    UNION SELECT 'Whirlpool', 'Lavadora', 'lavadora', 'electrodomestico'
    UNION SELECT 'Mabe', 'Refrigerador', 'refrigerador', 'electrodomestico'
    UNION SELECT 'Hisense', 'Pantalla Smart TV', 'pantalla-smart-tv', 'electrodomestico'
    UNION SELECT 'TCL', 'Pantalla Roku TV', 'pantalla-roku-tv', 'electrodomestico'
    UNION SELECT 'Bosch', 'Taladro', 'taladro', 'herramienta'
    UNION SELECT 'Makita', 'Esmeril', 'esmeril', 'herramienta'
    UNION SELECT 'DeWalt', 'Rotomartillo', 'rotomartillo', 'herramienta'
    UNION SELECT 'Black+Decker', 'Taladro', 'taladro', 'herramienta'
    UNION SELECT 'Ryobi', 'Sierra', 'sierra', 'herramienta'
    UNION SELECT 'Italika', 'FT', 'ft', 'moto'
    UNION SELECT 'Italika', 'DM', 'dm', 'moto'
    UNION SELECT 'Honda', 'CG', 'cg', 'moto'
    UNION SELECT 'Yamaha', 'FZ', 'fz', 'moto'
    UNION SELECT 'Generica', 'Equipo Otro Demo', 'equipo-otro-demo', 'otro'
) x ON x.marca = m.nombre
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), tipo_equipo = VALUES(tipo_equipo), estatus = VALUES(estatus);
