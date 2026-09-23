<?php
/**
 * Devuelve la cadena de fechas derivadas de una fecha de embarque.
 *
 * ES EL UNICO CAMINO POR EL QUE EL NAVEGADOR OBTIENE ESTAS FECHAS. Antes
 * cargaInicial.js las calculaba solo, con 45, 5 y 2 escritos en el codigo, y
 * el cronograma las calculaba con otros numeros. Ahora las dos pantallas leen
 * la misma cadena de CronogramaFechas::cadenaDeFechas(), que sale de
 * RO_T_IMPORTACIONES_PARAM_CRONOGRAMA.
 *
 * POR QUE PIDE LA CADENA Y NO LOS PARAMETROS. Mandarle los dias al navegador
 * para que sume alla habria sacado los numeros del JS pero no la aritmetica, y
 * con la aritmetica duplicada las dos pantallas pueden volver a separarse por
 * un detalle -un mes de 31, un cambio de ano, un fallback distinto-. Pidiendo
 * la cadena, la unica forma de que difieran es que difieran los parametros.
 * Los parametros viajan igual en la respuesta, pero solo para los carteles de
 * la pantalla ("+45 días"), nunca para calcular.
 *
 * ES GET Y NO ESCRIBE NADA: es una consulta pura. La fecha llega por query
 * string para que el navegador la pueda cachear entre teclas.
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

try {
    require_once __DIR__ . '/../../class/conexion.php';
    require_once __DIR__ . '/../cronogramaDespachos/class/CronogramaFechas.php';

    $cid  = new Conexion();
    $db   = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
    $conn = $cid->conectar($db);

    if (!$conn) {
        throw new Exception('No se pudo establecer conexión a la base de datos');
    }

    /* La pantalla manda las dos fechas de embarque y la prioridad -ETD real
       sobre estimado- la resuelve el servidor con fechaBaseEmbarque(), que es
       la misma regla que aplica el cronograma. Si la resolviera el JS,
       volveria a haber dos versiones de "cual es la fecha base". */
    $fila = [
        'FECHA_EMB'     => isset($_GET['fechaEmb'])    ? trim($_GET['fechaEmb'])    : null,
        'FECHA_EST_EMB' => isset($_GET['fechaEstEmb']) ? trim($_GET['fechaEstEmb']) : null,
    ];

    foreach (['FECHA_EMB', 'FECHA_EST_EMB'] as $campo) {
        if (!empty($fila[$campo]) && !CronogramaFechas::esFechaValida($fila[$campo])) {
            throw new Exception('Fecha inválida en ' . $campo . ': se espera AAAA-MM-DD');
        }
    }

    /* El arribo fijado y la recepcion real son OPCIONALES y los manda la
       pantalla solo cuando los tiene: con ellos, la nacionalizacion cuelga del
       arribo en firme en vez del proyectado, que es lo que hace que la fecha
       siga a la ETA que confirmo la naviera. */
    $arribo = isset($_GET['fechaArr']) ? trim($_GET['fechaArr']) : null;
    if (!empty($arribo) && !CronogramaFechas::esFechaValida($arribo)) {
        throw new Exception('Fecha de arribo inválida: se espera AAAA-MM-DD');
    }

    $recepcion = isset($_GET['fechaRec']) ? trim($_GET['fechaRec']) : null;
    if (!empty($recepcion) && !CronogramaFechas::esFechaValida($recepcion)) {
        throw new Exception('Fecha de recepción inválida: se espera AAAA-MM-DD');
    }

    $base      = CronogramaFechas::fechaBaseEmbarque($fila);
    $resultado = CronogramaFechas::cadenaDeFechas($conn, $base, $arribo, $recepcion);

    /* Las fechas van en los dos formatos: 'Y-m-d' es lo que se guarda y
       'd/m/Y' lo que muestran los inputs. Convertir en el JS obligaria a
       repetir ahi una regla de formato ya resuelta; mismo criterio que
       controller/revertirFechaPagoAuto.php. */
    $pantalla = [];
    foreach ($resultado['fechas'] as $clave => $iso) {
        if ($iso === null) {
            $pantalla[$clave] = null;
            continue;
        }
        $p = explode('-', $iso);
        $pantalla[$clave] = (count($p) === 3) ? ($p[2] . '/' . $p[1] . '/' . $p[0]) : $iso;
    }

    /* faltan NO es un error: la pantalla tiene que poder abrirse y mostrar lo
       que hay aunque el script 12 no se haya corrido en esta base. Lo que no
       puede es inventar fechas, asi que vienen en null y con el motivo. */
    $mensaje = null;
    if (!empty($resultado['faltan'])) {
        $mensaje = 'No se pueden calcular las fechas automáticas: faltan parámetros ('
                 . implode(', ', $resultado['faltan']) . '). '
                 . 'Corré comercioExterior/sql/12_parametros_fechas_derivadas.sql en esta base.';
    } elseif ($base === null && empty($arribo)) {
        $mensaje = 'Sin fecha de embarque no hay nada que calcular.';
    } elseif ($base === null) {
        // Con arribo pero sin embarque sale toda la cadena menos el pago, que
        // es la unica que cuelga del embarque.
        $mensaje = 'La fecha estimada de pago no se puede calcular sin fecha de embarque.';
    }

    echo json_encode([
        'success'     => true,
        'entorno'     => $db,
        'fechaBase'   => $base,
        'fechas'      => $resultado['fechas'],
        'pantalla'    => $pantalla,
        'parametros'  => $resultado['parametros'],
        'faltan'      => $resultado['faltan'],
        'message'     => $mensaje,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('Error en calcularCadenaFechas.php: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
