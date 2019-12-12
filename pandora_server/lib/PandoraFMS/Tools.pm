package PandoraFMS::Tools;
########################################################################
# Tools Package
# Pandora FMS. the Flexible Monitoring System. http://www.pandorafms.org
########################################################################
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
 
use warnings;
use Time::Local;
use POSIX qw(setsid strftime);
use POSIX;
use HTML::Entities;
use Encode;
use Socket qw(inet_ntoa inet_aton);
use Sys::Syslog;
use Scalar::Util qw(looks_like_number);
use LWP::UserAgent;
use threads;
use threads::shared;

use lib '/usr/lib/perl5';
use PandoraFMS::Sendmail;

# New in 3.2. Used to sendmail internally, without external scripts
# use Module::Loaded;

# Used to calculate the MD5 checksum of a string
use constant MOD232 => 2**32;
# 2 to the power of 32.
use constant POW232 => 2**32;

# UTF-8 flags deletion from multibyte characters when files are opened.
use open OUT => ":utf8";
use open ":std";

require Exporter;

our @ISA = ("Exporter");
our %EXPORT_TAGS = ( 'all' => [ qw( ) ] );
our @EXPORT_OK = ( @{ $EXPORT_TAGS{'all'} } );
our @EXPORT = qw(
	DATASERVER
	NETWORKSERVER
	SNMPCONSOLE
	DISCOVERYSERVER
	PLUGINSERVER
	PREDICTIONSERVER
	WMISERVER
	EXPORTSERVER
	INVENTORYSERVER
	WEBSERVER
	EVENTSERVER
	ICMPSERVER
	SNMPSERVER
	SATELLITESERVER
	MFSERVER
	TRANSACTIONALSERVER
	SYNCSERVER
	SYSLOGSERVER
	WUXSERVER
	PROVISIONINGSERVER
	MIGRATIONSERVER
	METACONSOLE_LICENSE
	OFFLINE_LICENSE
	DISCOVERY_HOSTDEVICES
	DISCOVERY_HOSTDEVICES_CUSTOM
	DISCOVERY_CLOUD_AWS
	DISCOVERY_APP_VMWARE
	DISCOVERY_APP_MYSQL
	DISCOVERY_APP_ORACLE
	DISCOVERY_CLOUD_AWS_EC2
	DISCOVERY_CLOUD_AWS_RDS
	DISCOVERY_CLOUD_AZURE_COMPUTE
	DISCOVERY_DEPLOY_AGENTS
	$DEVNULL
	$OS
	$OS_VERSION
	RECOVERED_ALERT
	FIRED_ALERT
	MODULE_NORMAL
	MODULE_CRITICAL
	MODULE_WARNING
	MODULE_UNKNOWN
	MODULE_NOTINIT
	$THRRUN
	api_call_url
	cron_get_closest_in_range
	cron_next_execution
	cron_next_execution_date
	cron_check_syntax
	pandora_daemonize
	logger
	pandora_rotate_logfile
	limpia_cadena
	md5check
	float_equal
	sqlWrap
	is_numeric
	is_metaconsole
	is_offline
	to_number
	clean_blank
	credential_store_get_key
	pandora_sendmail
	pandora_trash_ascii
	enterprise_hook
	enterprise_load
	print_message
	get_tag_value
	disk_free
	load_average
	free_mem
	md5
	md5_init
	pandora_ping
	pandora_ping_latency
	pandora_block_ping
	resolve_hostname
	ticks_totime
	safe_input
	safe_output
	month_have_days
	translate_obj
	valid_regex
	read_file
	set_file_permissions
	uri_encode
	check_server_threads
	start_server_thread
	stop_server_threads
	generate_agent_name_hash
	long_to_ip
	ip_to_long
	get_enabled_servers
	dateTimeToTimestamp
	get_user_agent
);

# ID of the different servers
use constant DATASERVER => 0;
use constant NETWORKSERVER => 1;
use constant SNMPCONSOLE => 2;
use constant DISCOVERYSERVER => 3;
use constant PLUGINSERVER => 4;
use constant PREDICTIONSERVER => 5;
use constant WMISERVER => 6;
use constant EXPORTSERVER => 7;
use constant INVENTORYSERVER => 8;
use constant WEBSERVER => 9;
use constant EVENTSERVER => 10;
use constant ICMPSERVER => 11;
use constant SNMPSERVER => 12;
use constant SATELLITESERVER => 13;
use constant TRANSACTIONALSERVER => 14;
use constant MFSERVER => 15;
use constant SYNCSERVER => 16;
use constant WUXSERVER => 17;
use constant SYSLOGSERVER => 18;
use constant PROVISIONINGSERVER => 19;
use constant MIGRATIONSERVER => 20;

# Module status
use constant MODULE_NORMAL => 0;
use constant MODULE_CRITICAL => 1;
use constant MODULE_WARNING => 2;
use constant MODULE_UNKNOWN => 3;
use constant MODULE_NOTINIT => 4;

# Mask for a metaconsole license type
use constant METACONSOLE_LICENSE => 0x01;

# Mask for an offline license type
use constant OFFLINE_LICENSE => 0x02;

# Alert modes
use constant RECOVERED_ALERT => 0;
use constant FIRED_ALERT => 1;

# Discovery task types
use constant DISCOVERY_HOSTDEVICES => 0;
use constant DISCOVERY_HOSTDEVICES_CUSTOM => 1;
use constant DISCOVERY_CLOUD_AWS => 2;
use constant DISCOVERY_APP_VMWARE => 3;
use constant DISCOVERY_APP_MYSQL => 4;
use constant DISCOVERY_APP_ORACLE => 5;
use constant DISCOVERY_CLOUD_AWS_EC2 => 6;
use constant DISCOVERY_CLOUD_AWS_RDS => 7;
use constant DISCOVERY_CLOUD_AZURE_COMPUTE => 8;
use constant DISCOVERY_DEPLOY_AGENTS => 9;

# Set OS, OS version and /dev/null
our $OS = $^O;
our $OS_VERSION = "unknown";
our $DEVNULL = '/dev/null';
if ($OS eq 'linux') {
	$OS_VERSION = `lsb_release -sd 2>/dev/null`;
} elsif ($OS eq 'aix') {
	$OS_VERSION = "$2.$1" if (`uname -rv` =~ /\s*(\d)\s+(\d)\s*/);
} elsif ($OS =~ /win/i) {
	$OS = "windows";
	$OS_VERSION = `ver`;
	$OS_VERSION =~ s/[^[:ascii:]]//g; 
	$DEVNULL = '/Nul';
} elsif ($OS eq 'freebsd') {
	$OS_VERSION = `uname -r`;
}
chomp($OS_VERSION);

