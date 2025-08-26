<?php
require_once '../../config/Database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Obtener registros como lo hace ver_registros.php
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
    
    echo "<h2>Debug de Agrupación de Registros</h2>";
    echo "<p>Total de registros encontrados: " . count($registros) . "</p>";
    
    // Simular la lógica de agrupación exacta de ver_registros.php
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
        
        echo "<p><strong>Registro ID {$registro['id']}:</strong><br>";
        echo "Clave de grupo: <code>$groupKey</code><br>";
        echo "Componentes: fecha_carga={$registro['fecha_carga']}, usuario={$registro['nombre_usuario']}, conductor={$registro['nombre_conductor']}, chapa={$registro['chapa']}, baucher={$registro['numero_baucher']}, litros={$registro['litros_cargados']}</p>";
    }
    
    echo "<hr>";
    echo "<h3>Resultado de Agrupación</h3>";
    echo "<p>Total de grupos: " . count($groupedRecords) . "</p>";
    
    $multipleGroups = 0;
    foreach ($groupedRecords as $groupKey => $group) {
        $isMultiple = count($group) > 1;
        if ($isMultiple) {
            $multipleGroups++;
        }
        
        echo "<div style='border: 1px solid #ccc; margin: 10px 0; padding: 10px;'>";
        echo "<h4>Grupo: " . substr($groupKey, 0, 50) . "...</h4>";
        echo "<p><strong>Es múltiple:</strong> " . ($isMultiple ? 'SÍ' : 'NO') . " (" . count($group) . " registros)</p>";
        
        if ($isMultiple) {
            echo "<p style='color: green;'><strong>¡Este grupo debería mostrar botón de expansión!</strong></p>";
        }
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 12px;'>";
        echo "<tr><th>ID</th><th>Fecha Carga</th><th>Hora</th><th>Usuario</th><th>Conductor</th><th>Chapa</th><th>Baucher</th><th>Litros</th><th>Origen</th><th>Destino</th></tr>";
        
        foreach ($group as $registro) {
            echo "<tr>";
            echo "<td>{$registro['id']}</td>";
            echo "<td>{$registro['fecha_carga']}</td>";
            echo "<td>{$registro['hora_carga']}</td>";
            echo "<td>" . htmlspecialchars($registro['nombre_usuario']) . "</td>";
            echo "<td>" . htmlspecialchars($registro['nombre_conductor']) . "</td>";
            echo "<td>" . htmlspecialchars($registro['chapa']) . "</td>";
            echo "<td>" . htmlspecialchars($registro['numero_baucher']) . "</td>";
            echo "<td>{$registro['litros_cargados']}</td>";
            echo "<td>" . htmlspecialchars($registro['origen']) . "</td>";
            echo "<td>" . htmlspecialchars($registro['destino']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    }
    
    echo "<hr>";
    echo "<h3>Resumen</h3>";
    echo "<p><strong>Total de grupos:</strong> " . count($groupedRecords) . "</p>";
    echo "<p><strong>Grupos múltiples:</strong> $multipleGroups</p>";
    
    if ($multipleGroups > 0) {
        echo "<p style='color: green; font-weight: bold;'>✓ Hay grupos múltiples. Los botones de expansión DEBERÍAN aparecer.</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>✗ No hay grupos múltiples. No aparecerán botones de expansión.</p>";
        echo "<p>Para que aparezcan botones de expansión, necesitas registros que tengan exactamente los mismos valores en:</p>";
        echo "<ul>";
        echo "<li>Fecha de carga</li>";
        echo "<li>Nombre de usuario</li>";
        echo "<li>Nombre de conductor</li>";
        echo "<li>Chapa del vehículo</li>";
        echo "<li>Número de baucher</li>";
        echo "<li>Litros cargados</li>";
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>