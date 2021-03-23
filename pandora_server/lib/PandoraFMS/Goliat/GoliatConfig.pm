##########################################################################
# Goliat Config package
##########################################################################
# Copyright (c) 2007-2021 Artica Soluciones Tecnologicas S.L
# This code is not free or OpenSource. Please don't redistribute.
##########################################################################

package PandoraFMS::Goliat::GoliatConfig;

use strict;
use warnings;
use PandoraFMS::Tools;
use PandoraFMS::Goliat::GoliatTools;

require Exporter;
our @ISA = ("Exporter");
our %EXPORT_TAGS = ( 'all' => [ qw( ) ] );
our @EXPORT_OK = ( @{ $EXPORT_TAGS{'all'} } );
our @EXPORT = qw( 	g_help_screen
			g_init
			g_load_config  );

my $g_version = "1.0";
my $g_build = "110929";
our $VERSION = $g_version." ".$g_build;


sub g_load_config {
	my ( $config , $work_list )= @_ ;
	my $archivo_cfg = $config->{'config_file'};
	my $buffer_line;
 	my $task_block = 0;
 	my $commit_block = 0;
 	my $task_url = "";
 	my $task_cookie = 0;
 	my $task_resources = 1;
 	my $task_type = "";
 	my $task_headers = {};
 	my $task_debug = "";
	my $http_auth_user = "";
	my $http_auth_pass = "";
	my $http_auth_realm = "";
	my $http_auth_serverport = "";
	my $get_string = "";
	my $get_content = "";
	my $get_content_advanced = "";
 	my @task_variable_name;
 	my @task_variable_value;
	my @task_check_string;
	my @task_check_not_string;
	my $parametro;
	my $temp1;

	# Default options
	$config->{'con_delay'} =0;
	$config->{'ses_delay'} =0;
	if (!defined($config->{'agent'})){
		$config->{'agent'}="PandoraFMS/Goliat 4.0; Linux)";
	}	
	if (!defined($config->{'proxy'})){
				$config->{'proxy'}="";
		}

		if (!defined($config->{'retries'})){
				$config->{'retries'} = 1;
		}

		if ((!is_numeric($config->{'retries'})) || ($config->{'retries'} == 0)){
				$config->{'retries'} = 1;
		}

	$config->{'refresh'} = "5";
	$config->{"max_depth"} = 25;
	$config->{'log_file'}="/var/log/pandora/pandora_goliat.log";
	$config->{'log_output'} = 0;

	# Collect items from config file and put in an array 
	open (CFG, "< $archivo_cfg");
	while (<CFG>){
		$buffer_line = $_;
		if ($buffer_line =~ /^[a-zA-Z]/){ # begins with letters
			$parametro = $buffer_line;
		} else {
			$parametro = "";
		}
 		# Need to commit block ??
 		if (($commit_block == 1) && ($task_block == 1)) {
			my %work_item;
			$work_item{'url'} = $task_url;
			$work_item{'cookie'} = $task_cookie;
			$work_item{'type'} = $task_type;
			$work_item{'get_resources'} = $task_resources;
			$work_item{'get_string'} = $get_string;
			$work_item{'get_content'} = $get_content;
			$work_item{'get_content_advanced'} = $get_content_advanced;
			$work_item{'http_auth_user'} = $http_auth_user;
			$work_item{'http_auth_pass'} = $http_auth_pass;
			$work_item{'http_auth_realm'} = $http_auth_realm;
			$work_item{'http_auth_serverport'} = $http_auth_serverport;
			$work_item{'headers'} = $task_headers;
			$work_item{'debug'} = $task_debug;

			my $ax=0;
			while ($#task_check_string >= 0){
				$temp1 = pop (@task_check_string);
				$work_item{'checkstring'}[$ax] = $temp1;
				$ax++;
			}
			$ax=0;
			while ($#task_check_not_string >= 0){
				$temp1 = pop (@task_check_not_string);
				$work_item{'checknotstring'}[$ax] = $temp1;
				$ax++;
			}
			$ax=0;
			while ($#task_variable_name >= 0){
				$temp1 = pop (@task_variable_name);
				$work_item{'variable_name'}[$ax] = $temp1;
				$ax++;
			}
			$ax=0;
			while ($#task_variable_value >= 0){
				$temp1 = pop (@task_variable_value);
				$work_item{'variable_value'}[$ax] = $temp1;
				$ax++;
				
			}
			push @{$work_list}, \%work_item;
			$commit_block = 0;
			$task_block = 0;
 			$task_url = "";
 			$task_cookie = 0;
 			$task_resources = 0;
 			$task_type = "";
			$task_headers = {};
 			$task_debug = "";
 			$config->{"work_items"}++;
			$commit_block = 0;
			$task_block = 0;
			$http_auth_user = "";
			$http_auth_pass = "";
			$http_auth_realm = "";
			$get_string = "";
			$get_content = "";
			$get_content_advanced = "";
 		}
 		# ~~~~~~~~~~~~~~
		# Main setup items
		# ~~~~~~~~~~~~~~

		if ($parametro =~ m/^task_begin/i) {
			$task_block = 1;
		}
		elsif ($parametro =~ m/^task_end/i) {
			$commit_block = 1;
		}
		elsif ($parametro =~ m/^ses_delay\s(.*)/i) {
			$config->{'ses_delay'} = $1;
		}
		elsif ($parametro =~ m/^con_delay\s(.*)/i) {
			$config->{'con_delay'} = $1;
		}
		elsif ($parametro =~ m/^agent\s(.*)/i) {
			$config->{'agent'} = $1;
		}
		elsif ($parametro =~ m/^proxy\s(.*)/i) {
			$config->{'proxy'} = $1;
		}
		elsif ($parametro =~ m/^max_depth\s(.*)/i) {
			$config->{'max_depth'} = $1;
		}
		elsif ($parametro =~ m/^log_file\s(.*)/i) { 
			$config->{"log_file"} = $1;	
		}
		elsif ($parametro =~ m/^log_output\s(.*)/i) {
			$config->{"log_output"} = $1;
		}
		elsif ($parametro =~ m/^log_http\s(.*)/i) {
			$config->{"log_http"} = $1;
		}
		elsif ($parametro =~ m/^retries\s(.*)/i) {
			$config->{"retries"} = $1;
		}
		# ~~~~~~~~~~~~~~
		# Task items
		# ~~~~~~~~~~~~~~
		elsif ($parametro =~ m/^variable_name\s(.*)/i) {
			push (@task_variable_name, $1);
		}
		elsif ($parametro =~ m/^variable_value\s(.*)/i) {
			push (@task_variable_value, $1);
		}
		elsif ($parametro =~ m/^check_string\s(.*)/i) {
			push (@task_check_string, $1);
		}
		elsif ($parametro =~ m/^check_not_string\s(.*)/i) {
			push (@task_check_not_string, $1);
		}
		elsif ($parametro =~ m/^get\s(.*)/i) {
			$task_type = "GET";
			$task_url = $1;
		}
		elsif ($parametro =~ m/^post\s(.*)/i) {
			$task_type = "POST";
			$task_url = $1;
		}
		elsif ($parametro =~ m/^head\s(.*)/i) {
			$task_type = "HEAD";
			$task_url = $1;
		}
		# New in 4.0 version
		elsif ($parametro =~ m/^get_string\s(.*)/i) {
			$get_string = $1;
		}
		elsif ($parametro =~ m/^get_content\s(.*)/i) {
			$get_content = $1;
		}
		elsif ($parametro =~ m/^get_content_advanced\s(.*)/i) {
			$get_content_advanced = $1;
		}
		elsif ($parametro =~ m/^http_auth_user\s(.*)/i) {
			$http_auth_user = $1;
		}
		elsif ($parametro =~ m/^http_auth_pass\s(.*)/i) {
			$http_auth_pass = $1;
		}
		elsif ($parametro =~ m/^http_auth_realm\s(.*)/i) {
			$http_auth_realm = $1;
		}
		elsif ($parametro =~ m/^http_auth_serverport\s(.*)/i) {
			$http_auth_serverport = $1;
		}
		elsif ($parametro =~ m/^cookie\s(.*)/i) {
			if ($1 =~ m/1/i){
				$task_cookie = 1;
			} else {
				$task_cookie = 0;
			}
		}
		elsif ($parametro =~ m/^resource\s(.*)/i) {
			if ($1 =~ m/1/i){
				$task_resources = 1;
			} else {
				$task_resources = 0;
			}
		}
		# New in 5.0 version
		elsif ($parametro =~ m/^header\s+(\S+)\s(.*)/i) {
			$task_headers->{$1} = $2;
		}
		elsif ($parametro =~ m/^debug\s+(.*)/i) {
			$task_debug = $1;
		}

 	}
 	close (CFG);
}

# End of function declaration
# End of defined Code

1;
__END__


