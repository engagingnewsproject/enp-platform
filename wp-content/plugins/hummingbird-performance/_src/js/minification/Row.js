const Row = ( _element, _filter, _filter_sec, _filter_type ) => {
	let $el = _element,
		filter = _filter.toLowerCase(),
		filterSecondary = false,
		filterType = _filter_type.toLowerCase(),
		selected = false,
		visible = true;

	const $include = $el.find( '.toggle-include' ),
		$combine = $el.find( '.toggle-combine' ),
		$minify = $el.find( '.toggle-minify' ),
		$posFooter = $el.find( '.toggle-position-footer' ),
		$defer = $el.find( '.toggle-defer' ),
		$inline = $el.find( '.toggle-inline' ),
		$disableIcon = $el.find( '.toggle-cross > i' ),
		$selectCheckbox = $el.find(
			'.wphb-minification-file-select input[type=checkbox]'
		);

	if ( _filter_sec ) {
		filterSecondary = _filter_sec.toLowerCase();
	}

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
			switch ( what ) {
				case 'minify': {
					$minify.prop( 'checked', value );
					$minify.toggleClass( 'changed' );
					const row = $minify.closest( '.wphb-border-row' );
					const row_status = row.find( 'span.wphb-row-status' );
					row_status.removeClass( 'hidden' );
					break;
				}
				case 'combine': {
					$combine.prop( 'checked', value );
					$combine.toggleClass( 'changed' );
					const row = $combine.closest( '.wphb-border-row' );
					const row_status = row.find( 'span.wphb-row-status' );
					row_status.removeClass( 'hidden' );
					break;
				}
				case 'defer': {
					$defer.prop( 'checked', value );
					$defer.toggleClass( 'changed' );
					const row = $defer.closest( '.wphb-border-row' );
					const row_status = row.find( 'span.wphb-row-status' );
					row_status.removeClass( 'hidden' );
					break;
				}
				case 'inline': {
					$inline.prop( 'checked', value );
					$inline.toggleClass( 'changed' );
					const row = $inline.closest( '.wphb-border-row' );
					const row_status = row.find( 'span.wphb-row-status' );
					row_status.removeClass( 'hidden' );
					break;
				}
				case 'include': {
					$disableIcon.removeClass();
					$include.prop( 'checked', value );
					$include.toggleClass( 'changed' );
					const row = $include.closest( '.wphb-border-row' );
					const row_status = row.find( 'span.wphb-row-status' );
					row_status.removeClass( 'hidden' );
					if ( value ) {
						$el.removeClass( 'disabled' );
						$disableIcon.addClass( 'dev-icon dev-icon-cross' );
						$include.attr( 'checked', true );
					} else {
						$el.addClass( 'disabled' );
						$disableIcon.addClass( 'wdv-icon wdv-icon-refresh' );
						$include.removeAttr( 'checked' );
					}
					break;
				}
				case 'footer': {
					$posFooter.prop( 'checked', value );
					$posFooter.toggleClass( 'changed' );
					const row = $posFooter.closest( '.wphb-border-row' );
					const row_status = row.find( 'span.wphb-row-status' );
					row_status.removeClass( 'hidden' );
					break;
				}
			}
		},
	};
};

export default Row;
