<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

/**
 * Convierte fecha de formato DD/MM/YYYY a YYYY-MM-DD para SQL Server
 */
function convertirFecha($fecha)
{
    if (empty($fecha))
        return null;
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        return $fecha;
    }
    $partes = explode('/', $fecha);
    if (count($partes) == 3) {
        return $partes[2] . '-' . $partes[1] . '-' . $partes[0];
    }
    return $fecha;
}

/**
 * Limpieza BLINDADA de moneda.
 * Transforma "$ 29.972.337,50" -> 29972337.50
 * Transforma "1452.50" -> 1452.50
 */
function limpiarMoneda($valor)
{
    if (empty($valor))
        return 0;

    // 1. Asegurar string y quitar $ y espacios
    $valor = (string) $valor;
    $valor = str_replace(['$', ' '], '', $valor);

    // 2. DETECCIÓN DE FORMATO ARGENTINO (Puntos de mil y coma decimal)
    // Si hay una coma, asumimos que es el decimal -> Borramos TODOS los puntos
    if (strpos($valor, ',') !== false) {
        $valor = str_replace('.', '', $valor); // Borrar puntos de mil (29.972... -> 29972...)
        $valor = str_replace(',', '.', $valor); // Coma a punto
    }
    // Si NO hay coma, pero hay MÁS DE UN punto (ej: 29.972.337) -> Son miles
    else if (substr_count($valor, '.') > 1) {
        $valor = str_replace('.', '', $valor);
    }
    // Si no hay coma y solo hay un punto (1452.50), lo dejamos como está (formato SQL válido)

    return floatval($valor);
}

