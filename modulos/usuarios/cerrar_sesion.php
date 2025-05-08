<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'administrador') {
    echo json_encode(['success' => false]);
    exit;
}

require_once '../../config/database.php';
require_once '../../models/Usuario.php';

$database = new Database();
$db = $database->getConnection();
$usuario = new Usuario($db);

if(isset($_POST['user_id'])) {
    $result = $usuario->cerrarSesion($_POST['user_id']);
    echo json_encode(['success' => $result]);
} else {
    echo json_encode(['success' => false]);
}