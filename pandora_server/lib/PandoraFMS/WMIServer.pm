package PandoraFMS::WMIServer;
##########################################################################
# Pandora FMS WMI Server.
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

use POSIX qw(strftime);
use HTML::Entities;

use PandoraFMS::Tools;
use PandoraFMS::DB;
use PandoraFMS::Core;
use PandoraFMS::ProducerConsumerServer;

# Inherits from PandoraFMS::ProducerConsumerServer
our @ISA = qw(PandoraFMS::ProducerConsumerServer);

# Global variables
my @TaskQueue :shared;
my %PendingTasks :shared;
my $Sem :shared = Thread::Semaphore->new;
my $TaskSem :shared = Thread::Semaphore->new (0);

########################################################################################
# NetworkServer class constructor.
########################################################################################
sub new ($$;$) {
	my ($class, $config, $dbh) = @_;

	return undef unless $config->{'wmiserver'} == 1;

	# Check for a WMI client
	if (system ($config->{'wmi_client'} . ' > /dev/null 2>&1') != 256) {
		logger ($config, ' [E] ' . $config->{'wmi_client'} . " not found. Pandora FMS WMI Server needs a DCOM/WMI client.", 0);
		print ' [E] ' . $config->{'wmi_client'} . " not found. Pandora FMS WMI Server needs a DCOM/WMI client.\n\n";
		return undef;
	}
		
	# Call the constructor of the parent class
	my $self = $class->SUPER::new($config, 6, \&PandoraFMS::WMIServer::data_producer, \&PandoraFMS::WMIServer::data_consumer, $dbh);

    bless $self, $class;
    return $self;
}

###############################################################################
# Run.
###############################################################################
sub run ($) {
	my $self = shift;
	my $pa_config = $self->getConfig ();

	print " [*] Starting Pandora FMS WMI Server. \n";
	$self->setNumThreads ($pa_config->{'wmi_threads'});
	$self->SUPER::run (\@TaskQueue, \%PendingTasks, $Sem, $TaskSem);
}

###############################################################################
# Data producer.
###############################################################################
sub data_producer ($) {
	my $self = shift;
	my ($pa_config, $dbh) = ($self->getConfig (), $self->getDBH ());

	my @tasks;
	my @rows;

	if ($pa_config->{'pandora_master'} != 1) {
		@rows = get_db_rows ($dbh, 'SELECT tagente_modulo.id_agente_modulo, tagente_modulo.flag UNIX_TIMESTAMP() - tagente_estado.current_interval - tagente_estado.last_execution_try  AS time_left 
			FROM tagente, tagente_modulo, tagente_estado
			WHERE server_name = ?
			AND tagente_modulo.id_agente = tagente.id_agente
			AND	tagente.disabled = 0
			AND tagente_modulo.id_modulo = 6
			AND tagente_modulo.disabled = 0
			AND	tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
			AND ((tagente_estado.last_execution_try + tagente_estado.current_interval) < UNIX_TIMESTAMP() 
			  OR tagente_modulo.flag = 1)				
			ORDER BY tagente_modulo.flag DESC, time_left DESC, last_execution_try ASC', $pa_config->{'servername'});		
    } else {
		@rows = get_db_rows ($dbh, 'SELECT DISTINCT(tagente_modulo.id_agente_modulo), tagente_modulo.flag, UNIX_TIMESTAMP() - tagente_estado.current_interval - tagente_estado.last_execution_try  AS time_left  
			FROM tagente, tagente_modulo, tagente_estado, tserver
			WHERE ((server_name = ?) OR (server_name = ANY(SELECT name FROM tserver WHERE status = 0)))
			AND tagente_modulo.id_agente = tagente.id_agente
			AND tagente.disabled = 0
			AND tagente_modulo.disabled = 0
			AND tagente_modulo.id_modulo = 6
			AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
			AND ((tagente_estado.last_execution_try + tagente_estado.current_interval) < UNIX_TIMESTAMP() OR tagente_modulo.flag = 1 )
			ORDER BY tagente_modulo.flag DESC, time_left DESC, last_execution_try ASC', $pa_config->{'servername'});
	}

	foreach my $row (@rows) {
		
		# Reset forced execution flag
		if ($row->{'flag'} == 1) {
			db_do ($dbh, 'UPDATE tagente_modulo SET flag = 0 WHERE id_agente_modulo = ?', $row->{'id_agente_modulo'});
		}

		push (@tasks, $row->{'id_agente_modulo'});
	}

	return @tasks;
}

###############################################################################
# Data consumer.
###############################################################################
sub data_consumer ($$) {
	my ($self, $module_id) = @_;
	my ($pa_config, $dbh) = ($self->getConfig (), $self->getDBH ());

	my $module = get_db_single_row ($dbh, 'SELECT * FROM tagente_modulo WHERE id_agente_modulo = ?', $module_id);
	return unless defined $module;

	# Build command to execute
	my $wmi_command = $pa_config->{'wmi_client'} . ' -U "' . $module->{'plugin_user'} . '"%"' . $module->{'plugin_pass'} . '"';
    
	# Use a custom namespace
	my $namespace = $module->{'tcp_send'};
	if ($namespace ne '') {
		$namespace =~ s/\"/\'/g;
		$wmi_command .= ' --namespace="' . $namespace . '"';
	}

	# WMI query
	my $wmi_query = decode_entities($module->{'snmp_oid'});
	$wmi_query =~ s/\"/\'/g;

	$wmi_command .= ' //' . $module->{'ip_target'} . ' "' . $wmi_query . '"';
	logger ($pa_config, "Executing AM # $module_id WMI command '$wmi_command'", 9);
  
	# Execute command
	my $module_data = `$wmi_command`;
	if (! defined ($module_data)) {
		pandora_update_module_on_error ($pa_config, $module_id, $dbh);
		return;
	}

	# Parse command output. Example:
	# CLASS: Win32_Processor
	# DeviceID|LoadPercentage
	# CPU0|2
	my @output = split("\n", $module_data);
	if ($#output < 2) {
		pandora_update_module_on_error ($pa_config, $module_id, $dbh);
		return;
	}

	# Check for errors
	if ($output[0] =~ /ERROR/) {
		pandora_update_module_on_error ($pa_config, $module_id, $dbh);
		return;
	} 

	# Get the first row (line 3)
	my @row = split(/\|/, $output[2]);

	# Get the specified column
	$module_data = $row[$module->{'tcp_port'}];

	# Regexp
	if ($module->{'snmp_community'} ne ''){
		my $filter = $module->{'snmp_community'};
		$module_data = ($module_data =~ /$filter/) ? 1 : 0;
	}

	my $utimestamp = time ();
	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime($utimestamp));

	pandora_process_module ($pa_config, $module_data, '', $module, '', $timestamp, $utimestamp, $self->getServerID (), $dbh);
	pandora_update_agent ($pa_config, $timestamp, $module->{'id_agente'},  $pa_config->{'servername'} . '_WMI', $pa_config->{'version'}, -1, $dbh);
}

1;
__END__
