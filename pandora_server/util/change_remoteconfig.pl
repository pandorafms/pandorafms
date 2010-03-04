#!/usr/bin/perl
# (c) Artica Soluciones Tecnologicas 2010
# This script is licensed under GPL v2 licence.

use strict;
use POSIX qw(floor);


# TODO: Let more massive changes (fields) to be changed.

# Used to calculate the MD5 checksum of a string
use constant MOD232 => 2**32;
if ($#ARGV != 1) {
    print "This tool is used to do a massive change in all remote configuration\n";
    print "files for the remote agents, and change a list of files, given it's \n";
    print "agent name (case sensisitive)\n\n";
    print "Usage: change_remoteconfig.pl <file_with_server_names> <server_ip>\n\n";
    exit;
}

my $fichero_nombres = $ARGV[0];
my $servidor_destino = $ARGV[1];

# Ruta al directorio data_in
my $data_in = "/var/spool/pandora/data_in";
print "Massive changes are set. Ready to modify files at $data_in/conf and the MD5 hashes in $data_in/md5\n";

md5_init();
open (NOMBRES, $fichero_nombres) or die ("File $fichero_nombres not readable : $!");
my @servidores = <NOMBRES>;
close (NOMBRES);
print "Server IP address '$servidor_destino' is about to be changed in these agents:\n";
print "Total agents: ". scalar(@servidores)."\n";
print @servidores;

print "Waiting 10 seconds. Press ^C to cancel.n\n";
sleep (10);

foreach (@servidores) {
        my $servidor  = $_;
        chomp ($servidor);
        print "Procesing: $servidor " ;
        my $nombre_md5 =  md5($servidor);
        my $fichero_conf = "$data_in/conf/$nombre_md5.conf";
        # Se lee el fichero y se cambia la linea correspondiente
        open (CONF_FILE, $fichero_conf)or print ("Could not open file '$fichero_conf': $!.");
        open (NEW_CONF_FILE, '>', "$fichero_conf.new")or print ("Could not open file '$fichero_conf.new': $!.");
        while (my $linea = <CONF_FILE>) {
                if ($linea =~ m/^\s*server_ip.*/) {
                        $linea = "server_ip\t$servidor_destino\n";
                }
                print NEW_CONF_FILE $linea;
        }
        close (CONF_FILE);
        close (NEW_CONF_FILE);
        `mv $fichero_conf.new $fichero_conf`;

        # Calculate the new configuration file MD5 digest
        open (CONF_FILE, $fichero_conf)or print ("Could not open file '$fichero_conf': $!.");
        binmode(CONF_FILE);
        my $conf_md5 = md5 (join ('', <CONF_FILE>));
        close (CONF_FILE);
        print "Nuevo MD5 : $conf_md5\t";
        my $fichero_md5 = "$data_in/md5/$nombre_md5.md5";
        `echo -n "$conf_md5" > $fichero_md5`;
}

###############################################################################
# MD5 leftrotate function. See http://en.wikipedia.org/wiki/MD5#Pseudocode.
###############################################################################
sub leftrotate ($$) {
    my ($x, $c) = @_;

    return (0xFFFFFFFF & ($x << $c)) | ($x >> (32 - $c));
}

###############################################################################
# Initialize some variables needed by the MD5 algorithm.
# See http://en.wikipedia.org/wiki/MD5#Pseudocode.
###############################################################################
my (@R, @K);
sub md5_init () {

    # R specifies the per-round shift amounts
    @R = (7, 12, 17, 22,  7, 12, 17, 22,  7, 12, 17, 22,  7, 12, 17, 22,
          5,  9, 14, 20,  5,  9, 14, 20,  5,  9, 14, 20,  5,  9, 14, 20,
          4, 11, 16, 23,  4, 11, 16, 23,  4, 11, 16, 23,  4, 11, 16, 23,
          6, 10, 15, 21,  6, 10, 15, 21,  6, 10, 15, 21,  6, 10, 15, 21);

    # Use binary integer part of the sines of integers (radians) as constants
    for (my $i = 0; $i < 64; $i++) {
        $K[$i] = floor(abs(sin($i + 1)) * MOD232);
    }
}

###############################################################################
# Return the MD5 checksum of the given string.
# Pseudocode from http://en.wikipedia.org/wiki/MD5#Pseudocode.
###############################################################################
sub md5 ($) {
    my $str = shift;

    # Note: All variables are unsigned 32 bits and wrap modulo 2^32 when calculating

    # Initialize variables
    my $h0 = 0x67452301;
    my $h1 = 0xEFCDAB89;
    my $h2 = 0x98BADCFE;
    my $h3 = 0x10325476;

    # Pre-processing
    my $msg = unpack ("B*", pack ("A*", $str));
    my $bit_len = length ($msg);

    # Append "1" bit to message
    $msg .= '1';

    # Append "0" bits until message length in bits â¡ 448 (mod 512)
    $msg .= '0' while ((length ($msg) % 512) != 448);

    # Append bit /* bit, not byte */ length of unpadded message as 64-bit little-endian integer to message
    $msg .= unpack ("B64", pack ("VV", $bit_len));

    # Process the message in successive 512-bit chunks
    for (my $i = 0; $i < length ($msg); $i += 512) {

        my @w;
        my $chunk = substr ($msg, $i, 512);

        # Break chunk into sixteen 32-bit little-endian words w[i], 0 <= i <= 15
        for (my $j = 0; $j < length ($chunk); $j += 32) {
            push (@w, unpack ("V", pack ("B32", substr ($chunk, $j, 32))));
        }

        # Initialize hash value for this chunk
        my $a = $h0;
        my $b = $h1;
        my $c = $h2;
        my $d = $h3;
        my $f;
        my $g;

        # Main loop
        for (my $y = 0; $y < 64; $y++) {
            if ($y <= 15) {
                $f = $d ^ ($b & ($c ^ $d));
                $g = $y;
            }
            elsif ($y <= 31) {
                $f = $c ^ ($d & ($b ^ $c));
                $g = (5 * $y + 1) % 16;
            }
            elsif ($y <= 47) {
                $f = $b ^ $c ^ $d;
                $g = (3 * $y + 5) % 16;
            }
            else {
                $f = $c ^ ($b | (0xFFFFFFFF & (~ $d)));
                $g = (7 * $y) % 16;
            }

            my $temp = $d;
            $d = $c;
            $c = $b;
            $b = ($b + leftrotate (($a + $f + $K[$y] + $w[$g]) % MOD232, $R[$y])) % MOD232;
            $a = $temp;
        }

        # Add this chunk's hash to result so far
        $h0 = ($h0 + $a) % MOD232;
        $h1 = ($h1 + $b) % MOD232;
        $h2 = ($h2 + $c) % MOD232;
        $h3 = ($h3 + $d) % MOD232;
    }

    # Digest := h0 append h1 append h2 append h3 #(expressed as little-endian)
    return unpack ("H*", pack ("V", $h0)) . unpack ("H*", pack ("V", $h1)) . unpack ("H*", pack ("V", $h2)) . unpack ("H*", pack ("V", $h3));
}


