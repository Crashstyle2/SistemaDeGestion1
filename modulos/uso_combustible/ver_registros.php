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

// Construir la consulta SQL base - MODIFICAR LÍNEAS 50-53
$sql = "SELECT uc.*, u.nombre as nombre_usuario, 
       ucr.origen, ucr.destino, ucr.km_sucursales, ucr.comentarios_sector,
       s_origen.segmento as origen_segmento, s_origen.cebe as origen_cebe, 
       s_origen.local as origen_local, s_origen.m2_neto as origen_m2_neto, 
       s_origen.localidad as origen_localidad,
       s_destino.segmento as destino_segmento, s_destino.cebe as destino_cebe,
       s_destino.local as destino_local, s_destino.m2_neto as destino_m2_neto,
       s_destino.localidad as destino_localidad
       FROM uso_combustible uc 
       LEFT JOIN usuarios u ON uc.user_id = u.id 
       LEFT JOIN uso_combustible_recorridos ucr ON uc.id = ucr.uso_combustible_id 
       LEFT JOIN sucursales s_origen ON ucr.origen = s_origen.local
       LEFT JOIN sucursales s_destino ON ucr.destino = s_destino.local
       WHERE 1=1";
$params = array();

// Verificar el tiempo límite de modificación para técnicos (3 horas)
$puedeModificar = false;
if ($rol === 'tecnico') {
    $horasLimite = 3;
    $fechaActual = new DateTime();
    $fechaLimite = $fechaActual->modify("-{$horasLimite} hours");
    $fechaLimiteStr = $fechaLimite->format('Y-m-d H:i:s');
    
    $sql .= " AND (uc.fecha_registro >= '{$fechaLimiteStr}' OR uc.user_id = {$_SESSION['user_id']})";
    $puedeModificar = true;
} elseif ($rol === 'supervisor' || $rol === 'administrador' || $rol === 'administrativo') {
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

// Procesar exportación a Excel
if ($export === 'excel') {
    $registrosParaExportar = [];
    
    // Si hay IDs seleccionados, filtrar solo esos registros
    if (!empty($selected_ids) && is_array($selected_ids)) {
        foreach ($registros as $registro) {
            if (in_array($registro['id'], $selected_ids)) {
                $registrosParaExportar[] = $registro;
            }
        }
    } else {
        // Si no hay selección específica, exportar todos los registros filtrados
        $registrosParaExportar = $registros;
    }
    
    if (!empty($registrosParaExportar)) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Configurar encabezados - MODIFICAR LÍNEA 128
        $headers = ['Fecha', 'Conductor', 'Tipo Vehículo', 'Chapa', 'Nº Tarjeta', 'Nº Voucher', 'Litros Cargados', 
                   'Origen', 'Origen Segmento', 'Origen CEBE', 'Origen Localidad', 'Origen M2 Neto',
                   'Destino', 'Destino Segmento', 'Destino CEBE', 'Destino Localidad', 'Destino M2 Neto',
                   'KM entre Sucursales', 'Comentarios', 'Documento'];
        $sheet->fromArray($headers, null, 'A1');
        
        // Estilo para encabezados - MODIFICAR LÍNEA 136
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => 'center']
        ];
        $sheet->getStyle('A1:T1')->applyFromArray($headerStyle);
        
        // Agregar datos
        $row = 2;
        foreach ($registrosParaExportar as $registro) {
            $data = [
                date('d/m/Y H:i', strtotime($registro['fecha_carga'] . ' ' . $registro['hora_carga'])),
                $registro['nombre_conductor'] ?? '',
                ucfirst(str_replace('_', ' ', $registro['tipo_vehiculo'] ?? '')),
                $registro['chapa'] ?? '',
                $registro['tarjeta'] ?? '',
                $registro['numero_baucher'] ?? '',
                $registro['litros_cargados'] ?? 0,
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
        }
        
        // Ajustar ancho de columnas - MODIFICAR LÍNEA 160
        foreach (range('A', 'T') as $col) {
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
        background-color: #f8f9fa;
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
        transition: color 0.2s ease;
    }
    .expand-btn:hover {
        color: #0056b3;
        text-decoration: none;
    }
    .expand-btn:focus {
        outline: none;
        box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
    }
    .multiple-indicator {
        font-size: 0.8em;
        color: #6c757d;
        margin-left: 5px;
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
                                    
                                    $groupIndex = 0;
                                    foreach ($groupedRecords as $groupKey => $group): 
                                        $isMultiple = count($group) > 1;
                                        $mainRecord = $group[0];
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
                                            <?php if ($isMultiple): ?>
                                                <span class="group-info">Múltiples destinos</span>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($mainRecord['origen'] ?? ''); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($isMultiple): ?>
                                                <span class="group-info">Ver detalles</span>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($mainRecord['destino'] ?? ''); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($mainRecord['documento'] ?? ''); ?></td>
                                        <?php if ($puedeModificar): ?>
                                        <td class="text-center">
                                            <a href="editar_registro.php?id=<?php echo $mainRecord['id']; ?>" class="btn btn-sm btn-warning" title="Editar registro">
                                                <i class="fas fa-edit"></i>
                                            </a>
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
                                            $subRecord = $group[$i]; ?>
                                        <!-- Sub-registros (aparecen DESPUÉS del principal) -->
                                        <tr class="sub-record" data-group="<?php echo $groupIndex; ?>" style="display: none;">
                                            <td class="text-center">
                                                <input type="checkbox" class="record-checkbox sub-checkbox" 
                                                       value="<?php echo $subRecord['id']; ?>" 
                                                       data-group="<?php echo $groupIndex; ?>"
                                                       data-main-id="<?php echo $mainRecord['id']; ?>">
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($subRecord['fecha_carga'] . ' ' . $subRecord['hora_carga'])); ?></td>
                                            <td><?php echo htmlspecialchars($subRecord['nombre_usuario'] ?? ''); ?></td>
                                            <td><?php echo ucfirst(str_replace('_', ' ', $subRecord['tipo_vehiculo'] ?? '')); ?></td>
                                            <td><?php echo htmlspecialchars($subRecord['nombre_conductor'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($subRecord['chapa'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($subRecord['numero_baucher'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($subRecord['tarjeta'] ?? ''); ?></td>
                                            <td><?php echo number_format($subRecord['litros_cargados'] ?? 0, 2); ?></td>
                                            <td><?php echo htmlspecialchars($subRecord['origen'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($subRecord['destino'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($subRecord['documento'] ?? ''); ?></td>
                                            <?php if (in_array($_SESSION['user_rol'], ['administrador', 'tecnico', 'supervisor'])): ?>
                                            <td class="text-center">
                                                <a href="editar_registro.php?id=<?php echo $subRecord['id']; ?>" 
                                                   class="btn btn-warning btn-sm" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($_SESSION['user_rol'] === 'administrador'): ?>
                                                <button class="btn btn-danger btn-sm eliminar-registro" 
                                                        data-id="<?php echo $subRecord['id']; ?>"
                                                        data-conductor="<?php echo htmlspecialchars($subRecord['nombre_conductor'] ?? ''); ?>"
                                                        data-chapa="<?php echo htmlspecialchars($subRecord['chapa'] ?? ''); ?>"
                                                        title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </td>
                                            <?php elseif ($_SESSION['user_rol'] === 'administrativo'): ?>
                                            <td class="text-center">
                                                <span class="text-muted"><i class="fas fa-eye"></i> Solo lectura</span>
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

    <script>
// Esperar a que jQuery esté completamente cargado
jQuery(document).ready(function($) {
    'use strict';
    
    console.log('🚀 Iniciando ver_registros.php');
    
    // Variables globales
    let idToDelete = null;
    
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
            alert('Error: No hay ID para eliminar');
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
                    $('body').append(`
                        <div class="alert alert-success alert-dismissible fade show position-fixed" 
                             style="top: 20px; right: 20px; z-index: 10000; min-width: 300px;">
                            <i class="fas fa-check-circle"></i> Registro eliminado correctamente
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    `);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    alert('Error: ' + (response.message || 'No se pudo eliminar'));
                }
            },
            error: function() {
                $('#customConfirmModal').fadeOut(300);
                alert('Error de conexión al eliminar el registro');
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
            // Verificar estado actual usando visibility
            const isVisible = $subRecords.first().is(':visible');
            
            if (isVisible) {
                // Contraer
                $subRecords.hide().removeClass('show');
                $icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
                $button.attr('title', 'Expandir registros');
                console.log('📥 Grupo contraído:', groupId);
            } else {
                // Expandir
                $subRecords.show().addClass('show');
                $icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
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
</script>
            </body>
            </html>
