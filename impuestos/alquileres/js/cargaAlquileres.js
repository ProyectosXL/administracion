

const comprobarEstado = (estado) =>{

    if(estado == 1){

        document.querySelectorAll("input").forEach(e => {

            e.readOnly = true;
            
        })
        let idConceptos = document.querySelectorAll("#idConcepto");
        let sucursales = document.querySelectorAll("#sucursal");
    
        sucursales.forEach(s => {
            
            let result = 0;

            idConceptos.forEach(e => {
                let concepto = e.textContent;
                $valorSumar = document.querySelector(`#input-${concepto.trimEnd()}-${s.textContent}`).value.replace(/[$.]/g, "");
                $valorSumar = $valorSumar.replace(/ /g,'');
    
                result = parseInt(result) +  parseInt($valorSumar);  

            
            })

            document.querySelector("#total-"+s.textContent).textContent = "$"+parseNumber(result);
        });

    }else{

        totalizar();

    }
}
const totalizar = (div = null) => {
 
    let idConceptos = document.querySelectorAll("#idConcepto");
    let sucursales = document.querySelectorAll("#sucursal");

    sucursales.forEach(s => {
        
        let result = 0;
        idConceptos.forEach(e => {
            let concepto = e.textContent;
            if(e.textContent == 14 && ["2","16","60","79","81"].includes(s.textContent)) {
                
                let porcentaje = document.querySelector(`#input-${concepto.trimEnd()}-${s.textContent}`).getAttribute("attr-realvalue");
                let valorId7 = document.querySelector(`#input-7-${s.textContent}`).value.replace(/[$.]/g, "");

                document.querySelector(`#input-${concepto.trimEnd()}-${s.textContent}`).value ="$"+ parseNumber((parseInt(valorId7) * parseFloat(porcentaje)) / 100);
                
            }
            
            if(e.textContent == 9 || e.textContent == 13 ) {

                let porcentaje = document.querySelector(`#input-${concepto.trimEnd()}-${s.textContent}`).getAttribute("attr-realvalue");
                let valorId8 = document.querySelector(`#input-8-${s.textContent}`).value.replace(/[$.]/g, "");
                document.querySelector(`#input-${concepto.trimEnd()}-${s.textContent}`).value ="$"+ parseNumber((parseInt(valorId8) * parseFloat(porcentaje)) / 100);

            }

            if(e.textContent == 16 || e.textContent == 17 ) {

                let valorId9 = document.querySelector(`#input-9-${s.textContent}`).value.replace(/[$.]/g, "");
                let inputActual = document.querySelector(`#input-${concepto.trimEnd()}-${s.textContent}`)

                if ( parseInt(inputActual.getAttribute('attr-realvalue')) - parseInt(valorId9)  < 0) {
                    inputActual.value = "$0";
                }else{

                    inputActual.value ="$"+ parseNumber( parseInt(inputActual.getAttribute('attr-realvalue')) - parseInt(valorId9) );
                }

            }

            if(e.textContent == 6 || e.textContent == 7 ) {

                let valorId8 = document.querySelector(`#input-8-${s.textContent}`).value.replace(/[$.]/g, "");
                let inputActual = document.querySelector(`#input-${concepto.trimEnd()}-${s.textContent}`)
                let calculo =  parseInt(inputActual.getAttribute('attr-realvalue')) - parseInt(valorId8) ;

                if(calculo < 0) {
                    calculo = 0;
                }
                inputActual.value ="$"+ parseNumber( calculo); 

            }

            if(e.textContent == 4 || e.textContent == 5  || e.textContent == 18 ) {

                let inputActual = document.querySelector(`#input-${concepto.trimEnd()}-${s.textContent}`)

                let value = inputActual.value.replace(/[$.]/g, "")
                let ajustado = inputActual.getAttribute('attr-ajustado');
            
                // Deshabilitar solo si está marcado como ajustado en la BD
                if(ajustado == "1") {
                    inputActual.disabled = true;
                }

            }

            $valorSumar = document.querySelector(`#input-${concepto.trimEnd()}-${s.textContent}`).value.replace(/[$.]/g, "");
            $valorSumar = $valorSumar.replace(/ /g,'');

            result = parseInt(result) +  parseInt($valorSumar);  
        });
        
        document.querySelector("#total-"+s.textContent).textContent = "$"+parseNumber(result);

    });

    if(div != null) {

    let sucursalActual = div.id.split("-")[2];

        actualizarDetalle(div);

        if(div.id.split("-")[1] == 8) {
        
            actualizarDetalle(document.querySelector(`#input-6-${sucursalActual}`));
            actualizarDetalle(document.querySelector(`#input-7-${sucursalActual}`));
            actualizarDetalle(document.querySelector(`#input-9-${sucursalActual}`));
            actualizarDetalle(document.querySelector(`#input-13-${sucursalActual}`));
            actualizarDetalle(document.querySelector(`#input-16-${sucursalActual}`));
            actualizarDetalle(document.querySelector(`#input-17-${sucursalActual}`));
            actualizarDetalle(document.querySelector(`#input-14-${sucursalActual}`));
            
        }

        value = div.value.replace(/[$.]/g, "");
        value = parseInt(value.replace(/ /g,''));


        if(value < 0){

            div.value = "- $"+(parseNumber((value * -1),true)  )
            
        }else{

            div.value = "$"+parseNumber(value)
        }

    }

}

