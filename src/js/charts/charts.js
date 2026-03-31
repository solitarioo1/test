/**
 * CHARTS.JS - Gestión de gráficas UV protegida
 * Versión segura con protecciones anti-manipulación
 */

(function() {
    'use strict';

    // ========================================
    // 🛡️ CONFIGURACIÓN DE SEGURIDAD
    // ========================================
    
    const CHART_CONFIG = {
        // Solo logs en desarrollo
        DEBUG_MODE: window.location.hostname === 'localhost' || 
                   window.location.hostname.includes('dev') ||
                   window.location.search.includes('debug=1'),
        
        // Límites de seguridad
        MAX_DATA_POINTS: 500,     // Máximo puntos en gráfica
        MAX_STATION_ID: 1000,     // ID máximo de estación
        UPDATE_INTERVAL: 300000,  // 5 minutos
        CHART_TIMEOUT: 30000,     // 30 segundos para crear gráfica
        
        // Validaciones
        DATE_REGEX: /^\d{4}-\d{2}-\d{2}$/,
        VALID_CONTAINER_ID: 'graficoUv',
        
        // Rate limiting para actualizaciones
        MIN_UPDATE_INTERVAL: 5000,  // cambiar de 300000 a 5000 (5 seg)
        
        // Protección DOM
        ALLOWED_ELEMENTS: ['graficoUv', 'nombre-estacion', 'hora-registro', 'coordenadas-estacion']
    };

    // ========================================
    // 🚨 RATE LIMITING PARA GRÁFICAS
    // ========================================
    
    class ChartRateLimit {
        constructor() {
            this._lastUpdate = new Map();
            this._updateAttempts = new Map();
            this._blocked = new Set();
        }

        canUpdate(stationId) {
            const now = Date.now();
            const key = `update_${stationId}`;
            
            // Verificar si está bloqueado
            if (this._blocked.has(key)) {
                const blockTime = this._blocked.get(key);
                if (now - blockTime < CHART_CONFIG.MIN_UPDATE_INTERVAL) {
                    this._logSecure(`🚫 Update bloqueado para estación ${stationId}`, 'warn');
                    return false;
                }
                this._blocked.delete(key);
            }

            // Verificar última actualización
            const lastUpdate = this._lastUpdate.get(key) || 0;
            if (now - lastUpdate < CHART_CONFIG.MIN_UPDATE_INTERVAL) {
                this._logSecure(`⏱️ Update muy frecuente para estación ${stationId}`, 'warn');
                return false;
            }

            // Contar intentos en los últimos 5 minutos
            const attempts = this._updateAttempts.get(key) || [];
            const recentAttempts = attempts.filter(time => now - time < 300000);
            
            if (recentAttempts.length >= 10) { // Máximo 10 updates en 5 min
                this._blocked.set(key, now);
                this._logSecure(`🚨 Demasiados updates para estación ${stationId}`, 'warn');
                return false;
            }

            // Registrar intento
            recentAttempts.push(now);
            this._updateAttempts.set(key, recentAttempts);
            this._lastUpdate.set(key, now);
            
            return true;
        }

        _logSecure(message, level = 'log') {
            if (CHART_CONFIG.DEBUG_MODE) {
                console[level](`[ChartRateLimit] ${message}`);
            }
        }
    }

    // ========================================
    // 🔒 GESTOR DE GRÁFICAS PROTEGIDO
    // ========================================
    
    class SecureChartManager {
        constructor() {
            this._chartContainer = null;
            this._chartInstance = null;
            this._estacionActual = null;
            this._fechaActual = null;
            this._intervalos = new Set();
            this._rateLimit = new ChartRateLimit();
            this._isInitialized = false;
            this._isDestroyed = false;
            
            // Proteger propiedades críticas
            Object.defineProperties(this, {
                _isDestroyed: { writable: true, configurable: false },
                _rateLimit: { writable: false, configurable: false }
            });

            this._init();
        }

        async _init() {
            try {
                this._logSecure('📊 Inicializando gestor de gráficas protegido...');
                
                await this._esperarApi();
                
                if (!this._validarEntorno()) {
                    throw new Error('Entorno no válido para gráficas');
                }
                
                this._configurarContenedor();
                this._configurarEventos();
                this._isInitialized = true;
                
                this._logSecure('✅ Gestor de gráficas protegido inicializado');
                
            } catch (error) {
                this._logSecure(`❌ Error inicializando gráficas: ${error.message}`, 'error');
                this._isInitialized = false;
            }
        }

        async _esperarApi() {
            return new Promise((resolve, reject) => {
                if (window.api && window.apiUtils) {
                    resolve();
                    return;
                }
                
                const timeout = setTimeout(() => {
                    reject(new Error('Timeout esperando API'));
                }, 10000);
                
                document.addEventListener('apiLista', () => {
                    clearTimeout(timeout);
                    resolve();
                }, { once: true });
            });
        }

        _validarEntorno() {
            // Verificar dependencias críticas
            if (typeof echarts === 'undefined') {
                this._logSecure('❌ ECharts no disponible', 'error');
                return false;
            }
            
            if (!window.api || !window.apiUtils) {
                this._logSecure('❌ API no disponible', 'error');
                return false;
            }
            
            return true;
        }

        _configurarContenedor() {
            const containerId = CHART_CONFIG.VALID_CONTAINER_ID;
            this._chartContainer = document.getElementById(containerId);
            
            if (!this._chartContainer) {
                throw new Error(`Contenedor '${containerId}' no encontrado`);
            }

            // Validar que es el contenedor correcto
            if (this._chartContainer.id !== containerId) {
                throw new Error('Contenedor de gráfica inválido');
            }

            this._configurarResponsive();
        }

        _configurarEventos() {
            // Event listener protegido
            const eventHandler = async (event) => {
                try {
                    if (this._isDestroyed) return;
                    
                    const { estacion } = event.detail;
                    if (!this._validarEstacion(estacion)) {
                        this._logSecure('❌ Estación inválida recibida en evento', 'warn');
                        return;
                    }
                    
                    await this._actualizarGraficaSegura(estacion);
                    
                } catch (error) {
                    this._logSecure(`❌ Error en event handler: ${error.message}`, 'error');
                }
            };

            document.addEventListener('estacionSeleccionada', eventHandler);
            
            // Guardar referencia para cleanup
            this._eventHandler = eventHandler;
        }

        // ========================================
        // 📊 GESTIÓN PRINCIPAL PROTEGIDA
        // ========================================

        // ✅ MÉTODO PRINCIPAL ACTUALIZADO
        async _actualizarGraficaSegura(estacion) {
            try {
                if (!this._isInitialized || this._isDestroyed) {
                    this._logSecure('❌ Gestor no inicializado o destruido', 'warn');
                    return;
                }

                if (!this._validarEstacion(estacion)) {
                    throw new Error('Datos de estación inválidos');
                }

                // Rate limiting
                if (!this._rateLimit.canUpdate(estacion.id)) {
                    return;
                }

                this._logSecure(`📊 Actualizando gráfica para: ${estacion.nombre}`);
                
                this._estacionActual = estacion;
                
                // ✅ ELIMINAR TODA LÓGICA DE FECHAS - DEJAR QUE PHP DECIDA
                // YA NO: const fechaParaGrafica = new Date().toISOString().split('T')[0];
                // AHORA: null = PHP decide qué fecha usar
                
                this._mostrarEstadoCarga();
                
                // Timeout para la operación
                const timeoutPromise = new Promise((_, reject) => {
                    setTimeout(() => reject(new Error('Timeout cargando datos')), CHART_CONFIG.CHART_TIMEOUT);
                });
                
                // ✅ ENVIAR null - PHP MANEJA LAS FECHAS
                const dataPromise = window.api.obtenerRegistrosDia(estacion.id, null);
                
                const datosGrafica = await Promise.race([dataPromise, timeoutPromise]);
                
                // 🔍 DEBUG: Mostrar lo que recibimos del backend
                console.log('📊 DATOS RECIBIDOS DEL BACKEND:', datosGrafica);
                console.log('📅 Fecha título desde PHP:', datosGrafica?.data?.fecha_titulo);
                console.log('📅 Usando fallback:', datosGrafica?.data?.usando_fallback);
                console.log('📅 Mensaje para usuario:', datosGrafica?.data?.info_fecha?.mensaje_usuario);
                
                this._renderizarGraficaSegura(datosGrafica);
                
                this._logSecure(`✅ Gráfica actualizada para ${estacion.nombre}`);

            } catch (error) {
                console.error('❌ Error actualizando gráfica:', error);
                this._logSecure(`❌ Error actualizando gráfica: ${error.message}`, 'error');
                this._mostrarErrorGrafica('Error al cargar datos de la gráfica');
            }
        }


        _renderizarGraficaSegura(datosGrafica) {
            if (!this._chartContainer || this._isDestroyed) {
                this._logSecure('❌ Contenedor no disponible para renderizar', 'warn');
                return;
            }

            try {
                // Dispose seguro
                this._disposeChartSeguro();

                // Verificar datos
                if (!this._validarDatosGrafica(datosGrafica)) {
                    this._mostrarSinDatos(datosGrafica);
                    return;
                }

                // Limpiar contenedor de forma segura
                this._limpiarContenedorSeguro();

                // Procesar datos con límites
                const datosProcesados = this._procesarDatosSeguro(datosGrafica.registros);
                
                if (datosProcesados.valores.length > CHART_CONFIG.MAX_DATA_POINTS) {
                    this._logSecure(`⚠️ Demasiados puntos de datos: ${datosProcesados.valores.length}`, 'warn');
                    // Reducir datos manteniendo proporción
                    const factor = Math.ceil(datosProcesados.valores.length / CHART_CONFIG.MAX_DATA_POINTS);
                    datosProcesados.labels = datosProcesados.labels.filter((_, index) => index % factor === 0);
                    datosProcesados.valores = datosProcesados.valores.filter((_, index) => index % factor === 0);
                }

                // Crear gráfico con protecciones
                this._chartInstance = echarts.init(this._chartContainer);
                
                const opciones = this._crearOpcionesSeguras(datosProcesados, datosGrafica);
                
                this._chartInstance.setOption(opciones);
                
                this._configurarActualizacionSegura();

            } catch (error) {
                this._logSecure(`❌ Error renderizando gráfica: ${error.message}`, 'error');
                this._mostrarErrorGrafica('Error al crear la gráfica');
            }
        }

        _procesarDatosSeguro(registros) {
            if (!Array.isArray(registros)) {
                throw new Error('Registros debe ser un array');
            }

            // Validar cada registro
            const registrosValidos = registros.filter(registro => {
                return registro && 
                       typeof registro.hora === 'string' && 
                       (typeof registro.uv_index === 'number' || !isNaN(parseFloat(registro.uv_index)));
            });

            // Filtrar cada 5 minutos
            const datosFiltrados = registrosValidos.filter(registro => {
                const partesHora = registro.hora.split(':');
                if (partesHora.length < 2) return false;
                
                const minutos = parseInt(partesHora[1]);
                return !isNaN(minutos) && minutos % 5 === 0;
            });

            // Ordenar por hora
            datosFiltrados.sort((a, b) => {
                const [horaA, minutoA] = a.hora.split(':').map(Number);
                const [horaB, minutoB] = b.hora.split(':').map(Number);
                return (horaA - horaB) || (minutoA - minutoB);
            });

            const labels = datosFiltrados.map(registro => {
                const hora = registro.hora.substring(0, 5);
                // Validar formato HH:MM
                return /^\d{2}:\d{2}$/.test(hora) ? hora : '00:00';
            });
            
            const valores = datosFiltrados.map(registro => {
                const valor = parseFloat(registro.uv_index);
                // Validar rango UV (0-20)
                return !isNaN(valor) && valor >= 0 && valor <= 20 ? valor : 0;
            });

            return { labels, valores, registrosOriginales: datosFiltrados };
        }

        // ✅ MÉTODO PARA CREAR OPCIONES - USAR FECHAS DEL BACKEND
        _crearOpcionesSeguras(datosProcesados, datosGrafica) {
            const { labels, valores } = datosProcesados;
            const estacion = datosGrafica.estacion || this._estacionActual;
            
            // ✅ USAR FECHA YA FORMATEADA QUE VIENE DEL BACKEND
            const fechaTitulo = datosGrafica.fecha_titulo || 'Fecha no disponible';
            const usandoFallback = datosGrafica.usando_fallback || false;
            const mensajeUsuario = datosGrafica.info_fecha?.mensaje_usuario || '';

            // Validar datos antes de crear opciones
            if (!Array.isArray(labels) || !Array.isArray(valores)) {
                throw new Error('Datos de gráfica inválidos');
            }

            if (labels.length !== valores.length) {
                throw new Error('Inconsistencia en datos de gráfica');
            }

            // ✅ TÍTULO DINÁMICO BASADO EN LO QUE DICE EL BACKEND
            let tituloCompleto = `Índice UV Diario - ${fechaTitulo}`;
            if (usandoFallback) {
                tituloCompleto += ' (Último día disponible)';
            }

            return {
                title: {
                    text: tituloCompleto, // ✅ USAR FECHA FORMATEADA POR PHP
                    subtext: `${estacion?.nombre || 'Estación'}${mensajeUsuario ? ' • ' + mensajeUsuario : ''}`,
                    left: 'center',
                    textStyle: {
                        fontSize: 16,
                        fontWeight: 'bold',
                        color: usandoFallback ? '#ff6600' : '#333' // ✅ COLOR DIFERENTE SI ES FALLBACK
                    },
                    subtextStyle: {
                        fontSize: 12,
                        color: usandoFallback ? '#ff6600' : '#666'
                    }
                },
                
                tooltip: {
                    trigger: 'axis',
                    backgroundColor: 'rgba(255, 255, 255, 0.95)',
                    borderColor: '#ddd',
                    borderWidth: 1,
                    formatter: (params) => {
                        if (!params || params.length === 0) return '';
                        
                        const valor = parseFloat(params[0].value) || 0;
                        const hora = params[0].axisValue || '00:00';
                        const color = this._obtenerColorUVSeguro(valor);
                        const nivel = this._obtenerNivelUVSeguro(valor);
                        
                        return `
                            <div style="padding: 8px;">
                                <strong>⏰ Hora:</strong> ${this._sanitizarTexto(hora)}<br/>
                                <strong>☀️ Índice UV:</strong> ${valor.toFixed(2)}<br/>
                                <strong>📊 Nivel:</strong> 
                                <span style="color: ${color}; font-weight: bold;">${nivel}</span>
                                ${usandoFallback ? '<br/><em style="color: #ff6600;">Datos de último día disponible</em>' : ''}
                            </div>
                        `;
                    }
                },
                
                // ✅ RESTO DE CONFIGURACIÓN IGUAL
                grid: {
                    left: '8%',
                    right: '8%',
                    bottom: '15%',
                    top: '20%',
                    containLabel: true
                },
                
                xAxis: {
                    type: 'category',
                    data: labels,
                    axisLabel: {
                        rotate: 45,
                        interval: 0,
                        fontSize: 10,
                        formatter: (value) => {
                            const [hora, minuto] = value.split(':').map(Number);
                            return (!isNaN(hora) && !isNaN(minuto) && minuto % 30 === 0) ? value : '';
                        }
                    }
                },
                
                yAxis: {
                    type: 'value',
                    min: 0,
                    max: 15,
                    interval: 1,
                    name: 'Índice UV',
                    nameLocation: 'middle',
                    nameGap: 40
                },
                
                visualMap: {
                    show: false,
                    dimension: 1,
                    pieces: [
                        { gt: 11, color: '#800080' },
                        { gt: 7, lte: 11, color: '#FF0000' },
                        { gt: 5, lte: 7, color: '#FC7D02' },
                        { gt: 2, lte: 5, color: '#FBDB0F' },
                        { lte: 2, color: '#00FF00' }
                    ]
                },
                
                dataZoom: [
                    {
                        type: 'slider',
                        start: 0,
                        end: 100,
                        height: 20,
                        bottom: '5%'
                    }
                ],
                
                series: [{
                    name: 'Índice UV',
                    type: 'line',
                    data: valores,
                    smooth: true,
                    lineStyle: {
                        width: 3,
                        color: usandoFallback ? '#ff6600' : '#ff6b35' // ✅ COLOR DIFERENTE SI ES FALLBACK
                    },
                    itemStyle: {
                        borderWidth: 2,
                        borderColor: '#fff'
                    },
                    areaStyle: {
                        opacity: 0.1
                    },
                    markLine: {
                        silent: true,
                        lineStyle: {
                            color: '#666',
                            type: 'dashed'
                        },
                        data: [
                            { yAxis: 2, name: 'Bajo' },
                            { yAxis: 5, name: 'Moderado' },
                            { yAxis: 7, name: 'Alto' },
                            { yAxis: 11, name: 'Muy Alto' }
                        ]
                    }
                }],
                
                animation: true,
                animationDuration: 1000
            };
        }
        // ========================================
        // 🔒 MÉTODOS DE VALIDACIÓN
        // ========================================

        _validarEstacion(estacion) {
            if (!estacion || typeof estacion !== 'object') {
                return false;
            }
            
            const id = parseInt(estacion.id);
            if (isNaN(id) || id <= 0 || id > CHART_CONFIG.MAX_STATION_ID) {
                return false;
            }
            
            if (typeof estacion.nombre !== 'string' || estacion.nombre.length === 0) {
                return false;
            }
            
            return true;
        }

        _validarDatosGrafica(datos) {
            if (!datos || typeof datos !== 'object') {
                return false;
            }
            
            if (!Array.isArray(datos.registros)) {
                return false;
            }
            
            return datos.registros.length > 0;
        }

        _sanitizarTexto(texto) {
            if (typeof texto !== 'string') return '';
            
            return texto
                .replace(/[<>]/g, '') // Eliminar < >
                .replace(/javascript:/gi, '') // Eliminar javascript:
                .substring(0, 100); // Limitar longitud
        }

        // ========================================
        // 🛠️ UTILIDADES SEGURAS
        // ========================================

        _obtenerColorUVSeguro(uvIndex) {
            if (window.apiUtils && typeof window.apiUtils.obtenerColorUV === 'function') {
                return window.apiUtils.obtenerColorUV(uvIndex);
            }
            
            // Fallback seguro
            const uv = parseFloat(uvIndex);
            if (isNaN(uv)) return '#cccccc';
            
            if (uv > 11) return '#800080';
            if (uv > 7) return '#FF0000';
            if (uv > 5) return '#FC7D02';
            if (uv > 2) return '#FBDB0F';
            return '#00FF00';
        }

        _obtenerNivelUVSeguro(uvIndex) {
            if (window.apiUtils && typeof window.apiUtils.obtenerNivelUV === 'function') {
                return window.apiUtils.obtenerNivelUV(uvIndex);
            }
            
            // Fallback seguro
            const uv = parseFloat(uvIndex);
            if (isNaN(uv)) return 'Desconocido';
            
            if (uv > 11) return 'Extremo';
            if (uv > 7) return 'Muy Alto';
            if (uv > 5) return 'Alto';
            if (uv > 2) return 'Moderado';
            return 'Bajo';
        }

        _formatearFechaSegura(fechaString) {
            try {
                if (!fechaString || !CHART_CONFIG.DATE_REGEX.test(fechaString)) {
                    return 'Fecha inválida';
                }
                
                const fecha = new Date(fechaString);
                if (isNaN(fecha.getTime())) {
                    return 'Fecha inválida';
                }
                
                return fecha.toLocaleDateString('es-ES', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
            } catch (error) {
                this._logSecure(`❌ Error formateando fecha: ${error.message}`, 'error');
                return 'Error en fecha';
            }
        }

        // ========================================
        // 🧹 LIMPIEZA SEGURA
        // ========================================

        // _disposeChartSeguro() {
        //     if (this._chartInstance) {
        //         try {
        //             // Validar que el contenedor DOM existe
        //             if (this._chartContainer && this._chartContainer.parentNode) {
        //                 this._chartInstance.dispose();
        //             }
        //         } catch (error) {
        //             this._logSecure(`⚠️ Error disposing chart: ${error.message}`, 'warn');
        //             this._limpiarContenedorSeguro();
        //         }
        //         this._chartInstance = null;
        //     }
        // }
        _disposeChartSeguro() {
            if (this._chartInstance) {
                try {
                    // ✅ VALIDACIÓN MÁS ROBUSTA
                    if (this._chartContainer && 
                        this._chartContainer.parentNode && 
                        document.body.contains(this._chartContainer) &&
                        typeof this._chartInstance.dispose === 'function' &&
                        !this._chartInstance.isDisposed()) {
                        
                        this._chartInstance.dispose();
                    } else {
                        // Si el contenedor no está en el DOM, limpiar referencia solamente
                        this._logSecure(`⚠️ Contenedor no disponible, limpiando referencia`, 'warn');
                    }
                } catch (error) {
                    this._logSecure(`⚠️ Error disposing chart: ${error.message}`, 'warn');
                    // Intentar limpieza manual si dispose falla
                    try {
                        if (this._chartContainer) {
                            this._chartContainer.innerHTML = '';
                        }
                    } catch (cleanupError) {
                        this._logSecure(`⚠️ Error en limpieza manual: ${cleanupError.message}`, 'warn');
                    }
                } finally {
                    // ✅ SIEMPRE limpiar la referencia
                    this._chartInstance = null;
                }
            }
        }
        _limpiarContenedorSeguro() {
            if (this._chartContainer && this._chartContainer.id === CHART_CONFIG.VALID_CONTAINER_ID) {
                this._chartContainer.innerHTML = '';
            }
        }

        // ========================================
        // 📱 ESTADOS DE INTERFAZ
        // ========================================

        _mostrarEstadoCarga() {
            if (!this._chartContainer) return;
            
            this._chartContainer.innerHTML = `
                <div class="estado-grafica cargando">
                    <div class="spinner-grafica"></div>
                    <p>Cargando datos de radiación UV...</p>
                </div>
            `;
        }

        // ✅ ACTUALIZAR MÉTODO PARA MOSTRAR SIN DATOS
        _mostrarSinDatos(datosGrafica) {
            if (!this._chartContainer) return;
            
            // ✅ USAR INFORMACIÓN QUE VIENE DEL BACKEND
            const fechaTitulo = datosGrafica.fecha_titulo || 'Fecha no disponible';
            const estacion = datosGrafica.estacion?.nombre || this._estacionActual?.nombre || 'Estación';
            const mensajeUsuario = datosGrafica.info_fecha?.mensaje_usuario || 'No hay datos disponibles';
            
            this._chartContainer.innerHTML = `
                <div class="estado-grafica sin-datos">
                    <div class="icono-sin-datos">📊</div>
                    <h3>Sin datos disponibles</h3>
                    <p>No hay registros UV para <strong>${this._sanitizarTexto(estacion)}</strong></p>
                    <p>${this._sanitizarTexto(mensajeUsuario)}</p>
                    <button onclick="window.chartManager?.recargarGrafica()" class="btn-recargar">
                        🔄 Intentar nuevamente
                    </button>
                </div>
            `;
        }

        _mostrarErrorGrafica(mensaje) {
            if (!this._chartContainer) return;
            
            this._chartContainer.innerHTML = `
                <div class="estado-grafica error">
                    <div class="icono-error">⚠️</div>
                    <h3>Error al cargar gráfica</h3>
                    <p>${this._sanitizarTexto(mensaje)}</p>
                    <button onclick="window.chartManager?.recargarGrafica()" class="btn-recargar">
                        🔄 Reintentar
                    </button>
                </div>
            `;
        }

        // ========================================
        // 🔄 ACTUALIZACIÓN AUTOMÁTICA SEGURA
        // ========================================

        _configurarActualizacionSegura() {
            // Limpiar intervalos anteriores
            this._limpiarIntervalos();

            const intervalo = setInterval(async () => {
                try {
                    if (this._isDestroyed || !this._estacionActual) {
                        clearInterval(intervalo);
                        return;
                    }
                    
                    await this._actualizarGraficaSegura(this._estacionActual);
                    this._logSecure('🔄 Gráfica actualizada automáticamente');
                    
                } catch (error) {
                    this._logSecure(`⚠️ Error en actualización automática: ${error.message}`, 'warn');
                }
            }, CHART_CONFIG.UPDATE_INTERVAL);

            this._intervalos.add(intervalo);
        }

        _configurarResponsive() {
            if (!this._chartContainer) return;
            
            const resizeHandler = () => {
                if (this._chartInstance && !this._isDestroyed) {
                    try {
                        this._chartInstance.resize();
                    } catch (error) {
                        this._logSecure(`⚠️ Error en resize: ${error.message}`, 'warn');
                    }
                }
            };

            window.addEventListener('resize', resizeHandler);
            this._resizeHandler = resizeHandler;
        }

        _limpiarIntervalos() {
            this._intervalos.forEach(intervalo => {
                try {
                    clearInterval(intervalo);
                } catch (error) {
                    this._logSecure(`⚠️ Error limpiando intervalo: ${error.message}`, 'warn');
                }
            });
            this._intervalos.clear();
        }

        // ========================================
        // 🌍 MÉTODOS PÚBLICOS PROTEGIDOS
        // ========================================

        async recargarGrafica() {
            if (this._isDestroyed) {
                this._logSecure('❌ No se puede recargar: gestor destruido', 'warn');
                return;
            }
            
            if (this._estacionActual) {
                await this._actualizarGraficaSegura(this._estacionActual);
            }
        }

        destruir() {
            this._logSecure('🧹 Destruyendo gestor de gráficas...');
            
            this._isDestroyed = true;
            this._limpiarIntervalos();
            this._disposeChartSeguro();
            
            // Remover event listeners
            if (this._eventHandler) {
                document.removeEventListener('estacionSeleccionada', this._eventHandler);
            }
            
            if (this._resizeHandler) {
                window.removeEventListener('resize', this._resizeHandler);
            }
            
            this._chartContainer = null;
            this._estacionActual = null;
            this._fechaActual = null;
        }

        // Getters protegidos
        get isInitialized() {
            return this._isInitialized && !this._isDestroyed;
        }

        get currentStation() {
            return this._estacionActual ? { ...this._estacionActual } : null;
        }

        _logSecure(message, level = 'log') {
            if (CHART_CONFIG.DEBUG_MODE) {
                console[level](`[SecureCharts] ${message}`);
            }
        }
    }

    // ========================================
    // 🌍 EXPORTACIÓN E INICIALIZACIÓN SEGURA
    // ========================================
    
    let chartManagerInstance = null;

    document.addEventListener('DOMContentLoaded', async () => {
        if (CHART_CONFIG.DEBUG_MODE) {
            console.log('📊 Inicializando gestión de gráficas protegida...');
        }
        
        // Esperar API
        await new Promise((resolve) => {
            if (window.api) {
                resolve();
            } else {
                document.addEventListener('apiLista', resolve, { once: true });
                setTimeout(resolve, 5000); // timeout
            }
        });

        try {
            chartManagerInstance = new SecureChartManager();
            
            // Exportar de forma protegida
            Object.defineProperty(window, 'chartManager', {
                value: chartManagerInstance,
                writable: false,
                configurable: false
            });

            // Compatibilidad para función global existente
            Object.defineProperty(window, 'gestorGraficas', {
                value: chartManagerInstance,
                writable: false,
                configurable: false
            });

            // Función de compatibilidad protegida
            Object.defineProperty(window, 'updateGraph', {
                value: (stationId, serverDate = null) => {
                    if (chartManagerInstance && chartManagerInstance.isInitialized) {
                        chartManagerInstance.recargarGrafica();
                    }
                },
                writable: false,
                configurable: false
            });
            
            if (CHART_CONFIG.DEBUG_MODE) {
                console.log('✅ Gestión de gráficas protegida lista');
            }
            
        } catch (error) {
            console.error('❌ Error inicializando gráficas protegidas:', error);
        }
    });

    // Cleanup automático al cerrar página
    window.addEventListener('beforeunload', () => {
        if (chartManagerInstance) {
            chartManagerInstance.destruir();
        }
    });

    if (CHART_CONFIG.DEBUG_MODE) {
        console.log('✅ Charts.js protegido cargado');
    }

})();


// En charts.js, actualizar la sección de debug:

// 🔍 DEBUG: Verificar qué datos devuelve el API
console.log('🔍 DEBUG - Respuesta completa del API:', datosGrafica);
console.log('🔍 DEBUG - Fecha en respuesta API:', datosGrafica?.data?.fecha);
console.log('🔍 DEBUG - Total registros:', datosGrafica?.data?.registros?.length || 0);

// 🔍 NUEVO: Debug de información PHP
if (datosGrafica?.debug_info) {
    console.log('🔍 DEBUG PHP - Info desde servidor:', datosGrafica.debug_info);
    console.log('🔍 DEBUG PHP - Fecha solicitada:', datosGrafica.debug_info.fecha_solicitada);
    console.log('🔍 DEBUG PHP - Fecha servidor:', datosGrafica.debug_info.servidor_fecha);
    console.log('🔍 DEBUG PHP - Total registros servidor:', datosGrafica.debug_info.total_registros);
}

// 🔍 DEBUG: Si hay registros, ver la fecha del primer registro
if (datosGrafica?.data?.registros?.length > 0) {
    console.log('🔍 DEBUG - Fecha primer registro:', datosGrafica.data.registros[0].fecha);
    console.log('🔍 DEBUG - Hora primer registro:', datosGrafica.data.registros[0].hora);
} else {
    console.log('🔍 DEBUG - NO HAY REGISTROS en la respuesta');
}