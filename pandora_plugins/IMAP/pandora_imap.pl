#!/usr/bin/perl
# Pandora FMS Agent Plugin for Monitoring IMAP mails
# Mario Pulido (c) Artica Soluciones Tecnologicas  <info@artica.es> 2013
# v1.0,  03 Jul 2013
# ------------------------------------------------------------------------
use strict;
exit unless load_modules(qw/Getopt::Long Mail::IMAPClient/);

BEGIN {
	if( grep { /^--hires$/ } @ARGV ) {
		eval "use Time::HiRes qw(time);";
		warn "Time::HiRes not installed\n" if $@;
	}
}
########################################################################
# Get options from parameters
########################################################################

Getopt::Long::Configure("bundling");
my $verbose = 0;
my $help_usage = "";
my $imap_server = "";
my $default_imap_port = "143";
my $default_imap_ssl_port = "993";
my $imap_port = "";
my $username = "";
my $password = "";
my $capture_data = "";
my $mailbox = "INBOX";
my @search = ();
my $delete = 0;
my $timeout = 30;
my $max_retries = 1;
my $interval = "";
my $ssl = 0;
my $ssl_ca_file = "";
my $tls = 0;
my $time_hires = "";
my $version = "v1r3";
my $ok;
$ok = Getopt::Long::GetOptions(
	"v|verbose=i"=>\$verbose,"h|help"=>\$help_usage,
	"t|timeout=i"=>\$timeout,
	# imap settings
	"H|hostname=s"=>\$imap_server,"p|port=i"=>\$imap_port,
	"U|username=s"=>\$username,"P|password=s"=>\$password, "m|mailbox=s"=>\$mailbox,
	"imap-check-interval=i"=>\$interval,"imap-retries=i"=>\$max_retries,
	"ssl!"=>\$ssl, "ssl-ca-file=s"=>\$ssl_ca_file, "tls!"=>\$tls,
	# search settings
	"capture-data=s"=>\$capture_data,
	"s|search=s"=>\@search,
	"d|deleted=i"=>\$delete,
	# Time
	"hires"=>\$time_hires,
	);


my @required_module = ();
push @required_module, 'IO::Socket::SSL' if $ssl || $tls;
exit unless load_modules(@required_module);
#########################################################################
# Show if all parameters are not setting.
#########################################################################
if( $help_usage
	||
	( $imap_server eq "" || $username eq "" || $password eq "" || scalar(@search)==0 )
  ) {
	print "Usage: $0 -H host [-p port] -U username -P password -s ( FROM | BODY | SUBJECT | TEXT | YOUNGER | OLDER ) -s 'string' \n";
	print "  [-m mailbox][ --capture-data XXXX ][-d 0][--ssl --ssl-ca-files XXX --tls ][--imap-retries <tries>]\n";
	print "Version $version\n";
	print "PandoraFMS Plugin IMAP Monitoring";
	exit ;
}

########################################################################
# Initialize
########################################################################

my $report = {};
my $time_start = time;

########################################################################
# Connect to IMAP server
########################################################################

