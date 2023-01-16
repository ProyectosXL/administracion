window.addEventListener("DOMContentLoaded", iniciarEscuchaSelect); //1 - cuando se termina de carga toda la pagina, comienza a escuchar los eventos del dom

const selectRubro = document.querySelectorAll(".codRubro"); //2 - guardo en un array todos los check donde se va escuchar si se produjo un cambio
const selectProrrateo = document.querySelectorAll(".codProrrateo");
const selectCentro = document.querySelectorAll(".codCentro");
const selectCuenta = document.querySelectorAll(".codCuenta");
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
  selectCentro.forEach((select) =>
    select.addEventListener("change", completarCampoCentro)
  );
  selectCuenta.forEach((select) =>
    select.addEventListener("change", completarCampoCuenta)
  );
}

function completarCampoRubro(e) {
  // 5 - al evento change de un codRubro se llama a la funcion completarCampoRubro, e , es el evento con la información de cual elemnto del dom fue clickeado
  let Dato = e.target; // 6 - guardo el elemento del html donde se produjo el evento.
  /*  let cuenta = Dato.parentElement.parentElement.children[4].textContent; */
  let n_comp = Dato.parentElement.parentElement.children[8].textContent;
  let codRubro = Dato.value;
  let rubroDesc = Dato.parentElement.parentElement.children[10];
  conexion = new XMLHttpRequest();
  conexion.onreadystatechange = () => {
    if (conexion.readyState == 4 && conexion.status == 200) {
      rubroDesc.textContent = conexion.responseText;
    }
  };
  conexion.open("GET", "Class/rubroContable.php?codigo=" + Dato.value, true);
  conexion.send();
}

function completarCampoProrrateo(e) {
  let Dato = e.target;
  /*  let cuenta = Dato.parentElement.parentElement.children[4].textContent; */
  let n_comp = Dato.parentElement.parentElement.children[8].textContent;
  let codProrrateo = Dato.value;
  let prorrateoDesc = Dato.parentElement.parentElement.children[12];
  /* let txtDescProrrateo = e.target; */
  conexion = new XMLHttpRequest();
  conexion.onreadystatechange = () => {
    if (conexion.readyState == 4 && conexion.status == 200) {
      prorrateoDesc.textContent = conexion.responseText;
    }
  };
  conexion.open("GET", "Class/prorrateo.php?codigo=" + codProrrateo, true);
  conexion.send();
}

function completarCampoCentro(e) {
  let Dato = e.target;
  // let Dato=document.querySelector('.codCentro').value;
  /*  let cuenta = Dato.parentElement.parentElement.children[4].textContent; */
  let n_comp = Dato.parentElement.parentElement.children[1].textContent;
  let codCentro = Dato.value;
  let centroDesc = Dato.parentElement.parentElement.children[3];
  let sector = Dato.parentElement.parentElement.children[4];
  let numSuc = Dato.parentElement.parentElement.children[13];
  /* let txtDescProrrateo = e.target; */
  conexion = new XMLHttpRequest();
  conexion.onreadystatechange = () => {
    if (conexion.readyState == 4 && conexion.status == 200) {
      let info = JSON.parse(conexion.responseText);
      centroDesc.textContent = info.DESC_AUXILIAR;
      sector.textContent = info.SECTOR;
      numSuc.textContent = info.NUM_SUCURSAL;
    }
  };
  conexion.open("GET", "Class/centroCosto.php?codigo=" + codCentro, true);
  conexion.send();
}

function completarCampoCuenta(e) {
  let Dato = e.target;
  /*  let cuenta = Dato.parentElement.parentElement.children[4].textContent; */
  let n_comp = Dato.parentElement.parentElement.children[5].textContent;
  let codCuenta = Dato.value;
  let cuentaDesc = Dato.parentElement.parentElement.children[6];
  /* let txtDescProrrateo = e.target; */
  conexion = new XMLHttpRequest();
  conexion.onreadystatechange = () => {
    if (conexion.readyState == 4 && conexion.status == 200) {
      cuentaDesc.textContent = conexion.responseText;
    }
  };
  conexion.open("GET", "Class/cuentaContable.php?codigo=" + codCuenta, true);
  conexion.send();
}

//Tabla dinámica//

