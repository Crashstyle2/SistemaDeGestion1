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

// Configuración de paginación
$registros_por_pagina = 25;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Obtener total de registros y calcular páginas
$total_registros = $mantenimiento->contarTodos($busqueda);
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Obtener registros de la página actual
$stmt = $mantenimiento->leerConPaginacion($registros_por_pagina, $offset, $busqueda);
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
    <style>
        .details-row {
            display: none;
            background-color: #f8f9fa;
        }
        .btn-toggle {
            background: none;
            border: none;
            color: #007bff;
            cursor: pointer;
            padding: 0;
        }
        .btn-toggle:hover {
            color: #0056b3;
            text-decoration: underline;
        }
        .compact-table th {
            font-size: 0.9rem;
            padding: 0.5rem;
        }
        .compact-table td {
            padding: 0.5rem;
            vertical-align: middle;
        }
        .details-content {
            padding: 1rem;
            border-left: 4px solid #007bff;
            margin: 0.5rem 0;
        }
        /* Estilos para dropdown */
        .dropdown-menu {
            min-width: 180px;
        }
        .dropdown-item {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        /* Columna de acciones más compacta */
        .compact-table th:last-child,
        .compact-table td:last-child {
            width: 100px;
            min-width: 100px;
            max-width: 100px;
            text-align: center;
        }
        /* Columna Ver Detalles también compacta */
        .compact-table th:nth-child(6),
        .compact-table td:nth-child(6) {
            width: 90px;
            min-width: 90px;
            max-width: 90px;
            text-align: center;
        }
        .details-content {
            padding: 1rem;
            border-left: 4px solid #007bff;
            margin: 0.5rem 0;
        }
        /* Estilos para dropdown compacto */
        .dropdown {
            width: 100%;
        }
        .dropdown .btn {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            width: 100%;
        }
        .dropdown-menu {
            min-width: 160px;
            font-size: 0.85rem;
        }
        .dropdown-item {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }
        .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        .dropdown-item i {
            width: 16px;
        }
        .details-content {
            padding: 1rem;
            border-left: 4px solid #007bff;
            margin: 0.5rem 0;
        }
        /* Estilos para botones compactos */
        .btn-group .btn {
            border-radius: 0;
            margin-right: 1px;
        }
        .btn-group .btn:first-child {
            border-top-left-radius: 0.25rem;
            border-bottom-left-radius: 0.25rem;
        }
        .btn-group .btn:last-child {
            border-top-right-radius: 0.25rem;
            border-bottom-right-radius: 0.25rem;
            margin-right: 0;
        }
        /* Estilos para dropdown */
        .dropdown-menu {
            min-width: 180px;
        }
        .dropdown-item {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        .dropdown-item:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center w-100">
                <div class="d-flex align-items-center">
                    <i class="fas fa-bolt mr-2 text-primary"></i>
                    <span class="h5 mb-0">Sistema de UPS</span>
                </div>
                <div class="d-flex align-items-center">
                    <i class="fas fa-user mr-2 text-primary"></i>
                    <span class="h5 mb-0">Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre'] ?? ''); ?></span>
                </div>
                <div class="d-flex align-items-center">
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

        <!-- Campo de búsqueda -->
        <form method="GET" class="mb-3">
            <div class="input-group">
                <input type="text" name="busqueda" class="form-control" 
                       placeholder="Buscar en cualquier campo..." 
                       value="<?php echo htmlspecialchars($busqueda); ?>">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                    <?php if(!empty($busqueda)): ?>
                        <a href="index.php" class="btn btn-outline-danger">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
        
        <!-- Información de paginación -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="pagination-info">
                Mostrando <?php echo $offset + 1; ?> - <?php echo min($offset + $registros_por_pagina, $total_registros); ?> 
                de <?php echo $total_registros; ?> registros
                <?php if(!empty($busqueda)): ?>
                    (filtrado por: "<?php echo htmlspecialchars($busqueda); ?>")
                <?php endif; ?>
            </div>
            <div>
                <?php if(in_array($_SESSION['user_rol'], ['administrador', 'supervisor'])): ?>
                    <a href="crear.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus mr-1"></i>Nuevo
                    </a>
                <?php endif; ?>
                <a href="../../exportar_excel.php" class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel mr-1"></i>Excel
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped compact-table" id="dataTable">
                <thead class="thead-dark">
                    <tr>
                        <th>Cadena</th>
                        <th>Sucursal</th>
                        <th>Último Mant.</th>
                        <th>Próximo Mant.</th>
                        <th>Estado</th>
                        <th>Ver Detalles</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr class="<?php echo determinarColorFila($row['fecha_proximo_mantenimiento']); ?>" data-patrimonio="<?php echo $row['patrimonio']; ?>">
                            <td><?php echo htmlspecialchars($row['cadena']); ?></td>
                            <td><?php echo htmlspecialchars($row['sucursal']); ?></td>
                            <td><?php echo htmlspecialchars($row['fecha_ultimo_mantenimiento']); ?></td>
                            <td><?php echo htmlspecialchars($row['fecha_proximo_mantenimiento']); ?></td>
                            <td class="text-dark"><?php echo htmlspecialchars($row['estado_mantenimiento'] ?? 'Pendiente'); ?></td>
                            <td>
                                <button class="btn-toggle" onclick="toggleDetails(<?php echo $row['patrimonio']; ?>)">
                                    <i class="fas fa-eye mr-1"></i>Ver UPS
                                </button>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-cog mr-1"></i>Acciones
                                    </button>
                                    <div class="dropdown-menu">
                                        <?php if(in_array($_SESSION['user_rol'], ['administrador', 'supervisor'])): ?>
                                            <a class="dropdown-item" href="editar.php?id=<?php echo $row['patrimonio']; ?>">
                                                <i class="fas fa-edit mr-2 text-info"></i>Editar
                                            </a>
                                        <?php endif; ?>
                                        <a class="dropdown-item" href="realizar_mantenimiento.php?id=<?php echo $row['patrimonio']; ?>">
                                            <i class="fas fa-tools mr-2 text-success"></i>Realizar Mantenimiento
                                        </a>
                                        <a class="dropdown-item" href="historial.php?id=<?php echo $row['patrimonio']; ?>">
                                            <i class="fas fa-history mr-2 text-info"></i>Ver Historial
                                        </a>
                                        <?php if(in_array($_SESSION['user_rol'], ['administrador', 'supervisor'])): ?>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item text-danger" href="#" onclick="confirmarEliminacion(<?php echo $row['patrimonio']; ?>)">
                                                <i class="fas fa-trash-alt mr-2"></i>Eliminar
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <!-- Fila de detalles oculta -->
                        <tr class="details-row" id="details-<?php echo $row['patrimonio']; ?>">
                            <td colspan="7">
                                <div class="details-content">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-info-circle mr-2"></i>Información General</h6>
                                            <p><strong>Patrimonio:</strong> <?php echo htmlspecialchars($row['patrimonio']); ?></p>
                                            <p><strong>Marca:</strong> <?php echo htmlspecialchars($row['marca']); ?></p>
                                            <p><strong>Potencia UPS:</strong> <?php echo htmlspecialchars($row['potencia_ups']); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-battery-half mr-2"></i>Información de Batería</h6>
                                            <p><strong>Tipo Batería:</strong> <?php echo htmlspecialchars($row['tipo_bateria']); ?></p>
                                            <p><strong>Cantidad:</strong> <?php echo htmlspecialchars($row['cantidad']); ?></p>
                                            <p><strong>Observaciones:</strong> <?php echo htmlspecialchars($row['observaciones'] ?? 'Sin observaciones'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Paginación -->
        <?php if($total_paginas > 1): ?>
        <nav aria-label="Navegación de páginas">
            <ul class="pagination justify-content-center">
                <!-- Botón Anterior -->
                <li class="page-item <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?><?php echo !empty($busqueda) ? '&busqueda=' . urlencode($busqueda) : ''; ?>">
                        <i class="fas fa-chevron-left"></i> Anterior
                    </a>
                </li>
                
                <?php
                // Calcular rango de páginas a mostrar
                $inicio = max(1, $pagina_actual - 2);
                $fin = min($total_paginas, $pagina_actual + 2);
                
                // Mostrar primera página si no está en el rango
                if($inicio > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?pagina=1<?php echo !empty($busqueda) ? '&busqueda=' . urlencode($busqueda) : ''; ?>">1</a>
                    </li>
                    <?php if($inicio > 2): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif;
                endif;
                
                // Mostrar páginas en el rango
                for($i = $inicio; $i <= $fin; $i++): ?>
                    <li class="page-item <?php echo ($i == $pagina_actual) ? 'active' : ''; ?>">
                        <a class="page-link" href="?pagina=<?php echo $i; ?><?php echo !empty($busqueda) ? '&busqueda=' . urlencode($busqueda) : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor;
                
                // Mostrar última página si no está en el rango
                if($fin < $total_paginas): 
                    if($fin < $total_paginas - 1): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="?pagina=<?php echo $total_paginas; ?><?php echo !empty($busqueda) ? '&busqueda=' . urlencode($busqueda) : ''; ?>">
                            <?php echo $total_paginas; ?>
                        </a>
                    </li>
                <?php endif; ?>
                
                <!-- Botón Siguiente -->
                <li class="page-item <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?><?php echo !empty($busqueda) ? '&busqueda=' . urlencode($busqueda) : ''; ?>">
                        Siguiente <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>



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
                            // Eliminar las filas (principal y detalles)
                            $(`tr[data-patrimonio='${idToDelete}']`).fadeOut(400, function() {
                                $(this).remove();
                            });
                            $(`#details-${idToDelete}`).fadeOut(400, function() {
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
            $("#dataTable tbody tr:not(.details-row)").filter(function() {
                var isVisible = $(this).text().toLowerCase().indexOf(value) > -1;
                $(this).toggle(isVisible);
                // También ocultar la fila de detalles correspondiente si la principal no es visible
                var patrimonio = $(this).data('patrimonio');
                if (!isVisible) {
                    $(`#details-${patrimonio}`).hide();
                }
                return isVisible;
            });
        });
    });

    // Función para mostrar/ocultar detalles
    function toggleDetails(patrimonio) {
        const detailsRow = $(`#details-${patrimonio}`);
        const button = $(`tr[data-patrimonio='${patrimonio}'] .btn-toggle`);
        
        if (detailsRow.is(':visible')) {
            detailsRow.slideUp(300);
            button.html('<i class="fas fa-eye mr-1"></i>Ver UPS');
        } else {
            // Ocultar otros detalles abiertos
            $('.details-row:visible').slideUp(300);
            $('.btn-toggle').html('<i class="fas fa-eye mr-1"></i>Ver UPS');
            
            // Mostrar los detalles de este registro
            detailsRow.slideDown(300);
            button.html('<i class="fas fa-eye-slash mr-1"></i>Ocultar');
        }
    }
    </script>
</body>
</html>