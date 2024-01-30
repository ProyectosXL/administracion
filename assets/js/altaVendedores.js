$('#selectGrupo').select2({});
 
$('#selectGrupo').on('select2:opening select2:closing', function( event ) {
var $searchfield = $(this).parent().find('.select2-search__field');
// $searchfield.prop('disabled', true);
});



$('#selectSucursal').select2();

$('#selectSucursal').on('select2:opening select2:closing', function( event ) {
var $searchfield = $(this).parent().find('.select2-search__field');
// $searchfield.prop('disabled', true);
});

const mostrarSpiner = () =>{

    let spinner = document.querySelector("#boxLoading");

    spinner.classList.add("loading");


}

$('#selectCategoria').select2();

$('#selectCategoria').on('select2:opening select2:closing', function( event ) {
var $searchfield = $(this).parent().find('.select2-search__field');
// $searchfield.prop('disabled', true);
});


$('#selectRangoEtario').select2();

$('#selectRangoEtario').on('select2:opening select2:closing', function( event ) {
var $searchfield = $(this).parent().find('.select2-search__field');
// $searchfield.prop('disabled', true);
});

const filtrarCategoria = () =>{

    let allOptions = Array.from(document.querySelector("#selectGrupo").selectedOptions);

    let arraySelected = [];
    allOptions.forEach((element,x )=> {
        arraySelected[x] = element.value;
    });

    let rubros = "";

    if(arraySelected.length != 0){

        rubros = JSON.stringify(arraySelected).replace("[", "(").replace("]", ")").replace(/"/g, "'");
    
    }

        $.ajax({

            url: 'Controller/ClienteController.php?accion=traerCategorias',
            type: 'POST',
            dataType: 'json',
            data: {rubros: rubros},
            success: function (data) {
                let html = '';
                data.forEach(element => {
                    html += `<option value="${element.RUBRO}-${element.CATEGORIA}">${element.CATEGORIA}</option>`;
                });
                $('#selectCategoria').html("");
                $('#selectCategoria').html(html);
                $('#selectCategoria').select2();
            }

        })

}

const exportTable = () =>{

    $(`#tablaClientes`).table2excel({
    // exclude CSS class
    exclude: ".noE  xl",
    name: "excel Document ",
    filename: "Excel", //do not include extension
    fileext: ".xlsx" // file extension
    });

    }

    const cambiarEntorno = () =>{}


    const habilitar = () =>{
        let selectGrupo = $('#selectGrupo').val();
        let selectSucursal = $('#selectSucursal').val();
        if(opcionesSeleccionadas.length == 0){
            selectSucursal.forEach(function(opcion) {
                console.log('Opción:', opcion);
            });
        }
        
    }
    const inhabilitar = () =>{

        console.log("inhabilitar")

    }