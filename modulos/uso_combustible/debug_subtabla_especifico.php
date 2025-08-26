<?php
require_once '../../config/Database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h2>Debug Específico - Subtabla con 3 Recorridos</h2>";
    
    // Buscar el grupo que tiene 3 recorridos
    $stmt = $pdo->prepare("
        SELECT 
            id, fecha_carga, nombre_usuario, nombre_conductor, chapa, 
            numero_baucher, litros_cargados, recorrido_id, origen, destino,
            hora_carga, tipo_vehiculo, tarjeta, documento, foto_voucher_ruta,
            foto_voucher, estado_recorrido, user_id, fecha_cierre, nombre_cerrador
        FROM uso_combustible 
        ORDER BY fecha_carga DESC, recorrido_id ASC
    ");
    
    $stmt->execute();
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Agrupar registros usando la misma lógica que ver_registros.php
    $groupedRecords = [];
    
    foreach ($registros as $registro) {
        $groupKey = $registro['fecha_carga'] . '|' . 
                   $registro['nombre_usuario'] . '|' . 
                   $registro['nombre_conductor'] . '|' . 
                   $registro['chapa'] . '|' . 
                   $registro['numero_baucher'] . '|' . 
                   $registro['litros_cargados'];
        
        if (!isset($groupedRecords[$groupKey])) {
            $groupedRecords[$groupKey] = [];
        }
        $groupedRecords[$groupKey][] = $registro;
    }
    
    // Buscar el grupo con 3 recorridos
    $grupoTresRecorridos = null;
    $claveGrupoTres = null;
    
    foreach ($groupedRecords as $clave => $grupo) {
        if (count($grupo) == 3) {
            $grupoTresRecorridos = $grupo;
            $claveGrupoTres = $clave;
            break;
        }
    }
    
    if ($grupoTresRecorridos) {
        echo "<h3>✅ Grupo con 3 recorridos encontrado</h3>";
        echo "<p><strong>Clave del grupo:</strong> <code>$claveGrupoTres</code></p>";
        echo "<p><strong>Número de registros:</strong> " . count($grupoTresRecorridos) . "</p>";
        
        echo "<h4>Datos del grupo:</h4>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 12px;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>Índice</th><th>ID</th><th>Recorrido ID</th><th>Fecha/Hora</th><th>Origen</th><th>Destino</th><th>Estado</th>";
        echo "</tr>";
        
        foreach ($grupoTresRecorridos as $i => $registro) {
            echo "<tr>";
            echo "<td><strong>$i</strong></td>";
            echo "<td>{$registro['id']}</td>";
            echo "<td>{$registro['recorrido_id']}</td>";
            echo "<td>{$registro['fecha_carga']} {$registro['hora_carga']}</td>";
            echo "<td>{$registro['origen']}</td>";
            echo "<td>{$registro['destino']}</td>";
            echo "<td>{$registro['estado_recorrido']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<hr>";
        echo "<h4>Simulación de la Subtabla</h4>";
        echo "<p>Simulando el bucle: <code>for (\$i = 0; \$i < count(\$group); \$i++)</code></p>";
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #e3f2fd;'>";
        echo "<th>Iteración</th><th>Recorrido #</th><th>ID Registro</th><th>Origen → Destino</th><th>¿Se muestra?</th>";
        echo "</tr>";
        
        for ($i = 0; $i < count($grupoTresRecorridos); $i++) {
            $subRecord = $grupoTresRecorridos[$i];
            $recorridoNumero = $i + 1;
            $origenAnterior = ($i > 0) ? $grupoTresRecorridos[$i-1]['destino'] : '';
            $origenActual = $subRecord['origen'];
            $esSecuencial = ($i > 0) ? ($origenAnterior === $origenActual) : true;
            
            echo "<tr>";
            echo "<td><strong>$i</strong></td>";
            echo "<td>{$recorridoNumero}°</td>";
            echo "<td>{$subRecord['id']}</td>";
            echo "<td>{$subRecord['origen']} → {$subRecord['destino']}</td>";
            echo "<td style='color: green; font-weight: bold;'>SÍ</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<hr>";
        echo "<h4>Verificación de Visibilidad CSS</h4>";
        echo "<p>Verificando si hay problemas de CSS que puedan ocultar las filas...</p>";
        
        echo "<div style='border: 2px solid #007bff; padding: 15px; margin: 10px 0; background: #f8f9fa;'>";
        echo "<h5>Contenedor de Sub-registros (simulado)</h5>";
        echo "<div style='background: white; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px;'>";
        echo "<h6 style='color: #495057; border-bottom: 2px solid #007bff; padding-bottom: 10px;'>";
        echo "<i class='fas fa-route' style='color: #007bff;'></i> Detalles del Recorrido Múltiple";
        echo "</h6>";
        
        echo "<table style='width: 100%; font-size: 0.9em; margin-top: 15px;' border='1'>";
        echo "<thead style='background: #f8f9fa;'>";
        echo "<tr><th>Recorrido</th><th>Origen → Destino</th><th>Estado</th></tr>";
        echo "</thead>";
        echo "<tbody>";
        
        for ($i = 0; $i < count($grupoTresRecorridos); $i++) {
            $subRecord = $grupoTresRecorridos[$i];
            $recorridoNumero = $i + 1;
            
            echo "<tr style='background: white;'>";
            echo "<td>{$recorridoNumero}°</td>";
            echo "<td>{$subRecord['origen']} → {$subRecord['destino']}</td>";
            echo "<td>{$subRecord['estado_recorrido']}</td>";
            echo "</tr>";
        }
        
        echo "</tbody>";
        echo "</table>";
        echo "</div>";
        echo "</div>";
        
        echo "<hr>";
        echo "<h4>Conclusión</h4>";
        echo "<p style='color: green; font-weight: bold;'>✅ El bucle debería mostrar los 3 registros correctamente.</p>";
        echo "<p><strong>Si no se ven en la interfaz, el problema podría ser:</strong></p>";
        echo "<ul>";
        echo "<li>CSS que oculta las filas</li>";
        echo "<li>JavaScript que interfiere</li>";
        echo "<li>Problema con el contenedor padre</li>";
        echo "<li>Error en la consulta SQL original</li>";
        echo "</ul>";
        
    } else {
        echo "<h3>❌ No se encontró ningún grupo con exactamente 3 recorridos</h3>";
        
        echo "<h4>Grupos encontrados:</h4>";
        foreach ($groupedRecords as $clave => $grupo) {
            $count = count($grupo);
            if ($count > 1) {
                echo "<p>• Grupo con <strong>$count registros</strong>: " . substr($clave, 0, 80) . "...</p>";
            }
        }
        
        echo "<p style='color: orange;'>Puede que el grupo de 3 recorridos se haya dividido por diferencias en los campos de agrupación.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>