document.addEventListener("DOMContentLoaded", iniciarEscucha);
let Gastos = document.getElementById("totalGastos");


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

let sacarParseo = (string,isNumber = false) => {
  numero  = string;
  if(isNumber == false){
    numero = convertToNumber(string);
  }
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

if(document.querySelector("#btnSaveDetalle") != null){

  let btnSave = document.querySelector("#btnSaveDetalle");


  btnSave.addEventListener("click",()=>{
    let rows = document.querySelectorAll("#id");
    let arrayDatos = [];
    let idEncabezado = document.querySelector("#idEncabezado").getAttribute("attr-value");
    rows.forEach((e , x) => {

    let rowsElement = e.parentElement;
    let Gastos = rowsElement.childNodes[3].textContent;
    let importeEnDolares = sacarParseo(rowsElement.childNodes[5].childNodes[0].value,true);
    let tipoCambio = sacarParseo(rowsElement.childNodes[7].childNodes[0].value,true);
    let importeEnPesos = sacarParseo(rowsElement.childNodes[9].childNodes[0].value,true);
    let sobreFob = rowsElement.childNodes[11].childNodes[0].textContent.replace("%","");
    let observaciones = rowsElement.childNodes[13].childNodes[0].value

    arrayDatos [x] = [Gastos,importeEnDolares,tipoCambio,importeEnPesos,sobreFob,observaciones]

    });

    arrayDatos[16] = idEncabezado;
    $.ajax({
      url: 'Controller/insertarDetalle.php',
      method: 'POST',
      data:{
        "array":arrayDatos
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
}