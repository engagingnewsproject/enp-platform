/*
 * Translated default messages for the jQuery validation plugin.
 * Locale: SV (Swedish; Svenska)
 */
(function($) {
	$.extend($.validator.messages, {
		required: "Detta f&auml;lt &auml;r obligatoriskt.",
		remote: "Åtgärda detta område.",
		email: "Ange en korrekt e-postadress.",
		url: "Ange en korrekt URL.",
		date: "Ange ett korrekt datum.",
		dateISO: "Ange ett korrekt datum (&Aring;&Aring;&Aring;&Aring;-MM-DD).",
		number: "Ange ett korrekt nummer.",
		digits: "Ange endast siffror.",
		creditcard: "Ange ett korrekt kreditkortsnummer.",
		equalTo: "Ange samma v&auml;rde igen.",
		accept: "Ange ett värde med en giltig domän.",
		maxlength: $.validator.format("Du f&aring;r ange h&ouml;gst {0} tecken."),
		minlength: $.validator.format("Du m&aring;ste ange minst {0} tecken."),
		rangelength: $.validator.format("Ange minst {0} och max {1} tecken."),
		range: $.validator.format("Ange ett v&auml;rde mellan {0} och {1}."),
		max: $.validator.format("Ange ett v&auml;rde som &auml;r mindre eller lika med {0}."),
		min: $.validator.format("Ange ett v&auml;rde som &auml;r st&ouml;rre eller lika med {0}."),
		maxWords: $.validator.format("Ange {0} ord eller mindre."),
		minWords: $.validator.format("Ange minst {0} ord."),
		rangeWords: $.validator.format("Ange mellan {0} och {1} ord."),
		alphanumeric: "Bokstäver, siffror och understreck bara snälla",
		lettersonly: "Brev bara snälla",
		nowhitespace: "Inga blank vänligen",
		phone: 'Ange ett giltigt telefonnummer. De flesta USA / Kanada och internationella format accepteras.',
		ipv4: 'Ange en giltig IP v4-adress.',
		ipv6: 'Ange en giltig IP v6-adress.',
		ziprange: 'Din ZIP-kod måste ligga i intervallet 902xx-xxxx till 905-xx-xxxx',
		zipcodeUS: 'Den angivna amerikanska postnummer är ogiltig',
		integer: 'En positiv eller negativ icke-decimaltal vänligen',
		vfbUsername: 'Detta användarnamn är upptaget. Var vänlig välj en annan'
	});
}(jQuery));