<?php
/**
 * Reserva Local - Solicitar Recuperación de Contraseña
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

$mensaje = '';
$error = '';
$demoLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (!verify_csrf()) {
        $error = 'Token de seguridad inválido.';
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ingresa un correo electrónico válido.';
    } else {
        $user = Usuario::findByEmail($email);
        if ($user) {
            $rawToken = Usuario::createPasswordReset((int)$user['id']);
            $resetUrl = url('/restablecer.php?token=' . urlencode($rawToken));
            
            // Simulación visible en entorno de desarrollo / demo para pruebas inmediatas
            $demoLink = $resetUrl;

            // Encolar notificación en BD
            Notificacion::create([
                'reserva_id' => 0, // General
                'usuario_id' => $user['id'],
                'tipo' => 'cambio_estado',
                'destinatario' => $user['email'],
                'asunto' => 'Recuperación de contraseña - Reserva Local',
                'mensaje' => "Hola {$user['nombre']}, haz clic en el siguiente enlace para restablecer tu contraseña: $resetUrl (expira en 60 minutos).",
                'programada_para' => date('Y-m-d H:i:s')
            ]);
        }
        // Mensaje genérico para no filtrar existencia de correos
        $mensaje = 'Si el correo electrónico está registrado, recibirás un enlace de recuperación con vigencia de 60 minutos.';
    }
}

$pageTitle = 'Recuperar Contraseña';
require APP_ROOT . '/app/views/layouts/header.php';
?>

<div class="container container-sm py-5">
    <div class="card shadow-md">
        <div class="card-header text-center" style="display:block;">
            <h2>Recuperar Contraseña</h2>
            <p class="text-muted" style="margin-bottom:0;">Te enviaremos un enlace de un solo uso para restablecerla</p>
        </div>

        <div class="card-body">
            <?php if (!empty($mensaje)): ?>
                <div class="alert alert-success">
                    <span class="alert-icon">✓</span>
                    <div class="alert-body"><?= e($mensaje) ?></div>
                </div>

                <?php if (!empty($demoLink)): ?>
                    <div class="card mt-3" style="background:#eff6ff; border-color:#93c5fd; padding:1rem;">
                        <p style="margin:0; font-size:0.9rem;">
                            <strong>🔗 Enlace de recuperación generado (Modo Demo Local):</strong><br>
                            <a href="<?= e($demoLink) ?>" class="font-bold" style="word-break:break-all;"><?= e($demoLink) ?></a>
                        </p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <span class="alert-icon">⚠️</span>
                    <div class="alert-body"><?= e($error) ?></div>
                </div>
            <?php endif; ?>

            <form action="<?= url('/recuperar.php') ?>" method="POST" class="mt-3">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="email" class="form-label">Correo Electrónico de tu Cuenta</label>
                    <input type="email" id="email" name="email" class="form-control" required placeholder="ejemplo@correo.com">
                </div>

                <button type="submit" class="btn btn-primary btn-block mt-4">Enviar Enlace</button>
            </form>
        </div>

        <div class="card-footer text-center">
            <a href="<?= url('/login.php') ?>">&larr; Volver al inicio de sesión</a>
        </div>
    </div>
</div>

<?php require APP_ROOT . '/app/views/layouts/footer.php'; ?>
