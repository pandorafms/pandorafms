#!/usr/bin/perl

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
