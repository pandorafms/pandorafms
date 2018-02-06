#!/usr/bin/perl
# Pandora FMS Plugin for MySQL
# (c) Artica ST 2015
# v1.4, 19 Feb 2015
# ----------------------------------------------------------------------

# Default lib dir for RPM and DEB packages
use lib '/usr/lib/perl5';

use strict;
use Data::Dumper;
use POSIX qw(setsid strftime);
use POSIX;
use Time::Local;

#---------------------------- Global parameters -----------------------#

# OS and OS version
my $OS = $^O;

# Store original PATH
my $ORIGINAL_PATH = $ENV{'PATH'};

# FLUSH in each IO
$| = 1;

# Conf file divided line by line
my @config_file;
# Conf filename received by command line
my $archivo_cfg = $ARGV[0];
# Hash with this plugin setup
my %plugin_setup; 
# Array with different block checks 
my @checks;

# ----------------------------------------------------------------------
# parse_dosline (line) 
#
# This cleans DOS-like line and cleans ^M character. VERY Important when 
# you process .conf edited from DOS
# ----------------------------------------------------------------------
sub parse_dosline ($){
    my $str = $_[0];

    $str =~ s/\r//g;
    return $str;
}

# ----------------------------------------------------------------------
# load_external_setup (config file) 
#
# Loads a config file 
# ----------------------------------------------------------------------
sub load_external_setup ($){
    my $archivo_cfg = $_[0];
    my $buffer_line;

    # Collect items from config file and put in an array
    if (! open (CFG, "< $archivo_cfg")) {
		print "[ERROR] Error opening configuration file $archivo_cfg: $!.\n";
        logger ("[ERROR] Error opening configuration file $archivo_cfg: $!");
        exit 0;
	}

    while (<CFG>) {
		$buffer_line = parse_dosline ($_);
        # Parse configuration file, this is specially difficult because can contain SQL code, with many things
        if ($buffer_line !~ /^\#/) {  # begins with anything except # (for commenting)
			if ($buffer_line =~ m/(.+)\s(.*)/) {
				push @config_file, $buffer_line;
            }
        }
    }
    
    close (CFG);
}

# ----------------------------------------------------------------------
# logger_begin (message)
# Beggining of logging to file
# ----------------------------------------------------------------------
sub logger_begin ($) {
	my ($message) = @_;
		
	my $file = "/tmp/pandora_mysql";
			
    if (! open (FILE, "> $file")) {
		print "[ERROR] Could not open logfile '$file' \n";
        logger ("[ERROR] Error opening logfile $file");
        exit 0;
	}				
				
#	open (FILE, "> $file") or die "[FATAL] Could not open logfile '$file'";
	print FILE strftime ("%Y-%m-%d %H:%M:%S", localtime()) . " >> " . $message . "\n";
	close (FILE);
}

# ----------------------------------------------------------------------
# logger (message)
# Log to file
# ----------------------------------------------------------------------
sub logger ($) {
	my ($message) = @_;
		
	my $file = "/tmp/pandora_mysql";	
	
    if (! open (FILE, ">> $file")) {
		print "[ERROR] Could not open logfile '$file' \n";
        logger ("[ERROR] Error opening logfile $file");
        exit 0;
	}	
	
	print FILE strftime ("%Y-%m-%d %H:%M:%S", localtime()) . " >> " . $message . "\n";
	close (FILE);
}

# ----------------------------------------------------------------------
# trim (string) 
#
# Erase blank spaces before and after the string
# ----------------------------------------------------------------------
sub trim ($) {
	my $string = shift;
	$string =~ s/^\s+//;
	$string =~ s/\s+$//;
	
	return $string;
}

# ----------------------------------------------------------------------
# clean_blank (string)
#
# This function return a string without blankspaces, given a simple text 
# string
# ----------------------------------------------------------------------
sub clean_blank($) {
	my $input = $_[0];
	$input =~ s/[\s\r\n]*//g;
        
    return $input;
}

# ----------------------------------------------------------------------
# print_module (module name, module type, module value, module 
# description, [module severity])
#
# This function returns a XML module part for the plugin
# ----------------------------------------------------------------------
sub print_module ($$$$;$) {
    my $MODULE_NAME = $_[0];
    my $MODULE_TYPE = $_[1];
    my $MODULE_VALUE = $_[2];
    my $MODULE_DESC = $_[3];    
	my $MODULE_SEVERITY = $_[4];

    # If not a string type, remove all blank spaces and check for not value returned!    
    if ($MODULE_TYPE !~ m/string/) {
		$MODULE_VALUE =  clean_blank($MODULE_VALUE);
    }    

    print "<module>\n";
    print "<name>$MODULE_NAME</name>\n";
    print "<type>$MODULE_TYPE</type>\n";
    print "<data><![CDATA[$MODULE_VALUE]]></data>\n";
    print "<description><![CDATA[$MODULE_DESC]]></description>\n";
    if (defined($MODULE_SEVERITY)) {
		print "<status>$MODULE_SEVERITY</status>\n";    
	} else {
		print "<status>NORMAL</status>\n";  	
	}	
		
    print "</module>\n";
}

