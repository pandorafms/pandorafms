package PandoraFMS::Server;
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

use POSIX 'strftime';
use threads;
use threads::shared;

# Default lib dir for RPM and DEB packages
BEGIN { push @INC, '/usr/lib/perl5'; }

use PandoraFMS::DB;
use PandoraFMS::Core;

# Defined in PandoraFMS::Core.pm
our @ServerSuffixes;

########################################################################################
# Server class constructor.
########################################################################################
sub new ($$$;$) {
	my $class = shift;
	my $self = {
		_pa_config => shift,
		_server_id => 0,
		_server_type => shift,
		_dbh => shift,
		_num_threads => 1,
		_threads => [],
		_queue_size => 0,
		_errstr => '',
		_period => 0,
		_producer_stats => {},
		_consumer_stats => {},
	};
	
	# Share variables that may be set from different threads
	share ($self->{'_queue_size'});
	share ($self->{'_errstr'});

	# Set the default period.
	$self->{'_period'} = $self->{'_pa_config'}->{'server_threshold'};
		
	bless $self, $class;
	return $self;
}

########################################################################################
# Run.
########################################################################################
sub run ($$) {
	my ($self, $func) = @_;

	# Update server status and set server ID
	$self->update ();
	$self->setServerID ();

	for (1..$self->{'_num_threads'}) {
		my $thr = threads->create ({'exit' => 'thread_only'},
			sub {
				local $SIG{'KILL'} = sub  { exit 0; };
				$func->(@_);
			}, $self
		);
		return unless defined ($thr);
		push (@{$self->{'_threads'}}, $thr->tid ());
	}
}

########################################################################################
# Set server ID.
########################################################################################
sub setServerID ($) {
	my $self = shift;
	
	my $server_id = get_server_id ($self->{'_dbh'}, $self->{'_pa_config'}->{'servername'},
	                               $self->{'_server_type'});
	return unless ($server_id > 0);
	$self->{'_server_id'} = $server_id;
}

########################################################################################
# Get server ID.
########################################################################################
sub getServerID ($) {
	my $self = shift;
	
	return $self->{'_server_id'};
}

########################################################################################
# Set the actual server queue size (used for statistics).
########################################################################################
sub setQueueSize ($$) {
	my ($self, $size) = @_;

	$self->{'_queue_size'} = $size;
}

########################################################################################
# Set the number of server threads.
########################################################################################
sub setNumThreads ($$) {
	my ($self, $num_threads) = @_;
	
	$self->{'_num_threads'} = $num_threads;
}

########################################################################################
# Get the number of server threads.
########################################################################################
sub getNumThreads ($) {
	my $self = shift;
	
	return $self->{'_num_threads'};
}

########################################################################################
# Get consumer function.
########################################################################################
sub getConsumer ($) {
	my $self = shift;
	
	return $self->{'_consumer'};
}

########################################################################################
# Set DB handler.
########################################################################################
sub setDBH ($$) {
	my ($self, $dbh) = @_;
	
	$self->{'_dbh'} = $dbh;
}

########################################################################################
# Get DB handler.
########################################################################################
sub getDBH ($) {
	my $self = shift;
	
	return $self->{'_dbh'};
}

########################################################################################
# Get config.
########################################################################################
sub getConfig ($) {
	my $self = shift;
	
	return $self->{'_pa_config'};
}

########################################################################################
# Get server type.
########################################################################################
sub getServerType ($) {
	my $self = shift;
	
	return $self->{'_server_type'};
}

########################################################################################
# Return consumer stats.
########################################################################################
sub getConsumerStats ($) {
	my $self = shift;
	
	return $self->{'_consumer_stats'};
}

########################################################################################
# Return producer stats.
########################################################################################
sub getProducerStats ($) {
	my $self = shift;
	
	return $self->{'_producer_stats'};
}

########################################################################################
# Set error string.
########################################################################################
sub setErrStr ($$) {
	my ($self, $errstr) = @_;

	$self->{'_errstr'} = $errstr;
}

########################################################################################
# Get error string.
########################################################################################
sub getErrStr ($) {
	my $self = shift;
	
	return $self->{'_errstr'};
}

########################################################################################
# Get period.
########################################################################################
sub getPeriod ($) {
	my $self = shift;
	
	return $self->{'_period'};
}

########################################################################################
# Set period.
########################################################################################
sub setPeriod ($$) {
	my ($self, $period) = @_;
	
	$self->{'_period'} = $period;
}

########################################################################################
# Set event storm protection.
########################################################################################
sub setEventStormProtection ($) {
	my ($self, $event_storm_protection) = @_;
	
	$PandoraFMS::Core::EventStormProtection = $event_storm_protection;
}

########################################################################################
# Add a thread to the server thread list.
########################################################################################
sub addThread ($$) {
	my ($self, $tid) = @_;
	push (@{$self->{'_threads'}}, $tid);
}

########################################################################################
# Returns 1 if all server threads are running, 0 otherwise.
########################################################################################
sub checkThreads ($) {
	my $self = shift;
	
	foreach my $tid (@{$self->{'_threads'}}) {
		my $thr = threads->object ($tid);
		
		# May happen when the server is killed
		if (! defined ($thr)) {
			next;
		}
		
		return 1 unless $thr->can ('is_running');
		return 0 unless $thr->is_running ();
	}
	
	return 1;
}

