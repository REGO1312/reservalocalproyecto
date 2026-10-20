<?php
/**
 * Modelo Notificacion
 */

class Notificacion {
    public static function create(array $data): int {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO notificaciones (
                reserva_id, usuario_id, tipo, canal, destinatario, asunto, mensaje, estado, programada_para
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            (int)$data['reserva_id'],
            (int)$data['usuario_id'],
            $data['tipo'],
            $data['canal'] ?? 'log',
            trim($data['destinatario']),
            trim($data['asunto']),
            trim($data['mensaje']),
            $data['estado'] ?? 'pendiente',
            $data['programada_para'] ?? date('Y-m-d H:i:s')
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function getPendingToSend(int $limit = 50): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT n.*, r.fecha as reserva_fecha, r.hora_inicio as reserva_hora, r.estado as reserva_estado
            FROM notificaciones n
            JOIN reservas r ON r.id = n.reserva_id
            WHERE n.estado = 'pendiente' 
              AND n.programada_para <= NOW() 
              AND n.intentos < 3
              AND r.estado != 'cancelada'
            ORDER BY n.programada_para ASC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public static function markSent(int $id): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE notificaciones 
            SET estado = 'enviada', enviado_en = NOW(), intentos = intentos + 1
            WHERE id = ?
        ");
        return $stmt->execute([$id]);
    }

    public static function markFailed(int $id, string $error): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE notificaciones 
            SET intentos = intentos + 1,
                error_mensaje = ?,
                estado = IF(intentos >= 3, 'fallida', 'pendiente')
            WHERE id = ?
        ");
        return $stmt->execute([substr($error, 0, 500), $id]);
    }

    public static function isDuplicate(int $reservaId, string $tipo): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM notificaciones 
            WHERE reserva_id = ? AND tipo = ? AND estado IN ('enviada', 'pendiente')
        ");
        $stmt->execute([$reservaId, $tipo]);
        return $stmt->fetchColumn() > 0;
    }
}
