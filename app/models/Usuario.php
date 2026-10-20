<?php
/**
 * Modelo Usuario
 */

class Usuario {
    public static function findById(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if ($user) {
            $user['roles'] = self::getRoles($user['id']);
        }
        return $user ?: null;
    }

    public static function findByEmail(string $email): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([strtolower(trim($email))]);
        $user = $stmt->fetch();
        if ($user) {
            $user['roles'] = self::getRoles($user['id']);
        }
        return $user ?: null;
    }

    public static function getRoles(int $userId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT r.nombre 
            FROM roles r
            JOIN usuario_roles ur ON ur.rol_id = r.id
            WHERE ur.usuario_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public static function create(array $data, array $roleNames = ['cliente']): int {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO usuarios (nombre, email, password_hash, telefono, estado)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                trim($data['nombre']),
                strtolower(trim($data['email'])),
                password_hash($data['password'], PASSWORD_BCRYPT),
                trim($data['telefono'] ?? ''),
                $data['estado'] ?? 'activo'
            ]);
            $userId = (int)$pdo->lastInsertId();

            // Asignar roles
            $roleStmt = $pdo->prepare("SELECT id, nombre FROM roles WHERE nombre = ?");
            $assignStmt = $pdo->prepare("INSERT INTO usuario_roles (usuario_id, rol_id) VALUES (?, ?)");

            foreach ($roleNames as $rName) {
                $roleStmt->execute([$rName]);
                $roleId = $roleStmt->fetchColumn();
                if ($roleId) {
                    $assignStmt->execute([$userId, $roleId]);
                }
            }

            $pdo->commit();
            audit_log($userId, 'REGISTRO_USUARIO', 'usuarios', $userId, "Usuario registrado: {$data['email']}");
            return $userId;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function update(int $id, array $data): bool {
        $pdo = Database::getConnection();
        $fields = [];
        $params = [];

        if (isset($data['nombre'])) {
            $fields[] = 'nombre = ?';
            $params[] = trim($data['nombre']);
        }
        if (isset($data['telefono'])) {
            $fields[] = 'telefono = ?';
            $params[] = trim($data['telefono']);
        }
        if (isset($data['email'])) {
            $fields[] = 'email = ?';
            $params[] = strtolower(trim($data['email']));
        }
        if (!empty($data['password'])) {
            $fields[] = 'password_hash = ?';
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        if (isset($data['estado'])) {
            $fields[] = 'estado = ?';
            $params[] = $data['estado'];
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE usuarios SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute($params);
        if ($ok) {
            audit_log(auth_id(), 'ACTUALIZAR_USUARIO', 'usuarios', $id, "Usuario actualizado ID: $id");
        }
        return $ok;
    }

    public static function checkLoginRateLimit(string $ip, string $email): ?string {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT intentos, bloqueado_hasta 
            FROM login_attempts 
            WHERE ip = ? AND email = ?
        ");
        $stmt->execute([$ip, $email]);
        $attempt = $stmt->fetch();

        if ($attempt && $attempt['bloqueado_hasta']) {
            $blockedUntil = strtotime($attempt['bloqueado_hasta']);
            if (time() < $blockedUntil) {
                $remainingMin = ceil(($blockedUntil - time()) / 60);
                return "Demasiados intentos fallidos. Cuenta temporalmente bloqueada. Intenta de nuevo en {$remainingMin} minuto(s).";
            } else {
                // Desbloqueado
                self::resetLoginAttempts($ip, $email);
            }
        }
        return null;
    }

    public static function recordFailedLogin(string $ip, string $email): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, intentos FROM login_attempts WHERE ip = ? AND email = ?");
        $stmt->execute([$ip, $email]);
        $attempt = $stmt->fetch();

        $maxAttempts = 5;
        $lockoutMinutes = 15;

        if ($attempt) {
            $newAttempts = $attempt['intentos'] + 1;
            $bloqueadoHasta = null;
            if ($newAttempts >= $maxAttempts) {
                $bloqueadoHasta = date('Y-m-d H:i:s', strtotime("+{$lockoutMinutes} minutes"));
            }
            $update = $pdo->prepare("
                UPDATE login_attempts 
                SET intentos = ?, bloqueado_hasta = ?, ultimo_intento = NOW() 
                WHERE id = ?
            ");
            $update->execute([$newAttempts, $bloqueadoHasta, $attempt['id']]);
        } else {
            $insert = $pdo->prepare("
                INSERT INTO login_attempts (ip, email, intentos) 
                VALUES (?, ?, 1)
            ");
            $insert->execute([$ip, $email]);
        }
    }

    public static function resetLoginAttempts(string $ip, string $email): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip = ? AND email = ?");
        $stmt->execute([$ip, $email]);
    }

    public static function createPasswordReset(int $userId): string {
        $pdo = Database::getConnection();
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $expiraEn = date('Y-m-d H:i:s', strtotime('+60 minutes'));

        // Invalidar tokens previos
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE usuario_id = ?");
        $stmt->execute([$userId]);

        $stmt = $pdo->prepare("
            INSERT INTO password_resets (usuario_id, token_hash, expira_en)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$userId, $tokenHash, $expiraEn]);

        audit_log($userId, 'SOLICITUD_RECUPERACION_PASSWORD', 'usuarios', $userId, 'Token generado');
        return $rawToken;
    }

    public static function verifyPasswordResetToken(string $rawToken): ?array {
        $pdo = Database::getConnection();
        $tokenHash = hash('sha256', $rawToken);

        $stmt = $pdo->prepare("
            SELECT pr.*, u.email, u.nombre 
            FROM password_resets pr
            JOIN usuarios u ON u.id = pr.usuario_id
            WHERE pr.token_hash = ? AND pr.usado_en IS NULL AND pr.expira_en > NOW()
        ");
        $stmt->execute([$tokenHash]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public static function completePasswordReset(string $rawToken, string $newPassword): bool {
        $reset = self::verifyPasswordResetToken($rawToken);
        if (!$reset) {
            return false;
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $hash = password_hash($newPassword, PASSWORD_BCRYPT);
            $uStmt = $pdo->prepare("UPDATE usuarios SET password_hash = ? WHERE id = ?");
            $uStmt->execute([$hash, $reset['usuario_id']]);

            $rStmt = $pdo->prepare("UPDATE password_resets SET usado_en = NOW() WHERE id = ?");
            $rStmt->execute([$reset['id']]);

            $pdo->commit();
            audit_log($reset['usuario_id'], 'PASSWORD_RESTABLECIDO', 'usuarios', $reset['usuario_id'], 'Contraseña cambiada con token');
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }

    public static function getAll(): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("
            SELECT u.id, u.nombre, u.email, u.telefono, u.estado, u.creado_en,
                   GROUP_CONCAT(r.nombre SEPARATOR ', ') as roles_str
            FROM usuarios u
            LEFT JOIN usuario_roles ur ON ur.usuario_id = u.id
            LEFT JOIN roles r ON r.id = ur.rol_id
            GROUP BY u.id
            ORDER BY u.id ASC
        ");
        return $stmt->fetchAll() ?: [];
    }
}
