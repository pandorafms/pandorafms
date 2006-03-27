<?php
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raul Mateos <raulofpandora@gmail.com>, 2004-2006
?>

<div align='center'>
	<h1 id="log"><?php echo $lang_label['welcome_title']; ?></h1>
	<div id='login'>
		<div id="login_box">
			<form method="post" action="index.php?login=1">
				<div class="f9b">Login</div>
				<input class="login" type="text" name="nick" value="demo">
				<div class="f9b">Password</div>
				<input class="login" type="password" name="pass" value="demo">
				<div><input type="submit" class="sub" value="Login"></div>
			</form>
		</div>
		<div id="logo_box">
			<a href="index.php"><img src="images/logo_login.gif" border="0" alt="logo"></a><br>
			<?php echo $pandora_version; ?>
		</div>
		<div id="ip"><?php echo 'IP: <b class="f10">'.$REMOTE_ADDR.'</b>'; ?></div>
	</div>
	<?php include "general/footer.php"; ?>
</div>