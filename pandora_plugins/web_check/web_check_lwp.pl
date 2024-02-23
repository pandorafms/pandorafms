use strict;
use warnings;

use LWP::UserAgent ();
use Data::Dumper;

die "Usage: $0 <URL> <username> <password>\n" unless @ARGV == 3;

my ($URL, $username, $password) = @ARGV;

my $ua = LWP::UserAgent->new(timeout => 10);
$ua->protocols_allowed( ['http', 'https'] );
$ua->ssl_opts("verify_hostname" => 0);

$ua->credentials($URL, "", $username, $password);

my $response = $ua->get($URL);

if ($response->is_success) {
    print $response->decoded_content;
}
else {
    die print(Dumper($response));
}
