package PandoraFMS::DiscoveryServer;
################################################################################
# Pandora FMS Discovery Server.
# Pandora FMS. the Flexible Monitoring System. http://www.pandorafms.org
################################################################################
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
################################################################################

use strict;
use warnings;

use threads;
use threads::shared;
use Thread::Semaphore;

use IO::Socket::INET;
use POSIX qw(strftime ceil);
use JSON;
use Encode qw(encode_utf8);
use MIME::Base64;
use File::Basename qw(dirname);
use File::Copy;

# Default lib dir for RPM and DEB packages
BEGIN { push @INC, '/usr/lib/perl5'; }

use PandoraFMS::Tools;
use PandoraFMS::DB;
use PandoraFMS::Core;
use PandoraFMS::ProducerConsumerServer;
use PandoraFMS::GIS;
use PandoraFMS::Recon::Base;

# Inherits from PandoraFMS::ProducerConsumerServer
our @ISA = qw(PandoraFMS::ProducerConsumerServer);

# Global variables
my @TaskQueue :shared;
my %PendingTasks :shared;
my $Sem :shared;
my $TaskSem :shared;

# Some required constants, OS_X from tconfig_os.
use constant {
  OS_OTHER => 10,
  OS_ROUTER => 17,
  OS_SWITCH => 18,
  STEP_SCANNING => 1,
  STEP_AFT => 2,
  STEP_TRACEROUTE => 3,
  STEP_GATEWAY => 4,
  STEP_MONITORING => 5,
  STEP_PROCESSING => 6,
  STEP_STATISTICS => 1,
  STEP_APP_SCAN => 2,
  STEP_CUSTOM_QUERIES => 3,
  DISCOVERY_REVIEW => 0,
  DISCOVERY_STANDARD => 1,
  DISCOVERY_RESULTS => 2,
  DISCOVERY_APP => 15,
};

################################################################################
# Discovery Server class constructor.
################################################################################
sub new ($$$$$$) {
  my ($class, $config, $dbh) = @_;
  
  return undef unless (defined($config->{'reconserver'}) && $config->{'reconserver'} == 1)
   || (defined($config->{'discoveryserver'}) && $config->{'discoveryserver'} == 1);
  
  if (! -e $config->{'nmap'}) {
    logger ($config, ' [E] ' . $config->{'nmap'} . " needed by " . $config->{'rb_product_name'} . " Discovery Server not found.", 1);
    print_message ($config, ' [E] ' . $config->{'nmap'} . " needed by " . $config->{'rb_product_name'} . " Discovery Server not found.", 1);
    return undef;
  }

  # Initialize semaphores and queues
  @TaskQueue = ();
  %PendingTasks = ();
  $Sem = Thread::Semaphore->new;
  $TaskSem = Thread::Semaphore->new (0);
  
  # Restart automatic recon tasks.
  db_do ($dbh, 'UPDATE trecon_task  SET utimestamp = 0 WHERE id_recon_server = ? AND status <> -1 AND interval_sweep > 0',
       get_server_id ($dbh, $config->{'servername'}, DISCOVERYSERVER));

  # Reset (but do not restart) manual recon tasks.
  db_do ($dbh, 'UPDATE trecon_task  SET status = -1, summary = "cancelled" WHERE id_recon_server = ? AND status <> -1 AND interval_sweep = 0',
       get_server_id ($dbh, $config->{'servername'}, DISCOVERYSERVER));

  # Call the constructor of the parent class
  my $self = $class->SUPER::new($config, DISCOVERYSERVER, \&PandoraFMS::DiscoveryServer::data_producer, \&PandoraFMS::DiscoveryServer::data_consumer, $dbh);
  
  bless $self, $class;
  return $self;
}

################################################################################
# Run.
################################################################################
sub run ($) {
  my $self = shift;
  my $pa_config = $self->getConfig ();
  my $dbh = $self->getDBH();
  
  print_message ($pa_config, " [*] Starting " . $pa_config->{'rb_product_name'} . " Discovery Server.", 1);
  my $threads = $pa_config->{'recon_threads'};

  # Use hightest value
  if ($pa_config->{'discovery_threads'}  > $pa_config->{'recon_threads'}) {
    $threads = $pa_config->{'discovery_threads'};
  }
  $self->setNumThreads($threads);
  $self->SUPER::run (\@TaskQueue, \%PendingTasks, $Sem, $TaskSem);
}

