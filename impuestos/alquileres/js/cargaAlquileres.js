

/**
 * Número de sucursal a partir de su <th>.
 * El <th> puede contener un badge ("Cerrada"), así que su textContent ya no es sólo
 * el número: hay que leerlo del atributo. Si se usa el textContent, los selectores
 * `#input-{concepto}-{sucursal}` no matchean, el querySelector devuelve null y la
 * excepción corta el forEach — dejando sin totales a esa sucursal y a todas las que siguen.
 */
const nroSucursalDe = (th) => (th.getAttribute("attr-nrosuc") || th.textContent).trim();

const comprobarEstado = (estado) => {

    if (estado == 1) {

        document.querySelectorAll("input").forEach(e => {

            e.readOnly = true;

        })
        let idConceptos = document.querySelectorAll("#idConcepto");
        let sucursales = document.querySelectorAll("#sucursal");

        sucursales.forEach(s => {

            let result = 0;
            let nroSuc = nroSucursalDe(s);

            idConceptos.forEach(e => {
                let concepto = e.textContent;
                $valorSumar = document.querySelector(`#input-${concepto.trimEnd()}-${nroSuc}`).value.replace(/[$.]/g, "");
                $valorSumar = $valorSumar.replace(/ /g, '');

                result = parseInt(result) + parseInt($valorSumar);


            })

            document.querySelector("#total-" + nroSuc).textContent = "$" + parseNumber(result);
        });

    } else {

        totalizar();

    }
}
const totalizar = (div = null) => {

    let idConceptos = document.querySelectorAll("#idConcepto");
    let sucursales = document.querySelectorAll("#sucursal");

    sucursales.forEach(s => {

        let result = 0;
        let nroSuc = nroSucursalDe(s);

        idConceptos.forEach(e => {
            let concepto = e.textContent;

            if (e.textContent == 9 || e.textContent == 13) {

                let inputActual = document.querySelector(`#input-${concepto.trimEnd()}-${nroSuc}`);
                let porcentaje = inputActual.getAttribute("attr-porcentaje");
                let valorId8 = document.querySelector(`#input-8-${nroSuc}`).getAttribute("attr-realvalue") || 0;
                let calculo = (parseInt(valorId8) * parseFloat(porcentaje)) / 100;

                inputActual.value = "$" + parseNumber(calculo);
                inputActual.setAttribute("attr-realvalue", calculo);

            }

            // Concepto 16: Restar concepto 9
            if (e.textContent == 16) {

                let inputConcepto9 = document.querySelector(`#input-9-${nroSuc}`);
                let valorId9 = parseInt(inputConcepto9.getAttribute('attr-realvalue') || 0);
                let inputActual = document.querySelector(`#input-${concepto.trimEnd()}-${nroSuc}`);
                let valorBruto = parseInt(inputActual.getAttribute('attr-realvalue-original') || inputActual.getAttribute('attr-realvalue') || 0);

                let calculo = valorBruto - valorId9;
                if (calculo < 0) {
                    calculo = 0;
                }

                inputActual.value = "$" + parseNumber(calculo);
                inputActual.setAttribute('attr-realvalue', calculo);

            }

            // Concepto 17: Calcular porcentaje sobre venta neta y RESTAR concepto 9
            if (e.textContent == 17) {
                let inputConcepto9 = document.querySelector(`#input-9-${nroSuc}`);
                let valorId9 = parseInt(inputConcepto9.getAttribute('attr-realvalue') || 0);
                let inputActual = document.querySelector(`#input-${concepto.trimEnd()}-${nroSuc}`);

                // Usar el valor BRUTO original (porcentaje sobre venta neta)
                let valorBruto = parseInt(inputActual.getAttribute('attr-realvalue-original') || inputActual.getAttribute('attr-realvalue') || 0);

                // Restar el concepto 9
                let calculo = valorBruto - valorId9;
                if (calculo < 0) {
                    calculo = 0;
                }

                inputActual.value = "$" + parseNumber(calculo);
                inputActual.setAttribute('attr-realvalue', calculo);
            }

            if (e.textContent == 6 || e.textContent == 7) {

                let inputConcepto8 = document.querySelector(`#input-8-${nroSuc}`);
                let valorId8 = parseInt(inputConcepto8.getAttribute('attr-realvalue') || 0);
                let inputActual = document.querySelector(`#input-${concepto.trimEnd()}-${nroSuc}`);

                // Usar el valor BRUTO original (antes de cualquier resta)
                let valorBruto = parseInt(inputActual.getAttribute('attr-realvalue-original') || inputActual.getAttribute('attr-realvalue') || 0);
                let calculo = valorBruto - valorId8;

                if (calculo < 0) {
                    calculo = 0;
                }
                inputActual.value = "$" + parseNumber(calculo);
                // IMPORTANTE: Actualizar attr-realvalue con el valor NETO calculado
                // para que se guarde correctamente en BD
                inputActual.setAttribute('attr-realvalue', calculo);

            }

            // Concepto 14: Calcular sobre el concepto 7 NETO (ya con mínimo restado)
            if (e.textContent == 14) {
                let inputActual = document.querySelector(`#input-${concepto.trimEnd()}-${nroSuc}`);
                let porcentaje = inputActual.getAttribute("attr-porcentaje");
                let inputConcepto7 = document.querySelector(`#input-7-${nroSuc}`);

                // IMPORTANTE: Usar el attr-realvalue del concepto 7 que ahora tiene el valor NETO
                // (después de restar el mínimo en el bloque anterior)
                let valorId7Neto = parseInt(inputConcepto7.getAttribute('attr-realvalue') || 0);

                // Calcular: valor concepto 7 (neto) * porcentaje / 100
                let calculo = (valorId7Neto * parseFloat(porcentaje)) / 100;

                inputActual.value = "$" + parseNumber(calculo);
                // Actualizar attr-realvalue para que se guarde correctamente en BD
                inputActual.setAttribute("attr-realvalue", calculo);
            }

            if (e.textContent == 4 || e.textContent == 5 || e.textContent == 18) {

                let inputActual = document.querySelector(`#input-${concepto.trimEnd()}-${nroSuc}`)

                let value = inputActual.value.replace(/[$.]/g, "")
                let ajustado = inputActual.getAttribute('attr-ajustado');

                // Deshabilitar solo si está marcado como ajustado en la BD
                if (ajustado == "1") {
                    inputActual.disabled = true;
                }

            }

            $valorSumar = document.querySelector(`#input-${concepto.trimEnd()}-${nroSuc}`).value.replace(/[$.]/g, "");
            $valorSumar = $valorSumar.replace(/ /g, '');

            result = parseInt(result) + parseInt($valorSumar);
        });

        document.querySelector("#total-" + nroSuc).textContent = "$" + parseNumber(result);

    });

    if (div != null) {

        let sucursalActual = div.id.split("-")[2];
        let conceptoActual = div.id.split("-")[1];

        // IMPORTANTE: Parsear el valor ingresado por el usuario y actualizar attr-realvalue
        // ANTES de llamar a actualizarDetalle()
        // El valor puede tener formato: $1.234.567 o 1234567 o -$1.234
        let valorIngresado = div.value.replace(/[$\s]/g, ""); // Eliminar $ y espacios
        valorIngresado = valorIngresado.replace(/\./g, ""); // Eliminar puntos separadores de miles
        valorIngresado = parseInt(valorIngresado) || 0;

        // Actualizar attr-realvalue con el valor parseado correctamente
        div.setAttribute("attr-realvalue", valorIngresado);

        // DEBUGGING: Log antes de actualizar
        console.group("🎯 DEBUG - Evento onchange disparado");
        console.log("🆔 Input ID:", div.id);
        console.log("🏢 Sucursal:", sucursalActual);
        console.log("📋 Concepto:", conceptoActual);
        console.log("💾 Valor original input:", div.value);
        console.log("🔢 Valor parseado:", valorIngresado);
        console.log("📊 attr-realvalue actualizado a:", div.getAttribute("attr-realvalue"));
        console.groupEnd();

        // Marcar si es una edición principal (no cascada)
        let esEdicionPrincipal = true;
        actualizarDetalle(div, esEdicionPrincipal);

        if (div.id.split("-")[1] == 8) {

            console.log("🔄 Concepto 8 detectado - Actualizando conceptos dependientes");
            actualizarDetalle(document.querySelector(`#input-6-${sucursalActual}`), false);
            actualizarDetalle(document.querySelector(`#input-7-${sucursalActual}`), false);
            actualizarDetalle(document.querySelector(`#input-9-${sucursalActual}`), false);
            actualizarDetalle(document.querySelector(`#input-13-${sucursalActual}`), false);
            actualizarDetalle(document.querySelector(`#input-16-${sucursalActual}`), false);
            actualizarDetalle(document.querySelector(`#input-17-${sucursalActual}`), false);
            actualizarDetalle(document.querySelector(`#input-14-${sucursalActual}`), false);

        }

        // Si cambia el concepto 7, recalcular el concepto 14 (que depende del 7)
        if (div.id.split("-")[1] == 7) {
            console.log("🔄 Concepto 7 detectado - Actualizando concepto 14");
            actualizarDetalle(document.querySelector(`#input-14-${sucursalActual}`), false);
        }

        value = div.value.replace(/[$.]/g, "");
        value = parseInt(value.replace(/ /g, ''));


        if (value < 0) {

            div.value = "- $" + (parseNumber((value * -1), true))
            console.log("💰 Valor final formateado (negativo):", div.value);

        } else {

            div.value = "$" + parseNumber(value)
            console.log("💰 Valor final formateado (positivo):", div.value);
        }

    }

}

