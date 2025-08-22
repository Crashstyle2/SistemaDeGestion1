<?php
// Archivo para ver los logs de debug en tiempo real
require_once '../../config/session.php';

// Solo administradores pueden ver los logs
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'administrador') {
    die('Acceso denegado. Solo administradores pueden ver los logs.');
}

$logFile = '../../logs/debug_cerrar_recorrido.log';

// Si se solicita limpiar el log
if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    file_put_contents($logFile, "=== LOG LIMPIADO ===\nFecha: " . date('Y-m-d H:i:s') . "\n\n");
    header('Location: ver_logs.php');
    exit;
}

// Si se solicita via AJAX, devolver solo el contenido
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: text/plain');
    if (file_exists($logFile)) {
        echo file_get_contents($logFile);
    } else {
        echo "No hay logs disponibles.";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs de Debug - Cerrar/Reabrir Recorridos</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            margin: 20px;
            background-color: #1e1e1e;
            color: #ffffff;
        }
        .header {
            background-color: #333;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .controls {
            margin-bottom: 20px;
        }
        .btn {
            background-color: #007bff;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .log-container {
            background-color: #2d2d2d;
            border: 1px solid #555;
            border-radius: 5px;
            padding: 15px;
            height: 600px;
            overflow-y: auto;
            white-space: pre-wrap;
            font-size: 12px;
            line-height: 1.4;
        }
        .auto-refresh {
            color: #28a745;
            font-weight: bold;
        }
        .timestamp {
            color: #ffc107;
        }
        .error {
            color: #dc3545;
            font-weight: bold;
        }
        .success {
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔍 Logs de Debug - Cerrar/Reabrir Recorridos</h1>
        <p>Monitoreo en tiempo real de las operaciones de cierre y reapertura de recorridos.</p>
    </div>
    
    <div class="controls">
        <button class="btn" onclick="refreshLogs()">🔄 Actualizar</button>
        <button class="btn" onclick="toggleAutoRefresh()" id="autoRefreshBtn">▶️ Auto-refresh (OFF)</button>
        <a href="ver_logs.php?clear=1" class="btn btn-danger" onclick="return confirm('¿Está seguro de que desea limpiar el log?')">🗑️ Limpiar Log</a>
        <a href="ver_registros.php" class="btn">← Volver a Registros</a>
    </div>
    
    <div class="log-container" id="logContainer">
        <?php
        if (file_exists($logFile)) {
            echo htmlspecialchars(file_get_contents($logFile));
        } else {
            echo "No hay logs disponibles. El archivo se creará cuando ocurra la primera operación.";
        }
        ?>
    </div>
    
    <script>
        let autoRefreshInterval = null;
        let isAutoRefreshOn = false;
        
        function refreshLogs() {
            fetch('ver_logs.php?ajax=1')
                .then(response => response.text())
                .then(data => {
                    const container = document.getElementById('logContainer');
                    container.textContent = data;
                    // Auto-scroll al final
                    container.scrollTop = container.scrollHeight;
                })
                .catch(error => {
                    console.error('Error al cargar logs:', error);
                });
        }
        
        function toggleAutoRefresh() {
            const btn = document.getElementById('autoRefreshBtn');
            
            if (isAutoRefreshOn) {
                clearInterval(autoRefreshInterval);
                btn.textContent = '▶️ Auto-refresh (OFF)';
                btn.classList.remove('auto-refresh');
                isAutoRefreshOn = false;
            } else {
                autoRefreshInterval = setInterval(refreshLogs, 2000); // Cada 2 segundos
                btn.textContent = '⏸️ Auto-refresh (ON)';
                btn.classList.add('auto-refresh');
                isAutoRefreshOn = true;
            }
        }
        
        // Actualizar logs cada 5 segundos por defecto
        setInterval(refreshLogs, 5000);
    </script>
</body>
</html>