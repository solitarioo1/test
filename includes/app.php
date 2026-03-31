<!-- app.php -->

<?php

/**
 * ========================================
 * 🚀 ARCHIVO DE INICIALIZACIÓN DE LA APLICACIÓN
 * ========================================
 * Este archivo configura todo lo necesario para que la aplicación funcione
 * Debe ser incluido en todos los archivos que necesiten acceso a la base de datos
 */

// Cargar Composer (autoload) - DEBE SER PRIMERO
require_once __DIR__ . '/../vendor/autoload.php';

// Cargar funciones generales
require_once __DIR__ . '/funciones.php';

// Importar clases necesarias
use Config\Database;
use Model\ActiveRecord;

// Cargar variables de entorno desde .env
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
    
    // Verificar variables críticas
    $required = [
        'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD', 
        'CHATBOT_DB_NAME', 'CHATBOT_DB_USER', 'CHATBOT_DB_PASSWORD',
        'APP_ENV'
    ];
    
    foreach ($required as $var) {
        if (empty($_ENV[$var])) {
            throw new Exception("Variable de entorno requerida no encontrada: {$var}");
        }
    }
    
} catch (Exception $e) {
    die("❌ Error cargando configuración: " . $e->getMessage());
}

// Configurar zona horaria
date_default_timezone_set('America/Lima');

// Configurar manejo de errores según el entorno
if ($_ENV['APP_ENV'] === 'development') {
    // Desarrollo: mostrar todos los errores
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    // Producción: no mostrar errores al usuario
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Inicializar conexiones de base de datos y ActiveRecord
try {
    // Obtener la instancia principal de la base de datos
    $mainDatabase = Database::getMainInstance();
    $mainConnection = $mainDatabase->getConnection();
    
    // Configurar la conexión para ActiveRecord
    ActiveRecord::setDB($mainConnection);
    
    // Verificar que ambas conexiones funcionen
    $chatbotDatabase = Database::getChatbotInstance();
    
    // Log de inicialización exitosa (solo en desarrollo)
    if ($_ENV['APP_DEBUG'] === 'true') {
        error_log("✅ Aplicación inicializada correctamente");
        error_log("🌍 Entorno: " . $_ENV['APP_ENV']);
        error_log("🗃️ Base de datos principal conectada: " . $_ENV['DB_NAME']);
        error_log("🤖 Base de datos chatbot conectada: " . $_ENV['CHATBOT_DB_NAME']);
    }
    
} catch (Exception $e) {
    error_log("❌ Error inicializando la aplicación: " . $e->getMessage());
    // No hacer die() — permitir que páginas estáticas carguen sin BD
}

/**
 * ========================================
 * 🛠️ FUNCIONES DE UTILIDAD GLOBALES
 * ========================================
 */

/**
 * Obtiene una variable de entorno con valor por defecto
 * @param string $key Clave de la variable
 * @param mixed $default Valor por defecto
 * @return mixed Valor de la variable o el valor por defecto
 */
function env($key, $default = null) {
    return $_ENV[$key] ?? $default;
}

/**
 * Verifica si la aplicación está en modo debug
 * @return bool
 */
function isDebug() {
    return env('APP_DEBUG') === 'true';
}

/**
 * Verifica si la aplicación está en producción
 * @return bool
 */
function isProduction() {
    return env('APP_ENV') === 'production';
}

/**
 * Obtiene la URL base de la aplicación
 * @return string URL base
 */
function getAppUrl() {
    return rtrim(env('APP_URL', 'http://localhost'), '/');
}

/**
 * Genera una URL completa basada en la ruta
 * @param string $path Ruta relativa
 * @return string URL completa
 */
function url($path = '') {
    return getAppUrl() . '/' . ltrim($path, '/');
}

/**
 * Función para logging personalizado
 * @param string $message Mensaje a loggear
 * @param string $level Nivel del log (INFO, ERROR, WARNING)
 */
function customLog($message, $level = 'INFO') {
    if (isDebug()) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] {$message}";
        error_log($logMessage);
    }
}

/**
 * Función para manejar errores de base de datos
 * @param Exception $e Excepción capturada
 * @param string $context Contexto donde ocurrió el error
 */
function handleDBError($e, $context = 'Database') {
    $message = "[{$context}] " . $e->getMessage();
    customLog($message, 'ERROR');
    
    if (isProduction()) {
        // En producción, no mostrar detalles del error
        http_response_code(500);
        die("Error interno del servidor. Contacte al administrador.");
    } else {
        // En desarrollo, mostrar el error completo
        die("Error en {$context}: " . $e->getMessage());
    }
}

/**
 * Obtiene la conexión principal de la base de datos
 * @return PDO
 */
function getMainDB() {
    return Database::getMainInstance()->getConnection();
}

/**
 * Obtiene la conexión de la base de datos del chatbot
 * @return PDO
 */
function getChatbotDB() {
    return Database::getChatbotInstance()->getConnection();
}

/**
 * Función para validar conexiones de base de datos
 * @return array Estado de las conexiones
 */
function checkDatabaseConnections() {
    $status = [
        'main_db' => false,
        'chatbot_db' => false,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    try {
        $mainDB = Database::getMainInstance();
        $status['main_db'] = $mainDB->isConnectionActive();
    } catch (Exception $e) {
        customLog("Error verificando conexión principal: " . $e->getMessage(), 'ERROR');
    }
    
    try {
        $chatbotDB = Database::getChatbotInstance();
        $status['chatbot_db'] = $chatbotDB->isConnectionActive();
    } catch (Exception $e) {
        customLog("Error verificando conexión chatbot: " . $e->getMessage(), 'ERROR');
    }
    
    return $status;
}

/**
 * Ejecuta una transacción en la base de datos principal
 * @param callable $callback Función que contiene las operaciones
 * @return mixed Resultado del callback
 */
function executeMainTransaction(callable $callback) {
    return Database::getMainInstance()->transaction($callback);
}

/**
 * Ejecuta una transacción en la base de datos del chatbot
 * @param callable $callback Función que contiene las operaciones
 * @return mixed Resultado del callback
 */
function executeChatbotTransaction(callable $callback) {
    return Database::getChatbotInstance()->transaction($callback);
}

// Registrar un shutdown handler para limpiar recursos
register_shutdown_function(function() {
    if (isDebug()) {
        customLog("🏁 Finalizando aplicación", 'INFO');
    }
    Database::closeAllConnections();
});

/**
 * ========================================
 * 🔧 CONFIGURACIONES ADICIONALES
 * ========================================
 */

// Establecer headers de seguridad básicos
if (!headers_sent()) {
    // Prevenir XSS
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    
    // Solo en producción, usar HTTPS
    if (isProduction()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// Configurar la sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    // Configurar parámetros de sesión
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isProduction() ? 1 : 0);
    
    session_start();
}

customLog("🚀 Aplicación totalmente inicializada", 'INFO');