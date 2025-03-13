<?php
require_once "Controller/emailController.php";

$mes = date('n');
$anio = date('Y');
$periodo_actual = "$mes-$anio";


iniciarEnvioAutomatico($periodo_actual);
