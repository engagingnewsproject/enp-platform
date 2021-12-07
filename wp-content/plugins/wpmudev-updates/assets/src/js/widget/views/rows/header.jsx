import React from 'react'

export default ({title, metrics, currentMetric}) => {
	return (
		<tr>
			<th>{title}</th>
			{metrics.map((metric) =>
				<th
					key={metric.key}
					className={`data-${metric.key} ${metric.key === currentMetric ? 'wpmudui-current' : ''} wpmudui-table-views wpmudui-tooltip wpmudui-tooltip-top wpmudui-tooltip-top-right wpmudui-tooltip-constrained`}
					data-tooltip={metric.desc}
				>
					{metric.name}
				</th>
			)}
		</tr>
	);
}