import { render, cleanup } from '@testing-library/react';
import { globalParams, bulkExportParams } from './mockData';
import { TriggerBulkExportComponent } from '../';

describe( 'submissions-trigger-bulk-export-component', () => {
	afterEach( cleanup );
	it( 'Matches snapshot', () => {
		const props = { globalParams, bulkExportParams };

		const { container } = render(
			<TriggerBulkExportComponent props={ props } />
		);
		expect( container ).toMatchSnapshot();
	} );
} );
