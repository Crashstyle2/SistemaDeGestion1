<?php
session_start();  // Agregar session_start()
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

require_once '../../config/database.php';
require_once '../../models/InformeTecnico.php';
require_once '../../models/RegistroActividad.php';  // Agregamos el modelo faltante

// Modificar la sección de procesamiento POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $informe = new InformeTecnico($db);
    $registro = new RegistroActividad($db);

    // Crear el informe primero
    $informe_id = $informe->crear([
        'local' => $_POST['local'],
        'sector' => $_POST['sector'],
        'equipo_asistido' => $_POST['equipo_asistido'],
        'orden_trabajo' => $_POST['orden_trabajo'],
        'patrimonio' => $_POST['patrimonio'],
        'jefe_turno' => $_POST['jefe_turno'],
        'observaciones' => $_POST['observaciones'],
        'firma_digital' => $_POST['firma_digital'],
        'tecnico_id' => $_SESSION['user_id']
    ]);

    if($informe_id) {
        // Procesar las fotos
        $fotos = [];
        foreach($_FILES['fotos']['tmp_name'] as $key => $tmp_name) {
            if($_FILES['fotos']['error'][$key] == 0) {
                $fotos[] = [
                    'foto' => base64_encode(file_get_contents($tmp_name)),
                    'descripcion' => $_POST['descripcion_foto'][$key],
                    'tipo' => $_POST['tipo_foto'][$key]
                ];
            }
        }
        
        // Guardar las fotos
        if(!empty($fotos)) {
            $informe->guardarFotos($informe_id, $fotos);
        }

        // Registrar la actividad con el nuevo formato
        require_once '../../config/ActivityLogger.php';
        ActivityLogger::logAccion(
            $_SESSION['user_id'],
            'informe_tecnico',
            'crear',
            "Nuevo informe técnico creado - Local: {$_POST['local']}, Patrimonio: {$_POST['patrimonio']}"
        );
        
        header("Location: index.php?mensaje=Informe creado exitosamente");
        exit;
    }
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
            
            <!-- Componente de Firma Digital -->
            <div class="form-group">
                <label>Firma Digital:</label>
                <div class="signature-container">
                    <div class="signature-controls">
                        <button type="button" class="btn btn-sm btn-info expand-canvas" title="Expandir área de firma">
                            <i class="fas fa-expand-arrows-alt"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" id="limpiarFirma">
                            <i class="fas fa-eraser"></i> Limpiar
                        </button>
                    </div>
                    <canvas id="signatureCanvas" class="signature-pad"></canvas>
                </div>
                <input type="hidden" name="firma_digital" id="firma_digital">
                <div id="firmaConfirmacion" class="text-success mt-2" style="display: none;">
                    <i class="fas fa-check-circle"></i> Firma guardada
                </div>
            </div>

            <style>
                .signature-container {
                    position: relative;
                    margin: 20px 0;
                }
                .signature-controls {
                    position: absolute;
                    top: -30px;
                    right: 0;
                    z-index: 100;
                }
                .signature-pad {
                    border: 1px solid #ccc;
                    border-radius: 4px;
                    background-color: #fff;
                    width: 100%;
                    height: 200px;
                    cursor: crosshair;
                }
                .signature-pad.expanded {
                    position: fixed;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    width: 80vw;
                    height: 60vh;
                    z-index: 1000;
                    box-shadow: 0 0 20px rgba(0,0,0,0.2);
                }
                .modal-backdrop.show {
                    opacity: 0.5;
                    display: block;
                }
            </style>

            <script>
            $(document).ready(function() {
                const canvas = document.getElementById('signatureCanvas');
                const ctx = canvas.getContext('2d');
                let isDrawing = false;
                let lastX = 0;
                let lastY = 0;

                function initCanvas() {
                    const rect = canvas.getBoundingClientRect();
                    canvas.width = rect.width;
                    canvas.height = rect.height;
                    ctx.strokeStyle = '#000000';
                    ctx.lineWidth = 2;
                    ctx.lineCap = 'round';
                    ctx.lineJoin = 'round';
                }

                $('.expand-canvas').click(function() {
                    $('#signatureCanvas').toggleClass('expanded');
                    if($('#signatureCanvas').hasClass('expanded')) {
                        $('body').append('<div class="modal-backdrop show"></div>');
                    } else {
                        $('.modal-backdrop').remove();
                    }
                    initCanvas();
                });

                function getMousePos(e) {
                    const rect = canvas.getBoundingClientRect();
                    const scaleX = canvas.width / rect.width;
                    const scaleY = canvas.height / rect.height;
                    
                    if (e.type.includes('touch')) {
                        return {
                            x: (e.touches[0].clientX - rect.left) * scaleX,
                            y: (e.touches[0].clientY - rect.top) * scaleY
                        };
                    }
                    return {
                        x: (e.clientX - rect.left) * scaleX,
                        y: (e.clientY - rect.top) * scaleY
                    };
                }

                function startDrawing(e) {
                    e.preventDefault();
                    isDrawing = true;
                    const pos = getMousePos(e);
                    [lastX, lastY] = [pos.x, pos.y];
                }

                function draw(e) {
                    if (!isDrawing) return;
                    e.preventDefault();
                    
                    const pos = getMousePos(e);
                    ctx.beginPath();
                    ctx.moveTo(lastX, lastY);
                    ctx.lineTo(pos.x, pos.y);
                    ctx.stroke();
                    [lastX, lastY] = [pos.x, pos.y];
                }

                function stopDrawing() {
                    isDrawing = false;
                }

                // Mouse Events
                canvas.addEventListener('mousedown', startDrawing);
                canvas.addEventListener('mousemove', draw);
                canvas.addEventListener('mouseup', stopDrawing);
                canvas.addEventListener('mouseout', stopDrawing);

                // Touch Events
                canvas.addEventListener('touchstart', startDrawing);
                canvas.addEventListener('touchmove', draw);
                canvas.addEventListener('touchend', stopDrawing);

                $('#modalFirma').on('shown.bs.modal', function() {
                    initCanvas();
                    // Ajustar tamaño del canvas al modal
                    canvas.width = canvas.offsetWidth;
                    canvas.height = canvas.offsetHeight;
                });

                $('#limpiarFirma').click(function() {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                });

                $('#guardarFirma').click(function() {
                    const firma = canvas.toDataURL('image/png');
                    $('#firma_digital').val(firma);
                    $('#firmaConfirmacion').show();
                    $('#modalFirma').modal('hide');
                });
            });
            </script>
            <script>
            $(document).ready(function() {
                const canvas = document.getElementById('signatureCanvas');
                const ctx = canvas.getContext('2d');
                let isDrawing = false;
                let lastX = 0;
                let lastY = 0;

                function initCanvas() {
                    const rect = canvas.getBoundingClientRect();
                    canvas.width = rect.width;
                    canvas.height = rect.height;
                    ctx.strokeStyle = '#000000';
                    ctx.lineWidth = 2;
                    ctx.lineCap = 'round';
                    ctx.lineJoin = 'round';
                }

                $('.expand-canvas').click(function() {
                    $('#signatureCanvas').toggleClass('expanded');
                    if($('#signatureCanvas').hasClass('expanded')) {
                        $('body').append('<div class="modal-backdrop show"></div>');
                    } else {
                        $('.modal-backdrop').remove();
                    }
                    initCanvas();
                });

                function getMousePos(e) {
                    const rect = canvas.getBoundingClientRect();
                    const scaleX = canvas.width / rect.width;
                    const scaleY = canvas.height / rect.height;
                    
                    if (e.type.includes('touch')) {
                        return {
                            x: (e.touches[0].clientX - rect.left) * scaleX,
                            y: (e.touches[0].clientY - rect.top) * scaleY
                        };
                    }
                    return {
                        x: (e.clientX - rect.left) * scaleX,
                        y: (e.clientY - rect.top) * scaleY
                    };
                }

                function startDrawing(e) {
                    e.preventDefault();
                    isDrawing = true;
                    const pos = getMousePos(e);
                    [lastX, lastY] = [pos.x, pos.y];
                }

                function draw(e) {
                    if (!isDrawing) return;
                    e.preventDefault();
                    
                    const pos = getMousePos(e);
                    ctx.beginPath();
                    ctx.moveTo(lastX, lastY);
                    ctx.lineTo(pos.x, pos.y);
                    ctx.stroke();
                    [lastX, lastY] = [pos.x, pos.y];
                }

                function stopDrawing() {
                    isDrawing = false;
                }

                // Mouse Events y Modificar el evento submit del formulario
                $('#informeForm').on('submit', function(e) {
                    e.preventDefault();
                    const firma = canvas.toDataURL('image/png');
                    $('#firma_digital').val(firma);
                    this.submit();
                });

                // Agregar eventos de redimensionamiento y inicialización
                $(window).on('resize', function() {
                    if(!$('#signatureCanvas').hasClass('expanded')) {
                        initCanvas();
                    }
                });

                // Inicializar el canvas
                initCanvas();
            });
            </script>

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

            <!-- Remove old single photo upload section -->
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Informe
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </form>
    </div>
</body>
</html>