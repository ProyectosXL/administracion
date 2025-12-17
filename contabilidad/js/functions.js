window.addEventListener("DOMContentLoaded", iniciarEscuchaSelect); //1 - cuando se termina de carga toda la pagina, comienza a escuchar los eventos del dom
 
let a;
const selectRubro = document.querySelectorAll(".codRubro"); //2 - guardo en un array todos los check donde se va escuchar si se produjo un cambio
const selectProrrateo = document.querySelectorAll(".codProrrateo");
const checkExcluir = document.querySelectorAll(".checkExcluir");
const checkControlado = document.querySelectorAll(".checkControlado");
const checkAmortizado = document.querySelectorAll(".checkAmortizado");
const inputAmortiza = document.querySelectorAll(".amortiza");
const selectCentroCosto = document.querySelector("#selectCentroCosto");
const btnEjecutar = document.querySelector("#btnEjecutar");

const validarModulos = () => {
    let periodo = document.querySelector("#periodo").getAttribute("attr-periodo");
    
    $.ajax({
        url: 'Controller/controlGastosController.php?accion=validarModulos',
        method: 'POST',
        data: {periodo: periodo},
        dataType: 'json',
        success: function (response) {
            if (!response.success) {
                // Solo mostrar alerta si hay módulos faltantes, no si el periodo no existe
                if (response.modulosFaltantes && response.modulosFaltantes.length > 0) {
                    Swal.fire({
                        title: 'Módulos Faltantes',
                        text: response.message,
                        icon: 'warning',
                        confirmButtonText: 'Aceptar',
                        allowOutsideClick: false
                    });
                }
            }
        },
        error: function (xhr, status, error) {
            console.error('Error al validar módulos:', error);
        }
    });
};

let periodo = document.querySelector("#periodo").getAttribute("attr-periodo");
let pasoActual = 0;

let payloads =  {

  "desde": document.getElementsByName("desde")[0].value,
  "hasta": document.getElementsByName("hasta")[0].value,
  "periodo": document.querySelector("#periodo").getAttribute("attr-periodo")
  
}


let conexion;

function iniciarEscuchaSelect() {
  // Agregar event listeners para botones (después de que las funciones estén disponibles)
  if (btnEjecutar) {
    btnEjecutar.addEventListener("click", ejecutarPasos);
  }
  
  // Llamar a pintarPasos si existe el elemento periodo
  const periodoElement = document.querySelector("#periodo");
  if (periodoElement) {
    const periodo = periodoElement.getAttribute("attr-periodo");
    // Usar setTimeout para asegurar que pintarPasos esté definido
    setTimeout(() => {
      pintarPasos(periodo);
    }, 0);
  }
  
  //3 - se llama a la funcion de paso 1
  selectRubro.forEach(
    (
      select // 4 - recorre cada elemento del array, para saber quien es el elto donde se produjo el click
    ) => select.addEventListener("change", completarCampoRubro)
  );
  selectProrrateo.forEach((select) =>
    select.addEventListener("change", completarCampoProrrateo)
  );

  checkExcluir.forEach((select) => {
    select.addEventListener("change", (e) => {
      guardarExcluir(0, e);
    });
  });

  checkControlado.forEach((select) => {
    select.addEventListener("change", (e) => {
      guardarControlado(0, e);
    });
  });

  inputAmortiza.forEach((select) => {
    select.addEventListener("change", guardarAmortizar);
  });
}

function guardarExcluir(datoMasivo = 0, e) {
  let excluir;
  let dato = datoMasivo != 0 ? datoMasivo : e.target;
  if (dato.checked == true) {
    excluir = 1;
  } else {
    excluir = 0;
  }
  let ID = dato.parentElement.parentElement.children[20].textContent;
  /*   let codRubro = Dato.value;
  let rubroDesc = Dato.parentElement.parentElement.children[11]; */
  conexion = new XMLHttpRequest();
  conexion.onreadystatechange = () => {
    if (conexion.readyState == 4 && conexion.status == 200) {
      console.info("cambios guardados");
      console.log(conexion.responseText);
    } else {
      console.warn("error al grabar");
      console.log(conexion.responseText);
    }
  };
  conexion.open("POST", "./Class/update.php", false);
  conexion.setRequestHeader(
    "Content-Type",
    "application/x-www-form-urlencoded"
  );
  let infoActualizar =
    "ID=" + encodeURIComponent(ID) + "&checked=" + encodeURIComponent(excluir);
  conexion.send(infoActualizar);
}

function guardarAmortizar(e) {
  let dato = e.target;
  let amortizar = dato.value;
  let ID = dato.parentElement.parentElement.children[20].textContent;
  conexion = new XMLHttpRequest();
  conexion.onreadystatechange = () => {
    if (conexion.readyState == 4 && conexion.status == 200) {
      console.info("cambios guardados");
      console.log(conexion.responseText);
    } else {
      console.warn("error al grabar");
      console.log(conexion.responseText);
    }
  };
  conexion.open("POST", "./Class/update.php", false);
  conexion.setRequestHeader(
    "Content-Type",
    "application/x-www-form-urlencoded"
  );
  let infoActualizar =
    "ID=" +
    encodeURIComponent(ID) +
    "&amortizar=" +
    encodeURIComponent(amortizar);
  conexion.send(infoActualizar);
}

