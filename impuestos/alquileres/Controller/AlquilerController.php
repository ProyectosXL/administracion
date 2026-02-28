<?php

$accion = isset($_GET['accion']) ? $_GET['accion'] : "";

switch ($accion) {

    case 'insertarDetalle':
        insertarDetalle();
        break;

    case 'actualizarDetalle':
        actualizarDetalle();
        break;

    case 'procesar':
        execSpAlquileres();
        break;

    case 'verificarProcesado':
        verificarProcesado();
        break;

    case 'checkCierrePeriodoAnt':
        checkCierrePeriodoAnt();
        break;

    case 'cerrarPeriodo':
        cerrarPeriodo();
        break;

    case 'verificarDiferenciasPreCierre':
        verificarDiferenciasPreCierre();
        break;

    case 'abrirPeriodo':
        abrirPeriodo();
        break;

    // case 'ocultarSucursal':
    //     ocultarSucursal(); 
    //     break;

    case 'aplicarAjuste':
        aplicarAjuste();
        break;

    case 'comprobarAjuste':
        comprobarAjuste();
        break;

    case 'revertirProcesamiento':
        revertirProcesamiento();
        break;

    case 'obtenerSucursalesSinCostos':
        obtenerSucursalesSinCostos();
        break;

    case 'eliminarSucursalDelPeriodo':
        eliminarSucursalDelPeriodo();
        break;

    case 'guardarCoeficiente':
        guardarCoeficiente();
        break;

    case 'traerCoeficiente':
        traerCoeficiente();
        break;

    default:
        // No hacer nada si no hay acción válida
        break;
}

function insertarDetalle()
{

    require_once __DIR__ . '/../Class/Alquiler.php';

    $alquiler = new Alquiler();

    $data = $_POST['values'];

    // DEBUG: Log de inserción
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $entornoActual = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
    $nombreEntorno = ($entornoActual === 'uy') ? 'URUGUAY' : 'URUGUAY';

    error_log("=====================================");
    error_log("💾 DEBUG PHP - insertarDetalle");
    error_log("📍 Entorno: " . $nombreEntorno);
    error_log("📊 Longitud de datos: " . strlen($data) . " caracteres");
    error_log("📝 Primeros 200 caracteres: " . substr($data, 0, 200));

    // Contar cuántos registros se van a insertar
    $cantidadRegistros = substr_count($data, '),(') + 1;
    error_log("🔢 Cantidad de registros a insertar: " . $cantidadRegistros);

    $result = $alquiler->insertarDetalle($data);

    error_log("✅ Resultado de insertarDetalle: " . ($result ? "SUCCESS" : "FAILED"));
    error_log("=====================================");

    return true;
}

function actualizarDetalle()
{

    require_once __DIR__ . '/../Class/Alquiler.php';

    $alquiler = new Alquiler();

    $periodo = $_POST['periodo'];
    $sucursal = $_POST['sucursal'];
    $concepto = $_POST['concepto'];
    $importe = $_POST['importe'];
    $porcentaje = $_POST['porcentaje'];

    // DEBUGGING: Log en el servidor
    error_log("=====================================");
    error_log("🔍 DEBUG PHP - actualizarDetalle");
    error_log("📅 Periodo: " . $periodo);
    error_log("🏢 Sucursal: " . $sucursal);
    error_log("📋 Concepto: " . $concepto);
    error_log("💰 Importe: " . $importe . " (tipo: " . gettype($importe) . ")");
    error_log("📊 Porcentaje: " . $porcentaje);

    // Verificar si el valor es cero
    if ($importe == "0" || $importe == "" || floatval($importe) === 0.0) {
        error_log("⚠️  ADVERTENCIA: Importe es CERO");
    }

    // Obtener el entorno actual desde la sesión
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $entornoActual = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
    $nombreEntorno = ($entornoActual === 'central') ? 'ARGENTINA (ARG)' : 'URUGUAY (UY)';
    error_log("📍 Entorno: " . $nombreEntorno);
    error_log("=====================================");

    $result = $alquiler->actualizarDetalle($periodo, $sucursal, $concepto, $importe, null, $porcentaje);

    // Log del resultado
    error_log("✅ Resultado de actualizarDetalle: " . ($result ? "SUCCESS" : "FAILED"));

    return true;
}

