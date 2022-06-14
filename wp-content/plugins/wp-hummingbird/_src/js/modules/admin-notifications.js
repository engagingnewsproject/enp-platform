/* global WPHB_Admin */
/* global SUI */
/* global ajaxurl */
/* global wphb */
/* global _ */
/* global wphbMixPanel */

/**
 * External dependencies
 */
import 'core-js/features/array/find-index';

/**
 * Internal dependencies
 */
import HBFetcher from '../utils/fetcher';
import { getString } from '../utils/helpers';

/**
 * Notifications module.
 *
 * @since 3.1.1
 */
( function( $ ) {
	'use strict';

	WPHB_Admin.notifications = {
		module: 'notifications',
		exclude: [],
		edit: false,
		settings: {
			view: 'schedule',
			module: '',
			type: '',
			schedule: {
				frequency: 7,
				time: '',
				weekDay: '',
				monthDay: '',
				threshold: 0,
			},
			recipients: [],
			performance: {
				device: 'both',
				metrics: true,
				audits: true,
				fieldData: true,
			},
			uptime: {
				showPing: true,
			},
			database: {
				revisions: true,
				drafts: true,
				trash: true,
				spam: true,
				trashComment: true,
				expiredTransients: true,
				transients: false,
			},
		},
		moduleData: {},

		/**
		 * Initialize the module.
		 *
		 * @since 3.1.1
		 * @param {Object} settings
		 * @return {WPHB_Admin.notifications}  Notifications module.
		 */
		init( settings ) {
			this.moduleData = settings;

			$( '.wphb-disable-notification' ).on( 'click', this.disable );
			$( '.wphb-enable-notification' ).on( 'click', ( e ) =>
				this.renderTemplate( e, 'add' )
			);

			$( '.wphb-configure-notification' ).on( 'click', ( e ) =>
				this.renderTemplate( e, 'edit' )
			);

			this.maybeOpenModal();

			return this;
		},

		/**
		 * Handle opening modals from other pages via hash.
		 *
		 * @since 3.2.0
		 */
		maybeOpenModal() {
			let hash = window.location.hash;
			if ( 0 === hash.length ) {
				return;
			}

			hash = hash.substring( 1 );
			hash = hash.split( '-' );
			if ( 2 !== hash.length ) {
				return;
			}

			let el = $(
				'button.wphb-configure-notification[data-id="' +
					hash[ 0 ] +
					'"][data-type="' +
					hash[ 1 ] +
					'"]'
			);

			if ( 0 === el.length ) {
				el = $(
					'.wphb-enable-notification[data-id="' +
						hash[ 0 ] +
						'"][data-type="' +
						hash[ 1 ] +
						'"]'
				);
			}

			if ( 0 !== el.length ) {
				el.trigger( 'click' );
			}
		},

		/**
		 * Render template.
		 *
		 * @since 3.1.1
		 * @param {Object} e
		 * @param {string} id Template ID.
		 */
		renderTemplate( e, id ) {
			e.preventDefault();

			this.settings.module = e.currentTarget.dataset.id;
			this.settings.type = e.currentTarget.dataset.type;

			const view = e.currentTarget.dataset.view;
			this.settings.view = 'schedule';
			if ( 'edit' === id && 'recipients' === view ) {
				this.settings.view = view;
			}

			// Just in case - make sure we have the correct data.
			if ( this.moduleData.hasOwnProperty( this.settings.type ) ) {
				let data = this.moduleData[ this.settings.type ];

				if ( data.hasOwnProperty( this.settings.module ) ) {
					data = data[ this.settings.module ];

					// Add schedule.
					if ( data.hasOwnProperty( 'schedule' ) ) {
						this.settings.schedule = data.schedule;
					}

					// Add settings.
					if ( data.hasOwnProperty( 'settings' ) ) {
						this.settings[ this.settings.module ] = data.settings;
					}

					if ( data.hasOwnProperty( 'recipients' ) ) {
						this.settings.recipients = data.recipients;
						// Populate exclude IDs list based on user IDs.
						this.exclude = data.recipients.reduce(
							( o, r ) => (
								0 < r.id && o.push( parseInt( r.id ) ), o
							),
							[]
						);
					}
				}
			}

			this.loadUsers();

			const template = WPHB_Admin.notifications.template(
				id + '-notifications-content'
			);
			const content = template( this.settings );

			if ( content ) {
				$( '#notification-modal' ).html( content );

				this.initSUI();
				this.mapActions();

				if ( 'edit' === id ) {
					this.edit = true;
					this.addSelections();
				} else {
					this.toggleUserNotice();
				}

				SUI.openModal( 'notification-modal', $( this ) );
			}
		},

		/**
		 * Ajax call to fetch load 10 WordPress users.
		 */
		loadUsers() {
			const self = this;

			HBFetcher.notifications
				.getUsers( this.exclude )
				.then( ( response ) => {
					if ( 'undefined' === typeof response ) {
						return;
					}

					let i = 0;
					response.forEach( function( user ) {
						self.addToUsersList( user, 0 === i++ );
					} );

					// Fix overflow for user selectors.
					this.fixRecipientCSS( $( '#modal-wp-user-list' ) );
				} )
				.catch( ( error ) => {
					window.console.log( error );
				} );
		},

		/**
		 * Process schedule selections.
		 *
		 * @since 3.1.1
		 */
		processScheduleSettings() {
			const threshold =
				'uptime' === this.settings.module &&
				'notifications' === this.settings.type;

			if ( threshold ) {
				const select = $( 'select#report-threshold' );
				this.settings.schedule.threshold = select.val();
			} else {
				const frequency = $( 'input[name="report-frequency"]:checked' );
				this.settings.schedule = {
					frequency: frequency.val(),
					time: $( 'select#report-time' ).val(),
					weekDay: $( 'select#report-day' ).val(),
					monthDay: $( 'select#report-day-month' ).val(),
					threshold: '',
				};
			}
		},

		/**
		 * Process additional settings tab.
		 *
		 * @since 3.1.1
		 */
		processAdditionalSettings() {
			if ( 'reports' !== this.settings.type ) {
				return;
			}

			if ( 'performance' === this.settings.module ) {
				const device = $( 'input[name="report-type"]:checked' ).val();
				const metrics = $( 'input#metrics' ).is( ':checked' );
				const audits = $( 'input#audits' ).is( ':checked' );
				const fieldData = $( 'input#field-data' ).is( ':checked' );

				this.settings.performance = {
					device,
					metrics,
					audits,
					fieldData,
				};

				return;
			}

			if ( 'uptime' === this.settings.module ) {
				const showPing = $( 'input#show_ping' ).is( ':checked' );
				this.settings.uptime = { showPing };
				return;
			}

			if ( 'database' === this.settings.module ) {
				const revisions = $( 'input#revisions' ).is( ':checked' );
				const drafts = $( 'input#drafts' ).is( ':checked' );
				const trash = $( 'input#trash' ).is( ':checked' );
				const spam = $( 'input#spam' ).is( ':checked' );
				const trashComment = $( 'input#trashComment' ).is( ':checked' );
				const expiredTransients = $( 'input#expiredTransients' ).is( ':checked' );
				const transients = $( 'input#transients' ).is( ':checked' );

				this.settings.database = {
					revisions,
					drafts,
					trash,
					spam,
					trashComment,
					expiredTransients,
					transients,
				};
			}
		},

		/**
		 * Update settings in reports (during activate and edit).
		 *
		 * @since 3.1.1
		 * @param {boolean} processSettings
		 */
		update( processSettings = false ) {
			const btn = event.target;
			btn.classList.add( 'sui-button-onload-text' );

			this.processScheduleSettings();
			if ( processSettings ) {
				this.processAdditionalSettings();
			}

			HBFetcher.notifications
				.enable( this.settings, this.edit )
				.then( ( response ) => {
					history.pushState( "", document.title, window.location.pathname + window.location.search );
					window.location.search += '&status=' + response.code;
				} )
				.catch( ( error ) => {
					window.console.log( error );
				} );
		},

		/**
		 * Activate the reports module.
		 *
		 * @since 3.1.1
		 * @param {boolean} processSettings
		 */
		activate( processSettings = false ) {
			const moduleName = this.getModuleName();
			if ( '' !== moduleName ) {
				wphbMixPanel.enableFeature( moduleName );
			}

			this.update( processSettings );
		},

		/**
		 * Map modal actions.
		 *
		 * @since 3.1.1
		 */
		mapActions() {
			this.initUserSelects();
			this.toggleAddButton();

			$( '#add-recipient-button' ).on( 'click', () => {
				this.handleAddButtonClick();
			} );

			const frequency = $( 'input[name="report-frequency"]' );
			frequency.on( 'change', this.handleFrequencySelect );
		},

		/**
		 * When editing notifications, make sure we have the proper selections for schedule data.
		 *
		 * @since 3.1.1
		 */
		addSelections() {
			$( '#report-time' )
				.val( this.settings.schedule.time )
				.trigger( 'change' );

			if ( 7 === this.settings.schedule.frequency ) {
				$( '#report-day' )
					.val( this.settings.schedule.weekDay )
					.trigger( 'change' );
			}

			if ( 30 === this.settings.schedule.frequency ) {
				$( '#report-day-month' )
					.val( this.settings.schedule.monthDay )
					.trigger( 'change' );
			}

			$( '#report-threshold' )
				.val( this.settings.schedule.threshold )
				.trigger( 'change' );
		},

		/**
		 * Due to how we load the template, we need to re-initialize the SUI related modules.
		 *
		 * @since 3.1.1
		 */
		initSUI() {
			$( '.sui-select' ).each( function() {
				const select = $( this );
				if ( 'icon' === select.data( 'theme' ) ) {
					SUI.select.initIcon( select );
				} else if ( 'color' === select.data( 'theme' ) ) {
					SUI.select.initColor( select );
				} else if ( 'search' === select.data( 'theme' ) ) {
					SUI.select.initSearch( select );
				} else {
					SUI.select.init( select );
				}
			} );

			SUI.modalDialog();
			SUI.tabs();
			SUI.notice();

			$( '.sui-side-tabs label.sui-tab-item input' ).each( function() {
				SUI.sideTabs( this );
			} );
		},

		/**
		 * Process frequency change in modal.
		 *
		 * @since 3.1.1
		 */
		handleFrequencySelect() {
			const freq = $( this ).val();
			const scheduleBox = $( '.schedule-box' );

			const weekDiv = scheduleBox.find( '[data-type="week"]' );
			const monthDiv = scheduleBox.find( '[data-type="month"]' );

			weekDiv.toggleClass( 'sui-hidden', '30' === freq || '1' === freq );
			monthDiv.toggleClass( 'sui-hidden', '7' === freq || '1' === freq );
		},

		/**
		 * Get module name string for tracking.
		 * Do not translate these strings, they are used in Mixpanel tracking.
		 *
		 * @since 3.1.1
		 * @return {string}  Module name.
		 */
		getModuleName() {
			let moduleName = '';

			if (
				'performance' === this.settings.module &&
				'reports' === this.settings.type
			) {
				moduleName = 'Performance Reports';
			} else if (
				'uptime' === this.settings.module &&
				'reports' === this.settings.type
			) {
				moduleName = 'Uptime Reports';
			} else if (
				'uptime' === this.settings.module &&
				'notifications' === this.settings.type
			) {
				moduleName = 'Uptime Notifications';
			}

			return moduleName;
		},

		/**
		 * Disable notification.
		 *
		 * @since 3.1.1
		 */
		disable() {
			event.preventDefault();

			const id = event.target.dataset.id;
			const type = event.target.dataset.type;

			if ( 'undefined' === typeof id || 'undefined' === typeof type ) {
				return;
			}

			const moduleName = WPHB_Admin.notifications.getModuleName();
			if ( '' !== moduleName ) {
				wphbMixPanel.disableFeature( moduleName );
			}

			HBFetcher.notifications
				.disable( id, type )
				.then( () => {
					window.location.search += '&status=disabled';
				} )
				.catch( ( error ) => {
					window.console.log( error );
				} );
		},

		/**
		 * Toggle Add Recipient button based on input. Disable if fields not filled.
		 *
		 * @since 3.1.1
		 */
		toggleAddButton() {
			const inputs = $(
				'#notifications-invite-users-content input[id^="recipient-"]'
			);

			inputs.on( 'keyup', function() {
				let empty = false;
				inputs.each( function() {
					if ( '' === $( this ).val() ) {
						empty = true;
					}
				} );

				if ( empty ) {
					$( '#add-recipient-button' ).attr( 'disabled', 'disabled' );
				} else {
					$( '#add-recipient-button' ).attr( 'disabled', false );
				}
			} );
		},

		/**
		 * Handle "Add recipient" button click on "Add by email" section of the modal.
		 *
		 * @since 3.1.1
		 */
		handleAddButtonClick() {
			const btn = event.target;
			btn.classList.add( 'sui-button-onload-text' );

			const name = $( 'input#recipient-name' );
			const email = $( 'input#recipient-email' );
			const err = $( '#error-recipient-email' );

			HBFetcher.notifications
				.getAvatar( email.val() )
				.then( ( avatar ) => {
					err.html( '' );
					err.parents().removeClass( 'sui-form-field-error' );

					const user = {
						name: name.val(),
						email: email.val(),
						role: '',
						avatar,
						id: 0,
					};

					this.confirmSubscription( user ).then( ( response ) => {
						if ( undefined !== response ) {
							user.is_pending = response.pending;
							user.is_subscribed = response.subscribed;
							user.is_can_resend_confirmation =
								response.canResend;
						}
						this.addUser( user, 'email' );

						// Reset inputs.
						name.val( '' ).trigger( 'keyup' );
						email.val( '' ).trigger( 'keyup' );
						btn.classList.remove( 'sui-button-onload-text' );
					} );
				} )
				.catch( ( error ) => {
					err.html( error );
					err.parents().addClass( 'sui-form-field-error' );
					btn.classList.remove( 'sui-button-onload-text' );
				} );
		},

		/**
		 * Initialize the search user select.
		 *
		 * @since 3.1.1
		 */
		initUserSelects() {
			const userSelect = $( '#search-users' );
			const self = this;

			userSelect.SUIselect2( {
				minimumInputLength: 3,
				maximumSelectionLength: 1,
				ajax: {
					url: ajaxurl,
					method: 'POST',
					dataType: 'json',
					delay: 250,
					data( params ) {
						return {
							action: 'wphb_pro_search_users',
							nonce: wphb.nonces.HBFetchNonce,
							query: params.term,
							exclude: self.exclude,
						};
					},
					processResults( data ) {
						return {
							results: jQuery.map(
								data.data,
								function( item, index ) {
									return {
										text: item.name,
										id: index,
										user: {
											name: item.name,
											email: item.email,
											role: item.role,
											avatar: item.avatar,
											id: item.id,
										},
									};
								}
							),
						};
					},
				},
			} );

			userSelect.on( 'select2:select', function( e ) {
				self.add( e.params.data.user );
				userSelect.val( null ).trigger( 'change' );
			} );
		},

		/**
		 * Send out a confirmation email to the user.
		 *
		 * @since 3.1.1
		 * @param {Object} user
		 */
		async confirmSubscription( user ) {
			if (
				'uptime' !== this.settings.module ||
				'notifications' !== this.settings.type
			) {
				return;
			}

			return HBFetcher.notifications.sendConfirmationEmail(
				user.name,
				user.email
			);
		},

		/**
		 * Resend invite.
		 *
		 * @since 3.1.1
		 * @param {string} name
		 * @param {string} email
		 */
		resendInvite( name, email ) {
			const self = $( this );
			self.attr( 'disabled', 'disabled' );
			HBFetcher.notifications
				.resendConfirmationEmail( name, email )
				.then( ( response ) => {
					const notice = $( '.notifications-resend-notice' );
					notice.find( 'p' ).html( response.message );
					notice.removeClass( 'sui-hidden' );
					self.attr( 'disabled', false );
				} );
		},

		/**
		 * Add a user recipient row to modal UI.
		 *
		 * @since 3.1.1
		 * @param {Object} user User object.
		 * @param {string} type Accepts: user ("Add users" section), email ("Add by email" section).
		 */
		addUser( user, type = 'user' ) {
			// Check if recipient already exists.
			const index = this.settings.recipients.findIndex(
				( r ) => user.email === r.email
			);
			if ( index > -1 ) {
				this.toggleUserNotice( true );
				return;
			}

			const recipientList = $( '#modal-' + type + '-recipients-list' );
			const tooltip = getString( 'removeRecipient' );
			const role = '' === user.role ? user.email : user.role;

			let subClass = '';
			if ( 'undefined' !== typeof user.is_pending ) {
				if (
					! user.is_pending &&
					'undefined' !== typeof user.is_subscribed &&
					! user.is_subscribed
				) {
					subClass = 'unsubscribed';
				} else {
					subClass = user.is_pending ? 'pending' : 'confirmed';
				}
			}

			let img = `<img src="${ user.avatar }" alt="${ user.email }">`;
			let confirmBtn = ``;
			if ( 'pending' === subClass || 'unsubscribed' === subClass ) {
				const confirmTooltip = getString( 'awaitingConfirmation' );
				const resendTooltip = getString( 'resendInvite' );
				img = `<span class="sui-tooltip" data-tooltip="${ confirmTooltip }">${ img }</span>`;
				confirmBtn = `<button type="button" class="resend-invite sui-button-icon sui-tooltip" data-tooltip="${ resendTooltip }"
					onclick="WPHB_Admin.notifications.resendInvite( '${ user.name }', '${ user.email }' )">
					<span class="sui-icon-send" aria-hidden="true"></span>
				</button>`;
			}

			const row = `
				<div class="sui-recipient" data-id="${ user.id }" data-email="${ user.email }">
					<span class="sui-recipient-name">
						<span class="subscriber ${ subClass }">${ img }</span>
						<span class="wphb-recipient-name">${ user.name }</span>
					</span>
					<span class="sui-recipient-email">${ role }</span>
					${ confirmBtn }
					<button type="button" class="sui-button-icon sui-tooltip" data-tooltip="${ tooltip }"
						onclick="WPHB_Admin.notifications.removeUser( ${ user.id }, '${ user.email }', '${ type }' )">
						<span class="sui-icon-trash" aria-hidden="true"></span>
					</button>
				</div>
			`;

			recipientList.append( row );

			// Add to the recipients and exclude arrays.
			this.settings.recipients.push( user );
			if ( 'user' === type ) {
				this.exclude.push( user.id );
			}

			this.toggleRecipientList( recipientList );
		},

		/**
		 * Populate "Users" list.
		 *
		 * @since 3.1.1
		 * @param {Object}  user
		 * @param {boolean} first
		 */
		addToUsersList( user, first = false ) {
			const recipientList = $( '#modal-wp-user-list' );

			const tooltipClass = first
				? 'sui-tooltip-bottom-right'
				: 'sui-tooltip-top-right';

			const row = `
				<div class="sui-recipient" data-id="${ user.id }" data-email="${ user.email }">
					<span class="sui-recipient-name">
						<span class="subscriber">
							<img src="${ user.avatar }" alt="${ user.email }">
						</span>
						<span class="wphb-recipient-name">${ user.name }</span>
					</span>
					<span class="sui-recipient-email">${ user.role }</span>
					<button type="button" class="sui-button-icon sui-tooltip ${ tooltipClass }"
						data-tooltip="${ getString( 'addRecipient' ) }"
						onclick='WPHB_Admin.notifications.add( ${ JSON.stringify( user ) } )'>
						<span class="sui-icon-plus" aria-hidden="true"></span>
					</button>
				</div>
			`;

			recipientList.append( row );
			this.toggleRecipientList( recipientList );
		},

		/**
		 * Remove a user recipient row from modal UI.
		 *
		 * @since 3.1.1
		 * @param {number} id    User ID to remove.
		 * @param {string} email User email.
		 * @param {string} type  Accepts: user ("Add users" section), email ("Add by email" section).
		 */
		removeUser( id, email, type = 'user' ) {
			const recipientList = $( '#modal-' + type + '-recipients-list' );
			const el = '.sui-recipient[data-email="' + email + '"]';

			// Remove Div.
			const row = recipientList.find( el );
			row.remove();

			// Remove from exclude list.
			let index;
			if ( 'user' === type ) {
				index = this.exclude.indexOf( id );
				if ( index > -1 ) {
					this.exclude.splice( index, 1 );
				}
				this.returnToList( row );
			}

			// Remove from recipients array.
			index = this.settings.recipients.findIndex(
				( r ) => id === parseInt( r.id ) && email === r.email
			);
			if ( index > -1 ) {
				this.settings.recipients.splice( index, 1 );
			}

			// Hide title if no more elements.
			this.toggleRecipientList( recipientList );
		},

		/**
		 * Add user from the "Users" list.
		 *
		 * @since 3.1.1
		 * @param {Object} user
		 */
		add( user ) {
			this.confirmSubscription( user ).then( ( response ) => {
				if ( undefined !== response ) {
					user.is_pending = response.pending;
					user.is_subscribed = response.subscribed;
					user.is_can_resend_confirmation = response.canResend;
				}

				this.addUser( user );

				const recipientList = $( '#modal-wp-user-list' );
				const el = '.sui-recipient[data-email="' + user.email + '"]';

				// Remove Div.
				recipientList.find( el ).remove();

				this.fixRecipientCSS( recipientList );
				this.toggleRecipientList( recipientList, false );
			} );
		},

		/**
		 * Return user back to "Users" list.
		 *
		 * @since 3.1.1
		 * @param {Object} el User row.
		 */
		returnToList( el ) {
			const recipientList = $( '#modal-wp-user-list' );

			const user = {
				id: el.data( 'id' ),
				name: el.find( '.wphb-recipient-name' ).text(),
				email: el.data( 'email' ),
				role: el.find( '.sui-recipient-email' ).text(),
				avatar: el.find( 'img' ).attr( 'src' ),
			};

			const onClickFunction =
				'WPHB_Admin.notifications.add(' + JSON.stringify( user ) + ')';

			// Remove the resend icon.
			el.find( '.resend-invite' ).remove();

			el.find( '.sui-icon-trash' )
				.removeClass( 'sui-icon-trash' )
				.addClass( 'sui-icon-plus' );

			el.find( 'button' )
				.attr( 'onclick', onClickFunction )
				.attr( 'data-tooltip', getString( 'addRecipient' ) )
				.addClass( 'sui-tooltip-top-right' );

			recipientList.append( el );

			this.fixRecipientCSS( recipientList );
			this.toggleRecipientList( recipientList, false );
		},

		/**
		 * Fix recipient overflow.
		 *
		 * @since 3.2.0
		 * @param {Object} list
		 */
		fixRecipientCSS( list ) {
			const val = list.children().length > 1 ? 'hidden' : 'unset';
			list.css( 'overflow-x', val );

			list.find( '.sui-recipient:first-of-type .sui-tooltip' )
				.removeClass( 'sui-tooltip-top-right' )
				.addClass( 'sui-tooltip-bottom-right' );
		},

		/**
		 * Show/hide recipient list based on child items.
		 *
		 * @since 3.1.1
		 * @param {Object}  el         Recipient list element.
		 * @param {boolean} userNotice Show user notice when no recipients.
		 */
		toggleRecipientList( el, userNotice = true ) {
			const hasItems = 0 === el.html().trim().length;

			el.parent( 'div' )
				.toggleClass( 'sui-hidden', hasItems )
				.toggleClass( 'sui-margin-top', ! hasItems );

			if ( userNotice ) {
				this.toggleUserNotice();
			}
		},

		/**
		 * Do not allow saving settings if no users are added to a notification.
		 *
		 * @since 3.1.1
		 * @param {boolean} recipientExists Show the recipient already exists notice.
		 */
		toggleUserNotice( recipientExists = false ) {
			const notice = $( '.notifications-recipients-notice' );
			const btn = $( '#notification-modal .sui-button.sui-button-blue' );
			const continueButton = $( '.notification-next-buttons' );

			let text = getString( 'noRecipients' );
			if ( recipientExists ) {
				text = getString( 'recipientExists' );
			} else if ( this.edit ) {
				text = getString( 'noRecipientDisable' );
			}

			notice.find( 'p' ).html( text );

			if ( recipientExists ) {
				notice.removeClass( 'sui-hidden' );
				setTimeout( () => notice.addClass( 'sui-hidden' ), 3000 );
			} else if ( 0 === this.settings.recipients.length ) {
				if ( ! this.edit ) {
					btn.attr( 'disabled', 'disabled' );
					continueButton.attr( 'disabled', 'disabled' );
				}
				notice.removeClass( 'sui-hidden' );
			} else {
				btn.attr( 'disabled', false );
				continueButton.attr( 'disabled', false );
				notice.addClass( 'sui-hidden' );
			}
		},
	};

	/**
	 * Template function (underscores based).
	 *
	 * @type {Function}
	 */
	WPHB_Admin.notifications.template = _.memoize( ( id ) => {
		let compiled;
		const options = {
			evaluate: /<#([\s\S]+?)#>/g,
			interpolate: /{{{([\s\S]+?)}}}/g,
			escape: /{{([^}]+?)}}(?!})/g,
			variable: 'data',
		};

		return ( data ) => {
			_.templateSettings = options;
			compiled = compiled || _.template( $( '#' + id ).html() );
			return compiled( data );
		};
	} );
} )( jQuery );
