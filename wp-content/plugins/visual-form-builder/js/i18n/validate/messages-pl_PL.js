/*
 * Translated default messages for the jQuery validation plugin.
 * Locale: PL (Polish; język polski, polszczyzna)
 */
(function($) {
	$.extend($.validator.messages, {
		required: "To pole jest wymagane.",
		remote: "Proszę o wypełnienie tego pola.",
		email: "Proszę o podanie prawidłowego adresu email.",
		url: "Proszę o podanie prawidłowego URL.",
		date: "Proszę o podanie prawidłowej daty.",
		dateISO: "Proszę o podanie prawidłowej daty (ISO).",
		number: "Proszę o podanie prawidłowej liczby.",
		digits: "Proszę o podanie samych cyfr.",
		creditcard: "Proszę o podanie prawidłowej karty kredytowej.",
		equalTo: "Proszę o podanie tej samej wartości ponownie.",
		accept: "Proszę o podanie wartości z prawidłowym rozszerzeniem.",
		maxlength: $.validator.format("Proszę o podanie nie więcej niż {0} znaków."),
		minlength: $.validator.format("Proszę o podanie przynajmniej {0} znaków."),
		rangelength: $.validator.format("Proszę o podanie wartości o długości od {0} do {1} znaków."),
		range: $.validator.format("Proszę o podanie wartości z przedziału od {0} do {1}."),
		max: $.validator.format("Proszę o podanie wartości mniejszej bądź równej {0}."),
		min: $.validator.format("Proszę o podanie wartości większej bądź równej {0}."),
		maxWords: $.validator.format("Proszę o podanie {0} lub mniej słów."),
		minWords: $.validator.format("Proszę o podanie najmniej {0} słowa"),
		rangeWords: $.validator.format("Proszę o podanie między {0} i {1} słów."),
		alphanumeric: "Liter, cyfr, podkreśleń i tylko proszę",
		lettersonly: "Proszę tylko litery",
		nowhitespace: "Proszę nie spacje",
		phone: 'Proszę podać poprawny numer telefonu. Przyjmujemy większość formatów US / Kanada i międzynarodowe.',
		ipv4: 'Proszę podać adres IP v4.',
		ipv6: 'Proszę podać adres IP v6.',
		ziprange: 'Twój kod pocztowy musi być w zakresie 902xx do 905-xxxx-xxxx-xx',
		zipcodeUS: 'Określone US kod pocztowy jest nieprawidłowy',
		integer: 'Dodatnia lub ujemna liczba dziesiętna proszę nie',
		vfbUsername: 'Ta nazwa użytkownika jest już zarejestrowana. Wybierz inny',
	});
}(jQuery));