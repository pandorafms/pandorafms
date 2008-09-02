<?php


/* Change to E_ALL for development/debugging */
error_reporting (E_ALL);

/* Database backend, not really tested with other backends, so it's 
 not functional right now */
define ('DB_BACKEND', 'mysql');
define ('FREE_USER', 'PANDORA-FREE');

if (! extension_loaded ('mysql'))
	die ('Your PHP installation appears to be missing the MySQL extension which is required.');

require_once ('lib/libupdate_manager.php');

function get_user_key ($settings) {
	if ($settings->customer_key != FREE_USER) {
		if (! file_exists ($settings->keygen_path)) {
			echo '<h3 class="err">';
			echo __('Keygen file does not exists');
			echo '</h3>';
			
			return '';
		}
		if (! is_executable ($settings->keygen_path)) {
			echo '<h3 class="err">';
			echo __('Keygen file is not executable');
			echo '</h3>';
			
			return '';
		}
		
		global $config;
		
		$user_key = exec (escapeshellcmd ($settings->keygen_path.
				' '.$settings->customer_key.' '.$config['dbhost'].
				' '.$config['dbuser'].' '.$config['dbpass'].
				' '.$config['dbname']));
		
		return $user_key;
	}
	
	/* Free users.
	   We only want to know this for statistics records.
	   Feel free to disable this extension if you want.
	 */
	$n = (int) get_db_value ('COUNT(`id_agente`)', 'tagente', 'disabled', 0);
	$m = (int) get_db_value ('COUNT(`id_agente_modulo`)', 'tagente_modulo',
				'disabled', 0);
	$user_key = array ('A' => $n, 'M' => $m);
	
	return json_encode ($user_key);
}

flush ();
?>
