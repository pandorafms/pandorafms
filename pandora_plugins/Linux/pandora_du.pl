#!/usr/bin/perl



use strict;
use warnings;

use Scalar::Util qw(looks_like_number);

### defines
my $DEFAULT_GROUP   = "";
my $MODULE_GROUP    = "";
my $MODULE_TAGS     = "";
my $GLOBAL_LOG_FILE = "";

my %config;


### Custom configuration
$config{MODULE_INTERVAL} = 1;
$config{MODULE_TAG_LIST} = "";


########################################################################################
# Erase blank spaces before and after the string 
########################################################################################
sub trim($){
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

########################################################################################
# Empty
########################################################################################
sub empty($){
	my $str = shift;

	if (! (defined ($str)) ){
		return 1;
	}

	if(looks_like_number($str)){
		return 0;
	}

	if ($str =~ /^\ *[\n\r]{0,2}\ *$/) {
		return 1;
	}
	return 0;
}

########################################################################################
# print_module
########################################################################################
sub print_module ($;$){
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

	$data->{tags}  = $data->{tags}?$data->{tags}:($config{MODULE_TAG_LIST}?$config{MODULE_TAG_LIST}:undef);
	$data->{interval}     = $data->{interval}?$data->{interval}:($config{MODULE_INTERVAL}?$config{MODULE_INTERVAL}:undef);
	$data->{module_group} = $data->{module_group}?$data->{module_group}:($config{MODULE_GROUP}?$config{MODULE_GROUP}:$MODULE_GROUP);

	$xml_module .= "<module>\n";
	$xml_module .= "\t<name><![CDATA[" . $data->{name} . "]]></name>\n";
	$xml_module .= "\t<type>" . $data->{type} . "</type>\n";
	$xml_module .= "\t<data><![CDATA[" . $data->{value} . "]]></data>\n";

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
	if (! (empty ($data->{alerts}))) {
		foreach my $alert (@{$data->{alerts}}){
			$xml_module .= "\t<alert_template><![CDATA[" . $alert . "]]></alert_template>\n";
		}
	}

	if (defined ($config{global_alerts})){
		foreach my $alert (@{$config{global_alerts}}){
			$xml_module .= "\t<alert_template><![CDATA[" . $alert . "]]></alert_template>\n";
		}
	}

	$xml_module .= "</module>\n";

	if (empty ($not_print_flag)) {
		print $xml_module;	
	}

	return $xml_module;
}

########################################################################################
# Get unit
########################################################################################
sub get_unit($){
	my $str = shift;
	$str =~ s/[\d\.\,]//g;
	return $str;
}


########################################################################################
########################################################################################
# MAIN
########################################################################################
########################################################################################


my @r = split /\n/, `du -s $ARGV[0] 2>/dev/null`;

foreach (@r) {
	my ($data, $name) = split /\s+/, $_, 2;
	my $value = $data;
	$value =~ s/[^\d\.\,]//g;
	my $unit  = get_unit($data);
	print_module({
		name  => "Size of: " . trim($name),
		type  => "generic_data",
		value => $value,
		unit  => $unit

	});
}

