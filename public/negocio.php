<?php
/**
 * Reserva Local - Ficha Detallada del Negocio
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
$negocio = Negocio::findById($id);

if (!$negocio || $negocio['estado'] !== 'activo') {
    http_response_code(404);
    require APP_ROOT . '/app/views/layouts/404.php';
    exit;
}

$servicios = Servicio::findByNegocio($id, true);
$profesionales = Profesional::findByNegocio($id, true);
$horarios = Horario::getNegocioHorarios($id);

$pageTitle = $negocio['nombre'];
require APP_ROOT . '/app/views/layouts/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <p class="text-muted" style="font-size:0.9rem; margin-bottom:1rem;">
        <a href="<?= url('/explorar.php') ?>">&larr; Explorar Negocios</a> / <?= e($negocio['nombre']) ?>
    </p>

    <!-- Encabezado del Negocio -->
    <div class="card mb-4">
        <div style="height: 260px; background: #0f172a; position: relative; overflow: hidden;">
            <img src="<?= asset($negocio['imagen_url'] ?: 'assets/img/barberia-caballero.svg') ?>" alt="<?= e($negocio['nombre']) ?>" style="width:100%; height:100%; object-fit:cover;">
            <span class="badge badge-<?= e($negocio['categoria']) ?>" style="position: absolute; bottom: 15px; left: 20px; font-size: 0.9rem; padding: 0.4rem 0.8rem;">
                <?= $negocio['categoria'] === 'barberia' ? '💈 Barbería Tradicional' : '💇‍♀️ Salón de Belleza' ?>
            </span>
        </div>
        <div class="card-body">
            <div class="d-flex justify-between align-center" style="flex-wrap:wrap; gap:1rem;">
                <div>
                    <h1 style="margin-bottom:0.25rem;"><?= e($negocio['nombre']) ?></h1>
                    <p class="text-muted" style="margin-bottom:0.5rem;">📍 <?= e($negocio['direccion']) ?> (<?= e($negocio['zona_municipio']) ?>)</p>
                    <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 0;">
                        📞 <?= e($negocio['telefono'] ?: 'No especificado') ?> &nbsp;|&nbsp; ✉️ <?= e($negocio['email'] ?: 'No especificado') ?>
                    </p>
                </div>
                <div>
                    <a href="<?= url('/reservar.php?negocio_id=' . $negocio['id']) ?>" class="btn btn-primary btn-lg">
                        📅 Agendar Cita Ahora
                    </a>
                </div>
            </div>

            <?php if (!empty($negocio['descripcion'])): ?>
                <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border-color);">
                    <h3>Acerca de nosotros</h3>
                    <p style="color: var(--text-secondary);"><?= nl2br(e($negocio['descripcion'])) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Catálogo de Servicios y Horarios en Grid -->
    <div class="grid grid-2" style="grid-template-columns: 2fr 1fr; align-items: start;">
        <!-- Lista de Servicios Disponibles -->
        <div>
            <div class="card mb-4">
                <div class="card-header">
                    <h2>Catálogo de Servicios</h2>
                </div>
                <div class="card-body" style="padding:0;">
                    <?php if (empty($servicios)): ?>
                        <div style="padding:2rem;" class="text-center text-muted">No hay servicios registrados en este momento.</div>
                    <?php else: ?>
                        <div style="display:flex; flex-direction:column;">
                            <?php foreach ($servicios as $s): ?>
                                <div style="display:flex; justify-content:space-between; align-items:center; padding:1.25rem 1.5rem; border-bottom:1px solid var(--border-color);">
                                    <div style="flex:1; padding-right:1rem;">
                                        <h4 style="margin-bottom:0.25rem;"><?= e($s['nombre']) ?></h4>
                                        <p class="text-muted" style="font-size:0.85rem; margin-bottom:0.4rem;"><?= e($s['descripcion']) ?></p>
                                        <span class="badge" style="background:var(--bg-subtle); color:var(--text-secondary);">
                                            ⏱ <?= format_duration((int)$s['duracion_minutos']) ?>
                                        </span>
                                    </div>
                                    <div class="text-right" style="flex:0 0 auto;">
                                        <div style="font-size:1.25rem; font-weight:800; color:var(--text-primary); margin-bottom:0.5rem;">
                                            <?= format_money($s['precio']) ?>
                                        </div>
                                        <a href="<?= url('/reservar.php?negocio_id=' . $negocio['id'] . '&servicio_id=' . $s['id']) ?>" class="btn btn-primary btn-sm">
                                            Reservar
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Especialistas / Personal -->
            <div class="card">
                <div class="card-header">
                    <h2>Especialistas del Negocio</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($profesionales)): ?>
                        <p class="text-muted">No hay especialistas asignados.</p>
                    <?php else: ?>
                        <div class="grid grid-2">
                            <?php foreach ($profesionales as $p): ?>
                                <div style="display:flex; align-items:center; gap:0.85rem; padding:0.75rem; border:1px solid var(--border-color); border-radius:var(--radius-md);">
                                    <div class="user-avatar" style="width:42px; height:42px; font-size:1.1rem;">
                                        <?= strtoupper(substr($p['nombre'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <strong style="display:block;"><?= e($p['nombre']) ?></strong>
                                        <small class="text-muted"><?= e($p['especialidad'] ?: 'Especialista en Estilo') ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar: Horarios de Atención -->
        <div>
            <div class="card">
                <div class="card-header">
                    <h3>Horarios de Atención</h3>
                </div>
                <div class="card-body">
                    <table class="table" style="font-size:0.9rem;">
                        <tbody>
                            <?php for ($d = 1; $d <= 7; $d++): 
                                $h = $horarios[$d] ?? null;
                                $esHoy = ($d == (int)date('N'));
                            ?>
                                <tr style="<?= $esHoy ? 'background: #eff6ff; font-weight: bold;' : '' ?>">
                                    <td><?= day_name_es($d) ?> <?= $esHoy ? '<span class="badge badge-confirmada" style="font-size:0.65rem;">HOY</span>' : '' ?></td>
                                    <td class="text-right">
                                        <?php if ($h && !empty($h['es_laborable'])): ?>
                                            <?= substr($h['hora_apertura'], 0, 5) ?> - <?= substr($h['hora_cierre'], 0, 5) ?>
                                        <?php else: ?>
                                            <span class="text-danger">Cerrado</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer text-center">
                    <a href="<?= url('/reservar.php?negocio_id=' . $negocio['id']) ?>" class="btn btn-primary btn-block">
                        Reservar Turno
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require APP_ROOT . '/app/views/layouts/footer.php'; ?>
