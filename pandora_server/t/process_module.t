use strict;
use warnings;
use Test::More;
use Scope::Guard;
use File::Basename;
use Data::Dumper;

use PandoraFMS::Core;
use PandoraFMS::Config;
use PandoraFMS::DB;

no lib '/usr/lib/perl5'; # disable @INC for system perl, because travis uses http://perlbrew.pl/.

my $conf;
my $dbh;
my $agent;
my $module;
my $module_with_ff;

my $ok = {data => 1};
my $ng = {data => 0};

BEGIN {
	diag "startup\n";
	$conf = {
		quiet => 0, verbosity => 1, daemon => 0, PID => "",
		pandora_path => dirname(__FILE__) . "/../conf/pandora_server.conf.new",
	};
	pandora_load_config($conf);
	$dbh = db_connect (
		'mysql', $conf->{'dbname'}, $conf->{'dbhost'},
		$conf->{'dbport'}, $conf->{'dbuser'}, $conf->{'dbpass'}
	);
	my $agent_id = pandora_create_agent($conf, $conf->{servername}, "test", "", 10, 0, 1, '', 300, $dbh);
	$agent = get_db_single_row ($dbh, 'SELECT * FROM tagente WHERE tagente.id_agente = ?', $agent_id);
}

END {
	diag "shutdown\n";
	pandora_delete_agent($dbh, $agent->{id_agente}, $conf);
}

sub teardown {
	diag "teardown\n";
	pandora_delete_module ($dbh, $module->{id_agente_modulo}, $conf);
	pandora_delete_module ($dbh, $module_with_ff->{id_agente_modulo}, $conf);
}

sub setup {
	diag "setup\n";
	my $module_id = pandora_create_module_from_hash(
		$conf,
		{
			id_agente => $agent->{id_agente}, id_tipo_modulo => 2,
			nombre => "test", module_interval => 300, id_module_group => 1
		},
		$dbh
	);
	$module = get_db_single_row(
		$dbh, 'SELECT * FROM tagente_modulo WHERE tagente_modulo.id_agente_modulo = ?', $module_id
	);
	pandora_process_module(
		$conf, $ok, $agent, $module, '', '', time(), $conf->{servername}, $dbh
	);
	my $module_with_ff_id = pandora_create_module_from_hash(
		$conf,
		{ 
			id_agente => $agent->{id_agente}, id_tipo_modulo => 2,
			nombre => "test with FF", module_interval => 300, id_module_group => 1,
			each_ff => 1, min_ff_event_normal => 0, min_ff_event_warning => 0, min_ff_event_critical => 2,
		},
		$dbh
	);
	$module_with_ff = get_db_single_row(
		$dbh, 'SELECT * FROM tagente_modulo WHERE tagente_modulo.id_agente_modulo = ?', $module_with_ff_id
	);
	pandora_process_module(
		$conf, $ok, $agent, $module_with_ff, '', '', time(), $conf->{servername}, $dbh
	);
	return Scope::Guard->new(\&teardown);
}

subtest 'OK -> NG, finally status changes' => sub {
	my $guard = setup();
	my $status;

	# OK
	pandora_process_module(
		$conf, $ok, $agent, $module, '', '', time(), $conf->{servername}, $dbh
	);
	$status = get_db_single_row(
		$dbh, 'SELECT * FROM tagente_estado WHERE id_agente_modulo = ?', $module->{id_agente_modulo}
	);
	ok($status->{estado} == 0, 'status goes normal');

	# NG
	pandora_process_module(
		$conf, $ng, $agent, $module, '', '', time(), $conf->{servername}, $dbh
	);
	$status = get_db_single_row(
		$dbh, 'SELECT * FROM tagente_estado WHERE id_agente_modulo = ?', $module->{id_agente_modulo}
	);
	ok($status->{estado} == 1, 'status goes critical');
};

subtest 'OK -> UN -> NG, finally status changes' => sub {
	my $guard = setup();
	my $status;

	# OK
	pandora_process_module(
		$conf, $ok, $agent, $module, '', '', time(), $conf->{servername}, $dbh
	);
	$status = get_db_single_row(
		$dbh, 'SELECT * FROM tagente_estado WHERE id_agente_modulo = ?', $module->{id_agente_modulo}
	);
	ok($status->{estado} == 0, 'status goes normal');

	# UNKNOWN
	db_do(
		$dbh, 'UPDATE tagente_estado SET last_status = 3, estado = 3 WHERE id_agente_modulo = ?', $module->{id_agente_modulo}
	);

	# NG
	pandora_process_module(
		$conf, $ng, $agent, $module, '', '', time(), $conf->{servername}, $dbh
	);
	$status = get_db_single_row(
		$dbh, 'SELECT * FROM tagente_estado WHERE id_agente_modulo = ?', $module->{id_agente_modulo}
	);
	ok($status->{estado} == 1, 'status goes critical');
};

