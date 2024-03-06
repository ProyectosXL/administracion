const agregar = (siglaRubro) =>{

    let codCategoria = document.getElementById("codCategoria").textContent;
    let descCategoria = document.getElementById("descCategoria").value;

    codCategoria = String(codCategoria).padStart(2, '0')

    $.ajax({
        url: 'Controller/CategoriaController.php?accion=insertarNuevo',
        method: 'POST',
        data:{
            codCategoria:codCategoria,
            descCategoria:descCategoria,
            siglaRubro:siglaRubro
        },
        success : function(data) {
          Swal.fire({
            icon: "success",
            title: "Carga Exitosa",
            text: `Categoria Insertada!`,
          }).then((result) => {
              
              location.reload();
          })


        }
    })
}

const editar = (div) =>{
  
   let element = div.parentElement.parentElement.querySelectorAll("td")[3];
   element.innerHTML = `<input type="text" id="descCategoria" value="${element.textContent}" style="width:250px" onchange="actualizarValor(this)">`;
} 

const actualizarValor = (input) =>{

    let allTd = input.parentElement.parentElement.querySelectorAll("td")

    let nuevoValor = input.value;

    let rubro = allTd[0].textContent;
    let Categoria = allTd[2].textContent;

    $.ajax({
        url: 'Controller/CategoriaController.php?accion=actualizarDescCategoria',
        method: 'POST',
        data:{
            nuevoValor:nuevoValor,
            rubro:rubro,
            Categoria:Categoria
        },
        success : function(data) {
            console.log("valor actualizado")
        }
    })
    input.parentElement.innerHTML = `<td>${input.value}</td>`;

}