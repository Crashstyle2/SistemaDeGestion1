<?php
session_start();
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_rol'], ['administrador', 'supervisor'])) {
    header("Location: ../../login.php");
    exit;
}

include_once '../../config/database.php';
include_once '../../models/ReporteCierres.php';
include_once '../../models/Usuario.php';

$database = new Database();
$db = $database->getConnection();
$reporte = new ReporteCierres($db);
$usuario = new Usuario($db);

// Obtener lista de técnicos
$tecnicos = $usuario->obtenerTecnicos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Registro de Cierre</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .form-control-sm {
            height: calc(1.8em + 0.5rem + 2px);
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        select.form-control-sm {
            padding-right: 1.75rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .card {
            margin-bottom: 1rem;
        }
        .row {
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2 class="mb-4">Gestión de Cierres</h2>
        
        <div class="card">
            <div class="card-body">
                <form id="form_reporte" method="POST">
                    <input type="hidden" id="registro_id" name="id" value="">
                    
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="tecnico_id" class="small">Técnico:</label>
                                <select class="form-control form-control-sm" id="tecnico_id" name="tecnico_id" required style="width: 100%">
                                    <option value="">Seleccione un técnico</option>
                                    <?php
                                    if($tecnicos) {
                                        while ($tecnico = $tecnicos->fetch(PDO::FETCH_ASSOC)) {
                                            echo "<option value='" . $tecnico['id'] . "'>" . 
                                                 htmlspecialchars($tecnico['nombre']) . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="codigo_tecnico" class="small">Código Técnico:</label>
                                <input type="text" class="form-control form-control-sm" id="codigo_tecnico" 
                                       name="codigo_tecnico" placeholder="Ingrese el código">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="mes" class="small">Mes:</label>
                                <select class="form-control form-control-sm" id="mes" name="mes" required>
                                    <option value="1">Enero</option>
                                    <option value="2">Febrero</option>
                                    <option value="3">Marzo</option>
                                    <option value="4">Abril</option>
                                    <option value="5">Mayo</option>
                                    <option value="6">Junio</option>
                                    <option value="7">Julio</option>
                                    <option value="8">Agosto</option>
                                    <option value="9">Septiembre</option>
                                    <option value="10">Octubre</option>
                                    <option value="11">Noviembre</option>
                                    <option value="12">Diciembre</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="anio" class="small">Año:</label>
                                <select class="form-control form-control-sm" id="anio" name="anio" required>
                                    <?php
                                    $anio_actual = date('Y');
                                    for($i = $anio_actual; $i >= 2023; $i--) {
                                        echo "<option value='$i'>$i</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="cantidad_cierres" class="small">Cantidad de Cierres:</label>
                                <input type="number" class="form-control form-control-sm" id="cantidad_cierres" 
                                       name="cantidad_cierres" value="0" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="justificacion" class="small">Justificación:</label>
                                <select class="form-control form-control-sm" id="justificacion" name="justificacion">
                                    <option value="N">Ninguna</option>
                                    <option value="R">Reposo Médico</option>
                                    <option value="V">Vacaciones</option>
                                    <option value="P">Permiso Especial</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="estado" class="small">Estado:</label>
                                <input type="text" class="form-control form-control-sm" id="estado" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="comentario_medida" class="small">Comentario/Medida:</label>
                        <textarea class="form-control form-control-sm" id="comentario_medida" 
                                name="comentario_medida" rows="3"></textarea>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-save"></i> Guardar Registro
                        </button>
                        <a href="index.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div id="mensaje_alerta" class="alert alert-info mt-3" style="display: none;">
            Editando registro existente
        </div>
    </div>

    <!-- Agregar después del div mensaje_alerta -->
    <div class="modal fade" id="mensajeModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Notificación</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p id="mensajeModalTexto"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modificar el script -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        function verificarDatos() {
            var tecnico_id = $('#tecnico_id').val();
            var mes = $('#mes').val();
            var anio = $('#anio').val();

            if (tecnico_id && mes && anio) {
                $.ajax({
                    url: 'obtener_datos.php',
                    method: 'POST',
                    data: {
                        tecnico_id: tecnico_id,
                        mes: mes,
                        anio: anio
                    },
                    success: function(response) {
                        if (response.existe) {
                            $('#registro_id').val(response.id);
                            $('#cantidad_cierres').val(response.cantidad_cierres);
                            $('#justificacion').val(response.justificacion); // Ya no necesitamos toUpperCase()
                            $('#comentario_medida').val(response.comentario_medida);
                            $('#estado').val(response.estado);
                            $('#mensaje_alerta').show();
                        } else {
                            $('#form_reporte')[0].reset();
                            $('#tecnico_id').val(tecnico_id);
                            $('#mes').val(mes);
                            $('#anio').val(anio);
                            $('#mensaje_alerta').hide();
                        }
                    }
                });
            }
        }

        $('#tecnico_id, #mes, #anio').change(verificarDatos);

        $('#form_reporte').submit(function(e) {
            e.preventDefault();
            
            // Serializar el formulario
            var formData = $(this).serialize();
            
            $.ajax({
                url: 'guardar_reporte.php',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#mensajeModalTexto').text(response.mensaje);
                        $('#mensajeModal').modal('show');
                        
                        // Redirigir después de 2 segundos
                        setTimeout(function() {
                            window.location.href = 'index.php?mensaje=' + encodeURIComponent(response.mensaje);
                        }, 2000);
                    } else {
                        $('#mensajeModalTexto').text(response.mensaje || 'Error al guardar el registro');
                        $('#mensajeModal').modal('show');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', xhr.responseText);
                    $('#mensajeModalTexto').text('Error al procesar la solicitud. Por favor, intente nuevamente.');
                    $('#mensajeModal').modal('show');
                }
            });
        });

        $('#justificacion').change(function() {
            if ($(this).val() !== 'ninguna') {
                $('#comentario_medida').attr('required', true);
            } else {
                $('#comentario_medida').attr('required', false);
            }
        });
    });
    </script>
</body>
</html>