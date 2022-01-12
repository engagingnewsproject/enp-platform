import React from 'react'
import LineChart from './../../../components/line-chart'

export default ({data, type}) => {
	const config = {
		type: 'LineWithLine',
		data: {
			datasets: [{
				label: 'NA',
				backgroundColor: "rgba(0,133,186,0.98)",
				borderColor: "rgba(0,133,186,0.98)",
				borderWidth: 3,
				pointRadius: 3,
				pointBorderWidth: 1,
				pointBorderColor: 'white',
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
						var label = data.datasets[tooltipItem.datasetIndex].label || '';
						if (label) {
							label = (tooltipItem.yLabel * 100) + '% ' + label;
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
						callback: function (value) {
							return Math.round(value * 100) + "%"
						}
					}
				}]
			}
		}
	}

	if (data.hasOwnProperty(type)) {
		config.data.datasets[0].label = data[type].label;
		config.data.datasets[0].data = data[type].data;
	}

	return (
		<LineChart options={config}/>
	);
}