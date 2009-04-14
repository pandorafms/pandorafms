package PandoraFMS::DB;
##########################################################################
# Database Package
# Pandora FMS. the Flexible Monitoring System. http://www.pandorafms.org
##########################################################################
# Copyright (c) 2009 Ramon Novoa, rnovoa@artica.es
# Copyright (c) 2004-2009 Sancho Lerena, slerena@gmail.com
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
use DBI;

require Exporter;

our @ISA = ("Exporter");
our %EXPORT_TAGS = ( 'all' => [ qw( ) ] );
our @EXPORT_OK = ( @{ $EXPORT_TAGS{'all'} } );
our @EXPORT = qw( 
		db_connect
		db_disconnect
		db_do
		db_insert
		get_agent_id
		get_agent_name
		get_db_rows
		get_db_single_row
		get_db_value
		get_module_id
		get_nc_profile_name
		get_server_id
		is_group_disabled
	);

##########################################################################
## Connect to the DB.
##########################################################################
sub db_connect ($$$$$$) {
	my ($rdbms, $db_name, $db_host, $db_port, $db_user, $db_pass) = @_;

	if ($rdbms eq 'mysql') {
		return DBI->connect("DBI:mysql:$db_name:$db_host:3306", $db_user, $db_pass, { RaiseError => 1, AutoCommit => 1 });
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
## Return agent ID given the agent name.
##########################################################################
sub get_agent_id ($$) {
	my ($dbh, $agent_name) = @_;

	my $rc = get_db_value ($dbh, "SELECT id_agente FROM tagente WHERE nombre = ? OR direccion = ?", $agent_name, $agent_name);
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
## SUB dame_agente_nombre (id_agente)
## Return agent name, given "id_agente"
##########################################################################
sub get_agent_name ($$) {
	my ($dbh, $agent_id) = @_;
	
	return get_db_value ($dbh, "SELECT nombre FROM tagente WHERE id_agente = ?", $agent_id);
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

	my $rc = get_db_value ($dbh, "SELECT id_tipo FROM ttipo_modulo WHERE nombre = ?", $module_name);
	return defined ($rc) ? $rc : -1;
}

##########################################################################
## Return a network component's profile name given it's ID.
##########################################################################
sub get_nc_profile_name ($$) {
	my ($dbh, $nc_id) = @_;
	
	return get_db_value ($dbh, "SELECT * FROM tnetwork_profile WHERE id_np = ?", $nc_id);
}

##########################################################################
## Get a single column returned by an SQL query as a hash reference.
##########################################################################
sub get_db_value ($$;@) {
		my ($dbh, $query, @values) = @_;

		# Cache statements
		my $sth = $dbh->prepare_cached($query);
		$sth->execute(@values);

		# No results
		if ($sth->rows == 0) {
			$sth->finish();
			return undef;
		}
		
		my $row = $sth->fetchrow_arrayref();
		$sth->finish();
		return $row->[0];
}

##########################################################################
## Get a single row returned by an SQL query as a hash reference. Returns
## -1 on error.
##########################################################################
sub get_db_single_row ($$;@) {
		my ($dbh, $query, @values) = @_;

		# Cache statements
		my $sth = $dbh->prepare_cached($query);

		$sth->execute(@values);

		# No results
		if ($sth->rows == 0) {
			$sth->finish();
			return undef;
		}
		
		my $row = $sth->fetchrow_hashref();
		$sth->finish();
		return $row;
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
## SQL insert. Returns the ID of the inserted row.
##########################################################################
sub db_insert ($$;@) {
	my ($dbh, $query, @values) = @_;

	$dbh->do($query, undef, @values);
	return $dbh->{'mysql_insertid'};
}

##########################################################################
## Generic SQL sentence. 
##########################################################################
sub db_do ($$;@) {
	my ($dbh, $query, @values) = @_;

	$dbh->do($query, undef, @values);
}

# End of function declaration
# End of defined Code

1;
__END__
