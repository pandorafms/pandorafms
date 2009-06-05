#!/usr/bin/perl
################################################################################
# Pandora XML Stress tool.
################################################################################
# Copyright (c) 2009 Ramon Novoa, rnovoa@artica.es
# Copyright (c) 2009 Artica Soluciones Tecnologicas S.L.
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation;  version 2
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
################################################################################
use strict;
use warnings;

use threads;
use threads::shared;
use Time::Local;
use Time::HiRes qw(gettimeofday);

use POSIX qw (strftime);

# Global variables used for statistics
my $Agents :shared = 0;
my $Modules :shared = 0;
my $XMLFiles :shared = 0;

my $LogLock :shared;

################################################################################
# Load the configuration file.
################################################################################
sub load_config ($\%\@) {
	my ($conf_file, $conf, $modules) = @_;

	open (FILE, "<", $conf_file) || die ("[error] Could not open configuration file '$conf_file': $!.\n\n");
	
	while (my $line = <FILE>) {

		# A module definition
		if ($line =~ m/module_begin/) {
			my %module;

			while (my $line = <FILE>) {
				last if ($line =~ m/module_end/);

				# A comment
				next if ($line =~ m/^#/);
				 
				# Unknown line
				next if ($line !~ /^\s*(\w+)\s+(.+)$/);
				
				$module{$1} = $2;
			}
			
			push (@{$modules}, \%module);
			$Modules++;
			next;
		}

		# A comment
		next if ($line =~ m/^#/);
		
		# Unknown line
		next if ($line !~ /^\s*(\w+)\s+(.+)$/);

		$conf->{$1} = $2;
	}
	close (FILE);
}

################################################################################
# Generate XML files.
################################################################################
sub generate_xml_files ($$$) {
	my ($agent_name, $conf, $modules) = @_;

	# Read agent configuration
	my $interval = get_conf_token ($conf, 'interval', '300');
	my $xml_version = get_conf_token ($conf, 'xml_version', '1.0');
	my $encoding = get_conf_token ($conf, 'encoding', 'ISO-8859-1');
	my $os_name = get_conf_token ($conf, 'os_name', 'Linux');
	my $os_version = get_conf_token ($conf, 'os_version', '2.6');
	my $temporal = get_conf_token ($conf, 'temporal', '/tmp');
	my $startup_delay = get_conf_token ($conf, 'startup_delay', '5');

	# Get time_from
	my $time_now = strftime ("%Y-%m-%d %H:%M:%S", localtime ());
	my $time_from = get_conf_token ($conf, 'time_from', $time_now);
	die ("[error] Invalid time_from: $time_from\n\n") unless ($time_from =~ /(\d+)\-(\d+)\-(\d+) +(\d+):(\d+):(\d+)/);
	my $utimestamp_from = timelocal($6, $5, $4, $3, $2 - 1, $1 - 1900);

	# Get time_to
	my $time_to = get_conf_token ($conf, 'time_to', $time_now);
	die ("[error] Invalid time_to: $time_to\n\n") unless ($time_to =~ /(\d+)\-(\d+)\-(\d+) +(\d+):(\d+):(\d+)/);
	my $utimestamp_to = timelocal($6, $5, $4, $3, $2 - 1, $1 - 1900);

	# Generate data files
	my $utimestamp = $utimestamp_from;
	while ($utimestamp < $utimestamp_to) {

		# XML header
		my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime ($utimestamp));
		my $xml_data = "<?xml version='$xml_version' encoding='$encoding'?>\n";
		$xml_data .= "<agent_data os_name='$os_name' os_version='$os_version' interval='$interval' version='$os_version' timestamp='$timestamp' agent_name='$agent_name'>\n";
		foreach my $module (@{$modules}) {

			# Skip unnamed modules
			my $module_name = get_conf_token ($module, 'module_name', '');
			next if ($module_name eq '');

			# Read module configuration
			my $module_type = get_conf_token ($module, 'module_type', 'generic_data');
			my $module_description = get_conf_token ($module, 'module_description', '');
			my $module_min = get_conf_token ($module, 'module_min', '0');
			my $module_max = get_conf_token ($module, 'module_max', '255');
			my $module_variation = get_conf_token ($module, 'module_variation', '100');
			my $module_data = get_conf_token ($module, 'module_data', '');

			# Generate module data
			$xml_data .= "\t<module>\n";
			$xml_data .= "\t\t<name>$module_name</name>\n";
			$xml_data .= "\t\t<description>$module_description</description>\n";			
			$xml_data .= "\t\t<type>$module_type</type>\n";
			my $rnd_data = generate_random_data ($module_type, $module_data, $module_min, $module_max, $module_variation);
			$module->{'module_data'} = $rnd_data;
			$xml_data .= "\t\t<data>$rnd_data</data>\n";
			$xml_data .= "\t</module>\n";
		}	

		$xml_data .= "</agent_data>\n";

		# Fix the temporal path
		my $last_char = chop ($temporal);
		$temporal .= $last_char if ($last_char ne '/');

		# Save the XML data file
		my $xml_file = $temporal . '/' . $agent_name . $utimestamp . '.data';
		open (FILE, ">", $xml_file) || die ("[error] Could not write to '$xml_file': $!.\n\n");
		print FILE $xml_data;
		close (FILE);

		copy_xml_file ($conf, $xml_file);
		$XMLFiles++;

		# First run, let the server create the new agent before sending more data
		if ($utimestamp == $utimestamp_from) {
			threads->yield ();
			sleep ($startup_delay);
		}

		$utimestamp += $interval;
	}
}

################################################################################
# Generates random data according to the module type.
################################################################################
sub generate_random_data ($$$$$) {
	my ($module_type, $current_data, $min, $max, $variation) = @_;

	my $change_rnd = int rand (100);
	return $current_data unless ($variation > $change_rnd) or $current_data eq '';
	
	# String
	if ($module_type =~ m/string/) {
		my @chars = ("A" .. "Z","a" .. "z", 0..9);
		return join ("", @chars[map {rand @chars} (1..(rand ($max - $min + 1) + $min))]);
	}

	# Proc
	if ($module_type =~ m/proc/) {
		return int rand (2);
	}
	
	# Generic data
	return int (rand ($max - $min + 1) + $min);
}

################################################################################
# Returns the value of a configuration token.
################################################################################
sub copy_xml_file ($$) {
	my ($conf, $file) = @_;
	
	my $server_ip = get_conf_token ($conf, 'server_ip', '');
	return if ($server_ip eq '');

	my $server_port = get_conf_token ($conf, 'server_port', '41121');
	my $tentacle_opts = get_conf_token ($conf, 'tentacle_opts', '');
	
	# Send the file and delete it
	`tentacle_client -a $server_ip -p $server_port $tentacle_opts $file > /dev/null 2>&1`;
	unlink ($file);

}

################################################################################
# Returns the value of a configuration token.
################################################################################
sub get_conf_token ($$$) {
	my ($hash_ref, $token, $def_value) = @_;
	
	return $def_value unless ref ($hash_ref) and defined ($hash_ref->{$token});
	return $hash_ref->{$token};
}

################################################################################
# Prints a message to the logfile.
################################################################################
sub log_message ($$) {
	my ($conf, $message) = @_;
	my $utimestamp = time ();

	my $log_file = get_conf_token ($conf, 'log_file', '');
	
	# Log to stdout
	if ($log_file eq '') {
		print "[$utimestamp] $message\n";
		return;
	}
	
	# Log to file
	{
		lock $LogLock;
		open (LOG_FILE, '>>', $log_file) || die ("[error] Could not open log file '$log_file': $!.\n\n");
		print LOG_FILE "[$utimestamp] $message\n";
		close (LOG_FILE);
	}
}

################################################################################
# Main
################################################################################
my (%conf, @modules);

# Check command line parameters
if ($#ARGV != 0) {
	print "Usage:\n\t$0 <configuration file>\n\n";
	exit 1;
}

# Load configuration file
load_config ($ARGV[0], %conf, @modules);

die ("[error] No agent file specified in configuration file.\n\n") unless defined ($conf{'agent_file'});
open (FILE, "<", $conf{'agent_file'}) || die ("[error] Could not open agent configuration file '" . $conf{'agent_file'} . "': $!.\n\n");

my $t0 = gettimeofday ();

# Launch a thread for each agent
while (my $agent_name = <FILE>) {
	chomp ($agent_name);
	threads->create (\&generate_xml_files, $agent_name, \%conf, \@modules);
	$Agents++;
}
close (FILE);

# Log some information for the user
my $time_now = strftime ("%Y-%m-%d %H:%M:%S", localtime ());
my $time_from = get_conf_token (\%conf, 'time_from', $time_now);
my $time_to = get_conf_token (\%conf, 'time_to', $time_now);
my $interval = get_conf_token (\%conf, 'interval', '300');
log_message (\%conf, "Generating XML data files for $Agents agents from $time_from to $time_to interval $interval.");

# Wait for all threads to finish
foreach my $thr (threads->list(threads::all)) {
	$thr->join ();
}

my $t1 = gettimeofday ();

# Log statistics
log_message (\%conf, "\tTotal agents:\t$Agents\n\t\tTotal modules:\t" . ($Agents * $Modules) . "\t($Modules per agent)\n\t\tTotal XML:\t$XMLFiles\t(" . int ($XMLFiles / ($t1 - $t0)) . " per second)");
