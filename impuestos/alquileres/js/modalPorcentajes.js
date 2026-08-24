/**
 * JavaScript para Gestión de Porcentajes en Modal
 * Manejo de operaciones CRUD para porcentajes de conceptos
 */

// Variable global para almacenar el concepto seleccionado
let conceptoSeleccionadoModal = null;

/**
 * Abrir el modal de Parámetros (pestañas Porcentajes y Sucursales).
 * Sólo carga la pestaña de Porcentajes: la de Sucursales se carga al entrar en ella
 * (ver js/modalSucursales.js).
 */
function abrirModalParametros() {
    console.log("🔧 Abriendo modal de parámetros");
    $('#modalPorcentajes').modal('show');

    // Siempre arranca en la primera pestaña
    $('#tab-porcentajes').tab('show');

    // Cargar datos iniciales del primer concepto
    const conceptoSelect = document.getElementById('conceptosModal');
    if (conceptoSelect && conceptoSelect.options.length > 0) {
        conceptoSeleccionadoModal = conceptoSelect.value;
        cargarPorcentajesModal(conceptoSeleccionadoModal);
    }
}

/**
 * Filtrar porcentajes por concepto seleccionado
 */
function filtrarPorcentajesModal() {
    const conceptoSelect = document.getElementById('conceptosModal');
    conceptoSeleccionadoModal = conceptoSelect.value;
    
    console.log("🔍 Filtrando por concepto:", conceptoSeleccionadoModal);
    cargarPorcentajesModal(conceptoSeleccionadoModal);
}

/**
 * Cargar porcentajes de un concepto específico
 */
