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
  valorEnFloat = numero.replace(",",".");
  valor = parseFloat(valorEnFloat);

  return valor;
}

const iniciarCalculo = (data,isCopy = null)=>{
 
  let table = document.querySelector("#table")
  let rows = table.querySelectorAll("tr:not(:last-child)")

  rows.forEach((x,e)=>{

      total = 0;
    
        let tipoCambio =(x.querySelectorAll("td")[3].firstChild.value).replace(",",".");

        if(tipoCambio == 0){
          total = (x.querySelectorAll("td")[2].firstChild.value ).replace(",",".")

        }else{
          total = (x.querySelectorAll("td")[2].firstChild.value ).replace(",",".")* (x.querySelectorAll("td")[3].firstChild.value).replace(",",".");
        }
 
        x.querySelectorAll("td")[4].firstChild.value =parseFloat(total).toFixed(2);

        let sobreFob = x.querySelectorAll("td")[5]
        let importeEnPesos = x.querySelectorAll("td")[4].firstChild.value
        
        let valorPesosFob = document.querySelector("#valorPesosFob").getAttribute("attr-value");

        valor = sacarParseo(valorPesosFob)

        let result = ((importeEnPesos / valor)*100) ;
        sobreFob.firstChild.value = result.toFixed(2) + "%";

      
  })
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