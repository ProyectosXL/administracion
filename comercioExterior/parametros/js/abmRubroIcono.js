/**
 * abmRubroIcono.js - ABM de iconos por rubro.
 *
 * El icono puede ser una clase de Bootstrap Icons o un emoji: la vista previa
 * renderiza los dos formatos igual que el badge del calendario.
 */

let rubrosIconoCargados = false;
let modalRubroIcono = null;

$('#rubros-tab').on('shown.bs.tab', function () {
    if (!rubrosIconoCargados) cargarRubroIconos();
});

$(document).ready(function () {
    if ($('#rubros-tab').hasClass('active')) cargarRubroIconos();
});

function escaparRi(t) {
    return String(t === null || t === undefined ? '' : t)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

/** Misma regla que el calendario: clase bi-* o emoji suelto. */
function renderIconoRi(valor) {
    if (typeof valor === 'string' && valor.indexOf('bi-') === 0) {
        return `<i class="bi ${escaparRi(valor)}"></i>`;
    }
    return escaparRi(valor);
}

function cargarRubroIconos() {
    $.getJSON('controller/gestionarRubroIcono.php', { accion: 'listar' })
        .done(function (r) {
            if (!r.success) {
                $('#tablaRubroIcono tbody').html(
                    `<tr><td colspan="7" class="text-center text-danger py-4">${escaparRi(r.message)}</td></tr>`);
                return;
            }
            rubrosIconoCargados = true;
            renderizarTablaRubroIcono(r.data);
            renderizarSinMapear(r.sinMapear);
        })
        .fail(function (xhr) {
            const r = xhr.responseJSON;
            $('#tablaRubroIcono tbody').html(`
                <tr><td colspan="7" class="text-center text-danger py-4">
                    ${escaparRi((r && r.message) ? r.message
                        : 'No se pudieron cargar los iconos. ¿Se corrió el script 03 en esta base?')}
                </td></tr>`);
        });
}

function renderizarTablaRubroIcono(filas) {
    const $tb = $('#tablaRubroIcono tbody').empty();

    if (!filas || filas.length === 0) {
        $tb.html('<tr><td colspan="7" class="text-center text-muted py-4">Sin iconos configurados.</td></tr>');
        return;
    }

    filas.forEach(f => {
        $tb.append(`
            <tr>
                <td class="icono-celda">${renderIconoRi(f.ICONO)}</td>
                <td>${escaparRi(f.RUBRO)}</td>
                <td><code>${escaparRi(f.ICONO)}</code></td>
                <td>${f.ARTICULOS > 0
                        ? f.ARTICULOS.toLocaleString('es-AR')
                        : '<span class="text-muted" title="No existe en SOF_RUBROS_TANGO">sin uso</span>'}</td>
                <td>${f.ORDEN}</td>
                <td><span class="badge ${f.ACTIVO ? 'bg-success' : 'bg-secondary'}">
                        ${f.ACTIVO ? 'Activo' : 'Inactivo'}</span></td>
                <td>
                    <button class="btn btn-sm btn-outline-primary btn-editar-rubro-icono"
                            data-id="${f.ID}" data-rubro="${escaparRi(f.RUBRO)}"
                            data-icono="${escaparRi(f.ICONO)}" data-orden="${f.ORDEN}"
                            data-activo="${f.ACTIVO}">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger btn-eliminar-rubro-icono"
                            data-id="${f.ID}" data-rubro="${escaparRi(f.RUBRO)}">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>`);
    });
}

/** Rubros que existen en Tango pero nadie mapeó: caen en bi-tag en el calendario. */
function renderizarSinMapear(sinMapear) {
    const $c = $('#rubrosSinMapear').empty();
    const $lista = $('#listaRubrosTango').empty();

    if (!sinMapear || sinMapear.length === 0) {
        $c.html(`
            <div class="alert alert-success mb-0">
                <i class="bi bi-check-circle"></i>
                Todos los rubros de Tango tienen icono asignado.
            </div>`);
        return;
    }

    sinMapear.forEach(s => $lista.append($('<option>', { value: s.RUBRO })));

    $c.html(`
        <div class="alert alert-warning mb-0">
            <i class="bi bi-exclamation-triangle"></i>
            <strong>${sinMapear.length}</strong> rubro${sinMapear.length === 1 ? '' : 's'} de Tango
            sin icono. En el calendario caen en <code>bi-tag</code>:
            ${sinMapear.slice(0, 6).map(s =>
                `<button type="button" class="btn btn-sm btn-link p-0 btn-mapear-rubro"
                         data-rubro="${escaparRi(s.RUBRO)}">${escaparRi(s.RUBRO)}</button>`
            ).join(', ')}
            ${sinMapear.length > 6 ? ` y ${sinMapear.length - 6} más` : ''}
        </div>`);
}

function abrirModalRubroIcono(datos) {
    if (!modalRubroIcono) {
        modalRubroIcono = new bootstrap.Modal(document.getElementById('modalRubroIcono'));
    }
    $('#modalRubroIconoTitulo').html(datos.id
        ? '<i class="bi bi-tags"></i> Editar Icono de Rubro'
        : '<i class="bi bi-tags"></i> Nuevo Icono de Rubro');

    $('#rubroIconoId').val(datos.id || '');
    $('#rubroIconoRubro').val(datos.rubro || '').prop('disabled', !!datos.id);
    $('#rubroIconoValor').val(datos.icono || '');
    $('#rubroIconoOrden').val(datos.orden || 0);
    $('#rubroIconoActivo').val(String(datos.activo === undefined ? 1 : datos.activo));

    actualizarPreviewRubroIcono();
    modalRubroIcono.show();
}

function actualizarPreviewRubroIcono() {
    const v = $('#rubroIconoValor').val().trim();
    $('#previewRubroIconoGlifo').html(v ? renderIconoRi(v) : '<span class="text-muted">—</span>');
}

// ---- eventos ----
$(document).on('click', '#btnNuevoRubroIcono', () => abrirModalRubroIcono({}));

$(document).on('click', '.btn-mapear-rubro', function () {
    abrirModalRubroIcono({ rubro: $(this).data('rubro') });
});

$(document).on('click', '.btn-editar-rubro-icono', function () {
    const $b = $(this);
    abrirModalRubroIcono({
        id: $b.data('id'), rubro: $b.data('rubro'), icono: $b.data('icono'),
        orden: $b.data('orden'), activo: $b.data('activo')
    });
});

$(document).on('input', '#rubroIconoValor', actualizarPreviewRubroIcono);

$(document).on('click', '#btnGuardarRubroIcono', function () {
    const $btn = $(this).prop('disabled', true);

    $.post('controller/gestionarRubroIcono.php', {
        accion: 'guardar',
        id: $('#rubroIconoId').val(),
        rubro: $('#rubroIconoRubro').val().trim(),
        icono: $('#rubroIconoValor').val().trim(),
        orden: $('#rubroIconoOrden').val(),
        activo: $('#rubroIconoActivo').val()
    }, null, 'json')
        .done(function (r) {
            if (!r.success) { Swal.fire('Error', r.message, 'error'); return; }
            modalRubroIcono.hide();
            rubrosIconoCargados = false;
            cargarRubroIconos();
            Swal.fire({ icon: 'success', title: 'Icono guardado', timer: 1600, showConfirmButton: false });
        })
        .fail(function (xhr) {
            const r = xhr.responseJSON;
            Swal.fire('Error', (r && r.message) ? r.message : 'No se pudo guardar.', 'error');
        })
        .always(() => $btn.prop('disabled', false));
});

$(document).on('click', '.btn-eliminar-rubro-icono', function () {
    const id = $(this).data('id');
    const rubro = $(this).data('rubro');

    Swal.fire({
        icon: 'warning',
        title: `¿Eliminar el icono de ${rubro}?`,
        text: 'En el calendario ese rubro va a pasar a mostrarse con bi-tag.',
        showCancelButton: true,
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar'
    }).then(res => {
        if (!res.isConfirmed) return;
        $.post('controller/gestionarRubroIcono.php', { accion: 'eliminar', id: id }, null, 'json')
            .done(function (r) {
                if (!r.success) { Swal.fire('Error', r.message, 'error'); return; }
                rubrosIconoCargados = false;
                cargarRubroIconos();
            })
            .fail(function (xhr) {
                const r = xhr.responseJSON;
                Swal.fire('Error', (r && r.message) ? r.message : 'No se pudo eliminar.', 'error');
            });
    });
});
