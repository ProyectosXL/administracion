

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
            icon: 'error',
            title: 'Error',
            text: 'No se puede controlar si alguna diferencia es mayor a 0',
        });
        return;
    };
    $.ajax({
        url: 'Controller/ControlDiario.php?verificado=1',
        type: 'POST',
        data: {
            data
        },
        success: function (response) {

            Swal.fire({
                icon: 'success',
                title: 'Controlado',
                text: 'Se controló correctamente',

            }).then (() => {
                location.reload();
            }
            );


        }
    });



}

document.ready = calcularTotales ();