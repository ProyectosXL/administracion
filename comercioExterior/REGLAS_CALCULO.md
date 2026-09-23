# 📋 Reglas de Cálculo Automático - Comercio Exterior

## 🎯 Lógica Prioritaria de Fechas

Todos los cálculos de fechas siguen esta regla de prioridad:

### Orden de Prioridad:
1. **🔴 PRIORIDAD MÁXIMA:** Fecha de Embarque real (ETD - `fechaEtd`)
2. **🟡 FALLBACK:** Fecha Estimada de Embarque (`fechaEmb`)

### Regla General:
> Si existe **Fecha de Embarque (ETD)** → usar para cálculos  
> Si NO existe → usar **Fecha Estimada de Embarque**

La resuelve `CronogramaFechas::fechaBaseEmbarque()`, en el servidor. Antes la
aplicaban por su cuenta `obtenerFechaBase()` en `cargaInicial.js` y
`calcularFechasEstimadas()` en `cronograma.js`.

---

## 🔢 La cadena de fechas derivadas

> **UN SOLO CÁLCULO, EN EL SERVIDOR.** Toda la cadena la resuelve
> `CronogramaFechas::cadenaDeFechas()`. **Ningún JS ni PHP del módulo tiene
> números de días escritos**: salen de `RO_T_IMPORTACIONES_PARAM_CRONOGRAMA` y
> se administran desde **Parámetros › Cronograma**.

```
embarque (ETD real, o estimado)
   ├── + DIAS_EMB_ARR   (45) ──► arribo
   │                               └── + DIAS_ARR_DESP  (5) ──► nacionalización
   │                                        └── + DIAS_DESP_REC (2) ──► recepción
   │                                                 └── + DIAS_REC_DIST (1) ──► distribución
   └── + DIAS_EMB_PAGO  (5)  ──► fecha estimada de pago
```

| Fecha | Columna | Cuelga de | Parámetro |
|---|---|---|---|
| Arribo (ETA) | `FECHA_ARR` | embarque | `DIAS_EMB_ARR` |
| Estimada de pago | `FECHA_EST_PAGO` | embarque | `DIAS_EMB_PAGO` |
| Nacionalización | `FECHA_DESP_ADU` | arribo | `DIAS_ARR_DESP` |
| Recepción estimada | *(no se persiste)* | nacionalización | `DIAS_DESP_REC` |
| Distribución | `FECHA_DISTRI` | recepción (real o estimada) | `DIAS_REC_DIST` |

### Los hechos le ganan a las proyecciones

- **Arribo en firme** (`ETA_CONFIRMADA = 1`, o corregido a mano): la
  nacionalización cuelga de **ese** arribo, no del proyectado. Sin esto, un
  contenedor cuya ETA se atrasó dos semanas seguiría mostrando la
  nacionalización calculada sobre un arribo que ya se sabe que no va a pasar.
- **Recepción real** (llega por Tango, STA20 comprobantes `'RP'`): la
  distribución cuelga de ella y no de la recepción estimada.

### El pago cuelga del embarque, no del arribo

Se le paga al proveedor del exterior **contra embarque**. Es la única rama de
la cadena que no pasa por el arribo, y por eso mover la ETA no mueve la fecha
de pago.

### Son días corridos

La cadena no corre las fechas al siguiente día hábil. Quien **sí** lo hace es
la pantalla de gestión de despachos (`validarCampoFechaHabil()`), sobre el
campo ya cargado y **avisando al usuario**. Es la única diferencia posible
entre lo que dibuja el cronograma y lo que termina guardado, y es visible.

### `DIAS_ARR_DIST` (10) se retiró

Ese parámetro valía 10 porque **con la cadena vieja** —nacionalización en
arribo + 7, recepción estimada en arribo + 9— caía justo un día después de la
recepción. Con la nacionalización en arribo + 5 la recepción estimada es
arribo + 7, así que arribo + 10 **dejó de ser "el día siguiente" de nada**:
eran dos días de aire que nadie había decidido.

