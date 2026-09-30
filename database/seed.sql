-- Taskify · Fase 2 · Datos de prueba (ejecutar después de schema.sql)

USE taskify;

-- Tareas simples (avance manual)
INSERT INTO tareas (id, tipo, titulo, descripcion, fecha_creacion, fecha_vencimiento, avance, periodicidad, imagen) VALUES
(1, 'simple', 'Redactar propuesta del proyecto', 'Documento con el caso de estudio y el modelo de clases.', '2026-08-01', '2026-08-10', 100, NULL, NULL),
(2, 'simple', 'Diseñar modelo relacional', 'Diagrama entidad-relación y justificación de la herencia.', '2026-09-01', '2026-09-15', 60, NULL, NULL),
(3, 'simple', 'Preparar exposición final', 'Diapositivas y demo en vivo.', '2026-09-10', '2026-10-05', 0, NULL, NULL);

-- Tareas compuestas (su avance se calcula con las subtareas)
INSERT INTO tareas (id, tipo, titulo, descripcion, fecha_creacion, fecha_vencimiento, avance, periodicidad, imagen) VALUES
(4, 'compuesta', 'Lanzar sitio web', 'Proyecto que agrupa diseño, desarrollo y pruebas.', '2026-09-01', '2026-10-30', NULL, NULL, NULL),
(5, 'compuesta', 'Preparar examen parcial', 'Repaso de los temas de PHP y bases de datos.', '2026-09-05', '2026-10-01', NULL, NULL, NULL),
(6, 'compuesta', 'Organizar evento de bienvenida', 'Logística, invitaciones y presupuesto.', '2026-09-08', '2026-11-15', NULL, NULL, NULL);

-- Tareas recurrentes (avance del ciclo actual + periodicidad)
INSERT INTO tareas (id, tipo, titulo, descripcion, fecha_creacion, fecha_vencimiento, avance, periodicidad, imagen) VALUES
(7, 'recurrente', 'Reunión de seguimiento', 'Revisión semanal del avance del equipo.', '2026-09-01', '2026-10-06', 25, 'Semanal', NULL),
(8, 'recurrente', 'Respaldo de la base de datos', 'Exportar y guardar el respaldo diario.', '2026-09-01', '2026-10-01', 0, 'Diaria', NULL),
(9, 'recurrente', 'Informe mensual de avances', 'Resumen para la docente.', '2026-09-01', '2026-10-31', 50, 'Mensual', NULL);

-- Tareas hijas de las compuestas
INSERT INTO tareas (id, tipo, titulo, descripcion, fecha_creacion, fecha_vencimiento, avance, periodicidad, imagen) VALUES
(10, 'simple', 'Maquetar páginas HTML', 'Estructura semántica de todas las vistas.', '2026-09-02', '2026-09-20', 100, NULL, NULL),
(11, 'simple', 'Escribir hoja de estilos', 'CSS propio con variables y diseño adaptable.', '2026-09-02', '2026-09-25', 50, NULL, NULL),
(12, 'simple', 'Probar formularios', 'Validación en cliente y servidor.', '2026-09-02', '2026-10-10', 0, NULL, NULL),
(13, 'simple', 'Repasar POO', 'Abstracción, encapsulamiento, herencia y polimorfismo.', '2026-09-06', '2026-09-25', 80, NULL, NULL),
(14, 'simple', 'Practicar consultas PDO', 'Consultas preparadas y repositorios.', '2026-09-06', '2026-09-28', 40, NULL, NULL),
(15, 'simple', 'Reservar el salón', 'Confirmar fecha y lugar.', '2026-09-09', '2026-10-01', 100, NULL, NULL),
(16, 'simple', 'Enviar invitaciones', 'Correo a estudiantes y docentes.', '2026-09-09', '2026-10-20', 0, NULL, NULL);

INSERT INTO subtareas (tarea_padre_id, tarea_hija_id) VALUES
(4, 10), (4, 11), (4, 12),
(5, 13), (5, 14),
(6, 15), (6, 16);
