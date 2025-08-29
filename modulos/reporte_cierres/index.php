<?php
session_start();
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_rol'], ['administrador', 'supervisor'])) {
    header("Location: ../../login.php");
    exit;
}

include_once '../../config/database.php';
include_once '../../models/ReporteCierres.php';

$database = new Database();
$db = $database->getConnection();
$reporte = new ReporteCierres($db);

$anio_actual = isset($_GET['anio']) ? $_GET['anio'] : date('Y');
$meses_disponibles = [
    1 => "ENERO", 2 => "FEBRERO", 3 => "MARZO", 4 => "ABRIL",
    5 => "MAYO", 6 => "JUNIO", 7 => "JULIO", 8 => "AGOSTO",
    9 => "SEPTIEMBRE", 10 => "OCTUBRE", 11 => "NOVIEMBRE", 12 => "DICIEMBRE"
];

// Convert comma-separated string to array if needed
$meses_seleccionados = isset($_GET['meses']) ? 
    (is_array($_GET['meses']) ? $_GET['meses'] : explode(',', $_GET['meses'])) : 
    [];

// Convert string numbers to integers
$meses_seleccionados = array_map('intval', array_filter($meses_seleccionados));
$stmt = $reporte->obtenerReporteAnual($anio_actual, $meses_seleccionados);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Cierres por Técnico</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .celda-muy-bajo { background-color: #ff8080 !important; }
        .celda-bajo { background-color: #ffcccc !important; }
        .celda-normal { background-color: #ccffcc !important; }
        .celda-justificado { background-color: #98FB98 !important; }
        
        .mes-celda {
            position: relative;
            cursor: pointer;
        }
    </style>
    <style>
    .error-modal {
        font-family: Arial, sans-serif;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 0 20px rgba(0,0,0,0.2);
    }
    .swal2-popup {
        padding: 1.5em;
    }
    .swal2-title {
        color: #e74c3c;
        font-size: 1.5em;
    }
    .swal2-content {
        color: #555;
    }
    .swal2-confirm {
        background-color: #3498db !important;
    }
    </style>
    <style>
    .mes-celda {
        position: relative;
    
        .tooltip-custom {
            display: none;
            position: absolute;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            z-index: 1000;
            max-width: 200px;
            white-space: normal;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
        }
    
        .mes-celda:hover .tooltip-custom {
            display: block;
        }
    </style>
    <style>
        .filter-input {
            width: 100%;
            padding: 5px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .thead-dark th {
            position: relative;
        }
    </style>
    <style>
    .filter-container {
        display: none;
        position: absolute;
        background: white;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 10px;
        z-index: 1000;
        min-width: 250px;
        max-height: 400px;
        overflow-y: auto;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        color: #333;
    }

    .filter-toggle {
        cursor: pointer;
        padding-right: 20px;
        color: white; /* Make header text white */
    }

    .filter-option {
        padding: 8px;
        margin: 2px 0;
        cursor: pointer;
        color: #333;
        background-color: white;
        display: flex;
        align-items: center;
        border-radius: 4px;
    }

    .filter-option:hover {
        background-color: #f8f9fa;
    }

    .filter-option.selected {
        background-color: #e3f2fd;
    }

    .filter-option input[type="checkbox"] {
        margin-right: 8px;
    }

    .filter-actions {
        padding-top: 8px;
        margin-top: 8px;
        border-top: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
    }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Reporte de Cierres por Técnico</h2>
            <div>
                <!-- Eliminar esta línea -->
                <!-- <a href="crear.php" class="btn btn-primary">
                    <i class="fas fa-plus mr-2"></i>Nuevo Registro
                </a> -->
                <a href="../../dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-2"></i>Volver
                </a>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="form-inline justify-content-center" id="filtroForm">
                    <div class="form-group mx-2">
                        <label class="mr-2">Año:</label>
                        <select name="anio" class="form-control">
                            <?php
                            $anio_inicio = 2024;
                            $anio_fin = date('Y') + 10;
                            for ($i = $anio_inicio; $i <= $anio_fin; $i++) {
                                $selected = ($i == $anio_actual) ? 'selected' : '';
                                echo "<option value='$i' $selected>$i</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <input type="hidden" name="meses" id="mesesSeleccionados" value="<?php echo implode(',', $meses_seleccionados); ?>">
                    <button type="button" class="btn btn-info mx-2" data-toggle="modal" data-target="#selectorMeses">
                        <i class="fas fa-calendar-alt mr-2"></i>Seleccionar Meses
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter mr-2"></i>Filtrar
                    </button>
                </form>
            </div>
        </div>

        <!-- Modal Selector de Meses -->
        <div class="modal fade" id="selectorMeses" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Seleccionar Meses</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <?php foreach ($meses_disponibles as $num => $nombre): ?>
                            <div class="col-md-4 mb-2">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" 
                                           id="mes<?php echo $num; ?>" 
                                           name="meses[]" 
                                           value="<?php echo $num; ?>"
                                           <?php echo in_array($num, $meses_seleccionados) ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="mes<?php echo $num; ?>">
                                        <?php echo $nombre; ?>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-primary" id="aplicarMeses">Aplicar</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-fixed">
                <thead class="thead-dark">
                    <tr>
                        <th>
                            <span class="filter-toggle" data-column="tecnico">Técnico</span>
                            <div class="filter-container" id="tecnico-filter">
                                <input type="text" class="form-control mb-2" placeholder="Buscar técnico...">
                                <div class="filter-options"></div>
                            </div>
                        </th>
                        <th>
                            <span class="filter-toggle" data-column="usuario">Usuario</span>
                            <div class="filter-container" id="usuario-filter">
                                <input type="text" class="form-control mb-2" placeholder="Buscar usuario...">
                                <div class="filter-options"></div>
                            </div>
                        </th>
                        <?php
                        foreach ($meses_disponibles as $num => $nombre) {
                            if (empty($meses_seleccionados) || in_array($num, $meses_seleccionados)) {
                                echo "<th>" . $nombre . "</th>";
                            }
                        }
                        ?>
                        <th>TOTAL</th>
                        <th>
                            <span class="filter-toggle" data-column="codtec">COD.TEC.</span>
                            <div class="filter-container" id="codtec-filter">
                                <input type="text" class="form-control mb-2" placeholder="Buscar código...">
                                <div class="filter-options"></div>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($stmt) {
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { 
                            $meses = array_map('strtolower', $meses_disponibles);
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['nombre_tecnico']); ?></td>
                            <td><?php echo htmlspecialchars($row['usuario']); ?></td>
                            <?php 
                            foreach ($meses as $num => $mes) {
                                if (empty($meses_seleccionados) || in_array($num, $meses_seleccionados)) {
                                    $cantidad = $row[$mes] ?? '0';
                                    $justificacion = $row['justificacion_'.$mes] ?? 'N';
                                    $comentario = $row['comentario_'.$mes] ?? '';
                                    
                                    $clase_color = '';
                                    if ($justificacion !== 'N') {
                                        $clase_color = 'celda-normal'; // Changed to normal for unified green
                                    } else {
                                        if ($cantidad >= 0 && $cantidad <= 10) {
                                            $clase_color = 'celda-muy-bajo';
                                        } elseif ($cantidad <= 45) {
                                            $clase_color = 'celda-bajo';
                                        } else {
                                            $clase_color = 'celda-normal';
                                        }
                                    }
                                    
                                    echo "<td class='mes-celda {$clase_color}' 
                                          data-tecnico='{$row['id']}' 
                                          data-mes='{$num}'
                                          data-cantidad='{$cantidad}'
                                          data-justificacion='{$justificacion}'
                                          data-comentario='" . htmlspecialchars($comentario) . "'
                                          data-cod-tec='" . htmlspecialchars($row['cod_tec'] ?? '') . "'>";
                                    echo $cantidad;
                                    
                                    if (!empty($comentario)) {
                                        echo "<div class='tooltip-custom'>" . htmlspecialchars($comentario) . "</div>";
                                    }
                                    
                                    if ($justificacion !== 'N') {
                                        $justificacionTexto = [
                                            'R' => ' (RM)',
                                            'V' => ' (V)',
                                            'P' => ' (PE)'
                                        ];
                                        echo $justificacionTexto[$justificacion] ?? '';
                                    }
                                    echo "</td>";
                                }
                            }
                            ?>
                            <td class="total-column"><?php echo $row['total_anual'] ?: '0'; ?></td>
                            <td><?php echo htmlspecialchars($row['cod_tec'] ?: ''); ?></td>
                        </tr>
                    <?php 
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Sección de estadísticas -->
    <div class="container-fluid">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Estadísticas</h5>
                <div class="row">
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h6>Promedio General</h6>
                                <?php
                                $total_cierres = 0;
                                $total_meses_con_datos = 0;
                                $stmt_stats = $reporte->obtenerReporteAnual($anio_actual, $meses_seleccionados);
                                if ($stmt_stats) {
                                    while ($row = $stmt_stats->fetch(PDO::FETCH_ASSOC)) {
                                        foreach ($meses as $num => $mes) {
                                            if ($row[$mes] !== null) {
                                                $total_cierres += $row[$mes];
                                                $total_meses_con_datos++;
                                            }
                                        }
                                    }
                                    $promedio_general = $total_meses_con_datos > 0 ? 
                                        round($total_cierres / $total_meses_con_datos, 2) : 0;
                                    echo "<h4>$promedio_general</h4>";
                                    echo "<small>cierres por mes</small>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h6>Mejor Rendimiento</h6>
                                <?php
                                $mejor_tecnico = '';
                                $mejor_promedio = 0;
                                $stmt_stats = $reporte->obtenerReporteAnual($anio_actual, $meses_seleccionados);
                                if ($stmt_stats) {
                                    while ($row = $stmt_stats->fetch(PDO::FETCH_ASSOC)) {
                                        $total_tecnico = 0;
                                        $meses_tecnico = 0;
                                        foreach ($meses as $mes) {
                                            if ($row[$mes] !== null) {
                                                $total_tecnico += $row[$mes];
                                                $meses_tecnico++;
                                            }
                                        }
                                        if ($meses_tecnico > 0) {
                                            $promedio_tecnico = $total_tecnico / $meses_tecnico;
                                            if ($promedio_tecnico > $mejor_promedio) {
                                                $mejor_promedio = $promedio_tecnico;
                                                $mejor_tecnico = $row['nombre_tecnico'];
                                            }
                                        }
                                    }
                                    echo "<h4>" . round($mejor_promedio, 2) . "</h4>";
                                    echo "<small>" . htmlspecialchars($mejor_tecnico) . "</small>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card bg-warning">
                            <div class="card-body">
                                <h6>Total Cierres</h6>
                                <h4><?php echo $total_cierres; ?></h4>
                                <small>en el período</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Remove or comment out the button div -->
                    <!--<div class="col-md-3">
                        <button class="btn btn-primary btn-block" onclick="generarReporteDetallado()">
                            <i class="fas fa-file-pdf mr-2"></i>Generar Reporte Detallado
                        </button>
                    </div>-->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Edición -->
    <div class="modal fade" id="editarCierreModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Cierre</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formEditarCierre">
                        <input type="hidden" id="edit_tecnico_id" name="tecnico_id">
                        <input type="hidden" id="edit_mes" name="mes">
                        <input type="hidden" id="edit_anio" name="anio">
                        
                        <div class="form-group">
                            <label>COD.TEC.:</label>
                            <input type="text" class="form-control" id="edit_cod_tec" name="cod_tec">
                        </div>
                        
                        <div class="form-group">
                            <label>Cantidad de Cierres:</label>
                            <input type="number" class="form-control" id="edit_cantidad" name="cantidad" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Justificación:</label>
                            <select class="form-control" id="edit_justificacion" name="justificacion">
                                <option value="N">Ninguna</option>
                                <option value="R">Reposo Médico</option>
                                <option value="V">Vacaciones</option>
                                <option value="P">Permiso Especial</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Comentario:</label>
                            <textarea class="form-control" id="edit_comentario" name="comentario" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="guardarCierre">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
    $(document).ready(function() {
        console.log('Script cargado');

        // Manejador de clics para las celdas
        $('body').on('click', '.mes-celda', function() {
            console.log('Celda clickeada');
            
            const tecnico_id = $(this).data('tecnico');
            const mes = $(this).data('mes');
            const anio = $('select[name="anio"]').val();
            const cantidad = $(this).data('cantidad');
            const justificacion = $(this).data('justificacion');
            const comentario = $(this).data('comentario');
            const codTec = $(this).data('cod-tec');
            
            console.log('Datos:', {tecnico_id, mes, anio, cantidad, justificacion, comentario, codTec});

            $('#edit_tecnico_id').val(tecnico_id);
            $('#edit_mes').val(mes);
            $('#edit_anio').val(anio);
            $('#edit_cantidad').val(cantidad);
            $('#edit_justificacion').val(justificacion);
            $('#edit_comentario').val(comentario);
            $('#edit_cod_tec').val(codTec);
            
            $('#editarCierreModal').modal('show');
        });

        // Manejador de selección de meses
        $('#aplicarMeses').click(function() {
            var mesesSeleccionados = [];
            $('#selectorMeses input:checked').each(function() {
                mesesSeleccionados.push($(this).val());
            });
            
            $('#mesesSeleccionados').val(mesesSeleccionados.join(','));
            $('#selectorMeses').modal('hide');
            $('#filtroForm').submit();
        });

        // Manejador del botón guardar
        $('#guardarCierre').on('click', function() {
            const formData = {
                tecnico_id: $('#edit_tecnico_id').val(),
                mes: $('#edit_mes').val(),
                anio: $('#edit_anio').val(),
                cantidad: $('#edit_cantidad').val(),
                justificacion: $('#edit_justificacion').val(),
                comentario: $('#edit_comentario').val(),
                cod_tec: $('#edit_cod_tec').val()
            };

            console.log('Enviando datos:', formData);

            $.ajax({
                url: 'guardar_reporte.php',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    console.log('Respuesta:', response);
                    if (response.success) {
                        Swal.fire({
                            title: 'Éxito',
                            text: response.mensaje,
                            icon: 'success'
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: response.mensaje || 'Error al guardar los cambios',
                            icon: 'error'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error AJAX:', {xhr, status, error});
                    Swal.fire({
                        title: 'Error',
                        text: 'Error al guardar los cambios: ' + error,
                        icon: 'error'
                    });
                }
            });
        });

        // Funciones de filtrado
        function initializeFilters() {
            const columns = ['tecnico', 'usuario', 'codtec'];
            
            columns.forEach(column => {
                const values = new Set();
                const container = $(`#${column}-filter .filter-options`);
                
                $('tbody tr').each(function() {
                    const value = column === 'codtec' ? 
                        $(this).find('td:last').text().trim() : 
                        $(this).find(`td:eq(${column === 'tecnico' ? 0 : 1})`).text().trim();
                    if (value) values.add(value);
                });

                container.html(`
                    <div class="filter-option">
                        <input type="checkbox" id="${column}-select-all" checked>
                        <label for="${column}-select-all">Seleccionar todos</label>
                    </div>
                    ${Array.from(values).sort().map(value => `
                        <div class="filter-option">
                            <input type="checkbox" id="${column}-${value}" value="${value}" checked>
                            <label for="${column}-${value}">${value}</label>
                        </div>
                    `).join('')}
                `);

                $(`#${column}-filter input[type="text"]`).on('keyup', function() {
                    const searchText = $(this).val().toLowerCase();
                    $(`#${column}-filter .filter-option`).each(function() {
                        $(this).toggle($(this).text().toLowerCase().includes(searchText));
                    });
                });

                $(`#${column}-select-all`).on('change', function() {
                    const isChecked = $(this).prop('checked');
                    $(`#${column}-filter .filter-option:not(:first) input`).prop('checked', isChecked);
                    applyFilters();
                });

                $(`#${column}-filter .filter-option:not(:first) input`).on('change', applyFilters);
            });
        }

        function applyFilters() {
            $('tbody tr').each(function() {
                const tecnico = $(this).find('td:eq(0)').text().trim();
                const usuario = $(this).find('td:eq(1)').text().trim();
                const codtec = $(this).find('td:last').text().trim();

                const showTecnico = $('#tecnico-filter input:checked').toArray()
                    .some(cb => $(cb).parent().text().trim().includes(tecnico));
                const showUsuario = $('#usuario-filter input:checked').toArray()
                    .some(cb => $(cb).parent().text().trim().includes(usuario));
                const showCodtec = $('#codtec-filter input:checked').toArray()
                    .some(cb => $(cb).parent().text().trim().includes(codtec));

                $(this).toggle(showTecnico && showUsuario && showCodtec);
            });
        }

        // Inicializar filtros
        initializeFilters();

        // Update filter toggle handlers
        $('.filter-toggle').click(function(e) {
            e.preventDefault();
            e.stopPropagation();
            const column = $(this).data('column');
            const container = $(`#${column}-filter`);
            
            // Close other open filters
            $('.filter-container').not(container).hide();
            
            // Toggle current filter
            container.toggle();
            
            // Position the filter container below the header
            const header = $(this).closest('th');
            const headerPos = header.offset();
            container.css({
                top: headerPos.top + header.outerHeight(),
                left: headerPos.left
            });
        });

        // Close filters when clicking outside
        $(document).click(function(e) {
            if (!$(e.target).closest('.filter-container, .filter-toggle').length) {
                $('.filter-container').hide();
            }
        });

        // Prevent filter container clicks from bubbling
        $('.filter-container').click(function(e) {
            e.stopPropagation();
        });
    });
    </script>
</body>
</html>