import React from 'react';
import ChartOptionButton from './chart-option-button'

export default ({totals, options, current, handleClick}) => {
	// Remove unique page views.
	let metrics = options.filter(option => option.key !== 'unique_pageviews');

	return (
		<div className="wpmudui-chart-options">
			{metrics.map((metric) => {
					return totals.hasOwnProperty(metric.key) &&
						<ChartOptionButton
							key={metric.key}
							name={metric.key}
							total={totals[metric.key]}
							option={metric}
							current={metric.key === current}
							handleClick={(key) => handleClick(key)}
						/>
				}
			)}
		</div>
	);
}