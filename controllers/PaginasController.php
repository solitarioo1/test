<?php
namespace Controllers;

use MVC\Router;
use Model\ContactoFormulario;
use Services\EmailService;

class PaginasController {

    public static function index(Router $router) {
        $router->render('paginas/index', [
            'titulo' => 'Inicio - IntiSmart'
        ]);
    }

    public static function nosotros(Router $router) {
        $router->render('paginas/nosotros', [
            'titulo' => 'Nosotros - IntiSmart'
        ]);
    }

    public static function integrantes(Router $router) {
        $router->render('paginas/integrantes', [
            'titulo' => 'Integrantes - IntiSmart'
        ]);
    }

    public static function politicaPrivacidad(Router $router) {
        $router->render('paginas/politicaPrivacidad', [
            'titulo' => 'Política Privacidad - IntiSmart'
        ]);
    }

    public static function productos(Router $router) {
        $router->render('paginas/productos', [
            'titulo' => 'Producto - IntiSmart'
        ]);
    }

    public static function registros(Router $router) {
        $router->render('paginas/registros', [
            'titulo' => 'Registros - IntiSmart'
        ]);
    }

    public static function blogRadiacion(Router $router) {
        $router->render('paginas/blogRadiacion', [
            'titulo' => 'Blog - Radiación'
        ]);
    }

