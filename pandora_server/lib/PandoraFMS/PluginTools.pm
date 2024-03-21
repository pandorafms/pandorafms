package PandoraFMS::PluginTools;
################################################################################
#
# Pandora FMS Plugin functions library
#
#  (c) Fco de Borja Sanchez <fborja.sanchez@artica.es>
# 
# Requirements:
#  Library              Centos package
#  -------              --------------
#  LWP::UserAgent       perl-libwww-perl
# 
################################################################################

use strict;
use warnings;

use LWP::UserAgent;
use HTTP::Cookies;
use HTTP::Request::Common;
use Socket qw(inet_ntoa inet_aton);
use File::Copy;
use Scalar::Util qw(looks_like_number);
use Time::HiRes qw(time);
eval "use POSIX::strftime::GNU;1" if ($^O =~ /win/i);
use POSIX qw(strftime setsid floor);
use MIME::Base64;
use JSON qw(decode_json encode_json);
use PerlIO::encoding;

use base 'Exporter';

our @ISA = qw(Exporter);

# version: Defines actual version of Pandora Server for this module only
my $pandora_version = "7.0NG.776";
my $pandora_build = "240321";
our $VERSION = $pandora_version." ".$pandora_build;

our %EXPORT_TAGS = ( 'all' => [ qw() ] );

our @EXPORT_OK = ( @{ $EXPORT_TAGS{'all'} } );

our @EXPORT = qw(
	__ip_to_long
	__long_to_ip
	api_available
	api_call
	api_create_custom_field
	api_create_tag
	api_create_group
	call_url
	check_lib_version
	csv_to_obj
	decrypt
	empty
	encrypt
	extract_dbpass
	extract_key_map
	get_addresses
	get_current_utime_milis
	get_lib_version
	get_unit
	get_unix_time
	get_sys_environment
	get_value_translated
	getCurrentUTimeMilis
	head
	in_array
	init
	is_enabled
	join_by_field
	load_perl_modules
	logger
	mask_to_decimal
	merge_hashes
	parse_arguments
	parse_configuration
	parse_php_configuration
	process_performance
	post_url
	print_agent
	print_discovery_module
	print_error
	print_execution_result
	print_message
	print_module
	print_warning
	print_stderror
	read_configuration
	read_file
	simple_decode_json
	snmp_data_switcher
	snmp_get
	snmp_walk
	seconds2readable
	tail
	to_number
	transfer_xml
	trim
);

# ~ compat
my $DevNull = ($^O =~ /win/i)?'/NUL':'/dev/null';

################################################################################
# Returns current library version
################################################################################
sub get_lib_version {
	return $VERSION;
}

################################################################################
# Check version compatibility
################################################################################
sub check_lib_version {
	my ($plugin_version) = @_;

	$plugin_version = "0NG.0" if empty($plugin_version);

	my ($main,$oum) = ($plugin_version =~ m/(\d*\.?\d+)NG\.(\d*\.?\d+)/);

	$main = 0 if empty($main) || !looks_like_number($main);
	$oum  = 0 if empty($oum)  || !looks_like_number($oum);

	my ($libmain,$liboum) =  ($pandora_version =~ m/(\d*\.?\d+)NG\.(\d*\.?\d+)/);

	if (($liboum < $oum)
	||  ($libmain != $main)) {
		return 0;
	}

	return 1;
}

###############################################################################
# Returns IP address(v4) in longint format
###############################################################################
sub __ip_to_long {
	my $ip_str = shift;
	return unpack "N", inet_aton($ip_str);
}

###############################################################################
# Returns IP address(v4) in longint format
###############################################################################
sub __long_to_ip {
	my $ip_long = shift;
	return inet_ntoa pack("N", ($ip_long));
}

