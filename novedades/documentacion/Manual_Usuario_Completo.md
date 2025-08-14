# **Manual de Usuario Completo**
## **Sistema de Novedades RRHH**

---

### **Información del Documento**

| **Campo** | **Valor** |
|-----------|-----------|
| **Sistema:** | Sistema de Novedades RRHH |
| **Versión:** | 2.0 |
| **Fecha:** | Agosto 2025 |
| **Estado:** | Implementado Completo |

---

## **1. INTRODUCCIÓN**

### **1.1 Propósito del Sistema** 

El Sistema de Novedades RRHH es una aplicación web desarrollada para gestionar integralmente las novedades laborales que impactan en la liquidación de sueldos. Permite el registro digital, consulta y seguimiento de cambios que afectan a los empleados, con un sistema avanzado de cálculo de períodos y gestión dinámica de tipos de novedad.

### **1.2 Alcance del Manual**

Este manual describe todas las funcionalidades implementadas en el sistema:

- **Gestión de Novedades:** Creación, consulta y edición de novedades
- **Cálculo Avanzado de Períodos:** Sistema inteligente basado en días de cierre y corte
- **Gestión Dinámica de Tipos:** Configuración flexible de tipos de novedad por perfil
- **Sistema de Permisos:** Control granular por tipo de usuario
- **Período Siguiente:** Funcionalidad para registrar novedades del período venidero
- **Integración con APIs:** Consulta automática de feriados argentinos

### **1.3 Perfiles de Usuario**

El sistema maneja cuatro perfiles con diferentes permisos:

- **RRHH (Tipo 4):** Acceso total - puede ver, crear y gestionar todos los tipos de novedad
- **Administradores:** Acceso completo según configuración dinámica de tipos
- **Comercial:** Acceso a tipos específicos configurados para área comercial
- **Producción:** Acceso a tipos específicos configurados para área productiva

---

## **2. CARACTERÍSTICAS AVANZADAS DEL SISTEMA**

### **2.1 Cálculo Inteligente de Períodos**

#### **2.1.1 Lógica Base de Períodos**

El sistema utiliza una lógica sofisticada para determinar el período de aplicación de cada novedad:

- **Período Base:** Del 28 del mes anterior al 27 del mes actual
- **Configuración por Tipo:** Cada tipo de novedad tiene días específicos de "cierre" y "corte"
- **Integración con Feriados:** Consulta automática de feriados argentinos vía API

#### **2.1.2 Días de Cierre y Corte**

Cada tipo de novedad está configurado con:

- **Día de Cierre:** Último día hábil para registrar la novedad en el período actual
- **Día de Corte:** Día límite para que la novedad tenga efecto en el período
- **Lógica Especial:** El valor "1" significa "primer día hábil del mes"

**Ejemplo práctico:**
- **Tipo "Nuevo Salario":** Cierre día 20, Corte día 15
- Si registrás el 22 de agosto, automáticamente se asigna al período siguiente (septiembre)

#### **2.1.3 Integración con API de Feriados**

El sistema consulta automáticamente la API de feriados argentinos para:
- Calcular correctamente los días hábiles
- Determinar el "primer día hábil" cuando corresponde
- Ajustar automáticamente los períodos considerando feriados

### **2.2 Funcionalidad "Período Siguiente"**

#### **2.2.1 ¿Qué es el Período Siguiente?**

Permite registrar novedades que se aplicarán al período venidero, no al actual. Esto es útil para:
- **Planificación:** Registrar cambios que entrarán en vigor el próximo mes
- **Anticipación:** Preparar aumentos salariales o cambios de puesto
- **Organización:** Separar novedades inmediatas de las futuras

#### **2.2.2 ¿Cómo Funciona?**

Al seleccionar "Período siguiente" en el formulario:
1. El sistema calcula automáticamente el próximo período
2. La novedad se guarda con mes/año del período siguiente
3. Aparece claramente marcada en las consultas
4. Se aplica en la liquidación correspondiente

