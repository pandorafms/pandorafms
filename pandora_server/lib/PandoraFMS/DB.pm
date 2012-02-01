package PandoraFMS::DB;
##########################################################################
# Database Package
# Pandora FMS. the Flexible Monitoring System. http://www.pandorafms.org
##########################################################################
# Copyright (c) 2005-2011 Artica Soluciones Tecnologicas S.L
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
use DBI;
use PandoraFMS::Tools;	

require Exporter;

our @ISA = ("Exporter");
our %EXPORT_TAGS = ( 'all' => [ qw( ) ] );
our @EXPORT_OK = ( @{ $EXPORT_TAGS{'all'} } );
our @EXPORT = qw(
		add_address
		add_new_address_agent
		db_concat
		db_connect
		db_disconnect
		db_do
		db_insert
		db_process_insert
		db_process_update
		db_reserved_word
		db_string
		db_text
		db_update
		get_action_id
		get_addr_id
		get_agent_id
		get_agent_address
		get_agent_group
		get_agent_name
		get_agent_module_id
		get_alert_template_module_id
		get_alert_template_name
		get_db_rows
		get_db_single_row
		get_db_value
		get_group_id
		get_group_name
		get_module_agent_id
		get_module_group_id
		get_module_group_name
		get_module_id
		get_module_name
		get_nc_profile_name
		get_os_id
		get_plugin_id
		get_profile_id
		get_server_id
		get_template_id
		get_template_module_id
		is_agent_address
		is_group_disabled
	);

##########################################################################
## Connect to the DB.
##########################################################################
my $RDBMS = '';
sub db_connect ($$$$$$) {
	my ($rdbms, $db_name, $db_host, $db_port, $db_user, $db_pass) = @_;

	if ($rdbms eq 'mysql') {
		$RDBMS = 'mysql';
		
		# Connect to MySQL
		my $dbh = DBI->connect("DBI:mysql:$db_name:$db_host:3306", $db_user, $db_pass, { RaiseError => 1, AutoCommit => 1 });
		return undef unless defined ($dbh);
		
		# Enable auto reconnect
		$dbh->{'mysql_auto_reconnect'} = 1;

		# Enable character semantics
		$dbh->{'mysql_enable_utf8'} = 1;

		return $dbh;
	} elsif ($rdbms eq 'postgresql') {
		$RDBMS = 'postgresql';
		
		# Connect to PostgreSQL
		my $dbh = DBI->connect("DBI:Pg:dbname=$db_name;host=$db_host;port=5432", $db_user, $db_pass, { RaiseError => 1, AutoCommit => 1 });
		return undef unless defined ($dbh);
		
		return $dbh;
	} elsif ($rdbms eq 'oracle') {
		$RDBMS = 'oracle';
		
		# Connect to Oracle
		my $dbh = DBI->connect("DBI:Oracle:dbname=$db_name;host=$db_host;port=1521;sid=pandora", $db_user, $db_pass, { RaiseError => 1, AutoCommit => 1 });
		return undef unless defined ($dbh);
		
		# Set date format
		$dbh->do("ALTER SESSION SET NLS_TIMESTAMP_FORMAT='YYYY-MM-DD HH24:MI:SS'");
		$dbh->do("ALTER SESSION SET NLS_NUMERIC_CHARACTERS='.,'");
		return $dbh;
	}
	
	return undef;
}

##########################################################################
## Disconnect from the DB. 
##########################################################################
sub db_disconnect ($) {
	my $dbh = shift;

	$dbh->disconnect();
}

##########################################################################
## Return action ID given the action name.
##########################################################################
sub get_action_id ($$) {
	my ($dbh, $action_name) = @_;

	my $rc = get_db_value ($dbh, "SELECT id FROM talert_actions WHERE name = ?", $action_name);
	return defined ($rc) ? $rc : -1;
}

##########################################################################
## Return agent ID given the agent name.
##########################################################################
sub get_agent_id ($$) {
	my ($dbh, $agent_name) = @_;

	my $rc = get_db_value ($dbh, "SELECT id_agente FROM tagente WHERE nombre = ? OR direccion = ?", safe_input($agent_name), $agent_name);
	return defined ($rc) ? $rc : -1;
}

