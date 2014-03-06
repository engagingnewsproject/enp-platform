casper.start();
casper.test.comment('Set a custom icon for one of the built-in menus.');

ameTest.deactivateAllHelpers();
ameTest.thenLoginAsAdmin();
ameTest.thenOpenMenuEditor();

casper.then(function() {
	ameTest.loadDefaultMenu();
	ameTest.selectItemByTitle('Media', null, true);
	casper.click('.ws_menu.ws_active .ws_toggle_advanced_fields');
	ameTest.setItemFields({
		'icon_url': 'images/loading.gif' //Change to something more appropriate if I ever add custom icons to AME.
	});
	casper.click('#ws_save_menu');
});

casper.waitForSelector('#message.updated', function() {
	casper.test.assertExists('#menu-media', 'The "Media" menu exists');
	casper.test.assertDoesntExist('#menu-media.menu-icon-media', "The default icon class has been removed");
	casper.test.assertExists(
		'#menu-media .wp-menu-image img[src="images/loading.gif"]',
		"The custom icon exists and has the right URL"
	);
});

casper.then(function() {
	casper.test.comment('Test the icon selector widget');

	ameTest.selectItemByTitle('Media', null, true);
	casper.click('.ws_menu.ws_active .ws_toggle_advanced_fields');

	casper.click('.ws_menu.ws_active .ws_select_icon');
	casper.test.assertVisible('#ws_icon_selector', 'Clicking the icon button displays the icon selector');

	casper.test.assertEvalEquals(
		function() {
			return jQuery('#ws_icon_selector').find('.ws_selected_icon').data('icon-url');
		},
		'images/loading.gif',
		'The custom icon is marked as selected'
	);

	//Change the icon of the "Media" menu to the built-in "Tools" icon.
	casper.click('#ws_icon_selector .ws_icon_option[data-icon-class="menu-icon-tools"]');
	casper.test.assertEval(function() {
		return !jQuery('#ws_icon_selector').is(':visible');
	}, 'Clicking one of the available icons hides the icon selector');

	casper.click('.ws_menu.ws_active .ws_select_icon');
	casper.test.assertEvalEquals(
		function() {
			return jQuery('#ws_icon_selector').find('.ws_selected_icon').data('icon-class');
		},
		'menu-icon-tools',
		'The clicked icon is correctly marked as selected'
	);

	casper.click('#ws_save_menu');
});

casper.waitForSelector('#message.updated', function() {
	casper.test.assertExists(
		'#menu-media.menu-icon-tools',
		'The menu icon class was successfully changed using the icon selector'
	);
	casper.test.assertDoesntExist(
		'#menu-media .wp-menu-image img',
		'Selecting an icon class removes the custom icon URL (if any)'
	);
});

casper.run(function() {
    this.test.done();
});