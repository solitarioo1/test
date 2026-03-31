<?php
namespace Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class EmailService {
    private const MAX_INTENTOS = 2;
    private const TIMEOUT = 10; // segundos

    public static function enviarConfirmacionContacto(array $datosContacto, array $opciones = []): bool {
        // Validación básica de datos requeridos
        if (empty($datosContacto['email']) || empty($datosContacto['nombre'])) {
            error_log('[EmailService] Error: Datos incompletos para enviar email');
            return false;
        }

        // Configuración por defecto + opciones
        $config = array_merge([
            'copias_admin' => [$_ENV['MAIL_ADMIN_ADDRESS'] => 'Administrador IntiSmart'],
            'modo_prueba' => $_ENV['APP_ENV'] === 'development',
            'intentos' => 0
        ], $opciones);

        // Modo prueba (no envía realmente)
        if ($config['modo_prueba']) {
            error_log("[EmailService] Modo prueba - Simulando envío a: {$datosContacto['email']}");
            return true;
        }

        $mail = new PHPMailer(true);
        
        try {
            // Configuración SMTP
            $mail->isSMTP();
            $mail->Host = $_ENV['MAIL_HOST'];
            $mail->Port = $_ENV['MAIL_PORT'];
            $mail->Username = $_ENV['MAIL_USERNAME'];
            $mail->Password = $_ENV['MAIL_PASSWORD'];
            $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
            $mail->SMTPAuth = true;
            $mail->Timeout = self::TIMEOUT;
            $mail->SMTPDebug = $_ENV['APP_DEBUG'] ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF;

            // Remitente
            $mail->setFrom(
                $_ENV['MAIL_FROM_ADDRESS'], 
                $_ENV['MAIL_FROM_NAME']
            );
            
            // Destinatario principal
            $mail->addAddress(
                filter_var($datosContacto['email'], FILTER_SANITIZE_EMAIL),
                htmlspecialchars($datosContacto['nombre'])
            );

            // Copias administrativas
            foreach ($config['copias_admin'] as $email => $nombre) {
                $mail->addBCC($email, $nombre);
            }

            // Contenido del email
            $mail->Subject = 'Confirmación de cita - IntiSmart';
            $mail->isHTML(true);
            $mail->Body = self::crearTemplateConfirmacion($datosContacto);
            $mail->AltBody = self::crearTextoPlano($datosContacto);
            $mail->CharSet = 'UTF-8';

            // Intento de envío
            $enviado = $mail->send();
            
            if (!$enviado && $config['intentos'] < self::MAX_INTENTOS) {
                $config['intentos']++;
                return self::enviarConfirmacionContacto($datosContacto, $config);
            }

            return $enviado;

        } catch (Exception $e) {
            error_log(sprintf(
                '[EmailService] Error enviando email a %s: %s. Intento %d/%d',
                $datosContacto['email'],
                $mail->ErrorInfo,
                $config['intentos'] + 1,
                self::MAX_INTENTOS
            ));
            return false;
        }
    }

    private static function crearTemplateConfirmacion(array $datos): string {
        ob_start();
        include __DIR__ . '/../views/emails/confirmacion_contacto.php';
        return ob_get_clean();
    }

    private static function crearTextoPlano(array $datos): string {
        return sprintf(
            "Confirmación de Cita\n\n" .
            "Hola %s,\n\n" .
            "Tu cita ha sido programada para el %s a las %s.\n" .
            "Tipo: %s\n\n" .
            "¡Gracias por elegir IntiSmart!",
            $datos['nombre'],
            strftime('%A %d de %B del %Y', strtotime($datos['fecha_preferida'])),
            substr($datos['hora_preferida'], 0, 5),
            ($datos['tipo_cita'] === 'presencial' ? 'Presencial' : 'Virtual')
        );
    }
}