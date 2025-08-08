<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include_once 'config/database.php';
include_once 'models/MantenimientoUPS.php';

$database = new Database();
$db = $database->getConnection();
$mantenimiento = new MantenimientoUPS($db);
$stmt = $mantenimiento->leerTodos();

// Configurar headers para descarga de Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="Mantenimiento_UPS_'.date('Y-m-d').'.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Crear el contenido del archivo Excel
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet">';
echo '<Worksheet ss:Name="Mantenimiento UPS">';
echo '<Table>';

// Encabezados
echo '<Row>';
echo '<Cell><Data ss:Type="String">Patrimonio</Data></Cell>';
echo '<Cell><Data ss:Type="String">Cadena</Data></Cell>';
echo '<Cell><Data ss:Type="String">Sucursal</Data></Cell>';
echo '<Cell><Data ss:Type="String">Marca</Data></Cell>';
echo '<Cell><Data ss:Type="String">Tipo Batería</Data></Cell>';
echo '<Cell><Data ss:Type="String">Cantidad</Data></Cell>';
echo '<Cell><Data ss:Type="String">Potencia</Data></Cell>';
echo '<Cell><Data ss:Type="String">Último Mantenimiento</Data></Cell>';
echo '<Cell><Data ss:Type="String">Próximo Mantenimiento</Data></Cell>';
echo '<Cell><Data ss:Type="String">Observaciones</Data></Cell>';
echo '<Cell><Data ss:Type="String">Estado</Data></Cell>';
echo '</Row>';

// Datos
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo '<Row>';
    echo '<Cell><Data ss:Type="String">'.htmlspecialchars($row['patrimonio']).'</Data></Cell>';
    echo '<Cell><Data ss:Type="String">'.htmlspecialchars($row['cadena']).'</Data></Cell>';
    echo '<Cell><Data ss:Type="String">'.htmlspecialchars($row['sucursal']).'</Data></Cell>';
    echo '<Cell><Data ss:Type="String">'.htmlspecialchars($row['marca']).'</Data></Cell>';
    echo '<Cell><Data ss:Type="String">'.htmlspecialchars($row['tipo_bateria']).'</Data></Cell>';
    echo '<Cell><Data ss:Type="Number">'.htmlspecialchars($row['cantidad']).'</Data></Cell>';
    echo '<Cell><Data ss:Type="String">'.htmlspecialchars($row['potencia_ups']).'</Data></Cell>';
    echo '<Cell><Data ss:Type="String">'.htmlspecialchars($row['fecha_ultimo_mantenimiento']).'</Data></Cell>';
    echo '<Cell><Data ss:Type="String">'.htmlspecialchars($row['fecha_proximo_mantenimiento']).'</Data></Cell>';
    echo '<Cell><Data ss:Type="String">'.htmlspecialchars($row['observaciones'] ?? '').'</Data></Cell>';
    echo '<Cell><Data ss:Type="String">'.htmlspecialchars($row['estado_mantenimiento'] ?? 'Pendiente').'</Data></Cell>';
    echo '</Row>';
}

echo '</Table>';
echo '</Worksheet>';
echo '</Workbook>';
exit;
?>