-- Base de datos: reserva_local
-- Motor: InnoDB, Charset: utf8mb4_unicode_ci

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS auditoria;
DROP TABLE IF EXISTS notificaciones;
DROP TABLE IF EXISTS reservas;
DROP TABLE IF EXISTS excepciones_horario;
DROP TABLE IF EXISTS horarios_profesional;
DROP TABLE IF EXISTS horarios_negocio;
DROP TABLE IF EXISTS profesional_servicios;
DROP TABLE IF EXISTS profesionales;
DROP TABLE IF EXISTS servicios;
DROP TABLE IF EXISTS negocio_usuarios;
DROP TABLE IF EXISTS negocios;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS usuario_roles;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS roles;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. ROLES
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. USUARIOS
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    telefono VARCHAR(30) NULL,
    estado ENUM('activo', 'inactivo', 'bloqueado') NOT NULL DEFAULT 'activo',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_usuarios_email (email),
    INDEX idx_usuarios_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. USUARIO_ROLES (un usuario puede tener varios roles)
CREATE TABLE usuario_roles (
    usuario_id INT NOT NULL,
    rol_id INT NOT NULL,
    PRIMARY KEY (usuario_id, rol_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TOKENS DE RECUPERACIÓN DE CONTRASEÑA
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    token_hash VARCHAR(64) NOT NULL UNIQUE,
    expira_en DATETIME NOT NULL,
    usado_en DATETIME NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_pw_reset_hash (token_hash),
    INDEX idx_pw_reset_exp (expira_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. INTENTOS DE LOGIN (control de fuerza bruta)
CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    email VARCHAR(150) NOT NULL,
    intentos INT NOT NULL DEFAULT 1,
    bloqueado_hasta DATETIME NULL,
    ultimo_intento TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_login_attempts (ip, email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. NEGOCIOS
CREATE TABLE negocios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    categoria ENUM('barberia', 'salon') NOT NULL,
    descripcion TEXT NULL,
    direccion VARCHAR(255) NOT NULL,
    zona_municipio VARCHAR(100) NOT NULL,
    latitud DECIMAL(10, 7) NOT NULL,
    longitud DECIMAL(10, 7) NOT NULL,
    telefono VARCHAR(30) NULL,
    email VARCHAR(150) NULL,
    zona_horaria VARCHAR(50) NOT NULL DEFAULT 'America/El_Salvador',
    imagen_url VARCHAR(255) NULL,
    estado ENUM('activo', 'inactivo', 'pendiente') NOT NULL DEFAULT 'activo',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_negocios_cat (categoria),
    INDEX idx_negocios_estado (estado),
    INDEX idx_negocios_zona (zona_municipio),
    INDEX idx_negocios_coords (latitud, longitud)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. NEGOCIO_USUARIOS (propietarios y administradores del negocio)
CREATE TABLE negocio_usuarios (
    negocio_id INT NOT NULL,
    usuario_id INT NOT NULL,
    rol ENUM('propietario', 'administrador_local') NOT NULL DEFAULT 'propietario',
    PRIMARY KEY (negocio_id, usuario_id),
    FOREIGN KEY (negocio_id) REFERENCES negocios(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. SERVICIOS
CREATE TABLE servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    negocio_id INT NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    descripcion TEXT NULL,
    precio DECIMAL(10, 2) NOT NULL,
    duracion_minutos INT NOT NULL,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (negocio_id) REFERENCES negocios(id) ON DELETE CASCADE,
    INDEX idx_servicios_negocio (negocio_id),
    INDEX idx_servicios_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. PROFESIONALES
CREATE TABLE profesionales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    negocio_id INT NOT NULL,
    usuario_id INT NULL,
    nombre VARCHAR(120) NOT NULL,
    especialidad VARCHAR(100) NULL,
    telefono VARCHAR(30) NULL,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (negocio_id) REFERENCES negocios(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_profesionales_negocio (negocio_id),
    INDEX idx_profesionales_usuario (usuario_id),
    INDEX idx_profesionales_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. PROFESIONAL_SERVICIOS
CREATE TABLE profesional_servicios (
    profesional_id INT NOT NULL,
    servicio_id INT NOT NULL,
    PRIMARY KEY (profesional_id, servicio_id),
    FOREIGN KEY (profesional_id) REFERENCES profesionales(id) ON DELETE CASCADE,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. HORARIOS_NEGOCIO (1=Lunes, 7=Domingo)
CREATE TABLE horarios_negocio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    negocio_id INT NOT NULL,
    dia_semana TINYINT NOT NULL COMMENT '1=Lunes, 2=Martes, ..., 7=Domingo',
    hora_apertura TIME NOT NULL,
    hora_cierre TIME NOT NULL,
    es_laborable TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_negocio_dia (negocio_id, dia_semana),
    FOREIGN KEY (negocio_id) REFERENCES negocios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. HORARIOS_PROFESIONAL (1=Lunes, 7=Domingo)
CREATE TABLE horarios_profesional (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profesional_id INT NOT NULL,
    dia_semana TINYINT NOT NULL COMMENT '1=Lunes, 2=Martes, ..., 7=Domingo',
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    descanso_inicio TIME NULL,
    descanso_fin TIME NULL,
    es_laborable TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_profesional_dia (profesional_id, dia_semana),
    FOREIGN KEY (profesional_id) REFERENCES profesionales(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. EXCEPCIONES_HORARIO (festivos, cierres especiales, permisos)
CREATE TABLE excepciones_horario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    negocio_id INT NOT NULL,
    profesional_id INT NULL,
    fecha DATE NOT NULL,
    hora_inicio TIME NULL,
    hora_fin TIME NULL,
    tipo ENUM('cierre_total', 'horario_especial') NOT NULL DEFAULT 'cierre_total',
    motivo VARCHAR(255) NULL,
    FOREIGN KEY (negocio_id) REFERENCES negocios(id) ON DELETE CASCADE,
    FOREIGN KEY (profesional_id) REFERENCES profesionales(id) ON DELETE CASCADE,
    INDEX idx_excepciones_negocio_fecha (negocio_id, fecha),
    INDEX idx_excepciones_prof_fecha (profesional_id, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. RESERVAS
CREATE TABLE reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_reserva VARCHAR(25) NOT NULL UNIQUE,
    negocio_id INT NOT NULL,
    profesional_id INT NOT NULL,
    servicio_id INT NOT NULL,
    cliente_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    precio_acordado DECIMAL(10, 2) NOT NULL,
    duracion_minutos INT NOT NULL,
    estado ENUM('pendiente', 'confirmada', 'cancelada', 'completada', 'no_asistio') NOT NULL DEFAULT 'confirmada',
    notas_cliente TEXT NULL,
    notas_internas TEXT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (negocio_id) REFERENCES negocios(id) ON DELETE RESTRICT,
    FOREIGN KEY (profesional_id) REFERENCES profesionales(id) ON DELETE RESTRICT,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE RESTRICT,
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX idx_reservas_negocio_fecha (negocio_id, fecha),
    INDEX idx_reservas_prof_fecha (profesional_id, fecha),
    INDEX idx_reservas_cliente (cliente_id),
    INDEX idx_reservas_estado (estado),
    INDEX idx_reservas_solapamiento (profesional_id, fecha, hora_inicio, hora_fin, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. NOTIFICACIONES
CREATE TABLE notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reserva_id INT NOT NULL,
    usuario_id INT NOT NULL,
    tipo ENUM('confirmacion', 'recordatorio_24h', 'recordatorio_2h', 'cancelacion', 'cambio_estado') NOT NULL,
    canal ENUM('log', 'email') NOT NULL DEFAULT 'log',
    destinatario VARCHAR(150) NOT NULL,
    asunto VARCHAR(200) NOT NULL,
    mensaje TEXT NOT NULL,
    estado ENUM('pendiente', 'enviada', 'fallida') NOT NULL DEFAULT 'pendiente',
    intentos INT NOT NULL DEFAULT 0,
    error_mensaje TEXT NULL,
    programada_para DATETIME NOT NULL,
    enviado_en DATETIME NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reserva_id) REFERENCES reservas(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_notif_cola (estado, programada_para),
    INDEX idx_notif_reserva (reserva_id, tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. AUDITORIA
CREATE TABLE auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    accion VARCHAR(60) NOT NULL,
    entidad VARCHAR(50) NOT NULL,
    entidad_id INT NULL,
    detalles TEXT NULL,
    ip VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_auditoria_usuario (usuario_id),
    INDEX idx_auditoria_accion (accion),
    INDEX idx_auditoria_entidad (entidad, entidad_id),
    INDEX idx_auditoria_fecha (creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
