<?php
/**
 * API: Cambiar estado de una cita (confirmada, completada, no_asistio, cancelada)
 */

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Método no permitido'], 405);
}

if (!is_authenticated()) {
    json_response(['error' => 'No autorizado'], 401);
}

if (!verify_csrf()) {
    json_response(['error' => 'Token CSRF inválido'], 403);
}

try {
    $reservaId = (int)($_POST['reserva_id'] ?? 0);
    $nuevoEstado = trim($_POST['estado'] ?? '');
    $notasInternas = trim($_POST['notas_internas'] ?? '');

    $reserva = Reserva::findById($reservaId);
    if (!$reserva) {
        json_response(['error' => 'Reserva no encontrada'], 404);
    }

    $uId = auth_id();
    $isOwner = Negocio::isOwner($uId, (int)$reserva['negocio_id']);
    $isProf = false;
    foreach (Profesional::findByUserId($uId) as $pr) {
        if ($pr['id'] == $reserva['profesional_id']) {
            $isProf = true;
            break;
        }
    }
    $isAdmin = has_role('administrador');

    if (!$isOwner && !$isProf && !$isAdmin) {
        json_response(['error' => 'No tienes permisos para modificar el estado de esta reserva'], 403);
    }

    Reserva::updateEstado($reservaId, $nuevoEstado, $notasInternas, $uId);
    json_response(['success' => true, 'nuevo_estado' => $nuevoEstado]);

} catch (Exception $e) {
    json_response(['error' => $e->getMessage()], 400);
}
