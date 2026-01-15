/**
 * gestionPuertos.js - Gestión de puertos de origen
 */

let tablaPuertos;
let modalPuerto;

// Cargar puertos cuando se activa la pestaña
$('#puertos-tab').on('shown.bs.tab', function() {
    if (!tablaPuertos) {
        cargarPuertos();
    }
});

$(document).ready(function() {
    console.log('Gestión de puertos lista');
    
    // Si la pestaña de puertos está activa al cargar, cargar datos
    if ($('#puertos-tab').hasClass('active')) {
        cargarPuertos();
    }
});

/**
 * Carga los puertos desde el servidor
 */
function cargarPuertos() {
    $.ajax({
        url: '../controller/gestionarPuertosController.php',
        method: 'GET',
        data: { accion: 'listar' },
        dataType: 'json',
        success: function(response) {
            console.log('Puertos cargados:', response);
            
            if (response.success) {
                renderizarTablaPuertos(response.puertos);
            } else {
                mostrarError('Error al cargar puertos: ' + response.error);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            mostrarError('Error al conectar con el servidor');
        }
    });
}

/**
 * Renderiza la tabla de puertos
 */
function renderizarTablaPuertos(puertos) {
    // Destruir DataTable si existe
    if (tablaPuertos) {
        tablaPuertos.destroy();
    }
    
    const tbody = $('#tablaPuertos tbody');
    tbody.empty();
    
    if (!puertos || puertos.length === 0) {
        tbody.html('<tr><td colspan="6" class="text-center">No hay puertos configurados</td></tr>');
        return;
    }
    
    puertos.forEach(function(puerto) {
        const estadoBadge = puerto.ACTIVO == 1 
            ? '<span class="badge bg-success">Activo</span>' 
            : '<span class="badge bg-secondary">Inactivo</span>';
        
        // Formatear fecha
        let fechaModificacion = '-';
        if (puerto.FECHA_MODIFICACION && puerto.FECHA_MODIFICACION.date) {
            const fecha = new Date(puerto.FECHA_MODIFICACION.date);
            fechaModificacion = fecha.toLocaleDateString('es-AR') + ' ' + fecha.toLocaleTimeString('es-AR', {hour: '2-digit', minute: '2-digit'});
        }
        
        const fila = `
            <tr>
                <td>${puerto.ID}</td>
                <td><strong>${puerto.NOMBRE}</strong></td>
                <td>${puerto.PAIS || '-'}</td>
                <td>${estadoBadge}</td>
                <td>${fechaModificacion}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="editarPuerto(${puerto.ID})" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="eliminarPuerto(${puerto.ID}, '${puerto.NOMBRE}')" title="Desactivar">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        
        tbody.append(fila);
    });
    
    // Inicializar DataTable
    tablaPuertos = $('#tablaPuertos').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.1/i18n/es-ES.json'
        },
        order: [[1, 'asc']],
        pageLength: 25,
        responsive: true
    });
}

/**
 * Abre el modal para crear un nuevo puerto
 */
function abrirModalNuevoPuerto() {
    if (!modalPuerto) {
        modalPuerto = new bootstrap.Modal(document.getElementById('modalPuerto'));
    }
    
    $('#modalPuertoTitulo').html('<i class="bi bi-geo-alt"></i> Nuevo Puerto');
    $('#formPuerto')[0].reset();
    $('#puertoId').val('');
    modalPuerto.show();
}

/**
 * Edita un puerto existente
 */
function editarPuerto(id) {
    // Buscar los datos del puerto en la tabla
    const fila = tablaPuertos.row(function(idx, data, node) {
        return $(node).find('td:first').text() == id;
    }).node();
    
    if (!fila) {
        mostrarError('No se encontró el puerto');
        return;
    }
    
    const celdas = $(fila).find('td');
    const nombre = celdas.eq(1).find('strong').text();
    const pais = celdas.eq(2).text();
    const estadoBadge = celdas.eq(3).find('.badge').text().trim();
    const activo = estadoBadge === 'Activo' ? '1' : '0';
    
    if (!modalPuerto) {
        modalPuerto = new bootstrap.Modal(document.getElementById('modalPuerto'));
    }
    
    // Llenar el formulario
    $('#modalPuertoTitulo').html('<i class="bi bi-pencil"></i> Editar Puerto');
    $('#puertoId').val(id);
    $('#puertoNombre').val(nombre);
    $('#puertoPais').val(pais === '-' ? '' : pais);
    $('#puertoActivo').val(activo);
    
    modalPuerto.show();
}

/**
 * Guarda un puerto (nuevo o editado)
 */
function guardarPuerto() {
    const id = $('#puertoId').val();
    const nombre = $('#puertoNombre').val().trim();
    const pais = $('#puertoPais').val().trim();
    const activo = $('#puertoActivo').val();
    
    // Validaciones
    if (!nombre) {
        Swal.fire({
            icon: 'warning',
            title: 'Atención',
            text: 'El nombre del puerto es obligatorio'
        });
        return;
    }
    
    const accion = id ? 'actualizar' : 'insertar';
    const datos = {
        accion: accion,
        nombre: nombre,
        pais: pais || null,
        activo: activo
    };
    
    if (id) {
        datos.id = id;
    }
    
    // Mostrar loading
    Swal.fire({
        title: 'Guardando...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Enviar al servidor
    $.ajax({
        url: '../controller/gestionarPuertosController.php',
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
                
                modalPuerto.hide();
                cargarPuertos();
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
 * Elimina (desactiva) un puerto
 */
function eliminarPuerto(id, nombre) {
    Swal.fire({
        title: '¿Desactivar puerto?',
        text: `Se desactivará el puerto "${nombre}". Podrá reactivarlo editándolo.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, desactivar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../controller/gestionarPuertosController.php',
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
                            title: '¡Desactivado!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        
                        cargarPuertos();
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