function cargarAlquieres($fecha, $periodo)
{

    require_once __DIR__ . "/../Class/Alquiler.php";
    require_once __DIR__ . "/../Class/Sucursal.php";
    require_once __DIR__ . "/../contratos/Class/Contrato.php";

    $alquiler = new Alquiler();
    $sucursal = new Sucursal();
    $contrato = new Contrato();

    // DEBUG: Log inicio de función
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $entornoActual = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
    $nombreEntorno = ($entornoActual === 'uy') ? 'URUGUAY' : 'ARGENTINA';
    error_log("🆕 cargarAlquieres - Entorno: {$nombreEntorno}, Fecha: {$fecha}, Periodo: {$periodo}");

    $conceptos = $alquiler->traerConceptos();

    $contratoAlquiler = $contrato->traerContratoAlquiler($fecha);

    $todosLosLocales = $sucursal->traerLocales();

    $traerPorcentajes = $alquiler->traerTodosLosPorcentajes();

    $rentabilidadNeta = $alquiler->traerRentabilidadNeta($fecha);
    $rentabilidadBruta = $alquiler->traerRentabilidadBruta($periodo);

    // DEBUG: Log de datos obtenidos
    error_log("  📋 Conceptos: " . count($conceptos));
    error_log("  🏢 Locales: " . count($todosLosLocales));
    error_log("  📊 Porcentajes: " . count($traerPorcentajes));
    error_log("  💰 Rentabilidad Neta: " . count($rentabilidadNeta));
    error_log("  💰 Rentabilidad Bruta: " . count($rentabilidadBruta));
    error_log("  📄 Contratos: " . count($contratoAlquiler));

    $newArray = [];

    foreach ($todosLosLocales as $k => $v) {

        $newArray[$v['NRO_SUCURSAL']] = [];

        foreach ($conceptos as $key => $value) {

            $total = 0;

            if ($value['carga_manual'] != 1) {

                if (in_array($value['ID_CA'], ["9", "13"])) {

                    foreach ($traerPorcentajes as $porcentaje) {

                        if ($porcentaje['ID_CA'] == $value['ID_CA'] && $porcentaje['NRO_SUCURS'] == $v['NRO_SUCURSAL']) {

                            $total = $porcentaje['PORCENTAJE'];
                        }
                    }
                }

                if (in_array($value['ID_CA'], ["6", "15", "16"])) {

                    foreach ($rentabilidadBruta as $rentabilidad) {

                        if ($rentabilidad['NRO_SUCURSAL'] == $v['NRO_SUCURSAL']) {
                            foreach ($traerPorcentajes as $porcentaje) {
                                if ($porcentaje['ID_CA'] == $value['ID_CA'] && $porcentaje['NRO_SUCURS'] == $rentabilidad['NRO_SUCURSAL']) {
                                    // Guardar el valor BRUTO sin restar el mínimo
                                    // JavaScript lo restará dinámicamente cuando el período esté abierto
                                    $porcentajeFloat = floatval(str_replace(',', '.', $porcentaje['PORCENTAJE']));
                                    $total = (($rentabilidad['IMPORTE'] * $porcentajeFloat) / 100);
                                }
                            }
                        }

                    }

                }

                if (in_array($value['ID_CA'], ["7", "17"])) {

                    $encontroRentabilidad = false;
                    foreach ($rentabilidadNeta as $rentabilidad) {

                        if ($rentabilidad['NRO_SUCURS'] == $v['NRO_SUCURSAL']) {
                            $encontroRentabilidad = true;
                            $encontroPorcentaje = false;

                            foreach ($traerPorcentajes as $porcentaje) {
                                if ($porcentaje['ID_CA'] == $value['ID_CA'] && $porcentaje['NRO_SUCURS'] == $rentabilidad['NRO_SUCURS']) {
                                    $encontroPorcentaje = true;
                                    // Para ID_CA 7 y 17: calcular sobre venta neta
                                    // Guardar el valor BRUTO sin restar el mínimo (para concepto 7)
                                    // JavaScript lo restará dinámicamente cuando el período esté abierto
                                    $porcentajeFloat = floatval(str_replace(',', '.', $porcentaje['PORCENTAJE']));
                                    $total = $rentabilidad['VENTA'] * $porcentajeFloat / 100;
                                    break;
                                }
                            }
                        }

                    }
                }

                // ID_CA 14 se calcula DESPUÉS porque depende del concepto 7
                // Se procesará en un segundo bucle después de que todos los conceptos básicos estén calculados


            }

            if (in_array($value['ID_CA'], ["4", "5", "18"])) {

                foreach ($contratoAlquiler as $contrato) {

                    $vigDesde = new DateTime($contrato['VIG_DESDE']->format("Y-m-d")); // Primera fecha
                    $vigHasta = new DateTime($contrato['VIG_HASTA']->format("Y-m-d")); // Segunda fecha

                    $diferenciaDeDias = $vigHasta->diff($vigDesde)->days;

                    // Calcula la diferencia en meses
                    $mesesDiferencia = round(($diferenciaDeDias / 365) * 12);

                    if ($mesesDiferencia == 0) {
                        $mesesDiferencia = 1;
                    }

                    if ($contrato['ID_CA'] == $value['ID_CA'] && $contrato['NRO_SUCURS'] == $v['NRO_SUCURSAL']) {

                        $total = ($contrato['IMPORTE'] / $mesesDiferencia);

                    }

                    if ($contrato['ID_CA_2'] == $value['ID_CA'] && $contrato['NRO_SUCURS'] == $v['NRO_SUCURSAL']) {

                        $total = ($contrato['IMPORTE_2'] / $mesesDiferencia);

                    }

                    if ($contrato['ID_CA_3'] == $value['ID_CA'] && $contrato['NRO_SUCURS'] == $v['NRO_SUCURSAL']) {

                        $total = ($contrato['IMPORTE_3'] / $mesesDiferencia);

                    }
                }

            }

            $newArray[$v['NRO_SUCURSAL']][$value['CONCEPTO']] = $total;

        }
    }

    // SEGUNDO BUCLE: Calcular conceptos que dependen de otros (ID_CA 14)
    // Debe ejecutarse DESPUÉS de que todos los conceptos básicos estén calculados
    foreach ($todosLosLocales as $k => $v) {
        foreach ($conceptos as $key => $value) {
            if ($value['ID_CA'] == "14") {
                // ID_CA 14 = ((Concepto 7 - Valor mínimo mensual) * porcentaje / 100)
                // El concepto 7 tiene el valor BRUTO, debemos restarle el mínimo primero
                $concepto7Bruto = isset($newArray[$v['NRO_SUCURSAL']]["Porc. S/ventas netas"]) ? $newArray[$v['NRO_SUCURSAL']]["Porc. S/ventas netas"] : 0;
                $valorMinimo = isset($newArray[$v['NRO_SUCURSAL']]["Valor minimo mensual"]) ? $newArray[$v['NRO_SUCURSAL']]["Valor minimo mensual"] : 0;

                // Calcular el concepto 7 neto (restando el mínimo)
                $concepto7Neto = $concepto7Bruto - $valorMinimo;
                if ($concepto7Neto < 0) {
                    $concepto7Neto = 0;
                }

                // Buscar el porcentaje para esta sucursal
                $porcentajeEncontrado = false;
                foreach ($traerPorcentajes as $porcentaje) {
                    if ($porcentaje['ID_CA'] == "14" && $porcentaje['NRO_SUCURS'] == $v['NRO_SUCURSAL']) {
                        $porcentajeFloat = floatval(str_replace(',', '.', $porcentaje['PORCENTAJE']));
                        $newArray[$v['NRO_SUCURSAL']][$value['CONCEPTO']] = ($concepto7Neto * $porcentajeFloat) / 100;
                        $porcentajeEncontrado = true;
                        break; // Ya encontramos el porcentaje para esta sucursal
                    }
                }

                // Si no se encontró porcentaje, dejar en 0
                if (!$porcentajeEncontrado) {
                    $newArray[$v['NRO_SUCURSAL']][$value['CONCEPTO']] = 0;
                }

                break; // Ya procesamos el concepto 14, salir del bucle de conceptos
            }
        }
    }

    return $newArray;

}

