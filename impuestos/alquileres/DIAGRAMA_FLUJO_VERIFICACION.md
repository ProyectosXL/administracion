# Flujo de Verificación de Diferencias al Cerrar Período

```
┌─────────────────────────────────────────────────────────────────────────┐
│                   INICIO: Usuario hace clic en "Cerrar Período"         │
└────────────────────────────────┬────────────────────────────────────────┘
                                 │
                                 ▼
                    ┌────────────────────────────┐
                    │ Verificar período anterior │
                    │      está cerrado          │
                    └────────────┬───────────────┘
                                 │
                    ┌────────────┴────────────┐
                    │                         │
                   SI                        NO
                    │                         │
                    ▼                         ▼
      ┌─────────────────────────┐   ┌──────────────────────┐
      │  Verificar período está │   │ Mostrar: "Debe cerrar│
      │      procesado          │   │ períodos anteriores" │
      └──────────┬──────────────┘   └──────────────────────┘
                 │                            │
    ┌────────────┴────────────┐              │
    │                         │              │
   SI                        NO              │
    │                         │              │
    ▼                         ▼              │
┌───────────────────┐  ┌─────────────────┐  │
│ Recolectar valores│  │ Mostrar: "Debe  │  │
│  actuales de la   │  │ procesar período│◄─┘
│      tabla        │  └─────────────────┘
└────────┬──────────┘
         │
         ▼
┌────────────────────────────┐
│ Enviar AJAX:               │
│ verificarDiferenciasPreCie │
│ - período                  │
│ - datosActuales            │
└────────┬───────────────────┘
         │
         ▼
┌────────────────────────────┐
│ BACKEND: Comparar valores  │
│ guardados vs actuales      │
│ (tolerancia ±0.01)         │
└────────┬───────────────────┘
         │
         │
    ┌────┴─────┐
    │          │
 Diferencias  Sin diferencias
    │          │
    ▼          ▼
┌───────────────────────┐    ┌──────────────────┐
│ Resaltar celdas con   │    │ Ejecutar cierre  │
│ diferencias (amarillo)│    │    directamente  │
└───────┬───────────────┘    └────────┬─────────┘
        │                              │
        ▼                              │
┌────────────────────────────────┐    │
│ Mostrar Modal SweetAlert2      │    │
│ ┌──────────────────────────┐   │    │
│ │ Tabla con diferencias:   │   │    │
│ │ - Sucursal              │   │    │
│ │ - Concepto              │   │    │
│ │ - Valor guardado        │   │    │
│ │ - Valor actual          │   │    │
│ │ - Diferencia            │   │    │
│ └──────────────────────────┘   │    │
│                                │    │
│ Botones:                       │    │
│ [Actualizar y Cerrar]          │    │
│ [Cerrar sin Actualizar]        │    │
│ [Cancelar]                     │    │
└────────┬───────────────────────┘    │
         │                            │
    ┌────┴─────────────┬──────────────┤
    │                  │              │
    ▼                  ▼              ▼
┌─────────┐    ┌──────────────┐  ┌────────┐
│Actualizar│    │Cerrar sin    │  │Cancelar│
│y Cerrar  │    │Actualizar    │  │        │
└────┬─────┘    └──────┬───────┘  └────┬───┘
     │                 │               │
     ▼                 │               ▼
┌──────────────────┐   │         ┌──────────────┐
│ Mostrar spinner  │   │         │ Quitar       │
│ "Actualizando..."│   │         │ resaltados   │
└────┬─────────────┘   │         └──────────────┘
     │                 │               │
     ▼                 │               ▼
┌──────────────────┐   │         ┌──────────────┐
│ Para cada input  │   │         │ Mantener     │
│ con diferencia:  │   │         │ período      │
│ - Ejecutar AJAX  │   │         │ abierto      │
│   actualizarDet. │   │         └──────────────┘
└────┬─────────────┘   │
     │                 │
     ▼                 │
┌──────────────────┐   │
│ Esperar todas    │   │
│ las actualizac.  │   │
└────┬─────────────┘   │
     │                 │
     ▼                 │
┌──────────────────┐   │
│ Quitar           │   │
│ resaltados       │   │
└────┬─────────────┘   │
     │                 │
     ├─────────────────┘
     │
     ▼
┌──────────────────────────┐
│ Ejecutar cierre forzado  │
│ (forzarCierre = true)    │
└────────┬─────────────────┘
         │
         ▼
┌──────────────────────────┐
│ BACKEND: cerrarPeriodo   │
│ - Insertar en tabla      │
│   ESTADO                 │
└────────┬─────────────────┘
         │
         ▼
┌──────────────────────────┐
│ Mostrar: "Período        │
│ cerrado correctamente"   │
└────────┬─────────────────┘
         │
         ▼
┌──────────────────────────┐
│ Recargar página          │
│ (los inputs quedan       │
│  bloqueados)             │
└──────────────────────────┘
```