let eh;
function guardarControlado(datoMasivo = 0, e) {
  let controlado;
  eh = datoMasivo;

  /* let dato = e.target; */
  let dato = datoMasivo != 0 ? datoMasivo : e.target;
  let codRubro =
    datoMasivo != 0
      ? datoMasivo.parentElement.parentElement.children[10].children[0].value
      : e.target.parentElement.parentElement.children[10].children[0].value;
  let codProrrateo =
    datoMasivo != 0
      ? datoMasivo.parentElement.parentElement.children[12].children[0].value
      : e.target.parentElement.parentElement.children[12].children[0].value;
  if (dato.checked == true && codRubro !== "" && codProrrateo !== "") {
    controlado = 1;
  } else {
    if (codRubro == "" && codProrrateo == "") {
      Swal.fire({
        icon: "error",
        title: "Error de control",
        text: "Debe definir el código del rubro contable y código de prorrateo!",
      });
    } else {
      if (codRubro == "") {
        Swal.fire({
          icon: "error",
          title: "Error de control",
          text: "Debe definir el código del rubro contable!",
        });
      } else {
        if (codProrrateo == "") {
          Swal.fire({
            icon: "error",
            title: "Error de control",
            text: "Debe definir el código de prorrateo!",
          });
        }
      }
    }
    controlado = 0;
    dato.checked = false;
  }
  let ID = dato.parentElement.parentElement.children[20].textContent;
  /*   let codRubro = Dato.value;
  let rubroDesc = Dato.parentElement.parentElement.children[11]; */
  conexion = new XMLHttpRequest();
  conexion.onreadystatechange = () => {
    if (conexion.readyState == 4 && conexion.status == 200) {
      console.info("cambios guardados");
      console.log(conexion.responseText);
    } else {
      console.warn("error al grabar");
      console.log(conexion.responseText);
    }
  };
  conexion.open("POST", "./Class/update.php", false);
  conexion.setRequestHeader(
    "Content-Type",
    "application/x-www-form-urlencoded"
  );
  let infoActualizar =
    "ID=" +
    encodeURIComponent(ID) +
    "&controlado=" +
    encodeURIComponent(controlado);
  conexion.send(infoActualizar);
}

function completarCampoRubro(e) {
  // 5 - al evento change de un codRubro se llama a la funcion completarCampoRubro, e , es el evento con la información de cual elemnto del dom fue clickeado
  let ID = e.parentElement.parentElement.children[20].textContent;
  let codRubro = e.value;
  let rubroDesc = e.parentElement.parentElement.children[11];
  conexion = new XMLHttpRequest();
  conexion.onreadystatechange = () => {
    if (conexion.readyState == 4 && conexion.status == 200) {
      rubroDesc.textContent = conexion.responseText;
      guardarCambiosRubro(ID, codRubro, rubroDesc.textContent);
    }
  };
  conexion.open("GET", "Class/rubroContable.php?codigo=" + e.value, true);
  conexion.send();
}

function completarCampoProrrateo(e) {
  let Dato = e.target;
  /*  let cuenta = Dato.parentElement.parentElement.children[4].textContent; */
  let ID = Dato.parentElement.parentElement.children[20].textContent;
  let codProrrateo = Dato.value;
  let prorrateoDesc = Dato.parentElement.parentElement.children[13];
  /* let txtDescProrrateo = e.target; */
  conexion = new XMLHttpRequest();
  conexion.onreadystatechange = () => {
    if (conexion.readyState == 4 && conexion.status == 200) {
      /*  Dato.parentElement.parentElement.children[14].textContent =
        conexion.responseText; */
      prorrateoDesc.textContent = conexion.responseText;
      guardarCambiosProrrateo(ID, codProrrateo, prorrateoDesc.textContent);
    }
  };
  conexion.open("GET", "Class/prorrateo.php?codigo=" + codProrrateo, true);
  conexion.send();
}

function guardarCambiosRubro(ID, codRubro, rubroDesc) {
  conexion = new XMLHttpRequest();
  conexion.onreadystatechange = () => {
    if (conexion.readyState == 4 && conexion.status == 200) {
      console.info("cambios guardados");
      console.log(conexion.responseText);
    } else {
      console.warn("error al grabar");
      console.log(conexion.responseText);
    }
  };
  conexion.open("POST", "./Class/update.php", false);
  conexion.setRequestHeader(
    "Content-Type",
    "application/x-www-form-urlencoded"
  );
  let infoActualizar =
    "ID=" +
    encodeURIComponent(ID) +
    "&codRubro=" +
    encodeURIComponent(codRubro) +
    "&descRubro=" +
    encodeURIComponent(rubroDesc.trimStart());
  conexion.send(infoActualizar);
}

function guardarCambiosProrrateo(ID, codProrrateo, prorrateoDesc) {
  conexion = new XMLHttpRequest();
  conexion.onreadystatechange = () => {
    if (conexion.readyState == 4 && conexion.status == 200) {
      console.info("cambios guardados");
      console.log(conexion.responseText);
    } else {
      console.warn("error al grabar");
      console.log(conexion.responseText);
    }
  };
  conexion.open("POST", "./Class/update.php", false);
  conexion.setRequestHeader(
    "Content-Type",
    "application/x-www-form-urlencoded"
  );
  let infoActualizar =
    "ID=" +
    encodeURIComponent(ID) +
    "&codProrrateo=" +
    encodeURIComponent(codProrrateo) +
    "&descRubro=" +
    encodeURIComponent(prorrateoDesc.trimStart());
  conexion.send(infoActualizar);
}

