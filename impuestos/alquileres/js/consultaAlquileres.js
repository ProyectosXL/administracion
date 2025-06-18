const mostrarDetalle = (div) =>{

    let numRow = div.getAttribute('data-target').split('-')[1];

    let todosLosTr = document.querySelectorAll("#trHidden"+numRow)
    todosLosTr.forEach(trPorMostrar => {
        if(trPorMostrar.hidden == true) {
            trPorMostrar.hidden = false
        }else{
            trPorMostrar.hidden = true
        }
    });

      
}

const totalizar = () => {

    let conceptos = document.querySelectorAll("#idConcepto");
    let meses = document.querySelectorAll("#meses");
    let contador = 0;
    meses.forEach(meses => {
        let result = 0
        conceptos.forEach(concepto => {
            let valor = document.querySelector(`#td-${concepto.textContent.trimEnd()}-${contador}`)

            result += parseInt(valor.textContent.replace(/[$.]/g, ""));

        })
        document.querySelector(`#total-${contador}`).textContent = "$"+parseNumber(result);
        contador++;
    })
}

const parseNumber = (number) => {

    number = parseInt(number);

    let newNumber = number.toLocaleString('de-De', {
        style: 'decimal',
        maximumFractionDigits: 0,
        minimumFractionDigits: 0
    });

    return newNumber;

}

const cambiarEntorno = (t) =>{

    let entorno = 0;
  
    if(t.getAttribute("data-off") == "ARG" ){
      entorno = 0;
    }else{
      entorno = 1;
    }
  
  
    $.ajax({
      url: "Controller/cambiarEntorno.php",
      method: "POST",
      data : {entorno: entorno},
      success: function (data) {
        location.reload();
      }
    });
  
  }