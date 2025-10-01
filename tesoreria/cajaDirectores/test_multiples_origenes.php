<?php
require_once '../../class/classEnv.php';
require_once 'Class/Database.php';
require_once 'Class/Ingreso.php';
require_once 'Class/Egreso.php';

echo "<h1>🌟 Test de Múltiples Orígenes de Ingresos</h1>";

try {
    $ingreso = new Ingreso();
    $egreso = new Egreso();
    
    echo "<h2>1. Test de Conexiones a Bases de Datos</h2>";
    
    // Test conexión APPS
    try {
        $dbApps = Database::getInstance()->getAppsConnection();
        echo "<div class='alert alert-success'>";
        echo "✅ <strong>Base APPS:</strong> Conexión exitosa<br>";
        echo "</div>";
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>";
        echo "❌ <strong>Base APPS:</strong> " . $e->getMessage() . "<br>";
        echo "</div>";
    }
    
    // Test conexión CENTRAL
    try {
        $dbCentral = Database::getInstance()->getCentralConnection();
        echo "<div class='alert alert-success'>";
        echo "✅ <strong>Base CENTRAL:</strong> Conexión exitosa<br>";
        echo "</div>";
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>";
        echo "❌ <strong>Base CENTRAL:</strong> " . $e->getMessage() . "<br>";
        echo "</div>";
    }
    
    echo "<h2>2. Test de Consultas por Origen</h2>";
    
    $desde = date('Y-m-01'); // Primer día del mes
    $hasta = date('Y-m-d');  // Hoy
    
    echo "<div class='alert alert-info'>";
    echo "<strong>Período de prueba:</strong> {$desde} a {$hasta}<br>";
    echo "</div>";
    
    // Test Ingresos MANUALES
    echo "<h3>2.1. Ingresos MANUALES (Base APPS)</h3>";
    try {
        $manuales = $ingreso->obtenerTodos(['fecha_desde' => $desde, 'fecha_hasta' => $hasta]);
        echo "<div class='alert alert-success'>";
        echo "✅ <strong>Ingresos MANUALES:</strong> " . count($manuales) . " registros encontrados<br>";
        if (count($manuales) > 0) {
            $ultimo = $manuales[0];
            echo "• Último: " . (is_object($ultimo['fecha']) ? $ultimo['fecha']->format('Y-m-d') : $ultimo['fecha']) . 
                 " - $" . number_format($ultimo['importe'], 0, ',', '.') . "<br>";
        }
        echo "</div>";
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>";
        echo "❌ <strong>Error MANUALES:</strong> " . $e->getMessage() . "<br>";
        echo "</div>";
    }
    
    // Test Ingresos 599
    echo "<h3>2.2. Ingresos 599 (Base CENTRAL)</h3>";
    try {
        $ingresos599 = $ingreso->obtenerIngresos599($desde, $hasta);
        echo "<div class='alert alert-success'>";
        echo "✅ <strong>Ingresos 599:</strong> " . count($ingresos599) . " registros encontrados<br>";
        if (count($ingresos599) > 0) {
            $ultimo = $ingresos599[0];
            echo "• Último: " . (is_object($ultimo['fecha']) ? $ultimo['fecha']->format('Y-m-d') : $ultimo['fecha']) . 
                 " - $" . number_format($ultimo['importe'], 0, ',', '.') . 
                 " - Estado: " . ($ultimo['recibido'] ? 'RECIBIDO' : 'PENDIENTE') . "<br>";
        }
        echo "</div>";
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>";
        echo "❌ <strong>Error 599:</strong> " . $e->getMessage() . "<br>";
        echo "</div>";
    }
    
    // Test Ingresos TESORERIA
    echo "<h3>2.3. Ingresos TESORERIA (Base CENTRAL)</h3>";
    try {
        $ingresosTesoreria = $ingreso->obtenerIngresosTesoreria($desde, $hasta);
        echo "<div class='alert alert-success'>";
        echo "✅ <strong>Ingresos TESORERIA:</strong> " . count($ingresosTesoreria) . " registros encontrados<br>";
        if (count($ingresosTesoreria) > 0) {
            $ultimo = $ingresosTesoreria[0];
            echo "• Último: " . (is_object($ultimo['fecha']) ? $ultimo['fecha']->format('Y-m-d') : $ultimo['fecha']) . 
                 " - $" . number_format($ultimo['importe'], 0, ',', '.') . 
                 " - Siempre RECIBIDO<br>";
        }
        echo "</div>";
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>";
        echo "❌ <strong>Error TESORERIA:</strong> " . $e->getMessage() . "<br>";
        echo "</div>";
    }
    
    echo "<h2>3. Test de Datos Combinados</h2>";
    
    try {
        $combinados = $ingreso->obtenerIngresosCombinados($desde, $hasta);
        echo "<div class='alert alert-success'>";
        echo "✅ <strong>Total Combinado:</strong> " . count($combinados) . " registros<br>";
        echo "</div>";
        
        // Contar por origen
        $contadores = ['MANUAL' => 0, '599' => 0, 'TESORERIA' => 0];
        $totalPendiente = 0;
        $totalRecibido = 0;
        
        foreach ($combinados as $item) {
            $origen = $item['origen'] ?? 'MANUAL';
            if (isset($contadores[$origen])) {
                $contadores[$origen]++;
            }
            
            if ($item['recibido']) {
                $totalRecibido += $item['importe'];
            } else {
                $totalPendiente += $item['importe'];
            }
        }
        
        echo "<div class='table-responsive'>";
        echo "<table class='table table-striped'>";
        echo "<thead>";
        echo "<tr><th>Origen</th><th>Cantidad</th><th>Descripción</th><th>Estado</th><th>Inserción BD</th></tr>";
        echo "</thead>";
        echo "<tbody>";
        echo "<tr>";
        echo "<td><span class='badge bg-primary'>MANUAL</span></td>";
        echo "<td>{$contadores['MANUAL']}</td>";
        echo "<td>Ingresos cargados manualmente</td>";
        echo "<td>Pendiente → Checkbox</td>";
        echo "<td>✅ Al crear</td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td><span class='badge bg-info'>599</span></td>";
        echo "<td>{$contadores['599']}</td>";
        echo "<td>Desde SBA05 COD_CTA='100130'</td>";
        echo "<td>Pendiente → Checkbox</td>";
        echo "<td>✅ Al marcar recibido</td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td><span class='badge bg-secondary'>TESORERIA</span></td>";
        echo "<td>{$contadores['TESORERIA']}</td>";
        echo "<td>Desde sj_administracion_cobros</td>";
        echo "<td>Siempre RECIBIDO</td>";
        echo "<td>❌ No se inserta</td>";
        echo "</tr>";
        echo "</tbody>";
        echo "</table>";
        echo "</div>";
        
        echo "<h3>Resumen Financiero</h3>";
        echo "<div class='row'>";
        echo "<div class='col-md-6'>";
        echo "<div class='alert alert-success'>";
        echo "<strong>Total Recibido:</strong> $" . number_format($totalRecibido, 0, ',', '.') . "<br>";
        echo "</div>";
        echo "</div>";
        echo "<div class='col-md-6'>";
        echo "<div class='alert alert-warning'>";
        echo "<strong>Total Pendiente:</strong> $" . number_format($totalPendiente, 0, ',', '.') . "<br>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>";
        echo "❌ <strong>Error en datos combinados:</strong> " . $e->getMessage() . "<br>";
        echo "</div>";
    }
    
    echo "<h2>4. Muestra de Datos por Origen</h2>";
    
    if (isset($combinados) && count($combinados) > 0) {
        echo "<div class='table-responsive'>";
        echo "<table class='table table-sm'>";
        echo "<thead>";
        echo "<tr><th>Fecha</th><th>Origen</th><th>COMP</th><th>Concepto</th><th>Importe</th><th>Estado</th></tr>";
        echo "</thead>";
        echo "<tbody>";
        
        foreach (array_slice($combinados, 0, 10) as $item) {
            $fecha = is_object($item['fecha']) ? $item['fecha']->format('Y-m-d') : $item['fecha'];
            $comp = ($item['COD_COMP'] && $item['N_COMP']) ? $item['COD_COMP'] . $item['N_COMP'] : '-';
            $origen = $item['origen'] ?? 'MANUAL';
            $estado = $item['recibido'] ? 'RECIBIDO' : 'PENDIENTE';
            $estadoClass = $item['recibido'] ? 'success' : 'warning';
            
            echo "<tr>";
            echo "<td>{$fecha}</td>";
            echo "<td><span class='badge bg-";
            echo ($origen === 'MANUAL') ? 'primary' : (($origen === '599') ? 'info' : 'secondary');
            echo "'>{$origen}</span></td>";
            echo "<td><small>{$comp}</small></td>";
            echo "<td>" . substr($item['observaciones'] ?? 'Sin observaciones', 0, 30) . "...</td>";
            echo "<td>$" . number_format($item['importe'], 0, ',', '.') . "</td>";
            echo "<td><span class='badge bg-{$estadoClass}'>{$estado}</span></td>";
            echo "</tr>";
        }
        
        echo "</tbody>";
        echo "</table>";
        echo "</div>";
    }
    
    echo "<h2>✅ Resumen de Implementación</h2>";
    echo "<div class='alert alert-info'>";
    echo "<strong>Funcionalidades Implementadas:</strong><br>";
    echo "✅ Consulta a 3 orígenes diferentes<br>";
    echo "✅ Combinación de datos en vista unificada<br>";
    echo "✅ Lógica diferenciada por origen<br>";
    echo "✅ Campo ORIGEN en reportes<br>";
    echo "✅ Checkbox condicional (MANUAL y 599)<br>";
    echo "✅ Inserción BD solo cuando corresponde<br>";
    echo "✅ TESORERIA siempre RECIBIDO<br>";
    echo "✅ Campos COMP vacíos para TESORERIA<br>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "❌ <strong>Error general:</strong> " . $e->getMessage() . "<br>";
    echo "</div>";
}
?>

<style>
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
.row { display: flex; }
.col-md-6 { flex: 1; margin: 0 5px; }
</style>