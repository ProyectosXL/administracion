const agregar = () => {

    let concepto = document.getElementById('conceptos').value;
    let idConcepto = concepto.split("-")[0];
    let idLocal = document.getElementById('locales').value;
    let index = document.getElementById('locales').selectedIndex;
    let descLocal = document.getElementById('locales').querySelectorAll("option")[index].textContent;

    $.ajax({
        url: 'Controller/PorcentajeController.php?accion=insertarPorcentaje',   
        method: 'POST',
        data: {
            idConcepto: idConcepto,
            idLocal: idLocal,
            descLocal: descLocal
        },
        success : function(data) {
           
            if (data == 1) {
       
                Swal.fire({
                    title: 'Sucursal Agregada!',
                    icon: 'success',
                    confirmButtonText: `Ok`,
                    })
                    .then((e) => {
                        location.reload()
                    })

            } else {
                Swal.fire({
                    title: 'La Sucursal Ya Existe!',
                    icon: 'error',
                    confirmButtonText: `Ok`,
                    })
                    .then((e) => {
                    })
            }  
        }
    });

}

const actualizarPorcentaje = (fila) => {
    
    let todosLosTd = fila.parentElement.parentElement.querySelectorAll("td");
    let id = todosLosTd[0].textContent;
    let porcentaje = todosLosTd[3].querySelector("input").value;

    $.ajax({
        url: 'Controller/PorcentajeController.php?accion=actualizarPorcentaje',   
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


const eliminarPorcentaje = (fila) => {

    let todosLosTd = fila.parentElement.parentElement.querySelectorAll("td");
    let id = todosLosTd[0].textContent;

    $.ajax({
        url: 'Controller/PorcentajeController.php?accion=eliminarPorcentaje',   
        method: 'POST',
        data: {
            id: id,
        },
        success : function(data) {
            location.reload()
        }
    });
}

const cambiarEntorno = (t) =>{

    let entorno = 0;
  
    if(t.getAttribute("data-off") == "ARG" ){
      entorno = 0;
    }else{
      entorno = 1;
    }
  
  
    $.ajax({
      url: "Controller/cambiarEntorno.php",
      method: "POST",
      data : {entorno: entorno},
      success: function (data) {
        location.reload();
      }
    });
  
  }