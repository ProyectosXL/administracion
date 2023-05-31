document.addEventListener("DOMContentLoaded", iniciarEscuchaSelect); 
let conexion;

function iniciarEscuchaSelect() {
  $(".select-auxiliar").on("select2:select", function (e) {
    console.log("ID seleccionado: " + e.params.data.id);
    completarAuxiliar(e.params.data.id);
  });
  $(".codCuenta").on("select2:select", function (e) {
    console.log("ID seleccionado: " + e.params.data.id);
    completarCuenta(e.params.data.id);
  });
  $(".codRubro").on("select2:select", function (e) {
    console.log("ID seleccionado: " + e.params.data.id);
    completarCampoRubro(e.params.data.id);
  });
  $(".codProrrateo").on("select2:select", function (e) {
    console.log("ID seleccionado: " + e.params.data.id);
    completarCampoProrrateo(e.params.data.id);
  });
}

function completarCampoRubro(dato) {
  let rubroDesc=document.querySelector('.rubro');
  conexion = new XMLHttpRequest();
  conexion.onreadystatechange = () => {
    if (conexion.readyState == 4 && conexion.status == 200) {
      rubroDesc.textContent = conexion.responseText;
    }
  };
  conexion.open("GET", "Class/rubroContable.php?codigo=" + dato, true);
  conexion.send();
}

function completarCampoProrrateo(dato) {
 let prorrateoDesc = document.querySelector('.descProrrateo');
  conexion = new XMLHttpRequest();
  conexion.onreadystatechange = () => {
    if (conexion.readyState == 4 && conexion.status == 200) {
      prorrateoDesc.textContent = conexion.responseText;
    }
  };
  conexion.open("GET", "Class/prorrateo.php?codigo=" + dato, true);
  conexion.send();
}


function completarAuxiliar(dato) {
  let DESC_AUXILIAR = document.querySelector(".auxiliar");
  let SECTOR = document.querySelector(".sector");
  let NUM_SUCURSAL = document.querySelector(".suc");

  conexion = new XMLHttpRequest();
  conexion.onreadystatechange = () => {
    if (conexion.readyState == 4 && conexion.status == 200) {
      let info = JSON.parse(conexion.responseText);
      DESC_AUXILIAR.textContent = info.DESC_AUXILIAR;
      SECTOR.textContent = info.SECTOR;
      NUM_SUCURSAL.textContent = info.NUM_SUCURSAL;
    }
  };
  conexion.open("GET", "Class/centroCosto.php?codigo=" + dato, true);
  conexion.send();
}
function completarCuenta(dato) {
  let descCuenta = document.querySelector(".cuenta");
  conexion = new XMLHttpRequest();
  conexion.onreadystatechange = () => {
    if (conexion.readyState == 4 && conexion.status == 200) {
      descCuenta.textContent = conexion.responseText;
    }
  };
  conexion.open("GET", "Class/cuentaContable.php?codigo=" + dato, true);
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
          success: function (data) {
            console.log(data);
          }
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