    /**
     * Página de contacto con formulario de citas
     * Incluye protecciones: CSRF, Rate Limiting, Validaciones
     */
    public static function contacto(Router $router) {
        $form_data = [];
        $form_errores = [];
        $form_success = null;
        $form_error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 🛡️ Validación CSRF
            if (!$router->validateCSRF()) {
                $form_error = 'Token CSRF inválido. Por favor, recarga la página.';
            } 
            // 🛡️ Verificación Rate Limiting
            elseif (!self::verificarRateLimit()) {
                $form_error = 'Demasiados intentos. Espera 5 minutos antes de volver a intentar.';
                error_log("Rate limit excedido para IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            } 
            else {
                // ✅ Procesar formulario
                try {
                    // Crear instancia del modelo con datos POST
                    $contacto = new ContactoFormulario($_POST);
                    
                    // Validar datos
                    $form_errores = $contacto->validar();

                    if (empty($form_errores)) {
                        // Intentar guardar en base de datos
                        if ($contacto->guardar()) {
                            // 📧 Enviar email de confirmación
                            $emailEnviado = EmailService::enviarConfirmacionContacto($_POST, [
                                'copias_admin' => [
                                    'admin@intismart.com' => 'Admin IntiSmart',
                                    'ventas@intismart.com' => 'Departamento de Ventas',
                                    'info@intismart.com' => 'Información General'
                                ],
                                'modo_prueba' => $_ENV['APP_ENV'] === 'development'
                            ]);

                            if ($emailEnviado) {
                                $form_success = '✅ ¡Tu cita ha sido agendada exitosamente! Hemos enviado un correo de confirmación a ' . htmlspecialchars($_POST['email']) . '.';
                                $form_data = []; // Limpiar formulario después del éxito
                                
                                // Log del éxito para auditoría
                                error_log("Cita agendada exitosamente para: " . $_POST['email'] . " - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                                
                            } else {
                                $form_success = '✅ Tu cita ha sido agendada correctamente. Nos pondremos en contacto contigo pronto.';
                                $form_error = '⚠️ Nota: Hubo un problema menor al enviar el correo de confirmación, pero tu cita está registrada.';
                                $form_data = []; // Limpiar formulario
                                
                                // Log del problema de email
                                error_log("Cita guardada pero email falló para: " . $_POST['email']);
                            }
                        } else {
                            // Error al guardar en BD
                            $form_error = 'Error al procesar tu solicitud. Por favor, inténtalo nuevamente.';
                            $form_data = $_POST; // Mantener datos para que el usuario no los pierda
                            
                            // Log del error de BD
                            error_log("Error guardando contacto en BD para: " . ($_POST['email'] ?? 'email_no_disponible'));
                        }
                    } else {
                        // Errores de validación
                        $form_data = $_POST; // Mantener datos ingresados
                    }
                    
                } catch (\Exception $e) {
                    // Error inesperado
                    $form_error = 'Error interno del sistema. Por favor, contacta directamente con nosotros.';
                    $form_data = $_POST;
                    
                    // Log del error crítico
                    error_log("Error crítico en formulario de contacto: " . $e->getMessage() . " - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                }
            }
        }

        // Renderizar vista con todos los datos
        $router->render('paginas/contacto', [
            'titulo' => 'Contacto - IntiSmart',
            'form_data' => $form_data,
            'form_errores' => $form_errores,
            'form_success' => $form_success,
            'form_error' => $form_error,
            'csrf_token' => $router->generateCSRF()
        ]);
    }

    /**
     * 🛡️ SISTEMA DE RATE LIMITING
     * Limita el número de intentos de envío por IP
     * 
     * @return bool true si está permitido, false si está bloqueado
     */
    private static function verificarRateLimit(): bool {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ahora = time();
        $limite = 3; // máximo 3 intentos
        $ventana = 300; // en 5 minutos (300 segundos)
        
        // Inicializar array de intentos si no existe
        if (!isset($_SESSION['form_attempts'])) {
            $_SESSION['form_attempts'] = [];
        }
        
        // Inicializar intentos para esta IP si no existen
        if (!isset($_SESSION['form_attempts'][$ip])) {
            $_SESSION['form_attempts'][$ip] = [];
        }
        
        // Limpiar intentos antiguos (fuera de la ventana de tiempo)
        $_SESSION['form_attempts'][$ip] = array_filter(
            $_SESSION['form_attempts'][$ip], 
            function($tiempo) use ($ahora, $ventana) {
                return ($ahora - $tiempo) < $ventana;
            }
        );
        
        // Verificar si se ha excedido el límite
        if (count($_SESSION['form_attempts'][$ip]) >= $limite) {
            return false; // Bloqueado por rate limiting
        }
        
        // Registrar intento actual
        $_SESSION['form_attempts'][$ip][] = $ahora;
        
        return true; // Permitido
    }

    /**
     * 🧹 MÉTODO DE UTILIDAD: Limpiar datos de rate limiting antiguos
     * Puede llamarse periódicamente para limpiar la sesión
     */
    public static function limpiarRateLimitAntiguo(): void {
        if (!isset($_SESSION['form_attempts'])) {
            return;
        }
        
        $ahora = time();
        $ventana = 300; // 5 minutos
        
        foreach ($_SESSION['form_attempts'] as $ip => $intentos) {
            $_SESSION['form_attempts'][$ip] = array_filter(
                $intentos, 
                function($tiempo) use ($ahora, $ventana) {
                    return ($ahora - $tiempo) < $ventana;
                }
            );
            
            // Eliminar IPs sin intentos recientes
            if (empty($_SESSION['form_attempts'][$ip])) {
                unset($_SESSION['form_attempts'][$ip]);
            }
        }
    }

    /**
     * 📊 MÉTODO DE UTILIDAD: Obtener estadísticas de rate limiting (para admin)
     */
    public static function obtenerEstadisticasRateLimit(): array {
        if (!isset($_SESSION['form_attempts'])) {
            return ['total_ips' => 0, 'intentos_activos' => 0];
        }
        
        $ahora = time();
        $ventana = 300; // 5 minutos
        $intentosActivos = 0;
        
        foreach ($_SESSION['form_attempts'] as $intentos) {
            $intentosRecientes = array_filter(
                $intentos, 
                function($tiempo) use ($ahora, $ventana) {
                    return ($ahora - $tiempo) < $ventana;
                }
            );
            $intentosActivos += count($intentosRecientes);
        }
        
        return [
            'total_ips' => count($_SESSION['form_attempts']),
            'intentos_activos' => $intentosActivos,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}