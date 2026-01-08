<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('Location: login.php');
    exit();
} 
include 'templates/layout/header.php'; 
?>

<main>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fa-solid fa-book-open text-primary me-2"></i>
           <strong>Capacitación y Guía de Uso (Administrador)</strong>
        </h1>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-2"></i>Volver al Dashboard
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <p class="lead">Bienvenido a la guía del Sistema de Gestión de Cobranzas. Aquí encontrarás explicaciones detalladas de cada funcionalidad para optimizar tu trabajo.</p>

            <div class="accordion" id="capacitacionAccordion">

                <!-- Tema 1: Dashboard y Pestañas -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                            <strong>1. Entendiendo el Dashboard y las Pestañas de Navegación</strong>
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#capacitacionAccordion">
                        <div class="accordion-body">
                            El dashboard es tu centro de mando y se divide en varias pestañas:
                            <ul>
                                <li><strong>Cronograma de Pagos:</strong> Tu herramienta principal para la planificación. Muestra en un calendario todos los vencimientos de propuestas aceptadas. Puedes hacer clic en un día para ver un resumen de todos los clientes que deben pagar.</li>
                                <li><strong>Gestión de Propuestas:</strong> Aquí controlas todas las negociaciones activas. Los KPIs te informan de montos en negociación, propuestas vencidas y cuáles requieren tu acción (contrapropuestas de clientes).</li>
                                <li><strong>Pendientes (Franquicias / Mayoristas):</strong> Estas pestañas listan a todos los clientes con facturas pendientes. Desde aquí inicias el proceso de creación de una nueva propuesta.</li>
                            </ul>
                            <strong>Notificaciones por Email:</strong> El sistema envía emails automáticos en momentos clave (nueva propuesta, contrapropuesta, etc.) para mantener a todos informados.
                        </div>
                    </div>
                </div>

                <!-- Tema 2: Crear una Propuesta -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                            <strong>2. Flujo de Creación de una Propuesta de Pago</strong>
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#capacitacionAccordion">
                        <div class="accordion-body">
                            <ol>
                                <li>Ve a la pestaña <strong>'Pendientes'</strong> y selecciona al cliente. Haz clic en el botón "Ver Detalle" (<i class="fa-solid fa-list-check"></i>).</li>
                                <li>En el modal, selecciona las facturas a incluir. Puedes ajustar el <strong>% de Descuento</strong> en cada línea (incluyendo Notas de Crédito, si aplica).</li>
                                <li>Al hacer clic en <strong>'Enviar Propuesta'</strong>, define la <strong>Fecha de Pago</strong> y el <strong>Medio de Pago</strong>.</li>
                                <li>¡Listo! Al confirmar, se creará la propuesta y se enviará una <strong>notificación por email automáticamente</strong> al cliente.</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- Tema 3: Gestionar una Contrapropuesta -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                            <strong>3. ¿Cómo Gestionar una Contrapropuesta del Cliente?</strong>
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#capacitacionAccordion">
                        <div class="accordion-body">
                            Cuando un cliente responde con cambios, recibirás una notificación por email y el KPI "Requieren Acción" aumentará.
                            <ol>
                                <li>En <strong>'Gestión de Propuestas'</strong>, busca la propuesta con estado <span class="badge bg-primary">CONTRAPROPUESTA CLIENTE</span> y haz clic en "Revisar" (<i class="fa-solid fa-magnifying-glass"></i>).</li>
                                <li>En el modal, revisa el <strong>Historial</strong> para ver los comentarios del cliente.</li>
                                <li>La propuesta ahora es editable: puedes <strong>quitar facturas</strong> de la lista (<i class="fa-solid fa-trash"></i>) o <strong>ajustar los descuentos</strong> directamente en la tabla. Los totales se recalcularán automáticamente.</li>
                                <li>Puedes <strong>adjuntar una imagen</strong> al historial (ej. una captura de pantalla) para documentar la negociación.</li>
                                <li>Cuando estés listo, haz clic en <strong>'Aceptar Contrapropuesta'</strong>. Esto guarda todos tus cambios, pasa la propuesta a estado <span class="badge bg-success">ACEPTADA</span> y notifica al cliente por email.</li>
                            </ol>
                        </div>
                    </div>
                </div>
                
                <!-- Tema 4: Automatizaciones y Tareas Finales -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingFour">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                            <strong>4. Automatizaciones del Sistema y Tareas Finales</strong>
                        </button>
                    </h2>
                    <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#capacitacionAccordion">
                        <div class="accordion-body">
                            El sistema realiza varias tareas automáticamente para ayudarte:
                            <ul>
                                <li><strong>Aviso de Vencimiento de Propuesta:</strong> El sistema enviará automáticamente un email de recordatorio a los clientes 24 horas antes de que su propuesta pendiente venza (a las 72hs hábiles).</li>
                                <li><strong>Vencimiento Automático:</strong> Si un cliente no responde a una propuesta en <strong>96 horas hábiles</strong>, el sistema la marcará como <span class="badge bg-danger">VENCIDA</span>. Ya no será visible para el cliente.</li>
                                <li><strong>Sincronización de Pagos:</strong> Cuando un cliente adjunta un comprobante (estado <span class="badge bg-dark">DOCUMENTACION ADJUNTADA</span>), el sistema intentará verificar si las facturas correspondientes ya están canceladas en el sistema central. Si es así, la propuesta pasará a estado <span class="badge bg-success">PAGADO</span> y se notificará a todas las partes por email.</li>
                                <li><strong>Exportar a Excel:</strong> Dentro del detalle de cualquier propuesta, usa el botón "Exportar a Excel" para generar un reporte detallado.</li>
                            </ul>
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