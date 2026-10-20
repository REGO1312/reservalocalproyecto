<?php
/**
 * Reserva Local - Cierre de Sesión Seguro
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

if (is_authenticated()) {
    $uId = auth_id();
    audit_log($uId, 'LOGOUT', 'usuarios', $uId, 'Cierre de sesión de usuario');
}

session_start_secure();
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

// Iniciar nueva sesión para mensaje flash
session_start_secure();
set_flash('info', 'Has cerrado sesión correctamente.');
redirect('/');
