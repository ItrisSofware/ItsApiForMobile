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
        if(!isset($_GET["datos"])){
            echo json_encode(array("DebugModeOn"=>1, "motivo"=>"No definiste o la variable 'datos' esta vacia"));
			$bd='';
		    $user='';
            folderCreateLog('No definiste o la variable "datos" está vacia.','ItsExceptions',$bd,$user);
            exit;
        }
        $ws = "http://iserver.itris.com.ar:3000/ITSWS/ItsCliSvrWS.asmx?WSDL";
        $bd = "LM_10_09_14";
        $user = "administrador";
        $pass = "12348";
        $datos = $_GET["datos"];
        //$datos = '[{"Datos":{"uuid":"42","id":2,"empresa":" 14931538","articulo":" XA-01-028 ","cantidad":1,"precio":1}}]';
    }else{
        $ws = $_GET["ws"];
        $bd = $_GET["base"];
        $user = $_GET["usuario"];
        $pass = $_GET["pass"];
        $datos = $_GET["datos"];
    }	
	
	$id = '';
	$todo='';
	$iderr='';
	$todOK='';
	$salida = '';
	$err = '';

    //Lo decodifico y obtengo un array.
    $String = json_decode($datos);
    
    //Cuento los resultados, pero por ahora no hago nada. solo guardo en la variable $resultados para pasarlo por el JSON al cliente, pero nada m·s.
    $resultados = count($String);

//Me conecto al WSDL
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
				}else{//Login realizado con total éxito.
                    //echo json_encode(array("ItsLoginResult"=>0, "datos"=>$datos, "Cantidad"=>$resultados));                
                    for ($i = 0; $i < count($String); $i++) {
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
                                
                                //MODIFICO la variable y le quito los espacio.
                                $uuid = trim($String[$i]->Datos->uuid);
                                $id = trim($String[$i]->Datos->id);
                                $empresa = trim($String[$i]->Datos->empresa);
                                $articulos = trim($String[$i]->Datos->articulo);
                                $cantidad = trim($String[$i]->Datos->cantidad);
                                $precio = trim($String[$i]->Datos->precio);
                                //FIN LE QUITO LO ESPACIOS.

                                $ITS->ROWDATA->ROW['_ID_APP']=$id;
                                $ITS->ROWDATA->ROW['_UUID']=$uuid;
                                $ITS->ROWDATA->ROW['FK_ERP_EMPRESAS']=$empresa;
                                $ITS->ROWDATA->ROW['FK_ERP_ARTICULOS']=$articulos;
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
                                        echo json_encode(array("ItsLoginResult"=>$ItsPostResult, "motivo" => $err, "idinsertado"=>$id));
                                        folderCreateLog($err,'ItsExceptions',$bd,$user);
                                        
                                        $todo.= $ItsGetDate." WS: ".$ws." | Usuario: ".$user." | Articulo: ".$String[$i]->Datos->articulo." Cantidad:".$String[$i]->Datos->cantidad." Motivo:".$err. PHP_EOL;
                                        $iderr.='{"idError":{"id":"'.$String[$i]->Datos->articulo.'","motivo":"'.$err.'}}';
                                        $ArrayError=array('ID'=>$String[$i]->Datos->id,'Motivo'=>$err);
                                        $salida[] = $ArrayError;
                                    }else{
                                        $ItsPostResultaXML = $ItsPost["XMLData"];
                                        $todOK.= $ItsGetDate." WS: ".$ws." | Usuario: ".$user." | Articulo: ".$String[$i]->Datos->articulo." Cantidad:".$String[$i]->Datos->cantidad. PHP_EOL;
                                        echo json_encode(array("ItsLoginResult"=>$ItsPostResult, "Cantidad"=>$resultados));
                                        folderCreateLog("ItsLoginResult:".$ItsPostResult.", Cantidad:".$resultados,'LogActivity',$bd,$user);	
                                    }							
                                }						
                            }						
                        }//Fin del bucle FOR
						$ItsLogOut = $client->call('ItsLogout', array('UserSession' => $session));						
                    }//Fin del ELSE del LoginResult = 0
    }//Fin del ELSE conexión al WS con total éxito.

?>