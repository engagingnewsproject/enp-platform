/*
 * Translated default messages for the jQuery validation plugin.
 * Locale: ES (Spanish; Español)
 */
(function($) {
	$.extend($.validator.messages, {
		required: "Este campo es obligatorio.",
		remote: "Por favor, rellena este campo.",
		email: "Por favor, escribe una dirección de correo válida.",
		url: "Por favor, escribe una URL válida.",
		date: "Por favor, escribe una fecha válida.",
		dateISO: "Por favor, escribe una fecha (ISO) válida.",
		number: "Por favor, escribe un número entero válido.",
		digits: "Por favor, escribe sólo dígitos.",
		creditcard: "Por favor, escribe un número de tarjeta válido.",
		equalTo: "Por favor, escribe el mismo valor de nuevo.",
		accept: "Por favor, escribe un valor con una extensión aceptada.",
		maxlength: $.validator.format("Por favor, no escribas más de {0} caracteres."),
		minlength: $.validator.format("Por favor, no escribas menos de {0} caracteres."),
		rangelength: $.validator.format("Por favor, escribe un valor entre {0} y {1} caracteres."),
		range: $.validator.format("Por favor, escribe un valor entre {0} y {1}."),
		max: $.validator.format("Por favor, escribe un valor menor o igual a {0}."),
		min: $.validator.format("Por favor, escribe un valor mayor o igual a {0}."),
		maxWords: $.validator.format("Por favor, introduzca {0} palabras o menos."),
		minWords: $.validator.format("Por favor introduzca al menos {0} palabras."),
		rangeWords: $.validator.format("Por favor introduce entre {0} y {1} palabras."),
		alphanumeric: "Las letras, números y subrayados por favor",
		lettersonly: "Cartas solamente por favor",
		nowhitespace: "No hay espacio en blanco por favor",
		phone: 'Por favor, introduzca un número de teléfono válido. La mayoría de los formatos de EE.UU. / Canadá e internacionales aceptados.',
		ipv4: 'Por favor, introduce una dirección IP v4 válida.',
		ipv6: 'Por favor, introduce una dirección IP v6 válida.',
		ziprange: 'Su código postal debe estar en el rango 902xx-xxxx a 905-xx-xxxx',
		zipcodeUS: 'Los EE.UU. Código postal especificado no es válido',
		integer: 'Un número no decimal positivo o negativo por favor',
		vfbUsername: 'Este nombre de usuario ya está registrado. Por favor elija otra'
	});
}(jQuery));