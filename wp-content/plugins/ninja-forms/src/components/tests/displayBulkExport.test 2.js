import { render, cleanup } from '@testing-library/react';
import { globalParams, bulkExportParams } from './mockData';
import { DisplayBulkExport } from '../';

describe( 'submissions-display-bulk-export', () => {
	afterEach( cleanup );

	const props = { globalParams, bulkExportParams };
	it( 'Matches snapshot', () => {
		const { container } = render( <DisplayBulkExport data={ props } /> );
		expect( container ).toMatchSnapshot();
	} );
} );
