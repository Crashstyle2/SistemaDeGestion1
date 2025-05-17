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

// Configurar márgenes más pequeños para aprovechar el espacio
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(false, 0);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Sistema de Mantenimiento UPS');
$pdf->SetTitle('Informe Técnico');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();

// Contenido del PDF con espaciado optimizado
$html = '
<div style="border: 2px solid #333; padding: 15px; margin: 5px;">
    <table style="width: 100%; margin-bottom: 15px;">
        <tr>
            <td style="text-align: center;">
                <h1 style="color: #2c3e50; margin: 0; font-size: 20px;">Soporte Terreno - TIC Retail S.A.</h1>
                <div style="background: #2c3e50; height: 2px; width: 100%; margin: 5px 0;"></div>
                <h2 style="color: #34495e; margin: 5px 0; font-size: 16px;">Informe Técnico N° ' . sprintf("%06d", $informeData['id']) . '</h2>
            </td>
        </tr>
    </table>

    <table cellpadding="4" style="width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 11px;">
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

    <div style="margin: 15px 0;">
        <div style="background-color: #2c3e50; color: white; padding: 5px; margin-bottom: 5px; font-size: 12px;">
            <strong>Observaciones del Trabajo Realizado</strong>
        </div>
        <div style="border: 1px solid #ddd; padding: 10px; background-color: #fff; font-size: 11px;">
            <p style="margin: 0; line-height: 1.4;">' . nl2br($informeData['observaciones']) . '</p>
        </div>
    </div>
';

// Sección de firma y foto optimizada
if(!empty($informeData['firma_digital']) || !empty($informeData['foto_trabajo'])) {
    $html .= '<table style="width: 100%; margin-top: 15px;"><tr>';
    
    if(!empty($informeData['firma_digital'])) {
        $html .= '<td style="width: 50%; padding: 5px; text-align: center;">
            <div style="border: 1px solid #ddd; padding: 5px; background-color: #fff;">
                <img src="' . $informeData['firma_digital'] . '" style="max-width: 180px; max-height: 60px;">
                <div style="margin-top: 3px; font-size: 9px; font-weight: bold;">Firma Digital del Jefe de Turno</div>
                <div style="font-size: 8px; color: #666;">Firma de conformidad del trabajo realizado</div>
            </div>
        </td>';
    }
    
    if(!empty($informeData['foto_trabajo'])) {
        $html .= '<td style="width: 50%; padding: 5px; text-align: center;">
            <div style="border: 1px solid #ddd; padding: 5px; background-color: #fff;">
                <img src="data:image/jpeg;base64,' . $informeData['foto_trabajo'] . '" style="max-width: 200px; max-height: 150px;">
                <div style="margin-top: 3px; font-size: 9px; font-weight: bold;">Foto del Trabajo Realizado</div>
            </div>
        </td>';
    }
    
    $html .= '</tr></table>';
}

$html .= '</div>';

// Optimizar escala de imágenes
$pdf->setImageScale(1.2);
$pdf->writeHTML($html, true, false, true, false, '');

// Generar PDF
$pdf->Output('Informe_Tecnico_' . $informeData['id'] . '.pdf', 'D');