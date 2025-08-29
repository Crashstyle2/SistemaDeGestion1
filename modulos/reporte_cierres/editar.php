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

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];
$registro = $reporte->obtenerUno($id);

if (!$registro) {
    header("Location: index.php");
    exit;
}

$tecnicos = $usuario->obtenerTecnicos();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reporte->id = $id;
    $reporte->tecnico_id = $_POST['tecnico_id'];
    $reporte->mes = $_POST['mes'];
    $reporte->anio = $_POST['anio'];
    $reporte->cantidad_cierres = $_POST['cantidad_cierres'];
    $reporte->justificacion = $_POST['justificacion'];
    $reporte->comentario_medida = $_POST['comentario_medida'];

    if($reporte->actualizar()) {
        // Add activity logging
        require_once '../../config/ActivityLogger.php';
        ActivityLogger::logAccion(
            $_SESSION['user_id'],
            'reporte_cierres',
            'editar',
            "Reporte actualizado - ID: {$id}, Técnico ID: {$_POST['tecnico_id']}, Mes: {$_POST['mes']}, Año: {$_POST['anio']}"
        );

        header("Location: index.php?mensaje=Registro actualizado exitosamente");
        exit;
    } else {
        $error = "Error al actualizar el registro";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Registro de Cierre</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="container mt-4">
        <h2>Editar Registro de Cierre</h2>
        <form method="POST" class="needs-validation" novalidate>
            <div class="form-group">
                <label>Técnico:</label>
                <select name="tecnico_id" class="form-control" required>
                    <option value="">Seleccione un técnico</option>
                    <?php while ($tecnico = $tecnicos->fetch(PDO::FETCH_ASSOC)): ?>
                        <option value="<?php echo $tecnico['id']; ?>" 
                                <?php echo ($tecnico['id'] == $registro['tecnico_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tecnico['nombre']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Mes:</label>
                    <select name="mes" class="form-control" required>
                        <?php
                        $meses = array(
                            1 => "Enero", 2 => "Febrero", 3 => "Marzo", 4 => "Abril",
                            5 => "Mayo", 6 => "Junio", 7 => "Julio", 8 => "Agosto",
                            9 => "Septiembre", 10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre"
                        );
                        foreach ($meses as $num => $nombre) {
                            $selected = ($num == $registro['mes']) ? 'selected' : '';
                            echo "<option value='$num' $selected>$nombre</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label>Año:</label>
                    <select name="anio" class="form-control" required>
                        <?php
                        $anio_actual = date('Y');
                        for ($i = 2023; $i <= $anio_actual; $i++) {
                            $selected = ($i == $registro['anio']) ? 'selected' : '';
                            echo "<option value='$i' $selected>$i</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Cantidad de Cierres:</label>
                <input type="number" name="cantidad_cierres" class="form-control" 
                       value="<?php echo htmlspecialchars($registro['cantidad_cierres']); ?>" required>
            </div>

            <div class="form-group">
                <label>Justificación:</label>
                <select name="justificacion" class="form-control" id="justificacion">
                    <option value="ninguna" <?php echo ($registro['justificacion'] == 'ninguna') ? 'selected' : ''; ?>>Ninguna</option>
                    <option value="vacaciones" <?php echo ($registro['justificacion'] == 'vacaciones') ? 'selected' : ''; ?>>Vacaciones</option>
                    <option value="reposo_medico" <?php echo ($registro['justificacion'] == 'reposo_medico') ? 'selected' : ''; ?>>Reposo Médico</option>
                </select>
            </div>

            <div class="form-group" id="comentarioDiv" style="display: <?php echo ($registro['justificacion'] != 'ninguna') ? 'block' : 'none'; ?>;">
                <label>Comentario/Medida:</label>
                <textarea name="comentario_medida" class="form-control" rows="3"><?php echo htmlspecialchars($registro['comentario_medida']); ?></textarea>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">Actualizar</button>
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#justificacion').change(function() {
                if ($(this).val() === 'ninguna') {
                    $('#comentarioDiv').hide();
                } else {
                    $('#comentarioDiv').show();
                }
            });
        });
    </script>
</body>
</html>