#!/usr/bin/perl
#
# Pandora FMS log migration tool
#
# Artica ST 2017
# 2017/09/07 v1 (c) Fco de Borja Sanchez <fborja.sanchez@artica.es>
#
########################################################################
use strict;
use warnings;

use JSON;
use IO::Socket::INET;
use File::Copy;

use lib '/usr/lib/perl5';

use PandoraFMS::Config;
use PandoraFMS::Tools;
use PandoraFMS::DB;

my $HELP=<<EO_HELP;

Pandora FMS log migration tool (c) Artica ST

  Usage $0 /etc/pandora/pandora_server.conf
	

EO_HELP

########################################################################
# Migrate log data
########################################################################
sub migrate_log {
	my ($pa_config, $dbh, $item) = @_;

	if (!defined($item) || ($item eq "")) {
		logger($pa_config, "Migration tool: Empty item detected", 1);
		return undef;
	}

	my ($agent_id, $id_agent_module_log, $utimestamp) = $item =~ /(\d+?)_(\d+?)_(\d+?).log$/;
	my $fh;

	if ( (! defined($agent_id)) ||
	     (! defined($id_agent_module_log)) ||
	     (! defined($utimestamp)) ||
	     (! -f $item) ) {
		logger($pa_config, "Migration tool: Cannot retrieve log information from [$item]", 1);
		return undef;
	}

	# Get the log module
	my $source_id = get_db_value ($dbh,
		'SELECT source
		FROM tagent_module_log
		WHERE id_agent = ? AND id_agent_module_log = ?', $agent_id, $id_agent_module_log);
	if (! defined ($source_id)) {
		logger($pa_config, "Migration tool: Failed to retrieve source_id [" . $agent_id ."/" . $id_agent_module_log . "]", 1);
		return undef;
	}

	
	my $data;
	# Read data
	{
		local $/ = undef;
		open($fh, $item) or die ("Cannot open file [$item]");
		binmode $fh;
		$data = <$fh>;
		close $fh;
	};

	my $datagram = {
		"utimestamp" => $utimestamp,
		"logcontent" => $data,
		"agent_id"   => $agent_id,
		"source_id"  => $source_id,
	};

	my $sock = $pa_config->{'migration_socket'};
	print $sock encode_json($datagram) . "\n";
	
	move ($item, $item . ".migrated");
}



########################################################################
# Recursive file read
########################################################################
sub recursive_file_apply;
sub recursive_file_apply {
	my ($pa_config, $dbh, $psub, $item) = @_;

	if (-d $item) {
		my $dh;
		opendir($dh,$item) or die ("Cannot open directory [$item]");
		
		my @dirs = readdir($dh); # no need to sort them, the timestamp is in the file name.

		foreach my $object (@dirs) {
 			next if ($object =~ /^\./);
			recursive_file_apply($pa_config, $dbh, $psub, $item . "/" . $object)
		}
		closedir($dh);	
	}
	elsif(-f $item) {
		$psub->($pa_config, $dbh, $item);
		
	}
	else {
		print STDERR "[W] Unknown item [$item]\n";
		logger($pa_config, "Migration tool: Unknown item [$item]", 3);
	}
}

#############################################################
# MAIN
#############################################################

my %pa_config;

if ($#ARGV < 0) {
	print STDERR $HELP;
	exit 1;
}
print STDERR "Starting migration process\n";

pandora_init(\%pa_config, 'Pandora FMS log migration tool');
pandora_load_config (\%pa_config);

# Connect to the DB
my $dbh = db_connect ($pa_config{'dbengine'}, $pa_config{'dbname'}, $pa_config{'dbhost'}, $pa_config{'dbport'},
	$pa_config{'dbuser'}, $pa_config{'dbpass'});
	
# Grab config tokens shared with the console and not in the .conf	
pandora_get_sharedconfig (\%pa_config, $dbh);


if (  (defined($pa_config{'logstash_host'}) && $pa_config{'logstash_host'} ne '')
   && (defined($pa_config{'logstash_port'}) && $pa_config{'logstash_port'} != 0)) {


	if (-d $pa_config{'log_dir'}) {
		$pa_config{'migration_socket'} = new IO::Socket::INET(PeerAddr => $pa_config{'logstash_host'},
						PeerPort => $pa_config{'logstash_port'},
						Proto => 'tcp', Timeout => 1);
	
		if(! defined($pa_config{'migration_socket'})) {
			logger(\%pa_config, "Failed to connect to LogStash server at " . $pa_config{'logstash_host'}, 1);
			exit 1;
		}

		recursive_file_apply(\%pa_config, $dbh, \&migrate_log,$pa_config{'log_dir'});

		$pa_config{'migration_socket'}->close();
	}

}


$dbh->disconnect();

logger(\%pa_config,"Migration completed", 1);
print STDERR "Migration complete\n";

