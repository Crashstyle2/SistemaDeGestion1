let recorridoCount = 1;

function agregarRecorrido() {
    const recorridosDiv = document.getElementById('recorridos');
    const nuevoRecorrido = document.createElement('div');
    nuevoRecorrido.className = 'recorrido-item mb-3';
    nuevoRecorrido.innerHTML = `
        <hr class="my-3">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="origen_${recorridoCount}">Origen</label>
                <input type="text" class="form-control" id="origen_${recorridoCount}" name="origen[]" required>
            </div>
            <div class="form-group col-md-6">
                <label for="destino_${recorridoCount}">Destino</label>
                <input type="text" class="form-control" id="destino_${recorridoCount}" name="destino[]" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="km_inicial_${recorridoCount}">Kilómetro Inicial</label>
                <input type="number" step="0.01" class="form-control" id="km_inicial_${recorridoCount}" name="km_inicial[]" required>
            </div>
            <div class="form-group col-md-6">
                <label for="km_final_${recorridoCount}">Kilómetro Final</label>
                <input type="number" step="0.01" class="form-control" id="km_final_${recorridoCount}" name="km_final[]" required>
            </div>
        </div>
        <div class="text-right">
            <button type="button" class="btn btn-danger btn-sm" onclick="eliminarRecorrido(this)">
                <i class="fas fa-trash mr-2"></i>Eliminar Recorrido
            </button>
        </div>
    `;
    recorridosDiv.appendChild(nuevoRecorrido);
    recorridoCount++;
}

function eliminarRecorrido(button) {
    button.closest('.recorrido-item').remove();
}

// Validación del formulario
document.getElementById('formCombustible').addEventListener('submit', function(e) {
    const kmIniciales = document.getElementsByName('km_inicial[]');
    const kmFinales = document.getElementsByName('km_final[]');
    
    for (let i = 0; i < kmIniciales.length; i++) {
        const kmInicial = parseFloat(kmIniciales[i].value);
        const kmFinal = parseFloat(kmFinales[i].value);
        
        if (kmFinal <= kmInicial) {
            e.preventDefault();
            alert('El kilómetro final debe ser mayor al kilómetro inicial en cada recorrido.');
            return;
        }
    }
});