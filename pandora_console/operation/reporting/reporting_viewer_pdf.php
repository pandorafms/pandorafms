<?PHP

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development and project architecture and management
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas, info@artica.es
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.


require ("../../include/config.php");
require_once ("../../include/functions.php");
require_once ("../../include/functions_db.php");
require_once ("../../include/languages/language_".$config["language"].".php");
require_once ("../../include/functions_reporting_pdf.php");

if (!isset($_SESSION["id_usuario"])){
    session_start();
    session_write_close();
}

$config ["id_user"] = $_SESSION["id_usuario"];

// Session check
check_login ();

// Login check
$id_user=$_SESSION["id_usuario"];
global $REMOTE_ADDR;

if (comprueba_login() != 0) {
	audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access graph builder");
	include ("general/noaccess.php");
	exit;
}

if ((give_acl($id_user,0,"AR") != 1 ) AND (dame_admin($id_user)!=1)) {
	audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access graph builder");
	include ("general/noaccess.php");
	exit;
}


$id_report = give_parameter_get ( 'id', $default = "");
if ($id_report == ""){
	audit_db($id_user,$REMOTE_ADDR, "HACK Attempt","Trying to access graph viewer withoud ID");
	include ("general/noaccess.php");
	exit;
}

$report_private= give_db_value ("private", "treport", "id_report", $id_report);
$report_user = give_db_value ("id_user", "treport", "id_report", $id_report);

if (($report_user == $id_user) OR (dame_admin($id_user)==1) OR ($report_private == 0)) {
    // Without report type parameter: ABORT
    if (isset($_GET["rtype"])) // YES, R-type was a classic game :-)
        $report_type = get_parameter ("rtype"); 
    else {
        echo "<h2>No access without report type</h2>";
        audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access report without specify reportype");
        exit;
    }
    
    // Available PDF reports:
    switch ($report_type){
            case "general": 
                general_report ($id_report);
                break;
    }
}

?>
