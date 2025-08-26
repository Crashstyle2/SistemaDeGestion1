<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

include_once '../../config/database.php';

require_once '../../config/ActivityLogger.php';

require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Verificar el rol del usuario
$rol = $_SESSION['user_rol'];

// Permitir acceso a todos los roles válidos
if (!in_array($rol, ['tecnico', 'supervisor', 'administrativo', 'administrador'])) {
    header("Location: ../../dashboard.php");
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Obtener parámetros de búsqueda - actualizar para manejar POST y GET
$search = '';
$fecha_inicio = '';
$fecha_fin = '';
$export = '';
$selected_ids = [];

// Verificar si es una exportación (POST) o una búsqueda normal (GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export'])) {
    // Parámetros de exportación vienen por POST
    $export = $_POST['export'];
    $search = isset($_POST['search']) ? trim($_POST['search']) : '';
    $fecha_inicio = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : '';
    $fecha_fin = isset($_POST['fecha_fin']) ? $_POST['fecha_fin'] : '';
    $selected_ids = isset($_POST['selected_ids']) ? $_POST['selected_ids'] : [];
} else {
    // Parámetros de búsqueda vienen por GET
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
    $fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';
    $export = isset($_GET['export']) ? $_GET['export'] : '';
}

// Construir la consulta SQL base - VERIFICAR que incluya fecha_registro
$sql = "SELECT uc.*, u.nombre as nombre_usuario, 
       ucr.origen, ucr.destino, ucr.km_sucursales, ucr.comentarios_sector,

       uc.fecha_registro, ucr.id as recorrido_id, uc.estado_recorrido,
       uc.fecha_cierre, uc.cerrado_por, uc.reabierto_por, uc.fecha_reapertura,
       cerrador.nombre as nombre_cerrador, reabridor.nombre as nombre_reabridor,
       uc.fecha_registro, ucr.id as recorrido_id,

       s_origen.segmento as origen_segmento, s_origen.cebe as origen_cebe, 
       s_origen.local as origen_local, s_origen.m2_neto as origen_m2_neto, 
       s_origen.localidad as origen_localidad,
       s_destino.segmento as destino_segmento, s_destino.cebe as destino_cebe,
       s_destino.local as destino_local, s_destino.m2_neto as destino_m2_neto,
       s_destino.localidad as destino_localidad
       FROM uso_combustible uc 
       LEFT JOIN usuarios u ON uc.user_id = u.id 

       LEFT JOIN usuarios cerrador ON uc.cerrado_por = cerrador.id
       LEFT JOIN usuarios reabridor ON uc.reabierto_por = reabridor.id


       LEFT JOIN uso_combustible_recorridos ucr ON uc.id = ucr.uso_combustible_id 
       LEFT JOIN sucursales s_origen ON ucr.origen = s_origen.local
       LEFT JOIN sucursales s_destino ON ucr.destino = s_destino.local
       WHERE 1=1";
$params = array();

// Verificar el tiempo límite de modificación para técnicos (3 horas)
$puedeModificar = false;
if ($rol === 'tecnico') {
    // MODIFICAR: Los técnicos solo pueden ver sus propios registros
    $sql .= " AND uc.user_id = ?";
    $params[] = $_SESSION['user_id'];
    
    // Verificar si pueden modificar (dentro de 3 horas)
    $horasLimite = 3;
    $fechaActual = new DateTime();
    $fechaLimite = $fechaActual->modify("-{$horasLimite} hours");
    $fechaLimiteStr = $fechaLimite->format('Y-m-d H:i:s');
    
    // Solo pueden modificar registros recientes
    if (isset($registros)) {
        foreach ($registros as &$registro) {
            $fechaRegistro = new DateTime($registro['fecha_registro']);
            $registro['puede_modificar'] = $fechaRegistro >= $fechaLimite;
        }
    }
    
    $puedeModificar = true;
} elseif ($rol === 'supervisor' || $rol === 'administrador' || $rol === 'administrativo') {
    // Supervisores, administradores y administrativos pueden ver todos los registros
    $puedeModificar = true;
}

// Agregar filtros si existen - Búsqueda simplificada por palabras clave
if (!empty($search)) {
    $searchWords = explode(' ', $search);
    $searchConditions = [];
    
    foreach ($searchWords as $word) {
        if (!empty(trim($word))) {
            $searchConditions[] = "(u.nombre LIKE ? OR uc.nombre_conductor LIKE ? OR uc.chapa LIKE ? OR ucr.origen LIKE ? OR ucr.destino LIKE ? OR uc.numero_baucher LIKE ? OR uc.documento LIKE ? OR uc.tarjeta LIKE ?)";
            $searchParam = "%" . trim($word) . "%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
        }
    }
    
    if (!empty($searchConditions)) {
        $sql .= " AND (" . implode(' OR ', $searchConditions) . ")";
    }
}

// Corregir filtros de fecha - usar DATE() para comparar solo la fecha
if (!empty($fecha_inicio)) {
    $sql .= " AND DATE(uc.fecha_carga) >= ?";
    $params[] = $fecha_inicio;
}

if (!empty($fecha_fin)) {
    $sql .= " AND DATE(uc.fecha_carga) <= ?";
    $params[] = $fecha_fin;
}

$sql .= " ORDER BY uc.fecha_registro DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Verificar recorridos abiertos antes de exportar
if ($export === 'excel') {
    include_once '../../models/UsoCombustible.php';
    $usoCombustible = new UsoCombustible($conn);
    
    $permitir_exportacion = false;
    
    // Si hay IDs seleccionados, verificar solo esos recorridos
        if (!empty($selected_ids) && is_array($selected_ids)) {
            // Verificar el estado de los recorridos seleccionados
            $recorridosAbiertosSeleccionados = $usoCombustible->verificarRecorridosAbiertosPorIds($selected_ids);
            
            if ($recorridosAbiertosSeleccionados['total_abiertos'] > 0) {
                // Mostrar mensaje específico para recorridos seleccionados
                $usuarios_responsables = $recorridosAbiertosSeleccionados['recorridos_abiertos'];
                $error_message = "El recorrido se encuentra abierto, no podrás descargar el Excel. Favor solicitar a " . $usuarios_responsables . " proceder con el cierre.";
                $mostrar_error_seleccionados = true;
                $permitir_exportacion = false;
            } else {
                // Todos los recorridos seleccionados están cerrados
                $permitir_exportacion = true;
            }
        } else {
            // Si no hay selección específica, verificar todos los recorridos en el rango
            $filtros = [
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin
            ];
            
            $recorridosAbiertos = $usoCombustible->verificarRecorridosAbiertos($filtros);
            
            if ($recorridosAbiertos['total_abiertos'] > 0) {
                $error_message = "Hay recorridos abiertos en el rango de fechas que deben cerrarse antes de poder descargar el Excel completo. Solicite cerrar los recorridos abiertos o seleccione únicamente los recorridos cerrados.";
                $mostrar_error_rango = true;
                $permitir_exportacion = false;
            } else {
                // Todos los recorridos en el rango están cerrados
                $permitir_exportacion = true;
            }
        }
    
    // Solo proceder con la exportación si está permitida
    if ($permitir_exportacion) {
        // APLICAR LA MISMA LÓGICA DE AGRUPACIÓN Y ORDENAMIENTO
        $groupedRecords = [];
        foreach ($registros as $registro) {
            $groupKey = $registro['fecha_carga'] . '_' . 
                       $registro['nombre_usuario'] . '_' . 
                       $registro['nombre_conductor'] . '_' . 
                       $registro['chapa'] . '_' . 
                       $registro['numero_baucher'] . '_' . 
                       $registro['litros_cargados'];
            
            if (!isset($groupedRecords[$groupKey])) {
                $groupedRecords[$groupKey] = [];
            }
            $groupedRecords[$groupKey][] = $registro;
        }
        
        // Ordenamiento por fecha_registro y ID de recorrido
        foreach ($groupedRecords as $groupKey => &$group) {
            usort($group, function($a, $b) {
                $fechaComparison = strtotime($a['fecha_registro']) - strtotime($b['fecha_registro']);
                if ($fechaComparison === 0) {
                    return intval($a['recorrido_id']) - intval($b['recorrido_id']);
                }
                return $fechaComparison;
            });
        }
        unset($group);
    }
}

