#!/usr/bin/php
<?php
$host = "128.151.188.89";
$user = "pandora";
$pass = "";
$path = "ipmitool";

$opt['chassis'] = "chassis status";
$opt['sensor'] = "sensor";

$cmd['chassis'] =  $path . " -H " . $host . " -U " . $user . " -P " . $pass . " " . $opt['chassis'];
$cmd['sensor'] = $path . " -H " . $host . " -U " . $user . " -P " . $pass . " " . $opt['sensor'];

//print_r($cmd);

$output['chassis'] = shell_exec($cmd['chassis']);
$output['sensor'] = shell_exec($cmd['sensor']);

//Chassis
/* Sample output
System Power         : on
Power Overload       : false
Power Interlock      : inactive
Main Power Fault     : false
Power Control Fault  : false
Power Restore Policy : always-on
Last Power Event     : 
Chassis Intrusion    : inactive
Front-Panel Lockout  : active
Drive Fault          : false
Cooling/Fan Fault    : false
Front Panel Light    : off
*/
/* Sample XML
<module><data>28.5</data><name>DRIVE BAY</name><type>generic_data</type></module>
*/
$array = explode("\n",$output['chassis']);
foreach ($array as $value) {
     if($value != "") {
       $tmp = explode(":",$value);
       $status[trim($tmp[0])] = trim($tmp[1]);
     }
}
unset($array);
unset($tmp);

foreach ($status as $name => $data) {
     switch($name) {
	case "Power Interlock":
	case "Last Power Event":
	case "System Power":
	case "Power Restore Policy":
		break;
	case "Power Overload":
	case "Main Power Fault":
	case "Power Control Fault":
	case "Drive Fault":
	case "Cooling/Fan Fault":
		$data_out = ($data="false" ? "1" : "0");
	case "Front Panel Light":
		$data_out = ($data="off" ? "1" : "0");	
	echo "<module><name>" . $name . "</name><data>" . $data_out . "</data><type>generic_proc</type></module>";
     }
}
unset($status);
//End of Chassis

//Begin of Sensor
$array = explode("\n\n",$output['sensor']);
foreach ($array as $value) {
   if($value != "") {
       $tmp[] = explode("\n",$value);
   }
}

foreach ($tmp as $value_arr) {
  foreach ($value_arr as $value) {
    if($value != "") {
       $tmp2 = explode(":",$value);
       $status[trim($tmp2[0])] = trim($tmp2[1]);
     }
  }
  unset($value_arr);
  unset($tmp2);

/* Sample $status array
    [Sensor ID] => 'PSU1 Fan Out' (0x3c)
    [Entity ID] => 10.1
    [Sensor Type (Analog)] => Fan
    [Sensor Reading] => 6784 (+/- 0) RPM
    [Status] => ok
    [Lower Non-Recoverable] => na
    [Lower Critical] => na
    [Lower Non-Critical] => 1024.000
    [Upper Non-Critical] => 18048.000
    [Upper Critical] => na
    [Upper Non-Recoverable] => na
    [Assertion Events] => 
    [Assertions Enabled] => lnc- lnc+ unc- unc+
    [Deassertions Enabled] => lnc- lnc+ unc- unc+
*/

  //Get the name without references
  $name_tmp = explode("'",$status["Sensor ID"]);

/*  //Get the Sensor Type
  if(array_key_exists("Sensor Type (Analog)",$status)) {
	$status["type"] = $status["Sensor Type (Analog)"];	
  } elseif(array_key_exists("Sensor Type (Discrete)",$status)) {
  	$status["type"] = $status["Sensor Type (Discrete)"];
  } else {
  	echo "Unhandled Sensor Type";
	print_r($status);
	die();
  }
*/

  $data_tmp = explode(" ",$status["Sensor Reading"]);

if($data_tmp[3]) {
	$name = $name_tmp[1] . " (" . $data_tmp[3] . ($data_tmp[4] ? " " . $data_tmp[4] : "" ) . ")";	
	echo "\n<module><name>" . $name . "</name>";
	if($status["Lower Non-Critical"] != "na") {
		$min = "<min>" . $status["Lower Non-Critical"] . "</min>";
	}
        if($status["Upper Non-Critical"] != "na") {
                $max = "<max>" . $status["Upper Non-Critical"] . "</max>";
        }
	if($status["Lower Critical"] != "na") {
                $min = "<min>" . $status["Lower Critical"] . "</min>";
        } 
        if($status["Upper Critical"] != "na") {
                $max = "<max>" . $status["Upper Critical"] . "</max>";
        }
	echo $min . $max . "<data>" . $data_tmp[0] . "</data><type>generic_data</type></module>";	
}


//$data_out = ($data="false" ? "1" : "0");
//$data_out = ($data="off" ? "1" : "0");

  unset($status);
}

//End of Sensor

?>