function traerDetalleAlquiler($fecha, $periodo)
{

    require_once __DIR__ . "/../Class/Alquiler.php";
    require_once __DIR__ . "/../Class/Sucursal.php";
    require_once __DIR__ . "/../contratos/Class/Contrato.php";

    $alquiler = new Alquiler();
    $sucursal = new Sucursal();
    $contrato = new Contrato();

    // DEBUG: Log inicio de función
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $entornoActual = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
    $nombreEntorno = ($entornoActual === 'uy') ? 'URUGUAY' : 'ARGENTINA';
    error_log("🔄 traerDetalleAlquiler - Entorno: {$nombreEntorno}, Fecha: {$fecha}, Periodo: {$periodo}");

    $traerPorcentajes = $alquiler->traerTodosLosPorcentajes();

    $conceptos = $alquiler->traerConceptos();
    $todosLosLocales = $sucursal->traerLocales();

    $rentabilidadNeta = $alquiler->traerRentabilidadNeta($fecha);
    $rentabilidadBruta = $alquiler->traerRentabilidadBruta($periodo);

    $inputStringWithDay = $fecha . "-01";

    $detalle = $alquiler->traerDetalle($periodo);
    $estado = $alquiler->traerEstado($periodo);
    $contratoAlquiler = $contrato->traerContratoAlquiler($fecha);

    // DEBUG: Log de datos obtenidos
    error_log("  📊 Porcentajes encontrados: " . count($traerPorcentajes));
    error_log("  💰 Rentabilidad Neta: " . count($rentabilidadNeta));
    error_log("  💰 Rentabilidad Bruta: " . count($rentabilidadBruta));
    error_log("  📝 Detalle BD: " . count($detalle));
    error_log("  📄 Contratos Alquiler: " . count($contratoAlquiler));
    error_log("  🔒 Estado periodo: " . $estado);

    // DEBUG: Mostrar algunos registros de detalle para verificar
    if (count($detalle) > 0) {
        error_log("📋 Primeros 3 registros del detalle:");
        for ($i = 0; $i < min(3, count($detalle)); $i++) {
            error_log("  - Sucursal: {$detalle[$i]['NRO_SUCURS']}, Concepto: {$detalle[$i]['ID_CA']}, Importe: {$detalle[$i]['IMPORTE_PARSE']}");
        }
    }

    $newArray = [];

    error_log("🔍 Iniciando construcción de newArray");
    error_log("🔒 Estado del período: " . ($estado == 1 ? "CERRADO" : "ABIERTO"));

    // SIEMPRE usar los datos de la base de datos cuando existen registros
    // La diferencia es solo si el período está cerrado (no editable) o abierto (editable)

    foreach ($todosLosLocales as $k => $v) {

        $newArray[$v['NRO_SUCURSAL']] = [];

        foreach ($conceptos as $key => $value) {

            // Inicializar en 0
            $newArray[$v['NRO_SUCURSAL']][$value['CONCEPTO']] = 0;

            // Para conceptos 6 y 7, si el período está ABIERTO, recalcular siempre
            // No usar el valor de BD porque puede tener la resta ya aplicada
            if ($estado == 0 && in_array($value['ID_CA'], ["6", "7"])) {

                if ($value['ID_CA'] == "6") {
                    // Recalcular: venta bruta * porcentaje / 100 (SIN restar el mínimo)
                    foreach ($rentabilidadBruta as $rentabilidad) {
                        if ($rentabilidad['NRO_SUCURSAL'] == $v['NRO_SUCURSAL']) {
                            foreach ($traerPorcentajes as $porcentaje) {
                                if ($porcentaje['ID_CA'] == $value['ID_CA'] && $porcentaje['NRO_SUCURS'] == $rentabilidad['NRO_SUCURSAL']) {
                                    $porcentajeFloat = floatval(str_replace(',', '.', $porcentaje['PORCENTAJE']));
                                    $newArray[$v['NRO_SUCURSAL']][$value['CONCEPTO']] = (($rentabilidad['IMPORTE'] * $porcentajeFloat) / 100);
                                }
                            }
                        }
                    }
                } else if ($value['ID_CA'] == "7") {
                    // Recalcular: venta neta * porcentaje / 100 (SIN restar el mínimo)
                    foreach ($rentabilidadNeta as $rentabilidad) {
                        if ($rentabilidad['NRO_SUCURS'] == $v['NRO_SUCURSAL']) {
                            foreach ($traerPorcentajes as $porcentaje) {
                                if ($porcentaje['ID_CA'] == $value['ID_CA'] && $porcentaje['NRO_SUCURS'] == $rentabilidad['NRO_SUCURS']) {
                                    $porcentajeFloat = floatval(str_replace(',', '.', $porcentaje['PORCENTAJE']));
                                    $newArray[$v['NRO_SUCURSAL']][$value['CONCEPTO']] = (($rentabilidad['VENTA'] * $porcentajeFloat) / 100);
                                }
                            }
                        }
                    }
                }

                continue; // Saltar la búsqueda en BD para estos conceptos
            }

            // Para conceptos 15 y 16, si el período está ABIERTO, recalcular
            if ($estado == 0 && in_array($value['ID_CA'], ["15", "16"])) {
                foreach ($rentabilidadBruta as $rentabilidad) {
                    if ($rentabilidad['NRO_SUCURSAL'] == $v['NRO_SUCURSAL']) {
                        foreach ($traerPorcentajes as $porcentaje) {
                            if ($porcentaje['ID_CA'] == $value['ID_CA'] && $porcentaje['NRO_SUCURS'] == $rentabilidad['NRO_SUCURSAL']) {
                                $porcentajeFloat = floatval(str_replace(',', '.', $porcentaje['PORCENTAJE']));
                                $newArray[$v['NRO_SUCURSAL']][$value['CONCEPTO']] = (($rentabilidad['IMPORTE'] * $porcentajeFloat) / 100);
                            }
                        }
                    }
                }
                continue;
            }

            // Para concepto 17, si el período está ABIERTO, recalcular
            if ($estado == 0 && $value['ID_CA'] == "17") {
                foreach ($rentabilidadNeta as $rentabilidad) {
                    if ($rentabilidad['NRO_SUCURS'] == $v['NRO_SUCURSAL']) {
                        foreach ($traerPorcentajes as $porcentaje) {
                            if ($porcentaje['ID_CA'] == $value['ID_CA'] && $porcentaje['NRO_SUCURS'] == $rentabilidad['NRO_SUCURS']) {
                                $porcentajeFloat = floatval(str_replace(',', '.', $porcentaje['PORCENTAJE']));
                                $newArray[$v['NRO_SUCURSAL']][$value['CONCEPTO']] = (($rentabilidad['VENTA'] * $porcentajeFloat) / 100);
                            }
                        }
                    }
                }
                continue;
            }

            // Para concepto 14, si el período está ABIERTO, NO procesar aquí
            // Se calculará en un segundo bucle después de que concepto 7 esté listo
            if ($estado == 0 && $value['ID_CA'] == "14") {
                continue; // Saltar por ahora, se procesará después
            }

            // Para todos los demás conceptos, o si está cerrado, usar el valor de BD
            $encontrado = false;
            foreach ($detalle as $det) {
                if ($det['NRO_SUCURS'] == $v['NRO_SUCURSAL'] && $det['ID_CA'] == $value['ID_CA']) {
                    $newArray[$v['NRO_SUCURSAL']][$value['CONCEPTO']] = $det['IMPORTE_PARSE'];
                    $encontrado = true;

                    // DEBUG: Log de asignación para primeras sucursales
                    if ($k < 2 && $key < 3) {
                        error_log("✓ Asignado Suc: {$v['NRO_SUCURSAL']}, Concepto: {$value['ID_CA']} ({$value['CONCEPTO']}), Valor: {$det['IMPORTE_PARSE']}");
                    }
                    break;
                }
            }

            // DEBUG: Log para valores no encontrados
            if (!$encontrado && $k < 2 && $key < 3) { // Solo primeras iteraciones para no saturar
                error_log("⚠️  No se encontró detalle para Sucursal: {$v['NRO_SUCURSAL']}, Concepto: {$value['ID_CA']} ({$value['CONCEPTO']})");
            }
        }
    }

    // SEGUNDO BUCLE: Si el período está ABIERTO, calcular concepto 14 que depende del concepto 7
    if ($estado == 0) {
        foreach ($todosLosLocales as $k => $v) {
            foreach ($conceptos as $key => $value) {
                if ($value['ID_CA'] == "14") {
                    // ID_CA 14 = ((Concepto 7 - Valor mínimo mensual) * porcentaje / 100)
                    // El concepto 7 tiene el valor BRUTO, debemos restarle el mínimo primero
                    $concepto7Bruto = isset($newArray[$v['NRO_SUCURSAL']]["Porc. S/ventas netas"]) ? $newArray[$v['NRO_SUCURSAL']]["Porc. S/ventas netas"] : 0;
                    $valorMinimo = isset($newArray[$v['NRO_SUCURSAL']]["Valor minimo mensual"]) ? $newArray[$v['NRO_SUCURSAL']]["Valor minimo mensual"] : 0;

                    // Calcular el concepto 7 neto (restando el mínimo)
                    $concepto7Neto = $concepto7Bruto - $valorMinimo;
                    if ($concepto7Neto < 0) {
                        $concepto7Neto = 0;
                    }

                    // Buscar el porcentaje para esta sucursal
                    $porcentajeEncontrado = false;
                    foreach ($traerPorcentajes as $porcentaje) {
                        if ($porcentaje['ID_CA'] == "14" && $porcentaje['NRO_SUCURS'] == $v['NRO_SUCURSAL']) {
                            $porcentajeFloat = floatval(str_replace(',', '.', $porcentaje['PORCENTAJE']));
                            $newArray[$v['NRO_SUCURSAL']][$value['CONCEPTO']] = ($concepto7Neto * $porcentajeFloat) / 100;
                            $porcentajeEncontrado = true;
                            break; // Ya encontramos el porcentaje para esta sucursal
                        }
                    }

                    // Si no se encontró porcentaje, dejar en 0
                    if (!$porcentajeEncontrado) {
                        $newArray[$v['NRO_SUCURSAL']][$value['CONCEPTO']] = 0;
                    }

                    break; // Ya procesamos el concepto 14, salir del bucle de conceptos
                }
            }
        }
    }

    error_log("✅ Array construido con " . count($newArray) . " sucursales");

    // DEBUG: Mostrar contenido del array para la primera sucursal
    if (count($newArray) > 0) {
        $primeraSucursal = array_key_first($newArray);
        error_log("📦 Contenido primera sucursal ({$primeraSucursal}):");
        $count = 0;
        foreach ($newArray[$primeraSucursal] as $concepto => $valor) {
            if ($count < 5) {
                error_log("  - {$concepto}: {$valor}");
                $count++;
            }
        }
    }

    return $newArray;
}