const parseNumber = (number, realValue = null) => {

    number = parseInt(number);

    let newNumber = number.toLocaleString('de-De', {
        style: 'decimal',
        maximumFractionDigits: 0,
        minimumFractionDigits: 0
    });
    if (realValue != true) {

        if (newNumber < 0) {
            return 0;
        }

    }
    return newNumber;

}

const insertarDetalle = () => {

    let tabla = document.querySelector("#tablaAlquileres");

    let inputs = tabla.querySelectorAll("input");
    let values = "";
    let periodo = document.querySelector("#periodo").textContent;
    let sucursales = document.querySelectorAll("#sucursal");

    // DEBUG: Información inicial
    console.group("💾 DEBUG - insertarDetalle");
    console.log("📅 Periodo:", periodo);
    console.log("🔢 Total de inputs:", inputs.length);
    console.log("🏢 Total de sucursales:", sucursales.length);

    let conteoRegistros = 0;
    inputs.forEach((e, x) => {

        let data = e.id.split("-");
        let idConcepto = data[1];
        let idSucursal = data[2];
        let valor = e.value.replace(/[$.]/g, "");
        valor = parseFloat(valor).toFixed(2)

        sucursales.forEach(sucursal => {

            // El número sale de su propio atributo, y el nombre se corta por el ÚLTIMO
            // separador: attr-infosuc es "DESC_SUCURSAL-NRO_SUCURSAL" y hay nombres de
            // sucursal con guiones, que con un split("-") dejaban de matchear (la
            // sucursal no recibía ninguna fila) y guardaban el nombre truncado.
            let nroSucursal = sucursal.getAttribute("attr-nrosuc");
            let infoSucursal = sucursal.getAttribute("attr-infosuc") || "";
            let corte = infoSucursal.lastIndexOf("-");
            let descSucursal = corte > 0 ? infoSucursal.substring(0, corte) : infoSucursal;

            if (idSucursal == nroSucursal) {

                values += `('${periodo}','${idSucursal}','${descSucursal}','${valor}','${idConcepto}'),`;
                conteoRegistros++;

            }

        });

    });
    values = values.substring(0, values.length - 1);

    console.log("📊 Total de registros a insertar:", conteoRegistros);
    console.log("📝 Primeros 200 caracteres del SQL VALUES:", values.substring(0, 200) + "...");
    console.groupEnd();

    $.ajax({
        url: 'Controller/AlquilerController.php?accion=insertarDetalle',
        method: 'POST',
        data: {
            values: values
        },
        success: function (data) {
            console.log("✅ Respuesta de insertarDetalle:", data);
            console.log("🔄 Recargando página para mostrar datos insertados...");
            // Recargar la página después de insertar para que muestre los datos
            setTimeout(() => {
                location.reload();
            }, 500);
        },
        error: function (xhr, status, error) {
            console.error("❌ Error en insertarDetalle:", {
                status: status,
                error: error,
                responseText: xhr.responseText
            });
        }
    });

}

