/* global wdpI18n */
import React from 'react'

export default ({type, metrics, onChange}) => {
	/**
	 * Handle metric change event.
	 *
	 * @since 4.11.6
	 */
	const handleChange = (ev) => {
		onChange(ev.target.value)
	};

	// Metrics that needs to be skipped.
	let skip = ['visit_time']

	return (
		<>
			<label
				className="wpmudui-label"
				htmlFor={`wpmudui-analytics-${type}-type`}
			>{wdpI18n.labels.data_for}
			</label>
			<select
				id={`wpmudui-analytics-${type}-type`}
				className="wpmudui-select wpmudui-analytics-column-filter"
				onChange={handleChange}
			>
				{metrics.map((metric) => {
						return !skip.includes(metric.key) &&
							<option
								key={metric.key}
								value={metric.key}
							>
								{metric.name}
							</option>
					}
				)}
			</select>
		</>
	);
}