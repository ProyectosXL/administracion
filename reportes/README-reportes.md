# Reportes de Administración

Reportes a medida para el personal de Administración, uno por pestaña. Lo que
el usuario se lleva es un **Excel**: el SQL no se ve en ningún lado.

Hoy hay tres pestañas:

- **Valorización de stock**: el stock de LAKERS SA (Argentina) en una fecha de
  cierre, valorizado como se grabó o al costo de hoy.
- **Remitos pendientes de facturar**: los remitos de un período que seguían
  sin facturar a la fecha Hasta, y con qué factura se resolvieron después.
- **Parámetros**: los valores que usan los reportes. Por ahora, el factor de
  nacionalización estimado.

Ruta: `/administracion/reportes/`. Tarjeta en el HUB, en "Administración &
Finanzas".

---

## Para pasar a producción: los scripts, en orden

Los dos son reejecutables (`IF NOT EXISTS` / `COL_LENGTH`), informan con
`PRINT` lo que hicieron y no borran nada. Son independientes entre sí: van a
bases distintas.

| # | Script | Base | Qué hace | Si no se corre |
| --- | --- | --- | --- | --- |
| 1 | `sql/reportes_parametros.sql` | central (LAKER_SA) | Verifica que esté en la base correcta (la tabla histórica, `STA11`, `STA12`, `SJ_VIEW_STA11` y `RO_ORDEN_COMPRA_IMPORT_COSTO_NAC`); crea `RO_T_REPORTES_ADM_PARAMETROS` y su historial `RO_T_REPORTES_ADM_PARAMETROS_HIST`; siembra `FACTOR_NAC_ESTIMADO = 1.65` | Parámetros no muestra nada y el **modo actual** de la valorización no se puede consultar: avisa que falta el factor. El modo histórico funciona igual |
| 2 | `sql/reportes_permisos.sql` | apps (Gestionusuarios) | Alta del módulo `REPORTES_ADMINISTRACION` en `FP_MODULOS` y de sus cinco permisos. **No asigna roles**. Donde ya se corrió, volver a correrlo agrega solo las claves nuevas | El módulo no aparece en el HUB y solo entra el administrador |

El 1 va **antes** de publicar el código. El 2, cuando el código ya esté
publicado: apenas corre, la tarjeta aparece para quien tenga el permiso.

Después, los accesos se dan desde **Gestionusuarios**, asignando las claves a
cada rol (ver *Permisos*).

### La tarjeta del HUB

El HUB (`sistemas/hub/index.php`, otro repo) arma las tarjetas desde
`FP_MODULOS` y muestra cada una a quien tenga `hub.app.` + el código del módulo
en minúsculas. Para este módulo eso es `hub.app.reportes_administracion`, que
es la clave que da de alta el script (`tests/test_auth_reportes.php` lo
verifica). No hay que tocar el HUB.

Un rol que todavía no tiene ningún `hub.app.*` asignado cae en las reglas
viejas del HUB, y para un módulo que no conoce, como este, solo muestra la
tarjeta al administrador.

---

## Estructura del código

```
reportes/
  index.php                 la página: barra de arriba, solapas y un panel por pestaña
  Class/
    AuthReportes.php        usuario del padrón, permisos y el mapa de acciones
    Menu.php                las pestañas, como dato
    Parametros.php          los parámetros, su validación y su historial
    ValorizacionStock.php   la consulta y las reglas puras de la valorización
    RemitosPendientes.php   las consultas y la clasificación de los remitos pendientes
  Controller/
    autorizacion.php        autorizar(): 401 / 403
    TabController.php       sirve el HTML de cada pestaña
    ParametrosController.php
    ValorizacionController.php
    RemitosController.php
  Tabs/                     el HTML de cada pestaña
  Js/
    reportes.js             solapas, carga por AJAX y Reportes.post()
    permisos.js             el de cashflow: data-puede-editar
    excel.js                lo común de los Excel: fechas, formatos, autofiltro, encabezado
    parametros.js
    valorizacion.js         filtros, resumen y armado del Excel
    remitos.js              filtros, resumen y armado del Excel
  Css/reportes.css
  sql/                      los dos scripts de arriba
  tests/                    el arnés de comercioExterior/tests y las pruebas
```

