/* global wdp_analytics_ajax */
import React from 'react'
import moment from 'moment'
import ChartOptions from './overview/chart-options'
import ChartContent from './overview/chart-content'
import Autocomplete from './../components/autocomplete'
import PeriodSelector from './../components/period-selector'

export default class ContentOverview extends React.Component {
	constructor(props) {
		super(props);

		// Remove unwanted metrics.
		let skipMetrics = ['page_time', 'unique_pageviews']
		// Do not show exit rate on full site view.
		if (!this.props.isFiltered) {
			skipMetrics.push('exit_rate')
		}
		const filterMetrics = this.props.metrics.filter((metric) => !skipMetrics.includes(metric.key))

		this.state = {
			loading: false,
			currentOption: filterMetrics.length > 0 ? filterMetrics[0].key : ''
		}

		// Register change event.
		this.handleOptionChange = this.handleOptionChange.bind(this);
	}

	/**
	 * After mounting the component.
	 *
	 * @since 4.11.6
	 */
	componentDidMount() {
		this.initMomentLocale();
	}

	/**
	 * Setup locale for moment js to use it in chart.
	 *
	 * @since 4.11.6
	 */
	initMomentLocale() {
		moment.updateLocale(wdp_analytics_ajax.locale_settings.locale, {
			// Inherit anything missing from the default locale.
			parentLocale: moment.locale(),
			monthsShort: wdp_analytics_ajax.locale_settings.monthsShort,
			weekdays: wdp_analytics_ajax.locale_settings.weekdays
		});

		// Set the locale.
		moment.locale(wdp_analytics_ajax.locale_settings.locale);
	}

	/**
	 * Handle option change event.
	 *
	 * @param {string} option
	 * @since 4.11.4
	 */
	handleOptionChange(option) {
		this.setState({
			currentOption: option
		});
	}

	render() {
		let totals = {};
		let metrics = this.props.metrics;
		let skipMetrics = ['unique_pageviews']
		if (typeof this.props.data.totals !== 'undefined') {
			totals = this.props.data.totals;
		}

		// Do not show exit rate on full site view.
		if (!this.props.isFiltered) {
			skipMetrics.push('exit_rate');
		}

		// Remove unwanted metrics.
		metrics = metrics.filter(metric => !skipMetrics.includes(metric.key));

		return (
			<div
				data-pane="overview"
				className={`${this.props.currentTab === 'overview' ? 'wpmudui-tab-current ' : ''}wpmudui-tab-content`}
			>
				<div className="wpmudui-search-form">
					<PeriodSelector
						type="overview"
						period={this.props.period}
					/>

					<Autocomplete
						period={this.props.period.current}
						source={this.props.autocomplete}
					/>
				</div>

				<ChartContent
					data={this.props.data}
					current={this.state.currentOption}
				/>
				<ChartOptions
					totals={totals}
					options={metrics}
					current={this.state.currentOption}
					handleClick={this.handleOptionChange}
				/>
			</div>
		);
	}
}