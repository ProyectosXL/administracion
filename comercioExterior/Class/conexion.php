
<?php

class Conexion{
    private $servidor = 'servidor';
    private $database = 'LAKER_SA';
    private $user = 'sa';
    private $pass = 'Axoft1988';
    private $character = 'UTF-8';

    public function conectar(){
        try {
            $conexion_central = array( "Database"=>$this->database, "UID"=>$this->user, "PWD"=>$this->pass, "CharacterSet" => $this->character);
            $cid_central = sqlsrv_connect($this->servidor, $conexion_central);

            return $cid_central;
             
         } catch (PDOException $e){
                 echo $e->getMessage();
         }
    }
}