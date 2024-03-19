package PandoraFMS::DB;
##########################################################################
# Database Package
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

use DBI;
use Carp qw/croak/;

BEGIN { push @INC, '/usr/lib/perl5'; }
use PandoraFMS::Tools;

#use Data::Dumper;

require Exporter;

our @ISA = ("Exporter");
our %EXPORT_TAGS = ( 'all' => [ qw( ) ] );
our @EXPORT_OK = ( @{ $EXPORT_TAGS{'all'} } );
our @EXPORT = qw(
		add_address
		add_new_address_agent
		db_concat
		db_connect
		db_history_connect
		db_delete_limit
		db_disconnect
		db_do
		db_get_lock
		db_get_pandora_lock
		db_insert
		db_insert_get_values
		db_insert_from_array_hash
		db_insert_from_hash
		db_process_insert
		db_process_update
		db_release_lock
		db_release_pandora_lock
		db_string
		db_text
		db_update
		db_update_hash
		db_update_get_values
		set_update_agent
		set_update_agentmodule
		get_action_id
		get_action_name
		get_addr_id
		get_agent_addr_id
		get_agent_id
		get_agent_ids_from_alias
		get_agent_address
		get_agent_alias
		get_agent_group
		get_agent_name
		get_agent_module_id
		get_agent_module_id_by_name
		get_alert_template_module_id
		get_alert_template_name
		get_command_id
		get_console_api_url
		get_db_nodes
		get_db_rows
		get_db_rows_limit
		get_db_rows_node
		get_db_rows_parallel
		get_db_single_row
		get_db_value
		get_db_value_limit
		get_first_server_name
		get_group_id
		get_group_name
		get_module_agent_id
		get_module_group_id
		get_module_group_name
		get_module_id
		get_module_name
		get_nc_profile_name
		get_pen_templates
		get_nc_profile_advanced
		get_os_id
		get_os_name
		get_plugin_id
		get_profile_id
		get_priority_name
		get_server_id
		get_tag_id
		get_tag_name
		get_template_id
		get_template_name
		get_group_name
		get_template_id
		get_template_module_id
		get_user_disabled
		get_user_exists
		get_user_profile_id
		get_group_children
		get_agentmodule_custom_id
		set_agentmodule_custom_id
		is_agent_address
		is_group_disabled
		get_agent_status
		get_agent_modules
		get_agentmodule_status
		get_agentmodule_status_str
		get_agentmodule_data
		set_ssl_opts
		db_synch_insert
		db_synch_update
		db_synch_delete
		db_synch
		$RDBMS
		$RDBMS_QUOTE
		$RDBMS_QUOTE_STRING
	);

# Relational database management system in use
our $RDBMS = '';

# For fields, character used to quote reserved words in the current RDBMS
our $RDBMS_QUOTE = '';

# For strings, Character used to quote in the current RDBMS
our $RDBMS_QUOTE_STRING = '';

# SSL options.
my $SSL_OPTS = '';

##########################################################################
## Connect to the DB.
##########################################################################
sub db_connect ($$$$$$) {
	my ($rdbms, $db_name, $db_host, $db_port, $db_user, $db_pass) = @_;
	
	if ($rdbms eq 'mysql') {
		$RDBMS = 'mysql';
		$RDBMS_QUOTE = '`';
		$RDBMS_QUOTE_STRING = '"';
		
		# Connect to MySQL
		my $dbh = DBI->connect("DBI:mysql:$db_name:$db_host:$db_port;$SSL_OPTS", $db_user, $db_pass, { RaiseError => 1, AutoCommit => 1, AutoInactiveDestroy => 1 });
		return undef unless defined ($dbh);
		
		# Enable auto reconnect
		$dbh->{'mysql_auto_reconnect'} = 1;
		
		# Enable character semantics
		$dbh->{'mysql_enable_utf8'} = 1;
		
		return $dbh;
	}
	elsif ($rdbms eq 'postgresql') {
		$RDBMS = 'postgresql';
		$RDBMS_QUOTE = '"';
		$RDBMS_QUOTE_STRING = "'";
		
		# Connect to PostgreSQL
		my $dbh = DBI->connect("DBI:Pg:dbname=$db_name;host=$db_host;port=$db_port", $db_user, $db_pass, { RaiseError => 1, AutoCommit => 1 });
		return undef unless defined ($dbh);
		
		return $dbh;
	}
	elsif ($rdbms eq 'oracle') {
		$RDBMS = 'oracle';
		$RDBMS_QUOTE = '"';
		$RDBMS_QUOTE_STRING = '\'';
		
		# Connect to Oracle
		my $dbh = DBI->connect("DBI:Oracle:dbname=$db_name;host=$db_host;port=$db_port;sid=$db_name", $db_user, $db_pass, { RaiseError => 1, AutoCommit => 1 });
		return undef unless defined ($dbh);
		
		# Set date format
		$dbh->do("ALTER SESSION SET NLS_TIMESTAMP_FORMAT='YYYY-MM-DD HH24:MI:SS'");
		$dbh->do("ALTER SESSION SET NLS_DATE_FORMAT='YYYY-MM-DD HH24:MI:SS'");
		$dbh->do("ALTER SESSION SET NLS_NUMERIC_CHARACTERS='.,'");
		
		# Configuration to avoid errors when working with CLOB columns
		$dbh->{'LongReadLen'} = 66000;
		$dbh->{'LongTruncOk'} = 1;
		
		return $dbh;
	}
	
	return undef;
}

