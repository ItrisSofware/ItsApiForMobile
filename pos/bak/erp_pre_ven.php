<?php
header("Access-Control-Allow-Origin: *");
date_default_timezone_set("America/Argentina/Buenos_Aires");
$ItsGetDate = date("Y/m/d H:i:s");
require_once('lib/nusoap.php');

$ws = $_GET["ws"];
$bd = $_GET["base"];
$user = $_GET["usuario"];
$pass = $_GET["pass"];
$fua = $_GET["fua_pre"];

/*
$ws = "http://iserver.itris.com.ar:3000/ITSWS/ItsCliSvrWS.asmx?WSDL";
$bd = "LM_10_09_14";
$user = "administrador";
$pass = "12348";
$fua = '';
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
				}else{
					//echo json_encode(array("ItsLoginResult"=>$error, "session"=>$session));
				$empresas = $client->call('ItsGetData', array('UserSession' => $session, 'ItsClassName' => '_ERP_PRE_VEN_APP', 'RecordCount' => '-1', 'SQLFilter'=>"FEC_ULT_ACT > '".$fua."'" , 'SQLSort'=> '') );
				$ItsGetDataResult = $empresas["ItsGetDataResult"];
				$DataEmpresas = $empresas["XMLData"];

					if($ItsGetDataResult){
								$LastErro = $client->call('ItsGetLastError', array('UserSession' => $session) );
								$err = utf8_encode($LastErro['Error']);
								echo json_encode(array("ItsLoginResult"=>$ItsGetDataResult, "motivo"=>$err));
							}else{
								$erp_empresas=simplexml_load_string($DataEmpresas) or die("Error: Cannot create object");
								$array = json_decode( json_encode($erp_empresas) , 1);
								//Ahora comienzo a recorrer el XML para mostrar los atributos por pantalla.
								$langs = $array['ROWDATA']['ROW'];
								$count = sizeof($langs);
								$counti = sizeof($langs);
								if($count==''){$counts=0;} 
								for ($i=0; $i<sizeof($langs); $i++) {							
if($count == 1){
									$cadena = $langs['@attributes']['DES_ART'];//FK_ERP_LIS_PRECIO
									$des_art = str_replace("'", "", $cadena);	
$datos = array('ID'=>$langs['@attributes']['ID'],'FK_ERP_ARTICULOS'=>$langs['@attributes']['FK_ERP_ARTICULOS'],'DES_ART'=>$des_art,'FK_ERP_LIS_PRECIO'=>$langs['@attributes']['FK_ERP_LIS_PRECIO'], 'SAL_DISP'=>$langs['@attributes']['SAL_DISP'], 'PRECIO'=>$langs['@attributes']['PRECIO'] );
}else{
									$cadena = $langs[$i]['@attributes']['DES_ART'];
									$des_art = str_replace("'", "", $cadena);
$datos = array('ID'=>$langs[$i]['@attributes']['ID'],'FK_ERP_ARTICULOS'=>$langs[$i]['@attributes']['FK_ERP_ARTICULOS'],'DES_ART'=>$des_art, 'FK_ERP_LIS_PRECIO'=>$langs[$i]['@attributes']['FK_ERP_LIS_PRECIO'], 'SAL_DISP'=>$langs[$i]['@attributes']['SAL_DISP'], 'PRECIO'=>$langs[$i]['@attributes']['PRECIO'] );
}									
								$salida[] = $datos;
								}
								echo json_encode(array("ItsLoginResult"=>$ItsGetDataResult, "ItsGetDate"=>$ItsGetDate, "Cantidad"=>$counti, "Data"=>$salida));
								$LogOut = $client->call('ItsLogout', array('UserSession' => $session) );
							}
				}				
	}
?>