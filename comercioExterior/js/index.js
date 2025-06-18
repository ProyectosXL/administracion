

// Manejar clicks en los botones de acción
let btnCrear = document.querySelector("#btnCrear");
let btnEditar = document.querySelector("#btnEditar");

btnCrear.addEventListener("click", () => {
    window.location = "cargaInicial.php";
});

btnEditar.addEventListener("click", () => {
    window.location = "mostrarOrden.php";
});

// Función para el toggle de país que ya estaba funcionando
const cambiarEntorno = (t) => {
    let entorno = 0;
  
    if(t.getAttribute("data-off") == "ARG") {
        entorno = 0;
    } else {
        entorno = 1;
    }

    $.ajax({
        url: "Controller/cambiarEntorno.php",
        method: "POST",
        data: {entorno: entorno},
        success: function (data) {
            location.reload();
        }
    });
};

// Nueva función para manejar el toggle en el diseño nuevo
$(document).ready(function() {
    const countryToggle = $('#country-toggle');
    const environmentInfo = $('#environment-info');
    
    // Toggle environment en el nuevo diseño
    countryToggle.change(function() {
        const isArgentina = $(this).is(':checked');
        let entorno = isArgentina ? 0 : 1; // 0 para central (Argentina), 1 para uy
        
        $.ajax({
            url: "Controller/cambiarEntorno.php",
            method: "POST",
            data: {entorno: entorno},
            success: function(data) {
                location.reload();
            },
            error: function(xhr, status, error) {
                console.error('Error updating environment:', error);
            }
        });
    });
});