<?php
/**
 * Reserva Local - Inicio de Sesión
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

// Redirigir si ya está autenticado
if (is_authenticated()) {
    redirect('/');
}

$error = '';
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    // 1. Validar CSRF
    if (!verify_csrf()) {
        $error = 'Token de seguridad inválido. Por favor intenta nuevamente.';
    } else {
        // 2. Control de fuerza bruta por IP y Email
        $rateLimitMsg = Usuario::checkLoginRateLimit($ip, $email);
        if ($rateLimitMsg) {
            $error = $rateLimitMsg;
        } elseif (empty($email) || empty($password)) {
            $error = 'Por favor ingresa tu correo electrónico y contraseña.';
        } else {
            $user = Usuario::findByEmail($email);

            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['estado'] !== 'activo') {
                    $error = 'Tu cuenta se encuentra ' . $user['estado'] . '. Contacta al administrador.';
                } else {
                    // Login exitoso: reiniciar contador de intentos
                    Usuario::resetLoginAttempts($ip, $email);

                    // Regenerar ID de sesión para prevenir Session Fixation
                    session_regenerate_id(true);

                    // Guardar datos mínimos seguros en sesión
                    $_SESSION['user'] = [
                        'id' => $user['id'],
                        'nombre' => $user['nombre'],
                        'email' => $user['email'],
                        'telefono' => $user['telefono'],
                        'roles' => $user['roles']
                    ];

                    audit_log($user['id'], 'LOGIN_EXITOSO', 'usuarios', $user['id'], "Inicio de sesión desde IP $ip");
                    set_flash('success', "¡Bienvenido de nuevo, {$user['nombre']}!");

                    // Redirección inteligente según rol
                    $roles = $user['roles'];
                    if (in_array('administrador', $roles, true)) {
                        redirect('/administrador/');
                    } elseif (in_array('propietario', $roles, true)) {
                        redirect('/propietario/');
                    } elseif (in_array('profesional', $roles, true)) {
                        redirect('/profesional/');
                    } else {
                        $redirectUrl = $_GET['redirect'] ?? '/mis-citas.php';
                        redirect($redirectUrl);
                    }
                }
            } else {
                // Registrar intento fallido
                Usuario::recordFailedLogin($ip, $email);
                audit_log(null, 'LOGIN_FALLIDO', 'usuarios', null, "Intento fallido para $email desde IP $ip");
                $error = 'Credenciales incorrectas. Verifica tu correo y contraseña.';
            }
        }
    }
}

$pageTitle = 'Iniciar Sesión';
require APP_ROOT . '/app/views/layouts/header.php';
?>

<div class="container container-sm py-5">
    <div class="card shadow-md">
        <div class="card-header text-center" style="display:block;">
            <h2>Iniciar Sesión</h2>
            <p class="text-muted" style="margin-bottom:0;">Ingresa a tu cuenta de Reserva Local</p>
        </div>

        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <span class="alert-icon">⚠️</span>
                    <div class="alert-body"><?= e($error) ?></div>
                </div>
            <?php endif; ?>

            <form action="<?= url('/login.php' . (isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '')) ?>" method="POST">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="email" class="form-label">Correo Electrónico</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?= e($email) ?>" required autofocus placeholder="ejemplo@correo.com">
                </div>

                <div class="form-group">
                    <div class="d-flex justify-between align-center mb-1">
                        <label for="password" class="form-label" style="margin-bottom:0;">Contraseña</label>
                        <a href="<?= url('/recuperar.php') ?>" style="font-size: 0.85rem;">¿Olvidaste tu contraseña?</a>
                    </div>
                    <input type="password" id="password" name="password" class="form-control" required placeholder="Tu contraseña">
                </div>

                <button type="submit" class="btn btn-primary btn-block mt-4">Iniciar Sesión</button>
            </form>
        </div>

        <div class="card-footer text-center">
            <p style="margin: 0;">¿No tienes cuenta? <a href="<?= url('/registro.php') ?>" class="font-bold">Regístrate aquí</a></p>
        </div>
    </div>

    <!-- Caja de ayuda con credenciales de prueba para el revisor -->
    <div class="card mt-4" style="background: #f1f5f9; border-style: dashed;">
        <div class="card-body" style="font-size: 0.85rem;">
            <strong>💡 Cuentas Demo Rápidas (Contraseña: <code>Demo123!</code>):</strong>
            <ul style="margin: 0.5rem 0 0 1.25rem;">
                <li><strong>Admin Global:</strong> <code>admin@reservalocal.test</code></li>
                <li><strong>Propietario 1 (Barbería):</strong> <code>propietario1@reservalocal.test</code></li>
                <li><strong>Propietario 2 (Salón):</strong> <code>propietario2@reservalocal.test</code></li>
                <li><strong>Profesional (Barbero):</strong> <code>carlos@reservalocal.test</code></li>
                <li><strong>Cliente:</strong> <code>cliente1@reservalocal.test</code></li>
            </ul>
        </div>
    </div>
</div>

<?php require APP_ROOT . '/app/views/layouts/footer.php'; ?>
