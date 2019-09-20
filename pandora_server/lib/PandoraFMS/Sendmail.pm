package PandoraFMS::Sendmail;

# Repackaged for work "by default" in PandoraFMS.
# Original library by:
# Mail::Sendmail by Milivoj Ivkovic <mi\x40alma.ch>
# see embedded POD documentation after __END__
# or http://alma.ch/perl/mail.html

=head1 NAME

Mail::Sendmail v. 0.79_16 - Simple platform independent mailer

=cut

$VERSION = '0.79_16';

# *************** Configuration you may want to change *******************
# You probably want to set your SMTP server here (unless you specify it in
# every script), and leave the rest as is. See pod documentation for details

%mailcfg = (
    # List of SMTP servers:
    'smtp'    => [ qw( localhost ) ],
    #'smtp'    => [ qw( mail.mydomain.com ) ], # example

    'from'    => '', # default sender e-mail, used when no From header in mail

    'mime'    => 1, # use MIME encoding by default

    'retries' => 1, # number of retries on smtp connect failure
    'delay'   => 1, # delay in seconds between retries

    'tz'      => '', # only to override automatic detection
    'port'    => 25, # change it if you always use a non-standard port
    'debug'   => 0, # prints stuff to STDERR
    'encryption'  => 'none', # no, ssl or starttls
	'timeout' => 5, # timeout for socket reads/writes in seconds
);

# *******************************************************************

require Exporter;
use strict;
use vars qw(
            $VERSION
            @ISA
            @EXPORT
            @EXPORT_OK
            %mailcfg
            $address_rx
            $debug
            $log
            $error
            $retry_delay
            $connect_retries
            $auth_support
           );

use IO::Socket::INET;
use IO::Select;
use Time::Local; # for automatic time zone detection
use Sys::Hostname; # for use of hostname in HELO

#use Digest::HMAC_MD5 qw(hmac_md5 hmac_md5_hex);

$auth_support = 'DIGEST-MD5 CRAM-MD5 PLAIN LOGIN';

# IO::Socket object.
my $S;

# IO::Select object.
my $Sel;

# use MIME::QuotedPrint if available and configured in %mailcfg
eval("use MIME::QuotedPrint");
$mailcfg{'mime'} &&= (!$@);

