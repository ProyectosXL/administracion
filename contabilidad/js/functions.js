window.addEventListener("DOMContentLoaded", iniciarEscuchaSelect); //1 - cuando se termina de carga toda la pagina, comienza a escuchar los eventos del dom

let a;
const selectRubro = document.querySelectorAll(".codRubro"); //2 - guardo en un array todos los check donde se va escuchar si se produjo un cambio
const selectProrrateo = document.querySelectorAll(".codProrrateo");
const checkExcluir = document.querySelectorAll(".checkExcluir");
const checkControlado = document.querySelectorAll(".checkControlado");
const checkAmortizado = document.querySelectorAll(".checkAmortizado");
const inputAmortiza = document.querySelectorAll(".amortiza");
const btnAmortizar = document.querySelector(".btn-danger");
const btnProrratear = document.querySelector("#btnProrrateo");
const btnEjecutar = document.querySelector("#btnEjecutar");
let periodo = document.querySelector("#periodo").getAttribute("attr-periodo");
let pasoActual = 0;

let payloads =  {

  "desde": document.getElementsByName("desde")[0].value,
  "hasta": document.getElementsByName("hasta")[0].value,
  
}

btnAmortizar.addEventListener("click", amortizarGastos);
btnProrratear.addEventListener("click", prorratearGastos);
btnEjecutar.addEventListener("click", ejecutarPasos);

let conexion;

function iniciarEscuchaSelect() {
  pintarPasos(periodo);
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
  let Dato = e.target; // 6 - guardo el elemento del html donde se produjo el evento.
  /*  let cuenta = Dato.parentElement.parentElement.children[4].textContent; */
  let ID = Dato.parentElement.parentElement.children[20].textContent;
  let codRubro = Dato.value;
  let rubroDesc = Dato.parentElement.parentElement.children[11];
  conexion = new XMLHttpRequest();
  conexion.onreadystatechange = () => {
    if (conexion.readyState == 4 && conexion.status == 200) {
      rubroDesc.textContent = conexion.responseText;
      guardarCambiosRubro(ID, codRubro, rubroDesc.textContent);
    }
  };
  conexion.open("GET", "Class/rubroContable.php?codigo=" + Dato.value, true);
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
  conexion = new XMLHttpRequest();
  conexion.open(
    "POST",
    "./Controller/amortizar.php?estado=1&desde=" + desde + "&hasta=" + hasta,
    true
  ); // no se envia fecha inicio y fin
  conexion.onreadystatechange = ejecutarQuery;
  conexion.send();
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

  let desde = document.getElementsByName("desde")[0].value;
  let hasta = document.getElementsByName("hasta")[0].value;
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
        /******************************* */
        fetch("./Controller/prorratear.php?desde=" + desde + "&hasta=" + hasta)
          .then((respuesta) => respuesta.json())
          .then((perfil) => {
            if (perfil.resultado == 0) {
              Swal.fire({
                icon: "error",
                title: "Error",
                text: "No hay gastos para prorratear!",
              });
            }else{
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

  /******************************************************* */
/*   fetch("./Controller/prorratear.php?desde=" + desde + "&hasta=" + hasta)
    .then((respuesta) => respuesta.json())
    .then((perfil) => {
      if (perfil.resultado == 0) {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "No hay gastos para prorratear!",
        });
      } else {
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
              swalWithBootstrapButtons.fire(
                "Prorrateado!",
                "Los gastos fueron prorrateados",
                "success"
              );
            } else if (result.dismiss === Swal.DismissReason.cancel) {
              swalWithBootstrapButtons.fire(
                "Cancelado",
                "Los gastos no fueron prorrateados :(",
                "error"
              );
            }
          });
      }
    }); */
}


function ejecutarPasos() {

  if(pasoActual == 7){
    pasoActual = 0;
  }

  pasoActual = pasoActual + 1;

  let pasosDirectos = [3,5,6,7]; 

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
        let paso = document.getElementById("paso"+pasoActual);
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
            // console.log(perfil);

            if (perfil.result == 0 || pasosDirectos.includes(pasoActual) == true ) {

              paso.className += "active";
              spinner.classList.remove('loading');
              Swal.fire({
                icon: "success",
                title: "Control exitoso",
                text: `Paso ${pasoActual} realizado!`,
              });

            }else{

              
              spinner.classList.remove('loading');

              if(pasoActual == 1){
          
                $('#modalCn').modal('toggle');

              }

              if(pasoActual == 2){

           
                rellenarModal2(perfil);
                
                $('#modalPc').modal('toggle');      
              }
              if(pasoActual == 4){

                  rellenarModal4(perfil);

                  $('#modalVct').modal('toggle');
  
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
          "Los gastos no fueron prorrateados :(",
          "error"
        );
      }
    });
}

const pintarPasos =(periodo)=>{

    fetch("./Controller/consultarPasos.php?periodo="+periodo,
    )
    .then((respuesta) => respuesta.json())
    .then((data) => {

      let pasos = [];

      for (let i = 0; i < 7; i++) {
        pasos[i] = data[0]['PASO_'+(i+1)];
      }
    
      pasos.forEach((element,x) => {
    
        if(element != null){
        let paso = document.getElementById("paso"+(x+1));

        pasoActual = x+1;

        paso.className += "active";

      }

      });
    })

}


const rellenarModal2 = (obj)=>{


  let tableModal =  document.querySelector("#tableModalPc");

  for (let x = 0; x < obj.length ; x++) {

    let tr=document.createElement('tr');
    console.log(tr)
    let td1=document.createElement('td');
    let td2=document.createElement('td');
    let text1=document.createTextNode(obj[x]['COD_ARTICU']);
    let text2=document.createTextNode(obj[x]['RUBRO']);
    
    td1.appendChild(text1);
    td2.appendChild(text2);
    tr.appendChild(td1);
    tr.appendChild(td2);
    console.log(td1);
    console.log(td2);
    
    tableModal.appendChild(tr);


  }

}
const rellenarModal4 = (obj)=>{


  let tableModal =  document.querySelector("#tableModalvCT");

  for (let x = 0; x < obj.length; x++) {

    const tr=document.createElement('tr');
    const td1=document.createElement('td');
    const td2=document.createElement('td');
    const td3=document.createElement('td');
    const td4=document.createElement('td');
    const text1=document.createTextNode(obj[x]['NRO_SUCURS']);
    const text2=document.createTextNode(obj[x]['IMP_VENTA']);
    const text3=document.createTextNode(obj[x]['IMP_COBRANZA']);
    const text4=document.createTextNode(obj[x]['DIFERENCIA']);

    td1.appendChild(text1);
    td2.appendChild(text2);
    td3.appendChild(text3);
    td4.appendChild(text4);
    tr.appendChild(td1);
    tr.appendChild(td2);
    tr.appendChild(td3);
    tr.appendChild(td4);
    
    tableModal.appendChild(tr);


  }

}