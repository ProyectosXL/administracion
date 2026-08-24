/**
 * Pestaña "Sucursales" del modal de Parámetros.
 * Administra las excepciones a la regla automática de visibilidad y carga
 * (tabla RO_T_SUCURSALES_ALQUILERES_EXC).
 */

const URL_SUCURSALES_EXC = 'Controller/SucursalExcController.php';

// Se cargan una sola vez por apertura del modal; recién al entrar a la pestaña.
let sucursalesParametrosCargadas = false;

/**
 * Escapa texto que viene de la base antes de meterlo en el HTML de la tabla.
 * Los nombres y observaciones los escribe gente, no el sistema.
 */
function escaparHtml(texto) {
    if (texto === null || texto === undefined) return '';
    return String(texto)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

/**
 * Carga el listado completo de sucursales con su excepción (si tiene).
 */
function cargarSucursalesParametros() {
    const tbody = document.getElementById('tbodySucursales');

    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="porcentajes-loading">
                <i class="bi bi-arrow-repeat"></i>
            </td>
        </tr>
    `;

    $.ajax({
        url: URL_SUCURSALES_EXC + '?accion=traerSucursales',
        method: 'GET',
        dataType: 'json',
        success: function (respuesta) {
            if (!respuesta || !respuesta.success) {
                mostrarErrorSucursales(respuesta && respuesta.message);
                return;
            }
            renderizarTablaSucursales(respuesta.data);
            sucursalesParametrosCargadas = true;
        },
        error: function (xhr) {
            let mensaje = null;
            try {
                mensaje = JSON.parse(xhr.responseText).message;
            } catch (e) {
                mensaje = null;
            }
            mostrarErrorSucursales(mensaje);
        }
    });
}

function mostrarErrorSucursales(mensaje) {
    document.getElementById('tbodySucursales').innerHTML = `
        <tr>
            <td colspan="7" class="text-center text-danger py-4">
                ${escaparHtml(mensaje || 'No se pudieron cargar las sucursales. Intentá nuevamente.')}
            </td>
        </tr>
    `;
}

/**
 * Combo de tres estados: automático (''), sí (1), no (0).
 */
function comboExcepcion(nroSucursal, campo, valorActual, etiquetaSi, etiquetaNo) {
    const valor = (valorActual === null || valorActual === undefined) ? '' : String(valorActual);

    return `
        <select class="form-control form-control-sm"
                data-campo="${campo}"
                onchange="guardarExcepcionSucursal('${nroSucursal}')">
            <option value=""  ${valor === ''  ? 'selected' : ''}>Automático</option>
            <option value="1" ${valor === '1' ? 'selected' : ''}>${etiquetaSi}</option>
            <option value="0" ${valor === '0' ? 'selected' : ''}>${etiquetaNo}</option>
        </select>
    `;
}

function renderizarTablaSucursales(sucursales) {
    const tbody = document.getElementById('tbodySucursales');

    if (!sucursales || sucursales.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="porcentajes-empty">
                    <i class="bi bi-inbox"></i>
                    <p>No hay sucursales para mostrar</p>
                </td>
            </tr>
        `;
        return;
    }

    let html = '';

    sucursales.forEach(suc => {
        const nro = String(suc.NRO_SUCURSAL).trim();

        // Normalizamos: sqlsrv puede devolver el bit como true/false o 1/0.
        const visibleExc = (suc.VISIBLE_EXPLICITA === null || suc.VISIBLE_EXPLICITA === undefined)
            ? null : (suc.VISIBLE_EXPLICITA ? 1 : 0);
        const cargaExc = (suc.CARGA_EXPLICITA === null || suc.CARGA_EXPLICITA === undefined)
            ? null : (suc.CARGA_EXPLICITA ? 1 : 0);

        const tieneExcepcion = visibleExc !== null || cargaExc !== null;

        const estado = suc.HABILITADO
            ? '<span class="badge badge-success">Activa</span>'
            : '<span class="badge badge-secondary">Cerrada</span>';

        const historial = suc.TIENE_DATOS
            ? ''
            : ' <i class="bi bi-dash-circle text-muted" title="Sin historial de alquileres"></i>';

        html += `
            <tr data-nro="${escaparHtml(nro)}" class="${tieneExcepcion ? 'fila-excepcion' : ''}">
                <td class="text-center"><strong>${escaparHtml(nro)}</strong></td>
                <td>${escaparHtml(suc.DESC_SUCURSAL)}${historial}</td>
                <td class="text-center">${estado}</td>
                <td>${comboExcepcion(nro, 'visible', visibleExc, 'Mostrar siempre', 'Ocultar siempre')}</td>
                <td>${comboExcepcion(nro, 'carga', cargaExc, 'Cargar siempre', 'Nunca cargar')}</td>
                <td>
                    <input type="text"
                           class="form-control form-control-sm"
                           data-campo="observacion"
                           maxlength="200"
                           placeholder="Motivo de la excepción"
                           value="${escaparHtml(suc.OBSERVACION)}"
                           onchange="guardarExcepcionSucursal('${nro}')">
                </td>
                <td class="text-center">
                    <button type="button"
                            class="btn btn-danger btn-sm"
                            onclick="limpiarExcepcionSucursal('${nro}')"
                            title="Volver al comportamiento automático"
                            ${tieneExcepcion ? '' : 'disabled'}>
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

/**
 * Guarda la excepción de una fila. Si los dos combos quedan en "Automático" y no hay
 * observación, el backend borra la fila y la sucursal vuelve a resolverse sola.
 */
function guardarExcepcionSucursal(nroSucursal) {
    const fila = document.querySelector(`#tbodySucursales tr[data-nro="${nroSucursal}"]`);
    if (!fila) return;

    const visible = fila.querySelector('[data-campo="visible"]').value;
    const carga = fila.querySelector('[data-campo="carga"]').value;
    const observacion = fila.querySelector('[data-campo="observacion"]').value;

    $.ajax({
        url: URL_SUCURSALES_EXC + '?accion=guardarExcepcion',
        method: 'POST',
        dataType: 'json',
        data: {
            nroSucursal: nroSucursal,
            visible: visible,
            carga: carga,
            observacion: observacion
        },
        success: function (respuesta) {
            if (!respuesta || !respuesta.success) {
                avisoSucursales('error', (respuesta && respuesta.message) || 'No se pudo guardar');
                return;
            }

            const tieneExcepcion = visible !== '' || carga !== '';
            fila.classList.toggle('fila-excepcion', tieneExcepcion);
            fila.querySelector('button').disabled = !tieneExcepcion;

            avisoSucursales('success', 'Parámetro guardado');
            marcarParametrosSucursalesTocados();
        },
        error: function (xhr) {
            let mensaje = 'No se pudo guardar';
            try {
                mensaje = JSON.parse(xhr.responseText).message || mensaje;
            } catch (e) { /* se usa el mensaje por defecto */ }
            avisoSucursales('error', mensaje);
        }
    });
}

/**
 * Vuelve una sucursal al comportamiento automático.
 */
function limpiarExcepcionSucursal(nroSucursal) {
    Swal.fire({
        title: '¿Volver a automático?',
        html: `La sucursal <strong>${escaparHtml(nroSucursal)}</strong> va a pasar a resolverse sola:
               se muestra si está activa o tiene historial, y se carga si está activa.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#667eea',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, volver a automático',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (!result.isConfirmed) return;

        $.ajax({
            url: URL_SUCURSALES_EXC + '?accion=eliminarExcepcion',
            method: 'POST',
            dataType: 'json',
            data: { nroSucursal: nroSucursal },
            success: function (respuesta) {
                if (!respuesta || !respuesta.success) {
                    avisoSucursales('error', (respuesta && respuesta.message) || 'No se pudo eliminar');
                    return;
                }
                cargarSucursalesParametros();
                avisoSucursales('success', 'Excepción eliminada');
                marcarParametrosSucursalesTocados();
            },
            error: function () {
                avisoSucursales('error', 'No se pudo eliminar la excepción');
            }
        });
    });
}

function avisoSucursales(icono, titulo) {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: icono,
        title: titulo,
        showConfirmButton: false,
        timer: icono === 'error' ? 2500 : 1500,
        timerProgressBar: true
    });
}

/*
    Los cambios acá alteran qué sucursales entran en el período, y la grilla de carga
    ya está renderizada: sin recargar, la pantalla queda mostrando algo que no coincide
    con los parámetros. Se avisa al cerrar el modal en vez de recargar de prepo, para no
    perder ediciones sin guardar.
*/
let parametrosSucursalesTocados = false;

function marcarParametrosSucursalesTocados() {
    parametrosSucursalesTocados = true;
}

$('#modalPorcentajes').on('hidden.bs.modal', function () {
    if (!parametrosSucursalesTocados) return;

    parametrosSucursalesTocados = false;
    sucursalesParametrosCargadas = false;

    Swal.fire({
        icon: 'info',
        title: 'Cambiaste parámetros de sucursales',
        text: 'Hay que recargar la pantalla para que la grilla refleje los cambios.',
        showCancelButton: true,
        confirmButtonText: 'Recargar ahora',
        cancelButtonText: 'Después',
        confirmButtonColor: '#667eea',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            location.reload();
        }
    });
});

// Carga diferida: recién al entrar por primera vez a la pestaña.
$(document).on('shown.bs.tab', '#tab-sucursales', function () {
    if (!sucursalesParametrosCargadas) {
        cargarSucursalesParametros();
    }
});
