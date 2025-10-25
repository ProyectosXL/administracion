<?php
require_once "../Class/Alquiler.php";
require_once "../Class/Periodo.php";
require_once "../Controller/AlquilerController.php";

$alquiler = new Alquiler();
$periodoClass = new Periodo();

$mes = isset($_GET['mes']) ? $_GET['mes'] : date('m',  strtotime( date("Y-m-d")));
$anio = isset($_GET['anio']) ? $_GET['anio'] : date('Y',  strtotime( date("Y-m-d")));
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : (int)$mes."-".$anio;

$fechaParaMostrar = $mes."/".$anio;
$fecha = $anio."-".$mes;

$todosLosLocales = traerLocales();
$conceptos = traerConceptos();
$newArray = traerDetalleAlquiler($fecha, $periodo);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$entorno = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
$nombrePais = ($entorno === 'central') ? 'Argentina' : 'Uruguay';

// FUNCIONALIDAD DE SUCURSALES OCULTAS DESHABILITADA
$sucursalesOcultasArray = [];

$htmlContent = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Alquileres - '.$fechaParaMostrar.'</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 8px;
            margin: 10px;
        }
        h1 {
            text-align: center;
            font-size: 14px;
            margin-bottom: 5px;
        }
        h2 {
            text-align: center;
            font-size: 11px;
            color: #666;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 7px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 4px 2px;
            text-align: center;
        }
        th {
            background-color: #343a40;
            color: white;
            font-weight: bold;
            font-size: 7px;
        }
        tbody tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        tbody tr:last-child {
            background-color: #e9ecef;
            font-weight: bold;
        }
        td:nth-child(1) {
            text-align: center;
            width: 25px;
        }
        td:nth-child(2) {
            text-align: left;
            max-width: 120px;
            font-size: 7px;
        }
        .footer {
            margin-top: 15px;
            text-align: center;
            font-size: 7px;
            color: #666;
        }
    </style>
            text-align: center;
            font-size: 8px;
            color: #666;
        }
    </style>
</head>
<body>
    <h1>Carga de Gastos de Alquiler</h1>
    <h2>'.$fechaParaMostrar.' - '.$nombrePais.'</h2>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>CONCEPTOS</th>';

foreach ($todosLosLocales as $value) {   
    if (in_array($value['NRO_SUCURSAL'], $sucursalesOcultasArray)) {
        continue;
    } 
    $htmlContent .= '<th>'.$value['NRO_SUCURSAL'].'</th>';
}

$htmlContent .= '
            </tr>
        </thead>
        <tbody>';

foreach ($conceptos as $concepto) {    
    $htmlContent .= '<tr>';
    $htmlContent .= '<td>'.$concepto['ID_CA'].'</td>';
    $htmlContent .= '<td><strong>'.$concepto['CONCEPTO'].'</strong></td>';
    
    foreach ($newArray as $k => $val) {
        if (in_array($k, $sucursalesOcultasArray)) {
            continue;
        }
        
        $valor = $val[$concepto['CONCEPTO']];
        $signo = ($valor < 0) ? "-" : "";
        $valor = abs($valor);
        
        $htmlContent .= '<td>'.$signo.'$'.number_format($valor, 0, ',', '.').'</td>';
    }
    
    $htmlContent .= '</tr>';
}

// Fila de totales
$htmlContent .= '<tr>';
$htmlContent .= '<td></td>';
$htmlContent .= '<td><strong>Total</strong></td>';

foreach ($todosLosLocales as $local) {
    if (in_array($local['NRO_SUCURSAL'], $sucursalesOcultasArray)) {
        continue;
    }
    
    $total = 0;
    foreach ($conceptos as $concepto) {
        if (isset($newArray[$local['NRO_SUCURSAL']][$concepto['CONCEPTO']])) {
            $total += $newArray[$local['NRO_SUCURSAL']][$concepto['CONCEPTO']];
        }
    }
    
    $htmlContent .= '<td>$'.number_format($total, 0, ',', '.').'</td>';
}

$htmlContent .= '</tr>';

$htmlContent .= '
        </tbody>
    </table>
    
    <div class="footer">
        Generado el '.date('d/m/Y H:i:s').'
    </div>
</body>
</html>';

require '../../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($htmlContent);

// Setup the paper size and orientation
$dompdf->setPaper('A4', 'landscape');

// Render the HTML as PDF
$dompdf->render();

// Output the generated PDF to Browser
$dompdf->stream("Alquileres_".$fechaParaMostrar.".pdf", array("Attachment" => true));
?>
