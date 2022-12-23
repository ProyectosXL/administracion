document.addEventListener("DOMContentLoaded", iniciarEscucha);
let Gastos = document.getElementById("totalGastos");

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
      maximumFractionDigits: 2,
      minimumFractionDigits: 2
      });
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
      valorFobDolar;
  } else {
    e.target.parentElement.parentElement.children[4].children[0].value =
      valorFobPeso;
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
