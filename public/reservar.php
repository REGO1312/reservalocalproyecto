<?php
/**
 * Reserva Local - Wizard Interactivo de Citas y Reserva
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

$negocioId = (int)($_GET['negocio_id'] ?? 0);
$selectedServicioId = (int)($_GET['servicio_id'] ?? 0);

$negocio = Negocio::findById($negocioId);
if (!$negocio || $negocio['estado'] !== 'activo') {
    // Si no se pasó negocio, redirigir a explorar
    redirect('/explorar.php');
}

$servicios = Servicio::findByNegocio($negocioId, true);
$profesionales = Profesional::findByNegocio($negocioId, true);

// Fecha mínima: hoy
$hoy = date('Y-m-d');
$fechaDefecto = $hoy;

$pageTitle = 'Reservar Cita en ' . $negocio['nombre'];
$extraScripts = ['assets/js/reservar.js'];
require APP_ROOT . '/app/views/layouts/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <p class="text-muted" style="font-size:0.9rem; margin-bottom:1rem;">
        <a href="<?= url('/explorar.php') ?>">Explorar</a> /
        <a href="<?= url('/negocio.php?id=' . $negocio['id']) ?>"><?= e($negocio['nombre']) ?></a> /
        Reservar Cita
    </p>

    <div class="d-flex justify-between align-center mb-4" style="flex-wrap:wrap; gap:1rem;">
        <div>
            <h1>Reservar Cita</h1>
            <p class="text-muted" style="margin-bottom:0;">
                Establecimiento: <strong><?= e($negocio['nombre']) ?></strong> (<?= e($negocio['direccion']) ?>)
            </p>
        </div>
        <span class="badge badge-<?= e($negocio['categoria']) ?>">
            <?= $negocio['categoria'] === 'barberia' ? '💈 Barbería' : '💇‍♀️ Salón' ?>
        </span>
    </div>

    <?php if (!is_authenticated()): ?>
        <div class="alert alert-warning">
            <span class="alert-icon">⚠️</span>
            <div class="alert-body">
                <strong>Atención:</strong> Para confirmar tu cita necesitarás una cuenta. Puedes elegir tu horario ahora y te solicitaremos iniciar sesión antes del paso final.
                <div class="mt-2">
                    <a href="<?= url('/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'])) ?>" class="btn btn-outline btn-sm">Iniciar Sesión</a>
                    <a href="<?= url('/registro.php') ?>" class="btn btn-primary btn-sm">Crear Cuenta</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Formulario / Wizard -->
    <div class="grid grid-2" style="grid-template-columns: 2fr 1fr; align-items: start;">
        <div>
            <form id="bookingForm" 
                  action="<?= url('/api/reservar.php') ?>" 
                  method="POST" 
                  data-negocio-id="<?= $negocio['id'] ?>"
                  data-api-url="<?= url('/api/disponibilidad.php') ?>">
                
                <?= csrf_field() ?>
                <input type="hidden" name="negocio_id" value="<?= $negocio['id'] ?>">
                <input type="hidden" name="hora_inicio" id="inputHoraInicio" value="">
                <input type="hidden" name="hora_fin" id="inputHoraFin" value="">
                <input type="hidden" name="profesional_id" id="hiddenProfId" value="">

                <!-- Paso 1: Seleccionar Servicio -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3>1. Elige tu Servicio</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="selectServicio" class="form-label">Servicio deseado</label>
                            <select id="selectServicio" name="servicio_id" class="form-control" required>
                                <option value="">-- Selecciona un servicio --</option>
                                <?php foreach ($servicios as $s): ?>
                                    <option value="<?= $s['id'] ?>" 
                                            data-precio="<?= $s['precio'] ?>" 
                                            data-duracion="<?= $s['duracion_minutos'] ?>"
                                            data-nombre="<?= e($s['nombre']) ?>"
                                            <?= $selectedServicioId == $s['id'] ? 'selected' : '' ?>>
                                        <?= e($s['nombre']) ?> - <?= format_money($s['precio']) ?> (<?= format_duration((int)$s['duracion_minutos']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Paso 2: Seleccionar Especialista y Fecha -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3>2. Especialista y Fecha</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="selectProfesional" class="form-label">Especialista (opcional)</label>
                                <select id="selectProfesional" class="form-control">
                                    <option value="">Cualquier profesional disponible</option>
                                    <?php foreach ($profesionales as $p): ?>
                                        <option value="<?= $p['id'] ?>">
                                            <?= e($p['nombre']) ?> <?= !empty($p['especialidad']) ? '(' . e($p['especialidad']) . ')' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Si seleccionas "Cualquiera", te asignaremos el primer especialista libre.</div>
                            </div>

                            <div class="form-group">
                                <label for="inputFecha" class="form-label">Fecha de la Cita</label>
                                <input type="date" id="inputFecha" name="fecha" class="form-control" min="<?= $hoy ?>" value="<?= $fechaDefecto ?>" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Paso 3: Horarios Disponibles en Tiempo Real -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3>3. Horarios Disponibles</h3>
                    </div>
                    <div class="card-body">
                        <div id="slotsLoading" style="display:none;" class="text-center py-4">
                            <span class="spinner"></span>
                            <p class="text-muted mt-2">Calculando turnos libres con la agenda...</p>
                        </div>

                        <div id="slotsEmpty" class="text-center text-muted py-4">
                            Selecciona un servicio para cargar los horarios libres.
                        </div>

                        <div id="slotsContainer" class="slots-grid"></div>
                    </div>
                </div>

                <!-- Paso 4: Notas del Cliente -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3>4. Notas Adicionales (Opcional)</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group" style="margin-bottom:0;">
                            <label for="notasCliente" class="form-label">Instrucciones o preferencias para el especialista</label>
                            <textarea id="notasCliente" name="notas_cliente" rows="3" class="form-control" placeholder="Ej. Corte clásico con tijera, tengo evento a las 6pm..."></textarea>
                        </div>
                    </div>
                </div>

                <div id="bookingError" class="alert alert-error" style="display:none;"></div>
            </form>
        </div>

        <!-- Resumen Lateral de la Cita -->
        <div>
            <div class="card" style="position: sticky; top: 5rem;">
                <div class="card-header">
                    <h3>Resumen de Reserva</h3>
                </div>
                <div class="card-body">
                    <div style="display:flex; flex-direction:column; gap:0.75rem; font-size:0.95rem;">
                        <div>
                            <span class="text-muted" style="display:block; font-size:0.8rem;">Establecimiento:</span>
                            <strong><?= e($negocio['nombre']) ?></strong>
                        </div>
                        <div>
                            <span class="text-muted" style="display:block; font-size:0.8rem;">Servicio:</span>
                            <strong id="summaryServicio">-</strong>
                        </div>
                        <div>
                            <span class="text-muted" style="display:block; font-size:0.8rem;">Especialista:</span>
                            <span id="summaryProfesional">Cualquiera disponible</span>
                        </div>
                        <div class="form-row">
                            <div>
                                <span class="text-muted" style="display:block; font-size:0.8rem;">Fecha:</span>
                                <strong id="summaryFecha">-</strong>
                            </div>
                            <div>
                                <span class="text-muted" style="display:block; font-size:0.8rem;">Hora:</span>
                                <strong id="summaryHora" class="text-primary">-</strong>
                            </div>
                        </div>
                        <div>
                            <span class="text-muted" style="display:block; font-size:0.8rem;">Duración estimada:</span>
                            <span id="summaryDuracion">-</span>
                        </div>

                        <div style="border-top:1px dashed var(--border-color); padding-top:0.75rem; margin-top:0.25rem;">
                            <div class="d-flex justify-between align-center">
                                <span style="font-size:1.1rem; font-weight:700;">Total a Pagar:</span>
                                <strong id="summaryPrecio" style="font-size:1.4rem; color:var(--primary);">$0.00</strong>
                            </div>
                            <small class="text-muted" style="display:block; margin-top:0.25rem;">Pago directo en el establecimiento al finalizar tu cita.</small>
                        </div>
                    </div>

                    <div class="mt-4">
                        <?php if (is_authenticated()): ?>
                            <button type="submit" form="bookingForm" id="btnConfirmar" class="btn btn-primary btn-block btn-lg" disabled>
                                Confirmar y Reservar Cita
                            </button>
                        <?php else: ?>
                            <a href="<?= url('/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'])) ?>" class="btn btn-primary btn-block btn-lg">
                                Iniciar Sesión para Confirmar
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require APP_ROOT . '/app/views/layouts/footer.php'; ?>
