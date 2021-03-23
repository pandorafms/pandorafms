##################################################################################
# Goliath Tools CURL Module
##################################################################################
# Copyright (c) 2013-2021 Artica Soluciones Tecnologicas S.L
# This code is not free or OpenSource. Please don't redistribute.
##################################################################################

package PandoraFMS::Goliat::GoliatCURL;

use PandoraFMS::Goliat::GoliatTools;

use strict;
use warnings;
use Data::Dumper;
use PandoraFMS::DB;

use IO::Socket::INET6;
use URI::Escape;
use Time::Local;
use Time::HiRes qw ( gettimeofday );

# Japanese encoding support
use Encode::Guess qw/euc-jp shiftjis iso-2022-jp/;

require Exporter;

our @ISA = ("Exporter");
our %EXPORT_TAGS = ( 'all' => [ qw() ] );
our @EXPORT_OK = ( @{ $EXPORT_TAGS{'all'} } );
our @EXPORT = qw(
	g_http_task
	@task_requests
	@task_reqsec
	@task_fails
	@task_time
	@task_end
	@task_sessions
	@task_ssec
	@task_get_string
	@task_get_content
 	@task_session_fails
);

our @task_requests;
our @task_reqsec;
our @task_fails	;
our @task_time;
our @task_end;
our @task_sessions;
our @task_ssec;
our @task_get_string;
our @task_get_content;
our @task_session_fails;
our $goliat_abort;

# Returns a string than can be safely used as a command line parameter for CURL
sub safe_param ($) {
	my $string = shift;
	
	$string =~ s/'/"/g;
	return "'" . $string . "'";
}

