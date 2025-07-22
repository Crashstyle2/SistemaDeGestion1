<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

require_once '../../config/Database.php';

// Verificar el rol del usuario
$rol = $_SESSION['user_rol'];

// Verificar si es un rol válido
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
                        <form id="formCombustible" action="guardar_registro.php" method="POST">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="fecha">Fecha</label>
                                    <input type="date" class="form-control" id="fecha" name="fecha" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="nombreConductor">Conductor</label>
                                    <input type="text" class="form-control" id="nombreConductor" name="nombreConductor" value="<?php echo htmlspecialchars($_SESSION['nombre']); ?>" readonly>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="chapa">Vehículo Chapa</label>
                                    <input type="text" class="form-control" id="chapa" name="chapa" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="numeroBaucher">Nº Voucher</label>
                                    <input type="text" class="form-control" id="numeroBaucher" name="numeroBaucher" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="tarjeta">Nº tarjeta (últimos 4 dígitos)</label>
                                    <input type="text" class="form-control" id="tarjeta" name="tarjeta" maxlength="4" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="litrosCargados">Litros Cargados</label>
                                    <input type="number" step="0.01" class="form-control" id="litrosCargados" name="litrosCargados" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="tipoVehiculo">Tipo de Vehículo</label>
                                    <select class="form-control" id="tipoVehiculo" name="tipoVehiculo" required>
                                        <option value="">Seleccione tipo de vehículo</option>
                                        <option value="particular">Particular</option>
                                        <option value="movil_retail">Móvil de Retail</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="fecha_carga">Fecha de Carga</label>
                                    <input type="date" class="form-control" id="fecha_carga" name="fecha_carga" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="documento">Documento</label>
                                    <input type="text" class="form-control" id="documento" name="documento" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="hora_carga">Hora de Carga</label>
                                    <input type="time" class="form-control" id="hora_carga" name="hora_carga" value="<?php echo date('H:i'); ?>" required>
                                </div>
                            </div>
                            <div id="recorridos">
                                <h5 class="mb-3">Recorridos</h5>
                                <div class="recorrido-item mb-3">
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="origen_0">Origen</label>
                                            <input type="text" class="form-control" id="origen_0" name="origen[]" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="destino_0">Destino</label>
                                            <input type="text" class="form-control" id="destino_0" name="destino[]" required>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="km_inicial_0">Kilómetro Inicial</label>
                                            <input type="number" step="0.01" class="form-control" id="km_inicial_0" name="km_inicial[]">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="km_final_0">Kilómetro Final</label>
                                            <input type="number" step="0.01" class="form-control" id="km_final_0" name="km_final[]">
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
    <script src="../../assets/js/uso_combustible.js"></script>
</body>
</html>