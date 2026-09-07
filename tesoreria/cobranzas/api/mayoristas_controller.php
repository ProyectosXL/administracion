<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesion expirada o no iniciada.']);
    exit;
}

$conn_central = Database::getConnection('central');
$conn_apps = Database::getConnection('apps');

// Asegurar tablas necesarias en XL-APPS (FP_MAYORISTAS_CONTACTOS y FP_COBRANZAS_MAYORISTAS_HISTORIAL)
function verificarTablasMayoristas($conn_apps) {
    $sql_tabla_contactos = "
        IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='FP_MAYORISTAS_CONTACTOS' AND xtype='U')
        BEGIN
            CREATE TABLE FP_MAYORISTAS_CONTACTOS (
                COD_CLIENT VARCHAR(50) PRIMARY KEY,
                RAZON_SOCI VARCHAR(150),
                TELEFONO_WPP VARCHAR(50),
                CONTACTO_NOMBRE VARCHAR(100),
                OBSERVACIONES VARCHAR(255),
                FECHA_ACTUALIZACION DATETIME DEFAULT GETDATE()
            )
        END
    ";
    sqlsrv_query($conn_apps, $sql_tabla_contactos);

    $sql_tabla_historial = "
        IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='FP_COBRANZAS_MAYORISTAS_HISTORIAL' AND xtype='U')
        BEGIN
            CREATE TABLE FP_COBRANZAS_MAYORISTAS_HISTORIAL (
                ID INT IDENTITY(1,1) PRIMARY KEY,
                COD_CLIENT VARCHAR(50) NOT NULL,
                RAZON_SOCI VARCHAR(150),
                COD_VENDED VARCHAR(10),
                ID_USUARIO INT,
                NOMBRE_USUARIO VARCHAR(100),
                TIPO_MENSAJE INT,
                MONTO_FACTURA FLOAT DEFAULT 0,
                MONTO_REMITO FLOAT DEFAULT 0,
                TOTAL_PROPUESTO FLOAT DEFAULT 0,
                TELEFONO_DESTINO VARCHAR(50),
                MENSAJE_ENVIADO NVARCHAR(MAX),
                COMPROBANTES_JSON NVARCHAR(MAX),
                FECHA_ENVIO DATETIME DEFAULT GETDATE(),
                ESTADO VARCHAR(50) DEFAULT 'ENVIADO',
                NRO_RECIBO VARCHAR(50),
                FECHA_PAGO DATETIME,
                MONTO_PAGO_RECIBO FLOAT,
                DIFERENCIA_MONTO FLOAT,
                DIFERENCIA_PORC FLOAT,
                DIAS_A_PAGO INT,
                OBSERVACIONES_GESTION NVARCHAR(MAX)
            )
        END
        ELSE
        BEGIN
            IF NOT EXISTS (SELECT * FROM syscolumns WHERE id=OBJECT_ID('FP_COBRANZAS_MAYORISTAS_HISTORIAL') AND name='COMPROBANTES_JSON')
            BEGIN
                ALTER TABLE FP_COBRANZAS_MAYORISTAS_HISTORIAL ADD COMPROBANTES_JSON NVARCHAR(MAX);
            END
        END
    ";
    sqlsrv_query($conn_apps, $sql_tabla_historial);
}

verificarTablasMayoristas($conn_apps);

$action = $_GET['action'] ?? '';