## Leyenda de Colores (Visual)

- 🟦 **Azul**: Verificaciones previas
- 🟨 **Amarillo**: Inputs con diferencias resaltados
- 🟩 **Verde**: Proceso exitoso
- 🟥 **Rojo**: Errores o advertencias

## Estados del Período

```
┌────────────┐  Abrir     ┌────────────┐
│  CERRADO   │◄───────────│   ABIERTO  │
│ (ESTADO=1) │            │ (ESTADO=0  │
└──────┬─────┘            │ o NULL)    │
       │                  └──────▲─────┘
       │                         │
       │ Usuario reabre período  │
       └─────────────────────────┘
```

## Tipos de Conceptos

```
┌─────────────────────────────────────────┐
│         CONCEPTOS EN EL SISTEMA         │
├─────────────────────────────────────────┤
│                                         │
│  CARGA MANUAL (4, 5, 18)                │
│  ├── Requieren ajuste con coeficiente  │
│  └── Campo AJUSTADO debe ser 1          │
│                                         │
│  CÁLCULO AUTOMÁTICO CON RENTABILIDAD    │
│  ├── Rent. Bruta (6, 15, 16)            │
│  └── Rent. Neta (7, 14, 17)             │
│                                         │
│  PORCENTAJES DIRECTOS (9, 13)           │
│  └── Se aplican sobre otros conceptos   │
│                                         │
└─────────────────────────────────────────┘
```

## Ejemplo de Diferencia Detectada

```
┌──────────────────────────────────────────────────────────┐
│  Sucursal 01 - Casa Central                              │
│  Concepto 6: Porc. s/ventas brutas                      │
│                                                          │
│  Valor Guardado:   $50,000.00                           │
│  Valor Actual:     $52,500.00  ← Cambió por nuevo %    │
│  ─────────────────────────────                          │
│  Diferencia:       +$2,500.00  ✅ (Positivo)           │
│                                                          │
│  Motivo: El porcentaje aplicado cambió de 2.5% a 2.8%  │
│          debido a actualización de configuración        │
└──────────────────────────────────────────────────────────┘
```

## Base de Datos - Tablas Involucradas

```
RO_T_DETALLE_ALQUILERES
├── PERIODO (PK)
├── NRO_SUCURS (PK)
├── ID_CA (PK)
├── IMPORTE ◄── Valor que se compara
├── PORCENTAJE_APLICADO
├── AJUSTADO
├── FECHA_MODIF
├── FECHA_AJUSTE
└── USUARIO

RO_T_DETALLE_ALQUILERES_ESTADO
├── PERIODO (PK)
└── ESTADO (1=Cerrado, NULL/0=Abierto)

RO_T_CONCEPTOS_ALQUILERES
├── ID_CA
├── CONCEPTO
├── carga_manual
└── ES_PORCENTAJE

RO_T_PORC_CONCEPTOS_ALQUILERES
├── ID_PA
├── ID_CA
├── NRO_SUCURS
└── PORCENTAJE ◄── Usado para recalcular

RO_T_COEFICIENTES_AJUSTE
├── PERIODO
└── COEFICIENTE ◄── Para ajustar conceptos manuales
```

## Tolerancia de Comparación

```
Valor Guardado: 1000.00
Valor Actual:   1000.00  → Sin diferencia ✓
Valor Actual:   1000.01  → Sin diferencia ✓ (dentro de tolerancia)
Valor Actual:   1000.02  → DIFERENCIA ⚠ (fuera de tolerancia)

Tolerancia = ±0.01
```
