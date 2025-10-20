/**
 * Sincronización entre sidebar y pestañas del panel de control
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Variables globales para los elementos
    const sidebarLinks = document.querySelectorAll('#sidebarMenu a[data-bs-toggle="tab"]');
    const panelTabs = document.querySelectorAll('#cajaTabs button[data-bs-toggle="tab"]');
    
    // Función para limpiar todos los estados activos
    function limpiarEstadosActivos() {
        // Limpiar sidebar
        sidebarLinks.forEach(link => {
            link.classList.remove('active');
        });
        
        // Limpiar panel
        panelTabs.forEach(tab => {
            tab.classList.remove('active');
        });
    }
    
    // Función para activar vista específica
    function activarVista(targetId) {
        console.log('Activando vista:', targetId);
        
        // Limpiar todos los estados activos primero
        limpiarEstadosActivos();
        
        // Activar en sidebar
        sidebarLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href === '#' + targetId) {
                link.classList.add('active');
            }
        });
        
        // Activar en panel
        panelTabs.forEach(tab => {
            const target = tab.getAttribute('data-bs-target');
            if (target === '#' + targetId) {
                tab.classList.add('active');
            }
        });
    }
    
    // Variables para prevenir bucles infinitos
    let sincronizandoDesdePanel = false;
    let sincronizandoDesdeSidebar = false;
    
    // Escuchar eventos de las pestañas del panel
    panelTabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            if (sincronizandoDesdeSidebar) return; // Evitar bucle
            
            const targetId = e.target.getAttribute('data-bs-target').substring(1);
            console.log('Panel tab shown:', targetId);
            
            sincronizandoDesdePanel = true;
            
            // Solo actualizar sidebar
            sidebarLinks.forEach(link => {
                const href = link.getAttribute('href');
                if (href === '#' + targetId) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
            
            setTimeout(() => {
                sincronizandoDesdePanel = false;
            }, 50);
        });
    });
    
    // Escuchar eventos de los enlaces del sidebar
    sidebarLinks.forEach(link => {
        link.addEventListener('shown.bs.tab', function(e) {
            if (sincronizandoDesdePanel) return; // Evitar bucle
            
            const targetId = e.target.getAttribute('href').substring(1);
            console.log('Sidebar tab shown:', targetId);
            
            sincronizandoDesdeSidebar = true;
            
            // Solo actualizar panel
            panelTabs.forEach(tab => {
                const target = tab.getAttribute('data-bs-target');
                if (target === '#' + targetId) {
                    tab.classList.add('active');
                } else {
                    tab.classList.remove('active');
                }
            });
            
            setTimeout(() => {
                sincronizandoDesdeSidebar = false;
            }, 50);
        });
    });
    
    // Inicializar vista por defecto (Ingresos)
    setTimeout(() => {
        activarVista('ingresos');
        
        // Asegurar que la pestaña de ingresos esté visible
        const ingresosPane = document.querySelector('#ingresos');
        if (ingresosPane) {
            ingresosPane.classList.add('show', 'active');
        }
        
        // Cargar datos iniciales
        if (typeof cargarIngresos === 'function') {
            cargarIngresos();
        }
        
        if (typeof actualizarResumen === 'function') {
            actualizarResumen();
        }
    }, 100);
    
    // Manejar actualización de datos
    const btnActualizar = document.getElementById('btnActualizarDatos');
    if (btnActualizar) {
        btnActualizar.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Actualizar según la vista activa
            const vistaActiva = document.querySelector('.tab-pane.active');
            if (vistaActiva) {
                const vistaId = vistaActiva.id;
                
                switch(vistaId) {
                    case 'ingresos':
                        if (typeof cargarIngresos === 'function') {
                            cargarIngresos();
                        }
                        break;
                    case 'egresos':
                        if (typeof cargarEgresos === 'function') {
                            cargarEgresos();
                        }
                        break;
                    case 'reporte':
                        if (typeof cargarReporte === 'function') {
                            cargarReporte();
                        }
                        break;
                }
            }
            
            // Siempre actualizar resumen
            if (typeof actualizarResumen === 'function') {
                actualizarResumen();
            }
            
            // Mostrar feedback
            console.log('[SINCRONIZAR] Verificando mostrarAlerta:', typeof mostrarAlerta);
            console.log('[SINCRONIZAR] window.mostrarAlerta:', typeof window.mostrarAlerta);
            
            if (typeof mostrarAlerta === 'function') {
                console.log('[SINCRONIZAR] Llamando a mostrarAlerta');
                mostrarAlerta('Éxito', 'Datos actualizados correctamente');
            } else {
                console.log('[SINCRONIZAR] mostrarAlerta NO existe, usando fallback');
                // Crear modal temporal si no existe la función
                const modal = document.createElement('div');
                modal.className = 'alert alert-success alert-dismissible fade show position-fixed';
                modal.style.cssText = 'top: 20px; right: 20px; z-index: 1055; max-width: 300px; background-color: #d4edda; border-color: #c3e6cb; color: #155724;';
                modal.innerHTML = `
                    <i class="bi bi-check-circle"></i> Datos actualizados correctamente
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.body.appendChild(modal);
                
                // Auto-cerrar después de 3 segundos
                setTimeout(() => {
                    if (modal.parentNode) {
                        modal.remove();
                    }
                }, 3000);
            }
        });
    }
    
    // Debug: Mostrar estado actual cada 2 segundos (remover en producción)
    if (window.location.hostname === 'localhost') {
        setInterval(() => {
            const sidebarActivo = document.querySelector('#sidebarMenu a.active');
            const panelActivo = document.querySelector('#cajaTabs button.active');
            const vistaActiva = document.querySelector('.tab-pane.active');
            
            console.log('Debug - Estados:', {
                sidebar: sidebarActivo ? sidebarActivo.getAttribute('href') : 'ninguno',
                panel: panelActivo ? panelActivo.getAttribute('data-bs-target') : 'ninguno',
                vista: vistaActiva ? vistaActiva.id : 'ninguna'
            });
        }, 2000);
    }
});