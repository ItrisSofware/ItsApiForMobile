<?php
ini_set('max_execution_time', 99000);
ini_set('memory_limit', '-1');
ini_set('max_input_vars','-1');

//Función para ser usada para grabar los archivos que vayan entregando los métodos de Itris.
function GrabarXML($XMLData,$nombre){
    $now = date('Ymd-H-i-s');
    $fp = fopen($nombre.$now.".xml", "a");
    fwrite($fp, $XMLData. PHP_EOL);
    fclose($fp);
}

//Función para ser usada para grabar los archivos que vayan entregando los métodos de Itris.
function GrabarTXT($String,$name){
    $now = date('Ymd-H-i-s');
    $fpt = fopen($name.".log", "a");
    fwrite($fpt, $String. PHP_EOL);
    fclose($fpt);
}

header("Access-Control-Allow-Origin: *");
date_default_timezone_set("America/Argentina/Buenos_Aires");
$ItsGetDate = date("Y/m/d H:i:s");
require_once('lib/nusoap.php');

//Datos de Conexión del WS
$ws = $_GET["ws"];
$db = $_GET["db"];
$user = $_GET["user"];
$pass = $_GET["pass"];

$err=$_GET["errores"];

/*
$ws= "http://itris.no-ip.com:3000/ITSWS/ItsCliSvrWS.asmx?WSDL";
$db="TOMA_INVENTARIO";
$user="lcondori";
$pass="123";
$err='ssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssss';
*/

$client = new nusoap_client($ws,true);
$sError = $client->getError();
if ($sError) {
    echo json_encode(array("valor"=>1, "msn"=>"No se pudo conectar al WebService indicado."));
    $texto = $ItsGetDate.' - '.$ws.' | No se pudo conectar al WebService indicado';
    GrabarTXT($texto,"ItsExceptions");
}else{
    $login = $client->call('ItsLogin', array('DBName' => $db, 'UserName' => $user, 'UserPwd' => $pass, 'LicType'=>'WS') );
    $error = $login['ItsLoginResult'];
    $session = $login['UserSession'];
    if($error){
        $LastErro = $client->call('ItsGetLastError', array('UserSession' => $session) );
        $err = utf8_encode($LastErro['Error']);
        echo json_encode(array("valor"=>$error, "msn"=>'Linea 47: '.$err));
        $texto = $ItsGetDate.' - '.$ws.' | '.$user.' | '.$err;
        GrabarTXT($texto,"ItsExceptions");
    }else {
        $param = $client->call('ItsGetData', array('UserSession' => $session, 'ItsClassName' => 'ERP_PARAMETROS', 'RecordCount' => '1', 'SQLFilter'=>'', 'SQLSort'=>'') );
        $ItsGetDataResult = $param["ItsGetDataResult"];
        $XMLData = $param["XMLData"];

        if($ItsGetDataResult){
            $LastErro = $client->call('ItsGetLastError', array('UserSession' => $session) );
            $err = utf8_encode($LastErro['Error']);
            echo json_encode(array("valor"=>1, "msn"=>'Linea 57: '.$err));
            $texto = $ItsGetDate.' - '.$ws.' | '.$user.' | '.$err;
            GrabarTXT($texto,"ItsExceptions");

        }else{
            //barXML($XMLData, 'emailparam');
            $parametros=simplexml_load_string($XMLData);
            $array = json_decode( json_encode($parametros) , 1);
            //Ahora comienzo a recorrer el XML para mostrar los atributos por pantalla.
            $langs = $array['ROWDATA']['ROW'];
            $count = sizeof($langs);
            if($count==''){$counts=0;}else{$counts=$count;}

            for ($i=0; $i<sizeof($langs); $i++){
                if($count == 1){

                    $mail =$langs['@attributes']['MAIL_ADM_DEPO'];
                }else{
                    $mail = $langs[$i]['@attributes']['MAIL_ADM_DEPO'];
                }
            }

            $destinatario = $mail;
            //$destinatario = 'lcondori@gmail.com';
            $asunto = "Actividad Aplicación Inventario";
            $cuerpo = '
                        <html>
                        <head>
                           <title>Actividad de la aplicación</title>
                        </head>
                        <body>
                        <h1>Estimado '.$mail.'</h1>
                        <p>
                        <b>Estás recibiendo este correo porque estás como responsable de depósito en los parámetros de Itris Software</b>. Queremos notificarte que el usuario '.$user.' ha suprimido de la aplicación de inventario la siguiente información.
                        </p>
                         <p>'.$err.'</p>
                        </body>
                        </html>
                        ';

//para el envío en formato HTML
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";

//dirección del remitente
            $headers .= "From: Itris APP Inventario <app.no-reply@itris.com.ar>\r\n";

//dirección de respuesta, si queremos que sea distinta que la del remitente
            //$headers .= "Reply-To: mariano@desarrolloweb.com\r\n";

//ruta del mensaje desde origen a destino
            //$headers .= "Return-path: holahola@desarrolloweb.com\r\n";

//direcciones que recibián copia
            //$headers .= "Cc: lcondori@gmail.com\r\n";

//direcciones que recibirán copia oculta
            //$headers .= "Bcc: pepe@pepe.com,juan@juan.com\r\n";

            if(mail($destinatario,$asunto,$cuerpo,$headers)){
                echo json_encode(array("valor"=>0, "msn"=>"El mensaje fue enviado con exito","ItsGetDate"=>$ItsGetDate));
                $texto = $ItsGetDate.' - '.$ws.' | '.$user.' El mensaje fue enviado con exito.';
                GrabarTXT($texto,"ItsClientAsync");
            }else{
                echo json_encode(array("valor"=>1, "msn"=>"Existio un problema al enviar mail al responsable de deposito.","ItsGetDate"=>$ItsGetDate));
                $texto = $ItsGetDate.' - '.$ws.' | '.$user.' | Existio un problema al enviar mail al responsable de deposito.';
                GrabarTXT($texto,"ItsExceptions");
            }
            //Cierro sesión
            $LogOut = $client->call('ItsLogout', array('UserSession' => $session) );
            $ItsLogOutResult=$LogOut
            ["ItsLogoutResult"];
            if($ItsLogOutResult){
                $LastErro = $client->call('ItsGetLastError', array('UserSession' => $session) );
                $err = utf8_encode($LastErro['Error']);
                $texto = $ItsGetDate.' - '.$ws.' | '.$user.' | '.$err;
                GrabarTXT($texto,"ItsExceptions");
            }else{
                $texto = $ItsGetDate.' - '.$ws.' | '.$user.' Cerraste sesión con éxito';
                GrabarTXT($texto,"ItsClientAsync");
            }
        }

    }
}

?>