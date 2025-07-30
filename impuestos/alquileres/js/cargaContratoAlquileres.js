

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
    let error = false;

    if(valorLlave == "" && comisiones == "" && lanzamiento == ""){
        error = true;
    }
    valorLlave = (valorLlave != '') ? valorLlave : 0;
    comisiones = (comisiones != '') ? comisiones : 0;
    lanzamiento = (lanzamiento != '') ? lanzamiento : 0;

    if(error){
        Swal.fire({
        icon: "warning",
        title: "Estás guardando un contrato sin importes cargados. ¿Deseás continuar?",
        showDenyButton: true,
        confirmButtonText: "Aceptar",
        denyButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
             enviarData(desde, hasta, idSucursal, descSucursal, valorLlave, comisiones, lanzamiento);
            } else if (result.isDenied) {
                return 1;
            }});
     return 
    }
   
   enviarData(desde, hasta, idSucursal, descSucursal, valorLlave, comisiones, lanzamiento);

}

const enviarData = (desde, hasta, idSucursal, descSucursal, valorLlave, comisiones, lanzamiento) =>{

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
            if(data == true){

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
                    text: 'Ya existe un registro para ese periodo!',
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