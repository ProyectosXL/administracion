<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

error_reporting(0);
header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['id_ce']) || !isset($data['valor_default_1'])) {
        throw new Exception('Datos incompletos');
    }
    
    require_once '../../../class/conexion.php';
    
    $cid = new Conexion();
    
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    $db = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
    $conn = $cid->conectar($db);
    
    $idCe = intval($data['id_ce']);
    $valorDefault1 = $data['valor_default_1'] !== '' ? floatval($data['valor_default_1']) : null;
    $valorDefault2 = isset($data['valor_default_2']) && $data['valor_default_2'] !== '' ? 
                     floatval($data['valor_default_2']) : null;
    $tipoValor = isset($data['tipo_valor']) ? trim($data['tipo_valor']) : null;
    
    // Validar que el concepto existe
    $sqlCheck = "SELECT ID_CE FROM RO_T_CONCEPTOS_ESTIMACION_COMEX WHERE ID_CE = ?";
    $stmtCheck = sqlsrv_query($conn, $sqlCheck, array($idCe));
    
    if (!$stmtCheck || !sqlsrv_fetch($stmtCheck)) {
        throw new Exception('Concepto no encontrado');
    }
    
    $idRefConcepto = isset($data['id_ref_concepto']) && $data['id_ref_concepto'] !== '' && $data['id_ref_concepto'] !== null ? intval($data['id_ref_concepto']) : null;

    // Actualizar parámetros
    if ($db === 'uy') {
        $moneda = isset($data['moneda']) ? trim($data['moneda']) : 'USD';
        $tipoCambio = isset($data['tipo_cambio']) ? floatval($data['tipo_cambio']) : 1.0;
        $v1Uyu = isset($data['valor_default_1_uyu']) && $data['valor_default_1_uyu'] !== null ? floatval($data['valor_default_1_uyu']) : null;
        $v2Uyu = isset($data['valor_default_2_uyu']) && $data['valor_default_2_uyu'] !== null ? floatval($data['valor_default_2_uyu']) : null;

        $sql = "UPDATE RO_T_CONCEPTOS_ESTIMACION_COMEX 
                SET VALOR_DEFAULT_1 = ?,
                    VALOR_DEFAULT_2 = ?,
                    TIPO_VALOR = COALESCE(?, TIPO_VALOR),
                    MONEDA = ?,
                    TIPO_CAMBIO = ?,
                    VALOR_DEFAULT_1_UYU = ?,
                    VALOR_DEFAULT_2_UYU = ?,
                    ID_REF_CONCEPTO = ?,
                    ULT_ACTUA = GETDATE()
                WHERE ID_CE = ?";
        
        $params = array($valorDefault1, $valorDefault2, $tipoValor, $moneda, $tipoCambio, $v1Uyu, $v2Uyu, $idRefConcepto, $idCe);
    } else {
        $sql = "UPDATE RO_T_CONCEPTOS_ESTIMACION_COMEX 
                SET VALOR_DEFAULT_1 = ?,
                    VALOR_DEFAULT_2 = ?,
                    TIPO_VALOR = COALESCE(?, TIPO_VALOR),
                    ULT_ACTUA = GETDATE()
                WHERE ID_CE = ?";
        
        $params = array($valorDefault1, $valorDefault2, $tipoValor, $idCe);
    }
    
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        throw new Exception('Error al actualizar parámetro: ' . print_r(sqlsrv_errors(), true));
    }

    /* EDITAR UN PARÁMETRO DEJA RASTRO.
       Este UPDATE pisa el valor anterior y no hay dónde leerlo después. Desde
       que las alícuotas tienen vigencia, cada edición además INSERTA una
       vigencia nueva y cierra la anterior, así que el valor viejo sigue
       existiendo con el período en el que rigió.

       vigencia_desde es opcional: si el formulario no lo manda, la vigencia
       arranca HOY, que es lo que afirma una corrección hecha ahora. Quien
       necesite fecharla distinto -"desde el 1/7 rige el 12%"- la manda.

       Si el script 09 no se corrió, esto no hace nada y el UPDATE de arriba
       se comporta como antes: la edición funciona, sin historial. */
    $vigencia = null;
    require_once __DIR__ . '/../../class/AlicuotasVigencia.php';

    if (AlicuotasVigencia::disponible($conn)) {
        $desde = (isset($data['vigencia_desde']) && trim((string) $data['vigencia_desde']) !== '')
            ? $data['vigencia_desde'] : date('Y-m-d');

        $vigencia = AlicuotasVigencia::agregar(
            $conn, $idCe, $valorDefault1, $valorDefault2,
            $desde, null,
            'Editado desde Parámetros',
            $_SESSION['usuario_dns'] ?? null
        );
    }

    echo json_encode([
        'success'  => true,
        'vigencia' => $vigencia,
        'message'  => ($vigencia !== null && $vigencia['success'])
            ? 'Parámetro actualizado. Se registró una vigencia nueva y la anterior quedó cerrada.'
            : 'Parámetro actualizado correctamente'
    ]);
    
} catch (Exception $e) {
    error_log('Error en actualizarParametro.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
