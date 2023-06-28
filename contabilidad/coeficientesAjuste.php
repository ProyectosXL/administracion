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

    <div class="modal fade" id="modalCA" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="exampleModalLabel"><i class="fa fa-edit" aria-hidden="true" style="font-size: 25px;"></i> Articulos sin costo de nacionalización</h4>
                </div>
                <div class="modal-body">

                    <div class="row">
                            <div class="col">Coeficiente De Ajuste</div>
                            <div class="col">Periodo</div>
                    </div>
                    <div class="row">
                        <div class="col"><input type="text" style="text-align:center" id="ca-valor"></input></div>
                        <div class="col"><input type="text" placeholder="0-0000" style="text-align:center" id="ca-periodo" readonly></input></div>
                    </div>
    

                </div>
                    
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal" onclick="insertarCoeficienteAjuste()">Cargar</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    
</body>    
