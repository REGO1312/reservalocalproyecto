<?php
/**
 * Panel Propietario - Reportes Estadísticos y Financieros
 */

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_role('propietario');

$ownerId = auth_id();
$negocios = Negocio::findByOwner($ownerId);
if (empty($negocios)) redirect('/propietario/');

$activeNegocioId = (int)($_SESSION['current_negocio_id'] ?? $negocios[0]['id']);
$negocioActual = Negocio::findById($activeNegocioId);

// Rango de fechas por defecto: últimos 30 días
$fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
$fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));

$reporte = ReporteService::generarReporte($activeNegocioId, $fechaInicio, $fechaFin);

$pageTitle = 'Reportes de Negocio - ' . $negocioActual['nombre'];
$panelType = 'propietario';
require APP_ROOT . '/app/views/layouts/header.php';
?>

<div class="panel-layout">
    <?php require APP_ROOT . '/app/views/layouts/sidebar.php'; ?>

    <main class="panel-main">
        <div class="d-flex justify-between align-center mb-4" style="flex-wrap:wrap; gap:1rem;">
            <div>
                <h1>Reportes y Estadísticas</h1>
                <p class="text-muted" style="margin-bottom:0;">Métricas reales de demanda, facturación y horas pico</p>
            </div>
            <div class="badge badge-confirmada" style="font-size:0.85rem; padding:0.4rem 0.8rem;">
                🏢 <?= e($negocioActual['nombre']) ?>
            </div>
        </div>

        <!-- Filtro de Fechas -->
        <div class="card mb-4">
            <div class="card-body">
                <form action="<?= url('/propietario/reportes.php') ?>" method="GET" class="d-flex gap-3 align-center" style="flex-wrap:wrap;">
                    <div style="flex:1; min-width:200px;">
                        <label for="fecha_inicio" class="form-label">Desde</label>
                        <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control" value="<?= e($fechaInicio) ?>">
                    </div>

                    <div style="flex:1; min-width:200px;">
                        <label for="fecha_fin" class="form-label">Hasta</label>
                        <input type="date" id="fecha_fin" name="fecha_fin" class="form-control" value="<?= e($fechaFin) ?>">
                    </div>

                    <div style="padding-top:1.5rem; display:flex; gap:0.5rem;">
                        <button type="submit" class="btn btn-primary">Generar Reporte</button>
                        <a href="?fecha_inicio=<?= date('Y-m-01') ?>&fecha_fin=<?= date('Y-m-t') ?>" class="btn btn-outline">Mes Actual</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tarjetas de Resumen del Período -->
        <div class="grid grid-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <span class="text-muted" style="font-size:0.85rem;">Total Reservas</span>
                    <h2 style="font-size:2rem; margin:0.3rem 0; color:var(--primary);"><?= $reporte['resumen']['total_reservas'] ?? 0 ?></h2>
                    <small class="text-muted">En el período consultado</small>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <span class="text-muted" style="font-size:0.85rem;">Ingresos Confirmados/Cobrados</span>
                    <h2 style="font-size:2rem; margin:0.3rem 0; color:var(--success);"><?= format_money($reporte['resumen']['ingresos_totales'] ?? 0) ?></h2>
                    <small class="text-muted">Suma de precios pactados</small>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <span class="text-muted" style="font-size:0.85rem;">Completadas con Éxito</span>
                    <h2 style="font-size:2rem; margin:0.3rem 0; color:var(--info);"><?= $reporte['resumen']['completadas'] ?? 0 ?></h2>
                    <small class="text-muted"><?= $reporte['resumen']['confirmadas'] ?? 0 ?> confirmadas pendientes</small>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <span class="text-muted" style="font-size:0.85rem;">Cancelaciones / No Asistió</span>
                    <h2 style="font-size:2rem; margin:0.3rem 0; color:var(--danger);"><?= ($reporte['resumen']['canceladas'] ?? 0) + ($reporte['resumen']['no_asistio'] ?? 0) ?></h2>
                    <small class="text-muted"><?= $reporte['resumen']['canceladas'] ?? 0 ?> canceladas &bull; <?= $reporte['resumen']['no_asistio'] ?? 0 ?> no asistieron</small>
                </div>
            </div>
        </div>

        <div class="grid grid-2 mb-4">
            <!-- Servicios Más Solicitados -->
            <div class="card">
                <div class="card-header">
                    <h3>Top 5 Servicios Más Solicitados</h3>
                </div>
                <div class="card-body" style="padding:0;">
                    <?php if (empty($reporte['top_servicios'])): ?>
                        <div class="text-center py-4 text-muted">Sin datos suficientes en este rango.</div>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Servicio</th>
                                    <th>Citas</th>
                                    <th>Ingresos</th>
                                    <th>Promedio</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reporte['top_servicios'] as $s): ?>
                                    <tr>
                                        <td><strong><?= e($s['nombre']) ?></strong></td>
                                        <td><span class="badge" style="background:#e0f2fe; color:#0369a1;"><?= $s['total_citas'] ?></span></td>
                                        <td><strong><?= format_money($s['total_ingresos']) ?></strong></td>
                                        <td><?= format_money($s['precio_promedio']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Horas de Mayor Demanda (Horas Pico) -->
            <div class="card">
                <div class="card-header">
                    <h3>Horas de Mayor Demanda (Horas Pico)</h3>
                </div>
                <div class="card-body" style="padding:0;">
                    <?php if (empty($reporte['horas_pico'])): ?>
                        <div class="text-center py-4 text-muted">Sin datos suficientes en este rango.</div>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Franja Horaria</th>
                                    <th>Total de Citas Solicitadas</th>
                                    <th>Gráfico de Frecuencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $maxCitas = max(array_column($reporte['horas_pico'], 'total_citas') ?: [1]);
                                foreach ($reporte['horas_pico'] as $h): 
                                    $pct = round(($h['total_citas'] / $maxCitas) * 100);
                                ?>
                                    <tr>
                                        <td><strong><?= str_pad($h['hora'], 2, '0', STR_PAD_LEFT) ?>:00 - <?= str_pad($h['hora'] + 1, 2, '0', STR_PAD_LEFT) ?>:00</strong></td>
                                        <td><?= $h['total_citas'] ?> citas</td>
                                        <td style="width: 40%;">
                                            <div style="background:#e2e8f0; border-radius:4px; height:14px; width:100%; overflow:hidden;">
                                                <div style="background:var(--primary); height:100%; width:<?= $pct ?>%;"></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Rendimiento por Especialista -->
        <div class="card">
            <div class="card-header">
                <h3>Rendimiento por Especialista</h3>
            </div>
            <div class="card-body" style="padding:0;">
                <?php if (empty($reporte['por_profesional'])): ?>
                    <div class="text-center py-4 text-muted">Sin datos de especialistas en este rango.</div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Especialista</th>
                                <th>Total de Citas Atendidas</th>
                                <th>Ingresos Generados</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reporte['por_profesional'] as $p): ?>
                                <tr>
                                    <td><strong><?= e($p['nombre']) ?></strong></td>
                                    <td><?= $p['total_citas'] ?> citas</td>
                                    <td><strong><?= format_money($p['total_ingresos']) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php require APP_ROOT . '/app/views/layouts/footer.php'; ?>
