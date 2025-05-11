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
        $this->SetFont('helvetica', 'B', 15);
        $this->Cell(0, 15, 'Acuse de Recibo', 0, false, 'C', 0, '', 0, false, 'M', 'M');
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Página '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
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

$pdf->SetFont('helvetica', '', 12);

// Información General
$html = '<h3>Información General</h3>
<table cellspacing="0" cellpadding="5" border="1">
    <tr>
        <td width="30%"><strong>Local:</strong></td>
        <td width="70%">'.htmlspecialchars($row['local']).'</td>
    </tr>
    <tr>
        <td><strong>Sector:</strong></td>
        <td>'.htmlspecialchars($row['sector']).'</td>
    </tr>
    <tr>
        <td><strong>Documento:</strong></td>
        <td>'.htmlspecialchars($row['documento']).'</td>
    </tr>
    <tr>
        <td><strong>Jefe/Encargado:</strong></td>
        <td>'.htmlspecialchars($row['jefe_encargado']).'</td>
    </tr>
    <tr>
        <td><strong>Fecha:</strong></td>
        <td>'.date('d/m/Y H:i', strtotime($row['fecha_creacion'])).'</td>
    </tr>
    <tr>
        <td><strong>Técnico:</strong></td>
        <td>'.htmlspecialchars($row['nombre_tecnico']).'</td>
    </tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');

// Observaciones
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Observaciones:', 0, 1);
$pdf->SetFont('helvetica', '', 12);
$pdf->writeHTML(nl2br(htmlspecialchars($row['observaciones'])), true, false, true, false, '');

// Foto
if($row['foto']) {
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Foto del Documento:', 0, 1);
    $pdf->Image('@'.base64_decode($row['foto']), '', '', 150, 150, '', '', 'T', false, 300, '', false, false, 1, false, false, false);
}

// Firma
if($row['firma_digital']) {
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Firma Digital:', 0, 1);
    $pdf->Image($row['firma_digital'], '', '', 150, 50, '', '', 'T', false, 300, '', false, false, 1, false, false, false);
}

$pdf->Output('acuse_recibo_'.$row['id'].'.pdf', 'I');