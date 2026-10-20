<?php
/**
 * Panel Administrador Global - Resumen del Sistema
 */

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_role('administrador');

$pdo = Database::getConnection();

// Estadísticas globales
$totalUsuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$totalNegocios = $pdo->query("SELECT COUNT(*) FROM negocios")->fetchColumn();
$totalReservas = $pdo->query("SELECT COUNT(*) FROM reservas")->fetchColumn();
$totalAuditoria = $pdo->query("SELECT COUNT(*) FROM auditoria")->fetchColumn();

// Actividades recientes de auditoría
$logsRecientes = Auditoria::getAll(10, 0);

$pageTitle = 'Panel Administrador Global';
$panelType = 'administrador';
require APP_ROOT . '/app/views/layouts/header.php';
?>

<div class="panel-layout">
    <?php require APP_ROOT . '/app/views/layouts/sidebar.php'; ?>

    <main class="panel-main">
        <div class="d-flex justify-between align-center mb-4">
            <div>
                <h1>Administración General</h1>
                <p class="text-muted" style="margin-bottom:0;">Supervisión global de cuentas, negocios y seguridad de la plataforma</p>
            </div>
            <span class="badge badge-admin" style="font-size:0.85rem; padding:0.4rem 0.8rem; background:#fee2e2; color:#b91c1c;">
                🛡️ SUPERADMIN
            </span>
        </div>

        <!-- Tarjetas de Métricas Globales -->
        <div class="grid grid-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <span class="text-muted" style="font-size:0.85rem;">Usuarios Totales</span>
                    <h2 style="font-size:2rem; margin:0.3rem 0; color:var(--primary);"><?= $totalUsuarios ?></h2>
                    <a href="<?= url('/administrador/usuarios.php') ?>" style="font-size:0.85rem;">Gestionar cuentas &rarr;</a>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <span class="text-muted" style="font-size:0.85rem;">Negocios Registrados</span>
                    <h2 style="font-size:2rem; margin:0.3rem 0; color:var(--accent);"><?= $totalNegocios ?></h2>
                    <a href="<?= url('/administrador/negocios.php') ?>" style="font-size:0.85rem;">Ver establecimientos &rarr;</a>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <span class="text-muted" style="font-size:0.85rem;">Citas en Plataforma</span>
                    <h2 style="font-size:2rem; margin:0.3rem 0; color:var(--success);"><?= $totalReservas ?></h2>
                    <small class="text-muted">Historial completo</small>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <span class="text-muted" style="font-size:0.85rem;">Eventos Auditados</span>
                    <h2 style="font-size:2rem; margin:0.3rem 0; color:var(--text-secondary);"><?= $totalAuditoria ?></h2>
                    <a href="<?= url('/administrador/auditoria.php') ?>" style="font-size:0.85rem;">Ver log de seguridad &rarr;</a>
                </div>
            </div>
        </div>

        <!-- Registro Reciente de Auditoría -->
        <div class="card">
            <div class="card-header d-flex justify-between align-center">
                <h3>Eventos Recientes del Sistema</h3>
                <a href="<?= url('/administrador/auditoria.php') ?>" class="btn btn-outline btn-sm">Ver Auditoría Completa</a>
            </div>
            <div class="card-body" style="padding:0;">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Fecha y Hora</th>
                                <th>Usuario</th>
                                <th>Acción</th>
                                <th>Entidad</th>
                                <th>Detalles</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logsRecientes as $log): ?>
                                <tr>
                                    <td><small class="text-muted"><?= $log['creado_en'] ?></small></td>
                                    <td>
                                        <?= e($log['usuario_nombre'] ?: 'Sistema/Invitado') ?><br>
                                        <small class="text-muted"><?= e($log['usuario_email'] ?: '-') ?></small>
                                    </td>
                                    <td><strong><?= e($log['accion']) ?></strong></td>
                                    <td><span class="badge" style="background:#f1f5f9; color:var(--text-secondary);"><?= e($log['entidad']) ?></span></td>
                                    <td><?= e($log['detalles'] ?: '-') ?></td>
                                    <td><code><?= e($log['ip']) ?></code></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require APP_ROOT . '/app/views/layouts/footer.php'; ?>
