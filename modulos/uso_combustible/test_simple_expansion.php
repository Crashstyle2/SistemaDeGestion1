<?php
require_once '../../config/Database.php';

// Primero, insertar datos de prueba simples
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Limpiar datos de prueba anteriores
    $stmt = $pdo->prepare("DELETE FROM uso_combustible WHERE numero_baucher LIKE 'TEST-%'");
    $stmt->execute();
    
    // Insertar exactamente 2 registros con los mismos datos de agrupación
    $datosBase = [
        'fecha_carga' => '2024-01-15',
        'hora_carga' => '10:00:00',
        'nombre_usuario' => 'Usuario Test',
        'nombre_conductor' => 'Conductor Test',
        'chapa' => 'ABC-123',
        'numero_baucher' => 'TEST-SIMPLE',
        'litros_cargados' => '50.00',
        'precio_litro' => '1.50',
        'total_carga' => '75.00',
        'numero_tarjeta' => '123456',
        'estado_recorrido' => 'abierto',
        'user_id' => 1
    ];
    
    // Registro 1
    $registro1 = array_merge($datosBase, [
        'recorrido_id' => 1,
        'origen' => 'Sucursal A',
        'destino' => 'Sucursal B'
    ]);
    
    // Registro 2
    $registro2 = array_merge($datosBase, [
        'recorrido_id' => 2,
        'origen' => 'Sucursal B',
        'destino' => 'Sucursal C'
    ]);
    
    $sql = "INSERT INTO uso_combustible (
        fecha_carga, hora_carga, nombre_usuario, nombre_conductor, chapa, 
        numero_baucher, litros_cargados, precio_litro, total_carga, 
        numero_tarjeta, recorrido_id, origen, destino, estado_recorrido, user_id
    ) VALUES (
        :fecha_carga, :hora_carga, :nombre_usuario, :nombre_conductor, :chapa,
        :numero_baucher, :litros_cargados, :precio_litro, :total_carga,
        :numero_tarjeta, :recorrido_id, :origen, :destino, :estado_recorrido, :user_id
    )";
    
    $stmt = $pdo->prepare($sql);
    
    // Insertar registro 1
    $stmt->execute($registro1);
    echo "<p style='color: green;'>✅ Registro 1 insertado (ID: " . $pdo->lastInsertId() . ")</p>";
    
    // Insertar registro 2
    $stmt->execute($registro2);
    echo "<p style='color: green;'>✅ Registro 2 insertado (ID: " . $pdo->lastInsertId() . ")</p>";
    
    echo "<h2>Datos insertados correctamente</h2>";
    echo "<p>Se han insertado 2 registros con los mismos datos de agrupación:</p>";
    echo "<ul>";
    echo "<li>Fecha: {$datosBase['fecha_carga']}</li>";
    echo "<li>Usuario: {$datosBase['nombre_usuario']}</li>";
    echo "<li>Conductor: {$datosBase['nombre_conductor']}</li>";
    echo "<li>Chapa: {$datosBase['chapa']}</li>";
    echo "<li>Baucher: {$datosBase['numero_baucher']}</li>";
    echo "<li>Litros: {$datosBase['litros_cargados']}</li>";
    echo "</ul>";
    
    echo "<p><strong>Estos registros deberían aparecer como un grupo múltiple en ver_registros.php</strong></p>";
    
    echo "<hr>";
    echo "<h3>Verificación de agrupación</h3>";
    
    // Verificar la agrupación
    $stmt = $pdo->prepare("
        SELECT * FROM uso_combustible 
        WHERE numero_baucher = 'TEST-SIMPLE'
        ORDER BY recorrido_id ASC
    ");
    $stmt->execute();
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $groupKey = $registros[0]['fecha_carga'] . '|' . 
                $registros[0]['nombre_usuario'] . '|' . 
                $registros[0]['nombre_conductor'] . '|' . 
                $registros[0]['chapa'] . '|' . 
                $registros[0]['numero_baucher'] . '|' . 
                $registros[0]['litros_cargados'];
    
    echo "<p><strong>Clave de grupo generada:</strong> <code>$groupKey</code></p>";
    echo "<p><strong>Número de registros en el grupo:</strong> " . count($registros) . "</p>";
    
    if (count($registros) > 1) {
        echo "<p style='color: green; font-weight: bold;'>✅ GRUPO MÚLTIPLE DETECTADO - Debería aparecer botón de expansión</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ No se detectó grupo múltiple</p>";
    }
    
    echo "<hr>";
    echo "<p><a href='ver_registros.php' target='_blank' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔗 Abrir ver_registros.php</a></p>";
    echo "<p><small>Busca el registro con baucher 'TEST-SIMPLE' - debería tener un botón azul con '2 recorridos'</small></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>