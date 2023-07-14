const checkControl = (div) => {
    comprobarChecks();
    let nroSucursal = div.parentElement.parentElement.querySelectorAll("td")[0].textContent;
    let periodo = document.querySelector("#periodo").textContent;

    // let keys = document.querySelectorAll("th");
    // let data = [];
    // keys.forEach((element,x) => {
    //     data[element.textContent] = values[x].textContent; 
    // });
    // console.log(data); 
    
    $.ajax({
        type: "POST",
        url: "Controller/gastosTesoreriaController.php?accion=checkControl",
        data: {
            nroSucursal: nroSucursal,
            periodo: periodo
        },
        success: function (response) {
        }
    });


}


const comprobarChecks = () => {

    let allControl = document.querySelectorAll("#checkControl");
    allControl.forEach(element => {
        if(element.checked){
            element.disabled = true;
        }
    });

}