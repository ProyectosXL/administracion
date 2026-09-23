/**
 * paramCronograma.js - Días de offset de las fechas derivadas
 *
 * ESTOS VALORES SON LA ÚNICA FUENTE de la cadena de fechas. Ya no hay números
 * de días escritos en ningún JS ni PHP del módulo: la cadena entera la calcula
 * CronogramaFechas::cadenaDeFechas() leyendo esta tabla, y la usan tanto el
 * cronograma como la carga inicial de despachos.
 *
 * ADEMÁS MUEVEN EL CASHFLOW DE ProyectosXL/finanzas, que lee FECHA_EST_PAGO y
 * FECHA_DESP_ADU del maestro de importaciones y que va a leer esta misma tabla
 * para proyectar contenedores que todavía no existen como fila. Cambiar un
 * valor acá no es un ajuste cosmético: corre plata de mes en el tablero.
 */

let paramCronogramaCargados = false;

$('#cronograma-tab').on('shown.bs.tab', function () {
    if (!paramCronogramaCargados) {
        cargarParamCronograma();
    }
});

$(document).ready(function () {
    if ($('#cronograma-tab').hasClass('active')) {
        cargarParamCronograma();
    }
});

function cargarParamCronograma() {
    $.ajax({
        url: 'controller/listarParamCronograma.php',
        method: 'GET',
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                paramCronogramaCargados = true;
                renderizarParamCronograma(response.data);
            } else {
                mostrarErrorParamCronograma(response.message);
            }
        },
        error: function (xhr) {
            let msg = 'No se pudieron cargar los parámetros del cronograma.';
            // El caso mas probable si falla: la tabla todavia no existe en
            // esta base porque no se corrio el script 04.
            if (xhr.status === 500) {
                msg += ' Verificá que el script 04_cronograma_parametros_dias.sql ' +
                       'se haya corrido en esta base.';
            }
            mostrarErrorParamCronograma(msg);
        }
    });
}

function renderizarParamCronograma(parametros) {
    const $tbody = $('#tablaParamCronograma tbody');
    $tbody.empty();

    if (!parametros || parametros.length === 0) {
        $tbody.append(`
            <tr><td colspan="5" class="text-center text-muted py-4">
                No hay parámetros cargados. ¿Se corrió el script
                <code>04_cronograma_parametros_dias.sql</code> en esta base?
            </td></tr>
        `);
        return;
    }

    parametros.forEach(p => {
        const clave = escaparHtmlParam(p.CLAVE);

        /* Los parámetros retirados siguen en la tabla —no se borran datos— pero
           no los lee nadie. Se muestran deshabilitados y con el motivo, en vez
           de ofrecer un input y un botón que el servidor va a rechazar: una
           perilla que no hace nada es peor que ninguna perilla.

           El marcador es la propia DESCRIPCION, que pone el script 15. Una
           lista de claves retiradas acá sería un cuarto lugar donde acordarse
           de actualizar algo. */
        const retirado = /^SIN USO/i.test(p.DESCRIPCION || '');

        if (retirado) {
            $tbody.append(`
                <tr data-clave="${clave}" class="table-secondary text-muted">
                    <td><code><s>${clave}</s></code></td>
                    <td><em>${escaparHtmlParam(p.DESCRIPCION || '')}</em></td>
                    <td>
                        <div class="input-group input-group-sm" style="max-width: 130px;">
                            <input type="number" class="form-control"
                                   value="${parseInt(p.VALOR, 10)}" disabled>
                            <span class="input-group-text">días</span>
                        </div>
                    </td>
                    <td class="small">${p.FECHA_MOD ? escaparHtmlParam(p.FECHA_MOD) : '-'}</td>
                    <td><span class="badge bg-secondary">sin uso</span></td>
                </tr>
            `);
            return;
        }

        $tbody.append(`
            <tr data-clave="${clave}">
                <td><code>${clave}</code></td>
                <td>${escaparHtmlParam(p.DESCRIPCION || '')}</td>
                <td>
                    <div class="input-group input-group-sm" style="max-width: 130px;">
                        <input type="number" class="form-control param-cronograma-valor"
                               value="${parseInt(p.VALOR, 10)}" min="0" max="365" step="1">
                        <span class="input-group-text">días</span>
                    </div>
                </td>
                <td class="text-muted small">${p.FECHA_MOD ? escaparHtmlParam(p.FECHA_MOD) : '-'}</td>
                <td>
                    <button class="btn btn-sm btn-primary btn-guardar-param-cronograma">
                        <i class="bi bi-check-lg"></i> Guardar
                    </button>
                </td>
            </tr>
        `);
    });
}

function escaparHtmlParam(texto) {
    return String(texto === null || texto === undefined ? '' : texto)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function mostrarErrorParamCronograma(mensaje) {
    $('#tablaParamCronograma tbody').html(`
        <tr><td colspan="5" class="text-center text-danger py-4">
            <i class="bi bi-exclamation-triangle"></i> ${escaparHtmlParam(mensaje)}
        </td></tr>
    `);
}

$(document).on('click', '.btn-guardar-param-cronograma', function () {
    const $fila = $(this).closest('tr');
    const clave = $fila.data('clave');
    const valor = parseInt($fila.find('.param-cronograma-valor').val(), 10);

    // Mismo rango que valida el servidor. La validacion de verdad es la de
    // alla; esta solo evita el viaje.
    if (isNaN(valor) || valor < 0 || valor > 365) {
        Swal.fire('Valor inválido', 'Los días deben ser un número entre 0 y 365.', 'warning');
        return;
    }

    const $btn = $(this).prop('disabled', true);

    $.ajax({
        url: 'controller/actualizarParamCronograma.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ clave: clave, valor: valor }),
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Parámetro actualizado',
                    /* El mensaje decía "se aplica a las próximas estimaciones
                       del cronograma" y eso ya no es todo lo que pasa: la misma
                       cadena la usa la carga inicial, y las fechas que escribe
                       las lee el cashflow de Finanzas. */
                    text: 'Se aplica a las fechas automáticas del cronograma y de la carga '
                        + 'de despachos. Las fechas fijadas a mano no se tocan.',
                    timer: 2000,
                    showConfirmButton: false
                });
                paramCronogramaCargados = false;
                cargarParamCronograma();
            } else {
                Swal.fire('Error', response.message || 'No se pudo actualizar.', 'error');
            }
        },
        error: function (xhr) {
            const r = xhr.responseJSON;
            Swal.fire('Error', (r && r.message) ? r.message : 'No se pudo actualizar.', 'error');
        },
        complete: function () {
            $btn.prop('disabled', false);
        }
    });
});
