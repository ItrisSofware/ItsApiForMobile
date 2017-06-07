			<?php
			error_reporting(E_ERROR);
			$myport = $_POST["puerto"];
			
			if($myport == ""){
				$myport = '<span class="glyphicon glyphicon-ban-circle" aria-hidden="true"></span>';
			}else{
				$myport;
			}
			// Si quieres añadir alguna maquina cambia el NumServ al numero de servidores i añade bajo $Maquines[numerosiguiente]="IPMAQUINANUEVA";
			$NumServ=1;
			$Maquines[1]="200.55.245.171";
			 
			// Idem si quieres añadir algun puerto a provar
			$NumPorts=3;
			$Ports[1]=6100;
			$Ports[2]=6101;
			$Ports[3]=$myport;
			echo '
			<div class="btn-group" role="group" aria-label="...">
			';
			for ($k=1 ; $k<$NumPorts+1 ; $k++) {
				print('<button type="button" class="btn btn-default">'.$Ports[$k].'</button>');
			}
			echo '</div>';

			for ($i=1 ; $i<$NumServ+1 ; $i++){
			   for($j=1 ; $j<$NumPorts+1 ; $j++) {
				   $fs=fsockopen($Maquines[$i],$Ports[$j],$errno,$errstr,5);
				   echo '<br>';
					if (!$fs) 
							{
								echo('<span class="glyphicon glyphicon-remove" aria-hidden="true"></span> <span class="label label-danger">Sin conexión '.$Ports[$j].'</span>'); 
							}else{
								echo ('<span class="glyphicon glyphicon-ok" aria-hidden="true"></span> <span class="label label-success"> Conexión establecida '.$Ports[$j].'</span>');
							}	
			   }
			}
			?>