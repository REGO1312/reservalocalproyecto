-- Datos de demostración para Reserva Local (Configurado para El Salvador)
-- Contraseña general para todas las cuentas de prueba: Demo123!
-- Hash bcrypt correspondiente: $2y$10$eJh1HAFACMGXcvd5LwJDSeCq58f1LrxdYgERJFhC2hG/G76fds6ji

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. ROLES
INSERT INTO roles (id, nombre, descripcion) VALUES
(1, 'cliente', 'Usuario que busca y reserva citas'),
(2, 'propietario', 'Administrador de negocio(s), servicios y personal'),
(3, 'profesional', 'Especialista que atiende citas y gestiona su agenda'),
(4, 'administrador', 'Superadministrador global de la plataforma');

-- 2. USUARIOS (Teléfonos locales de El Salvador)
INSERT INTO usuarios (id, nombre, email, password_hash, telefono, estado) VALUES
(1, 'Administrador Global', 'admin@reservalocal.test', '$2y$10$eJh1HAFACMGXcvd5LwJDSeCq58f1LrxdYgERJFhC2hG/G76fds6ji', '7850-1122', 'activo'),
(2, 'Roberto Garza (Dueño El Caballero)', 'propietario1@reservalocal.test', '$2y$10$eJh1HAFACMGXcvd5LwJDSeCq58f1LrxdYgERJFhC2hG/G76fds6ji', '7920-3344', 'activo'),
(3, 'Mariana Valdés (Dueña Bella Donna)', 'propietario2@reservalocal.test', '$2y$10$eJh1HAFACMGXcvd5LwJDSeCq58f1LrxdYgERJFhC2hG/G76fds6ji', '7140-5566', 'activo'),
(4, 'Carlos Méndez (Barbero)', 'carlos@reservalocal.test', '$2y$10$eJh1HAFACMGXcvd5LwJDSeCq58f1LrxdYgERJFhC2hG/G76fds6ji', '7210-7788', 'activo'),
(5, 'David López (Barbero)', 'david@reservalocal.test', '$2y$10$eJh1HAFACMGXcvd5LwJDSeCq58f1LrxdYgERJFhC2hG/G76fds6ji', '7350-9900', 'activo'),
(6, 'Sofía Ramírez (Estilista)', 'sofia@reservalocal.test', '$2y$10$eJh1HAFACMGXcvd5LwJDSeCq58f1LrxdYgERJFhC2hG/G76fds6ji', '7490-1234', 'activo'),
(7, 'Elena Gómez (Colorista & Manicura)', 'elena@reservalocal.test', '$2y$10$eJh1HAFACMGXcvd5LwJDSeCq58f1LrxdYgERJFhC2hG/G76fds6ji', '7580-5678', 'activo'),
(8, 'Alejandro Morales (Cliente)', 'cliente1@reservalocal.test', '$2y$10$eJh1HAFACMGXcvd5LwJDSeCq58f1LrxdYgERJFhC2hG/G76fds6ji', '7630-9012', 'activo'),
(9, 'Camila Torres (Cliente)', 'cliente2@reservalocal.test', '$2y$10$eJh1HAFACMGXcvd5LwJDSeCq58f1LrxdYgERJFhC2hG/G76fds6ji', '7720-3456', 'activo');

-- 3. ASIGNACIÓN DE ROLES
INSERT INTO usuario_roles (usuario_id, rol_id) VALUES
(1, 4), -- Admin es administrador
(1, 1), -- Admin también puede actuar como cliente
(2, 2), -- Roberto es propietario
(2, 1), -- y cliente
(3, 2), -- Mariana es propietaria
(3, 1), -- y cliente
(4, 3), -- Carlos es profesional
(4, 1), -- y cliente
(5, 3), -- David es profesional
(5, 1),
(6, 3), -- Sofía es profesional
(6, 1),
(7, 3), -- Elena es profesional
(7, 1),
(8, 1), -- Alejandro es cliente
(9, 1); -- Camila es cliente

