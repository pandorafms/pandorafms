package PandoraFMS::SNMPServer;
##########################################################################
# Pandora FMS SNMP Console.
# Pandora FMS. the Flexible Monitoring System. http://www.pandorafms.org
##########################################################################
# Copyright (c) 2005-2009 Artica Soluciones Tecnologicas S.L
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public License
# as published by the Free Software Foundation; version 2
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
##########################################################################

use strict;
use warnings;

use threads;
use threads::shared;
use Thread::Semaphore;

use Time::Local;
use Time::HiRes qw(usleep);
use XML::Simple;

# Default lib dir for RPM and DEB packages
use lib '/usr/lib/perl5';

use PandoraFMS::Tools;
use PandoraFMS::DB;
use PandoraFMS::Core;
use PandoraFMS::ProducerConsumerServer;

# Inherits from PandoraFMS::ProducerConsumerServer
our @ISA = qw(PandoraFMS::ProducerConsumerServer);

# Global variables
my @TaskQueue :shared;
my %PendingTasks :shared;
my $Sem :shared;
my $TaskSem :shared;

# Trap statistics by agent
my %AGENTS = ();

# Index file management
my ($IDX_FILE, $LAST_LINE, $LAST_SIZE) = ();

########################################################################################
# SNMP Server class constructor.
########################################################################################
sub new ($$$) {
	my ($class, $config, $dbh) = @_;

	return undef unless $config->{'snmpconsole'} == 1;

	# Start snmptrapd
	if (start_snmptrapd ($config) != 0) {
		return undef;
	}
	
	# Wait for the SNMP log file to be available
	my $log_file = $config->{'snmp_logfile'};
	sleep ($config->{'server_threshold'}) if (! -e $log_file);
	if (!open (SNMPLOGFILE, $log_file)) {
		logger ($config, ' [E] Could not open the SNMP log file ' . $config->{'snmp_logfile'} . ".", 1);
		print_message ($config, ' [E] Could not open the SNMP log file ' . $config->{'snmp_logfile'} . ".", 1);
		return 1;
	}

	# Process index file, if available
	($IDX_FILE, $LAST_LINE, $LAST_SIZE) = ($log_file . '.index', 0, 0);
	if (-e  $IDX_FILE) {
		open (INDEXFILE, $IDX_FILE) or return;
		my $idx_data = <INDEXFILE>;
		close INDEXFILE;
		($LAST_LINE, $LAST_SIZE) = split(/\s+/, $idx_data);
	}
	my $log_size = (stat ($log_file))[7];

	# New SNMP log file found
	if ($log_size < $LAST_SIZE) {
		unlink ($IDX_FILE);
		($LAST_LINE, $LAST_SIZE) = (0, 0);
	}

	# Skip already processed lines
	read_snmplogfile() for (1..$LAST_LINE);
	
	# Initialize semaphores and queues
	@TaskQueue = ();
	%PendingTasks = ();
	$Sem = Thread::Semaphore->new;
	$TaskSem = Thread::Semaphore->new (0);

	# Call the constructor of the parent class
	my $self = $class->SUPER::new($config, SNMPCONSOLE, \&PandoraFMS::SNMPServer::data_producer, \&PandoraFMS::SNMPServer::data_consumer, $dbh);

	# Save the path of snmptrapd
	$self->{'snmp_trapd'} = $config->{'snmp_trapd'};

    bless $self, $class;
    return $self;
}

###############################################################################
# Run.
###############################################################################
sub run ($) {
	my $self = shift;
	my $pa_config = $self->getConfig ();

	print_message ($pa_config, " [*] Starting Pandora FMS SNMP Console.", 2);
	
	# Set the initial date for storm protection.
	$pa_config->{"__storm_ref__"} = time();

	$self->setNumThreads ($pa_config->{'snmpconsole_threads'});
	$self->SUPER::run (\@TaskQueue, \%PendingTasks, $Sem, $TaskSem);
}

