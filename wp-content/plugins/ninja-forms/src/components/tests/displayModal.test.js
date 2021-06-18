import { render, cleanup } from '@testing-library/react';
import { DisplayModal } from '..';

describe( 'submissions-display-modal', () => {
	afterEach( cleanup );

	const closeComp = jest.fn();
	const setModalOpen = jest.fn();
	it( 'Matches snapshot', () => {
		const { container } = render(
			<DisplayModal
				title="Cancel Bulk Export?"
				actionText="Yes"
				action={ closeComp }
				cancelText="No"
				cancel={ setModalOpen }
			/>
		);
		expect( container ).toMatchSnapshot();
	} );
} );
