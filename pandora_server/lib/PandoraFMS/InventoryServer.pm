package PandoraFMS::InventoryServer;

##########################################################################
# Pandora FMS Inventory Server.
##########################################################################
# Copyright (c) 2007-2023 Pandora FMS
# This code is not free or OpenSource. Please don't redistribute.
##########################################################################

use strict;
use warnings;

use threads;
use threads::shared;
use Thread::Semaphore;

use File::Temp qw(tempfile unlink0);
use POSIX qw(strftime);
use HTML::Entities;
use MIME::Base64;
use JSON;

# UTF-8 flags control with I/O for multibyte characters
use open ":utf8";

# Default lib dir for RPM and DEB packages
BEGIN { push @INC, '/usr/lib/perl5'; }

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

########################################################################################
# Inventory Server class constructor.
########################################################################################
sub new ($$;$) {
	my ($class, $config, $dbh) = @_;

	return undef unless $config->{'inventoryserver'} == 1;

	# Initialize semaphores and queues
	@TaskQueue = ();
	%PendingTasks = ();
	$Sem = Thread::Semaphore->new;
	$TaskSem = Thread::Semaphore->new (0);
	
	# Call the constructor of the parent class
	my $self = $class->SUPER::new($config, INVENTORYSERVER, \&PandoraFMS::InventoryServer::data_producer, \&PandoraFMS::InventoryServer::data_consumer, $dbh);

	bless $self, $class;
	return $self;
}

###############################################################################
# Run.
###############################################################################
sub run ($) {
	my $self = shift;
	my $pa_config = $self->getConfig ();

	print_message ($pa_config, " [*] Starting " . $pa_config->{'rb_product_name'} . " Inventory Server.", 1);
	$self->setNumThreads ($pa_config->{'inventory_threads'});
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
		if ($pa_config->{'dbengine'} ne 'oracle') {
			@rows = get_db_rows ($dbh,
				'SELECT tagent_module_inventory.id_agent_module_inventory, tagent_module_inventory.flag, tagent_module_inventory.timestamp
				FROM tagente, tagent_module_inventory, tmodule_inventory
				WHERE tagente.server_name = ?
					AND tmodule_inventory.id_module_inventory = tagent_module_inventory.id_module_inventory
					AND tmodule_inventory.id_os IS NOT NULL
					AND tagente.id_agente = tagent_module_inventory.id_agente
					AND tagent_module_inventory.target <> \'\'
					AND tagente.disabled = 0
					AND (tagent_module_inventory.timestamp = \'1970-01-01 00:00:00\'
						OR UNIX_TIMESTAMP(tagent_module_inventory.timestamp) + tagent_module_inventory.interval < UNIX_TIMESTAMP()
						OR tagent_module_inventory.flag = 1)
				ORDER BY tagent_module_inventory.timestamp ASC',
				$pa_config->{'servername'});
		}
		else {
			@rows = get_db_rows ($dbh,
				'SELECT tagent_module_inventory.id_agent_module_inventory, tagent_module_inventory.flag, tagent_module_inventory.timestamp
				FROM tagente, tagent_module_inventory, tmodule_inventory
				WHERE tagente.server_name = ?
					AND tmodule_inventory.id_module_inventory = tagent_module_inventory.id_module_inventory
					AND tmodule_inventory.id_os IS NOT NULL
					AND tagente.id_agente = tagent_module_inventory.id_agente
					AND tagent_module_inventory.target IS NOT NULL
					AND tagente.disabled = 0
					AND (tagent_module_inventory.timestamp = \'1970-01-01 00:00:00\'
						OR UNIX_TIMESTAMP(tagent_module_inventory.timestamp) + tagent_module_inventory.' . ${RDBMS_QUOTE} . 'interval' . ${RDBMS_QUOTE} . '< UNIX_TIMESTAMP()
						OR tagent_module_inventory.flag = 1)
				ORDER BY tagent_module_inventory.timestamp ASC',
				$pa_config->{'servername'});
		}
	}
	else {
		if ($pa_config->{'dbengine'} ne 'oracle') {
			@rows = get_db_rows ($dbh,
				'SELECT tagent_module_inventory.id_agent_module_inventory, tagent_module_inventory.flag, tagent_module_inventory.timestamp
				FROM tagente, tagent_module_inventory, tmodule_inventory 
				WHERE (server_name = ? OR server_name NOT IN (SELECT name FROM tserver WHERE status = 1 AND server_type = ?)) 
					AND tmodule_inventory.id_module_inventory = tagent_module_inventory.id_module_inventory
					AND tmodule_inventory.id_os IS NOT NULL 
					AND tagente.id_agente = tagent_module_inventory.id_agente
					AND tagent_module_inventory.target <> \'\'
					AND tagente.disabled = 0
					AND (tagent_module_inventory.timestamp = \'1970-01-01 00:00:00\'
						OR UNIX_TIMESTAMP(tagent_module_inventory.timestamp) + tagent_module_inventory.interval < UNIX_TIMESTAMP()
						OR tagent_module_inventory.flag = 1)
				ORDER BY tagent_module_inventory.timestamp ASC',
				$pa_config->{'servername'}, INVENTORYSERVER);
		}
		else {
			@rows = get_db_rows ($dbh,
				'SELECT tagent_module_inventory.id_agent_module_inventory, tagent_module_inventory.flag, tagent_module_inventory.timestamp
				FROM tagente, tagent_module_inventory, tmodule_inventory 
				WHERE (server_name = ? OR server_name NOT IN (SELECT name FROM tserver WHERE status = 1 AND server_type = ?)) 
					AND tmodule_inventory.id_module_inventory = tagent_module_inventory.id_module_inventory
					AND tmodule_inventory.id_os IS NOT NULL 
					AND tagente.id_agente = tagent_module_inventory.id_agente
					AND tagent_module_inventory.target IS NOT NULL
					AND tagente.disabled = 0
					AND (tagent_module_inventory.timestamp = \'1970-01-01 00:00:00\'
						OR UNIX_TIMESTAMP(tagent_module_inventory.timestamp) + tagent_module_inventory.' . ${RDBMS_QUOTE} . 'interval' . ${RDBMS_QUOTE} . ' < UNIX_TIMESTAMP()
						OR tagent_module_inventory.flag = 1)
				ORDER BY tagent_module_inventory.timestamp ASC',
				$pa_config->{'servername'}, INVENTORYSERVER);
		}
	}

	foreach my $row (@rows) {

		# Reset forced execution flag
		if ($row->{'flag'} == 1) {
			db_do ($dbh, 'UPDATE tagent_module_inventory SET flag = 0 WHERE id_agent_module_inventory = ?', $row->{'id_agent_module_inventory'});
		}

		push (@tasks, $row->{'id_agent_module_inventory'});
	}

	return @tasks;
}

