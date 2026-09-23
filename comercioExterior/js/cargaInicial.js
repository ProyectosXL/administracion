/**
 * cargaInicial.js - Script para la carga inicial de despachos de importación
 * Maneja cálculos automáticos, validaciones y gestión de órdenes de compra
 */

/* ==========================================================================
   VARIABLES GLOBALES Y FLAGS

   LOS TRES FLAGS DE "FIJADA A MANO" YA NO SON FLAGS DE MEMORIA. Salen del
   maestro al abrir la pantalla:

       fechaEstPagoIsManual   <- FECHA_PAGO_CONF   (sql/10)
       fechaArriboIsManual    <- ETA_CONFIRMADA    (ya existía; sql/11 le suma
                                                    quién y cuándo)
       fechaDespachoIsManual  <- FECHA_DESP_CONF   (sql/11)

   Antes vivían solo acá y arrancaban en false en cada apertura. Para que el
   recálculo no pisara una corrección hecha a mano, cargarDatosDespacho() los
   encendía a ciegas: "si la fila trae FECHA_ARR, marcala como manual". Eso
   protegía, pero al precio de que NINGUNA fecha ya guardada se recalculara
   nunca más —ni al mover el ETD, ni al cambiar un parámetro—. La pantalla no
   distinguía "esto lo decidió alguien" de "esto vino así".
   ========================================================================== */
let fechaArriboIsManual = false;
let fechaDespachoIsManual = false;
let fechaEstPagoIsManual = false;

/* Quién fijó cada fecha y cuándo, para el tooltip de los badges. Vienen del
   maestro y no se calculan acá. */
let fechaEstPagoConfUsuario = null;
let fechaEstPagoConfFecha = null;
let fechaArrConfUsuario = null;
let fechaArrConfFecha = null;
let fechaDespConfUsuario = null;
let fechaDespConfFecha = null;

/* ETA en firme (ETA_CONFIRMADA del maestro).
   Reemplaza al checkbox "ETA Confirmada": ahora se enciende SOLA al editar la
   ETA a mano. El campo no se deprecia —lo leen el cronograma, la validación de
   coherencia de CronogramaFechas::esFechaReal() y las dos pestañas del
   cashflow—; lo que cambia es cómo se enciende, no qué significa.

   ES LA MISMA COSA QUE fechaArriboIsManual y se mantienen las dos variables a
   propósito: "fijada a mano" es lo que decide si se recalcula, y "ETA
   confirmada" es lo que se guarda y lo que leen las otras pantallas. Hoy
   coinciden porque el único modo de confirmar una ETA es editarla. */
let etaConfirmada = false;
let cargandoDatos = false; // Flag para evitar marcar como manual durante carga inicial

/* Estado de los pagos. Se declara acá arriba, junto al resto de las globales,
   y no al lado de sus helpers: cargarPagos() y renderizarTablaPagos() están
   definidas antes en el archivo y las leen, y con `let` eso sería una zona
   muerta temporal si alguna llegara a ejecutarse durante la evaluación del
   script en vez de después del ready. */

/** Último resumen de pagos que devolvió el servidor. */
let resumenPagos = { fobUsd: 0, totalPagado: 0, saldoPendiente: 0 };

/** Últimos pagos traídos, para que el modal de edición los pueda leer. */
let pagosCargados = [];

// ========== FUNCIONES DE VALIDACIÓN DE FECHAS HÁBILES ==========

/**
 * Convierte fecha DD/MM/YYYY a YYYY-MM-DD
 */
function convertirFechaAISO(fechaDDMMYYYY) {
    if (!fechaDDMMYYYY) return '';
    const partes = fechaDDMMYYYY.split('/');
    if (partes.length === 3) {
        return `${partes[2]}-${partes[1]}-${partes[0]}`;
    }
    return fechaDDMMYYYY;
}

/**
 * Convierte fecha YYYY-MM-DD a DD/MM/YYYY
 */
function convertirFechaADDMMYYYY(fechaISO) {
    if (!fechaISO) return '';
    const partes = fechaISO.split('-');
    if (partes.length === 3) {
        return `${partes[2]}/${partes[1]}/${partes[0]}`;
    }
    return fechaISO;
}

/**
 * Verifica si una fecha es día hábil (no fin de semana ni feriado)
 * @param {string} fecha - Fecha en formato DD/MM/YYYY o YYYY-MM-DD
 * @return {boolean}
 */
function esDiaHabil(fecha) {
    if (!fecha) return true;
    
    // Convertir a formato ISO si viene en DD/MM/YYYY
    let fechaISO = fecha.includes('/') ? convertirFechaAISO(fecha) : fecha;
    
    const date = new Date(fechaISO + 'T00:00:00');
    const diaSemana = date.getDay();  // 0=Domingo, 6=Sábado
    
    // Verificar fin de semana
    if (diaSemana === 0 || diaSemana === 6) {
        return false;
    }
    
    // Verificar feriado
    return !feriadosArgentinos.includes(fechaISO);
}

/**
 * Obtiene el motivo por el cual no es día hábil
 * @param {string} fecha - Fecha en formato DD/MM/YYYY o YYYY-MM-DD
 * @return {string}
 */
function obtenerMotivoNoHabil(fecha) {
    if (!fecha) return '';
    
    let fechaISO = fecha.includes('/') ? convertirFechaAISO(fecha) : fecha;
    const date = new Date(fechaISO + 'T00:00:00');
    const diaSemana = date.getDay();
    
    if (diaSemana === 0) return "Domingo";
    if (diaSemana === 6) return "Sábado";
    if (feriadosArgentinos.includes(fechaISO)) return "Feriado";
    
    return "Día no hábil";
}

/**
 * Obtiene el siguiente día hábil
 * @param {string} fecha - Fecha en formato DD/MM/YYYY o YYYY-MM-DD
 * @return {string} Siguiente día hábil en formato original
 */
function obtenerSiguienteDiaHabil(fecha) {
    if (!fecha) return '';
    
    const formatoOriginal = fecha.includes('/') ? 'DD/MM/YYYY' : 'YYYY-MM-DD';
    let fechaISO = fecha.includes('/') ? convertirFechaAISO(fecha) : fecha;
    
    let date = new Date(fechaISO + 'T00:00:00');
    let intentos = 0;
    const maxIntentos = 15;
    
    while (intentos < maxIntentos) {
        date.setDate(date.getDate() + 1);
        const fechaStr = formatearFechaISO(date);
        
        if (esDiaHabil(fechaStr)) {
            return formatoOriginal === 'DD/MM/YYYY' ? convertirFechaADDMMYYYY(fechaStr) : fechaStr;
        }
        
        intentos++;
    }
    
    return fecha;
}

/**
 * Formatea fecha como YYYY-MM-DD
 */
