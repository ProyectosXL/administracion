<?php
/**
 * AuthReportes
 * Autenticación y permisos de Reportes de Administración contra el padrón de
 * Gestionusuarios. Mismo mecanismo que cashflow/Class/AuthCashflow.php y
 * informe_economico/Class/AuthInformeEconomico.php, de ProyectosXL/finanzas.
 *
 * POR QUÉ ES UNA COPIA Y NO SE COMPARTE
 * -------------------------------------
 * Las dos de finanzas viven en otro repo y tienen el estado estático de una
 * sola instancia: cargarlas desde acá traería los permisos de su módulo y no
 * los de este. Lo que cambia respecto de AuthCashflow:
 *   - el módulo se resuelve por FP_MODULOS.codigo, en UN solo lugar
 *     (MODULO_CODIGO), como en AuthInformeEconomico;
 *   - las LECTURAS también nombran su pestaña y se exigen: acá un endpoint de
 *     lectura devuelve el stock valorizado de toda la empresa, y que la URL
 *     directa no muestre nada sin permiso es parte del pedido.
 */

/**
 * El padrón de usuarios vive en OTRO módulo de htdocs, no en este repo.
 *
 * El require va condicionado porque un checkout de administracion sin
 * Gestionusuarios al lado es un caso real -correr tests/run.php en una máquina
 * que sólo clonó este repo- y ahí un require_once directo mata el proceso con
 * un fatal, sin llegar a ninguna prueba.
 *
 * FALLA CERRADA, y eso es lo que hace que la guarda sea segura: sin el padrón
 * no hay usuario, así que puede() devuelve false para TODO. Nadie entra de
 * más; lo que se pierde es la aplicación, no el control de acceso.
 */
if (!defined('AUTH_REPORTES_PADRON')) {
    define('AUTH_REPORTES_PADRON', __DIR__ . '/../../../Gestionusuarios/config/Database.php');
}

if (file_exists(AUTH_REPORTES_PADRON)) {
    require_once AUTH_REPORTES_PADRON;
}

/**
 * Las dos negativas, separadas porque piden cosas distintas a quien las
 * recibe: sin usuario hay que volver a entrar (401); sin permiso hay que
 * pedirlo en Gestionusuarios (403). Ver Controller/autorizacion.php, que es el
 * único lugar que las traduce a HTTP.
 */
class AuthReportesSinUsuario extends Exception {}

class AuthReportesSinPermiso extends Exception {}

class AuthReportes {

    /** El código del módulo en FP_MODULOS. El único lugar donde se nombra. */
    const MODULO_CODIGO = 'REPORTES_ADMINISTRACION';

    /** La clave que hace aparecer la tarjeta en el HUB. La lee el HUB, no este módulo. */
    const CLAVE_HUB = 'hub.app.reportes_administracion';

    const PREFIJO_VER = 'ra.tab.';
    const PREFIJO_EDITAR = 'ra.editar.';

    /** Largo de las columnas USUARIO_* de las tablas del módulo */
    const LARGO_USUARIO = 50;

    /**
     * QUÉ EXIGE CADA ACCIÓN DE CADA CONTROLLER.
     *
     * Es la ÚNICA fuente: ningún controller escribe un nombre de permiso
     * suelto. Cada acción dice qué pestaña hay que poder ver (LECTURAS) o
     * editar (ESCRITURAS) para ejecutarla; con CUALQUIERA de la lista alcanza.
     *
     * Una acción que no está en ninguna de las dos se rechaza. Así un case
     * nuevo no puede quedar abierto por olvido, y tests/test_auth_reportes.php
     * falla si alguien lo agrega sin declararlo.
     *
     * Las claves de edición que existen salen de acá (ver claves()): agregar
     * una pestaña editable es declarar su escritura, y la prueba exige que el
     * script de permisos la dé de alta.
     */
    const ESCRITURAS = [
        'Parametros' => [
            'guardar' => ['parametros']
        ]
    ];