function traerLocales()
{

    require_once __DIR__ . "/../Class/Sucursal.php";

    $sucursal = new Sucursal();
    $todosLosLocales = $sucursal->traerLocales();

    return $todosLosLocales;
}

function traerConceptos()
{

    require_once __DIR__ . "/../Class/Alquiler.php";

    $alquiler = new Alquiler();

    $conceptos = $alquiler->traerConceptos();

    return $conceptos;
}

function consultarMesesDetalle($periodoPasado, $periodo)
{

    require_once __DIR__ . "/../Class/Alquiler.php";
    require_once __DIR__ . "/../Class/Sucursal.php";

    $alquiler = new Alquiler();
    $sucursal = new Sucursal();

    $conceptos = $alquiler->traerConceptos();
    $detalles = $alquiler->consultarMesesDetalle($periodoPasado, $periodo);

    $locales = $sucursal->traerLocales();

    $newArray = [];
    $periodoActual = "";
    $sucursalActual = "";
    foreach ($conceptos as $concepto) {

        $newArray[$concepto['ID_CA']] = [];
        $total = 0;
        foreach ($detalles as $key => $detalle) {

            if ($detalle['ID_CA'] == $concepto['ID_CA']) {

                if ($detalle['PERIODO'] != $periodoActual) {
                    $total = 0;
                }

                $newArray[$concepto['ID_CA']][$detalle['PERIODO']] = [];

                $total += (int) $detalle['IMPORTE'];
                $newArray[$concepto['ID_CA']][$detalle['PERIODO']]['TOTAL'] = "";
                $newArray[$concepto['ID_CA']][$detalle['PERIODO']]['TOTAL'] = $total;

                $periodoActual = $detalle['PERIODO'];

            }

        }



    }


    return $newArray;

}

