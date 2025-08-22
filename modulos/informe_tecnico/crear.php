<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

require_once '../../config/database.php';
require_once '../../models/InformeTecnico.php';
require_once '../../models/RegistroActividad.php';

// Procesar el formulario de datos (sin firma)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Guardar los datos en la sesión para el siguiente paso
    $_SESSION['informe_temp'] = [
        'local' => $_POST['local'],
        'sector' => $_POST['sector'],
        'equipo_asistido' => $_POST['equipo_asistido'],
        'orden_trabajo' => $_POST['orden_trabajo'],
        'patrimonio' => $_POST['patrimonio'],
        'jefe_turno' => $_POST['jefe_turno'],
        'observaciones' => $_POST['observaciones']
    ];
    
    // Procesar las fotos temporalmente
    if(isset($_FILES['fotos']) && is_array($_FILES['fotos']['tmp_name'])) {
        $_SESSION['fotos_temp'] = [];
        foreach($_FILES['fotos']['tmp_name'] as $key => $tmp_name) {
            if($_FILES['fotos']['error'][$key] === UPLOAD_ERR_OK && !empty($tmp_name)) {
                // Crear directorio temporal
                $directorio_temp = '../../img/temp/';
                if (!file_exists($directorio_temp)) {
                    mkdir($directorio_temp, 0755, true);
                }
                
                $extension = pathinfo($_FILES['fotos']['name'][$key], PATHINFO_EXTENSION);
                if (empty($extension)) {
                    $extension = 'jpg';
                }
                
                $nombre_temp = 'temp_' . time() . '_' . uniqid() . '.' . $extension;
                $ruta_temp = $directorio_temp . $nombre_temp;
                
                if (move_uploaded_file($tmp_name, $ruta_temp)) {
                    $_SESSION['fotos_temp'][] = [
                        'archivo_temp' => $nombre_temp,
                        'descripcion' => $_POST['descripcion_foto'][$key] ?? '',
                        'tipo' => $_POST['tipo_foto'][$key] ?? 'antes'
                    ];
                }
            }
        }
    }
    
    // Redirigir a la página de firma
    header("Location: firmar.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Informe Técnico</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <!-- En el head, mantener solo una copia de cada script -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
        #signatureCanvas {
            border: 1px solid #ccc;
            border-radius: 4px;
            touch-action: none;
            cursor: crosshair;
        }
        .modal-body {
            padding: 15px;
            background: #fff;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h2>Nuevo Informe Técnico</h2>
        <form method="POST" id="informeForm" enctype="multipart/form-data">
            <div class="form-group">
                <label>Local</label>
                <input type="text" name="local" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Sector</label>
                <input type="text" name="sector" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Orden de Trabajo</label>
                <input type="text" name="orden_trabajo" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Equipo con Problema</label>
                <input type="text" name="equipo_asistido" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Nº de Patrimonio</label>
                <input type="text" name="patrimonio" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Jefe de Turno</label>
                <input type="text" name="jefe_turno" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Observaciones del Trabajo Realizado</label>
                <textarea name="observaciones" class="form-control" rows="4" required></textarea>
            </div>
            



            <!-- Reemplazar la sección de foto única con esto -->
            <div class="form-group">
                <label>Fotos del trabajo</label>
                <div id="fotosContainer">
                    <div class="foto-entrada mb-3">
                        <div class="row">
                            <div class="col-md-4">
                                <input type="file" class="form-control-file" name="fotos[]" accept="image/*">
                            </div>
                            <div class="col-md-4">
                                <select class="form-control" name="tipo_foto[]">
                                    <option value="antes">Foto Antes</option>
                                    <option value="despues">Foto Después</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <textarea class="form-control" name="descripcion_foto[]" placeholder="Descripción de la foto"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-info btn-sm mt-2" id="agregarFoto">
                    <i class="fas fa-plus"></i> Agregar otra foto
                </button>
            </div>

            <script>
            $(document).ready(function() {
                $('#agregarFoto').click(function() {
                    const nuevaFoto = $('.foto-entrada:first').clone();
                    nuevaFoto.find('input[type="file"]').val('');
                    nuevaFoto.find('textarea').val('');
                    $('#fotosContainer').append(nuevaFoto);
                });
            });
            </script>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-arrow-right"></i> Siguiente - Firmar
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </form>
    </div>
</body>
</html>