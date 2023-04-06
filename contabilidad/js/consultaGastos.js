window.addEventListener("DOMContentLoaded", iniciarEscuchaSelect); //1 - cuando se termina de carga toda la pagina, comienza a escuchar los eventos del dom

let a;
const selectRubro = document.querySelectorAll(".codRubroC"); //2 - guardo en un array todos los check donde se va escuchar si se produjo un cambio
const selectProrrateo = document.querySelectorAll(".codProrrateoC");




let conexion;

function iniciarEscuchaSelect() {
  //3 - se llama a la funcion de paso 1
  selectRubro.forEach(
    (
      select // 4 - recorre cada elemento del array, para saber quien es el elto donde se produjo el click
    ) => select.addEventListener("change", completarCampoRubro)
  );
  selectProrrateo.forEach((select) =>
    select.addEventListener("change", completarCampoProrrateo)
  );

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