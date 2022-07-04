################################################################################
# get Event
################################################################################
# Copyright (c) 2020 Artica Soluciones Tecnologicas S.L
# Jose Antonio Almendros 
################################################################################
# 
# usage: getEvent.exe -command "get_event.exe [event_source] [log_name] [interval] [*nodatalist] [*sendlog]"			 
#				
################################################################################

param (
[switch]$h = $false,
[switch]$nodatalist = $false,
[switch]$sendlog = $false
)

if (($h -eq $true) -or ($($Args.Count) -le 2)){
	echo "Plugin to get events from the last N minutes" 
	echo "Usage:" 
	echo "getEvent.exe [event_source] [log_name] [interval] *[-nodatalist] *[-sendlog]`n"
    echo "event_source:`t`tfield Source of the Event"
    echo "log_name:`t`tfield Log Name of the Event"
    echo "interval:`t`ttime interval from events will be extracted (in minutes)"
    echo "nodatalist [optional]:`tshows all output in same module data"
    echo "sendlog [optional]:`tsends logs to log server"
	echo "Artica ST @ 2020" 
	exit
}


$source = $args[0]
$logname = $args[1]
$interval = $args[2]
 

if (($nodatalist -eq $false) -and ($sendlog -eq $false))
    {
    $Logs = get-EventLog  -Source $source -LogName $logname -After $((get-date).AddMinutes(-$interval)) | ft -HideTableHeaders
        $result = foreach ($Log in $Logs)
        {

	          if ($Log)
                {
                echo "<data><value><![CDATA["
                echo $Log
                echo "]]></value></data>"
                echo "`r`n"
                }


        }

		echo "<module>" 
		echo "<name>$source Events</name>" 
		echo "<type>async_string</type>" 
		echo "<datalist>"
        echo $result
        echo "</datalist>" 
		echo "<description>Logs with log name $logname in source $source</description>"
        echo "</module>" 
    }
         
else 
    {
        if ($sendlog -eq $false)
        {
            $Logs = get-EventLog  -Source $source -LogName $logname -After $((get-date).AddMinutes(-$interval)) | ft -HideTableHeaders | Out-String
            $result = foreach ($Log in $Logs)
            {

                echo $Log
                echo "`r`n"


            }

		echo "<module>" 
		echo "<name>$source Events</name>" 
		echo "<type>async_string</type>"
        echo "<data><![CDATA[" 
        echo $result
        echo "]]></data>"
		echo "<description>Logs with log name $logname in source $source</description>"
        echo "</module>" 
        }
    } 

if ($sendlog -eq $true) 
    {
        $Logs = get-EventLog  -Source $source -LogName $logname -After $((get-date).AddMinutes(-$interval)) | ft -HideTableHeaders | Out-String
        $result = foreach ($Log in $Logs)
        {
        
	          if ($Log)
                {
                echo "<![CDATA["
                echo $Log
                echo "]]>"
                echo "`n"
                }


        }

		echo "<log_module>" 
		echo "<source>$source Events</source>"
        echo "<data>" 
        echo $result 
        echo "</data>"
        echo "</log_module>" 
    }