function cargarPorcentajesModal(concepto) {
    const idConcepto = concepto.split("-")[0];
    const tbody = document.getElementById('tbodyPorcentajes');
    
    // Mostrar loading
    tbody.innerHTML = `
        <tr>
            <td colspan="4" class="porcentajes-loading">
                <i class="bi bi-arrow-repeat"></i>
            </td>
        </tr>
    `;
    
    $.ajax({
        url: 'Controller/PorcentajeController.php?accion=traerPorcentajesPorConcepto',
        method: 'POST',
        data: { idConcepto: idConcepto },
        dataType: 'json',
        success: function(data) {
            console.log("📊 Porcentajes recibidos:", data);
            renderizarTablaPorcentajes(data);
        },
        error: function(xhr, status, error) {
            console.error("❌ Error al cargar porcentajes:", error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center text-danger">
                        Error al cargar los datos. Por favor, intente nuevamente.
                    </td>
                </tr>
            `;
        }
    });
}

/**
 * Renderizar tabla de porcentajes
 */
function renderizarTablaPorcentajes(porcentajes) {
    const tbody = document.getElementById('tbodyPorcentajes');
    
    if (!porcentajes || porcentajes.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="porcentajes-empty">
                    <i class="bi bi-inbox"></i>
                    <p>No hay porcentajes configurados para este concepto</p>
                </td>
            </tr>
        `;
        return;
    }
    
    let html = '';
    porcentajes.forEach(item => {
        html += `
            <tr data-id="${item.ID_PA}">
                <td class="text-center"><strong>${item.NRO_SUCURS}</strong></td>
                <td>${item.DESC_SUCURS}</td>
                <td>
                    <input type="number" 
                           class="form-control form-control-sm" 
                           value="${item.PORCENTAJE}" 
                           step="0.01"
                           min="0"
                           max="100"
                           onchange="actualizarPorcentajeModal(this, ${item.ID_PA})"
                           data-id="${item.ID_PA}">
                </td>
                <td class="text-center">
                    <button type="button" 
                            class="btn btn-danger btn-sm" 
                            onclick="eliminarPorcentajeModal(${item.ID_PA})"
                            title="Eliminar">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

/**
 * Agregar nuevo porcentaje
 */
function agregarPorcentajeModal() {
    const conceptoSelect = document.getElementById('conceptosModal');
    const localSelect = document.getElementById('localesModal');
    
    const concepto = conceptoSelect.value;
    const idConcepto = concepto.split("-")[0];
    const idLocal = localSelect.value;
    const descLocal = localSelect.options[localSelect.selectedIndex].text;
    
    console.log("➕ Agregando porcentaje:", { idConcepto, idLocal, descLocal });
    
    $.ajax({
        url: 'Controller/PorcentajeController.php?accion=insertarPorcentaje',
        method: 'POST',
        data: {
            idConcepto: idConcepto,
            idLocal: idLocal,
            descLocal: descLocal
        },
        success: function(data) {
            console.log("📥 Respuesta insertar:", data);
            
            if (data == 1) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Sucursal Agregada!',
                    text: 'La sucursal se ha agregado correctamente',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#667eea',
                    timer: 2000,
                    timerProgressBar: true
                }).then(() => {
                    // Recargar tabla del modal
                    cargarPorcentajesModal(concepto);
                });
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sucursal Existente',
                    text: 'Esta sucursal ya tiene un porcentaje configurado para este concepto',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#f5576c'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error("❌ Error al agregar:", error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo agregar la sucursal. Intente nuevamente.',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#e74c3c'
            });
        }
    });
}

/**
 * Actualizar porcentaje
 */
function actualizarPorcentajeModal(input, id) {
    const porcentaje = parseFloat(input.value);
    
    // Validar rango
    if (porcentaje < 0 || porcentaje > 100) {
        Swal.fire({
            icon: 'warning',
            title: 'Valor inválido',
            text: 'El porcentaje debe estar entre 0 y 100',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#f39c12'
        });
        input.focus();
        return;
    }
    
    console.log("📝 Actualizando porcentaje:", { id, porcentaje });
    
    $.ajax({
        url: 'Controller/PorcentajeController.php?accion=actualizarPorcentaje',
        method: 'POST',
        data: {
            id: id,
            porcentaje: porcentaje
        },
        success: function(data) {
            console.log("✅ Porcentaje actualizado:", data);
            
            // Mostrar feedback visual
            $(input).addClass('border-success');
            setTimeout(() => {
                $(input).removeClass('border-success');
            }, 1000);
            
            // Mostrar toast de éxito
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Porcentaje actualizado',
                showConfirmButton: false,
                timer: 1500,
                timerProgressBar: true
            });
        },
        error: function(xhr, status, error) {
            console.error("❌ Error al actualizar:", error);
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: 'Error al actualizar',
                showConfirmButton: false,
                timer: 2000
            });
        }
    });
}

/**
 * Eliminar porcentaje
 */
function eliminarPorcentajeModal(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "Se eliminará el porcentaje configurado para esta sucursal",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f5576c',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            console.log("🗑️ Eliminando porcentaje:", id);
            
            $.ajax({
                url: 'Controller/PorcentajeController.php?accion=eliminarPorcentaje',
                method: 'POST',
                data: { id: id },
                success: function(data) {
                    console.log("✅ Porcentaje eliminado:", data);
                    
                    Swal.fire({
                        icon: 'success',
                        title: '¡Eliminado!',
                        text: 'El porcentaje ha sido eliminado',
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#667eea',
                        timer: 2000,
                        timerProgressBar: true
                    }).then(() => {
                        // Recargar tabla
                        const conceptoSelect = document.getElementById('conceptosModal');
                        cargarPorcentajesModal(conceptoSelect.value);
                    });
                },
                error: function(xhr, status, error) {
                    console.error("❌ Error al eliminar:", error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo eliminar el porcentaje. Intente nuevamente.',
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#e74c3c'
                    });
                }
            });
        }
    });
}

// Event listener para cuando se cierra el modal
$('#modalPorcentajes').on('hidden.bs.modal', function () {
    console.log("🔒 Modal de porcentajes cerrado");
});

// Event listener para cuando se abre el modal
$('#modalPorcentajes').on('shown.bs.modal', function () {
    console.log("🔓 Modal de porcentajes abierto");
});
