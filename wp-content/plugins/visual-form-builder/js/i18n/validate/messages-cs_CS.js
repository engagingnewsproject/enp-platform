/*
 * Translated default messages for the jQuery validation plugin.
 * Locale: CS (Czech; čeština, český jazyk)
 */
(function($) {
	$.extend($.validator.messages, {
		required: "Tento údaj je povinný.",
		remote: "Prosím, opravte tento údaj.",
		email: "Prosím, zadejte platný e-mail.",
		url: "Prosím, zadejte platné URL.",
		date: "Prosím, zadejte platné datum.",
		dateISO: "Prosím, zadejte platné datum (ISO).",
		number: "Prosím, zadejte číslo.",
		digits: "Prosím, zadávejte pouze číslice.",
		creditcard: "Prosím, zadejte číslo kreditní karty.",
		equalTo: "Prosím, zadejte znovu stejnou hodnotu.",
		accept: "Prosím, zadejte soubor se správnou příponou.",
		maxlength: $.validator.format("Prosím, zadejte nejvíce {0} znaků."),
		minlength: $.validator.format("Prosím, zadejte nejméně {0} znaků."),
		rangelength: $.validator.format("Prosím, zadejte od {0} do {1} znaků."),
		range: $.validator.format("Prosím, zadejte hodnotu od {0} do {1}."),
		max: $.validator.format("Prosím, zadejte hodnotu menší nebo rovnu {0}."),
		min: $.validator.format("Prosím, zadejte hodnotu větší nebo rovnu {0}."),
		maxWords: $.validator.format("Prosím, zadejte {0} slova nebo méně."),
		minWords: $.validator.format("Prosím, zadejte alespoň {0} slov."),
		rangeWords: $.validator.format("Prosím zadejte mezi {0} a {1} slovy."),
		alphanumeric: "Písmena, číslice a podtržítka jen prosím",
		lettersonly: "Dopisy jen prosím",
		nowhitespace: "Žádné mezery, prosím",
		phone: 'Prosím, zadejte platné telefonní číslo. Většina USA / Kanada a mezinárodní formáty přijaty.',
		ipv4: 'Prosím, zadejte platnou IP v4 adresu.',
		ipv6: 'Prosím, zadejte platnou IP v6 adresu.',
		ziprange: 'Váš ZIP kód musí v rozsahu 902xx-xxxx na 905-xx-xxxx',
		zipcodeUS: 'Zadaná US PSČ je neplatné',
		integer: 'Pozitivní nebo negativní, non-desetinné číslo, prosím',
		vfbUsername: 'Toto uživatelské jméno je již registrováno. Prosím, zvolte jinou'
	});
}(jQuery));