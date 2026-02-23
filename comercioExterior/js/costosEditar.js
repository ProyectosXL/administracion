document.addEventListener("DOMContentLoaded", function() {
    console.log("DOM cargado - inicializando...");
    functionInitial();
    
    // Inicializar cálculos para filas existentes
    setTimeout(function() {
        inicializarCalculosExistentes();
    }, 500);
});

let Gastos = document.getElementById("totalGastos");

function functionInitial() {
    console.log("Inicializando función...");
    totalGastos();
    inicializarEventos();
}

function inicializarCalculosExistentes() {
    console.log("Inicializando cálculos existentes...");
    document.querySelectorAll("table tr:not(:first-child)").forEach(row => {
        let cells = row.querySelectorAll("td");
        if (cells.length >= 6) {
            let importeUsdInput = cells[2].querySelector("input");
            if (importeUsdInput && importeUsdInput.value && importeUsdInput.value !== '0,00') {
                console.log("Recalculando fila con importe USD:", importeUsdInput.value);
                iniciarCalculo(importeUsdInput);
            }
        }
    });
}

function inicializarEventos() {
    console.log("Inicializando eventos...");
    
    // Eventos para inputs de IMPORTE USD
    document.querySelectorAll("table tr:not(:first-child)").forEach(row => {
        let cells = row.querySelectorAll("td");
        if (cells.length >= 4) {
            let importeUsdInput = cells[2].querySelector("input");
            let tipoCambioInput = cells[3].querySelector("input");
            
            if (importeUsdInput) {
                importeUsdInput.addEventListener('input', function() {
                    iniciarCalculo(this);
                });
                importeUsdInput.addEventListener('blur', function() {
                    window.formatearInput(this);
                    iniciarCalculo(this);
                });
            }
            
            if (tipoCambioInput) {
                tipoCambioInput.addEventListener('input', function() {
                    iniciarCalculo(this);
                });
                tipoCambioInput.addEventListener('blur', function() {
                    window.formatearInput(this);
                    iniciarCalculo(this);
                });
            }
        }
    });
    
    // Agregar botón de recálculo
    agregarBotonRecalcular();
}

const parseNumber = (value)=>{
    return value.toLocaleString('en-US', {
        style: 'decimal',
    });
}

const convertToNumber = (numero)=>{
    let newNumero1 = numero.replaceAll(",", "");
    return newNumero1.replace(".", ",");
}

const iniciarCalculo = (div)=>{
    console.log("Iniciando cálculo para:", div);
    
    if(!div || !div.value || div.value.trim() === '' || div.value === '0,00'){
        console.log("Div vacío o sin valor");
        return;
    }

    let row = div.closest('tr');
    if (!row) {
        console.error("No se encontró la fila");
        return;
    }

    let cells = row.querySelectorAll("td");
    if (cells.length < 6) {
        console.error("Fila incompleta");
        return;
    }

    // Obtener valores
    let importeUsdInput = cells[2].querySelector("input");
    let tipoCambioInput = cells[3].querySelector("input");
    let importePesosInput = cells[4].querySelector("input");
    let porcentajeInput = cells[5].querySelector("input");
    
    if (!importeUsdInput || !tipoCambioInput || !importePesosInput || !porcentajeInput) {
        console.error("No se encontraron todos los inputs necesarios");
        return;
    }

    let importeUsd = window.convertirANumero(importeUsdInput.value) || 0;
    let tipoCambio = window.convertirANumero(tipoCambioInput.value) || 0;
    
    console.log("Valores obtenidos - USD:", importeUsd, "TC:", tipoCambio);

    // Calcular importe en pesos
    // Si el tipo de cambio es 0 o vacío, multiplicar por 1 (usar el valor directo)
    let importePesos = 0;
    if (importeUsd > 0) {
        if (tipoCambio > 0) {
            importePesos = importeUsd * tipoCambio;
        } else {
            // Sin tipo de cambio, usar el valor directo (multiplicar por 1)
            importePesos = importeUsd * 1;
        }
    }
    
    console.log("Importe en pesos calculado:", importePesos);

    // Actualizar campo de importe en pesos
    if (importePesos > 0) {
        importePesosInput.value = window.formatearNumero(importePesos);
    } else {
        importePesosInput.value = '0,00';
    }

    // Calcular porcentaje sobre FOB
    let valorPesosFobElement = document.querySelector("#valorPesosFob");
    let valorFob = 0;
    
    if (valorPesosFobElement) {
        // Intentar obtener de diferentes formas
        if (valorPesosFobElement.getAttribute("attr-value")) {
            valorFob = parseFloat(valorPesosFobElement.getAttribute("attr-value"));
        } else if (valorPesosFobElement.textContent) {
            valorFob = window.convertirANumero(valorPesosFobElement.textContent);
        } else if (valorPesosFobElement.value) {
            valorFob = window.convertirANumero(valorPesosFobElement.value);
        }
    }
    
    console.log("Valor FOB para cálculo:", valorFob);
    
    if (valorFob > 0 && importePesos > 0) {
        let porcentaje = (importePesos / valorFob) * 100;
        porcentajeInput.value = window.formatearNumero(porcentaje) + "%";
    } else {
        porcentajeInput.value = "0,00%";
    }
    
    // Actualizar totales
    setTimeout(totalGastos, 100);
};

