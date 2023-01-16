<?php

$originalDate = "2022-12-01";

$periodo = str_replace("0","",substr($originalDate, 5, 2)).'-'.substr($originalDate, 0, 4);

echo $periodo;


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

    <script src="https://code.jquery.com/jquery-3.6.3.js" integrity="sha256-nQLuAZGRRcILA+6dMBOvcRh5Pe310sBpanc6+QBmyVM=" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

</head>
<body>

<select class="js-example-basic-single" name="state">
  <option value="AL">Alabama</option>
    ...
  <option value="WY">Wyoming</option>
</select>
 
</body>

<script>
    // In your Javascript (external .js resource or <script> tag)
    $(document).ready(function() {
        $('.js-example-basic-single').select2();
    });
</script>

</html>
<br/>


function prorratearGastos() {
  /*  $nombre = document.querySelector("#nombre"), */

  let desde = document.getElementsByName("desde")[0].value;
  let hasta = document.getElementsByName("hasta")[0].value;

  const swalWithBootstrapButtons = Swal.mixin({
    customClass: {
      confirmButton: 'btn btn-success',
      cancelButton: 'btn btn-danger'
    },
    buttonsStyling: false
  });

  fetch("./Controller/prorratear.php?desde=" + desde + "&hasta=" + hasta)
    .then((respuesta) => respuesta.json())
    .then((perfil) => {
      if (perfil.resultado == 0)      
      swalWithBootstrapButtons.fire({
        title: 'Desea realizar el prorrateo?',
        text: "Ya no se podran deshacer los cambios!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ok, prorratear!',
        cancelButtonText: 'No, cancelar!',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          swalWithBootstrapButtons.fire(
            'Prorrateado!',
            'Los gastos fueron prorrateados',
            'success'
          )
        } else if (
          /* Read more about handling dismissals below */
          result.dismiss === Swal.DismissReason.cancel
        ) {
          swalWithBootstrapButtons.fire(
            'Cancelado',
            'Los gastos no fueron prorrateados :(',
            'error'
          )
        }
      })

    });
}


