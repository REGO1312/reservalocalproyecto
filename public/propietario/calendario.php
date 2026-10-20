<?php
/**
 * Panel Propietario - Calendario y Agendas por Profesional
 */

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_role('propietario');

$ownerId = auth_id();
$negocios = Negocio::findByOwner($ownerId);
if (empty($negocios)) redirect('/propietario/');

$activeNegocioId = (int)($_SESSION['current_negocio_id'] ?? $negocios[0]['id']);
$negocioActual = Negocio::findById($activeNegocioId);

$profesionales = Profesional::findByNegocio($activeNegocioId, false);

// Filtros
$fecha = $_GET['fecha'] ?? date('Y-m-d');
$profesionalId = !empty($_GET['profesional_id']) ? (int)$_GET['profesional_id'] : null;
$estado = $_GET['estado'] ?? null;

$citas = Reserva::findByNegocio($activeNegocioId, $fecha, $fecha, $profesionalId, $estado);

$pageTitle = 'Calendario de Citas - ' . $negocioActual['nombre'];
$panelType = 'propietario';
require APP_ROOT . '/app/views/layouts/header.php';
?>

<div class="panel-layout">
    <?php require APP_ROOT . '/app/views/layouts/sidebar.php'; ?>

    <main class="panel-main">
        <div class="d-flex justify-between align-center mb-4" style="flex-wrap:wrap; gap:1rem;">
            <div>
                <h1>Calendario y Agenda</h1>
                <p class="text-muted" style="margin-bottom:0;">Visualiza y gestiona las citas por fecha y especialista</p>
            </div>
            <div class="badge badge-confirmada" style="font-size:0.85rem; padding:0.4rem 0.8rem;">
                🏢 <?= e($negocioActual['nombre']) ?>
            </div>
        </div>

        <!-- Filtros de fecha y especialista -->
        <div class="card mb-4">
            <div class="card-body">
                <form action="<?= url('/propietario/calendario.php') ?>" method="GET" class="d-flex gap-3 align-center" style="flex-wrap:wrap;">
                    <div style="flex:1; min-width:200px;">
                        <label for="fecha" class="form-label">Fecha de Consulta</label>
                        <input type="date" id="fecha" name="fecha" class="form-control" value="<?= e($fecha) ?>" onchange="this.form.submit()">
                    </div>

                    <div style="flex:1; min-width:200px;">
                        <label for="profesional_id" class="form-label">Filtrar por Especialista</label>
                        <select id="profesional_id" name="profesional_id" class="form-control" onchange="this.form.submit()">
                            <option value="">Todos los especialistas</option>
                            <?php foreach ($profesionales as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= $profesionalId == $p['id'] ? 'selected' : '' ?>>
                                    <?= e($p['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="flex:1; min-width:160px;">
                        <label for="estado" class="form-label">Estado</label>
                        <select id="estado" name="estado" class="form-control" onchange="this.form.submit()">
                            <option value="">Todos los estados</option>
                            <option value="confirmada" <?= $estado === 'confirmada' ? 'selected' : '' ?>>Confirmadas</option>
                            <option value="pendiente" <?= $estado === 'pendiente' ? 'selected' : '' ?>>Pendientes</option>
                            <option value="completada" <?= $estado === 'completada' ? 'selected' : '' ?>>Completadas</option>
                            <option value="no_asistio" <?= $estado === 'no_asistio' ? 'selected' : '' ?>>No Asistió</option>
                            <option value="cancelada" <?= $estado === 'cancelada' ? 'selected' : '' ?>>Canceladas</option>
                        </select>
                    </div>

                    <div style="padding-top:1.5rem;">
                        <a href="<?= url('/propietario/calendario.php') ?>" class="btn btn-outline">Hoy</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Vista de la agenda -->
        <div class="card">
            <div class="card-header d-flex justify-between align-center">
                <h3>Citas del <?= format_date_es($fecha) ?> (Total: <?= count($citas) ?>)</h3>
            </div>
            <div class="card-body" style="padding:0;">
                <?php if (empty($citas)): ?>
                    <div class="text-center py-5 text-muted">
                        No hay citas programadas para esta fecha con los filtros seleccionados.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Hora</th>
                                    <th>Código</th>
                                    <th>Cliente</th>
                                    <th>Servicio</th>
                                    <th>Especialista</th>
                                    <th>Precio</th>
                                    <th>Notas</th>
                                    <th>Estado</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($citas as $c): ?>
                                    <tr>
                                        <td><strong class="text-primary"><?= substr($c['hora_inicio'], 0, 5) ?> - <?= substr($c['hora_fin'], 0, 5) ?></strong></td>
                                        <td><code><?= e($c['codigo_reserva']) ?></code></td>
                                        <td>
                                            <?= e($c['cliente_nombre']) ?><br>
                                            <small class="text-muted"><?= e($c['cliente_telefono'] ?: $c['cliente_email']) ?></small>
                                        </td>
                                        <td><?= e($c['servicio_nombre']) ?></td>
                                        <td><strong><?= e($c['profesional_nombre']) ?></strong></td>
                                        <td><?= format_money($c['precio_acordado']) ?></td>
                                        <td><small class="text-muted"><?= e($c['notas_cliente'] ?: '-') ?></small></td>
                                        <td><span class="badge badge-<?= e($c['estado']) ?>"><?= strtoupper(e($c['estado'])) ?></span></td>
                                        <td>
                                            <select class="form-control form-control-sm select-cambiar-estado" data-id="<?= $c['id'] ?>">
                                                <option value="confirmada" <?= $c['estado'] === 'confirmada' ? 'selected' : '' ?>>Confirmar</option>
                                                <option value="completada" <?= $c['estado'] === 'completada' ? 'selected' : '' ?>>Completar</option>
                                                <option value="no_asistio" <?= $c['estado'] === 'no_asistio' ? 'selected' : '' ?>>No asistió</option>
                                                <option value="cancelada" <?= $c['estado'] === 'cancelada' ? 'selected' : '' ?>>Cancelar</option>
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
