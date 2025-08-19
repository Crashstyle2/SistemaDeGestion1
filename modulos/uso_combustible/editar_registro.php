<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

require_once '../../config/Database.php';
require_once '../../models/UsoCombustible.php';
require_once '../../config/ActivityLogger.php'; // AGREGAR ESTA LÍNEA

// Verificar el rol del usuario
$rol = $_SESSION['user_rol'];

if (!in_array($rol, ['tecnico', 'supervisor', 'administrador'])) {
    header("Location: ../../dashboard.php");
    exit;
}

// Verificar permisos de edición para rol administrativo
if ($rol === 'administrativo') {
    header("Location: ver_registros.php?error=sin_permisos");
    exit;
}

// Verificar que se proporcione un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ver_registros.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Obtener sucursales para los campos de origen y destino
$query_sucursales = "SELECT DISTINCT local FROM sucursales ORDER BY local";
$stmt_sucursales = $db->prepare($query_sucursales);
$stmt_sucursales->execute();
$sucursales = $stmt_sucursales->fetchAll(PDO::FETCH_ASSOC);

$registro_id = $_GET['id'];

// Obtener datos del registro
$query = "SELECT uc.*, ucr.origen, ucr.destino, ucr.km_sucursales, ucr.comentarios_sector 
          FROM uso_combustible uc 
          LEFT JOIN uso_combustible_recorridos ucr ON uc.id = ucr.uso_combustible_id 
          WHERE uc.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $registro_id);
$stmt->execute();
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($registros)) {
    header("Location: ver_registros.php");
    exit;
}

$registro_principal = $registros[0];
$recorridos = [];
foreach ($registros as $reg) {
    if (!empty($reg['origen']) && !empty($reg['destino'])) {
        $recorridos[] = [
            'origen' => $reg['origen'],
<<<<<<< HEAD
            'destino' => $reg['destino'],
            'km_sucursales' => $reg['km_sucursales'],
            'comentarios_sector' => $reg['comentarios_sector']
=======
            'destino' => $reg['destino']
>>>>>>> ef2bc8c156abe68b036b639c0ea6b5add6465c9d
        ];
    }
}

// Si no hay recorridos, agregar uno vacío
if (empty($recorridos)) {
<<<<<<< HEAD
    $recorridos[] = ['origen' => '', 'destino' => '', 'km_sucursales' => '', 'comentarios_sector' => ''];
=======
    $recorridos[] = ['origen' => '', 'destino' => ''];
>>>>>>> ef2bc8c156abe68b036b639c0ea6b5add6465c9d
}

