# 📋 Reglas de Cálculo Automático - Comercio Exterior

## 🎯 Lógica Prioritaria de Fechas

Todos los cálculos de fechas siguen esta regla de prioridad:

### Orden de Prioridad:
1. **🔴 PRIORIDAD MÁXIMA:** Fecha de Embarque real (ETD - `fechaEtd`)
2. **🟡 FALLBACK:** Fecha Estimada de Embarque (`fechaEmb`)

### Regla General:
> Si existe **Fecha de Embarque (ETD)** → usar para cálculos  
> Si NO existe → usar **Fecha Estimada de Embarque**

---

## 🔢 Campos Calculados Automáticamente

### 1️⃣ Fecha de Arribo (ETA) - `fechaArr`
```
Fecha Arribo = Fecha base + 45 días
```
- Usa Fecha de Embarque si existe
- Si no, usa Fecha Estimada de Embarque

### 2️⃣ Fecha de Pago - `fechaPago`
```
Fecha Pago = Fecha base + 5 días
```
- Usa Fecha de Embarque si existe
- Si no, usa Fecha Estimada de Embarque

### 3️⃣ Fecha de Nacionalización (Despacho Aduana) - `fechaDespAdu`
```
Fecha Despacho = Fecha Arribo + 2 días
```
- Depende de la Fecha de Arribo calculada
- Respeta la misma regla de prioridad (ETD → Estimada)

### 4️⃣ Valor FOB en Pesos - `valorFobPeso`
```
Valor FOB $ = Valor FOB U$S × Tipo de Cambio
```
- Se recalcula automáticamente cada vez que cambie:
  - Valor FOB U$S
  - Tipo de Cambio

---

## 🔒 Sistema de "Manual Override"

Cada campo calculado tiene una bandera interna que controla si el usuario lo editó manualmente:

### Flags de Control:
```javascript
fechaArriboIsManual = false
fechaPagoIsManual = false
fechaDespachoIsManual = false
```

### Reglas del Manual Override:

#### ✏️ Cuando el usuario EDITA un campo calculado:
- ✅ Flag se pone en `TRUE`
- ✅ Se deja de recalcular automáticamente
- 🎨 Campo se marca visualmente con fondo naranja y icono ✏️

#### 🗑️ Cuando el usuario BORRA el campo:
- ✅ Flag vuelve a `FALSE`
- ✅ Se reactivan los cálculos automáticos
- 🎨 Campo vuelve al estado calculado automático

#### 🔄 Cuando cambia la Fecha de Embarque (ETD o Estimada):
- ✅ Se recalculan TODOS los campos dependientes
- ⚠️ EXCEPTO los que están en modo manual (flag = true)

---

## 🔄 Lógica de Carga por Etapas

### 📝 Etapa 1: Alta Inicial

**Sección 1 - Datos Iniciales:**
- ✅ Todos los campos son **obligatorios** y **editables**
- Campos: Proveedor, Contenedor, Material, Origen, Valor FOB U$S, Fecha Estimada Embarque, Órdenes de Compra

**Secciones 2 y 3:**
- 👀 Se muestran pero están **NO EDITABLES** (readonly)
- 🤖 Los campos autocalculados se generan usando **Fecha Estimada de Embarque**
- Estado visual: fondo gris (campo-readonly)

### ✏️ Etapa 2: Edición Posterior

**Sección 1 - Datos Iniciales:**
- 🔒 Campos en **SOLO LECTURA** (no se pueden modificar)
- ⚠️ EXCEPCIÓN: "Valor FOB U$S" sigue siendo **editable**
- Razón: permite ajustes sin alterar los datos originales del despacho

**Secciones 2 y 3:**
- ✅ Todos los campos son **editables**
- 🔄 Cuando se carga **Fecha de Embarque (ETD)**, se recalculan automáticamente:
  - Fecha Arribo (si no es manual)
  - Fecha Pago (si no es manual)
  - Fecha Despacho (si no es manual)

---

## 🎨 Indicadores Visuales

### Estados de Campos:

| Estado | Fondo | Borde Izquierdo | Icono | Significado |
|--------|-------|-----------------|-------|-------------|
| **Calculado Auto** | Verde claro | Verde | 🤖 | Campo calculado automáticamente |
| **Manual Override** | Naranja claro | Naranja | ✏️ | Campo editado manualmente |
| **Solo Lectura** | Gris | - | - | Campo no editable |
| **Normal** | Blanco | - | - | Campo editable estándar |

### Badges:

- 🟢 **Auto**: Indica que el campo se calcula automáticamente
  - Aparece en: Fecha Arribo, Fecha Pago, Fecha Despacho, FOB Peso

---

## 🔍 Eventos que Disparan Recálculos

### Cambios en Fechas Base:
```javascript
// Al cambiar Fecha Estimada de Embarque
$('#fechaEmb').on('change') → recalcularTodasLasFechas()

// Al cambiar Fecha de Embarque real (ETD) - PRIORIDAD MÁXIMA
$('#fechaEtd').on('change') → recalcularTodasLasFechas()
```

