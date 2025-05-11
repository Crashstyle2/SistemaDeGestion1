<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

include_once '../../config/database.php';
include_once '../../models/AcuseRecibo.php';

$database = new Database();
$db = $database->getConnection();
$acuse = new AcuseRecibo($db);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acuse de Recibo</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="row mb-3">
            <div class="col-md-6">
                <h2><i class="fas fa-file-alt"></i> Acuse de Recibo</h2>
            </div>
            <div class="col-md-6 text-right">
                <a href="../../dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
                <a href="crear.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nuevo Acuse
                </a>
            </div>
        </div>

        <?php if(isset($_GET['mensaje'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_GET['mensaje']; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Local</th>
                                <th>Sector</th>
                                <th>Documento</th>
                                <th>Jefe/Encargado</th>
                                <th>Técnico</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $acuse->leer();
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                                extract($row);
                                echo "<tr>";
                                echo "<td>" . date('d/m/Y H:i', strtotime($fecha_creacion)) . "</td>";
                                echo "<td>{$local}</td>";
                                echo "<td>{$sector}</td>";
                                echo "<td>{$documento}</td>";
                                echo "<td>{$jefe_encargado}</td>";
                                echo "<td>{$nombre_tecnico}</td>";
                                echo "<td class='text-center'>";
                                echo "<div class='btn-group btn-group-sm'>";
                                echo "<a href='ver.php?id={$id}' class='btn btn-info' title='Ver'><i class='fas fa-eye'></i></a>";
                                echo "<a href='generar_pdf.php?id={$id}' class='btn btn-secondary' title='PDF' target='_blank'><i class='fas fa-file-pdf'></i></a>";
                                if($_SESSION['user_rol'] === 'administrador') {
                                    echo "<a href='editar.php?id={$id}' class='btn btn-warning' title='Editar'><i class='fas fa-edit'></i></a>";
                                    echo "<button onclick='confirmarEliminar({$id})' class='btn btn-danger' title='Eliminar'><i class='fas fa-trash'></i></button>";
                                }
                                echo "</div>";
                                echo "</td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmarEliminar(id) {
            if(confirm('¿Está seguro de que desea eliminar este acuse?')) {
                window.location.href = 'eliminar.php?id=' + id;
            }
        }
    </script>
</body>
</html>