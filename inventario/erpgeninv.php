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

if(isset($_GET["debug"])){
	$ws = "http://itris.no-ip.com:3000/ITSWS/ItsCliSvrWS.asmx?WSDL";
	$bd = "TOMA_INVENTARIO";
	$user = "LCONDORI";
	$pass = "123";
	$fua = '';
}else{
	$ws = $_GET["ws"];
	$bd = $_GET["base"];
	$user = $_GET["usuario"];
	$pass = $_GET["pass"];
	$fua = $_GET["fua_pre"];
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
				$empresas = $client->call('ItsGetData', array('UserSession' => $session, 'ItsClassName' => 'ERP_GEN_INV', 'RecordCount' => '-1', 'SQLFilter'=>'' , 'SQLSort'=> '') );
				$ItsGetDataResult = $empresas["ItsGetDataResult"];
				$DataEmpresas = $empresas["XMLData"];

					if($ItsGetDataResult){
								$LastErro = $client->call('ItsGetLastError', array('UserSession' => $session) );
								$err = utf8_encode($LastErro['Error']);
								echo json_encode(array("ItsLoginResult"=>$ItsGetDataResult, "motivo"=>$err));
								folderCreateLog($err,'ItsExceptions',$bd,$user);
							}else{
								$erp_empresas=simplexml_load_string($DataEmpresas) or die("Error: No se puede leer el objeto XML");
								$array = json_decode( json_encode($erp_empresas) , 1);
								folderCreateLog($err,'ItsExceptions',$bd,$user);
								$langs = $array['ROWDATA']['ROW'];
								$count = sizeof($langs);
								if($count==''){$counts=0;}else{$counts=$count;} 
								for ($i=0; $i<sizeof($langs); $i++) {							
if($count == 1){
				$cadena = $langs['@attributes']['Z_FK_ERP_DEPOSITOS'];
				$des_dep = str_replace("'", "", $cadena);	
$datos = array('FECHA'=>$langs['@attributes']['FECHA'],'FK_ERP_DEPOSITOS'=>$langs['@attributes']['FK_ERP_DEPOSITOS'],'DES_DEP'=>$des_dep );
}else{
									$cadena = $langs[$i]['@attributes']['Z_FK_ERP_DEPOSITOS'];
									$des_dep = str_replace("'", "", $cadena);
$datos = array('FECHA'=>$langs[$i]['@attributes']['FECHA'],'FK_ERP_DEPOSITOS'=>$langs[$i]['@attributes']['FK_ERP_DEPOSITOS'],'DES_DEP'=>$des_dep );
}									
								$salida[] = $datos;
								}
								echo json_encode(array("ItsLoginResult"=>$ItsGetDataResult, "ItsGetDate"=>$ItsGetDate, "Cantidad"=>$counts, "Data"=>$salida));
								folderCreateLog("ItsLoginResult: ".$ItsGetDataResult.", ItsGetDate: ".$ItsGetDate.", Cantidad: ".$counts.", Data: ".$salida,'ItsExceptions',$bd,$user);
								$LogOut = $client->call('ItsLogout', array('UserSession' => $session) );
							}
				}				
	}
?>