function formatearFechaISO(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

/**
 * Valida campo de fecha y muestra advertencia si no es día hábil
 * Cambia automáticamente a fecha sugerida y muestra alerta informativa
 * @param {jQuery} $campo - Campo jQuery a validar
 */
function validarCampoFechaHabil($campo) {
    const fecha = $campo.val();
    
    if (!fecha || cargandoDatos) return;
    
    // Verificar si el campo tiene override
    if ($campo.data('override-fecha-habil')) {
        return;
    }
    
    if (!esDiaHabil(fecha)) {
        const motivo = obtenerMotivoNoHabil(fecha);
        const fechaSugerida = obtenerSiguienteDiaHabil(fecha);
        
        // Cambiar automáticamente a la fecha sugerida
        $campo.val(fechaSugerida);
        
        // Sincronizar datepicker con la nueva fecha
        const $picker = $campo.data('daterangepicker');
        if ($picker && fechaSugerida) {
            const fechaMoment = moment(fechaSugerida, 'DD/MM/YYYY');
            if (fechaMoment.isValid()) {
                $picker.setStartDate(fechaMoment);
                $picker.setEndDate(fechaMoment);
            }
        }
        
        // Mostrar alerta informativa
        mostrarAdvertenciaFecha($campo, fecha, fechaSugerida, motivo);
        
        /* Correr el arribo al siguiente día hábil mueve la nacionalización,
           que cuelga de él. Se vuelve a pedir la cadena entera en vez de
           recalcular solo ese campo: la cuenta la hace el servidor.

           NO se pregunta acá por fechaDespachoIsManual -como hacía el código
           viejo- porque de eso ya se ocupa aplicarCadenaFechas(), que es el
           único lugar donde se decide qué campo se pisa y cuál no. */
        if ($campo.attr('id') === 'fechaArr') {
            recalcularTodasLasFechas();
        }
    } else {
        ocultarAdvertenciaFecha($campo);
    }
}

/**
 * Muestra advertencia informativa de cambio automático de fecha
 */
function mostrarAdvertenciaFecha($campo, fechaOriginal, fechaSugerida, motivo) {
    // Remover advertencia previa si existe
    ocultarAdvertenciaFecha($campo);
    
    const campoId = $campo.attr('id');
    const mensaje = `
        <div class="alerta-fecha-no-habil" data-campo="${campoId}">
            <i class="bi bi-info-circle"></i>
            La fecha <strong>${fechaOriginal}</strong> es <strong>${motivo}</strong>.
            <br>
            Se cambió automáticamente a: <strong>${fechaSugerida}</strong>
            <small style="display: block; margin-top: 5px; opacity: 0.8;">
                Puede modificarla manualmente si lo desea. Click para cerrar.
            </small>
        </div>
    `;
    
    $campo.after(mensaje);
}

/**
 * Oculta advertencia de fecha no hábil
 */
function ocultarAdvertenciaFecha($campo) {
    $campo.next('.alerta-fecha-no-habil').remove();
    $campo.removeClass('campo-advertencia-fecha');
}

/* ==========================================================================
   LAS FECHAS DERIVADAS LAS CALCULA EL SERVIDOR

   Acá había tres funciones que sumaban días con los números escritos en el
   código: arribo = base + 45, pago = base + 5, nacionalización = arribo + 2.
   El cronograma hacía la misma cuenta con otros números —45 y 7, leídos de
   RO_T_IMPORTACIONES_PARAM_CRONOGRAMA— así que el mismo contenedor tenía dos
   fechas de nacionalización distintas según por qué pantalla se lo mirara.

   Ahora hay un solo cálculo, en CronogramaFechas::cadenaDeFechas(), y este
   archivo NO TIENE NINGÚN NÚMERO DE DÍAS. Le pide la cadena al servidor cada
   vez que cambia una fecha base y aplica lo que le devuelve.

   POR QUÉ PEDIR LA CADENA Y NO LOS DÍAS. Traerse los días para sumar acá
   habría sacado los números del JS pero no la aritmética, y con la aritmética
   duplicada las dos pantallas pueden volver a separarse por cualquier
   detalle. Pidiendo la cadena, la única forma de que difieran es que difieran
   los parámetros. Los días llegan igual en la respuesta, pero solo para los
   carteles ("+45 días"), nunca para calcular.
   ========================================================================== */

/** Días de cada tramo, como los devolvió el servidor. Solo para mostrar. */
let parametrosDias = {};

/** Claves de parámetros que la base no tiene, si falta alguna. */
let parametrosFaltantes = [];

/* La última petición en vuelo. Cambiar el ETD dispara varios handlers
   -dp.change, change y apply.daterangepicker sobre el mismo input- y sin esto
   salen tres pedidos cuyas respuestas pueden llegar desordenadas y dejar la
   pantalla con la cadena de una fecha base vieja. */
let cadenaEnVuelo = null;
let cadenaSecuencia = 0;

/**
 * Convierte un input DD/MM/YYYY al AAAA-MM-DD que espera el servidor.
 * Devuelve '' si el campo está vacío o la fecha no es válida.
 */
function fechaInputAISO(selector) {
    const valor = $(selector).val();
    if (!valor || valor.trim() === '') return '';

    const m = moment(valor.trim(), 'DD/MM/YYYY', true);
    return m.isValid() ? m.format('YYYY-MM-DD') : '';
}

/**
 * Pide al servidor la cadena de fechas y la aplica a los campos.
 *
 * QUÉ MANDA. Las dos fechas de embarque -la prioridad ETD sobre estimada la
 * resuelve el servidor, que es donde vive esa regla-, más el arribo y la
 * recepción cuando son hechos y no proyecciones:
 *
 *   - El arribo va SOLO si está fijado a mano o confirmado. Si va, la
 *     nacionalización cuelga de él. Si no fuera así, a un contenedor con la
 *     ETA confirmada por la naviera se le calcularía la nacionalización sobre
 *     un arribo proyectado que ya se sabe que no va a pasar.
 *
 * QUÉ APLICA. Solo los campos que NO están fijados a mano. Esa decisión se
 * toma acá y no en el servidor a propósito: el servidor devuelve la cadena
 * completa —el cronograma la necesita entera— y cada pantalla decide qué
 * pisar.
 */
function recalcularTodasLasFechas() {
    const datos = {
        fechaEmb:    fechaInputAISO('#fechaEmb'),
        fechaEstEmb: fechaInputAISO('#fechaEstEmb')
    };

    /* El arribo entra a la cadena solo cuando es un hecho. Con el arribo
       automático, mandarlo sería circular: el servidor lo devolvería igual
       que como se lo mandamos y la nacionalización nunca seguiría al ETD. */
    if (fechaArriboIsManual || etaConfirmada) {
        datos.fechaArr = fechaInputAISO('#fechaArr');
    }

    if (!datos.fechaEmb && !datos.fechaEstEmb && !datos.fechaArr) {
        // Sin ninguna fecha de la que colgar, no hay nada que pedir ni que
        // limpiar: los campos quedan como estén.
        return;
    }

    const secuencia = ++cadenaSecuencia;

    if (cadenaEnVuelo && cadenaEnVuelo.abort) {
        cadenaEnVuelo.abort();
    }

    cadenaEnVuelo = $.ajax({
        url: '../controller/calcularCadenaFechas.php',
        method: 'GET',
        dataType: 'json',
        data: datos,
        success: function (r) {
            // Llegó una respuesta vieja: la de la fecha base anterior. Se
            // descarta, porque aplicarla dejaría la pantalla mintiendo.
            if (secuencia !== cadenaSecuencia) return;

            if (!r || !r.success) {
                mostrarErrorCadenaFechas(r && r.message);
                return;
            }

            parametrosDias      = r.parametros || {};
            parametrosFaltantes = r.faltan || [];

            if (parametrosFaltantes.length) {
                mostrarErrorCadenaFechas(r.message);
                return;
            }

            aplicarCadenaFechas(r.pantalla || {});
        },
        error: function (xhr, estado) {
            if (estado === 'abort') return;   // la canceló un pedido más nuevo

            let msg = 'No se pudieron calcular las fechas automáticas.';
            try { msg = JSON.parse(xhr.responseText).message || msg; } catch (e) {}
            mostrarErrorCadenaFechas(msg);
        }
    });
}

/**
 * Escribe en los campos las fechas que devolvió el servidor.
 *
 * NO TOCA LAS FIJADAS A MANO. Es la razón de ser de los tres flags, que desde
 * los scripts 10 y 11 ya no viven solo en memoria: salen del maestro al abrir
 * la pantalla, así que una fecha corregida a mano sigue estando corregida
 * mañana.
 *
 * @param {Object} pantalla fechas en DD/MM/YYYY, de la respuesta del servidor
 */
function aplicarCadenaFechas(pantalla) {
    if (!fechaArriboIsManual && !etaConfirmada) {
        escribirFechaCalculada('#fechaArr', pantalla.arribo);
    }

    if (!fechaEstPagoIsManual) {
        escribirFechaCalculada('#fechaEstPago', pantalla.pago);
    }

    if (!fechaDespachoIsManual) {
        escribirFechaCalculada('#fechaDespAdu', pantalla.nacionalizacion);
    }
}

/**
 * Pone una fecha calculada en un campo: valor, datepicker, estilo y aviso de
 * día no hábil.
 *
 * EL CORRIMIENTO A DÍA HÁBIL SIGUE SIENDO DE ACÁ, no del servidor. La cadena
 * son días corridos; si el resultado cae sábado, domingo o feriado, esta
 * pantalla lo corre al siguiente hábil y AVISA. Subir eso al servidor
 * cambiaría de golpe todas las fechas que dibuja el cronograma, que hoy no
 * corre ninguna, y esta entrega cambia la nacionalización y nada más.
 */
function escribirFechaCalculada(selector, fecha) {
    const $campo = $(selector);
    if (!$campo.length) return;

    if (!fecha) {
        $campo.val('');
        return;
    }

    $campo.val(fecha);

    const $picker = $campo.data('daterangepicker');
    if ($picker) {
        const m = moment(fecha, 'DD/MM/YYYY');
        if (m.isValid()) {
            $picker.setStartDate(m);
            $picker.setEndDate(m);
        }
    }

    marcarCampoCalculado(selector);

    if (!cargandoDatos) {
        setTimeout(() => validarCampoFechaHabil($campo), 100);
    }
}

/**
 * Avisa que las fechas automáticas no se pudieron calcular.
 *
 * NO DEJA LOS CAMPOS EN BLANCO ni les inventa un valor: se queda con lo que
 * haya y lo dice. Un formulario que se vacía solo porque falló una llamada
 * pierde datos que el usuario ya tenía en pantalla.
 */
function mostrarErrorCadenaFechas(mensaje) {
    const texto = mensaje || 'No se pudieron calcular las fechas automáticas.';
    console.warn('Cadena de fechas:', texto);

    $('#avisoCadenaFechas').remove();
    $('#fechaArr').closest('.input-group, .form-group, td, div').first().before(
        '<div id="avisoCadenaFechas" class="alerta-fecha-no-habil">' +
        '<i class="bi bi-exclamation-triangle"></i> ' + escaparAtributo(texto) +
        '</div>'
    );
}


/* ==========================================================================
   LOS BADGES DE LAS FECHAS CALCULADAS

   Eran HTML estático que decía "Auto" siempre, incluso sobre una fecha que
   alguien había puesto a mano. Dicen cuál de las dos cosas es, porque es la
   diferencia entre "esto se va a recalcular solo" y "esto lo decidió alguien".

   AHORA SON TRES, uno por cada fecha que el sistema calcula. El del pago lo
   trajo el script 10; los del arribo y la nacionalización, el 11. Es una sola
   función para las tres: tres copias de este HTML serían tres lugares donde
   arreglar el mismo detalle.
   ========================================================================== */

/**
 * Descripción de cada badge. La regla NO lleva el número de días escrito: sale
 * de parametrosDias, que llega del servidor. Antes este tooltip decía
 * "+5 días" en duro, así que cambiar DIAS_EMB_PAGO desde el ABM dejaba a la
 * pantalla explicando una regla que ya no era la que se aplicaba.
 */
const BADGES_FECHA = {
    PAGO: {
        contenedor: '#badgeFechaEstPago',
        boton:      'btnVolverAutoFechaEstPago',
        etiqueta:   'fecha estimada de pago',
        base:       'fecha de embarque',
        clave:      'DIAS_EMB_PAGO'
    },
    ARRIBO: {
        contenedor: '#badgeFechaArr',
        boton:      'btnVolverAutoFechaArr',
        etiqueta:   'fecha de arribo (ETA)',
        base:       'fecha de embarque',
        clave:      'DIAS_EMB_ARR'
    },
    NACIONALIZACION: {
        contenedor: '#badgeFechaDespAdu',
        boton:      'btnVolverAutoFechaDespAdu',
        etiqueta:   'fecha de nacionalización',
        base:       'fecha de arribo',
        clave:      'DIAS_ARR_DESP'
    }
};

/** Estado actual de cada badge, para no repetir el mismo `if` en tres lados. */
function estadoBadge(tipo) {
    if (tipo === 'PAGO') {
        return { fijada: fechaEstPagoIsManual,
                 usuario: fechaEstPagoConfUsuario, cuando: fechaEstPagoConfFecha };
    }
    if (tipo === 'ARRIBO') {
        return { fijada: fechaArriboIsManual,
                 usuario: fechaArrConfUsuario, cuando: fechaArrConfFecha };
    }
    return { fijada: fechaDespachoIsManual,
             usuario: fechaDespConfUsuario, cuando: fechaDespConfFecha };
}

/**
 * Redibuja el badge de una de las tres fechas calculadas.
 *
 * Manual además trae el botón "volver a auto". Ese botón NO aparece en modo
 * lectura ni en alta: en lectura no se edita nada, y en un alta todavía no hay
 * fila en la base sobre la que revertir.
 *
 * EL DE LA NACIONALIZACIÓN ES EL QUE MÁS IMPORTA hoy: el backfill del script 11
 * marcó como manual toda FECHA_DESP_ADU que no se explicara con la regla vieja
 * —marcar de más se arregla con un clic, marcar de menos deja que el recálculo
 * pise una corrección a mano—, así que va a haber contenedores marcados que en
 * realidad eran automáticos. Este botón es cómo se los libera.
 */
function actualizarBadgeFecha(tipo) {
    const cfg = BADGES_FECHA[tipo];
    if (!cfg) return;

    const $cont = $(cfg.contenedor);
    if (!$cont.length) return;

    const estado = estadoBadge(tipo);

    /* Si todavía no llegaron los parámetros -primer render, antes de la
       primera respuesta del servidor- se dice la regla sin el número en vez de
       inventar uno. */
    const dias  = parametrosDias[cfg.clave];
    const regla = (dias === undefined)
        ? cfg.base
        : cfg.base + ' + ' + dias + ' días';

    if (!estado.fijada) {
        $cont.html('<span class="badge-auto" title="La calcula el sistema: '
                 + escaparAtributo(regla) + '.">Auto</span>');
        return;
    }

    /* Sin login en este módulo, así que el usuario puede llegar vacío. Decir
       "la fijó null" sería peor que no decir quién; misma decisión que
       tooltipRastro() en el cashflow. */
    const quien = estado.usuario || 'desde Comercio Exterior';
    const cuando = estado.cuando
        ? (' el ' + estado.cuando)
        : ' (se guarda al confirmar)';

    const titulo = 'Fecha fijada a mano ' + quien + cuando
        + '. El recálculo automático (' + regla + ') no la toca.';

    let html = '<span class="badge-manual" title="' + escaparAtributo(titulo) + '">Manual</span>';

    /* Los dos hidden los pone tabs/cargaInicial.php. En un alta todavía no hay
       fila en la base sobre la que revertir, y en solo lectura no se edita
       nada: en los dos casos queda el badge sin botón. */
    const enEdicion = $('#modoEdicion').val() === 'true';
    const enLectura = $('#esLectura').val() === '1';

    if (enEdicion && !enLectura) {
        html += '<button type="button" id="' + cfg.boton + '" class="btn-volver-auto"'
              + ' data-tipo-fecha="' + tipo + '"'
              + ' title="Descarta la fecha cargada y vuelve al cálculo automático.">'
              + '<i class="zmdi zmdi-refresh"></i> volver a auto</button>';
    }

    $cont.html(html);
}

/** Alias del nombre que ya usaba el resto del archivo. */
function actualizarBadgeFechaEstPago() {
    actualizarBadgeFecha('PAGO');
}

/**
 * El indicador verde sobre el campo de arribo cuando la ETA está en firme.
 *
 * Es lo único que quedó del checkbox "ETA Confirmada": el gesto se fue, la
 * señal no. Sin ella la pantalla no tendría dónde decir que esa fecha es un
 * hecho y no una estimación, que es justo lo que decide si un choque de fechas
 * bloquea o solo advierte en el cronograma -CronogramaFechas::esFechaReal()-.
 */
function aplicarEstiloEtaConfirmada() {
    const $fechaArr = $('#fechaArr');
    if (!$fechaArr.length) return;

    if (etaConfirmada) {
        $fechaArr.css({
            'border-left': '3px solid #28a745',
            'background-color': '#f0f9f0'
        }).attr('title', 'ETA confirmada. Se confirmó al editarla a mano.');
    } else {
        $fechaArr.css({
            'border-left': '',
            'background-color': ''
        }).removeAttr('title');
    }
}

/** Escapa un texto para meterlo en un atributo HTML. */
function escaparAtributo(texto) {
    return String(texto === null || texto === undefined ? '' : texto)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/"/g, '&quot;');
}

/**
 * "Volver a auto": descarta la fecha fijada y la recalcula en el servidor.
 *
 * SIRVE PARA LAS TRES FECHAS. Era solo para la estimada de pago; desde el
 * script 11 el arribo y la nacionalización también se pueden fijar a mano, así
 * que también necesitan la puerta de salida. El tipo viaja en el
 * data-tipo-fecha que pone actualizarBadgeFecha().
 *
 * PIDE CONFIRMACIÓN porque descarta un dato cargado a mano, y la fecha vieja no
 * queda en ningún lado de la pantalla: queda en
 * RO_T_IMPORTACIONES_FECHAS_HIST, que es otra pantalla.
 *
 * ACTUALIZA SIN RECARGAR, pero el valor que muestra es el que devolvió el
 * servidor y no uno recalculado acá: si los dos calcularan por su cuenta, un
 * día se despegarían y la pantalla mostraría una fecha que la base no tiene.
 */
$(document).on('click', '.btn-volver-auto', function() {
    const id   = $('#idDespacho').val();
    const tipo = $(this).data('tipo-fecha');

    if (!id || !BADGES_FECHA[tipo]) return;

    const cfg = BADGES_FECHA[tipo];

    /* La regla que se le muestra al usuario sale de los parámetros, no de un
       literal: es la cuenta que va a hacer el servidor. */
    const dias  = parametrosDias[cfg.clave];
    const regla = (dias === undefined) ? cfg.base : cfg.base + ' + ' + dias + ' días';

    Swal.fire({
        title: '¿Volver al cálculo automático?',
        html: 'Se va a descartar la ' + cfg.etiqueta + ' cargada a mano y se va a '
            + 'recalcular como <b>' + regla + '</b>.<br><br>'
            + 'El cambio queda registrado en el historial de fechas.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, volver a auto',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ff9800'
    }).then((res) => {
        if (!res.isConfirmed) return;

        $.ajax({
            url: '../controller/revertirFechaAuto.php',
            method: 'POST',
            dataType: 'json',
            data: { id: id, tipo: tipo },
            success: function(r) {
                if (!r.success) {
                    Swal.fire('No se pudo', r.message || 'Error al revertir la fecha', 'error');
                    return;
                }

                aplicarReversionFecha(tipo, r.fechaPantalla);

                Swal.fire({
                    title: 'Listo',
                    text: r.message,
                    icon: r.aviso ? 'warning' : 'success',
                    timer: r.aviso ? undefined : 2000,
                    showConfirmButton: !!r.aviso
                });
            },
            error: function(xhr) {
                let msg = 'Error al revertir la fecha';
                try { msg = JSON.parse(xhr.responseText).message || msg; } catch (e) {}
                Swal.fire('No se pudo', msg, 'error');
            }
        });
    });
});

/**
 * Deja la pantalla como quedó la base después de revertir una fecha.
 *
 * VOLVER EL ARRIBO A AUTO ARRASTRA A LA NACIONALIZACIÓN, que cuelga de él: si
 * no está fijada, se vuelve a pedir la cadena para que siga al arribo nuevo.
 * El servidor ya la movió en la base; esto es solo que la pantalla no quede
 * mostrando la vieja hasta el próximo F5.
 */
function aplicarReversionFecha(tipo, fechaPantalla) {
    const campos = {
        PAGO:            '#fechaEstPago',
        ARRIBO:          '#fechaArr',
        NACIONALIZACION: '#fechaDespAdu'
    };

    if (tipo === 'PAGO') {
        fechaEstPagoIsManual = false;
        fechaEstPagoConfUsuario = null;
        fechaEstPagoConfFecha = null;
    } else if (tipo === 'ARRIBO') {
        fechaArriboIsManual = false;
        etaConfirmada = false;
        fechaArrConfUsuario = null;
        fechaArrConfFecha = null;
        aplicarEstiloEtaConfirmada();
    } else {
        fechaDespachoIsManual = false;
        fechaDespConfUsuario = null;
        fechaDespConfFecha = null;
    }

    if (fechaPantalla) {
        const selector = campos[tipo];
        $(selector).val(fechaPantalla);

        const $picker = $(selector).data('daterangepicker');
        if ($picker) {
            const m = moment(fechaPantalla, 'DD/MM/YYYY');
            $picker.setStartDate(m);
            $picker.setEndDate(m);
        }

        marcarCampoCalculado(selector);
    }

    actualizarBadgeFecha(tipo);

    if (tipo === 'ARRIBO' && !fechaDespachoIsManual) {
        recalcularTodasLasFechas();
    }
}


/**
 * Carga los datos de un despacho existente en el formulario (modo edición)
 */
function cargarDatosDespacho(datos) {
    console.log('Cargando datos del despacho:', datos);
    
    // Activar flag de carga para evitar marcar campos como manuales
    cargandoDatos = true;
    
    // Sección 1 - Datos Iniciales
    if (datos.COD_PROVEE) {
        $('#proveedor').val(datos.COD_PROVEE).trigger('change');
    }
    if (datos.CONTENEDOR) $('#contenedor').val(datos.CONTENEDOR);
    if (datos.MATERIAL) $('#material').val(datos.MATERIAL);
    if (datos.ORIGEN) $('#origen').val(datos.ORIGEN);
    if (datos.VALOR_FOB_DOLAR) $('#valorFobDolar').val(datos.VALOR_FOB_DOLAR);
    if (datos.FECHA_EST_EMB) $('#fechaEstEmb').val(datos.FECHA_EST_EMB);
    if (datos.DESPACHANTE) {
        $('#despachante').val(datos.DESPACHANTE).trigger('change');
    }
    if (datos.ORDEN_COMPRA) {
        // Cargar órdenes de compra (manejar múltiples si están separadas por coma)
        const ordenes = datos.ORDEN_COMPRA.split(',');
        ordenes.forEach(oc => {
            if (oc.trim()) {
                agregarOrdenAlContenedor(oc.trim());
            }
        });
    }
    if (datos.OCM) $('#ordenManual').prop('checked', datos.OCM === '1' || datos.OCM === 1);
    
    // Sección 2 - Datos de Embarque
    if (datos.FECHA_EMB) {
        $('#fechaEmb').val(datos.FECHA_EMB);
    }
    if (datos.FECHA_ARR) {
        $('#fechaArr').val(datos.FECHA_ARR);
    }

    /* EL ESTADO SALE DE LA BASE, NO DE QUE HAYA UN VALOR CARGADO.
       Acá había un `fechaArriboIsManual = true` dentro del if de arriba, con
       el comentario "Marcar como manual para evitar recálculo". Era el mismo
       bug que el script 10 corrigió para la fecha de pago, al revés: en vez de
       perder la marca, la inventaba. Todo contenedor con arribo cargado —o
       sea, todos— quedaba marcado como fijado a mano, así que mover el ETD no
       movía nada y cambiar DIAS_EMB_ARR desde el ABM no llegaba a ninguna
       fila existente.

       Ahora sale de ETA_CONFIRMADA, que es el BIT del maestro que ya
       significaba "esta ETA es un hecho, no una proyección": se enciende al
       editar la ETA a mano y sobrevive al cierre de la pantalla.

       Va FUERA del if a propósito: un contenedor puede estar marcado sin fecha
       cargada, y en ese caso el badge igual tiene que decir Manual. */
    etaConfirmada = (datos.ETA_CONFIRMADA === 1 || datos.ETA_CONFIRMADA === '1');
    fechaArriboIsManual = etaConfirmada;
    fechaArrConfUsuario = datos.ETA_CONF_USUARIO || null;
    fechaArrConfFecha = datos.ETA_CONF_FECHA || null;
    aplicarEstiloEtaConfirmada();
    actualizarBadgeFecha('ARRIBO');
    if (datos.NUMERO_BL) $('#numeroBl').val(datos.NUMERO_BL);
    if (datos.FACTURA) $('#factura').val(datos.FACTURA);
    if (datos.PUERTO_ORIGEN) {
        $('#puertoOrigen').val(datos.PUERTO_ORIGEN).trigger('change');
        console.log('Puerto Origen cargado:', datos.PUERTO_ORIGEN);
    }
    if (datos.TERMINAL) {
        $('#terminal').val(datos.TERMINAL).trigger('change');
        console.log('Terminal cargado:', datos.TERMINAL);
    }
    
    // Sección 3 - Datos Financieros y Aduana
    if (datos.TIPO_CAMBIO) $('#tipoCambio').val(datos.TIPO_CAMBIO);
    if (datos.VALOR_FOB_PESO) {
        const valorFormateado = '$ ' + parseFloat(datos.VALOR_FOB_PESO).toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        $('#valorFobPeso').val(valorFormateado);
    }
    if (datos.FORMA_PAGO) $('#formaPago').val(datos.FORMA_PAGO).trigger('change');
    /* Acá se cargaba datos.FECHA_PAGO en #fechaPago. Las dos puntas eran
       fantasmas: ni el input existe en el formulario ni FECHA_PAGO es una
       columna del maestro en central ni en uy. */
    if (datos.FECHA_EST_PAGO) {
        $('#fechaEstPago').val(datos.FECHA_EST_PAGO);
        // Sincronizar datepicker
        if ($('.js-datepicker-est-pago').data('daterangepicker')) {
            const fecha = moment(datos.FECHA_EST_PAGO, 'DD/MM/YYYY');
            $('.js-datepicker-est-pago').data('daterangepicker').setStartDate(fecha);
            $('.js-datepicker-est-pago').data('daterangepicker').setEndDate(fecha);
        }
    }

    /* EL ESTADO SALE DE LA BASE, NO DE ESTA SESIÓN.
       Acá había un `fechaEstPagoIsManual = false` con el comentario "NO marcar
       como manual - es solo carga de datos de BD". Era el bug: una fecha que
       alguien había fijado a mano volvía a nacer automática en cada apertura y
       el recálculo de +5 días la pisaba en el primer guardado.

       Va FUERA del if de arriba a propósito: un contenedor puede estar marcado
       como fijado sin fecha cargada -pasa si se revirtió el embarque después de
       fijarla- y en ese caso el badge igual tiene que decir Manual.

       Sin el script 10 la clave llega en 0 -Encabezado::obtenerDespachoPorId()
       la define siempre- y la pantalla se comporta exactamente como hoy. */
    fechaEstPagoIsManual = (datos.FECHA_PAGO_CONF === 1 || datos.FECHA_PAGO_CONF === '1');
    fechaEstPagoConfUsuario = datos.FECHA_PAGO_CONF_USUARIO || null;
    fechaEstPagoConfFecha = datos.FECHA_PAGO_CONF_FECHA || null;
    actualizarBadgeFechaEstPago();
    if (datos.FECHA_DESP_ADU) {
        $('#fechaDespAdu').val(datos.FECHA_DESP_ADU);
        // Sincronizar datepicker
        if ($('.js-datepicker-despacho').data('daterangepicker')) {
            const fecha = moment(datos.FECHA_DESP_ADU, 'DD/MM/YYYY');
            $('.js-datepicker-despacho').data('daterangepicker').setStartDate(fecha);
            $('.js-datepicker-despacho').data('daterangepicker').setEndDate(fecha);
        }
    }

    /* Misma corrección que en el arribo: acá había un
       `fechaDespachoIsManual = true` por el solo hecho de que la fila trajera
       FECHA_DESP_ADU, y todas la traen. El estado ahora sale de
       FECHA_DESP_CONF, el BIT que creó sql/11.

       ES EL CAMBIO QUE HACE QUE LA NACIONALIZACIÓN VUELVA A SEGUIR AL ARRIBO,
       y es también el que hace imprescindible el backfill del script 11: sin
       esas marcas, el primer guardado de cada contenedor pisaría con arribo +
       DIAS_ARR_DESP las fechas que alguien había corregido a mano.

       Sin el script 11 la clave llega en 0 -Encabezado::obtenerDespachoPorId()
       la define siempre-. Ojo con eso: en una base donde el DDL no corrió, la
       pantalla pasa a recalcular fechas que antes quedaban congeladas. Por eso
       el 11 va antes que el deploy, no después. */
    fechaDespachoIsManual = (datos.FECHA_DESP_CONF === 1 || datos.FECHA_DESP_CONF === '1');
    fechaDespConfUsuario = datos.FECHA_DESP_CONF_USUARIO || null;
    fechaDespConfFecha = datos.FECHA_DESP_CONF_FECHA || null;
    actualizarBadgeFecha('NACIONALIZACION');
    if (datos.GASTOS_PUERTO_DOLAR) $('#gastosPuertoDolar').val(datos.GASTOS_PUERTO_DOLAR);
    if (datos.GASTOS_PUERTO_PESO) $('#gastosPuertoPeso').val(datos.GASTOS_PUERTO_PESO);
    if (datos.FLETE_INTERNACIONAL) $('#fleteInternacional').val(datos.FLETE_INTERNACIONAL);
    if (datos.SEGURO) $('#seguro').val(datos.SEGURO);
    if (datos.DERECHOS) $('#derechos').val(datos.DERECHOS);
    if (datos.TASA_ESTADISTICA) $('#tasaEstadistica').val(datos.TASA_ESTADISTICA);
    if (datos.IVA_ADICIONAL) $('#ivaAdicional').val(datos.IVA_ADICIONAL);
    if (datos.GASTO_DESPACHANTE) $('#gastoDespachante').val(datos.GASTO_DESPACHANTE);
    if (datos.ANTICIPO) $('#anticipo').val(datos.ANTICIPO);
    
    // IMPORTANTE: Guardar el número de despacho para cargarlo después de los recálculos
    const numeroDespacho = datos.DESPACHO || null;
    
    // Desactivar flag de carga
    cargandoDatos = false;
    
    // Sincronizar datepickers con las fechas cargadas desde BD
    sincronizarDatepickers();
    
    console.log('Datos cargados correctamente');
    console.log('Todos los datos recibidos:', datos);
    
    // Cargar pagos si hay ID de despacho
    const idDespacho = $('#idDespacho').val();
    if (idDespacho) {
        cargarPagos(idDespacho);
    }
    
    // Cargar el número de despacho DESPUÉS de que se desactive cargandoDatos
    // para que los recálculos no lo sobrescriban
    if (numeroDespacho) {
        console.log('Cargando número de despacho después de recálculos:', numeroDespacho);
        // Usar setTimeout para asegurar que se ejecuta después de los recálculos
        setTimeout(function() {
            $('#despacho').val(numeroDespacho);
            console.log('Número de despacho cargado:', numeroDespacho);
        }, 100);
    }
}

/**
 * Carga los pagos del despacho desde BD
 */
function cargarPagos(idDespacho) {
    const id = parseInt(idDespacho);
    if (!id || id <= 0) {
        // Contenedor todavía sin guardar: no hay pagos, y el saldo es el FOB
        // entero. La cuenta la hace el servidor apenas exista el registro.
        $('#tbodyPagos').html('');
        $('#sinPagos').show();
        pagosCargados = [];
        aplicarResumenPagos({ fobUsd: obtenerFOBDolar(), totalPagado: 0,
                              saldoPendiente: obtenerFOBDolar() });
        return;
    }

    console.log('Cargando pagos del despacho:', id);

    $.ajax({
        url: '../controller/traerPagosController.php',
        method: 'POST',
        data: { id_despacho: id },
        dataType: 'json',
        success: function(response) {
            console.log('Pagos cargados:', response);
            if (response && response.pagos) {
                renderizarTablaPagos(response.pagos);
                aplicarResumenPagos(response);
            }
        },
        error: function(err) {
            console.error('Error al cargar pagos:', err);
        }
    });
}

/**
 * Renderiza la tabla de pagos. Los importes son U$S.
 */
function renderizarTablaPagos(pagos) {
    const tbody = $('#tbodyPagos');
    tbody.html(''); // Limpiar tabla

    pagosCargados = pagos || [];

    if (!pagos || pagos.length === 0) {
        // Mostrar mensaje "No hay pagos"
        $('#sinPagos').show();
        return;
    }

    // Ocultar mensaje "No hay pagos"
    $('#sinPagos').hide();

    pagos.forEach(pago => {
        /* Los pagos que el script 08 convirtió de pesos a dólares lo dicen
           acá. El importe que manda es el de U$S -es el que cuenta para el
           saldo- pero de dónde salió no debería tener que buscarse en la
           base para entender por qué ese número no es el que se tipeó. */
        const convertido = pago.MONTO_ORIGEN_ARS !== null && pago.MONTO_ORIGEN_ARS !== undefined;
        const marcaConversion = convertido
            ? ` <i class="bi bi-arrow-left-right text-muted" title="Cargado originalmente como $ ${formatearMonedaUI(pago.MONTO_ORIGEN_ARS)} y convertido a U$S"></i>`
            : '';

        const fila = `
            <tr>
                <td>
                    <a href="#" class="link-primary" data-bs-toggle="modal" data-bs-target="#modalEditarFechaPago" onclick="abrirModalEditarPago(${pago.ID})">
                        ${pago.FECHA_PAGO || '-'}
                        <i class="bi bi-pencil-square ms-1"></i>
                    </a>
                </td>
                <td>${pago.FORMA_PAGO || '-'}</td>
                <td>${pago.MEDIO_PAGO || '-'}</td>
                <td class="text-end">U$S ${formatearMonedaUI(pago.MONTO)}${marcaConversion}</td>
                <td style="text-align: center;">
                    <button class="btn btn-sm btn-outline-secondary" title="Editar pago"
                            data-bs-toggle="modal" data-bs-target="#modalEditarFechaPago"
                            onclick="abrirModalEditarPago(${pago.ID})"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-danger" title="Eliminar pago"
                            onclick="eliminarPago(${pago.ID})"><i class="bi bi-trash"></i></button>
                </td>
            </tr>
        `;
        tbody.append(fila);
    });
}

/**
 * Abre el modal de edición con TODOS los campos del pago, no sólo la fecha.
 *
 * Recibe el ID y busca el pago en lo último que trajo el servidor, en vez de
 * recibir los valores interpolados en el onclick: una observación o una forma
 * de pago con una comilla rompía el atributo, y el importe llegaba ya
 * formateado para mostrar, que no es lo que hay que poner en un input number.
 */
function abrirModalEditarPago(idPago) {
    const pago = (pagosCargados || []).find(p => parseInt(p.ID) === parseInt(idPago));
    if (!pago) {
        console.warn('No se encontró el pago', idPago, 'en los datos cargados');
        return;
    }

    $('#idPagoEdit').val(pago.ID);
    $('#fechaPagoEdit').val(pago.FECHA_PAGO || '');
    $('#formaPagoEdit').val(pago.FORMA_PAGO || 'PAGO VISTA');
    $('#medioPagoEdit').val(pago.MEDIO_PAGO || 'Transferencia');
    $('#montoEdit').val(parseFloat(pago.MONTO || 0).toFixed(2));

    const $aviso = $('#avisoOrigenArs');
    if (pago.MONTO_ORIGEN_ARS !== null && pago.MONTO_ORIGEN_ARS !== undefined) {
        $aviso.html('<i class="bi bi-arrow-left-right"></i> Este pago se cargó originalmente como ' +
                    '$ ' + formatearMonedaUI(pago.MONTO_ORIGEN_ARS) + ' y se convirtió a dólares. ' +
                    'El importe original queda guardado.').show();
    } else {
        $aviso.hide().html('');
    }

    console.log('Editando pago ID:', pago.ID);
    
    // Inicializar datepicker del modal si no está inicializado
    if (!$('.js-datepicker-edit-fecha').data('daterangepicker')) {
        $('.js-datepicker-edit-fecha').daterangepicker({
            singleDatePicker: true,
            showDropdowns: true,
            autoApply: true,
            locale: {format: 'DD/MM/YYYY'}
        }).on('apply.daterangepicker', function() {
            setTimeout(() => validarCampoFechaHabil($(this)), 100);
        });
    }
    
    // Establecer la fecha en el datepicker
    const picker = $('.js-datepicker-edit-fecha').data('daterangepicker');
    if (picker) {
        const fecha = moment(pago.FECHA_PAGO, 'DD/MM/YYYY');
        if (fecha.isValid()) {
            picker.setStartDate(fecha);
            picker.setEndDate(fecha);
        }
    }
}

/**
 * Guarda el pago editado: fecha, forma, medio e importe en U$S.
 *
 * Conserva el nombre porque es el que invoca el botón del modal; lo que
 * cambió es que ya no manda sólo la fecha.
 */
function guardarFechaPago() {
    const idPago = $('#idPagoEdit').val();
    const nuevaFecha = $('#fechaPagoEdit').val();
    const nuevoMonto = parseFloat($('#montoEdit').val());

    if (!idPago || !nuevaFecha) {
        Swal.fire('Error', 'Fecha requerida', 'error');
        return;
    }

    if (isNaN(nuevoMonto) || nuevoMonto <= 0) {
        Swal.fire('Error', 'El importe en U$S tiene que ser mayor a cero', 'error');
        return;
    }

    $.ajax({
        url: '../controller/actualizarPagoController.php',
        method: 'POST',
        data: {
            id_pago: idPago,
            fecha_pago: nuevaFecha,
            forma_pago: $('#formaPagoEdit').val(),
            medio_pago: $('#medioPagoEdit').val(),
            monto: nuevoMonto
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const editModalEl = document.getElementById('modalEditarFechaPago');
                const idDespacho = $('#idDespacho').val();

                $(editModalEl).one('hidden.bs.modal', function() {
                    cargarPagos(idDespacho);
                });

                bootstrap.Modal.getOrCreateInstance(editModalEl).hide();

                Swal.fire({
                    icon: 'success',
                    title: 'Pago actualizado',
                    toast: true,
                    position: 'top-end',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire('Error', response.error || 'Error al actualizar', 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Error en la solicitud', 'error');
        }
    });
}

// ========== HELPERS DE PAGOS ==========
//
// LA MONEDA DE LOS PAGOS ES EL DÓLAR.
//
//     Saldo pendiente U$S = Valor FOB U$S - Σ Pagos U$S
//
// Antes el saldo se calculaba contra el FOB en pesos, y el total pagado se
// obtenía leyendo el TEXTO de una columna de la tabla y desarmando su formato.
// Eso tenía dos problemas: la columna MONTO no declara moneda -y de hecho
// convivían las dos, ver sql/08_pagos_en_dolares.sql- y la suma del navegador
// era una segunda implementación de una cuenta que el servidor ya hacía.
//
// Ahora la cuenta la hace Pagos::obtenerResumen() y la pantalla la muestra.
// Lo que el servidor devuelve se guarda acá para que el modal de alta pueda
// calcular el "nuevo saldo" sin volver a pedirlo.

function formatearMonedaUI(valor) {
    if (isNaN(valor) || valor === null) return '0,00';
    return parseFloat(valor).toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

/**
 * El FOB en dólares tal como está en el formulario.
 *
 * Se lee de la pantalla y no del resumen del servidor porque tiene que
 * reaccionar mientras el usuario EDITA el FOB, antes de guardar: es lo que
 * hace que el saldo se mueva al corregir el importe. El número que manda para
 * la cuenta definitiva sigue siendo el del servidor.
 */
function obtenerFOBDolar() {
    let valor = $('#valorFobDolar').val();
    if (!valor) return 0;
    valor = valor.toString().replace(/U\$S|\$|\s/gi, '');
    if (valor.includes(',')) {
        valor = valor.replace(/\./g, '').replace(',', '.');
    }
    return parseFloat(valor) || 0;
}

function obtenerTotalPagado() {
    return parseFloat(resumenPagos.totalPagado) || 0;
}

/**
 * El saldo que muestra la pantalla.
 *
 * Usa el FOB del formulario -que puede estar recién editado y todavía sin
 * guardar- contra el total pagado que informó el servidor. Así, corregir el
 * FOB mueve el saldo en el momento, que es justamente lo que se espera al
 * hacer editable ese campo.
 */
function obtenerSaldoPendiente() {
    return obtenerFOBDolar() - obtenerTotalPagado();
}

/**
 * Guarda lo que devolvió el servidor y repinta.
 */
function aplicarResumenPagos(resumen) {
    if (!resumen) return;

    resumenPagos = {
        fobUsd:         parseFloat(resumen.fobUsd) || 0,
        totalPagado:    parseFloat(resumen.totalPagado) || 0,
        saldoPendiente: parseFloat(resumen.saldoPendiente) || 0
    };

    repintarResumenPagos();
}

/**
 * Repinta los TRES números de una sola pasada: FOB, pagado y saldo.
 *
 * Van juntos a propósito. El FOB que se muestra es el del formulario -que
 * puede estar recién corregido y todavía sin guardar- y es el mismo con el
 * que se calcula el saldo. Si el cartel del FOB mostrara el valor del
 * servidor y el saldo usara el de la pantalla, editar el FOB dejaría tres
 * números que no cierran entre sí, y no habría nada que explicara por qué.
 */
function repintarResumenPagos() {
    const fob = obtenerFOBDolar();

    $('#fobTotalUsd').html('<i class="bi bi-cash-stack"></i> FOB: U$S ' + formatearMonedaUI(fob));
    $('#totalPagadoUsd').html('<i class="bi bi-check2-all"></i> Pagado: U$S ' +
                              formatearMonedaUI(resumenPagos.totalPagado));

    actualizarSaldoPendiente(fob - obtenerTotalPagado());
}

/**
 * Habilita/deshabilita el botón Agregar Pago según FOB y saldo.
 *
 * Ya no depende del Tipo de Cambio: como el pago es en dólares, alcanza con
 * tener cargado el FOB U$S. Antes un contenedor con FOB pero sin TC no dejaba
 * registrar ningún pago, aunque el importe en dólares se supiera perfectamente.
 */
function actualizarEstadoBotonPago() {
    const $btn = $('#btnAgregarPago');
    if (!$btn.length) return;

    const fob = obtenerFOBDolar();
    const saldo = obtenerSaldoPendiente();
    const TOLERANCIA = 0.01;

    if (fob <= 0) {
        $btn.prop('disabled', true)
            .attr('title', 'Cargá primero el Valor F.O.B. U$S para poder registrar pagos')
            .attr('data-bs-toggle', 'tooltip');
    } else if (saldo <= TOLERANCIA) {
        $btn.prop('disabled', true)
            .attr('title', 'El pago ya está completo, no se pueden agregar más pagos')
            .attr('data-bs-toggle', 'tooltip');
    } else {
        $btn.prop('disabled', false)
            .removeAttr('title')
            .removeAttr('data-bs-toggle');
    }

    const tt = bootstrap.Tooltip.getInstance($btn[0]);
    if (tt) tt.dispose();
    if ($btn.attr('data-bs-toggle') === 'tooltip') {
        new bootstrap.Tooltip($btn[0]);
    }
}

/**
 * Actualiza el badge de saldo pendiente con color según estado. Todo en U$S.
 */
function actualizarSaldoPendiente(saldo) {
    const $badge = $('#saldoPendiente');
    $badge.removeClass('bg-success bg-warning bg-danger text-white');
    const TOLERANCIA = 0.01;

    if (Math.abs(saldo) < TOLERANCIA) {
        $badge.html('<i class="bi bi-check-circle-fill"></i> Pagado completo')
              .addClass('bg-success text-white')
              .css('color', '');
    } else if (saldo < 0) {
        /* Sobrepago: se muestra, no se bloquea. Puede ser un pago cargado de
           más -y entonces hay que corregirlo- o un FOB que quedó viejo, que
           es exactamente lo que el campo editable vino a permitir arreglar.
           Cuál de las dos cosas es, lo sabe quien mira, no la pantalla. */
        $badge.html('<i class="bi bi-exclamation-triangle"></i> Sobrepago: U$S ' + formatearMonedaUI(Math.abs(saldo)))
              .addClass('bg-danger text-white')
              .css('color', '');
    } else {
        $badge.html('<i class="bi bi-hourglass-split"></i> Saldo pendiente: U$S ' + formatearMonedaUI(saldo))
              .addClass('bg-warning')
              .css('color', '#856404');
    }

    actualizarEstadoBotonPago();
}

/* recalcularFechaDespacho() ESTABA ACÁ y hacía "arribo + 2 días", que era el
   número que no coincidía con el DIAS_ARR_DESP = 7 de la tabla de parámetros
   y que ahora es DIAS_ARR_DESP = 5 para las dos pantallas.

   Quedó reemplazada por recalcularTodasLasFechas(), que pide la cadena entera
   al servidor: la nacionalización no se puede recalcular sola sin volver a
   decidir de qué arribo cuelga, y esa decisión ya la toma
   CronogramaFechas::cadenaDeFechas(). */

/**
 * Sincroniza todos los datepickers con los valores actuales de los inputs
 * Se usa después de cargar datos desde BD
 */
function sincronizarDatepickers() {
    console.log('Sincronizando datepickers con valores de inputs...');
    
    // Fecha Estimada de Embarque
    const fechaEstEmb = $('#fechaEstEmb').val();
    if (fechaEstEmb) {
        const $picker = $('#fechaEstEmb').data('daterangepicker');
        if ($picker) {
            const fecha = moment(fechaEstEmb, 'DD/MM/YYYY');
            if (fecha.isValid()) {
                $picker.setStartDate(fecha);
                $picker.setEndDate(fecha);
                console.log('Fecha Est. Embarque sincronizada:', fechaEstEmb);
            }
        }
    }
    
    // Fecha de Embarque (ETD)
    const fechaEmb = $('#fechaEmb').val();
    if (fechaEmb) {
        const $picker = $('#fechaEmb').data('daterangepicker');
        if ($picker) {
            const fecha = moment(fechaEmb, 'DD/MM/YYYY');
            if (fecha.isValid()) {
                $picker.setStartDate(fecha);
                $picker.setEndDate(fecha);
                console.log('Fecha Embarque (ETD) sincronizada:', fechaEmb);
            }
        }
    }
    
    // Fecha Arribo (ETA)
    const fechaArr = $('#fechaArr').val();
    if (fechaArr) {
        const $picker = $('#fechaArr').data('daterangepicker');
        if ($picker) {
            const fecha = moment(fechaArr, 'DD/MM/YYYY');
            if (fecha.isValid()) {
                $picker.setStartDate(fecha);
                $picker.setEndDate(fecha);
                console.log('Fecha Arribo sincronizada:', fechaArr);
            }
        }
    }
    
    // Fecha Estimada de Pago
    const fechaEstPago = $('#fechaEstPago').val();
    if (fechaEstPago) {
        const $picker = $('#fechaEstPago').data('daterangepicker');
        if ($picker) {
            const fecha = moment(fechaEstPago, 'DD/MM/YYYY');
            if (fecha.isValid()) {
                $picker.setStartDate(fecha);
                $picker.setEndDate(fecha);
                console.log('Fecha Est. Pago sincronizada:', fechaEstPago);
            }
        }
    }
    
    // Fecha de Nacionalización
    const fechaDespAdu = $('#fechaDespAdu').val();
    if (fechaDespAdu) {
        const $picker = $('#fechaDespAdu').data('daterangepicker');
        if ($picker) {
            const fecha = moment(fechaDespAdu, 'DD/MM/YYYY');
            if (fecha.isValid()) {
                $picker.setStartDate(fecha);
                $picker.setEndDate(fecha);
                console.log('Fecha Nacionalización sincronizada:', fechaDespAdu);
            }
        }
    }
    
    console.log('Sincronización de datepickers completada');
}

/* recalcularTodasLasFechas() ESTABA ACÁ y encadenaba recalcularFechaArribo() ->
   recalcularFechaDespacho() + recalcularFechaPago() + recalcularFechaEstimadaPago(),
   cada una con sus días escritos en el código. Ahora es una sola llamada al
   servidor y vive arriba, junto al resto de la cadena. */

/**
 * Calcula FOB en Pesos = FOB U$S × Tipo de Cambio
 * Se recalcula cada vez que cualquiera de los dos valores cambie
 * En Uruguay: FOB Peso = FOB Dólar (mismo valor, ya que se ingresa en pesos uruguayos)
 */
/**
 * Calcula FOB en Pesos = FOB U$S × Tipo de Cambio
 */
/**
 * Calcula FOB en Pesos = FOB U$S × Tipo de Cambio
 * Soporta inputs con punto (1452.50) o coma (1452,50)
 */
function recalcularFobPesos() {
    const entorno = $('#entorno').text().trim();
    
    // Función robusta para parsear números
    const parsearNumeroHibrido = (valor) => {
        if (!valor) return 0;
        let str = valor.toString().replace('$', '').replace(/\s/g, '');
        
        // Si tiene coma, es formato AR: borrar puntos, cambiar coma a punto
        if (str.includes(',')) {
            str = str.replace(/\./g, ''); // Borrar miles
            str = str.replace(',', '.');  // Decimal
        }
        // Si NO tiene coma, asumimos que el punto (si existe) ya es decimal
        // (No hacemos replace del punto)
        
        return parseFloat(str) || 0;
    };

    const valorFobDolar = parsearNumeroHibrido($('#valorFobDolar').val());
    
    if (entorno === 'uy') {
        if (valorFobDolar > 0) {
            const valorFormateado = '$ ' + valorFobDolar.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            $('#valorFobPeso').val(valorFormateado);
            marcarCampoCalculado('#valorFobPeso');
        } else {
            $('#valorFobPeso').val('');
        }
    } else {
        const tipoCambio = parsearNumeroHibrido($('#tipoCambio').val());
        
        if (valorFobDolar > 0 && tipoCambio > 0) {
            const valorFobPeso = valorFobDolar * tipoCambio;
            // Formatear para mostrar en pantalla (siempre muestra con coma decimal)
            const valorFormateado = '$ ' + valorFobPeso.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2});

            $('#valorFobPeso').val(valorFormateado);
            marcarCampoCalculado('#valorFobPeso');
        } else {
            $('#valorFobPeso').val('');
        }
    }

    /* Corregir el FOB U$S mueve el saldo de los pagos en el acto, sin esperar
       a guardar. Antes acá sólo se refrescaba el botón de Agregar Pago, así
       que el badge seguía mostrando el saldo del FOB viejo hasta recargar la
       pantalla: el número que la gente mira para decidir cuánto pagar. */
    repintarResumenPagos();
}

// ========== FUNCIONES DE MANUAL OVERRIDE ==========

/**
 * Marca visualmente un campo como calculado automáticamente
 */
function marcarCampoCalculado(selector) {
    $(selector).removeClass('campo-manual-override').addClass('campo-calculado');
}

/**
 * Marca visualmente un campo como editado manualmente
 */
function marcarCampoManual(selector) {
    $(selector).removeClass('campo-calculado').addClass('campo-manual-override');
}

/**
 * Reactiva el cálculo automático cuando un campo se vacía
 */
function verificarCampoVacio(selector, flagVariable, flagName) {
    $(selector).on('change', function() {
        const valor = $(this).val();
        if (!valor || valor.trim() === '') {
            // Campo vacío: reactivar cálculo automático
            window[flagName] = false;
            $(this).removeClass('campo-manual-override campo-calculado');
        }
    });
}

/**
 * Detecta edición manual en campos calculados
 */
function configurarManualOverride() {
    // Fecha Arribo (FECHA_ARR - ETA)
    $('#fechaArr').on('dp.change', function(e) {
        if (!cargandoDatos && e.date && $(this).val().trim() !== '') {
            console.log('Usuario modificó Fecha Arribo manualmente');
            fechaArriboIsManual = true;
            marcarCampoManual('#fechaArr');
            actualizarBadgeFecha('ARRIBO');
            /* Mover el arribo mueve la nacionalización, que cuelga de él. Se
               pide la cadena entera: el arribo ya fijado viaja al servidor y
               la nacionalización se recalcula sobre ÉL y no sobre el arribo
               proyectado. */
            recalcularTodasLasFechas();
        }
    });
    verificarCampoVacio('#fechaArr', fechaArriboIsManual, 'fechaArriboIsManual');

    /* EL HANDLER DE #fechaPago NO ESTÁ MÁS. Escuchaba un input que no existe:
       tabs/cargaInicial.php no tiene ningún id="fechaPago" -los fechaPagoNuevo
       y fechaPagoEdit son de los modales de pagos- y FECHA_PAGO tampoco es una
       columna del maestro en ninguna de las dos bases. Era la otra mitad de la
       regla "pago = embarque + 5": sumaba los días y los escribía en un
       jQuery vacío. La fecha de pago que sí existe es FECHA_EST_PAGO, más
       abajo, y ahora la calcula el servidor con DIAS_EMB_PAGO. */

    // Fecha Nacionalización (FECHA_DESP_ADU)
    $('#fechaDespAdu').on('dp.change', function(e) {
        if (!cargandoDatos && e.date && $(this).val().trim() !== '') {
            console.log('Usuario modificó Fecha Despacho manualmente');
            fechaDespachoIsManual = true;
            /* La fecha todavía no se guardó: el usuario y la hora reales los
               pone el backend al confirmar. Se limpian para no mostrar en el
               tooltip los datos de la edición anterior. */
            fechaDespConfUsuario = null;
            fechaDespConfFecha = null;
            marcarCampoManual('#fechaDespAdu');
            actualizarBadgeFecha('NACIONALIZACION');
        }
    });
    verificarCampoVacio('#fechaDespAdu', fechaDespachoIsManual, 'fechaDespachoIsManual');
}

// ========== LÓGICA DE ETAPAS (ALTA vs EDICIÓN) ==========

/**
 * Controla el modo de edición del formulario
 */
function establecerModoFormulario(esEdicion) {
    $('#modoEdicion').val(esEdicion ? 'true' : 'false');
    
    if (esEdicion) {
        // MODO EDICIÓN: Sección 1 readonly excepto Valor FOB U$S
        $('#proveedor').prop('disabled', true).addClass('campo-readonly');
        $('#contenedor').prop('readonly', true).addClass('campo-readonly');
        $('#material').prop('readonly', true).addClass('campo-readonly');
        $('#origen').prop('readonly', true).addClass('campo-readonly');
        $('#fechaEstEmb').prop('readonly', true).addClass('campo-readonly');
        // Para select2, deshabilitar el select y el contenedor
        $('#despachante').prop('disabled', true).addClass('campo-readonly');
        $('#despachante').next('.select-dropdown').find('.select2-container').css({
            'pointer-events': 'none',
            'opacity': '0.6',
            'background-color': '#e9ecef'
        });
        $('#btnAddOrdenCompra').prop('disabled', true).css('opacity', '0.5');
        
        /* Valor FOB U$S sigue editable: es la excepción de la Sección 1.
           Funcionaba, pero no se notaba. Rodeado de seis campos grises, un
           campo blanco más se lee como "otro readonly", y la corrección del
           FOB terminaba pidiéndose por mail. La marca lo dice. */
        $('#valorFobDolar')
            .prop('readonly', false)
            .removeClass('campo-readonly')
            .addClass('campo-editable-excepcion')
            .attr('title', 'El Valor F.O.B. U$S se puede corregir después de creado el contenedor');
        $('#avisoFobEditable, #ayudaFobEditable').show();

        // Secciones 2 y 3 editables
        $('#fechaEmb, #numeroBl, #factura, #fechaArr').prop('readonly', false).removeClass('campo-readonly');
        $('#tipoCambio, #formaPago, #fechaPago, #fechaDespAdu, #despacho').prop('readonly', false).removeClass('campo-readonly');
        
    } else {
        // MODO ALTA INICIAL: Sección 1 obligatoria y editable
        $('#proveedor').prop('disabled', false).removeClass('campo-readonly');
        $('#contenedor, #material, #origen, #fechaEstEmb, #valorFobDolar').prop('readonly', false).removeClass('campo-readonly');
        // En el alta TODA la sección es editable, así que destacar el FOB no
        // distinguiría nada: la marca sólo tiene sentido contra campos grises.
        $('#valorFobDolar').removeClass('campo-editable-excepcion').removeAttr('title');
        $('#avisoFobEditable, #ayudaFobEditable').hide();
        // Para select2, habilitar el select y el contenedor
        $('#despachante').prop('disabled', false).removeClass('campo-readonly');
        $('#despachante').next('.select-dropdown').find('.select2-container').css({
            'pointer-events': 'auto',
            'opacity': '1',
            'background-color': ''
        });
        $('#btnAddOrdenCompra').prop('disabled', false).css('opacity', '1');
        
        // Secciones 2 y 3 visibles pero no editables (se calculan automáticamente)
        $('#fechaEmb, #numeroBl, #factura').prop('readonly', true).addClass('campo-readonly');
        $('#tipoCambio, #fechaEstPago, #fechaDespAdu, #despacho').prop('readonly', true).addClass('campo-readonly');
        $('#fechaArr').prop('readonly', true); // Este siempre es calculado inicialmente
    }
}

/**
 * Valida campos obligatorios de Sección 1 en alta inicial
 */
function validarSeccion1() {
    const proveedor = $('#proveedor').val();
    const ordenProveedor = $('#contenedor').val();
    const material = $('#material').val();
    const origen = $('#origen').val();
    const valorFobDolar = $('#valorFobDolar').val();
    const fechaEstEmb = $('#fechaEstEmb').val();
    const ordenesSeleccionadas = $('#ordenesSeleccionadas').children().length;
    
    console.log('Validando Sección 1:', {
        proveedor, ordenProveedor, material, origen, valorFobDolar, fechaEstEmb, ordenesSeleccionadas
    });
    
    if (!proveedor || proveedor === 'PROVEEDOR') {
        Swal.fire({icon: 'error', title: 'Error', text: 'Debe seleccionar un proveedor', confirmButtonColor: '#3085d6'});
        return false;
    }
    
    if (!ordenProveedor || ordenProveedor.trim() === '') {
        Swal.fire({icon: 'error', title: 'Error', text: 'Debe ingresar el número de orden del proveedor', confirmButtonColor: '#3085d6'});
        return false;
    }
    
    if (!material || material.trim() === '') {
        Swal.fire({icon: 'error', title: 'Error', text: 'Debe ingresar el material', confirmButtonColor: '#3085d6'});
        return false;
    }
    
    if (!origen || origen.trim() === '') {
        Swal.fire({icon: 'error', title: 'Error', text: 'Debe ingresar el origen', confirmButtonColor: '#3085d6'});
        return false;
    }
    
    if (!valorFobDolar || valorFobDolar.trim() === '') {
        Swal.fire({icon: 'error', title: 'Error', text: 'Debe ingresar el valor FOB en dólares', confirmButtonColor: '#3085d6'});
        return false;
    }
    
    if (!fechaEstEmb || fechaEstEmb.trim() === '') {
        Swal.fire({icon: 'error', title: 'Error', text: 'Debe ingresar la fecha estimada de embarque', confirmButtonColor: '#3085d6'});
        return false;
    }
    
    if (ordenesSeleccionadas === 0 && !$('#ordenManual').is(':checked')) {
        Swal.fire({icon: 'error', title: 'Error', text: 'Debe agregar al menos una orden de compra o marcar orden manual', confirmButtonColor: '#3085d6'});
        return false;
    }
    
    return true;
}

// ========== GESTIÓN DE ÓRDENES DE COMPRA ==========

/**
 * Añadir orden de compra al contenedor visual
 */
function agregarOrdenAlContenedor(orden) {
    const modoEdicion = $('#modoEdicion').val() === 'true';
    const div = document.createElement('div');
    div.className = 'orden-de-compra-item';
    div.id = 'ordenDeCompra';
    
    // Asegurarse de que el texto de la orden sea limpio
    const ordenLimpia = orden.trim();
    
    // En modo edición, no mostrar botón de eliminar
    if (modoEdicion) {
        div.innerHTML = `
            <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                  class="nroOrdenSpan">${ordenLimpia}</span>
        `;
    } else {
        div.innerHTML = `
            <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                  class="nroOrdenSpan">${ordenLimpia}</span>
            <button class="btn-delete" data-orden="${ordenLimpia}">
                <i class="bi bi-x-circle" style="color:white;"></i>
            </button>
        `;
    }
    
    const contenedor = document.querySelector("#ordenesSeleccionadas");
    contenedor.appendChild(div);
    
    // Solo configurar botón de eliminación si NO estamos en modo edición
    if (!modoEdicion) {
        const deleteButton = div.querySelector('.btn-delete');
        deleteButton.addEventListener('click', function(e) {
            e.stopPropagation();
            const orden = this.getAttribute('data-orden');
            
            // Animación de eliminación
            const parentDiv = this.parentNode;
            parentDiv.style.transform = 'scale(0.8)';
            parentDiv.style.opacity = '0';
            
            setTimeout(() => {
                parentDiv.remove();
                
                // Notificación toast
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'bottom-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
                
                Toast.fire({
                    icon: 'success',
                    title: `Orden ${orden} eliminada`
                });
            }, 300);
        });
    }
}
/**
 * Función para traer orden manual
 */
const traerOrden = () => {
    let ordenManual = document.querySelector("#ordenManual");
    let ordenesSeleccionadas = document.querySelector("#ordenesSeleccionadas");
    
    if(ordenManual.checked == true) {
        // Mostrar un indicador de carga
        ordenesSeleccionadas.innerHTML = `
            <div class="text-center p-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
            </div>
        `;
        
        $.ajax({
            url: '../controller/traerOrdenManualController.php',
            method: 'GET',
            success: function(data) {
                ordenesSeleccionadas.innerHTML = '';

                let num = JSON.parse(data);
                
                if(num['nroOrden'] == null) {
                    num['nroOrden'] = 0;
                }

                let sumaOrden = 200000000 + num['nroOrden'];
                let orden = `0000${sumaOrden}`;
                
                const div = document.createElement('div');
                div.id = 'ordenDeCompra';
                div.innerHTML = `<span class="nroOrdenSpan">${orden}</span>`;
                
                // Añadir el elemento con animación
                div.style.opacity = '0';
                div.style.transform = 'translateY(10px)';
                ordenesSeleccionadas.appendChild(div);
                
                setTimeout(() => {
                    div.style.transition = 'all 0.3s ease';
                    div.style.opacity = '1';
                    div.style.transform = 'translateY(0)';
                }, 10);

                // Notificación toast
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
                
                Toast.fire({
                    icon: 'success',
                    title: 'Orden manual generada correctamente'
                });
            },
            error: function() {
                ordenesSeleccionadas.innerHTML = '';
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo generar la orden manual',
                    confirmButtonColor: '#3085d6'
                });
            }
        });
    } else {
        // Eliminar con animación
        const elementos = ordenesSeleccionadas.querySelectorAll('#ordenDeCompra');
        
        elementos.forEach(elem => {
            elem.style.transition = 'all 0.3s ease';
            elem.style.opacity = '0';
            elem.style.transform = 'scale(0.8)';
        });
        
        setTimeout(() => {
            ordenesSeleccionadas.innerHTML = '';
        }, 300);
    }
}

/**
 * Inicializa todos los datepickers del formulario
 */
function inicializarDatepickers() {
    console.log('=== Inicializando Datepickers ===');
    
    // Fecha Estimada de Embarque
    $('.js-datepicker-estimada').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        autoApply: true,
        locale: {format: 'DD/MM/YYYY'}
    });
    
    // Fecha de Embarque Real (ETD)
    $('.js-datepicker-etd').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        autoApply: true,
        autoUpdateInput: false,
        locale: {format: 'DD/MM/YYYY'}
    }).on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('DD/MM/YYYY'));
        console.log('Fecha ETD seleccionada:', picker.startDate.format('DD/MM/YYYY'));
        recalcularTodasLasFechas();
    });
    
    // Fecha de Arribo (ETA)
    $('.js-datepicker-arribo').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        autoApply: true,
        locale: {format: 'DD/MM/YYYY'}
    }).on('apply.daterangepicker', function(ev, picker) {
        /* EDITAR LA ETA A MANO ES CONFIRMARLA. Reemplaza al checkbox "ETA
           Confirmada", que pedía dos gestos para una sola decisión y se
           olvidaba: quien corrige la ETA con la fecha que le pasó la naviera ya
           está afirmando que esa fecha es en firme.

           LA GUARDA DE cargandoDatos ES LO QUE HACE QUE ESTO SEA SEGURO, y es
           el mismo patrón que el datepicker de est. pago. NO se puede usar
           fechaArriboIsManual: ese flag se enciende al CARGAR los datos de la
           base -ver cargarDatosDespacho()- así que no distingue "el usuario la
           editó" de "vino así", y colgarse de él marcaría como confirmada toda
           ETA existente en el primer guardado. */
        if (!cargandoDatos) {
            etaConfirmada = true;
            aplicarEstiloEtaConfirmada();
            console.log('ETA editada a mano - queda CONFIRMADA');
        }
        setTimeout(() => validarCampoFechaHabil($(this)), 100);
    });
    
    // Fecha de Pago
    $('.js-datepicker-pago').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        autoApply: true,
        locale: {format: 'DD/MM/YYYY'}
    });

    // Fecha de pago nuevo (modal agregar pago)
    $('.js-datepicker-nuevo-pago').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        autoApply: true,
        locale: {format: 'DD/MM/YYYY'}
    }).on('apply.daterangepicker', function() {
        setTimeout(() => validarCampoFechaHabil($(this)), 100);
    });
    
    // Fecha de Nacionalización (Despacho)
    $('.js-datepicker-despacho').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        autoApply: true,
        locale: {format: 'DD/MM/YYYY'}
    }).on('apply.daterangepicker', function(ev, picker) {
        setTimeout(() => validarCampoFechaHabil($(this)), 100);
    });
    
    // Fecha Estimada de Pago
    console.log('Inicializando datepicker para .js-datepicker-est-pago');
    $('.js-datepicker-est-pago').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        autoApply: true,
        locale: {format: 'DD/MM/YYYY'}
    }).on('apply.daterangepicker', function(ev, picker) {
        console.log('Evento apply.daterangepicker en fechaEstPago');
        /* La guarda de cargandoDatos es lo que separa "el usuario eligió una
           fecha" de "el datepicker se sincronizó con lo que vino de la base".
           Sin ella, abrir la pantalla marcaría todo como manual. */
        if (!cargandoDatos) {
            fechaEstPagoIsManual = true;
            /* La fecha todavía no se guardó: el usuario y la hora reales los
               pone el backend al confirmar. Se limpian para no mostrar en el
               tooltip los datos de la edición anterior, que ya no describen
               esta fecha. */
            fechaEstPagoConfUsuario = null;
            fechaEstPagoConfFecha = null;
            actualizarBadgeFechaEstPago();
            console.log('Fecha Est. Pago - Manual Override ACTIVADO');
        }
        setTimeout(() => validarCampoFechaHabil($(this)), 100);
    });
    
    // Event listeners para cambios en las fechas base de embarque
    $(document).on('apply.daterangepicker', '.js-datepicker-estimada', function(ev, picker) {
        console.log('FECHA_EST_EMB cambió - pidiendo la cadena de fechas');
        recalcularTodasLasFechas();
    });

    /* El ETD recalcula la cadena, PERO NO DESCARTA LA DECISIÓN DEL USUARIO:
       acá había un `fechaEstPagoIsManual = false` que hacía que poner la fecha
       a mano y después tocar el ETD la pisara dentro de la misma sesión. Ahora
       lo que corta es aplicarCadenaFechas(), que no escribe ningún campo
       fijado. Para volver al automático está el botón "volver a auto", que es
       explícito. */
    $(document).on('apply.daterangepicker', '.js-datepicker-etd', function(ev, picker) {
        console.log('FECHA_EMB (ETD) cambió - pidiendo la cadena de fechas');
        recalcularTodasLasFechas();
    });
    
    // Event listener para cerrar alerta informativa
    $(document).on('click', '.alerta-fecha-no-habil', function() {
        const campoId = $(this).data('campo');
        const $campo = $('#' + campoId);
        ocultarAdvertenciaFecha($campo);
    });
    
    console.log('=== Datepickers Inicializados ===');
}


