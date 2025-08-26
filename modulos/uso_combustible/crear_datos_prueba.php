<?php
// Script para crear datos de prueba con múltiples recorridos
require_once '../../config/database.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Creando datos de prueba para recorridos múltiples</h2>";
    
    // Obtener un usuario existente
    $sqlUser = "SELECT id FROM usuarios LIMIT 1";
    $stmtUser = $pdo->prepare($sqlUser);
    $stmtUser->execute();
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "<p style='color: red;'>Error: No se encontraron usuarios en la base de datos.</p>";
        exit;
    }
    
    $userId = $user['id'];
    
    // Datos de prueba para múltiples recorridos
    $fechaCarga = date('Y-m-d');
    $horaCarga = '08:00:00';
    $conductor = 'Juan Pérez - PRUEBA';
    $chapa = 'TEST123';
    $baucher = 'PRUEBA001';
    $litros = 50.00;
    $tarjeta = 'TARJETA001';
    
    // Insertar primer recorrido
    $sql1 = "INSERT INTO uso_combustible (
        fecha_carga, hora_carga, usuario_id, nombre_usuario, tipo_vehiculo, 
        nombre_conductor, chapa, numero_baucher, tarjeta, litros_cargados,
        origen, destino, documento, fecha_registro, estado_recorrido
    ) VALUES (
        :fecha_carga, :hora_carga, :usuario_id, 'Usuario Prueba', 'camion',
        :conductor, :chapa, :baucher, :tarjeta, :litros,
        'Origen A', 'Destino B', 'DOC001', NOW(), 'cerrado'
    )";
    
    $stmt1 = $pdo->prepare($sql1);
    $stmt1->execute([
        ':fecha_carga' => $fechaCarga,
        ':hora_carga' => $horaCarga,
        ':usuario_id' => $userId,
        ':conductor' => $conductor,
        ':chapa' => $chapa,
        ':baucher' => $baucher,
        ':tarjeta' => $tarjeta,
        ':litros' => $litros
    ]);
    
    $recorrido1Id = $pdo->lastInsertId();
    echo "<p>✅ Primer recorrido creado con ID: $recorrido1Id</p>";
    
    // Insertar segundo recorrido (mismo grupo)
    $sql2 = "INSERT INTO uso_combustible (
        fecha_carga, hora_carga, usuario_id, nombre_usuario, tipo_vehiculo, 
        nombre_conductor, chapa, numero_baucher, tarjeta, litros_cargados,
        origen, destino, documento, fecha_registro, estado_recorrido
    ) VALUES (
        :fecha_carga, :hora_carga, :usuario_id, 'Usuario Prueba', 'camion',
        :conductor, :chapa, :baucher, :tarjeta, :litros,
        'Destino B', 'Destino C', 'DOC002', NOW(), 'cerrado'
    )";
    
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute([
        ':fecha_carga' => $fechaCarga,
        ':hora_carga' => $horaCarga,
        ':usuario_id' => $userId,
        ':conductor' => $conductor,
        ':chapa' => $chapa,
        ':baucher' => $baucher,
        ':tarjeta' => $tarjeta,
        ':litros' => $litros
    ]);
    
    $recorrido2Id = $pdo->lastInsertId();
    echo "<p>✅ Segundo recorrido creado con ID: $recorrido2Id</p>";
    
    // Insertar tercer recorrido (mismo grupo)
    $sql3 = "INSERT INTO uso_combustible (
        fecha_carga, hora_carga, usuario_id, nombre_usuario, tipo_vehiculo, 
        nombre_conductor, chapa, numero_baucher, tarjeta, litros_cargados,
        origen, destino, documento, fecha_registro, estado_recorrido
    ) VALUES (
        :fecha_carga, :hora_carga, :usuario_id, 'Usuario Prueba', 'camion',
        :conductor, :chapa, :baucher, :tarjeta, :litros,
        'Destino C', 'Destino Final', 'DOC003', NOW(), 'cerrado'
    )";
    
    $stmt3 = $pdo->prepare($sql3);
    $stmt3->execute([
        ':fecha_carga' => $fechaCarga,
        ':hora_carga' => $horaCarga,
        ':usuario_id' => $userId,
        ':conductor' => $conductor,
        ':chapa' => $chapa,
        ':baucher' => $baucher,
        ':tarjeta' => $tarjeta,
        ':litros' => $litros
    ]);
    
    $recorrido3Id = $pdo->lastInsertId();
    echo "<p>✅ Tercer recorrido creado con ID: $recorrido3Id</p>";
    
    echo "<h3>Datos de prueba creados exitosamente</h3>";
    echo "<p><strong>Grupo de recorridos múltiples:</strong></p>";
    echo "<ul>";
    echo "<li>Fecha: $fechaCarga</li>";
    echo "<li>Conductor: $conductor</li>";
    echo "<li>Chapa: $chapa</li>";
    echo "<li>Baucher: $baucher</li>";
    echo "<li>Total recorridos: 3</li>";
    echo "</ul>";
    
    echo "<p><a href='ver_registros.php' class='btn btn-primary'>Ver Registros</a></p>";
    echo "<p><a href='eliminar_datos_prueba.php' class='btn btn-danger'>Eliminar Datos de Prueba</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>