### Cambios en Valores Financieros:
```javascript
// Al cambiar Valor FOB U$S o Tipo de Cambio
$('#valorFobDolar').on('input') → recalcularFobPesos()
$('#tipoCambio').on('input') → recalcularFobPesos()
```

---

## 📊 Flujo de Datos

```
┌─────────────────────────────┐
│  Fecha Estimada Embarque    │ (Sección 1)
│     (fechaEmb)              │
└──────────┬──────────────────┘
           │
           ├──────────────────────────┐
           │                          │
           ↓                          ↓
   ┌───────────────┐         ┌────────────────┐
   │ Fecha Arribo  │         │  Fecha Pago    │
   │   + 45 días   │         │   + 5 días     │
   └───────┬───────┘         └────────────────┘
           │
           ↓
   ┌───────────────┐
   │Fecha Despacho │
   │   + 2 días    │
   └───────────────┘

┌─────────────────┐    ┌──────────────────┐
│ Fecha Embarque  │ ──→│  SI EXISTE, USA  │
│  (ETD) real     │    │  ESTA EN VEZ DE  │
│   (fechaEtd)    │    │  LA ESTIMADA     │
└─────────────────┘    └──────────────────┘

┌─────────────────┐    ┌──────────────────┐
│  Valor FOB U$S  │    │   Tipo Cambio    │
└────────┬────────┘    └─────────┬────────┘
         │                       │
         └───────────┬───────────┘
                     ↓
            ┌─────────────────┐
            │ Valor FOB Peso  │
            │   (U$S × TC)    │
            └─────────────────┘
```

---

## 🚀 Implementación Técnica

### Archivos Involucrados:

1. **cargaInicial.js** - Lógica de cálculos y validaciones
2. **cargaInicial.css** - Estilos visuales de estados
3. **cargaInicial.php** - Estructura HTML del formulario
4. **main.js** - Función de guardado
5. **insertarEncabezado.php** - Controller de backend
6. **encabezado.php** - Clase con SQL dinámico

### Funciones Principales:

```javascript
obtenerFechaBase()         // Retorna fecha según prioridad
recalcularFechaArribo()    // Calcula ETA
recalcularFechaPago()      // Calcula fecha pago
recalcularFechaDespacho()  // Calcula fecha nacionalización
recalcularFobPesos()       // Calcula FOB en pesos
recalcularTodasLasFechas() // Ejecuta todos los cálculos
configurarManualOverride() // Configura detección de edición manual
establecerModoFormulario() // Controla modo alta/edición
```

---

## ⚠️ Notas Importantes

1. **NO** se puede agregar Órdenes de Compra si está marcado "Orden Manual"
2. En **Alta Inicial**, solo se guardan campos de Sección 1
3. En **Edición**, se pueden completar Secciones 2 y 3
4. Los campos calculados respetan SIEMPRE la prioridad: ETD → Estimada
5. Al editar manualmente un campo calculado, se desactiva su recálculo automático
6. Al borrar un campo editado manualmente, vuelve al modo automático

---

## 🔧 Sistema de Parámetros de Importación

### 📊 Parámetros Configurables

El sistema cuenta con 13 conceptos configurables que se utilizan en los cálculos de estimación de costos:

| ID | Concepto | Tipo | Descripción |
|----|----------|------|-------------|
| 1 | Flete | I | Importe fijo del flete en USD |
| 2 | Seguro | P | Porcentaje sobre (FOB + Flete) |
| 3 | Derechos | P | Porcentaje sobre CIF |
| 4 | Tasa estadística | P | Porcentaje sobre CIF |
| 5 | IVA General | P | Porcentaje sobre Base Imponible |
| 6 | IVA Adicional | P | Porcentaje sobre Base Imponible |
| 7 | IIGG | P | Porcentaje sobre Base Imponible |
| 8 | IIBB | P | Porcentaje sobre Base Imponible |
| 9 | SIM | I | Importe fijo en moneda local |
| 10 | Antidumping | P/I | Variable según contexto |
| 11 | Despachante | I | Honorario base × multiplicador |
| 12 | Terminal | I | Costo fijo de terminal |
| 13 | Suma asegurada | P | Multiplicadores para cálculo |

### 🔑 Tipos de Valor

- **P (Porcentaje):** Se multiplica por el valor base (ej: 0.21 = 21%)
- **I (Importe):** Valor fijo en moneda (ej: 5000.00 = USD 5,000)

### 🧮 Qué suma cada total

| Total | Conceptos |
|---|---|
| **CIF** | FOB + Flete (1) + Seguro (2) |
| **Base imponible** | CIF + Derechos (3) + Tasa estadística (4) |
| **Total nacionalización** | **3 a 10** — Derechos, Tasa, IVA General, IVA Adicional, IIGG, IIBB, SIM, Antidumping |
| **Total Cashflow** | Total nacionalización + Despachante (11) + Terminal (12) + conceptos nuevos |
| *(fuera de los totales)* | Suma asegurada (13) |

