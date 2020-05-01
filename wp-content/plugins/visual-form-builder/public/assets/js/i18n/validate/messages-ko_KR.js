/*
 * Translated default messages for the jQuery validation plugin.
 * Locale: KO (Korean; 한국어)
 */
(function($) {
	$.extend($.validator.messages, {
		required: "필수 항목입니다.",
		remote: "항목을 수정하세요.",
		email: "유효하지 않은 E-Mail주소입니다.",
		url: "유효하지 않은 URL입니다.",
		date: "올바른 날짜를 입력하세요.",
		dateISO: "올바른 날짜(ISO)를 입력하세요.",
		number: "유효한 숫자가 아닙니다.",
		digits: "숫자만 입력 가능합니다.",
		creditcard: "신용카드 번호가 바르지 않습니다.",
		equalTo: "같은 값을 다시 입력하세요.",
		accept: "올바른 확장자가 아닙니다.",
		maxlength: $.validator.format("{0}자를 넘을 수 없습니다. "),
		minlength: $.validator.format("{0}자 이상 입력하세요."),
		rangelength: $.validator.format("문자 길이가 {0} 에서 {1} 사이의 값을 입력하세요."),
		range: $.validator.format("{0} 에서 {1} 사이의 값을 입력하세요."),
		max: $.validator.format("{0} 이하의 값을 입력하세요."),
		min: $.validator.format("{0} 이상의 값을 입력하세요."),
		maxWords: $.validator.format("{0} 단어 이하를 입력 해 주시기 바랍니다."),
		minWords: $.validator.format("적어도 {0} 단어를 입력 해주세요."),
		rangeWords: $.validator.format("{0}과 {1} 단어 사이에 입력하십시오."),
		alphanumeric: "문자, 숫자, 그리고 밑줄 만주세요",
		lettersonly: "문자 만주세요",
		nowhitespace: "에 공백하시기 바랍니다",
		phone: '올바른 전화 번호를 입력하세요. 대부분의 미국 / 캐나다 및 국제 형식으로 받아 들였다.',
		ipv4: '유효한 IP V4 주소를 입력하십시오.',
		ipv6: '유효한 IP V6 주소를 입력 해주십시오.',
		ziprange: '귀하의 ZIP 코드는 905-XX-XXXX의 범위의 902xx-XXXX에 있어야합니다',
		zipcodeUS: '지정된 미국의 우편 번호가 잘못되었습니다',
		integer: '양수 또는 음수가 아닌 십진수주세요',
		vfbUsername: '이 사용자 이름은 이미 등록되어 있습니다. 또 다른 하나를 선택하십시오'
	});
}(jQuery));