# ----------------------------------------------------------------------
# parse_config
#
# This function load configuration tokens and store in a global hash
# called %plugin_setup accesible on all program.
# ----------------------------------------------------------------------
sub parse_config {

    my $check_block = 0;
    my $parametro;

    # Some default options
    $plugin_setup{"conf_mysql_homedir"} = "/var/lib/mysql";
    $plugin_setup{"conf_mysql_basedir"} = "/var/lib/mysql";    
    $plugin_setup{"conf_temp"} = "/tmp";
    $plugin_setup{"numchecks"} = 0;
    $plugin_setup{"conf_mysql_version"} = "5.5";

	foreach (@config_file) {
        $parametro = $_;

        if ($parametro =~ m/^include\s(.*)/i) {
            load_external_setup ($1);
        }
    
 	if ($parametro =~ m/^conf\_mysql\_version\s(.*)/i) {
            $plugin_setup{"conf_mysql_version"} = trim($1);
        }

        if ($parametro =~ m/^conf\_mysql\_user\s(.*)/i) {
            $plugin_setup{"conf_mysql_user"} = trim($1);
        }
    
        if ($parametro =~ m/^conf\_mysql\_pass\s(.*)/i) {
	    my $temp = $1;
	    $temp =~ s/\"//g;
            $plugin_setup{"conf_mysql_pass"} = trim($temp);
        }

        if ($parametro =~ m/^conf\_mysql\_host\s(.*)/i) {
            $plugin_setup{"conf_mysql_host"} = trim($1);
        }
        
        if ($parametro =~ m/^conf\_mysql\_homedir\s(.*)/i) {
			$plugin_setup{"conf_mysql_homedir"} = trim($1);
		}
		if ($parametro =~ m/^conf\_mysql\_basedir\s(.*)/i) {
			$plugin_setup{"conf_mysql_basedir"} = trim($1);
		}
		
        if ($parametro =~ m/^conf\_mysql\_logfile\s(.*)/i) {
			$plugin_setup{"conf_mysql_logfile"} = trim($1);
		}
	
        if ($parametro =~ m/^conf\_temp\s(.*)/i) {
			$plugin_setup{"conf_temp"} = trim($1);
		}
		
        if ($parametro =~ m/^conf\_logparser\s(.*)/i) {
			$plugin_setup{"conf_logparser"} = trim($1);
		}		
		
        # Detect begin of check definition
        if ($parametro =~ m/^check\_begin/i) {
            $check_block = 1;
            $checks[$plugin_setup{"numchecks"}]{'type'} = 'unknown';
        }
        
		############### Specific check block parsing ###################       		
		if ($check_block == 1) {

			if ($parametro =~ m/^check\_end/i) {
				$check_block = 0;
				$plugin_setup{"numchecks"}++;
			}
		
			# Try to parse check type (System parameters)
			if ($parametro =~ m/^check\_mysql\_service/i) {
				$checks[$plugin_setup{"numchecks"}]{'type'} = 'mysql_service';
			} 
			
			if ($parametro =~ m/^check\_mysql\_memory/i) { 
				$checks[$plugin_setup{"numchecks"}]{'type'} = 'mysql_memory';				
			}
			
			if ($parametro =~ m/^check\_mysql\_cpu/i) { 
				$checks[$plugin_setup{"numchecks"}]{'type'} = 'mysql_cpu';				
			}
			
			if ($parametro =~ m/^check\_system\_timewait/i) { 
				$checks[$plugin_setup{"numchecks"}]{'type'} = 'system_timewait';				
			}
	
			if ($parametro =~ m/^check\_system\_diskusage/i) { 
				$checks[$plugin_setup{"numchecks"}]{'type'} = 'system_diskusage';				
			}		
	
			if ($parametro =~ m/^check\_mysql\_ibdata1/i) { 
				$checks[$plugin_setup{"numchecks"}]{'type'} = 'mysql_ibdata1';				
			}	

			if ($parametro =~ m/^check\_mysql\_logs/i) { 
				$checks[$plugin_setup{"numchecks"}]{'type'} = 'mysql_logs';				
			}
			
			if ($parametro =~ m/^check\_mysql\_connect/i) { 
				$checks[$plugin_setup{"numchecks"}]{'type'} = 'mysql_connection';				
			}		
			
			
			# Try to parse check type (Performance parameters)
			if ($parametro =~ m/^mysql\_status\s(.*)/i) { 
				$checks[$plugin_setup{"numchecks"}]{'type'} = 'status';
				$checks[$plugin_setup{"numchecks"}]{'show'} = trim($1);					
			}
			
			# Try to parse check type (Open SQL interface)
			if ($parametro =~ m/^check\_sql\s(.*)/i) { 
				$checks[$plugin_setup{"numchecks"}]{'type'} = 'sql';
				$checks[$plugin_setup{"numchecks"}]{'sql'} = trim($1);					
			}
			
			# Try to parse check type (Query Innodb status)
			if ($parametro =~ m/^mysql\_innodb\s(.*)/i) { 
				$checks[$plugin_setup{"numchecks"}]{'type'} = 'innodb';
				$checks[$plugin_setup{"numchecks"}]{'query'} = trim($1);					
			}			
			
			if ($parametro =~ m/^check\_name\s(.*)/i) { 
				$checks[$plugin_setup{"numchecks"}]{'check_name'} = trim($1);					
			}	
			
			if ($parametro =~ m/^check\_schema\s(.*)/i) { 
				$checks[$plugin_setup{"numchecks"}]{'check_schema'} = trim($1);					
			}					
			
			if ($parametro =~ m/^module\_type\s(generic_data|generic_data_inc|generic_data_string|generic_proc|async_string|async_proc|async_data)/i) { 
				$checks[$plugin_setup{"numchecks"}]{'module_type'} = trim($1);				
			}			
			
			if ($parametro =~ m/^post\_condition\s+(==|!=|<|>)\s+(.*)/i) {
				$checks[$plugin_setup{"numchecks"}]{'postcondition_op'} = trim($1);	
				$checks[$plugin_setup{"numchecks"}]{'postcondition_val'} = trim($2);
			}
			
			if ($parametro =~ m/^post\_execution\s(.*)/i) {
				$checks[$plugin_setup{"numchecks"}]{'postexecution'} = trim($1);
			}
			
			if ($parametro =~ m/^data\_absolute/i) {
				$checks[$plugin_setup{"numchecks"}]{'return_type'} = 'data_absolute';
			}			
		
			if ($parametro =~ m/^data\_delta/i) {
				$checks[$plugin_setup{"numchecks"}]{'return_type'} = 'data_delta';
			}		

			if ($parametro =~ m/^post\_status\s(WARNING|CRITICAL)/i) {
				$checks[$plugin_setup{"numchecks"}]{'status'} = trim($1);
			}			
				
		} # Check block
	
	}
}

