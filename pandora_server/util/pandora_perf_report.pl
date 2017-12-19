#!/usr/bin/perl
################################################################################
# Pandora FMS Performance Report Tool
# Copyright (c) 2017 Artica Soluciones Tecnologicas S.L.
################################################################################
use strict;
use warnings;

use POSIX qw (strftime floor);
use Sys::Hostname;
use Time::HiRes  qw(gettimeofday tv_interval);

use lib '/usr/lib/perl5';

# Pandora Modules.
use PandoraFMS::Config;
use PandoraFMS::Tools;
use PandoraFMS::DataServer;
use PandoraFMS::DB;
use PandoraFMS::Core;
use Thread::Semaphore;

our $VERSION = '1.0';

my $STRESS_AGENT_NAME = '__PANDORA_STRESS_AGENT__'; # Name of the agent used for XML stress tests.
my $STRESS_AGENT_MODULES = 50;  # Number of modules per agent.
my $STRESS_AGENT_OPS = 50;      # Number of XML iterations (one XML per iteration).
my $STRESS_AGENT_XML = {        # Hash representing data coming from an XML data file.
          'os_name' => 'Other',
          'version' => '7.0NG',
          'interval' => '300',
          'description' => 'Pandora FMS XML stress agent',
          'os_version' => '',
          'agent_name' => $STRESS_AGENT_NAME,
          'timestamp' => 0,
          'agent_alias' => $STRESS_AGENT_NAME,
          'module' => []
};
my $DUMP_CNF_ONLY = 0;
my $HELP=<<EO_HELP;

Pandora FMS tool for MySQL testing and optimization.

  Usage $0 /etc/pandora/pandora_server.conf [options]

  where options could be:
  -g      Generate optimized my.cnf (dumped to stdout)

EO_HELP

my $STRESS_CPU_OPS = 100000; # Number of CPU iterations.

my $STRESS_DB_OPS = 5;     # Number of DB iterations.
my $STRESS_DB_BLOCK = 1;    # Number of rows read per SELECT statement.

################################################################################
# Print a generic ratio calculated as $num/$den.
################################################################################
sub ratio {
	my ($num, $den) = @_;

	return $den == 0 ? "Inf" : sprintf("%10.2f", $num/$den);
}

################################################################################
# Print a generic numeric metric.
################################################################################
sub metric {
	my ($metric) = @_;

	# Integer.
	if ($metric =~ /^-?\d+\z/) {
		return sprintf("%10d", $metric);
	}
	# Float.
	else {
		return sprintf("%10.2f", $metric);
	}
}

################################################################################
# Print log messages.
################################################################################
sub print_log {
	my ($sec, $msg) = @_;

	print(strftime ("%Y-%m-%d %H:%M:%S", localtime()) . " [$sec] $msg\n");
}

################################################################################
# Stress the CPU.
################################################################################
sub stress_cpu {
	my $t0 = [gettimeofday];

	print_log("CPU", "start");
	for (my $i = 1; $i <= $STRESS_CPU_OPS; $i++) {
		my $sqrt = sqrt($i);
		my $exp = exp($i);
		my $log = log($i);
		my $sin = sin($i);
		my $cos = cos($i);
	}
	my $elapsed = tv_interval($t0) * 1000; # Convert from s to ms.

	print_log("CPU", "stop");
	return ratio($STRESS_CPU_OPS, $elapsed);
}

################################################################################
# Stress the DB.
################################################################################
sub stress_db {
	my ($dbh) = @_;

	print_log("DB INSERT", "start");
	my $t0 = [gettimeofday];
	for (my $i = 1; $i <= $STRESS_DB_OPS; $i++) {
		db_do($dbh, "INSERT INTO tagente_datos (`id_agente_modulo`, `datos`, `utimestamp`) VALUES (0, ?, ?)", $STRESS_DB_OPS - $i, $i);
	}
	my $insert_score = ratio($STRESS_DB_OPS, tv_interval($t0));
	print_log("DB INSERT", "stop");

	print_log("DB UPDATE", "start");
	$t0 = [gettimeofday];
	for (my $i = 1; $i <= $STRESS_DB_OPS; $i++) {
		db_do($dbh, "UPDATE tagente_datos SET datos=? WHERE id_agente_modulo = 0 AND utimestamp = ?", $i, $i);
	}
	my $update_score = ratio($STRESS_DB_OPS, tv_interval($t0));
	print_log("DB UPDATE", "stop");

	print_log("DB SELECT", "start");
	$t0 = [gettimeofday];
	for (my $i = 1; $i <= $STRESS_DB_OPS; $i++) {
		db_do($dbh, "SELECT SQL_NO_CACHE datos FROM tagente_datos WHERE id_agente_modulo = 0 AND utimestamp > ? AND utimestamp < ?", $i, $i + $STRESS_DB_BLOCK);
	}
	my $select_score = ratio($STRESS_DB_OPS, tv_interval($t0));
	print_log("DB SELECT", "stop");

	print_log("DB DELETE", "start");
	$t0 = [gettimeofday];
	for (my $i = 1; $i <= $STRESS_DB_OPS; $i++) {
		db_do($dbh, "DELETE FROM tagente_datos WHERE id_agente_modulo = 0 AND utimestamp = ?", $i);
	}
	my $delete_score = ratio($STRESS_DB_OPS, tv_interval($t0));
	print_log("DB DELETE", "stop");

	return { 'insert_score' => $insert_score, 'update_score' => $update_score, 'select_score' => $select_score, 'delete_score' => $delete_score };
}

