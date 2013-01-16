#!/usr/bin/perl
##################################################################################
# CSV/XML Bridge Tool for Pandora FMS 
# (c) Sancho Lerena 2012, slerena@gmail.com
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
use Getopt::Long;

my $version = "v1.0";

sub print_usage () {
	print "Usage: $0 $version\n\n";
	print "\t-A <field position for Agent> \n";
	print "\t-Y <field position for module data value> \n";
	print "\t-T <module type, by default is 'generic_data'> \n";
	print "\t-O <field position for module description> \n";
	print "\t-N <field position for agent description> \n";
	print "\t-M <module name> \n";
	print "\t-X <field position for data timestamp> \n";
	print "\t-I <agent interval (in secs)> \n";
	print "\t-G <field position for agent group> \n";

	print "\t-s <Skip # firt lines (2 by default)> \n";
	print "\t-c <character delimitator>\n";
	print "\t-f <CSV file to process>\n";
	print "\t-d <destination directory>\n";
	print "\t-R Is used to dump header fields / order, to select \n";
	print "\t   what fields you need to parse\n";

	print "\t-hV Help (this help)\n";
	print "\nSample Usage:\n\n";
	print "\tperl pandora_csv_bridge.pl -f datos4.csv -c @ -A 2 -Y 26 -M Consumo_Electrico -d /tmp/dump -G 3 -X 12\n\n";
    exit;
}

sub transform_date ($){
	#'2012/03/26 19:37:22	# Output format needed
	my $orig_data = $_[0];
	my $date;

	my @t1;
	if ($orig_data =~ /\//){
		@t1 = split ("/", $orig_data);
	} else {
		@t1 = split ("-", $orig_data);
	}
	
	# We asume 3rd digit is YEAR in "two digits" format
	# 2nd field is DAY and 1st field is month
	if ($t1[0] < 12){
		$date = "20".$t1[2]."/".$t1[0]."/".$t1[1]. " 00:00:00";
	} else {
		$date = "20".$t1[2]."/".$t1[1]."/".$t1[0]. " 00:00:00";
	}
	return $date;
}

my ($opt_v, $opt_h, $opt_d, $opt_c, $opt_f, $opt_s, $opt_R, $opt_A, $opt_O, $opt_Y, $opt_T, $opt_X, $opt_I,  $opt_M, $opt_G, $opt_N);

# Default values 
$opt_N = "";
$opt_G = "Servers";
$opt_I = "300";
$opt_I = "300";
$opt_s = 2;
$opt_T = "generic_data";

my $utimestamp;
my $utimestamp_extra;

if ( $ARGV[0] eq "" ) {
	print_usage();	
}

GetOptions
        ("h"   => \$opt_h, 
		 "R"   => \$opt_R, 
		 "help" => \$opt_h,
	     "c=s" => \$opt_c,
         "f=s" => \$opt_f, 
		 "d=s" => \$opt_d,
		 "s=s" => \$opt_s,
		 "M=s" => \$opt_M,
		 "N=s" => \$opt_N,
		 "A=s" => \$opt_A,
		 "O=s" => \$opt_O,
		 "Y=s" => \$opt_Y,
		 "T=s" => \$opt_T,
		 "X=s" => \$opt_X,
		 "G=s" => \$opt_G,
		 "I=s" => \$opt_I,
         "v" => \$opt_v, 
		 "verbose" => \$opt_v);

if ($opt_v) {
    print "\n";
    print "$0 Pandora FMS CSV / XML Bridge Tool $version\n";
    print "\n";
    print_usage();
} 

if ($opt_h) {
    print_usage();
}

if (!defined($opt_R)){
	if ( !defined($opt_f)){
		print_usage();
	}

	if ( !defined($opt_X)){
		print_usage();
	}
}

# DEBUG
#print "Opening file $opt_f with CSV character $opt_c Agent name $opt_A Timestamp Field $opt_X \n";

open (FIDATA, "< $opt_f");
my @data;
my $header;

# Skip first $opt_s lines of File
my $ax; my $temp_skip;
for ($ax = 0; $ax < $opt_s; $ax++){
	if ($ax == 0) {
		$header = <FIDATA>;
	}
	else {
		$temp_skip = <FIDATA>;
		$header = $temp_skip;
	}
}

# Dump header
if ( defined($opt_R)){

	if (!defined($opt_c)){
		print_usage();
	}
	
	my %header_data = split ("$opt_c", $header );
	my $key;

	print "\nDumping CSV Structure (Field Label -> Field Order)\n\n";

	foreach $key (keys %header_data){
		print $header_data{$key} ." -> ". $key ." \n";
	}

	exit;
}

# Begin process file
my $buffer_line;
my $filename;
my $counter = 1;

print "Generating data from CSV file $opt_f, with CSV character $opt_c Agent name $opt_A Timestamp Field $opt_X \n";

while (<FIDATA>){
	$counter++;
    $buffer_line = $_;
	@data = split ("$opt_c", $buffer_line);
	
	# Let's create the damm XML for each line
	$utimestamp = time ();
	$utimestamp_extra = int(rand(10000));
	$utimestamp = $utimestamp . $utimestamp_extra;
	$utimestamp = $utimestamp + $counter;

	$filename = $opt_d."/". $data[$opt_A].".".$utimestamp.".data";

	open (OUTDATA, "> $filename");
	print OUTDATA "<?xml version='1.0' encoding='ISO-8859-1'?>";
	print OUTDATA "<agent_data description='$data[$opt_N]' group='$data[$opt_G]' os_name='other' os_version='1.0' interval='$opt_I' version='4.0.1' timestamp='".transform_date($data[$opt_X])."' agent_name='$data[$opt_A]' timezone_offset='0'>";

	print OUTDATA "<module>
	<name>$opt_M</name>
	<type>$opt_T</type>
	<description>$data[$opt_O]</description>
	<data>$data[$opt_Y]</data>
	</module>";
	print OUTDATA "</agent_data>";
	close (OUTDATA);

}
