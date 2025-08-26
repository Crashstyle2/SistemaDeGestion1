<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

// Verificar que existan los datos temporales
if(!isset($_SESSION['informe_temp'])) {
    header("Location: crear.php");
    exit;
}

require_once '../../config/database.php';
require_once '../../models/InformeTecnico.php';
require_once '../../models/RegistroActividad.php';

// Procesar la firma y guardar el informe completo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['firma_digital'])) {
    $database = new Database();
    $db = $database->getConnection();
    $informe = new InformeTecnico($db);
    
    // Crear el informe con todos los datos
    $datos_informe = $_SESSION['informe_temp'];
    $datos_informe['firma_digital'] = $_POST['firma_digital'];
    $datos_informe['tecnico_id'] = $_SESSION['user_id'];
    
    $informe_id = $informe->crear($datos_informe);
    
    if($informe_id) {
        // Procesar las fotos desde archivos temporales
        if(isset($_SESSION['fotos_temp']) && !empty($_SESSION['fotos_temp'])) {
            $directorio_final = '../../img/informe_tecnicos/fotos/';
            if (!file_exists($directorio_final)) {
                mkdir($directorio_final, 0755, true);
            }
            
            foreach($_SESSION['fotos_temp'] as $foto_temp) {
                $archivo_temp = '../../img/temp/' . $foto_temp['archivo_temp'];
                
                if(file_exists($archivo_temp)) {
                    $extension = pathinfo($foto_temp['archivo_temp'], PATHINFO_EXTENSION);
                    $nombre_final = 'informe_' . $informe_id . '_' . time() . '_' . uniqid() . '.' . $extension;
                    $ruta_final = $directorio_final . $nombre_final;
                    
                    if(rename($archivo_temp, $ruta_final)) {
                        // Insertar en la base de datos
                        $query = "INSERT INTO fotos_informe_tecnico (informe_id, foto, foto_ruta, descripcion, tipo) VALUES (?, ?, ?, ?, ?)";
                        $stmt = $db->prepare($query);
                        
                        $tipo = $foto_temp['tipo'];
                        if (!in_array($tipo, ['antes', 'despues'])) {
                            $tipo = 'antes';
                        }
                        
                        $stmt->execute([
                            $informe_id,
                            null,
                            $nombre_final,
                            $foto_temp['descripcion'],
                            $tipo
                        ]);
                    }
                }
            }
        }
        
        // Registrar la actividad
        require_once '../../config/ActivityLogger.php';
        ActivityLogger::logAccion(
            $_SESSION['user_id'],
            'informe_tecnico',
            'crear',
            "Nuevo informe técnico creado - Local: {$_SESSION['informe_temp']['local']}, Patrimonio: {$_SESSION['informe_temp']['patrimonio']}"
        );
        
        // Limpiar datos temporales
        unset($_SESSION['informe_temp']);
        unset($_SESSION['fotos_temp']);
        
        // Limpiar archivos temporales restantes
        $directorio_temp = '../../img/temp/';
        if(is_dir($directorio_temp)) {
            $archivos = glob($directorio_temp . 'temp_*');
            foreach($archivos as $archivo) {
                if(filemtime($archivo) < (time() - 3600)) { // Eliminar archivos de más de 1 hora
                    unlink($archivo);
                }
            }
        }
        
        header("Location: index.php?mensaje=Informe creado exitosamente");
        exit;
    } else {
        $error = "Error al guardar el informe. Por favor, intente nuevamente.";
    }
}

