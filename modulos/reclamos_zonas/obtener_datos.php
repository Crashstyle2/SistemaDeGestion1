<?php
require_once '../../config/Database.php';

$database = new Database();
$conn = $database->getConnection();

header('Content-Type: application/json; charset=utf-8');

try {
    // Obtener parámetros
    $meses = isset($_GET['meses']) ? explode(',', $_GET['meses']) : [];
    $anios = isset($_GET['anios']) ? explode(',', $_GET['anios']) : [];
    
    // Construir consulta base
    $sql = "SELECT zona, mes, anio, cantidad_reclamos 
            FROM reclamos_zonas";
    
    // Agregar filtros si hay parámetros
    $where = [];
    $params = [];
    
    if (!empty($meses)) {
        $where[] = "mes IN (" . implode(',', array_fill(0, count($meses), '?')) . ")";
        $params = array_merge($params, $meses);
    }
    
    if (!empty($anios)) {
        $where[] = "anio IN (" . implode(',', array_fill(0, count($anios), '?')) . ")";
        $params = array_merge($params, $anios);
    }
    
    if (!empty($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    
    $sql .= " ORDER BY anio DESC, mes DESC, zona ASC";
    
    // Preparar y ejecutar consulta
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $resultados,
        'debug' => [
            'sql' => $sql,
            'params' => $params,
            'meses' => $meses,
            'anios' => $anios
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'meses' => $meses ?? [],
            'anios' => $anios ?? []
        ]
    ]);
}