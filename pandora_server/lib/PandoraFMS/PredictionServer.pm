package PandoraFMS::PredictionServer;
########################################################################
# Pandora FMS Prediction Server.
# Pandora FMS. the Flexible Monitoring System. http://www.pandorafms.org
########################################################################
# Copyright (c) 2005-2021 Artica Soluciones Tecnologicas S.L
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

use IO::Socket::INET;
use Net::Ping;
use POSIX qw(floor strftime);

# Default lib dir for RPM and DEB packages
use lib '/usr/lib/perl5';

use PandoraFMS::Tools;
use PandoraFMS::DB;
use PandoraFMS::Core;
use PandoraFMS::ProducerConsumerServer;
use PandoraFMS::Statistics::Regression;

#For debug
#use Data::Dumper;

# Inherits from PandoraFMS::ProducerConsumerServer
our @ISA = qw(PandoraFMS::ProducerConsumerServer);

# Global variables
my @TaskQueue :shared;
my %PendingTasks :shared;
my $Sem :shared;
my $TaskSem :shared;

########################################################################
# Prediction Server class constructor.
########################################################################
sub new ($$;$) {
	my ($class, $config, $dbh) = @_;
	
	return undef unless $config->{'predictionserver'} == 1;

	# Initialize semaphores and queues
	@TaskQueue = ();
	%PendingTasks = ();
	$Sem = Thread::Semaphore->new;
	$TaskSem = Thread::Semaphore->new (0);
		
	# Call the constructor of the parent class
	my $self = $class->SUPER::new($config, PREDICTIONSERVER, \&PandoraFMS::PredictionServer::data_producer, \&PandoraFMS::PredictionServer::data_consumer, $dbh);

	bless $self, $class;
	
	return $self;
}

########################################################################
# Run.
########################################################################
sub run ($) {
	my $self = shift;
	my $pa_config = $self->getConfig ();
	
	print_message ($pa_config, " [*] Starting " . $pa_config->{'rb_product_name'} . " Prediction Server.", 1);
	$self->setNumThreads ($pa_config->{'prediction_threads'});
	$self->SUPER::run (\@TaskQueue, \%PendingTasks, $Sem, $TaskSem);
}