# Entity to character mapping. Contains a few tweaks to make it backward compatible with the previous safe_input implementation.
my %ENT2CHR = (
	'#x00' => chr(0), 
	'#x01' => chr(1), 
	'#x02' => chr(2), 
	'#x03' => chr(3), 
	'#x04' => chr(4), 
	'#x05' => chr(5), 
	'#x06' => chr(6), 
	'#x07' => chr(7), 
	'#x08' => chr(8), 
	'#x09' => chr(9), 
	'#x0a' => chr(10), 
	'#x0b' => chr(11), 
	'#x0c' => chr(12), 
	'#x0d' => chr(13), 
	'#x0e' => chr(14), 
	'#x0f' => chr(15), 
	'#x10' => chr(16), 
	'#x11' => chr(17), 
	'#x12' => chr(18), 
	'#x13' => chr(19), 
	'#x14' => chr(20), 
	'#x15' => chr(21), 
	'#x16' => chr(22), 
	'#x17' => chr(23), 
	'#x18' => chr(24), 
	'#x19' => chr(25), 
	'#x1a' => chr(26), 
	'#x1b' => chr(27), 
	'#x1c' => chr(28), 
	'#x1d' => chr(29), 
	'#x1e' => chr(30), 
	'#x1f' => chr(31), 
	'#x20' => chr(32), 
	'quot' => chr(34), 
	'amp' => chr(38), 
	'#039' => chr(39), 
	'#40' => chr(40), 
	'#41' => chr(41), 
	'lt' => chr(60), 
	'gt' => chr(62), 
	'#92' => chr(92), 
	'#x80' => chr(128), 
	'#x81' => chr(129), 
	'#x82' => chr(130), 
	'#x83' => chr(131), 
	'#x84' => chr(132), 
	'#x85' => chr(133), 
	'#x86' => chr(134), 
	'#x87' => chr(135), 
	'#x88' => chr(136), 
	'#x89' => chr(137), 
	'#x8a' => chr(138), 
	'#x8b' => chr(139), 
	'#x8c' => chr(140), 
	'#x8d' => chr(141), 
	'#x8e' => chr(142), 
	'#x8f' => chr(143), 
	'#x90' => chr(144), 
	'#x91' => chr(145), 
	'#x92' => chr(146), 
	'#x93' => chr(147), 
	'#x94' => chr(148), 
	'#x95' => chr(149), 
	'#x96' => chr(150), 
	'#x97' => chr(151), 
	'#x98' => chr(152), 
	'#x99' => chr(153), 
	'#x9a' => chr(154), 
	'#x9b' => chr(155), 
	'#x9c' => chr(156), 
	'#x9d' => chr(157), 
	'#x9e' => chr(158), 
	'#x9f' => chr(159), 
	'#xa0' => chr(160), 
	'#xa1' => chr(161), 
	'#xa2' => chr(162), 
	'#xa3' => chr(163), 
	'#xa4' => chr(164), 
	'#xa5' => chr(165), 
	'#xa6' => chr(166), 
	'#xa7' => chr(167), 
	'#xa8' => chr(168), 
	'#xa9' => chr(169), 
	'#xaa' => chr(170), 
	'#xab' => chr(171), 
	'#xac' => chr(172), 
	'#xad' => chr(173), 
	'#xae' => chr(174), 
	'#xaf' => chr(175), 
	'#xb0' => chr(176), 
	'#xb1' => chr(177), 
	'#xb2' => chr(178), 
	'#xb3' => chr(179), 
	'#xb4' => chr(180), 
	'#xb5' => chr(181), 
	'#xb6' => chr(182), 
	'#xb7' => chr(183), 
	'#xb8' => chr(184), 
	'#xb9' => chr(185), 
	'#xba' => chr(186), 
	'#xbb' => chr(187), 
	'#xbc' => chr(188), 
	'#xbd' => chr(189), 
	'#xbe' => chr(190), 
	'Aacute' => chr(193), 
	'Auml' => chr(196), 
	'Eacute' => chr(201), 
	'Euml' => chr(203), 
	'Iacute' => chr(205), 
	'Iuml' => chr(207), 
	'Ntilde' => chr(209), 
	'Oacute' => chr(211), 
	'Ouml' => chr(214), 
	'Uacute' => chr(218), 
	'Uuml' => chr(220), 
	'aacute' => chr(225), 
	'auml' => chr(228), 
	'eacute' => chr(233), 
	'euml' => chr(235), 
	'iacute' => chr(237), 
	'iuml' => chr(239), 
	'ntilde' => chr(241), 
	'oacute' => chr(243), 
	'ouml' => chr(246), 
	'uacute' => chr(250), 
	'uuml' => chr(252), 
);

# Construct the character to entity mapping.
my %CHR2ENT;
while (my ($ent, $chr) = each(%ENT2CHR)) {
	$CHR2ENT{$chr} = "&" . $ent . ";";
}

# Threads started by the Pandora FMS Server.
my @ServerThreads;

# Keep threads running.
our $THRRUN :shared = 1;

##########################################################################
## Reads a file and returns entire content or undef if error.
##########################################################################
sub read_file {
	my $path = shift;

	my $_FILE;
	if( !open($_FILE, "<", $path) ) {
		# failed to open, return undef
		return undef;
	}

	# Slurp configuration file content.
	my $content = do { local $/; <$_FILE> };

	# Close file
	close($_FILE);

	return $content;
}


###############################################################################
# Sets user:group owner for the given file
###############################################################################
sub set_file_permissions($$;$) {
	my ($pa_config, $file, $grants) = @_;
	if ($^O !~ /win/i ) { # Only for Linux environments
		eval {
			if (defined ($grants)) {
				$grants = oct($grants);
			}
			else {
				$grants = oct("0777");
			}
			my $uid  = getpwnam($pa_config->{'user'});
			my $gid  = getgrnam($pa_config->{'group'});
			my $perm = $grants & (~oct($pa_config->{'umask'}));

			chown $uid, $gid, $file;
			chmod ( $perm, $file );
		};
		if ($@) {
			# Ignore error
		}
	}
}


########################################################################
## SUB pandora_trash_ascii 
# Generate random ascii strings with variable lenght
########################################################################

sub pandora_trash_ascii {
	my $config_depth = $_[0];
	my $a;
	my $output;
	
	for ($a=0;$a<$config_depth;$a++){
		$output = $output.chr(int(rand(25)+97));
	}
	return $output
}

########################################################################
## Convert the $value encode in html entity to clear char string.
########################################################################
sub safe_input($) {
	my $value = shift;

	return "" unless defined($value);

	$value =~ s/([\x00-\xFF])/$CHR2ENT{$1}||$1/ge;
	
	return $value;
}

########################################################################
## Convert the html entities to value encode to rebuild char string.
########################################################################
sub safe_output($) {
	my $value = shift;

	return "" unless defined($value);

	_decode_entities ($value, \%ENT2CHR);

	return $value;
}

########################################################################
# Sub daemonize ()
# Put program in background (for daemon mode)
########################################################################

sub pandora_daemonize {
	my $pa_config = $_[0];
	open STDIN, "$DEVNULL"		or die "Can't read $DEVNULL: $!";
	open STDOUT, ">>$DEVNULL"	or die "Can't write to $DEVNULL: $!";
	open STDERR, ">>$DEVNULL"	or die "Can't write to $DEVNULL: $!";
	chdir '/tmp'					or die "Can't chdir to /tmp: $!";
	defined(my $pid = fork)		or die "Can't fork: $!";
	exit if $pid;
	setsid							or die "Can't start a new session: $!";
	
	# Store PID of this process in file presented by config token
	if ($pa_config->{'PID'} ne "") {
		if ( -e $pa_config->{'PID'} && open (FILE, $pa_config->{'PID'})) {
			$pid = <FILE> + 0;
			close FILE;
			
			# check if pandora_server is running
			if (kill (0, $pid)) {
				die "[FATAL] " . pandora_get_initial_product_name() . " Server already running, pid: $pid.";
			}
			logger ($pa_config, '[W] Stale PID file, overwriting.', 1);
		}
		umask 0022;
		open (FILE, "> ".$pa_config->{'PID'}) or die "[FATAL] Cannot open PIDfile at ".$pa_config->{'PID'};
		print FILE "$$";
		close (FILE);
	}
	umask 0007;
}


# -------------------------------------------+
# Pandora other General functions |
# -------------------------------------------+

########################################################################
# SUB credential_store_get_key
# Retrieve all information related to target identifier.
# param1 - config hash
# param2 - dbh link
# param3 - string identifier
########################################################################
sub credential_store_get_key($$$) {
	my ($pa_config, $dbh, $identifier) = @_;

	my $sql = 'SELECT * FROM tcredential_store WHERE identifier = ?';
	my $key = PandoraFMS::DB::get_db_single_row($dbh, $sql, $identifier);

	return {
		'username' => PandoraFMS::Core::pandora_output_password(
			$pa_config,
			$key->{'username'}
		),
		'password' => PandoraFMS::Core::pandora_output_password(
			$pa_config,
			$key->{'password'}
		),
		'extra_1' => $key->{'extra_1'},
		'extra_2' => $key->{'extra_2'},
	};

}

########################################################################
# SUB pandora_sendmail
# Send a mail, connecting directly to MTA
# param1 - config hash
# param2 - Destination email addres
# param3 - Email subject
# param4 - Email Message body
# param4 - Email content type
########################################################################

sub pandora_sendmail {
	
	my $pa_config = $_[0];
	my $to_address = $_[1];
	my $subject = $_[2];
	my $message = $_[3];
	my $content_type = $_[4];
	
	$subject = decode_entities ($subject);

	# If content type is defined, the message will be custom
	if (! defined($content_type)) {
		$message = decode_entities ($message);
	}
	
	my %mail = ( To	=> $to_address,
		Message		=> $message,
		Subject		=> encode('MIME-Header', $subject),
		'X-Mailer'	=> $pa_config->{"rb_product_name"},
		Smtp		=> $pa_config->{"mta_address"},
		Port		=> $pa_config->{"mta_port"},
		From		=> $pa_config->{"mta_from"},
		Encryption	=> $pa_config->{"mta_encryption"},
	);

	# Set the timeout.
	$PandoraFMS::Sendmail::mailcfg{'timeout'} = $pa_config->{"tcp_timeout"};

	# Enable debugging.
	$PandoraFMS::Sendmail::mailcfg{'debug'} = $pa_config->{"verbosity"};
	
	if (defined($content_type)) {
		$mail{'Content-Type'} = $content_type;
	}

	# Check if message has non-ascii chars.
	# non-ascii chars should be encoded in UTF-8.
	if ($message =~ /[^[:ascii:]]/o && !defined($content_type)) {
		$mail{Message} = encode("UTF-8", $mail{Message});
		$mail{'Content-Type'} = 'text/plain; charset="UTF-8"';
	}
	
	if ($pa_config->{"mta_user"} ne ""){
		$mail{auth} = {user=>$pa_config->{"mta_user"}, password=>$pa_config->{"mta_pass"}, method=>$pa_config->{"mta_auth"}, required=>1 };
	}

	eval {
		if (!sendmail(%mail)) { 
			logger ($pa_config, "[ERROR] Sending email to $to_address with subject $subject", 1);
			logger ($pa_config, "ERROR Code: $Mail::Sendmail::error", 5) if (defined($Mail::Sendmail::error));
		}
	};
}

