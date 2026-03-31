<?php

namespace Controllers;

use Model\RegistrosRadiacion;
use Exception;
date_default_timezone_set('America/Lima');
ini_set('date.timezone', 'America/Lima');
class ApiController {

    // ========================================
    // 🛡️ RATE LIMITING CONFIGURATION
    // ========================================
    // Al inicio de tu script (ApiController.php)

    private static $rateLimits = [
        'default' => ['requests' => 60, 'window' => 60],     // 60 req/min
        'heavy' => ['requests' => 10, 'window' => 60],       // 10 req/min para queries pesadas
        'light' => ['requests' => 120, 'window' => 60],      // 120 req/min para datos básicos
    ];

    /**
     * 🛡️ Verificar rate limiting por IP y endpoint
     */
    private static function verificarRateLimit($tipo = 'default') {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ahora = time();
        $limite = self::$rateLimits[$tipo]['requests'];
        $ventana = self::$rateLimits[$tipo]['window'];
        
        // Limpiar datos antiguos primero
        self::limpiarRateLimitAntiguo();
        
        // Inicializar estructura de sesión
        if (!isset($_SESSION['api_attempts'])) {
            $_SESSION['api_attempts'] = [];
        }
        
        if (!isset($_SESSION['api_attempts'][$ip])) {
            $_SESSION['api_attempts'][$ip] = [];
        }
        
        if (!isset($_SESSION['api_attempts'][$ip][$tipo])) {
            $_SESSION['api_attempts'][$ip][$tipo] = [];
        }
        
        // Limpiar intentos fuera de la ventana
        $_SESSION['api_attempts'][$ip][$tipo] = array_filter(
            $_SESSION['api_attempts'][$ip][$tipo],
            function($tiempo) use ($ahora, $ventana) {
                return ($ahora - $tiempo) < $ventana;
            }
        );
        
        // Verificar límite
        if (count($_SESSION['api_attempts'][$ip][$tipo]) >= $limite) {
            return false;
        }
        
        // Registrar intento actual
        $_SESSION['api_attempts'][$ip][$tipo][] = $ahora;
        
        return true;
    }

    /**
     * 🧹 Limpiar rate limiting antiguo
     */
    private static function limpiarRateLimitAntiguo() {
        if (!isset($_SESSION['api_attempts'])) return;
        
        $ahora = time();
        $ventanaMaxima = max(array_column(self::$rateLimits, 'window'));
        
        foreach ($_SESSION['api_attempts'] as $ip => $tipos) {
            foreach ($tipos as $tipo => $intentos) {
                $_SESSION['api_attempts'][$ip][$tipo] = array_filter(
                    $intentos,
                    function($tiempo) use ($ahora, $ventanaMaxima) {
                        return ($ahora - $tiempo) < $ventanaMaxima;
                    }
                );
                
                // Eliminar tipos vacíos
                if (empty($_SESSION['api_attempts'][$ip][$tipo])) {
                    unset($_SESSION['api_attempts'][$ip][$tipo]);
                }
            }
            
            // Eliminar IPs vacías
            if (empty($_SESSION['api_attempts'][$ip])) {
                unset($_SESSION['api_attempts'][$ip]);
            }
        }
    }

