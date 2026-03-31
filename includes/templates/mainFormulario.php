 <main class="contenido-formulario contenedor">
        <h2><span>Solicita una cita</span> para conocernos más</h2>
        <p class="descripcion-formulario">
            No esperes más, agenda tu cita y toma acción con el cuidado de tu piel. Nuestro dispositivo detector UV es la prevención que necesitas para protegerte del daño solar.
        </p>
        
 <!-- Mensajes de éxito/error simplificados -->
        <?php if (!empty($form_errores)): ?>
            <div class="mensaje-error">
                <strong>Por favor, corrige los siguientes errores:</strong>
                <ul>
                    <?php foreach ($form_errores as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php elseif (!empty($form_success)): ?>
            <div class="mensaje-success">
                <?= htmlspecialchars($form_success) ?>
            </div>
        <?php endif; ?>

        <form class="formulario" action="/contacto" method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            
            <fieldset>
                <legend>Tus Datos</legend>
                <div class="campo">
                    <label for="nombre">Nombre completo:</label>
                    <input 
                        id="nombre" 
                        name="nombre" 
                        type="text" 
                        placeholder="Tu nombre y apellido" 
                        required
                        minlength="2"
                        maxlength="50"
                        pattern="[A-Za-zÀ-ÿ\s]+"
                        value="<?= htmlspecialchars($form_data['nombre'] ?? '') ?>"
                    >
                </div>
    
                <div class="campo">
                    <label for="email">Correo electrónico:</label>
                    <input 
                        id="email" 
                        name="email" 
                        type="email" 
                        placeholder="Tu email" 
                        required
                        pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                        value="<?= htmlspecialchars($form_data['email'] ?? '') ?>"
                    >
                </div>
    
                <div class="campo">
                    <label for="tel">Teléfono de contacto:</label>
                    <input 
                        id="tel" 
                        name="telefono" 
                        type="tel" 
                        placeholder="Tu número de teléfono"
                        pattern="[0-9]{9,}"
                        required
                        value="<?= htmlspecialchars($form_data['telefono'] ?? '') ?>"
                    >
                </div>
                
                <div class="campo">
                    <label for="empresa">Empresa/organización:</label>
                    <input 
                        id="empresa" 
                        name="empresa" 
                        type="text" 
                        placeholder="Nombre de tu empresa u organización"
                        value="<?= htmlspecialchars($form_data['empresa'] ?? '') ?>"
                    >
                </div>
            </fieldset>
    
            <fieldset>
                <legend>Detalles para tu Atención</legend>
                
                <div class="campo">
                    <label for="tipo_cita">Tipo de Atención:</label>
                    <select id="tipo_cita" name="tipo_cita" required>
                        <option value="">-- Seleccione una opción --</option>
                        <option value="presencial" <?= isset($form_data['tipo_cita']) && $form_data['tipo_cita'] === 'presencial' ? 'selected' : '' ?>>Atención presencial</option>
                        <option value="virtual" <?= isset($form_data['tipo_cita']) && $form_data['tipo_cita'] === 'virtual' ? 'selected' : '' ?>>Atención virtual</option>
                    </select>
                </div>
                
                <div class="campo">
                    <label for="tipo_interes">¿Qué te interesa conocer?</label>
                    <select id="tipo_interes" name="tipo_interes" required>
                        <option value="">-- Seleccione una opción --</option>
                        <option value="detector_uv" <?= isset($form_data['tipo_interes']) && $form_data['tipo_interes'] === 'detector_uv' ? 'selected' : '' ?>>Detector UV y su funcionamiento</option>
                        <option value="charla_salud" <?= isset($form_data['tipo_interes']) && $form_data['tipo_interes'] === 'charla_salud' ? 'selected' : '' ?>>Charla sobre salud y protección UV</option>
                        <option value="ambos" <?= isset($form_data['tipo_interes']) && $form_data['tipo_interes'] === 'ambos' ? 'selected' : '' ?>>Ambos servicios</option>
                    </select>
                </div>
                
                <div class="campo">
                    <label for="fecha_preferida">Fecha preferida:</label>
                    <input 
                        id="fecha_preferida" 
                        name="fecha_preferida" 
                        type="date" 
                        min="<?= date('Y-m-d') ?>"
                        required
                        value="<?= htmlspecialchars($form_data['fecha_preferida'] ?? '') ?>"
                    >
                </div>
                
                <div class="campo">
                    <label for="hora_preferida">Hora preferida:</label>
                    <input 
                        id="hora_preferida" 
                        name="hora_preferida" 
                        type="time" 
                        min="09:00" 
                        max="17:00" 
                        step="any"

                        required
                        value="<?= htmlspecialchars($form_data['hora_preferida'] ?? '') ?>"
                    >
                    <small class="hora-info">Horario disponible: 9:00 AM - 5:00 PM (intervalos de 15 min)</small>
                </div>
    
                <div class="campo">
                    <label for="mensaje">Detalles adicionales (opcional):</label>
                    <textarea 
                        id="mensaje" 
                        name="mensaje" 
                        rows="4" 
                        cols="30" 
                        placeholder="Cuéntanos brevemente tu interés o cualquier pregunta que tengas..."
                    ><?= htmlspecialchars($form_data['mensaje'] ?? '') ?></textarea>
                </div>
            </fieldset>
    
            <fieldset>
                <legend>De dónde nos escribes</legend>
                <div class="campo">
                    <label for="departamento">Departamento:</label>
                    <select id="departamento" name="departamento" required>
                        <option value="">-- Seleccione --</option>
                        <?php
                        $departamentos = [
                            'AMA' => 'Amazonas', 'ANC' => 'Áncash', 'APU' => 'Apurímac',
                            'ARE' => 'Arequipa', 'AYA' => 'Ayacucho', 'CAJ' => 'Cajamarca',
                            'CAL' => 'Callao', 'CUS' => 'Cusco', 'HUV' => 'Huancavelica',
                            'HUC' => 'Huánuco', 'ICA' => 'Ica', 'JUN' => 'Junín',
                            'LAL' => 'La Libertad', 'LAM' => 'Lambayeque', 'LIM' => 'Lima',
                            'LOR' => 'Loreto', 'MDD' => 'Madre de Dios', 'MOQ' => 'Moquegua',
                            'PAS' => 'Pasco', 'PIU' => 'Piura', 'PUN' => 'Puno',
                            'SAM' => 'San Martín', 'TAC' => 'Tacna', 'TUM' => 'Tumbes',
                            'UCA' => 'Ucayali'
                        ];
                        
                        foreach ($departamentos as $codigo => $nombre) {
                            $selected = isset($form_data['departamento']) && $form_data['departamento'] === $codigo ? 'selected' : '';
                            echo "<option value=\"{$codigo}\" {$selected}>{$nombre}</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="campo">
                    <label for="ciudad">Ciudad/Distrito:</label>
                    <input 
                        id="ciudad" 
                        name="ciudad" 
                        type="text" 
                        placeholder="Tu ciudad o distrito" 
                        required
                        value="<?= htmlspecialchars($form_data['ciudad'] ?? '') ?>"
                    >
                </div>
            </fieldset>
            
            <div class="campo campo-checkbox">
                <input type="checkbox" id="newsletter" name="newsletter" value="si" <?= isset($form_data['newsletter']) && $form_data['newsletter'] === 'si' ? 'checked' : '' ?>>
                <label for="newsletter">Deseo recibir información sobre novedades y consejos de protección UV</label>
            </div>
                        
            <div class="campo campo-checkbox">
                <input type="checkbox" id="politica" name="politica" required <?= isset($form_data['politica']) ? 'checked' : '' ?>>
                <label for="politica">He leído y acepto la <a href="/politicaPrivacidad">política de privacidad</a></label>
            </div>
    
            <input class="btn" type="submit" value="Solicitar Cita">
        </form>
        
        <div class="info-adicional">
            <p>Te contactaremos para confirmar tu cita en un plazo de 24 horas. Para citas presenciales, un especialista te visitará con nuestro detector UV para una demostración personalizada.</p>
            <p>También puedes contactarnos directamente: <strong>intismartsac@gmail.com</strong> | <strong>+51994146924</strong></p>
        </div>
    </main>