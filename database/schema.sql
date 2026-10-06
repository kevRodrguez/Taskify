-- Taskify · Fase 2 · Esquema relacional (MySQL / MariaDB)
-- Jerarquía de tareas: tabla única con columna `tipo` (Single Table Inheritance).

-- Los scripts están en UTF-8: sin esto, el cliente mysql de Windows daña los acentos.
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS taskify
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE taskify;

DROP TABLE IF EXISTS subtareas;
DROP TABLE IF EXISTS tareas;

CREATE TABLE tareas (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tipo              ENUM('simple', 'compuesta', 'recurrente') NOT NULL,
    titulo            VARCHAR(120) NOT NULL,
    descripcion       TEXT NULL,
    fecha_creacion    DATE NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    avance            TINYINT UNSIGNED NULL,
    periodicidad      ENUM('Diaria', 'Semanal', 'Mensual') NULL,
    imagen            VARCHAR(64) NULL,
    PRIMARY KEY (id),
    INDEX idx_tareas_tipo (tipo),
    INDEX idx_tareas_vencimiento (fecha_vencimiento),
    CONSTRAINT chk_tareas_titulo CHECK (CHAR_LENGTH(TRIM(titulo)) > 0),
    CONSTRAINT chk_tareas_avance CHECK (avance IS NULL OR avance BETWEEN 0 AND 100),
    CONSTRAINT chk_tareas_fechas CHECK (fecha_vencimiento >= fecha_creacion)
) ENGINE=InnoDB;

CREATE TABLE subtareas (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tarea_padre_id  INT UNSIGNED NOT NULL,
    tarea_hija_id   INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_subtareas_par (tarea_padre_id, tarea_hija_id),
    INDEX idx_subtareas_hija (tarea_hija_id),
    CONSTRAINT fk_subtareas_padre FOREIGN KEY (tarea_padre_id)
        REFERENCES tareas (id) ON DELETE CASCADE,
    CONSTRAINT fk_subtareas_hija FOREIGN KEY (tarea_hija_id)
        REFERENCES tareas (id) ON DELETE CASCADE,
    CONSTRAINT chk_subtareas_distintas CHECK (tarea_padre_id <> tarea_hija_id)
) ENGINE=InnoDB;
