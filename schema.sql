-- =============================================
-- FRACCIONAMIENTO ARRYANAES - Base de Datos
-- =============================================

CREATE DATABASE IF NOT EXISTS arryanaes CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE arryanaes;

-- ---------------------------------------------
-- Tabla de usuarios del sistema
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios_sistema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    rol ENUM('admin', 'consulta') NOT NULL DEFAULT 'consulta',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME
);

-- Usuario admin por defecto (password: admin123 - cambiar en producción)
INSERT INTO usuarios_sistema (username, password_hash, nombre, rol) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'admin'),
('consulta', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Usuario Consulta', 'consulta');

-- ---------------------------------------------
-- Tabla de residentes
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS residentes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id_externo INT,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100),
    segundo_nombre VARCHAR(100),
    calle VARCHAR(50) NOT NULL,
    numero_ext VARCHAR(20),
    numero_int VARCHAR(20),
    identificacion VARCHAR(100),
    departamento VARCHAR(50),
    activo TINYINT(1) NOT NULL DEFAULT 1,
    comentario TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ---------------------------------------------
-- Tabla de tags (tarjetas de acceso)
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residente_id INT NOT NULL,
    numero_tag VARCHAR(50) NOT NULL,
    facility_code VARCHAR(20),
    fecha_desde DATETIME,
    fecha_hasta DATETIME,
    access_group VARCHAR(50),
    estatus ENUM('ACTIVO', 'MOROSO', 'INACTIVO') NOT NULL DEFAULT 'ACTIVO',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (residente_id) REFERENCES residentes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_tag (numero_tag)
);

-- ---------------------------------------------
-- Tabla de pagos mensuales por residente
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residente_id INT NOT NULL,
    anio YEAR NOT NULL,
    mes TINYINT NOT NULL CHECK (mes BETWEEN 1 AND 12),
    pagado TINYINT(1) NOT NULL DEFAULT 0,
    fecha_pago DATETIME,
    monto DECIMAL(10,2),
    metodo_pago ENUM('EFECTIVO', 'TRANSFERENCIA', 'CHEQUE', 'OTRO') DEFAULT 'EFECTIVO',
    referencia VARCHAR(100),
    estatus_previo ENUM('ACTIVO', 'MOROSO') COMMENT 'Estatus antes de registrar este pago',
    registrado_por INT,
    notas TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_pago_mes (residente_id, anio, mes),
    FOREIGN KEY (residente_id) REFERENCES residentes(id) ON DELETE CASCADE,
    FOREIGN KEY (registrado_por) REFERENCES usuarios_sistema(id)
);

-- ---------------------------------------------
-- Tabla de historial de cambios de estatus
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS historial_estatus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tag_id INT NOT NULL,
    residente_id INT NOT NULL,
    estatus_anterior ENUM('ACTIVO', 'MOROSO', 'INACTIVO'),
    estatus_nuevo ENUM('ACTIVO', 'MOROSO', 'INACTIVO'),
    motivo VARCHAR(255),
    usuario_id INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tag_id) REFERENCES tags(id),
    FOREIGN KEY (residente_id) REFERENCES residentes(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios_sistema(id)
);

-- ---------------------------------------------
-- Vista útil: Estado actual de residentes
-- ---------------------------------------------
CREATE OR REPLACE VIEW vista_residentes AS
SELECT 
    r.id,
    r.user_id_externo,
    CONCAT(r.nombre, ' ', COALESCE(r.apellidos, '')) AS nombre_completo,
    r.nombre,
    r.apellidos,
    r.calle,
    r.numero_ext,
    r.numero_int,
    r.identificacion,
    r.activo,
    r.comentario,
    COUNT(t.id) AS total_tags,
    SUM(CASE WHEN t.estatus = 'ACTIVO' AND t.activo = 1 THEN 1 ELSE 0 END) AS tags_activos,
    SUM(CASE WHEN t.estatus = 'MOROSO' AND t.activo = 1 THEN 1 ELSE 0 END) AS tags_morosos,
    MAX(t.estatus) AS estatus_general,
    -- Pago del mes actual
    (SELECT pagado FROM pagos p 
     WHERE p.residente_id = r.id 
       AND p.anio = YEAR(CURDATE()) 
       AND p.mes = MONTH(CURDATE()) 
     LIMIT 1) AS pago_mes_actual,
    -- Pago del mes anterior
    (SELECT pagado FROM pagos p 
     WHERE p.residente_id = r.id 
       AND p.anio = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) 
       AND p.mes = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) 
     LIMIT 1) AS pago_mes_anterior
FROM residentes r
LEFT JOIN tags t ON t.residente_id = r.id AND t.activo = 1
WHERE r.activo = 1
GROUP BY r.id;

-- Índices para búsquedas rápidas
CREATE INDEX idx_residentes_calle ON residentes(calle);
CREATE INDEX idx_residentes_nombre ON residentes(nombre);
CREATE INDEX idx_tags_numero ON tags(numero_tag);
CREATE INDEX idx_pagos_mes ON pagos(anio, mes);
CREATE INDEX idx_pagos_residente ON pagos(residente_id);
