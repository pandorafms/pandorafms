#!/usr/bin/perl

# (c) Artica ST 2010, for Pandora FMS monitoring system
# See more at http://pandorafms.org
# Licensed under GPL2 license.

use strict;
use warnings;

use XML::Simple;
use Data::Dumper;
use Sys::Hostname;
use POSIX qw(strftime);

# Default lib dir for RPM and DEB packages
use lib '/usr/lib/perl5';

use PandoraFMS::Tools;

use constant AGENT_VERSION => '3.1';


# Check parameters

if ($#ARGV != 1) {
    print "Pandora FMS GIS tool to produce XML files from a standard GPX file\n";
    print "This tool will not work with waypoints. To convert Waypoints to Tracks\n";
    print "please use another software like GPSBabel \n";
    print "This will put all the XML files in /var/spool/pandora/data_in directory \n";
    print "\n";
	print "Usage: $0 <filename.gpx> <agent_name>\n\n";
	exit 1;
}

# Configuration tokens
my %Conf = (
    'server_path' => '/var/spool/pandora/data_in',
    'interval' => 300,
    'agent_name' => hostname (),
    'description' => 'Data from GPX',
    'group' => '',
    'encoding' => 'ISO-8859-1',
);


my $file_name = shift;
my $agent_name = shift;
if (defined($agent_name)) {
	print "agent_name: $agent_name\n";
	$Conf{'agent_name'} = $agent_name;
}

my $xml_data = XMLin ($file_name, forcearray => 1 );

# Invalid XML
if ($@) {
	print "Invalid XML";
	rename($file_name, $file_name . '_BADXML');
	exit -1;
}

# Debug, code commented
# print "Printing XML DATA\n";
#print Dumper ($xml_data);
#print "Finish Printing XML DATA\n";

# Detect if Rte or Track format

my $gpx_format = "";

if (defined($xml_data->{'rte'}[0])){
    $gpx_format = "RTE";
} else {
    $gpx_format = "TRK";
}

print "Detecting GPX in $gpx_format format \n";

my $posiciones;
my $track_segment;
my $foreach_pointer;

if ($gpx_format eq "RTE"){
    $posiciones = $xml_data->{'rte'}[0];
    $foreach_pointer = $posiciones->{'rtept'}
} else {
    $posiciones = $xml_data->{'trk'}[0];
    $track_segment = $posiciones->{"trkseg"}[0];
    $foreach_pointer = $track_segment->{'trkpt'}
}

# Process positions
foreach my $position (@{$foreach_pointer}) {
    my $longitude= $position->{'lon'};
    my $latitude= $position->{'lat'};
    my $altitude= $position->{'ele'}[0];
    my $timestamp= $position->{'time'}[0];

    $timestamp =~ s/Z$//;
    $timestamp =~ s/T/ /;
    $timestamp =~ s/02/31/;
    # Use the current time
    $timestamp= strftime ('%Y/%m/%d %H:%M:%S', localtime ()); 

    print "Longitude: $longitude, Latitude: $latitude, Altitude: $altitude, Timestamp: $timestamp\n";

    my $OS = $^O;

    my $xml = "<?xml version='1.0' encoding='" . $Conf{'encoding'} . "'?>\n" .
    "<agent_data description='" . $Conf{'description'} ."' group='11".
    "' os_name='$OS' os_version='1' interval='" . $Conf{'interval'} .
    "' version='" . AGENT_VERSION .  "' timestamp='" . $timestamp.
    "' agent_name='" . $Conf{'agent_name'} . "' timezone_offset='0' longitude='" .$longitude.
    "' latitude='" .$latitude."' altitude='".$altitude."'>\n";
    $xml .= "<module>";
    $xml .= "    <name><![CDATA[gps_data]]></name>";
    $xml .= "    <description><![CDATA[GPS Data export from GPX source]]></description>";
    $xml .= "    <type>generic_proc</type>";
    $xml .= "    <data><![CDATA[1]]></data>";
    $xml .= "</module>";
    $xml .= "</agent_data>";

#    print $xml;

    # Save XML data file

    my $temp_file = $Conf{'server_path'} . '/' . $Conf{'agent_name'} . '.' . time () . '.data';
    open (TEMP_FILE, "> $temp_file") ||print ("Could not write XML data file: $!");
    print TEMP_FILE $xml;
    close (TEMP_FILE);
    sleep(1);
}

