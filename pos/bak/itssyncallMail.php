<?php
header("Access-Control-Allow-Origin: *");
require 'PHPMailerAutoload.php';
date_default_timezone_set("America/Argentina/Buenos_Aires");
$ItsGetDate = date("Y/m/d H:i:s");
require_once('lib/nusoap.php');


//Función para ser usada para grabar los archivos que vayan entregando los m�todos de Itris.
function GrabarXML($XMLData,$nombre){
    $now = date('Ymd-H-i-s');
    $fp = fopen($nombre.$now.".xml", "a");
    fwrite($fp, $XMLData. PHP_EOL);
    fclose($fp);
}

//Función para ser usada para grabar los archivos que vayan entregando los m�todos de Itris.
function GrabarTXT($String,$name){
    $now = date('Ymd-H-i-s');
    $fpt = fopen($name.".log", "a");
    fwrite($fpt, $String. PHP_EOL);
    fclose($fpt);
}

//Función para ser usada para grabar los archivos que vayan entregando los m�todos de Itris.
function GrabarJson($String,$name){
    $now = date('Ymd-H-i-s');
    $fpt = fopen($name.".json", "a");
    fwrite($fpt, $String. PHP_EOL);
    fclose($fpt);
}

$ws = $_GET["ws"];
$bd = $_GET["base"];
$user = $_GET["usuario"];
$pass = $_GET["pass"];
$datos = $_GET["datos"];
$imeil = $_GET["imeil"];
$id = '';
$todo='';
$iderr='';
$todOK='';

/*
$ws = "http://iserver,itris.com.ar:3000/ITSWS/ItsCliSvrWS.asmx?WSDL";
$bd = "LM_10_09_14";
$user = "administrador";
$pass = "12348";
$datos = '[{"Datos":{"id":177,"articulo":"0000013","deposito":"1","cantidad":"5","fecha":20151209}},{"Datos":{"id":2,"articulo":"0000013","deposito":"1","cantidad":"8","fecha":20151209}},{"Datos":{"id":3,"articulo":"0000013","deposito":"1","cantidad":"6","fecha":20151209}},{"Datos":{"id":4,"articulo":"0000013","deposito":"1","cantidad":"null","fecha":20151209}},{"Datos":{"id":5,"articulo":"0000013","deposito":"1","cantidad":"null","fecha":20151209}},{"Datos":{"id":6,"articulo":"0000013","deposito":"1","cantidad":"null","fecha":20151209}},{"Datos":{"id":7,"articulo":"0000013","deposito":"1","cantidad":"null","fecha":20151209}},{"Datos":{"id":8,"articulo":"0000013","deposito":"1","cantidad":"null","fecha":20151209}},{"Datos":{"id":9,"articulo":"0000013","deposito":"1","cantidad":"9","fecha":20151209}},{"Datos":{"id":10,"articulo":"0000013","deposito":"1","cantidad":"6","fecha":20151209}},{"Datos":{"id":11,"articulo":"0000013","deposito":"1","cantidad":"null","fecha":20151209}},{"Datos":{"id":12,"articulo":"0000013","deposito":"1","cantidad":"8","fecha":20151209}},{"Datos":{"id":13,"articulo":"0000013","deposito":"1","cantidad":"77","fecha":20151209}},{"Datos":{"id":14,"articulo":"0000013","deposito":"1","cantidad":"88","fecha":20151209}},{"Datos":{"id":15,"articulo":"0002222","deposito":"1","cantidad":"1","fecha":20151209}},{"Datos":{"id":16,"articulo":"0002222","deposito":"1","cantidad":"1","fecha":20151209}},{"Datos":{"id":17,"articulo":"0022200222","deposito":"1","cantidad":"1","fecha":20151209}},{"Datos":{"id":18,"articulo":"0022200222","deposito":"1","cantidad":"1","fecha":20151209}},{"Datos":{"id":19,"articulo":"01050100","deposito":"1","cantidad":"33","fecha":20151209}},{"Datos":{"id":20,"articulo":"01310340","deposito":"1","cantidad":"null","fecha":20151209}},{"Datos":{"id":21,"articulo":"01310340","deposito":"1","cantidad":"null","fecha":20151209}},{"Datos":{"id":22,"articulo":"01310340","deposito":"1","cantidad":"55","fecha":20151209}},{"Datos":{"id":23,"articulo":"01310340","deposito":"1","cantidad":"","fecha":20151209}},{"Datos":{"id":24,"articulo":"01310340","deposito":"1","cantidad":"","fecha":20151209}},{"Datos":{"id":25,"articulo":"010540010400001","deposito":"1","cantidad":"","fecha":20151209}},{"Datos":{"id":26,"articulo":"0000661","deposito":"1","cantidad":"","fecha":20151209}},{"Datos":{"id":207,"articulo":"0000661","deposito":"1","cantidad":"33","fecha":20151209}},{"Datos":{"id":2898,"articulo":"0000014","deposito":"1","cantidad":"77","fecha":20151209}},{"Datos":{"id":299,"articulo":"0000661","deposito":"1","cantidad":"444","fecha":20151209}}]';
$imeil = 'lcondori@itris.com.ar';*/
//http://leocondori.com.ar/app/local/itssync.php?id=777&empresa=000001178&articulo=AC-02-090&precio=4702.88

    GrabarJson($datos,'pedidos');
    //Lo decodifico y obtengo un array.
    $String = json_decode($datos);
    
    //Cuento los resultados, pero por ahora no hago nada. solo guardo en la variable $resultados para pasarlo por el JSON al cliente, pero nada m·s.
    $resultados = count($String);
 
$mail = new PHPMailer;

//$mail->SMTPDebug = 3;                               // Enable verbose debug output

$mail->isSMTP();                                      // Set mailer to use SMTP
$mail->Host = 'smtp.gmail.com';  // Specify main and backup SMTP servers
$mail->SMTPAuth = true;                               // Enable SMTP authentication
$mail->Username = 'soportecliente@itris.com.ar';                 // SMTP username
$mail->Password = 'itris123';                           // SMTP password
$mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
$mail->Port = 587;                                    // TCP port to connect to

$mail->setFrom('soportecliente@itris.com.ar', 'Pedidos Mobile');
$mail->addAddress($imeil, 'user-app');     // Add a recipient
$mail->addAddress($imeil);               // Name is optional
$mail->addReplyTo($imeil, 'No-Reply');
//$mail->addCC('leo.condori@outlook.com');
//$mail->addBCC('info@leocondori.com.ar');

//$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
//$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
$mail->isHTML(true);                                  // Set email format to HTML

$mail->Subject = 'Backup de la APP Pedidos Mobile';
$mail->Body    = '<b>'.$datos.'</b>';
$mail->AltBody = '';

if(!$mail->send()) {
    //echo 'Error al enviar correo.';
    //echo 'Motivo: ' . $mail->ErrorInfo;
	GrabarTXT($imeil.' - ' . $mail->ErrorInfo,'ItsLogError');
	echo json_encode(array("ItsLoginResult"=>1, "motivo"=>$mail->ErrorInfo.' ¡Volvé a intentarlo!'));
} else {
    //echo 'Mensaje enviado con éxito';
	GrabarTXT($imeil.' - ¡Email fue enviado con éxito!','ItsLog');
	echo json_encode(array("ItsLoginResult"=>0, "motivo"=>'¡Email fue enviado con éxito!'));
}

?>