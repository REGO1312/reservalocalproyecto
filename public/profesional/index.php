<?php
/**
 * Panel Profesional - Agenda Personal y Gestión de Citas
 */

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_role('profesional');

$userId = auth_id();
$perfiles = Profesional::findByUserId($userId);

if (empty($perfiles)) {
    // Usuario con rol profesional pero aún sin perfil asignado por un propietario
    $pageTitle = 'Agenda Personal';
    require APP_ROOT . '/app/views/layouts/header.php';
    ?>
    <div class="container py-5 text-center">
        <div class="card container-sm py-4">
            <h2>Agenda Personal</h2>
            <p class="text-muted">Tu cuenta tiene el rol de profesional, pero aún no has sido vinculado como especialista por el propietario de un negocio.</p>
            <p class="text-muted">Proporciona tu correo electrónico (<code><?= e(auth_user()['email']) ?></code>) al dueño de la barbería o salón para vincular tu agenda.</p>
            <div class="mt-3">
                <a href="<?= url('/') ?>" class="btn btn-primary">Volver al Inicio</a>
            </div>
        </div>
    </div>
    <?php
    require APP_ROOT . '/app/views/layouts/footer.php';
    exit;
}

$activeProfId = (int)($_GET['profesional_id'] ?? $perfiles[0]['id']);
$profActual = null;
foreach ($perfiles as $p) {
    if ($p['id'] == $activeProfId) {
        $profActual = $p;
        break;
    }
}
if (!$profActual) {
    $profActual = $perfiles[0];
    $activeProfId = (int)$profActual['id'];
}

$fecha = $_GET['fecha'] ?? date('Y-m-d');
$citas = Reserva::findByProfesional($activeProfId, $fecha);

$pageTitle = 'Mi Agenda - ' . $profActual['nombre'];
$panelType = 'profesional';
require APP_ROOT . '/app/views/layouts/header.php';
?>

<div class="panel-layout">
    <?php require APP_ROOT . '/app/views/layouts/sidebar.php'; ?>

    <main class="panel-main">
        <div class="d-flex justify-between align-center mb-4" style="flex-wrap:wrap; gap:1rem;">
            <div>
                <h1>Mi Agenda Personal</h1>
                <p class="text-muted" style="margin-bottom:0;">
                    Especialista: <strong><?= e($profActual['nombre']) ?></strong> &bull; Establecimiento: <strong><?= e($profActual['negocio_nombre']) ?></strong>
                </p>
            </div>

            <?php if (count($perfiles) > 1): ?>
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    <label for="selectP" class="text-muted">Establecimiento:</label>
                    <select id="selectP" class="form-control" onchange="window.location.href='?profesional_id=' + this.value;">
                        <?php foreach ($perfiles as $pr): ?>
                            <option value="<?= $pr['id'] ?>" <?= $pr['id'] == $activeProfId ? 'selected' : '' ?>>
                                <?= e($pr['negocio_nombre']) ?> (<?= e($pr['nombre']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
        </div>

        <!-- Filtro de Fecha -->
        <div class="card mb-4">
            <div class="card-body">
                <form action="<?= url('/profesional/') ?>" method="GET" class="d-flex gap-3 align-center" style="flex-wrap:wrap;">
                    <input type="hidden" name="profesional_id" value="<?= $activeProfId ?>">

                    <div style="flex:1; min-width:220px;">
                        <label for="fecha" class="form-label">Fecha de Agenda</label>
                        <input type="date" id="fecha" name="fecha" class="form-control" value="<?= e($fecha) ?>" onchange="this.form.submit()">
                    </div>

                    <div style="padding-top:1.5rem; display:flex; gap:0.5rem;">
                        <a href="?profesional_id=<?= $activeProfId ?>&fecha=<?= date('Y-m-d') ?>" class="btn <?= $fecha === date('Y-m-d') ? 'btn-primary' : 'btn-outline' ?>">Hoy</a>
                        <a href="?profesional_id=<?= $activeProfId ?>&fecha=<?= date('Y-m-d', strtotime('+1 day')) ?>" class="btn btn-outline">Mañana</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Listado de Citas de la Agenda -->
        <div class="card">
            <div class="card-header d-flex justify-between align-center">
                <h3>Citas del <?= format_date_es($fecha) ?> (Total: <?= count($citas) ?>)</h3>
            </div>
            <div class="card-body" style="padding:0;">
                <?php if (empty($citas)): ?>
                    <div class="text-center py-5 text-muted">
                        No tienes citas agendadas para este día. ¡Tiempo libre o día de descanso!
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Horario</th>
                                    <th>Código</th>
                                    <th>Cliente</th>
                                    <th>Servicio Solicitado</th>
                                    <th>Precio</th>
                                    <th>Notas del Cliente</th>
                                    <th>Estado</th>
                                    <th>Actualizar Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($citas as $c): ?>
                                    <tr>
                                        <td>
                                            <strong class="text-primary" style="font-size:1.05rem;">
                                                <?= substr($c['hora_inicio'], 0, 5) ?> - <?= substr($c['hora_fin'], 0, 5) ?>
                                            </strong>
                                        </td>
                                        <td><code><?= e($c['codigo_reserva']) ?></code></td>
                                        <td>
                                            <strong><?= e($c['cliente_nombre']) ?></strong><br>
                                            <small class="text-muted">📞 <?= e($c['cliente_telefono'] ?: $c['cliente_email']) ?></small>
                                        </td>
                                        <td><?= e($c['servicio_nombre']) ?></td>
                                        <td><strong><?= format_money($c['precio_acordado']) ?></strong></td>
                                        <td><small class="text-muted"><?= e($c['notas_cliente'] ?: '-') ?></small></td>
                                        <td><span class="badge badge-<?= e($c['estado']) ?>"><?= strtoupper(e($c['estado'])) ?></span></td>
                                        <td>
                                            <select class="form-control form-control-sm select-cambiar-estado" data-id="<?= $c['id'] ?>">
                                                <option value="confirmada" <?= $c['estado'] === 'confirmada' ? 'selected' : '' ?>>Confirmada</option>
                                                <option value="completada" <?= $c['estado'] === 'completada' ? 'selected' : '' ?>>Completada</option>
                                                <option value="no_asistio" <?= $c['estado'] === 'no_asistio' ? 'selected' : '' ?>>No Asistió</option>
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