// Procesar exportación a Excel
if ($export === 'excel' && $permitir_exportacion) {
    
    // Crear lista ordenada para exportar
    $registrosParaExportar = [];
    
    // Si hay IDs seleccionados, filtrar solo esos registros
    if (!empty($selected_ids) && is_array($selected_ids)) {
        foreach ($groupedRecords as $group) {
            foreach ($group as $registro) {
                if (in_array($registro['id'], $selected_ids)) {
                    $registrosParaExportar[] = $registro;
                }
            }
        }
    } else {
        // Si no hay selección específica, exportar todos los registros ordenados
        foreach ($groupedRecords as $group) {
            foreach ($group as $registro) {
                $registrosParaExportar[] = $registro;
            }
        }
    }
    
    if (!empty($registrosParaExportar)) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Configurar encabezados
        $headers = ['Fecha', 'Técnico', 'Conductor', 'Tipo Vehículo', 'Chapa', 'Nº Tarjeta', 'Nº Voucher', 'Litros Cargados',
                   'Secuencia', 'Origen', 'Origen Segmento', 'Origen CEBE', 'Origen Localidad', 'Origen M2 Neto',
                   'Destino', 'Destino Segmento', 'Destino CEBE', 'Destino Localidad', 'Destino M2 Neto',
                   'KM entre Sucursales', 'Comentarios', 'Documento'];
            $sheet->fromArray($headers, null, 'A1');
            
            // Estilo para encabezados
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4472C4']],
                'alignment' => ['horizontal' => 'center']
            ];
            $sheet->getStyle('A1:V1')->applyFromArray($headerStyle);
            
            // Agregar datos
            $row = 2;
            $currentGroup = null;
            $secuencia = 1;
            
            foreach ($registrosParaExportar as $registro) {
                // Detectar nuevo grupo para reiniciar secuencia
                $groupKey = $registro['fecha_carga'] . '_' . 
                           $registro['nombre_usuario'] . '_' . 
                           $registro['nombre_conductor'] . '_' . 
                           $registro['chapa'] . '_' . 
                           $registro['numero_baucher'] . '_' . 
                           $registro['litros_cargados'];
                
                if ($currentGroup !== $groupKey) {
                    $currentGroup = $groupKey;
                    $secuencia = 1;
                }
                
                $data = [
                    date('d/m/Y H:i', strtotime($registro['fecha_carga'] . ' ' . $registro['hora_carga'])),
                    $registro['nombre_usuario'] ?? '',
                    $registro['nombre_conductor'] ?? '',
                    ucfirst(str_replace('_', ' ', $registro['tipo_vehiculo'] ?? '')),
                    $registro['chapa'] ?? '',
                    $registro['tarjeta'] ?? '',
                    $registro['numero_baucher'] ?? '',
                    $registro['litros_cargados'] ?? 0,
                    $secuencia . '°', // NUEVA COLUMNA DE SECUENCIA
                    $registro['origen'] ?? '',
                    $registro['origen_segmento'] ?? '',
                    $registro['origen_cebe'] ?? '',
                    $registro['origen_localidad'] ?? '',
                    $registro['origen_m2_neto'] ?? '',
                    $registro['destino'] ?? '',
                    $registro['destino_segmento'] ?? '',
                    $registro['destino_cebe'] ?? '',
                    $registro['destino_localidad'] ?? '',
                    $registro['destino_m2_neto'] ?? '',
                    $registro['km_sucursales'] ?? 0,
                    $registro['comentarios_sector'] ?? '',
                    $registro['documento'] ?? ''
                ];
                $sheet->fromArray($data, null, 'A' . $row);
                $row++;
                $secuencia++;
            }
            
            // Ajustar ancho de columnas
            foreach (range('A', 'V') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Configurar el archivo para descarga
            $tipoExport = !empty($selected_ids) ? 'seleccionados' : 'filtrados';
            $filename = 'registros_combustible_' . $tipoExport . '_' . date('Y-m-d_H-i-s') . '.xlsx';
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
            // Registrar actividad de exportación
            $tipoRegistros = !empty($selected_ids) ? count($selected_ids) . ' registros seleccionados' : 'todos los registros filtrados';
            ActivityLogger::logAccion(
                $_SESSION['user_id'],
                'uso_combustible',
                'exportar_excel_interfaz',
                "Exportación Excel desde interfaz - {$tipoRegistros}, Fecha: {$fecha_inicio} a {$fecha_fin}"
            );
            
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registros de Uso de Combustible</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css">
    <style>
    .custom-alert {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 9999;
        text-align: center;
        min-width: 300px;
    }

    .multiple-indicator {
        font-size: 0.75em;
        color: #6c757d;
        margin-left: 5px;
        font-style: italic;
        background: #e9ecef;
        padding: 2px 6px;
        border-radius: 10px;
        white-space: nowrap;
    }
    .group-info {
        font-style: italic;
        color: #6c757d;
    }
    .export-options {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
    }
    .export-options h6 {
        color: #495057;
        margin-bottom: 15px;
        font-weight: 600;
    }
    .btn-excel {
        background: linear-gradient(45deg, #1d7044, #28a745);
        border: none;
        color: white;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    .btn-excel:hover {
        background: linear-gradient(45deg, #155724, #1e7e34);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    .selection-info {
        background: #e7f3ff;
        border: 1px solid #b3d7ff;
        border-radius: 6px;
        padding: 12px;
        margin: 15px 0;
        color: #0c5aa6;
        display: none;
    }
    .checkbox-column {

        width: 50px !important;
        text-align: center !important;
    }
    
    .main-checkbox, .sub-checkbox, #select-all-header {
        cursor: pointer !important;
        pointer-events: auto !important;
        position: relative !important;
        z-index: 1 !important;
        width: 18px;
        height: 18px;
        margin: 0 auto;
        display: block;
    }
    
    .main-checkbox:checked, .sub-checkbox:checked, #select-all-header:checked {
        background-color: #007bff !important;
        border-color: #007bff !important;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
    }
    
    .form-check-label {
        color: #495057;
        font-weight: 500;
    }
    
    .form-check-input:checked {
        background-color: #007bff;
        border-color: #007bff;
    }
    
    /* Indicadores de secuencia */
    .recorrido-numero {
        display: inline-block;
        color: white;
        font-size: 0.75em;
        font-weight: bold;
        padding: 3px 8px;
        border-radius: 50%;
        margin-right: 8px;
        min-width: 24px;
        text-align: center;
        line-height: 1.2;
    }
    
    .recorrido-numero.secuencial {
        background: #28a745; /* Verde para secuencia correcta */
    }
    
    .recorrido-numero.no-secuencial {
        background: #ffc107; /* Amarillo para advertencia */
    }
    
    .main-record .recorrido-numero {
        background: #007bff; /* Azul para el principal */
    }
    
    .secuencia-indicator {
        font-size: 0.75em;
        color: #28a745;
        margin-left: 8px;
        font-style: italic;
        background: #d4edda;
        padding: 2px 8px;
        border-radius: 12px;
        white-space: nowrap;
    }
    
    .origen-destino {
        font-weight: 500;
    }
    
    /* Estilos duplicados eliminados - ya están definidos arriba */

    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }
    
    @keyframes modalSlideIn {
        from {
            transform: scale(0.9) translateY(-20px);
            opacity: 0;
        }
        to {
            transform: scale(1) translateY(0);
            opacity: 1;
        }
    }
    
    /* Mejoras adicionales para la tabla */
    .table {
        border-collapse: separate;
        border-spacing: 0;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    .table thead th {
        background: linear-gradient(135deg, #343a40 0%, #495057 100%);
        color: white;
        border: none;
        padding: 15px 12px;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85em;
        letter-spacing: 0.5px;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .table tbody tr {
        transition: all 0.2s ease;
        border-bottom: 1px solid #dee2e6;
    }
    
    .table tbody tr:hover:not(.sub-record) {
        background: linear-gradient(90deg, #f1f3f4 0%, #ffffff 100%) !important;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .table tbody td {
        padding: 12px;
        vertical-align: middle;
        border-top: none;
        border-bottom: 1px solid #f1f3f4;
    }
    
    /* Indicador visual para registros con sub-registros */
    .has-subrecords {
        position: relative;
    }
    
    .has-subrecords::after {
        content: "";
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 3px;
        background: linear-gradient(180deg, #007bff 0%, #0056b3 100%);
    }
    
    /* Mejora del contenedor de la tabla */
    .table-responsive {
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }
    
    /* Animación para el contenido expandido */
    .expand-content {
        overflow: hidden;
        transition: max-height 0.3s ease-out;
    }
    
    /* Estilos para contenedor de sub-registros */
    .sub-records-container {
        background: #f8f9fa;
    }
    
    .sub-records-wrapper {
        padding: 15px;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        margin: 5px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        width: 100%;
        min-width: 100%;
        overflow: visible;
    }
    
    .sub-records-header {
        border-bottom: 2px solid #007bff;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }
    
    .sub-records-header h6 {
        color: #495057;
        font-weight: 600;
        margin: 0;
    }
    
    .sub-records-table {
        font-size: 0.9em;
        width: 100%;
        table-layout: auto;
        min-width: max-content;
    }
    
    /* Asegurar que el contenedor principal permita el desbordamiento horizontal */
    .card-body {
        overflow-x: auto;
        overflow-y: visible;
    }
    
    .sub-records-wrapper .table-responsive {
        overflow: visible;
        width: 100%;
    }
    
    .sub-records-table thead th {
        background: #f8f9fa;
        border-top: none;
        font-weight: 600;
        color: #495057;
        padding: 10px 8px;
        vertical-align: middle;
    }
    
    .sub-record {
        transition: background-color 0.2s ease;
    }
    
    .sub-record:hover {
        background-color: #f8f9fa;
    }
    
    .sub-record td {
        padding: 8px;
        vertical-align: middle;
        border-top: 1px solid #dee2e6;
    }
    
    .sub-select-all {
        transform: scale(1.1);
    }

    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="../../dashboard.php">
                <i class="fas fa-home mr-2"></i>Inicio
            </a>
            <div class="navbar-text text-white">
                <i class="fas fa-user mr-2"></i>
                Bienvenido, <?php echo isset($_SESSION['nombre']) ? htmlspecialchars($_SESSION['nombre']) : 'Usuario'; ?>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-gas-pump mr-2"></i>Registros de Uso de Combustible
                            </h5>
                            <div>
                                <a href="index.php" class="btn btn-light btn-sm mr-2">
                                    <i class="fas fa-arrow-left mr-2"></i>Volver
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">

                        <!--<!-- Logging de búsqueda -->
        <?php if (!empty($search) || !empty($fecha_inicio) || !empty($fecha_fin)): ?>
        <script>
        // Logging de búsqueda realizada
        $.post('../../config/log_activity.php', {
            action: 'log',
            modulo: 'uso_combustible',
            accion: 'busqueda_registros',
            detalle: 'Búsqueda realizada - Términos: ' + <?php echo json_encode($search); ?> + ', Fecha inicio: ' + <?php echo json_encode($fecha_inicio); ?> + ', Fecha fin: ' + <?php echo json_encode($fecha_fin); ?>
        });
        </script>
        <?php endif; ?>
        
        <!-- Búsqueda simplificada -->
                        <div class="search-container">
                            <form method="GET" class="mb-0" onsubmit="logBusqueda()">

                        <!-- Búsqueda simplificada -->
                        <div class="search-container">
                            <form method="GET" class="mb-0">

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="search"><i class="fas fa-search mr-2"></i>Búsqueda por palabras clave:</label>
                                            <input type="text" id="search" name="search" class="form-control" 
                                                   value="<?php echo htmlspecialchars($search); ?>" 
                                                   placeholder="Ej: Gran Union, Juan, ABC123...">
                                            <small class="form-text text-muted">Busca en técnico, conductor, chapa, origen, destino, voucher y documento</small>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group mb-3">
                                            <label for="fecha_inicio">Fecha Inicio:</label>
                                            <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control" 
                                                   value="<?php echo htmlspecialchars($fecha_inicio); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group mb-3">
                                            <label for="fecha_fin">Fecha Fin:</label>
                                            <input type="date" id="fecha_fin" name="fecha_fin" class="form-control" 
                                                   value="<?php echo htmlspecialchars($fecha_fin); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <div class="form-group mb-3 w-100">
                                            <button type="submit" class="btn btn-primary btn-block">
                                                <i class="fas fa-search mr-2"></i>Buscar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php if (!empty($search) || !empty($fecha_inicio) || !empty($fecha_fin)): ?>
                                <div class="row">
                                    <div class="col-12">
                                        <a href="ver_registros.php" class="btn btn-secondary btn-sm">
                                            <i class="fas fa-times mr-2"></i>Limpiar filtros
                                        </a>
                                        <span class="badge badge-info ml-2">
                                            <?php echo count($registros); ?> registro(s) encontrado(s)
                                        </span>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </form>
                        </div>

                        <?php if (empty($registros)): ?>
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle mr-2"></i>
                            No se encontraron registros con los criterios de búsqueda especificados.
                        </div>
                        <?php else: ?>
                        
                        <!-- Opciones de exportación -->
                        <div class="export-options">
                            <h6><i class="fas fa-download mr-2"></i>Opciones de Descarga</h6>
                            <form method="POST" action="ver_registros.php" id="exportForm">
                                <!-- Mantener los parámetros de búsqueda actuales -->
                                <input type="hidden" name="export" value="excel">
                                <?php if (!empty($search)): ?>
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                <?php endif; ?>
                                <?php if (!empty($fecha_inicio)): ?>
                                <input type="hidden" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
                                <?php endif; ?>
                                <?php if (!empty($fecha_fin)): ?>
                                <input type="hidden" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>">
                                <?php endif; ?>
                                
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="export_type" id="export_all" value="all" checked>
                                            <label class="form-check-label" for="export_all">
                                                <i class="fas fa-list mr-1"></i>Descargar todos los registros mostrados (<?php echo count($registros); ?>)
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline ml-3">
                                            <input class="form-check-input" type="radio" name="export_type" id="export_selected" value="selected">
                                            <label class="form-check-label" for="export_selected">
                                                <i class="fas fa-check-square mr-1"></i>Descargar solo los seleccionados (<span id="selected-count">0</span>)
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-right">
                                        <button type="submit" class="btn btn-excel" id="downloadBtn">
                                            <i class="fas fa-file-excel mr-2"></i>Descargar Excel
                                        </button>
                                    </div>
                                </div>
                                <div id="selected-ids-container"></div>
                            </form>
                        </div>
                        
                        <!-- Información de selección -->
                        <div class="selection-info" id="selection-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            <span id="selection-text">Has seleccionado 0 registros</span>
                            <button type="button" class="btn btn-sm btn-outline-primary ml-3" id="select-all-btn">
                                <i class="fas fa-check-double mr-1"></i>Seleccionar todos
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary ml-2" id="clear-selection-btn">
                                <i class="fas fa-times mr-1"></i>Limpiar selección
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="registrosTable">
                                <thead class="thead-dark">
                                    <tr>
                                        <th class="checkbox-column">
                                            <input type="checkbox" class="form-check-input" id="select-all-header" title="Seleccionar/Deseleccionar todos">
                                        </th>
                                        <th>Conductor</th>
                                        <th>Chapa</th>
                                        <th>Nº Voucher</th>
                                        <th>Nº Tarjeta</th>
                                        <th>Foto Voucher</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                    // Agrupar registros por carga principal
                    $groupedRecords = [];
                    foreach ($registros as $registro) {
                        $groupKey = $registro['fecha_carga'] . '_' . 
                                   $registro['nombre_usuario'] . '_' . 
                                   $registro['nombre_conductor'] . '_' . 
                                   $registro['chapa'] . '_' . 
                                   $registro['numero_baucher'] . '_' . 
                                   $registro['litros_cargados'];
                        
                        if (!isset($groupedRecords[$groupKey])) {
                            $groupedRecords[$groupKey] = [];
                        }
                        $groupedRecords[$groupKey][] = $registro;
                    }
                    
                    // NUEVO: Ordenamiento por fecha_registro y ID de recorrido

        foreach ($groupedRecords as $groupKey => &$group) {
            usort($group, function($a, $b) {
                // Primero por fecha_registro, luego por ID de recorrido
                $fechaComparison = strtotime($a['fecha_registro']) - strtotime($b['fecha_registro']);
                if ($fechaComparison === 0) {
                    // Si tienen la misma fecha_registro, ordenar por recorrido_id
                    return intval($a['recorrido_id']) - intval($b['recorrido_id']);
                }
                return $fechaComparison;
            });
        }
        unset($group); // Limpiar referencia

                    foreach ($groupedRecords as $groupKey => &$group) {
                        usort($group, function($a, $b) {
                            // Primero por fecha_registro, luego por ID de recorrido
                            $fechaComparison = strtotime($a['fecha_registro']) - strtotime($b['fecha_registro']);
                            if ($fechaComparison === 0) {
                                // Si tienen la misma fecha_registro, ordenar por recorrido_id
                                return intval($a['recorrido_id']) - intval($b['recorrido_id']);
                            }
                            return $fechaComparison;
                        });
                    }
                    unset($group); // Limpiar referencia

                    
                    $groupIndex = 0;
                    $totalGroups = count($groupedRecords);
                    $multipleGroups = 0;
                    
                    // DEBUG: Contar grupos múltiples
                    foreach ($groupedRecords as $tempKey => $tempGroup) {
                        if (count($tempGroup) > 1) {
                            $multipleGroups++;
                        }
                    }
                    
                    // DEBUG: Mostrar información en comentario HTML
                    echo "<!-- DEBUG: Total grupos: $totalGroups, Grupos múltiples: $multipleGroups -->";
                    
                    foreach ($groupedRecords as $groupKey => $group): 
                        $isMultiple = count($group) > 1;
                        $mainRecord = $group[0]; // Ahora es el PRIMER recorrido registrado cronológicamente
                        $groupIndex++;
                        
                        // DEBUG: Información del grupo
                        echo "<!-- DEBUG Grupo $groupIndex: isMultiple=" . ($isMultiple ? 'true' : 'false') . ", count=" . count($group) . ", key=$groupKey -->";
                    ?>
                                    <!-- Registro principal -->
                                    <tr class="main-record" data-group="<?php echo $groupIndex; ?>">
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input main-checkbox" 
                                                   value="<?php echo $mainRecord['id']; ?>" 
                                                   data-group="<?php echo $groupIndex; ?>">
                                        </td>
                                        <td>
                                            <?php if ($isMultiple): ?>
                                                <button type="button" class="btn btn-sm btn-outline-secondary expand-btn mr-2" 
                                                        data-group="<?php echo $groupIndex; ?>" 
                                                        title="Expandir registros">
                                                    <i class="fas fa-chevron-right"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($mainRecord['nombre_conductor'] ?? ''); ?>
                                            <?php if ($isMultiple): ?>
                                                <span class="badge badge-info ml-2"><?php echo count($group); ?> recorridos</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($mainRecord['chapa'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($mainRecord['numero_baucher'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($mainRecord['tarjeta'] ?? ''); ?></td>
                                        <td class="text-center">
                                            <?php if (!empty($mainRecord['foto_voucher_ruta']) || !empty($mainRecord['foto_voucher'])): ?>
                                                <button type="button" class="btn btn-sm btn-info ver-foto-inline" 
                                                        data-foto="<?php echo htmlspecialchars($mainRecord['foto_voucher']); ?>"
                                                        data-foto-ruta="<?php echo htmlspecialchars($mainRecord['foto_voucher_ruta']); ?>"
                                                        title="Ver foto del voucher">
                                                    <i class="fas fa-image"></i> Ver
                                                </button>
                                                <div class="voucher-preview" style="display:none;">
                                                    <img src="" alt="Foto voucher">
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">Sin foto</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown d-inline-block">
                                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-h"></i> Acciones
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    <div class="px-3 py-2 text-muted">
                                                        Estado: 
                                                        <?php if ($mainRecord['estado_recorrido'] === 'abierto'): ?>
                                                            <span class="badge badge-success">Abierto</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-danger">Cerrado</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if ($mainRecord['estado_recorrido'] === 'abierto' && in_array($rol, ['tecnico', 'supervisor', 'administrador']) && $mainRecord['user_id'] == $_SESSION['user_id']): ?>
                                                        <a class="dropdown-item" href="#" onclick="cerrarRecorrido(<?php echo $mainRecord['id']; ?>)"><i class="fas fa-lock mr-2"></i>Cerrar Recorrido</a>
                                                    <?php endif; ?>
                                                    <?php if ($mainRecord['estado_recorrido'] === 'cerrado' && $rol === 'administrador'): ?>
                                                        <a class="dropdown-item" href="#" onclick="reabrirRecorrido(<?php echo $mainRecord['id']; ?>)"><i class="fas fa-unlock mr-2"></i>Reabrir Recorrido</a>
                                                    <?php endif; ?>
                                                    <?php if ($puedeModificar): ?>
                                                        <?php if ($mainRecord['estado_recorrido'] === 'cerrado'): ?>
                                                            <span class="dropdown-item text-muted"><i class="fas fa-edit mr-2"></i>No se puede editar</span>
                                                        <?php else: ?>
                                                            <a class="dropdown-item" href="editar_registro.php?id=<?php echo $mainRecord['id']; ?>"><i class="fas fa-edit mr-2"></i>Editar</a>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                    <?php if ($_SESSION['user_rol'] === 'administrador'): ?>
                                                        <div class="dropdown-divider"></div>
                                                        <a class="dropdown-item text-danger eliminar-registro" href="#"
                                                           data-id="<?php echo $mainRecord['id']; ?>"
                                                           data-conductor="<?php echo htmlspecialchars($mainRecord['nombre_conductor'] ?? ''); ?>"
                                                           data-chapa="<?php echo htmlspecialchars($mainRecord['chapa'] ?? ''); ?>">
                                                           <i class="fas fa-trash mr-2"></i>Eliminar
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    
                                    <?php if ($isMultiple): ?>
                                    <!-- Subtabla para registros adicionales -->
                                    <tr class="sub-records-container" data-group="<?php echo $groupIndex; ?>" style="display: none;">
                                        <td colspan="7">
                                            <div class="sub-table-container">
                                                <div class="sub-table-header">
                                                    <h6><i class="fas fa-route mr-2"></i>Todos los recorridos (<?php echo count($group); ?>)</h6>
                                                    <div class="sub-table-controls">
                                                        <input type="checkbox" class="form-check-input sub-select-all" 
                                                               data-group="<?php echo $groupIndex; ?>" 
                                                               title="Seleccionar/Deseleccionar todos los sub-registros">
                                                        <label class="form-check-label ml-2">Seleccionar todos</label>
                                                    </div>
                                                </div>
                                                <table class="table table-sm table-bordered sub-table">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th width="40">Sel.</th>
                                                            <th>Fecha/Hora</th>
                                                            <th>Tipo Vehículo</th>
                                                            <th>Conductor</th>
                                                            <th>Chapa</th>
                                                            <th>Nº Voucher</th>
                                                            <th>Nº Tarjeta</th>
                                                            <th>Litros</th>
                                                            <th>Origen</th>
                                                            <th>Destino</th>
                                                            <th>Documento</th>
                                                            <th>Foto</th>
                                                            <th>Acciones</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php for ($i = 0; $i < count($group); $i++): 
                                                            $subRecord = $group[$i];
                                                        ?>
                                                        <tr>
                                                            <td class="text-center">
                                                                <input type="checkbox" class="form-check-input sub-checkbox" 
                                                                       value="<?php echo $subRecord['id']; ?>" 
                                                                       data-group="<?php echo $groupIndex; ?>">
                                                            </td>
                                                            <td><?php echo date('d/m/Y H:i', strtotime($subRecord['fecha_carga'] . ' ' . $subRecord['hora_carga'])); ?></td>
                                                            <td><?php echo ucfirst(str_replace('_', ' ', $subRecord['tipo_vehiculo'] ?? '')); ?></td>
                                                            <td><?php echo htmlspecialchars($subRecord['nombre_conductor'] ?? ''); ?></td>
                                                            <td><?php echo htmlspecialchars($subRecord['chapa'] ?? ''); ?></td>
                                                            <td><?php echo htmlspecialchars($subRecord['numero_baucher'] ?? ''); ?></td>
                                                            <td><?php echo htmlspecialchars($subRecord['tarjeta'] ?? ''); ?></td>
                                                            <td><?php echo number_format($subRecord['litros_cargados'] ?? 0, 2); ?></td>
                                                            <td><?php echo htmlspecialchars($subRecord['origen'] ?? ''); ?></td>
                                                            <td><?php echo htmlspecialchars($subRecord['destino'] ?? ''); ?></td>
                                                            <td><?php echo htmlspecialchars($subRecord['documento'] ?? ''); ?></td>
                                                            <td class="text-center">
                                                                <?php if (!empty($subRecord['foto_voucher_ruta']) || !empty($subRecord['foto_voucher'])): ?>
                                                                    <button type="button" class="btn btn-sm btn-info ver-foto-inline" 
                                                                            data-foto="<?php echo htmlspecialchars($subRecord['foto_voucher']); ?>"
                                                                            data-foto-ruta="<?php echo htmlspecialchars($subRecord['foto_voucher_ruta']); ?>"
                                                                            title="Ver foto del voucher">
                                                                        <i class="fas fa-image"></i>
                                                                    </button>
                                                                <?php else: ?>
                                                                    <span class="text-muted">-</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="text-center">
                                                                <div class="dropdown d-inline-block">
                                                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                                                        <i class="fas fa-ellipsis-h"></i>
                                                                    </button>
                                                                    <div class="dropdown-menu dropdown-menu-right">
                                                                        <div class="px-3 py-2 text-muted">
                                                                            Estado: 
                                                                            <?php if ($subRecord['estado_recorrido'] === 'abierto'): ?>
                                                                                <span class="badge badge-success">Abierto</span>
                                                                            <?php else: ?>
                                                                                <span class="badge badge-danger">Cerrado</span>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <?php if ($subRecord['estado_recorrido'] === 'abierto' && in_array($rol, ['tecnico', 'supervisor', 'administrador']) && $subRecord['user_id'] == $_SESSION['user_id']): ?>
                                                                            <a class="dropdown-item" href="#" onclick="cerrarRecorrido(<?php echo $subRecord['id']; ?>)"><i class="fas fa-lock mr-2"></i>Cerrar</a>
                                                                        <?php endif; ?>
                                                                        <?php if ($subRecord['estado_recorrido'] === 'cerrado' && $rol === 'administrador'): ?>
                                                                            <a class="dropdown-item" href="#" onclick="reabrirRecorrido(<?php echo $subRecord['id']; ?>)"><i class="fas fa-unlock mr-2"></i>Reabrir</a>
                                                                        <?php endif; ?>
                                                                        <?php if ($puedeModificar): ?>
                                                                            <?php if ($subRecord['estado_recorrido'] === 'cerrado'): ?>
                                                                                <span class="dropdown-item text-muted"><i class="fas fa-edit mr-2"></i>No editable</span>
                                                                            <?php else: ?>
                                                                                <a class="dropdown-item" href="editar_registro.php?id=<?php echo $subRecord['id']; ?>"><i class="fas fa-edit mr-2"></i>Editar</a>
                                                                            <?php endif; ?>
                                                                        <?php endif; ?>
                                                                        <?php if ($_SESSION['user_rol'] === 'administrador'): ?>
                                                                            <div class="dropdown-divider"></div>
                                                                            <a class="dropdown-item text-danger eliminar-registro" href="#"
                                                                               data-id="<?php echo $subRecord['id']; ?>"
                                                                               data-conductor="<?php echo htmlspecialchars($subRecord['nombre_conductor'] ?? ''); ?>"
                                                                               data-chapa="<?php echo htmlspecialchars($subRecord['chapa'] ?? ''); ?>">
                                                                               <i class="fas fa-trash mr-2"></i>Eliminar
                                                                            </a>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <?php endfor; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    <!-- Modal de confirmación personalizado -->
    <div id="customConfirmModal" class="custom-modal">
        <div class="custom-modal-content">
            <div class="custom-modal-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h4>Confirmar Eliminación</h4>
            </div>
            <div class="custom-modal-body">
                <p>¿Estás seguro de que deseas eliminar este registro?</p>
                <div class="custom-modal-info">
                    <div><strong>Conductor:</strong> <span id="modalConductorCustom">N/A</span></div>
                    <div><strong>Chapa:</strong> <span id="modalChapaCustom">N/A</span></div>
                </div>
                <p><small>Esta acción no se puede deshacer.</small></p>
            </div>
            <div class="custom-modal-footer">
                <button type="button" class="custom-modal-btn custom-modal-btn-cancel" id="btnCancelarCustom">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="custom-modal-btn custom-modal-btn-confirm" id="btnConfirmarCustom">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </div>
        </div>
    </div>

    <!-- Estilos para el modal personalizado -->
    <style>
    /* Modal de confirmación personalizado */
    .custom-modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(3px);
    }
    
    .custom-modal-content {
        background-color: #fff;
        margin: 10% auto;
        padding: 0;
        border-radius: 12px;
        width: 90%;
        max-width: 450px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        animation: modalSlideIn 0.3s ease-out;
        overflow: hidden;
    }
    
    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-50px) scale(0.9);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    
    .custom-modal-header {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
        padding: 20px;
        text-align: center;
        position: relative;
    }
    
    .custom-modal-header h4 {
        margin: 0;
        font-size: 1.3rem;
        font-weight: 600;
    }
    
    .custom-modal-header i {
        font-size: 2.5rem;
        margin-bottom: 10px;
        opacity: 0.9;
    }
    
    .custom-modal-body {
        padding: 25px;
        text-align: center;
        color: #333;
    }
    
    .custom-modal-body p {
        margin: 0 0 15px 0;
        font-size: 1.1rem;
        line-height: 1.5;
    }
    
    .custom-modal-info {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin: 15px 0;
        border-left: 4px solid #dc3545;
    }
    
    .custom-modal-info strong {
        color: #dc3545;
    }
    
    .custom-modal-footer {
        padding: 20px 25px;
        display: flex;
        justify-content: space-between;
        gap: 15px;
        background-color: #f8f9fa;
    }
    
    .custom-modal-btn {
        flex: 1;
        padding: 12px 20px;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .custom-modal-btn-cancel {
        background-color: #6c757d;
        color: white;
    }
    
    .custom-modal-btn-cancel:hover {
        background-color: #5a6268;
        transform: translateY(-2px);
    }
    
    .custom-modal-btn-confirm {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
    }
    
    .custom-modal-btn-confirm:hover {
        background: linear-gradient(135deg, #c82333, #a71e2a);
        transform: translateY(-2px);
    }
    
    .custom-modal-btn:active {
        transform: translateY(0);
    }
    </style>

    <!-- Librerías JavaScript -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
    
    <!-- Script de manejo de errores mejorado -->
    <script src="error_handler.js"></script>

    <?php if (isset($mostrar_error_seleccionados) && $mostrar_error_seleccionados): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            mostrarModalMejorado(
                'warning',
                'Recorridos Seleccionados Abiertos',
                '<?php echo addslashes($error_message); ?>'
            );
        }, 500);
    });
    </script>
    <?php endif; ?>

    <?php if (isset($mostrar_error_rango) && $mostrar_error_rango): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            mostrarModalMejorado(
                'warning',
                'Recorridos Abiertos en Rango',
                '<?php echo addslashes($error_message); ?>'
            );
        }, 500);
    });
    </script>
    <?php endif; ?>

    <!-- Modal personalizado para mostrar foto del voucher -->
    <div id="fotoVoucherModal" class="foto-modal" style="display: none;">
        <div class="foto-modal-overlay">
            <div class="foto-modal-content">
                <div class="foto-modal-header">
                    <h5><i class="fas fa-image mr-2"></i>Foto del Voucher</h5>
                    <button type="button" class="foto-modal-close" id="closeFotoModal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="foto-modal-body">
                    <img id="fotoVoucherImg" src="" alt="Foto del voucher" class="foto-modal-img">
                </div>
                <div class="foto-modal-footer">
                    <button type="button" class="btn btn-secondary" id="closeFotoModalBtn">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <style>
    /* Estilos para botones ver-foto - Corregir parpadeo */
    .btn-info.ver-foto,
    .btn-info.ver-foto-inline {
        background: linear-gradient(135deg, #17a2b8, #138496) !important;
        border: 1px solid #17a2b8 !important;
        color: white !important;
        transition: all 0.2s ease !important;
        position: relative !important;
        overflow: hidden !important;
    }
    
    .btn-info.ver-foto:hover,
    .btn-info.ver-foto-inline:hover {
        background: linear-gradient(135deg, #138496, #117a8b) !important;
        border-color: #138496 !important;
        color: white !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 8px rgba(23, 162, 184, 0.3) !important;
    }
    
    .btn-info.ver-foto:focus,
    .btn-info.ver-foto-inline:focus {
        background: linear-gradient(135deg, #17a2b8, #138496) !important;
        border-color: #17a2b8 !important;
        color: white !important;
        box-shadow: 0 0 0 0.2rem rgba(23, 162, 184, 0.25) !important;
    }
    
    .btn-info.ver-foto:active,
    .btn-info.ver-foto-inline:active {
        background: linear-gradient(135deg, #138496, #117a8b) !important;
        border-color: #138496 !important;
        transform: translateY(0) !important;
    }
    
    /* Estado activo para vista previa inline */
    .btn-info.ver-foto-inline.active {
        background: linear-gradient(135deg, #28a745, #20c997) !important;
        border-color: #20c997 !important;
        box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.25) !important;
    }
    
    .btn-info.ver-foto-inline.active:hover {
        background: linear-gradient(135deg, #218838, #1ea085) !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3) !important;
    }

    /* Modal personalizado para fotos - Centrado y Estable */
    .foto-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        z-index: 10000;
        background-color: rgba(0, 0, 0, 0.9);
        display: none;
        align-items: center;
        justify-content: center;
        padding: 0;
        box-sizing: border-box;
        overflow: hidden;
    }

    .foto-modal.show {
        display: flex !important;
    }

    .foto-modal-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        box-sizing: border-box;
    }

    .foto-modal-content {
        background: white;
        border-radius: 15px;
        width: 700px;
        height: 550px;
        overflow: hidden;
        box-shadow: 0 25px 80px rgba(0, 0, 0, 0.8);
        display: flex;
        flex-direction: column;
        position: relative;
        margin: 0 auto;
    }

    .foto-modal-header {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
        border-radius: 12px 12px 0 0;
    }

    .foto-modal-header h5 {
        margin: 0;
        font-size: 1.2rem;
        font-weight: 600;
    }

    .foto-modal-close {
        background: none;
        border: none;
        color: white;
        font-size: 1.8rem;
        cursor: pointer;
        padding: 5px;
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: all 0.2s ease;
    }

    .foto-modal-close:hover {
        background-color: rgba(255, 255, 255, 0.2);
        transform: scale(1.1);
    }

    .foto-modal-body {
        padding: 20px;
        text-align: center;
        flex: 1;
        overflow: auto;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 0;
    }

    .foto-modal-img {
        width: 600px;
        height: 450px;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        object-fit: contain;
        background: #f8f9fa;
        display: block;
        margin: 0 auto;
    }

    .foto-modal-footer {
        padding: 15px 20px;
        background: #f8f9fa;
        text-align: right;
        flex-shrink: 0;
        border-radius: 0 0 12px 12px;
        border-top: 1px solid #dee2e6;
    }
    
    /* Responsividad del modal */
    @media (max-width: 768px) {
        .foto-modal {
            padding: 10px;
        }
        
        .foto-modal-content {
            max-width: 98vw;
            max-height: 98vh;
        }
        
        .foto-modal-img {
            width: 90vw;
            height: 60vh;
            max-width: 400px;
            max-height: 300px;
        }
        
        .foto-modal-header {
            padding: 12px 15px;
        }
        
        .foto-modal-header h5 {
            font-size: 1.1rem;
        }
        
        .foto-modal-body {
            padding: 15px;
        }
        
        .foto-modal-footer {
            padding: 12px 15px;
        }
    }
    
    /* Responsividad para tablets */
    @media (min-width: 769px) and (max-width: 1024px) {
        .foto-modal-img {
            width: 500px;
            height: 375px;
        }
    }
    
    /* Animaciones del modal - Mejoradas */
    .foto-modal {
        opacity: 0;
        visibility: hidden;
        transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }
    
    .foto-modal.show {
        opacity: 1;
        visibility: visible;
    }
    
    .foto-modal-content {
        transform: scale(0.7) translateY(-50px);
        transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }
    
    .foto-modal.show .foto-modal-content {
        transform: scale(1) translateY(0);
    }
    
    /* Vista previa inline de imágenes - Sin deformación de tabla */
    .voucher-preview {
        margin-top: 8px;
        padding: 4px;
        background: #f8f9fa;
        border-radius: 4px;
        border: 1px solid #dee2e6;
        text-align: center;
        max-width: 140px;
        width: max-content;
        margin-left: auto;
        margin-right: auto;
    }
    
    .voucher-preview img {
        max-width: 130px;
        max-height: 70px;
        width: auto;
        height: auto;
        object-fit: contain;
        border-radius: 3px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: transform 0.2s ease;
        display: block;
    }
    
    .voucher-preview img:hover {
        transform: scale(1.02);
        cursor: pointer;
    }
    
    /* Asegurar que las celdas de foto no se deformen */
    td .voucher-preview {
        overflow: hidden;
    }
    
    /* Corregir z-index del dropdown de acciones - MEJORADO */
    .dropdown {
        position: relative;
        z-index: 1000;
        transition: z-index 0s !important; /* Sin transición para cambio inmediato */
    }
    
    /* Dropdown activo tiene mayor z-index */
    .dropdown.show {
        z-index: 1060 !important;
    }
    
    .dropdown-menu {
        z-index: 1070 !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
        border: 1px solid rgba(0,0,0,0.1) !important;
        border-radius: 8px !important;
        padding: 8px 0 !important;
        min-width: 180px !important;
        position: absolute !important;
        transition: none !important; /* Sin transiciones para cambio inmediato */
    }
    
    /* Dropdown menu cuando está visible */
    .dropdown-menu.show {
        z-index: 1080 !important;
    }
    
    /* Forzar cierre inmediato de dropdowns no activos */
    .dropdown:not(.show) .dropdown-menu {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
    }
    
    .dropdown-item {
        padding: 8px 16px !important;
        transition: all 0.2s ease !important;
        border-radius: 0 !important;
    }
    
    .dropdown-item:hover {
        background-color: #f8f9fa !important;
        transform: translateX(2px) !important;
    }
    
    .dropdown-toggle {
        position: relative;
        z-index: 1001;
    }
    
    /* Asegurar que la tabla no interfiera con dropdowns */
    .table-responsive {
        overflow: visible !important;
    }
    
    /* Permitir que las filas expandibles se muestren completamente */
    tbody tr {
        position: relative;
    }
    
    tbody tr:hover {
        z-index: 10;
    }
    
    /* Fila con dropdown activo tiene mayor z-index */
    tbody tr:has(.dropdown.show) {
        z-index: 1050 !important;
    }
    
    /* Fallback para navegadores que no soportan :has() */
    tbody tr.dropdown-active {
        z-index: 1050 !important;
    }
    </style>

    <script>
// Esperar a que jQuery esté completamente cargado
jQuery(document).ready(function($) {
    'use strict';
    
    console.log('🚀 Iniciando ver_registros.php');
    
    // Variables globales
    let idToDelete = null;
    
    // MANEJO DE DROPDOWNS - Evitar superposición (MEJORADO)
    
    // Función para cerrar todos los dropdowns inmediatamente
    function cerrarTodosDropdowns() {
        $('.dropdown').removeClass('show');
        $('.dropdown-menu').removeClass('show').hide();
        $('.dropdown-toggle').attr('aria-expanded', 'false');
        $('tbody tr').removeClass('dropdown-active');
    }
    
    // No interceptar el clic en dropdown-toggle, dejar que Bootstrap lo maneje
    
    // Manejar eventos de Bootstrap
    $(document).on('show.bs.dropdown', '.dropdown', function() {
        // Cerrar todos los otros dropdowns
        $('.dropdown').not(this).removeClass('show').find('.dropdown-menu').removeClass('show');
        $('.dropdown-toggle').not($(this).find('.dropdown-toggle')).attr('aria-expanded', 'false');
        
        // Remover clase dropdown-active de todas las filas
        $('tbody tr').removeClass('dropdown-active');
        
        // Agregar clase dropdown-active a la fila actual
        $(this).closest('tr').addClass('dropdown-active');
    });
    
    $(document).on('hide.bs.dropdown', '.dropdown', function() {
        // Remover clase dropdown-active de la fila
        $(this).closest('tr').removeClass('dropdown-active');
    });
    
    // Cerrar dropdowns al hacer clic fuera (más agresivo)
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.dropdown').length) {
            cerrarTodosDropdowns();
        }
    });
    
    // Cerrar dropdowns al hacer scroll (prevenir problemas de posicionamiento)
    $(window).on('scroll', function() {
        cerrarTodosDropdowns();
    });
    
    // Manejar clic en botón "Ver foto" - SOLO MODAL COMPLETO (para botones .ver-foto)
    $(document).on('click', '.ver-foto', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('🖼️ Botón ver foto modal clickeado');
        
        const fotoRuta = $(this).data('foto-ruta');
        const fotoBase64 = $(this).data('foto');
        const registroId = $(this).closest('tr').find('input[type="checkbox"]').val();
        
        console.log('📊 Datos de foto:', {
            fotoRuta: fotoRuta,
            fotoBase64: fotoBase64 ? 'Presente (' + fotoBase64.length + ' chars)' : 'No disponible',
            registroId: registroId
        });
        
        // Registrar visualización de foto
        $.post('../../config/log_activity.php', {
            action: 'log',
            modulo: 'uso_combustible',
            accion: 'ver_foto_modal',
            detalle: `Visualización de foto voucher en modal - Registro ID: ${registroId}`
        });

        // Crear lightbox simple y elegante
        mostrarLightbox(fotoRuta, fotoBase64, registroId);
    });
    
    // Función para mostrar lightbox elegante
    function mostrarLightbox(fotoRuta, fotoBase64, registroId) {
        // Remover lightbox existente si hay uno
        $('.lightbox-overlay').remove();
        
        let imgSrc;
        if (fotoRuta) {
            imgSrc = '../../img/uso_combustible/vouchers/' + fotoRuta;
            console.log('✅ Usando ruta de archivo:', imgSrc);
        } else if (fotoBase64) {
            imgSrc = 'data:image/jpeg;base64,' + fotoBase64;
            console.log('✅ Usando Base64, longitud:', fotoBase64.length);
        } else {
            console.log('❌ No hay foto disponible');
            mostrarModalMejorado('warning', 'Sin foto', 'No hay foto disponible para este voucher');
            return;
        }
        
        // Crear lightbox HTML
        const lightboxHTML = `
            <div class="lightbox-overlay" style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(0, 0, 0, 0.95);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 99999;
                opacity: 0;
                transition: opacity 0.3s ease;
            ">
                <div class="lightbox-content" style="
                    position: relative;
                    max-width: 90vw;
                    max-height: 90vh;
                    background: white;
                    border-radius: 10px;
                    overflow: hidden;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.8);
                ">
                    <div class="lightbox-header" style="
                        background: linear-gradient(135deg, #007bff, #0056b3);
                        color: white;
                        padding: 15px 20px;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    ">
                        <h5 style="margin: 0; font-size: 1.2rem;">📷 Foto del Voucher</h5>
                        <button class="lightbox-close" style="
                            background: none;
                            border: none;
                            color: white;
                            font-size: 24px;
                            cursor: pointer;
                            padding: 0;
                            width: 30px;
                            height: 30px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            border-radius: 50%;
                            transition: background 0.2s;
                        " onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='none'">×</button>
                    </div>
                    <div class="lightbox-body" style="
                        padding: 20px;
                        text-align: center;
                        background: #f8f9fa;
                    ">
                        <img class="lightbox-img" src="${imgSrc}" style="
                            max-width: 100%;
                            max-height: 70vh;
                            width: auto;
                            height: auto;
                            border-radius: 8px;
                            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
                        " alt="Foto del Voucher">
                    </div>
                </div>
            </div>
        `;
        
        // Añadir al DOM
        $('body').append(lightboxHTML);
        $('body').css('overflow', 'hidden');
        
        // Mostrar con animación
        setTimeout(() => {
            $('.lightbox-overlay').css('opacity', '1');
        }, 10);
        
        // Eventos de cierre
        $('.lightbox-close, .lightbox-overlay').on('click', function(e) {
            if (e.target === this) {
                cerrarLightbox();
            }
        });
        
        // Cerrar con ESC
        $(document).on('keydown.lightbox', function(e) {
            if (e.keyCode === 27) {
                cerrarLightbox();
            }
        });
        
        console.log('🎯 Lightbox elegante mostrado');
    }
    
    // Función para cerrar lightbox
    function cerrarLightbox() {
        $('.lightbox-overlay').css('opacity', '0');
        setTimeout(() => {
            $('.lightbox-overlay').remove();
            $('body').css('overflow', 'auto');
            $(document).off('keydown.lightbox');
        }, 300);
        console.log('🔒 Lightbox cerrado');
    }
    
    // Función para cerrar el modal con animación mejorada
    function cerrarFotoModal() {
        console.log('🔒 Cerrando modal de foto');
        const modal = $('#fotoVoucherModal');
        modal.removeClass('show');
        
        // Esperar a que termine la animación antes de ocultar
        setTimeout(() => {
            modal.hide();
            modal.css('display', 'none');
            $('#fotoVoucherImg').attr('src', '');
            $('body').css('overflow', 'auto'); // Restaurar scroll
            console.log('✅ Modal cerrado completamente');
        }, 400); // Aumentado para coincidir con la nueva duración de animación
    }
    
    // Cerrar modal con botón X
    $('#closeFotoModal').on('click', cerrarFotoModal);
    
    // Cerrar modal con botón Cerrar
    $('#closeFotoModalBtn').on('click', cerrarFotoModal);
    
    // Cerrar modal al hacer clic en el fondo
    $('#fotoVoucherModal').on('click', function(e) {
        if (e.target === this) {
            cerrarFotoModal();
        }
    });
    
    // Prevenir que el clic en el contenido cierre el modal
    $('.foto-modal-content').on('click', function(e) {
        e.stopPropagation();
    });
    
    // Cerrar modal con tecla ESC
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
            if ($('#fotoVoucherModal').is(':visible')) {
                cerrarFotoModal();
            }
        }
    });
    
    // Manejar clic en botón "Ver foto inline" - USAR LIGHTBOX ELEGANTE
    $(document).on('click', '.ver-foto-inline', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('🖼️ Botón ver foto inline clickeado - usando lightbox');
        
        const fotoRuta = $(this).data('foto-ruta');
        const fotoBase64 = $(this).data('foto');
        const registroId = $(this).closest('tr').find('input[type="checkbox"]').val();
        
        console.log('📊 Datos de foto inline:', {
            fotoRuta: fotoRuta,
            fotoBase64: fotoBase64 ? 'Presente (' + fotoBase64.length + ' chars)' : 'No disponible',
            registroId: registroId
        });
        
        // Registrar visualización de foto
        $.post('../../config/log_activity.php', {
            action: 'log',
            modulo: 'uso_combustible',
            accion: 'ver_foto_inline_lightbox',
            detalle: `Visualización de foto voucher en lightbox desde botón inline - Registro ID: ${registroId}`
        });

        // Usar el mismo lightbox elegante
        mostrarLightbox(fotoRuta, fotoBase64, registroId);
    });
    
    // Al hacer clic en la imagen inline, abrir el modal completo
    $(document).on('click', '.voucher-preview img', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('🖼️ Clic en imagen inline - abriendo modal completo');
        
        const btn = $(this).closest('td').find('.ver-foto-inline');
        const registroId = btn.closest('tr').find('input[type="checkbox"]').val();
        
        // Registrar apertura de modal desde imagen inline
        $.post('../../config/log_activity.php', {
            action: 'log',
            modulo: 'uso_combustible',
            accion: 'ver_foto_modal_desde_inline',
            detalle: `Apertura de modal desde imagen inline - Registro ID: ${registroId}`
        });
        
        // Obtener datos de la imagen
        const fotoBase64 = btn.data('foto');
        const fotoRuta = btn.data('foto-ruta');
        
        let imgSrc;
        if (fotoRuta) {
            imgSrc = '../../img/uso_combustible/vouchers/' + fotoRuta;
            console.log('✅ Usando ruta de archivo para modal:', imgSrc);
        } else if (fotoBase64) {
            imgSrc = 'data:image/jpeg;base64,' + fotoBase64;
            console.log('✅ Usando Base64 para modal, longitud:', fotoBase64.length);
        } else {
            console.log('❌ No hay foto disponible para modal');
            mostrarModalMejorado('warning', 'Sin foto', 'No hay foto disponible para este voucher');
            return;
        }
        
        // Verificar que la imagen se puede cargar antes de mostrar el modal
        const testImg = new Image();
        testImg.onload = function() {
            console.log('✅ Imagen cargada correctamente para modal desde inline');
            $('#fotoVoucherImg').attr('src', imgSrc);
            
            // Mostrar modal centrado desde imagen inline
            const modal = $('#fotoVoucherModal');
            modal.css('display', 'flex'); // Usar flex para centrado perfecto
            $('body').css('overflow', 'hidden'); // Prevenir scroll
            
            setTimeout(() => {
                modal.addClass('show');
                console.log('🎯 Modal centrado desde inline y visible');
            }, 50);
        };
        testImg.onerror = function() {
            console.error('❌ Error al cargar imagen para modal:', imgSrc);
            mostrarModalMejorado('error', 'Error de imagen', 'No se pudo cargar la imagen del voucher para el modal.');
        };
        testImg.src = imgSrc;
    });
    
    // 1. Función para eliminar registros
    $(document).on('click', '.eliminar-registro', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        idToDelete = $(this).data('id');
        const conductor = $(this).data('conductor') || 'N/A';
        const chapa = $(this).data('chapa') || 'N/A';
        
        $('#modalConductorCustom').text(conductor);
        $('#modalChapaCustom').text(chapa);
        $('#customConfirmModal').fadeIn(300);
    });
    
    // 2. Manejar botones del modal
    $('#btnCancelarCustom').on('click', function() {
        $('#customConfirmModal').fadeOut(300);
        idToDelete = null;
    });
    
    $('#btnConfirmarCustom').on('click', function() {
        if (!idToDelete) {
            mostrarModalMejorado('error', 'Error', 'No hay ID para eliminar');
            return;
        }
        
        const $btn = $(this);
        $btn.html('<i class="fas fa-spinner fa-spin"></i> Eliminando...');
        $btn.prop('disabled', true);
        
        $.ajax({
            url: 'eliminar.php',
            type: 'POST',
            data: { id: idToDelete },
            dataType: 'json',
            success: function(response) {
                $('#customConfirmModal').fadeOut(300);
                if (response.success) {

                    // Logging adicional desde frontend
                    $.post('../../config/log_activity.php', {
                        action: 'log',
                        modulo: 'uso_combustible',
                        accion: 'eliminar_registro_interfaz',
                        detalle: `Registro eliminado desde interfaz - ID: ${idToDelete}`
                    });
                    
                    mostrarModalMejorado('success', 'Éxito', 'Registro eliminado correctamente', function() {
                        location.reload();
                    });
                } else {
                    mostrarModalMejorado('error', 'Error al eliminar', response.message || 'No se pudo eliminar el registro');
                }
            },
            error: function() {
                $('#customConfirmModal').fadeOut(300);
                mostrarModalMejorado('error', 'Error de conexión', 'No se pudo conectar con el servidor al eliminar el registro');

            },
            complete: function() {
                $btn.html('<i class="fas fa-trash"></i> Eliminar');
                $btn.prop('disabled', false);
            }
        });
    });
    
    // 3. Cerrar modal al hacer clic fuera
    $(document).on('click', '#customConfirmModal', function(e) {
        if (e.target === this) {
            $(this).fadeOut(300);
            idToDelete = null;
        }
    });
    
    // 4. FUNCIONALIDAD EXPANDIR/CONTRAER - NUEVA VERSIÓN CON SUBTABLA
    $(document).on('click', '.expand-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('🔄 Botón expandir clickeado');
        
        const $button = $(this);
        const groupId = $button.data('group');
        const $icon = $button.find('i');
        const $subRecordsContainer = $('.sub-records-container[data-group="' + groupId + '"]');
        
        console.log('📊 Grupo ID:', groupId);
        console.log('📋 Contenedor de sub-registros encontrado:', $subRecordsContainer.length);
        
        if ($subRecordsContainer.length === 0) {
            console.warn('⚠️ No se encontró contenedor de sub-registros para el grupo:', groupId);
            return;
        }
        
        try {
            // Verificar estado actual
            if ($subRecordsContainer.is(':visible')) {
                // Contraer
                $subRecordsContainer.hide();
                $icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
                $button.removeClass('expanded');
                $button.attr('title', 'Expandir registros');
                console.log('📥 Grupo contraído:', groupId);

                
                // Logging de contracción
                $.post('../../config/log_activity.php', {
                    action: 'log',
                    modulo: 'uso_combustible',
                    accion: 'contraer_grupo_interfaz',
                    detalle: `Grupo de recorridos contraído - Grupo ID: ${groupId}`
                });

            } else {
                // Expandir
                $subRecordsContainer.show();
                $icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
                $button.addClass('expanded');
                $button.attr('title', 'Contraer registros');
                console.log('📤 Grupo expandido:', groupId);

                
                // Logging de expansión
                $.post('../../config/log_activity.php', {
                    action: 'log',
                    modulo: 'uso_combustible',
                    accion: 'expandir_grupo_interfaz',
                    detalle: `Grupo de recorridos expandido - Grupo ID: ${groupId}`
                });

            }
        } catch (error) {
            console.error('❌ Error al expandir/contraer:', error);
        }
    });
    
    // 4.1. FUNCIONALIDAD CHECKBOX SUBTABLAS
    $(document).on('change', '.sub-select-all', function() {
        const groupId = $(this).data('group');
        const isChecked = $(this).prop('checked');
        const $subCheckboxes = $('.sub-records-container[data-group="' + groupId + '"] .sub-checkbox');
        
        console.log('🔄 Seleccionar todo subtabla - Grupo:', groupId, 'Checked:', isChecked);
        
        $subCheckboxes.prop('checked', isChecked);
        updateSelectionInfo();
        
        // Logging
        $.post('../../config/log_activity.php', {
            action: 'log',
            modulo: 'uso_combustible',
            accion: isChecked ? 'seleccionar_todos_subtabla' : 'deseleccionar_todos_subtabla',
            detalle: `${isChecked ? 'Seleccionados' : 'Deseleccionados'} todos los sub-registros del grupo ${groupId}`
        });
    });
    


    // Función para actualizar información de selección
    function updateSelectionInfo() {
        try {
            const selectedCount = $('.main-checkbox:checked').length;
            $('#selected-count').text(selectedCount);
            $('#selection-text').text('Has seleccionado ' + selectedCount + ' registros de combustible');
            
            if (selectedCount > 0) {
                $('#export_selected').prop('disabled', false);
                $('#selection-info').show();
            } else {
                $('#export_selected').prop('disabled', true);
                $('#export_all').prop('checked', true);
                $('#selection-info').hide();
            }
            
            console.log('✅ Selección actualizada: ' + selectedCount + ' registros');
        } catch (error) {
            console.error('❌ Error al actualizar selección:', error);
        }
    }
    
    // Función para manejar checkbox principal
    function handleMainCheckbox(element) {
        try {
            const $checkbox = $(element);
            const groupId = $checkbox.data('group');
            const isChecked = $checkbox.is(':checked');
            
            console.log('📋 Checkbox principal grupo ' + groupId + ': ' + (isChecked ? 'seleccionado' : 'deseleccionado'));
            
            updateSelectionInfo();
            
            // Log de la acción
            $.post('../../config/log_activity.php', {
                action: 'log',
                modulo: 'uso_combustible',
                accion: isChecked ? 'seleccionar_registro_principal' : 'deseleccionar_registro_principal',
                detalle: 'Registro principal grupo ' + groupId + ' ' + (isChecked ? 'seleccionado' : 'deseleccionado')
            }).fail(function() {
                console.warn('⚠️ Error al registrar log de selección');
            });
            
        } catch (error) {
            console.error('❌ Error en checkbox principal:', error);
        }
    }
    
    // Inicialización mejorada de eventos
    function initializeCheckboxEvents() {
        console.log('🔄 Inicializando eventos de checkboxes...');
        
        // Remover eventos existentes para evitar duplicados
        $(document).off('change', '.main-checkbox');
        $(document).off('change', '#select-all-header');
        $('#select-all-btn').off('click');
        $('#clear-selection-btn').off('click');
        
        // Eventos de checkboxes principales
        $(document).on('change', '.main-checkbox', function(e) {
            e.preventDefault();
            handleMainCheckbox(this);
        });
        
        // Checkbox del header
        $('#select-all-header').on('change', function() {
            try {
                const isChecked = $(this).is(':checked');
                console.log('📋 Seleccionar todos desde header: ' + isChecked);
                
                $('.main-checkbox').prop('checked', isChecked);
                updateSelectionInfo();
                
                // Logging
                $.post('../../config/log_activity.php', {
                    action: 'log',
                    modulo: 'uso_combustible',
                    accion: isChecked ? 'seleccionar_todos_header' : 'deseleccionar_todos_header',
                    detalle: (isChecked ? 'Seleccionados' : 'Deseleccionados') + ' todos los registros desde header'
                });
            } catch (error) {
                console.error('❌ Error en select-all-header:', error);
            }
        });
        
        // Botón seleccionar todos
        $('#select-all-btn').on('click', function() {
            try {
                console.log('📋 Seleccionar todos desde botón');
                $('.main-checkbox, #select-all-header').prop('checked', true);
                updateSelectionInfo();
                
                $.post('../../config/log_activity.php', {
                    action: 'log',
                    modulo: 'uso_combustible',
                    accion: 'seleccionar_todos_boton',
                    detalle: 'Seleccionados todos los registros usando botón'
                });
            } catch (error) {
                console.error('❌ Error en select-all-btn:', error);
            }
        });
        
        // Botón limpiar selección
        $('#clear-selection-btn').on('click', function() {
            try {
                console.log('📋 Limpiar selección desde botón');
                $('.main-checkbox, #select-all-header').prop('checked', false);
                updateSelectionInfo();
                
                $.post('../../config/log_activity.php', {
                    action: 'log',
                    modulo: 'uso_combustible',
                    accion: 'limpiar_seleccion_boton',
                    detalle: 'Limpiada la selección usando botón'
                });
            } catch (error) {
                console.error('❌ Error en clear-selection-btn:', error);
            }
        });
        
        console.log('✅ Eventos de checkboxes inicializados correctamente');
    }
    
    // Verificación de elementos
    function verifyElements() {
        setTimeout(function() {
            const mainCheckboxes = $('.main-checkbox').length;

            const headerCheckbox = $('#select-all-header').length;
            const selectAllBtn = $('#select-all-btn').length;
            const clearBtn = $('#clear-selection-btn').length;
            
            console.log('🔍 Verificación de elementos:');
            console.log('- Checkboxes principales:', mainCheckboxes);

            console.log('- Checkbox header:', headerCheckbox);
            console.log('- Botón seleccionar todos:', selectAllBtn);
            console.log('- Botón limpiar:', clearBtn);
            
            if (mainCheckboxes === 0) {
                console.error('❌ No se encontraron checkboxes principales');
            }
            if (headerCheckbox === 0) {
                console.error('❌ No se encontró checkbox del header');
            }
            if (selectAllBtn === 0) {
                console.error('❌ No se encontró botón seleccionar todos');
            }
            
            // Verificar que jQuery está funcionando
            try {
                $('.main-checkbox').first().prop('checked', false);
                console.log('✅ jQuery funcionando correctamente');
            } catch (error) {
                console.error('❌ Error con jQuery:', error);
            }
        }, 1000);
    }

    
    // 10. Manejar formulario de exportación
    $('#exportForm').on('submit', function(e) {
        const exportType = $('input[name="export_type"]:checked').val();
        
        if (exportType === 'selected') {
            const $checkedMainBoxes = $('.main-checkbox:checked');
            
            if ($checkedMainBoxes.length === 0) {
                e.preventDefault();
                mostrarModalMejorado('warning', 'Selección requerida', 'Por favor, selecciona al menos un registro para descargar.');

                alert('Por favor, selecciona al menos un registro para descargar.');

                return false;
            }
            
            $('#selected-ids-container').empty();
            const selectedIds = [];
            
            $checkedMainBoxes.each(function() {
                const groupId = $(this).data('group');
                const mainId = $(this).val();
                selectedIds.push(mainId);
                
                $('.sub-checkbox[data-group="' + groupId + '"]').each(function() {
                    selectedIds.push($(this).val());
                });
            });
            
            selectedIds.forEach(function(id) {
                $('#selected-ids-container').append(
                    $('<input>', {
                        type: 'hidden',
                        name: 'selected_ids[]',
                        value: id
                    })
                );
            });

            
            // Logging de exportación seleccionada
            $.post('../../config/log_activity.php', {
                action: 'log',
                modulo: 'uso_combustible',
                accion: 'exportar_seleccionados_interfaz',
                detalle: `Iniciando exportación de ${selectedIds.length} registros seleccionados desde interfaz`
            });
        } else {
            $('#selected-ids-container').empty();
            
            // Logging de exportación completa
            $.post('../../config/log_activity.php', {
                action: 'log',
                modulo: 'uso_combustible',
                accion: 'exportar_todos_interfaz',
                detalle: 'Iniciando exportación de todos los registros filtrados desde interfaz'
            });
        }
        
        return true;
    });
    

    // Inicialización principal - Asegurar que el DOM esté completamente cargado
    $(document).ready(function() {
        console.log('🚀 Inicializando sistema de checkboxes mejorado...');
        
        // Esperar un poco más para asegurar que todo esté cargado
        setTimeout(function() {
            try {
                initializeCheckboxEvents();
                updateSelectionInfo();
                verifyElements();
                
                console.log('🎉 Sistema de checkboxes inicializado completamente');
            } catch (error) {
                console.error('❌ Error en inicialización:', error);
            }
        }, 500);
    });
    
    setTimeout(function() {
        const mainRecords = $('.main-record').length;
        
        console.log('🔍 Verificación de elementos:');
        console.log('- Registros principales:', mainRecords);


    }, 1000);
    
    console.log('🎉 Inicialización completa de ver_registros.php');
    

    
    // Logging de carga de página
    $.post('../../config/log_activity.php', {
        action: 'log',
        modulo: 'uso_combustible',
        accion: 'cargar_ver_registros',
        detalle: 'Página de ver registros cargada exitosamente'
    });
});

