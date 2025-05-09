<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reclamos por Zonas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background: linear-gradient(to right, #4e73df, #6f42c1);
            min-height: 100vh;
        }
        .container-fluid {
            padding: 2rem;
            background-color: rgba(255, 255, 255, 0.1);
            min-height: 100vh;
        }
        .card {
            background-color: white;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 1.5rem;
        }
        .btn-secondary {
            background-color: white;
            color: #4e73df;
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .btn-secondary:hover {
            background-color: #f8f9fc;
            color: #4e73df;
        }
        h2 {
            color: white;
            font-size: 1.75rem;
            font-weight: 400;
            margin-bottom: 0.5rem;
        }
        .celda-editable { 
            cursor: pointer; 
            transition: all 0.3s ease;
        }
        .celda-editable:hover { 
            background-color: #f8f9fc; 
        }
        .verde { background-color: #90EE90 !important; }
        .amarillo { background-color: #FFD700 !important; }
        .rojo { background-color: #DC3545 !important; color: white !important; }
    </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="container-fluid p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-chart-bar"></i> Reclamos por Zonas</h2>
            <a href="../../dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>

        <!-- Tabla Principal -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Registro de Reclamos</h5>
                <div>
                    <button class="btn btn-success mr-2" id="btnExportar">
                        <i class="fas fa-file-excel"></i> Exportar
                    </button>
                    <button class="btn btn-primary" id="btnRefrescar">
                        <i class="fas fa-sync"></i> Actualizar
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>Zonas</th>
                                <th id="mes2"></th>
                                <th id="mes1"></th>
                                <th id="mes0"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $zonas = [
                                'ZONA 1', 'ZONA 2', 'ZONA 3', 'ZONA 4',
                                'ADM', 'ALTO PARANA', 'ITAPUA', 'VCA OV CAA SANT'
                            ];
                            foreach ($zonas as $zona) {
                                echo "<tr><td>$zona</td>";
                                for ($i = 0; $i < 3; $i++) {
                                    echo "<td class='celda-editable text-center' 
                                             data-zona='$zona'></td>";
                                }
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Gráficos y Estadísticas -->
        <div class="row">
            <div class="col-md-5">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="mb-3">Gráfico de Distribución</h5>
                        <div style="position: relative; height: 280px;">
                            <canvas id="graficoTorta"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-7">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="mb-3">Comparación Mensual por Zona</h5>
                        <div id="tablaEstadisticas" class="small-table mb-3"></div>
                        <div id="resumenComparacion" class="text-center"></div>
                    </div>
                </div>
            </div>
        </div>

        <style>
        .small-table {
            font-size: 0.85rem;
        }
        .small-table .table td, 
        .small-table .table th {
            padding: 0.4rem;
        }
        .card-body {
            padding: 1rem;
        }
        </style>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/main.js"></script>
</body>
</html>

<style>
    #btnExportar, /* if using ID */
    .btn-exportar /* if using class */ {
        display: none;
    }
</style>