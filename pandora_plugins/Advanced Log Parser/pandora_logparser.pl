#!/usr/bin/perl
###############################################################################
# Pandora FMS Agent Plugin for ADVANCED log parsing 
# Copyright (c) 2011-2015  Sancho Lerena  <slerena@artica.es>
# Copyright (c) 2011-2015  Artica Soluciones Tecnologicas S.L.
#   _______        __   __               _______ _______
#  |   _   |.----.|  |_|__|.----.---.-. |     __|_     _|
#  |       ||   _||   _|  ||  __|  _  | |__     | |   |
#  |___|___||__|  |____|__||____|___._| |_______| |___|
#
#
#        ARTICA SOLUCIONES TECNOLOGICAS
#        http://www.artica.es
#
# v1r3
# Change log (v1r3 - Ago 2015)
# * Solved lot of issues present in r2
# * Added support for wildcards
# * Identified problem with perl <5.13. It won't work on old Perl. Use binary!
###############################################################################

use strict;
use Data::Dumper;
use File::Basename;

# Used to calculate the MD5 checksum of a string
use constant MOD232 => 2**32;

my @config_file; # Stores the config file contents, line by line
my %plugin_setup; # Hash with this plugin setup

my $archivo_cfg = $ARGV[0]; # External main config file
my $log_items = 0; # Stores total of log definitions in the conf file
my $reg_exp = 0; # Total regexps
my $version;
$version = "v1r3"; # Actual plugin's version

###############################################################################
# SUB load_external_setup
# Receives a configuration filename to load in the configuration hash
###############################################################################