// ========== OC CHIPS: NAVEGACIÓN ENTRE VINCULADAS ==========

$(document).on('click', '.oc-chip:not(.oc-chip-actual)', function() {
    const idOC        = $(this).data('id-oc');
    const esPrincipal = $(this).data('es-principal') == 1;

    if (esPrincipal) {
        window.location.href = 'cargaInicial.php?id=' + idOC + '&modo=edicion';
    } else {
        Swal.fire({
            title: 'OC vinculada',
            html: 'Esta OC está vinculada a la principal. ' +
                  '<strong>Las modificaciones se hacen desde la OC principal.</strong><br><br>' +
                  '¿Cómo querés abrirla?',
            icon: 'info',
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: '<i class="bi bi-arrow-left-circle"></i> Pasar a la principal',
            denyButtonText:    '<i class="bi bi-eye"></i> Ver en solo lectura',
            cancelButtonText:  'Cancelar',
            confirmButtonColor: '#0d6efd',
            denyButtonColor:    '#6c757d'
        }).then(result => {
            if (result.isConfirmed) {
                const idPrincipal = $('#idPrincipalGrupo').val();
                window.location.href = 'cargaInicial.php?id=' + idPrincipal + '&modo=edicion';
            } else if (result.isDenied) {
                window.location.href = 'cargaInicial.php?id=' + idOC + '&modo=lectura';
            }
        });
    }
});