// Procesar actualización si es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // Procesar foto del voucher si se subió una nueva
        $foto_voucher_ruta_nueva = $registro_principal['foto_voucher_ruta']; // Mantener la actual por defecto
        
        // Debug: Log inicial
        error_log("DEBUG: Iniciando actualización. Foto actual: " . $registro_principal['foto_voucher_ruta']);
        
        if (isset($_FILES['foto_voucher']) && $_FILES['foto_voucher']['error'] === UPLOAD_ERR_OK) {
            error_log("DEBUG: Nueva foto detectada. Nombre: " . $_FILES['foto_voucher']['name']);
            
            // Crear directorio si no existe - USAR LA MISMA LÓGICA QUE FUNCIONA EN EL TEST
            $directorio = __DIR__ . '/../../img/uso_combustible/vouchers/';
            if (!file_exists($directorio)) {
                mkdir($directorio, 0755, true);
                error_log("DEBUG: Directorio creado: " . $directorio);
            }
            
            // Eliminar foto anterior si existe
            if (!empty($registro_principal['foto_voucher_ruta'])) {
                $foto_anterior = $directorio . $registro_principal['foto_voucher_ruta'];
                if (file_exists($foto_anterior)) {
                    unlink($foto_anterior);
                    error_log("DEBUG: Foto anterior eliminada: " . $foto_anterior);
                }
            }
            
            // Generar nombre único para el archivo nuevo
            $extension = pathinfo($_FILES['foto_voucher']['name'], PATHINFO_EXTENSION);
            if (empty($extension)) {
                $extension = 'jpg';
            }
            
            $nombre_archivo = 'voucher_' . time() . '_' . uniqid() . '.' . $extension;
            $ruta_completa = $directorio . $nombre_archivo;
            
            error_log("DEBUG: Nuevo nombre de archivo: " . $nombre_archivo);
            error_log("DEBUG: Ruta completa: " . $ruta_completa);
            
            // Mover archivo subido
            if (move_uploaded_file($_FILES['foto_voucher']['tmp_name'], $ruta_completa)) {
                $foto_voucher_ruta_nueva = $nombre_archivo;
                error_log("DEBUG: Archivo movido exitosamente. Nueva ruta: " . $foto_voucher_ruta_nueva);
            } else {
                error_log("DEBUG: Error al mover archivo. Error: " . error_get_last()['message']);
                throw new Exception('Error al guardar la nueva foto del voucher');
            }
        } else {
            error_log("DEBUG: No se detectó nueva foto o hay error en upload");
        }
        
        // Usar el modelo para actualizar
        $usoCombustible = new UsoCombustible($db);
        $usoCombustible->id = $registro_id;
        $usoCombustible->fecha = $_POST['fecha'];
        $usoCombustible->conductor = $_POST['conductor'];
        $usoCombustible->chapa = $_POST['chapa'];
        $usoCombustible->numero_voucher = $_POST['numero_voucher'];
        $usoCombustible->tarjeta = $_POST['tarjeta'];
        $usoCombustible->litros_cargados = $_POST['litros_cargados'];
        $usoCombustible->tipo_vehiculo = $_POST['tipo_vehiculo'];
        $usoCombustible->documento = $_POST['documento'];
        $usoCombustible->fecha_carga = $_POST['fecha_carga'];
        $usoCombustible->hora_carga = $_POST['hora_carga'];
        $usoCombustible->foto_voucher_ruta = $foto_voucher_ruta_nueva;
        
        error_log("DEBUG: Datos preparados para actualizar. Foto ruta: " . $foto_voucher_ruta_nueva);
        
        if (!$usoCombustible->actualizar()) {
            error_log("DEBUG: Error en método actualizar()");
            throw new Exception('Error al actualizar el registro principal');
        }
        
        error_log("DEBUG: Registro principal actualizado exitosamente");
        
        // Eliminar recorridos existentes
        $deleteQuery = "DELETE FROM uso_combustible_recorridos WHERE uso_combustible_id = :id";
        $stmt = $db->prepare($deleteQuery);
        $stmt->bindParam(':id', $registro_id);
        $stmt->execute();
        
        // Insertar nuevos recorridos
