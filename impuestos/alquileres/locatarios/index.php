
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Locatarios IRSA - Exportación de Datos</title>
    <link rel="shortcut icon" href="assets/css/icono.jpg" />
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container-custom">
        <!-- Header -->
        <div class="header">
            <h1>📊 Exportar Datos para Locatarios de IRSA</h1>
            <p>Seleccione el rango de fechas y la sucursal para generar el archivo de exportación</p>
        </div>

        <!-- Alert Container -->
        <div id="alert" class="alert"></div>

        <!-- Formulario -->
        <form id="formExportar" onsubmit="return false;">
            <div class="form-row">
                <div class="form-group">
                    <label for="desde">📅 Fecha Desde</label>
                    <input type="date" id="desde" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="hasta">📅 Fecha Hasta</label>
                    <input type="date" id="hasta" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="suc">🏢 Sucursal</label>
                    <select id="suc" class="form-control" required>
                        <option value="">-- Seleccione una sucursal --</option>
                        <option value="3">Alto Palermo</option>
                        <option value="6">Avellaneda</option>
                        <option value="7">Abasto</option>
                        <option value="48">Alto Rosario</option>
                        <option value="66">Dot</option>
                        <option value="76">Soleil</option>
                        <option value="78">Arcos</option>
                    </select>
                </div>
            </div>

            <!-- Botón de exportar -->
            <button type="button" class="btn btn-primary" id="boton">
                📥 Exportar TXT
            </button>
        </form>

        <!-- Loader -->
        <div id="loader" class="loader"></div>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>