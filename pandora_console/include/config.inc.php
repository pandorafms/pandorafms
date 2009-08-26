<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// 
// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2
//  
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package Include
 * @subpackage Config
 */

// Default values

// $config["dbname"]="pandora";
// $config["dbuser"]="pandora";
// $config["dbpass"]="pandora";
// $config["dbhost"]="localhost";

// This is used for reporting, please add "/" character at the end
// $config["homedir"]="/var/www/pandora_console/";
// $config["homeurl"]="/pandora_console/";

// $config["auth"]["scheme"] = "mysql";

///*************************** Start LDAP Config *****************************/
// Only use the following if you have LDAP. Unnecessary for built-in
//
//Name or address of the LDAP server
//  For SSL (not TLS) use 'ldaps://localhost'
//$config["auth"]["ldap_server"] = 'ldap://rcbi.rochester.edu';
//
//OPTIONAL: Port LDAP listens on (usually 389). Some configurations require you to specify this no matter what
//$config["auth"]["ldap_port"] = 389;
//
//OPTIONAL: Use TLS for the connection (not the same as ldaps://)
//$config["auth"]["ldap_start_tls"] = true;
//
//OPTIONAL: Protocol version to use to connect to your server (3 for most installations)
//$config["auth"]["ldap_version"] = 0;
//
// base DN to search for user information (full dn)
// This is based on Mac OS X OpenDirectory, change accordingly
//$config["auth"]["ldap_base_dn"] = 'cn=users,dc=rcbi,dc=rochester,dc=edu';
//
// The ldap attribute used to find a user (login).
// E.g., if you use cn,  your login might be "Jane Smith" -- untested!
//       if you use uid, your login might be "jsmith"
//$config["auth"]["ldap_login_attr"] = 'uid';
//
// OPTIONAL: Account used to connect (bind) to the server and SEARCH for information.
// This user must have the correct rights to perform search on objects
// By default the search will be made anonymous.
// *** We do NOT recommend storing the root LDAP account or any type of admin/living person info here ***
//$config["auth"]["ldap_admin_dn"] = '';  // user DN
//$config["auth"]["ldap_admin_pwd"] = ''; // user password
//
//------ Admin Group Settings ------//
//
// A group name (complete DN) to find users with admin rights
//$config["auth"]["ldap_admin_group_name"] = 'cn=pandora_admins,cn=groups,dc=rcbi,dc=rochester,dc=edu';
//
// What type of group do we want (posixgroup, groupofnames, groupofuniquenames)
//$config["auth"]["ldap_admin_group_type"] = 'posixgroup';
//
// The LDAP attribute used to store member of a group
//$config["auth"]["ldap_admin_group_attr"] = 'memberuid';
//
//------ LDAP Filter Settings ------//
//
// LDAP filter used to limit search results and login authentication
//$config["auth"]["ldap_user_filter"] = '(&(objectclass=person)(!(sn=99)))';
//
// Attributes to fetch from LDAP and corresponding user variables in the
// application. Do change according to your LDAP Schema
//$config["auth"]["ldap_user_attr"] = array (
//						 //Pandora attribute // LDAP attribute //Explanation
//						 'id_user' =>		'uid',          //login
//						 'lastname' =>      'sn',			//last (sur) name
//						 'firstname' =>     'givenname',    //first (given) name
//						 'fullname' =>		'cn',           //full (common) name
//						 'comments' =>		'description',	//comments - you can set this to anything
//						 'last_contact' =>  'lastlogin',	//last login utimestamp or don't define
//						 'email' =>			'mail',         //email - not necessary will default to empty
//						 'phone'  =>		'phone',		//phone
//						 'middlename' =>	'mn',			//not yet used except for representational purposes
//						 'registered' =>	'created'		//created utimestamp or don't define
//);
///* You can uncomment the following only if you understand what it implies
//
// $config["auth"]["create_user_undefined"] = false; //Create a user with minimal rights if the user is in your authentication scheme but not in Pandora
//*/
///*************************** End LDAP Config *****************************/

/**
 * Do not display any ERROR
 */
error_reporting(E_ALL); 



// Display ALL errors
// error_reporting(E_ERROR);

// This is directory where placed "/attachment" directory, to upload files stores. 
// This MUST be writtable by http server user, and should be in pandora root. 
// By default, Pandora adds /attachment to this, so by default is the pandora console home dir

include ("config_process.php");
?>
