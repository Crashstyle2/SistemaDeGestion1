// Script mejorado para manejo de errores JavaScript con mensajes detallados

// Función para mostrar errores de sintaxis con contexto
function mostrarErrorSintaxis(error, archivo, linea, contexto) {
    const errorDetails = {
        tipo: 'Error de Sintaxis JavaScript',
        archivo: archivo || 'ver_registros.php',
        linea: linea || 'Desconocida',
        mensaje: error.message || error,
        contexto: contexto || 'No disponible',
        timestamp: new Date().toISOString(),
        userAgent: navigator.userAgent,
        url: window.location.href
    };
    
    console.group('🚨 ERROR DE SINTAXIS DETECTADO');
    console.error('Archivo:', errorDetails.archivo);
    console.error('Línea aproximada:', errorDetails.linea);
    console.error('Mensaje:', errorDetails.mensaje);
    console.error('Contexto:', errorDetails.contexto);
    console.error('Timestamp:', errorDetails.timestamp);
    console.error('URL:', errorDetails.url);
    console.groupEnd();
    
    // Mostrar modal con información detallada
    if (typeof mostrarModalMejorado === 'function') {
        const mensajeDetallado = `
            <div style="text-align: left; font-family: monospace; font-size: 12px;">
                <strong>Archivo:</strong> ${errorDetails.archivo}<br>
                <strong>Línea:</strong> ${errorDetails.linea}<br>
                <strong>Error:</strong> ${errorDetails.mensaje}<br>
                <strong>Contexto:</strong> ${errorDetails.contexto}<br>
                <strong>Hora:</strong> ${new Date().toLocaleString()}<br>
                <hr>
                <strong>Posibles causas:</strong><br>
                • Variable PHP sin escapar correctamente<br>
                • Comillas no cerradas en JavaScript<br>
                • Caracteres especiales en datos dinámicos<br>
                • Problema de codificación de caracteres<br>
                <hr>
                <strong>Recomendaciones:</strong><br>
                • Verificar que todas las variables PHP usen htmlspecialchars() o json_encode()<br>
                • Revisar la consola del navegador para más detalles<br>
                • Contactar al administrador del sistema si persiste
            </div>
        `;
        
        mostrarModalMejorado(
            'error',
            'Error de Sintaxis JavaScript Detectado',
            mensajeDetallado,
            () => {
                // Opción para recargar la página
                if (confirm('¿Desea recargar la página para intentar resolver el problema?')) {
                    location.reload();
                }
            }
        );
    } else {
        // Fallback si no existe la función de modal
        alert(`Error de Sintaxis JavaScript:\n\nArchivo: ${errorDetails.archivo}\nLínea: ${errorDetails.linea}\nError: ${errorDetails.mensaje}\n\nRevise la consola del navegador para más detalles.`);
    }
    
    // Enviar error al servidor para logging (si está disponible)
    if (typeof logFrontend === 'function') {
        logFrontend('ERROR_SINTAXIS_JAVASCRIPT', errorDetails);
    }
    
    return errorDetails;
}

// Capturar errores de sintaxis globales
window.addEventListener('error', function(event) {
    if (event.error && event.error.name === 'SyntaxError') {
        const contexto = event.error.stack ? event.error.stack.split('\n')[0] : 'No disponible';
        mostrarErrorSintaxis(
            event.error,
            event.filename || window.location.pathname,
            event.lineno,
            contexto
        );
    }
});

// Función para validar JSON antes de parsearlo
function parseJSONSafe(jsonString, contexto = '') {
    try {
        // Intentar parsear directamente primero
        return JSON.parse(jsonString);
    } catch (error) {
        // Si falla, intentar extraer JSON de una respuesta mixta HTML/JSON
        try {
            // Buscar patrones de JSON en la respuesta
            const jsonMatches = jsonString.match(/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/g);
            if (jsonMatches && jsonMatches.length > 0) {
                // Intentar parsear cada coincidencia JSON encontrada
                for (const match of jsonMatches) {
                    try {
                        const parsed = JSON.parse(match);
                        console.warn('⚠️ JSON extraído de respuesta mixta:', match);
                        return parsed;
                    } catch (e) {
                        continue;
                    }
                }
            }
            
            // Si no se encuentra JSON válido, lanzar el error original
            throw error;
        } catch (extractError) {
            // Si la extracción también falla, continuar con el manejo de error original
        }
        const errorDetails = {
            tipo: 'Error de Parsing JSON',
            mensaje: error.message,
            contexto: contexto,
            jsonString: jsonString.substring(0, 200) + (jsonString.length > 200 ? '...' : ''),
            timestamp: new Date().toISOString()
        };
        
        console.group('🚨 ERROR DE JSON DETECTADO');
        console.error('Contexto:', errorDetails.contexto);
        console.error('Mensaje:', errorDetails.mensaje);
        console.error('JSON (primeros 200 chars):', errorDetails.jsonString);
        console.error('Timestamp:', errorDetails.timestamp);
        console.groupEnd();
        
        if (typeof mostrarModalMejorado === 'function') {
            const mensajeDetallado = `
                <div style="text-align: left; font-family: monospace; font-size: 12px;">
                    <strong>Error:</strong> ${errorDetails.mensaje}<br>
                    <strong>Contexto:</strong> ${errorDetails.contexto}<br>
                    <strong>JSON (inicio):</strong><br>
                    <code style="background: #f5f5f5; padding: 5px; display: block; margin: 5px 0;">
                        ${errorDetails.jsonString.replace(/</g, '&lt;').replace(/>/g, '&gt;')}
                    </code>
                    <hr>
                    <strong>Posibles causas:</strong><br>
                    • Respuesta del servidor contiene HTML mezclado con JSON<br>
                    • Error de PHP que genera salida antes del JSON<br>
                    • Caracteres especiales no escapados<br>
                    • Respuesta vacía o incompleta del servidor
                </div>
            `;
            
            mostrarModalMejorado(
                'error',
                'Error de Formato JSON',
                mensajeDetallado
            );
        }
        
        if (typeof logFrontend === 'function') {
            logFrontend('ERROR_JSON_PARSING', errorDetails);
        }
        
        throw error; // Re-lanzar el error para que el código que llama pueda manejarlo
    }
}

// Función para validar respuestas de fetch
function validateFetchResponse(response, contexto = '') {
    if (!response.ok) {
        const errorDetails = {
            tipo: 'Error de Respuesta HTTP',
            status: response.status,
            statusText: response.statusText,
            url: response.url,
            contexto: contexto,
            timestamp: new Date().toISOString()
        };
        
        console.group('🚨 ERROR HTTP DETECTADO');
        console.error('Status:', errorDetails.status);
        console.error('Status Text:', errorDetails.statusText);
        console.error('URL:', errorDetails.url);
        console.error('Contexto:', errorDetails.contexto);
        console.groupEnd();
        
        if (typeof logFrontend === 'function') {
            logFrontend('ERROR_HTTP_RESPONSE', errorDetails);
        }
        
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    return response;
}

console.log('✅ Error handler mejorado cargado correctamente');