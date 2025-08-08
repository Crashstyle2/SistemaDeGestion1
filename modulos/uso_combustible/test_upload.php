<?php
echo "<h2>Test de Upload de Fotos</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>Datos recibidos:</h3>";
    echo "<pre>";
    print_r($_FILES);
    echo "</pre>";
    
    if (isset($_FILES['foto_voucher']) && $_FILES['foto_voucher']['error'] === UPLOAD_ERR_OK) {
        echo "<p style='color: green;'>✓ Archivo recibido correctamente</p>";
        
        // Probar diferentes rutas
        $rutas_test = [
            '../../img/uso_combustible/vouchers/',
            '../../../img/uso_combustible/vouchers/',
            $_SERVER['DOCUMENT_ROOT'] . '/MantenimientodeUPS/img/uso_combustible/vouchers/',
            realpath('../../img/uso_combustible/vouchers/') . '/'
        ];
        
        foreach ($rutas_test as $i => $ruta) {
            echo "<p><strong>Ruta " . ($i+1) . ":</strong> " . $ruta . "</p>";
            echo "<p>Existe: " . (file_exists($ruta) ? '✓ SÍ' : '✗ NO') . "</p>";
            echo "<p>Es escribible: " . (is_writable($ruta) ? '✓ SÍ' : '✗ NO') . "</p>";
            echo "<hr>";
        }
        
        // Intentar guardar con la ruta que funciona
        $directorio_correcto = realpath('../../img/uso_combustible/vouchers/') . '/';
        if (file_exists($directorio_correcto)) {
            $nombre_archivo = 'test_' . time() . '.jpg';
            $ruta_completa = $directorio_correcto . $nombre_archivo;
            
            if (move_uploaded_file($_FILES['foto_voucher']['tmp_name'], $ruta_completa)) {
                echo "<p style='color: green;'>✓ Archivo guardado exitosamente en: " . $ruta_completa . "</p>";
            } else {
                echo "<p style='color: red;'>✗ Error al guardar archivo</p>";
                echo "<p>Error: " . error_get_last()['message'] . "</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>✗ No se recibió archivo o hay error</p>";
        if (isset($_FILES['foto_voucher'])) {
            echo "<p>Error code: " . $_FILES['foto_voucher']['error'] . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Upload</title>
</head>
<body>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="foto_voucher" accept="image/*" required>
        <button type="submit">Probar Upload</button>
    </form>
</body>
</html>