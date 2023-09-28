

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
    if(totalEnSistema < 0){
        totalEnSistema = totalEnSistema * -1;
        document.querySelector('#totalEnSistema').textContent ="- $"+parseNumber(totalEnSistema);
    }else{

        document.querySelector('#totalEnSistema').textContent ="$" + parseNumber(totalEnSistema);
    }

    let totalFisico = 0;
    tdMontoFisico.forEach((td) => {

        let valor = td.value.replace(/[$.]/g, "");

        if(valor.includes("-")){

            valor = valor.replace(/-/g, "") * -1;
          
        }

        totalFisico += parseInt(valor)
        
        
    
    });
    if(totalFisico < 0){
        totalFisico = totalFisico * -1;
        document.querySelector('#totalFisico').textContent ="- $"+parseNumber(totalFisico);
    }else{

        document.querySelector('#totalFisico').textContent ="$" + parseNumber(totalFisico);
    }


    let tdDiferencia = document.querySelectorAll('#diferencias');
    let totalDiferencia = 0;
    tdDiferencia.forEach((td) => {
            
            let valorFisico = td.parentElement.querySelectorAll("td")[3].querySelector("input").value.replace(/[$.]/g, "");
            let valorSistema = td.parentElement.querySelectorAll("td")[1].textContent.replace(/[$.]/g, "");
    
            if(valorSistema.includes("-")){
                
                valorSistema = valorSistema.replace(/-/g, "") * -1;

                if(valorFisico.includes("-")){

                    valorFisico = valorFisico.replace(/-/g, "") * -1;

                }
                if(valorSistema - valorFisico < 0){
                    let numero = (parseInt(valorSistema) - valorFisico)
                    numero = numero * -1;
             
                    td.textContent = "- $" + parseNumber(numero);
                }else{
                    td.textContent = "$" + parseNumber(valorSistema - valorFisico); 

                }


            }else{
                td.textContent = "$" + parseNumber(valorSistema - valorFisico); 

            }

            let valor = td.textContent.replace(/[$.]/g, "");
    
            if(valor.includes("-")){

                valor = valor.replace(/-/g, "") * -1;
              
            }

            totalDiferencia += parseInt(valor)
            
        
    });

    if(totalDiferencia < 0){

        totalDiferencia = totalDiferencia * -1;
        document.querySelector('#totalDiferencia').textContent ="- $"+parseNumber(totalDiferencia);
    }else{
        document.querySelector('#totalDiferencia').textContent ="$" + parseNumber(totalDiferencia);

    }

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

    let datos = document.querySelector('#medioPago').value
    let medioPago = datos.split("-")[1];
    let valorFisico = div.value;

    if(medioPago == "PROMO BANCO"){
        
        valorFisico = valorFisico.replace(/[$.]/g, "");
        if(!valorFisico.includes("-") && valorFisico != 0){
            Swal.fire({
                icon: 'warning',
                title: 'El valor debe ser negativo',
                text: 'Por favor ingrese un valor negativo',
            })
            document.querySelector('#controlar').disabled = true;
            return 1
        }else{

            document.querySelector('#controlar').disabled = false;
        }

    }
    div.value = div.value.replace(/[$.]/g, "");


    if(div.value < 0){
        div.value = div.value * -1;
        div.value = "- $" +parseNumber(div.value);

    }else{

        div.value = "$" +parseNumber(div.value);
    }
    let valorSistema = div.parentElement.parentElement.querySelectorAll("td")[1].textContent.replace(/[$.]/g, "");
    
    
    if(valorSistema.includes("-")){
        valorSistema = valorSistema.replace(/-/g, "") * -1;
    }

    if(valorSistema - parseInt(valorFisico) < 0){
        
        let total = (valorSistema - parseInt(valorFisico)) * -1;
        div.parentElement.parentElement.querySelectorAll("td")[4].textContent= "- $" + parseNumber(total);

    }else{

        div.parentElement.parentElement.querySelectorAll("td")[4].textContent = "$" + parseNumber(valorSistema - parseInt(valorFisico));
    }

    calcularTotales();
}

const guardar = () => {

    let allTr = document.querySelectorAll('tbody tr');
    let data = [];
    allTr.forEach((tr) => {
        let id = tr.querySelectorAll('td')[6].textContent;
        let importeControl = tr.querySelectorAll('td')[3].querySelector('input').value.replace(/[$.]/g, "");
        let observaciones = tr.querySelectorAll('td')[5].querySelector('input').value;

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
    let inputsCargados = true;
    allTr.forEach((tr) => {
        let id = tr.querySelectorAll('td')[6].textContent;
        let importeControl = tr.querySelectorAll('td')[3].querySelector('input').value.replace(/[$.]/g, "");
        let observaciones = tr.querySelectorAll('td')[5].querySelector('input').value;

        if(importeControl == "" || importeControl == 0 )inputsCargados = false;

        if(tr.querySelectorAll('td')[4].textContent.replace(/[$.]/g, "") != 0){
            console.log(tr.querySelectorAll('td')[4].textContent.replace(/[$.]/g, ""));
            error = true;
        }

            data.push({
                id,
                importeControl,
                observaciones
            });

     
    });

    if(inputsCargados == false){

        Swal.fire({
            icon: 'warning',
            title: 'Hay valores sin cargar',
            text: 'Por favor complete todos los valores',
        })
        return 1;

    }
    
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

document.querySelector("#controlar").addEventListener('click', controlar);

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