//Amortiza los gastos que tienen seteado el campo amortiza//
function amortizarGastos() {

  let desde = document.getElementsByName("desde")[0].value;
  let hasta = document.getElementsByName("hasta")[0].value;
  let spinner = document.getElementById("boxLoading");
  spinner.className += " loading";
  
  $('#myTable').DataTable().destroy();
  let allTd = document.querySelector("tbody").querySelectorAll("tr")

  
  $('#myTable').DataTable({
    responsive: true,
  });

  let paso8 = document.querySelector("#paso8");

  if(!(paso8.className == "active")){
    spinner.classList.remove('loading');
    Swal.fire({
      icon: "error",
      title: "Error",
      text: "Complete todos los pasos de control antes de continuar!",
    });

    return 1;

  }


  $.ajax({
    url: 'Controller/controlGastosController.php?accion=validarPendienteDeAsignar',
    method: 'POST',
    data: {
      desde: desde,
      hasta: hasta
    },
    success: function (data) {

      data = data.trim()
    

      if(data == 'false'){

        $.ajax({
          url: 'Controller/controlGastosController.php?accion=validarPendienteControl',
          method: 'POST',
          data: {
            desde: desde,
            hasta: hasta
          },
          success: function (data) {

            data = data.trim()
            spinner.classList.remove('loading');

            if(data == 'false'){

              $.ajax({
                url: 'Controller/controlGastosController.php?accion=validarPendienteAmortizar',
                method: 'POST',
                data: {
                  desde: desde,
                  hasta: hasta
                },
                success: function (data) {
                  data = data.trim()
                  spinner.classList.remove('loading');

                  if(data == 'false'){

                    Swal.fire({
                      icon: "error",
                      title: "Error",
                      text: "No existen registros para amortizar",
                    });

                  }else{

                    conexion = new XMLHttpRequest();
                    conexion.open(
                      "POST",
                      "./Controller/amortizar.php?estado=1&desde=" + desde + "&hasta=" + hasta,
                      true
                    ); // no se envia fecha inicio y fin
                    conexion.onreadystatechange = ejecutarQuery;
                    conexion.send();

      
                  }

                }
              })

           
            }else{

              spinner.classList.remove('loading');

              Swal.fire({
                icon: "error",
                title: "Error",
                text: "Aún hay registros pendientes de controlar",
              });

            }
          }
        })
      }else{

        spinner.classList.remove('loading');

        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Aún hay registros pendientes de asignar",
        });

      }
    }

  });
  
}

function ejecutarQuery() {
  Swal.fire({
    icon: "warning",
    title: "Desea amortizar los gastos?",
    showDenyButton: true,
    showCancelButton: false,
    confirmButtonText: "Procesar",
    denyButtonText: `No procesar`,
  }).then((result) => {
    /* Read more about isConfirmed, isDenied below */
    if (result.isConfirmed) {
      if (revisar() != 1) {
        Swal.fire("Los gastos fueron amortizados!", "", "success").then(
          function () {
            window.location.reload();
          }
        );
      } else {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "No hay gastos para amortizar o falta el control. Por favor revise los registros filtrados",
        });
      }
    } else if (result.isDenied) {
      Swal.fire("Los gastos no fueron amortizados", "", "info");
    }
  });
}

function revisar() {
  let b = 0;
  let inputAmortizaSelect = document.querySelectorAll(".amortiza");
  if (inputAmortizaSelect.length > 0) {
    inputAmortizaSelect.forEach((ele) => {
      let checkControlado =
        ele.parentElement.parentElement.children[16].children[0].checked; //guarda el valor del check del campo amortiza de la fila correspondiente
      if (
        (ele.value == "" && checkAmortizado == true) ||
        checkControlado == false
      ) {
        b = 1;
      }
    });
  } else {
    return 1;
  }
  if (b == 1) {
    return 1;
  } else {
    return 0;
  }
}

function checkControladoAll(source) {
  var checkboxes = document.querySelectorAll(".checkControlado");

  for (var i = 0; i < checkboxes.length; i++) {
    if (checkboxes[i] != source) checkboxes[i].checked = true;

    guardarControlado(checkboxes[i]);
  }
}

function uncheckControladoAll(source) {
  var checkboxes = document.querySelectorAll(".checkControlado");

  for (var i = 0; i < checkboxes.length; i++) {
    if (checkboxes[i] != source) checkboxes[i].checked = false;

    guardarControlado(checkboxes[i]);
  }
}

function checkExcluirAll(source) {
  var checkboxes = document.querySelectorAll(".checkExcluir");
  for (var i = 0; i < checkboxes.length; i++) {
    if (checkboxes[i] != source) checkboxes[i].checked = true;

    guardarExcluir(checkboxes[i]);
  }
}
function uncheckExcluirAll(source) {
  var checkboxes = document.querySelectorAll(".checkExcluir");
  for (var i = 0; i < checkboxes.length; i++) {
    if (checkboxes[i] != source) checkboxes[i].checked = false;

    guardarExcluir(checkboxes[i]);
  }
}

