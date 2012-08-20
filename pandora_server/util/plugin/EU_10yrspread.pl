#!/usr/bin/perl

use strict;
use warnings;

use LWP::Simple;
use POSIX qw(strftime);

sub main
{
	my $agent_name = "EU_10yrspread";
	my $incoming_dir = "/var/spool/pandora/data_in";
	
	my $utimestamp = time ();
	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime());
	
	my %codes;

	$codes{'Spain'} = '.SPAINGER10:IND';
	$codes{'Greece'} = '.GRGER10:IND';
	$codes{'Italy'} = '.ITAGER10:IND';
	$codes{'Portugal'} = '.PORGER10:IND';
	$codes{'Ireland'} = '.IRGERSP:IND';
	$codes{'France'} = '.FRAGER10:IND';
	$codes{'Belgium'} = '.BELGER10:IND';
	
	my $xml_output = "<?xml version='1.0' encoding='ISO-8859-1' ?>";
	$xml_output .= "<agent_data os_name='Linux' agent_name='".$agent_name."' interval='300' timestamp='".$timestamp."' >";

	foreach my $k (keys(%codes)) {
		my $code = $codes{$k};
		my $spread = get_10yspread($code)*100;

		$xml_output .=" <module>";
		$xml_output .=" <name><![CDATA[$k]]></name>";
		$xml_output .=" <type>generic_data</type>";
		$xml_output .=" <data>$spread</data>";
		$xml_output .=" <min_warning>250</min_warning>";
		$xml_output .=" <max_warning>0</max_warning>";
		$xml_output .=" <min_critical>500</min_critical>";
		$xml_output .=" <max_critical>0</max_critical>";
		$xml_output .=" </module>";
	}
	
	$xml_output .= "</agent_data>";
	
	my $filename = $incoming_dir."/".$agent_name.".".$utimestamp.".data";
	
	open (XMLFILE, ">> $filename") or die "[FATAL] Could not open internal monitoring XML file for deploying monitorization at '$filename'";
	print XMLFILE $xml_output;
	close (XMLFILE);

	# return OK when the execution is complete	
	print "OK";
}
	

	
sub get_10yspread($) {
	my $code = shift;

	my $data = get("http://www.bloomberg.com/quote/".$code);


	my @lines =  split(/\n/,$data);
	my $start_parse = 0;
	my $stop_parse = 0;
	my $spread = '';

	foreach my $line (@lines) {
		if($start_parse == 1) {
			$spread .= $line;
			if($line =~ /<\/span>/gi) {
				last;	
			}
		}

		if($line =~ /.*<h3.*$code.*<\/h3>/gi) {
			$start_parse = 1;
		}
	}

	if($spread =~ /.*<span.*>\s*(\S+)\s*<\/span>/i) {
		return $1;
	}
}

main();

