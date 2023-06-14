const totalizar = (div = null) => {

    let idConceptos = document.querySelectorAll("#idConcepto");
    let sucursales = document.querySelectorAll("#sucursal");

    sucursales.forEach(s => {
        
        let result = 0;
        idConceptos.forEach(e => {
            let concepto =e.textContent;

            if(e.textContent == 9 || e.textContent == 13 ) {

                let porcentaje = document.querySelector(`#input-${concepto.trimEnd()}-${s.textContent}`).getAttribute("attr-realvalue");
                let valorId8 = document.querySelector(`#input-8-${s.textContent}`).value.replace(/[$.]/g, "");
                document.querySelector(`#input-${concepto.trimEnd()}-${s.textContent}`).value ="$"+ parseNumber((parseInt(valorId8) * parseInt(porcentaje)) / 100);

            }

            result = parseInt(result) +  parseInt( document.querySelector(`#input-${concepto.trimEnd()}-${s.textContent}`).value.replace(/[$.]/g, ""));  
        });

        document.querySelector("#total-"+s.textContent).textContent = "$"+parseNumber(result);

    });

    if(div != null) {

        actualizarDetalle(div);
        div.value = "$"+parseNumber(div.value.replace(/[$.]/g, ""))

    }

}

const parseNumber = (number) => {

    number = parseInt(number);

    let newNumber = number.toLocaleString('de-De', {
        style: 'decimal',
        maximumFractionDigits: 0,
        minimumFractionDigits: 0
    });

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
        url: 'Controller/InsertarDetalleController.php',   
        method: 'POST',
        data: {
            values: values
        },
        success : function(data) {
                console.log(data);
        }
    });

}

const actualizarDetalle = (div) => {

    let periodo = document.querySelector("#periodo").textContent;
    let sucursal = div.id.split("-")[2];
    let concepto = div.id.split("-")[1];
    
    let importe = div.value.replace(/[$.]/g, "");

    let importe9 = 0;
    let importe13 = 0;

    if(concepto == 8){
        importe9 = document.querySelector(`#input-9-${sucursal}`).value.replace(/[$.]/g, "");
        importe13 = document.querySelector(`#input-13-${sucursal}`).value.replace(/[$.]/g, "");
    }
    
    $.ajax({
        url: 'Controller/ActualizarDetalleController.php',   
        method: 'POST',
        data: {
            periodo:  periodo,
            sucursal: sucursal,
            concepto: concepto,
            importe:  importe,
            importe9: importe9,
            importe13: importe13
        },
        success : function(data) {
                console.log(data);
        }
    });

}