El patrón es el de `comercioExterior` y el Informe Económico de `finanzas`:

- El front llama a `Controller/XController.php?action=...` por POST y recibe
  `{ success, message, ... }`.
- La lógica va en `Class/` y los controllers solo orquestan.
- Las reglas son métodos estáticos puros, para poder probarlas sin base.
- Todas las consultas son parametrizadas. Las listas de los filtros (fechas,
  sucursales) se validan contra lo que existe en la tabla.

### Cómo agregar un reporte

Sumar una pestaña, una clase y un permiso, sin tocar lo demás:

1. **La pestaña.** Una entrada en `Class/Menu.php` (`tab`, `nombre`, `icono`,
   `js` y, si hace falta, `libs`), su HTML en `Tabs/<tab>.php` con
   `AuthReportes::atributoEdicion('<tab>')` en la raíz, y su JS en `Js/`, que se
   registra con `Reportes.registrar('<tab>', { init: function (raiz) {...} })`.
   `js` es una lista: si la pestaña arma un Excel, lleva `excel.js` antes del
   propio y SheetJS en `libs`. `index.php` dibuja la solapa y carga cada JS una
   sola vez, solo si el usuario puede ver alguna pestaña que lo pida.
2. **La clase.** La lógica en `Class/`, con las reglas puras como métodos
   estáticos, y un `Controller/<Nombre>Controller.php` que llama a
   `autorizar('<Nombre>', $action)` **antes** del `switch`.
3. **Las acciones.** Cada `case` del controller, declarado en
   `AuthReportes::LECTURAS` o `AuthReportes::ESCRITURAS`, con la pestaña que
   habilita. Una acción sin declarar se rechaza con 403.
4. **El permiso.** `ra.tab.<tab>` (y `ra.editar.<tab>` si tiene escrituras) en
   `sql/reportes_permisos.sql`.
5. **Si necesita parámetros,** su fila en `RO_T_REPORTES_ADM_PARAMETROS` (en
   un script) y su regla en `Parametros::REGLAS`. La pantalla de Parámetros la
   muestra sola.

Las pruebas avisan si falta algo de los puntos 1, 3 y 4.

---

## Permisos

`Class/AuthReportes.php` es la misma lógica que `AuthCashflow` y
`AuthInformeEconomico` de `finanzas`:

- **El usuario sale del padrón** (`FP_SOF_USUARIOS`, base apps de
  Gestionusuarios). El `require` del padrón es condicional y la clase **falla
  cerrada**: sin padrón, sin sesión o ante un error de base no hay usuario, y no
  se ve nada.
- **Administrador:** la misma regla que el Cashflow (sector Proyectos +
  `es_admin` / `CONTROLTOTAL`). Entra a todo sin permisos asignados.
- **El módulo** se resuelve por el código `REPORTES_ADMINISTRACION` en
  `FP_MODULOS` (`AuthReportes::MODULO_CODIGO`), nunca por un id.
- **Lectura y edición separadas:** `puede($tab)`, `puedeEditar($tab)` y
  `atributoEdicion($tab)`. Escribir implica leer: `ra.editar.parametros` sin
  `ra.tab.parametros` no deja editar.
- **Cada acción está declarada** en `LECTURAS` o `ESCRITURAS`. A diferencia del
  Cashflow, las lecturas también exigen usuario y permiso de su pestaña: un
  endpoint de lectura devuelve el stock valorizado de toda la empresa, y la URL
  directa no tiene que mostrar nada sin permiso.
- **Respuestas:** sin usuario, 401; sin permiso o con una acción no declarada,
  403. Las traduce `Controller/autorizacion.php`, el único lugar que lo hace.
- **El front** oculta los controles de edición según `data-puede-editar`
  (`Js/permisos.js`), pero lo que protege es el servidor.
- `username()` no vuelve a `$_SESSION`: lo que se graba en la auditoría es
  siempre el usuario del padrón.

| Clave | Para qué |
| --- | --- |
| `hub.app.reportes_administracion` | Hace aparecer la tarjeta en el HUB |
| `ra.tab.valorizacion_stock` | Ver la pestaña de valorización y descargar el Excel |
| `ra.tab.remitos_pendientes` | Ver los remitos pendientes de facturar y descargar el Excel. La pestaña no escribe: no tiene clave de edición |
| `ra.tab.parametros` | Ver la pestaña Parámetros y su historial |
| `ra.editar.parametros` | Modificar parámetros (exige también `ra.tab.parametros`) |

