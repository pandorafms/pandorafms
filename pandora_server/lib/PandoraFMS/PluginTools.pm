package PandoraFMS::PluginTools;
################################################################################
#
# Pandora FMS Plugin functions library
#
#  (c) Fco de Borja Sanchez <fborja.sanchez@artica.es>
#
#
# Version   Date
#  a1        17-11-2015
#  ** Revision handler in gitlab **
# 
################################################################################

use strict;
use warnings;

use LWP::UserAgent;
use HTTP::Cookies;
use HTTP::Request::Common;

use File::Copy;
use Scalar::Util qw(looks_like_number);
use Time::HiRes qw(time);
use POSIX qw(strftime setsid floor);
use MIME::Base64;

use base 'Exporter';

our @ISA = qw(Exporter);

# version: Defines actual version of Pandora Server for this module only
my $pandora_version = "7.0NG.715";
my $pandora_build = "171114";
our $VERSION = $pandora_version." ".$pandora_build;

our %EXPORT_TAGS = ( 'all' => [ qw() ] );

our @EXPORT_OK = ( @{ $EXPORT_TAGS{'all'} } );

our @EXPORT = qw(
	api_available
	api_create_custom_field
	api_create_tag
	api_create_group
	call_url
	decrypt
	empty
	encrypt
	get_lib_version
	get_unit
	get_unix_time
	get_sys_environment
	getCurrentUTimeMilis
	head
	in_array
	init
	is_enabled
	load_perl_modules
	logger
	merge_hashes
	parse_arguments
	parse_configuration
	parse_php_configuration
	process_performance
	post_url
	print_agent
	print_error
	print_execution_result
	print_message
	print_module
	print_warning
	print_stderror
	simple_decode_json
	snmp_data_switcher
	snmp_get
	snmp_walk
	tail
	to_number
	transfer_xml
	trim
);

################################################################################
#
################################################################################
sub get_lib_version {
	return $VERSION;
}


################################################################################
# Get current time (milis)
################################################################################
sub getCurrentUTimeMilis {
	#return trim (`date +"%s%3N"`); # returns 1449681679712
	return floor(time*1000);
}

################################################################################
# Mix hashses
################################################################################
sub merge_hashes {
	my $_h1 = shift;
	my $_h2 = shift;

	my %ret = (%{$_h1}, %{$_h2});

	return \%ret;
}

################################################################################
# Regex based tail command
################################################################################
sub tail {
	my $string = shift;
	my $n = shift;
	my $reverse_flag = shift;
	my $nlines = $string =~ tr/\n//;

	if (empty ($string)){
		return "";
	}

	if (defined($reverse_flag)) {
		$n = $n-1;
	}
	else {
		$n = $nlines-$n;
	}

	$string =~ s/^(?:.*\n){0,$n}//;
	return $string;
}

################################################################################
# Regex based head command
################################################################################
sub head {
	my $string = shift;
	my $n = shift;
	my $reverse_flag = shift;
	my $nlines = $string =~ tr/\n//;
	if (empty ($string)){
		return "";
	}

	if (defined($reverse_flag)) {
		$n = $nlines - $n +1;
	}
	my $str="";
	my @lines = split /\n/, $string;
	for (my $x=0; $x < $n; $x++) {
		$str .= $lines[$x] . "\n";
	}
	return $str;
}