// Función para logging de búsqueda
function logBusqueda() {
    const search = document.getElementById('search').value;
    const fechaInicio = document.getElementById('fecha_inicio').value;
    const fechaFin = document.getElementById('fecha_fin').value;
    
    $.post('../../config/log_activity.php', {
        action: 'log',
        modulo: 'uso_combustible',
        accion: 'realizar_busqueda_interfaz',
        detalle: 'Búsqueda iniciada desde interfaz - Términos: "' + search + '", Fecha inicio: ' + fechaInicio + ', Fecha fin: ' + fechaFin
    });
}

// Función mejorada para mostrar modales de mensaje con mejor diseño
function mostrarModalMejorado(tipo, titulo, mensaje, callback = null) {
    // Remover modal existente si hay uno
    const existingOverlay = document.getElementById('messageOverlay');
    if (existingOverlay) {
        existingOverlay.remove();
    }
    
    // Crear overlay con efecto blur
    var overlay = document.createElement('div');
    overlay.id = 'messageOverlay';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.6);
        backdrop-filter: blur(5px);
        z-index: 10000;
        display: flex;
        justify-content: center;
        align-items: center;
        opacity: 0;
        transition: all 0.3s ease;
    `;
    
    // Configuración según el tipo
    const config = {
        success: {
            color: '#28a745',
            bgColor: '#d4edda',
            borderColor: '#c3e6cb',
            icon: '✅',
            iconBg: '#28a745'
        },
        error: {
            color: '#dc3545',
            bgColor: '#f8d7da',
            borderColor: '#f5c6cb',
            icon: '❌',
            iconBg: '#dc3545'
        },
        warning: {
            color: '#ffc107',
            bgColor: '#fff3cd',
            borderColor: '#ffeaa7',
            icon: '⚠️',
            iconBg: '#ffc107'
        },
        info: {
            color: '#17a2b8',
            bgColor: '#d1ecf1',
            borderColor: '#bee5eb',
            icon: 'ℹ️',
            iconBg: '#17a2b8'
        }
    };
    
    const currentConfig = config[tipo] || config.info;
    
    // Crear modal con diseño mejorado
    var modal = document.createElement('div');
    modal.id = 'messageModal';
    modal.style.cssText = 
        'background: white;' +
        'border-radius: 15px;' +
        'box-shadow: 0 20px 60px rgba(0,0,0,0.3);' +
        'text-align: center;' +
        'min-width: 420px;' +
        'max-width: 500px;' +
        'font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;' +
        'overflow: hidden;' +
        'transform: scale(0.7);' +
        'transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);' +
        'border: 3px solid ' + currentConfig.borderColor + ';';
    
    modal.innerHTML = 
        '<div style="background: ' + currentConfig.bgColor + '; padding: 25px 30px; border-bottom: 1px solid ' + currentConfig.borderColor + ';">' +
            '<div style="display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">' +
                '<div style="width: 60px; height: 60px; background: ' + currentConfig.iconBg + '; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-right: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">' +
                    currentConfig.icon +
                '</div>' +
                '<h3 style="color: ' + currentConfig.color + '; margin: 0; font-size: 22px; font-weight: 600;">' + titulo + '</h3>' +
            '</div>' +
        '</div>' +
        '<div style="padding: 30px;">' +
            '<p style="margin: 0 0 30px 0; font-size: 16px; color: #333; line-height: 1.6;">' + mensaje + '</p>' +
            '<button onclick="cerrarModalMejorado(' + (callback ? 'true' : 'false') + ')" ' +
                        'style="background: linear-gradient(135deg, ' + currentConfig.color + ', ' + currentConfig.color + 'dd); color: ' + (currentConfig.color === '#ffc107' ? '#333' : 'white') + '; border: none; padding: 14px 30px; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0,0,0,0.2); text-transform: uppercase; letter-spacing: 0.5px;" ' +
                        'onmouseover="this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 6px 20px rgba(0,0,0,0.3)\';" ' +
                        'onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 4px 15px rgba(0,0,0,0.2)\';" ' +
                        'onmousedown="this.style.transform=\'translateY(0)\';">' +
                '<i class="fas fa-check" style="margin-right: 8px; color: ' + (currentConfig.color === '#ffc107' ? '#333' : 'white') + ';"></i>Aceptar' +
            '</button>' +
        '</div>';
    
    overlay.appendChild(modal);
    document.body.appendChild(overlay);
    
    // Animar entrada
    setTimeout(() => {
        overlay.style.opacity = '1';
        modal.style.transform = 'scale(1)';
    }, 10);
    
    // Agregar estilos de animación si no existen
    if (!document.getElementById('modalAnimationStyleMejorado')) {
        try {
            var style = document.createElement('style');
            style.id = 'modalAnimationStyleMejorado';
            style.type = 'text/css';
            style.textContent = `
                @keyframes modalShake {
                    0%, 100% { transform: scale(1) translateX(0); }
                    25% { transform: scale(1) translateX(-5px); }
                    75% { transform: scale(1) translateX(5px); }
                }
                @keyframes modalPulse {
                    0% { transform: scale(1); }
                    50% { transform: scale(1.05); }
                    100% { transform: scale(1); }
                }
            `;
            
            // Verificar que document.head existe antes de insertar
            if (document.head) {
                document.head.appendChild(style);
            } else {
                console.warn('⚠️ document.head no disponible, estilos no aplicados');
            }
        } catch (error) {
            console.error('❌ Error al insertar estilos CSS:', error);
        }
    }
    
    // Guardar callback
    window.modalCallback = callback;
    
    // Cerrar con ESC
    const escHandler = (e) => {
        if (e.key === 'Escape') {
            cerrarModalMejorado(false);
            document.removeEventListener('keydown', escHandler);
        }
    };
    document.addEventListener('keydown', escHandler);
    
    // Cerrar al hacer clic en el overlay
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            cerrarModalMejorado(false);
        }
    });
}

function cerrarModalMejorado(executeCallback = false) {
    const overlay = document.getElementById('messageOverlay');
    const modal = document.getElementById('messageModal');
    
    if (overlay && modal) {
        // Animar salida
        modal.style.transform = 'scale(0.7)';
        overlay.style.opacity = '0';
        
        setTimeout(() => {
            if (overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
        }, 300);
    }
    
    if (executeCallback && window.modalCallback) {
        window.modalCallback();
    }
    window.modalCallback = null;
}

// Función mejorada para confirmaciones
function mostrarConfirmacionMejorada(titulo, mensaje, onConfirm, tipo = 'warning') {
    // Remover modal existente
    const existingOverlay = document.getElementById('confirmOverlay');
    if (existingOverlay) {
        existingOverlay.remove();
    }
    
    // Crear overlay
    var overlay = document.createElement('div');
    overlay.id = 'confirmOverlay';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.6);
        backdrop-filter: blur(5px);
        z-index: 10000;
        display: flex;
        justify-content: center;
        align-items: center;
        opacity: 0;
        transition: all 0.3s ease;
    `;
    
    // Crear modal
    var modal = document.createElement('div');
    modal.id = 'confirmModal';
    modal.style.cssText = `
        background: white;
        border-radius: 15px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        text-align: center;
        min-width: 480px;
        max-width: 550px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        overflow: hidden;
        transform: scale(0.7);
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        border: 3px solid #ffeaa7;
    `;
    
    modal.innerHTML = 
        '<div style="background: #fff3cd; padding: 25px 30px; border-bottom: 1px solid #ffeaa7;">' +
            '<div style="display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">' +
                '<div style="width: 60px; height: 60px; background: #ffc107; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-right: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">' +
                    '⚠️' +
                '</div>' +
                '<h3 style="color: #856404; margin: 0; font-size: 22px; font-weight: 600;">' + titulo + '</h3>' +
            '</div>' +
        '</div>' +
        '<div style="padding: 30px;">' +
            '<p style="margin: 0 0 30px 0; font-size: 16px; color: #333; line-height: 1.6;">' + mensaje + '</p>' +
            '<div style="display: flex; gap: 15px; justify-content: center;">' +
                '<button onclick="cerrarConfirmacionMejorada(false)" ' +
                        'style="background: linear-gradient(135deg, #6c757d, #5a6268); color: white; border: none; padding: 14px 24px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0,0,0,0.2); text-transform: uppercase; letter-spacing: 0.5px;" ' +
                        'onmouseover="this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 6px 20px rgba(0,0,0,0.3)\';" ' +
                        'onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 4px 15px rgba(0,0,0,0.2)\';">' +
                    '<i class="fas fa-times" style="margin-right: 8px;"></i>Cancelar' +
                '</button>' +
                '<button onclick="cerrarConfirmacionMejorada(true)" ' +
                        'style="background: linear-gradient(135deg, #dc3545, #c82333); color: white; border: none; padding: 14px 24px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0,0,0,0.2); text-transform: uppercase; letter-spacing: 0.5px;" ' +
                        'onmouseover="this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 6px 20px rgba(0,0,0,0.3)\';" ' +
                        'onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 4px 15px rgba(0,0,0,0.2)\';">' +
                    '<i class="fas fa-check" style="margin-right: 8px;"></i>Confirmar' +
                '</button>' +
            '</div>' +
        '</div>';
    
    overlay.appendChild(modal);
    document.body.appendChild(overlay);
    
    // Animar entrada
    setTimeout(() => {
        overlay.style.opacity = '1';
        modal.style.transform = 'scale(1)';
    }, 10);
    
    // Guardar callback
    window.confirmCallback = onConfirm;
    
    // Cerrar con ESC
    const escHandler = (e) => {
        if (e.key === 'Escape') {
            cerrarConfirmacionMejorada(false);
            document.removeEventListener('keydown', escHandler);
        }
    };
    document.addEventListener('keydown', escHandler);
}

