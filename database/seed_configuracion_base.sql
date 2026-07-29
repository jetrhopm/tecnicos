-- Configuracion base operativa del sistema.
-- Importar despues de database/seed_roles_demo.sql.
-- No crea roles, usuarios, clientes, ordenes, inventario ni catalogos.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO configuraciones (clave, valor, tipo, grupo) VALUES
('negocio.nombre', 'Servicio Tecnico', 'string', 'negocio'),
('negocio.telefono', '', 'string', 'negocio'),
('negocio.whatsapp', '', 'string', 'negocio'),
('negocio.email', '', 'string', 'negocio'),
('negocio.direccion', '', 'string', 'negocio'),
('negocio.logo_url', '', 'string', 'negocio'),
('sistema.nombre', 'Sistema Web de Gestión de Servicios Técnicos', 'string', 'sistema'),
('sistema.moneda', 'MXN', 'string', 'sistema'),
('sistema.zona_horaria', 'America/Mexico_City', 'string', 'sistema'),
('sistema.iva_activo', '0', 'bool', 'sistema'),
('sistema.iva_porcentaje', '16', 'number', 'sistema'),
('whatsapp.codigo_pais', '52', 'string', 'mensajes'),
('ordenes.prefijo_folio', 'ST', 'string', 'ordenes'),
('ordenes.garantia_default', '30 días naturales sobre la reparación realizada', 'string', 'ordenes'),
('garantia.dias_default', '30', 'number', 'garantia'),
('ticket.garantia', '1. El taller no se responsabiliza por pérdida o extravío de equipos que no sean retirados dentro de los 90 días naturales posteriores a la fecha de ingreso.\n2. La garantía es de 30 días naturales a partir de la fecha de reparación y aplica únicamente sobre la falla o servicio realizado.\n3. Para retirar el equipo es indispensable presentar la orden de servicio.\n4. Al firmar la orden, el cliente acepta las condiciones físicas y de funcionamiento en las que se recibe el equipo.\n5. La garantía será válida siempre que el sello de garantía permanezca intacto y el equipo no haya sido manipulado o revisado por terceros.\n6. No cuentan con garantía los equipos mojados, golpeados, con pantalla dañada o con falla en flex.\n7. No cuentan con garantía los equipos afectados por variaciones de voltaje.\n8. Las reparaciones o servicios de software no cuentan con garantía.\n9. Todo servicio o actualización de software se realiza bajo autorización y riesgo del cliente.\n10. No se realizan reembolsos bajo ningún concepto.', 'text', 'ticket'),
('legal.terminos_servicio', 'El cliente autoriza la revisión del equipo y acepta las condiciones del servicio.', 'text', 'legal'),
('legal.politica_garantia', '1. El taller no se responsabiliza por pérdida o extravío de equipos que no sean retirados dentro de los 90 días naturales posteriores a la fecha de ingreso.\n2. La garantía es de 30 días naturales a partir de la fecha de reparación y aplica únicamente sobre la falla o servicio realizado.\n3. Para retirar el equipo es indispensable presentar la orden de servicio.\n4. Al firmar la orden, el cliente acepta las condiciones físicas y de funcionamiento en las que se recibe el equipo.\n5. La garantía será válida siempre que el sello de garantía permanezca intacto y el equipo no haya sido manipulado o revisado por terceros.\n6. No cuentan con garantía los equipos mojados, golpeados, con pantalla dañada o con falla en flex.\n7. No cuentan con garantía los equipos afectados por variaciones de voltaje.\n8. Las reparaciones o servicios de software no cuentan con garantía.\n9. Todo servicio o actualización de software se realiza bajo autorización y riesgo del cliente.\n10. No se realizan reembolsos bajo ningún concepto.', 'text', 'legal'),
('archivos.max_mb', '8', 'number', 'archivos'),
('whatsapp.orden_recibida', 'Hola {cliente}, tu orden {folio} para el equipo {equipo} fue recibida correctamente. Puedes consultar el avance aquí: {link}', 'text', 'plantillas'),
('whatsapp.diagnostico_listo', 'Hola {cliente}, tenemos lista la cotización de tu orden {folio} ({equipo}). Necesitamos tu validación para continuar con la reparación. Revísala y autorízala aquí: {link}', 'text', 'plantillas'),
('whatsapp.equipo_listo', 'Hola {cliente}, tu {equipo} de la orden {folio} ya está listo para entrega. Saldo por pagar: {saldo}. Puedes pasar a recogerlo. Gracias.', 'text', 'plantillas'),
('whatsapp.entregado', 'Hola {cliente}, gracias por tu preferencia. Tu {equipo} de la orden {folio} fue entregado. Conserva tu comprobante para hacer válida la garantía. Estamos para servirte, que tengas excelente día.', 'text', 'plantillas'),
('whatsapp.no_reparable', 'Hola {cliente}, lamentablemente tu {equipo} de la orden {folio} no pudo ser reparado. Puedes pasar a recogerlo cuando gustes y con gusto te explicamos el diagnóstico. Cualquier duda estamos para ayudarte.', 'text', 'plantillas'),
('whatsapp.demora', 'Hola {cliente}, te avisamos que tu {equipo} de la orden {folio} está tomando más tiempo del estimado (por ejemplo, en espera de una refacción). Te mantendremos al tanto en cuanto tengamos novedades. Gracias por tu paciencia.', 'text', 'plantillas'),
('demo.activo', '1', 'bool', 'licencia'),
('demo.bloqueo_manual', '0', 'bool', 'licencia'),
('demo.dias', '14', 'number', 'licencia'),
('demo.mensaje_expirado', 'La demostración terminó. Si te interesó nuestro sistema y lo ves útil en tu día a día, contáctanos para activar la versión extendida.', 'text', 'licencia'),
('demo.mensaje_bloqueado', 'El acceso a esta demostración fue pausado temporalmente. Contáctanos para reactivar el servicio.', 'text', 'licencia')
ON DUPLICATE KEY UPDATE valor = VALUES(valor), tipo = VALUES(tipo), grupo = VALUES(grupo);

INSERT INTO configuraciones (clave, valor, tipo, grupo)
SELECT 'demo.inicio', CURDATE(), 'date', 'licencia'
WHERE NOT EXISTS (SELECT 1 FROM configuraciones WHERE clave = 'demo.inicio');