###############################################################################
# Data producer.
###############################################################################
sub data_producer ($) {
	my $self = shift;
	my ($pa_config, $dbh) = ($self->getConfig (), $self->getDBH ());

	my @tasks;
	
	# Reset storm protection counters
	my $curr_time = time ();
	if ($pa_config->{"__storm_ref__"} + $pa_config->{"snmp_storm_timeout"} < $curr_time) {
		$pa_config->{"__storm_ref__"} = $curr_time;
		%AGENTS = ();
	}

	while (my $line_with_pos = read_snmplogfile()) {
		my $line;

		$LAST_LINE++;
		($LAST_SIZE, $line) = @$line_with_pos;

		chomp ($line);

		# Update index file
		open INDEXFILE, '>' . $IDX_FILE;
		print INDEXFILE $LAST_LINE . ' ' . $LAST_SIZE;
		close INDEXFILE;

		# Skip lines other than SNMP Trap logs
		next unless ($line =~ m/^SNMPv[12]\[\*\*\]/);

		# Storm protection.
		my ($ver, $date, $time, $source, $null) = split(/\[\*\*\]/, $line, 5);
		next unless defined ($source);
		if (! defined ($AGENTS{$source})) {
			$AGENTS{$source}{'count'} = 1;
			$AGENTS{$source}{'event'} = 0;
		} else {
			$AGENTS{$source}{'count'} += 1;
		}
		if ($pa_config->{'snmp_storm_protection'} > 0 && $AGENTS{$source}{'count'} > $pa_config->{'snmp_storm_protection'}) {
			if ($AGENTS{$source}{'event'} == 0) {
				pandora_event ($pa_config, "Too many traps coming from $source. Silenced for " . int ($pa_config->{"snmp_storm_timeout"} / 60) . " minutes.", 0, 0, 4, 0, 0, 'system', 0, $dbh);
			}
			$AGENTS{$source}{'event'} = 1;
			next;
		}

		push (@tasks, $line);
	}

	return @tasks;
}

###############################################################################
# Data consumer.
###############################################################################
sub data_consumer ($$) {
	my ($self, $task) = @_;

	pandora_snmptrapd ($self->getConfig (), $task, $self->getServerID (), $self->getDBH ());
}

