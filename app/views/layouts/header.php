<?php
/**
 * Header común para todas las vistas públicas y de clientes
 */
$authUser = auth_user();
$roles = user_roles();
$currentUrl = $_SERVER['REQUEST_URI'] ?? '';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Descubre y reserva citas al instante en las mejores barberías y salones de belleza locales.">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' | Reserva Local' : 'Reserva Local - Citas en Barberías y Salones' ?></title>
    <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
</head>
<body>
    <header class="navbar">
        <div class="container navbar-container">
            <a href="<?= url('/') ?>" class="brand-logo">
                <span class="logo-icon">✂️</span>
                <span class="logo-text">Reserva<span class="logo-highlight">Local</span></span>
            </a>

            <button class="nav-toggle" id="navToggle" aria-label="Abrir menú de navegación">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <nav class="nav-menu" id="navMenu">
                <ul class="nav-list">
                    <li><a href="<?= url('/explorar.php') ?>" class="nav-link <?= strpos($currentUrl, 'explorar') !== false ? 'active' : '' ?>">🔍 Explorar Negocios</a></li>
                    
                    <?php if ($authUser): ?>
                        <li><a href="<?= url('/mis-citas.php') ?>" class="nav-link <?= strpos($currentUrl, 'mis-citas') !== false ? 'active' : '' ?>">📅 Mis Citas</a></li>
                        
                        <?php if (in_array('propietario', $roles, true)): ?>
                            <li><a href="<?= url('/propietario/') ?>" class="nav-link nav-badge-panel">🏢 Mi Negocio</a></li>
                        <?php endif; ?>

                        <?php if (in_array('profesional', $roles, true)): ?>
                            <li><a href="<?= url('/profesional/') ?>" class="nav-link nav-badge-panel">📋 Mi Agenda</a></li>
                        <?php endif; ?>

                        <?php if (in_array('administrador', $roles, true)): ?>
                            <li><a href="<?= url('/administrador/') ?>" class="nav-link nav-badge-admin">⚙️ Admin</a></li>
                        <?php endif; ?>

                        <li class="nav-user-dropdown">
                            <a href="<?= url('/perfil.php') ?>" class="nav-link nav-user">
                                <span class="user-avatar"><?= strtoupper(substr($authUser['nombre'], 0, 1)) ?></span>
                                <span class="user-name"><?= e($authUser['nombre']) ?></span>
                            </a>
                            <a href="<?= url('/logout.php') ?>" class="btn-logout" title="Cerrar sesión">Salir</a>
                        </li>
                    <?php else: ?>
                        <li><a href="<?= url('/login.php') ?>" class="nav-link">Iniciar Sesión</a></li>
                        <li><a href="<?= url('/registro.php') ?>" class="btn btn-primary btn-sm">Registrarse</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <?php if ($flash): ?>
            <div class="container mt-3">
                <div class="alert alert-<?= e($flash['type']) ?>" role="alert">
                    <span class="alert-icon">
                        <?= $flash['type'] === 'success' ? '✓' : ($flash['type'] === 'error' ? '⚠️' : 'ℹ️') ?>
                    </span>
                    <div class="alert-body"><?= e($flash['message']) ?></div>
                </div>
            </div>
        <?php endif; ?>