function prorratearGastos() {
  /*  $nombre = document.querySelector("#nombre"), */
  let spinner = document.getElementById("boxLoading");
  spinner.className += " loading";

  let desde = document.getElementsByName("desde")[0].value;
  let hasta = document.getElementsByName("hasta")[0].value;


  $('#myTable').DataTable().destroy();
  let allTd = document.querySelector("tbody").querySelectorAll("tr")

  if(!(paso8.className == "active")){
    spinner.classList.remove('loading');
    Swal.fire({
      icon: "error",
      title: "Error",
      text: "Complete todos los pasos de control antes de continuar!",
    });
    return 1;
    
  }

  $.ajax({
    url: 'Controller/controlGastosController.php?accion=validarPendienteDeAsignar',
    method: 'POST',
    data: {
      desde: desde,
      hasta: hasta
    },
    success: function (data) {
      data = data.trim()
    

      if(data == 'false'){

        $.ajax({
          url: 'Controller/controlGastosController.php?accion=validarPendienteControl',
          method: 'POST',
          data: {
            desde: desde,
            hasta: hasta
          },
          success: function (data) {
            data = data.trim()
            

            if(data == 'false'){
              
              $.ajax({
                url: 'Controller/controlGastosController.php?accion=validarPendienteAmortizar',
                method: 'POST',
                data: {
                  desde: desde,
                  hasta: hasta
                },
                success: function (data) {

                  data = data.trim()
                  spinner.classList.remove('loading');

                  if(data == 'false'){
                    
                     $('#myTable').DataTable({
                      responsive: true,
                    });

                    const swalWithBootstrapButtons = Swal.mixin({
                      customClass: {
                        confirmButton: "btn btn-success",
                        cancelButton: "btn btn-danger",
                      },
                      buttonsStyling: false,
                    });

                      swalWithBootstrapButtons
                      .fire({
                        title: "Desea realizar el prorrateo?",
                        text: "Ya no se podran deshacer los cambios!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonText: "Ok, prorratear!",
                        cancelButtonText: "No, cancelar!",
                        reverseButtons: true,
                      })
                      .then((result) => {
                        if (result.isConfirmed) {
                          let btn = document.getElementsByClassName(".btnProrrateo");
                          let spinner = document.getElementById("boxLoading");
                          spinner.className += " loading";
                          /******************************* */
                          fetch("./Controller/prorratear.php?desde=" + desde + "&hasta=" + hasta)
                            .then((respuesta) => respuesta.json())
                            .then((perfil) => {
                              if (perfil.resultado == 0) {
                                btn.className += "active";
                                spinner.classList.remove("loading");
                                Swal.fire({
                                  icon: "error",
                                  title: "Error",
                                  text: "No hay gastos para prorratear!",
                                });
                              } else {
                                btn.className += "active";
                                spinner.classList.remove("loading");
                                swalWithBootstrapButtons.fire(
                                  "Prorrateado!",
                                  "Los gastos fueron prorrateados",
                                  "success"
                                );
                              }
                            });
                          /******************************** */
                        } else if (
                          /* Read more about handling dismissals below */
                          result.dismiss === Swal.DismissReason.cancel
                        ) {
                          swalWithBootstrapButtons.fire(
                            "Cancelado",
                            "Los gastos no fueron prorrateados :(",
                            "error"
                          );
                        }
                      });

                  }else{

                    Swal.fire({
                      icon: "error",
                      title: "Error",
                      text: "Aún hay registros pendientes de amortizar",
                    });
                    
                  }

                }
              })
             

            }else{
              
              spinner.classList.remove('loading');

              Swal.fire({
                icon: "error",
                title: "Error",
                text: "Aún hay registros pendientes de controlar",
              });

            }
          }
        })

      }else{

        spinner.classList.remove('loading');

        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Aún hay registros pendientes de asignar",
        });
    
      }
    },
    error: function (error) {
      reject(error);
    }
  });


}
const activarModalPaso1 = () => {

  let desde = document.querySelector("#desde").value;
  let hasta = document.querySelector("#hasta").value;

  $.ajax({
          url: 'Controller/rentabilidadBruta.php?accion=traerRentabilidad',
          method: 'POST',
          data:{
              desde:desde,
              hasta:hasta
          },
          success : function(data) {
             rellenarModal5(data);
          }
      })

  $('#modalVb').modal('toggle');
}