$(document).ready(function() {
    // Verificar si estamos en modo edición
    const modoEdicion = $('#modoEdicion').val() === 'true';

    // ── Modo solo lectura (hija en modo=lectura) ──────────────────────────────
    if ($('#esLectura').val() === '1') {
        $('input, select, textarea').not('[type="hidden"]').prop('disabled', true);
        $('#btnSave, #btnAgregarPago, #btnAgregarOrden, #btnAddOrdenCompra, ' +
          '#guardarNuevoPagoBtn, #btnSaveDetalle').hide();
        $('#tbodyPagos').on('click', 'a.link-primary', function(e) {
            e.preventDefault();
        });
    }
    // ─────────────────────────────────────────────────────────────────────────

    // PRIMERO: Inicializar todos los datepickers ANTES de cargar datos
    inicializarDatepickers();
    
    // ── Aviso: redirigido de hija a principal ────────────────────────────────
    // La variable mostrarAvisoRedirect la inyecta PHP cuando detecta que el
    // id recibido era de una OC hija y cargamos el principal en su lugar.
    if (typeof mostrarAvisoRedirect !== 'undefined' && mostrarAvisoRedirect) {
        Swal.fire({
            title: 'Te llevamos a la OC principal',
            html: 'La OC que ingresaste está vinculada a otra del mismo contenedor. ' +
                  'Te abrimos la <strong>OC principal</strong> para que puedas editar los datos comunes.<br><br>' +
                  'Las modificaciones se replican automáticamente a las OCs vinculadas.',
            icon: 'info',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#0d6efd'
        });
    }
    // ─────────────────────────────────────────────────────────────────────────

    // Detectar parámetros URL o sessionStorage para preselección desde OC Pendientes
    const urlParams = new URLSearchParams(window.location.search);
    let proveedorParam = urlParams.get('proveedor');
    let ordenCompraParam = urlParams.get('ordenCompra');
    
    // Si no vienen por URL, intentar leer de sessionStorage
    if (!proveedorParam && sessionStorage.getItem('ocPendiente_proveedor')) {
        proveedorParam = sessionStorage.getItem('ocPendiente_proveedor');
        ordenCompraParam = sessionStorage.getItem('ocPendiente_ordenCompra');
        
        // Limpiar sessionStorage después de leer
        sessionStorage.removeItem('ocPendiente_proveedor');
        sessionStorage.removeItem('ocPendiente_ordenCompra');
        
        console.log('Datos de OC pendiente recuperados de sessionStorage:', proveedorParam, ordenCompraParam);
    }
    
    // LUEGO: Cargar datos si estamos en modo edición
    if (modoEdicion && typeof datosDespacho !== 'undefined' && datosDespacho) {
        // MODO EDICIÓN - Cargar datos existentes
        cargarDatosDespacho(datosDespacho);
        establecerModoFormulario(true);
    } else {
        // MODO ALTA - Establecer modo inicial
        establecerModoFormulario(false);
        
        // Si vienen parámetros de URL o sessionStorage, preseleccionar proveedor y OC
        if (proveedorParam && ordenCompraParam && !modoEdicion) {
            preseleccionarProveedorYOrden(proveedorParam, ordenCompraParam);
        }
    }
    
    // IMPORTANTE: Siempre limpiar campo ETD al inicio (en modo alta debe estar vacío)
    if (!modoEdicion) {
        $('#fechaEmb').val('');
        console.log('Campo FECHA_EMB limpiado en modo alta');
    }
    
    // Configurar manual override
    configurarManualOverride();
    
    // Ejecutar cálculos iniciales solo si NO estamos cargando datos de BD
    if (!cargandoDatos) {
        recalcularTodasLasFechas();
    }
    recalcularFobPesos();
    
    // Event listeners para recálculos automáticos de fechas
    
    // Cuando cambia Fecha Estimada de Embarque (FECHA_EST_EMB)
    $('#fechaEstEmb').on('dp.change', function() {
        recalcularTodasLasFechas();
    });
    
    $('#fechaEstEmb').on('change', function() {
        recalcularTodasLasFechas();
    });
    
    // Cuando cambia Fecha de Embarque real ETD (FECHA_EMB) - PRIORIDAD MÁXIMA
    $('#fechaEmb').on('dp.change', function() {
        // Al cambiar la fecha real, recalcular TODOS los campos que dependan
        // y que NO estén en modo manual
        recalcularTodasLasFechas();
    });
    
    $('#fechaEmb').on('change', function() {
        recalcularTodasLasFechas();
    });
    
    // Event listeners para cálculo de FOB en Pesos
    $('#valorFobDolar').on('input change', function() {
        recalcularFobPesos();
    });
    
    // Event listener para botón Agregar Pago
    $('#btnAgregarPago').on('click', function() {
        // Limpiar campos del modal
        $('#fechaPagoNuevo').val('');
        $('#formaPagoNuevo').val('');
        $('#medioPagoNuevo').val('');
        $('#montoNuevo').val('');

        // Inicializar header informativo del modal. Todo en U$S: es la
        // moneda en la que se le paga al proveedor del exterior.
        const fobTotal = obtenerFOBDolar();
        const saldoActual = obtenerSaldoPendiente();
        $('#modalFobTotal').text('U$S ' + formatearMonedaUI(fobTotal));
        $('#modalSaldoActual').text('U$S ' + formatearMonedaUI(saldoActual));
        $('#modalNuevoSaldo').text('U$S ' + formatearMonedaUI(saldoActual));
        $('#modalNuevoSaldoContainer').removeClass('text-danger');
        $('#guardarNuevoPagoBtn').prop('disabled', false);

        // Mostrar modal
        const modal = new bootstrap.Modal(document.getElementById('modalAgregarPago'));
        modal.show();
    });

    // Cálculo en vivo del nuevo saldo mientras el usuario tipea el monto
    $(document).on('input', '#montoNuevo', function() {
        const monto = parseFloat($(this).val()) || 0;
        const saldoActual = obtenerSaldoPendiente();
        const nuevoSaldo = saldoActual - monto;
        const TOLERANCIA = 0.01;

        $('#modalNuevoSaldo').text('U$S ' + formatearMonedaUI(nuevoSaldo));

        if (nuevoSaldo < -TOLERANCIA) {
            $('#modalNuevoSaldoContainer').addClass('text-danger');
            $('#guardarNuevoPagoBtn').prop('disabled', true);
        } else {
            $('#modalNuevoSaldoContainer').removeClass('text-danger');
            $('#guardarNuevoPagoBtn').prop('disabled', false);
        }
    });
    
    $('#tipoCambio').on('input change', function() {
        recalcularFobPesos();
    });

    // Evento para cargar órdenes cuando se selecciona un proveedor
$('#proveedor').on('change', function() {
    const proveedorSeleccionado = $(this).val();
    
    if (proveedorSeleccionado && proveedorSeleccionado !== 'PROVEEDOR') {
        cargarOrdenesPorProveedor(proveedorSeleccionado);
    } else {
        // Limpiar órdenes si no hay proveedor seleccionado
        localStorage.removeItem('ordenes');
    }
});

// Función para cargar órdenes por proveedor
function cargarOrdenesPorProveedor(codProveedor) {
    console.log('Cargando órdenes para proveedor:', codProveedor);
    
    $.ajax({
        url: '../controller/traerOrdenesController.php',
        method: 'GET',
        data: { proveedor: codProveedor },
        dataType: 'json',
        success: function(response) {
            console.log('Órdenes cargadas:', response);
            
            if (response && Array.isArray(response)) {
                // Guardar en localStorage para usar en el modal
                localStorage.setItem('ordenes', JSON.stringify(response));
                
                // Mostrar notificación de éxito
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
                
                Toast.fire({
                    icon: 'success',
                    title: `${response.length} órdenes cargadas`
                });
            } else {
                console.error('Respuesta no válida:', response);
                localStorage.removeItem('ordenes');
            }
        },
        error: function(error) {
            console.error('Error al cargar órdenes:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudieron cargar las órdenes del proveedor',
                confirmButtonColor: '#3085d6'
            });
            localStorage.removeItem('ordenes');
        }
    });
}
    // ========== MODAL PARA AGREGAR ORDEN DE COMPRA ==========
$('#btnAddOrdenCompra').on('click', function() {
    // Validaciones iniciales
    const proveedorValor = $('#proveedor').val();
    const modoEdicion = $('#modoEdicion').val() === 'true';
    
    if(!proveedorValor || proveedorValor === 'PROVEEDOR') {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Debes seleccionar un proveedor primero',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'Entendido'
        });
        return;
    }

    if(document.querySelector("#ordenManual").checked) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No puedes agregar órdenes de compra si seleccionaste orden manual',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'Entendido'
        });
        return;
    }

    // Obtener órdenes del localStorage
    let ordenesStorage = localStorage.getItem('ordenes');
    let ordenes = [];
    
    try {
        if (ordenesStorage) {
            ordenes = JSON.parse(ordenesStorage);
        }
    } catch (e) {
        console.error('Error al parsear órdenes:', e);
        localStorage.removeItem('ordenes');
    }
    
    // Obtener órdenes ya seleccionadas
    let ordenesSeleccionadas = document.querySelectorAll("#ordenDeCompra");
    let ordenesSeleccionadasArray = Array.from(ordenesSeleccionadas).map(el => {
        const span = el.querySelector('.nroOrdenSpan');
        return span ? span.textContent.trim() : el.textContent.trim().replace('×', '').trim();
    });
    
    // Preparar opciones del select
    let selectOptions = '';
    
    if (ordenes && Array.isArray(ordenes) && ordenes.length > 0) {
        // Filtrar órdenes para excluir las ya seleccionadas
        let ordenesFiltradas = ordenes.filter(orden => {
            const nroOrden = orden.N_ORDEN_CO ? orden.N_ORDEN_CO.trim() : '';
            return nroOrden && !ordenesSeleccionadasArray.includes(nroOrden);
        });
        
        if (ordenesFiltradas.length === 0) {
            Swal.fire({
                icon: 'info',
                title: 'Información',
                text: 'No hay más órdenes disponibles para seleccionar',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Aceptar'
            });
            return;
        }
        
        ordenesFiltradas.forEach(function(orden) {
            const nroOrden = orden.N_ORDEN_CO ? orden.N_ORDEN_CO.trim() : '';
            if (nroOrden) {
                selectOptions += `<option value="${nroOrden}">${nroOrden}</option>`;
            }
        });
    } else {
        // Si no hay órdenes en localStorage, intentar cargarlas nuevamente
        Swal.fire({
            title: 'Cargando órdenes...',
            text: 'Por favor espera',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
                
                $.ajax({
                    url: '../controller/traerOrdenesController.php',
                    method: 'GET',
                    data: { proveedor: proveedorValor },
                    dataType: 'json',
                    success: function(response) {
                        Swal.close();
                        
                        if (response && Array.isArray(response) && response.length > 0) {
                            localStorage.setItem('ordenes', JSON.stringify(response));
                            // Volver a abrir el modal con las órdenes cargadas
                            setTimeout(() => $('#btnAddOrdenCompra').click(), 100);
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Sin datos',
                                text: 'No se encontraron órdenes de compra disponibles para este proveedor',
                                confirmButtonColor: '#3085d6',
                                confirmButtonText: 'Aceptar'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudieron cargar las órdenes. Intenta nuevamente.',
                            confirmButtonColor: '#3085d6',
                            confirmButtonText: 'Aceptar'
                        });
                    }
                });
            }
        });
        return;
    }

    // Crear HTML personalizado para el modal
    const modalHTML = `
        <div class="modal-orden-compra">
            <p class="modal-subtitle">Selecciona una o varias órdenes de compra</p>
            <div class="select-container">
                <select id="ordenCompra" class="swal2-select custom-select" multiple style="min-height: 200px; width: 100%;">
                    ${selectOptions}
                </select>
            </div>
            <div id="seleccionPrevia" class="seleccion-previa mt-3"></div>
            <div class="form-hint mt-2">
                <small><i class="bi bi-info-circle"></i> Mantén presionada la tecla Ctrl (Cmd en Mac) para seleccionar múltiples órdenes</small>
            </div>
        </div>
    `;

    // Mostrar modal mejorado
    let selectedOptions = [];
    Swal.fire({
        title: 'Añadir Orden de Compra',
        html: modalHTML,
        showCancelButton: true,
        confirmButtonText: '<i class="bi bi-check-circle"></i> Guardar',
        cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar',
        confirmButtonColor: '#7066e0',
        cancelButtonColor: '#6c757d',
        focusConfirm: false,
        width: '600px',
        didOpen: () => {
            // Establecer tamaño del select
            const selectElement = document.getElementById('ordenCompra');
            selectElement.style.height = '200px';
            
            // Actualizar vista previa cuando se seleccionan opciones
            selectElement.addEventListener('change', () => {
                const seleccionPrevia = document.getElementById('seleccionPrevia');
                seleccionPrevia.innerHTML = '';
                
                const selectedOptions = Array.from(selectElement.selectedOptions);
                
                if (selectedOptions.length === 0) {
                    seleccionPrevia.innerHTML = '<div class="text-muted">No hay órdenes seleccionadas</div>';
                    return;
                }
                
                selectedOptions.forEach(option => {
                    const ordenItem = document.createElement('div');
                    ordenItem.className = 'orden-item mb-2 p-2 bg-light rounded';
                    ordenItem.innerHTML = `
                        <span class="orden-item-text">${option.value}</span>
                    `;
                    seleccionPrevia.appendChild(ordenItem);
                });
            });
        },
        preConfirm: () => {
            selectedOptions = Array.from(
                Swal.getPopup().querySelectorAll('.swal2-select option:checked'),
                option => option.value
            );
            
            if (selectedOptions.length === 0) {
                Swal.showValidationMessage('Por favor selecciona al menos una orden de compra');
                return false;
            }
            
            // Añadir órdenes seleccionadas al contenedor
            selectedOptions.forEach(function(orden) {
                agregarOrdenAlContenedor(orden);
            });
            
            return true;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar notificación de éxito
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
            
            Toast.fire({
                icon: 'success',
                title: `${selectedOptions.length} orden(es) agregada(s) correctamente`
            });
        }
    });
});

    console.log('cargaInicial.js cargado correctamente');
});

