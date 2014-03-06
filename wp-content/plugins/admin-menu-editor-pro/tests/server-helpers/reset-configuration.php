<?php
/**
 * Reset all plugin configuration (as opposed to resetting just the custom menu).
 */

delete_site_option('ws_menu_editor_pro');
delete_option('ws_menu_editor_pro');

delete_site_option('ws_menu_editor');
delete_option('ws_menu_editor');