function cerrarConfirmacionMejorada(confirmed) {
    const overlay = document.getElementById('confirmOverlay');
    const modal = document.getElementById('confirmModal');
    
    if (overlay && modal) {
        // Animar salida
        modal.style.transform = 'scale(0.7)';
        overlay.style.opacity = '0';
        
        setTimeout(() => {
            if (overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
        }, 300);
    }
    
    if (confirmed && window.confirmCallback) {
        window.confirmCallback();
    }
    window.confirmCallback = null;
}

// Función para mostrar modal de confirmación personalizado (mantener compatibilidad)
function mostrarConfirmacion(titulo, mensaje, onConfirm) {
    mostrarConfirmacionMejorada(titulo, mensaje, onConfirm);
}

function cerrarConfirmacion(confirmed) {
    cerrarConfirmacionMejorada(confirmed);
}

// Función para mostrar modales de mensaje (mantener compatibilidad)
function mostrarModal(tipo, titulo, mensaje, callback = null) {
    mostrarModalMejorado(tipo, titulo, mensaje, callback);
}

function cerrarModalMensaje(executeCallback = false) {
    cerrarModalMejorado(executeCallback);
}

// Función para verificar el estado real de un registro
async function verificarEstadoRegistro(id) {
    try {
        const response = await fetch('verificar_estado.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        });
        
        // Validar respuesta HTTP
        validateFetchResponse(response, 'verificarEstadoRegistro');
        
        // Obtener texto de respuesta para debugging
        const responseText = await response.text();
        
        // Intentar parsear JSON de forma segura
        const data = parseJSONSafe(responseText, `verificarEstadoRegistro - ID: ${id}`);
        return data;
    } catch (error) {
        mostrarErrorSintaxis(error, 'ver_registros.php', 'verificarEstadoRegistro', `Error al verificar estado del registro ID: ${id}`);
        return null;
    }
}

// Función para logging del frontend
function logFrontend(message, data = null) {
    const timestamp = new Date().toISOString();
    const logData = {
        timestamp: timestamp,
        message: message,
        data: data,
        url: window.location.href,
        userAgent: navigator.userAgent
    };
    
    // Enviar log al servidor
    fetch('../../config/log_activity.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'log_frontend',
            modulo: 'uso_combustible',
            accion: 'frontend_debug',
            detalle: JSON.stringify(logData)
        })
    }).catch(err => console.error('Error enviando log:', err));
    
    // También mostrar en consola
    console.log(`[FRONTEND LOG] ${message}`, data);
}

