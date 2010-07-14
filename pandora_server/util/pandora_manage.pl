#!/usr/bin/perl

###############################################################################
# Pandora FMS General Management Tool
###############################################################################
# Copyright (c) 2010 Artica Soluciones Tecnologicas S.L
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License version 2
###############################################################################

# Includes list
use strict;
use Time::Local;		# DateTime basic manipulation
use DBI;				# DB interface with MySQL
use POSIX qw(strftime);
use POSIX;

# Default lib dir for RPM and DEB packages
use lib '/usr/lib/perl5';

use PandoraFMS::Tools;
use PandoraFMS::DB;
use PandoraFMS::Core;
use PandoraFMS::Config;

# version: define current version
my $version = "3.1 PS100519";

# Parameter
my $param;

# Used to calculate the MD5 checksum of a string
use constant MOD232 => 2**32;

# Initialize MD5 variables
md5_init ();

# Pandora server configuration
my %conf;

# FLUSH in each IO
$| = 0;

# Init
pandora_manage_init(\%conf);

# Read config file
pandora_load_config (\%conf);

# Load enterprise module
if (enterprise_load (\%conf) == 0) {
	print "[*] Pandora FMS Enterprise module not available.\n\n";
} else {
	print "[*] Pandora FMS Enterprise module loaded.\n\n";
}

# Connect to the DB
my $dbh = db_connect ('mysql', $conf{'dbname'}, $conf{'dbhost'}, '3306', $conf{'dbuser'}, $conf{'dbpass'});
my $history_dbh = ($conf{'_history_db_enabled'} eq '1') ? db_connect ('mysql', $conf{'_history_db_name'},
		$conf{'_history_db_host'}, '3306', $conf{'_history_db_user'}, $conf{'_history_db_pass'}) : undef;

# Main
pandora_manage_main(\%conf, $dbh, $history_dbh);

# Cleanup and exit
db_disconnect ($history_dbh) if defined ($history_dbh);
db_disconnect ($dbh);
exit;

###############################################################################
###############################################################################
# GENERAL FUNCTIONS
###############################################################################
###############################################################################

###############################################################################
# Disable alert system globally
###############################################################################
sub pandora_disable_alerts ($$) {
	my ($conf, $dbh) = @_;

	# This works by disabling alerts in each defined group
    # If you have previously a group with alert disabled, and you disable 
    # alerts globally, when enabled it again, it will enabled also !

	db_do ($dbh, "UPDATE tgrupo SET disabled = 1");

    exit;
}

###############################################################################
# Enable alert system globally
###############################################################################
sub pandora_enable_alerts ($$) {
	my ($conf, $dbh) = @_;

	db_do ($dbh, "UPDATE tgrupo SET disabled = 0");

    exit;
}

###############################################################################
# Disable enterprise ACL
###############################################################################
sub pandora_disable_eacl ($$) {
	my ($conf, $dbh) = @_;

	db_do ($dbh, "UPDATE tconfig SET `value` ='0' WHERE `token` = 'acl_enterprise'");

    exit;
}

###############################################################################
# Enable enterprise ACL
###############################################################################
sub pandora_enable_eacl ($$) {
	my ($conf, $dbh) = @_;

    db_do ($dbh, "UPDATE tconfig SET `value` ='1' WHERE `token` = 'acl_enterprise'");
    	
    exit;
}

###############################################################################
# Disable a entire group
###############################################################################
sub pandora_disable_group ($$$) {
    my ($conf, $dbh, $group) = @_;

	if ($group == 0){
		db_do ($dbh, "UPDATE tagente SET disabled = 1");
	}
	else {
		db_do ($dbh, "UPDATE tagente SET disabled = 1 WHERE id_grupo = $group");
	}
    exit;
}

###############################################################################
# Enable a entire group
###############################################################################
sub pandora_enable_group ($$$) {
    my ($conf, $dbh, $group) = @_;

	if ($group == 0){
			db_do ($dbh, "UPDATE tagente SET disabled = 0");
	}
	else {  
			db_do ($dbh, "UPDATE tagente SET disabled = 0 WHERE id_grupo = $group");
	}
    exit;
}

