/*
 * Translated default messages for the jQuery validation plugin.
 * Locale: DE (German, Deutsch)
 */
(function($) {
	$.extend($.validator.messages, {
		required: "Dieses Feld ist ein Pflichtfeld.",
		remote: "Bitte korrigieren Sie dieses Feld.",
		email: "Geben Sie bitte eine gültige E-Mail Adresse ein.",
		url: "Geben Sie bitte eine gültige URL ein.",
		date: "Bitte geben Sie ein gültiges Datum ein.",
		dateISO: "Bitte geben Sie ein gültiges Datum ein (ISO).",
		number: "Geben Sie bitte eine Nummer ein.",
		digits: "Geben Sie bitte nur Ziffern ein.",
		creditcard: "Geben Sie bitte eine gültige Kreditkarten-Nummer ein.",
		equalTo: "Bitte denselben Wert wiederholen.",
		accept: "Bitte geben Sie einen Wert mit einer gültigen Erweiterung.",
		maxlength: $.validator.format("Geben Sie bitte maximal {0} Zeichen ein."),
		minlength: $.validator.format("Geben Sie bitte mindestens {0} Zeichen ein."),
		rangelength: $.validator.format("Geben Sie bitte mindestens {0} und maximal {1} Zeichen ein."),
		range: $.validator.format("Geben Sie bitte einen Wert zwischen {0} und {1} ein."),
		max: $.validator.format("Geben Sie bitte einen Wert kleiner oder gleich {0} ein."),
		min: $.validator.format("Geben Sie bitte einen Wert größer oder gleich {0} ein."),
		maxWords: $.validator.format("Geben Sie bitte {0} Wörter oder weniger."),
		minWords: $.validator.format("Geben Sie bitte mindestens {0} Worte."),
		rangeWords: $.validator.format("Geben Sie bitte zwischen {0} und {1} Wörter eingeben."),
		alphanumeric: "Buchstaben, Zahlen und Unterstrichen bitte",
		lettersonly: "Nur Buchstaben bitte",
		nowhitespace: "Kein Leerzeichen bitte",
		phone: 'Bitte geben Sie eine gültige Telefonnummer. Die meisten US / Canada und internationale Formate akzeptiert.',
		ipv4: 'Bitte geben Sie eine gültige IP v4 Adresse.',
		ipv6: 'Bitte geben Sie eine gültige IP-v6-Adresse.',
		ziprange: 'Ihre ZIP-Code muss im Bereich 902xx-xxxx bis 905-xx-xxxx sein',
		zipcodeUS: 'Die angegebene US-Postleitzahl ist ungültig',
		integer: 'Eine positive oder negative Nicht-Dezimalzahl bitte',
		vfbUsername: 'Dieser Benutzername ist bereits registriert. Bitte wählen Sie einen anderen'
	});
}(jQuery));