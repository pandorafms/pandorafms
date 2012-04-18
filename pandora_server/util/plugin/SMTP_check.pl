#!/usr/bin/perl

# Original plugin by slerena@gmail.com, (c) 2012. based on Sendmail.pm library

use strict;
use vars qw(
            $VERSION
            %mailcfg
            $address_rx
            $debug
            $log
            $error
            $retry_delay
            $connect_retries
            $auth_support
           );

use Socket;
use Time::Local; # for automatic time zone detection
use Sys::Hostname; # for use of hostname in HELO

sub get_param($) {
	my $param = shift;
	my $value = "";

	$param = "-".$param;
	
	for(my $i=0; $i<$#ARGV; $i++) {
		
		if ($ARGV[$i] eq $param) {
			$value = $ARGV[$i+1];
			last;
		}

	}
	return $value;
}

%mailcfg = (
    # List of SMTP servers:
    'smtp'    => [ qw( localhost ) ],
    'from'    => '', # default sender e-mail, used when no From header in mail
    'mime'    => 1, # use MIME encoding by default
    'retries' => 1, # number of retries on smtp connect failure
    'delay'   => 1, # delay in seconds between retries
    'tz'      => '', # only to override automatic detection
    'port'    => 25, # change it if you always use a non-standard port
    'debug'   => 0 # prints stuff to STDERR
);

# *******************************************************************

#use Digest::HMAC_MD5 qw(hmac_md5 hmac_md5_hex);

$auth_support = 'DIGEST-MD5 CRAM-MD5 PLAIN LOGIN';

# use MIME::QuotedPrint if available and configured in %mailcfg
eval("use MIME::QuotedPrint");
$mailcfg{'mime'} &&= (!$@);

# regex for e-mail addresses where full=$1, user=$2, domain=$3
# see pod documentation about this regex

my $word_rx = '[\x21\x23-\x27\x2A-\x2B\x2D\x2F\w\x3D\x3F]+';
my $user_rx = $word_rx         # valid chars
             .'(?:\.' . $word_rx . ')*' # possibly more words preceded by a dot
             ;
my $dom_rx = '\w[-\w]*(?:\.\w[-\w]*)*'; # less valid chars in domain names
my $ip_rx = '\[\d{1,3}(?:\.\d{1,3}){3}\]';

$address_rx = '((' . $user_rx . ')\@(' . $dom_rx . '|' . $ip_rx . '))';
; # v. 0.61

sub _require_md5 {
    eval { require Digest::MD5; Digest::MD5->import(qw(md5 md5_hex)); };
    $error .= $@ if $@;
    return ($@ ? undef : 1);
}

sub _require_base64 {
    eval {
        require MIME::Base64; MIME::Base64->import(qw(encode_base64 decode_base64));
    };
    $error .= $@ if $@;
    return ($@ ? undef : 1);
}

sub _hmac_md5 {
    my ($pass, $ckey) = @_;
    my $size = 64;
    $pass = md5($pass) if length($pass) > $size;
    my $ipad = $pass ^ (chr(0x36) x $size);
    my $opad = $pass ^ (chr(0x5c) x $size);
    return md5_hex($opad, md5($ipad, $ckey));
}

sub _digest_md5 {
    my ($user, $pass, $challenge, $realm) = @_;

    my %ckey = map { /^([^=]+)="?(.+?)"?$/ } split(/,/, $challenge);
    $realm ||= $ckey{realm}; #($user =~ s/\@(.+)$//o) ? $1 : $server;
    my $nonce  = $ckey{nonce};
    my $cnonce = &make_cnonce;
    my $uri = join('/', 'smtp', hostname()||'localhost', $ckey{realm});
    my $qop = 'auth';
    my $nc  = '00000001';
    my($hv, $a1, $a2);
    $hv = md5("$user:$realm:$pass");
    $a1 = md5_hex("$hv:$nonce:$cnonce");
    $a2 = md5_hex("AUTHENTICATE:$uri");
    $hv = md5_hex("$a1:$nonce:$nc:$cnonce:$qop:$a2");
    return qq(username="$user",realm="$ckey{realm}",nonce="$nonce",nc=$nc,cnonce="$cnonce",digest-uri="$uri",response=$hv,qop=$qop);
}

sub make_cnonce {
    my $s = '' ;
    for(1..16) { $s .= chr(rand 256) }
    $s = encode_base64($s, "");
    $s =~ s/\W/X/go;
    return substr($s, 0, 16);
}

