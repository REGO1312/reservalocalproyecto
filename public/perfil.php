<?php
/**
 * Reserva Local - Perfil de Usuario
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_login();

$userId = auth_id();
$user = Usuario::findById($userId);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Token de seguridad inválido.';
    } else {
        $action = $_POST['action'] ?? 'info';

        if ($action === 'info') {
            $nombre = trim($_POST['nombre'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');

            if (empty($nombre)) {
                $error = 'El nombre no puede estar vacío.';
            } else {
                Usuario::update($userId, ['nombre' => $nombre, 'telefono' => $telefono]);
                $_SESSION['user']['nombre'] = $nombre;
                $_SESSION['user']['telefono'] = $telefono;
                $user['nombre'] = $nombre;
                $user['telefono'] = $telefono;
                $success = 'Datos personales actualizados correctamente.';
            }
        } elseif ($action === 'password') {
            $currentPass = $_POST['current_password'] ?? '';
            $newPass = $_POST['new_password'] ?? '';
            $confirmPass = $_POST['confirm_password'] ?? '';

            if (!password_verify($currentPass, $user['password_hash'])) {
                $error = 'Tu contraseña actual no es correcta.';
            } elseif (strlen($newPass) < 8) {
                $error = 'La nueva contraseña debe tener al menos 8 caracteres.';
            } elseif ($newPass !== $confirmPass) {
                $error = 'Las nuevas contraseñas no coinciden.';
            } else {
                Usuario::update($userId, ['password' => $newPass]);
                $success = 'Contraseña actualizada con éxito.';
            }
        }
    }
}

$pageTitle = 'Mi Perfil';
require APP_ROOT . '/app/views/layouts/header.php';
?>

<div class="container container-sm py-4">
    <h1 class="mb-4">Mi Perfil</h1>

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

    <!-- Datos Generales -->
    <div class="card mb-4">
        <div class="card-header">
            <h3>Información Personal</h3>
        </div>
        <div class="card-body">
            <form action="<?= url('/perfil.php') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="info">

                <div class="form-group">
                    <label for="email" class="form-label">Correo Electrónico</label>
                    <input type="email" id="email" class="form-control" value="<?= e($user['email']) ?>" disabled>
                    <div class="form-text">El correo está vinculado a tu cuenta y no puede modificarse directamente.</div>
                </div>

                <div class="form-group">
                    <label for="nombre" class="form-label">Nombre Completo</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" value="<?= e($user['nombre']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="telefono" class="form-label">Teléfono Móvil</label>
                    <input type="tel" id="telefono" name="telefono" class="form-control" value="<?= e($user['telefono']) ?>" placeholder="ej. 5512345678">
                </div>

                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </form>
        </div>
    </div>

    <!-- Cambiar Contraseña -->
    <div class="card">
        <div class="card-header">
            <h3>Cambiar Contraseña</h3>
        </div>
        <div class="card-body">
            <form action="<?= url('/perfil.php') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="password">

                <div class="form-group">
                    <label for="current_password" class="form-label">Contraseña Actual</label>
                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="new_password" class="form-label">Nueva Contraseña</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required placeholder="Mínimo 8 caracteres">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirmar Nueva Contraseña</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required placeholder="Repite la contraseña">
                    </div>
                </div>

                <button type="submit" class="btn btn-outline">Actualizar Contraseña</button>
            </form>
        </div>
    </div>
</div>

<?php require APP_ROOT . '/app/views/layouts/footer.php'; ?>