    /**
     * 🚫 Responder con error de rate limiting
     */
    private static function responderRateLimitExcedido($router, $tipo = 'default') {
        $ventana = self::$rateLimits[$tipo]['window'];
        
        // Headers de rate limiting
        header('X-RateLimit-Limit: ' . self::$rateLimits[$tipo]['requests']);
        header('X-RateLimit-Window: ' . $ventana);
        header('X-RateLimit-Remaining: 0');
        header('Retry-After: ' . $ventana);
        
        // Log del rate limit excedido
        error_log("Rate limit excedido - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . 
                 " - Tipo: {$tipo} - User-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
        
        $router->json([
            'success' => false,
            'message' => 'Demasiadas solicitudes. Inténtalo más tarde.',
            'error_code' => 'RATE_LIMIT_EXCEEDED',
            'retry_after' => $ventana
        ], 429);
        
        return;
    }

    /**
     * 🔒 Validar request básico (tamaño, headers, etc.)
     */
    private static function validarRequest() {
        // Verificar tamaño de request
        $maxSize = 1024 * 1024; // 1MB
        $contentLength = $_SERVER['CONTENT_LENGTH'] ?? 0;
        
        if ($contentLength > $maxSize) {
            return ['valid' => false, 'message' => 'Request demasiado grande'];
        }
        
        // Verificar User-Agent (básico anti-bot)
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (empty($userAgent) || strlen($userAgent) < 10) {
            return ['valid' => false, 'message' => 'User-Agent inválido'];
        }
        
        // Verificar método HTTP
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        $allowedMethods = ['GET', 'POST', 'OPTIONS'];
        if (!in_array($method, $allowedMethods)) {
            return ['valid' => false, 'message' => 'Método no permitido'];
        }
        
        return ['valid' => true];
    }

    /**
     * 🛡️ Wrapper para endpoints con protecciones
     */
    private static function ejecutarConProteccion($router, $callback, $rateLimit = 'default') {
        try {
            // Validar request
            $validacion = self::validarRequest();
            if (!$validacion['valid']) {
                $router->json([
                    'success' => false,
                    'message' => $validacion['message']
                ], 400);
                return;
            }
            
            // Verificar rate limiting
            if (!self::verificarRateLimit($rateLimit)) {
                self::responderRateLimitExcedido($router, $rateLimit);
                return;
            }
            
            // Headers de seguridad
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: DENY');
            header('X-XSS-Protection: 1; mode=block');
            
            // Ejecutar callback
            $callback();
            
        } catch (Exception $e) {
            error_log("Error en API protegida: " . $e->getMessage());
            
            $router->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $_ENV['APP_DEBUG'] === 'true' ? $e->getMessage() : 'Error procesando solicitud'
            ], 500);
        }
    }

    // ========================================
    // 📍 ESTACIONES (Rate Limit: light)
    // ========================================

    /**
     * Obtener todas las estaciones activas con información básica
     * GET /api/estaciones
     */
    public function estaciones($router) {
        self::ejecutarConProteccion($router, function() use ($router) {
            RegistrosRadiacion::useConnection('main');
            
            $estaciones = RegistrosRadiacion::obtenerEstaciones();
            
            if (empty($estaciones)) {
                $router->json([
                    'success' => false,
                    'message' => 'No se encontraron estaciones activas',
                    'data' => []
                ], 404);
                return;
            }

            $router->json([
                'success' => true,
                'message' => 'Estaciones obtenidas correctamente',
                'data' => $estaciones,
                'total' => count($estaciones)
            ]);
        }, 'light');
    }

    /**
     * Obtener coordenadas de estaciones para el mapa
     * GET /api/estaciones/coordenadas
     */
    public function coordenadasEstaciones($router) {
        self::ejecutarConProteccion($router, function() use ($router) {
            RegistrosRadiacion::useConnection('main');
            
            $coordenadas = RegistrosRadiacion::obtenerCoordenadasEstaciones();
            
            $router->json([
                'success' => true,
                'message' => 'Coordenadas obtenidas correctamente',
                'data' => $coordenadas
            ]);
        }, 'light');
    }

    // ========================================
    // 📊 REGISTROS UV (Rate Limit: default)
    // ========================================

    /**



 */
    public function registrosDia($router, $estacionId, $fecha = null) {
        self::ejecutarConProteccion($router, function() use ($router, $estacionId, $fecha) {
            // Validar estación ID
            if (!is_numeric($estacionId) || $estacionId <= 0 || $estacionId > 1000) {
                $router->json([
                    'success' => false,
                    'message' => 'ID de estación inválido'
                ], 400);
                return;
            }

            RegistrosRadiacion::useConnection('main');

            // Validar que la estación existe
            if (!RegistrosRadiacion::validarEstacion($estacionId)) {
                $router->json([
                    'success' => false,
                    'message' => 'Estación no encontrada o inactiva'
                ], 404);
                return;
            }

            // Validar fecha si se proporciona
            if ($fecha) {
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !strtotime($fecha)) {
                    $router->json([
                        'success' => false,
                        'message' => 'Formato de fecha inválido. Use YYYY-MM-DD'
                    ], 400);
                    return;
                }
                
                // Limitar rango de fechas (máximo 1 año atrás)
                $fechaMinima = date('Y-m-d', strtotime('-1 year'));
                $fechaMaxima = date('Y-m-d', strtotime('+1 day'));
                
                if ($fecha < $fechaMinima || $fecha > $fechaMaxima) {
                    $router->json([
                        'success' => false,
                        'message' => 'Fecha fuera del rango permitido'
                    ], 400);
                    return;
                }
            }
            
            // ✅ DEJAR QUE PHP MANEJE TODA LA LÓGICA DE FECHAS
            // NO asignar fecha aquí, dejar que el modelo decida
            
            $datosCompletos = RegistrosRadiacion::obtenerRegistrosDia($estacionId, $fecha);
            $registros = $datosCompletos['registros'] ?? [];
            $metadatos = $datosCompletos['metadatos_fecha'] ?? [];
            
            // Obtener información de la estación
            $infoEstacion = RegistrosRadiacion::obtenerInfoEstacion($estacionId);

            if (empty($registros)) {
                $router->json([
                    'success' => true,
                    'message' => 'No hay registros para la fecha especificada',
                    'data' => [
                        'estacion' => $infoEstacion,
                        // ✅ USAR METADATOS DE FECHA DEL MODELO
                        'fecha_solicitada' => $metadatos['fecha_solicitada'] ?? ($fecha ?? date('Y-m-d')),
                        'fecha_usada' => $metadatos['fecha_usada'],
                        'fecha_titulo' => $metadatos['fecha_titulo'] ?? 'Sin datos disponibles',
                        'usando_fallback' => $metadatos['usando_fallback'] ?? false,
                        'registros' => [],
                        'grafica' => [
                            'labels' => [],
                            'datasets' => [],
                            'metadatos_fecha' => $metadatos // ✅ INCLUIR METADATOS EN GRÁFICA
                        ],
                        'total_registros' => 0,
                        'rango_horario' => null
                    ]
                ]);
                return;
            }

            // ✅ USAR NUEVO MÉTODO CON METADATOS
            $datosGrafica = RegistrosRadiacion::formatearParaGraficaConMetadatos($datosCompletos);
            
            // Formatear registros para JSON
            $registrosFormateados = RegistrosRadiacion::formatearArrayParaJson($registros);

            $router->json([
                'success' => true,
                'message' => 'Registros obtenidos correctamente',
                'data' => [
                    'estacion' => $infoEstacion,
                    // ✅ TODAS LAS FECHAS VIENEN DEL BACKEND
                    'fecha_solicitada' => $metadatos['fecha_solicitada'],
                    'fecha_usada' => $metadatos['fecha_usada'],
                    'fecha_titulo' => $metadatos['fecha_titulo'], // ✅ FECHA YA FORMATEADA PARA TÍTULO
                    'usando_fallback' => $metadatos['usando_fallback'],
                    'registros' => $registrosFormateados,
                    'grafica' => $datosGrafica,
                    'total_registros' => count($registros),
                    'rango_horario' => $metadatos['rango_horario'] ?? '08:00 - 17:00',
                    // ✅ INFORMACIÓN ADICIONAL PARA FRONTEND
                    'info_fecha' => [
                        'es_hoy' => $metadatos['fecha_usada'] === date('Y-m-d'),
                        'diferencia_dias' => $metadatos['usando_fallback'] ? 
                            (strtotime($metadatos['fecha_solicitada']) - strtotime($metadatos['fecha_usada'])) / (60*60*24) : 0,
                        'mensaje_usuario' => $metadatos['usando_fallback'] ? 
                            "Mostrando datos del " . $metadatos['fecha_titulo'] . " (último día disponible)" :
                            "Datos del " . $metadatos['fecha_titulo']
                    ]
                ]
            ]);

        }, 'default');
    }
