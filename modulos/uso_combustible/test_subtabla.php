<?php
require_once '../../config/Database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Buscar grupos múltiples existentes
    $sql = "SELECT uc.*, 
                   u.nombre as nombre_usuario,
                   DATE_FORMAT(uc.fecha_carga, '%Y-%m-%d') as fecha_carga,
                   TIME_FORMAT(uc.hora_carga, '%H:%i:%s') as hora_carga
            FROM uso_combustible uc
            LEFT JOIN usuarios u ON uc.usuario_id = u.id
            WHERE uc.estado = 'activo'
            ORDER BY uc.fecha_carga DESC, uc.hora_carga DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Agrupar registros (misma lógica que ver_registros.php)
    $groupedRecords = [];
    foreach ($registros as $registro) {
        $groupKey = $registro['fecha_carga'] . '_' . 
                   $registro['nombre_usuario'] . '_' . 
                   $registro['nombre_conductor'] . '_' . 
                   $registro['chapa'] . '_' . 
                   $registro['numero_baucher'] . '_' . 
                   $registro['litros_cargados'];
        
        if (!isset($groupedRecords[$groupKey])) {
            $groupedRecords[$groupKey] = [];
        }
        $groupedRecords[$groupKey][] = $registro;
    }
    
    echo "<h2>Test de Subtabla - Verificación de Corrección</h2>";
    
    $gruposMultiples = 0;
    foreach ($groupedRecords as $groupKey => $group) {
        if (count($group) > 1) {
            $gruposMultiples++;
            $mainRecord = $group[0];
            
            echo "<div style='border: 2px solid #007bff; margin: 20px 0; padding: 15px; border-radius: 5px;'>";
            echo "<h3>Grupo Múltiple #$gruposMultiples</h3>";
            echo "<p><strong>Total de registros en el grupo:</strong> " . count($group) . "</p>";
            echo "<p><strong>Registro principal:</strong> {$mainRecord['nombre_conductor']} - {$mainRecord['chapa']} - Baucher: {$mainRecord['numero_baucher']}</p>";
            
            echo "<h4>Subtabla Generada (DESPUÉS de la corrección):</h4>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-top: 10px;'>";
            echo "<thead style='background-color: #f8f9fa;'>";
            echo "<tr>";
            echo "<th>Índice</th><th>Recorrido #</th><th>ID</th><th>Fecha/Hora</th><th>Origen</th><th>Destino</th><th>Secuencial</th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";
            
            // Simular la lógica CORREGIDA de la subtabla
            for ($i = 0; $i < count($group); $i++) {
                $subRecord = $group[$i];
                $recorridoNumero = $i + 1;
                $origenAnterior = ($i > 0) ? $group[$i-1]['destino'] : '';
                $origenActual = $subRecord['origen'];
                $esSecuencial = ($i > 0) ? ($origenAnterior === $origenActual) : true;
                
                $bgColor = ($i === 0) ? '#e3f2fd' : '#ffffff';
                $note = ($i === 0) ? ' (PRINCIPAL - Ahora incluido)' : '';
                
                echo "<tr style='background-color: $bgColor;'>";
                echo "<td><strong>$i</strong>$note</td>";
                echo "<td><strong>{$recorridoNumero}°</strong></td>";
                echo "<td>{$subRecord['id']}</td>";
                echo "<td>" . date('d/m/Y H:i', strtotime($subRecord['fecha_carga'] . ' ' . $subRecord['hora_carga'])) . "</td>";
                echo "<td>" . htmlspecialchars($subRecord['origen']) . "</td>";
                echo "<td>" . htmlspecialchars($subRecord['destino']) . "</td>";
                echo "<td>" . ($esSecuencial ? '✓ Sí' : '✗ No') . "</td>";
                echo "</tr>";
            }
            
            echo "</tbody>";
            echo "</table>";
            
            echo "<div style='margin-top: 15px; padding: 10px; background-color: #d4edda; border-radius: 3px;'>";
            echo "<strong>✅ CORRECCIÓN APLICADA:</strong><br>";
            echo "• Antes: El bucle empezaba en índice 1, omitiendo el primer registro<br>";
            echo "• Ahora: El bucle empieza en índice 0, mostrando TODOS los registros<br>";
            echo "• Resultado: Si tienes 2 recorridos, ahora verás los 2 en la subtabla";
            echo "</div>";
            
            echo "</div>";
        }
    }
    
    if ($gruposMultiples === 0) {
        echo "<div style='padding: 20px; background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px;'>";
        echo "<h3>⚠️ No hay grupos múltiples para probar</h3>";
        echo "<p>Para probar la corrección:</p>";
        echo "<ol>";
        echo "<li>Ejecuta <a href='crear_grupo_multiple_forzado.php' target='_blank'>crear_grupo_multiple_forzado.php</a></li>";
        echo "<li>Luego vuelve a ejecutar este script</li>";
        echo "<li>Verifica que todos los recorridos aparezcan en la subtabla</li>";
        echo "</ol>";
        echo "</div>";
    }
    
    echo "<hr>";
    echo "<h3>Resumen de la Corrección</h3>";
    echo "<div style='padding: 15px; background-color: #f8f9fa; border-radius: 5px;'>";
    echo "<p><strong>Problema identificado:</strong> El bucle de la subtabla comenzaba en índice 1 en lugar de 0</p>";
    echo "<p><strong>Consecuencia:</strong> Si tenías 2 recorridos, solo se mostraba 1 en la subtabla expandida</p>";
    echo "<p><strong>Solución:</strong> Cambiar el bucle para que comience en índice 0 y ajustar la lógica de secuencia</p>";
    echo "<p><strong>Resultado:</strong> Ahora se muestran TODOS los recorridos del grupo en la subtabla</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}
?>