@ISA        = qw(Exporter);
@EXPORT     = qw(&sendmail);
@EXPORT_OK  = qw(
                 %mailcfg
                 time_to_date
                 $address_rx
                 $debug
                 $log
                 $error
                );

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
        $smtp, $server, $port, $localhost,
        $fromaddr, $recip, @recipients, $to, $header,
        %esmtp, @wanted_methods, $encryption
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
        close $S if defined($S);
        return 0;
    }

    sub socket_write {
        my $i;
        for $i (0..$#_) {
            # accept references, so we don't copy potentially big data
            my $data = ref($_[$i]) ? $_[$i] : \$_[$i];
            if ($mailcfg{'debug'} > 9) {
                if (length($$data) < 500) {
                    print STDERR ">", $$data;
                }
                else {
                    print STDERR "> [...", length($$data), " bytes sent ...]\n";
                }
            }
			my @sockets = $Sel->can_write($mailcfg{'timeout'});
			return 0 if (!@sockets);
           	syswrite($sockets[0], $$data) || return 0;
        }
        1;
    }

    sub socket_read {
		my $buffer;
        $server_reply = "";

		while (my @sockets = $Sel->can_read($mailcfg{'timeout'})) {
			return if (!@sockets);
			# 16kByte is the maximum size of an SSL frame and because sysread
			# returns data from only a single SSL frame you can guarantee that
			# there are no pending data.
 		    sysread($sockets[0], $buffer, 65535) || return;
        	$server_reply .= $buffer;
			last if ($buffer =~ m/\n$/);
		}

        print STDERR "<$server_reply" if $mailcfg{'debug'} > 9;
        if ($server_reply =~ /^[45]/) {
            chomp $server_reply;
            return; # return false
        }
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
    $mailcfg{'smtp'}->[0] = $smtp if ($smtp and $mailcfg{'smtp'}->[0] ne $smtp);

    $encryption = $mail{'Encryption'} || $mail{'Encryption'};

    # delete non-header keys, so we don't send them later as mail headers
    # I like this syntax, but it doesn't seem to work with AS port 5.003_07:
    # delete @mail{'Smtp', 'Server'};
    # so instead:
    delete $mail{'Smtp'}; delete $mail{'Server'}; delete $mail{'Encryption'};

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
        print STDERR "- trying $server\n" if $mailcfg{'debug'} > 9;

        $server =~ s/\s+//go; # remove spaces just in case of a typo
        # extract port if server name like "mail.domain.com:2525"
        $port = ($server =~ s/:(\d+)$//o) ? $1 : $mailcfg{'port'};
        $smtp = $server; # save $server for use outside foreach loop

        # load IO::Socket SSL if needed
        if ($encryption ne 'none') {
            eval "require IO::Socket::SSL" || return fail("IO::Socket::SSL is not available");
        }
        my $retried = 0; # reset retries for each server
        if ($encryption ne 'ssl') {
            $S = new IO::Socket::INET(PeerPort => $port, PeerAddr => $server, Proto => 'tcp');
        }
        else {
            $S = new IO::Socket::SSL(PeerPort => $port, PeerAddr => $server, Proto => 'tcp', SSL_verify_mode => IO::Socket::SSL::SSL_VERIFY_NONE(), Domain => AF_INET);
        }
        if ( $S ) {
            print STDERR "- connected to $server\n" if $mailcfg{'debug'} > 9;
            last;
        }
        else {
            $error .= "connect to $server failed\n";
            print STDERR "- connect to $server failed, next server...\n" if $mailcfg{'debug'} > 9;
            next; # next server
        }
    }

    unless ( $S ) {
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

	$Sel = new IO::Select() || return fail("IO::Select error");
	$Sel->add($S);
	
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

    # STARTTLS
    if ($encryption eq 'starttls') {
        defined($esmtp{'STARTTLS'})
            || return fail('STARTTLS not supported');
        socket_write("STARTTLS$CRLF") || return fail("send STARTTLS error");
        socket_read()
            || return fail('STARTTLS error');
        IO::Socket::SSL->start_SSL($S, SSL_hostname => $server, SSL_verify_mode => IO::Socket::SSL::SSL_VERIFY_NONE())
            || return fail("start_SSL failed");

        # The client SHOULD send an EHLO command as the
        # first command after a successful TLS negotiation.
        socket_write("EHLO $localhost$CRLF")
            || return fail("send EHLO error (lost connection?)");
        my $ehlo = socket_read();
        if ($ehlo) {
            # The server MUST discard any knowledge
            # obtained from the client.
            %esmtp = ();

            # parse EHLO response
            map {
                s/^\d+[- ]//;
                my ($k, $v) = split /\s+/, $_, 2;
                $esmtp{$k} = $v || 1 if $k;
            } split(/\n/, $ehlo);
        }
    }

    if (defined($auth) && $auth->{'user'} ne '') {
        warn "AUTH requested\n" if ($mailcfg{debug} > 9);
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
                warn "\nCHALLENGE=", decode_base64($challenge), "\n" if ($mailcfg{debug} > 9);
                my $response = _digest_md5($auth->{user}, $auth->{password}, decode_base64($challenge), $auth->{realm});
                warn "\nRESPONSE=$response\n" if ($mailcfg{debug} > 9);
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
    close $S;

    return 1;
} # end sub sendmail

1;
__END__

=head1 SYNOPSIS

  use Mail::Sendmail;

  %mail = ( To      => 'you@there.com',
            From    => 'me@here.com',
            Message => "This is a very short message"
           );

  sendmail(%mail) or die $Mail::Sendmail::error;

  print "OK. Log says:\n", $Mail::Sendmail::log;

=head1 DESCRIPTION

Simple platform independent e-mail from your perl script. Only requires
Perl 5 and a network connection.

Mail::Sendmail takes a hash with the message to send and sends it to your
mail server. It is intended to be very easy to setup and
use. See also L<"FEATURES"> below, and as usual, read this documentation.

There is also a FAQ (see L<"NOTES">).

=head1 INSTALLATION

=over 4

=item Best

C<perl -MCPAN -e "install Mail::Sendmail">

=item Traditional

    perl Makefile.PL
    make
    make test
    make install

=item Manual

Copy Sendmail.pm to Mail/ in your Perl lib directory.

    (eg. c:\Perl\site\lib\Mail\
     or  /usr/lib/perl5/site_perl/Mail/
     or whatever it is on your system.
     They are listed when you type C< perl -V >)

=item ActivePerl's PPM

Depending on your PPM version:

    ppm install --location=http://alma.ch/perl/ppm Mail-Sendmail

or

    ppm install http://alma.ch/perl/ppm/Mail-Sendmail.ppd

But this way you don't get a chance to have a look at other files (Changes,
Todo, test.pl, ...).

=back

At the top of Sendmail.pm, set your default SMTP server(s), unless you specify
it with each message, or want to use the default (localhost).

Install MIME::QuotedPrint. This is not required but strongly recommended.

=head1 FEATURES

Automatic time zone detection, Date: header, MIME quoted-printable encoding
(if MIME::QuotedPrint installed), all of which can be overridden.

Bcc: and Cc: support.

Allows real names in From:, To: and Cc: fields

Doesn't send an X-Mailer: header (unless you do), and allows you to send any
header(s) you want.

Configurable retries and use of alternate servers if your mail server is
down

Good plain text error reporting

Experimental support for SMTP AUTHentication

=head1 LIMITATIONS

Headers are not encoded, even if they have accented characters.

Since the whole message is in memory, it's not suitable for
sending very big attached files.

The SMTP server has to be set manually in Sendmail.pm or in your script,
unless you have a mail server on localhost.

Doesn't work on OpenVMS, I was told. Cannot test this myself.

=head1 CONFIGURATION

=over 4

=item Default SMTP server(s)

This is probably all you want to configure. It is usually done through
I<$mailcfg{smtp}>, which you can edit at the top of the Sendmail.pm file.
This is a reference to a list of SMTP servers. You can also set it from
your script:

C<unshift @{$Mail::Sendmail::mailcfg{'smtp'}} , 'my.mail.server';>

Alternatively, you can specify the server in the I<%mail> hash you send
from your script, which will do the same thing:

C<$mail{smtp} = 'my.mail.server';>

A future version will (hopefully) try to set useful defaults for you
during the Makefile.PL.

=item Other configuration settings

See I<%mailcfg> under L<"DETAILS"> below for other configuration options.

=back

=head1 DETAILS

=head2 sendmail()

sendmail is the only thing exported to your namespace by default

C<sendmail(%mail) || print "Error sending mail: $Mail::Sendmail::error\n";>

It takes a hash containing the full message, with keys for all headers
and the body, as well as for some specific options.

It returns 1 on success or 0 on error, and rewrites
C<$Mail::Sendmail::error> and C<$Mail::Sendmail::log>.

Keys are NOT case-sensitive.

The colon after headers is not necessary.

The Body part key can be called 'Body', 'Message' or 'Text'.

The SMTP server key can be called 'Smtp' or 'Server'. If the connection to
this one fails, the other ones in C<$mailcfg{smtp}> will still be tried.

The following headers are added unless you specify them yourself:

    Mime-Version: 1.0
    Content-Type: 'text/plain; charset="iso-8859-1"'

    Content-Transfer-Encoding: quoted-printable
    or (if MIME::QuotedPrint not installed)
    Content-Transfer-Encoding: 8bit

    Date: [string returned by time_to_date()]

If you wish to use an envelope sender address different than the
From: address, set C<$mail{Sender}> in your %mail hash.



The following are not exported by default, but you can still access them
with their full name, or request their export on the use line like in:
C<use Mail::Sendmail qw(sendmail $address_rx time_to_date);>

=head3 embedding options in your %mail hash

The following options can be set in your %mail hash. The corresponding keys
will be removed before sending the mail.

=over 4

=item $mail{smtp} or $mail{server}

The SMTP server to try first. It will be added

=item $mail{port}

This option will be removed. To use a non-standard port, set it in your server name:

$mail{server}='my.smtp.server:2525' will try to connect to port 2525 on server my.smtp.server

=item $mail{auth}

This must be a reference to a hash containg all your authentication options:

$mail{auth} = \%options;
or
$mail{auth} = {user=>"username", password=>"password", method=>"DIGEST-MD5", required=>0 };

=over

=item user

username

=item pass or password

password

=item method

optional method. compared (stripped down) to available methods. If empty, will try all available.

=item required

optional. defaults to false. If set to true, no delivery will be attempted if
authentication fails. If false or undefined, and authentication fails or is not available, sending is tried without.

(different auth for different servers?)

=back

=back

=head2 Mail::Sendmail::time_to_date()

convert time ( as from C<time()> ) to an RFC 822 compliant string for the
Date header. See also L<"%Mail::Sendmail::mailcfg">.

=head2 $Mail::Sendmail::error

When you don't run with the B<-w> flag, the module sends no errors to
STDERR, but puts anything it has to complain about in here. You should
probably always check if it says something.

=head2 $Mail::Sendmail::log

A summary that you could write to a log file after each send

=head2 $Mail::Sendmail::address_rx

A handy regex to recognize e-mail addresses.

A correct regex for valid e-mail addresses was written by one of the judges
in the obfuscated Perl contest... :-) It is quite big. This one is an
attempt to a reasonable compromise, and should accept all real-world
internet style addresses. The domain part is required and comments or
characters that would need to be quoted are not supported.

  Example:
    $rx = $Mail::Sendmail::address_rx;
    if (/$rx/) {
      $address=$1;
      $user=$2;
      $domain=$3;
    }

