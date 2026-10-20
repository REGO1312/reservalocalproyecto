<?php
/**
 * Panel Propietario - Excepciones y Cierres Programados
 */

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_role('propietario');

$ownerId = auth_id();
$negocios = Negocio::findByOwner($ownerId);
if (empty($negocios)) redirect('/propietario/');

$activeNegocioId = (int)($_SESSION['current_negocio_id'] ?? $negocios[0]['id']);
$negocioActual = Negocio::findById($activeNegocioId);

$profesionales = Profesional::findByNegocio($activeNegocioId, true);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Token de seguridad inválido.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'crear') {
            $fecha = trim($_POST['fecha'] ?? '');
            $tipo = $_POST['tipo'] ?? 'cierre_total';
            $profId = !empty($_POST['profesional_id']) ? (int)$_POST['profesional_id'] : null;
            $motivo = trim($_POST['motivo'] ?? '');
            $horaInicio = !empty($_POST['hora_inicio']) ? $_POST['hora_inicio'] : null;
            $horaFin = !empty($_POST['hora_fin']) ? $_POST['hora_fin'] : null;

            if (empty($fecha)) {
                $error = 'La fecha de la excepción es obligatoria.';
            } else {
                Horario::addExcepcion([
                    'negocio_id' => $activeNegocioId,
                    'profesional_id' => $profId,
                    'fecha' => $fecha,
                    'tipo' => $tipo,
                    'hora_inicio' => $horaInicio,
                    'hora_fin' => $horaFin,
                    'motivo' => $motivo
                ]);
                $success = 'Excepción de horario agregada con éxito.';
            }
        } elseif ($action === 'eliminar') {
            $id = (int)($_POST['id'] ?? 0);
            Horario::deleteExcepcion($id, $activeNegocioId);
            $success = 'Excepción eliminada correctamente.';
        }
    }
}

$excepciones = Horario::getExcepciones($activeNegocioId);

$pageTitle = 'Cierres y Excepciones - ' . $negocioActual['nombre'];
$panelType = 'propietario';
require APP_ROOT . '/app/views/layouts/header.php';
?>

<div class="panel-layout">
    <?php require APP_ROOT . '/app/views/layouts/sidebar.php'; ?>

    <main class="panel-main">
        <div class="d-flex justify-between align-center mb-4" style="flex-wrap:wrap; gap:1rem;">
            <div>
                <h1>Cierres y Excepciones por Fecha</h1>
                <p class="text-muted" style="margin-bottom:0;">Registra días festivos, vacaciones de especialistas o mantenimientos para bloquear reservas</p>
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
            <!-- Formulario de Excepción -->
            <div class="card">
                <div class="card-header">
                    <h3>Registrar Excepción</h3>
                </div>
                <div class="card-body">
                    <form action="<?= url('/propietario/excepciones.php') ?>" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="crear">

                        <div class="form-group">
                            <label for="fecha" class="form-label">Fecha Afectada</label>
                            <input type="date" id="fecha" name="fecha" class="form-control" min="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="profesional_id" class="form-label">Aplica a:</label>
                            <select id="profesional_id" name="profesional_id" class="form-control">
                                <option value="">Todo el establecimiento (Cierre General)</option>
                                <?php foreach ($profesionales as $p): ?>
                                    <option value="<?= $p['id'] ?>">Solo especialista: <?= e($p['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="tipo" class="form-label">Tipo de Excepción</label>
                            <select id="tipo" name="tipo" class="form-control">
                                <option value="cierre_total">Cierre Total (Todo el día)</option>
                                <option value="horario_especial">Horario Especial / Parcial</option>
                            </select>
                        </div>

                        <div class="form-row" id="horasRow" style="display:none;">
                            <div class="form-group">
                                <label for="hora_inicio" class="form-label">Desde</label>
                                <input type="time" id="hora_inicio" name="hora_inicio" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="hora_fin" class="form-label">Hasta</label>
                                <input type="time" id="hora_fin" name="hora_fin" class="form-control">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="motivo" class="form-label">Motivo o Descripción</label>
                            <input type="text" id="motivo" name="motivo" class="form-control" placeholder="ej. Día feriado, Capacitación, Remodelación...">
                        </div>

                        <button type="submit" class="btn btn-primary btn-block mt-3">Guardar Excepción</button>
                    </form>
                </div>
            </div>

            <!-- Listado de Excepciones -->
            <div class="card">
                <div class="card-header">
                    <h3>Excepciones Registradas (<?= count($excepciones) ?>)</h3>
                </div>
                <div class="card-body" style="padding:0;">
                    <?php if (empty($excepciones)): ?>
                        <div class="text-center py-5 text-muted">No hay excepciones registradas en este momento.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Aplica a</th>
                                        <th>Tipo</th>
                                        <th>Motivo</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($excepciones as $ex): ?>
                                        <tr>
                                            <td><strong><?= format_date_es($ex['fecha']) ?></strong></td>
                                            <td>
                                                <?= $ex['profesional_nombre'] ? 'Especialista: ' . e($ex['profesional_nombre']) : '🏢 Todo el Negocio' ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-cancelada">
                                                    <?= $ex['tipo'] === 'cierre_total' ? 'Cierre Total' : 'Horario Especial' ?>
                                                </span>
                                            </td>
                                            <td><?= e($ex['motivo'] ?: '-') ?></td>
                                            <td>
                                                <form action="<?= url('/propietario/excepciones.php') ?>" method="POST" onsubmit="return confirm('¿Eliminar esta excepción?');" style="margin:0;">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="eliminar">
                                                    <input type="hidden" name="id" value="<?= $ex['id'] ?>">
                                                    <button type="submit" class="btn btn-outline btn-sm text-danger">Eliminar</button>
                                                </form>
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

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tipo = document.getElementById('tipo');
    const horasRow = document.getElementById('horasRow');
    if (tipo && horasRow) {
        tipo.addEventListener('change', () => {
            horasRow.style.display = (tipo.value === 'horario_especial') ? 'flex' : 'none';
        });
    }
});
</script>

<?php require APP_ROOT . '/app/views/layouts/footer.php'; ?>
