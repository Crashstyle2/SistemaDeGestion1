<?php
session_start();
include_once 'config/database.php';
include_once 'models/Usuario.php';

$database = new Database();
$db = $database->getConnection();

$message = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = new Usuario($db);
    $usuario->username = trim($_POST['username']);
    $usuario->password = trim($_POST['password']);
    
    if($usuario->validarCredenciales()) {
        $_SESSION['user_id'] = $usuario->id;
        $_SESSION['user_rol'] = $usuario->rol;
        $_SESSION['nombre'] = $usuario->nombre;
        $_SESSION['user_name'] = $usuario->username;

        // Agregar registro de actividad
        require_once 'config/ActivityLogger.php';
        ActivityLogger::logAccion(
            $usuario->id,
            'autenticacion',
            'login',
            'Inicio de sesión exitoso - Usuario: ' . $usuario->username
        );

        header("Location: dashboard.php");
        exit;
    } else {
        $message = "Usuario o contraseña incorrectos";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión Soporte Terreno</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background: linear-gradient(120deg, #2980b9, #8e44ad);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(10px);
        }
        .card-header {
            background: transparent;
            border-bottom: none;
            padding: 25px 0 0;
        }
        .card-header h3 {
            color: #2c3e50;
            font-weight: 600;
        }
        .form-control {
            border-radius: 25px;
            padding: 12px 20px;
            border: 1px solid #ddd;
            margin-bottom: 15px;
        }
        .form-control:focus {
            box-shadow: none;
            border-color: #8e44ad;
        }
        .btn-primary {
            border-radius: 25px;
            padding: 12px;
            background: linear-gradient(120deg, #2980b9, #8e44ad);
            border: none;
            font-weight: 500;
            letter-spacing: 1px;
            transition: transform 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .input-group-text {
            background: transparent;
            border: none;
            padding-right: 0;
        }
        .alert {
            border-radius: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <!-- Eliminar este h2 duplicado -->
            <div class="row justify-content-center">
                <div class="col-md-5">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="text-center mb-4">Sistema de Gestión Soporte Terreno</h3>
                        </div>
                        <div class="card-body px-5">
                            <?php if($message): ?>
                                <div class="alert alert-danger"><?php echo $message; ?></div>
                            <?php endif; ?>
                            
                            <form method="POST" action="">
                                <div class="form-group">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-user text-primary"></i>
                                            </span>
                                        </div>
                                        <input type="text" class="form-control" id="username" name="username" placeholder="Usuario" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-lock text-primary"></i>
                                            </span>
                                        </div>
                                        <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block mt-4">Ingresar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>