##########################################################################
# SUB is_numeric
# Return TRUE if given argument is numeric
##########################################################################

sub is_numeric {
	my $val = $_[0];
	
	if (!defined($val)){
		return 0;
	}
	# Replace "," for "."
	$val =~ s/\,/\./;
	
	my $DIGITS = qr{ \d+ (?: [.] \d*)? | [.] \d+ }xms;
	my $SIGN   = qr{ [+-] }xms;
	my $NUMBER = qr{ ($SIGN?) ($DIGITS) }xms;
	if ( $val !~ /^${NUMBER}$/ ) {
		#Non-numeric, or maybe... leave looks_like_number try
		return looks_like_number($val);
	}
	else {
		return 1;   #Numeric
	}
}

##########################################################################
# SUB md5check (param_1, param_2)
# Verify MD5 file .checksum
##########################################################################
# param_1 : Name of data file
# param_2 : Name of md5 file

sub md5check {
	my $buf;
	my $buf2;
	my $file = $_[0];
	my $md5file = $_[1];
	open(FILE, $file) or return 0;
	binmode(FILE);
	my $md5 = Digest::MD5->new;
	while (<FILE>) {
		$md5->add($_);
	}
	close(FILE);
	$buf2 = $md5->hexdigest;
	open(FILE,$md5file) or return 0;
	while (<FILE>) {
		$buf = $_;
	}
	close (FILE);
	$buf=uc($buf);
	$buf2=uc($buf2);
	if ($buf =~ /$buf2/ ) {
		#print "MD5 Correct";
		return 1;
	}
	else {
		#print "MD5 Incorrect";
		return 0;
	}
}

########################################################################
# SUB logger (pa_config, message, level)
# Log to file
########################################################################
sub logger ($$;$) {
	my ($pa_config, $message, $level) = @_;

	# Clean any string and ready to be printed in screen/file
	$message = safe_output ($message);

	$level = 1 unless defined ($level);
	return if (!defined ($pa_config->{'verbosity'}) || $level > $pa_config->{'verbosity'});

	if (!defined($pa_config->{'log_file'})) {
		print strftime ("%Y-%m-%d %H:%M:%S", localtime()) . " [V". $level ."] " . $message . "\n";
		return;
	}

	# Get the log file (can be a regular file or 'syslog')
	my $file = $pa_config->{'log_file'};

	# Syslog
	if ($file eq 'syslog') {
		
		# Set the security level
		my $security_level = 'info';
		if ($level < 2) {
			$security_level = 'crit';
		} elsif ($level < 5) {
			$security_level = 'warn';
		}

		openlog('pandora_server', 'ndelay', 'daemon');
		syslog($security_level, $message);
		closelog();
	} else {
		# Obtain the script that invoke this log
		my $parent_caller = "";
		$parent_caller = ( caller(2) )[1];
		if (defined $parent_caller) {
			$parent_caller = (split '/', $parent_caller)[-1];
			$parent_caller =~ s/\.[^.]+$//;
			$parent_caller = " " . $parent_caller . ": ";
		} else {
			$parent_caller = " ";
		}
		open (FILE, ">> $file") or die "[FATAL] Could not open logfile '$file'";
		# Get an exclusive lock on the file (LOCK_EX)
		flock (FILE, 2);
		print FILE strftime ("%Y-%m-%d %H:%M:%S", localtime()) . $parent_caller . (defined($pa_config->{'servername'}) ? $pa_config->{'servername'} : '') . " [V". $level ."] " . $message . "\n";
		close (FILE);
	}
}

########################################################################
# SUB pandora_rotate_log (pa_config)
# Log to file
########################################################################
sub pandora_rotate_logfile ($) {
	my ($pa_config) = @_;

	my $file = $pa_config->{'log_file'};

	# Log File Rotation
	if ($file ne 'syslog' && -e $file && (stat($file))[7] > $pa_config->{'max_log_size'}) {
		foreach my $i (reverse 1..$pa_config->{'max_log_generation'}) {
			rename ($file . "." . ($i - 1), $file . "." . $i);
		}
		rename ($file, "$file.0");
	
	}
}

########################################################################
# limpia_cadena (string) - Purge a string for any forbidden characters (esc, etc)
########################################################################
sub limpia_cadena {
	my $micadena;
	$micadena = $_[0];
	if (defined($micadena)){
		$micadena =~ s/[^\-\:\;\.\,\_\s\a\*\=\(\)a-zA-Z0-9]//g;
		$micadena =~ s/[\n\l\f]//g;
		return $micadena;
	}
	else {
		return "";
	}
}

########################################################################
# clean_blank (string) - Remove leading and trailing blanks
########################################################################
sub clean_blank {
	my $input = $_[0];
	$input =~ s/^\s+//g;
	$input =~ s/\s+$//g;
	return $input;
}

########################################################################################
# sub sqlWrap(texto)
# Elimina comillas y caracteres problematicos y los sustituye por equivalentes
########################################################################################

sub sqlWrap {
	my $toBeWrapped = shift(@_);
	if (defined $toBeWrapped){
		$toBeWrapped =~ s/\'/\\\'/g;
		$toBeWrapped =~ s/\"/\\\'/g; # " This is for highlighters that don't understand escaped quotes
		return "'".$toBeWrapped."'";
	}
}

##########################################################################
# sub float_equal (num1, num2, decimals)
# This function make possible to compare two float numbers, using only x decimals
# in comparation.
# Taken from Perl Cookbook, O'Reilly. Thanks, guys.
##########################################################################
sub float_equal {
	my ($A, $B, $dp) = @_;
	return sprintf("%.${dp}g", $A) eq sprintf("%.${dp}g", $B);
}

##########################################################################
# Tries to load the PandoraEnterprise module. Must be called once before
# enterprise_hook ().
##########################################################################
sub enterprise_load ($) {
	my $pa_config = shift;
	
	# Check dependencies
	
	# Already loaded
	#return 1 if (is_loaded ('PandoraFMS::Enterprise'));
	
	# Try to load the module
	if ($^O eq 'MSWin32') {
		# If the Windows service dies the service is stopped, even inside an eval ($RUN is set to 0)!
		eval 'local $SIG{__DIE__}; require PandoraFMS::Enterprise;';
	}
	else {
		eval 'require PandoraFMS::Enterprise;';
	}

	# Ops
	if ($@) {
		# Enterprise.pm not found.
		return 0 if ($@ =~ m/PandoraFMS\/Enterprise\.pm.*\@INC/);

		open (STDERR, ">> " . $pa_config->{'errorlog_file'});
		print STDERR $@;
		close (STDERR);
		return 0;
	}
	
	# Initialize the enterprise module.
	PandoraFMS::Enterprise::init($pa_config);
	
	return 1;
}

##########################################################################
# Tries to call a PandoraEnterprise function. Returns undef if unsuccessful.
##########################################################################
sub enterprise_hook ($$) {
	my $func = shift;
	my @args = @{shift ()};

	# Temporarily disable strict refs
	no strict 'refs';

	# Prepend the package name
	$func = 'PandoraFMS::Enterprise::' . $func;
	
	# undef is returned only if the enterprise function was not found
	return undef unless (defined (&$func));

	# Try to call the function
	my $output = eval { &$func (@args); };

	# Discomment to debug.
	if ($@) {
		print STDERR $@;
	}

	# Check for errors
	#return undef if ($@);
	return '' unless defined ($output);

	return $output;
}

########################################################################
# Prints a message to STDOUT at the given log level.
########################################################################
sub print_message ($$$) {
	my ($pa_config, $message, $log_level) = @_;
	
	print STDOUT $message . "\n" if ($pa_config->{'verbosity'} >= $log_level);
}

