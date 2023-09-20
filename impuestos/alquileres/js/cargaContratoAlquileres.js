

$(document).ready(function() {
    document.querySelector(".select2-selection.select2-selection--single").style.height = "40px";
})

const guardar = () => {

    let desde   = document.querySelector("#desde").value;
    let hasta   = document.querySelector("#hasta").value;
    let sucursal = document.querySelector("#selectSucursal").value;

    let idSucursal = sucursal.split("-")[0];
    let descSucursal = sucursal.split("-")[1];

    let valorLlave = document.querySelector("#valorLlave").value.replace(/[$.]/g, "");
    let comisiones = document.querySelector("#comisiones").value.replace(/[$.]/g, "");
    let lanzamiento = document.querySelector("#lanzamiento").value.replace(/[$.]/g, "");

    if(valorLlave == "" && comisiones == "" && lanzamiento == ""){

        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'Debes cargar al menos un valor!',
        })
        return 1
    }
    $.ajax({
        url: 'Controller/alquilerController.php?accion=guardarContratoAlquiler',
        type: 'POST',
        data: {
            desde: desde,
            hasta: hasta,
            idSucursal: idSucursal,
            descSucursal: descSucursal,
            valorLlave: valorLlave,
            comisiones: comisiones,
            lanzamiento: lanzamiento
        },
        success: function(data) {
            if(data == 1){

                Swal.fire({
                    icon: 'success',
                    title: 'Contrato guardado correctamente!',
                    showConfirmButton: false,
                    timer: 1500
                })
                location.reload();

            }else {

                Swal.fire({
                    icon: 'error',
                    title: 'Error...',
                    text: 'Ya exise un registro para ese periodo!',
                })

            }
     
        }
    });

}

const parseNumber = (div) => {

    if(div.value == ""){
        return 1;
    }
    numero = parseInt(div.value.replace(/[$.]/g, ""));
    newNumber = numero.toLocaleString('de-De', {
        style: 'decimal',
        maximumFractionDigits: 0,
        minimumFractionDigits: 0
    });
    
        div.value = "$"+ newNumber;
}
