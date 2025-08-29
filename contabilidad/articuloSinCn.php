<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Articulos sin costo de nacionalizació2n</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" integrity="sha384-B0vP5xmATw1+K9KRQjQERJvTumQW0nPEzvF6L/Z6nronJ3oUOFUFpCjEUQouq2+l" crossorigin="anonymous">
  <!-- <link rel="icon" type="image/jpg" href="images/LOGO XL 2018.jpg"> -->
  <link rel="stylesheet" href="css/style.css">


</head>

<body>

  <div class="modal fade" id="modalCn" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title" id="exampleModalLabel"><i class="fa fa-edit" aria-hidden="true" style="font-size: 25px;"></i> Articulos sin costo de nacionalización</h4>
          </button>
        </div>
        <div class="modal-body">

          <!-- Aca se debe mostrar la tabla que arroja el SP RO_SP_ARTICULOS_SIN_COSTO_NAC -->

          <div class="table-responsive" id="tableIndex">
            <table class="table table-hover table-condensed table-striped text-center" id="tableScn">
              <thead class="thead-dark" style="font-size: small;">
                <th scope="col" >ARTICULO</th>
                <th scope="col" >RUBRO</th>
                <th scope="col" >ORDEN DE COMPRA</th>
              </thead>

              <tbody id="tableCn" style="font-size: small;">

              </tbody>
            </table>
          </div>
          <div class="modal-footer">
            <button class="btn btn-success btn_exportar" id="btnExportCn" onclick ="exportModal('tableScn')"> Exportar<i class="bi bi-file-earmark-excel"></i></button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
  </div>