##########################################################################
# Returns the value of an XML tag from a hash returned by XMLin (one level
# depth).
##########################################################################
sub get_tag_value ($$$;$) {
	my ($hash_ref, $tag, $def_value, $all_array) = @_;
	$all_array = 0 unless defined ($all_array);
	
	return $def_value unless defined ($hash_ref->{$tag}) and ref ($hash_ref->{$tag});
	
	# If all array is required, returns the array
	return $hash_ref->{$tag} if ($all_array == 1);
	# Return the first found value
	foreach my $value (@{$hash_ref->{$tag}}) {
		
		# If the tag is defined but has no value a ref to an empty hash is returned by XML::Simple
		return $value unless ref ($value);
	}
	
	return $def_value;
}

########################################################################
# Initialize some variables needed by the MD5 algorithm.
# See http://en.wikipedia.org/wiki/MD5#Pseudocode.
########################################################################
my (@R, @K);
sub md5_init () {
	
	# R specifies the per-round shift amounts
	@R = (7, 12, 17, 22,  7, 12, 17, 22,  7, 12, 17, 22,  7, 12, 17, 22,
		  5,  9, 14, 20,  5,  9, 14, 20,  5,  9, 14, 20,  5,  9, 14, 20,
		  4, 11, 16, 23,  4, 11, 16, 23,  4, 11, 16, 23,  4, 11, 16, 23,
		  6, 10, 15, 21,  6, 10, 15, 21,  6, 10, 15, 21,  6, 10, 15, 21);
	
	# Use binary integer part of the sines of integers (radians) as constants
	for (my $i = 0; $i < 64; $i++) {
		$K[$i] = floor(abs(sin($i + 1)) * MOD232);
	}
}

###############################################################################
# Return the MD5 checksum of the given string. 
# Pseudocode from http://en.wikipedia.org/wiki/MD5#Pseudocode.
###############################################################################
sub md5 ($) {
	my $str = shift;

	if (!defined($str)){
		return "";
	}

	# Initialize once.
	md5_init() if (!defined($R[0]));

	# Note: All variables are unsigned 32 bits and wrap modulo 2^32 when calculating

	# Initialize variables
	my $h0 = 0x67452301;
	my $h1 = 0xEFCDAB89;
	my $h2 = 0x98BADCFE;
	my $h3 = 0x10325476;

	# Pre-processing
	my $msg = unpack ("B*", pack ("A*", $str));
	my $bit_len = length ($msg);

	# Append "1" bit to message
	$msg .= '1';

	# Append "0" bits until message length in bits ≡ 448 (mod 512)
	$msg .= '0' while ((length ($msg) % 512) != 448);

	# Append bit /* bit, not byte */ length of unpadded message as 64-bit little-endian integer to message
	$msg .= unpack ("B64", pack ("VV", $bit_len));

	# Process the message in successive 512-bit chunks
	for (my $i = 0; $i < length ($msg); $i += 512) {

		my @w;
		my $chunk = substr ($msg, $i, 512);

		# Break chunk into sixteen 32-bit little-endian words w[i], 0 <= i <= 15
		for (my $j = 0; $j < length ($chunk); $j += 32) {
			push (@w, unpack ("V", pack ("B32", substr ($chunk, $j, 32))));
		}

		# Initialize hash value for this chunk
		my $a = $h0;
		my $b = $h1;
		my $c = $h2;
		my $d = $h3;
		my $f;
		my $g;

		# Main loop
		for (my $y = 0; $y < 64; $y++) {
			if ($y <= 15) {
				$f = $d ^ ($b & ($c ^ $d));
				$g = $y;
			}
			elsif ($y <= 31) {
				$f = $c ^ ($d & ($b ^ $c));
				$g = (5 * $y + 1) % 16;
			}
			elsif ($y <= 47) {
				$f = $b ^ $c ^ $d;
				$g = (3 * $y + 5) % 16;
			}
			else {
				$f = $c ^ ($b | (0xFFFFFFFF & (~ $d)));
				$g = (7 * $y) % 16;
			}

			my $temp = $d;
			$d = $c;
			$c = $b;
			$b = ($b + leftrotate (($a + $f + $K[$y] + $w[$g]) % MOD232, $R[$y])) % MOD232;
			$a = $temp;
		}

		# Add this chunk's hash to result so far
		$h0 = ($h0 + $a) % MOD232;
		$h1 = ($h1 + $b) % MOD232;
		$h2 = ($h2 + $c) % MOD232;
		$h3 = ($h3 + $d) % MOD232;
	}

	# Digest := h0 append h1 append h2 append h3 #(expressed as little-endian)
	return unpack ("H*", pack ("V", $h0)) . unpack ("H*", pack ("V", $h1)) . unpack ("H*", pack ("V", $h2)) . unpack ("H*", pack ("V", $h3));
}

###############################################################################
# MD5 leftrotate function. See http://en.wikipedia.org/wiki/MD5#Pseudocode.
###############################################################################
sub leftrotate ($$) {
	my ($x, $c) = @_;

	return (0xFFFFFFFF & ($x << $c)) | ($x >> (32 - $c));
}

##########################################################################
## Convert a date (yyy-mm-ddThh:ii:ss) to Timestamp.
##########################################################################
sub dateTimeToTimestamp {
	$_[0] =~ /(\d{4})-(\d{2})-(\d{2})([ |T])(\d{2}):(\d{2}):(\d{2})/;
	my($year, $mon, $day, $GMT, $hour, $min, $sec) = ($1, $2, $3, $4, $5, $6, $7);
	#UTC
	return timegm($sec, $min, $hour, $day, $mon - 1, $year - 1900);
	#BST
	#print "BST\t" . mktime($sec, $min, $hour, $day, $mon - 1, $year - 1900, 0, 0) . "\n";
}

##############################################################################
# Below some "internal" functions for automonitoring feature
# TODO: Implement the same for other systems like Solaris or BSD
##############################################################################

sub disk_free ($) {
	my $target = $_[0];

	my $OSNAME = $^O;

	# Get the free disk on data_in folder unit
	if ($OSNAME eq "MSWin32") {
		# Check relative path
		my $unit;
		if ($target =~ m/^([a-zA-Z]):/gi) {
			$unit = $1;
		} else {
			return;
		}
		# Get the free space of unit found
		my $all_disk_info = `wmic logicaldisk get caption, freespace`;
		if ($all_disk_info =~ m/$unit:\D*(\d+)/gmi){
			return $1/(1024*1024);
		}
		return;
	}
	# Try to use df command with Posix parameters... 
	my $command = "df -k -P ".$target." | tail -1 | awk '{ print \$4/1024}'";
	my $output = `$command`;
	return $output;
}

sub load_average {
	my $load_average;

	my $OSNAME = $^O;

	if ($OSNAME eq "freebsd"){
		$load_average = ((split(/\s+/, `/sbin/sysctl -n vm.loadavg`))[1]);
	} elsif ($OSNAME eq "MSWin32") {
		# Windows hasn't got load average.
		$load_average = `powershell "(Get-WmiObject win32_processor | Measure-Object -property LoadPercentage -Average).average"`;
		chop($load_average);
	}
	# by default LINUX calls
	else {
		$load_average = `cat /proc/loadavg | awk '{ print \$1 }'`;
	}
	return $load_average;
}

sub free_mem {
	my $free_mem;

	my $OSNAME = $^O;

	if ($OSNAME eq "freebsd"){
		my ($pages_free, $page_size) = `/sbin/sysctl -n vm.stats.vm.v_page_size vm.stats.vm.v_free_count`;
		# in kilobytes
		$free_mem = $pages_free * $page_size / 1024;

	}
	elsif ($OSNAME eq "netbsd"){
		$free_mem = `cat /proc/meminfo | grep MemFree | awk '{ print \$2 }'`;
	}
	elsif ($OSNAME eq "MSWin32"){
		$free_mem = `wmic OS get FreePhysicalMemory /Value`;
		if ($free_mem =~ m/=(.*)$/gm) {
			$free_mem = $1;
		} else {
			$free_mem = undef;
		}
	}
	# by default LINUX calls
	else {
		$free_mem = `free | grep Mem | awk '{ print \$4 }'`;
	}
	return $free_mem;
}

##########################################################################
## SUB ticks_totime
	# Transform a snmp timeticks count in a date
##########################################################################

sub ticks_totime ($){

	# Calculate ticks per second, minute, hour, and day
	my $TICKS_PER_SECOND = 100;
	my $TICKS_PER_MINUTE = $TICKS_PER_SECOND * 60;
	my $TICKS_PER_HOUR   = $TICKS_PER_MINUTE * 60;
	my $TICKS_PER_DAY    = $TICKS_PER_HOUR * 24;

	my $ticks   = shift;
	
	if (!defined($ticks)){
			return "";
	}
	
	my $seconds = int($ticks / $TICKS_PER_SECOND) % 60;
	my $minutes = int($ticks / $TICKS_PER_MINUTE) % 60;
	my $hours   = int($ticks / $TICKS_PER_HOUR)   % 24;
	my $days    = int($ticks / $TICKS_PER_DAY);

	return "$days days, $hours hours, $minutes minutes, $seconds seconds";
}

