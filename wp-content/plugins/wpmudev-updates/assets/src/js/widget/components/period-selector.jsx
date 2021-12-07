/* global wdpI18n */
import React from 'react'
import eventBus from './../helpers/event-bus'

export default ({type, period}) => {
	/**
	 * Handle period change event.
	 *
	 * @since 4.11.6
	 */
	const handleChange = (ev) => {
		// Dispatch event.
		eventBus.dispatch('AnalyticsPeriodChange', {
			period: ev.target.value,
			type: type
		});
	};

	return (
		<>
			<label
				className="wpmudui-label"
				htmlFor={`wpmudui-analytics-range-${type}`}
			>{wdpI18n.labels.show}
			</label>
			<select
				id={`wpmudui-analytics-range-${type}`}
				className="wpmudui-select wpmudui-analytics-range"
				value={period.current}
				onChange={handleChange}
			>
				<option value="1">{wdpI18n.periods.yesterday}</option>
				<option value="7">{wdpI18n.periods.last7}</option>
				<option value="30">{wdpI18n.periods.last30}</option>
				<option value="90">{wdpI18n.periods.last90}</option>
			</select>
			{period.loading &&
			<span className="wpmudui-icon wpmudui-icon-loader wpmudui-loading wpmudui-period-loader">
			</span>
			}
		</>
	);
}