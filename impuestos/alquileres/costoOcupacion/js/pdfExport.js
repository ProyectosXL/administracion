/**
 * pdfExport.js
 * Función genérica para exportar cualquier contenedor como PDF multi-página.
 * Requiere html2canvas >= 1.4.1 y jsPDF >= 2.5.1 (UMD).
 */

/**
 * Exporta el contenido visual de un contenedor a un archivo PDF.
 *
 * @param {string} containerId  ID del elemento DOM a capturar.
 * @param {string} titulo       Título que se mostrará en la cabecera del PDF.
 * @param {string} filename     Nombre base del archivo (sin extensión ni fecha).
 */
async function exportarPDF(containerId, titulo, filename) {

    const contenedor = document.getElementById(containerId);
    if (!contenedor) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: `No se encontró el contenedor #${containerId}`,
            confirmButtonColor: '#e74c3c',
            confirmButtonText: 'Cerrar'
        });
        return;
    }

    // Alerta de carga
    Swal.fire({
        title: 'Generando PDF…',
        text: 'Por favor espere mientras se procesa el documento.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        // ── 1. Capturar con html2canvas usando onclone ──────────────────────
        // Todos los fixes (overflow, sticky, dimensiones) se aplican sobre el
        // documento CLONADO que usa html2canvas internamente. El DOM real
        // no se toca en ningún momento, evitando problemas de restauración y
        // asegurando que las reglas CSS de clase también queden anuladas.

        // Tamaño real del contenido (sin restricciones) medido con un clone inline
        // antes de llamar a html2canvas para poder pasarle width/windowWidth correctos.
        let naturalW, naturalH;
        {
            const clone = contenedor.cloneNode(true);
            clone.style.cssText = [
                'position:absolute', 'top:0', 'left:0', 'visibility:hidden',
                'pointer-events:none', 'overflow:visible!important',
                'width:max-content!important', 'max-width:none!important',
                'height:auto!important', 'max-height:none!important'
            ].join(';');

            // Liberar overflow en todos los descendientes del clone
            clone.querySelectorAll('*').forEach(el => {
                el.style.setProperty('overflow',   'visible', 'important');
                el.style.setProperty('overflow-x', 'visible', 'important');
                el.style.setProperty('overflow-y', 'visible', 'important');
                el.style.setProperty('max-width',  'none',    'important');
                el.style.setProperty('max-height', 'none',    'important');
                el.style.setProperty('height',     'auto',    'important');
                if (getComputedStyle(el).position === 'sticky') {
                    el.style.setProperty('position', 'relative', 'important');
                }
            });

            document.body.appendChild(clone);
            naturalW = clone.scrollWidth;
            naturalH = clone.scrollHeight;
            document.body.removeChild(clone);
        }

        // ── Medir posiciones de filas en el DOM antes de capturar ───────────
        // Se usa para calcular puntos de corte que no partan filas por la mitad.
        const HTML2CANVAS_SCALE = 1.8;
        const containerRect = contenedor.getBoundingClientRect();
        const rowBottomsPx = Array.from(contenedor.querySelectorAll('tr'))
            .map(tr => {
                const r = tr.getBoundingClientRect();
                // Posición de la parte inferior de la fila en píxeles del canvas
                return (r.bottom - containerRect.top) * HTML2CANVAS_SCALE;
            })
            .filter(y => y > 0);

        // Dar un tick para asegurarse de que el DOM está estable
        await new Promise(r => setTimeout(r, 60));

        const canvas = await html2canvas(contenedor, {
            scale: 1.8,
            backgroundColor: '#ffffff',
            useCORS: true,
            logging: false,
            scrollX: 0,
            scrollY: 0,
            // Capturar todo el contenido, no sólo el área visible
            width:        naturalW,
            height:       naturalH,
            // viewport simulado generoso para que ninguna regla CSS recorte
            windowWidth:  naturalW + 200,
            windowHeight: naturalH + 200,
            onclone: (clonedDoc, clonedEl) => {
                // Función de liberación reutilizable
                function liberar(el) {
                    el.style.setProperty('overflow',   'visible', 'important');
                    el.style.setProperty('overflow-x', 'visible', 'important');
                    el.style.setProperty('overflow-y', 'visible', 'important');
                    el.style.setProperty('height',     'auto',    'important');
                    el.style.setProperty('max-height', 'none',    'important');
                    el.style.setProperty('width',      'auto',    'important');
                    el.style.setProperty('max-width',  'none',    'important');
                    try {
                        if (getComputedStyle(el).position === 'sticky') {
                            el.style.setProperty('position', 'relative', 'important');
                            el.style.removeProperty('top');
                            el.style.removeProperty('left');
                        }
                    } catch (_) {}
                    if (el.scrollTop  !== undefined) el.scrollTop  = 0;
                    if (el.scrollLeft !== undefined) el.scrollLeft = 0;
                }

                // 1. Liberar todos los ancestros del contenedor hasta <body>
                //    para que ningún padre recorte el contenido capturado
                let ancestor = clonedEl.parentElement;
                while (ancestor && ancestor !== clonedDoc.body) {
                    liberar(ancestor);
                    ancestor = ancestor.parentElement;
                }

                // 2. Liberar el contenedor raíz
                liberar(clonedEl);
                clonedEl.style.setProperty('width', 'max-content', 'important');

                // 3. Liberar todos los descendientes
                clonedEl.querySelectorAll('*').forEach(el => liberar(el));
            }
        });

        const imgWidth  = canvas.width;
        const imgHeight = canvas.height;

        // ── 4. Escala legible (equivalente a 96 dpi en pantalla) ─────────────
        const SCREEN_SCALE = 1.8;
        const SCREEN_DPI   = 96;
        const readableScale = 25.4 / (SCREEN_DPI * SCREEN_SCALE); // mm por px de canvas

        const HEADER_HEIGHT = 18;   // mm
        const MARGIN        = 10;   // mm

        const contentWidthMm = imgWidth * readableScale;
        const MIN_PAGE_W     = 297;  // A4 landscape mínimo
        const pageW = Math.max(MIN_PAGE_W, contentWidthMm + MARGIN * 2);
        const pageH = 210;           // alto A4 landscape fijo

        const availableW = pageW - MARGIN * 2;
        const availableH = pageH - HEADER_HEIGHT - MARGIN;

        // Escala final px-canvas → mm
        const scale = availableW / imgWidth;
        // Máximo de píxeles de canvas que caben en una página
        const maxPxPerPage = availableH / scale;

        // ── 5. Calcular puntos de corte respetando bordes de filas ───────────
        //  Estrategia: para cada página, avanzamos hasta maxPxPerPage desde el
        //  inicio. Si ese punto cae dentro de una fila, retrocedemos al fondo
        //  de la fila anterior para no partir el contenido.
        function buildPageStarts(totalPx, maxPx, rowBottoms) {
            const starts = [0];
            let cur = 0;
            while (cur < totalPx) {
                let next = cur + maxPx;
                if (next >= totalPx) break; // última página, no hace falta corte

                if (rowBottoms.length > 0) {
                    // Buscar el último fondo de fila que esté ≤ next
                    let bestCut = -1;
                    for (let i = rowBottoms.length - 1; i >= 0; i--) {
                        if (rowBottoms[i] > cur && rowBottoms[i] <= next) {
                            bestCut = rowBottoms[i];
                            break;
                        }
                    }
                    // Si encontramos un borde limpio, usarlo; si no, cortar donde toca
                    if (bestCut > cur) next = bestCut;
                }

                starts.push(Math.round(next));
                cur = next;
            }
            return starts;
        }

        const pageStarts = buildPageStarts(imgHeight, maxPxPerPage, rowBottomsPx);
        const totalPages  = pageStarts.length;

        // ── 6. Crear instancia jsPDF ──────────────────────────────────────────
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF({ orientation: 'landscape', unit: 'mm', format: [pageW, pageH] });

        const ahora     = new Date();
        const fechaStr  = ahora.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });
        const horaStr   = ahora.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
        const fechaHora = `Generado el ${fechaStr} a las ${horaStr}`;

        // ── 7. Dibujar páginas ────────────────────────────────────────────────
        for (let page = 0; page < totalPages; page++) {

            if (page > 0) pdf.addPage([pageW, pageH], 'landscape');

            // Cabecera
            pdf.setFillColor(44, 62, 80);
            pdf.rect(0, 0, pageW, HEADER_HEIGHT, 'F');

            pdf.setFont('helvetica', 'bold');
            pdf.setFontSize(11);
            pdf.setTextColor(255, 255, 255);
            pdf.text(titulo, MARGIN, HEADER_HEIGHT / 2 + 2);

            pdf.setFont('helvetica', 'normal');
            pdf.setFontSize(8);
            pdf.text(fechaHora, pageW - MARGIN, HEADER_HEIGHT / 2 + 2, { align: 'right' });

            if (totalPages > 1) {
                pdf.setFontSize(7);
                pdf.text(
                    `Página ${page + 1} de ${totalPages}`,
                    pageW / 2, HEADER_HEIGHT / 2 + 2,
                    { align: 'center' }
                );
            }

            // Slice del canvas para esta página
            const pxStart  = pageStarts[page];
            const pxEnd    = page + 1 < totalPages ? pageStarts[page + 1] : imgHeight;
            const pxHeight = Math.round(pxEnd - pxStart);

            const sliceCanvas = document.createElement('canvas');
            sliceCanvas.width  = imgWidth;
            sliceCanvas.height = pxHeight;
            const ctx = sliceCanvas.getContext('2d');
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, imgWidth, pxHeight);
            ctx.drawImage(
                canvas,
                0, pxStart, imgWidth, pxHeight,
                0, 0,       imgWidth, pxHeight
            );

            pdf.addImage(
                sliceCanvas.toDataURL('image/jpeg', 0.93),
                'JPEG',
                MARGIN,
                HEADER_HEIGHT,
                availableW,
                pxHeight * scale
            );
        }

        // ── 8. Guardar ───────────────────────────────────────────────────────
        const today   = ahora.toISOString().split('T')[0];
        const pdfName = `${filename}_${today}.pdf`;
        pdf.save(pdfName);

    } catch (err) {
        console.error('Error al generar PDF:', err);
        Swal.fire({
            icon: 'error',
            title: 'Error al generar PDF',
            text: err.message || 'Ocurrió un error inesperado.',
            confirmButtonColor: '#e74c3c',
            confirmButtonText: 'Cerrar'
        });
        return;
    }

    Swal.close();
}

// ── Vincular botones cuando el DOM esté listo ────────────────────────────────
$(document).ready(function () {

    // Tab 1: Reporte por Sucursal
    $('#btnPDFAnalisis').on('click', function () {
        exportarPDF('tableSection', 'Reporte por Sucursal – Costo de Ocupación', 'reporte_sucursal');
    });

    // Tab 2: Reporte a Fecha
    $('#btnPDFReporteFecha').on('click', function () {
        exportarPDF('tableSectionReporte', 'Reporte a Fecha – Costo de Ocupación', 'reporte_fecha');
    });

    // Tab 3: Ranking
    $('#btnPDFRanking').on('click', function () {
        exportarPDF('rankingChartSection', 'Ranking – % Costo de Ocupación', 'ranking_costo_ocupacion');
    });

    // Tab 4: Costo por M²
    $('#btnPDFCostoM2').on('click', function () {
        exportarPDF('cardTablaCostoM2', 'Costo de Ocupación por Metro Cuadrado', 'costo_m2');
    });

    // Tab 5: Comparar Sucursales
    $('#btnPDFComparar').on('click', function () {
        exportarPDF('tableSectionComparar', 'Comparación de Sucursales – Costo de Ocupación', 'comparar_sucursales');
    });
});
