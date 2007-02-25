<?php	

	#########################
	
	# php code for managing xml information. Two functions:
	
	# (a) given a xml file (POST variable 'xml_file' or via text box),
	#     it represents its parameters in a html menu
	
	# (b) the user can modify the parameters of the xml file via
	#     direct editing in the text box or through the html menu
	#     and then create another xml file
	
	#########################

	require ('./operation/reporting/report.php');
	require ("include/languages/language_".$language_code.".php");
	
	// calculating XML file
	
	if ( !($xml_file = $_POST['xml_file']) ) { $xml_file = parse_POST_to_XML ( $_POST, 0 ); }
	$xml_file = stripslashes($xml_file);
	
	// if XML has to be executed ...
	
	if ($xml_file and $_POST['execute']) {
		
		// let's execute the report and offer the resulting document
		// It offers a zip file and quits
		
		open_and_write_report ( $xml_file, $report_plugins, $report_plugin_functions, 'report' ); 
		return;
	}

	// if we are uploading a XML report ...

	if ( isset($_FILES['xml_file']) && !$xml_file) { 
		// TODO protection vs upload attacks
		$filesize = filesize($_FILES['xml_file']['tmp_name']);
		$handle = fopen($_FILES['xml_file']['tmp_name'], "r");
		$xml_file = fread($handle, $filesize);
		fclose($handle);
	}

	// default XML report

	if (!($xml_file)) { 
		$xml_file = "<?xml version='1.0' encoding='UTF-8' ?>
<report>
	<defaultvalues>
		<RC_report>
			<output_format></output_format>
		</RC_report>
	</defaultvalues>
</report>";
	}

?>



<!--
#########################

cross-browser javascripts for the hiding menus

#########################
-->

<script type='text/javascript'>
window.onload = function()
{
    if (window.winOnLoad) window.winOnLoad();
}
window.onunload = function()
{
    if (window.winOnUnload) window.winOnUnload();
}
</script>

<link rel="stylesheet" href="include/styles/reporting.css" type="text/css">

<script type='text/javascript' src='include/styles/cb/x_core.js'></script>
<script type='text/javascript' src='include/styles/cb/x_dom.js'></script>
<script type='text/javascript' src='include/styles/cb/lib/xcollapsible.js'></script>
<script type='text/javascript' src='include/styles/cb/lib/xmenu5.js'></script>
<script type='text/javascript'>

// xMenu5/xCollapsible
var mnu = new Array();
window.onload = function()
{
  mnu = new xCollapsible('clpsDIV1', false);
}

</script>


<!--
#########################

 end of cross-browser javascripts

#########################
-->




<div id='clpsDIV1'>
<h2><?php echo $lang_label['rep_101'] . '<a href="help/'.$help_code.'/chap10.php#101" target="_help" class="help">&nbsp;<span>'.$lang_label["help"].'</span></a>'; ?></h2> 

<!--

   if 'xml_file' POST variable exist, xml file is parsed and printed. 
   The user can modify the parameters and request a new xml file via this
   form, that is printed in the text area of the second form.

-->
<form method='POST' action='index.php?sec=reporting&sec2=operation/reporting/report_create'>
<?php  
	// calculating and printing form
	if ( $xml_file ) {
	
		open_and_write_report ( $xml_file, $report_plugins, $report_plugin_functions, 'php://output', 'guihtml');   
	} 
?>
</div> <!-- end clpsDIV1 -->

<div class='replev3'>	
	<br><br>					
	<input type="submit" value="<?php echo $lang_label['rep_submit_changes']; ?>">
</div>

<hr><br>

<h2><?php echo $lang_label['rep_102'] . '<a href="help/'.$help_code.'/chap10.php#102" target="_help" class="help">&nbsp;<span>'.$lang_label["help"].'</span></a>'; ?></h2>

<h3><?php echo $lang_label['rep_1021']; ?>:</h3> 
<div class='replev3'>
		<?php echo $lang_label['rep_type']; ?>:&nbsp;&nbsp;  
		<select name='adddeldefaultvalue' value=''>
			<option value=''>
		<?php
			for ($cc=0; $cc < count($report_plugins); $cc++) {
				print"<option value='" . $report_plugins[$cc] . "'> " . $report_plugins[$cc] ;
			}
		?> 
		</select>
</div>