##############################################################################
=head2 C<< pandora_ping (I<$pa_config>, I<$host>) >> 

Ping the given host. 
Returns:
 1 if the host is alive
 0 otherwise.

=cut
##############################################################################
sub pandora_ping ($$$$) {
	my ($pa_config, $host, $timeout, $retries) = @_;
	
	# Adjust timeout and retry values
	if ($timeout == 0) {
		$timeout = $pa_config->{'networktimeout'};
	}
	if ($retries == 0) {
		$retries = $pa_config->{'icmp_checks'};
	}
	my $packets = defined($pa_config->{'icmp_packets'}) ? $pa_config->{'icmp_packets'} : 1;
	 
	my $output = 0;
	my $i;
	
	# See codes on http://perldoc.perl.org/perlport.html#PLATFORMS
	my $OSNAME = $^O;
	
	# Windows XP .. Windows 7
	if (($OSNAME eq "MSWin32") || ($OSNAME eq "MSWin32-x64") || ($OSNAME eq "cygwin")){
		my $ms_timeout = $timeout * 1000;
		for ($i=0; $i < $retries; $i++) {
			$output = `ping -n $packets -w $ms_timeout $host`;
			if ($output =~ /TTL/){
				return 1;
			}
			sleep 1;
		}
		return 0;
	}
	
	elsif ($OSNAME eq "solaris"){
		my $ping_command = "ping";
		
		if ($host =~ /\d+:|:\d+/ ) {
			$ping_command = "ping -A inet6"
		}
		
		# Note: timeout option is not implemented in ping.
		# 'networktimeout' is not used by ping on Solaris.
		
		# Ping the host
		for ($i=0; $i < $retries; $i++) {
			`$ping_command -s -n $host 56 $packets >$DEVNULL 2>&1`;
			if ($? == 0) {
				return 1;
			}
			sleep 1;
		}
		return 0;
	}
	
	elsif ($OSNAME eq "freebsd"){
		my $ping_command = "ping -t $timeout";
		
		if ($host =~ /\d+:|:\d+/ ) {
			$ping_command = "ping6";
		}
		
		# Note: timeout(-t) option is not implemented in ping6.
		# 'networktimeout' is not used by ping6 on FreeBSD.
		
		# Ping the host
		for ($i=0; $i < $retries; $i++) {
			`$ping_command -q -n -c $packets $host >$DEVNULL 2>&1`;
			if ($? == 0) {
				return 1;
			}
			sleep 1;
		}
		return 0;
	}

        elsif ($OSNAME eq "netbsd"){                      
		my $ping_command = "ping -w $timeout";

		if ($host =~ /\d+:|:\d+/ ) {
			$ping_command = "ping6";
		}

		# Note: timeout(-w) option is not implemented in ping6.
		# 'networktimeout' is not used by ping6 on NetBSD.

		# Ping the host
		for ($i=0; $i < $retries; $i++) {
			`$ping_command -q -n -c $packets $host >$DEVNULL 2>&1`;
			if ($? == 0) {
				return 1;
			}
			sleep 1;
		}
		return 0;
	}
	
	# by default LINUX calls
	else {
		
		my $ping_command = "ping";
		
		if ($host =~ /\d+:|:\d+/ ) {
			$ping_command = "ping6";
		}
		
		# Ping the host
		for ($i=0; $i < $retries; $i++) {
			`$ping_command -q -W $timeout -n -c $packets $host >$DEVNULL 2>&1`;	
			if ($? == 0) {
				return 1;
			}
			sleep 1;
		}
		return 0;
	}
	
	return $output;
}

########################################################################
=head2 C<< pandora_ping_latency (I<$pa_config>, I<$host>) >> 

Ping the given host. Returns the average round-trip time. Returns undef if fails.

=cut
########################################################################
sub pandora_ping_latency ($$$$) {
	my ($pa_config, $host, $timeout, $retries) = @_;

	# Adjust timeout and retry values
	if ($timeout == 0) {
		$timeout = $pa_config->{'networktimeout'};
	}
	if ($retries == 0) {
		$retries = $pa_config->{'icmp_checks'};
	}
	
	my $output = 0;
	
	# See codes on http://perldoc.perl.org/perlport.html#PLATFORMS
	my $OSNAME = $^O;
	
	# Windows XP .. Windows 2008, I assume Win7 is the same
	if (($OSNAME eq "MSWin32") || ($OSNAME eq "MSWin32-x64") || ($OSNAME eq "cygwin")){
		
		# System ping reports in different languages, but with the same format:
		# Mínimo = xxms, Máximo = xxms, Media = XXms
		# Minimun = xxms, Mamimun = xxms, Average = XXms
		
		# If this fails, ping can be replaced by fping which also have the same format
		# but always in english
		
		my $ms_timeout = $timeout * 1000;
		$output = `ping -n $retries -w $ms_timeout $host`;
		
		if ($output =~ m/\=\s([0-9]+)ms$/){
			return $1;
		} else {
			return undef;
		}
		
	}
	
	elsif ($OSNAME eq "solaris"){
		my $ping_command = "ping";
		
		if ($host =~ /\d+:|:\d+/ ) {
			$ping_command = "ping -A inet6";
		}
		
		# Note: timeout option is not implemented in ping.
		# 'networktimeout' is not used by ping on Solaris.
		
		# Ping the host
		my @output = `$ping_command -s -n $host 56 $retries 2>$DEVNULL`;
		
		# Something went wrong
		return undef if ($? != 0);
		
		# Parse the output
		my $stats = pop (@output);
		return undef unless ($stats =~ m/([\d\.]+)\/([\d\.]+)\/([\d\.]+)\/([\d\.]+) +ms/);
		return $2;
	}
	
	elsif ($OSNAME eq "freebsd"){
		my $ping_command = "ping -t $timeout";
		
		if ($host =~ /\d+:|:\d+/ ) {
			$ping_command = "ping6";
		}
		
		# Note: timeout(-t) option is not implemented in ping6. 
		# timeout(-t) and waittime(-W) options in ping are not the same as
		# Linux. On latency, there are no way to set timeout.
		# 'networktimeout' is not used on FreeBSD.
		
		# Ping the host
		my @output = `$ping_command -q -n -c $retries $host 2>$DEVNULL`;
		
		# Something went wrong
		return undef if ($? != 0);
		
		# Parse the output
		my $stats = pop (@output);
		return undef unless ($stats =~ m/([\d\.]+)\/([\d\.]+)\/([\d\.]+)\/([\d\.]+) +ms/);
		return $2;
	}

        elsif ($OSNAME eq "netbsd"){
		my $ping_command = "ping -w $timeout";

		if ($host =~ /\d+:|:\d+/ ) {
			$ping_command = "ping6";
		}
              
		# Note: timeout(-w) option is not implemented in ping6.
		# timeout(-w) and waittime(-W) options in ping are not the same as
		# Linux. On latency, there are no way to set timeout.
		# 'networktimeout' is not used on NetBSD.

		# Ping the host
		my @output = `$ping_command -q -n -c $retries $host >$DEVNULL 2>&1`;
               
		# Something went wrong
		return undef in ($? != 0);
              
		# Parse the output
		my $stats = pop (@output);
		return undef unless ($stats =~ m/([\d\.]+)\/([\d\.]+)\/([\d\.]+)\/([\d\.]+) +ms/);
		return $2;
	}
	
	# by default LINUX calls
	else {
		my $ping_command = "ping";
		
		if ($host =~ /\d+:|:\d+/ ) {
			$ping_command = "ping6";
		}
		
		
		# Ping the host
		my @output = `$ping_command -q -W $timeout -n -c $retries $host 2>$DEVNULL`;
		
		# Something went wrong
		return undef if ($? != 0);
		
		# Parse the output
		my $stats = pop (@output);
		return undef unless ($stats =~ m/([\d\.]+)\/([\d\.]+)\/([\d\.]+)\/([\d\.]+) +ms/);
		return $2;
	}
	
	# If no valid get values until now, just return with empty value (not valid)
	return $output;
}

########################################################################
=head2 C<< pandora_block_ping (I<$pa_config>, I<$hosts>) >> 

Ping all given hosts. Returns an array with all hosts detected as alive.

