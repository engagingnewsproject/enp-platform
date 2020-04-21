/*
 * Translated default messages for the jQuery validation plugin.
 * Locale: RO (Romanian, limba română)
 */
(function($) {
	$.extend($.validator.messages, {
		required: "Acest câmp este obligatoriu.",
		remote: "Te rugăm să completezi acest câmp.",
		email: "Te rugăm să introduci o adresă de email validă",
		url: "Te rugăm sa introduci o adresă URL validă.",
		date: "Te rugăm să introduci o dată corectă.",
		dateISO: "Te rugăm să introduci o dată (ISO) corectă.",
		number: "Te rugăm să introduci un număr întreg valid.",
		digits: "Te rugăm să introduci doar cifre.",
		creditcard: "Te rugăm să introduci un numar de carte de credit valid.",
		equalTo: "Te rugăm să reintroduci valoarea.",
		accept: "Te rugăm să introduci o valoare cu o extensie validă.",
		maxlength: $.validator.format("Te rugăm să nu introduci mai mult de {0} caractere."),
		minlength: $.validator.format("Te rugăm să introduci cel puțin {0} caractere."),
		rangelength: $.validator.format("Te rugăm să introduci o valoare între {0} și {1} caractere."),
		range: $.validator.format("Te rugăm să introduci o valoare între {0} și {1}."),
		max: $.validator.format("Te rugăm să introduci o valoare egal sau mai mică decât {0}."),
		min: $.validator.format("Te rugăm să introduci o valoare egal sau mai mare decât {0}."),
		maxWords: $.validator.format("Te rugăm să introduci {0} de cuvinte sau mai puțin"),
		minWords: $.validator.format("Te rugăm să introduci cel putin {0} cuvinte."),
		rangeWords: $.validator.format("Te rugăm să introduci {0} și {1} cuvinte"),
		alphanumeric: "Litere, numere, și subliniază numai vă rog",
		lettersonly: "Scrisori doar vă rog",
		nowhitespace: "Nu există spațiu alb, vă rugăm",
		phone: 'Vă rugăm să introduceți un număr de telefon valid. Cele mai multe formate de SUA / Canada și internaționale acceptate.',
		ipv4: 'Vă rugăm să introduceți o adresă validă IP v4.',
		ipv6: 'Vă rugăm să introduceți o adresă validă IP v6.',
		ziprange: 'Dvs. ZIP-cod trebuie să fie în gama 902xx-xxxx a 905-xx-xxxx',
		zipcodeUS: 'Specificat SUA cod poștal este nevalid',
		integer: 'Un număr de non-zecimal pozitiv sau negativ, vă rugăm',
		vfbUsername: 'Acest nume de utilizator este deja înregistrat. Vă rugăm să alegeți un alt unul'
	});
}(jQuery));