sub load_external_setup ($){

    my $archivo_cfg = $_[0];
    my $buffer_line;

        # Collect items from config file and put in an array
        if (! open (CFG, "< $archivo_cfg")) {
                print "[ERROR] Error opening configuration file $archivo_cfg: $!.\n";
                exit 1;
        }

    while (<CFG>){
            $buffer_line = $_;
            # Parse configuration file, this is specially difficult because can contain regexp, with many things
            if ($buffer_line !=~ /^\#/){  # begins with anything except # (for commenting)
                    if ($buffer_line =~ m/(.+)\s(.*)/){
                            push @config_file, $buffer_line;
                    }
            }
    }
    close (CFG);
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

	# Append "0" bits until message length in bits ≡ 448 (mod 512)
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

###############################################################################
# SUB log_msg
# Print a log message.
###############################################################################

sub log_msg ($) {
	my $log_msg = $_[0];

    if (! open (LOG, "> ".$plugin_setup{"logfile"})) {
        print "[ERROR] Error opening internal logfile ".$plugin_setup{"logfile"}."\n";
        exit 1;
    }

    print LOG $log_msg;
    close (LOG);
}


###############################################################################
# SUB error_msg
# Print a log message and exit (fatal log error message)
###############################################################################
sub error_msg ($) {
	my $log_msg = $_[0];

    log_msg ($log_msg);
}

###############################################################################
# parse_config
#
# This function load configuration tokens and store in a global hash
# called %plugin_setup accesible on all program.
###############################################################################

sub parse_config {

	my $tbuf;
	my $log_block = 0;
    my $reg_exp_rule = 0; 
    my $parametro;

    # Some default options
    $plugin_setup{"index_dir"} = "/tmp";
    $plugin_setup{"logfile"} = "/tmp/pandora_logparser.log";
    $plugin_setup{"log_rotate_mode"}="inode";

	foreach (@config_file){
        $parametro = $_;

        if ($parametro =~ m/^include\s+(.*)$/i) {
            load_external_setup ($1);
        }
    
        if ($parametro =~ m/^index_dir\s+(.*)$/i) {
            $plugin_setup{"index_dir"} = $1;
        }
    
        if ($parametro =~ m/^logfile\s+(.*)$/i) {
            $plugin_setup{"logfile"} = $1;
        }

        if ($parametro =~ m/^log\_rotate\_mode\s+(.*)$/i) {
            $plugin_setup{"log_rotate_mode"} = $1;
        }

        # Detect begin of log definition
        if ($parametro =~ m/^log\_begin/i) {
            $log_block = 1;
        }            

        # Log definition parsing mode
        if ($log_block == 1){
        
            if ($parametro =~ m/^log\_module\_name\s+(.*)$/i) {
                $plugin_setup{"log"}->[$log_items]->{"name"} = $1;
            }

			if ($parametro =~ m/^log\_type\s+(.*)$/i) {
                $plugin_setup{"log"}->[$log_items]->{"type"} = $1;
            }

            if ($parametro =~ m/^log\_create_module_for_each_log/i) {
                $plugin_setup{"log"}->[$log_items]->{"module_for_each_log"} = 1;
            } else {
                if (!defined($plugin_setup{"log"}->[$log_items]->{"module_for_each_log"})){
                    $plugin_setup{"log"}->[$log_items]->{"module_for_each_log"} = 0;    
                }
            }

            if ($parametro =~ m/^log\_force\_readall/i) {
                $plugin_setup{"log"}->[$log_items]->{"readall"} = 1;
            } else {
				if (!defined($plugin_setup{"log"}->[$log_items]->{"readall"})){
					$plugin_setup{"log"}->[$log_items]->{"readall"} = 0;
				}
			}

			if ($parametro =~ m/^log\_location\_file\s+(.*)$/i) {
                $plugin_setup{"log"}->[$log_items]->{"log_location_file"} = $1;
            }
            
            if ($parametro =~ m/^log\_location\_exec\s+(.*)$/i) {
                $plugin_setup{"log"}->[$log_items]->{"log_location_exec"} = $1;
            }

            if ($parametro =~ m/^log\_location\_multiple\s+(.*)$/i) {
                $plugin_setup{"log"}->[$log_items]->{"log_location_multiple"} = $1;
            }

            if ($parametro =~ m/^log\_description\s+(.*)$/i) {
                $plugin_setup{"log"}->[$log_items]->{"description"} = $1;
            }

            if ($parametro =~ m/^log\_regexp\_begin/i) {
                $log_block = 2;
                $reg_exp_rule = 0;
            }

            if ($parametro =~ m/^log\_end/i) {
                $log_items++;
            	$log_block = 0;
                $reg_exp=0;
            }
        }

        if ($log_block == 2){

            if ($parametro =~ m/^log_regexp_severity\s+(.*)$/i) {
                $plugin_setup{"log"}->[$log_items]->{"regexp"}->{$reg_exp}->{"severity"} = $1;
            }

            if ($parametro =~ m/^log_regexp_rule\s+(.*)$/i) {
                $plugin_setup{"log"}->[$log_items]->{"regexp"}->{$reg_exp}->{"rule"} = $1;
            }

            if ($parametro =~ m/^log_return_message\s+(.*)$/i) {
                $plugin_setup{"log"}->[$log_items]->{"regexp"}->{$reg_exp} -> {"message"} = $1;
            }

            if ($parametro =~ m/^log_action\s+(.*)$/i) {
                $plugin_setup{"log"}->[$log_items]->{"regexp"}->{$reg_exp} -> {"action"} = $1;
            }

            if ($parametro =~ m/^log\_regexp\_end/i) {
                $log_block = 1;
                $reg_exp++;
            }
        }
	}
}

###############################################################################
# clean_blank 
#
# This function return a string without blanspaces, given a simple text string
###############################################################################

sub clean_blank($){
        my $input = $_[0];
        $input =~ s/[\s\r\n]*//g;
        return $input;
}


###############################################################################
# SUB load_idx
#
# Load index file and Logfile
###############################################################################

sub load_idx ($$) {
    my $Idx_file = $_[0];
    my $Log_file = $_[1];

	my $line;
	my $current_ino;
	my $current_size;
    my $Idx_pos;
    my $Idx_ino;
    
	log_msg("Loading index file $Idx_file");

	open(IDXFILE, $Idx_file) || error_msg("Error opening file $Idx_file: " . $!);

	# Read position and date
	$line = <IDXFILE>;
	($Idx_pos, $Idx_ino) = split(' ', $line);

	close(IDXFILE);
	
	# Reset the file index if the file has changed
	my $current_ino = (stat($Log_file))[1];
	my $current_size = (stat($Log_file))[7];
		if (($current_ino != $Idx_ino) || ($current_size < $Idx_pos)) {
		log_msg("File changed, resetting index");

		$Idx_pos = 0;
		$Idx_ino = $current_ino;
	}

	return ($Idx_pos, $Idx_ino);
}

###############################################################################
# SUB save_idx
#
# Save index file, fiven idxfile, logfile, idxpos and idxinode
###############################################################################

sub save_idx ($$$$) {

    my $Idx_file = $_[0];
    my $Log_file = $_[1];
    my $Idx_pos  = $_[2];
    my $Idx_ino  = $_[3];

	log_msg("Saving index file $Idx_file");

	open(IDXFILE, "> $Idx_file") || error_msg("Error opening file $Idx_file: ". $!);
	print (IDXFILE $Idx_pos . " " . $Idx_ino);

	close(IDXFILE);

	return;
}

###############################################################################
# SUB create_idx
#
# Create index file.
###############################################################################

sub create_idx ($$) {
    my $Idx_file = $_[0];
    my $Log_file = $_[1];
	my $first_line;

	log_msg("Creating index file $Idx_file");

    open(LOGFILE, $Log_file) || error_msg("Error opening file $Log_file: " . $!);

	# Go to EOF and save the position
	seek(LOGFILE, 0, 2);
	my $Idx_pos = tell(LOGFILE);

	close(LOGFILE);

	# Save the file inode number
	my $Idx_ino = (stat($Log_file))[1];

    # Sometimes returns "blank" inode ¿?
    if ($Idx_ino eq ""){
            $Idx_ino = 0;
    }

	# Save the index file
	save_idx($Idx_file, $Log_file, $Idx_pos, $Idx_ino);

	return;
}

###############################################################################
# SUB parse_log
#
# Parse log file starting from position $Idx_pos.
###############################################################################

sub parse_log ($$$$$$$) {
    my $Idx_file = $_[0];
    my $Log_file = $_[1];
    my $Idx_pos  = $_[2];
    my $Idx_ino  = $_[3];
    my $Module_name  = $_[4];
    my $type  = $_[5];
    my $regexp_collection = $_[6]; # hash of rules 
	my $line;
    my $count = 0;

    my $action = "";
    my $severity = "";
    my $rule = "";
    my $buffer = "";

    # Parse log file
    
    # Open log file for reading
    open(LOGFILE, $Log_file) || error_msg("Error opening file $Log_file: " . $!);

    # Go to starting position
    seek(LOGFILE, $Idx_pos, 0);

	$buffer .= "<module>\n";
	$buffer .= "<name><![CDATA[" . $Module_name . "]]></name>\n";
    $buffer .= "<description><![CDATA[" . $Log_file . "]]></description>\n";

    if ($type eq "return_ocurrences"){
    	$buffer .= "<type>generic_data</type>\n";        
    } else {
    	$buffer .= "<type><![CDATA[async_string]]></type>\n";
    	$buffer .= "<datalist>\n";
    }

	while ($line = <LOGFILE>) {
        while (my ($key, $value) = each (%{$regexp_collection})) {
            # For each regexp block

            $rule = $value->{"rule"};

            #print "[DEBUG] Action: ".$value->{"action"} ."\n";
            #print "[DEBUG] Severity: ".$value->{"severity"} ."\n";
            #print "[DEBUG] Message: ".$value->{"message"} ."\n";
            #print "[DEBUG] Rule: ".$value->{"rule"} ."\n";

    		if ($line =~ m/$rule/i) {

    			# Remove the trailing '\n'
    			chop($line);

                # depending on type:
                if ($type eq "return_message"){
	                $buffer .= "<data><value><![CDATA[".$value->{"message"}."]]></value></data>\n";
                }
              
                if ($type eq "return_lines"){
	                $buffer .= "<data><value><![CDATA[".$line."]]></value></data>\n";
                }

                # Critical severity will prevail over other matches
                if ($severity eq ""){
                    $severity = $value->{"severity"};
                } elsif ($severity ne "CRITICAL"){
                    $severity = $value->{"severity"};
                }

                $action = $value->{"action"};
                $count++;        		
            }
        }
	}

    if ($type eq "return_ocurrences"){
        $buffer .= "<data><![CDATA[".$count."]]></data>\n";
    } else {
    	$buffer .= "</datalist>\n";
    }

    # Execute action if any match (always for last match) 
    if ($count > 0){
        `$action`;
    }        

    # Write severity field in XML
    if ($severity ne ""){
        $buffer .= "<status>$severity</status>\n";
    }

    # End XML
	$buffer .= "</module>\n";

    # Update Index
	$Idx_pos = tell(LOGFILE);
	close(LOGFILE);

    # Save the index file
	save_idx($Idx_file, $Log_file, $Idx_pos, $Idx_ino);

    # There is no need to write XML, no data.
    if (($count eq 0) && ($type ne "return_ocurrences")){
        return;
    }

    # Else, show the XML file
    print $buffer;

	return;
}

###############################################################################
# SUB print_module
#
# Dump a XML module
###############################################################################

sub print_module ($$$$$){
    my $MODULE_NAME = $_[0];
    my $MODULE_TYPE = $_[1];
    my $MODULE_VALUE = $_[2];
    my $MODULE_DESC = $_[3];
    my $MODULE_STATUS = $_[4];

    # If not a string type, remove all blank spaces!    
    if ($MODULE_TYPE !=~ m/string/){
        $MODULE_VALUE =  clean_blank($MODULE_VALUE);
    }    

    print "<module>\n";
    print "<name>$MODULE_NAME</name>\n";
    print "<type>$MODULE_TYPE</type>\n";
    print "<data><![CDATA[$MODULE_VALUE]]></data>\n";

    if ($MODULE_STATUS ne ""){
        print "<status><![CDATA[$MODULE_STATUS]]></status>\n";
    }

    print "<description><![CDATA[$MODULE_DESC]]></description>\n";
    print "</module>\n";

}


###############################################################################
# SUB manage_logfile
#
# Do the stuff with a given file and options
###############################################################################
#manage_logfile($log_filename, $module_name, $readall, $type, $regexp);

sub manage_logfile ($$$$$){

    my $Idx_pos;
    my $Idx_ino;
    my $Idx_md5;
    my $Idx_file;
    
    my $log_filename = $_[0];
    my $module_name = $_[1];
    my $readall = $_[2];
    my $type = $_[3];
    my $regexp = $_[4];
    
    my $index_file_converted = $log_filename;
	# Avoid / \ | and : characters
    $index_file_converted =~ s/\//_/g;
    $index_file_converted =~ s/\\/_/g;
    $index_file_converted =~ s/\|/_/g;
    $index_file_converted =~ s/\:/_/g;

    # Create index file if it does not exist
    $Idx_file = $plugin_setup{"index_dir"} . "/". $module_name . "_" . $index_file_converted . ".idx";

    # if force read all is enabled, 
    if (! -e $Idx_file) {
        create_idx($Idx_file, $log_filename);

        # Load index file
        ($Idx_pos, $Idx_ino) = load_idx ($Idx_file, $log_filename);            

        if ($readall == 1){
            $Idx_pos = 0;
        }
    } else {
        # Load index file
        ($Idx_pos, $Idx_ino) = load_idx ($Idx_file, $log_filename);            
    }

    # Parse log file
    parse_log($Idx_file, $log_filename, $Idx_pos, $Idx_ino, $module_name, $type, $regexp);

}

###############################################################################
###############################################################################
######################## MAIN PROGRAM CODE ####################################
###############################################################################
###############################################################################

# Parse external configuration file
# -------------------------------------------

# Load config file from command line
if ($#ARGV == -1 ){
        print "I need at least one parameter: Complete path to external configuration file \n";
        exit;
}

# Check for file
if ( ! -f $archivo_cfg ) {
        printf "\n [ERROR] Cannot open configuration file at $archivo_cfg. \n\n";
        exit 1;
}

load_external_setup ($archivo_cfg);
parse_config;

#print Dumper(%plugin_setup);

my $log_filename;
my $log_filename_multiple;
my $log_create_module_for_each_log;
my $module_name;
my $module_name_multiple;
my $module_type;
my $readall;
my $type;
my $regexp;


# Parse external configuration file
# -------------------------------------------

# Please note that following sentence is not compatible in perl 5.10, it requires
# Perl 5.13 or higher. 
while (my ($key, $value) = each (@{$plugin_setup{"log"}})) {

    # For each log defined, read data from hash tree
    #print "[DEBUG] Key: $key\n";
    #print "[DEBUG] Name: ".$value->{name} . "\n";
    #print "[DEBUG] Log Location: ".$value->{"log_location_file"} . "\n";
    #print "[DEBUG] Log Location exec: ".$value->{"log_location_exec"} . "\n";
    #print "[DEBUG] Log Location multiple: ".$value->{"log_location_multiple"} . "\n";
    #print "[DEBUG] Log readall: ".$value->{"readall"} . "\n";
    #print "[DEBUG] Type: ".$value->{"type"} . "\n";
    #print "[DEBUG] Regexp: ".$value->{"regexp"} . "\n";

    if (!defined($value->{"name"})) {
        print_module ($module_name, "async_string", "", "Missing name in log definition. Skipped", "");
        next;
    }

    $module_name = $value->{"name"};
    $readall = $value->{"readall"};
    $type = $value->{"type"};
    $regexp = $value->{"regexp"};

    # Check if filename exists

    if (defined($value->{"log_location_file"})){
        $log_filename = $value->{"log_location_file"};
        manage_logfile ($log_filename, $module_name, $readall, $type, $regexp);

    } elsif (defined($value->{"log_location_exec"})){
        $log_filename = `$value->{"log_location_exec"}`;
        manage_logfile ($log_filename, $module_name, $readall, $type, $regexp);
    }

    # Multiple files
    if (defined($value->{"log_location_multiple"})){
        $log_filename_multiple = $value->{"log_location_multiple"};
        $log_create_module_for_each_log = $value->{"module_for_each_log"};
        my @buffer = `find $log_filename_multiple`;
        foreach (@buffer) {
            # This should solve problems with carriage return in Unix, Linux and Windooze    
            chomp($_);
            chop($_) if ($_ =~ m/\r$/);
            $log_filename = $_;
            $module_name_multiple = $module_name;
            if ($log_create_module_for_each_log == 1){
                # Create a dynamic module name with the name of the logfile
                $module_name_multiple = $log_filename;
                $module_name_multiple =~ s/\//_/g;
                $module_name_multiple = $module_name . "_" . $module_name_multiple;
            }
            manage_logfile ($log_filename, $module_name_multiple, $readall, $type, $regexp);
        }
    } 

    print "\n";

}