=cut
########################################################################
sub pandora_block_ping($@) {
	my ($pa_config, @hosts) = @_;

	# fping timeout in milliseconds
	my $cmd = $pa_config->{'fping'} . " -a -q -t " . (1000 * $pa_config->{'networktimeout'}) . " " . (join (' ', @hosts));

	my @output = `$cmd 2>$DEVNULL`;

	return @output;
}

########################################################################
=head2 C<< month_have_days (I<$month>, I<$year>) >> 

Pass a $month (as january 0 number and each month with numbers) and the year
as number (for example 1981). And return the days of this month.

=cut
########################################################################
sub month_have_days($$) {
	my $month= shift(@_);
	my $year= @_ ? shift(@_) : (1900 + (localtime())[5]);
	
	my @monthDays= qw( 31 28 31 30 31 30 31 31 30 31 30 31 );
	
	if (  $year <= 1752  ) {
		# Note:  Although September 1752 only had 19 days,
		# they were numbered 1,2,14..30!
		if (1752 == $year  &&  8 == $month) {
			return 19;
		}
		if (1 == $month  &&  0 == $year % 4) {
			return 29;
		}
	}
	else {
		#Check if Leap year
		if (1 == $month && 0 == $year % 4 && 0 == $year%100
			|| 0 == $year%400) {
			return 29;
		}
	}
	
	return $monthDays[$month];
}

###############################################################################
# Convert a text obj tag to an OID and update the module configuration.
###############################################################################
sub translate_obj ($$$) {
	my ($pa_config, $dbh, $obj) = @_;

	# Pandora FMS's console MIB directory
	my $mib_dir = $pa_config->{'attachment_dir'} . '/mibs';

	# Translate!
	my $oid = `snmptranslate -On -mALL -M+"$mib_dir" $obj 2>$DEVNULL`;

	if ($? != 0) {
		return undef;
	}
	chomp($oid);
	
	return $oid;
}

###############################################################################
# Get the number of seconds left to the next execution of the given cron entry.
###############################################################################
sub cron_next_execution {
	my ($cron, $interval) = @_;

	# Check cron conf format
	if ($cron !~ /^((\*|(\d+(-\d+){0,1}))\s*){5}$/) {
		return $interval;
	}

	# Get day of the week and month from cron config
	my ($wday) = (split (/\s/, $cron))[4];
	# Check the wday values to avoid infinite loop
	my ($wday_down, $wday_up) = cron_get_interval($wday);
	if ($wday_down ne "*" && ($wday_down > 6 || (defined($wday_up) && $wday_up > 6))) {
		$wday = "*";
	}

	# Get current time and day of the week
	my $cur_time = time();
	my $cur_wday = (localtime ($cur_time))[6];

	my $nex_time = cron_next_execution_date ($cron, $cur_time, $interval);

	# Check the day
	while (!cron_check_interval($wday, (localtime ($nex_time))[6])) {
		# If it does not acomplish the day of the week, go to the next day.
		$nex_time += 86400;
		$nex_time = cron_next_execution_date ($cron, $nex_time, 0);
	}

	return $nex_time - time();
}
###############################################################################
# Get the number of seconds left to the next execution of the given cron entry.
###############################################################################
sub cron_check_syntax ($) {
	my ($cron) = @_;
	
	return 0 if !defined ($cron);
	return ($cron =~ m/^(\d|\*|-)+ (\d|\*|-)+ (\d|\*|-)+ (\d|\*|-)+ (\d|\*|-)+$/);
}
###############################################################################
# Check if a value is inside an interval.
###############################################################################
sub cron_check_interval {
	my ($elem_cron, $elem_curr_time) = @_;

	# Return 1 if wildcard.
	return 1 if ($elem_cron eq "*");

	my ($down, $up) = cron_get_interval($elem_cron);
	# Check if it is not a range
	if (!defined($up)) {
		return ($down == $elem_curr_time) ? 1 : 0;
	}

	# Check if it is on the range
	if ($down < $up) {
		return 0 if ($elem_curr_time < $down || $elem_curr_time > $up);
	} else {
		return 0 if ($elem_curr_time > $down || $elem_curr_time < $up);
	}

	return 1;
}
###############################################################################
# Get the next execution date for the given cron entry in seconds since epoch.
###############################################################################
sub cron_next_execution_date {
	my ($cron, $cur_time, $interval) = @_;

	# Get cron configuration
	my ($min, $hour, $mday, $mon, $wday) = split (/\s/, $cron);

	# Months start from 0
	if($mon ne '*') {
		my ($mon_down, $mon_up) = cron_get_interval ($mon);
		if (defined($mon_up)) {
			$mon = ($mon_down - 1) . "-" . ($mon_up - 1);
		} else {
			$mon = $mon_down - 1;
		}
	}

	# Get current time
	if (! defined ($cur_time)) {
		$cur_time = time();
	}
	# Check if current time + interval is on cron too
	my $nex_time = $cur_time + $interval;
	my ($cur_min, $cur_hour, $cur_mday, $cur_mon, $cur_year) 
		= (localtime ($nex_time))[1, 2, 3, 4, 5];
	
	my @cron_array = ($min, $hour, $mday, $mon);
	my @curr_time_array = ($cur_min, $cur_hour, $cur_mday, $cur_mon);
	return ($nex_time) if cron_is_in_cron(\@cron_array, \@curr_time_array) == 1;

	# Get first next date candidate from next cron configuration
	# Initialize some vars
	my @nex_time_array = @curr_time_array;

	# Update minutes
	$nex_time_array[0] = cron_get_next_time_element($min);

	$nex_time = cron_valid_date(@nex_time_array, $cur_year);
	if ($nex_time >= $cur_time) {
		return $nex_time if cron_is_in_cron(\@cron_array, \@nex_time_array);
	}

	# Check if next hour is in cron
	$nex_time_array[1]++;
	$nex_time = cron_valid_date(@nex_time_array, $cur_year);

	if ($nex_time == 0) {
		#Update the month day if overflow
		$nex_time_array[1] = 0;
		$nex_time_array[2]++;
		$nex_time = cron_valid_date(@nex_time_array, $cur_year);
		if ($nex_time == 0) {
			#Update the month if overflow
			$nex_time_array[2] = 1;
			$nex_time_array[3]++;
			$nex_time = cron_valid_date(@nex_time_array, $cur_year);
			if ($nex_time == 0) {
				#Update the year if overflow
				$cur_year++;
				$nex_time_array[3] = 0;
				$nex_time = cron_valid_date(@nex_time_array, $cur_year);
			}
		}
	}
	#Check the hour
	return $nex_time if cron_is_in_cron(\@cron_array, \@nex_time_array);

	#Update the hour if fails
	$nex_time_array[1] = cron_get_next_time_element($hour);

	# When an overflow is passed check the hour update again
	$nex_time = cron_valid_date(@nex_time_array, $cur_year);
	if ($nex_time >= $cur_time) {
		return $nex_time if cron_is_in_cron(\@cron_array, \@nex_time_array);
	}

	# Check if next day is in cron
	$nex_time_array[2]++;
	$nex_time = cron_valid_date(@nex_time_array, $cur_year);
	if ($nex_time == 0) {
		#Update the month if overflow
		$nex_time_array[2] = 1;
		$nex_time_array[3]++;
		$nex_time = cron_valid_date(@nex_time_array, $cur_year);
		if ($nex_time == 0) {
			#Update the year if overflow
			$nex_time_array[3] = 0;
			$cur_year++;
			$nex_time = cron_valid_date(@nex_time_array, $cur_year);
		}
	}
	#Check the day
	return $nex_time if cron_is_in_cron(\@cron_array, \@nex_time_array);
	
	#Update the day if fails
	$nex_time_array[2] = cron_get_next_time_element($mday, 1);

	# When an overflow is passed check the hour update in the next execution
	$nex_time = cron_valid_date(@nex_time_array, $cur_year);
	if ($nex_time >= $cur_time) {
		return $nex_time if cron_is_in_cron(\@cron_array, \@nex_time_array);
	}

	# Check if next month is in cron
	$nex_time_array[3]++;
	$nex_time = cron_valid_date(@nex_time_array, $cur_year);
	if ($nex_time == 0) {
		#Update the year if overflow
		$nex_time_array[3] = 0;
		$cur_year++;
		$nex_time = cron_valid_date(@nex_time_array, $cur_year);
	}

	#Check the month
	return $nex_time if cron_is_in_cron(\@cron_array, \@nex_time_array);

	#Update the month if fails
	$nex_time_array[3] = cron_get_next_time_element($mon);

	# When an overflow is passed check the hour update in the next execution
	$nex_time = cron_valid_date(@nex_time_array, $cur_year);
	if ($nex_time >= $cur_time) {
		return $nex_time if cron_is_in_cron(\@cron_array, \@nex_time_array);
	}

	$nex_time = cron_valid_date(@nex_time_array, $cur_year + 1);

	return $nex_time;
}
###############################################################################
# Returns if a date is in a cron. Recursive.
# Needs the cron like an array reference and
# current time in cron format to works properly
###############################################################################
sub cron_is_in_cron {
	my ($elems_cron, $elems_curr_time) = @_;

	my @deref_elems_cron = @$elems_cron;
	my @deref_elems_curr_time = @$elems_curr_time;
	
	my $elem_cron = shift(@deref_elems_cron);
	my $elem_curr_time = shift (@deref_elems_curr_time);

	#If there is no elements means that is in cron
	return 1 unless (defined($elem_cron) || defined($elem_curr_time));

	# Check the element interval
	return 0 unless (cron_check_interval($elem_cron, $elem_curr_time));

	return cron_is_in_cron(\@deref_elems_cron, \@deref_elems_curr_time);
}
################################################################################
#Get the next tentative time for a cron value or interval in case of overflow.
#Floor data is the minimum localtime data for a position. Ex: 
#Ex:
#     * should returns floor data.
#     5 should returns 5.
#     10-55 should returns 10.
#     55-10 should retunrs floor data.
################################################################################
sub cron_get_next_time_element {
	# Default floor data is 0
	my ($curr_element, $floor_data) = @_;
	$floor_data = 0 unless defined($floor_data);

	my ($elem_down, $elem_up) = cron_get_interval ($curr_element);
	return ($elem_down eq '*' || (defined($elem_up) && $elem_down > $elem_up))
		? $floor_data
		: $elem_down;
}
###############################################################################
# Returns the interval of a cron element. If there is not a range,
# returns an array with the first element in the first place of array
# and the second place undefined.
###############################################################################
sub cron_get_interval {
	my ($element) = @_;

	# Not a range
	if ($element !~ /(\d+)\-(\d+)/) {
		return ($element, undef);
	}
	
	return ($1, $2);
}
###############################################################################
# Returns the closest number to the target inside the given range (including
# the target itself).
###############################################################################
sub cron_get_closest_in_range ($$) {
	my ($target, $range) = @_;

	# Not a range
	if ($range !~ /(\d+)\-(\d+)/) {
		return $range;
	}
	
	# Search the closes number to the target in the given range
	my $range_start = $1;
	my $range_end = $2;
	
	# Outside the range
	if ($target <= $range_start || $target > $range_end) {
		return $range_start;
	}
	
	# Inside the range
	return $target;
}