sub time_to_date {
    # convert a time() value to a date-time string according to RFC 822

    my $time = $_[0] || time(); # default to now if no argument

    my @months = qw(Jan Feb Mar Apr May Jun Jul Aug Sep Oct Nov Dec);
    my @wdays  = qw(Sun Mon Tue Wed Thu Fri Sat);

    my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst)
        = localtime($time);

    my $TZ = $mailcfg{'tz'};
    if ( $TZ eq "" ) {
        # offset in hours
        my $offset  = sprintf "%.1f", (timegm(localtime) - time) / 3600;
        my $minutes = sprintf "%02d", abs( $offset - int($offset) ) * 60;
        $TZ  = sprintf("%+03d", int($offset)) . $minutes;
    }
    return join(" ",
                    ($wdays[$wday] . ','),
                     $mday,
                     $months[$mon],
                     $year+1900,
                     sprintf("%02d:%02d:%02d", $hour, $min, $sec),
                     $TZ
               );
} # end sub time_to_date

sub sendmail {

    $error = '';
    $log = "Mail::Sendmail v. $VERSION - "    . scalar(localtime()) . "\n";

    my $CRLF = "\015\012";
    local $/ = $CRLF;
    local $\ = ''; # to protect us from outside settings
    local $_;

    my (%mail, $k,
        $smtp, $server, $port, $connected, $localhost,
        $fromaddr, $recip, @recipients, $to, $header,
        %esmtp, @wanted_methods,
       );
    use vars qw($server_reply);
    # -------- a few internal subs ----------
    sub fail {
        # things to do before returning a sendmail failure
        $error .= join(" ", @_) . "\n";
        if ($server_reply) {
            $error .= "Server said: $server_reply\n";
            print STDERR "Server said: $server_reply\n" if $^W;
        }
        close S;
        return 0;
    }

    sub socket_write {
        my $i;
        for $i (0..$#_) {
            # accept references, so we don't copy potentially big data
            my $data = ref($_[$i]) ? $_[$i] : \$_[$i];
            if ($mailcfg{'debug'} > 5) {
                if (length($$data) < 500) {
                    print ">", $$data;
                }
                else {
                    print "> [...", length($$data), " bytes sent ...]\n";
                }
            }
            print(S $$data) || return 0;
        }
        1;
    }

    sub socket_read {
        $server_reply = "";
        do {
            $_ = <S>;
            $server_reply .= $_;
            #chomp $_;
            print "<$_" if $mailcfg{'debug'} > 5;
            if (/^[45]/ or !$_) {
                chomp $server_reply;
                return; # return false
            }
        } while (/^[\d]+-/);
        chomp $server_reply;
        return $server_reply;
    }
    # -------- end of internal subs ----------

    # all config keys to lowercase, to prevent typo errors
    foreach $k (keys %mailcfg) {
        if ($k =~ /[A-Z]/) {
            $mailcfg{lc($k)} = $mailcfg{$k};
        }
    }

    # redo mail hash, arranging keys case etc...
    while (@_) {
        $k = shift @_;
        if (!$k and $^W) {
            warn "Received false mail hash key: \'$k\'. Did you forget to put it in quotes?\n";
        }

        # arrange keys case
        $k = ucfirst lc($k);

        $k =~ s/\s*:\s*$//o; # kill colon (and possible spaces) at end, we add it later.
        # uppercase also after "-", so people don't complain that headers case is different
        # than in Outlook.
        $k =~ s/-(.)/"-" . uc($1)/ge;
        $mail{$k} = shift @_;
        if ($k !~ /^(Message|Body|Text)$/i) {
            # normalize possible line endings in headers
            $mail{$k} =~ s/\015\012?/\012/go;
            $mail{$k} =~ s/\012/$CRLF/go;
        }
    }

    $smtp = $mail{'Smtp'} || $mail{'Server'};
    unshift @{$mailcfg{'smtp'}}, $smtp if ($smtp and $mailcfg{'smtp'}->[0] ne $smtp);

    # delete non-header keys, so we don't send them later as mail headers
    # I like this syntax, but it doesn't seem to work with AS port 5.003_07:
    # delete @mail{'Smtp', 'Server'};
    # so instead:
    delete $mail{'Smtp'}; delete $mail{'Server'};

    $mailcfg{'port'} = $mail{'Port'} || $mailcfg{'port'} || 25;
    delete $mail{'Port'};

    my $auth = $mail{'Auth'};
    delete $mail{'Auth'};


    {    # don't warn for undefined values below
        local $^W = 0;
        $mail{'Message'} = join("", $mail{'Message'}, $mail{'Body'}, $mail{'Text'});
    }

    # delete @mail{'Body', 'Text'};
    delete $mail{'Body'}; delete $mail{'Text'};

    # Extract 'From:' e-mail address to use as envelope sender

    $fromaddr = $mail{'Sender'} || $mail{'From'} || $mailcfg{'from'};
    #delete $mail{'Sender'};
    unless ($fromaddr =~ /$address_rx/) {
        return fail("Bad or missing From address: \'$fromaddr\'");
    }
    $fromaddr = $1;

    # add Date header if needed
    $mail{Date} ||= time_to_date() ;
    $log .= "Date: $mail{Date}\n";

    # cleanup message, and encode if needed
    $mail{'Message'} =~ s/\r\n/\n/go;     # normalize line endings, step 1 of 2 (next step after MIME encoding)

    $mail{'Mime-Version'} ||= '1.0';
    $mail{'Content-Type'} ||= 'text/plain; charset="iso-8859-1"';

    unless ( $mail{'Content-Transfer-Encoding'}
          || $mail{'Content-Type'} =~ /multipart/io )
    {
        if ($mailcfg{'mime'}) {
            $mail{'Content-Transfer-Encoding'} = 'quoted-printable';
            $mail{'Message'} = encode_qp($mail{'Message'});
        }
        else {
            $mail{'Content-Transfer-Encoding'} = '8bit';
            if ($mail{'Message'} =~ /[\x80-\xFF]/o) {
                $error .= "MIME::QuotedPrint not present!\nSending 8bit characters, hoping it will come across OK.\n";
                warn "MIME::QuotedPrint not present!\n",
                     "Sending 8bit characters without encoding, hoping it will come across OK.\n"
                     if $^W;
            }
        }
    }

    $mail{'Message'} =~ s/^\./\.\./gom;     # handle . as first character
    $mail{'Message'} =~ s/\n/$CRLF/go; # normalize line endings, step 2.

    # Get recipients
    {    # don't warn for undefined values below
        local $^W = 0;
        $recip = join(", ", $mail{To}, $mail{Cc}, $mail{Bcc});
    }

    delete $mail{'Bcc'};

    @recipients = ();
    while ($recip =~ /$address_rx/go) {
        push @recipients, $1;
    }
    unless (@recipients) {
        return fail("No recipient!")
    }

    # get local hostname for polite HELO
    $localhost = hostname() || 'localhost';

    foreach $server ( @{$mailcfg{'smtp'}} ) {
        # open socket needs to be inside this foreach loop on Linux,
        # otherwise all servers fail if 1st one fails !??! why?
        unless ( socket S, AF_INET, SOCK_STREAM, scalar(getprotobyname 'tcp') ) {
            return fail("socket failed ($!)")
        }

        print "- trying $server\n" if $mailcfg{'debug'} > 1;

        $server =~ s/\s+//go; # remove spaces just in case of a typo
        # extract port if server name like "mail.domain.com:2525"
        $port = ($server =~ s/:(\d+)$//o) ? $1 : $mailcfg{'port'};
        $smtp = $server; # save $server for use outside foreach loop

        my $smtpaddr = inet_aton $server;
        unless ($smtpaddr) {
            $error .= "$server not found\n";
            next; # next server
        }

        my $retried = 0; # reset retries for each server
        while ( ( not $connected = connect S, pack_sockaddr_in($port, $smtpaddr) )
            and ( $retried < $mailcfg{'retries'} )
              ) {
            $retried++;
            $error .= "connect to $server failed ($!)\n";
            print "- connect to $server failed ($!)\n" if $mailcfg{'debug'} > 1;
            print "retrying in $mailcfg{'delay'} seconds...\n" if $mailcfg{'debug'} > 1;
            sleep $mailcfg{'delay'};
        }

        if ( $connected ) {
            print "- connected to $server\n" if $mailcfg{'debug'} > 3;
            last;
        }
        else {
            $error .= "connect to $server failed\n";
            print "- connect to $server failed, next server...\n" if $mailcfg{'debug'} > 1;
            next; # next server
        }
    }

    unless ( $connected ) {
        return fail("connect to $smtp failed ($!) no (more) retries!")
    };

    {
        local $^W = 0; # don't warn on undefined variables
        # Add info to log variable
        $log .= "Server: $smtp Port: $port\n"
              . "From: $fromaddr\n"
              . "Subject: $mail{Subject}\n"
              ;
    }

    my($oldfh) = select(S); $| = 1; select($oldfh);

    socket_read()
        || return fail("Connection error from $smtp on port $port ($_)");
    socket_write("EHLO $localhost$CRLF")
        || return fail("send EHLO error (lost connection?)");
    my $ehlo = socket_read();
    if ($ehlo) {
        # parse EHLO response
        map {
            s/^\d+[- ]//;
            my ($k, $v) = split /\s+/, $_, 2;
            $esmtp{$k} = $v || 1 if $k;
        } split(/\n/, $ehlo);
    }
    else {
        # try plain HELO instead
        socket_write("HELO $localhost$CRLF")
            || return fail("send HELO error (lost connection?)");
    }

    if ($auth) {
        warn "AUTH requested\n" if ($mailcfg{debug} > 4);
        # reduce wanted methods to those supported
        my @methods = grep {$esmtp{'AUTH'}=~/(^|\s)$_(\s|$)/i}
                        grep {$auth_support =~ /(^|\s)$_(\s|$)/i}
                            grep /\S/, split(/\s+/, $auth->{method});

        if (@methods) {
            # try to authenticate

            if (exists $auth->{pass}) {
                $auth->{password} = $auth->{pass};
            }

            my $method = uc $methods[0];
            _require_base64() || fail("Could not use MIME::Base64 module required for authentication");
            if ($method eq "LOGIN") {
                print STDERR "Trying AUTH LOGIN\n" if ($mailcfg{debug} > 9);
                socket_write("AUTH LOGIN$CRLF")
                    || return fail("send AUTH LOGIN failed (lost connection?)");
                socket_read()
                    || return fail("AUTH LOGIN failed: $server_reply");
                socket_write(encode_base64($auth->{user},$CRLF))
                    || return fail("send LOGIN username failed (lost connection?)");
                socket_read()
                    || return fail("LOGIN username failed: $server_reply");
                socket_write(encode_base64($auth->{password},$CRLF))
                    || return fail("send LOGIN password failed (lost connection?)");
                socket_read()
                    || return fail("LOGIN password failed: $server_reply");
            }
            elsif ($method eq "PLAIN") {
                warn "Trying AUTH PLAIN\n" if ($mailcfg{debug} > 9);
                socket_write(
                    "AUTH PLAIN "
                    . encode_base64(join("\0", $auth->{user}, $auth->{user}, $auth->{password}), $CRLF)
                ) || return fail("send AUTH PLAIN failed (lost connection?)");
                socket_read()
                    || return fail("AUTH PLAIN failed: $server_reply");
            }
            elsif ($method eq "CRAM-MD5") {
                _require_md5() || fail("Could not use Digest::MD5 module required for authentication");
                warn "Trying AUTH CRAM-MD5\n" if ($mailcfg{debug} > 9);
                socket_write("AUTH CRAM-MD5$CRLF")
                    || return fail("send CRAM-MD5 failed (lost connection?)");
                my $challenge = socket_read()
                    || return fail("AUTH CRAM-MD5 failed: $server_reply");
                $challenge =~ s/^\d+\s+//;
                my $response = _hmac_md5($auth->{password}, decode_base64($challenge));
                socket_write(encode_base64("$auth->{user} $response", $CRLF))
                    || return fail("AUTH CRAM-MD5 failed: $server_reply");
                socket_read()
                    || return fail("AUTH CRAM-MD5 failed: $server_reply");
            }
            elsif ($method eq "DIGEST-MD5") {
                _require_md5() || fail("Could not use Digest::MD5 module required for authentication");
                warn "Trying AUTH DIGEST-MD5\n" if ($mailcfg{debug} > 9);
                socket_write("AUTH DIGEST-MD5$CRLF")
                    || return fail("send CRAM-MD5 failed (lost connection?)");
                my $challenge = socket_read()
                    || return fail("AUTH DIGEST-MD5 failed: $server_reply");
                $challenge =~ s/^\d+\s+//; $challenge =~ s/[\r\n]+$//;
                warn "\nCHALLENGE=", decode_base64($challenge), "\n" if ($mailcfg{debug} > 10);
                my $response = _digest_md5($auth->{user}, $auth->{password}, decode_base64($challenge), $auth->{realm});
                warn "\nRESPONSE=$response\n" if ($mailcfg{debug} > 10);
                socket_write(encode_base64($response, ""), $CRLF)
                    || return fail("AUTH DIGEST-MD5 failed: $server_reply");
                my $status = socket_read()
                    || return fail("AUTH DIGEST-MD5 failed: $server_reply");
                if ($status =~ /^3/) {
                    socket_write($CRLF)
                        || return fail("AUTH DIGEST-MD5 failed: $server_reply");
                    socket_read()
                        || return fail("AUTH DIGEST-MD5 failed: $server_reply");
                }
            }
            else {
                return fail("$method not supported (and wrongly advertised as supported by this silly module)\n");
            }
            $log .= "AUTH $method succeeded as user $auth->{user}\n";
        }
        else {
            $esmtp{'AUTH'} =~ s/(^\s+|\s+$)//g; # cleanup for printig it below
            if ($auth->{required}) {
                return fail("Required AUTH method '$auth->{method}' not supported. "
                            ."(Server supports '$esmtp{'AUTH'}'. Module supports: '$auth_support')");
            }
            else {
                warn "No common authentication method! Requested: '$auth->{method}'. Server supports '$esmtp{'AUTH'}'. Module supports: '$auth_support'. Skipping authentication\n";
            }
        }
    }
    socket_write("MAIL FROM:<$fromaddr>$CRLF")
        || return fail("send MAIL FROM: error");
    socket_read()
        || return fail("MAIL FROM: error ($_)");

    my $to_ok = 0;
    foreach $to (@recipients) {
        socket_write("RCPT TO:<$to>$CRLF")
            || return fail("send RCPT TO: error");
        if (socket_read()) {
            $log .= "To: $to\n";
            $to_ok++;
        } else {
            $log .= "FAILED To: $to ($server_reply)";
            $error .= "Bad recipient <$to>: $server_reply\n";
        }
    }
    unless ($to_ok) {
        return fail("No valid recipient");
    }

    # start data part

    socket_write("DATA$CRLF")
        || return fail("send DATA error");
    socket_read()
        || return fail("DATA error ($_)");

    # print headers
    foreach $header (keys %mail) {
        next if $header eq "Message";
        $mail{$header} =~ s/\s+$//o; # kill possible trailing garbage
        socket_write("$header: $mail{$header}$CRLF")
            || return fail("send $header: error");
    };

    #- test diconnecting from network here, to see what happens
    #- print STDERR "DISCONNECT NOW!\n";
    #- sleep 4;
    #- print STDERR "trying to continue, expecting an error... \n";

    # send message body (passed as a reference, in case it's big)
    socket_write($CRLF, \$mail{'Message'}, "$CRLF.$CRLF")
           || return fail("send message error");
    socket_read()
        || return fail("message transmission error ($_)");
    $log .= "\nResult: $_";

    # finish
    socket_write("QUIT$CRLF")
           || return fail("send QUIT error");
    socket_read();
    close S;

    return 1;
} # end sub sendmail



# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Main code here
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~


my $host = get_param("h");
my $destination = get_param("d");
my $from = get_param("f");

if (($host eq "") || ($destination eq "") || ($from eq "")){
	print 'Pandora SMTP Remote plugin, (c) 2012 slerena@gmail.com';
	print "\n\nThis plugin is used to send a mail to a SMTP server and check if works\n\nUsage:\n\n";
	print " -h SMTP Server IP address\n";
	print " -d Destination email\n";
	print " -f Email of the sender\n";

	print "\nOptional parameters \n\n";
	print " -a Autentication system, could be LOGIN, PLAIN, CRAM-MD5 or DIGEST-MD\n";
	print " -o SMTP Port (25 by default)\n";
	print " -u user (only if MTA auth required)\n";
	print " -p password (only if MTA auth required)\n";
	print " -e debug - Show error (for testing in console only!)\n";
	print "\n";
	exit;
}

# Optional parameters

my $user = get_param("u");
my $pass = get_param("p");
my $port = get_param("o");

if ($port eq ""){
	$port = 25;
}

my $show_error = get_param("e");
my $auth = get_param("a");

my $subject = "Pandora FMS SMTP Test";
my $message = "This is a check for SMTP done with Pandora FMS";

my %mail = ( To   => $destination,
			  Message => $message,
			  Subject => $subject,
			  'X-Mailer' => "Pandora FMS",
			  Smtp    => $host,
			  Port    => $port,
			  From    => $from,
);

if ($auth ne ""){
	$mail{auth} = {user=>$user, password=>$pass, method=>$auth, required=>1 };
}

if (sendmail %mail) { 
	print "1\n";
} else {
	print "0\n";
	if ($show_error ne ""){
		if (defined($Mail::Sendmail::error)){
			print "ERROR Code: $Mail::Sendmail::error \n";
		} else {
			print "Undefined error ¿?\n";
		}
	}
}

exit;


