const totalizar = (div = null) => {

    let idConceptos = document.querySelectorAll("#idConcepto");
    let sucursales = document.querySelectorAll("#sucursal");

    sucursales.forEach(s => {
        
        let result = 0;
        idConceptos.forEach(e => {
            let concepto = e.textContent;

            if(e.textContent == 9 || e.textContent == 13 ) {

                let porcentaje = document.querySelector(`#input-${concepto.trimEnd()}-${s.textContent}`).getAttribute("attr-realvalue");
                let valorId8 = document.querySelector(`#input-8-${s.textContent}`).value.replace(/[$.]/g, "");
                document.querySelector(`#input-${concepto.trimEnd()}-${s.textContent}`).value ="$"+ parseNumber((parseInt(valorId8) * parseInt(porcentaje)) / 100);

            }

            if(e.textContent == 16 || e.textContent == 17 ) {

                let valorId9 = document.querySelector(`#input-9-${s.textContent}`).value.replace(/[$.]/g, "");
                let inputActual = document.querySelector(`#input-${concepto.trimEnd()}-${s.textContent}`)

                inputActual.value ="$"+ parseNumber( parseInt(inputActual.getAttribute('attr-realvalue')) - parseInt(valorId9) );
                if(inputActual.value.replace(/[$.]/g, "") > 0) {
                    actualizarDetalle(document.querySelector(`#input-${concepto.trimEnd()}-${s.textContent}`));
                }
            }

            if(e.textContent == 6 || e.textContent == 7 ) {

                let valorId8 = document.querySelector(`#input-8-${s.textContent}`).value.replace(/[$.]/g, "");
                let inputActual = document.querySelector(`#input-${concepto.trimEnd()}-${s.textContent}`)
                let calculo =  parseInt(inputActual.getAttribute('attr-realvalue')) - parseInt(valorId8) ;

                if(calculo < 0) {
                    calculo = 0;
                }
                inputActual.value ="$"+ parseNumber( calculo); 
                if(inputActual.value.replace(/[$.]/g, "") > 0) {
                    actualizarDetalle(document.querySelector(`#input-${concepto.trimEnd()}-${s.textContent}`));
                }

            }
            $valorSumar = document.querySelector(`#input-${concepto.trimEnd()}-${s.textContent}`).value.replace(/[$.]/g, "");
            $valorSumar = $valorSumar.replace(/ /g,'');

            result = parseInt(result) +  parseInt($valorSumar);  
        });
        
        document.querySelector("#total-"+s.textContent).textContent = "$"+parseNumber(result);

    });

    if(div != null) {

        actualizarDetalle(div);

        value = div.value.replace(/[$.]/g, "");
        value = parseInt(value.replace(/ /g,''));


        if(value < 0){
            // console.log(parseNumber(value))
            div.value = "- $"+(parseNumber((value * -1),true)  )
            
        }else{

            div.value = "$"+parseNumber(value)
        }

    }

}

const parseNumber = (number,realValue = null) => {

    number = parseInt(number);

    let newNumber = number.toLocaleString('de-De', {
        style: 'decimal',
        maximumFractionDigits: 0,
        minimumFractionDigits: 0
    });
    if(realValue != true){

        if(newNumber < 0){
            return 0;
        }
        
    }
    return newNumber;

}

const insertarDetalle = () => {

    let tabla =document.querySelector("#tablaAlquileres");

    let inputs = tabla.querySelectorAll("input");
    let values = "";
    let periodo = document.querySelector("#periodo").textContent;
    let sucursales = document.querySelectorAll("#sucursal");


    inputs.forEach((e,x)=> {
        
        let data = e.id.split("-");
        let idConcepto = data[1];
        let idSucursal = data[2];
        let valor = e.value.replace(/[$.]/g, "");
        valor = parseFloat(valor).toFixed(2)

        sucursales.forEach(sucursal => {

            let infoSucursal =  sucursal.getAttribute("attr-infosuc").split("-")

            if(idSucursal == infoSucursal[1]) {

                values += `('${periodo}','${idSucursal}','${infoSucursal[0]}','${valor}','${idConcepto}'),`;

            }

        });

    });
    values = values.substring(0, values.length - 1);

    $.ajax({
        url: 'Controller/AlquilerController.php?accion=insertarDetalle',   
        method: 'POST',
        data: {
            values: values
        },
        success : function(data) {
                // console.log(data);
        }
    });

}

const actualizarDetalle = (div) => {

    let periodo = document.querySelector("#periodo").textContent;
    let sucursal = div.id.split("-")[2];
    let concepto = div.id.split("-")[1];
    
    let importe = div.value.replace(/[$.]/g, "");
    let userName = document.querySelector("#userName").value;
    let importe9 = 0;
    let importe13 = 0;


    if(concepto == 8){

        importe9 = document.querySelector(`#input-9-${sucursal}`).value.replace(/[$.]/g, "");
        importe13 = document.querySelector(`#input-13-${sucursal}`).value.replace(/[$.]/g, "");

    }

    $.ajax({
        url: 'Controller/AlquilerController.php?accion=actualizarDetalle',   
        method: 'POST',
        data: {
            periodo:  periodo,
            sucursal: sucursal,
            concepto: concepto,
            importe:  importe,
            importe9: importe9,
            importe13: importe13,
            userName: userName
        },
        success : function(data) {
                // console.log(data);
        }
    });

}

const actualizarCargaAutomatica = () => {

    let tabla =document.querySelector("#tablaAlquileres");

    let inputs = tabla.querySelectorAll("input");

    let periodo = document.querySelector("#periodo").textContent;

    inputs.forEach((e,x)=> {

        let data = e.id.split("-");
        let idConcepto = data[1];
        let valor = e.value.replace(/[$.]/g, "");
        valor = parseFloat(valor).toFixed(2);

        if(['6','7','9','13','14','15','16','17'].includes(idConcepto)) {

            if(valor > 0){
                actualizarDetalle(e);
            }

        }
        

    });


}


const procesar = () => {

    let allTd = document.querySelectorAll("tr")[19].querySelectorAll("td");
    let periodo = document.querySelector("#periodo").textContent;
    let error = false;

    for (let i = 0; i < allTd.length; i++) {

        if(i >= 2){

            let element = allTd[i];

            let value = element.textContent.replace(/[$.]/g, "");

       
            if(value == 0){

                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'Complete los gastos de todas las sucursales!'
                })
                error = true;
                break;

            }


        }
    };

    if(error == false){ 
        $.ajax({
            url: 'Controller/AlquilerController.php?accion=procesar',
            method: 'POST',
            data: {
                periodo: periodo
            },
            success : function(data) {
                if(data = 1){
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'El período ya se encuentra procesado!'
                        })
                }else{

                    Swal.fire({
                        icon: 'success',
                        title: 'Procesado',
                        text: 'Se ha procesado correctamente!'
                    }).then((result) => {
                        // location.reload();
                    })
                    
                }
                

            }
        });
    }
}