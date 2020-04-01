/*
 * Translated default messages for the jQuery validation plugin.
 * Locale: IT (Italian; Italiano)
 */
(function($) {
	$.extend($.validator.messages, {
		required: "Campo obbligatorio.",
		remote: "Controlla questo campo.",
		email: "Inserisci un indirizzo email valido.",
		url: "Inserisci un indirizzo web valido.",
		date: "Inserisci una data valida.",
		dateISO: "Inserisci una data valida (ISO).",
		number: "Inserisci un numero valido.",
		digits: "Inserisci solo numeri.",
		creditcard: "Inserisci un numero di carta di credito valido.",
		equalTo: "Il valore non corrisponde.",
		accept: "Inserisci un valore con un&apos;estensione valida.",
		maxlength: $.validator.format("Non inserire pi&ugrave; di {0} caratteri."),
		minlength: $.validator.format("Inserisci almeno {0} caratteri."),
		rangelength: $.validator.format("Inserisci un valore compreso tra {0} e {1} caratteri."),
		range: $.validator.format("Inserisci un valore compreso tra {0} e {1}."),
		max: $.validator.format("Inserisci un valore minore o uguale a {0}."),
		min: $.validator.format("Inserisci un valore maggiore o uguale a {0}."),
		maxWords: $.validator.format("Inserisci {0} parole o meno."),
		minWords: $.validator.format("Inserisci almeno {0} parole."),
		rangeWords: $.validator.format("Inserisci tra {0} e {1} parole."),
		alphanumeric: "Lettere, numeri e underscore solo per favore",
		lettersonly: "Lettere solo per favore",
		nowhitespace: "Nessuno spazio bianco per favore",
		phone: 'Si prega di inserire un numero di telefono valido. La maggior parte dei formati di US / Canada e internazionali accettate.',
		ipv4: 'Si prega di inserire un indirizzo IP v4 valido.',
		ipv6: 'Si prega di inserire un indirizzo IP v6 valido.',
		ziprange: 'Il tuo ZIP-codice deve essere nel range 902xx-xxxx a 905-xx-xxxx',
		zipcodeUS: 'Gli Stati Uniti CAP specificato non è valido',
		integer: 'Un numero non decimale positivo o negativo per favore',
		vfbUsername: 'Questo nome utente è già registrato. Si prega di scegliere un altro'
	});
}(jQuery));