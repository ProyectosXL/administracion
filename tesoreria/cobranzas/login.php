<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Gestión de Cobranzas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background-color: #f8f9fa;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
        }
    </style>
</head>
<body>
    <div class="card login-card shadow-sm">
        <div class="card-body p-5">
            <h3 class="card-title text-center mb-4">
                <i class="fa-solid fa-file-invoice-dollar text-primary"></i>
                Gestión de Cobranzas
            </h3>
            <form id="login-form">
                <div class="mb-3">
                    <label for="nombre" class="form-label">Usuario</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" required>
                </div>
                <div class="mb-4">
                    <label for="pass" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="pass" name="pass" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Ingresar
                    </button>
                </div>
            </form>
            <div id="login-alert" class="mt-3"></div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        // Lógica de Login que irá en app.js pero la ponemos aquí temporalmente para que funcione
        $(document).ready(function() {
            $('#login-form').on('submit', function(e) {
                e.preventDefault();
                const btn = $(this).find('button[type="submit"]');
                const spinner = btn.find('.spinner-border');
                const alertContainer = $('#login-alert');
                
                btn.prop('disabled', true);
                spinner.removeClass('d-none');
                alertContainer.html('');

                $.ajax({
                    url: 'api/auth_controller.php?action=login',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Redirigir según el rol del usuario
                            if (response.rol === 'mayoristas') {
                                window.location.href = 'mayoristas.php';
                            } else if (response.rol === 'admin') {
                                window.location.href = 'index.php';
                            } else {
                                window.location.href = 'portal_cliente.php'; 
                            }
                        } else {
                            alertContainer.html(`<div class="alert alert-danger">${response.message}</div>`);
                        }
                    },
                    error: function() {
                        alertContainer.html('<div class="alert alert-danger">Error de conexión. Intente de nuevo.</div>');
                    },
                    complete: function() {
                        btn.prop('disabled', false);
                        spinner.addClass('d-none');
                    }
                });
            });
        });
    </script>
</body>
</html>