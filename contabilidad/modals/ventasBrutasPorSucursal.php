<div class="modal fade bd-example-modal-lg" id="modalVb" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title" id="exampleModalLabel"><i class="fa fa-edit" aria-hidden="true" style="font-size: 25px;"></i> Ventas Brutas Por Sucursal</h4>
          </button>
        </div>
        <div class="modal-body">

          <!-- Aca se debe mostrar la tabla que arroja el SP RO_SP_ARTICULOS_SIN_COSTO_NAC -->

          <div class="table-responsive" id="tableIndex">
            <table class="table table-hover table-condensed table-striped text-center" id="tablaVentasBrutas">
              <thead class="thead-dark" style="font-size: small;">
                <th scope="col" style="width: 6%">Nro. Sucursal</th>
                <th scope="col" style="width: 15%">Sucursal</th>
                <th scope="col" style="width: 8%">Venta $</th>
                <th scope="col" style="width: 8%">Observaciones</th>
              </thead>

              <tbody id="tableVb" style="font-size: small;">

              </tbody>
            </table>
          </div>
          <div class="modal-footer">
            <button class="btn btn-success btn_exportar" id="btnExport" > Exportar<i class="bi bi-file-earmark-excel"></i></button>
            <button type="button" class="btn btn-primary" data-dismiss="modal" onclick="marcarControlado()">Confirmar <i class="bi bi-check2-square"></i></button>

          </div>
        </div>
      </div>
    </div>
  </div>


 <script>

$("#btnExport").click(function() {

  let inputs = document.querySelectorAll('#inputVenta');

  inputs.forEach(element => {

    let valor = element.value
    let td = element.parentElement; 
    td.innerHTML = "";
    td.id="tdVenta";
    const text=document.createTextNode(valor);

    td.appendChild(text);


  });

  let inputsObservacion = document.querySelectorAll('#inputObservacion');

  inputsObservacion.forEach(element => {

    let valor = element.value
    let td = element.parentElement; 
    td.innerHTML = "";
    td.id="tdObservacion";
    const text=document.createTextNode(valor);

    td.appendChild(text);


  });

  $("#tablaVentasBrutas").table2excel({
      // exclude CSS class
      exclude: ".noExl",
      name: "Worksheet Name",
      filename: "VentasBrutas", //do not include extension
      fileext: ".xls", // file extension
  });


  let tdVenta = document.querySelectorAll('#tdVenta');

  tdVenta.forEach(element => {

    let valor = element.textContent;
    let td = element; 
    td.innerHTML = "";

    var input = document.createElement("input");
    input.type = "text";
    input.className = "form-control";
    input.id="inputVenta";
    input.value = valor,
    input.setAttribute("onchange", "actualizarValor(this)");


    td.appendChild(input);

    
    
  });
  
  let tdObservacion = document.querySelectorAll('#tdObservacion');

  tdObservacion.forEach(element => {

    let valor = element.textContent;
    let td = element; 
    td.innerHTML = "";

    var input = document.createElement("input");
    input.type = "text";
    input.className = "form-control";
    input.id="inputObservacion";
    input.value = valor,
    input.setAttribute("onchange", "actualizarValor(this)");


    td.appendChild(input)
  });
  });

 </script>