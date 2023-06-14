const agregar = () => {
    let concepto = document.getElementById('conceptos').value;
    let idConcepto = concepto.split("-")[0];
    let idLocal = document.getElementById('locales').value;
    let index = document.getElementById('locales').selectedIndex;
    let descLocal = document.getElementById('locales').querySelectorAll("option")[index].textContent;

    $.ajax({
        url: 'Controller/InsertarPorcentajeController.php',   
        method: 'POST',
        data: {
            idConcepto: idConcepto,
            idLocal: idLocal,
            descLocal: descLocal
        },
        success : function(data) {
               if (data == 1) {
                    alert("Se ha agregado el porcentaje correctamente");
                    location.reload();
                } else {
                    alert("Ha ocurrido un error");
                }  
        }
    });

}

const actualizarPorcentaje = (fila) => {
    let todosLosTd = fila.parentElement.parentElement.querySelectorAll("td");
    let id = todosLosTd[0].textContent;
    let porcentaje = todosLosTd[3].querySelector("input").value;

    $.ajax({
        url: 'Controller/ActualizarPorcentajeController.php',   
        method: 'POST',
        data: {
            id: id,
            porcentaje: porcentaje
        },
        success : function(data) {
                console.log(data);
        }
    });
}