const parseNumber = (number,realValue = null) => {

    number = parseInt(number);

    let newNumber = number.toLocaleString('de-De', {
        style: 'decimal',
        maximumFractionDigits: 0,
        minimumFractionDigits: 0
    });
    if(realValue != true){

        if(newNumber < 0){
            return 0;
        }
        
    }
    return newNumber;

}

const insertarDetalle = () => {

    let tabla =document.querySelector("#tablaAlquileres");

    let inputs = tabla.querySelectorAll("input");
    let values = "";
    let periodo = document.querySelector("#periodo").textContent;
    let sucursales = document.querySelectorAll("#sucursal");


    inputs.forEach((e,x)=> {
        
        let data = e.id.split("-");
        let idConcepto = data[1];
        let idSucursal = data[2];
        let valor = e.value.replace(/[$.]/g, "");
        valor = parseFloat(valor).toFixed(2)

        sucursales.forEach(sucursal => {

            let infoSucursal =  sucursal.getAttribute("attr-infosuc").split("-")

            if(idSucursal == infoSucursal[1]) {

                values += `('${periodo}','${idSucursal}','${infoSucursal[0]}','${valor}','${idConcepto}'),`;

            }

        });

    });
    values = values.substring(0, values.length - 1);

    $.ajax({
        url: 'Controller/AlquilerController.php?accion=insertarDetalle',   
        method: 'POST',
        data: {
            values: values
        },
        success : function(data) {
                // console.log(data);
        }
    });

}

const actualizarDetalle = (div) => {

    let periodo = document.querySelector("#periodo").textContent;
    let sucursal = div.id.split("-")[2];
    let concepto = div.id.split("-")[1];

    let porcentaje = div.getAttribute("attr-porcentaje");

    let importe = div.value.replace(/[$.]/g, "");
    let importe9 = 0;
    let importe13 = 0;


    $.ajax({
        url: 'Controller/AlquilerController.php?accion=actualizarDetalle',   
        method: 'POST',
        data: {
            periodo:  periodo,
            sucursal: sucursal,
            concepto: concepto,
            importe:  importe,
            importe9: importe9,
            importe13: importe13,
            porcentaje: porcentaje
        },
        success : function(data) {
                // console.log(data);
        }
    });

}

