$(document).ready( function () {
    
    $(function() {
        $('[data-toggle="tooltip"]').tooltip()
    })

    $('#myTable').DataTable({
        "bLengthChange": false,
        "bInfo": false,
        "aaSorting": false,
        'columnDefs': [
            {
                "targets": "_all", 
                "className": "text-center",
                "sortable": false,
         
            },
        ],
        "oLanguage": {
    
            "sSearch": "Busqueda rapida:",
            "sSearchPlaceholder" : "Sobre cualquier campo"
            
    
        },
    });

})
    
const checkFatura = (div) => {

    comprobarChecks();
    allTd = div.parentElement.parentElement.querySelectorAll("td");
    let fecha = allTd[0].textContent;
    let nro_sucursal = allTd[1].textContent;
    let tipoComprobante = allTd[2].textContent;
    let nroComprobante = allTd[3].textContent;
    let codCuenta = allTd[4].textContent;
    let descripcionCuenta = allTd[5].textContent;
    let monto = allTd[6].textContent.replace(/[$.]/g, "");
    let leyenda = allTd[7].textContent;
    let factura = 0;
    if(allTd[9].querySelector("input").checked == true){ 
        factura = 1;
        
    }
    let control = 0;
    if(allTd[10].querySelector("input").checked == true){ 
        control = 1;

    }

    $.ajax({
        type: "POST",
        url: "Controller/ControlEgresosController.php?accion=checkFactura",
        data: {
            fecha: fecha,
            nro_sucursal: nro_sucursal,
            tipoComprobante: tipoComprobante,
            nroComprobante: nroComprobante,
            codCuenta: codCuenta,
            descripcionCuenta: descripcionCuenta,
            monto: monto,
            leyenda: leyenda,
            factura: factura,
            control: control,
        },
        success: function (response) {
        }
    });

}
const checkControl = (div) => {
 
    comprobarChecks();
    allTd = div.parentElement.parentElement.querySelectorAll("td");
    let fecha = allTd[0].textContent;
    let nro_sucursal = allTd[1].textContent;
    let tipoComprobante = allTd[2].textContent;
    let nroComprobante = allTd[3].textContent;
    let codCuenta = allTd[4].textContent;
    let descripcionCuenta = allTd[5].textContent;
    let monto = allTd[6].textContent.replace(/[$.]/g, "");;
    let leyenda = allTd[7].textContent;
    let factura = 0;
    if(allTd[9].querySelector("input").checked == true){ 
        factura = 1;
        
    }
    let control = 0;
    if(allTd[10].querySelector("input").checked == true){ 
        control = 1;

    }

    $.ajax({
        type: "POST",
        url: "Controller/ControlEgresosController.php?accion=checkControl",
        data: {
            fecha: fecha,
            nro_sucursal: nro_sucursal,
            tipoComprobante: tipoComprobante,
            nroComprobante: nroComprobante,
            codCuenta: codCuenta,
            descripcionCuenta: descripcionCuenta,
            monto: monto,
            leyenda: leyenda,
            factura: factura,
            control: control,
        },
        success: function (response) {
        }
    });

}

const comprobarChecks = () => {

    let allFactura = document.querySelectorAll("#checkFactura");
    allFactura.forEach(element => {
        if(element.checked){
            element.disabled = true;
        }
    });

    let allControl = document.querySelectorAll("#checkControl");
    allControl.forEach(element => {
        if(element.checked){
            element.disabled = true;
        }
    });

}