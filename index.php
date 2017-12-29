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
<h1>Simple Babelweb </h1>
<?php

$addr = "::1";
$port = "33123";
$msg = "dump\r\n";

$sock = socket_create(AF_INET6,SOCK_STREAM,0) or die("Cannot create socket");
socket_connect($sock,$addr,$port) or die("Cannot connect to socket");
$read = socket_read($sock,1024);

$data = explode(PHP_EOL, $read);

echo "<table>";
for($i = 0; $i < count($data); ++$i) {
 	if ($data[$i] == "ok"){
		break;
}
       echo "<tr><td>$data[$i]</td></tr>";
}
echo "</table>";

# dump anfordern
socket_write($sock,$msg,strlen($msg));

# Daten einlesen
$interface = array();
$neighbour = array();
$xroute = array();

while (1) {
	$read = socket_read($sock, 1024, PHP_NORMAL_READ);
	if (preg_match("/interface\b/", $read)){
		$interface[] = $read;
	}
	if (preg_match("/neighbour\b/", $read)){
                $neighbour[] = $read;
        }
        if (preg_match("/xroute\b/", $read)){
                $xroute[] = $read;
        }
        if (preg_match("/\broute\b/", $read)){
                break 1;
        }
}

socket_close($sock);

# Ausgabe
echo "<H2>Interfaces</H2>";
echo '<table>
  <tr>
    <th>Interface</th>
    <th>up</th>
    <th>ipv6</th>
    <th>ipv4</th>
  </tr>';

for($n = 0; $n < count($interface); ++$n){
	$data = explode(" ", $interface[$n]);
	echo "<tr>";
	for($i = 2; $i < count($data); $i+=2) {
		echo "<td>$data[$i]</td>";
	}
	echo "</tr>";
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

for($n = 0; $n < count($neighbour); ++$n){
        $data = explode(" ", $neighbour[$n]);
        echo "<tr>";
        for($i = 2; $i < count($data); $i+=2) {
                echo "<td>$data[$i]</td>";
        }
        echo "</tr>";
}
echo "</table>";


echo "<H2>Redistributed routes</H2>";
echo '<table>
  <tr>
    <th>prefix</th>
    <th>from</th>
    <th>metric</th>
  </tr>';

for($n = 0; $n < count($xroute); ++$n){
        $data = explode(" ", $xroute[$n]);
        echo "<tr>";
        for($i = 4; $i < count($data); $i+=2) {
                echo "<td>$data[$i]</td>";
        }
        echo "</tr>";
}
echo "</table>";
?>

</body></html>
