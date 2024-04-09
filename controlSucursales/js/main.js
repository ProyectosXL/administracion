
//Búsqueda rápida table//

function myFunction() {
    var input, filter, table, tr, td, td2, i, txtValue;
    input = document.getElementById("textBox");
    filter = input.value.toUpperCase();
    table = document.getElementById("table");
    tr = table.getElementsByTagName("tr");
    //tr = document.getElementById('tr');
  
    for (i = 0; i < tr.length; i++) {
      visible = false;
      /* Obtenemos todas las celdas de la fila, no sólo la primera */
      td = tr[i].getElementsByTagName("td");
  
      for (j = 0; j < td.length; j++) {
        if (td[j] && td[j].innerHTML.toUpperCase().indexOf(filter) > -1) {
          visible = true;
        } 
      }
      if (visible === true) {
        tr[i].style.display = "";
      } else {
        tr[i].style.display = "none";
      }
    }
  }


  const confirmarVentaVsCobranza = (e) => {
    
    e.disabled = true;
    console.log();

    let nroSucursal = e.parentElement.parentElement.querySelectorAll("td")[1].textContent;
    let nroComprobante = e.parentElement.parentElement.querySelectorAll("td")[4].textContent;

    $.ajax({
      url: "Controller/VentaVsCobranzaController.php?accion=confirmarVentaVsCobranza",
      type: "POST",
      data: {
        nroSucursal:nroSucursal,
        nroComprobante:nroComprobante
      },

      success: function (response) {
        location.reload();
      }
    });


  }
  

  const calcularTotales = () => {


    let totalTarjeta = 0;
    let totalCuentaDni = 0
    let totalTarjetas = 0;
    let totalMercadoPagoQr = 0
    let totalMercadoPago = 0;
    let totalModoQr = 0;
    let totalPromoBanco = 0;
    let totalEfectivo = 0;
    let totalBonusShopping = 0;
    let totalDolares = 0;
    let totalEuros = 0;
    let totalVentas = 0;

    /* TARJETAS */
    document.querySelectorAll("#tdTarjeta").forEach(total => {
      totalTarjeta += parseFloat(total.textContent.replaceAll(",","").replace("$", ""));
    });
    document.querySelector("#totalTarjeta").textContent = "$" + parseNumber(totalTarjeta);

    /* CUENTA DNI */
    document.querySelectorAll("#tdCuentaDni").forEach(total => {
      totalCuentaDni += parseFloat(total.textContent.replaceAll(",","").replace("$", ""));
    });
    document.querySelector("#totalCuentaDni").textContent = "$" + parseNumber(totalCuentaDni);

    /* TOTAL TARJETAS */
    document.querySelectorAll("#tdTotalTarjetas").forEach(total => {
      totalTarjetas += parseFloat(total.textContent.replaceAll(",","").replace("$", ""));
    });
    document.querySelector("#totalTarjetas").textContent = "$" + parseNumber(totalTarjetas);

    /* MERCADO PAGO QR */
    document.querySelectorAll("#tdMercadoPagoQr").forEach(total => {
      totalMercadoPagoQr += parseFloat(total.textContent.replaceAll(",","").replace("$", ""));
    });
    document.querySelector("#totalMercadoPagoQr").textContent = "$" + parseNumber(totalMercadoPagoQr);

    /* MERCADO PAGO */
    document.querySelectorAll("#tdMercadoPago").forEach(total => {
      totalMercadoPago += parseFloat(total.textContent.replaceAll(",","").replace("$", ""));
    });
    document.querySelector("#totalMercadoPago").textContent = "$" + parseNumber(totalMercadoPago);

    /* MODO QR */
    document.querySelectorAll("#tdModoQr").forEach(total => {
      totalModoQr += parseFloat(total.textContent.replaceAll(",","").replace("$", ""));
    });
    document.querySelector("#totalModoQr").textContent = "$" + parseNumber(totalModoQr);

    /* PROMO BANCO */
    document.querySelectorAll("#tdPromoBanco").forEach(total => {
      totalPromoBanco += parseFloat(total.textContent.replaceAll(",","").replace("$", ""));
    });
    document.querySelector("#totalPromoBanco").textContent = "$" + parseNumber(totalPromoBanco);

    /* EFECTIVO */
    document.querySelectorAll("#tdEfectivo").forEach(total => {
      totalEfectivo += parseFloat(total.textContent.replaceAll(",","").replace("$", ""));
    });
    document.querySelector("#totalEfectivo").textContent = "$" + parseNumber(totalEfectivo);

    /* BONUS SHOPPING */
    document.querySelectorAll("#tdBonusShopping").forEach(total => {
      totalBonusShopping += parseFloat(total.textContent.replaceAll(",","").replace("$", ""));
    });
    document.querySelector("#totalBonusShopping").textContent = "$" + parseNumber(totalBonusShopping);

    /* DOLARES */
    document.querySelectorAll("#tdDolares").forEach(total => {
      totalDolares += parseFloat(total.textContent.replaceAll(",","").replace("$", ""));
    });
    document.querySelector("#totalDolares").textContent = "$" + parseNumber(totalDolares);

    /* EUROS */
    document.querySelectorAll("#tdEuros").forEach(total => {
      totalEuros += parseFloat(total.textContent.replaceAll(",","").replace("$", ""));
    });
    document.querySelector("#totalEuros").textContent = "$" + parseNumber(totalEuros);

    /* TOTAL VENTAS */
    document.querySelectorAll("#tdTotalVentas").forEach(total => {
      totalVentas += parseFloat(total.textContent.replaceAll(",","").replace("$", ""));
    });
    document.querySelector("#totalVentas").textContent = "$" + parseNumber(totalVentas);

  }





  const parseNumber = (number) => {

    let newNumber = number.toLocaleString('de-De', {
        style: 'decimal',
        maximumFractionDigits: 2,
        minimumFractionDigits: 2
    });

    console.log(newNumber)
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