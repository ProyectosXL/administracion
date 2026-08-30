/**
 * paramCronograma.js - Días de offset del cronograma de contenedores
 *
 * Reemplazan los valores que estaban hardcodeados en
 * cronogramaDespachos/js/cronograma.js (45 / 7 / 3) y suman el nuevo
 * DIAS_ARR_DIST, que es el que proyecta la fecha de distribución.
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
                    text: 'Se aplica a las próximas estimaciones del cronograma.',
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
