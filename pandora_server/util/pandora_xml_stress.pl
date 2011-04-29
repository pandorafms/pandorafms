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

use POSIX qw (strftime ceil);

use Data::Dumper;
use Math::Trig;

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

			# A comment
			next if ($line =~ m/^#/);

			while (my $line = <FILE>) {

				# A comment
				next if ($line =~ m/^#/);

				last if ($line =~ m/module_end/);
				 
				# Unknown line
				next if ($line !~ /^\s*(\w+)\s+(.+)$/);
				
				$module{$1} = $2;
			}
			
			push (@{$modules}, \%module);
			$Modules++;
			next;
		}
		
		# Unknown line
		next if ($line !~ /^\s*(\w+)\s+(.+)$/);

		$conf->{$1} = $2;
	}
	close (FILE);
}

################################################################################
# Generate XML files.
################################################################################
sub generate_xml_files ($$$$$) {
	my ($agents, $start, $step, $conf, $modules) = @_;

	# Read agent configuration
	my $interval = get_conf_token ($conf, 'agent_interval', '300');
	my $xml_version = get_conf_token ($conf, 'xml_version', '1.0');
	my $encoding = get_conf_token ($conf, 'encoding', 'ISO-8859-1');
	my $os_name = get_conf_token ($conf, 'os_name', 'Linux');
	my $os_version = get_conf_token ($conf, 'os_version', '2.6');
	my $temporal = get_conf_token ($conf, 'temporal', '/tmp');
	my $startup_delay = get_conf_token ($conf, 'startup_delay', '5');
	my $ag_timezone_offset = get_conf_token ($conf, 'timezone_offset', '0');
	my $ag_timezone_offset_range = get_conf_token ($conf, 'timezone_offset_range', '0');
	my $latitude_base = get_conf_token ($conf, 'latitude_base', '40.42056');
	my $longitude_base = get_conf_token ($conf, 'longitude_base', '-3.708187');
	my $altitude_base = get_conf_token ($conf, 'altitude_base', '0');
	my $position_radius = get_conf_token ($conf, 'position_radius', '10');

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
	while ($utimestamp <= $utimestamp_to) {
		for (my $i = $start; $i < $start + $step; $i++) {

			# Get the name of the agent
			last unless defined ($agents->[$i]);
			my $agent_name = $agents->[$i];
			# Agent random position
			my $ag_latitude = $latitude_base + (rand ($position_radius) - $position_radius/2)/100;
			my $ag_longitude = $longitude_base + (rand ($position_radius) - $position_radius/2)/100;
			my $ag_altitude = $altitude_base + (rand ($position_radius) - $position_radius/2)/100;
			
			# XML header
			my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime ($utimestamp));
			my $xml_data = "<?xml version='$xml_version' encoding='$encoding'?>\n";
			my $sign = int rand(2);
			$ag_timezone_offset += ($sign*(-1)+(1-$sign)) * int rand($ag_timezone_offset_range);
			$xml_data .= "<agent_data os_name='$os_name' os_version='$os_version' interval='$interval' version='$os_version' timestamp='$timestamp' agent_name='$agent_name' timezone_offset='$ag_timezone_offset' longitude='$ag_longitude' latitude='$ag_latitude' altitude='$ag_altitude'>\n";
			foreach my $module (@{$modules}) {

				# Skip unnamed modules
				my $module_name = get_conf_token ($module, 'module_name', '');
				next if ($module_name eq '');

				# Read module configuration
				my $module_type = get_conf_token ($module, 'module_type', 'generic_data');
				my $module_description = get_conf_token ($module, 'module_description', '');
				#my $module_min = get_conf_token ($module, 'module_min', '0');
				#my $module_max = get_conf_token ($module, 'module_max', '255');
				#my $module_variation = get_conf_token ($module, 'module_variation', '100');
				#my $module_data = get_conf_token ($module, 'module_data', '');
				
				# Extract the data config for the generator.
				my $generation_type = get_generation_parameter($module, 'type', 'RANDOM');
				my $module_variation = get_generation_parameter($module, 'variation', '100');
				my $module_min = get_generation_parameter($module, 'min', '0');
				my $module_max = get_generation_parameter($module, 'max', '255');
				my $module_data = get_generation_parameter($module, 'data', '');
				my $module_prob = get_generation_parameter($module, 'prob', '50');
				my $module_avg = get_generation_parameter($module, 'avg', '127');
				my $module_time_wave_length = get_generation_parameter($module, 'time_wave_length', '0');
				my $module_time_offset = get_generation_parameter($module, 'time_offset', '0');
				
				# Generate module data
				$xml_data .= "\t<module>\n";
				$xml_data .= "\t\t<name>$module_name</name>\n";
				$xml_data .= "\t\t<description>$module_description</description>\n";			
				$xml_data .= "\t\t<type>$module_type</type>\n";
				
				
				
				# Generate data
				my $rnd_data = $module->{'module_data'};
				
				if ($generation_type eq 'RANDOM') {
					$rnd_data = generate_random_data ($module_type, $module_data,
						$module_min, $module_max, $module_variation);
				}
				elsif ($generation_type eq 'SCATTER') {
					if (($module_type eq 'generic_data_string') ||
						($module_type eq 'async_string')) {
							
							printf "\n";
							
							log_message ($conf, "\tERROR:\tTry to generate scatter data in string module '$module_name' in agent '$agent_name'\n");
							
							$rnd_data = $module_data;
					}
					else {
						$rnd_data = generate_scatter_data ($module_type, $module_data, 
							$module_min, $module_max, $module_prob, $module_avg);
					}
				}
				elsif ($generation_type eq 'CURVE') {
					if (($module_type eq 'generic_data_string') ||
						($module_type eq 'async_string')) {
							
							printf "\n";
							
							log_message ($conf, "\tERROR:\tTry to generate curve data in string module '$module_name' in agent '$agent_name'\n");
							
							$rnd_data = $module_data;
					}
					else {
						$rnd_data = generate_curve_data ($utimestamp, $module_min, $module_max,
							$module_time_wave_length, $module_time_offset);
					}
				}
				
				# Save previous data
				$module->{'module_data'} = $rnd_data;
				$xml_data .= "\t\t<data>$rnd_data</data>\n";
				$xml_data .= "\t</module>\n";
			}	

			$xml_data .= "</agent_data>\n";

			# Fix the temporal path
			my $last_char = chop ($temporal);
			$temporal .= $last_char if ($last_char ne '/');

			# Save the XML data file
			my $xml_file = $temporal . '/' . $agent_name . '_' . $utimestamp . '.data';
			open (FILE, ">", $xml_file) || die ("[error] Could not write to '$xml_file': $!.\n\n");
			print FILE $xml_data;
			close (FILE);

			copy_xml_file ($conf, $xml_file);
			$XMLFiles++;
		}

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
		return int (rand ($max - $min + 1) + $min);
	}
	
	# Generic data
	return int (rand ($max - $min + 1) + $min);
}