const actualizarDetalle = (div, esEdicionPrincipal = false) => {

    let periodo = document.querySelector("#periodo").textContent;
    let sucursal = div.id.split("-")[2];
    let concepto = div.id.split("-")[1];

    let porcentaje = div.getAttribute("attr-porcentaje");

    // Obtener valor anterior del atributo attr-realvalue
    let valorAnterior = div.getAttribute("attr-realvalue");

    // IMPORTANTE: Usar attr-realvalue que contiene el valor numérico correcto
    // en lugar de parsear div.value que está formateado
    let importe = div.getAttribute("attr-realvalue") || "0";
    let importe9 = 0;
    let importe13 = 0;

    // DEBUGGING: Obtener el entorno actual
    let entornoElement = document.querySelector("#checkEntorno");
    let entorno = entornoElement && entornoElement.checked ? "ARGENTINA (ARG)" : "URUGUAY (UY)";

    // DEBUGGING: Mostrar información detallada en consola
    console.group("🔍 DEBUG - Actualizando Detalle");
    console.log("📍 Entorno:", entorno);
    console.log("📅 Periodo:", periodo);
    console.log("🏢 Sucursal:", sucursal);
    console.log("📋 Concepto:", concepto);
    console.log("📊 Porcentaje:", porcentaje);
    console.log("⬅️  Valor Anterior:", valorAnterior);
    console.log("➡️  Valor Nuevo:", importe);
    console.log("🔢 Valor Formateado (input):", div.value);
    console.log("🔄 Cambio:", parseFloat(importe) - parseFloat(valorAnterior));
    console.log("🎯 Es edición principal:", esEdicionPrincipal);

    // Advertencia especial para valor cero
    if (importe == "0" || importe == "" || parseFloat(importe) === 0) {
        console.warn("⚠️  ADVERTENCIA: El valor nuevo es CERO");
    }

    console.groupEnd();

    $.ajax({
        url: 'Controller/AlquilerController.php?accion=actualizarDetalle',
        method: 'POST',
        data: {
            periodo: periodo,
            sucursal: sucursal,
            concepto: concepto,
            importe: importe,
            importe9: importe9,
            importe13: importe13,
            porcentaje: porcentaje
        },
        success: function (data) {
            console.log("✅ Respuesta del servidor (actualizarDetalle):", data);

            // Actualizar el attr-realvalue con el nuevo valor para futuras comparaciones
            div.setAttribute("attr-realvalue", importe);

            // Si es una edición principal (hecha por el usuario), recargar página
            // para mostrar todos los valores actualizados correctamente
            if (esEdicionPrincipal) {
                console.log("🔄 Recargando página para mostrar valores actualizados...");
                setTimeout(() => {
                    location.reload();
                }, 300);
            }
        },
        error: function (xhr, status, error) {
            console.error("❌ Error al actualizar detalle:", {
                status: status,
                error: error,
                responseText: xhr.responseText
            });
        }
    });

}

const actualizarCargaAutomatica = (cerrado = 0) => {

    if (cerrado == 1) {
        document.querySelectorAll("input").forEach(e => {
            // e.disabled = true;
        })
        return false;

    }

    let tabla = document.querySelector("#tablaAlquileres");

    let inputs = tabla.querySelectorAll("input");

    let periodo = document.querySelector("#periodo").textContent;

    inputs.forEach((e, x) => {

        let data = e.id.split("-");
        let idConcepto = data[1];
        let valor = e.value.replace(/[$.]/g, "");
        valor = parseFloat(valor).toFixed(2);

        if (['6', '7', '9', '13', '14', '15', '16', '17'].includes(idConcepto)) {

            // Siempre guardar, incluso si el valor es 0.
            // Si el BRUTO < mínimo, el NETO es 0 y debe quedar 0 en BD.
            // La condición anterior (valor > 0) dejaba el BRUTO en BD cuando el resultado era negativo.
            actualizarDetalle(e);

        }


    });


}


const procesar = () => {

    let periodo = document.querySelector("#periodo").textContent;
    let estado = document.querySelector("#estado").textContent;

    // VALIDACIÓN 1: Verificar primero si el período está cerrado
    if (estado == 1) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Debe abrir el período antes de procesar!'
        })
        return false;
    }

    // VALIDACIÓN 2: Verificar si fue aplicado el ajuste
    $.ajax({
        url: 'Controller/AlquilerController.php?accion=comprobarAjuste',
        method: 'POST',
        data: {
            periodo: periodo
        },
        success: function (data) {
            console.log('Respuesta comprobarAjuste:', data);

            if (data == 1) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'Debe aplicar el ajuste antes de procesar!'
                })
            } else {
                // VALIDACIÓN 3: Verificar sucursales con total = 0
                verificarSucursalesSinCostosYProcesar(periodo);
            }
        }
    })
}