// ========== FUNCIÓN DE GUARDADO ==========

/**
 * Guarda los datos del despacho en la base de datos
 */
function guardarCabecera() {
    // Si es modo alta inicial, validar solo Sección 1
    const modoEdicion = document.getElementById('modoEdicion').value === 'true';
    
    if (!modoEdicion) {
        // Validar campos obligatorios de Sección 1
        if (!validarSeccion1()) {
            return;
        }
    }

    // --- FUNCIÓN DE LIMPIEZA CLAVE PARA EVITAR ERRORES DE MONEDA ---
    const limpiarParaEnviar = (valor) => {
        if (!valor) return '0';
        let str = valor.toString();
        
        // 1. Quitar $ y espacios
        str = str.replace('$', '').replace(/\s/g, '');
        
        // 2. Detección de formato para limpiar correctamente
        if (str.includes(',')) {
            // Caso Argentina: 29.972.337,50
            str = str.replace(/\./g, ''); // Borrar puntos de mil (esto arregla el error 29.97)
            str = str.replace(',', '.');  // Cambiar coma por punto
        } 
        
        return str;
    };

    // Mostrar confirmación antes de procesar
    Swal.fire({
        title: '¿Desea guardar los cambios?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#7066e0',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, guardar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        // Capturar datos del formulario
        const cod_proveedor = $('#proveedor').val();
        const proveedor = $('#proveedor option:selected').text();
        const contenedor = $('#contenedor').val();
        const material = $('#material').val();
        const origen = $('#origen').val();
        const fechaEstEmb = $('#fechaEstEmb').val();
        
        // Órdenes de compra
        let ocm = $('#ordenManual').is(':checked') ? 1 : 0;
        let ordenCompra = [];
        $('.nroOrdenSpan').each(function() {
            ordenCompra.push($(this).text().trim().split(" ")[0]);
        });
        console.log('[guardarCabecera] ordenCompra recolectadas:', ordenCompra);
        
        // Sección 2 - Datos de Embarque
        const fechaEmb = $('#fechaEmb').val();
        const numeroBl = $('#numeroBl').val();
        const factura = $('#factura').val();
        const fechaArr = $('#fechaArr').val();
        
        // Sección 3 - Datos Financieros y Aduana
        const formaPago = $('#formaPago').val();
        const fechaDespAdu = $('#fechaDespAdu').val();
        const despacho = $('#despacho').val();
 
        // Preparar datos para enviar
        const dataToSend = {
            // Si es edición, incluir ID
            id: modoEdicion ? $('#idDespacho').val() : null,
            modoEdicion: modoEdicion,
            
            // Sección 1 - Datos Iniciales
            cod_proveedor: cod_proveedor,
            proveedor: proveedor,
            contenedor: contenedor,
            material: material,
            origen: origen,
            
            // LIMPIEZA DE NÚMEROS
            valorFobDolar: limpiarParaEnviar($('#valorFobDolar').val()),
            
            fechaEstEmb: fechaEstEmb,
            ordenCompra: JSON.stringify(ordenCompra),
            ocm: ocm,
            despachante: $('#despachante').val() || 'Laffitte', 
            
            /* Campos calculados automáticamente. fechaPago ya no viaja: el
               input no existía y FECHA_PAGO tampoco es columna del maestro, así
               que insertarEncabezado.php le armaba a la UPDATE un
               "FECHA_PAGO = ..." contra una columna inexistente cada vez que
               llegaba con valor. */
            fechaArr: fechaArr,
            fechaDespAdu: fechaDespAdu,

            /* "Esta nacionalización la fijó el usuario". Mismo trato que
               fechaEstPagoManual: el backend no le cree solo, además compara
               contra el maestro -Encabezado::marcarFechaFijada()-. Va igual
               porque es lo único que distingue esta edición de un recálculo:
               los dos mandan un fechaDespAdu distinto del guardado. */
            fechaDespAduManual: fechaDespachoIsManual ? 1 : 0,
            
            // Sección 2 - Datos de Embarque (solo enviar en modo edición)
            fechaEmb: (modoEdicion && fechaEmb) ? fechaEmb : '',
            numeroBl: numeroBl,
            factura: factura,
            puertoOrigen: $('#puertoOrigen').val(),
            terminal: $('#terminal').val(),
            
            /* ETA en firme. Ya no sale de un checkbox: se enciende al editar la
               ETA a mano y se arrastra tal cual si venía confirmada de antes. */
            eta_confirmada: etaConfirmada ? 1 : 0,
            
            // Sección 3 - Datos Financieros y Aduana
            fechaEstPago: $('#fechaEstPago').val(),

            /* "Esta fecha la fijó el usuario". El backend NO le cree solo: además
               compara contra lo que tiene el maestro y no marca nada si el valor
               no cambió -Encabezado::marcarFechaPagoFijada()-. Va igual porque es
               lo único que distingue esta edición de un recálculo automático:
               los dos mandan un fechaEstPago distinto del guardado. */
            fechaEstPagoManual: fechaEstPagoIsManual ? 1 : 0,
            
            // LIMPIEZA DE NÚMEROS (Aquí solucionamos el problema del valor gigante o cortado)
            tipoCambio: limpiarParaEnviar($('#tipoCambio').val()),
            valorFobPeso: limpiarParaEnviar($('#valorFobPeso').val()),
            
            formaPago: formaPago,
            despacho: despacho
        };
        
        console.log('Datos a enviar:', dataToSend);
        
        // Enviar datos al servidor
        $.ajax({
            url: '../controller/insertarEncabezado.php',
            method: 'POST',
            dataType: 'json',
            data: dataToSend,
            success: function(response) {
                console.log('Respuesta del servidor:', response);
                
                if (response.success) {
                    Swal.fire({
                        title: '¡Despacho guardado correctamente!',
                        text: response.message || 'Los datos han sido guardados exitosamente',
                        icon: 'success',
                        confirmButtonText: 'Aceptar',
                        confirmButtonColor: '#7066e0'
                    }).then(function () {
                        // Si estamos en modo edición, regresar a la lista de pendientes
                        if (modoEdicion) {
                            window.location.href = 'gestionDespachos.php';
                        } else {
                            window.location.reload();
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Error al guardar',
                        text: response.message || 'Ocurrió un error al guardar el despacho',
                        icon: 'error',
                        confirmButtonText: 'Aceptar',
                        confirmButtonColor: '#d33'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al guardar:', error);
                
                let errorMessage = 'Error al conectar con el servidor';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMessage = response.message || errorMessage;
                } catch (e) {
                    errorMessage = xhr.responseText || errorMessage;
                }
                
                Swal.fire({
                    title: 'Error',
                    text: errorMessage,
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#d33'
                });
            }
        });
    });
}

/**
 * Elimina un pago del despacho
 */
function eliminarPago(idPago) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: 'Se eliminará este registro de pago. Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../controller/eliminarPago.php',
                method: 'POST',
                data: { id_pago: idPago },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Eliminado', 'El pago ha sido eliminado correctamente', 'success');
                        // Recargar pagos
                        const idDespacho = $('#idDespacho').val();
                        cargarPagos(idDespacho);
                    } else {
                        Swal.fire('Error', response.message || 'No se pudo eliminar el pago', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error al eliminar:', error);
                    Swal.fire('Error', 'Error al conectar con el servidor', 'error');
                }
            });
        }
    });
}

