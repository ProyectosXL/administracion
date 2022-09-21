document.addEventListener('DOMContentLoaded',iniciar);
      
    $('.decimales').on('input', function () {
        this.value = this.value.replace(/[^0-9,.]/g, '').replace(/,/g, '.');
      });

    function calcular(){
        var tipoCambio = parseFloat(document.getElementById('tipoCambio').value);

        var valorFobPeso = parseFloat(document.getElementById('valorFobDolar').value);

        var resultado = valorFobPeso * tipoCambio;
        document.getElementById('valorFobPeso').value = resultado;
    }

    $('input.currencyInput').on('blur', function() {
        const value = this.value.replace(/,/g, '');
        this.value = parseFloat(value).toLocaleString('en-US', {
        style: 'decimal',
        maximumFractionDigits: 2,
        minimumFractionDigits: 2
        });
    });

    
    //Validar entrada solo de texto//
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