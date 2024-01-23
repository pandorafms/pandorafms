package PandoraFMS::WebServer;
##########################################################################
# Pandora FMS Web Server.
# Pandora FMS. the Flexible Monitoring System. http://www.pandorafms.org
##########################################################################
# Copyright (c) 2007-2023 Pandora FMS
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

use File::Temp qw(tempfile);
use HTML::Entities;
use POSIX qw(strftime);

use Encode;

# Default lib dir for RPM and DEB packages
use lib '/usr/lib/perl5';

use PandoraFMS::Tools;
use PandoraFMS::DB;
use PandoraFMS::Core;
use PandoraFMS::ProducerConsumerServer;

use PandoraFMS::Goliat::GoliatTools;
use PandoraFMS::Goliat::GoliatConfig;

# Inherits from PandoraFMS::ProducerConsumerServer
our @ISA = qw(PandoraFMS::ProducerConsumerServer);

# Global variables
my @TaskQueue :shared;
my %PendingTasks :shared;
my $Sem :shared;
my $TaskSem :shared;

########################################################################################
# Web Server class constructor.
########################################################################################
sub new ($$;$) {
	my ($class, $config, $dbh) = @_;

	return undef unless defined ($config->{'webserver'}) and ($config->{'webserver'} == 1);

	# Use Goliat with CURL
	if ($config->{'web_engine'} eq 'curl') {
		require PandoraFMS::Goliat::GoliatCURL;
		PandoraFMS::Goliat::GoliatCURL->import;
	
		# Check for CURL binary
		if (system ("curl -V >/dev/null 2>&1") >> 8 != 0) {

			logger ($config, ' [E] CURL binary not found. Install CURL or uncomment the web_engine configuration token to use LWP.', 1);
			print_message ($config, ' [E] CURL binary not found. Install CURL or uncomment the web_engine configuration token to use LWP.', 1);
			return undef;
		}
		# Check for pandora_exec binary
		if (system ("\"" . $config->{'plugin_exec'} . "\" 10 echo >/dev/null 2>&1") >> 8 != 0) {
			logger ($config, ' [E] ' . $config->{'plugin_exec'} . ' not found. Please install it or add it to the PATH.', 1);
			print_message ($config, ' [E] ' . $config->{'plugin_exec'} . ' not found. Please install it or add it to the PATH.', 1);
			return undef;
		}
	}
	# Use LWP by default
	else {
		require PandoraFMS::Goliat::GoliatLWP;
		PandoraFMS::Goliat::GoliatLWP->import;
		
		if (! LWP::UserAgent->can('ssl_opts')) {
			logger($config, "LWP version $LWP::VERSION does not support SSL. Make sure version 6.0 or higher is installed.", 1);
			print_message ($config, " [W] LWP version $LWP::VERSION does not support SSL. Make sure version 6.0 or higher is installed.", 1);
		}
	}

	# Initialize semaphores and queues
	@TaskQueue = ();
	%PendingTasks = ();
	$Sem = Thread::Semaphore->new;
	$TaskSem = Thread::Semaphore->new (0);
	
	# Call the constructor of the parent class
	my $self = $class->SUPER::new($config, WEBSERVER, \&PandoraFMS::WebServer::data_producer, \&PandoraFMS::WebServer::data_consumer, $dbh);

    bless $self, $class;
    return $self;
}