################################################################################
# Convert CSV string to hash
################################################################################
sub csv_to_obj {
	my ($csv) = @_;
	my @ahr;
	my @lines = split /\n/, $csv;

	return [] unless $#lines >= 0;

	# scan headers
	my @hr_headers = split /,/, shift @lines;

	# Clean \n\r
	@hr_headers = map { $_ =~ s/\"//g; trim($_); } @hr_headers;

	foreach my $line (@lines) {
		next if empty($line);
		
		my $i = 0;
		my %hr = map { $_ =~ s/\"//g; $hr_headers[$i++] => trim($_) } split /,/, $line;

		push @ahr, \%hr;
	}
	return \@ahr;
}

################################################################################
# Get current time (milis)
################################################################################
sub get_current_utime_milis { return getCurrentUTimeMilis(); }
sub getCurrentUTimeMilis {
	#return trim (`date +"%s%3N"`); # returns 1449681679712
	return floor(time*1000);
}

################################################################################
# Mask to decimal
################################################################################
sub mask_to_decimal {
	my $mask = shift;
	my ($a,$b,$c,$d) = $mask =~ /^(\d+)\.(\d+)\.(\d+)\.(\d+)$/;

	$a = sprintf "%08b", $a;
	$b = sprintf "%08b", $b;
	$c = sprintf "%08b", $c;
	$d = sprintf "%08b", $d;
	
	my $str = $a . $b . $c . $d;

	$str =~ s/0.*$//;

	return length($str);
}

################################################################################
# Mix hashses
################################################################################
sub merge_hashes {
	my $_h1 = shift;
	my $_h2 = shift;

	if (ref($_h1) ne "HASH") {
		return \%{$_h2} if (ref($_h2) eq "HASH");
	}

	if (ref($_h2) ne "HASH") {
		return \%{$_h1} if (ref($_h1) eq "HASH");
	}

	if ((ref($_h1) ne "HASH") && (ref($_h2) ne "HASH")) {
		return {};
	}

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
# Assing a value to a string key as subkeys in a hash map
################################################################################
sub extract_key_map;
sub extract_key_map {
	my ($hash, $string, $value) = @_;

	my ($key, $str) = split /\./, $string, 2;
	if (empty($str)) {
		$hash->{$key} = $value;

		return $hash;
	}

	$hash->{$key} = extract_key_map($hash->{$key}, $str, $value);

	return $hash;
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
# Get unit
################################################################################
sub get_value_translated {
	my $str = shift;

	if (empty($str)) {
		return $str;
	}
	$str = trim($str);

	my $value = $str;
	my $unit = get_unit($str);
	if(empty($unit)) {
		return $str;
	}

	$value =~ s/$unit//g;

	if ($unit =~ /kb/i) { return $value * (2**10);}
	if ($unit =~ /kib/i) { return $value * (2**10);}
	if ($unit =~ /mb/i) { return $value * (2**20);}
	if ($unit =~ /mib/i) { return $value * (2**20);}
	if ($unit =~ /gb/i) { return $value * (2**30);}
	if ($unit =~ /gib/i) { return $value * (2**30);}
	if ($unit =~ /tb/i) { return $value * (2**40);}
	
	return $value;

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

	my $group_password_specified = 0;

	foreach my $kad (keys %{$agent_data}){
		no warnings "uninitialized";
		$xml .= $kad . "='";
		$xml .= $agent_data->{$kad} . "' ";

		if ($kad eq 'group_password') {
			$group_password_specified = 1;
		}
	}

	if ($group_password_specified == 0 && !empty($config->{'group_password'})) {
		$xml .= " group_password='".$config->{'group_password'}."' ";
	}

	$xml .= ">";

	if (ref($modules_def) eq "ARRAY") {
		foreach my $module (@{$modules_def}) {
			if (ref($module) eq "HASH" && (defined $module->{'name'})) {
				$xml .= print_module($config, $module,1);
			} elsif (ref($module) eq "HASH" && (defined $module->{'discovery'})) {
				$xml .= print_discovery_module($config, $module,1);
			}
		}
	} elsif (ref($modules_def) eq "HASH" && (defined $modules_def->{'name'})) {
		$xml .= print_module($config, $modules_def,1);
	} elsif (ref($modules_def) eq "HASH" && (defined $modules_def->{'discovery'})) {
		$xml .= print_discovery_module($config, $modules_def,1);
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
sub print_discovery_module {
	my ($conf, $global_data, $not_print_flag) = @_;

	return undef if (ref($global_data) ne "HASH" || !defined($global_data->{'discovery'}));
	return "" if empty($global_data);

	my $data = $global_data->{'discovery'};

	my $xml_module = "<discovery><![CDATA[";
	$xml_module .= encode_base64(encode_json($data));
	$xml_module .= "]]></discovery>\n";

	if (empty ($not_print_flag)) {
		print $xml_module;	
	}

	return $xml_module;
}

################################################################################
# print_module
################################################################################
sub print_module {
	my ($conf, $data, $not_print_flag) = @_;

	if ((ref($data) ne "HASH") || (!defined $data->{name})) {
		return undef;
	}
	
	my $xml_module = "";
	# If not a string type, remove all blank spaces!    
	if ($data->{type} !~ m/string/){
		$data->{value} = trim($data->{value});
	}

	$data->{value} = '' if empty($data->{value});

	$data->{tags} = ($data->{tags} ?
		$data->{tags} : ($conf->{MODULE_TAG_LIST} ?
			$conf->{MODULE_TAG_LIST} : ($conf->{module_tag_list} ? 
				$conf->{module_tag_list} : undef)));

	$data->{interval} = ($data->{interval} ? 
		$data->{interval} : ($conf->{MODULE_INTERVAL} ?
			$conf->{MODULE_INTERVAL} : ($conf->{module_interval} ? 
				$conf->{module_interval} : undef)));

	$data->{module_group} = ($data->{module_group} ? 
		$data->{module_group} : ($conf->{MODULE_GROUP} ? 
			$conf->{MODULE_GROUP} : ( $conf->{module_group} ?
				$conf->{module_group} : undef)));
	

	# Global instructions (if defined)
	$data->{unknown_instructions}  = $conf->{unknown_instructions}  unless (defined($data->{unknown_instructions})  || (!defined($conf->{unknown_instructions})));
	$data->{warning_instructions}  = $conf->{warning_instructions}  unless (defined($data->{warning_instructions})  || (!defined($conf->{warning_instructions})));
	$data->{critical_instructions} = $conf->{critical_instructions} unless (defined($data->{critical_instructions}) || (!defined($conf->{critical_instructions})));

	# Translation compatibility
	$data->{min_warning}      = $data->{'wmin'} if empty($data->{min_warning});
	$data->{max_warning}      = $data->{'wmax'} if empty($data->{max_warning});
	$data->{min_critical}     = $data->{'cmin'} if empty($data->{min_critical});
	$data->{max_critical}     = $data->{'cmax'} if empty($data->{max_critical});
	$data->{warning_inverse}  = $data->{'winv'} if empty($data->{warning_inverse});
	$data->{critical_inverse} = $data->{'cinv'} if empty($data->{critical_inverse});
	$data->{str_warning}      = $data->{'wstr'} if empty($data->{str_warning});
	$data->{str_critical}     = $data->{'cstr'} if empty($data->{str_critical});

	$xml_module .= "<module>\n";
	$xml_module .= "\t<name><![CDATA[" . $data->{name} . "]]></name>\n";
	$xml_module .= "\t<type>" . $data->{type} . "</type>\n";

	if (ref ($data->{value}) eq "ARRAY") {
		$xml_module .= "\t<datalist>\n";
		foreach (@{$data->{value}}) {
			if ((ref($_) eq "HASH") && defined($_->{value})) {
				$xml_module .= "\t<data>\n";
				$xml_module .= "\t\t<value><![CDATA[" . $_->{value} . "]]></value>\n";
				if (defined($_->{timestamp})) {
					$xml_module .= "\t\t<timestamp><![CDATA[" . $_->{timestamp} . "]]></timestamp>\n";
				}
				$xml_module .= "\t</data>\n";
			}
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
	if (! (empty($data->{min_warning})) ) {
		$xml_module .= "\t<min_warning><![CDATA[" . $data->{min_warning} . "]]></min_warning>\n";
	}
	if (! (empty($data->{max_warning})) ) {
		$xml_module .= "\t<max_warning><![CDATA[" . $data->{max_warning} . "]]></max_warning>\n";
	}
	if (! (empty ($data->{min_critical})) ) {
		$xml_module .= "\t<min_critical><![CDATA[" . $data->{min_critical} . "]]></min_critical>\n";
	}
	if (! (empty ($data->{max_critical})) ){
		$xml_module .= "\t<max_critical><![CDATA[" . $data->{max_critical} . "]]></max_critical>\n";
	}
	if (! (empty ($data->{str_warning}))) {
		$xml_module .= "\t<str_warning><![CDATA[" . $data->{str_warning} . "]]></str_warning>\n";
	}
	if (! (empty ($data->{str_critical}))) {
		$xml_module .= "\t<str_critical><![CDATA[" . $data->{str_critical} . "]]></str_critical>\n";
	}
	if (! (empty ($data->{critical_inverse}))) {
		$xml_module .= "\t<critical_inverse><![CDATA[" . $data->{critical_inverse} . "]]></critical_inverse>\n";
	}
	if (! (empty ($data->{warning_inverse}))) {
		$xml_module .= "\t<warning_inverse><![CDATA[" . $data->{warning_inverse} . "]]></warning_inverse>\n";
	}
	if (! (empty($data->{min_warning_forced})) ) {
		$xml_module .= "\t<min_warning_forced><![CDATA[" . $data->{min_warning_forced} . "]]></min_warning_forced>\n";
	}
	if (! (empty($data->{max_warning_forced})) ) {
		$xml_module .= "\t<max_warning_forced><![CDATA[" . $data->{max_warning_forced} . "]]></max_warning_forced>\n";
	}
	if (! (empty ($data->{min_critical_forced})) ) {
		$xml_module .= "\t<min_critical_forced><![CDATA[" . $data->{min_critical_forced} . "]]></min_critical_forced>\n";
	}
	if (! (empty ($data->{max_critical_forced})) ){
		$xml_module .= "\t<max_critical_forced><![CDATA[" . $data->{max_critical_forced} . "]]></max_critical_forced>\n";
	}
	if (! (empty ($data->{str_warning_forced}))) {
		$xml_module .= "\t<str_warning_forced><![CDATA[" . $data->{str_warning_forced} . "]]></str_warning_forced>\n";
	}
	if (! (empty ($data->{str_critical_forced}))) {
		$xml_module .= "\t<str_critical_forced><![CDATA[" . $data->{str_critical_forced} . "]]></str_critical_forced>\n";
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
	if (! (empty ($data->{ff_type}))) {
		$xml_module .= "\t<ff_type><![CDATA[" . $data->{ff_type} . "]]></ff_type>\n";
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

	if ($xml =~ /\n/ || ! -f $xml) {
		# Not a file, it's content.
		if (! (empty ($name))) {
			$file_name = $name . "." . sprintf("%d",getCurrentUTimeMilis(). (rand()*10000)) . ".data";
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
			
			# Tentacle server does not allow files with symbols in theirs name.
			$file_name =~ s/[^a-zA-Z0-9_-]//g;
			$file_name .=  "." . sprintf("%d",time()) . ".data";
		}

		logger($conf, "transfer_xml", "Failed to generate file name.") if empty($file_name);

		$conf->{temp} = $conf->{tmp}             if (empty($conf->{temp}) && defined($conf->{tmp}));
		$conf->{temp} = $conf->{temporal}        if (empty($conf->{temp}) && defined($conf->{temporal}));
		$conf->{temp} = $conf->{__system}->{tmp} if (empty($conf->{temp}) && defined($conf->{__system})) && (ref($conf->{__system}) eq "HASH");
		$conf->{temp} = $ENV{'TMP'}              if empty($conf->{temp}) && $^O =~ /win/i;
		$conf->{temp} = '/tmp'                   if empty($conf->{temp}) && $^O =~ /lin/i;

		$file_path = $conf->{temp} . "/" . $file_name;
		
		#Creating XML file in temp directory
		
		if ( -e $file_path ) {
			sleep (1);
			$file_name = $name . "." . sprintf("%d",time()) . ".data";
			$file_path = $conf->{temp} . "/" . $file_name;
		}

		my $r = open (my $FD, ">>", $file_path);

		if (empty($r)) {
			print_stderror($conf, "Cannot write to [" . $file_path . "]", $conf->{'debug'});
			return undef;
		}
		
		my $bin_opts = ':raw:encoding(UTF8)';
		
		if ($^O eq "Windows") {
			$bin_opts .= ':crlf';
		}
		
		binmode($FD, $bin_opts);

		print $FD $xml;

		close ($FD);

	} else {
		$file_path = $xml;
	}

	# Reassign default values if not present
	$conf->{tentacle_client} = "tentacle_client" if empty($conf->{tentacle_client});
	$conf->{tentacle_port}   = "41121"     if empty($conf->{tentacle_port});
	$conf->{tentacle_opts}   = ""          if empty($conf->{tentacle_opts});
	$conf->{mode} = $conf->{transfer_mode} if empty($conf->{mode});

	if (empty ($conf->{mode}) ) {
		print_stderror($conf, "[ERROR] Nor \"mode\" nor \"transfer_mode\" defined in configuration.");
		return undef;
	}

	#Transfering XML file
	if ($conf->{mode} eq "tentacle") {
		my $msg = "";
		my $r = -1;
		#Send using tentacle
		if ($^O =~ /win/i) {
			$msg = `$conf->{tentacle_client} -v -a $conf->{tentacle_ip} -p $conf->{tentacle_port} $conf->{tentacle_opts} "$file_path"`;
			$r = $?;
		}
		else {
			$msg = `$conf->{tentacle_client} -v -a $conf->{tentacle_ip} -p $conf->{tentacle_port} $conf->{tentacle_opts} "$file_path" 2>&1`;
			$r = $?;
		}
			
			
		#If no errors were detected delete file	
		
		if ($r == 0) {
			unlink ($file_path);
		}
		else {
			print_stderror($conf, trim($msg) . " File [$file_path]");
			return undef;
		}	
	} 
	else {
		#Copy file to local folder
		my $dest_dir = $conf->{local_folder};

		my $rc = copy($file_path, $dest_dir);

		#If there was no error, delete file
		if ($rc == 0) {
			print_stderror($conf, "[ERROR] There was a problem copying local file to $dest_dir: $!");
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
		name  => "Plugin execution result " . $0,
		type  => "generic_proc",
		value => (defined($value)?$value:0),
		desc  => $msg,
	});
}

################################################################################
## Plugin devolution in case of error
################################################################################
sub print_error {
	my ($conf, $msg, $value, $always_show) = @_;

	$value = 0 unless defined($value);

	if (!(is_enabled($conf->{informational_modules}) || is_enabled($always_show))) {
		exit 1;
	}

	if (is_enabled($conf->{'as_server_plugin'})) {
		print STDERR $msg . "\n";
		print $value . "\n";
		exit 0;
	}

	print_module($conf, {
		name  => (empty($conf->{'global_plugin_module'})?"Plugin execution result " . $0:$conf->{'global_plugin_module'}),
		type  => "generic_proc",
		value => $value,
		desc  => $msg,
	});
	exit 0;
}

################################################################################
## Plugin message to SDTDOUT
################################################################################
sub print_stderror {
	my ($conf, $msg, $always_show) = @_;

	if(is_enabled($conf->{debug}) || (is_enabled($always_show))) {
		print STDERR strftime ("%Y-%m-%d %H:%M:%S", localtime()) . ": " . $msg . "\n";
	}
}


################################################################################
# Log data
################################################################################
my $log_aux_flag = 0;
sub logger {
	my ($conf, $tag, $message) = @_;
	my $file = $conf->{'log'};

	print_error($conf, "[ERROR] Log file is not defined.", 0, 1) unless defined($file);

	# Log rotation
	if (defined($file) && -e $file && (stat($file))[7] > 32000000) {
		rename ($file, $file.'.old');
	}
	my $LOGFILE;
	if ($log_aux_flag == 0) {
		# Log starts
		if (! open ($LOGFILE, "> $file")) {
			print_error ($conf, "[ERROR] Could not open logfile '$file'", 0, 1);
		}
		$log_aux_flag = 1;
	}
	else {
		if (! open ($LOGFILE, ">> $file")) {
			print_error ($conf, "[ERROR] Could not open logfile '$file'", 0, 1);
		}
	}

	if (empty($message)) {
		$message = $tag;
		$message = "" if empty($message);
	}
	else {
		$message = "[" . $tag . "] " . $message unless empty($tag);
	}


	if (!(empty($conf->{'agent_name'}))){
		$message = "[" . $conf->{'agent_name'} . "] " . $message;
	}

	print $LOGFILE strftime ("%Y-%m-%d %H:%M:%S", localtime()) . " - " . $message . "\n";
	close ($LOGFILE);
}

################################################################################
# is Enabled 
################################################################################
sub is_enabled {
	my $value = shift;
	
	if ((defined ($value)) && looks_like_number($value) && ($value > 0)){
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
	} elsif (!empty($response->{'_msg'})) {
		print_stderror($conf, 'Failed: ' .  $response->{'_msg'});
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
	} elsif (!empty($response->{'_msg'})) {
		print_stderror($conf, 'Failed: ' . $response->{'_msg'});
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

				# Disable library extra checks 
				BEGIN {
					$ENV{PERL_NET_HTTPS_SSL_SOCKET_CLASS} = "Net::SSL";
					$ENV{PERL_LWP_SSL_VERIFY_HOSTNAME} = 0;
				}
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
# Update internal UA timeout
################################################################################
sub ua_set_timeout {
	my ($config, $timeout) = @_;
	return unless looks_like_number($timeout) and $timeout > 0;
	my $sys = get_sys_environment($config);

	return unless defined($sys->{'ua'});
	$sys->{'ua'}->timeout($timeout);
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
		$system{tmp}     = ".\\";
		$system{cmdsep}  = "\&";
	}
	else {
		$system{devnull} = "/dev/null";
		$system{cat}     = "cat";
		$system{os}      = "Linux";
		$system{ps}      = "ps -eo pmem,pcpu,comm";
		$system{grep}    = "grep";
		$system{echo}    = "echo";
		$system{wcl}     = "wc -l";
		$system{tmp}     = "/tmp";
		$system{cmdsep}  = ";";

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
# Return a string with the concatenation of a hash array based on a field
################################################################################
sub join_by_field {
	my ($separator, $field, $array_hashref) = @_;

	$separator = ',' if empty($separator);
	my $str = '';
	foreach my $item (@{$array_hashref}) {
		$str .= (defined($item->{$field})?$item->{$field}:'') . $separator;
	}
	chop($str);

	return $str;
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
# Parses any configuration, from file (1st arg to program) or direct arguments 
#
# Custom evals are defined in an array reference of hash references:
# 
#   $custom_eval = [
#   	{
#   		'exp'    => 'regular expression to match',
#   		'target' => \&target_custom_method_to_parse_line
#   	},
#   	{
#   		'exp'    => 'another regular expression to search',
#   		'target' => \&target_custom_method_to_parse_line2
#   	},
#   ]
#
# Target is an user defined function wich will be invoked with following
# arguments:
# 
#  $config          : The configuration read to the point the regex matches
#  $exp             : The matching regex which fires this action
#  $line            : The complete line readed from the file
#  $file_pointer    : A pointer to the file which is being parsed.
#  $current_entity  : The current_entity (optional) when regex matches
#  
#  E.g.
#  
#  sub target_custom_method_to_parse_line {
#  	my ($config, $exp, $line, $file_pointer, $current_entity) = @_;
#  	
#  	if ($line =~ /$exp/) {
#  		$config->{'my_key'} = complex_operation_on_data($1,$2,$3);
#  	}
#  	
#  	return $config;
#  }
#
################################################################################
sub read_configuration {
	my ($config, $separator, $custom_eval) = @_;

	if ((!empty(@ARGV)) && (-f $ARGV[0])) {
	    $config = merge_hashes($config, parse_configuration(shift @ARGV, $separator, $custom_eval));
	}
	$config = merge_hashes($config, parse_arguments(\@ARGV));

	if(is_enabled($config->{'as_agent_plugin'})) {
		$config->{'as_server_plugin'} = 0 if (empty($config->{'as_server_plugin'}));
	}
	else {
		$config->{'as_server_plugin'} = 1 if (empty($config->{'as_server_plugin'}));
	}

	if(is_enabled($config->{'as_server_plugin'})) {
		$config->{'as_agent_plugin'} = 0 if (empty($config->{'as_agent_plugin'}));
	}
	else {
		$config->{'as_agent_plugin'} = 1 if (empty($config->{'as_agent_plugin'}));
	}

	return $config;
}

################################################################################
## Reads a file and returns entire content or undef if error.
################################################################################
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
		if ($key =~ /^\s*global_alerts/){
			push (@{$data{global_alerts}}, trim($args[$i+1]));
			next;
		}
		$data{$key} = trim($args[$i+1]);
	}

	return \%data;

}


################################################################################
# General configuration file parser
#
# Custom evals are defined in an array reference of hash references:
# 
#   $custom_eval = [
#   	{
#   		'exp'    => 'regular expression to match',
#   		'target' => \&target_custom_method_to_parse_line
#   	},
#   	{
#   		'exp'    => 'another regular expression to search',
#   		'target' => \&target_custom_method_to_parse_line2
#   	},
#   ]
#
# Target is an user defined function wich will be invoked with following
# arguments:
#
#  $config          : The configuration read to the point the regex matches
#  $exp             : The matching regex which fires this action
#  $line            : The complete line readed from the file
#  $file_pointer    : A pointer to the file which is being parsed.
#  $current_entity  : The current_entity (optional) when regex matches
#
################################################################################
sub parse_configuration;
sub parse_configuration {
	my ($conf_file, $separator, $custom_eval, $detect_entities, $entities_list) = @_;

	my @arguments = @_;
	shift(@arguments);

	$separator = "=" unless defined($separator);


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

	my $current_entity = '';
	my $new_entity = '';

	my $global_config;

	while (my $line = <$_CFILE>){
		if (($line =~ /^ *\r*\n*$/)
		 || ($line =~ /^#/ )){
		 	# skip blank lines and comments
			next;
		}
		my ($key,$value) = split /$separator/, $line, 2;

		# Clear entity detection is only compatible with specific entities declaration
		if (empty($value) && ($line =~ /^(\w+?)\r*\n*$/)
		 && is_enabled($detect_entities)
		 && in_array($entities_list, trim($key))) {
			# possible Entity detected - compatibility vmware-plugin
			$new_entity = $key;
		}
		if (($line =~ /\[(.*?)\]\r*\n*$/) && is_enabled($detect_entities)) {
			# Entity detected
			$new_entity = $1
		}

		if (!empty($new_entity)) {
			if (empty($current_entity)) {
				$global_config = merge_hashes($global_config, $_config);
			}
			else {
				$global_config->{$current_entity} = $_config;
			}

			$current_entity = trim($new_entity);
			undef($new_entity);

			# Initialize reference
			$global_config->{$current_entity} = {};
			$_config = $global_config->{$current_entity};

			next;
		}

		if ($line =~ /^\s*global_alerts/){
			push (@{$_config->{global_alerts}}, trim($value));
			next;
		}
		if (ref ($custom_eval) eq "ARRAY") {
			my $f = 0;
			foreach my $item (@{$custom_eval}) {
				if ($line =~ /$item->{'exp'}/) {
					$f = 1;
					my $aux;
					eval {
						$aux = $item->{'target'}->($_config, $item->{'exp'}, $line, $_CFILE, $current_entity);
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
		if ($key =~ /^include$/i) {
			my $file_included = trim($value);
			my $aux;
			eval {
				$aux = parse_configuration($file_included, @arguments);
			};
			if($@) {
				Carp::croak ("Failed to parse configuration");
			}

			if (empty($_config)) {
				$_config = $aux;
			}
			elsif (!empty($aux)  && (ref ($aux) eq "HASH")) {
				$_config = merge_hashes($_config, $aux);
			}
			next;
		}
		$_config->{trim($key)} = trim($value);
	}
	close ($_CFILE);

	if(is_enabled($detect_entities)) {
		if (empty($current_entity) && (!empty($global_config))) {
			$global_config = merge_hashes($global_config, $_config);
		}
		else {
			$global_config->{$current_entity} = $_config;
		}

		return $global_config unless empty($global_config);
	}

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

	if (empty($_PluginTools_system)) {
		$_PluginTools_system = init_system();
		$_PluginTools_system = get_sys_environment($_PluginTools_system);
	}

	my $cpu;
	my $mem;
	my $instances;
	my $runit = "%";
	my $cunit = "%";

	$mod_name = $process if empty($mod_name);
	
	if (empty($process)) {
		$process  = "" if empty($process);
		$mod_name = "" if empty($mod_name);
		$cpu = 0;
		$mem = 0;
		$instances = 0;
	}
	elsif ($^O =~ /win/i) {
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
		$mem = trim(`$_PluginTools_system->{ps} | $_PluginTools_system->{grep} -w "$process" | $_PluginTools_system->{grep} -v grep | awk 'BEGIN {sum=0} {sum+=\$1} END{print sum}'`);
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

	return {
		cpu => $cpu,
		mem => $mem,
		instances => $instances,
		runit => $runit,
		cunit => $cunit,
	};
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

	$api_url       = $conf->{'api_url'}       if empty($api_url);
	$api_pass      = $conf->{'api_pass'}      if empty($api_pass);
	$api_user      = $conf->{'api_user'}      if empty($api_user);
	$api_user_pass = $conf->{'api_user_pass'} if empty($api_user_pass);

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
# Pandora API call
# apidata->{other} = [field1,field2,...,fieldi,...,fieldn]
#########################################################################################
sub api_call {
	my ($conf, $apidata, $decode_json) = @_;
	my ($api_url, $api_pass, $api_user, $api_user_pass,
	 	 $op, $op2, $id, $id2, $other_mode, $other, $return_type);
	my $separator;

	if (ref $apidata eq "ARRAY") {
	 	($api_url, $api_pass, $api_user, $api_user_pass,
	 	 $op, $op2, $id, $id2, $return_type, $other_mode, $other) = @{$apidata};
	}
	if (ref $apidata eq "HASH") {
		$api_url       = $apidata->{'api_url'};
		$api_pass      = $apidata->{'api_pass'};
		$api_user      = $apidata->{'api_user'};
		$api_user_pass = $apidata->{'api_user_pass'};
		$op            = $apidata->{'op'};
		$op2           = $apidata->{'op2'};
		$id            = $apidata->{'id'};
		$id2           = $apidata->{'id2'};
		$return_type   = $apidata->{'return_type'};
		$other_mode    = "url_encode_separator_" . $apidata->{'url_encode_separator'} unless empty($apidata->{'url_encode_separator'});
		$other_mode    = "url_encode_separator_|" if empty($other_mode);
		($separator)   = $other_mode =~ /url_encode_separator_(.*)/;
	}

	$api_url       = $conf->{'api_url'}       if empty($api_url);
	$api_pass      = $conf->{'api_pass'}      if empty($api_pass);
	$api_user      = $conf->{'api_user'}      if empty($api_user);
	$api_user_pass = $conf->{'api_user_pass'} if empty($api_user_pass);
	$op            = $conf->{'op'}            if empty($op);
	$op2           = $conf->{'op2'}           if empty($op2);
	$id            = $conf->{'id'}            if empty($id);
	$id2           = $conf->{'id2'}           if empty($id2);
	$return_type   = $conf->{'return_type'}   if empty($return_type);
	$return_type   = 'json'                   if empty($return_type);
	if (ref ($apidata->{'other'}) eq "ARRAY") {
		$other_mode  = "url_encode_separator_|" if empty($other_mode);
		($separator) = $other_mode =~ /url_encode_separator_(.*)/;

		if (empty($separator)) {
			$separator  = "|";
			$other_mode = "url_encode_separator_|";
		}

		$other = join $separator, @{$apidata->{'other'}};
	}
	else {
		$other = $apidata->{'other'};
	}

	$other = '' if empty($other);
	$id    = '' if empty($id); 
	$id2   = '' if empty($id2);

	my $call;

	$call = $api_url . '?';
	$call .= 'op=' . $op . '&op2=' . $op2 . '&id=' . $id;
	$call .= '&other_mode=url_encode_separator_' . $separator;
	$call .= '&other=' . $other;
	$call .= '&apipass=' . $api_pass . '&user=' . $api_user . '&pass=' . $api_user_pass;
	$call .= '&return_type=' . $return_type;

	my $rs = call_url($conf, "$call");
	if (ref($rs) ne "HASH") {
		if (is_enabled($decode_json)) {
			eval {
				my $tmp = decode_json($rs);
				$rs = $tmp;
			};
			if ($@) {
				print_stderror($conf, "Error: " . $@);
			}
		}
		return {
			rs => (empty($rs)?1:0),
			error => (empty($rs)?"Empty response.":undef),
			id => (empty($rs)?undef:trim($rs)),
			response => (empty($rs)?undef:$rs),
		}
	}
	else {
		return {
			rs => 1,
			error => $rs->{'error'},
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

	$api_url       = $conf->{'api_url'}       if empty($api_url);
	$api_pass      = $conf->{'api_pass'}      if empty($api_pass);
	$api_user      = $conf->{'api_user'}      if empty($api_user);
	$api_user_pass = $conf->{'api_user_pass'} if empty($api_user_pass);



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

	$api_url       = $conf->{'api_url'}       if empty($api_url);
	$api_pass      = $conf->{'api_pass'}      if empty($api_pass);
	$api_user      = $conf->{'api_user'}      if empty($api_user);
	$api_user_pass = $conf->{'api_user_pass'} if empty($api_user_pass);

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
#   $snmp{version}
#   $snmp{community}
#   $snmp{host}
#   $snmp{oid}
#   $snmp{port}
#	  $snmp{securityName}
#	  $snmp{context
#	  $snmp{securityLevel}
#	  $snmp{authProtocol}
#	  $snmp{authKey}
#	  $snmp{privProtocol}
#	  $snmp{privKey}
################################################################################
sub snmp_walk {
	my $snmp = shift;
	my $cmd;
	my $timeout = 2;

	if (!empty($snmp->{timeout})) {
		$timeout = $snmp->{timeout};
	}

	if ($^O =~ /lin/i && "`which snmpwalk`" eq "") {
		return {
			'error' => 'snmpwalk not found'
		};
	}

	$snmp->{extra} = '' unless defined $snmp->{extra};

	if ( defined ($snmp->{version} )
	  && (($snmp->{version} eq "1")
	   || ($snmp->{version} eq "2")
	   || ($snmp->{version} eq "2c"))) {

		if (defined $snmp->{port}){
			$cmd = "snmpwalk -t $timeout $snmp->{extra} -On -v $snmp->{version} -c $snmp->{community} $snmp->{host}:$snmp->{port} $snmp->{oid}";
		}
		else {
			$cmd = "snmpwalk -t $timeout $snmp->{extra} -On -v $snmp->{version} -c $snmp->{community} $snmp->{host} $snmp->{oid}";
		}

	}
	elsif ( defined ($snmp->{version} )
	  && ($snmp->{version} eq "3") ) { # SNMP v3
		# Authentication required

		# $securityLevel = (noAuthNoPriv|authNoPriv|authPriv);

		# unauthenticated request
		# Ex. snmpwalk -t $timeout $snmp->{extra} -On -v 3 -n "" -u noAuthUser -l noAuthNoPriv test.net-snmp.org sysUpTime

		# authenticated request
		# Ex. snmpwalk -t $timeout $snmp->{extra} -On -v 3 -n "" -u MD5User -a MD5 -A "The Net-SNMP Demo Password" -l authNoPriv test.net-snmp.org sysUpTime

		# authenticated and encrypted request
		# Ex. snmpwalk -t $timeout $snmp->{extra} -On -v 3 -n "" -u MD5DESUser -a MD5 -A "The Net-SNMP Demo Password" -x DES -X "The Net-SNMP Demo Password" -l authPriv test.net-snmp.org system

		if ($snmp->{securityLevel} =~ /^noAuthNoPriv$/i){
			# Unauthenticated request

			if (defined $snmp->{port}){
				$cmd = "snmpwalk -t $timeout $snmp->{extra} -On -v $snmp->{version} -n \"$snmp->{context}\" -l $snmp->{securityLevel} $snmp->{host}:$snmp->{port} $snmp->{oid}";
			}
			else {
				$cmd = "snmpwalk -t $timeout $snmp->{extra} -On -v $snmp->{version} -n \"$snmp->{context}\" -l $snmp->{securityLevel} $snmp->{host} $snmp->{oid}";
			}
		}
		elsif ($snmp->{securityLevel} =~ /^authNoPriv$/i){ 
			# Authenticated request

			if (defined $snmp->{port}){
				$cmd = "snmpwalk -t $timeout $snmp->{extra} -On -v $snmp->{version} -n \"$snmp->{context}\" -u $snmp->{securityName} -a $snmp->{authProtocol} -A $snmp->{authKey} -l $snmp->{securityLevel} $snmp->{host}:$snmp->{port} $snmp->{oid}";
			}
			else {
				$cmd = "snmpwalk -t $timeout $snmp->{extra} -On -v $snmp->{version} -n \"$snmp->{context}\" -u $snmp->{securityName} -a $snmp->{authProtocol} -A $snmp->{authKey} -l $snmp->{securityLevel} $snmp->{host} $snmp->{oid}";
			}
		}
		elsif ($snmp->{securityLevel} =~ /^authPriv$/i){
			# Authenticated and encrypted request

			if (defined $snmp->{port}){
				$cmd = "snmpwalk -t $timeout $snmp->{extra} -On -v $snmp->{version} -n \"$snmp->{context}\" -u $snmp->{securityName} -l $snmp->{securityLevel} -a $snmp->{authProtocol} -A $snmp->{authKey} -x $snmp->{privProtocol} -X $snmp->{privKey} $snmp->{host}:$snmp->{port} $snmp->{oid}";
			}
			else {
				$cmd = "snmpwalk -t $timeout $snmp->{extra} -On -v $snmp->{version} -n \"$snmp->{context}\" -u $snmp->{securityName} -l $snmp->{securityLevel} -a $snmp->{authProtocol} -A $snmp->{authKey} -x $snmp->{privProtocol} -X $snmp->{privKey} $snmp->{host} $snmp->{oid}";
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
#	$snmp{securityName}
#	$snmp{context
#	$snmp{securityLevel}
#	$snmp{authProtocol}
#	$snmp{authKey}
#	$snmp{privProtocol}
#	$snmp{privKey}
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

	if ($^O =~ /lin/i && "`which snmpwalk`" eq "") {
		return {
			'error' => 'snmpwalk not found'
		};
	}

	if (!defined $snmp->{version}) {
		return {
			'error' => "Only SNMP 1 2 2c and 3 are supported."
		};
	} elsif (!defined $snmp->{host}) {
		return {
			'error' => "Destination host must be defined."
		};
	} elsif (!defined $snmp->{oid}) {
		return {
			'error' => "OID must be defined"
		};
	} else {
		$snmp->{extra} = '' unless defined $snmp->{extra};
		$snmp->{context} = '' unless defined $snmp->{context};
		$snmp->{community} = 'public' unless defined $snmp->{community};

		if (($snmp->{version} eq "1")
			|| ($snmp->{version} eq "2")
			|| ($snmp->{version} eq "2c")) {

			if (defined $snmp->{port}){
				$cmd = "snmpget -r $retries -t $timeout $snmp->{extra} -On -v $snmp->{version} -c $snmp->{community} $snmp->{host}:$snmp->{port} $snmp->{oid}";
			}
			else {
				$cmd = "snmpget -r $retries -t $timeout $snmp->{extra} -On -v $snmp->{version} -c $snmp->{community} $snmp->{host} $snmp->{oid}";
			}

		}
		elsif ( defined ($snmp->{version} )
			&& ($snmp->{version} eq "3") ) {

			$snmp->{securityLevel} = '' unless defined $snmp->{securityLevel};

			# SNMP v3
			# Authentication required

			# $securityLevel = (noAuthNoPriv|authNoPriv|authPriv);

			# unauthenticated request
			# Ex. snmpget -r $retries -t $timeout $snmp->{extra} -On -v 3 -n "" -u noAuthUser -l noAuthNoPriv test.net-snmp.org sysUpTime

			# authenticated request
			# Ex. snmpget -r $retries -t $timeout $snmp->{extra} -On -v 3 -n "" -u MD5User -a MD5 -A "The Net-SNMP Demo Password" -l authNoPriv test.net-snmp.org sysUpTime

			# authenticated and encrypted request
			# Ex. snmpget -r $retries -t $timeout $snmp->{extra} -On -v 3 -n "" -u MD5DESUser -a MD5 -A "The Net-SNMP Demo Password" -x DES -X "The Net-SNMP Demo Password" -l authPriv test.net-snmp.org system

			if ($snmp->{securityLevel} =~ /^noAuthNoPriv$/i){
				# Unauthenticated request

				if (defined $snmp->{port}){
					$cmd = "snmpget -r $retries -t $timeout $snmp->{extra} -On -v $snmp->{version} -n \"$snmp->{context}\" -l $snmp->{securityLevel} $snmp->{host}:$snmp->{port} $snmp->{oid}";
				}
				else {
					$cmd = "snmpget -r $retries -t $timeout $snmp->{extra} -On -v $snmp->{version} -n \"$snmp->{context}\" -l $snmp->{securityLevel} $snmp->{host} $snmp->{oid}";
				}
			}
			elsif ($snmp->{securityLevel} =~ /^authNoPriv$/i){
				# Authenticated request

				if (defined $snmp->{port}){
					$cmd = "snmpget -r $retries -t $timeout $snmp->{extra} -On -v $snmp->{version} -n \"$snmp->{context}\" -u $snmp->{securityName} -a $snmp->{authProtocol} -A $snmp->{authKey} -l $snmp->{securityLevel} $snmp->{host}:$snmp->{port} $snmp->{oid}";
				}
				else {
					$cmd = "snmpget -r $retries -t $timeout $snmp->{extra} -On -v $snmp->{version} -n \"$snmp->{context}\" -u $snmp->{securityName} -a $snmp->{authProtocol} -A $snmp->{authKey} -l $snmp->{securityLevel} $snmp->{host} $snmp->{oid}";
				}
			}
			elsif ($snmp->{securityLevel} =~ /^authPriv$/i){
				# Authenticated and encrypted request

				if (defined $snmp->{port}){
					$cmd = "snmpget -r $retries -t $timeout $snmp->{extra} -On -v $snmp->{version} -n \"$snmp->{context}\" -u $snmp->{securityName} -l $snmp->{securityLevel} -a $snmp->{authProtocol} -A $snmp->{authKey} -x $snmp->{privProtocol} -X $snmp->{privKey} $snmp->{host}:$snmp->{port} $snmp->{oid}";
				}
				else {
					$cmd = "snmpget -r $retries -t $timeout $snmp->{extra} -On -v $snmp->{version} -n \"$snmp->{context}\" -u $snmp->{securityName} -l $snmp->{securityLevel} -a $snmp->{authProtocol} -A $snmp->{authKey} -x $snmp->{privProtocol} -X $snmp->{privKey} $snmp->{host} $snmp->{oid}";
				}
			}
			else {
				return {
					'error' => "Security Level not defined."
				};
			}
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
		$data{data} = $pure_data; 
		$data{data} =~ s/[^-\d]//g;
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

	my $processed_salt = substr(Digest::SHA::hmac_sha256_base64($salt,''), 0, 16);

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

	my $processed_salt = substr(Digest::SHA::hmac_sha256_base64($salt,''), 0, 16);

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

	return 0 if empty($str_time);

	if (empty($separator_dates)) {
		$separator_dates = "\/";
	}

	if (empty($separator_hours)) {
		$separator_hours = ":";
	}

	my $time;
	eval {
		use Time::Local;
		my ($mday,$mon,$year,$hour,$min,$sec) = split(/[\s$separator_dates$separator_hours]+/, $str_time);
		$time = strftime("%s", $sec,$min,$hour,$mday,$mon-1,$year);
	};
	if ($@) {
		return 0;
	}
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

################################################################################
# Transforms an absolute seconds value into a readable count
################################################################################
sub seconds2readable {
	my ($tseconds, $format) = @_;

	return '' unless looks_like_number($tseconds);

	if (empty($format)) {
		return int($tseconds/(24*60*60)) . " d, "
			 . ($tseconds/(60*60))%24 . "h, "
			 . ($tseconds/60)%60 . "m, "
			 . $tseconds%60 . "s";
	}


	my $str = $format;
	
	# %d -> days
	if($format =~ /\%d/) {
		my $days = ($tseconds/(24*60*60)) | 0;
		$tseconds -= $days*24*60*60;
		$str =~ s/%d/$days/g;
	}

	# %h -> hours
	if($format =~ /\%h/) {
		my $hours = ($tseconds/(60*60)) | 0;
		$tseconds -= $hours*60*60;
		$str =~ s/%h/$hours/g;
	}

	# %m -> minutes
	if($format =~ /\%m/) {
		my $min = ($tseconds/60) | 0;
		$tseconds -= $min*60;
		$str =~ s/%m/$min/g;
	}
	
	# %s -> seconds
	if($format =~ /\%s/) {
		$str =~ s/%s/$tseconds/g;
	}


	return $str;
}

################################################################################
# Extracts a database password from file or environment
################################################################################
sub extract_dbpass {
	my ($config) = @_;

	return $config->{'dbpass'} unless empty($config->{'dbpass'});

	if (!empty ($config->{'dbpass_file'})) {
		if (-f $config->{'dbpass_file'}) {
			eval {
				open(my $pf, "<", $config->{'dbpass_file'}) or die ("Cannot open file " . $config->{'dbpass_file'});
				$config->{'dbpass'} = trim(<$pf>);
				close($pf);
			};
			if($@) {
				print_error($config, "Failed to read password file" . $@, 1);
				exit;
			}
		}
		else {
			print_error($config, "Failed to read password file", 1);
			exit;
		}
	}
	elsif (!empty ($config->{'dbpass_env_var_name'})) {
		if (!empty ($ENV{$config->{'dbpass_env_var_name'}})) {
			$config->{'dbpass'} = $ENV{$config->{'dbpass_env_var_name'}};
		}

		if (empty($config->{'dbpass'})) {
			print_error($config, "Failed to read password from environment", 1);
			exit;
		}
	}

	return $config->{'dbpass'};
}

################################################################################
# Extracts IP addresses (IPv4) from current system
################################################################################
sub get_addresses {
	my ($config) = @_;
	my $address = '';

	if (is_enabled($config->{'local'})) {
		$address = $config->{'dbhost'};
	}
	elsif($^O !~ /win/i) {
		my @address_list;

		if( -x "/bin/ip" || -x "/sbin/ip" || -x "/usr/sbin/ip" ) {
			@address_list = `ip addr show 2>$DevNull | sed -e '/127.0.0/d' -e '/[0-9]*\\.[0-9]*\\.[0-9]*/!d' -e 's/^[ \\t]*\\([^ \\t]*\\)[ \\t]*\\([^ \\t]*\\)[ \\t].*/\\2/' -e 's/\\/.*//'`;
		}
		else {
			@address_list = `ifconfig -a 2>$DevNull | sed -e '/127.0.0/d' -e '/[0-9]*\\.[0-9]*\\.[0-9]*/!d' -e 's/^[ \\t]*\\([^ \\t]*\\)[ \\t]*\\([^ \\t]*\\)[ \\t].*/\\2/' -e 's/.*://'`;
		}

		for (my $i = 0; $i <= $#address_list; $i++) {		
			chomp($address_list[$i]);
			if ($i > 0) {
				$address .= ',';
			}

			$address .= $address_list[$i];
		}			
	}


	return $address;
}

1;
