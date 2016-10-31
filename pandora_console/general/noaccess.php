<script>

	$('body').append( "<div id='opacidad' style='position:fixed;background:black;opacity:0.6;z-index:1'></div>" );
	jQuery.get ("ajax.php",
		{
	"page": "general/alert_enterprise",
	"message":"noaccess"},
		function (data, status) {
			$("#alert_messages").hide ()
				.empty ()
				.append (data)
				.show ();
		},
		"html"
	);
	
	
	
</script>