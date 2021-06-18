import { render, cleanup } from '@testing-library/react';
import { DisplayNotice } from '../';

describe( 'submissions-display-notice', () => {
	afterEach( cleanup );
	it( 'Matches snapshot', () => {
		const { container } = render(
			<DisplayNotice isDismissible="false" text="Count emails sent" />
		);
		expect( container ).toMatchSnapshot();
	} );
} );