---

## Parámetros

Una fila por parámetro en `RO_T_REPORTES_ADM_PARAMETROS` (central): `CLAVE`,
`NOMBRE`, `VALOR DECIMAL(10,4)`, `DESCRIPCION` y la auditoría
`USUARIO_ALTA / FECHA_ALTA / USUARIO_MODIF / FECHA_MODIF`.

- **La pantalla se arma desde la tabla:** un parámetro nuevo aparece solo.
- **La validación vive en `Parametros::REGLAS`.** Un parámetro que está en la
  tabla y no tiene regla se muestra pero no se puede modificar: sin saber qué
  rango tiene sentido, aceptar cualquier número sería peor que no dejarlo
  cambiar.
- **Cada cambio** graba el `UPDATE` y su fila en
  `RO_T_REPORTES_ADM_PARAMETROS_HIST` (valor anterior, nuevo, usuario y fecha)
  en una sola transacción. El valor anterior se lee con `UPDLOCK`.
- **La auditoría la escribe siempre el backend**, con el usuario del padrón y
  `GETDATE()`. El front no manda ninguna columna de auditoría. Sin usuario del
  padrón, la escritura se rechaza.
- Guardar el mismo valor que ya estaba no graba nada.

| Parámetro | Valor inicial | Regla | Lo usa |
| --- | --- | --- | --- |
| `FACTOR_NAC_ESTIMADO` — Factor de nacionalización estimado | 1,65 | Entre 1 y 5 inclusive, hasta 4 decimales, coma decimal | Solo el modo **actual** de la valorización |

La vista `RO_V_VALORIZACION_STOCK_DETALLADO_LAKERS` **no lee este parámetro**:
tiene su 1,65 escrito y sigue igual. Cambiar el factor solo cambia el modo
actual del reporte.

---

## Valorización de stock

### Fuente

`RO_T_VALORIZACION_STOCK_DETALLADO_LAKERS` (central): `FECHA, COD_ARTICU,
DESCRIPCIO, SUCURSAL, SECTOR, STOCK, VALORIZACION`. La llena
`RO_SP_VALORIZACION_STOCK_DETALLADO_LAKERS` desde la vista
`RO_V_VALORIZACION_STOCK_DETALLADO_LAKERS`, con `GETDATE()-1` como fecha.
Ninguna de las tres se modifica: el reporte solo las lee.

- `SECTOR = 'CENTRAL'`: la sucursal es "LAKERS SA".
- `SECTOR = 'LOCALES'`: una fila por sucursal.
- Toda comparación por fecha va contra `CAST(FECHA AS DATE)`. Hoy la columna es
  `DATE`, pero el SP la llena con `GETDATE()-1`, que trae hora.

### Se agrupa por sector + sucursal + artículo

En CENTRAL la vista une `STA19` **por depósito** sin agrupar, y la tabla **no
guarda el depósito**. Un mismo artículo aparece hasta seis veces en LAKERS SA
sin ninguna columna que distinga las filas (en el cierre del 18/10/2026, 3.842
de las 9.230 filas de CENTRAL eran repetidas). El reporte las suma: una fila
por sector + sucursal + artículo, con stock y valorización sumados. Ningún
total cambia. En LOCALES no hay repetidas.

### Filtros

- **Fecha de cierre** (obligatoria): las que existen en la tabla, de la más
  reciente a la más vieja, con la última preseleccionada. No hay calendario.
- **Costo** (obligatorio): Histórico (por defecto) o Actual.
- **Sector:** Todos, Central o Locales.
- **Sucursal:** varias, de las que tienen stock en esa fecha, filtradas por el
  sector. Ninguna tildada es todas.
- **Artículo:** texto que se busca en código o descripción, con `LIKE`
  parametrizado. Los comodines del usuario (`%`, `_`, `[`) se buscan como
  texto, y el código se compara sin distinguir mayúsculas (`COD_ARTICU` es
  `Latin1_General_BIN`).

Cambiar un filtro **no consulta**: se consulta con el botón (o Enter en el
artículo). Si se cambió un filtro sin consultar, la pantalla lo avisa.

