<?php

namespace Controllers;

use Model\ChatbotMensaje;
use Model\RegistrosRadiacion;
use Services\ChatbotService;
use Exception;

class ChatbotController {

    // Límites de seguridad
    private const MAX_MENSAJE_LENGTH = 500;
    private const MAX_MENSAJES_POR_SESION = 20;
    private const MAX_TOKENS_DIARIOS = 500;
    private const MAX_REQUESTS_POR_IP = 10; // por minuto
    private const TIMEOUT_RATE_LIMIT = 60; // segundos

    /**
     * Procesar mensaje del usuario
     * POST /api/chatbot/mensaje
     */
    public function mensaje($router) {
        try {
            // 🛡️ Headers de seguridad para API
            $this->configurarHeadersSeguridad();
            
            // 🛡️ Verificar rate limiting por IP
            if (!$this->verificarRateLimitIP()) {
                $router->json([
                    'success' => false,
                    'error' => 'Demasiadas peticiones. Espera un momento.'
                ], 429);
                return;
            }

            // 🛡️ Sanitizar y validar entrada JSON
            $datosEntrada = $this->sanitizarEntradaJSON();
            if (!$datosEntrada) {
                $router->json([
                    'success' => false,
                    'error' => 'Datos de entrada inválidos'
                ], 400);
                return;
            }

            // 🛡️ Extraer y sanitizar parámetros
            $mensaje = $this->sanitizarMensaje($datosEntrada['mensaje'] ?? '');
            $sessionId = $this->sanitizarSessionId($datosEntrada['session_id'] ?? '');

            // Generar session_id si no existe
            if (empty($sessionId)) {
                $sessionId = $this->generarSessionId();
            }

            // 🛡️ Validaciones de negocio
            $validacion = $this->validarParametrosMensaje($mensaje, $sessionId);
            if (!$validacion['valido']) {
                $router->json([
                    'success' => false,
                    'error' => $validacion['error']
                ], 400);
                return;
            }

            // 🛡️ Verificar límites de uso
            if (!$this->verificarLimitesUso($sessionId)) {
                $router->json([
                    'success' => false,
                    'error' => 'Límite de mensajes alcanzado'
                ], 429);
                return;
            }

            // ✅ Procesar mensaje
            $respuesta = $this->procesarMensaje($mensaje, $sessionId);

            // ✅ Guardar conversación (el modelo ya sanitiza)
            $mensajeGuardado = ChatbotMensaje::crearConversacion(
                $sessionId,
                $mensaje,
                $respuesta['mensaje'],
                $respuesta['tipo'],
                $respuesta['tokens_usados'] ?? 0
            );

            if (!$mensajeGuardado) {
                error_log("Error guardando conversación para session: {$sessionId}");
            }

            // ✅ Respuesta sanitizada
            $router->json([
                'success' => true,
                'data' => [
                    'respuesta' => $this->sanitizarRespuesta($respuesta['mensaje']),
                    'tipo' => $this->sanitizarTipo($respuesta['tipo']),
                    'session_id' => $sessionId,
                    'tiempo_respuesta' => (int) ($respuesta['tiempo_respuesta'] ?? 0)
                ]
            ]);

        } catch (Exception $e) {
            error_log("Error en ChatbotController::mensaje - " . $e->getMessage());
            
            $router->json([
                'success' => false,
                'error' => 'Error procesando mensaje'
            ], 500);
        }
    }

    /**
     * Obtener historial de conversación
     * GET /api/chatbot/historial/{session_id}
     */
    public function historial($router, $sessionId = null) {
        try {
            // 🛡️ Headers de seguridad
            $this->configurarHeadersSeguridad();

            // 🛡️ Sanitizar session_id
            $sessionId = $this->sanitizarSessionId($sessionId);
            
            if (empty($sessionId)) {
                $router->json([
                    'success' => false,
                    'error' => 'Session ID requerido y válido'
                ], 400);
                return;
            }

            // 🛡️ Verificar rate limiting
            if (!$this->verificarRateLimitIP()) {
                $router->json([
                    'success' => false,
                    'error' => 'Demasiadas peticiones'
                ], 429);
                return;
            }

            // ✅ Obtener historial (ya sanitizado en el modelo)
            $mensajes = ChatbotMensaje::obtenerHistorial($sessionId);

            // ✅ Formatear respuesta
            $historialFormateado = [];
            foreach ($mensajes as $msg) {
                $historialFormateado[] = [
                    'id' => (int) $msg['id'],
                    'usuario' => $msg['mensaje_usuario'], // Ya escapado en modelo
                    'bot' => $msg['respuesta_bot'], // Ya escapado en modelo
                    'tipo' => $msg['tipo_respuesta'],
                    'timestamp' => $msg['timestamp_creado']
                ];
            }

            $router->json([
                'success' => true,
                'data' => [
                    'session_id' => $sessionId,
                    'mensajes' => $historialFormateado,
                    'total' => count($historialFormateado)
                ]
            ]);

        } catch (Exception $e) {
            error_log("Error obteniendo historial: " . $e->getMessage());
            
            $router->json([
                'success' => false,
                'error' => 'Error obteniendo historial'
            ], 500);
        }
    }

