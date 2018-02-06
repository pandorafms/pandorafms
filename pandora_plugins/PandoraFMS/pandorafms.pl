#!/usr/bin/perl
# Pandora FMS server monitoring plugin


use strict;
use warnings;
use POSIX qw(strftime);
use PandoraFMS::DB;

use constant DATASERVER => 0;
use Scalar::Util qw(looks_like_number);


my $RDBMS = "mysql";


####
# Erase blank spaces before and after the string 
####################################################
sub trim($){
	my $string = shift;
	if (empty ($string)){
		return "";
	}

	chomp ($string);
	return $string;
}

#####
# Empty
###################################
sub empty($){
	my $str = shift;

	if (! (defined ($str)) ){
		return 1;
	}

	if(looks_like_number($str)){
		return 0;
	}

	if ($str =~ /^\ *[\n\r]{0,2}\ *$/) {
		return 1;
	}
	return 0;
}

#####
## General configuration file parser
##
## log=/PATH/TO/LOG/FILE
##
#######################################
sub parse_configuration($){
	my $conf_file = shift;
	my %config;

	open (FILE,"<", "$conf_file") or return undef;

	while (<FILE>){
		if (($_ =~ /^ *$/)
		 || ($_ =~ /^#/ )){
		 	# skip blank lines and comments
			next;
		}
		my @parsed = split /\ /, $_, 2;
		$config{trim($parsed[0])} = trim($parsed[1]);
	}
	close (FILE);

	return %config;
}

sub disk_free ($) {
	my $target = $_[0];

	# Try to use df command with Posix parameters... 
	my $command = "df -k -P ".$target." | tail -1 | awk '{ print \$4/1024}'";
	my $output = trim(`$command`);
	return $output;
}

sub load_average {
	my $load_average;

	my $OSNAME = $^O;

	if ($OSNAME eq "freebsd"){
		$load_average = ((split(/\s+/, `/sbin/sysctl -n vm.loadavg`))[1]);
	}
	# by default LINUX calls
	else {
		$load_average = `cat /proc/loadavg | awk '{ print \$1 }'`;
	}
	return trim($load_average);
}


sub free_mem {
	my $free_mem;

	my $OSNAME = $^O;

	if ($OSNAME eq "freebsd"){
		my ($pages_free, $page_size) = `/sbin/sysctl -n vm.stats.vm.v_page_size vm.stats.vm.v_free_count`;
		# in kilobytes
		$free_mem = $pages_free * $page_size / 1024;

	}
	elsif ($OSNAME eq "netbsd"){
		$free_mem = `cat /proc/meminfo | grep MemFree | awk '{ print \$2 }'`;
	}
	# by default LINUX calls
	else {
		$free_mem = `free | grep Mem | awk '{ print \$4 }'`;
	}
	return trim($free_mem);
}

sub pandora_self_monitoring ($$) {
	my ($pa_config, $dbh) = @_;
	my $timezone_offset = 0; # PENDING (TODO) !
	my $utimestamp = time ();
	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime());

	my $xml_output = "";
	
	$xml_output .=" <module>\n";
	$xml_output .=" <name>Status</name>\n";
	$xml_output .=" <type>generic_proc</type>\n";
	$xml_output .=" <data>1</data>\n";
	$xml_output .=" </module>\n";

	my $load_average = load_average();
	$load_average = '' unless defined ($load_average);
	my $free_mem = free_mem();
	$free_mem = '' unless defined ($free_mem);
	my $free_disk_spool = disk_free ($pa_config->{"incomingdir"});
	$free_disk_spool = '' unless defined ($free_disk_spool);
	my $my_data_server = trim(get_db_value ($dbh, "SELECT id_server FROM tserver WHERE server_type = ? AND name = '".$pa_config->{"servername"}."'", DATASERVER));

	# Number of unknown agents
	my $agents_unknown = 0;
	if (defined ($my_data_server)) {
		$agents_unknown = trim(get_db_value ($dbh, "SELECT COUNT(DISTINCT tagente_estado.id_agente) "
		                                       . "FROM tagente_estado, tagente, tagente_modulo "
		                                       . "WHERE tagente.disabled = 0 AND tagente.id_agente = tagente_estado.id_agente "
		                                       . "AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo "
		                                       . "AND tagente_modulo.disabled = 0 "
		                                       . "AND running_by = $my_data_server "
		                                       . " AND estado = 3"));
		$agents_unknown = 0 if (!defined($agents_unknown));
	}
	
	my $queued_modules = trim(get_db_value ($dbh, "SELECT SUM(queued_modules) FROM tserver WHERE name = '".$pa_config->{"servername"}."'"));
	
	if (!defined($queued_modules)) {
		$queued_modules = 0;
	}
	
	my $dbmaintance;
	if ($RDBMS eq 'postgresql') {
		$dbmaintance = trim(get_db_value ($dbh,
			 "SELECT COUNT(*) "
			. "FROM tconfig "
			. "WHERE token = 'db_maintance' "
				. "AND NULLIF(value, '')::int > UNIX_TIMESTAMP() - 86400"));
	}
	elsif ($RDBMS eq 'oracle') {
		$dbmaintance = trim (get_db_value ($dbh,
			"SELECT COUNT(*) "
			. "FROM tconfig "
			. "WHERE token = 'db_maintance' AND DBMS_LOB.substr(value, 100, 1) > UNIX_TIMESTAMP() - 86400"));
	}
	else {
		$dbmaintance = trim (get_db_value ($dbh,
			"SELECT COUNT(*)"
			. "FROM tconfig "
			. "WHERE token = 'db_maintance' AND value > UNIX_TIMESTAMP() - 86400"));
	}


	$xml_output .=" <module>\n";
	$xml_output .=" <name>Database Maintenance</name>\n";
	$xml_output .=" <type>generic_proc</type>\n";
	$xml_output .=" <data>$dbmaintance</data>\n";
	$xml_output .=" </module>\n";
	
	$xml_output .=" <module>\n";
	$xml_output .=" <name>Queued_Modules</name>\n";
	$xml_output .=" <type>generic_data</type>\n";
	$xml_output .=" <data>$queued_modules</data>\n";
	$xml_output .=" </module>\n";
	
	$xml_output .=" <module>\n";
	$xml_output .=" <name>Agents_Unknown</name>\n";
	$xml_output .=" <type>generic_data</type>\n";
	$xml_output .=" <data>$agents_unknown</data>\n";
	$xml_output .=" </module>\n";
	
	$xml_output .=" <module>\n";
	$xml_output .=" <name>System_Load_AVG</name>\n";
	$xml_output .=" <type>generic_data</type>\n";
	$xml_output .=" <data>$load_average</data>\n";
	$xml_output .=" </module>\n";
	
	$xml_output .=" <module>\n";
	$xml_output .=" <name>Free_RAM</name>\n";
	$xml_output .=" <type>generic_data</type>\n";
	$xml_output .=" <data>$free_mem</data>\n";
	$xml_output .=" </module>\n";
	
	$xml_output .=" <module>\n";
	$xml_output .=" <name>FreeDisk_SpoolDir</name>\n";
	$xml_output .=" <type>generic_data</type>\n";
	$xml_output .=" <data>$free_disk_spool</data>\n";
	$xml_output .=" </module>\n";
	
	return $xml_output;
}

#######################################
#
#
#   MAIN
#
#
#######################################


if ($#ARGV < 0) {
	print STDERR "Needed 1 argument as last\nUsage:\n$0 pandora_server.conf\n";
	exit 1;
}

my %config = parse_configuration($ARGV[0]);


$config{version} = "6.0";
$config{'servername'} = trim (`hostname`) if (!(defined ($config{'servername'})));
$RDBMS = $config{dbengine};

# ($rdbms, $db_name, $db_host, $db_port, $db_user, $db_pass)
my $dbh = db_connect($RDBMS, $config{dbname}, $config{dbhost}, $config{dbport}, $config{dbuser}, $config{dbpass});


my $xml_output = pandora_self_monitoring(\%config, $dbh);


db_disconnect($dbh);


print $xml_output;
exit 0;