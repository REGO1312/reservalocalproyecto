<?php
/**
 * Panel Propietario - Horarios del Negocio y de Profesionales (con descansos)
 */

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_role('propietario');

$ownerId = auth_id();
$negocios = Negocio::findByOwner($ownerId);
if (empty($negocios)) redirect('/propietario/');

$activeNegocioId = (int)($_SESSION['current_negocio_id'] ?? $negocios[0]['id']);
$negocioActual = Negocio::findById($activeNegocioId);

$profesionales = Profesional::findByNegocio($activeNegocioId, true);
$selectedProfId = !empty($_GET['profesional_id']) ? (int)$_GET['profesional_id'] : ($profesionales[0]['id'] ?? 0);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Token de seguridad inválido.';
    } else {
        $tipo = $_POST['tipo_horario'] ?? '';

        if ($tipo === 'negocio') {
            $diasData = $_POST['dias'] ?? [];
            Horario::saveNegocioHorarios($activeNegocioId, $diasData);
            $success = 'Horarios generales del negocio actualizados correctamente.';
        } elseif ($tipo === 'profesional') {
            $pId = (int)($_POST['profesional_id'] ?? 0);
            $diasData = $_POST['dias_prof'] ?? [];
            if ($pId > 0) {
                Horario::saveProfesionalHorarios($pId, $diasData);
                $success = 'Horarios y descansos del especialista actualizados correctamente.';
            }
        }
    }
}

$horariosNegocio = Horario::getNegocioHorarios($activeNegocioId);
$horariosProf = $selectedProfId > 0 ? Horario::getProfesionalHorarios($selectedProfId) : [];

$pageTitle = 'Horarios y Descansos - ' . $negocioActual['nombre'];
$panelType = 'propietario';
require APP_ROOT . '/app/views/layouts/header.php';
?>

<div class="panel-layout">
    <?php require APP_ROOT . '/app/views/layouts/sidebar.php'; ?>

    <main class="panel-main">
        <div class="d-flex justify-between align-center mb-4" style="flex-wrap:wrap; gap:1rem;">
            <div>
                <h1>Horarios y Descansos</h1>
                <p class="text-muted" style="margin-bottom:0;">Configura los días laborables del negocio y las jornadas de tu equipo</p>
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

        <!-- Sección 1: Horarios de Atención del Negocio -->
        <div class="card mb-4">
            <div class="card-header">
                <h3>Horario General de Apertura del Establecimiento</h3>
            </div>
            <div class="card-body">
                <form action="<?= url('/propietario/horarios.php') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="tipo_horario" value="negocio">

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Día</th>
                                    <th>Laborable</th>
                                    <th>Hora Apertura</th>
                                    <th>Hora Cierre</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($d = 1; $d <= 7; $d++): 
                                    $hn = $horariosNegocio[$d] ?? ['es_laborable' => ($d <= 6 ? 1 : 0), 'hora_apertura' => '09:00:00', 'hora_cierre' => '19:00:00'];
                                ?>
                                    <tr>
                                        <td><strong><?= day_name_es($d) ?></strong></td>
                                        <td>
                                            <label style="display:flex; align-items:center; gap:0.4rem; cursor:pointer;">
                                                <input type="checkbox" name="dias[<?= $d ?>][es_laborable]" value="1" <?= !empty($hn['es_laborable']) ? 'checked' : '' ?>>
                                                <span>Abierto</span>
                                            </label>
                                        </td>
                                        <td>
                                            <input type="time" name="dias[<?= $d ?>][hora_apertura]" class="form-control form-control-sm" value="<?= substr($hn['hora_apertura'], 0, 5) ?>" style="max-width:140px;">
                                        </td>
                                        <td>
                                            <input type="time" name="dias[<?= $d ?>][hora_cierre]" class="form-control form-control-sm" value="<?= substr($hn['hora_cierre'], 0, 5) ?>" style="max-width:140px;">
                                        </td>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 text-right">
                        <button type="submit" class="btn btn-primary">Guardar Horario del Negocio</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sección 2: Horarios y Descansos del Especialista -->
        <?php if (!empty($profesionales)): ?>
            <div class="card">
                <div class="card-header d-flex justify-between align-center" style="flex-wrap:wrap; gap:1rem;">
                    <h3>Horarios y Descansos por Especialista</h3>
                    
                    <div style="display:flex; align-items:center; gap:0.5rem;">
                        <label for="selectProf" class="text-muted" style="font-size:0.9rem;">Especialista:</label>
                        <select id="selectProf" class="form-control" onchange="window.location.href='?profesional_id=' + this.value;" style="width:auto;">
                            <?php foreach ($profesionales as $pr): ?>
                                <option value="<?= $pr['id'] ?>" <?= $pr['id'] == $selectedProfId ? 'selected' : '' ?>>
                                    <?= e($pr['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="card-body">
                    <form action="<?= url('/propietario/horarios.php?profesional_id=' . $selectedProfId) ?>" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="tipo_horario" value="profesional">
                        <input type="hidden" name="profesional_id" value="<?= $selectedProfId ?>">

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Día</th>
                                        <th>Labora</th>
                                        <th>Inicio Jornada</th>
                                        <th>Fin Jornada</th>
                                        <th>Inicio Descanso / Comida</th>
                                        <th>Fin Descanso / Comida</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php for ($d = 1; $d <= 7; $d++): 
                                        $hp = $horariosProf[$d] ?? ['es_laborable' => ($d <= 5 ? 1 : 0), 'hora_inicio' => '09:00:00', 'hora_fin' => '18:00:00', 'descanso_inicio' => '14:00:00', 'descanso_fin' => '15:00:00'];
                                    ?>
                                        <tr>
                                            <td><strong><?= day_name_es($d) ?></strong></td>
                                            <td>
                                                <label style="display:flex; align-items:center; gap:0.4rem; cursor:pointer;">
                                                    <input type="checkbox" name="dias_prof[<?= $d ?>][es_laborable]" value="1" <?= !empty($hp['es_laborable']) ? 'checked' : '' ?>>
                                                    <span>Activo</span>
                                                </label>
                                            </td>
                                            <td>
                                                <input type="time" name="dias_prof[<?= $d ?>][hora_inicio]" class="form-control form-control-sm" value="<?= substr($hp['hora_inicio'] ?? '09:00:00', 0, 5) ?>" style="max-width:130px;">
                                            </td>
                                            <td>
                                                <input type="time" name="dias_prof[<?= $d ?>][hora_fin]" class="form-control form-control-sm" value="<?= substr($hp['hora_fin'] ?? '18:00:00', 0, 5) ?>" style="max-width:130px;">
                                            </td>
                                            <td>
                                                <input type="time" name="dias_prof[<?= $d ?>][descanso_inicio]" class="form-control form-control-sm" value="<?= !empty($hp['descanso_inicio']) ? substr($hp['descanso_inicio'], 0, 5) : '' ?>" style="max-width:130px;">
                                            </td>
                                            <td>
                                                <input type="time" name="dias_prof[<?= $d ?>][descanso_fin]" class="form-control form-control-sm" value="<?= !empty($hp['descanso_fin']) ? substr($hp['descanso_fin'], 0, 5) : '' ?>" style="max-width:130px;">
                                            </td>
                                        </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3 text-right">
                            <button type="submit" class="btn btn-primary">Guardar Horario del Especialista</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require APP_ROOT . '/app/views/layouts/footer.php'; ?>
