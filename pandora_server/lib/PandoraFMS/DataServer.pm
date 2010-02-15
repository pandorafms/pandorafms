package PandoraFMS::DataServer;
##########################################################################
# Pandora FMS Data Server.
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

use Time::Local;
use XML::Simple;
use POSIX qw(setsid strftime);

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
my $AgentSem :shared = Thread::Semaphore->new (1);
my $ModuleSem :shared = Thread::Semaphore->new (1);

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

	print_message ($pa_config, " [*] Starting Pandora FMS Data Server.", 1);
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

	my $queue_count = 0;
 	while (defined (my $file_name = readdir(DIR))) {
		if ($queue_count > $pa_config->{"max_queue_files"}) {
			last;
		}

		# For backward compatibility
		if ($file_name =~ /^.*\.checksum$/) {
			unlink("$pa_config->{'incomingdir'}/$file_name");
			next;
		} 

		# Data files must have the extension .data
		next if ($file_name !~ /^.*\.data$/);

		$queue_count++;
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
    		sleep (10);
    		next;
    	}
    	
	# Ignore the timestamp in the XML and use the file timestamp instead
    	$xml_data->{'timestamp'} = strftime ("%Y-%m-%d %H:%M:%S", localtime((stat($file_name))[9])) if ($pa_config->{'use_xml_timestamp'} eq '1' || ! defined ($xml_data->{'timestamp'}));

    	unlink ($file_name);
		process_xml_data ($self->getConfig (), $file_name, $xml_data, $self->getServerID (), $self->getDBH ());
		return;	
	}

	rename($file_name, $file_name . '_BADXML');
    pandora_event ($pa_config, "Unable to process XML data file ($file_name)", 0, 0, 0, 0, 0, 'error', 0, $dbh);
}

