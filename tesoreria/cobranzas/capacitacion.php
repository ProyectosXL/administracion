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
            Capacitación y Guía de Uso
        </h1>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-2"></i>Volver al Dashboard
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <p class="lead">Bienvenido a la guía del Sistema de Gestión de Cobranzas. Aquí encontrarás explicaciones detalladas de cada funcionalidad.</p>

            <div class="accordion" id="capacitacionAccordion">

                <!-- Tema 1: Dashboard Principal -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                            <strong>1. Entendiendo el Dashboard Principal</strong>
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#capacitacionAccordion">
                        <div class="accordion-body">
                            El dashboard principal te da una vista rápida del estado de todas las propuestas.
                            <ul>
                                <li><strong>KPIs Superiores:</strong> Muestran totales como 'Propuestas Activas', 'Monto en Negociación', etc.</li>
                                <li><strong>Distribución de Estados:</strong> Un gráfico circular que muestra la proporción de cada estado (Pagado, Pendiente, etc.).</li>
                                <li><strong>Propuestas Aceptadas:</strong> Un gráfico de barras que muestra cuántas propuestas se han aceptado en los últimos 7 días.</li>
                                <li><strong>Pestañas de Navegación:</strong> Te permiten cambiar entre la 'Gestión de Propuestas' y las listas de deudores por 'Franquicias' o 'Mayoristas'.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Tema 2: Crear una Propuesta de Pago -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                            <strong>2. ¿Cómo Crear una Propuesta de Pago?</strong>
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#capacitacionAccordion">
                        <div class="accordion-body">
                            <ol>
                                <li>Ve a la pestaña <strong>'Pendientes (Franquicias)'</strong> o <strong>'Pendientes (Mayoristas)'</strong>.</li>
                                <li>Busca al cliente en la lista y haz clic en el botón de "Ver Detalle" (<i class="fa-solid fa-list-check"></i>).</li>
                                <li>Se abrirá un modal con todos los comprobantes disponibles del cliente. Selecciona los que deseas incluir en la propuesta usando los checkboxes.</li>
                                <li>Las <strong>Notas de Crédito (NCP, NCR)</strong> restarán automáticamente del total. Puedes ajustar el <strong>% de Descuento</strong> para cada comprobante.</li>
                                <li>Una vez seleccionados los comprobantes, haz clic en <strong>'Enviar Propuesta'</strong>.</li>
                                <li>El sistema te pedirá que confirmes la <strong>Fecha Propuesta de Pago</strong>. Selecciónala y haz clic en 'Crear Propuesta'.</li>
                                <li>¡Listo! La propuesta ha sido creada y enviada al cliente. Ahora podrás verla y gestionarla en la pestaña <strong>'Gestión de Propuestas'</strong>.</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- Tema 3: Gestionar una Contrapropuesta -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                            <strong>3. Gestionar una Contrapropuesta del Cliente</strong>
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#capacitacionAccordion">
                        <div class="accordion-body">
                            Cuando un cliente responde con una contrapropuesta, el estado cambiará y requerirá tu acción.
                            <ol>
                                <li>En la pestaña <strong>'Gestión de Propuestas'</strong>, busca la propuesta con estado <span class="badge bg-primary">CONTRAPROPUESTA CLIENTE</span>.</li>
                                <li>Haz clic en el botón de "Revisar" (<i class="fa-solid fa-magnifying-glass"></i>).</li>
                                <li>En el modal, lee el comentario del cliente en el <strong>Historial</strong> para entender su petición.</li>
                                <li>Puedes <strong>eliminar facturas</strong> (<i class="fa-solid fa-trash"></i>), <strong>ajustar los descuentos</strong> o cambiar la <strong>Fecha Propuesta de Pago</strong>.</li>
                                <li>Añade un comentario para el cliente (opcional) y haz clic en <strong>'Enviar Propuesta Final'</strong>. El estado cambiará y el cliente deberá aceptarla.</li>
                            </ol>
                        </div>
                    </div>
                </div>
                
                <!-- Tema 4: Sincronizar Pagos y Eliminar -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingFour">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                            <strong>4. Finalizar el Proceso: Sincronizar Pagos y Eliminar Propuestas</strong>
                        </button>
                    </h2>
                    <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#capacitacionAccordion">
                        <div class="accordion-body">
                            El último paso del ciclo es marcar las propuestas como pagadas.
                            <ul>
                                <li><strong>Sincronizar Pagos:</strong> Después de que el cliente sube el comprobante (estado <span class="badge bg-dark">DOCUMENTACION ADJUNTADA</span>) y el pago impacta en el sistema principal, haz clic en el botón de sincronizar (<i class="fa-solid fa-sync"></i>) en el dashboard. El sistema verificará automáticamente qué propuestas han sido completamente pagadas y actualizará su estado a <span class="badge bg-success">PAGADO</span>.</li>
                                <li><strong>Eliminar Propuesta:</strong> Si una propuesta se creó por error o debe ser anulada, puedes hacer clic en el botón de eliminar (<i class="fa-solid fa-trash"></i>) en la tabla de gestión. Se te pedirá confirmación, ya que esta acción es irreversible.</li>
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
<!-- No se necesitan scripts específicos para esta página -->
</body>
</html>