# ----------------------------------------------------------------------
# check_mysql_service
#
# This function check MySQL service status 
# ----------------------------------------------------------------------
sub check_mysql_service {
	my ($mysql_service_result1, $mysql_service_result2, $mysql_service_result3, $mysql_service_result4);
	my $mysql_service_result = 0;
	
	# Try different flavours of mysql status
	$mysql_service_result1 = `service mysql status 2> /dev/null`;
	$mysql_service_result2 = `service mysqld status 2> /dev/null`;
	$mysql_service_result3 = `/etc/init.d/mysql status 2> /dev/null`;		
	$mysql_service_result4 = `/etc/init.d/mysqld status 2> /dev/null`;		
	
	
	#print "1 >> " . $mysql_service_result1 . "\n";
	#print "2 >> " . $mysql_service_result2 . "\n";
	#print "3 >> " . $mysql_service_result3 . "\n";
	#print "4 >> " . $mysql_service_result4 . "\n";	
	
	# If any of these ones is up then check result eq OK!
	if (($mysql_service_result1 =~ /start/i) || ($mysql_service_result1 =~ /running/i) ||
		($mysql_service_result2 =~ /start/i) || ($mysql_service_result2 =~ /running/i) ||
		($mysql_service_result3 =~ /start/i) || ($mysql_service_result3 =~ /running/i) ||
		($mysql_service_result4 =~ /start/i) || ($mysql_service_result4 =~ /running/i)) {
		
		# OK!
		$mysql_service_result = 1;
			
	} else {
		
		# Fail
		$mysql_service_result = 0;
		
	}	

	logger("[INFO] check_mysql_service with result: " . $mysql_service_result);
	
	return $mysql_service_result;
	
}

# ----------------------------------------------------------------------
# check_mysql_memory
#
# This function check MySQL memory usage
# ----------------------------------------------------------------------
sub check_mysql_memory {
	my $mysql_memory_result = 0;
	
	# Depends on OS 
	if ($OS =~ /hpux/i){
		$mysql_memory_result = `ps -eo comm,pmem | grep -v "grep" | grep "mysqld --basedir" | awk '{print \$2}'`;
	} else {
		$mysql_memory_result = `ps aux | grep -v "grep" | grep "mysqld --basedir" | awk '{print \$4}'`;
	}
	
	logger("[INFO] check_mysql_memory with result: " . $mysql_memory_result);	
	
	return $mysql_memory_result;
}

# ----------------------------------------------------------------------
# check_mysql_cpu
#
# This function check MySQL cpu usage
# ----------------------------------------------------------------------
sub check_mysql_cpu {
	my $mysql_cpu_result = 0;
	
	# Depends on OS 
	if ($OS =~ /hpux/i){
		$mysql_cpu_result = `ps -eo comm,pcpu | grep -v "grep" | grep "mysqld --basedir" | awk '{print \$2}'`;
	} else {
		$mysql_cpu_result = `ps aux | grep -v "grep" | grep "mysqld --basedir" | awk '{print \$3}'`;
	}
	
	logger("[INFO] check_mysql_cpu with result: " . $mysql_cpu_result);	
	
	return $mysql_cpu_result;
}

