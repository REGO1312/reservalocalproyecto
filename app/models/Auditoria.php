<?php
/**
 * Modelo Auditoria
 */

class Auditoria {
    public static function getAll(int $limit = 100, int $offset = 0): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT a.*, u.nombre as usuario_nombre, u.email as usuario_email
            FROM auditoria a
            LEFT JOIN usuarios u ON u.id = a.usuario_id
            ORDER BY a.creado_en DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }
}
