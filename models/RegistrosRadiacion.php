<?php

namespace Model;

use Exception;

class RegistrosRadiacion extends ActiveRecord {
    
    protected static $tabla = 'registros_uv';
    protected static $columnasDB = [
        'id', 'estacion_id', 'fecha', 'hora', 'uv_index', 
        'temperatura', 'humedad', 'voltaje'
    ];
    
    protected static $validaciones = [
        'estacion_id' => ['required', 'numeric'],
        'fecha' => ['required', 'date'],
        'hora' => ['required'],
        'uv_index' => ['required', 'numeric'],
        'temperatura' => ['numeric'],
        'humedad' => ['numeric'],
        'voltaje' => ['numeric']
    ];

    // Propiedades del modelo
    public $id;
    public $estacion_id;
    public $fecha;
    public $hora;
    public $uv_index;
    public $temperatura;
    public $humedad;
    public $voltaje;

    public function __construct($args = []) {
        $this->id = $args['id'] ?? null;
        $this->estacion_id = $args['estacion_id'] ?? null;
        $this->fecha = $args['fecha'] ?? date('Y-m-d');
        $this->hora = $args['hora'] ?? date('H:i:s');
        $this->uv_index = $args['uv_index'] ?? null;
        $this->temperatura = $args['temperatura'] ?? null;
        $this->humedad = $args['humedad'] ?? null;
        $this->voltaje = $args['voltaje'] ?? null;
    }

    // ========================================
    // MÉTODOS PARA LA INTERFAZ WEB
    // ========================================

    /**
     * Obtener todas las estaciones con su información básica
     * Para el sidebar izquierdo y el mapa
     */
    public static function obtenerEstaciones() {
        try {
            $query = "
                SELECT 
                    e.id,
                    e.nombre,
                    e.latitud,
                    e.longitud,
                    e.ubicacion,
                    e.activo,
                    COUNT(r.id) as total_registros,
                    MAX(CONCAT(r.fecha, ' ', r.hora)) as ultimo_registro
                FROM estaciones e
                LEFT JOIN registros_uv r ON e.id = r.estacion_id
                WHERE e.activo = 1
                GROUP BY e.id, e.nombre, e.latitud, e.longitud, e.ubicacion, e.activo
                ORDER BY e.nombre ASC
            ";
            
            return self::query($query);
            
        } catch (Exception $e) {
            error_log("Error obteniendo estaciones: " . $e->getMessage());
            return [];
        }
    }



