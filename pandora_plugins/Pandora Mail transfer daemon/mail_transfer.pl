#!/usr/bin/perl
##########################################################################
# Pandora FMS Mail Transfer
# This is a tool for transfering Pandora FMS data files by mail (SMTP/POP)
##########################################################################
# Copyright (c) 2011 Artica Soluciones Tecnologicas S.L
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; version 2
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
##########################################################################

use strict;
use warnings;
use Net::SMTP;
use Mail::POP3Client;
use MIME::Parser;
$| = 1;

# GLOBAL VARIABLES 

my $boundary='frontier';

####### FUNCTIONS #######

########################################################################
## SUB check_args
## Checks the command line arguments given at the function call.
########################################################################
sub check_args(){
	my $num_args = $#ARGV + 1;
	my $action = $ARGV[0];
	my $conf_file = $ARGV[1];
	my $filename = $ARGV[2];
	my $error = "Usage: mail_transfer.pl {send|receive conf_file [FILE]}\n";
	my $error_conf_file = "conf_file does not exist or is not readable\n";
	my $error_filename = "File to send does not exist or is not readable\n";
	
	if (($num_args < 2) || (($action ne "send") && ($action ne "receive"))) {
		die $error;
	} elsif ((!(-e $conf_file)) || (!(-r $conf_file))) {
		die $error_conf_file;
	} elsif (($action eq "send") && ((!(-e $filename)) || (!(-r $filename)))) {
		die $error_filename;
	}
}

########################################################################
## SUB parse_conf
## Reads the entire conf file and stores all the information given
########################################################################
sub parse_conf ($$) {

	my $conf_file = $_[0];
	my $conf_hash = $_[1];
	
	open (CONF, $conf_file);
	my $line;
	
	while (<CONF>)
	{
		$line = $_;
		# Get the smtp user
		if ($line =~ /^smtp_user\s([a-zA-Z0-9\.\_\-\@]+)/) {
			$conf_hash -> {smtp_user} = $1;
		}
		# Get the smtp pass
		elsif ($line =~ /^smtp_pass\s(.+)/) {
			$conf_hash -> {smtp_pass} = $1;
		}
		# Get the smtp hostname
		elsif ($line =~ /^smtp_hostname\s([a-zA-Z0-9\.\_\-\@]+)/) {
			$conf_hash -> {smtp_hostname} = $1;
		}
		# Get the pop3 user
		elsif ($line =~ /^pop3_user\s([a-zA-Z0-9\.\_\-\@]+)/) {
			$conf_hash -> {pop3_user} = $1;
		}
		# Get the pop3 pass
		elsif ($line =~ /^pop3_pass\s(.+)/) {
			$conf_hash -> {pop3_pass} = $1;
		}
		# Get the pop3 hostname
		elsif ($line =~ /^pop3_hostname\s([a-zA-Z0-9\.\_\-\@]+)/) {
			$conf_hash -> {pop3_hostname} = $1;
		}
		# Get the pop3 ssl flag to know if it's enabled or not
		elsif ($line =~ /^pop3_ssl\s(0|1)/) {
			$conf_hash -> {pop3_ssl} = $1;
		}
		# Get the pop3 ssl port
		elsif ($line =~ /^pop3_ssl_port\s([0-9]{1,5})/) {
			$conf_hash -> {pop3_ssl_port} = $1;
		}
		# Get the path where to save the attached file
		elsif ($line =~ /^pathtosave\s(.+)/) {
			$conf_hash -> {pathtosave} = $1;
		}
		# Get the receiver's email where to send the attached file
		elsif ($line =~ /^receiver_email\s([a-zA-Z0-9\.\_\-\@]+)/) {
			$conf_hash -> {receiver_email} = $1;
		}
	}
	close CONF;
}

########################################################################
## SUB send_mail
## Sends an attachement file via email using smtp
########################################################################
sub send_mail($) {
	
	my $conf_hash = $_[0];
	my $smtp;
	my $attachment = $conf_hash -> {filename};

	# Get the filename in case the full path was given
	# Split the full path with '/', the last item will be the filename
	my @file_path = split ('/', $attachment);

	# Get the array's last position with '-1' index
	my $attach_file = $file_path[-1];

	my $host = $conf_hash -> {smtp_hostname};
	my $from = $conf_hash -> {smtp_user};
	my $password = $conf_hash -> {smtp_pass};
	my $to = $conf_hash -> {receiver_email};
		
	open(DATA, $attachment) || die("mail_transfer.pl: ERROR: Could not open the file $attach_file"); 
		my @xml = <DATA>;
	close(DATA);

	$smtp = Net::SMTP->new($host,
                           Hello => $host,
                           Timeout => 30,
                           Debug   => 0,
                          ) || die("mail_trasfer.pl: ERROR: Could not connect to $host");

	$smtp->auth($from, $password);
	$smtp->mail($from);
	$smtp->to($to);
	$smtp->data();
	$smtp->datasend("To: $to\n");
	$smtp->datasend("From: $from\n");
	$smtp->datasend("Subject: Pandora mail transfer\n");
	$smtp->datasend("MIME-Version: 1.0\n");
	$smtp->datasend("Content-Type: application/text; name=" . $attach_file . "\n");
	$smtp->datasend("Content-Disposition: attachment; filename=" . $attach_file . "\n");
	$smtp->datasend("Content-type: multipart/mixed boundary=" . $boundary . "\n");
	$smtp->datasend("\n");
	$smtp->datasend("@xml\n");
	$smtp->dataend() || print "mail_transfer.pl: ERROR: Data end failed: $!";
	$smtp->quit;
}

