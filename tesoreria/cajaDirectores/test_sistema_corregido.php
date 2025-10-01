<?php
/**
 * Script de prueba para verificar el sistema corregido de múltiples orígenes
 * NUEVA LÓGICA:
 * - Fuente 599: Viene RECIBIDA por defecto, sin checkbox, no se duplica
 * - Fuente TESORERÍA: Viene PENDIENTE, con checkbox, se inserta al marcar
 * - Fuente MANUAL: Como siempre (pendiente, con checkbox)
 */

require_once __DIR__ . '/../../class/classEnv.php';
require_once __DIR__ . '/Class/Database.php';
require_once __DIR__ . '/Class/Ingreso.php';

try {
    $ingreso = new Ingreso();
    $database = Database::getInstance();
    
    echo "<style>
        .alert { padding: 15px; margin: 10px 0; border-radius: 5px; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert-danger { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .alert-warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        .alert-info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        .table th, .table td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        .table th { background: #f8f9fa; font-weight: bold; }
        .badge { padding: 4px 8px; border-radius: 3px; color: white; font-size: 12px; font-weight: bold; }
        .bg-primary { background: #007bff; }
        .bg-info { background: #17a2b8; }
        .bg-secondary { background: #6c757d; }
        .bg-success { background: #28a745; }
        .bg-warning { background: #ffc107; color: #212529; }
        .bg-danger { background: #dc3545; }
        .row { display: flex; }
        .col-md-6 { flex: 1; margin: 0 5px; }
    </style>";

    echo "<h1>🔧 Test de Sistema Corregido - Múltiples Orígenes</h1>";
    
    // Test de conexiones
    echo "<h2>1. Verificación de Conexiones</h2>";
    
    try {
        $appsConn = $database->getAppsConnection();
        echo "<div class='alert alert-success'>✅ <strong>Base APPS:</strong> Conexión exitosa<br></div>";
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>❌ <strong>Base APPS:</strong> Error: " . $e->getMessage() . "<br></div>";
    }
    
    try {
        $centralConn = $database->getCentralConnection();
        echo "<div class='alert alert-success'>✅ <strong>Base CENTRAL:</strong> Conexión exitosa<br></div>";
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>❌ <strong>Base CENTRAL:</strong> Error: " . $e->getMessage() . "<br></div>";
    }
    
    // Período de prueba
    $desde = '2025-01-01';
    $hasta = '2025-12-31';
    
    echo "<h2>2. Test de Nueva Lógica de Estados</h2>";
    echo "<div class='alert alert-info'><strong>Período de prueba:</strong> {$desde} a {$hasta}<br></div>";
    
    // Test individual de cada fuente
    echo "<h3>2.1. Ingresos MANUALES (Estado: PENDIENTE por defecto)</h3>";
    $filtros = ['fecha_desde' => $desde, 'fecha_hasta' => $hasta];
    $manuales = $ingreso->obtenerTodos($filtros);
    echo "<div class='alert alert-success'>✅ <strong>MANUAL:</strong> " . count($manuales) . " registros<br></div>";
    
    echo "<h3>2.2. Ingresos 599 (Estado: RECIBIDO por defecto, sin duplicados)</h3>";
    $ingresos599 = $ingreso->obtenerIngresos599($desde, $hasta);
    echo "<div class='alert alert-success'>✅ <strong>599:</strong> " . count($ingresos599) . " registros<br>";
    if (count($ingresos599) > 0) {
        $primerIngreso599 = $ingresos599[0];
        $estadoMostrado = $primerIngreso599['recibido'] == 1 ? 'RECIBIDO' : 'PENDIENTE';
        echo "• Estado del primer registro: <span class='badge bg-success'>{$estadoMostrado}</span><br>";
    }
    echo "</div>";
    
    echo "<h3>2.3. Ingresos TESORERÍA (Estado: PENDIENTE por defecto, con checkbox)</h3>";
    $ingresosTesoreria = $ingreso->obtenerIngresosTesoreria($desde, $hasta);
    echo "<div class='alert alert-success'>✅ <strong>TESORERÍA:</strong> " . count($ingresosTesoreria) . " registros<br>";
    if (count($ingresosTesoreria) > 0) {
        $primerIngresoTes = $ingresosTesoreria[0];
        $estadoMostrado = $primerIngresoTes['recibido'] == 1 ? 'RECIBIDO' : 'PENDIENTE';
        echo "• Estado del primer registro: <span class='badge bg-warning'>{$estadoMostrado}</span><br>";
    }
    echo "</div>";
    
    // Test datos combinados
    echo "<h2>3. Test de Datos Combinados (Sin Duplicados)</h2>";
    $combinados = $ingreso->obtenerIngresosCombinados($desde, $hasta);
    echo "<div class='alert alert-success'>✅ <strong>Total Combinado:</strong> " . count($combinados) . " registros<br></div>";
    
    // Análisis por origen
    $contadorPorOrigen = [];
    $pendientesPorOrigen = [];
    $recibidosPorOrigen = [];
    
    foreach ($combinados as $item) {
        $origen = $item['origen'];
        
        if (!isset($contadorPorOrigen[$origen])) {
            $contadorPorOrigen[$origen] = 0;
            $pendientesPorOrigen[$origen] = 0;
            $recibidosPorOrigen[$origen] = 0;
        }
        
        $contadorPorOrigen[$origen]++;
        
        if ($item['recibido'] == 1) {
            $recibidosPorOrigen[$origen]++;
        } else {
            $pendientesPorOrigen[$origen]++;
        }
    }
    
    echo "<div class='table-responsive'>";
    echo "<table class='table table-striped'>";
    echo "<thead><tr><th>Origen</th><th>Total</th><th>Recibidos</th><th>Pendientes</th><th>Estado Esperado</th><th>Acción Requerida</th></tr></thead>";
    echo "<tbody>";
    
    foreach (['MANUAL', '599', 'TESORERIA'] as $origen) {
        $total = $contadorPorOrigen[$origen] ?? 0;
        $recibidos = $recibidosPorOrigen[$origen] ?? 0;
        $pendientes = $pendientesPorOrigen[$origen] ?? 0;
        
        if ($origen == 'MANUAL') {
            $estadoEsperado = '<span class="badge bg-warning">PENDIENTE</span>';
            $accionRequerida = '✅ Checkbox para marcar';
        } elseif ($origen == '599') {
            $estadoEsperado = '<span class="badge bg-success">RECIBIDO</span>';
            $accionRequerida = '❌ Sin checkbox (ya recibido)';
        } else { // TESORERIA
            $estadoEsperado = '<span class="badge bg-warning">PENDIENTE</span>';
            $accionRequerida = '✅ Checkbox para marcar';
        }
        
        echo "<tr>";
        echo "<td><span class='badge bg-info'>{$origen}</span></td>";
        echo "<td>{$total}</td>";
        echo "<td><span class='badge bg-success'>{$recibidos}</span></td>";
        echo "<td><span class='badge bg-warning'>{$pendientes}</span></td>";
        echo "<td>{$estadoEsperado}</td>";
        echo "<td>{$accionRequerida}</td>";
        echo "</tr>";
    }
    
    echo "</tbody></table></div>";
    
    // Verificación de duplicados
    echo "<h2>4. Verificación de Duplicados</h2>";
    
    $idsUnicos = [];
    $duplicados = [];
    
    foreach ($combinados as $item) {
        $claveUnica = '';
        
        if ($item['origen'] == '599' && !empty($item['ID_SBA05'])) {
            $claveUnica = '599_' . $item['ID_SBA05'];
        } elseif ($item['origen'] == 'TESORERIA' && !empty($item['ID_TESORERIA'])) {
            $claveUnica = 'TES_' . $item['ID_TESORERIA'];
        } elseif ($item['origen'] == 'MANUAL') {
            $idManual = str_replace('MAN_', '', $item['id']);
            $claveUnica = 'MAN_' . $idManual;
        }
        
        if (!empty($claveUnica)) {
            if (in_array($claveUnica, $idsUnicos)) {
                $duplicados[] = $claveUnica;
            } else {
                $idsUnicos[] = $claveUnica;
            }
        }
    }
    
    if (empty($duplicados)) {
        echo "<div class='alert alert-success'>✅ <strong>Sin Duplicados:</strong> Todos los registros son únicos<br></div>";
    } else {
        echo "<div class='alert alert-danger'>❌ <strong>Duplicados Encontrados:</strong> " . implode(', ', $duplicados) . "<br></div>";
    }
    
    // Muestra de datos
    echo "<h2>5. Muestra de Datos por Origen</h2>";
    echo "<div class='table-responsive'>";
    echo "<table class='table table-sm'>";
    echo "<thead><tr><th>Fecha</th><th>Origen</th><th>Concepto</th><th>Importe</th><th>Estado</th><th>ID Externo</th></tr></thead>";
    echo "<tbody>";
    
    $contador = 0;
    foreach ($combinados as $item) {
        if ($contador >= 10) break; // Mostrar solo los primeros 10
        
        $fecha = is_object($item['fecha']) ? $item['fecha']->format('Y-m-d') : $item['fecha'];
        $origen = $item['origen'];
        $concepto = substr($item['observaciones'], 0, 30) . '...';
        $importe = '$' . number_format($item['importe'], 0, ',', '.');
        $estado = $item['recibido'] == 1 ? 
            '<span class="badge bg-success">RECIBIDO</span>' : 
            '<span class="badge bg-warning">PENDIENTE</span>';
        
        $idExterno = '';
        if ($origen == '599' && !empty($item['ID_SBA05'])) {
            $idExterno = 'SBA05: ' . $item['ID_SBA05'];
        } elseif ($origen == 'TESORERIA' && !empty($item['ID_TESORERIA'])) {
            $idExterno = 'TES: ' . $item['ID_TESORERIA'];
        }
        
        $origenBadge = '';
        if ($origen == 'MANUAL') $origenBadge = '<span class="badge bg-primary">MANUAL</span>';
        elseif ($origen == '599') $origenBadge = '<span class="badge bg-info">599</span>';
        elseif ($origen == 'TESORERIA') $origenBadge = '<span class="badge bg-secondary">TESORERÍA</span>';
        
        echo "<tr>";
        echo "<td>{$fecha}</td>";
        echo "<td>{$origenBadge}</td>";
        echo "<td>{$concepto}</td>";
        echo "<td>{$importe}</td>";
        echo "<td>{$estado}</td>";
        echo "<td><small>{$idExterno}</small></td>";
        echo "</tr>";
        
        $contador++;
    }
    
    echo "</tbody></table></div>";
    
    // Resumen final
    echo "<h2>✅ Resumen de Correcciones Implementadas</h2>";
    echo "<div class='alert alert-info'>";
    echo "<strong>Cambios Realizados:</strong><br>";
    echo "✅ Fuente 599: Ahora viene RECIBIDA por defecto (sin checkbox)<br>";
    echo "✅ Fuente TESORERÍA: Ahora viene PENDIENTE (con checkbox)<br>";
    echo "✅ Eliminación de duplicados: Registros ya insertados no se muestran<br>";
    echo "✅ Campo ID_TESORERIA: Agregado para seguimiento<br>";
    echo "✅ Lógica diferenciada: Cada origen tiene su comportamiento específico<br>";
    echo "✅ Controllers actualizados: Soporte para marcar TESORERÍA como recibido<br>";
    echo "✅ JavaScript corregido: Botones condicionales según origen<br>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ <strong>Error:</strong> " . $e->getMessage() . "</div>";
}
?>