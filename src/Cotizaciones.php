<?php

namespace biller\bcu;

use DateTime;
use Exception;
use biller\bcu\UltimoCierre;

class Cotizaciones
{
    use WsBcu;

    public static function obtener($fecha = null, $moneda = 2225, $grupo = 0): float
    {
        if (isset($fecha)) {
            if (!DateTime::createFromFormat('Y-m-d', $fecha)) {
                throw new Exception('Formato de fecha no es AAAA-MM-DD');
            }
        } else {
            $fecha = UltimoCierre::obtener();
        }

        $cache = self::cacheGet($fecha, $moneda, $grupo);
        if ($cache) {
            return $cache;
        }

        $params = [
            'Entrada' => [
                'FechaDesde' => $fecha,
                'FechaHasta' => $fecha,
                'Grupo' => $grupo,
                'Moneda' => ['item' => $moneda],
            ],
        ];

        $client = self::getSoapClient('awsbcucotizaciones');
        $response = $client->Execute($params);

        $cotizacion = $response->Salida->datoscotizaciones->{'datoscotizaciones.dato'}->TCC;

        self::cachePut($fecha, $moneda, $grupo, $cotizacion);

        return $cotizacion;
    }

    private static function cacheGet(string $fecha, int $moneda, int $grupo)
    {
        $path = __DIR__ . "/../cache/$fecha/$grupo/$moneda";

        if (!file_exists($path)) {
            return false;
        }

        return file_get_contents($path);
    }

    private static function cachePut(string $fecha, int $moneda, int $grupo, float $cotizacion)
    {
        $path = __DIR__ . "/../cache/$fecha/$grupo";

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        file_put_contents("$path/$moneda", $cotizacion);
    }
}