**Ejemplo:** En agosto 2025, al marcar "Período siguiente", la novedad se registra para septiembre 2025.

### **2.3 Gestión Dinámica de Tipos de Novedad**

#### **2.3.1 Sistema Flexible**

A diferencia de sistemas rígidos, este permite:
- **Configuración por Usuario:** Cada tipo se asigna dinámicamente según el perfil
- **Sin Hardcodeo:** No hay tipos "quemados" en el código
- **Escalabilidad:** Fácil agregar nuevos tipos y permisos
- **Control Granular:** Cada usuario ve solo los tipos que le corresponden

#### **2.3.2 Configuración de Permisos**

Los permisos se manejan mediante campos en la base de datos:
- `permitir_rrhh`: Para usuarios de RRHH
- `permitir_admin`: Para administradores
- `permitir_comercial`: Para área comercial
- `permitir_produccion`: Para área productiva

---

## **3. ACCESO Y NAVEGACIÓN**

### **3.1 Requisitos Técnicos**

- **Navegador:** Chrome, Firefox, Safari o Edge actualizados
- **JavaScript:** Obligatorio habilitado
- **Conexión:** Internet estable para APIs externas
- **Resolución:** Mínima 1024x768 (responsive design)

### **3.2 URL de Acceso**

```
http://[servidor]/administracion/novedades/
```

### **3.3 Navegación Principal**

La barra de navegación incluye:

- **🏠 Inicio:** Panel principal con estadísticas actualizadas
- **➕ Nueva Novedad:** Formulario inteligente de registro
- **🔍 Consultar Novedades:** Búsqueda avanzada con múltiples filtros
- **⚙️ Gestión Tipos:** Configuración de tipos (solo RRHH)
- **❓ Ayuda:** Manual interactivo completo

---

## **4. PANEL DE CONTROL ACTUALIZADO**

### **4.1 Estadísticas del Panel**

El panel de inicio muestra tres métricas clave:

- **📊 Novedades Totales Registradas:** Suma de todas las novedades en el sistema
- **📅 Novedades del Período:** Solo las del período de liquidación actual
- **🏢 Sucursales Activas:** Cantidad de sucursales con movimientos

### **4.2 Información del Período**

En la esquina superior derecha se muestra:
- **Período Actual:** Mes/Año en curso para liquidación
- **Fechas:** Rango exacto del período (28 al 27)
- **Estado:** Indicador visual del período activo

### **4.3 Últimas Novedades**

Lista dinámica mostrando:
- Las 5 novedades más recientes
- Incluye novedades de períodos actual y siguiente
- Acceso directo a detalles completos

---

## **5. CREAR NUEVA NOVEDAD**

### **5.1 Formulario Inteligente**

El formulario de nueva novedad incluye:

#### **5.1.1 Búsqueda de Empleado**
- **Autocompletado:** Sistema Select2 con búsqueda inteligente
- **Múltiples Criterios:** Busca por nombre, apellido o legajo
- **Información Completa:** Muestra datos del empleado al seleccionar

#### **5.1.2 Selección de Tipo**
- **Dinámico:** Solo muestra tipos permitidos para tu perfil
- **Descriptivo:** Cada tipo incluye descripción clara
- **Configurado:** Basado en los permisos de tu usuario

#### **5.1.3 Cálculo Automático de Período**

**Funcionalidad Clave:**
1. Al seleccionar el tipo de novedad, se calcula automáticamente el período
2. Si estás dentro del plazo → Período actual
3. Si estás fuera del plazo → Automáticamente período siguiente
4. Opción manual para forzar "Período siguiente"

**Ejemplo práctico:**
- Tipo: "Nuevo Salario" (cierre día 20)
- Fecha actual: 22 de agosto
- **Resultado:** Automáticamente se asigna a septiembre 2025

#### **5.1.4 Campos Dinámicos**

Según el tipo seleccionado aparecen campos específicos:
- **Salarios:** Campo numérico para monto
- **Puestos:** Campo de texto para descripción
- **Horas:** Campo numérico para cantidad
- **Permisos:** Campos de fecha y compensación
- **Producción:** Campos de cantidad y porcentaje

