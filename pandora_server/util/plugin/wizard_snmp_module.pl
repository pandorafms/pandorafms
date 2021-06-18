#!/usr/bin/perl
#
################################################################################
#
# SNMP wizard module
# 
# Requirements:
#   Net::SNMP
#   Crypt::DES
#   Digest::SHA1
# 
# (c) Enrique Martin Garcia <enrique.martin@artica.es>
#
# 2020/03/10
#
################################################################################

use strict;
use warnings;

use POSIX qw(strftime);

use Encode;
use Encode::Locale;
use Getopt::Long;
use Net::SNMP;
use Net::SNMP::Security::USM;

my $HELP=<<EO_HELP;

Get the result of an arithmetic operation using several OIDs values.

Usage: $0 -host "<ip_address>" -version "<snmp_version>" [SNMP] [SNMPv3] -oidList "<oid1>,<oid2>" -operation "<aritmetic_operation>"

-host                Target host
-version             SNMP version (1, 2c, 3)

-oidList             Comma separated OIDs used
-operation           Aritmetic operation to get data.
                     Macros _oN_ will be changed by OIDs in list.
                     Example: (_o1_ * 100) / _o2_

[SNMP]
    -community       Community (only version 1 and 2c)
    -port            Target UDP port (Default 161)

[SNMPv3]
    -user            Username
    -authMethod      Authentication method (MD5, SHA)
    -authPass        Authentication password
    -privMethod      Privacy method (DES, AES)
    -privPass        Privacy password
    -secLevel        Security level (noAuthNoPriv, authNoPriv, authPriv)

Example: $0 -host 192.168.80.43 -community public -version 1 -oidlist "1.3.6.1.2.1.2.2.1.10.1,1.3.6.1.2.1.2.2.1.16.1" -operation "_o1_ + _o2_"

EO_HELP

#
# FUNCTIONS
##############

sub new_snmp_target {
	my ($config) = @_;
	my $target;
	my $error;

	if ($config->{'version'} ne '3'){
		($target, $error) = Net::SNMP->session(
			-hostname      => $config->{'host'},
			-port          => $config->{'port'},
			-version       => $config->{'version'},
			-timeout       => $config->{'timeout'},
			-translate     => 0,
			-community     => $config->{'community'}
		);		
	}else{
		if ($config->{'sec_level'} =~ /^noAuthNoPriv$/i){
			($target, $error) = Net::SNMP->session(
				-hostname      => $config->{'host'},
				-port          => $config->{'port'},
				-version       => $config->{'version'},
				-timeout       => $config->{'timeout'},
				-translate     => 0,
				-username      => $config->{'user'}
			);
		}elsif ($config->{'sec_level'} =~ /^authNoPriv$/i){
			($target, $error) = Net::SNMP->session(
				-hostname      => $config->{'host'},
				-port          => $config->{'port'},
				-version       => $config->{'version'},
				-timeout       => $config->{'timeout'},
				-translate     => 0,
				-username      => $config->{'user'},
				-authpassword  => $config->{'auth_pass'},
				-authprotocol  => $config->{'auth_method'}
			);
		}elsif ($config->{'sec_level'} =~ /^authPriv$/i){
			($target, $error) = Net::SNMP->session(
				-hostname      => $config->{'host'},
				-port          => $config->{'port'},
				-version       => $config->{'version'},
				-timeout       => $config->{'timeout'},
				-translate     => 0,
				-username      => $config->{'user'},
				-authpassword  => $config->{'auth_pass'},
				-authprotocol  => $config->{'auth_method'},
				-privpassword  => $config->{'priv_pass'},
				-privprotocol  => $config->{'priv_method'}
			);
		}
	}


	return ($target, $error);
}

sub snmp_walk {
	my ($target, $oid) = @_;
	my $result = {};

	my $walk = $target->get_table(
		-baseoid => $oid,
	);

	if (defined($walk)){
		$result = $walk;
	}

	return $result;
}

sub snmp_get {
	my ($target, $oid) = @_;
	my $result = '';

	my $get = $target->get_request(
		-varbindlist => [$oid],
	);

	if (defined($get)){
		$result = $get->{$oid};
	}

	return $result;
}

#
# MAIN
##############

@ARGV = map { decode(locale => $_, 1) } @ARGV if -t STDIN;
binmode STDOUT, ":encoding(console_out)" if -t STDOUT;
binmode STDERR, ":encoding(console_out)" if -t STDERR;

my %Param = ();
GetOptions(
    \%Param,
    # General
    'community=s',
    'version=s',
    'host=s',
    'port=s',
    'timeout=s',
    # Version 3
    'user=s',
    'authMethod=s',
    'authPass=s',
    'privMethod=s',
    'privPass=s',
    'secLevel=s',
    # Operation
    'oidList=s',
    'operation=s',
    # Help option
    'Help',
);

