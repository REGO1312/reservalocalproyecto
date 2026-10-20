<?php
http_response_code(404);
$pageTitle = 'Página No Encontrada (404)';
require APP_ROOT . '/app/views/layouts/header.php';
?>
<div class="container py-5 text-center">
    <div class="error-card">
        <h1 class="error-code">404</h1>
        <h2>Página No Encontrada</h2>
        <p class="text-muted">El recurso que buscas no existe o ha sido movido.</p>
        <div class="mt-4">
            <a href="<?= url('/explorar.php') ?>" class="btn btn-primary">Explorar Negocios</a>
            <a href="<?= url('/') ?>" class="btn btn-outline">Volver al Inicio</a>
        </div>
    </div>
</div>
<?php require APP_ROOT . '/app/views/layouts/footer.php'; ?>
