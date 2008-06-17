/* http://keith-wood.name/timeEntry.html
   Catalonian initialisation for the jQuery time entry extension*/
$(document).ready(function() {
	$.timeEntry.regional['ca'] = {show24Hours: true, separator: ':',
		ampmPrefix: '', ampmNames: ['AM', 'PM'],
		spinnerTexts: ['Ahora', 'Campo anterior', 'Siguiente campo', 'Aumentar', 'Disminuir']};
	$.timeEntry.setDefaults($.timeEntry.regional['ca']);
});