Ahora la distribución cuelga **siempre** de la recepción, en los dos caminos
(`cadenaDeFechas()` y `derivarDistribucion()`):

| | sin recepción real | con recepción real |
|---|---|---|
| antes | arribo + `DIAS_ARR_DIST` (10) | recepción + `DIAS_REC_DIST` |
| ahora | recepción **estimada** + `DIAS_REC_DIST` = arribo + 8 | recepción + `DIAS_REC_DIST` |

**Por qué se eliminó en vez de bajarlo a 8.** Un 8 daría hoy el mismo
resultado, pero volvería a quedar desactualizado **en silencio** la próxima vez
que alguien toque `DIAS_ARR_DESP` o `DIAS_DESP_REC` desde el ABM. Es el mismo
modo de falla que tenían el 45/7/2 repartidos: un número plausible que describe
una cadena que ya cambió.

**La fila no se borró** (`sql/15`): queda con `DESCRIPCION = 'SIN USO…'`, el ABM
la muestra tachada y deshabilitada, y ya no está en la whitelist del controller.
Un `DELETE` no dejaría rastro de que existió.

Las `FECHA_DISTRI` guardadas **se mueven solas**: con `DIST_ORIGEN = 'A'` la
columna es una caché y `derivarDistribucion()` la recalcula en cada lectura. Las
`'M'` y `'C'` no se tocan nunca.

### 4️⃣ Valor FOB en Pesos - `valorFobPeso`
```
Valor FOB $ = Valor FOB U$S × Tipo de Cambio
```
- Se recalcula automáticamente cada vez que cambie:
  - Valor FOB U$S
  - Tipo de Cambio
- Lo deriva el servidor (`Encabezado::calcularFobPeso()`), no el navegador

---

## 💸 Estos parámetros también mueven el cashflow de Finanzas

El tablero de **ProyectosXL/finanzas** lee del maestro
`RO_T_IMPORTACIONES_ENCABEZADO`:

| Columna | Pestaña del cashflow |
|---|---|
| `FECHA_EST_PAGO` | Proveedores Exterior |
| `FECHA_DESP_ADU` | Crono Nacionalización |

o sea **las dos puntas de esta cadena**. Y va a leer además
`RO_T_IMPORTACIONES_PARAM_CRONOGRAMA` para **proyectar contenedores que
todavía no existen como fila** —cuando hay una fecha de embarque prevista pero
nadie cargó el contenedor—, que es lo que hoy no puede hacer.

> ⚠️ **Cambiar un valor desde Parámetros › Cronograma no es un ajuste cosmético
> de Comercio Exterior: mueve fechas en las dos aplicaciones.** Subir
> `DIAS_ARR_DESP` corre plata de un mes al siguiente en el tablero de Finanzas.

---

## 🔒 Fechas fijadas a mano

Tres fechas las calcula el sistema, y para las tres hace falta poder decir
"esta no, esta la puso una persona". **La marca vive en la base, no en la
memoria del navegador:**

| Fecha | Bit | Quién / cuándo | Script |
|---|---|---|---|
| `FECHA_EST_PAGO` | `FECHA_PAGO_CONF` | `FECHA_PAGO_CONF_USUARIO` / `_FECHA` | `sql/10` |
| `FECHA_ARR` | `ETA_CONFIRMADA` | `ETA_CONF_USUARIO` / `_FECHA` | `sql/11` |
| `FECHA_DESP_ADU` | `FECHA_DESP_CONF` | `FECHA_DESP_CONF_USUARIO` / `_FECHA` | `sql/11` |

**El arribo no estrena bit**: `ETA_CONFIRMADA` ya existía y ya significaba
esto —se enciende al editar la ETA a mano— además de que ya la leen el
cronograma (`CronogramaFechas::esFechaReal()`) y las dos pestañas del cashflow.
Una segunda columna afirmando el mismo hecho se contradice tarde o temprano.

### Cuál era el bug

