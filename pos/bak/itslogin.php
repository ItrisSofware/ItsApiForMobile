<?php
header("Access-Control-Allow-Origin: *");

require_once('lib/nusoap.php');

$ws = $_GET["ws"];
$bd = $_GET["base"];
$user = $_GET["usuario"];
$pass = $_GET["pass"];
/*
echo $ws = "http://200.55.245.171:3000/ITSWS/ItsCliSvrWS.asmx?WSDL";
echo $bd = "LM_10_09_14";
echo $user = "administrador";
echo $pass = "12348";
*/
$client = new nusoap_client($ws,true);
	$sError = $client->getError();
	if ($sError) {
		echo json_encode(array("ItsLoginResult"=>1, "motivo"=>"No se pudo conectar al WebService indicado."));	
		//echo '<span class="label label-danger"> No se pudo realizar la conexión '.$sError.'</span>';
	}else{
		$login = $client->call('ItsLogin', array('DBName' => $bd, 'UserName' => $user, 'UserPwd' => $pass, 'LicType'=>'WS') );			
		$error = $login['ItsLoginResult'];
		$session = $login['UserSession'];

		if($error){
					$LastErro = $client->call('ItsGetLastError', array('UserSession' => $session) );
					$err = utf8_encode($LastErro['Error']);
					echo json_encode(array("ItsLoginResult"=>$error, "motivo"=>$err));
				  //echo json_encode(array("valor"=>$value, "TimeOut"=>"oK:".$url) );
				}else{
					echo json_encode(array("ItsLoginResult"=>$error, "session"=>$session));
					$LogOut = $client->call('ItsLogout', array('UserSession' => $session) );
				}				
	}	
?>