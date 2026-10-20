<?php
/**
 * Panel Propietario - Crear Nuevo Negocio
 */

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_role('propietario');

$ownerId = auth_id();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Token de seguridad inválido.';
    } else {
        $nombre = trim($_POST['nombre'] ?? '');
        $categoria = $_POST['categoria'] ?? 'barberia';
        $descripcion = trim($_POST['descripcion'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $zonaMunicipio = trim($_POST['zona_municipio'] ?? '');
        $latitud = (float)($_POST['latitud'] ?? 13.6989);
        $longitud = (float)($_POST['longitud'] ?? -89.2415);
        $telefono = trim($_POST['telefono'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($nombre) || empty($direccion) || empty($zonaMunicipio)) {
            $error = 'Nombre, dirección y zona/municipio son obligatorios.';
        } else {
            $negocioId = Negocio::create([
                'nombre' => $nombre,
                'categoria' => $categoria,
                'descripcion' => $descripcion,
                'direccion' => $direccion,
                'zona_municipio' => $zonaMunicipio,
                'latitud' => $latitud,
                'longitud' => $longitud,
                'telefono' => $telefono,
                'email' => $email,
                'imagen_url' => ($categoria === 'barberia' ? 'assets/img/barberia-caballero.svg' : 'assets/img/salon-belladonna.svg'),
                'estado' => 'activo'
            ], $ownerId);

            $_SESSION['current_negocio_id'] = $negocioId;
            set_flash('success', "¡Negocio '$nombre' registrado exitosamente!");
            redirect('/propietario/');
        }
    }
}

$pageTitle = 'Registrar Nuevo Negocio';
$panelType = 'propietario';
require APP_ROOT . '/app/views/layouts/header.php';
?>

<div class="container container-sm py-5">
    <div class="card shadow-md">
        <div class="card-header text-center" style="display:block;">
            <h2>Registrar Establecimiento</h2>
            <p class="text-muted" style="margin-bottom:0;">Da de alta tu barbería o salón para comenzar a recibir reservas</p>
        </div>

        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span class="alert-icon">⚠️</span>
                    <div class="alert-body"><?= e($error) ?></div>
                </div>
            <?php endif; ?>

            <form action="<?= url('/propietario/crear_negocio.php') ?>" method="POST">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="nombre" class="form-label">Nombre del Negocio</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" required placeholder="ej. Barbería El Imperio">
                </div>

                <div class="form-group">
                    <label for="categoria" class="form-label">Categoría</label>
                    <select id="categoria" name="categoria" class="form-control">
                        <option value="barberia">💈 Barbería</option>
                        <option value="salon">💇‍♀️ Salón de Belleza / Estética</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="3" class="form-control" placeholder="Describe los servicios, ambiente e instalaciones..."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="direccion" class="form-label">Dirección (Calle y Número)</label>
                        <input type="text" id="direccion" name="direccion" class="form-control" required placeholder="ej. Av. Juárez 123">
                    </div>
                    <div class="form-group">
                        <label for="zona_municipio" class="form-label">Colonia / Zona / Municipio</label>
                        <input type="text" id="zona_municipio" name="zona_municipio" class="form-control" required placeholder="ej. Escalón, San Benito, Santa Tecla">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="telefono" class="form-label">Teléfono de Contacto</label>
                        <input type="tel" id="telefono" name="telefono" class="form-control" placeholder="7850-1122">
                    </div>
                    <div class="form-group">
                        <label for="email" class="form-label">Correo de Contacto</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="contacto@negocio.com">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="latitud" class="form-label">Latitud (GPS El Salvador)</label>
                        <input type="number" step="0.0000001" id="latitud" name="latitud" class="form-control" value="13.6989000">
                    </div>
                    <div class="form-group">
                        <label for="longitud" class="form-label">Longitud (GPS El Salvador)</label>
                        <input type="number" step="0.0000001" id="longitud" name="longitud" class="form-control" value="-89.2415000">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block mt-4">Registrar Negocio</button>
            </form>
        </div>
    </div>
</div>

<?php require APP_ROOT . '/app/views/layouts/footer.php'; ?>