const verificarSucursalesSinCostosYProcesar = (periodo) => {
    // Obtener la fila de totales (última fila de la tabla)
    let filaTotales = document.querySelector("tbody tr:last-child");
    if (!filaTotales) {
        console.error("No se encontró la fila de totales");
        ejecutarProcesamiento(periodo);
        return;
    }

    let celdasTotales = filaTotales.querySelectorAll("td");
    let sucursalesSinCostos = [];
    let sucursalesYaProcesadas = new Set(); // Para evitar duplicados

    // Iterar desde la tercera celda (índice 2) en adelante, saltando las dos primeras columnas
    for (let i = 2; i < celdasTotales.length; i++) {
        let element = celdasTotales[i];

        // Obtener el número de sucursal del id de la celda (ej: "total-02")
        let idTotal = element.id;
        if (!idTotal || !idTotal.startsWith('total-')) continue;

        let nroSucursal = idTotal.replace('total-', '');

        // Evitar duplicados
        if (sucursalesYaProcesadas.has(nroSucursal)) continue;

        // Limpiar el valor del total
        let valorTotal = element.textContent.replace(/[$.\s]/g, "").replace(/-/g, "");
        let total = parseInt(valorTotal) || 0;

        console.log(`Verificando sucursal ${nroSucursal}: total = ${total}, texto original = "${element.textContent}"`);

        if (total === 0) {
            sucursalesSinCostos.push({
                numero: nroSucursal
            });
            sucursalesYaProcesadas.add(nroSucursal);
        }
    }

    console.log("Sucursales sin costos detectadas:", sucursalesSinCostos);

    if (sucursalesSinCostos.length > 0) {
        // Hay sucursales sin costos, preguntar qué hacer
        mostrarModalSucursalesSinCostos(sucursalesSinCostos, periodo);
    } else {
        // No hay sucursales sin costos, proceder directamente
        ejecutarProcesamiento(periodo);
    }
}

const mostrarModalSucursalesSinCostos = (sucursales, periodo) => {
    // Crear listado simple de sucursales sin costos
    let listaSucursales = sucursales.map(suc => `<strong>${suc.numero}</strong>`).join(', ');

    let contenidoHTML = `
        <div style="text-align: center; padding: 20px;">
            <p style="font-size: 15px; margin-bottom: 20px;">
                Las siguientes sucursales tienen costos totales en <strong style="color: #dc3545;">$0</strong>:
            </p>
            <div style="background-color: #fff3cd; border: 2px solid #ffc107; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                <p style="font-size: 18px; margin: 0; color: #856404;">
                    ${listaSucursales}
                </p>
            </div>
            <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; text-align: left;">
                <p style="margin: 0 0 10px 0; font-size: 13px;">
                    <i class="fas fa-info-circle" style="color: #17a2b8;"></i> 
                    <strong>Opciones:</strong>
                </p>
                <ul style="font-size: 13px; margin: 0; padding-left: 25px;">
                    <li style="margin-bottom: 8px;"><strong>Continuar Procesamiento:</strong> Procesa solo las sucursales con costos (las que están en $0 serán ignoradas)</li>
                    <li style="margin-bottom: 8px;"><strong>Cancelar:</strong> Vuelve a la pantalla principal sin procesar</li>
                </ul>
            </div>
        </div>
    `;

    Swal.fire({
        icon: 'warning',
        title: '⚠️ Sucursales sin costos detectadas',
        html: contenidoHTML,
        width: '550px',
        showCancelButton: true,
        showDenyButton: true,
        cancelButtonText: '<i class="fas fa-check-circle"></i> Continuar Procesamiento',
        denyButtonText: '<i class="fas fa-times"></i> Cancelar',
        cancelButtonColor: '#28a745',
        denyButtonColor: '#6c757d',
        showConfirmButton: false,
        allowOutsideClick: false,
        customClass: {
            cancelButton: 'btn-icon-swal',
            denyButton: 'btn-icon-swal'
        }
    }).then((result) => {
        if (result.isDismissed && result.dismiss === Swal.DismissReason.cancel) {
            // Continuar procesamiento solo con las sucursales que tienen costos
            ejecutarProcesamiento(periodo);
        }
        // Si es Deny, simplemente no hace nada (cancelar)
    });
}

const eliminarSucursalSinCostos = (nroSucursal, periodo) => {
    Swal.fire({
        title: '¿Confirmar eliminación?',
        html: `¿Está seguro que desea eliminar la sucursal <strong>${nroSucursal}</strong> del período <strong>${periodo}</strong>?<br><br>
               <small style="color: #dc3545;"><i class="fas fa-exclamation-triangle"></i> Esta acción eliminará todos los registros de esta sucursal para este período.</small>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Realizar la eliminación
            $.ajax({
                url: 'Controller/AlquilerController.php?accion=eliminarSucursalDelPeriodo',
                method: 'POST',
                data: {
                    periodo: periodo,
                    nroSucursal: nroSucursal
                },
                success: function (response) {
                    try {
                        const data = JSON.parse(response);

                        if (data.status === 'success') {
                            // Eliminar la fila de la tabla del modal
                            const row = document.querySelector(`#row-suc-${nroSucursal}`);
                            if (row) {
                                row.remove();
                            }

                            // Mostrar notificación de éxito
                            Swal.fire({
                                icon: 'success',
                                title: 'Eliminada',
                                text: `Sucursal ${nroSucursal} eliminada correctamente`,
                                timer: 2000,
                                showConfirmButton: false,
                                toast: true,
                                position: 'top-end'
                            });

                            // Verificar si quedan más sucursales en la tabla
                            const tablaSucursales = document.querySelector('#tablaSucursalesSinCostos');
                            if (tablaSucursales && tablaSucursales.children.length === 0) {
                                // No quedan más sucursales, cerrar el modal y proceder
                                Swal.close();
                                ejecutarProcesamiento(periodo);
                            }
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message
                            });
                        }
                    } catch (e) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error al procesar la respuesta: ' + e.message
                        });
                    }
                },
                error: function (xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de conexión',
                        text: 'No se pudo eliminar la sucursal. Error: ' + error
                    });
                }
            });
        }
    });
}

