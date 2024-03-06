

let btnCrear = document.querySelector("#btnCrear");
let btnEditar = document.querySelector("#btnEditar");

btnCrear.addEventListener("click", ()    => {
    window.location ="cargaInicial.php";
 
});

btnEditar.addEventListener("click",()=>{

    window.location ="mostrarOrden.php";

})


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