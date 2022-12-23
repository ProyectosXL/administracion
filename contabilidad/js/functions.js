window.addEventListener("DOMContentLoaded", iniciarEscuchaSelect);//1 - cuando se termina de carga toda la pagina, comienza a escuchar los eventos del dom

const selectRubro = document.querySelectorAll(".codRubro");//2 - guardo en un array todos los check donde se va escuchar si se produjo un cambio 
const selectProrrateo = document.querySelectorAll(".codProrrateo");
const checkExcluir = document.querySelectorAll('.checkExcluir');
const checkControlado = document.querySelectorAll('.checkControlado');
const checkAmortizado = document.querySelectorAll('.checkAmortizado');
const inputAmortiza = document.querySelectorAll('.amortiza');
const btnAmortizar = document.querySelector('.btn-danger');
const btnProrratear = document.querySelector('.btn-info');

btnAmortizar.addEventListener('click',amortizarGastos);
btnProrratear.addEventListener('click',prorratearGastos);

let conexion;

function iniciarEscuchaSelect() { //3 - se llama a la funcion de paso 1
  selectRubro.forEach((select) => // 4 - recorre cada elemento del array, para saber quien es el elto donde se produjo el click
    select.addEventListener("change", completarCampoRubro)
  );
  selectProrrateo.forEach((select) =>
    select.addEventListener("change", completarCampoProrrateo)
  );

  checkExcluir.forEach((select) => {
    select.addEventListener("change", (e)=>{guardarExcluir(0,e)});
  });

  checkControlado.forEach((select) => {
    select.addEventListener("change", (e)=>{guardarControlado(0,e)});
  });

  inputAmortiza.forEach((select) => {
    select.addEventListener("change", guardarAmortizar);
  });
}

function guardarExcluir(datoMasivo=0,e) {
  let excluir;
  let dato=datoMasivo!=0?datoMasivo:e.target;
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
    "ID=" +
    encodeURIComponent(ID) +
    "&checked=" +
    encodeURIComponent(excluir);
  conexion.send(infoActualizar);
}


