#!/usr/bin/php
<?php
$host = "yourserver.example.net";
$user = "youruser";
$pass = "yourpassword";
$path = "ipmitool";

$opt['chassis'] = "chassis status";
$opt['sensor'] = "sensor";

$cmd['chassis'] =  $path . " -H " . $host . " -U " . $user . " -P " . $pass . " " . $opt['chassis'];
$cmd['sensor'] = $path . " -H " . $host . " -U " . $user . " -P " . $pass . " " . $opt['sensor'];

function print_xml_sensor ($name, $data, $type = "generic_proc") {
	echo "<module><name>".$name."</name><data>".$data."</data><type>".$type."</type></module>";
}

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
	## False is good
	case "Power Overload":
	case "Main Power Fault":
        case "Power Control Fault":
        case "Drive Fault":
        case "Cooling/Fan Fault":
		$data = ($data == "false" ? 1 : 0);
		print_xml_sensor ($name, $data);
		break;
	## Inactive is good
	case "Power Interlock":
		$data = ($data == "inactive" ? 1 : 0);
		print_xml_sensor ($name, $data);
		break;
	## On is good
	case "System Power":
		$data = ($data == "on" ? 1 : 0);
		print_xml_sensor ($name, $data);
		break;
	## Off is good
	case "Front Panel Light":
		$data = ($data == "off" ? 1 : 0);	
     		print_xml_sensor ($name, $data);
		break;
	## Ignore the following values
        case "Last Power Event":
        case "Power Restore Policy":
        default:
                break;

	}
}
unset($status);
//End of Chassis

//Begin of Sensor
$array = explode("\n",$output['sensor']);
foreach ($array as $value) {
   if($value != "") {
       $tmp[] = explode("|",$value);
   }
}

/* 
Sample $tmp:
[1] => Array
        (
            [0] => CPU A Core       
            [1] =>  1.264      
            [2] =>  Volts      
            [3] =>  ok    
            [4] =>  na        
            [5] =>  na        
            [6] =>  1.000     
            [7] =>  1.368     
            [8] =>  na        
            [9] =>  na        
        )

*/
unset ($tmp[0]);
foreach ($tmp as $value_arr) {
	if (trim($value_arr[1]) == "na") {
		continue;	
	} elseif (trim($value_arr[2]) == "discrete") {
		continue;
	} 
	print_xml_sensor (trim($value_arr[0]).' ('.trim($value_arr[2]).')', trim ($value_arr[1]), "generic_data");  
}

//End of Sensor

?>