###############################################################################
# Run.
###############################################################################
sub run ($) {
	my $self = shift;
	my $pa_config = $self->getConfig ();

	print_message ($pa_config, " [*] Starting " . $pa_config->{'rb_product_name'} . " Web Server.", 1);
	
	$self->setNumThreads ($pa_config->{'web_threads'});
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

	if (pandora_is_master($pa_config) == 0) {
		@rows = get_db_rows ($dbh, 'SELECT tagente_modulo.id_agente_modulo, tagente_modulo.flag, tagente_estado.current_interval + tagente_estado.last_execution_try AS time_left, last_execution_try
			FROM tagente, tagente_modulo, tagente_estado
			WHERE server_name = ?
			AND tagente_modulo.id_agente = tagente.id_agente
			AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
			AND tagente.disabled = 0
			AND tagente_modulo.id_modulo = 7
			AND tagente_modulo.disabled = 0
			AND (tagente_modulo.flag = 1 OR ((tagente_estado.last_execution_try + tagente_estado.current_interval) < UNIX_TIMESTAMP())) 
			ORDER BY tagente_modulo.flag DESC, time_left ASC, last_execution_try ASC ', $pa_config->{'servername'});
    } else {
		@rows = get_db_rows ($dbh, 'SELECT DISTINCT(tagente_modulo.id_agente_modulo), tagente_modulo.flag, tagente_estado.current_interval + tagente_estado.last_execution_try  AS time_left, last_execution_try
			FROM tagente, tagente_modulo, tagente_estado, tserver
			WHERE ((server_name = ?) OR (server_name NOT IN (SELECT server_name FROM tserver WHERE status = 1 AND server_type = ?)))
			AND tagente_modulo.id_agente = tagente.id_agente
			AND tagente.disabled = 0
			AND tagente_modulo.disabled = 0
			AND tagente_modulo.id_modulo = 7
			AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
			AND ((tagente_estado.last_execution_try + tagente_estado.current_interval) < UNIX_TIMESTAMP() OR tagente_modulo.flag = 1 )
			ORDER BY tagente_modulo.flag DESC, time_left ASC, last_execution_try ASC', $pa_config->{'servername'}, WEBSERVER);
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
	our (@task_fails, @task_time, @task_ssec, @task_get_content); # Defined in GoliatLWP.pm and GoliatCURL.

	# Retrieve module data
	my $module = get_db_single_row ($dbh, 'SELECT * FROM tagente_modulo WHERE id_agente_modulo = ?', $module_id);
	return unless defined ($module);

	# Retrieve agent data
	my $agent = get_db_single_row ($dbh, 'SELECT * FROM tagente WHERE id_agente = ?', $module->{'id_agente'});
	return unless defined $agent;
	
	# Save Goliat config to a temporary file
	my ($fh, $temp_file) = tempfile();
	return unless defined ($fh);

	# Read the Goliat task	
	my $task = safe_output($module->{'plugin_parameter'});

	# Delete any carriage returns
	$task =~ s/\r//g;

	# Agent and module macros
	my %macros = (_agent_ => (defined ($agent)) ? $agent->{'alias'} : '',
				_agentdescription_ => (defined ($agent)) ? $agent->{'comentarios'} : '',
				_agentstatus_ => (defined ($agent)) ? get_agent_status ($pa_config, $dbh, $agent->{'id_agente'}) : '',
				_address_ => (defined ($agent)) ? $agent->{'direccion'} : '',
				_module_ => (defined ($module)) ? $module->{'nombre'} : '',
				_modulegroup_ => (defined ($module)) ? (get_module_group_name ($dbh, $module->{'id_module_group'}) || '') : '',
				_moduledescription_ => (defined ($module)) ? $module->{'descripcion'} : '',
				_modulestatus_ => (defined ($module)) ? get_agentmodule_status($pa_config, $dbh, $module->{'id_agente_modulo'}) : '',
				_moduletags_ => (defined ($module)) ? pandora_get_module_url_tags ($pa_config, $dbh, $module->{'id_agente_modulo'}) : '',
				_id_agent_ => (defined ($module)) ? $module->{'id_agente'} : '', 
				_interval_ => (defined ($module) && $module->{'module_interval'} != 0) ? $module->{'module_interval'} : (defined ($agent)) ? $agent->{'intervalo'} : '',
				_target_ip_ => (defined ($agent)) ? $agent->{'direccion'} : '', 
				_target_port_ => (defined ($module)) ? $module->{'tcp_port'} : '', 
				_policy_ => (defined ($module)) ? enterprise_hook('get_policy_name', [$dbh, $module->{'id_policy_module'}]) : '',
				_plugin_parameters_ => (defined ($module)) ? $module->{'plugin_parameter'} : '',
				_email_tag_ => (defined ($module)) ? pandora_get_module_email_tags ($pa_config, $dbh, $module->{'id_agente_modulo'}) : '',
				_phone_tag_ => (defined ($module)) ? pandora_get_module_phone_tags ($pa_config, $dbh, $module->{'id_agente_modulo'}) : '',
				_name_tag_ => (defined ($module)) ? pandora_get_module_tags ($pa_config, $dbh, $module->{'id_agente_modulo'}) : '',
	);
	$task = subst_alert_macros ($task, \%macros);
	
	# Goliat has some trouble parsing conf files without the newlines
	$fh->print ("\n\n" . encode_utf8($task) . "\n\n");
	close ($fh);

	# Global vars needed by Goliat
	my (%config, @work_list, $check_string);

	# Goliat config defaults
   	$config{'verbosity'} = 1;
	$config{'slave'} = 0;
	$config{'port'} = 80;
	$config{'log_file'} = "$DEVNULL";
	$config{'log_output'} = 0;
	$config{'log_http'} = 0;
	$config{'work_items'} = 0;
	$config{'config_file'} = $temp_file;
	$config{'agent'} = safe_output($module->{'plugin_user'});
	if ($module->{'max_retries'} != 0) {
		$config{'retries'} = $module->{'max_retries'};
	}	
	if ($module->{'max_timeout'} != 0) {
		$config{'timeout'} = $module->{'max_timeout'};
	} else {
		$config{'timeout'} = $pa_config->{'web_timeout'};
	}

	$config{'proxy'} = $module->{'snmp_oid'};
	$config{'auth_user'} = safe_output($module->{'tcp_send'});
	$config{'auth_pass'} = safe_output($module->{'tcp_rcv'});
	$config{'auth_server'} = $module->{'ip_target'};
	$config{'auth_realm'} = $module->{'snmp_community'};
	$config{'http_check_type'} = $module->{'tcp_port'};
	$config{'moduleId'} = $module_id;
	$config{'dbh'} = $dbh;

	# Pandora FMS variables passed to Goliat.
	$config{'plugin_exec'} = $pa_config->{'plugin_exec'};

	eval {
		# Load Goliat config
		g_load_config(\%config, \@work_list);
		
		# Run Goliat task
		g_http_task (\%config, 0, @work_list);
	};
	
	if ($@) {
		pandora_update_module_on_error ($pa_config, $module, $dbh);
		unlink ($temp_file);
		return;
	}

	unlink ($temp_file);

	my $utimestamp = time ();
	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime($utimestamp));

	# Get module type
	my $module_type = get_db_value ($dbh, 'SELECT nombre FROM ttipo_modulo WHERE id_tipo = ?', $module->{'id_tipo_modulo'});

	# Get data from Goliat
	my $module_data;
	{
		no strict 'vars';
		if ($module_type eq 'web_proc') {
			$module_data = ($task_fails[0] == 0 && $task_get_content[0] ne "") ? 1 : 0;
		} 
		elsif ($module_type eq 'web_data') {
			$module_data = $task_ssec[0];
		} elsif ($module_type eq 'web_server_status_code_string') {
			my @resp_lines = split "\r\n", $task_get_content[0];
			$module_data = $resp_lines[0];
		} else {
			$module_data = $task_get_content[0];
		}	
	}
	
	my %data = ("data" => $module_data);
	pandora_process_module ($pa_config, \%data, undef, $module, $module_type, $timestamp, $utimestamp, $self->getServerID (), $dbh);

	my $agent_os_version = get_db_value ($dbh, 'SELECT os_version FROM tagente WHERE id_agente = ?', $module->{'id_agente'});
	
	if (! defined ($agent_os_version) || $agent_os_version eq '') {
		$agent_os_version = $pa_config->{'servername'}.'_Web';
	}
    
    # Todo: Implement here
        # 1. Detect if exists a module with the same name, but with type generic_string.
        # 2. If not, create the module, get the id's
        # 3. Insert data coming from $task_get_string in that module

	pandora_update_agent ($pa_config, $timestamp, $module->{'id_agente'},  undef, undef, -1, $dbh);
}

1;
__END__
