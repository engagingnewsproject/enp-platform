const RowsCollection = () => {
	const items = [];
	let currentFilter = '';
	let currentSecondaryFilter = '';
	let currentTypeFilter = '';

	return {
		push( row ) {
			if ( typeof row === 'object' ) {
				items.push( row );
			}
		},

		getItems() {
			return items;
		},

		getItem( i ) {
			if ( items[ i ] ) {
				return items[ i ];
			}
			return false;
		},

		/**
		 * Get a collection item by type and ID
		 *
		 * @param type
		 * @param id
		 */
		getItemById( type, id ) {
			let value = false;
			for ( const i in items ) {
				if ( 'wphb-file-' + type + '-' + id === items[ i ].getId() ) {
					value = items[ i ];
					break;
				}
			}
			return value;
		},

		getItemsByDataType( type ) {
			const selected = [];

			for ( const i in items ) {
				if ( items[ i ].isType( type ) ) {
					selected.push( items[ i ] );
				}
			}

			return selected;
		},

		getVisibleItems() {
			const visible = [];
			for ( const i in items ) {
				if ( items[ i ].isVisible() ) {
					visible.push( items[ i ] );
				}
			}
			return visible;
		},

		getSelectedItems() {
			const selected = [];

			for ( const i in items ) {
				if ( items[ i ].isVisible() && items[ i ].isSelected() ) {
					selected.push( items[ i ] );
				}
			}

			return selected;
		},

		addFilter( filter, type ) {
			if ( type === 'type' ) {
				currentTypeFilter = filter;
			} else if ( type === 'secondary' ) {
				currentSecondaryFilter = filter;
			} else {
				currentFilter = filter;
			}
		},

		/**
		 * Clear selected filters.
		 *
		 * @since 2.7.1
		 */
		clearFilters() {
			currentFilter = '';
			currentSecondaryFilter = '';
			currentTypeFilter = '';
			this.applyFilters();
		},

		applyFilters() {
			for ( const i in items ) {
				if ( items[ i ] ) {
					if (
						items[ i ].matchFilter( currentFilter ) &&
						items[ i ].matchSecondaryFilter(
							currentSecondaryFilter
						) &&
						items[ i ].matchTypeFilter( currentTypeFilter )
					) {
						items[ i ].show();
					} else {
						items[ i ].hide();
					}
				}
			}
		},
	};
};

export default RowsCollection;
