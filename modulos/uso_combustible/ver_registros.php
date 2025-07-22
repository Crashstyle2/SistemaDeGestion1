<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

include_once '../../config/database.php';

// Verificar el rol del usuario
$rol = $_SESSION['user_rol'];

// Permitir acceso a todos los roles válidos
if (!in_array($rol, ['tecnico', 'supervisor', 'administrativo', 'administrador'])) {
    header("Location: ../../dashboard.php");
    exit;
}

// Verificar el tiempo límite de modificación para técnicos (3 horas)
$puedeModificar = false;
if ($rol === 'tecnico') {
    $horasLimite = 3;
    $fechaActual = new DateTime();
    $fechaLimite = $fechaActual->modify("-{$horasLimite} hours");
    $fechaLimiteStr = $fechaLimite->format('Y-m-d H:i:s');
    
    // Agregar condición de tiempo a la consulta SQL
    $sql .= " AND (uc.fecha_registro >= '{$fechaLimiteStr}' OR uc.user_id = {$_SESSION['user_id']})";
    $puedeModificar = true;
} elseif ($rol === 'supervisor') {
    $puedeModificar = true;
}

$db = new Database();
$conn = $db->getConnection();

// Obtener parámetros de búsqueda
$search = isset($_GET['search']) ? $_GET['search'] : '';
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';

// Construir la consulta SQL base
$sql = "SELECT uc.*, u.nombre as nombre_usuario FROM uso_combustible uc 
LEFT JOIN usuarios u ON uc.user_id = u.id WHERE 1=1";
$params = array();

// Agregar filtros si existen
if (!empty($search)) {
    $sql .= " AND (u.nombre LIKE ? OR uc.conductor LIKE ? OR uc.chapa LIKE ? OR uc.origen LIKE ? OR uc.destino LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
}

if (!empty($fecha_inicio)) {
    $sql .= " AND uc.fecha_baucher >= ?";
    $params[] = $fecha_inicio;
}

if (!empty($fecha_fin)) {
    $sql .= " AND uc.fecha_baucher <= ?";
    $params[] = $fecha_fin;
}

$sql .= " ORDER BY uc.fecha_registro DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registros de Uso de Combustible</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css">
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
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-gas-pump mr-2"></i>Registros de Uso de Combustible
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="mb-4">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="search">Búsqueda General:</label>
                                        <input type="text" id="search" name="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>" placeholder="Buscar por técnico, conductor, chapa...">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="fecha_inicio">Fecha Inicio:</label>
                                        <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="fecha_fin">Fecha Fin:</label>
                                        <input type="date" id="fecha_fin" name="fecha_fin" class="form-control" value="<?php echo htmlspecialchars($fecha_fin); ?>">
                                    </div>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fas fa-search mr-2"></i>Buscar
                                    </button>
                                </div>
                            </div>
                        </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-bordered" id="registrosTable">
                <thead class="thead-dark">
                    <tr>
                        <th>Fecha</th>
                        <th>Técnico</th>
                        <th>Tipo Móvil</th>
                        <th>Conductor</th>
                        <th>Chapa</th>
                        <th>Nº Tarjeta</th>
                        <th>Origen</th>
                        <th>Destino</th>
                        <th>KM</th>
                        <?php if ($rol === 'supervisor'): ?>
                        <th>Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registros as $registro): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($registro['fecha_baucher'] . ' ' . $registro['hora_baucher'])); ?></td>
                        <td><?php echo htmlspecialchars($registro['nombre_usuario']); ?></td>
                        <td><?php echo ucfirst($registro['tipo_movil']); ?></td>
                        <td><?php echo htmlspecialchars($registro['conductor']); ?></td>
                        <td><?php echo htmlspecialchars($registro['chapa']); ?></td>
                        <td><?php echo htmlspecialchars($registro['numero_tarjeta']); ?></td>
                        <td><?php echo htmlspecialchars($registro['origen']); ?></td>
                        <td><?php echo htmlspecialchars($registro['destino']); ?></td>
                        <td><?php echo number_format($registro['kilometros_recorridos'], 2); ?></td>
                        <?php if ($rol === 'supervisor'): ?>
                        <td class="text-center">
                            <a href="editar_registro.php?id=<?php echo $registro['id']; ?>" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#registrosTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
            },
            "order": [[0, "desc"]],
            "pageLength": 25
        });
    });
    </script>
</body>
</html>