=head2 %Mail::Sendmail::mailcfg

This hash contains installation-wide configuration options. You normally edit it once (if
ever) in Sendmail.pm and forget about it, but you could also access it from
your scripts. For readability, I'll assume you have imported it
(with something like C<use Mail::Sendmail qw(sendmail %mailcfg)>).

The keys are not case-sensitive: they are all converted to lowercase before
use. Writing C<$mailcfg{Port} = 2525;> is OK: the default $mailcfg{port}
(25) will be deleted and replaced with your new value of 2525.

=over 4

=item $mailcfg{smtp}

C<$mailcfg{smtp} = [qw(localhost my.other.mail.server)];>

This is a reference to a list of smtp servers, so if your main server is
down, the module tries the next one. If one of your servers uses a special
port, add it to the server name with a colon in front, to override the
default port (like in my.special.server:2525).

Default: localhost.

=item $mailcfg{from}

C<$mailcfg{from} = 'Mailing script me@mydomain.com';>

From address used if you don't supply one in your script. Should not be of
type 'user@localhost' since that may not be valid on the recipient's
host.

Default: undefined.

=item $mailcfg{mime}

C<$mailcfg{mime} = 1;>

Set this to 0 if you don't want any automatic MIME encoding. You normally
don't need this, the module should 'Do the right thing' anyway.

