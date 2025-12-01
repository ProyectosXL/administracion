<?php
session_start();
require_once '../../class/conexion.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

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

    // Crear el archivo Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Establecer el título
    $pais = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy') ? 'Uruguay' : 'Argentina';
    $sheet->setTitle('Control Ventas');

    // Agregar encabezado
    $sheet->setCellValue('A1', 'CONTROL DE VENTAS POR SUCURSAL');
    $sheet->mergeCells('A1:H1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Agregar información del período
    $sheet->setCellValue('A2', 'País: ' . $pais);
    $sheet->setCellValue('C2', 'Período: ' . date('d/m/Y', strtotime($desde)) . ' - ' . date('d/m/Y', strtotime($hasta)));
    $sheet->getStyle('A2:C2')->getFont()->setBold(true);

    // Agregar encabezados de columnas
    $row = 4;
    $headers = ['Nro. Sucursal', 'Cod. Sucursal', 'Importe Central', 'Importe Local', 'Diferencia', 'Estado', 'Últ. Actualización'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $row, $header);
        $col++;
    }

    // Estilo de encabezados
    $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '007BFF']
        ],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ]);

    // Agregar datos
    $row++;
    $totalImporteCentral = 0;
    $totalImporteLocal = 0;
    $totalDiferencia = 0;

    foreach ($data_final as $fila) {
        $sheet->setCellValue('A' . $row, $fila['NUM_SUC']);
        $sheet->setCellValue('B' . $row, $fila['COD_SUCURSAL']);
        $sheet->setCellValue('C' . $row, floatval($fila['IMPORTE_CENTRAL']));
        $sheet->setCellValue('D' . $row, floatval($fila['IMPORTE_LOCAL']));
        $sheet->setCellValue('E' . $row, floatval($fila['DIFERENCIA']));
        $sheet->setCellValue('F' . $row, $fila['ESTADO']);
        
        // Formatear la fecha
        $refreshedDate = 'N/A';
        if ($fila['REFRESHED_AT']) {
            $refreshedDate = $fila['REFRESHED_AT']->format('d/m/Y H:i');
        }
        $sheet->setCellValue('G' . $row, $refreshedDate);

        // Acumular totales
        $totalImporteCentral += floatval($fila['IMPORTE_CENTRAL']);
        $totalImporteLocal += floatval($fila['IMPORTE_LOCAL']);
        $totalDiferencia += floatval($fila['DIFERENCIA']);

        // Aplicar formato de moneda a las columnas de importes
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
        $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
        $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');

        // Aplicar bordes
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC']
                ]
            ]
        ]);

        $row++;
    }

    // Agregar fila de totales
    $sheet->setCellValue('A' . $row, 'TOTALES');
    $sheet->mergeCells('A' . $row . ':B' . $row);
    $sheet->setCellValue('C' . $row, $totalImporteCentral);
    $sheet->setCellValue('D' . $row, $totalImporteLocal);
    $sheet->setCellValue('E' . $row, $totalDiferencia);

    // Estilo de la fila de totales
    $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '343A40']
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ]);

    // Formato de moneda para totales
    $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
    $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
    $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');

    // Alinear totales
    $sheet->getStyle('A' . $row . ':B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    // Ajustar anchos de columna
    $sheet->getColumnDimension('A')->setWidth(15);
    $sheet->getColumnDimension('B')->setWidth(15);
    $sheet->getColumnDimension('C')->setWidth(18);
    $sheet->getColumnDimension('D')->setWidth(18);
    $sheet->getColumnDimension('E')->setWidth(18);
    $sheet->getColumnDimension('F')->setWidth(12);
    $sheet->getColumnDimension('G')->setWidth(20);

    // Generar el archivo
    $writer = new Xlsx($spreadsheet);
    
    // Preparar el nombre del archivo
    $filename = 'Control_Ventas_' . date('Ymd', strtotime($desde)) . '_' . date('Ymd', strtotime($hasta)) . '.xlsx';
    
    // Configurar headers para la descarga
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    // Guardar el archivo en el output
    $writer->save('php://output');
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
