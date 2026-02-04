
let btnUpdateDetalle = document.querySelector("#btnUpdateDetalle");
let btnAgregarDetalle = document.querySelector("#btnAgregarDetalle");

btnUpdateDetalle.addEventListener("click",()=> {
    let arrayDatos = [];
    let idEncabezado = document.querySelector("#idEncabezado").getAttribute("attr-value");
    idEncabezado = '('+idEncabezado+')';
    let nroOrden = document.querySelector("#nroOrden").textContent;
   
    let table = document.querySelector("#table")
    let rows = table.querySelectorAll("tr:not(:last-child)")
  
rows.forEach((x,e)=>{
  // Usar querySelector para obtener el input dentro del td
  let gastos = x.querySelectorAll("td")[1].querySelector("input").value;
  let idDetalle = x.querySelectorAll("td")[0].getAttribute('attr-value')
  
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

      arrayDatos [e] = [gastos, importeEnDolares, tipoCambio, importeEnPesos, sobreFob, observaciones, idDetalle]
    })

    $.ajax({
      url: '/administracion/comercioExterior/Controller/OrdenDeCompraController.php',
      method: 'POST',
      data:{
        "array": arrayDatos, 
        "idEncabezado": idEncabezado
      },
    })
    .done(function(e) {
      $.ajax({
        url: '/administracion/comercioExterior/class/Orden.php',
        method: 'POST',
        data:{
          "nroOrdenDeCompra": nroOrden
        },
      });

      Swal.fire({
        title: 'Detalle guardado!',
        icon: 'success',
        showDenyButton: true,
        showCancelButton: false,
        showConfirmButton: false,
        denyButtonText: `Volver`,
        })
        .then((e) => {
          window.location = "/administracion/comercioExterior/tabs/costoNacionalizacion.php"
        })
    })
})

btnAgregarDetalle.addEventListener("click",()=>{
  let rows = document.querySelectorAll("#id");
  let original = rows[rows.length - 1].parentElement;
  let lastId =  Number(original.childNodes[1].textContent) + 1 
  
  let text = `
    <tr>
    <td id ="id">${lastId}</td>
    <td><input type="text" style ="text-align:center"></td>
    <td><input class="decimales currencyInput" style="text-align:center" type="text" id="valorFobDolar" onkeyup="iniciarCalculo(this)" value = "" onblur="window.formatearInput(this)"></input></td>
    <td><input class="decimales currencyInput tipoCambio" style="text-align:center" type="text"  onkeyup="iniciarCalculo(this)" id="tipoCambio" value="0,00" onblur="window.formatearInput(this)"></input></td>
    <td><input class="decimales currencyInput importe" style="text-align:center" type="text" id="valorFobPeso" name="inputNum[]" readonly value="0,00"></input></td>
    <td><input style="text-align:center" value="0,00%" readonly></input></td>
    <td><input></input></td>
    <td><button type="button" class="btn btn-danger" onclick="borrarGasto(this)">X</button></td>
    </tr>
  `;

  original.insertAdjacentHTML("afterend",text);
  
  // Agregar event listeners a los nuevos inputs
  let newRow = original.nextElementSibling;
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