    public static function obtenerRegistrosDia($estacionId, $fecha = null) {
        try {
            // ✅ FORZAR ZONA HORARIA DE LIMA
            date_default_timezone_set('America/Lima');
            
            // ✅ PHP DECIDE QUE FECHA USAR - SIEMPRE
            $fechaSolicitada = $fecha ?? date('Y-m-d');
            $fechaUsada = $fechaSolicitada;
            $usandoFallback = false;
            $fechaFormateadaTitulo = '';
            
            // ✅ CACHE ADAPTADO AL NUEVO FORMATO
            $cacheFile = __DIR__ . '/../cache/registros_dia_' . $estacionId . '_' . $fechaUsada . '.json';
            $cacheTime = 300; // 5 minutos
            
            // Verificar cache válido
            if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
                $cached = json_decode(file_get_contents($cacheFile), true);
                if ($cached && isset($cached['registros']) && isset($cached['metadatos_fecha'])) {
                    return $cached; // ✅ Retornar datos completos del cache
                }
            }
            
            $query = "
                SELECT 
                    r.id, r.estacion_id, r.fecha, r.hora, 
                    r.uv_index, r.temperatura, r.humedad, r.voltaje,
                    e.nombre as estacion_nombre, e.latitud, e.longitud, e.ubicacion,
                    CONCAT(r.fecha, ' ', r.hora) as fecha_completa
                FROM registros_uv r
                JOIN estaciones e ON e.id = r.estacion_id
                WHERE r.estacion_id = ? 
                AND r.fecha = ?
                ORDER BY r.fecha ASC, r.hora ASC
            ";

            $resultado = self::query($query, [$estacionId, $fechaUsada]);
            
            // ✅ FALLBACK: Si no hay datos, buscar último día disponible
            if (empty($resultado)) {
                $queryUltimaFecha = "
                    SELECT fecha 
                    FROM registros_uv 
                    WHERE estacion_id = ? 
                    ORDER BY fecha DESC 
                    LIMIT 1
                ";
                
                $ultimaFecha = self::query($queryUltimaFecha, [$estacionId]);
                
                if (!empty($ultimaFecha)) {
                    $fechaUsada = $ultimaFecha[0]['fecha'];
                    $usandoFallback = true;
                    
                    // ✅ CAMBIAR CLAVE DE CACHE PARA FECHA FALLBACK
                    $cacheFile = __DIR__ . '/../cache/registros_dia_' . $estacionId . '_' . $fechaUsada . '.json';
                    
                    // Verificar cache para fecha fallback
                    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
                        $cached = json_decode(file_get_contents($cacheFile), true);
                        if ($cached && isset($cached['registros']) && isset($cached['metadatos_fecha'])) {
                            // ✅ ACTUALIZAR METADATOS PARA REFLEJAR FALLBACK
                            $cached['metadatos_fecha']['fecha_solicitada'] = $fechaSolicitada;
                            $cached['metadatos_fecha']['usando_fallback'] = true;
                            return $cached;
                        }
                    }
                    
                    // Nueva consulta con la última fecha disponible
                    $resultado = self::query($query, [$estacionId, $fechaUsada]);
                }
            }
            
            // ✅ PHP FORMATEA LA FECHA PARA EL TITULO (en español)
            if ($fechaUsada) {
                $fechaObj = new \DateTime($fechaUsada);
                $fechaFormateadaTitulo = $fechaObj->format('j \d\e F \d\e Y');
                
                // Traducir meses al español
                $mesesEspanol = [
                    'January' => 'enero', 'February' => 'febrero', 'March' => 'marzo',
                    'April' => 'abril', 'May' => 'mayo', 'June' => 'junio',
                    'July' => 'julio', 'August' => 'agosto', 'September' => 'septiembre',
                    'October' => 'octubre', 'November' => 'noviembre', 'December' => 'diciembre'
                ];
                
                $mesIngles = $fechaObj->format('F');
                $fechaFormateadaTitulo = str_replace($mesIngles, $mesesEspanol[$mesIngles], $fechaFormateadaTitulo);
            }
            
            // ✅ PREPARAR DATOS COMPLETOS CON METADATOS
            $datosCompletos = [
                'registros' => $resultado,
                'metadatos_fecha' => [
                    'fecha_solicitada' => $fechaSolicitada,
                    'fecha_usada' => $fechaUsada,
                    'fecha_titulo' => $fechaFormateadaTitulo,
                    'usando_fallback' => $usandoFallback,
                    'total_registros' => count($resultado),
                    'rango_horario' => !empty($resultado) ? [
                        'primera_hora' => $resultado[0]['hora'],
                        'ultima_hora' => end($resultado)['hora']
                    ] : null,
                    'cache_timestamp' => time() // ✅ Para debug
                ]
            ];
            
            // ✅ GUARDAR EN CACHE (solo si hay datos)
            if (!empty($resultado)) {
                // Crear directorio si no existe
                if (!is_dir(dirname($cacheFile))) {
                    mkdir(dirname($cacheFile), 0755, true);
                }
                file_put_contents($cacheFile, json_encode($datosCompletos));
            }
            