const actualizarCargaAutomatica = (cerrado = 0) => {

    if(cerrado == 1){
        document.querySelectorAll("input").forEach(e => {
            // e.disabled = true;
        })
        return false;

    }

    let tabla =document.querySelector("#tablaAlquileres");

    let inputs = tabla.querySelectorAll("input");

    let periodo = document.querySelector("#periodo").textContent;

    inputs.forEach((e,x)=> {

        let data = e.id.split("-");
        let idConcepto = data[1];
        let valor = e.value.replace(/[$.]/g, "");
        valor = parseFloat(valor).toFixed(2);

        if(['6','7','9','13','14','15','16','17'].includes(idConcepto)) {

            if(valor > 0){
                actualizarDetalle(e);
            }

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
    // Ahora solo necesitamos enviar el período
    $.ajax({

        url: 'Controller/AlquilerController.php?accion=comprobarAjuste',
        method: 'POST',
        data: {
            periodo: periodo
        },
        success : function(data) {
            console.log('Respuesta comprobarAjuste:', data); // Debug
            
            if(data == 1){

                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'Debe aplicar el ajuste antes de procesar!'
                })
                
            }else{

                // VALIDACIÓN 3: Verificar que todos los totales sean mayores a 0
                let allTd = document.querySelectorAll("tr")[19].querySelectorAll("td");
                let error = false;


                for (let i = 0; i < allTd.length; i++) {

                    if(i >= 2){

                        let element = allTd[i];

                        let value = element.textContent.replace(/[$.]/g, "");

                
                        if(value == 0){

                            Swal.fire({
                                icon: 'warning',
                                title: 'Atención',
                                text: 'Complete los gastos de todas las sucursales!'
                            })
                            error = true;
                            break;

                        }


                    }
                };

                if(error == false){ 
                    $.ajax({
                        url: 'Controller/AlquilerController.php?accion=procesar',
                        method: 'POST',
                        data: {
                            periodo: periodo
                        },
                        success : function(data) {
                            try {
                                // Intentar parsear como JSON
                                const response = JSON.parse(data);
                                
                                if(response.status === 'error'){
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: response.message
                                    });
                                } else if(response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Procesado',
                                        text: response.message
                                    }).then((result) => {
                                        location.reload();
                                    });
                                }
                                
                            } catch (e) {
                                // Fallback para respuestas que no sean JSON (compatibilidad)
                                if(data == 1){
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
                        error: function(xhr, status, error) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error de conexión',
                                text: 'No se pudo procesar la solicitud. Error: ' + error
                            });
                        }
                    });
                }


            }
        }
    })


}

