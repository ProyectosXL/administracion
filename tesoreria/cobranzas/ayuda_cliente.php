<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'cliente') {
    header('Location: login.php');
    exit();
} 
include 'templates/layout/header.php'; 
?>

<main>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fa-solid fa-circle-question text-primary me-2"></i>
            <strong>Centro de Ayuda para Clientes</strong>
        </h1>
        <a href="portal_cliente.php" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-2"></i>Volver a Mi Portal
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <p class="lead">Bienvenido a su guía del Portal de Cliente. Aquí encontrará explicaciones sobre cómo gestionar sus propuestas de pago de forma centralizada.</p>

            <div class="accordion" id="ayudaClienteAccordion">

                <!-- Tema 1: Vista Unificada -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                            <strong>1. ¿Qué información veo en Mi Portal? (Vista Unificada)</strong>
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#ayudaClienteAccordion">
                        <div class="accordion-body">
                            Su portal ahora le ofrece una <strong>vista consolidada de todos sus locales</strong> asociados a su razón social. Aunque inicie sesión con el usuario de una sucursal, verá la información de todas.
                            <ul>
                                <li><strong>KPIs Consolidados:</strong> Las tarjetas superiores muestran la suma total de su deuda, los montos en negociación y los pagos pendientes de todos sus locales.</li>
                                <li><strong>Tabla de Propuestas:</strong> Verá una lista unificada de las propuestas de todas sus sucursales. Hemos añadido una columna <strong>"Local"</strong> para que pueda identificar a qué sucursal corresponde cada propuesta.</li>
                                <li><strong>Cronograma Centralizado:</strong> El calendario resalta los vencimientos de pago de todas sus propuestas aceptadas, sin importar el local.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Tema 2: Responder a una Propuesta -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                            <strong>2. ¿Cómo respondo a una propuesta? (¡Importante!)</strong>
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#ayudaClienteAccordion">
                        <div class="accordion-body">
                            Al recibir una nueva propuesta (recibirá una notificación por email), tiene <strong>96 horas hábiles para responder</strong>. De lo contrario, la propuesta vencerá.
                            <ol>
                                <li>En la tabla, haga clic en el botón "Ver" (<i class="fa-solid fa-eye"></i>). El título del detalle le indicará el local de la propuesta.</li>
                                <li>Dentro del detalle, tiene dos opciones principales:
                                    <ul>
                                        <li><span class="badge bg-success">Aceptar Propuesta</span>: Si está de acuerdo con todo, haga clic en este botón.</li>
                                        <li><span class="badge bg-danger">Enviar Contrapropuesta</span>: Si desea un cambio, use la sección "Negociar Propuesta". Al cambiar el <strong>Medio de Pago</strong> o la <strong>Fecha</strong>, los descuentos y totales se recalcularán al instante para que vea el impacto. Luego, escriba un comentario (obligatorio) y envíe.</li>
                                    </ul>
                                </li>
                            </ol>
                            <strong>Aviso de Vencimiento:</strong> Recibirá un email de recordatorio 24 horas antes de que su propuesta expire.
                        </div>
                    </div>
                </div>

                <!-- Tema 3: Adjuntar un Comprobante -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                            <strong>3. Ya pagué, ¿cómo subo mi comprobante?</strong>
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#ayudaClienteAccordion">
                        <div class="accordion-body">
                           Una vez que una propuesta tiene el estado <span class="badge bg-success">ACEPTADA</span>, aparecerá un nuevo botón con un clip (<i class="fa-solid fa-paperclip"></i>) en la columna "Acciones".
                           <ol>
                               <li>Haga clic en el botón "Adjuntar" de la propuesta correspondiente.</li>
                               <li>Seleccione su archivo de comprobante (PDF, JPG o PNG) y haga clic en "Subir".</li>
                               <li>El estado cambiará a <span class="badge bg-dark">DOCUMENTACION ADJUNTADA</span> y nuestro equipo recibirá una <strong>notificación por email</strong> para verificar el pago.</li>
                           </ol>
                           Una vez verificado, el estado final será <span class="badge bg-success">PAGADO</span> y usted también recibirá una notificación.
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

<?php 
include 'templates/layout/footer.php'; 
?>
</body>
</html>