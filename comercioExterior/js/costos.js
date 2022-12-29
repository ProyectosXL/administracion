document.addEventListener("DOMContentLoaded", iniciarEscucha);
let Gastos = document.getElementById("totalGastos");
let btnSave = document.querySelector("#btnSaveDetalle");

function iniciarEscucha() {
  /* Gastos.value=0; */
  let inputTipoCambio = document.querySelectorAll(".tipoCambio");
  inputTipoCambio.forEach((input) => {
    input.addEventListener("keyup", calcular);
  });
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

//Calcula valor FOB en pesos según cotización de tipoCambio//
function calcular(e) {
  let tipoCambio = parseFloat(e.target.value);
  
  let valorFobDolar = parseFloat(
    e.target.parentElement.parentElement.children[2].children[0].value
  );
  
  let valorFobPeso = tipoCambio * valorFobDolar;
  if (tipoCambio == "") {
    e.target.parentElement.parentElement.children[4].children[0].value =
    parseFloat(valorFobDolar);
  } else {
    e.target.parentElement.parentElement.children[4].children[0].value =
    parseFloat(valorFobPeso);
  }
  calcularTotales();

}


function calcularTotales()
{
    let importes=document.querySelectorAll('.importe');
    let sum=0;
    importes.forEach(importe=>{
        if(importe.value!==''){
        sum = (parseFloat(sum))+parseFloat(importe.value)
}});
    concat = 'Gastos: $'+parseNumber(sum);
    // 'Gastos:'+new Intl.NumberFormat("es-ar",{style: "currency", currency: "ARS", minimumFractionDigits: 0}).format(sum);
    Gastos.textContent=concat;
    
}

let sacarParseo = (string) => {
  numero = convertToNumber(string);
  valorEnFloat = numero.replace(",",".");
  valor = parseFloat(valorEnFloat);

  return valor;
}

const iniciarCalculo = (data)=>{

  let tipoCambio = parseFloat(data.parentElement.parentElement.childNodes[7].childNodes[0].value)
  
  let valorFobDolar = (data.value);
  let NumberFobDolar = valorFobDolar.replace(",",".");
  let valorFobPeso = NumberFobDolar * tipoCambio;

  if( tipoCambio == 0 ){
    valorFobPeso = valorFobDolar
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