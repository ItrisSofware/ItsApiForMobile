<?php
header("Access-Control-Allow-Origin: *");
date_default_timezone_set("America/Argentina/Buenos_Aires");
$ItsGetDate = date("Y/m/d H:i:s");

error_reporting(0);
ini_set('max_execution_time', 99000);
ini_set('memory_limit', '-1');
ini_set('max_input_vars','-1');

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
	$fua = $_GET["fua_cliente"];
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
		if($error<>0){
					$LastErro = $client->call('ItsGetLastError', array('UserSession' => $session) );
					 $err = utf8_encode($LastErro['Error']);
					echo json_encode(array("ItsLoginResult"=>$error, "motivo"=>$err));
					folderCreateLog($err,'ItsExceptions',$bd,$user);
				}else{
				$empresas = $client->call('ItsGetData', array('UserSession' => $session, 'ItsClassName' => 'ERP_ARTICULOS_APP', 'RecordCount' => '-1', 'SQLFilter'=>"FEC_ULT_ACT >= '".$fua."' ", 'SQLSort'=> '') );

				$ItsGetDataResult = $empresas["ItsGetDataResult"];
				$DataEmpresas = $empresas["XMLData"];
				$file = fopen("ERP_ARTICULOS_APP.xml", "a");
				fwrite($file, $DataEmpresas . PHP_EOL);
				fclose($file);
					if($ItsGetDataResult<>0){
								$LastErro = $client->call('ItsGetLastError', array('UserSession' => $session) );
								$err = utf8_encode($LastErro['Error']);
								echo json_encode(array("ItsGetDataResult"=>$ItsGetDataResult, "motivo"=>$err));
								folderCreateLog($err,'ItsExceptions',$bd,$user);
							}else{
								$erp_empresas=simplexml_load_string($DataEmpresas) or die("Error: No puedo crear objeto de articulos.");
								$array = json_decode( json_encode($erp_empresas) , 1);
								//Ahora comienzo a recorrer el XML para mostrar los atributos por pantalla.
								$langs = $array['ROWDATA']['ROW'];
								$count = sizeof($langs);
								if($count==''){$counts=0;}else{$counts=$count;}

					for ($i=0; $i<sizeof($langs); $i++){
							if($count == 1){
								$des_art = str_replace("'", "", $langs['@attributes']['DESCRIPCION']);	
								$datos = array('ID'=>$langs['@attributes']['ID'],'DESCRIPCION'=>$des_art,'COD_BARRA'=>$langs['@attributes']['COD_BARRA']);
							}else{
								$des_art = str_replace("'", "", $langs[$i]['@attributes']['DESCRIPCION']);	
								$datos = array('ID'=>$langs[$i]['@attributes']['ID'],'DESCRIPCION'=>$des_art,'COD_BARRA'=>$langs[$i]['@attributes']['COD_BARRA']);
							}									
									$salida[] = $datos;
								}
								echo json_encode(array("ItsLoginResult"=>$ItsGetDataResult, "ItsGetDate"=>$ItsGetDate, "Cantidad"=>$counts, "Data"=>$salida));
								folderCreateLog("ItsLoginResult: ".$ItsGetDataResult.", ItsGetDate: ".$ItsGetDate.", Cantidad: ".$counts.", Data: ".$salida,'LogActivity',$bd,$user);
								$LogOut = $client->call('ItsLogout', array('UserSession' => $session) );
							}
				}		
		
	}
?>