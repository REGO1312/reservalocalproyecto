<?php
/**
 * API: Cancelar reserva
 */

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Método no permitido'], 405);
}

if (!is_authenticated()) {
    json_response(['error' => 'Debes iniciar sesión'], 401);
}

if (!verify_csrf()) {
    json_response(['error' => 'Token CSRF inválido'], 403);
}

try {
    $reservaId = (int)($_POST['reserva_id'] ?? 0);
    $motivo = trim($_POST['motivo'] ?? '');

    if (!$reservaId) {
        json_response(['error' => 'ID de reserva requerido'], 400);
    }

    ReservaService::cancelar($reservaId, auth_id(), $motivo);

    set_flash('info', 'La cita ha sido cancelada correctamente.');
    json_response(['success' => true, 'mensaje' => 'Cita cancelada']);

} catch (Exception $e) {
    json_response(['error' => $e->getMessage()], 400);
}