###############################################################################
# Check if a date is valid to get timelocal
###############################################################################
sub cron_valid_date {
	my ($min, $hour, $mday, $month, $year) = @_;
	my $utime;
	eval {
		local $SIG{__DIE__} = sub {};
		$utime = timelocal(0, $min, $hour, $mday, $month, $year);
	};
	if ($@) {
		return 0;
	}
	return $utime;
}

###############################################################################
# Attempt to resolve the given hostname.
###############################################################################
sub resolve_hostname ($) {
	my ($hostname) = @_;
	
	$resolved_hostname = inet_aton($hostname);
	return $hostname if (! defined ($resolved_hostname));
	
	return inet_ntoa($resolved_hostname);
}

###############################################################################
# Returns 1 if the given regular expression is valid, 0 otherwise.
###############################################################################
sub valid_regex ($) {
	my $regex = shift;
	
	eval {
		local $SIG{'__DIE__'};
		qr/$regex/
	};
	
	# Invalid regex
	return 0 if ($@);
	
	# Valid regex
	return 1;
}

###############################################################################
# Returns 1 if a valid metaconsole license is configured, 0 otherwise.
###############################################################################
sub is_metaconsole ($) {
	my ($pa_config) = @_;

	if (defined($pa_config->{"license_type"}) &&
		($pa_config->{"license_type"} & METACONSOLE_LICENSE) &&
		$pa_config->{"node_metaconsole"} == 0) {
		return 1;
	}

	return 0;
}

###############################################################################
# Returns 1 if a valid offline license is configured, 0 otherwise.
###############################################################################
sub is_offline ($) {
	my ($pa_config) = @_;

	if (defined($pa_config->{"license_type"}) &&
		($pa_config->{"license_type"} & OFFLINE_LICENSE)) {
		return 1;
	}

	return 0;
}

###############################################################################
# Check if a given variable contents a number
###############################################################################
sub to_number($) {
	my $n = shift;
	if ($n =~ /[\d+,]*\d+\.\d+/) {
		# American notation
		$n =~ s/,//g;
	}
	elsif ($n =~ /[\d+\.]*\d+,\d+/) {
		# Spanish notation
		$n =~ s/\.//g;
		$n =~ s/,/./g;
	}
	if(looks_like_number($n)) {
		return $n;
	}
	return undef;
}

#######################
# ENCODE
#######################
sub uri_encode {
    # Un-reserved characters
    my $unreserved_re = qr{ ([^a-zA-Z0-9\Q-_.~\E\%]) }x;
    my $enc_map       = { ( map { chr($_) => sprintf( "%%%02X", $_ ) } ( 0 ... 255 ) ) };
    my $dec_map       = { ( map { sprintf( "%02X", $_ ) => chr($_) } ( 0 ... 255 ) ) };

    my ($data) = @_;

    # Check for data
    # Allow to be '0'
    return unless defined $data;

    # UTF-8 encode
    $data = Encode::encode( 'utf-8-strict', $data );

    # Encode a literal '%'
    $data =~ s{(\%)(.*)}{uri_encode_literal_percent($1, $2, $enc_map, $dec_map)}gex;

    # Percent Encode
    $data =~ s{$unreserved_re}{uri_encode_get_encoded_char($1, $enc_map)}gex;

    # Done
  return $data;
} ## end sub encode

#######################
# INTERNAL
#######################
sub uri_encode_get_encoded_char($$) {
    my ( $char, $enc_map ) = @_;

  return $enc_map->{$char} if exists $enc_map->{$char};
  return $char;
} ## end sub uri_encode_get_encoded_char

sub uri_encode_literal_percent {
    my ( $char, $post, $enc_map, $dec_map ) = @_;

  return uri_encode_get_encoded_char($char, $enc_map) if not defined $post;

    my $return_char;
    if ( $post =~ m{^([a-fA-F0-9]{2})}x ) {
        if ( exists $dec_map->{$1} ) {
            $return_char = join( '', $char, $post );
        }
    } ## end if ( $post =~ m{^([a-fA-F0-9]{2})}x)

    $return_char ||= join( '', uri_encode_get_encoded_char($char, $enc_map), $post );
  return $return_char;
} ## end sub uri_encode_literal_percent


################################################################################
# Launch API call
################################################################################
sub api_call_url {
	my ($pa_config, $server_url, $api_params, @options) = @_;
	

	my $ua = LWP::UserAgent->new();
	$ua->timeout($pa_config->{'tcp_timeout'});
	# Enable environmental proxy settings
	$ua->env_proxy;
	# Enable in-memory cookie management
	$ua->cookie_jar( {} );
	
	# Disable verify host certificate (only needed for self-signed cert)
	$ua->ssl_opts( 'verify_hostname' => 0 );
	$ua->ssl_opts( 'SSL_verify_mode' => 0x00 );

	my $response;

	eval {
		$response = $ua->post($server_url, $api_params, @options);
	};
	if ((!$@) && $response->is_success) {
		return $response->decoded_content;
	}
	return undef;
}

################################################################################
# Start a server thread and keep track of it.
################################################################################
sub start_server_thread {
	my ($fn, $args) = @_;

	# Signal the threads to run.
	$THRRUN = 1;

	my $thr = threads->create($fn, @{$args});
	push(@ServerThreads, $thr);
}

################################################################################
# Check the status of server threads. Returns 1 if all all running, 0 otherwise.
################################################################################
sub check_server_threads {
	my ($fn, $args) = @_;

	foreach my $thr (@ServerThreads) {
		return 0 unless $thr->is_running();
	}

	return 1;
}

################################################################################
# Stop all server threads.
################################################################################
sub stop_server_threads {
	my ($fn, $args) = @_;

	# Signal the threads to exits.
	$THRRUN = 0;

	foreach my $thr (@ServerThreads) {
			$thr->join();
	}

	@ServerThreads = ();
}

################################################################################
# Generate random hash as agent name.
################################################################################
sub generate_agent_name_hash {
	my ($agent_alias, $server_ip) = @_;
	return sha256(join('|', ($agent_alias, $server_ip, time(), sprintf("%04d", rand(10000)))));
}