print "connecting to server $imap_server\n" if $verbose > 2;
my $imap;
eval {
	local $SIG{ALRM} = sub { die "exceeded timeout $timeout seconds\n" }; # NB: \n required, see `perldoc -f alarm`
	alarm $timeout;
	
	if( $ssl || $tls ) {
		$imap_port = $default_imap_ssl_port unless $imap_port;
		my %ssl_args = ();
		if( length($ssl_ca_file) > 0 ) {
			$ssl_args{SSL_verify_mode} = 1;
			$ssl_args{SSL_ca_file} = $ssl_ca_file;
			$ssl_args{SSL_verifycn_scheme} = 'imap';
			$ssl_args{SSL_verifycn_name} = $imap_server;
		}
		my $socket = IO::Socket::SSL->new(PeerAddr=>"$imap_server:$imap_port", %ssl_args);
		die IO::Socket::SSL::errstr() . " (if you get this only when using both --ssl and --ssl-ca-file, but not when using just --ssl, the server SSL certificate failed validation)" unless $socket;
		$socket->autoflush(1);
		$imap = Mail::IMAPClient->new(Socket=>$socket, Debug => 0 );
		$imap->State(Mail::IMAPClient->Connected);
		$imap->_read_line() if "$Mail::IMAPClient::VERSION" le "2.2.9"; # necessary to remove the server's "ready" line from the input buffer for old versions of Mail::IMAPClient. Using string comparison for the version check because the numeric didn't work on Darwin and for Mail::IMAPClient the next version is 2.3.0 and then 3.00 so string comparison works
		$imap->User($username);
		$imap->Password($password);
		$imap->login() or die "Cannot login: $@";
	}
	else {
		$imap_port = $default_imap_port unless $imap_port;		
		$imap = Mail::IMAPClient->new(Debug => 0 );		
		$imap->Server("$imap_server:$imap_port");
		$imap->User($username);
		$imap->Password($password);
		$imap->connect() or die "$@";
	}


	$imap->Ignoresizeerrors(1);

	alarm 0;
};
if( $@ ) {
	chomp $@;
	print "Could not connect to $imap_server port $imap_port: $@\n";
	exit;	
}
unless( $imap ) {
	print "Could not connect to $imap_server port $imap_port: $@\n";
	exit;
}
my $time_connected = time;+

########################################################################
# Selecting a mailbox. By default INBOX
########################################################################

print "selecting mailbox $mailbox\n" if $verbose > 2;
unless( $imap->select($mailbox) ) {
	print "IMAP RECEIVE CRITICAL - Could not select $mailbox: $@ $!\n";
	if( $verbose > 2 ) {
		print "Mailbox list:\n" . join("\n", $imap->folders) . "\n";
		print "Mailbox separator: " . $imap->separator . "\n";
	}
	$imap->logout();
	exit;
}

##########################################################################
# Searching emails
###########################################################################

my $tries = 0;
my @msgs;
until( scalar(@msgs) != 0 || $tries >= $max_retries ) {
	eval {
		$imap->select( $mailbox );
		print "searching on server\n" if $verbose > 2;
		@msgs = $imap->search(@search);
		die "Invalid search parameters: $@" if $@;
		
	};
	if( $@ ) {
		chomp $@;
		print "Cannot search messages: $@\n";
		$imap->close();
		$imap->logout();
		exit;
	}	
	$report->{found} = scalar(@msgs);
	$tries++;
	sleep $interval unless (scalar(@msgs) != 0 || $tries >= $max_retries);
}

########################################################################
# Capture data in messages
########################################################################

my $captured_max_id = "";
if( $capture_data ) {
	my $max = undef;
	my %captured = ();
	for (my $i=0;$i < scalar(@msgs); $i++) 	{
		my $message = $imap->message_string($msgs[$i]);
		if( $message =~ m/$capture_data/ ) {
			if( !defined($max) || $1 > $max ) {
				$captured{ $i } = 1;
				$max = $1;
				$captured_max_id = $msgs[$i];
				print $1;
			}
		}
	}
}
else{
	#########################################################################
	# Calculate mail number matching
	##########################################################################

	$report->{found} = 0 unless defined $report->{found};
	print "$report->{found} \n";
}
#########################################################################
# Deleting messages
#########################################################################

if( $delete ) {
	print "deleting matching messages\n" if $verbose > 2;
	my $deleted = 0;
	for (my $i=0;$i < scalar(@msgs); $i++) 	{
		$imap->delete_message($msgs[$i]);
		$deleted++;
	}
	$report->{deleted} = $deleted;
	$imap->expunge() if $deleted;
}
else {
	print "Auto deletion disabled\n" if $verbose > 3;
}

##########################################################################
# Deselecting the mailbox and disconnecting the IMAP server
#########################################################################

$imap->close();
print "disconnecting from server\n" if $verbose > 2;
$imap->logout();

#########################################################################
# Load required modules. 
#########################################################################

sub load_modules {
	my @missing_modules = ();
	foreach( @_ ) {
		eval "require $_";
		push @missing_modules, $_ if $@;	
	}
	if( @missing_modules ) {
		print "Missing perl modules: @missing_modules\n";
		return 0;
	}
	return 1;
}

