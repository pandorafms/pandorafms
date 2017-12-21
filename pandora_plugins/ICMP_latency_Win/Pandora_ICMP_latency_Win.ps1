# Plugin for monitoring devices via ICMP.

# Pandora FMS Agent Plugin for ICMP Monitoring
# (c) Tomás Palacios <tomas.palacios@artica.es> 2012
# v1.1, 02 Aug 2012 - 21:40:00
# ------------------------------------------------------------------------

# Configuration Parameters

param ([string]$w = "w", [string]$n = "n", [string]$select = "select", [string]$list = "list", [string]$onlyxml = "onlyxml", [string]$onlyvalue = "onlyvalue")

$host.UI.RawUI.BufferSize = new-object System.Management.Automation.Host.Size(512,50);

	if ($select -eq "select") { 

		echo "`nPandora FMS Agent Plugin for ICMP Monitoring`n"

		echo "(c) Tomás Palacios <tomas.palacios@artica.es> 2012	v1.1, 02 Aug 2012 - 21:40:00`n"

		echo "Parameters:`n"

		echo "	-w			Timeout in milliseconds to wait for each reply (Default timeout is 1000 milliseconds)`n"

		echo "	-n			Number of echo requests to send (Default is 4 echo requests)`n"

		echo "	-select all		All operations are executed`n"

		echo "	-select	host-alive	Only operations to check host status (responding or not)`n"

		echo "	-select host-latency	Only operations to check host latency (returns -1 if not available)`n"

		echo "	-list			Provides an absolute path to a list of hosts to check (not to be used with -only-value)`n"

		echo "	-onlyxml		Provides a single host to check returning the value in XML format`n"

		echo "	-onlyvalue		Provides a single host to check returning the value in standard format (useless for -list or -select all)`n"

		echo "Usage example: .\Pandora_Plugin_Ping_v1.0.ps1 -w 1000 -n 4 -select all -list C:\Users\Pandora\hosts.txt 2> plugin_error.log`n"
	}

	else {

	$server = hostname

#############################CODE BEGINS HERE###############################

# Función para sacar los módulos en formato XML en el output

	function print_module {

		param ([string]$module_name,[string]$module_type,[string]$module_value,[string]$module_description)

		echo "<module>"
		echo "<name><![CDATA[$module_name]]></name>"
		echo "<type>$module_type</type>"
		echo "<description>"
		echo "<![CDATA[$module_description"
		echo "]]>"
		echo "</description>"
		echo "<data><![CDATA[$module_value]]></data>"
		echo "</module>"

	}


# Error si no se selecciona ninguna tarea a realizar

	if ( $list -eq "list" -and $onlyxml -eq "onlyxml" -and $onlyvalue -eq "onlyvalue" ) {

		Write-Error "Error: A list or a single host must be provided as a parameter first." -category InvalidArgument

	} else {
	
# Definir valores por defecto de timeout y peticiones echo

		if ( $w -eq "w" ) {

			$w = 1000 -as [int]

		}

		else {

			$w = $w -as [int]

		}

		if ($n -eq "n" ) {

			$n = 4 -as [int]

		}

		else {

			$n = $n -as [int]

		}

# Restringiendo el uso de select onlyvalue a una sola tarea y host

		if ( $onlyvalue -ne "onlyvalue" -and $select -eq "all" ) {

			Write-Error "Error: Cannot select all operations for parameter -select onlyvalue" -category InvalidArgument

		}

		if ( $onlyvalue -ne "onlyvalue" -and $list -ne "list" ) {

			Write-Error "Error: Cannot use a host list for parameter -select onlyvalue" -category InvalidArgument

		}
		
# MAIN CODE

		if ( $onlyvalue -ne "onlyvalue" -and $list -eq "list" -and $select -ne "all" ) {

			$commandc = `ping $onlyvalue -w $w -n $n | grep "ms,";

			$commandc | foreach-object {

				$unformattedvalue = `echo $_ | gawk '{ print $NF }' | gawk -F ms '{ print $1 }';

				if ( $select -eq "host-latency" -and $unformattedvalue ) {

					echo "$unformattedvalue"

				}

				if ( $select -eq "host-latency" -and !$unformattedvalue ) {

					echo "-1"

				}

				if ( $select -eq "host-alive" -and $unformattedvalue ) {

					echo "1"

				}

				if ( $select -eq "host-alive" -and !$unformattedvalue ) {

					echo "0"

				}

			}

		}

		if ( $onlyxml -ne "onlyxml" ) {

			$command = `ping $onlyxml -w $w -n $n | grep "ms,";

			$command | foreach-object {

				$value = `echo $_ | gawk '{ print $NF }' | gawk -F ms '{ print $1 }';

				if ( $select -eq "all" -and $value -or $select -eq "host-latency" -and $value ) {
					
					print_module "Host Latency - $onlyxml" "generic_data" "$value" "$_"

				}

				if ( $select -eq "all" -and !$value -or $select -eq "host-latency" -and !$value ) {

					print_module "Host Latency - $onlyxml" "generic_data" "-1" "Unable to ping location"

				}
				
				if ( $select -eq "all" -and $value -or $select -eq "host-alive" -and $value ) {
					
					print_module "Host Alive - $onlyxml" "generic_proc" "1" "Host is alive"

				}

				if ( $select -eq "all" -and !$value -or $select -eq "host-alive" -and !$value ) {

					print_module "Host Alive - $onlyxml" "generic_proc" "0" "Unable to ping location"

				}

			}

		}

		if ( $list -ne "list" -and $onlyvalue -eq "onlyvalue" ) {

			get-content $list | foreach-object {

					$hostcheck = $_

					$command2= `ping $hostcheck -w $w -n $n | grep "ms,";

					$command2 | foreach-object {

					$value = `echo $_ | gawk '{ print $NF }' | gawk -F ms '{ print $1 }';

					if ( $select -eq "all" -and $value -or $select -eq "host-latency" -and $value ) {
					
						print_module "Host Latency - $hostcheck" "generic_data" "$value" "$_"

					}

					if ( $select -eq "all" -and !$value -or $select -eq "host-latency" -and !$value ) {

						print_module "Host Latency - $hostcheck" "generic_data" "-1" "Unable to ping location"

					}

					if ( $select -eq "all" -and $value -or $select -eq "host-alive" -and $value ) {
					
						print_module "Host Alive - $hostcheck" "generic_proc" "1" "Host is alive"

					}

					if ( $select -eq "all" -and !$value -or $select -eq "host-alive" -and !$value ) {

						print_module "Host Alive - $hostcheck" "generic_proc" "0" "Unable to ping location"

					}

				}

			}

		}
					
	}

}


