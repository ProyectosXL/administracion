const calcularDiferencias = (td) =>{
    let control = td.value; 
    let numeroParseado = parseNumber(control.replace(/[$.]/g, ""));
    td.value = "$"+numeroParseado;
    let sistema = td.parentElement.parentElement.childNodes[3].innerHTML; 
    let diferencia = control.replace(/[$.]/g, "") - sistema.replace(/[$.]/g, "");
    td.parentElement.parentElement.childNodes[7].innerHTML = "$" + parseNumber(diferencia);

}



//  $("#btnExport").click(function() {

    // $('input[type=number]').each(function(){
    //     this.setAttribute('value',$(this).val());
    // });

    // $("table").table2excel({
    //     // exclude CSS class
    //     exclude: ".noExl",
    //     name: "Worksheet Name",
    //     filename: "Remitos", //do not include extension
    //     fileext: ".xls", // file extension
    // });
// });

const parseNumber = (number) => {
    number = parseInt(number);

    newNumber = number.toLocaleString('de-De', {
        style: 'decimal',
        maximumFractionDigits: 0,
        minimumFractionDigits: 0
    });

    return newNumber;
}

const guardar = () =>{
    let allSelect = document.querySelectorAll("#sistema");
    let data = [];
    allSelect.forEach(element => {
        if(element.getAttribute("attr-idSistema") != 0 ){
            let id = element.getAttribute("attr-idSistema");
            let importeControl = element.parentElement.childNodes[5].childNodes[0].value.replace(/[$.]/g, "") ;
            let observaciones = element.parentElement.childNodes[9].childNodes[0].value;
            data.push({
                id,
                importeControl,
                observaciones
            })
        }
    });
    $.ajax({
        type: "POST",
        url: "Controller/ControlDiario.php?verificado=0",
        data: {data},
        success: function (response) {
            Swal.fire({
                icon: 'success',
                title: 'Guardado',
                text: 'Se guardo correctamente',
                showConfirmButton: false,
                timer: 1500
            })
        }
    });
}
const controlar = () =>{

    let allSelect = document.querySelectorAll("#sistema");
    let data = [];
    let diferencias = false;
    allSelect.forEach(element => {

        if(element.getAttribute("attr-idSistema") != 0 ){
        
            if(element.parentElement.childNodes[7].textContent != "$0"){

                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Hay Diferencias en los importes',
                    showConfirmButton: false,
                    timer: 1500
                })
                element.parentElement.childNodes[7].style.borderBottomColor = "red";
                diferencias = true;
            }else{
                element.parentElement.childNodes[7].style.borderBottomColor = "";
                let id = element.getAttribute("attr-idSistema");
                let importeControl = element.parentElement.childNodes[5].childNodes[0].value.replace(/[$.]/g, "") ;
                let observaciones = element.parentElement.childNodes[9].childNodes[0].value;
                data.push({
                    id,
                    importeControl,
                    observaciones
                })

            }
        }
    })

    if(diferencias != true){

        $.ajax({
            type: "POST",
            url: "Controller/ControlDiario.php?verificado=1",
            data: {data},
            success: function (response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Guardado',
                    text: 'Se guardo correctamente',
                    showConfirmButton: false,
                    timer: 1500
                })
            }
        });

    }
}