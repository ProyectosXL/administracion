
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Entrega</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        #signature-pad {
            border: 1px solid #ccc;
            border-radius: 4px;
            touch-action: none;
        }
        .alert-icon {
            font-size: 1.5rem;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-3">
        <!-- Alert con título -->
        <div class="alert alert-primary d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-clipboard-check alert-icon"></i>
            <div>
                <h4 class="alert-heading mb-0">Formulario de Entrega</h4>
            </div>
        </div>

        <form id="entregaForm">
            <div class="mb-3">
                <label for="numeroRegistro" class="form-label">
                    <i class="bi bi-hash"></i> Número de Registro
                </label>
                <input type="text" class="form-control" id="numeroRegistro" readonly>
            </div>
            
            <div class="mb-3">
                <label for="entrego" class="form-label">
                    <i class="bi bi-person-fill"></i> Entregó
                </label>
                <select class="form-select" id="entrego" required>
                    <option value="">Seleccione una persona</option>
                    <option value="Juan Pérez">Juan Pérez</option>
                    <option value="María García">María García</option>
                    <option value="Carlos López">Carlos López</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="recibio" class="form-label">
                    <i class="bi bi-person-check-fill"></i> Recibió
                </label>
                <select class="form-select" id="recibio" required>
                    <option value="">Seleccione una persona</option>
                    <option value="Juan Pérez">Juan Pérez</option>
                    <option value="María García">María García</option>
                    <option value="Carlos López">Carlos López</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="observaciones" class="form-label">
                    <i class="bi bi-chat-left-text-fill"></i> Observaciones
                </label>
                <textarea class="form-control" id="observaciones" rows="3"></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">
                    <i class="bi bi-pen-fill"></i> Firma
                </label>
                <canvas id="signature-pad" class="mb-3" width="300" height="150"></canvas>
                <button type="button" id="clear" class="btn btn-secondary btn-sm">
                    <i class="bi bi-eraser-fill"></i> Limpiar Firma
                </button>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100">
                <i class="bi bi-save-fill"></i> Registrar
            </button>
        </form>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/signature_pad/1.5.3/signature_pad.min.js"></script>
    <script>
        // Inicializar el número de registro
        let numeroRegistro = 1;
        
        // Función para generar el número de registro
        function generarNumeroRegistro() {
            return 'C' + String(numeroRegistro).padStart(10, '0');
        }

        // Inicializar SignaturePad
        var canvas = document.getElementById('signature-pad');
        var signaturePad = new SignaturePad(canvas);

        // Limpiar firma
        document.getElementById('clear').addEventListener('click', function() {
            signaturePad.clear();
        });

        // Manejar el envío del formulario
        document.getElementById('entregaForm').addEventListener('submit', function(e) {
            e.preventDefault();

            if (signaturePad.isEmpty()) {
                alert('Por favor, proporcione una firma.');
                return;
            }

            // Aquí iría el código para enviar los datos al servidor
            console.log('Número de Registro:', document.getElementById('numeroRegistro').value);
            console.log('Entregó:', document.getElementById('entrego').value);
            console.log('Recibió:', document.getElementById('recibio').value);
            console.log('Observaciones:', document.getElementById('observaciones').value);
            console.log('Firma:', signaturePad.toDataURL());

            alert('Formulario enviado con éxito');

            // Incrementar el número de registro para la próxima entrada
            numeroRegistro++;
            document.getElementById('numeroRegistro').value = generarNumeroRegistro();

            // Limpiar el formulario
            this.reset();
            signaturePad.clear();
        });

        // Inicializar el número de registro al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('numeroRegistro').value = generarNumeroRegistro();
        });
    </script>
</body>
</html>