################################################################################
# Stress a single agent.
################################################################################
sub stress_agent {
	my ($dbh, $conf) = @_;

	# Initialize some lexical variables needed by the Data Server.
	PandoraFMS::DataServer->new($conf);

	# Create the stress agent.
	my $agent_id = get_agent_id($dbh, $STRESS_AGENT_NAME);
	if ($agent_id == -1) {
		print_log("XML", "creating stress agent");
		$agent_id = pandora_create_agent ($conf, '', safe_input($STRESS_AGENT_NAME), '127.0.0.1', 10,  0, 10, 'Pandora FMS XML stress agent', 300, $dbh);
	} else {
		print_log("XML", "stress agent already exists");
	}

	if (!defined($agent_id) || $agent_id == -1) {
		die("Error creating stress agent.\n\n");
	}

	# Make sure the timestamp from the XML file (as opposed to the timestamp in the XML data) is never used.
	$conf->{'use_xml_timestamp'} = 0;

	# Pre-generate timestamps.
	my @timestamps;
	for (my $i = 0, my $offset = 0; $i < $STRESS_AGENT_OPS; $i++, $offset += 172800) {
		$timestamps[$i] = strftime ("%Y-%m-%d %H:%M:%S", localtime($offset));
	}

	print_log("XML", "initializing $STRESS_AGENT_MODULES modules");
	for (my $i = 0; $i < $STRESS_AGENT_MODULES; $i++) {
		push(@{$STRESS_AGENT_XML->{'module'}},
		     {
				'name' => ["Module $i"],
				'data' => [$i],
				'type' => ['generic_data']
             });
	}

	print_log("XML", "start");
	my $t0 = [gettimeofday];
	for (my $i = 0; $i < $STRESS_AGENT_OPS; $i++) {
		$STRESS_AGENT_XML->{'timestamp'} = $timestamps[$i];
		PandoraFMS::DataServer::process_xml_data ($conf, $0, $STRESS_AGENT_XML, 0, $dbh);
	}
	my $elapsed = tv_interval($t0);
	my $agent_score = ratio($STRESS_AGENT_OPS, $elapsed);
	my $module_score = ratio($STRESS_AGENT_OPS * $STRESS_AGENT_MODULES, $elapsed);
	print_log("XML", "stop");

	print_log("XML", "deleting stress agent");
	pandora_delete_agent($dbh, $agent_id);
	
	return { 'agent_score' => $agent_score, 'module_score'  => $module_score };
}

################################################################################
# Compute table stats.
################################################################################
sub table_stats {
	my ($dbh, $conf) = @_;
	my $stats = {
		tagent_access => 'N/A',
		tagente => 'N/A',
		tagente_datos => 'N/A',
		tagente_datos_string => 'N/A',
		tagente => 'N/A',
		tevento => 'N/A',
		tsesion => 'N/A',
	};

	my @rows = get_db_rows($dbh, "SELECT TABLE_NAME, TABLE_ROWS
	                              FROM information_schema.TABLES
							      WHERE TABLE_SCHEMA=?
							      AND TABLE_NAME IN (?, ?, ?, ?, ?, ?, ?)",
							      $conf->{'dbname'},
							      'tagent_access',
							      'tagente',
							      'tagente_datos',
							      'tagente_datos_string',
							      'tagente_modulo',
							      'tevento',
								  'tsesion',
							);

	foreach my $row (@rows) {
		$stats->{$row->{'TABLE_NAME'}} = metric($row->{'TABLE_ROWS'});
	}

	return $stats;
}

