<?php
/**
 * Servicio de Creación y Gestión de Reservas con Control de Concurrencia
 */

class ReservaService {
    /**
     * Crear una reserva con transacción y SELECT ... FOR UPDATE para evitar solapamientos simultáneos
     */
    public static function crearReserva(array $params): array {
        $pdo = Database::getConnection();

        $negocioId = (int)($params['negocio_id'] ?? 0);
        $servicioId = (int)($params['servicio_id'] ?? 0);
        $profesionalId = (int)($params['profesional_id'] ?? 0);
        $clienteId = (int)($params['cliente_id'] ?? 0);
        $fecha = trim($params['fecha'] ?? '');
        $horaInicio = trim($params['hora_inicio'] ?? '');
        $notasCliente = trim($params['notas_cliente'] ?? '');

        if (!$negocioId || !$servicioId || !$profesionalId || !$clienteId || empty($fecha) || empty($horaInicio)) {
            throw new InvalidArgumentException("Faltan datos obligatorios para la reserva.");
        }

        // Formato seguro de hora
        if (strlen($horaInicio) === 5) {
            $horaInicio .= ':00';
        }

        // Validar negocio activo
        $negocio = Negocio::findById($negocioId);
        if (!$negocio || $negocio['estado'] !== 'activo') {
            throw new Exception("El negocio no está activo.");
        }

        // Obtener servicio para congelar precio y duración actuales
        $servicio = Servicio::findById($servicioId);
        if (!$servicio || $servicio['negocio_id'] != $negocioId || $servicio['estado'] !== 'activo') {
            throw new Exception("El servicio seleccionado no está disponible.");
        }

        $duracionMinutos = (int)$servicio['duracion_minutos'];
        $precioAcordado = (float)$servicio['precio'];

        // Calcular hora_fin
        $inicioTs = strtotime("$fecha $horaInicio");
        if (!$inicioTs) {
            throw new InvalidArgumentException("Fecha u hora con formato no válido.");
        }
        $finTs = $inicioTs + ($duracionMinutos * 60);
        $horaFin = date('H:i:s', $finTs);

        // Validar que la cita sea a futuro
        if ($inicioTs < time()) {
            throw new Exception("No es posible reservar en una fecha u hora que ya ha transcurrido.");
        }

        // Validar cliente
        $cliente = Usuario::findById($clienteId);
        if (!$cliente || $cliente['estado'] !== 'activo') {
            throw new Exception("Cuenta de cliente no válida.");
        }

        // === INICIO DE TRANSACCIÓN CON SELECT ... FOR UPDATE ===
        $pdo->beginTransaction();
        try {
            // 1. Bloquear la fila del profesional para serializar transacciones concurrentes sobre su agenda
            $lockStmt = $pdo->prepare("
                SELECT id, negocio_id, estado, nombre 
                FROM profesionales 
                WHERE id = ? FOR UPDATE
            ");
            $lockStmt->execute([$profesionalId]);
            $profesional = $lockStmt->fetch();

            if (!$profesional || $profesional['estado'] !== 'activo' || $profesional['negocio_id'] != $negocioId) {
                throw new Exception("El profesional seleccionado no está disponible.");
            }

            // 2. Comprobar que el profesional ofrece el servicio
            $psStmt = $pdo->prepare("
                SELECT 1 FROM profesional_servicios 
                WHERE profesional_id = ? AND servicio_id = ?
            ");
            $psStmt->execute([$profesionalId, $servicioId]);
            if (!$psStmt->fetchColumn()) {
                throw new Exception("El profesional seleccionado no realiza este servicio.");
            }

            // 3. Comprobar solapamiento exacto:
            // Solapan si inicio_existente < fin_solicitado Y fin_existente > inicio_solicitado. Las canceladas NO bloquean.
            $overlapStmt = $pdo->prepare("
                SELECT id, codigo_reserva, hora_inicio, hora_fin 
                FROM reservas 
                WHERE profesional_id = ? 
                  AND fecha = ? 
                  AND estado != 'cancelada'
                  AND hora_inicio < ? 
                  AND hora_fin > ?
            ");
            $overlapStmt->execute([$profesionalId, $fecha, $horaFin, $horaInicio]);
            $colision = $overlapStmt->fetch();

            if ($colision) {
                throw new Exception("El horario seleccionado ({$horaInicio} a {$horaFin}) ya fue reservado por otro cliente. Por favor elige otro turno.");
            }

            // 4. Comprobar descansos del profesional
            $diaSemana = (int)date('N', $inicioTs);
            $hProfStmt = $pdo->prepare("
                SELECT hora_inicio, hora_fin, descanso_inicio, descanso_fin, es_laborable
                FROM horarios_profesional
                WHERE profesional_id = ? AND dia_semana = ?
            ");
            $hProfStmt->execute([$profesionalId, $diaSemana]);
            $hProf = $hProfStmt->fetch();

            if (!$hProf || empty($hProf['es_laborable'])) {
                throw new Exception("El profesional no labora el día seleccionado.");
            }

            if ($hProf['descanso_inicio'] && $hProf['descanso_fin']) {
                if ($horaInicio < $hProf['descanso_fin'] && $horaFin > $hProf['descanso_inicio']) {
                    throw new Exception("El horario coincide con el periodo de descanso del profesional.");
                }
            }

            // 5. Comprobar excepciones de negocio y profesional
            $exStmt = $pdo->prepare("
                SELECT tipo, motivo 
                FROM excepciones_horario 
                WHERE negocio_id = ? 
                  AND fecha = ? 
                  AND (profesional_id IS NULL OR profesional_id = ?) 
                  AND tipo = 'cierre_total'
            ");
            $exStmt->execute([$negocioId, $fecha, $profesionalId]);
            $excepcion = $exStmt->fetch();
            if ($excepcion) {
                throw new Exception("No es posible reservar en esta fecha: " . ($excepcion['motivo'] ?: 'Cierre programado'));
            }

            // 6. Generar código único de reserva e insertar
            $codigo = Reserva::generateUniqueCode();
            $insStmt = $pdo->prepare("
                INSERT INTO reservas (
                    codigo_reserva, negocio_id, profesional_id, servicio_id, cliente_id,
                    fecha, hora_inicio, hora_fin, precio_acordado, duracion_minutos,
                    estado, notas_cliente
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmada', ?)
            ");
            $insStmt->execute([
                $codigo,
                $negocioId,
                $profesionalId,
                $servicioId,
                $clienteId,
                $fecha,
                $horaInicio,
                $horaFin,
                $precioAcordado,
                $duracionMinutos,
                $notasCliente
            ]);
            $reservaId = (int)$pdo->lastInsertId();

            // 7. Encolar notificación inmediata de confirmación
            Notificacion::create([
                'reserva_id' => $reservaId,
                'usuario_id' => $clienteId,
                'tipo' => 'confirmacion',
                'destinatario' => $cliente['email'],
                'asunto' => "¡Cita Confirmada! - {$negocio['nombre']}",
                'mensaje' => "Hola {$cliente['nombre']}, tu cita de '{$servicio['nombre']}' con {$profesional['nombre']} ha sido confirmada con código $codigo para el $fecha de $horaInicio a $horaFin. Total acordado: $" . number_format($precioAcordado, 2),
                'programada_para' => date('Y-m-d H:i:s')
            ]);

            // 8. Encolar recordatorio para 24 horas antes si hay suficiente tiempo
            $recordatorioTs = $inicioTs - (24 * 3600);
            if ($recordatorioTs > time()) {
                Notificacion::create([
                    'reserva_id' => $reservaId,
                    'usuario_id' => $clienteId,
                    'tipo' => 'recordatorio_24h',
                    'destinatario' => $cliente['email'],
                    'asunto' => "Recordatorio de tu cita mañana en {$negocio['nombre']}",
                    'mensaje' => "Hola {$cliente['nombre']}, te recordamos tu cita de {$servicio['nombre']} con {$profesional['nombre']} mañana a las $horaInicio.",
                    'programada_para' => date('Y-m-d H:i:s', $recordatorioTs)
                ]);
            }

            // Confirmar transacción
            $pdo->commit();

            audit_log($clienteId, 'CREAR_RESERVA', 'reservas', $reservaId, "Reserva $codigo creada para $fecha $horaInicio");

            return [
                'id' => $reservaId,
                'codigo_reserva' => $codigo,
                'negocio_nombre' => $negocio['nombre'],
                'servicio_nombre' => $servicio['nombre'],
                'profesional_nombre' => $profesional['nombre'],
                'fecha' => $fecha,
                'hora_inicio' => $horaInicio,
                'hora_fin' => $horaFin,
                'precio_acordado' => $precioAcordado,
                'duracion_minutos' => $duracionMinutos,
                'estado' => 'confirmada'
            ];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Cancelar una reserva validando permisos estrictos del usuario
     */
    public static function cancelar(int $reservaId, int $usuarioId, ?string $motivo = null): bool {
        $reserva = Reserva::findById($reservaId);
        if (!$reserva) {
            throw new Exception("Reserva no encontrada.");
        }

        if ($reserva['estado'] === 'cancelada') {
            throw new Exception("Esta reserva ya se encuentra cancelada.");
        }

        // Permisos: Cliente dueño de la reserva, Propietario del negocio, Profesional asignado, o Admin global
        $isClient = ($reserva['cliente_id'] == $usuarioId);
        $isProf = false;
        $prof = Profesional::findByUserId($usuarioId);
        foreach ($prof as $pr) {
            if ($pr['id'] == $reserva['profesional_id']) {
                $isProf = true;
                break;
            }
        }
        $isOwner = Negocio::isOwner($usuarioId, (int)$reserva['negocio_id']);
        $isAdmin = has_role('administrador');

        if (!$isClient && !$isProf && !$isOwner && !$isAdmin) {
            throw new Exception("No tienes autorización para cancelar esta reserva.");
        }

        $nota = $motivo ? "Cancelada: $motivo" : "Cancelada por usuario ID $usuarioId";
        return Reserva::updateEstado($reservaId, 'cancelada', $nota, $usuarioId);
    }
}
