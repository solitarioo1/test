/**
 * CHATBOT.JS - Cliente JavaScript para IntiBot (VERSIÓN SANITIZADA SIMPLE)
 * Mantiene la lógica original + sanitización básica
 */

class ChatbotManager {
    constructor() {
        this.sessionId = null;
        this.isOpen = false;
        this.isTyping = false;
        this.mensajes = [];
        
        // Elementos DOM
        this.widget = null;
        this.body = null;
        this.messages = null;
        this.input = null;
        this.sendBtn = null;
        this.toggleIcon = null;
        
        this.init();
    }

    init() {
        console.log('🤖 Inicializando ChatBot...');
        
        // Configurar elementos DOM
        this.configurarElementos();
        
        // Configurar eventos
        this.configurarEventos();
        
        // Generar session ID
        this.sessionId = this.generarSessionId();
        
        // Mostrar mensaje de bienvenida
        this.mostrarMensajeBienvenida();
        
        console.log('✅ ChatBot inicializado');
    }

    configurarElementos() {
        this.widget = document.getElementById('chatbot-widget');
        this.body = document.getElementById('chatbot-body');
        this.messages = document.getElementById('chatbot-messages');
        this.input = document.getElementById('chatbot-user-input');
        this.sendBtn = document.getElementById('chatbot-send-btn');
        this.toggleIcon = document.getElementById('chatbot-toggle-icon');

        if (!this.widget || !this.messages || !this.input || !this.sendBtn) {
            console.error('❌ Elementos del chatbot no encontrados');
            return;
        }
    }