sub g_http_task {
	my ( $config, $thread_id, @work_list ) = @_;
	my ( $ax, $bx, $cx ); # used in FOR loop
	my ( $ttime1, $ttime2, $ttime_tot );
	
	my $resp; # HTTP Response
	my $total_requests = 0;
	my $total_valid_requests = 0;
	my $total_invalid_request = 0;
	my $cookie_file = "/tmp/gtc_".$thread_id."_".g_trash_ascii (3);
	my $check_string = 1;
	my $get_string = "";
	my $get_content = "";
	my $get_content_advanced = "";
	my $timeout = 10;

	#my $ua = new LWP::UserAgent;
	$task_requests [$thread_id] = 0 ;
	$task_sessions [$thread_id] = 0 ;
	$task_reqsec[$thread_id] = 0;
	$task_fails[$thread_id] = 0;
	$task_session_fails[$thread_id] = 0;
	$task_ssec[$thread_id] = 0;
	$task_end[$thread_id] = 0;
	$task_time[$thread_id] = 0;
	$task_get_string[$thread_id] = "";
	$task_get_content[$thread_id] = "";
	
	# Set command line options for CURL
	my $curl_opts;
	
	# Follow redirects
	$curl_opts .= " --location-trusted";
	
	# User agent
	if ($config->{"agent"} ne '') {
		$curl_opts .= " -A " . safe_param($config->{"agent"})
	}
	
	# Prevent pages from being cached
	$curl_opts .= " -H 'Pragma: no-cache'";
	
	# Timeout
	if (defined ($config->{"timeout"}) && $config->{"timeout"} > 0) {
		$timeout = $config->{"timeout"};
	}

	# Maximum file size
	if (defined($config->{"maxsize"}) && $config->{"maxsize"} > 0) {
		$curl_opts .= " --max-filesize " . $config->{"maxsize"};
	}
	
	# Disable SSL certificate host verification
	$curl_opts .= " -k";
	
	# Proxy
	if ($config->{'proxy'} ne ""){
		$curl_opts .= " -x " . safe_param($config->{'proxy'});
	}

	# Proxy HTTP authentication
	if ($config->{'auth_user'} ne "") {
		$curl_opts .= " --proxy-anyauth -U " . safe_param($config->{'auth_user'} . ':' . $config->{'auth_pass'});
	}
	
	# Delete existing cookies
	my $cookie_carry_on = 0;
	if ( -e $cookie_file){
		unlink ($cookie_file);
	}
	
	$ttime1 = Time::HiRes::gettimeofday();
	for ($ax = 0; $ax != $config->{'retries'}; $ax++){
		for ($bx = 0; $bx < $config->{"work_items"}; $bx++){
			if ($config->{'con_delay'} > 0){
				sleep ($config->{'con_delay'});
			}
			$total_requests++;
			# Start to count!
			$check_string = 1;
			# Prepare parameters
			my $task_curl_opts = $curl_opts;
			my $params = "";
			$cx = 0;
			while (defined($work_list[$bx]->{'variable_name'}[$cx])){
				if ($cx > 0){
					$params = $params."&";
				}
				$params = $params . $work_list[$bx]->{'variable_name'}[$cx] . "=" . uri_escape($work_list[$bx]->{'variable_value'}[$cx]);
				$cx++;
			}

			# Cookie carry on
			if (defined ($work_list[$bx]->{'cookie'}) && $work_list[$bx]->{'cookie'} == 1){
				$cookie_carry_on = 1;
			}

			if ($cookie_carry_on == 1) {
				$task_curl_opts .= " -c " . safe_param ($cookie_file);
				$task_curl_opts .= " -b " . safe_param ($cookie_file);
			}

			# HTTP authentication
			if ($work_list[$bx]->{'http_auth_user'} ne "" && $work_list[$bx]->{'http_auth_pass'} ne "") {
				
				if($config->{'http_check_type'} == 0){
					$task_curl_opts .= " --anyauth -u " . safe_param($work_list[$bx]->{'http_auth_user'} . ':' . $work_list[$bx]->{'http_auth_pass'});
				}
				
				if ($config->{'http_check_type'} == 1) {
					$task_curl_opts .= " --ntlm -u " . safe_param($work_list[$bx]->{'http_auth_user'} . ':' . $work_list[$bx]->{'http_auth_pass'});
				}
				
				if ($config->{'http_check_type'} == 2) {
					$task_curl_opts .= " --digest -u " . safe_param($work_list[$bx]->{'http_auth_user'} . ':' . $work_list[$bx]->{'http_auth_pass'});
				}
				
				if ($config->{'http_check_type'} == 3) {
					$task_curl_opts .= " --basic -u " . safe_param($work_list[$bx]->{'http_auth_user'} . ':' . $work_list[$bx]->{'http_auth_pass'});
				}
				
				
			}

			# GET
			if ($work_list[$bx]->{'type'} eq "GET"){
			  	$task_curl_opts .= " -H 'Accept: text/html'";
				if ($cx > 0){
					$params = $work_list[$bx]->{'url'} . "?" . $params;
				} else {
					$params = $work_list[$bx]->{'url'};
				}

				$resp = curl ($config->{"plugin_exec"}, $timeout, $task_curl_opts, $params, $work_list[$bx]->{'headers'}, $work_list[$bx]->{'debug'}, $config->{"moduleId"}, $config->{"dbh"});

			# POST
			} elsif ($work_list[$bx]->{'type'} eq "POST") {
				$task_curl_opts .= " -d " . safe_param($params);
				$task_curl_opts .= " -H 'Content-type: application/x-www-form-urlencoded'";
				$resp = curl ($config->{"plugin_exec"}, $timeout, $task_curl_opts, $work_list[$bx]->{'url'}, $work_list[$bx]->{'headers'}, $work_list[$bx]->{'debug'}, $config->{"moduleId"}, $config->{"dbh"});

			# HEAD
			} else {
				$task_curl_opts .= " -I";
				if ($cx > 0){
					$params = $work_list[$bx]->{'url'} . "?" . uri_escape($params);
				} else {
					$params = $work_list[$bx]->{'url'};
				}
				$resp = curl ($config->{"plugin_exec"}, $timeout, $task_curl_opts, $params, $work_list[$bx]->{'headers'}, $work_list[$bx]->{'debug'}, $config->{"moduleId"}, $config->{"dbh"});
			}

			# Get string ?
			if (defined($work_list[$bx]->{'get_string'})) {
				my $temp = $work_list[$bx]->{'get_string'};
				if ($resp =~ m/($temp)/) {
						 $task_get_string[$thread_id] = $1;
				}
			}

			# Get response ?
			if ($work_list[$bx]->{'get_content_advanced'} ne "") {
				my $temp = $work_list[$bx]->{'get_content_advanced'};
				if ($resp =~ m/$temp/) {
					$task_get_content[$thread_id] = $1 if defined ($1);
				}
			} elsif ($work_list[$bx]->{'get_content'} ne "") {
				my $temp = $work_list[$bx]->{'get_content'};
				if ($resp =~ m/($temp)/) {
					$task_get_content[$thread_id] = $1;
				}
			} else {
				$task_get_content[$thread_id] = $resp;
			}
						 
			# Resource bashing
			#if ((defined($work_list[$bx]->{'get_resources'})) && ($work_list[$bx]->{'get_resources'} == 1)){	
			#	$total_requests = g_get_all_links ($config, $ua, $resp, $total_requests, $work_list[$bx]->{'url'}, $work_list[$bx]->{'headers'}, $work_list[$bx]->{'debug'});
			#}
			
			# CHECKSTRING check
			$cx = 0;
			while (defined($work_list[$bx]->{'checkstring'}[$cx]))  {
				my $match_string = $work_list[$bx]->{'checkstring'}[$cx];
				my $as_string = $resp;
				my $guess = Encode::Guess::guess_encoding($as_string);
				if (ref $guess) {
					$as_string = $guess->decode($as_string);
				}
				unless (utf8::is_utf8($match_string)) {
					utf8::decode($match_string);
				}

				if ( $as_string =~ m/$match_string/i ){
					$total_valid_requests++;
				} else {
					$total_invalid_request++;
					$bx = $config->{"work_items"}; # Abort session remaining request
					$check_string=0;
				}
				$cx++;
			}

			# CHECKNOTSTRING check
			$cx = 0;
			while (defined($work_list[$bx]->{'checknotstring'}[$cx]))  {
				my $match_string = $work_list[$bx]->{'checknotstring'}[$cx];
				my $as_string = $resp;

				my $guess = Encode::Guess::guess_encoding($as_string);
				if (ref $guess) {
					$as_string = $guess->decode($as_string);
				}
				unless (utf8::is_utf8($match_string)) {
					utf8::decode($match_string);
				}

				if ( $as_string !~ m/$match_string/i ){
					$total_valid_requests++;
				} else {
					$total_invalid_request++;
					$bx = $config->{"work_items"}; # Abort session remaining request
					$check_string=0;
				}
				$cx++;
			}

			# End just now by pressing CTRL-C or Kill Signal !
			#if ($goliat_abort == 1){
				#$ax = $config->{'retries'};
				#$bx = $config->{'items'};
				#goto END_LOOP;
			#}
		} #main work_detail loop
		$ttime2 = Time::HiRes::gettimeofday();

		$ttime_tot = $ttime2 - $ttime1; # Total time for this task
		$task_time[$thread_id] = $ttime_tot; 
		$task_requests [$thread_id] = $total_requests;
		if ($ttime_tot > 0 ){
			$task_reqsec[$thread_id] = $total_requests / $ttime_tot;
		} else {
			$task_reqsec[$thread_id] = $total_requests;
		}
		$task_fails[$thread_id] = $total_invalid_request;
		if ($check_string == 0){
			$task_session_fails[$thread_id]++
		}
		$task_sessions [$thread_id]++;
		if ($task_sessions [$thread_id] > 0 ){
			$task_ssec[$thread_id]  = $ttime_tot / $task_sessions [$thread_id];
		} else {
			$task_ssec[$thread_id] = $task_sessions[$thread_id];
		}
		sleep $config->{'ses_delay'};
	}
END_LOOP:

	if ( -f $cookie_file){
		unlink ($cookie_file);
	}

	$task_end[$thread_id] = 1;
}

# Call CURL and return its output.
sub curl  {
	my ($exec, $timeout, $curl_opts, $url, $headers, $debug, $moduleId, $dbh) = @_;

	while (my ($header, $value) = each %{$headers}) {
  		$curl_opts .= " -H " . safe_param($header . ':' . $value);
	}
	
	my $cmd = "curl $curl_opts " . safe_param($url);
	my $response = `"$exec" $timeout $cmd 2>/dev/null`;

	# Curl command stored for live debugging feature.
	set_update_agentmodule ($dbh, $moduleId, { 'debug_content' =>  $cmd }) if defined($dbh);

	return $response if ($debug eq '');
	
	# Debug
	if (open (DEBUG, '>>', $debug . '.req')) {
		print DEBUG "[Goliat debug " . time () . "]\n";
		print DEBUG $cmd;
		print "\n";
		close (DEBUG);
	}
	if (open (DEBUG, '>>', $debug . '.res')) {
		print DEBUG "[Goliat debug " . time () . "]\n";
		print DEBUG $response;
		print "\n";
		close (DEBUG);
	}
  	return $response;
}

# End of function declaration
# End of defined Code

1;
__END__
