<?php
// Class/BankPromoService.php
require_once __DIR__ . '/../../class/conexion.php';
require_once __DIR__ . '/../../controlSucursales/Class/sucursal.php';

class BankPromoService
{
    private $conexion;
    private $sucursalModel;
    private $sucursalesConfig = [];

    public function __construct()
    {
        $this->conexion = new Conexion();
        $this->sucursalModel = new Sucursal();
        $configFile = __DIR__ . '/../config/gocuotas_sucursales.php';
        if (file_exists($configFile)) {
            $this->sucursalesConfig = require $configFile;
        }
    }

    /**
     * Consulta las ventas de PROMO BANCO comparando $ SISTEMA vs $ CONTROL (V_creditospromosbancarias)
     */
    public function getBankPromos(string $desde, string $hasta, string $sucursalTarget = 'todos', string $bancoTarget = 'todos'): array
    {
        $sucursalesToFetch = [];
        if ($sucursalTarget !== 'todos' && isset($this->sucursalesConfig[$sucursalTarget])) {
            $sucursalesToFetch[$sucursalTarget] = $this->sucursalesConfig[$sucursalTarget];
        } else {
            $sucursalesToFetch = $this->sucursalesConfig;
        }

        $allRows = [];
        $kpis = [
            'total_sistema' => 0,
            'total_control' => 0,
            'total_diferencia' => 0,
            'count_ok' => 0,
            'count_diff' => 0,
            'count_total' => 0
        ];

        foreach ($sucursalesToFetch as $keySuc => $conf) {
            $nroSuc = $conf['nro_sucursal'] ?? null;
            $nombreSuc = $conf['nombre'] ?? $keySuc;
            if (!$nroSuc) continue;

            // 1. Obtener $ SISTEMA de RO_T_VENTA_DIARIA_SUCURSALES
            $sistemaSales = $this->sucursalModel->traerImportesTotalesPorPeriodo($nroSuc, $desde, $hasta, 'PROMO BANCO');
            $sistemaMap = [];
            if (is_array($sistemaSales)) {
                foreach ($sistemaSales as $s) {
                    $fechaRaw = $s['FECHA'];
                    $fecha = is_a($fechaRaw, 'DateTime') ? $fechaRaw->format('Y-m-d') : substr(strval($fechaRaw), 0, 10);
                    $monto = floatval($s['IMPORTE_$_SISTEMA'] ?? 0);
                    $sistemaMap[$fecha] = $monto;
                }
            }

            // 2. Obtener $ CONTROL de V_creditospromosbancarias en la BD local de la sucursal
            $controlMap = [];
            $promoDetailsMap = [];

            if ($this->conexion->setearDnsBaseName($nroSuc)) {
                $connLocal = $this->conexion->conectar();
                if ($connLocal) {
                    $sql = "SELECT CONVERT(VARCHAR(10), Fecha, 120) AS FechaOnly, 
                                   Promocion, 
                                   SUM(importe) AS TotalControl 
                            FROM V_creditospromosbancarias 
                            WHERE Fecha BETWEEN '$desde 00:00:00' AND '$hasta 23:59:59' 
                            GROUP BY CONVERT(VARCHAR(10), Fecha, 120), Promocion";

                    $stmt = sqlsrv_query($connLocal, $sql);
                    if ($stmt !== false) {
                        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                            $fOnly = $row['FechaOnly'];
                            $imp = floatval($row['TotalControl']);
                            $promoName = trim($row['Promocion'] ?? '');

                            if (!isset($controlMap[$fOnly])) {
                                $controlMap[$fOnly] = 0;
                                $promoDetailsMap[$fOnly] = [];
                            }
                            $controlMap[$fOnly] += $imp;
                            if ($promoName && !in_array($promoName, $promoDetailsMap[$fOnly])) {
                                $promoDetailsMap[$fOnly][] = $promoName;
                            }
                        }
                    }
                }
            }

            // Combine all dates from both $ SISTEMA and $ CONTROL
            $allDates = array_unique(array_merge(array_keys($sistemaMap), array_keys($controlMap)));
            sort($allDates);

            foreach ($allDates as $fecha) {
                $montoSistema = $sistemaMap[$fecha] ?? 0;
                $montoControl = $controlMap[$fecha] ?? 0;

                // Si $ SISTEMA viene como negativo (ej. -$23.480) y $ CONTROL como positivo (23.480), normalizamos el signo para comparar
                $normSistema = abs($montoSistema);
                $normControl = abs($montoControl);

                // Filtrar por banco si se especificó
                $promosList = $promoDetailsMap[$fecha] ?? ['PROMO BANCO'];
                $promoStr = implode(', ', $promosList);

                if ($bancoTarget !== 'todos') {
                    $matchBanco = false;
                    foreach ($promosList as $pr) {
                        if (stripos($pr, $bancoTarget) !== false) {
                            $matchBanco = true;
                            break;
                        }
                    }
                    if (!$matchBanco) continue;
                }

                $diff = round($normSistema - $normControl, 2);
                $isOk = (Math_abs_diff($normSistema, $normControl) < 0.01 && $normControl > 0);

                $allRows[] = [
                    'id' => "PB-{$nroSuc}-{$fecha}",
                    'fecha' => $fecha,
                    'sucursal' => $nombreSuc,
                    'nro_sucursal' => $nroSuc,
                    'promocion' => $promoStr ?: 'PROMO BANCO',
                    'monto_sistema' => $montoSistema,
                    'monto_control' => $montoControl,
                    'norm_sistema' => $normSistema,
                    'norm_control' => $normControl,
                    'diferencia' => $diff,
                    'status' => $isOk ? 'ok' : ($normControl == 0 ? 'pendiente' : 'diferencia')
                ];

                $kpis['total_sistema'] += $normSistema;
                $kpis['total_control'] += $normControl;
                $kpis['total_diferencia'] += abs($diff);
                $kpis['count_total']++;

                if ($isOk) {
                    $kpis['count_ok']++;
                } else {
                    $kpis['count_diff']++;
                }
            }
        }

        // Ordenar por fecha descendente
        usort($allRows, function ($a, $b) {
            return strcmp($b['fecha'], $a['fecha']);
        });

        return [
            'rows' => $allRows,
            'kpis' => $kpis
        ];
    }
}

function Math_abs_diff($a, $b) {
    return abs($a - $b);
}
