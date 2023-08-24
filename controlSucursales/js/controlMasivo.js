

const calcularTotales  = () => {
    let totalEnSistema = 0;
    let tdMontoEnSistema = document.querySelectorAll('#valorSistema');
    let tdMontoFisico = document.querySelectorAll('#valorFisico');

    tdMontoEnSistema.forEach((td) => {

        let valor = td.textContent.replace(/[$.]/g, "");

        if (valor.includes("-")) {

            totalEnSistema -= parseInt(valor.replace(/-/g, ""))

        } else {

            totalEnSistema += parseInt(valor)

        }
        
    
    });
    document.querySelector('#totalEnSistema').textContent ="$" + parseNumber(totalEnSistema);

    let totalFisico = 0;
    tdMontoFisico.forEach((td) => {

        let valor = td.value.replace(/[$.]/g, "");

        totalFisico += parseInt(valor)
        
    
    });
    document.querySelector('#totalFisico').textContent ="$" + parseNumber(totalFisico);

    let tdDiferencia = document.querySelectorAll('#diferencias');
    let totalDiferencia = 0;
    tdDiferencia.forEach((td) => {
            
            let valorFisico = td.parentElement.querySelectorAll("td")[2].querySelector("input").value.replace(/[$.]/g, "");
            let valorSistema = td.parentElement.querySelectorAll("td")[1].textContent.replace(/[$.]/g, "");
            td.textContent = "$" + parseNumber(valorSistema - valorFisico); 

            let valor = td.textContent.replace(/[$.]/g, "");
    
            totalDiferencia += parseInt(valor)
            
        
    });
    document.querySelector('#totalDiferencia').textContent ="$" + parseNumber(totalDiferencia);

}


const parseNumber = (number) => {
    number = parseInt(number);

    newNumber = number.toLocaleString('de-De', {
        style: 'decimal',
        maximumFractionDigits: 0,
        minimumFractionDigits: 0
    });

    return newNumber;
}

const calcularDiferecias = (div) => {

    if (div.value < 0) {
        div.value = 0;
    }

    div.value = "$" +parseNumber(div.value);

    let valorFisico = div.value.replace(/[$.]/g, "");
    let valorSistema = div.parentElement.parentElement.querySelectorAll("td")[1].textContent.replace(/[$.]/g, "");
    div.parentElement.parentElement.querySelectorAll("td")[3].textContent = "$" + parseNumber(valorSistema - valorFisico);

    calcularTotales();
}

const guardar = () => {

    let allTr = document.querySelectorAll('tbody tr');
    let data = [];
    allTr.forEach((tr) => {
        let id = tr.querySelectorAll('td')[5].textContent;
        let importeControl = tr.querySelectorAll('td')[2].querySelector('input').value.replace(/[$.]/g, "");
        let observaciones = tr.querySelectorAll('td')[4].querySelector('input').value;

        data.push({
            id,
            importeControl,
            observaciones
        });

    });

    $.ajax({
        url: 'Controller/ControlDiario.php?verificado=0',
        type: 'POST',
        data: {
            data
        },
        success: function (response) {
            Swal.fire({
                icon: 'success',
                title: 'Guardado',
                text: 'Se guardo correctamente',
            }).then (() => {
                location.reload();
            });
        }
    });



}

const controlar = () => {

    let allTr = document.querySelectorAll('tbody tr');
    let data = [];
    let error = false;
    allTr.forEach((tr) => {
        let id = tr.querySelectorAll('td')[5].textContent;
        let importeControl = tr.querySelectorAll('td')[2].querySelector('input').value.replace(/[$.]/g, "");
        let observaciones = tr.querySelectorAll('td')[4].querySelector('input').value;

        if(tr.querySelectorAll('td')[3].textContent.replace(/[$.]/g, "") != 0){
            console.log(tr.querySelectorAll('td')[3].textContent.replace(/[$.]/g, ""));
            error = true;
        }

            data.push({
                id,
                importeControl,
                observaciones
            });

     
    });

    if(error == true){

        Swal.fire({
            icon: 'warning',
            title: 'Desea realizar el control con diferencias?',
            showDenyButton: true,
            confirmButtonText: 'Confirmar',
            denyButtonText: 'Cancelar',
            }).then((result) => {
            /* Read more about isConfirmed, isDenied below */
            if (result.isConfirmed) {

                confirmarControl(data);
               

            } else if (result.isDenied) {

                Swal.fire('El control fue cancelado', '', 'info')
                return 1 ;

            }
        })

    }else{

        confirmarControl(data);
       
    }
 
   



}

const confirmarControl = (data) => {

    $.ajax({
        url: 'Controller/ControlDiario.php?verificado=1',
        type: 'POST',
        data: {
            data
        },
        success: function (response) {

            Swal.fire('Controlado!', '', 'success').then (() => {
                location.reload();
            });


        }
    });

}


$("#btnExport").click(function() {

    let inputs = document.querySelectorAll('#valorFisico');

    inputs.forEach(element => {

    let valor = element.value
    let td = element.parentElement; 
    td.innerHTML = "";
    td.id="tdValorFisico";
    const text=document.createTextNode(valor);

    td.appendChild(text);


    });

    let inputsObservacion = document.querySelectorAll('#observacion');

    inputsObservacion.forEach(element => {

    let valor = element.value
    let td = element.parentElement; 
    td.innerHTML = "";
    td.id="tdObservacion";
    const text=document.createTextNode(valor);

    td.appendChild(text);


    });

    $("#tablaControl").table2excel({

        // exclude CSS class
        exclude: ".noE  xl",
        name: "Control Masivo de Cobranza",
        filename: "Control Masivo de Cobranza", //do not include extension
        fileext: ".xlsx" // file extension
        
    });


    let tdValorFisico = document.querySelectorAll('#tdValorFisico');

    tdValorFisico.forEach(element => {

    let valor = element.textContent;
    let td = element; 
    td.innerHTML = "";

    var input = document.createElement("input");
    input.type = "text";
    input.id="valorFisico";
    input.value = valor,
    input.style.textAlign = "center";
    input.style.width = "100%";

    input.setAttribute("onchange", "calcularDiferecias(this)");


    td.appendChild(input);



    });

    let tdObservacion = document.querySelectorAll('#tdObservacion');

    tdObservacion.forEach(element => {

    let valor = element.textContent;
    let td = element; 
    td.innerHTML = "";

    var input = document.createElement("input");
    input.type = "text";
    input.id="observacion";
    input.value = valor,
    input.style.textAlign = "center";
    input.style.width = "100%";

    td.appendChild(input)
    });
});

document.ready = calcularTotales ();