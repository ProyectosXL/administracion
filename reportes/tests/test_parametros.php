<?php
/**
 * Parámetros: la validación del factor, el formato con coma y que la
 * escritura deje auditoría e historial.
 *
 * La escritura se prueba LEYENDO EL CÓDIGO: estas pruebas no escriben en la
 * base, ni siquiera en una transacción que se revierte.
 */

require_once __DIR__ . '/../Class/Parametros.php';

$base = __DIR__ . '/..';
$F = Parametros::FACTOR_NAC_ESTIMADO;

seccion('validación del factor de nacionalización estimado');

$v = Parametros::validarValor($F, '1,65');
chequear('1,65 con coma se acepta', true, $v['ok']);
chequear('y vale 1.65', 1.65, $v['valor']);
chequear('1.5 con punto se acepta', 1.5, Parametros::validarValor($F, '1.5')['valor']);
chequear('el mínimo, 1, se acepta', 1.0, Parametros::validarValor($F, '1')['valor']);
chequear('el máximo, 5, se acepta', 5.0, Parametros::validarValor($F, '5')['valor']);
chequear('4 decimales se aceptan', 1.2345, Parametros::validarValor($F, '1,2345')['valor']);
chequear('los espacios alrededor no molestan', 1.65, Parametros::validarValor($F, ' 1,65 ')['valor']);

$v = Parametros::validarValor($F, '0,8');
chequear('0,8 se rechaza', false, $v['ok']);
chequear('y el mensaje dice el rango y lo ingresado',
    'El valor tiene que ser mayor o igual a 1 y menor o igual a 5; ingresaste 0,8.', $v['message']);

$v = Parametros::validarValor($F, '7');
chequear('7 se rechaza', false, $v['ok']);
chequear('y el mensaje dice el rango y lo ingresado',
    'El valor tiene que ser mayor o igual a 1 y menor o igual a 5; ingresaste 7.', $v['message']);

chequear('5,0001 se rechaza: el máximo es inclusive pero no más', false, Parametros::validarValor($F, '5,0001')['ok']);
chequear('0,9999 se rechaza', false, Parametros::validarValor($F, '0,9999')['ok']);

$v = Parametros::validarValor($F, '1,23456');
chequear('5 decimales se rechazan', false, $v['ok']);
chequear('y el mensaje lo explica', 'Se aceptan hasta 4 decimales y ingresaste 5.', $v['message']);

chequear('vacío se rechaza', false, Parametros::validarValor($F, '')['ok']);
chequear('texto se rechaza', false, Parametros::validarValor($F, 'abc')['ok']);
chequear('dos separadores se rechazan', false, Parametros::validarValor($F, '1,6,5')['ok']);
chequear('separador de miles se rechaza (1.650 es ambiguo)', false, Parametros::validarValor($F, '1.650,00')['ok']);
chequear('negativo se rechaza', false, Parametros::validarValor($F, '-1,5')['ok']);
chequear('una clave sin regla no se puede modificar', false, Parametros::validarValor('NO_EXISTE', '2')['ok']);

seccion('formato con coma decimal');

chequear('1.65 -> 1,65', '1,65', Parametros::formatear(1.65));
chequear('1.5 -> 1,50', '1,50', Parametros::formatear(1.5));
chequear('1.2345 -> 1,2345', '1,2345', Parametros::formatear('1.2345'));
chequear('el DECIMAL de la base, "1.6500" -> 1,65', '1,65', Parametros::formatear('1.6500'));
chequear('sin mínimo, 5 -> 5', '5', Parametros::formatear(5, 0));
chequear('el texto de la regla', 'Entre 1 y 5, hasta 4 decimales.', Parametros::textoRegla($F));

seccion('la escritura deja auditoría e historial');

$clase = codigoSinComentarios($base . '/Class/Parametros.php');
$guardar = cuerpoDe($clase, 'guardar');

chequear('valida el usuario antes que nada', true,
    strpos($guardar, 'AuthReportes::usuarioDeEscritura($usuario)') !== false
    && strpos($guardar, 'usuarioDeEscritura') < strpos($guardar, 'sqlsrv_begin_transaction'));
chequear('en una transacción', true,
    strpos($guardar, 'sqlsrv_begin_transaction') !== false && strpos($guardar, 'sqlsrv_commit') !== false
    && strpos($guardar, 'sqlsrv_rollback') !== false);