##########################################################################
# Process SNMP log file.
##########################################################################
sub pandora_snmptrapd {
	my ($pa_config, $line, $server_id, $dbh) = @_;

	(my $trap_ver, $line) = split(/\[\*\*\]/, $line, 2);

	# Process SNMP filter
	next if (matches_filter ($dbh, $pa_config, $line) == 1);

	logger($pa_config, "Reading trap '$line'", 10);
	my ($date, $time, $source, $oid, $type, $type_desc, $value, $data) = ('', '', '', '', '', '', '', '');

	if ($trap_ver eq "SNMPv1") {
		($date, $time, $source, $oid, $type, $type_desc, $value, $data) = split(/\[\*\*\]/, $line, 8);

		$value = limpia_cadena ($value);

		# Try to save as much information as possible if the trap could not be parsed
		$oid = $type_desc if ($oid eq '' || $oid eq '.');

		if (!defined($oid)) {
			logger($pa_config, "[W] snmpTrapOID not found (Illegal SNMPv1 trap?)", 1);
			return;
		}

	} elsif ($trap_ver eq "SNMPv2") {
		($date, $time, $source, $data) = split(/\[\*\*\]/, $line, 4);
		my @data = split(/\t/, $data);

		shift @data; # Drop unused 1st data.
		$oid = shift @data;

		if (!defined($oid)) {
			logger($pa_config, "[W] snmpTrapOID not found (Illegal SNMPv2 trap?)", 1);
			return;
		}
		$oid =~ s/.* = OID: //;
		if ($oid =~ m/^\.1\.3\.6\.1\.6\.3\.1\.1\.5\.([1-5])$/) {
			$type = $1 - 1;
		} else {
			$type = 6;
		}
		$data = join("\t", @data);
	}

	if ($trap_ver eq "SNMPv2" || $pa_config->{'snmp_pdu_address'} eq '1' ) {
		# extract IP address from %b part:
		#  * destination part (->[dest_ip]:dest_port) appears in Net-SNMP > 5.3
		#  * protocol name (TCP: or UDP:) and bracketted IP addr w/ port number appear in
		#    Net-SNMP > 5.1 (Net-SNMP 5.1 has IP addr only).
		#  * port number is signed (often negative) in Net-SNMP 5.2
		$source =~ s/(?:(?:TCP|UDP):\s*)?\[?([^] ]+)\]?(?::-?\d+)?(?:\s*->.*)?$/$1/;
	}

	my $timestamp = $date . ' ' . $time;
	my ($custom_oid, $custom_type, $custom_value) = ('', '', '');

	# custom_type, custom_value is not used since 4.0 version, all custom data goes on custom_oid
	$custom_oid = $data;

	#Trap forwarding
	if ($pa_config->{'snmp_forward_trap'}==1) {
		my $trap_data_string = "";

		#We loop through all the custom data of the received trap, creating the $trap_data_string string to forward the trap properly
		while ($data =~ /([\.\d]+)\s=\s([^:]+):\s([\S ]+)/g) {
			my ($trap_data, $trap_type, $trap_value) = ($1, $2, $3);
			if ($trap_type eq "INTEGER") {
				#FIX for translated traps from IF-MIB.txt MIB
				$trap_value =~ s/\D//g;
				$trap_data_string = $trap_data_string . "$trap_data i $trap_value ";
			}
			elsif ($trap_type eq "UNSIGNED"){
				$trap_data_string = $trap_data_string . "$trap_data u $trap_value ";
			}
			elsif ($trap_type eq "COUNTER32"){
			        $trap_data_string = $trap_data_string . "$trap_data c $trap_value ";
			}
			elsif ($trap_type eq "STRING"){
			        $trap_data_string = $trap_data_string . "$trap_data s $trap_value ";
			}
			elsif ($trap_type eq "HEX STRING"){
			        $trap_data_string = $trap_data_string . "$trap_data x $trap_value ";
			}
			elsif ($trap_type eq "DECIMAL STRING"){
			        $trap_data_string = $trap_data_string . "$trap_data d $trap_value ";
			}
			elsif ($trap_type eq "NULLOBJ"){
			        $trap_data_string = $trap_data_string . "$trap_data n $trap_value ";
			}
			elsif ($trap_type eq "OBJID"){
			        $trap_data_string = $trap_data_string . "$trap_data o $trap_value ";
			}
			elsif ($trap_type eq "TIMETICKS"){
			        $trap_data_string = $trap_data_string . "$trap_data t $trap_value ";
			}
			elsif ($trap_type eq "IPADDRESS"){
			        $trap_data_string = $trap_data_string . "$trap_data a $trap_value ";
			}
			elsif ($trap_type eq "BITS"){
			        $trap_data_string = $trap_data_string . "$trap_data b $trap_value ";
			}
		}

		#We distinguish between the three different kinds of SNMP forwarding
		if ($pa_config->{'snmp_forward_version'} eq '3') {
			system("snmptrap -v $pa_config->{'snmp_forward_version'} -n \"\" -a $pa_config->{'snmp_forward_authProtocol'} -A $pa_config->{'snmp_forward_authPassword'} -x $pa_config->{'snmp_forward_privProtocol'} -X $pa_config->{'snmp_forward_privPassword'} -l $pa_config->{'snmp_forward_secLevel'} -u $pa_config->{'snmp_forward_secName'} -e $pa_config->{'snmp_forward_engineid'} $pa_config->{'snmp_forward_ip'} '' $oid $trap_data_string");
		}
		elsif ($pa_config->{'snmp_forward_version'} eq '2' || $pa_config->{'snmp_forward_version'} eq '2c') {
			system("snmptrap -v 2c -n \"\" -c $pa_config->{'snmp_forward_community'} $pa_config->{'snmp_forward_ip'} '' $oid $trap_data_string");
		}
		elsif ($pa_config->{'snmp_forward_version'} eq '1') {
			#Because of tne SNMP v1 protocol, we must perform additional steps for creating the trap
			my $value_sending = "";
			my $type_sending = "";

			if ($value eq ''){
				$value_sending = "\"\"";
			}
			else {
				$value_sending = $value;
				$value_sending =~ s/[\$#@~!&*()\[\];.,:?^ `\\\/]+//g;
			}
			if ($type eq ''){
				$type_sending = "\"\"";
			}
			else{
				$type_sending = $type;
			}

			system("snmptrap -v 1 -c $pa_config->{'snmp_forward_community'} $pa_config->{'snmp_forward_ip'} $oid \"\" $type_sending $value_sending \"\" $trap_data_string");
		}
	}

	# Insert the trap into the DB
	if (! defined(enterprise_hook ('snmp_insert_trap', [$pa_config, $source, $oid, $type, $value, $custom_oid, $custom_value, $custom_type, $timestamp, $server_id, $dbh]))) {
		my $trap_id = db_insert ($dbh, 'id_trap', 'INSERT INTO ttrap (timestamp, source, oid, type, value, oid_custom, value_custom,  type_custom) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
								 $timestamp, $source, $oid, $type, $value, $custom_oid, $custom_value, $custom_type);
		logger ($pa_config, "Received SNMP Trap from $source", 4);

		# Evaluate alerts for this trap
		pandora_evaluate_snmp_alerts ($pa_config, $trap_id, $source, $oid, $type, $oid, $value, $custom_oid, $dbh);
	}

	# Delay the consumption of the next task.
	sleep($pa_config->{'snmp_delay'}) if ($pa_config->{'snmp_delay'} > 0);
}

########################################################################################
# Returns 1 if the given string matches any SNMP filter, 0 otherwise.
########################################################################################
sub matches_filter ($$$) {
	my ($dbh, $pa_config, $string) = @_;
	
	# Get filters
	my @filters = get_db_rows ($dbh, 'SELECT filter FROM tsnmp_filter');
	foreach my $filter (@filters) {
		my $regexp = safe_output($filter->{'filter'}) ;
		my $eval_result;

		# eval protects against server down (by invalid regular expressions)
	    $eval_result = eval {
		     $string =~ m/$regexp/i ;
     	        };

	    if ($eval_result) {
		logger($pa_config, "Trap '$string' matches filter '$regexp'. Discarding...", 10);
		return 1;
	    }

	}
	
	return 0;
}

