/**
 * Sincronización entre sidebar y pestañas del panel de control
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Variables globales para los elementos
    const sidebarLinks = document.querySelectorAll('#sidebarMenu a[data-bs-toggle="tab"]');
    const panelTabs = document.querySelectorAll('#mainTabs button[data-bs-toggle="tab"]');
    
    /**
     * Limpia todos los estados activos
     */
    function limpiarEstadosActivos() {
        sidebarLinks.forEach(link => link.classList.remove('active'));
        panelTabs.forEach(tab => tab.classList.remove('active'));
    }
    
    /**
     * Activa vista específica
     */
    function activarVista(targetId) {
        console.log('Activando vista:', targetId);
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
    
    /**
     * Escuchar eventos de las pestañas del panel
     */
    panelTabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            if (sincronizandoDesdeSidebar) return;
            
            const targetId = e.target.getAttribute('data-bs-target').substring(1);
            console.log('Panel tab shown:', targetId);
            
            sincronizandoDesdePanel = true;
            
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
    
    /**
     * Escuchar eventos de los enlaces del sidebar
     */
    sidebarLinks.forEach(link => {
        link.addEventListener('shown.bs.tab', function(e) {
            if (sincronizandoDesdePanel) return;
            
            const targetId = e.target.getAttribute('href').substring(1);
            console.log('Sidebar tab shown:', targetId);
            
            sincronizandoDesdeSidebar = true;
            
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
    
    /**
     * Inicializar vista por defecto (Nueva Solicitud)
     */
    setTimeout(() => {
        activarVista('solicitud');
        
        const solicitudPane = document.querySelector('#solicitud');
        if (solicitudPane) {
            solicitudPane.classList.add('show', 'active');
        }
        
        // Cargar directores al inicio
        if (typeof cargarDirectores === 'function') {
            cargarDirectores();
        }
    }, 100);
    
    /**
     * Cargar directores también en el filtro del listado
     */
    async function cargarDirectoresFiltro() {
        try {
            const response = await fetch('controller/solicitud_controller.php?accion=obtener_directores');
            const result = await response.json();
            
            if (result.success) {
                const selectFiltro = document.getElementById('filtroDirector');
                if (selectFiltro) {
                    // Mantener opción "Todos"
                    const opcionTodos = selectFiltro.querySelector('option[value=""]');
                    selectFiltro.innerHTML = '';
                    if (opcionTodos) {
                        selectFiltro.appendChild(opcionTodos);
                    } else {
                        const option = document.createElement('option');
                        option.value = '';
                        option.textContent = 'Todos';
                        selectFiltro.appendChild(option);
                    }
                    
                    result.data.forEach(director => {
                        const option = document.createElement('option');
                        option.value = director;
                        option.textContent = director;
                        selectFiltro.appendChild(option);
                    });
                }
            }
        } catch (error) {
            console.error('Error al cargar directores para filtro:', error);
        }
    }
    
    // Cargar directores en filtro cuando se muestra pestaña de listado
    document.getElementById('listado-tab')?.addEventListener('shown.bs.tab', function() {
        cargarDirectoresFiltro();
    });
});