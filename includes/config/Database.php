<?php

namespace Config;

use PDO;
use PDOException;
use Exception;

class Database {
    
    private static ?self $mainInstance = null;
    private static ?self $chatbotInstance = null;
    private ?PDO $connection = null;
    private array $config = [];
    private string $type;
    
    private function __construct(string $type = 'main') {
        $this->type = $type;
        $this->loadConfig();
        $this->connect();
    }
    
    private function loadConfig(): void {
        $baseConfig = [
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . ($_ENV['DB_CHARSET'] ?? 'utf8mb4'),
                PDO::ATTR_PERSISTENT => false,
                PDO::ATTR_TIMEOUT => 30
            ]
        ];
        
        if ($this->type === 'main') {
            $this->config = array_merge($baseConfig, [
                'database' => $_ENV['DB_NAME'] ?? 'main_db',
                'username' => $_ENV['DB_USER'] ?? 'root',
                'password' => $_ENV['DB_PASSWORD'] ?? ''
            ]);
        } else {
            $this->config = array_merge($baseConfig, [
                'database' => $_ENV['CHATBOT_DB_NAME'] ?? 'chatbot_db',
                'username' => $_ENV['CHATBOT_DB_USER'] ?? 'root',
                'password' => $_ENV['CHATBOT_DB_PASSWORD'] ?? ''
            ]);
        }
    }
    
    private function connect(): void {
        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                $this->config['host'],
                $this->config['port'],
                $this->config['database'],
                $this->config['charset']
            );
            
            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );
            
            if ($_ENV['APP_DEBUG'] === 'true') {
                error_log("✅ Conexión exitosa a {$this->type}: {$this->config['database']}");
            }
            
        } catch (PDOException $e) {
            $this->handleConnectionError($e);
        }
    }
    
    private function handleConnectionError(PDOException $e): void {
        error_log("❌ Error en {$this->type}: " . $e->getMessage());
        
        if ($_ENV['APP_ENV'] === 'production') {
            throw new Exception("Error de conexión. Contacte al administrador.");
        } else {
            throw new Exception("Error en {$this->type}: " . $e->getMessage());
        }
    }
    
    public static function getMainInstance(): self {
        if (self::$mainInstance === null) {
            self::$mainInstance = new self('main');
        }
        return self::$mainInstance;
    }
    
    public static function getChatbotInstance(): self {
        if (self::$chatbotInstance === null) {
            self::$chatbotInstance = new self('chatbot');
        }
        return self::$chatbotInstance;
    }
    
    public function getConnection(): PDO {
        if (!$this->isConnectionActive()) {
            $this->reconnect();
        }
        return $this->connection;
    }
    
    public function isConnectionActive(): bool {
        try {
            return $this->connection !== null && $this->connection->query('SELECT 1');
        } catch (PDOException $e) {
            error_log("❌ Conexión perdida ({$this->type}): " . $e->getMessage());
            return false;
        }
    }
    
    public function reconnect(): void {
        $this->connection = null;
        $this->connect();
    }
    
    public function transaction(callable $callback) {
        $pdo = $this->getConnection();
        
        try {
            $pdo->beginTransaction();
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("❌ Error en transacción ({$this->type}): " . $e->getMessage());
            throw $e;
        }
    }
    
    public function query(string $query, array $params = []): \PDOStatement {
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->prepare($query);
            
            // Bind parameters with proper type handling
            foreach ($params as $key => $value) {
                $type = is_int($value) ? PDO::PARAM_INT : 
                       (is_bool($value) ? PDO::PARAM_BOOL : PDO::PARAM_STR);
                $stmt->bindValue(is_int($key) ? $key + 1 : $key, $value, $type);
            }
            
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("❌ Error en query ({$this->type}): " . $e->getMessage());
            throw new Exception("Error en consulta: " . $e->getMessage());
        }
    }
    
    public function fetchOne(string $query, array $params = []) {
        return $this->query($query, $params)->fetch();
    }
    
    public function fetchAll(string $query, array $params = []): array {
        return $this->query($query, $params)->fetchAll();
    }
    
    public function getLastInsertId(): string {
        return $this->getConnection()->lastInsertId();
    }
    
    public function getInfo(): array {
        return [
            'type' => $this->type,
            'host' => $this->config['host'],
            'port' => $this->config['port'],
            'database' => $this->config['database'],
            'charset' => $this->config['charset'],
            'is_connected' => $this->isConnectionActive()
        ];
    }
    
    public static function getAllConnectionsInfo(): array {
        $info = [
            'timestamp' => date('Y-m-d H:i:s'),
            'environment' => $_ENV['APP_ENV'] ?? 'development'
        ];
        
        if (self::$mainInstance !== null) {
            $info['main_db'] = self::$mainInstance->getInfo();
        }
        
        if (self::$chatbotInstance !== null) {
            $info['chatbot_db'] = self::$chatbotInstance->getInfo();
        }
        
        return $info;
    }
    
    public function close(): void {
        $this->connection = null;
        if ($_ENV['APP_DEBUG'] === 'true') {
            error_log("🔌 Cerrando conexión {$this->type}");
        }
    }
    
    public static function closeAllConnections(): void {
        if (self::$mainInstance !== null) {
            self::$mainInstance->close();
            self::$mainInstance = null;
        }
        
        if (self::$chatbotInstance !== null) {
            self::$chatbotInstance->close();
            self::$chatbotInstance = null;
        }
        
        if ($_ENV['APP_DEBUG'] === 'true') {
            error_log("🔌 Todas las conexiones cerradas");
        }
    }
    
    private function __clone() {}
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}