try {
    require_once '../Class/encabezado.php';
    $cid = new Encabezado();

    // Log para verificar qué llega (puedes verlo en el error_log de PHP)
    // error_log("Datos POST recibidos: " . print_r($_POST, true));

    // Detectar modo de operación
    $modoEdicion = isset($_POST['modoEdicion']) && $_POST['modoEdicion'] === 'true';
    $idDespacho = isset($_POST['id']) ? intval($_POST['id']) : 0;

    // Validar datos obligatorios
    if (!isset($_POST['cod_proveedor']) || !isset($_POST['ordenCompra'])) {
        throw new Exception('Faltan datos obligatorios: cod_proveedor o ordenCompra');
    }

    if (!$modoEdicion && (!isset($_POST['fechaEstEmb']) || empty($_POST['fechaEstEmb']))) {
        throw new Exception('Falta la Fecha Estimada de Embarque');
    }

    if ($modoEdicion && $idDespacho <= 0) {
        throw new Exception('ID de despacho inválido para actualización');
    }

    $datosDeCabezera = [];

    // === SECCIÓN 1: DATOS INICIALES ===
    $datosDeCabezera['cod_proveedor'] = $_POST['cod_proveedor'];
    $datosDeCabezera['proveedor'] = $_POST['proveedor'];
    $datosDeCabezera['contenedor'] = $_POST['contenedor'];
    $datosDeCabezera['material'] = $_POST['material'];
    $datosDeCabezera['origen'] = $_POST['origen'];
    // Aplicamos limpieza al FOB Dolar
    $datosDeCabezera['valorFobDolar'] = limpiarMoneda($_POST['valorFobDolar']);
    $datosDeCabezera['fechaEstEmb'] = convertirFecha($_POST['fechaEstEmb']);
    $datosDeCabezera['ocm'] = $_POST['ocm'];
    $datosDeCabezera['despachante'] = isset($_POST['despachante']) && !empty($_POST['despachante'])
        ? $_POST['despachante'] : 'Laffitte';

    // === SECCIÓN 2: DATOS DE EMBARQUE ===
    if (!empty($_POST['fechaEmb']))
        $datosDeCabezera['fechaEmb'] = convertirFecha($_POST['fechaEmb']);
    if (!empty($_POST['fechaArr']))
        $datosDeCabezera['fechaArr'] = convertirFecha($_POST['fechaArr']);
    if (!empty($_POST['numeroBl']))
        $datosDeCabezera['numeroBl'] = $_POST['numeroBl'];
    if (!empty($_POST['factura']))
        $datosDeCabezera['facturaProveedor'] = $_POST['factura'];
    if (!empty($_POST['puertoOrigen']))
        $datosDeCabezera['puertoOrigen'] = $_POST['puertoOrigen'];
    if (!empty($_POST['terminal']))
        $datosDeCabezera['terminal'] = $_POST['terminal'];
    $datosDeCabezera['etaConfirmada'] = isset($_POST['eta_confirmada']) ? intval($_POST['eta_confirmada']) : 0;

    // === SECCIÓN 3: DATOS FINANCIEROS Y ADUANA ===

    // AQUÍ ESTÁ LA CORRECCIÓN CRÍTICA
    // Forzamos la limpieza con la función mejorada
    if (isset($_POST['tipoCambio'])) {
        $datosDeCabezera['tipoCambio'] = limpiarMoneda($_POST['tipoCambio']);
    }

    if (isset($_POST['valorFobPeso'])) {
        $datosDeCabezera['valorFobPeso'] = limpiarMoneda($_POST['valorFobPeso']);
        // Debug temporal: descomentar si sigue fallando para ver qué valor guarda
        // error_log("Valor FOB PESO Limpio: " . $datosDeCabezera['valorFobPeso']);
    }

    /* EL FOB EN PESOS LO DERIVA EL SERVIDOR.
       Desde que el Valor F.O.B. U$S se puede corregir después del alta, el
       FOB en pesos no puede depender de lo que informe el navegador: si el
       campo llegaba vacío, el UPDATE salteaba la columna y quedaba el valor
       calculado con el FOB anterior. Ese número es el que usan el % sobre FOB
       de los costos de nacionalización y la impresión, así que quedaba un
       contenedor diciendo dos cosas distintas sobre cuánto vale.

       Si no hay con qué calcularlo -falta el tipo de cambio- NO se pisa la
       columna y se avisa. Escribir cero afirmaría que el contenedor no vale
       nada; borrarla rompería el % sobre FOB de los costos ya cargados. */
    $esUruguay = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy');
    $fobPesoDerivado = Encabezado::calcularFobPeso(
        $datosDeCabezera['valorFobDolar'],
        $datosDeCabezera['tipoCambio'] ?? null,
        $esUruguay
    );

    $avisoFobPeso = null;
    if ($fobPesoDerivado !== null) {
        $datosDeCabezera['valorFobPeso'] = $fobPesoDerivado;
    } else {
        unset($datosDeCabezera['valorFobPeso']);
        $avisoFobPeso = $esUruguay
            ? 'No se pudo recalcular el Valor F.O.B. $ porque falta el Valor F.O.B. U$S.'
            : 'El Valor F.O.B. $ no se recalculó porque falta el Tipo de Cambio. '
              . 'Cargalo y volvé a guardar para que el % sobre FOB de los costos quede bien.';
    }

    if (!empty($_POST['formaPago']))
        $datosDeCabezera['formaPago'] = $_POST['formaPago'];
    if (!empty($_POST['despacho']))
        $datosDeCabezera['despacho'] = $_POST['despacho'];

    // Fechas sección 3
    if (!empty($_POST['fechaPago']))
        $datosDeCabezera['fechaPago'] = convertirFecha($_POST['fechaPago']);
    if (!empty($_POST['fechaEstPago']))
        $datosDeCabezera['fechaEstPago'] = convertirFecha($_POST['fechaEstPago']);

    if (!empty($_POST['fechaDespAdu']))
        $datosDeCabezera['fechaDespAdu'] = convertirFecha($_POST['fechaDespAdu']);

    /* LOS MARCADORES DE "ESTO LO TOCÓ EL USUARIO", uno por cada fecha que el
       sistema calcula.

       El navegador los manda en 1 solo cuando la fecha se movió desde su
       datepicker, y en 0 cuando el valor que viaja lo calculó el servidor
       -mover el ETD, por ejemplo, recalcula la cadena entera y manda las tres
       fechas distintas-. Es lo único que separa los dos casos, porque los dos
       llegan por este mismo POST con valores distintos de los que había.

       NO ALCANZA SOLO CON ELLOS: Encabezado::marcarFechaFijada() además compara
       contra el maestro y no marca nada si el valor no cambió. El cliente no
       decide solo.

       EL ARRIBO NO TIENE MARCADOR PROPIO: es eta_confirmada, que ya viajaba y
       ya significa esto. Ver el bloque de FECHAS_FIJABLES en encabezado.php. */
    $datosDeCabezera['fechaEstPagoManual'] =
        isset($_POST['fechaEstPagoManual']) && $_POST['fechaEstPagoManual'] == '1';

    $datosDeCabezera['fechaArrManual'] = ($datosDeCabezera['etaConfirmada'] === 1);

    $datosDeCabezera['fechaDespAduManual'] =
        isset($_POST['fechaDespAduManual']) && $_POST['fechaDespAduManual'] == '1';

    /* El mapa que consumen actualizarEncabezado() y actualizarEncabezadoGrupo().
       Las claves son las de Encabezado::FECHAS_FIJABLES. */
    $marcasManuales = [
        'PAGO'            => $datosDeCabezera['fechaEstPagoManual'],
        'ARRIBO'          => $datosDeCabezera['fechaArrManual'],
        'NACIONALIZACION' => $datosDeCabezera['fechaDespAduManual'],
    ];

    // === PROCESO DE GUARDADO ===

    // Procesar Órdenes de Compra
    $ordenCompraRaw = $_POST['ordenCompra'];
    if (is_string($ordenCompraRaw)) {
        $decoded = json_decode($ordenCompraRaw, true);
        $ordenes = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [$ordenCompraRaw];
    } else {
        $ordenes = $ordenCompraRaw;
    }

    if (!is_array($ordenes) || count($ordenes) == 0) {
        // Permitir continuar si es OCM
        if ($_POST['ocm'] != 1) {
            // Log warning but continue
        }
    }

    $arrayResult = [];
    $idPrincipal = null;

    if ($modoEdicion) {
        // === UPDATE ===
        $ordenesFormateadas = array_map(function ($orden) {
            $ordenTrim = trim($orden);
            return (strlen($ordenTrim) == 13) ? ' ' . $ordenTrim : $ordenTrim;
        }, $ordenes);

        $datosDeCabezera['ordenCompra'] = implode(', ', $ordenesFormateadas);

        $result = $cid->actualizarEncabezado($idDespacho, $datosDeCabezera);

        if ($result) {
            // Replicar campos comunes a todas las OCs del grupo (excluye ORDEN_COMPRA, OCM)
            $datosGrupo = array_filter([
                'FECHA_EMB'       => $datosDeCabezera['fechaEmb']         ?? null,
                'FACTURA'         => $datosDeCabezera['facturaProveedor']  ?? null,
                'NUMERO_BL'       => $datosDeCabezera['numeroBl']          ?? null,
                'TIPO_CAMBIO'     => $datosDeCabezera['tipoCambio']        ?? null,
                'VALOR_FOB_DOLAR' => $datosDeCabezera['valorFobDolar']     ?? null,
                'VALOR_FOB_PESO'  => $datosDeCabezera['valorFobPeso']      ?? null,
                'FECHA_ARR'       => $datosDeCabezera['fechaArr']          ?? null,
                'FECHA_DESP_ADU'  => $datosDeCabezera['fechaDespAdu']      ?? null,
                'FECHA_EST_PAGO'  => $datosDeCabezera['fechaEstPago']      ?? null,
                'FECHA_EST_EMB'   => $datosDeCabezera['fechaEstEmb']       ?? null,
                'DESPACHANTE'     => $datosDeCabezera['despachante']       ?? null,
                'PUERTO_ORIGEN'   => $datosDeCabezera['puertoOrigen']      ?? null,
                'TERMINAL'        => $datosDeCabezera['terminal']          ?? null,
                'ETA_CONFIRMADA'  => $datosDeCabezera['etaConfirmada']     ?? null,
                'CONTENEDOR'      => $datosDeCabezera['contenedor']        ?? null,
                'DESPACHO'        => $datosDeCabezera['despacho']          ?? null,
                'MATERIAL'        => $datosDeCabezera['material']          ?? null,
                'ORIGEN'          => $datosDeCabezera['origen']            ?? null,
                'FORMA_PAGO'      => $datosDeCabezera['formaPago']         ?? null,
                'COD_PROVEE'      => $datosDeCabezera['cod_proveedor']     ?? null,
                'PROVEEDOR'       => $datosDeCabezera['proveedor']         ?? null,
            ], fn($v) => $v !== null && $v !== '');

            if (!empty($datosGrupo)) {
                // Los marcadores viajan al grupo: las fechas se replican a
                // todas las OCs del contenedor, así que las marcas también.
                $cid->actualizarEncabezadoGrupo(
                    $idDespacho,
                    $datosGrupo,
                    $marcasManuales
                );
            }

            echo json_encode([
                'success' => true,
                'ids' => [$result],
                'avisoFobPeso' => $avisoFobPeso,
                'message' => $avisoFobPeso === null
                    ? 'Despacho actualizado correctamente'
                    : 'Despacho actualizado. ' . $avisoFobPeso
            ]);
        } else {
            $errors = sqlsrv_errors();
            $errorMsg = 'La base de datos no confirmó la actualización.';
            if ($errors) {
                $errorMsg .= ' Detalle: ' . $errors[0]['message'];
            }
            throw new Exception($errorMsg);
        }

    } else {
        // === INSERT ===
        // La primera OC del lote queda como principal (ID_PADRE IS NULL).
        // Las siguientes se vinculan a ella vía vincularComoPadre().
        error_log("[insertarEncabezado] Iniciando INSERT. Total OCs recibidas: " . count($ordenes) . " | Órdenes: " . implode(', ', $ordenes));
        foreach ($ordenes as $orden) {
            if (strlen(trim($orden)) == 13) {
                $orden = ' ' . trim($orden);
            }
            $datosDeCabezera['ordenCompra'] = $orden;

            error_log("[insertarEncabezado] Insertando OC: '{$orden}'");
            $result = $cid->insertarEncabezado($datosDeCabezera);
            if ($result) {
                $arrayResult[] = $result;
                error_log("[insertarEncabezado] OC '{$orden}' insertada con ID: {$result}");

                if ($idPrincipal === null) {
                    $idPrincipal = $result; // primera OC del lote → principal
                    error_log("[insertarEncabezado] OC principal establecida: ID {$idPrincipal}");
                } else {
                    $cid->vincularComoPadre($result, $idPrincipal);
                    error_log("[insertarEncabezado] OC '{$orden}' (ID {$result}) vinculada como hija de ID {$idPrincipal}");
                }
            } else {
                error_log("[insertarEncabezado] FALLO al insertar OC '{$orden}'");
            }
        }
        error_log("[insertarEncabezado] INSERT completo. IDs generados: " . implode(', ', $arrayResult));

        echo json_encode([
            'success' => true,
            'ids' => $arrayResult,
            'message' => 'Despacho(s) guardado(s) correctamente'
        ]);
    }

} catch (Exception $e) {
    error_log("Error en insertarEncabezado.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>