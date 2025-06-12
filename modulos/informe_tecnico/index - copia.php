<?php
session_start();  // Asegurarnos que esté al inicio del archivo
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

include_once '../../config/database.php';
include_once '../../models/InformeTecnico.php';

$database = new Database();
$db = $database->getConnection();
$informe = new InformeTecnico($db);

// Si el usuario no es administrador, solo ver sus propios informes
$tecnico_id = null;
if ($_SESSION['user_rol'] !== 'administrador') {
    $tecnico_id = $_SESSION['user_id'];
}

$stmt = $informe->leerTodos($tecnico_id);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informes Técnicos</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        @media screen and (max-width: 768px) {
            .columna-oculta-movil {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2 class="mb-4">Informes Técnicos</h2>
        
        <div class="mb-3">
            <a href="/MantenimientodeUPS/modulos/informe_tecnico/crear.php" class="btn btn-primary">
                <i class="fas fa-plus mr-2"></i>Nuevo Informe
            </a>
            <a href="../../dashboard.php" class="btn btn-secondary">
                <i class="fas fa-home mr-2"></i>Volver al Panel
            </a>
        </div>

        <style>
            /* Estilos base */
            .table td, .table th {
                font-size: 0.95rem;
            }
            
            /* Estilos móviles */
            @media screen and (max-width: 768px) {
                .table th:not(:nth-child(1)):not(:nth-child(2)):not(:nth-child(3)):not(:nth-child(9)),
                .table td:not(:nth-child(1)):not(:nth-child(2)):not(:nth-child(3)):not(:nth-child(9)) {
                    display: none !important;
                }
                
                .table td, .table th {
                    font-size: 0.85rem;
                    padding: 0.4rem;
                }
                
                /* Ajustar anchos de columnas visibles */
                .table td:nth-child(1) { width: 20%; }  /* Fecha */
                .table td:nth-child(2) { width: 35%; }  /* Local */
                .table td:nth-child(3) { width: 30%; }  /* Técnico */
                .table td:nth-child(9) { width: 15%; }  /* Acciones */
            }
        </style>

        <div class="table-responsive">
            <div class="mb-3">
                <input type="text" id="searchInput" class="form-control" placeholder="Buscar en cualquier campo...">
            </div>
            <!-- Modificar la estructura de la tabla -->
            <table class="table table-hover" id="dataTable">
                <thead>
                    <tr>
                        <th>Acciones</th>
                        <th>Fecha</th>
                        <th>Local</th>
                        <th>Técnico</th>
                        <th class="columna-oculta-movil">Equipo</th>
                        <th class="columna-oculta-movil">Patrimonio</th>
                        <th class="columna-oculta-movil">Jefe Turno</th>
                        <th class="columna-oculta-movil">Sector</th>
                        <th class="columna-oculta-movil">OT</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="ver.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if($_SESSION['user_rol'] === 'administrador'): ?>
                                    <a href="#" class="btn btn-danger btn-sm eliminar-informe" data-id="<?php echo $row['id']; ?>">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?php echo $row['fecha_creacion'] ? htmlspecialchars(date('d/m/y', strtotime($row['fecha_creacion']))) : ''; ?></td>
                            <td><?php echo $row['local'] ? htmlspecialchars($row['local']) : ''; ?></td>
                            <td><?php echo $row['nombre_tecnico'] ? htmlspecialchars($row['nombre_tecnico']) : ''; ?></td>
                            <td class="columna-oculta-movil"><?php echo $row['equipo_asistido'] ? htmlspecialchars($row['equipo_asistido']) : ''; ?></td>
                            <td class="columna-oculta-movil"><?php echo $row['patrimonio'] ? htmlspecialchars($row['patrimonio']) : ''; ?></td>
                            <td class="columna-oculta-movil"><?php echo $row['jefe_turno'] ? htmlspecialchars($row['jefe_turno']) : ''; ?></td>
                            <td class="columna-oculta-movil"><?php echo $row['sector'] ? htmlspecialchars($row['sector']) : ''; ?></td>
                            <td class="columna-oculta-movil"><?php echo !empty($row['orden_trabajo']) ? htmlspecialchars($row['orden_trabajo']) : '-'; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Agregar antes de los modales -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Modal de Confirmación de Eliminación -->
    <div class="modal fade" id="confirmarEliminarModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirmar Eliminación</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="fas fa-exclamation-triangle text-warning mb-3" style="font-size: 3em;"></i>
                    <p class="mb-0">¿Está seguro que desea eliminar este informe?</p>
                    <p class="text-muted small">Esta acción no se puede deshacer</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmarEliminar">Eliminar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Éxito -->
    <div class="modal fade" id="exitoModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Éxito</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="fas fa-check-circle text-success mb-3" style="font-size: 3em;"></i>
                    <p class="mb-0">El informe ha sido eliminado correctamente</p>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media screen and (max-width: 768px) {
            .table td:nth-child(4),
            .table th:nth-child(4),
            .table td:nth-child(5),
            .table th:nth-child(5),
            .table td:nth-child(6),
            .table th:nth-child(6),
            .table td:nth-child(7),
            .table th:nth-child(7),
            .table td:nth-child(8),
            .table th:nth-child(8) {
                display: none;
            }
        }
    </style>
    <script>
    $(document).ready(function() {
        let idToDelete = null;

        $('.eliminar-informe').on('click', function(e) {
            e.preventDefault();
            idToDelete = $(this).data('id');
            $('#confirmarEliminarModal').modal('show');
        });

        $('#btnConfirmarEliminar').on('click', function() {
            if (!idToDelete) return;

            $.ajax({
                url: 'eliminar.php',
                type: 'POST',
                data: { id: idToDelete },
                dataType: 'json',
                success: function(response) {
                    $('#confirmarEliminarModal').modal('hide');
                    if (response.success) {
                        $('#exitoModal').modal('show');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        alert(response.message || 'Error al eliminar el informe');
                    }
                },
                error: function(xhr, status, error) {
                    $('#confirmarEliminarModal').modal('hide');
                    console.error(xhr.responseText);
                    alert('Error al procesar la solicitud');
                }
            });
        });
    });
    </script>
    <script>
    $(document).ready(function() {
        $("#searchInput").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#dataTable tbody tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
    });
    </script>
</body>
</html>