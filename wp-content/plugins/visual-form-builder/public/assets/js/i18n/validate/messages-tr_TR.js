/*
 * Translated default messages for the jQuery validation plugin.
 * Locale: TR (Turkish; Türkçe)
 */
(function($) {
	$.extend($.validator.messages, {
		required: "Bu alanın doldurulması zorunludur.",
		remote: "Lütfen bu alanı düzeltin.",
		email: "Lütfen geçerli bir e-posta adresi giriniz.",
		url: "Lütfen geçerli bir web adresi (URL) giriniz.",
		date: "Lütfen geçerli bir tarih giriniz.",
		dateISO: "Lütfen geçerli bir tarih giriniz(ISO formatında)",
		number: "Lütfen geçerli bir sayı giriniz.",
		digits: "Lütfen sadece sayısal karakterler giriniz.",
		creditcard: "Lütfen geçerli bir kredi kartı giriniz.",
		equalTo: "Lütfen aynı değeri tekrar giriniz.",
		accept: "Lütfen geçerli uzantıya sahip bir değer giriniz.",
		maxlength: $.validator.format("Lütfen en fazla {0} karakter uzunluğunda bir değer giriniz."),
		minlength: $.validator.format("Lütfen en az {0} karakter uzunluğunda bir değer giriniz."),
		rangelength: $.validator.format("Lütfen en az {0} ve en fazla {1} uzunluğunda bir değer giriniz."),
		range: $.validator.format("Lütfen {0} ile {1} arasında bir değer giriniz."),
		max: $.validator.format("Lütfen {0} değerine eşit ya da daha küçük bir değer giriniz."),
		min: $.validator.format("Lütfen {0} değerine eşit ya da daha büyük bir değer giriniz."),
		maxWords: $.validator.format("Lütfen {0} kelime veya daha az girin."),
		minWords: $.validator.format("Lütfen en az {0} kelimeleri giriniz."),
		rangeWords: $.validator.format("Lütfen {0} ve {1} kelimeler arasına girin."),
		alphanumeric: "Harfler, sayılar ve alt çizgi sadece lütfen",
		lettersonly: "Mektuplar sadece lütfen",
		nowhitespace: "Hiçbir boşluk lütfen",
		phone: 'Geçerli bir telefon numarası giriniz. Çoğu ABD / Kanada ve Uluslararası formatları kabul.',
		ipv4: 'Geçerli bir IP v4 adresini girin.',
		ipv6: 'Geçerli bir IP v6 adresini girin.',
		ziprange: 'Posta kodu 905-xx-xxxx aralık 902xx-xxxx olmalı',
		zipcodeUS: 'Belirtilen ABD Posta Kodu geçersiz',
		integer: 'Bir pozitif veya negatif olmayan ondalık sayı lütfen',
		vfbUsername: 'Bu kullanıcı adı zaten kayıtlı. Başka bir tane seçiniz'
	});
}(jQuery));