/*
 * Translated default messages for the jQuery validation plugin.
 * Locale: JA (Japanese; 日本語)
 */
(function($) {
	$.extend($.validator.messages, {
		required: "このフィールドは必須です。",
		remote: "このフィールドを修正してください。",
		email: "有効なEメールアドレスを入力してください。",
		url: "有効なURLを入力してください。",
		date: "有効な日付を入力してください。",
		dateISO: "有効な日付（ISO）を入力してください。",
		number: "有効な数字を入力してください。",
		digits: "数字のみを入力してください。",
		creditcard: "有効なクレジットカード番号を入力してください。",
		equalTo: "同じ値をもう一度入力してください。",
		accept: "有効な拡張子を含む値を入力してください。",
		maxlength: $.validator.format("{0} 文字以内で入力してください。"),
		minlength: $.validator.format("{0} 文字以上で入力してください。"),
		rangelength: $.validator.format("{0} 文字から {1} 文字までの値を入力してください。"),
		range: $.validator.format("{0} から {1} までの値を入力してください。"),
		max: $.validator.format("{0} 以下の値を入力してください。"),
		min: $.validator.format("{0} 以上の値を入力してください。"),
		maxWords: $.validator.format("{0}字以内で入力してください。"),
		minWords: $.validator.format("少なくとも{0}の言葉を入力してください。"),
		rangeWords: $.validator.format("{0}と{1}単語の間で入力してください。"),
		alphanumeric: "文字、数字、およびアンダースコアのみでお願いします",
		lettersonly: "手紙のみでお願いします",
		nowhitespace: "に空白をして下さい",
		phone: '有効な電話番号を入力してください。ほとんどの米国/カナダおよび国際フォーマットが受け入れた。',
		ipv4: '有効なIP V4アドレスを入力してください。',
		ipv6: '有効なIP V6アドレスを入力してください。',
		ziprange: 'あなたの郵便番号は905-XX-XXXXの範囲の902xx-XXXXである必要があります',
		zipcodeUS: '指定された米国の郵便番号が無効です',
		integer: '正または負の非小数ください',
		vfbUsername: 'このユーザー名はすでに登録されています。他の名前を選んでください'
	});
}(jQuery));