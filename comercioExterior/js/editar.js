
let btnUpdateDetalle = document.querySelector("#btnUpdateDetalle");
let btnAgregarDetalle = document.querySelector("#btnAgregarDetalle");

btnUpdateDetalle.addEventListener("click",()=> {
    let arrayDatos = [];
    let idEncabezadoRaw = document.querySelector("#idEncabezado").getAttribute("attr-value");
    let idEncabezado = '(' + idEncabezadoRaw + ')';
    let nroOrden = document.querySelector("#nroOrden").textContent.trim();

    // Porcentaje calculado (guardado como decimal: 8.5% → 0.085)
    let porcentajeEl = document.querySelector("#porcentaje");
    let attrValue = porcentajeEl ? porcentajeEl.getAttribute("attr-value") : null;
    let costoNacDecimal = 0;
    if (attrValue !== null && !isNaN(parseFloat(attrValue))) {
        costoNacDecimal = parseFloat(attrValue) / 100;
    } else if (porcentajeEl) {
        // Fallback: leer del textContent (ej: "8,53%") 
        let txt = porcentajeEl.textContent.replace("%","").replace(",",".").trim();
        costoNacDecimal = parseFloat(txt) / 100 || 0;
    }
   
    let table = document.querySelector("#table");
    let rows = table.querySelectorAll("tr:not(:last-child)");
  
    rows.forEach((x, e) => {
        let gastos = x.querySelectorAll("td")[1].querySelector("input").value;
        let idDetalle = x.querySelectorAll("td")[0].getAttribute('attr-value');

        let importeDolaresInput = x.querySelectorAll("td")[2].querySelector("input");
        let importeEnDolares = importeDolaresInput ? window.convertirANumero(importeDolaresInput.value) : 0;

        let tipoCambioInput = x.querySelectorAll("td")[3].querySelector("input");
        let tipoCambio = tipoCambioInput ? window.convertirANumero(tipoCambioInput.value) : 0;

        let importePesosInput = x.querySelectorAll("td")[4].querySelector("input");
        let importeEnPesos = importePesosInput ? window.convertirANumero(importePesosInput.value) : 0;

        let sobreFobInput = x.querySelectorAll("td")[5].querySelector("input");
        let sobreFob = sobreFobInput && sobreFobInput.value ?
                       window.convertirANumero(sobreFobInput.value.replace("%","")) : 0;

        let observacionesInput = x.querySelectorAll("td")[6].querySelector("input");
        let observaciones = observacionesInput ? observacionesInput.value : "";

        arrayDatos[e] = [gastos, importeEnDolares, tipoCambio, importeEnPesos, sobreFob, observaciones, idDetalle];
    });

    $.ajax({
        url: '/administracion/comercioExterior/Controller/OrdenDeCompraController.php',
        method: 'POST',
        dataType: 'json',
        data: {
            "array": arrayDatos,
            "idEncabezado": idEncabezado,
            "nroOrdenDeCompra": nroOrden,
            "costoNac": costoNacDecimal
        },
    }).done(function(response) {
        if (response.success) {
            Swal.fire({
                title: '¡Costos guardados!',
                text: 'Los costos de nacionalización fueron registrados correctamente.',
                icon: 'success',
                confirmButtonText: 'Volver a Costos de Nacionalización',
                confirmButtonColor: '#198754',
                allowOutsideClick: false,
                allowEscapeKey: false,
            }).then(() => {
                window.location.href = "/administracion/comercioExterior/tabs/costoNacionalizacion.php";
            });
        } else {
            Swal.fire({
                title: 'Atención',
                text: response.message || 'Ocurrió un error al guardar.',
                icon: 'warning',
                confirmButtonText: 'Volver a Costos de Nacionalización',
                confirmButtonColor: '#ffc107',
                allowOutsideClick: false,
                allowEscapeKey: false,
            }).then(() => {
                window.location.href = "/administracion/comercioExterior/tabs/costoNacionalizacion.php";
            });
        }
    }).fail(function(xhr, status, error) {
        console.error('Error al guardar costos:', error, xhr.responseText);
        Swal.fire({
            title: 'Error',
            text: 'No se pudo conectar con el servidor. Por favor, intente nuevamente.',
            icon: 'error',
            confirmButtonText: 'Volver a Costos de Nacionalización',
            allowOutsideClick: false,
            allowEscapeKey: false,
        }).then(() => {
            window.location.href = "/administracion/comercioExterior/tabs/costoNacionalizacion.php";
        });
    });
})

btnAgregarDetalle.addEventListener("click",()=>{
  let rows = document.querySelectorAll("#id");
  let original = rows.length > 0 ? rows[rows.length - 1].parentElement : null;
  let lastId = original ? Number(original.childNodes[1].textContent) + 1 : 1;
  
  let text = `
    <tr>
    <td id ="id" class="cell-id"><span class="badge-info">${lastId}</span></td>
    <td><input type="text" class="input-field" style ="text-align:center"></td>
    <td><input class="input-field decimales currencyInput" style="text-align:center" type="text" id="valorFobDolar" onkeyup="iniciarCalculo(this)" value = "" onblur="window.formatearInput(this)"></input></td>
    <td><input class="input-field decimales currencyInput tipoCambio" style="text-align:center" type="text"  onkeyup="iniciarCalculo(this)" id="tipoCambio" value="0,00" onblur="window.formatearInput(this)"></input></td>
    <td><input class="input-field decimales currencyInput importe" style="text-align:center" type="text" id="valorFobPeso" name="inputNum[]" readonly value="0,00"></input></td>
    <td><input class="input-field" style="text-align:center" value="0,00%" readonly></input></td>
    <td><input class="input-field"></input></td>
    <td class="action-cell"><button type="button" class="btn-delete" onclick="borrarGasto(this)"><i class="bi bi-trash"></i></button></td>
    </tr>
  `;

  let newRow;
  if (original) {
    original.insertAdjacentHTML("afterend",text);
    newRow = original.nextElementSibling;
  } else {
    let tbody = document.querySelector("#table");
    tbody.insertAdjacentHTML("afterbegin",text);
    newRow = tbody.firstElementChild;
  }
  
  // Agregar event listeners a los nuevos inputs
  let newInputs = newRow.querySelectorAll('.currencyInput');
  newInputs.forEach(input => {
    input.addEventListener('blur', function() {
      if(this.value !== ''){
        window.formatearInput(this);
      }
    });
  });
})

const borrarGasto = (e)=>{
  let row = e.parentElement.parentElement
  row.remove()
  // Recalcular totales después de borrar
  if(typeof totalGastos === 'function') {
    totalGastos();
  }
}

// Inicializar formateo para inputs existentes
document.addEventListener('DOMContentLoaded', function() {
    // Agregar formateo automático a todos los inputs de currency existentes
    document.querySelectorAll('.currencyInput').forEach(input => {
        input.addEventListener('blur', function() {
            if(this.value !== '' && this.value !== '0'){
                window.formatearInput(this);
            }
        });
    });
});