chequear('lee el anterior con UPDLOCK', true, strpos($guardar, 'WITH (UPDLOCK, ROWLOCK)') !== false);
chequear('escribe USUARIO_MODIF y FECHA_MODIF con la fecha del servidor', true,
    strpos($guardar, 'USUARIO_MODIF = ?, FECHA_MODIF = GETDATE()') !== false);
chequear('graba el historial con anterior, nuevo, usuario y fecha', true,
    strpos($guardar, '(CLAVE, VALOR_ANTERIOR, VALOR_NUEVO, USUARIO_ALTA, FECHA_ALTA)') !== false);
chequear('el historial va después del UPDATE y antes del commit', true,
    strpos($guardar, 'UPDATE ') < strpos($guardar, 'INSERT INTO ')
    && strpos($guardar, 'INSERT INTO ') < strpos($guardar, 'sqlsrv_commit'));
chequear('no hay usuario por defecto en guardar()', false,
    (bool) preg_match('/function guardar\([^)]*\$usuario\s*=/', $clase));

$ctrl = codigoSinComentarios($base . '/Controller/ParametrosController.php');
chequear('el controller no lee ningún usuario del request', false,
    (bool) preg_match('/\$_(POST|GET|REQUEST)\[\s*\'(usuario|USUARIO)/', $ctrl));
chequear('guarda con el usuario que devolvió autorizar()', true,
    strpos($ctrl, '$usuario = autorizar(\'Parametros\', $action)') !== false
    && strpos($ctrl, '->guardar($clave, $_POST[\'valor\'] ?? \'\', $usuario)') !== false);

$js = codigoSinComentarios($base . '/Js/parametros.js');
chequear('el front no manda columnas de auditoría', false, (bool) preg_match('/USUARIO_|FECHA_/', $js));
chequear('el front pide confirmación antes de guardar', true,
    strpos($js, '¿Guardar el cambio?') !== false && strpos($js, 'guardar(p, nuevo)') !== false);

seccion('el script de parámetros');

$sql = file_get_contents($base . '/sql/reportes_parametros.sql');
chequear('siembra FACTOR_NAC_ESTIMADO = 1.65', true,
    (bool) preg_match("/'FACTOR_NAC_ESTIMADO',\s*N'[^']+',\s*1\.65,/", $sql));
chequear('sólo si no existe: no pisa un valor modificado', true,
    strpos($sql, "IF NOT EXISTS (SELECT 1 FROM dbo.RO_T_REPORTES_ADM_PARAMETROS WHERE CLAVE = 'FACTOR_NAC_ESTIMADO')") !== false);
chequear('VALOR es DECIMAL(10,4), la escala de DECIMALES_TABLA', true,
    strpos($sql, 'VALOR         DECIMAL(10,4)') !== false && Parametros::DECIMALES_TABLA === 4);
chequear('verifica la base con OBJECT_ID', true,
    strpos($sql, "OBJECT_ID('dbo.RO_T_VALORIZACION_STOCK_DETALLADO_LAKERS', 'U')") !== false);
chequear('no borra nada', false, (bool) preg_match('/\b(DROP|DELETE|TRUNCATE)\b/i', $sql));
chequear('toda clave con regla en REGLAS está sembrada en el script', [],
    array_values(array_filter(array_keys(Parametros::REGLAS), function ($k) use ($sql) {
        return strpos($sql, "'" . $k . "'") === false;
    })));

seccion('contra la base (sólo lectura)');

if (!Pruebas::hayBase()) {
    Pruebas::saltear('sin conexión a la base');
} else {
    $conn = (new Conexion())->conectar('central');
    $stmt = sqlsrv_query($conn, "SELECT OBJECT_ID('dbo.RO_T_REPORTES_ADM_PARAMETROS', 'U') AS T");
    $existe = ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) && $r['T'] !== null;

    if (!$existe) {
        Pruebas::saltear('RO_T_REPORTES_ADM_PARAMETROS no existe todavía: falta correr sql/reportes_parametros.sql');
    } else {
        $p = new Parametros($conn);
        $factor = $p->valor($F);
        chequear('el factor está cargado', true, $factor !== null);
        chequear('y el valor vigente pasa la validación', true,
            $factor !== null && Parametros::validarValor($F, (string) $factor)['ok']);
        Pruebas::informar('factor vigente: ' . Parametros::formatear($factor));
    }
}