    configurarEventos() {
        // Toggle chatbot
        document.getElementById('chatbot-header').addEventListener('click', () => {
            this.toggleChatbot();
        });

        // Enviar mensaje
        this.sendBtn.addEventListener('click', () => {
            this.enviarMensaje();
        });

        // Enter para enviar
        this.input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.enviarMensaje();
            }
        });

        // Auto-resize del input
        this.input.addEventListener('input', () => {
            this.adjustInputHeight();
        });
    }

    // ========================================
    // 🛡️ FUNCIONES DE SANITIZACIÓN (NUEVAS)
    // ========================================

    /**
     * Sanitizar texto de entrada del usuario
     */
    sanitizarTexto(texto) {
        if (typeof texto !== 'string') {
            return '';
        }

        // Límite de longitud
        texto = texto.substring(0, 500);

        // Remover HTML tags
        texto = texto.replace(/<[^>]*>/g, '');
        
        // Remover scripts y eventos
        texto = texto.replace(/javascript:/gi, '');
        texto = texto.replace(/on\w+\s*=/gi, '');
        
        // Normalizar espacios
        texto = texto.replace(/\s+/g, ' ');
        
        return texto.trim();
    }

    /**
     * Escapar HTML para mostrar en el DOM
     */
    escaparHTML(texto) {
        const div = document.createElement('div');
        div.textContent = texto;
        return div.innerHTML;
    }

    // ========================================
    // INTERFAZ DE USUARIO (ORIGINAL)
    // ========================================

    toggleChatbot() {
        this.isOpen = !this.isOpen;
        
        if (this.isOpen) {
            this.widget.classList.remove('minimized');
            this.toggleIcon.textContent = '▼';
            this.input.focus();
        } else {
            this.widget.classList.add('minimized');
            this.toggleIcon.textContent = '▲';
        }
    }

    mostrarMensajeBienvenida() {
        const mensajeBienvenida = `¡Hola! 👋 Soy IntiBot de IntiSmart.

Te ayudo con:
• 📊 Datos UV en tiempo real
• 🛡️ Información sobre protección solar
• 🔬 Dispositivos INTI UV+
• 📅 Agendar citas y demostraciones

¿En qué puedo ayudarte?`;

        this.agregarMensaje('bot', mensajeBienvenida);
    }

    agregarMensaje(tipo, contenido, opciones = {}) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `chatbot-message ${tipo}`;
        
        const timestamp = new Date().toLocaleTimeString('es-PE', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });

        // 🛡️ CAMBIO: Formatear mensaje de forma segura
        messageDiv.innerHTML = `
            <div class="message-content">
                <div class="message-text">${this.formatearMensajeSeguro(contenido)}</div>
                <div class="message-time">${this.escaparHTML(timestamp)}</div>
            </div>
        `;

        // Agregar indicadores especiales
        if (opciones.tipo === 'datos_uv') {
            messageDiv.classList.add('message-with-data');
        }

        this.messages.appendChild(messageDiv);
        this.scrollToBottom();
        
        // Guardar en memoria
        this.mensajes.push({
            tipo,
            contenido,
            timestamp: new Date(),
            opciones
        });
    }

    // 🛡️ CAMBIO: Formatear mensaje de forma segura
    formatearMensajeSeguro(contenido) {
        // Escapar HTML primero
        let formateado = this.escaparHTML(contenido);
        
        // Convertir saltos de línea a <br>
        formateado = formateado.replace(/\n/g, '<br>');
        
        // Convertir emojis de texto básicos
        formateado = formateado.replace(/:\)/g, '😊');
        formateado = formateado.replace(/:\(/g, '😞');
        
        return formateado;
    }

    mostrarIndicadorEscribiendo() {
        const typingDiv = document.createElement('div');
        typingDiv.className = 'chatbot-message bot typing';
        typingDiv.id = 'typing-indicator';
        typingDiv.innerHTML = `
            <div class="message-content">
                <div class="typing-animation">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        `;
        
        this.messages.appendChild(typingDiv);
        this.scrollToBottom();
    }

    removerIndicadorEscribiendo() {
        const typingIndicator = document.getElementById('typing-indicator');
        if (typingIndicator) {
            typingIndicator.remove();
        }
    }

    scrollToBottom() {
        setTimeout(() => {
            this.messages.scrollTop = this.messages.scrollHeight;
        }, 100);
    }

    adjustInputHeight() {
        this.input.style.height = 'auto';
        this.input.style.height = Math.min(this.input.scrollHeight, 100) + 'px';
    }

    // ========================================
    // PROCESAMIENTO DE MENSAJES (ORIGINAL + SANITIZACIÓN)
    // ========================================

    async enviarMensaje() {
        // 🛡️ CAMBIO: Sanitizar mensaje antes de procesar
        const mensaje = this.sanitizarTexto(this.input.value);
        
        if (!mensaje || this.isTyping) {
            return;
        }

        // Limpiar input
        this.input.value = '';
        this.adjustInputHeight();
        
        // Mostrar mensaje del usuario
        this.agregarMensaje('usuario', mensaje);
        
        // Marcar como escribiendo
        this.isTyping = true;
        this.sendBtn.disabled = true;
        this.mostrarIndicadorEscribiendo();

        try {
            // Verificar si es FAQ primero (opcional)
            const respuestaFAQ = this.verificarFAQ(mensaje);
            
            if (respuestaFAQ) {
                // Respuesta rápida con FAQ
                setTimeout(() => {
                    this.removerIndicadorEscribiendo();
                    this.agregarMensaje('bot', respuestaFAQ.respuesta, { tipo: 'faq' });
                    this.finalizarEnvio();
                }, 800);
            } else {
                // Procesar con API
                await this.procesarConAPI(mensaje);
            }

        } catch (error) {
            console.error('❌ Error enviando mensaje:', error);
            this.removerIndicadorEscribiendo();
            this.agregarMensaje('bot', 'Lo siento, hubo un error. Por favor intenta nuevamente.', { tipo: 'error' });
            this.finalizarEnvio();
        }
    }

    verificarFAQ(mensaje) {
        // Verificación básica de FAQs
        const mensajeLimpio = mensaje.toLowerCase();
        
        // Saludos
        if (/^(hola|hi|hey|buenos días|buenas tardes)/.test(mensajeLimpio)) {
            return {
                respuesta: '¡Hola! 👋 ¿En qué puedo ayudarte con información sobre protección UV o nuestros dispositivos?'
            };
        }
        
        // Precios
        if (mensajeLimpio.includes('precio') || mensajeLimpio.includes('costo')) {
            return {
                respuesta: `💰 **Precios INTI UV+:**

🏫 Colegios Privados: S/3,500 - S/4,200
🏛️ Colegios Públicos: S/2,800
🎓 Universidades: S/4,500
💳 Modelo Freemium: S/0 inicial + S/200/mes

¿Te interesa algún sector específico? Puedo agendar una cita para una cotización personalizada.`
            };
        }

        return null; // No es FAQ, procesar con API
    }

    async procesarConAPI(mensaje) {
        try {
            // Llamar a la API del chatbot
            const response = await fetch('/api/chatbot/mensaje', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    mensaje: mensaje,
                    session_id: this.sessionId
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP Error: ${response.status}`);
            }

            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Error en la respuesta');
            }

            // Mostrar respuesta
            this.removerIndicadorEscribiendo();
            this.agregarMensaje('bot', data.data.respuesta, { 
                tipo: data.data.tipo,
                tiempo_respuesta: data.data.tiempo_respuesta 
            });

            this.finalizarEnvio();

        } catch (error) {
            console.error('❌ Error API chatbot:', error);
            this.removerIndicadorEscribiendo();
            
            // Mensaje de fallback
            const mensajeFallback = this.obtenerMensajeFallback(mensaje);
            this.agregarMensaje('bot', mensajeFallback, { tipo: 'fallback' });
            
            this.finalizarEnvio();
        }
    }

    obtenerMensajeFallback(mensaje) {
        const mensajeLimpio = mensaje.toLowerCase();
        
        if (mensajeLimpio.includes('uv') || mensajeLimpio.includes('radiación')) {
            return '🌞 Te ayudo con información sobre radiación UV. Nuestros sensores monitorean los niveles UV en tiempo real. ¿Te gustaría conocer más sobre nuestros dispositivos INTI UV+ o agendar una demostración?';
        }
        
        if (mensajeLimpio.includes('cita') || mensajeLimpio.includes('agendar')) {
            return '📅 ¡Perfecto! Puedo ayudarte a agendar una cita. Ofrecemos citas presenciales y virtuales para demostraciones. Puedes agendar directamente en nuestra página de contacto.';
        }
        
        return '🤖 Disculpa, no pude procesar tu mensaje en este momento. Te ayudo con información sobre protección UV, dispositivos INTI UV+ y puedo asistirte para agendar citas. ¿Podrías ser más específico?';
    }

    finalizarEnvio() {
        this.isTyping = false;
        this.sendBtn.disabled = false;
        this.input.focus();
    }

    // ========================================
    // UTILIDADES (ORIGINAL)
    // ========================================

    generarSessionId() {
        return 'chat_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    async cargarHistorial() {
        try {
            const response = await fetch(`/api/chatbot/historial/${this.sessionId}`);
            const data = await response.json();
            
            if (data.success && data.data.mensajes) {
                data.data.mensajes.forEach(msg => {
                    this.agregarMensaje('usuario', msg.usuario);
                    this.agregarMensaje('bot', msg.bot, { tipo: msg.tipo });
                });
            }
        } catch (error) {
            console.warn('⚠️ No se pudo cargar historial:', error);
        }
    }

    limpiarChat() {
        this.messages.innerHTML = '';
        this.mensajes = [];
        this.sessionId = this.generarSessionId();
        this.mostrarMensajeBienvenida();
        
        // Resetear estados
        this.isTyping = false;
        this.sendBtn.disabled = false;
    }

    // ========================================
    // MÉTODOS PÚBLICOS (ORIGINAL)
    // ========================================

    abrirChat() {
        if (!this.isOpen) {
            this.toggleChatbot();
        }
    }

    cerrarChat() {
        if (this.isOpen) {
            this.toggleChatbot();
        }
    }

    enviarMensajeProgramatico(mensaje) {
        this.input.value = mensaje;
        this.enviarMensaje();
    }

    resetearEstados() {
        this.isTyping = false;
        this.sendBtn.disabled = false;
        this.removerIndicadorEscribiendo();
        console.log('🔄 Estados reseteados manualmente');
    }
}

// ========================================
// INICIALIZACIÓN (ORIGINAL)
// ========================================

document.addEventListener('DOMContentLoaded', () => {
    console.log('🤖 Inicializando interfaz de chatbot...');
    
    // Crear instancia global
    window.chatbotManager = new ChatbotManager();
    
    console.log('✅ Interfaz de chatbot lista');
});

// Funciones globales para uso externo (ORIGINAL)
window.chatbotUtils = {
    abrir: () => window.chatbotManager?.abrirChat(),
    cerrar: () => window.chatbotManager?.cerrarChat(),
    enviar: (mensaje) => window.chatbotManager?.enviarMensajeProgramatico(mensaje),
    limpiar: () => window.chatbotManager?.limpiarChat(),
    resetear: () => window.chatbotManager?.resetearEstados()
};