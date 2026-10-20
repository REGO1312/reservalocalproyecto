<?php
/**
 * Reserva Local - Restablecer Contraseña con Token
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

$rawToken = trim($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$success = false;

if (empty($rawToken)) {
    redirect('/login.php');
}

$resetInfo = Usuario::verifyPasswordResetToken($rawToken);
if (!$resetInfo) {
    $error = 'El enlace de recuperación es inválido, ya ha sido utilizado o ha expirado (vigencia 60 min).';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $resetInfo) {
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (!verify_csrf()) {
        $error = 'Token de seguridad inválido. Intenta de nuevo.';
    } elseif (strlen($password) < 8) {
        $error = 'La nueva contraseña debe tener al menos 8 caracteres.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $ok = Usuario::completePasswordReset($rawToken, $password);
        if ($ok) {
            $success = true;
            set_flash('success', 'Tu contraseña ha sido restablecida con éxito. Ya puedes iniciar sesión.');
            redirect('/login.php');
        } else {
            $error = 'No se pudo actualizar la contraseña. Por favor solicita un nuevo enlace.';
        }
    }
}

$pageTitle = 'Nueva Contraseña';
require APP_ROOT . '/app/views/layouts/header.php';
?>

<div class="container container-sm py-5">
    <div class="card shadow-md">
        <div class="card-header text-center" style="display:block;">
            <h2>Restablecer Contraseña</h2>
            <p class="text-muted" style="margin-bottom:0;">Ingresa tu nueva clave de acceso</p>
        </div>

        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <span class="alert-icon">⚠️</span>
                    <div class="alert-body"><?= e($error) ?></div>
                </div>
                <div class="text-center mt-3">
                    <a href="<?= url('/recuperar.php') ?>" class="btn btn-outline">Solicitar nuevo enlace</a>
                </div>
            <?php elseif ($resetInfo): ?>
                <p class="mb-4">Hola <strong><?= e($resetInfo['nombre']) ?></strong>, escribe tu nueva contraseña a continuación:</p>

                <form action="<?= url('/restablecer.php') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= e($rawToken) ?>">

                    <div class="form-group">
                        <label for="password" class="form-label">Nueva Contraseña</label>
                        <input type="password" id="password" name="password" class="form-control" required placeholder="Mínimo 8 caracteres">
                    </div>

                    <div class="form-group">
                        <label for="password_confirm" class="form-label">Confirmar Nueva Contraseña</label>
                        <input type="password" id="password_confirm" name="password_confirm" class="form-control" required placeholder="Repite la nueva contraseña">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block mt-4">Guardar Contraseña</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require APP_ROOT . '/app/views/layouts/footer.php'; ?>