# ----------------------------------------------------------------------
# check_system_timewait
#
# This function check system timewait
# ----------------------------------------------------------------------
sub check_system_timewait {
	my $system_timewait_result = 0;
	
	$system_timewait_result = `netstat -ntu | grep "TIME_WAIT" | wc -l`;
	
	logger("[INFO] check_system_timewait with result: " . $system_timewait_result);	
	
	return $system_timewait_result;
}

# ----------------------------------------------------------------------
# check_system_diskusage
#
# This function check system disk usage
# ----------------------------------------------------------------------
sub check_system_diskusage {
	my $system_diskusage_result_tmp = 0;
	my $system_diskusage_result = 0;
	
	if (!defined($plugin_setup{"conf_mysql_homedir"})) {
		logger("[INFO] system_diskusage_result check needs conf_mysql_homedir token defined in configuration file.");
		return 0;
	}
	
	my $system_diskusage_result_tmp = `df -k "$plugin_setup{'conf_mysql_homedir'}" | awk '{print \$5}' | tail -1 | tr -d "%"`;
 
	$system_diskusage_result = 100 - $system_diskusage_result_tmp;
	
	logger("[INFO] check_system_diskusage with result: " . $system_diskusage_result);	
	
	return $system_diskusage_result;
}


# ----------------------------------------------------------------------
# check_mysql_ibdata1
#
# This function check ibdata1 file disk usage
# ----------------------------------------------------------------------
sub check_mysql_ibdata1 {
	my $mysql_ibdata1_result = 0;
	
	if (!defined($plugin_setup{"conf_mysql_basedir"})) {
		logger("[INFO] check_mysql_ibdata1 check needs conf_mysql_basedir token defined in configuration file.");
	}
	
	$mysql_ibdata1_result = `du -k $plugin_setup{"conf_mysql_basedir"}/ibdata1 | tail -1 | awk '{ print \$1 }'`;
	
	logger("[INFO] check_mysql_ibdata1 with result: " . $mysql_ibdata1_result);
	
	return $mysql_ibdata1_result;
}

# ----------------------------------------------------------------------
# check_mysql_connection
#
# This function check connection against MySQL, if goes wrong abort monitoring
# ----------------------------------------------------------------------
sub check_mysql_connection {
	my $mysql_connection_result = 1;
	my $connection_string = 0;
	if (!defined($plugin_setup{"conf_mysql_pass"})){
	$connection_string = "mysql -u" . $plugin_setup{"conf_mysql_user"} .  
								 " -h" . $plugin_setup{"conf_mysql_host"} ;
						}else{
	# Connection string
	$connection_string = "mysql -u" . $plugin_setup{"conf_mysql_user"} . 
								 " -p" . $plugin_setup{"conf_mysql_pass"} . 
								 " -h" . $plugin_setup{"conf_mysql_host"} ;
	}
	my $tmp_file = $plugin_setup{'conf_temp'} . '/mysql_connection.tmp';
								 
	my $connection_result = `$connection_string -e "SELECT 1 FROM DUAL" 2> $tmp_file`;
		
	# Collect info from temp file
	open (TMP, "< $tmp_file");
	
	while (<TMP>) {
		my $buffer_line = parse_dosline ($_);
		
		if ($buffer_line =~ /error/i) {
			$mysql_connection_result = 0;
		}
	}
	
	close (TMP);
	
	unlink($tmp_file);		

	logger("[INFO] check_mysql_connection with result: " . $mysql_connection_result);
	
	# Abort monitoring
	if ($mysql_connection_result == 0) {
		print_module("MySQL_connection_error", "async_string", "Error", "MySQL plugin cannot connect to Database. Abort execution of plugin", "CRITICAL");
	}
	
	return $mysql_connection_result;
}


