package PandoraFMS::Server;
##########################################################################
# Pandora FMS generic server.
# Pandora FMS. the Flexible Monitoring System. http://www.pandorafms.org
##########################################################################
# Copyright (c) 2009 Ramon Novoa, rnovoa@artica.es
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

use PandoraFMS::DB;
use PandoraFMS::Core;

# defined in PandoraFMS::Core.pm
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
    };

	# Thread kill signal handler
	#$SIG{'KILL'} = sub {
    #	threads->exit() if threads->can('exit');
    #	exit();
	#};

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
		my $thr = threads->create (\&{$func}, $self);
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
		return 1 unless $thr->can ('is_running');
		return 0 unless $thr->is_running ();
	}
	
	return 1;
}

########################################################################################
# Generate a 'going up' event.
########################################################################################
sub upEvent ($) {
	my $self = shift;

	return unless defined ($self->{'_dbh'});
	pandora_event ($self->{'_pa_config'}, $self->{'_pa_config'}->{'servername'} .
	               $ServerTypes[$self->{'_server_type'}] . ' going UP',
	               0, 0, 3, 0, 0, 'system', $self->{'_dbh'});
}

########################################################################################
# Generate a 'going down' event.
########################################################################################
sub downEvent ($) {
	my $self = shift;

	return unless defined ($self->{'_dbh'});
	pandora_event ($self->{'_pa_config'}, $self->{'_pa_config'}->{'servername'} .
	               $ServerTypes[$self->{'_server_type'}] . ' going DOWN',
	               0, 0, 4, 0, 0, 'system', $self->{'_dbh'});
}

########################################################################################
# Update server status.
########################################################################################
sub update ($) {
	my $self = shift;

	eval {
		pandora_update_server ($self->{'_pa_config'}, $self->{'_dbh'}, $self->{'_pa_config'}->{'servername'},
		                       1, $self->{'_server_type'}, $self->{'_num_threads'}, $self->{'_queue_size'});
	};
}

########################################################################################
# Stop the server, killing all server threads.
########################################################################################
sub stop ($) {
	my $self = shift;

	eval {
		# Update server status
		pandora_update_server ($self->{'_pa_config'}, $self->{'_dbh'}, $self->{'_pa_config'}->{'servername'},
		                       0, $self->{'_server_type'});

		# Generate an event
		$self->downEvent ();
	};

	# Kill server threads
	foreach my $tid (@{$self->{'_threads'}}) {
		my $thr = threads->object($tid);
		next unless defined ($thr);

		# A kill method might not be available
		#if ($thr->can('kill')) {
    	#	$thr->kill('KILL')->detach();
    	#} else {
    		$thr->detach();
    	#}
	}
}

# End of function declaration
# End of defined Code

1;
__END__
