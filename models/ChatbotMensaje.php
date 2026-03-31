<?php

namespace Model;

use Exception;

class ChatbotMensaje extends ActiveRecord {
    
    protected static $tabla = 'chat_mensajes';
    protected static $columnasDB = [
        'id', 'session_id', 'mensaje_usuario', 'respuesta_bot', 
        'tipo_respuesta', 'tokens_usados', 'timestamp_creado'
    ];
    
    protected static $validaciones = [
        'session_id' => ['required', ['max', 100]],
        'mensaje_usuario' => ['required', ['max', 1000]],
        'respuesta_bot' => ['required']
    ];

    public $id;
    public $session_id;
    public $mensaje_usuario;
    public $respuesta_bot;
    public $tipo_respuesta;
    public $tokens_usados;
    public $timestamp_creado;

    // Constructor con sanitización completa
    public function __construct($args = []) {
        // Sanitizar todos los datos de entrada
        $datosLimpios = $this->sanitizarDatos($args);
        
        $this->id = $datosLimpios['id'] ?? null;
        $this->session_id = $datosLimpios['session_id'] ?? null;
        $this->mensaje_usuario = $datosLimpios['mensaje_usuario'] ?? '';
        $this->respuesta_bot = $datosLimpios['respuesta_bot'] ?? '';
        $this->tipo_respuesta = $datosLimpios['tipo_respuesta'] ?? 'faq';
        $this->tokens_usados = $datosLimpios['tokens_usados'] ?? 0;
        $this->timestamp_creado = $datosLimpios['timestamp_creado'] ?? date('Y-m-d H:i:s');
    }

    // ========================================
    // 🛡️ MÉTODOS DE SANITIZACIÓN
    // ========================================

    /**
     * Sanitizar todos los datos de entrada
     * @param array $datos Datos crudos del chatbot
     * @return array Datos sanitizados
     */
    private function sanitizarDatos($datos) {
        $sanitizados = [];
        
        foreach ($datos as $campo => $valor) {
            switch ($campo) {
                case 'session_id':
                    $sanitizados[$campo] = $this->sanitizarSessionId($valor);
                    break;
                    
                case 'mensaje_usuario':
                    $sanitizados[$campo] = $this->sanitizarMensajeUsuario($valor);
                    break;
                    
                case 'respuesta_bot':
                    $sanitizados[$campo] = $this->sanitizarRespuestaBot($valor);
                    break;
                    
                case 'tipo_respuesta':
                    $sanitizados[$campo] = $this->sanitizarTipoRespuesta($valor);
                    break;
                    
                case 'tokens_usados':
                    $sanitizados[$campo] = $this->sanitizarTokens($valor);
                    break;
                    
                case 'timestamp_creado':
                    $sanitizados[$campo] = $this->sanitizarTimestamp($valor);
                    break;
                    
                default:
                    // Para campos no específicos, sanitización básica
                    $sanitizados[$campo] = $this->sanitizarGeneral($valor);
                    break;
            }
        }
        
        return $sanitizados;
    }

    /**
     * Sanitizar Session ID (alfanumérico + guiones bajos)
     */
    private function sanitizarSessionId($valor) {
        if (empty($valor)) return null;
        
        $valor = trim($valor);
        // Solo permitir letras, números, guiones y guiones bajos
        $valor = preg_replace('/[^a-zA-Z0-9_\-]/', '', $valor);
        
        // Longitud máxima para session ID
        return substr($valor, 0, 100);
    }

    /**
     * Sanitizar mensaje del usuario (texto chat)
     */
    private function sanitizarMensajeUsuario($valor) {
        if (empty($valor)) return '';
        
        $valor = trim($valor);
        
        // Remover HTML/XML tags
        $valor = strip_tags($valor);
        
        // Escapar caracteres especiales para evitar XSS
        $valor = htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
        
        // Normalizar espacios múltiples
        $valor = preg_replace('/\s+/', ' ', $valor);
        
        // Remover caracteres de control peligrosos
        $valor = preg_replace('/[\x00-\x1F\x7F]/', '', $valor);
        
        // Filtrar palabras potencialmente peligrosas para prompt injection
        $valor = $this->filtrarPromptInjection($valor);
        
        // Límite de longitud
        return substr($valor, 0, 1000);
    }

    /**
     * Sanitizar respuesta del bot
     */
    private function sanitizarRespuestaBot($valor) {
        if (empty($valor)) return '';
        
        $valor = trim($valor);
        
        // Permitir algunos HTML básicos pero escapar el resto
        $tagsPermitidos = '<b><i><u><br><p><strong><em>';
        $valor = strip_tags($valor, $tagsPermitidos);
        
        // Escapar atributos potencialmente peligrosos
        $valor = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $valor);
        $valor = preg_replace('/javascript:/i', '', $valor);
        