-- 4. NEGOCIOS (Ubicados en San Salvador, San Benito y Santa Tecla - El Salvador)
INSERT INTO negocios (id, nombre, categoria, descripcion, direccion, zona_municipio, latitud, longitud, telefono, email, zona_horaria, imagen_url, estado) VALUES
(1, 'Barbería Clásica El Caballero', 'barberia', 'Auténtica barbería tradicional con toallas calientes, navaja libre y los mejores cortes clásicos y modernos en la Escalón.', 'Paseo General Escalón #3700, Col. Escalón', 'Colonia Escalón, San Salvador', 13.7025000, -89.2430000, '2263-1122', 'contacto@elcaballerobarber.sv', 'America/El_Salvador', 'assets/img/barberia-caballero.svg', 'activo'),
(2, 'Salón & Spa Bella Donna', 'salon', 'Estilismo de alta gama en la Zona Rosa/San Benito, colorimetría experta, tratamientos capilares y spa de uñas para realzar tu belleza.', 'Boulevard del Hipódromo #415, Col. San Benito', 'San Benito, San Salvador', 13.6890000, -89.2395000, '2245-9988', 'citas@belladonna.sv', 'America/El_Salvador', 'assets/img/salon-belladonna.svg', 'activo'),
(3, 'Vintage Blade Studio', 'barberia', 'Estilo vintage y urbano en Santa Tecla, diseño de barba spa y cortes precisos con café salvadoreño de especialidad de cortesía.', 'Av. Las Palmeras y 7a Calle Poniente', 'Santa Tecla, La Libertad', 13.6740000, -89.2880000, '2228-7733', 'info@vintageblade.sv', 'America/El_Salvador', 'assets/img/barberia-vintage.svg', 'activo');

-- 5. RELACIÓN NEGOCIO - USUARIO (Propietarios)
INSERT INTO negocio_usuarios (negocio_id, usuario_id, rol) VALUES
(1, 2, 'propietario'),
(2, 3, 'propietario'),
(3, 2, 'propietario');

-- 6. SERVICIOS (Precios en USD para El Salvador)
INSERT INTO servicios (id, negocio_id, nombre, descripcion, precio, duracion_minutos, estado) VALUES
-- Negocio 1: El Caballero (San Salvador)
(1, 1, 'Corte de Cabello Clásico', 'Lavado revitalizante, corte personalizado con tijera o máquina y peinado con pomada mate.', 8.00, 45, 'activo'),
(2, 1, 'Afeitado Tradicional con Toalla Caliente', 'Ritual de afeitado con navaja, toalla caliente perfumada y bálsamo calmante.', 6.00, 30, 'activo'),
(3, 1, 'Combo Corte + Barba Premium', 'Servicio completo de corte de cabello y arreglo de barba con perfilado profesional.', 12.00, 60, 'activo'),
(4, 1, 'Perfilado y Tinte de Barba', 'Definición de contornos y cobertura de canas con matizador natural.', 7.00, 30, 'activo'),
-- Negocio 2: Bella Donna (San Benito)
(5, 2, 'Corte y Peinado Dama', 'Diagnóstico capilar, lavado relajante, corte según visagismo y secado profesional.', 15.00, 60, 'activo'),
(6, 2, 'Balayage & Colorimetría Especializada', 'Técnica artesanal de iluminación gradual, incluye matiz y tratamiento protector.', 45.00, 120, 'activo'),
(7, 2, 'Manicura Rusa & Gelish', 'Limpieza profunda de cutículas con torno y esmaltado semipermanente de larga duración.', 12.00, 45, 'activo'),
(8, 2, 'Tratamiento Keratina Hidratante', 'Reconstrucción profunda con keratina pura para eliminar frizz y aportar brillo espejo.', 25.00, 60, 'activo'),
-- Negocio 3: Vintage Blade Studio (Santa Tecla)
(9, 3, 'Corte Ejecutivo & Fade', 'Corte con desvanecido a navaja, lavado revitalizante y peinado con cera.', 9.00, 45, 'activo'),
(10, 3, 'Ritual Barba Spa', 'Vapor de ozono, exfoliación cutánea y perfilado de barba con toalla fría.', 10.00, 45, 'activo');

