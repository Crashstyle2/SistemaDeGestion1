<?php
// Test para verificar registros múltiples
require_once '../../config/database.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Consulta para encontrar registros con múltiples recorridos
    $sql = "SELECT 
                fecha_carga,
                nombre_usuario,
                nombre_conductor,
                chapa,
                numero_baucher,
                litros_cargados,
                COUNT(*) as total_registros
            FROM uso_combustible uc
            LEFT JOIN usuarios u ON uc.usuario_id = u.id
            WHERE fecha_carga >= CURDATE() - INTERVAL 30 DAY
            GROUP BY fecha_carga, nombre_usuario, nombre_conductor, chapa, numero_baucher, litros_cargados
            HAVING COUNT(*) > 1
            ORDER BY total_registros DESC
            LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $multipleRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Registros con múltiples recorridos (últimos 30 días):</h2>";
    echo "<p>Total encontrados: " . count($multipleRecords) . "</p>";
    
    if (count($multipleRecords) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Fecha Carga</th><th>Usuario</th><th>Conductor</th><th>Chapa</th><th>Baucher</th><th>Litros</th><th>Total Registros</th></tr>";
        
        foreach ($multipleRecords as $record) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($record['fecha_carga']) . "</td>";
            echo "<td>" . htmlspecialchars($record['nombre_usuario']) . "</td>";
            echo "<td>" . htmlspecialchars($record['nombre_conductor']) . "</td>";
            echo "<td>" . htmlspecialchars($record['chapa']) . "</td>";
            echo "<td>" . htmlspecialchars($record['numero_baucher']) . "</td>";
            echo "<td>" . htmlspecialchars($record['litros_cargados']) . "</td>";
            echo "<td><strong>" . $record['total_registros'] . "</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p><strong>No se encontraron registros con múltiples recorridos.</strong></p>";
        echo "<p>Esto explica por qué no aparecen los botones de expansión.</p>";
    }
    
    // Verificar también la tabla uso_combustible_recorridos
    echo "<h2>Verificación de tabla uso_combustible_recorridos:</h2>";
    $sqlRecorridos = "SELECT COUNT(*) as total FROM uso_combustible_recorridos";
    $stmtRecorridos = $pdo->prepare($sqlRecorridos);
    $stmtRecorridos->execute();
    $totalRecorridos = $stmtRecorridos->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>Total de registros en uso_combustible_recorridos: " . $totalRecorridos['total'] . "</p>";
    
    if ($totalRecorridos['total'] > 0) {
        $sqlSample = "SELECT * FROM uso_combustible_recorridos LIMIT 5";
        $stmtSample = $pdo->prepare($sqlSample);
        $stmtSample->execute();
        $sampleRecorridos = $stmtSample->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Muestra de registros:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        if (count($sampleRecorridos) > 0) {
            echo "<tr>";
            foreach (array_keys($sampleRecorridos[0]) as $column) {
                echo "<th>" . htmlspecialchars($column) . "</th>";
            }
            echo "</tr>";
            
            foreach ($sampleRecorridos as $row) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
        }
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>