#!/usr/bin/perl
###############################################################################
# Pandora FMS Daemon Watchdog
###############################################################################
# Copyright (c) 2018-2023 Pandora FMS
###############################################################################

use strict;
use warnings;
use DBI;
use Getopt::Std;
use POSIX qw(setsid strftime :sys_wait_h);
use threads;
use threads::shared;
use File::Path qw(rmtree);

# Default lib dir for Pandora FMS RPM and DEB packages.
BEGIN { push @INC, '/usr/lib/perl5'; }

use PandoraFMS::Tools;
use PandoraFMS::DB;
use PandoraFMS::Core;
use PandoraFMS::Config;

use Data::Dumper;
$Data::Dumper::Sortkeys = 1;

# Pandora server configuration.
my %Conf;

# Command line options.
my %Opts;

# Run as a daemon.
my $DAEMON = 0;

# Timeout for the HA DB lock.
my $LOCK_TIMEOUT = 300;

# Avoid retry old processing orders.
my $First_Cleanup = 1;

# List of known HA DB hosts.
my @HA_DB_Hosts;

# Current master node.
my $DB_Host = '';

# PID file.
my $PID_FILE = '/var/run/pandora_ha.pid';

# Server service handler.
my $Pandora_Service;

# Restart the Pandora FMS Server.
my $Restart = 0;

# Controlled exit
my $Running = 0;

########################################################################
# Print the given message with a preceding timestamp.
########################################################################
sub log_message($$$;$) {
  my ($conf, $source, $message, $verbosity_level) = @_;

  my $level = $verbosity_level;
  $level = 5 unless defined($level);

  if ($source eq 'DEBUG' && !defined($ENV{'PANDORA_DEBUG'})) {
  	return;
  }

  if (ref($conf) eq "HASH") {
    logger($conf, 'HA (' . $source . ') ' . "$message", $level);
  }
  
  if ($source eq '') {
    print $message;
  }
  else {
    print strftime("%H:%M:%S", localtime()) . ' [' . $source . '] ' . "$message\n";
  }
}

########################################################################
# Run as a daemon in the background.
########################################################################
sub ha_daemonize($) {
  my ($pa_config) = @_;

  $PID_FILE = $pa_config->{'ha_pid_file'} if defined($pa_config->{'ha_pid_file'});

  open STDIN, "$DEVNULL" or die "Can't read $DEVNULL: $!";
  open STDOUT, ">>$DEVNULL" or die "Can't write to $DEVNULL: $!";
  open STDERR, ">>$DEVNULL" or die "Can't write to $DEVNULL: $!";
  chdir '/tmp' or die "Can't chdir to /tmp: $!";

  # Fork!
  defined(my $pid = fork) or die "Can't fork: $!";
  exit if ($pid);

  # Child inherits execution.
  setsid or die "Can't start a new session: $!";

  # Store PID of this process in file presented by config token
  if ($PID_FILE ne "") {
    if ( -e $PID_FILE && open (FILE, $PID_FILE)) {
      $pid = <FILE> + 0;
      close FILE;
      
      # Check if pandora_ha is running.
      die "[ERROR] pandora_ha is already running with pid: $pid.\n" if (kill (0, $pid));
    }

    umask 0022;
    open (FILE, '>', $PID_FILE) or die "[FATAL] $!";
    print FILE $$;
    close (FILE);
  }
}