-- 7. PROFESIONALES
INSERT INTO profesionales (id, negocio_id, usuario_id, nombre, especialidad, telefono, estado) VALUES
(1, 1, 4, 'Carlos Méndez', 'Master Barber & Afeitado Clásico', '7210-7788', 'activo'),
(2, 1, 5, 'David López', 'Especialista en Fades & Diseños', '7350-9900', 'activo'),
(3, 2, 6, 'Sofía Ramírez', 'Estilista Senior & Balayage', '7490-1234', 'activo'),
(4, 2, 7, 'Elena Gómez', 'Colorista & Manicurista Spa', '7580-5678', 'activo'),
(5, 3, 5, 'David López', 'Cortes Clásicos & Barba', '7350-9900', 'activo');

-- 8. ASIGNACIÓN PROFESIONAL - SERVICIOS
INSERT INTO profesional_servicios (profesional_id, servicio_id) VALUES
-- Carlos en Negocio 1
(1, 1), (1, 2), (1, 3), (1, 4),
-- David en Negocio 1
(2, 1), (2, 3), (2, 4),
-- Sofía en Negocio 2
(3, 5), (3, 6), (3, 8),
-- Elena en Negocio 2
(4, 5), (4, 7), (4, 8),
-- David en Negocio 3
(5, 9), (5, 10);

-- 9. HORARIOS DE NEGOCIOS (1=Lunes .. 7=Domingo)
-- Negocio 1: El Caballero (Lun-Sáb 09:00-20:00, Dom 10:00-16:00)
INSERT INTO horarios_negocio (negocio_id, dia_semana, hora_apertura, hora_cierre, es_laborable) VALUES
(1, 1, '09:00:00', '20:00:00', 1),
(1, 2, '09:00:00', '20:00:00', 1),
(1, 3, '09:00:00', '20:00:00', 1),
(1, 4, '09:00:00', '20:00:00', 1),
(1, 5, '09:00:00', '20:00:00', 1),
(1, 6, '09:00:00', '20:00:00', 1),
(1, 7, '10:00:00', '16:00:00', 1),
-- Negocio 2: Bella Donna (Lun-Sáb 10:00-20:00, Dom Cerrado)
(2, 1, '10:00:00', '20:00:00', 1),
(2, 2, '10:00:00', '20:00:00', 1),
(2, 3, '10:00:00', '20:00:00', 1),
(2, 4, '10:00:00', '20:00:00', 1),
(2, 5, '10:00:00', '20:00:00', 1),
(2, 6, '10:00:00', '20:00:00', 1),
(2, 7, '10:00:00', '14:00:00', 0),
-- Negocio 3: Vintage Blade (Mar-Dom 10:00-19:00, Lun Cerrado)
(3, 1, '10:00:00', '19:00:00', 0),
(3, 2, '10:00:00', '19:00:00', 1),
(3, 3, '10:00:00', '19:00:00', 1),
(3, 4, '10:00:00', '19:00:00', 1),
(3, 5, '10:00:00', '19:00:00', 1),
(3, 6, '10:00:00', '19:00:00', 1),
(3, 7, '10:00:00', '16:00:00', 1);

