<?php
/**
 * exportarReporteFecha.php
 * Exporta el reporte a fecha a formato Excel
 */

session_start();

try {
    // Validar parámetros
    if (!isset($_POST['fechaDesde']) || !isset($_POST['fechaHasta']) || !isset($_POST['datos'])) {
        throw new Exception('Faltan parámetros obligatorios');
    }

    $fechaDesde = $_POST['fechaDesde'];
    $fechaHasta = $_POST['fechaHasta'];
    $datosJson = $_POST['datos'];
    
    // Decodificar datos JSON
    $datos = json_decode($datosJson, true);
    
    if (!$datos) {
        throw new Exception('Error al decodificar los datos');
    }
    
    $sucursales = $datos['sucursales'];
    $conceptos = $datos['conceptos'];
    $porcentajesAnteriores = $datos['porcentajesAnteriores'] ?? [];
    $entorno = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
    $pais = ($entorno === 'uy') ? 'Uruguay' : 'Argentina';
    
    // Preparar el nombre del archivo
    $filename = 'Reporte_Costo_Ocupacion_' . date('Ymd', strtotime($fechaDesde)) . '_' . date('Ymd', strtotime($fechaHasta)) . '.xls';
    
    // Configurar headers para la descarga
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
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
  <Style ss:ID="HeaderTitle">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Font ss:FontName="Calibri" ss:Size="16" ss:Color="#000000" ss:Bold="1"/>
  </Style>
  <Style ss:ID="HeaderInfo">
   <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>
   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#000000" ss:Bold="1"/>
  </Style>
  <Style ss:ID="ColumnHeader">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2"/>
   </Borders>
   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#FFFFFF" ss:Bold="1"/>
   <Interior ss:Color="#3498db" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="RowHeaderFixed">
   <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
   </Borders>
   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#000000" ss:Bold="1"/>
   <Interior ss:Color="#ecf0f1" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="DataCell">
   <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
   </Borders>
   <NumberFormat ss:Format="#,##0"/>
  </Style>
  <Style ss:ID="DataCellPercent">
   <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
   </Borders>
   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#000000" ss:Bold="1"/>
   <NumberFormat ss:Format="0.00"/>
  </Style>
  <Style ss:ID="SubtotalRow">
   <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
   </Borders>
   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#000000" ss:Bold="1"/>
   <Interior ss:Color="#d5dbdb" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="SubtotalCell">
   <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
   </Borders>
   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#000000" ss:Bold="1"/>
   <Interior ss:Color="#d5dbdb" ss:Pattern="Solid"/>
   <NumberFormat ss:Format="#,##0"/>
  </Style>
  <Style ss:ID="PercentageRow">
   <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2"/>
   </Borders>
   <Font ss:FontName="Calibri" ss:Size="12" ss:Color="#e65100" ss:Bold="1"/>
   <Interior ss:Color="#fff3e0" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="PercentageCell">
   <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2"/>
   </Borders>
   <Font ss:FontName="Calibri" ss:Size="12" ss:Color="#e65100" ss:Bold="1"/>
   <Interior ss:Color="#fff3e0" ss:Pattern="Solid"/>
   <NumberFormat ss:Format="0.00&quot;%&quot;"/>
  </Style>
  <Style ss:ID="YoYAnteriorRow">
   <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2"/>
   </Borders>
   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#000000" ss:Bold="1"/>
   <Interior ss:Color="#e3f2fd" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="YoYAnteriorCell">
   <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2"/>
   </Borders>
   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#000000" ss:Bold="1"/>
   <Interior ss:Color="#e3f2fd" ss:Pattern="Solid"/>
   <NumberFormat ss:Format="0.00&quot;%&quot;"/>
  </Style>
  <Style ss:ID="YoYVariacionRow">
   <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
   </Borders>
   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#000000" ss:Bold="1"/>
   <Interior ss:Color="#fff9c4" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="YoYVariacionCell">
   <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
   </Borders>
   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#000000" ss:Bold="1"/>
   <Interior ss:Color="#fff9c4" ss:Pattern="Solid"/>
   <NumberFormat ss:Format="0.00&quot;%&quot;"/>
  </Style>
 </Styles>
 <Worksheet ss:Name="Reporte Costo Ocupación">
  <Table>';
  
    // Calcular número de columnas (1 fija + sucursales)
    $numColumnas = 1 + count($sucursales);
    
    echo '
   <Column ss:Width="250"/>'; // Columna de conceptos
    
    // Columnas de sucursales
    foreach ($sucursales as $suc) {
        echo '
   <Column ss:Width="120"/>';
    }
    
    // ==== FILA 1: Título ====
    echo '
   <Row ss:Height="25">
    <Cell ss:MergeAcross="' . ($numColumnas - 1) . '" ss:StyleID="HeaderTitle">
     <Data ss:Type="String">REPORTE COSTO DE OCUPACIÓN - ' . strtoupper($pais) . '</Data>
    </Cell>
   </Row>';
    
    // ==== FILA 2: Período ====
    echo '
   <Row ss:Height="20">
    <Cell ss:MergeAcross="' . ($numColumnas - 1) . '" ss:StyleID="HeaderInfo">
     <Data ss:Type="String">Período: ' . date('d/m/Y', strtotime($fechaDesde)) . ' - ' . date('d/m/Y', strtotime($fechaHasta)) . '</Data>
    </Cell>
   </Row>';
    
    // ==== FILA 3: Fecha de generación ====
    echo '
   <Row ss:Height="20">
    <Cell ss:MergeAcross="' . ($numColumnas - 1) . '" ss:StyleID="HeaderInfo">
     <Data ss:Type="String">Generado: ' . date('d/m/Y H:i:s') . '</Data>
    </Cell>
   </Row>';
    
    // ==== FILA 4: Vacía ====
    echo '
   <Row ss:Height="15"/>';
    
    // ==== FILA 5: Headers de columnas ====
    echo '
   <Row ss:Height="40">
    <Cell ss:StyleID="ColumnHeader">
     <Data ss:Type="String">Concepto</Data>
    </Cell>';
    
    foreach ($sucursales as $sucursal) {
        $nombreSucursal = htmlspecialchars($sucursal['numero'] . ' - ' . $sucursal['nombre'], ENT_XML1, 'UTF-8');
        echo '
    <Cell ss:StyleID="ColumnHeader">
     <Data ss:Type="String">' . $nombreSucursal . '</Data>
    </Cell>';
    }
    
    echo '
   </Row>';
    
    // ==== FILAS DE DATOS: Conceptos ====
    foreach ($conceptos as $concepto) {
        $nombreConcepto = htmlspecialchars($concepto['nombre'], ENT_XML1, 'UTF-8');
        
        // Determinar el estilo según el tipo de fila
        $rowStyle = 'RowHeaderFixed';
        $cellStyle = 'DataCell';
        
        if (isset($concepto['is_subtotal']) && $concepto['is_subtotal']) {
            $rowStyle = 'SubtotalRow';
            $cellStyle = 'SubtotalCell';
        } else if (isset($concepto['is_percentage']) && $concepto['is_percentage']) {
            $rowStyle = 'PercentageRow';
            $cellStyle = 'PercentageCell';
        } else if (isset($concepto['is_metric']) && $concepto['is_metric']) {
            $cellStyle = 'DataCell';
        }
        
        echo '
   <Row>
    <Cell ss:StyleID="' . $rowStyle . '">
     <Data ss:Type="String">' . $nombreConcepto . '</Data>
    </Cell>';
        
        foreach ($sucursales as $sucursal) {
            $valor = isset($concepto['valores'][$sucursal['id']]) ? $concepto['valores'][$sucursal['id']] : 0;
            
            // Si es porcentaje, usar formato de porcentaje
            if (isset($concepto['is_percentage']) && $concepto['is_percentage']) {
                echo '
    <Cell ss:StyleID="' . $cellStyle . '">
     <Data ss:Type="Number">' . number_format($valor, 2, '.', '') . '</Data>
    </Cell>';
            } else {
                echo '
    <Cell ss:StyleID="' . $cellStyle . '">
     <Data ss:Type="Number">' . number_format($valor, 0, '.', '') . '</Data>
    </Cell>';
            }
        }
        
        echo '
   </Row>';
    }
    
    // ==== FILA YoY: Período Anterior ====
    echo '
   <Row>
    <Cell ss:StyleID="YoYAnteriorRow">
     <Data ss:Type="String">% Costo de Ocupación (Período Anterior YoY)</Data>
    </Cell>';
    
    foreach ($sucursales as $sucursal) {
        $valorAnterior = isset($porcentajesAnteriores[$sucursal['id']]) ? $porcentajesAnteriores[$sucursal['id']] : null;
        
        if ($valorAnterior !== null) {
            echo '
    <Cell ss:StyleID="YoYAnteriorCell">
     <Data ss:Type="Number">' . number_format($valorAnterior, 2, '.', '') . '</Data>
    </Cell>';
        } else {
            echo '
    <Cell ss:StyleID="YoYAnteriorCell">
     <Data ss:Type="String">-</Data>
    </Cell>';
        }
    }
    
    echo '
   </Row>';
    
    // ==== FILA YoY: Variación Relativa ====
    echo '
   <Row>
    <Cell ss:StyleID="YoYVariacionRow">
     <Data ss:Type="String">Variación Relativa (%)</Data>
    </Cell>';
    
    // Buscar % Costo de Ocupación actual
    $porcentajesActuales = [];
    foreach ($conceptos as $concepto) {
        if (isset($concepto['is_percentage']) && $concepto['is_percentage'] && 
            stripos($concepto['nombre'], 'costo') !== false) {
            $porcentajesActuales = $concepto['valores'];
            break;
        }
    }
    
    foreach ($sucursales as $sucursal) {
        $porcentajeActual = isset($porcentajesActuales[$sucursal['id']]) ? $porcentajesActuales[$sucursal['id']] : null;
        $porcentajeAnterior = isset($porcentajesAnteriores[$sucursal['id']]) ? $porcentajesAnteriores[$sucursal['id']] : null;
        
        if ($porcentajeActual !== null && $porcentajeAnterior !== null && $porcentajeAnterior != 0) {
            $variacionRelativa = (($porcentajeActual - $porcentajeAnterior) / $porcentajeAnterior) * 100;
            echo '
    <Cell ss:StyleID="YoYVariacionCell">
     <Data ss:Type="Number">' . number_format($variacionRelativa, 2, '.', '') . '</Data>
    </Cell>';
        } else {
            echo '
    <Cell ss:StyleID="YoYVariacionCell">
     <Data ss:Type="String">-</Data>
    </Cell>';
        }
    }
    
    echo '
   </Row>';
    
    echo '
  </Table>
 </Worksheet>
</Workbook>';

} catch (Exception $e) {
    // Si hay un error, enviar mensaje de error
    header('Content-Type: text/html; charset=utf-8');
    echo '<html><body>';
    echo '<h2>Error al exportar</h2>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><a href="javascript:window.close()">Cerrar</a></p>';
    echo '</body></html>';
}
