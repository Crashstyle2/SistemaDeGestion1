<?php
require_once '../../config/Database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Consulta para encontrar registros agrupados
    $sql = "SELECT 
                fecha_carga,
                nombre_usuario,
                nombre_conductor,
                chapa,
                numero_baucher,
                litros_cargados,
                COUNT(*) as total_registros,
                GROUP_CONCAT(id ORDER BY fecha_carga, hora_carga) as ids,
                GROUP_CONCAT(CONCAT(origen, ' → ', destino) SEPARATOR ' | ') as recorridos
            FROM uso_combustible 
            WHERE estado = 'activo'
            GROUP BY fecha_carga, nombre_usuario, nombre_conductor, chapa, numero_baucher, litros_cargados
            HAVING COUNT(*) > 1
            ORDER BY fecha_carga DESC, hora_carga DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Verificación de Grupos Múltiples</h2>";
    echo "<p>Total de grupos con múltiples registros: " . count($grupos) . "</p>";
    
    if (count($grupos) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr>";
        echo "<th>Fecha</th>";
        echo "<th>Usuario</th>";
        echo "<th>Conductor</th>";
        echo "<th>Chapa</th>";
        echo "<th>Baucher</th>";
        echo "<th>Litros</th>";
        echo "<th>Total Registros</th>";
        echo "<th>IDs</th>";
        echo "<th>Recorridos</th>";
        echo "</tr>";
        
        foreach ($grupos as $grupo) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($grupo['fecha_carga']) . "</td>";
            echo "<td>" . htmlspecialchars($grupo['nombre_usuario']) . "</td>";
            echo "<td>" . htmlspecialchars($grupo['nombre_conductor']) . "</td>";
            echo "<td>" . htmlspecialchars($grupo['chapa']) . "</td>";
            echo "<td>" . htmlspecialchars($grupo['numero_baucher']) . "</td>";
            echo "<td>" . htmlspecialchars($grupo['litros_cargados']) . "</td>";
            echo "<td><strong>" . $grupo['total_registros'] . "</strong></td>";
            echo "<td>" . htmlspecialchars($grupo['ids']) . "</td>";
            echo "<td>" . htmlspecialchars($grupo['recorridos']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p><strong>No se encontraron grupos con múltiples registros.</strong></p>";
        echo "<p>Esto significa que cada registro tiene una combinación única de:</p>";
        echo "<ul>";
        echo "<li>Fecha de carga</li>";
        echo "<li>Usuario</li>";
        echo "<li>Conductor</li>";
        echo "<li>Chapa del vehículo</li>";
        echo "<li>Número de baucher</li>";
        echo "<li>Litros cargados</li>";
        echo "</ul>";
        echo "<p>Para que aparezcan botones de expansión, debe haber al menos 2 registros con los mismos valores en estos campos.</p>";
    }
    
    // Mostrar algunos registros recientes para referencia
    echo "<h3>Últimos 10 registros (para referencia)</h3>";
    $sql_recent = "SELECT id, fecha_carga, hora_carga, nombre_usuario, nombre_conductor, chapa, numero_baucher, litros_cargados, origen, destino 
                   FROM uso_combustible 
                   WHERE estado = 'activo' 
                   ORDER BY fecha_carga DESC, hora_carga DESC 
                   LIMIT 10";
    
    $stmt_recent = $pdo->prepare($sql_recent);
    $stmt_recent->execute();
    $registros_recientes = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 12px;'>";
    echo "<tr>";
    echo "<th>ID</th><th>Fecha</th><th>Hora</th><th>Usuario</th><th>Conductor</th><th>Chapa</th><th>Baucher</th><th>Litros</th><th>Origen</th><th>Destino</th>";
    echo "</tr>";
    
    foreach ($registros_recientes as $registro) {
        echo "<tr>";
        echo "<td>" . $registro['id'] . "</td>";
        echo "<td>" . $registro['fecha_carga'] . "</td>";
        echo "<td>" . $registro['hora_carga'] . "</td>";
        echo "<td>" . htmlspecialchars($registro['nombre_usuario']) . "</td>";
        echo "<td>" . htmlspecialchars($registro['nombre_conductor']) . "</td>";
        echo "<td>" . htmlspecialchars($registro['chapa']) . "</td>";
        echo "<td>" . htmlspecialchars($registro['numero_baucher']) . "</td>";
        echo "<td>" . $registro['litros_cargados'] . "</td>";
        echo "<td>" . htmlspecialchars($registro['origen']) . "</td>";
        echo "<td>" . htmlspecialchars($registro['destino']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>