-- 10. HORARIOS DE PROFESIONALES (con descansos)
INSERT INTO horarios_profesional (profesional_id, dia_semana, hora_inicio, hora_fin, descanso_inicio, descanso_fin, es_laborable) VALUES
(1, 1, '09:00:00', '18:00:00', '14:00:00', '15:00:00', 1),
(1, 2, '09:00:00', '18:00:00', '14:00:00', '15:00:00', 1),
(1, 3, '09:00:00', '18:00:00', '14:00:00', '15:00:00', 1),
(1, 4, '09:00:00', '18:00:00', '14:00:00', '15:00:00', 1),
(1, 5, '09:00:00', '18:00:00', '14:00:00', '15:00:00', 1),
(1, 6, '09:00:00', '16:00:00', '13:00:00', '13:30:00', 1),
(1, 7, '10:00:00', '16:00:00', NULL, NULL, 0),
(2, 1, '11:00:00', '20:00:00', NULL, NULL, 0),
(2, 2, '11:00:00', '20:00:00', '15:00:00', '16:00:00', 1),
(2, 3, '11:00:00', '20:00:00', '15:00:00', '16:00:00', 1),
(2, 4, '11:00:00', '20:00:00', '15:00:00', '16:00:00', 1),
(2, 5, '11:00:00', '20:00:00', '15:00:00', '16:00:00', 1),
(2, 6, '10:00:00', '19:00:00', '14:00:00', '15:00:00', 1),
(2, 7, '10:00:00', '16:00:00', '13:00:00', '13:30:00', 1),
(3, 1, '10:00:00', '19:00:00', '14:00:00', '15:00:00', 1),
(3, 2, '10:00:00', '19:00:00', '14:00:00', '15:00:00', 1),
(3, 3, '10:00:00', '19:00:00', '14:00:00', '15:00:00', 1),
(3, 4, '10:00:00', '19:00:00', '14:00:00', '15:00:00', 1),
(3, 5, '10:00:00', '19:00:00', '14:00:00', '15:00:00', 1),
(3, 6, '10:00:00', '16:00:00', NULL, NULL, 1),
(3, 7, '10:00:00', '14:00:00', NULL, NULL, 0),
(4, 1, '11:00:00', '20:00:00', '15:00:00', '16:00:00', 1),
(4, 2, '11:00:00', '20:00:00', '15:00:00', '16:00:00', 1),
(4, 3, '11:00:00', '20:00:00', '15:00:00', '16:00:00', 1),
(4, 4, '11:00:00', '20:00:00', '15:00:00', '16:00:00', 1),
(4, 5, '11:00:00', '20:00:00', '15:00:00', '16:00:00', 1),
(4, 6, '10:00:00', '18:00:00', '14:00:00', '15:00:00', 1),
(4, 7, '10:00:00', '14:00:00', NULL, NULL, 0),
(5, 1, '10:00:00', '18:00:00', NULL, NULL, 0),
(5, 2, '10:00:00', '18:00:00', '14:00:00', '15:00:00', 1),
(5, 3, '10:00:00', '18:00:00', '14:00:00', '15:00:00', 1),
(5, 4, '10:00:00', '18:00:00', '14:00:00', '15:00:00', 1),
(5, 5, '10:00:00', '18:00:00', '14:00:00', '15:00:00', 1),
(5, 6, '10:00:00', '18:00:00', '14:00:00', '15:00:00', 1),
(5, 7, '10:00:00', '16:00:00', NULL, NULL, 1);

-- 11. EXCEPCIONES DE HORARIO
INSERT INTO excepciones_horario (id, negocio_id, profesional_id, fecha, hora_inicio, hora_fin, tipo, motivo) VALUES
(1, 1, NULL, DATE_ADD(CURRENT_DATE(), INTERVAL 14 DAY), NULL, NULL, 'cierre_total', 'Mantenimiento y remodelación de instalaciones'),
(2, 2, 3, DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY), NULL, NULL, 'cierre_total', 'Permiso por capacitación en estilismo internacional');