# ----------------------------------------------------------------------
# check_mysql_logs
#
# This function check ibdata1 file disk usage
# ----------------------------------------------------------------------
sub check_mysql_logs(;$) {
    my $module_type = $_[0];
	
	my $mysql_logs_result = 0;
	
	if (!defined($plugin_setup{"conf_logparser"})) {
		logger("[INFO] check_mysql_logs check needs conf_logparser token defined in configuration file.");
		return 0;
	}
	
	if (!defined($plugin_setup{"conf_logparser"})) {
		logger("[INFO] check_mysql_logs check needs conf_mysql_logfile token defined in configuration file.");
		return 0;
	}	
	
	my $plugin_call = $plugin_setup{"conf_logparser"}. " " . $plugin_setup{"conf_mysql_logfile"} . " MySQL_error_logs error 2> /dev/null";

	$mysql_logs_result = `$plugin_call`;

    if ($mysql_logs_result ne "") {
		logger ("[INFO] check_mysql_logs with result:\n$mysql_logs_result");		
        print $mysql_logs_result;
        
		# Process output of grep_log plugin
		my @temp = split ("\n", $mysql_logs_result);
		my @result;
		
		# Try to get return values
		my $i = 0;
		foreach (@temp) {
			# Get return values
			if ($_ =~ /<data><value><!\[CDATA\[(.*)\]\]><\/value><\/data>/){
				$result[$i] = $1;
				$i++;
			}
		}
		
		return @result;        
        
    } else {
		logger ("[INFO] Blank output in check_mysql_logs searching in logfile: " . $plugin_setup{"conf_mysql_logfile"});
		
#		if (defined($module_type)) {
#			print_module("MySQL_error_logs", $module_type, "Blank output", "Blank output in check_mysql_logs searching in logfile " . $plugin_setup{"conf_mysql_logfile"});
#		} else {
#			print_module("MySQL_error_logs", "async_string", "Blank output", "Blank output in check_mysql_logs searching in logfile " . $plugin_setup{"conf_mysql_logfile"});			
#		}
	}
}

# ----------------------------------------------------------------------
# check_mysql_status (SHOW parameter, [SQL statement/ Query for Innodb status, Mysql_schema])
#
# This function check status parameter (Performance parameters)
# ----------------------------------------------------------------------
sub check_mysql_status($;$$) {
	my $show_parameter = $_[0];
	my $sql_statement = $_[1];	
	my $sql_schema = $_[2];	
	my $mysql_status_result = 0;
	
	if (!defined($show_parameter)){
		logger("[INFO] Empty parameter in check_mysql_status check, please revise configuration file.");
		return 0;
	}
	
	# Writes in temp file
	my $temp_file = $plugin_setup{"conf_temp"} . "/mysql_pandora.tmp";
	
    open (TEMP1, "> " . $temp_file);
    
    # Depends on the request write different statements ('pending_io', 'sql', 'innodb' is different)
    if ($show_parameter =~ /processlist/i) {
		print TEMP1 "SHOW " . $show_parameter;
	} elsif ($show_parameter =~ /total_size/i) {
		print TEMP1 "SELECT \"Total_size\", 
						   sum( data_length + index_length ) / 1024 / 1024 / 1024 \"Data Base Size in GB\" 
					FROM information_schema.TABLES";
    } elsif (($show_parameter !~ /pending_io/i) and ($show_parameter !~ /sql/i) and ($show_parameter ne 'innodb')) {
		print TEMP1 "SHOW GLOBAL STATUS LIKE '" . $show_parameter . "'";
	}
    
    close (TEMP1);

	# Connection string
	my $connection_string = 0;
	if (!defined($plugin_setup{"conf_mysql_pass"} ) ){
	$connection_string = "mysql -u" . $plugin_setup{"conf_mysql_user"} .  
								 " -h" . $plugin_setup{"conf_mysql_host"} ;
						}else{
	$connection_string = "mysql -u" . $plugin_setup{"conf_mysql_user"} . 
								 " -p" . $plugin_setup{"conf_mysql_pass"} . 
								 " -h" . $plugin_setup{"conf_mysql_host"} ;
	}
	#my $connection_string = "mysql -u" . $plugin_setup{"conf_mysql_user"} . 
	#							 " -p" . $plugin_setup{"conf_mysql_pass"} . 
	#							 " -h" . $plugin_setup{"conf_mysql_host"} ;
								 
	# If its a query, then try to add schema to the connection string
	if ($show_parameter =~ /sql/i) {
		if (defined($sql_schema)) {
			$connection_string .= " $sql_schema";
		}
	}
		
	my $post_sql = '';			
	# If we want to retrieve 'active sessions' count lines				 
	if ($show_parameter =~ /processlist/i) {
		$post_sql = ' | wc -l';
	} # Rest of request have this suffix 
	else {
		$post_sql = ' | tail -1 | awk \'{print $2}\'';
	}
	
	# 'pending_io' and 'sql' and 'innodb' are different
	if (($show_parameter !~ /pending_io/i) and ($show_parameter !~ /sql/i) and ($show_parameter ne 'innodb')) {										 
		$mysql_status_result = `$connection_string  <  $temp_file  $post_sql`;							 
	} elsif ($show_parameter =~ /sql/i) {
		$mysql_status_result = `$connection_string -e "$sql_statement" | tail -1`;
	} elsif ($show_parameter =~ /innodb/i) {


		if ($plugin_setup{"conf_mysql_version"} == "5.0") {
			$mysql_status_result = `$connection_string -e "SHOW INNODB STATUS\\G"`;
		} else {
			$mysql_status_result = `$connection_string -e "SHOW ENGINE INNODB STATUS\\G"`;
		}

		# Process output of show innodb status
		my @temp = split ("\n", $mysql_status_result);
		
		# If we detect query lines then output
		$mysql_status_result = 0;
		foreach (@temp) {
			if ($_ =~ m/$sql_statement\s+(.*)/) {
				$mysql_status_result = $1;
			}
		}

	} else {

		if ($plugin_setup{"conf_mysql_version"} == "5.0") {
			$mysql_status_result = `$connection_string -e "SHOW INNODB STATUS\\G"`;
		} else {
			$mysql_status_result = `$connection_string -e "SHOW ENGINE INNODB STATUS\\G"`;
		}
	
		# Process output of show innodb status
		my @temp = split ("\n", $mysql_status_result);
		
		# If we detect 'i/o waiting' lines then increment counter
		$mysql_status_result = 0;
		foreach (@temp) {
			if ($_ =~ m/(.*)waiting for i\/o request(.*)/){
				$mysql_status_result++;
			}
		}
	}
	
	unlink ($temp_file);

	# If we want to retrieve active session substract 1 due to header
	if ($show_parameter =~ /processlist/i) {
		if ($mysql_status_result > 0){
			$mysql_status_result--;
		}
	}

	logger("[INFO] check_mysql_status executing: $show_parameter with result: " . $mysql_status_result);
	
	return $mysql_status_result;
}

