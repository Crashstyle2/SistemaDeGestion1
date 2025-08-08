if($informe->actualizar()) {
        // Agregar registro de actividad
        require_once '../../config/ActivityLogger.php';
        ActivityLogger::logAccion(
            $_SESSION['user_id'],
            'informe_tecnico',
            'editar',
            "Informe técnico actualizado - ID: {$id}, Local: {$_POST['local']}, Patrimonio: {$_POST['patrimonio']}"
        );

        header("Location: index.php?mensaje=Informe actualizado exitosamente");
        exit;
    }