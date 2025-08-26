<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

echo "<h2>🔧 Test Final - Corrección de Subtabla</h2>";
echo "<p>Verificando que la corrección de clases CSS funcione correctamente...</p>";

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Limpiar datos de prueba anteriores
    $pdo->exec("DELETE FROM uso_combustible WHERE conductor LIKE 'TEST-FINAL%'");
    
    // Crear 3 registros de prueba con los mismos datos de agrupación
    $testData = [
        [
            'fecha_carga' => '2024-01-15',
            'hora_carga' => '08:00:00',
            'origen' => 'Asunción',
            'destino' => 'Ciudad del Este',
            'tipo_vehiculo' => 'Camión',
            'conductor' => 'TEST-FINAL-CONDUCTOR',
            'chapa' => 'ABC123',
            'numero_boucher' => '12345',
            'tarjeta' => 'TARJETA001',
            'litros_cargados' => 50.00,
            'documento' => 'DOC001',
            'user_id' => $_SESSION['user_id'],
            'estado_recorrido' => 'abierto'
        ],
        [
            'fecha_carga' => '2024-01-15',
            'hora_carga' => '10:00:00',
            'origen' => 'Ciudad del Este',
            'destino' => 'Encarnación',
            'tipo_vehiculo' => 'Camión',
            'conductor' => 'TEST-FINAL-CONDUCTOR',
            'chapa' => 'ABC123',
            'numero_boucher' => '12346',
            'tarjeta' => 'TARJETA001',
            'litros_cargados' => 45.00,
            'documento' => 'DOC002',
            'user_id' => $_SESSION['user_id'],
            'estado_recorrido' => 'abierto'
        ],
        [
            'fecha_carga' => '2024-01-15',
            'hora_carga' => '14:00:00',
            'origen' => 'Encarnación',
            'destino' => 'Asunción',
            'tipo_vehiculo' => 'Camión',
            'conductor' => 'TEST-FINAL-CONDUCTOR',
            'chapa' => 'ABC123',
            'numero_boucher' => '12347',
            'tarjeta' => 'TARJETA001',
            'litros_cargados' => 55.00,
            'documento' => 'DOC003',
            'user_id' => $_SESSION['user_id'],
            'estado_recorrido' => 'abierto'
        ]
    ];
    
    $sql = "INSERT INTO uso_combustible (fecha_carga, hora_carga, origen, destino, tipo_vehiculo, conductor, chapa, numero_boucher, tarjeta, litros_cargados, documento, user_id, estado_recorrido, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    
    foreach ($testData as $data) {
        $stmt->execute([
            $data['fecha_carga'],
            $data['hora_carga'],
            $data['origen'],
            $data['destino'],
            $data['tipo_vehiculo'],
            $data['conductor'],
            $data['chapa'],
            $data['numero_boucher'],
            $data['tarjeta'],
            $data['litros_cargados'],
            $data['documento'],
            $data['user_id'],
            $data['estado_recorrido']
        ]);
    }
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<h4>✅ Datos de prueba creados exitosamente</h4>";
    echo "<p><strong>3 registros</strong> con el conductor <strong>TEST-FINAL-CONDUCTOR</strong></p>";
    echo "<p>Estos registros deberían agruparse y mostrar un botón <strong>'3 recorridos'</strong></p>";
    echo "</div>";
    
    echo "<div style='background: #cce5ff; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<h4>🔧 Corrección Aplicada</h4>";
    echo "<p><strong>Problema identificado:</strong> Las filas de la subtabla usaban la clase <code>sub-record-row</code> pero el CSS definía estilos para <code>.sub-record</code></p>";
    echo "<p><strong>Solución:</strong> Cambié la clase HTML a <code>sub-record show</code> para que coincida con los estilos CSS</p>";
    echo "<p><strong>Resultado esperado:</strong> Ahora las 3 filas deberían aparecer correctamente en la subtabla expandida</p>";
    echo "</div>";
    
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<h4>📋 Instrucciones de Verificación</h4>";
    echo "<ol>";
    echo "<li>Haz clic en el botón de abajo para abrir ver_registros.php</li>";
    echo "<li>Busca el registro con conductor <strong>TEST-FINAL-CONDUCTOR</strong></li>";
    echo "<li>Deberías ver un botón azul que dice <strong>'3 recorridos'</strong></li>";
    echo "<li>Haz clic en ese botón para expandir</li>";
    echo "<li>Verifica que aparezcan <strong>las 3 filas</strong> en la subtabla:</li>";
    echo "<ul>";
    echo "<li>1° recorrido: Asunción → Ciudad del Este (08:00)</li>";
    echo "<li>2° recorrido: Ciudad del Este → Encarnación (10:00)</li>";
    echo "<li>3° recorrido: Encarnación → Asunción (14:00)</li>";
    echo "</ul>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div style='text-align: center; margin: 20px 0;'>";
    echo "<a href='ver_registros.php' class='btn btn-primary btn-lg' target='_blank'>";
    echo "<i class='fas fa-external-link-alt'></i> Abrir ver_registros.php";
    echo "</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
.btn {
    display: inline-block;
    padding: 12px 24px;
    background: #007bff;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-weight: bold;
}
.btn:hover {
    background: #0056b3;
    color: white;
    text-decoration: none;
}
</style>