/*
 * Translated default messages for the jQuery validation plugin.
 * Locale: FR (French; français)
 */
(function($) {
	$.extend($.validator.messages, {
		required: "Ce champ est obligatoire.",
		remote: "Veuillez corriger ce champ.",
		email: "Veuillez fournir une adresse électronique valide.",
		url: "Veuillez fournir une adresse URL valide.",
		date: "Veuillez fournir une date valide.",
		dateISO: "Veuillez fournir une date valide (ISO).",
		number: "Veuillez fournir un numéro valide.",
		digits: "Veuillez fournir seulement des chiffres.",
		creditcard: "Veuillez fournir un numéro de carte de crédit valide.",
		equalTo: "Veuillez fournir encore la même valeur.",
		accept: "Veuillez fournir une valeur avec une extension valide.",
		maxlength: $.validator.format("Veuillez fournir au plus {0} caractères."),
		minlength: $.validator.format("Veuillez fournir au moins {0} caractères."),
		rangelength: $.validator.format("Veuillez fournir une valeur qui contient entre {0} et {1} caractères."),
		range: $.validator.format("Veuillez fournir une valeur entre {0} et {1}."),
		max: $.validator.format("Veuillez fournir une valeur inférieur ou égal à {0}."),
		min: $.validator.format("Veuillez fournir une valeur supérieur ou égal à {0}."),
		maxWords: $.validator.format("Veuillez fournir au plus {0} mots."),
		minWords: $.validator.format("Veuillez fournir au moins {0} mots."),
		rangeWords: $.validator.format("Veuillez fournir entre {0} et {1} mots."),
		alphanumeric: "Veuillez fournir seulement des lettres, nombres, espaces et soulignages.",
		lettersonly: "Veuillez fournir seulement des lettres.",
		nowhitespace: "Veuillez ne pas inscrire d'espaces blancs.",
		phone: 'Veuillez fournir entrez un numéro de téléphone valide. La plupart des formats US / Canada et internationales acceptées.',
		ipv4: "Veuillez fournir une adresse IP v4 valide.",
		ipv6: "Veuillez fournir une adresse IP v6 valide.",
		ziprange: "Veuillez fournir un code postal entre 902xx-xxxx et 905-xx-xxxx.",
		zipcodeUS: "Les États-Unis Code postal spécifié n'est pas valide",
		integer: "Veuillez fournir un nombre non décimal qui est positif ou négatif.",
		vfbUsername: "Ce nom d'utilisateur est déjà enregistré. S'il vous plaît choisir un autre"
	});
}(jQuery));