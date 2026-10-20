<?php
/**
 * Panel Administrador Global - Gestión de Negocios y Estados
 */

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_role('administrador');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Token de seguridad inválido.';
    } else {
        $negocioId = (int)($_POST['negocio_id'] ?? 0);
        $nuevoEstado = $_POST['estado'] ?? '';

        if ($negocioId && in_array($nuevoEstado, ['activo', 'inactivo', 'pendiente'], true)) {
            Negocio::updateEstado($negocioId, $nuevoEstado);
            $success = "El estado del negocio ID $negocioId ha sido actualizado a: $nuevoEstado";
        }
    }
}

$negocios = Negocio::getAllAdmin();

$pageTitle = 'Gestión de Negocios - Administrador';
$panelType = 'administrador';
require APP_ROOT . '/app/views/layouts/header.php';
?>

<div class="panel-layout">
    <?php require APP_ROOT . '/app/views/layouts/sidebar.php'; ?>

    <main class="panel-main">
        <div class="d-flex justify-between align-center mb-4">
            <div>
                <h1>Gestión de Negocios</h1>
                <p class="text-muted" style="margin-bottom:0;">Supervisión de establecimientos, aprobación y estados de servicio</p>
            </div>
            <span class="badge" style="background:var(--bg-surface); border:1px solid var(--border-color); font-size:0.85rem; padding:0.4rem 0.8rem;">
                Total: <?= count($negocios) ?> establecimientos
            </span>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <span class="alert-icon">✓</span>
                <div class="alert-body"><?= e($success) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <span class="alert-icon">⚠️</span>
                <div class="alert-body"><?= e($error) ?></div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body" style="padding:0;">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Establecimiento</th>
                                <th>Categoría</th>
                                <th>Ubicación / Zona</th>
                                <th>Servicios</th>
                                <th>Especialistas</th>
                                <th>Total Citas</th>
                                <th>Estado</th>
                                <th>Modificar Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($negocios as $n): ?>
                                <tr>
                                    <td><code>#<?= $n['id'] ?></code></td>
                                    <td>
                                        <strong><a href="<?= url('/negocio.php?id=' . $n['id']) ?>" target="_blank"><?= e($n['nombre']) ?> ↗</a></strong><br>
                                        <small class="text-muted">📞 <?= e($n['telefono'] ?: '-') ?> &bull; ✉️ <?= e($n['email'] ?: '-') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= e($n['categoria']) ?>">
                                            <?= $n['categoria'] === 'barberia' ? '💈 Barbería' : '💇‍♀️ Salón' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= e($n['zona_municipio']) ?><br>
                                        <small class="text-muted"><?= e($n['direccion']) ?></small>
                                    </td>
                                    <td><?= $n['total_servicios'] ?></td>
                                    <td><?= $n['total_profesionales'] ?></td>
                                    <td><strong><?= $n['total_reservas'] ?></strong></td>
                                    <td>
                                        <span class="badge badge-<?= e($n['estado']) ?>">
                                            <?= strtoupper(e($n['estado'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form action="<?= url('/administrador/negocios.php') ?>" method="POST" style="margin:0;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="negocio_id" value="<?= $n['id'] ?>">
                                            <select name="estado" class="form-control form-control-sm" style="width:auto;" onchange="this.form.submit()">
                                                <option value="activo" <?= $n['estado'] === 'activo' ? 'selected' : '' ?>>Activo</option>
                                                <option value="inactivo" <?= $n['estado'] === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                                                <option value="pendiente" <?= $n['estado'] === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                            </select>
                                        </form>
                                    </td>
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
