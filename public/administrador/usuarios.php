<?php
/**
 * Panel Administrador Global - Gestión de Cuentas de Usuario y Estados
 */

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_role('administrador');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Token de seguridad inválido.';
    } else {
        $usuarioId = (int)($_POST['usuario_id'] ?? 0);
        $nuevoEstado = $_POST['estado'] ?? '';

        if ($usuarioId && in_array($nuevoEstado, ['activo', 'inactivo', 'bloqueado'], true)) {
            // Evitar que el admin se bloquee a sí mismo
            if ($usuarioId === auth_id() && $nuevoEstado !== 'activo') {
                $error = 'No puedes desactivar ni bloquear tu propia cuenta de superadministrador.';
            } else {
                Usuario::update($usuarioId, ['estado' => $nuevoEstado]);
                audit_log(auth_id(), 'CAMBIO_ESTADO_USUARIO', 'usuarios', $usuarioId, "Nuevo estado de cuenta: $nuevoEstado");
                $success = "El estado del usuario ID $usuarioId ha sido actualizado a: $nuevoEstado";
            }
        }
    }
}

$usuarios = Usuario::getAll();

$pageTitle = 'Gestión de Usuarios - Administrador';
$panelType = 'administrador';
require APP_ROOT . '/app/views/layouts/header.php';
?>

<div class="panel-layout">
    <?php require APP_ROOT . '/app/views/layouts/sidebar.php'; ?>

    <main class="panel-main">
        <div class="d-flex justify-between align-center mb-4">
            <div>
                <h1>Gestión de Cuentas de Usuario</h1>
                <p class="text-muted" style="margin-bottom:0;">Supervisa clientes, propietarios, especialistas y su estado de acceso</p>
            </div>
            <span class="badge" style="background:var(--bg-surface); border:1px solid var(--border-color); font-size:0.85rem; padding:0.4rem 0.8rem;">
                Total: <?= count($usuarios) ?> usuarios
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
                                <th>Nombre Completo</th>
                                <th>Correo Electrónico</th>
                                <th>Teléfono</th>
                                <th>Roles Asignados</th>
                                <th>Fecha Registro</th>
                                <th>Estado</th>
                                <th>Modificar Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $u): ?>
                                <tr>
                                    <td><code>#<?= $u['id'] ?></code></td>
                                    <td><strong><?= e($u['nombre']) ?></strong></td>
                                    <td><?= e($u['email']) ?></td>
                                    <td><?= e($u['telefono'] ?: '-') ?></td>
                                    <td>
                                        <?php 
                                        $rolesArr = explode(', ', $u['roles_str'] ?? '');
                                        foreach ($rolesArr as $r): 
                                            if (empty($r)) continue;
                                        ?>
                                            <span class="badge" style="background:#eef2ff; color:#4338ca; font-size:0.7rem; margin-right:2px;">
                                                <?= e($r) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </td>
                                    <td><small class="text-muted"><?= substr($u['creado_en'], 0, 10) ?></small></td>
                                    <td>
                                        <span class="badge badge-<?= e($u['estado']) ?>">
                                            <?= strtoupper(e($u['estado'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form action="<?= url('/administrador/usuarios.php') ?>" method="POST" style="margin:0; display:flex; gap:0.4rem;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="usuario_id" value="<?= $u['id'] ?>">
                                            <select name="estado" class="form-control form-control-sm" style="width:auto;" onchange="this.form.submit()">
                                                <option value="activo" <?= $u['estado'] === 'activo' ? 'selected' : '' ?>>Activo</option>
                                                <option value="inactivo" <?= $u['estado'] === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                                                <option value="bloqueado" <?= $u['estado'] === 'bloqueado' ? 'selected' : '' ?>>Bloqueado</option>
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