Default: 1;

=item $mailcfg{retries}

C<$mailcfg{retries} = 1;>

How many times should the connection to the same SMTP server be retried in
case of a failure.

Default: 1;

=item $mailcfg{delay}

C<$mailcfg{delay} = 1;>

Number of seconds to wait between retries. This delay also happens before
trying the next server in the list, if the retries for the current server
have been exhausted. For CGI scripts, you want few retries and short delays
to return with a results page before the http connection times out. For
unattended scripts, you may want to use many retries and long delays to
have a good chance of your mail being sent even with temporary failures on
your network.

Default: 1 (second);

=item $mailcfg{tz}

C<$mailcfg{tz} = '+0800';>

Normally, your time zone is set automatically, from the difference between
C<time()> and C<gmtime()>. This allows you to override automatic detection
in cases where your system is confused (such as some Win32 systems in zones
which do not use daylight savings time: see Microsoft KB article Q148681)

Default: undefined (automatic detection at run-time).

=item $mailcfg{port}

C<$mailcfg{port} = 25;>

Port used when none is specified in the server name.

Default: 25.

=item $mailcfg{debug}

C<$mailcfg{debug} = 0;>

Prints stuff to STDERR. Current maximum is 6, which prints the whole SMTP
session, except data exceeding 500 bytes.

