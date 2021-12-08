import { render, cleanup } from '@testing-library/react';
import { globalParams } from './mockData';
import { SelectDates } from '../';

describe( 'submissions-select-dates', () => {
	afterEach( cleanup );
	it( 'Matches snapshot', () => {
		const dateFormat = globalParams.dateFormat;
		const timeFormat = globalParams.timeFormat;
		const setDates = jest.fn();

		const { container } = render(
			<SelectDates
				setDates={ setDates }
				dateFormat={ dateFormat }
				timeFormat={ timeFormat }
			/>
		);
		expect( container ).toMatchSnapshot();
	} );
} );
