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
            <table class="table table-hover table-condensed table-striped text-center" style="width: 60%;border: solid 1px;margin-left:20%" cellspacing="0" data-page-length="100">
                <thead class="" style="font-size: small;">
                    <th scope="col" style="width: 6%;text-align:left;font-size:25px">
                    <div class="row">
                        <div class="col-6">

                            <strong>Listado de Empleados</strong>
                        </div>
                        <div class="col" style="text-align: right;">

                            
                            <button style="margin-right:4px;height:42px;background-color:#3f6fad; border: none;outline: none;" onclick="cambiarFormatoCuadricula(this)"><i class="fa-regular fa-address-card" style="color:white"></i>
                            <button style="margin-right:4px;height:42px;background-color:#49d793; border: none;outline: none;"><i class="bi bi-plus-lg" style="color:white"></i>
                            <button style="margin-right:4px;height:42px;background-color:#59b9e2; border: none;outline: none;"><i class="bi bi-printer-fill" style="color:white"></i>
                            <button style="height:42px;background-color:##f0f0f0; border: none;outline: none;"><i class="bi bi-three-dots-vertical" style="color:black"></i>
                        </div>
                        
                    </div>
                        <div style="border:solid 1px grey; border-radius: 10px;margin-top:30px;height:40px;width:100%;margin-left:5px" class ="row">
                    
                            <input type="text" placeholder="Busqueda" id="inputBusqueda" style="margin-top:4px;margin-left:2%;border:solid 1px grey;width:22%;height:30px; font-size: 14px;">
                            <select name="selectFiltro" id="selectFiltro" style="border: solid 1px grey; width: 15%; height: 30px; margin-top: 4px; margin-left: 5px;font-size: 14px;">
                            <option value="%">Todos</option>
                            <option value="ENCARGADA">Encargado</option>
                            <option value="SUB ENCARGADO">Sub Encargado</option>
                            <option value="caja">caja</option>
                            <option value="VENDEDOR">VENDEDOR</option>
                
                            </select>
                            <select name="" id="" style="border:solid 1px grey;width:15%;height:30px;margin-top:4px;margin-left:5px;font-size: 14px;" >
                            <option value="1">Todos</option>
                            <option value="">2</option>
                            </select>
                            <select name="" id="" style="border:solid 1px grey;width:15%;height:30px;margin-top:4px;margin-left:5px; font-size: 14px;" >
                            <option value="activo">Activos</option>
                            <option value="inactivo">Inactivo</option>
                            </select>                           
                    
                        </div>
                    </th>
                    
                </thead>

                <tbody >
                
                    <tr>

                        <td style="background-color:white" id="tdPrincipal">
                        <table style="width:100%">
                            <thead >
                            <tr style="background-color:#59b9e2;color:white">
                                <td> 
                                        <input type="checkbox" id="miCheckbox" class="custom-checkbox">
                                        <label for="miCheckbox" class="custom-checkbox-label"></label>
                                </td>
                                <td>Legajo</td>
                                <td>Apellido</td>
                                <td>Nombres</td>
                                <td>Cargo</td>
                                <td>Localidad</td>
                                <td>Local</td>
                                <td>Accion</td>
                            </tr>
                            </thead>
                            <tbody id="tableBody">
                            <tr>
                                
                            </tr>
                            </tbody>
                        </table> 
                        
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
