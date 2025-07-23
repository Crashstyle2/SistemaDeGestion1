<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

require_once '../../config/Database.php';

// Verificar el rol del usuario
$rol = $_SESSION['user_rol'];

if (!in_array($rol, ['tecnico', 'supervisor', 'administrativo', 'administrador'])) {
    header("Location: ../../dashboard.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Obtener sucursales para los campos de origen y destino
$query_sucursales = "SELECT DISTINCT local FROM sucursales ORDER BY local";
$stmt_sucursales = $db->prepare($query_sucursales);
$stmt_sucursales->execute();
$sucursales = $stmt_sucursales->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Uso de Combustible</title>
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
            <div class="text-white mt-2">Guardando registro...</div>
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
                    <div class="card-header bg-info text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-gas-pump mr-2"></i>Registro de Uso de Combustible
                            </h5>
                            <div>
                                <?php if($_SESSION['user_rol'] === 'administrador'): ?>
                                <a href="sucursales.php" class="btn btn-warning btn-sm mr-2">
                                    <i class="fas fa-building mr-2"></i>Gestionar Sucursales
                                </a>
                                <?php endif; ?>
                                <a href="ver_registros.php" class="btn btn-light btn-sm">
                                    <i class="fas fa-list mr-2"></i>Ver Registros
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form id="formCombustible" method="POST">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="fecha">Fecha</label>
                                    <input type="date" class="form-control" id="fecha" name="fecha" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="conductor">Conductor</label>
                                    <input type="text" class="form-control" id="conductor" name="conductor" value="<?php echo htmlspecialchars($_SESSION['nombre']); ?>" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="chapa">Vehículo Chapa</label>
                                    <input type="text" class="form-control" id="chapa" name="chapa" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="numero_voucher">Nº Voucher</label>
                                    <input type="text" class="form-control" id="numero_voucher" name="numero_voucher" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="tarjeta">Nº tarjeta (últimos 4 dígitos)</label>
                                    <input type="text" class="form-control" id="tarjeta" name="tarjeta" maxlength="4">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="litros_cargados">Litros Cargados</label>
                                    <input type="number" step="0.01" class="form-control" id="litros_cargados" name="litros_cargados" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="tipo_vehiculo">Tipo de Vehículo</label>
                                    <select class="form-control" id="tipo_vehiculo" name="tipo_vehiculo" required>
                                        <option value="">Seleccione tipo de vehículo</option>
                                        <option value="particular">Particular</option>
                                        <option value="movil_retail">Móvil de Retail</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="documento">Documento</label>
                                    <input type="text" class="form-control" id="documento" name="documento" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="fecha_carga">Fecha de Carga del Voucher</label>
                                    <input type="date" class="form-control" id="fecha_carga" name="fecha_carga" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="hora_carga">Hora de Carga del Voucher</label>
                                    <input type="time" class="form-control" id="hora_carga" name="hora_carga" value="<?php echo date('H:i'); ?>" required>
                                </div>
                            </div>
                            
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="fas fa-map-marker-alt mr-2"></i>Sucursales</h5>
                            </div>
                            <div class="card-body">
                                <div id="recorridos-container">
                                    <div class="recorrido-item border p-3 mb-3">
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Origen</label>
                                                <select class="form-control" name="origen[]" required>
                                                    <option value="">Seleccione origen</option>
                                                    <?php foreach($sucursales as $sucursal): ?>
                                                    <option value="<?php echo htmlspecialchars($sucursal['local']); ?>">
                                                        <?php echo htmlspecialchars($sucursal['local']); ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Destino</label>
                                                <select class="form-control" name="destino[]" required>
                                                    <option value="">Seleccione destino</option>
                                                    <?php foreach($sucursales as $sucursal): ?>
                                                    <option value="<?php echo htmlspecialchars($sucursal['local']); ?>">
                                                        <?php echo htmlspecialchars($sucursal['local']); ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-right mb-3">
                                <button type="button" class="btn btn-secondary" onclick="agregarRecorrido()" id="btn-agregar">
                                    <i class="fas fa-plus mr-2"></i>Agregar Sucursal
                                </button>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                    <i class="fas fa-save mr-2"></i>Guardar Registro
                                </button>
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
    // Función para cambiar automáticamente el campo Documento según el tipo de vehículo
    $('#tipo_vehiculo').on('change', function() {
        const tipoVehiculo = $(this).val();
        const documentoField = $('#documento');
        
        if (tipoVehiculo === 'particular') {
            documentoField.val('Recorrido');
        } else if (tipoVehiculo === 'movil_retail') {
            documentoField.val('Ruteo');
        } else {
            documentoField.val('');
        }
    });
    
    function agregarRecorrido() {
        const container = document.getElementById('recorridos-container');
        const nuevoRecorrido = document.createElement('div');
        nuevoRecorrido.className = 'recorrido-item border p-3 mb-3';
        
        // Crear las opciones de sucursales para los nuevos selects
        const opcionesSucursales = `
            <option value="">Seleccione origen</option>
            <?php foreach($sucursales as $sucursal): ?>
            <option value="<?php echo htmlspecialchars($sucursal['local']); ?>">
                <?php echo htmlspecialchars($sucursal['local']); ?>
            </option>
            <?php endforeach; ?>
        `;
        
        const opcionesDestino = `
            <option value="">Seleccione destino</option>
            <?php foreach($sucursales as $sucursal): ?>
            <option value="<?php echo htmlspecialchars($sucursal['local']); ?>">
                <?php echo htmlspecialchars($sucursal['local']); ?>
            </option>
            <?php endforeach; ?>
        `;
        
        nuevoRecorrido.innerHTML = `
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Origen:</label>
                    <select class="form-control" name="origen[]" required>
                        ${opcionesSucursales}
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label>Destino:</label>
                    <select class="form-control" name="destino[]" required>
                        ${opcionesDestino}
                    </select>
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
        e.preventDefault();
        
        // Mostrar loading
        $('#loadingOverlay').show();
        $('#submitBtn').prop('disabled', true);
        
        $.ajax({
            url: 'guardar_registro.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            timeout: 30000,
            success: function(response) {
                $('#loadingOverlay').hide();
                $('#submitBtn').prop('disabled', false);
                
                if(response.success) {
                    showMessage('success', '¡Éxito!', response.message, 
                        response.data ? `Registro ID: ${response.data.registro_id}, Recorridos guardados: ${response.data.recorridos_guardados}` : '');
                    
                    // Reset form after success
                    setTimeout(() => {
                        $('#formCombustible')[0].reset();
                        $('#fecha').val('<?php echo date('Y-m-d'); ?>');
                        $('#fecha_carga').val('<?php echo date('Y-m-d'); ?>');
                        $('#hora_carga').val('<?php echo date('H:i'); ?>');
                        $('#conductor').val('<?php echo htmlspecialchars($_SESSION['nombre']); ?>');
                        
                        // Reset recorridos
                        $('#recorridos-container').html(`
                            <div class="recorrido-item border p-3 mb-3">
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>Origen</label>
                                        <select class="form-control" name="origen[]" required>
                                            <option value="">Seleccione origen</option>
                                            <?php foreach($sucursales as $sucursal): ?>
                                            <option value="<?php echo htmlspecialchars($sucursal['local']); ?>">
                                                <?php echo htmlspecialchars($sucursal['local']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Destino</label>
                                        <select class="form-control" name="destino[]" required>
                                            <option value="">Seleccione destino</option>
                                            <?php foreach($sucursales as $sucursal): ?>
                                            <option value="<?php echo htmlspecialchars($sucursal['local']); ?>">
                                                <?php echo htmlspecialchars($sucursal['local']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        `);
                    }, 2000);
                } else {
                    let title = 'Error';
                    let details = '';
                    
                    switch(response.error_type) {
                        case 'auth':
                            title = 'Error de Autenticación';
                            details = 'Su sesión ha expirado. Será redirigido al login.';
                            setTimeout(() => {
                                window.location.href = '../../login.php';
                            }, 3000);
                            break;
                        case 'validation':
                            title = 'Error de Validación';
                            details = 'Verifique los datos ingresados y corrija los errores.';
                            break;
                        case 'server':
                            title = 'Error del Servidor';
                            details = 'Error interno del sistema. Contacte al administrador si persiste.';
                            break;
                        default:
                            title = 'Error';
                            details = response.error_code ? `Código de error: ${response.error_code}` : '';
                    }
                    
                    showMessage('error', title, response.message, details);
                }
            },
            error: function(xhr, status, error) {
                $('#loadingOverlay').hide();
                $('#submitBtn').prop('disabled', false);
                
                let message = 'Error de conexión';
                let details = '';
                
                if (status === 'timeout') {
                    message = 'La solicitud tardó demasiado tiempo';
                    details = 'Verifique su conexión a internet e intente nuevamente.';
                } else if (xhr.status === 0) {
                    message = 'Sin conexión al servidor';
                    details = 'Verifique que el servidor esté funcionando.';
                } else {
                    message = 'Error al procesar la solicitud';
                    details = `Estado: ${xhr.status} - ${error}`;
                }
                
                showMessage('error', 'Error de Conexión', message, details);
            }
        });
    });
    </script>
</body>
</html>

<?php /*
    // Funciones para gestión de sucursales (solo administradores) - OCULTO
    <?php if($_SESSION['user_rol'] === 'administrador'): ?>
    
    // Todo el código JavaScript de sucursales comentado...
    
    <?php endif; ?>
*/ ?>
</body>
</html>