const totalGastos = ()=> {
    console.log("Calculando total gastos...");
    
    let totalElement = document.querySelector("#totalGastosDetalle");
    let totalResultElement = document.querySelector("#totalGastosDetalleR");
    let porcentajeSpan = document.querySelector("#porcentaje");
    
    if (!totalElement || !totalResultElement || !porcentajeSpan) {
        console.error("No se encontraron elementos para totales");
        return;
    }
    
    let sum = 0;
    
    // Sumar todos los importes en pesos (excluyendo la fila de totales)
    document.querySelectorAll("table tbody tr:not(.total-row)").forEach(row => {
        let cells = row.querySelectorAll("td");
        if (cells.length >= 5) {
            let importePesosInput = cells[4].querySelector("input");
            if (importePesosInput && importePesosInput.value && importePesosInput.value !== '0,00') {
                let valor = window.convertirANumero(importePesosInput.value);
                sum += valor;
                console.log("Sumando:", valor, "Total parcial:", sum);
            }
        }
    });
    
    console.log("Total gastos sumados:", sum);
    
    // Formatear y mostrar total
    let sumFormateada = window.formatearNumero(sum);
    totalElement.textContent = '$ ' + sumFormateada;
    totalElement.setAttribute("attr-value", sum);
    totalResultElement.textContent = '$ ' + sumFormateada;
    
    // Calcular porcentaje total
    let valorPesosFobElement = document.querySelector("#valorPesosFob");
    let valorFob = 0;
    
    if (valorPesosFobElement) {
        if (valorPesosFobElement.getAttribute("attr-value")) {
            valorFob = parseFloat(valorPesosFobElement.getAttribute("attr-value"));
        } else if (valorPesosFobElement.textContent) {
            valorFob = window.convertirANumero(valorPesosFobElement.textContent);
        }
    }
    
    console.log("Valor FOB para total:", valorFob, "Total gastos:", sum);
    
    if (valorFob > 0) {
        let porcentajeTotal = (sum / valorFob) * 100;
        porcentajeSpan.textContent = window.formatearNumero(porcentajeTotal) + "%";
        console.log("Porcentaje total calculado:", porcentajeTotal + "%");
    } else {
        porcentajeSpan.textContent = "0,00%";
    }
};

function agregarBotonRecalcular() {
    console.log("Intentando agregar botón de recálculo...");
    
    // Buscar el contenedor de botones
    let btnGuardar = document.querySelector("#btnSaveDetalle");
    
    if (btnGuardar) {
        console.log("Botón guardar encontrado:", btnGuardar);
        
        // Verificar si ya existe el botón de recálculo
        if (!document.querySelector("#btnRecalcular")) {
            let btnRecalcular = document.createElement("button");
            btnRecalcular.id = "btnRecalcular";
            btnRecalcular.type = "button";
            btnRecalcular.className = "btn btn-warning";
            btnRecalcular.textContent = "Recalcular Todo";
            btnRecalcular.style.marginRight = "10px";
            btnRecalcular.style.marginBottom = "10px";
            
            btnRecalcular.onclick = function() {
                console.log("Recalculando todo...");
                recalcularTodo();
            };
            
            // Insertar antes del botón guardar
            btnGuardar.parentNode.insertBefore(btnRecalcular, btnGuardar);
            console.log("Botón de recálculo agregado");
        }
    } else {
        console.error("No se encontró el botón de guardar");
        
        // Intentar encontrar otro lugar para poner el botón
        let container = document.querySelector(".container, .card-body, form");
        if (container) {
            let btnRecalcular = document.createElement("button");
            btnRecalcular.id = "btnRecalcular";
            btnRecalcular.type = "button";
            btnRecalcular.className = "btn btn-warning";
            btnRecalcular.textContent = "Recalcular Todo";
            btnRecalcular.style.marginRight = "10px";
            btnRecalcular.style.marginBottom = "10px";
            btnRecalcular.style.marginTop = "10px";
            
            btnRecalcular.onclick = function() {
                recalcularTodo();
            };
            
            container.appendChild(btnRecalcular);
            console.log("Botón de recálculo agregado al contenedor");
        }
    }
}