//Agregar filas//
function addRow(tableDinamic) {
  var table = document.getElementById(tableDinamic);
  var rowCount = table.rows.length;
  var row = table.insertRow(rowCount);
  var cell1 = row.insertCell(0);
  var element1 = document.createElement("input");
  element1.type = "checkbox";
  cell1.appendChild(element1);

  for (var i = 0; i < 17; i++) {
    var element2 = document.createElement("input");
    var cell2 = row.insertCell(1);
    // var element2 = document.createElement("input");
    // element2.type = "text";
    var element2;
    //aquí puedes controlar el tamaño del input
    // element2.style.width="2 rem";
    cell2.appendChild(element2);
  }
}

//Eliminar filas//
function deleteRow(tableDinamic) {
  try {
    var table = document.getElementById(tableDinamic);
    var rowCount = table.rows.length;

    for (var i = 0; i < rowCount; i++) {
      var row = table.rows[i];
      var chkbox = row.cells[0].childNodes[0];

      if (null != chkbox && true == chkbox.checked) {
        table.deleteRow(i);
        rowCount--;
        i--;
      }
    }
  } catch (e) {
    alert(e);
  }
}

function guardarGasto() {
  let arreglo = [];
  let fecha = document.querySelector(".fecha").value;
  arreglo.push(fecha);
  let codCentro = document.querySelector(".codCentro").value;
  arreglo.push(codCentro);
  let auxiliar = document.querySelector(".auxiliar").textContent;
  arreglo.push(auxiliar);
  let sector = document.querySelector(".sector").textContent;
  arreglo.push(sector);
  let codCuenta = document.querySelector(".codCuenta").value;
  arreglo.push(codCuenta);
  let cuenta = document
    .querySelector(".cuenta")
    .textContent.replace(/(\r\n|\n|\r)/gm, "");
  arreglo.push(cuenta);
  let importe = document.querySelector(".importe").value;
  arreglo.push(importe);
  let leyenda = document.querySelector(".leyenda").value;
  arreglo.push(leyenda);
  let codRubro = document.querySelector(".codRubro").value;
  arreglo.push(codRubro);
  let rubro = document
    .querySelector(".rubro")
    .textContent.replace(/(\r\n|\n|\r)/gm, "");
  arreglo.push(rubro);
  let codProrrateo = document.querySelector(".codProrrateo").value;
  arreglo.push(codProrrateo);
  let descProrrateo = document
    .querySelector(".descProrrateo")
    .textContent.replace(/(\r\n|\n|\r)/gm, "");
  arreglo.push(descProrrateo);
  let suc = document.querySelector(".suc").textContent;
  arreglo.push(suc);
  let amortizar = document.querySelector(".amortizar").value;
  arreglo.push(amortizar);
  console.log(arreglo);

  if (
    fecha != "" &&
    codCentro != "" &&
    codCuenta != "" &&
    importe != "" &&
    leyenda != "" &&
    codRubro != "" &&
    codProrrateo != ""
  ) {
    Swal.fire({
      icon: "info",
      title: "Desea registrar el gasto?",
      showDenyButton: true,
      showCancelButton: true,
      confirmButtonText: "Guardar",
      denyButtonText: `No guardar`,
    }).then((result) => {
      /* Read more about isConfirmed, isDenied below */
      if (result.isConfirmed) {
        console.log("EH");
        let env = 1;
        let url = env == 1 ? "insertCuentas2.php" : "prueba.php";
        $.ajax({
          url: "Controller/" + url,
          method: "POST",
          data: {
            fecha: fecha,
            codCentro: codCentro,
            auxiliar: auxiliar,
            sector: sector,
            codCuenta: codCuenta,
            cuenta: cuenta,
            importe: importe,
            leyenda: leyenda,
            codRubro: codRubro,
            rubro: rubro,
            codProrrateo: codProrrateo,
            descProrrateo: descProrrateo,
            suc: suc,
            amortizar: amortizar,
          },
        });
        Swal.fire("Gasto cargado correctamente!", "", "success").then(
          function () {
            window.history.back();
          }
        );
      } else if (result.isDenied) {
        Swal.fire("El gasto no fue cargado", "", "info").then(function () {
          window.location = "cargaGastos.php";
        });
      }
    });
  } else {
    Swal.fire({
      icon: "info",
      title: "Atención",
      text: "Debe llenar todos los campos!",
    });
  }
}
