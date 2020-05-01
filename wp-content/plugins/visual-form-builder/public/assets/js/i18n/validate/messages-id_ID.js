/*
 * Translated default messages for the jQuery validation plugin.
 * Locale: ID (Indonesian)
 */
(function($) {
	$.extend($.validator.messages, {
		required: "Field ini harus diisi.",
		remote: "Harap perbaiki field ini.",
		email: "Harap masukan alamat email yang valid.",
		url: "Harap masukan URL yang valid.",
		date: "Harap masukan tanggal yang valid.",
		dateISO: "Harap masukan tanggal yang valid (ISO).",
		number: "Harap masukan angka yang valid.",
		digits: "Harap masukan hanya digit.",
		creditcard: "Harap masukan nomor kartu kredit yang valid.",
		equalTo: "Harap masukan nilai yang sama lagi.",
		accept: "Harap masukan nilai dengan ekstensi yang valid.",
		maxlength: $.validator.format("Harap masukan tidak lebih dari {0} karakter."),
		minlength: $.validator.format("Harap masukan sedikitnya {0} karakter."),
		rangelength: $.validator.format("Harap masukan antara {0} dan {1} karakter."),
		range: $.validator.format("Harap masukan nilai antara {0} dan {1}."),
		max: $.validator.format("Harap masukan nilai lebih kecil atau sama dengan {0}."),
		min: $.validator.format("Harap masukan nilai lebih besar atau sama dengan {0}."),
		maxWords: $.validator.format("Harap masukan {0} kata atau kurang."),
		minWords: $.validator.format("Harap masukan sedikitnya {0} kata."),
		rangeWords: $.validator.format("Harap masukan antara {0} dan {1} kata."),
		alphanumeric: "Harap masukan hanya karakter, angka atau garis bawah.",
		lettersonly: "Harap masukan hanya karakter.",
		nowhitespace: "Tidak boleh ada spasi.",
		phone: 'Masukkan nomor telepon yang valid. Kebanyakan format AS / Kanada dan International diterima.',
		ipv4: 'Silahkan masukkan alamat IP v4 yang valid.',
		ipv6: 'Silahkan masukkan alamat IP v6 valid.',
		ziprange: 'Anda ZIP-kode harus dalam kisaran 902xx-xxxx untuk 905-xx-xxxx',
		zipcodeUS: 'AS ZIP Code yang ditentukan tidak valid',
		integer: 'Sejumlah non-desimal positif atau negatif silahkan',
		vfbUsername: 'Username sudah terdaftar. Silahkan pilih yang lain'
	});
}(jQuery));