/*
 * Translated default messages for the jQuery validation plugin.
 * Locale: ZH (Chinese, 中文 (Zhōngwén), 汉语, 漢語)
 */
(function($) {
	$.extend($.validator.messages, {
		required: "必须填写",
		remote: "请修正此栏位",
		email: "请输入有效的电子邮件",
		url: "请输入有效的网址",
		date: "请输入有效的日期",
		dateISO: "请输入有效的日期 (YYYY-MM-DD)",
		number: "请输入正确的数字",
		digits: "只可输入数字",
		creditcard: "请输入有效的信用卡号码",
		equalTo: "你的输入不相同",
		accept: "请输入有效的后缀",
		maxlength: $.validator.format("最多 {0} 个字"),
		minlength: $.validator.format("最少 {0} 个字"),
		rangelength: $.validator.format("请输入长度为 {0} 至 {1} 之間的字串"),
		range: $.validator.format("请输入 {0} 至 {1} 之间的数值"),
		max: $.validator.format("请输入不大于 {0} 的数值"),
		min: $.validator.format("请输入不小于 {0} 的数值"),
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