########################################################################
# Check command line parameters.
########################################################################
sub ha_init_pandora($) {
  my $conf = shift;
  
  log_message($conf, '', "\nPandora FMS Daemon Watchdog " . $PandoraFMS::Tools::VERSION . " Copyright (c) Pandora FMS\n");
  
  getopts('dp:', \%Opts);

  # Run as a daemon.
  $DAEMON = 1 if (defined($Opts{'d'}));

  # PID file.
  $PID_FILE = $Opts{'p'} if (defined($Opts{'p'}));

  # Load config file from command line.
  help_screen () if ($#ARGV != 0);

  $conf->{'_pandora_path'} = $ARGV[0];

}

########################################################################
# Read external configuration file.
########################################################################
sub ha_load_pandora_conf($) {
  my $conf = shift;

  # Set some defaults.
  $conf->{"servername"} = `hostname`;
  chomp($conf->{"servername"});
  $conf->{"ha_file"} = '/etc/pandora/pandora_ha.bin' unless defined $conf->{"ha_file"};

  pandora_init($conf, 'Pandora HA');
  pandora_load_config ($conf);

  # Check conf tokens.
  foreach my $param ('dbuser', 'dbpass', 'dbname', 'dbhost', 'log_file') {
    die ("[ERROR] Bad config values. Make sure " . $conf->{'_pandora_path'} . " is a valid config file.\n\n") unless defined ($conf->{$param});
  }
  $conf->{'dbengine'} = 'mysql' unless defined ($conf->{'dbengine'});
  $conf->{'dbport'} = '3306' unless defined ($conf->{'dbport'});
  $conf->{'ha_interval'} = 10 unless defined ($conf->{'ha_interval'});
  $conf->{'ha_monitoring_interval'} = 60 unless defined ($conf->{'ha_monitoring_interval'});
  $conf->{'pandora_service_cmd'} = 'service pandora_server' unless defined($conf->{'pandora_service_cmd'});
  $conf->{'tentacle_service_cmd'} = 'service tentacle_serverd' unless defined ($conf->{'tentacle_service_cmd'});
  $conf->{'tentacle_service_watchdog'} = 1 unless defined ($conf->{'tentacle_service_watchdog'});
  $conf->{'made_service_cmd'} = 'service pandora_made' unless defined($conf->{'made_service_cmd'});
}

##############################################################################
# Print a help screen and exit.
##############################################################################
sub help_screen {
  log_message(undef, '', "Usage: $0 [options] <path to pandora_server.conf>\n\nOptions:\n\t-p <PID file> Write the PID of the process to the specified file.\n\t-d Run in the background.\n\n");
  exit 1;
}

##############################################################################
# Keep server running
##############################################################################
sub ha_keep_pandora_running($$) {
  my ($conf, $dbh) = @_;
  my $OSNAME = $^O;
  my $control_command;
  $Pandora_Service = $conf->{'pandora_service_cmd'};

  # A restart was requested.
  if ($Restart == 1) {
    $Restart = 0;
    
    log_message($conf, 'LOG', 'Restarting Pandora service');
    $control_command = $^O eq "freebsd" ? "restart_server" : 'restart-server';
    `$Pandora_Service $control_command $ENV{'PANDORA_DBHOST'} 2>/dev/null`;
    return;
  }

  # Check if all servers are running
  # Restart if crashed or keep interval is over.
  my $component_last_contact = get_db_value(
    $dbh,
    'SELECT count(*) AS "delayed"
     FROM  tserver
     WHERE ((status = -1) OR ( (unix_timestamp() - unix_timestamp(keepalive)) > (server_keepalive+1) AND status != 0 ))
       AND server_type NOT IN (?, ?) AND name = ?',
    PandoraFMS::Tools::SATELLITESERVER,
    PandoraFMS::Tools::MFSERVER,
    $conf->{'servername'}
  );

  my $nservers = get_db_value ($dbh, 'SELECT count(*) FROM tserver where name = ?', $conf->{'servername'});

  # Check if service is running
  $control_command = "status-server";
  if ($OSNAME eq "freebsd") {
    $control_command = "status_server";
  }
  my $pid =  `$Pandora_Service $control_command | grep -v /conf.d/ | awk '{print \$NF*1}' | tr -d '\.'`;

  if ( ($pid > 0) && ($component_last_contact > 0)) {
    # service running but not all components
    log_message($conf, 'LOG', 'Pandora service running but not all components.');
    print ">> service running but delayed...\n";
    $control_command = "restart-server";
    if ($OSNAME eq "freebsd") {
      $control_command = "restart_server";
    }
    `$Pandora_Service $control_command 2>/dev/null`;
  } elsif ($pid == 0) {
    # service not running
    log_message($conf, 'LOG', 'Pandora service not running.');
    print ">> service not running...\n";
    $control_command = "start-server";
    if ($OSNAME eq "freebsd") {
      $control_command = "start_server";
    }
    `$Pandora_Service $control_command 2>/dev/null`;
  } elsif ($pid > 0
    && $nservers == 0
  ) {
    my @server_list = get_enabled_servers($conf);
    my $nservers = $#server_list;
    # Process running but no servers active, restart.
    # Try to restart pandora_server if no servers are found.
    # Do not restart if is a configuration issue.
    log_message($conf, 'LOG', 'Pandora service running without servers ['.$nservers.'].');
    if ($nservers >= 0) {
      log_message($conf, 'LOG', 'Restarting Pandora service...');
      $control_command = "restart-server";
      if ($OSNAME eq "freebsd") {
        $control_command = "restart_server";
      }
      `$Pandora_Service $control_command 2>/dev/null`;
    }
  }
}

##############################################################################
# Keep MADE running
##############################################################################
sub ha_keep_made_running($$) {
  my ($conf, $dbh) = @_;

  # Is MADE enabled?
  return unless (defined($conf->{'madeserver'}) && $conf->{'madeserver'} == 1);

  # Is MADE installed?
  `$conf->{'made_service_cmd'} status 2>/dev/null`;
  if (($? >> 8) == 4) {
    log_message($conf, 'LOG', "Pandora FMS MADE is not installed.");
    return;
  }

  # Try to get the PID of the service.
  my $pid = `systemctl show --property MainPID pandora_made | cut -d= -f2`;
  chomp($pid);
  if ($pid eq "0") {
    log_message($conf, 'LOG', 'MADE service not running.');
    `$conf->{'made_service_cmd'} start 2>/dev/null`;
  }
}

##############################################################################
# Keep the Tentacle server running
##############################################################################
sub ha_keep_tentacle_running($$) {
  my ($conf, $dbh) = @_;

  return unless ($conf->{'tentacle_service_watchdog'} == 1);

  # Try to get the PID of the service.
  my $pid = `$conf->{'tentacle_service_cmd'} status | awk '{print \$NF*1}' | tr -d '\.'`;

  # Not running.
  if ($pid == 0) {
    log_message($conf, 'LOG', 'Tentacle service not running.');
    print ">> service not running...\n";
    `$conf->{'tentacle_service_cmd'} start 2>/dev/null`;
  }
}

###############################################################################
# Update pandora services.
###############################################################################
sub ha_update_server($$) {
  my ($config, $dbh) = @_;
  my $OSNAME = $^O;

  my $repoServer = pandora_get_tconfig_token(
    $dbh, 'remote_config',  '/var/spool/pandora/data_in'
  );
  $repoServer .= '/updates/server/';

  my $lockFile = $repoServer.'/'.$config->{'servername'}.'.installed';
  my $workDir = $config->{"temporal"}.'/server_update/';
  my $versionFile = $repoServer.'version.txt';
  return if (-e $lockFile) || (!-e $versionFile);

  log_message($config, 'LOG', 'Detected server update: '.`cat "$versionFile"`);

  if(!-e "$workDir" && !mkdir ($workDir)) {
    log_message($config, 'ERROR', 'Server update failed: '.$!);
    return;
  }

  my $r = `cd "$workDir/" && tar xzf "$repoServer/pandorafms_server.tar.gz" 2>&1`;
  if ($? ne 0) {
    log_message($config, 'ERROR', 'Failed to uncompress file: '.$r);
    return;
  }
  
  $r = `cd "$workDir/pandora_server/" && ./pandora_server_installer --install 2>&1 >/dev/null`;
  if ($? ne 0) {
    log_message($config, 'ERROR', 'Failed to install server update: '.$r);
    return;
  } else {
		log_message($config, 'LOG', 'Server update '.`cat "$versionFile"`.' installed');
  }

  # Cleanup
  rmtree($workDir);

  # Restart service
  my $control_command = "restart-server";
  if ($OSNAME eq "freebsd") {
    $control_command = "restart_server";
  }
  `$config->{'pandora_service_cmd'} $control_command 2>/dev/null`;
  `touch "$lockFile"`;

  # After apply update, permission over files are changed, allow group to 
  # modify/delete files.
  `chmod 770 "$repoServer"`;
  `chmod 770 "$repoServer/../"`;
  `chmod 660 "$repoServer"/*`;

}

################################################################################
# Dump the list of known databases to disk.
################################################################################
sub ha_dump_databases($) {
    my ($conf) = @_;

	# HA is not configured.
    return unless defined($conf->{'ha_hosts'});

    eval {
        open(my $fh, '>', $conf->{'ha_hosts_file'});
        print $fh $DB_Host; # The console only needs the master DB.
        close($fh);
        log_message($conf, 'DEBUG', "Dumped master database $DB_Host to disk");
    };
    log_message($conf, 'WARNING', $@) if ($@);
}

################################################################################
# Read the list of known databases from disk.
################################################################################
sub ha_load_databases($) {
    my ($conf) = @_;

	# HA is not configured.
    return unless defined($conf->{'ha_hosts'});

    @HA_DB_Hosts = grep { !/^#/ } map { s/^\s+|\s+$//g; $_; } split(/,/, $conf->{'ha_hosts'});
    log_message($conf, 'DEBUG', "Loaded databases from disk (@HA_DB_Hosts)");  
}

###############################################################################
# Connect to ha database, falling back to direct connection to db.
###############################################################################
sub ha_database_connect($) {
  my $conf = shift;

  my $dbh = enterprise_hook('ha_connect', [$conf]);

  if (!defined($dbh)) {
    $dbh = db_connect ('mysql', $conf->{'dbname'}, $conf->{'dbhost'}, $conf->{'dbport'}, $conf->{'dbuser'}, $conf->{'dbpass'});
  }

  return $dbh;
}

###############################################################################
# Connect to ha database, falling back to direct connection to db.
###############################################################################
sub ha_database_connect_pandora($) {
	my $conf = shift;
	my $dbhost = $conf->{'dbhost'};

	# Load the list of HA databases.
	ha_load_databases($conf);
  
	# Select a new master database.
	my ($dbh, $utimestamp, $max_utimestamp) = (undef, undef, -1);

  my @disabled_nodes = get_disabled_nodes($conf);

  # If there are disabled nodes ignore them from the HA_DB_Hosts.
  if(scalar @disabled_nodes ne 0){
    @HA_DB_Hosts = grep { my $item = $_; !grep { $_ eq $item } @disabled_nodes } @HA_DB_Hosts;

    my $data = join(",", @disabled_nodes);
    log_message($conf, 'LOG', "Ignoring disabled hosts: " . $data);
  }

	foreach my $ha_dbhost (@HA_DB_Hosts) {

		# Retry each database ha_connect_retries times.
		for (my $i = 0; $i < $conf->{'ha_connect_retries'}; $i++) {
			eval {
				log_message($conf, 'DEBUG', "Trying database $ha_dbhost...");
				$dbh= db_connect('mysql',
								 $conf->{'dbname'},
								 $ha_dbhost,
								 $conf->{'dbport'},
								 $conf->{'ha_dbuser'},
								 $conf->{'ha_dbpass'});
				log_message($conf, 'DEBUG', "Connected to database $ha_dbhost");
			};
			log_message($conf, 'WARNING', $@) if ($@);

			# Connection successful.
			last if defined($dbh);

			# Wait for the next retry.
			sleep($conf->{'ha_connect_delay'});
		}

		# No luck. Try the next database.
		next unless defined($dbh);

		eval {
		   # Get the most recent utimestamp from the database.
		   $utimestamp = get_db_value($dbh, 'SELECT UNIX_TIMESTAMP(MAX(keepalive)) FROM tserver');
		   db_disconnect($dbh);

		   # Did we find a more recent database?
		   $utimestamp = 0 unless defined($utimestamp);
		   if ($utimestamp > $max_utimestamp) {
			   $dbhost = $ha_dbhost;
			   $max_utimestamp = $utimestamp;
		   }
		};
		log_message($conf, 'WARNING', $@) if ($@);
	}

	# Return a connection to the selected master.
	eval {
		log_message($conf, 'DEBUG', "Connecting to selected master $dbhost...");
		$dbh = db_connect('mysql',
						  $conf->{'dbname'},
						  $dbhost,
						  $conf->{'dbport'},
						  $conf->{'ha_dbuser'},
						  $conf->{'ha_dbpass'});

		# Restart if a new master was selected.
		if ($dbhost ne $DB_Host) {
			log_message($conf, 'DEBUG', "Setting master database to $dbhost");
			$DB_Host = $dbhost;
			$Restart = 1;
		}
	};
	log_message($conf, 'WARNING', $@) if ($@);

	# Save the list of HA databases.
	ha_dump_databases($conf);

	return $dbh;
}

###############################################################################
# Return 1 if the given DB is read-only, 0 otherwise.
###############################################################################
sub ha_read_only($$) {
    my ($conf, $dbh) = @_;

    my $read_only = get_db_value($dbh, 'SELECT @@global.read_only');
    return 1 if (defined($read_only) && $read_only == 1);

    return 0;
}

###############################################################################
# Restart the Pandora FMS Server.
###############################################################################
sub ha_restart_pandora($) {
    my ($config) = @_;

    my $control_command = $^O eq 'freebsd' ?
                          'restart_server' :
                          'restart-server';
    `$config->{'pandora_service_cmd'} $control_command 2>/dev/null`;
}

###############################################################################
# Get ip of the disabled nodes.
###############################################################################
sub get_disabled_nodes($) {
  my ($conf) = @_;
  
  my $dbh = db_connect('mysql',
						  $conf->{'dbname'},
						  $conf->{'dbhost'},
						  $conf->{'dbport'},
						  $conf->{'ha_dbuser'},
						  $conf->{'ha_dbpass'});

  my $disabled_nodes = get_db_value($dbh, "SELECT value FROM tconfig WHERE token = 'ha_disabled_nodes'");
  
  if(!defined($disabled_nodes) || $disabled_nodes eq ""){
    $disabled_nodes = ',';
  }

  my @disabled_nodes = split(',', $disabled_nodes);

  if(scalar @disabled_nodes ne 0){
    $disabled_nodes = join(",", @disabled_nodes);
    @disabled_nodes = get_db_rows($dbh, "SELECT host FROM tdatabase WHERE id IN ($disabled_nodes)");
    @disabled_nodes = map { $_->{host} } @disabled_nodes;
  }

  return @disabled_nodes;
}

###############################################################################
# Main (Pacemaker)
###############################################################################
sub ha_main_pacemaker($) {
  my ($conf) = @_;

  # Set the PID file.
  $conf->{'PID'} = $PID_FILE;

  # Log to a separate file if needed.
  $conf->{'log_file'} = $conf->{'ha_log_file'} if defined ($conf->{'ha_log_file'});

  $Running = 1;

  ha_daemonize($conf) if ($DAEMON == 1);

  while ($Running) {
    eval {
      eval { 
        local $SIG{__DIE__};
        # Load enterprise components.
        enterprise_load($conf, 1);

        # Register Enterprise logger
        enterprise_hook('pandoraha_logger', [\&log_message]);
        log_message($conf, 'LOG', 'Enterprise capabilities loaded');

      };
      if ($@) {
        # No enterprise capabilities.
        log_message($conf, 'LOG', 'No enterprise capabilities');
      }

      # Start the Pandora FMS server if needed.
      log_message($conf, 'LOG', 'Checking the pandora_server service.');

      # Connect to a DB.
      my $dbh = ha_database_connect($conf);

      if ($First_Cleanup == 1) {
        log_message($conf, 'LOG', 'Cleaning previous unfinished actions');
        enterprise_hook('pandoraha_cleanup_states', [$conf, $dbh]);
        $First_Cleanup = 0;
      }

      # Check if there are updates pending.
      ha_update_server($conf, $dbh);

      # Keep pandora running
      ha_keep_pandora_running($conf, $dbh);

      # Keep Tentacle running
      ha_keep_tentacle_running($conf, $dbh);
    
      # Keep MADE running
      ha_keep_made_running($conf, $dbh);

      # Are we the master?
      pandora_set_master($conf, $dbh);
      if (!pandora_is_master($conf)) {
        log_message($conf, 'LOG', $conf->{'servername'} . ' is not the current master. Skipping DB-HA actions and monitoring.');
        # Exit current eval.
        return;
      }

      # Synchronize database.
      enterprise_hook('pandoraha_sync_node', [$conf, $dbh]);

      # Monitoring.
      enterprise_hook('pandoraha_monitoring', [$conf, $dbh]);
    
      # Pending actions.
      enterprise_hook('pandoraha_process_queue', [$conf, $dbh, $First_Cleanup]);
    
      # Cleanup and exit
      db_disconnect ($dbh);
    };
    log_message($conf, 'WARNING', $@) if ($@);

    log_message($conf, 'LOG', "Sleep.");
    sleep($conf->{'ha_interval'});
  }
}

###############################################################################
# Main (Pandora)
###############################################################################
sub ha_main_pandora($) {
  my ($conf) = @_;

  # Set the PID file.
  $conf->{'PID'} = $PID_FILE;

  # Log to a separate file if needed.
  $conf->{'log_file'} = $conf->{'ha_log_file'} if defined ($conf->{'ha_log_file'});

  # Run in the background.
  ha_daemonize($conf) if ($DAEMON == 1);

  # Main loop.
  $Running = 1;
  while ($Running) {
    my $dbh = undef;
    eval {

      # Connect to a DB.
      log_message($conf, 'LOG', "Looking for databases");
      $dbh = ha_database_connect_pandora($conf);
      if (!defined($dbh)) {
        log_message($conf, 'LOG', 'No databases available');
        return;
      }

      # Make the DB host available to the Pandora FMS Server.
      $ENV{'PANDORA_DBHOST'} = $DB_Host;

      # Needed for the Enterprise module.
      $conf->{'dbhost'} = $DB_Host;

      # Enterprise capabilities need access to the DB.
      eval { 
        local $SIG{__DIE__};
        # Load enterprise components.
        enterprise_load($conf, 1);

        # Register Enterprise logger
        enterprise_hook('pandoraha_logger', [\&log_message]);
        log_message($conf, 'LOG', 'Enterprise capabilities loaded');

      };
      log_message($conf, 'LOG', "No enterprise capabilities: $@") if ($@);

      log_message($conf, 'LOG', "Connected to database $DB_Host");
      enterprise_hook('pandoraha_stop_slave', [$conf, $dbh]);

      if (ha_read_only($conf, $dbh) == 1) {
        log_message($conf, 'LOG', "The database is read-only.");
        return;
      }

      # Check if there are updates pending.
      ha_update_server($conf, $dbh);

      # Keep pandora running
      ha_keep_pandora_running($conf, $dbh);

      # Keep Tentacle running
      ha_keep_tentacle_running($conf, $dbh);
    
      # Keep MADE running
      ha_keep_made_running($conf, $dbh);

      # Are we the master?
      pandora_set_master($conf, $dbh);
      if (!pandora_is_master($conf)) {
        log_message($conf, 'LOG', $conf->{'servername'} . ' is not the current master. Skipping DB-HA actions and monitoring.');
        return;
      }

      # Check the status of slave databases.
      enterprise_hook('pandoraha_check_slaves', [$conf, $dbh, $DB_Host, \@HA_DB_Hosts]);

      # Update the status of HA databases.
      enterprise_hook('pandoraha_update_dbs', [$conf, $dbh, $DB_Host, \@HA_DB_Hosts]);

      # Execute resync actions.
      enterprise_hook('pandoraha_resync_dbs', [$conf, $dbh, $DB_Host, \@HA_DB_Hosts]);

      # Synchronize nodes.
      enterprise_hook('pandoraha_sync_node', [$conf, $dbh]);
    };
    log_message($conf, 'WARNING', $@) if ($@);

    # Cleanup.
    eval {
      db_disconnect($dbh) if defined($dbh);
    };

    # Go to sleep.
    log_message($conf, 'LOG', "Sleep.");
    sleep($conf->{'ha_interval'});
  }
}

################################################################################
# Stop pandora server
################################################################################
sub stop {
  my $OSNAME = $^O;

  if ($Running == 1) {
    $Running = 0;
    # cleanup and stop pandora_server
    print ">> stopping server...\n";
    my $control_command = "stop-server";
    if ($OSNAME eq "freebsd") {
      $control_command = "stop_server";
    }
    `$Pandora_Service $control_command 2>/dev/null`;
  }
}

################################################################################
# END block.
################################################################################
END {
  stop();
}

$SIG{INT} = \&stop;
$SIG{TERM} = \&stop;

# Init
ha_init_pandora(\%Conf);

# Read config file
ha_load_pandora_conf (\%Conf);

# Main
if (defined($Conf{'ha_mode'}) && lc($Conf{'ha_mode'}) eq 'pandora') {
	ha_main_pandora(\%Conf);
} else {
	ha_main_pacemaker(\%Conf);
}

exit 0;
