=== Advanced Code Editor ===
Contributors: bainternet 
Donate link:  http://en.bainternet.info/donations
Tags: code, theme editor, plugin editor, code editor, WordPress IDE
Requires at least: 3.0
Tested up to: 4.0.0
Stable tag: 2.2.6

Enables syntax highlighting in the integrated themes and plugins source code editors with line numbers, AutoComplete and much more. Supports PHP, HTML, CSS and JS.
 

== Description ==

Enables syntax highlighting in the integrated themes and plugins source code editors. Supports PHP, HTML, CSS and JS.  
Effectively edit your themes or plugins when you only have access to a browser, by enabling syntax highlighting in WordPress integrated source code editors. Supports PHP, HTML, CSS and JavaScript
[youtube http://www.youtube.com/watch?v=UUzH0tLIJE0]

**Features**

*	Mixed language Syntax highlighting (PHP,HTML,JavaScript and CSS)
*	Smart Search (string or regex).
*	Search and Replace.
*	Full Screen editor.
*	11 editor themes.
*	Multiple Undo & Redo (editor remembers all edits).
*	AutoComplete with over 3500 WordPress Functions.
*	Tested and Works with IE8,9,10 FF3.6 and up, chrome 8 and up.
*	Ajax save file. (NEW)
*	Ajax Create new file. (NEW)
*	Ajax Delete file. (NEW)
*	Ajax Create new directory. (NEW)
*	Jump To Line with hot key Crtl + G. (NEW)
*	File Tree View (NEW)
*	Download theme button.(NEW)
*	Download Plugin button.(NEW)
*	Download file button.(NEW)
*	Editor CodeFolding.(NEW)
*	Toggle file tree on/off.(NEW)
*	Download file button.(NEW)
*	Comment code out/in.(NEW)
*	Auto format and indenting code.(NEW)
*	Built-in version control.(NEW)



any feedback or suggestions are welcome.

check out my [other plugins][1]

Also Credits to marijn for his excellent CodeMirror JS library : http://marijn.haverbeke.nl/codemirror/

[1]: http://en.bainternet.info/category/plugins


== Installation ==
Simple steps:  

1.  Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation.
1.  Then activate the Plugin from Plugins page.
1.  Done!

== Frequently Asked Questions ==
=What are the requirements?=

PHP 5.2 and up.

=Its Not Working!=

Well, Yes it does, make sure you save the options and refresh.

=I have Found a Bug, Now what?=

Simply use the <a href=\"http://wordpress.org/tags/advanced-code-editor/?forum_id=10\">Support Forum</a> and thanks a head for doing that.
== Screenshots ==

1. The Editor

2. Editor with autocomplete

3. The editor search

4. The editor night theme


== Changelog ==
2.2.6
Fixed strict errors notice.

2.2.5
Fixed custom button image url
Made search boxes bigger.

2.2.4
Fixed `plugin generated XXX char...` notice.

2.2.3
Fixed missing jquery ui dialog issue

2.2.2
Changed remote google jquery ui to local.

2.2.1
fixed plugin and theme download (hopefully).

2.2.0
Added New File status notice. (no changes, last saved, has unsaved changes)
Fixed theme file and directory creation.
Fixed file ,theme and plugin download.
Major code rewrite for toolbar.
Added toolbar api (allows easy way of adding your own buttons and action).
Moved MOST of the strings into wp_localized_script() for easy translation.
Moved most of nonces creations to wp_localize_script().

2.1.6
Fixed Scroll issues.
Fixed RTL version.

2.1.5
updated codemirror lib to 2.34.
Fixed New File, New Directory bug in theme editor.
Fixed The auto-formatting has gone crazy issue, I think :)
Cleaned up some CSS code.

2.1.4
updated toolbar icons to a more WordPressish look. thanks to Farvig of http://codeshare.bakadesign.dk/ for designing them for us.

2.1.3 Fixed bugs caused by short php tag.
added plugin version to js files to avoid clearing cache.

2.1.2 Fixed WP_Debug issues

2.1.1 quick bug fixes fatal error on activation.

2.1 quick bug fixes download plugin and theme.
better download error handling.
fixed missing rubyblue theme.


2.0 Codemirror Js update to 2.2 .
added Editor CodeFolding.
added Settings panel with few Codemirror settings.
added Toggle file tree on/off
added Comment code out/in (with hot keys)
added Auto format and indenting code (with hot keys)
added Few new editor buttons
added Built-in version control
JS and CSS files are now called using wp_enqueue_script/style
Some Code cleanup
Better theme download functionallty
added uninstall plugin on plugin deletion
added database table for file versions
and a few more


1.9 Cleand jQuery Code a bit.
Added Download theme button.
Added Download Plugin button.
Added Download file button.

1.8 added file tree view to editor.

1.7 added 6 new themes and moved over to git svn.

1.6 Added an auto close for save box after 5 seconds and crusr position is restored as requested by James Davis (Thanks).

1.5 Fixed bug on search while using enter key (which caused the editor to react to key press).

1.4.1 Added a new theme thanks to Roberto Merino, fixed full screen background color and implemented enter key on jump to line box.

1.4.0 Jump To Line with hot key Crtl + G

1.3.2 added documentation

1.3.1 added search box Enter key for search.

1.3 Added Ajax saving feature, Create New File, Delete file, Create New Directory. Added Crtl + S save hot key.

1.2 Added Crtl+F search and Crtl+H replace hot keys.

1.1 added RTL instaltions support.

1.0 initial release.