#!/usr/bin/perl
#
################################################################################
#
# WMI wizard module
# 
# Requirements:
#   wmic
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

use Data::Dumper;

my $HELP=<<EO_HELP;

Get the result of an arithmetic operation using distinct fields in a WMI query (Query must return only 1 row).

Usage: $0 [-wmicPath "<path_to_wmic>"] -host "<ip_address>" [-namespace "<namespace>"] -user "<username>" -pass "<password>" -wmiClass "<wmi_class>" -fieldsList "<class_fields_names>" [-queryFilter "<query_filter>"] -operation "<aritmetic_operation>"

-wmicPath            Path to pandorawmic command (Default: /usr/bin/pandorawmic)

-host                Target host
-namespace           WMI namespace
-user                Windows username ([domain/]username)
-pass                User password

-wmiClass            WMI class for query (Example: Win32_OperatingSystem)
-fieldsList          Comma separated class fields list (Example: TotalVisibleMemorySize,FreePhysicalMemory)
-queryFilter         WMI query class filter (Example: DeviceID = 3 AND DeviceType = 1)

-operation           Aritmetic operation to get data.
                     Macros _fN_ will be changed by fields in list.
                     Example: ((_f1_ - _f2_) * 100) / _f1_

Example: $0 -host "192.168.80.43" -user "pandora/Administrator" -pass "PandoraFMS1234" -wmiClass "Win32_OperatingSystem" -fieldsList "TotalVisibleMemorySize,FreePhysicalMemory" -operation "((_f1_ - _f2_) * 100) / _f1_"

EO_HELP

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
    'wmicPath=s',
    'host=s',
    'namespace=s',
    'user=s',
    'pass=s',
    # Query
    'wmiClass=s',
    'fieldsList=s',
    'queryFilter=s',
    # Operation
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
$config->{'wmicPath'}  = $Param{wmicPath}  || '/usr/bin/pandorawmic';
$config->{'host'}      = $Param{host}      || '';
$config->{'namespace'} = $Param{namespace} || '';
$config->{'user'}      = $Param{user}      || '';
$config->{'pass'}      = $Param{pass}      || '';

# Query parameters
$config->{'wmiClass'}    = $Param{wmiClass}    || '';
$config->{'queryFilter'} = $Param{queryFilter} || '';
$config->{'fieldsList'}  = $Param{fieldsList} || '';

# Operation
my $operation = $Param{operation} || '';

# Fields
my @fields_list = split /,/, $config->{'fieldsList'} || '';

# Verify parameters
if (!$config->{'host'} || !$config->{'user'} || !$config->{'pass'} || !$config->{'wmiClass'} || !@fields_list || !$operation){
	print $HELP;
	print "Host, user, password, WMI class, fields list and operation are required.\n";
	exit 1;
}

# Verify operation (avoid code injection)
my @operation = split //, lc($operation);

foreach my $op (@operation){
	if ($op !~ /\d/ && $op ne ' ' && $op ne '(' && $op ne ')' && $op ne '_' && $op ne '-' && $op ne '+' && $op ne '*' && $op ne '/' && $op ne 'f'){
		print $HELP;
		print "Specified operation has invalid characters: " . $op . "\n";
		exit 1;	
	}
}

# Build WMI query
my $wmi_query = 'SELECT ' . $config->{'fieldsList'} . ' FROM ' . $config->{'wmiClass'} . ($config->{'queryFilter'} ? ' WHERE ' . $config->{'queryFilter'} : '');

# Build wmic command
my $wmi_command = $config->{'wmicPath'} . ' -U ' . "'" . $config->{'user'} . '%' . $config->{'pass'} . "'" . ($config->{'namespace'} ? ' --namespace="' . $config->{'namespace'} . '"' : '') . ' //' . $config->{'host'} . ' "' . $wmi_query . '"';

# Run wmic and parse output
my $output = `$wmi_command 2>/dev/null`;

my @data = split("\n", $output);

if (index($data[0], 'CLASS: ' . $config->{'wmiClass'}) != 0) {
    print $output;
    exit 1;
}

# Parse fields positions
my @fields_pos;

my $i = 0;
foreach my $field (split /\|/, $data[1]){
	my $x = 1;
	foreach my $f (@fields_list){
		$f =~ s/^\s*//;
		$f =~ s/\s*$//;
		if (lc($field) eq lc($f)){
			$fields_pos[$i] = $x;
		}
		$x++;
	}
	$i++;
}

# Get fields values
my $fields_values = {};
$i = 0;
foreach my $field_value (split /\|/, $data[2]){
	$fields_values->{'_f' . $fields_pos[$i] . '_'} = $field_value;
	$i++;
}

# Change operation macros with values
$i = 1;
foreach my $k (keys %{$fields_values}){
	my $field_macro = '_f' . $i . '_';
	my $value = $fields_values->{$field_macro};
	$operation =~ s/$field_macro/$value/g;
	$i++;
}

# Get operation result
my $result = eval $operation;
if (defined($result)){
	print $result, "\n"; 
}