# ----------------------------------------------------------------------
# is_numeric (arg)
#
# Return TRUE if given argument is numeric
# ----------------------------------------------------------------------
sub is_numeric($) {
	my $val = $_[0];
	
	if (!defined($val)){
		return 0;
	}
	# Replace "," for "."
	$val =~ s/\,/\./;
	
	my $DIGITS = qr{ \d+ (?: [.] \d*)? | [.] \d+ }xms;
	my $SIGN   = qr{ [+-] }xms;
	my $NUMBER = qr{ ($SIGN?) ($DIGITS) }xms;
	if ( $val !~ /^${NUMBER}$/ ) {
		return 0;   #Non-numeric
	} else {
		return 1;   #Numeric
	}
}

# ----------------------------------------------------------------------
# eval_postcondition (result, operator, value)
#
# This function eval postcondition and return a boolean
# ----------------------------------------------------------------------
sub eval_postcondition($$$) {
	my $result = $_[0];
	my $operator = $_[1];
	my $value = $_[2];
	my $eval_postcondition_result = 0;

	my $result_type = is_numeric($result);
	
	logger("[INFO] Evaluating poscondition: $result $operator $value.");
	
	 # Numeric result
	if ($result_type == 1) {
		 # Equal to
		if ($operator =~ /==/) {
			if (int($result) == int($value)) {
				
				$eval_postcondition_result = 1;
				
			}
		}# Not equal to
		elsif ($operator =~ /!=/) {
			
			if (int($result) != int($value)) {
				
				$eval_postcondition_result = 1;
				
			}			
		}# Less than
		elsif ($operator =~ /</) {
			
			if (int($result) < int($value)) {
				
				$eval_postcondition_result = 1;
				
			}			
		}# More than
		elsif ($operator =~ />/) {
			
			if (int($result) > int($value)) {
				
				$eval_postcondition_result = 1;
				
			}			
		} else {
			
			logger("[ERROR] Unknown operator in postcondition, please revise your configuration file.");
			$eval_postcondition_result = 0;
			
		}	
	} # Non numeric result
	else {
		
		my $result_value = sprintf("%s", $result);
		my $string_value = sprintf("%s", $value);
		
		 # Equal to
		if ($operator =~ /==/) {
			
			if ($result_value =~ /$string_value/) {
				
				$eval_postcondition_result = 1;
				
			}
		}# Not equal to
		elsif ($operator =~ /!=/) {
			
			if ($result_value !~ /$string_value/) {
				
				$eval_postcondition_result = 1;
				
			}			
		}# Less than
		elsif ($operator =~ /</) {
			
			if ($result_value lt $string_value) {
				
				$eval_postcondition_result = 1;
				
			}			
		}# More than
		elsif ($operator =~ />/) {
			
			if ($result_value gt $string_value) {
				
				$eval_postcondition_result = 1;
				
			}			
		} else {
			
			logger("[ERROR] Unknown operator in postcondition, please revise your configuration file.");
			$eval_postcondition_result = 0;
			
		}			
	}
	
	return $eval_postcondition_result;
}

# ----------------------------------------------------------------------
# eval_postcondition_array (Array result, operator, value)
#
# This function eval postcondition and return a boolean if some of the 
# elements fulfill the condition
# ----------------------------------------------------------------------
sub eval_postcondition_array {
	# This works
	my $value = pop;
	my $operator = pop;
	my @result = @_;
	
	my $eval_postcondition_array_result = 0;
	my $return_postcondition_element = 0;
	
	# Eval all array
	foreach my $single_result (@result) {
		
		$return_postcondition_element = 0;
				
		$return_postcondition_element = eval_postcondition($single_result, $operator, $value);

		if ($return_postcondition_element) {
			$eval_postcondition_array_result = 1;
		}
	}

	return $eval_postcondition_array_result;
}

