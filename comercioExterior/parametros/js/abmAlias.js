/**
 * abmAlias.js - ABM de alias de proveedor.
 *
 * Lo usan la pestaña de Parámetros y el modal del cronograma, con los mismos
 * endpoints y el mismo markup (components/abm-alias.php). Por eso todo va
 * delegado desde document: el fragmento puede aparecer despues de cargada la
 * pagina.
 *
 * ABM_ALIAS_BASE lo define quien incluye el script, porque la ruta al
 * controller cambia segun desde donde se lo consuma.
 */
const ABM_ALIAS_BASE = (typeof window.ABM_ALIAS_BASE !== 'undefined')
    ? window.ABM_ALIAS_BASE
    : 'controller/';

let aliasCargados = false;
let proveedoresAlias = [];

$('#alias-tab').on('shown.bs.tab', function () {
    if (!aliasCargados) cargarAlias();
});

$(document).ready(function () {
    if ($('#alias-tab').hasClass('active')) cargarAlias();
});

function escaparAbm(t) {
    return String(t === null || t === undefined ? '' : t)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function avisoAbm(tipo, titulo, texto) {
    if (typeof Swal !== 'undefined') {
        if (tipo === 'success') {
            Swal.fire({ icon: 'success', title: titulo, text: texto, timer: 1800, showConfirmButton: false });
        } else {
            Swal.fire({ icon: tipo, title: titulo, text: texto });
        }
    } else {
        alert(titulo + (texto ? '\n' + texto : ''));
    }
}

function cargarAlias() {
    $.getJSON(ABM_ALIAS_BASE + 'gestionarAlias.php', { accion: 'listar' })
        .done(function (r) {
            if (!r.success) {
                mostrarErrorAlias(r.message);
                return;
            }
            aliasCargados = true;
            renderizarTablaAlias(r.data);
            cargarProveedoresAlias();
        })
        .fail(function (xhr) {
            const r = xhr.responseJSON;
            mostrarErrorAlias((r && r.message)
                ? r.message
                : 'No se pudieron cargar los alias. ¿Se corrió el script 02 en esta base?');
        });
}

function mostrarErrorAlias(msg) {
    $('#tablaAlias tbody').html(`
        <tr><td colspan="6" class="text-center text-danger py-4">
            <i class="bi bi-exclamation-triangle"></i> ${escaparAbm(msg)}
        </td></tr>`);
}

function renderizarTablaAlias(filas) {
    const $tb = $('#tablaAlias tbody').empty();

    if (!filas || filas.length === 0) {
        $tb.html(`
            <tr><td colspan="6" class="text-center text-muted py-4">
                Todavía no hay alias configurados. Todos los proveedores se muestran
                con las 2 primeras letras de su nombre, en gris.
            </td></tr>`);
        return;
    }

    filas.forEach(f => {
        $tb.append(`
            <tr>
                <td><span class="alias-chip">${escaparAbm(f.ALIAS)}</span></td>
                <td><code>${escaparAbm(f.COD_PROVEE)}</code></td>
                <td>${escaparAbm(f.PROVEEDOR || '-')}</td>
                <td>${f.OCS}</td>
                <td>
                    <span class="badge ${f.ACTIVO ? 'bg-success' : 'bg-secondary'}">
                        ${f.ACTIVO ? 'Activo' : 'Inactivo'}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-outline-primary btn-editar-alias"
                            data-cod="${escaparAbm(f.COD_PROVEE)}"
                            data-alias="${escaparAbm(f.ALIAS)}"
                            data-activo="${f.ACTIVO}">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger btn-eliminar-alias"
                            data-cod="${escaparAbm(f.COD_PROVEE)}"
                            data-alias="${escaparAbm(f.ALIAS)}">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>`);
    });
}

function cargarProveedoresAlias() {
    $.getJSON(ABM_ALIAS_BASE + 'gestionarAlias.php', { accion: 'proveedores' })
        .done(function (r) {
            if (!r.success) return;
            proveedoresAlias = r.data || [];

            const sin = proveedoresAlias.filter(p => p.TIENE_ALIAS === 0);
            const $cont = $('#proveedoresSinAlias').empty();

            if (sin.length > 0) {
                $cont.html(`
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>${sin.length}</strong> proveedor${sin.length === 1 ? '' : 'es'}
                        del cronograma sin alias:
                        ${sin.slice(0, 8).map(p =>
                            `<code>${escaparAbm(p.COD_PROVEE)}</code>`).join(' ')}
                        ${sin.length > 8 ? `y ${sin.length - 8} más` : ''}
                    </div>`);
            }
        });
}

function abrirFormAlias(cod, alias, activo) {
    const editando = !!cod;

    $('#formAliasTitulo').text(editando ? 'Editar alias' : 'Nuevo alias');
    $('#aliasError').prop('hidden', true).text('');

    const $sel = $('#aliasProveedor').empty();
    proveedoresAlias.forEach(p => {
        // Al editar se fija el proveedor; al crear solo se ofrecen los que no
        // tienen alias todavia.
        if (editando ? p.COD_PROVEE !== cod : p.TIENE_ALIAS === 1) return;
        $sel.append($('<option>', {
            value: p.COD_PROVEE,
            text: `${p.PROVEEDOR} (${p.COD_PROVEE}) · ${p.OCS} OCs`
        }));
    });

    if ($sel.children().length === 0) {
        avisoAbm('info', 'Sin proveedores pendientes',
                 'Todos los proveedores del cronograma ya tienen alias.');
        return;
    }

    $sel.prop('disabled', editando);
    $('#aliasValor').val(alias || '');
    $('#formAliasPanel').data('editando', editando ? cod : '').prop('hidden', false);
    actualizarPreviewAlias();
    $('#aliasValor').trigger('focus');
}

function actualizarPreviewAlias() {
    const v = ($('#aliasValor').val() || '').toUpperCase();
    $('#previewAliasTexto')
        .text(v || '??')
        .toggleClass('provisorio', v.length !== 2);
}

// ---- eventos, todos delegados ----
$(document).on('click', '#btnNuevoAlias', function () {
    abrirFormAlias(null, '', 1);
});

$(document).on('click', '.btn-editar-alias', function () {
    const $b = $(this);
    abrirFormAlias($b.data('cod'), $b.data('alias'), $b.data('activo'));
});

$(document).on('click', '#btnCancelarAlias', function () {
    $('#formAliasPanel').prop('hidden', true);
});

// Solo mayúsculas A-Z: se filtra al tipear para que el campo nunca llegue al
// servidor con algo que el CHECK binario de la tabla vaya a rechazar.
$(document).on('input', '#aliasValor', function () {
    const limpio = ($(this).val() || '').toUpperCase().replace(/[^A-Z]/g, '').slice(0, 2);
    $(this).val(limpio);
    actualizarPreviewAlias();
    $('#aliasError').prop('hidden', true);
});

$(document).on('click', '#btnGuardarAlias', function () {
    const cod = $('#aliasProveedor').val();
    const alias = ($('#aliasValor').val() || '').toUpperCase();

    if (!/^[A-Z]{2}$/.test(alias)) {
        $('#aliasError').prop('hidden', false)
            .text('El alias debe ser exactamente 2 letras de la A a la Z.');
        return;
    }

    const $btn = $(this).prop('disabled', true);

    $.post(ABM_ALIAS_BASE + 'gestionarAlias.php', {
        accion: 'guardar', cod_provee: cod, alias: alias, activo: 1
    }, null, 'json')
        .done(function (r) {
            if (!r.success) {
                $('#aliasError').prop('hidden', false).text(r.message);
                return;
            }
            $('#formAliasPanel').prop('hidden', true);
            aliasCargados = false;
            cargarAlias();
            avisoAbm('success', 'Alias guardado', '');
            // El cronograma escucha esto para repintar sin recargar.
            $(document).trigger('alias:cambiado');
        })
        .fail(function (xhr) {
            const r = xhr.responseJSON;
            // El mensaje del servidor dice a qué proveedor pertenece un alias
            // duplicado; se muestra tal cual.
            $('#aliasError').prop('hidden', false)
                .text((r && r.message) ? r.message : 'No se pudo guardar el alias.');
        })
        .always(function () {
            $btn.prop('disabled', false);
        });
});

$(document).on('click', '.btn-eliminar-alias', function () {
    const cod = $(this).data('cod');
    const alias = $(this).data('alias');

    const borrar = () => {
        $.post(ABM_ALIAS_BASE + 'gestionarAlias.php', { accion: 'eliminar', cod_provee: cod }, null, 'json')
            .done(function (r) {
                if (!r.success) { avisoAbm('error', 'Error', r.message); return; }
                aliasCargados = false;
                cargarAlias();
                $(document).trigger('alias:cambiado');
            })
            .fail(function (xhr) {
                const r = xhr.responseJSON;
                avisoAbm('error', 'Error', (r && r.message) ? r.message : 'No se pudo eliminar.');
            });
    };

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'warning',
            title: `¿Eliminar el alias ${alias}?`,
            text: 'El proveedor va a volver a mostrarse con las 2 primeras letras de su nombre.',
            showCancelButton: true,
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar'
        }).then(res => { if (res.isConfirmed) borrar(); });
    } else if (confirm(`¿Eliminar el alias ${alias}?`)) {
        borrar();
    }
});
