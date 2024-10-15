const marcarRecibido = (e) => {

 
    let nroSucursal = e.parentElement.parentElement.querySelectorAll("td")[1].textContent;
    let fecha = e.parentElement.parentElement.querySelectorAll("td")[0].textContent;
    let tipoComprobante = e.parentElement.parentElement.querySelectorAll("td")[3].textContent;
    let nroComprobante = e.parentElement.parentElement.querySelectorAll("td")[4].textContent;
    let codCuenta = e.parentElement.parentElement.querySelectorAll("td")[5].textContent;
    let descripcionCuenta = e.parentElement.parentElement.querySelectorAll("td")[6].textContent;
    let monto = e.parentElement.parentElement.querySelectorAll("td")[7].textContent.replace(/[$.]/g, "");

    e.parentElement.parentElement.querySelectorAll("td")[8].innerHTML = `<i class='bi bi-check-circle-fill' style='color:green;font-size:27px;margin-right:15%'></i>`;

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

const marcarControlado = (e) => {

    let nroSucursal = e.parentElement.parentElement.querySelectorAll("td")[1].textContent;
    let fecha = e.parentElement.parentElement.querySelectorAll("td")[0].textContent;
    let tipoComprobante = e.parentElement.parentElement.querySelectorAll("td")[3].textContent;
    let nroComprobante = e.parentElement.parentElement.querySelectorAll("td")[4].textContent;
    let codCuenta = e.parentElement.parentElement.querySelectorAll("td")[5].textContent;
    let descripcionCuenta = e.parentElement.parentElement.querySelectorAll("td")[6].textContent;
    let monto = e.parentElement.parentElement.querySelectorAll("td")[7].textContent.replace(/[$.]/g, "");

    e.parentElement.parentElement.querySelectorAll("td")[9].innerHTML = `<i class='bi bi-check-circle-fill' style='color:green;font-size:20px;margin-right:15%'></i>`;

    $.ajax({
        type: "POST",
        url: "Controller/ControlEgresosController.php?accion=controlTesoreria",
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