function traerArrayPeriodo()
{
    $fechaActual = date('Y-m-d'); // Obtiene la fecha actual en el formato "Año-Mes-Día"
    $fechaHaceUnAnio = date('Y-m-d', strtotime('-1 year', strtotime($fechaActual)));

    $hoy = new DateTime($fechaActual);
    $haceUnAnio = new DateTime($fechaHaceUnAnio);

    $fecha = $hoy;

    $interval = $hoy->diff($haceUnAnio);
    $interval = $interval->format('%a');

    $dates = [];

    for ($i = $interval; $i > 0; $i--) {

        $fecha = $fecha->sub(new DateInterval('P1D'));
        $periodo = substr($fecha->format('Y-m-d'), 0, 7);

        if (!in_array($periodo, $dates)) {
            $dates[] = $periodo;
        }
    }
    foreach ($dates as $key => &$value) {
        $valor = explode("-", $value);
        $value = (int) $valor[1] . "-" . $valor[0];

    }

    return ($dates);
}

function traerDetalleHaceUnAño($periodoPasado, $now)
{

    require_once __DIR__ . "/../Class/Alquiler.php";
    require_once __DIR__ . "/../Class/Sucursal.php";

    $alquiler = new Alquiler();
    $sucursal = new Sucursal();

    $detalles = $alquiler->consultarMesesDetalle($periodoPasado, $now);

    return $detalles;
}

