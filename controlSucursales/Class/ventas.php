<?php

class Ventas
{
    private $cid;
    private $cid_conn;
    
    function __construct(){

        require_once __DIR__.'/../../class/conexion.php';
        $this->cid = new Conexion();

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy'){
            $this->cid_conn = $this->cid->conectar('suc_uy');
            
        }else{

            $this->cid_conn = $this->cid->conectar('locales');
            
        }

    } 

    
    public function ejercutarSP($desde, $hasta){   


        $sql = "EXEC ".$this->cid->prefix."RO_RESUMEN_VENTA_SUCURSALES '$desde', '$hasta'";

        try {

            $stmt = sqlsrv_prepare($this->cid_conn, $sql);
            $stmt = sqlsrv_execute($stmt);

        } catch (\Throwable $th) {

            print_r($th);

        }
    
    }

    public function traerVentas($desde, $hasta)
    {  

        $this->ejercutarSP($desde,$hasta);

        $sql2 = "SELECT * FROM ".$this->cid->prefix."RO_T_RESUMEN_VENTA_SUCURSALES";

        $stmt = sqlsrv_query($this->cid_conn, $sql2);

        try{
            
            $rows = array();
    
            while ($v = sqlsrv_fetch_array($stmt)) {
                // Calcular VENTAS si viene en 0 o null
                if (empty($v['VENTAS']) || $v['VENTAS'] == 0) {
                    $v['VENTAS'] = ($v['TARJETA'] ?? 0) + 
                                   ($v['CUENTA_DNI'] ?? 0) + 
                                   ($v['MERCADO_PAGO_QR'] ?? 0) + 
                                   ($v['MERCADO_PAGO'] ?? 0) + 
                                   ($v['MODO_QR'] ?? 0) + 
                                   ($v['PROMO_BANCO'] ?? 0) + 
                                   ($v['EFECTIVO'] ?? 0) + 
                                   ($v['BONUS_SHOPPING'] ?? 0) + 
                                   ($v['DOLARES'] ?? 0) + 
                                   ($v['EUROS'] ?? 0);
                }
                $rows[] = $v;
            }

            // Fallback seguro para La Rural (Suc. 83) cuando CTA29 no replicó en el rango consultado
            if (!isset($_SESSION['entorno']) || $_SESSION['entorno'] !== 'suc_uy') {
                $rows = $this->complementarLaRuralSinCta29($rows, $desde, $hasta);
            }
    
            $myJSON = json_encode($rows);
    
            return $myJSON;
        
        } catch (\Throwable $th){
            print_r($th);
        }

    }

    /**
     * Completa las ventas por medio de pago de La Rural (Suc. 83) directamente desde su BD local (SBA05)
     * únicamente cuando CTA29 en LOCALES_LAKERS no tiene movimientos FAC/NCR en el rango consultado.
     */
    private function complementarLaRuralSinCta29(array $rows, string $desde, string $hasta): array
    {
        $nroSucursal = 83;

        if (!$this->cid_conn) {
            return $rows;
        }

        // Si CTA29 ya tiene datos de La Rural en el período, no duplicar ni tocar nada
        $sqlCheck = "SELECT TOP 1 1 AS OK FROM {$this->cid->prefix}CTA29 WHERE NRO_SUCURS = ? AND COD_COMP IN ('FAC','NCR') AND FECHA BETWEEN ? AND ?";
        $stmtCheck = sqlsrv_query($this->cid_conn, $sqlCheck, [$nroSucursal, $desde, $hasta], ['QueryTimeout' => 5]);
        if ($stmtCheck === false) {
            return $rows;
        }
        $hasCta29 = (sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC) !== null);
        sqlsrv_free_stmt($stmtCheck);

        if ($hasCta29) {
            return $rows;
        }

        // Verificar que tenga comprobantes en CTA02 en el rango antes de conectar al local
        $sqlCta02 = "SELECT TOP 1 1 AS OK FROM {$this->cid->prefix}CTA02 WHERE NRO_SUCURS = ? AND FECHA_EMIS BETWEEN ? AND ? AND ESTADO <> 'ANU'";
        $stmtCta02 = sqlsrv_query($this->cid_conn, $sqlCta02, [$nroSucursal, $desde, $hasta], ['QueryTimeout' => 5]);
        if ($stmtCta02 === false) {
            return $rows;
        }
        $hasCta02 = (sqlsrv_fetch_array($stmtCta02, SQLSRV_FETCH_ASSOC) !== null);
        sqlsrv_free_stmt($stmtCta02);

        if (!$hasCta02) {
            return $rows;
        }

        // Obtener conexión desde SUCURSALES_LAKERS
        $connCentral = $this->cid->conectar('central');
        if (!$connCentral) {
            return $rows;
        }

        $sqlCfg = "SELECT CONEXION_DNS, BASE_NOMBRE, USUARIO_DNS, CLAVE_DNS, HABILITADO_CONT
                   FROM [XL-LAKERBIS].locales_lakers.dbo.SUCURSALES_LAKERS
                   WHERE NRO_SUC_MADRE IS NULL AND NRO_SUCURSAL = ?";
        $stmtCfg = sqlsrv_query($connCentral, $sqlCfg, [$nroSucursal], ['QueryTimeout' => 5]);
        $cfg = $stmtCfg ? sqlsrv_fetch_array($stmtCfg, SQLSRV_FETCH_ASSOC) : null;
        if ($stmtCfg) {
            sqlsrv_free_stmt($stmtCfg);
        }
        sqlsrv_close($connCentral);

        if (!$cfg || intval($cfg['HABILITADO_CONT'] ?? 0) !== 1) {
            return $rows;
        }

        $dns = trim($cfg['CONEXION_DNS'] ?? '');
        $base = trim($cfg['BASE_NOMBRE'] ?? '');
        if ($dns === '' || $base === '') {
            return $rows;
        }

        // Pre-chequeo rápido TCP para no demorar si la sucursal está offline
        if (strpos($dns, '\\') === false) {
            $parts = explode(',', $dns);
            $host = trim($parts[0]);
            $port = isset($parts[1]) ? intval(trim($parts[1])) : 1433;
            $errno = 0;
            $errstr = '';
            $sock = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 1.5);
            if ($sock === false) {
                return $rows;
            }
            @fclose($sock);
        }

        $user = !empty($cfg['USUARIO_DNS']) ? $cfg['USUARIO_DNS'] : $this->cid->user;
        $pass = !empty($cfg['CLAVE_DNS']) ? $cfg['CLAVE_DNS'] : $this->cid->pass;

        $connLocal = @sqlsrv_connect($dns, [
            'Database' => $base,
            'UID' => $user,
            'PWD' => $pass,
            'CharacterSet' => $this->cid->character,
            'LoginTimeout' => 3,
            'ConnectRetryCount' => 0
        ]);

        if (!$connLocal) {
            return $rows;
        }

        $sqlLocal = "
            SELECT
                CASE
                    WHEN B.COD_CTA = '100193' THEN 'MODO_QR'
                    WHEN B.COD_CTA = '100194' THEN 'CUENTA_DNI'
                    WHEN B.COD_CTA = '100198' THEN 'MERCADO_PAGO_QR'
                    WHEN B.COD_CTA IN ('100195', '100199') THEN 'MERCADO_PAGO'
                    WHEN B.COD_CTA = '100900' THEN 'DOLARES'
                    WHEN B.COD_CTA = '100910' THEN 'EUROS'
                    WHEN B.COD_CTA IN ('100800', '100850') THEN 'BONUS_SHOPPING'
                    WHEN C.TIPO = 'T' THEN 'TARJETA'
                    WHEN C.TIPO = 'O' THEN 'EFECTIVO'
                END AS MEDIO_PAGO,
                SUM(CASE WHEN A.T_COMP LIKE 'NC%' THEN -B.MONTO ELSE B.MONTO END) AS IMPORTE
            FROM GVA12 A
            INNER JOIN (
                SELECT COD_COMP, N_COMP, COD_CTA, SUM(MONTO) AS MONTO
                FROM SBA05
                WHERE ((D_H = 'D' AND COD_COMP = 'FAC' AND CONCILIADO != 1)
                    OR (D_H = 'H' AND COD_COMP = 'NCR'))
                GROUP BY COD_COMP, N_COMP, COD_CTA
            ) B ON A.T_COMP = B.COD_COMP AND A.N_COMP = B.N_COMP
            LEFT JOIN SBA01 C ON B.COD_CTA = C.COD_CTA
            WHERE A.ESTADO <> 'ANU'
              AND A.FECHA_EMIS BETWEEN ? AND ?
            GROUP BY CASE
                    WHEN B.COD_CTA = '100193' THEN 'MODO_QR'
                    WHEN B.COD_CTA = '100194' THEN 'CUENTA_DNI'
                    WHEN B.COD_CTA = '100198' THEN 'MERCADO_PAGO_QR'
                    WHEN B.COD_CTA IN ('100195', '100199') THEN 'MERCADO_PAGO'
                    WHEN B.COD_CTA = '100900' THEN 'DOLARES'
                    WHEN B.COD_CTA = '100910' THEN 'EUROS'
                    WHEN B.COD_CTA IN ('100800', '100850') THEN 'BONUS_SHOPPING'
                    WHEN C.TIPO = 'T' THEN 'TARJETA'
                    WHEN C.TIPO = 'O' THEN 'EFECTIVO'
                END
            HAVING CASE
                    WHEN B.COD_CTA = '100193' THEN 'MODO_QR'
                    WHEN B.COD_CTA = '100194' THEN 'CUENTA_DNI'
                    WHEN B.COD_CTA = '100198' THEN 'MERCADO_PAGO_QR'
                    WHEN B.COD_CTA IN ('100195', '100199') THEN 'MERCADO_PAGO'
                    WHEN B.COD_CTA = '100900' THEN 'DOLARES'
                    WHEN B.COD_CTA = '100910' THEN 'EUROS'
                    WHEN B.COD_CTA IN ('100800', '100850') THEN 'BONUS_SHOPPING'
                    WHEN C.TIPO = 'T' THEN 'TARJETA'
                    WHEN C.TIPO = 'O' THEN 'EFECTIVO'
                END IS NOT NULL
        ";

        $stmtLocal = sqlsrv_query($connLocal, $sqlLocal, [$desde, $hasta], ['QueryTimeout' => 10]);
        if ($stmtLocal === false) {
            sqlsrv_close($connLocal);
            return $rows;
        }

        $medios = [
            'TARJETA' => 0.0,
            'CUENTA_DNI' => 0.0,
            'MERCADO_PAGO_QR' => 0.0,
            'MERCADO_PAGO' => 0.0,
            'MODO_QR' => 0.0,
            'PROMO_BANCO' => 0.0,
            'EFECTIVO' => 0.0,
            'BONUS_SHOPPING' => 0.0,
            'DOLARES' => 0.0,
            'EUROS' => 0.0
        ];

        $hasData = false;
        while ($r = sqlsrv_fetch_array($stmtLocal, SQLSRV_FETCH_ASSOC)) {
            $col = $r['MEDIO_PAGO'];
            if ($col && array_key_exists($col, $medios)) {
                $medios[$col] += floatval($r['IMPORTE'] ?? 0);
                $hasData = true;
            }
        }
        sqlsrv_free_stmt($stmtLocal);
        sqlsrv_close($connLocal);

        if (!$hasData) {
            return $rows;
        }

        // Si ya existía fila parcial (ej. por NC de PROMO BANCO en CTA02+CTA03), combinarla
        $existingIdx = null;
        foreach ($rows as $idx => $row) {
            if (intval($row['NRO_SUCURSAL'] ?? 0) === $nroSucursal) {
                $existingIdx = $idx;
                break;
            }
        }

        if ($existingIdx !== null) {
            foreach ($medios as $col => $val) {
                $rows[$existingIdx][$col] = floatval($rows[$existingIdx][$col] ?? 0) + $val;
            }
            $r = &$rows[$existingIdx];
            $r['TOTAL_TARJETAS'] = floatval($r['TARJETA']) + floatval($r['CUENTA_DNI']);
            $r['TOTAL_CONTADO'] = floatval($r['EFECTIVO']) + floatval($r['BONUS_SHOPPING']) + floatval($r['DOLARES']) + floatval($r['EUROS']);
            $r['VENTAS'] = $r['TOTAL_TARJETAS'] + floatval($r['MERCADO_PAGO_QR']) + floatval($r['MERCADO_PAGO']) + floatval($r['MODO_QR']) + floatval($r['PROMO_BANCO']) + $r['TOTAL_CONTADO'];
            unset($r);
        } else {
            $totalTarjetas = $medios['TARJETA'] + $medios['CUENTA_DNI'];
            $totalContado = $medios['EFECTIVO'] + $medios['BONUS_SHOPPING'] + $medios['DOLARES'] + $medios['EUROS'];
            $totalVentas = $totalTarjetas + $medios['MERCADO_PAGO_QR'] + $medios['MERCADO_PAGO'] + $medios['MODO_QR'] + $medios['PROMO_BANCO'] + $totalContado;

            $rows[] = [
                'ID' => 0,
                'NRO_SUCURSAL' => $nroSucursal,
                'DESC_SUCURSAL' => 'LA RURAL',
                'VENTAS' => $totalVentas,
                'TARJETA' => $medios['TARJETA'],
                'EFECTIVO' => $medios['EFECTIVO'],
                'CUENTA_DNI' => $medios['CUENTA_DNI'],
                'MERCADO_PAGO_QR' => $medios['MERCADO_PAGO_QR'],
                'MERCADO_PAGO' => $medios['MERCADO_PAGO'],
                'BONUS_SHOPPING' => $medios['BONUS_SHOPPING'],
                'DOLARES' => $medios['DOLARES'],
                'EUROS' => $medios['EUROS'],
                'PROMO_BANCO' => $medios['PROMO_BANCO'],
                'TOTAL_TARJETAS' => $totalTarjetas,
                'TOTAL_CONTADO' => $totalContado,
                'MODO_QR' => $medios['MODO_QR']
            ];

            usort($rows, function ($a, $b) {
                return intval($a['NRO_SUCURSAL'] ?? 0) <=> intval($b['NRO_SUCURSAL'] ?? 0);
            });
        }

        return $rows;
    }

    public function ejercutarSP2($desde, $hasta){   
 

        $sql = "EXEC ".$this->cid->prefix."RO_SP_VENTAS_VS_COBRANZA_POR_COMPROBANTE '$desde', '$hasta'";
     
        try {

            $stmt = sqlsrv_prepare($this->cid_conn, $sql);
            $stmt = sqlsrv_execute($stmt);

        } catch (\Throwable $th) {

            print_r($th);

        }
    
    }

    public function traerComprobantes($desde, $hasta)
    {

        $this->ejercutarSP2($desde,$hasta);

        $sql2 = "SELECT * FROM ".$this->cid->prefix."RO_T_VENTAS_VS_COBRANZA_POR_COMPROBANTE";

        $stmt = sqlsrv_query($this->cid_conn, $sql2);

        try{
            
            $rows = array();
    
            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }
    
            $myJSON = json_encode($rows);
    
            return $myJSON;
        
        } catch (\Throwable $th){
            print_r($th);
        }

    }

    public function confirmarVentaVsCobranza($nroSucursal ,$nroComprobante)
    {

        $sql = "UPDATE ".$this->cid->prefix."CTA29 SET CONCILIADO = 1 WHERE NRO_SUCURS = '$nroSucursal' AND N_COMP = '$nroComprobante'";

        try{
            
            $stmt = sqlsrv_query($this->cid_conn, $sql);
    
            return true;
        
        } catch (\Throwable $th){
            print_r($th);
        }

    }

}
