/* http://keith-wood.name/timeEntry.html
   French initialisation for the jQuery time entry extension
   Written by Keith Wood (kbwood@iprimus.com.au) June 2007. */
$(document).ready(function() {
	$.timeEntry.regional['fr'] = {show24Hours: true, separator: ':',
		ampmPrefix: '', ampmNames: ['AM', 'PM'],
		spinnerTexts: ['Maintenant', 'Précédent', 'Suivant', 'Augmentez', 'Amoindrissez']};
	$.timeEntry.setDefaults($.timeEntry.regional['fr']);
});