/**
 * CHATBOT FAQS - IntiSmart (VERSIÓN SANITIZADA)
 * Preguntas frecuentes estáticas para ahorrar tokens de Gemini
 */

const ChatbotFAQs = {
    // ========================================
    // CONFIGURACIÓN
    // ========================================
    config: {
        umbralSimilitud: 0.7, // Umbral para considerar una coincidencia válida
        maxRespuestasAlternativas: 3,
        enableLogging: true,
        maxInputLength: 500, // 🛡️ Límite de entrada
        maxPatronLength: 100 // 🛡️ Límite de patrones
    },

    // ========================================
    // BASE DE CONOCIMIENTO ESTÁTICA
    // ========================================
    faqs: {
        // === SALUDOS Y CORTESÍA ===
        saludos: {
            patrones: [
                'hola', 'buenos días', 'buenas tardes', 'buenas noches', 'hey', 'hi',
                'qué tal', 'cómo estás', 'saludos', 'buen día'
            ],
            respuestas: [
                '¡Hola! Soy IntiBot de IntiSmart 👋. Te ayudo con información sobre protección UV, nuestros dispositivos INTI UV+ y puedo agendar citas. ¿En qué puedo ayudarte?',
                '¡Hola! Bienvenido a IntiSmart. Estoy aquí para ayudarte con todo sobre radiación UV y nuestros dispositivos de monitoreo. ¿Qué necesitas saber?'
            ],
            tipo: 'estatica'
        },

        despedidas: {
            patrones: [
                'adiós', 'bye', 'hasta luego', 'gracias', 'chau', 'nos vemos',
                'hasta la vista', 'me voy', 'eso es todo'
            ],
            respuestas: [
                'Gracias por contactar IntiSmart. Recuerda protegerte del sol y cuida tu salud. ¡Hasta pronto! ☀️🛡️',
                '¡Hasta pronto! Si necesitas más información sobre protección UV, estaré aquí. Cuídate del sol 🌞'
            ],
            tipo: 'estatica'
        },

        // === INFORMACIÓN EMPRESA ===
        queEsIntismart: {
            patrones: [
                'qué es intismart', 'quiénes son', 'sobre la empresa', 'qué hacen',
                'a qué se dedican', 'información empresa', 'misión', 'empresa'
            ],
            respuestas: [
                'IntiSmart desarrolla soluciones tecnológicas para alerta temprana ambiental. Nuestro propósito es proteger vidas mediante dispositivos que miden radiación UV, temperatura y humedad en tiempo real. Conectamos ciencia, tecnología y personas ante los efectos del cambio climático. 🌡️📊'
            ],
            tipo: 'estatica'
        },

        // === PRODUCTO INTI UV+ ===
        queEsIntiUV: {
            patrones: [
                'qué es inti uv', 'inti uv+', 'dispositivo', 'producto', 'detector uv',
                'sensor uv', 'medidor radiación', 'aparato'
            ],
            respuestas: [
                'El INTI UV+ es nuestro dispositivo que mide en tiempo real:\n📊 Radiación Ultravioleta (UV)\n🌡️ Temperatura ambiente\n💧 Humedad relativa\n\nCaracterísticas:\n✅ Visualización desde cualquier ángulo\n✅ Fácil instalación\n✅ Compatible con plataformas digitales\n✅ Alerta sonora\n✅ Recolección de datos automática'
            ],
            tipo: 'estatica'
        },

        // === PRECIOS ===
        precios: {
            patrones: [
                'precio', 'costo', 'cuánto cuesta', 'cotización', 'tarifa', 'presupuesto',
                'valor', 'inversión', 'cuánto vale'
            ],
            respuestas: [
                'Nuestros precios del INTI UV+ varían según el sector:\n\n🏫 Colegios Privados: S/3,500 - S/4,200\n🏛️ Colegios Públicos: S/2,800\n🎓 Universidades: S/4,500\n💰 Modelo Freemium: S/0 inicial + S/200/mes x 24 meses\n\n¿Te interesa algún sector específico? Puedo agendar una cita para una cotización personalizada.'
            ],
            tipo: 'estatica'
        },

        // === SERVICIOS ===
        servicios: {
            patrones: [
                'servicios', 'qué ofrecen', 'capacitaciones', 'alquiler', 'reportes',
                'soporte', 'mantenimiento', 'instalación'
            ],
            respuestas: [
                'IntiSmart ofrece:\n\n📦 PRODUCTOS:\n• Venta de dispositivos INTI UV+\n\n🛠️ SERVICIOS:\n• Capacitaciones en protección UV\n• Reportes y análisis de datos\n• Alquiler mensual de dispositivos\n• Instalación y configuración\n• Soporte técnico\n• Mantenimiento preventivo\n\n¿Qué servicio te interesa más?'
            ],
            tipo: 'estatica'
        },

        // === FOTOTIPOS DE PIEL ===
        fototiposPiel: {
            patrones: [
                'tipo de piel', 'fototipo', 'piel clara', 'piel oscura', 'qué tipo soy',
                'clasificación piel', 'piel sensible', 'bronceado'
            ],
            respuestas: [
                'Los fototipos de piel son:\n\n☀️ FOTOTIPO I: Piel muy pálida, pecas, siempre se quema\n☀️ FOTOTIPO II: Piel blanca, generalmente se quema\n☀️ FOTOTIPO III: Piel blanca a morena clara, se broncea gradualmente\n☀️ FOTOTIPO IV: Piel morena clara, pocas veces se quema\n☀️ FOTOTIPO V: Piel morena, raramente se quema\n☀️ FOTOTIPO VI: Piel oscura, nunca se quema\n\n¿Quieres saber cómo protegerte según tu tipo de piel?'
            ],
            tipo: 'estatica'
        },

        // === RECOMENDACIONES DE PROTECCIÓN ===
        proteccionUV: {
            patrones: [
                'cómo protegerme', 'protección solar', 'cuidados piel', 'bloqueador',
                'recomendaciones', 'consejos protección', 'evitar quemaduras'
            ],
            respuestas: [
                'Recomendaciones de protección UV:\n\n🧴 Usa bloqueador SPF 50+ (reaplicar cada 2 horas)\n👒 Usa sombrero de ala ancha\n🕶️ Lentes con protección UV\n👕 Ropa protectora de manga larga\n🕐 Evita exposición 10 AM - 4 PM\n🌳 Busca sombra\n💧 Mantente hidratado\n\n¿Sabías que 15 minutos al sol pueden afectarte más de lo que crees?'
            ],
            tipo: 'estatica'
        },

        // === LEY Y REGLAMENTO ===
        leyMinsa: {
            patrones: [
                'ley 30102', 'reglamento', 'obligatorio', 'minsa', 'normativa',
                'legal', 'cumplimiento', 'ministerio salud'
            ],
            respuestas: [
                'La Ley N° 30102 y su reglamento (Resolución Ministerial N° 178-2024/MINSA) establecen:\n\n⚖️ Medidas preventivas contra radiación solar\n🏭 Obligatorio para empresas mineras implementar protocolos\n👷 Protección para trabajadores expuestos a radiación UV\n📋 Marco legal fiscalizable por el Estado\n💼 Sanciones por incumplimiento\n\nEs especialmente importante para minería, construcción, agricultura y pesca.'
            ],
            tipo: 'estatica'
        },

        // === CONTACTO ===
        contacto: {
            patrones: [
                'contacto', 'teléfono', 'email', 'dirección', 'cómo contactar',
                'comunicarse', 'llamar', 'escribir'
            ],
            respuestas: [
                'Puedes contactarnos:\n\n📞 Teléfono: 994-146-924 (Gabriel Miguel)\n📧 Email: info@intismart.com\n🌐 Web: www.intismart.com\n⏰ Horario: Lunes a Viernes 9:00 AM - 6:00 PM\n\n¿Prefieres agendar una cita virtual o presencial?'
            ],
            tipo: 'estatica'
        },

        // === AGENDAR CITAS ===
        agendarCita: {
            patrones: [
                'agendar cita', 'cita', 'reunión', 'visita', 'demostración',
                'ver producto', 'presentación', 'agenda'
            ],
            respuestas: [
                'Perfecto, puedo ayudarte a agendar una cita. Ofrecemos:\n\n💻 CITA VIRTUAL: Presentación online del producto\n🏢 CITA PRESENCIAL: Visita con demostración del INTI UV+\n\nPara agendar, necesito:\n• Tu nombre y empresa\n• Fecha preferida\n• Tipo de cita (virtual/presencial)\n• Tu interés (detector UV / charla salud / ambos)\n\n¿Qué tipo de cita prefieres?'
            ],
            tipo: 'requiere_seguimiento'
        },

        // === INSTALACIONES Y CASOS DE ÉXITO ===
        instalaciones: {
            patrones: [
                'dónde instalado', 'casos éxito', 'referencias', 'unalm', 'universidad',
                'clientes', 'proyectos'
            ],
            respuestas: [
                'Tenemos instalaciones exitosas en:\n\n🎓 Universidad Nacional Agraria La Molina (UNALM)\n🤝 Alianzas estratégicas con instituciones educativas\n🏫 Colegios privados y públicos emblemáticos\n🏭 Empresas del sector minero y construcción\n\nNuestro objetivo es llegar a 500 unidades en el primer año. ¿Te interesa ser parte de nuestros casos de éxito?'
            ],
            tipo: 'estatica'
        },

        // === DATOS UV EN TIEMPO REAL ===
        datosUV: {
            patrones: [
                'datos uv actuales', 'índice uv hoy', 'radiación actual', 'temperatura ahora',
                'humedad actual', 'datos tiempo real', 'registros hoy'
            ],
            respuestas: [
                'Para consultar datos UV en tiempo real, puedo ayudarte con información de nuestras estaciones activas. Los datos incluyen:\n\n📊 Índice UV actual\n🌡️ Temperatura ambiente\n💧 Humedad relativa\n📈 Gráficas del día (8 AM - 5 PM)\n\n¿De qué estación te interesa conocer los datos?'
            ],
            tipo: 'requiere_bd'
        }
    },

    // ========================================
    // 🛡️ MÉTODOS DE SANITIZACIÓN
    // ========================================

    /**
     * Sanitizar entrada del usuario
     */
    sanitizarEntrada(input) {
        if (typeof input !== 'string') {
            return '';
        }

        // Límite de longitud
        if (input.length > this.config.maxInputLength) {
            input = input.substring(0, this.config.maxInputLength);
        }

        // Remover caracteres peligrosos
        input = input.replace(/[<>\"'&]/g, ''); // XSS básico
        input = input.replace(/javascript:/gi, ''); // URLs maliciosas
        input = input.replace(/on\w+\s*=/gi, ''); // Eventos HTML
        input = input.replace(/data:/gi, ''); // Data URLs
        
        // Normalizar espacios y caracteres de control
        input = input.replace(/[\x00-\x1F\x7F]/g, ''); // Caracteres de control
        input = input.replace(/\s+/g, ' '); // Espacios múltiples
        
        return input.trim();
    },

    /**
     * Sanitizar patrones de búsqueda
     */
    sanitizarPatron(patron) {
        if (typeof patron !== 'string') {
            return '';
        }

        // Límite de longitud para patrones
        if (patron.length > this.config.maxPatronLength) {
            patron = patron.substring(0, this.config.maxPatronLength);
        }

        // Escapar caracteres especiales para regex
        patron = patron.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        
        return patron.toLowerCase().trim();
    },

    /**
     * Escapar HTML en respuestas
     */
    escaparHTML(texto) {
        const div = document.createElement('div');
        div.textContent = texto;
        return div.innerHTML;
    },

    /**
     * Validar estructura de FAQ
     */
    validarFAQ(faq) {
        if (!faq || typeof faq !== 'object') {
            return false;
        }

        // Verificar propiedades requeridas
        if (!Array.isArray(faq.patrones) || !faq.respuestas || !faq.tipo) {
            return false;
        }

        // Verificar que patrones sean strings válidos
        for (const patron of faq.patrones) {
            if (typeof patron !== 'string' || patron.length === 0) {
                return false;
            }
        }

        // Verificar respuestas
        const respuestas = Array.isArray(faq.respuestas) ? faq.respuestas : [faq.respuestas];
        for (const respuesta of respuestas) {
            if (typeof respuesta !== 'string' || respuesta.length === 0) {
                return false;
            }
        }

        return true;
    },

    // ========================================
    // SISTEMA DE PROCESAMIENTO SEGURO
    // ========================================

    /**
     * Procesar mensaje del usuario y encontrar respuesta estática (SEGURO)
     */
    procesarMensaje(mensaje) {
        try {
            // 🛡️ Sanitizar entrada
            const mensajeSanitizado = this.sanitizarEntrada(mensaje);
            
            if (!mensajeSanitizado) {
                return null;
            }

            const mensajeLimpio = this.limpiarTextoSeguro(mensajeSanitizado);
            let mejorCoincidencia = null;
            let maxSimilitud = 0;

            // Buscar coincidencias en todas las categorías
            for (const [categoria, faq] of Object.entries(this.faqs)) {
                // 🛡️ Validar FAQ antes de procesar
                if (!this.validarFAQ(faq)) {
                    console.warn(`FAQ inválida detectada: ${categoria}`);
                    continue;
                }

                const similitud = this.calcularSimilitudSegura(mensajeLimpio, faq.patrones);
                
                if (similitud > maxSimilitud && similitud >= this.config.umbralSimilitud) {
                    maxSimilitud = similitud;
                    mejorCoincidencia = {
                        categoria,
                        faq,
                        similitud
                    };
                }
            }

            if (mejorCoincidencia) {
                const respuesta = this.seleccionarRespuestaSegura(mejorCoincidencia.faq);
                
                if (this.config.enableLogging) {
                    console.log(`🤖 FAQ Match: ${mejorCoincidencia.categoria} (${(maxSimilitud * 100).toFixed(1)}%)`);
                }

                return {
                    tipo: 'faq_estatica',
                    categoria: mejorCoincidencia.categoria,
                    respuesta: respuesta,
                    similitud: maxSimilitud,
                    requiereSeguimiento: mejorCoincidencia.faq.tipo === 'requiere_seguimiento',
                    requiereBaseDatos: mejorCoincidencia.faq.tipo === 'requiere_bd',
                    sanitizado: true
                };
            }

            return null; // No encontró coincidencia estática

        } catch (error) {
            console.error('Error procesando mensaje FAQ:', error);
            return null;
        }
    },

    /**
     * Limpiar y normalizar texto de forma segura
     */
    limpiarTextoSeguro(texto) {
        if (typeof texto !== 'string') {
            return '';
        }

        // Convertir a minúsculas
        let limpio = texto.toLowerCase();
        
        // Normalizar caracteres Unicode de forma segura
        try {
            limpio = limpio.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        } catch (e) {
            // Fallback si normalize() falla
            console.warn('Error normalizando texto:', e);
        }
        
        // Remover puntuación manteniendo espacios
        limpio = limpio.replace(/[^\w\sáéíóúñ]/g, ' ');
        
        // Normalizar espacios
        limpio = limpio.replace(/\s+/g, ' ').trim();
        
        return limpio;
    },

    /**
     * Calcular similitud de forma segura (sin regex complejas)
     */
    calcularSimilitudSegura(mensaje, patrones) {
        if (!Array.isArray(patrones)) {
            return 0;
        }

        let maxSimilitud = 0;

        for (const patron of patrones) {
            if (typeof patron !== 'string') {
                continue;
            }

            const patronLimpio = this.limpiarTextoSeguro(patron);
            
            // Coincidencia exacta (mayor peso)
            if (mensaje.includes(patronLimpio)) {
                maxSimilitud = Math.max(maxSimilitud, 1.0);
                continue;
            }

            // Similitud por palabras en común
            const similitud = this.calcularSimilitudPalabras(mensaje, patronLimpio);
            maxSimilitud = Math.max(maxSimilitud, similitud);
        }

        return maxSimilitud;
    },

    /**
     * Calcular similitud basada en palabras en común
     */
    calcularSimilitudPalabras(mensaje, patron) {
        const palabrasMensaje = mensaje.split(' ').filter(p => p.length > 2); // Filtrar palabras muy cortas
        const palabrasPatron = patron.split(' ').filter(p => p.length > 2);
        
        if (palabrasPatron.length === 0) {
            return 0;
        }

        let coincidencias = 0;
        
        for (const palabraPatron of palabrasPatron) {
            for (const palabraMensaje of palabrasMensaje) {
                // Coincidencia exacta
                if (palabraMensaje === palabraPatron) {
                    coincidencias += 1;
                    break;
                }
                // Coincidencia parcial (palabra contiene o está contenida)
                else if (palabraMensaje.length > 3 && palabraPatron.length > 3) {
                    if (palabraMensaje.includes(palabraPatron) || palabraPatron.includes(palabraMensaje)) {
                        coincidencias += 0.7;
                        break;
                    }
                }
            }
        }

        return coincidencias / palabrasPatron.length;
    },

    /**
     * Seleccionar respuesta de forma segura
     */
    seleccionarRespuestaSegura(faq) {
        if (!this.validarFAQ(faq)) {
            return 'Lo siento, hubo un error procesando tu consulta.';
        }

        const respuestas = Array.isArray(faq.respuestas) ? faq.respuestas : [faq.respuestas];
        
        // Validar que las respuestas sean strings válidos
        const respuestasValidas = respuestas.filter(r => typeof r === 'string' && r.length > 0);
        
        if (respuestasValidas.length === 0) {
            return 'Lo siento, no tengo una respuesta disponible para esa consulta.';
        }

        const respuestaSeleccionada = respuestasValidas[Math.floor(Math.random() * respuestasValidas.length)];
        
        // No escapar HTML aquí ya que se hará en el renderizado
        return respuestaSeleccionada;
    },

    // ========================================
    // UTILIDADES SEGURAS
    // ========================================

    /**
     * Obtener todas las categorías disponibles
     */
    obtenerCategorias() {
        return Object.keys(this.faqs).filter(categoria => 
            this.validarFAQ(this.faqs[categoria])
        );
    },

    /**
     * Obtener estadísticas de la base de conocimiento
     */
    obtenerEstadisticas() {
        const categorias = this.obtenerCategorias();
        let totalPatrones = 0;
        let totalRespuestas = 0;

        categorias.forEach(categoria => {
            const faq = this.faqs[categoria];
            if (this.validarFAQ(faq)) {
                totalPatrones += faq.patrones.length;
                totalRespuestas += Array.isArray(faq.respuestas) ? faq.respuestas.length : 1;
            }
        });

        return {
            categorias: categorias.length,
            totalPatrones,
            totalRespuestas,
            umbralSimilitud: this.config.umbralSimilitud,
            version: '1.0.0-sanitizado'
        };
    },

    /**
     * Actualizar configuración de forma segura
     */
    actualizarConfig(nuevaConfig) {
        if (!nuevaConfig || typeof nuevaConfig !== 'object') {
            console.warn('Configuración inválida');
            return false;
        }

        // Validar valores de configuración
        const configValida = {};
        
        if (typeof nuevaConfig.umbralSimilitud === 'number' && 
            nuevaConfig.umbralSimilitud >= 0 && nuevaConfig.umbralSimilitud <= 1) {
            configValida.umbralSimilitud = nuevaConfig.umbralSimilitud;
        }
        
        if (typeof nuevaConfig.maxInputLength === 'number' && 
            nuevaConfig.maxInputLength > 0 && nuevaConfig.maxInputLength <= 1000) {
            configValida.maxInputLength = nuevaConfig.maxInputLength;
        }
        
        if (typeof nuevaConfig.enableLogging === 'boolean') {
            configValida.enableLogging = nuevaConfig.enableLogging;
        }

        this.config = { ...this.config, ...configValida };
        return true;
    },

    /**
     * Validar integridad del sistema
     */
    validarIntegridad() {
        const errores = [];
        
        // Verificar configuración
        if (typeof this.config.umbralSimilitud !== 'number' || 
            this.config.umbralSimilitud < 0 || this.config.umbralSimilitud > 1) {
            errores.push('umbralSimilitud inválido');
        }
        
        // Verificar FAQs
        for (const [categoria, faq] of Object.entries(this.faqs)) {
            if (!this.validarFAQ(faq)) {
                errores.push(`FAQ inválida: ${categoria}`);
            }
        }
        
        return {
            valido: errores.length === 0,
            errores
        };
    },

    /**
     * Método de prueba seguro
     */
    probarSistema(mensajePrueba = 'hola') {
        try {
            const resultado = this.procesarMensaje(mensajePrueba);
            const integridad = this.validarIntegridad();
            
            return {
                funcionando: true,
                resultadoPrueba: resultado,
                integridad: integridad,
                estadisticas: this.obtenerEstadisticas()
            };
        } catch (error) {
            return {
                funcionando: false,
                error: error.message
            };
        }
    }
};

// ========================================
// EXPORTAR PARA USO GLOBAL (SEGURO)
// ========================================

// Para uso en Node.js
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ChatbotFAQs;
}

// Para uso en navegador
if (typeof window !== 'undefined') {
    // Verificar integridad antes de exponer
    const integridad = ChatbotFAQs.validarIntegridad();
    if (integridad.valido) {
        window.ChatbotFAQs = ChatbotFAQs;
        console.log('✅ ChatbotFAQs cargado y validado');
    } else {
        console.error('❌ ChatbotFAQs falló validación:', integridad.errores);
    }
}