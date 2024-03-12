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
    
const checkFactura = (div) => {


    allTd = div.parentElement.parentElement.querySelectorAll("td");
    let fecha = allTd[0].textContent;
    let nro_sucursal = allTd[1].textContent;
    let tipoComprobante = allTd[2].textContent;
    let nroComprobante = allTd[3].textContent;
    let codCuenta = allTd[4].textContent;
    let descripcionCuenta = allTd[5].textContent;
    let monto = allTd[6].textContent.replace(/[$.]/g, "");
    let leyenda = allTd[7].textContent;
    let factura = 0;
    if(allTd[10].querySelector("input").checked == true){ 
        factura = 1;
        
    }
    let control = 0;
    if(allTd[11].querySelector("input").checked == true){ 
        control = 1;

    }
    let accion = ""
    if(div.checked == true){

        accion = "checkFactura";

    }else{

        accion = "uncheckFactura";

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
            descripcionCuenta: descripcionCuenta,
            monto: monto,
            leyenda: leyenda,
            factura: factura,
            control: control,
        },
        success: function (response) {
        }
    });

}

const checkControl = (div) => {
 

    allTd = div.parentElement.parentElement.querySelectorAll("td");
    let fecha = allTd[0].textContent;
    let nro_sucursal = allTd[1].textContent;
    let tipoComprobante = allTd[2].textContent;
    let nroComprobante = allTd[3].textContent;
    let codCuenta = allTd[4].textContent;
    let descripcionCuenta = allTd[5].textContent;
    let monto = allTd[6].textContent.replace(/[$.]/g, "");;
    let leyenda = allTd[7].textContent;
    let factura = 0;
    if(allTd[10].querySelector("input").checked == true){ 
        factura = 1;
        
    }
    let control = 0;
    if(allTd[11].querySelector("input").checked == true){ 
        control = 1;

    }

    if(div.checked == true){

        accion = "checkControl";

    }else{

        accion = "uncheckControl";

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
            descripcionCuenta: descripcionCuenta,
            monto: monto,
            leyenda: leyenda,
            factura: factura,
            control: control,
        },
        success: function (response) {
        }
    });

}

// const comprobarChecks = () => {

//     let allFactura = document.querySelectorAll("#checkFactura");
//     allFactura.forEach(element => {
//         if(element.checked){
//             element.disabled = true;
//         }
//     });

//     let allControl = document.querySelectorAll("#checkControl");
//     allControl.forEach(element => {
//         if(element.checked){
//             element.disabled = true;
//         }
//     });

// }


const mostrarImagen = (divImagen, startIndex = 0) => {

 
    let codigosImagenes = [];
  
    let nComp = divImagen.parentElement.parentElement.querySelectorAll("td")[3].textContent;

    let codCuenta = divImagen.parentElement.parentElement.querySelectorAll("td")[4].textContent;

    let nroSucursal = document.querySelector("#selectSucursal").value.split("-")[0];
    
    let carouselElement = document.querySelector('#carruselImagenes'); 
    
  
    carouselElement.innerHTML = ''; 

    $.ajax({

      url: "Controller/ControlEgresosController.php?accion=contarImagenes",
      type: "POST",
      data: {
        nComp:nComp,
        nroSucursal:nroSucursal,
        codCuenta:codCuenta

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

          let closeButton = document.createElement('button');
          closeButton.type = 'button';
          closeButton.className = 'close';
          closeButton.setAttribute('data-dismiss', 'modal');
          closeButton.setAttribute('aria-label', 'Close');
          closeButton.innerHTML = '<span aria-hidden="true" title="Cerrar" style="font-size:50px; margin-right: 0.5rem; color: red;">&times;</span>';
      
          // Agregar el botón de cierre al encabezado del modal
          let modalHeader = document.createElement('div');
          modalHeader.appendChild(closeButton);
          modalBody.appendChild(modalHeader);

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

          let rotateButton = document.createElement('button');
          rotateButton.type = 'button';
          rotateButton.style.width = '400px'
          rotateButton.style.height = '50px'
          rotateButton.style.marginTop = '10px'
          rotateButton.style.marginBottom = '10px'
          rotateButton.style.marginLeft = '35%'
          rotateButton.className = 'btn btn-primary'; // Puedes ajustar las clases según tu estilo
          rotateButton.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Rotar Imagen';
          rotateButton.addEventListener('click', function() {
              rotarImagen();
          });

          modalBody.appendChild(rotateButton);
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


  
const rotarImagen = () => {
  let carousel = document.querySelector('#carruselImagenes .carousel-inner');
  let activeItem = carousel.querySelector('.carousel-item.active img');

  // Obtener el ángulo actual de rotación (en grados)
  let currentRotation = activeItem.getAttribute('data-rotation') || 0;

  // Incrementar el ángulo de rotación en 90 grados
  let newRotation = (parseInt(currentRotation) + 90) % 360;

  // Aplicar la rotación a la imagen
  activeItem.style.transform = `rotate(${newRotation}deg)`;

  // Actualizar el atributo data-rotation
  activeItem.setAttribute('data-rotation', newRotation);
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