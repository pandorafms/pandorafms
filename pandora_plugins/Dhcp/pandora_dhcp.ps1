#Plugin for monitoring Microsoft Exchange Server.
#
# Pandora FMS Agent Plugin for dchp.
#
#(c) Alejandro Sánchez <alejandro.sanchez@pandorafms.com> 
# v1.2, 26 enero 2023
# ------------------------------------------------------------------------


function print_module {

	param ([string]$module_name,[string]$module_type,[string]$module_value,[string]$module_desc)

	echo "<module>"
	echo "<name>$module_name</name>"
	echo "<type>$module_type</type>"
	echo "<data><![CDATA[$module_value]]></data>"
	echo "<description><![CDATA[$module_desc]]></description>"
	echo "</module>"

}

#$LinuxCurrentIP=$() 2> $NULL
$WindowsCurrentIP=$((Get-NetIPConfiguration | Where-Object { $_.IPv4DefaultGateway -ne $null -and $_.NetAdapter.Status -ne "Disconnected" }).IPv4Address.IPAddress) 2> $NULL
$Scopes=$(get-dhcpserverv4scope | ConvertTo-Csv -NoTypeInformation) 2> $NULL
$Scopes=$(get-dhcpserverv4scope | Select ScopeId |ConvertTo-Csv -NoTypeInformation) 2> $NULL
$ScopeIds=$(((get-dhcpserverv4scope).ScopeId).IPAddressToString) 2> $NULL

$avalaible_ips=0
$count_reservation=0
$count_leases=0
ForEach($scope_ids in $Scopes)
{
if($scope_ids -notmatch 'ScopeId')
{
$scope_ids = $scope_ids -replace '"', ""
$scope_ids =[IPAddress]$scope_ids 
$ScopeRange=$(get-dhcpserverv4scope -ScopeId $scope_ids | ConvertTo-Csv -NoTypeInformation) 
$ScopeMask=$(((get-dhcpserverv4scope -ScopeId $scope_ids).SubnetMask).IPAddressToString) 2> $NULL
$PercentageInUse=$((get-dhcpserverv4scopestatistics -ScopeId $scope_ids).PercentageInUse) 2> $NULL
# $Free=$((get-dhcpserverv4scopestatistics -ScopeId $scope_ids).Free) 2> $NULL
# $InUse=$((get-dhcpserverv4scopestatistics -ScopeId $scope_ids).InUse) 2> $NULL
# $Reserved=$((get-dhcpserverv4scopestatistics -ScopeId $scope_ids).Reserved) 2> $NULL
# $Pending=$((get-dhcpserverv4scopestatistics -ScopeId $scope_ids).Pending) 2> $NULL
#$AddressAssignedList=$(Get-DhcpServerv4Lease -ScopeId $scope_ids | ConvertTo-Csv -NoTypeInformation) 2> $NULL
$AddressAssignedList=$((Get-DhcpServerv4Lease -ScopeId $scope_ids).AddressState) 2> $NULL
#$Reservations=$(Get-DhcpServerv4Reservation -ScopeId $scope_ids | ConvertTo-Csv -NoTypeInformation) 2> $NULL
$Reservations=$((Get-DhcpServerv4Reservation -ScopeId $scope_ids).AddressState) 2> $NULL
$ExclusionRanges=$(Get-DhcpServerv4ExclusionRange -ScopeId $scope_ids | ConvertTo-Csv -NoTypeInformation) 2> $NULL
$Start_range=((Get-DhcpServerv4ExclusionRange -ScopeId $scope_ids).StartRange.IPAddressToString) 2> $NULL
$End_range=((Get-DhcpServerv4ExclusionRange -ScopeId $scope_ids).EndRange.IPAddressToString) 2> $NULL


## reservation 
ForEach($reservation in $Reservations){
if ($Reservations -match "InactiveReservation"){$count_reservation=$count_reservation+0}else {if ($Reservations){$count_reservation=$count_reservation+1}else {$count_reservation=$count_reservation+0} }
}
## leases 
ForEach($lease in $AddressAssignedList){
if ($AddressAssignedList -match "InactiveReservation"){$count_leases=$count_leases+0}else {if ($Reservations){$count_reservation=$count_reservation+1}else {$count_reservation=$count_reservation+0} }
}

$count_assigned=$count_reservation+$count_leases

# last octet value of an IP address
$exc_start=$Start_range.Split('.')[-1]
$exc_end=$End_range.Split('.')[-1]

# avalaible end range - start range +1
$avalaible=[int]$exc_end - [int]$exc_start +1

$free= $avalaible - $count_reservation

print_module "[$scope_ids] - dhcp usage" "generic_data" "$PercentageInUse" "Used percentage"
print_module "[$scope_ids] - dhcp reserved ips" "generic_data" "$count_reservation" "reservations"
print_module "[$scope_ids] - dhcp assigned ips" "generic_data" "$count_assigned" "assigned ips"
print_module "[$scope_ids] - dhcp avalaible ips" "generic_data" "$avalaible" "Available and reserved ips"
print_module "[$scope_ids] - dhcp free ips" "generic_data" "$free" "Available ips (not reserved)"

#reset
$count_reservation=0
$count_leases=0
}
}

