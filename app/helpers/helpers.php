<?php
/**
 * Reserva Local - Funciones Auxiliares (Seguridad, Sesión, URLs, Formato)
 */

if (!function_exists('e')) {
    /**
     * Escapar salida contra ataques XSS
     */
    function e(?string $value): string {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('session_start_secure')) {
    /**
     * Iniciar sesión segura con SameSite, HttpOnly y Secure dinámico
     */
    function session_start_secure(): void {
        if (session_status() === PHP_SESSION_NONE) {
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

            session_set_cookie_params([
                'lifetime' => 86400,
                'path' => '/',
                'domain' => '',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            session_name('RESERVA_LOCAL_SESS');
            session_start();
        }
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Obtener o generar token CSRF
     */
    function csrf_token(): string {
        session_start_secure();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Generar input hidden con token CSRF
     */
    function csrf_field(): string {
        return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('verify_csrf')) {
    /**
     * Validar token CSRF recibido en POST o en cabecera HTTP_X_CSRF_TOKEN
     */
    function verify_csrf(?string $token = null): bool {
        session_start_secure();
        if ($token === null) {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        }
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('auth_user')) {
    /**
     * Obtener usuario autenticado actual o null
     */
    function auth_user(): ?array {
        session_start_secure();
        return $_SESSION['user'] ?? null;
    }
}

if (!function_exists('auth_id')) {
    function auth_id(): ?int {
        $u = auth_user();
        return $u ? (int)$u['id'] : null;
    }
}

if (!function_exists('is_authenticated')) {
    function is_authenticated(): bool {
        return auth_user() !== null;
    }
}

if (!function_exists('user_roles')) {
    /**
     * Lista de nombres de roles del usuario actual
     */
    function user_roles(): array {
        $u = auth_user();
        return $u['roles'] ?? [];
    }
}

if (!function_exists('has_role')) {
    /**
     * Comprobar si el usuario actual posee un rol específico
     */
    function has_role(string $role): bool {
        return in_array($role, user_roles(), true);
    }
}

if (!function_exists('require_login')) {
    /**
     * Exigir sesión activa o redirigir a login
     */
    function require_login(): void {
        if (!is_authenticated()) {
            set_flash('error', 'Debes iniciar sesión para acceder a esta sección.');
            redirect('/login.php');
        }
    }
}

if (!function_exists('require_role')) {
    /**
     * Exigir que el usuario tenga al menos uno de los roles solicitados
     */
    function require_role(string|array $roles): void {
        require_login();
        $roles = is_array($roles) ? $roles : [$roles];
        $hasAny = false;
        foreach ($roles as $r) {
            if (has_role($r)) {
                $hasAny = true;
                break;
            }
        }
        if (!$hasAny) {
            http_response_code(403);
            require_once APP_ROOT . '/app/views/layouts/403.php';
            exit;
        }
    }
}

if (!function_exists('base_path_url')) {
    /**
     * Detectar la base URL de forma dinámica
     */
    function base_path_url(): string {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        // Si estamos dentro de /reserva-local/public/
        if (strpos($script, '/reserva-local/public') !== false) {
            return '/reserva-local/public';
        }
        return '';
    }
}

if (!function_exists('url')) {
    /**
     * Construir URL absoluta interna del proyecto
     */
    function url(string $path = ''): string {
        $base = base_path_url();
        $path = '/' . ltrim($path, '/');
        if ($path === '/' && !empty($base)) {
            return $base . '/';
        }
        return $base . $path;
    }
}

if (!function_exists('asset')) {
    /**
     * Construir URL hacia un recurso estático
     */
    function asset(string $path): string {
        $path = ltrim($path, '/');
        return url('/' . $path);
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirección HTTP segura
     */
    function redirect(string $path): void {
        $target = (str_starts_with($path, 'http://') || str_starts_with($path, 'https://'))
            ? $path
            : url($path);
        header('Location: ' . $target);
        exit;
    }
}

if (!function_exists('set_flash')) {
    function set_flash(string $type, string $message): void {
        session_start_secure();
        $_SESSION['flash'] = [
            'type' => $type, // 'success', 'error', 'info', 'warning'
            'message' => $message
        ];
    }
}

if (!function_exists('get_flash')) {
    function get_flash(): ?array {
        session_start_secure();
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
}

if (!function_exists('json_response')) {
    function json_response(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}

if (!function_exists('audit_log')) {
    /**
     * Registrar evento de auditoría en la base de datos
     */
    function audit_log(?int $usuarioId, string $accion, string $entidad, ?int $entidadId, ?string $detalles = null): void {
        try {
            $pdo = Database::getConnection();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

            $stmt = $pdo->prepare("
                INSERT INTO auditoria (usuario_id, accion, entidad, entidad_id, detalles, ip, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$usuarioId, $accion, $entidad, $entidadId, $detalles, $ip, $ua]);
        } catch (Exception $e) {
            // Silencioso para no interrumpir el flujo principal, pero registrado en log
            @file_put_contents(
                APP_ROOT . '/storage/logs/auditoria_error.log',
                date('[Y-m-d H:i:s] ') . 'Error al auditar: ' . $e->getMessage() . PHP_EOL,
                FILE_APPEND
            );
        }
    }
}

if (!function_exists('format_money')) {
    function format_money(float|int|string $amount): string {
        return '$' . number_format((float)$amount, 2, '.', ',');
    }
}

if (!function_exists('format_duration')) {
    function format_duration(int $minutes): string {
        if ($minutes < 60) {
            return $minutes . ' min';
        }
        $hours = floor($minutes / 60);
        $rem = $minutes % 60;
        return $rem > 0 ? "{$hours}h {$rem}min" : "{$hours}h";
    }
}

if (!function_exists('format_date_es')) {
    function format_date_es(string $date): string {
        $timestamp = strtotime($date);
        $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        $meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        $diaSemana = $dias[date('w', $timestamp)];
        $dia = date('d', $timestamp);
        $mes = $meses[(int)date('m', $timestamp)];
        $anio = date('Y', $timestamp);
        return "$diaSemana, $dia de $mes de $anio";
    }
}

if (!function_exists('day_name_es')) {
    function day_name_es(int $day): string {
        return match($day) {
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo',
            default => 'Desconocido'
        };
    }
}
