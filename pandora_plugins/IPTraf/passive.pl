#!/usr/bin/perl

###############################################################################
# Passive collector
###############################################################################

use NetAddr::IP;

# Define variables

my %months = ('Jan' => 1, 'Feb' => 2, 'Mar' => 3, 'Apr' => 4, 'May' => 5, 'Jun' => 6, 
			'Jul' => 7, 'Aug' => 8, 'Sep' => 9, 'Oct' => 10, 'Nov' => 11, 'Dec' => 12);

##########
## INFO ##
##########

# WHEN SPLIT A LOGFILE LINE, THIS IS THE RESULT
	# Hour: $packages[3]
	# Date: $packages[2]-$packages[1]-$packages[4]
	# Protocol: $packages[5]
	# Interface: $packages[6]
	# Bytes: $packages[7]
	# From: $packages[10] (Format -> IP:Port)
	# To: $packages[12] (Format -> IP:Port)
	# Info: All after $packages[12] ('To')

# Main code

check_root();

# Load config file from command line or show help
help_screen () if ($#ARGV < 0 || $ARGV[0] =~ m/--*h/i);

my $conf_file = $ARGV[0];

if(!-e $conf_file) {
	print "[ERROR] Configuration file $conf_file not exists.\n\n";
	exit;
}

help_screen () if ($ARGV[1] =~ m/--*h/i);
	
my $config = load_config($conf_file);


passive_collector_main($config);

##############################################################################
# Print a help screen and exit.
##############################################################################
sub help_screen{
	print "Usage: $0 <path to collector.conf> [<options>]\n\n";
	print "Available options:\n";
	print "\t-h: Display help\n\n";
	exit;
}
	
##########################################################################
# Write a data XML on pandora incoming dir
##########################################################################
sub writexml($$$) {
	my ($ip, $xmlmessage, $config) = @_;
	
	my $hostname = "IP_".$ip;
	my $interval = $config->{'interval'};
	my $file = $config->{'incomingdir'}.$hostname.".".time().".data";

	open (FILEXML, ">> $file") or die "[FATAL] Cannot write to XML '$file'";
	
	print FILEXML "<agent_data interval='$interval' os_name='Network' os_vesion='3.2' version='N/A' timestamp='AUTO' address='$ip' agent_name='$hostname'>\n";
	print FILEXML $xmlmessage;
	print FILEXML "</agent_data>";
	
	close (FILEXML);
}

##########################################################################
# Add module info to XML passed
##########################################################################
sub add_module_to_xml($$$$) {
	my ($config, $module_name, $description, $data) = @_;
	my $xml = "<module>\n";
	$xml .= "<name>$module_name</name>\n";
	$xml .= "<type>async_data</type>\n";
	$xml .= "<description>$description</description>\n";
	$xml .= "<interval>".$config->{'interval'}."</interval>\n";
	$xml .= "<data>$data</data>\n";
	$xml .= "</module>\n";
	return $xml;
}

################################################################################
# Load config file parameters
################################################################################
sub load_config ($) {
	my $config_file = shift;
	my $interface;
	my @process_rules;
	my @discard_rules;
	my %config;
	
	open (FILE, $config_file);
	while (<FILE>) {
		my ($line) = split("\t");
		if($line =~ /^iface/) {
			$config{'interface'} = (split(' ', $line))[1];
		}
		elsif($line =~ /^log_path/) {
			$config{'log_path'} = (split(' ', $line))[1];
		}
		elsif($line =~ /^process/) {
			push(@process_rules, $line);
		}
		elsif($line =~ /^discard/) {
			push(@discard_rules, $line);
		}
		elsif($line =~ /^interval/) {
			$config{'interval'} = (split(' ', $line))[1];
		}
		elsif($line =~ /^incomingdir/) {
			$config{'incomingdir'} = (split(' ', $line))[1];
		}
		elsif($line =~ /^min_size/) {
			$config{'min_size'} = (split(' ', $line))[1];
		}
	}
	
	$config{'process_rules'} = \@process_rules;
	$config{'discard_rules'} = \@discard_rules;
	
	return \%config;
}

################################################################################
# Check if the script is running with root privileges
################################################################################
sub check_root () {
	if ($> != 0) {
		print "\nThis program can be run only by the system administrator\n\n";
		exit
	}
}

################################################################################
# Check the rules with specific source and destination IPs and PORTs.
################################################################################
sub check_rules ($$$$) {
	my ($from_addr, $to_addr, $protocol, $rules_info) = @_;
	
	my @from = split(':',$from_addr);
	my @to = split(':',$to_addr);
	my @rules_info = @{$rules_info};
	
	my %success_info;
	
	$success_info{'success'} = 0;
	for(my $i = 0; $i <= $#rules_info; $i++) {
		if(@rules_info[$i]->{'ip_side'} eq 'src') {
			$success_info{'match_ip'} = $from[0];
			$success_info{'other_ip'} = $to[0];
		}
		else {
			$success_info{'match_ip'} = $to[0];
			$success_info{'other_ip'} = $from[0];
		}
		
		if(@rules_info[$i]->{'port_side'} eq 'src') {
			$success_info{'match_port'} = $from[1];
		}
		else {
			$success_info{'match_port'} = $to[1];
		}
	
		# If the port is not specified we set wilcard as port
		if($success_info{'match_port'} eq '') {
			$success_info{'match_port'} = "*";
		}
		
		$success_info{'protocol'} = $protocol;
				
		my $ip_found = 0;
		# Check if the address match with the rule
		if($success_info{'match_ip'} =~ @rules_info[$i]->{'addrs_re'}) {
			$ip_found = 1;
		}
		
		my $port_found = 0;
		# Check if the port exists in the hash table
		if(defined($rules_info[$i]->{'ports'}->{$uccess_info{'match_port'}})) {
			$port_found = 1;
		}
		
		my $protocol_found = 0;
		# Check if the protocol exists in the hash table or is all
		if(defined($rules_info[$i]->{'protocols'}->{$success_info{'protocol'}}) || defined($rules_info[$i]->{'protocols'}->{'all'})) {
			$protocol_found = 1;
		}
	
		# If found and negative or not found and possitive are the bad combinations
		# The results cant be 1 
		$ip_success = $rules_info[$i]->{'ip_sign'} + $ip_found;
		$port_success = $rules_info[$i]->{'port_sign'} + $port_found;
		$protocol_success = $rules_info[$i]->{'protocol_sign'} + $protocol_found;
	
		if($ip_success == 1 || $port_success == 1 || $protocol_success == 1) {
			next;
		}
		else {
			if($rules_info[$i]->{'rule_type'} eq 'process') {
				$success_info{'success'} = 1;
			}
			else {
				$success_info{'success'} = 0;
			}
			last;
		}
	}
	
	return \%success_info;
}

################################################################################
# Parse rules and return a data structure with the rules parameters.
################################################################################
sub parse_rules ($) {
	my $rules = shift;
	my @rules = @{$rules};

	my @rules_info;
	for(my $i = 0; $i <= $#rules; $i++) {	
		@rules_info[$i] = $rules[$i];

		my @rule_parts = split(" ", $rules[$i]);
		
		my $net_addr = new NetAddr::IP ($rule_parts[2]);

		@rules_info[$i]->{'addrs_re'} = $net_addr->re();
		
		my %ports;
				
		my @ports_enumeration = split(",", $rule_parts[4]);

		for(my $i = 0; $i<=$#ports_enumeration; $i++) {
			my @ports_interval = split("-", $ports_enumeration[$i]);
			# If the format is [port1]-[port2] we store the interval
			if($#ports_interval == 1) {
				for(my $j = $ports_interval[0]; $j<=$ports_interval[1]; $j++) {
					$ports{$j} = 1;
				}
			}
			else {
				# If the format is not interval, is single port
				$ports{$ports_enumeration[$i]} = 1;
			}
		}
		
		@rules_info[$i]->{'ports'} = \%ports;
		
		my %protocols;
				
		my @protocols_enumeration = split(",", $rule_parts[6]);

		for(my $i = 0; $i<=$#protocols_enumeration; $i++) {
			$protocols{$protocols_enumeration[$i]} = 1;
		}
		
		@rules_info[$i]->{'protocols'} = \%protocols;
		
		# SIGN OF THE IP RULE #
		#0 if the ip rule is negate, 1 if not
		if($rule_parts[1] =~ /^!.*/) {
			@rules_info[$i]->{'ip_sign'} = 0;
		}
		else {
			@rules_info[$i]->{'ip_sign'} = 1;
		}
		
		# SIGN OF THE SIDE OF THE IP RULE #
		#src for source; dst for destination
		if($rule_parts[1] =~ /(!)*src.*/) {
			$ip_side = 'src';
			@rules_info[$i]->{'ip_side'} = 'src';
		}
		else {
			$ip_side = 'dst';
			@rules_info[$i]->{'ip_side'} = 'dst';
		}
		
		# SIGN OF THE PORT RULE #
		#0 if the port rule is negate, 1 if not
		if($rule_parts[3] =~ /^!.*/) {
			@rules_info[$i]->{'port_sign'} = 0;
		}
		else {
			@rules_info[$i]->{'port_sign'} = 1;
		}
		
		# SIGN OF THE SIDE OF THE PORT RULE #
		#src for source; dst for destination
		if($rule_parts[3] =~ /(!)*src.*/) {
			@rules_info[$i]->{'port_side'} = 'src';
		}
		else {
			@rules_info[$i]->{'port_side'} = 'dst';
		}
		
		# SIGN OF THE PROTOCOL RULE #
		#0 if the protocol rule is negate, 1 if not
		if($rule_parts[5] =~ /^!.*/) {
			@rules_info[$i]->{'protocol_sign'} = 0;
		}
		else {
			@rules_info[$i]->{'protocol_sign'} = 1;
		}
		
		# TYPE OF THE RULE #
		#'process' for store the matches and 'discard' for avoid it
		@rules_info[$i]->{'rule_type'} = $rule_parts[0];
	}

	return \@rules_info;
}

##########################################################################
## Main function
##########################################################################

sub passive_collector_main ($) {
        my ($config) = @_;
        
        my $log_path = $config->{'log_path'};
		
		my %tree;
		my $start_date;
		my $start_hour;
		
        my @rules = (@{$config->{'discard_rules'}}, @{$config->{'process_rules'}});
		
		###################
		# PARSE THE RULES #
		###################	
			
		my @rules_info = @{parse_rules(\@rules)};
		
		##################
		# PARSE THE FILE #
		##################	
		
		open (FILE, $log_path);
		my $descarted1 = 0;
		my $descarted2 = 0;
		my $descarted3 = 0;
		my $processed = 0;
		my $superprocessed = 0;
		
		while (<FILE>) {
			my ($line) = split("\t");
			
			# Keep the original line to future changes
			$orig_line = $line;

			# Remove ; from the line
			$line =~ s/;//g;
							
			# Split the line into array	
			my @packages = split(" ", $line);
			
			# Get the numeric month
			$packages[1] = $months{$packages[1]};

			#######################
			# CHECK SPECIAL CASES #
			#######################
			
			# The first line of the execution is a header with the start date and hour
			if($packages[5] eq '********') {
				$start_date = "$packages[2]-$packages[1]-$packages[4]";
				$start_hour = $packages[3];
				next;
			}

			# Special case with the 'Connection' script in the interface place 
			# has a different structure. Discard this case.

			my $from_addr;
			my $to_addr;
			my $info;
			
			# Get the substring from the clean line after the destination to keep the extra info
			if ( $orig_line =~ /$packages[12];\s(.*?)\n/ )
			{
				$info = $1;
			}
			
			# Discard the ACKs and other Synchronization packages
			if ($info =~ /FIN/ || $info =~ /SYN/ || $info =~ /reset/) {
				next;
			}

			# Discard the connections tries
			if($packages[6] eq 'Connection') {
				next;
			}
			
			# Check the min size to discard little packages
			if($config->{'min_size'} > $packages[7]) {
				next;
			}
						
			# Store the Addresses
			$from_addr = $packages[10];
			$to_addr = $packages[12];
			$protocol = $packages[5];
			
			###################
			# CHECK THE RULES #
			###################	
			
			my %success_info = %{check_rules($from_addr, $to_addr, $protocol, \@rules_info)};
			
			if($success_info{'success'} == 0) {
				next;
			}

			######################
			# STORE THE PACKAGES #
			######################
			$tree{$success_info{'match_ip'}}[0]->{$success_info{'match_port'}}->{'total'} += $packages[7];
			$tree{$success_info{'match_ip'}}[0]->{$success_info{'match_port'}}->{$success_info{'protocol'}} += $packages[7];
			$tree{$success_info{'match_ip'}}[1]->{$success_info{'other_ip'}}->{'total'} += $packages[7];
			$tree{$success_info{'match_ip'}}[1]->{$success_info{'other_ip'}}->{$success_info{'match_port'}} += $packages[7];
			$tree{$success_info{'match_ip'}}[2]->{$success_info{'protocol'}}->{'total'} += $packages[7];
			
			# Registers counter
			$tree{$success_info{'match_ip'}}[3] ++;
			# Total Bytes
			$tree{$success_info{'match_ip'}}[4] += $packages[7];
		}
		close(FILE);

		##########################################
		# BUILD XML AND WRITE IT #
		##########################################
		my $total_bytes = 0;
		my $total_regs = 0;
		my $total_ips = 0;
		 		
		foreach $k (keys (%tree))
		{
			my $xmlmessage = '';
			
			foreach $i (keys (%{$tree{$k}[0]}))
			{	
				$xmlmessage .= add_module_to_xml ($config, "Port_".$i, "Total bytes of port ".$i, $tree{$k}[0]->{$i}->{'total'});
				foreach $j (keys (%{$tree{$k}[0]->{$i}}))
				{	
					if($j eq 'total') {
						next;
					}
					$xmlmessage .= add_module_to_xml ($config, "Port_".$i."_Protocol_".$j, "Total bytes of port ".$i." for protocol ".$j, $tree{$k}[0]->{$i}->{$j});
				}
			}
			foreach $i (keys (%{$tree{$k}[1]}))
			{
				$xmlmessage .= add_module_to_xml ($config, "IP_".$i, "Total bytes of IP ".$i, $tree{$k}[1]->{$i}->{'total'});
				foreach $j (keys (%{$tree{$k}[1]->{$i}}))
				{	
					if($j eq 'total') {
						next;
					}
					$xmlmessage .= add_module_to_xml ($config, "IP_".$i."_Port_".$j, "Total bytes of IP ".$i." for port ".$j, $tree{$k}[1]->{$i}->{$j});
				}
			}
			foreach $i (keys (%{$tree{$k}[2]}))
			{
				$xmlmessage .= add_module_to_xml ($config, "Protocol_".$i, "Total bytes of Protocol ".$i, $tree{$k}[2]->{$i}->{'total'});
			}
			
			# Store statistics in the future
			$total_regs += $tree{$k}[3];
			$total_bytes += $tree{$k}[4];
			$total_ips ++;
			
			writexml($k, $xmlmessage, $config);
		}
}	
