<?php
namespace MVC;

use Exception;
use Controllers\ApiController;

class Router {
    protected $rutasGET = [];
    protected $rutasPOST = [];
    protected $rutasPUT = [];
    protected $rutasDELETE = [];
    
    protected $middlewareGlobal = [];
    protected $grupoActual = '';
    protected $middlewareGrupo = [];

    public function get($url, $fn) {
        $this->rutasGET[$this->grupoActual . $url] = [
            'callback' => $fn,
            'middleware' => $this->middlewareGrupo
        ];
    }

    public function post($url, $fn) {
        $this->rutasPOST[$this->grupoActual . $url] = [
            'callback' => $fn,
            'middleware' => $this->middlewareGrupo
        ];
    }

    public function put($url, $fn) {
        $this->rutasPUT[$this->grupoActual . $url] = [
            'callback' => $fn,
            'middleware' => $this->middlewareGrupo
        ];
    }

    public function delete($url, $fn) {
        $this->rutasDELETE[$this->grupoActual . $url] = [
            'callback' => $fn,
            'middleware' => $this->middlewareGrupo
        ];
    }

    public function group($prefijo, $callback, $middleware = []) {
        $grupoAnterior = $this->grupoActual;
        $middlewareAnterior = $this->middlewareGrupo;
        
        $this->grupoActual = $prefijo;
        $this->middlewareGrupo = array_merge($this->middlewareGrupo, $middleware);
        
        $callback($this);
        
        $this->grupoActual = $grupoAnterior;
        $this->middlewareGrupo = $middlewareAnterior;
    }

    public function middleware($middleware) {
        $this->middlewareGlobal[] = $middleware;
    }

    protected function matchRoute($url, $patron) {
        preg_match_all('/\{([^}]+)\}/', $patron, $paramNames);
        $paramNames = $paramNames[1];
        
        $regexPatron = preg_replace('/\{([^}]+)\}/', '([^/]+)', $patron);
        $regexPatron = '#^' . $regexPatron . '$#';
        
        if (preg_match($regexPatron, $url, $matches)) {
            array_shift($matches);
            return array_combine($paramNames, $matches);
        }
        return false;
    }

    public function comprobarRutas() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $urlActual = $_SERVER['PATH_INFO'] ?? '/';
        $metodo = $_SERVER['REQUEST_METHOD'];
        $urlActual = rtrim($urlActual, '/') ?: '/';

        // Middleware global
        foreach ($this->middlewareGlobal as $middleware) {
            if (!$this->ejecutarMiddleware($middleware)) {
                return;
            }
        }

        $rutas = $this->obtenerRutasPorMetodo($metodo);
        if (empty($rutas)) {
            $this->mostrarError(405, "Método no permitido");
            return;
        }

        $rutaEncontrada = false;
        $parametros = [];
        $rutaData = null;

        // Coincidencia exacta
        if (isset($rutas[$urlActual])) {
            $rutaData = $rutas[$urlActual];
            $rutaEncontrada = true;
        } else {
            // Coincidencia con parámetros
            foreach ($rutas as $patron => $data) {
                $parametros = $this->matchRoute($urlActual, $patron);
                if ($parametros !== false) {
                    $rutaData = $data;
                    $rutaEncontrada = true;
                    break;
                }
            }
        }

