<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Modelo base con helpers sobre PDO. Los modelos concretos definen $table.
 */
abstract class Model
{
    protected static string $table = '';

    protected static function db(): PDO
    {
        return Database::connection();
    }

    /** @return array<int,array<string,mixed>> */
    public static function all(string $orderBy = 'id DESC'): array
    {
        $stmt = static::db()->query('SELECT * FROM ' . static::$table . ' ORDER BY ' . $orderBy);
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        $stmt = static::db()->prepare('SELECT * FROM ' . static::$table . ' WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * @param array<string,mixed> $where
     * @return array<string,mixed>|null
     */
    public static function findBy(array $where): ?array
    {
        $rows = static::where($where, '', 1);
        return $rows[0] ?? null;
    }

    /**
     * Consulta con filtros de igualdad simple.
     * @param array<string,mixed> $where
     * @return array<int,array<string,mixed>>
     */
    public static function where(array $where, string $orderBy = 'id DESC', int $limit = 0): array
    {
        $clauses = [];
        $params  = [];
        foreach ($where as $col => $val) {
            if ($val === null) {
                $clauses[] = "$col IS NULL";
            } else {
                $clauses[] = "$col = ?";
                $params[]  = $val;
            }
        }
        $sql = 'SELECT * FROM ' . static::$table;
        if ($clauses) {
            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }
        if ($orderBy !== '') {
            $sql .= ' ORDER BY ' . $orderBy;
        }
        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit;
        }
        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Inserta y devuelve el id creado.
     * @param array<string,mixed> $data
     */
    public static function create(array $data): int
    {
        $cols = array_keys($data);
        $placeholders = array_fill(0, count($cols), '?');
        $sql = 'INSERT INTO ' . static::$table
             . ' (' . implode(',', $cols) . ') VALUES (' . implode(',', $placeholders) . ')';
        $stmt = static::db()->prepare($sql);
        $stmt->execute(array_values($data));
        return (int) static::db()->lastInsertId();
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function update(int $id, array $data): bool
    {
        $sets = [];
        foreach (array_keys($data) as $col) {
            $sets[] = "$col = ?";
        }
        $sql = 'UPDATE ' . static::$table . ' SET ' . implode(',', $sets) . ' WHERE id = ?';
        $params = array_values($data);
        $params[] = $id;
        return static::db()->prepare($sql)->execute($params);
    }

    public static function delete(int $id): bool
    {
        return static::db()->prepare('DELETE FROM ' . static::$table . ' WHERE id = ?')->execute([$id]);
    }

    public static function count(string $where = '', array $params = []): int
    {
        $sql = 'SELECT COUNT(*) FROM ' . static::$table;
        if ($where !== '') {
            $sql .= ' WHERE ' . $where;
        }
        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
}
