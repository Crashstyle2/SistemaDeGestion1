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
</head>
<body>
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
                            <a href="ver_registros.php" class="btn btn-light btn-sm">
                                <i class="fas fa-list mr-2"></i>Ver Registros
                            </a>
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
                                    <label for="fecha_carga">Fecha de Carga</label>
                                    <input type="date" class="form-control" id="fecha_carga" name="fecha_carga" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="hora_carga">Hora de Carga</label>
                                    <input type="time" class="form-control" id="hora_carga" name="hora_carga" value="<?php echo date('H:i'); ?>" required>
                                </div>
                            </div>
                            
                            <!-- Sección de Recorridos (sin kilómetros) -->
                            <h5 class="mb-3">Recorridos</h5>
                            <div id="recorridos-container">
                                <div class="recorrido-item border p-3 mb-3">
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Origen</label>
                                            <input type="text" class="form-control" name="origen[]" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Destino</label>
                                            <input type="text" class="form-control" name="destino[]" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-right mb-3">
                                <button type="button" class="btn btn-secondary" onclick="agregarRecorrido()">
                                    <i class="fas fa-plus mr-2"></i>Agregar Recorrido
                                </button>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-lg">
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
    
    // Manejar envío del formulario
    $('#formCombustible').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: 'guardar_registro.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alert('Registro guardado correctamente');
                    $('#formCombustible')[0].reset();
                    // Mantener solo un recorrido
                    $('#recorridos-container').html(`
                        <div class="recorrido-item border p-3 mb-3">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Origen</label>
                                    <input type="text" class="form-control" name="origen[]" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Destino</label>
                                    <input type="text" class="form-control" name="destino[]" required>
                                </div>
                            </div>
                        </div>
                    `);
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error al procesar la solicitud');
            }
        });
    });
    </script>
</body>
</html>