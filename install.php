<?php
/**
 * Reserva Local - Instalador Automático de Base de Datos
 * Puede ejecutarse desde CLI: php install.php
 * O desde el navegador: http://localhost:8000/install.php
 */

define('APP_ROOT', __DIR__);

// Cabecera si es navegador
$isCli = (php_sapi_name() === 'cli');
if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
}

function outputMsg(string $msg, string $type = 'info', bool $isCli = false): void {
    if ($isCli) {
        $prefix = match($type) {
            'success' => '[OK] ',
            'error' => '[ERROR] ',
            'warning' => '[AVISO] ',
            default => '[INFO] '
        };
        echo $prefix . $msg . PHP_EOL;
    } else {
        $color = match($type) {
            'success' => '#16a34a',
            'error' => '#dc2626',
            'warning' => '#d97706',
            default => '#2563eb'
        };
        echo "<div style='margin: 8px 0; padding: 10px; border-left: 4px solid $color; background: #f8fafc; font-family: monospace;'><strong>" . strtoupper($type) . ":</strong> " . htmlspecialchars($msg) . "</div>";
    }
}

if (!$isCli) {
    echo "<!DOCTYPE html><html lang='es'><head><meta charset='utf-8'><title>Instalador Reserva Local</title><style>body{max-width:800px;margin:30px auto;padding:20px;font-family:system-ui,-apple-system,sans-serif;color:#1e293b;background:#f1f5f9;line-height:1.5;}h1{color:#0f172a;}pre{background:#fff;padding:15px;border-radius:8px;border:1px solid #cbd5e1;overflow-x:auto;}</style></head><body><h1>🛠️ Instalador Reserva Local</h1>";
}