########################################################################
# Data producer.
########################################################################
sub data_producer ($) {
	my $self = shift;
	my ($pa_config, $dbh) = ($self->getConfig (), $self->getDBH ());
	
	my @tasks;
	my @rows;
	
	if (pandora_is_master($pa_config) == 0) {
		@rows = get_db_rows ($dbh, 'SELECT tagente_modulo.id_agente_modulo,
				tagente_modulo.flag, last_execution_try
			FROM tagente, tagente_modulo, tagente_estado
			WHERE server_name = ?
				AND tagente_modulo.id_agente = tagente.id_agente
				AND tagente.disabled = 0
				AND tagente_modulo.prediction_module != 0
				AND tagente_modulo.disabled = 0
				AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
				AND tagente_modulo.id_modulo = 5
				AND (tagente_modulo.flag = 1
				OR (tagente_estado.last_execution_try + tagente_estado.current_interval) < UNIX_TIMESTAMP())
			ORDER BY last_execution_try ASC ', safe_input($pa_config->{'servername'}));
	}
	else {
		# If is metaconsole server, will evaluate orphan modules also.
		@rows = get_db_rows ($dbh, 'SELECT DISTINCT(tagente_modulo.id_agente_modulo),
				tagente_modulo.flag, last_execution_try
			FROM tagente, tagente_modulo, tagente_estado
			WHERE ((server_name = ?)
				OR (server_name = ANY(SELECT name
					FROM tserver
					WHERE status <> 1 AND server_type = ?))
				OR ((server_name = 0 OR server_name IS NULL) AND 1=?)
				)
				AND tagente_modulo.id_agente = tagente.id_agente
				AND tagente.disabled = 0
				AND tagente_modulo.disabled = 0
				AND tagente_modulo.prediction_module != 0
				AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
				AND tagente_modulo.id_modulo = 5
				AND (tagente_modulo.flag = 1
				OR (tagente_estado.last_execution_try + tagente_estado.current_interval) < UNIX_TIMESTAMP())
			ORDER BY last_execution_try ASC',
			safe_input($pa_config->{'servername'}),
			PREDICTIONSERVER,
			is_metaconsole($pa_config)
		);
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

########################################################################
# Data consumer.
########################################################################
sub data_consumer ($$) {
	my ($self, $task) = @_;
	
	exec_prediction_module ($self->getConfig (), $task, $self->getServerID (), $self->getDBH ());
}

########################################################################
# Execute prediction module.
########################################################################
sub exec_prediction_module ($$$$) {
	my ($pa_config, $id_am, $server_id, $dbh) = @_;
	
	# Get a full hash for agent_module record reference ($agent_module)
	my $agent_module = get_db_single_row ($dbh, 'SELECT *
		FROM tagente_modulo
		WHERE id_agente_modulo = ?', $id_am);
	return unless defined $agent_module;
	
	# Service modules
	if ($agent_module->{'prediction_module'} == 2) {
		
		if ($agent_module->{'custom_string_1'} eq 'SLA') {
			logger ($pa_config, "Executing service module SLA " .
				$agent_module->{'id_agente_modulo'} . " " .
				$agent_module->{'nombre'}, 10);
			enterprise_hook ('exec_service_module_sla', [$pa_config, $agent_module, $server_id, $dbh]);
		}
		elsif ($agent_module->{'custom_string_1'} eq 'SLA_Value')  {
			#Do none
		}
		else {
			logger ($pa_config, "Executing service module " .
				$agent_module->{'id_agente_modulo'} . " " .
				$agent_module->{'nombre'}, 10);
			enterprise_hook ('exec_service_module', [$pa_config, $agent_module, undef, $server_id, $dbh]);
		}
		
		return;
	}
	
	# Synthetic modules
	if ($agent_module->{'prediction_module'} == 3) {
		logger ($pa_config, "Executing synthetic module " . $agent_module->{'nombre'}, 10);
		enterprise_hook ('exec_synthetic_module', [$pa_config, $agent_module, $server_id, $dbh]);
		return;
	}
	
	# Netflow modules
	if ($agent_module->{'prediction_module'} == 4) {
		logger ($pa_config, "Executing netflow module " . $agent_module->{'nombre'}, 10);
		enterprise_hook ('exec_netflow_module', [$pa_config, $agent_module, $server_id, $dbh]);
		return;
	}
	
	# Cluster status module.
	if ($agent_module->{'prediction_module'} == 5) {
		logger ($pa_config, "Executing cluster status module " . $agent_module->{'nombre'}, 10);
		enterprise_hook ('exec_cluster_status_module', [$pa_config, $agent_module, $server_id, $dbh]);
		return;
	}

	# Cluster active-active module.
	if ($agent_module->{'prediction_module'} == 6) {
		logger ($pa_config, "Executing cluster active-active module " . $agent_module->{'nombre'}, 10);
		enterprise_hook ('exec_cluster_aa_module', [$pa_config, $agent_module, $server_id, $dbh]);
		return;
	}

	# Cluster active-passive module.
	if ($agent_module->{'prediction_module'} == 7) {
		logger ($pa_config, "Executing cluster active-passive module " . $agent_module->{'nombre'}, 10);
		enterprise_hook ('exec_cluster_ap_module', [$pa_config, $agent_module, $server_id, $dbh]);
		return;
	}

	# Trend module.
	if ($agent_module->{'prediction_module'} == 8) {
		logger ($pa_config, "Executing trend module " . $agent_module->{'nombre'}, 10);
		enterprise_hook ('exec_trend_module', [$pa_config, $agent_module, $server_id, $dbh]);
		return;
	}

	# Capacity planning module.
	exec_capacity_planning_module($pa_config, $agent_module, $server_id, $dbh);
}

########################################################################
# Execute a capacity planning module.
########################################################################
sub exec_capacity_planning_module($$$$) {
	my ($pa_config, $module, $server_id, $dbh) = @_;
	my $pred;

	# Retrieve the target module.
	my $target_module = get_db_single_row($dbh, 'SELECT * FROM tagente_modulo WHERE id_agente_modulo = ?', $module->{'custom_integer_1'});
	if (!defined($target_module)) {
		pandora_update_module_on_error ($pa_config, $module, $dbh);
		return;
	}

	# Set the period.
	my $period;

	# Weekly.
	if ($module->{'custom_integer_2'} == 0) {
		$period = 604800;
	}
	# Monthly.
	elsif ($module->{'custom_integer_2'} == 1) {
		$period = 2678400;
	}
	# Daily.
	else {
		$period = 86400;
	}

	# Set other parameters.
	my $now = time();
	my $from = $now - $period;
	my $type = $module->{'custom_string_2'};
	my $target_value = $module->{'custom_string_1'};

	# Fit a line of the form: y = theta_0 + x * theta_1
	my ($theta_0, $theta_1);
	eval {
		($theta_0, $theta_1) = linear_regression($target_module, $from, $now, $dbh);
	};
	if (!defined($theta_0) || !defined($theta_1)) {
		pandora_update_module_on_error ($pa_config, $module, $dbh);
		return;
	}

	# Predict the value.
	if ($type eq 'estimation_absolute') {
		# y = theta_0 + x * theta_1
		$pred = $theta_0 + ($now + $target_value) * $theta_1;

		# Clip predictions.
		if ($target_module->{'max'} != $target_module->{'min'}) {
			if ($pred < $target_module->{'min'}) {
				$pred = $target_module->{'min'};
			}
			elsif ($pred > $target_module->{'max'}) {
				$pred = $target_module->{'max'};
			}
		}
	}
	# Predict the date.
	else {
		# Infinity.
		if ($theta_1 == 0) {
			$pred = -1;
		} else {
			# x = (y - theta_0) / theta_1
			$pred = ($target_value - $theta_0) / $theta_1;

			# Convert the prediction from a unix timestamp to days from now.
			$pred = ($pred - $now) / 86400;

			# We are not interested in past dates.
			if ($pred < 0) {
				$pred = -1;
			}
		}
	}
	
	# Update the module.
	my %data = ("data" => $pred);
	my $utimestamp = time ();
	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime($utimestamp));
	pandora_process_module ($pa_config, \%data, '', $module, '', $timestamp, $utimestamp, $server_id, $dbh);
	
	# Update the agent.
	my $agent_os_version = get_db_value ($dbh, 'SELECT os_version FROM tagente WHERE id_agente = ?', $module->{'id_agente'});
	if ($agent_os_version eq ''){
		$agent_os_version = $pa_config->{'servername'}.'_Prediction';
	}
	pandora_update_agent ($pa_config, $timestamp, $module->{'id_agente'}, undef, undef, -1, $dbh);
}

########################################################################
# Perform linear regression on the given module.
########################################################################
sub linear_regression($$$$) {
	my ($module, $from, $to, $dbh) = @_;

	# Should not happen.
	return if ($module->{'module_interval'} < 1);

	# Retrieve the data.
	my @rows = get_db_rows($dbh, 'SELECT datos, utimestamp FROM tagente_datos WHERE id_agente_modulo = ? AND utimestamp > ? AND utimestamp < ? ORDER BY utimestamp ASC', $module->{'id_agente_modulo'}, $from, $to);
	return if scalar(@rows) <= 0;

	# Perform linear regression on the data.
	my $reg = PandoraFMS::Statistics::Regression->new( "linear regression", ["const", "x"] );
	my $prev_utimestamp = $from;
	foreach my $row (@rows) {
		my ($utimestamp, $data) = ($row->{'utimestamp'}, $row->{'datos'});

		# Elapsed time.
		my $elapsed = $utimestamp - $prev_utimestamp;
		$elapsed = 1 unless $elapsed > 0;
		$prev_utimestamp = $utimestamp;

		# Number of points (Pandora compresses data!)
		my $local_count = floor($elapsed / $module->{'module_interval'});
		$local_count = 1 if $local_count <= 0;

		# Add the points.
		for (my $i = 0; $i < $local_count; $i++) {
			$reg->include($data, [1.0, $utimestamp]);
		}
	}

	return $reg->theta();
}

1;
__END__
