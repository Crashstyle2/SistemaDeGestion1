<?php
session_start();
require_once '../../config/database.php';

// Verificar si hay registros con múltiples recorridos
$sql = "SELECT uc.id, uc.fecha_carga, uc.nombre_conductor, uc.chapa, uc.numero_baucher, uc.litros_cargados,
       COUNT(ucr.id) as total_recorridos,
       GROUP_CONCAT(ucr.id ORDER BY ucr.id) as recorrido_ids,
       GROUP_CONCAT(CONCAT(ucr.origen, ' → ', ucr.destino) ORDER BY ucr.id SEPARATOR ' | ') as rutas
       FROM uso_combustible uc 
       LEFT JOIN uso_combustible_recorridos ucr ON uc.id = ucr.uso_combustible_id 
       GROUP BY uc.id, uc.fecha_carga, uc.nombre_conductor, uc.chapa, uc.numero_baucher, uc.litros_cargados
       HAVING total_recorridos > 1
       ORDER BY uc.fecha_registro DESC
       LIMIT 10";

$stmt = $conn->prepare($sql);
$stmt->execute();
$registros_multiples = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Debug: Registros con Múltiples Recorridos</h2>";
echo "<p>Total de registros con múltiples recorridos: " . count($registros_multiples) . "</p>";

if (count($registros_multiples) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Fecha Carga</th><th>Conductor</th><th>Chapa</th><th>Voucher</th><th>Litros</th><th>Total Recorridos</th><th>IDs Recorridos</th><th>Rutas</th></tr>";
    
    foreach ($registros_multiples as $registro) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($registro['id']) . "</td>";
        echo "<td>" . htmlspecialchars($registro['fecha_carga']) . "</td>";
        echo "<td>" . htmlspecialchars($registro['nombre_conductor']) . "</td>";
        echo "<td>" . htmlspecialchars($registro['chapa']) . "</td>";
        echo "<td>" . htmlspecialchars($registro['numero_baucher']) . "</td>";
        echo "<td>" . htmlspecialchars($registro['litros_cargados']) . "</td>";
        echo "<td>" . htmlspecialchars($registro['total_recorridos']) . "</td>";
        echo "<td>" . htmlspecialchars($registro['recorrido_ids']) . "</td>";
        echo "<td>" . htmlspecialchars($registro['rutas']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No se encontraron registros con múltiples recorridos.</p>";
}

// Verificar la lógica de agrupación actual
echo "<hr><h2>Debug: Lógica de Agrupación Actual</h2>";

$sql2 = "SELECT uc.*, u.nombre as nombre_usuario, 
       ucr.origen, ucr.destino, ucr.km_sucursales, ucr.comentarios_sector,
       uc.fecha_registro, ucr.id as recorrido_id, uc.estado_recorrido
       FROM uso_combustible uc 
       LEFT JOIN usuarios u ON uc.user_id = u.id 
       LEFT JOIN uso_combustible_recorridos ucr ON uc.id = ucr.uso_combustible_id 
       WHERE 1=1
       ORDER BY uc.fecha_registro DESC
       LIMIT 20";

$stmt2 = $conn->prepare($sql2);
$stmt2->execute();
$registros = $stmt2->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Total de registros obtenidos: " . count($registros) . "</p>";

// Simular la lógica de agrupación
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

echo "<p>Grupos creados: " . count($groupedRecords) . "</p>";

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Grupo</th><th>Cantidad</th><th>¿Es Múltiple?</th><th>Detalles</th></tr>";

$groupIndex = 0;
foreach ($groupedRecords as $groupKey => $group) {
    $groupIndex++;
    $isMultiple = count($group) > 1;
    
    echo "<tr style='background-color: " . ($isMultiple ? '#e8f5e8' : '#f8f8f8') . "'>";
    echo "<td>Grupo " . $groupIndex . "</td>";
    echo "<td>" . count($group) . "</td>";
    echo "<td>" . ($isMultiple ? 'SÍ' : 'NO') . "</td>";
    echo "<td>";
    
    foreach ($group as $item) {
        echo "ID: " . $item['id'] . ", Recorrido ID: " . ($item['recorrido_id'] ?? 'NULL') . ", Origen: " . ($item['origen'] ?? 'NULL') . " → Destino: " . ($item['destino'] ?? 'NULL') . "<br>";
    }
    
    echo "</td>";
    echo "</tr>";
}
echo "</table>";

// Verificar estructura de tablas
echo "<hr><h2>Debug: Estructura de Tablas</h2>";

$sql3 = "SHOW TABLES LIKE '%combustible%'";
$stmt3 = $conn->prepare($sql3);
$stmt3->execute();
$tablas = $stmt3->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Tablas relacionadas con combustible:</p>";
echo "<ul>";
foreach ($tablas as $tabla) {
    echo "<li>" . array_values($tabla)[0] . "</li>";
}
echo "</ul>";

// Contar registros en cada tabla
$sql4 = "SELECT COUNT(*) as total FROM uso_combustible";
$stmt4 = $conn->prepare($sql4);
$stmt4->execute();
$total_uc = $stmt4->fetch(PDO::FETCH_ASSOC)['total'];

$sql5 = "SELECT COUNT(*) as total FROM uso_combustible_recorridos";
$stmt5 = $conn->prepare($sql5);
$stmt5->execute();
$total_ucr = $stmt5->fetch(PDO::FETCH_ASSOC)['total'];

echo "<p>Total registros en uso_combustible: " . $total_uc . "</p>";
echo "<p>Total registros en uso_combustible_recorridos: " . $total_ucr . "</p>";

if ($total_ucr == 0) {
    echo "<p style='color: red; font-weight: bold;'>¡PROBLEMA ENCONTRADO! No hay registros en la tabla uso_combustible_recorridos.</p>";
    echo "<p>Esto explica por qué no se muestran los detalles del recorrido múltiple.</p>";
}
?>