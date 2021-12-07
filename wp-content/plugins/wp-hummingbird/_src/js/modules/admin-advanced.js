/* global WPHB_Admin */

/**
 * Internal dependencies
 */
import Fetcher from '../utils/fetcher';
import { getString } from '../utils/helpers';
import { OrphanedScanner, BATCH_SIZE } from '../scanners/OrphanedScanner';

( function( $ ) {
	'use strict';

	WPHB_Admin.advanced = {
		module: 'advanced',

		init() {
			const self = this,
				systemInfoDropdown = $( '#wphb-system-info-dropdown' ),
				hash = window.location.hash;

			/**
			 * Process form submit for advanced tools forms
			 */
			$( '#wphb-db-delete-all, .wphb-db-row-delete' ).on(
				'click',
				function( e ) {
					e.preventDefault();
					self.showModal(
						e.target.dataset.entries,
						e.target.dataset.type
					);
				}
			);

			/**
			 * Process form submit for advanced tools forms
			 */
			$(
				'form[id="advanced-general-settings"], form[id="advanced-lazy-settings"]'
			).on( 'submit', function( e ) {
				e.preventDefault();

				const button = $( this ).find( '.sui-button-blue' );
				button.addClass( 'sui-button-onload-text' );

				Fetcher.advanced
					.saveSettings( $( this ).serialize(), e.target.id )
					.then( ( response ) => {
						button.removeClass( 'sui-button-onload-text' );

						if (
							'undefined' !== typeof response &&
							response.success
						) {
							WPHB_Admin.notices.show();
						} else {
							WPHB_Admin.notices.show(
								getString( 'errorSettingsUpdate' ),
								'error'
							);
						}
					} );
			} );

			/**
			 * Show initial system information table.
			 */
			$( '#wphb-system-info-php' ).removeClass( 'sui-hidden' );
			if ( hash ) {
				const system = hash.replace( '#', '' );
				$( '.wphb-sys-info-table' ).addClass( 'sui-hidden' );
				$( '#wphb-system-info-' + system ).removeClass( 'sui-hidden' );
				systemInfoDropdown.val( system ).trigger( 'sui:change' );
			}

			/**
			 * Show/hide system information tables on dropdown change.
			 */
			systemInfoDropdown.on( 'change', function( e ) {
				e.preventDefault();
				$( '.wphb-sys-info-table' ).addClass( 'sui-hidden' );
				$( '#wphb-system-info-' + $( this ).val() ).removeClass(
					'sui-hidden'
				);
				location.hash = $( this ).val();
			} );

			/**
			 * Paste default values to url strings option.
			 *
			 * @since 1.9.0
			 */
			$( '#wphb-adv-paste-value' ).on( 'click', function( e ) {
				e.preventDefault();
				const urlStrings = $( 'textarea[name="url_strings"]' );
				if ( '' === urlStrings.val() ) {
					urlStrings.val( urlStrings.attr( 'placeholder' ) );
				} else {
					urlStrings.val(
						urlStrings.val() +
							'\n' +
							urlStrings.attr( 'placeholder' )
					);
				}
			} );

			/**
			 * Toggle woo cart fragments settings.
			 *
			 * @since 2.2.0
			 */
			const fragmentsToggle = document.getElementById( 'cart_fragments' );
			if ( fragmentsToggle ) {
				fragmentsToggle.addEventListener( 'change', function( e ) {
					e.preventDefault();
					$( '#cart_fragments_desc' ).toggle();
				} );
			}

			// If button is center aligned, Disable left and right margin and set them to 0.
			const alignOptions = document.querySelectorAll(
				'input[name="button[alignment][align]"]'
			);
			const marginLeft = document.getElementById( 'button_margin_l' );
			const marginRight = document.getElementById( 'button_margin_r' );
			for ( let i = 0; i < alignOptions.length; i++ ) {
				alignOptions[ i ].addEventListener( 'change', function() {
					if (
						'center' === alignOptions[ i ].value &&
						alignOptions[ i ].checked
					) {
						marginLeft.setAttribute( 'disabled', 'disabled' );
						marginRight.setAttribute( 'disabled', 'disabled' );
						marginLeft.value = 0;
						marginRight.value = 0;
					} else {
						marginLeft.removeAttribute( 'disabled' );
						marginRight.removeAttribute( 'disabled' );
					}
				} );
			}

			/**
			 * Show/Hide Lazy comments load options
			 *
			 */
			$( 'input[id="lazy_load"]' ).on( 'change', function() {
				$(
					'#wphb-lazy-load-comments-wrap, #sui-upsell-gravtar-caching'
				).toggle();
			} );

			/**
			 * Initialize color picker on lazy load
			 */
			if ( true === window.location.search.includes( 'view=lazy' ) ) {
				this.createPickers();
			}

			/**
			 * Purge cache on plugin health page.
			 *
			 * @since 2.7.0
			 */
			const purgeBtn = document.getElementById( 'btn-cache-purge' );
			if ( purgeBtn ) {
				purgeBtn.addEventListener( 'click', () => this.purgeDb() );
			}

			/**
			 * Purge asset optimization on plugin health page.
			 *
			 * @since 2.7.0
			 */
			const purgeAObtn = document.getElementById( 'btn-minify-purge' );
			if ( purgeAObtn ) {
				purgeAObtn.addEventListener( 'click', () =>
					this.purgeDb( 'minify' )
				);
			}

			/**
			 * Purge orphaned data from modal.
			 *
			 * @since 2.7.0
			 */
			const purgeModalBtn = document.getElementById(
				'site-health-orphaned-clear'
			);
			if ( purgeModalBtn ) {
				purgeModalBtn.addEventListener( 'click', () => {
					const count = document.getElementById( 'count-ao-orphaned' )
						.innerHTML;
					const steps = Math.ceil( parseInt( count ) / BATCH_SIZE );

					const scanner = new OrphanedScanner( steps, 0 );
					scanner.start();
				} );
			}

			return this;
		},

		/**
		 * Show the modal window asking if a user is sure he wants to delete the db records.
		 *
		 * @param {string} items Number of records to delete.
		 * @param {string} type  Data type to delete from db (See data-type element for each row in the code).
		 */
		showModal( items, type ) {
			const modal = $( '#wphb-database-cleanup-modal' );

			let dialog;
			let btn = '<span class="sui-icon-trash" aria-hidden="true"></span>';

			if ( 'drafts' === type ) {
				dialog = getString( 'dbDeleteDrafts' );
				btn += getString( 'dbDeleteDraftsButton' );
			} else {
				dialog =
					getString( 'db_delete' ) +
					' ' +
					items +
					' ' +
					getString( 'db_entries' ) +
					'? ' +
					getString( 'db_backup' );
				btn += getString( 'dbDeleteButton' );
			}

			modal.find( 'p' ).html( dialog );
			modal
				.find( '.sui-button-red' )
				.attr( 'data-type', type )
				.html( btn );

			window.SUI.openModal(
				'wphb-database-cleanup-modal',
				'wpbody-content',
				'wphb-clear-database-confirm',
				false
			);
		},

		/**
		 * Process database cleanup (both individual and all entries).
		 *
		 * @param {string} type Data type to delete from db (See data-type element for each row in the code).
		 */
		confirmDelete( type ) {
			window.SUI.closeModal();

			let row;
			const footer = $( '.box-advanced-db .sui-box-footer' );

			if ( 'all' === type ) {
				row = footer;
			} else {
				row = $( '.box-advanced-db .wphb-border-frame' ).find(
					'div[data-type=' + type + ']'
				);
			}

			const allBtn = $( '#wphb-db-delete-all' );
			const button = row.find( '.wphb-db-row-delete' );

			allBtn.addClass( 'sui-button-onload-text' );
			button.addClass( 'sui-button-onload' );

			Fetcher.advanced
				.deleteSelectedData( type )
				.then( ( response ) => {
					allBtn.removeClass( 'sui-button-onload-text' );
					button.removeClass( 'sui-button-onload' );

					for ( const prop in response.left ) {
						if ( 'total' === prop ) {
							const leftString =
								getString( 'deleteAll' ) +
								' (' +
								response.left[ prop ] +
								')';
							footer
								.find( '.wphb-db-delete-all' )
								.html( leftString );
							footer
								.find( '#wphb-db-delete-all' )
								.attr( 'data-entries', response.left[ prop ] );
						} else {
							const itemRow = $(
								'.box-advanced-db div[data-type=' + prop + ']'
							);
							itemRow
								.find( '.wphb-db-items' )
								.html( response.left[ prop ] );
							itemRow
								.find( '.wphb-db-row-delete' )
								.attr( 'data-entries', response.left[ prop ] )
								.attr(
									'disabled',
									'0' === response.left[ prop ]
								);
						}
					}

					WPHB_Admin.notices.show( response.message );
				} )
				.catch( ( error ) => {
					WPHB_Admin.notices.show( error, 'error' );
					allBtn.removeClass( 'sui-button-onload-text' );
					button.removeClass( 'sui-button-onload' );
				} );
		},

		createPickers() {
			const $suiPickerInputs = $( '.sui-colorpicker-input' );

			$suiPickerInputs.wpColorPicker( {
				change( event, ui ) {
					const $this = $( this );

					// Prevent the model from being marked as changed on load.
					if ( $this.val() !== ui.color.toCSS() ) {
						$this.val( ui.color.toCSS() ).trigger( 'change' );
					}
				},
			} );

			if ( $suiPickerInputs.hasClass( 'wp-color-picker' ) ) {
				$suiPickerInputs.each( function() {
					const $suiPickerInput = $( this ),
						$suiPicker = $suiPickerInput.closest(
							'.sui-colorpicker-wrap'
						),
						$suiPickerColor = $suiPicker.find(
							'.sui-colorpicker-value span[role=button]'
						),
						$suiPickerValue = $suiPicker.find(
							'.sui-colorpicker-value'
						),
						$wpPicker = $suiPickerInput.closest(
							'.wp-picker-container'
						),
						$wpPickerButton = $wpPicker.find( '.wp-color-result' );
					// Listen to color change
					$suiPickerInput.on( 'change', function() {
						// Change color preview
						$suiPickerColor.find( 'span' ).css( {
							'background-color': $wpPickerButton.css(
								'background-color'
							),
						} );

						// Change color value
						$suiPickerValue
							.find( 'input' )
							.val( $suiPickerInput.val() );
					} );

					// Open iris picker
					$suiPicker
						.find( '.sui-button, span[role=button]' )
						.on( 'click', function( e ) {
							$wpPickerButton.click();

							e.preventDefault();
							e.stopPropagation();
						} );

					// Clear color value
					$suiPickerValue
						.find( 'button' )
						.on( 'click', function( e ) {
							$wpPicker.find( '.wp-picker-clear' ).click();
							$suiPickerValue.find( 'input' ).val( '' );
							$suiPickerInput.val( '' ).trigger( 'change' );
							$suiPickerColor.find( 'span' ).css( {
								'background-color': '',
							} );

							e.preventDefault();
							e.stopPropagation();
						} );
				} );
			}
		},

		/**
		 * Purge page cache preloader database entries or asset optimization custom post type groups and orphaned data.
		 *
		 * @since 2.7.0
		 *
		 * @param {string} type What to purge. Accepts: cache, minify.
		 */
		purgeDb( type = 'cache' ) {
			const button = document.getElementById( 'btn-' + type + '-purge' );

			if ( ! button ) {
				return;
			}

			button.classList.add( 'sui-button-onload-text' );

			Fetcher.common.call( 'wphb_advanced_purge_' + type ).then( () => {
				document.getElementById( 'count-' + type ).innerHTML = '0';

				button.classList.remove( 'sui-button-onload-text' );

				const str =
					'successAdvPurge' +
					type.charAt( 0 ).toUpperCase() +
					type.slice( 1 );

				WPHB_Admin.notices.show( getString( str ) );
			} );
		},
	};
} )( jQuery );