################################################################################
# Check if a given variable contents a number
################################################################################
sub to_number {
	my $n = shift;

	if(empty($n)) {
		return undef;
	}
	
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

################################################################################
# Erase blank spaces before and after the string 
################################################################################
sub trim {
	my $string = shift;
	if (empty ($string)){
		return "";
	}

	$string =~ s/\r//g;

	chomp ($string);
	$string =~ s/^\s+//g;
	$string =~ s/\s+$//g;

	return $string;
}

################################################################################
# Empty
################################################################################
sub empty {
	my $str = shift;

	if (! (defined ($str)) ){
		return 1;
	}

	if(looks_like_number($str)){
		return 0;
	}

	if (ref ($str) eq "ARRAY") {
		return (($#{$str}<0)?1:0);
	}

	if (ref ($str) eq "HASH") {
		my @tmp = keys %{$str};
		return (($#tmp<0)?1:0);
	}

	if ($str =~ /^\ *[\n\r]{0,2}\ *$/) {
		return 1;
	}
	return 0;
}

################################################################################
# Check if a value is in an array
################################################################################
sub in_array {
	my ($array, $value) = @_;

	if (empty($value)) {
		return 0;
	}

	my %params = map { $_ => 1 } @{$array};
	if (exists($params{$value})) {
		return 1;
	}
	return 0;
}

################################################################################
# Get unit
################################################################################
sub get_unit {
	my $str = shift;
	$str =~ s/[\d\.\,]//g;
	return $str;
}

################################################################################
## Decodes a json strin into an hash
################################################################################
sub simple_decode_json;
sub simple_decode_json {
     my $json = shift;
     my $hash_reference;

     if (empty ($json)){
          return undef;

     }
     if ($json =~ /^\".*\"\:\{.*}$/ ){ # key => tree
          my @data = split /:/, $json, 2;

          # data[0] it's key, remove "
          $data[0] =~ s/^\"//;
          $data[0] =~ s/\"$//;

          $hash_reference->{$data[0]} = simple_decode_json($data[1]);
          return $hash_reference;

     }
     if ($json =~ /^\{(.*)\}$/) { # parse tree
          $hash_reference = simple_decode_json($1);
          return $hash_reference;

     }
     if ($json =~ /^(\".*[\"|\}]),(\".*[\"|\}])/) { # multi keys
          my @data = split /,/, $json, 2;

          if ($data[0] =~ /{/ ){
               @data = split /},/, $json, 2;
               $data[0] .= "}";
          }

          my $left_tree;
          my $right_tree;

          $left_tree  = simple_decode_json($data[0]);
          $right_tree = simple_decode_json($data[1]);


          # join both sides
          foreach (keys %{$left_tree}){
               $hash_reference->{$_} = $left_tree->{$_};
          }
          foreach (keys %{$right_tree}){
               $hash_reference->{$_} = $right_tree->{$_};
          }

          return $hash_reference;

     }
     if ($json =~ /^\"(.*)\"\:(\".*\")$/ ) { # return key => value
          $hash_reference->{$1} = simple_decode_json($2);

          return $hash_reference;

     }
     if ($json =~ /^"(.*)"$/) {
          return $1;

     }


     return $hash_reference;

}

################################################################################
# print_agent
################################################################################
sub print_agent {
	my ($config, $agent_data, $modules_def, $str_flag) = @_;

	my $xml = "<?xml version='1.0' encoding='UTF-8'?>\n";

	# print header
	$xml .= "<agent_data ";

	foreach my $kad (keys %{$agent_data}){
		no warnings "uninitialized";
		$xml .= $kad . "='";
		$xml .= $agent_data->{$kad} . "' ";
	}

	$xml .= ">";

	foreach my $module (@{$modules_def}) {
		$xml .= print_module($config, $module,1);
	}

	# print tail
	$xml .= "</agent_data>\n";

	if (is_enabled($str_flag)){
		print $xml;
	}

	return $xml;

}

################################################################################
# print_module
################################################################################
sub print_module {
	my $conf = shift;
	my $data = shift;
	my $not_print_flag = shift;

	if ((ref($data) ne "HASH") || (!defined $data->{name})) {
		return undef;
	}
	
	my $xml_module = "";
	# If not a string type, remove all blank spaces!    
	if ($data->{type} !~ m/string/){
		$data->{value} = trim($data->{value});
	}

	$data->{tags}  = $data->{tags}?$data->{tags}:($conf->{MODULE_TAG_LIST}?$conf->{MODULE_TAG_LIST}:undef);
	$data->{interval}     = $data->{interval}?$data->{interval}:($conf->{MODULE_INTERVAL}?$conf->{MODULE_INTERVAL}:undef);
	$data->{module_group} = $data->{module_group}?$data->{module_group}:($conf->{MODULE_GROUP}?$conf->{MODULE_GROUP}:undef);

	# Global instructions (if defined)
	$data->{unknown_instructions}  = $conf->{unknown_instructions}  unless (defined($data->{unknown_instructions})  || (!defined($conf->{unknown_instructions})));
	$data->{warning_instructions}  = $conf->{warning_instructions}  unless (defined($data->{warning_instructions})  || (!defined($conf->{warning_instructions})));
	$data->{critical_instructions} = $conf->{critical_instructions} unless (defined($data->{critical_instructions}) || (!defined($conf->{critical_instructions})));

	$xml_module .= "<module>\n";
	$xml_module .= "\t<name><![CDATA[" . $data->{name} . "]]></name>\n";
	$xml_module .= "\t<type>" . $data->{type} . "</type>\n";

	if (ref ($data->{value}) eq "ARRAY") {
		$xml_module .= "\t<datalist>\n";
		foreach (@{$data->{value}}) {
			$xml_module .= "\t<data><![CDATA[" . $data->{value} . "]]></data>\n";
		}
		$xml_module .= "\t</datalist>\n";
	}
	else {
		$xml_module .= "\t<data><![CDATA[" . $data->{value} . "]]></data>\n";
	}

	if ( !(empty($data->{desc}))) {
		$xml_module .= "\t<description><![CDATA[" . $data->{desc} . "]]></description>\n";
	}
	if ( !(empty ($data->{unit})) ) {
		$xml_module .= "\t<unit><![CDATA[" . $data->{unit} . "]]></unit>\n";
	}
	if (! (empty($data->{interval})) ) {
		$xml_module .= "\t<module_interval><![CDATA[" . $data->{interval} . "]]></module_interval>\n";
	}
	if (! (empty($data->{tags})) ) {
		$xml_module .= "\t<tags>" . $data->{tags} . "</tags>\n";
	}
	if (! (empty($data->{module_group})) ) {
		$xml_module .= "\t<module_group>" . $data->{module_group} . "</module_group>\n";
	}
	if (! (empty($data->{module_parent})) ) {
		$xml_module .= "\t<module_parent>" . $data->{module_parent} . "</module_parent>\n";
	}
	if (! (empty($data->{wmin})) ) {
		$xml_module .= "\t<min_warning><![CDATA[" . $data->{wmin} . "]]></min_warning>\n";
	}
	if (! (empty($data->{wmax})) ) {
		$xml_module .= "\t<max_warning><![CDATA[" . $data->{wmax} . "]]></max_warning>\n";
	}
	if (! (empty ($data->{cmin})) ) {
		$xml_module .= "\t<min_critical><![CDATA[" . $data->{cmin} . "]]></min_critical>\n";
	}
	if (! (empty ($data->{cmax})) ){
		$xml_module .= "\t<max_critical><![CDATA[" . $data->{cmax} . "]]></max_critical>\n";
	}
	if (! (empty ($data->{wstr}))) {
		$xml_module .= "\t<str_warning><![CDATA[" . $data->{wstr} . "]]></str_warning>\n";
	}
	if (! (empty ($data->{cstr}))) {
		$xml_module .= "\t<str_critical><![CDATA[" . $data->{cstr} . "]]></str_critical>\n";
	}
	if (! (empty ($data->{cinv}))) {
		$xml_module .= "\t<critical_inverse><![CDATA[" . $data->{cinv} . "]]></critical_inverse>\n";
	}
	if (! (empty ($data->{winv}))) {
		$xml_module .= "\t<warning_inverse><![CDATA[" . $data->{winv} . "]]></warning_inverse>\n";
	}
	if (! (empty ($data->{max}))) {
		$xml_module .= "\t<max><![CDATA[" . $data->{max} . "]]></max>\n";
	}
	if (! (empty ($data->{min}))) {
		$xml_module .= "\t<min><![CDATA[" . $data->{min} . "]]></min>\n";
	}
	if (! (empty ($data->{post_process}))) {
		$xml_module .= "\t<post_process><![CDATA[" . $data->{post_process} . "]]></post_process>\n";
	}
	if (! (empty ($data->{disabled}))) {
		$xml_module .= "\t<disabled><![CDATA[" . $data->{disabled} . "]]></disabled>\n";
	}
	if (! (empty ($data->{min_ff_event}))) {
		$xml_module .= "\t<min_ff_event><![CDATA[" . $data->{min_ff_event} . "]]></min_ff_event>\n";
	}
	if (! (empty ($data->{status}))) {
		$xml_module .= "\t<status><![CDATA[" . $data->{status} . "]]></status>\n";
	}
	if (! (empty ($data->{timestamp}))) {
		$xml_module .= "\t<timestamp><![CDATA[" . $data->{timestamp} . "]]></timestamp>\n";
	}
	if (! (empty ($data->{custom_id}))) {
		$xml_module .= "\t<custom_id><![CDATA[" . $data->{custom_id} . "]]></custom_id>\n";
	}
	if (! (empty ($data->{critical_instructions}))) {
		$xml_module .= "\t<critical_instructions><![CDATA[" . $data->{critical_instructions} . "]]></critical_instructions>\n";
	}
	if (! (empty ($data->{warning_instructions}))) {
		$xml_module .= "\t<warning_instructions><![CDATA[" . $data->{warning_instructions} . "]]></warning_instructions>\n";
	}
	if (! (empty ($data->{unknown_instructions}))) {
		$xml_module .= "\t<unknown_instructions><![CDATA[" . $data->{unknown_instructions} . "]]></unknown_instructions>\n";
	}
	if (! (empty ($data->{quiet}))) {
		$xml_module .= "\t<quiet><![CDATA[" . $data->{quiet} . "]]></quiet>\n";
	}
	if (! (empty ($data->{module_ff_interval}))) {
		$xml_module .= "\t<module_ff_interval><![CDATA[" . $data->{module_ff_interval} . "]]></module_ff_interval>\n";
	}
	if (! (empty ($data->{crontab}))) {
		$xml_module .= "\t<crontab><![CDATA[" . $data->{crontab} . "]]></crontab>\n";
	}
	if (! (empty ($data->{min_ff_event_normal}))) {
		$xml_module .= "\t<min_ff_event_normal><![CDATA[" . $data->{min_ff_event_normal} . "]]></min_ff_event_normal>\n";
	}
	if (! (empty ($data->{min_ff_event_warning}))) {
		$xml_module .= "\t<min_ff_event_warning><![CDATA[" . $data->{min_ff_event_warning} . "]]></min_ff_event_warning>\n";
	}
	if (! (empty ($data->{min_ff_event_critical}))) {
		$xml_module .= "\t<min_ff_event_critical><![CDATA[" . $data->{min_ff_event_critical} . "]]></min_ff_event_critical>\n";
	}
	if (! (empty ($data->{ff_timeout}))) {
		$xml_module .= "\t<ff_timeout><![CDATA[" . $data->{ff_timeout} . "]]></ff_timeout>\n";
	}
	if (! (empty ($data->{each_ff}))) {
		$xml_module .= "\t<each_ff><![CDATA[" . $data->{each_ff} . "]]></each_ff>\n";
	}
	if (! (empty ($data->{parent_unlink}))) {
		$xml_module .= "\t<module_parent_unlink><![CDATA[" . $data->{parent_unlink} . "]]></module_parent_unlink>\n";
	}
	if (! (empty ($data->{alerts}))) {
		foreach my $alert (@{$data->{alerts}}){
			$xml_module .= "\t<alert_template><![CDATA[" . $alert . "]]></alert_template>\n";
		}
	}
	if (defined ($conf->{global_alerts})){
		foreach my $alert (@{$conf->{global_alerts}}){
			$xml_module .= "\t<alert_template><![CDATA[" . $alert . "]]></alert_template>\n";
		}
	}

	$xml_module .= "</module>\n";

	if (empty ($not_print_flag)) {
		print $xml_module;	
	}

	return $xml_module;
}

################################################################################
# transfer_xml
################################################################################
sub transfer_xml {
	my ($conf, $xml, $name) = @_;
	my $file_name;
	my $file_path;

	if (! (empty ($name))) {
		$file_name = $name . "_" . time() . ".data";
	}
	else {
		# Inherit file name
		($file_name) = $xml =~ /\s+agent_name='(.*?)'\s+.*$/m;
		if (empty($file_name)){
			($file_name) = $xml =~ /\s+agent_name="(.*?)"\s+.*$/m;
		}
		if (empty($file_name)){
			$file_name = trim(`hostname`);
		}

		$file_name .=  "_" . time() . ".data";
	}

	$file_path = $conf->{temp} . "/" . $file_name;
	
	#Creating XML file in temp directory
	
	if ( -e $file_path ) {
		sleep (1);
		$file_name = $name . "_" . time() . ".data";
		$file_path = $conf->{temp} . "/" . $file_name;
	}

	open (FD, ">>", $file_path) or print_stderror($conf, "Cannot write to [" . $file_path . "]");
	
	my $bin_opts = ':raw:encoding(UTF8)';
	
	if ($^O eq "Windows") {
		$bin_opts .= ':crlf';
	}
	
	binmode(FD, $bin_opts);

	print FD $xml;

	close (FD);

	if (!defined($conf->{mode} && (defined($conf->{transfer_mode})))) {
		$conf->{mode} = $conf->{transfer_mode};
	}
	else {
		print_stderror($conf, "Transfer mode not defined in configuration.");
		return undef;
	}

	#Transfering XML file
	if ($conf->{mode} eq "tentacle") {

		#Send using tentacle
		if ($^O =~ /win/i) {
			`$conf->{tentacle_client} -v -a $conf->{tentacle_ip} -p $conf->{tentacle_port} $conf->{tentacle_opts} "$file_path"`;
		}
		else {
			`$conf->{tentacle_client} -v -a $conf->{tentacle_ip} -p $conf->{tentacle_port} $conf->{tentacle_opts} "$file_path" 2>&1 > /dev/null`;
		}
			
			
		#If no errors were detected delete file	
		
		if (! $?) {
			unlink ($file_path);
			} else {
				print STDERR "There was a problem sending file [$file_path] using tentacle\n";
				return undef;
			}	
		} 
		else {
		#Copy file to local folder
		my $dest_dir = $conf->{local_folder};

		my $rc = copy($file_path, $dest_dir);

		#If there was no error, delete file
		if ($rc == 0) {
			print STDERR "There was a problem copying local file to $dest_dir: $!\n";
			return undef;
		} 
		else {
			unlink ($file_path);
		}
	}
	return 1;
}

################################################################################
# Plugin mesage as module
################################################################################
sub print_message {
	my ($conf, $data) = @_;

	if (is_enabled($conf->{'as_server_plugin'})) {
		print $data->{value};
	}
	else { # as agent plugin
		print_module($conf, $data);
	}
}

################################################################################
# Module warning
#      - tag: name
#      - value: severity (default 0)
#      - msg: description of the message
################################################################################
sub print_warning {
	my ($conf, $tag, $msg, $value) = @_;

	if (!(is_enabled($conf->{informational_modules}))) {
		return 0;
	}
	
	print_module($conf, {
		name  => "Plugin message" . ( $tag?" " . $tag:""),
		type  => "generic_data",
		value => (defined($value)?$value:0),
		desc  => $msg,
		wmin  => 1,
		cmin  => 3,
	});
}

################################################################################
# Plugin mesage as module
################################################################################
sub print_execution_result {
	my ($conf, $msg, $value) = @_;

	if (!(is_enabled($conf->{informational_modules}))) {
		return 0;
	}

	print_module($conf, {
		name  => "Plugin execution result",
		type  => "generic_proc",
		value => (defined($value)?$value:0),
		desc  => $msg,
	});
}

################################################################################
## Plugin devolution in case of error
################################################################################
sub print_error {
	my ($conf, $msg) = @_;

	if (!(is_enabled($conf->{informational_modules}))) {
		return 0;
	}

	print_module($conf, {
		name  => "Plugin execution result",
		type  => "generic_proc",
		value => 0,
		desc  => $msg,
	});
	exit 1;
}

################################################################################
## Plugin message to SDTDOUT
################################################################################
sub print_stderror {
	my ($conf, $msg, $always_show) = @_;

	if(is_enabled($conf->{debug}) || (is_enabled($always_show))) {
		print STDERR strftime ("%Y-%m-%d %H:%M:%S", localtime()) . ": " . $msg;
	}
}


################################################################################
# Log data
################################################################################
my $log_aux_flag = 0;
sub logger {
	my ($conf, $tag, $message) = @_;
	my $file = $conf->{log};
	print_error($conf, "Log file undefined\n") unless defined $file;

	# Log rotation
	if (-e $file && (stat($file))[7] > 32000000) {
		rename ($file, $file.'.old');
	}
	my $LOGFILE;
	if ($log_aux_flag == 0) {
		# Log starts
		if (! open ($LOGFILE, "> $file")) {
			print_error ($conf, "[ERROR] Could not open logfile '$file'");
		}
		$log_aux_flag = 1;
	}
	else {
		if (! open ($LOGFILE, ">> $file")) {
			print_error ($conf, "[ERROR] Could not open logfile '$file'");
		}
	}

	$message = "[" . $tag . "] " . $message if ((defined $tag) && ($tag ne ""));

	if (!(empty($conf->{agent_name}))){
		$message = "[" . $conf->{agent_xml_name} . "] " . $message;
	}

	print $LOGFILE strftime ("%Y-%m-%d %H:%M:%S", localtime()) . " - " . $message . "\n";
	close ($LOGFILE);
}

################################################################################
# is Enabled 
################################################################################
sub is_enabled {
	my $value = shift;
	
	if ((defined ($value)) && ($value > 0)){
		# return true
		return 1;
	}
	#return false
	return 0;

}

################################################################################
# Launch URL call
################################################################################
sub call_url {
	my $conf = shift;
	my $call = shift;
	my @options = @_;
	my $_PluginTools_system = get_sys_environment($conf);

	if (empty($_PluginTools_system->{ua})) {
		return {
			error => "Uninitialized, please initialize UserAgent first"
		};
	}
	my $response = $_PluginTools_system->{ua}->get($call, @options);

	if ($response->is_success){
		return $response->decoded_content;
	}
	return undef;
}

################################################################################
# Launch URL call (POST)
################################################################################
sub post_url {
	my $conf = shift;
	my $url = shift;
	my @options = @_;
	my $_PluginTools_system = $conf->{'__system'};
	
	if (empty($_PluginTools_system->{ua})) {
		return {
			error => "Uninitialized, please initialize UserAgent first"
		};
	}
	my $response = $_PluginTools_system->{ua}->request(POST "$url", @options);

	if ($response->is_success){
		return $response->decoded_content;
	}
	return undef;	
}

################################################################################
# initialize plugin (advanced - hashed configuration)
################################################################################
sub init {
	my $options = shift;
	my $conf;

	eval {
		$conf = init_system($options);

		if (defined($options->{lwp_enable})) {
			if (empty($options->{lwp_timeout})) {
				$options->{lwp_timeout} = 3;
			}

			$conf->{'__system'}->{ua} = LWP::UserAgent->new((keep_alive => "10"));
			$conf->{'__system'}->{ua}->timeout($options->{lwp_timeout});
			# Enable environmental proxy settings
			$conf->{'__system'}->{ua}->env_proxy;
			# Enable in-memory cookie management
			$conf->{'__system'}->{ua}->cookie_jar( {} );
			if ( defined($options->{ssl_verify}) && ( ($options->{ssl_verify} eq "no") || (!is_enabled($options->{ssl_verify})) ) ) {
				# Disable verify host certificate (only needed for self-signed cert)
				$conf->{'__system'}->{ua}->ssl_opts( 'verify_hostname' => 0 );
				$conf->{'__system'}->{ua}->ssl_opts( 'SSL_verify_mode' => 0x00 );
			}
		}
	};
	if($@) {
		# Failed
		return {
			error => $@
		};
	}

	return $conf;
}

################################################################################
# initialize plugin (basic)
################################################################################
sub init_system {
	my ($conf) = @_;

	my %system;

	if ($^O =~ /win/i ){
		$system{devnull} = "NUL";
		$system{cat}     = "type";
		$system{os}      = "Windows";
		$system{ps}      = "tasklist";
		$system{grep}    = "findstr";
		$system{echo}    = "echo";
		$system{wcl}     = "wc -l";
	}
	else {
		$system{devnull} = "/dev/null";
		$system{cat}     = "cat";
		$system{os}      = "Linux";
		$system{ps}      = "ps -eo pmem,pcpu,comm";
		$system{grep}    = "grep";
		$system{echo}    = "echo";
		$system{wcl}     = "wc -l";

		if ($^O =~ /hpux/i) {
			$system{os}      = "HPUX";
			$system{ps}      = "ps -eo pmem,pcpu,comm";
		}

		if ($^O =~ /solaris/i ) {
			$system{os}      = "solaris";
			$system{ps}      = "ps -eo pmem,pcpu,comm";
		}
	}

	$conf->{'__system'} = \%system;
	return $conf;
}

################################################################################
# Return system environment
################################################################################
sub get_sys_environment {
	my $conf = shift;

	if (ref ($conf) eq "HASH") {
		return $conf->{'__system'};
	}
	return undef;
}

################################################################################
# General arguments parser
################################################################################
sub parse_arguments {
	my $raw = shift;
	my @args;
	if (defined($raw)){
		@args = @{$raw};
	}
	else {
		return {};
	}
	
	my %data;
	for (my $i = 0; $i < $#args; $i+=2) {
		my $key = trim($args[$i]);

		$key =~  s/^-//;
		$data{$key} = trim($args[$i+1]);
	}

	return \%data;

}

################################################################################
# General configuration file parser
#
# log=/PATH/TO/LOG/FILE
#
################################################################################
sub parse_configuration {
	my $conf_file = shift;
	my $separator;
	$separator = shift or $separator = "=";
	my $custom_eval = shift;
	my $_CFILE;

	my $_config;

	if (empty($conf_file)) {
		return {
			error => "Configuration file not specified"
		};
	}

	if( !open ($_CFILE,"<", "$conf_file")) {
		return {
			error => "Cannot open configuration file"
		};
	}

	while (my $line = <$_CFILE>){
		if (($line =~ /^ *\r*\n*$/)
		 || ($line =~ /^#/ )){
		 	# skip blank lines and comments
			next;
		}
		my @parsed = split /$separator/, $line, 2;
		if ($line =~ /^\s*global_alerts/){
			push (@{$_config->{global_alerts}}, trim($parsed[1]));
			next;
		}
		if (ref ($custom_eval) eq "ARRAY") {
			my $f = 0;
			foreach my $item (@{$custom_eval}) {
				if ($line =~ /$item->{exp}/) {
					$f = 1;
					my $aux;
					eval {
						$aux = $item->{target}->($item->{exp},$line);
					};

					if (empty($_config)) {
						$_config = $aux;
					}
					elsif (!empty($aux)  && (ref ($aux) eq "HASH")) {
						$_config = merge_hashes($_config, $aux);
					}
				}
			}

			if (is_enabled($f)){
				next;
			}
		}
		$_config->{trim($parsed[0])} = trim($parsed[1]);
	}
	close ($_CFILE);

	return $_config;
}

################################################################################
# PHP file parser
#
# log=/PATH/TO/LOG/FILE
#
################################################################################
sub parse_php_configuration {
	my $conf_file = shift;
	my $separator = shift;

	if (!defined($separator)){
		$separator = "=";
	}
	my %_config;

	open (my $_CFILE,"<", "$conf_file") or return undef;

	my $comment_block = 0;
	my $in_php = 0;
	while (my $line = <$_CFILE>){
		if ($line =~ /.*\<\?php/ ){
			$in_php = 1;
			$line =~ s/<\?php//g;
		}
		if($line =~ /.*\<\?/ ) {
			$in_php = 1;
			$line =~ s/<\?//g;
		}
		if ($in_php == 1){
			if ( ( $comment_block == 1)
			  && ( $line =~ /\*\// )) { # search multiline comment end

			  	# remove commented content:
			  	$line =~ s/.*?(\*\/)//g;

				$comment_block = 0;
			}
			if ($comment_block == 1){ # ignore lines in commented block
				next;
			}
			$line =~ /\/\*[^(?\*\/)]*/; # remove block comment
			if ($line =~ /\/\*/ ) {
				# multiline block comment detected!
				$comment_block = 1;
				next;
			}
			
			if ($line =~ /.*\?\>/ ){
				$in_php = 0;
				$line =~ s/\?\>.*//g;
			}
			$line =~ s/\/\*.*\*\///g; # Erase inline comments
			$line =~ s/\/\/.*//g;     # Erase inline comments

			chomp($line);
			if ($line =~ /^\s*$/){
			 	# skip blank and empty lines
				next;
			}

			my @parsed = split /$separator/, $line, 2;
			$_config{trim($parsed[0])} = trim($parsed[1]);
			$_config{trim($parsed[0])} =~ s/[";]//g;
		}
	}
	close ($_CFILE);

	return %_config;
}

################################################################################
# Process performance
################################################################################
sub process_performance {
	my ($conf, $process, $mod_name, $only_text_flag) = @_;
	my $_PluginTools_system = $conf->{'__system'};

	my $cpu;
	my $mem;
	my $instances;
	my $runit = "%";
	my $cunit = "%";

	$mod_name = $process if (empty($mod_name));
	
	if ($^O =~ /win/i) {
		my $out = trim(`(FOR /F \"skip=2 tokens=2 delims=,\" %P IN ('typeperf \"\\Proceso($process)\\% de tiempo de procesador\" -sc 1') DO \@echo %P) | find /V /I \"...\"  2> $_PluginTools_system->{devnull}`);

		if ( ($out =~ /member/i) 
		  || ($out =~ /error/i) 
		  || (! $out =~ /satisfact/i )) {
		  	$out = trim(`(FOR /F \"skip=2 tokens=2 delims=,\" %P IN ('typeperf \"\\Process($process)\\% Processor Time\" -sc 1') DO \@echo %P) | find /V /I \"...\"  2> $_PluginTools_system->{devnull}`);
		}
		if ( ($out =~ /member/i) 
		  || ($out =~ /error/i) 
		  || (! $out =~ /successfully/i )) {
		  	$cpu = 0;
		}
		$out =~ s/\"//g;

		if (! looks_like_number($out)){
			print STDERR "CPU usage [$out] is not numeric\n";
			$out = 0;
		}

		$cpu = sprintf '%.2f', $out;

		$mem = (split /\s+/, trim(`$_PluginTools_system->{ps} | $_PluginTools_system->{grep} \"$process\"`))[-2];
		if (! empty($mem)) {
			$mem =~ s/,/./;
		}
		else {
			$mem = 0;
		}
		$runit = "K";

		$instances = trim (head(`$_PluginTools_system->{ps} | $_PluginTools_system->{grep} "$process"| $_PluginTools_system->{wcl}`, 1));

    }
	elsif ($^O =~ /linux/i ){
		$cpu = trim(`$_PluginTools_system->{ps} | $_PluginTools_system->{grep} -w "$process" | $_PluginTools_system->{grep} -v grep | awk 'BEGIN {sum=0} {sum+=\$2} END{print sum}'`);
		$mem = trim(`$_PluginTools_system->{ps} | $_PluginTools_system->{grep} -w "$process" | $_PluginTools_system->{grep} -v grep | awk 'BEGIN {sum=0} {sum+=\$3} END{print sum}'`);
		$instances = trim(`$_PluginTools_system->{ps} | $_PluginTools_system->{grep} -w "$process" | $_PluginTools_system->{grep} -v grep | $_PluginTools_system->{wcl}`);
	}
	elsif ($^O =~ /hpux/ ) {
		$cpu = trim(`UNIX95= ps -eo pcpu,comm | $_PluginTools_system->{grep} -w "$process" |  $_PluginTools_system->{grep} -v grep | awk 'BEGIN {sum=0} {sum+=\$1} END{printf("\%.2f",sum)}'`);
		$mem = trim(`UNIX95= ps -eo vsz,comm | $_PluginTools_system->{grep} -w "$process" |  $_PluginTools_system->{grep} -v grep | awk 'BEGIN {sum=0} {sum+=(\$1*4096/1048576)} END{printf("\%.2f",sum)}'`);
		$instances = trim(`$_PluginTools_system->{ps} | $_PluginTools_system->{grep} -w "$process" | $_PluginTools_system->{grep} -v grep | $_PluginTools_system->{wcl}`);
		$runit = "MB";
	}
	elsif ($^O =~ /solaris/i) {
		$cpu = trim(`UNIX95= ps -eo pcpu,comm | $_PluginTools_system->{grep} -w "$process" |  $_PluginTools_system->{grep} -v grep | awk 'BEGIN {sum=0} {sum+=\$1} END{printf("\%.2f",sum)}'`);
		$mem = trim(`UNIX95= ps -eo pmem,comm | $_PluginTools_system->{grep} -w "$process" |  $_PluginTools_system->{grep} -v grep | awk 'BEGIN {sum=0} {sum+=\$1} END{printf("\%.2f",sum)}'`);
		$instances = trim(`$_PluginTools_system->{ps} | $_PluginTools_system->{grep} -w "$process" | $_PluginTools_system->{grep} -v grep | $_PluginTools_system->{wcl}`);
		$runit = "%";
	}
	elsif ($^O =~ /aix/i ) {
		$cpu = trim(`ps -Ao comm,pcpu |grep $process | grep -v grep | awk 'BEGIN {sum=0} {sum+=\$2} END {print sum}'`);
		$mem = trim(`ps au -A | grep $process |  grep -v grep | awk 'BEGIN {sum=0} {sum+=\$4} END {print sum}'`);
		$instances = trim(`ps -ef | grep "$process"|grep -v grep| wc -l`);
		$runit = "MB";
	}

	# print
	if (!looks_like_number($instances)){
		$instances = 0;
	}

	print_module ($conf, {
		name  => "$mod_name",
		type  => "generic_proc",
		desc  => "Presence of $process ($instances instances)",
		value => (($instances>0)?1:0),
	}, $only_text_flag);

	if ($instances > 0) {

		print_module ($conf, {
			name  => "$mod_name CPU usage",
			type  => "generic_data",
			desc  => "CPU usage of $process ($instances instances)",
			value => $cpu,
			unit  => $cunit
		}, $only_text_flag);

		print_module ($conf, {
			name  => "$mod_name RAM usage",
			type  => "generic_data",
			desc  => "RAM usage of $process ($instances instances)",
			value => $mem,
			unit  => $runit
		}, $only_text_flag);
	}

	return \[$cpu,$mem,$instances];
}

#########################################################################################
# Check api availability
#########################################################################################
sub api_available {
	my ($conf, $apidata) = @_;
	my ($api_url, $api_pass, $api_user, $api_user_pass) = ('','','','','');
	if (ref $apidata eq "ARRAY") {
	 	($api_url, $api_pass, $api_user, $api_user_pass) = @{$apidata};
	}

	$api_url       = $conf->{'api_url'}       unless empty($api_url);
	$api_pass      = $conf->{'api_pass'}      unless empty($api_pass);
	$api_user      = $conf->{'api_user'}      unless empty($api_user);
	$api_user_pass = $conf->{'api_user_pass'} unless empty($api_user_pass);

	my $op = "get";
	my $op2 = "test";

	my $call = $api_url . "?";
	$call .= "op=" . $op . "&op2=" . $op2;
	$call .= "&apipass=" . $api_pass . "&user=" . $api_user . "&pass=" . $api_user_pass;

	my $rs = call_url($conf, $call);

	if (ref $rs eq "HASH") {
		return {
			rs => 1,
			error => $rs->{error}
		};
	}
	else {
		return {
			rs => (empty($rs)?1:0),
			error => (empty($rs)?"Empty response.":undef),
			id => (empty($rs)?undef:trim($rs))
		}
	}

}

#########################################################################################
# Pandora API create custom field
#########################################################################################
sub api_create_custom_field {
	my ($conf, $apidata, $name, $display, $password) = @_;
	my ($api_url, $api_pass, $api_user, $api_user_pass) = ('','','','','');
	if (ref $apidata eq "ARRAY") {
	 	($api_url, $api_pass, $api_user, $api_user_pass) = @{$apidata};
	}

	$api_url       = $conf->{'api_url'}       unless empty($api_url);
	$api_pass      = $conf->{'api_pass'}      unless empty($api_pass);
	$api_user      = $conf->{'api_user'}      unless empty($api_user);
	$api_user_pass = $conf->{'api_user_pass'} unless empty($api_user_pass);



	$display  = 0 unless defined ($display);
	$password = 0 unless defined ($password);

	my $call;

	# 1st try to get previous custom field id
	my $op = "get";
	my $op2 = "custom_field";

	$call = $api_url . "?";
	$call .= "op=" . $op . "&op2=" . $op2;

	# Extra arguments
	if (!empty($name)) {
		$call .= "&other=" . $name;
	}
	if (!empty($display)) {
		$call .= "%7C" . $display;
	}
	if (!empty($password)) {
		$call .= "%7C" . $password;
	}
	
	$call .= "&other_mode=url_encode_separator=%7C&";
	$call .= "apipass=" . $api_pass . "&user=" . $api_user . "&pass=" . $api_user_pass;

	my $rs = call_url($conf, "$call");

	if (ref($rs) ne "HASH") {
		$rs = trim($rs);
	}
	else {
		# Failed to reach API, return with error
		return {
			rs => 1,
			error => 'Failed to reach API'
		};
	}
	
	if (empty($rs) || ($rs !~ /^\d+$/ || $rs eq "0")) {
		# Custom field is not defined
		
		# 2nd create only if the custom field does not exist
		$op = "set";
		$op2 = "create_custom_field";
		


		$call = $api_url . "?";
		$call .= "op=" . $op . "&op2=" . $op2;
		$call .= "&other=" . $name . "%7C" . $display . "%7C" . $password;
		$call .= "&other_mode=url_encode_separator=%7C&";
		$call .= "apipass=" . $api_pass . "&user=" . $api_user . "&pass=" . $api_user_pass;

		$rs = call_url($conf, "$call");
	}

	if (ref($rs) ne "HASH") {
		$rs = trim($rs);
	}
	else {
		# Failed to reach API, return with error
		return {
			rs => 1,
			error => 'Failed to reach API while creating custom field [' . $name . ']'
		};
	}

	if (empty($rs) || ($rs !~ /^\d+$/ || $rs eq "0")) {
		return {
			rs => 1,
			error => 'Failed while creating custom field [' . $name . '] => [' . $rs . ']'
		};
	}

	# Return the valid id
	return {
		rs => 0,
		id => $rs
	};
}

#########################################################################################
# Pandora API create tag
#########################################################################################
sub api_create_tag {
	my ($conf, $apidata, $tag, $desc, $url, $email) = @_;
	my ($api_url, $api_pass, $api_user, $api_user_pass) = ('','','','','');
	if (ref $apidata eq "ARRAY") {
	 	($api_url, $api_pass, $api_user, $api_user_pass) = @{$apidata};
	}

	$api_url       = $conf->{'api_url'}       unless empty($api_url);
	$api_pass      = $conf->{'api_pass'}      unless empty($api_pass);
	$api_user      = $conf->{'api_user'}      unless empty($api_user);
	$api_user_pass = $conf->{'api_user_pass'} unless empty($api_user_pass);

	my $op = "set";
	my $op2 = "create_tag";
	$desc = 'Created by PluginTools' unless defined $desc;

	my $call = $api_url . "?";
	$call .= "op=" . $op . "&op2=" . $op2;
	$call .= "&other=";
	if (!empty($tag)) {
		$call .= $tag . "%7C";
	}
	if (!empty($desc)) {
		$call .= $desc . "%7C";
	}
	if (!empty($url)) {
		$call .= $url . "%7C";
	}
	if (!empty($email)) {
		$call .= $email;
	}

	$call .= "&other_mode=url_encode_separator=%7C&";
	$call .= "apipass=" . $api_pass . "&user=" . $api_user . "&pass=" . $api_user_pass;

	my $rs = call_url($conf, $call);

	if (ref $rs eq "HASH") {
		return {
			rs => 1,
			error => $rs->{error}
		};
	}
	else {
		return {
			rs => (empty($rs)?1:0),
			error => (empty($rs)?"Empty response.":undef),
			id => (empty($rs)?undef:trim($rs))
		}
	}
}

#########################################################################################
# Pandora API create group
#########################################################################################
sub api_create_group {
	my ($conf, $apidata, $group_name, $group_config, $email) = @_;
	my ($api_url, $api_pass, $api_user, $api_user_pass);
	if (ref $apidata eq "ARRAY") {
	 	($api_url, $api_pass, $api_user, $api_user_pass) = @{$apidata};
	}


	if(empty ($group_config->{icon})) {
		return {
			rs => 1,
			error => "No icon set"
		};
	}
	# Group config:
	
	my $other = '';
	$other .= $group_config->{icon} . '%7C&';
	$other .= (empty($group_config->{parent})?'':$group_config->{parent}.'%7C&');
	$other .= (empty($group_config->{desc})?'':$group_config->{desc}.'%7C&');
	$other .= (empty($group_config->{propagate})?'':$group_config->{propagate}.'%7C&');
	$other .= (empty($group_config->{disabled})?'':$group_config->{disabled}.'%7C&');
	$other .= (empty($group_config->{custom_id})?'':$group_config->{custom_id}.'%7C&');
	$other .= (empty($group_config->{contact})?'':$group_config->{contact}.'%7C&');
	$other .= (empty($group_config->{other})?'':$group_config->{other}.'%7C&');

	$api_url       = $conf->{'api_url'}       unless defined $api_url;
	$api_pass      = $conf->{'api_pass'}      unless defined $api_pass;
	$api_user      = $conf->{'api_user'}      unless defined $api_user;
	$api_user_pass = $conf->{'api_user_pass'} unless defined $api_user_pass;

	my $op = "set";
	my $op2 = "create_group";

	my $call = $api_url . "?";
	$call .= "op=" . $op . "&op2=" . $op2;
	$call .= "&id=" . $group_name;
	$call .= "&other=" . $other . "&other_mode=url_encode_separator=%7C&";
	$call .= "apipass=" . $api_pass . "&user=" . $api_user . "&pass=" . $api_user_pass;

	my $rs = call_url($conf, $call);
	
	if (ref $rs eq "HASH") {
		return {
			rs => 1,
			error => $rs->{error}
		};
	}
	else {
		return {
			rs => (empty($rs)?1:0),
			error => (empty($rs)?"Empty response.":undef),
			id => (empty($rs)?undef:trim($rs))
		}
	}
}

################################################################################
# SNMP walk value
#  will return the snmpwalk output
#
#  $community (v1,2,2c)
#   -> means $context (v3)
# 
#  Configuration hash
# 
# 	$snmp{version}
# 	$snmp{community}
# 	$snmp{host}
# 	$snmp{oid}
# 	$snmp{port}
#   $snmp{securityName}
#   $snmp{context
#   $snmp{securityLevel}
#   $snmp{authProtocol}
#   $snmp{authKey}
#   $snmp{privProtocol}
#   $snmp{privKey}
################################################################################
sub snmp_walk {
	my $snmp = shift;
	my $cmd;
	my $timeout = 2;

	if (!empty($snmp->{timeout})) {
		$timeout = $snmp->{timeout};
	}

	if ( defined ($snmp->{version} )
	  && (($snmp->{version} eq "1")
	   || ($snmp->{version} eq "2")
	   || ($snmp->{version} eq "2c"))) {

		if (defined $snmp->{port}){
			$cmd = "snmpwalk -t $timeout -On -v $snmp->{version} -c $snmp->{community} $snmp->{host}:$snmp->{port} $snmp->{oid}";
		}
		else {
			$cmd = "snmpwalk -t $timeout -On -v $snmp->{version} -c $snmp->{community} $snmp->{host} $snmp->{oid}";
		}

	}
	elsif ( defined ($snmp->{version} )
	  && ($snmp->{version} eq "3") ) { # SNMP v3
		# Authentication required

		# $securityLevel = (noAuthNoPriv|authNoPriv|authPriv);

		# unauthenticated request
		# Ex. snmpwalk -t $timeout -On -v 3 -n "" -u noAuthUser -l noAuthNoPriv test.net-snmp.org sysUpTime

		# authenticated request
		# Ex. snmpwalk -t $timeout -On -v 3 -n "" -u MD5User -a MD5 -A "The Net-SNMP Demo Password" -l authNoPriv test.net-snmp.org sysUpTime

		# authenticated and encrypted request
		# Ex. snmpwalk -t $timeout -On -v 3 -n "" -u MD5DESUser -a MD5 -A "The Net-SNMP Demo Password" -x DES -X "The Net-SNMP Demo Password" -l authPriv test.net-snmp.org system

		if ($snmp->{securityLevel} =~ /^noAuthNoPriv$/i){
			# Unauthenticated request

			if (defined $snmp->{port}){
				$cmd = "snmpwalk -t $timeout -On -v $snmp->{version} -n \"$snmp->{context}\" -l $snmp->{securityLevel} $snmp->{host}:$snmp->{port} $snmp->{oid}";
			}
			else {
				$cmd = "snmpwalk -t $timeout -On -v $snmp->{version} -n \"$snmp->{context}\" -l $snmp->{securityLevel} $snmp->{host} $snmp->{oid}";
			}
		}
		elsif ($snmp->{securityLevel} =~ /^authNoPriv$/i){ 
			# Authenticated request

			if (defined $snmp->{port}){
				$cmd = "snmpwalk -t $timeout -On -v $snmp->{version} -n \"$snmp->{context}\" -u $snmp->{securityName} -a $snmp->{authProtocol} -A $snmp->{authKey} -l $snmp->{securityLevel} $snmp->{host}:$snmp->{port} $snmp->{oid}";
			}
			else {
				$cmd = "snmpwalk -t $timeout -On -v $snmp->{version} -n \"$snmp->{context}\" -u $snmp->{securityName} -a $snmp->{authProtocol} -A $snmp->{authKey} -l $snmp->{securityLevel} $snmp->{host} $snmp->{oid}";
			}
		}
		elsif ($snmp->{securityLevel} =~ /^authPriv$/i){
			# Authenticated and encrypted request

			if (defined $snmp->{port}){
				$cmd = "snmpwalk -t $timeout -On -v $snmp->{version} -n \"$snmp->{context}\" -u $snmp->{securityName} -l $snmp->{securityLevel} -a $snmp->{authProtocol} -A $snmp->{authKey} -x $snmp->{privProtocol} -X $snmp->{privKey} $snmp->{host}:$snmp->{port} $snmp->{oid}";
			}
			else {
				$cmd = "snmpwalk -t $timeout -On -v $snmp->{version} -n \"$snmp->{context}\" -u $snmp->{securityName} -l $snmp->{securityLevel} -a $snmp->{authProtocol} -A $snmp->{authKey} -x $snmp->{privProtocol} -X $snmp->{privKey} $snmp->{host} $snmp->{oid}";
			}
		}
	}
	else {
		return {
			error => "Only SNMP 1 2 2c and 3 are supported."
		}
	}
	#print STDERR "Launching $cmd\n";
	my $result = `$cmd 2>/dev/null`;
	if ($? != 0){
		return {
			error => "No response from " . trim($snmp->{host})
		};
	}
	return $result;

}

################################################################################
# SNMP get value
#  will return a hash with the data and datatype
#
#  $community (v1,2,2c)
#   -> means $context (v3)
# 
#  Configuration hash
# 
# 	$snmp{version}
# 	$snmp{community}
# 	$snmp{host}
# 	$snmp{oid}
# 	$snmp{port}
#   $snmp{securityName}
#   $snmp{context
#   $snmp{securityLevel}
#   $snmp{authProtocol}
#   $snmp{authKey}
#   $snmp{privProtocol}
#   $snmp{privKey}
################################################################################
sub snmp_get {
	my $snmp = shift;
	my $cmd;
	my $timeout = 2;
	my $retries = 1;

	if (!empty($snmp->{retries})) {
		$retries = $snmp->{retries};
	}
	
	if (!empty($snmp->{timeout})) {
		$timeout = $snmp->{timeout};
	}

	if ( defined ($snmp->{version} )
	  && (($snmp->{version} eq "1")
	   || ($snmp->{version} eq "2")
	   || ($snmp->{version} eq "2c"))) {

		if (defined $snmp->{port}){
			$cmd = "snmpget -r $retries -t $timeout -On -v $snmp->{version} -c $snmp->{community} $snmp->{host}:$snmp->{port} $snmp->{oid}";
		}
		else {
			$cmd = "snmpget -r $retries -t $timeout -On -v $snmp->{version} -c $snmp->{community} $snmp->{host} $snmp->{oid}";
		}

	}
	elsif ( defined ($snmp->{version} )
	  && ($snmp->{version} eq "3") ) { # SNMP v3
		# Authentication required

		# $securityLevel = (noAuthNoPriv|authNoPriv|authPriv);

		# unauthenticated request
		# Ex. snmpget -r $retries -t $timeout -On -v 3 -n "" -u noAuthUser -l noAuthNoPriv test.net-snmp.org sysUpTime

		# authenticated request
		# Ex. snmpget -r $retries -t $timeout -On -v 3 -n "" -u MD5User -a MD5 -A "The Net-SNMP Demo Password" -l authNoPriv test.net-snmp.org sysUpTime

		# authenticated and encrypted request
		# Ex. snmpget -r $retries -t $timeout -On -v 3 -n "" -u MD5DESUser -a MD5 -A "The Net-SNMP Demo Password" -x DES -X "The Net-SNMP Demo Password" -l authPriv test.net-snmp.org system

		if ($snmp->{securityLevel} =~ /^noAuthNoPriv$/i){
			# Unauthenticated request

			if (defined $snmp->{port}){
				$cmd = "snmpget -r $retries -t $timeout -On -v $snmp->{version} -n \"$snmp->{context}\" -l $snmp->{securityLevel} $snmp->{host}:$snmp->{port} $snmp->{oid}";
			}
			else {
				$cmd = "snmpget -r $retries -t $timeout -On -v $snmp->{version} -n \"$snmp->{context}\" -l $snmp->{securityLevel} $snmp->{host} $snmp->{oid}";
			}
		}
		elsif ($snmp->{securityLevel} =~ /^authNoPriv$/i){ 
			# Authenticated request

			if (defined $snmp->{port}){
				$cmd = "snmpget -r $retries -t $timeout -On -v $snmp->{version} -n \"$snmp->{context}\" -u $snmp->{securityName} -a $snmp->{authProtocol} -A $snmp->{authKey} -l $snmp->{securityLevel} $snmp->{host}:$snmp->{port} $snmp->{oid}";
			}
			else {
				$cmd = "snmpget -r $retries -t $timeout -On -v $snmp->{version} -n \"$snmp->{context}\" -u $snmp->{securityName} -a $snmp->{authProtocol} -A $snmp->{authKey} -l $snmp->{securityLevel} $snmp->{host} $snmp->{oid}";
			}
		}
		elsif ($snmp->{securityLevel} =~ /^authPriv$/i){
			# Authenticated and encrypted request

			if (defined $snmp->{port}){
				$cmd = "snmpget -r $retries -t $timeout -On -v $snmp->{version} -n \"$snmp->{context}\" -u $snmp->{securityName} -l $snmp->{securityLevel} -a $snmp->{authProtocol} -A $snmp->{authKey} -x $snmp->{privProtocol} -X $snmp->{privKey} $snmp->{host}:$snmp->{port} $snmp->{oid}";
			}
			else {
				$cmd = "snmpget -r $retries -t $timeout -On -v $snmp->{version} -n \"$snmp->{context}\" -u $snmp->{securityName} -l $snmp->{securityLevel} -a $snmp->{authProtocol} -A $snmp->{authKey} -x $snmp->{privProtocol} -X $snmp->{privKey} $snmp->{host} $snmp->{oid}";
			}
		}
	}
	else {
		return {
			error => "Only SNMP 1 2 2c and 3 are supported."
		}
	}
	#print STDERR "Launched: $cmd\n";
	my $result = `$cmd`;
	if ($? != 0) {
		return {
			error => "No response from " . trim($snmp->{host})
		};
	}
	return snmp_data_switcher((split /=\ /, $result)[1]);

}

################################################################################
# returns a hash with [type->datatype][data->value]
################################################################################
sub snmp_data_switcher {
	my @st_data = split /\: /, $_[0];
	my %data;

	my $pure_data = trim($st_data[1]) or undef;
	$data{data} = $pure_data;
	
	if ( uc($st_data[0]) eq uc("INTEGER")) {
		$data{type} = "generic_data";
	}
	elsif (uc($st_data[0]) eq uc("Integer32")) {
		$data{type} = "generic_data";
	}
	elsif (uc($st_data[0]) eq uc("octect string")) {
		$data{type} = "generic_data";
	}
	elsif (uc($st_data[0]) eq uc("bits")) {
		$data{type} = "generic_data";
	}
	elsif (uc($st_data[0]) eq uc("object identifier")) {
		$data{type} = "generic_data_string";
	}
	elsif (uc($st_data[0]) eq uc("IpAddress")) {
		$data{type} = "generic_data_string";
	}
	elsif (uc($st_data[0]) eq uc("Counter")) {
		$data{type} = "generic_data_inc";
	}
	elsif (uc($st_data[0]) eq uc("Counter32")) {
		$data{type} = "generic_data_inc";
	}
	elsif (uc($st_data[0]) eq uc("Gauge")) {
		$data{type} = "generic_data";
	}
	elsif (uc($st_data[0]) eq uc("Unsigned32")) {
		$data{type} = "generic_data_inc";
	}
	elsif (uc($st_data[0]) eq uc("TimeTicks")) {
		$data{type} = "generic_data_string";
	}
	elsif (uc($st_data[0]) eq uc("Opaque")) {
		$data{type} = "generic_data_string";
	}
	elsif (uc($st_data[0]) eq uc("Counter64")) {
		$data{type} = "generic_data_inc";
	}
	elsif (uc($st_data[0]) eq uc("UInteger32")) {
		$data{type} = "generic_data";
	}
	elsif (uc($st_data[0]) eq uc("BIT STRING")) {
		$data{type} = "generic_data_string";
	}
	elsif (uc($st_data[0]) eq uc("STRING")) {
		$data{type} = "generic_data_string";
	}
	else {
		$data{type} = "generic_data_string";
	}

	if ($data{type} eq "generic_data"){
		($data{data} = $pure_data) =~ s/\D*//g;
	}

	return \%data;
}

################################################################################
# returns a encrypted string
#  $1 => string to be encrypted
#  $2 => salt to use (default default_salt)
################################################################################
sub encrypt {
	my ($str,$salt,$iv) = @_;

	return undef unless (load_perl_modules('Crypt::CBC', 'Crypt::OpenSSL::AES','Digest::SHA') == 1);

	if (empty($salt)) {
		$salt = "default_salt";
	}

	my $processed_salt = substr(hmac_sha256_base64($salt,''), 0, 16);

	if (empty($iv)) {
		$iv = "0000000000000000";
	}

	my $cipher = Crypt::CBC->new({
		'key'         => $processed_salt,
		'cipher'      => 'Crypt::OpenSSL::AES',
		'iv'          => $iv, 
		'literal_key' => 1,
		'header'      => 'none',
		'keysize'     => 128 / 8
	});
	
	my $encrypted = encode_base64($cipher->encrypt($str));

	return $encrypted;

}

################################################################################
# returns a decrypted string from an encrypted one
#  $1 => string to be decrypted
#  $2 => salt to use (default default_salt)
################################################################################
sub decrypt {
	my ($encrypted_str,$salt,$iv) = @_;

	return undef unless (load_perl_modules('Crypt::CBC', 'Crypt::OpenSSL::AES','Digest::SHA') == 1);

	if (empty($salt)) {
		$salt = "default_salt";
	}

	my $processed_salt = substr(hmac_sha256_base64($salt,''), 0, 16);

	if (empty($iv)) {
		$iv = "0000000000000000";
	}

	my $cipher = Crypt::CBC->new({
		'key'         => $processed_salt,
		'cipher'      => 'Crypt::OpenSSL::AES',
		'iv'          => $iv, 
		'literal_key' => 1,
		'header'      => 'none',
		'keysize'     => 128 / 8
	});

	my $decrypted = $cipher->decrypt(decode_base64($encrypted_str));

	return $decrypted;

}

################################################################################
# Return unix timestamp from a given string
################################################################################
sub get_unix_time {
	my ($str_time,$separator_dates,$separator_hours) = @_;

	if (empty($separator_dates)) {
		$separator_dates = "\/";
	}

	if (empty($separator_hours)) {
		$separator_hours = ":";
	}


	use Time::Local;
	my ($mday,$mon,$year,$hour,$min,$sec) = split(/[\s$separator_dates$separator_hours]+/, $str_time);
	my $time = timelocal($sec,$min,$hour,$mday,$mon-1,$year);
	return $time;
}

################################################################################
# Load required modules. 
################################################################################
sub load_perl_modules {
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



1;
