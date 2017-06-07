<?php
header("Access-Control-Allow-Origin: *");
// Desactivar toda notificación de error
error_reporting(0);

date_default_timezone_set("America/Argentina/Buenos_Aires");
$ItsGetDate = date("Y/m/d H:i:s");
require_once('lib/nusoap.php');
require 'PHPMailerAutoload.php';

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
    if(!isset($_GET["imeil"])){
        echo json_encode(array("DebugModeOn"=>1, "motivo"=>"No definiste o la variable 'imeil' esta vacia"));
		$bd='';
		$user='';
        folderCreateLog('No definiste o la variable "imeil" está vacia.','ItsExceptions',$bd,$user);
        exit;
    }
    $ws = "http://iserver,itris.com.ar:3000/ITSWS/ItsCliSvrWS.asmx?WSDL";
    $bd = "LM_10_09_14";
    $user = "administrador";
    $pass = "12348";
    $datos = '[{"Datos":{"id":177,"articulo":"0000013","deposito":"1","cantidad":"5","fecha":20151209}},{"Datos":{"id":2,"articulo":"0000013","deposito":"1","cantidad":"8","fecha":20151209}},{"Datos":{"id":3,"articulo":"0000013","deposito":"1","cantidad":"6","fecha":20151209}},{"Datos":{"id":4,"articulo":"0000013","deposito":"1","cantidad":"null","fecha":20151209}},{"Datos":{"id":5,"articulo":"0000013","deposito":"1","cantidad":"null","fecha":20151209}},{"Datos":{"id":6,"articulo":"0000013","deposito":"1","cantidad":"null","fecha":20151209}},{"Datos":{"id":7,"articulo":"0000013","deposito":"1","cantidad":"null","fecha":20151209}},{"Datos":{"id":8,"articulo":"0000013","deposito":"1","cantidad":"null","fecha":20151209}},{"Datos":{"id":9,"articulo":"0000013","deposito":"1","cantidad":"9","fecha":20151209}},{"Datos":{"id":10,"articulo":"0000013","deposito":"1","cantidad":"6","fecha":20151209}},{"Datos":{"id":11,"articulo":"0000013","deposito":"1","cantidad":"null","fecha":20151209}},{"Datos":{"id":12,"articulo":"0000013","deposito":"1","cantidad":"8","fecha":20151209}},{"Datos":{"id":13,"articulo":"0000013","deposito":"1","cantidad":"77","fecha":20151209}},{"Datos":{"id":14,"articulo":"0000013","deposito":"1","cantidad":"88","fecha":20151209}},{"Datos":{"id":15,"articulo":"0002222","deposito":"1","cantidad":"1","fecha":20151209}},{"Datos":{"id":16,"articulo":"0002222","deposito":"1","cantidad":"1","fecha":20151209}},{"Datos":{"id":17,"articulo":"0022200222","deposito":"1","cantidad":"1","fecha":20151209}},{"Datos":{"id":18,"articulo":"0022200222","deposito":"1","cantidad":"1","fecha":20151209}},{"Datos":{"id":19,"articulo":"01050100","deposito":"1","cantidad":"33","fecha":20151209}},{"Datos":{"id":20,"articulo":"01310340","deposito":"1","cantidad":"null","fecha":20151209}},{"Datos":{"id":21,"articulo":"01310340","deposito":"1","cantidad":"null","fecha":20151209}},{"Datos":{"id":22,"articulo":"01310340","deposito":"1","cantidad":"55","fecha":20151209}},{"Datos":{"id":23,"articulo":"01310340","deposito":"1","cantidad":"","fecha":20151209}},{"Datos":{"id":24,"articulo":"01310340","deposito":"1","cantidad":"","fecha":20151209}},{"Datos":{"id":25,"articulo":"010540010400001","deposito":"1","cantidad":"","fecha":20151209}},{"Datos":{"id":26,"articulo":"0000661","deposito":"1","cantidad":"","fecha":20151209}},{"Datos":{"id":207,"articulo":"0000661","deposito":"1","cantidad":"33","fecha":20151209}},{"Datos":{"id":2898,"articulo":"0000014","deposito":"1","cantidad":"77","fecha":20151209}},{"Datos":{"id":299,"articulo":"0000661","deposito":"1","cantidad":"444","fecha":20151209}}]';
    $imeil = $_GET["imeil"];
}else{
    $ws = $_GET["ws"];
    $bd = $_GET["base"];
    $user = $_GET["usuario"];
    $pass = $_GET["pass"];
    $datos = $_GET["datos"];
    $imeil = $_GET["imeil"];
	folderCreateLog($ws.' - '.$bd.' - '.$user.' - '.$pass.' - '.$datos.' - '.$imeil,'modoincognito',$bd,$user);
}

$id = '';
$todo='';
$iderr='';
$todOK='';

    
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
	folderCreateLog($mail->ErrorInfo,'ItsExceptions',$bd,$user);
	echo json_encode(array("ItsLoginResult"=>1, "motivo"=>$mail->ErrorInfo.' ¡Volvé a intentarlo!'));
} else {
    //echo 'Mensaje enviado con éxito';
	folderCreateLog('El correo fue enviado de manera existosa a '.$imeil,'LogActivity',$bd,$user);
	echo json_encode(array("ItsLoginResult"=>0, "motivo"=>'¡Email fue enviado con éxito!'));
}

?>