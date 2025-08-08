<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

require_once '../../config/database.php';
require_once '../../models/AcuseRecibo.php';
require_once '../../libraries/tcpdf/tcpdf.php';

$database = new Database();
$db = $database->getConnection();
$acuse = new AcuseRecibo($db);

if(!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$acuse->id = $_GET['id'];
$stmt = $acuse->leerUno();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$row) {
    header("Location: index.php");
    exit;
}

class MYPDF extends TCPDF {
    public function Header() {
        // Título con mejor diseño
        $this->SetFont('helvetica', 'B', 24);
        $this->SetTextColor(41, 128, 185);
        $this->Cell(0, 20, 'Acuse de Recibo', 0, false, 'C');
        $this->Ln(25);
    }
}

$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Sistema de Mantenimiento');
$pdf->SetTitle('Acuse de Recibo');

$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

$pdf->AddPage();

// Estilo para la tabla de información
$html = '
<style>
    table {
        border-collapse: collapse;
        margin-top: 20px;
        width: 100%;
    }
    th, td {
        border: 1px solid #BDC3C7;
        padding: 12px;
    }
    th {
        background-color: #3498DB;
        color: white;
        font-weight: bold;
    }
    td {
        background-color: #FFFFFF;
    }
    h3 {
        color: #2980B9;
        border-bottom: 2px solid #3498DB;
        padding-bottom: 5px;
        margin-bottom: 20px;
    }
</style>
<h3>Información General</h3>
<table>
    <tr>
        <th width="30%" style="background-color: #3498DB; color: white;">Local</th>
        <td width="70%">'.htmlspecialchars($row['local']).'</td>
    </tr>
    <tr>
        <th style="background-color: #3498DB; color: white;">Sector</th>
        <td>'.htmlspecialchars($row['sector']).'</td>
    </tr>
    <tr>
        <th style="background-color: #3498DB; color: white;">Documento</th>
        <td>'.htmlspecialchars($row['documento']).'</td>
    </tr>
    <tr>
        <th style="background-color: #3498DB; color: white;">Jefe/Encargado</th>
        <td>'.htmlspecialchars($row['jefe_encargado']).'</td>
    </tr>
    <tr>
        <th style="background-color: #3498DB; color: white;">Fecha</th>
        <td>'.date('d/m/Y H:i', strtotime($row['fecha_creacion'])).'</td>
    </tr>
    <tr>
        <th style="background-color: #3498DB; color: white;">Técnico</th>
        <td>'.htmlspecialchars($row['nombre_tecnico']).'</td>
    </tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');

// Observaciones con mejor formato
$pdf->AddPage();
$html = '
<h3 style="color: #2980B9; border-bottom: 2px solid #3498DB; padding-bottom: 5px;">Observaciones</h3>
<div style="background-color: #F8F9F9; border: 1px solid #BDC3C7; border-radius: 5px; padding: 15px; margin-top: 10px;">
    '.nl2br(htmlspecialchars($row['observaciones'])).'
</div>';
$pdf->writeHTML($html, true, false, true, false, '');

// Foto con marco y título
if($row['foto']) {
    $pdf->AddPage();
    $html = '<h3 style="color: #2980B9; border-bottom: 2px solid #3498DB; padding-bottom: 5px;">Foto del Documento</h3>';
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Image('@'.base64_decode($row['foto']), 30, '', 150, 150, '', '', 'T', false, 300, '', false, false, 1, true, false, false);
}

// Firma con diseño mejorado
if($row['firma_digital']) {
    $pdf->AddPage();
    $html = '
    <h3 style="color: #2980B9; border-bottom: 2px solid #3498DB; padding-bottom: 5px;">Firma Digital</h3>
    <div style="text-align: center; margin-top: 20px;">';
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Image($row['firma_digital'], 30, null, 150, 50, '', '', 'T', false, 300, '', false, false, 1, false, false, false);
    $pdf->writeHTML('<div style="border-top: 1px solid #BDC3C7; margin-top: 60px; padding-top: 5px; text-align: center; color: #7F8C8D;">Firma del Encargado</div>', true, false, true, false, '');
}

$pdf->Output('acuse_recibo_'.$row['id'].'.pdf', 'I');