<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8" />
		<title>Simple Babelweb</title>
		<style>
			table {
				font-family: monospace, sans-serif;
				border-collapse: collapse;
			}
			td, th {
				border: 1px solid #fddddd;
				text-align: left;
				padding: 1px;
				padding-right: 10px;
			}
			tr:nth-child(even) {
				background-color: #dddddd;
			}
		</style>
	</head>
	<body>
		<?php
		error_reporting(0);
		$addr = "::1";
		$port = shell_exec('grep local-port /etc/babeld.conf | cut -d" " -f 2');

		$msg = "dump\r\n";
		$sock = socket_create(AF_INET6, SOCK_STREAM, 0) or die("Cannot create socket");
		socket_connect($sock, $addr, $port) or die("Cannot connect to socket");
		$read = socket_read($sock, 1024);
		$data = explode(PHP_EOL, $read);

		# dump anfordern
		socket_write($sock, $msg, strlen($msg));

		# Daten einlesen
		$interface = array();
		$neighbour = array();
		$xroute = array();
		$route= array();

		while (1) {
			$read = socket_read($sock, 1024, PHP_NORMAL_READ);
			if (preg_match("/interface\b/", $read)) { $interface[] = $read; }
			if (preg_match("/neighbour\b/", $read)) { $neighbour[] = $read; }
			if (preg_match("/xroute\b/", $read)) { $xroute[] = $read; }
			#if (preg_match("/\broute\b/", $read)){ break 1; }
			if (preg_match("/\broute\b/", $read)){ $route[] = $read; }
 			if (preg_match("/ok/", $read)){ break 1; }
            	}
		socket_close($sock);

		$output['data'] = array(
			'name' => $data[0],
			'version' => $data[1],
			'host' => $data[2],
			'id' => $data[3],
		);
        
		foreach ($interface as $temp) {
			$tempdata = explode(" ", $temp);
			$output['interfaces'][] = array(
				'interface' => $tempdata[2],
				'up' => $tempdata[4],
				'ipv6' => $tempdata[6],
				'ipv4' => $tempdata[8],
			);
		}
		foreach ($neighbour as $temp) {
			$address=explode(" ",strstr($temp,"address"));
			$interface=explode(" ",strstr($temp,"if"));
			$reach=explode(" ",strstr($temp,"reach"));
			$rxcost=explode(" ",strstr($temp,"rxcost"));
			$txcost=explode(" ",strstr($temp,"txcost"));
			$rtt=explode(" ",strstr($temp,"rtt"));
			$rttcost=explode(" ",strstr($temp,"rttcost"));
			$cost=explode(" ",strstr($temp,"cost"));
			$output['neighbours'][] = array(
				'address' => $address[1],
				'interface' => $interface[1],
				'reach' => $reach[1],
				'rxcost' => $rxcost[1],
				'txcost' => $txcost[1],
				'rtt' => $rtt[1],
				'rttcost' => $rttcost[1],
				'cost' => $cost[1],
			);
		}

		foreach ($xroute as $temp) {
			$tempdata = explode(" ", $temp);
			$output['xroutes'][] = array(
				'prefix' => $tempdata[4],
				'metric' => $tempdata[8],
			);
		}

		foreach ($route as $temp) {
			$tempdata = explode(" ", $temp);
			$output['routes'][] = array(
				'target' => $tempdata[4],
				'installed' => $tempdata[8],
				'via' => $tempdata[16],
				'interface' => $tempdata[18],
				'metric' => $tempdata[12],
				'destid' => $tempdata[10],
			);
		}


		if($_REQUEST['format'] == 'json') { 
			echo json_encode($output); }
		else {
			# Ausgabe
			echo "<h1>Simple Babelweb</h1>";
	                ?><form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get">
				<button type="submit" name="" value="">home</button>
        	                <button type="submit" name="routes" value="1">show all babel routes</button>
                	        <button type="submit" name="v4table" value="1">show import/export table ipv4</button>
                        	<button type="submit" name="v6table" value="1">show import/export table ipv6</button>
	                </form>
			<H2>Babel information</H2><?php
			
			echo "<table>";
			foreach($output['data'] as $temp) { echo "<tr><td>$temp</td></tr>"; }
			echo "</table>";

			if($_GET['ip'] != '') {
				echo '<H2>Wege zu '.$_GET["ip"].'</H2>';
				echo '<table>
					<tr>
						<th>target</th>
						<th>installed</th>
						<th>via</th>
						<th>device</th>
						<th>metric</th>
						<th>Destination ID</th>
					</tr>';
				foreach($output['routes'] as $route) {
					if ($route['target'] == $_GET['ip']) {
						echo "<tr>";
						foreach($route as $temp) { echo "<td>$temp</td>"; }
						echo "</tr>";
					}
				}
				echo "</table>";
			}

			if (empty($_GET)) {
				echo "<H2>Interfaces</H2>";
				echo '<table>
				<tr>
					<th>Interface</th>
					<th>up</th>
					<th>ipv6</th>
					<th>ipv4</th>
					</tr>';
				foreach($output['interfaces'] as $interface) {
					echo "<tr>";
					foreach($interface as $temp) { echo "<td>$temp</td>"; }
					echo "</tr>";
				}
				echo "</table>";
				echo "<H2>Neighbours</H2>";
				echo '<table>
					<tr>
						<th>address</th>
						<th>interface</th>
						<th>reach</th>
						<th>rxcost</th>
						<th>txcost</th>
						<th>rtt</th>
						<th>rttcost</th>
						<th>cost</th>
					</tr>';
				foreach($output['neighbours'] as $neighbour) {
					echo "<tr>";
					foreach($neighbour as $temp) { echo "<td>$temp</td>"; }
					echo "</tr>";
				}
				echo "</table>";
				echo "<H2>Redistributed routes</H2>";
				echo '<table>
					<tr>
						<th>prefix</th>
						<th>metric</th>
					</tr>';
				foreach($output['xroutes'] as $xroute) {
					echo "<tr>";
					foreach($xroute as $temp) { echo "<td>$temp</td>"; }
					echo "</tr>";
				}
			        echo "</table>";
			}
			if($_GET['routes'] == '1') {
				echo "<H2>routes</H2>";
				echo '<table>
					<tr>
						<th>target</th>
						<th>installed</th>
						<th>via</th>
						<th>device</th>
						<th>metric</th>
						<th>Destination ID</th>
					</tr>';
				foreach($output['routes'] as $route) {
					$set=0;
					echo "<tr>";
					foreach($route as $temp) {
						if ($set == 0) {
							echo '<td><a href="'.$_SERVER["PHP_SELF"].'?ip='.$temp.'">'.$temp.'</a></td>';
							$set=1; 
						}
						else {
							echo "<td>$temp</td>";
						}
					}
					echo "</tr>";
				}
				echo "</table>";
			}


			if($_GET['v4table'] == '1') {
				echo "<H2>ipv4 routing table</H2>";
				echo '<table>';
				$v4routen = shell_exec('ip r s t $(grep import-table /etc/babeld.conf | cut -f2 -d" ")');
				$v4route = explode(PHP_EOL, $v4routen);
				for($i = 0; $i < count($v4route); ++$i) {
					echo "<tr>";
					$line = explode(" ", $v4route[$i]);
					for($n = 0; $n < 5; ++$n) {
						if ($n == 0) {
							echo '<td><a href="'.$_SERVER["PHP_SELF"].'?ip='.$line[$n].'">'.$line[$n].'</a></td>';
						} 
						else {
							echo '<td>'.$line[$n].'</td>';
						}
					}
					echo "</tr>";
				}	
				echo "</table>";
			}
	 
			if($_GET['v6table'] == '1') {
				echo "<H2>ipv6 routing table</H2>";
				echo '<table>
					<tr>
						<th>Destination</th>
						<th>Source Specific</th>
						<th>via</th>
						<th>Device</th>
						<th>proto</th>
						<th>Kernelmetric</th>
					</tr>';
				$v6routen = shell_exec('ip -6 r s t $(grep import-table /etc/babeld.conf | cut -f2 -d" ")');
				$v6route = explode(PHP_EOL, $v6routen);
				for($i = 0; $i < count($v6route); ++$i) {
					echo "<tr>";
					$destination=explode(" ", $v6route[$i]);
					$source=explode(" ", strstr($v6route[$i],"from"));	
					$via=explode(" ", strstr($v6route[$i],"via"));
					$device=explode(" ", strstr($v6route[$i],"dev"));
					$proto=explode(" ", strstr($v6route[$i],"proto"));
					$metric=explode(" ", strstr($v6route[$i],"metric"));
					echo '<td><a href="'.$_SERVER["PHP_SELF"].'?ip='.$destination[0].'">'.$destination[0].'</a></td>';
					echo '<td>'.$source[1].'</td>';
					echo '<td>'.$via[1].'</td>';
					echo '<td>'.$device[1].'</td>';
					echo '<td>'.$proto[1].'</td>';
					echo '<td>'.$metric[1].'</td>';

				echo "</tr>";
			}	

			echo "</table>";
			}
		}
		?>
		<br>
	</body>
</html>
