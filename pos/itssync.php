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
	//if(isset($_GET["debug"])){
	if(isset($_GET["debug"])){
			if(!isset($_GET["articulo"])){
				echo json_encode(array("DebugModeOn"=>1, "motivo"=>"No definiste o la variable 'articulo' esta vacia"));
				$bd='';
				$user='';
				folderCreateLog('No definiste o la variable "articulo" está vacia.','ItsExceptions',$bd,$user);
				exit;
			}	
		$ws = "http://iserver.itris.com.ar:3000/ITSWS/ItsCliSvrWS.asmx?WSDL";
		$bd = "LM_10_09_14";
		$user = "administrador";
		$pass = "12348";
		$id= '987';
		$empresa = '000001178';
		$articulo = $_GET["articulo"];
		$precio = '678';
		$cantidad = '34';
	}else{
		$ws = $_GET["ws"];
		$bd = $_GET["base"];
		$user = $_GET["usuario"];
		$pass = $_GET["pass"];
		$id = $_GET["id"];
		$empresa = $_GET["empresa"];
		$articulo = $_GET["articulo"];
		$precio = $_GET["precio"];
		$cantidad = $_GET["cantidad"];
}

//Le quieto los espacios.
$id = str_replace(' ','',$id);  
$empresa = str_replace(' ','',$empresa);  
$articulo = str_replace(' ','',$articulo);
$precio = str_replace(' ','',$precio);  

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
					echo json_encode(array("ItsLoginResult"=>$error, "motivo"=>'ItsLoginResult dice: '.$err));
					folderCreateLog($err,'ItsExceptions',$bd,$user);
				}else{
					$mig = $client->call('ItsPrepareAppend', array('UserSession' => $session, 'ItsClassName' => 'ERP_MIG_PED') );
					$ItsPrepareAppendResult = $mig["ItsPrepareAppendResult"];
					if($ItsPrepareAppendResult){
						$LastErro = $client->call('ItsGetLastError', array('UserSession' => $session) );
						$err = utf8_encode($LastErro['Error']);
						echo json_encode(array("ItsLoginResult"=>$error, "motivo"=>'ItsPrepareAppendResult dice:'.$err));
						folderCreateLog($err,'ItsExceptions',$bd,$user);
					}else{
						$XMLData = $mig["XMLData"];
						$DataSession = $mig["DataSession"];
						
						$ITS = new SimpleXMLElement($XMLData);
						//Modifico la variable.
						$ITS->ROWDATA->ROW['FK_ERP_EMPRESAS']=$empresa;
						$ITS->ROWDATA->ROW['FK_ERP_ARTICULOS']=$articulo;
						$ITS->ROWDATA->ROW['CANTIDAD']=$cantidad;
						$ITS->ROWDATA->ROW['PRE_LIS']=$precio;						
						//Lo grabo y lo asigno en una variable.
						$iXMLData = $ITS->asXML();
						
						//Ahora envío las modificaciones correspondientes. Usando el Método ItsSetData, que recibe 3 (tres) parámetros.
						$ItsSetData	 = $client->call('ItsSetData', array('UserSession' => $session, 'DataSession' => $DataSession, 'iXMLData' => $iXMLData) );
						//Devuelve dos variables
						//A.ItsSetDataResult: Si devuelve 0 (cero) es que fue todo correcto, todo lo que sea distinto de cero indica un error.
						$ItsSetDataResult = $ItsSetData["ItsSetDataResult"];
						//Ahora pregunto si el ItsSetData se ejecutó correctamente.
						//Controlo si el resultado es distinto de cero.
						if($ItsSetDataResult <> 0){
							$LastErro = $client->call('ItsGetLastError', array('UserSession' => $session) );
							$err = utf8_encode($LastErro['Error']);							
							echo json_encode(array("ItsLoginResult"=>$ItsSetDataResult, "motivo"=>'ItsSetDataResult dice:'.$err));
							folderCreateLog($err,'ItsExceptions',$bd,$user);	
						}else{
							$oXMLData = $ItsSetData["oXMLData"];
							//Ahora acepto los cambios, y guardo el pedido.
							$ItsPost = $client->call('ItsPost', array('UserSession' => $session, 'DataSession' => $DataSession) );
							$ItsPostResult = $ItsPost["ItsPostResult"];
							if($ItsPostResult <> 0){
								$LastErro = $client->call('ItsGetLastError', array('UserSession' => $session) );
								$err = utf8_encode($LastErro['Error']);							
								echo json_encode(array("ItsLoginResult"=>$ItsPostResult, "motivo"=>'ItsPostResult dice: '.$err));
								folderCreateLog($err,'ItsExceptions',$bd,$user);								
							}else{
								$ItsPostResultaXML = $ItsPost["XMLData"];
								echo json_encode(array("ItsLoginResult"=>$ItsPostResult, "id"=>$id));
								folderCreateLog("ItsLoginResult:".$ItsPostResult.", id:".$id,'LogActivity',$bd,$user);	
							}							
						}					
					}
				}
	}
?>