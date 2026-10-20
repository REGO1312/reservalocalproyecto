<?php
/**
 * Clase Database: Conexión PDO Singleton
 */

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $config = require __DIR__ . '/config.php';
            $db = $config['db'];

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $db['host'],
                $db['port'],
                $db['database'],
                $db['charset']
            );

            try {
                self::$instance = new PDO($dsn, $db['username'], $db['password'], $db['options']);
            } catch (PDOException $e) {
                // Registrar en log antes de fallar
                $logDir = dirname(__DIR__, 2) . '/storage/logs';
                if (!is_dir($logDir)) {
                    @mkdir($logDir, 0777, true);
                }
                @file_put_contents(
                    $logDir . '/database_errors.log',
                    date('[Y-m-d H:i:s] ') . 'Error de conexión BD: ' . $e->getMessage() . PHP_EOL,
                    FILE_APPEND
                );
                throw new Exception('Error al conectar con la base de datos: ' . $e->getMessage());
            }
        }
        return self::$instance;
    }

    /**
     * Permite reiniciar la conexión (útil tras install.php)
     */
    public static function resetConnection(): void {
        self::$instance = null;
    }
}
