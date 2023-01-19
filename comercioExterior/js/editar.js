let btnUpdateDetalle = document.querySelector("#btnUpdateDetalle");
let btnAgregarDetalle = document.querySelector("#btnAgregarDetalle");
btnUpdateDetalle.addEventListener("click",()=> {
    // let rows = document.querySelectorAll("#id");
    let arrayDatos = [];
    let idEncabezado = document.querySelector("#idEncabezado").getAttribute("attr-value");

   
    let table = document.querySelector("#table")
    let rows = table.querySelectorAll("tr:not(:last-child)")
    let acumTotal = 0;
  
    rows.forEach((x,e)=>{
      
      let gastos = x.querySelectorAll("td")[1].firstChild.value;
      let idDetalle = x.querySelectorAll("td")[0].getAttribute('attr-value')
      let importeEnDolares = sacarParseo( x.querySelectorAll("td")[2].firstChild.value,true);
      let tipoCambio = sacarParseo(x.querySelectorAll("td")[3].firstChild.value,true);
      let importeEnPesos = sacarParseo(x.querySelectorAll("td")[4].firstChild.value,true);
      let sobreFob = x.querySelectorAll("td")[5].firstChild.value.replace("%","");
      let observaciones = x.querySelectorAll("td")[6].firstChild.value;

      arrayDatos [e] = [gastos,importeEnDolares,tipoCambio,importeEnPesos,sobreFob,observaciones,idDetalle,idEncabezado]


    })


    fetch('Controller/editarDetalle.php', {
    method: "POST",
    body:JSON.stringify(arrayDatos),
    headers: { 
        "Content-type" : "application/json"
    }
    })
    .then(response => {
    })

    Swal.fire({
      title: 'Detalle guardado!',
      icon: 'success',
      showDenyButton: true,
      showCancelButton: false,
      showConfirmButton: false,
      denyButtonText: `Volver`,
      })
      .then((e) => {

        window.location = "../comercioExterior/mostrarOrden.php"
      })

})
btnAgregarDetalle.addEventListener("click",()=>{


  let rows = document.querySelectorAll("#id");
  let original = rows[rows.length - 1].parentElement;
  let lastId =  Number(original.childNodes[1].textContent) + 1 
  let text = `
    <tr>
    <td id ="id">${lastId}</td>
    <td><input type="text" style ="text-align:center"></td>
    <td><input class="decimales currencyInput" style="text-align:center" type="text" id="valorFobDolar" onkeyup="iniciarCalculo(this,true)" value = ""></input></td>
    <td><input class="decimales currencyInput tipoCambio" style="text-align:center" type="text"  onkeyup="iniciarCalculo(this,true)" id="tipoCambio" value="0"></input></td>
    <td><input class="decimales currencyInput importe" style="text-align:center" type="number" id="valorFobPeso" name="inputNum[]" readonly value="00,00"></input></td>
    <td><input style="text-align:center" value="0,00%" readonly></input></td>
    <td><input></input></td>
    <td><button type="button" class="btn btn-danger" onclick="borrarGasto(this)">X</button></td>
    </tr>
  `;



  original.insertAdjacentHTML("afterend",text)

 

})
const borrarGasto = (e)=>{
  let row = e.parentElement.parentElement
  row.remove()
}