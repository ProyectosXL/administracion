document.addEventListener('DOMContentLoaded',iniciar);

//valida que los campos no esten vacíos//


let inputs=document.querySelectorAll('.input--style-1');
let selected=document.querySelectorAll('.select2-hidden-accessible');

let selectProveedor=document.getElementById('proveedor');


// const selectOrdenes=document.getElementById('ordenCompra');

// selectOrdenes.addEventListener('change',checkOrden);
// console.log(selectOrdenes)


selectProveedor.addEventListener('change',buscarCuentas);


function buscarCuentas()
{
    let ordenes;
    conexion1 = new XMLHttpRequest();
    conexion1.onreadystatechange = () => {
        if (conexion1.readyState == 4 && conexion1.status == 200) {

            ordenes = JSON.parse(conexion1.responseText);
        
        
            localStorage.setItem('ordenes',JSON.stringify(ordenes));
      

        } else {

        }
      };
      conexion1.open(
        "GET",
        "Class/ordenDeCompra.php?proveedor=" +selectProveedor.value,
        true
      );
      conexion1.send();
}



function dibujarSelectOrdenes(ordenes)
{
    
    ordenes.forEach(orden=>{
        const option=document.createElement('option');
        option.value=orden.N_ORDEN_CO;
        option.text=orden.N_ORDEN_CO;
        selectOrdenes.appendChild(option);
        /* selectOrdenes.innerHTML=`<option value=${orden.N_ORDEN_CO}>${orden.N_ORDEN_CO}</option>`; */
    })
    
}

const limpiarSelect = () => {
    for (let i = selectOrdenes.options.length; i >= 1; i--) {
        selectOrdenes.remove(i);
    }
  };

  
