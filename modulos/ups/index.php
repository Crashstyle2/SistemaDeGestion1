<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

include_once '../../config/database.php';
include_once '../../models/MantenimientoUPS.php';
include_once '../../includes/verificar_permiso.php';

// Función para determinar el color de la fila
function determinarColorFila($fechaProximo) {
    $fecha_proximo = new DateTime($fechaProximo);
    $hoy = new DateTime();
    $diferencia = $fecha_proximo->diff($hoy);
    $dias_diferencia = $diferencia->days * ($fecha_proximo < $hoy ? -1 : 1);
    
    if ($dias_diferencia < 0) {
        return 'table-danger'; // rojo para mantenimientos vencidos (1+ días pasados)
    } elseif ($dias_diferencia <= 30) {
        return 'table-warning'; // amarillo para próximos 30 días
    } else {
        return 'table-success'; // verde para más de 30 días
    }
}

$database = new Database();
$db = $database->getConnection();

$mantenimiento = new MantenimientoUPS($db);
$stmt = $mantenimiento->leerTodos();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Mantenimiento UPS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <div class="d-flex flex-column w-100">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-bolt mr-2 text-primary"></i>
                    <span class="h5 mb-0">Sistema UPS</span>
                </div>
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-user mr-2"></i>
                    <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre'] ?? ''); ?></span>
                </div>
                <div class="d-flex">
                    <a href="../../dashboard.php" class="btn btn-outline-primary">
                        <i class="fas fa-home mr-2"></i>Volver al Panel
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-3">Mantenimiento de UPS</h2>
        
        <?php if(isset($_GET['mensaje'])): ?>
        <div class="alert alert-<?php echo $_GET['tipo'] ?? 'success'; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_GET['mensaje']); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <!-- Agregar campo de búsqueda -->
        <div class="mb-3">
            <input type="text" id="searchInput" class="form-control" placeholder="Buscar en cualquier campo...">
        </div>
        
        <div class="d-flex flex-column mb-3">
            <?php if($_SESSION['user_rol'] === 'administrador'): ?>
                <a href="crear.php" class="btn btn-primary mb-2">
                    <i class="fas fa-plus mr-2"></i>Nuevo Registro
                </a>
            <?php endif; ?>
            <a href="../../exportar_excel.php" class="btn btn-success">
                <i class="fas fa-file-excel mr-2"></i>Exportar a Excel
            </a>
            <a href="../../dashboard.php" class="btn btn-secondary mt-2">
                <i class="fas fa-home mr-2"></i>Volver al Inicio
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="dataTable">
                <thead class="thead-dark">
                    <tr>
                        <th>Patrimonio</th>
                        <th>Cadena</th>
                        <th>Sucursal</th>
                        <th>Marca</th>
                        <th>Tipo Batería</th>
                        <th>Cantidad</th>
                        <th>Potencia</th>
                        <th>Último Mant.</th>
                        <th>Próximo Mant.</th>
                        <th>Observaciones</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr class="<?php echo determinarColorFila($row['fecha_proximo_mantenimiento']); ?>">
                            <td><?php echo htmlspecialchars($row['patrimonio']); ?></td>
                            <td><?php echo htmlspecialchars($row['cadena']); ?></td>
                            <td><?php echo htmlspecialchars($row['sucursal']); ?></td>
                            <td><?php echo htmlspecialchars($row['marca']); ?></td>
                            <td><?php echo htmlspecialchars($row['tipo_bateria']); ?></td>
                            <td><?php echo htmlspecialchars($row['cantidad']); ?></td>
                            <td><?php echo htmlspecialchars($row['potencia_ups']); ?></td>
                            <td><?php echo htmlspecialchars($row['fecha_ultimo_mantenimiento']); ?></td>
                            <td><?php echo htmlspecialchars($row['fecha_proximo_mantenimiento']); ?></td>
                            <td><?php echo htmlspecialchars($row['observaciones'] ?? ''); ?></td>
                            <td class="text-dark"><?php echo htmlspecialchars($row['estado_mantenimiento'] ?? 'Pendiente'); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <?php if($_SESSION['user_rol'] === 'administrador'): ?>
                                        <a href="editar.php?id=<?php echo $row['patrimonio']; ?>" class="btn btn-info" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="realizar_mantenimiento.php?id=<?php echo $row['patrimonio']; ?>" class="btn btn-success" title="Realizar Mantenimiento">
                                        <i class="fas fa-tools"></i>
                                    </a>
                                    <a href="historial.php?id=<?php echo $row['patrimonio']; ?>" class="btn btn-info" title="Historial">
                                        <i class="fas fa-history"></i>
                                    </a>
                                    <?php if($_SESSION['user_rol'] === 'administrador'): ?>
                                        <button class="btn btn-danger" onclick="confirmarEliminacion(<?php echo $row['patrimonio']; ?>)" title="Eliminar">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>Propiedad Intelectual del Ing. Juan Caceres &copy; 2025</p>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Modal de Confirmación -->
    <div class="modal fade" id="confirmarEliminarModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content" style="border-radius: 10px;">
                <div class="modal-body text-center pt-4">
                    <p class="mb-4">¿Está seguro de que desea eliminar este registro?</p>
                    <div class="d-flex justify-content-center">
                        <button type="button" class="btn btn-primary mx-2" style="background-color: #4a90e2; border: none;" id="btnConfirmarEliminar">Aceptar</button>
                        <button type="button" class="btn btn-secondary mx-2" style="background-color: #6c757d; border: none;" data-dismiss="modal">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast para mensajes -->
    <div id="toastOverlay" class="position-fixed d-none align-items-center justify-content-center" 
         style="top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.4); z-index: 9999;">
        <div id="successToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-success text-white">
                <i class="fas fa-check-circle mr-2"></i>
                <strong class="mr-auto">¡Éxito!</strong>
                <button type="button" class="ml-2 mb-1 close text-white" data-dismiss="toast">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="toast-body bg-white">
                <p class="mb-0">UPS eliminado exitosamente</p>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        let idToDelete = null;

        window.confirmarEliminacion = function(id) {
            idToDelete = id;
            $('#confirmarEliminarModal').modal('show');
        };

        $('#btnConfirmarEliminar').on('click', function() {
            if (idToDelete) {
                $('#confirmarEliminarModal').modal('hide');
                
                $.ajax({
                    url: 'eliminar.php',
                    method: 'GET',
                    data: { id: idToDelete },
                    success: function(response) {
                        if (response === 'success') {
                            // Eliminar la fila específica usando el patrimonio como identificador
                            $(`#dataTable tbody tr td:first-child:contains('${idToDelete}')`).closest('tr').fadeOut(400, function() {
                                $(this).remove();
                            });

                            // Mostrar mensaje de éxito
                            $('#toastOverlay').removeClass('d-none').addClass('d-flex');
                            $('#successToast').toast({
                                animation: true,
                                autohide: true,
                                delay: 1500
                            }).toast('show');

                            // Ocultar el toast después de mostrarlo
                            setTimeout(function() {
                                $('#toastOverlay').removeClass('d-flex').addClass('d-none');
                            }, 1800);
                        }
                    }
                });
            }
        });

        $("#searchInput").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#dataTable tbody tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        });
    });
    </script>
</body>
</html>