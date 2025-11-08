CREATE DATABASE IF NOT EXISTS BDD CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE BDD;

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
);

INSERT INTO roles (nombre) VALUES ('Paciente'), ('Doctor'), ('Administrador');

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_rol INT NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    clave VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    correo VARCHAR(100) UNIQUE,
    telefono VARCHAR(20),
    direccion VARCHAR(255),
    cedula VARCHAR(100) NOT NULL UNIQUE,
    fecha_nacimiento DATE,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rol) REFERENCES roles(id) ON DELETE RESTRICT,
    INDEX idx_rol (id_rol),
    INDEX idx_nombre_apellido (nombre, apellido),
    INDEX idx_fecha_nacimiento (fecha_nacimiento)
);

CREATE TABLE especialidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE dias_semana (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(20) NOT NULL UNIQUE,
    numero_dia TINYINT NOT NULL UNIQUE
);

INSERT INTO dias_semana (nombre, numero_dia) VALUES 
('Lunes', 1),
('Martes', 2),
('Miércoles', 3),
('Jueves', 4),
('Viernes', 5),
('Sábado', 6),
('Domingo', 7);

CREATE TABLE horas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hora TIME NOT NULL UNIQUE
);

INSERT INTO horas (hora) VALUES
('08:00:00'),
('09:00:00'),
('10:00:00'),
('11:00:00'),
('12:00:00'),
('13:00:00'),
('14:00:00');

CREATE TABLE doctores (
    id INT PRIMARY KEY,
    id_especialidad INT NOT NULL,
    FOREIGN KEY (id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (id_especialidad) REFERENCES especialidades(id) ON DELETE RESTRICT,
    INDEX idx_especialidad (id_especialidad)
);

CREATE TABLE horarios_doctor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_doctor INT NOT NULL,
    id_dia_semana INT NOT NULL,
    id_hora_inicio INT NOT NULL,
    id_hora_fin INT NOT NULL,
    FOREIGN KEY (id_doctor) REFERENCES doctores(id) ON DELETE CASCADE,
    FOREIGN KEY (id_dia_semana) REFERENCES dias_semana(id) ON DELETE RESTRICT,
    FOREIGN KEY (id_hora_inicio) REFERENCES horas(id) ON DELETE RESTRICT,
    FOREIGN KEY (id_hora_fin) REFERENCES horas(id) ON DELETE RESTRICT,
    INDEX idx_doctor (id_doctor),
    INDEX idx_dia_semana (id_dia_semana),
    INDEX idx_hora_inicio (id_hora_inicio),
    INDEX idx_hora_fin (id_hora_fin)
);

CREATE TABLE listas_espera (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE lista_espera_inscripciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_lista INT NOT NULL,
    id_usuario INT NOT NULL,
    numero INT NOT NULL,
    estado ENUM('Pendiente','Atendido','No asistió') DEFAULT 'Pendiente',
    fecha_inscripcion DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY u_lista_usuario (id_lista, id_usuario),
    FOREIGN KEY (id_lista) REFERENCES listas_espera(id) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_lista (id_lista),
    INDEX idx_usuario (id_usuario),
    INDEX idx_estado (estado),
    INDEX idx_fecha_inscripcion (fecha_inscripcion)
);

CREATE TABLE consultas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_paciente INT NOT NULL,
    id_doctor INT NOT NULL,
    fecha_consulta DATETIME NOT NULL,
    diagnostico TEXT,
    receta TEXT,
    observaciones TEXT,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_paciente) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (id_doctor) REFERENCES doctores(id) ON DELETE CASCADE,
    INDEX idx_paciente (id_paciente),
    INDEX idx_doctor (id_doctor),
    INDEX idx_fecha_consulta (fecha_consulta),
    INDEX idx_fecha_creacion (fecha_creacion)
);

INSERT INTO usuarios (id_rol, usuario, clave, nombre, apellido, correo, telefono, cedula, fecha_nacimiento) VALUES 
(3, 'admin', '$2y$10$pFAtEttclX7vM1QM6.aPdex7mx.Iwqpkws6trYRzqav19a3uFRkPS', 'Administrador', 'Sistema', 'admin@sistema.com', '0000000000', '0000000000', '2025-01-01');

--usuario: admin
-- Password: admin-12345