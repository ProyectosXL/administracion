<?php
// Class/BankPromoService.php
require_once __DIR__ . '/../../class/conexion.php';

class BankPromoService
{
    private $conexion;
    private $sucursalesConfig = [];
    private $entorno = '';
    private $usuario = 'AUDITORIA';

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $this->entorno = $_SESSION['entorno'] ?? '';
        $this->usuario = $_SESSION['usuario'] ?? 'AUDITORIA';

        $this->conexion = new Conexion();
        $configFile = __DIR__ . '/../config/gocuotas_sucursales.php';
        if (file_exists($configFile)) {
            $this->sucursalesConfig = require $configFile;
        }
    }

    /**
     * Devuelve una conexión a la base donde reside RO_T_VENTA_DIARIA_SUCURSALES según el entorno.
     */
    private function getLocalesConnection()
    {
        if ($this->entorno === 'uy') {
            return $this->conexion->conectar('suc_uy');
        }
        if ($this->conexion->env === 'DEV') {
            return $this->conexion->conectar('central');
        }
        return $this->conexion->conectar('locales');
    }

    /**
     * Obtiene en una sola consulta la configuración de conexión de todas las sucursales solicitadas.
     */
    private function getBranchConfigsMap(array $nroSucursales): array
    {
        $map = [];
        $ids = array_values(array_unique(array_filter(array_map('intval', $nroSucursales), function ($v) {
            return $v > 0;
        })));

        if (empty($ids)) {
            return $map;
        }

        $connCentral = $this->conexion->conectar('central');
        if (!$connCentral) {
            return $map;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT NRO_SUCURSAL, CONEXION_DNS, BASE_NOMBRE, USUARIO_DNS, CLAVE_DNS, HABILITADO
                FROM [XL-LAKERBIS].locales_lakers.dbo.SUCURSALES_LAKERS
                WHERE NRO_SUC_MADRE IS NULL
                  AND NRO_SUCURSAL IN ($placeholders)";

        $stmt = sqlsrv_query($connCentral, $sql, $ids, ['QueryTimeout' => 10]);
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $nro = strval($row['NRO_SUCURSAL']);
                $map[$nro] = $row;
            }
            sqlsrv_free_stmt($stmt);
        } else {
            error_log("Error al consultar SUCURSALES_LAKERS en BankPromoService: " . print_r(sqlsrv_errors(), true));
        }

        sqlsrv_close($connCentral);
        return $map;
    }

    /**
     * Obtiene en una sola consulta los importes de RO_T_VENTA_DIARIA_SUCURSALES para todas las sucursales.
     */
    private function getSistemaSalesMap(array $nroSucursales, string $desde, string $hasta): array
    {
        $salesByBranch = [];
        $ids = array_values(array_unique(array_filter(array_map('intval', $nroSucursales), function ($v) {
            return $v > 0;
        })));

        if (empty($ids)) {
            return $salesByBranch;
        }

        $connLocales = $this->getLocalesConnection();
        if (!$connLocales) {
            error_log("No se pudo conectar a la base locales/central en getSistemaSalesMap.");
            return $salesByBranch;
        }

        $tabla = ($this->entorno === 'uy') ? 'RO_T_VENTA_DIARIA_SUCURSALES_UY' : 'RO_T_VENTA_DIARIA_SUCURSALES';
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT ID, FECHA, NRO_SUCURSAL, IMPORTE_\$_SISTEMA, VERIFICADO, OBSERVACIONES, USUARIO
                FROM {$this->conexion->prefix}{$tabla}
                WHERE NRO_SUCURSAL IN ($placeholders)
                  AND FECHA BETWEEN ? AND ?
                  AND MEDIO_PAGO = 'PROMO BANCO'
                ORDER BY FECHA";

        $params = array_merge($ids, [$desde, $hasta]);
        $stmt = sqlsrv_query($connLocales, $sql, $params, ['QueryTimeout' => 15]);

        if ($stmt !== false) {
            while ($s = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $nro = strval($s['NRO_SUCURSAL']);
                $fechaRaw = $s['FECHA'];
                $fecha = is_a($fechaRaw, 'DateTime') ? $fechaRaw->format('Y-m-d') : substr(strval($fechaRaw), 0, 10);
                $salesByBranch[$nro][$fecha] = [
                    'monto' => floatval($s['IMPORTE_$_SISTEMA'] ?? 0),
                    'id_registro' => $s['ID'] ?? null,
                    'verificado' => intval($s['VERIFICADO'] ?? 0),
                    'observaciones' => trim($s['OBSERVACIONES'] ?? ''),
                    'usuario' => trim($s['USUARIO'] ?? '')
                ];
            }
            sqlsrv_free_stmt($stmt);
        } else {
            error_log("Error en getSistemaSalesMap: " . print_r(sqlsrv_errors(), true));
        }

        sqlsrv_close($connLocales);
        return $salesByBranch;
    }

    /**
     * Verifica en paralelo qué servidores locales responden por TCP antes de intentar sqlsrv_connect.
     * Evita acumular esperas secuenciales de 15s por cada sucursal offline.
     */
    private function probeBranchesTcp(array $branchConfigs, float $timeoutSec = 1.5): array
    {
        $sockets = [];
        $meta = [];
        $results = [];

        foreach ($branchConfigs as $nroSuc => $conf) {
            $dns = trim($conf['CONEXION_DNS'] ?? '');
            $base = trim($conf['BASE_NOMBRE'] ?? '');
            if ($dns === '' || $base === '') {
                $results[$nroSuc] = false;
                continue;
            }

            // Instancias nombradas (host\instancia) no usan puerto fijo TCP directo
            if (strpos($dns, '\\') !== false) {
                $results[$nroSuc] = true;
                continue;
            }

            $parts = explode(',', $dns);
            $host = trim($parts[0]);
            $port = isset($parts[1]) ? intval(trim($parts[1])) : 1433;
            if ($host === '' || $port <= 0) {
                $results[$nroSuc] = false;
                continue;
            }

            $errno = 0;
            $errstr = '';
            $sock = @stream_socket_client(
                "tcp://{$host}:{$port}",
                $errno,
                $errstr,
                $timeoutSec,
                STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT
            );

            if ($sock === false) {
                $results[$nroSuc] = false;
            } else {
                stream_set_blocking($sock, false);
                $sockets[(int)$sock] = $sock;
                $meta[(int)$sock] = $nroSuc;
            }
        }

        $deadline = microtime(true) + $timeoutSec;
        while (!empty($sockets)) {
            $remaining = $deadline - microtime(true);
            if ($remaining <= 0) {
                break;
            }

            $sec = (int)floor($remaining);
            $usec = (int)(($remaining - $sec) * 1000000);

            $read = null;
            $write = $sockets;
            $except = $sockets;

            $ready = @stream_select($read, $write, $except, $sec, $usec);
            if ($ready === false || $ready === 0) {
                break;
            }

            $readySockets = [];
            if (is_array($write)) {
                foreach ($write as $s) {
                    $readySockets[(int)$s] = $s;
                }
            }
            if (is_array($except)) {
                foreach ($except as $s) {
                    $readySockets[(int)$s] = $s;
                }
            }

            foreach ($readySockets as $id => $s) {
                $nroSuc = $meta[$id];
                $peer = @stream_socket_get_name($s, true);
                $results[$nroSuc] = ($peer !== false);
                @fclose($s);
                unset($sockets[$id]);
            }
        }

        foreach ($sockets as $id => $s) {
            $nroSuc = $meta[$id];
            $results[$nroSuc] = false;
            @fclose($s);
        }

        return $results;
    }

    /**
     * Conecta directamente a la base de una sucursal con LoginTimeout acotado, sin depender de $_SESSION.
     */
    private function connectToBranch(array $branchConfig, int $loginTimeout = 3)
    {
        $dns = trim($branchConfig['CONEXION_DNS'] ?? '');
        $base = trim($branchConfig['BASE_NOMBRE'] ?? '');
        if ($dns === '' || $base === '') {
            return false;
        }

        $user = !empty($branchConfig['USUARIO_DNS']) ? $branchConfig['USUARIO_DNS'] : $this->conexion->user;
        $pass = !empty($branchConfig['CLAVE_DNS']) ? $branchConfig['CLAVE_DNS'] : $this->conexion->pass;

        $params = [
            'Database' => $base,
            'UID' => $user,
            'PWD' => $pass,
            'CharacterSet' => $this->conexion->character,
            'LoginTimeout' => $loginTimeout,
            'ConnectRetryCount' => 0
        ];

        return @sqlsrv_connect($dns, $params);
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

        $nroSucursales = [];
        foreach ($sucursalesToFetch as $conf) {
            if (!empty($conf['nro_sucursal'])) {
                $nroSucursales[] = strval($conf['nro_sucursal']);
            }
        }

        $branchConfigs = $this->getBranchConfigsMap($nroSucursales);
        $sistemaSalesByBranch = $this->getSistemaSalesMap($nroSucursales, $desde, $hasta);
        $tcpReachable = $this->probeBranchesTcp($branchConfigs, 1.5);

        $allRows = [];
        $offlineBranches = [];
        $kpis = [
            'total_sistema' => 0,
            'total_control' => 0,
            'total_diferencia' => 0,
            'count_ok' => 0,
            'count_diff' => 0,
            'count_total' => 0
        ];

        $desdeDt = $desde . ' 00:00:00';
        $hastaDt = $hasta . ' 23:59:59';

        foreach ($sucursalesToFetch as $keySuc => $conf) {
            $nroSuc = isset($conf['nro_sucursal']) ? strval($conf['nro_sucursal']) : null;
            $nombreSuc = $conf['nombre'] ?? $keySuc;
            if (!$nroSuc) continue;

            // 1. Obtener $ SISTEMA de RO_T_VENTA_DIARIA_SUCURSALES
            $sistemaMap = $sistemaSalesByBranch[$nroSuc] ?? [];

            // 2. Obtener $ CONTROL de V_creditospromosbancarias en la BD local de la sucursal
            $controlMap = [];
            $promoDetailsMap = [];

            $branchCfg = $branchConfigs[$nroSuc] ?? null;
            $isEnabled = $branchCfg ? (intval($branchCfg['HABILITADO'] ?? 1) === 1) : true;
            $isReachable = $branchCfg && !empty($tcpReachable[$nroSuc]);
            $localConnected = false;

            if ($isReachable) {
                $connLocal = $this->connectToBranch($branchCfg, 3);
                if ($connLocal) {
                    $localConnected = true;
                    $sql = "SELECT CONVERT(VARCHAR(10), Fecha, 120) AS FechaOnly, 
                                   Promocion, 
                                   SUM(importe) AS TotalControl 
                            FROM V_creditospromosbancarias 
                            WHERE Fecha BETWEEN ? AND ? 
                            GROUP BY CONVERT(VARCHAR(10), Fecha, 120), Promocion";

                    $stmt = sqlsrv_query($connLocal, $sql, [$desdeDt, $hastaDt], ['QueryTimeout' => 10]);
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
                        sqlsrv_free_stmt($stmt);
                    } else {
                        error_log("Error en V_creditospromosbancarias sucursal {$nroSuc}: " . print_r(sqlsrv_errors(), true));
                    }
                    sqlsrv_close($connLocal);
                }
            }

            if (!$localConnected && ($isEnabled || $sucursalTarget !== 'todos')) {
                $offlineBranches[] = [
                    'nro_sucursal' => $nroSuc,
                    'nombre' => $nombreSuc
                ];
            }

            // Combine all dates from both $ SISTEMA and $ CONTROL
            $allDates = array_unique(array_merge(array_keys($sistemaMap), array_keys($controlMap)));
            sort($allDates);

            foreach ($allDates as $fecha) {
                $sisData = $sistemaMap[$fecha] ?? null;
                $montoSistema = $sisData ? $sisData['monto'] : 0;
                $verificado = $sisData ? $sisData['verificado'] : 0;
                $observaciones = $sisData ? $sisData['observaciones'] : '';
                $usuarioAudit = $sisData ? $sisData['usuario'] : '';
                $idRegistro = $sisData ? $sisData['id_registro'] : null;

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
                $isAutoOk = (Math_abs_diff($normSistema, $normControl) < 0.01 && $normControl > 0);

                // Determinación del estado con soporte manual
                if ($verificado === 1) {
                    $status = 'ok';
                } elseif ($verificado === 2) {
                    $status = 'revisado';
                } elseif ($isAutoOk) {
                    $status = 'ok';
                } elseif ($normControl == 0 && $normSistema == 0) {
                    $status = 'pendiente';
                } else {
                    $status = 'diferencia';
                }

                $allRows[] = [
                    'id' => "PB-{$nroSuc}-{$fecha}",
                    'id_registro' => $idRegistro,
                    'fecha' => $fecha,
                    'sucursal' => $nombreSuc,
                    'nro_sucursal' => $nroSuc,
                    'promocion' => $promoStr ?: 'PROMO BANCO',
                    'monto_sistema' => $montoSistema,
                    'monto_control' => $montoControl,
                    'norm_sistema' => $normSistema,
                    'norm_control' => $normControl,
                    'diferencia' => $diff,
                    'status' => $status,
                    'verificado' => $verificado,
                    'observaciones' => $observaciones,
                    'usuario' => $usuarioAudit
                ];

                $kpis['total_sistema'] += $normSistema;
                $kpis['total_control'] += $normControl;
                $kpis['total_diferencia'] += abs($diff);
                $kpis['count_total']++;

                if ($status === 'ok') {
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
            'kpis' => $kpis,
            'offline_branches' => $offlineBranches
        ];
    }

    /**
     * Obtiene el detalle individual de comprobantes (Facturas, Cupones, NCs locales y Central)
     */
    public function getPromoVouchersDetail(string $fecha, string $nroSucursal): array
    {
        $sucursalConfig = null;
        foreach ($this->sucursalesConfig as $conf) {
            if (strval($conf['nro_sucursal'] ?? '') === strval($nroSucursal)) {
                $sucursalConfig = $conf;
                break;
            }
        }

        $nombreSucursal = $sucursalConfig['nombre'] ?? "Sucursal {$nroSucursal}";

        $localInvoices = [];
        $localDevolutions = [];
        $localCreditNotes = [];
        $totalReintegroCalculado = 0;
        $totalReintegroActivo = 0;
        $totalDevuelto = 0;
        $localOnline = false;

        $desdeDt = $fecha . ' 00:00:00';
        $hastaDt = $fecha . ' 23:59:59';

        // 1. Consultar Base Local con probe TCP rápido y LoginTimeout acotado
        $branchConfigs = $this->getBranchConfigsMap([$nroSucursal]);
        $branchCfg = $branchConfigs[strval($nroSucursal)] ?? null;
        if ($branchCfg) {
            $tcpReachable = $this->probeBranchesTcp([$nroSucursal => $branchCfg], 1.5);
            $connLocal = !empty($tcpReachable[$nroSucursal]) ? $this->connectToBranch($branchCfg, 3) : false;
            if ($connLocal) {
                $localOnline = true;
                // A. Facturas con cupones
                $sqlInvoices = "
                    SELECT 
                        g.fecha_emis,
                        g.t_comp,
                        g.n_comp,
                        g.importe AS total_factura,
                        g.canc_comp,
                        g.hora_comp,
                        g.cod_client,
                        g.usuario_ingreso,
                        s.id_sba20,
                        s.n_cupon,
                        s.n_autoriza,
                        s.importe_to AS importe_cupon,
                        s.tipo_cupon,
                        p.desc_promocion_tarjeta,
                        p.porc_reintegro,
                        ROUND(s.importe_to * (p.porc_reintegro / 100.0), 2) AS reintegro_calculado
                    FROM sba20 s
                    JOIN promocion_tarjeta p ON s.id_promocion_tarjeta = p.id_promocion_tarjeta
                    JOIN gva12 g ON g.n_comp = s.n_comp_rec AND g.t_comp = s.t_comp_rec
                    WHERE g.fecha_emis >= ? AND g.fecha_emis <= ?
                    ORDER BY g.n_comp, s.n_cupon";

                $stmt = sqlsrv_query($connLocal, $sqlInvoices, [$desdeDt, $hastaDt], ['QueryTimeout' => 10]);
                if ($stmt !== false) {
                    while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                        $reintegro = floatval($r['reintegro_calculado']);
                        $isCanceled = (trim($r['canc_comp'] ?? '') !== '');

                        $totalReintegroCalculado += $reintegro;
                        if (!$isCanceled) {
                            $totalReintegroActivo += $reintegro;
                        }

                        $fEmis = $r['fecha_emis'] instanceof DateTime ? $r['fecha_emis']->format('Y-m-d') : substr(strval($r['fecha_emis']), 0, 10);

                        $localInvoices[] = [
                            'fecha' => $fEmis,
                            't_comp' => trim($r['t_comp']),
                            'n_comp' => trim($r['n_comp']),
                            'total_factura' => floatval($r['total_factura']),
                            'canc_comp' => trim($r['canc_comp'] ?? ''),
                            'is_canceled' => $isCanceled,
                            'hora' => trim($r['hora_comp'] ?? ''),
                            'cod_cliente' => trim($r['cod_client'] ?? ''),
                            'usuario' => trim($r['usuario_ingreso'] ?? ''),
                            'n_cupon' => trim($r['n_cupon'] ?? ''),
                            'n_autoriza' => trim($r['n_autoriza'] ?? ''),
                            'importe_cupon' => floatval($r['importe_cupon']),
                            'promocion' => trim($r['desc_promocion_tarjeta']),
                            'porc_reintegro' => floatval($r['porc_reintegro']),
                            'reintegro' => $reintegro
                        ];
                    }
                    sqlsrv_free_stmt($stmt);
                }

                // B. Cupones de devolucion (Tipo D)
                $sqlDev = "
                    SELECT 
                        s.n_comp_rec,
                        s.t_comp_rec,
                        s.n_cupon,
                        s.n_autoriza,
                        s.hora_rec,
                        s.importe_to,
                        p.desc_promocion_tarjeta,
                        p.porc_reintegro,
                        ROUND(s.importe_to * (p.porc_reintegro / 100.0), 2) AS reintegro_devuelto
                    FROM sba20 s
                    JOIN promocion_tarjeta p ON s.id_promocion_tarjeta = p.id_promocion_tarjeta
                    WHERE s.fecha_rec >= ? AND s.fecha_rec <= ?
                      AND s.tipo_cupon = 'D'";

                $stmtDev = sqlsrv_query($connLocal, $sqlDev, [$desdeDt, $hastaDt], ['QueryTimeout' => 10]);
                if ($stmtDev !== false) {
                    while ($r = sqlsrv_fetch_array($stmtDev, SQLSRV_FETCH_ASSOC)) {
                        $reintDev = floatval($r['reintegro_devuelto']);
                        $totalDevuelto += $reintDev;

                        $localDevolutions[] = [
                            'n_comp' => trim($r['n_comp_rec'] ?? ''),
                            't_comp' => trim($r['t_comp_rec'] ?? ''),
                            'n_cupon' => trim($r['n_cupon'] ?? ''),
                            'n_autoriza' => trim($r['n_autoriza'] ?? ''),
                            'hora' => trim($r['hora_rec'] ?? ''),
                            'importe_cupon' => floatval($r['importe_to']),
                            'promocion' => trim($r['desc_promocion_tarjeta']),
                            'reintegro_devuelto' => $reintDev
                        ];
                    }
                    sqlsrv_free_stmt($stmtDev);
                }

                // C. Notas de Credito emitidas en el local (NCR en gva12 / gva53)
                $sqlNc = "
                    SELECT 
                        g.t_comp,
                        g.n_comp,
                        g.importe AS importe_nc,
                        g.hora_comp,
                        g.usuario_ingreso,
                        g.cod_client,
                        d.n_rengl_v,
                        d.cod_articu,
                        d.imp_re_pan
                    FROM gva12 g
                    LEFT JOIN gva53 d ON g.n_comp = d.n_comp AND g.t_comp = d.t_comp
                    WHERE g.fecha_emis >= ? AND g.fecha_emis <= ?
                      AND g.t_comp LIKE 'NC%'
                    ORDER BY g.n_comp, d.n_rengl_v";

                $stmtNc = sqlsrv_query($connLocal, $sqlNc, [$desdeDt, $hastaDt], ['QueryTimeout' => 10]);
                if ($stmtNc !== false) {
                    while ($r = sqlsrv_fetch_array($stmtNc, SQLSRV_FETCH_ASSOC)) {
                        $localCreditNotes[] = [
                            't_comp' => trim($r['t_comp']),
                            'n_comp' => trim($r['n_comp']),
                            'importe_nc' => floatval($r['importe_nc']),
                            'hora' => trim($r['hora_comp'] ?? ''),
                            'usuario' => trim($r['usuario_ingreso'] ?? ''),
                            'cod_cliente' => trim($r['cod_client'] ?? ''),
                            'renglon' => intval($r['n_rengl_v'] ?? 1),
                            'articulo' => trim($r['cod_articu'] ?? ''),
                            'importe_renglon' => floatval($r['imp_re_pan'] ?? 0)
                        ];
                    }
                    sqlsrv_free_stmt($stmtNc);
                }

                sqlsrv_close($connLocal);
            }
        }

        // 2. Consultar Central
        $centralVouchers = [];
        $centralResumen = null;
        $totalCentralImputado = 0;

        $connCentral = $this->getLocalesConnection() ?: $this->conexion->conectar('central');
        if ($connCentral) {
            $prefix = $this->conexion->prefix;

            // A. Comprobantes en cuenta 250800
            $sqlCentral = "
                SELECT 
                    a.FECHA_EMIS,
                    a.T_COMP,
                    a.N_COMP,
                    a.IMPORTE,
                    a.COD_CLIENT,
                    b.COD_CTA,
                    b.MONTO
                FROM CTA02 a
                INNER JOIN CTA29 b ON a.NRO_SUCURS = b.NRO_SUCURS AND a.N_COMP = b.N_COMP AND a.T_COMP = b.COD_COMP
                WHERE a.NRO_SUCURS = ? 
                  AND a.FECHA_EMIS = ?
                  AND b.COD_CTA = '250800'
                ORDER BY a.N_COMP";

            $stmtC = sqlsrv_query($connCentral, $sqlCentral, [$nroSucursal, $fecha], ['QueryTimeout' => 10]);
            if ($stmtC !== false) {
                while ($r = sqlsrv_fetch_array($stmtC, SQLSRV_FETCH_ASSOC)) {
                    $montoImp = floatval($r['MONTO']);
                    $totalCentralImputado += $montoImp;

                    $centralVouchers[] = [
                        't_comp' => trim($r['T_COMP']),
                        'n_comp' => trim($r['N_COMP']),
                        'importe_total' => floatval($r['IMPORTE']),
                        'cuenta' => trim($r['COD_CTA']),
                        'monto_imputado' => $montoImp,
                        'cliente' => trim($r['COD_CLIENT'])
                    ];
                }
                sqlsrv_free_stmt($stmtC);
            }

            // B. Fila en RO_T_VENTA_DIARIA_SUCURSALES
            $sqlRo = "SELECT ID, FECHA, NRO_SUCURSAL, DESC_SUCURSAL, MEDIO_PAGO, IMPORTE_\$_SISTEMA, IMPORTE_\$_FISICO, VERIFICADO, OBSERVACIONES, USUARIO, FECHA_MODIF
                      FROM {$prefix}RO_T_VENTA_DIARIA_SUCURSALES
                      WHERE NRO_SUCURSAL = ? AND FECHA = ? AND MEDIO_PAGO = 'PROMO BANCO'";

            $stmtRo = sqlsrv_query($connCentral, $sqlRo, [$nroSucursal, $fecha], ['QueryTimeout' => 10]);
            if ($stmtRo !== false && ($rowRo = sqlsrv_fetch_array($stmtRo, SQLSRV_FETCH_ASSOC))) {
                $centralResumen = [
                    'id' => $rowRo['ID'],
                    'monto_sistema' => floatval($rowRo['IMPORTE_$_SISTEMA'] ?? 0),
                    'monto_fisico' => floatval($rowRo['IMPORTE_$_FISICO'] ?? 0),
                    'verificado' => intval($rowRo['VERIFICADO'] ?? 0),
                    'observaciones' => trim($rowRo['OBSERVACIONES'] ?? ''),
                    'usuario' => trim($rowRo['USUARIO'] ?? ''),
                    'fecha_modif' => $rowRo['FECHA_MODIF'] instanceof DateTime ? $rowRo['FECHA_MODIF']->format('Y-m-d') : $rowRo['FECHA_MODIF']
                ];
            }
            if ($stmtRo !== false) {
                sqlsrv_free_stmt($stmtRo);
            }
            sqlsrv_close($connCentral);
        }

        return [
            'fecha' => $fecha,
            'nro_sucursal' => $nroSucursal,
            'sucursal' => $nombreSucursal,
            'local_online' => $localOnline,
            'totales' => [
                'reintegro_activo_local' => round($totalReintegroActivo, 2),
                'reintegro_bruto_local' => round($totalReintegroCalculado, 2),
                'reintegro_devuelto_local' => round($totalDevuelto, 2),
                'total_central_imputado' => round($totalCentralImputado, 2)
            ],
            'local_invoices' => $localInvoices,
            'local_devolutions' => $localDevolutions,
            'local_credit_notes' => $localCreditNotes,
            'central_vouchers' => $centralVouchers,
            'central_resumen' => $centralResumen
        ];
    }

    /**
     * Actualiza el estado de verificación manual y observaciones en RO_T_VENTA_DIARIA_SUCURSALES
     */
    public function updatePromoStatus(string $fecha, string $nroSucursal, string $estado, ?string $observacion = ''): bool
    {
        $verificado = 0;
        if ($estado === 'ok') {
            $verificado = 1;
        } elseif ($estado === 'revisado') {
            $verificado = 2;
        } elseif ($estado === 'auto') {
            $verificado = 0;
        }

        $usuario = $this->usuario;

        $connCentral = $this->getLocalesConnection() ?: $this->conexion->conectar('central');
        if (!$connCentral) {
            throw new Exception("No se pudo conectar con la base de datos central.");
        }

        $prefix = $this->conexion->prefix;

        // Comprobar si ya existe el registro
        $sqlCheck = "SELECT ID FROM {$prefix}RO_T_VENTA_DIARIA_SUCURSALES WHERE NRO_SUCURSAL = ? AND FECHA = ? AND MEDIO_PAGO = 'PROMO BANCO'";
        $stmtCheck = sqlsrv_query($connCentral, $sqlCheck, [$nroSucursal, $fecha]);
        $exists = ($stmtCheck !== false && sqlsrv_has_rows($stmtCheck));
        if ($stmtCheck !== false) {
            sqlsrv_free_stmt($stmtCheck);
        }

        if ($exists) {
            $sqlUpdate = "UPDATE {$prefix}RO_T_VENTA_DIARIA_SUCURSALES 
                          SET VERIFICADO = ?, 
                              OBSERVACIONES = ?, 
                              USUARIO = ?, 
                              FECHA_MODIF = GETDATE() 
                          WHERE NRO_SUCURSAL = ? AND FECHA = ? AND MEDIO_PAGO = 'PROMO BANCO'";
            $params = [$verificado, $observacion, $usuario, $nroSucursal, $fecha];
            $stmt = sqlsrv_query($connCentral, $sqlUpdate, $params);
            if ($stmt === false) {
                $err = print_r(sqlsrv_errors(), true);
                sqlsrv_close($connCentral);
                throw new Exception("Error al actualizar estado en la base de datos: " . $err);
            }
            sqlsrv_free_stmt($stmt);
            sqlsrv_close($connCentral);
            return true;
        } else {
            // Si no existiera registro previo en RO_T_VENTA_DIARIA_SUCURSALES, insertarlo
            $sqlDesc = "SELECT DESC_SUCURSAL FROM LAKERBIS.LOCALES_LAKERS.DBO.SUCURSALES_LAKERS WHERE NRO_SUCURSAL = ?";
            $stmtDesc = sqlsrv_query($connCentral, $sqlDesc, [$nroSucursal]);
            $descSuc = "SUCURSAL {$nroSucursal}";
            if ($stmtDesc && ($rDesc = sqlsrv_fetch_array($stmtDesc, SQLSRV_FETCH_ASSOC))) {
                $descSuc = $rDesc['DESC_SUCURSAL'];
            }
            if ($stmtDesc !== false) {
                sqlsrv_free_stmt($stmtDesc);
            }

            $sqlInsert = "INSERT INTO {$prefix}RO_T_VENTA_DIARIA_SUCURSALES 
                          (FECHA, NRO_SUCURSAL, DESC_SUCURSAL, MEDIO_PAGO, IMPORTE_\$_SISTEMA, VERIFICADO, OBSERVACIONES, USUARIO, FECHA_MODIF) 
                          VALUES (?, ?, ?, 'PROMO BANCO', 0, ?, ?, ?, GETDATE())";
            $params = [$fecha, $nroSucursal, $descSuc, $verificado, $observacion, $usuario];
            $stmt = sqlsrv_query($connCentral, $sqlInsert, $params);
            if ($stmt === false) {
                $err = print_r(sqlsrv_errors(), true);
                sqlsrv_close($connCentral);
                throw new Exception("Error al insertar estado en la base de datos: " . $err);
            }
            sqlsrv_free_stmt($stmt);
            sqlsrv_close($connCentral);
            return true;
        }
    }
}

function Math_abs_diff($a, $b) {
    return abs($a - $b);
}

