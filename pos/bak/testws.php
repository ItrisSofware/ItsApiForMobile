<?php
header("Access-Control-Allow-Origin: *");

function GrabarTXT($String,$name){
    $now = date('Ymd-H-i-s');
    $fpt = fopen($name.".log", "a");
    fwrite($fpt, $String. PHP_EOL);
    fclose($fpt);
}

function folderCreate(){
	// Estructura de la carpeta deseada
    $estructura = 'activity/';
	if(file_exists($estructura)){
    //echo "El fichero $nombre_fichero existe";
	}else{
		//echo "El fichero $nombre_fichero no existe";
		if(!mkdir($estructura, 0777, true)) {
			die('Fallo al crear las carpetas...');
		}
	}	
	
}


 function nowww($text) {
 $word = array(
 "http://" => "",
 "www." => "",
 );
 foreach ($word as $bad => $good) {
 $text = str_replace($bad, $good, $text);
 }
 folderCreate();
 $oldurl = explode("/", $text);
 $newurl = $oldurl[0];
 $text = "$newurl";
 $text = strip_tags(addslashes($text));
 return $text;
 }
//url que queremos saber si esta up o down
// $url = "http://dsystem.org";
$url = $_GET["ws"];
GrabarTXT($url,'URL');

 $site = nowww("$url"); 
 $check = @fsockopen($site, 80); 

 if ($check){ 
	//echo "la pagina $site esta online";
	echo json_encode(array("valor"=>1, "TimeOut"=>"oK:".$url) ); 
 }else{ 
	//echo "la pagina $site esta caida";
	echo json_encode(array("valor"=>2
	, "TimeOut"=>"nO:".$url) );	
 }
?>