const validarYProcesarConTodas = (periodo) => {
    // Validar que TODAS las sucursales tengan costos > 0
    let allTd = document.querySelectorAll("tr")[19].querySelectorAll("td");

    for (let i = 2; i < allTd.length; i++) {
        let element = allTd[i];
        let value = element.textContent.replace(/[$.]/g, "").replace(/ /g, '');

        if (value == 0 || value == "") {
            Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: 'Complete los gastos de todas las sucursales antes de procesar!'
            });
            return false;
        }
    }

    // Si todas tienen valores, procesar
    ejecutarProcesamiento(periodo);
}

const ejecutarProcesamiento = (periodo) => {
    $.ajax({
        url: 'Controller/AlquilerController.php?accion=procesar',
        method: 'POST',
        data: {
            periodo: periodo
        },
        success: function (data) {
            try {
                const response = JSON.parse(data);

                if (response.status === 'error') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                } else if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Procesado',
                        text: response.message
                    }).then((result) => {
                        location.reload();
                    });
                }
            } catch (e) {
                // Fallback para respuestas que no sean JSON
                if (data == 1) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'El período ya se encuentra procesado!'
                    });
                } else {
                    Swal.fire({
                        icon: 'success',
                        title: 'Procesado',
                        text: 'Se ha procesado correctamente!'
                    }).then((result) => {
                        location.reload();
                    });
                }
            }
        },
        error: function (xhr, status, error) {
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo procesar la solicitud. Error: ' + error
            });
        }
    });
}

const cerrarPeriodo = () => {

    let periodo = document.querySelector("#periodo").textContent;
    let ultimaFechaDelMes = document.querySelector("#ultimaFechaDelMes").textContent;

    mesAnterior = document.querySelector("#mesAnterior").textContent;

    $.ajax({
        url: 'Controller/AlquilerController.php?accion=checkCierrePeriodoAnt',
        method: 'POST',
        data: {
            mesAnterior: mesAnterior
        },
        success: function (response) {
            response = JSON.parse(response)
            if (response[0]['RegistroExiste'] == 0) {

                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'Debe cerrar los periodos anteriores!'
                })
                return 1

            } else {

                $.ajax({
                    url: 'Controller/AlquilerController.php?accion=verificarProcesado',
                    method: 'POST',
                    data: {
                        fecha: ultimaFechaDelMes
                    },
                    success: function (response) {
                        response = JSON.parse(response);

                        if (response[0]['RegistroExiste'] == 1) {

                            // Antes de cerrar, verificar si hay diferencias en los valores
                            verificarDiferenciasYCerrar(periodo);

                        } else {

                            Swal.fire({
                                icon: 'warning',
                                title: 'Atención',
                                text: 'Primero debe procesar el período!'
                            })
                            return 1
                        }
                    }
                });
            }
        }

    });

    return 1
}

const verificarDiferenciasYCerrar = (periodo) => {
    // Recolectar todos los valores actuales de la tabla
    let datosActuales = {};
    let sucursales = document.querySelectorAll("#sucursal");
    let conceptos = document.querySelectorAll("#idConcepto");

    sucursales.forEach(sucursal => {
        const nroSucursal = nroSucursalDe(sucursal);
        datosActuales[nroSucursal] = {};

        conceptos.forEach(concepto => {
            const idConcepto = concepto.textContent.trim();
            const inputElement = document.querySelector(`#input-${idConcepto}-${nroSucursal}`);
            if (inputElement) {
                // Extraer el valor real del input preservando el signo negativo
                let valorTexto = inputElement.value.trim();

                // Detectar si es negativo
                const esNegativo = valorTexto.startsWith('-') || valorTexto.startsWith('(');

                // Limpiar formato pero preservar números y decimales
                valorTexto = valorTexto.replace(/[\$\.\s\(\)]/g, '').replace(',', '.');

                // Parsear valor
                let valor = parseFloat(valorTexto) || 0;

                // Aplicar signo negativo si corresponde
                if (esNegativo && valor > 0) {
                    valor = -valor;
                }

                datosActuales[nroSucursal][idConcepto] = valor;
            }
        });
    });

    // Verificar si hay diferencias
    $.ajax({
        url: 'Controller/AlquilerController.php?accion=verificarDiferenciasPreCierre',
        method: 'POST',
        data: {
            periodo: periodo,
            datosActuales: datosActuales
        },
        success: function (response) {
            const data = JSON.parse(response);

            if (data.tieneDiferencias && data.diferencias.length > 0) {
                mostrarDiferenciasYConfirmar(periodo, data.diferencias);
            } else {
                // No hay diferencias, proceder al cierre normal
                ejecutarCierrePeriodo(periodo, false);
            }
        },
        error: function (xhr, status, error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al verificar diferencias: ' + error
            });
        }
    });
}

