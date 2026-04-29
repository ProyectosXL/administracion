/**
 * costoPersonal.js
 * Módulo global para el dashboard Costo de Personal.
 * Gestiona el toggle de categorías (FIJO/VARIABLE/DIFERIDO/CONTINGENTE),
 * dispara el evento `cp:categorias-changed` cuando cambia la selección,
 * y expone utilidades de formato compartidas por todos los tabs.
 */

const CostoPersonalGlobal = (() => {

    const STORAGE_KEY  = 'cp:categorias_activas';
    const TODAS_CATS   = ['FIJO', 'VARIABLE', 'DIFERIDO', 'CONTINGENTE'];
    const MIN_ACTIVAS  = 1;

    /* ── Estado ──────────────────────────────────────────────── */
    let _activas = [...TODAS_CATS];   // categorías actualmente activas

    /* ── Inicialización ──────────────────────────────────────── */
    function _cargarDesdeStorage() {
        try {
            const raw = sessionStorage.getItem(STORAGE_KEY);
            if (raw) {
                const arr = JSON.parse(raw);
                if (Array.isArray(arr) && arr.length >= MIN_ACTIVAS) {
                    _activas = arr.filter(c => TODAS_CATS.includes(c));
                    if (_activas.length < MIN_ACTIVAS) _activas = [...TODAS_CATS];
                }
            }
        } catch (_) {
            _activas = [...TODAS_CATS];
        }
    }

    function _guardarEnStorage() {
        try {
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(_activas));
        } catch (_) {}
    }

    /* ── Render visual de las píldoras ──────────────────────── */
    function _renderCheckboxes() {
        $('#cpCategoriasToggle .cp-cat-pill').each(function () {
            const $pill = $(this);
            const val   = $pill.find('input[type=checkbox]').val();
            const activ = _activas.includes(val);

            $pill.find('input[type=checkbox]').prop('checked', activ);
            $pill.toggleClass('activa',   activ);
            $pill.toggleClass('inactiva', !activ);

            // Deshabilitar si es la única activa (no puede deseleccionarse)
            const solaActiva = activ && _activas.length === MIN_ACTIVAS;
            $pill.find('input[type=checkbox]').prop('disabled', solaActiva);
            $pill.css('cursor', solaActiva ? 'not-allowed' : 'pointer');
        });
    }

    /* ── Binding de eventos ──────────────────────────────────── */
    function _bindEvents() {
        // El <input> está dentro del <label>, así que el browser ya lo togglea
        // al hacer clic en cualquier parte de la píldora — no se necesita handler
        // adicional de click (agregaría un segundo toggle cancelando el primero).
        $(document).on('change', '#cpCategoriasToggle .cat-toggle', function () {
            const val     = $(this).val();
            const checked = $(this).prop('checked');

            if (checked) {
                if (!_activas.includes(val)) _activas.push(val);
            } else {
                if (_activas.length <= MIN_ACTIVAS) {
                    // Revertir: no se puede quedar sin categorías
                    $(this).prop('checked', true);
                    return;
                }
                _activas = _activas.filter(c => c !== val);
            }

            _guardarEnStorage();
            _renderCheckboxes();
            _dispatchChange();
        });
    }

    /* ── Despachar evento global ─────────────────────────────── */
    function _dispatchChange() {
        $(document).trigger('cp:categorias-changed', [{ activas: [..._activas] }]);
    }

    /* ── API pública ─────────────────────────────────────────── */

    /**
     * Devuelve las categorías actualmente activas.
     * @returns {string[]}
     */
    function getActivas() {
        return [..._activas];
    }

    /**
     * Calcula el costo total combinando sólo las categorías activas.
     * @param {Object} costos_por_categoria  { FIJO: n, VARIABLE: n, DIFERIDO: n, CONTINGENTE: n }
     * @returns {number}
     */
    function calcularCostoTotal(costos_por_categoria) {
        if (!costos_por_categoria) return 0;
        return _activas.reduce((sum, cat) => sum + (costos_por_categoria[cat] || 0), 0);
    }

    /**
     * Calcula % costo de personal sobre venta neta.
     * @param {number} costo
     * @param {number} ventaNeta
     * @returns {number|null}
     */
    function calcularPorcentaje(costo, ventaNeta) {
        if (!ventaNeta || ventaNeta <= 0) return null;
        return (costo / ventaNeta) * 100;
    }

    /* ── Helpers de formato (compartidos por todos los módulos) ─ */

    function fmtPesos(v) {
        if (v === null || v === undefined) return '—';
        return new Intl.NumberFormat('es-AR', {
            style:                 'currency',
            currency:              'ARS',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(v);
    }

    function fmtPct(v, decimals = 2) {
        if (v === null || v === undefined) return '—';
        return v.toFixed(decimals) + '%';
    }

    function fmtDateHuman(dateStr) {
        if (!dateStr) return '';
        const [y, m, d] = dateStr.split('-');
        const meses = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
        return `${parseInt(d)} ${meses[parseInt(m) - 1]} ${y}`;
    }

    function fmtDate(d) {
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        return `${d.getFullYear()}-${mm}-${dd}`;
    }

    function calcRange(months) {
        const hoy   = new Date();
        const hasta = new Date(hoy.getFullYear(), hoy.getMonth(), 0);
        const desde = new Date(hasta.getFullYear(), hasta.getMonth() - (months - 1), 1);
        return { desde: fmtDate(desde), hasta: fmtDate(hasta) };
    }

    function esc(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    /* ── Clasificar semáforo con umbrales dinámicos de CP_CONFIG ─ */
    function semClass(pct) {
        if (pct === null || pct === undefined) return 'sin_datos';
        const verde = (typeof CP_CONFIG !== 'undefined') ? CP_CONFIG.umbralVerde : 10;
        const rojo  = (typeof CP_CONFIG !== 'undefined') ? CP_CONFIG.umbralRojo  : 15;
        if (pct <= verde) return 'verde';
        if (pct <= rojo)  return 'amarillo';
        return 'rojo';
    }

    function semLabel(pct) {
        const cl = semClass(pct);
        return cl === 'verde' ? 'Eficiente' : cl === 'amarillo' ? 'En rango' : cl === 'rojo' ? 'Requiere acción' : 'Sin datos';
    }

    function semColor(pct) {
        const cl = semClass(pct);
        return cl === 'verde' ? '#27ae60' : cl === 'amarillo' ? '#e67e22' : '#e74c3c';
    }

    /* ── Banner: meses sin datos ────────────────────────────────── */

    const _NOMBRES_MES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                          'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

    const _VERBO = {
        SIN_COSTOS: { sing: 'no tiene costos cargados',          plur: 'no tienen costos cargados' },
        SIN_VENTAS: { sing: 'no tiene ventas cargadas',           plur: 'no tienen ventas cargadas' },
        AMBOS:      { sing: 'no tiene costos ni ventas cargados', plur: 'no tienen costos ni ventas cargados' },
    };

    function _parseFecha(fecha) {
        const [y, m] = fecha.split('-');
        return { nombre: _NOMBRES_MES[parseInt(m) - 1], anio: parseInt(y) };
    }

    function _formatGrupo(fechas, estado) {
        const parsed = fechas.map(_parseFecha);
        const n      = parsed.length;
        const v      = (_VERBO[estado] || _VERBO.AMBOS);
        const verbo  = n === 1 ? v.sing : v.plur;

        if (n === 1) return `${parsed[0].nombre} ${parsed[0].anio} ${verbo}`;

        const anyos = [...new Set(parsed.map(p => p.anio))];

        if (n === 2) {
            // "Febrero 2025 y Marzo 2025 …"
            return `${parsed[0].nombre} ${parsed[0].anio} y ${parsed[1].nombre} ${parsed[1].anio} ${verbo}`;
        }

        // 3 o más
        if (anyos.length === 1) {
            // "Febrero, Marzo y Abril 2025 …"
            const head = parsed.slice(0, -1).map(p => p.nombre).join(', ');
            return `${head} y ${parsed[n - 1].nombre} ${anyos[0]} ${verbo}`;
        }

        // Años distintos: cada mes lleva su año
        const head = parsed.slice(0, -1).map(p => `${p.nombre} ${p.anio}`).join(', ');
        return `${head} y ${parsed[n - 1].nombre} ${parsed[n - 1].anio} ${verbo}`;
    }

    function _buildBannerMsg(mesesSinDatos) {
        const grupos = {};
        for (const mes of mesesSinDatos) {
            if (!grupos[mes.estado]) grupos[mes.estado] = [];
            grupos[mes.estado].push(mes.mes);
        }
        const partes = Object.entries(grupos).map(([est, fechas]) => _formatGrupo(fechas, est));
        return partes.join('. ') + '.';
    }

    /**
     * Muestra u oculta el banner de "meses sin datos" en #cp-banner-meses-sin-datos.
     * @param {Array}  mesesSinDatos   Array de {fecha, estado} devuelto por el backend.
     * @param {number} periodoCompleto Total de meses en el período seleccionado.
     * @param {number} periodoConDatos Meses con datos completos que se usaron.
     */
    function renderBannerMesesSinDatos(mesesSinDatos, periodoCompleto, periodoConDatos) {
        const $banner = $('#cp-banner-meses-sin-datos');
        if (!$banner.length) return;

        if (!mesesSinDatos || mesesSinDatos.length === 0) {
            $banner.hide().empty();
            return;
        }

        const msg  = _buildBannerMsg(mesesSinDatos);
        const sub  = periodoConDatos < periodoCompleto
            ? `Se calculó con ${periodoConDatos} de ${periodoCompleto} meses disponibles. `
            : '';

        $banner.html(
            `<div class="cp-banner-warning">` +
                `<span class="cp-banner-icon">&#9888;</span>` +
                `<div class="cp-banner-content">` +
                    `<strong>Período incompleto</strong>` +
                    `<small>${sub}${esc(msg)}</small>` +
                `</div>` +
            `</div>`
        ).show();
    }

    /* ── Auto-init cuando el DOM esté listo ────────────────────── */
    function init() {
        _cargarDesdeStorage();
        _renderCheckboxes();
        _bindEvents();

        // Ocultar banner al cambiar de pestaña
        $('a[data-toggle="tab"]').on('shown.bs.tab', () => {
            $('#cp-banner-meses-sin-datos').hide().empty();
        });
    }

    $(document).ready(init);

    return {
        getActivas,
        calcularCostoTotal,
        calcularPorcentaje,
        renderBannerMesesSinDatos,
        fmtPesos,
        fmtPct,
        fmtDateHuman,
        fmtDate,
        calcRange,
        esc,
        semClass,
        semLabel,
        semColor,
    };

})();
