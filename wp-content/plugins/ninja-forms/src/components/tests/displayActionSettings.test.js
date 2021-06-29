import { render, cleanup } from '@testing-library/react';
import { action } from './mockData';
import { DisplayActionSettings } from '../';

describe( 'submissions-display-action-settings', () => {
	afterEach( cleanup );
	it( 'Matches snapshot', () => {
		const { container } = render(
			<DisplayActionSettings value={ action } />
		);
		expect( container ).toMatchSnapshot();
	} );
} );
