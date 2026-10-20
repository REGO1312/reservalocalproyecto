<?php
$pageTitle = 'Acceso Denegado (403)';
require APP_ROOT . '/app/views/layouts/header.php';
?>
<div class="container py-5 text-center">
    <div class="error-card">
        <h1 class="error-code">403</h1>
        <h2>Acceso Denegado</h2>
        <p class="text-muted">No tienes los permisos requeridos para acceder a esta sección.</p>
        <div class="mt-4">
            <a href="<?= url('/') ?>" class="btn btn-primary">Volver al Inicio</a>
            <a href="<?= url('/login.php') ?>" class="btn btn-outline">Cambiar de Cuenta</a>
        </div>
    </div>
</div>
<?php require APP_ROOT . '/app/views/layouts/footer.php'; ?>
