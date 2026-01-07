document.addEventListener("DOMContentLoaded", functionInitial);

function functionInitial() {
  totalGastos();
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

let sacarParseo = (string, isNumber = false) => {
  if(!string || string === ''){
    return 0;
  }
  numero = string;
  if(isNumber == false){
    numero = convertToNumber(string);
  }
  valor = numero.replaceAll(".","");
  valorEnFloat = valor.replaceAll(",",".");
  valor = parseFloat(valorEnFloat);
  return isNaN(valor) ? 0 : valor;
}

const iniciarCalculo = (div)=>{
  let table = document.querySelector("#table");
  let total = 0;

  if(div.value == ''){
    div.parentElement.parentElement.querySelectorAll("td")[4].firstChild.value = '';
    div.parentElement.parentElement.querySelectorAll("td")[5].firstChild.value = '';
    totalGastos();
    return;
  }

  let tipoCambio = 0;
  let valorFobUsd = 0;

  let row = div.parentElement.parentElement;
  
  if(div.classList.contains('tipoCambio')){
    // Si se está editando el tipo de cambio
    let tipoCambioStr = div.value.toString();
    tipoCambio = parseFloat(tipoCambioStr.replaceAll(".","").replaceAll(",","."));

    // Obtener el valor FOB USD de la misma fila
    let valorFobInput = row.querySelector('input#valorFobDolar');
    if(valorFobInput && valorFobInput.value && valorFobInput.value.trim() !== ''){
      let valorFobStr = valorFobInput.value.toString();
      valorFobUsd = parseFloat(valorFobStr.replaceAll(".","").replaceAll(",","."));
    } else {
      return; // Si no hay valor FOB, no calcular
    }
  } else {
    // Si se está editando el importe en dólares
    let valorFobStr = div.value.toString();
    valorFobUsd = parseFloat(valorFobStr.replaceAll(".","").replaceAll(",","."));

    // Obtener el tipo de cambio de la misma fila
    let tipoCambioInput = row.querySelector('input.tipoCambio');
    console.log('tipoCambioInput encontrado:', tipoCambioInput);
    if(tipoCambioInput && tipoCambioInput.value && tipoCambioInput.value.trim() !== ''){
      let tipoCambioStr = tipoCambioInput.value.toString();
      console.log('Valor del tipo cambio (string):', tipoCambioStr);
      tipoCambio = parseFloat(tipoCambioStr.replaceAll(".","").replaceAll(",","."));
    } else {
      tipoCambio = 0;
    }
  }

  console.log('Valores:', { valorFobUsd, tipoCambio, tipo: typeof valorFobUsd, tipoCambioTipo: typeof tipoCambio });

  if(isNaN(valorFobUsd) || valorFobUsd == 0){
    return;
  }

  if(isNaN(tipoCambio) || tipoCambio == 0 || tipoCambio == ''){
    total = valorFobUsd;
  } else {
    total = valorFobUsd * tipoCambio;
  }

  console.log('Total calculado:', total);

  // Actualizar el IMPORTE $
  let importePesosField = row.querySelector('input.importe');
  console.log('Campo importe $ encontrado:', importePesosField);
  if(importePesosField){
    importePesosField.value = parseFloat(total).toLocaleString('es-ES', { minimumFractionDigits: 2 });
    console.log('Valor asignado:', importePesosField.value);
  }

  // Calcular % SOBRE F.O.B.
  let sobreFobInputs = row.querySelectorAll('input');
  let sobreFobField = sobreFobInputs[3]; // El cuarto input de la fila
  let importeEnPesos = total;

  let valorPesosFob = document.querySelector("#valorPesosFob").getAttribute("attr-value");
  let valorPesosFobNumerico = parseFloat(valorPesosFob.replaceAll(".","").replaceAll(",","."));

  let result = 0;
  if (valorPesosFobNumerico && valorPesosFobNumerico > 0 && importeEnPesos > 0) {
    result = ((importeEnPesos / valorPesosFobNumerico) * 100);
  }
  
  if(sobreFobField){
    sobreFobField.value = isNaN(result) ? '0.00%' : (result.toFixed(2) + "%");
    console.log('% sobre FOB asignado:', sobreFobField.value);
  }

  totalGastos();
}

const totalGastos = ()=> {
  let total = document.querySelector("#totalGastosDetalle");
  let totalResult = document.querySelector("#totalGastosDetalleR");
  let importes = document.querySelectorAll('.importe');
  let sum = 0;
  let porcentajeSpan = document.querySelector("#porcentaje");
  let valorPesosFob = document.querySelector("#valorPesosFob").getAttribute("attr-value");

  importes.forEach(importe=>{
    if(importe.value !== ''){
      valor = importe.value.replaceAll(".","").replaceAll(",",".");
      sum = (parseFloat(sum)) + parseFloat(valor);
    }
  });

  total.textContent = 'Gastos: $' + sum.toLocaleString('es-ES', { minimumFractionDigits: 2 });
  total.setAttribute("attr-value", sum.toLocaleString('es-ES', { minimumFractionDigits: 2 }));
  totalResult.textContent = 'Gastos: $' + sum.toLocaleString('es-ES', { minimumFractionDigits: 2 });

  valor = valorPesosFob.replaceAll(".","");
  valor = valor.replaceAll(",",".");

  let valorFobNumerico = parseFloat(valor);
  let result = 0;
  
  if (valorFobNumerico && valorFobNumerico > 0 && sum > 0) {
    result = ((sum / valorFobNumerico) * 100);
  }
  
  let numberResult = isNaN(result) ? '0.00' : (parseFloat(result).toFixed(2));
  porcentajeSpan.textContent = numberResult;
  porcentajeSpan.setAttribute("attr-value", isNaN(result) ? '0.00' : result.toFixed(2));
}

if(document.querySelector("#btnSaveDetalle") != null){
  let btnSave = document.querySelector("#btnSaveDetalle");

  btnSave.addEventListener("click", ()=>{
    let rows = document.querySelectorAll("#id");
    let arrayDatos = [];
    let idEncabezado = document.querySelector("#idEncabezado").getAttribute("attr-value");
    let nroOrdenDeCompra = document.querySelector("#nroOrdenCompra").textContent;

    rows.forEach((e, x) => {
      let rowElement = e.closest('tr');
      let inputs = rowElement.querySelectorAll('input');
      
      let Gastos = rowElement.querySelectorAll('td')[1].textContent;
      let importeEnDolaresVal = inputs[0] ? inputs[0].value : '';
      let tipoCambioVal = inputs[1] ? inputs[1].value : '';
      let importeEnPesosVal = inputs[2] ? inputs[2].value : '';
      let sobreFobVal = inputs[3] ? inputs[3].value : '';
      let observacionesVal = inputs[4] ? inputs[4].value : '';
      
      let importeEnDolares = sacarParseo(importeEnDolaresVal, true);
      let tipoCambio = sacarParseo(tipoCambioVal, true);
      let importeEnPesos = sacarParseo(importeEnPesosVal, true);
      let sobreFob = sobreFobVal.replace("%","");
      let observaciones = observacionesVal;

      console.log(Gastos, importeEnDolares, tipoCambio, importeEnPesos, sobreFob, observaciones);

      arrayDatos[x] = [Gastos, importeEnDolares, tipoCambio, importeEnPesos, sobreFob, observaciones];
    });

    $.ajax({
      url: 'Controller/OrdenDeCompraController.php',
      method: 'POST',
      data:{
        "array": arrayDatos, 
        "idEncabezado": idEncabezado
      },
    }).then((e)=>{
      $.ajax({
        url: 'Controller/ejecutarSpCostoNacionalizacion.php',
        method: 'POST',
        data:{
          "nroOrdenDeCompra": nroOrdenDeCompra
        },
      });

      Swal.fire({
        title: 'Detalle guardado!',
        icon: 'success',
        showDenyButton: true,
        showCancelButton: false,
        showConfirmButton: false,
        denyButtonText: `Volver`,
      }).then((e) => {
        window.location = "index.php";
      });
    });
  });
}

const convertirNumeros = (input) =>{
  if(input.value == ''){
    return;
  }
  let valueI = input.value.replaceAll(',','.');
  input.value = parseFloat(valueI).toLocaleString('es-ES', { minimumFractionDigits: 2 });
}

const limpiarInput = (input) =>{
  input.value = '';
}
