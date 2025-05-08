// Variables globales
let graficoTorta = null;
let mesesActuales = [];
let modalError = null;

$(document).ready(function() {
    inicializarMeses();
    cargarDatos();
    inicializarEventos();
    inicializarModal();
});

function inicializarEventos() {
    $('#btnRefrescar').click(cargarDatos);
    $('#btnExportar').click(() => window.location.href = 'exportar_excel.php');
    
    // Manejo de edición en celdas con SweetAlert2
    $(document).on('click', '.celda-editable', function() {
        const celda = $(this);
        const zona = celda.data('zona');
        const columnaIndex = celda.index() - 1;
        // Asegurar que usamos el mes correcto
        const mes = mesesActuales[columnaIndex];
        const valorActual = celda.text().trim() || '0';
        
        Swal.fire({
            title: 'Editar Reclamos',
            html: `<div class="mb-3">
                    <label class="form-label">Zona: <strong>${zona}</strong></label><br>
                    <label class="form-label">Período: <strong>${mes.nombre} ${mes.anio}</strong></label>
                   </div>`,
            input: 'number',
            inputValue: valorActual,
            inputAttributes: {
                min: 0
            },
            showCancelButton: true,
            confirmButtonText: 'Guardar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#1cc88a',
            cancelButtonColor: '#e74a3b',
            inputValidator: (value) => {
                if (!value || value < 0) {
                    return 'Por favor ingrese un número válido';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                guardarValorCelda(celda, zona, mes, result.value);
            }
        });
    });
}

function guardarValorCelda(celda, zona, mes, nuevoValor) {
    const valorOriginal = celda.text();
    
    $.ajax({
        url: 'guardar_valor.php',
        method: 'POST',
        data: {
            zona: zona,
            mes: mes.mes,
            anio: mes.anio,
            valor: nuevoValor
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                celda.text(nuevoValor);
                aplicarEstiloCelda(celda, nuevoValor);
                Swal.fire({
                    icon: 'success',
                    title: '¡Guardado!',
                    text: `Se actualizó correctamente el valor para ${zona} - ${mes.nombre} ${mes.anio}`,
                    timer: 2000,
                    showConfirmButton: false
                });
                setTimeout(cargarDatos, 300);
            } else {
                let errorDetail = '';
                if (response.message) {
                    errorDetail = response.message;
                } else if (response.error) {
                    errorDetail = response.error;
                } else if (response.sqlError) {
                    errorDetail = 'Error en la base de datos: ' + response.sqlError;
                } else {
                    errorDetail = 'No se pudo guardar el valor. Verifique la conexión y los datos ingresados.';
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error al guardar',
                    html: `<p>${errorDetail}</p>
                          <p class="small text-muted mt-2">Detalles técnicos:<br>
                          Zona: ${zona}<br>
                          Período: ${mes.nombre} ${mes.anio}<br>
                          Valor: ${nuevoValor}</p>`,
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#e74a3b'
                });
                celda.text(valorOriginal);
            }
        },
        error: function(xhr) {
            let errorMessage = 'Error de conexión';
            try {
                const response = JSON.parse(xhr.responseText);
                errorMessage = response.message || response.error || 'Error en el servidor';
            } catch(e) {
                errorMessage = `Error de conexión: ${xhr.status} - ${xhr.statusText}`;
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Error',
                html: `<p>${errorMessage}</p>
                      <p class="small text-muted mt-2">Si el problema persiste, contacte al administrador.</p>`,
                confirmButtonText: 'Aceptar',
                confirmButtonColor: '#e74a3b'
            });
            celda.text(valorOriginal);
        }
    });
}

function inicializarModal() {
    const modalHTML = `
        <div class="modal fade" id="errorModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Error</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p id="errorMessage"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Aceptar</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    $('body').append(modalHTML);
    modalError = new bootstrap.Modal(document.getElementById('errorModal'));
}

function inicializarMeses() {
    const hoy = new Date();
    mesesActuales = [];
    
    // Obtener mes actual y dos meses anteriores en orden inverso
    for (let i = 2; i >= 0; i--) {
        const fecha = new Date(hoy.getFullYear(), hoy.getMonth() - i, 1);
        mesesActuales.push({
            mes: fecha.getMonth() + 1,
            anio: fecha.getFullYear(),
            nombre: fecha.toLocaleString('es-ES', { month: 'long' }).toUpperCase()
        });
    }

    // Actualizar encabezados de la tabla en orden: marzo, abril, mayo
    mesesActuales.reverse().forEach((mes, index) => {
        $(`#mes${index}`).text(`${mes.nombre} ${mes.anio}`);
    });
}

