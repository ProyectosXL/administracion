document.addEventListener('DOMContentLoaded',iniciar);

//valida que los campos no esten vacíos//
var btnSave = document.getElementById('btnSave');
btnSave.addEventListener('click',guardarCabecera);

let inputs=document.querySelectorAll('.input--style-1');
let selected=document.querySelectorAll('.select2-hidden-accessible');
                                         
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
         Swal.fire('Despacho guardado!', '', 'success')
     } else if (result.isDenied) {
         Swal.fire('El despacho no fue guardado', '', 'info')
     }
     })}
 }
 )}
      
   //Calcula valor FOB en pesos según cotización de tipoCambio// 
    function calcular(){
        var tipoCambio = parseFloat(document.getElementById('tipoCambio').value);
        var valorFobPeso = parseFloat(document.getElementById('valorFobDolar').value);

        var resultado = valorFobPeso * tipoCambio;
        document.getElementById('valorFobPeso').value = resultado;
    }

    //Setea formato de moneda en campos donde se deben introducir números//
    $('input.currencyInput').on('blur', function() {
        const value = this.value.replace(/,/g, '');
        this.value = parseFloat(value).toLocaleString('en-US', {
        style: 'decimal',
        maximumFractionDigits: 2,
        minimumFractionDigits: 2
        });
    });

    
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
    var txtSoloLetras = document.getElementsByClassName("soloText")
    txtSoloLetras.addEventListener("input", function (event) {
        validarTextoEntrada(this, "[a-z ]")
    })

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

        