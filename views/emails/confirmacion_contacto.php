<?php
// Configuración regional para fechas en español
setlocale(LC_TIME, 'es_PE.UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Cita - IntiSmart</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .footer { margin-top: 20px; font-size: 0.9em; color: #666; }
        .details { background-color: #f8f9fa; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>¡Cita Confirmada!</h1>
        </div>
        
        <div class="content">
            <p><strong>Hola <?= htmlspecialchars($datos['nombre'] ?? '') ?>,</strong></p>
            <p>Gracias por solicitar una cita con IntiSmart. Aquí están los detalles:</p>
            
            <div class="details">
                <h3>Detalles de tu cita:</h3>
                <ul>
                    <li><strong>📅 Fecha:</strong> <?= htmlspecialchars(strftime('%A %d de %B del %Y', strtotime($datos['fecha_preferida'] ?? ''))) ?></li>
                    <li><strong>⏰ Hora:</strong> <?= htmlspecialchars(substr($datos['hora_preferida'] ?? '', 0, 5)) ?> horas</li>
                    <li><strong>📍 Tipo de atención:</strong> <?= htmlspecialchars(($datos['tipo_cita'] ?? '') === 'presencial' ? 'Presencial' : 'Virtual') ?></li>
                    <li><strong>🔍 Interés principal:</strong> 
                        <?php 
                        $interes = [
                            'detector_uv' => 'Detector UV',
                            'charla_salud' => 'Charla sobre protección UV',
                            'ambos' => 'Ambos servicios'
                        ];
                        echo htmlspecialchars($interes[$datos['tipo_interes'] ?? ''] ?? '');
                        ?>
                    </li>
                </ul>
            </div>
            
            <p>Nos pondremos en contacto contigo para confirmar los detalles finales.</p>
        </div>
        
        <div class="footer">
            <p>Si no solicitaste esta cita, por favor ignora este mensaje.</p>
            <p>© <?= date('Y') ?> IntiSmart. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>