# ----------------------------------------------------------------------
# store_result_check(check_type, value)
#
# This function stores in a temporary file the last value of the check
# ----------------------------------------------------------------------
sub store_result_check($$) {
	my $type = $_[0];
	my $value = $_[1];
	
	# Compose the temp filename
	my $tmp_file = $plugin_setup{'conf_temp'} . '/mysql_check_' . $type . '.tmp';

	# Try to open temp file
	if (! open (TMP, "> $tmp_file")) {
		logger("[ERROR] Error opening temp file: $tmp_file for write last check value: $type, $value");
		return;
	}
	
	# Write las check value
	print TMP $value;	
	close (TMP);
	
	logger("[INFO] Saving last check value: $value of check: $type in file $tmp_file .");	
}

# ----------------------------------------------------------------------
# process_delta_data(check_type, value)
#
# This function calculates delta value for the current check
# ----------------------------------------------------------------------
sub process_delta_data($$) {
	my $type = $_[0];
	my $value = $_[1];
	my $buffer_line;
	
	# Compose the temp filename
	my $tmp_file = $plugin_setup{'conf_temp'} . '/mysql_check_' . $type . '.tmp';

	# Collect info from temp file
	# If it's not possible then return token for not print module
	if (! open (TMP, "< $tmp_file")) {
		return '::MYSQL _ NON EXEC';
	}
	
	while (<TMP>) {
		$buffer_line = parse_dosline ($_);
	}
	
	my $process_delta_result = int($value) - int($buffer_line);
	
	close (TMP);
	
	# If delta value is negative then reset check last value
	if ($process_delta_result < 0) {
		logger("[INFO] Reset last value of check_" . $type . " due to negative value: " . $process_delta_result);
		return '::MYSQL _ NON EXEC';
	}
	
	logger("[INFO] check_" . $type . " postprocessed by delta calculation: " . $process_delta_result);
	
	return $process_delta_result;
}

###############################################################################
###############################################################################
######################## MAIN PROGRAM CODE ####################################
###############################################################################
###############################################################################

# ----------------------------------------------------------------------
# Checks input parameter from command line (Conf file)
# ----------------------------------------------------------------------
my $log_init = 0;

# Load config file from command line
if ($#ARGV == -1){
	print "I need at least one parameter: Complete path to external configuration file \n";
	logger_begin("[ERROR] Path to configuration file it's needed");
	
	# Logfile is initiated
	$log_init = 1;
	
	exit 0;
}

# Check for conf file
if ( ! -f $archivo_cfg ) {
	printf "\n [ERROR] Cannot open configuration file at $archivo_cfg. \n\n";
	
	if ($log_init == 1) {
		logger("[ERROR] Cannot open configuration file at $archivo_cfg, please set permissions correctly.");
	} else {
		logger_begin("[ERROR] Cannot open configuration file at $archivo_cfg, please set permissions correctly.");
		$log_init = 1;
	}
	
	print_module("MySQL_plugin_error", "generic_proc", 0, "Cannot open configuration file at $archivo_cfg, please set permissions correctly.");
    exit 0;
}

# ----------------------------------------------------------------------
# Parse external configuration file
# ----------------------------------------------------------------------
load_external_setup ($archivo_cfg);

if ($log_init == 1){
	
	logger("[INFO] Parsing config file $archivo_cfg.");	
	
} else {
	
	logger_begin("[INFO] Parsing config file $archivo_cfg.");
	$log_init = 1;	
	
}	

parse_config;

=COMMENT
print Dumper(%plugin_setup);
print Dumper(@checks);

exit;
=cut

# ----------------------------------------------------------------------
# First check connection to MySQL
# ----------------------------------------------------------------------
my $result_connection = check_mysql_connection;
if ($result_connection == 0) {
	logger ("[ERROR] Connection to MySQL error, abort monitoring.");
	exit 0;
}

