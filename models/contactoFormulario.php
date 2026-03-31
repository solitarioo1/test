<?php
namespace Model;

use Exception;

class ContactoFormulario extends ActiveRecord {
    // Configuración de la tabla
    protected static $tabla = 'contactos';
    protected static $columnasDB = [
        'id',
        'nombre',
        'email',
        'telefono',
        'empresa',
        'tipo_cita',
        'tipo_interes',
        'fecha_preferida',
        'hora_preferida',
        'mensaje',
        'departamento',
        'ciudad',
        'newsletter',
        'politica_aceptada',
        'estado',
        'ip_origen'
    ];

    // Propiedades (¡IMPORTANTE! deben coincidir con $columnasDB)
    public $id;
    public $nombre;
    public $email;
    public $telefono;
    public $empresa;
    public $tipo_cita;
    public $tipo_interes;
    public $fecha_preferida;
    public $hora_preferida;
    public $mensaje;
    public $departamento;
    public $ciudad;
    public $newsletter;
    public $politica_aceptada;
    public $estado;
    public $ip_origen;

    // Constructor con sanitización
    public function __construct($args = []) {
        // Sanitizar todos los datos de entrada
        $datosLimpios = $this->sanitizarDatos($args);
        
        // Asignar propiedades con datos ya sanitizados
        $this->id = $datosLimpios['id'] ?? null;
        $this->nombre = $datosLimpios['nombre'] ?? '';
        $this->email = $datosLimpios['email'] ?? '';
        $this->telefono = $datosLimpios['telefono'] ?? '';
        $this->empresa = $datosLimpios['empresa'] ?? null;
        $this->tipo_cita = $datosLimpios['tipo_cita'] ?? '';
        $this->tipo_interes = $datosLimpios['tipo_interes'] ?? '';
        $this->fecha_preferida = $datosLimpios['fecha_preferida'] ?? '';
        $this->hora_preferida = $datosLimpios['hora_preferida'] ?? '';
        $this->mensaje = $datosLimpios['mensaje'] ?? null;
        $this->departamento = $datosLimpios['departamento'] ?? '';
        $this->ciudad = $datosLimpios['ciudad'] ?? '';
        $this->newsletter = isset($datosLimpios['newsletter']) ? 1 : 0;
        $this->politica_aceptada = isset($datosLimpios['politica']) ? 1 : 0;
        $this->estado = 'pendiente';
        $this->ip_origen = $this->sanitizarIP($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }

    // ========================================
    // 🛡️ MÉTODOS DE SANITIZACIÓN
    // ========================================

    /**
     * Sanitizar todos los datos de entrada
     * @param array $datos Datos crudos del formulario
     * @return array Datos sanitizados
     */
    private function sanitizarDatos($datos) {
        $sanitizados = [];
        
        foreach ($datos as $campo => $valor) {
            switch ($campo) {
                case 'nombre':
                    $sanitizados[$campo] = $this->sanitizarNombre($valor);
                    break;
                    
                case 'email':
                    $sanitizados[$campo] = $this->sanitizarEmail($valor);
                    break;
                    
                case 'telefono':
                    $sanitizados[$campo] = $this->sanitizarTelefono($valor);
                    break;
                    
                case 'empresa':
                case 'ciudad':
                    $sanitizados[$campo] = $this->sanitizarTexto($valor);
                    break;
                    
                case 'tipo_cita':
                case 'tipo_interes':
                case 'departamento':
                    $sanitizados[$campo] = $this->sanitizarOpcion($valor);
                    break;
                    
                case 'fecha_preferida':
                    $sanitizados[$campo] = $this->sanitizarFecha($valor);
                    break;
                    
                case 'hora_preferida':
                    $sanitizados[$campo] = $this->sanitizarHora($valor);
                    break;
                    
                case 'mensaje':
                    $sanitizados[$campo] = $this->sanitizarMensaje($valor);
                    break;
                    
                case 'newsletter':
                case 'politica':
                    $sanitizados[$campo] = $this->sanitizarCheckbox($valor);
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
     * Sanitizar nombre (solo letras, espacios y acentos)
     */
    private function sanitizarNombre($valor) {
        if (empty($valor)) return '';
        
        $valor = trim($valor);
        $valor = strip_tags($valor);
        $valor = preg_replace('/[^A-Za-zÀ-ÿ\s]/', '', $valor);
        $valor = preg_replace('/\s+/', ' ', $valor); // Espacios múltiples a uno
        
        return substr($valor, 0, 50);
    }

    /**
     * Sanitizar email
     */
    private function sanitizarEmail($valor) {
        if (empty($valor)) return '';
        
        $valor = trim($valor);
        $valor = strtolower($valor);
        $valor = filter_var($valor, FILTER_SANITIZE_EMAIL);
        
        return substr($valor, 0, 100);
    }

    /**
     * Sanitizar teléfono (solo números)
     */
    private function sanitizarTelefono($valor) {
        if (empty($valor)) return '';
        
        $valor = preg_replace('/[^0-9]/', '', $valor);
        
        return substr($valor, 0, 15);
    }

    /**
     * Sanitizar texto general (empresa, ciudad)
     */
    private function sanitizarTexto($valor) {
        if (empty($valor)) return null;
        
        $valor = trim($valor);
        $valor = strip_tags($valor);
        $valor = htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
        $valor = preg_replace('/\s+/', ' ', $valor);
        
        return substr($valor, 0, 100);
    }

    /**
     * Sanitizar opciones de select (whitelist)
     */
    private function sanitizarOpcion($valor) {
        if (empty($valor)) return '';
        
        $valor = trim($valor);
        $valor = preg_replace('/[^a-z_]/', '', strtolower($valor));
        
        return $valor;
    }

    /**
     * Sanitizar fecha
     */
    private function sanitizarFecha($valor) {
        if (empty($valor)) return '';
        
        $valor = trim($valor);
        $valor = preg_replace('/[^0-9\-]/', '', $valor);
        
        // Validar formato Y-m-d
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            return $valor;
        }
        
        return '';
    }

    /**
     * Sanitizar hora
     */
    private function sanitizarHora($valor) {
        if (empty($valor)) return '';
        
        $valor = trim($valor);
        $valor = preg_replace('/[^0-9:]/', '', $valor);
        
        // Validar formato H:i
        if (preg_match('/^\d{2}:\d{2}$/', $valor)) {
            return $valor;
        }
        
        return '';
    }

    /**
     * Sanitizar mensaje largo
     */
    private function sanitizarMensaje($valor) {
        if (empty($valor)) return null;
        
        $valor = trim($valor);
        $valor = strip_tags($valor);
        $valor = htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
        $valor = preg_replace('/\s+/', ' ', $valor);
        
        return substr($valor, 0, 500);
    }

    /**
     * Sanitizar checkbox
     */
    private function sanitizarCheckbox($valor) {
        return !empty($valor) && ($valor === 'si' || $valor === '1' || $valor === 'on');
    }

    /**
     * Sanitizar IP
     */
    private function sanitizarIP($valor) {
        $valor = trim($valor);
        
        if (filter_var($valor, FILTER_VALIDATE_IP)) {
            return $valor;
        }
        
        return 'unknown';
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
    // VALIDACIÓN (sin cambios, pero mejorada)
    // ========================================

    public function validar() {
        static::$errores = [];

        // Validar nombre
        if (empty($this->nombre) || !preg_match('/^[A-Za-zÀ-ÿ\s]{2,50}$/', $this->nombre)) {
            static::$errores[] = "El nombre debe contener solo letras y espacios (2-50 caracteres)";
        }

        // Validar email
        if (empty($this->email) || !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            static::$errores[] = "El correo electrónico no tiene un formato válido";
        }

        // Validar teléfono
        if (empty($this->telefono) || !preg_match('/^[0-9]{9,15}$/', $this->telefono)) {
            static::$errores[] = "El teléfono debe contener entre 9 y 15 dígitos";
        }

        // Validar empresa (opcional)
        if (!empty($this->empresa) && (strlen($this->empresa) < 2 || strlen($this->empresa) > 100)) {
            static::$errores[] = "El nombre de la empresa debe tener entre 2 y 100 caracteres";
        }

        // Validar tipo de cita
        $tiposCitaValidos = ['presencial', 'virtual'];
        if (empty($this->tipo_cita) || !in_array($this->tipo_cita, $tiposCitaValidos)) {
            static::$errores[] = "Debe seleccionar un tipo de atención válido";
        }

        // Validar tipo de interés
        $tiposInteresValidos = ['detector_uv', 'charla_salud', 'ambos'];
        if (empty($this->tipo_interes) || !in_array($this->tipo_interes, $tiposInteresValidos)) {
            static::$errores[] = "Debe seleccionar qué le interesa conocer";
        }

        // Validar fecha
        if (empty($this->fecha_preferida)) {
            static::$errores[] = "Debe seleccionar una fecha preferida";
        } else {
            $fechaSeleccionada = new \DateTime($this->fecha_preferida);
            $fechaHoy = new \DateTime('today');
            if ($fechaSeleccionada < $fechaHoy) {
                static::$errores[] = "La fecha no puede ser anterior a hoy";
            }
        }

        // Validar hora
        if (empty($this->hora_preferida)) {
            static::$errores[] = "Debe seleccionar una hora preferida";
        } else {
            $hora = (int) substr($this->hora_preferida, 0, 2);
            if ($hora < 9 || $hora > 17) {
                static::$errores[] = "La hora debe estar entre 9:00 AM y 5:00 PM";
            }
        }

        // Validar departamento
        $departamentosValidos = ['AMA', 'ANC', 'APU', 'ARE', 'AYA', 'CAJ', 'CAL', 'CUS', 'HUV', 'HUC', 'ICA', 'JUN', 'LAL', 'LAM', 'LIM', 'LOR', 'MDD', 'MOQ', 'PAS', 'PIU', 'PUN', 'SAM', 'TAC', 'TUM', 'UCA'];
        if (empty($this->departamento) || !in_array($this->departamento, $departamentosValidos)) {
            static::$errores[] = "Debe seleccionar un departamento válido";
        }

        // Validar ciudad
        if (empty($this->ciudad) || strlen($this->ciudad) < 2 || strlen($this->ciudad) > 100) {
            static::$errores[] = "La ciudad debe tener entre 2 y 100 caracteres";
        }

        // Validar política de privacidad
        if (!$this->politica_aceptada) {
            static::$errores[] = "Debe aceptar la política de privacidad";
        }

        return static::$errores;
    }

    // ========================================
    // MÉTODOS DE NEGOCIO (Arreglados - sin SQL injection)
    // ========================================

    /**
     * Método para cambiar el estado de la cita
     */
    public function cambiarEstado($nuevoEstado) {
        $estadosValidos = ['pendiente', 'confirmada', 'completada', 'cancelada'];
        
        if (!in_array($nuevoEstado, $estadosValidos)) {
            throw new Exception("Estado no válido");
        }

        $this->estado = $nuevoEstado;
        return $this->guardar();
    }

    /**
     * Método para marcar como newsletter
     */
    public function suscribirNewsletter() {
        $this->newsletter = 1;
        return $this->guardar();
    }

    /**
     * Método para obtener citas por estado (ARREGLADO - sin SQL injection)
     */
    public static function obtenerPorEstado($estado) {
        $estadosValidos = ['pendiente', 'confirmada', 'completada', 'cancelada'];
        
        if (!in_array($estado, $estadosValidos)) {
            return [];
        }
        
        $query = "SELECT * FROM " . static::$tabla . " WHERE estado = ? ORDER BY fecha_preferida ASC";
        return self::consultarSQL($query, [$estado]);
    }

    /**
     * Método para obtener citas próximas (ARREGLADO - sin SQL injection)
     */
    public static function obtenerProximas($limite = 10) {
        $limite = (int) $limite;
        if ($limite <= 0 || $limite > 100) {
            $limite = 10; // Valor por defecto seguro
        }
        
        $fechaHoy = date('Y-m-d');
        $query = "SELECT * FROM " . static::$tabla . " WHERE fecha_preferida >= ? ORDER BY fecha_preferida ASC LIMIT ?";
        return self::consultarSQL($query, [$fechaHoy, $limite]);
    }

    /**
     * Obtener citas por fecha específica
     */
    public static function obtenerPorFecha($fecha) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return [];
        }
        
        $query = "SELECT * FROM " . static::$tabla . " WHERE DATE(fecha_preferida) = ? ORDER BY hora_preferida ASC";
        return self::consultarSQL($query, [$fecha]);
    }

    /**
     * Obtener estadísticas básicas
     */
    public static function obtenerEstadisticas() {
        $query = "SELECT 
                    estado, 
                    COUNT(*) as total,
                    DATE(fecha_preferida) as fecha
                  FROM " . static::$tabla . " 
                  GROUP BY estado, DATE(fecha_preferida)
                  ORDER BY fecha_preferida DESC";
        
        return self::consultarSQL($query);
    }

    /**
     * Buscar contactos por email (para evitar duplicados)
     */
    public static function buscarPorEmail($email) {
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [];
        }
        
        $query = "SELECT * FROM " . static::$tabla . " WHERE email = ? ORDER BY id DESC";
        return self::consultarSQL($query, [$email]);
    }
}