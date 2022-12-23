document.addEventListener('DOMContentLoaded',iniciar);

//valida que los campos no esten vacíos//
var btnSave = document.getElementById('btnSave');
btnSave.addEventListener('click',guardarCabecera);

let inputs=document.querySelectorAll('.input--style-1');
let selected=document.querySelectorAll('.select2-hidden-accessible');

let selectProveedor=document.getElementById('proveedor');

selectProveedor.addEventListener('change',buscarCuentas);

const selectOrdenes=document.getElementById('ordenCompra');

selectOrdenes.addEventListener('change',checkOrden);

function buscarCuentas()
{
    let ordenes;
    conexion1 = new XMLHttpRequest();
    conexion1.onreadystatechange = () => {
        if (conexion1.readyState == 4 && conexion1.status == 200) {
          ordenes = JSON.parse(conexion1.responseText);
        
          console.log(ordenes);
          limpiarSelect();
           dibujarSelectOrdenes(ordenes); 
          /*  inputCuenta.textContent = cuenta["VTEX_CUENTA"]; */
        } else {
          console.log("aguanta");
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

 let b=0;
 inputs.forEach(el=>{ if(el.value == '' ){
     el.parentElement.style.border="1px solid red";
     b=1;
 }else{
     el.parentElement.style.border="";
 }

 selected.forEach(el=>{ if(el.value == '' || el.value.includes("PROVEEDOR")|| el.value.includes("FORMA") ){
     el.parentElement.style.border="1px solid red";
     b=1;
 }else{
     el.parentElement.style.border="";
 }
  
 });

    var cod_proveedor = document.getElementById('proveedor').value;
    var proveedor = document.getElementById('proveedor').selectedOptions[0].innerHTML;
    var contenedor = document.getElementById('contenedor').value;
    var despacho = document.getElementById('despacho').value;
    var material = document.getElementById('material').value;
    var origen = document.getElementById('origen').value;
    var fechaEmbarque = document.getElementById('fechaEmbarque').value;
    var facturaProveedor = document.getElementById('facturaProveedor').value;
    var fechaFactura = document.getElementById('fechaFactura').value;
    var ordenCompra = document.getElementById('ordenCompra').value;
    var formaPago = document.getElementById('formaPago').value;
    var numeroBl = document.getElementById('numeroBl').value;
    var tipoCambio = document.getElementById('tipoCambio').value;
    var valorFobDolar = document.getElementById('valorFobDolar').value;
    var valorFobPeso = document.getElementById('valorFobPeso').value;
    var fechaArribo = document.getElementById('fechaArribo').value;
    var fechaDespacho = document.getElementById('fechaDespacho').value;
 
 if(b==1){
     Swal.fire({
     icon: 'error',
     title: 'Error...',
     text: 'Debe completar todos los campos!',
     });
     }
 else{
     Swal.fire({
     title: 'Desea guardar los cambios?',
     icon: 'info',
     showDenyButton: true,
     showCancelButton: true,
     cancelButtonText: 'Cancelar',
     confirmButtonText: 'Guardar',
     denyButtonText: `Descartar`,
     }).then((result) => {
     /* Read more about isConfirmed, isDenied below */
     if (result.isConfirmed) {
        let env = 1;
        let url = (env == 1) ? 'insertarEncabezado.php' : 'test.php';
        $.ajax({
            url: 'Controller/'+url,
            method: 'POST',
            data: {
                cod_proveedor: cod_proveedor, 
                proveedor: proveedor, 
                contenedor: contenedor, 
                despacho: despacho, 
                material: material, 
                origen: origen,
                fechaEmbarque: fechaEmbarque, 
                facturaProveedor: facturaProveedor, 
                fechaFactura: fechaFactura, 
                ordenCompra: ordenCompra, 
                formaPago: formaPago,
                numeroBl: numeroBl, 
                tipoCambio: tipoCambio.replace(/,/g, ""), 
                valorFobDolar: valorFobDolar.replace(/,/g, ""), 
                valorFobPeso: valorFobPeso.replace(/,/g, ""), 
                fechaArribo: fechaArribo, 
                fechaDespacho: fechaDespacho
            },
        });
            Swal.fire({
                title: 'Despacho guardado!',
                icon: 'success',
                showDenyButton: true,
                showCancelButton: false,
                showConfirmButton: false,
                denyButtonText: `Cargar detalle`,
                })
         .then(function () {
            window.location = "detalleCostos.php?ordenCompra="+ordenCompra+'&proveedor='+proveedor+'&valorFobPeso='+valorFobPeso+'&contenedor='+contenedor;
        });
     } else if (result.isDenied) {
         Swal.fire('El despacho no fue guardado', '', 'info')
     }
     })}
    }
 )};

    const parseNumber = (value)=>{
        return value.toLocaleString('en-US', {
            style: 'decimal',
            maximumFractionDigits: 2,
            minimumFractionDigits: 2
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
        var tipoCambio = parseFloat(document.getElementById('tipoCambio').value);
        var valorFobDolar = parseFloat(document.getElementById('valorFobDolar').getAttribute("originalvalue"));

        console.log(tipoCambio,valorFobDolar)
        var resultado = valorFobDolar * tipoCambio;
        document.getElementById('valorFobPeso').value = resultado.toLocaleString('en-US', {
            style: 'decimal',
            maximumFractionDigits: 2,
            minimumFractionDigits: 2
            });;
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
          console.log("aguanta");
        }
      };
      conexion1.open(
        "GET",
        "Class/ordenDeCompra.php?OrdenCompra=" +orden,
        true
      );
      conexion1.send();
   }