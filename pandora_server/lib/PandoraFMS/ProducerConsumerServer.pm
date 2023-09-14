package PandoraFMS::ProducerConsumerServer;
##########################################################################
# Pandora FMS generic server.
# Pandora FMS. the Flexible Monitoring System. http://www.pandorafms.org
##########################################################################
# Copyright (c) 2005-2023 Pandora FMS
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
use Time::HiRes qw(usleep);

# Default lib dir for RPM and DEB packages
BEGIN { push @INC, '/usr/lib/perl5'; }

use PandoraFMS::DB;
use PandoraFMS::Core;
use PandoraFMS::Server;

# Inherits from PandoraFMS::Server
our @ISA = qw(PandoraFMS::Server);

# Tells the producer and consumers to keep running
my $RUN :shared;

########################################################################################
# ProducerConsumerServer class constructor.
########################################################################################
sub new ($$$$$;$) {
	my ($class, $config, $server_type, $producer,
	    $consumer, $dbh) = @_;

	# Call the constructor of the parent class
	my $self = $class->SUPER::new($config, $server_type, $dbh);

	# Set producer/consumer functions
	$self->{'_producer'} = $producer;
	$self->{'_consumer'} = $consumer;

	# Run!
	$RUN = 1;
	
    bless $self, $class;
    return $self;
}

########################################################################################
# Get producer function.
########################################################################################
sub getProducer ($) {
	my $self = shift;
	
	return $self->{'_producer'};
}

########################################################################################
# Get consumer function.
########################################################################################
sub getConsumer ($) {
	my $self = shift;
	
	return $self->{'_consumer'};
}

###############################################################################
# Run.
###############################################################################
sub run ($$$$$) {
	my ($self, $task_queue, $pending_tasks, $sem, $task_sem) = @_;

	# Update server status and set server ID
	$self->update ();
	$self->setServerID ();

	# Launch consumer threads
	for (1..$self->getNumThreads ()) {

		# Enable consumer stats
		my $consumer_stats = shared_clone({
			'tstamp'      => time(),
			'rate'        => 0,
			'rate_count'  => 0,
			'rate_tstamp' => time(),
			'task_queue'  => $task_queue,
		});

		my $thr = threads->create ({'exit' => 'thread_only'},
			sub {
				my ($self, $task_queue, $pending_tasks, $sem, $task_sem) = @_;
				local $SIG{'KILL'} = sub {
					$RUN = 0;
					$task_sem->up();
					$sem->up();
					exit 0;
				};

				# Make consumer stats reachable from the thread
				$self->{'_consumer_stats'}->{threads->tid()} = $consumer_stats;

				PandoraFMS::ProducerConsumerServer::data_consumer->(@_);
			}, $self, $task_queue, $pending_tasks, $sem, $task_sem
		);
		return unless defined ($thr);
		$self->addThread ($thr->tid ());

		# Make consumer stats reachable from the main program
		$self->{'_consumer_stats'}->{$thr->tid()} = $consumer_stats;
	}


	# Enable producer stats
	my $producer_stats = shared_clone({
		'tstamp'      => time(),
		'rate'        => 0,
		'rate_count'  => 0,
		'rate_tstamp' => time(),
		'task_queue'  => $task_queue,
	});

	# Launch producer thread
	my $thr = threads->create ({'exit' => 'thread_only'},
		sub {
			my ($self, $task_queue, $pending_tasks, $sem, $task_sem) = @_;
			local $SIG{'KILL'} = sub {
				$RUN = 0;
				$task_sem->up();
				$sem->up();
				exit 0;
			};

			# Make producer stats reachable from the thread
			$self->{'_producer_stats'}->{threads->tid()} = $producer_stats;

			PandoraFMS::ProducerConsumerServer::data_producer->(@_);
		}, $self, $task_queue, $pending_tasks, $sem, $task_sem
	);
	return unless defined ($thr);
	$self->addThread ($thr->tid ());

	# Make producer stats reachable from the main program
	$self->{'_producer_stats'}->{$thr->tid()} = $producer_stats;
}

###############################################################################
# Queue pending tasks.
###############################################################################
sub data_producer ($$$$$) {
	my ($self, $task_queue, $pending_tasks, $sem, $task_sem) = @_;
	my $pa_config = $self->getConfig ();
	my $dbh;

	while ($RUN == 1) {
		eval {
			# Connect to the DB
			$dbh = db_connect ($pa_config->{'dbengine'}, $pa_config->{'dbname'}, $pa_config->{'dbhost'}, $pa_config->{'dbport'},
								  $pa_config->{'dbuser'}, $pa_config->{'dbpass'});
			$self->setDBH ($dbh);

			while ($RUN == 1) {

				# Get pending tasks
				$self->logThread('[PRODUCER] Queuing tasks.');
				my @tasks = &{$self->{'_producer'}}($self);
				
				foreach my $task (@tasks) {
					$sem->down;
					
					last if ($RUN == 0);
					if (defined $pending_tasks->{$task}) {
						$sem->up;
						next;
					}
						
					# Queue task and signal consumers
					$pending_tasks->{$task} = 0;
					push (@{$task_queue}, $task);
					$task_sem->up;
					
					$sem->up;
				}

				last if ($RUN == 0);

				# Update queue size and thread stats
				$self->setQueueSize (scalar @{$task_queue});
				$self->updateProducerStats(scalar(@tasks));

				threads->yield;
				usleep (int(1e6 * $self->getPeriod()));
			}
		};
		
		if ($@) {
			print STDERR $@;
		}
	}
	
	$task_sem->up($self->getNumThreads ());
	db_disconnect ($dbh);
	exit 0;
}

###############################################################################
# Execute pending tasks.
###############################################################################
sub data_consumer ($$$$$) {
	my ($self, $task_queue, $pending_tasks, $sem, $task_sem) = @_;
	my $pa_config = $self->getConfig ();

	my $dbh;
	my $sem_timeout = $pa_config->{'self_monitoring_interval'} > 0 ?
	                  $pa_config->{'self_monitoring_interval'} :
					  300;
	while ($RUN == 1) {
		eval {
			# Connect to the DB
			$dbh = db_connect ($pa_config->{'dbengine'}, $pa_config->{'dbname'}, $pa_config->{'dbhost'}, $pa_config->{'dbport'},
								  $pa_config->{'dbuser'}, $pa_config->{'dbpass'});
			$self->setDBH ($dbh);

			while ($RUN == 1) {
				# Wait for data
				$self->logThread('[CONSUMER] Waiting for data.');
				while (!$task_sem->down_timed($sem_timeout)) {
					$self->updateConsumerStats(0);
				}

				last if ($RUN == 0);

				$sem->down;
				my $task = shift (@{$task_queue});
				$sem->up;

				# The consumer was waiting for data when the producer exited
				last if ($RUN == 0);
				
				# Execute task
				$self->logThread("[CONSUMER] Executing task: $task");
				&{$self->{'_consumer'}}($self, $task);

				# Update thread stats
				$self->updateConsumerStats(1);

				# Update task status
				$sem->down;
				delete ($pending_tasks->{$task});
				$sem->up;

				threads->yield;
			}
		};

		if ($@) {
			print STDERR $@;
		}
	}

	db_disconnect ($dbh);
	exit 0;
}

###############################################################################
# Clean-up when the server is destroyed.
###############################################################################
sub DESTROY {
	my $self = shift;
	
	$RUN = 0;
}

1;
__END__
