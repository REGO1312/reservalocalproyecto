<?php
/**
 * Modelo Profesional
 */

class Profesional {
    public static function findById(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT p.*, n.nombre as negocio_nombre, n.categoria as negocio_categoria
            FROM profesionales p
            JOIN negocios n ON n.id = p.negocio_id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        $p = $stmt->fetch();
        if ($p) {
            $p['servicios'] = self::getServiceIds($p['id']);
        }
        return $p ?: null;
    }

    public static function findByUserId(int $userId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT p.*, n.nombre as negocio_nombre, n.categoria as negocio_categoria
            FROM profesionales p
            JOIN negocios n ON n.id = p.negocio_id
            WHERE p.usuario_id = ? AND p.estado = 'activo'
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function findByNegocio(int $negocioId, bool $onlyActive = true): array {
        $pdo = Database::getConnection();
        $sql = "
            SELECT p.*, u.email as usuario_email 
            FROM profesionales p
            LEFT JOIN usuarios u ON u.id = p.usuario_id
            WHERE p.negocio_id = ?
        ";
        if ($onlyActive) {
            $sql .= " AND p.estado = 'activo'";
        }
        $sql .= " ORDER BY p.nombre ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$negocioId]);
        $profs = $stmt->fetchAll() ?: [];
        foreach ($profs as &$prof) {
            $prof['servicios'] = self::getServiceIds($prof['id']);
        }
        return $profs;
    }

    public static function findByServicio(int $servicioId, bool $onlyActive = true): array {
        $pdo = Database::getConnection();
        $sql = "
            SELECT p.* 
            FROM profesionales p
            JOIN profesional_servicios ps ON ps.profesional_id = p.id
            WHERE ps.servicio_id = ?
        ";
        if ($onlyActive) {
            $sql .= " AND p.estado = 'activo'";
        }
        $sql .= " ORDER BY p.nombre ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$servicioId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function getServiceIds(int $profesionalId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT servicio_id FROM profesional_servicios WHERE profesional_id = ?");
        $stmt->execute([$profesionalId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public static function create(array $data, array $serviceIds = []): int {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO profesionales (negocio_id, usuario_id, nombre, especialidad, telefono, estado)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                (int)$data['negocio_id'],
                !empty($data['usuario_id']) ? (int)$data['usuario_id'] : null,
                trim($data['nombre']),
                trim($data['especialidad'] ?? ''),
                trim($data['telefono'] ?? ''),
                $data['estado'] ?? 'activo'
            ]);
            $profId = (int)$pdo->lastInsertId();

            // Vincular servicios
            if (!empty($serviceIds)) {
                $psStmt = $pdo->prepare("INSERT INTO profesional_servicios (profesional_id, servicio_id) VALUES (?, ?)");
                foreach ($serviceIds as $sId) {
                    $psStmt->execute([$profId, (int)$sId]);
                }
            }

            // Crear horarios por defecto para el profesional (Lun-Vie 09:00-18:00, Sáb 09:00-15:00, descanso 14:00-15:00)
            $hStmt = $pdo->prepare("
                INSERT INTO horarios_profesional (
                    profesional_id, dia_semana, hora_inicio, hora_fin, descanso_inicio, descanso_fin, es_laborable
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            for ($d = 1; $d <= 7; $d++) {
                if ($d <= 5) {
                    $hStmt->execute([$profId, $d, '09:00:00', '18:00:00', '14:00:00', '15:00:00', 1]);
                } elseif ($d == 6) {
                    $hStmt->execute([$profId, $d, '09:00:00', '15:00:00', null, null, 1]);
                } else {
                    $hStmt->execute([$profId, $d, '10:00:00', '14:00:00', null, null, 0]);
                }
            }

            // Si tiene usuario_id, asegurar que el usuario tenga el rol 'profesional'
            if (!empty($data['usuario_id'])) {
                $roleStmt = $pdo->prepare("SELECT id FROM roles WHERE nombre = 'profesional'");
                $roleStmt->execute();
                $rId = $roleStmt->fetchColumn();
                if ($rId) {
                    $urStmt = $pdo->prepare("INSERT IGNORE INTO usuario_roles (usuario_id, rol_id) VALUES (?, ?)");
                    $urStmt->execute([(int)$data['usuario_id'], $rId]);
                }
            }

            $pdo->commit();
            audit_log(auth_id(), 'CREAR_PROFESIONAL', 'profesionales', $profId, "Profesional creado: {$data['nombre']}");
            return $profId;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function update(int $id, array $data, ?array $serviceIds = null): bool {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                UPDATE profesionales SET 
                    usuario_id = ?, nombre = ?, especialidad = ?, telefono = ?, estado = ?
                WHERE id = ?
            ");
            $stmt->execute([
                !empty($data['usuario_id']) ? (int)$data['usuario_id'] : null,
                trim($data['nombre']),
                trim($data['especialidad'] ?? ''),
                trim($data['telefono'] ?? ''),
                $data['estado'] ?? 'activo',
                $id
            ]);

            if ($serviceIds !== null) {
                $del = $pdo->prepare("DELETE FROM profesional_servicios WHERE profesional_id = ?");
                $del->execute([$id]);

                if (!empty($serviceIds)) {
                    $ins = $pdo->prepare("INSERT INTO profesional_servicios (profesional_id, servicio_id) VALUES (?, ?)");
                    foreach ($serviceIds as $sId) {
                        $ins->execute([$id, (int)$sId]);
                    }
                }
            }

            // Asegurar rol al usuario vinculado si se actualizó
            if (!empty($data['usuario_id'])) {
                $roleStmt = $pdo->prepare("SELECT id FROM roles WHERE nombre = 'profesional'");
                $roleStmt->execute();
                $rId = $roleStmt->fetchColumn();
                if ($rId) {
                    $urStmt = $pdo->prepare("INSERT IGNORE INTO usuario_roles (usuario_id, rol_id) VALUES (?, ?)");
                    $urStmt->execute([(int)$data['usuario_id'], $rId]);
                }
            }

            $pdo->commit();
            audit_log(auth_id(), 'ACTUALIZAR_PROFESIONAL', 'profesionales', $id, "Profesional ID $id actualizado");
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function delete(int $id): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservas WHERE profesional_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            $up = $pdo->prepare("UPDATE profesionales SET estado = 'inactivo' WHERE id = ?");
            return $up->execute([$id]);
        }
        $del = $pdo->prepare("DELETE FROM profesionales WHERE id = ?");
        return $del->execute([$id]);
    }
}