const mostrarDiferenciasYConfirmar = (periodo, diferencias) => {
    // Resaltar las celdas con diferencias
    diferencias.forEach(dif => {
        const inputElement = document.querySelector(`#input-${dif.concepto}-${dif.sucursal}`);
        if (inputElement) {
            inputElement.style.backgroundColor = '#fff3cd';
            inputElement.style.border = '2px solid #ffc107';
        }
    });

    // Crear tabla HTML con las diferencias
    let tablaHTML = `
        <div style="max-height: 400px; overflow-y: auto; text-align: left;">
            <table class="table table-sm table-bordered" style="font-size: 12px;">
                <thead class="thead-light">
                    <tr>
                        <th>Sucursal</th>
                        <th>Concepto</th>
                        <th>Valor Guardado</th>
                        <th>Valor Actual</th>
                        <th>Diferencia</th>
                    </tr>
                </thead>
                <tbody>
    `;

    diferencias.forEach(dif => {
        const diferencia = dif.diferencia;
        const colorDif = diferencia > 0 ? 'text-success' : 'text-danger';
        const simbolo = diferencia > 0 ? '+' : '';

        tablaHTML += `
            <tr>
                <td>${dif.sucursal} - ${dif.desc_sucursal}</td>
                <td>${dif.concepto}</td>
                <td>$${formatNumber(dif.importeGuardado)}</td>
                <td>$${formatNumber(dif.importeActual)}</td>
                <td class="${colorDif}">${simbolo}$${formatNumber(diferencia)}</td>
            </tr>
        `;
    });

    tablaHTML += `
                </tbody>
            </table>
        </div>
        <p style="margin-top: 15px; font-weight: bold;">¿Desea actualizar los valores antes de cerrar el período?</p>
    `;

    Swal.fire({
        icon: 'warning',
        title: '⚠️ Se detectaron diferencias en los valores',
        html: tablaHTML,
        width: '800px',
        showDenyButton: true,
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-sync-alt"></i> Actualizar y Cerrar',
        denyButtonText: '<i class="fas fa-lock"></i> Cerrar sin Actualizar',
        cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
        confirmButtonColor: '#28a745',
        denyButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        customClass: {
            confirmButton: 'btn-icon-swal',
            denyButton: 'btn-icon-swal',
            cancelButton: 'btn-icon-swal'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Actualizar valores y luego cerrar
            actualizarValoresYCerrar(periodo);
        } else if (result.isDenied) {
            // Cerrar sin actualizar
            ejecutarCierrePeriodo(periodo, true);
        } else {
            // Cancelar - quitar resaltados
            quitarResaltadoDiferencias();
        }
    });
}

const actualizarValoresYCerrar = (periodo) => {
    // Primero actualizar todos los valores modificados
    Swal.fire({
        title: '🔄 Actualizando valores...',
        html: '<i class="fas fa-sync fa-spin" style="font-size: 48px; color: #007bff;"></i><br><br>Por favor espere',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Guardar todos los cambios actuales
    let sucursales = document.querySelectorAll("#sucursal");
    let conceptos = document.querySelectorAll("#idConcepto");
    let promises = [];

    sucursales.forEach(sucursal => {
        const nroSucursal = nroSucursalDe(sucursal);

        conceptos.forEach(concepto => {
            const idConcepto = concepto.textContent.trim();
            const inputElement = document.querySelector(`#input-${idConcepto}-${nroSucursal}`);

            if (inputElement && inputElement.style.backgroundColor === 'rgb(255, 243, 205)') {
                // Este input tiene diferencias, actualizarlo
                const porcentaje = inputElement.getAttribute('attr-porcentaje') || 0;

                // Obtener el valor actual del input preservando el signo negativo
                let valorTexto = inputElement.value.trim();

                // Detectar si es negativo
                const esNegativo = valorTexto.startsWith('-') || valorTexto.startsWith('(');

                // Limpiar formato pero preservar números y decimales
                valorTexto = valorTexto.replace(/[\$\.\s\(\)]/g, '').replace(',', '.');

                // Parsear valor
                let valorReal = parseFloat(valorTexto) || 0;

                // Aplicar signo negativo si corresponde
                if (esNegativo && valorReal > 0) {
                    valorReal = -valorReal;
                }

                console.log(`Actualizando: Sucursal=${nroSucursal}, Concepto=${idConcepto}, Valor=${valorReal}, Porcentaje=${porcentaje}`);

                const promise = $.ajax({
                    url: 'Controller/AlquilerController.php?accion=actualizarDetalle',
                    method: 'POST',
                    data: {
                        periodo: periodo,
                        sucursal: nroSucursal,
                        concepto: idConcepto,
                        importe: valorReal,
                        porcentaje: porcentaje
                    }
                });
                promises.push(promise);
            }
        });
    });

    // Esperar a que todas las actualizaciones terminen
    Promise.all(promises).then(() => {
        quitarResaltadoDiferencias();
        // Ahora proceder al cierre
        ejecutarCierrePeriodo(periodo, true);
    }).catch((error) => {
        Swal.fire({
            icon: 'error',
            title: '✖ Error al actualizar',
            html: `<div style="text-align: center;">
                    <i class="fas fa-times-circle" style="font-size: 48px; color: #dc3545; margin-bottom: 15px;"></i>
                    <p>Error al actualizar valores: ${error}</p>
                  </div>`,
            confirmButtonText: 'Aceptar'
        });
    });
}

const ejecutarCierrePeriodo = (periodo, forzarCierre) => {
    $.ajax({
        url: 'Controller/AlquilerController.php?accion=cerrarPeriodo',
        method: 'POST',
        data: {
            periodo: periodo,
            forzarCierre: forzarCierre
        },
        success: function (data) {
            try {
                const response = JSON.parse(data);

                if (response.status === 'success') {
                    quitarResaltadoDiferencias();
                    Swal.fire({
                        icon: 'success',
                        title: '✓ Período cerrado exitosamente',
                        html: `<div style="text-align: center;">
                                <i class="fas fa-check-circle" style="font-size: 48px; color: #28a745; margin-bottom: 15px;"></i>
                                <p>${response.message}</p>
                              </div>`,
                        confirmButtonText: 'Aceptar'
                    }).then((result) => {
                        location.reload();
                    });
                } else if (response.status === 'error') {
                    Swal.fire({
                        icon: 'error',
                        title: '✖ Error',
                        html: `<div style="text-align: center;">
                                <i class="fas fa-times-circle" style="font-size: 48px; color: #dc3545; margin-bottom: 15px;"></i>
                                <p>${response.message}</p>
                              </div>`,
                        confirmButtonText: 'Aceptar'
                    });
                }
            } catch (e) {
                // Compatibilidad con respuesta anterior
                if (data == 1) {
                    Swal.fire({
                        icon: 'error',
                        title: '✖ Error',
                        html: `<div style="text-align: center;">
                                <i class="fas fa-times-circle" style="font-size: 48px; color: #dc3545; margin-bottom: 15px;"></i>
                                <p>El período ya se encuentra cerrado!</p>
                              </div>`,
                        confirmButtonText: 'Aceptar'
                    });
                } else {
                    quitarResaltadoDiferencias();
                    Swal.fire({
                        icon: 'success',
                        title: '✓ Período cerrado',
                        html: `<div style="text-align: center;">
                                <i class="fas fa-check-circle" style="font-size: 48px; color: #28a745; margin-bottom: 15px;"></i>
                                <p>Se ha cerrado correctamente!</p>
                              </div>`,
                        confirmButtonText: 'Aceptar'
                    }).then((result) => {
                        location.reload();
                    });
                }
            }
        },
        error: function (xhr, status, error) {
            Swal.fire({
                icon: 'error',
                title: '✖ Error al cerrar período',
                html: `<div style="text-align: center;">
                        <i class="fas fa-times-circle" style="font-size: 48px; color: #dc3545; margin-bottom: 15px;"></i>
                        <p>${error}</p>
                      </div>`,
                confirmButtonText: 'Aceptar'
            });
        }
    });
}

const quitarResaltadoDiferencias = () => {
    document.querySelectorAll('input[type="text"]').forEach(input => {
        input.style.backgroundColor = '';
        input.style.border = '';
    });
}

const formatNumber = (num) => {
    return Math.abs(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

const abrirPeriodo = () => {

    let periodo = document.querySelector("#periodo").textContent;

    $.ajax({

        url: 'Controller/AlquilerController.php?accion=abrirPeriodo',
        method: 'POST',
        data: {
            periodo: periodo
        },
        success: function (data) {
            console.log('Respuesta abrirPeriodo:', data); // Debug

            // El controlador devuelve 0 para éxito, 1 para error
            if (data == 0) {
                Swal.fire({
                    icon: 'success',
                    title: 'Abierto',
                    text: 'Se ha abierto correctamente el período!'
                }).then((result) => {
                    location.reload();
                })
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al abrir el período!'
                })
            }
        },
        error: function (xhr, status, error) {
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo abrir el período. Error: ' + error
            });
        }

    });
}

