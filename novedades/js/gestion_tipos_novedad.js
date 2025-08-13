// /novedades/js/gestion_tipos_novedad.js

class GestionTiposApp {
    constructor() {
        this.tipos = [];
        this.tipoEditando = null;
        this.tipoAEliminar = null;
        this.init();
    }

    init() {
        console.log('🚀 Iniciando aplicación de gestión de tipos de novedad');
        this.cargarTipos();
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Evento para guardar cambios inline
        document.addEventListener('change', (e) => {
            if (e.target.type === 'checkbox' && e.target.classList.contains('permission-toggle')) {
                this.actualizarPermisoInline(e.target);
            }
        });

        // Eventos de modales
        document.getElementById('modalTipo').addEventListener('hidden.bs.modal', () => {
            this.limpiarFormulario();
        });
    }

    async cargarTipos() {
        try {
            this.mostrarLoading(true);
            
            const response = await fetch('controller/novedades_controller.php?action=get_tipos_novedad_gestion');
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'Error cargando tipos de novedad');
            }

            this.tipos = result.data;
            this.renderizarTabla();
            this.actualizarEstadisticas();

        } catch (error) {
            console.error('Error cargando tipos:', error);
            this.mostrarError('Error cargando tipos de novedad: ' + error.message);
        } finally {
            this.mostrarLoading(false);
        }
    }

    renderizarTabla() {
        const tbody = document.getElementById('tipos-tbody');
        tbody.innerHTML = '';

        if (this.tipos.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center py-4">
                        <div class="text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No hay tipos de novedad configurados</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        this.tipos.forEach(tipo => {
            const row = this.crearFilaTipo(tipo);
            tbody.appendChild(row);
        });
    }

    crearFilaTipo(tipo) {
        const row = document.createElement('tr');
        row.setAttribute('data-tipo-id', tipo.id);
        
        // Estado del tipo
        const estadoBadge = tipo.activo 
            ? '<span class="status-badge status-active">Activo</span>'
            : '<span class="status-badge status-inactive">Inactivo</span>';

        row.innerHTML = `
            <td class="fw-bold">${tipo.id}</td>
            <td>
                <code>${tipo.codigo}</code>
            </td>
            <td>
                <div class="fw-semibold">${tipo.descripcion}</div>
            </td>
            <td>${estadoBadge}</td>
            <td class="permission-cell">
                ${this.crearTogglePermiso(tipo.id, 'user_adm', tipo.user_adm)}
            </td>
            <td class="permission-cell">
                ${this.crearTogglePermiso(tipo.id, 'user_com', tipo.user_com)}
            </td>
            <td class="permission-cell">
                ${this.crearTogglePermiso(tipo.id, 'user_prod', tipo.user_prod)}
            </td>
            <td class="permission-cell">
                ${this.crearTogglePermiso(tipo.id, 'user_rrhh', tipo.user_rrhh)}
            </td>
            <td class="text-center">
                <small class="text-muted">${tipo.cierre || '-'}</small>
            </td>
            <td class="text-center">
                <small class="text-muted">${tipo.corte || '-'}</small>
            </td>
            <td>
                <div class="btn-group" role="group">
                    <button class="btn btn-outline-primary btn-action" onclick="app.editarTipo(${tipo.id})" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-outline-success btn-action" onclick="app.duplicarTipo(${tipo.id})" title="Duplicar">
                        <i class="fas fa-copy"></i>
                    </button>
                    <button class="btn btn-outline-danger btn-action" onclick="app.eliminarTipo(${tipo.id})" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;

        return row;
    }

    crearTogglePermiso(tipoId, campo, valor) {
        const checked = valor ? 'checked' : '';
        const colorClass = valor ? 'btn-success' : 'btn-outline-secondary';
        
        return `
            <div class="form-check form-switch d-flex justify-content-center">
                <input class="form-check-input permission-toggle" 
                       type="checkbox" 
                       ${checked}
                       data-tipo-id="${tipoId}" 
                       data-campo="${campo}"
                       title="${valor ? 'Activo' : 'Inactivo'}">
            </div>
        `;
    }

    async actualizarPermisoInline(toggle) {
        const tipoId = toggle.getAttribute('data-tipo-id');
        const campo = toggle.getAttribute('data-campo');
        const nuevoValor = toggle.checked;

        console.log('🔄 Actualizando permiso:', { tipoId, campo, nuevoValor });

        try {
            // Encontrar el tipo en los datos locales
            const tipo = this.tipos.find(t => t.id == tipoId);
            if (!tipo) {
                throw new Error('Tipo no encontrado');
            }

            // Preparar datos para actualización
            const datosActualizacion = {
                id: parseInt(tipoId),
                codigo: tipo.codigo,
                descripcion: tipo.descripcion,
                activo: Boolean(tipo.activo),
                user_adm: Boolean(tipo.user_adm),
                user_com: Boolean(tipo.user_com),
                user_prod: Boolean(tipo.user_prod),
                user_rrhh: Boolean(tipo.user_rrhh)
            };

            // Actualizar el campo específico
            datosActualizacion[campo] = nuevoValor;

            console.log('📤 Enviando datos:', datosActualizacion);

            const response = await fetch('controller/novedades_controller.php?action=actualizar_tipo_novedad&debug=1', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(datosActualizacion)
            });

            console.log('📥 Response status:', response.status);
            console.log('📥 Response headers:', Object.fromEntries(response.headers.entries()));

            // Verificar si la respuesta es OK
            if (!response.ok) {
                throw new Error(`HTTP Error: ${response.status} ${response.statusText}`);
            }

            const responseText = await response.text();
            console.log('📥 Response text:', responseText);

            if (!responseText.trim()) {
                throw new Error('Respuesta vacía del servidor');
            }

            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error('❌ Error parsing JSON:', parseError);
                throw new Error('Respuesta del servidor no es JSON válido: ' + responseText.substring(0, 100));
            }

            if (!result.success) {
                throw new Error(result.message || 'Error desconocido del servidor');
            }

            // Actualizar datos locales
            tipo[campo] = nuevoValor;

            // Mostrar feedback visual
            this.mostrarNotificacion('Permiso actualizado correctamente', 'success');
            
            // Actualizar estadísticas
            this.actualizarEstadisticas();

        } catch (error) {
            console.error('❌ Error actualizando permiso:', error);
            // Revertir el toggle en caso de error
            toggle.checked = !nuevoValor;
            this.mostrarError('Error actualizando permiso: ' + error.message);
        }
    }

    abrirModalNuevoTipo() {
        this.tipoEditando = null;
        document.getElementById('modalTipoTitulo').textContent = 'Nuevo Tipo de Novedad';
        this.limpiarFormulario();
        
        const modal = new bootstrap.Modal(document.getElementById('modalTipo'));
        modal.show();
    }

    editarTipo(id) {
        const tipo = this.tipos.find(t => t.id == id);
        if (!tipo) {
            this.mostrarError('Tipo no encontrado');
            return;
        }

        this.tipoEditando = tipo;
        document.getElementById('modalTipoTitulo').textContent = 'Editar Tipo de Novedad';
        
        // Llenar formulario
        document.getElementById('tipo-id').value = tipo.id;
        document.getElementById('tipo-codigo').value = tipo.codigo;
        document.getElementById('tipo-descripcion').value = tipo.descripcion;
        document.getElementById('tipo-activo').checked = tipo.activo;
        document.getElementById('tipo-user-adm').checked = tipo.user_adm;
        document.getElementById('tipo-user-com').checked = tipo.user_com;
        document.getElementById('tipo-user-prod').checked = tipo.user_prod;
        document.getElementById('tipo-user-rrhh').checked = tipo.user_rrhh;
        document.getElementById('tipo-cierre').value = tipo.cierre || '';
        document.getElementById('tipo-corte').value = tipo.corte || '';

        const modal = new bootstrap.Modal(document.getElementById('modalTipo'));
        modal.show();
    }

    duplicarTipo(id) {
        const tipo = this.tipos.find(t => t.id == id);
        if (!tipo) {
            this.mostrarError('Tipo no encontrado');
            return;
        }

        this.tipoEditando = null;
        document.getElementById('modalTipoTitulo').textContent = 'Duplicar Tipo de Novedad';
        
        // Llenar formulario con datos del tipo original (excepto ID y código)
        document.getElementById('tipo-id').value = '';
        document.getElementById('tipo-codigo').value = tipo.codigo + '_COPY';
        document.getElementById('tipo-descripcion').value = tipo.descripcion + ' (Copia)';
        document.getElementById('tipo-activo').checked = tipo.activo;
        document.getElementById('tipo-user-adm').checked = tipo.user_adm;
        document.getElementById('tipo-user-com').checked = tipo.user_com;
        document.getElementById('tipo-user-prod').checked = tipo.user_prod;
        document.getElementById('tipo-user-rrhh').checked = tipo.user_rrhh;
        document.getElementById('tipo-cierre').value = tipo.cierre || '';
        document.getElementById('tipo-corte').value = tipo.corte || '';

        const modal = new bootstrap.Modal(document.getElementById('modalTipo'));
        modal.show();
    }

    eliminarTipo(id) {
        const tipo = this.tipos.find(t => t.id == id);
        if (!tipo) {
            this.mostrarError('Tipo no encontrado');
            return;
        }

        this.tipoAEliminar = tipo;
        document.getElementById('eliminar-descripcion').textContent = tipo.descripcion;
        document.getElementById('eliminar-codigo').textContent = tipo.codigo;

        const modal = new bootstrap.Modal(document.getElementById('modalEliminar'));
        modal.show();
    }

    async guardarTipo() {
        try {
            const formData = new FormData(document.getElementById('formTipo'));
            const datos = {};

            // Convertir FormData a objeto
            for (let [key, value] of formData.entries()) {
                if (key === 'activo' || key.startsWith('user_')) {
                    datos[key] = true; // Si existe en FormData, está checkeado
                } else {
                    datos[key] = value; // Para select y text inputs
                }
            }

            // Establecer checkboxes no marcados como false
            ['activo', 'user_adm', 'user_com', 'user_prod', 'user_rrhh'].forEach(campo => {
                if (!datos.hasOwnProperty(campo)) {
                    datos[campo] = false;
                }
            });

            // Para cierre y corte, si están vacíos, convertir a null
            if (!datos.cierre || datos.cierre === '') {
                datos.cierre = null;
            }
            if (!datos.corte || datos.corte === '') {
                datos.corte = null;
            }

            // Validaciones
            if (!datos.codigo || !datos.descripcion) {
                this.mostrarError('Código y descripción son requeridos');
                return;
            }

            const esEdicion = this.tipoEditando !== null;
            const action = esEdicion ? 'actualizar_tipo_novedad' : 'crear_tipo_novedad';

            const response = await fetch(`controller/novedades_controller.php?action=${action}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(datos)
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'Error guardando tipo de novedad');
            }

            this.mostrarNotificacion(
                esEdicion ? 'Tipo actualizado correctamente' : 'Tipo creado correctamente',
                'success'
            );

            // Cerrar modal y recargar datos
            bootstrap.Modal.getInstance(document.getElementById('modalTipo')).hide();
            await this.cargarTipos();

        } catch (error) {
            console.error('Error guardando tipo:', error);
            this.mostrarError('Error guardando tipo: ' + error.message);
        }
    }

    async confirmarEliminacion() {
        if (!this.tipoAEliminar) {
            return;
        }

        try {
            const response = await fetch('controller/novedades_controller.php?action=eliminar_tipo_novedad', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: this.tipoAEliminar.id })
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'Error eliminando tipo de novedad');
            }

            this.mostrarNotificacion('Tipo eliminado correctamente', 'success');

            // Cerrar modal y recargar datos
            bootstrap.Modal.getInstance(document.getElementById('modalEliminar')).hide();
            await this.cargarTipos();

        } catch (error) {
            console.error('Error eliminando tipo:', error);
            this.mostrarError('Error eliminando tipo: ' + error.message);
        } finally {
            this.tipoAEliminar = null;
        }
    }

    limpiarFormulario() {
        document.getElementById('formTipo').reset();
        document.getElementById('tipo-id').value = '';
        // Por defecto, activar el tipo y darle permisos a RRHH
        document.getElementById('tipo-activo').checked = true;
        document.getElementById('tipo-user-rrhh').checked = true;
        // Limpiar selectores
        document.getElementById('tipo-cierre').value = '';
        document.getElementById('tipo-corte').value = '';
        this.tipoEditando = null;
    }

    actualizarEstadisticas() {
        const total = this.tipos.length;
        const activos = this.tipos.filter(t => t.activo).length;
        const conRRHH = this.tipos.filter(t => t.user_rrhh).length;
        const conAdmin = this.tipos.filter(t => t.user_adm).length;

        document.getElementById('total-tipos').textContent = total;
        document.getElementById('tipos-activos').textContent = activos;
        document.getElementById('tipos-rrhh').textContent = conRRHH;
        document.getElementById('tipos-admin').textContent = conAdmin;
    }

    mostrarLoading(mostrar) {
        const loading = document.getElementById('loading');
        const tabla = document.querySelector('.table-container');
        
        if (mostrar) {
            loading.style.display = 'block';
            tabla.style.display = 'none';
        } else {
            loading.style.display = 'none';
            tabla.style.display = 'block';
        }
    }

    mostrarNotificacion(mensaje, tipo = 'info') {
        // Crear toast notification
        const toast = document.createElement('div');
        toast.className = `alert alert-${tipo} alert-dismissible fade show position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        toast.innerHTML = `
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(toast);
        
        // Auto-remover después de 5 segundos
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 5000);
    }

    mostrarError(mensaje) {
        this.mostrarNotificacion(mensaje, 'danger');
    }

    formatearFecha(fecha) {
        if (!fecha) return 'N/A';
        try {
            return new Date(fecha).toLocaleDateString('es-ES');
        } catch (e) {
            return 'N/A';
        }
    }
}

// Funciones globales para eventos onclick
function abrirModalNuevoTipo() {
    app.abrirModalNuevoTipo();
}

function guardarTipo() {
    app.guardarTipo();
}

function confirmarEliminacion() {
    app.confirmarEliminacion();
}

// Inicializar aplicación
let app;
document.addEventListener('DOMContentLoaded', () => {
    app = new GestionTiposApp();
});
