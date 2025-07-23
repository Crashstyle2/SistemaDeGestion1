<?php
session_start();
require_once '../../config/Database.php';

// Verificar que sea administrador
if(!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'administrador') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$action = $_REQUEST['action'] ?? '';

try {
    switch($action) {
        case 'listar':
            $stmt = $db->prepare("SELECT * FROM sucursales ORDER BY segmento, local");
            $stmt->execute();
            $sucursales = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $sucursales]);
            break;
            
        case 'crear':
            $stmt = $db->prepare("INSERT INTO sucursales (segmento, cebe, local, m2_neto, localidad) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['segmento'],
                $_POST['cebe'],
                $_POST['local'],
                $_POST['m2_neto'],
                $_POST['localidad']
            ]);
            echo json_encode(['success' => true, 'message' => 'Sucursal creada exitosamente']);
            break;
            
        case 'editar':
            $stmt = $db->prepare("UPDATE sucursales SET segmento=?, cebe=?, local=?, m2_neto=?, localidad=? WHERE id=?");
            $stmt->execute([
                $_POST['segmento'],
                $_POST['cebe'],
                $_POST['local'],
                $_POST['m2_neto'],
                $_POST['localidad'],
                $_POST['sucursal_id']
            ]);
            echo json_encode(['success' => true, 'message' => 'Sucursal actualizada exitosamente']);
            break;
            
        case 'obtener':
            $stmt = $db->prepare("SELECT * FROM sucursales WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $sucursal = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $sucursal]);
            break;
            
        case 'eliminar':
            $stmt = $db->prepare("DELETE FROM sucursales WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            echo json_encode(['success' => true, 'message' => 'Sucursal eliminada exitosamente']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>