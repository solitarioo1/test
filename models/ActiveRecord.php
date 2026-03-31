<?php   

namespace Model;

use PDO;
use Exception;
use Config\Database;

abstract class ActiveRecord {

    // Conexiones múltiples
    protected static $connections = [
        'main' => null,
        'chatbot' => null
    ];
    
    protected static $currentConnection = 'main';
    protected static $columnasDB = [];
    protected static $tabla = '';
    protected static $errores = [];
    protected static $validaciones = [];
    
    protected $id;
    protected $existe = false;

    /**
     * Configurar conexión principal (main)
     */
    public static function setDB($database) {
        self::$connections['main'] = $database;
        self::$currentConnection = 'main';
    }

    /**
     * Configurar conexión para chatbot
     */
    public static function setChatbotDB($database) {
        self::$connections['chatbot'] = $database;
    }

    /**
     * Cambiar conexión activa
     */
    public static function useConnection($connection = 'main') {
        if (!in_array($connection, ['main', 'chatbot'])) {
            throw new Exception("Conexión '{$connection}' no válida. Usa 'main' o 'chatbot'");
        }
        
        self::$currentConnection = $connection;
        return new static;
    }

    /**
     * Obtener la conexión actual
     * @return PDO
     * @throws Exception Si no se puede obtener la conexión
     */
    protected static function getDB(): PDO {
        $connection = self::$currentConnection;
        
        if (self::$connections[$connection] === null) {
            // Auto-configurar conexiones si no están definidas
            if ($connection === 'main') {
                self::$connections['main'] = Database::getMainInstance()->getConnection();
            } else {
                self::$connections['chatbot'] = Database::getChatbotInstance()->getConnection();
            }
        }
        
        $db = self::$connections[$connection];
        
        if ($db === null) {
            throw new Exception("No se pudo establecer conexión a la base de datos '{$connection}'");
        }
        
        return $db;
    }

    public static function getErrores() {
        return static::$errores;
    }

    /**
     * Validar datos según reglas definidas en cada modelo
     */
    public function validar() {
        static::$errores = [];
        
        foreach (static::$validaciones as $campo => $reglas) {
            $valor = $this->$campo ?? null;
            
            foreach ($reglas as $regla) {
                if (is_string($regla)) {
                    $this->aplicarValidacion($campo, $regla, $valor);
                } elseif (is_array($regla)) {
                    $this->aplicarValidacion($campo, $regla[0], $valor, $regla[1] ?? null);
                }
            }
        }
        
        return static::$errores;
    }

    /**
     * Aplicar validación específica
     */
    protected function aplicarValidacion($campo, $regla, $valor, $parametro = null) {
        switch ($regla) {
            case 'required':
                if (empty($valor)) {
                    static::$errores[] = "El campo {$campo} es obligatorio";
                }
                break;
                
            case 'email':
                if (!empty($valor) && !filter_var($valor, FILTER_VALIDATE_EMAIL)) {
                    static::$errores[] = "El campo {$campo} debe ser un email válido";
                }
                break;
                
            case 'max':
                if (!empty($valor) && strlen($valor) > $parametro) {
                    static::$errores[] = "El campo {$campo} no puede tener más de {$parametro} caracteres";
                }
                break;
                
            case 'min':
                if (!empty($valor) && strlen($valor) < $parametro) {
                    static::$errores[] = "El campo {$campo} debe tener al menos {$parametro} caracteres";
                }
                break;
                
            case 'numeric':
                if (!empty($valor) && !is_numeric($valor)) {
                    static::$errores[] = "El campo {$campo} debe ser numérico";
                }
                break;
                
            case 'date':
                if (!empty($valor) && !strtotime($valor)) {
                    static::$errores[] = "El campo {$campo} debe ser una fecha válida";
                }
                break;
        }
    }

    public function guardar() {
        $errores = $this->validar();
        if (!empty($errores)) {
            return false;
        }
        
        return $this->existe ? $this->actualizar() : $this->crear();
    }

    // ========================================
    // MÉTODOS DE CONSULTA MEJORADOS
    // ========================================

