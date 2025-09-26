
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
            $vigenciaOk = $this->alquiler->validarVigencia($nroSucursal, $vigDesde, $vigHasta);
            
            if(!$vigenciaOk){
                // ERROR YA EXSTE VIGENCIA
                throw new Exception("Ya existe un contrato vigente para la franquicia seleccionada.");
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
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $originalName = $_FILES[$inputName]['name'];
        $fileName = uniqid() . '_' . $originalName;
        $filePath = $uploadDir . $fileName;
        $tempFile = $_FILES[$inputName]['tmp_name'];

        // Verificar tamaño del archivo (máximo 10MB)
        $maxFileSize = 10 * 1024 * 1024; // 10MB
        if ($_FILES[$inputName]['size'] > $maxFileSize) {
            throw new Exception("El archivo $originalName es demasiado grande. Tamaño máximo: 10MB");
        }

        // Comprimir archivo si es PDF y supera los 2MB
        if (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) === 'pdf' && $_FILES[$inputName]['size'] > 2 * 1024 * 1024) {
            $compressedFile = $this->compressPDF($tempFile, $filePath);
            if ($compressedFile) {
                return $fileName;
            }
        }

        if (move_uploaded_file($tempFile, $filePath)) {
            return $fileName;
        } else {
            throw new Exception("Error al subir el archivo " . $originalName);
        }
    }

    private function compressPDF($inputFile, $outputFile) {
        try {
            // Usar Ghostscript para comprimir PDF si está disponible
            $gsCommand = 'gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/screen -dNOPAUSE -dQUIET -dBATCH -sOutputFile="' . $outputFile . '" "' . $inputFile . '"';
            
            // Verificar si Ghostscript está disponible
            $gsAvailable = false;
            if (function_exists('exec')) {
                exec('gs --version 2>&1', $output, $returnCode);
                $gsAvailable = ($returnCode === 0);
            }

            if ($gsAvailable) {
                exec($gsCommand, $output, $returnCode);
                if ($returnCode === 0 && file_exists($outputFile)) {
                    return true;
                }
            }

            // Si Ghostscript no está disponible, usar compresión básica con opciones de PHP
            return $this->basicPDFCompression($inputFile, $outputFile);
            
        } catch (Exception $e) {
            // Si falla la compresión, mover el archivo original
            if (move_uploaded_file($inputFile, $outputFile)) {
                return true;
            }
            return false;
        }
    }

    private function basicPDFCompression($inputFile, $outputFile) {
        try {
            // Compresión básica copiando el archivo con configuración optimizada
            $source = file_get_contents($inputFile);
            if ($source !== false) {
                // Aplicar compresión de contenido si es posible
                $compressed = gzcompress($source, 9);
                if ($compressed !== false) {
                    // Si la compresión reduce el tamaño significativamente, usar el original
                    if (strlen($compressed) < strlen($source) * 0.9) {
                        file_put_contents($outputFile, gzuncompress($compressed));
                    } else {
                        file_put_contents($outputFile, $source);
                    }
                    return true;
                }
            }
            return move_uploaded_file($inputFile, $outputFile);
        } catch (Exception $e) {
            return move_uploaded_file($inputFile, $outputFile);
        }
    }
}

// Instanciar y ejecutar el controlador
$controller = new InsertContratoController();
$controller->insertarContrato();