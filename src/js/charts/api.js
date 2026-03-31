/**
 * API.js - Cliente API Protegido
 * Versión segura con rate limiting client-side y protecciones anti-manipulación
 */

(function() {
    'use strict';

    // ========================================
    // 🛡️ CONFIGURACIÓN DE SEGURIDAD
    // ========================================
    
    const CONFIG = {
        // Solo logs en desarrollo
        DEBUG_MODE: window.location.hostname === 'localhost' || 
                   window.location.hostname.includes('dev') ||
                   window.location.search.includes('debug=1'),
        
        // Rate limits client-side (requests por minuto)
        RATE_LIMITS: {
            light: 100,    // endpoints ligeros
            default: 40,   // endpoints normales  
            heavy: 8       // endpoints pesados
        },
        
        // Timeouts y reintentos
        REQUEST_TIMEOUT: 15000,  // 15 segundos
        MAX_RETRIES: 2,
        RETRY_DELAY: 1000,       // 1 segundo
        
        // Validaciones
        MAX_STATION_ID: 1000,
        DATE_REGEX: /^\d{4}-\d{2}-\d{2}$/,
        
        // Cache client-side
        CACHE_DURATION: 300000    // 30 segundos
    };

    // ========================================
    // 🚨 RATE LIMITING CLIENT-SIDE
    // ========================================
    
    class ClientRateLimit {
        constructor() {
            this._attempts = new Map();
            this._blocked = new Set();
            
            // Limpiar cada minuto
            setInterval(() => this._cleanup(), 60000);
        }

        canRequest(endpoint, type = 'default') {
            const key = `${type}_${endpoint}`;
            const now = Date.now();
            const limit = CONFIG.RATE_LIMITS[type] || CONFIG.RATE_LIMITS.default;
            
            // Si está bloqueado, rechazar
            if (this._blocked.has(key)) {
                const blockTime = this._blocked.get(key);
                if (now - blockTime < 60000) { // 1 minuto de bloqueo
                    this._logSecure('❌ Rate limit client-side excedido para: ' + endpoint, 'warn');
                    return false;
                }
                this._blocked.delete(key);
            }

            // Obtener intentos recientes
            if (!this._attempts.has(key)) {
                this._attempts.set(key, []);
            }
            
            const attempts = this._attempts.get(key);
            const recentAttempts = attempts.filter(time => now - time < 60000);
            
            // Verificar límite
            if (recentAttempts.length >= limit) {
                this._blocked.set(key, now);
                this._logSecure(`🚫 Bloqueando endpoint ${endpoint} por ${limit} requests/min`, 'warn');
                return false;
            }
            
            // Registrar intento
            recentAttempts.push(now);
            this._attempts.set(key, recentAttempts);
            
            return true;
        }

        _cleanup() {
            const now = Date.now();
            
            // Limpiar intentos antiguos
            for (const [key, attempts] of this._attempts.entries()) {
                const recent = attempts.filter(time => now - time < 60000);
                if (recent.length === 0) {
                    this._attempts.delete(key);
                } else {
                    this._attempts.set(key, recent);
                }
            }
            
            // Limpiar bloqueos antiguos
            for (const [key, blockTime] of this._blocked.entries()) {
                if (now - blockTime > 60000) {
                    this._blocked.delete(key);
                }
            }
        }

        _logSecure(message, level = 'log') {
            if (CONFIG.DEBUG_MODE) {
                console[level](`[RateLimit] ${message}`);
            }
        }
    }

    // ========================================
    // 🔒 CLIENTE API PROTEGIDO
    // ========================================
    
    class SecureApiClient {
        constructor() {
            this.baseUrl = '/api';
            this.rateLimit = new ClientRateLimit();
            this.cache = new Map();
            this.csrfToken = null;
            
            this.defaultOptions = {
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                timeout: CONFIG.REQUEST_TIMEOUT
            };

            // Obtener CSRF token si existe
            this._initCSRF();
        }

        /**
         * 🛡️ Método base para realizar peticiones HTTP protegidas
         */
        async request(endpoint, options = {}, rateLimitType = 'default') {
            try {
                // Validar endpoint
                if (!this._validateEndpoint(endpoint)) {
                    throw new Error('Endpoint inválido');
                }

                // Rate limiting client-side
                if (!this.rateLimit.canRequest(endpoint, rateLimitType)) {
                    throw new Error('Demasiadas solicitudes. Espera un momento.');
                }

                // Verificar cache
                const cacheKey = `${endpoint}_${JSON.stringify(options)}`;
                if (options.method !== 'POST' && this.cache.has(cacheKey)) {
                    const cached = this.cache.get(cacheKey);
                    if (Date.now() - cached.timestamp < CONFIG.CACHE_DURATION) {
                        this._logSecure(`📦 Cache hit: ${endpoint}`);
                        return cached.data;
                    }
                    this.cache.delete(cacheKey);
                }

                const url = `${this.baseUrl}${endpoint}`;
                const config = this._buildRequestConfig(options);

                this._logSecure(`🔄 API Request: ${config.method || 'GET'} ${url}`);

                // Realizar petición con timeout
                const response = await this._fetchWithTimeout(url, config);
                
                if (!response.ok) {
                    await this._handleHttpError(response);
                    return;
                }

                const data = await response.json();
                
                // Validar respuesta
                if (!this._validateResponse(data)) {
                    throw new Error('Respuesta de API inválida');
                }

                if (!data.success) {
                    throw new Error(data.message || 'Error en la respuesta de la API');
                }

                // Guardar en cache (solo GET)
                if (options.method !== 'POST') {
                    this.cache.set(cacheKey, {
                        data: data,
                        timestamp: Date.now()
                    });
                }

                this._logSecure(`✅ API Success: ${endpoint}`);
                return data;

            } catch (error) {
                this._logSecure(`❌ API Error (${endpoint}): ${error.message}`, 'error');
                
                // Retry automático para ciertos errores
                if (this._shouldRetry(error, options)) {
                    return this._retryRequest(endpoint, options, rateLimitType);
                }
                
                throw error;
            }
        }

        // ========================================
        // 📍 ESTACIONES (Rate Limit: light)
        // ========================================

        async obtenerEstaciones() {
            const response = await this.request('/estaciones', {}, 'light');
            return this._validateAndReturn(response.data, 'array');
        }

        async obtenerCoordenadasEstaciones() {
            const response = await this.request('/estaciones/coordenadas', {}, 'light');
            return this._validateAndReturn(response.data, 'array');
        }

// ========================================
// 🔧 API.JS ACTUALIZADO - FECHAS CENTRALIZADAS
// ========================================

        // ✅ MÉTODO ACTUALIZADO: obtenerRegistrosDia
        async obtenerRegistrosDia(estacionId, fecha = null) {
            // Validar parámetros
            if (!this._validateStationId(estacionId)) {
                throw new Error('ID de estación inválido');
            }
            
            // ✅ SOLO VALIDAR FORMATO SI SE PROPORCIONA FECHA ESPECÍFICA
            if (fecha && !CONFIG.DATE_REGEX.test(fecha)) {
                throw new Error('Formato de fecha inválido');
            }

            // ✅ CONSTRUCCIÓN DE ENDPOINT SIMPLIFICADA
            let endpoint;
            if (fecha) {
                // Si se proporciona fecha específica, usarla
                endpoint = `/registros/dia/${estacionId}/${fecha}`;
            } else {
                // ✅ SI NO SE PROPORCIONA FECHA, DEJAR QUE PHP DECIDA
                endpoint = `/registros/dia/${estacionId}`;
            }
            
            const response = await this.request(endpoint, {}, 'default');
            return this._validateAndReturn(response.data, 'object');
        }

        // ✅ MÉTODO AUXILIAR PARA DEBUG
        _logFechaDebug(mensaje, fecha) {
            if (CONFIG.DEBUG_MODE) {
                console.log(`[API] ${mensaje}:`, fecha || 'null (PHP decide)');
            }
        }

        async obtenerUltimoRegistro(estacionId) {
            if (!this._validateStationId(estacionId)) {
                throw new Error('ID de estación inválido');
            }

            const response = await this.request(`/estacion/${estacionId}/ultimo`, {}, 'default');
            return this._validateAndReturn(response.data, 'object');
        }

        async obtenerUltimosRegistros() {
            const response = await this.request('/registros/ultimos', {}, 'light');
            return this._validateAndReturn(response.data, 'array');
        }

        // ========================================
        // 📈 ESTADO Y ESTADÍSTICAS (Rate Limit: light)
        // ========================================

        async verificarEstado() {
            const response = await this.request('/estado', {}, 'light');
            return this._validateAndReturn(response.data, 'object');
        }

        async obtenerEstadisticas(fecha = null) {
            if (fecha && !CONFIG.DATE_REGEX.test(fecha)) {
                throw new Error('Formato de fecha inválido');
            }

            const endpoint = fecha ? `/estadisticas/${fecha}` : '/estadisticas';
            const response = await this.request(endpoint, {}, 'default');
            return this._validateAndReturn(response.data, 'object');
        }

        // ========================================
        // 🔍 CONSULTAS PESADAS (Rate Limit: heavy)
        // ========================================

        async obtenerRegistrosPorRango(params) {
            // Validar parámetros obligatorios
            if (!params || typeof params !== 'object') {
                throw new Error('Parámetros inválidos');
            }

            const required = ['estacion_id', 'fecha_inicio', 'fecha_fin'];
            for (const field of required) {
                if (!params[field]) {
                    throw new Error(`Campo requerido: ${field}`);
                }
            }

            // Validar tipos
            if (!this._validateStationId(params.estacion_id)) {
                throw new Error('ID de estación inválido');
            }

            if (!CONFIG.DATE_REGEX.test(params.fecha_inicio) || 
                !CONFIG.DATE_REGEX.test(params.fecha_fin)) {
                throw new Error('Formato de fechas inválido');
            }

            // Validar rango (máximo 31 días)
            const inicio = new Date(params.fecha_inicio);
            const fin = new Date(params.fecha_fin);
            const dias = (fin - inicio) / (1000 * 60 * 60 * 24);
            
            if (dias > 31) {
                throw new Error('Rango máximo: 31 días');
            }

            // Agregar CSRF token
            const body = { ...params };
            if (this.csrfToken) {
                body.csrf_token = this.csrfToken;
            }

            const response = await this.request('/registros/rango', {
                method: 'POST',
                body: JSON.stringify(body)
            }, 'heavy');
            
            return this._validateAndReturn(response.data, 'object');
        }

        // ========================================
        // 🧪 UTILIDADES
        // ========================================

        async testConectividad() {
            try {
                const response = await this.request('/test', {}, 'light');
                return { success: true, data: response };
            } catch (error) {
                return { success: false, error: error.message };
            }
        }

        async obtenerDashboard() {
            const response = await this.request('/dashboard', {}, 'default');
            return this._validateAndReturn(response.data, 'object');
        }

        // ========================================
        // 🔒 MÉTODOS PRIVADOS DE SEGURIDAD
        // ========================================

        _validateEndpoint(endpoint) {
            if (typeof endpoint !== 'string' || endpoint.length === 0) {
                return false;
            }
            
            // Lista blanca de endpoints permitidos
            const allowedPatterns = [
                /^\/estaciones$/,
                /^\/estaciones\/coordenadas$/,
                /^\/registros\/dia\/\d+$/,
                /^\/registros\/dia\/\d+\/\d{4}-\d{2}-\d{2}$/,
                /^\/estacion\/\d+\/ultimo$/,
                /^\/registros\/ultimos$/,
                /^\/estado$/,
                /^\/estadisticas$/,
                /^\/estadisticas\/\d{4}-\d{2}-\d{2}$/,
                /^\/registros\/rango$/,
                /^\/test$/,
                /^\/dashboard$/
            ];

            return allowedPatterns.some(pattern => pattern.test(endpoint));
        }

        _validateStationId(id) {
            const numId = parseInt(id);
            return !isNaN(numId) && numId > 0 && numId <= CONFIG.MAX_STATION_ID;
        }

        _validateResponse(data) {
            return data && 
                   typeof data === 'object' && 
                   typeof data.success === 'boolean';
        }

        _validateAndReturn(data, expectedType) {
            if (expectedType === 'array' && !Array.isArray(data)) {
                throw new Error('Respuesta inválida: se esperaba array');
            }
            
            if (expectedType === 'object' && (typeof data !== 'object' || data === null)) {
                throw new Error('Respuesta inválida: se esperaba objeto');
            }
            
            return data;
        }

        _buildRequestConfig(options) {
            const config = {
                ...this.defaultOptions,
                ...options
            };

            // Headers de seguridad
            config.headers = {
                ...this.defaultOptions.headers,
                ...options.headers
            };

            return config;
        }

        async _fetchWithTimeout(url, config) {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), CONFIG.REQUEST_TIMEOUT);
            
            try {
                const response = await fetch(url, {
                    ...config,
                    signal: controller.signal
                });
                
                clearTimeout(timeoutId);
                return response;
                
            } catch (error) {
                clearTimeout(timeoutId);
                
                if (error.name === 'AbortError') {
                    throw new Error('Timeout: La solicitud tardó demasiado');
                }
                
                throw error;
            }
        }

        async _handleHttpError(response) {
            const status = response.status;
            
            if (status === 429) {
                const retryAfter = response.headers.get('Retry-After') || 60;
                throw new Error(`Demasiadas solicitudes. Reintenta en ${retryAfter} segundos.`);
            }
            
            if (status === 403) {
                throw new Error('Acceso denegado');
            }
            
            if (status === 404) {
                throw new Error('Recurso no encontrado');
            }
            
            if (status >= 500) {
                throw new Error('Error del servidor. Inténtalo más tarde.');
            }
            
            throw new Error(`Error HTTP: ${status} ${response.statusText}`);
        }

        _shouldRetry(error, options) {
            // No reintentar POST
            if (options.method === 'POST') {
                return false;
            }
            
            // No reintentar errores de validación
            if (error.message.includes('inválido') || 
                error.message.includes('Demasiadas solicitudes')) {
                return false;
            }
            
            // Reintentar errores de red y timeouts
            return error.message.includes('Timeout') || 
                   error.message.includes('Failed to fetch') ||
                   error.message.includes('servidor');
        }

        async _retryRequest(endpoint, options, rateLimitType, attempt = 1) {
            if (attempt > CONFIG.MAX_RETRIES) {
                throw new Error('Máximo de reintentos alcanzado');
            }

            this._logSecure(`🔄 Reintento ${attempt}/${CONFIG.MAX_RETRIES} para: ${endpoint}`);
            
            // Esperar antes de reintentar
            await new Promise(resolve => setTimeout(resolve, CONFIG.RETRY_DELAY * attempt));
            
            try {
                return await this.request(endpoint, options, rateLimitType);
            } catch (error) {
                return this._retryRequest(endpoint, options, rateLimitType, attempt + 1);
            }
        }

        _initCSRF() {
            // Buscar token CSRF en meta tags
            const metaToken = document.querySelector('meta[name="csrf-token"]');
            if (metaToken) {
                this.csrfToken = metaToken.getAttribute('content');
            }
            
            // O en formularios existentes
            const formToken = document.querySelector('input[name="csrf_token"]');
            if (formToken) {
                this.csrfToken = formToken.value;
            }
        }

        _logSecure(message, level = 'log') {
            if (CONFIG.DEBUG_MODE) {
                console[level](`[SecureAPI] ${message}`);
            }
        }

        // Limpiar cache manualmente
        clearCache() {
            this.cache.clear();
            this._logSecure('🧹 Cache limpiado');
        }
    }

    // ========================================
    // 🛠️ UTILIDADES PROTEGIDAS
    // ========================================
    
    class SecureApiUtils {
        static formatearFechaHora(fecha, hora) {
            try {
                if (!fecha || !hora) {
                    return { fecha: 'Sin fecha', hora: 'Sin hora' };
                }
                
                const fechaCompleta = new Date(`${fecha} ${hora}`);
                
                if (isNaN(fechaCompleta.getTime())) {
                    return { fecha: 'Fecha inválida', hora: 'Hora inválida' };
                }

                const opcionesFecha = { 
                    day: '2-digit', 
                    month: 'long', 
                    year: 'numeric' 
                };
                
                const opcionesHora = { 
                    hour: '2-digit', 
                    minute: '2-digit', 
                    hour12: false 
                };

                return {
                    fecha: fechaCompleta.toLocaleDateString('es-ES', opcionesFecha),
                    hora: fechaCompleta.toLocaleTimeString('es-ES', opcionesHora),
                    fechaCompleta: fechaCompleta
                };
            } catch (error) {
                CONFIG.DEBUG_MODE && console.error('Error formateando fecha/hora:', error);
                return { fecha: 'Error', hora: 'Error' };
            }
        }

        static obtenerColorUV(uvIndex) {
            const uv = parseFloat(uvIndex);
            if (isNaN(uv)) return '#cccccc';
            
            if (uv > 11) return '#800080'; // Extremo
            if (uv > 7) return '#FF0000';  // Muy Alto
            if (uv > 5) return '#FC7D02';  // Alto
            if (uv > 2) return '#FBDB0F';  // Moderado
            return '#00FF00';              // Bajo
        }

        static obtenerNivelUV(uvIndex) {
            const uv = parseFloat(uvIndex);
            if (isNaN(uv)) return 'Desconocido';
            
            if (uv > 11) return 'Extremo';
            if (uv > 7) return 'Muy Alto';
            if (uv > 5) return 'Alto';
            if (uv > 2) return 'Moderado';
            return 'Bajo';
        }

        static esNumeroValido(valor) {
            return valor !== null && valor !== undefined && !isNaN(parseFloat(valor));
        }

        static formatearValor(valor, decimales = 1, fallback = 'N/A') {
            if (!this.esNumeroValido(valor)) return fallback;
            return parseFloat(valor).toFixed(decimales);
        }
    }

    // ========================================
    // 🔐 ESTADO DE CONEXIÓN PROTEGIDO
    // ========================================
    
    class SecureConnectionState {
        constructor() {
            this.conectado = false;
            this.ultimaVerificacion = null;
            this.erroresConsecutivos = 0;
            this.maxErrores = 3;
            
            // Proteger propiedades
            Object.defineProperties(this, {
                maxErrores: { writable: false, configurable: false }
            });
        }

        marcarConectado() {
            this.conectado = true;
            this.ultimaVerificacion = new Date();
            this.erroresConsecutivos = 0;
        }

        marcarError() {
            this.erroresConsecutivos++;
            if (this.erroresConsecutivos >= this.maxErrores) {
                this.conectado = false;
            }
            this.ultimaVerificacion = new Date();
        }

        estaDisponible() {
            return this.conectado && this.erroresConsecutivos < this.maxErrores;
        }

        obtenerEstadoTexto() {
            if (this.estaDisponible()) {
                return 'CONECTADO';
            } else if (this.erroresConsecutivos >= this.maxErrores) {
                return 'DESCONECTADO';
            } else {
                return 'RECONECTANDO';
            }
        }
    }

    // ========================================
    // 🌍 EXPORTACIÓN SEGURA
    // ========================================
    
    // Crear instancias
    const apiInstance = new SecureApiClient();
    const estadoInstance = new SecureConnectionState();

    // Proteger objetos en window con descriptores
    Object.defineProperty(window, 'api', {
        value: apiInstance,
        writable: false,
        configurable: false
    });

    Object.defineProperty(window, 'apiUtils', {
        value: SecureApiUtils,
        writable: false,
        configurable: false
    });

    Object.defineProperty(window, 'estadoApi', {
        value: estadoInstance,
        writable: false,
        configurable: false
    });

    // Helpers compatibles pero protegidos
    Object.defineProperty(window, 'apiHelpers', {
        value: Object.freeze({
            formatearFecha: SecureApiUtils.formatearFechaHora,
            colorUV: SecureApiUtils.obtenerColorUV,
            nivelUV: SecureApiUtils.obtenerNivelUV,
            formatearValor: SecureApiUtils.formatearValor,
            estadoConexion: () => estadoInstance.obtenerEstadoTexto()
        }),
        writable: false,
        configurable: false
    });

    // ========================================
    // 🚀 INICIALIZACIÓN SEGURA
    // ========================================
    
    document.addEventListener('DOMContentLoaded', async () => {
        if (CONFIG.DEBUG_MODE) {
            console.log('🚀 Inicializando cliente API seguro...');
        }
        
        try {
            const resultado = await apiInstance.testConectividad();
            
            if (resultado.success) {
                estadoInstance.marcarConectado();
                CONFIG.DEBUG_MODE && console.log('✅ API conectada correctamente');
                
                document.dispatchEvent(new CustomEvent('apiLista', {
                    detail: { estado: 'conectado' }
                }));
            } else {
                throw new Error(resultado.error);
            }
            
        } catch (error) {
            estadoInstance.marcarError();
            CONFIG.DEBUG_MODE && console.error('❌ Error conectando con API:', error);
            
            document.dispatchEvent(new CustomEvent('apiError', {
                detail: { error: error.message }
            }));
        }
    });

    // Verificación periódica más inteligente
    setInterval(async () => {
        try {
            const resultado = await apiInstance.testConectividad();
            if (resultado.success) {
                estadoInstance.marcarConectado();
            } else {
                estadoInstance.marcarError();
            }
        } catch (error) {
            estadoInstance.marcarError();
            CONFIG.DEBUG_MODE && console.warn('⚠️ Verificación de conectividad falló:', error.message);
        }
    }, 300000); // 5 minutos

    // Limpiar cache periódicamente
    setInterval(() => {
        apiInstance.clearCache();
    }, 600000); // 10 minutos

    CONFIG.DEBUG_MODE && console.log('✅ Cliente API seguro inicializado');

})();