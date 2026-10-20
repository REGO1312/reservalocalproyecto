<?php
/**
 * Modelo Negocio (con fórmula Haversine para búsqueda geoespacial)
 */

class Negocio {
    public static function findById(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM negocios WHERE id = ?");
        $stmt->execute([$id]);
        $negocio = $stmt->fetch();
        return $negocio ?: null;
    }

    public static function findByOwner(int $userId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT n.*, nu.rol as rol_en_negocio
            FROM negocios n
            JOIN negocio_usuarios nu ON nu.negocio_id = n.id
            WHERE nu.usuario_id = ?
            ORDER BY n.id ASC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function isOwner(int $userId, int $negocioId): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT 1 FROM negocio_usuarios 
            WHERE usuario_id = ? AND negocio_id = ?
        ");
        $stmt->execute([$userId, $negocioId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Búsqueda por Haversine en SQL, categoría, servicio, texto y zona
     */
    public static function buscar(array $filters = []): array {
        $pdo = Database::getConnection();

        $lat = isset($filters['lat']) && is_numeric($filters['lat']) ? (float)$filters['lat'] : null;
        $lng = isset($filters['lng']) && is_numeric($filters['lng']) ? (float)$filters['lng'] : null;
        $radioKm = isset($filters['radio']) && is_numeric($filters['radio']) ? (float)$filters['radio'] : 50.0;
        $categoria = !empty($filters['categoria']) ? trim($filters['categoria']) : null;
        $q = !empty($filters['q']) ? trim($filters['q']) : null;
        $servicio = !empty($filters['servicio']) ? trim($filters['servicio']) : null;
        $zona = !empty($filters['zona']) ? trim($filters['zona']) : null;

        $hasCoords = ($lat !== null && $lng !== null);

        $params = [];
        $select = "n.*";
        if ($hasCoords) {
            $select .= ", (6371 * ACOS(
                LEAST(1.0, GREATEST(-1.0,
                    COS(RADIANS(:lat1)) * COS(RADIANS(n.latitud)) * COS(RADIANS(n.longitud) - RADIANS(:lng))
                    + SIN(RADIANS(:lat2)) * SIN(RADIANS(n.latitud))
                ))
            )) AS distancia_km";
            $params[':lat1'] = $lat;
            $params[':lat2'] = $lat;
            $params[':lng'] = $lng;
        } else {
            $select .= ", NULL AS distancia_km";
        }

        $where = ["n.estado = 'activo'"];

        if ($categoria && in_array($categoria, ['barberia', 'salon'])) {
            $where[] = "n.categoria = :categoria";
            $params[':categoria'] = $categoria;
        }

        if ($q) {
            $where[] = "(n.nombre LIKE :q OR n.descripcion LIKE :q2)";
            $params[':q'] = "%$q%";
            $params[':q2'] = "%$q%";
        }

        if ($zona) {
            $where[] = "(n.zona_municipio LIKE :zona OR n.direccion LIKE :zona2)";
            $params[':zona'] = "%$zona%";
            $params[':zona2'] = "%$zona%";
        }

        if ($servicio) {
            $where[] = "EXISTS (
                SELECT 1 FROM servicios s 
                WHERE s.negocio_id = n.id 
                  AND s.estado = 'activo' 
                  AND s.nombre LIKE :servicio
            )";
            $params[':servicio'] = "%$servicio%";
        }

        $sql = "SELECT $select FROM negocios n WHERE " . implode(' AND ', $where);

        if ($hasCoords && $radioKm > 0) {
            $sql .= " HAVING distancia_km <= :radio";
            $params[':radio'] = $radioKm;
            $sql .= " ORDER BY distancia_km ASC";
        } elseif ($hasCoords) {
            $sql .= " ORDER BY distancia_km ASC";
        } else {
            $sql .= " ORDER BY n.nombre ASC";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();

        // Cargar servicios principales resumidos para cada negocio
        $svcStmt = $pdo->prepare("
            SELECT id, nombre, precio, duracion_minutos 
            FROM servicios 
            WHERE negocio_id = ? AND estado = 'activo' 
            ORDER BY precio ASC 
            LIMIT 4
        ");

        foreach ($results as &$negocio) {
            $svcStmt->execute([$negocio['id']]);
            $negocio['servicios_destacados'] = $svcStmt->fetchAll();
            if (isset($negocio['distancia_km']) && $negocio['distancia_km'] !== null) {
                $negocio['distancia_km'] = round((float)$negocio['distancia_km'], 1);
            }
        }

        return $results;
    }

    public static function create(array $data, int $ownerUserId): int {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO negocios (
                    nombre, categoria, descripcion, direccion, zona_municipio,
                    latitud, longitud, telefono, email, zona_horaria, imagen_url, estado
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                trim($data['nombre']),
                $data['categoria'],
                trim($data['descripcion'] ?? ''),
                trim($data['direccion']),
                trim($data['zona_municipio']),
                (float)$data['latitud'],
                (float)$data['longitud'],
                trim($data['telefono'] ?? ''),
                trim($data['email'] ?? ''),
                $data['zona_horaria'] ?? 'America/Mexico_City',
                $data['imagen_url'] ?? null,
                $data['estado'] ?? 'activo'
            ]);
            $negocioId = (int)$pdo->lastInsertId();

            // Vincular usuario como propietario
            $nuStmt = $pdo->prepare("INSERT INTO negocio_usuarios (negocio_id, usuario_id, rol) VALUES (?, ?, 'propietario')");
            $nuStmt->execute([$negocioId, $ownerUserId]);

            // Crear horarios por defecto (Lun-Sáb 09:00 a 19:00, Dom cerrado)
            $hStmt = $pdo->prepare("
                INSERT INTO horarios_negocio (negocio_id, dia_semana, hora_apertura, hora_cierre, es_laborable)
                VALUES (?, ?, ?, ?, ?)
            ");
            for ($d = 1; $d <= 7; $d++) {
                $esLab = ($d <= 6) ? 1 : 0;
                $hStmt->execute([$negocioId, $d, '09:00:00', '19:00:00', $esLab]);
            }

            $pdo->commit();
            audit_log($ownerUserId, 'CREAR_NEGOCIO', 'negocios', $negocioId, "Negocio creado: {$data['nombre']}");
            return $negocioId;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function update(int $id, array $data): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE negocios SET 
                nombre = ?, categoria = ?, descripcion = ?, direccion = ?,
                zona_municipio = ?, latitud = ?, longitud = ?, telefono = ?,
                email = ?, zona_horaria = ?, imagen_url = COALESCE(?, imagen_url),
                estado = COALESCE(?, estado)
            WHERE id = ?
        ");
        $ok = $stmt->execute([
            trim($data['nombre']),
            $data['categoria'],
            trim($data['descripcion'] ?? ''),
            trim($data['direccion']),
            trim($data['zona_municipio']),
            (float)$data['latitud'],
            (float)$data['longitud'],
            trim($data['telefono'] ?? ''),
            trim($data['email'] ?? ''),
            $data['zona_horaria'] ?? 'America/Mexico_City',
            $data['imagen_url'] ?? null,
            $data['estado'] ?? null,
            $id
        ]);
        if ($ok) {
            audit_log(auth_id(), 'ACTUALIZAR_NEGOCIO', 'negocios', $id, "Negocio ID $id actualizado");
        }
        return $ok;
    }

    public static function getAllAdmin(): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("
            SELECT n.*, 
                   (SELECT COUNT(*) FROM servicios WHERE negocio_id = n.id) as total_servicios,
                   (SELECT COUNT(*) FROM profesionales WHERE negocio_id = n.id) as total_profesionales,
                   (SELECT COUNT(*) FROM reservas WHERE negocio_id = n.id) as total_reservas
            FROM negocios n
            ORDER BY n.id ASC
        ");
        return $stmt->fetchAll() ?: [];
    }

    public static function updateEstado(int $id, string $estado): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE negocios SET estado = ? WHERE id = ?");
        $ok = $stmt->execute([$estado, $id]);
        if ($ok) {
            audit_log(auth_id(), 'CAMBIO_ESTADO_NEGOCIO', 'negocios', $id, "Nuevo estado: $estado");
        }
        return $ok;
    }
}
