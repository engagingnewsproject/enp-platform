import React from 'react';
import {Chart} from 'chart.js'

export default class LineChart extends React.PureComponent {
	constructor(props) {
		super(props);

		this.chart = React.createRef();
		this.chartInstance = null;
	}

	/**
	 * Initialize chart after mounting.
	 *
	 * @since 4.11.6
	 */
	componentDidMount() {
		Chart.defaults.LineWithLine = Chart.defaults.line;
		Chart.controllers.LineWithLine = Chart.controllers.line.extend({
			draw: function (ease) {
				Chart.controllers.line.prototype.draw.call(this, ease);

				if (this.chart.tooltip._active && this.chart.tooltip._active.length) {
					let activePoint = this.chart.tooltip._active[0],
						ctx = this.chart.ctx,
						x = activePoint.tooltipPosition().x,
						topY = this.chart.scales['y-axis-0'].top,
						bottomY = this.chart.scales['y-axis-0'].bottom;

					// Draw line.
					ctx.save();
					ctx.beginPath();
					ctx.moveTo(x, topY);
					ctx.lineTo(x, bottomY);
					ctx.lineWidth = 1;
					ctx.strokeStyle = 'rgba(0, 0, 0, 0.1)';
					ctx.stroke();
					ctx.restore();
				}
			}
		});

		this.initChart();
	}

	/**
	 * Re init chart if options changed.
	 *
	 * @param {object} prevProps Previous props.
	 * @since 4.11.6
	 */
	componentDidUpdate(prevProps) {
		// Typical usage (don't forget to compare props):
		if (this.props.options !== prevProps.options) {
			this.destroyChart();
			this.initChart();
		}
	}

	/**
	 * Destroy chart instance when component is removed.
	 *
	 * @since 4.11.6
	 */
	componentWillUnmount() {
		this.destroyChart();
	}

	/**
	 * Initialize the chart instance.
	 *
	 * @since 4.11.6
	 */
	initChart() {
		// Destroy existing.
		this.destroyChart();

		// Create new chart instance.
		this.chartInstance = new Chart(
			jQuery(this.chart.current),
			this.props.options
		);
	}

	/**
	 * Destroy current chart instance.
	 *
	 * @since 4.11.6
	 */
	destroyChart() {
		if (null !== this.chartInstance) {
			this.chartInstance.destroy();
		}
	}

	render() {
		return (
			<canvas
				id="wpmudui-analytics-graph"
				ref={this.chart}
			>
			</canvas>
		);
	}
}