<h3><?php echo $lang_label['rep_1022']; ?>:</h3>
<div class='replev3'>
		<input type='radio' name='addcomponentafter' value='0' checked > <?php echo $lang_label['rep_before']; ?>
				&nbsp; &nbsp; 
		<input type='radio' name='addcomponentafter' value='1' > <?php echo $lang_label['rep_after']; ?>
				&nbsp; &nbsp; <?php echo $lang_label['rep_position']; ?>:  
		<input type='text' name='addcomponentposition'  size='4' >		 
				&nbsp; &nbsp;  
		
		RC: 
		<select name='addRCcomponentplugin' value=''>
			<option value=''>
		<?php
			for ($cc=0; $cc < count($report_plugins); $cc++) {
				if (strpos($report_plugins[$cc], 'RC_') === 0) {
					print"<option value='" . $report_plugins[$cc] . "'> " . $report_plugins[$cc] ; }
			}
		?> 
		</select>
		
		DC: 
		<select name='addDCcomponentplugin' value=''>
			<option value=''>
		<?php
			for ($cc=0; $cc < count($report_plugins); $cc++) {
				if (strpos($report_plugins[$cc], 'DC_') === 0) {
					print"<option value='" . $report_plugins[$cc] . "'> " . $report_plugins[$cc] ; }
			}
		?> 
		</select>		
</div>		

			
<h3><?php echo $lang_label['rep_1023']; ?>:</h3> 
<div class='replev3'>
		<?php echo $lang_label['rep_position']; ?>: &nbsp;&nbsp; <input type='text' name='delcomponentposition'  size='4' >		 
</div>		
				
<div class='replev3'>	
	<br><br>					
	<input type="submit" value="<?php echo $lang_label['rep_submit_changes']; ?>">
</div>

</form>



<hr><br>

<h2><?php echo $lang_label['rep_103'] . '<a href="help/'.$help_code.'/chap10.php#103" target="_help" class="help">&nbsp;<span>'.$lang_label["help"].'</span></a>'; ?></h2>

<div class='replev3'>
	<form method='POST' action='index.php?sec=reporting&sec2=operation/reporting/report_create' target='_blank'>

		<table>
			<tr><td class='left'>
			<?php echo $lang_label['rep_1032']; ?>:
			</td><td class='right'>
				<input type='hidden' name='xml_file' value='<?php print htmlentities($xml_file,ENT_QUOTES); ?>'>
				<input type='hidden' name='execute' value='1'> 	
				<input type='submit' value='<?php echo $lang_label['rep_execute']; ?>'>		
				</td></tr>
		</table>
	</form>
</div>



<div class='replev3'>
	<form method='POST' action='operation/reporting/download_xml.php' target='_blank'>
	
		<table>
			<tr><td class='left'>
			<?php echo $lang_label['rep_1033']; ?>:
			</td><td class='right'>
				<input type='hidden' name='xml_file' value='<?php print htmlentities($xml_file,ENT_QUOTES); ?>'>
				<input type="submit" value="<?php echo $lang_label['rep_download_xml']; ?>">		
			</td></tr>
		</table>
	</form>
</div>



<div class='replev3'>
	<form enctype="multipart/form-data" action="index.php?sec=reporting&sec2=operation/reporting/report_create" method="POST">
	
		<table>
			<tr><td class='left'>
			<?php echo $lang_label['rep_1031']; ?>:
			</td><td class='right'>
				<input type="hidden" name="MAX_FILE_SIZE" value="30000" />
				<input name="xml_file" type="file" >&nbsp;
				<input type="submit" value="<?php echo $lang_label['rep_upload_xml']; ?>" >		
			</td></tr>
		</table>
	</form>
</div>

<hr><br>

<div class='replev3'>
	<?php echo $lang_label['rep_more_help']; ?>
	<ul>
		<li><?php echo $lang_label['rep_important_notes'] . '<a href="help/'.$help_code.'/chap10.php#104" target="_help" class="help">&nbsp;<span>'.$lang_label["help"].'</span></a>'; ?>
		<li><?php echo $lang_label['rep_suggested_steps'] . '<a href="help/'.$help_code.'/chap10.php#105" target="_help" class="help">&nbsp;<span>'.$lang_label["help"].'</span></a>'; ?>
		<li><?php echo $lang_label['rep_components_explained'] . '<a href="help/'.$help_code.'/chap10.php#106" target="_help" class="help">&nbsp;<span>'.$lang_label["help"].'</span></a>'; ?>
	</ul>
</div>



