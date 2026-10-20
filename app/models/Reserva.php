<?php
/**
 * Modelo Reserva
 */

class Reserva {
    public static function findById(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT r.*,
                   n.nombre as negocio_nombre, n.telefono as negocio_telefono, n.direccion as negocio_direccion,
                   s.nombre as servicio_nombre,
                   p.nombre as profesional_nombre,
                   u.nombre as cliente_nombre, u.email as cliente_email, u.telefono as cliente_telefono
            FROM reservas r
            JOIN negocios n ON n.id = r.negocio_id
            JOIN servicios s ON s.id = r.servicio_id
            JOIN profesionales p ON p.id = r.profesional_id
            JOIN usuarios u ON u.id = r.cliente_id
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByCodigo(string $codigo): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT r.*,
                   n.nombre as negocio_nombre, n.direccion as negocio_direccion,
                   s.nombre as servicio_nombre,
                   p.nombre as profesional_nombre,
                   u.nombre as cliente_nombre, u.email as cliente_email
            FROM reservas r
            JOIN negocios n ON n.id = r.negocio_id
            JOIN servicios s ON s.id = r.servicio_id
            JOIN profesionales p ON p.id = r.profesional_id
            JOIN usuarios u ON u.id = r.cliente_id
            WHERE r.codigo_reserva = ?
        ");
        $stmt->execute([trim($codigo)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByCliente(int $clienteId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT r.*,
                   n.nombre as negocio_nombre, n.direccion as negocio_direccion, n.categoria as negocio_categoria,
                   s.nombre as servicio_nombre,
                   p.nombre as profesional_nombre
            FROM reservas r
            JOIN negocios n ON n.id = r.negocio_id
            JOIN servicios s ON s.id = r.servicio_id
            JOIN profesionales p ON p.id = r.profesional_id
            WHERE r.cliente_id = ?
            ORDER BY r.fecha DESC, r.hora_inicio DESC
        ");
        $stmt->execute([$clienteId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function findByProfesional(int $profesionalId, ?string $fecha = null): array {
        $pdo = Database::getConnection();
        $sql = "
            SELECT r.*,
                   s.nombre as servicio_nombre,
                   u.nombre as cliente_nombre, u.telefono as cliente_telefono, u.email as cliente_email,
                   n.nombre as negocio_nombre
            FROM reservas r
            JOIN servicios s ON s.id = r.servicio_id
            JOIN usuarios u ON u.id = r.cliente_id
            JOIN negocios n ON n.id = r.negocio_id
            WHERE r.profesional_id = ?
        ";
        $params = [$profesionalId];

        if ($fecha !== null) {
            $sql .= " AND r.fecha = ?";
            $params[] = $fecha;
        }

        $sql .= " ORDER BY r.fecha ASC, r.hora_inicio ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public static function findByNegocio(int $negocioId, ?string $fechaInicio = null, ?string $fechaFin = null, ?int $profesionalId = null, ?string $estado = null): array {
        $pdo = Database::getConnection();
        $sql = "
            SELECT r.*,
                   s.nombre as servicio_nombre,
                   p.nombre as profesional_nombre,
                   u.nombre as cliente_nombre, u.telefono as cliente_telefono, u.email as cliente_email
            FROM reservas r
            JOIN servicios s ON s.id = r.servicio_id
            JOIN profesionales p ON p.id = r.profesional_id
            JOIN usuarios u ON u.id = r.cliente_id
            WHERE r.negocio_id = ?
        ";
        $params = [$negocioId];

        if ($fechaInicio !== null) {
            $sql .= " AND r.fecha >= ?";
            $params[] = $fechaInicio;
        }

        if ($fechaFin !== null) {
            $sql .= " AND r.fecha <= ?";
            $params[] = $fechaFin;
        }

        if ($profesionalId !== null) {
            $sql .= " AND r.profesional_id = ?";
            $params[] = $profesionalId;
        }

        if ($estado !== null && $estado !== '') {
            $sql .= " AND r.estado = ?";
            $params[] = $estado;
        }

        $sql .= " ORDER BY r.fecha ASC, r.hora_inicio ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public static function updateEstado(int $id, string $nuevoEstado, ?string $notasInternas = null, ?int $userId = null): bool {
        $allowed = ['pendiente', 'confirmada', 'cancelada', 'completada', 'no_asistio'];
        if (!in_array($nuevoEstado, $allowed, true)) {
            throw new InvalidArgumentException("Estado no válido: $nuevoEstado");
        }

        $pdo = Database::getConnection();
        $fields = ["estado = ?"];
        $params = [$nuevoEstado];

        if ($notasInternas !== null) {
            $fields[] = "notas_internas = ?";
            $params[] = trim($notasInternas);
        }

        $params[] = $id;
        $sql = "UPDATE reservas SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute($params);

        if ($ok) {
            $uId = $userId ?: auth_id();
            audit_log($uId, 'CAMBIO_ESTADO_RESERVA', 'reservas', $id, "Nuevo estado: $nuevoEstado");

            // Si se canceló o completó, podemos programar notificación
            $reserva = self::findById($id);
            if ($reserva) {
                if ($nuevoEstado === 'cancelada') {
                    Notificacion::create([
                        'reserva_id' => $reserva['id'],
                        'usuario_id' => $reserva['cliente_id'],
                        'tipo' => 'cancelacion',
                        'destinatario' => $reserva['cliente_email'],
                        'asunto' => "Cita Cancelada - {$reserva['negocio_nombre']}",
                        'mensaje' => "Hola {$reserva['cliente_nombre']}, tu cita de {$reserva['servicio_nombre']} para el {$reserva['fecha']} a las {$reserva['hora_inicio']} ha sido cancelada.",
                        'programada_para' => date('Y-m-d H:i:s')
                    ]);
                }
            }
        }

        return $ok;
    }

    public static function generateUniqueCode(): string {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservas WHERE codigo_reserva = ?");

        do {
            $code = 'RES-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $stmt->execute([$code]);
            $exists = $stmt->fetchColumn() > 0;
        } while ($exists);

        return $code;
    }
}
