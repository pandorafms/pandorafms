package PandoraFMS::AlertServer;
################################################################################
# Pandora FMS Alert Server.
# Pandora FMS. the Flexible Monitoring System. http://www.pandorafms.org
################################################################################
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
################################################################################

use strict;
use warnings;

use threads;
use threads::shared;
use Thread::Semaphore;

use MIME::Base64;
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

################################################################################
# Alert Server class constructor.
################################################################################
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

################################################################################
# Run.
################################################################################
sub run ($) {
	my $self = shift;
	my $pa_config = $self->getConfig ();

	print_message ($pa_config, " [*] Starting " . $pa_config->{'rb_product_name'} . " Alert Server.", 1);
	$self->setNumThreads ($pa_config->{'alertserver_threads'});
	$self->SUPER::run (\@TaskQueue, \%PendingTasks, $Sem, $TaskSem);
}

################################################################################
# Data producer.
################################################################################
sub data_producer ($) {
	my $self = shift;
	my ($pa_config, $dbh) = ($self->getConfig (), $self->getDBH ());

	my @tasks;
	my @rows;

	my $n_servers = get_db_value($dbh,
		'SELECT COUNT(*) FROM `tserver` WHERE `server_type` = ? AND `status` = 1',
		ALERTSERVER
	);

	my $i = 0;
	my %servers = map { $_->{'name'} => $i++; } get_db_rows($dbh,
		'SELECT `name` FROM `tserver` WHERE `server_type` = ? AND `status` = 1 ORDER BY `name` ASC',
		ALERTSERVER
	);

	if ($n_servers eq 0) {
		$n_servers = 1;
	}

	# Retrieve alerts to be evaluated.
	my $server_type_id = $servers{$pa_config->{'servername'}};
	
	# Make a local copy of locked alerts.
	$AlertSem->down ();
	my $locked_alerts = {%Alerts};
	$AlertSem->up ();

	# Check the execution queue.
	my $sql = sprintf(
		'SELECT id, utimestamp FROM talert_execution_queue
		 WHERE `id` %% %d = %d ORDER BY utimestamp ASC',
		$n_servers,
		$server_type_id
	);

	@rows = get_db_rows($dbh, $sql);

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

################################################################################
# Data consumer.
################################################################################
sub data_consumer ($$) {
	my ($self, $task_id) = @_;
	my ($pa_config, $dbh) = ($self->getConfig (), $self->getDBH ());

	eval {{
		local $SIG{__DIE__};

		# Get the alert from the queue.
		my $task = get_db_single_row ($dbh, 'SELECT * FROM talert_execution_queue WHERE id = ?', $task_id);
		if (! defined ($task)) {
			logger ($pa_config,"[ERROR] Executing invalid alert", 0);
			last 0;
		}

		my $args = PandoraFMS::Tools::p_decode_json(
			$pa_config,
			decode_base64($task->{'data'})
		);

		if (ref $args ne "ARRAY") {
			die ('Invalid alert queued');
		}

		my @args = @{$args};

		# You cannot code a DBI object into JSON, use current.
		my $execution_args = [
			$pa_config,
			@args[0..4],
			$dbh,
			@args[5..$#args]
		];

		# Execute.
		PandoraFMS::Core::pandora_execute_alert(@$execution_args);
	}};
	if ($@) {
		logger ($pa_config,"[ERROR] Executing alert ".$@, 0);
	}

	# Remove the alert from the queue and unlock.
	db_do($dbh, 'DELETE FROM talert_execution_queue WHERE id=?', $task_id);
	alert_unlock($pa_config, $task_id);
}

################################################################################
# Get a lock on the given alert. Return 1 on success, 0 otherwise.
################################################################################
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

################################################################################
# Remove the lock on the given alert.
################################################################################
sub alert_unlock {
	my ($pa_config, $alert) = @_;

	$AlertSem->down ();
	delete ($Alerts{$alert});
	$AlertSem->up ();
}

1;
__END__
