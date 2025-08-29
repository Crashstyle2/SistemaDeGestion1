<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

// Verificar que sea administrador o analista
if(!in_array($_SESSION['user_rol'], ['administrador', 'supervisor', 'analista'])) {
    header("Location: index.php");
    exit;
}

require_once '../../config/Database.php';

$database = new Database();
$db = $database->getConnection();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Sucursales</title>
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
    </style>
</head>
<body>
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
            <div class="col-md-12">
                <!-- Panel de Gestión de Sucursales -->
                <div class="card shadow">
                    <div class="card-header bg-warning text-dark">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-building mr-2"></i>Gestión de Sucursales
                            </h5>
                            <div>
                                <button type="button" class="btn btn-dark btn-sm mr-2" data-toggle="modal" data-target="#modalSucursal">
                                    <i class="fas fa-plus mr-2"></i>Agregar Sucursal
                                </button>
                                <a href="index.php" class="btn btn-info btn-sm">
                                    <i class="fas fa-arrow-left mr-2"></i>Volver
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Campo de búsqueda -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    </div>
                                    <input type="text" class="form-control" id="buscarSucursal" placeholder="Buscar por segmento, CEBE, local o localidad...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <button type="button" class="btn btn-secondary" id="limpiarBusqueda">
                                    <i class="fas fa-times mr-2"></i>Limpiar
                                </button>
                                <span class="ml-3 text-muted" id="contadorResultados"></span>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="tablaSucursales">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Segmento</th>
                                        <th>CEBE</th>
                                        <th>Local</th>
                                        <th>M² Neto</th>
                                        <th>Localidad</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="cuerpoTablaSucursales">
                                    <!-- Se carga dinámicamente -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Agregar/Editar Sucursal -->
    <div class="modal fade" id="modalSucursal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="tituloModalSucursal">Agregar Sucursal</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="formSucursal">
                    <div class="modal-body">
                        <input type="hidden" id="sucursal_id" name="sucursal_id">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="segmento">Segmento *</label>
                                <input type="text" class="form-control" id="segmento" name="segmento" maxlength="50" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="cebe">CEBE *</label>
                                <input type="number" class="form-control" id="cebe" name="cebe" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="local">Local *</label>
                                <input type="text" class="form-control" id="local" name="local" maxlength="50" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="m2_neto">M² Neto *</label>
                                <input type="number" class="form-control" id="m2_neto" name="m2_neto" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="localidad">Localidad *</label>
                            <input type="text" class="form-control" id="localidad" name="localidad" maxlength="250" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Guardar Sucursal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
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
    
    // Variables globales para búsqueda
    let todasLasSucursales = [];
    let sucursalesFiltradas = [];
    
    // Cargar sucursales al iniciar
    $(document).ready(function() {
        cargarSucursales();
        
        // Configurar búsqueda en tiempo real
        $('#buscarSucursal').on('input', function() {
            filtrarSucursales();
        });
        
        // Botón limpiar búsqueda
        $('#limpiarBusqueda').on('click', function() {
            $('#buscarSucursal').val('');
            filtrarSucursales();
        });
    });
    
    function cargarSucursales() {
        $.ajax({
            url: 'gestionar_sucursales.php',
            type: 'GET',
            data: { action: 'listar' },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    todasLasSucursales = response.data;
                    sucursalesFiltradas = [...todasLasSucursales];
                    mostrarSucursales(sucursalesFiltradas);
                    actualizarContador();
                } else {
                    showMessage('error', 'Error', 'No se pudieron cargar las sucursales');
                }
            },
            error: function() {
                showMessage('error', 'Error', 'Error de conexión al cargar sucursales');
            }
        });
    }
    
    function mostrarSucursales(sucursales) {
        let html = '';
        sucursales.forEach(function(sucursal) {
            html += `
                <tr>
                    <td>${sucursal.id}</td>
                    <td>${sucursal.segmento}</td>
                    <td>${sucursal.cebe}</td>
                    <td>${sucursal.local}</td>
                    <td>${sucursal.m2_neto}</td>
                    <td>${sucursal.localidad}</td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="editarSucursal(${sucursal.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="eliminarSucursal(${sucursal.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        $('#cuerpoTablaSucursales').html(html);
    }
    
    function filtrarSucursales() {
        const termino = $('#buscarSucursal').val().toLowerCase().trim();
        
        if (termino === '') {
            sucursalesFiltradas = [...todasLasSucursales];
        } else {
            sucursalesFiltradas = todasLasSucursales.filter(function(sucursal) {
                return sucursal.segmento.toLowerCase().includes(termino) ||
                       sucursal.cebe.toString().includes(termino) ||
                       sucursal.local.toLowerCase().includes(termino) ||
                       sucursal.localidad.toLowerCase().includes(termino);
            });
        }
        
        mostrarSucursales(sucursalesFiltradas);
        actualizarContador();
    }
    
    function actualizarContador() {
        const total = todasLasSucursales.length;
        const filtradas = sucursalesFiltradas.length;
        
        if (filtradas === total) {
            $('#contadorResultados').text(`${total} sucursal(es) total`);
        } else {
            $('#contadorResultados').text(`${filtradas} de ${total} sucursal(es)`);
        }
    }
    
    // Manejar formulario de sucursal
    $('#formSucursal').on('submit', function(e) {
        e.preventDefault();
        
        const sucursalId = $('#sucursal_id').val();
        const action = sucursalId ? 'editar' : 'crear';
        
        $.ajax({
            url: 'gestionar_sucursales.php',
            type: 'POST',
            data: $(this).serialize() + '&action=' + action,
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#modalSucursal').modal('hide');
                    cargarSucursales();
                    showMessage('success', '¡Éxito!', response.message);
                    $('#formSucursal')[0].reset();
                    $('#sucursal_id').val('');
                    // Limpiar búsqueda después de agregar/editar
                    $('#buscarSucursal').val('');
                } else {
                    showMessage('error', 'Error', response.message);
                }
            },
            error: function() {
                showMessage('error', 'Error', 'Error de conexión al guardar');
            }
        });
    });
    
    function editarSucursal(id) {
        $.ajax({
            url: 'gestionar_sucursales.php',
            type: 'GET',
            data: { action: 'obtener', id: id },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    const sucursal = response.data;
                    $('#sucursal_id').val(sucursal.id);
                    $('#segmento').val(sucursal.segmento);
                    $('#cebe').val(sucursal.cebe);
                    $('#local').val(sucursal.local);
                    $('#m2_neto').val(sucursal.m2_neto);
                    $('#localidad').val(sucursal.localidad);
                    $('#tituloModalSucursal').text('Editar Sucursal');
                    $('#modalSucursal').modal('show');
                } else {
                    showMessage('error', 'Error', 'No se pudo cargar la sucursal');
                }
            }
        });
    }
    
    function eliminarSucursal(id) {
        if(confirm('¿Está seguro de eliminar esta sucursal?')) {
            $.ajax({
                url: 'gestionar_sucursales.php',
                type: 'POST',
                data: { action: 'eliminar', id: id },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        cargarSucursales();
                        showMessage('success', '¡Éxito!', response.message);
                    } else {
                        showMessage('error', 'Error', response.message);
                    }
                }
            });
        }
    }
    
    // Limpiar modal al cerrarlo
    $('#modalSucursal').on('hidden.bs.modal', function() {
        $('#formSucursal')[0].reset();
        $('#sucursal_id').val('');
        $('#tituloModalSucursal').text('Agregar Sucursal');
    });
    </script>
</body>
</html>