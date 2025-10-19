# Módulo Costo de Ocupación

## Estructura de Archivos

### Archivos del Módulo (costoOcupacion/)

```
costoOcupacion/
├── index.php                    # Archivo principal (antes costoOcupacion.php)
├── Class/
│   └── costoOcupacionService.php  # Lógica de negocio específica
├── Controller/
│   ├── costoOcupacionController.php
│   ├── getReporteFecha.php
│   ├── rankingController.php
│   └── compararSucursalesController.php
├── css/
│   ├── costoOcupacion.css
│   └── modalEvolucion.css
├── js/
│   ├── costoOcupacion.js
│   ├── reporteFecha.js
│   ├── ranking.js
│   ├── compararSucursales.js
│   └── modalEvolucion.js
└── components/
    ├── modalEvolucion.php
    ├── rankingTab.php
    └── compararSucursales.php
```

### Archivos Compartidos (nivel superior)

Estos archivos son utilizados por múltiples módulos y permanecen en:
- `../Class/Sucursal.php` - Clase compartida por todos los módulos
- `../Class/Alquiler.php` - Clase compartida
- `../Class/Periodo.php` - Clase compartida
- `../Controller/cambiarEntorno.php` - Controlador compartido por todas las pantallas

## Funcionalidades

### 1. Reporte por Sucursal
- Análisis individual de costo de ocupación por sucursal
- Filtros por fecha
- Gráfico de evolución YoY

### 2. Reporte a Fecha
- Vista transpuesta: sucursales en columnas, conceptos en filas
- Comparación entre todas las sucursales
- KPIs: menor/mayor costo, promedio
- % Costo de Ocupación período anterior (YoY)
- Variación relativa (%)

### 3. Ranking
- Ranking de sucursales por % Costo de Ocupación
- Comparación YoY (Year over Year)
- Filtros: 3/6/12 meses o personalizado
- Top N selector (5/10/20/Todos)
- Gráfico horizontal con Chart.js

### 4. Comparar Sucursales
- Comparación lado a lado de dos sucursales
- Análisis de diferencias
- Gráficos comparativos

## Cálculos

### Agrupación de Conceptos:
- **Alquiler**: IDs 1, 2, 8 (Alquiler + Complementario + Valor mínimo mensual)
- **Llave**: 25% de (Alquiler) = (ID 1 + 2 + 8) × 0.25
- **Baulera**: ID 3 (calculado por separado)
- **Alquiler porcentual**: IDs 6, 7
- **Fondo de promoción**: IDs 9, 16, 17
- **Gastos varios**: IDs 10, 13, 14
- **Expensas**: ID 11
- **Diferencia**: ID 12

### Fórmula Principal:
```
% Costo de Ocupación = (Total Costo de Ocupación / Venta Neta) × 100
```

### Variación YoY:
```
Variación Relativa % = ((Actual - Anterior) / Anterior) × 100
```

## Acceso

URL: `http://192.168.0.13:8000/impuestos/alquileres/costoOcupacion/`

## Dependencias

- PHP 7.4+
- SQL Server
- Bootstrap 4.x
- jQuery 3.x
- Chart.js 3.9.1
- chartjs-plugin-datalabels 2.2.0
- SweetAlert2
- DataTables
- Select2

## Próximas Funcionalidades

- [ ] Costo por M2 (nueva pestaña)
  - Relación: Costo / Superficie (m2)
  - Query: `SELECT NRO_SUCURS, (M2_VENTA + M2_DEPOSITO + M2_BAULERA) SUPERFICIE FROM [XL-APPS].sistemas.dbo.FP_T_SUCURSALES_MEDIDAS`
  - Métricas: Expensas/M2, Total gastos alquiler/M2