### **5.2 Validaciones Inteligentes**

El sistema valida automáticamente:
- **Datos Obligatorios:** Según el tipo de novedad
- **Formatos:** Números, fechas, textos
- **Rangos:** Valores mínimos y máximos
- **Coherencia:** Relación entre campos
- **Duplicados:** Previene registros repetidos

### **5.3 Proceso de Guardado**

1. **Validación Frontend:** JavaScript valida antes de enviar
2. **Cálculo de Período:** Backend determina período final
3. **Validación Backend:** PHP valida datos recibidos
4. **Guardado en BD:** Registro en base de datos
5. **Confirmación:** Mensaje de éxito con detalles

---

## **6. CONSULTAR NOVEDADES**

### **6.1 Vista Integral**

La consulta muestra **TODAS** las novedades registradas, incluyendo:
- Novedades del período actual (mes en curso)
- Novedades del período siguiente (mes próximo)
- Novedades de períodos anteriores (histórico)

### **6.2 Sistema de Filtros Avanzado**

#### **6.2.1 Filtro por Empleado**
- **Autocompletado:** Búsqueda inteligente como en nueva novedad
- **Múltiples Criterios:** Nombre, apellido, legajo
- **Limpieza Rápida:** Botón para quitar filtro

#### **6.2.2 Filtros de Período**

**IMPORTANTE:** Hay dos tipos de filtros de período que NO se pueden usar simultáneamente:

**A) Filtro por Fecha de Registro:**
- Filtra por cuándo se registró la novedad
- Usa calendarios para "desde" y "hasta"
- Útil para auditorías y seguimiento

**B) Filtro por Período Real:**
- Filtra por el período de aplicación (mes/año de liquidación)
- Usa selectores de mes y año
- Útil para preparar liquidaciones

#### **6.2.3 Filtros Adicionales**
- **Sucursal:** Lista desplegable con todas las sucursales activas
- **Tipo de Novedad:** Solo tipos permitidos para tu perfil
- **Combinaciones:** Todos los filtros se pueden combinar

### **6.3 Visualización de Resultados**

#### **6.3.1 Tabla Inteligente**
- **Ordenamiento:** Click en encabezados para ordenar
- **Paginación:** Control de elementos por página
- **Información Completa:** Todos los datos relevantes
- **Acciones:** Ver detalle, editar (si tienes permisos)

#### **6.3.2 Información Mostrada**
- **Empleado:** Nombre completo y legajo
- **Tipo:** Descripción del tipo de novedad
- **Período de Aplicación:** Mes/Año donde se liquida
- **Fecha de Registro:** Cuándo se creó la novedad
- **Sucursal:** Nombre de la sucursal
- **Valor:** Monto o descripción según el tipo

### **6.4 Detalles de Novedad**

Al hacer click en "Ver Detalle":
- **Modal Completo:** Toda la información en ventana emergente
- **Datos del Empleado:** Información completa
- **Detalles de la Novedad:** Todos los campos registrados
- **Metadatos:** Fecha de creación, período calculado
- **Acciones:** Imprimir, editar (si corresponde)

---

## **7. GESTIÓN DE TIPOS DE NOVEDAD (SOLO RRHH)**

### **7.1 Acceso Exclusivo**

Esta funcionalidad está disponible **únicamente** para usuarios de RRHH (Tipo 4).

### **7.2 Configuración de Tipos**

#### **7.2.1 Datos Básicos**
- **Descripción:** Nombre del tipo de novedad
- **Estado:** Activo/Inactivo
- **Orden:** Para mostrar en listas

#### **7.2.2 Configuración de Períodos**
- **Día de Cierre:** Último día para registrar en período actual
- **Día de Corte:** Día límite para aplicar en período actual
- **Valor Especial "1":** Significa "primer día hábil del mes"

#### **7.2.3 Permisos por Usuario**
Checkboxes para cada tipo de usuario:
- ☑️ **RRHH:** Puede usar este tipo
- ☑️ **Administrador:** Puede usar este tipo
- ☑️ **Comercial:** Puede usar este tipo
- ☑️ **Producción:** Puede usar este tipo