##########################################################################
## Return server ID given the name of server.
##########################################################################
sub get_server_id ($$$) {
	my ($dbh, $server_name, $server_type) = @_;

	my $rc = get_db_value ($dbh, "SELECT id_server FROM tserver
					WHERE name = ? AND server_type = ?",
					$server_name, $server_type);
	return defined ($rc) ? $rc : -1;
}

##########################################################################
## Return group ID given the group name.
##########################################################################
sub get_group_id ($$) {
	my ($dbh, $group_name) = @_;

	my $rc = get_db_value ($dbh, 'SELECT id_grupo FROM tgrupo WHERE ' . db_text ('nombre') . ' = ?', safe_input($group_name));
	return defined ($rc) ? $rc : -1;
}

##########################################################################
## Return OS ID given the OS name.
##########################################################################
sub get_os_id ($$) {
	my ($dbh, $os_name) = @_;

	my $rc = get_db_value ($dbh, "SELECT id_os FROM tconfig_os WHERE name = ?", $os_name);
	return defined ($rc) ? $rc : -1;
}

##########################################################################
## SUB get_agent_name (agent_id)
## Return agent group id, given "agent_id"
##########################################################################
sub get_agent_group ($$) {
	my ($dbh, $agent_id) = @_;
	
	my $group_id = get_db_value ($dbh, "SELECT id_grupo FROM tagente WHERE id_agente = ?", $agent_id);
	return 0 unless defined ($group_id);
	
	return $group_id;
}

##########################################################################
## SUB get_agent_name (agent_id)
## Return agent name, given "agent_id"
##########################################################################
sub get_agent_name ($$) {
	my ($dbh, $agent_id) = @_;
	
	return get_db_value ($dbh, "SELECT nombre FROM tagente WHERE id_agente = ?", $agent_id);
}

##########################################################################
## SUB get_module_agent_id (agent_module_id)
## Return agent id, given "agent_module_id"
##########################################################################
sub get_module_agent_id ($$) {
	my ($dbh, $agent_module_id) = @_;
	
	return get_db_value ($dbh, "SELECT id_agente FROM tagente_modulo WHERE id_agente_modulo = ?", $agent_module_id);
}

##########################################################################
## SUB get_agent_address (id_agente)
## Return agent address, given "agent_id"
##########################################################################
sub get_agent_address ($$) {
	my ($dbh, $agent_id) = @_;
	
	return get_db_value ($dbh, "SELECT direccion FROM tagente WHERE id_agente = ?", $agent_id);
}

##########################################################################
## SUB get_module_name(module_id)
## Return the module name, given "module_id"
##########################################################################
sub get_module_name ($$) {
	my ($dbh, $module_id) = @_;
	
	return get_db_value ($dbh, "SELECT nombre FROM tagente_modulo WHERE id_agente_modulo = ?", $module_id);
}

##########################################################################
## Return module id given the module name and agent id.
##########################################################################
sub get_agent_module_id ($$$) {
	my ($dbh, $module_name, $agent_id) = @_;
	
	my $rc = get_db_value ($dbh, "SELECT id_agente_modulo FROM tagente_modulo WHERE delete_pending = 0 AND nombre = ? AND id_agente = ?", safe_input($module_name), $agent_id);
	return defined ($rc) ? $rc : -1;
}