// TEMPORAL: Debug directo en respuesta

    /**
     * Obtener último registro de una estación específica
     * GET /api/estacion/{estacion_id}/ultimo
     */
    public function ultimoRegistro($router, $estacionId) {
        self::ejecutarConProteccion($router, function() use ($router, $estacionId) {
            if (!is_numeric($estacionId) || $estacionId <= 0 || $estacionId > 1000) {
                $router->json([
                    'success' => false,
                    'message' => 'ID de estación inválido'
                ], 400);
                return;
            }

            RegistrosRadiacion::useConnection('main');

            if (!RegistrosRadiacion::validarEstacion($estacionId)) {
                $router->json([
                    'success' => false,
                    'message' => 'Estación no encontrada o inactiva'
                ], 404);
                return;
            }

            $ultimoRegistro = RegistrosRadiacion::obtenerUltimoRegistroPorEstacion($estacionId);

            if (!$ultimoRegistro) {
                $router->json([
                    'success' => false,
                    'message' => 'No hay registros disponibles para esta estación'
                ], 404);
                return;
            }

            $router->json([
                'success' => true,
                'message' => 'Último registro obtenido correctamente',
                'data' => [
                    'estacion_id' => (int)$ultimoRegistro['estacion_id'],
                    'estacion_nombre' => $ultimoRegistro['estacion_nombre'],
                    'coordenadas' => [
                        'latitud' => (float)$ultimoRegistro['latitud'],
                        'longitud' => (float)$ultimoRegistro['longitud']
                    ],
                    'ubicacion' => $ultimoRegistro['ubicacion'],
                    'ultimo_registro' => [
                        'fecha' => $ultimoRegistro['fecha'],
                        'hora' => $ultimoRegistro['hora'],
                        'uv_index' => (float)$ultimoRegistro['uv_index'],
                        'temperatura' => $ultimoRegistro['temperatura'] ? (float)$ultimoRegistro['temperatura'] : null,
                        'humedad' => $ultimoRegistro['humedad'] ? (float)$ultimoRegistro['humedad'] : null,
                        'voltaje' => $ultimoRegistro['voltaje'] ? (float)$ultimoRegistro['voltaje'] : null,
                        'fecha_completa' => $ultimoRegistro['fecha_completa']
                    ]
                ]
            ]);
        }, 'default');
    }

    /**
     * Obtener últimos registros de todas las estaciones activas
     * GET /api/registros/ultimos
     */
    public function ultimosRegistros($router) {
        self::ejecutarConProteccion($router, function() use ($router) {
            RegistrosRadiacion::useConnection('main');
            
            $ultimosRegistros = RegistrosRadiacion::obtenerUltimosRegistros();

            if (empty($ultimosRegistros)) {
                $router->json([
                    'success' => false,
                    'message' => 'No hay registros recientes disponibles'
                ], 404);
                return;
            }

            // Formatear datos para respuesta
            $datosFormateados = [];
            foreach ($ultimosRegistros as $registro) {
                $datosFormateados[] = [
                    'estacion_id' => (int)$registro['estacion_id'],
                    'estacion_nombre' => $registro['estacion_nombre'],
                    'coordenadas' => [
                        'latitud' => (float)$registro['latitud'],
                        'longitud' => (float)$registro['longitud']
                    ],
                    'ubicacion' => $registro['ubicacion'],
                    'registro' => [
                        'fecha' => $registro['fecha'],
                        'hora' => $registro['hora'],
                        'uv_index' => (float)$registro['uv_index'],
                        'temperatura' => $registro['temperatura'] ? (float)$registro['temperatura'] : null,
                        'humedad' => $registro['humedad'] ? (float)$registro['humedad'] : null,
                        'voltaje' => $registro['voltaje'] ? (float)$registro['voltaje'] : null,
                        'fecha_completa' => $registro['fecha_completa']
                    ]
                ];
            }

            $router->json([
                'success' => true,
                'message' => 'Últimos registros obtenidos correctamente',
                'data' => $datosFormateados,
                'total_estaciones' => count($datosFormateados),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }, 'light');
    }

    // ========================================
    // 📈 ESTADO Y ESTADÍSTICAS (Rate Limit: light)
    // ========================================

    /**
     * Verificar estado del sistema
     * GET /api/estado
     */
    public function estado($router) {
        self::ejecutarConProteccion($router, function() use ($router) {
            RegistrosRadiacion::useConnection('main');
            
            $hayDatosRecientes = RegistrosRadiacion::hayDatosRecientes(30);
            $totalEstaciones = count(RegistrosRadiacion::obtenerEstaciones());

            $router->json([
                'success' => true,
                'data' => [
                    'registrando' => $hayDatosRecientes,
                    'estado' => $hayDatosRecientes ? 'REGISTRANDO' : 'SIN DATOS RECIENTES',
                    'total_estaciones_activas' => $totalEstaciones,
                    'ultima_verificacion' => date('Y-m-d H:i:s'),
                    'minutos_verificacion' => 30
                ]
            ]);
        }, 'light');
    }

    /**
     * Obtener estadísticas del día actual
     * GET /api/estadisticas
     * GET /api/estadisticas/{fecha}
     */
    public function estadisticas($router, $fecha = null) {
        self::ejecutarConProteccion($router, function() use ($router, $fecha) {
            // Validar fecha si se proporciona
            if ($fecha) {
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !strtotime($fecha)) {
                    $router->json([
                        'success' => false,
                        'message' => 'Formato de fecha inválido. Use YYYY-MM-DD'
                    ], 400);
                    return;
                }
                
                // Limitar rango
                $fechaMinima = date('Y-m-d', strtotime('-6 months'));
                if ($fecha < $fechaMinima) {
                    $router->json([
                        'success' => false,
                        'message' => 'Fecha muy antigua. Máximo 6 meses atrás.'
                    ], 400);
                    return;
                }
            }

            RegistrosRadiacion::useConnection('main');
            
            $fecha = $fecha ?? date('Y-m-d');
            $estadisticas = RegistrosRadiacion::obtenerEstadisticasDia($fecha);

            if (empty($estadisticas)) {
                $router->json([
                    'success' => true,
                    'message' => 'No hay datos disponibles para la fecha especificada',
                    'data' => [
                        'fecha' => $fecha,
                        'estaciones' => []
                    ]
                ]);
                return;
            }

            // Formatear estadísticas
            $datosFormateados = [];
            foreach ($estadisticas as $est) {
                $datosFormateados[] = [
                    'estacion_id' => (int)$est['estacion_id'],
                    'estacion_nombre' => $est['estacion_nombre'],
                    'total_registros' => (int)$est['total_registros'],
                    'uv' => [
                        'promedio' => $est['uv_promedio'] ? round((float)$est['uv_promedio'], 2) : null,
                        'maximo' => $est['uv_maximo'] ? (float)$est['uv_maximo'] : null,
                        'minimo' => $est['uv_minimo'] ? (float)$est['uv_minimo'] : null
                    ],
                    'temperatura' => [
                        'promedio' => $est['temp_promedio'] ? round((float)$est['temp_promedio'], 1) : null,
                        'maxima' => $est['temp_maxima'] ? (float)$est['temp_maxima'] : null,
                        'minima' => $est['temp_minima'] ? (float)$est['temp_minima'] : null
                    ],
                    'humedad_promedio' => $est['humedad_promedio'] ? round((float)$est['humedad_promedio'], 1) : null,
                    'ultimo_registro' => $est['ultimo_registro']
                ];
            }

            $router->json([
                'success' => true,
                'message' => 'Estadísticas obtenidas correctamente',
                'data' => [
                    'fecha' => $fecha,
                    'estaciones' => $datosFormateados,
                    'total_estaciones' => count($datosFormateados)
                ]
            ]);
        }, 'default');
    }

    // ========================================
    // 🔍 CONSULTAS PESADAS (Rate Limit: heavy)
    // ========================================

    /**
     * Obtener registros por rango personalizado
     * POST /api/registros/rango
     * Body: {"estacion_id": 1, "fecha_inicio": "2025-06-20", "fecha_fin": "2025-06-25", "hora_inicio": "08:00", "hora_fin": "17:00"}
     */
    public function registrosPorRango($router) {
        self::ejecutarConProteccion($router, function() use ($router) {
            // Verificar CSRF para POST
            if (!$router->validateCSRF()) {
                $router->json([
                    'success' => false,
                    'message' => 'Token CSRF inválido'
                ], 403);
                return;
            }
            
            // Obtener datos POST
            $datos = $router->getPost();

            // Validar datos requeridos
            if (!isset($datos['estacion_id']) || !isset($datos['fecha_inicio']) || !isset($datos['fecha_fin'])) {
                $router->json([
                    'success' => false,
                    'message' => 'Faltan parámetros requeridos: estacion_id, fecha_inicio, fecha_fin'
                ], 400);
                return;
            }

            $estacionId = $datos['estacion_id'];
            $fechaInicio = $datos['fecha_inicio'];
            $fechaFin = $datos['fecha_fin'];
            $horaInicio = $datos['hora_inicio'] ?? '00:00:00';
            $horaFin = $datos['hora_fin'] ?? '23:59:59';

            // Validaciones
            if (!is_numeric($estacionId) || $estacionId <= 0 || $estacionId > 1000) {
                $router->json([
                    'success' => false,
                    'message' => 'ID de estación inválido'
                ], 400);
                return;
            }

            if (!strtotime($fechaInicio) || !strtotime($fechaFin)) {
                $router->json([
                    'success' => false,
                    'message' => 'Formato de fechas inválido. Use YYYY-MM-DD'
                ], 400);
                return;
            }
            
            // Limitar rango máximo a 31 días
            $diferenciaDias = (strtotime($fechaFin) - strtotime($fechaInicio)) / (60 * 60 * 24);
            if ($diferenciaDias > 31) {
                $router->json([
                    'success' => false,
                    'message' => 'Rango máximo permitido: 31 días'
                ], 400);
                return;
            }

            RegistrosRadiacion::useConnection('main');

            if (!RegistrosRadiacion::validarEstacion($estacionId)) {
                $router->json([
                    'success' => false,
                    'message' => 'Estación no encontrada o inactiva'
                ], 404);
                return;
            }

            $registros = RegistrosRadiacion::obtenerRegistrosPorRango($estacionId, $fechaInicio, $fechaFin, $horaInicio, $horaFin);
            $infoEstacion = RegistrosRadiacion::obtenerInfoEstacion($estacionId);

            $registrosFormateados = RegistrosRadiacion::formatearArrayParaJson($registros);
            $datosGrafica = RegistrosRadiacion::formatearParaGrafica($registros);

            $router->json([
                'success' => true,
                'message' => 'Registros por rango obtenidos correctamente',
                'data' => [
                    'estacion' => $infoEstacion,
                    'parametros' => [
                        'fecha_inicio' => $fechaInicio,
                        'fecha_fin' => $fechaFin,
                        'hora_inicio' => $horaInicio,
                        'hora_fin' => $horaFin
                    ],
                    'registros' => $registrosFormateados,
                    'grafica' => $datosGrafica,
                    'total_registros' => count($registros)
                ]
            ]);
        }, 'heavy');
    }

    // ========================================
    // 🧪 UTILIDADES (Rate Limit: light)
    // ========================================

    /**
     * Endpoint de prueba para verificar que la API funciona
     * GET /api/test
     */
    public function test($router) {
        self::ejecutarConProteccion($router, function() use ($router) {
            $router->json([
                'success' => true,
                'message' => 'API funcionando correctamente',
                'timestamp' => date('Y-m-d H:i:s'),
                'rate_limits' => [
                    'light' => self::$rateLimits['light'],
                    'default' => self::$rateLimits['default'],
                    'heavy' => self::$rateLimits['heavy']
                ],
                'endpoints' => [
                    'GET /api/estaciones' => 'Obtener todas las estaciones',
                    'GET /api/estaciones/coordenadas' => 'Coordenadas para mapa',
                    'GET /api/registros/dia/{estacion_id}' => 'Registros del día',
                    'GET /api/estacion/{estacion_id}/ultimo' => 'Último registro de estación',
                    'GET /api/registros/ultimos' => 'Últimos registros de todas las estaciones',
                    'GET /api/estado' => 'Estado del sistema',
                    'GET /api/estadisticas' => 'Estadísticas del día',
                    'POST /api/registros/rango' => 'Registros por rango personalizado'
                ]
            ]);
        }, 'light');
    }

    /**
     * Obtener dashboard completo (estaciones + últimos registros + estado)
     * GET /api/dashboard
     */
    public function dashboard($router) {
        self::ejecutarConProteccion($router, function() use ($router) {
            RegistrosRadiacion::useConnection('main');
            
            // Obtener todos los datos de una vez
            $estaciones = RegistrosRadiacion::obtenerEstaciones();
            $ultimosRegistros = RegistrosRadiacion::obtenerUltimosRegistros();
            $hayDatosRecientes = RegistrosRadiacion::hayDatosRecientes(30);
            
            // Formatear últimos registros
            $datosFormateados = [];
            foreach ($ultimosRegistros as $registro) {
                $datosFormateados[] = [
                    'estacion_id' => (int)$registro['estacion_id'],
                    'estacion_nombre' => $registro['estacion_nombre'],
                    'coordenadas' => [
                        'latitud' => (float)$registro['latitud'],
                        'longitud' => (float)$registro['longitud']
                    ],
                    'ubicacion' => $registro['ubicacion'],
                    'registro' => [
                        'fecha' => $registro['fecha'],
                        'hora' => $registro['hora'],
                        'uv_index' => (float)$registro['uv_index'],
                        'temperatura' => $registro['temperatura'] ? (float)$registro['temperatura'] : null,
                        'humedad' => $registro['humedad'] ? (float)$registro['humedad'] : null,
                        'voltaje' => $registro['voltaje'] ? (float)$registro['voltaje'] : null,
                        'fecha_completa' => $registro['fecha_completa']
                    ]
                ];
            }

            $router->json([
                'success' => true,
                'message' => 'Dashboard obtenido correctamente',
                'data' => [
                    'estaciones' => $estaciones,
                    'ultimos_registros' => $datosFormateados,
                    'estado' => [
                        'registrando' => $hayDatosRecientes,
                        'estado_texto' => $hayDatosRecientes ? 'REGISTRANDO' : 'SIN DATOS RECIENTES',
                        'total_estaciones_activas' => count($estaciones),
                        'ultima_verificacion' => date('Y-m-d H:i:s')
                    ]
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }, 'default');
    }
}