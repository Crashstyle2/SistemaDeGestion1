<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

include_once '../../config/database.php';
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
        
        // Ordenamiento por fecha_registro y ID de recorrido (IGUAL QUE EN LA WEB)
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
            
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        }
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
    .sub-record {
        display: none !important;
    }
    
    .sub-record td {
        padding-left: 15px;
        font-size: 0.95em;
    }
    .sub-record.show {
        display: table-row !important;
    }
    .expand-btn {
        background: none;
        border: none;
        color: #007bff;
        cursor: pointer;
        padding: 2px 5px;
        margin-right: 5px;
        font-size: 14px;
        transition: all 0.2s ease;
    }
    .expand-btn:hover {
        color: #0056b3;
        background: #f8f9fa;
        border-radius: 3px;
        text-decoration: none;
    }
    .expand-btn:focus {
        outline: none;
        box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
    }
    .expand-btn.expanded {
        color: #dc3545;
    }
    .expand-btn.expanded i {
        transform: rotate(90deg);
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
        width: 40px;
        text-align: center;
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
    
    .sub-record {
        background-color: #f8f9fa !important;
        border-left: 3px solid #28a745;
    }
    
    .sub-record.no-secuencial {
        border-left-color: #ffc107;
    }
    
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
                                            <input type="checkbox" id="select-all-header" title="Seleccionar/Deseleccionar todos">
                                        </th>
                                        <th>Fecha</th>
                                        <th>Técnico</th>
                                        <th>Tipo Vehículo</th>
                                        <th>Conductor</th>
                                        <th>Chapa</th>
                                        <th>Nº Voucher</th>
                                        <th>Nº Tarjeta</th>
                                        <th>Litros</th>
                                        <th>Origen</th>
                                        <th>Destino</th>
                                        <th>Documento</th>
                                        <th>Foto Voucher</th>
                                        <th>Estado</th>
                                        <?php if ($puedeModificar): ?>
                                        <th>Editar</th>
                                        <?php endif; ?>
                                        <?php if ($_SESSION['user_rol'] === 'administrador'): ?>
                                        <th>Eliminar</th>
                                        <?php endif; ?>
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
                    
                    $groupIndex = 0;
                    foreach ($groupedRecords as $groupKey => $group): 
                        $isMultiple = count($group) > 1;
                        $mainRecord = $group[0]; // Ahora es el PRIMER recorrido registrado cronológicamente
                        $groupIndex++;
                    ?>
                                    <!-- Registro principal -->
                                    <tr class="main-record" data-group="<?php echo $groupIndex; ?>">
                                        <td class="text-center">
                                            <input type="checkbox" class="record-checkbox main-checkbox" 
                                                   value="<?php echo $mainRecord['id']; ?>" 
                                                   data-group="<?php echo $groupIndex; ?>"
                                                   data-main-id="<?php echo $mainRecord['id']; ?>">
                                        </td>
                                        <td>
                                            <?php if ($isMultiple): ?>
                                <button class="expand-btn" data-group="<?php echo $groupIndex; ?>" title="Expandir registros" type="button">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            <?php endif; ?>
                                            <?php echo date('d/m/Y H:i', strtotime($mainRecord['fecha_carga'] . ' ' . $mainRecord['hora_carga'])); ?>
                                            <?php if ($isMultiple): ?>
                                                <span class="multiple-indicator"><?php echo count($group); ?> recorridos</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($mainRecord['nombre_usuario'] ?? ''); ?></td>
                                        <td><?php echo ucfirst(str_replace('_', ' ', $mainRecord['tipo_vehiculo'] ?? '')); ?></td>
                                        <td><?php echo htmlspecialchars($mainRecord['nombre_conductor'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($mainRecord['chapa'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($mainRecord['numero_baucher'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($mainRecord['tarjeta'] ?? ''); ?></td>
                                        <td><?php echo number_format($mainRecord['litros_cargados'] ?? 0, 2); ?></td>
                                        <td>
                            <span class="recorrido-numero">1°</span>
                            <span class="origen-destino"><?php echo htmlspecialchars($mainRecord['origen'] ?? ''); ?></span>
                            <?php if ($isMultiple): ?>
                                <span class="secuencia-indicator">→ Secuencia de <?php echo count($group); ?> recorridos</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="recorrido-numero">1°</span>
                            <span class="origen-destino"><?php echo htmlspecialchars($mainRecord['destino'] ?? ''); ?></span>
                            <?php if ($isMultiple): ?>
                                <button class="btn btn-sm btn-outline-info expand-btn" data-group="<?php echo $groupIndex; ?>" title="Ver secuencia completa">
                                    <i class="fas fa-route"></i> Ver ruta
                                </button>
                            <?php endif; ?>
                        </td>
                                        <td><?php echo htmlspecialchars($mainRecord['documento'] ?? ''); ?></td>
                                        <td class="text-center">
                                            <?php if (!empty($mainRecord['foto_voucher_ruta']) || !empty($mainRecord['foto_voucher'])): ?>
                                                <button type="button" class="btn btn-sm btn-info ver-foto" 
                                                        data-foto="<?php echo $mainRecord['foto_voucher']; ?>"
                                                        data-foto-ruta="<?php echo $mainRecord['foto_voucher_ruta']; ?>"
                                                        title="Ver foto del voucher">
                                                    <i class="fas fa-eye"></i> Ver
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">Sin foto</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($mainRecord['estado_recorrido'] === 'abierto'): ?>
                                                <span class="badge badge-success">Abierto</span>
                                                <?php if (in_array($rol, ['tecnico', 'supervisor', 'administrador']) && $mainRecord['user_id'] == $_SESSION['user_id']): ?>
                                                    <button class="btn btn-warning btn-sm" onclick="cerrarRecorrido(<?php echo $mainRecord['id']; ?>)" title="Cerrar Recorrido">
                                                        <i class="fas fa-lock"></i> Cerrar
                                                    </button>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Cerrado</span>
                                                <?php if ($mainRecord['fecha_cierre']): ?>
                                                    <small class="text-muted d-block">Cerrado: <?php echo date('d/m/Y H:i', strtotime($mainRecord['fecha_cierre'])); ?></small>
                                                    <small class="text-muted d-block">Por: <?php echo htmlspecialchars($mainRecord['nombre_cerrador']); ?></small>
                                                <?php endif; ?>
                                                <?php if ($rol === 'administrador'): ?>
                                                    <button class="btn btn-info btn-sm" onclick="reabrirRecorrido(<?php echo $mainRecord['id']; ?>)" title="Reabrir Recorrido">
                                                        <i class="fas fa-unlock"></i> Reabrir
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <?php if ($puedeModificar): ?>
                        <td class="text-center">
                            <?php if ($mainRecord['estado_recorrido'] === 'cerrado'): ?>
                                <button class="btn btn-sm btn-secondary" 
                                        onclick="mostrarMensajeRecorridoCerrado()" 
                                        title="Recorrido cerrado - No se puede editar">
                                    <i class="fas fa-lock"></i>
                                </button>
                            <?php else: ?>
                                <a href="editar_registro.php?id=<?php echo $mainRecord['id']; ?>" 
                                   class="btn btn-sm btn-warning" 
                                   title="Editar registro">
                                    <i class="fas fa-edit"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                                        <?php if ($_SESSION['user_rol'] === 'administrador'): ?>
                                        <td class="text-center">
                                            <button class="btn btn-danger btn-sm eliminar-registro" 
                                                    data-id="<?php echo $mainRecord['id']; ?>"
                                                    data-conductor="<?php echo htmlspecialchars($mainRecord['nombre_conductor'] ?? ''); ?>"
                                                    data-chapa="<?php echo htmlspecialchars($mainRecord['chapa'] ?? ''); ?>"
                                                    title="Eliminar registro">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                    
                                    <?php if ($isMultiple): ?>
                                        <?php for ($i = 1; $i < count($group); $i++): 
                                            $subRecord = $group[$i];
                                            $recorridoNumero = $i + 1;
                                            $origenAnterior = $group[$i-1]['destino'];
                                            $origenActual = $subRecord['origen'];
                                            $esSecuencial = ($origenAnterior === $origenActual);
                                        ?>
                                        <!-- Sub-registros (aparecen DESPUÉS del principal) -->
                                        <tr class="sub-record" data-group="<?php echo $groupIndex; ?>" style="display: none;">
                                            <td class="text-center">
                                                <input type="checkbox" class="record-checkbox sub-checkbox" 
                                                       value="<?php echo $subRecord['id']; ?>" 
                                                       data-group="<?php echo $groupIndex; ?>"
                                                       data-main-id="<?php echo $mainRecord['id']; ?>">
                                            </td>
                                            <td>
                                                <span class="recorrido-numero <?php echo $esSecuencial ? 'secuencial' : 'no-secuencial'; ?>">
                                                    <?php echo $recorridoNumero; ?>°
                                                </span>
                                                <?php echo date('d/m/Y H:i', strtotime($subRecord['fecha_carga'] . ' ' . $subRecord['hora_carga'])); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($subRecord['nombre_usuario'] ?? ''); ?></td>
                                            <td><?php echo ucfirst(str_replace('_', ' ', $subRecord['tipo_vehiculo'] ?? '')); ?></td>
                                            <td><?php echo htmlspecialchars($subRecord['nombre_conductor'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($subRecord['chapa'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($subRecord['numero_baucher'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($subRecord['tarjeta'] ?? ''); ?></td>
                                            <td><?php echo number_format($subRecord['litros_cargados'] ?? 0, 2); ?></td>
                                            <td>
                                                <span class="recorrido-numero <?php echo $esSecuencial ? 'secuencial' : 'no-secuencial'; ?>">
                                                    <?php echo $recorridoNumero; ?>°
                                                </span>
                                                <span class="origen-destino"><?php echo htmlspecialchars($subRecord['origen'] ?? ''); ?></span>
                                                <?php if (!$esSecuencial): ?>
                                                    <i class="fas fa-exclamation-triangle text-warning" title="No sigue secuencia del recorrido anterior"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="recorrido-numero <?php echo $esSecuencial ? 'secuencial' : 'no-secuencial'; ?>">
                                                    <?php echo $recorridoNumero; ?>°
                                                </span>
                                                <span class="origen-destino"><?php echo htmlspecialchars($subRecord['destino'] ?? ''); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($subRecord['documento'] ?? ''); ?></td>
                                            <td class="text-center">
                                                <?php if (!empty($subRecord['foto_voucher'])): ?>
                                                    <button type="button" class="btn btn-sm btn-info ver-foto" 
                                                            data-foto="<?php echo $subRecord['foto_voucher']; ?>"
                                                            title="Ver foto del voucher">
                                                        <i class="fas fa-eye"></i> Ver
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">Sin foto</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($subRecord['estado_recorrido'] === 'abierto'): ?>
                                                <span class="badge badge-success">Abierto</span>
                                                <?php if (in_array($rol, ['tecnico', 'supervisor', 'administrador']) && $subRecord['user_id'] == $_SESSION['user_id']): ?>
                                                    <button class="btn btn-warning btn-sm" onclick="cerrarRecorrido(<?php echo $subRecord['id']; ?>)" title="Cerrar Recorrido">
                                                        <i class="fas fa-lock"></i> Cerrar
                                                    </button>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Cerrado</span>
                                                <?php if ($subRecord['fecha_cierre']): ?>
                                                    <small class="text-muted d-block">Cerrado: <?php echo date('d/m/Y H:i', strtotime($subRecord['fecha_cierre'])); ?></small>
                                                    <small class="text-muted d-block">Por: <?php echo htmlspecialchars($subRecord['nombre_cerrador']); ?></small>
                                                <?php endif; ?>
                                                <?php if ($rol === 'administrador'): ?>
                                                    <button class="btn btn-info btn-sm" onclick="reabrirRecorrido(<?php echo $subRecord['id']; ?>)" title="Reabrir Recorrido">
                                                        <i class="fas fa-unlock"></i> Reabrir
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            </td>
                                            <?php if (in_array($_SESSION['user_rol'], ['administrador', 'tecnico', 'supervisor'])): ?>
                                            <td class="text-center">
                                                <?php if ($subRecord['estado_recorrido'] === 'cerrado'): ?>
                                                    <button class="btn btn-sm btn-secondary" 
                                                            onclick="mostrarMensajeRecorridoCerrado()" 
                                                            title="Recorrido cerrado - No se puede editar">
                                                        <i class="fas fa-lock"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <a href="editar_registro.php?id=<?php echo $subRecord['id']; ?>" 
                                                       class="btn btn-warning btn-sm" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                            <?php elseif ($_SESSION['user_rol'] === 'administrativo'): ?>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-secondary" 
                                                        onclick="mostrarMensajeRecorridoCerrado()" 
                                                        title="Solo lectura - No se puede editar">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                            <?php endif; ?>
                                            <?php if ($_SESSION['user_rol'] === 'administrador'): ?>
                                            <td class="text-center">
                                                <button class="btn btn-danger btn-sm eliminar-registro" 
                                                        data-id="<?php echo $subRecord['id']; ?>"
                                                        data-conductor="<?php echo htmlspecialchars($subRecord['nombre_conductor'] ?? ''); ?>"
                                                        data-chapa="<?php echo htmlspecialchars($subRecord['chapa'] ?? ''); ?>"
                                                        title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                            <?php endif; ?>
                                        </tr>
                                        <?php endfor; ?>
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
    /* Modal personalizado para fotos */
    .foto-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 10000;
        background-color: rgba(0, 0, 0, 0.8);
    }

    .foto-modal-overlay {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        padding: 20px;
    }

    .foto-modal-content {
        background: white;
        border-radius: 8px;
        max-width: 90vw;
        max-height: 90vh;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    }

    .foto-modal-header {
        background: #007bff;
        color: white;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .foto-modal-header h5 {
        margin: 0;
        font-size: 1.2rem;
    }

    .foto-modal-close {
        background: none;
        border: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .foto-modal-close:hover {
        background-color: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
    }

    .foto-modal-body {
        padding: 20px;
        text-align: center;
        max-height: 70vh;
        overflow: auto;
    }

    .foto-modal-img {
        max-width: 100%;
        max-height: 60vh;
        height: auto;
        border-radius: 4px;
    }

    .foto-modal-footer {
        padding: 15px 20px;
        background: #f8f9fa;
        text-align: right;
    }
    </style>

    <script>
// Esperar a que jQuery esté completamente cargado
jQuery(document).ready(function($) {
    'use strict';
    
    console.log('🚀 Iniciando ver_registros.php');
    
    // Variables globales
    let idToDelete = null;
    
    // Manejar clic en botón "Ver foto" - MODAL PERSONALIZADO
    $(document).on('click', '.ver-foto', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const fotoRuta = $(this).data('foto-ruta');
        const fotoBase64 = $(this).data('foto');
        
        let imgSrc;
        if (fotoRuta) {
            // Nueva implementación: mostrar desde archivo
            imgSrc = '../../img/uso_combustible/vouchers/' + fotoRuta;
        } else if (fotoBase64) {
            // Compatibilidad: mostrar Base64 existente
            imgSrc = 'data:image/jpeg;base64,' + fotoBase64;
        } else {
            mostrarModalMejorado('warning', 'Sin foto', 'No hay foto disponible para este voucher');
            return;
        }
        
        $('#fotoVoucherImg').attr('src', imgSrc);
        $('#fotoVoucherModal').fadeIn(300);
        $('body').css('overflow', 'hidden'); // Prevenir scroll
    });
    
    // Función para cerrar el modal
    function cerrarFotoModal() {
        $('#fotoVoucherModal').fadeOut(300);
        $('#fotoVoucherImg').attr('src', '');
        $('body').css('overflow', 'auto'); // Restaurar scroll
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
    
    // 4. FUNCIONALIDAD EXPANDIR/CONTRAER - NUEVA VERSIÓN
    $(document).on('click', '.expand-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('🔄 Botón expandir clickeado');
        
        const $button = $(this);
        const groupId = $button.data('group');
        const $icon = $button.find('i');
        const $subRecords = $('.sub-record[data-group="' + groupId + '"]');
        
        console.log('📊 Grupo ID:', groupId);
        console.log('📋 Sub-registros encontrados:', $subRecords.length);
        
        if ($subRecords.length === 0) {
            console.warn('⚠️ No se encontraron sub-registros para el grupo:', groupId);
            return;
        }
        
        try {
            // Verificar estado actual usando la clase 'show'
            if ($subRecords.hasClass('show')) {
                // Contraer
                $subRecords.removeClass('show').hide();
                $icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
                $button.removeClass('expanded');
                $button.attr('title', 'Expandir registros');
                console.log('📥 Grupo contraído:', groupId);
            } else {
                // Expandir
                $subRecords.addClass('show').show();
                $icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
                $button.addClass('expanded');
                $button.attr('title', 'Contraer registros');
                console.log('📤 Grupo expandido:', groupId);
            }
        } catch (error) {
            console.error('❌ Error al expandir/contraer:', error);
        }
    });
    
    // 5. Función para actualizar información de selección
    function updateSelectionInfo() {
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
    }
    
    // 6. Manejar checkboxes principales
    $(document).on('change', '.main-checkbox', function() {
        const groupId = $(this).data('group');
        const isChecked = $(this).is(':checked');
        $('.sub-checkbox[data-group="' + groupId + '"]').prop('checked', isChecked);
        updateSelectionInfo();
    });
    
    // 7. Manejar checkboxes secundarios
    $(document).on('change', '.sub-checkbox', function() {
        const groupId = $(this).data('group');
        const $mainCheckbox = $('.main-checkbox[data-group="' + groupId + '"]');
        const $subCheckboxes = $('.sub-checkbox[data-group="' + groupId + '"]');
        const $checkedSubs = $('.sub-checkbox[data-group="' + groupId + '"]:checked');
        
        if ($checkedSubs.length === $subCheckboxes.length && $subCheckboxes.length > 0) {
            $mainCheckbox.prop('checked', true);
        } else {
            $mainCheckbox.prop('checked', false);
        }
        updateSelectionInfo();
    });
    
    // 8. Checkbox del header
    $('#select-all-header').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.main-checkbox, .sub-checkbox').prop('checked', isChecked);
        updateSelectionInfo();
    });
    
    // 9. Botones de selección
    $('#select-all-btn').on('click', function() {
        $('.main-checkbox, .sub-checkbox, #select-all-header').prop('checked', true);
        updateSelectionInfo();
    });
    
    $('#clear-selection-btn').on('click', function() {
        $('.main-checkbox, .sub-checkbox, #select-all-header').prop('checked', false);
        updateSelectionInfo();
    });
    
    // 10. Manejar formulario de exportación
    $('#exportForm').on('submit', function(e) {
        const exportType = $('input[name="export_type"]:checked').val();
        
        if (exportType === 'selected') {
            const $checkedMainBoxes = $('.main-checkbox:checked');
            
            if ($checkedMainBoxes.length === 0) {
                e.preventDefault();
                mostrarModalMejorado('warning', 'Selección requerida', 'Por favor, selecciona al menos un registro para descargar.');
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
        } else {
            $('#selected-ids-container').empty();
        }
        
        return true;
    });
    
    // 11. Inicializar estado
    updateSelectionInfo();
    
    // 12. Verificación de elementos al cargar
    setTimeout(function() {
        const expandButtons = $('.expand-btn').length;
        const subRecords = $('.sub-record').length;
        const mainRecords = $('.main-record').length;
        
        console.log('🔍 Verificación de elementos:');
        console.log('- Registros principales:', mainRecords);
        console.log('- Botones expandir:', expandButtons);
        console.log('- Sub-registros:', subRecords);
        
        if (expandButtons === 0) {
            console.warn('⚠️ No se encontraron botones de expandir');
        } else {
            console.log('✅ Botones de expandir encontrados correctamente');
        }
        
        if (subRecords === 0) {
            console.warn('⚠️ No se encontraron sub-registros');
        } else {
            console.log('✅ Sub-registros encontrados correctamente');
        }
        
        // Verificar que los data-group coincidan
        $('.expand-btn').each(function() {
            const groupId = $(this).data('group');
            const subCount = $('.sub-record[data-group="' + groupId + '"]').length;
            console.log('📊 Grupo ' + groupId + ': ' + subCount + ' sub-registros');
        });
    }, 500);
    
    console.log('🎉 Inicialización completa de ver_registros.php');
});

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
    modal.style.cssText = `
        background: white;
        border-radius: 15px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        text-align: center;
        min-width: 420px;
        max-width: 500px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        overflow: hidden;
        transform: scale(0.7);
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        border: 3px solid ${currentConfig.borderColor};
    `;
    
    modal.innerHTML = `
        <div style='background: ${currentConfig.bgColor}; padding: 25px 30px; border-bottom: 1px solid ${currentConfig.borderColor};'>
            <div style='display: flex; align-items: center; justify-content: center; margin-bottom: 15px;'>
                <div style='width: 60px; height: 60px; background: ${currentConfig.iconBg}; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-right: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);'>
                    ${currentConfig.icon}
                </div>
                <h3 style='color: ${currentConfig.color}; margin: 0; font-size: 22px; font-weight: 600;'>${titulo}</h3>
            </div>
        </div>
        <div style='padding: 30px;'>
            <p style='margin: 0 0 30px 0; font-size: 16px; color: #333; line-height: 1.6;'>${mensaje}</p>
            <button onclick='cerrarModalMejorado(${callback ? 'true' : 'false'})' 
                        style='background: linear-gradient(135deg, ${currentConfig.color}, ${currentConfig.color}dd); color: ${currentConfig.color === '#ffc107' ? '#333' : 'white'}; border: none; padding: 14px 30px; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0,0,0,0.2); text-transform: uppercase; letter-spacing: 0.5px;'
                        onmouseover='this.style.transform="translateY(-2px)"; this.style.boxShadow="0 6px 20px rgba(0,0,0,0.3)";'
                        onmouseout='this.style.transform="translateY(0)"; this.style.boxShadow="0 4px 15px rgba(0,0,0,0.2)";'
                        onmousedown='this.style.transform="translateY(0)";'>
                <i class="fas fa-check" style="margin-right: 8px; color: ${currentConfig.color === '#ffc107' ? '#333' : 'white'};"></i>Aceptar
            </button>
        </div>
    `;
    
    overlay.appendChild(modal);
    document.body.appendChild(overlay);
    
    // Animar entrada
    setTimeout(() => {
        overlay.style.opacity = '1';
        modal.style.transform = 'scale(1)';
    }, 10);
    
    // Agregar estilos de animación si no existen
    if (!document.getElementById('modalAnimationStyleMejorado')) {
        var style = document.createElement('style');
        style.id = 'modalAnimationStyleMejorado';
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
        document.head.appendChild(style);
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
    
    modal.innerHTML = `
        <div style='background: #fff3cd; padding: 25px 30px; border-bottom: 1px solid #ffeaa7;'>
            <div style='display: flex; align-items: center; justify-content: center; margin-bottom: 15px;'>
                <div style='width: 60px; height: 60px; background: #ffc107; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-right: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);'>
                    ⚠️
                </div>
                <h3 style='color: #856404; margin: 0; font-size: 22px; font-weight: 600;'>${titulo}</h3>
            </div>
        </div>
        <div style='padding: 30px;'>
            <p style='margin: 0 0 30px 0; font-size: 16px; color: #333; line-height: 1.6;'>${mensaje}</p>
            <div style='display: flex; gap: 15px; justify-content: center;'>
                <button onclick='cerrarConfirmacionMejorada(false)' 
                        style='background: linear-gradient(135deg, #6c757d, #5a6268); color: white; border: none; padding: 14px 24px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0,0,0,0.2); text-transform: uppercase; letter-spacing: 0.5px;'
                        onmouseover='this.style.transform="translateY(-2px)"; this.style.boxShadow="0 6px 20px rgba(0,0,0,0.3)";'
                        onmouseout='this.style.transform="translateY(0)"; this.style.boxShadow="0 4px 15px rgba(0,0,0,0.2)";'>
                    <i class="fas fa-times" style="margin-right: 8px;"></i>Cancelar
                </button>
                <button onclick='cerrarConfirmacionMejorada(true)' 
                        style='background: linear-gradient(135deg, #dc3545, #c82333); color: white; border: none; padding: 14px 24px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0,0,0,0.2); text-transform: uppercase; letter-spacing: 0.5px;'
                        onmouseover='this.style.transform="translateY(-2px)"; this.style.boxShadow="0 6px 20px rgba(0,0,0,0.3)";'
                        onmouseout='this.style.transform="translateY(0)"; this.style.boxShadow="0 4px 15px rgba(0,0,0,0.2)";'>
                    <i class="fas fa-check" style="margin-right: 8px;"></i>Confirmar
                </button>
            </div>
        </div>
    `;
    
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
        
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error verificando estado:', error);
        return null;
    }
}

// Función mejorada para cerrar recorrido
async function cerrarRecorrido(id) {
    try {
        const userRole = '<?php echo $_SESSION['user_rol']; ?>';
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
        
        // Enviar solicitud de cierre (ignoramos la respuesta)
        fetch('cerrar_recorrido.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id: id,
                action: 'cerrar'
            })
        }).catch(() => {}); // Ignoramos errores de respuesta
        
        // Esperar un momento para que se procese
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        // Verificar el estado real del registro
        const estadoActual = await verificarEstadoRegistro(id);
        
        if (estadoActual && estadoActual.estado === 'cerrado') {
            // Éxito confirmado
            mostrarModalMejorado(
                'success',
                'Recorrido Cerrado',
                'El recorrido se ha cerrado exitosamente.',
                () => location.reload()
            );
        } else {
            // No se pudo confirmar el cierre
            mostrarModalMejorado(
                'error',
                'Error al Cerrar',
                'No se pudo cerrar el recorrido. Por favor, inténtelo nuevamente.',
                () => location.reload()
            );
        }
        
    } catch (error) {
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
    
    modal.innerHTML = `
        <div style="text-align: center; margin-bottom: 25px;">
            <div style="
                width: 60px;
                height: 60px;
                background: linear-gradient(135deg, #17a2b8, #138496);
                border-radius: 50%;
                margin: 0 auto 15px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 24px;
            ">
                <i class="fas fa-unlock-alt"></i>
            </div>
            <h4 style="color: #333; margin: 0; font-weight: 600;">${titulo}</h4>
        </div>
        
        <div style="margin-bottom: 25px;">
            <label style="display: block; margin-bottom: 8px; color: #555; font-weight: 500;">Motivo de la reapertura:</label>
            <textarea 
                id="motivoInput" 
                placeholder="${placeholder}"
                style="
                    width: 100%;
                    min-height: 100px;
                    padding: 12px;
                    border: 2px solid #e9ecef;
                    border-radius: 8px;
                    font-size: 14px;
                    resize: vertical;
                    transition: border-color 0.3s ease;
                    font-family: inherit;
                "
                maxlength="500"
            ></textarea>
            <small style="color: #6c757d; font-size: 12px;">Máximo 500 caracteres</small>
        </div>
        
        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <button 
                onclick="cerrarModalInput(false)"
                style="
                    background: #6c757d;
                    color: white;
                    border: none;
                    padding: 12px 24px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 14px;
                    font-weight: 500;
                    transition: all 0.3s ease;
                "
                onmouseover="this.style.background='#5a6268'"
                onmouseout="this.style.background='#6c757d'"
            >
                Cancelar
            </button>
            <button 
                onclick="confirmarInput()"
                style="
                    background: linear-gradient(135deg, #17a2b8, #138496);
                    color: white;
                    border: none;
                    padding: 12px 24px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 14px;
                    font-weight: 500;
                    transition: all 0.3s ease;
                    box-shadow: 0 4px 15px rgba(23, 162, 184, 0.3);
                "
                onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(23, 162, 184, 0.4)'"
                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(23, 162, 184, 0.3)'"
            >
                Confirmar
            </button>
        </div>
    `;
    
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
}

// Función mejorada para reabrir recorrido
async function reabrirRecorrido(id) {
    try {
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
        
        // Enviar solicitud de reapertura (ignoramos la respuesta)
        fetch('cerrar_recorrido.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id: id,
                action: 'reabrir',
                motivo: motivo
            })
        }).catch(() => {}); // Ignoramos errores de respuesta
        
        // Esperar un momento para que se procese
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        // Verificar el estado real del registro
        const estadoActual = await verificarEstadoRegistro(id);
        
        if (estadoActual && estadoActual.estado === 'abierto') {
            // Éxito confirmado
            mostrarModalMejorado(
                'success',
                'Recorrido Reabierto',
                'El recorrido se ha reabierto exitosamente.',
                () => location.reload()
            );
        } else {
            // No se pudo confirmar la reapertura
            mostrarModalMejorado(
                'error',
                'Error al Reabrir',
                'No se pudo reabrir el recorrido. Por favor, inténtelo nuevamente.',
                () => location.reload()
            );
        }
        
    } catch (error) {
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
