<?php
header("Access-Control-Allow-Origin: *");
// Desactivar toda notificación de error
error_reporting(0);

date_default_timezone_set("America/Argentina/Buenos_Aires");
$ItsGetDate = date("Y/m/d H:i:s");
require_once('lib/nusoap.php');

function folderCreateLog($String,$name,$bd,$user){
	// Estructura de la carpeta deseada
    $estructura = 'activity/'.$bd.'/'.$user.'/';

	if(file_exists($estructura)){
    //echo "El fichero $nombre_fichero existe";
			$now = date('d/m/y H:i:s');
			$fpt = fopen($estructura.$name.".log", "a");
			fwrite($fpt, $now.' | ' .$String. PHP_EOL);
			fclose($fpt);	
	}else{
		//echo "El fichero $nombre_fichero no existe";
		if(!mkdir($estructura, 0777, true)) {
			//die('Fallo al crear las carpetas...');
		}else{
			$now = date('Ymd-H-i-s');
			$fpt = fopen($estructura.$name.".log", "a");
			fwrite($fpt, $now.' | ' .$String. PHP_EOL);
			fclose($fpt);			
		}
	}	
}

if($_GET["debug"] == "true"){
	$ws = "http://iserver.itris.com.ar:3000/ITSWS/ItsCliSvrWS.asmx?WSDL";
	$bd = "LM_10_09_14";
	$user = "administrador";
	$pass = "12348";
}else{
	$ws = $_GET["ws"];
	$bd = $_GET["base"];
	$user = $_GET["usuario"];
	$pass = $_GET["pass"];
}

$client = new nusoap_client($ws,true);
	$sError = $client->getError();
	if ($sError) {
		echo json_encode(array("ItsLoginResult"=>1, "motivo"=>"No se pudo conectar al WebService indicado."));	
        folderCreateLog('No se pudo conectar al WebService indicado.','ItsExceptions',$bd,$user);
	}else{
		$login = $client->call('ItsLogin', array('DBName' => $bd, 'UserName' => $user, 'UserPwd' => $pass, 'LicType'=>'WS') );			
		$error = $login['ItsLoginResult'];
		$session = $login['UserSession'];

		if($error){
					$LastErro = $client->call('ItsGetLastError', array('UserSession' => $session) );
					$err = utf8_encode($LastErro['Error']);
					echo json_encode(array("ItsLoginResult"=>$error, "motivo"=>$err));
				    folderCreateLog($err,'ItsExceptions',$bd,$user);
				}else{
					echo json_encode(array("ItsLoginResult"=>$error, "session"=>$session));
					folderCreateLog("ItsLoginResult:".$error.", session:".$session,LogActivity,$bd,$user);
					$LogOut = $client->call('ItsLogout', array('UserSession' => $session) );
				}				
	}	
?>