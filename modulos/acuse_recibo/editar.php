<?php
session_start();
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_rol'], ['administrador', 'supervisor'])) {
    header("Location: ../../login.php");
    exit;
}

require_once '../../config/database.php';
require_once '../../models/AcuseRecibo.php';

$database = new Database();
$db = $database->getConnection();
$acuse = new AcuseRecibo($db);

if(!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$acuse->id = $_GET['id'];
$stmt = $acuse->leerUno();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$row) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Procesar la imagen
    $foto = $row['foto']; // Mantener la foto existente por defecto
    if(isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $foto_temp = $_FILES['foto']['tmp_name'];
        $foto_content = file_get_contents($foto_temp);
        $foto = base64_encode($foto_content);
    }

    // Asignar valores
    $acuse->local = $_POST['local'];
    $acuse->sector = $_POST['sector'];
    $acuse->documento = $_POST['documento'];
    $acuse->foto = $foto;
    $acuse->jefe_encargado = $_POST['jefe_encargado'];
    $acuse->observaciones = $_POST['observaciones'];
    $acuse->firma_digital = $_POST['firma'] ?: $row['firma_digital'];

    if($acuse->actualizar()) {
        // Registrar actividad
        require_once '../../config/ActivityLogger.php';
        ActivityLogger::logAccion(
            $_SESSION['user_id'],
            'acuse_recibo',
            'editar',
            "Acuse actualizado - ID: {$acuse->id}, Local: {$_POST['local']}"
        );
        
        header("Location: index.php?mensaje=Acuse de recibo actualizado exitosamente");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Acuse de Recibo</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/signature_pad/1.5.3/signature-pad.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="row mb-3">
            <div class="col-md-6">
                <h2><i class="fas fa-edit"></i> Editar Acuse de Recibo</h2>
            </div>
            <div class="col-md-6 text-right">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="local">Local</label>
                                <input type="text" class="form-control" id="local" name="local" value="<?php echo htmlspecialchars($row['local']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="sector">Sector</label>
                                <input type="text" class="form-control" id="sector" name="sector" value="<?php echo htmlspecialchars($row['sector']); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="documento">Documento</label>
                        <input type="text" class="form-control" id="documento" name="documento" value="<?php echo htmlspecialchars($row['documento']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="foto">Foto</label>
                        <input type="file" class="form-control-file" id="foto" name="foto" accept="image/*">
                        <?php if($row['foto']): ?>
                            <img src="data:image/jpeg;base64,<?php echo $row['foto']; ?>" class="preview-image">
                        <?php endif; ?>
                        <img id="preview" class="preview-image" style="display: none;">
                    </div>

                    <div class="form-group">
                        <label for="jefe_encargado">Jefe/Encargado</label>
                        <input type="text" class="form-control" id="jefe_encargado" name="jefe_encargado" value="<?php echo htmlspecialchars($row['jefe_encargado']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="observaciones">Observaciones</label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="3"><?php echo htmlspecialchars($row['observaciones']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Firma Digital</label>
                        <div class="border rounded p-3">
                            <canvas id="signature-pad" width="400" height="200"></canvas>
                        </div>
                        <input type="hidden" name="firma" id="firma">
                        <button type="button" class="btn btn-secondary btn-sm" id="clear-signature">Limpiar Firma</button>
                        <?php if($row['firma_digital']): ?>
                            <div class="mt-2">
                                <img src="<?php echo $row['firma_digital']; ?>" alt="Firma actual" class="img-fluid" style="max-width: 400px;">
                            </div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/signature_pad/1.5.3/signature-pad.min.js"></script>
    <script>
        // Preview de imagen
        document.getElementById('foto').onchange = function(e) {
            let reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview').src = e.target.result;
                document.getElementById('preview').style.display = 'block';
            }
            reader.readAsDataURL(this.files[0]);
        };

        // Firma digital
        var canvas = document.getElementById('signature-pad');
        var signaturePad = new SignaturePad(canvas);

        document.getElementById('clear-signature').addEventListener('click', function() {
            signaturePad.clear();
        });

        document.querySelector('form').addEventListener('submit', function(e) {
            if (!signaturePad.isEmpty()) {
                document.getElementById('firma').value = signaturePad.toDataURL();
            }
        });
    </script>
</body>
</html>