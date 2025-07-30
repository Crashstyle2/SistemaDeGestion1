<?php
require_once 'config/database.php';

echo "<h2>Verificación de Optimización del Sistema</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h3>✅ Conexión a Base de Datos: OK</h3>";
    
    // Verificar directorios
    $directorios = [
        'img/informe_tecnicos/fotos',
        'img/uso_combustible/vouchers',
        'img/acuse_recibo/fotos'
    ];
    
    echo "<h3>Verificación de Directorios:</h3>";
    foreach ($directorios as $dir) {
        if (is_dir($dir)) {
            echo "✅ {$dir}: Existe<br>";
            echo "&nbsp;&nbsp;&nbsp;Permisos: " . substr(sprintf('%o', fileperms($dir)), -4) . "<br>";
        } else {
            echo "❌ {$dir}: No existe<br>";
        }
    }
    
    // Verificar columnas en base de datos
    echo "<h3>Verificación de Columnas:</h3>";
    
    $tablas = [
        'fotos_informe_tecnico' => 'foto_ruta',
        'uso_combustible' => 'foto_voucher_ruta',
        'acuse_recibo' => 'foto_ruta'
    ];
    
    foreach ($tablas as $tabla => $columna) {
        $query = "SHOW COLUMNS FROM {$tabla} LIKE '{$columna}'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo "✅ {$tabla}.{$columna}: Existe<br>";
        } else {
            echo "❌ {$tabla}.{$columna}: No existe<br>";
        }
    }
    
    // Estadísticas de datos
    echo "<h3>Estadísticas de Datos:</h3>";
    
    $queries = [
        'Total fotos informe técnico' => "SELECT COUNT(*) as total FROM fotos_informe_tecnico",
        'Fotos con Base64' => "SELECT COUNT(*) as total FROM fotos_informe_tecnico WHERE foto IS NOT NULL",
        'Fotos con ruta' => "SELECT COUNT(*) as total FROM fotos_informe_tecnico WHERE foto_ruta IS NOT NULL",
        'Total vouchers' => "SELECT COUNT(*) as total FROM uso_combustible",
        'Vouchers con Base64' => "SELECT COUNT(*) as total FROM uso_combustible WHERE foto_voucher IS NOT NULL",
        'Vouchers con ruta' => "SELECT COUNT(*) as total FROM uso_combustible WHERE foto_voucher_ruta IS NOT NULL",
        'Total acuses' => "SELECT COUNT(*) as total FROM acuse_recibo",
        'Acuses con Base64' => "SELECT COUNT(*) as total FROM acuse_recibo WHERE foto IS NOT NULL",
        'Acuses con ruta' => "SELECT COUNT(*) as total FROM acuse_recibo WHERE foto_ruta IS NOT NULL"
    ];
    
    foreach ($queries as $descripcion => $query) {
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "{$descripcion}: {$result['total']}<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>