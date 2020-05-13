/**
 * @license Copyright (c) 2003-2013, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	config.toolbarGroups = [
		{ name: 'basicstyles', groups: [ 'basicstyles', 'list', 'align', 'links' ] },
		{ name: 'paragraph', groups: [ 'list', 'align' ] },
		{ name: 'document', groups: [ 'cleanup', 'mode' ] },
		{ name: 'others' },
		'/',
		{ name: 'styles' },
		{ name: 'colors' }
	];
	
	// Remove buttons
	config.removeButtons = 'Underline,Subscript,Superscript,Anchor,Save,NewPage,Preview,Templates,Print,Styles';
	
	// Set the most common block elements.
	config.format_tags = 'p;h1;h2;h3;pre';
	
	// Make dialogs simpler.
	config.removeDialogTabs = 'link:advanced';
	
	// Remove loading of styles.js since no Style dropdown is used
	config.stylesSet = [];
	
	config.language = 'en';
};
