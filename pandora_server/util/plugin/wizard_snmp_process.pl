#!/usr/bin/perl
#
################################################################################
#
# SNMP wizard process
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

my $HELP=<<EO_HELP;

Check if a process is running (1) or not (0) in OID .1.3.6.1.2.1.25.4.2.1.2 SNMP tree.

Usage: $0 -host "<ip_address>" -version "<snmp_version>" [SNMP] [SNMPv3] -process "<process_name>"

-host                Target host
-version             SNMP version (1, 2c, 3)

-process             Process name to check if is running (case sensitive)

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

Example: $0 -host 192.168.80.43 -community public -version 1 -process "httpd"

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
    # Process
    'process=s',
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
my $process = $Param{process}  || '';

# Verify parameters
if (!$config->{'host'} || !$config->{'version'} || !$process){
	print $HELP;
	print "Host, version and process are required.\n";
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

# Create SNMP target
my ($target, $error) = new_snmp_target($config);

if (!$target){
	print $error . "\n";
	exit 1;
}

# Get all running processes
my $processes = snmp_walk($target, '.1.3.6.1.2.1.25.4.2.1.2');

my $result = 0;

# Search process name
foreach my $k (keys %{$processes}){
	if ($processes->{$k} eq $process){
		$result = 1;
		last;
	}
}

print $result . "\n";