###############################################################################
# Process XML data coming from an agent.
###############################################################################
sub process_xml_data ($$$$$) {
	my ($pa_config, $file_name, $data, $server_id, $dbh) = @_;

	my ($agent_name, $agent_version, $timestamp, $interval, $os_version, $timezone_offset) =
	    ($data->{'agent_name'}, $data->{'version'}, $data->{'timestamp'},
	    $data->{'interval'}, $data->{'os_version'}, $data->{'timezone_offset'});

	# Timezone offset must be an integer beween -12 and +12
	if (!defined($timezone_offset) || $timezone_offset !~ /[-+]?[0-9,11,12]/) {
		$timezone_offset = 0; # Default value
	}

	my $valid_position_data = 1; 	

	# Get GIS information
	my ($longitude, $latitude, $altitude) = (
	    $data->{'longitude'}, $data->{'latitude'}, $data->{'altitude'});

	if ($pa_config->{'activate_gis'}) {

		# Validate the GIS informtation
		if (!defined($altitude) || $altitude !~ /[-+]?[0-9,11,12]/) {
			$altitude = 0; # Default value
		}

		# If position data (at least longitude and latitde)  are not valid should be ignored
		if ($longitude !~ /[-+]?[0-9]*\.?[0-9]+/ || $latitude !~ /[-+]?[0-9]*\.?[0-9]+/ || !defined($longitude) || !defined($latitude)) {
			$valid_position_data = 0;	
			$longitude = '';
			$latitude = '';
			$altitude='';
		}
			
		logger($pa_config, "Getting GIS Data=timezone_offset=$timezone_offset longitude=$longitude latitude=$latitude altitude=$altitude", 10);
	}
	# Unknown agent!
	if (! defined ($agent_name) || $agent_name eq '') {
		logger($pa_config, "$file_name has data from an unnamed agent", 3);
		return;
	}
  
	# Get current datetime from system if value AUTO is coming in the XML
	if ( $data->{'timestamp'} =~ /AUTO/ ){
		$timestamp = strftime ("%Y/%m/%d %H:%M:%S", localtime());
	}
	else {
		if ($timezone_offset != 0) {
		# Modify the timestamp with the timezone_offset
		logger($pa_config, "Unmodified timestamp = $timestamp", 5);
			$timestamp =~ /(\d+)[\/|\-](\d+)[\/|\-](\d+) +(\d+):(\d+):(\d+)/;
			logger($pa_config, "Unmodified timestamp = $1/$2/$3 $4:$5:$6", 5);
			my $utimestamp = timelocal($6, $5, $4, $3, $2 -1 , $1 - 1900) + ($timezone_offset * 3600); 
		logger($pa_config, "Seconds timestamp = $timestamp modified timestamp in seconds $utimestamp with timezone_offset = $timezone_offset", 5);
        $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime($utimestamp));
		}
		logger($pa_config, "Modified timestamp = $timestamp with timezone_offset = $timezone_offset", 5);
	}

	
  	# Check some variables
   	$interval = 300 if (! defined ($interval) || $interval eq '');
   	$os_version = 'N/A' if (! defined ($os_version) || $os_version eq '');
  
  	# Get agent id
  	$AgentSem->down ();
	my $agent_id = get_agent_id ($dbh, $agent_name);
	if ($agent_id < 1) {
		if ($pa_config->{'autocreate'} == 0) {
			logger($pa_config, "ERROR: There is no agent defined with name $agent_name", 3);
			$AgentSem->up ();
			return;
		}
		
		# Get OS, group and description
		my $os = pandora_get_os ($data->{'os_name'});
		my $group_id = undef;
		$group_id = get_db_value ($dbh, 'SELECT id_grupo FROM tgrupo WHERE nombre = ?', $data->{'group'}) if (defined ($data->{'group'}));
		$group_id = $pa_config->{'autocreate_group'} unless defined ($group_id);
		my $description = '';
		$description = $data->{'description'} if (defined ($data->{'description'}));

		# Create the agent
		if ($valid_position_data  == 1 && $pa_config->{'activate_gis'} != 0 ) {
			logger($pa_config, "Creating agent $agent_name at long: $longitude lat: $latitude alt: $altitude", 5);
			$agent_id = pandora_create_agent($pa_config, $pa_config->{'servername'}, $agent_name, '', 0, $group_id, 0, $os, 
												  $description, $interval, $dbh, $timezone_offset, $longitude, $latitude, $altitude);
		}
		else { # Ignore agent positional data
			logger($pa_config, "Creating agent $agent_name", 5);
			$agent_id = pandora_create_agent($pa_config, $pa_config->{'servername'}, $agent_name, '', 0, $group_id, 0, $os,
												  $description, $interval, $dbh, $timezone_offset);
		}
		if (! defined ($agent_id)) {
			$AgentSem->up ();
			return;
		}
	}
  	$AgentSem->up ();

	if ($valid_position_data  == 1 && $pa_config->{'activate_gis'} != 0) {
    	logger($pa_config, "Updating agent $agent_name at long: $longitude lat: $latitude alt: $altitude", 5);
		# Update agent information including position information
		pandora_update_agent($pa_config, $timestamp, $agent_id, $os_version, $agent_version, $interval, $dbh, $timezone_offset, $longitude, $latitude, $altitude);
	}
	else {
    	logger($pa_config, "Updating agent $agent_name", 5);
		# Update agent information without position information
		pandora_update_agent($pa_config, $timestamp, $agent_id, $os_version, $agent_version, $interval, $dbh, $timezone_offset);
	}	
	pandora_module_keep_alive ($pa_config, $agent_id, $agent_name, $server_id, $dbh);

	# Process modules
	foreach my $module_data (@{$data->{'module'}}) {

		my $module_name = get_tag_value ($module_data, 'name', '');

		# Unnamed module
		next if ($module_name eq '');

		my $module_type = get_tag_value ($module_data, 'type', 'generic_data');

	    # Single data
	    if (! defined ($module_data->{'datalist'})) {
			my $data_timestamp = get_tag_value ($module_data, 'timestamp', $timestamp);
			process_module_data ($pa_config, $module_data, $server_id, $agent_name, $module_name, $module_type, $interval, $data_timestamp, $dbh);
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
				my $data_timestamp = get_tag_value ($module_data, 'timestamp', $timestamp);
				process_module_data ($pa_config, $module_data, $server_id, $agent_name, $module_name,
									 $module_type, $interval, $data_timestamp, $dbh);
			}
		}
	}

	# Process inventory modules
	enterprise_hook('process_inventory_data', [$pa_config, $data, $server_id, $agent_name,
	                                           $interval, $timestamp, $dbh]);
}

