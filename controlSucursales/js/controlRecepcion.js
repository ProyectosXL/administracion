const marcarRecibido = (e) => {

 
    let nroSucursal = e.parentElement.parentElement.querySelectorAll("td")[1].textContent;
    let fecha = e.parentElement.parentElement.querySelectorAll("td")[0].textContent;
    let tipoComprobante = e.parentElement.parentElement.querySelectorAll("td")[2].textContent;
    let nroComprobante = e.parentElement.parentElement.querySelectorAll("td")[3].textContent;
    let codCuenta = e.parentElement.parentElement.querySelectorAll("td")[4].textContent;
    let descripcionCuenta = e.parentElement.parentElement.querySelectorAll("td")[5].textContent;
    let monto = e.parentElement.parentElement.querySelectorAll("td")[6].textContent.replace(/[$.]/g, "");

    e.parentElement.parentElement.querySelectorAll("td")[7].innerHTML = `<div class ="btn btn-success" style="margin-right:20px"><i class="bi bi-check2-square"></i><div>`;

    $.ajax({
        type: "POST",
        url: "Controller/ControlEgresosController.php?accion=marcarRecibido",
        data: {
            nroSucursal: nroSucursal,
            tipoComprobante: tipoComprobante,
            nroComprobante: nroComprobante,
            codCuenta: codCuenta,
            descripcionCuenta: descripcionCuenta,
            monto: monto,
            fecha: fecha
        },
        success: function (response) {
        
        }
    });

}