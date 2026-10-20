<?php
/**
 * Reserva Local - Bootstrap Central
 */

defined('APP_ROOT') or define('APP_ROOT', dirname(__DIR__));

// Cargar configuración
$config = require APP_ROOT . '/app/config/config.php';

// Zona horaria por defecto
date_default_timezone_set($config['app']['timezone'] ?? 'America/Mexico_City');

// Cargar Database singleton
require_once APP_ROOT . '/app/config/database.php';

// Cargar helpers generales
require_once APP_ROOT . '/app/helpers/helpers.php';

// Iniciar sesión segura automáticamente
session_start_secure();

// Autoload simple de modelos y servicios
spl_autoload_register(function ($class) {
    $paths = [
        APP_ROOT . '/app/models/' . $class . '.php',
        APP_ROOT . '/app/services/' . $class . '.php',
        APP_ROOT . '/app/controllers/' . $class . '.php',
        APP_ROOT . '/app/middleware/' . $class . '.php',
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});