### **7.3 Impacto de los Cambios**

Al modificar un tipo de novedad:
- **Inmediato:** Los cambios se aplican instantáneamente
- **Usuarios Activos:** Ven los cambios al recargar
- **Períodos:** Se recalculan según nueva configuración
- **Permisos:** Se actualizan dinámicamente

### **7.4 Casos de Uso Comunes**

#### **Ejemplo 1: Crear Tipo "Bono Especial"**
1. Descripción: "Bono Especial"
2. Cierre: 15, Corte: 10
3. Permisos: Solo RRHH y Administrador

#### **Ejemplo 2: Modificar Plazo de "Horas Extras"**
1. Cambiar cierre de 25 a 20
2. Cambiar corte de 22 a 15
3. Resultado: Plazos más estrictos para registrar

---

## **8. LÓGICA AVANZADA DE PERÍODOS**

### **8.1 Conceptos Clave**

#### **8.1.1 Período de Liquidación**
- **Definición:** Rango de fechas para el cual se calculan sueldos
- **Formato:** Del 28 del mes anterior al 27 del mes actual
- **Ejemplo:** Agosto 2025 = 28/07/2025 al 27/08/2025

#### **8.1.2 Período de Aplicación**
- **Definición:** En qué liquidación se aplica la novedad
- **Puede diferir:** Del período actual si se registra tarde
- **Calculado:** Automáticamente según tipo y fecha

### **8.2 Algoritmo de Cálculo**

#### **8.2.1 Proceso Paso a Paso**

1. **Obtener configuración del tipo:** Días de cierre y corte
2. **Calcular fechas límite:** Para el período actual
3. **Verificar si hay tiempo:** Comparar fecha actual con límites
4. **Consultar feriados:** API de feriados argentinos
5. **Calcular días hábiles:** Saltar fines de semana y feriados
6. **Determinar período:** Actual o siguiente según resultado

#### **8.2.2 Casos Especiales**

**A) Primer Día Hábil (Valor "1"):**
- Se calcula el primer día hábil del mes
- Se consideran fines de semana y feriados
- Se ajusta automáticamente

**B) Feriados y Fin de Semana:**
- Si el día límite cae en feriado → Se posterga al siguiente hábil
- Si cae en fin de semana → Se posterga al lunes (si no es feriado)

**C) Período Siguiente Manual:**
- Usuario puede forzar período siguiente
- Útil para planificación anticipada
- Se respeta la selección manual

### **8.3 Ejemplos Prácticos**

#### **Ejemplo 1: Registro Normal**
- **Fecha actual:** 15 de agosto 2025
- **Tipo:** "Nuevo Salario" (cierre: 20, corte: 15)
- **Resultado:** Período actual (agosto 2025)
- **Razón:** Estamos antes del día de cierre

#### **Ejemplo 2: Registro Tardío**
- **Fecha actual:** 25 de agosto 2025  
- **Tipo:** "Nuevo Salario" (cierre: 20, corte: 15)
- **Resultado:** Período siguiente (septiembre 2025)
- **Razón:** Pasamos el día de cierre

#### **Ejemplo 3: Primer Día Hábil**
- **Tipo:** "Ajuste Premio" (cierre: 1, corte: 1)
- **Significado:** Se puede registrar hasta el primer día hábil
- **Cálculo:** Sistema determina automáticamente qué día es el primer hábil

#### **Ejemplo 4: Con Feriado**
- **Día límite:** 17 de agosto (feriado de San Martín)
- **Ajuste:** Se extiende al 18 de agosto (siguiente día hábil)
- **Automático:** Sin intervención manual

---

## **9. CASOS DE USO FRECUENTES**

### **9.1 Escenarios Comunes**

#### **9.1.1 Aumento Salarial Urgente**
1. **Situación:** Empleado necesita aumento inmediato
2. **Acción:** Crear "Nuevo Salario" con período actual
3. **Validación:** Sistema verifica si hay tiempo
4. **Resultado:** Si es tarde, automáticamente va a próximo período