    public static function all() {
        $query = "SELECT * FROM " . static::$tabla . " ORDER BY id ASC";
        return self::consultarSQL($query);
    }

    public static function get($limit) {
        $query = "SELECT * FROM " . static::$tabla . " ORDER BY id DESC LIMIT " . (int)$limit;
        return self::consultarSQL($query);
    }

    public static function find($id) {
        $query = "SELECT * FROM " . static::$tabla . " WHERE id = ? LIMIT 1";
        $resultado = self::consultarSQL($query, [$id]);
        $objeto = array_shift($resultado);
        if ($objeto) {
            $objeto->existe = true;
        }
        return $objeto;
    }

    public static function where($columna, $operador, $valor = null) {
        // Si solo se pasan 2 parámetros, asumir que el operador es '='
        if ($valor === null) {
            $valor = $operador;
            $operador = '=';
        }
        
        $query = "SELECT * FROM " . static::$tabla . " WHERE {$columna} {$operador} ?";
        return self::consultarSQL($query, [$valor]);
    }

    /**
     * Consultar por fecha específica
     */
    public static function whereDate($columna, $fecha) {
        $query = "SELECT * FROM " . static::$tabla . " WHERE DATE({$columna}) = ?";
        return self::consultarSQL($query, [$fecha]);
    }

    /**
     * Consultar por rango de fechas
     */
    public static function whereDateBetween($columna, $fechaInicio, $fechaFin) {
        $query = "SELECT * FROM " . static::$tabla . " WHERE DATE({$columna}) BETWEEN ? AND ?";
        return self::consultarSQL($query, [$fechaInicio, $fechaFin]);
    }

    /**
     * Ordenar resultados
     */
    public static function orderBy($columna, $direccion = 'ASC') {
        $direccion = strtoupper($direccion) === 'DESC' ? 'DESC' : 'ASC';
        $query = "SELECT * FROM " . static::$tabla . " ORDER BY {$columna} {$direccion}";
        return self::consultarSQL($query);
    }

    /**
     * Obtener los últimos registros
     */
    public static function latest($columna = 'id', $limit = 10) {
        $query = "SELECT * FROM " . static::$tabla . " ORDER BY {$columna} DESC LIMIT " . (int)$limit;
        return self::consultarSQL($query);
    }

    // ========================================
    // MÉTODOS DE CREACIÓN Y ACTUALIZACIÓN
    // ========================================