function ejecutarPasos() {
  

  if(pasoActual == 8){
    Swal.fire({
      icon: "success",
      title: "Control exitoso",
      text: `Todos Los Pasos Se Realizaron Correctamente`,
    });
    return 1;
  }

  pasoActual = pasoActual + 1;


  let pasosDirectos = [1, 5, 6, 7, 8];


 
  const swalWithBootstrapButtons = Swal.mixin({
    customClass: {
      confirmButton: "btn btn-success",
      cancelButton: "btn btn-danger",
    },
    buttonsStyling: false,
  });

  swalWithBootstrapButtons
    .fire({
      title: "Desea ejecutar el proceso de control?",
      text: "Ya no se podran deshacer los cambios!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Ok, ejecutar!",
      cancelButtonText: "No, cancelar!",
      reverseButtons: true,
    })
    .then((result) => {
      if (result.isConfirmed) {
        /******************************* */
        let paso = document.getElementById("paso" + pasoActual);
        let spinner = document.getElementById("boxLoading");
        spinner.className += " loading";      
 /*otro fetch*/

        fetch("./Controller/ejecutarPasos.php?paso="+pasoActual,
          {
            method: 'POST',
            body: JSON.stringify(payloads)
          }
        )
        .then((respuesta) => respuesta.json())
        .then((perfil) => { 

          console.log("Paso:", pasoActual); // Agrega este log
          console.log("Perfil data:", perfil); // Agrega este log
          console.log("Perfil length:", perfil.length); // Agrega este log

            // Caso especial: Paso 2 con array vacío o con resultado 0
            // El SP ya marca el paso y ejecuta el resumen automáticamente
            if (pasoActual == 2 && perfil.length == 1 && perfil[0].RESULTADO === 0) {
              spinner.classList.remove('loading');
              Swal.fire({
                icon: "success",
                title: "Control exitoso",
                text: `Paso ${pasoActual} realizado! No se encontraron diferencias.`,
              });
              paso.className += "active";
              return;
            }

            if (perfil.length == 0 || pasosDirectos.includes(pasoActual) == true ) {

              spinner.classList.remove('loading');
              
              if (pasoActual == 8){
       
                if(perfil == false){
                  Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se encuentra cargado el coeficiente de ajuste!'
                  }).then(function () {

                    let periodo = document.querySelector("#periodo").getAttribute("attr-periodo");
                    document.querySelector("#ca-periodo").value = periodo;
                    $('#modalCA').modal('toggle');

                  });
                  pasoActual = pasoActual - 1;
                  return 1;
                }
                
              }

              if (pasoActual == 1){

                activarModalPaso1();
                return 1
              }

              Swal.fire({
                icon: "success",
                title: "Control exitoso",
                text: `Paso ${pasoActual} realizado!`,
              });
              paso.className += "active";
              
            }else{
              
              console.log("Entrando al switch para mostrar modal"); // Agrega este log
              spinner.classList.remove('loading');

              switch (pasoActual) {

                case 2:
                  rellenarModal2(perfil);
                  $("#modalVct").modal("toggle");
                  break;

                case 3:
                  rellenarModal3(perfil);
                  $("#modalCn").modal("toggle");
                  break;


                case 4:
                  rellenarModal4(perfil);
                  $("#modalPc").modal("toggle");
                  break;

                default:
                  break;
              }

              pasoActual = pasoActual - 1;
            }
          });
        /******************************** */
      } else if (
        /* Read more about handling dismissals below */
        result.dismiss === Swal.DismissReason.cancel
      ) {
        swalWithBootstrapButtons.fire(
          "Cancelado",
          "El paso de control no fue ejecutado",
          "error"
        );
      }
    });
}

const pintarPasos = (periodo) => {
  fetch("./Controller/consultarPasos.php?periodo=" + periodo)
    .then((respuesta) => respuesta.json())
    .then((data) => {
      let pasos = [];

      if (data < 1) {
        return false;
      }

      for (let i = 0; i < 8; i++) {
        pasos[i] = data[0]['PASO_'+(i+1)];
      }
      pasos.forEach((element,x) => {
        
        if(element != null && element != 0){

        let paso = document.getElementById("paso"+(x+1));

        pasoActual = x+1;

        paso.className = "active";

        }
      });
    });
};

const rellenarModal3 = (obj)=>{
  let tableModal = document.querySelector("#tableCn");
  tableModal.innerHTML = "";

  for (let x = 0; x < obj.length ; x++) {
    let tr = document.createElement('tr');

    let td1 = document.createElement('td');
    let td2 = document.createElement('td');
    let td3 = document.createElement('td');
    let td4 = document.createElement('td');
    let td5 = document.createElement('td');
    
    // Input para el costo de nacionalización
    let inputCosto = document.createElement('input');
    inputCosto.type = 'number';
    inputCosto.className = 'form-control costo-nac';
    inputCosto.step = '0.01';
    inputCosto.placeholder = 'Ingrese costo';
    
    // Botón guardar
    let btnGuardar = document.createElement('button');
    btnGuardar.className = 'btn btn-primary btn-sm';
    btnGuardar.innerHTML = '<i class="bi bi-save"></i> Guardar';
    btnGuardar.onclick = function() {
      guardarCostoNacionalizacion(
        obj[x]['COD_ARTICU'],
        inputCosto.value
      );
    };
    
    let text1 = document.createTextNode(obj[x]['COD_ARTICU']);
    let text2 = document.createTextNode(obj[x]['RUBRO']);
    let text3 = document.createTextNode(obj[x]['N_ORDEN_CO'] || '');
    
    td1.appendChild(text1);
    td2.appendChild(text2);
    td3.appendChild(text3);
    td4.appendChild(inputCosto);
    td5.appendChild(btnGuardar);
    
    tr.appendChild(td1);
    tr.appendChild(td2);
    tr.appendChild(td3);
    tr.appendChild(td4);
    tr.appendChild(td5);

    tableModal.appendChild(tr);
  }
};

const guardarCostoNacionalizacion = (codArticulo, costoNac) => {
  console.log('Iniciando guardarCostoNacionalizacion', { codArticulo, costoNac });
  
  // Convertir y validar el costo
  const costoNumerico = parseFloat(costoNac.toString().replace(',', '.'));
  
  if (isNaN(costoNumerico) || costoNumerico <= 0) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Ingrese un costo de nacionalización válido'
    });
    return;
  }

  // Obtener la fecha del primer día del mes seleccionado
  const mes = document.getElementById('mes').value;
  const anio = document.getElementById('selectAño').value;
  const fecha = `${anio}-${mes}-01`;

  // Crear objeto con los datos a enviar
  const datos = {
    codArticulo: codArticulo.trim(),
    costoNac: costoNumerico.toFixed(6), // Aseguramos precisión para tipo real
    fecha: fecha
  };

  console.log('Datos a enviar:', datos);

  $.ajax({
    url: 'Controller/guardarCostoNac.php',
    method: 'POST',
    data: {
      codArticulo: codArticulo,
      costoNac: costoNac,
      fecha: fecha
    },
    dataType: 'json',
    success: function(response) {
      if (response.success) {
        Swal.fire({
          icon: 'success',
          title: 'Éxito',
          text: 'Costo de nacionalización guardado correctamente',
          timer: 2000,
          showConfirmButton: false
        }).then(() => {
          // Recargar solo la tabla del modal
          $('#modalCn').modal('hide');
          ejecutarPasos();
        });
      } else {
        console.error('Error del servidor:', response.error);
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: response.error || 'Error al guardar el costo de nacionalización'
        });
      }
    },
    error: function(xhr, status, error) {
      let errorMsg = 'Error al guardar el costo de nacionalización';
      
      try {
        const response = JSON.parse(xhr.responseText);
        if (response.error) {
          errorMsg = response.error;
        }
      } catch (e) {
        console.error('Error al parsear la respuesta:', xhr.responseText);
      }

      console.error('Error en la solicitud:', {
        status: status,
        error: error,
        response: xhr.responseText
      });

      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: errorMsg
      });
    }
  });
};