function recalcularTodo() {
    console.log("=== RECALCULANDO TODO ===");
    
    Swal.fire({
        title: 'Recalculando...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Recalcular cada fila
    document.querySelectorAll("table tr:not(:first-child)").forEach((row, index) => {
        let cells = row.querySelectorAll("td");
        if (cells.length >= 6) {
            console.log(`Recalculando fila ${index + 1}`);
            
            let importeUsdInput = cells[2].querySelector("input");
            if (importeUsdInput) {
                console.log(`  Importe USD: ${importeUsdInput.value}`);
                iniciarCalculo(importeUsdInput);
            }
        }
    });
    
    // Esperar un momento y actualizar totales
    setTimeout(() => {
        totalGastos();
        Swal.fire({
            title: '¡Recálculo completado!',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
        console.log("Recálculo finalizado");
    }, 500);
}

// Guardar datos
if(document.querySelector("#btnSaveDetalle") != null){
    let btnSave = document.querySelector("#btnSaveDetalle");
    
    btnSave.addEventListener("click", function() {
        console.log("=== INICIANDO GUARDADO ===");
        
        let rows = document.querySelectorAll("table tr:not(:first-child)");
        let arrayDatos = [];
        let idEncabezado = document.querySelector("#idEncabezado");
        idEncabezado = idEncabezado ? idEncabezado.getAttribute("attr-value") : null;

        let nroOrdenDeCompra = document.querySelector("#nroOrdenCompra");
        nroOrdenDeCompra = nroOrdenDeCompra ? nroOrdenDeCompra.textContent.trim() : '';
        
        console.log("ID Encabezado:", idEncabezado);
        console.log("Orden Compra:", nroOrdenDeCompra);
        console.log("Filas a procesar:", rows.length);
        
        rows.forEach((row, index) => {
            let cells = row.querySelectorAll("td");
            if (cells.length >= 7) {
                let gasto = cells[1].textContent || cells[1].innerText || "";
                let importeUsd = cells[2].querySelector("input") ? window.convertirANumero(cells[2].querySelector("input").value) : 0;
                let tipoCambio = cells[3].querySelector("input") ? window.convertirANumero(cells[3].querySelector("input").value) : 0;
                let importePesos = cells[4].querySelector("input") ? window.convertirANumero(cells[4].querySelector("input").value) : 0;
                
                let porcentajeCell = cells[5].querySelector("input");
                let porcentaje = 0;
                if (porcentajeCell && porcentajeCell.value) {
                    porcentaje = window.convertirANumero(porcentajeCell.value.replace("%", ""));
                }
                
                let observaciones = cells[6].querySelector("input") ? cells[6].querySelector("input").value : "";
                
                console.log(`Fila ${index + 1}:`, {
                    gasto,
                    importeUsd,
                    tipoCambio,
                    importePesos,
                    porcentaje,
                    observaciones
                });
                
                arrayDatos[index] = [gasto, importeUsd, tipoCambio, importePesos, porcentaje, observaciones];
            }
        });
        
        console.log("Datos a enviar:", arrayDatos);
        
        // Mostrar carga
        Swal.fire({
            title: 'Guardando...',
            text: 'Por favor espere',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Enviar datos
        $.ajax({
            url: '/administracion/comercioExterior/Controller/OrdenDeCompraController.php',
            method: 'POST',
            data: {
                "array": arrayDatos,
                "idEncabezado": idEncabezado
            },
            success: function(response) {
                console.log("Respuesta del servidor:", response);
                
                // Ejecutar SP
                $.ajax({
                    url: '/administracion/comercioExterior/Controller/ejecutarSpCostoNacionalizacion.php',
                    method: 'POST',
                    data: {
                        "nroOrdenDeCompra": nroOrdenDeCompra
                    },
                    success: function(spResponse) {
                        console.log("SP ejecutado:", spResponse);
                        Swal.fire({
                            title: '¡Guardado exitoso!',
                            icon: 'success',
                            showDenyButton: true,
                            showCancelButton: false,
                            confirmButtonText: 'Quedarse aquí',
                            denyButtonText: 'Volver al listado'
                        }).then((result) => {
                            if (result.isDenied) {
                                window.location = "index.php";
                            } else {
                                location.reload();
                            }
                        });
                    },
                    error: function(spError) {
                        console.error("Error en SP:", spError);
                        Swal.fire('Guardado parcial', 'Los datos se guardaron pero hubo un error al ejecutar el cálculo', 'warning');
                    }
                });
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Error AJAX:", textStatus, errorThrown);
                Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
            }
        });
    });
}