#### ⚠️ Flete y Seguro NO son costos de nacionalización

Se pagan **antes** de nacionalizar, para poner la mercadería en el puerto de
destino, y por eso son **componentes del CIF** — que es la *base* sobre la que
se calculan los impuestos que sí lo son. Sumarlos al total contaría dos veces
el mismo concepto en dos roles distintos.

> El **Seguro estaba sumado** al `Total nacionalización` en la rama Argentina
> de `calcularTodosLosConceptos()` hasta la rama `feature/fecha-pago-manual`.
> Era el único de los tres lugares que lo hacía: la rama de Uruguay de esa
> misma función ya lo excluía, y el cashflow de Finanzas suma `ID_CE BETWEEN 3
> AND 10` en `getCronoNacionalizacion()`. Ahora los tres coinciden.

#### ⚠️ Flete y Seguro no los proyecta nadie

Quedan **fuera del cashflow de Finanzas**: sus dos pestañas cubren el pago al
proveedor por `VALOR_FOB_DOLAR` (Proveedores Exterior) y los gastos de
nacionalización 3 a 10 (Crono Nacionalización). Al 21/09/2026 son **208.560,00
de flete y 5.353,05 de seguro** sobre 60 contenedores que el tablero no está
proyectando. Es anterior a este trabajo y nadie lo documentó; queda anotado acá
porque el flete no es un importe menor.

### 📝 Lógica de Aplicación de Parámetros

> **REGLA FUNDAMENTAL:** Los cambios en parámetros **SOLO** afectan a **nuevas estimaciones** que se creen después de modificar los valores.

#### ✅ Cuándo se aplican los parámetros:

1. **Nueva Estimación:**
   - Al crear una nueva estimación desde cero
   - Los valores `VALOR_DEFAULT_1` y `VALOR_DEFAULT_2` se copian de `RO_T_CONCEPTOS_ESTIMACION_COMEX`
   - Se insertan en `RO_T_IMPORTACIONES_ESTIMACION_DETALLE` como valores iniciales

#### ❌ Cuándo NO se modifican automáticamente:

1. **Estimaciones existentes en estado BORRADOR:**
   - NO se actualizan automáticamente cuando cambian los parámetros
   - Mantienen los valores con los que fueron creadas
   - Solo si el usuario manualmente re-confirma con nuevos valores

2. **Estimaciones en estado CONFIRMADO:**
   - NUNCA se modifican
   - Son valores históricos congelados
   - Reflejan las condiciones del momento de confirmación

### 🔄 Flujo de Datos

```
┌─────────────────────────────────────┐
│  RO_T_CONCEPTOS_ESTIMACION_COMEX   │
│  (Parámetros Maestros)             │
│  - VALOR_DEFAULT_1                  │
│  - VALOR_DEFAULT_2                  │
└─────────────────┬───────────────────┘
                  │
                  │ SOLO al crear
                  │ nueva estimación
                  ↓
┌─────────────────────────────────────────┐
│  RO_T_IMPORTACIONES_ESTIMACION_DETALLE │
│  (Valores de cada estimación)           │
│  - VALOR_DEFAULT_1 (copia inicial)      │
│  - VALOR_DEFAULT_2 (copia inicial)      │
│  - CONFIRMADO (bit)                     │
└─────────────────────────────────────────┘
```

### ⚙️ Gestión de Parámetros

**Acceso:** Botón ⚙️ en el header de Comercio Exterior

**Funcionalidades:**
- Visualización de todos los parámetros configurables
- Edición de valores por defecto (VALOR_DEFAULT_1 y VALOR_DEFAULT_2)
- Registro de última actualización (ULT_ACTUA)
- Interfaz visual con badges indicando tipo de valor

**Campos editables:**
- Parámetro 1: Valor principal del concepto
- Parámetro 2: Valor secundario (solo ciertos conceptos)

**Validaciones:**
- Tipo numérico con hasta 4 decimales
- Obligatorio para Parámetro 1
- Opcional para Parámetro 2 (según concepto)

### 📌 Consideraciones Importantes

1. **Historial Inmutable:**
   - Las estimaciones confirmadas son registros históricos
   - Reflejan las condiciones económicas del momento
   - No deben alterarse automáticamente

2. **Estimaciones en Borrador:**
   - Se crean con los parámetros vigentes en ese momento
   - Si luego cambian los parámetros, el borrador NO se actualiza solo
   - El usuario debe re-confirmar manualmente si desea nuevos valores

3. **Trazabilidad:**
   - Campo `ULT_ACTUA` registra cuándo se modificó cada parámetro
   - Permite auditar cambios en configuración

---

**Última actualización:** 20/11/2025
