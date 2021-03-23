##################################################################################
# Goliath Tools LWP Module
##################################################################################
# Copyright (c) 2007-2021 Artica Soluciones Tecnologicas S.L
# This code is not free or OpenSource. Please don't redistribute.
##################################################################################

package PandoraFMS::Goliat::GoliatLWP;

use PandoraFMS::Goliat::GoliatTools;

use strict;
use warnings;
use Data::Dumper;

use IO::Socket::INET6;
use LWP::UserAgent;
use LWP::ConnCache;
use HTTP::Request::Common;
use HTTP::Response;
use HTML::TreeBuilder;
use HTML::Element;
use HTTP::Cookies;
use URI::URL;
use Time::Local;
use Time::HiRes qw ( gettimeofday );

# For IPv6 support in Net::HTTP.
BEGIN {
	$Net::HTTP::SOCKET_CLASS = 'IO::Socket::INET6';
	require Net::HTTP;
}

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
our @task_fails;
our @task_time;
our @task_end;
our @task_sessions;
our @task_ssec;
our @task_get_string;
our @task_get_content;
our @task_session_fails;
our $goliat_abort;

sub parse_html ($;$)
{
	my $p = $_[1];
	$p = _new_tree_maker() unless $p;
	$p->parse($_[0]);
}


sub parse_htmlfile ($;$)
{
	my($file, $p) = @_;
	local(*HTML);
	open(HTML, $file) or return undef;
	$p = _new_tree_maker() unless $p;
	$p->parse_file(\*HTML);
}