const rellenarModal4 = (obj) => {

    let tableModal =  document.querySelector("#tableModalPc");
    tableModal.innerHTML = "";

    for (let x = 0; x < obj.length ; x++) {

      let tr=document.createElement('tr');

      let td1=document.createElement('td');
      let td2=document.createElement('td');
      let text1=document.createTextNode(obj[x]['COD_ARTICU']);
      let text2=document.createTextNode(obj[x]['RUBRO']);
      
      td1.appendChild(text1);
      td2.appendChild(text2);
      tr.appendChild(td1);
      tr.appendChild(td2);

      tableModal.appendChild(tr);

    }
};

const cambiarCentroCosto = (e) => {

  let sector = e[e.selectedIndex].getAttribute("attr-sector");
  let numSucursal = e[e.selectedIndex].getAttribute("attr-numSucursal");
  let codAuxiliar = e[e.selectedIndex].getAttribute("attr-codAuxiliar");
  let descAuxiliar = e[e.selectedIndex].text;
  let id = e.parentElement.parentElement.childNodes[41].textContent;
  e.parentElement.parentElement.childNodes[5].textContent = sector;
  e.parentElement.parentElement.childNodes[39].textContent = numSucursal;

  $.ajax({
    url: "Controller/updateGasto.php",
    method: "POST",
    data: {
      sector: sector,
      numSucursal: numSucursal,
      codAuxiliar: codAuxiliar,
      descAuxiliar: descAuxiliar,
      id: id,
    },
  }).done(function (e) {});

};

const actualizarSaldo = (saldo) => {
  let saldoParseado = parseNumber(parseFloat(saldo.value));
  let nuevoSaldo = convertToNumber(saldoParseado);

  let id = saldo.parentElement.parentElement.childNodes[41].textContent;

  saldo.value = saldoParseado;

  $.ajax({
    url: "Controller/actualizarSaldo.php",
    method: "POST",
    data: {
      id: id,
      nuevoSaldo: nuevoSaldo,
    },
  });
};

const parseNumber = (value) => {
  return value.toLocaleString("de-DE", {
    style: "decimal",
  });
};

const convertToNumber = (numero) => {
  let newNumero1 = numero.replaceAll(".", "");
  return newNumero1.replace(",", ".");
};
const rellenarModal2 = (obj) => {
  let tableModal = document.querySelector("#tableModalvCT");

  tableModal.innerHTML = "";

  for (let x = 0; x < obj.length; x++) {

    const tr=document.createElement('tr');
    const td1=document.createElement('td');
    const td2=document.createElement('td');
    const td3=document.createElement('td');
    const td4=document.createElement('td');

    const td5=document.createElement('td');

    var input = document.createElement("input");
    input.type = "checkbox";
    input.className = "form-control";
    input.id="checkModal2";

    let number3 = obj[x]['IMP_COBRANZA'].toLocaleString('de-De', {
      style: 'decimal',
      maximumFractionDigits: 2,
      minimumFractionDigits: 0
    });
    
    let number2 = obj[x]['IMP_VENTA'].toLocaleString('de-De', {
      style: 'decimal',
      maximumFractionDigits: 2,
      minimumFractionDigits: 0
    });

    dif = obj[x]['DIFERENCIA'].toLocaleString('de-De', {
      style: 'decimal',
      maximumFractionDigits: 2,
      minimumFractionDigits: 0
    });

    const text1=document.createTextNode(parseInt(obj[x]['NRO_SUCURS']));
    const text2=document.createTextNode("$"+ number2);
    const text3=document.createTextNode("$"+ number3);
    const text4=document.createTextNode("$"+ dif);

    if(parseFloat(obj[x]['DIFERENCIA']) == 0){
      input.checked = true;
      input.setAttribute("onclick", "return false");
    }

    td1.appendChild(text1);
    td2.appendChild(text2);
    td3.appendChild(text3);
    td4.appendChild(text4);
    td5.appendChild(input);

    tr.appendChild(td1);
    tr.appendChild(td2);
    tr.appendChild(td3);
    tr.appendChild(td4);
    tr.appendChild(td5);
    
    tableModal.appendChild(tr);
  }
};

