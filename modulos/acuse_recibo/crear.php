<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

require_once '../../config/database.php';
require_once '../../models/AcuseRecibo.php';

$database = new Database();
$db = $database->getConnection();
$acuse = new AcuseRecibo($db);

// En la sección de procesamiento POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $foto = null;
    
    // Procesar foto de la cámara
    if (!empty($_POST['foto_camara'])) {
        $foto_data = $_POST['foto_camara'];
        $foto_data = str_replace('data:image/jpeg;base64,', '', $foto_data);
        $foto = $foto_data;
    }
    // Procesar foto subida
    else if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $foto_temp = $_FILES['foto']['tmp_name'];
        $foto_content = file_get_contents($foto_temp);
        $foto = base64_encode($foto_content);
    }

    $acuse->foto = $foto;
    $acuse->local = $_POST['local'];
    $acuse->sector = $_POST['sector'];
    $acuse->documento = $_POST['documento'];
    $acuse->foto = $foto;
    $acuse->jefe_encargado = $_POST['jefe_encargado'];
    $acuse->observaciones = $_POST['observaciones'];
    $acuse->firma_digital = $_POST['firma'];
    $acuse->tecnico_id = $_SESSION['user_id'];

    if($acuse->crear()) {
        // Registrar actividad
        require_once '../../config/ActivityLogger.php';
        ActivityLogger::logAccion(
            $_SESSION['user_id'],
            'acuse_recibo',
            'crear',
            "Nuevo acuse creado - Local: {$_POST['local']}, Documento: {$_POST['documento']}"
        );
        
        header("Location: index.php?mensaje=Acuse de recibo creado exitosamente");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Acuse de Recibo</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/signature_pad/1.5.3/signature-pad.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="row mb-3">
            <div class="col-md-6">
                <h2><i class="fas fa-file-alt"></i> Nuevo Acuse de Recibo</h2>
            </div>
            <div class="col-md-6 text-right">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
    
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="local">Local</label>
                                <input type="text" class="form-control" id="local" name="local" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="sector">Sector</label>
                                <input type="text" class="form-control" id="sector" name="sector" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="documento">Documento</label>
                        <input type="text" class="form-control" id="documento" name="documento" required>
                    </div>

                    <!-- En la sección del formulario donde está el input de foto -->
                    <div class="form-group">
                        <label>Foto del Documento</label>
                        <div class="row">
                            <div class="col-md-6">
                                <input type="file" class="form-control-file" id="foto" name="foto" accept="image/*">
                            </div>
                            <div class="col-md-6">
                                <button type="button" class="btn btn-secondary" id="toggleCamera">
                                    <i class="fas fa-camera"></i> Usar Cámara
                                </button>
                            </div>
                        </div>
                        
                        <div class="camera-container" style="display: none;">
                            <video id="video" autoplay playsinline></video>
                            <canvas id="canvas"></canvas>
                            <div class="camera-buttons">
                                <button type="button" class="btn btn-primary" id="capture">
                                    <i class="fas fa-camera"></i> Capturar Foto
                                </button>
                                <button type="button" class="btn btn-secondary" id="switchCamera">
                                    <i class="fas fa-sync"></i> Cambiar Cámara
                                </button>
                            </div>
                        </div>
                        
                        <img id="preview" class="preview-image mt-3" style="display: none;">
                        <input type="hidden" name="foto_camara" id="foto_camara">
                    </div>

                    <!-- Agregar este script al final del archivo -->
                    <script>
                    let stream;
                    let facingMode = 'environment';
                    const video = document.getElementById('video');
                    const canvas = document.getElementById('canvas');
                    const preview = document.getElementById('preview');
                    const toggleCamera = document.getElementById('toggleCamera');
                    const switchCamera = document.getElementById('switchCamera');
                    const captureButton = document.getElementById('capture');
                    const cameraContainer = document.querySelector('.camera-container');

                    toggleCamera.addEventListener('click', async () => {
                        if (cameraContainer.style.display === 'none') {
                            cameraContainer.style.display = 'block';
                            await startCamera();
                        } else {
                            stopCamera();
                            cameraContainer.style.display = 'none';
                        }
                    });

                    switchCamera.addEventListener('click', async () => {
                        facingMode = facingMode === 'environment' ? 'user' : 'environment';
                        await startCamera();
                    });

                    async function startCamera() {
                        if (stream) {
                            stopCamera();
                        }
                        try {
                            stream = await navigator.mediaDevices.getUserMedia({
                                video: { facingMode: facingMode }
                            });
                            video.srcObject = stream;
                        } catch (err) {
                            console.error('Error:', err);
                            alert('Error al acceder a la cámara');
                        }
                    }

                    function stopCamera() {
                        if (stream) {
                            stream.getTracks().forEach(track => track.stop());
                            stream = null;
                        }
                    }

                    captureButton.addEventListener('click', () => {
                        canvas.width = video.videoWidth;
                        canvas.height = video.videoHeight;
                        canvas.getContext('2d').drawImage(video, 0, 0);
                        const image = canvas.toDataURL('image/jpeg', 0.8);
                        preview.src = image;
                        preview.style.display = 'block';
                        document.getElementById('foto_camara').value = image;
                        stopCamera();
                        cameraContainer.style.display = 'none';
                    });

                    // Limpiar la cámara cuando se envía el formulario
                    document.querySelector('form').addEventListener('submit', () => {
                        stopCamera();
                    });
                    </script>
                    <div class="form-group">
                        <label for="jefe_encargado">Jefe/Encargado</label>
                        <input type="text" class="form-control" id="jefe_encargado" name="jefe_encargado" required>
                    </div>

                    <div class="form-group">
                        <label for="observaciones">Observaciones</label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="3"></textarea>
                    </div>

                    <!-- Reemplazar la sección de firma digital actual con esto -->
                    <div class="form-group">
                        <label>Firma Digital:</label>
                        <div class="signature-pad-container" style="background: white; border: 1px solid #ccc; border-radius: 4px; padding: 10px;">
                            <canvas id="signature-pad" style="width: 100%; height: 200px; border: 1px solid #e2e8f0; border-radius: 4px;"></canvas>
                            <div class="d-flex justify-content-end mt-2">
                                <button type="button" class="btn btn-danger btn-sm" id="clear-signature">
                                    <i class="fas fa-eraser"></i> Limpiar
                                </button>
                            </div>
                        </div>
                        <input type="hidden" name="firma" id="firma-input">
                    </div>

                    <!-- Asegúrate de que estos scripts estén antes del cierre del body -->
                    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        var canvas = document.getElementById('signature-pad');
                        var signaturePad = new SignaturePad(canvas, {
                            backgroundColor: 'rgb(255, 255, 255)'
                        });

                        // Ajustar tamaño del canvas
                        function resizeCanvas() {
                            var ratio = Math.max(window.devicePixelRatio || 1, 1);
                            canvas.width = canvas.offsetWidth * ratio;
                            canvas.height = canvas.offsetHeight * ratio;
                            canvas.getContext("2d").scale(ratio, ratio);
                        }

                        window.addEventListener("resize", resizeCanvas);
                        resizeCanvas();

                        // Limpiar firma
                        document.getElementById('clear-signature').addEventListener('click', function() {
                            signaturePad.clear();
                        });

                        // Guardar firma al enviar el formulario
                        document.querySelector('form').addEventListener('submit', function(e) {
                            if (!signaturePad.isEmpty()) {
                                var data = signaturePad.toDataURL();
                                document.getElementById('firma-input').value = data;
                            }
                        });
                    });
                    </script>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/signature_pad/1.5.3/signature-pad.min.js"></script>
    <script>
        // Preview de imagen
        document.getElementById('foto').onchange = function(e) {
            let reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview').src = e.target.result;
                document.getElementById('preview').style.display = 'block';
            }
            reader.readAsDataURL(this.files[0]);
        };

        // Firma digital
        var canvas = document.getElementById('signature-pad');
        var signaturePad = new SignaturePad(canvas);

        document.getElementById('clear-signature').addEventListener('click', function() {
            signaturePad.clear();
        });

        document.querySelector('form').addEventListener('submit', function(e) {
            if (!signaturePad.isEmpty()) {
                document.getElementById('firma').value = signaturePad.toDataURL();
            }
        });
    </script>
</body>
</html>