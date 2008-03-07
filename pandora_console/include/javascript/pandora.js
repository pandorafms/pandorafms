
/* Function to hide/unhide a specific Div id */
function toggleDiv (divid){
	if (document.getElementById(divid).style.display == 'none'){
		document.getElementById(divid).style.display = 'block';
	} else {
		document.getElementById(divid).style.display = 'none';
	}
}

function winopeng(url,wid) {
    nueva_ventana=open(url,wid,"width=530,height=275,status=no,toolbar=no,menubar=no");
    // WARNING !! Internet Explorer DOESNT SUPPORT "-" CARACTERS IN WINDOW HANDLE VARIABLE
    status =wid;
}

function pandora_help(help_id) {
    nueva_ventana=open("general/pandora_help.php?id="+help_id, "pandorahelp","width=600,height=500,status=no,toolbar=no,menubar=no,scrollbar=yes");
}