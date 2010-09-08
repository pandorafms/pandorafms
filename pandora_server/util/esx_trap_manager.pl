#!/usr/bin/perl
# (c) Sancho Lerena 2010 <slerena@artica.es>
# Specific Pandora FMS trap collector for ESX 

use POSIX qw(setsid strftime);

sub show_help {
	print "\nSpecific Pandora FMS trap collector for ESX\n";
	print "(c) Sancho Lerena 2010 <slerena@artica.es>\n";
	print "Usage:\n\n";
	print "   esx_trap_manager.pl <destination_agent_name> <TRAP DATA>\n\n";
	exit;
}

sub writexml {
	my ($hostname, $xmlmessage ) = @_;
	my $file = "/var/spool/pandora/data_in/$hostname.".rand(1000).".data";

	open (FILE, ">> $file") or die "[FATAL] Cannot write to XML '$file'";
	print FILE $xmlmessage;
	close (FILE);
}

if ($#ARGV == -1){
	show_help();
}

$chunk = "";

# First parameter is always destination host for virtual server
$target_host = $ARGV[0];

foreach $argnum (1 .. $#ARGV) {
	if ($chunk ne ""){
		$chunk .= " ";
	}
	$chunk .= $ARGV[$argnum];
}

my $hostname = "";
my $now = strftime ("%Y-%m-%d %H:%M:%S", localtime());
my $xmldata = "<agent_data agent_name='$target_host' timestamp='$now' version='1.0' os='Other' os_version='ESX_Collectordime ' interval='9999999999'>";

if ($chunk =~ m/.1.3.6.1.4.1.6876.4.3.302 \= STRING\: ([A-Za-z0-9\-\.]*)\s\.1/){
	$hostname = "_".$1;
}

if ($chunk =~ m/Host cpu usage \- Metric Usage \= ([0-9]*)\z/){
	$value = $1;
	$module_name = "CPU_OCUPADA$hostname";
}

if ($chunk =~ m/Host memory usage \- Metric Usage = ([0-9\.]*)\z/){
	$value = $1;
	$module_name = "MEMORIA_OCUPADA$hostname";
}

if ($chunk =~ m/Datastore usage on disk \- Metric Storage space actually used \= ([0-9\.]*)\z/){
	$value = $1;
	$module_name = "DISCO_OCUPADO$hostname";
}

$xmldata .= "<module><name>$module_name</name><type>async_data</type><data>$value</data></module>\n";

$xmldata .= "</agent_data>\n";
writexml ($target_host, $xmldata);


