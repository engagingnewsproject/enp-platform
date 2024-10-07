function MAMMOTH_OPTIONS(mammoth) {

	var styleMap = [

			"p[style-name='endnote text'] => span.footnotes > span.footnote", // custom
			"r[style-name='endnote reference'] =>",
	];

	function setParagraphStyleNameFromFonts(fonts, styleName, skipStyleNameRegex) {
		return mammoth.transforms.paragraph(function(paragraph) {
				var runs = mammoth.transforms.getDescendantsOfType(paragraph, "run");
				var isMatch = runs.length > 0 && runs.every(function(run) {
						return run.font && fonts.indexOf(run.font.toLowerCase()) !== -1;
				});
				if (isMatch && !skipStyleNameRegex.test(paragraph.styleName)) {
						return {...paragraph, styleName: styleName};
				} else {
						return paragraph;
				}
		});
}
	
	return {
			styleMap: styleMap,
        // If a paragraph is entirely made up of runs that use monospace fonts,
        // update the paragraph to use the style "Code Block" if it doesn't
        // already have a "Code Block" style.
        transformDocument: setParagraphStyleNameFromFonts(
					["consolas", "courier", "courier new"],
					"Code Block",
					/^Code Block/i
			)
	};
}