// Función mejorada para cerrar recorrido
async function cerrarRecorrido(id) {
    try {
        logFrontend('=== INICIO CERRAR RECORRIDO ===', {id: id});
        // Verificar el estado actual en el servidor para evitar desincronización con la UI
        logFrontend('Verificando estado actual del recorrido', {id: id});
        const estadoActual = await verificarEstadoRegistro(id);
        logFrontend('Estado verificado', {id: id, estado: estadoActual});
        if (estadoActual && estadoActual.estado === 'cerrado') {
            logFrontend('ERROR: Recorrido ya cerrado', {id: id});
            mostrarModalMejorado(
                'warning',
                'Recorrido ya cerrado',
                'Este recorrido ya fue cerrado por otro usuario o en otra pestaña. La página se actualizará para reflejar el estado actual.',
                () => location.reload()
            );
            return;
        }

        const userRole = '<?php echo isset($_SESSION["user_rol"]) ? htmlspecialchars($_SESSION["user_rol"]) : "guest"; ?>';
        let mensaje;
        
        if (userRole === 'administrador') {
            mensaje = '¿Está seguro que desea cerrar este recorrido?';
        } else {
            mensaje = '¿Está seguro que desea cerrar este recorrido? Una vez cerrado, no podrá modificarlo.';
        }
        
        // Mostrar confirmación
        const confirmacion = await new Promise(resolve => {
            mostrarConfirmacionMejorada(
                'Confirmar Cierre',
                mensaje,
                () => resolve(true)
            );
            // Agregar listener para cancelar
            window.confirmCallback = () => resolve(true);
            const originalCerrar = window.cerrarConfirmacionMejorada;
            window.cerrarConfirmacionMejorada = function(confirmed) {
                originalCerrar(confirmed);
                if (!confirmed) resolve(false);
            };
        });
        
        if (!confirmacion) return;
        
        // Mostrar loading
        mostrarModalMejorado(
            'info',
            'Procesando...',
            'Cerrando recorrido, por favor espere...'
        );
        
        // Enviar solicitud de cierre
        logFrontend('Enviando petición de cierre', {id: id, action: 'cerrar'});
        const response = await fetch('cerrar_recorrido.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id: id,
                action: 'cerrar'
            })
        });
        
        // Validar respuesta HTTP
        validateFetchResponse(response, 'cerrarRecorrido');
        
        logFrontend('Respuesta recibida', {
            status: response.status,
            statusText: response.statusText,
            headers: Object.fromEntries(response.headers.entries())
        });
        
        // Obtener texto de respuesta para debugging
        const responseText = await response.text();
        
        // Intentar parsear JSON de forma segura
        const result = parseJSONSafe(responseText, `cerrarRecorrido - ID: ${id}`);
        logFrontend('JSON parseado', result);
        
        if (result.success) {
            // Logging adicional desde frontend
            $.post('../../config/log_activity.php', {
                action: 'log',
                modulo: 'uso_combustible',
                accion: 'cerrar_recorrido_interfaz',
                detalle: `Recorrido cerrado desde interfaz - ID: ${id}`
            });
            
            // Éxito confirmado
            mostrarModalMejorado(
                'success',
                'Recorrido Cerrado',
                'El recorrido se ha cerrado exitosamente.',
                () => location.reload()
            );
        } else {
            // Error en el cierre
            logFrontend('ERROR: Fallo al cerrar recorrido', {
                id: id,
                serverResponse: result,
                message: result.message
            });
            mostrarModalMejorado(
                'error',
                'Error al Cerrar',
                result.message || 'No se pudo cerrar el recorrido. Por favor, inténtelo nuevamente.',
                () => location.reload()
            );
        }
        
    } catch (error) {
        logFrontend('=== EXCEPCIÓN EN CERRAR RECORRIDO ===', {
            id: id,
            error: {
                name: error.name,
                message: error.message,
                stack: error.stack
            },
            timestamp: new Date().toISOString()
        });
        console.error('Error:', error);
        mostrarModalMejorado(
            'error',
            'Error',
            'Ocurrió un error inesperado. La página se actualizará para verificar el estado.',
            () => location.reload()
        );
    }
}