const revertirProcesamiento = () => {

    let periodo = document.querySelector("#periodo").textContent;

    Swal.fire({
        title: '¿Está seguro?',
        text: "Se eliminarán los registros procesados de este período y podrá volver a procesarlo",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, revertir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'Controller/AlquilerController.php?accion=revertirProcesamiento',
                method: 'POST',
                data: {
                    periodo: periodo
                },
                success: function (data) {
                    try {
                        const response = JSON.parse(data);

                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Revertido',
                                text: response.message
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    } catch (e) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error al revertir el procesamiento'
                        });
                    }
                },
                error: function (xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error de conexión: ' + error
                    });
                }
            });
        }
    });
}

const ocultarSucursal = () => {

    let sucursal = document.querySelector("#selectOcultarSucursal").value
    let periodo = document.querySelector("#periodo").textContent;

    $.ajax({

        url: 'Controller/AlquilerController.php?accion=ocultarSucursal',
        method: 'POST',
        data: {
            sucursal: sucursal,
            periodo: periodo
        },
        success: function (response) {

            document.querySelectorAll(".suc" + sucursal).forEach(element => {
                element.remove()
            });

        }

    })


}

const AplicarAjuste = () => {
    let periodo = document.querySelector("#periodo").textContent;

    // Obtener el coeficiente actual antes de mostrar el modal
    $.ajax({
        url: 'Controller/AlquilerController.php?accion=traerCoeficiente',
        method: 'POST',
        data: { periodo: periodo },
        success: function (response) {
            let coeficienteDefault = "1,0000";
            try {
                const data = JSON.parse(response);
                if (data.status === 'success') {
                    coeficienteDefault = data.coeficiente;
                }
            } catch (e) {
                console.error("Error al obtener coeficiente:", e);
            }

            mostrarModalAjuste(periodo, coeficienteDefault);
        },
        error: function () {
            mostrarModalAjuste(periodo, "1,0000");
        }
    });
};

