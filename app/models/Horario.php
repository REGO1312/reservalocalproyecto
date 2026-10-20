<?php
/**
 * Modelo Horario (Horarios de Negocio, Profesional y Excepciones por Fecha)
 */

class Horario {
    /**
     * Obtener los horarios de apertura de un negocio indexados por día (1..7)
     */
    public static function getNegocioHorarios(int $negocioId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT dia_semana, hora_apertura, hora_cierre, es_laborable
            FROM horarios_negocio 
            WHERE negocio_id = ?
            ORDER BY dia_semana ASC
        ");
        $stmt->execute([$negocioId]);
        $rows = $stmt->fetchAll();
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(int)$row['dia_semana']] = $row;
        }
        return $indexed;
    }

    /**
     * Guardar o actualizar la semana completa de horarios de un negocio
     */
    public static function saveNegocioHorarios(int $negocioId, array $dias): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO horarios_negocio (negocio_id, dia_semana, hora_apertura, hora_cierre, es_laborable)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                hora_apertura = VALUES(hora_apertura),
                hora_cierre = VALUES(hora_cierre),
                es_laborable = VALUES(es_laborable)
        ");

        foreach ($dias as $diaNum => $cfg) {
            $esLab = !empty($cfg['es_laborable']) ? 1 : 0;
            $apertura = !empty($cfg['hora_apertura']) ? $cfg['hora_apertura'] : '09:00:00';
            $cierre = !empty($cfg['hora_cierre']) ? $cfg['hora_cierre'] : '19:00:00';
            $stmt->execute([$negocioId, (int)$diaNum, $apertura, $cierre, $esLab]);
        }

        audit_log(auth_id(), 'ACTUALIZAR_HORARIO_NEGOCIO', 'negocios', $negocioId, 'Horarios de atención actualizados');
        return true;
    }

    /**
     * Obtener los horarios de un profesional indexados por día (1..7)
     */
    public static function getProfesionalHorarios(int $profesionalId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT dia_semana, hora_inicio, hora_fin, descanso_inicio, descanso_fin, es_laborable
            FROM horarios_profesional 
            WHERE profesional_id = ?
            ORDER BY dia_semana ASC
        ");
        $stmt->execute([$profesionalId]);
        $rows = $stmt->fetchAll();
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(int)$row['dia_semana']] = $row;
        }
        return $indexed;
    }

    /**
     * Guardar o actualizar la semana completa de horarios de un profesional
     */
    public static function saveProfesionalHorarios(int $profesionalId, array $dias): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO horarios_profesional (
                profesional_id, dia_semana, hora_inicio, hora_fin, descanso_inicio, descanso_fin, es_laborable
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                hora_inicio = VALUES(hora_inicio),
                hora_fin = VALUES(hora_fin),
                descanso_inicio = VALUES(descanso_inicio),
                descanso_fin = VALUES(descanso_fin),
                es_laborable = VALUES(es_laborable)
        ");

        foreach ($dias as $diaNum => $cfg) {
            $esLab = !empty($cfg['es_laborable']) ? 1 : 0;
            $inicio = !empty($cfg['hora_inicio']) ? $cfg['hora_inicio'] : '09:00:00';
            $fin = !empty($cfg['hora_fin']) ? $cfg['hora_fin'] : '18:00:00';
            $descansoIni = !empty($cfg['descanso_inicio']) ? $cfg['descanso_inicio'] : null;
            $descansoFin = !empty($cfg['descanso_fin']) ? $cfg['descanso_fin'] : null;

            $stmt->execute([$profesionalId, (int)$diaNum, $inicio, $fin, $descansoIni, $descansoFin, $esLab]);
        }

        audit_log(auth_id(), 'ACTUALIZAR_HORARIO_PROFESIONAL', 'profesionales', $profesionalId, 'Horarios de profesional actualizados');
        return true;
    }

    /**
     * Excepciones de horario (días festivos, descansos especiales, cierres totales)
     */
    public static function getExcepciones(int $negocioId, ?string $fecha = null, ?int $profesionalId = null): array {
        $pdo = Database::getConnection();
        $sql = "
            SELECT eh.*, p.nombre as profesional_nombre
            FROM excepciones_horario eh
            LEFT JOIN profesionales p ON p.id = eh.profesional_id
            WHERE eh.negocio_id = ?
        ";
        $params = [$negocioId];

        if ($fecha !== null) {
            $sql .= " AND eh.fecha = ?";
            $params[] = $fecha;
        }

        if ($profesionalId !== null) {
            $sql .= " AND (eh.profesional_id IS NULL OR eh.profesional_id = ?)";
            $params[] = $profesionalId;
        }

        $sql .= " ORDER BY eh.fecha ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public static function addExcepcion(array $data): int {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO excepciones_horario (negocio_id, profesional_id, fecha, hora_inicio, hora_fin, tipo, motivo)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            (int)$data['negocio_id'],
            !empty($data['profesional_id']) ? (int)$data['profesional_id'] : null,
            $data['fecha'],
            !empty($data['hora_inicio']) ? $data['hora_inicio'] : null,
            !empty($data['hora_fin']) ? $data['hora_fin'] : null,
            $data['tipo'] ?? 'cierre_total',
            trim($data['motivo'] ?? '')
        ]);
        $id = (int)$pdo->lastInsertId();
        audit_log(auth_id(), 'CREAR_EXCEPCION_HORARIO', 'excepciones_horario', $id, "Excepción agregada para fecha {$data['fecha']}");
        return $id;
    }

    public static function deleteExcepcion(int $id, int $negocioId): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM excepciones_horario WHERE id = ? AND negocio_id = ?");
        $ok = $stmt->execute([$id, $negocioId]);
        if ($ok) {
            audit_log(auth_id(), 'ELIMINAR_EXCEPCION_HORARIO', 'excepciones_horario', $id, "Excepción ID $id eliminada");
        }
        return $ok;
    }
}
