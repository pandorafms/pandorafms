#!/usr/bin/perl
# header
#

$AGENT_VERSION=1.2;

if ($#ARGV == -1) { print <<EOF ; exit;}

FATAL ERROR: I need an argument to PANDORA AGENT home path"

 example:   /opt/pandora_ng/pandora_agent.sh /opt/pandora_ng

EOF

my $PANDORA_HOME = shift(@ARGV);

open (CONF, "$PANDORA_HOME/pandora_agent_log.conf") or die "\nFATAL ERROR: Cannot load pandora_agent_log.conf\n\n";
open (LOG, ">>$PANDORA_HOME/pandora.log") or die "\nFATAL ERROR: Cannot open pandora.log for writing\n\n"; 

# Default values

my $OS_VERSION = `uname -r`;
chomp $OS_VERSION;
my $OS_NAME = `uname -s`;
chomp $OS_NAME;
my $CHECKSUM_MODE=1;
my $DEBUG_MODE=0;
my $CONTADOR=0;
my $EXECUTE=1;
my $MODULE_END=0;
my $SERVER_IP;
my $SERVER_PATH;
my $TEMP;
my $INTERVAL;
my $NOMBRE_HOST;
my @now = localtime();
my $DATE = ( $now[5] + 1900 ) . "/" . ($now[4] + 1) . "/" . $now[3] . " " .
		$now[2] . ":" . $now[1] . ":" . $now[0];
my $TIMESTAMP = $DATE;

# reading agent configuration from pandora_agent.conf
# TODO writting into the log

my @all_modules;	# array for storing modules configuration
			# $all_modules[i]{'module_exec'}  for example
	
my %log_modules;	# array for storing log modules configuration
			# $log_modules{'/url/file'}[1] points to the element 1 of @all_modules

while (<CONF>) {
   next if (/^\s*#/);
   split;
   # from the next 7 variables, $SERVER_IP, $SERVER_PATH and $INTERVAL are not used
   $_[0] =~ /^server_ip/		&&	do { $SERVER_IP = $_[1]; };
   $_[0] =~ /^server_path/		&&	do { $SERVER_PATH = $_[1]; };
   $_[0] =~ /^temporal/			&&	do { $TEMP = $_[1]};
   $_[0] =~ /^interval/			&&	do { $INTERVAL = $_[1]; };
   $_[0] =~ /^agent_name/		&&	do { $NOMBRE_HOST = $_[1]; };
   $_[0] =~ /^debug/			&&	do { $DEBUG_MODE = $_[1]; };
   $_[0] =~ /^checksum/			&&	do { $CHECKSUM_MODE = $_[1]; };

   # now we load all the module configuration into @all_modules
   # Maybe it is not necessary in this script, but it do not load too much
   # and could be useful for someone
   
   $_[0] =~ /^module_begin/		&&	do {
   	# lets read this module	
	++$#all_modules;
	aqui: while (<CONF>) {
		if (/^module_end/) { last aqui; }
		split;
		if ($_[0] =~ /^module/) { $all_modules[$#all_modules]{$_[0]} = join (" ",@_[1..$#_]);}
	}
   }   
}

# now, %log_modules is populated with pointers to the elements of @all_modules that
# are 'module_log'

for ($cc=0; $cc<=$#all_modules; $cc++) {
	if ( grep(/^module_log/, keys %{$all_modules[$cc]} ) ) { 
		unshift (  @{$log_modules{ $all_modules[$cc]{'module_log'} }}, $cc  );
	}
}

# Hostname
unless ($NOMBRE_HOST) { $NOMBRE_HOST = `/bin/hostname`; };

# debug
print "all_modules:\n";
for ($cc=0; $cc <= $#all_modules; $cc++) { print "- $cc : " . $all_modules[$cc]{'module_name'} . "\n";  }

print "\n\nlog_modules:\n";
foreach $el (keys %log_modules)  {  
	print "\t$el\n";
	foreach $eel ( @{$log_modules{$el}} ) { print "\t$eel\n"; }
}
print "\n\n";


# MAIN Program loop begins

# this loop go over every log file configured in pandora_agent.conf.
# For each file, it executes all modules associated to it and creates
# one INDIVIDUAL data file in data_out for each new line of the log
# Timestamp can be overwritten with 'module_log_timestamp' option in
# pandora_agent.conf

# for each log file found in pandora_agent.conf
foreach $logfile ( keys %log_modules ) {

	my $last_line = 0;		# last line read
	my $last_line_byte = 0;		# position of $last_line
	my $last_byte = 0;		# last byte read ($last_line_byte + len($last_line))

	# if the file does not exist, we jump to the next
	
        #if ( ! -e $logfile) { next; }

	#debug
	print "\nprocessing $logfile ...\n";
	
	# lets open the index file to see how went the last time the agent
	# accessed $logfile
	
	$logfile_index = $logfile;
	$logfile_index =~ tr/\\\//_/;
	$logfile_index = $PANDORA_HOME . "/" . $logfile_index . ".index";

	#debug
	print "\tindex file: $logfile_index\n";
	
        if ( -e  $logfile_index ){
		
		# let's recover the index
		
		open (LOGFILE_INDEX, $logfile_index);
		my $index_data = <LOGFILE_INDEX>;
		close LOGFILE_INDEX;
		( $last_byte, $last_line, $last_line_byte ) = split (/\s+/, $index_data);
		
		# lets try to quickly detect if the log file has been rewritten
		# If that's the case, probably,  $logfile_size < $last_byte !
		# TODO: Note that if the logfile has been rewritten but 
		# $logfile_size > $last_byte, lines will be lost!!
		# more checks are needed
		
		my $logfile_size = (stat($logfile))[7];

		# debug
		print "\tlogfile size: $logfile_size\n";
		
		if ( $logfile_size < $last_byte ) { 
			
			# debug
			print "\tdeleting logfile.index $logfile_index\n";
			
			unlink ($logfile_index);
			( $last_byte, $last_line, $last_line_byte ) = ( 0, 0, 0 );
		}
        }
	
	# debug
	print "\tlast_byte: $last_byte\n";
        
        open (LOGFILE, $logfile);   # TODO checks!!
	
	# TODO:  more checks to see if the log file has been rewritten
	# we could check if the last line read remains the same
	# If not, maybe the log file has changed!
	# ... for the next version ...
	
	# moving to the last position read and begining to read new lines
	
	seek ( LOGFILE, $last_byte, 0 );
	while ($logline = <LOGFILE>) {

		chomp $logline;
		$logline =~ s/\'/\\\'/g; 	# escaping '

		# debug
		print "\tlog line: $logline\n";

		# for each line, a data file will be created in data_out
		# for each line, all modules associated to this logfile are processed

		# creating data file
		
		$SERIAL = time() . "-" . (int(rand(1000)) + 1);  # yes, could be a counter
		$datafile = "$TEMP/$NOMBRE_HOST.$SERIAL.data";
		$checksumfile = "$TEMP/$NOMBRE_HOST.$SERIAL.checksum";
		
		open (DATAFILE, ">$datafile");  # TODO checks !!

		# timestamp for the data file (remember, one for each line of the log)
		# can be overwritten. For that, a module with module_log_timestamp is needed.
		# This module has to return a TIMESTAMP with the format like the command
		# date +"%Y/%m/%d %H:%M:%S"
		# NOTE: this module will not be included in the data file as a 
		# normal module, i. e., timestamps will not be recorded in the database

		$TIMESTAMP_module = $TIMESTAMP;
		foreach $module_id ( @{$log_modules{$logfile}} ) {
			
			# just for clarity
			my %module = %{$all_modules[$module_id]};
			my $module_exec = $module{'module_exec'};   # TODO: checks!!
			
			unless ( defined($module{'module_log_timestamp'}) ) { next; }

			# let's substitute key words
			$module_exec = 'my $LINE  = \'' . $logline . '\'; ' . $module_exec . ';' ;
			
			$TIMESTAMP_module = eval( $module_exec );

			last;  # only the first 'module_log_timestamp' is considered
		}

		# header of the data file is printed now with the calculated timestamp
		
		print DATAFILE "<agent_data os_name='$OS_NAME' os_version='$OS_VERSION' interval='$INTERVAL' version='$AGENT_VERSION' timestamp='$TIMESTAMP_module' agent_name='$NOMBRE_HOST'>\n";

		# now, for every module of this logfile, a <module> entry is created
		# in DATAFILE.
		# Note that we do not use a data_temp, neither we check for all the required
		# fields --> for next version
		#
		# module_interval is not supported --> future versions
		
		foreach $module_id ( @{$log_modules{$logfile}} ) {

			# just for clarity
			my %module = %{$all_modules[$module_id]};
			my $module_exec = $module{'module_exec'};

			# modules with 'module_log_timestamp' wont be considered
			if ( defined($module{'module_log_timestamp'}) ) { next; }
			
			# debug
			print "\t\tprocessing module: $module_id\n";
			
			# let's substitute key words
			$module_exec = 'my $LINE  = \'' . $logline . '\'; ' . $module_exec . ';' ;
			
			# debug
			print "\t\texecuting: $module_exec\n";

			# printing headers
			print DATAFILE '<module>' . "\n";
			
			print DATAFILE '<name><![CDATA[' . $module{'module_name'} . ']]></name>'  . "\n"
				if ( defined($module{'module_name'}) );
			print DATAFILE '<max><![CDATA[' . $module{'module_max'} . ']]></max>' . "\n"	
				if ( defined($module{'module_max'}) );
			print DATAFILE '<min><![CDATA[' . $module{'module_min'} . ']]></min>' . "\n"		
				if ( defined($module{'module_min'}) );
			print DATAFILE '<description><![CDATA[' . $module{'module_description'} . ']]></description>' . "\n"
				if ( defined($module{'module_description'}) );
			print DATAFILE '<type><![CDATA[' . $module{'module_type'} . ']]></type>' . "\n"
				if ( defined($module{'module_type'}) );
				
			# in next line we execute $module_exec after key substitution
			# SECURITY NOTE:  in this version there are not injection checks,
			# so, if the agent is running as root, any user that can write in the log
			# files could execute commands as root !!!
			
			print DATAFILE '<data><![CDATA[' . eval( $module_exec ) . ']]></data>' . "\n";

			# debug
			print "\t\tresult: " . eval( $module_exec ) . "\n";
			
			print DATAFILE '</module>' . "\n";
		}

		# finishing this data file
		print DATAFILE "</agent_data>";
		close (DATAFILE);

		# now, checksum
		# we use /usr/bin/md5sum
		# for next versions: do it with perl
		
		open (CHECKSUM_FILE, ">$checksumfile");    #TODO checks!!
		
		# debug
		print "\t\tchecksum: $CHECKSUM_MODE\n";
		
		if ($CHECKSUM_MODE and (-e '/usr/bin/md5sum')) {
			print CHECKSUM_FILE `/usr/bin/md5sum $datafile`;
		} else {
			print CHECKSUM_FILE "No valid checksum";
		}
		close (CHECKSUM_FILE);
	}

	# updating index file
	
	# debug
	print "\tupdating index: $logfile_index\n";

	unlink $logfile_index;    # TODO checks!!
	if (-e $logfile) {
		open (LOGFILE_INDEX, ">$logfile_index");
		$last_byte = tell( LOGFILE );
		print LOGFILE_INDEX $last_byte . " " . $last_line . " " . $last_line_byte . "\n";
		print LOGFILE_INDEX "# $logfile";
		close LOGFILE_INDEX;
	}

}







