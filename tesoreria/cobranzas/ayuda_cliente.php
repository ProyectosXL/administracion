<?php
session_start();
// Solo usuarios con rol 'cliente' pueden acceder
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
            <p class="lead">Bienvenido a su guía del Portal de Cliente. Aquí encontrará explicaciones sobre cómo gestionar sus propuestas de pago.</p>

            <div class="accordion" id="ayudaClienteAccordion">

                <!-- Tema 1: Entendiendo su Portal -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                            <strong>1. ¿Qué información encuentro en Mi Portal?</strong>
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#ayudaClienteAccordion">
                        <div class="accordion-body">
                            Su portal está diseñado para darle una visión clara y completa de su estado de cuenta:
                            <ul>
                                <li><strong>Tarjetas de Resumen:</strong> En la parte superior, verá rápidamente la deuda total pendiente, el monto que está actualmente en negociación y si tiene propuestas que requieren su atención.</li>
                                <li><strong>Tabla "Mis Propuestas de Pago":</strong> Aquí se listan todas las propuestas que le hemos enviado, tanto las activas como las ya finalizadas.</li>
                                <li><strong>Cronograma de Pagos:</strong> A la derecha, un calendario resalta en azul los días en que tiene un vencimiento de pago correspondiente a una propuesta que usted ha aceptado.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Tema 2: Responder a una Propuesta -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                            <strong>2. ¿Cómo respondo a una propuesta?</strong>
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#ayudaClienteAccordion">
                        <div class="accordion-body">
                            <ol>
                                <li>En la tabla "Mis Propuestas de Pago", busque la propuesta que desea revisar y haga clic en el botón de "Ver" (<i class="fa-solid fa-eye"></i>).</li>
                                <li>Se abrirá un modal con todo el detalle: el monto, la fecha de pago, las facturas incluidas y el historial de la negociación.</li>
                                <li>En la parte inferior del modal, tendrá las siguientes opciones:
                                    <ul>
                                        <li><strong>Aceptar Propuesta:</strong> Si está de acuerdo con los términos, haga clic aquí. Se le pedirá una confirmación final.</li>
                                        <li><strong>Enviar Contrapropuesta:</strong> Si desea proponer cambios (quitar facturas, otra fecha, etc.), escriba su petición en el campo de comentarios y haga clic en este botón. Nuestro equipo de cobranzas recibirá su mensaje.</li>
                                    </ul>
                                </li>
                            </ol>
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
                           Una vez que una propuesta tiene el estado <span class="badge bg-success">ACEPTADA</span>, aparecerá un nuevo botón en la columna de "Acciones".
                           <ol>
                               <li>Haga clic en el botón de "Adjuntar" (<i class="fa-solid fa-paperclip"></i>) correspondiente a la propuesta pagada.</li>
                               <li>En el modal que aparece, seleccione su archivo de comprobante (puede ser PDF, JPG o PNG).</li>
                               <li>Haga clic en "Subir". El sistema procesará el archivo y el estado de su propuesta cambiará a <span class="badge bg-dark">DOCUMENTACION ADJUNTADA</span>, notificando a nuestro equipo.</li>
                           </ol>
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
<!-- No se necesitan scripts específicos para esta página -->
</body>
</html>