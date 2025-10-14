# Módulo de Contratos de Alquiler

## Descripción
Este módulo gestiona los contratos de alquiler de locales/sucursales. Ha sido separado del módulo principal de alquileres para una mejor organización del código.

## Estructura de Archivos

### Vistas Principales
- **cargaContratoAlquileres.php** - Formulario para dar de alta nuevos contratos
- **detalleContratosAlquiler.php** - Listado y gestión de contratos existentes (vigentes, anteriores, futuros)

### Clases (Class/)
- **Contrato.php** - Clase principal que maneja la lógica de negocio de contratos
  - `traerContratoAlquiler($fecha)` - Obtiene contratos por fecha
  - `traerContratoVigente()` - Obtiene contratos actualmente vigentes
  - `traerContratoAnterior()` - Obtiene contratos ya vencidos
  - `traerContratosFuturos()` - Obtiene contratos que iniciarán en el futuro
  - `guardarContratoAlquiler()` - Guarda un nuevo contrato
  - `actualizarContrato()` - Actualiza un contrato existente
  - `obtenerContratoPorId()` - Obtiene un contrato específico por ID
  - `verificarSolapamientoContrato()` - Verifica solapamientos al crear contrato
  - `verificarSolapamientoContratoEdicion()` - Verifica solapamientos al editar contrato

### Controladores (Controller/)
- **ContratoController.php** - Controlador AJAX para operaciones de contratos
  - Acción: `guardarContratoAlquiler` - Guarda nuevo contrato
  - Acción: `verificarSolapamientoContrato` - Verifica conflictos de fechas
  
- **actualizarContrato.php** - Controlador para actualizar contratos existentes

### Estilos (css/)
- **cargaContratoAlquileres.css** - Estilos para el formulario de carga
- **detalleContratosAlquiler.css** - Estilos para la vista de detalle/listado

### Scripts (js/)
- **cargaContratoAlquileres.js** - Lógica del formulario de carga
  - Validaciones de formulario
  - Verificación de solapamientos
  - Guardado de contratos
  - Manejo de errores y mensajes

## Base de Datos

### Tabla Principal
**RO_T_CONTRATOS_ALQUILERES**
- ID - Identificador único
- NRO_SUCURS - Número de sucursal
- DESC_SUCURS - Descripción de la sucursal
- VIG_DESDE - Fecha de inicio de vigencia
- VIG_HASTA - Fecha de fin de vigencia
- ID_CA - ID del concepto "Valor Llave" (4)
- IMPORTE - Importe del valor llave
- ID_CA_2 - ID del concepto "Comisiones" (5)
- IMPORTE_2 - Importe de comisiones
- ID_CA_3 - ID del concepto "FPC Lanzamiento" (18)
- IMPORTE_3 - Importe de FPC lanzamiento
- FECHA_CARGA - Fecha de carga del registro
- FECHA_MODIF - Fecha de última modificación

## Rutas

### URLs de Acceso
- Carga de contratos: `/administracion/impuestos/alquileres/contratos/cargaContratoAlquileres.php`
- Detalle de contratos: `/administracion/impuestos/alquileres/contratos/detalleContratosAlquiler.php`

### Dependencias
- Clase `Sucursal` (ubicada en `../Class/sucursal.php`)
- Clase `Alquiler` (para conceptos de porcentaje - ubicada en `../Class/Alquiler.php`)
- Controller `cambiarEntorno.php` (ubicado en `../Controller/cambiarEntorno.php`)

## Funcionalidades

### Carga de Contratos
1. Selección de sucursal
2. Definición de período de vigencia (desde/hasta)
3. Carga de importes:
   - Valor Llave
   - Comisiones
   - FPC Lanzamiento
4. Validación de solapamientos con contratos existentes
5. Guardado en base de datos

### Gestión de Contratos
1. Visualización de contratos por estado:
   - Vigentes (activos actualmente)
   - Anteriores (ya vencidos)
   - Futuros (aún no iniciados)
2. Edición de contratos existentes
3. Métricas y estadísticas:
   - Contador de contratos por estado
   - Contratos próximos a vencer
   - Contratos vencidos
4. Exportación de datos (en desarrollo)

## Validaciones

### Al Crear/Editar Contratos
- La fecha "Hasta" debe ser posterior a la fecha "Desde"
- Los importes no pueden ser negativos
- No puede haber solapamiento de fechas para la misma sucursal
- Todos los campos de fechas y sucursal son obligatorios

### Solapamiento de Contratos
Se verifica que el nuevo contrato no se solape con contratos existentes para la misma sucursal:
- El contrato nuevo no debe empezar durante un contrato existente
- El contrato nuevo no debe terminar durante un contrato existente
- El contrato nuevo no debe englobar completamente a uno existente
- Un contrato existente no debe englobar completamente al nuevo

## Migraci\u00f3n desde Alquiler.php

Los siguientes métodos fueron movidos desde la clase `Alquiler` a la clase `Contrato`:
- `traerContratoAlquiler()`
- `traerContratoVigente()`
- `traerContratoAnterior()`
- `traerContratosFuturos()`
- `guardarContratoAlquiler()`
- `actualizarContrato()`
- `obtenerContratoPorId()`
- `verificarSolapamientoContrato()`
- `verificarSolapamientoContratoEdicion()`

**IMPORTANTE:** Estos métodos deben ser eliminados manualmente de `../Class/Alquiler.php` (aproximadamente líneas 665-1296) para evitar duplicación de código.

## Cambios Pendientes

- [ ] Eliminar métodos de contratos de `../Class/Alquiler.php`
- [ ] Eliminar funciones relacionadas con contratos del `../Controller/AlquilerController.php` (guardarContratoAlquiler, verificarSolapamientoContrato)
- [ ] Implementar funcionalidad de exportación de datos

## Notas
- Soporte multi-entorno (Argentina/Uruguay)
- Interfaz responsive
- Integración con DataTables para listados
- SweetAlert2 para mensajes al usuario
- Select2 para selección de sucursales
