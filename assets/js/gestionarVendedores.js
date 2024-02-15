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