########################################################################################
# Returns 1 if the child process is running or there is no child process, 0 otherwise.
########################################################################################
sub checkProc ($) {
	my $self = shift;

	# Should there be a child process?
	if (defined($self->{'_child_pid'}) && $self->{'_child_pid'} != 0) {

		# Is the child process running?
		if (kill(0, $self->{'_child_pid'}) == 0) {
			return 0;
		}
	}

	return 1;
}

########################################################################################
# Generate a 'going up' event.
########################################################################################
sub upEvent ($) {
	my $self = shift;

	return unless defined ($self->{'_dbh'});
	pandora_event ($self->{'_pa_config'}, $self->{'_pa_config'}->{'servername'} .' '.
	               $ServerTypes[$self->{'_server_type'}] . ' going UP',
	               0, 0, 3, 0, 0, 'system', 0, $self->{'_dbh'});
}

########################################################################################
# Generate a 'going down' event.
########################################################################################
sub downEvent ($) {
	my $self = shift;

	return unless defined ($self->{'_dbh'});
	pandora_event ($self->{'_pa_config'}, $self->{'_pa_config'}->{'servername'} .' '.
	               $ServerTypes[$self->{'_server_type'}] . ' going DOWN',
	               0, 0, 4, 0, 0, 'system', 0, $self->{'_dbh'});
}

########################################################################################
# Generate a 'restarting' event.
########################################################################################
sub restartEvent ($$) {
	my ($self, $msg) = @_;

	return unless defined ($self->{'_dbh'});
	eval {
		pandora_event ($self->{'_pa_config'}, $self->{'_pa_config'}->{'servername'} . ' ' .
				       $ServerTypes[$self->{'_server_type'}] . " RESTARTING" . ($msg ne '' ? " ($msg)" : ''),
	                   0, 0, 4, 0, 0, 'system', 0, $self->{'_dbh'});
	};
}

########################################################################################
# Update server status.
########################################################################################
sub update ($) {
	my $self = shift;

	eval {
		pandora_update_server ($self->{'_pa_config'}, $self->{'_dbh'}, $self->{'_pa_config'}->{'servername'}, $self->{'_server_id'},
		                       1, $self->{'_server_type'}, $self->{'_num_threads'}, $self->{'_queue_size'});
	};
}

########################################################################################
# Log a message for the current thread.
########################################################################################
sub logThread ($$) {
	my ($self, $msg) = @_;

	return unless ($self->{'_pa_config'}->{'thread_log'} == 1);

	eval {
		open(my $fh, '>', $self->{'_pa_config'}->{'temporal'} . '/' . $self->{'_pa_config'}->{'servername'} .'.'. $ServerTypes[$self->{'_server_type'}] . '.' . threads->tid() . '.log');
		my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime());
		print $fh $timestamp . ' ' . $self->{'_pa_config'}->{'servername'} . ' ' . $ServerTypes[$self->{'_server_type'}] . ' (thread ' . threads->tid() . '):' . $msg . "\n";
		close($fh);
	};
}

########################################################################################
# Stop the server, killing all server threads.
########################################################################################
sub stop ($) {
	my $self = shift;

	eval {
		# Update server status
		pandora_update_server ($self->{'_pa_config'}, $self->{'_dbh'}, $self->{'_pa_config'}->{'servername'}, $self->{'_server_id'},
		                       0, $self->{'_server_type'}, 0, 0);
	};

	# Sigkill all server threads
	foreach my $tid (@{$self->{'_threads'}}) {
		my $thr = threads->object($tid);
		next unless defined ($thr);

   	$thr->kill('KILL');
	}
}

########################################################################################
# Update stats for the current thread.
########################################################################################
sub updateStats ($$$) {
	my ($self, $dest, $inc) = @_;
	my $tid = threads->tid();
	my $curr_time = time();

	# Stats disabled for this thread.
	if (!defined($dest->{$tid})) {
		return;
	}

	# Update the timestamp and count.
	$dest->{$tid}->{'tstamp'} = time();
	$dest->{$tid}->{'rate_count'} += $inc;

	# Compute the processing rate.
	my $elapsed = $curr_time - $dest->{$tid}->{'rate_tstamp'};
	if ($elapsed >= $self->{'_pa_config'}->{'self_monitoring_interval'}) {
		$dest->{$tid}->{'rate'} = $dest->{$tid}->{'rate_count'} / $elapsed;
		$dest->{$tid}->{'rate_count'} = 0;
		$dest->{$tid}->{'rate_tstamp'} = $curr_time;
		return;
	}
}


########################################################################################
# Update producer stats.
########################################################################################
sub updateProducerStats ($$) {
	my ($self, $queued_tasks) = @_;

	$self->updateStats($self->{'_producer_stats'}, $queued_tasks);
}

########################################################################################
# Update consumer stats.
########################################################################################
sub updateConsumerStats ($$) {
	my ($self, $processed_tasks) = @_;

	$self->updateStats($self->{'_consumer_stats'}, $processed_tasks);
}

# End of function declaration
# End of defined Code

1;
__END__