function execSpAlquileres()
{

    require_once __DIR__ . "/../Class/Alquiler.php";
    $alquiler = new Alquiler();

    $periodo = $_POST['periodo'];

    $alquiler->execSpAlquileres($periodo);

}

function verificarProcesado()
{

    require_once __DIR__ . "/../Class/Alquiler.php";

    $alquiler = new Alquiler();

    $fecha = $_POST['fecha'];

    $result = $alquiler->verificarProcesado($fecha);

    echo json_encode($result);
}

function cerrarPeriodo()
{

    require_once __DIR__ . "/../Class/Alquiler.php";

    $alquiler = new Alquiler();

    $periodo = $_POST['periodo'];
    $forzarCierre = isset($_POST['forzarCierre']) ? $_POST['forzarCierre'] : false;

    // Si no es un cierre forzado, verificar que no haya diferencias pendientes
    if (!$forzarCierre) {
        $diferencias = verificarDiferenciasInterno($periodo);
        if (!empty($diferencias)) {
            echo json_encode([
                'status' => 'diferencias',
                'message' => 'Se detectaron diferencias en los valores',
                'diferencias' => $diferencias
            ]);
            return;
        }
    }

    $result = $alquiler->cerrarPeriodo($periodo);

    echo json_encode([
        'status' => 'success',
        'message' => 'Período cerrado correctamente'
    ]);
}

function verificarDiferenciasPreCierre()
{
    $periodo = $_POST['periodo'];
    $diferencias = verificarDiferenciasInterno($periodo);

    echo json_encode([
        'status' => 'success',
        'diferencias' => $diferencias,
        'tieneDiferencias' => !empty($diferencias)
    ]);
}

function verificarDiferenciasInterno($periodo)
{
    require_once __DIR__ . "/../Class/Alquiler.php";
    require_once __DIR__ . "/../Class/Sucursal.php";

    $alquiler = new Alquiler();
    $sucursal = new Sucursal();

    // Obtener los datos guardados en la base de datos
    $detalleGuardado = $alquiler->traerDetalle($periodo);

    // Obtener los datos actuales enviados desde el frontend
    $datosActuales = isset($_POST['datosActuales']) ? $_POST['datosActuales'] : [];

    $diferencias = [];

    // Comparar cada registro
    foreach ($detalleGuardado as $registroGuardado) {
        $nroSucursal = $registroGuardado['NRO_SUCURS'];
        $idConcepto = $registroGuardado['ID_CA'];
        $importeGuardado = floatval($registroGuardado['IMPORTE_PARSE']);

        // Buscar el valor actual correspondiente
        if (isset($datosActuales[$nroSucursal]) && isset($datosActuales[$nroSucursal][$idConcepto])) {
            $importeActual = floatval($datosActuales[$nroSucursal][$idConcepto]);

            // Verificar si hay diferencia (tolerancia de 0.01 por redondeos)
            // Usar abs() para comparar valores absolutos y evitar problemas con signos
            $diferencia = $importeActual - $importeGuardado;

            if (abs($diferencia) > 0.01) {
                $diferencias[] = [
                    'sucursal' => $nroSucursal,
                    'concepto' => $idConcepto,
                    'desc_sucursal' => $registroGuardado['DESC_SUCURS'],
                    'importeGuardado' => $importeGuardado,
                    'importeActual' => $importeActual,
                    'diferencia' => $diferencia
                ];
            }
        }
    }

    return $diferencias;
}

function abrirPeriodo()
{

    require_once __DIR__ . "/../Class/Alquiler.php";

    $alquiler = new Alquiler();

    $periodo = $_POST['periodo'];

    try {
        $result = $alquiler->abrirPeriodo($periodo);

        if ($result) {
            echo 0; // Período abierto correctamente
        } else {
            echo 1; // Error al abrir período
        }
    } catch (\Throwable $th) {
        echo 1; // Error
    }
}

function checkCierrePeriodoAnt()
{

    require_once __DIR__ . "/../Class/Alquiler.php";

    $alquiler = new Alquiler();

    $mesAnterior = $_POST['mesAnterior'];

    $result = $alquiler->checkCierrePeriodoAnt($mesAnterior);

    echo json_encode($result);
}

