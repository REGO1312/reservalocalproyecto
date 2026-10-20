<?php
/**
 * API: Crear nueva reserva de cita
 */

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Método no permitido'], 405);
}

// 1. Validar sesión
if (!is_authenticated()) {
    json_response([
        'error' => 'Debes iniciar sesión para reservar una cita.',
        'require_login' => true,
        'redirect' => url('/login.php')
    ], 401);
}

// 2. Validar token CSRF
if (!verify_csrf()) {
    json_response(['error' => 'Token de seguridad inválido o expirado. Recarga la página.'], 403);
}

try {
    $clienteId = auth_id();

    $reservaData = ReservaService::crearReserva([
        'negocio_id' => (int)($_POST['negocio_id'] ?? 0),
        'servicio_id' => (int)($_POST['servicio_id'] ?? 0),
        'profesional_id' => (int)($_POST['profesional_id'] ?? 0),
        'cliente_id' => $clienteId,
        'fecha' => trim($_POST['fecha'] ?? ''),
        'hora_inicio' => trim($_POST['hora_inicio'] ?? ''),
        'notas_cliente' => trim($_POST['notas_cliente'] ?? '')
    ]);

    set_flash('success', "¡Cita reservada con éxito! Código de confirmación: {$reservaData['codigo_reserva']}");

    json_response([
        'success' => true,
        'codigo_reserva' => $reservaData['codigo_reserva'],
        'reserva' => $reservaData,
        'redirect' => url('/mis-citas.php?reserva=' . urlencode($reservaData['codigo_reserva']))
    ], 201);

} catch (Exception $e) {
    json_response(['error' => $e->getMessage()], 400);
}
