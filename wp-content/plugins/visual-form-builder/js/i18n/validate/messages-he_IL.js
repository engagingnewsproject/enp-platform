/*
 * Translated default messages for the jQuery validation plugin.
 * Locale: HE (Hebrew; עברית)
 */
(function($) {
	$.extend($.validator.messages, {
		required: "השדה הזה הינו שדה חובה",
		remote: "נא לתקן שדה זה",
		email: "נא למלא כתובת דוא\"ל חוקית",
		url: "נא למלא כתובת אינטרנט חוקית",
		date: "נא למלא תאריך חוקי",
		dateISO: "נא למלא תאריך חוקי (ISO)",
		number: "נא למלא מספר",
		digits: "נא למלא רק מספרים",
		creditcard: "נא למלא מספר כרטיס אשראי חוקי",
		equalTo: "נא למלא את אותו ערך שוב",
		accept: "נא למלא ערך עם סיומת חוקית",
		maxlength: $.validator.format(".נא לא למלא יותר מ- {0} תווים"),
		minlength: $.validator.format("נא למלא לפחות {0} תווים"),
		rangelength: $.validator.format("נא למלא ערך בין {0} ל- {1} תווים"),
		range: $.validator.format("נא למלא ערך בין {0} ל- {1}"),
		max: $.validator.format("נא למלא ערך קטן או שווה ל- {0}"),
		min: $.validator.format("נא למלא ערך גדול או שווה ל- {0}"),
		maxWords: $.validator.format("נא להזין את {0} מילות או פחות."),
		minWords: $.validator.format("נא להזין לפחות מילות {0}. "),
		rangeWords: $.validator.format("נא להזין בין {0} ו {1} מילות. "),
		alphanumeric: "אותיות, מספרים, ומדגיש רק בבקשה ",
		lettersonly: "רק אותיות בבקשה ",
		nowhitespace: "אין חלל לבן בבקשה",
		phone: 'נא להזין מספר טלפון חוקי. רוב פורמטי ארה"ב / קנדה והבינלאומית מקובלים. ',
		ipv4: 'נא להזין את כתובת ה-IP v4 בתוקף. ',
		ipv6: 'נא להזין את כתובת ה-IP v6 בתוקף. ',
		ziprange: 'מיקוד שלך חייב להיות ב902xx-XXXX טווח ל905-XX-XXXX ',
		zipcodeUS: 'המיקוד בארה"ב שצוין אינו חוקי ',
		integer: 'מספר שאינו עשרוני חיובי או שלילי בבקשה ',
		vfbUsername: 'שם משתמש זה כבר רשום. אנא בחר אחד אחר'
	});
}(jQuery));