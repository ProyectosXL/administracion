/**
 * excel.js
 * Lo común del armado de los Excel con SheetJS: fechas como fechas, formato
 * de números, autofiltro y el encabezado de cada hoja. Lo usan
 * valorizacion.js y remitos.js; cada una arma sus hojas.
 *
 * Se carga antes que el JS de la pestaña (ver 'js' en Class/Menu.php).
 */
(function () {
    'use strict';

    var Excel = {};

    /** 'AAAA-MM-DD' al número de serie de Excel, sin pasar por la zona horaria */
    Excel.serie = function (iso) {
        var p = iso.split('-');

        return (Date.UTC(+p[0], +p[1] - 1, +p[2]) - Date.UTC(1899, 11, 30)) / 86400000;
    };

    /** 'AAAA-MM-DD HH:MM:SS' a 'DD/MM/AAAA HH:MM' */
    Excel.fechaHora = function (s) {
        var m = String(s || '').match(/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})/);

        return m ? m[3] + '/' + m[2] + '/' + m[1] + ' ' + m[4] + ':' + m[5] : s;
    };

    /**
     * Las filas de encabezado de una hoja: el título, una fila por línea, quién
     * lo generó y cuándo, y una fila en blanco antes de los títulos.
     *
     * @param {string} titulo
     * @param {string[]} lineas
     * @param {string} usuario El del padrón, que manda el servidor
     * @param {string} generado 'AAAA-MM-DD HH:MM:SS', hora de Argentina
     * @returns {Array[]}
     */
    Excel.encabezado = function (titulo, lineas, usuario, generado) {
        var filas = [[titulo]];

        lineas.forEach(function (t) {
            filas.push([t]);
        });

        filas.push(['Generado por ' + usuario + ' el ' + Excel.fechaHora(generado)]);
        filas.push([]);

        return filas;
    };

    /** Formato numérico a un rango de celdas de una columna. Las vacías y las de texto no se tocan. */
    Excel.formatear = function (ws, col, desde, hasta, formato) {
        for (var f = desde; f <= hasta; f++) {
            var celda = ws[XLSX.utils.encode_cell({ r: f, c: col })];

            if (celda && celda.t === 'n') {
                celda.z = formato;
            }
        }
    };

    /** Autofiltro desde la fila de títulos hasta la última fila */
    Excel.autofiltro = function (ws, filaTitulos, ultimaFila, ultimaCol) {
        ws['!autofilter'] = { ref: XLSX.utils.encode_range({ s: { r: filaTitulos, c: 0 }, e: { r: ultimaFila, c: ultimaCol } }) };
    };

    window.ExcelReportes = Excel;
})();