// FUNCIÓN DESHABILITADA - No se usa más
/*
function ocultarSucursal () {

    require_once __DIR__ . "/../Class/Alquiler.php";

    $alquiler = new Alquiler();

    $sucursal = $_POST['sucursal'];
    $periodo = $_POST['periodo'];

    $result = $alquiler->traerSucursalesOcultas($periodo);

    if(count($result) > 0){

        $arraySucursales = json_decode($result[0]['JSON_LOCALES'], true);

        if ($arraySucursales !== null) {
          
            $nuevosValores = [$sucursal]; 
        
            if (isset($arraySucursales['sucursales'])) {
    
                $valoresExistente = explode(',', $arraySucursales['sucursales']);
          
                $nuevosValores = array_filter($nuevosValores, function ($valor) use ($valoresExistente) {
                    return !in_array($valor, $valoresExistente);
                });

                $nuevosValores = array_merge($valoresExistente, $nuevosValores);
            }

            $arraySucursales['sucursales'] = implode(',', $nuevosValores);
        
        }
            
  
        $result = $alquiler->ocultarSucursal($periodo, json_encode($arraySucursales));
    }else{

        $jsonSucursal = [
            "sucursales" => $sucursal
        ];

        $jsonSucursal = json_encode($jsonSucursal);
        $result = $alquiler->ocultarSucursal($periodo, $jsonSucursal);
    }

    echo json_encode($result);
}
*/

function aplicarAjuste()
{

    require_once __DIR__ . "/../Class/Alquiler.php";
    require_once __DIR__ . "/../Class/Sucursal.php";

    $alquiler = new Alquiler();
    $sucursal = new Sucursal();

    // Decodificar el JSON que viene del frontend
    $data = isset($_POST['arrayData']) ? json_decode($_POST['arrayData'], true) : [];
    $periodo = $_POST['periodo'];

    // LOG: Ver qué se está recibiendo
    error_log("DEBUG aplicarAjuste - Período recibido: " . $periodo);
    error_log("DEBUG aplicarAjuste - Tipo de dato: " . gettype($periodo));
    error_log("DEBUG aplicarAjuste - Array data: " . print_r($data, true));

    // Verificar si hay datos para ajustar
    $hayDatosParaAjustar = false;
    foreach ($data as $key => $value) {
        if (count($value) > 0) {
            $hayDatosParaAjustar = true;
            break;
        }
    }

    error_log("DEBUG aplicarAjuste - Hay datos para ajustar: " . ($hayDatosParaAjustar ? 'SI' : 'NO'));

    // Si no hay datos, verificar si ya están ajustados
    if (!$hayDatosParaAjustar) {
        error_log("DEBUG aplicarAjuste - No hay datos para ajustar, verificando estado en BD");

        // Obtener todas las sucursales y verificar si tienen conceptos 4, 5, 18 con valores
        $detalle = $alquiler->traerDetalle($periodo);
        $conceptosConValor = [];
        $conceptosAjustados = [];

        foreach ($detalle as $det) {
            if (in_array($det['ID_CA'], ['4', '5', '18'])) {
                $importe = floatval($det['IMPORTE_PARSE']);
                $ajustado = intval($det['AJUSTADO']);

                if ($importe > 0) {
                    $key = $det['NRO_SUCURS'] . '-' . $det['ID_CA'];
                    $conceptosConValor[] = $key;

                    if ($ajustado == 1) {
                        $conceptosAjustados[] = $key;
                    }
                }
            }
        }

        error_log("DEBUG aplicarAjuste - Conceptos con valor: " . count($conceptosConValor));
        error_log("DEBUG aplicarAjuste - Conceptos ajustados: " . count($conceptosAjustados));

        if (count($conceptosConValor) == 0) {
            echo json_encode([
                'status' => 'warning',
                'code' => 3,
                'message' => 'No hay valores en los conceptos 4, 5 o 18 para ajustar'
            ]);
        } else if (count($conceptosAjustados) > 0 && count($conceptosConValor) == count($conceptosAjustados)) {
            echo json_encode([
                'status' => 'info',
                'code' => 2,
                'message' => 'El ajuste ya fue aplicado anteriormente para todos los conceptos'
            ]);
        } else if (count($conceptosAjustados) > 0) {
            echo json_encode([
                'status' => 'warning',
                'code' => 4,
                'message' => 'Algunos conceptos ya tienen ajuste aplicado. Verifique los datos.'
            ]);
        } else {
            // Hay valores pero no fueron enviados desde el frontend
            // Esto puede pasar si los campos están deshabilitados en el frontend pero no marcados como ajustados en BD
            echo json_encode([
                'status' => 'error',
                'code' => 5,
                'message' => 'Error de sincronización. Los campos parecen estar bloqueados pero no hay registro de ajuste en la base de datos. Intente abrir el período y volver a cargar.'
            ]);
        }
        die();
    }

    // Verificar coeficiente
    error_log("DEBUG aplicarAjuste - Llamando a traerCoeficiente con período: " . $periodo);
    $coeficiente = $alquiler->traerCoeficiente($periodo);
    error_log("DEBUG aplicarAjuste - Coeficiente recibido: " . $coeficiente);

    if ($coeficiente == 0 || $coeficiente == null) {
        error_log("DEBUG aplicarAjuste - ERROR: Coeficiente es 0 o null");
        echo json_encode([
            'status' => 'error',
            'code' => 0,
            'message' => 'El coeficiente correspondiente al período no se encuentra cargado'
        ]);
        die();
    }

    // Aplicar ajuste
    $registrosActualizados = 0;
    $registrosOmitidos = 0;

    foreach ($data as $key => $value) {
        foreach ($value as $detalle) {
            $importe = $detalle['value'] * $coeficiente;
            $resultado = $alquiler->aplicarAjuste($key, $detalle['concepto'], $importe, $periodo);

            if ($resultado) {
                $registrosActualizados++;
            } else {
                $registrosOmitidos++;
                error_log("DEBUG aplicarAjuste - Registro omitido (ya ajustado): Sucursal=$key, Concepto={$detalle['concepto']}");
            }
        }
    }

    error_log("DEBUG aplicarAjuste - Total actualizados: $registrosActualizados, Total omitidos: $registrosOmitidos");

    if ($registrosActualizados == 0 && $registrosOmitidos > 0) {
        echo json_encode([
            'status' => 'info',
            'code' => 2,
            'message' => 'Todos los registros ya tenían el ajuste aplicado anteriormente',
            'registros_omitidos' => $registrosOmitidos
        ]);
    } else {
        echo json_encode([
            'status' => 'success',
            'code' => 1,
            'message' => 'Ajuste aplicado correctamente',
            'registros_actualizados' => $registrosActualizados,
            'registros_omitidos' => $registrosOmitidos,
            'coeficiente' => $coeficiente
        ]);
    }
}

