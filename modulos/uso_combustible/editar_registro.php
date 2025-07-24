<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

require_once '../../config/Database.php';

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

$registro_id = $_GET['id'];

// Obtener datos del registro
$query = "SELECT uc.*, ucr.origen, ucr.destino 
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
            'destino' => $reg['destino']
        ];
    }
}

// Si no hay recorridos, agregar uno vacío
if (empty($recorridos)) {
    $recorridos[] = ['origen' => '', 'destino' => ''];
}

// Procesar actualización si es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // Actualizar registro principal
        $updateQuery = "UPDATE uso_combustible SET 
                        fecha = :fecha,
                        nombre_conductor = :conductor,
                        chapa = :chapa,
                        numero_baucher = :numero_voucher,
                        tarjeta = :tarjeta,
                        litros_cargados = :litros_cargados,
                        tipo_vehiculo = :tipo_vehiculo,
                        documento = :documento,
                        fecha_carga = :fecha_carga,
                        hora_carga = :hora_carga
                        WHERE id = :id";
        
        $stmt = $db->prepare($updateQuery);
        $stmt->bindParam(':fecha', $_POST['fecha']);
        $stmt->bindParam(':conductor', $_POST['conductor']);
        $stmt->bindParam(':chapa', $_POST['chapa']);
        $stmt->bindParam(':numero_voucher', $_POST['numero_voucher']);
        $stmt->bindParam(':tarjeta', $_POST['tarjeta']);
        $stmt->bindParam(':litros_cargados', $_POST['litros_cargados']);
        $stmt->bindParam(':tipo_vehiculo', $_POST['tipo_vehiculo']);
        $stmt->bindParam(':documento', $_POST['documento']);
        $stmt->bindParam(':fecha_carga', $_POST['fecha_carga']);
        $stmt->bindParam(':hora_carga', $_POST['hora_carga']);
        $stmt->bindParam(':id', $registro_id);
        $stmt->execute();
        
        // Eliminar recorridos existentes
        $deleteQuery = "DELETE FROM uso_combustible_recorridos WHERE uso_combustible_id = :id";
        $stmt = $db->prepare($deleteQuery);
        $stmt->bindParam(':id', $registro_id);
        $stmt->execute();
        
        // Insertar nuevos recorridos
        if (isset($_POST['origen']) && isset($_POST['destino'])) {
            $insertRecorridoQuery = "INSERT INTO uso_combustible_recorridos (uso_combustible_id, origen, destino) VALUES (:uso_combustible_id, :origen, :destino)";
            $stmt = $db->prepare($insertRecorridoQuery);
            
            for ($i = 0; $i < count($_POST['origen']); $i++) {
                if (!empty($_POST['origen'][$i]) && !empty($_POST['destino'][$i])) {
                    $stmt->bindParam(':uso_combustible_id', $registro_id);
                    $stmt->bindParam(':origen', $_POST['origen'][$i]);
                    $stmt->bindParam(':destino', $_POST['destino'][$i]);
                    $stmt->execute();
                }
            }
        }
        
        $db->commit();
        $success_message = "Registro actualizado correctamente";
        
        // Recargar datos actualizados
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $registro_id);
        $stmt->execute();
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $registro_principal = $registros[0];
        
        $recorridos = [];
        foreach ($registros as $reg) {
            if (!empty($reg['origen']) && !empty($reg['destino'])) {
                $recorridos[] = [
                    'origen' => $reg['origen'],
                    'destino' => $reg['destino']
                ];
            }
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        $error_message = "Error al actualizar el registro: " . $e->getMessage();
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
    </style>
</head>
<body>
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
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle"></h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center">
                        <i id="modalIcon" class="mr-3" style="font-size: 2rem;"></i>
                        <div>
                            <p id="modalMessage" class="mb-1"></p>
                            <small id="modalDetails" class="text-muted"></small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" id="modalButton" data-dismiss="modal">Aceptar</button>
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
                            
                            <!-- Sección de Recorridos -->
                            <h5 class="mb-3">Recorridos</h5>
                            <div id="recorridos-container">
                                <?php foreach ($recorridos as $index => $recorrido): ?>
                                <div class="recorrido-item border p-3 mb-3">
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Origen</label>
                                            <input type="text" class="form-control" name="origen[]" value="<?php echo htmlspecialchars($recorrido['origen']); ?>" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Destino</label>
                                            <input type="text" class="form-control" name="destino[]" value="<?php echo htmlspecialchars($recorrido['destino']); ?>" required>
                                        </div>
                                    </div>
                                    <?php if ($index > 0): ?>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="eliminarRecorrido(this)">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                    <?php endif; ?>
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
    function agregarRecorrido() {
        const container = document.getElementById('recorridos-container');
        const nuevoRecorrido = document.createElement('div');
        nuevoRecorrido.className = 'recorrido-item border p-3 mb-3';
        nuevoRecorrido.innerHTML = `
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Origen:</label>
                    <input type="text" class="form-control" name="origen[]" required>
                </div>
                <div class="form-group col-md-6">
                    <label>Destino:</label>
                    <input type="text" class="form-control" name="destino[]" required>
                </div>
            </div>
            <button type="button" class="btn btn-danger btn-sm" onclick="eliminarRecorrido(this)">
                <i class="fas fa-trash"></i> Eliminar
            </button>
        `;
        container.appendChild(nuevoRecorrido);
    }
    
    function eliminarRecorrido(button) {
        button.closest('.recorrido-item').remove();
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
    
    // Manejar envío del formulario
    $('#formCombustible').on('submit', function(e) {
        // Mostrar loading
        $('#loadingOverlay').show();
        $('#submitBtn').prop('disabled', true);
        
        // El formulario se enviará normalmente (no AJAX para simplicidad)
        return true;
    });
    </script>
</body>
</html>