###############################################################################
# Return the SHA256 checksum of the given string as a hex string.
# Pseudocode from: http://en.wikipedia.org/wiki/SHA-2#Pseudocode
###############################################################################
my @K2 = (
	0x428a2f98, 0x71374491, 0xb5c0fbcf, 0xe9b5dba5, 0x3956c25b, 0x59f111f1,
	0x923f82a4, 0xab1c5ed5, 0xd807aa98, 0x12835b01, 0x243185be, 0x550c7dc3,
	0x72be5d74, 0x80deb1fe, 0x9bdc06a7, 0xc19bf174, 0xe49b69c1, 0xefbe4786,
	0x0fc19dc6, 0x240ca1cc, 0x2de92c6f, 0x4a7484aa, 0x5cb0a9dc, 0x76f988da,
	0x983e5152, 0xa831c66d, 0xb00327c8, 0xbf597fc7, 0xc6e00bf3, 0xd5a79147,
	0x06ca6351, 0x14292967, 0x27b70a85, 0x2e1b2138, 0x4d2c6dfc, 0x53380d13,
	0x650a7354, 0x766a0abb, 0x81c2c92e, 0x92722c85, 0xa2bfe8a1, 0xa81a664b,
	0xc24b8b70, 0xc76c51a3, 0xd192e819, 0xd6990624, 0xf40e3585, 0x106aa070,
	0x19a4c116, 0x1e376c08, 0x2748774c, 0x34b0bcb5, 0x391c0cb3, 0x4ed8aa4a,
	0x5b9cca4f, 0x682e6ff3, 0x748f82ee, 0x78a5636f, 0x84c87814, 0x8cc70208,
	0x90befffa, 0xa4506ceb, 0xbef9a3f7, 0xc67178f2
);
sub sha256 {
	my $str = shift;

	# No input!
	if (!defined($str)) {
		return "";
	}

	# Note: All variables are unsigned 32 bits and wrap modulo 2^32 when
	# calculating.

	# First 32 bits of the fractional parts of the square roots of the first 8
	# primes.
	my $h0 = 0x6a09e667;
	my $h1 = 0xbb67ae85;
	my $h2 = 0x3c6ef372;
	my $h3 = 0xa54ff53a;
	my $h4 = 0x510e527f;
	my $h5 = 0x9b05688c;
	my $h6 = 0x1f83d9ab;
	my $h7 = 0x5be0cd19;

	# Pre-processing.
	my $msg = unpack ("B*", pack ("A*", $str));
	my $bit_len = length ($msg);

	# Append "1" bit to message.
	$msg .= '1';

	# Append "0" bits until message length in bits = 448 (mod 512).
	$msg .= '0' while ((length ($msg) % 512) != 448);

	# Append bit /* bit, not byte */ length of unpadded message as 64-bit
	# big-endian integer to message.
	$msg .= unpack ("B32", pack ("N", $bit_len >> 32));
	$msg .= unpack ("B32", pack ("N", $bit_len));

	# Process the message in successive 512-bit chunks.
	for (my $i = 0; $i < length ($msg); $i += 512) {

		my @w;
		my $chunk = substr ($msg, $i, 512);

		# Break chunk into sixteen 32-bit big-endian words.
		for (my $j = 0; $j < length ($chunk); $j += 32) {
			push (@w, unpack ("N", pack ("B32", substr ($chunk, $j, 32))));
		}

		# Extend the first 16 words into the remaining 48 words w[16..63] of the message schedule array:
		for (my $i = 16; $i < 64; $i++) {
			my $s0 = rightrotate($w[$i - 15], 7) ^ rightrotate($w[$i - 15], 18) ^ ($w[$i - 15] >> 3);
			my $s1 = rightrotate($w[$i - 2], 17) ^ rightrotate($w[$i - 2], 19) ^ ($w[$i - 2] >> 10);
			$w[$i] = ($w[$i - 16] + $s0 + $w[$i - 7] + $s1) % POW232;
		}

		# Initialize working variables to current hash value.
		my $a = $h0;
		my $b = $h1;
		my $c = $h2;
		my $d = $h3;
		my $e = $h4;
		my $f = $h5;
		my $g = $h6;
		my $h = $h7;

		# Compression function main loop.
		for (my $i = 0; $i < 64; $i++) {
			my $S1 = rightrotate($e, 6) ^ rightrotate($e, 11) ^ rightrotate($e, 25);
			my $ch = ($e & $f) ^ ((0xFFFFFFFF & (~ $e)) & $g);
			my $temp1 = ($h + $S1 + $ch + $K2[$i] + $w[$i]) % POW232;
			my $S0 = rightrotate($a, 2) ^ rightrotate($a, 13) ^ rightrotate($a, 22);
			my $maj = ($a & $b) ^ ($a & $c) ^ ($b & $c);
			my $temp2 = ($S0 + $maj) % POW232;

			$h = $g;
			$g = $f;
			$f = $e;
			$e = ($d + $temp1) % POW232;
			$d = $c;
			$c = $b;
			$b = $a;
			$a = ($temp1 + $temp2) % POW232;
		}

		# Add the compressed chunk to the current hash value.
		$h0 = ($h0 + $a) % POW232;
		$h1 = ($h1 + $b) % POW232;
		$h2 = ($h2 + $c) % POW232;
		$h3 = ($h3 + $d) % POW232;
		$h4 = ($h4 + $e) % POW232;
		$h5 = ($h5 + $f) % POW232;
		$h6 = ($h6 + $g) % POW232;
		$h7 = ($h7 + $h) % POW232;
	}

	# Produce the final hash value (big-endian).
	return unpack ("H*", pack ("N", $h0)) .
	       unpack ("H*", pack ("N", $h1)) .
	       unpack ("H*", pack ("N", $h2)) .
	       unpack ("H*", pack ("N", $h3)) .
	       unpack ("H*", pack ("N", $h4)) .
	       unpack ("H*", pack ("N", $h5)) .
	       unpack ("H*", pack ("N", $h6)) .
	       unpack ("H*", pack ("N", $h7));
}

###############################################################################
# Rotate a 32-bit number a number of bits to the right.
###############################################################################
sub rightrotate {
	my ($x, $c) = @_;

	return (0xFFFFFFFF & ($x << (32 - $c))) | ($x >> $c);
}

###############################################################################
# Returns IP address(v4) in longint format
###############################################################################
sub ip_to_long {
	my $ip_str = shift;
	return unpack "N", inet_aton($ip_str);
}

###############################################################################
# Returns IP address(v4) in longint format
###############################################################################
sub long_to_ip {
	my $ip_long = shift;
	return inet_ntoa pack("N", ($ip_long));
}

###############################################################################
# Returns a list with enabled servers.
###############################################################################
sub get_enabled_servers {
	my $conf = shift;

	if (ref($conf) ne "HASH") {
		return ();
	}

	my @server_list = map {
		if ($_ =~ /server$/i && $conf->{$_} > 0) {
			$_
		} else {
		}
	} keys %{$conf};

	return @server_list;
}
# End of function declaration
# End of defined Code


################################################################################
# Initialize a LWP::User agent
################################################################################
sub get_user_agent {
	my $pa_config = shift;
	my $ua;

	eval {
		if (!(defined($pa_config->{'lwp_timeout'})
			&& is_numeric($pa_config->{'lwp_timeout'}))
		) {
			$pa_config->{'lwp_timeout'} = 3;
		}

		$ua = LWP::UserAgent->new(
			'keep_alive' => "10"
		);

		# Configure LWP timeout.
		$ua->timeout($pa_config->{'lwp_timeout'});

		# Enable environmental proxy settings
		$ua->env_proxy;

		# Enable in-memory cookie management
		$ua->cookie_jar( {} );

		if (!defined($pa_config->{'ssl_verify'})
			|| (defined($pa_config->{'ssl_verify'})
				&& $pa_config->{'ssl_verify'} eq "0")
		) {
			# Disable verify host certificate (only needed for self-signed cert)
			$ua->ssl_opts( 'verify_hostname' => 0 );
			$ua->ssl_opts( 'SSL_verify_mode' => 0x00 );

			# Disable library extra checks 
			BEGIN {
				$ENV{PERL_NET_HTTPS_SSL_SOCKET_CLASS} = "Net::SSL";
				$ENV{PERL_LWP_SSL_VERIFY_HOSTNAME} = 0;
			}
		}
	};
	if($@) {
		logger($pa_config, 'Failed to initialize LWP::UserAgent', 5);
		# Failed
		return;
	}

	return $ua;
}








1;
__END__

