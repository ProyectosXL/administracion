let btnUpdateDetalle = document.querySelector("#btnUpdateDetalle");

btnUpdateDetalle.addEventListener("click",()=> {
    let rows = document.querySelectorAll("#id");
    let arrayDatos = [];
    let idEncabezado = document.querySelector("#idEncabezado").getAttribute("attr-value");
    rows.forEach((e , x) => {

        let rowsElement = e.parentElement;
        let idDetalle = rowsElement.childNodes[1].getAttribute('attr-value')
        let Gastos = rowsElement.childNodes[3].textContent;
        let importeEnDolares = sacarParseo(rowsElement.childNodes[5].childNodes[0].value,true);
        let tipoCambio = sacarParseo(rowsElement.childNodes[7].childNodes[0].value,true);
        let importeEnPesos = sacarParseo(rowsElement.childNodes[9].childNodes[0].value,true);
        let sobreFob = rowsElement.childNodes[11].childNodes[0].value.replace("%","");
        let observaciones = rowsElement.childNodes[13].childNodes[0].value
        arrayDatos [x] = [Gastos,importeEnDolares,tipoCambio,importeEnPesos,sobreFob,observaciones,idDetalle,idEncabezado]

    });
    $.ajax({
      url: 'Controller/editarDetalle.php',
      method: 'POST',
      data:{
        "array":arrayDatos
      },
    })
    Swal.fire({
      title: 'Detalle guardado!',
      icon: 'success',
      showDenyButton: true,
      showCancelButton: false,
      showConfirmButton: false,
      denyButtonText: `Volver`,
      })
      .then((e) => {

        window.location = "../comercioExterior/mostrarOrden.php"
      })

})