if ($Param{Help}){
    print $HELP;
    exit 0;
}

my $config;

# General parameters
$config->{'community'}   = $Param{community}  || '';
$config->{'version'}     = $Param{version}    || '';
$config->{'host'}        = $Param{host}       || '';
$config->{'port'}        = $Param{port}       || '161';
$config->{'timeout'}     = $Param{timeout}    || '2';

# Version 3 parameters
$config->{'auth_method'} = $Param{authMethod} || '';
$config->{'user'}        = $Param{user}       || '';
$config->{'auth_pass'}   = $Param{authPass}   || '';
$config->{'priv_method'} = $Param{privMethod} || '';
$config->{'priv_pass'}   = $Param{privPass}   || '';
$config->{'sec_level'}   = $Param{secLevel}   || '';

$config->{'auth_method'} = uc($config->{'auth_method'});
$config->{'priv_method'} = uc($config->{'priv_method'});

# Operation
my $operation = $Param{operation}  || '';

# OIDs
my @oid_list = split /,/, $Param{oidList} || '';

# Verify parameters
if (!$config->{'host'} || !$config->{'version'} || !$operation || !@oid_list){
	print $HELP;
	print "Host, version, OID list and operation are required.\n";
	exit 1;
}

if ($config->{'version'} ne '1' && $config->{'version'} ne '2c' && $config->{'version'} ne '3'){
	print $HELP;
	print "Invalid SNMP version provided.\n";
	exit 1;
}

if ($config->{'version'} eq '1' || $config->{'version'} eq '2c'){
	if (!$config->{'community'}){
		print $HELP;
		print "SNMP community required for version 1 or 2c.\n";
		exit 1;
	}
}

if ($config->{'version'} eq '3'){
	if ($config->{'sec_level'} =~ /^noAuthNoPriv$/i){
		if (!$config->{'user'}){
			print $HELP;
			print "Username required for SNMP version 3 and security level 'noAuthNoPriv'.\n";
			exit 1;
		}
	}elsif ($config->{'sec_level'} =~ /^authNoPriv$/i){
		if (!$config->{'user'} && !$config->{'auth_pass'} && !$config->{'auth_method'}){
			print $HELP;
			print "Username, authentication password and authentication method required for SNMP version 3 and security level 'authNoPriv'.\n";
			exit 1;
		}
	}elsif ($config->{'sec_level'} =~ /^authPriv$/i){
		if (!$config->{'user'} && !$config->{'auth_pass'} && !$config->{'auth_method'} && !$config->{'priv_pass'} && !$config->{'priv_method'}){
			print $HELP;
			print "Username, authentication password, authentication method, privacy password and privacy method required for SNMP version 3 and security level 'authPriv'.\n";
			exit 1;
		}
	}else{
		print $HELP;
		print "Invalid SNMP security level provided for version 3.\n";
		exit 1;
	}

	if ($config->{'auth_method'} && $config->{'auth_method'} ne 'MD5' && $config->{'auth_method'} ne 'SHA'){
		print $HELP;
		print "Invalid SNMP authentication method provided for version 3.\n";
		exit 1;
	}

	if ($config->{'priv_method'} && $config->{'priv_method'} ne 'DES' && $config->{'priv_method'} ne 'AES'){
		print $HELP;
		print "Invalid SNMP privacy method provided for version 3.\n";
		exit 1;
	}
}

# Verify operation (avoid code injection)
my @operation = split //, lc($operation);

foreach my $op (@operation){
	if ($op !~ /\d/ && $op ne ' ' && $op ne '(' && $op ne ')' && $op ne '_' && $op ne '-' && $op ne '+' && $op ne '*' && $op ne '/' && $op ne 'o'){
		print $HELP;
		print "Specified operation has invalid characters: " . $op . "\n";
		exit 1;	
	}
}

# Create SNMP target
my ($target, $error) = new_snmp_target($config);

if (!$target){
	print $error . "\n";
	exit 1;
}

# Get OIDs values
my $oid_values = {};
my $i = 1;
foreach my $oid (@oid_list){
	$oid_values->{'_o' . $i . '_'} = snmp_get($target, $oid);
	$i++;
}

# Change operation macros with values
$i = 1;
foreach my $k (keys %{$oid_values}){
	my $oid_macro = '_o' . $i . '_';
	my $value = $oid_values->{$oid_macro};
	$operation =~ s/$oid_macro/$value/g;
	$i++;
}

# Get operation result
my $result = eval $operation;
if (defined($result)){
	print $result . "\n";
}