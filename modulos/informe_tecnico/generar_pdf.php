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

// CORREGIR: Usar leerUno() en lugar de obtenerUno()
$informe->id = $_GET['id'];
$stmt = $informe->leerUno();
$informeData = $stmt->fetch(PDO::FETCH_ASSOC);

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

// Después de obtener $informeData, agregar:
// CORREGIR: Obtener el array de fotos desde el PDOStatement
$stmt_fotos = $informe->obtenerFotos($_GET['id']);
$fotos = $stmt_fotos->fetchAll(PDO::FETCH_ASSOC);

// ÚNICA sección de firma y fotos
if(!empty($informeData['firma_digital']) || !empty($fotos)) {
    $html .= '<div style="margin-top: 5px;">';
    
    if(!empty($informeData['firma_digital'])) {
        $html .= '
        <div style="text-align: center; margin-bottom: 5px;">
            <img src="' . $informeData['firma_digital'] . '" style="width: 200px;">
            <div style="margin-top: 2px; font-size: 9px;">Firma Digital del Jefe de Turno</div>
        </div>';
    }
    
    if(!empty($fotos)) {
        $html .= '
        <div style="background-color: #2c3e50; color: white; padding: 3px; margin: 5px 0; font-size: 11px;">
            <strong>Registro Fotográfico</strong>
        </div>
        <table style="width: 100%; border-collapse: separate; border-spacing: 2px;">
            <tr>';
        
        $fotoAntes = array_filter($fotos, function($foto) { return $foto['tipo'] === 'antes'; });
        $fotoDespues = array_filter($fotos, function($foto) { return $foto['tipo'] === 'despues'; });
        
        $html .= '<td style="width: 48%; border: 1px solid #ddd; padding: 2px;">
            <div style="text-align: center; font-weight: bold; background-color: #f8f9fa; padding: 2px;">ANTES</div>';
        if(!empty($fotoAntes)) {
            $fotoAntes = array_values($fotoAntes)[0];
            // CORREGIR: Usar foto_ruta en lugar de foto (Base64)
            if(!empty($fotoAntes['foto_ruta'])) {
                // Convertir la imagen a Base64 para el PDF
                $imagen_path = '../../img/informe_tecnicos/fotos/' . $fotoAntes['foto_ruta'];
                if(file_exists($imagen_path)) {
                    $imagen_data = base64_encode(file_get_contents($imagen_path));
                    $html .= '<img src="data:image/jpeg;base64,' . $imagen_data . '" style="width: 320px; height: 240px;">';
                }
            } elseif(!empty($fotoAntes['foto'])) {
                // Fallback a Base64 si existe
                $html .= '<img src="data:image/jpeg;base64,' . $fotoAntes['foto'] . '" style="width: 320px; height: 240px;">';
            }
        }
        $html .= '</td>';
        
        $html .= '<td style="width: 48%; border: 1px solid #ddd; padding: 2px;">
            <div style="text-align: center; font-weight: bold; background-color: #f8f9fa; padding: 2px;">DESPUÉS</div>';
        if(!empty($fotoDespues)) {
            $fotoDespues = array_values($fotoDespues)[0];
            // CORREGIR: Usar foto_ruta en lugar de foto (Base64)
            if(!empty($fotoDespues['foto_ruta'])) {
                // Convertir la imagen a Base64 para el PDF
                $imagen_path = '../../img/informe_tecnicos/fotos/' . $fotoDespues['foto_ruta'];
                if(file_exists($imagen_path)) {
                    $imagen_data = base64_encode(file_get_contents($imagen_path));
                    $html .= '<img src="data:image/jpeg;base64,' . $imagen_data . '" style="width: 320px; height: 240px;">';
                }
            } elseif(!empty($fotoDespues['foto'])) {
                // Fallback a Base64 si existe
                $html .= '<img src="data:image/jpeg;base64,' . $fotoDespues['foto'] . '" style="width: 320px; height: 240px;">';
            }
        }
        $html .= '</td></tr></table>';
    }
    
    $html .= '</div>';
}

$html .= '</div>';

// Generar PDF
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('Informe_Tecnico_' . $informeData['id'] . '.pdf', 'D');