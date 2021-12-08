import { getString } from '../utils/helpers';

const Row = ( _element, _filter, _filterSec, _filterType ) => {
	const $el = _element;
	const filter = _filter.toLowerCase();
	const filterSecondary = _filterSec ? _filterSec.toLowerCase() : false;
	const filterType = _filterType.toLowerCase();
	const $selectCheckbox = $el.find(
		'.wphb-minification-file-select input[type=checkbox]'
	);
	let selected = false;
	let visible = true;

	return {
		hide() {
			$el.addClass( 'out-of-filter' );
			visible = false;
		},

		show() {
			$el.removeClass( 'out-of-filter' );
			visible = true;
		},

		getElement() {
			return $el;
		},

		getId() {
			return $el.attr( 'id' );
		},

		getFilter() {
			return filter;
		},

		matchFilter( text ) {
			if ( text === '' ) {
				return true;
			}

			text = text.toLowerCase();
			return filter.search( text ) > -1;
		},

		matchSecondaryFilter( text ) {
			if ( text === '' ) {
				return true;
			}

			if ( ! filterSecondary ) {
				return false;
			}

			text = text.toLowerCase();
			return filterSecondary === text;
		},

		matchTypeFilter( text ) {
			if ( text === '' || ! filterType ) {
				return true;
			}

			if ( text === 'all' ) {
				return true;
			}

			return filterType === text;
		},

		isVisible() {
			return visible;
		},

		isSelected() {
			return selected;
		},

		isType( type ) {
			return type === $selectCheckbox.attr( 'data-type' );
		},

		select() {
			selected = true;
			$selectCheckbox.prop( 'checked', true );
		},

		unSelect() {
			selected = false;
			$selectCheckbox.prop( 'checked', false );
		},

		change( what, value ) {
			const el = $el.find( '.toggle-' + what );
			what = 'position-footer' === what ? 'footer' : what;

			// Only action for found items.
			if ( 'undefined' === typeof el ) {
				return;
			}

			// Skip disabled items.
			if ( true === el.prop( 'disabled' ) ) {
				return;
			}

			// Uppercase the type.
			const type = what.charAt( 0 ).toUpperCase() + what.slice( 1 );
			const tooltip = getString( value.toString() + type );

			// Change checkbox value.
			el.prop( 'checked', value );
			el.toggleClass( 'changed' );

			// Add the notice icon on the left of the row.
			el.closest( '.wphb-border-row' )
				.find( 'span.wphb-row-status' )
				.removeClass( 'hidden' );

			// Change the tooltip.
			el.next().attr( 'data-tooltip', tooltip );
		},
	};
};

export default Row;
