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
        
        .comentarios-sector {
            background-color: #f8f9fa;
            border-left: 4px solid #17a2b8;
            padding: 15px;
            margin-top: 15px;
            border-radius: 0 5px 5px 0;
        }
        
        .comentarios-sector .alert {
            margin-bottom: 10px;
            font-size: 0.9em;
        }
        
        .comentarios-sector textarea {
            border: 2px solid #17a2b8;
        }
        
        .comentarios-sector textarea:focus {
            border-color: #138496;
            box-shadow: 0 0 0 0.2rem rgba(23, 162, 184, 0.25);
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
                                <?php if(in_array($_SESSION['user_rol'], ['administrador', 'administrativo'])): ?>
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
                            
                            <!-- Campo para foto del voucher -->
                            <div class="form-row">
                                <div class="form-group col-md-12">
                                    <label for="foto_voucher">Foto del Voucher</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="foto_voucher" name="foto_voucher" accept="image/*" capture="camera">
                                        <label class="custom-file-label" for="foto_voucher">Tomar foto del voucher...</label>
                                    </div>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-camera mr-1"></i>Toque para abrir la cámara y capturar la foto del voucher
                                    </small>
                                    <div id="preview_foto_voucher" class="mt-2" style="display: none;">
                                        <img id="img_preview_voucher" src="" alt="Vista previa" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                                        <button type="button" class="btn btn-sm btn-danger ml-2" onclick="eliminarFotoVoucher()">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="fas fa-map-marker-alt mr-2"></i>Sucursales</h5>
                            </div>
                            <div class="card-body">
                                <div id="recorridos-container">
                                    <div class="recorrido-item border p-3 mb-3">
                                        <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label>Origen</label>
                                    <div class="searchable-select">
                                        <input type="text" class="form-control searchable-input" 
                                               placeholder="Click para seleccionar origen..." 
                                               data-target="origen_0" 
                                               autocomplete="off" readonly>
                                        <input type="hidden" name="origen[]" id="origen_0" required>
                                    </div>
                                </div>
                                <div class="form-group col-md-4">
                                    <label>KM entre Sucursales</label>
                                    <input type="number" class="form-control" name="km_sucursales[]" 
                                           placeholder="Ingrese KM aproximados" 
                                           min="0" step="0.1" required>
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Destino</label>
                                    <div class="searchable-select">
                                        <input type="text" class="form-control searchable-input" 
                                               placeholder="Click para seleccionar destino..." 
                                               data-target="destino_0" 
                                               autocomplete="off" readonly>
                                        <input type="hidden" name="destino[]" id="destino_0" required>
                                    </div>
                                </div>
                            </div>
                            <!-- Campo de comentarios condicional -->
                            <div class="form-row comentarios-sector" style="display: none;">
                                <div class="form-group col-md-12">
                                    <label for="comentarios_sector">Comentarios del Sector</label>
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
    
    // Funcionalidad para dropdowns buscables
    let searchableCounter = 1;
    
    function initSearchableSelects() {
        $('.searchable-input').each(function() {
            const input = $(this);
            const hiddenInput = $('#' + input.data('target'));
            
            // Hacer el input de solo lectura y agregar evento click
            input.attr('readonly', true);
            
            input.on('click', function() {
                currentInput = input;
                currentHiddenInput = hiddenInput;
                
                // Configurar el modal
                const fieldType = input.closest('.form-group').find('label').text().trim();
                $('#selectionModalTitle').text('Seleccionar ' + fieldType);
                
                // Limpiar y cargar opciones
                loadModalOptions();
                
                // Mostrar modal
                $('#selectionModal').modal('show');
            });
        });
    }
    
    function loadModalOptions(searchTerm = '') {
        const modalOptions = $('#modalOptions');
        modalOptions.empty();
        
        // Agregar opción vacía
        modalOptions.append(`
            <div class="option-item" data-value="">
                <i class="fas fa-times text-muted mr-2"></i>Limpiar selección
            </div>
        `);
        
        // Filtrar y agregar sucursales
        sucursalesData.forEach(function(sucursal) {
            if (searchTerm === '' || sucursal.text.toLowerCase().includes(searchTerm.toLowerCase())) {
                modalOptions.append(`
                    <div class="option-item" data-value="${sucursal.value}">
                        <i class="fas fa-building text-primary mr-2"></i>${sucursal.text}
                    </div>
                `);
            }
        });
        
        // Si no hay resultados
        if (modalOptions.children().length === 1 && searchTerm !== '') {
            modalOptions.append(`
                <div class="option-item text-muted" style="cursor: default;">
                    <i class="fas fa-search mr-2"></i>No se encontraron resultados
                </div>
            `);
        }
    }
    
    // Función para verificar si debe mostrar el campo de comentarios
    function verificarSucursalesEspeciales() {
        $('.recorrido-item').each(function() {
            const recorridoItem = $(this);
            const destinoValue = recorridoItem.find('input[name="destino[]"]').val();
            const comentariosContainer = recorridoItem.find('.comentarios-sector');
            const comentariosTextarea = recorridoItem.find('textarea[name="comentarios_sector[]"]');
            
            // Lógica simplificada: mostrar comentarios SOLO cuando el destino sea:
            // - S6 GRAN UNION
            // - STOCK RCA ARGENTINA  
            // - PROVEEDOR
            const mostrarComentarios = (
                destinoValue === 'S6 GRAN UNION' || 
                destinoValue === 'STOCK RCA ARGENTINA' || 
                destinoValue === 'PROVEEDOR'
            );
            
            if (mostrarComentarios) {
                comentariosContainer.slideDown(300);
                comentariosTextarea.attr('required', true);
            } else {
                comentariosContainer.slideUp(300);
                comentariosTextarea.attr('required', false);
                comentariosTextarea.val(''); // Limpiar el valor si se oculta
            }
        });
    }
    
    // Eventos del modal
    $(document).ready(function() {
        // Búsqueda en el modal
        $('#modalSearchInput').on('input', function() {
            const searchTerm = $(this).val();
            loadModalOptions(searchTerm);
        });
        
        // Selección de opción
        $(document).on('click', '.option-item[data-value]', function() {
            const value = $(this).data('value');
            const text = $(this).text().trim();
            
            if (currentInput && currentHiddenInput) {
                if (value === '') {
                    currentInput.val('');
                    currentHiddenInput.val('');
                } else {
                    currentInput.val(text);
                    currentHiddenInput.val(value);
                }
                
                // Verificar sucursales especiales después de la selección
                setTimeout(() => {
                    verificarSucursalesEspeciales();
                }, 100);
            }
            
            $('#selectionModal').modal('hide');
        });
        
        // Limpiar búsqueda cuando se abre el modal
        $('#selectionModal').on('shown.bs.modal', function() {
            $('#modalSearchInput').val('').focus();
        });
        
        // Inicializar
        initSearchableSelects();
    });
    
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
    
    // Función actualizada para agregar recorrido
    function agregarRecorrido() {
        const container = document.getElementById('recorridos-container');
        const nuevoRecorrido = document.createElement('div');
        nuevoRecorrido.className = 'recorrido-item border p-3 mb-3';
        
        const origenId = `origen_${searchableCounter}`;
        const destinoId = `destino_${searchableCounter}`;
        
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
        
        nuevoRecorrido.innerHTML = `
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Origen:</label>
                    <div class="searchable-select">
                        <input type="text" class="form-control searchable-input" 
                               placeholder="Click para seleccionar origen..." 
                               data-target="${origenId}" 
                               autocomplete="off" readonly
                               value="${ultimoDestinoTexto}">
                        <input type="hidden" name="origen[]" id="${origenId}" required value="${ultimoDestino}">
                    </div>
                </div>
                <div class="form-group col-md-4">
                    <label>KM entre Sucursales:</label>
                    <input type="number" class="form-control" name="km_sucursales[]" 
                           placeholder="Ingrese KM aproximados" 
                           min="0" step="0.1" required>
                </div>
                <div class="form-group col-md-4">
                    <label>Destino:</label>
                    <div class="searchable-select">
                        <input type="text" class="form-control searchable-input" 
                               placeholder="Click para seleccionar destino..." 
                               data-target="${destinoId}" 
                               autocomplete="off" readonly>
                        <input type="hidden" name="destino[]" id="${destinoId}" required>
                    </div>
                </div>
            </div>
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
            <button type="button" class="btn btn-danger btn-sm" onclick="eliminarRecorrido(this)">
                <i class="fas fa-trash"></i> Eliminar
            </button>
        `;
        
        container.appendChild(nuevoRecorrido);
        searchableCounter++;
        
        // Reinicializar los eventos para los nuevos elementos
        initSearchableSelects();
        
        // Mostrar mensaje informativo si se auto-completó el origen
        if (ultimoDestino) {
            // Opcional: mostrar un pequeño mensaje de confirmación
            const mensaje = document.createElement('div');
            mensaje.className = 'alert alert-info alert-dismissible fade show mt-2';
            mensaje.innerHTML = `
                <small><i class="fas fa-info-circle mr-1"></i>
                Se estableció automáticamente "${ultimoDestinoTexto}" como origen del nuevo recorrido.</small>
                <button type="button" class="close" data-dismiss="alert" style="font-size: 1rem; line-height: 1;">
                    <span>&times;</span>
                </button>
            `;
            nuevoRecorrido.appendChild(mensaje);
            
            // Auto-ocultar el mensaje después de 3 segundos
            setTimeout(() => {
                if (mensaje.parentNode) {
                    $(mensaje).alert('close');
                }
            }, 3000);
        }
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
        
        // Crear FormData para manejar archivos
        const formData = new FormData(this);
        
        $.ajax({
            url: 'guardar_registro.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
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
                            
                            // Reset foto del voucher
                            eliminarFotoVoucher();
                            
                            // Reset recorridos - clonar el primer elemento
                            const firstRecorrido = $('#recorridos-container .recorrido-item:first');
                            if (firstRecorrido.length > 0) {
                                // Limpiar valores del primer elemento
                                firstRecorrido.find('input[type="text"]').val('');
                                firstRecorrido.find('input[type="hidden"]').val('');
                                firstRecorrido.find('input[type="number"]').val('');
                                
                                // Remover elementos adicionales
                                $('#recorridos-container .recorrido-item:not(:first)').remove();
                            }
                            
                            // Reinicializar los elementos searchable
                            searchableCounter = 1;
                            initSearchableSelects();
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
    
    // Función para manejar la foto del voucher
    function eliminarFotoVoucher() {
        $('#foto_voucher').val('');
        $('#foto_voucher').next('.custom-file-label').text('Tomar foto del voucher...');
        $('#preview_foto_voucher').hide();
        $('#img_preview_voucher').attr('src', '');
    }
    

    
    // Inicializar cuando el documento esté listo
    $(document).ready(function() {
        initSearchableSelects();
        
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
    </script>
</body>
</html>

<?php /*
    // Funciones para gestión de sucursales (solo administradores) - OCULTO
    <?php if($_SESSION['user_rol'] === 'administrador'): ?>
    
    // Todo el código JavaScript de sucursales
    

*/ ?>
                                </div>
                            </div>
                            <!-- Campo de comentarios condicional -->
                            <div class="form-row comentarios-sector" style="display: none;">
                                <div class="form-group col-md-12">
                                    <label for="comentarios_sector">Comentarios del Sector</label>
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
                                    </div>
                                </div>
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
    
    // Funcionalidad para dropdowns buscables
    let searchableCounter = 1;
    
    function initSearchableSelects() {
        $('.searchable-input').each(function() {
            const input = $(this);
            const hiddenInput = $('#' + input.data('target'));
            
            // Hacer el input de solo lectura y agregar evento click
            input.attr('readonly', true);
            
            input.on('click', function() {
                currentInput = input;
                currentHiddenInput = hiddenInput;
                
                // Configurar el modal
                const fieldType = input.closest('.form-group').find('label').text().trim();
                $('#selectionModalTitle').text('Seleccionar ' + fieldType);
                
                // Limpiar y cargar opciones
                loadModalOptions();
                
                // Mostrar modal
                $('#selectionModal').modal('show');
            });
        });
    }
    
    function loadModalOptions(searchTerm = '') {
        const modalOptions = $('#modalOptions');
        modalOptions.empty();
        
        // Agregar opción vacía
        modalOptions.append(`
            <div class="option-item" data-value="">
                <i class="fas fa-times text-muted mr-2"></i>Limpiar selección
            </div>
        `);
        
        // Filtrar y agregar sucursales
        sucursalesData.forEach(function(sucursal) {
            if (searchTerm === '' || sucursal.text.toLowerCase().includes(searchTerm.toLowerCase())) {
                modalOptions.append(`
                    <div class="option-item" data-value="${sucursal.value}">
                        <i class="fas fa-building text-primary mr-2"></i>${sucursal.text}
                    </div>
                `);
            }
        });
        
        // Si no hay resultados
        if (modalOptions.children().length === 1 && searchTerm !== '') {
            modalOptions.append(`
                <div class="option-item text-muted" style="cursor: default;">
                    <i class="fas fa-search mr-2"></i>No se encontraron resultados
                </div>
            `);
        }
    }
    
    // Eventos del modal
    $(document).ready(function() {
        // Búsqueda en el modal
        $('#modalSearchInput').on('input', function() {
            const searchTerm = $(this).val();
            loadModalOptions(searchTerm);
        });
        
        // Selección de opción
        $(document).on('click', '.option-item[data-value]', function() {
            const value = $(this).data('value');
            const text = $(this).text().trim();
            
            if (currentInput && currentHiddenInput) {
                if (value === '') {
                    currentInput.val('');
                    currentHiddenInput.val('');
                } else {
                    currentInput.val(text);
                    currentHiddenInput.val(value);
                }
                
                // Verificar sucursales especiales después de la selección
                setTimeout(() => {
                    verificarSucursalesEspeciales();
                }, 100);
            }
            
            $('#selectionModal').modal('hide');
        });
        
        // Limpiar búsqueda cuando se abre el modal
        $('#selectionModal').on('shown.bs.modal', function() {
            $('#modalSearchInput').val('').focus();
        });
        
        // Inicializar
        initSearchableSelects();
    });
    
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
    
    // Función actualizada para agregar recorrido
    function agregarRecorrido() {
        const container = document.getElementById('recorridos-container');
        const nuevoRecorrido = document.createElement('div');
        nuevoRecorrido.className = 'recorrido-item border p-3 mb-3';
        
        const origenId = `origen_${searchableCounter}`;
        const destinoId = `destino_${searchableCounter}`;
        
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
        
        nuevoRecorrido.innerHTML = `
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Origen:</label>
                    <div class="searchable-select">
                        <input type="text" class="form-control searchable-input" 
                               placeholder="Click para seleccionar origen..." 
                               data-target="${origenId}" 
                               autocomplete="off" readonly
                               value="${ultimoDestinoTexto}">
                        <input type="hidden" name="origen[]" id="${origenId}" required value="${ultimoDestino}">
                    </div>
                </div>
                <div class="form-group col-md-4">
                    <label>KM entre Sucursales:</label>
                    <input type="number" class="form-control" name="km_sucursales[]" 
                           placeholder="Ingrese KM aproximados" 
                           min="0" step="0.1" required>
                </div>
                <div class="form-group col-md-4">
                    <label>Destino:</label>
                    <div class="searchable-select">
                        <input type="text" class="form-control searchable-input" 
                               placeholder="Click para seleccionar destino..." 
                               data-target="${destinoId}" 
                               autocomplete="off" readonly>
                        <input type="hidden" name="destino[]" id="${destinoId}" required>
                    </div>
                </div>
            </div>
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
            <button type="button" class="btn btn-danger btn-sm" onclick="eliminarRecorrido(this)">
                <i class="fas fa-trash"></i> Eliminar
            </button>
        `;
        
        container.appendChild(nuevoRecorrido);
        searchableCounter++;
        
        // Reinicializar los eventos para los nuevos elementos
        initSearchableSelects();
        
        // Mostrar mensaje informativo si se auto-completó el origen
        if (ultimoDestino) {
            // Opcional: mostrar un pequeño mensaje de confirmación
            const mensaje = document.createElement('div');
            mensaje.className = 'alert alert-info alert-dismissible fade show mt-2';
            mensaje.innerHTML = `
                <small><i class="fas fa-info-circle mr-1"></i>
                Se estableció automáticamente "${ultimoDestinoTexto}" como origen del nuevo recorrido.</small>
                <button type="button" class="close" data-dismiss="alert" style="font-size: 1rem; line-height: 1;">
                    <span>&times;</span>
                </button>
            `;
            nuevoRecorrido.appendChild(mensaje);
            
            // Auto-ocultar el mensaje después de 3 segundos
            setTimeout(() => {
                if (mensaje.parentNode) {
                    $(mensaje).alert('close');
                }
            }, 3000);
        }
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
                        
                        // Reset recorridos - clonar el primer elemento
                        const firstRecorrido = $('#recorridos-container .recorrido-item:first');
                        if (firstRecorrido.length > 0) {
                            // Limpiar valores del primer elemento
                            firstRecorrido.find('input[type="text"]').val('');
                            firstRecorrido.find('input[type="hidden"]').val('');
                            firstRecorrido.find('input[type="number"]').val('');
                            
                            // Remover elementos adicionales
                            $('#recorridos-container .recorrido-item:not(:first)').remove();
                        }
                        
                        // Reinicializar los elementos searchable
                        searchableCounter = 1;
                        initSearchableSelects();
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
    
    // Inicializar cuando el documento esté listo
    $(document).ready(function() {
        initSearchableSelects();
    });
    </script>
</body>
</html>

<?php /*
    // Funciones para gestión de sucursales (solo administradores) - OCULTO
    <?php if($_SESSION['user_rol'] === 'administrador'): ?>
    
    // Todo el código JavaScript de sucursales
    

*/ ?>
</body>
</html>