        // Normalizar espacios
        $valor = preg_replace('/\s+/', ' ', $valor);
        
        // Límite de longitud para respuestas
        return substr($valor, 0, 2000);
    }

    /**
     * Sanitizar tipo de respuesta (whitelist)
     */
    private function sanitizarTipoRespuesta($valor) {
        if (empty($valor)) return 'faq';
        
        $tiposValidos = ['faq', 'gemini', 'datos_uv', 'error', 'manual'];
        $valor = trim(strtolower($valor));
        
        return in_array($valor, $tiposValidos) ? $valor : 'faq';
    }

    /**
     * Sanitizar número de tokens
     */
    private function sanitizarTokens($valor) {
        if (is_null($valor)) return 0;
        
        $tokens = (int) $valor;
        
        // Rango válido de tokens (0 a 10000)
        return max(0, min($tokens, 10000));
    }

    /**
     * Sanitizar timestamp
     */
    private function sanitizarTimestamp($valor) {
        if (empty($valor)) return date('Y-m-d H:i:s');
        
        $valor = trim($valor);
        
        // Validar formato de fecha
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $valor)) {
            // Verificar que la fecha sea válida
            $timestamp = strtotime($valor);
            if ($timestamp !== false) {
                return $valor;
            }
        }
        
        return date('Y-m-d H:i:s');
    }

    /**
     * Filtrar posible prompt injection
     */
    private function filtrarPromptInjection($mensaje) {
        // Patrones peligrosos para prompt injection
        $patronesPeligrosos = [
            '/ignore\s+previous\s+instructions/i',
            '/forget\s+everything/i',
            '/system\s*:\s*/i',
            '/assistant\s*:\s*/i',
            '/you\s+are\s+now/i',
            '/pretend\s+to\s+be/i',
            '/act\s+as\s+if/i',
            '/override\s+your/i',
            '/new\s+instructions/i',
            '/<\s*script\s*>/i',
            '/javascript\s*:/i',
            '/eval\s*\(/i',
            '/function\s*\(/i',
        ];
        
        foreach ($patronesPeligrosos as $patron) {
            $mensaje = preg_replace($patron, '[filtrado]', $mensaje);
        }
        
        return $mensaje;
    }

    /**
     * Sanitización general para campos no específicos
     */
    private function sanitizarGeneral($valor) {
        if (is_null($valor)) return null;
        if (is_numeric($valor)) return $valor;
        
        $valor = trim($valor);
        $valor = strip_tags($valor);
        $valor = htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
        
        return $valor;
    }

    // ========================================
    // MÉTODOS ESTÁTICOS MEJORADOS
    // ========================================

    /**
     * Crear nuevo mensaje de conversación (MEJORADO)
     */
    public static function crearConversacion($sessionId, $mensajeUsuario, $respuestaBot, $tipo = 'faq', $tokens = 0) {
        // Validación adicional antes de crear
        if (empty($sessionId) || empty($mensajeUsuario) || empty($respuestaBot)) {
            return null;
        }
        
        $mensaje = new static([
            'session_id' => $sessionId,
            'mensaje_usuario' => $mensajeUsuario,
            'respuesta_bot' => $respuestaBot,
            'tipo_respuesta' => $tipo,
            'tokens_usados' => $tokens
        ]);

        return $mensaje->guardar() ? $mensaje : null;
    }

    /**
     * Obtener historial de conversación (MEJORADO)
     */
    public static function obtenerHistorial($sessionId, $limite = 20) {
        try {
            // Sanitizar session_id de entrada
            $sessionId = preg_replace('/[^a-zA-Z0-9_\-]/', '', trim($sessionId));
            if (empty($sessionId)) {
                return [];
            }
            
            // Validar límite
            $limite = max(1, min((int) $limite, 100));
            
            $query = "SELECT * FROM " . static::$tabla . " 
                     WHERE session_id = ? 
                     ORDER BY timestamp_creado ASC 
                     LIMIT ?";
            
            $resultados = static::query($query, [$sessionId, $limite]);
            
            // Sanitizar resultados para output seguro
            return array_map(function($mensaje) {
                return [
                    'id' => (int) $mensaje['id'],
                    'session_id' => htmlspecialchars($mensaje['session_id']),
                    'mensaje_usuario' => htmlspecialchars($mensaje['mensaje_usuario']),
                    'respuesta_bot' => $mensaje['respuesta_bot'], // Ya sanitizada al guardar
                    'tipo_respuesta' => htmlspecialchars($mensaje['tipo_respuesta']),
                    'tokens_usados' => (int) $mensaje['tokens_usados'],
                    'timestamp_creado' => $mensaje['timestamp_creado']
                ];
            }, $resultados);
            
        } catch (Exception $e) {
            error_log("Error obteniendo historial: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar mensajes por sesión (MEJORADO)
     */
    public static function contarMensajes($sessionId) {
        try {
            // Sanitizar session_id
            $sessionId = preg_replace('/[^a-zA-Z0-9_\-]/', '', trim($sessionId));
            if (empty($sessionId)) {
                return 0;
            }
            
            $query = "SELECT COUNT(*) as total FROM " . static::$tabla . " WHERE session_id = ?";
            $resultado = static::query($query, [$sessionId]);
            return (int) ($resultado[0]['total'] ?? 0);
        } catch (Exception $e) {
            error_log("Error contando mensajes: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener tokens usados hoy (sin cambios, ya era seguro)
     */
    public static function tokensUsadosHoy() {
        try {
            $hoy = date('Y-m-d');
            $query = "SELECT SUM(tokens_usados) as total FROM " . static::$tabla . " 
                     WHERE DATE(timestamp_creado) = ?";
            
            $resultado = static::query($query, [$hoy]);
            return (int) ($resultado[0]['total'] ?? 0);
        } catch (Exception $e) {
            error_log("Error obteniendo tokens: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener estadísticas de uso por sesión
     */
    public static function obtenerEstadisticasSesion($sessionId) {
        try {
            $sessionId = preg_replace('/[^a-zA-Z0-9_\-]/', '', trim($sessionId));
            if (empty($sessionId)) {
                return null;
            }
            
            $query = "SELECT 
                        COUNT(*) as total_mensajes,
                        SUM(tokens_usados) as total_tokens,
                        MIN(timestamp_creado) as primer_mensaje,
                        MAX(timestamp_creado) as ultimo_mensaje,
                        tipo_respuesta
                      FROM " . static::$tabla . " 
                      WHERE session_id = ?
                      GROUP BY session_id, tipo_respuesta";
            
            return static::query($query, [$sessionId]);
        } catch (Exception $e) {
            error_log("Error obteniendo estadísticas: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Buscar mensajes por contenido (para moderar)
     */
    public static function buscarPorContenido($termino, $limite = 50) {
        try {
            // Sanitizar término de búsqueda
            $termino = trim($termino);
            $termino = strip_tags($termino);
            $termino = htmlspecialchars($termino, ENT_QUOTES, 'UTF-8');
            
            if (empty($termino) || strlen($termino) < 3) {
                return [];
            }
            
            $limite = max(1, min((int) $limite, 100));
            $terminoBusqueda = "%{$termino}%";
            
            $query = "SELECT session_id, mensaje_usuario, respuesta_bot, timestamp_creado 
                      FROM " . static::$tabla . " 
                      WHERE mensaje_usuario LIKE ? OR respuesta_bot LIKE ?
                      ORDER BY timestamp_creado DESC 
                      LIMIT ?";
            
            return static::query($query, [$terminoBusqueda, $terminoBusqueda, $limite]);
        } catch (Exception $e) {
            error_log("Error buscando contenido: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Limpiar mensajes antiguos (MEJORADO)
     */
    public static function limpiarMensajesAntiguos($dias = 7) {
        try {
            $dias = max(1, min((int) $dias, 365)); // Entre 1 y 365 días
            $fechaLimite = date('Y-m-d', strtotime("-{$dias} days"));
            
            $query = "DELETE FROM " . static::$tabla . " WHERE DATE(timestamp_creado) < ?";
            return static::execute($query, [$fechaLimite]);
        } catch (Exception $e) {
            error_log("Error limpiando mensajes antiguos: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validar integridad de mensaje antes de guardar
     */
    public function validarIntegridad() {
        $errores = [];
        
        // Validar session_id
        if (empty($this->session_id) || !preg_match('/^[a-zA-Z0-9_\-]+$/', $this->session_id)) {
            $errores[] = "Session ID inválido";
        }
        
        // Validar mensaje de usuario
        if (empty($this->mensaje_usuario) || strlen($this->mensaje_usuario) > 1000) {
            $errores[] = "Mensaje de usuario inválido";
        }
        
        // Validar respuesta del bot
        if (empty($this->respuesta_bot) || strlen($this->respuesta_bot) > 2000) {
            $errores[] = "Respuesta del bot inválida";
        }
        
        // Validar tokens
        if ($this->tokens_usados < 0 || $this->tokens_usados > 10000) {
            $errores[] = "Número de tokens inválido";
        }
        
        return $errores;
    }

    /**
     * Método para exportar conversación de forma segura
     */
    public static function exportarConversacion($sessionId) {
        $historial = self::obtenerHistorial($sessionId);
        
        return array_map(function($mensaje) {
            return [
                'timestamp' => $mensaje['timestamp_creado'],
                'usuario' => $mensaje['mensaje_usuario'],
                'bot' => strip_tags($mensaje['respuesta_bot']),
                'tipo' => $mensaje['tipo_respuesta']
            ];
        }, $historial);
    }
}