#### **9.1.2 Planificación de Cambios**
1. **Situación:** Preparar cambios para próximo mes
2. **Acción:** Marcar "Período siguiente" en formulario
3. **Ventaja:** Se registra anticipadamente
4. **Beneficio:** Liquidación preparada con tiempo

#### **9.1.3 Corrección de Errores**
1. **Situación:** Error en novedad ya registrada
2. **Acción:** Buscar en "Consultar Novedades"
3. **Edición:** Modificar datos directamente
4. **Validación:** Sistema recalcula período si es necesario

#### **9.1.4 Consulta de Auditoría**
1. **Objetivo:** Revisar novedades de un período específico
2. **Filtro:** Usar "Período Real" en consultas
3. **Resultado:** Solo novedades que impactan esa liquidación
4. **Análisis:** Información precisa para auditoría

### **9.2 Mejores Prácticas**

#### **9.2.1 Para Registro**
- **Planificar:** Registrar con anticipación cuando sea posible
- **Verificar:** Revisar el período calculado antes de guardar
- **Documentar:** Usar campos de observaciones para detalles
- **Confirmar:** Verificar datos del empleado antes de enviar

#### **9.2.2 Para Consultas**
- **Filtros Correctos:** Usar período real para liquidaciones
- **Fechas de Registro:** Para auditorías y seguimiento
- **Combinaciones:** Combinar filtros para búsquedas precisas
- **Exportar:** Usar opciones de impresión para respaldos

#### **9.2.3 Para Configuración (RRHH)**
- **Días Razonables:** Configurar plazos realistas
- **Permisos Claros:** Asignar tipos según responsabilidades
- **Documentar Cambios:** Mantener registro de modificaciones
- **Probar Configuración:** Verificar cálculos después de cambios

---

## **10. SOLUCIÓN DE PROBLEMAS COMUNES**

### **10.1 Problemas de Período**

#### **❓ "La novedad se asignó al período siguiente"**
- **Causa:** Se registró después del día de cierre
- **Solución:** Normal - se aplicará en próxima liquidación
- **Prevención:** Registrar antes del día límite

#### **❓ "No puedo seleccionar período actual"**
- **Causa:** El sistema calculó automáticamente período siguiente
- **Explicación:** Basado en configuración del tipo de novedad
- **Alternativa:** Consultar con RRHH si es urgente

### **10.2 Problemas de Permisos**

#### **❓ "No veo ciertos tipos de novedad"**
- **Causa:** Tu perfil no tiene permisos para esos tipos
- **Consulta:** Solicitar acceso a administrador
- **Verificar:** Confirmar tu tipo de usuario

#### **❓ "No puedo acceder a Gestión de Tipos"**
- **Causa:** Solo disponible para usuarios RRHH
- **Normal:** Función administrativa exclusiva
- **Alternativa:** Solicitar cambios a usuario RRHH

### **10.3 Problemas de Datos**

#### **❓ "No encuentro un empleado"**
- **Verificar:** Número de legajo correcto
- **Probar:** Búsqueda por nombre completo
- **Considerar:** Empleado puede estar inactivo

#### **❓ "Error al guardar novedad"**
- **Revisar:** Todos los campos obligatorios
- **Verificar:** Formatos de números y fechas
- **Consultar:** Administrador si persiste error

---

## **11. INFORMACIÓN TÉCNICA**

### **11.1 Tecnologías Utilizadas**

- **Frontend:** HTML5, CSS3, Bootstrap 5, JavaScript ES6+
- **Backend:** PHP 8+, SQL Server
- **APIs:** Feriados argentinos (nolaborables.com.ar)
- **Librerías:** Select2, jQuery, Font Awesome

### **11.2 Compatibilidad**

- **Navegadores:** Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
- **Dispositivos:** Desktop, tablet, móvil
- **Resoluciones:** Desde 320px hasta 4K
- **JavaScript:** ECMAScript 2015+ requerido