function actualizarEstadisticas(datos) {
    const datosPorMes = {};
    datos.forEach(dato => {
        const mesKey = `${dato.mes}-${dato.anio}`;
        if (!datosPorMes[mesKey]) datosPorMes[mesKey] = {};
        datosPorMes[mesKey][dato.zona] = parseInt(dato.cantidad_reclamos);
    });

    // Usar los dos meses anteriores para comparación (marzo y abril)
    const mesesComparacion = mesesActuales.slice(0, 2);
    const mesAnterior = `${mesesComparacion[0].mes}-${mesesComparacion[0].anio}`;
    const mesActual = `${mesesComparacion[1].mes}-${mesesComparacion[1].anio}`;

    const ordenZonas = ['ZONA 1', 'ZONA 2', 'ZONA 3', 'ZONA 4', 'ADM', 'ALTO PARANA', 'ITAPUA', 'VCA OV CAA SANT'];

    let html = '<table class="table table-sm table-bordered">';
    html += `
        <thead>
            <tr>
                <th>Zona</th>
                <th>MARZO</th>
                <th>ABRIL</th>
                <th>Variación</th>
            </tr>
        </thead>
        <tbody>
    `;

    ordenZonas.forEach(zona => {
        const valorAnterior = (datosPorMes[mesAnterior] && datosPorMes[mesAnterior][zona]) || 0;
        const valorActual = (datosPorMes[mesActual] && datosPorMes[mesActual][zona]) || 0;
        
        const variacion = valorAnterior === 0 ? 100 :
            ((valorActual - valorAnterior) / valorAnterior * 100).toFixed(1);
        
        const colorClase = variacion > 0 ? 'text-danger' : 'text-success';
        const icono = variacion > 0 ? '↑' : '↓';

        html += `
            <tr>
                <td>${zona}</td>
                <td class="text-center">${valorAnterior}</td>
                <td class="text-center">${valorActual}</td>
                <td class="text-center ${colorClase}">${Math.abs(variacion)}% ${icono}</td>
            </tr>
        `;
    });

    html += '</tbody></table>';
    $('#tablaEstadisticas').html(html);

    // Agregar resumen de comparación
    const totalAnterior = Object.values(datosPorMes[mesAnterior] || {}).reduce((a, b) => a + b, 0);
    const totalActual = Object.values(datosPorMes[mesActual] || {}).reduce((a, b) => a + b, 0);
    const variacionTotal = totalAnterior === 0 ? 100 :
        ((totalActual - totalAnterior) / totalAnterior * 100).toFixed(1);
    
    const resumenHTML = `
        <div class="alert ${variacionTotal > 0 ? 'alert-danger' : 'alert-success'} py-2">
            <strong>Resumen:</strong> En ${mesesComparacion[1].nombre} hubo un 
            ${variacionTotal > 0 ? 'aumento' : 'descenso'} del 
            ${Math.abs(variacionTotal)}% en reclamos respecto a MARZO
        </div>
    `;

    $('#resumenComparacion').html(resumenHTML);
}

function cargarDatos() {
    const params = {
        meses: mesesActuales.map(m => m.mes).join(','),
        anios: mesesActuales.map(m => m.anio).join(',')
    };
    
    console.log('Parámetros enviados:', params); // Debug

    $.ajax({
        url: 'obtener_datos.php',
        method: 'GET',
        data: params,
        dataType: 'json',
        success: function(response) {
            console.log('Respuesta completa:', response); // Debug
            if (response.success && response.data) {
                $('.celda-editable').text('0').removeClass('verde amarillo rojo');
                
                response.data.forEach(dato => {
                    const mesIndex = mesesActuales.findIndex(m => 
                        parseInt(m.mes) === parseInt(dato.mes) && 
                        parseInt(m.anio) === parseInt(dato.anio)
                    );
                    
                    if (mesIndex !== -1) {
                        const celda = $(`.celda-editable[data-zona="${dato.zona}"]`).eq(mesIndex);
                        if (celda.length) {
                            celda.text(dato.cantidad_reclamos);
                            aplicarEstiloCelda(celda, dato.cantidad_reclamos);
                        }
                    }
                });

                actualizarGrafico(response.data);
                actualizarEstadisticas(response.data);
            } else {
                console.error('Error en la respuesta:', response);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudieron cargar los datos'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error en la petición:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al cargar los datos'
            });
        }
    });
}


function actualizarGrafico(datos) {
    const ctx = document.getElementById('graficoTorta').getContext('2d');
    const totalesPorZona = {};
    
    // Usar todos los datos disponibles para el gráfico
    datos.forEach(dato => {
        if (!totalesPorZona[dato.zona]) totalesPorZona[dato.zona] = 0;
        totalesPorZona[dato.zona] += parseInt(dato.cantidad_reclamos);
    });

    const config = {
        type: 'pie',
        data: {
            labels: Object.keys(totalesPorZona),
            datasets: [{
                data: Object.values(totalesPorZona),
                backgroundColor: [
                    '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e',
                    '#e74a3b', '#858796', '#5a5c69', '#476072'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                position: 'right',
                align: 'center',
                labels: {
                    padding: 15,
                    boxWidth: 12,
                    fontSize: 11
                }
            }
        }
    };

    if (graficoTorta) graficoTorta.destroy();
    graficoTorta = new Chart(ctx, config);
}


function aplicarEstiloCelda(celda, valor) {
    celda.removeClass('verde amarillo rojo');
    valor = parseInt(valor);
    
    if (valor > 100) {
        celda.addClass('rojo');
    } else if (valor > 50) {
        celda.addClass('amarillo');
    } else {
        celda.addClass('verde');
    }
}