// Función para mostrar mensaje cuando el recorrido está cerrado
function mostrarMensajeRecorridoCerrado() {
    mostrarModalMejorado(
        'warning',
        'Recorrido Cerrado',
        'Este recorrido está cerrado y no se puede modificar. Si necesita realizar cambios, primero debe reabrirlo desde la columna de Estado.'
    );
}

// Función para mostrar modal de entrada de texto personalizado
function mostrarModalInput(titulo, placeholder, callback) {
    // Crear overlay
    const overlay = document.createElement('div');
    overlay.id = 'inputModalOverlay';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(5px);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: fadeIn 0.3s ease;
    `;
    
    // Crear modal
    const modal = document.createElement('div');
    modal.style.cssText = `
        background: white;
        border-radius: 15px;
        padding: 30px;
        max-width: 500px;
        width: 90%;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        transform: scale(0.9);
        animation: modalSlideIn 0.3s ease forwards;
        position: relative;
    `;
    
    modal.innerHTML = 
        '<div style="text-align: center; margin-bottom: 25px;">' +
            '<div style="' +
                'width: 60px;' +
                'height: 60px;' +
                'background: linear-gradient(135deg, #17a2b8, #138496);' +
                'border-radius: 50%;' +
                'margin: 0 auto 15px;' +
                'display: flex;' +
                'align-items: center;' +
                'justify-content: center;' +
                'color: white;' +
                'font-size: 24px;' +
            '">' +
                '<i class="fas fa-unlock-alt"></i>' +
            '</div>' +
            '<h4 style="color: #333; margin: 0; font-weight: 600;">' + titulo + '</h4>' +
        '</div>' +
        
        '<div style="margin-bottom: 25px;">' +
            '<label style="display: block; margin-bottom: 8px; color: #555; font-weight: 500;">Motivo de la reapertura:</label>' +
            '<textarea ' +
                'id="motivoInput" ' +
                'placeholder="' + placeholder + '"' +
                'style="' +
                    'width: 100%;' +
                    'min-height: 100px;' +
                    'padding: 12px;' +
                    'border: 2px solid #e9ecef;' +
                    'border-radius: 8px;' +
                    'font-size: 14px;' +
                    'resize: vertical;' +
                    'transition: border-color 0.3s ease;' +
                    'font-family: inherit;' +
                '"' +
                'maxlength="500"' +
            '></textarea>' +
            '<small style="color: #6c757d; font-size: 12px;">Máximo 500 caracteres</small>' +
        '</div>' +
        
        '<div style="display: flex; gap: 10px; justify-content: flex-end;">' +
            '<button ' +
                'onclick="cerrarModalInput(false)"' +
                'style="' +
                    'background: #6c757d;' +
                    'color: white;' +
                    'border: none;' +
                    'padding: 12px 24px;' +
                    'border-radius: 8px;' +
                    'cursor: pointer;' +
                    'font-size: 14px;' +
                    'font-weight: 500;' +
                    'transition: all 0.3s ease;' +
                '"' +
                'onmouseover="this.style.background=\'#5a6268\'"' +
                'onmouseout="this.style.background=\'#6c757d\'"' +
            '>' +
                'Cancelar' +
            '</button>' +
            '<button ' +
                'onclick="confirmarInput()"' +
                'style="' +
                    'background: linear-gradient(135deg, #17a2b8, #138496);' +
                    'color: white;' +
                    'border: none;' +
                    'padding: 12px 24px;' +
                    'border-radius: 8px;' +
                    'cursor: pointer;' +
                    'font-size: 14px;' +
                    'font-weight: 500;' +
                    'transition: all 0.3s ease;' +
                    'box-shadow: 0 4px 15px rgba(23, 162, 184, 0.3);' +
                '"' +
                'onmouseover="this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 6px 20px rgba(23, 162, 184, 0.4)\'"' +
                'onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 4px 15px rgba(23, 162, 184, 0.3)\'"' +
            '>' +
                'Confirmar' +
            '</button>' +
        '</div>';
    
    overlay.appendChild(modal);
    document.body.appendChild(overlay);
    
    // Enfocar el textarea
    setTimeout(() => {
        document.getElementById('motivoInput').focus();
    }, 100);
    
    // Manejar ESC para cerrar
    function handleEsc(e) {
        if (e.key === 'Escape') {
            cerrarModalInput(false);
        }
    }
    document.addEventListener('keydown', handleEsc);
    
    // Cerrar al hacer click fuera
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            cerrarModalInput(false);
        }
    });
    
    // Funciones globales para el modal
    window.cerrarModalInput = function(ejecutarCallback = false) {
        const overlay = document.getElementById('inputModalOverlay');
        if (overlay) {
            overlay.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => {
                if (overlay.parentNode) {
                    overlay.parentNode.removeChild(overlay);
                }
            }, 300);
        }
        document.removeEventListener('keydown', handleEsc);
        
        if (ejecutarCallback && callback) {
            callback(null);
        }
    };
    
    window.confirmarInput = function() {
        const input = document.getElementById('motivoInput');
        const valor = input.value.trim();
        
        if (valor === '') {
            input.style.borderColor = '#dc3545';
            input.focus();
            return;
        }
        
        cerrarModalInput(false);
        if (callback) {
            callback(valor);
        }
    };
    
    // Manejar Enter en el textarea (Ctrl+Enter para confirmar)
    document.getElementById('motivoInput').addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && e.ctrlKey) {
            confirmarInput();
        }
    });
    
    // Limpiar borde rojo cuando el usuario empiece a escribir
    document.getElementById('motivoInput').addEventListener('input', function(e) {
        if (e.target.value.trim() !== '') {
            e.target.style.borderColor = '#e9ecef';
        }
    });
    
    // Contador de caracteres
    document.getElementById('motivoInput').addEventListener('input', function(e) {
        const maxLength = 500;
        const currentLength = e.target.value.length;
        const remaining = maxLength - currentLength;
        
        // Buscar o crear el elemento contador
        let counter = document.getElementById('charCounter');
        if (!counter) {
            counter = document.createElement('small');
            counter.id = 'charCounter';
            counter.style.cssText = 'color: #6c757d; font-size: 12px; float: right;';
            e.target.parentNode.appendChild(counter);
        }
        
        counter.textContent = `${currentLength}/${maxLength} caracteres`;
        
        // Cambiar color si se acerca al límite
        if (remaining < 50) {
            counter.style.color = '#dc3545';
        } else if (remaining < 100) {
            counter.style.color = '#ffc107';
        } else {
            counter.style.color = '#6c757d';
        }
    });
}

// Función mejorada para reabrir recorrido
async function reabrirRecorrido(id) {
    try {
        logFrontend('=== INICIO REABRIR RECORRIDO ===', {id: id});
        // Verificar el estado actual en el servidor para evitar desincronización con la UI
        const estadoActual = await verificarEstadoRegistro(id);
        if (estadoActual && estadoActual.estado === 'abierto') {
            mostrarModalMejorado(
                'warning',
                'Recorrido ya abierto',
                'Este recorrido ya fue reabierto por otro usuario o en otra pestaña. La página se actualizará para reflejar el estado actual.',
                () => location.reload()
            );
            return;
        }

        // Mostrar confirmación
        const confirmacion = await new Promise(resolve => {
            mostrarConfirmacionMejorada(
                'Confirmar Reapertura',
                '¿Está seguro que desea reabrir este recorrido? Deberá proporcionar un motivo.',
                () => resolve(true)
            );
            // Agregar listener para cancelar
            window.confirmCallback = () => resolve(true);
            const originalCerrar = window.cerrarConfirmacionMejorada;
            window.cerrarConfirmacionMejorada = function(confirmed) {
                originalCerrar(confirmed);
                if (!confirmed) resolve(false);
            };
        });
        
        if (!confirmacion) return;
        
        // Solicitar motivo
        const motivo = await new Promise(resolve => {
            mostrarModalInput(
                'Reabrir Recorrido',
                'Describa el motivo por el cual necesita reabrir este recorrido...',
                (valor) => resolve(valor)
            );
        });
        
        if (!motivo || motivo.trim() === '') {
            mostrarModalMejorado(
                'warning',
                'Motivo Requerido',
                'Debe proporcionar un motivo para la reapertura.'
            );
            return;
        }
        
        // Mostrar loading
        mostrarModalMejorado(
            'info',
            'Procesando...',
            'Reabriendo recorrido, por favor espere...'
        );
        
        // Enviar solicitud de reapertura
        const response = await fetch('cerrar_recorrido.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id: id,
                action: 'reabrir',
                motivo: motivo
            })
        });
        
        // Validar respuesta HTTP
        validateFetchResponse(response, 'reabrirRecorrido');
        
        // Obtener texto de respuesta para debugging
        const responseText = await response.text();
        
        // Intentar parsear JSON de forma segura
        const result = parseJSONSafe(responseText, `reabrirRecorrido - ID: ${id}, Motivo: ${motivo}`);
        
        if (result.success) {
            // Logging adicional desde frontend
            $.post('../../config/log_activity.php', {
                action: 'log',
                modulo: 'uso_combustible',
                accion: 'reabrir_recorrido_interfaz',
                detalle: 'Recorrido reabierto desde interfaz - ID: ' + id + ', Motivo: ' + motivo
            });
            
            // Éxito confirmado
            mostrarModalMejorado(
                'success',
                'Recorrido Reabierto',
                'El recorrido se ha reabierto exitosamente.',
                () => location.reload()
            );
        } else {
            // Error en la reapertura
            logFrontend('ERROR: Fallo al reabrir recorrido', {
                id: id,
                serverResponse: result,
                message: result.message
            });
            mostrarModalMejorado(
                'error',
                'Error al Reabrir',
                result.message || 'No se pudo reabrir el recorrido. Por favor, inténtelo nuevamente.',
                () => location.reload()
            );
        }
        
    } catch (error) {
        logFrontend('=== EXCEPCIÓN EN REABRIR RECORRIDO ===', {
            id: id,
            error: {
                name: error.name,
                message: error.message,
                stack: error.stack
            },
            timestamp: new Date().toISOString()
        });
        console.error('Error:', error);
        mostrarModalMejorado(
            'error',
            'Error',
            'Ocurrió un error inesperado. La página se actualizará para verificar el estado.',
            () => location.reload()
        );
    }
}

</script>
            </body>
            </html>
