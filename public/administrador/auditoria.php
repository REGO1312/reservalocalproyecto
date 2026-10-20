<?php
/**
 * Panel Administrador Global - Registro de Auditoría de Seguridad
 */

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_role('administrador');

$limit = 50;
$page = max(1, (int)($_GET['p'] ?? 1));
$offset = ($page - 1) * $limit;

$logs = Auditoria::getAll($limit, $offset);

$pageTitle = 'Auditoría del Sistema - Administrador';
$panelType = 'administrador';
require APP_ROOT . '/app/views/layouts/header.php';
?>

<div class="panel-layout">
    <?php require APP_ROOT . '/app/views/layouts/sidebar.php'; ?>

    <main class="panel-main">
        <div class="d-flex justify-between align-center mb-4">
            <div>
                <h1>Registro de Auditoría</h1>
                <p class="text-muted" style="margin-bottom:0;">Trazabilidad completa de operaciones administrativas, cambios de estado y accesos</p>
            </div>
            <span class="badge" style="background:var(--bg-surface); border:1px solid var(--border-color); font-size:0.85rem; padding:0.4rem 0.8rem;">
                Página <?= $page ?>
            </span>
        </div>

        <div class="card">
            <div class="card-body" style="padding:0;">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Fecha / Hora</th>
                                <th>Usuario</th>
                                <th>Acción</th>
                                <th>Entidad</th>
                                <th>ID Entidad</th>
                                <th>Detalles del Evento</th>
                                <th>Dirección IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $l): ?>
                                <tr>
                                    <td><small class="text-muted"><?= $l['creado_en'] ?></small></td>
                                    <td>
                                        <strong><?= e($l['usuario_nombre'] ?: 'Sistema / No Autenticado') ?></strong><br>
                                        <small class="text-muted"><?= e($l['usuario_email'] ?: '-') ?></small>
                                    </td>
                                    <td><code><?= e($l['accion']) ?></code></td>
                                    <td><span class="badge" style="background:#f1f5f9; color:var(--text-secondary);"><?= e($l['entidad']) ?></span></td>
                                    <td><?= $l['entidad_id'] ? '#' . $l['entidad_id'] : '-' ?></td>
                                    <td><?= e($l['detalles'] ?: '-') ?></td>
                                    <td><code><?= e($l['ip']) ?></code></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer d-flex justify-between align-center">
                <?php if ($page > 1): ?>
                    <a href="?p=<?= $page - 1 ?>" class="btn btn-outline btn-sm">&larr; Página Anterior</a>
                <?php else: ?>
                    <span></span>
                <?php endif; ?>

                <?php if (count($logs) === $limit): ?>
                    <a href="?p=<?= $page + 1 ?>" class="btn btn-outline btn-sm">Página Siguiente &rarr;</a>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php require APP_ROOT . '/app/views/layouts/footer.php'; ?>
