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

**Última actualización:** 20/11/2025