sub generate_optimized_my_cnf {
	my $pool_size=`cat /proc/meminfo | grep -i total | head -1 | awk '{print \$(NF-1)*0.4/1024}' | sed 's/\\..*\$/M/' `;
	chomp($pool_size);

	my $out = '';

	$out .= "# Percona Server template configuration\n";
	$out .= "\n";
	$out .= "[mysqld]\n";
	$out .= "datadir=/var/lib/mysql\n";
	$out .= "socket=/var/lib/mysql/mysql.sock\n";
	$out .= "\n";
	$out .= "# Disabling symbolic-links is recommended to prevent assorted security risks\n";
	$out .= "symbolic-links=0\n";
	$out .= "\n";
	$out .= "# Recommended in standard MySQL setup\n";
	$out .= "sql_mode=\"\"\n";
	$out .= "max_allowed_packet=64M\n";
	$out .= "max_connections=100\n";
	$out .= "\n";
	$out .= "#InnoDB\n";
	$out .= "innodb_file_per_table\n";
	$out .= "innodb_buffer_pool_size=" . $pool_size . "\n";
	$out .= "innodb_additional_mem_pool_size=32M\n";
	$out .= "innodb_lock_wait_timeout=120\n";
	$out .= "innodb_flush_log_at_trx_commit=0\n";
	$out .= "innodb_flush_method=O_DIRECT\n";
	$out .= "innodb_log_file_size=32M\n";
	$out .= "innodb_log_buffer_size=128M\n";
	$out .= "#innodb_io_capacity=150\n";
	$out .= "\n";
	$out .= "#Threading\n";
	$out .= "thread_stack=256K\n";
	$out .= "thread_cache_size=16\n";
	$out .= "\n";
	$out .= "#Buffers\n";
	$out .= "sort_buffer_size=8M\n";
	$out .= "join_buffer_size=8M\n";
	$out .= "key_buffer_size=32M\n";
	$out .= "read_buffer_size=128K\n";
	$out .= "read_rnd_buffer_size=128K\n";
	$out .= "\n";
	$out .= "#Cache\n";
	$out .= "query_cache_type=1\n";
	$out .= "query_cache_size=8M\n";
	$out .= "query_cache_limit=32M\n";
	$out .= "\n";
	$out .= "#Default values\n";
	$out .= "tmp_table_size=64M\n";
	$out .= "bind_address=0.0.0.0\n";
	$out .= "\n";
	$out .= "\n";
	$out .= "[mysqld_safe]\n";
	$out .= "log-error=/var/log/mysqld.log\n";
	$out .= "pid-file=/var/run/mysqld/mysqld.pid\n";
	$out .= "\n";

	return $out;

}

################################################################################
# Add recommendations based on the given table stats.
################################################################################
sub table_comments {
	my ($stats) = @_;
	my $comments = {
		tagent_access => 'OK',
		tagente => 'OK',
		tagente_datos => 'OK',
		tagente_datos_string => 'OK',
		tagente_modulo => 'OK',
		tagente => 'OK',
		tevento => 'OK',
		tsesion => 'OK',
	};

	if ($stats->{'tagent_access'} > $stats->{'tagente'} * 24 * 250) {
		$comments->{'tagent_access'} = 'CRITICAL: Table too big. Please contact our support team at: support@artica.es';
	} elsif ($stats->{'tagent_access'} > $stats->{'tagente'} * 24 * 100) {
		$comments->{'tagent_access'} = 'WARNING: Table too big. Please contact our support team at: support@artica.es';
	}

	if ($stats->{'tagente_datos'} > 5000000) {
		$comments->{'tagente_datos'} = 'CRITICAL: Table too big. Please use a history database or decrease the purge period.';
	} elsif ($stats->{'tagente_datos'} > 1000000) {
		$comments->{'tagente_datos'} = 'WARNING: Table too big. Please use a history database or decrease the purge period.';
	}

	if ($stats->{'tagente_modulo'} > 500000) {
		$comments->{'tsesion'} = 'CRITICAL: Table too big. Please contact our support team at: support@artica.es';
	} elsif ($stats->{'tagente_modulo'} > 350000) {
		$comments->{'tsesion'} = 'WARNING Table too big. Please contact our support team at: support@artica.es';
	}
	
	if ($stats->{'tevento'} > 50000) {
		$comments->{'tevento'} = 'CRITICAL: Table too big. Please use a history database or decrease the purge period.';
	} elsif ($stats->{'tevento'} > 25000) {
		$comments->{'tevento'} = 'WARNING: Table too big. Please use a history database or decrease the purge period.';
	}

	if ($stats->{'tsesion'} > 50000) {
		$comments->{'tsesion'} = 'CRITICAL: Table too big. Please contact our support team at: support@artica.es';
	} elsif ($stats->{'tsesion'} > 15000) {
		$comments->{'tsesion'} = 'WARNING: Table too big. Please contact our support team at: support@artica.es';
	}

	return $comments;
}

