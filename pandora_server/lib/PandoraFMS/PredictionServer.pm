package PandoraFMS::PredictionServer;
########################################################################
# Pandora FMS Prediction Server.
# Pandora FMS. the Flexible Monitoring System. http://www.pandorafms.org
########################################################################
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

use IO::Socket::INET;
use Net::Ping;
use POSIX qw(strftime);

# Default lib dir for RPM and DEB packages
use lib '/usr/lib/perl5';

use PandoraFMS::Tools;
use PandoraFMS::DB;
use PandoraFMS::Core;
use PandoraFMS::ProducerConsumerServer;

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
	
	print_message ($pa_config, " [*] Starting Pandora FMS Prediction Server.", 1);
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
			ORDER BY last_execution_try ASC ', $pa_config->{'servername'});
	}
	else {
		@rows = get_db_rows ($dbh, 'SELECT DISTINCT(tagente_modulo.id_agente_modulo),
				tagente_modulo.flag, last_execution_try
			FROM tagente, tagente_modulo, tagente_estado
			WHERE ((server_name = ?)
				OR (server_name = ANY(SELECT name
					FROM tserver
					WHERE status = 0 AND server_type = ?)))
				AND tagente_modulo.id_agente = tagente.id_agente
				AND tagente.disabled = 0
				AND tagente_modulo.disabled = 0
				AND tagente_modulo.prediction_module != 0
				AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
				AND tagente_modulo.id_modulo = 5
				AND (tagente_modulo.flag = 1
				OR (tagente_estado.last_execution_try + tagente_estado.current_interval) < UNIX_TIMESTAMP())
			ORDER BY last_execution_try ASC', $pa_config->{'servername'}, PREDICTIONSERVER);
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
				$agent_module->{'nombre'}, 5);
			enterprise_hook ('exec_service_module_sla', [$pa_config, $agent_module, $server_id, $dbh]);
			logger ($pa_config, "End execution", 5);
		}
		elsif ($agent_module->{'custom_string_1'} eq 'SLA_Value')  {
			#Do none
		}
		else {
			logger ($pa_config, "Executing service module " .
				$agent_module->{'id_agente_modulo'} . " " .
				$agent_module->{'nombre'}, 5);
			enterprise_hook ('exec_service_module', [$pa_config, $agent_module, $server_id, $dbh]);
			logger ($pa_config, "End execution", 5);
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
	
	# Get a full hash for target agent_module record reference ($target_module)
	my $target_module = get_db_single_row ($dbh, 'SELECT * FROM tagente_modulo WHERE id_agente_modulo = ?', $agent_module->{'custom_integer_1'});
	return unless defined $target_module;
	
	# Prediction mode explanation
	#
	# 0 is for target type of generic_proc. It compares latest data with current data. Needs to get
	# data on a "middle" interval, so if interval is 300, get data to compare with 150  before 
	# and 150 in the future. If current data is ABOVE or BELOW average +- typical_deviation 
	# this is a BAD value (0), if not is ok (1) and written in target module as is.
	# more interval configured for this module, more "margin" has to compare data.
	#
	# 1 is for target type of generic_data. It get's data in the future, using the interval given in
	# module. It gets average from current timestamp to INTERVAL in the future and gets average
	# value. Typical deviation is not used here. 
	
	# 0 proc, 1 data
	my $prediction_mode = ($agent_module->{'id_tipo_modulo'} == 2) ? 0 : 1;
	
	# Initialize another global sub variables.
	my $module_data = 0;    # 0 data for default
	
	# Get current timestamp
	my $utimestamp = time ();
	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime($utimestamp));
	
	# Get different data from each week one month ago (4 values)
	# $agent_module->{'module_interval'} uses a margin of interval to get average data from the past
	my @week_data;
	my @week_utimestamp;
	
	for (my $i=0; $i<4; $i++) {
		$week_utimestamp[$i] = $utimestamp - (84600*7*($i+1));
		# Adjust for proc prediction
		if ($prediction_mode == 0) {
			$week_utimestamp[$i] = $week_utimestamp[$i] - ($agent_module->{'module_interval'} / 2);
		}
	}
	
	# Let's calculate statistical average using past data
	# n = total of real data values
	my ($n, $average, $temp1) = (0, 0, 0);
	for (my $i=0; $i < 4; $i++) {
		my ($first_data, $last_data, $average_interval);
		my $sum_data = 0;
		
		$temp1 = $week_utimestamp[$i] + $agent_module->{'module_interval'};
		# Get data for week $i in the past
		
		$average_interval = get_db_value ($dbh, 'SELECT AVG(datos)
			FROM tagente_datos
			WHERE id_agente_modulo = ?
				AND utimestamp > ?
				AND utimestamp < ?', $target_module->{'id_agente_modulo'}, $week_utimestamp[$i], $temp1);
		
		# Need to get data outside interval because no data.
		if (!(defined($average_interval)) || ($average_interval == 0)) {
			$last_data = get_db_value ($dbh, 'SELECT datos
				FROM tagente_datos
				WHERE id_agente_modulo = ?
					AND utimestamp > ?
				LIMIT 1', $target_module->{'id_agente_modulo'}, $week_utimestamp[$i]);
			next unless defined ($last_data);
			$first_data = get_db_value ($dbh, 'SELECT datos
				FROM tagente_datos
				WHERE id_agente_modulo = ?
					AND utimestamp < ?
				LIMIT 1', $target_module->{'id_agente_modulo'}, $temp1);
			next unless defined ($first_data);
			$sum_data++ if ($last_data != 0);
			$sum_data++ if ($first_data != 0);
			$week_data[$i] = ($sum_data > 0) ? (($last_data + $first_data) / $sum_data) : 0;
		}
		else {
			$week_data[$i] = $average_interval;
		}
		
		# It's possible that one of the week_data[i] values was not valid (NULL)
		# so recheck it and relay on n=0 for "no data" values set to 0 in result
		# Calculate total ammount of valida data for each data sample
		if ((is_numeric($week_data[$i])) && ($week_data[$i] > 0)) {
			$n++;
			# Average SUM
			$average = $average + $week_data[$i];
		}
	}
	
	# Real average value
	$average = ($n > 0) ? ($average / $n) : 0;
	
	# (PROC) Compare with current data
	if ($prediction_mode == 0) {
		# Calculate typical deviation
		my $typical_deviation = 0;
		for (my $i=0; $i< $n; $i++) {
			if ((is_numeric($week_data[$i])) && ($week_data[$i] > 0)) {
				$typical_deviation = $typical_deviation + (($week_data[$i] - $average)**2);
			}
		}
		$typical_deviation = ($n > 1) ? sqrt ($typical_deviation / ($n-1)) : 0;
		
		my $current_value = get_db_value ($dbh, 'SELECT datos
			FROM tagente_estado
			WHERE id_agente_modulo = ?', $target_module->{'id_agente_modulo'});
		if ( ($current_value > ($average - $typical_deviation)) && ($current_value < ($average + $typical_deviation)) ){
			$module_data = 1; # OK !!
		}
		else {
			$module_data = 0; # Out of predictions
		}
	}
	else {
		# Prediction based on data
		$module_data = $average;
	}
	
	my %data = ("data" => $module_data);
	pandora_process_module ($pa_config, \%data, '', $agent_module, '', $timestamp, $utimestamp, $server_id, $dbh);
	
	my $agent_os_version = get_db_value ($dbh, 'SELECT os_version
		FROM tagente
		WHERE id_agente = ?', $agent_module->{'id_agente'});
	
	if ($agent_os_version eq ''){
		$agent_os_version = $pa_config->{'servername'}.'_Prediction';
	}
	
	pandora_update_agent ($pa_config, $timestamp, $agent_module->{'id_agente'}, $agent_os_version, $pa_config->{'version'}, -1, $dbh);
}

1;
__END__
