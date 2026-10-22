<?php
/**
 * Parametros
 * Los parámetros de los reportes: RO_T_REPORTES_ADM_PARAMETROS (base central)
 * y su historial, RO_T_REPORTES_ADM_PARAMETROS_HIST. Ver
 * sql/reportes_parametros.sql.
 *
 * La pantalla se arma desde la tabla, pero la VALIDACIÓN vive acá, en REGLAS:
 * un parámetro que está en la tabla y no tiene regla se muestra y no se puede
 * modificar. Falla cerrada, como los permisos: sin saber qué rango tiene
 * sentido, aceptar cualquier número sería peor que no dejar cambiarlo.
 */
require_once __DIR__ . '/../../class/conexion.php';
require_once __DIR__ . '/AuthReportes.php';

class Parametros {

    const TABLA = 'RO_T_REPORTES_ADM_PARAMETROS';
    const TABLA_HIST = 'RO_T_REPORTES_ADM_PARAMETROS_HIST';

    /** La única clave que lee hoy un reporte: el modo actual de la valorización */
    const FACTOR_NAC_ESTIMADO = 'FACTOR_NAC_ESTIMADO';

    /** La escala de VALOR en la tabla: DECIMAL(10,4) */
    const DECIMALES_TABLA = 4;

    /**
     * Qué valores acepta cada parámetro.
     *
     *   min, max   inclusive
     *   decimales  los que se aceptan; no más que la escala de la tabla, para
     *              que lo que se graba sea exactamente lo que se escribió
     *
     * Para un parámetro nuevo: su fila en la tabla y su regla acá.
     */
    const REGLAS = [
        self::FACTOR_NAC_ESTIMADO => ['min' => 1, 'max' => 5, 'decimales' => 4]
    ];

    private $conn;

    /**
     * @param resource|null $conn Una conexión a central ya abierta (pruebas)
     */
    public function __construct($conn = null) {
        if ($conn === null) {
            $conn = (new Conexion())->conectar('central');
        }

        if (!$conn) {
            throw new Exception('No se pudo conectar con la base central.');
        }

        $this->conn = $conn;
    }

    /**
     * Valida y normaliza un valor escrito por el usuario. Pura.
     *
     * Acepta coma o punto como separador decimal -en la pantalla se escribe con
     * coma- y sin separador de miles: los rangos de estos parámetros no llegan
     * a mil, y "1.650" no se puede saber si es 1,65 o 1650. Ante la duda, se
     * rechaza.
     *
     * @param string $clave
     * @param mixed $crudo
     * @return array ['ok' => bool, 'valor' => float|null, 'message' => string]
     */
    public static function validarValor($clave, $crudo) {
        $regla = self::REGLAS[$clave] ?? null;

        if ($regla === null) {
            return self::rechazo('El parámetro ' . $clave . ' no tiene una regla de validación, así que no '
                . 'se puede modificar desde la pantalla.');
        }

        $texto = str_replace(' ', '', trim((string) $crudo));

        if ($texto === '') {
            return self::rechazo('Ingresá un valor.');
        }

        if (!preg_match('/^\d+(?:[.,](\d+))?$/', $texto, $m)) {
            return self::rechazo('"' . $crudo . '" no es un número válido. Escribilo con coma decimal, '
                . 'por ejemplo ' . self::formatear($regla['min'] + 0.5, 2) . '.');
        }

        $decimales = isset($m[1]) ? strlen($m[1]) : 0;

        if ($decimales > $regla['decimales']) {
            return self::rechazo('Se aceptan hasta ' . $regla['decimales'] . ' decimales y ingresaste '
                . $decimales . '.');
        }

        $valor = round((float) str_replace(',', '.', $texto), self::DECIMALES_TABLA);

        if ($valor < $regla['min'] || $valor > $regla['max']) {
            return self::rechazo('El valor tiene que ser mayor o igual a ' . self::formatear($regla['min'], 0)
                . ' y menor o igual a ' . self::formatear($regla['max'], 0) . '; ingresaste '
                . self::formatear($valor, 0) . '.');
        }

        return ['ok' => true, 'valor' => $valor, 'message' => ''];
    }

    private static function rechazo($mensaje) {
        return ['ok' => false, 'valor' => null, 'message' => $mensaje];
    }

    /**
     * Un valor con coma decimal, con al menos $minimo decimales y sin ceros de
     * más a la derecha: 1.65 -> "1,65", 1.5 -> "1,50", 1.2345 -> "1,2345".
     * Pura.
     *
     * @param float|string $valor
     * @param int $minimo
     * @return string
     */
    public static function formatear($valor, $minimo = 2) {
        $s = number_format((float) $valor, self::DECIMALES_TABLA, ',', '.');
        list($entero, $dec) = explode(',', $s);
        $dec = rtrim($dec, '0');

        if (strlen($dec) < $minimo) {
            $dec = str_pad($dec, $minimo, '0');
        }

        return $dec === '' ? $entero : $entero . ',' . $dec;
    }

    /** El texto de la regla, para mostrarlo al lado del valor */
    public static function textoRegla($clave) {
        $r = self::REGLAS[$clave] ?? null;

        if ($r === null) {
            return 'Sin regla de validación: no se puede modificar.';
        }

        return 'Entre ' . self::formatear($r['min'], 0) . ' y ' . self::formatear($r['max'], 0)
            . ', hasta ' . $r['decimales'] . ' decimales.';
    }

