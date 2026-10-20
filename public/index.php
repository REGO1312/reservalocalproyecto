<?php
/**
 * Reserva Local - Página Principal (Landing Page)
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

$pageTitle = 'Encuentra y Reserva en Barberías y Salones Locales';

// Obtener negocios destacados para mostrar en la portada
$negociosDestacados = Negocio::buscar(['radio' => 100]);
$negociosDestacados = array_slice($negociosDestacados, 0, 3);

require APP_ROOT . '/app/views/layouts/header.php';
?>

<!-- Sección Hero Principal -->
<section class="hero">
    <div class="container">
        <h1>Tu Próximo Estilo a un Clic</h1>
        <p>Descubre las mejores barberías y salones de belleza cerca de ti. Compara servicios, consulta horarios libres en tiempo real y agenda tu cita sin llamadas ni esperas.</p>

        <form action="<?= url('/explorar.php') ?>" method="GET" class="hero-search-box">
            <input type="text" name="q" class="form-control" placeholder="¿Qué servicio o barbería buscas? (ej. Corte, Barba, Balayage)" style="flex: 2; min-width: 200px;">
            <select name="categoria" class="form-control" style="flex: 1; min-width: 140px;">
                <option value="">Todas las categorías</option>
                <option value="barberia">💈 Barberías</option>
                <option value="salon">💇‍♀️ Salones de Belleza</option>
            </select>
            <button type="submit" class="btn btn-primary" style="flex: 0 0 auto;">Buscar Citas</button>
        </form>
    </div>
</section>

<!-- Sección Cómo Funciona -->
<section class="container py-5">
    <div class="text-center mb-5">
        <h2>¿Cómo Funciona Reserva Local?</h2>
        <p class="text-muted">Tres sencillos pasos para asegurar tu turno en minutos.</p>
    </div>

    <div class="grid grid-3">
        <div class="card text-center card-hover">
            <div class="card-body">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">📍</div>
                <h3>1. Descubre Negocios</h3>
                <p class="text-muted">Encuentra barberías y salones por ubicación GPS o por tu colonia favorita ordenados por cercanía real.</p>
            </div>
        </div>

        <div class="card text-center card-hover">
            <div class="card-body">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">⏰</div>
                <h3>2. Elige Horario Libre</h3>
                <p class="text-muted">Consulta la agenda en vivo de tu especialista favorito. Sin solapamientos ni turnos duplicados.</p>
            </div>
        </div>

        <div class="card text-center card-hover">
            <div class="card-body">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">✅</div>
                <h3>3. Cita Confirmada</h3>
                <p class="text-muted">Recibe tu código de reserva al instante, con precio congelado y recordatorios automáticos.</p>
            </div>
        </div>
    </div>
</section>

<!-- Negocios Destacados -->
<section class="container py-5" style="border-top: 1px solid var(--border-color);">
    <div class="d-flex justify-between align-center mb-4">
        <div>
            <h2>Negocios Destacados</h2>
            <p class="text-muted">Explora opciones verificadas con los mejores servicios disponibles.</p>
        </div>
        <a href="<?= url('/explorar.php') ?>" class="btn btn-outline">Ver Todos &rarr;</a>
    </div>

    <div class="grid grid-3">
        <?php foreach ($negociosDestacados as $neg): ?>
            <div class="card business-card card-hover">
                <div class="business-img-wrapper">
                    <img src="<?= asset($neg['imagen_url'] ?: 'assets/img/barberia-caballero.svg') ?>" alt="<?= e($neg['nombre']) ?>">
                    <span class="badge badge-<?= e($neg['categoria']) ?>" style="position: absolute; bottom: 10px; left: 10px;">
                        <?= $neg['categoria'] === 'barberia' ? '💈 Barbería' : '💇‍♀️ Salón' ?>
                    </span>
                </div>
                <div class="business-body">
                    <h3 style="margin-bottom: 0.25rem;"><a href="<?= url('/negocio.php?id=' . $neg['id']) ?>"><?= e($neg['nombre']) ?></a></h3>
                    <p class="text-muted" style="font-size: 0.85rem; margin-bottom: 0.75rem;">📍 <?= e($neg['zona_municipio']) ?> - <?= e($neg['direccion']) ?></p>
                    <p style="font-size: 0.9rem; color: var(--text-secondary); flex: 1;"><?= e(substr($neg['descripcion'] ?? '', 0, 110)) ?>...</p>

                    <?php if (!empty($neg['servicios_destacados'])): ?>
                        <div class="business-services-list">
                            <?php foreach ($neg['servicios_destacados'] as $s): ?>
                                <div class="business-service-item">
                                    <span><?= e($s['nombre']) ?></span>
                                    <strong><?= format_money($s['precio']) ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex gap-2 mt-3">
                        <a href="<?= url('/negocio.php?id=' . $neg['id']) ?>" class="btn btn-outline btn-sm" style="flex:1;">Ver Ficha</a>
                        <a href="<?= url('/reservar.php?negocio_id=' . $neg['id']) ?>" class="btn btn-primary btn-sm" style="flex:1;">Reservar Cita</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Call to action para dueños de negocios -->
<section style="background: var(--bg-subtle); padding: 4rem 0; margin-top: 2rem; border-top: 1px solid var(--border-color);">
    <div class="container text-center">
        <h2>¿Tienes una Barbería o Salón de Belleza?</h2>
        <p class="text-muted" style="max-width: 600px; margin: 0 auto 1.5rem;">Administra tu personal, tus horarios por especialista, evita ausencias con recordatorios y visualiza reportes de ingresos en tiempo real.</p>
        <a href="<?= url('/registro.php?tipo=propietario') ?>" class="btn btn-accent btn-lg">Registrar Mi Negocio Gratis</a>
    </div>
</section>

<?php require APP_ROOT . '/app/views/layouts/footer.php'; ?>