##########################################################################
## Return template id given the template name.
##########################################################################
sub get_template_id ($$) {
	my ($dbh, $template_name) = @_;
	
	my $rc = get_db_value ($dbh, "SELECT id FROM talert_templates WHERE name = ?", safe_input($template_name));
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
sub get_module_group_id ($$) {
	my ($dbh, $module_group_name) = @_;

	if(!defined($module_group_name) || $module_group_name eq '') {
		return 0;
	}
	
	my $rc = get_db_value ($dbh, "SELECT id_mg FROM tmodule_group WHERE name = ?", safe_input($module_group_name));
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

##########################################################################
## Get a single column returned by an SQL query as a hash reference.
##########################################################################
sub get_db_value ($$;@) {
		my ($dbh, $query, @values) = @_;
		my @rows;

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

##########################################################################
## Get a single row returned by an SQL query as a hash reference. Returns
## -1 on error.
##########################################################################
sub get_db_single_row ($$;@) {
		my ($dbh, $query, @values) = @_;
		my @rows;

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
			if ($RDBMS eq 'oracle') {
				push (@rows, {map { lc ($_) => $row->{$_} } keys (%{$row})});
			} else {
				push (@rows, $row);
			}
		}

		$sth->finish();
		return @rows;
}

##########################################################################
## SQL insert. Returns the ID of the inserted row.
##########################################################################
sub db_insert ($$$;@) {
	my ($dbh, $index, $query, @values) = @_;
	my $insert_id = undef;

	# MySQL
	if ($RDBMS eq 'mysql') {
		$dbh->do($query, undef, @values);
		$insert_id = $dbh->{'mysql_insertid'};
	}
	# PostgreSQL
	elsif ($RDBMS eq 'postgresql') {
		$insert_id = get_db_value ($dbh, $query . ' RETURNING ' . db_reserved_word ($index), @values); 
	}
	# Oracle
	elsif ($RDBMS eq 'oracle') {
		my $sth = $dbh->prepare($query . ' RETURNING ' . db_reserved_word (uc ($index)) . ' INTO ?');
		for (my $i = 0; $i <= $#values; $i++) {
			$sth->bind_param ($i+1, $values[$i]);
		}
		$sth->bind_param_inout($#values + 2, \$insert_id, 99);
		$sth->execute ();
	}

	return $insert_id;
}

##########################################################################
## SQL update. Returns the number of updated rows.
##########################################################################
sub db_update ($$;@) {
	my ($dbh, $query, @values) = @_;
	
	my $rows = $dbh->do($query, undef, @values);
	
	return $rows;
}

##########################################################################
## Return alert template-module ID given the module and template ids.
##########################################################################
sub get_alert_template_module_id ($$$) {
	my ($dbh, $id_module, $id_template) = @_;

	my $rc = get_db_value ($dbh, "SELECT id FROM talert_template_modules WHERE id_agent_module = ? AND id_alert_template = ?", $id_module, $id_template);
	return defined ($rc) ? $rc : -1;
}

##########################################################################
## SQL insert. Returns the ID of the inserted row.
##########################################################################
sub db_process_insert($$$$;@) {
	my ($dbh, $index, $table, $parameters, @values) = @_;
		
	my @columns_array = keys %$parameters;
	my @values_array = values %$parameters;

	if(!defined($table) || $#columns_array == -1) {
		return -1;
		exit;
	}
	
	# Generate the '?' simbols to the Query like '(?,?,?,?,?)'
	my $wildcards = '';
	for (my $i=0; $i<=$#values_array; $i++) {
		if(!defined($values_array[$i])) {
			$values_array[$i] = '';
		}
		if($i > 0 && $i <= $#values_array) {
			$wildcards = $wildcards.',';
		}
		$wildcards = $wildcards.'?';
	}	
	$wildcards = '('.$wildcards.')';
			
	my $columns_string = join('`,`',@columns_array);
	
	my $res = db_insert ($dbh, $index, "INSERT INTO $table (`".$columns_string."`) VALUES ".$wildcards, @values_array);

	return $res;
}

##########################################################################
## SQL update.
##########################################################################
sub db_process_update($$$$$;@) {
	my ($dbh, $table, $parameters, $where_column, $where_value, @values) = @_;
		
	my @columns_array = keys %$parameters;
	my @values_array = values %$parameters;

	if(!defined($table) || $#columns_array == -1) {
		return -1;
		exit;
	}
	
	my $fields = '';
	for (my $i=0; $i<=$#values_array; $i++) {
		if(!defined($values_array[$i])) {
			$values_array[$i] = '';
		}
		if($i > 0 && $i <= $#values_array) {
			$fields = $fields.',';
		}
		$fields = $fields." `$columns_array[$i]` = ?";
	}	
	
	push(@values_array, $where_value);
			
	my $res = db_update ($dbh, "UPDATE $table SET$fields WHERE $where_column = ?", @values_array);

	return $res;
}

##########################################################################
# Add the given address to taddress.
##########################################################################
sub add_address ($$) {
	my ($dbh, $ip_address) = @_;

	return db_insert ($dbh, 'id_a', 'INSERT INTO taddress (ip) VALUES (?)', $ip_address);
}

##########################################################################
# Assign the new address to the agent
##########################################################################
sub add_new_address_agent ($$$) {
	my ($dbh, $addr_id, $agent_id) = @_;
	
	db_do ($dbh, 'INSERT INTO taddress_agent (`id_a`, `id_agent`)
				  VALUES (?, ?)', $addr_id, $agent_id);
}

##########################################################################
# Return the ID of the given address, -1 if it does not exist.
##########################################################################
sub get_addr_id ($$) {
	my ($dbh, $addr) = @_;

	my $addr_id = get_db_value ($dbh, 'SELECT id_a FROM taddress WHERE ip = ?', $addr);
	return (defined ($addr_id) ? $addr_id : -1);
}

##########################################################################
## Generic SQL sentence. 
##########################################################################
sub db_do ($$;@) {
	my ($dbh, $query, @values) = @_;

	#DBI->trace( 3, '/tmp/dbitrace.log' );

	$dbh->do($query, undef, @values);
}

##########################################################################
# Return the ID of the taddress agent with the given IP.
##########################################################################
sub is_agent_address ($$$) {
	my ($dbh, $id_agent, $id_addr) = @_;

	my $id_ag = get_db_value ($dbh, 'SELECT id_ag FROM taddress_agent 
	                                    WHERE id_a = ?
	                                    AND id_agent = ?', $id_addr, $id_agent);
	                                    
	return (defined ($id_ag)) ? $id_ag : 0;
}

##########################################################################
## Escape the given reserved word. 
##########################################################################
sub db_reserved_word ($) {
	my $reserved_word = shift;
	
	# MySQL
	return '`' . $reserved_word . '`' if ($RDBMS eq 'mysql');

	# PostgreSQL
	return '"' . $reserved_word . '"' if ($RDBMS eq 'postgresql' || $RDBMS eq 'oracle');
	
	return $reserved_word;
}

##########################################################################
## Quote the given string. 
##########################################################################
sub db_string ($) {
	my $string = shift;
	
	# MySQL and PostgreSQL
	#return "'" . $string . "'" if ($RDBMS eq 'mysql' || $RDBMS eq 'postgresql' || $RDBMS eq 'oracle');
	
	return "'" . $string . "'";
}

##########################################################################
## Convert TEXT to string when necessary
##########################################################################
sub db_text ($) {
	my $string = shift;
	
	#return $string;
	return " dbms_lob.substr(" . $string . ", 4000, 1)" if ($RDBMS eq 'oracle');
	
	return $string;
}

##########################################################################
## SUB get_alert_template_name(alert_id)
## Return the alert template name, given "alert_id"
##########################################################################
sub get_alert_template_name ($$) {
	my ($dbh, $alert_id) = @_;
	
	return get_db_value ($dbh, "SELECT name FROM talert_templates, talert_template_modules WHERE talert_templates.id = talert_template_modules.id_alert_template AND talert_template_modules.id = ?", $alert_id);
}

##########################################################################
## Concat two strings
##########################################################################
sub db_concat ($$) {
	my ($element1, $element2) = @_;
	
	return " concat(" . $element1 . ", ' '," . $element2 . ") " if ($RDBMS eq 'mysql');
	return " " . $element1 . " || ' ' || " . $element2 . " " if ($RDBMS eq 'oracle' or $RDBMS eq 'postgresql');
	
}

# End of function declaration
# End of defined Code

1;
__END__