function procesar() {
  let desde = document.getElementsByName("desde")[0].value;
  let hasta = document.getElementsByName("hasta")[0].value;
  Swal.fire({
    title: "Desea ejecutar el proceso ?",
    text: "Ya no se podran deshacer los cambios!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Ok, ejecutar!",
    cancelButtonText: "No, cancelar!",
    reverseButtons: true,
  }).then((result) => {
    if (result.isConfirmed) {
      /******************************* */
      let spinner = document.getElementById("boxLoading");
      spinner.className += " loading";
      /*otro fetch*/

      fetch("./Controller/procesar.php?desde=" + desde + "&hasta=" + hasta, {
        method: "GET",
      })
        .then((respuesta) => respuesta.json())
        .then((mensaje) => {
  

          if (mensaje.length == 0) {
            spinner.classList.remove("loading");
            Swal.fire({
              icon: "success",
              title: "",
              text: "El proceso finalizó correctamente",
            });
          } else {
            
            spinner.classList.remove("loading");
            if(mensaje[0].RESULTADO == 1){
              Swal.fire({
                icon: "error",
                title: "Error",
                text: 'Hay registros pendientes de controlar y/o prorratear'
              })
              return 1;

            }else{

              Swal.fire({
                icon: "error",
                title: "Atención",
                text: `El periodo ya fue procesado`,
              });
              return 1
            }

          }
        });
      /******************************** */
    }
  });
}

const rellenarModal5 = (obj)=>{


  let tableModal =  document.querySelector("#tableVb");
  tableModal.innerHTML = "";
  let objeto = JSON.parse(obj);


  objeto.forEach(element => {

    const tr=document.createElement('tr');
    const td1=document.createElement('td');
    const td2=document.createElement('td');
    const td3=document.createElement('td');
    const td4=document.createElement('td');
    const td5=document.createElement('td');

    var input = document.createElement("input");
    input.type = "text";
    input.className = "form-control";
    input.id="inputVenta";
    input.value = "$"+parseNumber(parseInt(element['VENTA']));
    input.setAttribute("value", parseNumber(parseInt(element['VENTA'])));
    input.setAttribute("onchange", "actualizarValor(this)");


    let inputObservacion = document.createElement("input");
    inputObservacion.type = "text";
    inputObservacion.className = "form-control";
    inputObservacion.id="inputObservacion";
    inputObservacion.value = element['OBSERVACION_MOD'];
    inputObservacion.setAttribute("onchange", "actualizarValor(this)");



    const text1=document.createTextNode(element['NRO_SUCURS']);
    const text2=document.createTextNode(element['SUCURSAL']);
    // const text3=document.createTextNode(element['VENTA']);
    // const text4=document.createTextNode(element['OBSERVACION_MOD']);
    const text5=document.createTextNode(element['ID']);

    td1.appendChild(text1);
    td2.appendChild(text2);
    td3.appendChild(input);
    td4.appendChild(inputObservacion);
    td5.appendChild(text5);

    td5.hidden = true;
    
    tr.appendChild(td1);
    tr.appendChild(td2);
    tr.appendChild(td3);
    tr.appendChild(td4);
    tr.appendChild(td5);

    tableModal.appendChild(tr);

  });


}
const actualizarValor = (e)=>{

  let id = e.parentElement.parentElement.childNodes[4].textContent;
  let observacion = e.parentElement.parentElement.childNodes[3].childNodes[0].value;
  let inputVenta = e.parentElement.parentElement.childNodes[2].childNodes[0];
  let nuevoValor = inputVenta.value.replace(/[$.]/g, "");
  
  inputVenta.setAttribute("value", parseNumber(parseInt(nuevoValor)));
  inputVenta.value = "$"+parseNumber(parseInt(nuevoValor));
  
  $.ajax({
    url: 'Controller/rentabilidadBruta.php?accion=actualizarValor',
    method: 'POST',
    data:{
      id:id,
      nuevoValor:nuevoValor,
      observacion:observacion
    },
    success : function(data) {
      console.log(data)
    }

})
  
}

const marcarControlado = ()=>{

  let desde = document.querySelector("#desde").value;
  let hasta = document.querySelector("#hasta").value;
  let periodo  =  document.querySelector("#periodo").getAttribute("attr-periodo");
  $.ajax({
          url: 'Controller/rentabilidadBruta.php?accion=controlarRentabilidad',
          method: 'POST',
          data:{
              desde:desde,
              hasta:hasta,
              periodo:periodo
          },
          success : function(data) {
            Swal.fire({
              icon: "success",
              title: "Control exitoso",
              text: `Rentabilidad controlada!`,
            });
            document.querySelector("#paso1").className = 'active';
          }
      })
  

}

const insertarCoeficienteAjuste = () => {

  let coeficiente = document.querySelector("#ca-valor").value;
  coeficiente = coeficiente.replace(',', '.');

  let periodo = document.querySelector("#ca-periodo").value;

  $.ajax({
    url: 'Controller/coeficientesAjusteController.php',
    method: 'POST',
    data:{
      periodo:periodo,
      coeficiente:coeficiente
    },
    success : function(data) {
      Swal.fire({
        icon: "success",
        title: "Se guardo correctamente",
        text: `Coeficiente insertado!`,
      });

    }
  })

}

const exportModal = (table) =>{
    $(`#${table}`).table2excel({
        // exclude CSS class
        exclude: ".noE  xl",
        name: "excel Document ",
        filename: "Excel", //do not include extension
        fileext: ".xlsx" // file extension
    });

}
const aceptarDiferenciasModal2 = () =>{
  
  let allCheck = document.querySelectorAll("#checkModal2");
  let diferencias = false;
  for (let i = 0; i < allCheck.length; i++) {

    const element = allCheck[i];

    if(element.checked == false){
      
      Swal.fire({
        icon: "error",
        title: "Diferencias",
        text: `Debe aceptar Todas Las Diferencias!`,
      });
      diferencias = true;
      break;

    }
    
  }

  if(diferencias == false){

    marcarPasoControladoConDiferencias("2");

    $('#modalVct').modal('hide');
    
  }

}


