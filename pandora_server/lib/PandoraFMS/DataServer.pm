package PandoraFMS::DataServer;
##########################################################################
# Pandora FMS Data Server.
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
use Thread::Semaphore;

use Time::Local;
use XML::Simple;

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
# Data Server class constructor.
########################################################################################
sub new ($$;$) {
	my ($class, $config, $dbh) = @_;

	return undef unless $config->{'dataserver'} == 1;

	# Call the constructor of the parent class
	my $self = $class->SUPER::new($config, 0, \&PandoraFMS::DataServer::data_producer, \&PandoraFMS::DataServer::data_consumer, $dbh);

    bless $self, $class;
    return $self;
}

###############################################################################
# Run.
###############################################################################
sub run ($) {
	my $self = shift;
	my $pa_config = $self->getConfig ();

	print " [*] Starting Pandora FMS Data Server. \n";
	$self->setNumThreads ($pa_config->{'dataserver_threads'});
	$self->SUPER::run (\@TaskQueue, \%PendingTasks, $Sem, $TaskSem);
}

###############################################################################
# Data producer.
###############################################################################
sub data_producer ($) {
	my $self = shift;
	my $pa_config = $self->getConfig ();

	my @tasks;

	# Read all files in the incoming directory
	opendir (DIR, $pa_config->{'incomingdir'})
	        || die "[FATAL] Cannot open Incoming data directory at " . $pa_config->{'incomingdir'} . ": $!";

 	while (defined (my $file_name = readdir(DIR))) {

		# For backward compatibility
		if ($file_name =~ /^.*\.checksum$/) {
			unlink("$pa_config->{'incomingdir'}/$file_name");
			next;
		} 

		# Data files have the extension .data
		next if ($file_name !~ /^.*\.data$/);

		push (@tasks, $file_name);
	}

	closedir(DIR);
	return @tasks;
}

###############################################################################
# Data consumer.
###############################################################################
sub data_consumer ($$) {
	my ($self, $task) = @_;
	my ($pa_config, $dbh) = ($self->getConfig (), $self->getDBH ());

	my $file_name = $pa_config->{'incomingdir'};
	
	# Fix path
	$file_name .= "/" unless (substr ($file_name, -1, 1) eq '/');	
	$file_name .= $task;

	# Try to parse the XML 3 times
	my $xml_data;

	for (1..3) {
		eval {
			threads->yield;
			$xml_data = XMLin ($file_name, forcearray => 'module');
    	};
    	
    	# Invalid XML
    	if ($@) {
    		print "$@\n";
    		sleep (60);
    		next;
    	}
    	
    	unlink ($file_name);
		process_xml_data ($self->getConfig (), $xml_data, $self->getDBH ());
		return;	
	}

	rename($file_name, $file_name . '_BADXML');
    pandora_event ($pa_config, "Unable to process XML data file ($file_name)", 0, 0, 0, 0, 0, 'error', $dbh);
}

###############################################################################
# Process XML data coming from an agent.
###############################################################################
sub process_xml_data {
	my ($pa_config, $data, $dbh) = @_;

	my ($agent_name, $agent_version, $timestamp, $interval, $os_version) =
	    ($data->{'agent_name'}, $data->{'version'}, $data->{'timestamp'},
	    $data->{'interval'}, $data->{'os_version'});

	# Unknown agent!
	if (! defined ($agent_name) || $agent_name eq '') {
		logger($pa_config, 'ERROR: Received data from an unknown agent', 2);
		return;
	}
  
  	# Check some variables
   	$interval = 300 unless defined ($interval);
   	$os_version = 'N/A' if (! defined ($os_version) || $os_version eq '');
  
  	# Get agent id
	my $agent_id = get_agent_id ($dbh, $agent_name);
	if ($agent_id < 1) {
		if ($pa_config->{'autocreate'} == 0) {
			logger($pa_config, "ERROR: There is no agent defined with name $agent_name", 3);
			return;
		}
		
		# Create the agent
		my $os = pandora_get_os ($data->{'os'});
		$agent_id = pandora_create_agent ($pa_config, $pa_config->{'servername'}, $agent_name, '', 0, $pa_config->{'autocreate_group'}, 0, 0, $os, $dbh);
		return unless defined ($agent_id);
	}

	pandora_update_agent ($pa_config, $timestamp, $agent_id, $os_version, $agent_version, $interval, $dbh);
	pandora_module_keep_alive ($pa_config, $agent_id, $agent_name, $dbh);

	# Process modules
	foreach my $module_data (@{$data->{'module'}}) {
		
		# Unnamed module
		next unless (defined ($module_data->{'name'}->[0]));

		my $module_type = $module_data->{'type'}->[0];
		my $module_name = $module_data->{'name'}->[0];

	    # Single data
	    if (! defined ($module_data->{'datalist'})) {
			my $data_timestamp = (defined ($module_data->{'timestamp'})) ? $module_data->{'timestamp'}->[0] : $timestamp;
			process_module_data ($pa_config, $module_data, $agent_name, $module_name, $module_type, $data_timestamp, $dbh);
			next;
		}

		# Data list
		foreach my $list (@{$module_data->{'datalist'}}) {
			
			# Empty list
			next unless defined ($list->{'data'});
						
			foreach my $data (@{$list->{'data'}}) {
				
				# No value
				next unless defined ($data->{'value'});
							
				$module_data->{'data'} = $data->{'value'};
				my $data_timestamp = (defined ($data->{'timestamp'})) ? $data->{'timestamp'} : $timestamp;
				process_module_data ($pa_config, $module_data, $agent_name, $module_name,
									 $module_type, $data_timestamp, $dbh);
			}
		}
	}
}

##########################################################################
# Process module data, creating module if necessary.
##########################################################################
sub process_module_data ($$$$$$$) {
	my ($pa_config, $data, $agent_name, $module_name, $module_type, $timestamp, $dbh) = @_;

	my $agent = get_db_single_row ($dbh, 'SELECT * FROM tagente WHERE nombre = ?', $agent_name);
	return unless defined ($agent);

	my $module = get_db_single_row ($dbh, 'SELECT * FROM tagente_modulo WHERE id_agente = ? AND nombre = ?', $agent->{'id_agente'}, $module_name);
	if (! defined ($module)) {
		my $module_id = get_module_id ($dbh, $module_type);
		return if ($module_id == -1 && $pa_config->{'autocreate'} == 0);

		my ($min, $max, $description) = (0, 0, '');
		$max = $data->{'max'}->[0] if (defined ($data->{'max'}));
		$min = $data->{'min'}->[0] if (defined ($data->{'min'}));
		$description = $data->{'description'}->[0] if (defined ($data->{'description'}));
		pandora_create_module ($agent->{'id_agente'}, $module_id, $module_name,
	                          $max, $min, $description, $dbh);
		$module = get_db_single_row ($dbh, 'SELECT * FROM tagente_modulo WHERE id_agente = ? AND nombre = ?', $agent->{'id_agente'}, $module_name);
		return unless defined $module;
	}

	if ($timestamp =~ /(\d+)\/(\d+)\/(\d+) +(\d+):(\d+):(\d+)/ ||
	    $timestamp =~ /(\d+)\-(\d+)\-(\d+) +(\d+):(\d+):(\d+)/) {
		my $utimestamp = timelocal($6, $5, $4, $3, $2 - 1, $1 - 1900);
		pandora_process_module ($pa_config, $data->{'data'}->[0], $agent, $module, $module_type, $timestamp, $utimestamp, $dbh);
	}
}


1;
__END__
