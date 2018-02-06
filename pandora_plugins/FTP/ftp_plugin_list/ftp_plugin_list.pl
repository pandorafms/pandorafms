#!/usr/bin/perl
#
# Pandora FMS FTP plugin for ftp listing
#
#
# 2016-04-01 v1
#   (c) Fco de Borja Sanchez <fborja.sanchez@artica.es>
#


use strict;
use warnings;

use Scalar::Util qw(looks_like_number);
use Time::HiRes qw(time);
use POSIX qw(strftime setsid floor);

### defines
my $DEFAULT_GROUP   = "FTP";
my $MODULE_GROUP    = "Networking";
my $MODULE_TAGS     = "";
my $GLOBAL_log = "/tmp/ftp_plugin_list.log";

my %config;





####################################
###### COMMON FUNCTIONS BEGIN ######
####################################

#####
## Get current time (milis)
####################################
sub getCurrentUTimeMilis(){
	#return trim (`date +"%s%3N"`); # returns 1449681679712
	return floor(time*1000);
}

####
# Erase blank spaces before and after the string 
####################################################
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

#####
# Empty
###################################
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

#####
# print_module
###################################
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
		$xml_module .= "\t<tags>" . $data->{tags} . "></tags>\n";
	}
	if (! (empty($data->{module_group})) ) {
		$xml_module .= "\t<module_group>" . $data->{module_group} . "</module_group>\n";
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
		$xml_module .= "\t<str_warning><![CDATA[" . $data->{cstr} . "]]></str_warning>\n";
	}
	if (! (empty ($data->{cstr}))) {
		$xml_module .= "\t<str_critical><![CDATA[" . $data->{cstr} . "]]></str_critical>\n";
	}

	$xml_module .= "</module>\n";

	if (empty ($not_print_flag)) {
		print $xml_module;	
	}

	return $xml_module;
}

#####
## Module warning
##      - tag: name
##      - value: severity (default 0)
##      - msg: description of the message
###########################################
sub print_warning($$;$){
	my ($tag, $msg, $value) = @_;

	if (!(isEnabled($config{informational_modules}))) {
		return 0;
	}

	if (!(isEnabled($config{informational_monitors}))) {
		$value = 0;
	}

	my %module;
	$module{name}  = "Plugin message" . ( $tag?" " . $tag:"");
	$module{type}  = "generic_data";
	$module{value} = defined($value)?$value:0;
	$module{desc}  = $msg;
	$module{wmin}  = 1;
	$module{cmin}  = 3;
	print_module(\%module);
}


#####
## Plugin devolution in case of error
#######################################
sub print_error($){
	my $msg = shift;
	my %module;
	$module{name}  = "Plugin execution error";
	$module{type}  = "generic_proc";
	$module{value} = 0;
	$module{desc}  = $msg;
	print_module(\%module);
	exit -1;
}

#####
## Log data
########################
my $log_aux_flag = 0;
sub logger ($$) {
	my ($tag, $message) = @_;
	my $file = $config{log};
	defined $file or $file = $GLOBAL_log;

	# Log rotation
	if (-e $file && (stat($file))[7] > 32000) {
		rename ($file, $file.'.old');
	}
	if ($log_aux_flag == 0) {
		# Log starts
		if (! open (LOGFILE, "> $file")) {
			print_error "[ERROR] Could not open logfile '$file'";
		}
		$log_aux_flag = 1;
	}
	else {
		if (! open (LOGFILE, ">> $file")) {
			print_error "[ERROR] Could not open logfile '$file'";
		}
	}

	$message = "[" . $tag . "] " . $message if ((defined $tag) && ($tag ne ""));

	if (!(empty($config{agent_name}))){
		$message = "[" . $config{agent_xml_name} . "] " . $message;
	}

	print LOGFILE strftime ("%Y-%m-%d %H:%M:%S", localtime()) . " - " . $message . "\n";
	close (LOGFILE);
}

#####
## is Enabled 
#################
sub isEnabled($){
	my $value = shift;
	
	if ((defined ($value)) && ($value > 0)){
		# return true
		return 1;
	}
	#return false
	return 0;

}

#####
## General configuration file parser
##
## log=/PATH/TO/LOG/FILE
##
#######################################
sub parse_configuration($){
	my $conf_file = shift;
	my %_config;

	open (_CFILE,"<", "$conf_file") or return undef;

	while (<_CFILE>){
		if (($_ =~ /^ *$/)
		 || ($_ =~ /^#/ )){
		 	# skip blank lines and comments
			next;
		}
		my @parsed = split /=/, $_, 2;
		$_config{trim($parsed[0])} = trim($parsed[1]);
	}
	close (_CFILE);

	return %_config;
}

##################################
###### COMMON FUNCTIONS END ######
##################################


####
# Creates a response file for FTP
##################################
sub ftp_create_response_file($){
	my ($file_name) = @_;

	my $file = $config{tmp} . "/" . $file_name;

	if (! open (TFTP_OUT_FILE, "> $file")) {
		print_error "[ERROR] Could not open logfile '$file'";
	}

	if (empty($config{ftp_pass})){
		logger("user", "password not set, use 'none'");
		$config{ftp_pass} = "none";
	}

	print TFTP_OUT_FILE "open " . $config{ftp_server} . " " . $config{ftp_port} . "\n";
	print TFTP_OUT_FILE "user \"" . $config{ftp_user} . "\" " . $config{ftp_pass} . "\n";
	print TFTP_OUT_FILE "ls " . $config{ftp_directory} . "\n";
	print TFTP_OUT_FILE "bye\n";
	close(TFTP_OUT_FILE);

	return $file;
}


##################################################################
#
#
# ---------------------------- MAIN ----------------------------
#
#
##################################################################
logger("start", "");
if ($#ARGV < 0){
	print STDERR "Usage: $0 ftp_plugin_list.conf\n";
	exit 1;
}

%config = parse_configuration($ARGV[0]);

my $file_name = getCurrentUTimeMilis() . "T" . (sprintf "%04.0f", rand()*1000);
my $file = ftp_create_response_file($file_name);


my $tstart = getCurrentUTimeMilis();
my $nitems = -1;
logger("run", "running");
$nitems = `ftp -n < $file 2>>$config{log} | wc -l`;

my $tend = getCurrentUTimeMilis();


print_module({
	name  => "FTP listing items",
	type  => "generic_data",
	value => $nitems,
	description => "items in $config{ftp_server} $config{ftp_directory}"
});

print_module({
	name  => "FTP listing: timing",
	type  => "generic_data",
	value => ($tend - $tstart)/1000,
	description => "Time used to list items in $config{ftp_server}",
	unit  => "s"
});


if (-e $file){
	unlink ($file);
}
logger("end", "");
