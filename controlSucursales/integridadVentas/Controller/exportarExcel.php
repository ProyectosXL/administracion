<?php
session_start();

// Ajustar ruta para apuntar a la clase conexion desde integridadVentas/Controller/
require_once __DIR__ . '/../../../class/conexion.php';

try {
    // Obtener parámetros
    $desde = $_POST['desde'] ?? '';
    $hasta = $_POST['hasta'] ?? '';

    if (empty($desde) || empty($hasta)) {
        throw new Exception('Las fechas son obligatorias.');
    }

    $conn = new Conexion();

    // Obtener sucursales maestras
    $conexion_maestra = $conn->conectar('locales');
    if (!$conexion_maestra) {
        throw new Exception('No se pudo conectar a la base de datos maestra.');
    }

    $condicion_canal = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy')
        ? "CANAL = 'EXTERIOR' AND HABILITADO = 1"
        : "CANAL = 'PROPIOS' AND HABILITADO = 1";
    
    $sql_maestra = "SELECT NRO_SUCURSAL, COD_CLIENT FROM dbo.SUCURSALES_LAKERS WHERE {$condicion_canal}";
    $stmt_maestra = sqlsrv_query($conexion_maestra, $sql_maestra);
    if ($stmt_maestra === false) {
        throw new Exception('Error al obtener datos de la tabla maestra.');
    }

    $sucursales_maestra = [];
    while ($row = sqlsrv_fetch_array($stmt_maestra, SQLSRV_FETCH_ASSOC)) {
        $sucursales_maestra[$row['NRO_SUCURSAL']] = $row['COD_CLIENT'];
    }
    sqlsrv_close($conexion_maestra);

    // Obtener resultados de ventas
    $db_alias_procesamiento = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy') ? 'suc_uy' : 'locales';
    $conexion_procesamiento = $conn->conectar($db_alias_procesamiento);
    if (!$conexion_procesamiento) {
        throw new Exception('No se pudo conectar a la base de datos de procesamiento.');
    }

    $sql_resultados = "
        SELECT 
            NRO_SUCURS, 
            IMPORTE_CENTRAL, 
            IMPORTE_LOCAL, 
            ISNULL(DIFERENCIA, ISNULL(IMPORTE_CENTRAL, 0) - ISNULL(IMPORTE_LOCAL, 0)) AS DIFERENCIA,
            ESTADO, 
            REFRESHED_AT 
        FROM dbo.RO_T_COMPARA_VENTAS 
        WHERE DESDE = ? AND HASTA = ?
    ";

    $params_resultados = [$desde, $hasta];
    $stmt_resultados = sqlsrv_query($conexion_procesamiento, $sql_resultados, $params_resultados);
    if ($stmt_resultados === false) {
        throw new Exception('Error al obtener resultados de ventas.');
    }
    
    $resultados_ventas = [];
    while ($row = sqlsrv_fetch_array($stmt_resultados, SQLSRV_FETCH_ASSOC)) {
        $resultados_ventas[] = $row;
    }
    sqlsrv_close($conexion_procesamiento);

    // Preparar datos finales
    $data_final = [];
    foreach ($resultados_ventas as $venta) {
        $nro_suc = $venta['NRO_SUCURS'];
        if (isset($sucursales_maestra[$nro_suc])) {
            $data_final[] = [
                'NUM_SUC' => $nro_suc,
                'COD_SUCURSAL' => $sucursales_maestra[$nro_suc],
                'IMPORTE_CENTRAL' => $venta['IMPORTE_CENTRAL'],
                'IMPORTE_LOCAL' => $venta['IMPORTE_LOCAL'],
                'DIFERENCIA' => $venta['DIFERENCIA'],
                'ESTADO' => $venta['ESTADO'],
                'REFRESHED_AT' => $venta['REFRESHED_AT']
            ];
        }
    }

    // Crear el archivo Excel usando formato XML (no requiere librerías externas)
    $pais = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy') ? 'Uruguay' : 'Argentina';
    
    // Calcular totales
    $totalImporteCentral = 0;
    $totalImporteLocal = 0;
    $totalDiferencia = 0;
    
    foreach ($data_final as $fila) {
        $totalImporteCentral += floatval($fila['IMPORTE_CENTRAL']);
        $totalImporteLocal += floatval($fila['IMPORTE_LOCAL']);
        $totalDiferencia += floatval($fila['DIFERENCIA']);
    }

    // Preparar el nombre del archivo
    $filename = 'Control_Ventas_' . date('Ymd', strtotime($desde)) . '_' . date('Ymd', strtotime($hasta)) . '.xls';
    
    // Configurar headers para la descarga
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    // Generar el contenido del archivo Excel en formato XML
    echo '<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">
  <Created>' . date('Y-m-d\TH:i:s\Z') . '</Created>
 </DocumentProperties>
 <Styles>
  <Style ss:ID="Default" ss:Name="Normal">
   <Alignment ss:Vertical="Bottom"/>
   <Borders/>
   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#000000"/>
   <Interior/>
   <NumberFormat/>
   <Protection/>
  </Style>
  <Style ss:ID="s62">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Font ss:FontName="Calibri" ss:Size="14" ss:Color="#000000" ss:Bold="1"/>
  </Style>
  <Style ss:ID="s63">
   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#000000" ss:Bold="1"/>
  </Style>
  <Style ss:ID="s64">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
   </Borders>
   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#FFFFFF" ss:Bold="1"/>
   <Interior ss:Color="#007BFF" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="s65">
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
   </Borders>
  </Style>
  <Style ss:ID="s66">
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
   </Borders>
   <NumberFormat ss:Format="&quot;$&quot;#,##0.00"/>
  </Style>
  <Style ss:ID="s67">
   <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
   </Borders>
   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#FFFFFF" ss:Bold="1"/>
   <Interior ss:Color="#343A40" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="s68">
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
   </Borders>
   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#FFFFFF" ss:Bold="1"/>
   <Interior ss:Color="#343A40" ss:Pattern="Solid"/>
   <NumberFormat ss:Format="&quot;$&quot;#,##0.00"/>
  </Style>
 </Styles>
 <Worksheet ss:Name="Control Ventas">
  <Table>
   <Column ss:Width="80"/>
   <Column ss:Width="80"/>
   <Column ss:Width="100"/>
   <Column ss:Width="100"/>
   <Column ss:Width="100"/>
   <Column ss:Width="80"/>
   <Column ss:Width="120"/>
   <Row>
    <Cell ss:MergeAcross="6" ss:StyleID="s62"><Data ss:Type="String">CONTROL DE VENTAS POR SUCURSAL</Data></Cell>
   </Row>
   <Row>
    <Cell ss:StyleID="s63"><Data ss:Type="String">País: ' . htmlspecialchars($pais) . '</Data></Cell>
    <Cell></Cell>
    <Cell ss:MergeAcross="1" ss:StyleID="s63"><Data ss:Type="String">Período: ' . date('d/m/Y', strtotime($desde)) . ' - ' . date('d/m/Y', strtotime($hasta)) . '</Data></Cell>
   </Row>
   <Row></Row>
   <Row>
    <Cell ss:StyleID="s64"><Data ss:Type="String">Nro. Sucursal</Data></Cell>
    <Cell ss:StyleID="s64"><Data ss:Type="String">Cod. Sucursal</Data></Cell>
    <Cell ss:StyleID="s64"><Data ss:Type="String">Importe Central</Data></Cell>
    <Cell ss:StyleID="s64"><Data ss:Type="String">Importe Local</Data></Cell>
    <Cell ss:StyleID="s64"><Data ss:Type="String">Diferencia</Data></Cell>
    <Cell ss:StyleID="s64"><Data ss:Type="String">Estado</Data></Cell>
    <Cell ss:StyleID="s64"><Data ss:Type="String">Últ. Actualización</Data></Cell>
   </Row>';

    // Agregar filas de datos
    foreach ($data_final as $fila) {
        $refreshedDate = 'N/A';
        if ($fila['REFRESHED_AT']) {
            $refreshedDate = $fila['REFRESHED_AT']->format('d/m/Y H:i');
        }
        
        echo '
   <Row>
    <Cell ss:StyleID="s65"><Data ss:Type="Number">' . htmlspecialchars($fila['NUM_SUC']) . '</Data></Cell>
    <Cell ss:StyleID="s65"><Data ss:Type="String">' . htmlspecialchars($fila['COD_SUCURSAL']) . '</Data></Cell>
    <Cell ss:StyleID="s66"><Data ss:Type="Number">' . number_format(floatval($fila['IMPORTE_CENTRAL']), 2, '.', '') . '</Data></Cell>
    <Cell ss:StyleID="s66"><Data ss:Type="Number">' . number_format(floatval($fila['IMPORTE_LOCAL']), 2, '.', '') . '</Data></Cell>
    <Cell ss:StyleID="s66"><Data ss:Type="Number">' . number_format(floatval($fila['DIFERENCIA']), 2, '.', '') . '</Data></Cell>
    <Cell ss:StyleID="s65"><Data ss:Type="String">' . htmlspecialchars($fila['ESTADO']) . '</Data></Cell>
    <Cell ss:StyleID="s65"><Data ss:Type="String">' . htmlspecialchars($refreshedDate) . '</Data></Cell>
   </Row>';
    }

    // Agregar fila de totales
    echo '
   <Row>
    <Cell ss:MergeAcross="1" ss:StyleID="s67"><Data ss:Type="String">TOTALES</Data></Cell>
    <Cell ss:StyleID="s68"><Data ss:Type="Number">' . number_format($totalImporteCentral, 2, '.', '') . '</Data></Cell>
    <Cell ss:StyleID="s68"><Data ss:Type="Number">' . number_format($totalImporteLocal, 2, '.', '') . '</Data></Cell>
    <Cell ss:StyleID="s68"><Data ss:Type="Number">' . number_format($totalDiferencia, 2, '.', '') . '</Data></Cell>
    <Cell ss:StyleID="s67"><Data ss:Type="String"></Data></Cell>
    <Cell ss:StyleID="s67"><Data ss:Type="String"></Data></Cell>
   </Row>
  </Table>
 </Worksheet>
</Workbook>';
    
    exit;

} catch (Exception $e) {
    // En caso de error, mostrar mensaje
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Error</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            .error { background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; border: 1px solid #f5c6cb; }
        </style>
    </head>
    <body>
        <div class="error">
            <h3>Error al generar el archivo Excel</h3>
            <p>' . htmlspecialchars($e->getMessage()) . '</p>
            <p><a href="javascript:history.back()">Volver</a></p>
        </div>
    </body>
    </html>';
    exit;
}
?>
