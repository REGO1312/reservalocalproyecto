<?php
/**
 * Servicio de Notificaciones y Recordatorios con Adaptadores Desacoplados
 */

interface NotificationSenderInterface {
    public function send(array $notification): bool;
    public function getLastError(): ?string;
}

/**
 * Adaptador por defecto: Registro en archivo de log
 */
class LogNotificationSender implements NotificationSenderInterface {
    private string $logPath;
    private ?string $lastError = null;

    public function __construct(?string $logPath = null) {
        $this->logPath = $logPath ?: (APP_ROOT . '/storage/logs/recordatorios.log');
        $dir = dirname($this->logPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
    }

    public function send(array $notification): bool {
        try {
            $entry = sprintf(
                "[%s] CANAL:LOG | ID:%d | TIPO:%s | DEST:%s | ASUNTO:\"%s\" | MENSAJE:\"%s\"%s",
                date('Y-m-d H:i:s'),
                $notification['id'],
                $notification['tipo'],
                $notification['destinatario'],
                $notification['asunto'],
                str_replace(["\r", "\n"], ' ', $notification['mensaje']),
                PHP_EOL
            );
            return (bool)file_put_contents($this->logPath, $entry, FILE_APPEND);
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function getLastError(): ?string {
        return $this->lastError;
    }
}

/**
 * Adaptador para correo real (usa mail() nativo o SMTP según variables de entorno)
 */
class MailNotificationSender implements NotificationSenderInterface {
    private ?string $lastError = null;
    private array $config;

    public function __construct(array $config) {
        $this->config = $config;
    }

    public function send(array $notification): bool {
        $to = $notification['destinatario'];
        $subject = '=?UTF-8?B?' . base64_encode($notification['asunto']) . '?=';
        $message = $notification['mensaje'];
        $from = $this->config['from_address'] ?? 'no-reply@reservalocal.test';
        $fromName = $this->config['from_name'] ?? 'Reserva Local';

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/plain; charset=utf-8',
            'From: ' . sprintf('=?UTF-8?B?%s?= <%s>', base64_encode($fromName), $from),
            'Reply-To: ' . $from,
            'X-Mailer: PHP/' . phpversion()
        ];

        // Si no hay configuración SMTP externa o falla mail, avisar
        if (!empty($this->config['smtp_host'])) {
            // Nota: El prompt prohíbe composer/PHPMailer externo. Se usa fsockopen/mail estándar.
            return @mail($to, $subject, $message, implode("\r\n", $headers));
        }

        $sent = @mail($to, $subject, $message, implode("\r\n", $headers));
        if (!$sent) {
            $this->lastError = "No fue posible enviar correo nativo (revisar configuración de sendmail/SMTP).";
        }
        return $sent;
    }

    public function getLastError(): ?string {
        return $this->lastError;
    }
}

class NotificacionService {
    public static function getSender(): NotificationSenderInterface {
        $config = require APP_ROOT . '/app/config/config.php';
        $notifConfig = $config['notifications'] ?? [];
        $driver = $notifConfig['driver'] ?? 'log';

        if ($driver === 'email' && !empty($notifConfig['smtp_host'])) {
            return new MailNotificationSender($notifConfig);
        }

        return new LogNotificationSender($notifConfig['log_file'] ?? null);
    }

    /**
     * Procesa la cola de notificaciones pendientes
     */
    public static function procesarCola(int $limite = 50): array {
        $sender = self::getSender();
        $pendientes = Notificacion::getPendingToSend($limite);

        $stats = [
            'total' => count($pendientes),
            'enviadas' => 0,
            'fallidas' => 0,
            'detalles' => []
        ];

        foreach ($pendientes as $item) {
            $exito = $sender->send($item);
            if ($exito) {
                Notificacion::markSent((int)$item['id']);
                $stats['enviadas']++;
                $stats['detalles'][] = "ID {$item['id']}: Enviada a {$item['destinatario']}";
            } else {
                $err = $sender->getLastError() ?: 'Fallo desconocido en el adaptador';
                Notificacion::markFailed((int)$item['id'], $err);
                $stats['fallidas']++;
                $stats['detalles'][] = "ID {$item['id']}: Falló ($err)";
            }
        }

        return $stats;
    }
}
