
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
        // location.reload();
      }
    });


  }
  