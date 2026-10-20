<?php
/**
 * Modelo Servicio
 */

class Servicio {
    public static function findById(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM servicios WHERE id = ?");
        $stmt->execute([$id]);
        $s = $stmt->fetch();
        return $s ?: null;
    }

    public static function findByNegocio(int $negocioId, bool $onlyActive = true): array {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM servicios WHERE negocio_id = ?";
        if ($onlyActive) {
            $sql .= " AND estado = 'activo'";
        }
        $sql .= " ORDER BY precio ASC, nombre ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$negocioId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function findByProfesional(int $profesionalId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT s.* 
            FROM servicios s
            JOIN profesional_servicios ps ON ps.servicio_id = s.id
            WHERE ps.profesional_id = ? AND s.estado = 'activo'
            ORDER BY s.precio ASC
        ");
        $stmt->execute([$profesionalId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function create(array $data): int {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO servicios (negocio_id, nombre, descripcion, precio, duracion_minutos, estado)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            (int)$data['negocio_id'],
            trim($data['nombre']),
            trim($data['descripcion'] ?? ''),
            (float)$data['precio'],
            (int)$data['duracion_minutos'],
            $data['estado'] ?? 'activo'
        ]);
        $id = (int)$pdo->lastInsertId();
        audit_log(auth_id(), 'CREAR_SERVICIO', 'servicios', $id, "Servicio creado: {$data['nombre']}");
        return $id;
    }

    public static function update(int $id, array $data): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE servicios SET 
                nombre = ?, descripcion = ?, precio = ?, duracion_minutos = ?, estado = ?
            WHERE id = ?
        ");
        $ok = $stmt->execute([
            trim($data['nombre']),
            trim($data['descripcion'] ?? ''),
            (float)$data['precio'],
            (int)$data['duracion_minutos'],
            $data['estado'] ?? 'activo',
            $id
        ]);
        if ($ok) {
            audit_log(auth_id(), 'ACTUALIZAR_SERVICIO', 'servicios', $id, "Servicio ID $id actualizado");
        }
        return $ok;
    }

    public static function delete(int $id): bool {
        $pdo = Database::getConnection();
        // Verificar si tiene reservas asociadas
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservas WHERE servicio_id = ?");
        $stmt->execute([$id]);
        $hasReservas = $stmt->fetchColumn() > 0;

        if ($hasReservas) {
            // Desactivar en lugar de borrar para preservar integridad referencial
            $up = $pdo->prepare("UPDATE servicios SET estado = 'inactivo' WHERE id = ?");
            $ok = $up->execute([$id]);
            audit_log(auth_id(), 'DESACTIVAR_SERVICIO', 'servicios', $id, "Servicio desactivado por tener reservas");
            return $ok;
        }

        $del = $pdo->prepare("DELETE FROM servicios WHERE id = ?");
        $ok = $del->execute([$id]);
        if ($ok) {
            audit_log(auth_id(), 'ELIMINAR_SERVICIO', 'servicios', $id, "Servicio eliminado");
        }
        return $ok;
    }
}
