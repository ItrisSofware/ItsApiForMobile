<?php
header("Access-Control-Allow-Origin: *");
date_default_timezone_set("America/Argentina/Buenos_Aires");
$ItsGetDate = date("Y/m/d H:i:s");

function folderCreateLog($String,$name,$status){
	// Estructura de la carpeta deseada
    $estructura = 'activity/';
	if(file_exists($estructura)){
    //echo "El fichero $nombre_fichero existe";
			$now = date('d/m/y H:i:s');
			$fpt = fopen($estructura.$name.".log", "a");
			fwrite($fpt, $now.' | status : '.$status. ' | ' .$String. PHP_EOL);
			fclose($fpt);	
	}else{
		//echo "El fichero $nombre_fichero no existe";
		if(!mkdir($estructura, 0777, true)) {
			//die('Fallo al crear las carpetas...');
		}else{
			$now = date('Ymd-H-i-s');
			$fpt = fopen($estructura.$name.".log", "a");
			fwrite($fpt, $ItsGetDate.' | status : '.$status. ' | ' .$String. PHP_EOL);
			fclose($fpt);			
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
			
		$oldurl = explode("/", $text);
		$newurl = $oldurl[0];
		$text = "$newurl";
		$text = strip_tags(addslashes($text));
		return $text;
	 }

	if(isset($_GET["debug"])){

				$url = "http://iserver.itris.com.ar:3000/ITSWS/ItsCliSvrWS.asmx?WSDL";
			
	}else{
		$url = $_GET["ws"];
		if($url==""){
			$url = "http://www.itris.com.ar";
		}else{
			$url = $_GET["ws"];
		}
	}

 $site = nowww("$url"); 
 $check = @fsockopen($site, 80); 

 if ($check){ 
	//echo "la pagina $site esta online";
	echo json_encode(
					 array("valor"=>1, "Status"=>"200", "url"=>$url) 
					 );
	folderCreateLog($url,'url',200);				  
 }else{ 
	//echo "la pagina $site esta caida";
	echo json_encode(
					 array("valor"=>2, "Status"=>"404", "url"=>$url) 
					 );
	folderCreateLog($url,'url',404);				 	
 }
?>