function generarReporteDetallado() {
    // Get selected year
    const anio = $('select[name="anio"]').val();
    
    // Get selected months
    const meses = [];
    $('#selectorMeses input:checked').each(function() {
        meses.push($(this).val());
    });

    // Validate selections
    if (!anio) {
        alert('Por favor seleccione un año');
        return;
    }

    if (meses.length === 0) {
        alert('Por favor seleccione al menos un mes');
        return;
    }

    // Redirect to report generation with parameters
    window.location.href = `generar_reporte.php?anio=${anio}&meses=${meses.join(',')}`;
}