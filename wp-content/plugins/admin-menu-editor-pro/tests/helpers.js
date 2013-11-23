// Helper functions that can be used anywhere in the test suite.
var ameTest = {
	thenLogin: function(username, password) {
		//Check if already logged in as this user. Note: username does not always match display name.
		if ( this.isLoggedIn(username) ) {
			return;
		}

		casper.thenOpen(ameTestConfig.siteUrl + '/wp-login.php', function() {
			casper.fill('form[name="loginform"]', {
				'log': username,
				'pwd': password
			}, true);
		});
	},
	
	isLoggedIn: function(displayName) {
		var currentDisplayName = casper.evaluate(function() {
			return jQuery('#wpadminbar').find('.display-name').text().trim();
		});

		if ( currentDisplayName == '' ) {
			return false;
		}
		return displayName ? (currentDisplayName == displayName) : true;
	},
	
	thenLoginAsAdmin: function() {
		this.thenLogin(ameTestConfig.adminUsername, ameTestConfig.adminPassword);
	},

	thenOpenMenuEditor: function() {
		//Go to the menu editor page (unless it's already open).
		var editorUrl = ameTestConfig.adminUrl + '/options-general.php?page=menu_editor';
		casper.thenOpen(editorUrl);
	},

	isMenuEditorPage: function() {
		return casper.exists('#ws_menu_editor');
	},

	loadDefaultMenu: function() {
		casper.click('#ws_load_menu');
	},

	activateHelper: function(name) {
		casper.thenOpen(ameTestConfig.siteUrl + '?ame-activate-helper=' + name,
			function() {
				casper.test.info('Helper "' + name + '" loaded');
			}
		);
	},

	deactivateHelper: function(name) {
		casper.thenOpen(ameTestConfig.siteUrl + '?ame-deactivate-helper=' + name,
			function() {
				casper.test.info('Helper "' + name + '" unloaded');
			}
		);
	},

	deactivateAllHelpers: function() {
		casper.thenOpen(ameTestConfig.siteUrl + '?ame-deactivate-helpers=1');
	},

	resetPluginConfiguration: function() {
		this.activateHelper('reset-configuration');
		this.deactivateHelper('reset-configuration');
	},

	/**
	 * Select a menu item in the menu editor based on its title.
	 *
	 * @param {String} menuTitle
	 * @param  {String} [itemTitle]
	 * @param {Boolean} [expand] Whether to also expand the menu editor widget. Defaults to false.
	 */
	selectItemByTitle: function(menuTitle, itemTitle, expand) {
		if ( !this.isMenuEditorPage() ) {
			throw new Error('You must be on the menu editor page to select a menu.');
		}
		return casper.evaluate(
			function(menu, item, expand) {
				item = item || null;

				//Find and click the specified top-level menu
				var menuNode = jQuery('#ws_menu_box')
					.find('.ws_item_title:contains("' + menu + '")')
					.first()
					.closest('.ws_container');
				var selected = menuNode.click().length > 0;

				if ( item ) {
					//Click the submenu
					var itemNode = jQuery('#ws_submenu_box')
						.find('.ws_submenu:visible .ws_item_title:contains("' + item + '")')
						.first()
						.closest('.ws_container');
					selected = selected && (itemNode.click().length > 0);

					if ( selected && expand && !itemNode.find('.ws_edit_link').hasClass('.ws_edit_link_expanded') ) {
						itemNode.find('.ws_edit_link').click();
					}
				} else {
					if ( selected && expand && !menuNode.find('.ws_edit_link').hasClass('.ws_edit_link_expanded') ) {
						menuNode.find('.ws_edit_link').click();
					}
				}

				return selected;
			},
			menuTitle, itemTitle, expand
		);
	},

	/**
	 * Add a new top-level menu and optionally set its properties.
	 *
	 * @param {Object} [properties]
	 */
	addNewMenu: function(properties) {
		casper.click('#ws_new_menu');

		if ( properties ) {
			this.setItemFields(properties, 'menu');
		}
	},

	/**
	 * Add a new item to the currently selected submenu.
	 *
	 * @param {Object} [properties]
	 */
	addNewItem: function(properties) {
		casper.click('#ws_new_item');

		if ( properties ) {
			this.setItemFields(properties, 'submenu');
		}
	},

	/**
	 * Set the currently selected menu's editor fields to the specified values.
	 *
	 * @param {Object} properties Dictionary of field names and their values.
	 * @param {String} [level] Which menu to edit. "menu" - current top-level menu, "submenu" - current submenu item.
	 */
	setItemFields: function(properties, level) {
		level = level || 'menu';
		// Caution: If the first argument to casper.evaluate() is an object Casper will treat it
		// as a list of arguments to pass to the callback. So we must pass "properties" as the second arg.
		casper.evaluate(function(level, properties) {
			var itemSelector = '';
			if ( level === 'menu' ) {
				itemSelector = '.ws_menu.ws_active';
			} else {
				itemSelector = '.ws_submenu:visible .ws_item.ws_active';
			}
			var item = jQuery('#ws_menu_editor').find(itemSelector).closest('.ws_container');
			jQuery.each(properties, function(name, value) {
				item.find('.ws_edit_field-' + name + ' .ws_field_value').val(value).change();
			});
		}, level, properties);
	}
};

// Always click "OK" in confirmation pop-ups. At the moment, 
// there's just one - it appears when loading the default menu.
casper.setFilter('page.confirm', function() {
	//casper.test.comment('confirm(): ' + message);
	return true;
});