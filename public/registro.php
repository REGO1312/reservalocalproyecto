<?php
/**
 * Reserva Local - Registro de Nuevos Usuarios
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

if (is_authenticated()) {
    redirect('/');
}

$error = '';
$nombre = '';
$email = '';
$telefono = '';
$tipo = $_GET['tipo'] ?? 'cliente';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $telefono = trim($_POST['telefono'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $rolElegido = ($_POST['rol_registro'] ?? 'cliente') === 'propietario' ? 'propietario' : 'cliente';

    if (!verify_csrf()) {
        $error = 'Token de seguridad inválido. Intenta de nuevo.';
    } elseif (empty($nombre) || empty($email) || empty($password)) {
        $error = 'Nombre, correo electrónico y contraseña son campos obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El formato del correo electrónico no es válido.';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Las contraseñas ingresadas no coinciden.';
    } elseif (Usuario::findByEmail($email)) {
        $error = 'Ya existe una cuenta registrada con este correo electrónico.';
    } else {
        try {
            $roles = ['cliente'];
            if ($rolElegido === 'propietario') {
                $roles[] = 'propietario';
            }

            $userId = Usuario::create([
                'nombre' => $nombre,
                'email' => $email,
                'telefono' => $telefono,
                'password' => $password
            ], $roles);

            // Auto-login seguro
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => $userId,
                'nombre' => $nombre,
                'email' => $email,
                'telefono' => $telefono,
                'roles' => $roles
            ];

            set_flash('success', '¡Cuenta creada con éxito! Bienvenido a Reserva Local.');

            if ($rolElegido === 'propietario') {
                redirect('/propietario/');
            } else {
                redirect('/explorar.php');
            }
        } catch (Exception $e) {
            $error = 'Error al registrar la cuenta: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Crear Cuenta';
require APP_ROOT . '/app/views/layouts/header.php';
?>

<div class="container container-sm py-5">
    <div class="card shadow-md">
        <div class="card-header text-center" style="display:block;">
            <h2>Crear Cuenta</h2>
            <p class="text-muted" style="margin-bottom:0;">Únete a Reserva Local y agenda citas al instante</p>
        </div>

        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <span class="alert-icon">⚠️</span>
                    <div class="alert-body"><?= e($error) ?></div>
                </div>
            <?php endif; ?>

            <form action="<?= url('/registro.php') ?>" method="POST">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label class="form-label">¿Qué tipo de cuenta deseas?</label>
                    <div style="display: flex; gap: 1rem;">
                        <label style="flex:1; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: var(--radius-md); cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                            <input type="radio" name="rol_registro" value="cliente" <?= $tipo !== 'propietario' ? 'checked' : '' ?>>
                            <div>
                                <strong>Soy Cliente</strong>
                                <div class="form-text">Quiero descubrir y reservar citas</div>
                            </div>
                        </label>
                        <label style="flex:1; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: var(--radius-md); cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                            <input type="radio" name="rol_registro" value="propietario" <?= $tipo === 'propietario' ? 'checked' : '' ?>>
                            <div>
                                <strong>Soy Propietario</strong>
                                <div class="form-text">Tengo una barbería o salón</div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="nombre" class="form-label">Nombre Completo</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" value="<?= e($nombre) ?>" required placeholder="Tu nombre y apellido">
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Correo Electrónico</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?= e($email) ?>" required placeholder="tu@correo.com">
                </div>

                <div class="form-group">
                    <label for="telefono" class="form-label">Teléfono Móvil (opcional)</label>
                    <input type="tel" id="telefono" name="telefono" class="form-control" value="<?= e($telefono) ?>" placeholder="ej. 5512345678">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" id="password" name="password" class="form-control" required placeholder="Mínimo 8 caracteres">
                    </div>
                    <div class="form-group">
                        <label for="password_confirm" class="form-label">Confirmar Contraseña</label>
                        <input type="password" id="password_confirm" name="password_confirm" class="form-control" required placeholder="Repite la contraseña">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block mt-4">Registrarme</button>
            </form>
        </div>

        <div class="card-footer text-center">
            <p style="margin: 0;">¿Ya tienes una cuenta? <a href="<?= url('/login.php') ?>" class="font-bold">Inicia sesión</a></p>
        </div>
    </div>
</div>

<?php require APP_ROOT . '/app/views/layouts/footer.php'; ?>