################################################################################
# Generates curve data.
################################################################################
sub generate_curve_data ($$$$$$) {
	my ($utimestamp, $module_min, $module_max, $module_time_wave_length,
		$module_time_offset) = @_;
	
	#f(x) = (max - min) * Sin( (t * pi) / (wave_length)   +   (pi * (offset / wave_length))) + min
	
	######################################################
	#    GRAPHICAL EXPLAIN
	#
	#                 offset
	#                   |
	# (max - min) -> |-----  . .             . .
	#                |V   V.     .         .     .
	# ---------------|---------------------------------
	#                |   .         .     . ^     ^
	#         min -> | .             . .   |     |
	#                                      -------
	#                                         |
	#                                      wave_length
	#                                     
	######################################################
	
	my $return = ($module_max - $module_min)/2 *
		sin( ($utimestamp * pi) / $module_time_wave_length + 
		(pi * ($module_time_offset / $module_time_wave_length))) + ($module_min + $module_max) / 2;
		
	return $return;
}

################################################################################
# Generates scatter data.
################################################################################
sub generate_scatter_data ($$$$$$) {
	my ($module_type, $current_data, $min, $max, $prob, $avg) = @_;
	
	# And check the probability now
	my $other_rnd = int rand(100);
		
	if ( $prob >= $other_rnd) {		
		return int (rand ($max - $min + 1) + $min);
	}
	else {
		return $avg;
	}
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
	`tentacle_client -a $server_ip -p $server_port $tentacle_opts "$file" > /dev/null 2>&1`;
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
# Returns the parameter of a generator configuration of module.
################################################################################
sub get_generation_parameter($$$) {
	my ($hash_ref, $token, $def_value) = @_;
	
	return $def_value unless ref ($hash_ref) and defined ($hash_ref->{'module_exec'});
	
	my $configuration = $hash_ref->{'module_exec'};
	
	my $value = $def_value;
	
	$value = $1 if ($configuration =~ /$token=([^;]+)/);
	
	return $value;
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
open (FILE, "<", $conf{'agent_file'}) || 
	die ("[error] Could not open agent configuration file '" . $conf{'agent_file'} . "': $!.\n\n");

# Read agent names
my @agents;
while (my $agent_name = <FILE>) {
	chomp ($agent_name);
	push (@agents, $agent_name);
	$Agents++;
}
close (FILE);

# Get the maximum number of threads and the number of agents per thread
my $max_threads = 0 + get_conf_token (\%conf, 'max_threadss', '10');

my $step = ceil ($Agents / $max_threads);

my $t0 = gettimeofday ();
for (my $i = 0; $i < $Agents; $i += $step) {
	threads->create (\&generate_xml_files, \@agents, $i, $step, \%conf, \@modules);
}

# Log some information for the user
my $time_now = strftime ("%Y-%m-%d %H:%M:%S", localtime ());
my $time_from = get_conf_token (\%conf, 'time_from', $time_now);
my $time_to = get_conf_token (\%conf, 'time_to', $time_now);
my $interval = get_conf_token (\%conf, 'agent_interval', '300');
log_message (\%conf, "Generating XML data files for $Agents agents from $time_from to $time_to interval $interval.");

# Wait for all threads to finish
foreach my $thr (threads->list(threads::all)) {
	$thr->join ();
}

my $t1 = gettimeofday ();

# Log statistics
log_message (\%conf, "\tTotal agents:\t$Agents\n\t\tTotal modules:\t" . ($Agents * $Modules) . "\t($Modules per agent)\n\t\tTotal XML:\t$XMLFiles\t(" . int ($XMLFiles / ($t1 - $t0)) . " per second)");
