<?php
/**
 * Servicio de Generación de Reportes y Estadísticas de Negocio
 */

class ReporteService {
    /**
     * Reporte completo por negocio y período con consultas directas a MySQL
     */
    public static function generarReporte(int $negocioId, string $fechaInicio, string $fechaFin): array {
        $pdo = Database::getConnection();

        // 1. Resumen global de reservas e ingresos
        $stmtResumen = $pdo->prepare("
            SELECT 
                COUNT(*) as total_reservas,
                COALESCE(SUM(CASE WHEN estado IN ('confirmada', 'completada') THEN precio_acordado ELSE 0 END), 0) as ingresos_totales,
                COALESCE(SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END), 0) as completadas,
                COALESCE(SUM(CASE WHEN estado = 'confirmada' THEN 1 ELSE 0 END), 0) as confirmadas,
                COALESCE(SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END), 0) as pendientes,
                COALESCE(SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END), 0) as canceladas,
                COALESCE(SUM(CASE WHEN estado = 'no_asistio' THEN 1 ELSE 0 END), 0) as no_asistio
            FROM reservas 
            WHERE negocio_id = ? 
              AND fecha BETWEEN ? AND ?
        ");
        $stmtResumen->execute([$negocioId, $fechaInicio, $fechaFin]);
        $resumen = $stmtResumen->fetch() ?: [];

        // 2. Desglose de reservas por estado
        $stmtEstados = $pdo->prepare("
            SELECT estado, COUNT(*) as total, COALESCE(SUM(precio_acordado), 0) as total_dinero
            FROM reservas 
            WHERE negocio_id = ? 
              AND fecha BETWEEN ? AND ?
            GROUP BY estado
            ORDER BY total DESC
        ");
        $stmtEstados->execute([$negocioId, $fechaInicio, $fechaFin]);
        $porEstado = $stmtEstados->fetchAll() ?: [];

        // 3. Servicios más solicitados
        $stmtServicios = $pdo->prepare("
            SELECT 
                s.id, 
                s.nombre, 
                COUNT(r.id) as total_citas, 
                COALESCE(SUM(r.precio_acordado), 0) as total_ingresos,
                COALESCE(AVG(r.precio_acordado), 0) as precio_promedio
            FROM reservas r
            JOIN servicios s ON s.id = r.servicio_id
            WHERE r.negocio_id = ? 
              AND r.fecha BETWEEN ? AND ?
              AND r.estado != 'cancelada'
            GROUP BY s.id, s.nombre
            ORDER BY total_citas DESC, total_ingresos DESC
            LIMIT 5
        ");
        $stmtServicios->execute([$negocioId, $fechaInicio, $fechaFin]);
        $topServicios = $stmtServicios->fetchAll() ?: [];

        // 4. Horas de mayor demanda
        $stmtHoras = $pdo->prepare("
            SELECT 
                HOUR(hora_inicio) as hora, 
                COUNT(*) as total_citas
            FROM reservas 
            WHERE negocio_id = ? 
              AND fecha BETWEEN ? AND ?
            GROUP BY HOUR(hora_inicio)
            ORDER BY total_citas DESC
        ");
        $stmtHoras->execute([$negocioId, $fechaInicio, $fechaFin]);
        $horasPico = $stmtHoras->fetchAll() ?: [];

        // 5. Rendimiento por Profesional
        $stmtProf = $pdo->prepare("
            SELECT 
                p.id, 
                p.nombre, 
                COUNT(r.id) as total_citas,
                COALESCE(SUM(CASE WHEN r.estado IN ('completada', 'confirmada') THEN r.precio_acordado ELSE 0 END), 0) as total_ingresos
            FROM reservas r
            JOIN profesionales p ON p.id = r.profesional_id
            WHERE r.negocio_id = ? 
              AND r.fecha BETWEEN ? AND ?
            GROUP BY p.id, p.nombre
            ORDER BY total_citas DESC
        ");
        $stmtProf->execute([$negocioId, $fechaInicio, $fechaFin]);
        $profesionales = $stmtProf->fetchAll() ?: [];

        return [
            'periodo' => [
                'inicio' => $fechaInicio,
                'fin' => $fechaFin
            ],
            'resumen' => $resumen,
            'por_estado' => $porEstado,
            'top_servicios' => $topServicios,
            'horas_pico' => $horasPico,
            'por_profesional' => $profesionales
        ];
    }
}