### Modo histórico

`STOCK` y `VALORIZACION` tal cual se grabaron. Si en su momento la vista
valorizó con la lista 20 dividida por 3, queda así: es lo que dijo el cierre.

- Costo unitario: valorización sumada / stock sumado, o 0 si el stock da 0.
- Origen del costo: "Histórico".

### Modo actual

El `STOCK` es el grabado en la fecha elegida (sumado por artículo). Lo que se
recalcula es el costo, con los datos **de hoy** y la lógica de la vista, salvo
en los artículos sin costo:

1. **Costo base:** `STA12.PRECIO_U_L`, en pesos.
2. **Sin costo base** (`PRECIO_U_L` en `NULL` o `0`):
   - **artículos que empiezan con `OU`:** precio de venta de la **lista 20**
     (`GVA17.PRECIO` con `NRO_DE_LIS = 20`) **dividido por 3**, **sin
     nacionalización**. Si no tienen precio en lista 20 (o es 0), quedan sin
     costo;
   - **cualquier otro artículo:** el costo es **0**. No se usa la lista 20 ni
     se divide por 3.
3. **Artículo con partida** (`SJ_VIEW_STA11.USA_PARTID = 1`), con costo base:
   - con `RO_ORDEN_COMPRA_IMPORT_COSTO_NAC.COSTO_NAC`: costo base × (1 + `COSTO_NAC`);
   - sin `COSTO_NAC`: costo base × el factor de nacionalización estimado de
     Parámetros.
4. **Sin partida:** el costo base.
5. **Valorización:** stock × costo unitario, redondeada a 2 decimales.

| Origen del costo | Cuándo |
| --- | --- |
| Última compra | Sin partida, con costo base |
| Última compra + nacionalización | Con partida y `COSTO_NAC` cargado |
| Última compra + nacionalización estimada | Con partida, sin `COSTO_NAC`: se aplica el factor de Parámetros |
| Precio de venta lista 20 / 3 | Artículo `OU` sin costo base, con precio en lista 20: lista 20 / 3, sin nacionalización |
| Sin costo | `PRECIO_U_L` en `NULL` o `0` y no es un `OU` con lista 20: la valorización da 0 |

La falta de costo se resuelve antes que la partida: sin costo de última compra
no hay nada que nacionalizar. Por eso un `OU` por lista 20 nunca lleva
nacionalización. Hoy ningún `OU` usa partida, y la regla no se la sumaría
aunque alguno llegara a usarla.

Las cuatro fuentes tienen **una sola fila por `COD_ARTICU`**, así que las
uniones no duplican:
- `STA12` y `STA11` (de la que `SJ_VIEW_STA11` es una vista directa) tienen
  índice único sobre `COD_ARTICU`;
- `GVA17` tiene índice único sobre `COD_ARTICU, NRO_DE_LIS`, y se une solo con
  la lista 20 y solo para los `OU`;
- `RO_ORDEN_COMPRA_IMPORT_COSTO_NAC` es una vista que filtra
  `ROW_NUMBER() OVER (PARTITION BY COD_ARTICU ...) = 1`.

**"Sin costo" en 0 y la lista 20 / 3 solo para los `OU` son pedidos
explícitos de Administración.** En el cierre del 18/10/2026:

| Origen | Filas | Histórico | Actual |
| --- | ---: | ---: | ---: |
| Precio de venta lista 20 / 3 (540 artículos `OU`) | 1.020 | 64.821.293 | 64.821.293 |
| Sin costo (907 artículos) | 5.220 | 1.660.561.339 | 0 |

Los `OU` dan lo mismo que en el histórico porque es la misma cuenta que hacía
la vista. Los "Sin costo" valían en el histórico el 34% del total. La pantalla
muestra cuántas filas quedaron sin costo y cuántas fueron por lista 20.

### Una sola consulta

`ValorizacionStock::sqlConsulta()` hace todo el cálculo en SQL, en **una
consulta parametrizada** con la fecha, el modo, los filtros y el factor como
parámetros. Lo único que se arma es la cantidad de `?` de la lista de
sucursales. El **factor lo lee el servidor** de la tabla de parámetros: no
viene del front.

