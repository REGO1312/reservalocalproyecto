<?php
/**
 * Reserva Local - Explorador y Búsqueda de Negocios
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

$q = trim($_GET['q'] ?? '');
$categoria = trim($_GET['categoria'] ?? '');
$servicio = trim($_GET['servicio'] ?? '');
$zona = trim($_GET['zona'] ?? '');
$lat = isset($_GET['lat']) && is_numeric($_GET['lat']) ? (float)$_GET['lat'] : null;
$lng = isset($_GET['lng']) && is_numeric($_GET['lng']) ? (float)$_GET['lng'] : null;
$radio = isset($_GET['radio']) && is_numeric($_GET['radio']) ? (float)$_GET['radio'] : 50.0;

$hasCoords = ($lat !== null && $lng !== null);

// Búsqueda en MySQL con Haversine y filtros
$fueraDeRadio = false;
$negocios = Negocio::buscar([
    'q' => $q,
    'categoria' => $categoria,
    'servicio' => $servicio,
    'zona' => $zona,
    'lat' => $lat,
    'lng' => $lng,
    'radio' => $radio
]);

// Si el usuario proporcionó coordenadas pero no hay negocios en el radio seleccionado,
// calcular con radio 0 (sin límite) para que vea que el GPS sí calcula su distancia real.
if (empty($negocios) && $hasCoords && empty($q) && empty($categoria) && empty($servicio) && empty($zona)) {
    $negocios = Negocio::buscar([
        'q' => '',
        'categoria' => '',
        'servicio' => '',
        'zona' => '',
        'lat' => $lat,
        'lng' => $lng,
        'radio' => 0
    ]);
    if (!empty($negocios)) {
        $fueraDeRadio = true;
    }
}

$pageTitle = 'Explorar Barberías y Salones';
$extraScripts = ['assets/js/explorar.js'];
require APP_ROOT . '/app/views/layouts/header.php';
?>

<div class="container py-4">
    <div class="mb-4">
        <h1>Explorar Barberías y Salones</h1>
        <p class="text-muted">Descubre establecimientos verificados, filtra por cercanía o por tu servicio preferido.</p>
    </div>

    <!-- Filtros de Búsqueda -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="<?= url('/explorar.php') ?>" method="GET" id="formFiltros">
                <input type="hidden" name="lat" id="inputLat" value="<?= $hasCoords ? e((string)$lat) : '' ?>">
                <input type="hidden" name="lng" id="inputLng" value="<?= $hasCoords ? e((string)$lng) : '' ?>">

                <div class="grid grid-3">
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="inputQ" class="form-label">Buscar por Nombre o Palabra Clave</label>
                        <input type="text" id="inputQ" name="q" class="form-control" value="<?= e($q) ?>" placeholder="ej. Caballero, Bella Donna...">
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label for="selectCat" class="form-label">Categoría</label>
                        <select id="selectCat" name="categoria" class="form-control">
                            <option value="">Todas las categorías</option>
                            <option value="barberia" <?= $categoria === 'barberia' ? 'selected' : '' ?>>💈 Barberías</option>
                            <option value="salon" <?= $categoria === 'salon' ? 'selected' : '' ?>>💇‍♀️ Salones de Belleza</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label for="inputServicio" class="form-label">Servicio Específico</label>
                        <input type="text" id="inputServicio" name="servicio" class="form-control" value="<?= e($servicio) ?>" placeholder="ej. Barba, Balayage, Manicura...">
                    </div>
                </div>

                <!-- Ubicación GPS o Alternativa Manual -->
                <div style="border-top: 1px solid var(--border-color); margin-top: 1.25rem; padding-top: 1.25rem;">
                    <div class="d-flex justify-between align-center" style="flex-wrap: wrap; gap: 1rem;">
                        <div style="flex: 1; min-width: 280px;" id="manualLocationGroup">
                            <label for="inputZona" class="form-label">Ubicación Manual (Colonia, Municipio o Departamento)</label>
                            <input type="text" id="inputZona" name="zona" class="form-control" value="<?= e($zona) ?>" placeholder="ej. Escalón, San Benito, Santa Tecla, Antiguo Cuscatlán">
                        </div>

                        <div style="flex: 1.2; min-width: 320px;">
                            <label class="form-label">Ubicación por GPS (Navegador)</label>
                            <div class="d-flex gap-2" style="flex-wrap:wrap;">
                                <button type="button" id="btnGps" class="btn btn-outline" style="flex:1; min-width:160px;">
                                    📍 <?= $hasCoords ? 'Actualizar mi GPS' : 'Usar mi ubicación GPS' ?>
                                </button>
                                <button type="button" id="btnGpsDemo" class="btn btn-outline" style="flex:1; min-width:180px;" title="Simular estar en San Salvador cerca de los negocios demo">
                                    🎯 Probar GPS en San Salvador (Demo)
                                </button>
                                <?php if ($hasCoords): ?>
                                    <button type="button" id="btnClearGps" class="btn btn-outline text-danger" title="Borrar ubicación GPS">✕ Quitar</button>
                                <?php endif; ?>
                            </div>
                            <div id="gpsStatus" class="form-text" style="<?= $hasCoords ? '' : 'display:none;' ?>">
                                <?= $hasCoords ? "✓ Coordenadas activas ($lat, $lng)" : '' ?>
                            </div>
                        </div>

                        <?php if ($hasCoords): ?>
                            <div style="min-width: 160px;">
                                <label for="selectRadio" class="form-label">Radio Máximo</label>
                                <select id="selectRadio" name="radio" class="form-control" onchange="this.form.submit()">
                                    <option value="5" <?= $radio == 5 ? 'selected' : '' ?>>5 km</option>
                                    <option value="10" <?= $radio == 10 ? 'selected' : '' ?>>10 km</option>
                                    <option value="25" <?= $radio == 25 ? 'selected' : '' ?>>25 km</option>
                                    <option value="50" <?= $radio == 50 ? 'selected' : '' ?>>50 km</option>
                                    <option value="100" <?= $radio == 100 ? 'selected' : '' ?>>100 km</option>
                                    <option value="500" <?= $radio == 500 ? 'selected' : '' ?>>500 km</option>
                                    <option value="5000" <?= $radio == 5000 ? 'selected' : '' ?>>5,000 km</option>
                                    <option value="0" <?= $radio == 0 ? 'selected' : '' ?>>Sin límite (Ordenar por distancia)</option>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-flex justify-between align-center mt-3">
                    <span class="text-muted" style="font-size:0.9rem;">Resultados encontrados: <strong><?= count($negocios) ?></strong></span>
                    <div class="d-flex gap-2">
                        <a href="<?= url('/explorar.php') ?>" class="btn btn-outline btn-sm">Limpiar Filtros</a>
                        <button type="submit" class="btn btn-primary btn-sm">Aplicar Filtros</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($fueraDeRadio): ?>
        <div class="alert alert-info">
            <span class="alert-icon">📍</span>
            <div class="alert-body">
                <strong>Ubicación GPS detectada con éxito (Lat: <?= e((string)$lat) ?>, Lng: <?= e((string)$lng) ?>):</strong><br>
                Tu ubicación física actual se encuentra a más de <strong><?= e((string)$radio) ?> km</strong> de los negocios registrados (los negocios de prueba se encuentran en San Salvador / Santa Tecla, El Salvador).
                <br>
                El sistema calculó la distancia en tiempo real mediante la <strong>fórmula Haversine en MySQL</strong> y a continuación te muestra los negocios disponibles <strong>ordenados por proximidad real desde tu ubicación</strong>:
            </div>
        </div>
    <?php endif; ?>

    <!-- Listado de Negocios -->
    <?php if (empty($negocios)): ?>
        <div class="card text-center py-5">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🔍</div>
            <h3>No encontramos resultados</h3>
            <p class="text-muted">Prueba cambiando los términos de búsqueda, ampliando el radio o usando la búsqueda por zona.</p>
            <div class="mt-3">
                <a href="<?= url('/explorar.php') ?>" class="btn btn-primary">Ver Todos los Negocios</a>
            </div>
        </div>
    <?php else: ?>
        <div class="grid grid-3">
            <?php foreach ($negocios as $neg): ?>
                <div class="card business-card card-hover">
                    <div class="business-img-wrapper">
                        <img src="<?= asset($neg['imagen_url'] ?: 'assets/img/barberia-caballero.svg') ?>" alt="<?= e($neg['nombre']) ?>">
                        
                        <span class="badge badge-<?= e($neg['categoria']) ?>" style="position: absolute; bottom: 10px; left: 10px;">
                            <?= $neg['categoria'] === 'barberia' ? '💈 Barbería' : '💇‍♀️ Salón' ?>
                        </span>

                        <?php if ($neg['distancia_km'] !== null): ?>
                            <span class="business-distance-pill">
                                🚗 a <?= e((string)$neg['distancia_km']) ?> km
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="business-body">
                        <h3 style="margin-bottom: 0.25rem;">
                            <a href="<?= url('/negocio.php?id=' . $neg['id']) ?>"><?= e($neg['nombre']) ?></a>
                        </h3>
                        <p class="text-muted" style="font-size: 0.85rem; margin-bottom: 0.5rem;">
                            📍 <?= e($neg['zona_municipio']) ?>
                        </p>
                        <p style="font-size: 0.85rem; color: var(--text-secondary);"><?= e($neg['direccion']) ?></p>

                        <?php if (!empty($neg['servicios_destacados'])): ?>
                            <div class="business-services-list">
                                <strong style="display:block; margin-bottom:0.25rem; font-size:0.8rem; color:var(--text-muted);">Servicios disponibles:</strong>
                                <?php foreach ($neg['servicios_destacados'] as $s): ?>
                                    <div class="business-service-item">
                                        <span><?= e($s['nombre']) ?></span>
                                        <strong><?= format_money($s['precio']) ?></strong>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex gap-2 mt-auto" style="padding-top: 1rem;">
                            <a href="<?= url('/negocio.php?id=' . $neg['id']) ?>" class="btn btn-outline btn-sm" style="flex:1;">Detalles</a>
                            <a href="<?= url('/reservar.php?negocio_id=' . $neg['id']) ?>" class="btn btn-primary btn-sm" style="flex:1;">Agendar Cita</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require APP_ROOT . '/app/views/layouts/footer.php'; ?>
