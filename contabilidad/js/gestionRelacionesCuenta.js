
// Funciones existentes
const traerDescCuenta = (div) => {
    select = div.selectedIndex;
    let desc = div.querySelectorAll("option")[select].getAttribute('attr-desc-cuenta')
    document.querySelector('#descCuenta').textContent = desc;
}

const traerDescRubro = (div) => {
    select = div.selectedIndex;
    let desc = div.querySelectorAll("option")[select].getAttribute('attr-desc-rubro');
    document.querySelector('#rubroContable').textContent = desc;
}

const traerDescProrrateo = (div) => {
    select = div.selectedIndex;
    let desc = div.querySelectorAll("option")[select].getAttribute('attr-desc-prorrateo');
    document.querySelector('#descProrrateo').textContent = desc;
}

// Función para agregar una nueva relación
const agregar = () => {
    let codCuenta = document.querySelector('#codCuenta').value;
    let sector = document.querySelector('#sector').value;
    let codRubro = document.querySelector('#codRubro').value;
    let codProrrateo = document.querySelector('#codProrrateo').value;

    // Validación básica
    if (!codCuenta || !sector || !codRubro || !codProrrateo) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Todos los campos son obligatorios',
            showConfirmButton: true
        });
        return;
    }

    $.ajax({
        url: "Controller/gestionRelacionesController.php?accion=insert",
        method: "POST",
        data: {
            codCuenta: codCuenta,
            sector: sector,
            codRubro: codRubro,
            codProrrateo: codProrrateo,
        },
        success: function (data) {
            if(data == 1){
                Swal.fire({
                    icon: 'success',
                    title: 'Registro agregado correctamente',
                    showConfirmButton: true
                }).then((result) => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Ya existe un Registro con el Cod. Cuenta y Sector Seleccionados',
                    showConfirmButton: true
                });
            }
        }
    });
}

// Funciones para el modal de edición
const traerDescRubroEdit = (div) => {
    if (!div) return;
    
    const select = div.selectedIndex;
    if (select >= 0) {
        const desc = div.options[select].getAttribute('attr-desc-rubro');
        if (desc) {
            const descRubroElement = document.querySelector('#editRubroContable');
            if (descRubroElement) {
                descRubroElement.textContent = desc;
            }
        }
    }
}

const traerDescProrrateoEdit = (div) => {
    if (!div) return;
    
    const select = div.selectedIndex;
    if (select >= 0) {
        const desc = div.options[select].getAttribute('attr-desc-prorrateo');
        if (desc) {
            const descProrrateoElement = document.querySelector('#editDescProrrateo');
            if (descProrrateoElement) {
                descProrrateoElement.textContent = desc;
            }
        }
    }
}

// Función para editar una relación
const editarRelacion = (id, codCuenta, sector, codRubro, codProrrateo) => {
    // Primero mostramos el modal para asegurar que los elementos existan en el DOM
    $('#editarModal').modal('show');
    
    // Esperamos un momento para asegurar que el modal se ha renderizado completamente
    setTimeout(() => {
        // Guardar el ID de la relación que se está editando
        document.querySelector('#idRelacion').value = id;
        
        // Establecer los valores de los campos no editables
        document.querySelector('#editCodCuenta').value = codCuenta;
        document.querySelector('#editSector').value = sector;
        
        // Obtener la descripción de la cuenta desde el select principal
        const selectCodCuenta = document.querySelector('#codCuenta');
        if (selectCodCuenta) {
            for (let i = 0; i < selectCodCuenta.options.length; i++) {
                if (selectCodCuenta.options[i].value === codCuenta) {
                    const descCuenta = selectCodCuenta.options[i].getAttribute('attr-desc-cuenta');
                    document.querySelector('#editDescCuenta').value = descCuenta || '';
                    break;
                }
            }
        }
        
        // Establecer los valores de los campos editables
        const selectCodRubro = document.querySelector('#editCodRubro');
        const selectCodProrrateo = document.querySelector('#editCodProrrateo');
        
        // Seleccionar las opciones correspondientes
        setSelectedValue(selectCodRubro, codRubro);
        setSelectedValue(selectCodProrrateo, codProrrateo);
        
        // Mostrar las descripciones
        traerDescRubroEdit(selectCodRubro);
        traerDescProrrateoEdit(selectCodProrrateo);
        
        // Disparar eventos de cambio para actualizar los selects de Select2
        $(selectCodRubro).trigger('change');
        $(selectCodProrrateo).trigger('change');
    }, 300);
}