// ========== FUNCIONES PARA PRESELECCIÓN DESDE OC PENDIENTES ==========

/**
 * Preselecciona el proveedor y la orden de compra cuando vienen por parámetros URL
 * Esta función se ejecuta cuando se crea un despacho desde el modal de OC Pendientes
 */
function preseleccionarProveedorYOrden(codProveedor, nOrdenCo) {
    console.log('Preseleccionando proveedor y OC:', codProveedor, nOrdenCo);
    
    // Mostrar notificación de carga
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true
    });
    
    Toast.fire({
        icon: 'info',
        title: 'Cargando datos de la orden...'
    });
    
    // Preseleccionar el proveedor en el select
    const $selectProveedor = $('#proveedor');
    $selectProveedor.val(codProveedor);
    
    // Disparar el evento change para cargar las órdenes
    $selectProveedor.trigger('change');
    
    // Esperar un momento para que se carguen las órdenes y luego agregar la OC
    setTimeout(function() {
        agregarOrdenPrecarga(nOrdenCo);
    }, 1500);
}

/**
 * Agrega automáticamente una orden de compra específica al contenedor
 * Esta función se usa después de preseleccionar un proveedor
 */
function agregarOrdenPrecarga(nOrdenCo) {
    console.log('Agregando orden precargada:', nOrdenCo);
    
    // Verificar que las órdenes estén cargadas en localStorage
    const ordenesStorage = localStorage.getItem('ordenes');
    if (!ordenesStorage) {
        console.warn('No se encontraron órdenes en localStorage');
        Swal.fire({
            icon: 'warning',
            title: 'Atención',
            text: 'Por favor, vuelve a seleccionar el proveedor para cargar las órdenes',
            confirmButtonColor: '#7066e0'
        });
        return;
    }
    
    let ordenes = [];
    try {
        ordenes = JSON.parse(ordenesStorage);
    } catch (e) {
        console.error('Error al parsear órdenes:', e);
        return;
    }
    
    // Verificar que la orden exista en la lista
    const ordenEncontrada = ordenes.find(orden => orden.N_ORDEN_CO && orden.N_ORDEN_CO.trim() === nOrdenCo.trim());
    
    if (!ordenEncontrada) {
        console.warn('La orden no se encontró en la lista del proveedor:', nOrdenCo);
        Swal.fire({
            icon: 'warning',
            title: 'Atención',
            text: `La orden ${nOrdenCo} no se encontró en la lista del proveedor seleccionado`,
            confirmButtonColor: '#7066e0'
        });
        return;
    }
    
    // Crear el chip/badge de la orden y agregarlo al contenedor
    const ordenesSeleccionadas = document.querySelector('#ordenesSeleccionadas');
    if (!ordenesSeleccionadas) {
        console.error('No se encontró el contenedor #ordenesSeleccionadas');
        return;
    }
    
    // Verificar que no esté ya agregada
    const yaExiste = Array.from(ordenesSeleccionadas.querySelectorAll('#ordenDeCompra')).some(el => {
        const span = el.querySelector('.nroOrdenSpan');
        const texto = span ? span.textContent.trim() : el.textContent.trim().replace('×', '').trim();
        return texto === nOrdenCo.trim();
    });
    
    if (yaExiste) {
        console.log('La orden ya está agregada');
        return;
    }
    
    // Crear el elemento de orden de compra (chip)
    const div = document.createElement('div');
    div.className = 'badge bg-primary me-2 mb-2 d-inline-flex align-items-center';
    div.id = 'ordenDeCompra';
    div.style.fontSize = '0.9rem';
    div.style.padding = '0.5rem 0.75rem';
    
    const span = document.createElement('span');
    span.className = 'nroOrdenSpan';
    span.textContent = nOrdenCo.trim();
    
    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'btn-close btn-close-white ms-2';
    closeBtn.style.fontSize = '0.7rem';
    closeBtn.setAttribute('aria-label', 'Eliminar');
    closeBtn.onclick = function() {
        div.remove();
    };
    
    div.appendChild(span);
    div.appendChild(closeBtn);
    ordenesSeleccionadas.appendChild(div);
    
    // Mostrar notificación de éxito
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true
    });
    
    Toast.fire({
        icon: 'success',
        title: `Orden ${nOrdenCo} agregada correctamente`
    });
    
    console.log('Orden agregada exitosamente:', nOrdenCo);
}

