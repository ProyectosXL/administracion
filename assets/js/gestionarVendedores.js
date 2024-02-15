const traerVendedores = (div) => {

    let sucursal = div.value;

    $.ajax({
        type: "POST",
        url: "Controller/VendedorController.php?accion=traerVendedoresPorSucursal",
        data: {
            sucursal: sucursal
        },
        success: function (response) {
            // let select = document.querySelector("#selectVendedor")
            // select.innerHTML = '';
            // response = JSON.parse(response)
            // response.forEach(element => {
            //     let option = document.createElement("option")
            //     option.value = element[0]
            //     option.textContent = element[1]
            //     select.appendChild(option)
            // });
        }
    });

  

}