<?php
header("Access-Control-Allow-Origin: *");
$url = $_GET["ws"];
function checkURL($url) {
     // Primero, compruebo si la URL es una URL valida
     if(!filter_var($url, FILTER_VALIDATE_URL)) {
          return 0;
     }
     // Inicializo y configuro una peticion con CURL
     $curlInit = curl_init($url);
     // Limito el tiempo de espera en 5 segundos y configuro las opciones
     curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT, 5); 
     curl_setopt($curlInit, CURLOPT_HEADER, true);
     curl_setopt($curlInit, CURLOPT_NOBODY, true);
     curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);
     // Obtengo una respuesta
     $response = curl_exec($curlInit);
     curl_close($curlInit);
     if ($response) return 1;
     return 0;
}
$url = $_GET["ws"];
$value = checkURL($url);
if($value == 1){
	echo json_encode(array("valor"=>$value, "TimeOut"=>"oK:".$url) );
}else{
	echo json_encode(array("valor"=>$value, "TimeOut"=>"nO:".$url) );
}
/*if ($_GET["ws"]!="ES"){
   echo json_encode(array("tieneiva"=>"0", "preciofinal"=>$_GET["precio"]));
}else{
   echo json_encode(array("tieneiva"=>"1", "preciofinal"=>($_GET["precio"] * (18 / 100)) + $_GET["precio"]));
}
*/
?>