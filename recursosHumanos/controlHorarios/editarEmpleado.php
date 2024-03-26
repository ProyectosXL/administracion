<?php 
    require_once "../Class/Horario.php";
    $horario = new Horario();

    $empleado = $horario->traerVendedores($_GET['nroLegajo']);
    $empleado = $empleado[0];  

?>
<!DOCTYPE html>
    <html lang="en">
    <head>

        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Nuevo proceso de recodificación</title>
       
        <?php 
            require_once "../../assets/css/css.php";
        ?>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <style>
        /* Estilo para el checkbox personalizado */
        .custom-checkbox {
            /* Ocultar el checkbox nativo */
            position: absolute;
            opacity: 0;
        }

        /* Estilo para el cuadro del checkbox */
        .custom-checkbox-label {
            /* Tamaño del cuadro */
            width: 20px;
            height: 20px;
            /* Establecer el fondo celeste */
            background-color: #59b9e2;
            /* Establecer el borde blanco */
            border: 1px solid white;
            /* Alinear el cuadro al centro */
            display: inline-block;
            vertical-align: middle;
            /* Posición relativa para los elementos internos */
            position: relative;
        }

        /* Estilo para la tilde dentro del checkbox */
        .custom-checkbox:checked + .custom-checkbox-label::after {
            /* Contenido de la tilde */
            content: '\2714'; /* Caracter Unicode de la tilde */
            /* Estilo de la tilde */
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 14px; /* Tamaño de la tilde */
            color: white; /* Color de la tilde */
        }

        /* Estilo para la vista de cuadrícula */
        #cuadrado {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); /* Establece el ancho mínimo de cada elemento y el número de columnas según el ancho del contenedor */
            gap: 20px; /* Espacio entre elementos */
            height: 300px; /* Altura del contenedor */
            background-color: white;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
                }


  </style>

    </head>

    <body>

            <div class="table-responsive" id="tableIndex">
                <table class="table text-center" style="width: 60%;border: solid 1px;margin-left:20%" cellspacing="0" data-page-length="100">
                    <thead class="" style="font-size: small;">
                        <th scope="col" style="width: 6%;text-align:left;font-size:25px">
                            <div class="row">
                                <div class="col" style="color:grey; border-bottom:1px solid">

                                    <strong>Informacion del empleado</strong>
                                </div>
                                
                            </div>
                   
                        
                        </th>
                        
                    </thead>
                    <tbody >
                        <tr>
                            <td>
                                         
                            <div class="mt-1" style="width:30%;height:600px;border:1px solid grey">
                                    <div style="height:30px;text-align:right;margin:10px" ><i class="bi bi-three-dots-vertical" style="color:black;font-size:25px"></i></div>
                                    
                                    <img src="../../assets/images/pruebafoto.png" alt="" style="height: 115px; width: 100%;"> 
                                    <div style="color:#5095e3;height:10%;border-bottom:1px solid grey"><strong><?= $empleado['APELLIDO'] ?> <?= $empleado['NOMBRE'] ?></strong></div>
                                    <div class='ml-2' style="text-align:left;color:grey"><strong>Informacion</strong></div>
                                    <div class='ml-2 mt-2' style="text-align:left;color:grey;font-size:13px">
                                        <div>
                                            Legajo
                                        </div>
                                        <div><strong><?= $empleado['NRO_LEGAJO'] ?></strong></div>
                                    </div>
                                    <div class='ml-2 mt-2' style="text-align:left;color:grey;font-size:13px">
                                        <div>
                                            Cargo
                                        </div>
                                        <div><strong><?= $empleado['TAREA_HABITUAL'] ?></strong></div>
                                    </div>
                                    <div class='ml-2 mt-2' style="text-align:left;color:grey;font-size:13px">
                                        <div>
                                            Codigo Vendedor
                                        </div>
                                        <div><strong><?= $empleado['BLOQUE'] ?></strong></div>
                                    </div>
                                    <div class='ml-2 mt-2' style="text-align:left;color:grey;font-size:13px">
                                        <div>
                                            Sucursal
                                        </div>
                                        <div><strong><?= $empleado['NRO_SUCURS'] ?></strong></div>
                                    </div>
                                    

                            </div>
                            </td>
                        </tr>
                    </tbody>
             
                </table>
            </div>
                

        <?php 
            require_once "../../assets/js/js.php"
        ?>

        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.2/xlsx.full.min.js"></script>


        
    </body>

    </html>