Los flags `fechaArriboIsManual` y `fechaDespachoIsManual` vivían **solo en
memoria del navegador** y arrancaban en `false` en cada apertura. Para que el
recálculo no pisara una corrección hecha a mano, `cargarDatosDespacho()` los
encendía a ciegas: *"si la fila trae `FECHA_ARR`, marcala como manual"*. Eso
protegía, pero al precio de que **ninguna fecha ya guardada se recalculara
nunca más**: ni al mover el ETD, ni al cambiar un parámetro. La pantalla no
distinguía "esto lo decidió alguien" de "esto vino así".

Es el mismo bug que el script 10 corrigió para la fecha de pago, al revés: en
vez de perder la marca, la inventaba.

### Reglas

#### ✏️ Cuando el usuario EDITA un campo calculado:
- ✅ El navegador manda un marcador en el POST
- ✅ El backend **no le cree solo**: además compara contra el maestro y no marca
  nada si el valor no cambió (`Encabezado::marcarFechaFijada()`)
- ✅ Se marca **todo el grupo de OCs** del contenedor, porque la fecha también
  se replica a todas
- 🎨 El badge del campo pasa de **Auto** a **Manual**, con el tooltip de quién y
  cuándo

#### 🔄 "Volver a auto":
- Es un gesto **explícito**, con confirmación, vía
  `controller/revertirFechaAuto.php`
- Apaga el bit, recalcula con la cadena y deja el cambio en
  `RO_T_IMPORTACIONES_FECHAS_HIST`
- **Ningún guardado común apaga el bit**: si pudiera, la fecha fijada se
  perdería sin que nadie lo pida

#### 🔄 Cuando cambia la Fecha de Embarque (ETD o Estimada):
- ✅ Se pide la cadena completa al servidor y se recalculan los campos
  dependientes
- ⚠️ EXCEPTO los que están fijados a mano

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
$('#fechaEstEmb').on('change') → recalcularTodasLasFechas()

// Al cambiar Fecha de Embarque real (ETD) - PRIORIDAD MÁXIMA
$('#fechaEmb').on('change')    → recalcularTodasLasFechas()

// Al mover el arribo a mano, porque la nacionalización cuelga de él
$('#fechaArr').on('dp.change') → recalcularTodasLasFechas()

// recalcularTodasLasFechas() NO calcula: le pide la cadena a
// controller/calcularCadenaFechas.php y escribe lo que devuelve.
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
┌─────────────────┐    ┌──────────────────┐
│ Fecha Embarque  │ ──→│  SI EXISTE, USA  │
│  (ETD) real     │    │  ESTA EN VEZ DE  │
│   (fechaEtd)    │    │  LA ESTIMADA     │
└─────────────────┘    └──────────────────┘
           │
           ↓
┌─────────────────────────────┐
│    FECHA BASE DE EMBARQUE   │
└──────────┬──────────────────┘
           │
           ├──────────────────────────────────┐
           ↓                                  ↓
   ┌───────────────────┐            ┌─────────────────────┐
   │   Fecha Arribo    │            │ Fecha Est. de Pago  │
   │  + DIAS_EMB_ARR   │            │  + DIAS_EMB_PAGO    │
   └───────┬───────────┘            └─────────────────────┘
           │  (si la ETA está en firme, manda ESA)
           ↓
   ┌───────────────────┐
   │ Nacionalización   │
   │  + DIAS_ARR_DESP  │
   └───────┬───────────┘
           ↓
   ┌───────────────────┐
   │ Recepción estimada│ ←── si hay recepción REAL (Tango), manda ESA
   │  + DIAS_DESP_REC  │
   └───────┬───────────┘
           ↓
   ┌───────────────────┐
   │   Distribución    │
   │  + DIAS_REC_DIST  │
   └───────────────────┘

   Los cinco parámetros salen de RO_T_IMPORTACIONES_PARAM_CRONOGRAMA.
   Los calcula CronogramaFechas::cadenaDeFechas(), en el servidor,
   para las DOS pantallas: carga inicial y cronograma.

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

### Dónde vive cada cosa

**El cálculo (servidor) — es el único que existe:**