function guardarAmortizar(e){
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


function guardarControlado(datoMasivo=0,e) {
  let controlado;
  /* let dato = e.target; */
  let dato=datoMasivo!=0?datoMasivo:e.target;
  if (dato.checked == true) {
    controlado = 1;
  } else {
    controlado = 0;
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


function completarCampoRubro(e) { // 5 - al evento change de un codRubro se llama a la funcion completarCampoRubro, e , es el evento con la información de cual elemnto del dom fue clickeado
  let Dato = e.target; // 6 - guardo el elemento del html donde se produjo el evento. 
  /*  let cuenta = Dato.parentElement.parentElement.children[4].textContent; */
  let n_comp = Dato.parentElement.parentElement.children[9].textContent;
  let codRubro = Dato.value;
  let rubroDesc = Dato.parentElement.parentElement.children[11];
  conexion = new XMLHttpRequest();
  conexion.onreadystatechange = () => {
    if (conexion.readyState == 4 && conexion.status == 200) {
      rubroDesc.textContent = conexion.responseText;
      guardarCambiosRubro(n_comp, codRubro, rubroDesc.textContent);
    }
  };
  conexion.open(
    "GET",
    "Class/rubroContable.php?codigo=" + Dato.value,
    true
  );
  conexion.send();
}

function completarCampoProrrateo(e) {
  let Dato = e.target;
  /*  let cuenta = Dato.parentElement.parentElement.children[4].textContent; */
  let n_comp = Dato.parentElement.parentElement.children[9].textContent;
  let codProrrateo = Dato.value;
  let prorrateoDesc = Dato.parentElement.parentElement.children[13];
  /* let txtDescProrrateo = e.target; */
  conexion = new XMLHttpRequest();
  conexion.onreadystatechange = () => {
    if (conexion.readyState == 4 && conexion.status == 200) {
      /*  Dato.parentElement.parentElement.children[14].textContent =
        conexion.responseText; */
      prorrateoDesc.textContent = conexion.responseText;
      guardarCambiosProrrateo(n_comp, codProrrateo, prorrateoDesc.textContent);
    }
  };
  conexion.open("GET", "Class/prorrateo.php?codigo=" + codProrrateo, true);
  conexion.send();
}

function guardarCambiosRubro(n_comp, codRubro, rubroDesc) {
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
    "n_comp=" +
    encodeURIComponent(n_comp) +
    "&codRubro=" +
    encodeURIComponent(codRubro) +
    "&descRubro=" +
    encodeURIComponent(rubroDesc);
  conexion.send(infoActualizar);
}

function guardarCambiosProrrateo(n_comp, codProrrateo, prorrateoDesc) {
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
    "n_comp=" +
    encodeURIComponent(n_comp) +
    "&codProrrateo=" +
    encodeURIComponent(codProrrateo) +
    "&descRubro=" +
    encodeURIComponent(prorrateoDesc);
  conexion.send(infoActualizar);
}

//Amortiza los gastos que tienen seteado el campo amortiza//
function amortizarGastos()
{
  let desde=document.getElementsByName('desde')[0].value;
  let hasta=document.getElementsByName('hasta')[0].value;
  conexion = new XMLHttpRequest();
  conexion.open("POST", "./Controller/amortizar.php?estado=1&desde="+desde+"&hasta="+hasta, true);// no se envia fecha inicio y fin
  conexion.onreadystatechange = ejecutarQuery;
  conexion.send();
}

function ejecutarQuery(){
  Swal.fire({
    icon:'warning',
    title: 'Desea amortizar los gastos?',
    showDenyButton: true,
    showCancelButton: false,
    confirmButtonText: 'Procesar',
    denyButtonText: `No procesar`,
  }).then((result) => {
    /* Read more about isConfirmed, isDenied below */
    if (result.isConfirmed) {

      if(revisar()!=1){
      Swal.fire('Los gastos fueron amortizados!', '', 'success')
      .then(function () {
        window.location.reload();
    });}else{
      Swal.fire({
        icon:'error',
        title: 'Error',
        text:'No hay gastos para amortizar o falta el control. Por favor revise los registros filtrados'
      })
    }
    } else if (result.isDenied) {
      Swal.fire('Los gastos no fueron amortizados', '', 'info')
    }
  })
}


function revisar() {
  let b=0;
  let inputAmortizaSelect=document.querySelectorAll('.amortiza');
  inputAmortizaSelect.forEach(ele=>{
    let checkControlado=ele.parentElement.parentElement.children[16].children[0].checked;//guarda el valor del check del campo amortiza de la fila correspondiente 
    if((ele.value=="" && checkAmortizado==true)||checkControlado==false)
    {
     b=1;
    }
  })
  if(b==1)
  {
    return 1;
  }else
  {
    return 0;
  }
 }

 function checkControladoAll(source) {
  var checkboxes = document.querySelectorAll('.checkControlado');
  for (var i = 0; i < checkboxes.length; i++) {
      if (checkboxes[i] != source)
          checkboxes[i].checked = source.checked;
          guardarControlado(checkboxes[i]);
      }
  }  
  
  function checkExcluirAll(source) {
  var checkboxes = document.querySelectorAll('.checkExcluir');
  for (var i = 0; i < checkboxes.length; i++) {
      if (checkboxes[i] != source)
          checkboxes[i].checked = source.checked;
          guardarExcluir(checkboxes[i]);
      }
  } 

  function prorratearGastos (){
    $nombre = document.querySelector("#nombre"),
    btnProrratear.addEventListener("click", () => {
      let desde=document.getElementsByName('desde')[0].value;
      let hasta=document.getElementsByName('hasta')[0].value;
    fetch("./Controller/prorratear.php?desde="+desde+"&hasta="+hasta)
        .then(respuesta => respuesta.json())
        .then(perfil => {
            // Aquí hacer algo con la respuesta
            $nombre.textContent = perfil.nombre;
        });
    });
  }

 
  



  