    public function crear() {
        try {
            $atributos = $this->sanitizarAtributos();
            
            // Agregar timestamps si existen las columnas
            if (in_array('fecha_creacion', static::$columnasDB)) {
                $atributos['fecha_creacion'] = date('Y-m-d H:i:s');
            }
            if (in_array('fecha_actualizacion', static::$columnasDB)) {
                $atributos['fecha_actualizacion'] = date('Y-m-d H:i:s');
            }
            
            $columnas = implode(', ', array_keys($atributos));
            $placeholders = implode(', ', array_fill(0, count($atributos), '?'));
            
            $query = "INSERT INTO " . static::$tabla . " ({$columnas}) VALUES ({$placeholders})";
            
            $stmt = self::getDB()->prepare($query);
            $resultado = $stmt->execute(array_values($atributos));
            
            if ($resultado) {
                $this->id = self::getDB()->lastInsertId();
                $this->existe = true;
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            static::$errores[] = "Error al crear: " . $e->getMessage();
            error_log("Error creando en " . static::$tabla . ": " . $e->getMessage());
            return false;
        }
    }

    public function actualizar() {
        try {
            $atributos = $this->sanitizarAtributos();
            
            // Actualizar timestamp si existe la columna
            if (in_array('fecha_actualizacion', static::$columnasDB)) {
                $atributos['fecha_actualizacion'] = date('Y-m-d H:i:s');
            }
            
            $valores = [];
            foreach ($atributos as $key => $value) {
                $valores[] = "{$key} = ?";
            }
            
            $query = "UPDATE " . static::$tabla . " SET " . implode(', ', $valores) . " WHERE id = ?";
            
            $params = array_values($atributos);
            $params[] = $this->id;
            
            $stmt = self::getDB()->prepare($query);
            return $stmt->execute($params);
            
        } catch (Exception $e) {
            static::$errores[] = "Error al actualizar: " . $e->getMessage();
            error_log("Error actualizando en " . static::$tabla . ": " . $e->getMessage());
            return false;
        }
    }

    public function eliminar() {
        try {
            $query = "DELETE FROM " . static::$tabla . " WHERE id = ? LIMIT 1";
            $stmt = self::getDB()->prepare($query);
            return $stmt->execute([$this->id]);
        } catch (Exception $e) {
            static::$errores[] = "Error al eliminar: " . $e->getMessage();
            return false;
        }
    }

    // ========================================
    // MÉTODOS DE CONSULTA SQL
    // ========================================

    public static function consultarSQL($query, $params = []) {
        try {
            $stmt = self::getDB()->prepare($query);
            $stmt->execute($params);
            
            $resultados = [];
            while ($registro = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $objeto = static::crearObjeto($registro);
                $objeto->existe = true;
                $resultados[] = $objeto;
            }
            
            return $resultados;
            
        } catch (Exception $e) {
            error_log("Error SQL en " . static::$tabla . ": " . $e->getMessage());
            error_log("Query: " . $query);
            error_log("Params: " . print_r($params, true));
            static::$errores[] = "Error en consulta: " . $e->getMessage();
            return [];
        }
    }

    /**
     * Ejecutar consulta SQL personalizada que retorna datos
     */
    public static function query($sql, $params = []) {
        try {
            $stmt = self::getDB()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en query personalizada: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ejecutar consulta SQL que no retorna datos (INSERT, UPDATE, DELETE)
     */
    public static function execute($sql, $params = []) {
        try {
            $stmt = self::getDB()->prepare($sql);
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("Error ejecutando SQL: " . $e->getMessage());
            return false;
        }
    }

    protected static function crearObjeto($registro) {
        $objeto = new static;
        
        foreach ($registro as $key => $value) {
            if (property_exists($objeto, $key)) {
                $objeto->$key = $value;
            }
        }
        
        return $objeto;
    }

    // ========================================
    // MÉTODOS DE UTILIDAD
    // ========================================

    public function atributos() {
        $atributos = [];
        foreach (static::$columnasDB as $columna) {
            if ($columna === 'id') continue;
            $atributos[$columna] = $this->$columna ?? null;
        }
        return $atributos;
    }

    public function sanitizarAtributos() {
        $atributos = $this->atributos();
        $sanitized = [];
        
        foreach ($atributos as $key => $value) {
            if ($value !== null) {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }

    public function sincronizar($args = []) {
        foreach ($args as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public static function total() {
        try {
            $query = "SELECT COUNT(*) as total FROM " . static::$tabla;
            $stmt = self::getDB()->prepare($query);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'];
        } catch (Exception $e) {
            error_log("Error contando registros: " . $e->getMessage());
            return 0;
        }
    }

    public static function paginar($porPagina, $offset) {
        $query = "SELECT * FROM " . static::$tabla . " ORDER BY id DESC LIMIT ? OFFSET ?";
        return self::consultarSQL($query, [(int)$porPagina, (int)$offset]);
    }

    /**
     * Verificar si existe un registro con determinado valor
     */
    public static function exists($columna, $valor) {
        $query = "SELECT COUNT(*) as count FROM " . static::$tabla . " WHERE {$columna} = ?";
        $stmt = self::getDB()->prepare($query);
        $stmt->execute([$valor]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$resultado['count'] > 0;
    }

    // ========================================
    // GETTERS Y SETTERS
    // ========================================

    public function getId() {
        return $this->id;
    }

    public function existe() {
        return $this->existe;
    }

    public function toArray() {
        $array = [];
        foreach (static::$columnasDB as $columna) {
            $array[$columna] = $this->$columna ?? null;
        }
        return $array;
    }

    public function toJson() {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }
}