############################################################################
# Close STDOUT, avoid output
############################################################################
my $OLD_STDOUT;
sub close_stdout {
	open $OLD_STDOUT, ">&STDOUT";
	close STDOUT;
	open STDOUT, '>', '/dev/null';
}

############################################################################
# Restore STDOUT, recover output
############################################################################
sub restore_stdout {
	close STDOUT;
	open STDOUT, '>&', $OLD_STDOUT;
}

################################################################################
#
# Main.
#
################################################################################
my %conf;


if ($#ARGV < 0) {
	print $HELP;
	exit 0;
}

if ((defined($ARGV[1])) && ($ARGV[1] =~ /-g/i)) {
	$DUMP_CNF_ONLY = 1;
}


if ($DUMP_CNF_ONLY == 1) {

	print generate_optimized_my_cnf();

	exit 0;
}


# close STDOUT
close_stdout();

# Init Pandora FMS libs
pandora_init(\%conf,"Pandora FMS Performance Report Tool");
pandora_load_config(\%conf);

# close STDOUT
restore_stdout();

# Connect to the DB.
my $dbh = db_connect($conf{'dbengine'}, $conf{'dbname'}, $conf{'dbhost'}, $conf{'dbport'}, $conf{'dbuser'}, $conf{'dbpass'});

# Do not show server messages when running the stress tests.
$conf{'verbosity'} = 0;

$conf{'daemon'} = 0;

# CPU score.
my $cpu_score = stress_cpu();

# DB Scores.
my $db_scores = stress_db($dbh);

# Table stats.
my $table_stats = table_stats($dbh, \%conf);

# Add recommendations.
my $table_comments = table_comments($table_stats);

# Agent and module stats.
my $agent_scores = stress_agent($dbh, \%conf);

db_disconnect($dbh);

# Print the report.
my $tstamp = strftime("%Y-%m-%d %H:%M:%S", localtime());
my $host = hostname;
my $cpu = `cat /proc/cpuinfo | grep "model name" | head -1 | cut -d: -f2 | tr -d ' '`;
chomp($cpu);
my $cores = `cat /proc/cpuinfo | grep "model name" | wc -l`;
chomp($cores);
my $mem = `cat /proc/meminfo | grep "MemTotal" | cut -d: -f2 | tr -d ' '`;
chomp($mem);

my $max_agents  = floor ($agent_scores->{'agent_score'} * 300);
my $max_modules = floor ($agent_scores->{'module_score'} * 300);

print <<__EOF
-------------------------------------------------------------------------------

Pandora FMS Performance Report Tool v$VERSION
Report generated at: $tstamp

Host:   $host
CPU:    $cpu
Cores:  $cores
Memory: $mem

Metric                              Value             Reference value for small/medium/large systems (*)
------                              -----             --------------------------------------------------

CPU ops/ms                     $cpu_score             (1624.84/2347.42/3102.42)
DB INSERT/s                    $db_scores->{'insert_score'}             (2884.42/7026.07/8728.54)
DB UPDATE/s                    $db_scores->{'update_score'}             (2820.54/6954.10/8580.51)
DB SELECT/s                    $db_scores->{'select_score'}             (4632.42/7194.24/10051.32)
DB DELETE/s                    $db_scores->{'delete_score'}             (3192.37/6657.79/8933.77)

(*) small ~ 250 agents | medium ~ 1000 agents | large > 5000 agents


Database Analisys               Row count             Comments & recomendations
-----------------               ---------             -------------------------
Agent table                    $table_stats->{'tagente'}             $table_comments->{'tagente'}
Module table                   $table_stats->{'tagente_modulo'}             $table_comments->{'tagente_modulo'}
Data table                     $table_stats->{'tagente_datos'}             $table_comments->{'tagente_datos'}
String data table              $table_stats->{'tagente_datos_string'}             $table_comments->{'tagente_datos_string'}
Event table                    $table_stats->{'tevento'}             $table_comments->{'tevento'}
Access stats                   $table_stats->{'tagent_access'}             $table_comments->{'tagent_access'}
Audit information              $table_stats->{'tsesion'}             $table_comments->{'tsesion'}


Agent Data Processing (**)
--------------------------

Agents per second              $agent_scores->{'agent_score'}
Modules per second             $agent_scores->{'module_score'}


Max usage recommended (**)(***)
-------------------------------

Max agents                     $max_agents
Max modules                    $max_modules

(**) Single thread worst case scenario. Not representative of the real
     performance of a multi-threaded Pandora FMS Data Server.

(***) Taking a base interval 300s (default).

__EOF
