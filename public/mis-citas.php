<?php
/**
 * Reserva Local - Mis Citas (Portal de Cliente)
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_login();

$clienteId = auth_id();
$filtro = $_GET['filtro'] ?? 'todas'; // 'todas', 'proximas', 'completadas', 'canceladas'

$todas = Reserva::findByCliente($clienteId);

// Filtrar según pestaña
$hoy = date('Y-m-d');
$citas = array_filter($todas, function($r) use ($filtro, $hoy) {
    if ($filtro === 'proximas') {
        return ($r['fecha'] >= $hoy) && in_array($r['estado'], ['confirmada', 'pendiente']);
    } elseif ($filtro === 'completadas') {
        return $r['estado'] === 'completada' || ($r['fecha'] < $hoy && $r['estado'] === 'confirmada');
    } elseif ($filtro === 'canceladas') {
        return $r['estado'] === 'cancelada';
    }
    return true;
});

$pageTitle = 'Mis Citas';
require APP_ROOT . '/app/views/layouts/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-between align-center mb-4" style="flex-wrap:wrap; gap:1rem;">
        <div>
            <h1>Mis Citas Reservadas</h1>
            <p class="text-muted" style="margin-bottom:0;">Consulta tu historial y gestiona tus próximas citas.</p>
        </div>
        <a href="<?= url('/explorar.php') ?>" class="btn btn-primary">
            + Agendar Nueva Cita
        </a>
    </div>

    <!-- Pestañas de Filtro -->
    <div style="display:flex; gap:0.5rem; border-bottom:1px solid var(--border-color); margin-bottom:1.5rem; overflow-x:auto;">
        <a href="<?= url('/mis-citas.php?filtro=todas') ?>" 
           class="nav-link <?= $filtro === 'todas' ? 'active' : '' ?>" style="border-radius: var(--radius-sm) var(--radius-sm) 0 0;">
            Todas (<?= count($todas) ?>)
        </a>
        <a href="<?= url('/mis-citas.php?filtro=proximas') ?>" 
           class="nav-link <?= $filtro === 'proximas' ? 'active' : '' ?>">
            Próximas
        </a>
        <a href="<?= url('/mis-citas.php?filtro=completadas') ?>" 
           class="nav-link <?= $filtro === 'completadas' ? 'active' : '' ?>">
            Historial / Completadas
        </a>
        <a href="<?= url('/mis-citas.php?filtro=canceladas') ?>" 
           class="nav-link <?= $filtro === 'canceladas' ? 'active' : '' ?>">
            Canceladas
        </a>
    </div>

    <?php if (empty($citas)): ?>
        <div class="card text-center py-5">
            <div style="font-size:3rem; margin-bottom:1rem;">📅</div>
            <h3>No tienes citas en esta sección</h3>
            <p class="text-muted">Explora los mejores salones y barberías para agendar tu primera cita.</p>
            <div class="mt-3">
                <a href="<?= url('/explorar.php') ?>" class="btn btn-primary">Explorar Negocios</a>
            </div>
        </div>
    <?php else: ?>
        <div class="grid grid-2">
            <?php foreach ($citas as $c): 
                $puedeCancelar = in_array($c['estado'], ['confirmada', 'pendiente']) && ($c['fecha'] >= date('Y-m-d'));
            ?>
                <div class="card card-hover">
                    <div class="card-header d-flex justify-between align-center">
                        <div>
                            <span class="badge badge-<?= e($c['negocio_categoria']) ?>">
                                <?= $c['negocio_categoria'] === 'barberia' ? '💈 Barbería' : '💇‍♀️ Salón' ?>
                            </span>
                            <span style="font-size:0.8rem; color:var(--text-muted); margin-left:0.5rem;">
                                Código: <strong><?= e($c['codigo_reserva']) ?></strong>
                            </span>
                        </div>
                        <span class="badge badge-<?= e($c['estado']) ?>">
                            <?= strtoupper(e($c['estado'])) ?>
                        </span>
                    </div>

                    <div class="card-body">
                        <h3 style="margin-bottom:0.25rem;"><?= e($c['servicio_nombre']) ?></h3>
                        <p class="text-muted" style="margin-bottom:0.75rem;">
                            <strong><?= e($c['negocio_nombre']) ?></strong> &bull; <?= e($c['negocio_direccion']) ?>
                        </p>

                        <div style="background:var(--bg-subtle); padding:0.75rem 1rem; border-radius:var(--radius-md); font-size:0.9rem; margin-bottom:1rem;">
                            <div class="d-flex justify-between py-1">
                                <span class="text-muted">Especialista:</span>
                                <strong><?= e($c['profesional_nombre']) ?></strong>
                            </div>
                            <div class="d-flex justify-between py-1">
                                <span class="text-muted">Fecha:</span>
                                <strong><?= format_date_es($c['fecha']) ?></strong>
                            </div>
                            <div class="d-flex justify-between py-1">
                                <span class="text-muted">Horario:</span>
                                <strong class="text-primary"><?= substr($c['hora_inicio'], 0, 5) ?> - <?= substr($c['hora_fin'], 0, 5) ?></strong>
                            </div>
                            <div class="d-flex justify-between py-1" style="border-top:1px dashed #cbd5e1; margin-top:0.35rem; padding-top:0.35rem;">
                                <span class="text-muted">Precio acordado:</span>
                                <strong style="font-size:1.05rem; color:var(--text-primary);"><?= format_money($c['precio_acordado']) ?></strong>
                            </div>
                        </div>

                        <?php if (!empty($c['notas_cliente'])): ?>
                            <p style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:0.75rem;">
                                <em>"<?= e($c['notas_cliente']) ?>"</em>
                            </p>
                        <?php endif; ?>

                        <div class="d-flex gap-2 justify-between align-center">
                            <a href="<?= url('/negocio.php?id=' . $c['negocio_id']) ?>" class="btn btn-outline btn-sm">
                                Ver Negocio
                            </a>

                            <?php if ($puedeCancelar): ?>
                                <button type="button" 
                                        class="btn btn-danger btn-sm btn-cancelar-cita" 
                                        data-id="<?= $c['id'] ?>"
                                        data-codigo="<?= e($c['codigo_reserva']) ?>">
                                    Cancelar Cita
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.btn-cancelar-cita').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.dataset.id;
            const codigo = btn.dataset.codigo;
            
            const motivo = prompt(`¿Estás seguro de cancelar la cita ${codigo}?\nEscribe el motivo (opcional):`);
            if (motivo === null) return; // Canceló el prompt

            btn.disabled = true;
            btn.textContent = 'Cancelando...';

            const formData = new FormData();
            formData.append('reserva_id', id);
            formData.append('motivo', motivo);
            formData.append('csrf_token', '<?= csrf_token() ?>');

            try {
                const res = await apiFetch('<?= url("/api/cancelar.php") ?>', {
                    method: 'POST',
                    body: formData
                });
                if (res.success) {
                    window.location.reload();
                }
            } catch (err) {
                alert('Error al cancelar: ' + err.message);
                btn.disabled = false;
                btn.textContent = 'Cancelar Cita';
            }
        });
    });
});
</script>

<?php require APP_ROOT . '/app/views/layouts/footer.php'; ?>
