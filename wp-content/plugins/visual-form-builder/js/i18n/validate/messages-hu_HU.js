/*
 * Translated default messages for the jQuery validation plugin.
 * Locale: HU (Hungarian; Magyar)
 */
(function($) {
	$.extend($.validator.messages, {
		required: "Kötelező megadni.",
		remote: "Kérem javítsa ki ezt a mezőt.",
		email: "Érvényes e-mail címnek kell lennie.",
		url: "Érvényes URL-nek kell lennie.",
		date: "Dátumnak kell lennie.",
		dateISO: "Kérem írjon be egy érvényes dátumot (ISO).",
		number: "Számnak kell lennie.",
		digits: "Csak számjegyek lehetnek.",
		creditcard: "Érvényes hitelkártyaszámnak kell lennie.",
		equalTo: "Meg kell egyeznie a két értéknek.",
		accept: "Adjon meg egy értéket, megfelelő végződéssel.",
		maxlength: $.validator.format("Legfeljebb {0} karakter hosszú legyen."),
		minlength: $.validator.format("Legalább {0} karakter hosszú legyen."),
		rangelength: $.validator.format("Legalább {0} és legfeljebb {1} karakter hosszú legyen."),
		range: $.validator.format("{0} és {1} közé kell esnie."),
		max: $.validator.format("Nem lehet nagyobb, mint {0}."),
		min: $.validator.format("Nem lehet kisebb, mint {0}."),
		maxWords: $.validator.format("Kérjük, adja meg {0} szó, vagy kevesebb."),
		minWords: $.validator.format("Kérjük, adja meg legalább {0} szó."),
		rangeWords: $.validator.format("Kérjük, adja meg {0} {1} szavakat."),
		alphanumeric: "Betűk, számok és aláhúzás csak kérjük",
		lettersonly: "Letters csak kérlek",
		nowhitespace: "No white space kérlek",
		phone: 'Adjon meg egy érvényes telefonszámot. A legtöbb USA / Kanada és nemzetközi formátumban elfogadott.',
		ipv4: 'Adjon meg egy érvényes IP v4-címet.',
		ipv6: 'Adjon meg egy érvényes IP v6-címet.',
		ziprange: 'A zip-kódot kell a tartományban 902xx-xxxx a 905-xx-xxxx',
		zipcodeUS: 'A megadott US irányítószám érvénytelen',
		integer: 'A pozitív vagy negatív nem decimális számot kérjük',
		vfbUsername: 'Ez a felhasználónév már regisztrálva van. Kérjük, válasszon egy másikat'
	});
}(jQuery));