-- 12. RESERVAS DEMO (Precios en USD de El Salvador)
INSERT INTO reservas (id, codigo_reserva, negocio_id, profesional_id, servicio_id, cliente_id, fecha, hora_inicio, hora_fin, precio_acordado, duracion_minutos, estado, notas_cliente, notas_internas) VALUES
-- Citas históricas completadas
(1, 'RES-DEMO-001', 1, 1, 1, 8, DATE_SUB(CURRENT_DATE(), INTERVAL 5 DAY), '10:00:00', '10:45:00', 8.00, 45, 'completada', 'Corte con degrade suave', 'Cliente puntual y satisfecho'),
(2, 'RES-DEMO-002', 1, 1, 3, 9, DATE_SUB(CURRENT_DATE(), INTERVAL 4 DAY), '11:00:00', '12:00:00', 12.00, 60, 'completada', 'Combo completo', 'Se aplicó bálsamo de eucalipto'),
(3, 'RES-DEMO-003', 1, 2, 2, 8, DATE_SUB(CURRENT_DATE(), INTERVAL 3 DAY), '12:00:00', '12:30:00', 6.00, 30, 'completada', 'Afeitado tradicional', 'Navaja clásica'),
(4, 'RES-DEMO-004', 2, 3, 5, 9, DATE_SUB(CURRENT_DATE(), INTERVAL 2 DAY), '11:00:00', '12:00:00', 15.00, 60, 'completada', 'Corte y ondas de agua', 'Tratamiento sellador aplicado'),
(5, 'RES-DEMO-005', 2, 4, 7, 8, DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY), '16:00:00', '16:45:00', 12.00, 45, 'completada', 'Manicura rusa nude', 'Esmaltado semipermanente'),
-- Cita para HOY (confirmada)
(6, 'RES-DEMO-006', 1, 1, 1, 8, CURRENT_DATE(), '16:00:00', '16:45:00', 8.00, 45, 'confirmada', 'Cita regular de mantenimiento', 'Listo sillón 1'),
-- Cita para MAÑANA (confirmada)
(7, 'RES-DEMO-007', 2, 3, 8, 9, DATE_ADD(CURRENT_DATE(), INTERVAL 1 DAY), '11:00:00', '12:00:00', 25.00, 60, 'confirmada', 'Tratamiento capilar', 'Lavado previo preparado'),
-- Citas para los próximos días
(8, 'RES-DEMO-008', 1, 2, 3, 8, DATE_ADD(CURRENT_DATE(), INTERVAL 2 DAY), '12:00:00', '13:00:00', 12.00, 60, 'confirmada', 'Preparación para evento familiar', NULL),
(9, 'RES-DEMO-009', 2, 4, 7, 9, DATE_ADD(CURRENT_DATE(), INTERVAL 3 DAY), '17:00:00', '17:45:00', 12.00, 45, 'pendiente', 'Manicura express', NULL),
(10, 'RES-DEMO-010', 1, 1, 2, 8, DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY), '09:30:00', '10:00:00', 6.00, 30, 'cancelada', 'Canceló con anticipación por trabajo', 'Cancelada por el cliente');

-- 13. NOTIFICACIONES ASOCIADAS
INSERT INTO notificaciones (reserva_id, usuario_id, tipo, canal, destinatario, asunto, mensaje, estado, programada_para, enviado_en) VALUES
(1, 8, 'confirmacion', 'log', 'cliente1@reservalocal.test', 'Cita confirmada en Barbería Clásica El Caballero', 'Tu cita de Corte de Cabello Clásico ha sido completada con éxito.', 'enviada', DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY)),
(6, 8, 'confirmacion', 'log', 'cliente1@reservalocal.test', 'Confirmación de cita - Barbería Clásica El Caballero', 'Hola Alejandro Morales, tu cita con Carlos Méndez está confirmada para hoy.', 'enviada', NOW(), NOW()),
(7, 9, 'recordatorio_24h', 'log', 'cliente2@reservalocal.test', 'Recordatorio: Tu cita de mañana en Bella Donna', 'Hola Camila Torres, te recordamos tu cita de Tratamiento Keratina para mañana a las 11:00.', 'pendiente', DATE_ADD(NOW(), INTERVAL 2 HOUR), NULL);

-- 14. AUDITORIA DEMO
INSERT INTO auditoria (usuario_id, accion, entidad, entidad_id, detalles, ip) VALUES
(1, 'SISTEMA_INICIALIZADO', 'sistema', 1, 'Base de datos y datos demo creados con éxito (El Salvador)', '127.0.0.1'),
(2, 'CREAR_NEGOCIO', 'negocios', 1, 'Alta del negocio Barbería Clásica El Caballero (Col. Escalón, San Salvador)', '127.0.0.1'),
(3, 'CREAR_NEGOCIO', 'negocios', 2, 'Alta del negocio Salón & Spa Bella Donna (San Benito, San Salvador)', '127.0.0.1'),
(8, 'CREAR_RESERVA', 'reservas', 6, 'Reserva RES-DEMO-006 creada para servicio Corte de Cabello Clásico', '127.0.0.1');

SET FOREIGN_KEY_CHECKS = 1;
