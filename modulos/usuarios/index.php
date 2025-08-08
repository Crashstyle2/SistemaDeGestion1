<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'administrador') {
    header("Location: ../../dashboard.php");
    exit;
}

include_once '../../config/database.php';
include_once '../../models/Usuario.php';

$database = new Database();
$db = $database->getConnection();
$usuario = new Usuario($db);

try {
    $usuarios = $usuario->listarUsuarios();
} catch (Exception $e) {
    $_SESSION['error'] = "Error al cargar la lista de usuarios: " . $e->getMessage();
    $usuarios = null;
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Sistema UPS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .btn-group-sm > .btn, .btn-sm {
            padding: .25rem .5rem;
            font-size: .875rem;
            line-height: 1.5;
            border-radius: .2rem;
        }
        .badge {
            padding: .5em .8em;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-users mr-2"></i>Gestión de Usuarios</h2>
            <div>
                <a href="/MantenimientodeUPS/dashboard.php" class="btn btn-secondary mr-2">
                    <i class="fas fa-arrow-left mr-2"></i>Volver al Inicio
                </a>
                <a href="crear_usuario.php" class="btn btn-primary">
                    <i class="fas fa-plus mr-2"></i>Nuevo Usuario
                </a>
            </div>
        </div>

        <!-- Tabla -->
        <div class="card">
            <div class="card-body p-0">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger m-3">
                        <?php 
                            echo htmlspecialchars($_SESSION['error']); 
                            unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($usuarios): ?>
                <div class="table-responsive">
                    <div class="mb-3">
                        <input type="text" id="searchInput" class="form-control" placeholder="Buscar en cualquier campo...">
                    </div>
                    <table class="table table-hover" id="dataTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Nombre</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $usuarios->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr class="<?php echo $row['estado'] === 'inactivo' ? 'table-secondary' : ''; ?>">
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $row['rol'] === 'administrador' ? 'badge-primary' : 'badge-info'; ?>">
                                            <?php echo htmlspecialchars($row['rol']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $row['estado'] === 'activo' ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo htmlspecialchars($row['estado']); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="editar_usuario.php?id=<?php echo $row['id']; ?>" class="btn btn-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="cambiar_password.php?id=<?php echo $row['id']; ?>" class="btn btn-info" title="Cambiar Contraseña">
                                                <i class="fas fa-key"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="alert alert-info m-3">No hay usuarios registrados.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación -->
    <div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Eliminación</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    ¿Está seguro de que desea eliminar este usuario?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <a href="#" id="btnConfirmarEliminar" class="btn btn-danger">Eliminar</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
    function confirmarEliminacion(id) {
        $('#modalEliminar').modal('show');
        $('#btnConfirmarEliminar').attr('href', 'eliminar_usuario.php?id=' + id);
    }
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