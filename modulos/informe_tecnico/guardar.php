// ... código existente ...

if(isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
    $foto_nombre = time() . '_' . $_FILES['foto']['name'];
    $foto_ruta = '../../uploads/informe_tecnico/fotos/' . $foto_nombre;
    
    if(move_uploaded_file($_FILES['foto']['tmp_name'], $foto_ruta)) {
        // La foto se guardó exitosamente
        $_SESSION['foto_informe'] = $foto_nombre;
    }
}

// ... continuar con el guardado normal del informe ...