<<<<<<< HEAD
        if (isset($_POST['origen']) && isset($_POST['destino']) && isset($_POST['km_sucursales'])) {
            $insertRecorridoQuery = "INSERT INTO uso_combustible_recorridos (uso_combustible_id, origen, destino, km_sucursales, comentarios_sector) VALUES (:uso_combustible_id, :origen, :destino, :km_sucursales, :comentarios_sector)";
=======
        if (isset($_POST['origen']) && isset($_POST['destino'])) {
            $insertRecorridoQuery = "INSERT INTO uso_combustible_recorridos (uso_combustible_id, origen, destino) VALUES (:uso_combustible_id, :origen, :destino)";
>>>>>>> ef2bc8c156abe68b036b639c0ea6b5add6465c9d
            $stmt = $db->prepare($insertRecorridoQuery);
            
            for ($i = 0; $i < count($_POST['origen']); $i++) {
                if (!empty($_POST['origen'][$i]) && !empty($_POST['destino'][$i])) {
<<<<<<< HEAD
                    $comentarios_sector = isset($_POST['comentarios_sector'][$i]) ? $_POST['comentarios_sector'][$i] : '';
                    
                    $stmt->bindParam(':uso_combustible_id', $registro_id);
                    $stmt->bindParam(':origen', $_POST['origen'][$i]);
                    $stmt->bindParam(':destino', $_POST['destino'][$i]);
                    $stmt->bindParam(':km_sucursales', $_POST['km_sucursales'][$i]);
                    $stmt->bindParam(':comentarios_sector', $comentarios_sector);
=======
                    $stmt->bindParam(':uso_combustible_id', $registro_id);
                    $stmt->bindParam(':origen', $_POST['origen'][$i]);
                    $stmt->bindParam(':destino', $_POST['destino'][$i]);
>>>>>>> ef2bc8c156abe68b036b639c0ea6b5add6465c9d
                    $stmt->execute();
                }
            }
        }
        
        $db->commit();
        error_log("DEBUG: Transacción confirmada exitosamente");
<<<<<<< HEAD
        
        // Registrar actividad de edición
        ActivityLogger::logAccion(
            $_SESSION['user_id'],
            'uso_combustible',
            'editar',
            "Registro de combustible editado - ID: {$registro_id}, Conductor: {$_POST['conductor']}, Chapa: {$_POST['chapa']}"
        );
        
=======
>>>>>>> ef2bc8c156abe68b036b639c0ea6b5add6465c9d
        echo json_encode(['success' => true, 'message' => 'Registro actualizado correctamente']);
        exit;
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("DEBUG: Error capturado: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Registro de Uso de Combustible</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .modal-error {
            border-left: 5px solid #dc3545;
        }
        .modal-success {
            border-left: 5px solid #28a745;
        }
        .modal-warning {
            border-left: 5px solid #ffc107;
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .loading-spinner {
            color: white;
            font-size: 2rem;
        }
        
        /* Estilos para el modal de selección */
        .searchable-dropdown {
            display: none !important; /* Ocultar dropdowns originales */
        }
        
        .searchable-input {
            cursor: pointer;
            background-color: white;
        }
        
        .searchable-input:focus {
            cursor: text;
        }
        
        .selection-modal .modal-body {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .option-item {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .option-item:hover {
            background-color: #f8f9fa;
        }
        
        .option-item:last-child {
            border-bottom: none;
        }
        
        .search-modal-input {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <!-- Modal para selección de sucursales -->
    <div class="modal fade" id="selectionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered selection-modal" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="selectionModalTitle">Seleccionar Sucursal</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="text" class="form-control search-modal-input" id="modalSearchInput" placeholder="Buscar sucursal...">
                    <div id="modalOptions"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para mostrar foto actual -->
    <div id="fotoActualModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-image mr-2"></i>Foto Actual del Voucher</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <img id="fotoActualImg" src="" alt="Foto actual" class="img-fluid" style="max-height: 500px;">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="text-center">
            <i class="fas fa-spinner fa-spin loading-spinner"></i>
            <div class="text-white mt-2">Actualizando registro...</div>
        </div>
    </div>

    <!-- Modal para mensajes -->
    <div class="modal fade" id="messageModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" id="modalContent">
                <div class="modal-body text-center p-4">
                    <div class="d-flex align-items-center justify-content-center mb-3">
                        <i id="modalIcon" class="fas fa-check-circle text-success mr-3" style="font-size: 2rem;"></i>
                        <h5 id="modalTitle" class="mb-0">Título</h5>
                    </div>
                    <p id="modalMessage" class="mb-2">Mensaje</p>
                    <small id="modalDetails" class="text-muted"></small>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" id="modalButton" class="btn btn-success" data-dismiss="modal">Aceptar</button>
                </div>
            </div>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="../../dashboard.php">
                <i class="fas fa-home mr-2"></i>Inicio
            </a>
            <div class="navbar-text text-white">
                <i class="fas fa-user mr-2"></i>
                Bienvenido, <?php echo isset($_SESSION['nombre']) ? htmlspecialchars($_SESSION['nombre']) : 'Usuario'; ?>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-warning text-dark">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-edit mr-2"></i>Editar Registro de Uso de Combustible
                            </h5>
                            <a href="ver_registros.php" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left mr-2"></i>Volver a Registros
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle mr-2"></i><?php echo $success_message; ?>
                                <button type="button" class="close" data-dismiss="alert">
                                    <span>&times;</span>
                                </button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error_message; ?>
                                <button type="button" class="close" data-dismiss="alert">
                                    <span>&times;</span>
                                </button>
                            </div>
                        <?php endif; ?>
                        
                        <form id="formCombustible" method="POST">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="fecha">Fecha</label>
                                    <input type="date" class="form-control" id="fecha" name="fecha" value="<?php echo htmlspecialchars($registro_principal['fecha']); ?>" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="conductor">Conductor</label>
                                    <input type="text" class="form-control" id="conductor" name="conductor" value="<?php echo htmlspecialchars($registro_principal['nombre_conductor']); ?>" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="chapa">Vehículo Chapa</label>
                                    <input type="text" class="form-control" id="chapa" name="chapa" value="<?php echo htmlspecialchars($registro_principal['chapa']); ?>" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="numero_voucher">Nº Voucher</label>
                                    <input type="text" class="form-control" id="numero_voucher" name="numero_voucher" value="<?php echo htmlspecialchars($registro_principal['numero_baucher']); ?>" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="tarjeta">Nº tarjeta (últimos 4 dígitos)</label>
                                    <input type="text" class="form-control" id="tarjeta" name="tarjeta" maxlength="4" value="<?php echo htmlspecialchars($registro_principal['tarjeta']); ?>">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="litros_cargados">Litros Cargados</label>
                                    <input type="number" step="0.01" class="form-control" id="litros_cargados" name="litros_cargados" value="<?php echo htmlspecialchars($registro_principal['litros_cargados']); ?>" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="tipo_vehiculo">Tipo de Vehículo</label>
                                    <select class="form-control" id="tipo_vehiculo" name="tipo_vehiculo" required>
                                        <option value="">Seleccione tipo de vehículo</option>
                                        <option value="particular" <?php echo ($registro_principal['tipo_vehiculo'] === 'particular') ? 'selected' : ''; ?>>Particular</option>
                                        <option value="movil_retail" <?php echo ($registro_principal['tipo_vehiculo'] === 'movil_retail') ? 'selected' : ''; ?>>Móvil de Retail</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="documento">Documento</label>
                                    <input type="text" class="form-control" id="documento" name="documento" value="<?php echo htmlspecialchars($registro_principal['documento']); ?>" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="fecha_carga">Fecha de Carga</label>
                                    <input type="date" class="form-control" id="fecha_carga" name="fecha_carga" value="<?php echo htmlspecialchars($registro_principal['fecha_carga']); ?>" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="hora_carga">Hora de Carga</label>
                                    <input type="time" class="form-control" id="hora_carga" name="hora_carga" value="<?php echo htmlspecialchars($registro_principal['hora_carga']); ?>" required>
                                </div>
                            </div>
                            
                            <!-- Campo para foto del voucher -->
                            <div class="form-row">
                                <div class="form-group col-md-12">
                                    <label for="foto_voucher">Foto del Voucher</label>
                                    
                                    <!-- Mostrar foto actual si existe -->
                                    <?php if (!empty($registro_principal['foto_voucher_ruta'])): ?>
                                    <div class="mb-3">
                                        <p class="text-muted mb-2"><i class="fas fa-image mr-1"></i>Foto actual:</p>
                                        <div class="d-flex align-items-center">
                                            <img src="../../img/uso_combustible/vouchers/<?php echo htmlspecialchars($registro_principal['foto_voucher_ruta']); ?>" 
                                                 alt="Foto actual del voucher" 
                                                 class="img-thumbnail mr-3" 
                                                 style="max-width: 150px; max-height: 150px; cursor: pointer;"
                                                 onclick="mostrarFotoActual()">
                                            <div>
                                                <button type="button" class="btn btn-sm btn-info" onclick="mostrarFotoActual()">
                                                    <i class="fas fa-eye mr-1"></i>Ver foto actual
                                                </button>
                                                <p class="text-muted mt-2 mb-0"><small>Haga clic en "Seleccionar nueva foto" para cambiarla</small></p>
                                            </div>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                    <div class="alert alert-info mb-3">
                                        <i class="fas fa-info-circle mr-2"></i>Este registro no tiene foto del voucher. Puede agregar una nueva.
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Input para nueva foto -->
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="foto_voucher" name="foto_voucher" accept="image/*">
                                        <label class="custom-file-label" for="foto_voucher">
                                            <?php echo !empty($registro_principal['foto_voucher_ruta']) ? 'Seleccionar nueva foto...' : 'Tomar foto del voucher...'; ?>
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-camera mr-1"></i>Toque para abrir la cámara y capturar una nueva foto del voucher
                                        <?php if (!empty($registro_principal['foto_voucher_ruta'])): ?>
                                        <br><strong>Nota:</strong> Si selecciona una nueva foto, reemplazará la foto actual.
                                        <?php endif; ?>
                                    </small>
                                    
                                    <!-- Vista previa de nueva foto -->
                                    <div id="preview_foto_voucher" class="mt-2" style="display: none;">
                                        <p class="text-success mb-2"><i class="fas fa-check mr-1"></i>Nueva foto seleccionada:</p>
                                        <img id="img_preview_voucher" src="" alt="Vista previa" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                                        <button type="button" class="btn btn-sm btn-danger ml-2" onclick="eliminarFotoVoucher()">
                                            <i class="fas fa-trash"></i> Cancelar cambio
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Sección de Recorridos -->
                            <h5 class="mb-3">Recorridos</h5>
                            <div id="recorridos-container">
                                <?php foreach ($recorridos as $index => $recorrido): ?>
                                <div class="recorrido-item border p-3 mb-3">
<<<<<<< HEAD
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0">
                                            <span class="badge badge-primary recorrido-numero"><?php echo $index + 1; ?>°</span>
                                            Recorrido #<span class="numero-recorrido"><?php echo $index + 1; ?></span>
                                        </h6>
                                        <?php if ($index > 0): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger eliminar-recorrido" onclick="eliminarRecorrido(this)">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-4">
=======
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
>>>>>>> ef2bc8c156abe68b036b639c0ea6b5add6465c9d
                                            <label>Origen</label>
                                            <div class="searchable-select">
                                                <input type="text" class="form-control searchable-input" 
                                                       placeholder="Click para seleccionar origen..." 
                                                       data-target="origen_<?php echo $index; ?>" 
                                                       autocomplete="off" readonly
                                                       value="<?php echo htmlspecialchars($recorrido['origen']); ?>">
                                                <input type="hidden" name="origen[]" id="origen_<?php echo $index; ?>" 
                                                       value="<?php echo htmlspecialchars($recorrido['origen']); ?>" required>
<<<<<<< HEAD
                                                <input type="hidden" name="orden_secuencial[]" value="<?php echo $index + 1; ?>">
                                            </div>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label>KM entre Sucursales</label>
                                            <input type="number" class="form-control" name="km_sucursales[]" 
                                                   placeholder="Ingrese KM aproximados" 
                                                   min="0" step="0.1" required
                                                   value="<?php echo htmlspecialchars($recorrido['km_sucursales'] ?? ''); ?>">
                                        </div>
                                        <div class="form-group col-md-4">
=======
                                            </div>
                                        </div>
                                        <div class="form-group col-md-6">
>>>>>>> ef2bc8c156abe68b036b639c0ea6b5add6465c9d
                                            <label>Destino</label>
                                            <div class="searchable-select">
                                                <input type="text" class="form-control searchable-input" 
                                                       placeholder="Click para seleccionar destino..." 
                                                       data-target="destino_<?php echo $index; ?>" 
                                                       autocomplete="off" readonly
                                                       value="<?php echo htmlspecialchars($recorrido['destino']); ?>">
                                                <input type="hidden" name="destino[]" id="destino_<?php echo $index; ?>" 
                                                       value="<?php echo htmlspecialchars($recorrido['destino']); ?>" required>
                                            </div>
                                        </div>
                                    </div>
<<<<<<< HEAD
                                    <!-- Campo de comentarios condicional -->
                                    <div class="form-row comentarios-sector" style="display: none;">
                                        <div class="form-group col-md-12">
                                            <label>Comentarios del Sector</label>
                                            <div class="alert alert-info mb-2">
                                                <i class="fas fa-info-circle mr-2"></i>
                                                <strong>Si vas a retirar repuesto, ¿para qué sector es?</strong>
                                                <br><small>Indique el departamento para imputar correctamente el gasto de combustible (ej: Marketing, Cuadratura, Mantic, etc.)</small>
                                            </div>
                                            <textarea class="form-control" name="comentarios_sector[]" 
                                                      placeholder="Ejemplo: Retiro repuesto para el sector de Marketing" 
                                                      rows="3"><?php echo isset($recorrido['comentarios_sector']) ? htmlspecialchars($recorrido['comentarios_sector']) : ''; ?></textarea>
                                        </div>
                                    </div>
=======
                                    <?php if ($index > 0): ?>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="eliminarRecorrido(this)">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                    <?php endif; ?>
>>>>>>> ef2bc8c156abe68b036b639c0ea6b5add6465c9d
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="text-right mb-3">
                                <button type="button" class="btn btn-secondary" onclick="agregarRecorrido()">
                                    <i class="fas fa-plus mr-2"></i>Agregar Recorrido
                                </button>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-warning btn-lg" id="submitBtn">
                                    <i class="fas fa-save mr-2"></i>Actualizar Registro
                                </button>
                                <a href="ver_registros.php" class="btn btn-secondary btn-lg ml-2">
                                    <i class="fas fa-times mr-2"></i>Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Variables globales para el modal de selección
    let currentInput = null;
    let currentHiddenInput = null;
    let sucursalesData = [];
    
    // Cargar datos de sucursales
    <?php 
    echo "sucursalesData = [";
    foreach($sucursales as $index => $sucursal) {
        if($index > 0) echo ",";
        echo "{value: '" . htmlspecialchars($sucursal['local']) . "', text: '" . htmlspecialchars($sucursal['local']) . "'}";
    }
    echo "];";
    ?>
    
    function initSearchableSelects() {
        $('.searchable-input').each(function() {
            const input = $(this);
            const hiddenInput = $('#' + input.data('target'));
            
            input.attr('readonly', true);
            
            input.on('click', function() {
                currentInput = input;
                currentHiddenInput = hiddenInput;
                
                const fieldType = input.closest('.form-group').find('label').text().trim();
                $('#selectionModalTitle').text('Seleccionar ' + fieldType);
                
                loadModalOptions();
                $('#selectionModal').modal('show');
            });
        });
    }
    
    function loadModalOptions(searchTerm = '') {
        const modalOptions = $('#modalOptions');
        modalOptions.empty();
        
        modalOptions.append(`
            <div class="option-item" data-value="">
                <i class="fas fa-times text-muted mr-2"></i>Limpiar selección
            </div>
        `);
        
        sucursalesData.forEach(function(sucursal) {
            if (searchTerm === '' || sucursal.text.toLowerCase().includes(searchTerm.toLowerCase())) {
                modalOptions.append(`
                    <div class="option-item" data-value="${sucursal.value}">
                        <i class="fas fa-building text-primary mr-2"></i>${sucursal.text}
                    </div>
                `);
            }
        });
        
        if (modalOptions.children().length === 1 && searchTerm !== '') {
            modalOptions.append(`
                <div class="option-item text-muted" style="cursor: default;">
                    <i class="fas fa-search mr-2"></i>No se encontraron resultados
                </div>
            `);
        }
    }
    
    $(document).ready(function() {
        initSearchableSelects();
        
<<<<<<< HEAD
        // Verificar comentarios existentes al cargar la página
        $('.recorrido-item').each(function() {
            const recorridoItem = $(this);
            const destinoInput = recorridoItem.find('input[name="destino[]"]');
            const comentariosSector = recorridoItem.find('.comentarios-sector');
            
            if (destinoInput.val() && (destinoInput.val().toLowerCase().includes('deposito') || destinoInput.val().toLowerCase().includes('depósito'))) {
                comentariosSector.show();
                comentariosSector.find('textarea').prop('required', true);
            }
        });
        
=======
>>>>>>> ef2bc8c156abe68b036b639c0ea6b5add6465c9d
        $('#modalSearchInput').on('input', function() {
            const searchTerm = $(this).val();
            loadModalOptions(searchTerm);
        });
        
<<<<<<< HEAD
=======
        $(document).on('click', '.option-item', function() {
            const value = $(this).data('value');
            const text = $(this).text().trim();
            
            if (currentInput && currentHiddenInput) {
                if (value === '') {
                    currentInput.val('');
                    currentHiddenInput.val('');
                } else {
                    currentInput.val(text.replace(/^[^\s]*\s/, ''));
                    currentHiddenInput.val(value);
                }
            }
            
            $('#selectionModal').modal('hide');
        });
        
>>>>>>> ef2bc8c156abe68b036b639c0ea6b5add6465c9d
        $('#selectionModal').on('hidden.bs.modal', function() {
            $('#modalSearchInput').val('');
            currentInput = null;
            currentHiddenInput = null;
        });
        
        // Inicializar eventos de foto del voucher
        $('#foto_voucher').on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Actualizar el label
                $(this).next('.custom-file-label').text(file.name);
                
                // Mostrar vista previa
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#img_preview_voucher').attr('src', e.target.result);
                    $('#preview_foto_voucher').show();
                };
                reader.readAsDataURL(file);
            }
        });
    });
    
<<<<<<< HEAD
    // Actualizar función agregarRecorrido para incluir KM y comentarios
    function agregarRecorrido() {
        const container = document.getElementById('recorridos-container');
        const recorridoCount = container.children.length;
        
        // Obtener el último destino para usarlo como origen del nuevo recorrido
        let ultimoDestino = '';
        let ultimoDestinoTexto = '';
        
        // Buscar el último recorrido existente
        const recorridosExistentes = container.querySelectorAll('.recorrido-item');
        if (recorridosExistentes.length > 0) {
            const ultimoRecorrido = recorridosExistentes[recorridosExistentes.length - 1];
            const ultimoDestinoInput = ultimoRecorrido.querySelector('input[name="destino[]"]');
            const ultimoDestinoVisible = ultimoRecorrido.querySelector('.searchable-input[data-target*="destino"]');
            
            if (ultimoDestinoInput && ultimoDestinoInput.value) {
                ultimoDestino = ultimoDestinoInput.value;
                ultimoDestinoTexto = ultimoDestinoVisible ? ultimoDestinoVisible.value : ultimoDestino;
            }
        }
        
        const nuevoRecorrido = document.createElement('div');
        nuevoRecorrido.className = 'recorrido-item border p-3 mb-3';
        nuevoRecorrido.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">
                    <span class="badge badge-primary recorrido-numero">${recorridoCount + 1}°</span>
                    Recorrido #<span class="numero-recorrido">${recorridoCount + 1}</span>
                </h6>
                <button type="button" class="btn btn-sm btn-outline-danger eliminar-recorrido" onclick="eliminarRecorrido(this)">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </div>
            <div class="form-row">
                <div class="form-group col-md-4">
=======
    // Actualizar función agregarRecorrido para usar dropdowns
    function agregarRecorrido() {
        const container = document.getElementById('recorridos-container');
        const recorridoCount = container.children.length;
        const nuevoRecorrido = document.createElement('div');
        nuevoRecorrido.className = 'recorrido-item border p-3 mb-3';
        nuevoRecorrido.innerHTML = `
            <div class="form-row">
                <div class="form-group col-md-6">
>>>>>>> ef2bc8c156abe68b036b639c0ea6b5add6465c9d
                    <label>Origen</label>
                    <div class="searchable-select">
                        <input type="text" class="form-control searchable-input" 
                               placeholder="Click para seleccionar origen..." 
                               data-target="origen_${recorridoCount}" 
<<<<<<< HEAD
                               autocomplete="off" readonly
                               value="${ultimoDestinoTexto}">
                        <input type="hidden" name="origen[]" id="origen_${recorridoCount}" required value="${ultimoDestino}">
                        <input type="hidden" name="orden_secuencial[]" value="${recorridoCount + 1}">
                    </div>
                </div>
                <div class="form-group col-md-4">
                    <label>KM entre Sucursales</label>
                    <input type="number" class="form-control" name="km_sucursales[]" 
                           placeholder="Ingrese KM aproximados" 
                           min="0" step="0.1" required>
                </div>
                <div class="form-group col-md-4">
=======
                               autocomplete="off" readonly>
                        <input type="hidden" name="origen[]" id="origen_${recorridoCount}" required>
                    </div>
                </div>
                <div class="form-group col-md-6">
>>>>>>> ef2bc8c156abe68b036b639c0ea6b5add6465c9d
                    <label>Destino</label>
                    <div class="searchable-select">
                        <input type="text" class="form-control searchable-input" 
                               placeholder="Click para seleccionar destino..." 
                               data-target="destino_${recorridoCount}" 
                               autocomplete="off" readonly>
                        <input type="hidden" name="destino[]" id="destino_${recorridoCount}" required>
                    </div>
                </div>
            </div>
<<<<<<< HEAD
            <!-- Campo de comentarios condicional -->
            <div class="form-row comentarios-sector" style="display: none;">
                <div class="form-group col-md-12">
                    <label>Comentarios del Sector</label>
                    <div class="alert alert-info mb-2">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Si vas a retirar repuesto, ¿para qué sector es?</strong>
                        <br><small>Indique el departamento para imputar correctamente el gasto de combustible (ej: Marketing, Cuadratura, Mantic, etc.)</small>
                    </div>
                    <textarea class="form-control" name="comentarios_sector[]" 
                              placeholder="Ejemplo: Retiro repuesto para el sector de Marketing" 
                              rows="3"></textarea>
                </div>
            </div>
        `;
        
=======
            <button type="button" class="btn btn-danger btn-sm" onclick="eliminarRecorrido(this)">
                <i class="fas fa-trash"></i> Eliminar
            </button>
        `;
>>>>>>> ef2bc8c156abe68b036b639c0ea6b5add6465c9d
        container.appendChild(nuevoRecorrido);
        initSearchableSelects();
    }
    
    function eliminarRecorrido(button) {
<<<<<<< HEAD
        const recorridoItem = button.closest('.recorrido-item');
        recorridoItem.remove();
        
        // Renumerar los recorridos restantes
        $('#recorridos-container .recorrido-item').each(function(index) {
            const nuevoNumero = index + 1;
            $(this).find('.recorrido-numero').text(nuevoNumero + '°');
            $(this).find('.numero-recorrido').text(nuevoNumero);
        });
=======
        button.closest('.recorrido-item').remove();
>>>>>>> ef2bc8c156abe68b036b639c0ea6b5add6465c9d
    }
    
    function showMessage(type, title, message, details = '') {
        const modal = $('#messageModal');
        const modalContent = $('#modalContent');
        const modalTitle = $('#modalTitle');
        const modalMessage = $('#modalMessage');
        const modalDetails = $('#modalDetails');
        const modalIcon = $('#modalIcon');
        const modalButton = $('#modalButton');
        
        // Reset classes
        modalContent.removeClass('modal-error modal-success modal-warning');
        
        switch(type) {
            case 'success':
                modalContent.addClass('modal-success');
                modalIcon.attr('class', 'fas fa-check-circle text-success mr-3');
                modalButton.attr('class', 'btn btn-success');
                break;
            case 'error':
                modalContent.addClass('modal-error');
                modalIcon.attr('class', 'fas fa-exclamation-circle text-danger mr-3');
                modalButton.attr('class', 'btn btn-danger');
                break;
            case 'warning':
                modalContent.addClass('modal-warning');
                modalIcon.attr('class', 'fas fa-exclamation-triangle text-warning mr-3');
                modalButton.attr('class', 'btn btn-warning');
                break;
        }
        
        modalTitle.text(title);
        modalMessage.text(message);
        modalDetails.text(details);
        
        modal.modal('show');
    }
    
    // Manejar envío del formulario con AJAX
    $('#formCombustible').on('submit', function(e) {
        e.preventDefault(); // Prevenir envío normal
        
        // Mostrar loading
        $('#loadingOverlay').show();
        $('#submitBtn').prop('disabled', true);
        
        // Crear FormData para manejar archivos
        const formData = new FormData(this);
        
        // Enviar con AJAX
        $.ajax({
            url: window.location.href, // Misma página
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                $('#loadingOverlay').hide();
                $('#submitBtn').prop('disabled', false);
                
                if (response.success) {
                    showMessage('success', 'Éxito', response.message);
                    
                    // Redireccionar a ver_registros.php después de 2 segundos
                    setTimeout(function() {
                        window.location.href = 'ver_registros.php';
                    }, 2000);
                    
                    // También permitir cerrar manualmente el modal
                    $('#messageModal').on('hidden.bs.modal', function() {
                        window.location.href = 'ver_registros.php';
                    });
                } else {
                    showMessage('error', 'Error', response.message);
                }
            },
            error: function(xhr, status, error) {
                $('#loadingOverlay').hide();
                $('#submitBtn').prop('disabled', false);
                showMessage('error', 'Error', 'Error al procesar la solicitud: ' + error);
            }
        });
        
        return false;
    });
    
    // Función para mostrar foto actual
    function mostrarFotoActual() {
        const fotoRuta = '<?php echo !empty($registro_principal['foto_voucher_ruta']) ? htmlspecialchars($registro_principal['foto_voucher_ruta']) : ''; ?>';
        if (fotoRuta) {
            $('#fotoActualImg').attr('src', '../../img/uso_combustible/vouchers/' + fotoRuta);
            $('#fotoActualModal').modal('show');
        }
    }
    
    // Función para manejar la nueva foto del voucher
    function eliminarFotoVoucher() {
        $('#foto_voucher').val('');
        $('#foto_voucher').next('.custom-file-label').text('<?php echo !empty($registro_principal['foto_voucher_ruta']) ? 'Seleccionar nueva foto...' : 'Tomar foto del voucher...'; ?>');
        $('#preview_foto_voucher').hide();
        $('#img_preview_voucher').attr('src', '');
    }
    
    // AGREGAR - Inicializar eventos de foto del voucher
    $(document).ready(function() {
        $('#foto_voucher').on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Actualizar el label
                $(this).next('.custom-file-label').text(file.name);
                
                // Mostrar vista previa
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#img_preview_voucher').attr('src', e.target.result);
                    $('#preview_foto_voucher').show();
                };
                reader.readAsDataURL(file);
            }
        });
    });
<<<<<<< HEAD
    // Evento para manejar la selección de opciones en el modal
    $(document).on('click', '.option-item[data-value]', function() {
        const selectedValue = $(this).data('value');
        const selectedText = $(this).text().trim();
        
        if (currentInput && currentHiddenInput) {
            currentInput.val(selectedText);
            currentHiddenInput.val(selectedValue);
            
            // Verificar si es un destino y contiene "depósito" para mostrar comentarios
            if (currentHiddenInput.attr('name') === 'destino[]') {
                const recorridoItem = currentInput.closest('.recorrido-item');
                const comentariosSector = recorridoItem.find('.comentarios-sector');
                
                if (selectedValue && (selectedValue.toLowerCase().includes('deposito') || selectedValue.toLowerCase().includes('depósito'))) {
                    comentariosSector.show();
                    comentariosSector.find('textarea').prop('required', true);
                } else {
                    comentariosSector.hide();
                    comentariosSector.find('textarea').prop('required', false).val('');
                }
            }
            
            // Llamar a verificarSucursalesEspeciales si existe
            if (typeof verificarSucursalesEspeciales === 'function') {
                setTimeout(verificarSucursalesEspeciales, 100);
            }
        }
        
        $('#selectionModal').modal('hide');
    });
=======
>>>>>>> ef2bc8c156abe68b036b639c0ea6b5add6465c9d
    </script>
</body>
</html>