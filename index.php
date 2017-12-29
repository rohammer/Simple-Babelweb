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
			$addr = "::1";
			$port = "33123";

			$msg = "dump\r\n";
			$sock = socket_create(AF_INET6,SOCK_STREAM,0) or die("Cannot create socket");
			socket_connect($sock,$addr,$port) or die("Cannot connect to socket");
			$read = socket_read($sock,1024);
			$data = explode(PHP_EOL, $read);

			# dump anfordern
			socket_write($sock,$msg,strlen($msg));

			# Daten einlesen
			$interface = array();
			$neighbour = array();
			$xroute = array();

			while (1) {
				$read = socket_read($sock, 1024, PHP_NORMAL_READ);
				if (preg_match("/interface\b/", $read)) {
					$interface[] = $read;
				}
				if (preg_match("/neighbour\b/", $read)) {
					$neighbour[] = $read;
				}
				if (preg_match("/xroute\b/", $read)) {
					$xroute[] = $read;
				}
				if (preg_match("/\broute\b/", $read)){
					break 1;
				}
			}
			socket_close($sock);

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
				$tempdata = explode(" ", $temp);
				$output['neighbours'][] = array(
					'neighbour' => $tempdata[2],
					'address' => $tempdata[4],
					'interface' => $tempdata[6],
					'reach' => $tempdata[8],
					'rxcost' => $tempdata[10],
					'txcost' => $tempdata[12],
					'rtt' => $tempdata[14],
					'rttcost' => $tempdata[16],
					'cost' => $tempdata[18],
				);
			}

			foreach ($xroute as $temp) {
				$tempdata = explode(" ", $temp);
				$output['xroutes'][] = array(
					'prefix' => $tempdata[2],
					'from' => $tempdata[4],
					'metric' => $tempdata[6],
				);
			}

			if($_REQUEST['format'] == 'json') {
				echo json_encode($output);
			}
			else {
				# Ausgabe
				echo "<h1>Simple Babelweb</h1>";
				echo "<table>";
				for($i = 0; $i < count($data); ++$i) {
					if ($data[$i] == "ok") {
						break;
					}
					echo "<tr><td>$data[$i]</td></tr>";
				}
				echo "</table>";

				echo "<H2>Interfaces</H2>";
				echo '<table>
					<tr>
						<th>Interface</th>
						<th>up</th>
						<th>ipv6</th>
						<th>ipv4</th>
					</tr>';
				foreach($output['interfaces'] as $interface) {
					echo "<tr><td>".$interface['interface']."</td>".
							"<td>".$interface['up']."</td>".
							"<td>".$interface['ipv6']."</td>".
							"<td>".$interface['ipv4']."</td></tr>";
				}
				echo "</table>";
				echo "<H2>Neighbours</H2>";
					echo '<table>
						<tr>
							<th>neighbour</th>
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
					echo "<tr><td>".$neighbour['neighbour']."</td>".
							"<td>".$neighbour['address']."</td>".
							"<td>".$neighbour['interface']."</td>".
							"<td>".$neighbour['reach']."</td>".
							"<td>".$neighbour['rxcost']."</td>".
							"<td>".$neighbour['txcost']."</td>".
							"<td>".$neighbour['rtt']."</td>".
							"<td>".$neighbour['rttcost']."</td>".
							"<td>".$neighbour['cost']."</td></tr>";
				}
				echo "</table>";
				echo "<H2>Redistributed routes</H2>";
					echo '<table>
						<tr>
							<th>prefix</th>
							<th>from</th>
							<th>metric</th>
						</tr>';
				foreach($output['xroutes'] as $xroute) {
					echo "<tr><td>".$xroute['prefix']."</td>".
							"<td>".$xroute['from']."</td>".
							"<td>".$xroute['metric']."</td></tr>";
				}
				echo "</table>";
			}
		?>
	</body>
</html>
