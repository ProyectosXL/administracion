
document.addEventListener("DOMContentLoaded", functionInitial);
let Gastos = document.getElementById("totalGastos");

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

const iniciarCalculo = (div)=>{
  let table = document.querySelector("#table")
  let total = 0;

  if( div.value == '' ){
    div.parentElement.parentElement.querySelectorAll("td")[4].firstChild.value = '';
    div.parentElement.parentElement.querySelectorAll("td")[5].firstChild.value = '';
    return
  }

  let tipoCambio = 0;
  let valorFobUsd = 0;

  // Obtener valores y convertirlos a números
  let tipoCambioInput = div.parentElement.parentElement.querySelectorAll("td")[3].firstChild;
  let valorFobUsdInput = div.parentElement.parentElement.querySelectorAll("td")[2].firstChild;
  
  tipoCambio = window.convertirANumero(tipoCambioInput.value);
  valorFobUsd = window.convertirANumero(valorFobUsdInput.value);

  console.log(valorFobUsd,"valorFobUsd", tipoCambio,"tipoCambio")

  if(valorFobUsd == 0){
    return 
  }
  
  if(tipoCambio == 0){
    total = valorFobUsd
  } else {
    total = valorFobUsd * tipoCambio;
  }
  
  console.log(total,"total")

  // Actualizar el campo de importe en pesos con formato
  div.parentElement.parentElement.querySelectorAll("td")[4].firstChild.value = window.formatearNumero(total);

  let sobreFob = div.parentElement.parentElement.querySelectorAll("td")[5];
  let importeEnPesos = total;

  // Obtener el valor FOB en pesos correctamente - AHORA DIRECTAMENTE COMO NÚMERO
  let valorPesosFobElement = document.querySelector("#valorPesosFob");
  let valorPesosFobNumerico = parseFloat(valorPesosFobElement.getAttribute("attr-value"));
  
  console.log('Valor FOB en pesos (corregido):', valorPesosFobNumerico, 'Importe en pesos:', importeEnPesos);

  let result = (importeEnPesos / valorPesosFobNumerico) * 100;
  let resultFormatted = window.formatearNumero(result);
  sobreFob.firstChild.value = resultFormatted + "%";

  totalGastos();
}

const totalGastos = ()=> {
  let total = document.querySelector("#totalGastosDetalle");
  let totalResult = document.querySelector("#totalGastosDetalleR");
  let importes = document.querySelectorAll('.importe');
  let sum = 0;
  let porcentajeSpan = document.querySelector("#porcentaje");
  
  // CORRECCIÓN: Obtener el valor FOB directamente como número del attr-value
  let valorPesosFobElement = document.querySelector("#valorPesosFob");
  let valorPesosFobNumerico = parseFloat(valorPesosFobElement.getAttribute("attr-value"));
  
  // Debug: Verificar el valor que se está leyendo
  console.log('DEBUG - attr-value leído:', valorPesosFobElement.getAttribute("attr-value"));
  console.log('DEBUG - valor numérico convertido:', valorPesosFobNumerico);
  
  importes.forEach(importe=>{
    if(importe.value !== ''){
      let valor = window.convertirANumero(importe.value);
      sum += valor;
    }
  });

  let sumFormateada = window.formatearNumero(sum);
  total.textContent = 'Gastos: $' + sumFormateada;
  total.setAttribute("attr-value", sum); // Guardar el valor numérico
  totalResult.textContent = sumFormateada;

  console.log('DEBUG - Total gastos:', sum, 'Valor FOB numérico:', valorPesosFobNumerico);

  if(valorPesosFobNumerico > 0) {
    let result = (sum / valorPesosFobNumerico) * 100;
    let numberResult = window.formatearNumero(result);
    
    // Debug adicional para verificar el cálculo
    console.log('DEBUG - Cálculo: (' + sum + ' / ' + valorPesosFobNumerico + ') * 100 = ' + result);
    console.log('DEBUG - Resultado formateado:', numberResult + '%');
    
    porcentajeSpan.textContent = numberResult + "%";
    porcentajeSpan.setAttribute("attr-value", result.toFixed(2));
  } else {
    console.log('DEBUG - Valor FOB es 0 o inválido');
    porcentajeSpan.textContent = "0,00%";
    porcentajeSpan.setAttribute("attr-value", "0");
  }
} 

if(document.querySelector("#btnSaveDetalle") != null){
  let btnSave = document.querySelector("#btnSaveDetalle");
  
  btnSave.addEventListener("click",()=>{
    let rows = document.querySelectorAll("#id");
    let arrayDatos = [];
    let idEncabezado = document.querySelector("#idEncabezado").getAttribute("attr-value");

    let nroOrdenDeCompra = document.querySelector("#nroOrdenCompra").textContent;
  
    rows.forEach((e , x) => {
      let rowsElement = e.parentElement;
      let Gastos = rowsElement.childNodes[3].textContent;
      let importeEnDolares = window.convertirANumero(rowsElement.childNodes[5].childNodes[0].value);
      let tipoCambio = window.convertirANumero(rowsElement.childNodes[7].childNodes[0].value);
      let importeEnPesos = window.convertirANumero(rowsElement.childNodes[9].childNodes[0].value);
      let sobreFob = rowsElement.childNodes[11].childNodes[0].value.replace("%","");
      sobreFob = window.convertirANumero(sobreFob);
      let observaciones = rowsElement.childNodes[13].childNodes[0].value

      console.log(Gastos,importeEnDolares,tipoCambio,importeEnPesos,sobreFob,observaciones)

      arrayDatos [x] = [Gastos,importeEnDolares,tipoCambio,importeEnPesos,sobreFob,observaciones]
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
          })
          .then((e) => {
            window.location = "index.php"  
          })
    })
  })
}

// Agregar event listeners para formatear automáticamente
document.addEventListener('DOMContentLoaded', function() {
    // Agregar formateo automático a todos los inputs de currency
    document.querySelectorAll('.currencyInput').forEach(input => {
        input.addEventListener('blur', function() {
            window.formatearInput(this);
        });
        
        // También formatear cuando cambie el valor y no esté enfocado
        input.addEventListener('change', function() {
            if (this !== document.activeElement) {
                window.formatearInput(this);
            }
        });
    });
});