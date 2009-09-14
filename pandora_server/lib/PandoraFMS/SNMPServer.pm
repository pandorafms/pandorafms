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
use XML::Simple;

use PandoraFMS::Tools;
use PandoraFMS::DB;
use PandoraFMS::Core;
use PandoraFMS::Server;

# Load enterprise module
enterprise_load ();

# Inherits from PandoraFMS::Server
our @ISA = qw(PandoraFMS::Server);

########################################################################################
# SNMP Server class constructor.
########################################################################################
sub new ($$;$) {
	my ($class, $config, $dbh) = @_;

	return undef unless $config->{'snmpconsole'} == 1;

	# Start snmptrapd
	if (system ($config->{'snmp_trapd'} . ' -t -On -n -a -Lf ' . $config->{'snmp_logfile'} . ' -p /var/run/pandora_snmptrapd.pid -F %4y-%02.2m-%l[**]%02.2h:%02.2j:%02.2k[**]%a[**]%N[**]%w[**]%W[**]%q[**]%v\\\n >/dev/null 2>&1') != 0) {
		logger ($config, " [E] Could not start snmptrapd.\n\n", 0);
		print " [E] Could not start snmptrapd.\n\n";
		return undef;
	}

	# Call the constructor of the parent class
	my $self = $class->SUPER::new($config, 2, $dbh);

    bless $self, $class;
    return $self;
}

###############################################################################
# Run.
###############################################################################
sub run ($) {
	my $self = shift;

	print " [*] Starting Pandora FMS SNMP Console. \n";
	$self->SUPER::run (\&PandoraFMS::SNMPServer::pandora_snmptrapd);
}

##########################################################################
# Process SNMP log file.
##########################################################################
sub pandora_snmptrapd {
	my $self = shift;
	my $pa_config = $self->getConfig ();

	# Connect to the DB
	my $dbh = db_connect ('mysql', $pa_config->{'dbname'}, $pa_config->{'dbhost'},
	                      3306, $pa_config->{'dbuser'}, $pa_config->{'dbpass'});
	$self->setDBH ($dbh);

	# Wait for the SNMP log file to be available
	my $log_file = $pa_config->{'snmp_logfile'};
	sleep ($pa_config->{'server_threshold'}) while (! -e $log_file);	
	open (SNMPLOGFILE, $log_file) or return;

	# Process index file, if available
	my ($idx_file, $last_line, $last_size) = ($log_file . '.index', 0, 0);
	if (-e  $idx_file) {
		open (INDEXFILE, $idx_file) or return;
		my $idx_data = <INDEXFILE>;
		close INDEXFILE;
		($last_line, $last_size) = split(/\s+/, $idx_data);
	}

	my $log_size = (stat ($log_file))[7];

	# New SNMP log file found
	if ($log_size < $last_size) {
		unlink ($idx_file);
		($last_line, $last_size) = (0, 0);
	}

	# Skip already processed lines
	readline SNMPLOGFILE for (1..$last_line);

	
	my $trap2agent = 0;
	
	if ($pa_config->{enterprise} == 1){
		$trap2agent = enterprise_hook('snmp_get_trap2agent', [$dbh]);
	}

	# Main loop
	while (1) {
		while (my $line = <SNMPLOGFILE>) {
			$last_line++;
			$last_size = (stat ($log_file))[7];
			
			# Update index file
			open INDEXFILE, '>' . $idx_file;
			print INDEXFILE $last_line . ' ' . $last_size;
			close INDEXFILE;

			# Skip Headers
			next if ($line =~ m/NET-SNMP/);

			# Unknown data
			next if ($line !~ m/\[\*\*\]/);

			my ($date, $time, $source, $oid,
			    $type, $type_desc, $value, $data) = split(/\[\*\*\]/, $line);

			my $timestamp = $date . ' ' . $time;
			$value = limpia_cadena ($value);

			my ($custom_oid, $custom_type, $custom_value) = ('', '', $type_desc);

			# Custom OID
			if ($type eq '6') { 	
					
				#String data
				next if ($data =~ m/STRING/) && ($data !~ m/([0-9\.]*)\s\=\s([A-Za-z0-9]*)\:\s\"(.+)\"/);

				next if ($data !~ m/([0-9\.]*)\s\=\s([A-Za-z0-9]*)\:\s(.+)/);

				($custom_oid, $custom_type, $custom_value) = ($1, $2, $3);
				$custom_value = limpia_cadena ($custom_value);
			}

			# Insert the trap into the DB
			if (! defined(enterprise_hook ('snmp_insert_trap', [$pa_config, $source, $oid, $type, $value, $custom_oid, $custom_value, $custom_type, $timestamp, $dbh]))) {
				my $trap_id = db_insert ($dbh, 'INSERT INTO ttrap (timestamp, source, oid, type, value, oid_custom, value_custom,  type_custom) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
				                         $timestamp, $source, $oid, $type, $value, $custom_oid, $custom_value, $custom_type);
				logger ($pa_config, "Received SNMP Trap from $source", 4);

				# Evaluate alerts for this trap
				pandora_evaluate_snmp_alerts ($pa_config, $trap_id, $source, $oid, $oid, $custom_oid, $custom_value, $dbh);
			}
			if ($pa_config->{enterprise} == 1){
				enterprise_hook ('snmp_trap2agent', [$trap2agent, $pa_config, $source, $oid, $value, $custom_oid, $custom_value, $timestamp, $self->getServerID (), $dbh]);
			}
		}
		
		sleep ($pa_config->{'server_threshold'});
	}
}

########################################################################################
# Stop the server, killing snmptrapd before.
########################################################################################
sub stop () {
	my $self = shift;

	system ('kill -9 `cat /var/run/pandora_snmptrapd.pid 2> /dev/null`');
	unlink ('/var/run/pandora_snmptrapd.pid');
	
	$self->SUPER::stop ();
}

1;
__END__
