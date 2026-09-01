<?php
session_start();

// Ajustar ruta para apuntar a la clase conexion desde integridadVentas/Controller/
require_once __DIR__ . '/../../../class/conexion.php';

set_time_limit(600);

try {
    $desde = $_POST['desde'] ?? '';
    $hasta = $_POST['hasta'] ?? '';

    if (empty($desde) || empty($hasta)) {
        throw new Exception('Las fechas son obligatorias.');
    }

    $conn = new Conexion();

    $db_alias = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy') ? 'suc_uy' : 'locales';
    $conexion = $conn->conectar($db_alias);
    if (!$conexion) {
        throw new Exception('No se pudo conectar a la base de datos.');
    }

    // Detalle de comprobantes con el importe central por sucursal
    $sql_detalle = "
        SELECT
            A.NRO_SUCURS                   AS nro_sucursal,
            C.DESC_SUCURSAL,
            CAST(A.FECHA_EMIS AS DATE)     AS fecha_emis,
            A.T_COMP,
            A.N_COMP,
            CASE WHEN A.T_COMP LIKE 'NC%' THEN -B.IMPORTE_CENTRAL ELSE B.IMPORTE_CENTRAL END AS importe_central
        FROM dbo.CTA02 AS A WITH (NOLOCK)
        INNER JOIN (
            SELECT
                B.NRO_SUCURSAL,
                T_COMP,
                N_COMP,
                SUM(NETO_GRAV + IMPORTE) AS IMPORTE_CENTRAL
            FROM dbo.CTA04 A WITH (NOLOCK)
            INNER JOIN SUCURSAL B ON A.NRO_SUCURS = B.NRO_SUCURSAL
            GROUP BY
                B.NRO_SUCURSAL,
                T_COMP,
                N_COMP
        ) AS B
            ON A.NRO_SUCURS = B.NRO_SUCURSAL
           AND A.T_COMP      = B.T_COMP
           AND A.N_COMP      = B.N_COMP
        LEFT JOIN SUCURSALES_LAKERS C ON A.NRO_SUCURS = C.NRO_SUCURSAL
        WHERE A.NRO_SUCURS != '1'
          AND CAST(A.FECHA_EMIS AS DATE) BETWEEN ? AND ?
        ORDER BY A.NRO_SUCURS, fecha_emis, A.T_COMP, A.N_COMP
    ";

    $stmt_detalle = sqlsrv_query($conexion, $sql_detalle, [$desde, $hasta]);
    if ($stmt_detalle === false) {
        $errors = sqlsrv_errors();
        $error_msg = 'Error al obtener el detalle de comprobantes.';
        if ($errors) {
            $error_msg .= ' Detalles: ' . $errors[0]['message'];
        }
        throw new Exception($error_msg);
    }

    $pais = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy') ? 'Uruguay' : 'Argentina';

    $filename = 'Detalle_Comprobantes_IVA_' . date('Ymd', strtotime($desde)) . '_' . date('Ymd', strtotime($hasta)) . '.xls';

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

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
  <Style ss:ID="s69">
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
   </Borders>
   <NumberFormat ss:Format="dd/mm/yyyy"/>
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
 <Worksheet ss:Name="Detalle Comprobantes">
  <Table>
   <Column ss:Width="80"/>
   <Column ss:Width="180"/>
   <Column ss:Width="90"/>
   <Column ss:Width="70"/>
   <Column ss:Width="110"/>
   <Column ss:Width="110"/>
   <Row>
    <Cell ss:MergeAcross="5" ss:StyleID="s62"><Data ss:Type="String">DETALLE DE COMPROBANTES - IVA VENTAS</Data></Cell>
   </Row>
   <Row>
    <Cell ss:StyleID="s63"><Data ss:Type="String">País: ' . htmlspecialchars($pais) . '</Data></Cell>
    <Cell></Cell>
    <Cell ss:MergeAcross="1" ss:StyleID="s63"><Data ss:Type="String">Período: ' . date('d/m/Y', strtotime($desde)) . ' - ' . date('d/m/Y', strtotime($hasta)) . '</Data></Cell>
   </Row>
   <Row></Row>
   <Row>
    <Cell ss:StyleID="s64"><Data ss:Type="String">Nro. Sucursal</Data></Cell>
    <Cell ss:StyleID="s64"><Data ss:Type="String">Sucursal</Data></Cell>
    <Cell ss:StyleID="s64"><Data ss:Type="String">Fecha Emisión</Data></Cell>
    <Cell ss:StyleID="s64"><Data ss:Type="String">T. Comp.</Data></Cell>
    <Cell ss:StyleID="s64"><Data ss:Type="String">N. Comp.</Data></Cell>
    <Cell ss:StyleID="s64"><Data ss:Type="String">Importe Central</Data></Cell>
   </Row>';

    $cantidad = 0;
    $totalImporte = 0;

    while ($row = sqlsrv_fetch_array($stmt_detalle, SQLSRV_FETCH_ASSOC)) {
        $fecha = $row['fecha_emis'];
        $fecha_excel = '';
        if ($fecha instanceof DateTime) {
            $fecha_excel = $fecha->format('Y-m-d\T00:00:00.000');
        }

        $importe = floatval($row['importe_central']);
        $totalImporte += $importe;
        $cantidad++;

        $celda_fecha = ($fecha_excel !== '')
            ? '<Cell ss:StyleID="s69"><Data ss:Type="DateTime">' . $fecha_excel . '</Data></Cell>'
            : '<Cell ss:StyleID="s65"><Data ss:Type="String"></Data></Cell>';

        echo '
   <Row>
    <Cell ss:StyleID="s65"><Data ss:Type="Number">' . htmlspecialchars($row['nro_sucursal']) . '</Data></Cell>
    <Cell ss:StyleID="s65"><Data ss:Type="String">' . htmlspecialchars($row['DESC_SUCURSAL'] ?? '') . '</Data></Cell>
    ' . $celda_fecha . '
    <Cell ss:StyleID="s65"><Data ss:Type="String">' . htmlspecialchars($row['T_COMP']) . '</Data></Cell>
    <Cell ss:StyleID="s65"><Data ss:Type="String">' . htmlspecialchars($row['N_COMP']) . '</Data></Cell>
    <Cell ss:StyleID="s66"><Data ss:Type="Number">' . number_format($importe, 2, '.', '') . '</Data></Cell>
   </Row>';

        if ($cantidad % 500 === 0) {
            flush();
        }
    }

    sqlsrv_free_stmt($stmt_detalle);
    sqlsrv_close($conexion);

    echo '
   <Row>
    <Cell ss:MergeAcross="4" ss:StyleID="s67"><Data ss:Type="String">TOTAL (' . $cantidad . ' comprobantes)</Data></Cell>
    <Cell ss:StyleID="s68"><Data ss:Type="Number">' . number_format($totalImporte, 2, '.', '') . '</Data></Cell>
   </Row>
  </Table>
 </Worksheet>
</Workbook>';

    exit;

} catch (Exception $e) {
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
            <h3>Error al generar el detalle de comprobantes</h3>
            <p>' . htmlspecialchars($e->getMessage()) . '</p>
            <p><a href="javascript:history.back()">Volver</a></p>
        </div>
    </body>
    </html>';
    exit;
}
?>
