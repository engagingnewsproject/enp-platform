/*
 * Translated default messages for the jQuery validation plugin.
 * Locale: PT (Portuguese; português)
 * Region: BR (Brazil)
 */
(function($) {
	$.extend($.validator.messages, {
		required: "Este campo &eacute; requerido.",
		remote: "Por favor, corrija este campo.",
		email: "Por favor, forne&ccedil;a um endere&ccedil;o de email v&aacute;lido.",
		url: "Por favor, forne&ccedil;a uma URL v&aacute;lida.",
		date: "Por favor, forne&ccedil;a uma data v&aacute;lida.",
		dateISO: "Por favor, forne&ccedil;a uma data v&aacute;lida (ISO).",
		number: "Por favor, forne&ccedil;a um n&uacute;mero v&aacute;lido.",
		digits: "Por favor, forne&ccedil;a somente d&iacute;gitos.",
		creditcard: "Por favor, forne&ccedil;a um cart&atilde;o de cr&eacute;dito v&aacute;lido.",
		equalTo: "Por favor, forne&ccedil;a o mesmo valor novamente.",
		accept: "Por favor, forne&ccedil;a um valor com uma extens&atilde;o v&aacute;lida.",
		maxlength: $.validator.format("Por favor, forne&ccedil;a n&atilde;o mais que {0} caracteres."),
		minlength: $.validator.format("Por favor, forne&ccedil;a ao menos {0} caracteres."),
		rangelength: $.validator.format("Por favor, forne&ccedil;a um valor entre {0} e {1} caracteres de comprimento."),
		range: $.validator.format("Por favor, forne&ccedil;a um valor entre {0} e {1}."),
		max: $.validator.format("Por favor, forne&ccedil;a um valor menor ou igual a {0}."),
		min: $.validator.format("Por favor, forne&ccedil;a um valor maior ou igual a {0}."),
		maxWords: $.validator.format("Por favor entre {0} palavras ou menos."),
		minWords: $.validator.format("Por favor, insira pelo menos {0} palavras."),
		rangeWords: $.validator.format("Por favor, insira entre {0} e {1} palavras."),
		alphanumeric: "Letras, números e sublinhados só por favor",
		lettersonly: "Cartas só por favor",
		nowhitespace: "No espaço em branco, por favor",
		phone: 'Por favor insira um número de telefone válido. A maioria dos formatos EUA / Canadá e internacionais aceitos. ',
		ipv4: 'Por favor insira um endereço de IP v4 válido.',
		ipv6: 'Por favor insira um endereço de IP v6 válido.',
		ziprange: 'O CEP-código deve estar no intervalo 902xx-xxxx a 905-xx-xxxx',
		zipcodeUS: 'O CEP EUA especificado é inválido',
		integer: 'Um número não decimal positivo ou negativo, por favor',
		vfbUsername: 'Este nome de usuário já está registrado. Por favor escolha outro'
	});
}(jQuery));