# **Manual de Usuario Completo**
## **Sistema de Novedades RRHH**

---

### **Información del Documento**

| **Campo** | **Valor** |
|-----------|-----------|
| **Sistema:** | Sistema de Novedades RRHH |
| **Versión:** | 1.0 |
| **Fecha:** | Agosto 2025 |
| **Estado:** | Implementado |

---

## **1. INTRODUCCIÓN**

### **1.1 Propósito del Sistema**

El Sistema de Novedades RRHH es una aplicación web desarrollada para gestionar novedades laborales que impactan en la liquidación de sueldos. Permite el registro digital, consulta y seguimiento de cambios que afectan a los empleados.

### **1.2 Alcance del Manual**

Este manual describe las funcionalidades implementadas en el sistema:

- Proceso de creación de novedades
- Consulta y filtrado de registros
- Tipos de novedad disponibles
- Sistema de permisos por perfil de usuario

### **1.3 Perfiles de Usuario Implementados**

El sistema maneja tres perfiles con diferentes permisos:

- **Administradores (user_adm):** Acceso completo a todos los tipos de novedad
- **Comercial (user_com):** Acceso a tipos específicos para área comercial
- **Producción (user_prod):** Acceso a tipos específicos para área productiva

---

## **2. DESCRIPCIÓN GENERAL DEL SISTEMA**

### **2.1 Características Principales**

- **Interfaz Intuitiva:** Diseño con Bootstrap 5 y navegación clara
- **Responsive:** Funciona en diferentes dispositivos
- **Búsqueda de Empleados:** Sistema de autocompletado con Select2
- **Filtros de Consulta:** Búsqueda por empleado, sucursal, tipo y fechas
- **Validación:** Validación de datos en formularios

### **2.2 Período de Liquidación**

El sistema maneja períodos mensuales automáticos:
- **Inicio:** Día 28 del mes anterior
- **Fin:** Día 27 del mes actual

**Ejemplo:** Para agosto 2025:
- **Desde:** 28 de julio de 2025  
- **Hasta:** 27 de agosto de 2025

---

## **3. ACCESO AL SISTEMA**

### **3.1 Requisitos Técnicos**

- **Navegador:** Chrome, Firefox, Safari o Edge actuales
- **JavaScript:** Debe estar habilitado
- **Conexión:** Internet estable

### **3.2 URL de Acceso**

```
http://[servidor]/administracion/novedades/
```

### **3.3 Navegación Principal**

La barra de navegación incluye:

- **🏠 Inicio:** Panel principal con estadísticas
- **➕ Nueva Novedad:** Formulario de registro
- **🔍 Consultar Novedades:** Búsqueda y filtros
- **❓ Ayuda:** Manual interactivo

---

## **4. PANEL DE CONTROL (INICIO)**

### **4.1 Estadísticas del Panel**

Al acceder al sistema (`index.php`), se muestran:

- **Novedades del Período:** Total de novedades registradas
- **Pendientes de Revisión:** Novedades que requieren atención
- **Sucursales Activas:** Cantidad de sucursales con movimientos

### **4.2 Acciones Disponibles**

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

| **Tipo de Novedad** | **Código** | **Admin** | **Comercial** | **Producción** |
|---------------------|------------|-----------|---------------|----------------|
| Cambio de sucursal | CAMBIO_SUCURSAL | ✅ | ✅ | ❌ |
| Nuevo puesto | NUEVO_PUESTO | ✅ | ✅ | ❌ |
| Nuevo salario neto | NUEVO_SALARIO | ✅ | ❌ | ❌ |
| Ajuste de premios | AJUSTE_PREMIOS | ✅ | ✅ | ❌ |
| Horas extras | HORAS_EXTRAS | ✅ | ✅ | ✅ |
| Horas adicionales | HORAS_ADICIONALES | ✅ | ✅ | ✅ |
| Permisos | PERMISOS | ✅ | ✅ | ✅ |
| Cortes | CORTES | ✅ | ✅ | ✅ |
| Producción 25% | PRODUCCION_25 | ❌ | ❌ | ✅ |
| Producción 50% | PRODUCCION_50 | ❌ | ❌ | ✅ |
| Producción 100% | PRODUCCION_100 | ❌ | ❌ | ✅ |

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
