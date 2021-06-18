import { render, cleanup } from '@testing-library/react';
import { form } from './mockData';
import { SelectAction } from '../';

describe( 'submissions-select-action', () => {
	afterEach( cleanup );
	it( 'Matches snapshot', () => {
		const setEmailAction = jest.fn();

		const { container } = render(
			<SelectAction form={ form } setAction={ setEmailAction } />
		);
		expect( container ).toMatchSnapshot();
	} );
} );
