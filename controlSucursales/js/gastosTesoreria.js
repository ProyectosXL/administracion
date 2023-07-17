const checkControl = (div) => {
    comprobarChecks();
    let nroSucursal = div.parentElement.parentElement.querySelectorAll("td")[0].textContent;
    let periodo = document.querySelector("#periodo").textContent;

    let keys = document.querySelectorAll("th");
    let allth = [];
    let count = 0;
    keys.forEach((element,x) => {
        if(x > 1 && x < keys.length - 1){
           allth[count] = element.textContent;

            count++;
        }
   
    });
    let allTd = [];
    count = 0;
    div.parentElement.parentElement.querySelectorAll("td").forEach((element,x) => {
        if(x > 1 && x < keys.length - 1){
            allTd[count] = element.textContent.replace(/[$.]/g, ""); 
            count++;
        }
    });

    let data = {};
    data[allth[0]] = allTd[0];
    data[allth[1]] = allTd[1];
    data[allth[2]] = allTd[2];
    data[allth[3]] = allTd[3];
    data[allth[4]] = allTd[4];
    data[allth[5]] = allTd[5];
    data[allth[6]] = allTd[6];


    $.ajax({
        type: "POST",
        url: "Controller/gastosTesoreriaController.php?accion=checkControl",
        data: {
            nroSucursal: nroSucursal,
            periodo: periodo,
            data: data
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