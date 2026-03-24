/**
 * Seleccionar Sucursal - Script principal
 * Portal de Cobranzas XL
 */

// Variable global con el código del cliente
const COD_CLIENT = window.COD_CLIENT_ENTRADA || '';

/**
 * Genera las iniciales del nombre de la sucursal
 * @param {string} nombre - Nombre de la sucursal
 * @returns {string} Iniciales en mayúsculas
 */
function getIniciales(nombre) {
    if (!nombre) return '—';
    const palabras = nombre.trim().split(/\s+/).filter(Boolean);
    if (palabras.length === 1) {
        return palabras[0].substring(0, 2).toUpperCase();
    }
    return (palabras[0][0] + palabras[palabras.length - 1][0]).toUpperCase();
}

/**
 * Renderiza un mensaje de error
 * @param {string} msg - Mensaje de error a mostrar
 */
function renderError(msg) {
    const contenido = document.getElementById('contenido');
    if (!contenido) return;
    
    contenido.innerHTML = `
        <div class="error-state">
            <i class="fa-solid fa-circle-exclamation" style="margin-top:1px; font-size:13px;"></i>
            <span>${msg}</span>
        </div>`;
}

/**
 * Renderiza la lista de sucursales disponibles
 * @param {Array} sucursales - Array de objetos con datos de sucursales
 * @param {string} nombreGrupo - Nombre del grupo (opcional)
 * @param {boolean} esGrupo - Indica si es acceso por grupo
 */
function renderSucursales(sucursales, nombreGrupo, esGrupo) {
    const contenido = document.getElementById('contenido');
    const titulo = document.getElementById('titulo-card');
    const subtitulo = document.getElementById('subtitulo-card');

    if (!contenido || !titulo || !subtitulo) return;

    // Actualizar subtítulo si es parte de un grupo
    if (esGrupo && nombreGrupo) {
        subtitulo.innerHTML = `Tu cuenta pertenece al grupo <strong>${nombreGrupo}</strong>. Elegí con qué local querés trabajar.`;
    }

    // Validar existencia de sucursales
    if (!sucursales || sucursales.length === 0) {
        renderError('No se encontraron sucursales habilitadas para tu cuenta.');
        return;
    }

    // Si hay una sola sucursal, redirigir automáticamente
    if (sucursales.length === 1) {
        redirectToSucursal(sucursales[0], titulo, subtitulo, contenido);
        return;
    }

    // Renderizar lista de múltiples sucursales
    renderListaSucursales(sucursales, contenido);
}

/**
 * Redirige automáticamente a una sucursal única
 * @param {Object} sucursal - Datos de la sucursal
 * @param {HTMLElement} titulo - Elemento del título
 * @param {HTMLElement} subtitulo - Elemento del subtítulo
 * @param {HTMLElement} contenido - Elemento del contenido
 */
function redirectToSucursal(sucursal, titulo, subtitulo, contenido) {
    titulo.textContent = 'Accediendo…';
    subtitulo.textContent = 'Redireccionando a tu sucursal.';
    
    contenido.innerHTML = `
        <div class="redirect-notice">
            <div class="spinner-ring" style="width:16px;height:16px;border-width:1.5px;"></div>
            <span>Ingresando a <strong>${escapeHtml(sucursal.desc_sucursal)}</strong>…</span>
        </div>`;
    
    setTimeout(() => {
        window.location.href = `portal_cliente.php?cliente=${encodeURIComponent(sucursal.cod_client)}`;
    }, 700);
}

/**
 * Renderiza la lista completa de sucursales
 * @param {Array} sucursales - Array de objetos con datos de sucursales
 * @param {HTMLElement} contenido - Elemento del contenido
 */
function renderListaSucursales(sucursales, contenido) {
    let html = `
        <p class="section-label">
            Locales disponibles 
            <span class="count-badge">${sucursales.length}</span>
        </p>
        <div class="sucursal-list">`;

    sucursales.forEach(suc => {
        const iniciales = getIniciales(suc.desc_sucursal);
        const nombreEscaped = escapeHtml(suc.desc_sucursal);
        const codClientEscaped = escapeHtml(suc.cod_client);
        
        html += `
            <a href="portal_cliente.php?cliente=${encodeURIComponent(suc.cod_client)}"
               class="sucursal-item"
               data-sucursal="${codClientEscaped}">
                <div class="sucursal-item-left">
                    <div class="sucursal-avatar">${iniciales}</div>
                    <div>
                        <div class="sucursal-info-name">${nombreEscaped}</div>
                        <div class="sucursal-info-code">${codClientEscaped}</div>
                    </div>
                </div>
                <i class="fa-solid fa-chevron-right sucursal-arrow"></i>
            </a>`;
    });

    html += '</div>';
    contenido.innerHTML = html;

    // Añadir feedback al hacer clic
    addClickFeedback();
}

/**
 * Añade feedback visual al hacer clic en una sucursal
 */
function addClickFeedback() {
    const items = document.querySelectorAll('.sucursal-item');
    items.forEach(item => {
        item.addEventListener('click', function(e) {
            this.classList.add('loading');
        });
    });
}

/**
 * Escapa HTML para prevenir XSS
 * @param {string} text - Texto a escapar
 * @returns {string} Texto escapado
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

/**
 * Carga inicial - Obtiene las sucursales del servidor
 */
function cargarSucursales() {
    if (!COD_CLIENT) {
        renderError('Código de cliente no especificado.');
        return;
    }

    fetch(`api/sucursales_grupo_controller.php?cod_client=${encodeURIComponent(COD_CLIENT)}`)
        .then(res => {
            if (!res.ok) {
                throw new Error(`Error HTTP: ${res.status}`);
            }
            return res.json();
        })
        .then(data => {
            if (!data.success) {
                renderError(data.message || 'Error al cargar sucursales.');
                return;
            }
            renderSucursales(data.data, data.nombre_grupo, data.es_grupo);
        })
        .catch(err => {
            console.error('Error al cargar sucursales:', err);
            renderError('Error de conexión. Por favor recargá la página.');
        });
}

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', cargarSucursales);
} else {
    cargarSucursales();
}
