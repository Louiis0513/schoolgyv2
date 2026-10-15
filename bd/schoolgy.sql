-- Schoolgy · Base de datos del proyecto productivo
-- Ficha 3260309 · HTML + CSS + PHP + MySQL (XAMPP)
-- Importar en phpMyAdmin.

CREATE DATABASE IF NOT EXISTS schoolgy
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE schoolgy;

-- ---------------------------------------------------------
-- 1. USUARIO
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuario (
  id_usuario     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre         VARCHAR(100) NOT NULL,
  correo         VARCHAR(150) NOT NULL,
  contrasena     VARCHAR(255) NOT NULL,
  puntos_totales INT NOT NULL DEFAULT 0,
  rol            ENUM('estudiante','administrador') NOT NULL DEFAULT 'estudiante',
  creado_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_usuario_correo (correo)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 2. MATERIA (pertenece a un usuario)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS materia (
  id_materia INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT UNSIGNED NOT NULL,
  nombre     VARCHAR(80) NOT NULL,
  color      VARCHAR(20) NOT NULL DEFAULT '#6366f1',
  CONSTRAINT fk_materia_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 3. TAREA
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS tarea (
  id_tarea       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  titulo         VARCHAR(150) NOT NULL,
  descripcion    TEXT NULL,
  fecha_entrega  DATETIME NOT NULL,
  fecha_aviso    DATETIME NULL,
  prioridad      ENUM('alta','media','baja') NOT NULL DEFAULT 'media',
  estado         ENUM('pendiente','completada') NOT NULL DEFAULT 'pendiente',
  puntos         INT NOT NULL DEFAULT 10,
  id_usuario     INT UNSIGNED NOT NULL,
  id_materia     INT UNSIGNED NOT NULL,
  completada_en  DATETIME NULL,
  CONSTRAINT fk_tarea_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_tarea_materia
    FOREIGN KEY (id_materia) REFERENCES materia (id_materia)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Datos de ejemplo (contraseña: alumno123)
-- ---------------------------------------------------------
INSERT INTO usuario (nombre, correo, contrasena, puntos_totales, rol) VALUES
(
  'Sara Rivillas',
  'sara@schoolgy.com',
  '$2y$10$6vXd1LuBkCg/nzNDalmPTeUxfy5btyHrpSxvpk3uIUa6edM7YW1ZG',
  40,
  'estudiante'
);

SET @uid = LAST_INSERT_ID();

INSERT INTO materia (id_usuario, nombre, color) VALUES
(@uid, 'Matemáticas', '#6366f1'),
(@uid, 'Español', '#22c55e'),
(@uid, 'Ciencias', '#f59e0b');

INSERT INTO tarea (titulo, descripcion, fecha_entrega, fecha_aviso, prioridad, estado, puntos, id_usuario, id_materia) VALUES
(
  'Taller de derivadas',
  'Resolver ejercicios 1 al 10 del libro.',
  DATE_ADD(NOW(), INTERVAL 2 DAY),
  DATE_ADD(NOW(), INTERVAL 1 DAY),
  'alta',
  'pendiente',
  30,
  @uid,
  (SELECT id_materia FROM materia WHERE id_usuario = @uid AND nombre = 'Matemáticas' LIMIT 1)
),
(
  'Ensayo de lectura',
  'Resumen del capítulo 3.',
  DATE_ADD(NOW(), INTERVAL 5 DAY),
  NULL,
  'media',
  'pendiente',
  20,
  @uid,
  (SELECT id_materia FROM materia WHERE id_usuario = @uid AND nombre = 'Español' LIMIT 1)
),
(
  'Informe de laboratorio',
  'Experimento de densidad.',
  DATE_SUB(NOW(), INTERVAL 1 DAY),
  NULL,
  'baja',
  'pendiente',
  10,
  @uid,
  (SELECT id_materia FROM materia WHERE id_usuario = @uid AND nombre = 'Ciencias' LIMIT 1)
),
(
  'Quiz de fracciones',
  'Práctica corta.',
  DATE_SUB(NOW(), INTERVAL 3 DAY),
  NULL,
  'media',
  'completada',
  20,
  @uid,
  (SELECT id_materia FROM materia WHERE id_usuario = @uid AND nombre = 'Matemáticas' LIMIT 1)
);
