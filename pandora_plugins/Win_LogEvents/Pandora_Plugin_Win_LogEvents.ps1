# Plugin for monitoring Windows Event Logs.

# Pandora FMS Agent Plugin for Microsoft Windows Event Log Monitoring
# (c) Tomás Palacios <tomas.palacios@artica.es> 2012
# v1.0, 02 Aug 2012 - 13:35:00
# ------------------------------------------------------------------------

# Configuration Parameters

param ([string]$interval = "i", [string]$select = "select", [string]$list = "list", [string]$name = "name", [string]$source = "source", [string]$eventtype = "eventtype", [string]$eventcode = "eventcode", [string]$application = "application", [string]$pattern = "")

$host.UI.RawUI.BufferSize = new-object System.Management.Automation.Host.Size(512,50);

	if ($interval -eq "i") { 

		echo "`nPandora FMS Agent Plugin for Microsoft Windows Event Log Monitoring`n"

		echo "(c) Tomás Palacios <tomas.palacios@artica.es> 2012	v1.0, 02 Aug 2012 - 13:35:00`n"

		echo "Parameters:`n"

		echo "	-i	Interval in seconds to look for new events (mandatory)`n"

		echo "	-select	single	Only the events matching the parameters provided in the CLI are monitored `n	(not to be used with list option)`n"

#		echo "	-select	list	Events matching the parameters provided in a list are monitored `n	(only to be used with list option)`n"

#		echo "	-list	Complete path to a file containing a list of events to monitor (only to be used with select list option)`n"

		echo "	-name	Name of the module (only to be used with select single option) (mandatory)`n"

		echo "	-source	Event source log to search for events (only to be used with select single option) (mandatory)`n"

		echo "	-eventtype	Event type (only to be used with select single option) (optional)`n"

		echo "	-eventcode	Event numeric identifier (only to be used with select single option) (optional)`n"

		echo "	-application	Event source application (only to be used with select single option) (optional)`n"

		echo "	-pattern	Substring pattern to filter event contents (only with select single option) (optional)`n"

#		echo "Usage example: .\Pandora_Plugin_Win_LogEvents_v1.0.ps1 -i 300 -select list -list .\events.txt 2> plugin_error.log`n"

		echo "Usage example: .\Pandora_Plugin_Win_LogEvents_v1.0.ps1 -i 300 -select single -source System -eventtype Error -eventcode 5355 -application NetLogon -pattern Failure 2> plugin_error.log`n"
	}

	else {

	$datainterval = $interval -as [long]

#############################CODE BEGINS HERE###############################

# Función para sacar los módulos en formato XML en el output

	function print_module {

		param ([string]$module_name,[string]$module_type,[string]$module_value,[string]$module_description)

		echo "<module>"
		echo "<name>$module_name</name>"
		echo "<type>$module_type</type>"
		echo "<description>"
		echo "<![CDATA[$module_description]]>"
		echo "</description>"
		echo "<data>"
		echo "<![CDATA[$module_value"
		echo "]]>"
		echo "</data>"
		echo "</module>"

	}


# Recolección de los eventos Windows

	if ($select -eq "select") {

		Write-Error "Error: An operation must be selected." -category InvalidArgument

	}

	if ($select -eq "single" -and $name -eq "name") {

		Write-Error "Error: A name must be selected for this module." -category InvalidArgument

	}

	if ($select -eq "single" -and $name -ne "name") {

		if ($source -eq "source") {

			Write-Error "Error: A log source must be provided when selecting this option." -category InvalidArgument

		}

		if ($source -ne "source" -and $eventtype -eq "eventtype" -and $eventcode -eq "eventcode" -and $application -eq "application" -and $pattern -eq "") {

			Get-EventLog $source | Select-Object -Property * |

			foreach-object {

				$eventid = $_.EventID

				$eventsource = $_.Source + ""

				$eventtype = $_.EntryType

				$message = $_.Message + ""

				$timestamp = $_.TimeGenerated

				$matchtstamp = New-TimeSpan -Start ($timestamp) | ForEach-Object { echo $_.TotalSeconds } | gawk -F "," '{print $1}'

				$matchtimestamp = $matchtstamp -as [long]

				if ($matchtimestamp -lt $datainterval) {

					print_module "$name" "async_string" "$timestamp - $message" "Event source: $source - Event application source: $eventsource - Event ID: $eventid - Event Type: $eventtype"

				}

			}

		}

#####################################
#De uno en uno
#####################################

		if ($source -ne "source" -and $eventtype -ne "eventtype" -and $eventcode -eq "eventcode" -and $application -eq "application" -and $pattern -eq "") {

			Get-EventLog $source | Select-Object -Property * | Where-Object { $_.EntryType -eq $eventtype} |

			foreach-object {

				$eventid = $_.EventID

				$eventsource = $_.Source + ""

				$eventtype = $_.EntryType

				$message = $_.Message + ""

				$timestamp = $_.TimeGenerated

				$matchtstamp = New-TimeSpan -Start ($timestamp) | ForEach-Object { echo $_.TotalSeconds } | gawk -F "," '{print $1}'

				$matchtimestamp = $matchtstamp -as [long]

				if ($matchtimestamp -lt $datainterval) {

					print_module "$name" "async_string" "$timestamp - $message" "Event source: $source - Event application source: $eventsource - Event ID: $eventid - Event Type: $eventtype"

				}

			}

		}

		if ($source -ne "source" -and $eventtype -eq "eventtype" -and $eventcode -ne "eventcode" -and $application -eq "application" -and $pattern -eq "") {

			Get-EventLog $source | Select-Object -Property * | Where-Object { $_.EventID -eq $eventcode} |

			foreach-object {

				$eventid = $_.EventID

				$eventsource = $_.Source + ""

				$eventtype = $_.EntryType

				$message = $_.Message + ""

				$timestamp = $_.TimeGenerated

				$matchtstamp = New-TimeSpan -Start ($timestamp) | ForEach-Object { echo $_.TotalSeconds } | gawk -F "," '{print $1}'

				$matchtimestamp = $matchtstamp -as [long]

				if ($matchtimestamp -lt $datainterval) {

					print_module "$name" "async_string" "$timestamp - $message" "Event source: $source - Event application source: $eventsource - Event ID: $eventid - Event Type: $eventtype"

				}

			}

		}

		if ($source -ne "source" -and $eventtype -eq "eventtype" -and $eventcode -eq "eventcode" -and $application -ne "application" -and $pattern -eq "") {

			Get-EventLog $source | Select-Object -Property * | Where-Object { $_.Source -eq $application} |

			foreach-object {

				$eventid = $_.EventID

				$eventsource = $_.Source + ""

				$eventtype = $_.EntryType

				$message = $_.Message + ""

				$timestamp = $_.TimeGenerated

				$matchtstamp = New-TimeSpan -Start ($timestamp) | ForEach-Object { echo $_.TotalSeconds } | gawk -F "," '{print $1}'

				$matchtimestamp = $matchtstamp -as [long]

				if ($matchtimestamp -lt $datainterval) {

					print_module "$name" "async_string" "$timestamp - $message" "Event source: $source - Event application source: $eventsource - Event ID: $eventid - Event Type: $eventtype"

				}

			}

		}

		if ($source -ne "source" -and $eventtype -eq "eventtype" -and $eventcode -eq "eventcode" -and $application -eq "application" -and $pattern -ne "") {

			Get-EventLog $source | Select-Object -Property * | Where-Object { $_.Message -match $pattern } |

			foreach-object {

				$eventid = $_.EventID

				$eventsource = $_.Source + ""

				$eventtype = $_.EntryType

				$message = $_.Message + ""

				$timestamp = $_.TimeGenerated

				$matchtstamp = New-TimeSpan -Start ($timestamp) | ForEach-Object { echo $_.TotalSeconds } | gawk -F "," '{print $1}'

				$matchtimestamp = $matchtstamp -as [long]

				if ($matchtimestamp -lt $datainterval) {

					print_module "$name" "async_string" "$timestamp - $message" "Event source: $source - Event application source: $eventsource - Event ID: $eventid - Event Type: $eventtype"

				}

			}

		}

#####################################
#De dos en dos
#####################################

		if ($source -ne "source" -and $eventtype -ne "eventtype" -and $eventcode -ne "eventcode" -and $application -eq "application" -and $pattern -eq "") {

			Get-EventLog $source | Select-Object -Property * | Where-Object { $_.EntryType -eq $eventtype -and $_.EventID -eq $eventcode} |

			foreach-object {

				$eventid = $_.EventID

				$eventsource = $_.Source + ""

				$eventtype = $_.EntryType

				$message = $_.Message + ""

				$timestamp = $_.TimeGenerated

				$matchtstamp = New-TimeSpan -Start ($timestamp) | ForEach-Object { echo $_.TotalSeconds } | gawk -F "," '{print $1}'

				$matchtimestamp = $matchtstamp -as [long]

				if ($matchtimestamp -lt $datainterval) {

					print_module "$name" "async_string" "$timestamp - $message" "Event source: $source - Event application source: $eventsource - Event ID: $eventid - Event Type: $eventtype"

				}

			}

		}

		if ($source -ne "source" -and $eventtype -ne "eventtype" -and $eventcode -eq "eventcode" -and $application -ne "application" -and $pattern -eq "") {

			Get-EventLog $source | Select-Object -Property * | Where-Object { $_.EntryType -eq $eventtype -and $_.Source -eq $application} |

			foreach-object {

				$eventid = $_.EventID

				$eventsource = $_.Source + ""

				$eventtype = $_.EntryType

				$message = $_.Message + ""

				$timestamp = $_.TimeGenerated

				$matchtstamp = New-TimeSpan -Start ($timestamp) | ForEach-Object { echo $_.TotalSeconds } | gawk -F "," '{print $1}'

				$matchtimestamp = $matchtstamp -as [long]

				if ($matchtimestamp -lt $datainterval) {

					print_module "$name" "async_string" "$timestamp - $message" "Event source: $source - Event application source: $eventsource - Event ID: $eventid - Event Type: $eventtype"

				}

			}

		}

		if ($source -ne "source" -and $eventtype -ne "eventtype" -and $eventcode -eq "eventcode" -and $application -eq "application" -and $pattern -ne "") {

			Get-EventLog $source | Select-Object -Property * | Where-Object { $_.EntryType -eq $eventtype -and $_.Message -match $pattern} |

			foreach-object {

				$eventid = $_.EventID

				$eventsource = $_.Source + ""

				$eventtype = $_.EntryType

				$message = $_.Message + ""

				$timestamp = $_.TimeGenerated

				$matchtstamp = New-TimeSpan -Start ($timestamp) | ForEach-Object { echo $_.TotalSeconds } | gawk -F "," '{print $1}'

				$matchtimestamp = $matchtstamp -as [long]

				if ($matchtimestamp -lt $datainterval) {

					print_module "$name" "async_string" "$timestamp - $message" "Event source: $source - Event application source: $eventsource - Event ID: $eventid - Event Type: $eventtype"

				}

			}

		}

		if ($source -ne "source" -and $eventtype -eq "eventtype" -and $eventcode -ne "eventcode" -and $application -ne "application" -and $pattern -eq "") {

			Get-EventLog $source | Select-Object -Property * | Where-Object { $_.Source -eq $application -and $_.EventID -eq $eventcode} |

			foreach-object {

				$eventid = $_.EventID

				$eventsource = $_.Source + ""

				$eventtype = $_.EntryType

				$message = $_.Message + ""

				$timestamp = $_.TimeGenerated

				$matchtstamp = New-TimeSpan -Start ($timestamp) | ForEach-Object { echo $_.TotalSeconds } | gawk -F "," '{print $1}'

				$matchtimestamp = $matchtstamp -as [long]

				if ($matchtimestamp -lt $datainterval) {

					print_module "$name" "async_string" "$timestamp - $message" "Event source: $source - Event application source: $eventsource - Event ID: $eventid - Event Type: $eventtype"

				}

			}

		}

		if ($source -ne "source" -and $eventtype -eq "eventtype" -and $eventcode -ne "eventcode" -and $application -eq "application" -and $pattern -ne "") {

			Get-EventLog $source | Select-Object -Property * | Where-Object { $_.Message -match $pattern -and $_.EventID -eq $eventcode} |

			foreach-object {

				$eventid = $_.EventID

				$eventsource = $_.Source + ""

				$eventtype = $_.EntryType

				$message = $_.Message + ""

				$timestamp = $_.TimeGenerated

				$matchtstamp = New-TimeSpan -Start ($timestamp) | ForEach-Object { echo $_.TotalSeconds } | gawk -F "," '{print $1}'

				$matchtimestamp = $matchtstamp -as [long]

				if ($matchtimestamp -lt $datainterval) {

					print_module "$name" "async_string" "$timestamp - $message" "Event source: $source - Event application source: $eventsource - Event ID: $eventid - Event Type: $eventtype"

				}

			}

		}

		if ($source -ne "source" -and $eventtype -eq "eventtype" -and $eventcode -eq "eventcode" -and $application -ne "application" -and $pattern -ne "") {

			Get-EventLog $source | Select-Object -Property * | Where-Object { $_.Source -eq $application -and $_.Message -match $pattern} |

			foreach-object {

				$eventid = $_.EventID

				$eventsource = $_.Source + ""

				$eventtype = $_.EntryType

				$message = $_.Message + ""

				$timestamp = $_.TimeGenerated

				$matchtstamp = New-TimeSpan -Start ($timestamp) | ForEach-Object { echo $_.TotalSeconds } | gawk -F "," '{print $1}'

				$matchtimestamp = $matchtstamp -as [long]

				if ($matchtimestamp -lt $datainterval) {

					print_module "$name" "async_string" "$timestamp - $message" "Event source: $source - Event application source: $eventsource - Event ID: $eventid - Event Type: $eventtype"

				}

			}

		}

#####################################
#De tres en tres
#####################################

		if ($source -ne "source" -and $eventtype -ne "eventtype" -and $eventcode -ne "eventcode" -and $application -ne "application" -and $pattern -eq "") {

			Get-EventLog $source | Select-Object -Property * | Where-Object { $_.EntryType -eq $eventtype -and $_.EventID -eq $eventcode -and $_.Source -eq $application} |

			foreach-object {

				$eventid = $_.EventID

				$eventsource = $_.Source + ""

				$eventtype = $_.EntryType

				$message = $_.Message + ""

				$timestamp = $_.TimeGenerated

				$matchtstamp = New-TimeSpan -Start ($timestamp) | ForEach-Object { echo $_.TotalSeconds } | gawk -F "," '{print $1}'

				$matchtimestamp = $matchtstamp -as [long]

				if ($matchtimestamp -lt $datainterval) {

					print_module "$name" "async_string" "$timestamp - $message" "Event source: $source - Event application source: $eventsource - Event ID: $eventid - Event Type: $eventtype"

				}

			}

		}

		if ($source -ne "source" -and $eventtype -ne "eventtype" -and $eventcode -ne "eventcode" -and $application -eq "application" -and $pattern -ne "") {

			Get-EventLog $source | Select-Object -Property * | Where-Object { $_.EntryType -eq $eventtype -and $_.EventID -eq $eventcode -and $_.Message -match $pattern} |

			foreach-object {

				$eventid = $_.EventID

				$eventsource = $_.Source + ""

				$eventtype = $_.EntryType

				$message = $_.Message + ""

				$timestamp = $_.TimeGenerated

				$matchtstamp = New-TimeSpan -Start ($timestamp) | ForEach-Object { echo $_.TotalSeconds } | gawk -F "," '{print $1}'

				$matchtimestamp = $matchtstamp -as [long]

				if ($matchtimestamp -lt $datainterval) {

					print_module "$name" "async_string" "$timestamp - $message" "Event source: $source - Event application source: $eventsource - Event ID: $eventid - Event Type: $eventtype"

				}

			}

		}

		if ($source -ne "source" -and $eventtype -ne "eventtype" -and $eventcode -eq "eventcode" -and $application -ne "application" -and $pattern -ne "") {

			Get-EventLog $source | Select-Object -Property * | Where-Object { $_.EntryType -eq $eventtype -and $_.Message -match $pattern -and $_.Source -eq $application} |

			foreach-object {

				$eventid = $_.EventID

				$eventsource = $_.Source + ""

				$eventtype = $_.EntryType

				$message = $_.Message + ""

				$timestamp = $_.TimeGenerated

				$matchtstamp = New-TimeSpan -Start ($timestamp) | ForEach-Object { echo $_.TotalSeconds } | gawk -F "," '{print $1}'

				$matchtimestamp = $matchtstamp -as [long]

				if ($matchtimestamp -lt $datainterval) {

					print_module "$name" "async_string" "$timestamp - $message" "Event source: $source - Event application source: $eventsource - Event ID: $eventid - Event Type: $eventtype"

				}

			}

		}

		if ($source -ne "source" -and $eventtype -eq "eventtype" -and $eventcode -ne "eventcode" -and $application -ne "application" -and $pattern -ne "") {

			Get-EventLog $source | Select-Object -Property * | Where-Object { $_.Message -match $pattern -and $_.EventID -eq $eventcode -and $_.Source -eq $application} |

			foreach-object {

				$eventid = $_.EventID

				$eventsource = $_.Source + ""

				$eventtype = $_.EntryType

				$message = $_.Message + ""

				$timestamp = $_.TimeGenerated

				$matchtstamp = New-TimeSpan -Start ($timestamp) | ForEach-Object { echo $_.TotalSeconds } | gawk -F "," '{print $1}'

				$matchtimestamp = $matchtstamp -as [long]

				if ($matchtimestamp -lt $datainterval) {

					print_module "$name" "async_string" "$timestamp - $message" "Event source: $source - Event application source: $eventsource - Event ID: $eventid - Event Type: $eventtype"

				}

			}

		}

#####################################
#Todas las operaciones seleccionadas
#####################################

		if ($source -ne "source" -and $eventtype -ne "eventtype" -and $eventcode -ne "eventcode" -and $application -ne "application" -and $pattern -ne "") {

			Get-EventLog $source | Select-Object -Property * | Where-Object { $_.EntryType -eq $eventtype -and $_.EventID -eq $eventcode -and $_.Source -eq $application -and $_.Message -match $pattern} |

			foreach-object {

				$eventid = $_.EventID

				$eventsource = $_.Source + ""

				$eventtype = $_.EntryType

				$message = $_.Message + ""

				$timestamp = $_.TimeGenerated

				$matchtstamp = New-TimeSpan -Start ($timestamp) | ForEach-Object { echo $_.TotalSeconds } | gawk -F "," '{print $1}'

				$matchtimestamp = $matchtstamp -as [long]

				if ($matchtimestamp -lt $datainterval) {

					print_module "$name" "async_string" "$timestamp - $message" "Event source: $source - Event application source: $eventsource - Event ID: $eventid - Event Type: $eventtype"

				}

			}

		}

	}

}