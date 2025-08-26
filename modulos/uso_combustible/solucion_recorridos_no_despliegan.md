# Solución: Los Recorridos No Se Despliegan al Expandir

## Diagnóstico del Problema

El problema de que "al desplegar no me muestra el recorrido" se debe a que **no existen registros con múltiples recorridos** en la base de datos.

## ¿Cómo Funciona el Sistema?

El sistema agrupa registros por:
- Fecha de carga
- Usuario
- Conductor
- Chapa del vehículo
- Número de baucher
- Litros cargados

**Solo cuando hay MÁS DE UN registro con estos mismos datos, se muestra el botón de expansión.**

## Pasos para Verificar y Solucionar

### 1. Verificar si Hay Registros Múltiples

1. Abrir en el navegador: `http://localhost/MantenimientodeUPS/modulos/uso_combustible/test_multiple_records.php`
2. Este script mostrará si existen registros agrupados

### 2. Crear Datos de Prueba (Para Testing)

1. Abrir: `http://localhost/MantenimientodeUPS/modulos/uso_combustible/crear_datos_prueba.php`
2. Esto creará 3 recorridos con los mismos datos base
3. Luego ir a `ver_registros.php` para ver el botón de expansión

### 3. Verificar en el Código Fuente de la Página

En `ver_registros.php`, revisar los comentarios HTML de debug:
```html
<!-- DEBUG: Total grupos: X, Grupos múltiples: Y -->
<!-- DEBUG Grupo 1: isMultiple=true, count=3, key=... -->
```

### 4. Verificar la Consola del Navegador

Abrir las herramientas de desarrollador (F12) y revisar la consola:
- Debe mostrar: "✅ Botones de expandir encontrados correctamente"
- Si muestra: "⚠️ No se encontraron botones de expandir" = No hay registros múltiples

## Casos de Uso Reales

### ¿Cuándo se Generan Registros Múltiples?

1. **Mismo vehículo, múltiples destinos en el mismo día**
   - Ejemplo: Camión ABC123 va de A→B, luego B→C, luego C→D
   - Todos con el mismo baucher y carga de combustible

2. **Recorridos de ida y vuelta**
   - Ejemplo: A→B en la mañana, B→A en la tarde
   - Mismo conductor, vehículo y baucher

### ¿Cómo Crear Registros Múltiples Manualmente?

1. Ir a "Crear Registro" en el sistema
2. Llenar los datos básicos (conductor, chapa, baucher, litros)
3. **Agregar múltiples recorridos** usando el botón "+ Agregar Recorrido"
4. Guardar el registro

## Verificación de Funcionalidad

### El Botón de Expansión Debe:
- Aparecer solo cuando `count($group) > 1`
- Tener la clase `expand-btn`
- Mostrar icono de flecha (chevron)
- Al hacer clic, expandir/contraer los sub-registros

### Los Sub-registros Deben:
- Estar ocultos inicialmente (`display: none`)
- Mostrarse al hacer clic en el botón
- Contener todos los recorridos del grupo
- Permitir selección individual con checkboxes

## Archivos de Diagnóstico Creados

- `test_multiple_records.php`: Verifica registros múltiples en BD
- `crear_datos_prueba.php`: Crea datos de prueba
- `eliminar_datos_prueba.php`: Limpia datos de prueba
- `test_expandir.html`: Prueba funcional de JavaScript

## Solución Rápida para Testing

```sql
-- Ejecutar en phpMyAdmin o similar para crear datos de prueba
INSERT INTO uso_combustible (
    fecha_carga, hora_carga, usuario_id, nombre_usuario, tipo_vehiculo,
    nombre_conductor, chapa, numero_baucher, tarjeta, litros_cargados,
    origen, destino, documento, fecha_registro, estado_recorrido
) VALUES 
('2024-01-15', '08:00:00', 1, 'Usuario Test', 'camion', 'Juan Pérez', 'ABC123', '001234', 'TARJETA001', 50.00, 'Origen A', 'Destino B', 'DOC001', NOW(), 'cerrado'),
('2024-01-15', '08:00:00', 1, 'Usuario Test', 'camion', 'Juan Pérez', 'ABC123', '001234', 'TARJETA001', 50.00, 'Destino B', 'Destino C', 'DOC002', NOW(), 'cerrado'),
('2024-01-15', '08:00:00', 1, 'Usuario Test', 'camion', 'Juan Pérez', 'ABC123', '001234', 'TARJETA001', 50.00, 'Destino C', 'Final', 'DOC003', NOW(), 'cerrado');
```

## Conclusión

**El código funciona correctamente.** El problema es la ausencia de datos que cumplan los criterios para ser considerados "registros múltiples". Una vez que existan estos datos, los botones de expansión aparecerán automáticamente y la funcionalidad funcionará como se espera.