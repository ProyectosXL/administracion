
<?php
require_once __DIR__ . '/../Class/Alquiler.php';

class InsertContratoController {
    private $alquiler;

    public function __construct() {
        $this->alquiler = new Alquiler();
    }

    public function insertarContrato() {
        try {
            $nroSucursal = $_POST['franquicia'];
            $descSucursal = '';
            $franquicias = $this->alquiler->traerFranquicias();
            foreach ($franquicias as $franquicia) {
                if ($franquicia['NRO_SUCURSAL'] == $nroSucursal) {
                    $descSucursal = $franquicia['DESC_SUCURSAL'];
                    break;
                }
            }
            $vigDesde = $_POST['fechaDesde'];
            $vigHasta = $_POST['fechaHasta'];

            $this->alquiler->insertarContratoAlquiler($nroSucursal, $descSucursal, $vigDesde, $vigHasta);
            echo json_encode(["success" => true, "message" => "Contrato de alquiler guardado exitosamente."]);
        } catch (Exception $e) {
            error_log("Error al guardar el contrato: " . $e->getMessage());
            echo json_encode(["success" => false, "message" => "Error al guardar el contrato. Por favor, inténtelo de nuevo más tarde."]);
        }
    }
}

// Instanciar y ejecutar el controlador
$controller = new InsertContratoController();
$controller->insertarContrato();