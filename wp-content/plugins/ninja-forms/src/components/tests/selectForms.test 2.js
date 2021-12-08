import { render, cleanup } from '@testing-library/react';
import { globalParams } from './mockData';
import { SelectForms } from '../';

describe( 'submissions-select-forms', () => {
	afterEach( cleanup );
	it( 'Matches snapshot', () => {
		const data = {
			data: {
				globalParams,
			},
		};

		const setForms = jest.fn();

		const { container } = render(
			<SelectForms data={ data } setForms={ setForms } />
		);
		expect( container ).toMatchSnapshot();
	} );
} );