########################################################################
## SUB receive_mail
## Fetch the last email with 'Pandora mail transfer' as subject and
## download the attached file into the specified folder
########################################################################
sub receive_mail ($) {
	
	my $conf_hash = $_[0];
	my $user = $conf_hash -> {pop3_user};
	my $password = $conf_hash -> {pop3_pass};
	my $host = $conf_hash -> {pop3_hostname};
	my $ssl = $conf_hash -> {pop3_ssl};
	my $ssl_port = $conf_hash -> {pop3_ssl_port};
	my $pathtosave = $conf_hash -> {pathtosave};
    my $pop3;

    if ($ssl == 1){
	    $pop3 = new Mail::POP3Client(
			    USER		=>	$user,
			    PASSWORD	=>	$password,
			    HOST		=>	$host,
			    USESSL		=>	1,
			    PORT		=>	$ssl_port,
			    DEBUG		=>	0
	    ) or die "mail_transfer.pl: Connection failed\n";
    } else {
       $pop3 = new Mail::POP3Client(
			    USER		=>	$user,
			    PASSWORD	=>	$password,
			    HOST		=>	$host,
			    USESSL		=>	0,
			    PORT		=>	110,
			    DEBUG		=>	0
        ) or die "mail_transfer.pl: Connection failed\n";
    }

	my $tot_msg = $pop3->Count();

	if ($tot_msg == 0){
		print "No more emails avalaible\n";
	    return (0); # End program
	}
	elsif ($tot_msg eq '0E0'){
		print "No new emails available\n";
        return (0);
	}
	else{
		printf "There are $tot_msg messages \n\n";
	}

	# the list of valid file extensions. we do extensions, not
	# mime-types, because they're easier to understand from
	# an end-user perspective (no research is required).

	my $valid_exts = "txt xml data";
	my %msg_ids; # used to keep track of seen emails.

	# create a subdirectory if does not exist
	#print "Using directory '$pathtosave' for newly downloaded files.\n";
	if (!(-d $pathtosave)) {
		mkdir($pathtosave, 0777) or die "mail_transfer.pl: Error creating output directory\n";
	}
		
	# get the message to feed to MIME::Parser.
	my $msg = $pop3->HeadAndBody($tot_msg);
	my $header = $pop3->Head($tot_msg);
	
	if (($header !~ /Subject:\sPandora\smail\stransfer/) || ($header !~ /boundary=$boundary/)) {
		print "Deleting message not valid\n";

        # delete current email
        $pop3->Delete($tot_msg);

    	# clean up and close the connection.
    	$pop3->Close;
    
        return -1; 

	}

	# create a MIME::Parser object to
	# extract any attachments found within.
	my $parser = new MIME::Parser;

	$parser->output_dir($pathtosave);
	my $entity = $parser->parse_data($msg);

	# extract our mime parts and go through each one.
	my @parts = $entity->parts;
		
	foreach my $part (@parts) {

		# determine the path to the file in question.
		my $path = ($part->bodyhandle) ? $part->bodyhandle->path : undef;

		# move on if it's not defined,
		# else figure out the extension.
		next unless $path; 
		$path =~ /\w+\.([^.]+)$/;
		my $ext = $1; 
		next unless $ext;

		# we continue only if our extension is correct.
		my $continue; $continue++ if $valid_exts =~ /$ext/i;

    	# delete the blasted thing.
    	unless ($valid_exts =~ /$ext/) {
       		print "  Removing unwanted filetype ($ext): $path\n";
       		unlink $path or print " > Error removing file at $path: $!.";
       		next; # move on to the next attachment or message.
    	}

    	# a valid file type. yummy!
    	print "  Keeping valid file: $path.\n";
    }

    # delete current email
    $pop3->Delete($tot_msg);

	# clean up and close the connection.
	$pop3->Close;
}


####### MAIN #######

# Check the given command line arguments
check_args();

# Once checked store them
my $action = $ARGV[0];
my $conf_file = $ARGV[1];
my $filename = $ARGV[2];

# If the action is 'send', store the 'file_to_send'
my %conf_hash;
if ($action eq "send") {
	$conf_hash {filename} = $filename;
}

# Parse the config file 
parse_conf($conf_file, \%conf_hash);

# Call 'send_mail' function in its case
if ($action eq "send") {
	send_mail(\%conf_hash);
}

# Or call the 'receive_mail' function.
my $returncode = 1;

if ($action eq "receive") {
    while ($returncode != 0) {
        $returncode = receive_mail(\%conf_hash);
    }
}
