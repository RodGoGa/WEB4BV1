-- Crear la base de datos
CREATE DATABASE WEB2;
USE WEB2;

-- Tabla usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    tipo_usuario ENUM('empresa', 'solicitante') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla empresas
CREATE TABLE empresas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    nombre_empresa VARCHAR(100) NOT NULL,
    descripcion TEXT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Tabla solicitantes
CREATE TABLE solicitantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    curriculum_vitae TEXT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Tabla habilidades
CREATE TABLE habilidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) UNIQUE NOT NULL,
    tipo ENUM('predefinida', 'personalizada') NOT NULL DEFAULT 'predefinida'
);

-- Tabla solicitante_habilidades
CREATE TABLE solicitante_habilidades (
    solicitante_id INT NOT NULL,
    habilidad_id INT NOT NULL,
    nivel ENUM('básico', 'intermedio', 'avanzado') DEFAULT 'intermedio',
    PRIMARY KEY (solicitante_id, habilidad_id),
    FOREIGN KEY (solicitante_id) REFERENCES solicitantes(id),
    FOREIGN KEY (habilidad_id) REFERENCES habilidades(id)
);

-- Tabla vacantes
CREATE TABLE vacantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    titulo VARCHAR(100) NOT NULL,
    descripcion TEXT NOT NULL,
    requisitos TEXT,
    estado ENUM('disponible', 'ocupada', 'despublicada') DEFAULT 'disponible',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id)
);

-- Tabla vacante_habilidades
CREATE TABLE vacante_habilidades (
    vacante_id INT NOT NULL,
    habilidad_id INT NOT NULL,
    nivel_requerido ENUM('básico', 'intermedio', 'avanzado') DEFAULT 'intermedio',
    PRIMARY KEY (vacante_id, habilidad_id),
    FOREIGN KEY (vacante_id) REFERENCES vacantes(id),
    FOREIGN KEY (habilidad_id) REFERENCES habilidades(id)
);

-- Tabla postulaciones
CREATE TABLE postulaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    solicitante_id INT NOT NULL,
    vacante_id INT NOT NULL,
    estado ENUM('pendiente', 'aceptada', 'rechazada') DEFAULT 'pendiente',
    fecha_postulacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (solicitante_id) REFERENCES solicitantes(id),
    FOREIGN KEY (vacante_id) REFERENCES vacantes(id)
);


-- Insertar habilidades predefinidas
INSERT INTO habilidades (nombre, tipo) VALUES 
('Programación', 'predefinida'),
('Diseño Gráfico', 'predefinida'),
('Marketing Digital', 'predefinida'),
('Gestión de Proyectos', 'predefinida'),
('Análisis de Datos', 'predefinida'),
('Comunicación', 'predefinida'),
('Trabajo en Equipo', 'predefinida'),
('Resolución de Problemas', 'predefinida'),
('Ventas', 'predefinida'),
('Atención al Cliente', 'predefinida'),
('Idiomas', 'predefinida'),
('Liderazgo', 'predefinida'),
('Creatividad', 'predefinida'),
('Investigación', 'predefinida'),
('Diseño Web', 'predefinida'),
('Desarrollo de Software', 'predefinida'),
('Redes Sociales', 'predefinida'),
('Edición de Video', 'predefinida'),
('Redacción', 'predefinida'),
('Finanzas', 'predefinida');