| Archivo | Qué hace |
|---|---|
| `cronogramaDespachos/class/CronogramaFechas.php` | `cadenaDeFechas()`, `fechaBaseEmbarque()`, `obtenerParametros()`. **La cadena entera.** |
| `controller/calcularCadenaFechas.php` | Endpoint que consume la carga inicial |
| `cronogramaDespachos/class/CronogramaDespachos.php` | Manda la cadena ya calculada en `FECHAS_EST`, una por fila |
| `class/encabezado.php` | `FECHAS_FIJABLES`, `marcarFechaFijada()`, `revertirFechaAuto()` |
| `controller/revertirFechaAuto.php` | "Volver a auto" de las tres fechas |

**La pantalla (navegador) — ya no calcula fechas:**

```javascript
// js/cargaInicial.js
recalcularTodasLasFechas()  // pide la cadena al servidor
aplicarCadenaFechas()       // la escribe, SALTEANDO las fijadas a mano
escribirFechaCalculada()    // valor + datepicker + corrimiento a día hábil
actualizarBadgeFecha(tipo)  // el badge Auto / Manual de las tres fechas
recalcularFobPesos()        // esta sí sigue siendo del navegador
configurarManualOverride()  // detecta la edición manual
establecerModoFormulario()  // controla modo alta/edición
```

```javascript
// cronogramaDespachos/js/cronograma.js
calcularFechasEstimadas(d)  // ya NO calcula: lee d.FECHAS_EST del servidor
```

**Las que se fueron**, y por qué están acá: `obtenerFechaBase()`,
`recalcularFechaArribo()`, `recalcularFechaPago()`, `recalcularFechaDespacho()`
y `recalcularFechaEstimadaPago()` sumaban los días en el navegador con 45, 5 y
2 escritos en el código. `recalcularFechaPago()` además escribía sobre un
`#fechaPago` que no existe en el formulario, y `FECHA_PAGO` tampoco es columna
del maestro en ninguna de las dos bases.

**`main.js` ya no estaba en uso** cuando se escribió esto: su `<script>` está
comentado en `tabs/components/detalleCostos.php` y su función de guardado lee
`document.getElementById('fechaPago').value`, que hoy tiraría `TypeError`. El
guardado real vive en `js/cargaInicial.js`.

### Scripts SQL de esta cadena

| Script | Qué hace |
|---|---|
| `sql/04_cronograma_parametros_dias.sql` | Crea `RO_T_IMPORTACIONES_PARAM_CRONOGRAMA` |
| `sql/10_fecha_pago_manual.sql` | `FECHA_PAGO_CONF` + backfill |
| `sql/11_fechas_fijadas_arribo_nacionalizacion.sql` | `FECHA_DESP_CONF`, `ETA_CONF_*` + backfill |
| `sql/12_parametros_fechas_derivadas.sql` | `DIAS_EMB_PAGO`, y `DIAS_ARR_DESP` a 5 |
| `sql/13_diagnostico_fecha_desp_adu.sql` | Sólo SELECT: clasifica las fechas existentes |
| `sql/14_migrar_fecha_desp_adu.sql` | Migra las automáticas de arribo + 2 a arribo + `DIAS_ARR_DESP` |
| `sql/15_retirar_dias_arr_dist.sql` | Retira `DIAS_ARR_DIST` (no borra la fila) |

---

## ⚠️ Notas Importantes

1. **NO** se puede agregar Órdenes de Compra si está marcado "Orden Manual"
2. En **Alta Inicial**, solo se guardan campos de Sección 1
3. En **Edición**, se pueden completar Secciones 2 y 3
4. Los campos calculados respetan SIEMPRE la prioridad: ETD → Estimada
5. Al editar manualmente un campo calculado, se desactiva su recálculo automático **y la marca queda en la base**: sobrevive al cierre de la pantalla
6. Para volver al modo automático está el botón **"volver a auto"** del badge, que es explícito y pide confirmación
7. Las fechas **ya nacionalizadas o recibidas no se recalculan nunca**

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

**Última actualización:** 22/09/2026 — rama `feature/comex-fechas-parametros`