$datos = $_SESSION['informe_temp'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firmar Informe Técnico</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .resumen-datos {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 2rem;
        }
        .signature-container {
            position: relative;
            margin: 20px 0;
        }
        .signature-controls {
            position: absolute;
            top: -40px;
            right: 0;
            z-index: 100;
        }
        .signature-pad {
            border: 2px solid #007bff;
            border-radius: 8px;
            background-color: #fff;
            width: 100%;
            height: 300px;
            cursor: crosshair;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        /* Estilos de expandir removidos por solicitud del usuario */
        .gap-3 > * + * {
            margin-left: 1rem;
        }
        
        @media (max-width: 767px) {
            .gap-3 > * + * {
                margin-left: 0;
            }
            .d-flex.justify-content-between {
                flex-direction: column;
                align-items: flex-start !important;
            }
            .d-flex.justify-content-between .btn {
                align-self: center;
                margin-top: 10px;
                font-size: 14px;
                padding: 8px 16px;
            }
        }
        .firma-instrucciones {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        /* Estilos de modal-open removidos (funcionalidad de expandir eliminada) */
        
        .signature-pad {
            touch-action: none;
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            -khtml-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        
        /* Mejorar visibilidad en móviles */
        @media (max-width: 480px) {
            .signature-pad {
                height: 250px;
            }
            .container {
                padding-left: 10px;
                padding-right: 10px;
            }
            .resumen-datos {
                font-size: 14px;
            }
            .firma-instrucciones {
                font-size: 13px;
            }
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h2 class="mb-4">Firmar Informe Técnico</h2>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <!-- Resumen de datos ingresados -->
        <div class="resumen-datos">
            <h5><i class="fas fa-clipboard-list"></i> Resumen del Informe</h5>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Local:</strong> <?php echo htmlspecialchars($datos['local']); ?></p>
                    <p><strong>Sector:</strong> <?php echo htmlspecialchars($datos['sector']); ?></p>
                    <p><strong>Orden de Trabajo:</strong> <?php echo htmlspecialchars($datos['orden_trabajo']); ?></p>
                    <p><strong>Equipo con Problema:</strong> <?php echo htmlspecialchars($datos['equipo_asistido']); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Nº de Patrimonio:</strong> <?php echo htmlspecialchars($datos['patrimonio']); ?></p>
                    <p><strong>Jefe de Turno:</strong> <?php echo htmlspecialchars($datos['jefe_turno']); ?></p>
                    <p><strong>Observaciones:</strong> <?php echo nl2br(htmlspecialchars($datos['observaciones'])); ?></p>
                </div>
            </div>
            <?php if(isset($_SESSION['fotos_temp']) && !empty($_SESSION['fotos_temp'])): ?>
                <p><strong>Fotos adjuntas:</strong> <?php echo count($_SESSION['fotos_temp']); ?> archivo(s)</p>
            <?php endif; ?>
        </div>
        
        <form method="POST" id="firmaForm">
            <div class="firma-instrucciones">
                <h6><i class="fas fa-info-circle"></i> Instrucciones para firmar:</h6>
                <ul class="mb-0">
                    <li>Use el mouse o toque la pantalla para dibujar su firma</li>
                    <li>Use el botón "Limpiar" si necesita volver a firmar</li>
                    <li>Una vez satisfecho con su firma, haga clic en "Guardar Informe"</li>
                </ul>
            </div>
            
            <div class="form-group">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="mb-0"><strong>Firma Digital:</strong></label>
                    <button type="button" class="btn btn-sm btn-warning" id="limpiarFirma">
                        <i class="fas fa-eraser"></i> Limpiar
                    </button>
                </div>
                <canvas id="signatureCanvas" class="signature-pad"></canvas>
                <input type="hidden" name="firma_digital" id="firma_digital" required>
                <div id="firmaConfirmacion" class="text-success mt-2" style="display: none;">
                    <i class="fas fa-check-circle"></i> Firma capturada correctamente
                </div>
            </div>
            
            <div class="form-group mt-4">
                <div class="d-flex flex-column flex-md-row justify-content-center gap-3">
                    <button type="submit" class="btn btn-success btn-lg mb-2 mb-md-0" id="guardarBtn" disabled>
                        <i class="fas fa-save"></i> Guardar Informe
                    </button>
                    <a href="crear.php" class="btn btn-secondary btn-lg">
                        <i class="fas fa-arrow-left"></i> Volver a Datos
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
    $(document).ready(function() {
        const canvas = document.getElementById('signatureCanvas');
        const ctx = canvas.getContext('2d');
        let isDrawing = false;
        let lastX = 0;
        let lastY = 0;
        let hasSigned = false;

        function initCanvas() {
            // Esperar un momento para que el DOM se actualice completamente
            setTimeout(function() {
                const rect = canvas.getBoundingClientRect();
                const devicePixelRatio = window.devicePixelRatio || 1;
                
                // Establecer el tamaño del canvas
                canvas.width = rect.width * devicePixelRatio;
                canvas.height = rect.height * devicePixelRatio;
                
                // Escalar el contexto para que coincida con el ratio de píxeles del dispositivo
                ctx.scale(devicePixelRatio, devicePixelRatio);
                
                // Configurar el estilo del canvas
                canvas.style.width = rect.width + 'px';
                canvas.style.height = rect.height + 'px';
                
                ctx.strokeStyle = '#000000';
                ctx.lineWidth = window.innerWidth <= 768 ? 4 : 3; // Línea más gruesa en móviles
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';
                
                // Fondo blanco
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, rect.width, rect.height);
            }, 50);
        }

        // Funcionalidad de expandir removida por solicitud del usuario

        function getMousePos(e) {
            const rect = canvas.getBoundingClientRect();
            
            if (e.type.includes('touch')) {
                return {
                    x: e.touches[0].clientX - rect.left,
                    y: e.touches[0].clientY - rect.top
                };
            }
            return {
                x: e.clientX - rect.left,
                y: e.clientY - rect.top
            };
        }

        function startDrawing(e) {
            e.preventDefault();
            e.stopPropagation();
            isDrawing = true;
            const pos = getMousePos(e);
            [lastX, lastY] = [pos.x, pos.y];
            
            // Prevenir scroll en móviles
            if (e.type.includes('touch')) {
                document.body.style.overflow = 'hidden';
            }
        }

        function draw(e) {
            if (!isDrawing) return;
            e.preventDefault();
            e.stopPropagation();
            
            const pos = getMousePos(e);
            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
            [lastX, lastY] = [pos.x, pos.y];
            
            hasSigned = true;
            updateUI();
        }

        function stopDrawing(e) {
            if (e) {
                e.preventDefault();
            }
            isDrawing = false;
            
            // Restaurar scroll en móviles si no está expandido
            if (!$('#signatureCanvas').hasClass('expanded')) {
                document.body.style.overflow = '';
            }
        }
        
        function updateUI() {
            if(hasSigned) {
                $('#firmaConfirmacion').show();
                $('#guardarBtn').prop('disabled', false);
            } else {
                $('#firmaConfirmacion').hide();
                $('#guardarBtn').prop('disabled', true);
            }
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

        $('#limpiarFirma').click(function() {
            const rect = canvas.getBoundingClientRect();
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, rect.width, rect.height);
            hasSigned = false;
            updateUI();
        });

        $('#firmaForm').on('submit', function(e) {
            if(!hasSigned) {
                e.preventDefault();
                alert('Por favor, firme antes de guardar el informe.');
                return false;
            }
            
            const firma = canvas.toDataURL('image/png');
            $('#firma_digital').val(firma);
        });

        // Inicializar
        initCanvas();
        updateUI();
        
        // Event listeners del backdrop modal removidos (funcionalidad de expandir eliminada)
        
        // Redimensionar
        $(window).on('resize', function() {
            if(!$('#signatureCanvas').hasClass('expanded')) {
                setTimeout(initCanvas, 100);
            }
        });
    });
    </script>
</body>
</html>