            return $datosCompletos;
            
        } catch (Exception $e) {
            error_log("Error obteniendo registros del día: " . $e->getMessage());
            return [
                'registros' => [],
                'metadatos_fecha' => [
                    'fecha_solicitada' => $fecha ?? date('Y-m-d'),
                    'fecha_usada' => null,
                    'fecha_titulo' => 'Error al obtener fecha',
                    'usando_fallback' => false,
                    'total_registros' => 0,
                    'error' => $e->getMessage()
                ]
            ];
        }
    }


    // ✅ NUEVO MÉTODO: Formatear para gráfica CON METADATOS
    public static function formatearParaGraficaConMetadatos($datosCompletos) {
        $registros = $datosCompletos['registros'] ?? [];
        $metadatos = $datosCompletos['metadatos_fecha'] ?? [];
        
        $datos = [
            'labels' => [],
            'datasets' => [
                [
                    'label' => 'Índice UV',
                    'data' => [],
                    'borderColor' => '#ff6b35',
                    'backgroundColor' => 'rgba(255, 107, 53, 0.1)',
                    'tension' => 0.4
                ]
            ],
            'metadatos_fecha' => $metadatos // ✅ INCLUIR METADATOS
        ];

        foreach ($registros as $registro) {
            // Formatear hora para mostrar (ej: "08:30")
            $hora = date('H:i', strtotime($registro['hora']));
            $datos['labels'][] = $hora;
            $datos['datasets'][0]['data'][] = (float)$registro['uv_index'];
        }

        return $datos;
    }

    /**
     * Obtener el último registro de cada estación activa
     * Para mostrar datos en tiempo real
     */
    public static function obtenerUltimosRegistros() {
        try {
            // Cache de 30 segundos
            $cacheFile = __DIR__ . '/../cache/ultimos_registros.json';
            $cacheTime = 30; // segundos
            
            // Verificar si existe cache válido
            if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
                $cached = json_decode(file_get_contents($cacheFile), true);
                if ($cached) return $cached;
            }
            
            // Tu consulta optimizada actual
            $query = "
                SELECT * FROM (
                    SELECT 
                        r.id, r.estacion_id, r.fecha, r.hora, r.uv_index,
                        r.temperatura, r.humedad, r.voltaje,
                        e.nombre as estacion_nombre, e.latitud, e.longitud, e.ubicacion,
                        CONCAT(r.fecha, ' ', r.hora) as fecha_completa,
                        ROW_NUMBER() OVER (PARTITION BY r.estacion_id ORDER BY r.fecha DESC, r.hora DESC) as rn
                    FROM registros_uv r
                    JOIN estaciones e ON e.id = r.estacion_id
                    WHERE e.activo = 1
                ) ranked 
                WHERE rn = 1
                ORDER BY fecha_completa DESC
            ";
            
            $resultado = self::query($query);
            
            // Guardar en cache
            if (!is_dir(dirname($cacheFile))) {
                mkdir(dirname($cacheFile), 0755, true);
            }
            file_put_contents($cacheFile, json_encode($resultado));
            
            return $resultado;
            
        } catch (Exception $e) {
            error_log("Error: " . $e->getMessage());
            return [];
        }
    }


    public static function obtenerUltimoRegistroPorEstacion($estacionId) {
        try {
            // Cache de 60 segundos (1 minuto)
            $cacheFile = __DIR__ . '/../cache/ultimo_estacion_' . $estacionId . '.json';
            $cacheTime = 60;
            
            // Verificar cache válido
            if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
                $cached = json_decode(file_get_contents($cacheFile), true);
                if ($cached) return $cached;
            }
            
            $query = "
                SELECT 
                    r.id,
                    r.estacion_id,
                    r.fecha,
                    r.hora,
                    r.uv_index,
                    r.temperatura,
                    r.humedad,
                    r.voltaje,
                    e.nombre as estacion_nombre,
                    e.latitud,
                    e.longitud,
                    e.ubicacion,
                    CONCAT(r.fecha, ' ', r.hora) as fecha_completa
                FROM registros_uv r
                JOIN estaciones e ON e.id = r.estacion_id
                WHERE r.estacion_id = ?
                ORDER BY r.fecha DESC, r.hora DESC
                LIMIT 1
            ";
            
            $resultado = self::query($query, [$estacionId]);
            $final = !empty($resultado) ? $resultado[0] : null;
            
            // Guardar en cache
            file_put_contents($cacheFile, json_encode($final));
            
            return $final;
            
        } catch (Exception $e) {
            error_log("Error obteniendo último registro por estación: " . $e->getMessage());
            return null;
        }
    }




    /**
     * Obtener registros por rango de fechas para una estación
     * Para análisis históricos o gráficas personalizadas
     */
    public static function obtenerRegistrosPorRango($estacionId, $fechaInicio, $fechaFin, $horaInicio = '00:00:00', $horaFin = '23:59:59') {
        try {
            $query = "
                SELECT 
                    r.id,
                    r.fecha,
                    r.hora,
                    r.uv_index,
                    r.temperatura,
                    r.humedad,
                    r.voltaje,
                    e.nombre as estacion_nombre,
                    e.ubicacion as estacion_ubicacion,
                    CONCAT(r.fecha, ' ', r.hora) as fecha_completa
                FROM registros_uv r
                JOIN estaciones e ON e.id = r.estacion_id
                WHERE r.estacion_id = ?
                AND r.fecha BETWEEN ? AND ?
                AND TIME(r.hora) BETWEEN ? AND ?
                ORDER BY r.fecha ASC, r.hora ASC
            ";
            
            return self::query($query, [$estacionId, $fechaInicio, $fechaFin, $horaInicio, $horaFin]);
            
        } catch (Exception $e) {
            error_log("Error obteniendo registros por rango: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener estadísticas del día para todas las estaciones
     * Para dashboard o resúmenes
     */
    public static function obtenerEstadisticasDia($fecha = null) {
        try {
            $fecha = $fecha ?? date('Y-m-d');
            
            $query = "
                SELECT 
                    e.id as estacion_id,
                    e.nombre as estacion_nombre,
                    COUNT(r.id) as total_registros,
                    AVG(r.uv_index) as uv_promedio,
                    MAX(r.uv_index) as uv_maximo,
                    MIN(r.uv_index) as uv_minimo,
                    AVG(r.temperatura) as temp_promedio,
                    MAX(r.temperatura) as temp_maxima,
                    MIN(r.temperatura) as temp_minima,
                    AVG(r.humedad) as humedad_promedio,
                    MAX(CONCAT(r.fecha, ' ', r.hora)) as ultimo_registro
                FROM estaciones e
                LEFT JOIN registros_uv r ON e.id = r.estacion_id AND r.fecha = ?
                WHERE e.activo = 1
                GROUP BY e.id, e.nombre
                ORDER BY e.nombre ASC
            ";
            
            return self::query($query, [$fecha]);
            
        } catch (Exception $e) {
            error_log("Error obteniendo estadísticas del día: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verificar si hay datos recientes (últimos 30 minutos)
     * Para mostrar estado "REGISTRANDO..."
     */
    public static function hayDatosRecientes($minutos = 30) {
        try {
            $query = "
                SELECT COUNT(*) as count
                FROM registros_uv 
                WHERE CONCAT(fecha, ' ', hora) >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
            ";
            
            $resultado = self::query($query, [$minutos]);
            return !empty($resultado) && $resultado[0]['count'] > 0;
            
        } catch (Exception $e) {
            error_log("Error verificando datos recientes: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener coordenadas de todas las estaciones activas
     * Para inicializar el mapa
     */
    public static function obtenerCoordenadasEstaciones() {
        try {
            $query = "
                SELECT 
                    id,
                    nombre,
                    latitud,
                    longitud,
                    ubicacion
                FROM estaciones 
                WHERE activo = 1
                ORDER BY nombre ASC
            ";
            
            return self::query($query);
            
        } catch (Exception $e) {
            error_log("Error obteniendo coordenadas: " . $e->getMessage());
            return [];
        }
    }

    // ========================================
    // MÉTODOS AUXILIARES
    // ========================================

    /**
     * Formatear datos para Chart.js
     * Convierte los registros en formato compatible con gráficas
     */
    public static function formatearParaGrafica($registros) {
        $datos = [
            'labels' => [],
            'datasets' => [
                [
                    'label' => 'Índice UV',
                    'data' => [],
                    'borderColor' => '#ff6b35',
                    'backgroundColor' => 'rgba(255, 107, 53, 0.1)',
                    'tension' => 0.4
                ]
            ]
        ];

        foreach ($registros as $registro) {
            // Formatear hora para mostrar (ej: "08:30")
            $hora = date('H:i', strtotime($registro['hora']));
            $datos['labels'][] = $hora;
            $datos['datasets'][0]['data'][] = (float)$registro['uv_index'];
        }

        return $datos;
    }

    /**
     * Obtener información detallada de una estación
     */
    public static function obtenerInfoEstacion($estacionId) {
        try {
            $query = "
                SELECT 
                    e.*,
                    COUNT(r.id) as total_registros_historicos,
                    MAX(CONCAT(r.fecha, ' ', r.hora)) as ultimo_registro_fecha
                FROM estaciones e
                LEFT JOIN registros_uv r ON e.id = r.estacion_id
                WHERE e.id = ?
                GROUP BY e.id
            ";
            
            $resultado = self::query($query, [$estacionId]);
            return !empty($resultado) ? $resultado[0] : null;
            
        } catch (Exception $e) {
            error_log("Error obteniendo info de estación: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Validar que la estación existe y está activa
     */
    public static function validarEstacion($estacionId) {
        try {
            $query = "SELECT id FROM estaciones WHERE id = ? AND activo = 1";
            $resultado = self::query($query, [$estacionId]);
            return !empty($resultado);
        } catch (Exception $e) {
            return false;
        }
    }

    // ========================================
    // MÉTODOS DE UTILIDAD PARA JSON
    // ========================================

    /**
     * Formatear un registro para respuesta JSON
     */
    public function toJsonArray() {
        return [
            'id' => (int)$this->id,
            'estacion_id' => (int)$this->estacion_id,
            'fecha' => $this->fecha,
            'hora' => $this->hora,
            'uv_index' => (float)$this->uv_index,
            'temperatura' => $this->temperatura ? (float)$this->temperatura : null,
            'humedad' => $this->humedad ? (float)$this->humedad : null,
            'voltaje' => $this->voltaje ? (float)$this->voltaje : null
        ];
    }

    /**
     * Formatear múltiples registros para respuesta JSON
     */
    public static function formatearArrayParaJson($registros) {
        $resultado = [];
        foreach ($registros as $registro) {
            if (is_array($registro)) {
                // Si viene de query() directo
                $resultado[] = [
                    'id' => isset($registro['id']) ? (int)$registro['id'] : null,
                    'estacion_id' => isset($registro['estacion_id']) ? (int)$registro['estacion_id'] : null,
                    'fecha' => $registro['fecha'] ?? null,
                    'hora' => $registro['hora'] ?? null,
                    'uv_index' => isset($registro['uv_index']) ? (float)$registro['uv_index'] : null,
                    'temperatura' => isset($registro['temperatura']) && $registro['temperatura'] !== null ? (float)$registro['temperatura'] : null,
                    'humedad' => isset($registro['humedad']) && $registro['humedad'] !== null ? (float)$registro['humedad'] : null,
                    'voltaje' => isset($registro['voltaje']) && $registro['voltaje'] !== null ? (float)$registro['voltaje'] : null,
                    'estacion_nombre' => $registro['estacion_nombre'] ?? null,
                    'fecha_completa' => $registro['fecha_completa'] ?? null
                ];
            } else {
                // Si es instancia del modelo
                $resultado[] = $registro->toJsonArray();
            }
        }
        return $resultado;
    }
}