    /** Las acciones que sólo leen, con la pestaña que hay que poder ver */
    const LECTURAS = [
        'Parametros' => [
            'listar' => ['parametros'],
            'historial' => ['parametros']
        ],
        'Valorizacion' => [
            'fechas' => ['valorizacion_stock'],
            'sucursales' => ['valorizacion_stock'],
            'consultar' => ['valorizacion_stock'],
            'detalle' => ['valorizacion_stock']
        ],
        'Remitos' => [
            'periodos' => ['remitos_pendientes'],
            'clientes' => ['remitos_pendientes'],
            'consultar' => ['remitos_pendientes'],
            'detalle' => ['remitos_pendientes']
        ]
    ];

    private static $inicializado = false;
    private static $usuario = null;
    private static $permisos = [];
    private static $esAdmin = false;

    public static function init() {
        if (self::$inicializado) {
            return;
        }

        self::$inicializado = true;

        // Sin el padrón no hay a quién preguntarle: se queda sin usuario, que
        // es el estado en el que puede() deniega todo.
        if (!class_exists('\GestionUsuarios\Config\Database')) {
            return;
        }

        /* !headers_sent(): arrancar una sesión después de haber emitido salida
           no funciona y sólo deja un warning. Pasa por línea de comandos -las
           pruebas- y ahí el estado correcto es justamente el que queda: sin
           sesión, o sea sin usuario, o sea denegando todo. */
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $username = $_SESSION['username'] ?? $_SESSION['usuario'] ?? $_SESSION['nodo_usuario_activo']
            ?? ($_SESSION['fp_auth_user']['username'] ?? null);

        if (!$username) {
            return;
        }

        try {
            $db = \GestionUsuarios\Config\Database::getInstance()->connectApps();

            if (!$db) {
                return;
            }

            $sql = "SELECT u.id, u.username, u.nombre_completo, u.rol_id, u.sector_id,
                           s.codigo AS sector_codigo, s.nombre AS sector_nombre,
                           r.nombre AS rol_nombre, r.codigo AS rol_codigo, r.es_admin
                    FROM FP_SOF_USUARIOS u
                    LEFT JOIN FP_SECTORES s ON s.id = u.sector_id
                    LEFT JOIN FP_ROLES_PERMISOS_MAP r ON r.id = u.rol_id
                    WHERE UPPER(TRIM(u.username)) = UPPER(TRIM(?)) AND u.activo = 1";

            $stmt = sqlsrv_query($db, $sql, [trim($username)]);

            if (!$stmt || !($user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
                return;
            }

            // La MISMA regla de administrador que AuthCashflow y
            // AuthInformeEconomico: sector Proyectos (id 7) + es_admin /
            // CONTROLTOTAL. Se copia tal cual, incluidos los casos de rol 1 y
            // el usuario puntual, para que las tres digan lo mismo.
            $esSectorProyectos = ((int) ($user['sector_id'] ?? 0) === 7)
                || (strtoupper(trim($user['sector_codigo'] ?? '')) === 'PROYECTOS')
                || (stripos($user['sector_nombre'] ?? '', 'Proyectos') !== false);

            self::$esAdmin = $esSectorProyectos && (
                !empty($user['es_admin'])
                || (int) ($user['rol_id'] ?? 0) === 1
                || strtoupper(trim($user['rol_codigo'] ?? '')) === 'CONTROLTOTAL'
                || strtoupper(trim($user['username'] ?? '')) === 'FRANCOPERTUS'
            );

            self::$usuario = [
                'id' => $user['id'],
                'username' => $user['username'],
                'nombre' => $user['nombre_completo'],
                'rol_id' => $user['rol_id'],
                'rol_nombre' => $user['rol_nombre'] ?? 'Sin Rol Asignado',
                'sector_nombre' => $user['sector_nombre'] ?? 'Sin Sector',
                'es_admin' => self::$esAdmin
            ];

            if (!empty($user['rol_id'])) {
                $sqlP = "SELECT p.clave
                         FROM FP_ROL_PERMISO rp
                         JOIN FP_PERMISOS p ON p.id = rp.permiso_id
                         JOIN FP_MODULOS m ON m.id = p.modulo_id
                         WHERE rp.rol_id = ? AND m.codigo = ?";
                $stmtP = sqlsrv_query($db, $sqlP, [$user['rol_id'], self::MODULO_CODIGO]);

                if ($stmtP) {
                    while ($p = sqlsrv_fetch_array($stmtP, SQLSRV_FETCH_ASSOC)) {
                        self::$permisos[trim($p['clave'])] = true;
                    }
                }
            }
        } catch (\Throwable $t) {
            // Falla cerrada: se queda con lo que haya, que en el peor caso es
            // un usuario sin permisos.
        }
    }

    public static function usuario() {
        self::init();

        return self::$usuario;
    }

    public static function esAdmin() {
        self::init();

        return self::$esAdmin;
    }

    public static function estaAutenticado() {
        return self::usuario() !== null;
    }

    /**
     * Si el usuario actual puede ver una pestaña.
     *
     * @param string $tab Código de pestaña ('valorizacion_stock', 'parametros')
     * @return bool
     */
    public static function puede($tab) {
        self::init();

        // Sin usuario no hay permisos cargados ni admin: permiteVer() deniega.
        return self::permiteVer(self::$permisos, self::$esAdmin, $tab);
    }

    /**
     * Si el usuario actual puede editar una pestaña.
     *
     * @param string $tab
     * @return bool
     */
    public static function puedeEditar($tab) {
        self::init();

        return self::permiteEditar(self::$permisos, self::$esAdmin, $tab);
    }

    /**
     * El atributo que la vista pone en la raíz de una pestaña para que el JS
     * sepa si dibujar los controles de edición. Ver Js/permisos.js.
     *
     * ES COMODIDAD, NO SEGURIDAD. Quien no puede editar no ve los botones, pero
     * lo que lo frena es el 403 del servidor (Controller/autorizacion.php): un
     * atributo del HTML lo cambia cualquiera desde la consola.
     *
     * @param string $tab
     * @return string ' data-puede-editar="1"' o ' data-puede-editar="0"'
     */
    public static function atributoEdicion($tab) {
        return ' data-puede-editar="' . (self::puedeEditar($tab) ? '1' : '0') . '"';
    }

    /**
     * Regla pura de lectura, sobre un juego de permisos dado.
     *
     * @param array $permisos clave => true
     * @param bool $esAdmin
     * @param string $tab
     * @return bool
     */
    public static function permiteVer(array $permisos, $esAdmin, $tab) {
        if ($esAdmin) {
            return true;
        }

        return isset($permisos[self::claveVer($tab)]);
    }

    /**
     * Regla pura de edición, sobre un juego de permisos dado.
     *
     * ESCRIBIR IMPLICA LEER. Sin ra.tab.<tab> se niega aunque el rol tenga la
     * clave de edición: alguien que no ve la pestaña no tiene desde dónde
     * editarla, y una clave de edición suelta es casi seguro un error de
     * asignación en Gestionusuarios, no una intención.
     *
     * @param array $permisos clave => true
     * @param bool $esAdmin
     * @param string $tab
     * @return bool
     */
    public static function permiteEditar(array $permisos, $esAdmin, $tab) {
        if ($esAdmin) {
            return true;
        }

        if (!self::permiteVer($permisos, false, $tab)) {
            return false;
        }

        return isset($permisos[self::claveEdicion($tab)]);
    }

    /** La clave de FP_PERMISOS que habilita ver una pestaña */
    public static function claveVer($tab) {
        return self::PREFIJO_VER . trim((string) $tab);
    }

    /** La clave de FP_PERMISOS que habilita editar una pestaña */
    public static function claveEdicion($tab) {
        return self::PREFIJO_EDITAR . trim((string) $tab);
    }

    /**
     * Todas las claves que usa el módulo: la del HUB, una de lectura por
     * pestaña del menú y una de edición por pestaña que alguna escritura
     * edita. Es lo que sql/reportes_permisos.sql tiene que dar de alta, ni más
     * ni menos; la prueba compara las dos listas.
     *
     * @return string[]
     */
    public static function claves() {
        require_once __DIR__ . '/Menu.php';

        $claves = [self::CLAVE_HUB];

        foreach (Menu::todas() as $p) {
            $claves[] = self::claveVer($p['tab']);
        }

        foreach (self::ESCRITURAS as $acciones) {
            foreach ($acciones as $tabs) {
                foreach ($tabs as $tab) {
                    $claves[] = self::claveEdicion($tab);
                }
            }
        }

        $claves = array_values(array_unique($claves));
        sort($claves);

        return $claves;
    }

    /**
     * El username del padrón, tal como se graba en las columnas USUARIO_*.
     *
     * Sale SOLO del padrón. No hay vuelta a $_SESSION['usuario']: un nombre
     * que no está en Gestionusuarios no tiene permisos que verificar, y
     * grabarlo igual sería firmar la fila con alguien a quien nunca se le
     * preguntó si podía.
     *
     * @return string|null
     */
    public static function username() {
        self::init();

        return self::recortarUsuario(self::$usuario['username'] ?? null);
    }

    /**
     * Recorta un usuario al largo de las columnas, o null si queda vacío.
     *
     * @param mixed $valor
     * @return string|null
     */
    public static function recortarUsuario($valor) {
        $valor = trim((string) $valor);

        return ($valor === '') ? null : mb_substr($valor, 0, self::LARGO_USUARIO);
    }

    /**
     * El usuario que recibe un método de escritura, validado.
     *
     * DEFENSA EN PROFUNDIDAD. El controller ya lo exigió, pero las clases se
     * llaman también desde pruebas y sondas, y un método que acepta un usuario
     * vacío graba una fila que no dice quién la hizo -justo lo que la
     * auditoría tiene que impedir-. Por eso ningún método de escritura tiene
     * $usuario = null por defecto, y todos pasan por acá antes de tocar la base.
     *
     * @param mixed $usuario
     * @return string
     * @throws InvalidArgumentException si viene vacío
     */
    public static function usuarioDeEscritura($usuario) {
        $u = self::recortarUsuario($usuario);

        if ($u === null) {
            throw new InvalidArgumentException('No se puede guardar un cambio sin saber quién lo '
                . 'hace: falta el usuario.');
        }

        return $u;
    }

    /**
     * El username, o una excepción que el controller convierte en 401.
     *
     * @return string
     * @throws AuthReportesSinUsuario
     */
    public static function exigirUsuario() {
        $u = self::username();

        if ($u === null) {
            throw new AuthReportesSinUsuario('No se pudo identificar tu usuario en el padrón. '
                . 'Volvé a ingresar al sistema y probá de nuevo.');
        }

        return $u;
    }

    /**
     * La guarda de un controller: exige lo que el mapa dice para esa acción.
     *
     * A diferencia del cashflow, la lectura también exige usuario y permiso de
     * ver la pestaña. Devuelve siempre el username: el controller lo usa en las
     * escrituras y en el encabezado del Excel, y NUNCA lo saca del request.
     *
     * @param string $controlador Nombre sin "Controller" ('Parametros')
     * @param string $accion
     * @return string
     * @throws AuthReportesSinUsuario|AuthReportesSinPermiso
     */
    public static function exigirAccion($controlador, $accion) {
        $req = self::requisitoDe($controlador, $accion);
        $u = self::exigirUsuario();

        foreach ($req['tabs'] as $tab) {
            $ok = $req['escribe'] ? self::puedeEditar($tab) : self::puede($tab);

            if ($ok) {
                return $u;
            }
        }

        $claves = array_map(function ($tab) use ($req) {
            return $req['escribe'] ? self::claveEdicion($tab) : self::claveVer($tab);
        }, $req['tabs']);

        throw new AuthReportesSinPermiso($req['escribe']
            ? 'Tu rol no tiene permiso para modificar esta sección (' . implode(' o ', $claves)
                . '). Podés consultarla, pero el cambio no se guardó.'
            : 'Tu rol no tiene permiso para ver esta sección (' . implode(' o ', $claves) . ').');
    }

    /**
     * Qué pide una acción: si escribe y qué pestañas la habilitan. Pura.
     *
     * @param string $controlador
     * @param string $accion
     * @return array ['escribe' => bool, 'tabs' => string[]]
     * @throws AuthReportesSinPermiso si la acción no está declarada
     */
    public static function requisitoDe($controlador, $accion) {
        if (isset(self::ESCRITURAS[$controlador][$accion])) {
            return ['escribe' => true, 'tabs' => self::ESCRITURAS[$controlador][$accion]];
        }

        if (isset(self::LECTURAS[$controlador][$accion])) {
            return ['escribe' => false, 'tabs' => self::LECTURAS[$controlador][$accion]];
        }

        throw new AuthReportesSinPermiso('La acción "' . $accion . '" no está declarada en el '
            . 'mapa de permisos del módulo (Class/AuthReportes.php), así que no se ejecuta.');
    }
}
