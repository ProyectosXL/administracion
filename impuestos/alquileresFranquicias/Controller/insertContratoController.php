
<?php
require_once __DIR__ . '/../Class/Alquiler.php';

class InsertContratoController {
    private $alquiler;

    public function __construct() {
        $this->alquiler = new Alquiler();
    }

    public function insertarContrato() {
        try {
            $nroSucursal = $_POST['franquicia'] ?? null;
            $vigDesde = $_POST['fechaDesde'] ?? null;
            $vigHasta = $_POST['fechaHasta'] ?? null;

            if (!$nroSucursal || !$vigDesde || !$vigHasta) {
                throw new Exception("Datos incompletos.");
            }

            $contratoComercial = $this->uploadFile('contratoComercial');
            $contratoLocacion = $this->uploadFile('contratoLocacion');
            $habilitacion = $this->uploadFile('habilitacion');

            $resultado = $this->alquiler->insertarContratoAlquiler($nroSucursal, $vigDesde, $vigHasta, $contratoComercial, $contratoLocacion, $habilitacion);

            if ($resultado) {
                echo json_encode(["success" => true, "message" => "Contrato de alquiler guardado exitosamente."]);
            } else {
                throw new Exception("No se pudo insertar el contrato.");
            }
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "Error al guardar el contrato: " . $e->getMessage()]);
        }
    }

    private function uploadFile($inputName) {
        if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $uploadDir = __DIR__ . '/../archivos/';
        $fileName = uniqid() . '_' . $_FILES[$inputName]['name'];
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES[$inputName]['tmp_name'], $filePath)) {
            return $fileName;
        } else {
            throw new Exception("Error al subir el archivo " . $inputName);
        }
    }
}

// Instanciar y ejecutar el controlador
$controller = new InsertContratoController();
$controller->insertarContrato();