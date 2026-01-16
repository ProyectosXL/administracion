
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
    let totalCuentaDni = 0;
    let totalTarjetas = 0;
    let totalMercadoPagoQr = 0;
    let totalMercadoPago = 0;
    let totalModoQr = 0;
    let totalPromoBanco = 0;
    let totalEfectivo = 0;
    let totalBonusShopping = 0;
    let totalDolares = 0;
    let totalEuros = 0;
    let totalVentas = 0;

    // Obtener solo las filas visibles si existe DataTable
    let filas;
    if (typeof dataTable !== 'undefined' && dataTable) {
      // Usar la API de DataTables para obtener solo filas visibles
      filas = $('#tableVentas tbody tr:visible');
    } else {
      // Si no hay DataTable, usar todas las filas
      filas = document.querySelectorAll('#tableVentas tbody tr');
    }

    // Calcular totales solo de filas visibles
    filas.each ? filas.each(function() {
      // jQuery each
      const cells = $(this).find('td');
      totalTarjeta += parseFloat(cells.eq(2).text().replaceAll(",","").replace("$", "") || 0);
      totalCuentaDni += parseFloat(cells.eq(3).text().replaceAll(",","").replace("$", "") || 0);
      totalTarjetas += parseFloat(cells.eq(4).text().replaceAll(",","").replace("$", "") || 0);
      totalMercadoPagoQr += parseFloat(cells.eq(5).text().replaceAll(",","").replace("$", "") || 0);
      totalMercadoPago += parseFloat(cells.eq(6).text().replaceAll(",","").replace("$", "") || 0);
      totalModoQr += parseFloat(cells.eq(7).text().replaceAll(",","").replace("$", "") || 0);
      totalPromoBanco += parseFloat(cells.eq(8).text().replaceAll(",","").replace("$", "") || 0);
      totalEfectivo += parseFloat(cells.eq(9).text().replaceAll(",","").replace("$", "") || 0);
      totalBonusShopping += parseFloat(cells.eq(10).text().replaceAll(",","").replace("$", "") || 0);
      totalDolares += parseFloat(cells.eq(11).text().replaceAll(",","").replace("$", "") || 0);
      totalEuros += parseFloat(cells.eq(12).text().replaceAll(",","").replace("$", "") || 0);
      totalVentas += parseFloat(cells.eq(13).text().replaceAll(",","").replace("$", "") || 0);
    }) : filas.forEach(fila => {
      // Vanilla JS forEach
      const cells = fila.querySelectorAll('td');
      if (cells.length > 0) {
        totalTarjeta += parseFloat(cells[2]?.textContent.replaceAll(",","").replace("$", "") || 0);
        totalCuentaDni += parseFloat(cells[3]?.textContent.replaceAll(",","").replace("$", "") || 0);
        totalTarjetas += parseFloat(cells[4]?.textContent.replaceAll(",","").replace("$", "") || 0);
        totalMercadoPagoQr += parseFloat(cells[5]?.textContent.replaceAll(",","").replace("$", "") || 0);
        totalMercadoPago += parseFloat(cells[6]?.textContent.replaceAll(",","").replace("$", "") || 0);
        totalModoQr += parseFloat(cells[7]?.textContent.replaceAll(",","").replace("$", "") || 0);
        totalPromoBanco += parseFloat(cells[8]?.textContent.replaceAll(",","").replace("$", "") || 0);
        totalEfectivo += parseFloat(cells[9]?.textContent.replaceAll(",","").replace("$", "") || 0);
        totalBonusShopping += parseFloat(cells[10]?.textContent.replaceAll(",","").replace("$", "") || 0);
        totalDolares += parseFloat(cells[11]?.textContent.replaceAll(",","").replace("$", "") || 0);
        totalEuros += parseFloat(cells[12]?.textContent.replaceAll(",","").replace("$", "") || 0);
        totalVentas += parseFloat(cells[13]?.textContent.replaceAll(",","").replace("$", "") || 0);
      }
    });

    // Actualizar los totales en el footer
    document.querySelector("#totalTarjeta").textContent = "$" + parseNumber(totalTarjeta);
    document.querySelector("#totalCuentaDni").textContent = "$" + parseNumber(totalCuentaDni);
    document.querySelector("#totalTarjetas").textContent = "$" + parseNumber(totalTarjetas);
    document.querySelector("#totalMercadoPagoQr").textContent = "$" + parseNumber(totalMercadoPagoQr);
    document.querySelector("#totalMercadoPago").textContent = "$" + parseNumber(totalMercadoPago);
    document.querySelector("#totalModoQr").textContent = "$" + parseNumber(totalModoQr);
    document.querySelector("#totalPromoBanco").textContent = "$" + parseNumber(totalPromoBanco);
    document.querySelector("#totalEfectivo").textContent = "$" + parseNumber(totalEfectivo);
    document.querySelector("#totalBonusShopping").textContent = "$" + parseNumber(totalBonusShopping);
    document.querySelector("#totalDolares").textContent = "$" + parseNumber(totalDolares);
    document.querySelector("#totalEuros").textContent = "$" + parseNumber(totalEuros);
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