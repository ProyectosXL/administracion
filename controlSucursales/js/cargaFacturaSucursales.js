const checkContabilizar = (div) => {

    allTd = div.parentElement.parentElement.querySelectorAll("td");
    let fecha = allTd[0].textContent;
    let nro_sucursal = allTd[1].textContent;
    let tipoComprobante = allTd[2].textContent;
    let nroComprobante = allTd[3].textContent;
    let codCuenta = allTd[4].textContent;
    let monto = allTd[6].textContent.replace(/[$.]/g, "");

    let accion = "";

    if(div.checked == true){
        accion = "checkContabilizar"
    }else{
        accion = "uncheckContabilizar"
    }

    $.ajax({
        type: "POST",
        url: "Controller/ControlEgresosController.php?accion="+accion,
        data: {
            fecha: fecha,
            nro_sucursal: nro_sucursal,
            tipoComprobante: tipoComprobante,
            nroComprobante: nroComprobante,
            codCuenta: codCuenta,
            monto: monto,

        },
        success: function (response) {
        }
    });

    
}

$(document).ready( function () {
    
    $(function() {
        $('[data-toggle="tooltip"]').tooltip()
    })

    $('#myTable').DataTable({
        "bLengthChange": false,
        "bInfo": false,
        "aaSorting": false,
        'columnDefs': [
            {
                "targets": "_all", 
                "className": "text-center",
                "sortable": false,
         
            },
        ],
        "oLanguage": {
    
            "sSearch": "Busqueda rapida:",
            "sSearchPlaceholder": "Sobre cualquier campo"
    
        },
    });

})


const mostrarImagen = (divImagen, startIndex = 0) => {

 
    let codigosImagenes = [];
  
    let nComp = divImagen.parentElement.parentElement.querySelectorAll("td")[3].textContent;

    
    let carouselElement = document.querySelector('#carruselImagenes'); 
    
  
    carouselElement.innerHTML = ''; 

    $.ajax({

      url: "Controller/ControlEgresosController.php?accion=contarImagenes",
      type: "POST",
      data: {
        nComp:nComp

      },

      success: function (response) {

        response = JSON.parse(response);

   
        if(response['cantidad'] > 0){

          
          for (let index = 0; index < response['nombre'].length  ; index++) {
            
            codigosImagenes.push(response['nombre'][index] + '.jpg');

          }
          
          let modalContent = document.createElement('div');
          modalContent.className = 'modal-dialog modal-dialog-centered';
          modalContent.style = 'max-width: 100%;';
          
          let modalBody = document.createElement('div');
          modalBody.className = 'modal-content';

          let carousel = document.createElement('div');
          carousel.innerHTML = "";
          carousel.className = 'carousel slide';
          carousel.setAttribute('data-ride', 'carousel');

          let carouselInner = document.createElement('div');
          carouselInner.className = 'carousel-inner';


          codigosImagenes.forEach((imagen, index) => {

            let carouselItem = document.createElement('div');
            carouselItem.className = index === startIndex ? 'carousel-item active' : 'carousel-item';
            carouselItem.style = "text-align:center"
            let imgElement = document.createElement('img');
            imgElement.src = '../../../../Imagenes/egresosCaja/' + imagen;

            carouselItem.appendChild(imgElement);
            carouselInner.appendChild(carouselItem);
          
    
          });

          carousel.appendChild(carouselInner);
          modalBody.appendChild(carousel);
          modalContent.appendChild(modalBody);
          carouselElement.appendChild(modalContent);

          // Crear los controles "anterior" y "siguiente" del carrusel
          let prevControl = document.createElement('a');
          prevControl.className = 'carousel-control-prev';
          prevControl.href = '#carruselImagenes';
          prevControl.role = 'button';
          prevControl.setAttribute('data-slide', 'prev');
          prevControl.setAttribute("onclick",'pasarImagen(-1)')
          let prevIcon = document.createElement('span');
          prevIcon.className = 'carousel-control-prev-icon';
          prevIcon.style = 'background-color: black';
          prevIcon.setAttribute('aria-hidden', 'true');
          prevControl.appendChild(prevIcon);


          let nextControl = document.createElement('a');
          nextControl.className = 'carousel-control-next';
          nextControl.href = '#carruselImagenes';
          nextControl.role = 'button';
          nextControl.setAttribute('data-slide', 'next');
          nextControl.setAttribute("onclick",'pasarImagen(1)')
          let nextIcon = document.createElement('span');
          nextIcon.className = 'carousel-control-next-icon';
          nextIcon.style = 'background-color: black';
          nextIcon.setAttribute('aria-hidden', 'true');
          nextControl.appendChild(nextIcon);


          carousel.appendChild(prevControl);
          carousel.appendChild(nextControl);

          // Activa el carrusel de Bootstrap
          $(carousel).carousel();

          // Muestra el modal
          $('#carruselImagenes').modal('show');

        }

      }
    })


}

const validarExistenciaArchivo = (rutaArchivo, callback) => {
    const img = new Image();
    img.onload = function() {
      // La imagen se ha cargado correctamente, por lo que el archivo existe
      callback(true);
    };
    img.onerror = function() {
      // La imagen no se pudo cargar, por lo que el archivo no existe
      callback(false);
    };
    img.src = rutaArchivo;
}

const pasarImagen = (pos) =>{

    let items = document.querySelectorAll(".carousel-item")
    
    for (let index = 0; index < items.length; index++) {
      
      if (items[index].classList.contains("active")) {
  
        if (items[index + pos] !== undefined) {
    
          items[index].classList.remove("active");
        
          items[index + pos].classList.add("active");
          break; // Detener el bucle una vez que se encontró el siguiente elemento activo
  
        }
  
      }
  
    }
  
}