try {
    switch ($action) {

        // =========================================================================
        // 1. LISTAR CLIENTES Y PENDIENTES (CAMINO 1 FACTURAS + CAMINO 2 REMITOS)
        // =========================================================================
        case 'listar_pendientes':
            $cod_vended_filtro = $_GET['cod_vended'] ?? 'TODOS';

            // 1.1 Obtener Facturas Pendientes (Camino 1) desde RO_V_COBRANZA_PEND_MAYORISTAS
            // Se filtra por vendedores Z3, Z4, Z5
            $sql_facturas = "
                SELECT 
                    v.COD_CLIENT,
                    v.RAZON_SOCI,
                    v.COD_VENDED,
                    v.T_COMP,
                    v.N_COMP,
                    v.FECHA_EMIS,
                    CAST(ISNULL(s.SALDO_REAL, v.IMPORTE) AS FLOAT) as SALDO_COMP,
                    CAST(ISNULL(s.SALDO_REAL * (v.IMPORTE_NETO / NULLIF(v.IMPORTE, 0)), v.IMPORTE_NETO) AS FLOAT) as SALDO_NETO,
                    v.FECHA_PROB_COBRO,
                    v.PPP
                FROM RO_V_COBRANZA_PEND_MAYORISTAS v
                LEFT JOIN (
                    SELECT T_COMP, N_COMP, SUM(IMPORTE_VT - IMPORT_CAN) as SALDO_REAL 
                    FROM SJ_SALDOS_CC_DETALLE 
                    GROUP BY T_COMP, N_COMP
                ) s ON v.T_COMP = s.T_COMP AND v.N_COMP = s.N_COMP
                WHERE v.COD_VENDED IN ('Z3', 'Z4', 'Z5')
            ";

            if ($cod_vended_filtro !== 'TODOS' && in_array($cod_vended_filtro, ['Z3', 'Z4', 'Z5'])) {
                $sql_facturas .= " AND v.COD_VENDED = '" . $cod_vended_filtro . "'";
            }
            $sql_facturas .= " ORDER BY v.COD_CLIENT, v.FECHA_EMIS DESC";

            $stmt_f = sqlsrv_query($conn_central, $sql_facturas);
            $clientes = [];

            if ($stmt_f !== false) {
                while ($row = sqlsrv_fetch_array($stmt_f, SQLSRV_FETCH_ASSOC)) {
                    $cod = trim($row['COD_CLIENT']);
                    if (!isset($clientes[$cod])) {
                        $clientes[$cod] = [
                            'COD_CLIENT' => $cod,
                            'RAZON_SOCI' => trim($row['RAZON_SOCI']),
                            'COD_VENDED' => trim($row['COD_VENDED']),
                            'TOTAL_FACTURA' => 0,
                            'CANT_FACTURAS' => 0,
                            'TOTAL_REMITO' => 0,
                            'CANT_REMITOS' => 0,
                            'FACTURAS' => [],
                            'REMITOS' => [],
                            'TELEFONO_WPP' => '',
                            'CONTACTO_NOMBRE' => ''
                        ];
                    }

                    $tComp = strtoupper(trim($row['T_COMP'] ?? ''));
                    $saldo = (float)($row['SALDO_COMP'] ?? 0);
                    $esNegativo = (strpos($tComp, 'NC') !== false || ($tComp === 'REC' && $saldo < 0));
                    $montoReal = $esNegativo ? -abs($saldo) : abs($saldo);

                    $clientes[$cod]['TOTAL_FACTURA'] += $montoReal;
                    $clientes[$cod]['CANT_FACTURAS']++;
                    $clientes[$cod]['FACTURAS'][] = [
                        'T_COMP' => $tComp,
                        'N_COMP' => trim($row['N_COMP'] ?? ''),
                        'FECHA_EMIS' => $row['FECHA_EMIS'] instanceof DateTime ? $row['FECHA_EMIS']->format('Y-m-d') : $row['FECHA_EMIS'],
                        'IMPORTE' => $montoReal,
                        'FECHA_PROB_COBRO' => $row['FECHA_PROB_COBRO'] instanceof DateTime ? $row['FECHA_PROB_COBRO']->format('Y-m-d') : $row['FECHA_PROB_COBRO']
                    ];
                }
            }

            // 1.2 Obtener Remitos Pendientes (Camino 2) desde SJ_EQUIS_TABLE (App Finanzas 599)
            // Ejecutamos la actualizacion de remitos equis
            @sqlsrv_query($conn_central, "EXEC SJ_EQUIS_SP");

            $sql_remitos = "
                SELECT 
                    ISNULL(E.GRUPO_EMPR, A.COD_PRO_CL) as COD_CLIENT,
                    ISNULL(E.NOMBRE_GRU, A.RAZON_SOCI) as RAZON_SOCI,
                    ISNULL(G.COD_VENDED, 'Z3') as COD_VENDED,
                    A.N_COMP,
                    A.FECHA_MOV as FECHA_EMIS,
                    CAST(A.IMPORTE_TO AS FLOAT) as IMPORTE
                FROM SJ_EQUIS_TABLE A
                LEFT JOIN sj_administracion_cobros_por_remito B ON B.num_rem = A.N_COMP COLLATE Latin1_General_BIN
                LEFT JOIN sj_administracion_cobros C ON C.id = B.id_cobro
                LEFT JOIN STA14 D ON A.N_COMP = D.N_COMP
                LEFT JOIN (SELECT A.COD_CLIENT, A.GRUPO_EMPR, B.NOMBRE_GRU FROM GVA14 A INNER JOIN GVA62 B ON A.GRUPO_EMPR = B.GRUPO_EMPR) E ON A.COD_PRO_CL = E.COD_CLIENT
                LEFT JOIN GVA14 G ON A.COD_PRO_CL = G.COD_CLIENT
                WHERE (A.FECHA_MOV >= GETDATE()-700) 
                  AND (C.rendido != 1 OR C.rendido IS NULL) 
                  AND D.ESTADO_MOV != 'A'
                  AND A.CHEQUEADO = 0
            ";
            
            try {
                $stmt_r = sqlsrv_query($conn_central, $sql_remitos);
                if ($stmt_r !== false) {
                    while ($rRow = sqlsrv_fetch_array($stmt_r, SQLSRV_FETCH_ASSOC)) {
                        $cod = trim($rRow['COD_CLIENT']);
                        $codVendedRem = trim($rRow['COD_VENDED'] ?? '');
                        
                        // Si se filtra por vendedor y no coincide (y el cliente no estaba ya en la lista)
                        if ($cod_vended_filtro !== 'TODOS' && in_array($cod_vended_filtro, ['Z3', 'Z4', 'Z5'])) {
                            if (isset($clientes[$cod])) {
                                if ($clientes[$cod]['COD_VENDED'] !== $cod_vended_filtro) continue;
                            } else {
                                if ($codVendedRem !== $cod_vended_filtro) continue;
                            }
                        }

                        if (!isset($clientes[$cod])) {
                            $clientes[$cod] = [
                                'COD_CLIENT' => $cod,
                                'RAZON_SOCI' => trim($rRow['RAZON_SOCI']),
                                'COD_VENDED' => $codVendedRem,
                                'TOTAL_FACTURA' => 0,
                                'CANT_FACTURAS' => 0,
                                'TOTAL_REMITO' => 0,
                                'CANT_REMITOS' => 0,
                                'FACTURAS' => [],
                                'REMITOS' => [],
                                'TELEFONO_WPP' => '',
                                'CONTACTO_NOMBRE' => ''
                            ];
                        }
                        $clientes[$cod]['TOTAL_REMITO'] += (float)$rRow['IMPORTE'];
                        $clientes[$cod]['CANT_REMITOS']++;
                        $fechaRemito = $rRow['FECHA_EMIS'];
                        if ($fechaRemito instanceof DateTime) {
                            $fechaRemitoStr = $fechaRemito->format('Y-m-d');
                        } elseif (is_string($fechaRemito) && !empty($fechaRemito)) {
                            $timestamp = strtotime($fechaRemito);
                            $fechaRemitoStr = $timestamp ? date('Y-m-d', $timestamp) : substr(trim($fechaRemito), 0, 10);
                        } else {
                            $fechaRemitoStr = '';
                        }

                        $clientes[$cod]['REMITOS'][] = [
                            'N_COMP' => trim($rRow['N_COMP']),
                            'FECHA_EMIS' => $fechaRemitoStr,
                            'IMPORTE' => (float)$rRow['IMPORTE']
                        ];
                    }
                }
            } catch (Exception $e_rem) {
            }

            // 1.3 Cargar telefonos de WhatsApp guardados en FP_MAYORISTAS_CONTACTOS
            $sql_contactos = "SELECT COD_CLIENT, TELEFONO_WPP, CONTACTO_NOMBRE FROM FP_MAYORISTAS_CONTACTOS";
            $stmt_cont = sqlsrv_query($conn_apps, $sql_contactos);
            if ($stmt_cont !== false) {
                while ($cRow = sqlsrv_fetch_array($stmt_cont, SQLSRV_FETCH_ASSOC)) {
                    $cod = trim($cRow['COD_CLIENT']);
                    if (isset($clientes[$cod])) {
                        $clientes[$cod]['TELEFONO_WPP'] = trim($cRow['TELEFONO_WPP'] ?? '');
                        $clientes[$cod]['CONTACTO_NOMBRE'] = trim($cRow['CONTACTO_NOMBRE'] ?? '');
                    }
                }
            }

            // 1.4 Cargar último estado de cobranza enviada desde FP_COBRANZAS_MAYORISTAS_HISTORIAL
            $sql_ult_envios = "
                SELECT 
                    h.COD_CLIENT,
                    h.ESTADO,
                    h.FECHA_ENVIO,
                    h.TOTAL_PROPUESTO,
                    h.NRO_RECIBO
                FROM FP_COBRANZAS_MAYORISTAS_HISTORIAL h
                INNER JOIN (
                    SELECT COD_CLIENT, MAX(ID) as MAX_ID
                    FROM FP_COBRANZAS_MAYORISTAS_HISTORIAL
                    GROUP BY COD_CLIENT
                ) m ON h.ID = m.MAX_ID
            ";
            $stmt_ult = sqlsrv_query($conn_apps, $sql_ult_envios);
            if ($stmt_ult !== false) {
                while ($uRow = sqlsrv_fetch_array($stmt_ult, SQLSRV_FETCH_ASSOC)) {
                    $cod = trim($uRow['COD_CLIENT']);
                    if (isset($clientes[$cod])) {
                        $clientes[$cod]['ULTIMO_ENVIO'] = [
                            'ESTADO' => trim($uRow['ESTADO'] ?? ''),
                            'FECHA_ENVIO' => $uRow['FECHA_ENVIO'] instanceof DateTime ? $uRow['FECHA_ENVIO']->format('d/m/Y H:i') : $uRow['FECHA_ENVIO'],
                            'TOTAL_PROPUESTO' => (float)($uRow['TOTAL_PROPUESTO'] ?? 0),
                            'NRO_RECIBO' => trim($uRow['NRO_RECIBO'] ?? '')
                        ];
                    }
                }
            }

            // 1.5 Filtrar clientes: Si ya tienen una cobranza enviada pendiente de pago, no mostrarlos como pendientes
            $lista = [];
            foreach ($clientes as $c) {
                // Si el cliente tiene un envío en estado 'ENVIADO' (aún no abonado), se oculta de la bandeja de pendientes
                if (isset($c['ULTIMO_ENVIO']) && $c['ULTIMO_ENVIO'] !== null && $c['ULTIMO_ENVIO']['ESTADO'] === 'ENVIADO') {
                    continue;
                }
                $c['TOTAL_GENERAL'] = $c['TOTAL_FACTURA'] + $c['TOTAL_REMITO'];
                $lista[] = $c;
            }

            usort($lista, function($a, $b) {
                return $b['TOTAL_GENERAL'] <=> $a['TOTAL_GENERAL'];
            });

            echo json_encode([
                'success' => true,
                'data' => $lista,
                'fecha_actual_str' => date('d/m/Y H:i')
            ]);
            break;

        // =========================================================================
        // 2. GUARDAR / ACTUALIZAR CONTACTO WHATSAPP DE CLIENTE
        // =========================================================================
        case 'guardar_contacto':
            $cod_client = trim($_POST['cod_client'] ?? '');
            $razon_soci = trim($_POST['razon_soci'] ?? '');
            $telefono = trim($_POST['telefono_wpp'] ?? '');
            $contacto = trim($_POST['contacto_nombre'] ?? '');
            $observaciones = trim($_POST['observaciones'] ?? '');

            if (empty($cod_client)) {
                throw new Exception("El codigo de cliente es obligatorio.");
            }

            $telefono_limpio = preg_replace('/[^0-9]/', '', $telefono);

            $sql_upsert = "
                MERGE FP_MAYORISTAS_CONTACTOS AS Target
                USING (SELECT ? AS COD_CLIENT, ? AS RAZON_SOCI, ? AS TELEFONO_WPP, ? AS CONTACTO_NOMBRE, ? AS OBSERVACIONES) AS Source
                ON (Target.COD_CLIENT = Source.COD_CLIENT)
                WHEN MATCHED THEN
                    UPDATE SET 
                        Target.RAZON_SOCI = Source.RAZON_SOCI,
                        Target.TELEFONO_WPP = Source.TELEFONO_WPP,
                        Target.CONTACTO_NOMBRE = Source.CONTACTO_NOMBRE,
                        Target.OBSERVACIONES = Source.OBSERVACIONES,
                        Target.FECHA_ACTUALIZACION = GETDATE()
                WHEN NOT MATCHED THEN
                    INSERT (COD_CLIENT, RAZON_SOCI, TELEFONO_WPP, CONTACTO_NOMBRE, OBSERVACIONES, FECHA_ACTUALIZACION)
                    VALUES (Source.COD_CLIENT, Source.RAZON_SOCI, Source.TELEFONO_WPP, Source.CONTACTO_NOMBRE, Source.OBSERVACIONES, GETDATE());
            ";

            $stmt_up = sqlsrv_query($conn_apps, $sql_upsert, [$cod_client, $razon_soci, $telefono_limpio, $contacto, $observaciones]);
            if ($stmt_up === false) {
                throw new Exception("Error al guardar contacto: " . print_r(sqlsrv_errors(), true));
            }

            echo json_encode([
                'success' => true,
                'message' => 'Contacto de WhatsApp actualizado correctamente.',
                'telefono_formateado' => $telefono_limpio
            ]);
            break;

        // =========================================================================
        // 3. REGISTRAR ENVIO DE COBRANZA EN HISTORIAL
        // =========================================================================
        case 'registrar_envio':
            $cod_client = trim($_POST['cod_client'] ?? '');
            $razon_soci = trim($_POST['razon_soci'] ?? '');
            $cod_vended = trim($_POST['cod_vended'] ?? '');
            $tipo_mensaje = (int)($_POST['tipo_mensaje'] ?? 1);
            $monto_factura = (float)($_POST['monto_factura'] ?? 0);
            $monto_remito = (float)($_POST['monto_remito'] ?? 0);
            $total_propuesto = $monto_factura + $monto_remito;
            $telefono = trim($_POST['telefono_destino'] ?? '');
            $mensaje = trim($_POST['mensaje_enviado'] ?? '');
            $comprobantes_json = trim($_POST['comprobantes_json'] ?? '');
            $id_usuario = $_SESSION['usuario_id'] ?? null;
            $nombre_usuario = $_SESSION['usuario_nombre'] ?? 'valeria';

            if (empty($cod_client)) {
                throw new Exception("Datos incompletos para registrar la cobranza.");
            }

            $sql_insert = "
                INSERT INTO FP_COBRANZAS_MAYORISTAS_HISTORIAL 
                (COD_CLIENT, RAZON_SOCI, COD_VENDED, ID_USUARIO, NOMBRE_USUARIO, TIPO_MENSAJE, MONTO_FACTURA, MONTO_REMITO, TOTAL_PROPUESTO, TELEFONO_DESTINO, MENSAJE_ENVIADO, COMPROBANTES_JSON, FECHA_ENVIO, ESTADO)
                OUTPUT INSERTED.ID
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE(), 'ENVIADO')
            ";

            $params = [
                $cod_client, $razon_soci, $cod_vended, $id_usuario, $nombre_usuario,
                $tipo_mensaje, $monto_factura, $monto_remito, $total_propuesto,
                $telefono, $mensaje, $comprobantes_json
            ];

            $stmt_ins = sqlsrv_query($conn_apps, $sql_insert, $params);
            if ($stmt_ins === false) {
                throw new Exception("Error al guardar en el historial: " . print_r(sqlsrv_errors(), true));
            }

            $row_ins = sqlsrv_fetch_array($stmt_ins, SQLSRV_FETCH_ASSOC);
            $nuevo_id = $row_ins['ID'] ?? 0;

            echo json_encode([
                'success' => true,
                'message' => 'Cobranza registrada en el historial exitosamente.',
                'id_cobranza' => $nuevo_id
            ]);
            break;

        // =========================================================================
        // 4. LISTAR HISTORIAL DE COBRANZAS
        // =========================================================================
        case 'listar_historial':
            $cod_vended = $_GET['cod_vended'] ?? 'TODOS';
            $estado = $_GET['estado'] ?? 'TODOS';

            $sql_hist = "
                SELECT 
                    h.ID,
                    h.COD_CLIENT,
                    h.RAZON_SOCI,
                    h.COD_VENDED,
                    h.NOMBRE_USUARIO,
                    h.TIPO_MENSAJE,
                    h.MONTO_FACTURA,
                    h.MONTO_REMITO,
                    h.TOTAL_PROPUESTO,
                    h.TELEFONO_DESTINO,
                    h.MENSAJE_ENVIADO,
                    h.COMPROBANTES_JSON,
                    h.FECHA_ENVIO,
                    h.ESTADO,
                    h.NRO_RECIBO,
                    h.FECHA_PAGO,
                    h.MONTO_PAGO_RECIBO,
                    h.DIFERENCIA_MONTO,
                    h.DIFERENCIA_PORC,
                    h.DIAS_A_PAGO,
                    h.OBSERVACIONES_GESTION
                FROM FP_COBRANZAS_MAYORISTAS_HISTORIAL h
                WHERE 1=1
            ";

            if ($cod_vended !== 'TODOS' && in_array($cod_vended, ['Z3', 'Z4', 'Z5'])) {
                $sql_hist .= " AND h.COD_VENDED = '" . $cod_vended . "'";
            }
            if ($estado !== 'TODOS' && !empty($estado)) {
                $sql_hist .= " AND h.ESTADO = '" . $estado . "'";
            }

            $sql_hist .= " ORDER BY h.FECHA_ENVIO DESC";

            $stmt_h = sqlsrv_query($conn_apps, $sql_hist);
            if ($stmt_h === false) {
                throw new Exception("Error al consultar historial: " . print_r(sqlsrv_errors(), true));
            }

            $historial = [];
            while ($row = sqlsrv_fetch_array($stmt_h, SQLSRV_FETCH_ASSOC)) {
                $historial[] = [
                    'ID' => $row['ID'],
                    'COD_CLIENT' => trim($row['COD_CLIENT']),
                    'RAZON_SOCI' => trim($row['RAZON_SOCI'] ?? ''),
                    'COD_VENDED' => trim($row['COD_VENDED'] ?? ''),
                    'NOMBRE_USUARIO' => trim($row['NOMBRE_USUARIO'] ?? ''),
                    'TIPO_MENSAJE' => (int)$row['TIPO_MENSAJE'],
                    'MONTO_FACTURA' => (float)$row['MONTO_FACTURA'],
                    'MONTO_REMITO' => (float)$row['MONTO_REMITO'],
                    'TOTAL_PROPUESTO' => (float)$row['TOTAL_PROPUESTO'],
                    'TELEFONO_DESTINO' => trim($row['TELEFONO_DESTINO'] ?? ''),
                    'MENSAJE_ENVIADO' => $row['MENSAJE_ENVIADO'],
                    'COMPROBANTES_JSON' => $row['COMPROBANTES_JSON'],
                    'FECHA_ENVIO' => $row['FECHA_ENVIO'] instanceof DateTime ? $row['FECHA_ENVIO']->format('Y-m-d H:i') : $row['FECHA_ENVIO'],
                    'ESTADO' => trim($row['ESTADO'] ?? 'ENVIADO'),
                    'NRO_RECIBO' => trim($row['NRO_RECIBO'] ?? ''),
                    'FECHA_PAGO' => $row['FECHA_PAGO'] instanceof DateTime ? $row['FECHA_PAGO']->format('Y-m-d') : $row['FECHA_PAGO'],
                    'MONTO_PAGO_RECIBO' => $row['MONTO_PAGO_RECIBO'] !== null ? (float)$row['MONTO_PAGO_RECIBO'] : null,
                    'DIFERENCIA_MONTO' => $row['DIFERENCIA_MONTO'] !== null ? (float)$row['DIFERENCIA_MONTO'] : null,
                    'DIFERENCIA_PORC' => $row['DIFERENCIA_PORC'] !== null ? (float)$row['DIFERENCIA_PORC'] : null,
                    'DIAS_A_PAGO' => $row['DIAS_A_PAGO'] !== null ? (int)$row['DIAS_A_PAGO'] : null,
                    'OBSERVACIONES_GESTION' => trim($row['OBSERVACIONES_GESTION'] ?? '')
                ];
            }

            echo json_encode(['success' => true, 'data' => $historial]);
            break;

        // =========================================================================
        // 5. VINCULAR RECIBO Y CONCILIAR PAGO
        // =========================================================================
        case 'conciliar_recibo':
            $id_cobranza = (int)($_POST['id_cobranza'] ?? 0);
            $nro_recibo = trim($_POST['nro_recibo'] ?? '');
            $fecha_pago = trim($_POST['fecha_pago'] ?? '');
            $monto_pago = (float)($_POST['monto_pago'] ?? 0);
            $observaciones = trim($_POST['observaciones'] ?? '');

            if ($id_cobranza <= 0 || empty($nro_recibo)) {
                throw new Exception("ID de cobranza y Numero de Recibo son requeridos.");
            }

            $sql_cob = "SELECT TOTAL_PROPUESTO, FECHA_ENVIO FROM FP_COBRANZAS_MAYORISTAS_HISTORIAL WHERE ID = ?";
            $stmt_cob = sqlsrv_query($conn_apps, $sql_cob, [$id_cobranza]);
            $cob = sqlsrv_fetch_array($stmt_cob, SQLSRV_FETCH_ASSOC);

            if (!$cob) {
                throw new Exception("Cobranza no encontrada en historial.");
            }

            $total_propuesto = (float)$cob['TOTAL_PROPUESTO'];
            $fecha_envio = $cob['FECHA_ENVIO'] instanceof DateTime ? $cob['FECHA_ENVIO'] : new DateTime($cob['FECHA_ENVIO']);
            $f_pago = !empty($fecha_pago) ? new DateTime($fecha_pago) : new DateTime();

            $diff = $fecha_envio->diff($f_pago);
            $dias_a_pago = (int)$diff->format("%r%a");
            if ($dias_a_pago < 0) $dias_a_pago = 0;

            $dif_monto = $monto_pago - $total_propuesto;
            $dif_porc = $total_propuesto > 0 ? (($monto_pago - $total_propuesto) / $total_propuesto) * 100 : 0;

            $sql_up_hist = "
                UPDATE FP_COBRANZAS_MAYORISTAS_HISTORIAL 
                SET 
                    NRO_RECIBO = ?,
                    FECHA_PAGO = ?,
                    MONTO_PAGO_RECIBO = ?,
                    DIFERENCIA_MONTO = ?,
                    DIFERENCIA_PORC = ?,
                    DIAS_A_PAGO = ?,
                    ESTADO = 'ABONADO',
                    OBSERVACIONES_GESTION = ?
                WHERE ID = ?
            ";

            $params_up = [
                $nro_recibo,
                $f_pago->format('Y-m-d'),
                $monto_pago,
                $dif_monto,
                $dif_porc,
                $dias_a_pago,
                $observaciones,
                $id_cobranza
            ];

            $stmt_up_hist = sqlsrv_query($conn_apps, $sql_up_hist, $params_up);
            if ($stmt_up_hist === false) {
                throw new Exception("Error al actualizar gestion de recibo: " . print_r(sqlsrv_errors(), true));
            }

            echo json_encode([
                'success' => true,
                'message' => 'Cobranza conciliada con recibo exitosamente.',
                'dias_a_pago' => $dias_a_pago,
                'dif_monto' => $dif_monto,
                'dif_porc' => round($dif_porc, 2)
            ]);
            break;

        // =========================================================================
        // 5.1 ELIMINAR PROPUESTA / COBRANZA DEL HISTORIAL
        // =========================================================================
        case 'eliminar_cobranza':
            $id_cobranza = (int)($_POST['id_cobranza'] ?? 0);

            if ($id_cobranza <= 0) {
                throw new Exception("ID de cobranza no válido.");
            }

            $sql_del = "DELETE FROM FP_COBRANZAS_MAYORISTAS_HISTORIAL WHERE ID = ?";
            $stmt_del = sqlsrv_query($conn_apps, $sql_del, [$id_cobranza]);
            if ($stmt_del === false) {
                throw new Exception("Error al eliminar la cobranza: " . print_r(sqlsrv_errors(), true));
            }

            echo json_encode([
                'success' => true,
                'message' => 'Cobranza eliminada correctamente del historial.'
            ]);
            break;

        // =========================================================================
        // 6. BUSCAR RECIBOS EN BASE DE DATOS LAKER_SA (TANGO)
        // =========================================================================
        case 'buscar_recibos':
            $cod_client = trim($_GET['cod_client'] ?? '');
            
            $sql_rec = "
                SELECT TOP 30
                    v.T_COMP,
                    v.N_COMP,
                    v.FECHA_EMIS,
                    CAST(v.IMPORTE AS FLOAT) as IMPORTE,
                    v.COD_CLIENT,
                    v.RAZON_SOCI
                FROM RO_V_COBRANZA_PEND_MAYORISTAS v
                WHERE v.T_COMP = 'REC'
            ";

            if (!empty($cod_client)) {
                $sql_rec .= " AND v.COD_CLIENT = '" . $cod_client . "'";
            }

            $sql_rec .= " ORDER BY v.FECHA_EMIS DESC";

            $stmt_rec = sqlsrv_query($conn_central, $sql_rec);
            $recibos = [];

            if ($stmt_rec !== false) {
                while ($row = sqlsrv_fetch_array($stmt_rec, SQLSRV_FETCH_ASSOC)) {
                    $recibos[] = [
                        'T_COMP' => trim($row['T_COMP']),
                        'N_COMP' => trim($row['N_COMP']),
                        'FECHA_EMIS' => $row['FECHA_EMIS'] instanceof DateTime ? $row['FECHA_EMIS']->format('Y-m-d') : $row['FECHA_EMIS'],
                        'IMPORTE' => (float)$row['IMPORTE'],
                        'COD_CLIENT' => trim($row['COD_CLIENT']),
                        'RAZON_SOCI' => trim($row['RAZON_SOCI'])
                    ];
                }
            }

            echo json_encode(['success' => true, 'data' => $recibos]);
            break;

        // =========================================================================
        // 7. METRICAS Y REPORTES DE COBRANZAS MAYORISTAS
        // =========================================================================
        case 'obtener_reportes':
            $sql_kpis = "
                SELECT 
                    COUNT(*) as TOTAL_ENVIADOS,
                    SUM(CASE WHEN ESTADO = 'ABONADO' THEN 1 ELSE 0 END) as TOTAL_ABONADOS,
                    SUM(TOTAL_PROPUESTO) as MONTO_TOTAL_PROPUESTO,
                    SUM(CASE WHEN ESTADO = 'ABONADO' THEN MONTO_PAGO_RECIBO ELSE 0 END) as MONTO_TOTAL_COBRADO,
                    AVG(CASE WHEN ESTADO = 'ABONADO' THEN CAST(DIAS_A_PAGO AS FLOAT) ELSE NULL END) as PROMEDIO_DIAS_PAGO,
                    AVG(CASE WHEN ESTADO = 'ABONADO' THEN CAST(DIFERENCIA_PORC AS FLOAT) ELSE NULL END) as PROMEDIO_DESVIO_PORC
                FROM FP_COBRANZAS_MAYORISTAS_HISTORIAL
            ";

            $stmt_k = sqlsrv_query($conn_apps, $sql_kpis);
            $kpis = sqlsrv_fetch_array($stmt_k, SQLSRV_FETCH_ASSOC);

            $sql_vended = "
                SELECT 
                    COD_VENDED,
                    COUNT(*) as CANT_ENVIOS,
                    SUM(CASE WHEN ESTADO = 'ABONADO' THEN 1 ELSE 0 END) as CANT_ABONADOS,
                    SUM(TOTAL_PROPUESTO) as MONTO_PROPUESTO,
                    SUM(CASE WHEN ESTADO = 'ABONADO' THEN MONTO_PAGO_RECIBO ELSE 0 END) as MONTO_COBRADO,
                    AVG(CASE WHEN ESTADO = 'ABONADO' THEN CAST(DIAS_A_PAGO AS FLOAT) ELSE NULL END) as PROMEDIO_DIAS
                FROM FP_COBRANZAS_MAYORISTAS_HISTORIAL
                GROUP BY COD_VENDED
            ";

            $stmt_v = sqlsrv_query($conn_apps, $sql_vended);
            $reporte_vendedores = [];
            if ($stmt_v !== false) {
                while ($rV = sqlsrv_fetch_array($stmt_v, SQLSRV_FETCH_ASSOC)) {
                    $codV = trim($rV['COD_VENDED'] ?? 'SIN ASIGNAR');
                    $nombreV = $codV === 'Z3' ? 'VALERIA VILLARREAL' : ($codV === 'Z4' ? 'SERGIO LOPEZ COBOS' : ($codV === 'Z5' ? 'CRISTIAN NACKE' : $codV));
                    
                    // Obtener clientes de este vendedor en el historial
                    $sql_cli_v = "
                        SELECT 
                            ID,
                            COD_CLIENT,
                            RAZON_SOCI,
                            FECHA_ENVIO,
                            TOTAL_PROPUESTO,
                            MONTO_FACTURA,
                            MONTO_REMITO,
                            ESTADO,
                            NRO_RECIBO,
                            FECHA_PAGO,
                            MONTO_PAGO_RECIBO,
                            DIAS_A_PAGO,
                            DIFERENCIA_MONTO,
                            DIFERENCIA_PORC
                        FROM FP_COBRANZAS_MAYORISTAS_HISTORIAL
                        WHERE COD_VENDED = ?
                        ORDER BY FECHA_ENVIO DESC
                    ";
                    $stmt_cv = sqlsrv_query($conn_apps, $sql_cli_v, [$codV]);
                    $clientes_vendedor = [];
                    if ($stmt_cv !== false) {
                        while ($rowC = sqlsrv_fetch_array($stmt_cv, SQLSRV_FETCH_ASSOC)) {
                            $clientes_vendedor[] = [
                                'ID' => $rowC['ID'],
                                'COD_CLIENT' => trim($rowC['COD_CLIENT']),
                                'RAZON_SOCI' => trim($rowC['RAZON_SOCI'] ?? ''),
                                'FECHA_ENVIO' => $rowC['FECHA_ENVIO'] instanceof DateTime ? $rowC['FECHA_ENVIO']->format('d/m/Y H:i') : $rowC['FECHA_ENVIO'],
                                'TOTAL_PROPUESTO' => (float)$rowC['TOTAL_PROPUESTO'],
                                'MONTO_FACTURA' => (float)$rowC['MONTO_FACTURA'],
                                'MONTO_REMITO' => (float)$rowC['MONTO_REMITO'],
                                'ESTADO' => trim($rowC['ESTADO'] ?? 'ENVIADO'),
                                'NRO_RECIBO' => trim($rowC['NRO_RECIBO'] ?? ''),
                                'FECHA_PAGO' => $rowC['FECHA_PAGO'] instanceof DateTime ? $rowC['FECHA_PAGO']->format('d/m/Y') : $rowC['FECHA_PAGO'],
                                'MONTO_PAGO_RECIBO' => $rowC['MONTO_PAGO_RECIBO'] !== null ? (float)$rowC['MONTO_PAGO_RECIBO'] : null,
                                'DIAS_A_PAGO' => $rowC['DIAS_A_PAGO'] !== null ? (int)$rowC['DIAS_A_PAGO'] : null,
                                'DIFERENCIA_MONTO' => $rowC['DIFERENCIA_MONTO'] !== null ? (float)$rowC['DIFERENCIA_MONTO'] : null,
                                'DIFERENCIA_PORC' => $rowC['DIFERENCIA_PORC'] !== null ? (float)$rowC['DIFERENCIA_PORC'] : null
                            ];
                        }
                    }

                    $reporte_vendedores[] = [
                        'COD_VENDED' => $codV,
                        'NOMBRE_VENDEDOR' => $nombreV,
                        'CANT_ENVIOS' => (int)$rV['CANT_ENVIOS'],
                        'CANT_ABONADOS' => (int)$rV['CANT_ABONADOS'],
                        'MONTO_PROPUESTO' => (float)$rV['MONTO_PROPUESTO'],
                        'MONTO_COBRADO' => (float)$rV['MONTO_COBRADO'],
                        'PROMEDIO_DIAS' => $rV['PROMEDIO_DIAS'] !== null ? round((float)$rV['PROMEDIO_DIAS'], 1) : 0,
                        'CLIENTES' => $clientes_vendedor
                    ];
                }
            }

            echo json_encode([
                'success' => true,
                'kpis' => [
                    'total_enviados' => (int)($kpis['TOTAL_ENVIADOS'] ?? 0),
                    'total_abonados' => (int)($kpis['TOTAL_ABONADOS'] ?? 0),
                    'monto_total_propuesto' => (float)($kpis['MONTO_TOTAL_PROPUESTO'] ?? 0),
                    'monto_total_cobrado' => (float)($kpis['MONTO_TOTAL_COBRADO'] ?? 0),
                    'promedio_dias_pago' => $kpis['PROMEDIO_DIAS_PAGO'] !== null ? round((float)$kpis['PROMEDIO_DIAS_PAGO'], 1) : 0,
                    'promedio_desvio_porc' => $kpis['PROMEDIO_DESVIO_PORC'] !== null ? round((float)$kpis['PROMEDIO_DESVIO_PORC'], 2) : 0
                ],
                'vendedores' => $reporte_vendedores
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Accion no reconocida.']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
