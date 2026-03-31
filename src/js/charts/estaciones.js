/**
 * ESTACIONES.JS - Gestión de estaciones y mapas protegida
 * Versión segura con protecciones anti-manipulación
 */

(function() {
    'use strict';

    // ========================================
    // 🛡️ CONFIGURACIÓN DE SEGURIDAD
    // ========================================
    
    const STATIONS_CONFIG = {
        // Solo logs en desarrollo
        DEBUG_MODE: window.location.hostname === 'localhost' || 
                   window.location.hostname.includes('dev') ||
                   window.location.search.includes('debug=1'),
        
        // Límites geográficos (Perú)
        BOUNDS: {
            LAT_MIN: -18.5,    // Sur de Perú
            LAT_MAX: 0.0,      // Norte de Perú  
            LNG_MIN: -81.5,    // Oeste de Perú
            LNG_MAX: -68.5     // Este de Perú
        },
        
        // Configuración de mapa
        LIMA_CENTER: [-11.5, -76.5],
        ZOOM_INICIAL: 8,
        ZOOM_ESTACION: 12,
        MAX_ZOOM: 18,
        MIN_ZOOM: 6,
        
        // Rate limiting
        MIN_SELECTION_INTERVAL: 500,  // cambiar de 1000 a 500 (medio seg)
        MAX_SELECTIONS_PER_MINUTE: 30, // Máximo 30 selecciones por minuto
        UPDATE_INTERVAL: 300000,        // 2 minutos para updates automáticos
        
        // Validaciones
        MAX_STATION_ID: 1000,
        MAX_STATION_NAME_LENGTH: 100,
        MAX_LOCATION_LENGTH: 200,
        VALID_CONTAINER_IDS: ['mapa-estaciones', 'lista-estaciones'],
        
        // Cache y performance
        MARKER_CACHE_SIZE: 100,
        DATA_CACHE_DURATION: 300000,    // 1 minuto
    };

    // ========================================
    // 🚨 RATE LIMITING PARA ESTACIONES
    // ========================================
    
    class StationRateLimit {
        constructor() {
            this._selections = [];
            this._lastSelection = 0;
            this._blocked = false;
            this._blockTime = 0;
        }

        canSelect(stationId) {
            const now = Date.now();
            
            // Verificar si está bloqueado
            if (this._blocked && now - this._blockTime < 60000) { // 1 minuto de bloqueo
                this._logSecure(`🚫 Selección bloqueada por spam`, 'warn');
                return false;
            } else if (this._blocked) {
                this._blocked = false;
            }

            // Verificar intervalo mínimo
            if (now - this._lastSelection < STATIONS_CONFIG.MIN_SELECTION_INTERVAL) {
                this._logSecure(`⏱️ Selección muy rápida para estación ${stationId}`, 'warn');
                return false;
            }

            // Limpiar selecciones antiguas (último minuto)
            this._selections = this._selections.filter(time => now - time < 60000);

            // Verificar límite por minuto
            if (this._selections.length >= STATIONS_CONFIG.MAX_SELECTIONS_PER_MINUTE) {
                this._blocked = true;
                this._blockTime = now;
                this._logSecure(`🚨 Demasiadas selecciones: ${this._selections.length}/min`, 'warn');
                return false;
            }

            // Registrar selección
            this._selections.push(now);
            this._lastSelection = now;
            
            return true;
        }

        _logSecure(message, level = 'log') {
            if (STATIONS_CONFIG.DEBUG_MODE) {
                console[level](`[StationRateLimit] ${message}`);
            }
        }
    }

    // ========================================
    // 🔒 GESTOR DE ESTACIONES PROTEGIDO
    // ========================================
    
    class SecureStationManager {
        constructor() {
            this._estaciones = [];
            this._estacionSeleccionada = null;
            this._mapa = null;
            this._marcadores = new Map();
            this._intervalos = new Set();
            this._cache = new Map();
            this._rateLimit = new StationRateLimit();
            this._isInitialized = false;
            this._isDestroyed = false;
            
            // Proteger propiedades críticas
            Object.defineProperties(this, {
                _isDestroyed: { writable: true, configurable: false },
                _rateLimit: { writable: false, configurable: false },
                _marcadores: { writable: false, configurable: false }
            });

            this._init();
        }

        async _init() {
            try {
                this._logSecure('🚀 Inicializando Gestor de Estaciones protegido...');
                
                await this._esperarApi();
                
                if (!this._validarEntorno()) {
                    throw new Error('Entorno no válido para estaciones');
                }
                
                this._inicializarMapaSeguro();
                await this._cargarEstacionesSeguro();
                this._configurarActualizacionSegura();
                this._isInitialized = true;
                
                this._logSecure('✅ Gestor de Estaciones protegido inicializado');
                
            } catch (error) {
                this._logSecure(`❌ Error inicializando estaciones: ${error.message}`, 'error');
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
            // Verificar Leaflet
            if (typeof L === 'undefined') {
                this._logSecure('❌ Leaflet no disponible', 'error');
                return false;
            }
            
            // Verificar API
            if (!window.api || !window.apiUtils) {
                this._logSecure('❌ API no disponible', 'error');
                return false;
            }
            
            // Verificar contenedores
            for (const containerId of STATIONS_CONFIG.VALID_CONTAINER_IDS) {
                if (!document.getElementById(containerId)) {
                    this._logSecure(`❌ Contenedor '${containerId}' no encontrado`, 'error');
                    return false;
                }
            }
            
            return true;
        }

        // ========================================
        // 🗺️ MAPA PROTEGIDO
        // ========================================

        _inicializarMapaSeguro() {
            try {
                const contenedor = document.getElementById('mapa-estaciones');
                if (!contenedor || contenedor.id !== 'mapa-estaciones') {
                    throw new Error('Contenedor de mapa inválido');
                }

                // Configuración segura del mapa
                this._mapa = L.map('mapa-estaciones', {
                    center: STATIONS_CONFIG.LIMA_CENTER,
                    zoom: STATIONS_CONFIG.ZOOM_INICIAL,
                    maxZoom: STATIONS_CONFIG.MAX_ZOOM,
                    minZoom: STATIONS_CONFIG.MIN_ZOOM,
                    zoomControl: true,
                    attributionControl: true
                });

                // Validar coordenadas del centro
                if (!this._validarCoordenadas(STATIONS_CONFIG.LIMA_CENTER[0], STATIONS_CONFIG.LIMA_CENTER[1])) {
                    throw new Error('Coordenadas de centro inválidas');
                }

                // Tiles seguros
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors',
                    maxZoom: STATIONS_CONFIG.MAX_ZOOM,
                    crossOrigin: 'anonymous'
                }).addTo(this._mapa);

                this._logSecure('🗺️ Mapa protegido inicializado correctamente');

            } catch (error) {
                this._logSecure(`❌ Error inicializando mapa: ${error.message}`, 'error');
                throw error;
            }
        }

        _crearMarcadorSeguro(estacion, uvIndex = 0) {
            if (!this._mapa || this._isDestroyed) return null;

            try {
                // Validar datos de estación
                if (!this._validarEstacion(estacion)) {
                    this._logSecure(`❌ Estación inválida para marcador: ${estacion?.id}`, 'warn');
                    return null;
                }

                // Validar UV Index
                const uvValidado = this._validarUVIndex(uvIndex);
                const color = this._obtenerColorUVSeguro(uvValidado);
                
                // Crear icono seguro
                const icono = L.divIcon({
                    className: 'marcador-uv',
                    html: `
                        <div style="
                            width: 20px; 
                            height: 20px; 
                            background: ${color}; 
                            border: 2px solid white; 
                            border-radius: 50%; 
                            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
                        "></div>
                    `,
                    iconSize: [20, 20],
                    iconAnchor: [10, 10]
                });

                const lat = parseFloat(estacion.latitud);
                const lng = parseFloat(estacion.longitud);

                // Validar coordenadas
                if (!this._validarCoordenadas(lat, lng)) {
                    this._logSecure(`⚠️ Coordenadas inválidas para estación ${estacion.id}`, 'warn');
                    return null;
                }

                // Crear marcador
                const marcador = L.marker([lat, lng], { icon: icono })
                    .bindPopup(this._crearPopupSeguro(estacion, uvValidado))
                    .on('click', () => {
                        this._seleccionarEstacionSegura(estacion.id);
                    });

                return marcador;

            } catch (error) {
                this._logSecure(`❌ Error creando marcador: ${error.message}`, 'error');
                return null;
            }
        }

        _crearPopupSeguro(estacion, uvIndex) {
            // Sanitizar datos para evitar XSS
            const nombre = this._sanitizarTexto(estacion.nombre);
            const ubicacion = this._sanitizarTexto(estacion.ubicacion || 'No especificada');
            const uvSeguro = parseFloat(uvIndex).toFixed(2);
            const idSeguro = parseInt(estacion.id);

            return `
                <div style="max-width: 200px;">
                    <h4 style="margin: 0 0 5px 0;">${nombre}</h4>
                    <p style="margin: 0 0 5px 0;"><strong>Ubicación:</strong> ${ubicacion}</p>
                    <p style="margin: 0 0 10px 0;"><strong>Índice UV:</strong> ${uvSeguro}</p>
                    <button 
                        onclick="window.stationManager?.seleccionarEstacion(${idSeguro})" 
                        style="padding: 5px 10px; background: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer;">
                        Ver detalles
                    </button>
                </div>
            `;
        }

        // ========================================
        // 📋 GESTIÓN DE ESTACIONES SEGURA
        // ========================================

        async _cargarEstacionesSeguro() {
            try {
                this._mostrarCargando();
                
                // Verificar cache
                const cacheKey = 'estaciones_data';
                if (this._cache.has(cacheKey)) {
                    const cached = this._cache.get(cacheKey);
                    if (Date.now() - cached.timestamp < STATIONS_CONFIG.DATA_CACHE_DURATION) {
                        this._estaciones = cached.data;
                        await this._procesarEstacionesCargadas();
                        return;
                    }
                }

                // Cargar estaciones desde API
                const estacionesRaw = await window.api.obtenerEstaciones();
                
                if (!Array.isArray(estacionesRaw)) {
                    throw new Error('Datos de estaciones inválidos');
                }

                // Validar y limpiar datos
                this._estaciones = estacionesRaw
                    .map(estacion => this._procesarEstacion(estacion))
                    .filter(estacion => estacion !== null);

                // Guardar en cache
                this._cache.set(cacheKey, {
                    data: this._estaciones,
                    timestamp: Date.now()
                });

                await this._procesarEstacionesCargadas();
                
                this._logSecure(`✅ ${this._estaciones.length} estaciones cargadas correctamente`);

            } catch (error) {
                this._logSecure(`❌ Error cargando estaciones: ${error.message}`, 'error');
                this._mostrarError('Error al cargar estaciones');
            }
        }

        async _procesarEstacionesCargadas() {
            // Cargar marcadores
            await this._cargarMarcadoresSeguro();
            
            // Renderizar lista
            this._renderizarListaSegura();
            
            // Seleccionar primera estación válida
            if (this._estaciones.length > 0) {
                const primeraEstacion = this._estaciones.find(e => this._validarEstacion(e));
                if (primeraEstacion) {
                    await this._seleccionarEstacionSegura(primeraEstacion.id);
                }
            }
        }

        _procesarEstacion(estacionRaw) {
            try {
                const estacion = {
                    id: parseInt(estacionRaw.id),
                    nombre: this._sanitizarTexto(estacionRaw.nombre || 'Sin nombre'),
                    ubicacion: this._sanitizarTexto(estacionRaw.ubicacion || 'Sin ubicación'),
                    latitud: parseFloat(estacionRaw.latitud) || 0,
                    longitud: parseFloat(estacionRaw.longitud) || 0,
                    activa: estacionRaw.activa !== false
                };

                // Validar estación procesada
                return this._validarEstacion(estacion) ? estacion : null;

            } catch (error) {
                this._logSecure(`❌ Error procesando estación: ${error.message}`, 'error');
                return null;
            }
        }

        async _cargarMarcadoresSeguro() {
            if (!this._mapa || this._isDestroyed) return;

            try {
                // Limpiar marcadores existentes
                this._limpiarMarcadores();

                // Obtener datos UV con timeout
                const timeoutPromise = new Promise((_, reject) => {
                    setTimeout(() => reject(new Error('Timeout obteniendo datos UV')), 10000);
                });

                const dataPromise = window.api.obtenerUltimosRegistros();
                const ultimosRegistros = await Promise.race([dataPromise, timeoutPromise]);

                // Crear mapa de UV por estación
                const uvPorEstacion = new Map();
                
                if (Array.isArray(ultimosRegistros)) {
                    ultimosRegistros.forEach(registro => {
                        if (registro && registro.estacion_id && registro.registro) {
                            const uvIndex = this._validarUVIndex(registro.registro.uv_index);
                            uvPorEstacion.set(parseInt(registro.estacion_id), uvIndex);
                        }
                    });
                }

                // Crear marcadores
                let marcadoresCreados = 0;
                this._estaciones.forEach(estacion => {
                    if (this._validarCoordenadas(estacion.latitud, estacion.longitud)) {
                        const uvIndex = uvPorEstacion.get(estacion.id) || 0;
                        const marcador = this._crearMarcadorSeguro(estacion, uvIndex);
                        
                        if (marcador) {
                            marcador.addTo(this._mapa);
                            this._marcadores.set(estacion.id, marcador);
                            marcadoresCreados++;
                        }
                    }
                });

                this._logSecure(`🗺️ ${marcadoresCreados} marcadores creados`);

            } catch (error) {
                this._logSecure(`❌ Error cargando marcadores: ${error.message}`, 'error');
            }
        }

        _renderizarListaSegura() {
            const lista = document.getElementById('lista-estaciones');
            if (!lista || lista.id !== 'lista-estaciones') {
                this._logSecure('❌ Lista de estaciones no encontrada', 'warn');
                return;
            }

            lista.innerHTML = '';
            
            if (this._estaciones.length === 0) {
                lista.innerHTML = '<li>No hay estaciones disponibles</li>';
                return;
            }
            
            this._estaciones.forEach(estacion => {
                if (!this._validarEstacion(estacion)) return;
                
                const li = document.createElement('li');
                li.className = 'item-estacion';
                li.dataset.id = estacion.id;
                
                // Crear botón de forma segura
                const nombre = this._sanitizarTexto(estacion.nombre);
                const ubicacion = this._sanitizarTexto(estacion.ubicacion);
                
                li.innerHTML = `
                    <button class="btn-estacion" onclick="window.stationManager?.seleccionarEstacion(${estacion.id})">
                        <div class="info-estacion">
                            <span class="nombre-estacion">${nombre}</span>
                            <span class="ubicacion-estacion">${ubicacion}</span>
                        </div>
                    </button>
                `;
                
                lista.appendChild(li);
            });
        }

        // ========================================
        // 🎯 SELECCIÓN SEGURA
        // ========================================

        async _seleccionarEstacionSegura(estacionId) {
            try {
                if (this._isDestroyed) {
                    this._logSecure('❌ No se puede seleccionar: gestor destruido', 'warn');
                    return;
                }

                const id = parseInt(estacionId);
                if (!this._validarEstacionId(id)) {
                    this._logSecure(`❌ ID de estación inválido: ${estacionId}`, 'warn');
                    return;
                }

                // Rate limiting
                if (!this._rateLimit.canSelect(id)) {
                    return;
                }

                this._logSecure(`🎯 Seleccionando estación ID: ${id}`);
                
                const estacion = this._estaciones.find(e => e.id === id);
                if (!estacion) {
                    this._logSecure(`❌ Estación ${id} no encontrada`, 'error');
                    return;
                }

                // Actualizar selección visual
                this._actualizarSeleccionVisual(id);
                
                // Obtener datos con timeout
                const timeoutPromise = new Promise((_, reject) => {
                    setTimeout(() => reject(new Error('Timeout obteniendo datos')), 15000);
                });

                const dataPromise = window.api.obtenerUltimoRegistro(id);
                const datosEstacion = await Promise.race([dataPromise, timeoutPromise]);
                
                // Actualizar interfaz
                this._actualizarInformacionSegura(estacion, datosEstacion);
                
                // Enfocar mapa
                this._enfocarMapaSeguro(id);
                
                // Guardar selección
                this._estacionSeleccionada = estacion;
                
                // Evento para otros componentes
                document.dispatchEvent(new CustomEvent('estacionSeleccionada', {
                    detail: { estacion, datosEstacion }
                }));

                this._logSecure(`✅ Estación seleccionada: ${estacion.nombre}`);

            } catch (error) {
                this._logSecure(`❌ Error seleccionando estación: ${error.message}`, 'error');
            }
        }

        _actualizarSeleccionVisual(estacionId) {
            const lista = document.getElementById('lista-estaciones');
            if (!lista) return;

            // Remover selección anterior
            lista.querySelectorAll('.seleccionada').forEach(el => {
                el.classList.remove('seleccionada');
            });
            
            // Agregar nueva selección
            const nuevo = lista.querySelector(`[data-id="${estacionId}"]`);
            if (nuevo) {
                nuevo.classList.add('seleccionada');
            }
        }

        _actualizarInformacionSegura(estacion, datosEstacion) {
            try {
                // Validar y formatear coordenadas
                const lat = parseFloat(estacion.latitud);
                const lng = parseFloat(estacion.longitud);
                
                this._actualizarElementoSeguro('nombre-estacion', estacion.nombre);
                
                if (this._validarCoordenadas(lat, lng)) {
                    this._actualizarElementoSeguro('coordenadas-estacion', 
                        `Lat: ${lat.toFixed(4)}, Lng: ${lng.toFixed(4)}`);
                } else {
                    this._actualizarElementoSeguro('coordenadas-estacion', 'Coordenadas no válidas');
                }

                // Actualizar datos si existen
                if (datosEstacion && datosEstacion.ultimo_registro) {
                    const registro = datosEstacion.ultimo_registro;
                    
                    this._actualizarElementoSeguro('valor-temp', 
                        this._formatearValorSeguro(registro.temperatura, 1));
                    this._actualizarElementoSeguro('valor-humedad', 
                        this._formatearValorSeguro(registro.humedad, 1));
                    this._actualizarElementoSeguro('valor-uv', 
                        this._formatearValorSeguro(registro.uv_index, 2));
                    
                    // Formatear fecha y hora de forma segura
                    this._actualizarTiemposSeguro(registro);
                } else {
                    // Sin datos
                    this._actualizarElementoSeguro('valor-temp', 'N/A');
                    this._actualizarElementoSeguro('valor-humedad', 'N/A');
                    this._actualizarElementoSeguro('valor-uv', 'N/A');
                    this._actualizarElementoSeguro('ultima-actualizacion', '-');
                    this._actualizarElementoSeguro('hora-registro', '-');
                }

            } catch (error) {
                this._logSecure(`❌ Error actualizando información: ${error.message}`, 'error');
            }
        }

        _actualizarTiemposSeguro(registro) {
            try {
                if (registro.fecha && registro.hora) {
                    const fechaHora = `${registro.fecha} ${registro.hora}`;
                    const fecha = new Date(fechaHora);
                    
                    if (!isNaN(fecha.getTime())) {
                        this._actualizarElementoSeguro('ultima-actualizacion', 
                            fecha.toLocaleTimeString('es-PE', { 
                                hour: '2-digit', 
                                minute: '2-digit' 
                            }));
                        this._actualizarElementoSeguro('hora-registro', 
                            fecha.toLocaleDateString('es-PE'));
                    } else {
                        this._actualizarElementoSeguro('ultima-actualizacion', 
                            this._sanitizarTexto(registro.hora) || '-');
                        this._actualizarElementoSeguro('hora-registro', 
                            this._sanitizarTexto(registro.fecha) || '-');
                    }
                }
            } catch (error) {
                this._logSecure(`❌ Error actualizando tiempos: ${error.message}`, 'error');
                this._actualizarElementoSeguro('ultima-actualizacion', '-');
                this._actualizarElementoSeguro('hora-registro', '-');
            }
        }

        _enfocarMapaSeguro(estacionId) {
            if (!this._mapa || !this._marcadores.has(estacionId) || this._isDestroyed) {
                return;
            }
            
            try {
                const marcador = this._marcadores.get(estacionId);
                const latLng = marcador.getLatLng();
                
                // Validar coordenadas antes de volar
                if (this._validarCoordenadas(latLng.lat, latLng.lng)) {
                    this._mapa.flyTo(latLng, STATIONS_CONFIG.ZOOM_ESTACION, { 
                        duration: 1.5 
                    });
                    
                    setTimeout(() => {
                        if (!this._isDestroyed && marcador) {
                            marcador.openPopup();
                        }
                    }, 1600);
                }
                
            } catch (error) {
                this._logSecure(`❌ Error enfocando mapa: ${error.message}`, 'error');
            }
        }

        // ========================================
        // 🔒 MÉTODOS DE VALIDACIÓN
        // ========================================

        _validarEstacion(estacion) {
            if (!estacion || typeof estacion !== 'object') {
                return false;
            }
            
            const id = parseInt(estacion.id);
            if (!this._validarEstacionId(id)) {
                return false;
            }
            
            if (typeof estacion.nombre !== 'string' || 
                estacion.nombre.length === 0 || 
                estacion.nombre.length > STATIONS_CONFIG.MAX_STATION_NAME_LENGTH) {
                return false;
            }
            
            if (!this._validarCoordenadas(estacion.latitud, estacion.longitud)) {
                return false;
            }
            
            return true;
        }

        _validarEstacionId(id) {
            const numId = parseInt(id);
            return !isNaN(numId) && numId > 0 && numId <= STATIONS_CONFIG.MAX_STATION_ID;
        }

        _validarCoordenadas(lat, lng) {
            const latNum = parseFloat(lat);
            const lngNum = parseFloat(lng);
            
            if (isNaN(latNum) || isNaN(lngNum)) {
                return false;
            }
            
            return latNum >= STATIONS_CONFIG.BOUNDS.LAT_MIN && 
                   latNum <= STATIONS_CONFIG.BOUNDS.LAT_MAX &&
                   lngNum >= STATIONS_CONFIG.BOUNDS.LNG_MIN && 
                   lngNum <= STATIONS_CONFIG.BOUNDS.LNG_MAX;
        }

        _validarUVIndex(uvIndex) {
            const uv = parseFloat(uvIndex);
            if (isNaN(uv)) return 0;
            
            // Limitar a rango válido (0-20)
            return Math.max(0, Math.min(20, uv));
        }

        _sanitizarTexto(texto) {
            if (typeof texto !== 'string') return '';
            
            return texto
                .replace(/[<>]/g, '') // Eliminar < >
                .replace(/javascript:/gi, '') // Eliminar javascript:
                .replace(/on\w+=/gi, '') // Eliminar event handlers
                .substring(0, STATIONS_CONFIG.MAX_STATION_NAME_LENGTH); // Limitar longitud
        }

        // ========================================
        // 🛠️ UTILIDADES SEGURAS
        // ========================================

        _actualizarElementoSeguro(id, valor) {
            try {
                // Validar ID del elemento
                if (!STATIONS_CONFIG.VALID_CONTAINER_IDS.some(validId => 
                    id.includes(validId.split('-')[0]) || 
                    ['nombre', 'coordenadas', 'valor', 'ultima', 'hora'].some(prefix => id.startsWith(prefix))
                )) {
                    this._logSecure(`⚠️ Intento de actualizar elemento no permitido: ${id}`, 'warn');
                    return;
                }

                const elemento = document.getElementById(id);
                if (elemento) {
                    elemento.textContent = this._sanitizarTexto(String(valor));
                }
            } catch (error) {
                this._logSecure(`❌ Error actualizando elemento ${id}: ${error.message}`, 'error');
            }
        }

        _formatearValorSeguro(valor, decimales = 1) {
            if (valor === null || valor === undefined || valor === '') {
                return 'N/A';
            }
            
            const numero = parseFloat(valor);
            if (isNaN(numero)) {
                return 'N/A';
            }
            
            return numero.toFixed(decimales);
        }

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

        // ========================================
        // 🧹 LIMPIEZA Y ESTADOS
        // ========================================

        _limpiarMarcadores() {
            this._marcadores.forEach((marcador, id) => {
                try {
                    if (this._mapa && marcador) {
                        this._mapa.removeLayer(marcador);
                    }
                } catch (error) {
                    this._logSecure(`⚠️ Error removiendo marcador ${id}: ${error.message}`, 'warn');
                }
            });
            this._marcadores.clear();
        }

        _mostrarCargando() {
            const lista = document.getElementById('lista-estaciones');
            if (lista) {
                lista.innerHTML = '<li>🔄 Cargando estaciones...</li>';
            }
        }

        _mostrarError(mensaje) {
            const lista = document.getElementById('lista-estaciones');
            if (lista) {
                lista.innerHTML = `
                    <li>
                        <div style="padding: 10px; color: #dc3545;">
                            ❌ ${this._sanitizarTexto(mensaje)}
                            <button onclick="window.stationManager?.recargar()" 
                                    style="margin-left: 10px; padding: 5px 10px; background: #007bff; color: white; border: none; border-radius: 3px;">
                                🔄 Reintentar
                            </button>
                        </div>
                    </li>
                `;
            }
        }

        // ========================================
        // 🔄 ACTUALIZACIÓN AUTOMÁTICA SEGURA
        // ========================================

        _configurarActualizacionSegura() {
            // Limpiar intervalos anteriores
            this._limpiarIntervalos();

            // Actualizar marcadores cada 2 minutos
            const intervaloMarcadores = setInterval(async () => {
                try {
                    if (this._isDestroyed) {
                        clearInterval(intervaloMarcadores);
                        return;
                    }

                    await this._actualizarMarcadoresSeguro();
                    this._logSecure('🔄 Marcadores actualizados automáticamente');
                    
                } catch (error) {
                    this._logSecure(`⚠️ Error actualizando marcadores: ${error.message}`, 'warn');
                }
            }, STATIONS_CONFIG.UPDATE_INTERVAL);

            // Actualizar datos de estación seleccionada cada 2 minutos
            const intervaloEstacion = setInterval(async () => {
                try {
                    if (this._isDestroyed || !this._estacionSeleccionada) {
                        return;
                    }
                    
                    const datos = await window.api.obtenerUltimoRegistro(this._estacionSeleccionada.id);
                    this._actualizarInformacionSegura(this._estacionSeleccionada, datos);
                    
                } catch (error) {
                    this._logSecure(`⚠️ Error actualizando datos de estación: ${error.message}`, 'warn');
                }
            }, STATIONS_CONFIG.UPDATE_INTERVAL);

            this._intervalos.add(intervaloMarcadores);
            this._intervalos.add(intervaloEstacion);
        }

        async _actualizarMarcadoresSeguro() {
            if (!this._mapa || this._isDestroyed) return;

            try {
                const ultimosRegistros = await window.api.obtenerUltimosRegistros();
                const uvPorEstacion = new Map();
                
                if (Array.isArray(ultimosRegistros)) {
                    ultimosRegistros.forEach(registro => {
                        if (registro && registro.estacion_id && registro.registro) {
                            const uvIndex = this._validarUVIndex(registro.registro.uv_index);
                            uvPorEstacion.set(parseInt(registro.estacion_id), uvIndex);
                        }
                    });
                }

                // Actualizar marcadores existentes
                this._marcadores.forEach((marcador, estacionId) => {
                    const estacion = this._estaciones.find(e => e.id === estacionId);
                    if (estacion) {
                        const uvIndex = uvPorEstacion.get(estacionId) || 0;
                        
                        try {
                            this._mapa.removeLayer(marcador);
                            const nuevoMarcador = this._crearMarcadorSeguro(estacion, uvIndex);
                            if (nuevoMarcador) {
                                nuevoMarcador.addTo(this._mapa);
                                this._marcadores.set(estacionId, nuevoMarcador);
                            }
                        } catch (error) {
                            this._logSecure(`⚠️ Error actualizando marcador ${estacionId}: ${error.message}`, 'warn');
                        }
                    }
                });

            } catch (error) {
                this._logSecure(`❌ Error actualizando marcadores: ${error.message}`, 'error');
            }
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

        async recargar() {
            if (this._isDestroyed) {
                this._logSecure('❌ No se puede recargar: gestor destruido', 'warn');
                return;
            }
            
            this._logSecure('🔄 Recargando estaciones...');
            this._cache.clear();
            await this._cargarEstacionesSeguro();
        }

        async seleccionarEstacion(estacionId) {
            return this._seleccionarEstacionSegura(estacionId);
        }

        obtenerEstacionSeleccionada() {
            return this._estacionSeleccionada ? { ...this._estacionSeleccionada } : null;
        }

        obtenerTodasLasEstaciones() {
            return this._estaciones.map(estacion => ({ ...estacion }));
        }

        destruir() {
            this._logSecure('🧹 Destruyendo gestor de estaciones...');
            
            this._isDestroyed = true;
            this._limpiarIntervalos();
            this._limpiarMarcadores();
            
            if (this._mapa) {
                try {
                    this._mapa.remove();
                } catch (error) {
                    this._logSecure(`⚠️ Error destruyendo mapa: ${error.message}`, 'warn');
                }
                this._mapa = null;
            }
            
            this._estaciones = [];
            this._estacionSeleccionada = null;
            this._cache.clear();
        }

        // Getters protegidos
        get isInitialized() {
            return this._isInitialized && !this._isDestroyed;
        }

        _logSecure(message, level = 'log') {
            if (STATIONS_CONFIG.DEBUG_MODE) {
                console[level](`[SecureStations] ${message}`);
            }
        }
    }

    // ========================================
    // 🔒 VERIFICADOR DE ESTADO PROTEGIDO
    // ========================================
    
    class SecureStatusChecker {
        constructor() {
            this._elementoEstado = document.querySelector('.estado-registro');
            this._intervalo = null;
            
            if (this._elementoEstado) {
                this._iniciarVerificacionSegura();
            }
        }

        async _iniciarVerificacionSegura() {
            await this._verificarEstadoSeguro();
            
            this._intervalo = setInterval(async () => {
                await this._verificarEstadoSeguro();
            }, 30000); // 30 segundos
        }

        async _verificarEstadoSeguro() {
            try {
                if (!window.api || typeof window.api.verificarEstado !== 'function') {
                    this._mostrarError();
                    return;
                }

                const estado = await window.api.verificarEstado();
                
                if (estado && typeof estado.registrando === 'boolean') {
                    this._actualizarInterfazSegura(estado);
                } else {
                    this._mostrarError();
                }
                
            } catch (error) {
                STATIONS_CONFIG.DEBUG_MODE && console.warn('⚠️ Error verificando estado:', error);
                this._mostrarError();
            }
        }

        _actualizarInterfazSegura(estado) {
            if (!this._elementoEstado) return;

            const activo = Boolean(estado.registrando);
            const textoEstado = String(estado.estado || (activo ? 'REGISTRANDO' : 'SIN DATOS'));
            
            // Sanitizar texto
            const textoLimpio = textoEstado
                .replace(/[<>]/g, '')
                .substring(0, 50);
            
            this._elementoEstado.textContent = textoLimpio;
            this._elementoEstado.className = `estado-registro ${activo ? 'activo' : 'inactivo'}`;
        }

        _mostrarError() {
            if (this._elementoEstado) {
                this._elementoEstado.textContent = 'ERROR DE CONEXIÓN';
                this._elementoEstado.className = 'estado-registro error';
            }
        }

        destruir() {
            if (this._intervalo) {
                clearInterval(this._intervalo);
                this._intervalo = null;
            }
        }
    }

    // ========================================
    // 🌍 EXPORTACIÓN E INICIALIZACIÓN SEGURA
    // ========================================
    
    let stationManagerInstance = null;
    let statusCheckerInstance = null;

    document.addEventListener('DOMContentLoaded', async () => {
        if (STATIONS_CONFIG.DEBUG_MODE) {
            console.log('🚀 Inicializando gestión de estaciones protegida...');
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
            // Crear instancias
            stationManagerInstance = new SecureStationManager();
            statusCheckerInstance = new SecureStatusChecker();
            
            // Exportar de forma protegida
            Object.defineProperty(window, 'stationManager', {
                value: stationManagerInstance,
                writable: false,
                configurable: false
            });

            Object.defineProperty(window, 'gestorEstaciones', {
                value: stationManagerInstance,
                writable: false,
                configurable: false
            });

            // Funciones de compatibilidad protegidas
            Object.defineProperty(window, 'selectStation', {
                value: (id) => {
                    if (stationManagerInstance && stationManagerInstance.isInitialized) {
                        stationManagerInstance.seleccionarEstacion(id);
                    }
                },
                writable: false,
                configurable: false
            });

            Object.defineProperty(window, 'estacionesUtils', {
                value: Object.freeze({
                    seleccionar: (id) => stationManagerInstance?.seleccionarEstacion(id),
                    actual: () => stationManagerInstance?.obtenerEstacionSeleccionada(),
                    todas: () => stationManagerInstance?.obtenerTodasLasEstaciones() || [],
                    recargar: () => stationManagerInstance?.recargar()
                }),
                writable: false,
                configurable: false
            });
            
            if (STATIONS_CONFIG.DEBUG_MODE) {
                console.log('✅ Gestión de estaciones protegida lista');
            }
            
        } catch (error) {
            console.error('❌ Error inicializando estaciones protegidas:', error);
        }
    });

    // Cleanup automático
    window.addEventListener('beforeunload', () => {
        if (stationManagerInstance) {
            stationManagerInstance.destruir();
        }
        if (statusCheckerInstance) {
            statusCheckerInstance.destruir();
        }
    });

    if (STATIONS_CONFIG.DEBUG_MODE) {
        console.log('✅ Estaciones.js protegido cargado');
    }

})();