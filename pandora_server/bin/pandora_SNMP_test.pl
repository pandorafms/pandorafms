#!/usr/bin/perl
##################################################################################
# SNMP Test tool
##################################################################################
# Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
# Copyright (c) 2005-2006 Artica Soluciones Tecnológicas S.L
#
#This program is free software; you can redistribute it and/or
#modify it under the terms of the GNU General Public License
#as published by the Free Software Foundation; either version 2
#of the License, or (at your option) any later version.
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program; if not, write to the Free Software
#Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
##################################################################################

#use Net::SNMP;	 # For query2 testing
use SNMP '5.0.2.pre1' || die("Cannot load module\n");

##########################################################################################
# SUB pandora_query_snmp (pa_config, oid, community, target, error, dbh)
# Makes a call to SNMP modules to get a value,
##########################################################################################
sub pandora_query_snmp2 {
	my $snmp_oid = shift;
	my $snmp_community = shift;
	my $snmp_target = shift;


	print "DEBUG OID $snmp_oid comm  $snmp_community target $snmp_target \n";
	my $output ="";
	
	my ($session1, $error) = Net::SNMP->session(
      	-hostname  => $snmp_target,
      	-community => $snmp_community,
      	-port      => 161 );

   	if (!defined($session1)) {
      		printf("SNMP ERROR SESSION");
   	}

   	my $result = $session1->get_request(
      		-varbindlist => $snmp_oid
   	);

   	if (!defined($result)) {
      		printf("SNMP ERROR GET");
      		$session1->close;
   	} else {
   		$output = $result->{$snmp_oid};
		$session1->close;
   	}

	return $output;
}

sub pandora_query_snmp {
	my $snmp_oid = shift;
	my $snmp_community = shift;
	my $snmp_target = shift;

	$ENV{'MIBS'}="ALL";  #Load all available MIBs
	$SNMP_TARGET = $snmp_target;
	$SNMP_COMMUNITY = $snmp_community;
	
	$SESSION = new SNMP::Session (DestHost => $SNMP_TARGET, 
					Community => $SNMP_COMMUNITY,
					Version => 1);

	# Populate a VarList with OID values.
	$APC_VLIST =  new SNMP::VarList([$snmp_oid]);
	
	# Pass the VarList to getnext building an array of the output
	@APC_INFO = $SESSION->getnext($APC_VLIST);
	
	print $APC_INFO[0];
	print "\n";
}

if ($#ARGV == -1 ){
		print "Syntax: snmptest community hostname oid\n";
		exit;
	}
my $snmp_community = $ARGV[0];
my $snmp_target = $ARGV[1];
my $snmp_oid = $ARGV[2];

pandora_query_snmp($snmp_oid, $snmp_community, $snmp_target);
