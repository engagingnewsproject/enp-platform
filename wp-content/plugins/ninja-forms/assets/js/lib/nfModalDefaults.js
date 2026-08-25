/**
 * NF modal defaults.
 *
 * Every Modal/Confirm in core is a jBox, but jBox only renders its dimmed
 * overlay when a call site opts in — and none did. This shim wraps the jBox
 * constructor so all Modals and Confirms get the overlay and click-outside-
 * to-close by default, while any call site that sets these options
 * explicitly keeps its own behavior. Tooltips and Notices are untouched.
 *
 * Loaded immediately after the jBox library on the dashboard and builder.
 *
 * @package
 */
( function () {
	'use strict';

	if ( 'undefined' === typeof window.jBox ) {
		return;
	}

	const OriginalJBox = window.jBox;

	/**
	 * Apply Ninja Forms defaults to modal-style jBox instances.
	 *
	 * @param {string} type    jBox type.
	 * @param {Object} options jBox options.
	 * @return {Object} Constructed jBox instance.
	 * @class
	 */
	const PatchedJBox = function ( type, options ) {
		if ( 'Modal' === type || 'Confirm' === type ) {
			options = options || {};
			if ( ! ( 'overlay' in options ) ) {
				options.overlay = true;
			}
			if ( ! ( 'closeOnClick' in options ) ) {
				options.closeOnClick = 'body';
			}
		}
		return new OriginalJBox( type, options );
	};

	PatchedJBox.prototype = OriginalJBox.prototype;

	// Carry over statics (jBox.plugin, jBox._pluginOptions, …) so add-ons
	// registering jBox plugins keep working against the wrapped constructor.
	for ( const key in OriginalJBox ) {
		if ( Object.prototype.hasOwnProperty.call( OriginalJBox, key ) ) {
			PatchedJBox[ key ] = OriginalJBox[ key ];
		}
	}

	window.jBox = PatchedJBox;
} )();
