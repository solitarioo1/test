<?php
namespace Model;

use Exception;
use PDO;

class MapaCalor extends ActiveRecord {
    // Propiedades públicas para acceso directo
    public $id;
    public $estacion_id;
    public $fecha;
    public $hora;
    public $uv_index;
    public $temperatura;
    public $humedad;

    // Configuración de la tabla
    protected static $tabla = 'registros_uv';
    protected static $columnasDB = [
        'id', 'estacion_id', 'fecha', 'hora', 
        'uv_index', 'temperatura', 'humedad'
    ];

    /**
     * Obtiene datos para generar mapas de calor
     * @param string $tipoDato (uv_index|temperatura|humedad)
     * @return array
     */
    public static function obtenerDatosMapa($tipoDato = 'uv_index') {
        self::validarTipoDato($tipoDato);

        $query = "
            SELECT e.id, e.nombre, e.latitud, e.longitud,
                   r.{$tipoDato} as valor, r.fecha, r.hora
            FROM estaciones e
            JOIN (
                SELECT estacion_id, MAX(CONCAT(fecha, ' ', hora)) as max_fecha
                FROM registros_uv
                GROUP BY estacion_id
            ) ultimos ON e.id = ultimos.estacion_id
            JOIN registros_uv r ON r.estacion_id = ultimos.estacion_id 
                AND CONCAT(r.fecha, ' ', r.hora) = ultimos.max_fecha
            WHERE e.activo = 1
            ORDER BY e.id";

        $stmt = self::$db->prepare($query);
        $stmt->execute();

        return self::formatearDatosMapa($stmt->fetchAll(PDO::FETCH_ASSOC), $tipoDato);
    }

    /**
     * Obtiene datos históricos para animación de mapas
     * @param int $horasHistorial (1-24)
     * @return array
     */
    public static function obtenerDatosHistoricos($horasHistorial = 5) {
        $horasHistorial = max(1, min(24, (int)$horasHistorial)); // Asegurar entre 1-24

        $query = "
            SELECT e.id, e.nombre, e.latitud, e.longitud,
                   r.fecha, r.hora, r.uv_index, r.temperatura, r.humedad
            FROM estaciones e
            JOIN registros_uv r ON r.estacion_id = e.id
            WHERE e.activo = 1
            AND CONCAT(r.fecha, ' ', r.hora) >= DATE_SUB(NOW(), INTERVAL ? HOUR)
            ORDER BY r.fecha DESC, r.hora DESC";

        $stmt = self::$db->prepare($query);
        $stmt->execute([$horasHistorial]);

        return self::organizarDatosHistoricos($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // ============= MÉTODOS PRIVADOS DE APOYO ============= //

    /**
     * Valida el tipo de dato para el mapa de calor
     * @param string $tipoDato
     * @throws Exception
     */
    private static function validarTipoDato($tipoDato) {
        $tiposPermitidos = ['uv_index', 'temperatura', 'humedad'];
        if (!in_array($tipoDato, $tiposPermitidos)) {
            throw new Exception("Tipo de dato no válido para mapa de calor");
        }
    }

    /**
     * Formatea los datos para el mapa de calor
     * @param array $datos
     * @param string $tipoDato
     * @return array
     */
    private static function formatearDatosMapa($datos, $tipoDato) {
        return array_map(function($registro) use ($tipoDato) {
            return [
                'estacion_id' => (int)$registro['id'],
                'nombre' => $registro['nombre'],
                'latitud' => (float)$registro['latitud'],
                'longitud' => (float)$registro['longitud'],
                'valor' => (float)$registro['valor'],
                'fecha' => $registro['fecha'],
                'hora' => $registro['hora'],
                'tipo' => $tipoDato
            ];
        }, $datos);
    }

    /**
     * Organiza datos históricos por hora
     * @param array $datos
     * @return array
     */
    private static function organizarDatosHistoricos($datos) {
        $organizados = [];

        foreach ($datos as $registro) {
            $clave = $registro['fecha'] . ' ' . $registro['hora'];
            
            if (!isset($organizados[$clave])) {
                $organizados[$clave] = [
                    'fecha' => $registro['fecha'],
                    'hora' => $registro['hora'],
                    'registros' => []
                ];
            }

            $organizados[$clave]['registros'][] = [
                'estacion_id' => (int)$registro['id'],
                'nombre' => $registro['nombre'],
                'latitud' => (float)$registro['latitud'],
                'longitud' => (float)$registro['longitud'],
                'uv_index' => (float)$registro['uv_index'],
                'temperatura' => (float)$registro['temperatura'],
                'humedad' => (float)$registro['humedad']
            ];
        }

        return array_values($organizados);
    }

    /**
     * Genera datos para heatmap de Leaflet
     * @param array $registros
     * @param string $tipoDato
     * @return array
     */
    public static function generarDatosHeatmap($registros, $tipoDato = 'uv_index') {
        self::validarTipoDato($tipoDato);

        return array_map(function($registro) use ($tipoDato) {
            return [
                'lat' => $registro['latitud'],
                'lng' => $registro['longitud'],
                'value' => self::normalizarValor($registro[$tipoDato], $tipoDato),
                'nombre' => $registro['nombre'],
                'valor_real' => $registro[$tipoDato]
            ];
        }, $registros);
    }

    /**
     * Normaliza valores para el heatmap (0-1)
     * @param float $valor
     * @param string $tipoDato
     * @return float
     */
    private static function normalizarValor($valor, $tipoDato) {
        $rangos = [
            'uv_index' => ['min' => 0, 'max' => 15],
            'temperatura' => ['min' => -10, 'max' => 40],
            'humedad' => ['min' => 0, 'max' => 100]
        ];

        $rango = $rangos[$tipoDato];
        return max(0, min(1, ($valor - $rango['min']) / ($rango['max'] - $rango['min'])));
    }
}