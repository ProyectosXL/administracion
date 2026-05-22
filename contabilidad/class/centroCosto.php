
<?php

class CentroCosto
{

    function __construct(){

        require_once __DIR__.'/../../class/conexion.php';
        $cid = new Conexion();

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }


        if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){
            $this->cid_central  = $cid->conectar('uy');
        }else{
            $this->cid_central = $cid->conectar('central');

        }

    }

    public function traerCentroCostos(){

        $sql = "SELECT * FROM RO_T_CENTRO_DE_COSTOS WHERE ACTIVO = 1";

        $stmt = sqlsrv_query( $this->cid_central, $sql );
        try{

            $rows = array();

            while( $v = sqlsrv_fetch_array( $stmt) ) {
                $rows[] = $v;
            }

            $myJSON = json_encode($rows);

            return $myJSON;

        } catch (\Throwable $th){

            print_r($th);

        }

    }

    public function traerSectoresCentroCostos() {

        $sql = " SELECT DISTINCT(SECTOR) SECTOR FROM RO_T_CENTRO_DE_COSTOS ";

        $stmt = sqlsrv_query( $this->cid_central, $sql );
        try{

            $rows = array();

            while( $v = sqlsrv_fetch_array( $stmt) ) {
                $rows[] = $v;
            }

            return $rows;

        } catch (\Throwable $th){

            print_r($th);

        }

    }

    function traerDescripcion($codigo)
    {
        $sql = "SELECT TOP 1 DESC_AUXILIAR, SECTOR, NUM_SUCURSAL FROM RO_T_CENTRO_DE_COSTOS WHERE COD_AUXILIAR = '$codigo'";
        try {

           $stmt = sqlsrv_query($this->cid_central, $sql);


           $dato = sqlsrv_fetch_array($stmt);

          echo json_encode($dato);

        } catch (\Throwable $th){
            print_r($th);
        }


    }

    public function traerAuxiliaresDisponibles() {

        $sql = "SELECT COD_AUXILIAR, DESC_AUXILIAR FROM AUXILIAR
                WHERE HABILITADO = 'S' AND COD_AUXILIAR != 'SinAsignar'
                  AND COD_AUXILIAR NOT IN (SELECT COD_AUXILIAR FROM RO_T_CENTRO_DE_COSTOS)
                ORDER BY DESC_AUXILIAR";

        $stmt = sqlsrv_query($this->cid_central, $sql);
        try {
            $rows = [];
            while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $rows[] = $v;
            }
            return json_encode($rows);
        } catch (\Throwable $th) {
            print_r($th);
        }
    }

    public function traerCentrosCostoAdmin($estado = 1) {

        $sql = "SELECT ID, COD_AUXILIAR, DESC_AUXILIAR, CENTRO_COSTO, SECTOR, NUM_SUCURSAL, ACTIVO
                FROM RO_T_CENTRO_DE_COSTOS";

        if ($estado !== null) {
            $sql .= " WHERE ACTIVO = ? ORDER BY DESC_AUXILIAR";
            $params = [(int)$estado];
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        } else {
            $sql .= " ORDER BY DESC_AUXILIAR";
            $stmt = sqlsrv_query($this->cid_central, $sql);
        }

        try {
            $rows = [];
            while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $rows[] = $v;
            }
            return $rows;
        } catch (\Throwable $th) {
            print_r($th);
            return [];
        }
    }

    public function insertarCentroCosto($codAuxiliar, $descAuxiliar, $centroCosto, $sector, $numSucursal) {

        $descAuxiliar = substr($descAuxiliar, 0, 25);

        $sqlCheck = "SELECT COUNT(*) AS CNT FROM RO_T_CENTRO_DE_COSTOS WHERE COD_AUXILIAR = ?";
        $stmtCheck = sqlsrv_query($this->cid_central, $sqlCheck, [$codAuxiliar]);
        if ($stmtCheck === false) { return 0; }
        $rowCheck = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
        if ((int)$rowCheck['CNT'] > 0) { return 0; }

        $sql = "INSERT INTO RO_T_CENTRO_DE_COSTOS (COD_AUXILIAR, DESC_AUXILIAR, CENTRO_COSTO, SECTOR, NUM_SUCURSAL, ACTIVO)
                VALUES (?, ?, ?, ?, ?, 1)";
        $params = [$codAuxiliar, $descAuxiliar, $centroCosto, $sector, (int)$numSucursal];

        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        if ($stmt === false) { return 0; }

        return sqlsrv_rows_affected($stmt);
    }

    public function cambiarEstadoCentroCosto($codAuxiliar, $activo) {

        $sql = "UPDATE RO_T_CENTRO_DE_COSTOS SET ACTIVO = ? WHERE COD_AUXILIAR = ?";
        $params = [(int)$activo, $codAuxiliar];

        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        if ($stmt === false) { return 0; }

        return sqlsrv_rows_affected($stmt);
    }
}

if (isset($_GET['codigo'])) {
    $centroCosto = new CentroCosto();
    $centroCosto->traerDescripcion($_GET['codigo']);
}