function marcarPasoControladoConDiferencias(paso) {
  let desde = document.querySelector("#desde").value;
  let hasta = document.querySelector("#hasta").value;
  let periodo = document.querySelector("#periodo").getAttribute("attr-periodo");
  
  // Verificar primero si el paso ya está marcado
  $.ajax({
    url: 'Controller/consultarPasos.php',
    method: 'GET',
    data: {
      periodo: periodo
    },
    success: function(data) {
      let pasos = JSON.parse(data);
      
      // Si el paso ya está marcado, solo mostrar mensaje de éxito sin ejecutar nuevamente
      if (pasos.length > 0 && pasos[0]['PASO_' + paso] == 1) {
        Swal.fire({
          icon: "success",
          title: "Paso ya ejecutado",
          text: `El paso ${paso} ya fue marcado como completado.`,
        }).then(function() {
          pintarPasos(periodo);
        });
      } else {
        // Si no está marcado, proceder con la marca y ejecución
        $.ajax({
          url: 'Controller/marcarPasoControlado.php',
          method: 'POST',
          data: {
            periodo: periodo,
            paso: paso,
            desde: desde,
            hasta: hasta
          },
          success: function(data) {
            Swal.fire({
              icon: "success",
              title: "Se guardo correctamente",
              text: `Paso Ejecutado!`,
            }).then(function() {
              pintarPasos(periodo);
            });
          }
        });
      }
    }
  });
}


const resumen = () => {
  
  let periodo = document.querySelector("#periodo").getAttribute("attr-periodo");

  $.ajax({
    url: 'Controller/controlGastosController.php?accion=existeResumen',
    method: 'POST',
    data:{
      periodo:periodo
    },
    success : function(data) {

      data = data.trim();

      if(data == 'true'){

        $.ajax({
          url: 'Controller/controlGastosController.php?accion=resumen',
          method: 'POST',
          data:{
            periodo:periodo
          },
          success : function(data) {
        
            
            data = JSON.parse(data);

            let tableModal =  document.querySelector("#resumenBody");
            tableModal.innerHTML = "";


            data.forEach(element => {

              const tr=document.createElement('tr');
              const td1=document.createElement('td');
              const td2=document.createElement('td');
              const td3=document.createElement('td');
              const td4=document.createElement('td');
              const td5=document.createElement('td');
              const td6=document.createElement('td');

              const text1=document.createTextNode(element['PERIODO']);
              const text2=document.createTextNode(element['NRO_SUCURSAL']);
              const text3=document.createTextNode(element['DESC_SUCURSAL']);
              const text4=document.createTextNode(element['COD_RUBRO']);
              const text5=document.createTextNode(element['RUBRO_CONTABLE']);
              const text6=document.createTextNode(element['IMPORTE']);

              td1.appendChild(text1);
              td2.appendChild(text2);
              td3.appendChild(text3);
              td4.appendChild(text4);
              td5.appendChild(text5);
              td6.appendChild(text6);

              
              tr.appendChild(td1);
              tr.appendChild(td2);
              tr.appendChild(td3);
              tr.appendChild(td4);
              tr.appendChild(td5);
              tr.appendChild(td6);

              tableModal.appendChild(tr);
            })
            exportModal("tablaResumen");
          }
          
        })

      }else{

        Swal.fire({
          icon: "error",
          title: "Error",
          text: `No se encuentra cargado el resumen!`,
        });
        
      }

    }
  
  });
}



const cambiarEntorno = (t) => {
    let entorno = 'central';

    if(t.checked) {
        // Si está checked, usar el valor de data-on
        entorno = t.getAttribute("data-on") === "ARG" ? 'central' : 'uy';
    } else {
        // Si no está checked, usar el valor de data-off
        entorno = t.getAttribute("data-off") === "ARG" ? 'central' : 'uy';
    }

    $.ajax({
        url: "Controller/controlGastosController.php?accion=cambiarEntorno",
        method: "POST",
        data: {entorno: entorno},
        success: function (data) {
            location.reload();
        },
        error: function(xhr, status, error) {
            console.error('Error al cambiar entorno:', error);
        }
    });
}

const revertir = () => {
  let desde = document.querySelector("#desde").value;
  let hasta = document.querySelector("#hasta").value;
  Swal.fire({
    title: '¿Estás seguro?',
    text: 'Esta acción revertirá todos los cambios realizados en el proceso de control.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Sí, revertir',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      $.ajax({
        url: 'Controller/controlGastosController.php?accion=revertir',
        method: 'POST',
        data: {
          desde: desde,
          hasta: hasta
        },
        dataType: 'json',
        success: function (response) {
          if (response.success) {
            Swal.fire({
              title: '¡Éxito!',
              text: response.message,
              icon: 'success',
              confirmButtonText: 'Aceptar'
            }).then(() => {
              location.reload();
            });
          } else {
            Swal.fire({
              title: 'Error',
              text: response.message,
              icon: 'error',
              confirmButtonText: 'Aceptar'
            });
          }
        },
        error: function (xhr, status, error) {
          Swal.fire({
            title: 'Error de conexión',
            text: 'No se pudo conectar con el servidor. Por favor, inténtalo de nuevo.',
            icon: 'error',
            confirmButtonText: 'Aceptar'
          });
        }
      });
    }
  });
}