function comprobarAjuste()
{

    require_once __DIR__ . "/../Class/Alquiler.php";

    $alquiler = new Alquiler();

    $periodo = $_POST['periodo'];

    // Usar el nuevo método que verifica con la consulta optimizada
    $error = $alquiler->comprobarAjusteGeneral($periodo);

    // Retorna 1 si hay registros sin ajustar (error)
    // Retorna 0 si todo está ajustado (ok)
    echo ($error);
}


function revertirProcesamiento()
{

    require_once __DIR__ . "/../Class/Alquiler.php";

    $alquiler = new Alquiler();

    $periodo = $_POST['periodo'];

    try {
        $result = $alquiler->revertirProcesamiento($periodo);

        echo json_encode([
            'status' => 'success',
            'message' => 'Procesamiento revertido correctamente',
            'periodo' => $periodo
        ]);

    } catch (\Throwable $th) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error al revertir procesamiento: ' . $th->getMessage(),
            'periodo' => $periodo
        ]);
    }
}

function obtenerSucursalesSinCostos()
{

    require_once __DIR__ . "/../Class/Alquiler.php";

    $alquiler = new Alquiler();

    $periodo = $_POST['periodo'];

    try {
        $sucursales = $alquiler->obtenerSucursalesSinCostos($periodo);

        echo json_encode([
            'status' => 'success',
            'sucursales' => $sucursales,
            'cantidad' => count($sucursales)
        ]);

    } catch (\Throwable $th) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error al obtener sucursales sin costos: ' . $th->getMessage()
        ]);
    }
}

function eliminarSucursalDelPeriodo()
{

    require_once __DIR__ . "/../Class/Alquiler.php";

    $alquiler = new Alquiler();

    $periodo = $_POST['periodo'];
    $nroSucursal = $_POST['nroSucursal'];

    try {
        $result = $alquiler->eliminarSucursalDelPeriodo($periodo, $nroSucursal);

        echo json_encode([
            'status' => 'success',
            'message' => 'Sucursal eliminada correctamente del período',
            'sucursal' => $nroSucursal,
            'periodo' => $periodo,
            'registrosEliminados' => $result['rowsAffected']
        ]);

    } catch (\Throwable $th) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error al eliminar sucursal: ' . $th->getMessage()
        ]);
    }
}

function guardarCoeficiente()
{
    require_once __DIR__ . "/../Class/Alquiler.php";

    $alquiler = new Alquiler();

    $periodo = $_POST['periodo'];
    $coeficiente = $_POST['coeficiente'];

    $result = $alquiler->guardarCoeficiente($periodo, $coeficiente);

    if ($result) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Coeficiente guardado correctamente'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error al guardar el coeficiente'
        ]);
    }
}

function traerCoeficiente()
{
    require_once __DIR__ . "/../Class/Alquiler.php";
    $alquiler = new Alquiler();
    $periodo = isset($_POST['periodo']) ? $_POST['periodo'] : '';

    $coeficiente = $alquiler->traerCoeficiente($periodo);

    if (!$coeficiente || $coeficiente == 0) {
        $coeficienteStr = "1,0000";
    } else {
        $coeficienteStr = str_replace('.', ',', (string) $coeficiente);
        if (strpos($coeficienteStr, ',') === false) {
            $coeficienteStr .= ",0000";
        } else {
            $parts = explode(',', $coeficienteStr);
            $coeficienteStr = $parts[0] . ',' . str_pad(substr($parts[1], 0, 4), 4, '0', STR_PAD_RIGHT);
        }
    }

    echo json_encode([
        'status' => 'success',
        'coeficiente' => $coeficienteStr
    ]);
}

?>