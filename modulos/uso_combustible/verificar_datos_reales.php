<?php
require_once '../../config/Database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h2>Verificación de Datos Reales en la Base de Datos</h2>";
    
    // Consultar todos los registros
    $stmt = $pdo->prepare("
        SELECT 
            id,
            fecha_carga,
            nombre_usuario,
            nombre_conductor,
            chapa,
            numero_baucher,
            litros_cargados,
            recorrido_id,
            origen,
            destino,
            estado
        FROM uso_combustible 
        ORDER BY fecha_carga DESC, recorrido_id ASC
    ");
    
    $stmt->execute();
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Total de registros: " . count($registros) . "</h3>";
    
    if (count($registros) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th><th>Fecha Carga</th><th>Usuario</th><th>Conductor</th><th>Chapa</th><th>Baucher</th><th>Litros</th><th>Recorrido ID</th><th>Origen</th><th>Destino</th><th>Estado</th>";
        echo "</tr>";
        
        foreach ($registros as $registro) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($registro['id']) . "</td>";
            echo "<td>" . htmlspecialchars($registro['fecha_carga']) . "</td>";
            echo "<td>" . htmlspecialchars($registro['nombre_usuario']) . "</td>";
            echo "<td>" . htmlspecialchars($registro['nombre_conductor']) . "</td>";
            echo "<td>" . htmlspecialchars($registro['chapa']) . "</td>";
            echo "<td>" . htmlspecialchars($registro['numero_baucher']) . "</td>";
            echo "<td>" . htmlspecialchars($registro['litros_cargados']) . "</td>";
            echo "<td>" . htmlspecialchars($registro['recorrido_id']) . "</td>";
            echo "<td>" . htmlspecialchars($registro['origen']) . "</td>";
            echo "<td>" . htmlspecialchars($registro['destino']) . "</td>";
            echo "<td>" . htmlspecialchars($registro['estado']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Analizar agrupación
        echo "<h3>Análisis de Agrupación</h3>";
        $grupos = [];
        
        foreach ($registros as $registro) {
            $claveGrupo = $registro['fecha_carga'] . '|' . 
                         $registro['nombre_usuario'] . '|' . 
                         $registro['nombre_conductor'] . '|' . 
                         $registro['chapa'] . '|' . 
                         $registro['numero_baucher'] . '|' . 
                         $registro['litros_cargados'];
            
            if (!isset($grupos[$claveGrupo])) {
                $grupos[$claveGrupo] = [];
            }
            $grupos[$claveGrupo][] = $registro;
        }
        
        echo "<p>Total de grupos encontrados: " . count($grupos) . "</p>";
        
        $gruposMultiples = 0;
        foreach ($grupos as $clave => $grupo) {
            if (count($grupo) > 1) {
                $gruposMultiples++;
                echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
                echo "<h4>Grupo Múltiple #$gruposMultiples (" . count($grupo) . " registros)</h4>";
                echo "<p><strong>Clave:</strong> $clave</p>";
                echo "<ul>";
                foreach ($grupo as $reg) {
                    echo "<li>ID: {$reg['id']}, Recorrido: {$reg['recorrido_id']}, Origen: {$reg['origen']}, Destino: {$reg['destino']}</li>";
                }
                echo "</ul>";
                echo "</div>";
            }
        }
        
        if ($gruposMultiples == 0) {
            echo "<p style='color: red; font-weight: bold;'>No se encontraron grupos múltiples en los datos actuales.</p>";
            echo "<p>Esto explica por qué no aparecen los botones de expansión.</p>";
        } else {
            echo "<p style='color: green; font-weight: bold;'>Se encontraron $gruposMultiples grupos múltiples.</p>";
        }
        
    } else {
        echo "<p style='color: red;'>No hay registros en la base de datos.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>