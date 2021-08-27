/* global WPHB_Admin */
/* global wphb */

/**
 * Asset Optimization scripts.
 *
 * @package
 */

import Fetcher from '../utils/fetcher';
import { getString, getLink } from '../utils/helpers';
import Row from '../minification/Row';
import RowsCollection from '../minification/RowsCollection';
import MinifyScanner from '../scanners/MinifyScanner';

( function ( $ ) {
	'use strict';

	WPHB_Admin.minification = {
		module: 'minification',
		$checkFilesResultsContainer: null,
		checkURLSList: null,
		checkedURLS: 0,

		init() {
			const self = this;

			// Init files scanner.
			this.scanner = new MinifyScanner(
				wphb.minification.get.totalSteps,
				wphb.minification.get.currentScanStep
			);

			// Check files button.
			$( '#check-files' ).on( 'click', function ( e ) {
				e.preventDefault();
				$( document ).trigger( 'check-files' );
			} );

			$( document ).on( 'check-files', function () {
				window.SUI.openModal( 'check-files-modal', 'wpbody-content' );
				$( this ).attr( 'disabled', true );
				self.scanner.start();
			} );

			// Track changes done to minification files.
			$(
				':input.toggle-checkbox, :input[id*="wphb-minification-include"]'
			).on( 'change', function () {
				const row = $( this ).closest( '.wphb-border-row' );
				const rowStatus = row.find( 'span.wphb-row-status-changed' );
				$( this ).toggleClass( 'changed' );
				if ( row.find( '.changed' ).length !== 0 ) {
					rowStatus.removeClass( 'sui-hidden' );
				} else {
					rowStatus.addClass( 'sui-hidden' );
				}
				const changed = $( '.wphb-minification-files' ).find(
					'input.changed'
				);
				if ( changed.length !== 0 ) {
					$( '#wphb-publish-changes' ).removeClass( 'disabled' );
				} else {
					$( '#wphb-publish-changes' ).addClass( 'disabled' );
				}
			} );

			// Enable/disable bulk update button.
			$(
				':input.wphb-minification-file-selector, :input.wphb-minification-bulk-file-selector'
			).on( 'change', function () {
				$( this ).toggleClass( 'changed' );
				const changed = $( '.wphb-minification-files' ).find(
					'input.changed'
				);

				$( '.sui-actions-left > #bulk-update' ).toggleClass(
					'button-notice disabled',
					0 === changed.length
				);
			} );

			/**
			 * Open up bulk update modal. Make sure we hide elements not applicable to
			 * the selection.
			 */
			$( '#bulk-update' ).on( 'click', function ( e ) {
				e.preventDefault();

				const css = $(
					'input[data-type="CSS"].wphb-minification-file-selector:checked'
				);
				const js = $(
					'input[data-type="JS"].wphb-minification-file-selector:checked'
				);

				$(
					'#bulk-update-modal label[for="filter-inline"]'
				).toggleClass( 'sui-hidden', 0 === css.length );

				$( '#bulk-update-modal label[for="filter-defer"]' ).toggleClass(
					'sui-hidden',
					0 === js.length
				);

				$( '#bulk-update-modal label[for="filter-async"]' ).toggleClass(
					'sui-hidden',
					0 === js.length
				);

				window.SUI.openModal(
					'bulk-update-modal',
					this,
					'bulk-update-cancel',
					true
				);
			} );

			// Filter action button on Asset Optimization page
			$( '#wphb-minification-filter-button' ).on( 'click', function () {
				$( '.wphb-minification-filter' ).toggle( 'slow' );
				$( '#wphb-minification-filter-button' ).toggleClass( 'active' );
			} );

			// Discard changes button click
			$( '.wphb-discard' ).on( 'click', function ( e ) {
				e.preventDefault();

				if ( confirm( getString( 'discardAlert' ) ) ) {
					location.reload();
				}
				return false;
			} );

			// Enable discard button on any change
			$( '.wphb-enqueued-files input' ).on( 'change', function () {
				$( '.wphb-discard' ).attr( 'disabled', false );
			} );

			// CDN checkbox update status
			const checkboxes = $( 'input[type=checkbox][name=use_cdn]' );
			checkboxes.on( 'change', function () {
				$( '#cdn_file_exclude' ).toggleClass( 'sui-hidden' );
				const cdnValue = $( this ).is( ':checked' );

				// Handle two CDN checkboxes on Asset Optimization page
				checkboxes.each( function () {
					this.checked = cdnValue;
				} );

				// Update CDN status
				Fetcher.minification.toggleCDN( cdnValue ).then( () => {
					WPHB_Admin.notices.show();
				} );
			} );

			/**
			 * Improve tooltip handling.
			 *
			 * @since 3.0.0
			 */
			const aoButtons = $(
				'.wphb-minification-advanced-group > :input.toggle-checkbox'
			);
			aoButtons.on( 'change', function () {
				const label = $(
					"label[for='" + $( this ).attr( 'id' ) + "']"
				);

				let str;

				// Minify.
				if ( $( this ).hasClass( 'toggle-minify' ) ) {
					str = getString( this.checked.toString() + 'Minify' );
					label.attr( 'data-tooltip', str );
				}
				// Combine.
				if ( $( this ).hasClass( 'toggle-combine' ) ) {
					str = getString( this.checked.toString() + 'Combine' );
					label.attr( 'data-tooltip', str );
				}
				// Footer.
				if ( $( this ).hasClass( 'toggle-position-footer' ) ) {
					str = getString( this.checked.toString() + 'Footer' );
					label.attr( 'data-tooltip', str );
				}
				// Inline.
				if ( $( this ).hasClass( 'toggle-inline' ) ) {
					str = getString( this.checked.toString() + 'Inline' );
					label.attr( 'data-tooltip', str );
				}
				// Defer.
				if ( $( this ).hasClass( 'toggle-defer' ) ) {
					str = getString( this.checked.toString() + 'Defer' );
					label.attr( 'data-tooltip', str );
				}
				// Font optimization.
				if ( $( this ).hasClass( 'toggle-font-optimize' ) ) {
					str = getString( this.checked.toString() + 'Font' );
					label.attr( 'data-tooltip', str );
				}
				// Preload.
				if ( $( this ).hasClass( 'toggle-preload' ) ) {
					str = getString( this.checked.toString() + 'Preload' );
					label.attr( 'data-tooltip', str );
				}
				// Async.
				if ( $( this ).hasClass( 'toggle-async' ) ) {
					str = getString( this.checked.toString() + 'Async' );
					label.attr( 'data-tooltip', str );
				}
			} );

			// Exclude file buttons.
			const excludeButtons = $(
				'.wphb-minification-exclude > :input.toggle-checkbox'
			);
			excludeButtons.on( 'change', function () {
				const row = $( this ).closest( '.wphb-border-row' );
				row.toggleClass( 'disabled' );
				const label = $(
					"label[for='" + $( this ).attr( 'id' ) + "']"
				);
				if ( label.hasClass( 'fileIncluded' ) ) {
					label
						.find( 'span' )
						.removeClass( 'sui-icon-eye-hide' )
						.addClass( 'sui-icon-eye' );
					label.attr( 'data-tooltip', getString( 'includeFile' ) );
					label.removeClass( 'fileIncluded' );
				} else {
					label
						.find( 'span' )
						.removeClass( 'sui-icon-eye' )
						.addClass( 'sui-icon-eye-hide' );
					label.attr( 'data-tooltip', getString( 'excludeFile' ) );
					label.addClass( 'fileIncluded' );
				}
			} );

			/**
			 * Regenerate individual file.
			 *
			 * @since 1.9.2
			 */
			$( '.wphb-compressed .wphb-filename-extension' ).on(
				'click',
				function () {
					const row = $( this ).closest( '.wphb-border-row' );

					row.find( '.fileinfo-group' ).removeClass(
						'wphb-compressed'
					);

					row.find( '.wphb-row-status' )
						.removeClass( 'sui-hidden wphb-row-status-changed' )
						.addClass(
							'wphb-row-status-queued sui-tooltip-constrained'
						)
						.attr( 'data-tooltip', getString( 'queuedTooltip' ) )
						.find( 'span' )
						.attr( 'class', 'sui-icon-loader sui-loading' );

					Fetcher.minification.resetAsset(
						row.attr( 'data-filter' )
					);
				}
			);

			$( 'input[type=checkbox][name=debug_log]' ).on(
				'change',
				function () {
					const enabled = $( this ).is( ':checked' );
					Fetcher.minification.toggleLog( enabled ).then( () => {
						WPHB_Admin.notices.show();
						if ( enabled ) {
							$( '.wphb-logging-box' ).show();
						} else {
							$( '.wphb-logging-box' ).hide();
						}
					} );
				}
			);

			/**
			 * Save critical css file
			 */
			$( '#wphb-minification-tools-form' ).on( 'submit', function ( e ) {
				e.preventDefault();

				const spinner = $( this ).find( '.spinner' );
				spinner.addClass( 'visible' );

				Fetcher.minification
					.saveCriticalCss( $( this ).serialize() )
					.then( ( response ) => {
						spinner.removeClass( 'visible' );
						if (
							'undefined' !== typeof response &&
							response.success
						) {
							WPHB_Admin.notices.show( response.message );
						} else {
							WPHB_Admin.notices.show(
								response.message,
								'error'
							);
						}
					} );
			} );

			/**
			 * Parse custom asset dir input
			 *
			 * @since 1.9
			 */
			const textField = document.getElementById( 'file_path' );
			if ( null !== textField ) {
				textField.onchange = function ( e ) {
					e.preventDefault();
					Fetcher.minification
						.updateAssetPath( $( this ).val() )
						.then( ( response ) => {
							if ( response.message ) {
								WPHB_Admin.notices.show(
									response.message,
									'error'
								);
							} else {
								WPHB_Admin.notices.show();
							}
						} );
				};
			}

			/**
			 * Asset optimization network settings page.
			 *
			 * @since 2.0.0
			 */

			// Show/hide settings, based on checkbox value.
			$( '#wphb-network-ao' ).on( 'click', function () {
				$( '#wphb-network-border-frame' ).toggleClass( 'sui-hidden' );
			} );

			// Handle settings select.
			$( '#wphb-box-minification-network-settings' ).on(
				'change',
				'input[type=radio]',
				function ( e ) {
					const divs = document.querySelectorAll(
						'input[name=' + e.target.name + ']'
					);

					// Toggle logs frame.
					if ( 'log' === e.target.name ) {
						$( '.wphb-logs-frame' ).toggle( e.target.value );
					}

					for ( let i = 0; i < divs.length; ++i ) {
						divs[ i ].parentNode.classList.remove( 'active' );
					}

					e.target.parentNode.classList.add( 'active' );
				}
			);

			// Submit settings.
			$( '#wphb-ao-network-settings' ).on( 'click', function ( e ) {
				e.preventDefault();

				const spinner = $( '.sui-box-footer' ).find( '.spinner' );
				spinner.addClass( 'visible' );

				const form = $( '#ao-network-settings-form' ).serialize();
				Fetcher.minification
					.saveNetworkSettings( form )
					.then( ( response ) => {
						spinner.removeClass( 'visible' );
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

			$( '#wphb-ao-settings-update' ).on( 'click', function ( e ) {
				e.preventDefault();

				const spinner = $( '.sui-box-footer' ).find( '.spinner' );
				spinner.addClass( 'visible' );

				const data = self.getMultiSelectValues( 'cdn_exclude' );

				Fetcher.minification
					.updateExcludeList( JSON.stringify( data ) )
					.then( () => {
						spinner.removeClass( 'visible' );
						WPHB_Admin.notices.show();
					} );
			} );

			/**
			 * Asset optimization 2.0
			 *
			 * @since 2.6.0
			 */

			/**
			 * This is such a weird piece of code. Unfortunately, it was written during the sad time
			 * when my coffee machine broke down. Sorry.
			 * Increment the WTF Counter if you've checked it out and went like "Huh???"
			 *
			 * wtf_counter = 2
			 */
			const modeToggles = document.querySelectorAll(
				'[name=asset_optimization_mode]'
			);
			let current = 'auto';
			for ( let i = 0; i < modeToggles.length; i++ ) {
				// Set the current selection.
				if ( true === modeToggles[ i ].checked ) {
					current = modeToggles[ i ].value;
				}

				modeToggles[ i ].addEventListener( 'click', function () {
					// Ignore clicking on the selected value.
					if ( current === this.value ) {
						return;
					}

					// Visually switch toggles.
					document
						.getElementById( 'wphb-ao-' + current + '-label' )
						.classList.add( 'active' );
					document
						.getElementById( 'wphb-ao-' + this.value + '-label' )
						.classList.remove( 'active' );

					if ( 'manual' === current && 'auto' === this.value ) {
						if ( true === wphb.minification.get.showSwitchModal ) {
							window.SUI.openModal(
								'wphb-basic-minification-modal',
								'wphb-switch-to-basic'
							);
						} else {
							WPHB_Admin.minification.switchView( 'basic' );
						}
					}
				} );
			}

			// How does it work? stuff.
			const expandButtonManual = document.getElementById(
				'manual-ao-hdiw-modal-expand'
			);
			if ( expandButtonManual ) {
				expandButtonManual.onclick = function () {
					document
						.getElementById( 'manual-ao-hdiw-modal' )
						.classList.remove( 'sui-modal-sm' );
					document
						.getElementById( 'manual-ao-hdiw-modal-header-wrap' )
						.classList.remove( 'sui-box-sticky' );
					document
						.getElementById( 'automatic-ao-hdiw-modal' )
						.classList.remove( 'sui-modal-sm' );
				};
			}

			const collapseButtonManual = document.getElementById(
				'manual-ao-hdiw-modal-collapse'
			);
			if ( collapseButtonManual ) {
				collapseButtonManual.onclick = function () {
					document
						.getElementById( 'manual-ao-hdiw-modal' )
						.classList.add( 'sui-modal-sm' );
					const el = document.getElementById(
						'manual-ao-hdiw-modal-header-wrap'
					);
					if ( el.classList.contains( 'video-playing' ) ) {
						el.classList.add( 'sui-box-sticky' );
					}
					document
						.getElementById( 'automatic-ao-hdiw-modal' )
						.classList.add( 'sui-modal-sm' );
				};
			}

			// How does it work? stuff.
			const expandButtonAuto = document.getElementById(
				'automatic-ao-hdiw-modal-expand'
			);
			if ( expandButtonAuto ) {
				expandButtonAuto.onclick = function () {
					document
						.getElementById( 'automatic-ao-hdiw-modal' )
						.classList.remove( 'sui-modal-sm' );
					document
						.getElementById( 'manual-ao-hdiw-modal' )
						.classList.remove( 'sui-modal-sm' );
				};
			}

			const collapseButtonAuto = document.getElementById(
				'automatic-ao-hdiw-modal-collapse'
			);
			if ( collapseButtonAuto ) {
				collapseButtonAuto.onclick = function () {
					document
						.getElementById( 'automatic-ao-hdiw-modal' )
						.classList.add( 'sui-modal-sm' );
					document
						.getElementById( 'manual-ao-hdiw-modal' )
						.classList.add( 'sui-modal-sm' );
				};
			}

			const autoTrigger = document.getElementById(
				'hdw-auto-trigger-label'
			);
			if ( autoTrigger ) {
				autoTrigger.addEventListener( 'click', () => {
					window.SUI.replaceModal(
						'automatic-ao-hdiw-modal-content',
						'wphb-box-minification-summary-meta-box'
					);
				} );
			}

			const manualTrigger = document.getElementById(
				'hdw-manual-trigger-label'
			);
			if ( manualTrigger ) {
				manualTrigger.addEventListener( 'click', () => {
					window.SUI.replaceModal(
						'manual-ao-hdiw-modal-content',
						'wphb-box-minification-summary-meta-box'
					);
				} );
			}

			/**
			 * Asset Optimization filters
			 *
			 * @type {RowsCollection|*}
			 */
			this.rowsCollection = new WPHB_Admin.minification.RowsCollection();

			const rows = $( '.wphb-border-row' );

			rows.each( function ( index, row ) {
				let _row;
				if ( $( row ).data( 'filter-secondary' ) ) {
					_row = new WPHB_Admin.minification.Row(
						$( row ),
						$( row ).data( 'filter' ),
						$( row ).data( 'filter-secondary' ),
						$( row ).data( 'filter-type' )
					);
				} else {
					_row = new WPHB_Admin.minification.Row(
						$( row ),
						$( row ).data( 'filter' ),
						false,
						$( row ).data( 'filter-type' )
					);
				}
				self.rowsCollection.push( _row );
			} );

			// Filter search box
			const filterInput = $( '#wphb-s' );
			// Prevent enter submitting form to rescan files.
			filterInput.on( 'keydown', function ( e ) {
				if ( 13 === e.keyCode ) {
					event.preventDefault();
					return false;
				}
			} );
			filterInput.on( 'keyup', function () {
				self.rowsCollection.addFilter( $( this ).val(), 'primary' );
				self.rowsCollection.applyFilters();
			} );

			// Filter dropdown
			$( '#wphb-secondary-filter' ).on( 'change', function () {
				self.rowsCollection.addFilter( $( this ).val(), 'secondary' );
				self.rowsCollection.applyFilters();
			} );

			// Files filter.
			$( '[name="asset_optimization_filter"]' ).on(
				'change',
				function () {
					self.rowsCollection.addFilter( $( this ).val(), 'type' );
					self.rowsCollection.applyFilters();
				}
			);

			// Clear filters button.
			const clFilters = document.getElementById( 'wphb-clear-filters' );
			if ( clFilters ) {
				clFilters.addEventListener( 'click', function ( e ) {
					e.preventDefault();

					// There is probably an easier way to do via SUI.
					$( '#wphb-filter-all' ).prop( 'checked', true );
					$( '.wphb-minification-filter .sui-tab-item' ).removeClass(
						'active'
					);
					$( '#wphb-filter-all-label' ).addClass( 'active' );

					// Reset select.
					$( '#wphb-secondary-filter' )
						.val( null )
						.trigger( 'change' );

					// Reset input.
					filterInput.val( '' );

					self.rowsCollection.clearFilters();
				} );
			}

			// Files selectors
			const filesList = $( 'input.wphb-minification-file-selector' );
			filesList.on( 'click', function () {
				const $this = $( this );
				const element = self.rowsCollection.getItemById(
					$this.data( 'type' ),
					$this.data( 'handle' )
				);
				if ( ! element ) {
					return;
				}

				if ( $this.is( ':checked' ) ) {
					element.select();
				} else {
					element.unSelect();
				}
			} );

			/**
			 * Handle select/deselect of all files of a certain type for
			 * use on bulk update.
			 *
			 * @type {*|jQuery|HTMLElement}
			 */
			const selectAll = $( '.wphb-minification-bulk-file-selector' );
			selectAll.on( 'click', function () {
				const $this = $( this );
				const items = self.rowsCollection.getItemsByDataType(
					$this.attr( 'data-type' )
				);
				for ( const i in items ) {
					if ( items.hasOwnProperty( i ) ) {
						if ( $this.is( ':checked' ) ) {
							items[ i ].select();
						} else {
							items[ i ].unSelect();
						}
					}
				}
			} );

			/* Show details of minification row on mobile devices */
			$( 'body' ).on( 'click', '.wphb-border-row', function () {
				if ( window.innerWidth < 783 ) {
					$( this ).find( '.wphb-minification-row-details' ).toggle();
					$( this ).find( '.fileinfo-group' ).toggleClass( 'opened' );
				}
			} );

			/**
			 * Catch window resize and revert styles for responsive dive
			 * 1/4 of a second should be enough to trigger during device
			 * rotations (from portrait to landscape mode)
			 */
			const minificationResizeRows = _.debounce( function () {
				if ( window.innerWidth >= 783 ) {
					$( '.wphb-minification-row-details' ).css(
						'display',
						'flex'
					);
				} else {
					$( '.wphb-minification-row-details' ).css(
						'display',
						'none'
					);
				}
			}, 250 );

			window.addEventListener( 'resize', minificationResizeRows );

			return this;
		},

		/**
		 * Switch from advanced to basic view.
		 * Called from switch view modal.
		 *
		 * @param {string}  mode
		 */
		switchView( mode ) {
			let hide = false;
			const trackBox = document.getElementById(
				'hide-' + mode + '-modal'
			);

			if ( trackBox && true === trackBox.checked ) {
				hide = true;
			}

			Fetcher.minification.toggleView( mode, hide ).then( () => {
				window.location.href = getLink( 'minification' );
			} );
		},

		/**
		 * Go to the Asset Optimization files page.
		 *
		 * @since 1.9.2
		 * @since 2.1.0  Added show_tour parameter.
		 * @since 2.6.0  Remove show_tour parameter.
		 */
		goToSettings() {
			window.SUI.closeModal();

			Fetcher.minification
				.toggleCDN( $( 'input#enable_cdn' ).is( ':checked' ) )
				.then( () => {
					window.location.href = getLink( 'minification' );
				} );
		},

		/**
		 * Get all selected values from multiselect.
		 *
		 * @since 2.6.0
		 *
		 * @param {string} id  Select ID.
		 * @return {{styles: *[], scripts: *[]}}  Styles & scripts array.
		 */
		getMultiSelectValues( id ) {
			const selected = $( '#' + id ).find( ':selected' );

			const data = { scripts: [], styles: [] };

			for ( let i = 0; i < selected.length; ++i ) {
				data[ selected[ i ].dataset.type ].push( selected[ i ].value );
			}

			return data;
		},

		/**
		 * Skip upgrade.
		 *
		 * @since 2.6.0
		 */
		skipUpgrade() {
			Fetcher.common.call( 'wphb_ao_skip_upgrade' ).then( () => {
				window.location.href = getLink( 'minification' );
			} );
		},

		/**
		 * Perform AO upgrade.
		 *
		 * @since 2.6.0
		 */
		doUpgrade() {
			Fetcher.common.call( 'wphb_ao_do_upgrade' ).then( () => {
				window.location.href = getLink( 'minification' );
			} );
		},

		/**
		 * Process actions from bulk update modal.
		 */
		processBulkUpdateSelections() {
			const selectedFiles = this.rowsCollection.getSelectedItems();

			const actions = [
				'minify',
				'combine',
				'position-footer',
				'defer',
				'inline',
				'preload',
				'async',
			];

			actions.forEach( ( action ) => {
				const sel = '#bulk-update-modal input#filter-' + action;
				const val = $( sel ).prop( 'checked' );

				for ( const i in selectedFiles ) {
					if ( selectedFiles.hasOwnProperty( i ) ) {
						selectedFiles[ i ].change( action, val );
					}
				}

				$( sel ).prop( 'checked', false );
			} );

			// Enable the Publish Changes button.
			$( 'input[type=submit]' ).removeClass( 'disabled' );
		},
	}; // End WPHB_Admin.minification

	WPHB_Admin.minification.Row = Row;
	WPHB_Admin.minification.RowsCollection = RowsCollection;
} )( jQuery );
