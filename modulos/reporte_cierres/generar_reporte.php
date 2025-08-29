<?php
session_start();
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_rol'], ['administrador', 'supervisor'])) {
    header("Location: ../../login.php");
    exit;
}

require_once '../../config/database.php';
require_once '../../models/ReporteCierres.php';
// Cambiar la línea del autoload por la inclusión directa de TCPDF
require_once '../../lib/tcpdf/tcpdf.php';

$database = new Database();
$db = $database->getConnection();
$reporte = new ReporteCierres($db);

$anio = $_GET['anio'] ?? date('Y');
$meses_seleccionados = isset($_GET['meses']) ? explode(',', $_GET['meses']) : [];

// Agregar definición de meses disponibles
$meses_disponibles = [
    1 => "ENERO", 2 => "FEBRERO", 3 => "MARZO", 4 => "ABRIL",
    5 => "MAYO", 6 => "JUNIO", 7 => "JULIO", 8 => "AGOSTO",
    9 => "SEPTIEMBRE", 10 => "OCTUBRE", 11 => "NOVIEMBRE", 12 => "DICIEMBRE"
];

// Crear nuevo documento PDF
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8');

// Configurar documento
$pdf->SetCreator('Sistema UPS');
$pdf->SetAuthor('Administrador');
$pdf->SetTitle('Reporte de Cierres - ' . $anio);

// Eliminar encabezado y pie de página predeterminados
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Configurar márgenes para optimizar espacio
$pdf->SetMargins(10, 10, 10);

// Configurar márgenes más pequeños
$pdf->SetMargins(5, 5, 5);

// Agregar página
$pdf->AddPage();

// Título con color
$pdf->SetFillColor(51, 122, 183);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Reporte de Cierres por Técnico - ' . $anio, 0, 1, 'C', true);
$pdf->Ln(2);

// Obtener datos y calcular estadísticas
$stmt = $reporte->obtenerReporteAnual($anio, $meses_seleccionados);

$total_cierres = 0;
$total_meses_con_datos = 0;
$mejor_tecnico = '';
$mejor_promedio = 0;

// Calcular estadísticas
if ($stmt) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $total_tecnico = 0;
        $meses_tecnico = 0;
        foreach ($meses_disponibles as $num => $mes) {
            $mes_lower = strtolower($mes);
            if (isset($row[$mes_lower]) && $row[$mes_lower] !== null) {
                $total_cierres += $row[$mes_lower];
                $total_meses_con_datos++;
                $total_tecnico += $row[$mes_lower];
                $meses_tecnico++;
            }
        }
        
        if ($meses_tecnico > 0) {
            $promedio_tecnico = $total_tecnico / $meses_tecnico;
            if ($promedio_tecnico > $mejor_promedio) {
                $mejor_promedio = $promedio_tecnico;
                $mejor_tecnico = $row['nombre_tecnico'];
            }
        }
    }
}

$promedio_general = $total_meses_con_datos > 0 ? round($total_cierres / $total_meses_con_datos, 2) : 0;

// Preparar encabezados de tabla
$header = array('Técnico', 'Usuario');
foreach ($meses_disponibles as $num => $mes) {
    if (empty($meses_seleccionados) || in_array($num, $meses_seleccionados)) {
        $header[] = $mes;
    }
}
$header[] = 'TOTAL';

// Definir anchos de columnas
$anchosColumnas = array(35, 25); // Anchos fijos para Técnico y Usuario
$anchoRestante = ($pdf->GetPageWidth() - array_sum($anchosColumnas) - 20);
$anchoMes = $anchoRestante / (count($header) - 2); // -2 por Técnico y Usuario

for ($i = 0; $i < (count($header) - 2); $i++) {
    $anchosColumnas[] = $anchoMes;
}

// Encabezados con color
$pdf->SetFillColor(51, 122, 183);
$pdf->SetTextColor(255, 255, 255);
foreach ($header as $i => $h) {
    $pdf->Cell($anchosColumnas[$i], 7, $h, 1, 0, 'C', true);
}
$pdf->Ln();

// Datos de la tabla con colores alternados
$pdf->SetTextColor(0, 0, 0);
$fill = false;
// Ajustar tamaños para la tabla
$pdf->SetFont('helvetica', 'B', 7); // Fuente más pequeña para encabezados

// Definir anchos de columnas más compactos
$anchosColumnas = array(30, 20); // Reducir anchos base
$anchoRestante = ($pdf->GetPageWidth() - array_sum($anchosColumnas) - 10);
$anchoMes = $anchoRestante / (count($header) - 2);

for ($i = 0; $i < (count($header) - 2); $i++) {
    $anchosColumnas[] = $anchoMes;
}

// Reducir altura de las celdas
foreach ($header as $i => $h) {
    $pdf->Cell($anchosColumnas[$i], 5, $h, 1, 0, 'C', true);
}
$pdf->Ln();

// Datos con tamaño reducido
$pdf->SetFont('helvetica', '', 7);
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $pdf->Cell($anchosColumnas[0], 4, $row['nombre_tecnico'], 1, 0, 'L', $fill);
    $pdf->Cell($anchosColumnas[1], 4, $row['usuario'], 1, 0, 'C', $fill);
    
    $col = 2;
    foreach ($meses_disponibles as $num => $mes) {
        if (empty($meses_seleccionados) || in_array($num, $meses_seleccionados)) {
            $valor = $row[strtolower($mes)] ?? '';
            $pdf->Cell($anchosColumnas[$col], 4, $valor, 1, 0, 'C', $fill);
            $col++;
        }
    }
    $pdf->Cell($anchosColumnas[$col], 4, $row['total_anual'], 1, 0, 'C', $fill);
    $pdf->Ln();
    $fill = !$fill;
}

// Generar PDF
$pdf->Output('Reporte_Cierres_' . $anio . '.pdf', 'D');