### **11.3 Rendimiento**

- **Carga Inicial:** < 2 segundos
- **Búsquedas:** Respuesta instantánea
- **APIs:** Timeout 5 segundos
- **Base de Datos:** Índices optimizados

---

## **12. CONTACTO Y SOPORTE**

### **12.1 Soporte Técnico**

Para consultas técnicas o problemas del sistema:
- **Departamento:** Sistemas/IT
- **Horario:** Lunes a viernes 9:00-18:00
- **Urgencias:** Contactar administrador del sistema

### **12.2 Consultas Funcionales**

Para dudas sobre procesos o novedades laborales:
- **Departamento:** Recursos Humanos
- **Referente:** [Nombre del referente RRHH]
- **Consultas:** Cualquier horario laboral

### **12.3 Actualizaciones**

Este manual se actualiza con cada nueva versión del sistema:
- **Versión Actual:** 2.0
- **Última Actualización:** Agosto 2025
- **Próxima Revisión:** Según nuevas funcionalidades

---

**© 2025 Sistema de Novedades RRHH - Versión 2.0**  
*Manual actualizado con todas las funcionalidades implementadas*

- **Crear Nueva Novedad:** Acceso directo al formulario
- **Consultar Novedades:** Ver registros existentes

### **4.3 Últimas Novedades**

Panel que muestra las novedades más recientes registradas en el sistema.

### **4.4 Información del Período**

El período actual se muestra automáticamente y es calculado dinámicamente por el sistema.

---

## **5. CREAR NUEVA NOVEDAD**

### **5.1 Acceso**

- Desde el menú: **Nueva Novedad** 
- Desde el panel: botón **"Crear Nueva Novedad"**
- URL: `nueva_novedad.php`

### **5.2 Proceso de Registro**

#### **PASO 1: Selección del Empleado**

1. **Campo "Empleado/Legajo"** con autocompletado usando Select2
2. Búsqueda por:
   - Nombre del empleado
   - Apellido del empleado  
   - Número de legajo
3. **Selección:** Elegir empleado de la lista desplegable
4. **Autocompletado:** El legajo y sucursal se completan automáticamente

#### **PASO 2: Tipo de Novedad**

Seleccionar del menú desplegable "Tipo de Novedad" según los permisos del usuario.

#### **PASO 3: Datos Específicos**

Completar los campos que aparecen según el tipo seleccionado:
- Campos numéricos (importes, cantidades)
- Fechas de vigencia
- Campos de texto específicos

#### **PASO 4: Observaciones**

Campo opcional para información adicional.

#### **PASO 5: Envío**

1. Hacer clic en **"Crear Novedad"**
2. El sistema valida los datos
3. Se muestra confirmación de éxito o mensaje de error

### **5.3 Validaciones Implementadas**

- **Empleado:** Debe existir en la base de datos
- **Campos obligatorios:** No pueden estar vacíos
- **Tipos numéricos:** Validación de formato
- **Fechas:** Validación de formato

---

## **6. TIPOS DE NOVEDAD IMPLEMENTADOS**

### **6.1 Clasificación por Perfil**

Definido por RRHH

### **6.2 Campos por Tipo de Novedad**

Cada tipo de novedad requiere campos específicos (los campos exactos dependen de la implementación del formulario dinámico en JavaScript).

### **6.3 Puestos Disponibles**

El sistema incluye puestos predefinidos:
- Vendedor, Vendedor Senior, Supervisor de Ventas
- Cajero, Supervisor de Caja  
- Gerente de Sucursal, Gerente Regional
- Encargado de Depósito, Jefe de Depósito
- Personal Administrativo y de Soporte
- Personal de Limpieza y Seguridad
- Personal de Sistemas y Contabilidad
| Producción 25% | ❌ | ❌ | ✅ |
| Producción 50% | ❌ | ❌ | ✅ |
| Producción 100% | ❌ | ❌ | ✅ |

---

## **7. CONSULTAR NOVEDADES**

### **7.1 Acceso**

