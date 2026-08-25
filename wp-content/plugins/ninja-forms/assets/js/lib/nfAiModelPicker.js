/**
 * Exact AI model picker behavior.
 *
 * Keeps the picker UI independent from the surface that owns the selected
 * provider and model. Parent surfaces receive the complete selection through
 * an optional callback and remain responsible for request state.
 *
 * Assistive technology sees a combobox that opens a popup: the trigger owns a
 * dialog, the dialog holds the search field, and the search field is the
 * combobox that drives one listbox per provider. The popup carries a search
 * field, per-provider expansion buttons, and (in the builder drawer) a start
 * new chat button, so it cannot itself be a listbox. Scoping a listbox to each
 * provider's options keeps every interactive control outside the lists while
 * the options stay real single-choice options with a selected state.
 *
 */
/* global define, jQuery, _ */
define( [], function () {
	'use strict';

	/**
	 * Number of pickers built so far, used to keep generated IDs unique.
	 *
	 * @type {number}
	 */
	let pickerCount = 0;

	/**
	 * Milliseconds a type-ahead buffer survives between keystrokes.
	 *
	 * @type {number}
	 */
	const TYPE_AHEAD_LIFETIME = 700;

	/**
	 * Create an exact-model picker controller.
	 *
	 * @param {Object}   options                        Picker options.
	 * @param {Object}   options.$element               Root picker element.
	 * @param {Object}   options.$provider              Provider input.
	 * @param {Object}   options.$model                 Model input.
	 * @param {Function} [options.onSelect]             Selection callback.
	 * @param {boolean}  [options.deferSelection=false] Whether selection waits for parent persistence.
	 * @param {string}   [options.label]                Localized purpose of the control.
	 * @return {Object} Picker controller.
	 * @class
	 */
	const ModelPicker = function ( options ) {
		this.$element = options.$element;
		this.$provider = options.$provider;
		this.$model = options.$model;
		this.onSelect = options.onSelect || function () {};
		this.deferSelection = true === options.deferSelection;
		this.label = String( options.label || '' );
		this.$trigger = this.$element.find( '.nf-ai-model-trigger' );
		this.$menu = this.$element.find( '.nf-ai-model-menu' );
		this.$search = this.$element.find( '.nf-ai-model-search' );
		this.$active = null;
		this.typed = '';
		this.typedAt = 0;
		this.swallowEscape = false;
		pickerCount += 1;
		this.idPrefix = 'nf-ai-model-' + pickerCount;
	};

	/**
	 * Bind picker interactions.
	 *
	 * @return {Object} Current picker controller.
	 */
	ModelPicker.prototype.bind = function () {
		this.$trigger
			.off( 'click.nfAiModel' )
			.on( 'click.nfAiModel', this.handleTrigger.bind( this ) );
		this.$element
			.find( '.nf-ai-model-option' )
			.off( 'click.nfAiModel' )
			.on( 'click.nfAiModel', this.handleOption.bind( this ) );
		this.$element
			.find( '.nf-ai-model-show-all' )
			.off( 'click.nfAiModel' )
			.on( 'click.nfAiModel', this.handleShowAll.bind( this ) );
		this.$search
			.off( 'input.nfAiModel' )
			.on( 'input.nfAiModel', this.handleSearch.bind( this ) );
		/*
		 * Capture, not bubble. The builder binds its Escape hotkey straight
		 * onto every input on the page, including this picker's own search
		 * field, so those handlers run in the target phase. A bubble-phase
		 * listener on this root would arrive after the drawer had already
		 * closed and taken the administrator's typed message with it.
		 */
		if ( this.boundKeydown ) {
			this.$element[ 0 ].removeEventListener(
				'keydown',
				this.boundKeydown,
				true
			);
		}
		this.boundKeydown = this.handleKeydown.bind( this );
		this.$element[ 0 ].addEventListener(
			'keydown',
			this.boundKeydown,
			true
		);
		this.$element
			.off( 'keyup.nfAiModel' )
			.on( 'keyup.nfAiModel', this.handleKeyup.bind( this ) );
		this.$menu
			.off(
				'wheel.nfAiModel mousewheel.nfAiModel DOMMouseScroll.nfAiModel'
			)
			.on(
				'wheel.nfAiModel mousewheel.nfAiModel DOMMouseScroll.nfAiModel',
				this.handleMenuWheel.bind( this )
			);
		this.describeControl();
		return this;
	};

	/**
	 * Keep wheel events over the menu inside the menu.
	 *
	 * The menu scrolls itself, but surfaces that host the picker guard their
	 * own scrolling. The jBox modal binds legacy wheel events on its content
	 * element and cancels them from that element's scroll position, which never
	 * leaves the top, so every upward wheel over the menu was cancelled and the
	 * list scrolled down but never back up. Claiming the event here leaves the
	 * browser to scroll the menu in both directions, and the menu's own
	 * overscroll-behavior still keeps the page behind from taking over at the
	 * ends of the list.
	 *
	 * @param {Event} event Browser wheel event.
	 * @return {void}
	 */
	ModelPicker.prototype.handleMenuWheel = function ( event ) {
		event.stopPropagation();
	};

	/**
	 * Describe the trigger, popup, and search field to assistive technology.
	 *
	 * @return {void}
	 */
	ModelPicker.prototype.describeControl = function () {
		const menuId = this.idPrefix + '-menu';

		this.$menu.attr( {
			id: menuId,
			role: 'dialog',
			'aria-label': this.label || this.searchLabel(),
		} );
		this.$trigger.attr( {
			'aria-haspopup': 'dialog',
			'aria-controls': menuId,
			'aria-expanded': this.isOpen() ? 'true' : 'false',
		} );
		this.$search.attr( {
			id: this.idPrefix + '-search',
			role: 'combobox',
			'aria-haspopup': 'listbox',
			'aria-autocomplete': 'list',
			'aria-label': this.searchLabel(),
		} );
		this.describeTrigger();
		this.describePopupState();
	};

	/**
	 * Give the trigger a name that carries both purpose and current value.
	 *
	 * @return {void}
	 */
	ModelPicker.prototype.describeTrigger = function () {
		const currentId = this.idPrefix + '-current';
		const $label = this.resolveLabel();
		const names = [];

		this.$element.find( '.nf-ai-model-current' ).attr( 'id', currentId );
		if ( $label.length ) {
			names.push( $label.attr( 'id' ) );
		}
		names.push( currentId );
		this.$trigger.attr( 'aria-labelledby', names.join( ' ' ) );
	};

	/**
	 * Find the element that states what the picker chooses.
	 *
	 * The generation modal prints a visible label above the picker that names
	 * no control, so adopt it. Surfaces without one carry the picker's purpose
	 * in a screen-reader-only label inside the trigger.
	 *
	 * @return {Object} Label element, empty when there is nothing to name it with.
	 */
	ModelPicker.prototype.resolveLabel = function () {
		const labelId = this.idPrefix + '-label';
		const $visible = this.$element.prev( 'label' ).not( '[for]' );

		if ( $visible.length ) {
			return $visible.attr( 'id', labelId );
		}
		if ( '' === this.label ) {
			return jQuery();
		}
		let $hidden = this.$trigger.find( '.nf-ai-model-trigger-label' );
		if ( 0 === $hidden.length ) {
			$hidden = jQuery( '<span>' )
				.addClass( 'nf-ai-model-trigger-label screen-reader-text' )
				.prependTo( this.$trigger );
		}
		return $hidden.attr( 'id', labelId ).text( this.label );
	};

	/**
	 * Read the localized name of the search field.
	 *
	 * @return {string} Search field label.
	 */
	ModelPicker.prototype.searchLabel = function () {
		return String( this.$search.attr( 'placeholder' ) || '' );
	};

	/**
	 * Report the popup's current state to the combobox.
	 *
	 * @return {void}
	 */
	ModelPicker.prototype.describePopupState = function () {
		const $lists = this.$element.find( '.nf-ai-model-options' );
		const ids = [];

		$lists.each( function () {
			ids.push( jQuery( this ).attr( 'id' ) );
		} );
		this.$search.attr( 'aria-controls', ids.join( ' ' ) );
		this.$search.attr(
			'aria-expanded',
			this.isOpen() && 0 < this.visibleOptions().length ? 'true' : 'false'
		);
	};

	/**
	 * Report whether the menu is open.
	 *
	 * @return {boolean} Whether the menu is open.
	 */
	ModelPicker.prototype.isOpen = function () {
		return this.$element.hasClass( 'is-open' );
	};

	/**
	 * Toggle the model menu from the trigger button.
	 *
	 * @param {Event} event Browser click event.
	 * @return {void}
	 */
	ModelPicker.prototype.handleTrigger = function ( event ) {
		event.preventDefault();
		event.stopPropagation();
		this.toggle();
	};

	/**
	 * Store an option selected by the user.
	 *
	 * @param {Event} event Browser click event.
	 * @return {void}
	 */
	ModelPicker.prototype.handleOption = function ( event ) {
		event.preventDefault();
		event.stopPropagation();
		this.choose( jQuery( event.currentTarget ) );
	};

	/**
	 * Apply the selection an option element carries.
	 *
	 * @param {Object} $option Model option element.
	 * @return {void}
	 */
	ModelPicker.prototype.choose = function ( $option ) {
		const choice = this.getOptionChoice( $option );
		if ( this.deferSelection ) {
			this.onSelect( choice );
			return;
		}
		this.select( choice );
	};

	/**
	 * Reveal every compatible model in one provider group.
	 *
	 * @param {Event} event Browser click event.
	 * @return {void}
	 */
	ModelPicker.prototype.handleShowAll = function ( event ) {
		event.preventDefault();
		event.stopPropagation();
		const $button = jQuery( event.currentTarget );
		const $group = $button.closest( '.nf-ai-model-group' );
		$group.addClass( 'show-all' );
		this.updateGroups();
		/*
		 * The button that was just pressed is now gone, so focus would fall to
		 * the document and the arrow keys would stop moving the cursor. Put it
		 * back in the search field, where the popup drives the list from, and
		 * park the cursor on the first model the expansion revealed.
		 */
		this.$search.trigger( 'focus' );
		const $revealed = this.visibleOptions( $group ).filter( '.is-extra' );
		if ( $revealed.length ) {
			this.setActive( $revealed.first() );
		}
	};

	/**
	 * Route keyboard interaction for the trigger and the open popup.
	 *
	 * Focus stays in the search field while the popup is open, so the list is
	 * driven through aria-activedescendant. Keys that mean something to text
	 * being edited (Space, Home, End) only act on the list while the search
	 * field is empty, and the expansion buttons keep their own Enter and Space.
	 *
	 * @param {KeyboardEvent} event Key event.
	 * @return {void}
	 */
	ModelPicker.prototype.handleKeydown = function ( event ) {
		this.swallowEscape = false;
		if ( 'Escape' === event.key ) {
			if ( ! this.isOpen() ) {
				return;
			}
			event.preventDefault();
			event.stopPropagation();
			this.swallowEscape = true;
			this.dismiss();
			return;
		}
		if ( jQuery( event.target ).is( this.$trigger ) ) {
			this.handleTriggerKey( event );
			return;
		}
		if ( ! this.isOpen() ) {
			return;
		}
		this.handleMenuKey( event );
	};

	/**
	 * Drive the list from keys pressed inside the open popup.
	 *
	 * @param {KeyboardEvent} event Key event.
	 * @return {void}
	 */
	ModelPicker.prototype.handleMenuKey = function ( event ) {
		switch ( event.key ) {
			case 'ArrowDown':
				event.preventDefault();
				this.moveActive( 1 );
				return;
			case 'ArrowUp':
				event.preventDefault();
				this.moveActive( -1 );
				return;
			case 'Home':
				if ( this.isEditing( event ) ) {
					return;
				}
				event.preventDefault();
				this.setActive( this.visibleOptions().first() );
				return;
			case 'End':
				if ( this.isEditing( event ) ) {
					return;
				}
				event.preventDefault();
				this.setActive( this.visibleOptions().last() );
				return;
			case 'Enter':
				if ( this.isOnShowAll( event ) ) {
					return;
				}
				event.preventDefault();
				this.chooseActive();
				return;
			case ' ':
			case 'Spacebar':
				if ( this.isOnShowAll( event ) || this.isEditing( event ) ) {
					return;
				}
				event.preventDefault();
				this.chooseActive();
				return;
			default:
				break;
		}
		if ( ! jQuery( event.target ).is( this.$search ) ) {
			this.handleTypeAhead( event );
		}
	};

	/**
	 * Report whether a key belongs to text the user is editing.
	 *
	 * @param {KeyboardEvent} event Key event.
	 * @return {boolean} Whether the search field holds a query.
	 */
	ModelPicker.prototype.isEditing = function ( event ) {
		return (
			jQuery( event.target ).is( this.$search ) &&
			'' !== String( this.$search.val() || '' )
		);
	};

	/**
	 * Report whether a key belongs to a group expansion button.
	 *
	 * @param {KeyboardEvent} event Key event.
	 * @return {boolean} Whether the event came from an expansion button.
	 */
	ModelPicker.prototype.isOnShowAll = function ( event ) {
		return jQuery( event.target ).is( '.nf-ai-model-show-all' );
	};

	/**
	 * Keep the Escape that closed the picker from reaching the host surface.
	 *
	 * The generation modal and the builder drawer both close themselves on the
	 * document's Escape keyup, which arrives after the keydown this picker
	 * already answered. Without this the first Escape would close the whole
	 * surface and take the typed prompt with it.
	 *
	 * @param {KeyboardEvent} event Key event.
	 * @return {void}
	 */
	ModelPicker.prototype.handleKeyup = function ( event ) {
		if ( 'Escape' !== event.key || ! this.swallowEscape ) {
			return;
		}
		this.swallowEscape = false;
		event.stopPropagation();
	};

	/**
	 * Open the menu from the trigger's own keys.
	 *
	 * @param {KeyboardEvent} event Key event.
	 * @return {void}
	 */
	ModelPicker.prototype.handleTriggerKey = function ( event ) {
		if ( 'ArrowDown' !== event.key && 'ArrowUp' !== event.key ) {
			return;
		}
		event.preventDefault();
		if ( ! this.isOpen() ) {
			this.open();
			return;
		}
		this.moveActive( 'ArrowDown' === event.key ? 1 : -1 );
	};

	/**
	 * Jump to the first option whose label starts with what was typed.
	 *
	 * @param {KeyboardEvent} event Key event.
	 * @return {void}
	 */
	ModelPicker.prototype.handleTypeAhead = function ( event ) {
		if ( event.altKey || event.ctrlKey || event.metaKey ) {
			return;
		}
		if ( 1 !== String( event.key || '' ).length ) {
			return;
		}
		const now = new Date().getTime();
		this.typed =
			now - this.typedAt > TYPE_AHEAD_LIFETIME
				? event.key
				: this.typed + event.key;
		this.typedAt = now;
		const query = this.typed.toLowerCase();
		const $match = this.visibleOptions().filter( function () {
			return 0 === jQuery( this ).text().toLowerCase().indexOf( query );
		} );

		if ( 0 === $match.length ) {
			return;
		}
		event.preventDefault();
		this.setActive( $match.first() );
	};

	/**
	 * Filter compatible model options by their visible labels.
	 *
	 * @return {void}
	 */
	ModelPicker.prototype.handleSearch = function () {
		const query = String( this.$search.val() || '' ).toLowerCase();

		this.$menu.toggleClass( 'is-searching', '' !== query );
		this.$element.find( '.nf-ai-model-option' ).each( function () {
			const $option = jQuery( this );
			const matches =
				-1 !== $option.text().toLowerCase().indexOf( query );

			/*
			 * A class, not an inline style. jQuery's toggle writes display
			 * inline, which outranks the stylesheet rule that hides the
			 * models behind Show all, so clearing a search used to reveal
			 * them for good while the keyboard cursor still skipped them.
			 */
			$option.toggleClass( 'is-filtered-out', '' !== query && ! matches );
		} );
		this.updateGroups();
		this.setActive( this.visibleOptions().first() );
	};

	/**
	 * Keep provider groups and their lists in step with what is left to offer.
	 *
	 * A list with no visible options is hidden so assistive technology never
	 * meets an empty listbox, and a search that excludes a whole provider hides
	 * that provider's heading with it.
	 *
	 * @return {void}
	 */
	ModelPicker.prototype.updateGroups = function () {
		const picker = this;
		const searching = this.$menu.hasClass( 'is-searching' );

		this.$element.find( '.nf-ai-model-group' ).each( function () {
			const $group = jQuery( this );
			const filled = 0 < picker.visibleOptions( $group ).length;

			$group.find( '.nf-ai-model-options' ).toggle( filled );
			$group.toggle( filled || ! searching );
		} );
		this.describePopupState();
	};

	/**
	 * Toggle the picker menu.
	 *
	 * @return {void}
	 */
	ModelPicker.prototype.toggle = function () {
		if ( this.isOpen() ) {
			this.close();
			return;
		}
		this.open();
	};

	/**
	 * Open the picker menu and focus its search field.
	 *
	 * @return {void}
	 */
	ModelPicker.prototype.open = function () {
		this.$element.addClass( 'is-open' );
		this.$menu.prop( 'hidden', false );
		this.$trigger.attr( 'aria-expanded', 'true' );
		this.$search.trigger( 'focus' );
		const $selected = this.visibleOptions().filter( '.is-selected' );
		this.setActive(
			$selected.length ? $selected.first() : this.visibleOptions().first()
		);
		this.watchForOutsideClick();
	};

	/**
	 * Let a click anywhere else dismiss the menu.
	 *
	 * The menu overlays the content beneath it and carries no close control, so
	 * without this a mouse user who opened it by accident could only choose a
	 * model they did not want. Escape already closes it, which does not help
	 * someone reaching for the mouse.
	 *
	 * Capture phase on the document: a host that stops propagation on its own
	 * content, as the generation modal does, would otherwise swallow the click
	 * before it ever reached here. Clicks inside the picker are left alone, so
	 * the trigger keeps closing the menu through its own toggle rather than
	 * being closed here and reopened by that toggle.
	 *
	 * @return {void}
	 */
	ModelPicker.prototype.watchForOutsideClick = function () {
		this.stopWatchingForOutsideClick();
		this.boundOutsideClick = ( event ) => {
			if ( ! this.isOpen() || this.$element[ 0 ].contains( event.target ) ) {
				return;
			}
			// No focus move: the click itself decides where focus lands, and
			// pulling it back to the trigger would fight the user's intent.
			this.close();
		};
		document.addEventListener( 'mousedown', this.boundOutsideClick, true );
	};

	/**
	 * Stop listening for dismissing clicks.
	 *
	 * @return {void}
	 */
	ModelPicker.prototype.stopWatchingForOutsideClick = function () {
		if ( ! this.boundOutsideClick ) {
			return;
		}
		document.removeEventListener(
			'mousedown',
			this.boundOutsideClick,
			true
		);
		this.boundOutsideClick = null;
	};

	/**
	 * Close the picker menu and put focus back on the trigger.
	 *
	 * Focus moves first: hiding the popup while it still holds focus makes the
	 * browser drop focus to the document, and a focus call made after that is
	 * overridden by the browser's own recovery.
	 *
	 * @return {void}
	 */
	ModelPicker.prototype.dismiss = function () {
		this.$trigger.trigger( 'focus' );
		this.close();
	};

	/**
	 * Close the picker menu.
	 *
	 * @return {void}
	 */
	ModelPicker.prototype.close = function () {
		this.stopWatchingForOutsideClick();
		this.$element.removeClass( 'is-open' );
		this.$menu.prop( 'hidden', true );
		this.$trigger.attr( 'aria-expanded', 'false' );
		this.setActive( null );
	};

	/**
	 * List the options a keyboard user can reach right now.
	 *
	 * Availability is read from the option itself rather than from rendered
	 * visibility, because the whole popup is hidden while it is closed: an
	 * option counts unless the search excluded it or it is an extra model
	 * whose group has not been expanded.
	 *
	 * @param {Object} [$scope] Element to look inside, defaulting to the picker.
	 * @return {Object} Reachable option elements.
	 */
	ModelPicker.prototype.visibleOptions = function ( $scope ) {
		const searching = this.$menu.hasClass( 'is-searching' );

		return ( $scope || this.$element )
			.find( '.nf-ai-model-option' )
			.filter( function () {
				if ( jQuery( this ).hasClass( 'is-filtered-out' ) ) {
					return false;
				}
				if ( ! jQuery( this ).hasClass( 'is-extra' ) ) {
					return true;
				}
				return (
					searching ||
					jQuery( this )
						.closest( '.nf-ai-model-group' )
						.hasClass( 'show-all' )
				);
			} );
	};

	/**
	 * Move the keyboard cursor by one visible option, wrapping at the ends.
	 *
	 * @param {number} step Direction to move in.
	 * @return {void}
	 */
	ModelPicker.prototype.moveActive = function ( step ) {
		const $options = this.visibleOptions();
		const current =
			this.$active && this.$active.length
				? $options.index( this.$active[ 0 ] )
				: -1;

		if ( 0 === $options.length ) {
			return;
		}
		if ( 0 > current ) {
			this.setActive( 0 < step ? $options.first() : $options.last() );
			return;
		}
		let next = current + step;
		if ( 0 > next ) {
			next = $options.length - 1;
		}
		if ( next >= $options.length ) {
			next = 0;
		}
		this.setActive( $options.eq( next ) );
	};

	/**
	 * Mark one option as the keyboard cursor without moving focus.
	 *
	 * @param {Object|null} $option Option element, or null to clear the cursor.
	 * @return {void}
	 */
	ModelPicker.prototype.setActive = function ( $option ) {
		this.$element
			.find( '.nf-ai-model-option.is-active' )
			.removeClass( 'is-active' );
		if ( ! $option || 0 === $option.length ) {
			this.$active = null;
			this.$search.removeAttr( 'aria-activedescendant' );
			this.describePopupState();
			return;
		}
		this.$active = $option.first();
		this.$active.addClass( 'is-active' );
		this.$search.attr( 'aria-activedescendant', this.$active.attr( 'id' ) );
		this.revealActive();
		this.describePopupState();
	};

	/**
	 * Scroll the menu just far enough to show the keyboard cursor.
	 *
	 * Only the menu's own scroll position moves, so opening the picker never
	 * scrolls the modal or the builder behind it.
	 *
	 * @return {void}
	 */
	ModelPicker.prototype.revealActive = function () {
		const menu = this.$menu[ 0 ];
		const option = this.$active ? this.$active[ 0 ] : null;

		if ( ! menu || ! option || ! option.getBoundingClientRect ) {
			return;
		}
		const menuBox = menu.getBoundingClientRect();
		const optionBox = option.getBoundingClientRect();
		if ( optionBox.top < menuBox.top ) {
			menu.scrollTop -= menuBox.top - optionBox.top;
			return;
		}
		if ( optionBox.bottom > menuBox.bottom ) {
			menu.scrollTop += optionBox.bottom - menuBox.bottom;
		}
	};

	/**
	 * Apply the option the keyboard cursor rests on.
	 *
	 * @return {void}
	 */
	ModelPicker.prototype.chooseActive = function () {
		if ( ! this.$active || 0 === this.$active.length ) {
			return;
		}
		this.choose( this.$active );
	};

	/**
	 * Read a selection from one option element.
	 *
	 * @param {Object} $option Model option element.
	 * @return {Object} Exact provider/model selection.
	 */
	ModelPicker.prototype.getOptionChoice = function ( $option ) {
		return {
			provider: String( $option.data( 'provider' ) || '' ),
			providerName: String( $option.data( 'provider-name' ) || '' ),
			model: String( $option.data( 'model' ) || '' ),
			modelName: String( $option.data( 'model-name' ) || '' ),
		};
	};

	/**
	 * Apply and announce an exact provider/model selection.
	 *
	 * @param {Object} choice              Exact selection.
	 * @param {string} choice.provider     Provider ID.
	 * @param {string} choice.providerName Provider display name.
	 * @param {string} choice.model        Model ID.
	 * @param {string} choice.modelName    Model display name.
	 * @return {void}
	 */
	ModelPicker.prototype.select = function ( choice ) {
		this.commit( choice );
		this.onSelect( choice );
	};

	/**
	 * Commit a selection to fields and visible state without announcing it.
	 *
	 * @param {Object} choice Exact selection.
	 * @return {void}
	 */
	ModelPicker.prototype.commit = function ( choice ) {
		this.$provider.val( choice.provider );
		this.$model.val( choice.model );
		this.renderChoice( choice );
		this.$element
			.find( '.nf-ai-model-option' )
			.removeClass( 'is-selected' )
			.attr( 'aria-selected', 'false' )
			.filter( function () {
				const $option = jQuery( this );
				return (
					choice.provider ===
						String( $option.data( 'provider' ) || '' ) &&
					choice.model === String( $option.data( 'model' ) || '' )
				);
			} )
			.addClass( 'is-selected' )
			.attr( 'aria-selected', 'true' );
		this.dismiss();
	};

	/**
	 * Render grouped model options for surfaces that receive provider data.
	 *
	 * @param {Array}  providers    Provider/model rows.
	 * @param {Object} choice       Current provider/model IDs.
	 * @param {string} showAllLabel Localized expansion label.
	 * @param {string} emptyLabel   Localized empty-selection label.
	 * @return {void}
	 */
	ModelPicker.prototype.render = function (
		providers,
		choice,
		showAllLabel,
		emptyLabel
	) {
		const $list = this.$element.find( '.nf-ai-model-list' );
		const prefix = this.idPrefix;
		let current = null;
		$list.empty();
		_.each( providers || [], function ( provider, providerIndex ) {
			const groupId = prefix + '-group-' + providerIndex;
			const $group = jQuery( '<div>' ).addClass( 'nf-ai-model-group' );
			const $options = jQuery( '<div>' )
				.addClass( 'nf-ai-model-options' )
				.attr( {
					id: prefix + '-listbox-' + providerIndex,
					role: 'listbox',
					'aria-labelledby': groupId,
				} );
			jQuery( '<strong>' )
				.attr( 'id', groupId )
				.text( provider.name )
				.appendTo( $group );
			$options.appendTo( $group );
			_.each( provider.models || [], function ( model, modelIndex ) {
				const optionChoice = {
					provider: String( provider.id || '' ),
					providerName: String( provider.name || '' ),
					model: String( model.id || '' ),
					modelName: String( model.name || '' ),
				};
				const selected =
					choice.provider === optionChoice.provider &&
					choice.model === optionChoice.model;
				jQuery( '<button type="button">' )
					.addClass(
						'nf-ai-model-option' +
							( model.recommended ? '' : ' is-extra' ) +
							( selected ? ' is-selected' : '' )
					)
					.attr( {
						id:
							prefix +
							'-option-' +
							providerIndex +
							'-' +
							modelIndex,
						role: 'option',
						tabindex: '-1',
						'aria-selected': selected ? 'true' : 'false',
						'data-provider': optionChoice.provider,
						'data-provider-name': optionChoice.providerName,
						'data-model': optionChoice.model,
						'data-model-name': optionChoice.modelName,
					} )
					.text( optionChoice.modelName )
					.appendTo( $options );
				if ( selected ) {
					current = optionChoice;
				}
			} );
			jQuery( '<button type="button">' )
				.addClass( 'nf-ai-model-show-all' )
				.attr( 'aria-describedby', groupId )
				.text( showAllLabel )
				.appendTo( $group );
			$group.appendTo( $list );
		} );
		this.$element
			.find( '.nf-ai-model-current' )
			.text(
				current
					? current.providerName + ' · ' + current.modelName
					: emptyLabel
			);
		this.bind();
		this.updateGroups();
	};

	/**
	 * Render the selected provider and model label.
	 *
	 * @param {Object} choice              Exact selection.
	 * @param {string} choice.providerName Provider display name.
	 * @param {string} choice.modelName    Model display name.
	 * @return {void}
	 */
	ModelPicker.prototype.renderChoice = function ( choice ) {
		this.$element
			.find( '.nf-ai-model-current' )
			.text( choice.providerName + ' · ' + choice.modelName );
	};

	/**
	 * Synchronize the visible label with the hidden selection fields.
	 *
	 * @return {Object|null} Exact selection, or null when it is unavailable.
	 */
	ModelPicker.prototype.syncFromFields = function () {
		const provider = String( this.$provider.val() || '' );
		const model = String( this.$model.val() || '' );
		const $match = this.$element
			.find( '.nf-ai-model-option' )
			.filter( function () {
				const $option = jQuery( this );
				return (
					provider === String( $option.data( 'provider' ) || '' ) &&
					model === String( $option.data( 'model' ) || '' )
				);
			} )
			.first();

		if ( 0 === $match.length ) {
			return null;
		}
		this.renderChoice( this.getOptionChoice( $match ) );
		return this.getOptionChoice( $match );
	};

	/**
	 * Focus the picker trigger.
	 *
	 * @return {void}
	 */
	ModelPicker.prototype.focus = function () {
		this.$trigger.trigger( 'focus' );
	};

	return ModelPicker;
} );
