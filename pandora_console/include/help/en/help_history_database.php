<?php
/**
 * @package Include/help/en
 */
?>
<h1>History database</h1>

A history database is a database where old module data is moved to make the main <?php echo get_product_name(); ?>  database more responsive for everyday operations. That data will still be available seamlessly to the <?php echo get_product_name(); ?>  console when viewing reports, module charts etc. 
<br><br>
<b>SETTING UP A HISTORY DATABASE</b>
<br><br>
To configure a history database follow these simple steps:
<br><br>
<ol>
<li>Create the new history database. 
<br><br>
<li>Create the necessary tables in the new database. You can use the DB Tool script provided with the <?php echo get_product_name(); ?>  console: 
<br><br>
<i>cat pandoradb.sql | mysql -u user -p -D history_db</i>
<br><br>
<li>Give pandora user permissions to access pandora history database
<br><br>
<i>Mysql Example: GRANT ALL PRIVILEGES ON pandora.* TO 'pandora'@'IP' IDENTIFIED BY 'password'</i>
<br><br>
<li>In your <?php echo get_product_name(); ?>  console navigate to Setup->History database and enter the host, port, database name, user and password of the new database.
</ol>
<br><br>
<?php html_print_image('images/help/historyddbb.png', false, ['width' => '550px']); ?>
<br><br>
Data older than Days days will be moved to the history database in blocks of Step rows, waiting Delay seconds between one block and the next to avoid overload. 
<br><br>
Here are explained the fields:
<br><br>
<ol>
   <b>Enable history database:</b> Enabled history database feature. 
<br><br>
   <b>Host:</b> Hostname of history database. 
<br><br>
    <b>Port:</b> Port of history database. 
<br><br>
   <b>Database name:</b> Database name for history database. 
<br><br>
   <b>Database user:</b> User to access to history database. 
<br><br>
   <b>Database password:</b> Password to access to history database. 
<br><br>
   <b>Days:</b> Number of days since data will be transfered to history database. 
<br><br>
   <b>Step:</b> Size of buffer for data transferring. The lower step the slower data transferring, but lower performance reduction on main database. 
<br><br>
   <b>Delay:</b> delay time between data transferences between main and history database.
<br><br>
</ol> 