##############################################################################
# Init screen
##############################################################################
sub pandora_manage_init ($) {
    my $conf = shift; 
    
    $conf->{"verbosity"}=0;	# Verbose 1 by default
	$conf->{"daemon"}=0;	# Daemon 0 by default
	$conf->{'PID'}="";	# PID file not exist by default
	$conf->{"quiet"}=0;	# Daemon 0 by default
   

	print "\nPandora FMS Manage tool $version Copyright (c) 2010 Artica ST\n";
	print "This program is Free Software, licensed under the terms of GPL License v2\n";
	print "You can download latest versions and documentation at http://www.pandorafms.org\n\n";

	# Load config file from command line
	help_screen () if ($#ARGV < 0);
   
        $conf->{'pandora_path'} = $ARGV[0];

	help_screen () if ($conf->{'pandora_path'} =~ m/--*h\w*\z/i );
}

##########################################################################
## Delete a module given its id.
##########################################################################
sub pandora_delete_module ($$) {
        my ($dbh, $module_id) = @_;

        # Delete the module
        db_do ($dbh, 'DELETE FROM tagente_modulo WHERE id_agente_modulo = ?', $module_id);
        
        # Delete the module state
        db_do ($dbh, 'DELETE FROM tagente_estado WHERE id_agente_modulo = ?', $module_id);
        
        # Delete templates asociated to the module
        db_do ($dbh, 'DELETE FROM talert_template_modules WHERE id_agent_module = ?', $module_id);
}

##########################################################################
## Delete an agent given its id.
##########################################################################
sub pandora_delete_agent ($$) {
        my ($dbh, $agent_id) = @_;

        # Delete the agent
        db_do ($dbh, 'DELETE FROM tagente WHERE id_agente = ?', $agent_id);
        
        # Delete agent access data
        db_do ($dbh, 'DELETE FROM tagent_access WHERE id_agent = ?', $agent_id);
        
        # Delete addresses
        db_do ($dbh, 'DELETE FROM taddress_agent WHERE id_ag = ?', $agent_id);
        
        my @modules = get_db_rows ($dbh, 'SELECT * FROM tagente_modulo WHERE id_agente = ?', $agent_id);
        
        foreach my $module (@modules) {
			pandora_delete_module ($dbh, $module->{'id_agente_modulo'});
        }
}

##########################################################################
## Create a template module.
##########################################################################
sub pandora_create_template_module ($$$) {
my ($dbh, $module_id, $template_id) = @_;

return db_insert ($dbh, 'INSERT INTO talert_template_modules (id_agent_module, id_alert_template) VALUES (?, ?)', $module_id, $template_id);
}

##########################################################################
## Delete a template module.
##########################################################################
sub pandora_delete_template_module ($$) {
	my ($dbh, $template_module_id) = @_;

	# Delete the template module
	db_do ($dbh, 'DELETE FROM talert_template_modules WHERE id = ?', $template_module_id);
	
	# 
	db_do ($dbh, 'DELETE FROM talert_template_module_actions WHERE id_alert_template_module = ?', $template_module_id);
}

##########################################################################
## Asociate a module to a template.
##########################################################################
sub pandora_delete_template_module_action ($$$) {
        my ($dbh, $module_id, $template_id, $action_id) = @_;

        my $template_module_id = get_template_module_id ($dbh, $module_id, $template_id);
        return -1 if ($template_module_id == -1);

        return db_do ($dbh, 'DELETE FROM talert_template_module_actions WHERE id_alert_template_module = ? AND id_alert_action = ?', $template_module_id, $action_id);
}

##########################################################################
## Create a user.
##########################################################################
sub pandora_create_user ($$$$$) {
my ($dbh, $name, $password, $is_admin, $comments) = @_;

return db_insert ($dbh, 'INSERT INTO tusuario (id_user, fullname, password, comments, is_admin)
                         VALUES (?, ?, ?, ?, ?)', $name, $name, $password, $comments, $is_admin);
}

##########################################################################
## Delete a user.
##########################################################################
sub pandora_delete_user ($$) {
my ($dbh, $name) = @_;

# Delete user profiles
db_do ($dbh, 'DELETE FROM tusuario_perfil WHERE id_usuario = ?', $name);

# Delete the user
my $return = db_do ($dbh, 'DELETE FROM tusuario WHERE id_user = ?', $name);

if($return eq '0E0') {
	return -1;
}
else {
	return 0;
}
}

##########################################################################
## Asociate a module to a template.
##########################################################################
sub pandora_create_template_module_action ($$$$$) {
        my ($dbh, $template_module_id, $action_id, $fires_min, $fires_max) = @_;
        
        return db_insert ($dbh, 'INSERT INTO talert_template_module_actions (id_alert_template_module, id_alert_action, fires_min, fires_max) VALUES (?, ?, ?, ?)', $template_module_id, $action_id, $fires_min, $fires_max);
}

##########################################################################
## Assign a profile to the given user/group.
##########################################################################
sub pandora_create_user_profile ($$$$) {
        my ($dbh, $user_id, $profile_id, $group_id) = @_;
        
        return db_insert ($dbh, 'INSERT INTO tusuario_perfil (id_usuario, id_perfil, id_grupo) VALUES (?, ?, ?)', $user_id, $profile_id, $group_id);
}

##########################################################################
## Delete a profile from the given user/group.
##########################################################################
sub pandora_delete_user_profile ($$$$) {
        my ($dbh, $user_id, $profile_id, $group_id) = @_;
        
        return db_do ($dbh, 'DELETE FROM tusuario_perfil WHERE id_usuario=? AND id_perfil=? AND id_grupo=?', $user_id, $profile_id, $group_id);
}

##########################################################################
## Create a network module
##########################################################################
sub pandora_create_network_module ($$$$$$$$$$$$$$$$$) {
	my ($pa_config, $agent_id, $module_type_id, $module_name, $max,
		$min, $post_process, $description, $interval, $warning_min, 
		$warning_max, $critical_min, $critical_max, $history_data, 
		$module_address, $module_port, $dbh) = @_;
 
 	logger($pa_config, "Creating module '$module_name' for agent ID $agent_id.", 10);
 
	# Provide some default values	
	$warning_min = 0 if ($warning_min eq '');
	$warning_max = 0 if ($warning_max eq '');
	$critical_min = 0 if ($critical_min eq ''); 
	$critical_max = 0 if ($critical_max eq ''); 
	$history_data = 0 if ($history_data eq '');
	$max = 0 if ($max eq '');
	$min = 0 if ($min eq '');
	$post_process = 0 if ($post_process eq '');
	$description = 'N/A' if ($description eq '');

	my $module_id = db_insert($dbh, 'INSERT INTO tagente_modulo (`id_agente`, `id_tipo_modulo`, `nombre`, `max`, `min`, `post_process`, `descripcion`, `module_interval`, `min_warning`, `max_warning`, `min_critical`, `max_critical`, `history_data`, `id_modulo`)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)', $agent_id, $module_type_id, $module_name, $max, $min, $post_process, $description, $interval, $warning_min, $warning_max, $critical_min, $critical_max, $history_data);
	db_do ($dbh, 'INSERT INTO tagente_estado (`id_agente_modulo`, `id_agente`, `last_try`) VALUES (?, ?, \'0000-00-00 00:00:00\')', $module_id, $agent_id);
	return $module_id;
}

##########################################################################
# Validate event.
# This validates all events pending to ACK for the same id_agent_module
##########################################################################
sub pandora_validate_event_filter ($$$$$$$$$) {
	my ($pa_config, $id_agentmodule, $id_agent, $timestamp_min, $timestamp_max, $id_user, $id_alert_agent_module, $criticity, $dbh) = @_;
	my $filter = '';
		
	if ($id_agentmodule ne ''){
		$filter .= " AND id_agentmodule = $id_agentmodule";
	}
	if ($id_agent ne ''){
		$filter .= " AND id_agente = $id_agent";
	}
	if ($timestamp_min ne ''){
		$filter .= " AND timestamp >= '$timestamp_min'";
	}
	if ($timestamp_max ne ''){
		$filter .= " AND timestamp <= '$timestamp_max'";
	}
	if ($id_user ne ''){
		$filter .= " AND id_usuario = '$id_user'";
	}
	
	if ($id_alert_agent_module ne ''){
		$filter .= " AND id_alert_am = $id_alert_agent_module";
	}	
	
	if ($criticity ne ''){
		$filter .= " AND criticity = $criticity";
	}

	logger($pa_config, "Validating events", 10);
	db_do ($dbh, "UPDATE tevento SET estado = 1 WHERE estado = 0".$filter);
}


###############################################################################
###############################################################################
# PRINT HELP AND CHECK ERRORS FUNCTIONS
###############################################################################
###############################################################################

###############################################################################
# Print a parameter error and exit the program.
###############################################################################
sub param_error ($$) {
    print (STDERR "[ERROR] Parameters error: $_[1] received | $_[0] necessary.\n\n");
    
    help_screen ();
    exit 1;
}

###############################################################################
# Print a 'not exists' error and exit the program.
###############################################################################
sub notexists_error ($$) {
    print (STDERR "[ERROR] Error: The $_[0] '$_[1]' not exists.\n\n");
    exit 1;
}

###############################################################################
# Check the return of 'get id' and call the error if its equal to -1.
###############################################################################
sub exist_check ($$$) {
    if($_[0] == -1) {
		notexists_error($_[1],$_[2]);
	}
}

###############################################################################
# Check the parameters.
# Param 0: # of received parameters
# Param 1: # of necessary parameters
# Param 2: # of optional parameters
###############################################################################
sub param_check ($$;$) {
	my ($ltotal, $lneed, $lopt) = @_;
	$ltotal = $ltotal - 1;
	
	if(!defined($lopt)){
		$lopt = 0;
	}

	if( $ltotal < $lneed - $lopt || $ltotal > $lneed) {
		if( $lopt == 0 ) {
			param_error ($lneed, $ltotal);
		}
		else {
			param_error (($lneed-$lopt)."-".$lneed, $ltotal);
		}
	}
}

##############################################################################
# Print a help line.
##############################################################################
sub help_screen_line($$$){
	my ($option, $parameters, $help) = @_;
	print "\t$option $parameters\t$help.\n" unless ($param ne '' && $param ne $option);
}

##############################################################################
# Print a help screen and exit.
##############################################################################
sub help_screen{
	print "Usage: $0 <path to pandora_server.conf> [options] \n\n" unless $param ne '';
	print "Available options:\n\n" unless $param ne '';
	print "Available options for $param:\n\n" unless $param eq '';
	help_screen_line('--disable_alerts', '', 'Disable alerts in all groups');
	help_screen_line('--enable_alerts', '', 'Enable alerts in all groups');
	help_screen_line('--disable_eacl', '', 'Disable enterprise ACL system');
	help_screen_line('--enable_eacl', '', 'Enable enterprise ACL system');
	help_screen_line('--disable_group', '<group_name>', 'Disable agents from an entire group');
   	help_screen_line('--enable_group', '<group_name>', 'Enable agents from an entire group');
   	help_screen_line('--create_agent', '<agent_name> <operating_system> <group> <server_name> [<address> <description> <interval>]', 'Create agent');
	help_screen_line('--delete_agent', '<agent_name>', 'Delete agent');
	help_screen_line('--create_module', '<module_name> <module_type> <agent_name> <module_address> [<module_port> <description> <min> <max> <post_process> <interval> <warning_min> <warning_max> <critical_min> <critical_max> <history_data>]', 'Add module to agent');
    help_screen_line('--delete_module', 'Delete module from agent', '<module_name> <agent_name>');
    help_screen_line('--create_template_module', '<template_name> <module_name> <agent_name>', 'Add alert template to module');
    help_screen_line('--delete_template_module', '<template_name> <module_name> <agent_name>', 'Delete alert template from module');
    help_screen_line('--create_template_action', '<action_name> <template_name> <module_name> <agent_name> [<fires_min> <fires_max>]', 'Add alert action to module-template');
    help_screen_line('--delete_template_action', '<action_name> <template_name> <module_name> <agent_name>', 'Delete alert action from module-template');
    help_screen_line('--data_module', '<server_name> <agent_name> <module_name> <module_type> [<datetime>]', 'Insert data to module');
    help_screen_line('--create_user', '<user_name> <user_password> <is_admin> [<comments>]', 'Create user');
    help_screen_line('--delete_user', '<user_name>', 'Delete user');
    help_screen_line('--create_profile', '<user_name> <profile_name> <group_name>', 'Add perfil to user');
    help_screen_line('--delete_profile', '<user_name> <profile_name> <group_name>', 'Delete perfil from user');
    help_screen_line('--create_event', '<event> <event_type> <agent_name> <module_name> <group_name> [<event_status> <severity> <template_name>]', 'Add event');
    help_screen_line('--validate_event', '<agent_name> <module_name> <datetime_min> <datetime_max> <user_name> <criticity> <template_name>', 'Validate events');
    help_screen_line('--create_incident', '<title> <description> <origin> <status> <priority 0 for Informative, 1 for Low, 2 for Medium, 3 for Serious, 4 for Very serious or 5 for Maintenance> <group> [<owner>]', 'Create incidents');
    print "\n";
	exit;
}

###############################################################################
###############################################################################
# UTILITY FUNCTIONS
###############################################################################
###############################################################################

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
# MD5 leftrotate function. See http://en.wikipedia.org/wiki/MD5#Pseudocode.
###############################################################################
sub leftrotate ($$) {
	my ($x, $c) = @_;

	return (0xFFFFFFFF & ($x << $c)) | ($x >> (32 - $c));
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


##########################################################################
## Convert a date (yyy-mm-ddThh:ii:ss) to Timestamp.
##########################################################################
sub dateTimeToTimestamp {
        $_[0] =~ /(\d{4})-(\d{2})-(\d{2})([ |T])(\d{2}):(\d{2}):(\d{2})/;
        my($year, $mon, $day, $GMT, $hour, $min, $sec) = ($1, $2, $3, $4, $5, $6, $7);
        #UTC
        return timegm($sec, $min, $hour, $day, $mon - 1, $year - 1900);
        #BST
        #print "BST\t" . mktime($sec, $min, $hour, $day, $mon - 1, $year - 1900, 0, 0) . "\n";
}

###############################################################################
###############################################################################
# MAIN
###############################################################################
###############################################################################

sub pandora_manage_main ($$$) {
	my ($conf, $dbh, $history_dbh) = @_;

	my @args = @ARGV;
 	my $ltotal=$#args; 
	my $ax;

 	# Has read setup file ok ?
 	if ( $ltotal == 0 ) {
		print "[ERROR] No valid arguments\n\n";
		help_screen();
		exit;
 	}
	else {
		$param = $args[1];

		# help!
		if ($param =~ m/--*h\w*\z/i ) {
			$param = '';
			help_screen () ;
			exit;
		}
		elsif ($param =~ m/--disable_alerts\z/i) {
			print "[INFO] Disabling all alerts \n\n";
	        pandora_disable_alerts ($conf, $dbh);
	    }
		elsif ($param =~ m/--enable_alerts\z/i) {
			print "[INFO] Enabling all alerts \n\n";
	        pandora_enable_alerts ($conf, $dbh);
		} 
		elsif ($param =~ m/--disable_eacl\z/i) {
			print "[INFO] Disabling Enterprise ACL system (system wide)\n\n";
	        pandora_disable_eacl ($conf, $dbh);
		} 
		elsif ($param =~ m/--enable_eacl\z/i) {
			print "[INFO] Enabling Enterprise ACL system (system wide)\n\n";
            pandora_enable_eacl ($conf, $dbh);
		} 
        elsif ($param =~ m/--disable_group/i) {
			param_check($ltotal, 1);
			my $group_name = @ARGV[2];
			my $id_group;
			
			if($group_name eq "All") {
				print "[INFO] Disabling all groups\n\n";
				$id_group = 0;
			}
			else {
				$id_group = get_group_id($dbh, $args[2]);
				exist_check($id_group,'group',$group_name);
				print "[INFO] Disabling group '$group_name'\n\n";
			}
			
			pandora_disable_group ($conf, $dbh, $id_group);
		}
		elsif ($param =~ m/--enable_group/i) {
			param_check($ltotal, 1);
			my $group_name = @ARGV[2];
			my $id_group;
			
			if($group_name eq "All") {
				$id_group = 0;
				print "[INFO] Enabling all groups\n\n";
			}
			else {
				$id_group = get_group_id($dbh, $args[2]);
				exist_check($id_group,'group',$group_name);
				print "[INFO] Enabling group '$group_name'\n\n";
			}
			
			pandora_enable_group ($conf, $dbh, $id_group);
		}
		elsif ($param =~ m/--create_agent/i) {
			param_check($ltotal, 7, 3);
			my ($agent_name,$os_name,$group_name,$server_name,$address,$description,$interval) = @ARGV[2..8];
			print "[INFO] Creating agent '$agent_name'\n\n";
			
			$address = '' unless defined ($address);
			$description = '' unless defined ($description);
			$interval = 300 unless defined ($interval);
			
			my $id_group = get_group_id($dbh,$group_name);
			exist_check($id_group,'group',$group_name);
			my $os_id = get_os_id($dbh,$os_name);
			exist_check($id_group,'operating system',$group_name);
			pandora_create_agent ($conf, $server_name, $agent_name, $address, $id_group, 0, $os_id, $description, $interval, $dbh);
		}
		elsif ($param =~ m/--delete_agent/i) {
			param_check($ltotal, 1);
			my $agent_name = @ARGV[2];
			print "[INFO] Deleting agent '$agent_name'\n\n";
			
			my $id_agent = get_agent_id($dbh,$agent_name);
			exist_check($id_agent,'agent',$agent_name);
			
			pandora_delete_agent($dbh,$id_agent);
		}
		elsif ($param =~ m/--create_module/i) {
			param_check($ltotal, 15, 11);
			my ($module_name, $module_type, $agent_name, $module_address, $module_port, $description,
			$min,$max,$post_process, $interval, $warning_min, $warning_max, $critical_min,
			$critical_max, $history_data) = @ARGV[2..9];
			
			print "[INFO] Adding module '$module_name' to agent '$agent_name'\n\n";
				
			# The get_module_id has wrong name. Change in future
			my $module_type_id = get_module_id($dbh,$module_type);
			exist_check($module_type_id,'module type',$module_type);
			my $agent_id = get_agent_id($dbh,$agent_name);
			exist_check($agent_id,'agent',$agent_name);
			
			if ($module_type !~ m/.?icmp.?/) {
			  	if (not defined($module_port)) {
					print "[ERROR] Port error. Agents of type distinct of icmp need port\n\n";
					exit;
				}
			  	if ($module_port > 65535 || $module_port < 1) {
					print "[ERROR] Port error. Port must into [1-65535]\n\n";
					exit;
				}
			}
			
			$warning_min = 0 unless defined ($warning_min);
			$warning_max = 0 unless defined ($warning_max);
			$critical_min = 0 unless defined ($critical_min);
			$critical_max = 0 unless defined ($critical_max);
			$history_data = 0 unless defined ($history_data); 
			$module_port = '' unless defined ($module_port);
			$description = '' unless defined ($description);
			$min = 0 unless defined ($min);
			$max = 0 unless defined ($max);
			$post_process = 0 unless defined ($post_process);
			$interval = 300 unless defined ($interval);
			
			pandora_create_network_module ($conf, $agent_id, $module_type_id, $module_name, 
			$max, $min, $post_process, $description, $interval, $warning_min, 
			$warning_max, $critical_min, $critical_max, $history_data, 
			$module_address, $module_port, $dbh);
		}
		elsif ($param =~ m/--delete_module/i) {
			param_check($ltotal, 2);
			my ($module_name,$agent_name) = @ARGV[2..3];
			print "[INFO] Deleting module '$module_name' from agent '$agent_name' \n\n";
			
			my $id_agent = get_agent_id($dbh,$agent_name);
			exist_check($id_agent,'agent',$agent_name);
			my $id_module = get_agent_module_id($dbh,$module_name,$id_agent);
			exist_check($id_module,'module',$module_name);
			
			pandora_delete_module($dbh,$id_module);
		}
		elsif ($param =~ m/--create_template_module/i) {
			param_check($ltotal, 3);
			my ($template_name,$module_name,$agent_name) = @ARGV[2..4];
			print "[INFO] Adding template '$template_name' to module '$module_name' from agent '$agent_name' \n\n";
			
			my $id_agent = get_agent_id($dbh,$agent_name);
			exist_check($id_agent,'agent',$agent_name);
			my $module_id = get_agent_module_id($dbh,$module_name,$id_agent);
			exist_check($module_id,'module',$module_name);
			my $template_id = get_template_id($dbh,$template_name);
			exist_check($template_id,'template',$template_name);
				
			pandora_create_template_module ($dbh, $module_id, $template_id);
		}
		elsif ($param =~ m/--delete_template_module/i) {
			param_check($ltotal, 3);
			my ($template_name,$module_name,$agent_name) = @ARGV[2..4];
			print "[INFO] Delete template '$template_name' from module '$module_name' from agent '$agent_name' \n\n";
			
			my $id_agent = get_agent_id($dbh,$agent_name);
			exist_check($id_agent,'agent',$agent_name);
			my $module_id = get_agent_module_id($dbh,$module_name,$id_agent);
			exist_check($module_id,'module',$module_name);
			my $template_id = get_template_id($dbh,$template_name);
			exist_check($template_id,'template',$template_name);
			
			my $template_module_id = get_template_module_id($dbh, $module_id, $template_id);
			exist_check($template_module_id,"template '$template_name' on module",$module_name);
				
			pandora_delete_template_module ($dbh, $template_module_id);
		}
		elsif ($param =~ m/--create_template_action/i) {
			param_check($ltotal, 6, 2);
			my ($action_name,$template_name,$module_name,$agent_name,$fires_min,$fires_max) = @ARGV[2..7];
			print "[INFO] Adding action '$action_name' to template '$template_name' in module '$module_name' from agent '$agent_name' with $fires_min min. fires and $fires_max max. fires\n\n";
			
			my $id_agent = get_agent_id($dbh,$agent_name);
			exist_check($id_agent,'agent',$agent_name);
			my $module_id = get_agent_module_id($dbh,$module_name,$id_agent);
			exist_check($module_id,'module',$module_name);
			my $template_id = get_template_id($dbh,$template_name);
			exist_check($template_id,'template',$template_name);
			my $template_module_id = get_template_module_id($dbh,$module_id,$template_id);
			exist_check($template_module_id,'template module',$template_name);
			my $action_id = get_action_id($dbh,$action_name);
			exist_check($action_id,'action',$action_name);
			
			$fires_min = 0 unless defined ($fires_min);
			$fires_max = 0 unless defined ($fires_max);
									
			pandora_create_template_module_action ($dbh, $template_module_id, $action_id, $fires_min, $fires_max);
		}
		elsif ($param =~ m/--delete_template_action/i) {
			param_check($ltotal, 4);
			my ($action_name,$template_name,$module_name,$agent_name) = @ARGV[2..5];
			print "[INFO] Deleting action '$action_name' from template '$template_name' in module '$module_name' from agent '$agent_name')\n\n";
		
			my $id_agent = get_agent_id($dbh,$agent_name);
			exist_check($id_agent,'agent',$agent_name);
			my $module_id = get_agent_module_id($dbh,$module_name,$id_agent);
			exist_check($module_id,'module',$module_name);
			my $template_id = get_template_id($dbh,$template_name);
			exist_check($template_id,'template',$template_name);
			my $template_module_id = get_template_module_id($dbh,$module_id,$template_id);
			exist_check($template_module_id,'template module',$template_name);
			my $action_id = get_action_id($dbh,$action_name);
			exist_check($action_id,'action',$action_name);
		
			pandora_delete_template_module_action ($dbh, $template_module_id, $action_id);
		}
		elsif ($param =~ m/--data_module/i) {
			param_check($ltotal, 5, 1);
			my ($server_name,$agent_name,$module_name,$module_type,$datetime) = @ARGV[2..6];
			my $utimestamp;
			
			if(defined($datetime)) {
				if ($datetime !~ /([0-9]{2,4})\-([0-1][0-9])\-([0-3][0-9]) +([0-2][0-9]):([0-5][0-9])/) {
					print "[ERROR] Invalid datetime $datetime. (Correct format: YYYY-MM-DD HH:mm)\n";
					exit;
				}
				# Add the seconds
				$datetime .= ":00";
				$utimestamp = dateTimeToTimestamp($datetime);
			}
			else {
				$utimestamp = time();
			}

			# The get_module_id has wrong name. Change in future
			my $module_type_id = get_module_id($dbh,$module_type);
			exist_check($module_type_id,'module type',$module_type);
			
			my $id_agent = get_agent_id($dbh,$agent_name);
			exist_check($id_agent,'agent',$agent_name);
			
			# Server_type 0 is dataserver
			my $server_id = get_server_id($dbh,$server_name,0);
			exist_check($server_id,'data server',$server_name);
			
			my $module = get_db_single_row ($dbh, 'SELECT * FROM tagente_modulo WHERE id_agente = ? AND id_tipo_modulo = ?', $id_agent, $module_type_id);

			if(not defined($module->{'module_interval'})) {
				print "[ERROR] No module data finded. \n\n";
				exit;
			}

			my %data = ('data' => 1);
			pandora_process_module ($conf, \%data, '', $module, $module_type, '', $utimestamp, $server_id, $dbh);
			
			print "[INFO] Inserting data to module '$module_name'\n\n";
		}
		elsif ($param =~ m/--create_user/i) {
			param_check($ltotal, 4, 1);
			my ($user_name,$password,$is_admin,$comments) = @ARGV[2..5];
						
			$comments = '' unless defined ($comments);
			
			print "[INFO] Creating user '$user_name'\n\n";
			
			pandora_create_user ($dbh, $user_name, md5($password), $is_admin, $comments);
		}
		elsif ($param =~ m/--delete_user/i) {
			param_check($ltotal, 1);
			my $user_name = @ARGV[2];
			print "[INFO] Deleting user '$user_name' \n\n";
			
			my $result = pandora_delete_user($dbh,$user_name);
			exist_check($result,'user',$user_name);
		}
		elsif ($param =~ m/--create_profile/i) {
			param_check($ltotal, 3);
			my ($user_name,$profile_name,$group_name) = @ARGV[2..4];
			
			my $id_profile = get_profile_id($dbh,$profile_name);
			exist_check($id_profile,'profile',$profile_name);
			
			my $id_group;
			
			if($group_name eq "All") {
				$id_group = 0;
				print "[INFO] Adding profile '$profile_name' to all groups for user '$user_name') \n\n";
			}
			else {
				$id_group = get_group_id($dbh,$group_name);
				exist_check($id_group,'group',$group_name);
				print "[INFO] Adding profile '$profile_name' to group '$group_name' for user '$user_name') \n\n";
			}
			
			pandora_create_user_profile ($dbh, $user_name, $id_profile, $id_group);
		}
		elsif ($param =~ m/--delete_profile/i) {
			param_check($ltotal, 3);
			my ($user_name,$profile_name,$group_name) = @ARGV[2..4];
			
			my $id_profile = get_profile_id($dbh,$profile_name);
			exist_check($id_profile,'profile',$profile_name);
			
			my $id_group;
			
			if($group_name eq "All") {
				$id_group = 0;
				print "[INFO] Deleting profile '$profile_name' from all groups for user '$user_name') \n\n";
			}
			else {
				$id_group = get_group_id($dbh,$group_name);
				exist_check($id_group,'group',$group_name);
				print "[INFO] Deleting profile '$profile_name' from group '$group_name' for user '$user_name') \n\n";
			}
			
			pandora_delete_user_profile ($dbh, $user_name, $id_profile, $id_group);
		}
		elsif ($param =~ m/--create_event/i) {
			param_check($ltotal, 8, 3);
			my ($event,$event_type,$agent_name,$module_name,$group_name,$event_status,$severity,$template_name) = @ARGV[2..9];
			
			$event_status = 0 unless defined($event_status);
			$severity = 0 unless defined($severity);
			
			my $id_group;
			
			if (!defined($group_name) || $group_name == "All") {
				$id_group = 0;
			}
			else {
				$id_group = get_group_id($dbh,$group_name);
				exist_check($id_group,'group',$group_name);
			}
			
			my $id_agent = get_agent_id($dbh,$agent_name);
			exist_check($id_agent,'agent',$agent_name);
			my $id_agentmodule = get_agent_module_id($dbh,$module_name,$id_agent);
			exist_check($id_agentmodule,'module',$module_name);
			
			my $id_alert_agent_module;
						
			if(defined($template_name)) {
				my $id_template = get_template_id($dbh,$template_name);
				exist_check($id_template,'template',$template_name);
				$id_alert_agent_module = get_template_module_id($dbh,$id_agentmodule,$id_template);
				exist_check($id_alert_agent_module,'template module',$template_name);
			}
			else {
				$id_alert_agent_module = 0;
			}
			
			print "[INFO] Adding event '$event' for agent '$agent_name' \n\n";

			pandora_event ($conf, $event, $id_group, $id_agent, $severity,
		$id_alert_agent_module, $id_agentmodule, $event_type, $event_status, $dbh);
		}
		elsif ($param =~ m/--validate_event/i) {
			param_check($ltotal, 7, 6);
			my ($agent_name, $module_name, $datetime_min, $datetime_max, $user_name, $criticity, $template_name) = @ARGV[2..8];
			
			my $id_agent = '';
			my $id_agentmodule = '';

						
			if(defined($agent_name) && $agent_name ne '') {
				$id_agent = get_agent_id($dbh,$agent_name);
				exist_check($id_agent,'agent',$agent_name);
				
				if($module_name ne '') {
					$id_agentmodule = get_agent_module_id($dbh, $module_name, $id_agent);
					exist_check($id_agentmodule,'module',$module_name);
				}
			}

			if(defined($datetime_min) && $datetime_min ne '') {
				if ($datetime_min !~ /([0-9]{2,4})\-([0-1][0-9])\-([0-3][0-9]) +([0-2][0-9]):([0-5][0-9])/) {
					print "[ERROR] Invalid datetime_min format. (Correct format: YYYY-MM-DD HH:mm)\n";
					exit;
				}
				# Add the seconds
				$datetime_min .= ":00";
			}
			
			if(defined($datetime_max) && $datetime_max ne '') {
				if ($datetime_max !~ /([0-9]{2,4})\-([0-1][0-9])\-([0-3][0-9]) +([0-2][0-9]):([0-5][0-9])/) {
					print "[ERROR] Invalid datetime_max $datetime_max. (Correct format: YYYY-MM-DD HH:mm)\n";
					exit;
				}
				# Add the seconds
				$datetime_max .= ":00";
			}

			my $id_alert_agent_module = '';
			
			if(defined($template_name) && $template_name ne '') {
				my $id_template = get_template_id($dbh,$template_name);
				exist_check($id_template,'template',$template_name);
				$id_alert_agent_module = get_template_module_id($dbh,$id_agentmodule,$id_template);
				exist_check($id_alert_agent_module,'template module',$template_name);
			}
						
			pandora_validate_event_filter ($conf, $id_agentmodule, $id_agent, $datetime_min, $datetime_max, $user_name, $id_alert_agent_module, $criticity, $dbh);
			print "[INFO] Validating event for agent '$agent_name'\n\n";
		}
		elsif ($param =~ m/--create_incident/i) {
			param_check($ltotal, 7, 1);
			my ($title, $description, $origin, $status, $priority, $group_name, $owner) = @ARGV[2..8];
						
			my $id_group = get_group_id($dbh,$group_name);
			exist_check($id_group,'group',$group_name);
						
			pandora_create_incident ($conf, $dbh, $title, $description, $priority, $status, $origin, $id_group, $owner);
			print "[INFO] Creating incident '$title'\n\n";
		}
	}

     print "[W] Nothing to do. Exiting !\n\n";

    exit;
}
