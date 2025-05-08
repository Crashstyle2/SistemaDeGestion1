<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

require_once '../../config/database.php';
require_once '../../models/InformeTecnico.php';
require_once '../../libraries/tcpdf/tcpdf.php';

if(!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();
$informe = new InformeTecnico($db);
$informeData = $informe->obtenerUno($_GET['id']);

if(!$informeData) {
    header("Location: index.php");
    exit;
}

// Crear nuevo PDF con tamaño personalizado
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, array(210, 297), true, 'UTF-8', false);

// Configurar márgenes (izquierda, arriba, derecha)
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(false, 0); // Deshabilitar salto de página automático

// Configurar PDF
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Sistema de Mantenimiento UPS');
$pdf->SetTitle('Informe Técnico');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();

// Contenido del PDF con estilo actualizado
$html = '
<div style="border: 2px solid #333; padding: 30px; margin: 20px 40px;">
    <table style="width: 100%; margin-bottom: 30px;">
        <tr>
            <td style="text-align: center;">
                <h1 style="color: #2c3e50; margin: 0; font-size: 22px;">Soporte Terreno - TIC Retail S.A.</h1>
                <div style="background: #2c3e50; height: 3px; width: 100%; margin: 10px 0;"></div>
                <h2 style="color: #34495e; margin: 10px 0; font-size: 18px;">Informe Técnico N° ' . sprintf("%06d", $informeData['id']) . '</h2>
            </td>
        </tr>
    </table>

    <table cellpadding="8" style="width: 100%; border-collapse: collapse; margin: 0 auto 20px auto; max-width: 95%;">
        <tr style="background-color: #f8f9fa;">
            <td width="30%" style="border: 1px solid #ddd;"><strong>Local:</strong></td>
            <td style="border: 1px solid #ddd;">' . $informeData['local'] . '</td>
        </tr>
        <tr>
            <td style="border: 1px solid #ddd;"><strong>Sector:</strong></td>
            <td style="border: 1px solid #ddd;">' . $informeData['sector'] . '</td>
        </tr>
        <tr style="background-color: #f8f9fa;">
            <td style="border: 1px solid #ddd;"><strong>Equipo con Problema:</strong></td>
            <td style="border: 1px solid #ddd;">' . $informeData['equipo_asistido'] . '</td>
        </tr>
        <tr>
            <td style="border: 1px solid #ddd;"><strong>Orden de Trabajo:</strong></td>
            <td style="border: 1px solid #ddd;">' . $informeData['orden_trabajo'] . '</td>
        </tr>
        <tr style="background-color: #f8f9fa;">
            <td style="border: 1px solid #ddd;"><strong>Patrimonio:</strong></td>
            <td style="border: 1px solid #ddd;">' . $informeData['patrimonio'] . '</td>
        </tr>
        <tr>
            <td style="border: 1px solid #ddd;"><strong>Jefe de Turno:</strong></td>
            <td style="border: 1px solid #ddd;">' . $informeData['jefe_turno'] . '</td>
        </tr>
        <tr style="background-color: #f8f9fa;">
            <td style="border: 1px solid #ddd;"><strong>Técnico:</strong></td>
            <td style="border: 1px solid #ddd;">' . $informeData['nombre_tecnico'] . '</td>
        </tr>
        <tr>
            <td style="border: 1px solid #ddd;"><strong>Fecha:</strong></td>
            <td style="border: 1px solid #ddd;">' . $informeData['fecha_creacion'] . '</td>
        </tr>
    </table>

    <div style="margin: 30px 60px;">
        <div style="background-color: #2c3e50; color: white; padding: 8px; margin-bottom: 15px; width: 85%; margin-left: auto; margin-right: auto;">
            <strong>Observaciones del Trabajo Realizado</strong>
        </div>
        <div style="border: 1px solid #ddd; padding: 20px; min-height: 120px; background-color: #fff; width: 85%; margin-left: auto; margin-right: auto;">
            <p style="margin: 0; line-height: 1.6;">' . nl2br($informeData['observaciones']) . '</p>
        </div>
    </div>
';

if(!empty($informeData['firma_digital'])) {
    $html .= '
    <div style="margin: 20px 60px 20px 60px; text-align: center;">
        <div style="display: inline-block; border: 1px solid #ddd; padding: 10px; background-color: #fff; width: 60%; margin: 0 auto;">
            <img src="' . $informeData['firma_digital'] . '" style="max-width: 150px; max-height: 60px;">
            <div style="margin-top: 5px; font-size: 11px; font-weight: bold;">Firma Digital del Jefe de Turno</div>
            <div style="margin-top: 2px; font-size: 9px; color: #666;">Firma de conformidad del trabajo realizado</div>
        </div>
    </div>';
}

$html .= '</div>';

$pdf->writeHTML($html, true, false, true, false, '');

// Generar PDF para descarga directa
$pdf->Output('informe_tecnico.pdf', 'D');