// Función auxiliar para seleccionar una opción en un select
const setSelectedValue = (selectElement, value) => {
    if (!selectElement) return;
    
    for(let i = 0; i < selectElement.options.length; i++) {
        if(selectElement.options[i].value == value) {
            selectElement.selectedIndex = i;
            break;
        }
    }
}

// Función para guardar los cambios de edición
const guardarCambios = () => {
    const id = document.querySelector('#idRelacion').value;
    const codCuenta = document.querySelector('#editCodCuenta').value;
    const sector = document.querySelector('#editSector').value;
    const codRubro = document.querySelector('#editCodRubro').value;
    const codProrrateo = document.querySelector('#editCodProrrateo').value;
    
    // Validación básica
    if (!codCuenta || !sector || !codRubro || !codProrrateo) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Todos los campos son obligatorios',
            showConfirmButton: true
        });
        return;
    }
    
    // Mostrar loading
    Swal.fire({
        title: 'Guardando cambios...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    $.ajax({
        url: "Controller/gestionRelacionesController.php?accion=update",
        method: "POST",
        data: {
            id: id,
            codCuenta: codCuenta,
            sector: sector,
            codRubro: codRubro,
            codProrrateo: codProrrateo
        },
        success: function (data) {
            if(data == 1) {
                Swal.fire({
                    icon: 'success',
                    title: 'Relación actualizada correctamente',
                    showConfirmButton: true
                }).then((result) => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al actualizar la relación',
                    text: 'No se pudo actualizar la relación',
                    showConfirmButton: true
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor',
                showConfirmButton: true
            });
        }
    });
}

// Función para eliminar una relación
const eliminarRelacion = (id) => {
    Swal.fire({
        title: '¿Está seguro?',
        text: "Esta acción no se puede revertir",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Eliminando...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            $.ajax({
                url: "Controller/gestionRelacionesController.php?accion=delete",
                method: "POST",
                data: {
                    id: id
                },
                success: function (data) {
                    if(data == 1) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Relación eliminada correctamente',
                            showConfirmButton: true
                        }).then((result) => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al eliminar la relación',
                            text: 'No se pudo eliminar la relación',
                            showConfirmButton: true
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de conexión',
                        text: 'No se pudo conectar con el servidor',
                        showConfirmButton: true
                    });
                }
            });
        }
    });
}

// Función para cambiar el entorno (toggle de banderas ARG / UY)
const cambiarEntornoCustom = (container) => {
    const inactiveFlag = container.querySelector('.toggle-flag:not(.active)');
    if (!inactiveFlag) return;

    // cambiarEntorno.php espera 0 = central (ARG), 1 = uy
    const entorno = inactiveFlag.getAttribute('data-entorno') === 'uy' ? 1 : 0;

    $.ajax({
      url: "Controller/cambiarEntorno.php",
      method: "POST",
      data: { entorno: entorno },
      success: function (data) {
        location.reload();
      }
    });
}

// Inicializar Select2 para el modal cuando el documento esté listo y cuando se abra el modal
$(document).ready(function() {
    // Inicializar Select2 cuando se abre el modal
    $('#editarModal').on('shown.bs.modal', function() {
        $('.editCodRubro').select2({
            dropdownParent: $('#editarModal'),
            width: '100%'
        });
        $('.editCodProrrateo').select2({
            dropdownParent: $('#editarModal'),
            width: '100%'
        });
    });
});