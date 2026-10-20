<?php
/**
 * API: Obtener slots de disponibilidad para un negocio, servicio y fecha
 */

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $negocioId = (int)($_GET['negocio_id'] ?? 0);
    $servicioId = (int)($_GET['servicio_id'] ?? 0);
    $profesionalId = !empty($_GET['profesional_id']) ? (int)$_GET['profesional_id'] : null;
    $fecha = trim($_GET['fecha'] ?? date('Y-m-d'));

    if (!$negocioId || !$servicioId || empty($fecha)) {
        json_response(['error' => 'Parámetros negocio_id, servicio_id y fecha son obligatorios'], 400);
    }

    $resultado = DisponibilidadService::obtenerSlots($negocioId, $servicioId, $profesionalId, $fecha);
    json_response($resultado, 200);

} catch (Exception $e) {
    json_response(['error' => $e->getMessage()], 400);
}
