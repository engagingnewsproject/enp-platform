=== Admin Menu Editor Pro ===
Contributors: whiteshadow
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=A6P9S6CE3SRSW
Tags: admin, dashboard, menu, security, wpmu
Requires at least: 3.3
Tested up to: 3.7.1
Stable tag: 1.91

Lets you directly edit the WordPress admin menu. You can re-order, hide or rename existing menus, add custom menus and more.

== Description ==
Pro version of the Admin Menu Editor plugin. Lets you manually edit the Dashboard menu. You can reorder the menus, show/hide specific items, change access rights, and more. 

[Get the latest version here.](http://adminmenueditor.com/updates/)

**Pro Version Features**

- Import/export custom menus.
- Set menu items to open in a new window or IFrame.
- Improved, role-based menu permissions interface.
- Use shortcodes in menu fields.
- Create menus accessible only to a specific user (by setting extra capability to "user:username").

**Other Features**

- Change menu title, access rights, URL and more. 
- Create custom menus.
- Sort items using a simple drag & drop interface.
- Move items to a different submenus.
- Hide any menu or submenu item.
- Supports WordPress MultiSite/WPMU.

**Requirements**

- WordPress 3.3 or later

_For maximum compatibility and security, using a modern web browser such as Firefox, Opera, Chrome or Safari is recommended. Certain advanced features (e.g. menu import) may not work reliably or at all in Internet Explorer and other outdated browsers._

== Installation ==

_If you already have the free version of Admin Menu Editor installed, deactivate it before installing the Pro version._

**Normal installation**

1. Download the admin-menu-editor-pro.zip file to your computer.
1. Unzip the file.
1. Upload the `admin-menu-editor-pro` directory to your `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.

That's it. You can access the the menu editor by going to _Settings -> Menu Editor Pro_. The plugin will automatically load your current menu configuration the first time you run it.

**WP MultiSite installation**

If you have WordPress set up in multisite ("Network") mode, you can also install Admin Menu Editor as a global plugin. This will enable you to edit the Dashboard menu for all sites and users at once.

1. Download the admin-menu-editor-pro.zip file to your computer.
1. Unzip the file.
1. Create a new directory named `mu-plugins` in your site's `wp-content` directory (unless it already exists).
1. Upload the `admin-menu-editor-pro` directory to `/wp-content/mu-plugins/`.
1. Move `admin-menu-editor-mu.php` from `admin-menu-editor-pro/includes` to `/wp-content/mu-plugins/`.

Plugins installed in the `mu-plugins` directory are treated as "always on", so you don't need to explicitly activate the menu editor. Just go to _Settings -> Menu Editor_ and start customizing your admin menu :)

_Notes_

- Instead of installing Admin Menu Editor in `mu-plugins`, you can also install it normally and then activate it globally via "Network Activate". However, this will make the plugin visible to normal users when it is inactive (e.g. during upgrades).
- When Admin Menu Editor is installed in `mu-plugins` or activated via "Network Activate", only the "super admin" user can access the menu editor page. Other users will see the customized Dashboard menu, but be unable to edit it.
- It is currently not possible to install Admin Menu Editor as both a normal and global plugin on the same site.

== Notes ==
Here are some usage tips and other things that can be good to know when using the menu editor : 

- WordPress uses the concept of [roles and capabilities](http://codex.wordpress.org/Roles_and_Capabilities "An overview of roles and capabilities") to manage access rights. Each Dashboard menu item has an associated capability setting, and only the users that possess that capability can see and use that menu item.

- "Hidden" menus are invisible to everyone - including the administrator - but they can still be accessed via the direct page URL. To make a menu inaccessible to certain users, edit its permissions.

- If you delete any of the default menus they will reappear after saving. This is by design. To get rid of a menu for good, either hide it or set it's access rights to a higher level.

== Changelog ==

[Get the latest version here.](http://adminmenueditor.com/updates/)

= 1.91 =
* Added a number of small performance optimizations.
* Added an admin notice that explains how to get to the menu editor page.
* The plugin will now display an "are you sure?" dialog if you try to hide "Dashboard -> Home". Hiding this menu item can prevent people from logging in unless you also change what page they are redirected to upon login.
* Deprecated the "Show/Hide" toolbar button. Use role permissions or custom capabilities instead. You can still enable the button through the settings page if you need it.
* Fixed: Opening a modal dialog like "Permissions" or "Import menu" will now properly grey out the rest of the page.
* Tested with WordPress 3.7.1 and 3.8-alpha.

= 1.90 =
* Added a new settings page that lets you choose whether admin menu settings are per-site or network-wide, as well as specify who can access the plugin. To access this page, go to "Settings -> Menu Editor Pro" and click the small "Settings" link next to the page title.
* Added an option to hide the plugin from the list of installed plugins.
* Added a way to show/hide advanced menu options through the settings page in addition to the "Screen Options" panel.
* Added a "Show menu access checks" option to make debugging menu permissions easier.
* Added a "toggle all menus for the selected role" button.
* Added partial WPML support. Now you can translate custom menu titles with WPML.
* Replaced a number of toolbar icons with GPL-compatible alternatives so that they're consistent with the free version.
* The plugin will now display an error if you try to activate it when another version of it is already active.
* Fixed a bug where the capability drop-down list would briefly show up and then immediately disappear.

= 1.83 =
* Fixed an "invalid argument" warning caused by the plugin sometimes failing to retrieve a user's roles.
* Fixed a rare issue where the user would be unable to make a sub-menu item visible if a top-level menu with the same URL was hidden (e.g. hide "Posts", show "Posts -> All Posts" in another menu). Creating duplicate menus is still not recommended.
* Fixed a conflict with some CSS/JS auto-versioning schemes that could prevent the user from saving the menu.

= 1.82 =
* Added an "Upgrade or renew license" link to the license information screen.
* Added license expiration date (if applicable) to the license information screen.
* Fixed plugin updates not showing up in ManageWP.
* Fixed a rare bug where the icon selector would appear at the bottom of the page instead of next to the icon button.
* Fixed incorrect icon alignment and checkboxes showing up in the wrong place when using the MP6 admin UI.
* Removed "site token" from the license information screen.

= 1.81 =
* Added a workaround for a bug in BackupBuddy that would cause Admin Menu Editor Pro to check for updates much more often than it's supposed to.

= 1.80 =
* Added an icon drop-down that lets you pick one of the default WordPress menu icons or upload your own through the media library.
* The editor will now automatically re-select the last selected role after saving a menu.
* You can hold Ctrl while moving a menu item from a submenu to a top-level menu (or vice versa) to copy instead of moving.
* Changed the way permission checkboxes are displayed for menus that have some visible and some hidden items.
* Switched to a different, more subtle submenu separator style.
* Moved the "Icon URL" field below the "CSS classes" field.
* Made the "broken editor permissions" error message more helpful.
* Fixed menus with custom icons sometimes displaying two overlapping icons.
* Fixed the "Window title" setting not working for anything except custom menus.
* Fixed a hard-to-identify bug where one user would get "$(...).ajaxForm is not a function" errors all the time.
* Fixed compatibility issues with Ultimate TinyMCE.

= 1.70 =
* Added the ability to create menu separators in submenus.
* Fixed a bug where the height of the IFrame element generated for menus set to display in a frame would be limited to 300px (WP 3.5).
* Fixed a rare "call to a member function function get_virtual_caps() on a non-object" error.
* Fixed a couple of layout glitches that would make the editor sidebar display incorrectly in WP 3.5.
* Fixed: Ensure that the correct menu item gets highlighted when adding a new item of a custom post type, even if there's no "Add New {Thing}" entry in the admin menu. This fixes the bug where clicking the "Add New" button in WooCommerce -> Coupons would highlight the Posts -> Add New menu.
* Other minor fixes.
* Tested on WordPress 3.5 (RC6).

= 1.60 =
* Added a number of small optimizations. On some systems menu output time is reduced by almost 30%.
* Fixed a couple of PHP warnings caused by a bug in the custom update checker.
* Fixed a conflict with plugins that use an old version of the custom update checker.
* Changed how the plugin treats situations where multiple menu items have the same URL. Now either all of them will show up in the final menu or none will. However, it's still best to keep your menu free from duplicate items to avoid ambiguity.

= 1.50 =
* Added support for fully automatic plugin upgrades. You will need to enter a license key to enable this feature.
* Existing users will get a license key when they download this version. New users will get one in their purchase confirmation email.
* Added a "Check for updates" link to the plugin's entry in the "Plugins" page.

= 1.40 =
* Added a new way to view and set per-role permissions. There's now a role selector at the top of the menu editor page, and selecting a role grays out any menu items that role can't access. You can also change menu permissions for the currently selected role via checkboxes in menu titles.
* Added a warning to menus that are only accessible by a specific role due to hardcoded restrictions in the plugin(s) responsible for those menus.
* Added a warning if saving the menu would make it impossible for the current user to access the menu editor. This doesn't catch 100% of configuration problems, but should help.
* Re-added the ability to reset permissions to default. 
* Fixed incompatibility with Ozh's Admin Drop Down Menu.
* Fixed: Going to http://example.com/wp-admin/ now expands and highlights Dashboard -> Home as the current menu. Previously it didn't highlight any menus for that URL.
* Fixed: Newly created items sometimes didn't reflect the actual menu settings. This was a problem when loading the default menu or undoing changes.
* Fixed: When displaying a menu, remove all duplicate separators, not just every other one.
* Fixed: Only consider Super Admins when in multisite mode. On single-install sites, they're just identical to normal administrators.
* Fixed: If the currently open menu page has been moved to a different parent, clicking it's original parent menu would briefly flash the submenu as if it was currently active.
* Make the capability that's required to use the plugin user-configurable (via the "admin_menu_editor_capability" filter).
* When adding a new menu, sub-menu or separator, insert them after the currently selected item instead of at the end of the list.
* Only display the survey notice on the editor page, and let the user disable it by adding a `never-display-surveys.txt` file to the plugin folder.

= 1.30 =
* Added a new user survey. The link only shows up after you've used the plugin for a few days.
* Fixed a bug where menus that by default require a specific role (e.g. "administrator") and not a normal capability would become inaccessible immediately after activating the plugin.
* Fixed the wrong menu to being expanded/highlighted sometimes.
* Fixed a deprecated function notice.
* Fixed compatibility issues with sites using SSL.
* Fixed a number of complex bugs related to moved menu items.

= 1.20 =
* Revamped the permissions interface. Now you can directly select the roles that should or shouldn't be able to access a menu instead of trying to find a capability that matches your requirements. You can still set the capability if you want.
* Added a "Target page" field that lets you select what page a menu item should link to. This is less error-prone than entering the URL manually. Of course, if you *do* want to use custom URL, you can select "Custom" and type in your own URL.
* Fixed a bug that would cause certain menu items to not be highlighted properly if they've been moved to a different top-level menu.
* Made it possible to move a sub-menu item to the top level and vice versa. Drag the item in question to the very end of the (sub-)menu and drop it on the yellow rectangle that will appear.
* You can now cut & paste menu items between the sub-menus and the top level menu.
* You can now use "not:capability", "capability1,capability2", "capability1+capability2" and other advanced syntax in the capability field.
* Added a new menu export format. Old exported menus should still work.
* Items that point to missing menu pages (e.g. a page belonging to a plugin that has been deactivated) are automatically removed. Previously, they would still show up in the editor until you removed them manually.
* All custom menus now use the same set of defaults.
* Custom menus (i.e. menus with custom URLs) can no longer use defaults associated with default menus.

= 1.16 =
* Removed the "Feedback" button. You can still provide feedback via email or the contact form, of course.

= 1.15 =
* Fixed a PHP warning when no custom menu exists (yet).

= 1.14 =
* Fixed menu export not working when WP_DEBUG is set to True.
* Fixed the updater's cron hook not being removed when the plugin is deactivated.
* Fixed updates not showing up in some situations.
* Fixed the "Feedback" button not responding to mouse clicks in some browsers.
* Enforce the custom menu order by using the 'menu_order' filter. Fixes Jetpack menu not staying put.
* Fixed "Feedback" button style to be consistent with other WP screen meta buttons.
* You can now copy/paste as many menu separators as you like without worrying about some of them mysteriously disappearing on save.
* Fixed a long-standing copying related bug where copied menus would all still refer to the same JS object instance.
* Added ALT attributes to the toolbar icon images.
* Removed the "Custom" checkbox. In retrospect, all it did was confuse people.
* Made it impossible to edit separator properties.
* Removed the deprecated "level_X" capabilities from the "Required capability" dropdown. You can still type them in manually if you want.

= 1.13 =
* Tested for WordPress 3.2 compatibility.

= 1.12 =
* Fixed a "failed to decode input" error that would show up when saving the menu.

= 1.11 = 
* WordPress 3.1.3 compatibility. Should also be compatible with the upcoming 3.2.
* Fixed spurious slashes sometimes showing up in menus.
* Fixed a fatal error concerning "Services_JSON".

= 1.1 = 
* WordPress 3.1 compatibility.
* Added the ability to drag & drop a menu item to a different menu.
* You can now set the "Required capability" to a username. Syntax: "user:john_smith".
* Added a drop-down list of Dashboard pages to the "File" box.
* When the menu editor is opened, the first top-level menu is now automatically selected and it's submenu displayed. Hopefully, this will make the UI slightly easier to understand for first-time users.
* All corners rounded on the "expand" link when not expanded.
* By popular request, the "Menu Editor" menu entry can be hidden again.
