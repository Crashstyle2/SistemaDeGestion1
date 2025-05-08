<?php
require_once 'config/init.php';
require_once 'models/ReporteCierres.php';

$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');
$meses = isset($_GET['meses']) ? explode(',', $_GET['meses']) : range(1, 12);

$reporte = new ReporteCierres($db);
$resultado = $reporte->obtenerReporteAnual($anio, $meses);

// ... código para generar el reporte ...