try {
    $config = require __DIR__ . '/app/config/config.php';
    $db = $config['db'];

    outputMsg("Conectando al servidor MySQL en {$db['host']}:{$db['port']} con usuario '{$db['username']}'...", 'info', $isCli);

    // 1. Conexión inicial sin base de datos
    $pdoInit = new PDO(
        "mysql:host={$db['host']};port={$db['port']}",
        $db['username'],
        $db['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // 2. Crear base de datos
    $dbName = $db['database'];
    outputMsg("Creando base de datos '$dbName' si no existe...", 'info', $isCli);
    $pdoInit->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    outputMsg("Base de datos '$dbName' lista.", 'success', $isCli);

    // 3. Conexión con la base de datos seleccionada
    $dsn = "mysql:host={$db['host']};port={$db['port']};dbname=$dbName;charset={$db['charset']}";
    $pdo = new PDO($dsn, $db['username'], $db['password'], $db['options']);

    // 4. Importar schema.sql
    $schemaFile = __DIR__ . '/database/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("No se encontró el archivo schema.sql en: $schemaFile");
    }
    outputMsg("Importando estructura de tablas desde schema.sql...", 'info', $isCli);
    $schemaSql = file_get_contents($schemaFile);
    $pdo->exec($schemaSql);
    outputMsg("Tablas creadas exitosamente.", 'success', $isCli);

    // 5. Importar datos_demo.sql
    $demoFile = __DIR__ . '/database/datos_demo.sql';
    if (!file_exists($demoFile)) {
        throw new Exception("No se encontró el archivo datos_demo.sql en: $demoFile");
    }
    outputMsg("Importando datos de demostración desde datos_demo.sql...", 'info', $isCli);
    $demoSql = file_get_contents($demoFile);
    $pdo->exec($demoSql);
    outputMsg("Datos de demostración importados exitosamente.", 'success', $isCli);

    // 6. Asegurar directorios de almacenamiento
    $dirs = [
        __DIR__ . '/storage/logs',
        __DIR__ . '/storage/uploads/negocios',
        __DIR__ . '/public/uploads/negocios',
        __DIR__ . '/public/assets/img'
    ];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    // 7. Generar imágenes SVG de demostración para los negocios
    $imgDir = __DIR__ . '/public/assets/img';

    // Barbería Caballero SVG
    $barberSvg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 400" width="100%" height="100%">
  <defs>
    <linearGradient id="b1" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#1e293b"/>
      <stop offset="100%" stop-color="#0f172a"/>
    </linearGradient>
    <linearGradient id="gold" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#f59e0b"/>
      <stop offset="100%" stop-color="#b45309"/>
    </linearGradient>
  </defs>
  <rect width="600" height="400" fill="url(#b1)"/>
  <circle cx="300" cy="170" r="85" fill="none" stroke="url(#gold)" stroke-width="4"/>
  <path d="M260,185 L340,185 M275,150 C290,140 310,140 325,150 C310,165 290,165 275,150 Z" fill="url(#gold)"/>
  <path d="M270,175 Q300,195 330,175 Q300,215 270,175 Z" fill="#ffffff"/>
  <text x="300" y="295" font-family="system-ui, sans-serif" font-size="24" font-weight="bold" fill="#ffffff" text-anchor="middle" letter-spacing="2">BARBERÍA EL CABALLERO</text>
  <text x="300" y="325" font-family="system-ui, sans-serif" font-size="14" fill="#94a3b8" text-anchor="middle">COL. ESCALÓN • SAN SALVADOR</text>
</svg>
SVG;
    file_put_contents($imgDir . '/barberia-caballero.svg', $barberSvg);

    // Salón Bella Donna SVG
    $salonSvg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 400" width="100%" height="100%">
  <defs>
    <linearGradient id="s1" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#831843"/>
      <stop offset="100%" stop-color="#500724"/>
    </linearGradient>
    <linearGradient id="rose" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#f472b6"/>
      <stop offset="100%" stop-color="#db2777"/>
    </linearGradient>
  </defs>
  <rect width="600" height="400" fill="url(#s1)"/>
  <circle cx="300" cy="170" r="85" fill="none" stroke="url(#rose)" stroke-width="4"/>
  <path d="M280,140 Q300,120 320,140 Q330,170 300,205 Q270,170 280,140 Z" fill="url(#rose)"/>
  <text x="300" y="295" font-family="system-ui, sans-serif" font-size="24" font-weight="bold" fill="#ffffff" text-anchor="middle" letter-spacing="2">SALÓN &amp; SPA BELLA DONNA</text>
  <text x="300" y="325" font-family="system-ui, sans-serif" font-size="14" fill="#fbcfe8" text-anchor="middle">SAN BENITO • SAN SALVADOR</text>
</svg>
SVG;
    file_put_contents($imgDir . '/salon-belladonna.svg', $salonSvg);

    // Barbería Vintage Blade SVG
    $vintageSvg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 400" width="100%" height="100%">
  <defs>
    <linearGradient id="v1" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#14532d"/>
      <stop offset="100%" stop-color="#052e16"/>
    </linearGradient>
    <linearGradient id="brass" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#facc15"/>
      <stop offset="100%" stop-color="#a16207"/>
    </linearGradient>
  </defs>
  <rect width="600" height="400" fill="url(#v1)"/>
  <circle cx="300" cy="170" r="85" fill="none" stroke="url(#brass)" stroke-width="4"/>
  <path d="M255,160 L345,180 L340,190 L250,170 Z" fill="url(#brass)"/>
  <text x="300" y="295" font-family="system-ui, sans-serif" font-size="24" font-weight="bold" fill="#ffffff" text-anchor="middle" letter-spacing="2">VINTAGE BLADE STUDIO</text>
  <text x="300" y="325" font-family="system-ui, sans-serif" font-size="14" fill="#bbf7d0" text-anchor="middle">SANTA TECLA • LA LIBERTAD</text>
</svg>
SVG;
    file_put_contents($imgDir . '/barberia-vintage.svg', $vintageSvg);

    outputMsg("Directorios de almacenamiento e imágenes demo preparados.", 'success', $isCli);

    $summary = <<<TXT
=============================================================
 ¡INSTALACIÓN COMPLETADA EXITOSAMENTE!
=============================================================
Base de datos: {$db['database']}
Host: {$db['host']}:{$db['port']}

CUENTAS DE DEMOSTRACIÓN (Contraseña para todas: Demo123!)
-------------------------------------------------------------
1. Administrador Global:
   - Email: admin@reservalocal.test
   - Acceso: Panel Administrador (cuentas, negocios, auditoría)

2. Propietarios de Negocio:
   - Email: propietario1@reservalocal.test (Barbería El Caballero & Vintage)
   - Email: propietario2@reservalocal.test (Salón Bella Donna)
   - Acceso: Panel Propietario (calendario, servicios, personal, horarios, reportes)

3. Profesionales / Barberos / Estilistas:
   - Email: carlos@reservalocal.test (Barbero en El Caballero)
   - Email: sofia@reservalocal.test (Estilista en Bella Donna)
   - Acceso: Panel Profesional (agenda personal y gestión de citas)

4. Clientes de Prueba:
   - Email: cliente1@reservalocal.test (Alejandro Morales)
   - Email: cliente2@reservalocal.test (Camila Torres)
   - Acceso: Búsqueda GPS/manual, reservas, mis citas, perfil

Para arrancar el servidor integrado:
   php -S localhost:8000 -t public
O vía XAMPP:
   http://localhost/reserva-local/public/
=============================================================
TXT;

    if ($isCli) {
        echo PHP_EOL . $summary . PHP_EOL;
    } else {
        echo "<pre style='font-size: 13px; line-height: 1.4;'>" . htmlspecialchars($summary) . "</pre>";
        echo "<p><a href='public/' style='display:inline-block;padding:10px 20px;background:#2563eb;color:#fff;text-decoration:none;border-radius:6px;font-weight:600;'>Ir a la aplicación &rarr;</a></p></body></html>";
    }

} catch (Exception $e) {
    outputMsg("Error durante la instalación: " . $e->getMessage(), 'error', $isCli);
    if (!$isCli) {
        echo "</body></html>";
    }
    exit(1);
}
