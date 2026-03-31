<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/app.php';

use MVC\Router;
use Controllers\PaginasController;
use Controllers\ApiController;
use Controllers\ChatbotController;

$router = new Router(); 

$router->get('/', 'PaginasController@index');  
$router->get('/index', 'PaginasController@index');  
$router->get('/nosotros', 'PaginasController@nosotros');
$router->get('/registros', 'PaginasController@registros');
$router->post('/registros', 'PaginasController@registros');
$router->get('/productos', 'PaginasController@productos');
$router->get('/integrantes', 'PaginasController@integrantes');
$router->get('/blogRadiacion', 'PaginasController@blogRadiacion');
$router->get('/politicaPrivacidad', 'PaginasController@politicaPrivacidad');
$router->get('/contacto', 'PaginasController@contacto');
$router->post('/contacto', 'PaginasController@contacto');


// ========================================
// GRUPO DE RUTAS API - RADIACIÓN UV
// ========================================

$router->group('/api', function($router) {
    
    // === ESTACIONES ===
    
    // Obtener todas las estaciones activas (para sidebar y mapa)
    $router->get('/estaciones', 'ApiController@estaciones');
    
    // Obtener solo coordenadas de estaciones (optimizado para mapa)
    $router->get('/estaciones/coordenadas', 'ApiController@coordenadasEstaciones');
    
    // === REGISTROS UV ===
    
    // Registros del día actual para una estación (8am-5pm) - para gráfica
    $router->get('/registros/dia/{estacion_id}', 'ApiController@registrosDia');
    
    // Registros de fecha específica para una estación (8am-5pm)
    $router->get('/registros/dia/{estacion_id}/{fecha}', 'ApiController@registrosDia');
    
    // Últimos registros de todas las estaciones activas
    $router->get('/registros/ultimos', 'ApiController@ultimosRegistros');
    
    // Registros por rango personalizado (POST con parámetros)
    $router->post('/registros/rango', 'ApiController@registrosPorRango');
    
    // === ESTACIÓN ESPECÍFICA ===
    
    // Último registro de una estación específica (tiempo real)
    $router->get('/estacion/{estacion_id}/ultimo', 'ApiController@ultimoRegistro');
    
    // === ESTADO Y ESTADÍSTICAS ===
    
    // Estado del sistema (si está registrando datos)
    $router->get('/estado',  'ApiController@estado');
    
    // Estadísticas del día actual
    $router->get('/estadisticas', 'ApiController@estadisticas');
    
    // Estadísticas de fecha específica
    $router->get('/estadisticas/{fecha}', 'ApiController@estadisticas');
    
    // === UTILIDADES ===
    // Dashboard completo (estaciones + últimos registros + estado)
    $router->get('/dashboard', 'ApiController@dashboard');
    
    // Endpoint de prueba para verificar que la API funciona
    $router->get('/test', 'ApiController@test');
    
}, ['middleware' => ['cors']]); // Middleware CORS para APIs

// ========================================
// GRUPO DE RUTAS API - CHATBOT
// ========================================
// $router->get('/api/chatbot/test', 'ChatbotController@test');

$router->group('/api/chatbot', function($router) {
    
    // Procesar mensaje del usuario
    $router->post('/mensaje', 'ChatbotController@mensaje');
    
    // Obtener historial de conversación
    $router->get('/historial/{session_id}', 'ChatbotController@historial');
    
    // Test de conectividad (Gemini + BD)
    $router->get('/test', 'ChatbotController@test');
    
}, ['middleware' => ['cors']]);

$router->comprobarRutas();