# ----------------------------------------------------------------------
# Process each check
# ----------------------------------------------------------------------
my $result_check;
my $module_type;
my $module_status;
my @result_check;
my $performance_parameter;
foreach (@checks) {
	
	# Don't asumme performance parameter
	$performance_parameter = 0;
	my $type = $_->{'type'};
	my $postcondition_op =  $_->{'postcondition_op'};
	my $postcondition_val = $_->{'postcondition_val'};
	my $postexecution = $_->{'postexecution'};
	my $check_status = $_->{'status'};
	my $check_show = $_->{'show'};
	my $return_type = $_->{'return_type'};
	my $check_name = $_->{'check_name'};
	
	$result_check = 0;
	# Process check (System parameters)
	if ($_->{'type'} eq 'mysql_service') {
		
		$result_check = check_mysql_service;
		
	} elsif ($_->{'type'} eq 'mysql_memory') {
		
		$result_check = check_mysql_memory;
		
	} elsif ($_->{'type'} eq 'mysql_cpu') {
		
		$result_check = check_mysql_cpu;
		
	} elsif ($_->{'type'} eq 'system_timewait') {
		
		$result_check = check_system_timewait;
		
	} elsif ($_->{'type'} eq 'system_diskusage') {	
			
		$result_check = check_system_diskusage;
							
	} elsif ($_->{'type'} eq 'mysql_ibdata1') {
		
		$result_check = check_mysql_ibdata1;
		
	} elsif ($_->{'type'} eq 'mysql_connection') {
		
		$result_check = check_mysql_connection;
		
	} elsif ($_->{'type'} eq 'mysql_logs') {
		
		@result_check = check_mysql_logs($_->{'module_type'});
		
	}
	# Process check (Perfomance parameters) 
	elsif ($_->{'type'} eq 'status') {
		
		# This is a performance parameter
		$performance_parameter = 1;
		$result_check = check_mysql_status($_->{"show"});
		
	} 
	# Process check (Open SQL interface)
	elsif ($_->{'type'} eq 'sql') {
		
		$performance_parameter = 1;		
		$result_check = check_mysql_status($_->{'type'}, $_->{"sql"}, $_->{'check_schema'});
	
	}
	# Process check (Query Innodb status)
	elsif ($_->{'type'} eq 'innodb') {
		
		$result_check = check_mysql_status($_->{'type'}, $_->{"query"});
				 
	}else {
		
		logger("[INFO] Check block with invalid type, discart it. Please revise your configuration file.");
		$result_check = 0;
		
	}
	
	# Use results
	if (($_->{'type'} ne 'unknown')) {
		
		# Prints module ('mysql_logs' check prints it's module inside check_mysql_logs function)
		if ($_->{'type'} ne 'mysql_logs') {
			
			# Evalue module type
			$module_type = $_->{'module_type'};

			 # 'mysql_service' and 'mysql_connection' has always generic_proc type
			if (($_->{'type'} eq 'mysql_service') or ($type eq 'mysql_connection')) {

				$module_type = 'generic_proc';
			
			}# By default type is generic_data 
			else {
				
				$module_type = 'generic_data';
			
			}
			
			my $last_result_check = $result_check;
			# If where are dealing with a performance parameter then look at return_type value
			if ($performance_parameter and defined($_->{'return_type'})) {
				
				if ($_->{'return_type'} eq 'data_delta') {
	
					$result_check = process_delta_data($_->{'type'} . '_' . $_->{'show'}, $result_check);
						
				}
				
			}
			
			# Store result_check in temp file for delta postprocess 
			if ($performance_parameter) {
				
				store_result_check($type . '_' . $check_show, $last_result_check);
			
			}				

		}
		
		 # Postcondition ('mysql_logs' type will have an array like result)
		my $exec_postexecution = 0;
		my $result_check_test = sprintf("%s", $result_check);
		if (($type eq 'mysql_logs') and ($result_check_test ne '::MYSQL _ NON EXEC')) {

#			if (defined($postcondition_op) and defined($postcondition_val)) {

#				$exec_postexecution = eval_postcondition_array(@result_check, $postcondition_op, $postcondition_val);
				
#			}

			# If data has been returned, exec postcommand
			if (@result_check) {
				$exec_postexecution = 1;
			}
			
		}# Postcondition (other check types) 
		elsif (defined($postcondition_op) and defined($postcondition_val) and ($result_check_test ne '::MYSQL _ NON EXEC')) {
			
			$exec_postexecution = eval_postcondition($result_check, $postcondition_op, $postcondition_val);
			
		}

		# If postcondition is not fulfilled then don't assign status to module
		if (!$exec_postexecution) {
			$check_status = 'NORMAL';
		}
		
		# Prints module ('mysql_logs' check prints it's module inside check_mysql_logs function)
		if ($type ne 'mysql_logs') {		
			if ($result_check_test ne '::MYSQL _ NON EXEC') { 
				# Prints module
				if ($type eq 'status') {
					print_module("MySQL_" . $type . '_' . $check_show, $module_type, $result_check, '', $check_status);
				} else {
					if (defined($check_name)) {
						print_module("MySQL_" . $type . "_" . $check_name, $module_type, $result_check, '', $check_status);					
					} else {
						print_module("MySQL_" . $type, $module_type, $result_check, '', $check_status);
					}
				}
			} else {
				logger("[INFO] First execution of delta value for check $type, ignoring.");
			}
		}	

		# Exec command
		if ($exec_postexecution) {
			
			if (defined($postexecution)) {
				
				logger("[INFO] Executing postexecution command: " . $postexecution);
				if ($postexecution =~ /\_DATA\_/i) {
					my $round_data = ceil ($result_check);

					logger("[INFO] Detected macro _DATA_ in postexecution command, replacing with: $result_check");
					$postexecution =~ s/\_DATA\_/$round_data/;
				}
				my $command_output = `$postexecution`;
				logger("[INFO] Postexecution command result: " . $command_output);
				
			}
		}		
		
	} # type ne 'unknown'
}
