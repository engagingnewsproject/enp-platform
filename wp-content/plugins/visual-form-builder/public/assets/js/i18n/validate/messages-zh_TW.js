/*
 * Translated default messages for the jQuery validation plugin.
 * Locale: ZH (Chinese; 中文 (Zhōngwén), 汉语, 漢語)
 * Region: TW (Taiwan)
 */
(function($) {
	$.extend($.validator.messages, {
		required: "必須填寫",
		remote: "請修正此欄位",
		email: "請輸入有效的電子郵件",
		url: "請輸入有效的網址",
		date: "請輸入有效的日期",
		dateISO: "請輸入有效的日期 (YYYY-MM-DD)",
		number: "請輸入正確的數值",
		digits: "只可輸入數字",
		creditcard: "請輸入有效的信用卡號碼",
		equalTo: "請重複輸入一次",
		accept: "請輸入有效的後綴",
		maxlength: $.validator.format("最多 {0} 個字"),
		minlength: $.validator.format("最少 {0} 個字"),
		rangelength: $.validator.format("請輸入長度為 {0} 至 {1} 之間的字串"),
		range: $.validator.format("請輸入 {0} 至 {1} 之間的數值"),
		max: $.validator.format("請輸入不大於 {0} 的數值"),
		min: $.validator.format("請輸入不小於 {0} 的數值"),
		maxWords: $.validator.format("請輸入{0}字以內。"),
		minWords: $.validator.format("請輸入至少{0}字。"),
		rangeWords: $.validator.format("請{0}和{1}字之間進入。"),
		alphanumeric: "字母，數字和下劃線只請",
		lettersonly: "英皇只請",
		nowhitespace: "沒有空格，請",
		phone: '請輸入一個有效的電話號碼。接受大多數美國/加拿大和國際格式。',
		ipv4: '請輸入一個有效的IP地址卷。',
		ipv6: '請輸入一個有效的IP v6地址。',
		ziprange: '您的郵遞區號必須在範圍902xx-XXXX905-XX-XXXX',
		zipcodeUS: '指定美國郵政編碼無效',
		integer: '一個正或負的非十進制數，請',
		vfbUsername: '此用戶名已被註冊。請選擇另外一個'
	});
}(jQuery));