sub _new_tree_maker
{
	my $p = HTML::TreeBuilder->new(implicit_tags  => 1,
		 			   ignore_unknown => 1,
					   ignore_text	=> 0,
				   'warn'		 => 0,
				  );
	$p->strict_comment(1);
	$p;
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

	my $ua = new LWP::UserAgent;
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
	
	$ua->agent($config->{"agent"});
	$ua->protocols_allowed( ['http', 'https'] );
	$ua->default_headers->push_header('pragma' => "no-cache");
	$ua->timeout ($config->{"timeout"});
	$ua->max_size($config->{"maxsize"});
	$ua->use_alarm($config->{"alarm"});
	
	# Disable SSL certificate host verification
	if ($ua->can ('ssl_opts')) {
		$ua->ssl_opts("verify_hostname" => 0);
	}

	# Set proxy

	if ($config->{'proxy'} ne ""){
		$ua->proxy(['http','https'], $config->{'proxy'});
	}

	# Set HTTP Proxy auth
	if ($config->{'auth_user'} ne "") {
		$ua->credentials(  
			$config->{'auth_server'},
			$config->{'auth_realm'},
			$config->{'auth_user'} => $config->{'auth_pass'} );
	}

	if ( -e $cookie_file){
		unlink ($cookie_file);
	}
	my $cookies =  HTTP::Cookies->new ('file' => $cookie_file, 'autosave' => '0');
	
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
			my $params = "";
			$cx = 0;
			while (defined($work_list[$bx]->{'variable_name'}[$cx])){
				if ($cx > 0){
					$params = $params."&";
				}
				$params = $params . $work_list[$bx]->{'variable_name'}[$cx] . "=" . $work_list[$bx]->{'variable_value'}[$cx];
				$cx++;
			}

			if ( (defined($work_list[$bx]->{'http_auth_realm'})) && (defined($work_list[$bx]->{'http_auth_serverport'}))&& (defined($work_list[$bx]->{'http_auth_user'})) && (defined($work_list[$bx]->{'http_auth_pass'}))) {
				if ($work_list[$bx]->{'http_auth_realm'} ne "") {
					$ua->credentials(
						$work_list[$bx]->{'http_auth_serverport'},
						$work_list[$bx]->{'http_auth_realm'},
						$work_list[$bx]->{'http_auth_user'} => $work_list[$bx]->{'http_auth_pass'} 
					);
				}
			}

			# GET
			if ($work_list[$bx]->{'type'} eq "GET"){
				if ($cx > 0){
					$params = $work_list[$bx]->{'url'} . "?" . $params;
				} else {
					$params = $work_list[$bx]->{'url'};
				}
				$resp = g_get_page ( $ua, $params, $work_list[$bx]->{'headers'}, $work_list[$bx]->{'debug'});

			# POST
			} elsif ($work_list[$bx]->{'type'} eq "POST") {
				$resp = g_post_page ( $ua, $work_list[$bx]->{'url'}, $params, $work_list[$bx]->{'headers'}, $work_list[$bx]->{'debug'});

			# HEAD
			} else {
				if ($cx > 0){
					$params = $work_list[$bx]->{'url'} . "?" . $params;
				} else {
					$params = $work_list[$bx]->{'url'};
				}
				$resp = g_head_page ( $ua, $params, $work_list[$bx]->{'headers'}, $work_list[$bx]->{'debug'});
			}

			# Check for errors.
			if ($resp->code() == 500) {
				$total_invalid_request++;
				$bx = $config->{"work_items"};
				$check_string=0;
				last;
			}

			# Get string ?
			if (defined($work_list[$bx]->{'get_string'})) {
				my $as_string = $resp->as_string;
				my $temp = $work_list[$bx]->{'get_string'};
				if ($as_string =~ m/($temp)/) {
						 $task_get_string[$thread_id] = $1;
				}
			}

			# Get response ?
			if ($work_list[$bx]->{'get_content_advanced'} ne "") {
				my $content = $resp->decoded_content;
				my $temp = $work_list[$bx]->{'get_content_advanced'};
				if ($content =~ m/$temp/) {
					$task_get_content[$thread_id] = $1 if defined ($1);
				}
			} elsif ($work_list[$bx]->{'get_content'} ne "") {
				my $content = $resp->decoded_content;
				my $temp = $work_list[$bx]->{'get_content'};
				if ($content =~ m/($temp)/) {
					$task_get_content[$thread_id] = $1;
				}
			}
						 
			# Resource bashing
			if ((defined($work_list[$bx]->{'get_resources'})) && ($work_list[$bx]->{'get_resources'} == 1)){	
				$total_requests = g_get_all_links ($config, $ua, $resp, $total_requests, $work_list[$bx]->{'url'}, $work_list[$bx]->{'headers'}, $work_list[$bx]->{'debug'});
			}
			
			# CHECKSTRING check
			$cx = 0;
			while (defined($work_list[$bx]->{'checkstring'}[$cx]))  {
				my $match_string = $work_list[$bx]->{'checkstring'}[$cx];
				my $as_string = $resp->as_string;

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
				my $as_string = $resp->as_string;

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

			# Cookie carry on		
			if (defined ($work_list[$bx]->{'cookie'}) && $work_list[$bx]->{'cookie'} == 1){
				$cookies->extract_cookies($resp);
				$ua->cookie_jar($cookies);
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

	$cookies->clear;

	if ( -f $cookie_file){
		unlink ($cookie_file);
	}

	$task_end[$thread_id] = 1;
}


sub g_get_all_links  {
	my ($config, $ua, $response, $counter, $myurl, $headers, $debug) = @_;
	my $html;
	
	if ($response->is_success) {
		$html = $response->content;
	} else {
		return $counter;
	}
	# Beware this funcion, needs to be destroyed after use it !!!
	my $parsed_html = parse_html($html);
	#$ua->conn_cache(LWP::ConnCache->new());
	
	my @url_list;
	my $url = "";
	my $link;
	my $full_url;
	
	for (@{ $parsed_html->extract_links( ) }) {
		$link=$_->[0];
		if (($link =~ m/.png/i) || ($link =~ m/.gif/i) || ($link =~ m/.htm/i) ||
			 ($link =~ m/.html/i) || ($link =~ m/.pdf/i) || ($link =~ m/.jpg/i)
			 || ($link =~ m/.ico/i)){
			$url = new URI::URL $link;
			$full_url = $url->abs($myurl);
			@url_list = $full_url;
		}

	}
	$parsed_html->delete;
	my $ax = 0;
	while ($full_url = pop(@url_list)) {
		g_get_page ($ua, $full_url, $headers, $debug);
		$counter++;
		$ax++;
		if ($ax > $config->{"max_depth"}){
			return $counter;
		}
	}
	return $counter;
}

sub g_get_page  {
	my $ua = $_[0];
	my $url = $_[1];
	my $headers = $_[2];
	my $debug = $_[3];

	my $req = HTTP::Request->new(GET => $url);
  	$req->header('Accept' => 'text/html');
	while (my ($header, $value) = each %{$headers}) {
  		$req->header($header => $value);
	}
	my $response = $ua->request($req);
	return $response if ($debug eq '');

	# Debug
	if (open (DEBUG, '>>', $debug . '.req')) {
		print DEBUG "[Goliat debug " . time () . "]\n";
		print DEBUG $req->as_string ();
		print "\n";
		close (DEBUG);
	}
	if (open (DEBUG, '>>', $debug . '.res')) {
		print DEBUG "[Goliat debug " . time () . "]\n";
		print DEBUG $response->as_string ();
		print "\n";
		close (DEBUG);
	}
  	return $response;
}

sub g_head_page  {
	my $ua = $_[0];
	my $url = $_[1];
	my $headers = $_[2];
	my $debug = $_[3];

	my $req = HTTP::Request->new(HEAD => $url);
  	$req->header('Accept' => 'text/html');
	while (my ($header, $value) = each %{$headers}) {
  		$req->header($header => $value);
	}
	my $response = $ua->request($req);
  	return $response if ($debug eq '');

	# Debug
	if (open (DEBUG, '>>', $debug . '.req')) {
		print DEBUG "[Goliat debug " . time () . "]\n";
		print DEBUG $req->as_string ();
		print "\n";
		close (DEBUG);
	}
	if (open (DEBUG, '>>', $debug . '.res')) {
		print DEBUG "[Goliat debug " . time () . "]\n";
		print DEBUG $response->as_string ();
		print "\n";
		close (DEBUG);
	}
	return $response;
}

sub g_post_page  {
	my $ua = $_[0];
	my $url = $_[1];
	my $content = $_[2];
	my $headers = $_[3];
	my $debug = $_[4];

	my $req = HTTP::Request->new(POST => $url);
  	$req->content_type('application/x-www-form-urlencoded');
  	$req->content ($content);
	while (my ($header, $value) = each %{$headers}) {
  		$req->header($header => $value);
	}
	my $response = $ua->request($req);
	return $response if ($debug eq '');

	# Debug
	if (open (DEBUG, '>>', $debug . '.req')) {
		print DEBUG "[Goliat debug " . time () . "]\n";
		print DEBUG $req->as_string ();
		print "\n";
		close (DEBUG);
	}
	if (open (DEBUG, '>>', $debug . '.res')) {
		print DEBUG "[Goliat debug " . time () . "]\n";
		print DEBUG $response->as_string ();
		print "\n";
		close (DEBUG);
	}
	return $response;
}

# End of function declaration
# End of defined Code

1;
__END__