##########################################################################
# Process module data, creating module if necessary.
##########################################################################
sub process_module_data ($$$$$$$$$) {
	my ($pa_config, $data, $server_id, $agent_name,
	    $module_name, $module_type, $interval, $timestamp,
	    $dbh) = @_;

	# Get agent data
	my $agent = get_db_single_row ($dbh, 'SELECT * FROM tagente WHERE nombre = ?', $agent_name);
	if (! defined ($agent)) {
		logger($pa_config, "Invalid agent '$agent_name' for module '$module_name'.", 3);
		return;
	}

	# Get module data or create it if it does not exist
	$ModuleSem->down ();
	my $module = get_db_single_row ($dbh, 'SELECT * FROM tagente_modulo WHERE id_agente = ? AND nombre = ?', $agent->{'id_agente'}, $module_name);
	if (! defined ($module)) {
		# Do not auto create modules
		if ($pa_config->{'autocreate'} ne '1') {
			logger($pa_config, "Module '$module_name' not found for agent '$agent_name' and module auto-creation disabled.", 10);
			$ModuleSem->up ();
			return;
		}

		# Is the agent learning?
		if ($agent->{'modo'} ne '1') {
			logger($pa_config, "Learning mode disabled. Skipping module '$module_name' agent '$agent_name'.", 10);
			$ModuleSem->up ();
			return;
		}

		# Get the module type
		my $module_id = get_module_id ($dbh, $module_type);
		if ($module_id <= 0) {
			logger($pa_config, "Invalid module type '$module_type' for module '$module_name' agent '$agent_name'.", 3);
			$ModuleSem->up ();
			return;
		}

		# Get min/max/description/post process
		my $max = get_tag_value ($data, 'max', 0);
		my $min = get_tag_value ($data, 'min', 0);
		my $description = get_tag_value ($data, 'description', '');
		my $post_process = get_tag_value ($data, 'post_process', 0);

		# Allow , as a decimal separator
		$post_process =~ s/,/./;

		# Create the module
		pandora_create_module ($pa_config, $agent->{'id_agente'}, $module_id, $module_name,
	                          $max, $min, $post_process, $description, $interval, $dbh);
		$module = get_db_single_row ($dbh, 'SELECT * FROM tagente_modulo WHERE id_agente = ? AND nombre = ?', $agent->{'id_agente'}, $module_name);
		if (! defined ($module)) {
			logger($pa_config, "Could not create module '$module_name' for agent '$agent_name'.", 3);
			$ModuleSem->up ();
			return;
		}
	}
	$ModuleSem->up ();

	# Module disabled!
	if ($module->{'disabled'} eq '1') {
		logger($pa_config, "Skipping disabled module '$module_name' agent '$agent_name'.", 10);
		return;
	}

	# Parse the timestamp and process the module
	if ($timestamp !~ /(\d+)\/(\d+)\/(\d+) +(\d+):(\d+):(\d+)/ &&
	    $timestamp !~ /(\d+)\-(\d+)\-(\d+) +(\d+):(\d+):(\d+)/) {
		logger($pa_config, "Invalid timestamp '$timestamp' from module '$module_name' agent '$agent_name'.", 3);
		return;
	}

	my $utimestamp = timelocal($6, $5, $4, $3, $2 - 1, $1 - 1900);
	
	#my $value = get_tag_value ($data, 'data', '');		
	my $dataObject = get_module_data($data, $agent_name, $module_name, $module_type);
	my $extraMacros = get_macros_for_data($data, $agent_name, $module_name, $module_type);
	
	pandora_process_module ($pa_config, $dataObject, $agent, $module, $module_type, $timestamp, $utimestamp, $server_id, $dbh, $extraMacros);
}

sub get_module_data($$){
	my ($data, $agent_name, $module_name, $module_type) = @_;
	
	my %dataObject;
	
	if ($module_type eq "log4x") {
		foreach my $attr ('severity','message', 'stacktrace'){
			$dataObject{$attr} = get_tag_value ($data, $attr, '');
		}
	} else {
		# Default case
		my $value = get_tag_value ($data, 'data', '');
		$dataObject{'data'} = $value;
	}
	#return $value;
	return \%dataObject;
}

sub get_macros_for_data($$){
	my ($data, $agent_name, $module_name, $module_type) = @_;
	
	my %macros;
	
	if ($module_type eq "log4x") {
		foreach my $attr ('severity','message', 'stacktrace'){
			my $macro = "_" . $attr . "_";
			$macros{$macro} = get_tag_value ($data, $attr, '');
		}
	} else {
	}
	
	return \%macros;
}

1;
__END__
