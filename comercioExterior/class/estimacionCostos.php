<?php

class EstimacionCostos
{
    private $cid_central;

    private $encabezado;

    /** 'central' o 'uy': decide la base y qué columnas tiene el padrón de conceptos */
    private $entorno;

    /** @var string[] Lo que el último listado no pudo leer y la pantalla tiene que decir */
    private $avisos = [];

    /**
     * @param string|null $entorno 'central' o 'uy'. Sin él, el de la sesión,
     *                             que es lo que la aplicación hizo siempre.
     *
     * Con él, la clase no mira la sesión para nada. Lo necesita el
     * recálculo que dispara el cashflow de Finanzas: las dos aplicaciones
     * comparten origen y cookie, así que la sesión de ese pedido dice en qué
     * entorno estuvo alguien por última vez en Comex, no a qué base pertenece
     * el contenedor.
     */
    function __construct($entorno = null) {
        require_once __DIR__.'/../../class/conexion.php';
        require_once __DIR__.'/encabezado.php';
        require_once __DIR__.'/AlicuotasVigencia.php';
        require_once __DIR__.'/CalculoEstimacion.php';
        require_once __DIR__.'/VisibilidadContenedor.php';
        $cid = new Conexion();
        /* La sesión se abre SÓLO si hace falta leerla. Con el entorno
           explícito no se toca: además de no necesitarla, abrirla bloquearía
           el archivo de sesión que el cashflow comparte por la cookie mientras
           dura el recálculo. */
        if ($entorno === null) {
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            $entorno = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
        }
        $this->entorno     = ($entorno === 'uy') ? 'uy' : 'central';
        $this->cid_central = $cid->conectar($this->entorno);
        $this->encabezado  = new Encabezado($this->entorno);
    }

    /** El entorno con el que se construyó: 'central' o 'uy' */
    public function entorno() {
        return $this->entorno;
    }

    /** Lo que el último listado no pudo leer, para que la pantalla lo diga */
    public function avisos() {
        return $this->avisos;
    }

    /**
     * Listado de despachos para PCI (Proyección de Costos de Importación).
     * Solo retorna OCs PRINCIPALES (ID_PADRE IS NULL). Las hijas se ocultan.
     * Incluye OCS_VINCULADAS y CANT_OCS para el badge "+N OCs".
     */
    public function listarDespachosConEstado() {
        $sql = "SELECT
                    E.ID,
                    E.FECHA_MOV,
                    E.PROVEEDOR,
                    E.CONTENEDOR,
                    E.MATERIAL,
                    E.ORDEN_COMPRA,
                    E.VALOR_FOB_DOLAR,
                    STUFF((
                        SELECT ', ' + LTRIM(RTRIM(H.ORDEN_COMPRA))
                        FROM RO_T_IMPORTACIONES_ENCABEZADO H
                        WHERE H.ID_PADRE = E.ID
                        FOR XML PATH(''), TYPE
                    ).value('.', 'NVARCHAR(MAX)'), 1, 2, '') AS OCS_VINCULADAS,
                    1 + (SELECT COUNT(*) FROM RO_T_IMPORTACIONES_ENCABEZADO H WHERE H.ID_PADRE = E.ID) AS CANT_OCS,
                    CASE
                        WHEN EXISTS (
                            SELECT 1 FROM RO_T_IMPORTACIONES_ESTIMACION_DETALLE D
                            WHERE D.ID_MG = E.ID AND D.CONFIRMADO = 1
                        ) THEN 'CONFIRMADO'
                        WHEN EXISTS (
                            SELECT 1 FROM RO_T_IMPORTACIONES_ESTIMACION_DETALLE D
                            WHERE D.ID_MG = E.ID
                        ) THEN 'BORRADOR'
                        ELSE 'PENDIENTE'
                    END AS ESTADO
                FROM RO_T_IMPORTACIONES_ENCABEZADO E
                LEFT JOIN RO_T_IMPORTACIONES_DETALLE F ON E.ID = F.ID_MG
                WHERE E.FECHA_MOV >= DATEADD(MONTH, -6, GETDATE())
                  AND F.ID_MG IS NULL
                  AND E.ID_PADRE IS NULL
                ORDER BY E.FECHA_MOV DESC";
        
        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);
            
            if ($stmt === false) {
                error_log("Error en listarDespachosConEstado: " . print_r(sqlsrv_errors(), true));
                return [];
            }
            
