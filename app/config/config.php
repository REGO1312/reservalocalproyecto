<?php
/**
 * Reserva Local - Configuración Global
 */

// Evitar acceso directo no deseado
defined('APP_ROOT') or define('APP_ROOT', dirname(__DIR__, 2));

return [
    'app' => [
        'name' => 'Reserva Local',
        'env' => getenv('APP_ENV') ?: 'development',
        'debug' => (bool)(getenv('APP_DEBUG') ?: true),
        'timezone' => getenv('APP_TIMEZONE') ?: 'America/El_Salvador',
        'base_url' => getenv('APP_URL') ?: '',
        'default_location' => [
            'country' => 'El Salvador',
            'city' => 'San Salvador',
            'lat' => 13.6989,
            'lng' => -89.2415
        ],
    ],

    'db' => [
        'host' => getenv('DB_HOST') ?: 'sql203.infinityfree.com',
        'port' => getenv('DB_PORT') ?: '3306', // Puerto XAMPP por defecto
        'database' => getenv('DB_NAME') ?: 'if0_43219864_reserva_local',
        'username' => getenv('DB_USER') ?: 'if0_43219864',
        'password' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    ],

    'security' => [
        'max_login_attempts' => 5,
        'lockout_minutes' => 15,
        'password_reset_expire_minutes' => 60,
        'csrf_token_key' => 'csrf_token',
        'session_lifetime' => 86400, // 24 horas
    ],

    'uploads' => [
        'max_size_bytes' => 2 * 1024 * 1024, // 2 MB
        'allowed_types' => ['image/jpeg', 'image/png', 'image/webp'],
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
        'path' => APP_ROOT . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads',
        'storage_path' => APP_ROOT . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads',
    ],

    'notifications' => [
        'driver' => getenv('MAIL_DRIVER') ?: 'log', // 'log' o 'smtp'
        'log_file' => APP_ROOT . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'recordatorios.log',
        'from_address' => getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@reservalocal.test',
        'from_name' => getenv('MAIL_FROM_NAME') ?: 'Reserva Local',
        // Opcional para correo real con SMTP
        'smtp_host' => getenv('SMTP_HOST') ?: '',
        'smtp_port' => getenv('SMTP_PORT') ?: 587,
        'smtp_user' => getenv('SMTP_USER') ?: '',
        'smtp_pass' => getenv('SMTP_PASS') ?: '',
        'smtp_secure' => getenv('SMTP_SECURE') ?: 'tls',
    ]
];
