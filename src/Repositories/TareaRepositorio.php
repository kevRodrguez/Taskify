<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contratos\TareaInterface;
use App\Exceptions\DominioException;
use App\Factories\TareaFactory;
use PDO;

/**
 * Persistencia de tareas (tabla `tareas`, herencia de tabla única).
 *
 * Trabaja solo con TareaInterface: la fábrica decide la clase concreta y cada
 * tarea sabe convertirse en fila (aFila) y cargar sus subtareas, sin
 * condicionales por tipo.
 */
final class TareaRepositorio
{
    private readonly SubtareaRepositorio $subtareas;

    /**
     * [INYECCION-DEPENDENCIAS] La conexión llega desde afuera; el repositorio
     * no sabe cómo se crea (facilita pruebas con otra base).
     */
    public function __construct(private readonly PDO $pdo, ?SubtareaRepositorio $subtareas = null)
    {
        $this->subtareas = $subtareas ?? new SubtareaRepositorio($pdo);
    }

    /**
     * [CRUD-CREATE] Inserta la tarea, le asigna el id generado y lo devuelve.
     */
    public function crear(TareaInterface $tarea): int
    {
        $fila = $tarea->aFila();
        $sql = <<<'SQL'
            INSERT INTO tareas
                (tipo, titulo, descripcion, fecha_creacion, fecha_vencimiento, avance, periodicidad, imagen)
            VALUES
                (:tipo, :titulo, :descripcion, :fecha_creacion, :fecha_vencimiento, :avance, :periodicidad, :imagen)
            SQL;

        // [SEGURIDAD] consulta preparada: los datos viajan como parámetros, nunca concatenados.
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($fila);

        $id = (int) $this->pdo->lastInsertId();
        $tarea->setId($id);

        return $id;
    }

    /**
     * [CRUD-READ] Todas las tareas, con las subtareas de las compuestas cargadas.
     *
     * @return TareaInterface[]
     */
    public function listar(): array
    {
        // [CRUD-READ] SQL fijo, sin datos del usuario: query() es suficiente.
        $stmt = $this->pdo->query('SELECT * FROM tareas ORDER BY fecha_vencimiento, id');

        $tareas = [];
        while ($fila = $stmt->fetch()) {
            $tarea = TareaFactory::desdeFila($fila);
            $this->cargarSubtareas($tarea);
            $tareas[] = $tarea;
        }

        return $tareas;
    }

    /**
     * [CRUD-READ] Una tarea por id, o null si no existe.
     */
    public function buscarPorId(int $id): ?TareaInterface
    {
        // [SEGURIDAD] consulta preparada
        $stmt = $this->pdo->prepare('SELECT * FROM tareas WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();

        if ($fila === false) {
            return null;
        }

        $tarea = TareaFactory::desdeFila($fila);
        $this->cargarSubtareas($tarea);

        return $tarea;
    }

    /**
     * [CRUD-UPDATE] Actualiza los datos editables. El tipo y la fecha de
     * creación no cambian nunca.
     *
     * @throws DominioException Si no existe una tarea con ese id.
     */
    public function actualizar(int $id, TareaInterface $tarea): void
    {
        $fila = $tarea->aFila();
        unset($fila['tipo'], $fila['fecha_creacion']);
        $fila['id'] = $id;

        $sql = <<<'SQL'
            UPDATE tareas SET
                titulo = :titulo,
                descripcion = :descripcion,
                fecha_vencimiento = :fecha_vencimiento,
                avance = :avance,
                periodicidad = :periodicidad,
                imagen = :imagen
            WHERE id = :id
            SQL;

        // [SEGURIDAD] consulta preparada
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($fila);

        if ($stmt->rowCount() === 0 && !$this->existe($id)) {
            throw new DominioException('La tarea que intenta actualizar no existe.');
        }
    }

    /**
     * [CRUD-DELETE] Elimina la tarea; las filas de `subtareas` que la
     * referencian se borran por ON DELETE CASCADE (las tareas hijas se conservan).
     */
    public function eliminar(int $id): void
    {
        // [SEGURIDAD] consulta preparada
        $stmt = $this->pdo->prepare('DELETE FROM tareas WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function existe(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM tareas WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() !== false;
    }

    /**
     * Carga recursivamente las subtareas con SubtareaRepositorio para que
     * calcularAvance() funcione de forma polimórfica (Composite).
     *
     * Pregunta a la tarea si admite subtareas en lugar de revisar su tipo.
     *
     * @param array<int, true> $enCurso ids ya visitados en esta rama (defensa ante ciclos)
     */
    private function cargarSubtareas(TareaInterface $tarea, array $enCurso = []): void
    {
        $id = $tarea->getId();
        if (!$tarea->admiteSubtareas() || $id === null || isset($enCurso[$id])) {
            return;
        }

        $enCurso[$id] = true;
        foreach ($this->subtareas->listarPorPadre($id) as $hija) {
            $this->cargarSubtareas($hija, $enCurso);
            $tarea->agregarSubtarea($hija);
        }
    }
}
