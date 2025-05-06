import React from 'react'
import LineChart from './../../../components/line-chart'

export default ({data, type}) => {
	let config = {
		type: 'LineWithLine',
		data: {
			datasets: [{
				label: 'NA',
				backgroundColor: "rgba(0,133,186,0.98)",
				borderColor: "rgba(0,133,186,0.98)",
				borderWidth: 3,
				pointRadius: 3,
				pointBorderWidth: 1,
				pointBorderColor: "white",
				pointBackgroundColor: "rgba(0,133,186,0.98)",
				fill: false,
				data: []
			}]
		},
		options: {
			legend: {
				display: true
			},
			tooltips: {
				mode: 'x',
				intersect: false,
				displayColors: false,
				titleFontSize: 12,
				callbacks: {
					label: function (tooltipItem, data) {
						let label = data.datasets[tooltipItem.datasetIndex].label || '';
						if (label) {
							label = tooltipItem.yLabel + ' ' + label;
						}
						return label;
					}
				}
			},
			scales: {
				xAxes: [{
					type: 'time',
					time: {
						round: 'day',
						minUnit: 'day',
						tooltipFormat: 'dddd, MMM. D'
					},
					gridLines: {
						display: false
					},
					scaleLabel: {
						display: false
					}
				}],
				yAxes: [{
					scaleLabel: {
						display: false
					},
					ticks: {
						min: 0,
						suggestedMax: ''
					}
				}]
			}
		}
	}

	if (data.hasOwnProperty(type)) {
		// Set YAxis max value for proper curve.
		let yAxisData = data[type].data.map((data) => data.y),
			yAxisDataMax = Math.max.apply(null, yAxisData),
			max = yAxisDataMax + Math.round(0.1 * yAxisDataMax);

		// for small data math round may cause issue
		// just increase a number for small data.
		if (yAxisDataMax === max) {
			max++;
		}

		config.options.scales.yAxes[0].ticks.suggestedMax = max;
		config.data.datasets[0].label = data[type].label;
		config.data.datasets[0].data = data[type].data;
	}

	if ('pageviews' === type) {
		if (typeof data['unique_pageviews'] !== 'undefined') {
			config.data.datasets.push({
				label: data['unique_pageviews'].label,
				backgroundColor: "purple",
				borderColor: "purple",
				borderWidth: 3,
				pointRadius: 3,
				pointBorderWidth: 1,
				pointBorderColor: "white",
				pointBackgroundColor: "purple",
				fill: false,
				data: data['unique_pageviews'].data
			});
		}
	}

	if ('visits' === type) {
		if (typeof data['unique_visits'] !== 'undefined') {
			config.data.datasets.push({
				label: data['unique_visits'].label,
				backgroundColor: "purple",
				borderColor: "purple",
				borderWidth: 3,
				pointRadius: 3,
				pointBorderWidth: 1,
				pointBorderColor: "white",
				pointBackgroundColor: "purple",
				fill: false,
				data: data['unique_visits'].data
			});
		}
	}

	return (
		<LineChart options={config}/>
	);
}