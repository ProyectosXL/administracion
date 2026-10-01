<?php
/**
 * Arnés mínimo de pruebas de Comercio Exterior.
 *
 * Es el MISMO arnés que cashflow/tests/lib.php de ProyectosXL/finanzas, a
 * propósito: el proyecto no usa Composer ni ningún framework, así que las
 * pruebas tampoco. Son PHP plano que se corre con `php tests/run.php`, y quien
 * conoce las de un repo lee las del otro sin aprender nada nuevo.
 *
 * Lo que se prueba acá es lo más delicado del módulo: la cuenta de la
 * estimación -que vive dos veces, en el JS y en CalculoEstimacion-, la regla
 * de visibilidad -dos veces, en PHP y en SQL- y que los disparadores llamen al
 * recálculo. Las pruebas que necesitan la base se saltean solas sin conexión.
 */

class Pruebas {

    public static $ok = 0;
    public static $fallas = 0;
    public static $detalle = [];
    public static $archivo = '';

    /** Título de un bloque de pruebas */
    public static function seccion($titulo) {
        echo PHP_EOL . '  -- ' . $titulo . ' --' . PHP_EOL;
    }

    /**
     * Compara esperado contra obtenido.
     *
     * Los flotantes se comparan con tolerancia, porque un importe calculado
     * arrastra error de punto flotante. null se compara de forma estricta: la
     * diferencia entre null y 0 es justamente lo que distingue "no hay dato" de
     * "el dato es cero", y no puede quedar tapada por una comparación floja.
     */
    public static function chequear($nombre, $esperado, $obtenido) {
        if ($esperado === null || $obtenido === null) {
            $iguales = ($esperado === $obtenido);
        } elseif (is_float($esperado) || is_float($obtenido)) {
            $iguales = abs(floatval($esperado) - floatval($obtenido)) < 0.001;
        } else {
            $iguales = ($esperado === $obtenido);
        }

        if ($iguales) {
            self::$ok++;
            echo '    OK    ' . $nombre . PHP_EOL;

            return true;
        }

        self::$fallas++;
        self::$detalle[] = self::$archivo . ': ' . $nombre;

        echo '    FALLA ' . $nombre . PHP_EOL;
        echo '          esperado: ' . self::mostrar($esperado) . PHP_EOL;
        echo '          obtenido: ' . self::mostrar($obtenido) . PHP_EOL;

        return false;
    }

    private static function mostrar($v) {
        if (is_array($v)) {
            return preg_replace('/\s+/', ' ', var_export($v, true));
        }

        return var_export($v, true);
    }

    /** Si hay conexión a la base, para poder saltear las pruebas que la necesitan */
    public static function hayBase() {
        static $hay = null;

        if ($hay !== null) {
            return $hay;
        }

        try {
            require_once __DIR__ . '/../../class/conexion.php';
            $conn = new Conexion;
            $hay = (bool) @$conn->conectar('central');
        } catch (Throwable $e) {
            $hay = false;
        }

        return $hay;
    }

    public static function saltear($motivo) {
        echo '    (salteado: ' . $motivo . ')' . PHP_EOL;
    }

    /** Información que la prueba reporta sin juzgar: no suma ni resta */
    public static function informar($texto) {
        echo '    INFO  ' . $texto . PHP_EOL;
    }
}

function seccion($titulo) {
    Pruebas::seccion($titulo);
}

function chequear($nombre, $esperado, $obtenido) {
    return Pruebas::chequear($nombre, $esperado, $obtenido);
}

/**
 * El código de un archivo, SIN SUS COMENTARIOS.
 *
 * Mismo criterio que codigoSinComentarios() del cashflow: estos archivos
 * explican en prosa lo que dejaron de hacer -"antes era LEFT JOIN ... WHERE
 * ID_MG IS NULL"- y buscar el patrón sobre el archivo entero daría positivo en
 * la nota que dice que el patrón ya no está.
 */
function codigoSinComentarios($ruta) {
    $src = file_get_contents($ruta);
    $src = preg_replace('#/\*.*?\*/#s', '', $src);

    return preg_replace('#(^|\s)//[^\n]*#m', '$1', $src);
}

/** El cuerpo de una función, para buscar adentro de ella y no en todo el archivo */
function cuerpoDe($codigo, $funcion) {
    if (!preg_match('/function ' . preg_quote($funcion, '/') . '\s*\(.*?\n    \}/s', $codigo, $m)) {
        return '';
    }

    return $m[0];
}
