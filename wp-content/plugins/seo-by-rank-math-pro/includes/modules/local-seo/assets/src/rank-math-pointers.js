/**
 * External dependencies
 */
import $ from 'jquery'
import { forEach } from 'lodash'

/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n'

class RMPointers {
	constructor() {
		this.pointers = this.getPointers()
		this.showPointer = this.showPointer.bind( this )

		this.init()
	}

	init() {
		forEach( this.pointers, ( pointer, id ) => {
			this.showPointer( id )
			return false
		} )

		if ( $( '.rank-math-toolbar-score' ).parent().hasClass( 'is-pressed' ) ) {
			return
		}

		$( '.rank-math-toolbar-score' ).parent().trigger( 'click' )
	}

	showPointer( id ) {
		const pointer = this.pointers[ id ]
		const options = $.extend( pointer.options, {
			pointerClass: 'wp-pointer rm-pointer',
			close: () => {
				if ( pointer.next ) {
					this.showPointer( pointer.next )
				}
			},
			buttons: ( event, t ) => {
				const nextLabel = 'wp-pointer-3' === t.pointer[ 0 ].id ? __( 'Finish', 'rank-math-pro' ) : __( 'Next', 'rank-math-pro' ),
					button = $( '<a class="close" href="#">' + __( 'Dismiss', 'rank-math-pro' ) + '</a>' ),
					button2 = $( '<a class="button button-primary" href="#">' + nextLabel + '</a>' ),
					wrapper = $( '<div class="rm-pointer-buttons" />' )

				button.on( 'click.pointer', function( e ) {
					e.preventDefault()
					t.element.pointer( 'destroy' )
				} )

				button2.on( 'click.pointer', function( e ) {
					e.preventDefault()
					t.element.pointer( 'close' )
				} )

				wrapper.append( button )
				wrapper.append( button2 )

				return wrapper
			},
		} )
		const currentPointer = $( pointer.target ).pointer( options )
		currentPointer.pointer( 'open' )

		if ( pointer.next_trigger ) {
			$( pointer.next_trigger.target ).on( pointer.next_trigger.event, function() {
				setTimeout(
					function() {
						currentPointer.pointer( 'close' )
					}, 400 )
			} )
		}
	}

	getPointers() {
		return {
			title: {
				target: '.editor-post-title__input',
				next: 'schema',
				options: {
					content: '<h3>' + __( 'Local Business Name', 'rank-math-pro' ) + '</h3>' + '<p>' + __( 'Give your business\'s new location a name here. This field is required and will be visible to users.', 'rank-math-pro' ) + '</p>',
				},
			},
			schema: {
				target: '.components-tab-panel__tabs-item.rank-math-schema-tab',
				next: 'content',
				options: {
					content: '<h3>' + __( 'Local Business Schema', 'rank-math-pro' ) + '</h3>' + '<p>' + __( 'Add your local business\'s details here with "Local Business" Schema Markup in order to be eligible for local SERP features.', 'rank-math-pro' ) + '</p>',
					position: {
						edge: 'right',
						align: 'left',
					},
				},
			},
			content: {
				target: '.is-root-container',
				next: 'submitdiv',
				options: {
					content: '<h3>' + __( 'Show Business Information', 'rank-math-pro' ) + '</h3>' + '<p>' + sprintf( __( 'Make sure to add the Local Business Block or %s to display your business data.', 'rank-math-pro' ), '<a href="https://rankmath.com/kb/location-data-shortcode/" target="_blank">[rank_math_local] shortcode</a>' ) + '</p>',
					position: {
						edge: 'bottom',
						align: 'middle',
					},
				},
			},
			submitdiv: {
				target: '.editor-post-publish-button__button',
				next: '',
				options: {
					content: '<h3>' + __( 'Publish your location!', 'rank-math-pro' ) + '</h3>' + '<p>' + __( 'When you\'re done editing, don\'t forget to hit "publish" to create this location.', 'rank-math-pro' ) + '</p>',
				},
			},
		}
	}
}

$( window ).on( 'load', () => {
	new RMPointers
} )