        if ($rutaEncontrada) {
            $this->procesarRuta($rutaData, $parametros);
        } else {
            $this->mostrarError(404, "Página no encontrada");
        }
    }

    protected function obtenerRutasPorMetodo($metodo) {
        switch ($metodo) {
            case 'GET': return $this->rutasGET;
            case 'POST': return $this->rutasPOST;
            case 'PUT': return $this->rutasPUT;
            case 'DELETE': return $this->rutasDELETE;
            default: return [];
        }
    }

    protected function procesarRuta($rutaData, $parametros = []) {
        try {
            // Middleware de ruta
            if (isset($rutaData['middleware'])) {
                foreach ($rutaData['middleware'] as $middleware) {
                    if (!$this->ejecutarMiddleware($middleware)) {
                        return;
                    }
                }
            }

            $callback = $rutaData['callback'];
            
            if (is_string($callback)) {
                $this->ejecutarControlador($callback, $parametros);
            } else {
                $this->ejecutarControlador($callback, $parametros);
            }
        } catch (Exception $e) {
            error_log("Error en ruta: " . $e->getMessage());
            $this->mostrarError(500, "Error interno del servidor");
        }
    }

    protected function ejecutarControlador($callback, $parametros = []) {
        if (strpos($callback, '@') === false) {
            $this->mostrarError(500, "Formato de controlador inválido");
            return;
        }

        [$controlador, $metodo] = explode('@', $callback);
        $controladorClass = "Controllers\\{$controlador}";
        
        if (!class_exists($controladorClass)) {
            $this->mostrarError(404, "Controlador {$controlador} no encontrado");
            return;
        }

        $instancia = new $controladorClass();
        
        if (!method_exists($instancia, $metodo)) {
            $this->mostrarError(404, "Método {$metodo} no encontrado");
            return;
        }

        try {
            call_user_func_array([$instancia, $metodo], array_merge([$this], array_values($parametros)));
        } catch (Exception $e) {
            error_log("Error en controlador {$controlador}@{$metodo}: " . $e->getMessage());
            $this->mostrarError(500, "Error al procesar solicitud");
        }
    }

    protected function ejecutarMiddleware($middleware) {
        try {
            if (is_string($middleware)) {
                $middlewareClass = "Middleware\\{$middleware}";
                if (class_exists($middlewareClass)) {
                    $instancia = new $middlewareClass();
                    return $instancia->handle($this);
                }
            } elseif (is_callable($middleware)) {
                return $middleware($this);
            }
            return true;
        } catch (Exception $e) {
            error_log("Error en middleware: " . $e->getMessage());
            return false;
        }
    }

    public function render($view, $datos = []) {
        foreach ($datos as $key => $value) {
            $$key = $value;
        }
        
        ob_start();
        include_once __DIR__ . "/views/{$view}.php";
        $contenido = ob_get_clean();
        
        // ✅ FIX: NO incluir layout.php para rutas API
        $urlActual = $_SERVER['PATH_INFO'] ?? '/';
        if (strpos($urlActual, '/api/') !== 0) {
            include_once __DIR__ . '/views/layout.php';
        } else {
            // Para APIs, solo mostrar el contenido sin layout
            echo $contenido;
        }
    }

    public function json($data, $status = 200) {
        // ✅ FIX: Limpiar cualquier output anterior
        if (ob_get_level()) {
            ob_clean();
        }
        
        http_response_code($status);
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function redirect($url, $status = 302) {
        http_response_code($status);
        header("Location: {$url}");
        exit;
    }

    protected function mostrarError($codigo, $mensaje) {
        http_response_code($codigo);
        
        // ✅ Para APIs, devolver JSON error
        $urlActual = $_SERVER['PATH_INFO'] ?? '/';
        if (strpos($urlActual, '/api/') === 0) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $mensaje,
                'code' => $codigo
            ], JSON_PRETTY_PRINT);
            exit;
        }
        
        // Para páginas web, mostrar HTML error
        $archivoError = __DIR__ . "/views/errors/{$codigo}.php";
        if (file_exists($archivoError)) {
            include_once $archivoError;
        } else {
            echo "<h1>{$codigo} - {$mensaje}</h1>";
        }
        exit;
    }

    public function getPost($key = null, $filtro = FILTER_SANITIZE_FULL_SPECIAL_CHARS) {
        if ($key === null) {
            return filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? [];
        }
        return filter_input(INPUT_POST, $key, $filtro);
    }

    public function getGet($key = null, $filtro = FILTER_SANITIZE_FULL_SPECIAL_CHARS) {
        if ($key === null) {
            return filter_input_array(INPUT_GET, FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? [];
        }
        return filter_input(INPUT_GET, $key, $filtro);
    }

    public function validateCSRF() {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
        
        return $token && hash_equals($_SESSION['csrf_token'], $token);
    }

    public function generateCSRF() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}