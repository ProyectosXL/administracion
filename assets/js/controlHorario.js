


$(document).ready(function () {
    traerVendedores();

    $('#inputBusqueda').on('input', function() {
        filtrarTabla($(this).val().toLowerCase());
    });


});


const traerVendedores = () => {

    $.ajax({
        url : '../Controller/HorarioController.php?accion=traerVendedores',
        method : 'GET',
        dataType : 'json',
        success: function(data){
            localStorage.setItem("empleados", JSON.stringify(data));
            rellenarTableBody();
        }

    })
}


const rellenarTableBody = () => {

    obj = JSON.parse(localStorage.getItem("empleados"));

    let tablaDetalle = document.querySelector("#tableBody");

    tablaDetalle.innerHTML = "";
 
 
    for (let x = 0; x < obj.length; x++) {

      const tr=document.createElement('tr');
      tr.id = "trBodyDetalle";
      const td1=document.createElement('td');
      const td2=document.createElement('td');
      const td3=document.createElement('td');
      const td4=document.createElement('td');

      const td5=document.createElement('td');
      const td6=document.createElement('td');
      const td7=document.createElement('td');
      const td8=document.createElement('td');
 
      const td9=document.createElement('td');
      td9.hidden = true;
      
      const td10=document.createElement('td');
      td10.hidden = true;

      let checkbox1 = document.createElement("input");
      checkbox1.type = "checkbox";
  
        let button8 = document.createElement("a");
        button8.setAttribute("href", "editarEmpleado.php?nroLegajo="+obj[x]['NRO_LEGAJO']);
        button8.innerHTML = '<i class="fa-solid fa-pencil"></i>';
        // button8.className = "btn btn-primary";
        
        button8.style.color = "black";
        button8.style.border = "none";
    //   const text1=document.createTextNode(obj[x]['COD_ARTICULO']);
      const text2=document.createTextNode(obj[x]['NRO_LEGAJO']);
      const text3=document.createTextNode(obj[x]['APELLIDO']);
      const text4=document.createTextNode(obj[x]['NOMBRE']);
      const text5=document.createTextNode(obj[x]['TAREA_HABITUAL']);
      const text6=document.createTextNode(obj[x]['LOCALIDAD']);
      const text7=document.createTextNode(obj[x]['NRO_SUCURS']);
      const text9=document.createTextNode(obj[x]['BLOQUE']);
      const text10=document.createTextNode(obj[x]['HABILITADO']);




      td1.appendChild(checkbox1);
      td2.appendChild(text2);
      td3.appendChild(text3);
      td4.appendChild(text4);
      td5.appendChild(text5);
      td6.appendChild(text6);
      td7.appendChild(text7);
      td8.appendChild(button8);
      td9.appendChild(text9);
      td10.appendChild(text10);
      
    

      tr.appendChild(td1);
      tr.appendChild(td2);
      tr.appendChild(td3);
      tr.appendChild(td4);
      tr.appendChild(td5);
      tr.appendChild(td6);
      tr.appendChild(td7);
      tr.appendChild(td8);
      tr.appendChild(td9);
      tr.appendChild(td10);

      
      tablaDetalle.appendChild(tr);
    }
 

}



const filtrarTabla = (textoBusqueda) => {
    $('#tableBody tr').each(function() {
        // Obtener el texto de todas las celdas en la fila y convertirlo a minúsculas
        let textoFila = $(this).text().toLowerCase();
        // Mostrar la fila si el texto de la fila incluye el texto de búsqueda, de lo contrario, ocultarla
        $(this).toggle(textoFila.indexOf(textoBusqueda) > -1);
    });
}


// Agregar controlador de eventos al cambio de valor del select
$('#selectFiltro').on('change', function() {
    // Obtener el valor seleccionado del select
    let filtro = $(this).val().toLowerCase();
    console.log(filtro);
    // Recorrer las filas de la tabla
    $('#tableBody tr').each(function() {
        // Obtener el texto del td9 en la fila actual
        let textoFila = $(this).find('td:eq(4)').text().toLowerCase();
    
        // Mostrar u ocultar la fila según el valor seleccionado del select y el texto del td9
        if (filtro === '%' || textoFila == filtro) {
            $(this).show(); // Mostrar la fila
        } else {
            $(this).hide(); // Ocultar la fila
        }
    });
});


