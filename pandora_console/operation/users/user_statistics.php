<?php
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2005

// Cargamos variables globales
require("include/config.php");
//require("include/functions.php");
//require("include/functions_db.php");
if (comprueba_login() == 0) {
echo "<h2>".$lang_label["users"]."</h2>";
echo "<h3>".$lang_label["users_statistics"]."</h3>";
echo '<img src="reporting/fgraph.php?tipo=user_activity" border=0>';
echo "<br><br>";
}
?>