//Guarda datos de cabecera//  
function guardarCabecera(){

    // Si es modo alta inicial, validar solo Sección 1
    const modoEdicion = document.getElementById('modoEdicion').value === 'true';
    
    if (!modoEdicion) {
        // Validar campos obligatorios de Sección 1
        if (!validarSeccion1()) {
            return;
        }
    }

    // Mostrar confirmación antes de procesar
    Swal.fire({
        title: '¿Desea guardar los cambios?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#7066e0',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, guardar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        // Sección 1 - Datos Iniciales (mapeo a columnas de BD)
        var cod_proveedor = document.getElementById('proveedor').value; // COD_PROVEE
        var proveedor = document.getElementById('proveedor').selectedOptions[0].innerHTML; // PROVEEDOR
        var contenedor = document.getElementById('contenedor').value; // CONTENEDOR
        var material = document.getElementById('material').value; // MATERIAL
        var origen = document.getElementById('origen').value; // ORIGEN
        var valorFobDolar = document.getElementById('valorFobDolar').value; // VALOR_FOB_DOLAR
        var fechaEmb = document.getElementById('fechaEmb').value; // FECHA_EMB (Fecha Estimada)
        
        let ocm = 0;
        let ordenManual = document.querySelector("#ordenManual");
        let ordenCompra = document.querySelectorAll("#nroOrdenSpan")
        ordenCompra = Array.from(ordenCompra).map((el)=>el.innerHTML.trim().split(" ")[0]);
        console.log(ordenCompra, 'ordenes')
        ordenCompra = JSON.stringify (ordenCompra); // ORDEN_COMPRA

        if(ordenManual.checked == true){
            ocm = 1; // OCM
        }

        // Sección 2 - Datos de Embarque (mapeo a columnas de BD)
        var fechaEtd = document.getElementById('fechaEtd').value; // Fecha Embarque ETD
        var numeroBl = document.getElementById('numeroBl').value; // NUMERO_BL
        var factura = document.getElementById('factura').value; // FACTURA
        var fechaArr = document.getElementById('fechaArr').value; // FECHA_ARR (ETA)
        
        // Sección 3 - Datos Financieros y Aduana (mapeo a columnas de BD)
        var tipoCambio = document.getElementById('tipoCambio').value; // TIPO_CAMBIO
        var valorFobPeso = document.getElementById('valorFobPeso').value; // VALOR_FOB_PESO
        var formaPago = document.getElementById('formaPago').value; // FORMA_PAGO
        var fechaPago = document.getElementById('fechaPago').value; // FECHA_PAGO
        var fechaDespAdu = document.getElementById('fechaDespAdu').value; // FECHA_DESP_ADU
        var despacho = document.getElementById('despacho').value; // DESPACHO
 
        // Realizar el guardado
        let env = 1;
        let url = (env == 1) ? 'insertarEncabezado.php' : 'test.php';
        let id = null;
        
        $.ajax({
            url: 'Controller/'+url,
            method: 'POST',
            data: {
                // Sección 1 - Datos Iniciales (mapeo exacto a BD)
                cod_proveedor: cod_proveedor, // COD_PROVEE
                proveedor: proveedor, // PROVEEDOR
                contenedor: contenedor, // CONTENEDOR
                material: material, // MATERIAL
                origen: origen, // ORIGEN
                valorFobDolar: valorFobDolar.replace(/,/g, ""), // VALOR_FOB_DOLAR
                fechaEmb: fechaEmb, // FECHA_EMB (Fecha Estimada Embarque)
                ordenCompra: ordenCompra, // ORDEN_COMPRA
                ocm: ocm, // OCM
                
                // Sección 2 - Datos de Embarque (mapeo exacto a BD)
                fechaEtd: fechaEtd, // Fecha Embarque ETD
                numeroBl: numeroBl, // NUMERO_BL
                factura: factura, // FACTURA
                fechaArr: fechaArr, // FECHA_ARR (ETA)
                
                // Sección 3 - Datos Financieros y Aduana (mapeo exacto a BD)
                tipoCambio: tipoCambio.replace(/,/g, ""), // TIPO_CAMBIO
                valorFobPeso: valorFobPeso.replace(/,/g, ""), // VALOR_FOB_PESO
                formaPago: formaPago, // FORMA_PAGO
                fechaPago: fechaPago, // FECHA_PAGO
                fechaDespAdu: fechaDespAdu, // FECHA_DESP_ADU
                despacho: despacho // DESPACHO
            },
            success: function(data) {
                let id = JSON.stringify(data);
                
                Swal.fire({
                    title: 'Despacho guardado!',
                    icon: 'success',
                    confirmButtonText: 'Cargar detalle',
                    confirmButtonColor: '#7066e0'
                }).then(function () {
                    window.location = "detalleCostos.php?ordenCompra="+ordenCompra+'&proveedor='+proveedor+'&valorFobPeso='+valorFobPeso+'&contenedor='+contenedor+'&tipoCambio='+tipoCambio+'&idEncabezado='+id;
                });
            }
        });
    });
    }

    const parseNumber = (value)=>{
        return value.toLocaleString('en-US', {
            style: 'decimal',
            // maximumFractionDigits: 2,
            // minimumFractionDigits: 2
            });
    }

    //Setea formato de moneda en campos donde se deben introducir números//
    $('input.currencyInput').on('blur', function() {
        this.setAttribute("originalValue", this.value);
        // const value = this.value.replace(/,/g, '');
        this.value = parseNumber(parseFloat(this.value.replace(/,/g, '.')))
    });

     //Calcula valor FOB en pesos según cotización de tipoCambio// 
     function calcular(){
        var tipoCambio = (document.getElementById('tipoCambio').value);
        tipoCambio = tipoCambio.replace(",",".");
        tipoCambio = parseFloat(tipoCambio);

        var valorFobDolar = (document.getElementById('valorFobDolar').getAttribute("originalvalue"));
        valorFobDolar = valorFobDolar.replace(",",".");
        valorFobDolar = parseFloat(valorFobDolar);
        var resultado = valorFobDolar * tipoCambio;
        document.getElementById('valorFobPeso').value = resultado.toLocaleString('en-US', {
            style: 'decimal',
            maximumFractionDigits: 2,
            minimumFractionDigits: 2
            });
    }

    
    //Validar entrada de inputs//
        function validarTextoEntrada(input, patron) {
        var texto = input.value
        var letras = texto.split("")
    
        for (var x in letras) {
            var letra = letras[x]
    
            if (!(new RegExp(patron, "i")).test(letra)) {
                letras[x] = ""
            }
        }
      input.value = letras.join("")
    }

    //Validar campos de texto//
 /*    var txtSoloLetras = document.getElementsByClassName("soloText")
    txtSoloLetras.addEventListener("input", function (event) {
        validarTextoEntrada(this, "[a-z ]")
    })
 */
    //Setear mayusculas//
    function iniciar()
    {
        var txtCurp=document.querySelectorAll('.mayusc');
        txtCurp.forEach(ele=>ele.addEventListener('input', function (event) {
            this.value = this.value.toUpperCase();
        }));
    }
    var txtCurp=document.querySelector('.mayusc');
    txtCurp.addEventListener('input', function (event) {
        this.value = this.value.toUpperCase()});

        
   function checkOrden(e)
   {
    let orden=e.target.value;
    conexion1 = new XMLHttpRequest();
    conexion1.onreadystatechange = () => {
        if (conexion1.readyState == 4 && conexion1.status == 200) {
          
          if(conexion1.responseText.includes('existe'))
          {
            Swal.fire({
                icon: 'error',
                title: 'ups...',
                text: 'El comprobante '+ orden +' ya se encuentra cargado',
            });
                selectOrdenes.selectedIndex = "0";
          }
        } else {

        }
      };
      conexion1.open(
        "GET",
        "Class/ordenDeCompra.php?OrdenCompra=" +orden,
        true
      );
      conexion1.send();
   }

   function checkOrdenesUy(){
    let ordenes = document.querySelectorAll("#nroOrdenSpan");
    let stringParaSql = '(';
    ordenes.forEach((orden, index)=>{
        let ordenParaSql = orden.textContent.trim();
        ordenParaSql = " "+ordenParaSql;
        stringParaSql += "'"+ordenParaSql+"',";
    })
    stringParaSql = stringParaSql.slice(0, -1);
    stringParaSql += ')';

    
    $.ajax({
        url: 'Class/ordenDeCompra.php',
        method: 'POST',
        data:{
          "ordenCompra": stringParaSql
        },
      }).then((e)=>{
        let result = JSON.parse(e);
        console.log(result)
        let existen = ""
        result.forEach(element => {
            existen += element['ordenDeCompra'] + ", ";

            
          ordenes.forEach(orden=>{
            if(" "+orden.textContent.trim() == element['ordenDeCompra']){
                orden.remove();
            }
          })

        });
       
        if(existen !=  "")
        {
          Swal.fire({
              icon: 'error',
              title: 'Error...',
              text: 'Los comprobantes '+ existen +' ya se encuentran cargados',
          });

        }

      });

   }
   