########################################################################################
# Start snmptrapd, attempting to kill it if it is already running. Returns 0 if
# successful, 1 otherwise.
########################################################################################
sub start_snmptrapd ($) {
	my ($config) = @_;
	
	my $pid_file = '/var/run/pandora_snmptrapd.pid';
	my $snmptrapd_running = 0;

	# Manual start of snmptrapd
	if ($config->{'snmp_trapd'} eq 'manual') {
		logger ($config, "No SNMP trap daemon configured. Start snmptrapd manually.", 1);
		print_message ($config, " [*] No SNMP trap daemon configured. Start snmptrapd manually.", 1);

		if (! -f $config->{'snmp_logfile'}) {
			logger ($config, "SNMP log file " . $config->{'snmp_logfile'} . " not found.", 1);
			print_message ($config, " [E] SNMP log file " . $config->{'snmp_logfile'} . " not found.", 1);
			return 1;
		}
		
		return 0;
	}
	
	if ( -e $pid_file && open (PIDFILE, $pid_file)) {
		my $pid = <PIDFILE> + 0;
		close PIDFILE;

		# Check if snmptrapd is running
		if ($snmptrapd_running = kill (0, $pid)) {
			logger ($config, "snmptrapd (pid $pid) is already running, attempting to kill it...", 1);
			print_message ($config, "snmptrapd (pid $pid) is already running, attempting to kill it...", 1);
			kill (9, $pid);
		}
	}

	# Ignore auth failure traps
	my $snmp_ignore_authfailure = ($config->{'snmp_ignore_authfailure'} eq '1' ? ' -a' : '');

	# Select agent-addr field of the PDU or PDU source address for V1 traps
	my $address_format = ($config->{'snmp_pdu_address'} eq '0' ? '%a' : '%b');
	
	my $snmptrapd_args = ' -t -On -n' . $snmp_ignore_authfailure . ' -Lf ' . $config->{'snmp_logfile'} . ' -p ' . $pid_file;
	$snmptrapd_args .=  ' --format1=SNMPv1[**]%4y-%02.2m-%l[**]%02.2h:%02.2j:%02.2k[**]' . $address_format . '[**]%N[**]%w[**]%W[**]%q[**]%v\\\n';
	$snmptrapd_args .=  ' --format2=SNMPv2[**]%4y-%02.2m-%l[**]%02.2h:%02.2j:%02.2k[**]%b[**]%v\\\n';

	if (system ($config->{'snmp_trapd'} . $snmptrapd_args . " >$DEVNULL 2>&1") != 0) {
		logger ($config, " [E] Could not start snmptrapd.", 1);
		print_message ($config, " [E] Could not start snmptrapd.", 1);
		return 1;
	}

	return 0;
}

###############################################################################
# Read SNMP Log file with buffering (to handle multi-line Traps).
# Return reference of array (file-pos, line-data) if successful, undef othersise.
###############################################################################
my $read_ahead_line;	# buffer to save fetched ahead line
my $read_ahead_pos;

sub read_snmplogfile()
{
	my $line;
	my $pos;

	if(defined($read_ahead_line)) {
		# Restore saved line
		$line = $read_ahead_line;
		$pos = $read_ahead_pos;
	}
	else {
		# No saved line
		$line = <SNMPLOGFILE>;
		$pos = tell(SNMPLOGFILE);
	}

	return undef if (! defined($line));

	my $retry_count = 0;

	# More lines ?
	while(1) {
		while($read_ahead_line = <SNMPLOGFILE>) {

			# Get current file position
			$read_ahead_pos = tell(SNMPLOGFILE);

			# Get out of the loop if you find another Trap
			last if($read_ahead_line =~ /^SNMP/ );

			# $read_ahead_line looks continued line...

			# Append to the line and correct the position
			chomp($line);
			$line .= "$read_ahead_line";
			$pos = $read_ahead_pos;
		}

		# if $line looks incomplete, try to get continued line
		# just within 10sec.  After that, giving up to complete it
		# and flush $line as it is.
		last if(chomp($line) > 0  || $retry_count++ >= 10);

		sleep(1);
	}

	# return fetched line with file position to be saved.
	return [$pos, $line];
}

###############################################################################
# Clean-up when the server is destroyed.
###############################################################################
sub DESTROY {
	my $self = shift;
	
	if ($self->{'snmp_trapd'} ne 'manual') {
		system ("kill -9 `cat /var/run/pandora_snmptrapd.pid 2>$DEVNULL`");
		unlink ('/var/run/pandora_snmptrapd.pid');
	}
}

1;
__END__