- Desde el menú: **Consultar Novedades**
- URL: `consultar_novedades.php`

### **7.2 Funcionalidades de Consulta**

#### **Filtros Disponibles:**
- **Empleado/Legajo:** Búsqueda por autocompletado
- **Sucursal:** Filtro por ubicación
- **Tipo de Novedad:** Filtro por tipo específico
- **Rango de Fechas:** Filtro por fecha de creación

#### **Visualización:**
- Tabla con resultados de búsqueda
- Información de empleado, tipo, fecha y detalles
- Paginación de resultados (si aplica)

---

## **8. AYUDA INTEGRADA**

### **8.1 Modal de Ayuda**

El sistema incluye un modal de ayuda (`manual_modal.php`) accesible desde:
- Menú principal: **Ayuda**
- Función JavaScript: `mostrarManualUso()`

### **8.2 Secciones de Ayuda:**

- **Inicio Rápido:** Guía básica del sistema
- **Nueva Novedad:** Instrucciones paso a paso
- **Consultar:** Cómo usar los filtros
- **Tipos de Novedad:** Descripción de cada tipo

---

## **9. ASPECTOS TÉCNICOS**

### **9.1 Tecnologías Utilizadas**

- **Frontend:** HTML5, Bootstrap 5, JavaScript, jQuery
- **Backend:** PHP con SQL Server
- **Componentes:** Select2 para autocompletado
- **Base de datos:** SQL Server con tablas dinámicas

### **9.2 Archivos Principales**

- `index.php` - Panel principal
- `nueva_novedad.php` - Formulario de registro  
- `consultar_novedades.php` - Consultas y filtros
- `controller/novedades_controller.php` - Lógica de negocio
- `class/Novedades.php` - Clase principal
- `components/navbar.php` - Navegación
- `components/manual_modal.php` - Ayuda integrada

### **9.3 Base de Datos**

#### **Tablas Principales:**
- `tipos_novedad` - Tipos disponibles con permisos
- `puestos_disponibles` - Puestos de trabajo
- `novedades` - Registros de novedades
- `sucursales` - Información de sucursales
- `empleados` - Datos de personal

### **9.4 API Endpoints**

El controlador maneja las siguientes acciones:
- `get_sucursales` - Obtener listado de sucursales
- `get_tipos_novedad` - Tipos según perfil de usuario  
- `get_puestos` - Puestos disponibles
- `buscar_empleados_select2` - Autocompletado de empleados
- `crear_novedad` - Crear nueva novedad
- `get_novedades` - Consultar registros
- `get_periodo_actual` - Período vigente

---

## **10. CONFIGURACIÓN**

### **10.1 Configuración de Usuario**

El archivo `config/usuario_config.php` define el perfil del usuario actual para determinar qué tipos de novedad están disponibles.

### **10.2 Período Automático**

El sistema calcula automáticamente el período vigente usando la clase `Novedades::getPeriodoActual()`.

---

## **11. LIMITACIONES Y NOTAS**

### **11.1 Limitaciones Conocidas**

- Los períodos son calculados automáticamente y no son editables
- Los permisos por tipo se configuran a nivel de base de datos
- La búsqueda de empleados depende de los datos en la tabla `empleados`

### **11.2 Mantenimiento**

- Los tipos de novedad se inicializan automáticamente en la primera ejecución
- Los puestos se cargan automáticamente si la tabla está vacía
- Las sucursales se obtienen desde la tabla existente `sucursales`

---

*Este manual corresponde únicamente a las funcionalidades implementadas en el sistema actual.*
2. **Utilice** las validaciones del sistema como guía
3. **Aproveche** los filtros y reportes para análisis
4. **Mantenga** los datos actualizados y precisos
5. **Reporte** cualquier problema o sugerencia

El éxito del sistema depende del uso correcto y consistente por parte de todos los usuarios. Este manual debe servir como referencia constante y debe ser actualizado conforme evolucione el sistema.

---

**© 2025 - Sistema de Novedades RRHH - Manual de Usuario v1.0**
