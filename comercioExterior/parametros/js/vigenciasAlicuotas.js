/* ============================================================================
   VIGENCIAS DE ALÍCUOTAS

   Una alícuota ya no se edita pisando el valor anterior: se cierra la vigencia
   que estaba y se abre una nueva con su fecha. La alícuota que le toca a una
   operación se resuelve contra su FECHA DE NACIONALIZACIÓN, no contra la de
   hoy, así que una operación histórica sigue usando la que regía ese día.

   Vive aparte de parametros.js por lo mismo que abmAlias.js y
   paramCronograma.js: es un ABM propio con su modal, y meterlo en el archivo
   de la grilla de parámetros mezclaría dos pantallas que no se tocan.

   Ver comercioExterior/class/AlicuotasVigencia.php y el script
   comercioExterior/sql/09_alicuotas_vigencia.sql.
   ============================================================================ */

/** El concepto que está abierto en el modal de vigencias. */
let conceptoVigencias = null;

/**
 * Abre el modal de vigencias de un concepto y trae su historial.
 */
function abrirModalVigencias(param) {
    conceptoVigencias = param;

    $('#vigIdCe').val(param.ID_CE);
    $('#vigTipoValor').val(param.TIPO_VALOR);
    $('#vigConceptoNombre').text(param.CONCEPTO);
    $('#vigUnidad1').text(param.TIPO_VALOR === 'P' ? '%' : '$');

    // El formulario arranca limpio salvo la fecha, que por defecto es hoy: es
    // lo que afirma una alícuota que se carga ahora.
    $('#vigValor1, #vigValor2, #vigHasta, #vigObservacion').val('');
    $('#vigDesde').val(new Date().toISOString().slice(0, 10));
    $('#vigTbody').html('<tr><td colspan="7" class="text-center text-muted">Cargando...</td></tr>');
    $('#vigAviso').hide();

    const modal = new bootstrap.Modal(document.getElementById('modalVigencias'));
    modal.show();

    cargarVigencias(param.ID_CE);
}

function cargarVigencias(idCe) {
    $.ajax({
        url: 'controller/listarVigencias.php',
        method: 'GET',
        data: { id_ce: idCe },
        dataType: 'json',
        success: function (response) {
            if (!response.success) {
                Swal.fire('Error', response.message || 'No se pudieron traer las vigencias', 'error');
                return;
            }

            /* Si el script no se corrió, la pantalla lo dice y apaga el ABM en
               vez de fallar. La aplicación sigue andando con el padrón, que es
               lo que hacía antes de esta tanda. Mismo criterio que usa
               Finanzas cuando le falta una tabla suya. */
            if (!response.disponible) {
                $('#vigAviso')
                    .html('<i class="bi bi-exclamation-triangle"></i> Las vigencias no están ' +
                          'habilitadas en esta base. Falta correr <code>' + response.script + '</code>. ' +
                          'Mientras tanto las alícuotas salen del padrón y todo sigue funcionando.')
                    .attr('class', 'alert alert-warning py-2 px-3')
                    .show();
                $('#btnGuardarVigencia').prop('disabled', true);
                $('#vigTbody').html('<tr><td colspan="7" class="text-center text-muted">Sin historial</td></tr>');
                return;
            }

            $('#btnGuardarVigencia').prop('disabled', false);
            renderizarVigencias(response);
        },
        error: function (xhr, status, error) {
            console.error('Error al traer vigencias:', error);
            Swal.fire('Error', 'Error al conectar con el servidor', 'error');
        }
    });
}

function renderizarVigencias(response) {
    const tbody = $('#vigTbody');
    tbody.empty();

    const vigencias = response.vigencias || [];

    if (vigencias.length === 0) {
        tbody.html('<tr><td colspan="7" class="text-center text-muted">' +
                   'Todavía no hay vigencias cargadas para este concepto</td></tr>');
        return;
    }

    const hoy = response.hoy;
    const tipo = $('#vigTipoValor').val();

    vigencias.forEach(function (v) {
        // Misma regla que resuelve el servidor, con los dos extremos adentro.
        const rigeHoy = v.ACTIVO === 1 &&
                        v.VIGENCIA_DESDE <= hoy &&
                        (v.VIGENCIA_HASTA === null || v.VIGENCIA_HASTA >= hoy);

        let estado;
        if (v.ACTIVO !== 1) {
            estado = '<span class="badge bg-secondary">Retirada</span>';
        } else if (rigeHoy) {
            estado = '<span class="badge bg-success">Rige hoy</span>';
        } else if (v.VIGENCIA_DESDE > hoy) {
            estado = '<span class="badge bg-info">Futura</span>';
        } else {
            estado = '<span class="badge bg-light text-dark border">Cerrada</span>';
        }

        /* Retirar una vigencia que ya terminó reescribiría el pasado: las
           operaciones nacionalizadas dentro de ese período quedarían sin
           alícuota. Sólo se ofrece retirar la que rige y las futuras, que es
           donde sirve -alguien la cargó mal y todavía no impactó en nada-. */
        const puedeRetirar = v.ACTIVO === 1 &&
                             (v.VIGENCIA_HASTA === null || v.VIGENCIA_HASTA >= hoy);

        const botonRetirar = puedeRetirar
            ? '<button class="btn btn-sm btn-outline-danger" title="Retirar (no se borra)" ' +
              'onclick="retirarVigencia(' + v.ID + ')"><i class="bi bi-x-lg"></i></button>'
            : '';

        const hasta = v.VIGENCIA_HASTA === null
            ? '<span class="text-muted fst-italic">sigue vigente</span>'
            : formatearFechaCorta(v.VIGENCIA_HASTA);

        tbody.append(
            '<tr class="' + (rigeHoy ? 'table-success' : '') + '">' +
                '<td class="text-muted">' + v.ID + '</td>' +
                '<td class="fw-bold">' + formatearValorVigencia(v.VALOR_1, tipo) + '</td>' +
                '<td>' + (v.VALOR_2 === null ? '-' : formatearValorVigencia(v.VALOR_2, tipo)) + '</td>' +
                '<td>' + formatearFechaCorta(v.VIGENCIA_DESDE) + '</td>' +
                '<td>' + hasta + '</td>' +
                '<td>' + estado + '</td>' +
                '<td class="text-center">' + botonRetirar + '</td>' +
            '</tr>'
        );

        if (v.OBSERVACION) {
            tbody.append(
                '<tr><td></td><td colspan="6" class="small text-muted pt-0">' +
                $('<div>').text(v.OBSERVACION + (v.USUARIO ? ' — ' + v.USUARIO : '')).html() +
                '</td></tr>'
            );
        }
    });
}

