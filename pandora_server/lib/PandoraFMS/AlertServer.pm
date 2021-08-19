package PandoraFMS::AlertServer;
##########################################################################
# Pandora FMS Alert Server.
# Pandora FMS. the Flexible Monitoring System. http://www.pandorafms.org
##########################################################################
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

use JSON;
use POSIX qw(strftime);

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
my $AlertSem :shared;
my %Alerts :shared;
my $EventRef :shared = 0;

########################################################################################
# Alert Server class constructor.
########################################################################################
sub new ($$$) {
	my ($class, $config, $dbh) = @_;

	return undef unless $config->{'alertserver'} == 1;

	# Initialize semaphores and queues
	@TaskQueue = ();
	%PendingTasks = ();
	$Sem = Thread::Semaphore->new;
	$TaskSem = Thread::Semaphore->new (0);
	$AlertSem = Thread::Semaphore->new (1);
	
	# Call the constructor of the parent class
	my $self = $class->SUPER::new($config, ALERTSERVER, \&PandoraFMS::AlertServer::data_producer, \&PandoraFMS::AlertServer::data_consumer, $dbh);

    bless $self, $class;
    return $self;
}

###############################################################################
# Run.
###############################################################################
sub run ($) {
	my $self = shift;
	my $pa_config = $self->getConfig ();

	print_message ($pa_config, " [*] Starting " . $pa_config->{'rb_product_name'} . " Alert Server.", 1);
	$self->setNumThreads ($pa_config->{'alertserver_threads'});
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
	
	# Make a local copy of locked alerts.
	$AlertSem->down ();
	my $locked_alerts = {%Alerts};
	$AlertSem->up ();

	# Check the execution queue.
	if (pandora_is_master($pa_config) == 1) {
		@rows = get_db_rows ($dbh, 'SELECT id, utimestamp FROM talert_execution_queue ORDER BY utimestamp ASC');
	}

	# Queue alerts.
	foreach my $row (@rows) {
		next if (alert_lock($pa_config, $row->{'id'}, $locked_alerts) == 0);
		push (@tasks, $row->{'id'});

		# Generate an event if execution delay is high (every 1 hour at most).
		my $now = time();
		if (($pa_config->{'alertserver_warn'} > 0) &&
		    ($now - $row->{'utimestamp'} > $pa_config->{'alertserver_warn'}) &&
			($EventRef + 3600 < $now)) {
			$EventRef = $now;
			pandora_event ($pa_config, "Alert execution delay has exceeded " . $pa_config->{'alertserver_warn'} . " seconds.", 0, 0, 3, 0, 0, 'system', 0, $dbh);
		}

	}

	return @tasks;
}

###############################################################################
# Data consumer.
###############################################################################
sub data_consumer ($$) {
	my ($self, $task_id) = @_;
	my ($pa_config, $dbh) = ($self->getConfig (), $self->getDBH ());

	eval {{
		# Get the alert from the queue.
		my $task = get_db_single_row ($dbh, 'SELECT * FROM talert_execution_queue WHERE id = ?', $task_id);
		if (! defined ($task)) {
			logger ($pa_config,"[ERROR] Executing invalid alert", 0);
			last 0;
		}

		# Get the alert data.
		my $alert = get_db_single_row ($dbh, 'SELECT talert_template_modules.id as id_template_module,
			talert_template_modules.*, talert_templates.*
			FROM talert_template_modules, talert_templates
			WHERE talert_template_modules.id_alert_template = talert_templates.id
			AND talert_template_modules.id = ?', $task->{'id_alert_template_module'});
		if (! defined ($alert)) {
			logger($pa_config, "Alert ID " . $task->{'id_alert_template_module'} . " not found.", 10);
			last;
		}

		# Get the agent and module associated with the alert
		my $module = get_db_single_row ($dbh, 'SELECT * FROM tagente_modulo WHERE id_agente_modulo = ?', $alert->{'id_agent_module'});
		if (! defined ($module)) {
			logger($pa_config, "Module ID " . $alert->{'id_agent_module'} . " not found for alert ID " . $alert->{'id_template_module'} . ".", 10);
			last;
		}
		my $agent = get_db_single_row ($dbh, 'SELECT * FROM tagente WHERE id_agente = ?', $module->{'id_agente'});
		if (! defined ($agent)) {
			logger($pa_config, "Agent ID " . $module->{'id_agente'} . " not found for module ID " . $module->{'id_agente_modulo'} . " alert ID " . $alert->{'id_template_module'} . ".", 10);
			last;
		}

		# Execute the alert.
		pandora_execute_alert ($pa_config, $task->{'data'}, $agent, $module, $alert, $task->{'alert_mode'},
			$dbh, strftime ("%Y-%m-%d %H:%M:%S", localtime()), 0, decode_json($task->{'extra_macros'}));
	}};

	# Remove the alert from the queue and unlock.
	db_do($dbh, 'DELETE FROM talert_execution_queue WHERE id=?', $task_id);
	alert_unlock($pa_config, $task_id);
}

##########################################################################
# Get a lock on the given alert. Return 1 on success, 0 otherwise.
##########################################################################
sub alert_lock {
	my ($pa_config, $alert, $locked_alerts) = @_;

	if (defined($locked_alerts->{$alert})) {
		return 0;
	}

	$locked_alerts->{$alert} = 1;
	$AlertSem->down ();
	$Alerts{$alert} = 1;
	$AlertSem->up ();

	return 1;
}

##########################################################################
# Remove the lock on the given alert.
##########################################################################
sub alert_unlock {
	my ($pa_config, $alert) = @_;

	$AlertSem->down ();
	delete ($Alerts{$alert});
	$AlertSem->up ();
}

1;
__END__