    /**
     * Todos los parámetros, para la pestaña.
     *
     * @return array
     */
    public function listar() {
        $sql = "SELECT CLAVE, NOMBRE, VALOR, DESCRIPCION, USUARIO_ALTA, FECHA_ALTA, USUARIO_MODIF, FECHA_MODIF
                FROM " . self::TABLA . "
                ORDER BY NOMBRE";

        $stmt = $this->consultar($sql);
        $filas = [];

        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // La última modificación; si nunca se tocó, el alta
            $usuario = $r['USUARIO_MODIF'] ?? $r['USUARIO_ALTA'];
            $fecha = $r['FECHA_MODIF'] ?? $r['FECHA_ALTA'];

            $filas[] = [
                'clave' => trim($r['CLAVE']),
                'nombre' => $r['NOMBRE'],
                'valor' => (float) $r['VALOR'],
                'valorTexto' => self::formatear($r['VALOR']),
                'descripcion' => $r['DESCRIPCION'],
                'regla' => self::textoRegla(trim($r['CLAVE'])),
                'editable' => isset(self::REGLAS[trim($r['CLAVE'])]),
                'usuario' => $usuario,
                'fecha' => $fecha instanceof DateTime ? $fecha->format('d/m/Y H:i') : null,
                'modificado' => $r['USUARIO_MODIF'] !== null
            ];
        }

        return $filas;
    }

    /**
     * El valor vigente de un parámetro, o null si no está en la tabla.
     *
     * @param string $clave
     * @return float|null
     */
    public function valor($clave) {
        $stmt = $this->consultar("SELECT VALOR FROM " . self::TABLA . " WHERE CLAVE = ?", [$clave]);
        $r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        return $r ? (float) $r['VALOR'] : null;
    }

    /**
     * Los cambios de un parámetro, del más nuevo al más viejo.
     *
     * @param string $clave
     * @return array
     */
    public function historial($clave) {
        $sql = "SELECT VALOR_ANTERIOR, VALOR_NUEVO, USUARIO_ALTA, FECHA_ALTA
                FROM " . self::TABLA_HIST . "
                WHERE CLAVE = ?
                ORDER BY FECHA_ALTA DESC, ID DESC";

        $stmt = $this->consultar($sql, [$clave]);
        $filas = [];

        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $filas[] = [
                'anterior' => $r['VALOR_ANTERIOR'] === null ? null : self::formatear($r['VALOR_ANTERIOR']),
                'nuevo' => self::formatear($r['VALOR_NUEVO']),
                'usuario' => $r['USUARIO_ALTA'],
                'fecha' => $r['FECHA_ALTA'] instanceof DateTime ? $r['FECHA_ALTA']->format('d/m/Y H:i') : null
            ];
        }

        return $filas;
    }

    /**
     * Guarda un valor nuevo: el UPDATE y su fila de historial, en UNA
     * transacción. Un cambio sin historial es justo lo que el historial tiene
     * que impedir, y un historial sin cambio diría algo que no pasó.
     *
     * El usuario y las fechas los pone el servidor. El valor anterior se lee
     * con UPDLOCK dentro de la transacción: si dos personas guardan a la vez,
     * la segunda ve lo que grabó la primera y el historial queda encadenado.
     *
     * @param string $clave
     * @param mixed $crudo Lo que escribió el usuario
     * @param string $usuario El username del padrón (AuthReportes)
     * @return array ['success' => bool, 'message' => string, 'cambio' => bool]
     */
    public function guardar($clave, $crudo, $usuario) {
        $usuario = AuthReportes::usuarioDeEscritura($usuario);
        $v = self::validarValor($clave, $crudo);

        if (!$v['ok']) {
            return ['success' => false, 'message' => $v['message'], 'cambio' => false];
        }

        if (sqlsrv_begin_transaction($this->conn) === false) {
            throw new Exception('No se pudo iniciar la transacción.');
        }

        try {
            $stmt = $this->consultar("SELECT VALOR FROM " . self::TABLA . " WITH (UPDLOCK, ROWLOCK) WHERE CLAVE = ?",
                [$clave]);
            $actual = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

            if (!$actual) {
                sqlsrv_rollback($this->conn);

                return ['success' => false, 'cambio' => false,
                    'message' => 'El parámetro ' . $clave . ' no existe en la tabla de parámetros.'];
            }

            $anterior = round((float) $actual['VALOR'], self::DECIMALES_TABLA);

            if (abs($anterior - $v['valor']) < 0.00001) {
                sqlsrv_rollback($this->conn);

                return ['success' => true, 'cambio' => false,
                    'message' => 'El valor ya era ' . self::formatear($anterior) . ': no se grabó nada.'];
            }

            $this->consultar("UPDATE " . self::TABLA . "
                              SET VALOR = ?, USUARIO_MODIF = ?, FECHA_MODIF = GETDATE()
                              WHERE CLAVE = ?",
                [$v['valor'], $usuario, $clave]);

            $this->consultar("INSERT INTO " . self::TABLA_HIST . "
                                  (CLAVE, VALOR_ANTERIOR, VALOR_NUEVO, USUARIO_ALTA, FECHA_ALTA)
                              VALUES (?, ?, ?, ?, GETDATE())",
                [$clave, $anterior, $v['valor'], $usuario]);

            sqlsrv_commit($this->conn);
        } catch (Throwable $e) {
            sqlsrv_rollback($this->conn);
            throw $e;
        }

        return ['success' => true, 'cambio' => true,
            'message' => 'Se guardó ' . self::formatear($v['valor']) . ' (antes ' . self::formatear($anterior) . ').'];
    }

    /** Ejecuta una consulta parametrizada o lanza una excepción con el error */
    private function consultar($sql, array $params = []) {
        $stmt = sqlsrv_query($this->conn, $sql, $params);

        if ($stmt === false) {
            error_log('Reportes/Parametros: ' . print_r(sqlsrv_errors(), true));

            throw new Exception('Error al consultar los parámetros. Si es la primera vez, verificá que se '
                . 'haya corrido sql/reportes_parametros.sql en la base central.');
        }

        return $stmt;
    }
}