################################################################################
# Data producer.
################################################################################
sub data_producer ($) {
  my $self = shift;
  my ($pa_config, $dbh) = ($self->getConfig (), $self->getDBH ());
  
  my @tasks;
  
  my $server_id = get_server_id ($dbh, $pa_config->{'servername'}, $self->getServerType ());
  return @tasks unless defined ($server_id);
  
  # Manual tasks have interval_sweep = 0
  # Manual tasks are "forced" like the other, setting the utimestamp to 1
  # By default, after create a tasks it takes the utimestamp to 0
  # Status -1 means "done".
  my @rows;
  if (pandora_is_master($pa_config) == 0) {
    @rows = get_db_rows ($dbh, 'SELECT * FROM trecon_task 
      WHERE id_recon_server = ?
      AND disabled = 0
      AND ((utimestamp = 0 AND interval_sweep != 0 OR status = 1)
        OR (status < 0 AND interval_sweep > 0 AND (utimestamp + interval_sweep) < UNIX_TIMESTAMP()))', $server_id);
  } else {
    @rows = get_db_rows ($dbh, 'SELECT * FROM trecon_task 
      WHERE (id_recon_server = ? OR id_recon_server NOT IN (SELECT id_server FROM tserver WHERE status = 1 AND server_type = ?))
      AND disabled = 0
      AND ((utimestamp = 0 AND interval_sweep != 0 OR status = 1)
        OR (status < 0 AND interval_sweep > 0 AND (utimestamp + interval_sweep) < UNIX_TIMESTAMP()))', $server_id, DISCOVERYSERVER);
  }

  foreach my $row (@rows) {
    
	# Discovery apps must be fully set up.
    if ($row->{'type'} == DISCOVERY_APP && $row->{'setup_complete'} != 1) {
      logger($pa_config, 'Setup for recon app task ' . $row->{'id_app'} . ' not complete.', 10);
	  next;
    }

    # Update task status
    update_recon_task ($dbh, $row->{'id_rt'}, 1);
    
    push (@tasks, $row->{'id_rt'});
  }
  
  return @tasks;
}

################################################################################
# Data consumer.
################################################################################
sub data_consumer ($$) {
  my ($self, $task_id) = @_;
  my ($pa_config, $dbh) = ($self->getConfig (), $self->getDBH ());

  # Get server id.
  my $server_id = get_server_id($dbh, $pa_config->{'servername'}, $self->getServerType());

  # Get recon task data	
  my $task = get_db_single_row ($dbh, 'SELECT * FROM trecon_task WHERE id_rt = ?', $task_id);	
  return -1 unless defined ($task);

  # Is it a recon script?
  if (defined ($task->{'id_recon_script'}) && ($task->{'id_recon_script'} != 0)) {
    exec_recon_script ($pa_config, $dbh, $task);
    return;
  }
  # Is it a discovery app?
  elsif ($task->{'type'} == DISCOVERY_APP) {
    exec_recon_app ($pa_config, $dbh, $task);
	return;
  }
  else {
    logger($pa_config, 'Starting recon task for net ' . $task->{'subnet'} . '.', 10);
  }

  eval {
    local $SIG{__DIE__};

    my @subnets = split(/,/, safe_output($task->{'subnet'}));
    my @communities = split(/,/, safe_output($task->{'snmp_community'}));
    my @auth_strings = ();
    if(defined($task->{'auth_strings'})) {
      @auth_strings = split(/,/, safe_output($task->{'auth_strings'}));
    }

    my $main_event = pandora_event($pa_config,
      "[Discovery] Execution summary",
      $task->{'id_group'}, 0, 0, 0, 0, 'system', 0, $dbh
    );

    my %cnf_extra;
    
    my $r = enterprise_hook(
      'discovery_generate_extra_cnf',
      [
        $pa_config,
        $dbh, $task,
        \%cnf_extra
      ]
    );
    if (defined($r) && $r eq 'ERR') {
      # Could not generate extra cnf, skip this task.
      return;
    }

    if ($task->{'type'} == DISCOVERY_APP_SAP) {
      # SAP TASK, retrieve license.
      if (defined($task->{'field4'}) && $task->{'field4'} ne "") {
        $task->{'sap_license'} = $task->{'field4'};
      } else {
        $task->{'sap_license'} = pandora_get_config_value(
          $dbh,
          'sap_license'
        );
      }

      # Retrieve credentials for task (optional).
      if (defined($task->{'auth_strings'})
        && $task->{'auth_strings'} ne ''
      ) {
        my $key = credential_store_get_key(
          $pa_config,
          $dbh,
          $task->{'auth_strings'}
        );

        # Inside an eval, here it shouln't fail unless bad configured.
        $task->{'username'} = $key->{'username'};
        $task->{'password'} = $key->{'password'};

      }
    }

    if (!is_empty($task->{'recon_ports'})) {
      # Accept only valid symbols.
      if ($task->{'recon_ports'} !~ /[\d\-\,\ ]+/) {
        $task->{'recon_ports'} = '';
      }
    }

    my $recon = new PandoraFMS::Recon::Base(
      parent => $self,
      communities => \@communities,
      dbh => $dbh,
      group_id => $task->{'id_group'},
      id_os => $task->{'id_os'},
      id_network_profile => $task->{'id_network_profile'},
      os_detection => $task->{'os_detect'},
      parent_detection => $task->{'parent_detection'},
      parent_recursion => $task->{'parent_recursion'},
      pa_config => $pa_config,
      recon_ports => $task->{'recon_ports'},
      resolve_names => $task->{'resolve_names'},
      snmp_auth_user => $task->{'snmp_auth_user'},
      snmp_auth_pass => $task->{'snmp_auth_pass'},
      snmp_auth_method => $task->{'snmp_auth_method'},
      snmp_checks => $task->{'snmp_checks'},
      snmp_enabled => $task->{'snmp_enabled'},
      snmp_privacy_method => $task->{'snmp_privacy_method'},
      snmp_privacy_pass => $task->{'snmp_privacy_pass'},
      snmp_security_level => $task->{'snmp_security_level'},
      snmp_timeout => $task->{'snmp_timeout'},
      snmp_version => $task->{'snmp_version'},
      snmp_skip_non_enabled_ifs => $task->{'snmp_skip_non_enabled_ifs'},
      subnets => \@subnets,
      task_id => $task->{'id_rt'},
      vlan_cache_enabled => $task->{'vlan_enabled'},
      wmi_enabled => $task->{'wmi_enabled'},
      rcmd_enabled => $task->{'rcmd_enabled'},
      rcmd_timeout => $pa_config->{'rcmd_timeout'},
      rcmd_timeout_bin => $pa_config->{'rcmd_timeout_bin'},
      auth_strings_array => \@auth_strings,
      autoconfiguration_enabled => $task->{'autoconfiguration_enabled'},
      main_event_id => $main_event,
      server_id => $server_id,
      %{$pa_config},
      task_data => $task,
      public_url => PandoraFMS::Config::pandora_get_tconfig_token($dbh, 'public_url', ''),
      %cnf_extra
    );

    $recon->scan();

    # Clean tmp file.
    if (defined($cnf_extra{'creds_file'})
    && -f $cnf_extra{'creds_file'}) {
    unlink($cnf_extra{'creds_file'});
  }


    # Clean one shot tasks
    if ($task->{'type'} eq DISCOVERY_DEPLOY_AGENTS) {
      db_delete_limit($dbh, ' trecon_task ', ' id_rt = ? ', 1, $task->{'id_rt'});   
    }
  };
  if ($@) {
    logger(
      $pa_config,
      'Cannot execute Discovery task: ' . safe_output($task->{'name'}) . $@,
      10
    );
    update_recon_task ($dbh, $task_id, -1);
    return;
  }
}

################################################################################
# Update recon task status.
################################################################################
sub update_recon_task ($$$) {
  my ($dbh, $id_task, $status) = @_;
  
  db_do ($dbh, 'UPDATE trecon_task SET utimestamp = ?, status = ? WHERE id_rt = ?', time (), $status, $id_task);
} 

################################################################################
# Executes recon scripts
################################################################################
sub exec_recon_script ($$$) {
  my ($pa_config, $dbh, $task) = @_;
  
  # Get recon plugin data	
  my $script = get_db_single_row ($dbh, 'SELECT * FROM trecon_script WHERE id_recon_script = ?', $task->{'id_recon_script'});
  return -1 unless defined ($script);
  
  logger($pa_config, 'Executing recon script ' . safe_output($script->{'name'}), 10);
  
  my $command = safe_output($script->{'script'});
  
  my $macros = safe_output($task->{'macros'});

  # \r and \n should be escaped for p_decode_json().
  $macros =~ s/\n/\\n/g;
  $macros =~ s/\r/\\r/g;
  my $decoded_macros;
  
  if ($macros) {
    eval {
      $decoded_macros = p_decode_json($pa_config, $macros);
    };
  }
  
  my $macros_parameters = '';
  
  # Add module macros as parameter
  if(ref($decoded_macros) eq "HASH") {
    # Convert the hash to a sorted array
    my @sorted_macros;
    while (my ($i, $m) = each (%{$decoded_macros})) {
      $sorted_macros[$i] = $m;
    }

    # Remove the 0 position		
    shift @sorted_macros;

    foreach my $m (@sorted_macros) {
      $macros_parameters = $macros_parameters . ' "' . $m->{"value"} . '"';
    }
  }

  my $ent_script = 0;
  my $args = enterprise_hook(
    'discovery_custom_recon_scripts',
    [$pa_config, $dbh, $task, $script]
  );
  if (!$args) {
    $args = '"'.$task->{'id_rt'}.'" ';
    $args .= '"'.$task->{'id_group'}.'" ';
    $args .= $macros_parameters;
  } else {
    $ent_script = 1;
  }
  
  if (-x $command) {
    my $exec_output = `$command $args 2>&1`;
    log_execution($pa_config, $task->{'id_rt'}, "$command $args", $exec_output);
    logger($pa_config, "Execution output: \n". $exec_output, 10);
  } else {
    logger($pa_config, "Cannot execute recon task command $command.", 10);
  }
  
  # Only update the timestamp in case something went wrong. The script should set the status.
  db_do ($dbh, 'UPDATE trecon_task SET utimestamp = ? WHERE id_rt = ?', time (), $task->{'id_rt'});

  if ($ent_script == 1) {
    enterprise_hook('discovery_clean_custom_recon',[$pa_config, $dbh, $task, $script]);
  }
  
  logger($pa_config, 'Done executing recon script ' . safe_output($script->{'name'}), 10);
  return 0;
}

################################################################################
# Executes recon apps.
################################################################################
sub exec_recon_app ($$$) {
	my ($pa_config, $dbh, $task) = @_;
	
	# Get execution, macro and script data.
	my @executions = get_db_rows($dbh, 'SELECT * FROM tdiscovery_apps_executions WHERE id_app = ?', $task->{'id_app'});
	my @scripts = get_db_rows($dbh, 'SELECT * FROM tdiscovery_apps_scripts WHERE id_app = ?', $task->{'id_app'});
	
	logger($pa_config, 'Executing recon app ID ' . $task->{'id_app'}, 10);
	
	# Configure macros.
	my %macros = (
		"__taskMD5__"           => md5($task->{'id_rt'}),
		"__taskInterval__"      => $task->{'interval_sweep'},
		"__taskGroup__"         => get_group_name($dbh, $task->{'id_group'}),
		"__taskGroupID__"       => $task->{'id_group'},
		"__temp__"              => $pa_config->{'temporal'},
		"__incomingDir__"       => $pa_config->{'incomingdir'},
		"__consoleAPIURL__"     => $pa_config->{'console_api_url'},
		"__consoleAPIPass__"    => $pa_config->{'console_api_pass'},
		"__consoleUser__"       => $pa_config->{'console_user'},
		"__consolePass__"       => $pa_config->{'console_pass'},
		"__pandoraServerConf__" => $pa_config->{'pandora_path'},
		get_recon_app_macros($pa_config, $dbh, $task),
		get_recon_script_macros($pa_config, $dbh, $task)
	);

	# Dump macros to disk.
	dump_recon_app_macros($pa_config, $dbh, $task, \%macros);

	# Run executions.
	my $status = -1;
	my @summary;
	for (my $i = 0; $i < scalar(@executions); $i++) {
		my $execution = $executions[$i];

		# NOTE: Add the redirection before calling subst_alert_macros to prevent it from escaping quotes.
		my $cmd = $pa_config->{'plugin_exec'} . ' ' . $task->{'executions_timeout'} . ' ' .
				  subst_alert_macros(safe_output($execution->{'execution'}) . ' 2>&1', \%macros);
		logger($pa_config, 'Executing command for recon app ID ' . $task->{'id_app'} . ': ' . $cmd, 10);
		my $output_json = `$cmd`;

		# Something went wrong.
		my $rc = $? >> 8;
		if ($rc != 0) {
			$status = -2;
		}

		# Timeout specific mesage.
		if ($rc == 124) {
			push(@summary, "The execution timed out.");
			next;
		}

		# No output message.
		if (!defined($output_json)) {
			push(@summary, "The execution returned no output.");
			next;
		}

		# Parse the output.
		my $output = eval {
			local $SIG{'__DIE__'};
			decode_json($output_json);
		};

		# Non-JSON output.
		if (!defined($output)) {
			push(@summary, $output_json);
			next;
		}

		# Process monitoring data.
		if (ref($output) eq 'HASH' && defined($output->{'monitoring_data'})) {
	    	my $recon = new PandoraFMS::Recon::Base(
				dbh => $dbh,
				group_id => $task->{'id_group'},
				id_os => $task->{'id_os'},
				pa_config => $pa_config,
				snmp_enabled => 0,
				task_id => $task->{'id_rt'},
				task_data => $task,
			);
			$recon->create_agents($output->{'monitoring_data'});
			delete($output->{'monitoring_data'});
		}

		# Store output data.
		push(@summary, $output);

		# Update the progress.
		update_recon_task($dbh, $task->{'id_rt'}, int((100 * ($i + 1)) / scalar(@executions)));
	}

	# Parse the output.
	my $summary_json = eval {
		local $SIG{'__DIE__'};
		encode_json(\@summary);
	};
	if (!defined($summary_json)) {
		logger($pa_config, 'Invalid summary for recon app ID ' . $task->{'id_app'}, 10);
	} else {
		db_do($dbh, "UPDATE trecon_task SET summary=? WHERE id_rt=?", $summary_json, $task->{'id_rt'});
	  pandora_audit ($pa_config, 'Discovery task' . ' Executed task '.$task->{'name'}.'#'.$task->{'id_app'}, 'SYSTEM', 'Discovery task', $dbh);
	}

	update_recon_task($dbh, $task->{'id_rt'}, $status);
	
	return;
}

################################################################################
# Processe app macros and return them ready to be used by subst_alert_macros.
################################################################################
sub get_recon_app_macros ($$$) {
	my ($pa_config, $dbh, $task) = @_;
	my %macros;
	
	# Get a list of macros for the given task.
	my @macro_array = get_db_rows($dbh, 'SELECT * FROM tdiscovery_apps_tasks_macros WHERE id_task = ?', $task->{'id_rt'});
	foreach my $macro_item (@macro_array) {
		my $macro_id = safe_output($macro_item->{'id_task'});
		my $macro_name = safe_output($macro_item->{'macro'});
		my $macro_type = $macro_item->{'type'};
		my $macro_value = safe_output($macro_item->{'value'});
		my $computed_value = '';
	
		# The value can be a JSON array of values.
		my $value_array = eval {
			local $SIG{'__DIE__'};
			decode_json($macro_value);
		};
		if (defined($value_array) && ref($value_array) eq 'ARRAY') {
			# Multi value macro.
			my @tmp;
			foreach my $value_item (@{$value_array}) {
				push(@tmp, get_recon_macro_value($pa_config, $dbh, $macro_type, $value_item));
			}
			$computed_value = p_encode_json($pa_config, \@tmp);
			if (!defined($computed_value)) {
				logger($pa_config, "Error encoding macro $macro_name for task ID " . $task->{'id_rt'}, 10);
				next;
			}
		} else {
			# Single value macro.
			$computed_value = get_recon_macro_value($pa_config, $dbh, $macro_type, $macro_value);
		}

		# Store the computed value.
		$macros{$macro_name} = $computed_value;
	}

	return %macros;
}

################################################################################
# Dump macros that must be saved to disk.
# The macros dictionary is modified in-place.
################################################################################
sub dump_recon_app_macros ($$$$) {
	my ($pa_config, $dbh, $task, $macros) = @_;

	# Get a list of macros that must be dumped to disk.
	my @macro_array = get_db_rows($dbh, 'SELECT * FROM tdiscovery_apps_tasks_macros WHERE id_task = ? AND temp_conf = 1', $task->{'id_rt'});
	foreach my $macro_item (@macro_array) {
		
		# Make sure the macro has already been parsed.
		my $macro_name = safe_output($macro_item->{'macro'});
		next unless defined($macros->{$macro_name});
		my $macro_value = $macros->{$macro_name};
		my $macro_id = safe_output($macro_item->{'id_task'});

		# Save the computed value to a temporary file.
		my $temp_dir = $pa_config->{'incomingdir'} . '/discovery/tmp';
		mkdir($temp_dir) if (! -d $temp_dir);
		my $fname = $temp_dir . '/' . md5($task->{'id_rt'} . '_' . $macro_name) . '.macro';
		eval {
			open(my $fh, '>', $fname) or die($!);
			print $fh subst_alert_macros($macro_value, $macros);
			close($fh);
		};
		if ($@) {
			logger($pa_config, "Error writing macro $macro_name for task ID " . $task->{'id_rt'} . " to disk: $@", 10);
			next;
		}
	
		# Set the macro value to the temporary file name.
		$macros->{$macro_name} = $fname;
	}
}

################################################################################
# Processe recon script macros and return them ready to be used by
# subst_alert_macros.
################################################################################
sub get_recon_script_macros ($$$) {
	my ($pa_config, $dbh, $task) = @_;
	my %macros;
	
	# Get a list of script macros.
	my @macro_array = get_db_rows($dbh, 'SELECT * FROM tdiscovery_apps_scripts WHERE id_app = ?', $task->{'id_app'});
	foreach my $macro_item (@macro_array) {
		my $macro_name = safe_output($macro_item->{'macro'});
		my $macro_value = safe_output($macro_item->{'value'});

		# Compose the full path to the script: <incoming dir>/discovery/<app short name>/<script>
		my $app = get_db_single_row($dbh, 'SELECT short_name FROM tdiscovery_apps WHERE id_app = ?', $task->{'id_app'});
		if (!defined($app)) {
			logger($pa_config, "Discovery app with ID " . $task->{'id_app'} . " not found.", 10);
			next;
		}

		my $app_short_name = safe_output($app->{'short_name'});
		$macros{$macro_name} = $pa_config->{'incomingdir'} . '/discovery/' . $app_short_name . '/' . $macro_value;
	}

	return %macros;
}

################################################################################
# Return the replacement value for the given macro.
################################################################################
sub get_recon_macro_value($$$$) {
	my ($pa_config, $dbh, $type, $value) = @_;
	my $ret = '';

	# These macros return the macro value itself.
	if ($type eq 'custom' ||
	    $type eq 'interval' ||
		$type eq 'module_types' ||
		$type eq 'status') {
		$ret = $value;
	}
	# Name of the group if it exists. Empty otherwise.
	elsif ($type eq 'agent_groups') {
		my $group_name = '';
		if ($value > 0) {
			$group_name = get_group_name($dbh, $value);
		}

		if (defined($group_name)) {
			$ret = $group_name;
		}
	}
	# Name of the agent if it exists. Empty otherwise.
	elsif ($type eq 'agents') {
		my $agent_id = get_agent_id($dbh, $value);
		if ($agent_id > 0) {
			$ret = $value;
		}
	}
	# Name of the module group if it exists. Empty otherwise.
	elsif ($type eq 'module_groups') {
		my $module_group_name = get_module_group_name($dbh, $value);
		if (defined($module_group_name)) {
			$ret = $module_group_name;
		}
	}
	# Name of the module if it exists. Empty otherwise.
	elsif ($type eq 'modules') {
		my $module_id = get_db_value ($dbh, "SELECT id_agente_modulo FROM tagente_modulo WHERE nombre = ?", safe_input($value));
		if ($module_id > 0) {
			$ret = $value;
		}
	}
	# Name of the tag if it exists. Empty otherwise.
	elsif ($type eq 'tags') {
		my $tag_name = get_tag_name($dbh, $value);
		if (defined($tag_name)) {
			$ret = $tag_name;
		}
	}
	# Name of the alert template if it exists. Empty otherwise.
	elsif ($type eq 'alert_templates') {
		my $template_name = get_template_name($dbh, $value);
		if (defined($template_name)) {
			$ret = $template_name;
		}
	}
	# Name of the alert action if it exists. Empty otherwise.
	elsif ($type eq 'alert_actions') {
		my $action_name = get_action_name($dbh, $value);
		if (defined($action_name)) {
			$ret = $action_name;
		}
	}
	# Name of the OS if it exists. Empty otherwise.
	elsif ($type eq 'os') {
		my $os_name = get_os_name($dbh, $value);
		if (defined($os_name)) {
			$ret = $os_name;
		}
	}
	# Credentials from the Pandora DB.
	elsif ($type =~ m/^credentials\./) {
		$ret = get_recon_credential_macro($pa_config, $dbh, $value);
	}

	return $ret;
}

################################################################################
# Return the value for a credential macro.
################################################################################
sub get_recon_credential_macro($$$) {
	my ($pa_config, $dbh, $credential_id) = @_;
	my $cred_dict = {};
	my $cred_json = undef;

	# Retrieve the credentials.
	my $cred = get_db_single_row($dbh, 'SELECT * FROM tcredential_store WHERE identifier = ?', $credential_id);
	return '' unless defined($cred);

	# Generate the appropriate output for each product.
	my $product = uc($cred->{'product'});
	if ($product eq 'CUSTOM') {
		$cred_dict = {
			'user'     => safe_output($cred->{'username'}),
			'password' => safe_output($cred->{'password'})
		};
	}
	elsif ($product eq 'AWS') {
		$cred_dict = {
			'access_key_id'     => safe_output($cred->{'username'}),
			'secret_access_key' => safe_output($cred->{'password'})
		};
	}
	elsif ($product eq 'AZURE') {
		$cred_dict = {
			'client_id'          => safe_output($cred->{'username'}),
			'application_secret' => safe_output($cred->{'password'}),
			'tenant_domain'      => safe_output($cred->{'extra_1'}),
			'subscription_id'    => safe_output($cred->{'extra_2'})
		};
	}
	elsif ($product eq 'GOOGLE') {
		$cred_json = safe_output($cred->{'extra_1'});
	}
	elsif ($product eq 'SAP') {
		$cred_dict = {
			'user'     => safe_output($cred->{'username'}),
			'password' => safe_output($cred->{'password'})
		};
	}
	elsif ($product eq 'SNMP') {
		$cred_json = safe_output($cred->{'extra_1'});
	}
	elsif ($product eq 'WMI') {
		$cred_dict = {
			'user'      =>  safe_output($cred->{'username'}),
			'password'  =>  safe_output($cred->{'password'}),
			'namespace' => safe_output($cred->{'extra_1'})
		};
	}

	# Encode to JSON if needed.
	if (!defined($cred_json)) {
		$cred_json = p_encode_json($pa_config, $cred_dict);
		if (!defined($cred_json)) {
			logger($pa_config, "Error encoding credential $credential_id to JSON.", 10);
			return '';
		}
	}

	# Return the base 64 encoding of the credential JSON.
	return encode_base64($cred_json, '');
}



################################################################################
# Guess the OS using xprobe2 or nmap.
################################################################################
sub PandoraFMS::Recon::Base::guess_os($$;$$$) {
  my ($self, $device, $string_flag, $return_version_only) = @_;

  return $self->{'os_id'}{$device} if defined($self->{'os_id'}{$device});

  $DEVNULL = '/dev/null' if (!defined($DEVNULL));
  $DEVNULL = '/NUL' if ($^O =~ /win/i && !defined($DEVNULL));

  # OS detection disabled. Use the device type.
  if ($self->{'os_detection'} == 0) {
    my $device_type = $self->get_device_type($device);
    return OS_OTHER unless defined($device_type);

    return OS_ROUTER if ($device_type eq 'router');
    return OS_SWITCH if ($device_type eq 'switch');
    return OS_OTHER;
  }
  
  # Use nmap by default
  if (-x $self->{'pa_config'}->{'nmap'}) {
    my $return = `"$self->{pa_config}->{nmap}" -sSU -T5 -F -O --osscan-limit $device 2>$DEVNULL`;
    return OS_OTHER if ($? != 0);
    my ($str_os, $os_version);
    if ($return =~ /Aggressive OS guesses:(.*?)(?>\(\d+%\),)|^OS details:(.*?)$/mi) {
      if(defined($1) && $1 ne "") {
        $str_os = $1;
      } else {
        $str_os = $2;
      }

      my $pandora_os = pandora_get_os($self->{'dbh'}, $str_os);
      my $pandora_os_name = pandora_get_os_by_id($self->{'dbh'}, $pandora_os);

      if ($return_version_only == 1) {
        if ($str_os =~ /$pandora_os_name/i) {
          $os_version = $';  # Get string after matched found OS name.
          $os_version =~ s/^\s+//; # Remove leading spaces.
          $os_version =~ s/\s+$//; # Remove trailing spaces.
        } else {
          $os_version = '';
        }

        return $os_version;

      }

      return $str_os if is_enabled($string_flag);
      return $pandora_os;
    }
  }

  return OS_OTHER;
}

################################################################################
# Returns the number of open ports from the given list.
################################################################################
sub PandoraFMS::Recon::Base::tcp_scan ($$) {
  my ($self, $host) = @_;

  return if is_empty($host);
  return if is_empty($self->{'recon_ports'});

  my $r = `"$self->{pa_config}->{nmap}" -p$self->{recon_ports} $host`;

  # Same as ""| grep open | wc -l" but multi-OS;
  my $open_ports = () = $r =~ /open/gm;

  return $open_ports;
}

################################################################################
# Verifies if a module will be normal.
################################################################################
sub PandoraFMS::Recon::Base::test_module($$) {
  my ($self, $addr, $module) = @_;

  # Default values.
  my $test = {
    %{$module},
    'ip_target' => $addr,
  };

  if (is_enabled($module->{'__module_component'})) {
    # Component. Translate some fields.
    $test->{'id_tipo_modulo'} = $module->{'type'};
  } else {
    # Module.
    $module->{'type'} = $module->{'module_type'} if is_empty($module->{'type'});

    if (defined($module->{'type'})) {
      if(!defined($self->{'module_types'}{$module->{'type'}})) {
        $self->{'module_types'}{$module->{'type'}} = get_module_id(
          $self->{'dbh'},$module->{'type'}
        );
      }

      $test->{'id_tipo_modulo'} = $self->{'module_types'}{$module->{'type'}};
    }
  }

  my $value;

  # 1. Try to retrieve value.
  if ($test->{'id_tipo_modulo'} >= 15 && $test->{'id_tipo_modulo'} <= 18) {
    # SNMP
    $value = $self->call(
      'snmp_get_value',
      $test->{'ip_target'},
      $test->{'snmp_oid'}
    );
  } elsif ($test->{'id_tipo_modulo'} == 6) {
    # ICMP - alive - already tested.
    $value = 1;

  } elsif ($test->{'id_tipo_modulo'} == 7) {
    # ICMP - latency
    $value = pandora_ping_latency(
      $self->{'pa_config'},
      $test->{'ip_target'},
      $test->{'max_timeout'},
      $test->{'max_retries'},
    );

  } elsif (($test->{'id_tipo_modulo'} >= 1 && $test->{'id_tipo_modulo'} <= 5)
    || ($test->{'id_tipo_modulo'} >= 21 && $test->{'id_tipo_modulo'} <= 23)
  ) {
    # Generic, plugins. (21-23 ASYNC)
    if ($test->{'id_modulo'} == 6) {

      return 0 unless $self->wmi_responds($addr);

      # WMI commands.
      $value = $self->call(
        'wmi_get_value',
        $test->{'ip_target'},
        # WMI query.
        $test->{'snmp_oid'},
        # Column
        $test->{'tcp_port'}
      );
    } elsif ($test->{'id_modulo'} == 4) {
      # SNMP Bandwith plugin modules.
      # Check if plugin is running.
      if ($module->{'macros'} ne '') {

      # Get Bandwidth plugin.
      my $plugin = get_db_single_row(
        $self->{'dbh'},
        'SELECT * FROM tplugin WHERE name = "Network&#x20;bandwidth&#x20;SNMP"',
      );

      return 0 unless defined($plugin);
        my $parameters =  safe_output($plugin->{'parameters'});
        my $plugin_exec = $plugin->{'plugin_exec'};

        # Decode macros.
        my $macros = p_decode_json($self->{'config'}, safe_output($test->{'macros'}));

        my %macros = %{$macros};
        if(ref($macros) eq "HASH") {
          foreach my $macro_id (keys(%macros))
          {
            my $macro_field = safe_output($macros{$macro_id}{'macro'});
            my $macro_desc  = safe_output($macros{$macro_id}{'desc'});
            my $macro_value = (defined($macros{$macro_id}{'hide'}) && $macros{$macro_id}{'hide'} eq '1') ?
                              pandora_output_password($self->{'config'}, safe_output($macros{$macro_id}{'value'})) :
                              safe_output($macros{$macro_id}{'value'});

            # build parameters to invoke plugin
            $parameters =~ s/\'$macros{$macro_id}{'macro'}\'/$macro_value/g;

          }
        }
        my $command = safe_output($plugin_exec);

        # Execute the plugin.
        my $output = `$command 2>$DEVNULL`;
        # Do not save the output if there was an error.
        if ($? != 0) {
          return 0;
        } else {
          $value = 1;
        }
     }
    } elsif(is_enabled($test->{'id_plugin'})) {
      # XXX TODO: Test plugins. How to identify arguments? and values?
      # Disabled until we can ensure result.
      
      return 0;
    }

  } elsif ($test->{'id_tipo_modulo'} >= 34 && $test->{'id_tipo_modulo'} <= 37) {
    # Remote command.
    return 0 unless $self->rcmd_responds($addr);

    my $target_os;
    if ($test->{'custom_string_2'} =~ /inherited/i) {
      $target_os = pandora_get_os(
        $self->{'dbh'},
        $self->{'os_cache'}{$test->{'ip_target'}}
      );
    } else {
      $target_os = pandora_get_os($self->{'dbh'}, $test->{'custom_string_2'});
    }

    $value = enterprise_hook(
      'remote_execution_module',
      [
        # pa_config,
        $self->{'pa_config'},
        # dbh,
        $self->{'dbh'},
        # module,
        $test,
        # target_os,
        $target_os,
        # ip_target,
        $test->{'ip_target'},
        # tcp_port
        $test->{'tcp_port'}
      ]
    );

    chomp($value);

    return 0 unless defined($value);

  } elsif ($test->{'id_tipo_modulo'} >= 8 && $test->{'id_tipo_modulo'} <= 11) {
    # TCP
    return 0 unless is_numeric($test->{'tcp_port'})
      && $test->{'tcp_port'} > 0
      && $test->{'tcp_port'} <= 65535;

    my $result;

    PandoraFMS::NetworkServer::pandora_query_tcp(
      $self->{'pa_config'},
      $test->{'tcp_port'},
      $test->{'ip_target'},
      \$result,
      \$value,
      $test->{'tcp_send'},
      $test->{'tcp_rcv'},
      $test->{'id_tipo_modulo'},
      $test->{'max_timeout'},
      $test->{'max_retries'},
      '<Discovery testing>',
    );

    # Result 0 is OK, 1 failed
    return 0 unless defined($result) && $result == 0;
    return 0 unless defined($value);

  }

  # Invalid data (empty or not defined)
  return 0 if is_empty($value);

  # 2. Check if value matches type definition and fits thresholds.
  if (is_in_array(
        [1,2,4,5,6,7,8,9,11,15,16,18,21,22,25,30,31,32,34,35,37],
        $test->{'id_tipo_modulo'}
      )
  ) {
    # Numeric. Remove " symbols if any.
    $value =~ s/\"//g;
    return 0 unless is_numeric($value);

    if (is_in_array([2,6,9,18,21,31,35], $test->{'id_tipo_modulo'})) {
      # Boolean.
      if (!is_enabled($test->{'critical_inverse'})) {
        return 0 if $value == 0;
      } else {
        return 0 if $value != 0;
      }
    }

    my $thresholds_defined = 0;

    if ((!defined($test->{'min_critical'}) || $test->{'min_critical'} == 0)
      && (!defined($test->{'max_critical'}) || $test->{'max_critical'} == 0)
    ) {
      # In Default 0,0 do not test.or not defined
      $thresholds_defined = 0;
    } else {
      # min or max are diferent from 0
      $thresholds_defined = 1;
    }

    if ($thresholds_defined > 0) {
      # Check thresholds.
      if (!is_enabled($test->{'critical_inverse'})) {
        return 0 if $value >= $test->{'min_critical'} && $value <= $test->{'max_critical'};
      } else {
        return 0 if $value < $test->{'min_critical'} && $value > $test->{'max_critical'};
      }
    }

  } else {
    # String.
    if (!is_enabled($test->{'critical_inverse'})) {
      return 0 if !is_empty($test->{'str_critical'}) && $value =~ /$test->{'str_critical'}/;
    } else {
      return 0 if !is_empty($test->{'str_critical'}) && $value !~ /$test->{'str_critical'}/;
    }

  }

  # Success.
  return 1;

}

################################################################################
# Create interface modules for the given agent (if needed).
################################################################################
sub PandoraFMS::Recon::Base::create_interface_modules($$) {
  my ($self, $device) = @_;

  # Add interfaces to the agent if it responds to SNMP.
  return unless ($self->is_snmp_discovered($device));
  my $community = $self->get_community($device);

  my @output = $self->snmp_get_value_array($device, $PandoraFMS::Recon::Base::IFINDEX);
  foreach my $if_index (@output) {
    next unless ($if_index =~ /^[0-9]+$/);

    if ($self->{'task_data'}{'snmp_skip_non_enabled_ifs'} == 1) {
      # Check the status of the interface.
      my $if_status = $self->snmp_get_value($device, "$PandoraFMS::Recon::Base::IFOPERSTATUS.$if_index");

      next unless $if_status == 1;
    }

    # Fill the module description with the IP and MAC addresses.
    my $mac = $self->get_if_mac($device, $if_index);
    my $ip = $self->get_if_ip($device, $if_index);
    my $if_desc = ($mac ne '' ? "MAC $mac " : '') . ($ip ne '' ? "IP $ip" : '');

    # Get the name of the network interface.
    my $if_name = $self->snmp_get_value($device, "$PandoraFMS::Recon::Base::IFNAME.$if_index");
    $if_name = "if$if_index" unless defined ($if_name);
    $if_name =~ s/"//g;
    $if_name = clean_blank($if_name);

    # Interface status module.
    $self->call(
      'add_module',
      $device,
      {
        'id_tipo_modulo' => 18,
        'id_modulo' => 2,
        'name' => $if_name."_ifOperStatus",
        'descripcion' => safe_input(
          'The current operational state of the interface: up(1), down(2), testing(3), unknown(4), dormant(5), notPresent(6), lowerLayerDown(7)',
        ),
        'ip_target' => $device,
        'tcp_send' => $self->{'task_data'}{'snmp_version'},
        'custom_string_1' => $self->{'task_data'}{'snmp_privacy_method'},
        'custom_string_2' => $self->{'task_data'}{'snmp_privacy_pass'},
        'custom_string_3' => $self->{'task_data'}{'snmp_security_level'},
        'plugin_parameter' => $self->{'task_data'}{'snmp_auth_method'},
        'plugin_user' => $self->{'task_data'}{'snmp_auth_user'},
        'plugin_pass' => $self->{'task_data'}{'snmp_auth_pass'},
        'snmp_community' => $community,
        'snmp_oid' => "$PandoraFMS::Recon::Base::IFOPERSTATUS.$if_index",
        'unit'        => ''

      }
    );

    # Incoming traffic module.
    my $if_hc_in_octets = $self->snmp_get_value($device, "$PandoraFMS::Recon::Base::IFHCINOCTECTS.$if_index");
    if (defined($if_hc_in_octets)) {
      # Use HC counters.
      # ifHCInOctets
      $self->call(
        'add_module',
        $device,
        {
          'id_tipo_modulo' => 16,
          'id_modulo' => 2,
          'name' => $if_name."_ifHCInOctets",
          'descripcion' => safe_input(
            'The total number of octets received on the interface, including framing characters. This object is a 64-bit version of ifInOctets.'
          ),
          'ip_target' => $device,
          'tcp_send' => $self->{'task_data'}{'snmp_version'},
          'custom_string_1' => $self->{'task_data'}{'snmp_privacy_method'},
          'custom_string_2' => $self->{'task_data'}{'snmp_privacy_pass'},
          'custom_string_3' => $self->{'task_data'}{'snmp_security_level'},
          'plugin_parameter' => $self->{'task_data'}{'snmp_auth_method'},
          'plugin_user' => $self->{'task_data'}{'snmp_auth_user'},
          'plugin_pass' => $self->{'task_data'}{'snmp_auth_pass'},
          'snmp_community' => $community,
          'snmp_oid' => "$PandoraFMS::Recon::Base::IFHCINOCTECTS.$if_index",
          'unit' => safe_input('bytes/s')

        }
      );
    } else {
      # Use 32b counters.
      # ifInOctets
      $self->call(
        'add_module',
        $device,
        {
          'id_tipo_modulo' => 16,
          'id_modulo' => 2,
          'name' => $if_name."_ifInOctets",
          'descripcion' => safe_input(
            'The total number of octets received on the interface, including framing characters.'
          ),
          'ip_target' => $device,
          'tcp_send' => $self->{'task_data'}{'snmp_version'},
          'custom_string_1' => $self->{'task_data'}{'snmp_privacy_method'},
          'custom_string_2' => $self->{'task_data'}{'snmp_privacy_pass'},
          'custom_string_3' => $self->{'task_data'}{'snmp_security_level'},
          'plugin_parameter' => $self->{'task_data'}{'snmp_auth_method'},
          'plugin_user' => $self->{'task_data'}{'snmp_auth_user'},
          'plugin_pass' => $self->{'task_data'}{'snmp_auth_pass'},
          'snmp_community' => $community,
          'snmp_oid' => "$PandoraFMS::Recon::Base::IFINOCTECTS.$if_index",
          'unit' => safe_input('bytes/s')

        }
      );
    }

    # Outgoing traffic module.
    my $if_hc_out_octets = $self->snmp_get_value($device, "$PandoraFMS::Recon::Base::IFHCOUTOCTECTS.$if_index");
    if (defined($if_hc_out_octets)) {
      # Use HC counters.
      # ifHCOutOctets
      $self->call(
        'add_module',
        $device,
        {
          'id_tipo_modulo' => 16,
          'id_modulo' => 2,
          'name' => $if_name."_ifHCOutOctets",
          'descripcion' => safe_input(
            'The total number of octets transmitted out of the interface, including framing characters. This object is a 64-bit version of ifOutOctets.'
          ),
          'ip_target' => $device,
          'tcp_send' => $self->{'task_data'}{'snmp_version'},
          'custom_string_1' => $self->{'task_data'}{'snmp_privacy_method'},
          'custom_string_2' => $self->{'task_data'}{'snmp_privacy_pass'},
          'custom_string_3' => $self->{'task_data'}{'snmp_security_level'},
          'plugin_parameter' => $self->{'task_data'}{'snmp_auth_method'},
          'plugin_user' => $self->{'task_data'}{'snmp_auth_user'},
          'plugin_pass' => $self->{'task_data'}{'snmp_auth_pass'},
          'snmp_community' => $community,
          'snmp_oid' => "$PandoraFMS::Recon::Base::IFHCOUTOCTECTS.$if_index",
          'unit' => safe_input('bytes/s')

        }
      );
    } else { 
      # Use 32b counters.
      # ifOutOctets
      $self->call(
        'add_module',
        $device,
        {
          'id_tipo_modulo' => 16,
          'id_modulo' => 2,
          'name' => $if_name."_ifOutOctets",
          'descripcion' => safe_input(
            'The total number of octets transmitted out of the interface, including framing characters.'
          ),
          'ip_target' => $device,
          'tcp_send' => $self->{'task_data'}{'snmp_version'},
          'custom_string_1' => $self->{'task_data'}{'snmp_privacy_method'},
          'custom_string_2' => $self->{'task_data'}{'snmp_privacy_pass'},
          'custom_string_3' => $self->{'task_data'}{'snmp_security_level'},
          'plugin_parameter' => $self->{'task_data'}{'snmp_auth_method'},
          'plugin_user' => $self->{'task_data'}{'snmp_auth_user'},
          'plugin_pass' => $self->{'task_data'}{'snmp_auth_pass'},
          'snmp_community' => $community,
          'snmp_oid' => "$PandoraFMS::Recon::Base::IFOUTOCTECTS.$if_index",
          'unit' => safe_input('bytes/s')
        }
      );
    }

    # Bandwidth plugin.
    my $plugin = get_db_single_row(
      $self->{'dbh'},
      'SELECT id, macros FROM tplugin WHERE name = "Network&#x20;bandwidth&#x20;SNMP"',
    );
     next unless defined($plugin);

      # Network Bandwidth is installed.
			my $macros = p_decode_json($self->{'config'}, safe_output($plugin->{'macros'}));
      my $id_plugin = $plugin->{'id'};

      if(ref($macros) eq "HASH") {
      
          # SNMP Version.
          $macros->{'1'}->{'value'} = $self->{'task_data'}->{'snmp_version'};
          # Community.
          $macros->{'2'}->{'value'} = $community;
          # Host.
          $macros->{'3'}->{'value'} = $device;
          # Port.
          $macros->{'4'}->{'value'} = 161;
          # Interface index filter.
          $macros->{'5'}->{'value'} = $if_index;
          # SecurityName.
          $macros->{'6'}->{'value'} = $self->{'task_data'}->{'snmp_auth_user'};
          # SecurityContext.
          $macros->{'7'}->{'value'} = $community;
          # SecurityLevel.
          $macros->{'8'}->{'value'} = $self->{'task_data'}->{'snmp_security_level'};
          # AuthProtocol.
          $macros->{'9'}->{'value'} = $self->{'task_data'}->{'snmp_auth_method'};
          # AuthKey.
          $macros->{'10'}->{'value'} = $self->{'task_data'}->{'snmp_auth_pass'};
          # PrivProtocol.
          $macros->{'11'}->{'value'} = $self->{'task_data'}->{'snmp_privacy_method'};
          # PrivKey.
          $macros->{'12'}->{'value'} = $self->{'task_data'}->{'snmp_privacy_pass'};
          # Hash identifier.
          $macros->{'13'}->{'value'} = PandoraFMS::Tools::generate_agent_name_hash($if_name, $device);
          # Get input usage.
          $macros->{'14'}->{'value'} = 0;
          # Get output usage.
          $macros->{'15'}->{'value'} = 0;

          $self->call(
            'add_module',
            $device,
            {
              'id_tipo_modulo' => 1,
              'id_modulo' => 4,
              'name' => $if_name."_Bandwith",
              'descripcion' => safe_input(
			          'Amount of digital information sent and received from this interface over a particular time',
              ),
              'unit' => '%',
              'macros' => p_encode_json($self->{'config'}, $macros),
              'id_plugin' => $id_plugin,
              'unit' => '%',
              'min_warning'   => '0',
              'max_warning'   => '0',
              'min_critical'  => '85',
              'max_critical'  => '0',
            }
          );

          # inUsage
          # Hash identifier.
          $macros->{'13'}->{'value'} = PandoraFMS::Tools::generate_agent_name_hash($if_name, $device);
          # Get input usage.
          $macros->{'14'}->{'value'} = 1;
          # Get output usage.
          $macros->{'15'}->{'value'} = 0;
          
          $self->call(
          'add_module',
          $device,
          {
            'id_tipo_modulo' => 1,
            'id_modulo' => 4,
            'name' => $if_name."_inUsage",
            'descripcion' => safe_input(
			        'Bandwidth usage received into this interface over a particular time',
            ),
            'unit' => '%',
            'macros' => p_encode_json($self->{'config'}, $macros),
            'id_plugin' => $id_plugin,
            'unit' => '%',
            'min_warning'   => '0',
            'max_warning'   => '0',
            'min_critical'  => '85',
            'max_critical'  => '0',
          }
        );

          # OutUsage.
          # Hash identifier.
          $macros->{'13'}->{'value'} = PandoraFMS::Tools::generate_agent_name_hash($if_name, $device);
          # Get input usage.
          $macros->{'14'}->{'value'} = 0;
          # Get output usage.
          $macros->{'15'}->{'value'} = 1;

          $self->call(
          'add_module',
          $device,
          {
            'id_tipo_modulo' => 1,
            'id_modulo' => 4,
            'name' => $if_name."_outUsage",
            'descripcion' => safe_input(
			        'Bandwidth usage sent from this interface over a particular time',
            ),
            'unit' => '%',
            'macros' => p_encode_json($self->{'config'}, $macros),
            'id_plugin' => $id_plugin,
            'unit' => '%',
            'min_warning'   => '0',
            'max_warning'   => '0',
            'min_critical'  => '85',
            'max_critical'  => '0',
          }
        );
      }
  }

}

################################################################################
# Add wmi modules to the given host.
################################################################################
sub PandoraFMS::Recon::Base::create_wmi_modules {
  my ($self, $target) = @_;

  # Add modules to the agent if it responds to WMI.
  return unless ($self->wmi_responds($target));

  my $key = $self->wmi_credentials_key($target);
  my $creds = $self->call('get_credentials', $key);

  # Add modules.
  # CPU.
  my @cpus = $self->wmi_get_value_array($target, 'SELECT DeviceId FROM Win32_Processor', 0);
  foreach my $cpu (@cpus) {
    $self->add_module(
      $target,
      {
        'ip_target' => $target,
        'snmp_oid' => "SELECT LoadPercentage FROM Win32_Processor WHERE DeviceId=\'$cpu\'",
        'plugin_user' => $creds->{'username'},
        'plugin_pass' => $creds->{'password'},
        'tcp_port' => 1,
        'name' => "CPU Load $cpu",
        'descripcion' => safe_input("Load for $cpu (%)"),
        'id_tipo_modulo' => 1,
        'id_modulo' => 6,
        'unit' => '%',
      }
    );
  }

  # Memory.
  my $mem = $self->wmi_get_value($target, 'SELECT FreePhysicalMemory FROM Win32_OperatingSystem', 0);
  if (defined($mem)) {
    $self->add_module(
      $target,
      {
        'ip_target' => $target,
        'snmp_oid' => "SELECT FreePhysicalMemory, TotalVisibleMemorySize FROM Win32_OperatingSystem",
        'plugin_user' => $creds->{'username'},
        'plugin_pass' => $creds->{'password'},
        'tcp_port' => 0,
        'name' => 'FreeMemory',
        'descripcion' => safe_input('Free memory'),
        'id_tipo_modulo' => 1,
        'id_modulo' => 6,
        'unit' => 'KB',
      }
    );
  }

  # Disk.
  my @units = $self->wmi_get_value_array($target, 'SELECT DeviceID FROM Win32_LogicalDisk', 0);
  foreach my $unit (@units) {
    $self->add_module(
      $target,
      {
        'ip_target' => $target,
        'snmp_oid' => "SELECT FreeSpace FROM Win32_LogicalDisk WHERE DeviceID='$unit'",
        'plugin_user' => $creds->{'username'},
        'plugin_pass' => $creds->{'password'},
        'tcp_port' => 1,
        'name' => "FreeDisk $unit",
        'descripcion' => safe_input('Available disk space in kilobytes'),
        'id_tipo_modulo' => 1,
        'id_modulo' => 6,
        'unit' => 'KB',
      }
    );
  }

}

################################################################################
# Create network profile modules for the given agent.
################################################################################
sub PandoraFMS::Recon::Base::create_network_profile_modules($$) {
  my ($self, $device) = @_;

  my @template_ids = ();

  if (is_enabled($self->{'task_data'}{'auto_monitor'})) {
    # Apply PEN monitoring template (HW).
    my @pen_templates= get_pen_templates($self->{'dbh'}, $self->get_pen($device));
    # Join.
    @template_ids = (@template_ids, @pen_templates);
  } else {
    # Return if no specific templates are selected.
    return if is_empty($self->{'id_network_profile'});
  }

  push @template_ids, split /,/, $self->{'id_network_profile'}
    unless is_empty($self->{'id_network_profile'});

  my $data = $self->{'agents_found'}{$device};

  foreach my $t_id (@template_ids) {
    # 1. Retrieve template info.
    my $template = get_nc_profile_advanced($self->{'dbh'}, $t_id);

    # 2. Verify Private Enterprise Number matches (PEN)
    if (defined($template->{'pen'})) {
      my @pens = split(',', $template->{'pen'});

      next unless (is_in_array(\@pens, $self->get_pen($device)));
    }

    # 3. Retrieve module list from target template.
    my @np_components = get_db_rows(
      $self->{'dbh'},
      'SELECT * FROM tnetwork_profile_component WHERE id_np = ?',
      $t_id
    );

    foreach my $np_component (@np_components) {
      # 4. Register each module (candidate). 'add_module' will test them.
      my $component = get_db_single_row(
        $self->{'dbh'},
        'SELECT * FROM tnetwork_component WHERE id_nc = ?',
        $np_component->{'id_nc'}
      );

      # Tag cleanup.
      if (!is_empty($component->{'tags'})) {
        my @tags = map {
          if ($_ > 0) { $_ }
          else {}
        } split ',', $component->{'tags'};

        $component->{'tags'} = join ',', @tags;
      }

      $component->{'name'} = safe_output($component->{'name'});
      if ($component->{'type'} >= 15 && $component->{'type'} <= 18) {
        $component->{'snmp_community'} = safe_output($self->get_community($device));
        $component->{'tcp_send'} = $self->{'snmp_version'};
        $component->{'custom_string_1'} = $self->{'snmp_privacy_method'};
        $component->{'custom_string_2'} = $self->{'snmp_privacy_pass'};
        $component->{'custom_string_3'} = $self->{'snmp_security_level'};
        $component->{'plugin_parameter'} = $self->{'snmp_auth_method'};
        $component->{'plugin_user'} = $self->{'snmp_auth_user'};
        $component->{'plugin_pass'} = $self->{'snmp_auth_pass'};
      }

      if ($component->{'type'} >= 34 && $component->{'type'} <= 37) {
        # Update module credentials.
        $component->{'custom_string_1'} = $self->rcmd_credentials_key($device);
        $component->{'custom_string_2'} = pandora_get_os_by_id(
          $self->{'dbh'},
          $self->guess_os($device)
        );
      }

      $component->{'__module_component'} = 1;

      # 3. Try to register module into monitoring list.
      $self->call('add_module', $device, $component);
    }
  }

}

################################################################################
# Retrieve a key from credential store.
################################################################################
sub PandoraFMS::Recon::Base::get_credentials {
  my ($self, $key_index) = @_;

  return credential_store_get_key(
    $self->{'pa_config'},
    $self->{'dbh'},
    $key_index
  );
}

################################################################################
# Create agents and modules reported by Recon::Base.
################################################################################
sub PandoraFMS::Recon::Base::report_scanned_agents($;$) {
  my ($self,$force) = @_;

  my $force_creation = $force;
  $force_creation = 0 unless (is_enabled($force));

  #
  # Creation
  #

  if($force_creation == 1
    || (defined($self->{'task_data'}{'review_mode'})
        && $self->{'task_data'}{'review_mode'} == DISCOVERY_RESULTS)
  ) {

    # Load cache.
    my @rows = get_db_rows(
      $self->{'dbh'},
      'SELECT * FROM tdiscovery_tmp_agents WHERE `id_rt`=?',
      $self->{'task_data'}{'id_rt'}
    );

    # Return if no entries.
    return unless scalar @rows > 0;

    my @agents;

    my $progress = 0;
    my $step = 100.00 / scalar @rows;
    foreach my $row (@rows) {
      $progress += $step;
      $self->call('update_progress', $progress);

      my $name = safe_output($row->{'label'});
      my $checked = 0;
      my $data;
      eval {
        local $SIG{__DIE__};
        $data = p_decode_json($self->{'pa_config'}, decode_base64($row->{'data'}));
      };
      if ($@) {
        $self->call('message', "ERROR JSON: $@", 3);
      }

      # Agent could be 'not checked' unless  all modules are selected.
      if (ref($data->{'modules'}) eq 'HASH') {
        my @map = map {
          my $name = $_->{'name'};
          $name = $_->{'nombre'} if is_empty($name);

          if (is_enabled($_->{'checked'})
            && $name ne 'Host Alive'
          ) {
            $name;
          } else {}

        } values %{$data->{'modules'}};

        $checked = scalar  @map;
      }

      $checked = $data->{'agent'}{'checked'} if
        is_enabled($data->{'agent'}{'checked'})
        && $checked < $data->{'agent'}{'checked'};

      # Register target agent if enabled.
      if (is_enabled($checked)
        || $force_creation
      ) {
        my $parent_id;
        my $os_id = $data->{'agent'}{'id_os'};
        if (is_empty($os_id)) {
          $os_id = $self->guess_os($data->{'agent'}{'direccion'});
        }


        $self->call('message', "Agent accepted: ".$data->{'agent'}{'nombre'}, 5);

        # Agent creation.
        my $agent_id = $data->{'agent'}{'agent_id'};
        my $agent_learning;
        my $agent_data;

        if (defined($agent_id) && $agent_id > 0) {
          $agent_data = get_db_single_row(
            $self->{'dbh'},
            'SELECT * FROM tagente WHERE id_agente = ?',
            $agent_id
          );
          $agent_learning = $agent_data->{'modo'} if ref($agent_data) eq 'HASH';
        }

        if (!defined($agent_learning)) {
          # Agent id does not exists or is invalid.

          # Check if has been created by another process, if not found.
          $agent_data = PandoraFMS::Core::locate_agent(
            $self->{'pa_config'}, $self->{'dbh'}, $data->{'agent'}{'direccion'}
          ) if ref($agent_data) ne 'HASH';

          $agent_id = $agent_data->{'id_agente'} if ref($agent_data) eq 'HASH';
          if (ref($agent_data) eq 'HASH' && $agent_data->{'modo'} != 1) {
            # Agent previously exists, but is not in learning mode, so skip
            # modules scan and jump directly to parent analysis.
            $data->{'agent'}{'agent_id'} = $agent_id;
            push @agents, $data->{'agent'};
            next;
          }

          if (!defined($agent_id) || $agent_id <= 0 || !defined($agent_data)) {
            # Agent creation.
            $agent_id = pandora_create_agent(
              $self->{'pa_config'}, $self->{'servername'}, $data->{'agent'}{'nombre'},
              $data->{'agent'}{'direccion'}, $self->{'task_data'}{'id_group'}, $parent_id,
              $os_id, $data->{'agent'}->{'description'},
              $data->{'agent'}{'interval'}, $self->{'dbh'},
              $data->{'agent'}{'timezone_offset'}, undef, undef, undef, undef,
              undef, undef, 1, $data->{'agent'}{'alias'}, undef, $data->{'agent'}{'os_version'}
            );

            # Add found IP addresses to the agent.
            if (ref($data->{'other_ips'}) eq 'ARRAY') {
              foreach my $ip_addr (@{$data->{'other_ips'}}) {
                my $addr_id = get_addr_id($self->{'dbh'}, $ip_addr);
                $addr_id = add_address($self->{'dbh'}, $ip_addr) unless ($addr_id > 0);
                next unless ($addr_id > 0);

                # Assign the new address to the agent
                my $agent_addr_id = get_agent_addr_id($self->{'dbh'}, $addr_id, $agent_id);
                if ($agent_addr_id <= 0) {
                  db_do(
                    $self->{'dbh'}, 'INSERT INTO taddress_agent (`id_a`, `id_agent`)
                                      VALUES (?, ?)', $addr_id, $agent_id
                  );
                }
              }
            }

            # Agent autoconfiguration.
            if (is_enabled($self->{'autoconfiguration_enabled'})) {
              my $agent_data = PandoraFMS::DB::get_db_single_row(
                $self->{'dbh'},
                'SELECT * FROM tagente WHERE id_agente = ?',
                $agent_id
              );

              # Update agent configuration once, after create agent.
              enterprise_hook(
                'autoconfigure_agent',
                [
                  $self->{'pa_config'},
                  $data->{'agent'}{'direccion'},
                  $agent_id,
                  $agent_data,
                  $self->{'dbh'},
                  1
                ]
              );
            }

            if (defined($self->{'main_event_id'})) {
              my $addresses_str = join(
                ',',
                $self->get_addresses(safe_output($data->{'agent'}{'nombre'}))
              );

              pandora_extended_event(
                $self->{'pa_config'}, $self->{'dbh'},
                $self->{'main_event_id'},"[Discovery] New " 
                  . $self->get_device_type(safe_output($data->{'agent'}{'nombre'}))
                  . " found " . $data->{'agent'}{'nombre'} . " (" . $addresses_str
                  . ") Agent $agent_id."
              );
            }

            $agent_learning = 1;
          } else {
            # Read from effective agent_id.
            $agent_learning = get_db_value(
              $self->{'dbh'},
              'SELECT modo FROM tagente WHERE id_agente = ?',
              $agent_id
            );

            # Update new IPs.
            # Add found IP addresses to the agent.
            if (ref($data->{'other_ips'}) eq 'ARRAY') {
              foreach my $ip_addr (@{$data->{'other_ips'}}) {
                my $addr_id = get_addr_id($self->{'dbh'}, $ip_addr);
                $addr_id = add_address($self->{'dbh'}, $ip_addr) unless ($addr_id > 0);
                next unless ($addr_id > 0);

                # Assign the new address to the agent
                my $agent_addr_id = get_agent_addr_id($self->{'dbh'}, $addr_id, $agent_id);
                if ($agent_addr_id <= 0) {
                  db_do(
                    $self->{'dbh'}, 'INSERT INTO taddress_agent (`id_a`, `id_agent`)
                                      VALUES (?, ?)', $addr_id, $agent_id
                  );
                }
              }
            }
          }

          $data->{'agent'}{'agent_id'} = $agent_id;
        }

        $data->{'agent'}{'modo'} = $agent_learning;
        $self->call('message', "Agent id: ".$data->{'agent'}{'agent_id'}, 5);

        # Create selected modules.
        if(ref($data->{'modules'}) eq "HASH") {
          foreach my $i (keys %{$data->{'modules'}}) {
            my $module = $data->{'modules'}{$i};

            $module->{'name'} = $module->{'nombre'} if is_empty($module->{'name'});

            # Do not create any modules if the agent is not in learning mode.
            next unless ($agent_learning == 1);

            # Host alive is always being created.
            if ($module->{'name'} ne 'Host Alive') {
              next unless (is_enabled($module->{'checked'}) || $force_creation);
            }

            $self->call('message', "[$agent_id] Module: ".$module->{'name'}, 5);

            my $agentmodule_id = get_db_value(
              $self->{'dbh'},
              'SELECT id_agente_modulo FROM tagente_modulo
               WHERE id_agente = ? AND nombre = ?',
              $agent_id,
              safe_input($module->{'name'})
            );

            if (!is_enabled($agentmodule_id)) {
              # Create.

              # Delete unwanted fields.
              delete $module->{'agentmodule_id'};
              delete $module->{'checked'};

              my $id_tipo_modulo = $module->{'id_tipo_modulo'};
              $id_tipo_modulo = get_module_id($self->{'dbh'}, $module->{'type'})
                if is_empty($id_tipo_modulo);

              my $description = safe_output($module->{'descripcion'});
              $description = '' if is_empty($description);

              my $unit = safe_output($module->{'unit'});
              $unit = '' if is_empty($unit);
              

              if (is_enabled($module->{'__module_component'})) {
                # Module from network component.
                delete $module->{'__module_component'};
                $agentmodule_id = pandora_create_module_from_network_component(
                  $self->{'pa_config'},
                  # Send a copy, not original, because of 'deletes'
                  {
                    %{$module},
                    'name' => safe_input($module->{'name'}),
                  },
                  $agent_id,
                  $self->{'dbh'}
                );

                # Restore.
                $module->{'__module_component'} = 1;
              } else {
                # Create module - Direct.
                my $name = $module->{'name'};
                my $description = safe_output($module->{'descripcion'});

                my $unit = safe_output($module->{'unit'});
                $unit = '' if is_empty($unit);
                
                delete $module->{'name'};
                delete $module->{'description'};
                $agentmodule_id = pandora_create_module_from_hash(
                  $self->{'pa_config'},
                  {
                    %{$module},
                    'id_tipo_modulo' => $id_tipo_modulo,
                    'id_modulo' => $module->{'id_modulo'},
                    'nombre' => safe_input($name),
                    'descripcion' => safe_input($description),
                    'id_agente' => $agent_id,
                    'ip_target' => $data->{'agent'}{'direccion'},
                    'unit' => safe_input($unit)
                  },
                  $self->{'dbh'}
                );

                $module->{'name'} = $name;
                $module->{'description'} = safe_output($description);
              }

              # Restore.
              $module->{'checked'} = 1;

              # Store.
              $data->{'modules'}{$i}{'agentmodule_id'} = $agentmodule_id;

              $self->call(
                'message',
                "[$agent_id] Module: ".$module->{'name'}." ID: $agentmodule_id",
                5
              );
            }
          }
        }

        my $encoded;
        eval {
          local $SIG{__DIE__};
          $encoded = encode_base64(
            p_encode_json($self->{'pa_config'}, $data)
          );
        };

        push @agents, $data->{'agent'};

        # Update.
        db_do(
          $self->{'dbh'},
          'UPDATE tdiscovery_tmp_agents SET `data` = ? '
          .'WHERE `id_rt` = ? AND `label` = ?',
          $encoded,
          $self->{'task_data'}{'id_rt'},
          $name
        );

      }
    }

    # Update parent relationships.
    foreach my $agent (@agents) {
      # Avoid processing if does not exist.
      next unless (defined($agent->{'agent_id'}));

      # Avoid processing undefined parents.
      next unless defined($agent->{'parent'});

      # Get parent id.
      my $parent = PandoraFMS::Core::locate_agent(
        $self->{'pa_config'}, $self->{'dbh'}, $agent->{'parent'}
      );

      next unless defined($parent);

      # Is the agent in learning mode?
      next unless ($agent->{'modo'} == 1);

      # Connect the host to its parent.
      db_do($self->{'dbh'},
        'UPDATE tagente SET id_parent=? WHERE id_agente=?',
        $parent->{'id_agente'}, $agent->{'agent_id'}
      );
    }

    # Update OS information.
    foreach my $agent (@agents) {

      # Avoid processing if does not exist.
      next unless (defined($agent->{'agent_id'}));

      # Make sure OS version information is available.
      next unless (defined($agent->{'os_version'}));

      # Is the agent in learning mode?
      next unless ($agent->{'modo'} == 1);

      # Set the OS version.
      db_do($self->{'dbh'},
        'UPDATE tagente SET os_version=? WHERE id_agente=?',
        $agent->{'os_version'}, $agent->{'agent_id'}
      );
    }

    # Connect agents.
    my @connections = get_db_rows(
      $self->{'dbh'},
      'SELECT * FROM tdiscovery_tmp_connections WHERE id_rt = ?',
      $self->{'task_data'}{'id_rt'}
    );

    foreach my $cn (@connections) {
      $self->call('connect_agents',
        $cn->{'dev_1'},
        $cn->{'if_1'},
        $cn->{'dev_2'},
        $cn->{'if_2'},
        # Force creation if direct.
        $force_creation
      );
    }

    # Data creation finished.
    return;
  }


  #
  # Cleanup previous results.
  #
  $self->call('message', "Cleanup previous results", 6);
  db_do(
    $self->{'dbh'},
    'DELETE FROM tdiscovery_tmp_agents '
    .'WHERE `id_rt` = ?',
    $self->{'task_data'}{'id_rt'}
  );

  #
  # Store and review.
  #

  $self->call('message', "Storing results", 6);
  my @hosts = keys %{$self->{'agents_found'}};
  $self->{'step'} = STEP_PROCESSING;
  if ((scalar (@hosts)) > 0) {
    my ($progress, $step) = (90, 10.0 / scalar(@hosts)); # From 90% to 100%.

    foreach my $addr (keys %{$self->{'agents_found'}}) {
      my $label = $self->{'agents_found'}->{$addr}{'agent'}{'nombre'};

      next if is_empty($label);

      # Retrieve target agent OS.
      $self->{'agents_found'}->{$addr}{'agent'}{'id_os'} = $self->guess_os($addr);

      my $os_version = $self->get_os_version($addr);

      if (is_empty($os_version)) {
        $os_version = $self->guess_os($addr, undef, 1);
      }

      # Retrieve target agent OS version.
      $self->{'agents_found'}->{$addr}{'agent'}{'os_version'} = $os_version;

      $self->call('update_progress', $progress);
      $progress += $step;
      # Store temporally. Wait user approval.
      my $encoded;

      eval {
        local $SIG{__DIE__};
        $encoded = encode_base64(
          p_encode_json($self->{'pa_config'}, $self->{'agents_found'}->{$addr})
        );
      };

      my $id = get_db_value(
        $self->{'dbh'},
        'SELECT id FROM tdiscovery_tmp_agents WHERE id_rt = ? AND label = ?',
        $self->{'task_data'}{'id_rt'},
        safe_input($label)
      );
      
      if (defined($id)) {
        # Already defined.
        $self->{'agents_found'}{$addr}{'id'} = $id;

        db_do(
          $self->{'dbh'},
          'UPDATE tdiscovery_tmp_agents SET `data` = ? '
          .'WHERE `id_rt` = ? AND `label` = ?',
          $encoded,
          $self->{'task_data'}{'id_rt'},
          safe_input($label)
        );
        next;
      }

      # Insert.
      $self->{'agents_found'}{$addr}{'id'} = db_insert(
        $self->{'dbh'},
        'id',
        'INSERT INTO tdiscovery_tmp_agents (`id_rt`,`label`,`data`,`created`) '
        .'VALUES (?, ?, ?, now())',
        $self->{'task_data'}{'id_rt'},
        safe_input($label),
        $encoded
      );
    }
  }

  if(defined($self->{'task_data'}{'review_mode'})
    && $self->{'task_data'}{'review_mode'} == DISCOVERY_REVIEW
  ) {
    # Notify.
    my $notification = {};
    $notification->{'subject'} = safe_input('Discovery task ');
    $notification->{'subject'} .= $self->{'task_data'}{'name'};
    $notification->{'subject'} .= safe_input(' review pending');
    $notification->{'url'} = ui_get_full_url(
      'index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=tasklist#'
    );

    $notification->{'subtype'} .= safe_input('NOTIF.DISCOVERYTASK.REVIEW');

    $notification->{'mensaje'} = safe_input(
      'Discovery task (host&devices) \''.safe_output($self->{'task_data'}{'name'})
      .'\' has been completed. Please review the results.'
    );

    $notification->{'id_source'} = get_db_value(
      $self->{'dbh'},
      'SELECT id FROM tnotification_source WHERE description = ?',
      safe_input('System status')
    );

    # Create message
    my $notification_id = db_process_insert(
      $self->{'dbh'},
      'id_mensaje',
      'tmensajes',
      $notification
    );

    if (is_enabled($notification_id)) {
      my @users = notification_get_users($self->{'dbh'}, 'System status');
      my @groups = notification_get_groups($self->{'dbh'}, 'System status');

      notification_set_targets(
        $self->{'pa_config'}, $self->{'dbh'},
        $notification_id, \@users, \@groups
      );
    }
  }

  $self->call('message', "Completed", 5);
}

################################################################################
# Apply monitoring templates selected to detected agents.
################################################################################
sub PandoraFMS::Recon::Base::apply_monitoring($) {
  my ($self) = @_;

  my @hosts = keys %{$self->{'agents_found'}};

  my $progress = 80;

  if (scalar @hosts > 0) {
    $self->{'step'} = STEP_MONITORING;
    # From 80% to 90%.
    my ($progress, $step) = (80, 10.0 / scalar(@hosts));
    my ($partial, $sub_step) = (0, 100 / scalar(@hosts));

    foreach my $label (keys %{$self->{'agents_found'}}) {
      $self->{'c_network_percent'} = $partial;
      $self->{'c_network_name'} = $label;
      $self->call('update_progress', $progress);
      $progress += $step;
      $partial += $sub_step;
      $self->call('message', "Checking modules for $label", 5);

      # Monitorization selected.
      $self->call('create_network_profile_modules', $label);

      # Monitorization - interfaces
      $self->call('create_interface_modules', $label);

      # Monitorization - WMI modules.
      $self->call('create_wmi_modules', $label);

    }
    
  }

  $self->{'c_network_percent'} = 100;
  $self->call('update_progress', $progress);
}

################################################################################
# Connect the given devices in the Pandora FMS database.
################################################################################
sub PandoraFMS::Recon::Base::connect_agents($$$$$;$) {
  my ($self, $dev_1, $if_1, $dev_2, $if_2, $force) = @_;

  if($self->{'task_data'}{'review_mode'} == DISCOVERY_REVIEW
    || is_enabled($force)
  ) {
    # Store in tdiscovery_tmp_connections;

    db_process_insert(
      $self->{'dbh'},
      'id',
      'tdiscovery_tmp_connections',
      {
        'id_rt' => $self->{'task_data'}{'id_rt'},
        'dev_1' => $dev_1,
        'if_1'  => $if_1,
        'dev_2' => $dev_2,
        'if_2'  => $if_2,
      }
    );

    return;
  }

  # Get the agent for the first device.
  my $agent_1 = get_agent_from_addr($self->{'dbh'}, $dev_1);
  if (!defined($agent_1)) {
    $agent_1 = get_agent_from_name($self->{'dbh'}, $dev_1);
  }
  return unless defined($agent_1);

  # Get the agent for the second device.
  my $agent_2 = get_agent_from_addr($self->{'dbh'}, $dev_2);
  if (!defined($agent_2)) {
    $agent_2 = get_agent_from_name($self->{'dbh'}, $dev_2);
  }
  return unless defined($agent_2);

  # Use ping modules by default.
  $if_1 = 'Host Alive' if ($if_1 eq '');
  $if_2 = 'Host Alive' if ($if_2 eq '');

  # Check whether the modules exists.
  my $module_name_1 = $if_1 eq 'Host Alive' ? 'Host Alive' : "${if_1}_ifOperStatus";
  my $module_name_2 = $if_2 eq 'Host Alive' ? 'Host Alive' : "${if_2}_ifOperStatus";
  my $module_id_1 = get_agent_module_id($self->{'dbh'}, $module_name_1, $agent_1->{'id_agente'});
  if ($module_id_1 <= 0) {
    $self->call('message', "ERROR: Module " . safe_output($module_name_1) . " does not exist for agent $dev_1.", 5);
    return;
  }
  my $module_id_2 = get_agent_module_id($self->{'dbh'}, $module_name_2, $agent_2->{'id_agente'});
  if ($module_id_2 <= 0) {
    $self->call('message', "ERROR: Module " . safe_output($module_name_2) . " does not exist for agent $dev_2.", 5);
    return;
  }

  # Connect the modules if they are not already connected.
  my $connection_id = get_db_value($self->{'dbh'}, 'SELECT id FROM tmodule_relationship WHERE (module_a = ? AND module_b = ? AND `type` = "direct") OR (module_b = ? AND module_a = ? AND `type` = "direct")', $module_id_1, $module_id_2, $module_id_1, $module_id_2);
  if (! defined($connection_id)) {
    db_do($self->{'dbh'}, 'INSERT INTO tmodule_relationship (`module_a`, `module_b`, `id_rt`) VALUES(?, ?, ?)', $module_id_1, $module_id_2, $self->{'task_id'});
  }
}


################################################################################
# Create agents from db_scan. Uses DataServer methods.
# data = [
#	'agent_data' => {},
#	'module_data' => []
#	'inventory_data' => []
# ]
################################################################################
sub PandoraFMS::Recon::Base::create_agents($$) {
  my ($self, $data) = @_;

  my $pa_config = $self->{'pa_config'};
  my $dbh = $self->{'dbh'};
  my $server_id = $self->{'server_id'};

  return undef if (ref($data) ne "ARRAY");

  foreach my $information (@{$data}) {
    my $agent = $information->{'agent_data'};
    my $modules = defined($information->{'module_data'}) ? $information->{'module_data'} : [];
    my $inventory = defined($information->{'inventory_data'}) ? $information->{'inventory_data'} : [];
    my $force_processing = 0;

    # Search agent
    my $current_agent = PandoraFMS::Core::locate_agent(
      $pa_config, $dbh, $agent->{'agent_name'}
    );

    my $parent_id;
	if (defined($agent->{'id_parent'})) {
		$parent_id = $agent->{'id_parent'};
	} elsif (defined($agent->{'parent_agent_name'})) {
      $parent_id = PandoraFMS::Core::locate_agent(
        $pa_config, $dbh, $agent->{'parent_agent_name'}
      );
      if ($parent_id) {
        $parent_id = $parent_id->{'id_agente'};
      }
    }

    my $agent_id;
    my $os_id = defined($agent->{'id_os'}) ? $agent->{'id_os'} : get_os_id($dbh, $agent->{'os'});

    if ($os_id < 0) {
      $os_id = get_os_id($dbh, 'Other');
    }

    if (!$current_agent) {
      # Create agent.
      $agent_id = pandora_create_agent(
        $pa_config, $pa_config->{'servername'}, $agent->{'agent_name'},
        $agent->{'address'}, $agent->{'id_group'}, $parent_id,
        $os_id, $agent->{'description'},
        $agent->{'interval'}, $dbh, $agent->{'timezone_offset'}
      );

      $current_agent = $parent_id = PandoraFMS::Core::locate_agent(
        $pa_config, $dbh, $agent->{'agent_name'}
      );

      $force_processing = 1;

    } else {
      if ($current_agent->{'disabled'} eq '0') {
        $agent_id = $current_agent->{'id_agente'};
      }
    }

    if (!defined($agent_id)) {
      return undef;
    }

    if (defined($agent->{'address'}) && $agent->{'address'} ne '') {
      pandora_add_agent_address(
        $pa_config, $agent_id, $agent->{'agent_name'},
        $agent->{'address'}, $dbh
      );
    }

    # Update agent information
    pandora_update_agent(
      $pa_config, strftime("%Y-%m-%d %H:%M:%S", localtime()), $agent_id,
      $agent->{'os_version'}, $agent->{'agent_version'},
      $agent->{'interval'}, $dbh, undef, $parent_id
    );

    # Add modules.
    if (ref($modules) eq "ARRAY") {
      foreach my $module (@{$modules}) {
        next unless ref($module) eq 'HASH';
        # Translate data structure to simulate XML parser return.
        my %data_translated = map { $_ => [ $module->{$_} ] } keys %{$module};

        # Process modules.
        PandoraFMS::DataServer::process_module_data (
          $pa_config, \%data_translated,
          $server_id, $current_agent,
          $module->{'name'}, $module->{'type'},
          $agent->{'interval'},
          strftime ("%Y/%m/%d %H:%M:%S", localtime()),
          $dbh, $force_processing
        );
      }
    }

    # Add inventory data.
    if (ref($inventory) eq "HASH") {
      PandoraFMS::Core::process_inventory_data (
	  	$pa_config,
	  	$inventory,
	  	0, # Does not seem to be used.
	  	$agent->{'agent_name'},
	  	$agent->{'interval'},
	  	strftime ("%Y/%m/%d %H:%M:%S", localtime()),
	  	$dbh
      );
    }
  }
}

################################################################################
# Delete already existing connections.
################################################################################
sub PandoraFMS::Recon::Base::delete_connections($) {
  my ($self) = @_;

  $self->call('message', "Deleting connections...", 10);
  db_do($self->{'dbh'}, 'DELETE FROM tmodule_relationship WHERE id_rt=?', $self->{'task_id'});
}

################################################################################
# Print log messages.
################################################################################
sub PandoraFMS::Recon::Base::message($$$) {
  my ($self, $message, $verbosity) = @_;

  if ($verbosity <= 1) {
    my $label = "[Discovery task " . $self->{'task_id'} . "]";
    if (ref($self->{'task_data'}) eq 'HASH' && defined($self->{'task_data'}{'name'})) {
      $label = "[Discovery task " . $self->{'task_data'}{'name'} . "]";
    }

    PandoraFMS::Core::send_console_notification(
      $self->{'pa_config'},
      $self->{'parent'}->getDBH(),
      $label,
      $message,
      ['admin']
    );

    $self->{'summary'} = $message;
  }

  logger($self->{'pa_config'}, "[Recon task " . $self->{'task_id'} . "] $message", $verbosity);
}

################################################################################
# Connect the given hosts to its parent.
################################################################################
sub PandoraFMS::Recon::Base::set_parent($$$) {
  my ($self, $host, $parent) = @_;

  return unless ($self->{'parent_detection'} == 1);

  # Do not edit 'not scaned' agents.
  return if is_empty($self->{'agents_found'}{$host}{'agent'});

  $self->{'agents_found'}{$host}{'agent'}{'parent'} = $parent;

  # Add host alive module for parent.
  $self->add_module($parent,
    {
      'ip_target' => $parent,
      'name' => "Host Alive",
      'description' => '',
      'type' => 'remote_icmp_proc',
      'id_modulo' => 2,
    }
  );
}

################################################################################
# Update recon task status.
################################################################################
sub PandoraFMS::Recon::Base::update_progress ($$) {
  my ($self, $progress) = @_;

  my $stats = {};
  eval {
    local $SIG{__DIE__};
    if (defined($self->{'summary'}) && $self->{'summary'} ne '') {
      $stats->{'summary'} = $self->{'summary'};
    }

    $stats->{'step'} = $self->{'step'};
    $stats->{'c_network_name'} = $self->{'c_network_name'};
    $stats->{'c_network_percent'} = $self->{'c_network_percent'};

    # Store progress, last contact and overall status.
    db_do ($self->{'dbh'}, 'UPDATE trecon_task SET utimestamp = ?, status = ?, summary = ? WHERE id_rt = ?',
      time (), $progress, p_encode_json($self->{'pa_config'}, $stats), $self->{'task_id'});
  };
  if ($@) {
    $self->call('message', "Problems updating progress $@", 5);
    db_do ($self->{'dbh'}, 'UPDATE trecon_task SET utimestamp = ?, status = ?, summary = ? WHERE id_rt = ?',
      time (), $progress, "{}", $self->{'task_id'});
  }
}

################################################################################
# Store a log with execution details.
################################################################################
sub log_execution($$$$) {
  my ($pa_config, $task_id, $cmd, $output) = @_;

  return unless $pa_config->{'verbosity'} eq 10;

  my $discovery_log_path = dirname($pa_config->{'log_file'}).'/discovery/';
  mkdir($discovery_log_path) unless -d  $discovery_log_path;

  eval {
    local $SIG{__DIE__};

    open (my $f, ">", $discovery_log_path.'task.'.$task_id.'.cmd');
    print $f $cmd;
    close ($f);

    open ($f, ">", $discovery_log_path.'task.'.$task_id.'.out');
    print $f $output;
    close ($f);

  };
  
}

################################################################################
# Store configuration files.
################################################################################
sub log_conf_files($$@) {
	my $pa_config = shift;
  my $task_id = shift;
  my @files = @_;

  return unless $pa_config->{'verbosity'} eq 10;

	my $discovery_log_path = dirname($pa_config->{'log_file'}).'/discovery/';
	mkdir($discovery_log_path) unless -d  $discovery_log_path;

	eval {
		local $SIG{__DIE__};

    foreach my $f (@files) {
      copy($f, $discovery_log_path);
    }
	};

}

1;
__END__
