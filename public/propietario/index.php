<?php
/**
 * Panel Propietario - Resumen General y Métricas
 */

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_role('propietario');

$ownerId = auth_id();
$negocios = Negocio::findByOwner($ownerId);

if (empty($negocios)) {
    // Propietario sin negocio registrado aún
    redirect('/propietario/crear_negocio.php');
}

$activeNegocioId = (int)($_GET['negocio_id'] ?? $_SESSION['current_negocio_id'] ?? $negocios[0]['id']);
$_SESSION['current_negocio_id'] = $activeNegocioId;

$negocioActual = Negocio::findById($activeNegocioId);
if (!$negocioActual || !Negocio::isOwner($ownerId, $activeNegocioId)) {
    $activeNegocioId = (int)$negocios[0]['id'];
    $_SESSION['current_negocio_id'] = $activeNegocioId;
    $negocioActual = $negocios[0];
}

// Métricas del día y mes
$hoy = date('Y-m-d');
$primerDiaMes = date('Y-m-01');
$ultimoDiaMes = date('Y-m-t');

$pdo = Database::getConnection();

// Citas de hoy
$stmtHoy = $pdo->prepare("
    SELECT COUNT(*) as total, 
           COALESCE(SUM(CASE WHEN estado IN ('confirmada', 'completada') THEN precio_acordado ELSE 0 END), 0) as ingresos
    FROM reservas 
    WHERE negocio_id = ? AND fecha = ?
");
$stmtHoy->execute([$activeNegocioId, $hoy]);
$metricasHoy = $stmtHoy->fetch();

// Citas del mes
$stmtMes = $pdo->prepare("
    SELECT COUNT(*) as total, 
           COALESCE(SUM(CASE WHEN estado = 'completada' THEN precio_acordado ELSE 0 END), 0) as ingresos_completados
    FROM reservas 
    WHERE negocio_id = ? AND fecha BETWEEN ? AND ?
");
$stmtMes->execute([$activeNegocioId, $primerDiaMes, $ultimoDiaMes]);
$metricasMes = $stmtMes->fetch();

// Citas programadas para hoy
$citasHoy = Reserva::findByNegocio($activeNegocioId, $hoy, $hoy);

// Totales de servicios y staff
$totalServicios = count(Servicio::findByNegocio($activeNegocioId, true));
$totalProfesionales = count(Profesional::findByNegocio($activeNegocioId, true));

$pageTitle = 'Panel Propietario - ' . $negocioActual['nombre'];
$panelType = 'propietario';
require APP_ROOT . '/app/views/layouts/header.php';
?>

<div class="panel-layout">
    <?php require APP_ROOT . '/app/views/layouts/sidebar.php'; ?>

    <main class="panel-main">
        <div class="d-flex justify-between align-center mb-4" style="flex-wrap:wrap; gap:1rem;">
            <div>
                <h1>Panel de Control</h1>
                <p class="text-muted" style="margin-bottom:0;">Gestión en tiempo real de tu establecimiento</p>
            </div>

            <!-- Selector de Negocio si tiene varios -->
            <?php if (count($negocios) > 1): ?>
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    <label for="selectNeg" class="text-muted" style="font-size:0.9rem;">Negocio:</label>
                    <select id="selectNeg" class="form-control" onchange="window.location.href='?negocio_id=' + this.value;">
                        <?php foreach ($negocios as $n): ?>
                            <option value="<?= $n['id'] ?>" <?= $n['id'] == $activeNegocioId ? 'selected' : '' ?>>
                                <?= e($n['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else: ?>
                <div class="badge badge-confirmada" style="font-size:0.85rem; padding:0.4rem 0.8rem;">
                    🏢 <?= e($negocioActual['nombre']) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tarjetas de Métricas -->
        <div class="grid grid-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <span class="text-muted" style="font-size:0.85rem;">Citas para Hoy</span>
                    <h2 style="font-size:2rem; margin:0.3rem 0; color:var(--primary);"><?= $metricasHoy['total'] ?></h2>
                    <small class="text-muted"><?= format_money($metricasHoy['ingresos']) ?> proyectados</small>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <span class="text-muted" style="font-size:0.85rem;">Ingresos Mes Actual</span>
                    <h2 style="font-size:2rem; margin:0.3rem 0; color:var(--success);"><?= format_money($metricasMes['ingresos_completados']) ?></h2>
                    <small class="text-muted"><?= $metricasMes['total'] ?> citas este mes</small>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <span class="text-muted" style="font-size:0.85rem;">Especialistas Activos</span>
                    <h2 style="font-size:2rem; margin:0.3rem 0;"><?= $totalProfesionales ?></h2>
                    <a href="<?= url('/propietario/profesionales.php') ?>" style="font-size:0.85rem;">Gestionar equipo &rarr;</a>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <span class="text-muted" style="font-size:0.85rem;">Servicios en Catálogo</span>
                    <h2 style="font-size:2rem; margin:0.3rem 0;"><?= $totalServicios ?></h2>
                    <a href="<?= url('/propietario/servicios.php') ?>" style="font-size:0.85rem;">Ver servicios &rarr;</a>
                </div>
            </div>
        </div>

        <!-- Citas de Hoy -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-between align-center">
                <h3>Agenda de Hoy (<?= format_date_es($hoy) ?>)</h3>
                <a href="<?= url('/propietario/calendario.php') ?>" class="btn btn-outline btn-sm">Ver Calendario Completo</a>
            </div>

            <div class="card-body" style="padding:0;">
                <?php if (empty($citasHoy)): ?>
                    <div class="text-center py-5 text-muted">
                        No hay citas programadas para el día de hoy.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Horario</th>
                                    <th>Cliente</th>
                                    <th>Servicio</th>
                                    <th>Especialista</th>
                                    <th>Precio</th>
                                    <th>Estado</th>
                                    <th>Acción Rápida</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($citasHoy as $cita): ?>
                                    <tr>
                                        <td><strong><?= substr($cita['hora_inicio'], 0, 5) ?> - <?= substr($cita['hora_fin'], 0, 5) ?></strong></td>
                                        <td>
                                            <?= e($cita['cliente_nombre']) ?><br>
                                            <small class="text-muted"><?= e($cita['cliente_telefono'] ?: $cita['cliente_email']) ?></small>
                                        </td>
                                        <td><?= e($cita['servicio_nombre']) ?></td>
                                        <td><?= e($cita['profesional_nombre']) ?></td>
                                        <td><strong><?= format_money($cita['precio_acordado']) ?></strong></td>
                                        <td><span class="badge badge-<?= e($cita['estado']) ?>"><?= strtoupper(e($cita['estado'])) ?></span></td>
                                        <td>
                                            <select class="form-control form-control-sm select-cambiar-estado" 
                                                    data-id="<?= $cita['id'] ?>" 
                                                    style="width: auto; display: inline-block;">
                                                <option value="confirmada" <?= $cita['estado'] === 'confirmada' ? 'selected' : '' ?>>Confirmada</option>
                                                <option value="completada" <?= $cita['estado'] === 'completada' ? 'selected' : '' ?>>Completada</option>
                                                <option value="no_asistio" <?= $cita['estado'] === 'no_asistio' ? 'selected' : '' ?>>No asistió</option>
                                                <option value="cancelada" <?= $cita['estado'] === 'cancelada' ? 'selected' : '' ?>>Cancelar</option>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.select-cambiar-estado').forEach(sel => {
        sel.addEventListener('change', async () => {
            const id = sel.dataset.id;
            const nuevoEstado = sel.value;

            const formData = new FormData();
            formData.append('reserva_id', id);
            formData.append('estado', nuevoEstado);
            formData.append('csrf_token', '<?= csrf_token() ?>');

            try {
                const res = await apiFetch('<?= url("/api/cambiar_estado.php") ?>', {
                    method: 'POST',
                    body: formData
                });
                if (res.success) {
                    window.location.reload();
                }
            } catch (err) {
                alert('Error al actualizar estado: ' + err.message);
                window.location.reload();
            }
        });
    });
});
</script>

<?php require APP_ROOT . '/app/views/layouts/footer.php'; ?>
