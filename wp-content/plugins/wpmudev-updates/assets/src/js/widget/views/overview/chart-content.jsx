/* global wdpI18n */
import React from 'react'
import TimeChart from './charts/time-chart'
import NumberChart from './charts/number-chart'
import PercentageChart from './charts/percentage-chart'

export default class ChartContent extends React.PureComponent {
	constructor(props) {
		super(props);
	}

	/**
	 * Get the chart type component to render it.
	 *
	 *@since 4.11.6
	 * @return {JSX.Element}
	 */
	renderChart() {
		let content = this.props.data?.chart;
		if (!content) {
			content = {};
		}

		switch (this.props.current) {
			case 'page_time':
			case 'visit_time':
				return (
					<TimeChart
						data={content}
						type={this.props.current}
					/>
				)
			case 'bounce_rate':
			case 'exit_rate':
				return (
					<PercentageChart
						data={content}
						type={this.props.current}
					/>
				)
			default:
				return (
					<NumberChart
						data={content}
						type={this.props.current}
					/>
				)
		}
	}

	/**
	 * Check if data is empty.
	 *
	 * @since 4.11.6
	 * @return {boolean}
	 */
	isEmpty() {
		const data = this.props.data?.chart?.[this.props.current]?.data;
		if (!data) {
			return true;
		}

		return !data.some((item) => {
			return item.y !== null
		});
	}

	render() {
		return (
			<div className="wpmudui-analytics-chart">
				{this.isEmpty() &&
					<div className="wpmudui-analytics-chart-empty">
						<p className="wpmudui-analytics-chart-title">{wdpI18n.labels.empty}</p>
						<p>{wdpI18n.desc.empty}</p>
					</div>
				}
				{this.renderChart()}
			</div>
		);
	}
}