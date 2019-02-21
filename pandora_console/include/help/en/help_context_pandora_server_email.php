<?php
/**
 * @package Include/help/en
 */
?>
<h1>Config the <?php echo get_product_name(); ?> for email alerts</h1>
<p>You must to edit the <i>"<?php echo get_product_name(); ?> conf file"</i>, normaly it is in:
<pre>
    /etc/pandora_server/pandora_server.conf
</pre>
And you must set these values:
<pre>
# mta_address: External Mailer (MTA) IP Address to be used by <?php echo get_product_name(); ?> internal email capabilities

mta_address localhost

# mta_port, this is the mail server port (default 25)

mta_port 25

# mta_user MTA User (if needed for auth, FQD or simple user, depending on your server)

mta_user myuser@mydomain.com

# mta_pass MTA Pass (if needed for auth)

mta_pass mypassword

# mta_auth MTA Auth system (if needed, it supports LOGIN, PLAIN, CRAM-MD5, DIGEST-MD)

mta_auth LOGIN

# mta_from Email address that sends the mail, by default is pandora@localhost 
#           probably you need to change it to avoid problems with your antispam

mta_from <?php echo get_product_name(); ?> &lt;monitoring@mydomain.com&gt;
</pre>
</p>