###############################################################################
# Data consumer.
###############################################################################
sub data_consumer ($$) {
	my ($self, $module_id) = @_;
	my ($pa_config, $dbh) = ($self->getConfig (), $self->getDBH ());
	
	my $timeout = $pa_config->{'inventory_timeout'};

	# Get inventory module data
	my $module = get_db_single_row ($dbh,
		'SELECT * FROM tagent_module_inventory, tmodule_inventory
		WHERE tagent_module_inventory.id_agent_module_inventory = ?
			AND tagent_module_inventory.id_module_inventory = tmodule_inventory.id_module_inventory',
		$module_id);

	my $command;
	my ($fh, $temp_file) = tempfile();
	
	if ($module->{'script_mode'} == '1') {
		my $script_file = $module->{'script_path'};
		$command = $module->{'interpreter'} . ' ' . $script_file . ' "' . $module->{'target'} . '"';
	} else {
		# Save script in a temporary file
		$fh->print (decode_base64($module->{'code'}));
		close ($fh);
		set_file_permissions($pa_config, $temp_file, "0777");

		# Run the script
		$command = $module->{'interpreter'} . ' ' . $temp_file . ' "' . $module->{'target'} . '"';
	}
	
	# Try to read the custom fields to use them as arguments into the command
	if (defined($module->{'custom_fields'}) && $module->{'custom_fields'} ne '') {
		my $decoded_cfields;

		eval {
			$decoded_cfields = decode_json (decode_base64 ($module->{'custom_fields'}));
		};
		if ($@) {
			logger($pa_config, "Failed to encode received inventory data", 10);
		}

		if (!defined ($decoded_cfields)) {
			logger ($pa_config, "Remote inventory module ".$module->{'name'}." has failed because the custom fields can't be read", 6);

			if ($module->{'script_mode'} == '2') {
				unlink ($temp_file);
			}

			return;
		}

		foreach my $field (@{$decoded_cfields}) {
			if ($field->{'secure'}) {
				$command .= ' "' . pandora_output_password($pa_config, $field->{'value'}) . '"';
			}
			else {
				$command .= ' "' . $field->{'value'} . '"';
			}
		}
	}
	# Add the default user/password arguments to the command
	else {
		# Initialize macros.
		my %macros = (
			'_agentcustomfield_\d+_' => undef,
		);

		my $wmi_user = safe_output(subst_column_macros($module->{"username"}, \%macros, $pa_config, $dbh, undef, $module));
		my $wmi_pass = safe_output(pandora_output_password($pa_config, subst_column_macros($module->{"password"}, \%macros, $pa_config, $dbh, undef, $module)));
		$command .= ' "' . $wmi_user . '" "' . $wmi_pass . '"';
	}

	logger ($pa_config, "Inventory execution command $command", 10);
	my $data = `$command 2>$DEVNULL`;
	
	# Check for errors
	if ($? != 0) {
		logger ($pa_config,  "Remote inventory module ".$module->{'name'}." has failed with error level $?", 6);

		if ($module->{'script_mode'} == '2') {
			unlink ($temp_file);
		}

		return;
	}
	
	if ($module->{'script_mode'} == '2') {
		unlink ($temp_file);
	}

	my $utimestamp = time ();
	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime ($utimestamp));
	eval {
		$data = encode_entities ($data, "'<>&");
	};
	if ($@) {
		logger($pa_config, "Failed to encode received inventory data", 10);
		return;
	}

	# Get previous data from the database
	my $inventory_module = get_db_single_row ($dbh,
		'SELECT * FROM tagent_module_inventory
		WHERE id_agent_module_inventory = ?',
		$module_id);
	return unless defined ($inventory_module);
		
	process_inventory_module_diff($pa_config, $data, 
		$inventory_module, $timestamp, $utimestamp, $dbh);
}

1;
__END__
