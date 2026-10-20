<?php
/**
 * Panel Propietario - Gestión de Servicios
 */

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_role('propietario');

$ownerId = auth_id();
$negocios = Negocio::findByOwner($ownerId);
if (empty($negocios)) redirect('/propietario/');

$activeNegocioId = (int)($_SESSION['current_negocio_id'] ?? $negocios[0]['id']);
$negocioActual = Negocio::findById($activeNegocioId);

$error = '';
$success = '';

// Procesar formulario de alta o edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Token de seguridad inválido.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'guardar') {
            $id = (int)($_POST['id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $precio = (float)($_POST['precio'] ?? 0);
            $duracion = (int)($_POST['duracion_minutos'] ?? 0);
            $estado = $_POST['estado'] ?? 'activo';

            if (empty($nombre) || $precio <= 0 || $duracion <= 0) {
                $error = 'Nombre, precio (mayor a 0) y duración en minutos son obligatorios.';
            } else {
                if ($id > 0) {
                    // Editar
                    $serv = Servicio::findById($id);
                    if ($serv && $serv['negocio_id'] == $activeNegocioId) {
                        Servicio::update($id, [
                            'nombre' => $nombre,
                            'descripcion' => $descripcion,
                            'precio' => $precio,
                            'duracion_minutos' => $duracion,
                            'estado' => $estado
                        ]);
                        $success = 'Servicio actualizado correctamente.';
                    } else {
                        $error = 'Servicio no encontrado.';
                    }
                } else {
                    // Crear
                    Servicio::create([
                        'negocio_id' => $activeNegocioId,
                        'nombre' => $nombre,
                        'descripcion' => $descripcion,
                        'precio' => $precio,
                        'duracion_minutos' => $duracion,
                        'estado' => $estado
                    ]);
                    $success = 'Servicio agregado con éxito al catálogo.';
                }
            }
        } elseif ($action === 'eliminar') {
            $id = (int)($_POST['id'] ?? 0);
            $serv = Servicio::findById($id);
            if ($serv && $serv['negocio_id'] == $activeNegocioId) {
                Servicio::delete($id);
                $success = 'Servicio eliminado o desactivado correctamente.';
            }
        }
    }
}

$servicios = Servicio::findByNegocio($activeNegocioId, false);

$editItem = null;
if (isset($_GET['editar'])) {
    $editId = (int)$_GET['editar'];
    $s = Servicio::findById($editId);
    if ($s && $s['negocio_id'] == $activeNegocioId) {
        $editItem = $s;
    }
}

$pageTitle = 'Gestión de Servicios - ' . $negocioActual['nombre'];
$panelType = 'propietario';
require APP_ROOT . '/app/views/layouts/header.php';
?>

<div class="panel-layout">
    <?php require APP_ROOT . '/app/views/layouts/sidebar.php'; ?>

    <main class="panel-main">
        <div class="d-flex justify-between align-center mb-4" style="flex-wrap:wrap; gap:1rem;">
            <div>
                <h1>Gestión de Servicios</h1>
                <p class="text-muted" style="margin-bottom:0;">Configura los cortes, tratamientos y paquetes de tu negocio</p>
            </div>
            <div class="badge badge-confirmada" style="font-size:0.85rem; padding:0.4rem 0.8rem;">
                🏢 <?= e($negocioActual['nombre']) ?>
            </div>
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

        <div class="grid grid-2" style="grid-template-columns: 1fr 2fr; align-items: start;">
            <!-- Formulario de Alta / Edición -->
            <div class="card">
                <div class="card-header">
                    <h3><?= $editItem ? 'Editar Servicio' : 'Nuevo Servicio' ?></h3>
                </div>
                <div class="card-body">
                    <form action="<?= url('/propietario/servicios.php') ?>" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="guardar">
                        <input type="hidden" name="id" value="<?= $editItem ? $editItem['id'] : 0 ?>">

                        <div class="form-group">
                            <label for="nombre" class="form-label">Nombre del Servicio</label>
                            <input type="text" id="nombre" name="nombre" class="form-control" value="<?= e($editItem['nombre'] ?? '') ?>" required placeholder="ej. Corte Clásico + Barba">
                        </div>

                        <div class="form-group">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea id="descripcion" name="descripcion" rows="3" class="form-control" placeholder="Detalla qué incluye el servicio..."><?= e($editItem['descripcion'] ?? '') ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="precio" class="form-label">Precio ($ MXN)</label>
                                <input type="number" step="0.50" id="precio" name="precio" class="form-control" value="<?= e($editItem['precio'] ?? '250.00') ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="duracion_minutos" class="form-label">Duración (min)</label>
                                <input type="number" step="5" min="5" id="duracion_minutos" name="duracion_minutos" class="form-control" value="<?= e($editItem['duracion_minutos'] ?? '45') ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="estado" class="form-label">Estado</label>
                            <select id="estado" name="estado" class="form-control">
                                <option value="activo" <?= ($editItem['estado'] ?? 'activo') === 'activo' ? 'selected' : '' ?>>Activo (Visible)</option>
                                <option value="inactivo" <?= ($editItem['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo (Oculto)</option>
                            </select>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary" style="flex:1;">
                                <?= $editItem ? 'Actualizar' : 'Guardar Servicio' ?>
                            </button>
                            <?php if ($editItem): ?>
                                <a href="<?= url('/propietario/servicios.php') ?>" class="btn btn-outline">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla de Servicios Existentes -->
            <div class="card">
                <div class="card-header d-flex justify-between align-center">
                    <h3>Catálogo Actual (<?= count($servicios) ?>)</h3>
                </div>
                <div class="card-body" style="padding:0;">
                    <?php if (empty($servicios)): ?>
                        <div class="text-center py-5 text-muted">Aún no has agregado servicios a este negocio.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Servicio</th>
                                        <th>Precio</th>
                                        <th>Duración</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($servicios as $s): ?>
                                        <tr>
                                            <td>
                                                <strong><?= e($s['nombre']) ?></strong><br>
                                                <small class="text-muted"><?= e(substr($s['descripcion'] ?? '', 0, 60)) ?>...</small>
                                            </td>
                                            <td><strong><?= format_money($s['precio']) ?></strong></td>
                                            <td><?= format_duration((int)$s['duracion_minutos']) ?></td>
                                            <td>
                                                <span class="badge badge-<?= e($s['estado']) ?>"><?= strtoupper(e($s['estado'])) ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="?editar=<?= $s['id'] ?>" class="btn btn-outline btn-sm">Editar</a>
                                                    <form action="<?= url('/propietario/servicios.php') ?>" method="POST" onsubmit="return confirm('¿Eliminar o desactivar este servicio?');" style="margin:0;">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="eliminar">
                                                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                                        <button type="submit" class="btn btn-outline btn-sm text-danger">Borrar</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require APP_ROOT . '/app/views/layouts/footer.php'; ?>
