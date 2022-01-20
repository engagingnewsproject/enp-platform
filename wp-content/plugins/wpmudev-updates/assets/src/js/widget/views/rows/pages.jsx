/* global wdpI18n */
import React from 'react'

export default ({item, metrics, current, loading, onClick}) => {

	/**
	 * Handle row click event.
	 *
	 * @since 4.11.4
	 */
	const handleClick = () => {
		onClick({
			type: 'page',
			filter: item.filter,
			label: wdpI18n.labels.page + ': ' + item.name,
			name: item.name
		})
	}

	return (
		<tr
			key={item.name}
			className="wpmudui-table-item wpmudui-tracking"
			onClick={handleClick}
		>
			<td>
				{loading &&
				<span className="wpmudui-icon wpmudui-icon-loader wpmudui-loading">
				</span>
				}
				<span>{item.name}</span>
			</td>
			{metrics.map((metric) => {
					return item.hasOwnProperty(metric.key) && metric.key !== 'visit_time' &&
						<td
							key={metric.key}
							className={`wpmudui-table-views data-${metric.key} ${metric.key === current ? 'wpmudui-current' : ''}`}
							data-sort={item[metric.key]['sort']}
						>
							{item[metric.key]['value']}
						</td>
				}
			)}
		</tr>
	);
}