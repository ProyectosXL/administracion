document.addEventListener("DOMContentLoaded", iniciarEscucha);
let Gastos = document.getElementById("totalGastos");
let btnSave = document.querySelector("#btnSaveDetalle");

function iniciarEscucha() {
  /* Gastos.value=0; */
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

let sacarParseo = (string) => {
  numero = convertToNumber(string);
  valorEnFloat = numero.replace(",",".");
  valor = parseFloat(valorEnFloat);

  return valor;
}

const iniciarCalculo = (data)=>{

  data = data.parentElement.parentElement.childNodes[5].childNodes[0];
  let tipoCambio = (data.parentElement.parentElement.childNodes[7].childNodes[0].value)
  tipoCambio = tipoCambio.replace(",",".");
  tipoCambio = parseFloat(tipoCambio);

  let valorFobDolar = (data.value);

  let NumberFobDolar = valorFobDolar.replace(",",".");
  let valorFobPeso = NumberFobDolar * tipoCambio;

  if( tipoCambio == 0 ){
    valorFobPeso = valorFobDolar
    valorFobPeso = valorFobPeso.replace(",",".");
    valorFobPeso = parseFloat(valorFobPeso);
  };
  

  data.parentElement.parentElement.childNodes[9].childNodes[0].value = parseFloat(valorFobPeso).toFixed(2);

  totalGastos();
  calcularSobreFob(data);

}

const totalGastos = ()=> {

  let total = document.querySelector("#totalGastosDetalle");
  let totalResult = document.querySelector("#totalGastosDetalleR");
  let importes = document.querySelectorAll('.importe');
  let sum = 0;
  let porcentajeSpan = document.querySelector("#porcentaje");
  let valorPesosFob = document.querySelector("#valorPesosFob").getAttribute("attr-value");

  importes.forEach(importe=>{

    if(importe.value!==''){
      sum = (parseFloat(sum))+parseFloat(importe.value)
    }

  });

  total.textContent = 'Gastos: $'+ parseNumber(sum);
  total.setAttribute("attr-value", parseNumber(sum))
  totalResult.textContent ='Gastos: $'+ parseNumber(sum);

  valor = sacarParseo(valorPesosFob);

  let result = ((sum / valor)*100)
  let numberResult = (parseFloat(result).toFixed(2));
  porcentajeSpan.textContent = numberResult + "%";
  porcentajeSpan.setAttribute("attr-value",result.toFixed(2));

} 


const calcularSobreFob = (data)=>{

  let sobreFob = data.parentElement.parentElement.childNodes[11];
  let importeEnPesos = parseFloat(data.parentElement.parentElement.childNodes[9].childNodes[0].value);

  let valorPesosFob = document.querySelector("#valorPesosFob").getAttribute("attr-value");

  valor = sacarParseo(valorPesosFob)

  let result = ((importeEnPesos / valor)*100) ;
  sobreFob.textContent = result.toFixed(2) + "%";

}

btnSave.addEventListener("click",()=>{

  let importeEnDolares =  document.querySelector("#valorPesosFob").getAttribute("attr-value") ;
  let importeTotalDolares = sacarParseo(importeEnDolares);

  let tipoCambio = document.querySelector(".tipoCambio").value;

  let importeEnPesos = document.querySelector("#totalGastosDetalle").getAttribute("attr-value");
  let importeTotalEnPesos = sacarParseo(importeEnPesos);

  let porcentaje = document.querySelector("#porcentaje").getAttribute("attr-value");
  let idEncabezado = document.querySelector("#idEncabezado").getAttribute("attr-value");
  
  $.ajax({
    url: 'Controller/insertarDetalle.php',
    method: 'POST',
    data: {
      importeEnDolares:importeTotalDolares,
      tipoCambio:tipoCambio,
      importeEnPesos:importeTotalEnPesos,
      porcentaje:porcentaje,
      idEncabezado:idEncabezado
      
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

})