-- Schoolgy · Base de datos del proyecto productivo
-- Ficha 3260309 · HTML + CSS + PHP + MySQL (XAMPP)
-- Importar en phpMyAdmin o: mysql -u root < bd/schoolgy.sql

DROP DATABASE IF EXISTS schoolgy;
CREATE DATABASE schoolgy
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE schoolgy;

-- ---------------------------------------------------------
-- 1. USUARIO
-- ---------------------------------------------------------
CREATE TABLE usuario (
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
CREATE TABLE materia (
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
CREATE TABLE tarea (
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
-- Datos demo (contraseña: alumno123)
-- Hash: password_hash('alumno123', PASSWORD_DEFAULT)
-- ---------------------------------------------------------
INSERT INTO usuario (nombre, correo, contrasena, puntos_totales, rol) VALUES
(
  'Sara Gabriela Rivillas',
  'sara@schoolgy.com',
  '$2y$10$j6dgU2TRrBJMJxp/9pYDtutVHPHmVdgDnBe7RaEJ2oB/O.w9Jdyke',
  120,
  'estudiante'
);

SET @uid = LAST_INSERT_ID();

INSERT INTO materia (id_usuario, nombre, color) VALUES
(@uid, 'Matemáticas', '#6366f1'),
(@uid, 'Español', '#22c55e'),
(@uid, 'Ciencias Naturales', '#f59e0b'),
(@uid, 'Ciencias Sociales', '#ef4444'),
(@uid, 'Inglés', '#0ea5e9'),
(@uid, 'Tecnología e Informática', '#8b5cf6'),
(@uid, 'Educación Física', '#14b8a6');

SET @mat_mates = (SELECT id_materia FROM materia WHERE id_usuario = @uid AND nombre = 'Matemáticas' LIMIT 1);
SET @mat_esp   = (SELECT id_materia FROM materia WHERE id_usuario = @uid AND nombre = 'Español' LIMIT 1);
SET @mat_cien  = (SELECT id_materia FROM materia WHERE id_usuario = @uid AND nombre = 'Ciencias Naturales' LIMIT 1);
SET @mat_soc   = (SELECT id_materia FROM materia WHERE id_usuario = @uid AND nombre = 'Ciencias Sociales' LIMIT 1);
SET @mat_ing   = (SELECT id_materia FROM materia WHERE id_usuario = @uid AND nombre = 'Inglés' LIMIT 1);
SET @mat_tec   = (SELECT id_materia FROM materia WHERE id_usuario = @uid AND nombre = 'Tecnología e Informática' LIMIT 1);
SET @mat_ef    = (SELECT id_materia FROM materia WHERE id_usuario = @uid AND nombre = 'Educación Física' LIMIT 1);

INSERT INTO tarea
  (titulo, descripcion, fecha_entrega, fecha_aviso, prioridad, estado, puntos, id_usuario, id_materia, completada_en)
VALUES
-- Pendientes próximas
(
  'Taller de derivadas',
  'Resolver ejercicios 1 al 12 del capítulo 4 (regla de la cadena y producto). Entregar en PDF o cuaderno escaneado.',
  DATE_ADD(NOW(), INTERVAL 2 DAY),
  DATE_ADD(NOW(), INTERVAL 1 DAY),
  'alta', 'pendiente', 30, @uid, @mat_mates, NULL
),
(
  'Ensayo de lectura crítica',
  'Redactar un ensayo de 800 palabras sobre el capítulo 3 de “Cien años de soledad”. Incluir introducción, desarrollo y conclusión.',
  DATE_ADD(NOW(), INTERVAL 5 DAY),
  DATE_ADD(NOW(), INTERVAL 3 DAY),
  'media', 'pendiente', 20, @uid, @mat_esp, NULL
),
(
  'Informe de laboratorio: densidad',
  'Documentar el experimento de densidad con agua, aceite y objetos. Incluir hipótesis, materiales, procedimiento y conclusiones.',
  DATE_ADD(NOW(), INTERVAL 4 DAY),
  DATE_ADD(NOW(), INTERVAL 2 DAY),
  'alta', 'pendiente', 30, @uid, @mat_cien, NULL
),
(
  'Mapa de la Independencia',
  'Elaborar un mapa conceptual de las causas internas y externas de la Independencia de Colombia.',
  DATE_ADD(NOW(), INTERVAL 7 DAY),
  NULL,
  'media', 'pendiente', 20, @uid, @mat_soc, NULL
),
(
  'Oral presentation: My routine',
  'Preparar una presentación oral de 3 minutos describiendo tu rutina diaria en inglés. Incluir vocabulario de frecuencia.',
  DATE_ADD(NOW(), INTERVAL 6 DAY),
  DATE_ADD(NOW(), INTERVAL 4 DAY),
  'media', 'pendiente', 20, @uid, @mat_ing, NULL
),
(
  'Prototipo HTML + CSS',
  'Crear una landing page responsive de un producto escolar (mínimo 3 secciones) y subir el ZIP al aula virtual.',
  DATE_ADD(NOW(), INTERVAL 8 DAY),
  DATE_ADD(NOW(), INTERVAL 5 DAY),
  'alta', 'pendiente', 30, @uid, @mat_tec, NULL
),
(
  'Plan de entrenamiento semanal',
  'Diseñar un plan de 5 días con ejercicios de resistencia, fuerza y flexibilidad. Entregar en formato tabla.',
  DATE_ADD(NOW(), INTERVAL 10 DAY),
  NULL,
  'baja', 'pendiente', 10, @uid, @mat_ef, NULL
),
-- Vencidas (pendientes pasadas → prioridad efectiva alta en la UI)
(
  'Quiz de fracciones y proporciones',
  'Práctica corta de 15 ítems. Entrega retrasada: completar cuanto antes.',
  DATE_SUB(NOW(), INTERVAL 1 DAY),
  DATE_SUB(NOW(), INTERVAL 2 DAY),
  'media', 'pendiente', 20, @uid, @mat_mates, NULL
),
(
  'Guía de células y tejidos',
  'Responder la guía de 10 preguntas sobre tipos de células y funciones de tejidos.',
  DATE_SUB(NOW(), INTERVAL 3 DAY),
  NULL,
  'baja', 'pendiente', 10, @uid, @mat_cien, NULL
),
-- Completadas (suman los 120 puntos del usuario demo)
(
  'Taller de ecuaciones lineales',
  'Resolver el sistema de ecuaciones del taller 2. Entregado a tiempo.',
  DATE_SUB(NOW(), INTERVAL 12 DAY),
  NULL,
  'alta', 'completada', 30, @uid, @mat_mates,
  DATE_SUB(NOW(), INTERVAL 13 DAY)
),
(
  'Resumen de crónica periodística',
  'Resumen de 400 palabras de una crónica local, con ficha técnica del autor.',
  DATE_SUB(NOW(), INTERVAL 9 DAY),
  NULL,
  'media', 'completada', 20, @uid, @mat_esp,
  DATE_SUB(NOW(), INTERVAL 10 DAY)
),
(
  'Listening: School day',
  'Completar el worksheet de listening del Unit 2 y subir capturas de respuestas.',
  DATE_SUB(NOW(), INTERVAL 7 DAY),
  NULL,
  'media', 'completada', 20, @uid, @mat_ing,
  DATE_SUB(NOW(), INTERVAL 8 DAY)
),
(
  'Infografía de los biomas',
  'Diseñar una infografía digital de 4 biomas colombianos con flora y fauna representativa.',
  DATE_SUB(NOW(), INTERVAL 15 DAY),
  NULL,
  'alta', 'completada', 30, @uid, @mat_cien,
  DATE_SUB(NOW(), INTERVAL 16 DAY)
),
(
  'Línea de tiempo: Nueva Granada',
  'Línea de tiempo ilustrada desde 1810 hasta 1819 con 8 hitos clave.',
  DATE_SUB(NOW(), INTERVAL 18 DAY),
  NULL,
  'media', 'completada', 20, @uid, @mat_soc,
  DATE_SUB(NOW(), INTERVAL 19 DAY)
);

-- Usuario secundario (cuenta limpia para mostrar registro/flujo vacío)
INSERT INTO usuario (nombre, correo, contrasena, puntos_totales, rol) VALUES
(
  'Demo Estudiante',
  'demo@schoolgy.com',
  '$2y$10$j6dgU2TRrBJMJxp/9pYDtutVHPHmVdgDnBe7RaEJ2oB/O.w9Jdyke',
  0,
  'estudiante'
);