/** El valor se guarda en decimal (0,20) y se muestra en porcentaje (20%). */
function formatearValorVigencia(valor, tipo) {
    if (valor === null || valor === undefined) return '-';
    const num = parseFloat(valor);
    if (tipo === 'P') {
        return (num * 100).toFixed(4).replace(/\.?0+$/, '') + '%';
    }
    return num.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 4 });
}

function formatearFechaCorta(fecha) {
    if (!fecha) return '-';
    const p = String(fecha).split('-');
    return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : fecha;
}

function guardarVigencia() {
    const tipo = $('#vigTipoValor').val();
    const valor1Crudo = $('#vigValor1').val();

    if (valor1Crudo === '') {
        Swal.fire('Error', 'El valor de la alícuota es obligatorio', 'error');
        return;
    }
    if (!$('#vigDesde').val()) {
        Swal.fire('Error', 'La fecha "desde" es obligatoria', 'error');
        return;
    }

    /* Se tipea en porcentaje y se guarda en decimal, igual que en el modal de
       edición del parámetro: las dos pantallas escriben el mismo dato, y si
       una guardara 20 y la otra 0,20 el cálculo daría cien veces más. */
    let valor1 = parseFloat(valor1Crudo);
    let valor2 = $('#vigValor2').val() === '' ? null : parseFloat($('#vigValor2').val());

    if (tipo === 'P') {
        valor1 = valor1 / 100;
        if (valor2 !== null) valor2 = valor2 / 100;
    }

    const data = {
        accion:         'agregar',
        id_ce:          parseInt($('#vigIdCe').val()),
        valor_1:        valor1,
        valor_2:        valor2,
        vigencia_desde: $('#vigDesde').val(),
        vigencia_hasta: $('#vigHasta').val() || null,
        observacion:    $('#vigObservacion').val() || null
    };

    $.ajax({
        url: 'controller/guardarVigencia.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        dataType: 'json',
        success: function (response) {
            if (!response.success) {
                // La superposición de períodos llega por acá con el detalle de
                // con cuál choca, que es lo que hace falta para corregirlo.
                Swal.fire('No se pudo guardar', response.message || 'Error al guardar la vigencia', 'warning');
                return;
            }

            Swal.fire({
                title: 'Vigencia registrada',
                text: response.message,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });

            $('#vigValor1, #vigValor2, #vigHasta, #vigObservacion').val('');
            cargarVigencias(data.id_ce);
            cargarParametros();   // el padrón pudo haber quedado sincronizado
        },
        error: function (xhr) {
            let msg = 'Error al conectar con el servidor';
            try { msg = JSON.parse(xhr.responseText).message || msg; } catch (e) {}
            Swal.fire('Error', msg, 'error');
        }
    });
}

/**
 * Retira una vigencia. No la borra: queda con ACTIVO = 0 y sigue en el
 * historial, porque una estimación calculada con ella quedaría apuntando a
 * algo que ya no se puede explicar.
 */
function retirarVigencia(id) {
    Swal.fire({
        title: '¿Retirar esta vigencia?',
        text: 'No se borra: queda en el historial marcada como retirada y deja de usarse para calcular.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, retirar',
        cancelButtonText: 'Cancelar'
    }).then(function (r) {
        if (!r.isConfirmed) return;

        const idCe = parseInt($('#vigIdCe').val());

        $.ajax({
            url: 'controller/guardarVigencia.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ accion: 'retirar', id: id, id_ce: idCe }),
            dataType: 'json',
            success: function (response) {
                if (!response.success) {
                    Swal.fire('Error', response.message || 'No se pudo retirar', 'error');
                    return;
                }
                cargarVigencias(idCe);
                cargarParametros();
            },
            error: function () {
                Swal.fire('Error', 'Error al conectar con el servidor', 'error');
            }
        });
    });
}
