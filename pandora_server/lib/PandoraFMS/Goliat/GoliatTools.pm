###############################################################################
# Goliath Tools Module
###############################################################################
# Copyright (c) 2007-2021 Artica Soluciones Tecnologicas S.L
# This code is not free or OpenSource. Please don't redistribute.
###############################################################################

package PandoraFMS::Goliat::GoliatTools;

use 5.008004;
use strict;
use warnings;
use integer;

require Exporter;

our @ISA = ("Exporter");
our %EXPORT_TAGS = ( 'all' => [ qw() ] );
our @EXPORT_OK = ( @{ $EXPORT_TAGS{'all'} } );
our @EXPORT = qw(
			g_clean_string
			g_clean_string_unicode
			g_random_string
			g_trash_ascii
			g_trash_unicode
			g_unicode );

# Delaracion de funciones publicas 

##############################################################################
# clean_string (string) - Purge a string for any forbidden characters (esc, etc)
##############################################################################
sub g_clean_string {
    my $micadena;
    $micadena = $_[0];
    $micadena =~ s/[^\-\:\;\.\,\_\s\a\*\=\(\)a-zA-Z0-9]/ /g;
    $micadena =~ s/[\n\l\f]/ /g;
    return $micadena;
}

##############################################################################
# limpia_cadena_unicode (string) - Purge a string for any unicode character
##############################################################################
sub g_clean_string_unicode {
    my $micadena;
    $micadena = $_[0];
    $micadena =~ s/[%]/%%/g;
    return $micadena;
}

#############################################################################
# Hex converter - Convert dec value in hex representation (00 - FF)
#############################################################################
sub g_decToHex { #return a 16bit (o uno de 8bit) hex value
	my @hex = (0,1,2,3,4,5,6,7,8,9,"A","B","C","D","E","F");
	my @dec = @_;
	my $s3 = $hex[($dec[0]/4096)%16];
	my $s2 = $hex[($dec[0]/256)%16];
	my $s1 = $hex[($dec[0]/16)%16];
	my $s0 = $hex[$dec[0]%16];
	return "$s1$s0";
}

#############################################################################
# unicode - Generate unicode string (recursive)
#############################################################################

sub g_unicode {
    my $config_word = $_[0];
    my $config_depth = $_[1];
    my $config_char="%";
    if ($config_depth == 0) {
	return $config_word;
    }

    my $a;
    my $pos=0;
    my $output="";      
    my $len;

    for ($a=0;$a<$config_depth;$a++){
	$len = length($config_word);    
	while ($pos < $len ) {
	    my $item;
	    $item = substr($config_word,$pos,1);
	    $output = $output.$config_char.g_decToHex(ord($item));
	    $pos++;
	}
	$config_word = $output;
    }
    return $output
}

#############################################################################
# trash  - Generate "unicode" style trash string
#############################################################################

sub g_trash_unicode {
    my $config_depth = $_[0];
    my $config_char="%";
    my $a;
    my $output = "";

    for ($a=0;$a<$config_depth;$a++){
	    $output = $output.$config_char.g_decToHex(int(rand(25)+97));
    }
    return $output
}

#############################################################################
# trash_ascii  - Generate ASCII random strings
#############################################################################

sub g_trash_ascii {
    my $config_depth = $_[0];
    my $a;
    my $output = "";

    for ($a=0;$a<$config_depth;$a++){
	    $output = $output.chr(int(rand(25)+97));
    }
    return $output
}

#############################################################################
# random_string (min, max, type) - Generate ASCII alphanumeric string,
# 									from min and max
#############################################################################

sub g_random_string {
	my $config_min = $_[0];
	my $config_max = $_[1];
	my $config_type = $_[2]; # alphanumeric, alpha, numeric, lowalpha, highalpha
	my $a;
	my $output = "";
	my @valid_chars;
	my $rango;

	# First fill list of valid chars (A-Z, a-z, 0-9)
	if (($config_type eq "alphanumeric") || ($config_type eq "numeric")){
		for ($a=48;$a<58;$a++){ # numeric
			push @valid_chars, chr($a);
		}
	}

	if (($config_type eq "alphanumeric") || ($config_type eq "alpha") ||
		 ($config_type eq "highalpha") || ($config_type eq "lowalpha") ){
		if (($config_type eq "alphanumeric") || ($config_type eq "highalpha") || ($config_type eq "alpha")){
			for ($a=65;$a<91;$a++){ # alpha (CAPS)
				push @valid_chars, chr($a);
			}
		}
		if (($config_type eq "alphanumeric") || ($config_type eq "lowalpha") || ($config_type eq "alpha")){
			for ($a=97;$a<123;$a++){ # alpha (low)
				push @valid_chars, chr($a);
			}
		}
	}

	$rango = @valid_chars;

	# Fill min. value
	for ($a=0;$a<$config_min;$a++){
	$output = $output.$valid_chars[(int(rand($rango)))];
	}

	# Fill to max;
	if (($config_max - $config_min) != 0){
		for ($a=0;$a<rand($config_max - $config_min +1)-1;$a++){
			$output = $output.$valid_chars[(int(rand($rango)))];
		}
	}
	return $output
}

1;
__END__

=head1 NAME

Goliath-Tools Library tools for Goliath application.
This is an internal module, does not use for independent apps.


=head1 SYNOPSIS

  use GoliatTools;

=head1 DESCRIPTION


=head2 EXPORT

Pues no se que poner aqui :)

=head1 SEE ALSO

Mention other useful documentation such as the documentation of
related modules or operating system documentation (such as man pages
in UNIX), or any relevant external documentation such as RFCs or
standards.

If you have a mailing list set up for your module, mention it here.

If you have a web site set up for your module, mention it here.

=head1 AUTHOR

slerena, E<lt>slerena@Egmail.com<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2005 by Sancho Lerena

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.4 or,
at your option, any later version of Perl 5 you may have available.

Licenced under GPL

=cut
