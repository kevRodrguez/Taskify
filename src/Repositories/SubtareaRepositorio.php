<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contratos\TareaInterface;
use App\Exceptions\DominioException;
use App\Factories\TareaFactory;
use App\Tareas\ReglasSubtarea;
use PDO;
use PDOException;

/**
 * Persistencia de la relación subtareas (tarea compuesta → tarea hija).
 */
final class SubtareaRepositorio
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * [CRUD-CREATE] Asigna una tarea existente como subtarea de otra.
     *
     * @throws DominioException Si viola reglas de negocio (tipo, ciclo, duplicado).
     */
    public function agregar(int $padreId, int $hijaId): void
    {
        ReglasSubtarea::exigirPadreDistintoDeHija($padreId, $hijaId);

        $tipoPadre = $this->obtenerTipo($padreId);
        if ($tipoPadre === null) {
            throw new DominioException('La tarea padre no existe.');
        }

        ReglasSubtarea::exigirPadreCompuesto($tipoPadre);

        if ($this->obtenerTipo($hijaId) === null) {
            throw new DominioException('La tarea hija no existe.');
        }

        if ($this->creariaCiclo($padreId, $hijaId)) {
            throw new DominioException('No se puede asignar: crearía un ciclo entre tareas.');
        }

        $sql = 'INSERT INTO subtareas (tarea_padre_id, tarea_hija_id) VALUES (:padre, :hija)';

        try {
            // [CRUD-CREATE] consulta preparada; UNIQUE evita duplicados
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['padre' => $padreId, 'hija' => $hijaId]);
        } catch (PDOException $e) {
            if ($this->esViolacionUnica($e)) {
                throw new DominioException('Esa subtarea ya está asignada a esta tarea compuesta.', 0, $e);
            }

            throw $e;
        }
    }

    /**
     * [CRUD-READ] Lista las tareas hijas de una tarea compuesta.
     *
     * @return TareaInterface[]
     */
    public function listarPorPadre(int $padreId): array
    {
        $sql = <<<'SQL'
            SELECT t.*
            FROM subtareas s
            INNER JOIN tareas t ON t.id = s.tarea_hija_id
            WHERE s.tarea_padre_id = :padre
            ORDER BY t.titulo
            SQL;

        // [CRUD-READ] consulta preparada
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['padre' => $padreId]);

        $tareas = [];
        while ($fila = $stmt->fetch()) {
            $tareas[] = TareaFactory::desdeFila($fila);
        }

        return $tareas;
    }

    /** [CRUD-DELETE] Quita la relación padre → hija (no borra la tarea hija). */
    public function quitar(int $padreId, int $hijaId): void
    {
        $sql = 'DELETE FROM subtareas WHERE tarea_padre_id = :padre AND tarea_hija_id = :hija';

        // [CRUD-DELETE] consulta preparada
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['padre' => $padreId, 'hija' => $hijaId]);
    }

    /**
     * Datos mínimos del padre para las vistas de subtareas.
     *
     * @return array{id: int, titulo: string, tipo: string}|null
     */
    public function datosPadre(int $padreId): ?array
    {
        $sql = 'SELECT id, titulo, tipo FROM tareas WHERE id = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $padreId]);
        $fila = $stmt->fetch();

        if ($fila === false) {
            return null;
        }

        return [
            'id' => (int) $fila['id'],
            'titulo' => (string) $fila['titulo'],
            'tipo' => (string) $fila['tipo'],
        ];
    }

    /**
     * Tareas que pueden asignarse como subtarea (excluye padre, ya asignadas y las que crearían ciclo).
     *
     * @return list<array{id: int, titulo: string}>
     */
    public function listarCandidatas(int $padreId): array
    {
        $sql = 'SELECT id, titulo FROM tareas WHERE id <> :padre ORDER BY titulo';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['padre' => $padreId]);

        $candidatas = [];
        while ($fila = $stmt->fetch()) {
            $hijaId = (int) $fila['id'];
            if ($this->yaEsSubtarea($padreId, $hijaId)) {
                continue;
            }
            if ($this->creariaCiclo($padreId, $hijaId)) {
                continue;
            }
            $candidatas[] = [
                'id' => $hijaId,
                'titulo' => (string) $fila['titulo'],
            ];
        }

        return $candidatas;
    }

    private function obtenerTipo(int $id): ?string
    {
        $stmt = $this->pdo->prepare('SELECT tipo FROM tareas WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();

        return $fila === false ? null : (string) $fila['tipo'];
    }

    private function yaEsSubtarea(int $padreId, int $hijaId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM subtareas WHERE tarea_padre_id = :padre AND tarea_hija_id = :hija LIMIT 1'
        );
        $stmt->execute(['padre' => $padreId, 'hija' => $hijaId]);

        return $stmt->fetch() !== false;
    }

    /**
     * Verifica si agregar padre → hija crearía un ciclo (hija ya es ancestro de padre).
     */
    private function creariaCiclo(int $padreId, int $hijaId): bool
    {
        return $this->esDescendiente($hijaId, $padreId);
    }

    private function esDescendiente(int $ancestroId, int $buscadoId): bool
    {
        $cola = [$ancestroId];
        $visitados = [];

        while ($cola !== []) {
            $actual = array_shift($cola);
            if ($actual === $buscadoId) {
                return true;
            }
            if (isset($visitados[$actual])) {
                continue;
            }
            $visitados[$actual] = true;

            $stmt = $this->pdo->prepare(
                'SELECT tarea_hija_id FROM subtareas WHERE tarea_padre_id = :padre'
            );
            $stmt->execute(['padre' => $actual]);

            while ($fila = $stmt->fetch()) {
                $cola[] = (int) $fila['tarea_hija_id'];
            }
        }

        return false;
    }

    private function esViolacionUnica(PDOException $e): bool
    {
        $codigo = $e->errorInfo[1] ?? 0;

        return $codigo === 1062;
    }
}
