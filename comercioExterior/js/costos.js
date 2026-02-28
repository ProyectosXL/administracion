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

let sacarParseo = (string,isNumber = false) => {
  numero  = string;
  if(isNumber == false){
    numero = convertToNumber(string);
  }
  valor = numero.replaceAll(".","");
  valorEnFloat = valor.replaceAll(",",".");
  valor = parseFloat(valorEnFloat);

  return valor;
}

const iniciarCalculo = (div)=>{
 
  let table = document.querySelector("#table")
    

      total = 0;

      if( div.value == '' ){
        div.parentElement.parentElement.querySelectorAll("td")[4].firstChild.value = '';
        div.parentElement.parentElement.querySelectorAll("td")[5].firstChild.value = '';
        return
      }

      let tipoCambio = 0;
      let valorFobUsd = 0;

      if(div.id == 'tipoCambio'){
          tipoCambio = div.value;
          if(div.value.includes(",")){
            tipoCambio = sacarParseo(div.value,true);
          }

          valorFobUsd = div.parentElement.parentElement.querySelectorAll("td")[2].firstChild.value.replaceAll(".","").replaceAll(",",".");
      }else{
          valorFobUsd = div.value;
          if(div.value.includes(",")){
            valorFobUsd = sacarParseo(div.value,true);
          }
          tipoCambio = div.parentElement.parentElement.querySelectorAll("td")[3].firstChild.value.replaceAll(".","").replaceAll(",",".");
      }
      // console.log(valorFobUsd,"valorFobUsd", tipoCambio,"tipoCambio")

      if(valorFobUsd == ''){
        return 
      }
      if(tipoCambio == 0){
        total = valorFobUsd

      }else{
      
        total = valorFobUsd * tipoCambio;
      }
      // let totalResult = parseFloat(total).toLocaleString('es-ES', { minimumFractionDigits: 2 },"totalp")

      div.parentElement.parentElement.querySelectorAll("td")[4].firstChild.value = parseFloat(total).toLocaleString('es-ES', { minimumFractionDigits: 2 });

      let sobreFob = div.parentElement.parentElement.querySelectorAll("td")[5];
      let importeEnPesos = div.parentElement.parentElement.querySelectorAll("td")[4].firstChild.value.replaceAll(".","").replaceAll(",",".");
      

      let valorPesosFob = document.querySelector("#valorPesosFob").getAttribute("attr-value");

      valor = valorPesosFob.replaceAll(".","").replaceAll(",",".");


      let result = ((parseFloat(importeEnPesos) / parseFloat(valor))*100) ;
      sobreFob.firstChild.value = result.toFixed(2) + "%";

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

    if(importe.value!==''){

      valor = importe.value.replaceAll(".","").replaceAll(",",".");
      sum = (parseFloat(sum))+parseFloat(valor)

    }

  });

  total.textContent = 'Gastos: $'+ sum.toLocaleString('es-ES', { minimumFractionDigits: 2 });
  total.setAttribute("attr-value", sum.toLocaleString('es-ES', { minimumFractionDigits: 2 }));
  totalResult.textContent ='Gastos: $'+ sum.toLocaleString('es-ES', { minimumFractionDigits: 2 });

  valor = valorPesosFob.replaceAll(".","");
  console.log(valor, sum)
  valor = valor.replaceAll(",",".");

  let result = ((sum / parseFloat(valor))*100)
  let numberResult = (parseFloat(result).toFixed(2));
  porcentajeSpan.textContent = numberResult + "%";
  porcentajeSpan.setAttribute("attr-value",result.toFixed(2));

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
    let importeEnDolares = sacarParseo(rowsElement.childNodes[5].childNodes[0].value,true);
    let tipoCambio = sacarParseo(rowsElement.childNodes[7].childNodes[0].value,true);
    let importeEnPesos = sacarParseo(rowsElement.childNodes[9].childNodes[0].value,true);
    let sobreFob = rowsElement.childNodes[11].childNodes[0].textContent.replace("%","");
    let observaciones = rowsElement.childNodes[13].childNodes[0].value

    console.log(Gastos,importeEnDolares,tipoCambio,importeEnPesos,sobreFob,observaciones)

    arrayDatos [x] = [Gastos,importeEnDolares,tipoCambio,importeEnPesos,sobreFob,observaciones]

    });
    

    $.ajax({
      url: '/administracion/comercioExterior/Controller/OrdenDeCompraController.php',
      method: 'POST',
      dataType: 'json',
      data:{
        "array": arrayDatos,
        "idEncabezado": '(' + idEncabezado + ')',
        "nroOrdenDeCompra": nroOrdenDeCompra.trim(),
        "costoNac": (parseFloat(document.querySelector("#porcentaje").getAttribute("attr-value")) || 0) / 100
      },
    }).done(function(response) {
      if (response.success) {
        Swal.fire({
          title: '¡Costos guardados!',
          text: 'Los costos de nacionalización fueron registrados correctamente.',
          icon: 'success',
          confirmButtonText: 'Volver a Gestión de Despachos',
          confirmButtonColor: '#198754',
          allowOutsideClick: false,
          allowEscapeKey: false,
        }).then(() => {
          window.top.location.href = "/administracion/comercioExterior/index.php";
        });
      } else {
        Swal.fire('Atención', response.message || 'Ocurrió un error al guardar.', 'warning');
      }
    }).fail(function(xhr, status, error) {
      console.error('Error al guardar costos:', error, xhr.responseText);
      Swal.fire('Error', 'No se pudo conectar con el servidor. Por favor, intente nuevamente.', 'error');
    });



  })
}



const convertirNumeros = (input) =>{
    if(input.value == ''){
      return
    }
    let valueI = input.value.replaceAll(',','.');
    input.value = parseFloat(valueI).toLocaleString('es-ES', { minimumFractionDigits: 2 })

}

const limpiarInput = (input) =>{
  input.value = '';
}