function cambiarFormatoCuadricula(button) {
    
    button.innerHTML = '<i class="bi bi-grid-3x3-gap-fill" style="color:white"></i>'
    // Crear el div principal con la clase y estilos
    button.setAttribute("onclick", "cambiarFormatoTabla(this)");


    let tdPrincipal = document.querySelector("#tdPrincipal").innerHTML = '';

    let allT = localStorage.getItem("empleados");
    allT = JSON.parse(allT);

    // const allTdArray = Array.from(allTd);
    const grupos = [];
    const tamanoGrupo = 3;
    for (let i = 0; i < allT.length; i += tamanoGrupo) {
        grupos.push(allT.slice(i, i + tamanoGrupo));
    }

    grupos.forEach(grupo => {
        let html = `
            <div class="row ml-2" style="width:96%; display: flex; flex-wrap: nowrap;margin-bottom:20px">
        `;
        
        grupo.forEach(td => {
     
            let estado = '';
            if (td['HABILITADO'] == "S") {
                estado = '<span style="color:green">Activo</span>';
            } else {
                estado = '<span style="color:red">Inactivo</span>';
            }

            html += `
                <div class="col-4 ml-2" id="cuadrado">
                    <div class="row">
                        <div class="row ml-2" style="width:100%">
                            <div class="col" style="text-align:left;color:#5095e3"><strong>${td['NRO_LEGAJO']}</strong></div>
                            <div class="col"></div>
                            <div class="col"><a href='editarEmpleado.php?nroLegajo=${td['NRO_LEGAJO']}' style="color:black"><i class="bi bi-three-dots"></i></a></div>
                        </div>
                        <div class="row ml-2">
                            <div>
                                <img src="../../assets/images/pruebafoto.png" alt="" style="height: auto; width: 85%;max-width:80%">
                            </div>
                            <div class="row ml-2" style="width:98%">
                                <div class="col" style="color:#5095e3"><strong>${td['APELLIDO']} ${td['NOMBRE']}</strong></div>
                            </div>
                            <div class="row ml-2" style="width:98%;display: flex; flex-wrap: nowrap;">
                                <div class="col" style="text-align:left">
                                    <img src="../../assets/images/Brillo1.png" alt="" style="height: 40px; width: 40px;margin-left:10%"> 
                                    ${td['BLOQUE']} - ${td['TAREA_HABITUAL']}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row ml-2" style="border-top:solid 1px grey;width:99%">
                        <div class="row" style="width:99%">
                         
                            <div class="col"><br></div>
                      
                        </div>
                        <div class="row" style="width:99%">
                          
                            <div class="col"><br></div>
                        
                        </div>
                        <div class="row" style="width:99%">
                       
                            <div class="col" style="text-align:center"><strong>Estado:${estado}</strong></div>
                  
                        </div>
                    </div>
                </div>
            `;
        });

        html += `
            </div>
        `;

        // Agregar el HTML generado al contenedor principal
        document.querySelector("#tdPrincipal").innerHTML += html;
    });

    return 1;
}

function cambiarFormatoTabla(button){
   
    let allT = localStorage.getItem("empleados");

    document.querySelector("#tdPrincipal").innerHTML = `
    <table style="width:100%">
    <thead >
      <tr style="background-color:#59b9e2;color:white">
          <td> 
                  <input type="checkbox" id="miCheckbox" class="custom-checkbox">
                  <label for="miCheckbox" class="custom-checkbox-label"></label>
          </td>
          <td>Legajo</td>
          <td>Apellido</td>
          <td>Nombres</td>
          <td>Cargo</td>
          <td>Localidad</td>
          <td>Local</td>
          <td>Accion</td>
      </tr>
    </thead>
    <tbody id="tableBody">
      <tr>
        
      </tr>
    </tbody>
  </table> 
  `;

    rellenarTableBody(allT);
    button.innerHTML = '<i class="fa-regular fa-address-card" style="color:white"></i>'
    button.setAttribute("onclick", "cambiarFormatoCuadricula(this)");
    return 1;
}
// Llamar a la función para crear el div
// crearDiv();