subtest 'with FF, OK -> NG -> NG -> NG, finally status changes' => sub {
	my $guard = setup();
	my $status;

	# OK
	pandora_process_module(
		$conf, $ok, $agent, $module_with_ff, '', '', time(), $conf->{servername}, $dbh
	);
	$status = get_db_single_row(
		$dbh, 'SELECT * FROM tagente_estado WHERE id_agente_modulo = ?', $module_with_ff->{id_agente_modulo}
	);
	ok($status->{estado} == 0, 'status goes normal');

	# NG
	pandora_process_module(
		$conf, $ng, $agent, $module_with_ff, '', '', time(), $conf->{servername}, $dbh
	);
	$status = get_db_single_row(
		$dbh, 'SELECT * FROM tagente_estado WHERE id_agente_modulo = ?', $module_with_ff->{id_agente_modulo}
	);
	ok($status->{estado} == 0, 'status keeps normal due to FF');

	# NG
	pandora_process_module(
		$conf, $ng, $agent, $module_with_ff, '', '', time(), $conf->{servername}, $dbh
	);
	$status = get_db_single_row(
		$dbh, 'SELECT * FROM tagente_estado WHERE id_agente_modulo = ?', $module_with_ff->{id_agente_modulo}
	);
	ok($status->{estado} == 0, 'status keeps normal due to FF');

	# NG
	pandora_process_module(
		$conf, $ng, $agent, $module_with_ff, '', '', time(), $conf->{servername}, $dbh
	);
	$status = get_db_single_row(
		$dbh, 'SELECT * FROM tagente_estado WHERE id_agente_modulo = ?', $module_with_ff->{id_agente_modulo}
	);
	ok($status->{estado} == 1, 'status goes critical, crossing the Rubicon');
};

subtest 'with FF, OK -> UN -> NG -> NG -> NG, status changes finally' => sub {
	my $guard = setup();
	my $status;

	# OK
	pandora_process_module(
		$conf, $ok, $agent, $module_with_ff, '', '', time(), $conf->{servername}, $dbh
	);
	$status = get_db_single_row(
		$dbh, 'SELECT * FROM tagente_estado WHERE id_agente_modulo = ?', $module_with_ff->{id_agente_modulo}
	);
	ok($status->{estado} == 0, 'status goes normal');

	# UNKNOWN
	db_do(
		$dbh, 'UPDATE tagente_estado SET last_status = 3, estado = 3 WHERE id_agente_modulo = ?', $module_with_ff->{id_agente_modulo}
	);

	# NG
	pandora_process_module(
		$conf, $ng, $agent, $module_with_ff, '', '', time(), $conf->{servername}, $dbh
	);
	$status = get_db_single_row(
		$dbh, 'SELECT * FROM tagente_estado WHERE id_agente_modulo = ?', $module_with_ff->{id_agente_modulo}
	);
	ok($status->{estado} == 0, 'status keeps normal due to FF');
	ok($status->{status_changes} == 0, 'status counter equal to 0');

	# NG
	pandora_process_module(
		$conf, $ng, $agent, $module_with_ff, '', '', time(), $conf->{servername}, $dbh
	);
	$status = get_db_single_row(
		$dbh, 'SELECT * FROM tagente_estado WHERE id_agente_modulo = ?', $module_with_ff->{id_agente_modulo}
	);
	ok($status->{estado} == 0, 'status keeps normal due to FF');
	ok($status->{status_changes} == 1, 'status counter equal to 1');

	# NG
	pandora_process_module(
		$conf, $ng, $agent, $module_with_ff, '', '', time(), $conf->{servername}, $dbh
	);
	$status = get_db_single_row(
		$dbh, 'SELECT * FROM tagente_estado WHERE id_agente_modulo = ?', $module_with_ff->{id_agente_modulo}
	);
	ok($status->{estado} == 1, 'status goes critical, crossing the Rubicon');
	ok($status->{status_changes} == 2, 'status counter equal to 2');
};

subtest 'with FF, OK -> NG -> UN -> NG -> NG, status changes finally' => sub {
	my $guard = setup();
	my $status;

	# OK
	pandora_process_module(
		$conf, $ok, $agent, $module_with_ff, '', '', time(), $conf->{servername}, $dbh
	);
	$status = get_db_single_row(
		$dbh, 'SELECT * FROM tagente_estado WHERE id_agente_modulo = ?', $module_with_ff->{id_agente_modulo}
	);
	ok($status->{estado} == 0, 'status goes normal');

	# NG
	pandora_process_module(
		$conf, $ng, $agent, $module_with_ff, '', '', time(), $conf->{servername}, $dbh
	);
	$status = get_db_single_row(
		$dbh, 'SELECT * FROM tagente_estado WHERE id_agente_modulo = ?', $module_with_ff->{id_agente_modulo}
	);
	ok($status->{estado} == 0, 'status keeps normal due to FF');
	ok($status->{status_changes} == 0, 'status counter equal to 0');

	# UNKNOWN
	db_do(
		$dbh, 'UPDATE tagente_estado SET last_status = 3, estado = 3 WHERE id_agente_estado = ?', $module_with_ff->{id_agente_modulo}
	);

	# NG
	pandora_process_module(
		$conf, $ng, $agent, $module_with_ff, '', '', time(), $conf->{servername}, $dbh
	);
	$status = get_db_single_row(
		$dbh, 'SELECT * FROM tagente_estado WHERE id_agente_modulo = ?', $module_with_ff->{id_agente_modulo}
	);
	ok($status->{estado} == 0, 'status keeps normal due to FF');
	ok($status->{status_changes} == 1, 'status counter equal to 1');

	# NG
	pandora_process_module(
		$conf, $ng, $agent, $module_with_ff, '', '', time(), $conf->{servername}, $dbh
	);
	$status = get_db_single_row(
		$dbh, 'SELECT * FROM tagente_estado WHERE id_agente_modulo = ?', $module_with_ff->{id_agente_modulo}
	);
	ok($status->{estado} == 1, 'status goes critical, crossing the Rubicon');
	ok($status->{status_changes} == 2, 'status counter equal to 1');
};

done_testing;