    /**
     * Test de conectividad
     * GET /api/chatbot/test
     */
    public function test($router) {
        try {
            // 🛡️ Headers de seguridad
            $this->configurarHeadersSeguridad();

            // Test Gemini (con timeout)
            $testGemini = ChatbotService::testConexion();
            
            $router->json([
                'success' => true,
                'data' => [
                    'chatbot_db' => 'conectado',
                    'gemini_api' => $testGemini['success'] ? 'conectado' : 'error',
                    'tokens_usados_hoy' => ChatbotMensaje::tokensUsadosHoy(),
                    'timestamp' => date('Y-m-d H:i:s'),
                    'version' => '1.0.0'
                ]
            ]);

        } catch (Exception $e) {
            $router->json([
                'success' => false,
                'error' => 'Error en test de conectividad',
                'details' => $_ENV['APP_DEBUG'] === 'true' ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Estadísticas de uso (endpoint adicional para admin)
     * GET /api/chatbot/stats
     */
    public function stats($router) {
        try {
            // 🛡️ Headers de seguridad
            $this->configurarHeadersSeguridad();

            // Nota: En producción agregar autenticación admin
            
            $stats = [
                'tokens_hoy' => ChatbotMensaje::tokensUsadosHoy(),
                'mensajes_hoy' => $this->contarMensajesHoy(),
                'sesiones_activas' => $this->contarSesionesActivas(),
                'uptime' => $this->calcularUptime(),
                'timestamp' => date('Y-m-d H:i:s')
            ];

            $router->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (Exception $e) {
            error_log("Error obteniendo estadísticas: " . $e->getMessage());
            
            $router->json([
                'success' => false,
                'error' => 'Error obteniendo estadísticas'
            ], 500);
        }
    }

    // ========================================
    // 🛡️ MÉTODOS DE SANITIZACIÓN
    // ========================================

    /**
     * Sanitizar entrada JSON
     */
    private function sanitizarEntradaJSON() {
        try {
            $input = file_get_contents('php://input');
            
            // Verificar que no esté vacío
            if (empty(trim($input))) {
                return false;
            }

            // Limitar tamaño del JSON
            if (strlen($input) > 10000) { // 10KB máximo
                return false;
            }

            // Decodificar JSON
            $datos = json_decode($input, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return false;
            }

            // Verificar que sea un array
            if (!is_array($datos)) {
                return false;
            }

            return $datos;

        } catch (Exception $e) {
            error_log("Error sanitizando JSON: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sanitizar mensaje del usuario
     */
    private function sanitizarMensaje($mensaje) {
        if (!is_string($mensaje)) {
            return '';
        }

        // Trim básico
        $mensaje = trim($mensaje);
        
        // Remover caracteres de control
        $mensaje = preg_replace('/[\x00-\x1F\x7F]/', '', $mensaje);
        
        // Remover HTML tags
        $mensaje = strip_tags($mensaje);
        
        // Escapar caracteres especiales
        $mensaje = htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8');
        
        // Normalizar espacios
        $mensaje = preg_replace('/\s+/', ' ', $mensaje);
        
        // Aplicar límite de longitud
        return substr($mensaje, 0, self::MAX_MENSAJE_LENGTH);
    }

    /**
     * Sanitizar Session ID
     */
    private function sanitizarSessionId($sessionId) {
        if (!is_string($sessionId)) {
            return '';
        }

        $sessionId = trim($sessionId);
        
        // Solo permitir caracteres seguros para session ID
        $sessionId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $sessionId);
        
        // Verificar longitud
        if (strlen($sessionId) < 5 || strlen($sessionId) > 100) {
            return '';
        }

        return $sessionId;
    }

    /**
     * Sanitizar respuesta para output
     */
    private function sanitizarRespuesta($respuesta) {
        if (!is_string($respuesta)) {
            return '';
        }

        // La respuesta ya viene sanitizada del servicio, pero doble verificación
        $respuesta = trim($respuesta);
        
        // Permitir HTML básico pero escapar scripts
        $respuesta = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $respuesta);
        $respuesta = preg_replace('/javascript:/i', '', $respuesta);
        
        return $respuesta;
    }

    /**
     * Sanitizar tipo de respuesta
     */
    private function sanitizarTipo($tipo) {
        $tiposValidos = ['faq', 'gemini', 'datos_uv', 'error', 'manual'];
        $tipo = trim(strtolower($tipo));
        
        return in_array($tipo, $tiposValidos) ? $tipo : 'general';
    }

    // ========================================
    // 🛡️ MÉTODOS DE SEGURIDAD
    // ========================================

    /**
     * Configurar headers de seguridad para API
     */
    private function configurarHeadersSeguridad() {
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: DENY');
            header('X-XSS-Protection: 1; mode=block');
            header('Access-Control-Allow-Origin: *'); // En producción especificar dominio
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            
            // Prevenir caching de respuestas sensibles
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Pragma: no-cache');
        }
    }

    /**
     * Verificar rate limiting por IP
     */
    private function verificarRateLimitIP() {
        $ip = $this->obtenerIPReal();
        $ahora = time();
        $ventana = self::TIMEOUT_RATE_LIMIT;
        
        // Inicializar si no existe
        if (!isset($_SESSION['api_rate_limit'])) {
            $_SESSION['api_rate_limit'] = [];
        }
        
        if (!isset($_SESSION['api_rate_limit'][$ip])) {
            $_SESSION['api_rate_limit'][$ip] = [];
        }
        
        // Limpiar intentos antiguos
        $_SESSION['api_rate_limit'][$ip] = array_filter(
            $_SESSION['api_rate_limit'][$ip],
            function($tiempo) use ($ahora, $ventana) {
                return ($ahora - $tiempo) < $ventana;
            }
        );
        
        // Verificar límite
        if (count($_SESSION['api_rate_limit'][$ip]) >= self::MAX_REQUESTS_POR_IP) {
            return false;
        }
        
        // Registrar intento actual
        $_SESSION['api_rate_limit'][$ip][] = $ahora;
        
        return true;
    }

    /**
     * Obtener IP real del usuario
     */
    private function obtenerIPReal() {
        // Headers en orden de prioridad
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = trim(explode(',', $_SERVER[$header])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Validar parámetros del mensaje
     */
    private function validarParametrosMensaje($mensaje, $sessionId) {
        // Validar mensaje
        if (empty($mensaje)) {
            return ['valido' => false, 'error' => 'Mensaje requerido'];
        }

        if (strlen($mensaje) > self::MAX_MENSAJE_LENGTH) {
            return ['valido' => false, 'error' => 'Mensaje muy largo (máximo ' . self::MAX_MENSAJE_LENGTH . ' caracteres)'];
        }

        // Validar session_id
        if (empty($sessionId)) {
            return ['valido' => false, 'error' => 'Session ID inválido'];
        }

        // Detectar posible spam
        if ($this->detectarSpam($mensaje)) {
            return ['valido' => false, 'error' => 'Mensaje no permitido'];
        }

        return ['valido' => true];
    }

    /**
     * Detectar posible spam o contenido malicioso
     */
    private function detectarSpam($mensaje) {
        $mensaje = strtolower($mensaje);
        
        // Patrones de spam
        $patronesSpam = [
            '/http[s]?:\/\//',           // URLs
            '/www\./i',                  // WWW
            '/\b(viagra|casino|loan)\b/i', // Palabras spam
            '/(.)\1{10,}/',              // Caracteres repetidos
            '/[!]{3,}/',                 // Múltiples exclamaciones
            '/[A-Z]{10,}/',              // Texto en mayúsculas
        ];

        foreach ($patronesSpam as $patron) {
            if (preg_match($patron, $mensaje)) {
                return true;
            }
        }

        return false;
    }

    // ========================================
    // MÉTODOS DE NEGOCIO (sin cambios mayores)
    // ========================================

    /**
     * Procesar mensaje híbrido: FAQs → UV Data → Gemini
     */
    private function procesarMensaje($mensaje, $sessionId) {
        $inicioTiempo = microtime(true);

        try {
            // PASO 1: Intentar con datos UV si es relevante
            if ($this->necesitaDatosUV($mensaje)) {
                $datosUV = $this->obtenerDatosUV($mensaje);                
                if ($datosUV) {
                    // Procesar con Gemini + datos UV
                    $respuesta = ChatbotService::procesarConGemini($mensaje, $datosUV, [
                        'empresa' => 'IntiSmart',
                        'producto' => 'INTI UV+'
                    ]);

                    return [
                        'mensaje' => $respuesta['respuesta'],
                        'tipo' => 'datos_uv',
                        'tokens_usados' => $respuesta['tokens_usados'],
                        'tiempo_respuesta' => round((microtime(true) - $inicioTiempo) * 1000)
                    ];
                }
            }

            // PASO 2: Procesar solo con Gemini
            $respuesta = ChatbotService::procesarConGemini($mensaje, null, [
                'empresa' => 'IntiSmart',
                'producto' => 'INTI UV+'
            ]);

            return [
                'mensaje' => $respuesta['respuesta'],
                'tipo' => $respuesta['error'] ? 'error' : 'gemini',
                'tokens_usados' => $respuesta['tokens_usados'],
                'tiempo_respuesta' => round((microtime(true) - $inicioTiempo) * 1000)
            ];

        } catch (Exception $e) {
            error_log("Error procesando mensaje: " . $e->getMessage());
            
            return [
                'mensaje' => 'Disculpa, hubo un error procesando tu mensaje. ¿Puedes intentar de nuevo?',
                'tipo' => 'error',
                'tokens_usados' => 0,
                'tiempo_respuesta' => round((microtime(true) - $inicioTiempo) * 1000)
            ];
        }
    }

    /**
     * Verificar si necesita datos UV
     */
    private function necesitaDatosUV($mensaje) {
        $palabrasUV = ['uv', 'radiación', 'temperatura', 'humedad', 'estación', 'datos', 'actual', 'hoy'];
        $mensajeLimpio = strtolower($mensaje);
        
        foreach ($palabrasUV as $palabra) {
            if (strpos($mensajeLimpio, $palabra) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Obtener datos UV relevantes
     */
    private function obtenerDatosUV($mensaje) {
        try {
            return [
                'ultimos_registros' => RegistrosRadiacion::obtenerUltimosRegistros(),
                'estadisticas_dia' => RegistrosRadiacion::obtenerEstadisticasDia(),
                'sistema_activo' => RegistrosRadiacion::hayDatosRecientes(30),
                'timestamp' => date('Y-m-d H:i:s')
            ];

        } catch (Exception $e) {
            error_log("Error obteniendo datos UV: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verificar límites de uso
     */
    private function verificarLimitesUso($sessionId) {
        try {
            // Límite: mensajes por sesión
            $mensajesSesion = ChatbotMensaje::contarMensajes($sessionId);
            if ($mensajesSesion >= self::MAX_MENSAJES_POR_SESION) {
                return false;
            }

            // Límite: tokens por día
            $tokensHoy = ChatbotMensaje::tokensUsadosHoy();
            if ($tokensHoy >= self::MAX_TOKENS_DIARIOS) {
                return false;
            }

            return true;

        } catch (Exception $e) {
            error_log("Error verificando límites: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generar ID de sesión único y seguro
     */
    private function generarSessionId() {
        return 'chat_' . bin2hex(random_bytes(8)) . '_' . time();
    }

    // ========================================
    // MÉTODOS DE ESTADÍSTICAS
    // ========================================

    private function contarMensajesHoy() {
        try {
            $hoy = date('Y-m-d');
            $query = "SELECT COUNT(*) as total FROM chat_mensajes WHERE DATE(timestamp_creado) = ?";
            $resultado = ChatbotMensaje::query($query, [$hoy]);
            return (int) ($resultado[0]['total'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    private function contarSesionesActivas() {
        try {
            $hace24h = date('Y-m-d H:i:s', strtotime('-24 hours'));
            $query = "SELECT COUNT(DISTINCT session_id) as total FROM chat_mensajes WHERE timestamp_creado >= ?";
            $resultado = ChatbotMensaje::query($query, [$hace24h]);
            return (int) ($resultado[0]['total'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    private function calcularUptime() {
        // Placeholder para cálculo de uptime
        return '99.9%';
    }
}