            $despachos = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                // Convertir objetos DateTime a strings
                if (isset($row['FECHA_MOV']) && is_object($row['FECHA_MOV'])) {
                    $row['FECHA_MOV'] = $row['FECHA_MOV']->format('Y-m-d');
                }
                $despachos[] = $row;
            }
            
            return $despachos;
            
        } catch (Exception $e) {
            error_log('Error en listarDespachosConEstado: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Listado de despachos para Gestión de Despachos.
     * Retorna TODAS las OCs (principales e hijas). Las hijas incluyen
     * ID_PADRE, OCS_VINCULADAS (para principales) y ORDEN_COMPRA_PADRE.
     *
     * QUÉ CONTENEDORES SE VEN cambió con feature/comex-visibilidad-saldo: un
     * contenedor ya no sale de la grilla por tener los costos cargados, sino
     * por tener los costos cargados Y el FOB pagado. Y la ventana de 6 meses
     * deja de esconder lo que todavía se le debe al proveedor. La regla
     * completa, y por qué, está en VisibilidadContenedor.
     *
     * TIENE_COSTOS viaja en cada fila para la etiqueta "Costos cargados": un
     * contenedor que sigue en la grilla con los costos ya cargados está ahí
     * por el saldo, y sin la marca se confunde con uno al que le faltan.
     * ESTADO_PAGO viaja por lo mismo, aunque hoy la pantalla no lo muestre.
     *
     * La regla se evalúa en una tabla derivada para escribir cada expresión
     * una vez: el WHERE de afuera filtra por columnas ya calculadas.
     */
    public function listarDespachosTodosConPadre() {
        $this->avisos = [];

        $hayPagos = VisibilidadContenedor::hayTablaPagos($this->cid_central);
        $aviso = VisibilidadContenedor::avisoSinPagos($hayPagos);
        if ($aviso !== null) {
            $this->avisos[] = $aviso;
        }

        $grupo = 'COALESCE(E.ID_PADRE, E.ID)';

        /* Sin la tabla de pagos no hay nada pagado: cero, y el aviso de arriba
           lo dice. Mismo recurso que Comex::saldoSelect() en el cashflow. */
        $applyPagos = $hayPagos
            ? "OUTER APPLY (SELECT ISNULL(SUM(PG0.MONTO), 0) MONTO
                            FROM " . VisibilidadContenedor::TABLA_PAGOS . " PG0
                            WHERE PG0.ID_ENCABEZADO = " . $grupo . ") PG"
            : "OUTER APPLY (SELECT CAST(0 AS DECIMAL(18,2)) MONTO) PG";

        $sql = "SELECT T.*
                FROM (
                    SELECT
                        E.ID,
                        E.FECHA_MOV,
                        E.PROVEEDOR,
                        E.CONTENEDOR,
                        E.MATERIAL,
                        E.ORDEN_COMPRA,
                        E.VALOR_FOB_DOLAR,
                        E.ID_PADRE,
                        CASE WHEN E.ID_PADRE IS NULL THEN
                            STUFF((
                                SELECT ', ' + LTRIM(RTRIM(H.ORDEN_COMPRA))
                                FROM RO_T_IMPORTACIONES_ENCABEZADO H
                                WHERE H.ID_PADRE = E.ID
                                FOR XML PATH(''), TYPE
                            ).value('.', 'NVARCHAR(MAX)'), 1, 2, '')
                        END AS OCS_VINCULADAS,
                        (SELECT P.ORDEN_COMPRA
                         FROM RO_T_IMPORTACIONES_ENCABEZADO P
                         WHERE P.ID = E.ID_PADRE) AS ORDEN_COMPRA_PADRE,
                        CASE WHEN " . VisibilidadContenedor::sqlTieneCostos($grupo) . "
                             THEN 1 ELSE 0 END AS TIENE_COSTOS,
                        " . VisibilidadContenedor::sqlEstadoPago(
                                'ISNULL(PR.VALOR_FOB_DOLAR, E.VALOR_FOB_DOLAR)', 'PG.MONTO') . " AS ESTADO_PAGO,
                        ISNULL(PR.FECHA_MOV, E.FECHA_MOV) AS FECHA_MOV_GRUPO
                    FROM RO_T_IMPORTACIONES_ENCABEZADO E
                    OUTER APPLY (SELECT PR0.VALOR_FOB_DOLAR, PR0.FECHA_MOV
                                 FROM RO_T_IMPORTACIONES_ENCABEZADO PR0
                                 WHERE PR0.ID = " . $grupo . ") PR
                    " . $applyPagos . "
                ) T
                WHERE " . VisibilidadContenedor::sqlVisibleEnGestion(
                        'T.TIENE_COSTOS', 'T.ESTADO_PAGO', 'T.FECHA_MOV_GRUPO') . "
                ORDER BY T.FECHA_MOV DESC";

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);

            if ($stmt === false) {
                error_log("Error en listarDespachosTodosConPadre: " . print_r(sqlsrv_errors(), true));
                return [];
            }

            $despachos = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                foreach (['FECHA_MOV', 'FECHA_MOV_GRUPO'] as $campo) {
                    if (isset($row[$campo]) && is_object($row[$campo])) {
                        $row[$campo] = $row[$campo]->format('Y-m-d');
                    }
                }
                // Un 0/1 de SQL Server puede llegar como string: el JS lo
                // necesita booleano, y "0" sería verdadero.
                $row['TIENE_COSTOS'] = intval($row['TIENE_COSTOS']) === 1;
                $despachos[] = $row;
            }

            return $despachos;

        } catch (Exception $e) {
            error_log('Error en listarDespachosTodosConPadre: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener todos los conceptos de estimación configurados.
     *
     * Si se pasa la fecha de nacionalización del contenedor, los valores no
     * salen del padrón sino de la vigencia que regía A ESA FECHA: una
     * operación nacionalizada en marzo usa la alícuota de marzo, aunque desde
     * julio rija otra. Ver AlicuotasVigencia.
     *
     * Sin fecha -o sin vigencia que la cubra, o sin el script 09 corrido- se
     * usa el padrón, que es lo que la aplicación hacía antes de esta tanda.
     *
     * @param string|null $fechaNacionalizacion FECHA_DESP_ADU del contenedor
     */
    public function obtenerConceptos($fechaNacionalizacion = null) {
        $db = $this->entorno;
        if ($db === 'uy') {
            $sql = "SELECT 
                        ID_CE,
                        CONCEPTO,
                        TIPO_VALOR,
                        VALOR_DEFAULT_1,
                        VALOR_DEFAULT_2,
                        ID_REF_CONCEPTO
                    FROM RO_T_CONCEPTOS_ESTIMACION_COMEX
                    ORDER BY ID_CE";
        } else {
            $sql = "SELECT 
                        ID_CE,
                        CONCEPTO,
                        TIPO_VALOR,
                        VALOR_DEFAULT_1,
                        VALOR_DEFAULT_2,
                        NULL AS ID_REF_CONCEPTO
                    FROM RO_T_CONCEPTOS_ESTIMACION_COMEX
                    ORDER BY ID_CE";
        }
        
        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);
            
            if ($stmt === false) {
                error_log("Error en obtenerConceptos: " . print_r(sqlsrv_errors(), true));
                return [];
            }
            
            $conceptos = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $conceptos[] = $row;
            }

            $resolucion = AlicuotasVigencia::resolver($this->cid_central, $fechaNacionalizacion);

            return AlicuotasVigencia::aplicarAConceptos($conceptos, $resolucion);

        } catch (Exception $e) {
            error_log('Error en obtenerConceptos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Resumen de qué vigencia se usó, para que la pantalla lo pueda decir.
     * Sin esto, trece números en pantalla son indistinguibles entre "salió de
     * la alícuota que regía ese día" y "salió del padrón porque no había".
     */
    public function resumenVigencia($fechaNacionalizacion) {
        $resolucion = AlicuotasVigencia::resolver($this->cid_central, $fechaNacionalizacion);

        return [
            'disponible' => AlicuotasVigencia::disponible($this->cid_central),
            'fecha'      => $resolucion['fecha'],
            'aplicada'   => $resolucion['aplicada'],
            'conceptos'  => count($resolucion['valores']),
        ];
    }

    /**
     * Qué conceptos tienen guardada una alícuota distinta de la que rige para
     * la fecha de nacionalización del contenedor.
     *
     * QUÉ PROBLEMA HACE VISIBLE
     * -------------------------
     * Al abrir una estimación, el detalle guardado en
     * RO_T_IMPORTACIONES_ESTIMACION_DETALLE SIEMPRE le gana a la alícuota que
     * acaba de resolver AlicuotasVigencia -es el `valorExistente ? ... : ...`
     * de generarFormularioConceptos()-. Eso es correcto como default: un
     * importe que alguien revisó no se pisa solo. Pero hasta ahora la pantalla
     * tampoco DECÍA que los dos números no coincidían, así que una estimación
     * calculada con una alícuota que ya no rige se veía igual que una al día.
     *
     * Esto no cambia ningún valor: sólo informa la diferencia. Recalcular es
     * una decisión del usuario, con su botón, y sólo sobre estimaciones sin
     * confirmar.
     *
     * POR QUÉ SE COMPARA ACÁ Y NO EN EL NAVEGADOR
     * ------------------------------------------
     * Por el DESPACHANTE. cargarEstimacion.php pisa VALOR_DEFAULT_1 de ese
     * concepto -y el del detalle guardado- con VALOR_DEFAULT_2 cuando el
     * despachante es Farre, porque los dos honorarios viven en las dos columnas
     * del mismo concepto. Una comparación hecha antes de ese ajuste marca como
     * desviadas TODAS las operaciones de Farre: al 21/09/2026 son 51 de 60 en
     * central. Comparando después del ajuste -que es lo que recibe esta
     * función- el falso positivo no existe.
     *
     * NULL CONTRA UN VALOR SÍ ES UNA DIFERENCIA, y es el caso que motivó todo
     * esto: el detalle de la OC 0000100015881 tiene VALOR_DEFAULT_1 en NULL
     * para IVA Adicional mientras el padrón y la vigencia dicen 0,20, así que
     * ese impuesto se calcula en cero. NULL CONTRA NULL no lo es: es el estado
     * normal de Antidumping, que no tiene alícuota cargada en ningún lado.
     *
     * SE COMPARA COMO NÚMERO Y NO COMO TEXTO. El detalle es DECIMAL(18,6) y el
     * padrón DECIMAL(18,4): '0.2000' y '0.200000' son la misma alícuota y dos
     * cadenas distintas.
     *
     * @param array $conceptos Conceptos CON la vigencia ya resuelta y con el
     *                         ajuste de despachante aplicado
     * @param array|null $estimacionExistente Detalle guardado, con el mismo ajuste
     * @return array [['ID_CE'=>, 'CONCEPTO'=>, 'TIPO_VALOR'=>,
     *                 'GUARDADO'=>float|null, 'VIGENTE'=>float|null], ...]
     */
    public static function desviosDeAlicuota($conceptos, $estimacionExistente)
    {
        if (empty($estimacionExistente) || empty($conceptos)) {
            return [];
        }

        /* Tolerancia de comparación. No es un epsilon de punto flotante
           cualquiera: DECIMAL(18,6) es la escala más fina de las dos columnas,
           así que cualquier diferencia real es de al menos 1e-6. Por debajo de
           la mitad de eso no hay diferencia que un humano pueda haber cargado. */
        $tolerancia = 0.0000005;

        $porId = [];
        foreach ($estimacionExistente as $fila) {
            $porId[(int) $fila['ID_CE']] = $fila;
        }

        $desvios = [];

        foreach ($conceptos as $concepto) {
            $idCe = (int) $concepto['ID_CE'];

            if (!isset($porId[$idCe])) {
                continue;   // concepto sin fila guardada: no hay nada que comparar
            }

            $guardado = $porId[$idCe]['VALOR_DEFAULT_1'];
            $vigente  = $concepto['VALOR_DEFAULT_1'];

            $hayGuardado = ($guardado !== null && $guardado !== '');
            $hayVigente  = ($vigente  !== null && $vigente  !== '');

            if (!$hayGuardado && !$hayVigente) {
                continue;   // los dos vacíos: es el caso normal de Antidumping
            }

            if ($hayGuardado && $hayVigente
                && abs((float) $guardado - (float) $vigente) < $tolerancia) {
                continue;   // misma alícuota escrita con distinta escala
            }

            $desvios[] = [
                'ID_CE'      => $idCe,
                'CONCEPTO'   => $concepto['CONCEPTO'],
                'TIPO_VALOR' => $concepto['TIPO_VALOR'],
                'GUARDADO'   => $hayGuardado ? (float) $guardado : null,
                'VIGENTE'    => $hayVigente  ? (float) $vigente  : null,
            ];
        }

        return $desvios;
    }

    /**
     * Obtener estimación existente para un despacho
     */
    public function obtenerEstimacion($idMg) {
        $idMg = $this->encabezado->resolverIdPrincipal($idMg);
        $db = $this->entorno;
        
        if ($db === 'uy') {
            $sql = "SELECT
                        D.ID,
                        D.ID_MG,
                        D.ID_CE,
                        D.VALOR_DEFAULT_1,
                        D.VALOR_DEFAULT_2,
                        D.IMPORTE,
                        D.CONFIRMADO,
                        D.FECHA_MOD,
                        C.CONCEPTO,
                        C.TIPO_VALOR,
                        C.ID_REF_CONCEPTO
                    FROM RO_T_IMPORTACIONES_ESTIMACION_DETALLE D
                    INNER JOIN RO_T_CONCEPTOS_ESTIMACION_COMEX C ON D.ID_CE = C.ID_CE
                    WHERE D.ID_MG = ?
                    ORDER BY C.ID_CE";
        } else {
            $sql = "SELECT
                        D.ID,
                        D.ID_MG,
                        D.ID_CE,
                        D.VALOR_DEFAULT_1,
                        D.VALOR_DEFAULT_2,
                        D.IMPORTE,
                        D.CONFIRMADO,
                        D.FECHA_MOD,
                        C.CONCEPTO,
                        C.TIPO_VALOR,
                        NULL AS ID_REF_CONCEPTO
                    FROM RO_T_IMPORTACIONES_ESTIMACION_DETALLE D
                    INNER JOIN RO_T_CONCEPTOS_ESTIMACION_COMEX C ON D.ID_CE = C.ID_CE
                    WHERE D.ID_MG = ?
                    ORDER BY C.ID_CE";
        }
        
        try {
            $params = array($idMg);
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                error_log("Error en obtenerEstimacion: " . print_r(sqlsrv_errors(), true));
                return null;
            }
            
            $estimacion = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                // Convertir DateTime
                if (isset($row['FECHA_MOD']) && is_object($row['FECHA_MOD'])) {
                    $row['FECHA_MOD'] = $row['FECHA_MOD']->format('Y-m-d H:i:s');
                }
                $estimacion[] = $row;
            }
            
            return count($estimacion) > 0 ? $estimacion : null;
            
        } catch (Exception $e) {
            error_log('Error en obtenerEstimacion: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener datos del despacho (encabezado).
     * Si el ID es de una OC hija, resuelve al principal y devuelve sus datos
     * junto con OCS_VINCULADAS y CANT_OCS para el header del editor PCI.
     */
    public function obtenerDespacho($idMg) {
        $idMg = $this->encabezado->resolverIdPrincipal($idMg);
        $sql = "SELECT
                    E.ID,
                    E.FECHA_MOV,
                    E.COD_PROVEE,
                    E.PROVEEDOR,
                    E.CONTENEDOR,
                    E.MATERIAL,
                    E.ORIGEN,
                    E.VALOR_FOB_DOLAR,
                    E.ORDEN_COMPRA,
                    E.DESPACHANTE,
                    E.FECHA_DESP_ADU,
                    STUFF((
                        SELECT ', ' + LTRIM(RTRIM(H.ORDEN_COMPRA))
                        FROM RO_T_IMPORTACIONES_ENCABEZADO H
                        WHERE H.ID_PADRE = E.ID
                        FOR XML PATH(''), TYPE
                    ).value('.', 'NVARCHAR(MAX)'), 1, 2, '') AS OCS_VINCULADAS,
                    1 + (SELECT COUNT(*) FROM RO_T_IMPORTACIONES_ENCABEZADO H WHERE H.ID_PADRE = E.ID) AS CANT_OCS
                FROM RO_T_IMPORTACIONES_ENCABEZADO E
                WHERE E.ID = ?";
        
        try {
            $params = array($idMg);
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                error_log("Error en obtenerDespacho: " . print_r(sqlsrv_errors(), true));
                return null;
            }
            
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            
            if ($row) {
                // Convertir DateTime
                if (isset($row['FECHA_MOV']) && is_object($row['FECHA_MOV'])) {
                    $row['FECHA_MOV'] = $row['FECHA_MOV']->format('Y-m-d');
                }
                // La fecha que decide qué alícuota aplica. Va en 'Y-m-d' y no
                // en 'd/m/Y' como las del formulario: acá se usa para comparar
                // contra vigencias, no para mostrar.
                if (isset($row['FECHA_DESP_ADU']) && is_object($row['FECHA_DESP_ADU'])) {
                    $row['FECHA_DESP_ADU'] = $row['FECHA_DESP_ADU']->format('Y-m-d');
                }
                return $row;
            }

            return null;

        } catch (Exception $e) {
            error_log('Error en obtenerDespacho: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Guardar o actualizar estimación completa
     */
    public function guardarEstimacion($idMg, $conceptos) {
        $idMg = $this->encabezado->resolverIdPrincipal($idMg);
        try {
            // Verificar si ya existe estimación
            $existente = $this->obtenerEstimacion($idMg);
            
            if ($existente) {
                // Actualizar registros existentes
                return $this->actualizarEstimacion($idMg, $conceptos);
            } else {
                // Insertar nuevos registros
                return $this->insertarEstimacion($idMg, $conceptos);
            }
            
        } catch (Exception $e) {
            error_log('Error en guardarEstimacion: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener parámetros dinámicos para concepto DESPACHANTE según despachante del despacho
     */
    private function obtenerParametrosDespachante($idMg, $idCe, &$valor1, &$valor2) {
        // Verificar si es el concepto DESPACHANTE
        $sqlCheck = "SELECT CONCEPTO FROM RO_T_CONCEPTOS_ESTIMACION_COMEX WHERE ID_CE = ?";
        $stmtCheck = sqlsrv_query($this->cid_central, $sqlCheck, array($idCe));
        
        if ($stmtCheck === false) {
            return false;
        }
        
        $rowCheck = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
        if (!$rowCheck || strcasecmp($rowCheck['CONCEPTO'], 'DESPACHANTE') !== 0) {
            return false; // No es el concepto DESPACHANTE, mantener valores originales
        }
        
        // Obtener despachante del despacho y valores del concepto.
        // FECHA_DESP_ADU viaja para resolver la vigencia unas líneas más abajo:
        // el honorario del despachante también es un valor que cambia en el
        // tiempo, y una estimación histórica tiene que usar el de su fecha.
        $sql = "SELECT
                    enc.DESPACHANTE,
                    enc.FECHA_DESP_ADU,
                    conc.VALOR_DEFAULT_1,
                    conc.VALOR_DEFAULT_2
                FROM RO_T_IMPORTACIONES_ENCABEZADO enc
                CROSS JOIN RO_T_CONCEPTOS_ESTIMACION_COMEX conc
                WHERE enc.ID = ?
                  AND conc.ID_CE = ?";
        
        $params = array($idMg, $idCe);
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        
        if ($stmt === false) {
            error_log("Error obteniendo parámetros despachante: " . print_r(sqlsrv_errors(), true));
            return false;
        }
        
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        
        if ($row) {
            $despachante = $row['DESPACHANTE'];

            $v1 = $row['VALOR_DEFAULT_1'];
            $v2 = $row['VALOR_DEFAULT_2'];

            // Si hay una vigencia que cubre la fecha de nacionalización, sus
            // valores ganan sobre los del padrón. Si no la hay, quedan los
            // del padrón: mismo criterio que obtenerConceptos().
            $resolucion = AlicuotasVigencia::resolver($this->cid_central, $row['FECHA_DESP_ADU']);
            if (isset($resolucion['valores'][intval($idCe)])) {
                $v1 = $resolucion['valores'][intval($idCe)]['VALOR_1'];
                $v2 = $resolucion['valores'][intval($idCe)]['VALOR_2'];
            }

            // Lógica de asignación según despachante
            if ($despachante === 'Farre') {
                // Farre usa VALOR_DEFAULT_2
                $valor1 = $v2;
                $valor2 = null;
            } else {
                // Laffitte o cualquier otro caso usa VALOR_DEFAULT_1
                $valor1 = $v1;
                $valor2 = null;
            }

            return true;
        }

        return false;
    }

    /**
     * Insertar nueva estimación
     */
    private function insertarEstimacion($idMg, $conceptos) {
        try {
            foreach ($conceptos as $concepto) {
                // Valores por defecto
                $valor1 = isset($concepto['valor_default_1']) ? $concepto['valor_default_1'] : null;
                $valor2 = isset($concepto['valor_default_2']) ? $concepto['valor_default_2'] : null;
                
                // Aplicar lógica dinámica para concepto DESPACHANTE
                $this->obtenerParametrosDespachante($idMg, $concepto['id_ce'], $valor1, $valor2);
                
                $sql = "INSERT INTO RO_T_IMPORTACIONES_ESTIMACION_DETALLE 
                        (ID_MG, ID_CE, VALOR_DEFAULT_1, VALOR_DEFAULT_2, IMPORTE, CONFIRMADO, FECHA_MOD)
                        VALUES (?, ?, ?, ?, ?, 0, GETDATE())";
                
                $params = array(
                    $idMg,
                    $concepto['id_ce'],
                    $valor1,
                    $valor2,
                    $concepto['importe']
                );
                
                $stmt = sqlsrv_query($this->cid_central, $sql, $params);
                
                if ($stmt === false) {
                    error_log("Error insertando concepto: " . print_r(sqlsrv_errors(), true));
                    return false;
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('Error en insertarEstimacion: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar estimación existente.
     *
     * NO GENERA FILAS DUPLICADAS: el UPDATE va por (ID_MG, ID_CE), que es la
     * clave natural de la estimación -un importe por concepto y contenedor-.
     * Guardar diez veces el mismo contenedor deja siempre trece filas.
     *
     * El INSERT de respaldo cubre el concepto que se creó DESPUÉS de que esta
     * estimación naciera: sin él, su importe se tipearía en pantalla, el
     * UPDATE no afectaría ninguna fila y el valor se perdería en silencio al
     * volver a abrir. Tampoco genera duplicados: solo inserta si no hay fila.
     *
     * CONFIRMADO no se toca. Editar los importes de un contenedor confirmado
     * no lo devuelve a borrador: sigue confirmado y con los valores nuevos.
     */
    private function actualizarEstimacion($idMg, $conceptos) {
        try {
            foreach ($conceptos as $concepto) {
                // Valores por defecto
                $valor1 = isset($concepto['valor_default_1']) ? $concepto['valor_default_1'] : null;
                $valor2 = isset($concepto['valor_default_2']) ? $concepto['valor_default_2'] : null;

                // Aplicar lógica dinámica para concepto DESPACHANTE
                $this->obtenerParametrosDespachante($idMg, $concepto['id_ce'], $valor1, $valor2);

                $sql = "UPDATE RO_T_IMPORTACIONES_ESTIMACION_DETALLE
                        SET VALOR_DEFAULT_1 = ?,
                            VALOR_DEFAULT_2 = ?,
                            IMPORTE = ?,
                            FECHA_MOD = GETDATE()
                        WHERE ID_MG = ? AND ID_CE = ?";

                $params = array(
                    $valor1,
                    $valor2,
                    $concepto['importe'],
                    $idMg,
                    $concepto['id_ce']
                );

                $stmt = sqlsrv_query($this->cid_central, $sql, $params);

                if ($stmt === false) {
                    error_log("Error actualizando concepto: " . print_r(sqlsrv_errors(), true));
                    return false;
                }

                if (sqlsrv_rows_affected($stmt) > 0) {
                    continue;
                }

                // El concepto todavía no tenía fila en esta estimación.
                // Hereda el CONFIRMADO del resto para no quedar como una fila
                // suelta en borrador dentro de una estimación confirmada.
                $sqlInsert = "INSERT INTO RO_T_IMPORTACIONES_ESTIMACION_DETALLE
                                (ID_MG, ID_CE, VALOR_DEFAULT_1, VALOR_DEFAULT_2,
                                 IMPORTE, CONFIRMADO, FECHA_MOD)
                              SELECT ?, ?, ?, ?, ?,
                                     ISNULL((SELECT TOP 1 CONFIRMADO
                                             FROM RO_T_IMPORTACIONES_ESTIMACION_DETALLE
                                             WHERE ID_MG = ?), 0),
                                     GETDATE()
                              WHERE NOT EXISTS (
                                  SELECT 1 FROM RO_T_IMPORTACIONES_ESTIMACION_DETALLE
                                  WHERE ID_MG = ? AND ID_CE = ?
                              )";

                $stmtInsert = sqlsrv_query($this->cid_central, $sqlInsert, array(
                    $idMg, $concepto['id_ce'], $valor1, $valor2, $concepto['importe'],
                    $idMg, $idMg, $concepto['id_ce']
                ));

                if ($stmtInsert === false) {
                    error_log("Error insertando concepto nuevo en estimación existente: "
                              . print_r(sqlsrv_errors(), true));
                    return false;
                }
            }

            return true;

        } catch (Exception $e) {
            error_log('Error en actualizarEstimacion: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Confirmar estimación (marcar como confirmada)
     */
    public function confirmarEstimacion($idMg) {
        $idMg = $this->encabezado->resolverIdPrincipal($idMg);
        $sql = "UPDATE RO_T_IMPORTACIONES_ESTIMACION_DETALLE
                SET CONFIRMADO = 1,
                    FECHA_MOD = GETDATE()
                WHERE ID_MG = ?";
        
        try {
            $params = array($idMg);
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                error_log("Error en confirmarEstimacion: " . print_r(sqlsrv_errors(), true));
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('Error en confirmarEstimacion: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si una estimación está confirmada
     */
    public function estaConfirmada($idMg) {
        $idMg = $this->encabezado->resolverIdPrincipal($idMg);
        $sql = "SELECT TOP 1 CONFIRMADO
                FROM RO_T_IMPORTACIONES_ESTIMACION_DETALLE
                WHERE ID_MG = ?";
        
        try {
            $params = array($idMg);
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                return false;
            }
            
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            
            return $row && $row['CONFIRMADO'] == 1;

        } catch (Exception $e) {
            error_log('Error en estaConfirmada: ' . $e->getMessage());
            return false;
        }
    }

    /* ======================================================================
       LA ESTIMACIÓN SIN PANTALLA: GENERARLA EN EL ALTA Y RECALCULARLA

       POR QUÉ. El cashflow de Finanzas -Crono Nacionalización- proyecta los
       gastos de nacionalización desde RO_T_IMPORTACIONES_ESTIMACION_DETALLE y
       no mira CONFIRMADO: lo que necesita es que la estimación EXISTA. Hasta
       feature/comex-visibilidad-saldo sólo existía si alguien entraba a PCI y
       guardaba, así que un contenedor recién dado de alta no tenía gastos de
       nacionalización en el tablero hasta que alguien se acordaba.

       LA CUENTA ES LA DE LA PANTALLA, portada a CalculoEstimacion. Lo que se
       graba es lo que grabaría editar-estimacion si se abriera el contenedor
       sin tocar nada y se apretara Guardar.
       ====================================================================== */

    /**
     * El ajuste de despachante de cargarEstimacion.php, como función.
     *
     * Los dos honorarios viven en las dos columnas del mismo concepto: Farre
     * usa VALOR_DEFAULT_2 y cualquier otro -Laffitte, o NULL en los
     * contenedores viejos- VALOR_DEFAULT_1. El parámetro 2 queda siempre en
     * NULL. Es la misma regla que obtenerParametrosDespachante() aplica al
     * grabar; está escrita acá para que la pantalla y el servidor partan de los
     * mismos conceptos.
     *
     * @param array       $conceptos   Lo que devuelve obtenerConceptos()
     * @param string|null $despachante RO_T_IMPORTACIONES_ENCABEZADO.DESPACHANTE
     * @return array
     */
    public static function ajustarDespachante(array $conceptos, $despachante)
    {
        foreach ($conceptos as &$concepto) {
            if (strcasecmp((string) $concepto['CONCEPTO'], 'DESPACHANTE') === 0) {
                if ($despachante === 'Farre') {
                    $concepto['VALOR_DEFAULT_1'] = $concepto['VALOR_DEFAULT_2'];
                }
                $concepto['VALOR_DEFAULT_2'] = null;
                break;
            }
        }
        unset($concepto);

        return $conceptos;
    }

    /**
     * Las filas que se graban, a partir del resultado de la cuenta.
     *
     * Es enviarEstimacion() más obtenerParametrosDespachante(): el navegador
     * manda `parseFloat(param) || null` y el servidor, al grabar, pisa los dos
     * parámetros del concepto DESPACHANTE con el honorario que corresponde al
     * despachante del contenedor. Hacer las dos cosas acá deja en la base lo
     * mismo que el guardado de siempre.
     *
     * @param array $resultado CalculoEstimacion::calcular()
     * @param array $conceptos Los mismos conceptos ajustados que entraron a la cuenta
     * @return array [['id_ce','concepto','valor_default_1','valor_default_2','importe','editable']]
     */
    public static function filasParaGrabar(array $resultado, array $conceptos)
    {
        $honorario = null;
        $idDespachante = null;
        foreach ($conceptos as $c) {
            if (strcasecmp((string) $c['CONCEPTO'], 'DESPACHANTE') === 0) {
                $idDespachante = intval($c['ID_CE']);
                $honorario = $c['VALOR_DEFAULT_1'];
                break;
            }
        }

        $filas = [];
        foreach ($resultado['filas'] as $f) {
            if ($idDespachante !== null && $f['id_ce'] === $idDespachante) {
                $f['valor_default_1'] = $honorario;
                $f['valor_default_2'] = null;
            }
            $filas[] = $f;
        }

        return $filas;
    }

    /**
     * Qué filas cambian entre lo calculado y lo guardado.
     *
     * UN CAMBIO DE PARÁMETRO TAMBIÉN ES UNA DIFERENCIA, aunque el importe dé
     * igual: es la alícuota con la que quedó calculada la estimación, y es lo
     * que desviosDeAlicuota() compara al abrir PCI.
     *
     * CON TOLERANCIA, y por motivos distintos en cada columna:
     *  - los parámetros, medio millonésimo: es la escala de DECIMAL(18,6) y la
     *    misma tolerancia de desviosDeAlicuota(). NULL contra un valor SÍ es
     *    una diferencia; NULL contra NULL no.
     *  - el importe, al centavo: IMPORTE es DECIMAL(18,2) y lo que se graba es
     *    el double de la cuenta, que la base redondea. Comparar el double
     *    contra el guardado daría diferencia siempre.
     *
     * Un concepto sin fila guardada se INSERTA: es uno que se creó después de
     * que esta estimación naciera, y es lo mismo que hace actualizarEstimacion()
     * al guardar desde PCI.
     *
     * Es pura: la prueban tests/test_estimacion_calculo.php sin base.
     *
     * @param array      $calculadas filasParaGrabar()
     * @param array|null $guardadas  obtenerEstimacion()
     * @return array [['id_ce','concepto','accion'=>'UPDATE'|'INSERT',
     *                 'campos'=>[columna=>['antes'=>, 'despues'=>]], 'fila'=>calculada]]
     */
    public static function diferencias(array $calculadas, $guardadas)
    {
        $porId = [];
        foreach ((array) $guardadas as $g) {
            $porId[intval($g['ID_CE'])] = $g;
        }

        $num = function ($v) {
            return ($v === null || $v === '') ? null : (float) $v;
        };

        $out = [];

        foreach ($calculadas as $c) {
            $id = intval($c['id_ce']);

            if (!isset($porId[$id])) {
                $out[] = [
                    'id_ce'    => $id,
                    'concepto' => $c['concepto'],
                    'accion'   => 'INSERT',
                    'campos'   => [
                        'VALOR_DEFAULT_1' => ['antes' => null, 'despues' => $num($c['valor_default_1'])],
                        'VALOR_DEFAULT_2' => ['antes' => null, 'despues' => $num($c['valor_default_2'])],
                        'IMPORTE'         => ['antes' => null, 'despues' => round((float) $c['importe'], 2)],
                    ],
                    'fila'     => $c,
                ];
                continue;
            }

            $g = $porId[$id];
            $campos = [];

            foreach (['VALOR_DEFAULT_1' => 'valor_default_1', 'VALOR_DEFAULT_2' => 'valor_default_2'] as $col => $clave) {
                $antes = $num($g[$col]);
                $despues = $num($c[$clave]);

                $distintos = ($antes === null) !== ($despues === null)
                    || ($antes !== null && abs($antes - $despues) >= 0.0000005);

                if ($distintos) {
                    $campos[$col] = ['antes' => $antes, 'despues' => $despues];
                }
            }

            /* CONTRA EL DOUBLE CRUDO, NO CONTRA round(). La base guarda lo que
               le manda el navegador -el double de la cuenta- redondeando el
               valor binario exacto, y round() de PHP pre-redondea a 15
               dígitos: 26,754999… lo guarda la base como 26,75 y round() lo
               lleva a 26,76. Comparar round() contra lo guardado inventaría
               una diferencia de un centavo en una de cada diez estimaciones
               (medido contra central el 01/10/2026). Medio centavo de
               distancia al crudo es "lo mismo redondeado", sin depender de
               cómo redondea cada lado. */
            $antes = $num($g['IMPORTE']);
            $crudo = (float) $c['importe'];
            if ($antes === null || abs($antes - $crudo) > 0.005 + 1e-9) {
                $campos['IMPORTE'] = ['antes' => $antes, 'despues' => round($crudo, 2)];
            }

            if (!empty($campos)) {
                $out[] = [
                    'id_ce'    => $id,
                    'concepto' => $c['concepto'],
                    'accion'   => 'UPDATE',
                    'campos'   => $campos,
                    'fila'     => $c,
                ];
            }
        }

        return $out;
    }

    /**
     * Si el grupo del contenedor ya tiene costos REALES cargados (A de la
     * regla de visibilidad), mirando el detalle de cualquier OC del grupo.
     */
    public function tieneCostosReales($idMg)
    {
        $idPrincipal = $this->encabezado->resolverIdPrincipal($idMg);

        $stmt = sqlsrv_query($this->cid_central,
            "SELECT CASE WHEN " . VisibilidadContenedor::sqlTieneCostos('?') . " THEN 1 ELSE 0 END AS T",
            [$idPrincipal]);

        if ($stmt === false) {
            throw new Exception('No se pudo leer si el contenedor tiene costos cargados: '
                . print_r(sqlsrv_errors(), true));
        }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        return $row && intval($row['T']) === 1;
    }

    /**
     * Los conceptos con los que la pantalla abriría este contenedor sin
     * estimación guardada: la vigencia a su FECHA_DESP_ADU -con el padrón como
     * respaldo- y el ajuste de despachante. Es lo que hace cargarEstimacion.php.
     */
    private function conceptosPara(array $despacho)
    {
        $fecha = isset($despacho['FECHA_DESP_ADU']) ? $despacho['FECHA_DESP_ADU'] : null;

        $conceptos = $this->obtenerConceptos($fecha);
        if (empty($conceptos)) {
            throw new Exception('No se pudieron leer los conceptos de estimación (RO_T_CONCEPTOS_ESTIMACION_COMEX).');
        }

        return self::ajustarDespachante($conceptos, $despacho['DESPACHANTE']);
    }

    /**
     * Arma la cuenta para un contenedor, leyendo lo que la pantalla leería.
     *
     * @param array $despacho  obtenerDespacho() de la principal
     * @param array $editables [ID_CE => importe] de los editables que se conservan
     * @return array ['conceptos' => ajustados, 'filas' => filasParaGrabar()]
     */
    private function calcularPara(array $despacho, array $editables = [], $conceptos = null)
    {
        if ($conceptos === null) {
            $conceptos = $this->conceptosPara($despacho);
        }

        $resultado = CalculoEstimacion::calcular([
            'entorno'   => $this->entorno,
            'fob'       => $despacho['VALOR_FOB_DOLAR'],
            'conceptos' => $conceptos,
            'editables' => $editables,
        ]);

        return [
            'conceptos' => $conceptos,
            'filas'     => self::filasParaGrabar($resultado, $conceptos),
            'totales'   => $resultado['totales'],
        ];
    }

    /**
     * Genera y confirma la estimación de un despacho recién dado de alta.
     *
     * Va en la OC PRINCIPAL -una sola por grupo-, nace CONFIRMADA y después se
     * edita desde PCI como cualquier otra.
     *
     * NO REVIERTE EL ALTA SI FALLA: el despacho ya existe y es lo que el
     * usuario vino a cargar. Devuelve el motivo para que el alta lo informe;
     * el contenedor queda PENDIENTE en PCI, que es como quedaba siempre.
     *
     * Las filas se insertan en UNA transacción: una estimación a medias se
     * vería en PCI como un borrador al que le faltan conceptos y el cashflow
     * sumaría sólo algunos.
     *
     * @return array ['generada' => bool, 'idPrincipal' => int, 'mensaje' => string]
     */
    public function generarEstimacionAlta($idMg)
    {
        $idPrincipal = $this->encabezado->resolverIdPrincipal($idMg);

        try {
            $despacho = $this->obtenerDespacho($idPrincipal);
            if (!$despacho) {
                throw new Exception('No se encontró el despacho ' . intval($idMg) . '.');
            }

            if ($this->obtenerEstimacion($idPrincipal)) {
                return [
                    'generada'    => false,
                    'idPrincipal' => $idPrincipal,
                    'mensaje'     => 'El despacho ya tenía estimación: no se generó otra.',
                ];
            }

            $calculo = $this->calcularPara($despacho);

            if (sqlsrv_begin_transaction($this->cid_central) === false) {
                throw new Exception('No se pudo abrir la transacción.');
            }

            try {
                foreach ($calculo['filas'] as $f) {
                    $stmt = sqlsrv_query($this->cid_central,
                        "INSERT INTO RO_T_IMPORTACIONES_ESTIMACION_DETALLE
                            (ID_MG, ID_CE, VALOR_DEFAULT_1, VALOR_DEFAULT_2, IMPORTE, CONFIRMADO, FECHA_MOD)
                         VALUES (?, ?, ?, ?, ?, 1, GETDATE())",
                        [$idPrincipal, $f['id_ce'], $f['valor_default_1'], $f['valor_default_2'],
                         (float) $f['importe']]);

                    if ($stmt === false) {
                        throw new Exception('Error al grabar el concepto ' . $f['concepto'] . ': '
                            . print_r(sqlsrv_errors(), true));
                    }
                }

                sqlsrv_commit($this->cid_central);
            } catch (Throwable $e) {
                sqlsrv_rollback($this->cid_central);
                throw $e;
            }

            return [
                'generada'    => true,
                'idPrincipal' => $idPrincipal,
                'mensaje'     => 'Se generó y confirmó la estimación de costos de importación.',
            ];

        } catch (Throwable $e) {
            error_log('[generarEstimacionAlta] ' . $e->getMessage());

            return [
                'generada'    => false,
                'idPrincipal' => $idPrincipal,
                'mensaje'     => 'No se pudo generar la estimación de costos de importación: '
                    . $e->getMessage() . ' El contenedor queda pendiente en PCI.',
            ];
        }
    }

    /**
     * Si entre dos lecturas de obtenerDespacho() cambió algo que mueve la
     * estimación: VALOR_FOB_DOLAR o FECHA_DESP_ADU de la principal.
     *
     * EL DESPACHANTE NO ESTÁ, y es una decisión: cambiar el despachante no
     * dispara el recálculo. Si cambia junto con el FOB o la fecha, el
     * recálculo lo toma, porque calcula con lo que hay.
     *
     * Comparar el valor -y no "se escribió la columna"- es lo que evita que
     * reabrir y guardar un despacho sin tocar nada aplique de rebote un cambio
     * de vigencia o de despachante.
     */
    public static function cambioDisparaRecalculo($antes, $despues)
    {
        if (!$antes || !$despues) {
            return false;
        }

        $fobAntes = ($antes['VALOR_FOB_DOLAR'] === null) ? null : (float) $antes['VALOR_FOB_DOLAR'];
        $fobDespues = ($despues['VALOR_FOB_DOLAR'] === null) ? null : (float) $despues['VALOR_FOB_DOLAR'];

        $cambioFob = ($fobAntes === null) !== ($fobDespues === null)
            || ($fobAntes !== null && abs($fobAntes - $fobDespues) >= 0.005);

        $cambioFecha = AlicuotasVigencia::normalizarFecha($antes['FECHA_DESP_ADU'])
            !== AlicuotasVigencia::normalizarFecha($despues['FECHA_DESP_ADU']);

        return $cambioFob || $cambioFecha;
    }

    /**
     * El disparador que usan las pantallas de Comercio Exterior: recalcula si
     * entre $antes -leído con obtenerDespacho() ANTES de grabar- y lo que hay
     * ahora cambió el FOB o la fecha de nacionalización.
     *
     * NUNCA LANZA. Lo llaman después de que la escritura principal ya se
     * grabó, y un recálculo fallido no puede convertir en error un guardado
     * que salió bien: devuelve el motivo para que la respuesta lo informe.
     *
     * @param int        $idMg  Cualquier OC del grupo
     * @param array|null $antes obtenerDespacho() antes de grabar
     * @return array|null null si no cambió nada que lo dispare
     */
    public function recalcularSiCambio($idMg, $antes)
    {
        try {
            $despues = $this->obtenerDespacho($idMg);

            if (!self::cambioDisparaRecalculo($antes, $despues)) {
                return null;
            }

            return $this->recalcularEstimacion($idMg);

        } catch (Throwable $e) {
            error_log('[recalcularSiCambio] ' . $e->getMessage());

            return [
                'recalculo'   => false,
                'motivo'      => 'ERROR',
                'idPrincipal' => null,
                'mensaje'     => 'El cambio se guardó, pero no se pudo recalcular la estimación de '
                    . 'costos de importación: ' . $e->getMessage(),
                'cambios'     => [],
            ];
        }
    }

    /**
     * Recalcula la estimación de un contenedor cuando cambian su FOB o su
     * fecha de nacionalización.
     *
     * QUÉ SE RECALCULA. Todo como si la pantalla se abriera sin estimación:
     * las alícuotas se re-resuelven con la fecha de nacionalización vigente y
     * los overrides manuales de los conceptos calculados SE PISAN. Lo único
     * que se conserva es el importe guardado de los conceptos editables
     * -CalculoEstimacion::EDITABLES-; su parámetro sí se actualiza.
     *
     * CUÁNDO NO HACE NADA, y lo dice:
     *  - TIENE_COSTOS: el grupo ya tiene costos reales. Una estimación es una
     *    proyección, y ahí ya no hay nada que proyectar.
     *  - SIN_ESTIMACION: los contenedores viejos. No se crea una: eso es del
     *    alta, y no hay backfill.
     *  - SIN_DIFERENCIAS: la cuenta da lo mismo que lo guardado.
     *
     * Graba SÓLO las filas que cambian, con su FECHA_MOD, y CONFIRMADO queda
     * como está. Todo en una transacción.
     *
     * SIEMPRE LA PRINCIPAL. Si se movió la fecha de una hija, el recálculo usa
     * la de la principal: la estimación es del contenedor, no de la OC.
     *
     * LO LLAMAN los cuatro lugares que escriben VALOR_FOB_DOLAR o
     * FECHA_DESP_ADU -ver REGLAS_CALCULO.md- y sólo cuando el valor cambió.
     * Cambiar el despachante NO lo dispara.
     *
     * @return array ['recalculo' => bool, 'motivo' => string, 'idPrincipal' => int,
     *                'mensaje' => string, 'cambios' => array]
     * @throws Exception si no se puede leer o grabar
     */
    public function recalcularEstimacion($idMg)
    {
        $idPrincipal = $this->encabezado->resolverIdPrincipal($idMg);

        $r = [
            'recalculo'   => false,
            'motivo'      => null,
            'idPrincipal' => $idPrincipal,
            'mensaje'     => '',
            'cambios'     => [],
        ];

        $despacho = $this->obtenerDespacho($idPrincipal);
        if (!$despacho) {
            throw new Exception('No se encontró el despacho ' . intval($idMg) . '.');
        }

        if ($this->tieneCostosReales($idPrincipal)) {
            $r['motivo'] = 'TIENE_COSTOS';
            $r['mensaje'] = 'El contenedor ya tiene costos de nacionalización cargados: la estimación no se recalcula.';
            return $r;
        }

        $guardadas = $this->obtenerEstimacion($idPrincipal);
        if (!$guardadas) {
            $r['motivo'] = 'SIN_ESTIMACION';
            $r['mensaje'] = 'El contenedor no tiene estimación: no hay nada que recalcular.';
            return $r;
        }

        $conceptos = $this->conceptosPara($despacho);

        $editables = [];
        foreach (CalculoEstimacion::idsEditables($conceptos, $this->entorno) as $idEditable) {
            foreach ($guardadas as $g) {
                if (intval($g['ID_CE']) === $idEditable && $g['IMPORTE'] !== null) {
                    $editables[$idEditable] = $g['IMPORTE'];
                }
            }
        }

        $calculo = $this->calcularPara($despacho, $editables, $conceptos);
        $cambios = self::diferencias($calculo['filas'], $guardadas);

        if (empty($cambios)) {
            $r['motivo'] = 'SIN_DIFERENCIAS';
            $r['mensaje'] = 'La estimación ya estaba al día: no cambió ningún importe ni alícuota.';
            return $r;
        }

        if (sqlsrv_begin_transaction($this->cid_central) === false) {
            throw new Exception('No se pudo abrir la transacción para recalcular la estimación.');
        }

        try {
            foreach ($cambios as $cambio) {
                $f = $cambio['fila'];

                if ($cambio['accion'] === 'UPDATE') {
                    $stmt = sqlsrv_query($this->cid_central,
                        "UPDATE RO_T_IMPORTACIONES_ESTIMACION_DETALLE
                            SET VALOR_DEFAULT_1 = ?, VALOR_DEFAULT_2 = ?, IMPORTE = ?, FECHA_MOD = GETDATE()
                          WHERE ID_MG = ? AND ID_CE = ?",
                        [$f['valor_default_1'], $f['valor_default_2'], (float) $f['importe'],
                         $idPrincipal, $f['id_ce']]);
                } else {
                    // Hereda el CONFIRMADO del resto, como actualizarEstimacion().
                    $stmt = sqlsrv_query($this->cid_central,
                        "INSERT INTO RO_T_IMPORTACIONES_ESTIMACION_DETALLE
                            (ID_MG, ID_CE, VALOR_DEFAULT_1, VALOR_DEFAULT_2, IMPORTE, CONFIRMADO, FECHA_MOD)
                         SELECT ?, ?, ?, ?, ?,
                                ISNULL((SELECT TOP 1 CONFIRMADO
                                        FROM RO_T_IMPORTACIONES_ESTIMACION_DETALLE
                                        WHERE ID_MG = ?), 0),
                                GETDATE()",
                        [$idPrincipal, $f['id_ce'], $f['valor_default_1'], $f['valor_default_2'],
                         (float) $f['importe'], $idPrincipal]);
                }

                if ($stmt === false) {
                    throw new Exception('Error al grabar el concepto ' . $f['concepto'] . ': '
                        . print_r(sqlsrv_errors(), true));
                }
            }

            sqlsrv_commit($this->cid_central);
        } catch (Throwable $e) {
            sqlsrv_rollback($this->cid_central);
            throw $e;
        }

        foreach ($cambios as &$cambio) {
            unset($cambio['fila']);
        }
        unset($cambio);

        $r['recalculo'] = true;
        $r['motivo'] = 'RECALCULADA';
        $r['cambios'] = $cambios;
        $r['mensaje'] = 'Se recalculó la estimación: ' . count($cambios)
            . (count($cambios) === 1 ? ' concepto cambió.' : ' conceptos cambiaron.');

        return $r;
    }
}
