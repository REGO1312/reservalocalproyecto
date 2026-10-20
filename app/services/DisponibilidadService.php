<?php
/**
 * Servicio de Cálculo de Disponibilidad de Citas
 */

class DisponibilidadService {
    /**
     * Calcula los intervalos de tiempo disponibles para un servicio y fecha
     */
    public static function obtenerSlots(int $negocioId, int $servicioId, ?int $profesionalId, string $fecha): array {
        $pdo = Database::getConnection();

        // 1. Validar servicio
        $servicio = Servicio::findById($servicioId);
        if (!$servicio || $servicio['negocio_id'] != $negocioId || $servicio['estado'] !== 'activo') {
            return ['error' => 'Servicio no encontrado o inactivo', 'slots' => []];
        }

        $duracion = (int)$servicio['duracion_minutos'];
        if ($duracion <= 0) {
            $duracion = 30;
        }

        // 2. Validar fecha
        $fechaTime = strtotime($fecha);
        $hoyTime = strtotime(date('Y-m-d'));
        if ($fechaTime < $hoyTime) {
            return ['error' => 'No se pueden consultar fechas pasadas', 'slots' => []];
        }

        // Día de la semana (1 = Lunes, 7 = Domingo)
        $diaSemana = (int)date('N', $fechaTime);

        // 3. Horario del Negocio para este día
        $negHorarios = Horario::getNegocioHorarios($negocioId);
        if (!isset($negHorarios[$diaSemana]) || empty($negHorarios[$diaSemana]['es_laborable'])) {
            return [
                'motivo' => 'El negocio no abre los ' . day_name_es($diaSemana) . 's',
                'slots' => []
            ];
        }

        $negApertura = $negHorarios[$diaSemana]['hora_apertura'];
        $negCierre = $negHorarios[$diaSemana]['hora_cierre'];

        // 4. Excepciones generales del negocio para esta fecha
        $excepcionesNegocio = Horario::getExcepciones($negocioId, $fecha, null);
        foreach ($excepcionesNegocio as $ex) {
            if ($ex['profesional_id'] === null && $ex['tipo'] === 'cierre_total') {
                return [
                    'motivo' => 'Negocio cerrado por excepción: ' . ($ex['motivo'] ?: 'Día no laborable'),
                    'slots' => []
                ];
            }
        }

        // 5. Profesionales elegibles que presten el servicio
        $candidatos = [];
        if ($profesionalId && $profesionalId > 0) {
            $p = Profesional::findById($profesionalId);
            if ($p && $p['negocio_id'] == $negocioId && $p['estado'] === 'activo') {
                if (in_array($servicioId, $p['servicios'])) {
                    $candidatos[] = $p;
                }
            }
        } else {
            $candidatos = Profesional::findByServicio($servicioId, true);
            // Filtrar solo los que pertenezcan a este negocio
            $candidatos = array_filter($candidatos, fn($prof) => $prof['negocio_id'] == $negocioId);
        }

        if (empty($candidatos)) {
            return [
                'motivo' => 'No hay profesionales disponibles para este servicio',
                'slots' => []
            ];
        }

        // 6. Para cada profesional, evaluar sus bloques libres
        $todosLosSlots = [];
        $esHoy = ($fecha === date('Y-m-d'));
        $ahoraTime = time();

        foreach ($candidatos as $prof) {
            $pId = (int)$prof['id'];

            // Horario del profesional para este día
            $pHorarios = Horario::getProfesionalHorarios($pId);
            if (!isset($pHorarios[$diaSemana]) || empty($pHorarios[$diaSemana]['es_laborable'])) {
                continue; // No labora este día
            }

            // Excepciones del profesional
            $excepcionesProf = Horario::getExcepciones($negocioId, $fecha, $pId);
            $profCerrado = false;
            foreach ($excepcionesProf as $ex) {
                if ($ex['profesional_id'] == $pId && $ex['tipo'] === 'cierre_total') {
                    $profCerrado = true;
                    break;
                }
            }
            if ($profCerrado) {
                continue;
            }

            // Rango de horas efectivas
            $horaInicioEfectiva = max($negApertura, $pHorarios[$diaSemana]['hora_inicio']);
            $horaFinEfectiva = min($negCierre, $pHorarios[$diaSemana]['hora_fin']);

            $descansoIni = $pHorarios[$diaSemana]['descanso_inicio'];
            $descansoFin = $pHorarios[$diaSemana]['descanso_fin'];

            // Reservas existentes del profesional en esta fecha (que no estén canceladas)
            $stmtRes = $pdo->prepare("
                SELECT hora_inicio, hora_fin 
                FROM reservas 
                WHERE profesional_id = ? 
                  AND fecha = ? 
                  AND estado != 'cancelada'
                ORDER BY hora_inicio ASC
            ");
            $stmtRes->execute([$pId, $fecha]);
            $reservasExistentes = $stmtRes->fetchAll();

            // Intervalo de cuadrícula en minutos (ej. 30 minutos o 15 minutos)
            $stepMinutes = 30;

            $cursor = strtotime("$fecha $horaInicioEfectiva");
            $finJornada = strtotime("$fecha $horaFinEfectiva");

            while (($cursor + ($duracion * 60)) <= $finJornada) {
                $slotInicioStr = date('H:i:s', $cursor);
                $slotFinStr = date('H:i:s', $cursor + ($duracion * 60));

                // Si es hoy, no permitir slots en el pasado (con margen de 15 minutos)
                if ($esHoy && ($cursor <= ($ahoraTime + 900))) {
                    $cursor += ($stepMinutes * 60);
                    continue;
                }

                // Verificar descanso del profesional
                $solapaDescanso = false;
                if ($descansoIni && $descansoFin) {
                    if ($slotInicioStr < $descansoFin && $slotFinStr > $descansoIni) {
                        $solapaDescanso = true;
                    }
                }

                if ($solapaDescanso) {
                    $cursor += ($stepMinutes * 60);
                    continue;
                }

                // Verificar colisión con reservas existentes
                $solapaReserva = false;
                foreach ($reservasExistentes as $res) {
                    if ($res['hora_inicio'] < $slotFinStr && $res['hora_fin'] > $slotInicioStr) {
                        $solapaReserva = true;
                        break;
                    }
                }

                if (!$solapaReserva) {
                    $timeKey = substr($slotInicioStr, 0, 5);
                    if (!isset($todosLosSlots[$timeKey])) {
                        $todosLosSlots[$timeKey] = [
                            'hora_inicio' => $slotInicioStr,
                            'hora_inicio_formato' => substr($slotInicioStr, 0, 5),
                            'hora_fin' => $slotFinStr,
                            'hora_fin_formato' => substr($slotFinStr, 0, 5),
                            'profesionales' => []
                        ];
                    }
                    $todosLosSlots[$timeKey]['profesionales'][] = [
                        'id' => $prof['id'],
                        'nombre' => $prof['nombre'],
                        'especialidad' => $prof['especialidad']
                    ];
                }

                $cursor += ($stepMinutes * 60);
            }
        }

        // Ordenar cronológicamente
        ksort($todosLosSlots);

        return [
            'negocio_id' => $negocioId,
            'servicio' => [
                'id' => $servicio['id'],
                'nombre' => $servicio['nombre'],
                'precio' => (float)$servicio['precio'],
                'duracion_minutos' => $duracion
            ],
            'fecha' => $fecha,
            'slots' => array_values($todosLosSlots)
        ];
    }
}