/**
 * Guarda un nuevo pago
 */
function guardarNuevoPago() {
    const fechaPago  = $('#fechaPagoNuevo').val();
    const formaPago  = $('#formaPagoNuevo').val();
    const medioPago  = $('#medioPagoNuevo').val();
    const monto      = parseFloat($('#montoNuevo').val()) || 0;
    const idDespacho = $('#idDespacho').val();

    if (!fechaPago) { Swal.fire('Error', 'La fecha de pago es requerida', 'error'); return; }
    if (!formaPago) { Swal.fire('Error', 'Debe seleccionar una forma de pago', 'error'); return; }
    if (!medioPago) { Swal.fire('Error', 'Debe seleccionar un medio de pago', 'error'); return; }
    if (monto <= 0)  { Swal.fire('Error', 'El importe en U$S debe ser mayor a cero', 'error'); return; }

    // Validar que no supere el saldo pendiente. Todo en U$S.
    const saldoActual = obtenerSaldoPendiente();
    const TOLERANCIA  = 0.01;
    if (monto > saldoActual + TOLERANCIA) {
        Swal.fire({
            title: 'El importe excede el saldo',
            html: `El importe ingresado <strong>U$S ${formatearMonedaUI(monto)}</strong> ` +
                  `supera el saldo pendiente de <strong>U$S ${formatearMonedaUI(saldoActual)}</strong>. ` +
                  `Ajustá el importe antes de guardar, o corregí el Valor F.O.B. U$S si el que ` +
                  `está cargado quedó viejo.`,
            icon: 'error',
            confirmButtonColor: '#dc3545'
        });
        return;
    }

    $.ajax({
        url: '../controller/insertarPago.php',
        method: 'POST',
        data: {
            id_despacho: idDespacho,
            fecha_pago:  fechaPago,
            forma_pago:  formaPago,
            medio_pago:  medioPago,
            monto:       monto
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const modalEl = document.getElementById('modalAgregarPago');

                // Recargar tabla una vez que el modal termina su animación de cierre
                $(modalEl).one('hidden.bs.modal', function() {
                    cargarPagos(idDespacho);
                });

                bootstrap.Modal.getOrCreateInstance(modalEl).hide();

                Swal.fire({
                    icon: 'success',
                    title: 'Pago agregado',
                    toast: true,
                    position: 'top-end',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire('Error', response.message || 'No se pudo agregar el pago', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al agregar pago:', error);
            Swal.fire('Error', 'Error al conectar con el servidor', 'error');
        }
    });
}
