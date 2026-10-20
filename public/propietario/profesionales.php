<?php
/**
 * Panel Propietario - Gestión de Especialistas y Profesionales
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

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Token de seguridad inválido.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'guardar') {
            $id = (int)($_POST['id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $especialidad = trim($_POST['especialidad'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $usuarioEmail = strtolower(trim($_POST['usuario_email'] ?? ''));
            $estado = $_POST['estado'] ?? 'activo';
            $serviciosSeleccionados = $_POST['servicios'] ?? [];

            if (empty($nombre)) {
                $error = 'El nombre del especialista es obligatorio.';
            } else {
                // Si proporcionó un email de usuario, buscarlo para vincular
                $usuarioId = null;
                if (!empty($usuarioEmail)) {
                    $u = Usuario::findByEmail($usuarioEmail);
                    if ($u) {
                        $usuarioId = $u['id'];
                    } else {
                        $error = "No se encontró ningún usuario registrado con el correo: $usuarioEmail. Regístralo primero para vincular su agenda.";
                    }
                }

                if (empty($error)) {
                    if ($id > 0) {
                        $prof = Profesional::findById($id);
                        if ($prof && $prof['negocio_id'] == $activeNegocioId) {
                            Profesional::update($id, [
                                'nombre' => $nombre,
                                'especialidad' => $especialidad,
                                'telefono' => $telefono,
                                'usuario_id' => $usuarioId,
                                'estado' => $estado
                            ], $serviciosSeleccionados);
                            $success = 'Especialista actualizado con éxito.';
                        }
                    } else {
                        Profesional::create([
                            'negocio_id' => $activeNegocioId,
                            'nombre' => $nombre,
                            'especialidad' => $especialidad,
                            'telefono' => $telefono,
                            'usuario_id' => $usuarioId,
                            'estado' => $estado
                        ], $serviciosSeleccionados);
                        $success = 'Especialista registrado y servicios asignados.';
                    }
                }
            }
        } elseif ($action === 'eliminar') {
            $id = (int)($_POST['id'] ?? 0);
            $prof = Profesional::findById($id);
            if ($prof && $prof['negocio_id'] == $activeNegocioId) {
                Profesional::delete($id);
                $success = 'Especialista eliminado o desactivado correctamente.';
            }
        }
    }
}

$profesionales = Profesional::findByNegocio($activeNegocioId, false);
$todosServicios = Servicio::findByNegocio($activeNegocioId, true);

$editItem = null;
if (isset($_GET['editar'])) {
    $editId = (int)$_GET['editar'];
    $p = Profesional::findById($editId);
    if ($p && $p['negocio_id'] == $activeNegocioId) {
        $editItem = $p;
        // Obtener email del usuario vinculado si existe
        if (!empty($editItem['usuario_id'])) {
            $u = Usuario::findById((int)$editItem['usuario_id']);
            $editItem['usuario_email'] = $u['email'] ?? '';
        }
    }
}

$pageTitle = 'Personal y Especialistas - ' . $negocioActual['nombre'];
$panelType = 'propietario';
require APP_ROOT . '/app/views/layouts/header.php';
?>

<div class="panel-layout">
    <?php require APP_ROOT . '/app/views/layouts/sidebar.php'; ?>

    <main class="panel-main">
        <div class="d-flex justify-between align-center mb-4" style="flex-wrap:wrap; gap:1rem;">
            <div>
                <h1>Personal y Especialistas</h1>
                <p class="text-muted" style="margin-bottom:0;">Gestiona tus barberos, estilistas y vincula sus cuentas de agenda</p>
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
            <!-- Formulario de Especialista -->
            <div class="card">
                <div class="card-header">
                    <h3><?= $editItem ? 'Editar Especialista' : 'Nuevo Especialista' ?></h3>
                </div>
                <div class="card-body">
                    <form action="<?= url('/propietario/profesionales.php') ?>" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="guardar">
                        <input type="hidden" name="id" value="<?= $editItem ? $editItem['id'] : 0 ?>">

                        <div class="form-group">
                            <label for="nombre" class="form-label">Nombre del Especialista</label>
                            <input type="text" id="nombre" name="nombre" class="form-control" value="<?= e($editItem['nombre'] ?? '') ?>" required placeholder="ej. Carlos Méndez">
                        </div>

                        <div class="form-group">
                            <label for="especialidad" class="form-label">Especialidad</label>
                            <input type="text" id="especialidad" name="especialidad" class="form-control" value="<?= e($editItem['especialidad'] ?? '') ?>" placeholder="ej. Master Barber, Colorista, Degradados...">
                        </div>

                        <div class="form-group">
                            <label for="telefono" class="form-label">Teléfono de Contacto</label>
                            <input type="tel" id="telefono" name="telefono" class="form-control" value="<?= e($editItem['telefono'] ?? '') ?>" placeholder="ej. 5544556677">
                        </div>

                        <div class="form-group">
                            <label for="usuario_email" class="form-label">Correo de Cuenta Vinculada (Opcional)</label>
                            <input type="email" id="usuario_email" name="usuario_email" class="form-control" value="<?= e($editItem['usuario_email'] ?? '') ?>" placeholder="barbero@reservalocal.test">
                            <div class="form-text">Si este especialista tiene una cuenta de usuario, escribe su email aquí para que pueda ver su agenda personal al iniciar sesión.</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Servicios que presta</label>
                            <div style="max-height: 180px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 0.75rem;">
                                <?php if (empty($todosServicios)): ?>
                                    <small class="text-muted">Primero debes crear servicios en el catálogo.</small>
                                <?php else: ?>
                                    <?php 
                                    $asignados = $editItem['servicios'] ?? [];
                                    foreach ($todosServicios as $serv): 
                                        $checked = in_array($serv['id'], $asignados);
                                    ?>
                                        <label style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem; font-size: 0.9rem; cursor: pointer;">
                                            <input type="checkbox" name="servicios[]" value="<?= $serv['id'] ?>" <?= $checked ? 'checked' : '' ?>>
                                            <span><?= e($serv['nombre']) ?> (<?= format_money($serv['precio']) ?>)</span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="estado" class="form-label">Estado</label>
                            <select id="estado" name="estado" class="form-control">
                                <option value="activo" <?= ($editItem['estado'] ?? 'activo') === 'activo' ? 'selected' : '' ?>>Activo</option>
                                <option value="inactivo" <?= ($editItem['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary" style="flex:1;">
                                <?= $editItem ? 'Actualizar' : 'Guardar Especialista' ?>
                            </button>
                            <?php if ($editItem): ?>
                                <a href="<?= url('/propietario/profesionales.php') ?>" class="btn btn-outline">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla de Especialistas -->
            <div class="card">
                <div class="card-header">
                    <h3>Equipo Registrado (<?= count($profesionales) ?>)</h3>
                </div>
                <div class="card-body" style="padding:0;">
                    <?php if (empty($profesionales)): ?>
                        <div class="text-center py-5 text-muted">Aún no has agregado personal a este negocio.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Especialista</th>
                                        <th>Especialidad</th>
                                        <th>Cuenta Vinculada</th>
                                        <th>Servicios Asignados</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($profesionales as $p): ?>
                                        <tr>
                                            <td>
                                                <strong><?= e($p['nombre']) ?></strong><br>
                                                <small class="text-muted">Tel: <?= e($p['telefono'] ?: '-') ?></small>
                                            </td>
                                            <td><?= e($p['especialidad'] ?: 'General') ?></td>
                                            <td>
                                                <?php if (!empty($p['usuario_email'])): ?>
                                                    <code style="font-size:0.8rem;"><?= e($p['usuario_email']) ?></code>
                                                <?php else: ?>
                                                    <span class="text-muted" style="font-size:0.8rem;">Sin vincular</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge" style="background:var(--bg-subtle); color:var(--text-primary);">
                                                    <?= count($p['servicios']) ?> servicios
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= e($p['estado']) ?>"><?= strtoupper(e($p['estado'])) ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="?editar=<?= $p['id'] ?>" class="btn btn-outline btn-sm">Editar</a>
                                                    <form action="<?= url('/propietario/profesionales.php') ?>" method="POST" onsubmit="return confirm('¿Desactivar o eliminar este especialista?');" style="margin:0;">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="eliminar">
                                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
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