const cerrarPeriodo = () => {

    let periodo = document.querySelector("#periodo").textContent;
    let ultimaFechaDelMes = document.querySelector("#ultimaFechaDelMes").textContent;

    mesAnterior = document.querySelector("#mesAnterior").textContent;
    
    $.ajax({
        url : 'Controller/AlquilerController.php?accion=checkCierrePeriodoAnt',
        method: 'POST',
        data: {
            mesAnterior: mesAnterior
        },
        success : function(response) {
          response = JSON.parse(response)
             if(response[0]['RegistroExiste'] == 0){

                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'Debe cerrar los periodos anteriores!'
                })
                return 1
                
            }else{

                $.ajax({
                    url : 'Controller/AlquilerController.php?accion=verificarProcesado',
                    method: 'POST',
                    data: {
                        fecha: ultimaFechaDelMes
                    },
                    success : function(response) {  
                        response = JSON.parse(response);
                
                        if(response[0]['RegistroExiste'] == 1){
            
                            // Antes de cerrar, verificar si hay diferencias en los valores
                            verificarDiferenciasYCerrar(periodo);
            
                        }else{
            
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
        const nroSucursal = sucursal.textContent.trim();
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
        success: function(response) {
            const data = JSON.parse(response);
            
            if (data.tieneDiferencias && data.diferencias.length > 0) {
                mostrarDiferenciasYConfirmar(periodo, data.diferencias);
            } else {
                // No hay diferencias, proceder al cierre normal
                ejecutarCierrePeriodo(periodo, false);
            }
        },
        error: function(xhr, status, error) {
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
        const nroSucursal = sucursal.textContent.trim();
        
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
        success: function(data) {
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
        error: function(xhr, status, error) {
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
        success : function(data) {
            console.log('Respuesta abrirPeriodo:', data); // Debug
            
            // El controlador devuelve 0 para éxito, 1 para error
            if(data == 0){
                Swal.fire({
                    icon: 'success',
                    title: 'Abierto',
                    text: 'Se ha abierto correctamente el período!'
                }).then((result) => {
                    location.reload();
                })
            }else{
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al abrir el período!'
                })
            }
        },
        error: function(xhr, status, error) {
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
                success: function(data) {
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
                error: function(xhr, status, error) {
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

        url : 'Controller/AlquilerController.php?accion=ocultarSucursal',
        method: 'POST',
        data: {
            sucursal: sucursal,
            periodo: periodo
        },
        success : function(response) {

            document.querySelectorAll(".suc"+sucursal).forEach(element => {
                element.remove()
        });
        
        }

    })

   
}

const AplicarAjuste = () => {
    let sucursales = document.querySelectorAll("#sucursal");
    let newArray = {};
    let periodo = document.querySelector("#periodo").textContent;
    
    // LOG: Ver qué período se está enviando
    console.log("DEBUG AplicarAjuste - Período desde #periodo:", periodo);
    console.log("DEBUG AplicarAjuste - Tipo de dato:", typeof periodo);

    sucursales.forEach((sucursal) => {
        const sucursalName = sucursal.textContent;
        newArray[sucursalName] = [];

        ["4", "5", "18"].forEach((concepto) => {
            const div = document.querySelector(`#input-${concepto}-${sucursalName}`);
            if (!div) {
                console.log(`DEBUG - Input concepto ${concepto} sucursal ${sucursalName} NO ENCONTRADO`);
                return;
            }
            
            const valor = div.value.replace(/[$.]/g, "").replace(/-/g, "").trim();
            const estaDeshabilitado = div.disabled;

            console.log(`DEBUG - Concepto ${concepto} Sucursal ${sucursalName}: valor="${valor}", disabled=${estaDeshabilitado}, valorOriginal="${div.value}"`);

            // Solo incluir si NO está deshabilitado y tiene valor > 0
            if (valor > 0 && !estaDeshabilitado) {
                console.log(`  → INCLUIDO`);
                newArray[sucursalName].push({
                    concepto: concepto,
                    value: valor,
                });
            } else {
                console.log(`  → OMITIDO (valor=${valor}, disabled=${estaDeshabilitado})`);
            }
        });
    });

    console.log("DEBUG AplicarAjuste - Array de datos a enviar:", newArray);

    $.ajax({
        url: 'Controller/AlquilerController.php?accion=aplicarAjuste',
        method: 'POST',
        data: {
            arrayData: JSON.stringify(newArray),  // Convertir a JSON string
            periodo: periodo
        },
        success: function (response) {
            console.log("DEBUG AplicarAjuste - Respuesta del servidor:", response);
            
            try {
                // Intentar parsear como JSON
                const data = JSON.parse(response);
                
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '✓ Ajuste aplicado correctamente',
                        html: `<div style="text-align: center;">
                                <i class="fas fa-check-circle" style="font-size: 48px; color: #28a745; margin-bottom: 15px;"></i>
                                <p>Se actualizaron <strong>${data.registros_actualizados}</strong> registro(s)</p>
                                <p>Coeficiente aplicado: <strong>${data.coeficiente}</strong></p>
                              </div>`,
                        showConfirmButton: true,
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        location.reload();
                    });
                } else if (data.status === 'info') {
                    Swal.fire({
                        icon: 'info',
                        title: 'ℹ️ Información',
                        html: `<div style="text-align: center;">
                                <i class="fas fa-info-circle" style="font-size: 48px; color: #17a2b8; margin-bottom: 15px;"></i>
                                <p>${data.message}</p>
                              </div>`,
                        confirmButtonText: 'Entendido'
                    });
                } else if (data.status === 'warning') {
                    Swal.fire({
                        icon: 'warning',
                        title: '⚠️ Atención',
                        html: `<div style="text-align: center;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #ffc107; margin-bottom: 15px;"></i>
                                <p>${data.message}</p>
                              </div>`,
                        confirmButtonText: 'Entendido'
                    });
                } else if (data.status === 'error') {
                    Swal.fire({
                        icon: 'error',
                        title: '✖ Error',
                        html: `<div style="text-align: center;">
                                <i class="fas fa-times-circle" style="font-size: 48px; color: #dc3545; margin-bottom: 15px;"></i>
                                <p>${data.message}</p>
                              </div>`,
                        confirmButtonText: 'Aceptar'
                    });
                }
                
            } catch (e) {
                console.error("DEBUG AplicarAjuste - Error al parsear JSON:", e);
                console.log("DEBUG AplicarAjuste - Respuesta cruda:", response);
                
                // Fallback para compatibilidad con respuesta legacy
                if(response != 1){
                    Swal.fire({
                        icon: 'error',
                        title: 'Error...',
                        text: 'El coeficiente correspondiente al período no se encuentra cargado!'
                    });
                } else {
                    Swal.fire({
                        icon: 'success',
                        title: 'Ajuste aplicado correctamente!',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                }
            }
        },
        error: function(xhr, status, error) {
            console.error("DEBUG AplicarAjuste - Error en AJAX:", {xhr, status, error});
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo aplicar el ajuste. Error: ' + error
            });
        }
    });
};


const cambiarEntorno = (t) => {
    let entorno = 0;
    
    // Si el checkbox está checked = Argentina (central = 0)
    // Si el checkbox NO está checked = Uruguay (uy = 1)
    if(t.checked){
        entorno = 0; // Argentina
    } else {
        entorno = 1; // Uruguay
    }
  
    $.ajax({
        url: "Controller/cambiarEntorno.php",
        method: "POST",
        data: {entorno: entorno},
        success: function (data) {
            location.reload();
        },
        error: function(xhr, status, error) {
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