Default: 0;

=back

=head2 $Mail::Sendmail::VERSION

The package version number (you can not import this one)

=head2 Configuration variables from previous versions

The following global variables were used in version 0.74 for configuration.
As from version 0.78_1, they are not supported anymore.
Use the I<%mailcfg> hash if you need to access the configuration
from your scripts.

=over 4

=item $Mail::Sendmail::default_smtp_server

=item $Mail::Sendmail::default_smtp_port

=item $Mail::Sendmail::default_sender

=item $Mail::Sendmail::TZ

=item $Mail::Sendmail::connect_retries

=item $Mail::Sendmail::retry_delay

=item $Mail::Sendmail::use_MIME

=back

=head1 ANOTHER EXAMPLE

  use Mail::Sendmail;

  print "Testing Mail::Sendmail version $Mail::Sendmail::VERSION\n";
  print "Default server: $Mail::Sendmail::mailcfg{smtp}->[0]\n";
  print "Default sender: $Mail::Sendmail::mailcfg{from}\n";

  %mail = (
      #To      => 'No to field this time, only Bcc and Cc',
      #From    => 'not needed, use default',
      Bcc     => 'Someone <him@there.com>, Someone else her@there.com',
      # only addresses are extracted from Bcc, real names disregarded
      Cc      => 'Yet someone else <xz@whatever.com>',
      # Cc will appear in the header. (Bcc will not)
      Subject => 'Test message',
      'X-Mailer' => "Mail::Sendmail version $Mail::Sendmail::VERSION",
  );


  $mail{Smtp} = 'special_server.for-this-message-only.domain.com';
  $mail{'X-custom'} = 'My custom additionnal header';
  $mail{'mESSaGE : '} = "The message key looks terrible, but works.";
  # cheat on the date:
  $mail{Date} = Mail::Sendmail::time_to_date( time() - 86400 );

  if (sendmail %mail) { print "Mail sent OK.\n" }
  else { print "Error sending mail: $Mail::Sendmail::error \n" }

  print "\n\$Mail::Sendmail::log says:\n", $Mail::Sendmail::log;

Also see http://alma.ch/perl/Mail-Sendmail-FAQ.html for examples
of HTML mail and sending attachments.

=head1 CHANGES

Main changes since version 0.79:

Experimental SMTP AUTH support (LOGIN PLAIN CRAM-MD5 DIGEST-MD5)

Fix bug where one refused RCPT TO: would abort everything

send EHLO, and parse response

Better handling of multi-line responses, and better error-messages

Non-conforming line-endings also normalized in headers

Now keeps the Sender header if it was used. Previous versions
only used it for the MAIL FROM: command and deleted it.

See the F<Changes> file for the full history. If you don't have it
because you installed through PPM, you can also find the latest
one on F<http://alma.ch/perl/scripts/Sendmail/Changes>.

=head1 AUTHOR

Milivoj Ivkovic <mi\x40alma.ch> ("\x40" is "@" of course)

=head1 NOTES

MIME::QuotedPrint is used by default on every message if available. It
allows reliable sending of accented characters, and also takes care of
too long lines (which can happen in HTML mails). It is available in the
MIME-Base64 package at http://www.perl.com/CPAN/modules/by-module/MIME/ or
through PPM.

Look at http://alma.ch/perl/Mail-Sendmail-FAQ.html for additional
info (CGI, examples of sending attachments, HTML mail etc...)

You can use this module freely. (Someone complained this is too vague.
So, more precisely: do whatever you want with it, but be warned that
terrible things will happen to you if you use it badly, like for sending
spam, or ...?)

Thanks to the many users who sent me feedback, bug reports, suggestions, etc.
And please excuse me if I forgot to answer your mail. I am not always reliabe
in answering mail. I intend to set up a mailing list soon.

Last revision: 06.02.2003. Latest version should be available on
CPAN: F<http://www.cpan.org/modules/by-authors/id/M/MI/MIVKOVIC/>.

=cut