##########################################################################
## Connect to a history DB associated to given dbh.
##########################################################################
sub db_history_connect {
	my ($dbh, $pa_config) = @_;

	my %conf;

	$conf{'history_db_enabled'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = ?", "history_db_enabled");
	$conf{'history_db_host'}    = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = ?", "history_db_host");
	$conf{'history_db_port'}    = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = ?", "history_db_port");
	$conf{'history_db_name'}    = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = ?", "history_db_name");
	$conf{'history_db_user'}    = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = ?", "history_db_user");
	$conf{'history_db_pass'}    = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = ?", "history_db_pass");

	my $history_dbh = ($conf{'history_db_enabled'} eq '1') ? db_connect ($pa_config->{'dbengine'}, $conf{'history_db_name'},
		$conf{'history_db_host'}, $conf{'history_db_port'}, $conf{'history_db_user'}, $conf{'history_db_pass'}) : undef;


	return $history_dbh;
}

########################################################################
## Disconnect from the DB. 
########################################################################
sub db_disconnect ($) {
	my $dbh = shift;

	$dbh->disconnect();
}

########################################################################
## Return local console API url. 
########################################################################
sub get_console_api_url ($$) {
	my ($pa_config, $dbh) = @_;

	# Only if console_api_url was not defined
	if( !defined($pa_config->{"console_api_url"}) ) {
		my $console_api_url = PandoraFMS::Config::pandora_get_tconfig_token(
			$dbh, 'public_url', ''
		);

		my $include_api = 'include/api.php';
		# If public_url is empty in database
		if ( $console_api_url eq '' ) {
			$pa_config->{"console_api_url"} = 'http://127.0.0.1/pandora_console/' . $include_api;
			logger($pa_config, "Assuming default path for API url: " . $pa_config->{"console_api_url"}, 3);
		} else {
			if ($console_api_url !~ /\/$/) {
				$console_api_url .= '/';
			}
			$pa_config->{"console_api_url"} = $console_api_url . $include_api;	
		}
	}
	return $pa_config->{'console_api_url'};
}

########################################################################
## Return the ID of an alert action given its name.
########################################################################
sub get_action_id ($$) {
	my ($dbh, $action_name) = @_;

	my $rc = get_db_value ($dbh, "SELECT id FROM talert_actions
	                       WHERE name = ?", safe_input($action_name));
	return defined ($rc) ? $rc : -1;
}

########################################################################
## Return the name of an alert action given its ID.
########################################################################
sub get_action_name ($$) {
	my ($dbh, $action_id) = @_;

	my $rc = get_db_value ($dbh, "SELECT name FROM talert_actions
	                       WHERE id = ?", safe_input($action_id));
	return defined ($rc) ? $rc : -1;
}


########################################################################
## Return command ID given the command name.
########################################################################
sub get_command_id ($$) {
	my ($dbh, $command_name) = @_;

	my $rc = get_db_value ($dbh, "SELECT id FROM talert_commands WHERE name = ?", safe_input($command_name));
	return defined ($rc) ? $rc : -1;
}

########################################################################
## Return agent ID given the agent name.
########################################################################
sub get_agent_id ($$) {
	my ($dbh, $agent_name) = @_;
	my $is_meta = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token like 'metaconsole'");
	
	my $rc;
	if($is_meta == 1) {
		$rc = get_db_value ($dbh, "SELECT id_agente FROM tmetaconsole_agent WHERE nombre = ?", safe_input($agent_name));
	} else {
		$rc = get_db_value ($dbh, "SELECT id_agente FROM tagente WHERE nombre = ?", safe_input($agent_name));
	}

	return defined ($rc) ? $rc : -1;
}

########################################################################
## Return agent IDs given an agent alias.
########################################################################
sub get_agent_ids_from_alias ($$) {
	my ($dbh, $agent_alias) = @_;

	my @rc = get_db_rows ($dbh, "SELECT id_agente, nombre FROM tagente WHERE alias = ?", safe_input($agent_alias));

	return @rc;
}

########################################################################
## Return the ID of an alert template given its name.
########################################################################
sub get_template_id ($$) {
	my ($dbh, $template_name) = @_;

	my $rc = get_db_value ($dbh, "SELECT id FROM talert_templates
	                       WHERE name = ?", safe_input($template_name));
	return defined ($rc) ? $rc : -1;
}

########################################################################
## Return the name of an alert template given its ID.
########################################################################
sub get_template_name ($$) {
	my ($dbh, $template_id) = @_;

	my $rc = get_db_value ($dbh, "SELECT name FROM talert_templates
	                       WHERE id = ?", safe_input($template_id));
	return defined ($rc) ? $rc : -1;
}


########################################################################
## Return server ID given the name of server.
########################################################################
sub get_server_id ($$$) {
	my ($dbh, $server_name, $server_type) = @_;

	my $rc = get_db_value ($dbh, "SELECT id_server FROM tserver
					WHERE BINARY name = ? AND server_type = ?",
					$server_name, $server_type);
	return defined ($rc) ? $rc : -1;
}

########################################################################
## Return the ID of a tag given the tag name.
########################################################################
sub get_tag_id ($$) {
	my ($dbh, $tag_name) = @_;

	my $rc = get_db_value ($dbh, "SELECT id_tag FROM ttag
					WHERE name = ?",
					safe_input($tag_name));
	return defined ($rc) ? $rc : -1;
}

########################################################################
## Return the name of a tag given its id.
########################################################################
sub get_tag_name ($$) {
	my ($dbh, $id) = @_;

	my $rc = get_db_value(
		$dbh, "SELECT name FROM ttag
					WHERE id_tag = ?",
		safe_input($id)
	);
	return $rc;
}

########################################################################
## Return the first enabled server name found.
########################################################################
sub get_first_server_name ($) {
	my ($dbh) = @_;

	my $rc = get_db_value ($dbh, "SELECT name FROM tserver");
					
	return defined ($rc) ? $rc : "";
}

########################################################################
## Return group ID given the group name.
########################################################################
sub get_group_id ($$) {
	my ($dbh, $group_name) = @_;

	my $rc = get_db_value ($dbh, 'SELECT id_grupo FROM tgrupo WHERE ' . db_text ('nombre') . ' = ?', safe_input($group_name));
	return defined ($rc) ? $rc : -1;
}

########################################################################
# Return a array of groups, children of given parent.
########################################################################
sub get_group_children ($$$;$);
sub get_group_children ($$$;$) {
	my ($dbh, $parent, $ignorePropagate, $href_groups) = @_;

	if (is_empty($href_groups)) {
		my @groups = get_db_rows($dbh, 'SELECT * FROM tgrupo');

		my %groups = map {
			$_->{'id_grupo'} => $_
		} @groups;

		$href_groups = \%groups;
	}

	my $return = {};
	foreach my $id_grupo (keys %{$href_groups}) {
		if ($id_grupo eq 0) {
			next;
		}

		my $g = $href_groups->{$id_grupo};

		if ($ignorePropagate || $parent eq 0 || $href_groups->{$parent}{'propagate'}) {
			if ($g->{'parent'} eq $parent) {
				$return->{$g->{'id_grupo'}} = $g;
				if ($g->{'propagate'} || $ignorePropagate) {
					$return = add_hashes(
						$return,
						get_group_children($dbh, $g->{'id_grupo'}, $ignorePropagate, $href_groups)
					);
				}
			}
		}
	}

	return $return;
}

########################################################################
## Return OS ID given the OS name.
########################################################################
sub get_os_id ($$) {
	my ($dbh, $os_name) = @_;

	my $rc = get_db_value ($dbh, "SELECT id_os FROM tconfig_os WHERE name = ?", $os_name);
	return defined ($rc) ? $rc : -1;
}

########################################################################
## Return OS name given the OS id.
########################################################################
sub get_os_name ($$) {
	my ($dbh, $os_id) = @_;

	my $rc = get_db_value ($dbh, "SELECT name FROM tconfig_os WHERE id_os = ?", $os_id);
	return defined ($rc) ? $rc : -1;
}

##########################################################################
## SUB get_agent_name (agent_id)
## Return agent group id, given "agent_id"
##########################################################################
sub get_agent_group ($$) {
	my ($dbh, $agent_id) = @_;
	
	my $group_id = get_db_value ($dbh, "SELECT id_grupo
		FROM tagente
		WHERE id_agente = ?", $agent_id);
	return 0 unless defined ($group_id);
	
	return $group_id;
}

########################################################################
## SUB get_agent_name (agent_id)
## Return agent name, given "agent_id"
########################################################################
sub get_agent_name ($$) {
	my ($dbh, $agent_id) = @_;
	
	return get_db_value ($dbh, "SELECT nombre
		FROM tagente
		WHERE id_agente = ?", $agent_id);
}

########################################################################
## SUB get_agent_alias (agent_id)
## Return agent alias, given "agent_id"
########################################################################
sub get_agent_alias ($$) {
	my ($dbh, $agent_id) = @_;
	
	return get_db_value ($dbh, "SELECT alias
		FROM tagente
		WHERE id_agente = ?", $agent_id);
}

########################################################################
## SUB agents_get_modules (agent_id, fields, filters)
## Return the list of modules, given "agent_id"
########################################################################
sub get_agent_modules ($$$$$) {
	my ($pa_config, $dbh, $agent_id, $fields, $filters) = @_;
	
	my $str_filter = '';
	
	foreach my $key (keys %$filters) {
		$str_filter .= ' AND ' . $key . " = " . $filters->{$key};
	}
	
	my @rows = get_db_rows($dbh, "SELECT *
		FROM tagente_modulo
		WHERE id_agente = ?" . $str_filter, $agent_id);
	
	return @rows;
}

########################################################################
## SUB get_agentmodule_data (id_agent_module, period, date)
## Return The data for module in a period of time.
########################################################################

sub get_agentmodule_data ($$$$$) {
	my ($pa_config, $dbh, $id_agent_module, $period, $date) = @_;
	if ($date < 1) {
		# Get current timestamp
		$date = time ();
	}
	
	my $datelimit = $date - $period;
	
	my @rows = get_db_rows($dbh,
		"SELECT datos AS data, utimestamp
		FROM tagente_datos
		WHERE id_agente_modulo = ?
			AND utimestamp > ? AND utimestamp <= ?
		ORDER BY utimestamp ASC",
		$id_agent_module, $datelimit, $date);
	
	#logger($pa_config, "SELECT datos AS data, utimestamp
	#	FROM tagente_datos
	#	WHERE id_agente_modulo = " . $id_agent_module . "
	#		AND utimestamp > " . $datelimit . " AND utimestamp <= " . $date . "
	#	ORDER BY utimestamp ASC", 1);
	
	return @rows;
}

##########################################################################
## Return module custom ID given the module id.
##########################################################################
sub get_agentmodule_custom_id ($$) {
	my ($dbh, $id_agent_module) = @_;

	my $rc = get_db_value(
		$dbh,
		"SELECT custom_id FROM tagente_modulo WHERE id_agente_modulo = ?",
		safe_input($id_agent_module)
	);
	return defined($rc) ? $rc : undef;
}

##########################################################################
## Updates module custom ID given the module id and custom Id.
##########################################################################
sub set_agentmodule_custom_id ($$$) {
	my ($dbh, $id_agent_module, $custom_id) = @_;

	my $rc = db_update(
		$dbh,
		"UPDATE tagente_modulo SET custom_id = ? WHERE id_agente_modulo = ?",
		safe_input($custom_id),
		safe_input($id_agent_module)
	);
	return defined($rc) ? ($rc eq '0E0' ? 0 : $rc) : -1;
}

########################################################################
## SUB get_agentmodule_status (agent_module_id)
## Return agent module status. given "agent_module_id"
########################################################################
sub get_agentmodule_status($$$) {
	my ($pa_config, $dbh, $agent_module_id) = @_;
	
	my $status = get_db_value($dbh, 'SELECT estado
			FROM tagente_estado
			WHERE id_agente_modulo = ?', $agent_module_id);
	
	return $status;
}

########################################################################
## Return the status of an agent module as a string.
########################################################################
sub get_agentmodule_status_str($$$) {
	my ($pa_config, $dbh, $agent_module_id) = @_;
	
	my $status = get_db_value($dbh, 'SELECT estado
			FROM tagente_estado
			WHERE id_agente_modulo = ?', $agent_module_id);
	
	return 'N/A' unless defined($status);
	return 'Normal' if ($status == 0);
	return 'Critical' if ($status == 1);
	return 'Warning' if ($status == 2);
	return 'Unknown' if ($status == 3);
	return 'Not init' if ($status == 4);
	return 'N/A';
}

########################################################################
## SUB get_get_status (agent_id)
## Return agent status, given "agent_id"
########################################################################
sub get_agent_status ($$$) {
	my ($pa_config, $dbh, $agent_id) = @_;
	
	my @modules = get_agent_modules ($pa_config, $dbh,
		$agent_id, 'id_agente_modulo', {'disabled' => 0});
	#logger($pa_config, Dumper(@modules), 5);
	
	# The status are:
	#  3 -> AGENT_MODULE_STATUS_UNKNOW
	#  4 -> AGENT_MODULE_STATUS_CRITICAL_ALERT
	#  1 -> AGENT_MODULE_STATUS_CRITICAL_BAD
	#  2 -> AGENT_MODULE_STATUS_WARNING
	#  0 -> AGENT_MODULE_STATUS_NORMAL
	
	my $module_status = 4;
	my $modules_async = 0;
	foreach my $module (@modules) {
		my $m_status = get_agentmodule_status($pa_config, $dbh,
			$module->{'id_agente_modulo'});
		
		#This is the order to check
		# AGENT_MODULE_STATUS_CRITICAL_BAD
		# AGENT_MODULE_STATUS_WARNING
		# AGENT_MODULE_STATUS_UNKNOWN
		# AGENT_MODULE_STATUS_NORMAL

		if ($m_status == MODULE_CRITICAL) {
			$module_status = MODULE_CRITICAL;
		}
		elsif ($module_status != MODULE_CRITICAL) {
			if ($m_status == MODULE_WARNING)  {
				$module_status = MODULE_WARNING;
			}
			elsif ($module_status != MODULE_WARNING) {
				if ($m_status == MODULE_UNKNOWN) {
					$module_status = MODULE_UNKNOWN;
				}
				elsif ($module_status != MODULE_UNKNOWN) {
					if ($m_status == MODULE_NORMAL) {
						$module_status = MODULE_NORMAL;
					}
					elsif ($module_status != MODULE_NORMAL) {
						if($m_status == MODULE_NOTINIT) {
							$module_status = MODULE_NOTINIT;
						}
					}
				}
			}
		}
		
		
		my $module_type = get_db_value($dbh, 'SELECT id_tipo_modulo
			FROM tagente_modulo
			WHERE id_agente_modulo = ?', $module->{'id_agente_modulo'});
		
		if (($module_type >= 21 && $module_type <= 23) ||
			$module_type == 100) {
			$modules_async++;
		}
	}
	
	my $count_modules = scalar(@modules);
	
	# If all the modules are asynchronous or keep alive, the group cannot be unknown
	if ($modules_async < $count_modules) {
		my $last_contact = get_db_value($dbh,
			'SELECT (UNIX_TIMESTAMP(ultimo_contacto) + (intervalo * 2)) AS last_contact
			FROM tagente WHERE id_agente = ? AND UNIX_TIMESTAMP(ultimo_contacto) > 0', $agent_id);
		
		if (defined($last_contact) && $last_contact < time ()) {
			return 3;
		}
	}

	return $module_status;
}


########################################################################
## SUB get_module_agent_id (agent_module_id)
## Return agent id, given "agent_module_id"
########################################################################
sub get_module_agent_id ($$) {
	my ($dbh, $agent_module_id) = @_;
	
	return get_db_value ($dbh, "SELECT id_agente FROM tagente_modulo WHERE id_agente_modulo = ?", $agent_module_id);
}

########################################################################
## SUB get_agent_address (id_agente)
## Return agent address, given "agent_id"
########################################################################
sub get_agent_address ($$) {
	my ($dbh, $agent_id) = @_;
	
	return get_db_value ($dbh, "SELECT direccion FROM tagente WHERE id_agente = ?", $agent_id);
}

########################################################################
## SUB get_module_name(module_id)
## Return the module name, given "module_id"
########################################################################
sub get_module_name ($$) {
	my ($dbh, $module_id) = @_;
	
	return get_db_value ($dbh, "SELECT nombre FROM tagente_modulo WHERE id_agente_modulo = ?", $module_id);
}

########################################################################
## Return module id given the module name and agent id.
########################################################################
sub get_agent_module_id ($$$) {
	my ($dbh, $module_name, $agent_id) = @_;
	
	my $rc = get_db_value ($dbh, "SELECT id_agente_modulo FROM tagente_modulo WHERE delete_pending = 0 AND nombre = ? AND id_agente = ?", safe_input($module_name), $agent_id);
	return defined ($rc) ? $rc : -1;
}

########################################################################
## Return module id given the module name and agent name.
########################################################################
sub get_agent_module_id_by_name ($$$) {
	my ($dbh, $module_name, $agent_name) = @_;
	
	my $rc = get_db_value (
		$dbh,
		'SELECT id_agente_modulo 
		FROM tagente_modulo tam LEFT JOIN tagente ta ON tam.id_agente = ta.id_agente 
		WHERE tam.nombre = ? AND ta.nombre = ?', safe_input($module_name), $agent_name);
	return defined ($rc) ? $rc : -1;
}

##########################################################################
## Return the module template id given the module id and the template id.
##########################################################################
sub get_template_module_id ($$$) {
	my ($dbh, $module_id, $template_id) = @_;
	
	my $rc = get_db_value ($dbh, "SELECT id FROM talert_template_modules WHERE id_agent_module = ? AND id_alert_template = ?", $module_id, $template_id);
	return defined ($rc) ? $rc : -1;
}

##########################################################################
## Returns true if the given group is disabled, false otherwise.
##########################################################################
sub is_group_disabled ($$) {
	my ($dbh, $group_id) = @_;
	
	return get_db_value ($dbh, "SELECT disabled FROM tgrupo WHERE id_grupo = ?", $group_id);
}

##########################################################################
## Return module ID given the module name.
##########################################################################
sub get_module_id ($$) {
	my ($dbh, $module_name) = @_;
	
	my $rc = get_db_value ($dbh, "SELECT id_tipo FROM ttipo_modulo WHERE nombre = ?", safe_input($module_name));
	return defined ($rc) ? $rc : -1;
}

##########################################################################
## Return disabled bit frin a user.
##########################################################################
sub get_user_disabled ($$) {
	my ($dbh, $user_id) = @_;
	
	my $rc = get_db_value ($dbh, "SELECT disabled FROM tusuario WHERE id_user = ?", safe_input($user_id));
	return defined ($rc) ? $rc : -1;
}

##########################################################################
## Return 1 if user exists or -1 if not
##########################################################################
sub get_user_exists ($$) {
	my ($dbh, $user_id) = @_;
	
	my $rc = get_db_value ($dbh, "SELECT id_user FROM tusuario WHERE id_user = ?", safe_input($user_id));
	return defined ($rc) ? 1 : -1;
}

##########################################################################
## Return plugin ID given the plugin name.
##########################################################################
sub get_plugin_id ($$) {
	my ($dbh, $plugin_name) = @_;
	
	my $rc = get_db_value ($dbh, "SELECT id FROM tplugin WHERE name = ?", safe_input($plugin_name));
	return defined ($rc) ? $rc : -1;
}

##########################################################################
## Return module group ID given the module group name.
##########################################################################
sub get_module_group_id ($$;$) {
	my ($dbh, $module_group_name, $case_insensitve) = @_;
	
	$case_insensitve = 0 unless defined($case_insensitve);

	if (!defined($module_group_name) || $module_group_name eq '') {
		return 0;
	}
	
	my $rc; 
	if($case_insensitve == 0) {
		$rc = get_db_value ($dbh, "SELECT id_mg FROM tmodule_group WHERE name = ?", safe_input($module_group_name));
	} else {
		$rc = get_db_value ($dbh, "SELECT id_mg FROM tmodule_group WHERE LOWER(name) = ?", lc(safe_input($module_group_name)));
	}
	return defined ($rc) ? $rc : -1;
}

##########################################################################
## Return module group name given the module group id.
##########################################################################
sub get_module_group_name ($$) {
	my ($dbh, $module_group_id) = @_;
	
	return get_db_value ($dbh, "SELECT name FROM tmodule_group WHERE id_mg = ?", $module_group_id);
}

##########################################################################
## Return a network component's profile name given its ID.
##########################################################################
sub get_nc_profile_name ($$) {
	my ($dbh, $nc_id) = @_;
	
	return get_db_value ($dbh, "SELECT * FROM tnetwork_profile WHERE id_np = ?", $nc_id);
}

##########################################################################
## Return all network component's profile ids matching given PEN.
##########################################################################
sub get_pen_templates($$) {
	my ($dbh, $pen) = @_;

	my @results = get_db_rows(
		$dbh,
		'SELECT t.`id_np`
		 FROM `tnetwork_profile` t
		 INNER JOIN `tnetwork_profile_pen` pp ON pp.`id_np` = t.`id_np`
		 INNER JOIN `tpen` p ON pp.pen = p.pen
		 WHERE p.`pen` = ?',
		$pen
	);

	@results = map {
		if (ref($_) eq 'HASH') { $_->{'id_np'} }
		else {}
	} @results;


  return @results;
}

##########################################################################
## Return a network component's profile data and pen list, given its ID.
##########################################################################
sub get_nc_profile_advanced($$) {
	my ($dbh, $id_nc) = @_;
	return get_db_single_row(
		$dbh,
		'SELECT t.*,GROUP_CONCAT(p.pen) AS "pen"
		 FROM `tnetwork_profile` t
		 LEFT JOIN `tnetwork_profile_pen` pp ON t.id_np = pp.id_np
		 LEFT JOIN `tpen` p ON pp.pen = p.pen
		 WHERE t.`id_np` = ?
		 GROUP BY t.`id_np`',
		$id_nc
	);
}

##########################################################################
## Return user profile ID given the user id, group id and profile id.
##########################################################################
sub get_user_profile_id ($$$$) {
	my ($dbh, $user_id, $profile_id, $group_id) = @_;
	
	my $rc = get_db_value ($dbh, "SELECT id_up FROM tusuario_perfil
	                              WHERE id_usuario = ?
								  AND id_perfil = ?
								  AND id_grupo = ?",
								  safe_input($user_id),
								  $profile_id,
								  $group_id);
	return defined ($rc) ? $rc : -1;
}

##########################################################################
## Return profile ID given the profile name.
##########################################################################
sub get_profile_id ($$) {
	my ($dbh, $profile_name) = @_;
	
	my $rc = get_db_value ($dbh, "SELECT id_perfil FROM tperfil WHERE name = ?", safe_input($profile_name));
	return defined ($rc) ? $rc : -1;
}

##########################################################################
## Return a group's name given its ID.
##########################################################################
sub get_group_name ($$) {
	my ($dbh, $group_id) = @_;
	
	return get_db_value ($dbh, "SELECT nombre FROM tgrupo WHERE id_grupo = ?", $group_id);
}

########################################################################
## Get a single column returned by an SQL query as a hash reference.
########################################################################
sub get_db_value ($$;@) {
	my ($dbh, $query, @values) = @_;
	
	# Cache statements
	my $sth = $dbh->prepare_cached($query);
	
	$sth->execute(@values);
	
	# Save returned rows
	while (my $row = $sth->fetchrow_arrayref()) {
		$sth->finish();
		return defined ($row->[0]) ? $row->[0] : undef;
	}
	
	$sth->finish();
	
	return undef;
}

########################################################################
## Get a single column returned by an SQL query with a LIMIT statement
## as a hash reference.
########################################################################
sub get_db_value_limit ($$$;@) {
	my ($dbh, $query, $limit, @values) = @_;
	
	# Cache statements
	my $sth;
	if ($RDBMS ne 'oracle') {
		$sth = $dbh->prepare_cached($query . ' LIMIT ' . int($limit));
	} else {
		$sth = $dbh->prepare_cached('SELECT * FROM (' . $query . ') WHERE ROWNUM <= ' . int($limit));
	}

	$sth->execute(@values);

	# Save returned rows
	while (my $row = $sth->fetchrow_arrayref()) {
		$sth->finish();
		return defined ($row->[0]) ? $row->[0] : undef;
	}
	
	$sth->finish();
	
	return undef;
}

##########################################################################
## Get a single row returned by an SQL query as a hash reference. Returns
## hash or undef on error.
##########################################################################
sub get_db_single_row ($$;@) {
	my ($dbh, $query, @values) = @_;
	#my @rows;
	
	# Cache statements
	my $sth = $dbh->prepare_cached($query);
	
	$sth->execute(@values);
	
	# Save returned rows
	while (my $row = $sth->fetchrow_hashref()) {
		$sth->finish();
		return {map { lc ($_) => $row->{$_} } keys (%{$row})} if ($RDBMS eq 'oracle');
		return $row;
	}
	
	$sth->finish();
	
	return undef;
}

##########################################################################
## Get DB information for all known Pandora FMS nodes.
##########################################################################
sub get_db_nodes ($$) {
	my ($dbh, $pa_config) = @_;
	my $dbh_nodes = [];

	# Insert the current node first.
	push(@{$dbh_nodes},
	     {'dbengine' => $pa_config->{'dbengine'},
	      'dbname'   => $pa_config->{'dbname'},
	      'dbhost'   => $pa_config->{'dbhost'},
	      'dbport'   => $pa_config->{'dbport'},
	      'dbuser'   => $pa_config->{'dbuser'},
	      'dbpass'   => $pa_config->{'dbpass'}});

	# Look for additional nodes.
	my @nodes = get_db_rows($dbh, 'SELECT * FROM tmetaconsole_setup WHERE disabled = 0');
	foreach my $node (@nodes) {
		# Check and decrypy passwords if necessary.
		if (defined($pa_config->{'encryption_passphrase'})) {
			$pa_config->{'encryption_key'} = enterprise_hook('pandora_get_encryption_key', [$pa_config, $pa_config->{'encryption_passphrase'}]);
			$node->{'dbpass'} = PandoraFMS::Core::pandora_output_password($pa_config, $node->{'dbpass'});
		}
		
		push(@{$dbh_nodes},
		     {'dbengine' => $pa_config->{'dbengine'},
		      'dbname'   => $node->{'dbname'},
		      'dbhost'   => $node->{'dbhost'},
		      'dbport'   => $node->{'dbport'},
		      'dbuser'   => $node->{'dbuser'},
		      'dbpass'   => $node->{'dbpass'}});
	}

	return $dbh_nodes;
}

##########################################################################
## Get all rows returned by an SQL query as a hash reference array.
##########################################################################
sub get_db_rows ($$;@) {
	my ($dbh, $query, @values) = @_;
	my @rows;
	
	# Cache statements
	my $sth = $dbh->prepare_cached($query);
	
	$sth->execute(@values);
	
	# Save returned rows
	while (my $row = $sth->fetchrow_hashref()) {
		push (@rows, $row);
	}
	
	$sth->finish();
	return @rows;
}

##########################################################################
## Connect to the given node and run get_db_rows.
##########################################################################
sub get_db_rows_node ($$$;@) {
	my ($pa_config, $node, $query, @values) = @_;
	my $dbh;
	my @rows;

	eval {
		$dbh = db_connect($node->{'dbengine'},
		                  $node->{'dbname'},
		                  $node->{'dbhost'},
		                  $node->{'dbport'},
		                  $node->{'dbuser'},
		                  $node->{'dbpass'});
		@rows = get_db_rows($dbh, $query, @values);
	};
	if($@) {
		# Reconnect to meta db.
			my $dbh = db_connect ($pa_config->{'dbengine'},
														$pa_config->{'dbname'},
														$pa_config->{'dbhost'},
														$pa_config->{'dbport'},
														$pa_config->{'dbuser'},
														$pa_config->{'dbpass'});
														
			my $msg = "Cannot connect to node database: ".$node->{'dbhost'}.". Please check node credentials.";
			logger ($pa_config, "[ERROR] ".$msg, 3);
			PandoraFMS::Core::pandora_event ($pa_config, $msg, 0, 0, 4, 0, 0, 'error', 0, $dbh);			
			db_disconnect($dbh) if defined($dbh);

			exit 0;
	}

	db_disconnect($dbh) if defined($dbh);

	return \@rows;
}

##########################################################################
## Run get_db_rows on all known Pandora FMS nodes in parallel.
##########################################################################
sub get_db_rows_parallel ($$$;@) {
	my ($pa_config, $nodes, $query, @values) = @_;

	# Launch the queries.
	my @threads;
	{
		# Calling DESTROY would make the server restart.
		no warnings 'redefine';
		local *PandoraFMS::ProducerConsumerServer::DESTROY = sub {};
		local *PandoraFMS::BlockProducerConsumerServer::DESTROY = sub {};
		local *PandoraFMS::SNMPServer::DESTROY = sub {};

		# Query the nodes.
		foreach my $node (@{$nodes}) {
			my $thr = threads->create(\&get_db_rows_node, $pa_config, $node, $query, @values);
			push(@threads, $thr) if defined($thr);
		}
	}

	# Retrieve the results.
	my @combined_res;
	foreach my $thr (@threads) {
		my $res = $thr->join();
		push(@combined_res, @{$res}) if defined($res);
	}

	return @combined_res;
}

########################################################################
## Get all rows (with a limit clause) returned by an SQL query
## as a hash reference array.
########################################################################
sub get_db_rows_limit ($$$;@) {
	my ($dbh, $query, $limit, @values) = @_;
	my @rows;
	
	# Cache statements
	my $sth;
	if ($RDBMS ne 'oracle') {
		$sth = $dbh->prepare_cached($query . ' LIMIT ' . $limit);
	} else {
		$sth = $dbh->prepare_cached('SELECT * FROM (' . $query . ') WHERE ROWNUM <= ' . $limit);
	}
	
	$sth->execute(@values);
	
	# Save returned rows
	while (my $row = $sth->fetchrow_hashref()) {
		if ($RDBMS eq 'oracle') {
			push (@rows, {map { lc ($_) => $row->{$_} } keys (%{$row})});
		}
		else {
			push (@rows, $row);
		}
	}
	
	$sth->finish();
	return @rows;
}

##########################################################################
## Updates using hashed data.
##   $dbh       database connector (active)
##   $tablename table name
##   $id        hashref as { 'primary_key_id' => "value" }
##   $data      hashref as { 'field1' => "value", 'field2' => "value"}
##########################################################################
sub db_update_hash {
	my ($dbh, $tablename, $id, $data) = @_;

	return undef unless (defined($tablename) && $tablename ne "");
	
	return undef unless (ref($data) eq "HASH");

	# Build update query
	my $query = 'UPDATE `'.$tablename.'` SET ';

	my @values;
	foreach my $field (keys %{$data}) {
		push @values, $data->{$field};

		$query .= ' ' . $field . ' = ?,';
	}

	chop($query);

	my @keys = keys %{$id};
	my $k = shift @keys;

	$query .= ' WHERE '.$k.' = ? ';
	push @values, $id->{$k};

	return db_update($dbh, $query, @values);
}

##########################################################################
## Updates agent fields using field => value
##  Be careful, no filter is done.
##########################################################################
sub set_update_agent {
	my ($dbh, $agent_id, $data) = @_;

	return undef unless (defined($agent_id) && $agent_id > 0);
	return undef unless (ref($data) eq "HASH");

	return db_update_hash(
		$dbh,
		'tagente',
		{ 'id_agente' => $agent_id },
		$data
	);
}

##########################################################################
## Updates agent fields using field => value
##  Be careful, no filter is done.
##########################################################################
sub set_update_agentmodule {
	my ($dbh, $agentmodule_id, $data) = @_;

	return undef unless (defined($agentmodule_id) && $agentmodule_id > 0);
	return undef unless (ref($data) eq "HASH");

	return db_update_hash(
		$dbh,
		'tagente_modulo',
		{ 'id_agente_modulo' => $agentmodule_id },
		$data
	);
}

##########################################################################
## SQL delete with a LIMIT clause.
##########################################################################
sub db_delete_limit ($$$$;@) {
	my ($dbh, $from, $where, $limit, @values) = @_;
	my $sth;

	# MySQL
	if ($RDBMS eq 'mysql') {
		$sth = $dbh->prepare_cached("DELETE FROM $from WHERE $where LIMIT " . int($limit));
	}
	# PostgreSQL
	elsif ($RDBMS eq 'postgresql') {
		$sth = $dbh->prepare_cached("DELETE FROM $from WHERE $where LIMIT " . int($limit));
	}
	# Oracle
	elsif ($RDBMS eq 'oracle') {
		$sth = $dbh->prepare_cached("DELETE FROM (SELECT * FROM $from WHERE $where) WHERE ROWNUM <= " . int($limit));
	}

	$sth->execute(@values);
}

##########################################################################
## SQL insert. Returns the ID of the inserted row.
##########################################################################
sub db_insert ($$$;@) {
	my ($dbh, $index, $query, @values) = @_;
	my $insert_id = undef;

	eval {	
		$dbh->do($query, undef, @values);
		$insert_id = $dbh->{'mysql_insertid'};
	};
	if ($@) {
		my $exception = @_;
		if ($DBI::err == 1213 || $DBI::err == 1205) {
			$dbh->do($query, undef, @values);
			$insert_id = $dbh->{'mysql_insertid'};
		}
		else {
			croak (join(', ', @_));
		}
	}
	
	return $insert_id;
}

##########################################################################
## SQL update. Returns the number of updated rows.
##########################################################################
sub db_update ($$;@) {
	my ($dbh, $query, @values) = @_;
	my $rows;

	eval {
		$rows = $dbh->do($query, undef, @values);
	};
	if ($@) {
		my $exception = @_;
		if ($DBI::err == 1213 || $DBI::err == 1205) {
			$rows = $dbh->do($query, undef, @values);
		}
		else {
			croak (join(', ', @_));
		}
	}
	
	return $rows;
}

##########################################################################
## Return alert template-module ID given the module and template ids.
##########################################################################
sub get_alert_template_module_id ($$$$) {
	my ($dbh, $id_module, $id_template, $id_policy_alerts) = @_;
	
	my $rc = get_db_value ($dbh, "SELECT id FROM talert_template_modules WHERE id_agent_module = ? AND id_alert_template = ? AND id_policy_alerts = ?", $id_module, $id_template, $id_policy_alerts);
	return defined ($rc) ? $rc : -1;
}

########################################################################
## SQL insert. Returns the ID of the inserted row.
########################################################################
sub db_process_insert($$$$;@) {
	my ($dbh, $index, $table, $parameters, @values) = @_;
	
	my @columns_array = keys %$parameters;
	my @values_array = values %$parameters;
	
	if (!defined($table) || $#columns_array == -1) {
		return -1;
		exit;
	}
	
	# Generate the '?' simbols to the Query like '(?,?,?,?,?)'
	my $wildcards = '';
	for (my $i=0; $i<=$#values_array; $i++) {
		if (!defined($values_array[$i])) {
			$values_array[$i] = '';
		}
		if ($i > 0 && $i <= $#values_array) {
			$wildcards = $wildcards.',';
		}
		$wildcards = $wildcards.'?';
	}
	$wildcards = '('.$wildcards.')';
	
	# Escape column names that are reserved words
	for (my $i = 0; $i < scalar(@columns_array); $i++) {
		if ($columns_array[$i] eq 'interval') {
			$columns_array[$i] = "${RDBMS_QUOTE}interval${RDBMS_QUOTE}";
		}
	}
	my $columns_string = join(',', @columns_array);

	my $res = db_insert ($dbh,
		$index,
		"INSERT INTO $table ($columns_string) VALUES " . $wildcards, @values_array);
	
	
	return $res;
}

########################################################################
## SQL insert from hash
## 1st: dbh
## 2nd: index
## 3rd: table name,
## 4th: {field => value} ref
########################################################################
sub db_insert_from_hash {
	my ($dbh, $index, $table, $data) = @_;

	my $values_prep = "";
	my @fields = keys %{$data};
	my @values = values %{$data};
	my $nfields = scalar @fields;

	for (my $i=0; $i<$nfields; $i++) {
		$values_prep .= "?,";
	}
	$values_prep =~ s/,$//;

	return db_insert($dbh, $index, "INSERT INTO " . $table . " (" . join (",", @fields) . ") VALUES ($values_prep)", @values);
}

########################################################################
## SQL insert from hash
## 1st: dbh
## 2nd: index
## 3rd: table name,
## 4th: array({field => value},{field => value}) array ref
## 
## Returns: An array with the inserted indexes
########################################################################
sub db_insert_from_array_hash {
	my ($dbh, $index, $table, $data) = @_;

	if ((!defined($data) || ref ($data) ne "ARRAY")) {
		return ();
	}


	my @inserted_keys;

	eval {
		foreach my $row (@{$data}) {
			push @inserted_keys, db_insert_from_hash($dbh, $index, $table, $row);
		}
	};
	if ($@) {
		return undef;
	}

	return @inserted_keys;
}

########################################################################
## SQL update.
########################################################################
sub db_process_update($$$$) {
	my ($dbh, $table, $parameters, $conditions) = @_;
	
	my @columns_array = keys %$parameters;
	my @values_array = values %$parameters;
	my @where_columns = keys %$conditions;
	my @where_values = values %$conditions;
	
	if (!defined($table) || $#columns_array == -1 || $#where_columns == -1) {
		return -1;
		exit;
	}
	
	# VALUES...
	my $fields = '';
	for (my $i = 0; $i <= $#values_array; $i++) {
		if (!defined($values_array[$i])) {
			$values_array[$i] = '';
		}
		if ($i > 0 && $i <= $#values_array) {
			$fields = $fields.',';
		}
		
		# Avoid the use of quotes on the column names in oracle, cause the quotes
		# force the engine to be case sensitive and the column names created without
		# quotes are stores in uppercase.
		# The quotes should be introduced manually for every item created with it.
		if ($RDBMS eq 'oracle') {
			$fields = $fields . " " . $columns_array[$i] . " = ?";
		}
		else {
			$fields = $fields . " " . $RDBMS_QUOTE . "$columns_array[$i]" . $RDBMS_QUOTE . " = ?";
		}
	}

	# WHERE...
	my $where = '';
	for (my $i = 0; $i <= $#where_columns; $i++) {
		if (!defined($where_values[$i])) {
			$where_values[$i] = '';
		}
		if ($i > 0 && $i <= $#where_values) {
			$where = $where.' AND ';
		}
		
		# Avoid the use of quotes on the column names in oracle, cause the quotes
		# force the engine to be case sensitive and the column names created without
		# quotes are stores in uppercase.
		# The quotes should be introduced manually for every item created with it.
		if ($RDBMS eq 'oracle') {
			$where = $where . " " . $where_columns[$i] . " = ?";
		}
		else {
			$where = $where . " " . $RDBMS_QUOTE . "$where_columns[$i]" . $RDBMS_QUOTE . " = ?";
		}
	}

	my $res = db_update ($dbh, "UPDATE $table
		SET $fields
		WHERE $where", @values_array, @where_values);
	
	return $res;
}

########################################################################
# Add the given address to taddress.
########################################################################
sub add_address ($$) {
	my ($dbh, $ip_address) = @_;
	
	return db_insert ($dbh, 'id_a', 'INSERT INTO taddress (ip) VALUES (?)', $ip_address);
}

########################################################################
# Assign the new address to the agent
########################################################################
sub add_new_address_agent ($$$) {
	my ($dbh, $addr_id, $agent_id) = @_;
	
	db_do ($dbh, 'INSERT INTO taddress_agent (id_a, id_agent)
	              VALUES (?, ?)', $addr_id, $agent_id);
}

########################################################################
# Return the ID of the given address, -1 if it does not exist.
########################################################################
sub get_addr_id ($$) {
	my ($dbh, $addr) = @_;
	
	my $addr_id = get_db_value ($dbh,
		'SELECT id_a
		FROM taddress
		WHERE ip = ?', $addr);
	
	return (defined ($addr_id) ? $addr_id : -1);
}

##########################################################################
# Return the agent address ID for the given agent ID and address ID, -1 if
# it does not exist.
##########################################################################
sub get_agent_addr_id ($$$) {
	my ($dbh, $addr_id, $agent_id) = @_;
	
	my $agent_addr_id = get_db_value ($dbh,
		'SELECT id_ag
		FROM taddress_agent
		WHERE id_a = ?
			AND id_agent = ?', $addr_id, $agent_id);
	
	return (defined ($agent_addr_id) ? $agent_addr_id : -1);
}

########################################################################
## Generic SQL sentence. 
########################################################################
sub db_do ($$;@) {
	my ($dbh, $query, @values) = @_;

	#DBI->trace( 3, '/tmp/dbitrace.log' );
	eval {
		$dbh->do($query, undef, @values);
	};
	if ($@) {
		my $exception = @_;
		if ($DBI::err == 1213 || $DBI::err == 1205) {
			$dbh->do($query, undef, @values);
		}
		else {
			croak (join(', ', @_));
		}
	}
}

########################################################################
# Return the ID of the taddress agent with the given IP.
########################################################################
sub is_agent_address ($$$) {
	my ($dbh, $id_agent, $id_addr) = @_;
	
	my $id_ag = get_db_value ($dbh, 'SELECT id_ag
		FROM taddress_agent 
		WHERE id_a = ?
			AND id_agent = ?', $id_addr, $id_agent);
	
	return (defined ($id_ag)) ? $id_ag : 0;
}

########################################################################
## Quote the given string. 
########################################################################
sub db_string ($) {
	my $string = shift;
	
	# MySQL and PostgreSQL
	#return "'" . $string . "'" if ($RDBMS eq 'mysql' || $RDBMS eq 'postgresql' || $RDBMS eq 'oracle');
	
	return "'" . $string . "'";
}

########################################################################
## Convert TEXT to string when necessary
########################################################################
sub db_text ($) {
	my $string = shift;
	
	#return $string;
	return " dbms_lob.substr(" . $string . ", 4000, 1)" if ($RDBMS eq 'oracle');
	
	return $string;
}

########################################################################
## SUB get_alert_template_name(alert_id)
## Return the alert template name, given "alert_id"
########################################################################
sub get_alert_template_name ($$) {
	my ($dbh, $alert_id) = @_;
	
	return get_db_value ($dbh, "SELECT name
		FROM talert_templates, talert_template_modules
		WHERE talert_templates.id = talert_template_modules.id_alert_template
			AND talert_template_modules.id = ?", $alert_id);
}

########################################################################
## Concat two strings
########################################################################
sub db_concat ($$) {
	my ($element1, $element2) = @_;
	
	return " " . $element1 . " || ' ' || " . $element2 . " " if ($RDBMS eq 'oracle' or $RDBMS eq 'postgresql');
	return " concat(" . $element1 . ", ' '," . $element2 . ") ";
}

########################################################################
## Get priority/severity name from the associated ID
########################################################################
sub get_priority_name ($) {
	my ($priority_id) = @_;

	return '' unless defined($priority_id);
	
	if ($priority_id == 0) {
		return 'Maintenance';
	}
	elsif ($priority_id == 1) {
		return 'Informational';
	}
	elsif ($priority_id == 2) {
		return 'Normal';
	}
	elsif ($priority_id == 3) {
		return 'Warning';
	}
	elsif ($priority_id == 4) {
		return 'Critical';
	}
	elsif ($priority_id == 5) {
		return 'Minor';
	}
	elsif ($priority_id == 6) {
		return 'Major';
	}
	
	return '';
}

########################################################################
## Get the set string and array of values to perform un update from a hash.
########################################################################
sub db_update_get_values ($) {
	my ($set_ref) = @_;
	
	my $set = '';
	my @values;
	while (my ($key, $value) = each (%{$set_ref})) {
		
		# Not value for the given column
		next if (! defined ($value));
		
		$set .= "$key = ?,";
		push (@values, $value);
	}
	
	# Remove the last ,
	chop ($set);
	
	return ($set, \@values);
}

########################################################################
## Get the string and array of values to perform an insert from a hash.
########################################################################
sub db_insert_get_values ($) {
	my ($insert_ref) = @_;
	
	my $columns = '(';
	my @values;
	while (my ($key, $value) = each (%{$insert_ref})) {
		
		# Not value for the given column
		next if (! defined ($value));
		
		$columns .= $key . ",";
		push (@values, $value);
	}
	
	# Remove the last , and close the parentheses
	chop ($columns);
	$columns .= ')';
	
	# No columns
	if ($columns eq '()') {
		return;
	}
	
	# Add placeholders for the values
	$columns .= ' VALUES (' . ("?," x ($#values + 1));
	
	# Remove the last , and close the parentheses
	chop ($columns);
	$columns .= ')';
	
	return ($columns, \@values);
}

########################################################################
## Try to obtain the given lock.
########################################################################
sub db_get_lock($$;$$) {
	my ($dbh, $lock_name, $lock_timeout, $do_not_wait_lock) = @_;

	# Only supported in MySQL.
	return 1 unless ($RDBMS eq 'mysql');

	# Set a default lock timeout of 1 second
	$lock_timeout = 1 if (! defined ($lock_timeout));

	if ($do_not_wait_lock) {
		if (!db_is_free_lock($dbh, $lock_name)) {
			return 0;
		}
	}
	
	# Attempt to get the lock!
	my $sth = $dbh->prepare('SELECT GET_LOCK(?, ?)');
	$sth->execute($lock_name, $lock_timeout);
	my ($lock) = $sth->fetchrow;
	
	# Something went wrong
	return 0 if (! defined ($lock));
	
	return $lock;
}

########################################################################
## Check is lock is free.
########################################################################
sub db_is_free_lock($$) {
	my ($dbh, $lock_name) = @_;

	# Only supported in MySQL.
	return 1 unless ($RDBMS eq 'mysql');
	
	# Attempt to get the lock!
	my $sth = $dbh->prepare('SELECT IS_FREE_LOCK(?)');
	$sth->execute($lock_name);
	my ($lock) = $sth->fetchrow;
	
	# Something went wrong
	return 0 if (! defined ($lock));
	
	return $lock;
}

########################################################################
## Release the given lock.
########################################################################
sub db_release_lock($$) {
	my ($dbh, $lock_name) = @_;
	
	# Only supported in MySQL.
	return unless ($RDBMS eq 'mysql');

	my $sth = $dbh->prepare('SELECT RELEASE_LOCK(?)');
	$sth->execute($lock_name);
	my ($lock) = $sth->fetchrow;
}

########################################################################
## Try to obtain a persistent lock using Pandora FMS's database.
########################################################################
sub db_get_pandora_lock($$;$) {
	my ($dbh, $lock_name, $lock_timeout) = @_;
	my $rv;

	# Lock.
	my $lock = db_get_lock($dbh, $lock_name, $lock_timeout);
	if ($lock != 0) {
		my $lock_value = get_db_value($dbh, "SELECT `value` FROM tconfig WHERE token = 'pandora_lock_$lock_name'");
		if (!defined($lock_value)) {
			my $sth = $dbh->prepare('INSERT INTO tconfig (`token`, `value`) VALUES (?, ?)');
			$rv = $sth->execute('pandora_lock_' . $lock_name, '1');
		} elsif ($lock_value == 0) {
			my $sth = $dbh->prepare('UPDATE tconfig SET `value`=? WHERE `token`=?');
			$rv = $sth->execute('1', 'pandora_lock_' . $lock_name);
		}
		db_release_lock($dbh, $lock_name);
	}

	# Lock acquired.
	if ($rv) {
		return 1;
	}

	# Something went wrong.
	return 0;
}

########################################################################
## Release a persistent lock.
########################################################################
sub db_release_pandora_lock($$;$) {
	my ($dbh, $lock_name, $lock_timeout) = @_;
	my $rv;

	# Lock.
	my $lock = db_get_lock($dbh, $lock_name, $lock_timeout);
	if ($lock != 0) {
		my $sth = $dbh->prepare('UPDATE tconfig SET `value`=? WHERE `token`=?');
		$rv = $sth->execute('0', 'pandora_lock_' . $lock_name);
		db_release_lock($dbh, $lock_name);
	}
}

########################################################################
## Set SSL options globally for the module.
########################################################################
sub set_ssl_opts($) {
	my ($pa_config) = @_;

	# SSL is disabled for the DB.
	if (!defined($pa_config->{'dbssl'}) || $pa_config->{'dbssl'} == 0) {
		return;
	}

	# Enable SSL.
	$SSL_OPTS = "mysql_ssl=1;mysql_ssl_optional=1";

	# Set additional SSL options.
	if (defined($pa_config->{'verify_mysql_ssl_cert'}) && $pa_config->{'verify_mysql_ssl_cert'} ne "") {
		$SSL_OPTS .= ";mysql_ssl_verify_server_cert=" . $pa_config->{'verify_mysql_ssl_cert'};
	}
	if (defined($pa_config->{'dbsslcapath'}) && $pa_config->{'dbsslcapath'} ne "") {
		$SSL_OPTS .= ";mysql_ssl_ca_path=" . $pa_config->{'dbsslcapath'};
	}
	if (defined($pa_config->{'dbsslcafile'}) && $pa_config->{'dbsslcafile'} ne "") {
		$SSL_OPTS .= ";mysql_ssl_ca_file=" . $pa_config->{'dbsslcafile'};
	}
}

########################################################################
## Synch insert query with nodes.
########################################################################
sub db_synch_insert ($$$$$@) {
	my ($dbh, $pa_config, $table, $query, $result, @values) = @_;

	my $substr = "\"\%s\"";
	$query =~ s/\?/$substr/g;
	my $query_string = sprintf($query, @values);

	db_synch($dbh, $pa_config, 'INSERT INTO', $table, $query_string, $result);
}

########################################################################
## Synch update query with nodes.
########################################################################
sub db_synch_update ($$$$$@) {
	my ($dbh, $pa_config, $table, $query, $result, @values) = @_;

	my $substr = "\"\%s\"";
	$query =~ s/\?/$substr/g;
	my $query_string = sprintf($query, @values);

	db_synch($dbh, $pa_config, 'UPDATE', $table, $query_string, $result);
}

########################################################################
## Synch delete query with nodes.
########################################################################
sub db_synch_delete ($$$$@) {
	my ($dbh, $pa_config, $table, $result, @parameters) = @_;

	#Build query string.
	my $query = $dbh->{Statement};

	my $substr = "\"\%s\"";
	$query =~ s/\?/$substr/g;

	my $query_string = sprintf($query, @parameters);

	db_synch($dbh, $pa_config, 'DELETE FROM', $table, $query_string, $result);
}

########################################################################
## Synch queries with nodes.
########################################################################
sub db_synch ($$$$$$) {
	my ($dbh, $pa_config, $type, $table, $query, $result) = @_;
	my @nodes = get_db_rows($dbh, 'SELECT * FROM tmetaconsole_setup');
	foreach my $node (@nodes) {
		eval {
			local $SIG{__DIE__};
			my @values_queue = (
				safe_input($query),
				$node->{'id'},
				time(),
				$type,
				$table,
				'',
				$result
			);
			
			my $query_queue = 'INSERT INTO tsync_queue (`sql`, `target`, `utimestamp`, `operation`, `table`, `error`, `result`) VALUES (?, ?, ?, ?, ?, ?, ?)';
			db_insert ($dbh, 'id', $query_queue, @values_queue);
		};
		if ($@) {
			logger($pa_config, "Error add sync_queue: $@", 10);
			return;
		}
	}
}

# End of function declaration
# End of defined Code

1;
__END__
