/**
 * gestionTerminales.js - Gestión de terminales portuarias
 */

let tablaTerminales;
let modalTerminal;

// Cargar terminales cuando se activa la pestaña
$('#terminales-tab').on('shown.bs.tab', function() {
    if (!tablaTerminales) {
        cargarTerminales();
    }
});

$(document).ready(function() {
    console.log('Gestión de terminales lista');
    
    // Si la pestaña de terminales está activa al cargar, cargar datos
    if ($('#terminales-tab').hasClass('active')) {
        cargarTerminales();
    }
});

/**
 * Carga las terminales desde el servidor
 */
function cargarTerminales() {
    $.ajax({
        url: '../controller/gestionarTerminalesController.php',
        method: 'GET',
        data: { accion: 'listar' },
        dataType: 'json',
        success: function(response) {
            console.log('Terminales cargadas:', response);
            
            if (response.success) {
                renderizarTablaTerminales(response.terminales);
            } else {
                mostrarError('Error al cargar terminales: ' + response.error);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            mostrarError('Error al conectar con el servidor');
        }
    });
}

/**
 * Renderiza la tabla de terminales
 */
function renderizarTablaTerminales(terminales) {
    // Destruir DataTable si existe
    if (tablaTerminales) {
        tablaTerminales.destroy();
    }
    
    const tbody = $('#tablaTerminales tbody');
    tbody.empty();
    
    if (!terminales || terminales.length === 0) {
        tbody.html('<tr><td colspan="6" class="text-center">No hay terminales configuradas</td></tr>');
        return;
    }
    
    terminales.forEach(function(terminal) {
        const estadoBadge = terminal.ACTIVO == 1 
            ? '<span class="badge bg-success">Activo</span>' 
            : '<span class="badge bg-secondary">Inactivo</span>';
        
        const entornoLabel = terminal.ENTORNO === 'ARG' ? 'Argentina' 
            : (terminal.ENTORNO === 'UY' ? 'Uruguay' : 'Ambos');
        
        const entornoBadge = terminal.ENTORNO === 'ARG' 
            ? '<span class="badge bg-primary">Argentina</span>'
            : (terminal.ENTORNO === 'UY' 
                ? '<span class="badge bg-info">Uruguay</span>' 
                : '<span class="badge bg-success">Ambos</span>');
        
        // Formatear fecha
        let fechaModificacion = '-';
        if (terminal.FECHA_MODIFICACION && terminal.FECHA_MODIFICACION.date) {
            const fecha = new Date(terminal.FECHA_MODIFICACION.date);
            fechaModificacion = fecha.toLocaleDateString('es-AR') + ' ' + fecha.toLocaleTimeString('es-AR', {hour: '2-digit', minute: '2-digit'});
        }
        
        const fila = `
            <tr>
                <td>${terminal.ID}</td>
                <td><strong>${terminal.NOMBRE}</strong></td>
                <td>${entornoBadge}</td>
                <td>${estadoBadge}</td>
                <td>${fechaModificacion}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="editarTerminal(${terminal.ID})" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="eliminarTerminal(${terminal.ID}, '${terminal.NOMBRE}')" title="Desactivar">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        
        tbody.append(fila);
    });
    
    // Inicializar DataTable
    tablaTerminales = $('#tablaTerminales').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.1/i18n/es-ES.json'
        },
        order: [[2, 'asc'], [1, 'asc']],
        pageLength: 25,
        responsive: true
    });
}

/**
 * Abre el modal para crear una nueva terminal
 */
function abrirModalNuevaTerminal() {
    if (!modalTerminal) {
        modalTerminal = new bootstrap.Modal(document.getElementById('modalTerminal'));
    }
    
    $('#modalTerminalTitulo').html('<i class="bi bi-building"></i> Nueva Terminal');
    $('#formTerminal')[0].reset();
    $('#terminalId').val('');
    modalTerminal.show();
}

/**
 * Edita una terminal existente
 */
function editarTerminal(id) {
    // Buscar los datos de la terminal en la tabla
    const fila = tablaTerminales.row(function(idx, data, node) {
        return $(node).find('td:first').text() == id;
    }).node();
    
    if (!fila) {
        mostrarError('No se encontró la terminal');
        return;
    }
    
    const celdas = $(fila).find('td');
    const nombre = celdas.eq(1).find('strong').text();
    const entornoBadge = celdas.eq(2).find('.badge').text().trim();
    const estadoBadge = celdas.eq(3).find('.badge').text().trim();
    
    if (!modalTerminal) {
        modalTerminal = new bootstrap.Modal(document.getElementById('modalTerminal'));
    }
    
    const entorno = entornoBadge === 'Argentina' ? 'ARG' 
        : (entornoBadge === 'Uruguay' ? 'UY' : 'AMBOS');
    const activo = estadoBadge === 'Activo' ? '1' : '0';
    
    // Llenar el formulario
    $('#modalTerminalTitulo').html('<i class="bi bi-pencil"></i> Editar Terminal');
    $('#terminalId').val(id);
    $('#terminalNombre').val(nombre);
    $('#terminalEntorno').val(entorno);
    $('#terminalActivo').val(activo);
    
    modalTerminal.show();
}

/**
 * Guarda una terminal (nueva o editada)
 */
function guardarTerminal() {
    const id = $('#terminalId').val();
    const nombre = $('#terminalNombre').val().trim();
    const entorno = $('#terminalEntorno').val();
    const activo = $('#terminalActivo').val();
    
    // Validaciones
    if (!nombre) {
        Swal.fire({
            icon: 'warning',
            title: 'Atención',
            text: 'El nombre de la terminal es obligatorio'
        });
        return;
    }
    
    if (!entorno) {
        Swal.fire({
            icon: 'warning',
            title: 'Atención',
            text: 'Debe seleccionar un país/región'
        });
        return;
    }
    
    const accion = id ? 'actualizar' : 'insertar';
    const datos = {
        accion: accion,
        nombre: nombre,
        entorno: entorno,
        activo: activo
    };
    
    if (id) {
        datos.id = id;
    }
    
    // Mostrar loading
    Swal.fire({
        title: 'Guardando...',
        allowOideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Enviar al servidor
    $.ajax({
        url: '../controller/gestionarTerminalesController.php',
        method: 'POST',
        data: datos,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                
                modalTerminal.hide();
                cargarTerminales();
            } else {
                mostrarError(response.error || 'Error al guardar');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            mostrarError('Error al conectar con el servidor');
        }
    });
}

/**
 * Elimina (desactiva) una terminal
 */
function eliminarTerminal(id, nombre) {
    Swal.fire({
        title: '¿Desactivar terminal?',
        text: `Se desactivará la terminal "${nombre}". Podrá reactivarla editándola.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, desactivar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../controller/gestionarTerminalesController.php',
                method: 'POST',
                data: {
                    accion: 'eliminar',
                    id: id
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Desactivada!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        
                        cargarTerminales();
                    } else {
                        mostrarError(response.error || 'Error al desactivar');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    mostrarError('Error al conectar con el servidor');
                }
            });
        }
    });
}

/**
 * Muestra un mensaje de error
 */
function mostrarError(mensaje) {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: mensaje,
        confirmButtonColor: '#dc2626'
    });
}