const mostrarModalAjuste = (periodo, coeficienteDefault) => {
    Swal.fire({
        title: '<span style="color: #3085d6; font-weight: bold;">Aplicar Ajuste Manual</span>',
        html: `
            <div style="padding: 10px; text-align: center;">
                <p style="margin-bottom: 20px; color: #545454; font-size: 16px;">
                    Cargue el coeficiente para el período <strong style="color: #3085d6;">${periodo}</strong>
                </p>
                <div style="background-color: #f8f9fa; border-radius: 12px; padding: 25px; border: 1px solid #e9ecef;">
                    <label for="coeficiente_manual" style="display: block; margin-bottom: 12px; font-weight: 600; color: #333; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">
                        Coeficiente de Ajuste
                    </label>
                    <input type="text" id="coeficiente_manual" 
                        class="form-control" 
                        style="width: 100%; height: 50px; font-size: 24px; text-align: center; font-weight: bold; border-radius: 8px; border: 2px solid #3085d6; color: #3085d6; box-shadow: 0 4px 6px rgba(48, 133, 214, 0.1);" 
                        placeholder="1,2000" 
                        value="${coeficienteDefault}">
                    <p style="margin-top: 15px; margin-bottom: 0; color: #6c757d; font-size: 13px;">
                        <i class="fas fa-info-circle"></i> Use coma para decimales (4 dígitos requeridos).
                    </p>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Guardar y Aplicar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        reverseButtons: true,
        didOpen: () => {
            const input = document.getElementById('coeficiente_manual');
            input.focus();
            input.select();

            input.addEventListener('input', function (e) {
                this.value = this.value.replace('.', ',');
            });

            input.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    Swal.clickConfirm();
                }
            });
        },
        preConfirm: () => {
            const coeficiente = document.getElementById('coeficiente_manual').value.trim();
            if (!coeficiente) {
                Swal.showValidationMessage('Debe ingresar un coeficiente');
                return false;
            }
            if (!/^\d+(,\d{1,4})?$/.test(coeficiente)) {
                Swal.showValidationMessage('Formato inválido. Ejemplo: 1,2000');
                return false;
            }
            return coeficiente;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const coeficienteManual = result.value;

            // 1. Guardar el coeficiente en la tabla
            $.ajax({
                url: 'Controller/AlquilerController.php?accion=guardarCoeficiente',
                method: 'POST',
                data: {
                    periodo: periodo,
                    coeficiente: coeficienteManual
                },
                success: function (response) {
                    const data = JSON.parse(response);
                    if (data.status === 'success') {
                        // 2. Si se guardó bien, ejecutar la lógica original de aplicar ajuste
                        ejecutarAplicarAjuste(periodo);
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'No se pudo guardar el coeficiente', 'error');
                }
            });
        }
    });
};

const ejecutarAplicarAjuste = (periodo) => {
    let sucursales = document.querySelectorAll("#sucursal");
    let newArray = {};

    sucursales.forEach((sucursal) => {
        const sucursalName = nroSucursalDe(sucursal);
        newArray[sucursalName] = [];

        ["4", "5", "18"].forEach((concepto) => {
            const div = document.querySelector(`#input-${concepto}-${sucursalName}`);
            if (!div) return;

            const valor = div.value.replace(/[$.]/g, "").replace(/-/g, "").trim();
            const estaDeshabilitado = div.disabled;

            if (valor > 0 && !estaDeshabilitado) {
                newArray[sucursalName].push({
                    concepto: concepto,
                    value: valor,
                });
            }
        });
    });

    $.ajax({
        url: 'Controller/AlquilerController.php?accion=aplicarAjuste',
        method: 'POST',
        data: {
            arrayData: JSON.stringify(newArray),
            periodo: periodo
        },
        success: function (response) {
            try {
                const data = JSON.parse(response);
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Ajuste aplicado correctamente',
                        html: `<p>Se actualizaron <strong>${data.registros_actualizados}</strong> registro(s)</p>
                               <p>Coeficiente aplicado: <strong>${data.coeficiente}</strong></p>`,
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Atención', data.message || 'Error desconocido', data.status || 'warning');
                }
            } catch (e) {
                location.reload();
            }
        },
        error: function (xhr, status, error) {
            Swal.fire('Error', 'No se pudo aplicar el ajuste. Error: ' + error, 'error');
        }
    });
};


/**
 * Selector de entorno con banderas (mismo estilo que resumenVentas).
 * Un clic en cualquier parte del control cambia al entorno opuesto.
 *
 * Ojo: en Alquileres los entornos son 'central' y 'uy' — no 'suc_uy' como en
 * controlSucursales. Controller/cambiarEntorno.php espera 0 = central, 1 = uy.
 */
const cambiarEntornoCustom = (container) => {
    const activa = container.querySelector('.toggle-flag.active');
    const entorno = (activa && activa.dataset.entorno === 'central') ? 1 : 0;

    // Feedback inmediato: el reload tarda y sin esto el clic parece no hacer nada.
    container.style.pointerEvents = 'none';
    container.style.opacity = '0.6';

    $.ajax({
        url: "Controller/cambiarEntorno.php",
        method: "POST",
        data: { entorno: entorno },
        success: function () {
            location.reload();
        },
        error: function (xhr, status, error) {
            console.error('Error al cambiar entorno:', error);
            container.style.pointerEvents = '';
            container.style.opacity = '';
            Swal.fire({
                icon: 'error',
                title: 'No se pudo cambiar el entorno',
                text: 'Intentá nuevamente.',
                confirmButtonColor: '#667eea'
            });
        }
    });
}

const cambiarEntorno = (t) => {
    let entorno = 0;

    // Si el checkbox está checked = Argentina (central = 0)
    // Si el checkbox NO está checked = Uruguay (uy = 1)
    if (t.checked) {
        entorno = 0; // Argentina
    } else {
        entorno = 1; // Uruguay
    }

    $.ajax({
        url: "Controller/cambiarEntorno.php",
        method: "POST",
        data: { entorno: entorno },
        success: function (data) {
            location.reload();
        },
        error: function (xhr, status, error) {
            console.error('Error al cambiar entorno:', error);
            location.reload();
        }
    });
}

const descargarPDF = () => {
    let periodo = document.querySelector("#periodo").textContent;
    let mes = document.querySelector("#selectMes").value;
    let anio = document.querySelector("#selectAnio").value;

    // Abrir en nueva ventana el archivo PDF
    window.open(`components/generarPDF.php?periodo=${periodo}&mes=${mes}&anio=${anio}`, '_blank');
}