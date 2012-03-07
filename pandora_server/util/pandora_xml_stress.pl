#!/usr/bin/perl
################################################################################
# Pandora XML Stress tool.
################################################################################
# Copyright (c) 2009 Ramon Novoa, rnovoa@artica.es
# Copyright (c) 2012 Artica Soluciones Tecnologicas S.L.
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

use POSIX qw (strftime ceil floor);

use Data::Dumper;
use Math::Trig;

use File::Copy;

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
sub generate_xml_files ($$$$$$) {
	my ($agents, $start, $step, $conf, $modules, $local_conf) = @_;

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
			
			# Use the modules of local conf of agent.
			if ($local_conf->{$agent_name}) {
				$modules = $local_conf->{$agent_name};
			}
			
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
				my $module_unit = get_conf_token ($module, 'module_unit', '');

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
				$xml_data .= "\t\t<unit><![CDATA[$module_unit]]></unit>\n";
				
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
			# The temporal dir is normaly the /var/spool/pandora/data_in
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
# INI MD5 FUNCTIONS
################################################################################

# Used to calculate the MD5 checksum of a string
use constant MOD232 => 2**32;

###############################################################################
# MD5 leftrotate function. See http://en.wikipedia.org/wiki/MD5#Pseudocode.
###############################################################################
sub leftrotate ($$) {
	my ($x, $c) = @_;

	return (0xFFFFFFFF & ($x << $c)) | ($x >> (32 - $c));
}

###############################################################################
# Initialize some variables needed by the MD5 algorithm.
# See http://en.wikipedia.org/wiki/MD5#Pseudocode.
###############################################################################
my (@R, @K);
sub md5_init () {

	# R specifies the per-round shift amounts
	@R = (7, 12, 17, 22,  7, 12, 17, 22,  7, 12, 17, 22,  7, 12, 17, 22,
		  5,  9, 14, 20,  5,  9, 14, 20,  5,  9, 14, 20,  5,  9, 14, 20,
		  4, 11, 16, 23,  4, 11, 16, 23,  4, 11, 16, 23,  4, 11, 16, 23,
		  6, 10, 15, 21,  6, 10, 15, 21,  6, 10, 15, 21,  6, 10, 15, 21);

	# Use binary integer part of the sines of integers (radians) as constants
	for (my $i = 0; $i < 64; $i++) {
		$K[$i] = floor(abs(sin($i + 1)) * MOD232);
	}
}

###############################################################################
# Return the MD5 checksum of the given string. 
# Pseudocode from http://en.wikipedia.org/wiki/MD5#Pseudocode.
###############################################################################
sub md5 ($) {
	my $str = shift;

	# Note: All variables are unsigned 32 bits and wrap modulo 2^32 when calculating

	# Initialize variables
	my $h0 = 0x67452301;
	my $h1 = 0xEFCDAB89;
	my $h2 = 0x98BADCFE;
	my $h3 = 0x10325476;

	# Pre-processing
	my $msg = unpack ("B*", pack ("A*", $str));
	my $bit_len = length ($msg);

	# Append "1" bit to message
	$msg .= '1';

	# Append "0" bits until message length in bits ≡ 448 (mod 512)
	$msg .= '0' while ((length ($msg) % 512) != 448);

	# Append bit /* bit, not byte */ length of unpadded message as 64-bit little-endian integer to message
	$msg .= unpack ("B64", pack ("VV", $bit_len));

	# Process the message in successive 512-bit chunks
	for (my $i = 0; $i < length ($msg); $i += 512) {

		my @w;
		my $chunk = substr ($msg, $i, 512);

		# Break chunk into sixteen 32-bit little-endian words w[i], 0 <= i <= 15
		for (my $j = 0; $j < length ($chunk); $j += 32) {
			push (@w, unpack ("V", pack ("B32", substr ($chunk, $j, 32))));
		}

		# Initialize hash value for this chunk
		my $a = $h0;
		my $b = $h1;
		my $c = $h2;
		my $d = $h3;
		my $f;
		my $g;

		# Main loop
		for (my $y = 0; $y < 64; $y++) {
			if ($y <= 15) {
				$f = $d ^ ($b & ($c ^ $d));
				$g = $y;
			}
			elsif ($y <= 31) {
				$f = $c ^ ($d & ($b ^ $c));
				$g = (5 * $y + 1) % 16;
			}
			elsif ($y <= 47) {
				$f = $b ^ $c ^ $d;
				$g = (3 * $y + 5) % 16;
			}
			else {
				$f = $c ^ ($b | (0xFFFFFFFF & (~ $d)));
				$g = (7 * $y) % 16;
			}

			my $temp = $d;
			$d = $c;
			$c = $b;
			$b = ($b + leftrotate (($a + $f + $K[$y] + $w[$g]) % MOD232, $R[$y])) % MOD232;
			$a = $temp;
		}

		# Add this chunk's hash to result so far
		$h0 = ($h0 + $a) % MOD232;
		$h1 = ($h1 + $b) % MOD232;
		$h2 = ($h2 + $c) % MOD232;
		$h3 = ($h3 + $d) % MOD232;
	}

	# Digest := h0 append h1 append h2 append h3 #(expressed as little-endian)
	return unpack ("H*", pack ("V", $h0)) . unpack ("H*", pack ("V", $h1)) . unpack ("H*", pack ("V", $h2)) . unpack ("H*", pack ("V", $h3));
}

################################################################################
# END MD5 FUNCTIONS
################################################################################



################################################################################

################################################################################
# Sends a file to the server.
################################################################################
sub send_file($$) {
	my $file = shift;
	my $conf = shift;printf($file . "\n");
	my $output;
	my $server_ip =  get_conf_token($conf, 'server_ip', '');
	my $server_port =  get_conf_token($conf, 'server_port', '41121');
	my $tentacle_options =  get_conf_token($conf, 'tentacle_options', '');
	# Shell command separator
	my $CmdSep = ';';
	# $DevNull
	my $DevNull = '/dev/null';
	
	$output = `tentacle_client -v -a $server_ip -p $server_port $tentacle_options $file 2>&1 >$DevNull`;
	
	# Get the errorlevel
	my $rc = $? >> 8;
	if ($rc != 0) {
		log_message($conf, "\tERROR:\tError sending file '$file': $output");
	}
	
	return $rc;
}

################################################################################
# Receive a file from the server.
################################################################################
sub recv_file ($$) {
	my $file = shift;
	my $conf = shift;
	my $output;
	my $directory_temp = get_conf_token($conf, 'directory_temp', '/tmp/');
	my $server_ip =  get_conf_token($conf, 'server_ip', '');
	my $server_port =  get_conf_token($conf, 'server_port', '41121');
	my $tentacle_options =  get_conf_token($conf, 'tentacle_options', '');
	# Shell command separator
	my $CmdSep = ';';
	# $DevNull
	my $DevNull = '/dev/null';
	
 	$output = `cd "$directory_temp"$CmdSep tentacle_client -v -g -a $server_ip -p $server_port $tentacle_options $file 2>&1 >$DevNull`;
	
	# Get the errorlevel
	my $rc;
	$rc = $? >> 8;
	if ($rc != 0) {
		log_message ($conf, "\tERROR:\tGetting the remote $file.'\n");
		log_message ($conf, "\tERROR:\t$output'\n");
	}
	
	return $rc;
}
################################################################################



################################################################################
# Get the send agent conf and generate modules.
################################################################################
sub get_and_send_agent_conf(\@\%\@\%) {
	my ($agents, $conf, $modules, $local_conf) = @_;
	
	my $get_and_send_agent_conf = get_conf_token($conf, 'get_and_send_agent_conf', '0');
	my $directory_confs = get_conf_token($conf, 'directory_confs', '.');
	
	my $directory_temp = get_conf_token($conf, 'directory_temp', '/tmp/');
	my $md5_agent_name = '';
	
	if ($get_and_send_agent_conf == 1) {
		foreach my $agent (@{$agents}) {
			$md5_agent_name = md5($agent);
			
			if (open (CONF_FILE, "$directory_confs/$agent.conf")) {
				binmode(CONF_FILE);
				my $conf_md5 = md5 (join ('', <CONF_FILE>));
				close (CONF_FILE);
				
				# Get the remote MD5 file
				if (recv_file("$md5_agent_name.md5", $conf) != 0) {
					#The remote agent don't recive, then it send the agent conf and md5.
					open (MD5_FILE, ">$directory_temp/$md5_agent_name.md5")
						|| log_message ($conf, "\tERROR:\tCould not open file '$directory_temp/$md5_agent_name.md5' for writing: $!.");
					print MD5_FILE $conf_md5;
					close (MD5_FILE);
					
					copy ("$directory_confs/$agent.conf", "$directory_temp/$md5_agent_name.conf");
					send_file("$directory_temp/$md5_agent_name.conf", $conf);
					send_file("$directory_temp/$md5_agent_name.md5", $conf);
					log_message ($conf, "\tINFO:\tUploading configuration for the first time.");
					unlink ("$directory_temp/$md5_agent_name.conf");
					unlink ("$directory_temp/$md5_agent_name.md5");
				}
				else {
					#There is a remote agent.
					open (MD5_FILE, "< $directory_temp/$md5_agent_name.md5")
						|| log_message ($conf, "Could not open file '$directory_confs/$md5_agent_name.md5' for writing: $!.");
					#Get the first version of md5 file.
					my $remote_conf_md5 = <MD5_FILE>;
					close (MD5_FILE);
					
					if ($remote_conf_md5 ne $conf_md5) {
						if (recv_file ("$md5_agent_name.conf", $conf) != 0) {
							log_message ($conf, "\tERROR:\t Get the remote '$agent.conf'.");
						}
						else {
							move("$directory_temp/$md5_agent_name.conf", "$directory_confs/$agent.conf");
						}
					}
				}
			}
			else {
				log_message ($conf, "\tWARNING:\tThere is not the $agent.conf .'\n");
				
				my $interval = get_conf_token($conf, 'agent_interval', '300');
				my $timezone_offset = get_conf_token($conf, 'timezone_offset', '0');
				
				my $module_txt = '';
				my $temp = "";
				
				# Create the block of modules.
				foreach my $module (@{$modules}) {
					$temp .= "
module_begin
module_name " . $module->{'module_name'} . "
module_type " . $module->{'module_type'} . "
module_exec " . $module->{'module_exec'} . "
module_min " . $module->{'module_min'} . "
module_max " . $module->{'module_max'} . "
module_end
";
				}
				
				my $default_conf = 
"# General Parameters
# ==================

server_ip       " . get_conf_token ($conf, 'server_ip', 'localhost') . "
server_path     /var/spool/pandora/data_in
temporal /tmp
logfile /var/log/pandora/pandora_agent.log

# Interval in seconds, 300 by default
interval        $interval

# Debug mode only generate XML, and stop after first execution, 
# and does not copy XML to server.
debug           0

# By default, agent takes machine name
agent_name     $agent

# Agent description
description This conf is generated with pandora_xml_stress.

# Timezone offset: Difference with the server timezone
#timezone_offset $timezone_offset

# Listening TCP port for remote server. By default is 41121 (for tentacle)
# if you want to use SSH use 22, and FTP uses 21.
server_port     41121

# Transfer mode: tentacle, ftp, ssh or local 
transfer_mode tentacle

# If set to 1 allows the agent to be configured via the web console (Only Enterprise version) 
remote_config 1" . $temp;
			
				if (open (CONF_FILE, ">$directory_confs/$agent.conf")) {
					print CONF_FILE $default_conf;
					close (CONF_FILE);
					
					open (CONF_FILE, "$directory_confs/$agent.conf");
					binmode(CONF_FILE);
					my $conf_md5 = md5 (join ('', <CONF_FILE>));
					close (CONF_FILE);
					
					#Send files.
					open (MD5_FILE, "> $directory_temp/$md5_agent_name.md5")
						|| log_message ($conf, "\tERROR:\tCould not open file '$directory_temp/$agent.conf' for writing: $!.");
					print MD5_FILE $conf_md5;
					close (MD5_FILE);
					copy ("$directory_confs/$agent.conf", "$directory_temp/$md5_agent_name.conf");
					send_file ("$directory_temp/$md5_agent_name.conf", $conf);
					send_file ("$directory_temp/$md5_agent_name.md5", $conf);
					log_message ($conf, "\tINFO:\tUploading configuration for the first time.");
					unlink ("$directory_temp/$md5_agent_name.conf");
					unlink ("$directory_temp/$md5_agent_name.md5");
				}
				else {
					log_message ($conf, "\ERROR:\tThe $agent.conf is not create.'\n");
				}
			}
			
			
			# Fill the local conf for generate data
			
			my $conf = parse_local_conf($agent, $conf);
			
			$local_conf->{$agent} = $conf;
		}
	}
}

################################################################################
# Parse local conf.
################################################################################
sub parse_local_conf($$) {
	my ($agent_name, $conf) = @_;
	
	my $directory_confs = get_conf_token($conf, 'directory_confs', '.');
	
	my @return;
	
	if (open (CONF_FILE, "$directory_confs/$agent_name.conf")) {
		my $line = '';
		while (<CONF_FILE>) {
			$line = $_;
			
			# A module definition
			if ($line =~ m/module_begin/) {
				my %module;

				# A comment
				next if ($line =~ m/^#/);

				while (my $line = <CONF_FILE>) {

					# A comment
					next if ($line =~ m/^#/);

					last if ($line =~ m/module_end/);
					 
					# Unknown line
					next if ($line !~ /^\s*(\w+)\s+(.+)$/);
					
					$module{$1} = $2;
				}
				
				push(@return, \%module);
			}
		}
		
		close (CONF_FILE);
	}
	else {
		log_message ($conf, "\ERROR:\tOpen to parse the $agent_name.conf.'\n");
	}
	
	return \@return;
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

# Init MD5
md5_init();

# Get the agent conf, instead use the conf in the pandora_xml_stress.conf
my %local_conf;
get_and_send_agent_conf(@agents, %conf, @modules, %local_conf);

# Get the maximum number of threads and the number of agents per thread
my $max_threads = 0 + get_conf_token (\%conf, 'max_threads', '10');

my $step = ceil ($Agents / $max_threads);

my $t0 = gettimeofday ();
for (my $i = 0; $i < $Agents; $i += $step) {
	threads->create (\&generate_xml_files, \@agents, $i, $step, \%conf, \@modules, \%local_conf);
}

# Log some information for the user
my $time_now = strftime ("%Y-%m-%d %H:%M:%S", localtime ());
my $time_from = get_conf_token (\%conf, 'time_from', $time_now);
my $time_to = get_conf_token (\%conf, 'time_to', $time_now);
my $interval = get_conf_token (\%conf, 'agent_interval', '300');
log_message (\%conf, "Generating XML data files for $Agents agents from $time_from to $time_to interval $interval.");

# Wait for all threads to finish
foreach my $thr (threads->list()) {
	$thr->join ();
}

my $t1 = gettimeofday ();

# Log statistics
log_message (\%conf, "\tTotal agents:\t$Agents\n\t\tTotal modules:\t" . ($Agents * $Modules) . "\t($Modules per agent)\n\t\tTotal XML:\t$XMLFiles\t(" . int ($XMLFiles / ($t1 - $t0)) . " per second)");
