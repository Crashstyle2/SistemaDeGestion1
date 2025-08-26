<?php
require_once '../../config/Database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Primero, eliminar cualquier dato de prueba anterior
    $deleteSQL = "DELETE FROM uso_combustible WHERE numero_baucher LIKE 'TEST-%'";
    $pdo->exec($deleteSQL);
    
    // Obtener un usuario existente
    $userSQL = "SELECT id, nombre FROM usuarios LIMIT 1";
    $userStmt = $pdo->prepare($userSQL);
    $userStmt->execute();
    $usuario = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        throw new Exception("No se encontró ningún usuario en la base de datos");
    }
    
    echo "<h2>Creando Grupo Múltiple Forzado</h2>";
    echo "<p>Usuario seleccionado: {$usuario['nombre']} (ID: {$usuario['id']})</p>";
    
    // Datos exactamente iguales para crear un grupo múltiple
    $datosBase = [
        'usuario_id' => $usuario['id'],
        'fecha_carga' => '2024-01-15',
        'hora_carga' => '08:30:00',
        'tipo_vehiculo' => 'particular',
        'nombre_conductor' => 'Juan Pérez Test',
        'chapa' => 'ABC-123',
        'numero_baucher' => 'TEST-12345',
        'tarjeta' => '1234567890',
        'litros_cargados' => 50.00,
        'estado' => 'activo',
        'fecha_registro' => date('Y-m-d H:i:s')
    ];
    
    // Crear 3 registros con los mismos datos base pero diferentes recorridos
    $recorridos = [
        ['origen' => 'Asunción', 'destino' => 'San Lorenzo', 'documento' => 'DOC001'],
        ['origen' => 'San Lorenzo', 'destino' => 'Luque', 'documento' => 'DOC002'],
        ['origen' => 'Luque', 'destino' => 'Asunción', 'documento' => 'DOC003']
    ];
    
    $insertSQL = "INSERT INTO uso_combustible (
        usuario_id, fecha_carga, hora_carga, tipo_vehiculo, nombre_conductor, 
        chapa, numero_baucher, tarjeta, litros_cargados, origen, destino, 
        documento, estado, fecha_registro
    ) VALUES (
        :usuario_id, :fecha_carga, :hora_carga, :tipo_vehiculo, :nombre_conductor,
        :chapa, :numero_baucher, :tarjeta, :litros_cargados, :origen, :destino,
        :documento, :estado, :fecha_registro
    )";
    
    $stmt = $pdo->prepare($insertSQL);
    
    $idsInsertados = [];
    
    foreach ($recorridos as $index => $recorrido) {
        $datos = array_merge($datosBase, $recorrido);
        
        if ($stmt->execute($datos)) {
            $id = $pdo->lastInsertId();
            $idsInsertados[] = $id;
            echo "<p>✓ Registro " . ($index + 1) . " insertado con ID: $id</p>";
            echo "<p>&nbsp;&nbsp;&nbsp;Recorrido: {$recorrido['origen']} → {$recorrido['destino']}</p>";
        } else {
            echo "<p>✗ Error al insertar registro " . ($index + 1) . "</p>";
        }
    }
    
    echo "<hr>";
    echo "<h3>Verificación del Grupo Creado</h3>";
    
    // Verificar que se creó el grupo múltiple
    $verifySQL = "SELECT id, origen, destino, documento, fecha_registro
                  FROM uso_combustible 
                  WHERE numero_baucher = 'TEST-12345'
                  ORDER BY fecha_registro";
    
    $verifyStmt = $pdo->prepare($verifySQL);
    $verifyStmt->execute();
    $registrosCreados = $verifyStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Registros creados:</strong> " . count($registrosCreados) . "</p>";
    
    if (count($registrosCreados) > 1) {
        echo "<p style='color: green; font-weight: bold;'>✓ GRUPO MÚLTIPLE CREADO EXITOSAMENTE</p>";
        echo "<p>Estos registros tienen exactamente los mismos valores en:</p>";
        echo "<ul>";
        echo "<li>Fecha de carga: {$datosBase['fecha_carga']}</li>";
        echo "<li>Usuario: {$usuario['nombre']}</li>";
        echo "<li>Conductor: {$datosBase['nombre_conductor']}</li>";
        echo "<li>Chapa: {$datosBase['chapa']}</li>";
        echo "<li>Número de baucher: {$datosBase['numero_baucher']}</li>";
        echo "<li>Litros cargados: {$datosBase['litros_cargados']}</li>";
        echo "</ul>";
        
        echo "<table border='1' style='border-collapse: collapse; margin-top: 10px;'>";
        echo "<tr><th>ID</th><th>Origen</th><th>Destino</th><th>Documento</th><th>Fecha Registro</th></tr>";
        foreach ($registrosCreados as $reg) {
            echo "<tr>";
            echo "<td>{$reg['id']}</td>";
            echo "<td>{$reg['origen']}</td>";
            echo "<td>{$reg['destino']}</td>";
            echo "<td>{$reg['documento']}</td>";
            echo "<td>{$reg['fecha_registro']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<hr>";
        echo "<h3>Próximos Pasos</h3>";
        echo "<p>1. Ve a <a href='ver_registros.php' target='_blank'>ver_registros.php</a></p>";
        echo "<p>2. Busca el registro con baucher 'TEST-12345'</p>";
        echo "<p>3. Deberías ver un botón de expansión con '" . count($registrosCreados) . " recorridos'</p>";
        echo "<p>4. Para limpiar estos datos de prueba, ejecuta <a href='eliminar_datos_prueba.php' target='_blank'>eliminar_datos_prueba.php</a></p>";
        
    } else {
        echo "<p style='color: red; font-weight: bold;'>✗ Error: No se creó el grupo múltiple</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}
?>