#!/usr/bin/perl -w

##################################################################################
# SNMP INTERFACE BANDWITH PLUGIN FOR PANDORA FMS
# (c) Artica Soluciones Tecnologicas, 2012
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; version 2
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
##################################################################################

use strict;
use warnings;


sub get_param($) {
	my $param = shift;
	my $value = undef;

	$param = "-".$param;
	
	for(my $i=0; $i<$#ARGV; $i++) {
		
		if ($ARGV[$i] eq $param) {
			$value = $ARGV[$i+1];
			last;
		}

	}

	return $value;
}

sub usage () {
	print "\nusage: $0 -ip <device_ip> -community <community> -ifname <iface_name>\n";
	print "\nIMPORTANT: This plugin uses SNMP v1\n\n";
}

#Global variables
my $ip = get_param("ip");
my $community = get_param("community");
my $ifname = get_param("ifname");

if (!defined($ip) || 
	!defined($community) || 
	!defined($ifname) ) {
	usage();
	exit;
}

#Browse interface name
my $res = `snmpwalk -c $community -v1 $ip .1.3.6.1.2.1.2.2.1.2 -On`;

my $suffix = undef;

my @iface_list = split(/\n/, $res);

foreach my $line (@iface_list) {
	
	#Parse snmpwalk line
	if ($line =~ m/^([\d|\.]+) = STRING: (.*)$/) {
		my $aux = $1;
		
		#Chec if this is the interface requested
		if ($2 eq $ifname) {
	
			my @suffix_array = split(/\./, $aux);
			
			#Get last number of OID
			$suffix = $suffix_array[$#suffix_array];
		}
	}
}

#Check if iface name was found
if (defined($suffix)) {
	#Get octets stats
	my $inoctets = `snmpget $ip -c $community -v1 .1.3.6.1.2.1.2.2.1.10.$suffix -OUevqt`;
	my $outoctets = `snmpget $ip -c $community -v1 .1.3.6.1.2.1.2.2.1.16.$suffix -OUevqt`;
		
	print $inoctets+$outoctets;
}
