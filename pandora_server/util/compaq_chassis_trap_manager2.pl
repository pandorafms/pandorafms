#!/usr/bin/perl
# (c) Sancho Lerena 2010 <slerena@artica.es>
# Specific Pandora FMS trap collector for Compaq Hardware

# Parameter list: list_event, code with TRAP VALUES to match
#                 module_name: Name of the module generated.

my @list_event = ('22013', '22042', '22039');
my $module_name = "evento_enclosure";

use POSIX qw(setsid strftime);

sub show_help {
	print "\nSpecific Pandora FMS trap collector for compaq Hardware\n";
	print "(c) Sancho Lerena 2010 <slerena@artica.es>\n";
	print "Usage:\n\n";
	print "   compaq_chassis_trap_manager.pl <destination_agent_name> <TRAP DATA>\n\n";
	exit;
}

sub writexml {
	my ($hostname, $xmlmessage ) = @_;
	my $file = "/var/spool/pandora/data_in/$hostname.".rand(1000).".data";

#	my $file = "/tmp/compaq.debug";
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
my $xmldata = "<agent_data agent_name='$target_host' timestamp='$now' version='1.0' os='Other' os_version='N/A' interval='9999999999'>";

my $blade = "N/A";
my $index_pos = 1;
my $enclosure = "N/A";
my $rack = "N/A";

# Get position
if ($chunk =~ m/.1.3.6.1.4.1.232.22.2.4.1.1.1.8.([0-9])*\s/){
	$index_pos = $1;
}

# Get blade 
if ($chunk =~ m/.1.3.6.1.4.1.232.22.2.4.1.1.1.4.$index_pos \= STRING\: ([A-Za-z0-9\-\.]*)\s/){
	$blade = $1;
}

# Get enclosure
if ($chunk =~ m/.1.3.6.1.4.1.232.22.2.4.1.1.1.5.$index_pos \= STRING\: ([A-Za-z0-9\-\.]*)\s/){
	$enclosure = $1;
}

# Get rack
if ($chunk =~ m/1.3.6.1.4.1.232.22.2.2.1.1.2.1 \= STRING\: ([A-Za-z0-9\-\.]*)\s\.1/){
	$rack = $1;
}

my $event_code = "";
foreach $argnum (0 .. $#list_event) {
	if ($chunk =~ m/\s\.($list_event[$argnum])\s/){
		$text = chunk;
		$event_code = $1;
	}
}

$xmldata .= 
"<module><name>$module_name_$event_code</name><type>async_string</type><data><![CDATA[$text]]></data></module>\n";
$xmldata .= "</agent_data>\n";

writexml ($target_host, $xmldata);


