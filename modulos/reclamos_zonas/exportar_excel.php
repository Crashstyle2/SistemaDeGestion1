<?php
require_once __DIR__ . '/../../config/conexion.php';
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Crear nuevo documento de Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Establecer títulos de columnas
$sheet->setCellValue('A1', 'Zona');
$sheet->setCellValue('B1', 'Mes');
$sheet->setCellValue('C1', 'Año');
$sheet->setCellValue('D1', 'Cantidad de Reclamos');

// Obtener los últimos 3 meses
$fecha = new DateTime();
$meses = [];
for ($i = 2; $i >= 0; $i--) {
    $fecha->modify('-' . $i . ' month');
    $meses[] = [
        'mes' => $fecha->format('n'),
        'anio' => $fecha->format('Y')
    ];
    $fecha->modify('+' . $i . ' month');
}

// Construir la consulta SQL
$mesesStr = implode(',', array_map(function($m) { return $m['mes']; }, $meses));
$aniosStr = implode(',', array_map(function($m) { return $m['anio']; }, $meses));

$sql = "SELECT zona, mes, anio, cantidad_reclamos 
        FROM reclamos_zonas 
        WHERE (mes IN ($mesesStr) AND anio IN ($aniosStr))
        ORDER BY anio, mes, zona";

$resultado = $conn->query($sql);

// Llenar datos
$fila = 2;
while ($row = $resultado->fetch_assoc()) {
    $sheet->setCellValue('A' . $fila, $row['zona']);
    $sheet->setCellValue('B' . $fila, $row['mes']);
    $sheet->setCellValue('C' . $fila, $row['anio']);
    $sheet->setCellValue('D' . $fila, $row['cantidad_reclamos']);
    $fila++;
}

// Auto-ajustar columnas
foreach(range('A','D') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Configurar encabezados para descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Reclamos_por_zona.xlsx"');
header('Cache-Control: max-age=0');

// Crear archivo Excel y enviarlo al navegador
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;