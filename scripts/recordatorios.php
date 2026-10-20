<?php
/**
 * Reserva Local - Proceso Batch de Envío de Recordatorios y Notificaciones
 * Ejecutable vía CLI por Cron (Linux) o Programador de Tareas (Windows):
 * php scripts/recordatorios.php
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/app/bootstrap.php';

$isCli = (php_sapi_name() === 'cli');

echo "========================================================\n";
echo " RESERVA LOCAL - TAREA DE RECORDATORIOS\n";
echo " Hora de ejecución: " . date('Y-m-d H:i:s') . "\n";
echo "========================================================\n";

try {
    $stats = NotificacionService::procesarCola(100);

    echo "Notificaciones procesadas: {$stats['total']}\n";
    echo "Enviadas con éxito:       {$stats['enviadas']}\n";
    echo "Fallidas:                 {$stats['fallidas']}\n";

    if (!empty($stats['detalles'])) {
        echo "\nDetalles:\n";
        foreach ($stats['detalles'] as $detalle) {
            echo "  - $detalle\n";
        }
    }

    echo "========================================================\n";
    echo "Proceso finalizado exitosamente.\n";
    exit(0);
} catch (Exception $e) {
    echo "ERROR en el worker de recordatorios: " . $e->getMessage() . "\n";
    @file_put_contents(
        APP_ROOT . '/storage/logs/recordatorios_error.log',
        date('[Y-m-d H:i:s] ') . 'Error fatal: ' . $e->getMessage() . PHP_EOL,
        FILE_APPEND
    );
    exit(1);
}
