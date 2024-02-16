const traerVendedores = (div) => {

    let sucursal = div.value;

    let trVendedor = document.querySelectorAll("#trVendedor")


    trVendedor.forEach(vendedor => {

        vendedor.querySelectorAll("td")[2].querySelector("input").checked = false
    })

    $.ajax({
        type: "POST",
        url: "Controller/VendedorController.php?accion=traerVendedoresPorSucursal",
        data: {
            sucursal: sucursal
        },
        success: function (response) {
            
            if(response == false){
                Swal.fire({
                    icon: "error",
                    title: "Local sin conexion",
                    confirmButtonText: "Cerrar",
                })
            }else{
                data = JSON.parse(response)

                trVendedor.forEach(vendedor => {
                    
                    data.forEach(element => {
                   
                        if(vendedor.querySelector("td").textContent == element['COD_VENDED']){
                            vendedor.querySelectorAll("td")[2].querySelector("input").checked = true;
                        }   
                            
                    });

                });
            }
  
        }
    });

  

}

const marcarTodos = (check) => {

    let trVendedor = document.querySelectorAll("#trVendedor")

    trVendedor.forEach(vendedor => {

        if(check.checked == true){

            vendedor.querySelectorAll("td")[2].querySelector("input").checked = true
        }else{
            vendedor.querySelectorAll("td")[2].querySelector("input").checked = false

        }
    })

} 


const cambiarEntorno = (t) =>{


    let entorno = 'central';

    if(t.getAttribute("data-off") == "ARG" ){
        entorno = 'central';
    }else{
        entorno = 'uy';
    }

    console.log(entorno)

    $.ajax({
    url: "Controller/vendedorController.php?accion=cambiarEntorno",
    method: "POST",
    data : {entorno: entorno},
    success: function (data) {
        location.reload();
    }
    });
}

