<?php
/**
 * This test helper creates a bunch of admin menus in different locations.
 * Useful for testing the editor's ability to move/copy/add plugin menus.
 */

add_action('admin_menu', function() {
	add_options_page(
		'Dummy Settings',
		'Dummy Settings',
		'read',
		'dummy-settings',
		'amt_output_page'
	);

	add_comments_page(
		'Dummy Comments',
		'Dummy Comments',
		'read',
		'dummy-comments',
		'amt_output_page'
	);

	add_menu_page(
		'Dummy Top Menu',
		'Dummy Top Menu',
		'read',
		'dummy-top-menu',
		'amt_output_page'
	);

	add_submenu_page(
		'dummy-top-menu',
		'Dummy Submenu #1',
		'Dummy Submenu #1',
		'read',
		'dummy-submenu-1',
		'amt_output_page'
	);

	add_submenu_page(
		'dummy-top-menu',
		'Dummy Submenu #2',
		'Dummy Submenu #2',
		'read',
		'dummy-submenu-2',
		'amt_output_page'
	);

	add_dashboard_page(
		'Dummy Dashboard',
		'Dummy Dashboard',
		'read',
		'dummy-dashboard-page',
		'amt_output_page'
	);

	//A top-level menu with no submenus.
	add_menu_page(
		'The Dummy',
		'The Dummy',
		'read',
		'dummy-menu-with-no-items',
		'amt_output_page'
	);
});

function amt_output_page() {
	echo 'This is a dummy page.';
}