Las mismas reglas están en PHP (`origenCosto()`, `costoUnitarioActual()`,
`valorizar()`) para poder probarlas, y una prueba compara las dos contra la
base fila por fila (ver *Pruebas*).

`OPTION (RECOMPILE)`: con el modo como parámetro, recompilar deja que SQL Server
vea el valor de `@MODO` y en histórico no toque `STA12`, `GVA17` ni la vista
de nacionalización, que es lo caro. Un cierre completo tarda unos 3 s en histórico
y 6,5 s en actual.

### Lo que se ve en pantalla

- **Tarjetas:** stock total, valorización total, cantidad de artículos y, en
  modo actual, las filas "Sin costo", las de "nacionalización estimada" (con
  el factor aplicado) y las de "lista 20 / 3".
- **Resumen por sector y sucursal,** con subtotal por sector y total general.
- **Leyenda** con la fecha y el modo ("Stock al AAAA-MM-DD valorizado al costo
  de hoy") y los filtros.
- Sin datos para los filtros: un mensaje que sugiere cambiarlos.

**Stock e importes se muestran sin decimales**, redondeados, en pantalla y en
el Excel (formato `#,##0`). Es **formato, no redondeo del valor**: cada fila
conserva sus centavos, así el total sigue siendo la suma exacta de las filas y
coincide con `SUM(VALORIZACION)`. En Excel los centavos se ven en la barra de
fórmulas. Sumar a mano los números visibles de varias filas puede diferir en
algún peso del total.

### El Excel

Botón "Descargar Excel", habilitado después de consultar. Usa **los filtros de
la última consulta** (los que devolvió el servidor ya validados), no los que
estén en pantalla: el Excel es el detalle de lo que se está viendo.

- `.xlsx` generado en el navegador con **SheetJS `xlsx@0.18.5`** (la versión de
  `novedades`) desde el JSON de `ValorizacionController?action=detalle`.
- Nombre: `Valorizacion_Stock_AAAAMMDD_Historico.xlsx` o `..._Actual.xlsx`.
- **Hoja "Detalle":** encabezado con la fecha de cierre y el modo, los filtros,
  el factor (en modo actual), el usuario del padrón y la fecha y hora de
  generación (hora de Argentina, escrita en el código). Columnas: Fecha de
  cierre, Sector, Sucursal, Artículo, Descripción, Stock, Costo unitario,
  Valorización y Origen del costo. Fechas y números son celdas numéricas, con
  autofiltro en la fila de títulos.
- **Hoja "Resumen":** la misma tabla de la pantalla, con subtotales y total.

Volumen: un cierre completo son unas 37.000 filas (después de agrupar). El
detalle viaja en unos 3,8 MB de JSON, con las filas como listas y no como
objetos, y el `.xlsx` pesa unos 4 MB.

---

## Remitos pendientes de facturar

Para un rango de fechas de remito, qué remitos **seguían sin facturar a la
fecha Hasta** (la fecha de corte). Un remito que se facturó después del Hasta
cuenta como pendiente, y el reporte dice con qué factura y cuándo se resolvió.
Solo Argentina: base central (LAKER_SA).

La referencia es el SP `RO_BUSCA_REMITOS_SIN_FACTURA`, que manda por mail los
remitos de los últimos 60 días sin factura. **El SP no se modifica**: el
reporte toma de él el universo y los importes, y lo mejora (ver *Mejoras
respecto del SP*).

### Fuente y universo

**Remitos:** `STA14` con `TCOMP_IN_S = 'RE'`, `N_COMP LIKE 'R%'`, clientes
`COD_PRO_CL LIKE '[FML]%'` salvo `FRUSH`, y `ESTADO_MOV <> 'A'`. Los anulados
se excluyen siempre, aunque en el Hasta todavía estuvieran vigentes: Tango no
guarda cuándo se anuló.

- **Fecha del remito:** `GVA106.FECHA_REM`, unido por `TCOMP_IN_S` +
  `NCOMP_IN_S`. `GVA106` tiene una fila por renglón, pero una sola fecha por
  remito (verificado en toda la base). Si el remito no tiene `GVA106`, se usa
  `STA14.FECHA_MOV`: en los 241.659 remitos que tienen las dos, coinciden
  siempre. Los que no tienen `GVA106` son casi todos de 2010–2014; desde 2024
  hay 10.
- **Importe:** `SUM(STA20.CANTIDAD * STA20.PRECIO_REM)` de los renglones con
  `PROMOCION = 0`, como `DECIMAL(18,2)`. Neto y sin promociones.
- **Razón social:** `GVA14.RAZON_SOCI`, o "Sin datos".

**Facturas:** `GVA12` + `GVA53` (por `T_COMP` + `N_COMP`), solo
`T_COMP = 'FAC'`. Importe: `SUM(IMPORTE_GRAVADO)` de los renglones con
`PROMOCION = 0`, como `DECIMAL(18,2)`. Fecha: `FECHA_EMIS`.

**Vínculo de Tango:** `GVA54` une el remito (`TCOMP_IN_S` + `NCOMP_IN_S`) con
la factura (`T_COMP_V` + `N_COMP` → `GVA12`). Es por renglón, así que se toma
una fila por factura. Solo cuentan las `FAC`: hoy `GVA54` no vincula estos
remitos con otra cosa.

### Filtros

| Período (por fecha de remito) | Desde | Hasta |
| --- | --- | --- |
| Año contable actual (por defecto) | 01/08 del año contable en curso | hoy |
| Año contable anterior | 01/08 | 31/07 |
| Año calendario | 01/01 del año en curso | hoy |
| Personalizado | se elige | se elige |

- El año contable va del 01/08 al 31/07. El 22/10/2026, el actual es 01/08/2026
  → hoy y el anterior 01/08/2025 → 31/07/2026.
- Los rangos los calcula el servidor (`RemitosPendientes::rangoPeriodo()`, con
  prueba) con el "hoy" de Argentina, y el front los pide con la acción
  `periodos`. Elegir una opción completa Desde y Hasta; tocar una fecha a mano
  pasa el selector a "Personalizado".
- El Hasta no puede ser posterior a hoy y el Desde no puede ser posterior al
  Hasta. Lo valida el servidor; la pantalla lo avisa antes.
- **Tipo de cliente:** Todos, Franquicias (F), Gestión asistida (L) o
  Mayoristas (M).
- **Cliente:** varios, con búsqueda por código o razón social, entre los que
  tienen remitos en el período y el tipo elegidos. Ninguno es todos. El
  servidor rechaza un cliente que no está en esa lista.

Cambiar un filtro **no consulta**: se consulta con el botón, como en la
valorización.

### La clasificación

La base trae los remitos del período con sus facturas vinculadas, y el **pozo**:
las `FAC` de esos clientes emitidas desde el Desde hasta **hoy** que **no
están vinculadas en `GVA54` a ningún remito**. La clasificación la hace PHP
(`RemitosPendientes::clasificar()`, regla pura con prueba), porque el
emparejamiento uno a uno es secuencial. En este orden:

1. **Vinculado en Tango.** Si el remito tiene factura vinculada, manda la
   **última** (la de fecha más nueva): el remito queda facturado recién cuando
   se emitió su última factura.
   - Del Hasta o antes: facturado, no aparece.
   - Posterior: pendiente, con "Facturado después" = esa factura y "Cómo se
     identificó" = "Vinculado en Tango".
2. **Por importe, uno a uno.** Los no vinculados, por cliente + importe exacto
   al centavo:
   - dentro de cada grupo, los remitos se recorren por fecha y número;
   - cada uno se lleva la **primera factura del pozo todavía libre** con el
     mismo cliente, el mismo importe y `FECHA_EMIS` igual o posterior a la del
     remito (primero se hace el remito y después la factura);
   - cada factura cubre un solo remito;
   - del Hasta o antes: facturado. Posterior: pendiente, "Por importe".
   - Un remito con **importe 0** (solo renglones de promoción) no se empareja:
     que dos cosas valgan 0 no prueba que una facture a la otra.
3. **Sin factura:** pendiente, sin datos de factura, con su importe (0 si es
   solo de promociones).

| Cómo se identificó | Cuándo |
| --- | --- |
| Vinculado en Tango | La última factura vinculada en `GVA54` es posterior al Hasta |
| Por importe | Sin vínculo; la factura libre del mismo cliente e importe que le tocó es posterior al Hasta |
| (vacío) | Sin vínculo y sin factura que le toque: sigue sin factura hoy |

### Mejoras respecto del SP

| # | El SP | El reporte |
| --- | --- | --- |
| 1 | Filtra por `STA14.FECHA_MOV` y deja afuera los remitos sin `GVA106` | Filtra por la fecha del remito, y sin `GVA106` usa `FECHA_MOV` |
| 2 | Compara contra **todas** las facturas del período, también las que Tango ya vinculó a otro remito | Las facturas vinculadas en `GVA54` **no entran al pozo**: una factura que Tango asignó no da por facturado a otro remito |
| 3 | Cualquier factura del mismo cliente e importe tapa a **todos** los remitos de ese importe, sea anterior o posterior | **Uno a uno**: cada factura cubre un solo remito, y tiene que ser del mismo día o posterior |
| 4 | Toma cualquier comprobante de `GVA12`: notas de crédito y débito, recibos, `ZC1`/`ZD1` | **Solo `FAC`**: una nota de crédito por el mismo importe no factura un remito |
| 5 | Une `GVA54` con `GVA12` solo por `N_COMP`; un remito en 0 se empareja con cualquier comprobante en 0 | Une por `T_COMP_V` + `N_COMP`; un importe 0 no se empareja |

**Paridad.** `tests/test_remitos_pendientes.php` replica el `SELECT` del SP
(sin el mail) para los últimos 60 días con Hasta = hoy, y compara sus
pendientes con los "sin factura" del reporte. Cada diferencia tiene que tener
uno de estos motivos, y la prueba informa cuántas hay de cada uno. El 21/10/2026
el SP daba 2 y el reporte 3. La diferencia era un remito sin `GVA106` (mejora
1).

### Límites conocidos

- **Remitos anteriores al Desde.** Un remito anterior al Desde que siga
  pendiente no entra al emparejamiento, y su factura podría quedar asignada a
  un remito del período con el mismo cliente e importe. Es raro (mismo
  cliente, mismo importe exacto, remitos de distintos períodos), solo cambia el
  detalle de un remito, y el reporte lo muestra como "Por importe", nunca como
  seguro.
- **Importes exactos.** La comparación es exacta, como en el SP. Pero la
  factura a veces se emite a otro precio unitario que el remito (por ejemplo,
  14.089,44 en el remito y 14.089,50 en la factura): de los remitos vinculados
  1 a 1 con su factura desde el 01/08/2024, el 41% no coincide al centavo. Por
  eso un remito **sin vínculo** que se facturó a otro precio sale "Sin
  factura". Los vinculados no tienen el problema.
- **Facturas anteriores al remito.** Hay remitos vinculados en Tango a una
  factura con fecha **anterior** al remito (121 vínculos desde el 01/08/2024).
  En los vinculados la regla los cubre igual: si la factura es del Hasta o
  antes, el remito está facturado. En el emparejamiento por importe se
  mantiene que la factura sea del mismo día o posterior al remito.
- **Remitos con varias facturas.** 155 remitos desde el 01/08/2024 tienen 2 o 3
  facturas vinculadas, en 40 con fechas distintas (hasta 119 días entre la
  primera y la última). Manda la última.
- **Por importe no es seguro.** Una factura libre del mismo cliente e importe
  puede ser de mucho después: en el contable anterior, el único "Por importe"
  empareja un remito de enero con una factura de septiembre. Por eso se marca
  distinto que "Vinculado en Tango".

### Lo que se ve en pantalla

- **Tarjetas:** remitos pendientes al Hasta (cantidad e importe), de esos
  cuántos ya se facturaron después (cantidad e importe), cuántos siguen sin
  factura hoy (cantidad e importe) y clientes con pendientes.
- **Resumen por tipo de cliente:** tipo, cantidad de remitos e importe, con
  total.
- **Resumen por cliente:** código, razón social, tipo, cantidad, importe
  pendiente, cuántos ya se facturaron después y la fecha del remito más viejo,
  de mayor a menor importe.
- **Leyenda:** "Remitos del DD/MM/AAAA al DD/MM/AAAA, pendientes de facturar al
  DD/MM/AAAA". Si el Hasta es hoy, aclara que el resultado es al día de hoy.
- Sin pendientes: un mensaje que lo dice.

Importes con miles y 2 decimales, en pantalla y en el Excel.

Una consulta de un año contable tarda unos 10 s: unos 8 s para traer los
remitos y unos 2 s para validar los clientes. Las uniones con `GVA106` y `STA20`
se agrupan de una vez: buscarlas remito por remito tardaba más de dos minutos.

### El Excel

Botón "Descargar Excel", habilitado después de consultar si hay pendientes.
Usa **los filtros de la última consulta**, los que devolvió el servidor ya
validados. Se arma con SheetJS desde `RemitosController?action=detalle`, con
las funciones comunes de `Js/excel.js`.

- Nombre: `Remitos_Pendientes_AAAAMMDD_AAAAMMDD.xlsx` (desde y hasta).
- **Encabezado:** la leyenda, el período y la fecha de corte, el tipo y los
  clientes, el usuario del padrón y la fecha y hora de generación.
- **Hoja "Detalle",** una fila por remito pendiente: Fecha remito, Tipo de
  cliente, Código cliente, Razón social, Remito, Importe neto, Días al corte
  (Hasta − fecha del remito), Facturado después: Nº factura, Facturado
  después: fecha, y Cómo se identificó. Las fechas son fechas de Excel, el
  importe un número con `#,##0.00`, y hay autofiltro en la fila de títulos.
- **Hoja "Resumen":** las dos tablas de la pantalla.

---

## Pruebas

```
php reportes/tests/run.php            todo
php reportes/tests/run.php auth       solo los archivos que contengan "auth"
```

En XAMPP, `php` es `C:\xampp\php\php.exe`. El arnés es el de
`comercioExterior/tests` (y el del Cashflow): PHP plano, sin Composer.

| Archivo | Qué prueba |
| --- | --- |
| `test_auth_reportes.php` | Falla cerrada sin usuario; la regla pura (escribir implica leer, el administrador entra a todo); que todo `case` de los controllers esté declarado como lectura o escritura y que cada uno llame a `autorizar()` antes del `switch`; que las claves de `sql/reportes_permisos.sql` sean **exactamente** las de `AuthReportes::claves()`; que cada pestaña tenga su HTML y su JS |
| `test_parametros.php` | La validación del factor (0,8 y 7 se rechazan con un mensaje que lo explica, hasta 4 decimales, coma o punto, sin miles); el formato con coma; que la escritura use transacción, auditoría e historial; el script de parámetros |
| `test_valorizacion.php` | La clasificación de "Origen del costo" y el costo unitario (sin `PRECIO_U_L` da 0, salvo los `OU` con lista 20, que van a lista 20 / 3 sin nacionalización); los filtros; el resumen; la forma de la consulta; la pantalla y el Excel, leyendo el JS |
| `test_remitos_pendientes.php` | Los rangos del período (31/07, 01/08 y 01/01); los filtros; la clasificación caso por caso (vinculado antes y después del corte, varias vinculadas, por importe antes y después, dos remitos y una factura, factura anterior al remito, otro cliente, un centavo, importe 0); que las vinculadas no entren al pozo; el resumen y la leyenda; la forma de las consultas; los permisos; la pantalla y el Excel, leyendo el JS |
| `test_readme.php` | Que este README nombre lo que existe: claves, orígenes del costo, valores de "Cómo se identificó", scripts y parámetros |

**Contra la base, solo lectura y opcional:** si hay conexión, las pruebas
comparan el total histórico del último cierre con `SUM(VALORIZACION)`, exigen
una fila por sector + sucursal + artículo y recalculan en PHP cada fila del
modo actual con los insumos que devolvió el SQL, exigiendo el mismo origen y la
misma valorización. En remitos, revisan el contable anterior (fechas dentro
del período, ninguna factura del pozo vinculada, los "facturado después"
posteriores al corte, el filtro de tipo) y hacen la **paridad con el SP** (ver
*Remitos pendientes de facturar*). Sin conexión se saltean solas. Ninguna
escribe.

---

## Pendientes

- `AuthReportes` es una copia de la lógica de `AuthCashflow` y
  `AuthInformeEconomico`. Lo que comparten (padrón, regla de administrador)
  podría ir a una clase común, en otra rama.
- La valorización es solo de Argentina (LAKERS SA): la tabla histórica no tiene
  equivalente en Uruguay. Los remitos pendientes también son solo de la base
  central.
