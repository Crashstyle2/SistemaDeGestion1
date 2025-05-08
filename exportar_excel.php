<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'config/database.php';
require 'models/MantenimientoUPS.php';

$database = new Database();
$db = $database->getConnection();
$mantenimiento = new MantenimientoUPS($db);
$stmt = $mantenimiento->leerTodos();

// Configurar headers para descarga de Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="mantenimiento_ups.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Crear el contenido del archivo Excel
echo "Patrimonio\tCadena\tSucursal\tMarca\tTipo Batería\tCantidad\tPotencia\tÚltimo Mant.\tPróximo Mant.\tObservaciones\tEstado\n";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['patrimonio'] . "\t";
    echo $row['cadena'] . "\t";
    echo $row['sucursal'] . "\t";
    echo $row['marca'] . "\t";
    echo $row['tipo_bateria'] . "\t";
    echo $row['cantidad'] . "\t";
    echo $row['potencia_ups'] . "\t";
    echo $row['fecha_ultimo_mantenimiento'] . "\t";
    echo $row['fecha_proximo_mantenimiento'] . "\t";
    echo $row['observaciones'] . "\t";
    echo $row['estado_mantenimiento'] . "\n";
}
exit;